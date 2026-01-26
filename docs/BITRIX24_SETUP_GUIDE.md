# Bitrix24 Setup Guide for Facturino CRM Integration

This guide walks you through setting up the Bitrix24 CRM integration with Facturino for automated lead management and partner onboarding.

---

## Table of Contents

1. [Bitrix24 Prerequisites (Manual Steps)](#1-bitrix24-prerequisites-manual-steps)
2. [Facturino Configuration](#2-facturino-configuration)
3. [Initial Setup](#3-initial-setup)
4. [Importing Leads](#4-importing-leads)
5. [Running Outreach Campaigns](#5-running-outreach-campaigns)
6. [Lead Flow Diagram](#6-lead-flow-diagram)
7. [Webhook Events](#7-webhook-events)
8. [Creating Partners from Bitrix](#8-creating-partners-from-bitrix)
9. [Troubleshooting](#9-troubleshooting)
10. [API Reference](#10-api-reference)

---

## 1. Bitrix24 Prerequisites (Manual Steps)

Before connecting Facturino, you must complete the following steps manually in your Bitrix24 account.

### 1.1 Create Bitrix24 Account

- [ ] Sign up at [bitrix24.com](https://www.bitrix24.com/)
- [ ] Enable the CRM module in your workspace
- [ ] Note your Bitrix24 domain (e.g., `yourcompany.bitrix24.com`)

### 1.2 Create Inbound Webhook

This webhook allows Facturino to push data TO Bitrix24.

1. Log into your Bitrix24 account
2. Navigate to: **Applications** > **Developer resources** > **Other** > **Inbound webhook**
3. Click **Add inbound webhook**
4. Configure permissions (check the boxes for):
   - **CRM** - leads, statuses, custom fields, timeline
   - **Tasks** - create and manage tasks
   - **Users** - basic user information
5. Save the webhook
6. Copy the webhook URL (it looks like: `https://yourcompany.bitrix24.com/rest/1/abc123xyz/`)
7. This URL is your `BITRIX24_WEBHOOK_BASE_URL`

**Important:** The webhook URL contains a secret token. Keep it confidential.

### 1.3 Create Outbound Webhook (Event Handler)

This webhook allows Bitrix24 to notify Facturino when leads are updated.

1. Navigate to: **Applications** > **Developer resources** > **Other** > **Outbound webhook**
2. Click **Add outbound webhook**
3. Configure:
   - **Handler URL:** `https://app.facturino.mk/api/bitrix/events`
   - **Event type:** Select the following events:
     - `ONCRMLEADUPDATE` - triggered when a lead is updated
     - `ONCRMLEADADD` - triggered when a new lead is created (optional)
4. Note the authentication token if provided (you will use this as `BITRIX_SHARED_SECRET`)
5. Save the webhook

### 1.4 Configure Postmark Email Service

If you have not already set up Postmark for email delivery:

1. Create a [Postmark](https://postmarkapp.com/) account
2. Verify your sending domain
3. Create two message streams:
   - **outreach** - for cold outreach emails (promotional)
   - **transactional** - for partner invitations and system emails
4. Get your Server API Token from Postmark
5. Add webhook URL for email events: `https://app.facturino.mk/webhooks/postmark`
   - Enable events: Delivery, Open, Click, Bounce, Spam Complaint

---

## 2. Facturino Configuration

Add the following environment variables to your Facturino installation.

### Required Environment Variables

```env
# Bitrix24 Integration
BITRIX24_WEBHOOK_BASE_URL=https://yourcompany.bitrix24.com/rest/1/abc123xyz/
BITRIX_SHARED_SECRET=generate-a-random-string-here

# Outreach Rate Limits
OUTREACH_DAILY_LIMIT=100
OUTREACH_HOURLY_LIMIT=20

# Postmark Email Streams
POSTMARK_STREAM_OUTREACH=outreach
POSTMARK_STREAM_TRANSACTIONAL=transactional
```

### Configuration Values Explained

| Variable | Description | Example |
|----------|-------------|---------|
| `BITRIX24_WEBHOOK_BASE_URL` | The inbound webhook URL from Bitrix24 | `https://xxx.bitrix24.com/rest/1/xxx/` |
| `BITRIX_SHARED_SECRET` | Shared secret for verifying Bitrix webhooks | Random 32+ character string |
| `OUTREACH_DAILY_LIMIT` | Maximum emails sent per day | `100` |
| `OUTREACH_HOURLY_LIMIT` | Maximum emails sent per hour | `20` |
| `POSTMARK_STREAM_OUTREACH` | Postmark stream for cold emails | `outreach` |
| `POSTMARK_STREAM_TRANSACTIONAL` | Postmark stream for system emails | `transactional` |

**Tip:** Generate a secure shared secret with:
```bash
openssl rand -hex 32
```

---

## 3. Initial Setup

After configuring the environment variables, run the setup command to create the required custom fields and lead stages in Bitrix24.

### Run the Setup Command

```bash
php artisan bitrix:setup
```

### What This Command Creates

**Custom Fields in Bitrix24:**

| Field | Type | Purpose |
|-------|------|---------|
| `UF_FCT_SOURCE` | String | Lead source (e.g., "csv_import", "website") |
| `UF_FCT_SOURCE_URL` | URL | Website or original source URL |
| `UF_FCT_CITY` | String | City/location of the lead |
| `UF_FCT_TAGS` | String | Comma-separated tags |
| `UF_FCT_FACTURINO_PARTNER_ID` | Integer | Linked Facturino partner ID |
| `UF_FCT_LAST_POSTMARK_MESSAGE_ID` | String | Last email message ID for tracking |

**Lead Stages:**

| Stage ID | Display Name | Description |
|----------|--------------|-------------|
| `NEW` | New | Fresh lead, not yet contacted |
| `UC_EMAILED` | Emailed | Initial outreach email sent |
| `UC_FOLLOWUP` | Follow-up | Needs follow-up (no response) |
| `UC_INTERESTED` | Interested | Lead showed interest (opened/clicked) |
| `UC_INVITE_SENT` | Invite Sent | Partner invitation sent |
| `UC_PARTNER_CREATED` | Partner Created | Partner account created |
| `UC_ACTIVE` | Active | Active partner |
| `JUNK` | Lost | Lead marked as lost |

### Command Options

```bash
# Test connection only (dry run)
php artisan bitrix:setup --test

# Force recreate all fields and statuses
php artisan bitrix:setup --force
```

---

## 4. Importing Leads

Import leads from a CSV file into Facturino and sync them to Bitrix24.

### Prepare Your CSV File

Your CSV file should have the following columns:

| Column | Required | Description |
|--------|----------|-------------|
| `company_name` | Yes | Company/business name |
| `email` | Yes | Contact email address |
| `phone` | No | Phone number |
| `city` | No | City/location |
| `website` | No | Company website |
| `source` | No | Lead source (e.g., "google_maps", "linkedin") |
| `source_url` | No | Original URL where lead was found |
| `tags` | No | Comma-separated tags |
| `contact_name` | No | Name of the contact person |

**Example CSV:**

```csv
company_name,email,phone,city,website,source,source_url,tags,contact_name
ACME Accounting,info@acme.mk,+389 70 123456,Skopje,https://acme.mk,google_maps,https://g.page/acme,accounting,John Doe
Best Books Ltd,office@bestbooks.mk,+389 72 654321,Bitola,https://bestbooks.mk,linkedin,,bookkeeping,Jane Smith
```

### Run the Import Command

```bash
php artisan bitrix:import-leads --csv=/path/to/leads.csv
```

### Import Options

```bash
# Preview without importing (dry run)
php artisan bitrix:import-leads --csv=/path/to/leads.csv --dry-run

# Import locally only (do not push to Bitrix24)
php artisan bitrix:import-leads --csv=/path/to/leads.csv --skip-bitrix
```

### Import Results

The command displays:
- Total rows processed
- New leads created
- Existing leads updated
- Skipped leads (duplicates)
- Suppressed emails (bounced/unsubscribed)
- Bitrix24 sync status

---

## 5. Running Outreach Campaigns

Send automated outreach emails to your leads.

### Send Initial Emails

```bash
php artisan outreach:send-batch --limit=20
```

### Send Follow-up Emails

```bash
# Send follow-up #1 (3 days after initial)
php artisan outreach:send-batch --template=followup_1 --limit=20

# Send follow-up #2 (7 days after initial)
php artisan outreach:send-batch --template=followup_2 --limit=20
```

### Command Options

```bash
# Preview what would be sent (dry run)
php artisan outreach:send-batch --dry-run

# Set custom batch size
php artisan outreach:send-batch --limit=50
```

### Follow-up Schedule

| Day | Action | Stage After |
|-----|--------|-------------|
| 0 | Initial email sent | NEW -> EMAILED |
| 3 | Follow-up #1 (if no response) | EMAILED -> FOLLOWUP |
| 7 | Follow-up #2 (if opened but no conversion) | FOLLOWUP (remains) |

### Rate Limits

Outreach emails respect the configured rate limits:
- **Daily limit:** Maximum emails per day (default: 100)
- **Hourly limit:** Maximum emails per hour (default: 20)

The system adds random delays (30-60 seconds) between sends to avoid triggering spam filters.

---

## 6. Lead Flow Diagram

```
                           +-----------+
                           |   START   |
                           +-----+-----+
                                 |
                                 v
                    +------------------------+
                    |     CSV Import or      |
                    |    Manual Lead Add     |
                    +------------+-----------+
                                 |
                                 v
                           +-----------+
                           |    NEW    |
                           +-----+-----+
                                 |
                        (send initial email)
                                 |
                                 v
                          +-----------+
                          |  EMAILED  |
                          +-----+-----+
                                 |
               +--------+--------+--------+
               |        |                 |
        (no response)  (open/click)   (bounce/spam)
               |        |                 |
               v        v                 v
        +----------+ +------------+  +---------+
        | FOLLOWUP | | INTERESTED |  |  LOST   |
        +-----+----+ +------+-----+  +---------+
              |             |
      (follow-up emails)    |
              |      (manual or auto trigger)
              v             |
        +----------+        v
        | FOLLOWUP |  +-------------+
        +-----+----+  | INVITE_SENT |
              |       +------+------+
        (conversion)         |
              |        (partner activates)
              v              |
   +------------------+      v
   | PARTNER_CREATED  | +--------+
   +--------+---------+ | ACTIVE |
            |           +--------+
            +-------------->
```

---

## 7. Webhook Events

Facturino automatically responds to the following events from Postmark:

### Email Opened

- Bitrix timeline note: "Email opened by {email}"
- On first open: Lead stage changes to INTERESTED
- Subsequent opens are tracked but do not change stage

### Email Link Clicked

- Bitrix timeline note: "Email link clicked by {email}: {url}"
- Bitrix task created: "Call today - Email click from {email}"
- Task priority: High
- Task deadline: End of current day

### Email Bounced

- Email added to suppression list (no future sends)
- Bitrix lead stage changes to LOST
- Bitrix timeline note: "Email bounced: {type} - {description}"

### Spam Complaint

- Email added to suppression list
- Bitrix lead stage changes to LOST
- Bitrix timeline note: "Spam complaint received - lead marked as lost"

### Unsubscribe

- Email added to suppression list
- Future sends automatically skipped

---

## 8. Creating Partners from Bitrix

There are two ways to create a Facturino partner account from a Bitrix lead:

### Option A: Automatic (via Stage Change)

1. Move the lead to the **INTERESTED** or **IN_PROCESS** stage in Bitrix24
2. Facturino receives the webhook and automatically:
   - Creates a user account
   - Creates a partner account
   - Sends a partner invitation email
   - Updates the Bitrix lead with the partner ID
   - Changes lead stage to INVITE_SENT
   - Adds a timeline note with partner details

### Option B: Manual API Call

Create a Bitrix24 business process or workflow that calls the Facturino API:

**Endpoint:** `POST https://app.facturino.mk/api/bitrix/create-partner`

**Request Body:**

```json
{
  "bitrix_lead_id": 123,
  "email": "partner@example.com",
  "company_name": "Example Company Ltd",
  "name": "John Doe",
  "phone": "+389 70 123456",
  "city": "Skopje",
  "tax_id": "1234567890"
}
```

**Required Fields:**
- `bitrix_lead_id` - The Bitrix24 lead ID
- `email` - Partner email address
- `company_name` - Company name

**Optional Fields:**
- `name` - Contact person name
- `phone` - Phone number
- `city` - City/location
- `tax_id` - Tax identification number

**Response (Success):**

```json
{
  "status": "created",
  "partner_id": 456,
  "invite_sent_at": "2024-01-15T10:30:00Z"
}
```

**Response (Already Exists):**

```json
{
  "status": "exists",
  "partner_id": 456,
  "message": "Partner already exists"
}
```

### Bitrix24 Workflow Setup

To call this API from Bitrix:

1. Go to: **CRM** > **Settings** > **Automation rules** > **Leads**
2. Create a new rule triggered on stage change (e.g., when lead moves to INTERESTED)
3. Add action: **Webhook** (outgoing)
4. Configure:
   - URL: `https://app.facturino.mk/api/bitrix/create-partner`
   - Method: POST
   - Add header: `X-Bitrix-Secret: your-shared-secret`
   - Body: Include the required fields

---

## 9. Troubleshooting

### Connection Issues

**Problem:** `bitrix:setup` command fails with "Connection failed"

**Solutions:**
1. Verify your `BITRIX24_WEBHOOK_BASE_URL` is correct
2. Check that the URL ends with a forward slash
3. Ensure your Bitrix24 webhook has the required permissions
4. Check if there are any firewall rules blocking outbound connections

**Problem:** Bitrix webhook URL looks wrong

**Correct format:** `https://yourcompany.bitrix24.com/rest/1/abc123xyz/`
- Must include the full path with user ID and secret token
- Must end with a forward slash

### Email Delivery Issues

**Problem:** Emails are not being sent

**Check:**
1. Rate limits not exceeded: Check quota with `outreach:send-batch --dry-run`
2. Leads are in correct status (NEW for initial, EMAILED/FOLLOWUP for follow-ups)
3. Email is not in suppression list
4. Postmark API key is configured correctly

**Problem:** Emails going to spam

**Solutions:**
1. Ensure your domain has proper SPF, DKIM, and DMARC records
2. Verify your Postmark sending domain
3. Use the "outreach" stream for cold emails (separate from transactional)
4. Review email content for spam triggers

### Webhook Issues

**Problem:** Bitrix events not reaching Facturino

**Check:**
1. Outbound webhook URL is correct in Bitrix24
2. Event types are properly selected
3. Check Facturino logs: `storage/logs/laravel.log`
4. Verify the route is accessible: `https://app.facturino.mk/api/bitrix/events`

**Problem:** Postmark webhooks not working

**Check:**
1. Webhook URL in Postmark: `https://app.facturino.mk/webhooks/postmark`
2. Ensure the route is excluded from CSRF protection
3. Check for webhook failures in Postmark dashboard
4. Verify Facturino logs for incoming webhook data

### Partner Creation Issues

**Problem:** Partner not created when lead stage changes

**Check:**
1. Lead has a valid email address
2. Partner with same email does not already exist
3. Webhook is configured to send `ONCRMLEADUPDATE` event
4. Lead stage is exactly `INTERESTED` or `IN_PROCESS`

### Data Sync Issues

**Problem:** Custom fields not appearing in Bitrix

**Solution:**
Run the setup command with force flag:
```bash
php artisan bitrix:setup --force
```

**Problem:** Lead stages not matching

**Note:** Some stages like NEW and JUNK are built-in Bitrix statuses. Custom stages have the `UC_` prefix (e.g., `UC_EMAILED`, `UC_FOLLOWUP`).

---

## 10. API Reference

### Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/bitrix/events` | Receives Bitrix24 webhooks | Bitrix Auth |
| `POST` | `/api/bitrix/create-partner` | Creates partner from lead | Bitrix Auth |
| `POST` | `/webhooks/postmark` | Receives Postmark events | None (public) |
| `GET` | `/unsubscribe` | Shows unsubscribe page | None (public) |
| `POST` | `/unsubscribe` | Processes unsubscribe | None (public) |

### Artisan Commands

| Command | Description |
|---------|-------------|
| `php artisan bitrix:setup` | Set up Bitrix24 custom fields and stages |
| `php artisan bitrix:setup --test` | Test Bitrix24 connection |
| `php artisan bitrix:setup --force` | Force recreate all fields |
| `php artisan bitrix:import-leads --csv=file.csv` | Import leads from CSV |
| `php artisan bitrix:import-leads --csv=file.csv --dry-run` | Preview import |
| `php artisan bitrix:import-leads --csv=file.csv --skip-bitrix` | Import locally only |
| `php artisan outreach:send-batch` | Send outreach emails |
| `php artisan outreach:send-batch --template=followup_1` | Send follow-up emails |
| `php artisan outreach:send-batch --dry-run` | Preview sends |
| `php artisan outreach:send-batch --limit=50` | Set batch size |

### Database Tables

| Table | Purpose |
|-------|---------|
| `outreach_leads` | Local lead storage |
| `outreach_sends` | Email send history |
| `outreach_events` | Postmark webhook events |
| `outreach_suppressions` | Bounce/unsubscribe list |
| `bitrix_lead_maps` | Facturino-Bitrix lead mapping |

---

## Support

For additional help:
- Check Facturino logs: `storage/logs/laravel.log`
- Review Bitrix24 webhook logs in Developer resources
- Check Postmark Activity stream for email issues
- Contact support at support@facturino.mk
