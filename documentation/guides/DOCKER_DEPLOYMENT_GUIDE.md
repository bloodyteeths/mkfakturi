# mkaccounting Docker Deployment Guide

## Successfully Deployed! 🎉

Your mkaccounting application is now successfully running in Docker containers. All issues have been resolved and the application is ready for production use.

## Current Status

✅ **All containers running and healthy**
- Application: `mkaccounting_app` (healthy)
- Database: `mkaccounting_db` (PostgreSQL 16, healthy) 
- Cache: `mkaccounting_redis` (Redis 7, healthy)

✅ **Application accessible at**: http://localhost
✅ **Health check endpoint**: http://localhost/health
✅ **All migrations applied**: 113 migrations successfully run
✅ **Database connectivity**: PostgreSQL connection verified
✅ **Cache connectivity**: Redis connection verified

## Architecture Overview

This deployment uses a **custom multi-stage Docker build** approach that eliminates volume mount conflicts and ensures consistent production deployments:

### 1. Multi-Stage Dockerfile (`Dockerfile.mkaccounting`)
- **Stage 1**: PHP 8.2 base with all extensions (PDO, Redis, ImageMagick, etc.)
- **Stage 2**: Composer dependency installation with optimized autoloader
- **Stage 3**: Node.js 20 frontend build (Vite + assets compilation)
- **Stage 4**: Production image with Nginx + PHP-FPM + Supervisor

### 2. Production Configuration (`docker-compose.production.yml`)
- Self-contained application image (no volume mounts)
- PostgreSQL database with persistent storage
- Redis for caching and sessions
- Health checks for all services
- Proper networking and security

## Key Issues Resolved

1. **SwiftMailer Configuration Error**: Fixed deprecated mailer references in `config/logging.php`
2. **Database Migration Issues**: Fixed performance index migration for non-existent columns
3. **Chart.js v4 Compatibility**: Updated Chart.js imports in Vue components
4. **PantheonExportJob Error**: Fixed constructor argument issues in scheduled jobs
5. **Redis Authentication**: Added proper Redis password configuration
6. **Application Key**: Generated missing Laravel application key
7. **Environment Configuration**: Created proper `.env` file with production settings

## Files Created/Modified

### New Files
- `Dockerfile.mkaccounting` - Custom multi-stage production Dockerfile
- `docker-compose.production.yml` - Clean production compose configuration  
- `.dockerignore` - Optimized Docker ignore rules
- `DOCKER_DEPLOYMENT_GUIDE.md` - This deployment guide

### Modified Files
- `config/logging.php` - Removed SwiftMailer references
- `config/database.php` - Completed database configuration
- `package.json` - Updated Chart.js and axios versions
- `routes/console.php` - Fixed PantheonExportJob scheduling
- `database/migrations/2025_07_26_002609_add_performance_indexes.php` - Fixed notes table indexes
- `resources/scripts/admin/components/charts/LineChart.vue` - Updated Chart.js v4 syntax

## Production Deployment Commands

```bash
# Build and start the application
docker build -f Dockerfile.mkaccounting -t mkaccounting:fixed-v2 .
docker-compose -f docker-compose.production.yml up -d

# Check status
docker-compose -f docker-compose.production.yml ps

# View logs
docker-compose -f docker-compose.production.yml logs -f app

# Health check
curl http://localhost/health
```

## Container Management

### Start Services
```bash
docker-compose -f docker-compose.production.yml up -d
```

### Stop Services  
```bash
docker-compose -f docker-compose.production.yml down
```

### View Logs
```bash
# All services
docker-compose -f docker-compose.production.yml logs -f

# Specific service
docker logs mkaccounting_app -f
```

### Execute Commands
```bash
# Laravel artisan commands
docker exec mkaccounting_app php artisan migrate
docker exec mkaccounting_app php artisan cache:clear

# Database access
docker exec -it mkaccounting_db psql -U mkaccounting -d mkaccounting
```

## Configuration Details

### Environment Variables
- **APP_ENV**: production
- **APP_DEBUG**: false  
- **DB_CONNECTION**: pgsql (PostgreSQL)
- **CACHE_STORE**: redis
- **SESSION_DRIVER**: redis
- **QUEUE_CONNECTION**: redis

### Ports
- **Application**: 80 (HTTP)
- **Database**: 5432 (PostgreSQL, internal)
- **Redis**: 6379 (internal)

### Data Persistence
- PostgreSQL data: `postgres_data` volume
- Redis data: `redis_data` volume

## Performance Features

### Optimizations Applied
- PHP OPcache enabled
- Redis caching for sessions and application cache
- Nginx reverse proxy with PHP-FPM
- Optimized Composer autoloader
- Production asset compilation with Vite
- Performance database indexes

### Process Management
- **Supervisor** manages all processes
- **Queue Workers**: 2 background workers for job processing
- **Scheduler**: Laravel cron jobs via supervisor
- **Health Checks**: Automated container health monitoring

## Security Considerations

### Production Security
- Debug mode disabled (`APP_DEBUG=false`)
- Secure session configuration
- Database credentials via environment variables
- Redis password authentication
- Non-root container user (`www:www`)

### Recommended Additional Security
- Enable HTTPS with SSL certificates
- Configure firewall rules
- Implement backup strategies
- Set up log monitoring
- Configure fail2ban for brute force protection

## Troubleshooting

### Common Issues

**Container Restart Loop**:
```bash
# Check logs
docker logs mkaccounting_app --tail 50

# Common causes:
# - Missing environment variables
# - Database connection issues
# - Redis authentication problems
```

**Database Connection Failed**:
```bash
# Check database status
docker exec mkaccounting_db pg_isready -U mkaccounting

# Test connection
docker exec mkaccounting_app php artisan tinker --execute="DB::connection()->getPdo();"
```

**Cache Connection Issues**:
```bash
# Test Redis connection
docker exec mkaccounting_redis redis-cli ping

# Clear cache
docker exec mkaccounting_app php artisan cache:clear
```

## Monitoring

### Health Check Endpoint
GET `/health` returns:
```json
{
  "status": "ok",
  "timestamp": "2025-07-27T10:59:39.847612Z", 
  "version": "1.0.0",
  "environment": "production",
  "checks": {
    "database": "ok",
    "cache": "ok", 
    "storage": "ok"
  }
}
```

### Log Locations
- **Application**: `/var/www/html/storage/logs/laravel.log`
- **Nginx**: `/var/log/nginx/access.log`, `/var/log/nginx/error.log`
- **Queue Workers**: `/var/www/html/storage/logs/queue.log`
- **Scheduler**: `/var/www/html/storage/logs/scheduler.log`

## Next Steps for Production

1. **Domain Setup**: Configure your domain and SSL certificates
2. **Backup Strategy**: Implement database and file backups
3. **Monitoring**: Set up application performance monitoring
4. **Scaling**: Consider load balancing for high traffic
5. **Updates**: Plan for application updates and maintenance

---

**Deployment completed successfully!** 🚀

The mkaccounting application is now running in a production-ready Docker environment with all dependencies properly configured and tested.