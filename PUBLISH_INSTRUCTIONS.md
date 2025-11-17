# Publishing Facturino to Public GitHub Repository

## Current Status: READY FOR REVIEW

This document provides step-by-step instructions for publishing the Facturino codebase to a public GitHub repository in compliance with AGPL-3.0 licensing requirements.

---

## Changes Made to Prepare for Publication

### Security Improvements

1. **Updated .gitignore** to exclude all sensitive files:
   - All `.env.*` files (except `.example` files)
   - All certificate files (`.pem`, `.key`, `.pfx`, `.p12`, `.crt`)
   - Backup files (`*.backup`)
   - Credentials files

2. **Removed Tracked Secrets** from Git:
   - `facturino-test.pfx` (certificate file)
   - `.env.backup` (contained potential secrets)
   - `.env.dev` (contained APP_KEY)
   - `.env.production` (contained APP_KEY)
   - `.env.railway` (contained APP_KEY)
   - `.env.test` (minimal, but removed for consistency)
   - `.env.testing` (contained Mailtrap test credentials)

3. **Verified Remaining Files**:
   - `.env.example` contains only placeholders - SAFE
   - `.env.staging.example` contains only placeholders - SAFE
   - `.env.error-reporting.example` contains only placeholders - SAFE
   - No hardcoded secrets found in PHP files

### AGPL-3.0 Compliance

1. **LICENSE File**: Already present with full AGPL-3.0 text ✓

2. **LEGAL_NOTES.md**: Updated with:
   - Attribution to upstream InvoiceShelf project
   - Public repository URL: https://github.com/facturino/facturino
   - List of modifications and features added
   - Third-party license acknowledgments
   - GDPR compliance information

3. **Footer Updated**: Modified customer-facing footer to include:
   - Link to InvoiceShelf upstream project
   - "View Source Code (AGPL-3.0)" link to public repository
   - Proper attribution to Bytefury

4. **README.md Updated**:
   - Removed internal/private references
   - Added proper attribution to InvoiceShelf
   - Updated repository URL to public location
   - Made license information prominent
   - Removed demo video link (was internal)

### Documentation Created

1. **PRE_PUBLISH_CHECKLIST.md**: Comprehensive checklist with:
   - Security audit items
   - AGPL compliance checks
   - Pre-push verification commands
   - Post-publish configuration steps
   - Emergency procedures

2. **PUBLISH_INSTRUCTIONS.md**: This file - step-by-step guide

---

## Pre-Publish Verification Steps

Before creating the public GitHub repository, complete these verification steps:

### 1. Review All Changes

```bash
cd /Users/tamsar/Downloads/mkaccounting

# Review staged deletions
git diff --cached

# Review modified files
git diff

# Check for any remaining tracked .env files (should only show .example files)
git ls-files | grep .env
```

**Expected**: Only `.env.example`, `.env.staging.example`, and `.env.error-reporting.example` should be listed.

### 2. Scan for Secrets (CRITICAL)

Install and run TruffleHog to scan for accidentally committed secrets:

```bash
# Using Docker (recommended)
docker pull trufflesecurity/trufflehog:latest

docker run --rm -v /Users/tamsar/Downloads/mkaccounting:/scan \
  trufflesecurity/trufflehog:latest filesystem /scan --json \
  > /tmp/trufflehog-results.json

# Review results
cat /tmp/trufflehog-results.json
```

**Action**: If any secrets are found:
- Rotate those credentials immediately
- Remove from Git history using `git filter-repo`
- Update checklist before proceeding

### 3. Verify Tests Pass

```bash
# Run full test suite
php artisan test

# Expected: All tests should pass
```

### 4. Verify Build Succeeds

```bash
# Build frontend assets
npm run build

# Expected: Build should complete without errors
```

### 5. Check for Large Files

```bash
# Find files larger than 1MB (excluding dependencies)
find . -type f -size +1M | grep -v node_modules | grep -v vendor

# Expected: No unexpected large files (databases, dumps, media, etc.)
```

---

## Creating the Public GitHub Repository

### Step 1: Create Repository on GitHub

1. Go to https://github.com/new (or your organization)
2. Configure:
   - **Repository name**: `facturino`
   - **Description**: "Macedonian-localized accounting platform based on InvoiceShelf (AGPL-3.0)"
   - **Visibility**: **Public** ⚠️
   - **Do NOT** check "Initialize this repository with a README"
   - **Do NOT** add .gitignore or license (we have our own)

3. Click "Create repository"

### Step 2: Update Git Remote

```bash
cd /Users/tamsar/Downloads/mkaccounting

# Check current remotes
git remote -v

# Current output should show:
# origin    https://github.com/bloodyteeths/mkfakturi.git (fetch)
# origin    https://github.com/bloodyteeths/mkfakturi.git (push)
# upstream  https://github.com/InvoiceShelf/InvoiceShelf.git (fetch)
# upstream  https://github.com/InvoiceShelf/InvoiceShelf.git (push)

# Rename current origin to old-private for backup
git remote rename origin old-private

# Add new public repository as origin
git remote add origin https://github.com/facturino/facturino.git

# Verify new configuration
git remote -v

# Expected output:
# old-private  https://github.com/bloodyteeths/mkfakturi.git (fetch)
# old-private  https://github.com/bloodyteeths/mkfakturi.git (push)
# origin       https://github.com/facturino/facturino.git (fetch)
# origin       https://github.com/facturino/facturino.git (push)
# upstream     https://github.com/InvoiceShelf/InvoiceShelf.git (fetch)
# upstream     https://github.com/InvoiceShelf/InvoiceShelf.git (push)
```

### Step 3: Commit Preparation Changes

```bash
# Stage all changes
git add .gitignore LEGAL_NOTES.md readme.md resources/scripts/customer/layouts/partials/TheSiteFooter.vue PRE_PUBLISH_CHECKLIST.md PUBLISH_INSTRUCTIONS.md

# Review what will be committed
git status

# Commit the preparation changes
git commit -m "Prepare repository for public release

- Update .gitignore to exclude all .env.* files and certificates
- Remove accidentally tracked secrets (.env variants, .pfx certificate)
- Update LEGAL_NOTES.md with public repository URL
- Update README.md for public consumption
- Add AGPL-3.0 source code link to customer footer
- Add pre-publish checklist and instructions

Complies with AGPL-3.0 requirements for public source distribution."
```

### Step 4: Push to Public Repository

```bash
# Push main branch to new public repository
git push -u origin main

# If you have tags, push them too
git push origin --tags
```

---

## Post-Publish Configuration

After successfully pushing to the public GitHub repository:

### 1. Configure Repository Settings

On GitHub, go to repository Settings:

**General Settings:**
- [ ] Add repository description
- [ ] Add website: `https://facturino.mk` (or your production URL)
- [ ] Add topics: `accounting`, `invoicing`, `macedonia`, `agpl`, `invoiceshelf`, `laravel`, `vue`

**Features:**
- [ ] Enable Issues
- [ ] Enable Discussions (optional, for community support)
- [ ] Disable Wikis (use docs/ folder instead)
- [ ] Disable Projects (unless needed)

**Branch Protection:**
- [ ] Go to Settings → Branches
- [ ] Add rule for `main` branch:
  - Require pull request reviews before merging
  - Require status checks to pass before merging
  - Require branches to be up to date before merging

### 2. Add Repository Files (via GitHub UI or commits)

Consider adding these files to improve the project:

```bash
# .github/ISSUE_TEMPLATE/bug_report.md
# .github/ISSUE_TEMPLATE/feature_request.md
# .github/pull_request_template.md
# CONTRIBUTING.md (if accepting contributions)
# CODE_OF_CONDUCT.md (if you want to set community standards)
```

### 3. Update Any External References

If you have any of these, update them with the new repository URL:

- [ ] Documentation websites
- [ ] Deployment configurations
- [ ] Internal wikis or notes
- [ ] Marketing materials

### 4. Verify Public Installation

Test that someone can clone and install from the public repo:

```bash
# In a separate directory
cd /tmp
git clone https://github.com/facturino/facturino.git test-install
cd test-install

# Follow installation instructions
composer install
npm install
cp .env.example .env
php artisan key:generate

# Verify tests pass
php artisan test

# Clean up
cd /tmp
rm -rf test-install
```

### 5. Announce Release

Once verified:
- [ ] Create a GitHub Release with version tag
- [ ] Write release notes
- [ ] Announce on relevant channels (if applicable)

---

## Current Git Status

As of this preparation, the following changes are staged or modified:

**Deleted (staged):**
- `.env.backup`
- `facturino-test.pfx`
- `.env.dev`
- `.env.production`
- `.env.railway`
- `.env.test`
- `.env.testing`

**Modified:**
- `.gitignore` - Enhanced security exclusions
- `LEGAL_NOTES.md` - Updated repository URL
- `readme.md` - Updated for public consumption
- `resources/scripts/customer/layouts/partials/TheSiteFooter.vue` - Added AGPL source link

**New Files:**
- `PRE_PUBLISH_CHECKLIST.md`
- `PUBLISH_INSTRUCTIONS.md` (this file)

---

## Emergency: If Secrets Are Accidentally Pushed

If you accidentally push secrets to the public repository:

### 1. Immediately Rotate All Exposed Credentials

- Database passwords
- API keys (Paddle, CPAY, AWS, etc.)
- Application keys
- SSL certificates
- Webhook secrets

### 2. Contact GitHub Support

- Go to https://support.github.com/
- Request sensitive data removal
- Provide commit SHA and file paths

### 3. Remove from Git History

```bash
# Install git-filter-repo
pip install git-filter-repo

# Remove sensitive file from all history
git filter-repo --path .env.production --invert-paths

# Force push (WARNING: Destructive operation)
git push origin main --force

# Notify all collaborators to re-clone
```

### 4. Create Security Advisory

On GitHub:
- Go to Security → Advisories
- Create new security advisory
- Notify users to rotate credentials if they cloned the repo

---

## Verification Checklist

Before proceeding with publication, verify:

- [ ] Ran TruffleHog secret scan - no secrets found
- [ ] All tests pass (`php artisan test`)
- [ ] Frontend builds successfully (`npm run build`)
- [ ] No large files present (>1MB)
- [ ] LICENSE file present with AGPL-3.0
- [ ] LEGAL_NOTES.md updated with public repo URL
- [ ] README.md suitable for public consumption
- [ ] Footer includes source code link
- [ ] No `.env` files tracked (except `.example` files)
- [ ] No certificate files tracked
- [ ] Git history reviewed for secrets
- [ ] All preparation changes committed

---

## Questions or Issues?

If you encounter any issues during publication:

1. **DO NOT** proceed with pushing to public repository
2. Review the PRE_PUBLISH_CHECKLIST.md
3. Document the issue
4. Seek human review before continuing

---

## Next Steps

1. Complete "Pre-Publish Verification Steps" above
2. Create public GitHub repository
3. Follow "Creating the Public GitHub Repository" instructions
4. Complete "Post-Publish Configuration"
5. Verify public installation works
6. Mark INFRA-LEGAL-01 task as complete

---

**Prepared by**: Claude
**Date**: 2025-11-17
**Last Updated**: 2025-11-17
**Status**: Ready for human review and execution
