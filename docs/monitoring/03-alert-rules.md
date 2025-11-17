# Alert Rules Configuration

This guide provides comprehensive alert rules for monitoring critical issues in your Facturino application.

## Overview

Alert rules are configured in Grafana Cloud and trigger notifications when metrics cross defined thresholds. We provide alert rules for:

1. System health (database, cache, disk)
2. Application performance
3. Business metrics
4. Security (certificates)

## Alert Configuration in Grafana Cloud

### Step 1: Navigate to Alert Rules

1. In Grafana Cloud, go to **Alerting** > **Alert rules**
2. Click **New alert rule**

### Step 2: Configure Contact Points

Before creating alerts, set up notification channels:

1. Go to **Alerting** > **Contact points**
2. Click **Add contact point**
3. Configure your notification method:
   - **Email** (recommended for production)
   - **Slack** (for team notifications)
   - **PagerDuty** (for on-call)
   - **Webhook** (for custom integrations)

Example Email Contact Point:
```yaml
Name: facturino-ops-team
Type: Email
Addresses: ops@facturino.mk, alerts@facturino.mk
```

### Step 3: Create Notification Policy

1. Go to **Alerting** > **Notification policies**
2. Set default route to your contact point
3. Add label-based routing for different severity levels:
   - `severity=critical` → PagerDuty + Email
   - `severity=warning` → Email only
   - `severity=info` → Slack

## Critical Alert Rules

### 1. Database Down

**Rule Name**: `[CRITICAL] Database Connection Failed`

**Condition**:
```promql
invoiceshelf_database_healthy == 0
```

**For duration**: 1 minute

**Severity**: Critical

**Description**: Database connectivity check is failing. Application cannot access the database.

**Notification**: Immediate (PagerDuty + Email)

**Grafana Alert Configuration**:
```yaml
- alert: DatabaseDown
  expr: invoiceshelf_database_healthy == 0
  for: 1m
  labels:
    severity: critical
    component: database
  annotations:
    summary: "Database connection failed"
    description: "Facturino cannot connect to the database for {{ $value }} minutes"
    runbook_url: "https://docs.facturino.mk/runbooks/database-down"
```

### 2. Disk Space Critical

**Rule Name**: `[CRITICAL] Disk Space Above 90%`

**Condition**:
```promql
invoiceshelf_disk_usage_percent > 90
```

**For duration**: 5 minutes

**Severity**: Critical

**Description**: Disk usage is critically high. Risk of application failure.

**Notification**: Immediate

**Grafana Alert Configuration**:
```yaml
- alert: DiskSpaceCritical
  expr: invoiceshelf_disk_usage_percent > 90
  for: 5m
  labels:
    severity: critical
    component: storage
  annotations:
    summary: "Disk space critically low"
    description: "Disk usage is at {{ $value }}% - immediate action required"
    runbook_url: "https://docs.facturino.mk/runbooks/disk-full"
```

### 3. Certificate Expiring Soon

**Rule Name**: `[CRITICAL] E-Invoice Certificate Expires in < 7 Days`

**Condition**:
```promql
fakturino_signer_cert_expiry_days < 7 and fakturino_signer_cert_expiry_days > 0
```

**For duration**: 1 hour

**Severity**: Critical

**Description**: Digital signature certificate will expire soon. E-invoicing will stop working.

**Notification**: Daily reminder until resolved

**Grafana Alert Configuration**:
```yaml
- alert: CertificateExpiringSoon
  expr: fakturino_signer_cert_expiry_days < 7 and fakturino_signer_cert_expiry_days > 0
  for: 1h
  labels:
    severity: critical
    component: security
  annotations:
    summary: "E-Invoice certificate expiring soon"
    description: "Certificate expires in {{ $value }} days - renew immediately"
    runbook_url: "https://docs.facturino.mk/runbooks/certificate-renewal"
```

### 4. Certificate Expired

**Rule Name**: `[CRITICAL] E-Invoice Certificate Expired`

**Condition**:
```promql
fakturino_signer_cert_expiry_days <= 0
```

**For duration**: 0 (immediate)

**Severity**: Critical

**Description**: Certificate has expired. E-invoicing is not functional.

**Notification**: Immediate

**Grafana Alert Configuration**:
```yaml
- alert: CertificateExpired
  expr: fakturino_signer_cert_expiry_days <= 0
  labels:
    severity: critical
    component: security
  annotations:
    summary: "E-Invoice certificate EXPIRED"
    description: "Certificate is expired - E-invoicing is non-functional!"
```

### 5. Queue Jobs Failing

**Rule Name**: `[CRITICAL] High Queue Failure Rate`

**Condition**:
```promql
invoiceshelf_queue_jobs_failed > 100
```

**For duration**: 10 minutes

**Severity**: Critical

**Description**: Too many queue jobs are failing. Background processes may not be working.

**Notification**: Immediate

**Grafana Alert Configuration**:
```yaml
- alert: QueueFailureHigh
  expr: invoiceshelf_queue_jobs_failed > 100
  for: 10m
  labels:
    severity: critical
    component: queue
  annotations:
    summary: "High number of failed queue jobs"
    description: "{{ $value }} jobs have failed - check application logs"
    runbook_url: "https://docs.facturino.mk/runbooks/queue-failures"
```

## Warning Alert Rules

### 6. Disk Space Warning

**Rule Name**: `[WARNING] Disk Space Above 80%`

**Condition**:
```promql
invoiceshelf_disk_usage_percent > 80 and invoiceshelf_disk_usage_percent <= 90
```

**For duration**: 15 minutes

**Severity**: Warning

**Grafana Alert Configuration**:
```yaml
- alert: DiskSpaceWarning
  expr: invoiceshelf_disk_usage_percent > 80 and invoiceshelf_disk_usage_percent <= 90
  for: 15m
  labels:
    severity: warning
    component: storage
  annotations:
    summary: "Disk space running low"
    description: "Disk usage is at {{ $value }}% - plan cleanup or expansion"
```

### 7. Memory Usage High

**Rule Name**: `[WARNING] Memory Usage Above 85%`

**Condition**:
```promql
invoiceshelf_memory_usage_percent > 85
```

**For duration**: 10 minutes

**Severity**: Warning

**Grafana Alert Configuration**:
```yaml
- alert: MemoryUsageHigh
  expr: invoiceshelf_memory_usage_percent > 85
  for: 10m
  labels:
    severity: warning
    component: resources
  annotations:
    summary: "Memory usage is high"
    description: "Memory usage at {{ $value }}% - consider scaling or optimization"
```

### 8. Cache Down

**Rule Name**: `[WARNING] Cache/Redis Connection Failed`

**Condition**:
```promql
invoiceshelf_cache_healthy == 0
```

**For duration**: 5 minutes

**Severity**: Warning

**Grafana Alert Configuration**:
```yaml
- alert: CacheDown
  expr: invoiceshelf_cache_healthy == 0
  for: 5m
  labels:
    severity: warning
    component: cache
  annotations:
    summary: "Cache service is unavailable"
    description: "Redis/Cache is down - performance will be degraded"
```

### 9. Slow Response Time

**Rule Name**: `[WARNING] Average Response Time > 1000ms`

**Condition**:
```promql
invoiceshelf_avg_response_time_ms > 1000
```

**For duration**: 15 minutes

**Severity**: Warning

**Grafana Alert Configuration**:
```yaml
- alert: SlowResponseTime
  expr: invoiceshelf_avg_response_time_ms > 1000
  for: 15m
  labels:
    severity: warning
    component: performance
  annotations:
    summary: "Application response time is slow"
    description: "Average response time is {{ $value }}ms - investigate performance"
```

### 10. Bank Sync Errors

**Rule Name**: `[WARNING] High Bank Sync Error Rate`

**Condition**:
```promql
invoiceshelf_bank_sync_errors_24h > 50
```

**For duration**: 30 minutes

**Severity**: Warning

**Grafana Alert Configuration**:
```yaml
- alert: BankSyncErrors
  expr: invoiceshelf_bank_sync_errors_24h > 50
  for: 30m
  labels:
    severity: warning
    component: banking
  annotations:
    summary: "Bank synchronization errors detected"
    description: "{{ $value }} sync errors in last 24h - check banking API"
```

## Business Alerts

### 11. Overdue Invoices Spike

**Rule Name**: `[INFO] High Number of Overdue Invoices`

**Condition**:
```promql
invoiceshelf_invoices_overdue > 20
```

**For duration**: 1 hour

**Severity**: Info

**Grafana Alert Configuration**:
```yaml
- alert: OverdueInvoicesHigh
  expr: invoiceshelf_invoices_overdue > 20
  for: 1h
  labels:
    severity: info
    component: business
  annotations:
    summary: "High number of overdue invoices"
    description: "{{ $value }} invoices are overdue - consider payment reminders"
```

### 12. Low Bank Match Rate

**Rule Name**: `[WARNING] Bank Transaction Match Rate Below 70%`

**Condition**:
```promql
invoiceshelf_bank_match_rate_percent < 70
```

**For duration**: 1 hour

**Severity**: Warning

**Grafana Alert Configuration**:
```yaml
- alert: LowBankMatchRate
  expr: invoiceshelf_bank_match_rate_percent < 70
  for: 1h
  labels:
    severity: warning
    component: banking
  annotations:
    summary: "Bank transaction match rate is low"
    description: "Only {{ $value }}% of transactions matched - check matching rules"
```

## Setting Up Alerts in Grafana Cloud

### Method 1: Via UI

1. Go to **Alerting** > **Alert rules**
2. Click **New alert rule**
3. Fill in the form:
   - **Rule name**: Copy from above (e.g., `[CRITICAL] Database Down`)
   - **Query**: Select Prometheus data source
   - **Condition**: Paste PromQL query
   - **Evaluate every**: 1m
   - **For**: Duration from above
4. Add labels and annotations
5. Click **Save and exit**

### Method 2: Via Terraform (Infrastructure as Code)

```hcl
# terraform/grafana-alerts.tf
resource "grafana_rule_group" "facturino_critical_alerts" {
  name             = "Facturino Critical Alerts"
  folder_uid       = grafana_folder.facturino_monitoring.uid
  interval_seconds = 60

  rule {
    name      = "[CRITICAL] Database Down"
    condition = "A"

    data {
      ref_id = "A"
      query_type = "prometheus"
      datasource_uid = grafana_data_source.prometheus.uid

      relativeTimeRange {
        from = 600
        to   = 0
      }

      model = jsonencode({
        expr = "invoiceshelf_database_healthy == 0"
      })
    }

    for = "1m"

    labels = {
      severity  = "critical"
      component = "database"
    }

    annotations = {
      summary     = "Database connection failed"
      description = "Facturino cannot connect to the database"
    }
  }
}
```

### Method 3: Via API

```bash
# Create alert rule via Grafana API
curl -X POST \
  -H "Authorization: Bearer ${GRAFANA_API_KEY}" \
  -H "Content-Type: application/json" \
  -d @alert-rule.json \
  "https://YOUR-STACK.grafana.net/api/v1/provisioning/alert-rules"
```

## Alert Testing

### Test Alert Rules

1. In Grafana, go to your alert rule
2. Click **Test** button
3. Verify the query returns expected results
4. Check that notifications are sent to correct contact points

### Manual Testing

Trigger alerts manually for testing:

```bash
# Temporarily disable database to test alert
# (DO NOT DO IN PRODUCTION!)
railway run php artisan down

# Wait for alert to trigger (1-2 minutes)
# Then bring it back up
railway run php artisan up
```

### Silence Alerts (Maintenance Mode)

During maintenance:

1. Go to **Alerting** > **Silences**
2. Click **Add silence**
3. Add matcher: `component=database`
4. Set duration: 1 hour
5. Add comment: "Scheduled database maintenance"

## Alert Runbooks

For each critical alert, create a runbook:

- `/docs/runbooks/database-down.md`
- `/docs/runbooks/disk-full.md`
- `/docs/runbooks/certificate-renewal.md`
- `/docs/runbooks/queue-failures.md`

Include:
- Diagnosis steps
- Resolution steps
- Prevention measures
- Escalation contacts

## Recommended Alert Thresholds Summary

| Alert | Threshold | Duration | Severity |
|-------|-----------|----------|----------|
| Database Down | == 0 | 1m | Critical |
| Disk Space Critical | > 90% | 5m | Critical |
| Disk Space Warning | > 80% | 15m | Warning |
| Memory High | > 85% | 10m | Warning |
| Certificate Expiring | < 7 days | 1h | Critical |
| Certificate Expired | <= 0 days | 0 | Critical |
| Queue Failures | > 100 | 10m | Critical |
| Cache Down | == 0 | 5m | Warning |
| Slow Response | > 1000ms | 15m | Warning |
| Bank Sync Errors | > 50/24h | 30m | Warning |
| Overdue Invoices | > 20 | 1h | Info |
| Low Match Rate | < 70% | 1h | Warning |

## Next Steps

- [Set Up UptimeRobot](./04-uptimerobot-setup.md)
- [Complete Monitoring Checklist](./05-monitoring-checklist.md)

## Reference

- [Grafana Alerting Documentation](https://grafana.com/docs/grafana/latest/alerting/)
- [PromQL Alert Syntax](https://prometheus.io/docs/prometheus/latest/configuration/alerting_rules/)
