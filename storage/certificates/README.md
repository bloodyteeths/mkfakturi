# Bank PSD2 Certificates Directory

This directory stores mTLS client certificates for bank PSD2 API authentication.

## What Goes Here

Place your bank certificates and private keys in this directory:

```
storage/certificates/
  nlb_sandbox.pem          # NLB sandbox certificate
  nlb_sandbox.key          # NLB sandbox private key
  nlb_production.pem       # NLB production certificate (DO NOT commit!)
  nlb_production.key       # NLB production private key (DO NOT commit!)
  stopanska_sandbox.pem    # Stopanska sandbox certificate
  stopanska_sandbox.key    # Stopanska sandbox private key
```

## Security

- ✅ Sandbox/test certificates: Safe to commit to private repos
- ❌ Production certificates: **NEVER commit to git!**
- Set proper permissions: `chmod 600 *.key` and `chmod 644 *.pem`

## Setup Instructions

See the main documentation: [BANK_CERTIFICATES.md](../../BANK_CERTIFICATES.md)

## For Railway Deployment

Use base64-encoded environment variables instead of committing certificates:

```bash
# Encode certificate
cat nlb_production.pem | base64

# Add to Railway environment variables:
# NLB_MTLS_CERT_BASE64=<paste base64 output>
```

See [BANK_CERTIFICATES.md](../../BANK_CERTIFICATES.md#railway-deployment) for full instructions.
