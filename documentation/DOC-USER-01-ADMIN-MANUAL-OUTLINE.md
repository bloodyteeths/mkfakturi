# Facturino Administrator Manual - Complete Outline
**Version:** 1.0
**Date:** November 2025
**Audience:** System Administrators, IT Managers, DevOps Engineers

---

## Table of Contents

### Part 1: Installation & Setup (Pages 1-30)

#### 1.1 System Requirements
- Server requirements
  - PHP 8.1+
  - MySQL 8.0+ / PostgreSQL 13+
  - Redis (recommended)
  - Node.js 18+ (for builds)
- Hardware recommendations
  - CPU: 2+ cores
  - RAM: 4GB minimum, 8GB recommended
  - Storage: 20GB minimum
  - SSD recommended for database
- Operating system support
  - Ubuntu 20.04/22.04 LTS
  - Debian 11/12
  - CentOS/RHEL 8+
  - Docker support

#### 1.2 Installation Methods
- Docker installation (recommended)
  - Docker Compose setup
  - Environment variables
  - Volume mounting
  - Network configuration
- Manual installation
  - PHP-FPM setup
  - Nginx configuration
  - Apache configuration
  - Database creation
- Railway.app deployment
  - Quick deploy button
  - Environment variables
  - Database setup
  - Queue worker configuration
- Cloud provider setup
  - AWS EC2
  - DigitalOcean
  - Linode
  - Vultr

#### 1.3 Initial Configuration
- Environment file (.env)
  - Application key generation
  - Database connection
  - Mail configuration
  - Queue configuration
  - Cache configuration
  - Session configuration
- Database migrations
  - Running migrations
  - Seeding initial data
  - Database backups before migration
- Application setup
  - Admin account creation
  - Company creation
  - License key activation
- Web server configuration
  - Virtual host setup
  - SSL/TLS certificates (Let's Encrypt)
  - Domain configuration
  - Redirect rules
  - Security headers

#### 1.4 Post-Installation Checklist
- [ ] Database accessible
- [ ] Admin login working
- [ ] Email sending functional
- [ ] Queue workers running
- [ ] Cron jobs scheduled
- [ ] File uploads working
- [ ] PDF generation working
- [ ] SSL certificate valid
- [ ] Backups configured
- [ ] Monitoring enabled

**Configuration Files to Review:**
- `.env`
- `config/database.php`
- `config/mail.php`
- `config/queue.php`
- `config/filesystems.php`

---

### Part 2: Company Configuration (Pages 31-50)

#### 2.1 Company Profile Setup
- Company information management
- Multi-company support
- Company branding
- Logo upload and sizing
- Company-specific settings
- Fiscal year configuration
- Currency setup (MKD primary)

#### 2.2 Company Settings
- Default invoice settings
- Default payment terms
- Number sequences
- Tax settings
- Language preferences
- Timezone configuration
- Date/time formats

#### 2.3 Custom Domains (if enabled)
- DNS configuration
- SSL certificate setup
- Domain verification
- Email domain setup (DKIM, SPF, DMARC)

#### 2.4 Company Isolation
- Data segregation
- User access control
- Cross-company security
- Data export per company

**Screenshots Needed:**
- Company settings dashboard
- Fiscal year configuration
- Multi-company switcher
- Domain setup

---

### Part 3: User Management & Permissions (Pages 51-70)

#### 3.1 User Roles
- Admin role
- Manager role
- Staff role
- Accountant role
- Custom role creation
- Role hierarchy

#### 3.2 Permission System
- Granular permissions
  - View, Create, Edit, Delete
  - Per-module permissions
- Permission inheritance
- Default permission sets
- Permission auditing

#### 3.3 User Administration
- Creating users
- Password policies
  - Minimum length
  - Complexity requirements
  - Expiration policies
- User invitation system
- User deactivation
- User deletion (data retention)
- Bulk user import

#### 3.4 Two-Factor Authentication
- Enabling 2FA
- 2FA methods (TOTP, SMS)
- Backup codes
- 2FA enforcement policies
- Recovery procedures

#### 3.5 Session Management
- Session timeout configuration
- Concurrent session limits
- Force logout
- Session monitoring

**Screenshots Needed:**
- User list
- Add user form
- Role editor
- Permission matrix
- 2FA setup

---

### Part 4: Payment Gateway Configuration (Pages 71-90)

#### 4.1 Paddle Setup
- Paddle account creation
- Vendor ID configuration
- Sandbox vs production
- Webhook configuration
- Public key setup
- Testing payments
- Paddle subscription plans
- Trial period configuration

#### 4.2 CPAY Integration (Macedonian Gateway)
- CPAY merchant account
- API credentials
- Payment methods (card, bank transfer)
- Transaction fees
- Settlement schedules
- Testing in sandbox
- Production go-live checklist
- Callback URL configuration

#### 4.3 Payment Method Management
- Enabling/disabling methods
- Method ordering
- Custom payment instructions
- Fee transparency
- Refund policies

#### 4.4 Transaction Monitoring
- Transaction logs
- Failed payment alerts
- Reconciliation reports
- Chargeback handling
- Fraud prevention

**Configuration Files:**
- `config/cashier.php`
- `config/services.php` (CPAY)
- Webhook routes

**Screenshots Needed:**
- Paddle dashboard integration
- CPAY configuration
- Payment method settings
- Transaction log viewer

---

### Part 5: E-Faktura & Certificate Management (Pages 91-115)

#### 5.1 E-Faktura System Overview
- Macedonian e-invoice regulations
- UBL 2.1 standard
- Government portal integration
- Legal requirements
- Compliance timeline

#### 5.2 Certificate Management
- Certificate types supported
  - .pfx (PKCS#12)
  - .p12
  - .pem (with private key)
- Certificate authorities (CA) supported
- Certificate upload process
- Password-protected certificates
- Certificate storage security
  - Encrypted at rest
  - Access control
- Certificate validation
- Expiry monitoring
- Auto-renewal reminders

#### 5.3 Qualified Electronic Signature (QES)
- QES requirements in Macedonia
- Approved providers
- Certificate procurement
- Hardware token support (if applicable)
- Remote signing services

#### 5.4 E-Invoice Configuration
- UBL template customization
- XML namespace configuration
- Digital signature algorithms
  - RSA-SHA256
  - ECDSA
- Tax authority endpoints
- Submission retry logic
- Error handling

#### 5.5 E-Invoice Submission
- Batch submission
- Individual submission
- Submission queue
- Status tracking
- Error logs
- Resubmission procedures

#### 5.6 Compliance & Auditing
- Submission audit logs
- Signed invoice archive
- Legal retention requirements (10 years)
- Export for tax audits
- Proof of submission

**Configuration Files:**
- `config/mk.php` (Macedonian settings)
- Certificate storage path
- E-invoice queue configuration

**Screenshots Needed:**
- Certificate upload interface
- Certificate list with expiry
- E-invoice submission queue
- Submission logs
- Error detail view

---

### Part 6: Bank Integration Setup (Pages 116-135)

#### 6.1 PSD2 Banking Overview
- Open Banking (PSD2) in Macedonia
- Supported banks
  - Komercijalna Banka
  - Stopanska Banka
  - TTK Banka
  - ProCredit Bank
  - Ohridska Banka
- API providers
- Consent management

#### 6.2 Bank Connection Configuration
- OAuth 2.0 setup
- Client credentials
- Redirect URLs
- Scopes and permissions
- Consent duration

#### 6.3 Bank Feed Setup
- Automatic sync configuration
- Sync frequency (hourly, daily)
- Transaction history limits
- Transaction categorization rules
- Bank reconciliation automation

#### 6.4 Bank API Rate Limits
- Rate limit monitoring
- Throttling configuration
- Retry logic
- Error handling
- Failover procedures

#### 6.5 Bank Security
- Certificate-based authentication
- QWAC certificates
- API key rotation
- Audit logging
- Compliance monitoring

**Configuration Files:**
- `config/psd2.php`
- Bank provider credentials
- OAuth redirect URIs

**Screenshots Needed:**
- Bank provider list
- Bank connection flow
- Consent management
- Sync settings
- Rate limit dashboard

---

### Part 7: Email Configuration (Pages 136-150)

#### 7.1 SMTP Setup
- SMTP server configuration
- Authentication methods
- TLS/SSL encryption
- Port configuration (587, 465, 25)
- Connection testing

#### 7.2 Email Service Providers
- Gmail/Google Workspace
- Microsoft 365
- SendGrid
- Mailgun
- Amazon SES
- Postmark

#### 7.3 Email Templates
- Template editor
- Variable placeholders
- Multi-language templates
- Brand customization
- Preview and testing

#### 7.4 Email Queue
- Queue driver (database, Redis, SQS)
- Queue workers
- Retry logic
- Failed job handling
- Email rate limiting

#### 7.5 Email Deliverability
- SPF records
- DKIM signing
- DMARC policy
- Bounce handling
- Spam prevention
- Email reputation monitoring

#### 7.6 Email Logs
- Sent email logs
- Failed email logs
- Delivery tracking
- Open tracking (optional)
- Click tracking (optional)

**Configuration Files:**
- `config/mail.php`
- `.env` (MAIL_* variables)

**Screenshots Needed:**
- SMTP settings form
- Email template editor
- Email logs
- Queue monitoring

---

### Part 8: Queue & Background Jobs (Pages 151-165)

#### 8.1 Queue Configuration
- Queue drivers
  - Database (simple, recommended for small deployments)
  - Redis (recommended for production)
  - Amazon SQS
  - Beanstalkd
- Connection setup
- Queue naming
- Priority queues

#### 8.2 Queue Workers
- Worker processes
- Supervisor configuration
- Worker scaling
- Memory limits
- Timeout configuration
- Worker restarts

#### 8.3 Job Types
- Email sending jobs
- Invoice PDF generation
- E-invoice submission
- Bank transaction sync
- Report generation
- Data import jobs
- Backup jobs

#### 8.4 Failed Jobs
- Failed job table
- Retry mechanisms
- Manual retry
- Failed job notifications
- Dead letter queue

#### 8.5 Queue Monitoring
- Job throughput
- Queue length
- Worker status
- Job duration metrics
- Horizon dashboard (if using Redis)

**Configuration Files:**
- `config/queue.php`
- `supervisor.conf`
- Queue worker scripts

**Code Examples:**
```bash
# Start queue worker
php artisan queue:work --queue=default,high,low --tries=3

# Supervisor configuration
[program:facturino-worker]
command=php /path/to/artisan queue:work --sleep=3 --tries=3
```

**Screenshots Needed:**
- Queue dashboard
- Failed jobs list
- Worker status
- Job statistics

---

### Part 9: Backups & Disaster Recovery (Pages 166-180)

#### 9.1 Backup Strategy
- Backup types
  - Database backups
  - File storage backups
  - Configuration backups
- Backup frequency
  - Daily database backups
  - Weekly full backups
  - Hourly incremental (optional)
- Retention policies
  - Daily: 7 days
  - Weekly: 4 weeks
  - Monthly: 12 months

#### 9.2 Database Backups
- MySQL backup methods
  - mysqldump
  - Percona XtraBackup
- PostgreSQL backup methods
  - pg_dump
  - pg_basebackup
- Backup compression
- Encryption at rest
- Off-site storage
  - S3
  - DigitalOcean Spaces
  - Backblaze B2

#### 9.3 File Storage Backups
- Uploaded files (invoices, receipts, logos)
- Certificate backups
- Log file backups
- Backup verification

#### 9.4 Automated Backup Scripts
- Cron job configuration
- Backup scripts
- Notification on success/failure
- Backup monitoring
- Disk space alerts

#### 9.5 Disaster Recovery
- Recovery time objective (RTO)
- Recovery point objective (RPO)
- Restore procedures
  - Database restore
  - File restore
  - Application restore
- Testing restores
- DR runbook

#### 9.6 High Availability Setup
- Load balancing
- Database replication
- Redis clustering
- Failover procedures
- Health checks

**Backup Scripts:**
```bash
#!/bin/bash
# Database backup script
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u root -p facturino > /backups/db_$DATE.sql
gzip /backups/db_$DATE.sql
aws s3 cp /backups/db_$DATE.sql.gz s3://facturino-backups/
```

**Screenshots Needed:**
- Backup configuration
- Backup history
- Restore interface
- Disk usage monitoring

---

### Part 10: Security & Compliance (Pages 181-205)

#### 10.1 Security Hardening
- Web server hardening
  - Disable directory listing
  - Hide server version
  - Security headers (CSP, HSTS, X-Frame-Options)
- Database security
  - Strong passwords
  - Restrict remote access
  - Regular updates
- File permissions
- Environment variable protection

#### 10.2 SSL/TLS Configuration
- Certificate procurement
- Let's Encrypt automation
- Certificate renewal
- Mixed content prevention
- TLS version enforcement (TLS 1.2+)
- Cipher suite configuration

#### 10.3 Application Security
- CSRF protection
- XSS prevention
- SQL injection prevention
- Rate limiting
- Brute force protection
- File upload validation

#### 10.4 Access Control
- IP whitelisting (admin access)
- VPN requirements (optional)
- Firewall rules
- SSH key authentication
- Sudo access policies

#### 10.5 Audit Logging
- User activity logs
- Admin action logs
- Data modification logs
- Login attempts
- Failed authentication logs
- Log retention
- SIEM integration (optional)

#### 10.6 Data Encryption
- Encryption at rest
  - Database encryption
  - File encryption
- Encryption in transit
  - HTTPS everywhere
  - Database connections (TLS)
- Certificate encryption
- Sensitive field encryption

#### 10.7 GDPR Compliance
- Data protection impact assessment
- Privacy policy
- Data processing agreements
- User consent management
- Right to access
- Right to deletion
- Data portability
- Breach notification procedures

#### 10.8 Penetration Testing
- Regular security audits
- Vulnerability scanning
- Dependency updates
- Security patching
- Bug bounty program (if applicable)

**Configuration Files:**
- Nginx security headers
- Apache security headers
- `config/cors.php`
- Firewall rules (UFW, iptables)

**Screenshots Needed:**
- Audit log viewer
- Security settings
- SSL certificate status
- Firewall rules

---

### Part 11: Monitoring & Performance (Pages 206-225)

#### 11.1 Application Monitoring
- Uptime monitoring
  - UptimeRobot
  - Pingdom
  - StatusCake
- Performance monitoring
  - New Relic
  - Datadog
  - Application Insights
- Error tracking
  - Sentry
  - Bugsnag
  - Rollbar

#### 11.2 Server Monitoring
- CPU usage
- Memory usage
- Disk usage
- Network I/O
- Load averages
- Process monitoring

#### 11.3 Database Monitoring
- Query performance
- Slow query log
- Connection pool
- Replication lag
- Index optimization
- Table optimization

#### 11.4 Queue Monitoring
- Job throughput
- Queue backlog
- Worker health
- Failed jobs
- Job latency

#### 11.5 Log Management
- Log aggregation
  - ELK Stack (Elasticsearch, Logstash, Kibana)
  - Graylog
  - Papertrail
- Log rotation
- Log retention policies
- Searching logs
- Alert configuration

#### 11.6 Metrics & Dashboards
- Prometheus integration
- Grafana dashboards
- Key metrics
  - Request rate
  - Response time
  - Error rate
  - Invoice creation rate
  - Payment processing time

#### 11.7 Alerting
- Alert channels
  - Email
  - Slack
  - PagerDuty
  - SMS
- Alert thresholds
- Alert escalation
- On-call rotation

#### 11.8 Performance Optimization
- Caching strategies
  - Redis cache
  - OPcache
  - Browser caching
- Database optimization
  - Query optimization
  - Index tuning
  - Partitioning
- CDN setup
- Image optimization
- Asset compilation and minification

**Configuration Files:**
- `config/logging.php`
- `config/cache.php`
- Prometheus config
- Alert rules

**Screenshots Needed:**
- Grafana dashboard
- Error tracking dashboard
- Server metrics
- Database performance

---

### Part 12: Scaling & High Availability (Pages 226-245)

#### 12.1 Horizontal Scaling
- Load balancer setup
  - Nginx load balancing
  - HAProxy
  - AWS ALB
- Session management
  - Database sessions
  - Redis sessions
- File storage scaling
  - S3/Object storage
  - Shared NFS
- Database scaling
  - Read replicas
  - Connection pooling

#### 12.2 Vertical Scaling
- When to scale up
- Resource allocation
- Performance testing
- Benchmarking

#### 12.3 Caching Strategies
- Application cache
- Database query cache
- Full-page cache (for customer portal)
- API response cache
- Cache invalidation

#### 12.4 CDN Configuration
- CloudFlare setup
- AWS CloudFront
- Static asset delivery
- Edge caching
- Cache purging

#### 12.5 Database Optimization
- Index optimization
- Query optimization
- Connection pooling
- Read/write splitting
- Database sharding (future)

**Architecture Diagrams Needed:**
- Load balanced architecture
- High availability setup
- Caching layers
- Database replication

---

### Part 13: API Management (Pages 246-260)

#### 13.1 API Overview
- RESTful API
- API versioning
- Rate limiting
- API documentation (Swagger/OpenAPI)

#### 13.2 API Authentication
- API token generation
- OAuth 2.0 (for partners)
- API key rotation
- Token expiration
- Scopes and permissions

#### 13.3 API Endpoints
- Invoice API
- Customer API
- Payment API
- Report API
- Webhook API

#### 13.4 Webhooks
- Webhook configuration
- Event types
  - invoice.created
  - invoice.paid
  - payment.received
  - subscription.updated
- Webhook signatures
- Retry logic
- Webhook logs

#### 13.5 API Rate Limiting
- Rate limit tiers
- Throttling strategies
- Burst allowance
- Rate limit headers
- Upgrade paths

**API Documentation:**
- Interactive API docs
- Code examples (PHP, JavaScript, Python)
- Postman collection

**Screenshots Needed:**
- API token management
- Webhook configuration
- API documentation
- Rate limit dashboard

---

### Part 14: Partner Program Administration (Pages 261-275)

#### 14.1 Partner System Overview
- Partner tiers
- Commission structure
- Payout schedules
- Referral tracking

#### 14.2 Partner Onboarding
- Partner registration
- KYC verification
- Contract management
- Tax documentation

#### 14.3 Commission Management
- Commission calculation
- Recurring revenue share
- Payout processing
- Tax reporting
- Commission reports

#### 14.4 Partner Portal
- Partner dashboard
- Referral link generation
- Client management
- Earnings tracking
- Payout history

#### 14.5 Partner Support
- Partner resources
- Training materials
- Co-marketing materials
- Partner API access

**Screenshots Needed:**
- Partner dashboard (admin view)
- Commission settings
- Payout management
- KYC review interface

---

### Part 15: Maintenance & Updates (Pages 276-290)

#### 15.1 Application Updates
- Update notification system
- Backup before update
- Update process
  - Code deployment
  - Database migrations
  - Asset compilation
  - Cache clearing
- Rollback procedures
- Zero-downtime deployments

#### 15.2 Dependency Updates
- Composer updates
- NPM updates
- Security patches
- Compatibility testing

#### 15.3 Database Maintenance
- Table optimization
- Index rebuilding
- Statistics updates
- Vacuum (PostgreSQL)
- Log cleanup

#### 15.4 Scheduled Maintenance
- Maintenance windows
- User notifications
- Maintenance mode
- Status page updates

#### 15.5 Health Checks
- Application health endpoint
- Database connectivity
- Queue worker health
- External service connectivity
- Disk space checks

**Maintenance Scripts:**
```bash
# Put application in maintenance mode
php artisan down --message="Scheduled maintenance"

# Update application
git pull origin main
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm ci --production
npm run build

# Bring application back up
php artisan up
```

---

### Part 16: Troubleshooting (Pages 291-315)

#### 16.1 Common Issues

**Application Issues:**
- 500 Internal Server Error
  - Check logs: `storage/logs/laravel.log`
  - Check permissions
  - Check .env configuration
- 404 Not Found
  - Check web server configuration
  - Check route caching
- Session issues
  - Check session driver
  - Check Redis connection
- Upload failures
  - Check disk space
  - Check file permissions
  - Check upload limits (php.ini)

**Database Issues:**
- Connection timeout
  - Check database server status
  - Check credentials
  - Check firewall rules
- Slow queries
  - Enable slow query log
  - Review and optimize
  - Add indexes
- Migration errors
  - Check migration status
  - Review migration files
  - Manual intervention if needed

**Queue Issues:**
- Jobs not processing
  - Check worker status
  - Restart workers
  - Check queue connection
- Failed jobs piling up
  - Review error logs
  - Fix underlying issue
  - Retry or delete

**Email Issues:**
- Emails not sending
  - Check SMTP credentials
  - Test SMTP connection
  - Check queue workers
  - Review failed jobs
- Emails going to spam
  - Check SPF/DKIM
  - Review email content
  - Check sender reputation

**E-Invoice Issues:**
- Certificate errors
  - Verify certificate validity
  - Check password
  - Check certificate format
- Signature failures
  - Review XML structure
  - Check certificate chain
  - Test with sample XML
- Submission failures
  - Check government portal status
  - Review error messages
  - Check network connectivity

**Bank Integration Issues:**
- Connection failures
  - Check OAuth credentials
  - Renew consent
  - Check API endpoints
- Sync errors
  - Check rate limits
  - Review error logs
  - Manual resync

#### 16.2 Log Analysis
- Laravel logs
- Web server logs (Nginx, Apache)
- Database logs
- Queue logs
- System logs (syslog)

#### 16.3 Debugging Tools
- Laravel Debugbar
- Laravel Telescope
- Query logging
- Performance profiling
- Stack traces

#### 16.4 Performance Issues
- Slow page loads
  - Enable query logging
  - Check database indexes
  - Review caching
- High CPU usage
  - Check processes
  - Review queue workers
  - Optimize code
- High memory usage
  - Check for memory leaks
  - Review worker limits
  - Optimize queries
- Disk space issues
  - Review log files
  - Clean old backups
  - Optimize images

**Diagnostic Commands:**
```bash
# Check application status
php artisan about

# Check queue status
php artisan queue:work --once
php artisan queue:failed

# Check migrations
php artisan migrate:status

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Check permissions
ls -la storage/
ls -la bootstrap/cache/

# Database connection test
php artisan tinker
> DB::connection()->getPdo();
```

---

### Part 17: Advanced Configuration (Pages 316-330)

#### 17.1 Multi-Tenancy
- Tenant isolation strategies
- Database per tenant
- Shared database with tenant_id
- Performance considerations

#### 17.2 Localization
- Adding new languages
- Translation management
- RTL support
- Currency localization
- Date/time localization

#### 17.3 Custom Modules
- Module development
- Module installation
- Module updates
- Module dependencies

#### 17.4 Webhooks & Integrations
- Custom webhook handlers
- Third-party integrations
- API client development
- OAuth provider setup

#### 17.5 Reporting Customization
- Custom report development
- Report scheduling
- Report caching
- Export formats

---

### Appendices (Pages 331-350)

#### Appendix A: Configuration Reference
- Complete .env variable list
- Config file reference
- Default values

#### Appendix B: CLI Commands
- artisan command reference
- Custom commands
- Scheduled commands

#### Appendix C: Database Schema
- Entity-relationship diagrams
- Table descriptions
- Index listings
- Foreign key constraints

#### Appendix D: API Reference
- Endpoint listing
- Request/response examples
- Error codes
- Rate limits

#### Appendix E: Security Checklist
- Pre-deployment security checklist
- Post-deployment security checklist
- Monthly security review checklist
- Annual security audit checklist

#### Appendix F: Disaster Recovery Runbook
- Step-by-step recovery procedures
- Contact information
- Service dependencies
- Rollback procedures

#### Appendix G: Monitoring Checklist
- Metrics to monitor
- Alert thresholds
- Dashboard setup
- Incident response

#### Appendix H: Compliance Documentation
- GDPR compliance checklist
- Macedonian tax compliance
- E-invoice compliance
- PSD2 compliance
- PCI DSS (if applicable)

#### Appendix I: Third-Party Services
- Required services
  - Email provider
  - Payment gateways
  - SSL certificate provider
- Optional services
  - Monitoring
  - CDN
  - Backup storage
  - Error tracking

#### Appendix J: Glossary
- Technical terms
- Acronyms
- Macedonian terminology
- Industry terms

---

## Documentation Conventions

### Icons Used
- 🔧 **Configuration:** Configuration settings
- ⚙️ **Technical:** Technical details
- 🚨 **Critical:** Critical warnings
- 📊 **Metrics:** Performance metrics
- 🔒 **Security:** Security considerations
- 💾 **Backup:** Backup-related
- 🐛 **Debug:** Debugging information

### Code Blocks
- Configuration examples
- Command-line examples
- SQL queries
- API requests

### Architecture Diagrams
- System architecture
- Network diagrams
- Data flow diagrams
- Deployment diagrams

---

**End of Administrator Manual Outline**
