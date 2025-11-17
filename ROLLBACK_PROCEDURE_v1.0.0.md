# Rollback Procedure - Facturino v1.0.0

**Version:** 1.0.0
**Date:** 2025-11-17
**Purpose:** Emergency rollback guide for v1.0.0 deployment failures

---

## When to Trigger Rollback

### IMMEDIATE Rollback Required 🚨

Trigger rollback **immediately** if any of the following occur:

1. **Complete Application Outage**
   - Application returns 500/502/503 errors for all users
   - Cannot access admin panel at all
   - Database connection completely failed

2. **Critical Security Breach**
   - Debug mode accidentally enabled (`APP_DEBUG=true`)
   - `.env` file accessible via web
   - Secrets exposed in error messages
   - SQL injection vulnerability discovered

3. **Data Loss or Corruption**
   - User data being deleted
   - Database corruption detected
   - Invoices not saving correctly
   - Financial data showing incorrect values

4. **Authentication Completely Broken**
   - No users can login (not just one user)
   - Sessions not persisting at all
   - CSRF errors on all forms

5. **Payment Processing Failures**
   - All payments failing
   - Money being charged but not recorded
   - Double-charging occurring

### CONDITIONAL Rollback (Investigate First) ⚠️

Consider rollback after investigation:

1. **High Error Rate**
   - Error rate > 10% of requests
   - Errors are increasing over time
   - Errors affect core functionality

2. **Severe Performance Degradation**
   - Response times > 10 seconds consistently
   - Application timing out frequently
   - Database queries hanging

3. **Feature-Specific Failures**
   - One major feature completely broken (e.g., all PDF generation fails)
   - Critical workflow interrupted (e.g., cannot send invoices)

### DO NOT Rollback For ⛔

These issues can be fixed with hotfixes:

1. **Minor UI Issues**
   - Button alignment off
   - Color mismatch
   - Icon missing

2. **Individual User Issues**
   - One user cannot login (might be password issue)
   - One browser has issues (might be cache)

3. **Non-Critical Feature Bugs**
   - Export to CSV broken (but Excel works)
   - Optional feature not working

4. **Performance Issues with Workaround**
   - Slow but functional
   - Can be mitigated with Redis

---

## Rollback Options

### Option 1: Railway Dashboard Rollback (FASTEST - Recommended)

**Time:** 2-3 minutes
**Risk:** Low
**Data Loss:** None

**Procedure:**

1. **Access Railway Dashboard**
   ```
   Navigate to: https://railway.app
   Login to your account
   Select Facturino project
   ```

2. **Find Last Known Good Deployment**
   ```
   Click: "Deployments" tab
   Locate: Previous successful deployment (before v1.0.0)
   Note: Deployment ID and timestamp
   ```

3. **Redeploy Previous Version**
   ```
   Click: "..." menu next to previous deployment
   Click: "Redeploy"
   Confirm: "Yes, redeploy this version"
   ```

4. **Monitor Rollback**
   ```
   Watch: Build progress
   Wait: Typically 2-3 minutes
   Verify: Status shows "Active"
   ```

5. **Verify Application**
   ```
   Test: https://your-domain.com
   Expected: Application loads correctly
   Test: Admin login works
   Test: Core features functional
   ```

**Verification Checklist:**
- [ ] Application loads
- [ ] Admin can login
- [ ] Dashboard displays
- [ ] Invoices are accessible
- [ ] No error increase in logs

---

### Option 2: Git Revert (MODERATE - Clean History)

**Time:** 5-10 minutes
**Risk:** Moderate
**Data Loss:** Possible if migrations not handled

**Procedure:**

1. **Identify Commit to Revert**
   ```bash
   # View recent commits
   git log --oneline -10

   # Find the v1.0.0 tag commit
   git show v1.0.0

   # Note the commit hash (e.g., a5178ee9)
   ```

2. **Create Revert Commit**
   ```bash
   # Revert the problematic commit
   git revert a5178ee9 --no-commit

   # Or revert the entire merge/tag
   git revert v1.0.0 --no-commit

   # Review changes
   git diff
   ```

3. **Commit the Revert**
   ```bash
   # Commit with clear message
   git commit -m "Rollback v1.0.0 - Critical deployment failure

   Reason: [DESCRIBE SPECIFIC ISSUE]

   Failures encountered:
   - [List specific failures]

   Reverting to previous stable version.

   Investigation ticket: [TICKET NUMBER]

   🚨 Emergency Rollback
   Executed by: [YOUR NAME]
   Date: $(date)"
   ```

4. **Push to Trigger Redeploy**
   ```bash
   # Push to main branch
   git push origin main

   # Railway will automatically deploy the reverted code
   ```

5. **Monitor Deployment**
   ```bash
   # Watch Railway logs
   railway logs --follow

   # Wait for: "Build successful"
   # Wait for: "Deployment active"
   ```

**Verification Checklist:**
- [ ] Git history shows revert commit
- [ ] Railway deployed new build
- [ ] Application accessible
- [ ] Previous functionality restored

---

### Option 3: Railway CLI Rollback (ADVANCED)

**Time:** 3-5 minutes
**Risk:** Low
**Prerequisites:** Railway CLI installed

**Procedure:**

1. **List Recent Deployments**
   ```bash
   # Install Railway CLI if needed
   # npm install -g @railway/cli

   # Login
   railway login

   # List deployments
   railway deployments

   # Output shows:
   # ID            STATUS    DATE         COMMIT
   # dep_abc123    ACTIVE    2 hours ago  v1.0.0 (current)
   # dep_xyz789    SUCCESS   1 day ago    Pre-v1.0.0 (rollback target)
   ```

2. **Redeploy Previous Version**
   ```bash
   # Redeploy specific deployment ID
   railway redeploy dep_xyz789

   # Confirm when prompted
   ```

3. **Monitor Rollback**
   ```bash
   # Watch logs in real-time
   railway logs --follow

   # Look for:
   # "Building..."
   # "Deployment active"
   ```

4. **Verify Status**
   ```bash
   # Check deployment status
   railway status

   # Expected: "Status: Active"
   ```

**Verification Checklist:**
- [ ] Railway CLI shows deployment succeeded
- [ ] Application accessible via browser
- [ ] Logs show no errors

---

### Option 4: Database Rollback (DANGEROUS - Last Resort)

**Time:** 10-30 minutes
**Risk:** HIGH - Potential data loss
**Use Only If:** Database migrations caused the failure

**⚠️ WARNING: This can cause DATA LOSS. Only use if:**
- Migrations are the root cause of failure
- No new production data created since deployment
- Backup is recent and verified

**Procedure:**

1. **Verify Backup Exists**
   ```bash
   # List available backups
   railway run -- php artisan backup:list

   # Or check S3 bucket (if configured)
   aws s3 ls s3://facturino-backups/

   # Ensure backup is from BEFORE v1.0.0 deployment
   ```

2. **Download Backup**
   ```bash
   # If using S3
   aws s3 cp s3://facturino-backups/latest.sql ./backup_pre_v1.0.0.sql

   # Or export current database first (safety)
   railway run -- pg_dump $DATABASE_URL > current_state_backup.sql
   ```

3. **Stop Application (Prevent Writes)**
   ```bash
   # Scale down to 0 replicas temporarily
   # (Railway Dashboard → Settings → Scale to 0)

   # Or via CLI
   railway down
   ```

4. **Restore Database**
   ```bash
   # Option A: Via Laravel Backup (if available)
   railway run -- php artisan backup:restore --latest

   # Option B: Via PostgreSQL Restore
   railway run -- psql $DATABASE_URL < backup_pre_v1.0.0.sql

   # Option C: Via Railway Database (if available)
   # Railway Dashboard → Database → Restore from backup
   ```

5. **Rollback Migrations (Alternative)**
   ```bash
   # If you don't want full restore, just rollback migrations
   railway run -- php artisan migrate:rollback --step=1

   # For specific migration (e.g., 2FA)
   railway run -- php artisan migrate:rollback --path=database/migrations/2025_11_16_233237_add_two_factor_columns_to_users_table.php
   ```

6. **Restart Application**
   ```bash
   # Scale back up
   # Railway Dashboard → Settings → Scale to 1

   # Or via CLI
   railway up
   ```

7. **Verify Restoration**
   ```bash
   # Check migration status
   railway run -- php artisan migrate:status

   # Verify data integrity
   railway run -- php artisan tinker
   >>> \DB::table('users')->count();
   >>> \DB::table('invoices')->count();
   >>> exit

   # Test application functionality
   ```

**Verification Checklist:**
- [ ] Database restored successfully
- [ ] Migration status correct
- [ ] User count matches expected
- [ ] Invoice count matches expected
- [ ] Application functional

**⚠️ Post-Restore Actions:**
1. Document any data lost between backup and rollback
2. Notify affected users if data was lost
3. Create incident report
4. Update backup frequency if needed

---

## Post-Rollback Procedures

### Immediate Actions (Within 1 Hour)

1. **Verify Application Stability**
   ```bash
   # Monitor logs for 30 minutes
   railway logs --follow | grep -E "ERROR|CRITICAL|Exception"

   # Test core functionality
   # - Login/logout
   # - Invoice creation
   # - PDF generation
   # - Email sending
   ```

2. **Notify Stakeholders**
   ```
   Subject: 🚨 Facturino v1.0.0 Deployment Rolled Back

   Team,

   The v1.0.0 deployment has been rolled back due to [SPECIFIC ISSUE].

   📍 Current Version: [Previous version]
   ⏰ Rollback Time: [TIMESTAMP]
   🔄 Status: Application restored to previous stable state

   🔍 Root Cause: [Brief description]

   ✅ Verified:
   - Application is accessible
   - Core functionality restored
   - No data loss detected

   📊 Impact:
   - Users affected: [Estimate]
   - Downtime: [Duration]
   - Features lost: v1.0.0 features temporarily unavailable

   🛠️ Next Steps:
   1. Investigate root cause
   2. Fix identified issues
   3. Test in staging
   4. Plan hotfix deployment

   Investigation ticket: [TICKET NUMBER]

   Apologies for any inconvenience.
   ```

3. **Create Incident Report**
   ```markdown
   # Incident Report: v1.0.0 Rollback

   **Date:** 2025-11-17
   **Severity:** [Critical/High/Medium]
   **Status:** Resolved (Rolled Back)

   ## Timeline
   - [TIME]: v1.0.0 deployment initiated
   - [TIME]: Issue detected - [DESCRIBE]
   - [TIME]: Rollback decision made
   - [TIME]: Rollback executed
   - [TIME]: Application verified stable

   ## Root Cause
   [Detailed description of what went wrong]

   ## Impact
   - Users affected: [Number/All]
   - Downtime: [Duration]
   - Data loss: [Yes/No - Details]

   ## Resolution
   - Rolled back to: [Previous version]
   - Method used: [Railway Dashboard/Git Revert/etc]
   - Verification: [Tests performed]

   ## Lessons Learned
   1. [What could have prevented this]
   2. [What we'll do differently]
   3. [Process improvements needed]

   ## Follow-Up Actions
   - [ ] Fix root cause issue
   - [ ] Test fix in staging
   - [ ] Create hotfix branch
   - [ ] Deploy v1.0.1 with fix
   - [ ] Update deployment checklist
   ```

4. **Preserve Evidence**
   ```bash
   # Save logs from failed deployment
   railway logs --tail=1000 > rollback_logs_$(date +%Y%m%d_%H%M%S).log

   # Save environment configuration
   railway run -- php artisan config:show > rollback_config_$(date +%Y%m%d_%H%M%S).txt

   # Save database schema (for investigation)
   railway run -- php artisan schema:dump > rollback_schema_$(date +%Y%m%d_%H%M%S).sql
   ```

### Investigation Phase (1-24 Hours)

1. **Analyze Root Cause**
   ```bash
   # Review logs for errors
   cat rollback_logs_*.log | grep -E "CRITICAL|ERROR" > critical_errors.log

   # Check for patterns
   cat critical_errors.log | cut -d']' -f3 | sort | uniq -c | sort -rn

   # Analyze specific error
   grep "specific_error_message" rollback_logs_*.log
   ```

2. **Reproduce Locally**
   ```bash
   # Checkout the failing commit
   git checkout v1.0.0

   # Try to reproduce issue
   php artisan migrate
   php artisan test
   npm run build

   # Document findings
   ```

3. **Identify Fix**
   ```bash
   # Create hotfix branch
   git checkout main
   git pull origin main
   git checkout -b hotfix/v1.0.1-[issue-description]

   # Make necessary fixes
   # ... edit files ...

   # Test thoroughly
   php artisan test
   npm run test

   # Commit with clear description
   git commit -m "Fix: [ISSUE] causing v1.0.0 rollback

   Root Cause: [DESCRIPTION]

   Changes:
   - [List specific changes]

   Testing:
   - [Tests performed]
   - [Expected vs actual behavior]

   Fixes incident: [INCIDENT NUMBER]"
   ```

### Redeployment Planning (24-48 Hours)

1. **Create Hotfix Release**
   ```bash
   # Tag hotfix version
   git tag -a v1.0.1 -m "Hotfix for v1.0.0 rollback

   Fixes:
   - [Issue 1]
   - [Issue 2]

   Testing:
   - Comprehensive test suite passed
   - Manual QA completed
   - Staging deployment verified

   Rollback incident: [INCIDENT NUMBER]"

   # Push tag (DON'T DEPLOY YET)
   git push origin hotfix/v1.0.1-[issue-description]
   git push origin v1.0.1 --tags
   ```

2. **Test in Staging**
   ```bash
   # Deploy to staging environment
   # (Assuming staging Railway service exists)

   # Run full verification checklist
   # Repeat all steps from DEPLOYMENT_VERIFICATION_CHECKLIST.md

   # Leave in staging for at least 4 hours
   # Monitor for any issues
   ```

3. **Schedule Redeployment**
   ```
   Subject: 📅 Facturino v1.0.1 Deployment Scheduled

   Team,

   Following the v1.0.0 rollback, we have prepared a hotfix release.

   📦 Version: v1.0.1
   🎯 Fixes: [List fixes]
   🧪 Testing: Completed in staging (4 hours stable)
   📅 Deployment: [DATE/TIME]

   ✅ Pre-Deployment Checklist:
   - [ ] All tests passing in staging
   - [ ] Deployment runbook updated
   - [ ] Rollback procedure tested
   - [ ] Team notified
   - [ ] Backup verified

   🚀 Deployment Plan:
   1. Create pre-deployment backup
   2. Deploy v1.0.1 to production
   3. Execute verification checklist
   4. Monitor for 24 hours

   ⏸️ If any issues: Immediate rollback to current version

   Questions or concerns? Reply to this email.
   ```

---

## Rollback Scenarios & Solutions

### Scenario 1: Session Persistence Failure

**Symptoms:**
- Users logged out after page navigation
- "Unauthenticated" errors
- Cannot stay logged in

**Immediate Rollback:** ✅ Yes (if affects all users)

**Quick Fix (If Caught Early):**
```bash
# Update environment variable
railway variables set SESSION_DRIVER=database

# Redeploy
railway up

# Verify
railway run -- php artisan config:show session.driver
```

**Root Cause:** `SESSION_DRIVER` not set to `database`

---

### Scenario 2: Migration Failure

**Symptoms:**
- Application returns 500 errors
- Database queries failing
- Missing table/column errors

**Immediate Rollback:** ✅ Yes (if database corrupted)

**Quick Fix (If Safe):**
```bash
# Run migrations manually
railway run -- php artisan migrate --force

# Check status
railway run -- php artisan migrate:status
```

**Dangerous Fix (Last Resort):**
```bash
# Rollback specific migration
railway run -- php artisan migrate:rollback --step=1

# Verify
railway run -- php artisan migrate:status
```

**Root Cause:** Migration not tested properly, missing column, constraint violation

---

### Scenario 3: Environment Variable Misconfiguration

**Symptoms:**
- Application loads but features broken
- Database connection works but cache doesn't
- Specific feature failing

**Immediate Rollback:** ⚠️ Maybe (if critical feature)

**Quick Fix:**
```bash
# Update specific variable
railway variables set CACHE_STORE=database
railway variables set QUEUE_CONNECTION=database

# Restart application (no redeploy needed)
railway restart

# Verify
railway run -- php artisan config:clear
railway run -- php artisan config:cache
```

**Root Cause:** Missing or incorrect environment variables

---

### Scenario 4: Memory/Resource Exhaustion

**Symptoms:**
- Slow response times
- 503 Service Unavailable
- Application crashes randomly

**Immediate Rollback:** ⚠️ Maybe (if severe)

**Quick Fix:**
```bash
# Scale up resources via Railway Dashboard
# Settings → Resources → Increase memory/CPU

# Or via CLI (if supported)
railway scale --memory 1024 --cpu 2

# Monitor improvement
railway logs --follow
```

**Root Cause:** Insufficient resources, memory leak, N+1 queries

---

## Communication Templates

### Internal Team Alert

```
🚨 URGENT: v1.0.0 Rollback In Progress

Status: Rolling back now
Reason: [SPECIFIC ISSUE]
ETA: 5 minutes
Impact: [DESCRIPTION]

DO NOT:
- Deploy any changes
- Modify database
- Change environment variables

STANDBY:
- DevOps team monitoring
- Updates every 10 minutes

[YOUR NAME]
[TIMESTAMP]
```

### User Communication (If Needed)

```
Subject: 🔧 Brief Maintenance - Service Restored

Dear Facturino Users,

We experienced a brief technical issue today and have taken corrective action. Your service is now fully restored.

⏰ Impact Duration: [X] minutes
✅ Current Status: Fully operational
📊 Data: All your data is safe and secure

What happened?
[Brief, non-technical explanation]

What we did:
Restored to our previous stable version while we fix the issue.

What this means for you:
Everything is working normally. No action required on your part.

Apologies for any inconvenience.

The Facturino Team
```

---

## Rollback Checklist

### Pre-Rollback
- [ ] Confirm rollback trigger criteria met
- [ ] Notify team of rollback decision
- [ ] Identify target version to rollback to
- [ ] Create backup of current state (if possible)
- [ ] Pause any automated deployments

### During Rollback
- [ ] Execute chosen rollback method
- [ ] Monitor deployment progress
- [ ] Watch for errors in logs
- [ ] Verify application accessibility
- [ ] Test core functionality

### Post-Rollback
- [ ] Confirm application stability
- [ ] Notify stakeholders
- [ ] Create incident report
- [ ] Preserve evidence (logs, configs)
- [ ] Document root cause
- [ ] Plan fix and redeployment

### Follow-Up
- [ ] Investigate thoroughly
- [ ] Create hotfix branch
- [ ] Test fix in staging
- [ ] Update deployment procedures
- [ ] Schedule redeployment
- [ ] Conduct post-mortem

---

## Emergency Contacts

**DevOps Lead:** [Contact]
**Technical Director:** [Contact]
**CTO:** [Contact]

**External Support:**
- Railway Support: https://railway.app/help
- Database Admin: [Contact]

**Escalation Path:**
1. Execute rollback (DevOps Lead - 0-15 minutes)
2. Notify Technical Director (15-30 minutes)
3. Escalate to CTO (30+ minutes or data loss)

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-17
**Next Review:** After any rollback or before v1.1.0

🤖 **Generated with Claude Code**
**Co-Authored-By: Claude <noreply@anthropic.com>**
