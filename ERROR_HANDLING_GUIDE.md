# Error Handling and Logging Guide

This application implements a comprehensive error handling and logging system designed to provide excellent user experience while maintaining detailed system monitoring capabilities.

## Overview

The error handling system consists of:

1. **Custom Exception Classes** - Specialized exceptions for different error types
2. **Comprehensive Error Pages** - User-friendly error pages for all HTTP error codes
3. **Advanced Logging** - Multi-channel logging with context and structured data
4. **Real-time Notifications** - Slack and email alerts for critical errors
5. **API Error Responses** - Standardized JSON error responses for API endpoints

## Exception Classes

### MigrationException
Handles errors during data import/migration processes:
- File upload errors
- Parsing errors
- Field mapping errors
- Data validation errors
- Transformation errors
- Database commit errors

### BusinessLogicException
Handles application business rule violations:
- Invoice generation errors
- Payment processing errors
- Tax calculation errors
- Permission errors
- Resource limit errors
- State transition errors

### SystemException
Handles system-level errors:
- Database connection errors
- File system errors
- Memory exhaustion
- Third-party service failures
- Configuration errors
- Security violations

### ValidationException (Enhanced)
Handles detailed validation errors:
- Required field errors
- Format validation errors
- Range validation errors
- Custom rule violations

## Error Pages

The application provides user-friendly error pages for all major HTTP status codes:

- **401 Unauthorized** - Includes inline login form
- **403 Forbidden** - Shows permission information and contact details
- **404 Not Found** - Provides navigation help and support links
- **405 Method Not Allowed** - Explains allowed methods
- **419 CSRF Token Mismatch** - Explains session expiration with auto-refresh
- **422 Validation Error** - Shows detailed validation errors with helpful tips
- **429 Too Many Requests** - Includes countdown timer and rate limit info
- **500 Internal Server Error** - Provides troubleshooting steps and contact info
- **503 Service Unavailable** - Shows maintenance information with auto-refresh
- **Migration Errors** - Specialized page for import/migration failures
- **Business Logic Errors** - Context-aware business rule violation explanations

## Logging Channels

The system uses specialized logging channels for different types of events:

### Standard Channels
- `stack` - Default multi-channel logging
- `single` - Single file logging
- `daily` - Daily rotating logs
- `stderr` - Standard error output

### Specialized Channels
- `critical` - Critical system errors (30-day retention)
- `security` - Security-related events (90-day retention)
- `audit` - Audit trail for compliance (365-day retention)
- `migration` - Data import/migration logs (30-day retention)
- `payment` - Financial transaction logs (365-day retention)
- `api` - API request/response logs (30-day retention)
- `performance` - Performance monitoring (7-day retention)
- `user_activity` - User action tracking (30-day retention)
- `business_logic` - Business rule violations (30-day retention)
- `queue` - Background job logs (14-day retention)
- `database` - Database-related errors (7-day retention)

### Monitoring Integration
- `slack_critical` - Slack notifications for critical errors
- `slack_errors` - Slack notifications for general errors
- `email_critical` - Email notifications for critical errors
- `sentry` - Sentry error tracking integration
- `datadog` - DataDog log aggregation

## Configuration

### Environment Setup

1. Copy `.env.error-reporting.example` to understand available options
2. Configure notification channels:
   ```env
   LOG_SLACK_CRITICAL_WEBHOOK_URL=https://hooks.slack.com/services/...
   LOG_EMAIL_RECIPIENTS=admin@example.com,dev@example.com
   SENTRY_LARAVEL_DSN=https://your-dsn@sentry.io/project-id
   ```

3. Choose appropriate log channel for environment:
   ```env
   # Development
   LOG_CHANNEL=stack
   
   # Staging
   LOG_CHANNEL=staging
   
   # Production
   LOG_CHANNEL=production_with_monitoring
   ```

### Production Recommendations

- Use `LOG_CHANNEL=production_with_monitoring`
- Set `LOG_LEVEL=warning` to reduce noise
- Configure Slack and email notifications
- Enable Sentry integration
- Set `APP_DEBUG=false`
- Keep logs for appropriate retention periods

## Usage Examples

### Throwing Custom Exceptions

```php
// Business logic error
throw BusinessLogicException::invoiceGenerationError(
    'Customer address is incomplete',
    ['customer_id' => $customerId, 'missing_fields' => ['street', 'city']]
);

// System error
throw SystemException::databaseConnectionError(
    'Connection timeout after 30 seconds',
    ['host' => config('database.connections.mysql.host')]
);

// Migration error
throw MigrationException::fileParsingError(
    'Invalid CSV format on line 15',
    $importJobId,
    ['Fix column headers', 'Remove empty rows']
);

// Validation error
throw ValidationException::requiredFieldError(
    'email',
    ['form' => 'user_registration']
);
```

### Logging Best Practices

```php
// Log to specific channels
Log::channel('security')->warning('Failed login attempt', [
    'ip' => request()->ip(),
    'email' => $email,
    'user_agent' => request()->userAgent()
]);

Log::channel('audit')->info('Invoice created', [
    'invoice_id' => $invoice->id,
    'user_id' => auth()->id(),
    'amount' => $invoice->total
]);

Log::channel('performance')->warning('Slow query detected', [
    'query' => $query,
    'execution_time' => $executionTime,
    'threshold' => 1000
]);
```

## API Error Responses

All API endpoints return standardized error responses:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The provided data failed validation.",
    "type": "ValidationException"
  },
  "meta": {
    "timestamp": "2023-07-26T10:30:00Z",
    "request_id": "uuid-string"
  }
}
```

For validation errors, additional details are included:

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "The provided data failed validation.",
    "validation_errors": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

## Monitoring and Alerts

### Critical Error Alerts

Critical errors automatically trigger:
1. Slack notification to configured channel
2. Email notification to admin team
3. Sentry error tracking (if configured)
4. Detailed logging with full context

### Error Metrics

Monitor these key metrics:
- Error rate by type and endpoint
- Critical error frequency
- User-facing vs system errors
- Response time impact
- Recovery time for system errors

### Log Analysis Queries

Common log analysis patterns:

```bash
# Find all critical errors in the last hour
grep "CRITICAL" storage/logs/critical.log | grep "$(date '+%Y-%m-%d %H')"

# Count errors by type
grep "exception_class" storage/logs/laravel.log | cut -d'"' -f4 | sort | uniq -c

# Find slow queries
grep "Slow query" storage/logs/performance.log
```

## Security Considerations

- Sensitive data is automatically redacted from logs
- Error messages are sanitized in production
- User sessions are tracked for security analysis
- Failed authentication attempts are logged
- Rate limiting prevents abuse

## Testing

The error handling system includes comprehensive tests:

```bash
# Run error handling tests
php artisan test --filter=ErrorHandling

# Test error pages
php artisan test --filter=ErrorPages

# Test logging functionality  
php artisan test --filter=LoggingTest
```

## Troubleshooting

### Common Issues

1. **Slack notifications not working**
   - Verify webhook URL is correct
   - Check Slack app permissions
   - Review logs for notification failures

2. **Email notifications not sending**
   - Verify mail configuration
   - Check recipient email addresses
   - Ensure mail queue is processing

3. **Logs not being written**
   - Check file permissions on storage/logs
   - Verify log channel configuration
   - Review LOG_LEVEL setting

4. **High error rates**
   - Review application logs for patterns
   - Check system resource usage
   - Monitor third-party service status

### Emergency Procedures

For critical system errors:

1. Check status of all dependencies
2. Review recent deployments
3. Monitor system resources
4. Check external service status
5. Implement emergency fixes
6. Communicate with stakeholders

## Maintenance

### Log Rotation

Logs are automatically rotated based on retention policies. Monitor disk usage and adjust retention periods as needed.

### Performance Impact

The logging system is designed for minimal performance impact:
- Asynchronous logging where possible
- Efficient data serialization
- Configurable log levels
- Channel-specific filtering

### Regular Tasks

- Review error patterns weekly
- Update notification channels as team changes
- Test error page functionality
- Validate backup and recovery procedures
- Update documentation for new error types

This comprehensive error handling system ensures reliable application operation while providing excellent visibility into system health and user experience.