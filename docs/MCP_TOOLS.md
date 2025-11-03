# MCP AI Tools for Fakturino

**Feature Flag:** `FEATURE_MCP_AI_TOOLS=false` (default OFF)

## Overview

The MCP (Model Context Protocol) AI Tools integration provides intelligent automation and analysis capabilities for Fakturino through Claude Desktop and other AI assistants. This is powered by the open-source [maverick-mcp](https://github.com/wshobson/maverick-mcp) server (MIT license).

## Architecture

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│  Claude Desktop │ ◄─────► │  MCP Server      │ ◄─────► │  Laravel API    │
│  (or AI client) │  stdio  │  (Python/FastMCP)│  HTTP   │  (Fakturino)    │
└─────────────────┘         └──────────────────┘         └─────────────────┘
                                    │
                                    │ Bearer Token Auth
                                    │ (MCP_SERVER_TOKEN)
                                    ▼
                            Internal API Endpoints
                            /internal/mcp/*
```

## Components

### 1. MCP Server (Python/FastMCP)
- **Location:** `mcp-server/` (git submodule)
- **Base:** maverick-mcp (MIT license)
- **Plugin:** `maverick_mcp/plugins/fakturino_tools.py`
- **Port:** 3100 (default)
- **Transport:** SSE/HTTP/stdio

### 2. Laravel Backend
- **Middleware:** `App\Http\Middleware\VerifyMcpToken`
- **Controller:** `App\Http\Controllers\Internal\McpController`
- **Routes:** `routes/mcp.php`
- **Config:** `config/services.php` → `mcp` array

### 3. Security
- **Authentication:** Bearer token (`MCP_SERVER_TOKEN`)
- **Authorization:** Feature flag check (`FEATURE_MCP_AI_TOOLS`)
- **Rate Limiting:** 60 requests/minute per client
- **Scope:** Internal API only (not exposed to public)

## Available MCP Tools

### Invoice Management

#### `create_invoice`
Create a new invoice for a customer.

**Parameters:**
- `customer_id` (int): Customer ID
- `items` (list): Invoice items with name, quantity, price, tax
- `due_days` (int, optional): Days until due (default: 30)
- `invoice_date` (str, optional): Date in YYYY-MM-DD (default: today)
- `notes` (str, optional): Invoice notes

**Example:**
```python
create_invoice(
    customer_id=5,
    items=[
        {"name": "Web Development", "quantity": 10, "price": 5000.00, "tax": 18},
        {"name": "Hosting", "quantity": 1, "price": 1000.00, "tax": 18}
    ],
    due_days=15,
    notes="Payment due within 15 days"
)
```

#### `get_invoice_details`
Get detailed information about an invoice.

**Parameters:**
- `invoice_id` (int): Invoice ID

**Returns:**
- Invoice number, customer, items, subtotal, tax, total, status, due date

### Company Analytics

#### `get_company_stats`
Get company statistics including revenue, invoices, and customers.

**Parameters:**
- `company_id` (int): Company ID

**Returns:**
- `total_revenue`: Total revenue across all paid invoices
- `invoices_count`: Total number of invoices
- `customers_count`: Total number of customers
- `pending_invoices`: Number of unpaid invoices
- `overdue_invoices`: Number of overdue invoices
- `draft_invoices`: Number of draft invoices

### Customer Search

#### `search_customers`
Search for customers by name or email.

**Parameters:**
- `company_id` (int): Company ID
- `query` (str): Search query (minimum 2 characters)

**Returns:**
- List of matching customers with ID, name, email, phone, invoice count

### Accounting Tools

#### `get_trial_balance`
Get trial balance for accounting (requires `FEATURE_ACCOUNTING_BACKBONE`).

**Parameters:**
- `company_id` (int): Company ID
- `as_of_date` (str, optional): Date in YYYY-MM-DD (default: today)

**Returns:**
- `accounts`: List of accounts with balances
- `total_debits`: Sum of all debit balances
- `total_credits`: Sum of all credit balances
- `balanced`: True if debits equal credits

### UBL & Tax Tools

#### `ubl_validate`
Validate a UBL invoice XML and its digital signature.

**Parameters:**
- `invoice_id` (int): Invoice ID
- `xml_content` (str, optional): UBL XML content

**Returns:**
- `valid`: True if UBL structure is valid
- `signature_valid`: True if digital signature is valid
- `errors`: List of validation errors
- `warnings`: List of validation warnings

#### `tax_explain`
Explain tax calculation for an invoice (Macedonian DDV/VAT).

**Parameters:**
- `invoice_id` (int): Invoice ID

**Returns:**
- `subtotal`: Amount before tax
- `tax_rate`: Tax rate percentage (typically 18% DDV)
- `tax_amount`: Calculated tax amount
- `total`: Total amount including tax
- `items`: Per-item tax breakdown

### Banking Tools

#### `bank_categorize`
Suggest accounting category for a bank transaction (requires `FEATURE_PSD2_BANKING`).

**Parameters:**
- `transaction_id` (int): Bank transaction ID

**Returns:**
- `suggested_category`: Accounting category code
- `category_name`: Human-readable category name
- `confidence`: Confidence score (0-1)
- `reason`: Explanation for the suggestion

### Anomaly Detection

#### `anomaly_scan`
Scan for invoice anomalies in a date range.

**Parameters:**
- `company_id` (int): Company ID
- `start_date` (str): Start date in YYYY-MM-DD
- `end_date` (str): End date in YYYY-MM-DD

**Returns:**
- `anomalies`: List of detected issues
- `total_scanned`: Number of invoices scanned
- `issues_found`: Number of issues found
- `severity_breakdown`: Count by severity (high/medium/low)

**Detects:**
- Duplicate invoices (same customer, amount, date)
- Negative totals
- Unusual tax rates
- Missing required fields
- Suspicious patterns

## Setup Instructions

### 1. Install Dependencies

```bash
# Install MCP server dependencies
cd mcp-server
pip install -r requirements.txt
# or with uv (recommended)
uv sync
```

### 2. Configure Environment

Create `mcp-server/.env` from `mcp-server/.env.fakturino`:

```bash
PORT=3100
LARAVEL_INTERNAL_URL=http://localhost:8080  # or http://api:8080 for Railway
MCP_SERVER_TOKEN=<generate-secure-token>
```

Generate a secure token:
```bash
openssl rand -hex 32
```

Update Laravel `.env`:
```bash
FEATURE_MCP_AI_TOOLS=true
MCP_SERVER_URL=http://localhost:3100
MCP_SERVER_TOKEN=<same-token-as-above>
```

### 3. Start Services

**Development (local):**
```bash
# Terminal 1: Start Laravel
php artisan serve

# Terminal 2: Start MCP server
cd mcp-server
uv run python -m maverick_mcp.api.server --transport sse --port 3100
```

**Production (Railway):**
- MCP server runs as separate service (see `railway.json`)
- Uses internal network: `http://api:8080`
- Health check: `http://mcp-server:3100/health`

### 4. Configure Claude Desktop

Create or edit `~/Library/Application Support/Claude/claude_desktop_config.json` (macOS):

```json
{
  "mcpServers": {
    "fakturino": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "http://localhost:3100/sse"]
    }
  }
}
```

**Linux:** `~/.config/Claude/claude_desktop_config.json`
**Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

### 5. Test the Connection

1. Restart Claude Desktop
2. Start a conversation: "List available Fakturino tools"
3. Test a tool: "Get company statistics for company ID 1"

## Railway Deployment

### Service Configuration

Add to `railway.json`:

```json
{
  "services": {
    "mcp-server": {
      "source": {
        "repo": ".",
        "directory": "/mcp-server"
      },
      "build": {
        "builder": "NIXPACKS"
      },
      "env": {
        "PORT": "3100",
        "LARAVEL_INTERNAL_URL": "http://api:8080",
        "MCP_SERVER_TOKEN": "${MCP_SERVER_TOKEN}"
      },
      "healthcheck": {
        "path": "/health",
        "interval": 30
      }
    }
  }
}
```

### Environment Variables (Railway)

Set these in Railway dashboard:

```
MCP_SERVER_TOKEN=<secure-token>
FEATURE_MCP_AI_TOOLS=true
```

### Internal Networking

The `api` and `mcp-server` services communicate via Railway's internal network:
- API URL: `http://api:8080`
- MCP URL: `http://mcp-server:3100`

## Security Considerations

### Authentication Flow

1. MCP server receives tool call from Claude Desktop
2. MCP server makes HTTP POST to Laravel: `/internal/mcp/*`
3. Request includes `Authorization: Bearer <MCP_SERVER_TOKEN>`
4. Laravel middleware verifies token matches `config('services.mcp.token')`
5. If valid, execute and return result

### Token Requirements

- **Length:** Minimum 32 characters (64 recommended)
- **Entropy:** Use cryptographically secure random generator
- **Storage:** Environment variables only (never commit)
- **Rotation:** Rotate tokens periodically

### Rate Limiting

- **Default:** 60 requests/minute per MCP client
- **Health endpoint:** No rate limit (for monitoring)
- **Configurable:** Adjust in MCP server settings

## Monitoring

### Health Check

```bash
curl http://localhost:3100/internal/mcp/health
```

Response:
```json
{
  "status": "healthy",
  "service": "Fakturino MCP API",
  "timestamp": "2025-11-03T23:00:00Z"
}
```

### Logging

All MCP tool calls are logged:
- Laravel: `storage/logs/laravel.log`
- MCP Server: `mcp-server/logs/maverick_mcp.log`

Log format:
```
[2025-11-03 23:00:00] INFO: MCP: Company stats retrieved {"company_id": 1}
```

## Troubleshooting

### MCP Server Won't Start

**Error:** `ModuleNotFoundError: No module named 'fastmcp'`

**Solution:**
```bash
cd mcp-server
uv sync
```

### Token Authentication Fails

**Error:** `401 Unauthorized - Invalid MCP token`

**Check:**
1. Token matches in both `.env` files
2. Token has no whitespace/newlines
3. Feature flag is ON: `FEATURE_MCP_AI_TOOLS=true`

### Claude Desktop Can't Connect

**Error:** "No tools available"

**Check:**
1. MCP server is running: `lsof -i :3100`
2. Claude Desktop config is correct
3. Restart Claude Desktop after config changes

### Feature Disabled Error

**Error:** `403 Forbidden - MCP tools disabled`

**Solution:**
Enable in `.env`:
```bash
FEATURE_MCP_AI_TOOLS=true
```

## Development

### Adding New Tools

1. Add Python function to `mcp-server/maverick_mcp/plugins/fakturino_tools.py`:
```python
@tool()
def my_new_tool(ctx: Context, param1: str) -> dict:
    """Tool description for AI."""
    return _post("internal/mcp/my-endpoint", {"param1": param1})
```

2. Register in `register_fakturino_tools()`:
```python
mcp.tool()(my_new_tool)
```

3. Add Laravel endpoint to `app/Http/Controllers/Internal/McpController.php`:
```php
public function myEndpoint(Request $request): JsonResponse
{
    // Implementation
}
```

4. Add route to `routes/mcp.php`:
```php
Route::post('/my-endpoint', [McpController::class, 'myEndpoint']);
```

### Testing

```bash
# Laravel tests
php artisan test --filter=Mcp

# MCP server tests
cd mcp-server
pytest tests/
```

## License

- **MCP Server Base:** MIT License (wshobson/maverick-mcp)
- **Fakturino Plugin:** AGPL-3.0 (inherits from Fakturino)
- **Integration Layer:** AGPL-3.0

## References

- [Model Context Protocol Specification](https://modelcontextprotocol.io/)
- [FastMCP Documentation](https://github.com/jlowin/fastmcp)
- [MaverickMCP Repository](https://github.com/wshobson/maverick-mcp)
- [Claude Desktop Setup](https://claude.ai/settings/integrations)

---

**Last Updated:** 2025-11-03
**Version:** 1.0.0
**Feature Flag:** `FEATURE_MCP_AI_TOOLS`

// CLAUDE-CHECKPOINT
