# Monitoring Setup Checklist

Use this checklist to verify your Facturino monitoring infrastructure is properly configured.

## Pre-Setup Requirements

- [ ] Facturino application is deployed and accessible
- [ ] You have admin access to Railway (or your hosting platform)
- [ ] You can set environment variables
- [ ] You have email access for account creation

## Phase 1: Enable Monitoring Endpoints

### Application Configuration

- [ ] Set `FEATURE_MONITORING=true` in environment variables
- [ ] Restart application after setting feature flag
- [ ] Verify `/metrics` endpoint is accessible:
  ```bash
  curl https://your-app.railway.app/metrics
  # Should return Prometheus text format metrics
  ```
- [ ] Verify `/health` endpoint is accessible:
  ```bash
  curl https://your-app.railway.app/health
  # Should return JSON with health status
  ```
- [ ] Verify `/ping` endpoint is accessible:
  ```bash
  curl https://your-app.railway.app/ping
  # Should return {"status":"ok","timestamp":"..."}
  ```

### Expected Responses

**Metrics Endpoint** (`/metrics`):
```
# HELP invoiceshelf_database_healthy 1 if database is healthy, 0 otherwise
# TYPE invoiceshelf_database_healthy gauge
invoiceshelf_database_healthy 1
# HELP invoiceshelf_invoices_total Total number of invoices by status
# TYPE invoiceshelf_invoices_total gauge
invoiceshelf_invoices_total{status="DRAFT"} 5
...
```

**Health Endpoint** (`/health`):
```json
{
  "status": "healthy",
  "timestamp": "2025-11-17T10:30:00Z",
  "version": "1.3.0",
  "environment": "production",
  "checks": {
    "database": true,
    "redis": true,
    "queues": true,
    "signer": true,
    "bank_sync": true,
    "storage": true,
    "backup": true,
    "certificates": true,
    "paddle": true
  }
}
```

## Phase 2: Grafana Cloud Setup

### Account Creation

- [ ] Created Grafana Cloud account at [grafana.com](https://grafana.com)
- [ ] Selected appropriate region (Europe for Macedonia)
- [ ] Verified email address
- [ ] Logged into Grafana Cloud dashboard

### Data Source Configuration

- [ ] Navigated to **Connections** > **Data sources**
- [ ] Added Prometheus data source
- [ ] Configured Prometheus endpoint (if using self-hosted)
  - OR -
- [ ] Installed Grafana Agent for metric collection
- [ ] Tested data source connectivity (green checkmark)

### Grafana Agent Setup (Recommended)

- [ ] Created `grafana-agent.yaml` configuration file
- [ ] Added Grafana Cloud remote_write credentials:
  - [ ] Prometheus username (from Grafana Cloud)
  - [ ] API key (generated in Grafana Cloud)
  - [ ] Remote write URL
- [ ] Configured scrape targets for `/metrics` endpoint
- [ ] Deployed Grafana Agent (Railway service or sidecar)
- [ ] Verified agent is running:
  ```bash
  railway logs --service grafana-agent
  ```

### Verify Metrics Are Flowing

- [ ] In Grafana Cloud, navigate to **Explore**
- [ ] Select Prometheus data source
- [ ] Run test query: `invoiceshelf_database_healthy`
- [ ] Confirm data is visible and recent (within last 1 minute)
- [ ] Test multiple metrics:
  - [ ] `invoiceshelf_invoices_total`
  - [ ] `invoiceshelf_disk_usage_percent`
  - [ ] `invoiceshelf_queue_jobs_pending`

## Phase 3: Dashboard Configuration

### Import Dashboards

- [ ] Created folder "Facturino Monitoring" in Grafana
- [ ] Imported Application Overview dashboard
  - [ ] Copied JSON from `docs/monitoring/dashboards/application-overview.json`
  - [ ] Selected Prometheus data source
  - [ ] Verified all panels are showing data
- [ ] Imported Business Metrics dashboard
  - [ ] Copied JSON from `docs/monitoring/dashboards/business-metrics.json`
  - [ ] Verified revenue, invoices, customers data
- [ ] Imported System Health dashboard
  - [ ] Copied JSON from `docs/monitoring/dashboards/system-health.json`
  - [ ] Verified disk, memory, certificate metrics

### Dashboard Verification

For each dashboard, verify:

- [ ] All panels are green (no errors)
- [ ] Data is displayed correctly
- [ ] Time range selector works
- [ ] Refresh works (manual and auto)
- [ ] Dashboard variables work (if any)

### Customize Dashboards

- [ ] Updated dashboard variables for your environment
- [ ] Set appropriate y-axis ranges
- [ ] Added company logo/branding (optional)
- [ ] Set default time range: Last 24 hours
- [ ] Set auto-refresh: 1 minute

## Phase 4: Alert Configuration

### Contact Points

- [ ] Created email contact point:
  - [ ] Email: ops@facturino.mk
  - [ ] Name: Operations Team
  - [ ] Tested email delivery
- [ ] (Optional) Created Slack contact point:
  - [ ] Webhook URL configured
  - [ ] Channel: #alerts or #monitoring
  - [ ] Tested Slack notification
- [ ] (Optional) Created PagerDuty integration:
  - [ ] Integration key configured
  - [ ] Service linked
  - [ ] Tested PagerDuty alert

### Notification Policies

- [ ] Created default notification policy
- [ ] Routed critical alerts to PagerDuty + Email
- [ ] Routed warnings to Email only
- [ ] Set up grouping: Group by `component` label
- [ ] Set repeat interval: 4 hours

### Alert Rules - Critical

Created and tested the following critical alerts:

- [ ] **Database Down**
  - Query: `invoiceshelf_database_healthy == 0`
  - Threshold: 0
  - Duration: 1 minute
  - Contact: PagerDuty + Email
  - Tested: ✓

- [ ] **Disk Space Critical (>90%)**
  - Query: `invoiceshelf_disk_usage_percent > 90`
  - Threshold: 90%
  - Duration: 5 minutes
  - Contact: Email
  - Tested: ✓

- [ ] **Certificate Expiring (<7 days)**
  - Query: `fakturino_signer_cert_expiry_days < 7`
  - Threshold: 7 days
  - Duration: 1 hour
  - Contact: Email
  - Tested: ✓

- [ ] **Certificate Expired**
  - Query: `fakturino_signer_cert_expiry_days <= 0`
  - Threshold: 0
  - Duration: Immediate
  - Contact: PagerDuty + Email
  - Tested: ✓

- [ ] **Queue Jobs Failed (>100)**
  - Query: `invoiceshelf_queue_jobs_failed > 100`
  - Threshold: 100
  - Duration: 10 minutes
  - Contact: Email
  - Tested: ✓

### Alert Rules - Warning

- [ ] **Disk Space Warning (>80%)**
- [ ] **Memory Usage High (>85%)**
- [ ] **Cache Down**
- [ ] **Slow Response Time (>1000ms)**
- [ ] **Bank Sync Errors (>50/24h)**

### Alert Rules - Info

- [ ] **Overdue Invoices (>20)**
- [ ] **Low Bank Match Rate (<70%)**

### Test Alerts

- [ ] Manually triggered test alert
- [ ] Verified email received
- [ ] Verified Slack notification (if configured)
- [ ] Verified PagerDuty incident created (if configured)
- [ ] Tested alert silencing (maintenance mode)

## Phase 5: UptimeRobot Setup

### Account Setup

- [ ] Created UptimeRobot account at [uptimerobot.com](https://uptimerobot.com)
- [ ] Verified email address
- [ ] Logged into dashboard

### Monitor Configuration

Created the following monitors:

- [ ] **Health Check Monitor**
  - URL: `https://your-app.railway.app/health`
  - Interval: 5 minutes
  - Keyword: `"status":"healthy"`
  - Alert contacts configured

- [ ] **Ping Monitor**
  - URL: `https://your-app.railway.app/ping`
  - Interval: 5 minutes
  - Status code: 200

- [ ] **Metrics Endpoint Monitor**
  - URL: `https://your-app.railway.app/metrics`
  - Interval: 5 minutes
  - Keyword: `invoiceshelf_database_healthy`

- [ ] (Optional) **Customer Portal Monitor**
  - URL: Customer portal URL
  - Interval: 10 minutes

### Alert Contacts

- [ ] Added email alert contact
- [ ] (Optional) Added SMS alert contact
- [ ] (Optional) Added Slack webhook
- [ ] Tested alert delivery for each contact

### Advanced Configuration

- [ ] Enabled SSL certificate monitoring
- [ ] Configured maintenance windows:
  - [ ] Weekly: Sunday 02:00-04:00 GMT
- [ ] Set alert threshold: 1 consecutive down
- [ ] Set down check interval: 1 minute

### Status Page

- [ ] Created public status page
- [ ] (Optional) Configured custom domain: `status.facturino.mk`
- [ ] Added all monitors to status page
- [ ] Customized branding/theme
- [ ] Tested status page accessibility
- [ ] Added status page link to app footer

### Test UptimeRobot

- [ ] All monitors show "Up" status (green)
- [ ] Response times are reasonable (<1000ms)
- [ ] SSL certificate expiry date is correct
- [ ] Triggered test downtime alert:
  - [ ] Took site down temporarily
  - [ ] Received alert notification
  - [ ] Brought site back up
  - [ ] Received recovery notification

## Phase 6: Integration & Documentation

### Team Access

- [ ] Added team members to Grafana Cloud
- [ ] Added team members to UptimeRobot
- [ ] Documented access credentials securely (1Password, etc.)
- [ ] Created on-call rotation schedule

### Runbooks

Created runbooks for critical incidents:

- [ ] `docs/runbooks/database-down.md`
- [ ] `docs/runbooks/disk-full.md`
- [ ] `docs/runbooks/certificate-renewal.md`
- [ ] `docs/runbooks/queue-failures.md`

Each runbook includes:
- [ ] Diagnosis steps
- [ ] Resolution steps
- [ ] Prevention measures
- [ ] Escalation contacts

### Incident Response

- [ ] Documented incident response workflow
- [ ] Created #incidents Slack channel (or equivalent)
- [ ] Set up incident tracking (GitHub Issues, Jira, etc.)
- [ ] Scheduled monthly incident review meetings

### Training

- [ ] Conducted monitoring training for ops team
- [ ] Reviewed alert procedures
- [ ] Practiced incident response drill
- [ ] Documented common troubleshooting steps

## Phase 7: Final Verification

### End-to-End Testing

- [ ] Verified all metrics are being collected
- [ ] Checked all dashboards display correctly
- [ ] Tested all alert rules
- [ ] Verified all notification channels work
- [ ] Tested UptimeRobot monitors and alerts

### Performance Check

- [ ] Monitoring overhead is acceptable (<5% CPU)
- [ ] Metrics endpoint responds quickly (<500ms)
- [ ] No impact on application performance
- [ ] Grafana Agent memory usage is reasonable

### Security Review

- [ ] Metrics endpoint doesn't expose sensitive data
- [ ] Health endpoint doesn't leak internal info
- [ ] Alert notifications don't contain secrets
- [ ] API keys stored securely
- [ ] (Optional) IP whitelist configured for monitoring endpoints

### Documentation Review

- [ ] All setup steps documented
- [ ] Credentials stored securely
- [ ] Team has access to documentation
- [ ] Runbooks are complete and tested
- [ ] Contact information is up to date

## Phase 8: Ongoing Maintenance

### Weekly Tasks

- [ ] Review monitoring dashboard for anomalies
- [ ] Check for any new failed jobs
- [ ] Verify all monitors are green
- [ ] Review response time trends

### Monthly Tasks

- [ ] Review alert thresholds (adjust if needed)
- [ ] Check for false positive alerts
- [ ] Update runbooks based on incidents
- [ ] Review and prune old metrics data
- [ ] Test backup restoration process

### Quarterly Tasks

- [ ] Review and renew SSL certificates
- [ ] Update monitoring documentation
- [ ] Conduct incident response drill
- [ ] Review team access and permissions
- [ ] Optimize dashboard queries

### Annual Tasks

- [ ] Review monitoring strategy
- [ ] Evaluate new monitoring tools
- [ ] Update disaster recovery plan
- [ ] Renew monitoring service subscriptions
- [ ] Conduct comprehensive security audit

## Monitoring Maturity Levels

### Level 1: Basic (Minimum Viable)
- [x] Metrics endpoint enabled
- [x] Health check working
- [x] One external monitor (UptimeRobot)
- [x] Email alerts configured

### Level 2: Production Ready
- [x] Grafana Cloud configured
- [x] All critical alerts set up
- [x] Multiple notification channels
- [x] Public status page
- [x] Basic runbooks

### Level 3: Advanced
- [ ] Custom dashboards for each team
- [ ] SLO/SLA tracking
- [ ] Automated incident response
- [ ] Anomaly detection
- [ ] Distributed tracing (APM)

### Level 4: Expert
- [ ] Predictive alerting (ML-based)
- [ ] Auto-scaling based on metrics
- [ ] Cost optimization dashboards
- [ ] Full observability stack
- [ ] Self-healing infrastructure

## Completion Sign-Off

**Setup completed by**: ___________________

**Date**: ___________________

**Verified by**: ___________________

**Date**: ___________________

**Notes**:
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

## Troubleshooting Common Issues

| Issue | Solution |
|-------|----------|
| Metrics endpoint returns 404 | Check `FEATURE_MONITORING=true` is set |
| No data in Grafana | Verify Grafana Agent is running and scraping |
| Alerts not firing | Check alert query returns data in Explore |
| No email alerts | Verify email contact is verified |
| UptimeRobot shows down | Check if app is actually down, or IP blocked |
| High memory usage | Reduce scrape interval or use recording rules |
| Certificate alert spam | Adjust alert duration (e.g., 1 day instead of 1 hour) |

## Support Resources

- Grafana Cloud Support: [https://grafana.com/support/](https://grafana.com/support/)
- UptimeRobot Help: [https://uptimerobot.com/help/](https://uptimerobot.com/help/)
- Prometheus Documentation: [https://prometheus.io/docs/](https://prometheus.io/docs/)
- Facturino Support: support@facturino.mk

## Next Steps After Completion

1. Schedule weekly monitoring reviews
2. Document first incident response
3. Optimize alert thresholds based on real data
4. Add custom business metrics as needed
5. Implement SLO tracking
6. Set up cost monitoring for infrastructure

**Congratulations!** Your Facturino monitoring infrastructure is now fully operational.
