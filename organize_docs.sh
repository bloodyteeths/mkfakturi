#!/bin/bash
# Documentation Organization Script
# Moves scattered documentation files into organized structure

cd /Users/tamsar/Downloads/mkaccounting

echo "📚 Starting documentation organization..."

# DEPLOYMENT FILES
echo "📦 Organizing deployment documentation..."
mv DEPLOYMENT_*.md documentation/01-deployment/ 2>/dev/null
mv PRODUCTION_*.md documentation/01-deployment/production/ 2>/dev/null
mv RAILWAY_*.md documentation/01-deployment/railway/ 2>/dev/null
mv ROLLBACK_*.md documentation/01-deployment/ 2>/dev/null
mv STAGING_*.md documentation/01-deployment/staging/ 2>/dev/null
mv PUBLISH_*.md documentation/01-deployment/ 2>/dev/null
mv PRE_PUBLISH_*.md documentation/01-deployment/ 2>/dev/null

# TESTING FILES
echo "🧪 Organizing testing documentation..."
mv QA-*.md documentation/02-testing/qa/ 2>/dev/null
mv UI_TESTING_*.md documentation/02-testing/e2e/ 2>/dev/null
mv MANUAL_QA_*.md documentation/02-testing/qa/ 2>/dev/null
mv PARTNER_TESTING_GUIDE.md documentation/02-testing/guides/ 2>/dev/null
mv INTELLIGENT_IMPORT_TESTING_GUIDE.md documentation/02-testing/guides/ 2>/dev/null
mv SMOKE_TEST_*.md documentation/02-testing/ 2>/dev/null

# DEVELOPMENT FILES
echo "💻 Organizing development documentation..."
mv AGENT_*.md documentation/03-development/agent-workflows/ 2>/dev/null
mv GEMINI.md documentation/03-development/ 2>/dev/null
mv CLAUDE.md documentation/03-development/ 2>/dev/null
mv ORCHESTRATOR_*.md documentation/03-development/ 2>/dev/null
mv REQUIREMENTS.md documentation/03-development/ 2>/dev/null
mv CODE_OF_CONDUCT.md documentation/03-development/ 2>/dev/null
mv UPGRADE.md documentation/03-development/guides/ 2>/dev/null
mv QUICK_FIX_GUIDE.md documentation/03-development/guides/ 2>/dev/null

# FEATURE FILES
echo "✨ Organizing feature documentation..."
mv FEAT-*.md documentation/04-features/ui/ 2>/dev/null
mv INTELLIGENT_IMPORT_*.md documentation/04-features/import-system/ 2>/dev/null
mv IMPORT_TYPE_*.md documentation/04-features/import-system/ 2>/dev/null
mv CSV_TYPE_*.md documentation/04-features/import-system/ 2>/dev/null
mv FISCAL_RECEIPT_*.md documentation/06-legal-compliance/fiscal/ 2>/dev/null
mv MOBILE_*.md documentation/04-features/mobile/ 2>/dev/null
mv PARTNER_*.md documentation/04-features/partner-program/ 2>/dev/null
mv SUBSCRIPTION_*.md documentation/04-features/ 2>/dev/null
mv BACKUP_SYSTEM_*.md documentation/05-infrastructure/backup/ 2>/dev/null
mv SUPPORT_TICKETING_*.md documentation/04-features/ 2>/dev/null

# INFRASTRUCTURE FILES
echo "🏗️ Organizing infrastructure documentation..."
mv INFRA-*.md documentation/05-infrastructure/ 2>/dev/null
mv INFRA_PERF_*.md documentation/05-infrastructure/performance/ 2>/dev/null
mv POST_DEPLOY_MONITORING_*.md documentation/05-infrastructure/monitoring/ 2>/dev/null

# LEGAL & COMPLIANCE
echo "⚖️ Organizing legal documentation..."
mv LEGAL_*.md documentation/06-legal-compliance/ 2>/dev/null
mv SECURITY.md documentation/06-legal-compliance/security/ 2>/dev/null

# PROJECT MANAGEMENT FILES
echo "📊 Organizing project management documentation..."
mv PHASE*.md documentation/07-project-management/reports/ 2>/dev/null
mv LAUNCH_PREP_*.md documentation/07-project-management/checklists/ 2>/dev/null
mv MERGE_READINESS_*.md documentation/07-project-management/reports/ 2>/dev/null
mv *_COMPLETION_*.md documentation/07-project-management/reports/ 2>/dev/null
mv *_EXECUTIVE_SUMMARY.md documentation/07-project-management/summaries/ 2>/dev/null
mv CHANGELOG.md documentation/07-project-management/ 2>/dev/null
mv VERSION_BUMP.md documentation/07-project-management/ 2>/dev/null
mv RELEASE_NOTES_*.md documentation/07-project-management/ 2>/dev/null
mv GITHUB_RELEASE_*.md documentation/07-project-management/ 2>/dev/null

# ARCHIVE (old/completed work)
echo "📦 Archiving old documentation..."
mv FIX_*.md documentation/08-archive/ 2>/dev/null
mv INVESTIGATION_*.md documentation/08-archive/ 2>/dev/null
mv MIGRATION_*.md documentation/08-archive/ 2>/dev/null
mv CSRF_*.md documentation/08-archive/ 2>/dev/null
mv SESSION_*.md documentation/08-archive/ 2>/dev/null
mv SALES_REPORTS_*.md documentation/08-archive/ 2>/dev/null
mv README_SALES_*.md documentation/08-archive/ 2>/dev/null
mv INVOICE_MIGRATION_*.md documentation/08-archive/ 2>/dev/null
mv IMPLEMENTATION_SUMMARY.txt documentation/08-archive/ 2>/dev/null

# SYSTEM DOCS
echo "📋 Organizing system documentation..."
mv SYSTEM_*.md documentation/ 2>/dev/null
mv DOCUMENTATION.md documentation/ 2>/dev/null
mv LOG.md documentation/ 2>/dev/null

echo "✅ Documentation organization complete!"
echo ""
echo "📁 New structure:"
echo "  documentation/"
echo "    ├── 01-deployment/"
echo "    ├── 02-testing/"
echo "    ├── 03-development/"
echo "    ├── 04-features/"
echo "    ├── 05-infrastructure/"
echo "    ├── 06-legal-compliance/"
echo "    ├── 07-project-management/"
echo "    └── 08-archive/"
