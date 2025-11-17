# Railway Deployment Guide for Monitoring

This guide explains how to deploy Grafana Agent alongside your Facturino application on Railway.

## Option 1: Add Grafana Agent as a Separate Service (Recommended)

### Step 1: Create Grafana Agent Service

1. In your Railway project, click **New Service**
2. Select **Empty Service**
3. Name it: `grafana-agent`

### Step 2: Configure Grafana Agent Service

1. Go to **Variables** tab
2. Add the following variables:

```env
# Grafana Cloud credentials
GRAFANA_CLOUD_PROMETHEUS_URL=https://prometheus-prod-XX-XXX.grafana.net/api/prom/push
GRAFANA_CLOUD_PROMETHEUS_USER=123456
GRAFANA_CLOUD_API_KEY=your-api-key-here

# Target application URL (internal Railway URL)
FACTURINO_APP_URL=facturino-app.railway.internal:8000
```

### Step 3: Create Dockerfile for Grafana Agent

Create `Dockerfile.grafana-agent` in your project root:

```dockerfile
FROM grafana/agent:latest

# Copy agent configuration
COPY docs/monitoring/config/grafana-agent.yaml /etc/agent/agent.yaml

# Expose agent metrics (optional)
EXPOSE 12345

# Run agent
ENTRYPOINT ["/bin/agent"]
CMD ["-config.file=/etc/agent/agent.yaml", "-metrics.wal-directory=/tmp/agent/wal"]
```

### Step 4: Deploy Grafana Agent

1. In Railway, select the `grafana-agent` service
2. Go to **Settings** > **Source**
3. Select your repository
4. Set **Dockerfile Path**: `Dockerfile.grafana-agent`
5. Click **Deploy**

### Step 5: Update Grafana Agent Configuration

Edit `docs/monitoring/config/grafana-agent.yaml` to use Railway-specific URLs:

```yaml
configs:
  - name: facturino-metrics
    scrape_configs:
      - job_name: 'facturino-app'
        metrics_path: '/metrics'
        static_configs:
          - targets:
              # Use Railway internal networking
              - 'facturino-app.railway.internal:8000'
            labels:
              app: 'facturino'
              environment: 'production'
```

## Option 2: Sidecar Container (Advanced)

If you're using Docker Compose or Nixpacks with multi-container support:

### docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile.mkaccounting
    environment:
      FEATURE_MONITORING: "true"
    ports:
      - "8000:8000"
    networks:
      - facturino-network

  grafana-agent:
    image: grafana/agent:latest
    volumes:
      - ./docs/monitoring/config/grafana-agent.yaml:/etc/agent/agent.yaml:ro
    environment:
      - GRAFANA_CLOUD_PROMETHEUS_URL=${GRAFANA_CLOUD_PROMETHEUS_URL}
      - GRAFANA_CLOUD_PROMETHEUS_USER=${GRAFANA_CLOUD_PROMETHEUS_USER}
      - GRAFANA_CLOUD_API_KEY=${GRAFANA_CLOUD_API_KEY}
    command:
      - -config.file=/etc/agent/agent.yaml
      - -metrics.wal-directory=/tmp/agent/wal
    networks:
      - facturino-network
    depends_on:
      - app

networks:
  facturino-network:
    driver: bridge
```

## Option 3: External Prometheus Server (Not Recommended for Railway)

If you prefer to run Prometheus externally and scrape your Railway app:

1. Deploy Prometheus on a separate server/VM
2. Configure it to scrape your Railway public URL
3. Set up remote_write to Grafana Cloud

**Note**: This requires your `/metrics` endpoint to be publicly accessible, which may be a security concern.

## Railway-Specific Considerations

### Internal Networking

Railway provides internal DNS for services:
- Format: `{service-name}.railway.internal`
- Use this for inter-service communication
- No public internet egress charges

### Environment Variables

Store sensitive credentials in Railway's **Variables** tab:
- Auto-encrypted
- Per-environment support (staging/production)
- Can reference other variables

### Health Checks

Railway automatically performs health checks. Ensure your app responds to:
- `/health` - Comprehensive health check
- `/ping` - Quick availability check

### Logging

Railway captures stdout/stderr automatically:
- View in Railway dashboard
- No need for separate log aggregation (unless required)

## Verification Steps

After deployment:

1. **Check Grafana Agent Logs**:
   ```bash
   railway logs --service grafana-agent
   ```

   Look for:
   ```
   level=info msg="Starting Grafana Agent"
   level=info msg="Successfully scraped metrics" job=facturino-app
   ```

2. **Test Metrics Endpoint**:
   ```bash
   # From within Railway (using railway CLI)
   railway run curl http://facturino-app.railway.internal:8000/metrics
   ```

3. **Verify in Grafana Cloud**:
   - Go to Explore
   - Query: `invoiceshelf_database_healthy`
   - Should see recent data

## Troubleshooting

### Agent Can't Reach App

**Symptom**: Logs show connection refused or timeout

**Solution**:
1. Verify internal DNS: `facturino-app.railway.internal`
2. Check app is listening on `0.0.0.0:8000` (not `127.0.0.1`)
3. Verify `FEATURE_MONITORING=true` is set

### No Data in Grafana Cloud

**Symptom**: Metrics not appearing in Grafana

**Solution**:
1. Check Grafana Cloud credentials are correct
2. Verify remote_write URL format
3. Check agent logs for authentication errors
4. Ensure API key has write permissions

### High Memory Usage

**Symptom**: Grafana Agent consuming too much memory

**Solution**:
1. Reduce scrape frequency (60s → 120s)
2. Limit time series cardinality
3. Increase remote_write batch size
4. Add memory limits in Railway:
   ```
   Railway Settings > Resources > Memory Limit: 512MB
   ```

## Cost Optimization

### Reduce Scraping Frequency

For non-critical metrics, increase scrape interval:

```yaml
global:
  scrape_interval: 120s  # Instead of 60s
```

### Selective Metrics

Only scrape metrics you need:

```yaml
metric_relabel_configs:
  # Drop metrics you don't need
  - source_labels: [__name__]
    regex: 'invoiceshelf_internal_.*'
    action: drop
```

### Use Grafana Cloud Free Tier

- 10,000 series
- 15 days retention
- Sufficient for most small deployments

If you exceed:
- Review cardinality
- Drop unnecessary labels
- Use recording rules for aggregations

## Alternative: Grafana Cloud Integration (No Agent)

Grafana Cloud offers direct integrations for popular platforms. Check if available for Railway.

## Monitoring Railway Itself

Railway provides metrics via their API:

```bash
# Get deployment metrics
curl -H "Authorization: Bearer $RAILWAY_TOKEN" \
  https://api.railway.app/graphql \
  -d '{"query": "{ deployment(id: \"...\") { metrics { cpuUsage memoryUsage } } }"}'
```

Consider creating a custom exporter for Railway platform metrics.

## Next Steps

1. Complete [Monitoring Checklist](../05-monitoring-checklist.md)
2. Set up alerts in Grafana Cloud
3. Configure UptimeRobot for external monitoring
4. Document incident response procedures

## Reference

- [Railway Documentation](https://docs.railway.app/)
- [Grafana Agent Documentation](https://grafana.com/docs/agent/latest/)
- [Railway Internal Networking](https://docs.railway.app/deploy/private-networking)
