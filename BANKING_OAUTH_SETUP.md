# Banking OAuth Setup Guide

## The Problem

When connecting to NLB Bank (or any PSD2 bank), you may encounter this error:

```
Sorry, there was an error : Code:invalid_request, Description: Invalid redirect_uri
```

This happens because the **redirect_uri** used in the OAuth flow doesn't match what's registered in the bank's developer portal.

## The Solution

### Step 1: Identify Your Redirect URI

Your redirect URI is the URL where the bank will send users after they authorize the connection. It follows this format:

```
https://YOUR_DOMAIN/api/v1/banking/oauth/callback/{provider}
```

**Examples:**
- For NLB Bank: `https://www.facturino.mk/api/v1/banking/oauth/callback/nlb`
- For Stopanska Bank: `https://www.facturino.mk/api/v1/banking/oauth/callback/stopanska`

### Step 2: Register Redirect URI in Bank Developer Portal

#### For NLB Bank:

1. Go to [NLB Developer Portal](https://developer-ob.nlb.mk/)
2. Log in to your developer account
3. Navigate to your OAuth application settings
4. Add the redirect URI:
   - **Sandbox**: `https://www.facturino.mk/api/v1/banking/oauth/callback/nlb`
   - **Production**: Same URL (or your production domain)
5. Save the changes

#### For Stopanska Bank:

1. Go to Stopanska Bank Developer Portal
2. Log in to your developer account
3. Navigate to your OAuth application settings
4. Add the redirect URI:
   - **Sandbox**: `https://www.facturino.mk/api/v1/banking/oauth/callback/stopanska`
   - **Production**: Same URL (or your production domain)
5. Save the changes

### Step 3: Configure Environment Variables (Optional)

If you need to use a different redirect URI than the auto-generated one, you can configure it in your `.env` file:

```bash
# NLB Bank OAuth Configuration
NLB_CLIENT_ID=your_client_id_here
NLB_CLIENT_SECRET=your_client_secret_here
NLB_ENVIRONMENT=sandbox
NLB_REDIRECT_URI=https://www.facturino.mk/api/v1/banking/oauth/callback/nlb

# Stopanska Bank OAuth Configuration
STOPANSKA_CLIENT_ID=your_client_id_here
STOPANSKA_CLIENT_SECRET=your_client_secret_here
STOPANSKA_ENVIRONMENT=sandbox
STOPANSKA_REDIRECT_URI=https://www.facturino.mk/api/v1/banking/oauth/callback/stopanska
```

### Step 4: Clear Config Cache

After updating environment variables, clear the config cache:

```bash
php artisan config:clear
php artisan config:cache
```

## Important Notes

1. **Exact Match Required**: The redirect URI must match EXACTLY what's registered in the developer portal, including:
   - Protocol (https://)
   - Domain
   - Path
   - Port (if any)

2. **Multiple Environments**: If you have separate sandbox and production environments, register redirect URIs for both:
   - Sandbox: `https://sandbox.facturino.mk/api/v1/banking/oauth/callback/nlb`
   - Production: `https://www.facturino.mk/api/v1/banking/oauth/callback/nlb`

3. **Testing Locally**: For local development, you may need to:
   - Use ngrok or similar tunneling service
   - Register a localhost redirect URI (if bank allows)
   - Example: `http://localhost:8000/api/v1/banking/oauth/callback/nlb`

## Troubleshooting

### Still Getting "Invalid redirect_uri" Error?

1. **Double-check the registration**: Log in to the bank's developer portal and verify the redirect URI is correctly saved

2. **Check for typos**: Even a small typo will cause the error:
   - ✅ Correct: `https://www.facturino.mk/api/v1/banking/oauth/callback/nlb`
   - ❌ Wrong: `https://www.facturino.mk/api/v1/banking/oauth/callbacks/nlb` (extra 's')
   - ❌ Wrong: `http://www.facturino.mk/api/v1/banking/oauth/callback/nlb` (http instead of https)

3. **Wait for propagation**: Some banks may take a few minutes to propagate configuration changes

4. **Check logs**: Look at `storage/logs/laravel.log` to see what redirect_uri is actually being sent:
   ```bash
   tail -f storage/logs/laravel.log | grep "redirect_uri"
   ```

5. **Contact bank support**: If you're certain the configuration is correct, contact the bank's developer support

## Developer Portal Links

- **NLB Bank**: https://developer-ob.nlb.mk/
- **Stopanska Bank**: Contact your account manager for developer portal access

## Testing the Connection

Once configured, test the connection:

1. Log in to Facturino
2. Navigate to **Banking** → **Connect Bank**
3. Select NLB Bank (or your bank)
4. Click **Connect**
5. You should be redirected to the bank's login page
6. After logging in and authorizing, you should be redirected back to Facturino
7. Your bank accounts should appear in the Banking dashboard

## Security Best Practices

1. **Use HTTPS**: Always use HTTPS in production for security
2. **Keep credentials secure**: Never commit `NLB_CLIENT_ID` and `NLB_CLIENT_SECRET` to version control
3. **Rotate credentials**: Regularly rotate your OAuth credentials
4. **Monitor logs**: Keep an eye on OAuth logs for suspicious activity

## Need Help?

If you're still having issues, check:
- Laravel logs: `storage/logs/laravel.log`
- Browser console for JavaScript errors
- Network tab to see the actual OAuth request being made
