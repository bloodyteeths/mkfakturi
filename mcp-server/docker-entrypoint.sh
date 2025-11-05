#!/bin/sh
# Docker entrypoint script for Maverick MCP
# Reads Railway's PORT environment variable and starts the server

# Use Railway's PORT if set, otherwise default to 8000
PORT=${PORT:-8000}

echo "Starting Maverick MCP server on port $PORT..."

# Start the MCP server with the configured port
exec uv run python -m maverick_mcp.api.server \
  --transport sse \
  --host 0.0.0.0 \
  --port "$PORT"
