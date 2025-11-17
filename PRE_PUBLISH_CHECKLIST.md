# Pre-Publish Checklist for Public GitHub Repository

This checklist ensures compliance with AGPL-3.0 and prevents accidental exposure of secrets before publishing to a public GitHub repository.

## Security Audit

### 1. Environment Files & Secrets
- [x] `.env` is in `.gitignore`
- [x] `.env.example` contains no real secrets (only placeholders)
- [x] All `.env.*` variants checked for actual API keys/passwords
- [x] `.env.backup` removed from git tracking
- [x] Updated `.gitignore` to exclude `*.backup` files

### 2. Certificates & Keys
- [x] All `.pem`, `.key`, `.pfx`, `.p12` files excluded in `.gitignore`
- [x] `facturino-test.pfx` removed from git tracking
- [x] No production certificates in repository
- [x] Storage certificates directory is empty or contains only test certs

### 3. Credentials & Tokens
- [ ] No API keys hardcoded in source files
- [ ] No database passwords in code
- [ ] No OAuth secrets in code
- [ ] No webhook secrets hardcoded

**Action Required:** Run the following command to scan for secrets:
```bash
# Install truffleHog if not already installed
docker pull trufflesecurity/trufflehog:latest

# Scan entire repository
docker run --rm -v /Users/tamsar/Downloads/mkaccounting:/scan trufflesecurity/trufflehog:latest filesystem /scan --json
```

### 4. Database & Migrations
- [x] No production database dumps in repository
- [x] Migration files contain no sensitive data
- [x] Seed files use fake data only

## AGPL-3.0 Compliance

### 5. License Files
- [x] `LICENSE` file present with full AGPL-3.0 text
- [x] `LEGAL_NOTES.md` created with:
  - [x] Upstream attribution to InvoiceShelf
  - [x] Link to public source repository
  - [x] List of modifications
  - [x] Third-party license acknowledgments

### 6. Source Code Attribution
- [x] Original InvoiceShelf copyright headers preserved
- [x] Footer updated with:
  - [x] Link to InvoiceShelf upstream
  - [x] Link to public source repository
  - [x] AGPL-3.0 license notice

### 7. Copyright Headers
- [ ] New files have appropriate copyright headers
- [x] Modified files marked with "FACTURINO MODIFICATION" comments (where applicable)

## Documentation

### 8. README.md
- [x] Updated for public consumption
- [x] Removed internal/private references
- [x] Clear installation instructions
- [x] Attribution to upstream project
- [x] License information prominent
- [x] Repository URL updated to public location

### 9. Public Documentation
- [x] Installation guide is complete
- [x] No internal hostnames or IPs
- [x] No internal team references
- [ ] API documentation generated (run `php artisan scribe:generate` if needed)

## Repository Cleanup

### 10. Tracked Files Review
- [x] No `.DS_Store` files tracked
- [x] No `node_modules` tracked (in `.gitignore`)
- [x] No `vendor` tracked (in `.gitignore`)
- [x] No build artifacts tracked

### 11. Git History
- [ ] Review recent commits for accidentally committed secrets
- [ ] Consider using `git-filter-repo` if secrets found in history
- [ ] No merge conflicts or placeholder commits

### 12. Submodules & Dependencies
- [x] No private Git submodules
- [x] All dependencies are publicly available
- [x] `composer.json` contains no private repositories

## Pre-Push Verification

### 13. Final Checks Before Creating Public Repo
```bash
# 1. Verify .gitignore is working
git status --ignored

# 2. Verify no secrets in tracked files
git grep -i "password\|secret\|api_key\|token" -- '*.env.*' ':!*.example'

# 3. Verify all tests pass
php artisan test

# 4. Verify build succeeds
npm run build

# 5. Check for large files (>1MB)
find . -type f -size +1M | grep -v node_modules | grep -v vendor

# 6. Review all tracked .env variants
git ls-files | grep .env
```

## Creating the Public Repository

### 14. GitHub Repository Setup

1. **Create new public repository on GitHub:**
   - Repository name: `facturino`
   - Organization/Owner: `facturino` (or your GitHub username)
   - Description: "Macedonian-localized accounting platform based on InvoiceShelf"
   - **Public** visibility
   - Do NOT initialize with README (we have our own)

2. **Update git remote:**
```bash
# Check current remotes
git remote -v

# Remove existing origin (private repo)
git remote remove origin

# Add new public repository
git remote add origin https://github.com/facturino/facturino.git

# Verify upstream is still InvoiceShelf
git remote -v
# Should show:
# origin    https://github.com/facturino/facturino.git (fetch)
# origin    https://github.com/facturino/facturino.git (push)
# upstream  https://github.com/InvoiceShelf/InvoiceShelf.git (fetch)
# upstream  https://github.com/InvoiceShelf/InvoiceShelf.git (push)
```

3. **Push to public repository:**
```bash
# Push main branch
git push -u origin main

# Push all tags (if any)
git push --tags
```

### 15. Post-Publish Configuration

After publishing to public GitHub:

1. **Repository Settings:**
   - Enable Issues
   - Enable Discussions (optional)
   - Configure branch protection for `main`
   - Add repository description
   - Add topics: `accounting`, `invoicing`, `macedonia`, `agpl`, `invoiceshelf`

2. **Add Repository Files via GitHub UI:**
   - `CONTRIBUTING.md` (if applicable)
   - Code of Conduct (if applicable)
   - GitHub Issue templates
   - Pull Request template

3. **Update README badges** (optional):
   - License badge
   - Build status
   - Latest release

4. **Notify stakeholders:**
   - Update any documentation referencing the repository
   - Update LEGAL_NOTES.md if repository URL changed
   - Announce on relevant channels

## Final Verification

### 16. Post-Publish Checks

After pushing to public repository:

```bash
# 1. Clone from public repo to verify
cd /tmp
git clone https://github.com/facturino/facturino.git test-clone
cd test-clone

# 2. Verify no secrets present
grep -r "password\|secret\|api.*key" --include="*.php" --include="*.env*" --exclude="*.example"

# 3. Verify installation works
composer install
npm install
cp .env.example .env
php artisan key:generate

# 4. Verify tests pass
php artisan test

# 5. Clean up
cd .. && rm -rf test-clone
```

## Emergency Procedures

### If Secrets Are Accidentally Pushed

1. **Immediately rotate all exposed credentials:**
   - API keys
   - Database passwords
   - Webhook secrets
   - SSL certificates

2. **Remove from Git history:**
```bash
# Use git-filter-repo (recommended) or BFG Repo-Cleaner
git filter-repo --path-glob '*.env' --invert-paths
git push --force --all
```

3. **Notify users** via GitHub Security Advisory

4. **Review and update** this checklist to prevent recurrence

---

## Completion Sign-off

- [ ] All security checks passed
- [ ] All AGPL compliance items verified
- [ ] Documentation updated
- [ ] Repository cleaned
- [ ] Pre-push verification completed
- [ ] Ready to create public GitHub repository

**Prepared by:** Claude
**Date:** 2025-11-17
**Review Required:** Yes - Manual human review before publishing
