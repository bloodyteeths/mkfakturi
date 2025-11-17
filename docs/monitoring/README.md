# Facturino Monitoring & Alerting Guide

This directory contains comprehensive documentation for setting up monitoring and alerting infrastructure for the Facturino application.

## Overview

Facturino uses a multi-layered monitoring approach:

1. **Prometheus Metrics** - Application and business metrics exposed at `/metrics`
2. **Health Checks** - System health monitoring at `/health`
3. **Grafana Cloud** - Visualization and alerting platform
4. **UptimeRobot** - External uptime monitoring

## Quick Start

1. [Grafana Cloud Setup](./01-grafana-cloud-setup.md) - Connect Grafana to your metrics endpoint
2. [Dashboard Configuration](./02-dashboards.md) - Import and configure monitoring dashboards
3. [Alert Rules](./03-alert-rules.md) - Set up critical alerts
4. [UptimeRobot Setup](./04-uptimerobot-setup.md) - Configure external uptime monitoring
5. [Monitoring Checklist](./05-monitoring-checklist.md) - Step-by-step setup verification

## Available Endpoints

### Metrics Endpoint: `/metrics`

Prometheus-compatible metrics endpoint exposing:
- Business metrics (invoices, revenue, customers)
- System health (database, cache, disk, memory)
- Banking integration metrics
- Performance metrics (response time, queue jobs)

**Authentication**: Feature-flagged (requires `FEATURE_MONITORING=true`)

**Format**: Prometheus text format (compatible with Grafana Cloud)

### Health Check Endpoint: `/health`

Comprehensive health check endpoint returning JSON with status of:
- Database connectivity
- Redis/Cache
- Queue health
- Storage
- Certificates
- Banking sync
- Backups
- Paddle configuration

**Authentication**: Public (recommended to protect with IP whitelist in production)

**Format**: JSON

### Readiness Endpoint: `/ready`

Kubernetes-style readiness probe for deployment orchestration.

**Authentication**: Public

**Format**: JSON

## Metrics Collected

### Business Metrics

- `invoiceshelf_invoices_total` - Total invoices by status
- `invoiceshelf_revenue_30_days_total` - Revenue in last 30 days
- `invoiceshelf_customers_total` - Total customer count
- `invoiceshelf_customers_active` - Active customers (90 days)
- `invoiceshelf_invoices_overdue` - Overdue invoice count
- `invoiceshelf_companies_total` - Total companies

### System Health Metrics

- `invoiceshelf_database_healthy` - Database connectivity (1=healthy, 0=down)
- `invoiceshelf_cache_healthy` - Cache connectivity (1=healthy, 0=down)
- `invoiceshelf_disk_usage_percent` - Disk usage percentage
- `invoiceshelf_memory_usage_bytes` - Memory usage in bytes
- `invoiceshelf_memory_usage_percent` - Memory usage percentage
- `fakturino_signer_cert_expiry_days` - Days until certificate expiry
- `fakturino_signer_cert_healthy` - Certificate health (1=healthy, 0=expiring)

### Banking Metrics

- `invoiceshelf_bank_transactions_24h` - Transactions synced in 24h
- `invoiceshelf_bank_transactions_matched` - Matched transactions
- `invoiceshelf_bank_transactions_unmatched` - Unmatched transactions
- `invoiceshelf_bank_match_rate_percent` - Transaction match rate %
- `invoiceshelf_bank_sync_errors_24h` - Sync errors in 24h

### Performance Metrics

- `invoiceshelf_avg_response_time_ms` - Average response time (last hour)
- `invoiceshelf_queue_jobs_pending` - Pending queue jobs
- `invoiceshelf_queue_jobs_failed` - Failed queue jobs
- `invoiceshelf_uptime_seconds` - Application uptime

## Alert Thresholds

Recommended alert thresholds are documented in [03-alert-rules.md](./03-alert-rules.md).

## Configuration

### Environment Variables

```bash
# Enable monitoring endpoints
FEATURE_MONITORING=true

# Prometheus configuration
PROMETHEUS_NAMESPACE=invoiceshelf
PROMETHEUS_STORAGE_ADAPTER=memory
PROMETHEUS_COLLECT_DEFAULT_METRICS=true
PROMETHEUS_BUSINESS_METRICS_ENABLED=true
PROMETHEUS_PERFORMANCE_METRICS_ENABLED=true
```

### Feature Flag

The `/metrics` endpoint is protected by the `monitoring` feature flag. Ensure it's enabled:

```php
// config/features.php
'monitoring' => env('FEATURE_MONITORING', false),
```

## Security Considerations

1. **Protect Metrics Endpoint**: Consider adding IP whitelist or authentication
2. **Health Check Access**: Health checks are public by default - add middleware if needed
3. **Sensitive Data**: Metrics do not expose sensitive customer data
4. **Rate Limiting**: Consider rate limiting on monitoring endpoints

## Support

For issues or questions:
- Check the [Monitoring Checklist](./05-monitoring-checklist.md)
- Review logs: `storage/logs/laravel.log`
- Contact support: support@facturino.mk

## Related Documentation

- [Prometheus Configuration](../../config/prometheus.php)
- [PrometheusController](../../app/Http/Controllers/PrometheusController.php)
- [HealthController](../../app/Http/Controllers/HealthController.php)
