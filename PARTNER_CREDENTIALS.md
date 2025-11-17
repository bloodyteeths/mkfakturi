# Partner Demo User Credentials

## Automatic Setup ✅

The partner user is **automatically created** on Railway deployment via the `PartnerUserSeeder` which runs in `railway-start.sh`.

## Login Credentials

**Email:** `partner@demo.mk`
**Password:** `Partner2025!`

## How It Works

1. **On Railway Deployment:**
   - The `railway-start.sh` script runs during deployment
   - It executes `php artisan db:seed --class=PartnerUserSeeder`
   - The seeder creates the partner user if it doesn't exist
   - The user is automatically attached to the first company

2. **Idempotent:**
   - Running the seeder multiple times is safe
   - It checks if `partner@demo.mk` already exists
   - If exists, it skips creation and shows "Partner user already exists"

3. **Local Testing:**
   ```bash
   php artisan db:seed --class=PartnerUserSeeder
   ```

## Files Modified

- ✅ `database/seeders/PartnerUserSeeder.php` - The seeder that creates the partner user
- ✅ `railway-start.sh` - Updated to run the seeder on deployment (line ~429)
- ✅ `PARTNER_TESTING_GUIDE.md` - Complete UI testing instructions

## Next Steps

See `PARTNER_TESTING_GUIDE.md` for comprehensive UI testing instructions.
