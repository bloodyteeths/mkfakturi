# Grafana Cloud Setup Guide

This guide walks you through setting up Grafana Cloud to monitor your Facturino application.

## Prerequisites

- Facturino application deployed and accessible
- `FEATURE_MONITORING=true` in your environment
- Admin access to create Grafana Cloud account

## Step 1: Create Grafana Cloud Account

1. Go to [https://grafana.com/auth/sign-up/create-user](https://grafana.com/auth/sign-up/create-user)
2. Sign up for a free Grafana Cloud account
3. Choose a stack name (e.g., `facturino-monitoring`)
4. Select the region closest to your application (e.g., `eu-central-1` for Europe)
5. Complete account setup

## Step 2: Set Up Prometheus Data Source

### Option A: Using Grafana Agent (Recommended)

Grafana Agent is a lightweight alternative to running a full Prometheus server.

1. In Grafana Cloud, navigate to **Connections** > **Add new connection**
2. Search for and select **Grafana Agent**
3. Follow the installation instructions for your platform:

#### For Railway (Docker Deployment)

Add Grafana Agent as a sidecar container in your Railway deployment:

```yaml
# railway.toml or docker-compose.yml
services:
  grafana-agent:
    image: grafana/agent:latest
    volumes:
      - ./grafana-agent.yaml:/etc/agent/agent.yaml
    command:
      - -config.file=/etc/agent/agent.yaml
      - -metrics.wal-directory=/tmp/agent/wal
      - -enable-features=integrations-next
    restart: unless-stopped
```

Create `grafana-agent.yaml` in your project root:

```yaml
server:
  log_level: info

metrics:
  wal_directory: /tmp/agent/wal
  global:
    scrape_interval: 60s
    remote_write:
      - url: https://prometheus-prod-XX-XXX.grafana.net/api/prom/push
        basic_auth:
          username: YOUR_PROMETHEUS_USERNAME
          password: YOUR_GRAFANA_CLOUD_API_KEY

  configs:
    - name: facturino
      scrape_configs:
        # Scrape Facturino metrics endpoint
        - job_name: 'facturino-app'
          metrics_path: '/metrics'
          static_configs:
            - targets:
                - 'localhost:8000'  # Adjust to your app's address
              labels:
                environment: 'production'
                app: 'facturino'

        # Health check monitoring (optional)
        - job_name: 'facturino-health'
          metrics_path: '/health'
          metric_relabel_configs:
            - source_labels: [__name__]
              regex: '.*'
              target_label: __name__
              replacement: 'facturino_health_check'
          static_configs:
            - targets:
                - 'localhost:8000'
              labels:
                environment: 'production'
                app: 'facturino'
```

**Replace the following:**
- `YOUR_PROMETHEUS_USERNAME` - From Grafana Cloud (format: `123456`)
- `YOUR_GRAFANA_CLOUD_API_KEY` - Generate from Grafana Cloud > Security > API Keys
- `localhost:8000` - Your actual application URL/port

### Option B: Using Hosted Prometheus (Alternative)

If you prefer to run your own Prometheus server:

1. Install Prometheus on a server
2. Configure it to scrape your Facturino `/metrics` endpoint
3. Configure remote_write to send data to Grafana Cloud

See [prometheus-config-example.yml](./config/prometheus-config-example.yml) for a complete example.

### Option C: Direct HTTP Push (For Testing)

For testing or simple setups, you can use Grafana Cloud's Prometheus-compatible push endpoint:

```bash
# Test pushing metrics manually
curl -X POST \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -u "YOUR_PROMETHEUS_USERNAME:YOUR_GRAFANA_CLOUD_API_KEY" \
  --data-binary @metrics.txt \
  "https://prometheus-prod-XX-XXX.grafana.net/api/prom/push"
```

However, this requires a push-based approach and is not recommended for production.

## Step 3: Verify Metrics Are Flowing

1. In Grafana Cloud, go to **Explore**
2. Select your Prometheus data source
3. Try querying a metric:
   ```promql
   invoiceshelf_database_healthy
   ```
4. You should see data points if metrics are being scraped successfully

### Troubleshooting

**No data appearing?**

1. Check that metrics endpoint is accessible:
   ```bash
   curl https://your-app.railway.app/metrics
   ```

2. Verify feature flag is enabled:
   ```bash
   # In your Railway environment variables
   FEATURE_MONITORING=true
   ```

3. Check Grafana Agent logs:
   ```bash
   railway logs --service grafana-agent
   ```

4. Test metrics endpoint manually:
   ```bash
   curl -v https://your-app.railway.app/metrics
   # Should return Prometheus text format metrics
   ```

**Metrics endpoint returns 404?**

- Ensure the `monitoring` feature flag is enabled
- Check that you're using the correct URL path: `/metrics` (not `/prometheus/metrics`)
- Verify middleware configuration in `routes/web.php`

## Step 4: Configure Data Source in Grafana

1. In Grafana Cloud, go to **Connections** > **Data sources**
2. You should see your Prometheus data source automatically configured
3. Click **Test** to verify connectivity
4. If needed, adjust settings:
   - **HTTP URL**: Your Prometheus endpoint (if self-hosted)
   - **Auth**: Basic auth with Grafana Cloud credentials
   - **Scrape interval**: 60s (recommended)

## Step 5: Set Up Service Account (For API Access)

If you plan to use Grafana API for automation:

1. Go to **Administration** > **Service accounts**
2. Click **Add service account**
3. Name: `facturino-monitoring`
4. Role: `Viewer` (or `Editor` if you need to create dashboards via API)
5. Click **Add token**
6. Copy and save the token securely
7. Use this token for API access

## Next Steps

- [Configure Dashboards](./02-dashboards.md)
- [Set Up Alert Rules](./03-alert-rules.md)

## Reference

- [Grafana Cloud Documentation](https://grafana.com/docs/grafana-cloud/)
- [Grafana Agent Configuration](https://grafana.com/docs/agent/latest/)
- [Prometheus Metrics Format](https://prometheus.io/docs/instrumenting/exposition_formats/)

## Support

If you encounter issues:
1. Check Grafana Agent logs
2. Verify metrics endpoint is accessible
3. Contact Grafana Cloud support: [https://grafana.com/support/](https://grafana.com/support/)
