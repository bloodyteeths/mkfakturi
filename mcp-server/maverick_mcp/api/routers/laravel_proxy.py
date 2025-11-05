"""Proxy MCP requests to the Laravel API service.

These endpoints allow the internal MCP server to reuse the existing Laravel
implementations for company analytics and accounting tools by forwarding the
requests across the private Railway network.
"""

from __future__ import annotations

import logging
import os
from typing import Any, Tuple

import httpx
from fastapi import APIRouter, HTTPException, Request
from fastapi.responses import JSONResponse, Response

logger = logging.getLogger(__name__)

router = APIRouter(prefix="/internal/mcp", tags=["fakturino-proxy"])

_LARAVEL_URL = os.getenv("LARAVEL_INTERNAL_URL", "").rstrip("/")
_MCP_TOKEN = os.getenv("MCP_SERVER_TOKEN", "")
_TIMEOUT = float(os.getenv("MCP_PROXY_TIMEOUT", "15"))


async def _forward_to_laravel(
    endpoint: str,
    payload: dict[str, Any],
    inbound_headers: dict[str, str],
) -> Tuple[int, Any, str]:
    """Forward the MCP request to the Laravel internal API."""
    if not _LARAVEL_URL:
        logger.error("LARAVEL_INTERNAL_URL environment variable is not configured")
        raise HTTPException(
            status_code=503,
            detail="LARAVEL_INTERNAL_URL environment variable is not configured",
        )

    if not _MCP_TOKEN:
        logger.error("MCP_SERVER_TOKEN environment variable is not configured")
        raise HTTPException(
            status_code=503,
            detail="MCP_SERVER_TOKEN environment variable is not configured",
        )

    url = f"{_LARAVEL_URL}/internal/mcp/{endpoint}"
    headers = {
        "Authorization": f"Bearer {_MCP_TOKEN}",
        "Accept": "application/json",
    }

    # Preserve company context header if present
    company_header = inbound_headers.get("company")
    if company_header:
        headers["company"] = company_header

    try:
        async with httpx.AsyncClient(timeout=_TIMEOUT) as client:
            response = await client.post(url, json=payload, headers=headers)
    except httpx.RequestError as exc:
        logger.error(
            "Failed to reach Laravel MCP endpoint",
            extra={
                "endpoint": endpoint,
                "error": str(exc),
            },
        )
        raise HTTPException(
            status_code=502,
            detail=f"Failed to reach Laravel API: {exc}",
        ) from exc

    content_type = response.headers.get("content-type", "")

    if response.status_code >= 400:
        logger.warning(
            "Laravel MCP endpoint responded with error",
            extra={
                "endpoint": endpoint,
                "status": response.status_code,
                "body": response.text,
            },
        )
        if "application/json" in content_type:
            raise HTTPException(status_code=response.status_code, detail=response.json())

        raise HTTPException(
            status_code=response.status_code,
            detail={"error": response.text},
        )

    data: Any
    if "application/json" in content_type:
        data = response.json()
    else:
        data = response.text

    return response.status_code, data, content_type


@router.post("/{endpoint:path}")
async def proxy_internal_mcp(endpoint: str, request: Request) -> Response:
    """
    Proxy POST requests to the Laravel MCP API.

    All business logic lives in the Laravel application. This proxy allows the
    MCP server to expose compatible endpoints while delegating execution.
    """
    if endpoint.startswith("health"):
        # Let dedicated health routes handle these requests.
        raise HTTPException(status_code=404, detail="Not found")

    payload = await request.json()
    status_code, data, content_type = await _forward_to_laravel(
        endpoint,
        payload,
        {k.lower(): v for k, v in request.headers.items()},
    )

    if isinstance(data, str):
        return Response(content=data, media_type=content_type or "application/json", status_code=status_code)

    return JSONResponse(content=data, status_code=status_code)
