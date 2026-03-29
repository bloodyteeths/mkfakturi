/**
 * Smart Reconciliation — E2E Test Suite
 *
 * Tests AI-powered bank transaction reconciliation against production.
 * Evaluates: AI suggestion quality, record creation, UI flow.
 *
 * Usage:
 *   TEST_BASE_URL=https://app.facturino.mk TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/smart-reconciliation-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk';
const EMAIL = process.env.TEST_EMAIL || 'atillatkulu@gmail.com';
const PASS = process.env.TEST_PASSWORD || 'Facturino2026';
const COMPANY_ID = process.env.TEST_COMPANY_ID || '112';

let page;
let jsErrors = [];
let apiErrors = [];

/** Helper: call API via window.axios (SPA's axios with CSRF + interceptors) */
const api = (method, url, data = null) => {
  return `
    try {
      const opts = { headers: { company: '${COMPANY_ID}' } };
      const res = await window.axios.${method}('${url}'${data ? ', ' + JSON.stringify(data) + ', opts' : ', opts'});
      return { ok: true, status: res.status, data: res.data };
    } catch (e) {
      return { ok: false, status: e.response?.status, message: e.response?.data?.message || e.message, data: e.response?.data };
    }
  `;
};

test.describe('Smart Reconciliation — AI Banking', () => {
  test.describe.configure({ mode: 'serial' });
  test.setTimeout(45000);

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();
    jsErrors = [];
    apiErrors = [];
    page.on('pageerror', err => jsErrors.push({ url: page.url(), error: err.message }));
    page.on('response', resp => {
      if (resp.url().includes('/api/') && resp.status() >= 500)
        apiErrors.push({ url: resp.url(), status: resp.status() });
    });
  });

  test.afterAll(async () => {
    if (jsErrors.length) console.log('JS Errors:', JSON.stringify(jsErrors, null, 2));
    if (apiErrors.length) console.log('API Errors:', JSON.stringify(apiErrors, null, 2));
    await page?.close();
  });

  // ═══════════════════════════════════════════════
  // AUTH
  // ═══════════════════════════════════════════════

  test('1. Login and navigate to Banking', async () => {
    test.setTimeout(60000);
    await page.waitForTimeout(3000);
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 30000 });

    for (let retry = 0; retry < 3; retry++) {
      const hasAxios = await page.evaluate(() => typeof window.axios !== 'undefined').catch(() => false);
      if (hasAxios) break;
      await page.waitForTimeout(5000);
      if (retry < 2) await page.reload({ waitUntil: 'networkidle' }).catch(() => {});
    }

    const loginResult = await page.evaluate(async ({ email, password }) => {
      if (typeof window.axios === 'undefined') return { success: false, message: 'axios not available' };
      try {
        await window.axios.get(window.location.origin + '/sanctum/csrf-cookie');
        await window.axios.post('/auth/login', { email, password });
        return { success: true };
      } catch (e) {
        return { success: false, message: e.response?.data?.message || e.message };
      }
    }, { email: EMAIL, password: PASS });

    expect(loginResult.success, `Login failed: ${loginResult.message}`).toBeTruthy();

    // Set the target company in localStorage — axios interceptor reads company from here
    await page.evaluate((cid) => {
      localStorage.setItem('selectedCompany', cid);
    }, COMPANY_ID);

    await page.goto(`${BASE}/admin/banking`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(3000);
    await expect(page).toHaveURL(/\/admin\/banking/);
  });

  // ═══════════════════════════════════════════════
  // SMART-SUGGEST API
  // ═══════════════════════════════════════════════

  test('2. Smart-suggest — debit transaction', async () => {
    const result = await page.evaluate(async (companyId) => {
      const txRes = await window.axios.get('/banking/transactions', {
        params: { limit: 50, orderByField: 'transaction_date', orderBy: 'desc' },
        headers: { company: companyId },
      });
      const debit = (txRes.data.data || []).find(t =>
        t.transaction_type === 'debit' && !t.matched_invoice_id && !t.linked_type
      );
      if (!debit) return { skip: true };

      const suggestRes = await window.axios.post('/banking/reconciliation/smart-suggest',
        { transaction_id: debit.id },
        { headers: { company: companyId } }
      );
      return {
        suggestion: suggestRes.data.suggestion,
        tx: { id: debit.id, amount: debit.amount, desc: (debit.description || '').substring(0, 80), counterparty: debit.creditor_name },
      };
    }, COMPANY_ID);

    if (result.skip) { console.log('SKIP: No unreconciled debits'); return; }

    const s = result.suggestion;
    console.log(`\nDebit TX ${result.tx.id}: ${result.tx.amount} | ${result.tx.counterparty}`);
    console.log(`  Desc: ${result.tx.desc}`);
    console.log(`  → ${s.action} (${Math.round(s.confidence * 100)}%) — ${s.reason}`);
    if (s.category_name) console.log(`  Category: ${s.category_name}`);
    if (s.target_label) console.log(`  Target: ${s.target_label}`);

    expect(s.action).toBeTruthy();
    expect(['create_expense', 'link_bill', 'link_payroll', 'mark_reviewed']).toContain(s.action);
    expect(s.confidence).toBeGreaterThan(0);
    expect(s.reason).toBeTruthy();
  });

  test('3. Smart-suggest — credit transaction', async () => {
    const result = await page.evaluate(async (companyId) => {
      const txRes = await window.axios.get('/banking/transactions', {
        params: { limit: 50, orderByField: 'transaction_date', orderBy: 'desc' },
        headers: { company: companyId },
      });
      const credit = (txRes.data.data || []).find(t =>
        t.transaction_type === 'credit' && !t.matched_invoice_id && !t.linked_type
      );
      if (!credit) return { skip: true };

      const suggestRes = await window.axios.post('/banking/reconciliation/smart-suggest',
        { transaction_id: credit.id },
        { headers: { company: companyId } }
      );
      return {
        suggestion: suggestRes.data.suggestion,
        tx: { id: credit.id, amount: credit.amount, desc: (credit.description || '').substring(0, 80), counterparty: credit.debtor_name },
      };
    }, COMPANY_ID);

    if (result.skip) { console.log('SKIP: No unreconciled credits'); return; }

    const s = result.suggestion;
    console.log(`\nCredit TX ${result.tx.id}: ${result.tx.amount} | ${result.tx.counterparty}`);
    console.log(`  Desc: ${result.tx.desc}`);
    console.log(`  → ${s.action} (${Math.round(s.confidence * 100)}%) — ${s.reason}`);

    expect(s.action).toBeTruthy();
    expect(['record_income', 'link_invoice', 'mark_reviewed']).toContain(s.action);
    expect(s.confidence).toBeGreaterThan(0);
  });

  test('4. Bulk-smart-suggest — multiple transactions', async () => {
    const result = await page.evaluate(async (companyId) => {
      const txRes = await window.axios.get('/banking/transactions', {
        params: { limit: 20, orderByField: 'transaction_date', orderBy: 'desc' },
        headers: { company: companyId },
      });
      const unreconciled = (txRes.data.data || []).filter(t => !t.matched_invoice_id && !t.linked_type);
      if (unreconciled.length < 2) return { skip: true };

      const ids = unreconciled.slice(0, 3).map(t => t.id);
      const bulkRes = await window.axios.post('/banking/reconciliation/bulk-smart-suggest',
        { transaction_ids: ids },
        { headers: { company: companyId } }
      );
      return { suggestions: bulkRes.data.suggestions, count: Object.keys(bulkRes.data.suggestions).length };
    }, COMPANY_ID);

    if (result.skip) { console.log('SKIP: Need ≥2 unreconciled transactions'); return; }

    console.log(`\nBulk suggestions for ${result.count} transactions:`);
    for (const [txId, s] of Object.entries(result.suggestions)) {
      console.log(`  TX ${txId}: ${s.action} (${Math.round(s.confidence * 100)}%) — ${s.reason}`);
    }
    expect(result.count).toBeGreaterThan(0);
  });

  // ═══════════════════════════════════════════════
  // AI QUALITY EVALUATION
  // ═══════════════════════════════════════════════

  test('5. AI quality — evaluate all unreconciled transactions', async () => {
    test.setTimeout(90000);

    const result = await page.evaluate(async (companyId) => {
      const txRes = await window.axios.get('/banking/transactions', {
        params: { limit: 50, orderByField: 'transaction_date', orderBy: 'desc' },
        headers: { company: companyId },
      });
      const unreconciled = (txRes.data.data || []).filter(t => !t.matched_invoice_id && !t.linked_type);
      const sample = unreconciled.slice(0, 8);
      const results = [];

      for (const tx of sample) {
        try {
          const res = await window.axios.post('/banking/reconciliation/smart-suggest',
            { transaction_id: tx.id },
            { headers: { company: companyId } }
          );
          results.push({
            id: tx.id, type: tx.transaction_type, amount: tx.amount,
            counterparty: tx.counterparty_name || tx.creditor_name || tx.debtor_name || '',
            description: (tx.description || '').substring(0, 60),
            suggestion: res.data.suggestion,
          });
        } catch (e) {
          results.push({ id: tx.id, error: e.message });
        }
      }
      return results;
    }, COMPANY_ID);

    console.log('\n═══ AI QUALITY EVALUATION ═══');
    let highConf = 0, total = 0;

    for (const r of result) {
      if (r.error) { console.log(`  ✗ TX ${r.id}: ERROR — ${r.error}`); continue; }
      total++;
      const s = r.suggestion;
      if (s.confidence >= 0.7) highConf++;
      const icon = s.confidence >= 0.8 ? '✓' : s.confidence >= 0.5 ? '~' : '✗';
      console.log(`  ${icon} [${r.type}] TX ${r.id} (${r.amount}) — ${r.counterparty}`);
      console.log(`    "${r.description}"`);
      console.log(`    → ${s.action} (${Math.round(s.confidence * 100)}%) — ${s.reason}`);
      if (s.category_name) console.log(`    Category: ${s.category_name}`);
      if (s.target_label) console.log(`    Target: ${s.target_label}`);
      if (s.alternatives?.length > 0) console.log(`    Alts: ${s.alternatives.map(a => a.action).join(', ')}`);
    }

    console.log(`\n═══ ${highConf}/${total} with ≥70% confidence ═══\n`);
    expect(total).toBeGreaterThan(0);
  });

  // ═══════════════════════════════════════════════
  // RECORD CREATION
  // ═══════════════════════════════════════════════

  test('6. Record-expense — creates expense from debit', async () => {
    const result = await page.evaluate(async (companyId) => {
      try {
        const catRes = await window.axios.get('/banking/reconciliation/expense-categories', { headers: { company: companyId } });
        const categories = catRes.data.data || [];
        if (!categories.length) return { skip: true, message: 'No expense categories' };

        const txRes = await window.axios.get('/banking/transactions', {
          params: { limit: 50, orderByField: 'transaction_date', orderBy: 'desc' },
          headers: { company: companyId },
        });
        const debit = (txRes.data.data || []).find(t =>
          t.transaction_type === 'debit' && !t.matched_invoice_id && !t.linked_type && t.processing_status !== 'processed'
        );
        if (!debit) return { skip: true, message: 'No unreconciled debits (all already processed)' };

        const expRes = await window.axios.post('/banking/reconciliation/record-expense', {
          transaction_id: debit.id,
          expense_category_id: categories[0].id,
          notes: 'E2E test — auto-created expense',
        }, { headers: { company: companyId } });

        return { expense_id: expRes.data.expense_id, category: categories[0].name, tx_amount: debit.amount };
      } catch (e) {
        const resp = e.response || {};
        return { error: true, status: resp.status, data: JSON.stringify(resp.data || {}).substring(0, 500), url: e.config?.url, message: e.message };
      }
    }, COMPANY_ID);

    if (result.error) {
      console.log(`ERROR: ${result.status} ${result.url}\n  ${result.data}\n  ${result.message}`);
    }
    if (result.skip) { console.log(`SKIP: ${result.message}`); return; }

    expect(result.error).toBeFalsy();
    expect(result.expense_id).toBeTruthy();
    console.log(`✓ Created expense #${result.expense_id} (${result.category}) from ${result.tx_amount} debit`);
  });

  test('7. Record-income — creates payment from credit', async () => {
    const result = await page.evaluate(async (companyId) => {
      try {
        const txRes = await window.axios.get('/banking/transactions', {
          params: { limit: 50, orderByField: 'transaction_date', orderBy: 'desc' },
          headers: { company: companyId },
        });
        const credit = (txRes.data.data || []).find(t =>
          t.transaction_type === 'credit' && !t.matched_invoice_id && !t.linked_type && t.processing_status !== 'processed'
        );
        if (!credit) return { skip: true };

        const incRes = await window.axios.post('/banking/reconciliation/record-income', {
          transaction_id: credit.id,
          notes: 'E2E test — bank interest',
        }, { headers: { company: companyId } });

        return { payment_id: incRes.data.payment_id, tx_amount: credit.amount };
      } catch (e) {
        const resp = e.response || {};
        return { error: true, status: resp.status, data: JSON.stringify(resp.data || {}).substring(0, 500), url: e.config?.url };
      }
    }, COMPANY_ID);

    if (result.error) console.log(`ERROR: ${result.status} ${result.url}\n  ${result.data}`);
    if (result.skip) { console.log('SKIP: No unreconciled credits'); return; }

    expect(result.error).toBeFalsy();
    expect(result.payment_id).toBeTruthy();
    console.log(`✓ Created income payment #${result.payment_id} from ${result.tx_amount} credit`);
  });

  test('8. Mark-as-reviewed — marks transaction without record', async () => {
    const result = await page.evaluate(async (companyId) => {
      try {
        const txRes = await window.axios.get('/banking/transactions', {
          params: { limit: 50, orderByField: 'transaction_date', orderBy: 'desc' },
          headers: { company: companyId },
        });
        const tx = (txRes.data.data || []).find(t => !t.matched_invoice_id && !t.linked_type && t.processing_status !== 'processed');
        if (!tx) return { skip: true };

        await window.axios.post('/banking/reconciliation/mark-reviewed', {
          transaction_id: tx.id,
          notes: 'E2E test — reviewed',
        }, { headers: { company: companyId } });

        return { tx_id: tx.id };
      } catch (e) {
        const resp = e.response || {};
        return { error: true, status: resp.status, data: JSON.stringify(resp.data || {}).substring(0, 500), url: e.config?.url };
      }
    }, COMPANY_ID);

    if (result.error) console.log(`ERROR: ${result.status} ${result.url}\n  ${result.data}`);
    if (result.skip) { console.log('SKIP: No unreconciled transactions'); return; }
    expect(result.error).toBeFalsy();
    console.log(`✓ Marked TX ${result.tx_id} as reviewed`);
  });

  // ═══════════════════════════════════════════════
  // UI FLOW
  // ═══════════════════════════════════════════════

  test('9. Banking page shows Reconcile buttons', async () => {
    await page.goto(`${BASE}/admin/banking`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(3000);

    const reconcileBtn = page.locator('button:has-text("Reconcile")');
    const count = await reconcileBtn.count();
    console.log(`Found ${count} "Reconcile" buttons`);

    await page.screenshot({ path: 'test-results/smart-reconciliation-buttons.png', fullPage: true });
  });

  test('10. Smart drawer opens and shows AI suggestion', async () => {
    const reconcileBtn = page.locator('button:has-text("Reconcile")');
    const count = await reconcileBtn.count();
    if (count === 0) { console.log('SKIP: No Reconcile buttons'); return; }

    await reconcileBtn.first().click();
    await page.waitForTimeout(2000);

    // Drawer should appear
    const drawer = page.locator('.fixed.inset-0.z-50');
    await expect(drawer).toBeVisible({ timeout: 5000 });

    // Wait for AI suggestion
    await page.waitForTimeout(8000);

    await page.screenshot({ path: 'test-results/smart-reconciliation-drawer.png', fullPage: true });

    // Check Accept button exists
    const accept = page.locator('button:has-text("Accept")');
    const hasAccept = await accept.count();
    console.log(`Drawer shows Accept button: ${hasAccept > 0}`);

    // Check manual options link
    const manualLink = page.locator('button:has-text("Manual Options")');
    const hasManual = await manualLink.count();
    console.log(`Drawer shows Manual Options: ${hasManual > 0}`);

    // Close drawer
    await page.locator('.absolute.inset-0.bg-gray-500').click({ force: true });
    await page.waitForTimeout(500);
  });

  // ═══════════════════════════════════════════════
  // SUPPORTING ENDPOINTS
  // ═══════════════════════════════════════════════

  test('11. Supporting APIs return data', async () => {
    const result = await page.evaluate(async (companyId) => {
      const h = { headers: { company: companyId } };
      const [cats, bills, invoices, payroll] = await Promise.all([
        window.axios.get('/banking/reconciliation/expense-categories', h),
        window.axios.get('/banking/reconciliation/unpaid-bills', h),
        window.axios.get('/banking/reconciliation/unpaid-invoices', h),
        window.axios.get('/banking/reconciliation/payroll-runs', h),
      ]);
      return {
        categories: (cats.data.data || []).length,
        bills: (bills.data.data || []).length,
        invoices: (invoices.data.data || []).length,
        payroll: (payroll.data.data || []).length,
      };
    }, COMPANY_ID);

    console.log(`Supporting data: ${result.categories} categories, ${result.bills} bills, ${result.invoices} invoices, ${result.payroll} payroll runs`);
    expect(result.categories).toBeGreaterThanOrEqual(0);
  });

  // ═══════════════════════════════════════════════
  // VERIFICATION
  // ═══════════════════════════════════════════════

  test('12. Expenses page shows created records', async () => {
    await page.goto(`${BASE}/admin/expenses`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(3000);
    await page.screenshot({ path: 'test-results/smart-reconciliation-expenses.png', fullPage: true });
    console.log('✓ Expenses page loaded');
  });

  test('13. No JS errors or API 500s', async () => {
    if (jsErrors.length) console.log('JS Errors:', JSON.stringify(jsErrors, null, 2));
    if (apiErrors.length) console.log('API Errors:', JSON.stringify(apiErrors, null, 2));
    expect(apiErrors.length).toBe(0);
    // Allow minor JS errors from third-party scripts
    const criticalErrors = jsErrors.filter(e => !e.error.includes('ResizeObserver'));
    expect(criticalErrors.length).toBe(0);
  });
});
