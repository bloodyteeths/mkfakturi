# Partner Demo User Credentials

## Automatic Setup ✅

The partner user is **automatically created** on Railway deployment via the `PartnerUserSeeder` which runs in `railway-start.sh`.

## Login Credentials

| Account Type    | Email                  | Password                          | Company      | Role        |
  |-----------------|------------------------|-----------------------------------|--------------|-------------|
  | Partner 1       | partner@demo.mk        | testpass123                       | Teknomed DOO | partner     |
  | Partner 2       | partner2@demo.mk       | testpass123                       | xyz          | partner     |
  | Company Owner 1 | admin@invoiceshelf.com | newpassword123                    | xyz          | super admin |
  | Company Owner 2 | your-email@example.com | (not shown - use reset if needed) | Teknomed DOO | super admin |

  Email: company@demo.mk
Password: testpass123
Role: user (regular company account)

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
