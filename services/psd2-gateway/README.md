# PSD2 Gateway Setup Guide

## Overview

This directory contains the Docker setup for the Open Banking Gateway, which provides PSD2 (Payment Services Directive 2) compliance for accessing bank account information from Macedonian banks.

**What it does:**
- Implements Berlin Group XS2A protocol
- Provides AIS (Account Information Service) for reading bank accounts, balances, and transactions
- Handles OAuth2 authentication flows with banks
- Manages rate limiting (15 requests/minute as per PSD2 guidelines)
- Supports multiple Macedonian banks (NLB, Stopanska, Komercijalna)

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         Your Laravel App                         │
│  (Facturino - Sends OAuth requests, receives account data)      │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ HTTP/REST API
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                    Open Banking Gateway                          │
│  (adorsys/open-banking-gateway Docker container)                │
│  - Berlin Group XS2A protocol implementation                    │
│  - OAuth2 flows                                                 │
│  - Bank adapter configurations                                  │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ PSD2 API Calls
                             │
┌────────────────────────────▼────────────────────────────────────┐
│                    Macedonian Banks                              │
│  - NLB Banka                                                    │
│  - Stopanska Banka                                              │
│  - Komercijalna Banka                                           │
└──────────────────────────────────────────────────────────────────┘
```

## Quick Start

### 1. Prerequisites

- Docker and Docker Compose installed
- OAuth credentials from banks (see Bank Registration section below)
- HTTPS domain (for production) or ngrok (for local development)

### 2. Configuration

```bash
# Copy environment template
cp gateway.env.example gateway.env

# Edit with your bank credentials
nano gateway.env
```

**Required fields:**
- `NLB_CLIENT_ID` and `NLB_CLIENT_SECRET` (from NLB developer portal)
- `STOPANSKA_CLIENT_ID` and `STOPANSKA_CLIENT_SECRET` (from Stopanska Bank)
- `KOMERCIJALNA_CLIENT_ID` and `KOMERCIJALNA_CLIENT_SECRET` (from Komercijalna Banka)
- `JWT_SECRET` (generate with: `openssl rand -base64 32`)
- Redirect URIs matching your domain

### 3. Start the Gateway

```bash
# Start gateway and database
docker-compose -f docker-compose.psd2.yml --env-file gateway.env up -d

# Check logs
docker-compose -f docker-compose.psd2.yml logs -f

# Check health
curl http://localhost:8080/actuator/health
```

### 4. Verify Setup

```bash
# Should return: {"status":"UP"}
curl http://localhost:8080/actuator/health

# Check available banks
curl http://localhost:8080/api/v1/banks
```

## Bank Registration

### NLB Banka (NLB Banka AD Skopje)

1. **Register for Developer Access:**
   - Go to: https://developer-ob.nlb.mk/
   - Create developer account
   - Register a new OAuth application

2. **Configure OAuth Application:**
   - **Application Name:** Facturino
   - **Application Type:** Web Application
   - **Redirect URIs:**
     - Sandbox: `https://www.facturino.mk/api/v1/banking/oauth/callback/nlb`
     - Production: `https://www.facturino.mk/api/v1/banking/oauth/callback/nlb`
   - **Scopes:** `openid` (or `AIS` depending on NLB's configuration)

3. **Get Credentials:**
   - Copy `Client ID` and `Client Secret`
   - Add to `gateway.env`:
     ```bash
     NLB_CLIENT_ID=your_client_id_here
     NLB_CLIENT_SECRET=your_client_secret_here
     NLB_REDIRECT_URI=https://www.facturino.mk/api/v1/banking/oauth/callback/nlb
     ```

4. **Important Notes:**
   - NLB requires PKCE (Proof Key for Code Exchange) - automatically handled by our `Psd2Client`
   - Sandbox URL: `https://sandbox-ob-api.nlb.mk`
   - Production URL: `https://ob-api.nlb.mk`
   - Rate limit: 15 requests per minute

### Stopanska Banka

1. **Contact for Access:**
   - Email: developer@stopanska.mk (or contact your account manager)
   - Request PSD2/Open Banking API access
   - Provide your company details and use case

2. **Configure OAuth Application:**
   - Provide redirect URI: `https://www.facturino.mk/api/v1/banking/oauth/callback/stopanska`
   - Request scopes: `accounts transactions`

3. **Get Credentials:**
   - Add to `gateway.env`:
     ```bash
     STOPANSKA_CLIENT_ID=your_client_id_here
     STOPANSKA_CLIENT_SECRET=your_client_secret_here
     ```

### Komercijalna Banka

1. **Contact for Access:**
   - Email: api@kbm.mk (or contact your account manager)
   - Request PSD2/Open Banking API access

2. **Configure OAuth Application:**
   - Provide redirect URI: `https://www.facturino.mk/api/v1/banking/oauth/callback/komercijalna`
   - Request scopes: `accounts transactions`

3. **Get Credentials:**
   - Add to `gateway.env`:
     ```bash
     KOMERCIJALNA_CLIENT_ID=your_client_id_here
     KOMERCIJALNA_CLIENT_SECRET=your_client_secret_here
     ```

## Configuration Files

### Bank Adapter Configuration

Create `config/bank-adapters.yml` to define bank-specific settings:

```yaml
# NLB Banka adapter
nlb:
  name: "NLB Banka AD Skopje"
  bic: "TUTNMK22"
  country: "MK"
  sandbox:
    base_url: "https://sandbox-ob-api.nlb.mk"
    auth_url: "https://sandbox-ob-api.nlb.mk/oauth"
  production:
    base_url: "https://ob-api.nlb.mk"
    auth_url: "https://ob-api.nlb.mk/oauth"
  features:
    - AIS
  requires_pkce: true
  rate_limit: 15  # requests per minute

# Stopanska Banka adapter
stopanska:
  name: "Stopanska Banka AD Skopje"
  bic: "STOBMK2X"
  country: "MK"
  sandbox:
    base_url: "https://sandbox-api.stopanska.mk"
  production:
    base_url: "https://api.stopanska.mk"
  features:
    - AIS
  requires_pkce: false
  rate_limit: 15

# Komercijalna Banka adapter
komercijalna:
  name: "Komercijalna Banka AD Skopje"
  bic: "KOBSMK2X"
  country: "MK"
  sandbox:
    base_url: "https://sandbox-api.kbm.mk"
  production:
    base_url: "https://api.kbm.mk"
  features:
    - AIS
  requires_pkce: false
  rate_limit: 15
```

## Local Development

### Using ngrok for Local Testing

Banks require HTTPS redirect URIs. For local development:

```bash
# Install ngrok
brew install ngrok  # macOS
# or download from https://ngrok.com/

# Start ngrok tunnel
ngrok http 8000

# Update redirect URIs in bank portals to ngrok URL
# Example: https://abc123.ngrok.io/api/v1/banking/oauth/callback/nlb
```

### Testing OAuth Flow

1. Start your Laravel app: `php artisan serve`
2. Start PSD2 gateway: `docker-compose -f docker-compose.psd2.yml up`
3. Navigate to: `http://localhost:8000/banking`
4. Click "Connect NLB Bank"
5. You'll be redirected to NLB login
6. After authorization, you'll be redirected back with accounts

## Troubleshooting

### Error: "Invalid redirect_uri"

**Cause:** The redirect URI in your OAuth request doesn't match what's registered in the bank's developer portal.

**Solution:**
1. Check the exact redirect URI in your `gateway.env`
2. Log in to the bank's developer portal
3. Verify the redirect URI matches EXACTLY (including protocol, domain, path)
4. Common mistakes:
   - `http://` instead of `https://`
   - Extra/missing trailing slash
   - Wrong domain (localhost vs ngrok vs production)

### Error: "Invalid scope"

**Cause:** The scopes you're requesting don't match what's configured in the bank portal.

**Solution for NLB:**
- NLB automatically grants scopes based on your application type
- Try these options in `gateway.env`:
  ```bash
  # Option 1: Just OpenID
  NLB_SCOPES=openid

  # Option 2: PSD2-specific
  NLB_SCOPES=AIS

  # Option 3: Empty
  NLB_SCOPES=
  ```
- Clear config cache: `php artisan config:clear`
- Restart gateway: `docker-compose -f docker-compose.psd2.yml restart`

**Solution for Stopanska/Komercijalna:**
- Use standard scopes: `accounts transactions`
- Confirm with bank support which scopes your application is approved for

### Error: "mTLS authentication failed"

**Cause:** Some banks require mutual TLS (client certificates) for API calls.

**Solution:**
1. Request client certificates from the bank
2. Add certificate paths to `gateway.env`:
   ```bash
   NLB_CERT_PATH=/path/to/client-cert.pem
   NLB_KEY_PATH=/path/to/client-key.pem
   NLB_KEY_PASSWORD=cert_password
   ```
3. Mount certificates in `docker-compose.psd2.yml`:
   ```yaml
   volumes:
     - /path/to/certs:/app/certs:ro
   ```

See `BANK_CERTIFICATES.md` for detailed certificate setup instructions.

### Error: "Rate limit exceeded"

**Cause:** Exceeded 15 requests per minute (PSD2 standard).

**Solution:**
- Implement caching in your Laravel app
- Use queue jobs with delays: `sleep(60)` between bank sync jobs
- In your Laravel code:
  ```php
  // In BankSyncJob
  public function handle()
  {
      foreach ($connections as $connection) {
          // Sync transactions
          $this->syncTransactions($connection);

          // Sleep 60 seconds to respect rate limit
          if (!$this->isLastConnection($connection)) {
              sleep(60);
          }
      }
  }
  ```

### Gateway Won't Start

```bash
# Check logs
docker-compose -f docker-compose.psd2.yml logs

# Check if ports are available
lsof -i :8080  # Gateway port
lsof -i :5433  # Postgres port

# Reset everything
docker-compose -f docker-compose.psd2.yml down -v
docker-compose -f docker-compose.psd2.yml up -d
```

## Production Deployment

### Railway Deployment

The PSD2 gateway can be deployed alongside your Laravel app on Railway:

1. **Add Gateway Service:**
   - In Railway dashboard, create new service
   - Deploy from this directory
   - Set environment variables from `gateway.env`

2. **Configure Internal URL:**
   - In your Laravel app's Railway environment:
     ```bash
     PSD2_GATEWAY_BASE_URL=http://psd2-gateway:8080
     ```
   - Railway's private networking allows services to communicate internally

3. **Volume for Bank Adapters:**
   - Mount `config/bank-adapters.yml` as a volume
   - Or store configuration in Railway environment variables

### Security Checklist

- [ ] Use strong `JWT_SECRET` (32+ characters)
- [ ] Use HTTPS for all redirect URIs
- [ ] Store credentials in secrets manager (not plain text)
- [ ] Enable rate limiting (`RATE_LIMIT_ENABLED=true`)
- [ ] Use production bank URLs (not sandbox)
- [ ] Restrict CORS to your domain only
- [ ] Monitor logs for suspicious activity
- [ ] Rotate OAuth credentials regularly
- [ ] Keep Docker images updated

## Monitoring

### Health Check

```bash
# Gateway health
curl http://localhost:8080/actuator/health

# Database health
docker-compose -f docker-compose.psd2.yml exec psd2-postgres pg_isready
```

### Logs

```bash
# Gateway logs
docker-compose -f docker-compose.psd2.yml logs -f open-banking-gateway

# Database logs
docker-compose -f docker-compose.psd2.yml logs -f psd2-postgres
```

### Metrics

The gateway exposes Prometheus metrics at:
```
http://localhost:8080/actuator/prometheus
```

Metrics include:
- Request count and duration
- OAuth flow success/failure rates
- Bank-specific error rates
- Rate limit violations

## Support

- **Open Banking Gateway Issues:** https://github.com/adorsys/open-banking-gateway/issues
- **NLB Developer Portal:** https://developer-ob.nlb.mk/
- **Stopanska Bank API Support:** developer@stopanska.mk
- **Komercijalna Banka API Support:** api@kbm.mk

## License

This Docker setup is part of Facturino (AGPL-3.0).

The Open Banking Gateway (`adorsys/open-banking-gateway`) is Apache-2.0 licensed.

## References

- [Berlin Group NextGenPSD2 Specification](https://www.berlin-group.org/nextgenpsd2-downloads)
- [PSD2 Regulatory Technical Standards](https://www.eba.europa.eu/regulation-and-policy/payment-services-and-electronic-money/regulatory-technical-standards-on-strong-customer-authentication-and-secure-communication-under-psd2)
- [adorsys Open Banking Gateway](https://github.com/adorsys/open-banking-gateway)

# CLAUDE-CHECKPOINT
