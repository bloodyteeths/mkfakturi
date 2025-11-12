# Bank PSD2 Certificate Setup Guide

## What Are PSD2 Certificates?

PSD2 (Payment Services Directive 2) APIs require **mTLS (Mutual TLS)** authentication. This means both the client (your application) and the server (the bank) authenticate each other using digital certificates.

### Certificate Types Required

1. **QWAC (Qualified Website Authentication Certificate)**
   - Used for mTLS authentication
   - Proves your identity to the bank's API
   - Required for all PSD2 API calls (accounts, transactions)

2. **QSealC (Qualified Electronic Seal Certificate)** *(may be required for some banks)*
   - Used for signing API requests
   - Required for payment initiation (PIS) APIs
   - Not required for read-only account information (AIS)

## NLB Bank Certificate Setup

### Option 1: Sandbox Test Certificates (Recommended for Testing)

Most PSD2 sandbox environments accept test certificates. NLB Bank likely provides one of these options:

#### A. Self-Generated Test Certificates

You can generate your own test certificates for sandbox:

```bash
# Create storage/certificates directory
mkdir -p storage/certificates
cd storage/certificates

# Generate private key
openssl genrsa -out nlb_test.key 2048

# Generate certificate signing request (CSR)
openssl req -new -key nlb_test.key -out nlb_test.csr \
  -subj "/C=MK/O=YourCompany/CN=YourAppName"

# Generate self-signed certificate (valid for 1 year)
openssl x509 -req -days 365 -in nlb_test.csr \
  -signkey nlb_test.key -out nlb_test.crt

# Combine into PEM format (some APIs require this)
cat nlb_test.crt nlb_test.key > nlb_test.pem
```

Then add to your `.env`:

```env
NLB_MTLS_CERT_PATH=nlb_test.pem
NLB_MTLS_KEY_PATH=nlb_test.key
# No password needed for unencrypted key
```

#### B. NLB Developer Portal Test Certificates

1. **Log in to NLB Developer Portal**: https://developer-ob.nlb.mk/
2. **Navigate to Your Application**
3. **Look for Certificate Management Section**:
   - May be under "Certificates", "Security", or "Settings"
   - Some portals have a "Generate Test Certificate" button
4. **Generate or Upload Certificates**:
   - If generate option: Download the generated `.pem` and `.key` files
   - If upload option: Upload your self-generated certificates from Option A
5. **Download Certificates** to `storage/certificates/` directory
6. **Configure in `.env`** (see below)

### Option 2: Production eIDAS Certificates

For production use, you **must** obtain official eIDAS-qualified certificates from a Qualified Trust Service Provider (QTSP).

#### Qualified Trust Service Providers (QTSPs)

Choose one of these providers:

- **Buypass** (Norway) - https://www.buypass.com/products/eseal--and-enterprise-certificate/psd2-rts-qualified-certificate-for-strong-authentication-and-encryption
- **DigiCert** - https://www.digicert.com/tls-ssl/psd2-certificates
- **Sectigo** - https://www.sectigo.com/ssl-certificates-tls/psd2-qwac
- **D-Trust** (Germany) - https://www.d-trust.net/en/solutions/psd2
- **MultiCert** (Portugal) - https://www.multicert.com/en/certificates/authentication-and-security/psd2-online-banking-eidas/
- **Disig** (Slovakia) - https://eidas.disig.sk/en/qualified-certificates/qc-psd2/

#### Steps to Obtain Production Certificates

1. **Register with a QTSP**
   - Provide company registration documents
   - Provide proof of PSD2 registration with financial authority
   - Pay for certificate (€500-€2000/year typically)

2. **Generate Certificate Signing Request (CSR)**
   ```bash
   openssl req -new -newkey rsa:2048 -nodes \
     -keyout nlb_prod.key -out nlb_prod.csr \
     -subj "/C=MK/O=YourCompanyLegalName/CN=facturino.mk"
   ```

3. **Submit CSR to QTSP**
   - Upload CSR via their portal
   - Complete identity verification process
   - Wait for certificate issuance (1-5 business days)

4. **Download Issued Certificate**
   - Download `.crt` or `.pem` file from QTSP
   - Keep your `.key` file secure

5. **Upload to NLB Developer Portal**
   - Log in to https://developer-ob.nlb.mk/
   - Navigate to your application
   - Upload the production certificate
   - **Important**: NLB must approve production certificates

6. **Configure in Application** (see below)

## Stopanska Bank Certificate Setup

Stopanska Bank likely has similar requirements. Follow the same process:

1. Check Stopanska developer portal: https://ob.stb.kibs.mk/docs/getting-started
2. Generate or obtain certificates
3. Configure using `STOPANSKA_MTLS_*` environment variables

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# NLB Bank Certificates
NLB_MTLS_CERT_PATH=nlb_sandbox.pem
NLB_MTLS_KEY_PATH=nlb_sandbox.key
# Only needed if key is password-protected:
# NLB_MTLS_KEY_PASSWORD=your_key_password

# Stopanska Bank Certificates (if needed)
STOPANSKA_MTLS_CERT_PATH=stopanska_sandbox.pem
STOPANSKA_MTLS_KEY_PATH=stopanska_sandbox.key
# STOPANSKA_MTLS_KEY_PASSWORD=your_key_password
```

### Certificate File Locations

Certificates can be placed in two ways:

#### Option 1: Relative Path (Recommended)

Place certificates in `storage/certificates/` directory:

```
storage/
  certificates/
    nlb_sandbox.pem
    nlb_sandbox.key
    nlb_production.pem
    nlb_production.key
    stopanska_sandbox.pem
    stopanska_sandbox.key
```

Then use just the filename in `.env`:

```env
NLB_MTLS_CERT_PATH=nlb_sandbox.pem
NLB_MTLS_KEY_PATH=nlb_sandbox.key
```

#### Option 2: Absolute Path

Store certificates anywhere and use absolute paths:

```env
NLB_MTLS_CERT_PATH=/etc/ssl/certs/nlb_prod.pem
NLB_MTLS_KEY_PATH=/etc/ssl/private/nlb_prod.key
```

### Railway Deployment

For Railway, you need to include certificates in your deployment:

#### Method 1: Railway Secrets (Recommended)

1. **Convert certificates to base64**:
   ```bash
   cat storage/certificates/nlb_sandbox.pem | base64
   cat storage/certificates/nlb_sandbox.key | base64
   ```

2. **Add to Railway environment variables**:
   - `NLB_MTLS_CERT_BASE64` = base64-encoded certificate
   - `NLB_MTLS_KEY_BASE64` = base64-encoded key

3. **Decode in startup script** (`railway-start.sh`):
   ```bash
   # Create certificates directory
   mkdir -p storage/certificates

   # Decode certificates from environment variables
   if [ ! -z "$NLB_MTLS_CERT_BASE64" ]; then
       echo "$NLB_MTLS_CERT_BASE64" | base64 -d > storage/certificates/nlb.pem
   fi

   if [ ! -z "$NLB_MTLS_KEY_BASE64" ]; then
       echo "$NLB_MTLS_KEY_BASE64" | base64 -d > storage/certificates/nlb.key
   fi
   ```

4. **Set paths in Railway variables**:
   ```
   NLB_MTLS_CERT_PATH=nlb.pem
   NLB_MTLS_KEY_PATH=nlb.key
   ```

#### Method 2: Commit to Private Repo (Sandbox Only)

For sandbox/test certificates (NOT production), you can commit them:

```bash
git add storage/certificates/nlb_sandbox.*
git commit -m "Add NLB sandbox certificates"
git push
```

**⚠️ WARNING**: Never commit production certificates to git!

## Certificate Formats

Different banks may require different formats:

### PEM Format (Most Common)

- File extension: `.pem`, `.crt`, `.cer`
- Text file starting with `-----BEGIN CERTIFICATE-----`
- Can contain both certificate and key

### DER Format

- Binary format
- File extension: `.der`, `.cer`
- Convert to PEM: `openssl x509 -inform der -in cert.der -out cert.pem`

### PKCS#12 / PFX Format

- Contains certificate + private key in one file
- File extension: `.p12`, `.pfx`
- Extract certificate: `openssl pkcs12 -in cert.p12 -clcerts -nokeys -out cert.pem`
- Extract key: `openssl pkcs12 -in cert.p12 -nocerts -nodes -out key.pem`

## Troubleshooting

### Error: "mTLS verification failed"

**Causes**:
1. No certificates configured
2. Certificates not uploaded to bank's developer portal
3. Certificate paths incorrect
4. Certificate expired
5. Wrong certificate format

**Solutions**:
1. Check `.env` has correct certificate paths
2. Verify files exist: `ls -la storage/certificates/`
3. Check certificate validity: `openssl x509 -in cert.pem -text -noout`
4. Ensure certificate is uploaded to NLB developer portal
5. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Error: "Unable to read certificate file"

**Causes**:
1. File doesn't exist at specified path
2. File permissions incorrect
3. Path is relative but file not in `storage/certificates/`

**Solutions**:
```bash
# Check file exists
ls -la storage/certificates/

# Fix permissions
chmod 600 storage/certificates/*.key
chmod 644 storage/certificates/*.pem

# Check Laravel can read them
php artisan tinker
> file_exists(storage_path('certificates/nlb_sandbox.pem'))
```

### Error: "SSL certificate problem: unable to get local issuer certificate"

**Cause**: Server certificate verification failing

**Solution**: Check if you need to add CA bundle:

```php
// In Psd2Client, modify addMtlsCertificate():
$options = [
    'cert' => $certPath,
    'ssl_key' => $keyPassword ? [$keyPath, $keyPassword] : $keyPath,
    'verify' => '/path/to/ca-bundle.crt', // Add CA bundle
];
```

### Test Certificate Locally

Before deploying, test if certificates work:

```bash
# Test certificate with OpenSSL
openssl s_client -connect developer-ob.nlb.mk:443 \
  -cert storage/certificates/nlb_sandbox.pem \
  -key storage/certificates/nlb_sandbox.key
```

## Next Steps After Certificate Setup

1. **Add certificates** to `storage/certificates/` directory
2. **Configure `.env`** with certificate paths
3. **Upload certificates** to NLB developer portal (if required)
4. **Test connection**: Try connecting bank in Banking dashboard
5. **Check logs**: Monitor `storage/logs/laravel.log` for certificate issues
6. **If successful**: You should see accounts fetched from NLB API

## Security Best Practices

### DO:
- ✅ Store production certificates in secure environment variables
- ✅ Use different certificates for sandbox vs production
- ✅ Set restrictive file permissions (600 for keys, 644 for certs)
- ✅ Rotate certificates before expiry
- ✅ Keep private keys encrypted with strong passwords
- ✅ Monitor certificate expiry dates

### DON'T:
- ❌ Commit production certificates to git
- ❌ Share private keys via email/chat
- ❌ Use sandbox certificates in production
- ❌ Give world-readable permissions to private keys
- ❌ Store certificates in publicly accessible directories

## Resources

### NLB Bank
- Developer Portal: https://developer-ob.nlb.mk/
- Support: Contact via developer portal

### eIDAS Certificate Providers
- Buypass: https://www.buypass.com/products/eseal--and-enterprise-certificate/psd2-rts-qualified-certificate-for-strong-authentication-and-encryption
- DigiCert: https://www.digicert.com/tls-ssl/psd2-certificates
- Sectigo: https://www.sectigo.com/ssl-certificates-tls/psd2-qwac

### PSD2 Standards
- Berlin Group NextGenPSD2: https://www.berlin-group.org/nextgenpsd2-downloads
- eIDAS Regulation: https://ec.europa.eu/digital-single-market/en/trust-services-and-eid

---

**Need Help?**

If you're stuck, check:
1. NLB developer portal documentation
2. Laravel logs: `storage/logs/laravel.log`
3. Debug endpoint: `/debug/logs`
4. Contact NLB support via their developer portal
