# UptimeRobot Setup Guide

UptimeRobot provides external uptime monitoring and alerts when your application is unreachable from the internet.

## Why UptimeRobot?

While Grafana monitors internal application metrics, UptimeRobot provides:

- External monitoring (tests from outside your infrastructure)
- Simple uptime/downtime tracking
- Status page for customers
- Email/SMS alerts
- Free tier available (50 monitors)

## Step 1: Create UptimeRobot Account

1. Go to [https://uptimerobot.com/signup](https://uptimerobot.com/signup)
2. Sign up for a free account
3. Verify your email address
4. Log in to dashboard

## Step 2: Add Monitors

### Monitor 1: Main Application Health Check

**Monitor Type**: HTTP(s)

**Friendly Name**: Facturino - Health Check

**URL**: `https://your-app.railway.app/health`

**Monitoring Interval**: 5 minutes (Free tier)

**Monitor Timeout**: 30 seconds

**HTTP Method**: GET

**Expected Status Code**: 200

**Keyword Check**: (Optional)
- Look for: `"status":"healthy"`
- Alert if NOT found

**Configuration Steps**:
1. Click **Add New Monitor**
2. Select **HTTP(s)**
3. Enter details above
4. Click **Create Monitor**

### Monitor 2: Application Ping/Readiness

**Monitor Type**: HTTP(s)

**Friendly Name**: Facturino - Ping

**URL**: `https://your-app.railway.app/ping`

**Monitoring Interval**: 5 minutes

**Monitor Timeout**: 10 seconds

**Expected Status Code**: 200

**Purpose**: Quick connectivity check without database overhead

### Monitor 3: Metrics Endpoint

**Monitor Type**: HTTP(s)

**Friendly Name**: Facturino - Metrics Endpoint

**URL**: `https://your-app.railway.app/metrics`

**Monitoring Interval**: 5 minutes

**Monitor Timeout**: 30 seconds

**Expected Status Code**: 200

**Keyword Check**:
- Look for: `invoiceshelf_database_healthy`
- Alert if NOT found

**Purpose**: Ensure Prometheus metrics are being exposed

### Monitor 4: Customer Portal (Optional)

**Monitor Type**: HTTP(s)

**Friendly Name**: Facturino - Customer Portal

**URL**: `https://your-app.railway.app/[company-slug]/customer/portal`

**Monitoring Interval**: 10 minutes

**Expected Status Code**: 200

**Purpose**: Monitor customer-facing portal availability

## Step 3: Configure Alert Contacts

1. Go to **My Settings** > **Alert Contacts**
2. Click **Add Alert Contact**

### Email Alerts (Default)

- **Type**: Email
- **Email**: ops@facturino.mk
- **Friendly Name**: Operations Team
- Enable: ✓ Send alerts
- Enable: ✓ Send up alerts (recovery notifications)

### SMS Alerts (Recommended for Critical)

- **Type**: SMS
- **Phone**: +389 XX XXX XXX
- **Friendly Name**: On-Call Phone
- Enable: ✓ Send alerts
- Enable: ✓ Send up alerts

### Slack Integration

1. Create Slack webhook in your workspace:
   - Go to Slack App Directory
   - Search for "Incoming Webhooks"
   - Add to channel (e.g., #alerts)
   - Copy webhook URL

2. In UptimeRobot:
   - **Type**: Webhook
   - **URL**: Your Slack webhook URL
   - **Method**: POST
   - **Body**:
     ```json
     {
       "text": "*monitorFriendlyName* is *alertType*!\nReason: *alertDetails*\nTime: *alertDateTime*"
     }
     ```

### PagerDuty Integration

1. In PagerDuty, create integration:
   - Go to Services > Add Integration
   - Select "Events API v2"
   - Copy Integration Key

2. In UptimeRobot:
   - **Type**: Webhook
   - **URL**: `https://events.pagerduty.com/v2/enqueue`
   - **Method**: POST
   - **Headers**: `Content-Type: application/json`
   - **Body**:
     ```json
     {
       "routing_key": "YOUR_PAGERDUTY_KEY",
       "event_action": "trigger",
       "payload": {
         "summary": "*monitorFriendlyName* is *alertType*",
         "severity": "critical",
         "source": "UptimeRobot"
       }
     }
     ```

## Step 4: Configure Monitoring Settings

### Alert Timing

Go to **My Settings** > **Advanced Settings**:

- **Send alerts after**: 1 consecutive down (immediate)
- **Down monitors**: Check every 1 minute (during downtime)
- **Up monitors**: Resume normal interval after recovery

### Maintenance Windows

Schedule maintenance windows to prevent false alerts:

1. Go to specific monitor
2. Click **Edit**
3. Scroll to **Maintenance Windows**
4. Add schedule:
   - **Type**: Weekly
   - **Day**: Sunday
   - **Time**: 02:00 - 04:00 (GMT)
   - **Timezone**: Europe/Belgrade

## Step 5: Create Public Status Page

Create a status page for transparency:

1. Go to **Status Pages** > **Add Status Page**
2. Configure:
   - **Friendly Name**: Facturino Status
   - **Custom Domain**: status.facturino.mk (requires DNS setup)
   - **Select Monitors**: Add all monitors
   - **Design**: Choose theme
   - **Custom Logo**: Upload Facturino logo

3. DNS Configuration (for custom domain):
   ```dns
   status.facturino.mk CNAME stats.uptimerobot.com
   ```

4. Share status page:
   - Add link in app footer
   - Include in support emails
   - Reference in SLA documentation

## Step 6: Monitor Configuration Best Practices

### Health Check vs Ping

- Use `/ping` for quick availability checks (no DB)
- Use `/health` for comprehensive health status
- Monitor both separately

### SSL Certificate Monitoring

UptimeRobot automatically checks SSL certificates:

1. Edit monitor
2. Enable **SSL monitoring**
3. Set alert: Warn 7 days before expiry

### Response Time Tracking

UptimeRobot tracks response times automatically:

- View in monitor dashboard
- Alert if response time > 5 seconds (optional)

### Geographic Monitoring

Free tier monitors from random locations. For better coverage:

- Upgrade to Pro ($7/month)
- Select specific monitoring locations:
  - Europe (for Macedonian users)
  - Global (for international access)

## Step 7: Integrate with Incident Management

### Create Incident Workflow

When UptimeRobot detects downtime:

1. **Immediate**: Send SMS to on-call engineer
2. **1 minute**: Send email to operations team
3. **5 minutes**: Create PagerDuty incident
4. **10 minutes**: Post in #incidents Slack channel
5. **30 minutes**: Escalate to engineering lead

### Auto-Recovery Verification

After site comes back up:

1. UptimeRobot sends recovery notification
2. Manually verify:
   - Check `/health` endpoint
   - Review error logs
   - Verify database connectivity
   - Test critical user flows

3. Document incident:
   - Root cause
   - Duration
   - Resolution steps
   - Prevention measures

## Monitoring Checklist

After setup, verify:

- [ ] All monitors are "Up" (green)
- [ ] Alert contacts are configured
- [ ] Test alerts received successfully
- [ ] Status page is accessible
- [ ] SSL monitoring enabled
- [ ] Maintenance windows scheduled
- [ ] Integration with PagerDuty/Slack working

## Testing Alerts

### Test 1: Manual Downtime

1. Temporarily take site down:
   ```bash
   railway run php artisan down
   ```

2. Wait 5 minutes
3. Verify alert received
4. Bring site back up:
   ```bash
   railway run php artisan up
   ```

5. Verify recovery alert

### Test 2: SSL Certificate Check

1. Check SSL expiry in UptimeRobot
2. Verify it matches actual certificate
3. Test alert by setting warning threshold to current date + 10 days

## Monitoring URLs Summary

| Endpoint | Purpose | Interval | Alert Threshold |
|----------|---------|----------|-----------------|
| `/health` | Comprehensive health | 5 min | Immediate |
| `/ping` | Quick availability | 5 min | Immediate |
| `/metrics` | Prometheus endpoint | 5 min | After 2 failures |
| `/ready` | K8s readiness | 5 min | Immediate |
| Customer Portal | User access | 10 min | After 2 failures |

## Advanced Configuration

### Custom HTTP Headers

For protected endpoints, add authentication:

1. Edit monitor
2. Add **Custom HTTP Header**:
   ```
   Authorization: Bearer YOUR_MONITORING_TOKEN
   ```

### POST Request Monitoring

To monitor webhooks or API endpoints:

1. Select **HTTP(s)** monitor
2. **HTTP Method**: POST
3. **POST Value**: JSON payload
4. **Expected Status**: 200 or 201

### Keyword Monitoring

Alert on specific content changes:

1. **Keyword Type**: Exists
2. **Keyword**: `"status":"healthy"`
3. **Alert if**: Keyword does NOT exist

## Free vs Pro Comparison

| Feature | Free | Pro ($7/mo) |
|---------|------|-------------|
| Monitors | 50 | 50 |
| Interval | 5 minutes | 1 minute |
| Locations | Random | Selectable |
| SMS alerts | No | Yes |
| Status pages | 1 | Unlimited |
| Advanced HTTP | No | Yes |
| Multi-user | No | Yes |

**Recommendation**: Start with Free tier, upgrade if you need:
- 1-minute checks
- SMS alerts
- Multiple team members
- Specific geographic monitoring

## Troubleshooting

### Monitor shows "Down" but site is accessible

**Possible causes**:
1. UptimeRobot IP blocked by firewall
2. Rate limiting triggered
3. Keyword check failing
4. Response timeout too short

**Solution**:
- Whitelist UptimeRobot IPs: [https://uptimerobot.com/help/locations/](https://uptimerobot.com/help/locations/)
- Increase timeout to 30 seconds
- Verify keyword in response

### No alerts received

**Check**:
1. Alert contacts are verified
2. Monitor has alert contacts assigned
3. Email not in spam folder
4. Alert threshold configured correctly

### False positives

**Reduce false alerts**:
1. Increase "Send alerts after" to 2-3 consecutive downs
2. Increase timeout
3. Use `/ping` instead of `/health` for faster response

## Next Steps

- [Complete Monitoring Checklist](./05-monitoring-checklist.md)
- Test all alerts end-to-end
- Document incident response procedures

## Reference

- [UptimeRobot Documentation](https://uptimerobot.com/help/)
- [UptimeRobot API](https://uptimerobot.com/api/)
- [Webhook Integrations](https://uptimerobot.com/help/webhook-alert-contacts/)
