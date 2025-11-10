#!/usr/bin/env bash
# tools/sanity-check.sh
set -Eeuo pipefail

# Colors
GREEN="$(printf '\033[0;32m')"; RED="$(printf '\033[0;31m')"; YELLOW="$(printf '\033[0;33m')"; NC="$(printf '\033[0m')"

PASS=0
FAIL=0
SKIP=0

have_cmd() { command -v "$1" >/dev/null 2>&1; }

note() { printf "${YELLOW}• %s${NC}\n" "$1"; }
ok()   { printf "${GREEN}✔ %s${NC}\n" "$1"; PASS=$((PASS+1)); }
bad()  { printf "${RED}✖ %s${NC}\n" "$1"; FAIL=$((FAIL+1)); }
skip() { printf "${YELLOW}↷ %s${NC}\n" "$1"; SKIP=$((SKIP+1)); }

section() {
  printf "\n${YELLOW}== %s ==${NC}\n" "$1"
}

# ripgrep or grep fallback
RG=""; if have_cmd rg; then RG="rg -n --color=never"; elif have_cmd grep; then RG="grep -RnsI"; fi
if [ -z "$RG" ]; then
  echo "rg/grep not found. Install ripgrep or use grep."
  exit 2
fi

assert_file() {
  local path="$1"
  if [ -e "$path" ]; then ok "exists: $path"; else bad "missing: $path"; fi
}

assert_pattern_in_file() {
  local pattern="$1" file="$2" label="${3:-$pattern in $file}"
  if $RG --fixed-strings --quiet "$pattern" "$file"; then ok "$label"; else bad "$label"; fi
}

assert_pattern_in_tree() {
  local pattern="$1" tree="$2" label="${3:-$pattern in $tree}"
  if $RG --quiet "$pattern" "$tree"; then ok "$label"; else bad "$label"; fi
}

can_run_artisan() {
  if ! have_cmd php || [ ! -f artisan ]; then
    return 1
  fi
  return 0
}

artisan() {
  php artisan "$@" 2>&1
}

section "Git & build context"
if have_cmd git; then
  if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    ok "inside git repo"
  else
    bad "not a git repo"
  fi
else
  skip "git not installed; skipping"
fi

# .dockerignore includes for build inputs
assert_file ".dockerignore"
assert_pattern_in_file "!package.json" ".dockerignore" "docker includes package.json"
assert_pattern_in_file "!package-lock.json" ".dockerignore" "docker includes package-lock.json"
assert_pattern_in_file "!composer.json" ".dockerignore" "docker includes composer.json"
assert_pattern_in_file "!composer.lock" ".dockerignore" "docker includes composer.lock"
assert_pattern_in_file "!resources/" ".dockerignore" "docker includes resources/"
assert_pattern_in_file "!public/" ".dockerignore" "docker includes public/"
assert_pattern_in_file "!vite.config.js" ".dockerignore" "docker includes vite config"
assert_pattern_in_file "!tailwind.config.js" ".dockerignore" "docker includes tailwind config"

section "Migrations present"
# Phase 3–4
$RG --quiet "create_.*reconciliation" database/migrations && ok "reconciliation migration found" || bad "reconciliation migration missing"
$RG --quiet "create_.*approval_requests" database/migrations && ok "approval_requests migration found" || bad "approval_requests migration missing"
$RG --quiet "create_.*gateway_webhook_events" database/migrations && ok "gateway_webhook_events migration found" || bad "gateway_webhook_events migration missing"
$RG --quiet "create_.*export_jobs" database/migrations && ok "export_jobs migration found" || bad "export_jobs migration missing"
$RG --quiet "create_.*recurring_expenses" database/migrations && ok "recurring_expenses migration found" || bad "recurring_expenses migration missing"

# Banking tables (may already exist)
$RG --quiet "create_.*bank_providers" database/migrations && ok "bank_providers migration found" || skip "bank_providers migration not found (may pre-exist)"
$RG --quiet "create_.*bank_connections" database/migrations && ok "bank_connections migration found" || skip "bank_connections migration not found (may pre-exist)"
$RG --quiet "create_.*bank_consents" database/migrations && ok "bank_consents migration found" || skip "bank_consents migration not found (may pre-exist)"

section "Policies and observers wired"
assert_file "app/Providers/AuthServiceProvider.php"
assert_file "app/Providers/AppServiceProvider.php"

assert_pattern_in_file "ApprovalPolicy" "app/Providers/AuthServiceProvider.php" "ApprovalPolicy registered"
assert_pattern_in_tree "BankConnectionPolicy" "app/Providers" "BankConnectionPolicy registered"
assert_pattern_in_tree "ExportJobPolicy" "app/Providers" "ExportJobPolicy registered"
assert_pattern_in_tree "RecurringExpensePolicy" "app/Providers" "RecurringExpensePolicy registered"

assert_pattern_in_tree "AuditObserver" "app/Providers/AppServiceProvider.php" "AuditObserver booted"
assert_pattern_in_tree "BillObserver" "app/Providers/AppServiceProvider.php" "BillObserver booted"
assert_pattern_in_tree "BillPaymentObserver" "app/Providers/AppServiceProvider.php" "BillPaymentObserver booted"
assert_pattern_in_tree "ProformaInvoiceObserver" "app/Providers/AppServiceProvider.php" "ProformaInvoiceObserver booted"

section "Routes registered"
if can_run_artisan; then
  ROUTES="$(artisan route:list || true)"

  check_routes() {
    local keyword="$1" expected_min="$2"
    local count
    count="$(printf "%s" "$ROUTES" | $RG -c "$keyword" || true)"
    if [ "${count:-0}" -ge "$expected_min" ]; then
      ok "routes containing '$keyword' >= $expected_min (found $count)"
    else
      bad "routes containing '$keyword' expected >= $expected_min (found $count)"
    fi
  }

  check_routes "bank/oauth" 2       # start + callback
  check_routes "/bank/connections" 1
  check_routes "/bank/accounts" 2
  check_routes "reconciliation" 6
  check_routes "approvals" 6
  check_routes "/webhooks/" 4
  check_routes "/exports" 2
  check_routes "recurring-expenses" 5
else
  skip "php artisan not available; skipping route checks"
fi

section "Queue and scheduler"
assert_file ".env.example"
assert_pattern_in_file "QUEUE_CONNECTION=database" ".env.example" "QUEUE_CONNECTION=database in .env.example"

# E-invoice queue usage
assert_pattern_in_tree "->onQueue\\('einvoice'\\)" "app" "einvoice queue referenced (optional)"
# Scheduler for recurring expenses
if [ -f "app/Console/Kernel.php" ]; then
  $RG --quiet "ProcessRecurringExpenses|recurring-expenses" "app/Console/Kernel.php" \
    && ok "recurring expenses scheduled" \
    || bad "recurring expenses schedule missing in Kernel"
else
  bad "app/Console/Kernel.php missing"
fi

section "PSD2 configuration"
assert_pattern_in_file "PSD2_GATEWAY_BASE_URL" ".env.example" "PSD2_GATEWAY_BASE_URL present"
assert_pattern_in_file "PSD2_REDIRECT_URI" ".env.example" "PSD2_REDIRECT_URI present"
assert_pattern_in_tree "psd2_gateway" "config/services.php" "config/services.php contains psd2_gateway"
assert_file "services/psd2-gateway/gateway.env.example"

section "Security patterns"
# Certificate encrypted blob
assert_pattern_in_tree "encrypted_key_blob" "app" "Certificate uses encrypted_key_blob"
assert_pattern_in_tree "Crypt::encryptString|Crypt::decryptString" "app" "encryption helpers present"
# PII encryption in audit logs
assert_pattern_in_tree "encryptPii|decryptPii" "app" "PII encryption hooks present in audit logs"

section "Summary"
printf "\n${GREEN}PASS:${NC} %d  ${RED}FAIL:${NC} %d  ${YELLOW}SKIP:${NC} %d\n" "$PASS" "$FAIL" "$SKIP"
if [ "$FAIL" -gt 0 ]; then
  exit 1
fi
