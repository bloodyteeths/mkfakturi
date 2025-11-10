# Railway Environment Variables

This document lists the environment variables for Railway deployment.

## Auto-Configured (Defaults Set by railway-start.sh)

When deploying to Railway, these are automatically configured:

| Variable | Default Value | Description |
|----------|---------------|-------------|
| `RAILWAY_SKIP_INSTALL` | `true` | Auto-enabled on Railway - skips installation wizard |
| `ADMIN_EMAIL` | `admin@facturino.mk` | Default admin email (can be overridden) |
| `ADMIN_PASSWORD` | `admin123` | Default admin password (can be overridden) |

## Required (Set by Railway MySQL Service)

These are automatically provided by Railway when you add a MySQL service:

| Variable | Source | Description |
|----------|--------|-------------|
| `MYSQL_URL` | Railway MySQL | Full MySQL connection URL |
| `MYSQLHOST` | Railway MySQL | MySQL host |
| `MYSQLPORT` | Railway MySQL | MySQL port |
| `MYSQLUSER` | Railway MySQL | MySQL username |
| `MYSQLPASSWORD` | Railway MySQL | MySQL password |
| `MYSQLDATABASE` | Railway MySQL | MySQL database name |

## Optional Configuration

You can override these in Railway environment variables:

### Admin User Credentials
```bash
ADMIN_EMAIL=your-email@example.com
ADMIN_PASSWORD=your-secure-password
```

### Installation Control
```bash
# Force run installation seeder (alternative to RAILWAY_SKIP_INSTALL)
RAILWAY_AUTO_INSTALL=true

# Seed sample data (for testing)
RAILWAY_SEED_DB=true
```

### Feature Flags
```bash
# Enable IFRS accounting backbone
FEATURE_ACCOUNTING_BACKBONE=true
```

### Application Settings
```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=  # Auto-generated on first run
APP_URL=https://your-domain.railway.app
```

### Session & Sanctum
These are auto-configured from `RAILWAY_PUBLIC_DOMAIN`:
```bash
SESSION_DOMAIN=.your-domain.railway.app
SANCTUM_STATEFUL_DOMAINS=your-domain.railway.app,localhost,127.0.0.1
```

### Cache & Queue
```bash
CACHE_STORE=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log
```

### PSD2 Banking (Optional)
```bash
PSD2_GATEWAY_BASE_URL=https://your-psd2-gateway.com
PSD2_REDIRECT_URI=https://your-domain.railway.app/bank/oauth/callback

# Bank certificates (base64 encoded)
NLB_MTLS_CERT_BASE64=...
NLB_MTLS_KEY_BASE64=...
STOPANSKA_MTLS_CERT_BASE64=...
STOPANSKA_MTLS_KEY_BASE64=...
```

## How It Works

1. **First Deployment**:
   - `RAILWAY_SKIP_INSTALL=true` is auto-enabled
   - Admin user is created with default credentials
   - Installation wizard is bypassed
   - Database marker file is created

2. **Subsequent Deployments**:
   - Checks if installation already completed
   - Skips installation if `profile_complete=COMPLETED`
   - Runs migrations

3. **Custom Admin Credentials**:
   - Set `ADMIN_EMAIL` and `ADMIN_PASSWORD` in Railway
   - Restart deployment to apply

## Quick Start

1. Add MySQL service in Railway
2. Deploy the application
3. Access at your Railway URL
4. Login with:
   - Email: `admin@facturino.mk` (or your ADMIN_EMAIL)
   - Password: `admin123` (or your ADMIN_PASSWORD)

**⚠️ Important**: Change the default password immediately after first login!

## Troubleshooting

### Installation wizard shows on first visit
- Check Railway logs for "RAILWAY_SKIP_INSTALL enabled"
- Verify `RAILWAY_ENVIRONMENT` variable exists
- Manually set `RAILWAY_SKIP_INSTALL=true` in Railway dashboard

### Admin user not created
- Check Railway logs for admin creation output
- Verify MySQL connection is working
- Check that migrations completed successfully

### Cannot login
- Verify `ADMIN_EMAIL` and `ADMIN_PASSWORD` match what you set
- Check Railway logs for "Admin credentials:" output
- Try password reset via `php artisan admin:reset`
