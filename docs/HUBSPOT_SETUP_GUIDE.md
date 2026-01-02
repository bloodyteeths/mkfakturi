# HubSpot Setup Guide for Facturino CRM Integration

This guide covers the complete setup process for integrating HubSpot CRM with Facturino's partner outreach system.

---

## 1. HubSpot Free CRM Setup (Manual Steps)

### 1.1 Create HubSpot Account
- Sign up at [hubspot.com](https://hubspot.com) (free tier is sufficient)
- For EU region, you'll be on `app-eu1.hubspot.com`
- Complete basic setup wizard

### 1.2 Create an App for API Access

> **Note (December 2025)**: HubSpot has renamed "Private Apps" to **Legacy Apps** and introduced
> a new 2025.2 app system via CLI. For simple API access tokens, Legacy Apps still work fine.

#### Option A: Legacy Private App (Recommended for simplicity)

1. Go to **Settings** (gear icon ⚙️) → **Integrations** → **Private Apps**
   - If you see "Legacy Apps" instead, click that
   - Direct URL: `https://app.hubspot.com/private-apps/{YOUR_ACCOUNT_ID}`
   - EU region: `https://app-eu1.hubspot.com/private-apps/{YOUR_ACCOUNT_ID}`
2. Click **"Create a private app"** (orange button)
3. **Basic Info** tab:
   - Name: `Facturino Integration`
   - Description: `CRM sync for partner outreach`
4. **Scopes** tab - select these permissions:
   - **CRM**:
     - `crm.objects.contacts.read` + `crm.objects.contacts.write`
     - `crm.objects.companies.read` + `crm.objects.companies.write`
     - `crm.objects.deals.read` + `crm.objects.deals.write`
   - **CRM Schemas** (for custom properties):
     - `crm.schemas.contacts.read` + `crm.schemas.contacts.write`
     - `crm.schemas.companies.read` + `crm.schemas.companies.write`
     - `crm.schemas.deals.read` + `crm.schemas.deals.write`
   - **Timeline/Engagements**:
     - `timeline` (for activity logging)
     - `sales-email-read`
5. Click **"Create app"**
6. Click **"Show token"** → Copy the access token
   - Token format: `pat-eu1-xxxxxxxx` (EU) or `pat-na1-xxxxxxxx` (US)
   - This is your `HUBSPOT_ACCESS_TOKEN`

#### Option B: HubSpot CLI (Advanced - for developers)

If you prefer command-line setup:

```bash
# 1. Install HubSpot CLI globally
npm install -g @hubspot/cli

# 2. Authenticate with your HubSpot account
hs init
# Follow prompts - choose "personal access key" method
# This creates ~/.hubspot/hubspot.config.yml

# 3. Create a new project
hs project create
# Name: facturino-integration
# Choose: "Start from scratch" or a template

# 4. Edit the app configuration
# In your project folder, edit src/app/app.json:
```

**app.json** example:
```json
{
  "name": "Facturino Integration",
  "description": "CRM sync for partner outreach",
  "scopes": [
    "crm.objects.contacts.read",
    "crm.objects.contacts.write",
    "crm.objects.companies.read",
    "crm.objects.companies.write",
    "crm.objects.deals.read",
    "crm.objects.deals.write",
    "crm.schemas.contacts.read",
    "crm.schemas.contacts.write",
    "crm.schemas.deals.read",
    "crm.schemas.deals.write",
    "timeline"
  ]
}
```

```bash
# 5. Upload and deploy
hs project upload

# 6. Get the access token (still requires UI)
# Go to: HubSpot → CRM Development → Your App → Auth tab
# Click "Show token" and copy it
```

> **Note**: Even with CLI, you must visit the HubSpot UI to copy the access token.
> For simple API access, **Option A (Legacy App)** is faster.

Reference: [HubSpot CLI Commands](https://developers.hubspot.com/docs/platform/project-cli-commands) | [@hubspot/cli on npm](https://www.npmjs.com/package/@hubspot/cli)

#### Can't find Private Apps?

- **Free tier restriction**: Some accounts may not see the option. You need at least a
  [Super Admin](https://knowledge.hubspot.com/settings/hubspot-user-permissions-guide) role.
- **Try direct URL**: `https://app-eu1.hubspot.com/private-apps/{ACCOUNT_ID}`
- **Alternative**: Use the App Marketplace → Build → Create a public app (more complex)

### 1.3 Configure Sales Pipeline
1. Go to Settings -> Objects -> Deals -> Pipelines
2. Edit default pipeline or create new "Partner Acquisition" pipeline
3. Create stages (in order):
   - New Lead (0%)
   - Emailed (10%)
   - Follow-up (20%)
   - Interested (40%)
   - Invite Sent (60%)
   - Partner Created (80%)
   - Active (100% - Closed Won)
   - Lost (0% - Closed Lost)
4. Note the pipeline ID (visible in URL or API)

### 1.4 Custom Properties (Created by Setup Command)
The `hubspot:setup` command creates these automatically, but you can verify in:
Settings -> Properties -> Contact properties / Deal properties

**Contact properties:**
- `facturino_lead_id`
- `facturino_source`
- `facturino_source_url`
- `facturino_tags`
- `facturino_last_email_template`

**Deal properties:**
- `facturino_lead_id`
- `facturino_partner_id`

---

## 2. Environment Variables Checklist

| Variable | Description | Example |
|----------|-------------|---------|
| `HUBSPOT_PRIVATE_APP_TOKEN` | Private app access token | `pat-eu1-xxx` |
| `HUBSPOT_PIPELINE_ID` | Deal pipeline ID | `12345678` |
| `HUBSPOT_STAGE_NEW_LEAD` | Stage ID for new leads | `appointmentscheduled` |
| `HUBSPOT_STAGE_EMAILED` | Stage ID for emailed leads | `qualifiedtobuy` |
| `HUBSPOT_STAGE_FOLLOWUP_DUE` | Stage ID for follow-up due | `presentationscheduled` |
| `HUBSPOT_STAGE_INTERESTED` | Stage ID for interested leads | `decisionmakerboughtin` |
| `HUBSPOT_STAGE_INVITE_SENT` | Stage ID for invite sent | `contractsent` |
| `HUBSPOT_STAGE_PARTNER_ACTIVE` | Stage ID for active partners | `closedwon` |
| `HUBSPOT_STAGE_LOST` | Stage ID for lost leads | `closedlost` |
| `OUTREACH_DAILY_LIMIT` | Max emails per day | `100` |
| `OUTREACH_HOURLY_LIMIT` | Max emails per hour | `20` |
| `POSTMARK_STREAM_OUTREACH` | Postmark stream for outreach | `outreach` |
| `POSTMARK_STREAM_TRANSACTIONAL` | Postmark stream for transactional | `transactional` |

---

## 3. Facturino Configuration

Add to `.env`:

```env
#---------------------------------------------------------------------------
# HubSpot CRM Integration
#---------------------------------------------------------------------------
# Get token from: Settings → Integrations → Private Apps (Legacy Apps)
# Token format: pat-eu1-xxx (EU) or pat-na1-xxx (US)
HUBSPOT_PRIVATE_APP_TOKEN=

# Pipeline and stage IDs (run `php artisan hubspot:setup` to get these)
HUBSPOT_PIPELINE_ID=
HUBSPOT_STAGE_NEW_LEAD=
HUBSPOT_STAGE_EMAILED=
HUBSPOT_STAGE_FOLLOWUP_DUE=
HUBSPOT_STAGE_INTERESTED=
HUBSPOT_STAGE_INVITE_SENT=
HUBSPOT_STAGE_PARTNER_ACTIVE=
HUBSPOT_STAGE_LOST=

# Outreach throttling
OUTREACH_DAILY_LIMIT=100
OUTREACH_HOURLY_LIMIT=20

#---------------------------------------------------------------------------
# Postmark Email
#---------------------------------------------------------------------------
POSTMARK_STREAM_OUTREACH=outreach
POSTMARK_STREAM_TRANSACTIONAL=transactional
```

---

## 4. Initial Setup

```bash
# Test connection and create custom properties
php artisan hubspot:setup

# Expected output:
# [OK] Connected to HubSpot
# [OK] Created contact property: facturino_lead_id
# [OK] Created contact property: facturino_source
# ...
# [OK] Pipeline "default" has 8 stages
```

---

## 5. Import Flow

### 5.1 Prepare CSV

```csv
company_name,email,phone,city,website,source,source_url,tags
Kompanija DOOEL,info@kompanija.mk,02123456,Skopje,kompanija.mk,linkedin,https://linkedin.com/company/kompanija,retail;invoicing
```

### 5.2 Import

```bash
# Dry run first
php artisan hubspot:import-leads --csv=leads.csv --dry-run

# Actually import
php artisan hubspot:import-leads --csv=leads.csv
```

This will:
1. Create local OutreachLead record
2. Create/update HubSpot Contact
3. Create/update HubSpot Company (from website domain)
4. Create HubSpot Deal in pipeline
5. Associate Contact -> Company -> Deal

---

## 6. Outreach Campaign

```bash
# Send batch (respects throttle limits)
php artisan outreach:send-batch --limit=20

# Check quota
php artisan outreach:send-batch --dry-run
```

Each email sent:
1. Goes via Postmark
2. Logged as Email engagement in HubSpot contact timeline
3. Deal stage updated to "Emailed"

---

## 7. Creating Partners

### Option A: Polling (Automatic)
Run daily via cron:
```bash
php artisan hubspot:poll-deals
```
This checks for deals in "Interested" stage and auto-creates partners.

### Option B: Button Link (Manual)
1. In HubSpot, move deal to "Interested" stage
2. Generate partner link:
   ```bash
   php artisan hubspot:generate-partner-links
   ```
3. Click the link or add it to deal notes
4. Partner is created, invite sent, deal updated

---

## 8. Lead Flow Diagram

```
CSV Import
    |
    v
[HubSpot: Contact + Company + Deal created]
[Local: OutreachLead created]
    |
    v
Deal Stage: "New Lead"
    |
    v
php artisan outreach:send-batch
    |
    v
[Postmark: Email sent]
[HubSpot: Email engagement logged]
Deal Stage: "Emailed"
    |
    v
(Day 3, no response)
    |
    v
Deal Stage: "Follow-up"
[Postmark: Follow-up #1 sent]
    |
    v
(Recipient opens/clicks)
    |
    v
[HubSpot: Note logged, Task created]
Deal Stage: "Interested"
    |
    v
(Wife reviews in HubSpot kanban)
    |
    v
php artisan hubspot:poll-deals
  OR
Click partner creation link
    |
    v
[Facturino: Partner created]
[Postmark: Invite sent]
Deal Stage: "Invite Sent"
    |
    v
(Partner activates account)
    |
    v
Deal Stage: "Partner Created" -> "Active"
```

---

## 9. Postmark Webhooks

Configure in Postmark (Settings -> Webhooks):
- URL: `https://app.facturino.mk/webhooks/postmark`
- Events: Delivery, Open, Click, Bounce, SpamComplaint

---

## 10. Scheduled Tasks

Add to Laravel scheduler (`app/Console/Kernel.php`):

```php
// Send outreach every 15 minutes during business hours
$schedule->command('outreach:send-batch --limit=10')
    ->everyFifteenMinutes()
    ->between('08:00', '18:00')
    ->weekdays();

// Poll HubSpot for interested deals daily
$schedule->command('hubspot:poll-deals')
    ->dailyAt('09:00');
```

---

## 11. Troubleshooting

### Common Issues

#### 11.1 "401 Unauthorized" Error
- **Cause**: Invalid or expired access token
- **Solution**: Generate a new access token in HubSpot Settings → Integrations → Private Apps (Legacy Apps)

#### 11.2 "Property does not exist" Error
- **Cause**: Custom properties not created
- **Solution**: Run `php artisan hubspot:setup` to create required properties

#### 11.3 Rate Limiting (429 Error)
- **Cause**: Too many API requests
- **Solution**: HubSpot free tier allows 100 requests per 10 seconds. Reduce batch size.

#### 11.4 Deals Not Moving Stages
- **Cause**: Pipeline ID mismatch
- **Solution**: Verify `HUBSPOT_PIPELINE_ID` matches your pipeline. Find ID via:
  ```bash
  php artisan hubspot:list-pipelines
  ```

#### 11.5 Contacts Not Syncing
- **Cause**: Missing required scopes
- **Solution**: Edit your app in Settings → Integrations → Private Apps, add missing scopes, regenerate token

#### 11.6 Email Engagement Not Logging
- **Cause**: Missing `timeline` scope
- **Solution**: Add `timeline` scope to your app and regenerate token

#### 11.7 Company Association Failing
- **Cause**: No valid domain extracted from website
- **Solution**: Ensure website field has valid domain (e.g., `example.mk`)

#### 11.8 "Private Apps" Option Not Visible
- **Cause**: User role doesn't have permissions, or HubSpot UI changed
- **Solution**:
  - Ensure you have Super Admin role
  - Try direct URL: `https://app-eu1.hubspot.com/private-apps/{ACCOUNT_ID}`
  - Check Settings → Integrations → Legacy Apps (HubSpot renamed Private Apps)

### Debug Commands

```bash
# Test HubSpot connection
php artisan hubspot:test-connection

# List all pipelines and stages
php artisan hubspot:list-pipelines

# Check a specific contact
php artisan hubspot:debug-contact --email=test@example.mk

# View sync status
php artisan hubspot:sync-status
```

### Logs

Check Laravel logs for detailed error information:
```bash
tail -f storage/logs/laravel.log | grep -i hubspot
```

---

## 12. HubSpot Free Tier Limitations

Be aware of these limits on the free tier:
- **Contacts**: Unlimited (with limitations on marketing contacts)
- **API calls**: 100 requests per 10 seconds
- **Private apps**: 1 per account
- **Deal pipelines**: 1
- **Custom properties**: 10 per object type
- **Email templates**: Limited

For higher limits, consider HubSpot Starter ($15/month).

---

## 13. Security Best Practices

1. **Never commit tokens**: Keep `HUBSPOT_ACCESS_TOKEN` in `.env` only
2. **Rotate tokens**: Regenerate access tokens periodically
3. **Minimum scopes**: Only request scopes actually needed
4. **Webhook verification**: Validate Postmark webhook signatures
5. **Audit logs**: Enable HubSpot audit logging for compliance

---

## 14. Quick Reference

| Task | Command |
|------|---------|
| Setup/verify connection | `php artisan hubspot:setup` |
| Import leads from CSV | `php artisan hubspot:import-leads --csv=file.csv` |
| Send outreach batch | `php artisan outreach:send-batch --limit=20` |
| Poll for interested deals | `php artisan hubspot:poll-deals` |
| Generate partner links | `php artisan hubspot:generate-partner-links` |
| Test connection | `php artisan hubspot:test-connection` |
| List pipelines | `php artisan hubspot:list-pipelines` |

---

## 15. Manual QA Checklist

Use this checklist to verify the HubSpot integration is working correctly.

### Import Flow
- [ ] `hubspot:import-leads --csv=test.csv` creates companies/contacts/deals
- [ ] Associations are created (contact<->company, deal<->company, deal<->contact)
- [ ] Local OutreachLead and HubSpotMapping records created
- [ ] Suppressed emails are skipped
- [ ] Duplicate emails are handled (update, not create new)

### Outreach Flow
- [ ] `outreach:send-batch` sends emails to eligible leads
- [ ] Daily/hourly limits are respected
- [ ] HubSpot deal stage updated to "emailed"
- [ ] HubSpot deal properties updated (fct_last_touch_date, fct_next_followup_date)
- [ ] Note logged to HubSpot contact timeline

### Webhook Flow
- [ ] Open event updates deal to "followup_due"
- [ ] Click event updates deal to "followup_due"
- [ ] Bounce event adds to suppression and moves deal to "lost"
- [ ] Spam complaint adds to suppression and moves deal to "lost"
- [ ] Unsubscribe adds to suppression and moves deal to "lost"
- [ ] Events are idempotent (duplicate events ignored)

### Partner Creation Flow
- [ ] Moving deal to "interested" triggers partner creation
- [ ] Partner account created in Facturino
- [ ] Invite email sent via Postmark transactional
- [ ] HubSpot deal updated with fct_partner_id
- [ ] HubSpot deal moved to "invite_sent"
- [ ] Note logged to HubSpot contact

---

## 16. Support & References

For issues with:
- **HubSpot API**: Check [HubSpot Developer Docs](https://developers.hubspot.com/docs/api/overview)
- **HubSpot Legacy Apps**: Check [Legacy Private Apps Guide](https://developers.hubspot.com/docs/apps/legacy-apps/private-apps/overview)
- **HubSpot 2025.2 Apps**: Check [Creating Apps with Projects](https://developers.hubspot.com/docs/platform/create-private-apps-with-projects)
- **Postmark**: Check [Postmark Developer Docs](https://postmarkapp.com/developer)
- **Facturino Integration**: Contact support@facturino.mk

---

*Last updated: December 2025*
