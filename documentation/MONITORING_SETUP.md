# External Monitoring Setup for Facturino

This guide explains how to set up external monitoring for your Facturino installation using free and paid monitoring services.

## Health Check Endpoints

Facturino provides several health check endpoints:

- **`/health`** - Comprehensive health check with all system components
- **`/api/ping`** - Simple ping endpoint (lightweight, no database check)
- **`/ping`** - Alternative ping endpoint for quick availability checks
- **`/ready`** - Readiness check for container orchestration
- **`/metrics`** - Prometheus-compatible metrics endpoint (requires feature flag)

## Recommended Services

### Option 1: UptimeRobot (Free Tier)

UptimeRobot offers a generous free tier with 50 monitors and 5-minute check intervals.

**Setup Instructions:**

1. Sign up at [https://uptimerobot.com](https://uptimerobot.com)

2. Create your first monitor:
   - Click "Add New Monitor"
   - Monitor Type: **HTTP(s)**
   - Friendly Name: `Facturino Health Check`
   - URL: `https://app.facturino.mk/health`
   - Monitoring Interval: **5 minutes**
   - Monitor Timeout: 30 seconds
   - Click "Create Monitor"

3. Create a second monitor for the ping endpoint:
   - Monitor Type: **HTTP(s)**
   - Friendly Name: `Facturino Ping`
   - URL: `https://app.facturino.mk/api/ping`
   - Monitoring Interval: **1 minute** (or 5 minutes on free tier)
   - Click "Create Monitor"

4. Configure Alert Contacts:
   - Go to "My Settings" → "Alert Contacts"
   - Add your email address
   - Add SMS alerts (optional)
   - Enable push notifications via mobile app

5. Set up Status Page (optional):
   - Go to "Status Pages"
   - Create a public status page for your customers
   - Add your monitors to the status page

### Option 2: Better Uptime (Alternative)

Better Uptime offers a modern interface with incident management features.

**Setup Instructions:**

1. Sign up at [https://betteruptime.com](https://betteruptime.com)

2. Create a new monitor:
   - Type: **HTTP**
   - URL: `https://app.facturino.mk/health`
   - Check frequency: **1 minute** (paid) or **3 minutes** (free)
   - Request timeout: 30 seconds
   - Expected status code: **200**
   - Click "Create Monitor"

3. Set up incident notification:
   - Configure on-call schedules
   - Add email, SMS, Slack, or phone call alerts
   - Set escalation policies

4. Create a status page:
   - Go to Status Pages
   - Create a new page
   - Add your monitor
   - Customize branding

### Option 3: Pingdom (Paid)

Pingdom by SolarWinds offers advanced monitoring with detailed analytics.

**Setup Instructions:**

1. Sign up at [https://www.pingdom.com](https://www.pingdom.com)

2. Add new Uptime Check:
   - Name: `Facturino Health`
   - URL: `https://app.facturino.mk/health`
   - Check interval: **1 minute**
   - Check locations: Select multiple regions
   - Alert when down for: **2 minutes** (after 2 consecutive failures)

3. Configure alerting:
   - Add email contacts
   - Configure SMS alerts
   - Set up integrations (Slack, PagerDuty, etc.)

### Option 4: Grafana Cloud (Advanced)

For advanced monitoring with Prometheus metrics.

**Setup Instructions:**

1. Sign up at [https://grafana.com/products/cloud/](https://grafana.com/products/cloud/)

2. Configure Prometheus scraping:
   ```yaml
   scrape_configs:
     - job_name: 'facturino'
       scrape_interval: 60s
       static_configs:
         - targets: ['app.facturino.mk']
       metrics_path: '/metrics'
   ```

3. Import Facturino dashboard (if available)

4. Set up alerts based on metrics

## Alert Rules Configuration

Configure your monitoring service to alert on:

### Critical Alerts (Immediate Action Required)

- **Site Down**: Service is completely unreachable
  - Threshold: 2 consecutive failed checks
  - Notification: SMS + Email + Phone call

- **Health Check Failed (503 status)**: System components are degraded
  - Threshold: 3 consecutive failures
  - Notification: Email + Slack

- **SSL Certificate Expiring**: Certificate expires in < 7 days
  - Threshold: Check daily
  - Notification: Email

### Warning Alerts (Action Required Soon)

- **High Response Time**: Response time > 2 seconds
  - Threshold: 5 consecutive checks
  - Notification: Email

- **Backup Old**: Last backup > 48 hours old
  - Threshold: Check via health endpoint
  - Notification: Email

- **Certificate Expiring Soon**: QES certificates expire in < 30 days
  - Threshold: Daily check
  - Notification: Email

## Email Recipients

Configure the following email addresses to receive alerts:

- **Primary**: `support@facturino.mk` (for urgent production issues)
- **Secondary**: `admin@facturino.mk` (for non-urgent warnings)
- **DevOps**: Your DevOps team email

## Testing Your Setup

After configuring monitoring, test that alerts are working:

1. **Simulate Downtime**: Temporarily stop your application server
   - You should receive an alert within 1-5 minutes

2. **Test Health Degradation**: Temporarily break a component (e.g., stop Redis)
   - The `/health` endpoint should return 503
   - You should receive a degraded alert

3. **Verify Alert Delivery**: Check that you receive alerts via all configured channels

## Health Check Response Format

The `/health` endpoint returns JSON with the following structure:

```json
{
  "status": "healthy",
  "timestamp": "2025-11-14T12:00:00+00:00",
  "version": "1.0.0",
  "environment": "production",
  "checks": {
    "database": true,
    "redis": true,
    "queues": true,
    "signer": true,
    "bank_sync": true,
    "storage": true,
    "backup": true,
    "certificates": true
  }
}
```

**Status values:**
- `healthy` (200): All checks passed
- `degraded` (503): One or more checks failed

## Monitoring Dashboard

You can create a simple monitoring dashboard using your monitoring service's features:

1. **Uptime Percentage**: Track 99.9% uptime SLA
2. **Response Time**: Graph average response time over time
3. **Incident History**: View all past incidents
4. **Check Distribution**: See which checks are failing most often

## Internal Scheduled Monitoring

Facturino also includes internal scheduled monitoring tasks:

- **Certificate Expiry Check**: Runs daily at 8:00 AM
  - Sends email to company owners for certificates expiring within 30 days

- **Health Check Self-Test**: Runs every hour
  - Logs errors if health check fails

See the Console Kernel configuration for more details.

## Troubleshooting

### Health Check Returns 503

Check the Laravel logs for specific component failures:

```bash
tail -f storage/logs/laravel.log
```

Common issues:
- Database connection lost
- Redis server down
- Disk space full
- Queue stuck

### False Positives

If you're getting false positive alerts:

1. Increase the consecutive failure threshold
2. Adjust timeout settings (increase from 30s to 60s)
3. Check if monitoring location has connectivity issues
4. Verify monitoring service IP isn't blocked by firewall

### Missing Alerts

If alerts aren't being delivered:

1. Verify email addresses are correct
2. Check spam folder
3. Verify monitoring service account is active
4. Test alert contacts manually

## Best Practices

1. **Use Multiple Monitors**: Set up both `/health` and `/ping` endpoints
2. **Geographic Distribution**: Monitor from multiple regions
3. **Alert Escalation**: Configure escalation policies for critical alerts
4. **Regular Testing**: Test your monitoring setup quarterly
5. **Document Runbooks**: Create runbooks for common alert scenarios
6. **On-Call Rotation**: Set up on-call schedules for 24/7 coverage

## Additional Resources

- [UptimeRobot Documentation](https://uptimerobot.com/help/)
- [Better Uptime Guides](https://docs.betteruptime.com/)
- [Grafana Cloud Documentation](https://grafana.com/docs/grafana-cloud/)
- [Prometheus Monitoring Best Practices](https://prometheus.io/docs/practices/)

## Support

For questions about Facturino monitoring setup:
- Email: support@facturino.mk
- Documentation: https://docs.facturino.mk
- Status Page: https://status.facturino.mk (if configured)
