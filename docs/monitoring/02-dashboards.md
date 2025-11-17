# Grafana Dashboards Configuration

This guide provides dashboard configurations for monitoring Facturino application metrics.

## Overview

We provide three pre-configured dashboards:

1. **Application Overview** - High-level health and performance metrics
2. **Business Metrics** - Revenue, invoices, customers
3. **System Health** - Infrastructure and resource monitoring

## Dashboard 1: Application Overview

### Import Dashboard

1. In Grafana Cloud, click **Dashboards** > **New** > **Import**
2. Copy the JSON from [dashboards/application-overview.json](./dashboards/application-overview.json)
3. Paste into the import dialog
4. Select your Prometheus data source
5. Click **Import**

### Panels Included

- **System Health Status** (Single stat)
  - Database health
  - Cache health
  - Certificate health

- **Application Uptime** (Graph)
  - Uptime in hours/days

- **Response Time** (Graph)
  - Average response time over time
  - P95 and P99 percentiles (if available)

- **Queue Status** (Single stat + Graph)
  - Pending jobs
  - Failed jobs
  - Failed jobs rate

- **Disk Usage** (Gauge)
  - Current disk usage percentage
  - Alert threshold visualization

- **Memory Usage** (Graph)
  - Memory usage over time
  - Memory limit visualization

### Quick Queries

```promql
# System Health Overview
invoiceshelf_database_healthy
invoiceshelf_cache_healthy
fakturino_signer_cert_healthy

# Response Time
invoiceshelf_avg_response_time_ms

# Queue Health
invoiceshelf_queue_jobs_pending
invoiceshelf_queue_jobs_failed

# Disk & Memory
invoiceshelf_disk_usage_percent
invoiceshelf_memory_usage_percent
```

## Dashboard 2: Business Metrics

### Import Dashboard

1. Follow same import process as above
2. Use JSON from [dashboards/business-metrics.json](./dashboards/business-metrics.json)

### Panels Included

- **Revenue (Last 30 Days)** (Single stat)
  ```promql
  invoiceshelf_revenue_30_days_total
  ```

- **Active Customers** (Single stat + Trend)
  ```promql
  invoiceshelf_customers_active
  ```

- **Invoices by Status** (Pie chart)
  ```promql
  sum by (status) (invoiceshelf_invoices_total)
  ```

- **Overdue Invoices** (Single stat with alert)
  ```promql
  invoiceshelf_invoices_overdue
  ```

- **Bank Transaction Match Rate** (Gauge)
  ```promql
  invoiceshelf_bank_match_rate_percent
  ```

- **Banking Sync Activity** (Graph)
  ```promql
  rate(invoiceshelf_bank_transactions_24h[1h])
  ```

- **Total Companies** (Single stat)
  ```promql
  invoiceshelf_companies_total
  ```

### Business Intelligence Queries

```promql
# Revenue trend (7-day moving average)
avg_over_time(invoiceshelf_revenue_30_days_total[7d])

# Customer growth rate
deriv(invoiceshelf_customers_total[1d]) * 86400

# Invoice processing rate (invoices per hour)
rate(invoiceshelf_invoices_total[1h])

# Banking efficiency
invoiceshelf_bank_match_rate_percent / 100
```

## Dashboard 3: System Health

### Import Dashboard

1. Use JSON from [dashboards/system-health.json](./dashboards/system-health.json)

### Panels Included

- **Certificate Expiry** (Graph + Alert)
  ```promql
  fakturino_signer_cert_expiry_days
  ```
  Alert when < 7 days

- **Disk Space** (Gauge)
  ```promql
  invoiceshelf_disk_usage_percent
  ```
  Alert when > 85%

- **Memory Usage** (Graph)
  ```promql
  invoiceshelf_memory_usage_bytes / 1024 / 1024  # Convert to MB
  ```

- **Database Health** (Status panel)
  ```promql
  invoiceshelf_database_healthy
  ```

- **Cache Health** (Status panel)
  ```promql
  invoiceshelf_cache_healthy
  ```

- **Failed Jobs Alert** (Table)
  ```promql
  invoiceshelf_queue_jobs_failed > 10
  ```

- **Bank Sync Errors** (Graph)
  ```promql
  invoiceshelf_bank_sync_errors_24h
  ```

## Creating Custom Dashboards

### Step 1: Create New Dashboard

1. In Grafana, click **Dashboards** > **New Dashboard**
2. Click **Add visualization**
3. Select your Prometheus data source

### Step 2: Add Panels

Example: Revenue over time

```promql
invoiceshelf_revenue_30_days_total
```

**Visualization**: Time series graph
**Legend**: `{{app}} - Revenue (30d)`
**Unit**: Currency (MKD)
**Decimals**: 2

### Step 3: Configure Variables

Add dashboard variables for filtering:

1. Go to **Dashboard settings** > **Variables**
2. Add variable:
   - **Name**: `environment`
   - **Type**: Query
   - **Query**: `label_values(invoiceshelf_database_healthy, environment)`
   - **Multi-value**: Yes
   - **Include all**: Yes

3. Use in queries:
   ```promql
   invoiceshelf_invoices_total{environment=~"$environment"}
   ```

### Step 4: Set Up Refresh

1. In dashboard settings, set auto-refresh: **1m** (1 minute)
2. Set time range: **Last 24 hours** (default)
3. Enable browser notifications for important panels

## Dashboard Best Practices

### Organization

- Group related panels into rows
- Use consistent color schemes
- Add descriptions to complex panels
- Set appropriate y-axis ranges

### Performance

- Use `rate()` for counter metrics
- Avoid too many high-cardinality queries
- Set reasonable time ranges
- Use recording rules for complex queries

### Alerts

- Add alert annotations to critical panels
- Use threshold markers on graphs
- Configure panel alerts (see [03-alert-rules.md](./03-alert-rules.md))

## Sample Dashboard JSON

See the [dashboards/](./dashboards/) directory for complete JSON configurations:

- `application-overview.json` - Main monitoring dashboard
- `business-metrics.json` - Business intelligence dashboard
- `system-health.json` - Infrastructure monitoring

## Customization

### Adding Custom Metrics

If you've added custom metrics to PrometheusController:

1. Ensure they're properly registered:
   ```php
   $prometheus->registerGauge('my_custom_metric', 'Description');
   $prometheus->setGauge('my_custom_metric', $value);
   ```

2. Add to dashboard:
   ```promql
   my_custom_metric
   ```

### Filtering by Company

If you need per-company metrics:

```promql
invoiceshelf_invoices_total{company_id="1"}
```

Note: Currently, company_id labels are not added to metrics. To enable this, modify PrometheusController.php.

## Exporting Dashboards

To export a dashboard for backup or sharing:

1. Open the dashboard
2. Click the settings icon (gear) > **JSON Model**
3. Copy the JSON
4. Save to `docs/monitoring/dashboards/` directory

## Next Steps

- [Configure Alert Rules](./03-alert-rules.md)
- [Set Up UptimeRobot](./04-uptimerobot-setup.md)

## Reference

- [Grafana Dashboard Documentation](https://grafana.com/docs/grafana/latest/dashboards/)
- [PromQL Query Language](https://prometheus.io/docs/prometheus/latest/querying/basics/)
- [Grafana Variables](https://grafana.com/docs/grafana/latest/variables/)
