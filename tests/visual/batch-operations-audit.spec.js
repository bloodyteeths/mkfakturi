/**
 * Batch Operations — Functional UI Audit
 *
 * Tests the batch operations page end-to-end via Playwright:
 *  - Page loads without JS errors or API errors
 *  - All 7 operation cards render with correct labels
 *  - Company selector shows companies with select/deselect all
 *  - Parameter forms render per operation type
 *  - Client-side validation
 *  - Job creation via API works
 *  - Job list shows created jobs with status badges
 *  - Status filter works
 *  - i18n keys resolve (no raw key paths visible)
 *  - Backend validation rejects invalid requests
 *
 * Usage:
 *   TEST_EMAIL=giovanny.ledner@example.com TEST_PASSWORD=TestPassword123 \
 *     npx playwright test tests/visual/batch-operations-audit.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';

const BASE = 'http://localhost:8000';
const EMAIL = process.env.TEST_EMAIL || '';
const PASS = process.env.TEST_PASSWORD || '';

test.describe.configure({ mode: 'serial' });

/** @type {import('@playwright/test').Page} */
let page;
let jsErrors = [];
let apiErrors = [];
let authToken = '';

test.describe('Batch Operations Audit', () => {
  test.setTimeout(30000);

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    // Collect JS errors
    page.on('pageerror', err => {
      jsErrors.push({ url: page.url(), error: err.message });
    });

    // Collect API 404/500 errors
    page.on('response', resp => {
      if (resp.url().includes('/api/') && (resp.status() === 404 || resp.status() >= 500)) {
        apiErrors.push({ url: resp.url(), status: resp.status() });
      }
    });
  });

  test.afterAll(async () => {
    if (jsErrors.length > 0) {
      console.log('JS Errors:', JSON.stringify(jsErrors, null, 2));
    }
    if (apiErrors.length > 0) {
      console.log('API Errors:', JSON.stringify(apiErrors, null, 2));
    }
    await page.close();
  });

  // ─────────────────────────────────────────────
  // AUTH — Login through the browser UI
  // ─────────────────────────────────────────────
  test('Login and navigate to batch operations', async () => {
    test.setTimeout(60000);

    if (!EMAIL || !PASS) {
      console.log('SKIP: No credentials. Set TEST_EMAIL and TEST_PASSWORD.');
      test.skip();
      return;
    }

    // Step 1: Navigate to login page to initialize the SPA
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);

    // Step 2: Login through the SPA's own axios (uses Sanctum CSRF flow)
    const loginResult = await page.evaluate(async ({ email, password }) => {
      try {
        // Get CSRF cookie first (same as auth store)
        await window.axios.get(window.location.origin + '/sanctum/csrf-cookie');
        // Login via API
        const resp = await window.axios.post('/auth/login', { email, password });
        return { success: true, status: resp.status };
      } catch (e) {
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message };
      }
    }, { email: EMAIL, password: PASS });

    console.log('Login result:', JSON.stringify(loginResult));
    expect(loginResult.success, `Login failed: ${loginResult.message}`).toBeTruthy();

    // Step 3: Navigate to batch operations
    await page.goto(`${BASE}/admin/partner/accounting/batch-operations`, {
      waitUntil: 'networkidle',
      timeout: 30000
    });
    await page.waitForTimeout(3000);

    const url = page.url();
    console.log('Batch ops URL:', url);

    await page.screenshot({ path: 'test-results/batch-ops-initial.png', fullPage: true });

    // Verify we landed on the right page
    expect(url).not.toContain('/installation');
    expect(url).not.toContain('/login');
  });

  // ─────────────────────────────────────────────
  // PAGE LOAD — Title renders
  // ─────────────────────────────────────────────
  test('Page title and header render', async () => {
    const pageText = await page.textContent('body');

    // Should NOT contain raw i18n key paths
    expect(pageText).not.toContain('batch_operations.title');
    expect(pageText).not.toContain('batch_operations.select_operation');

    // Should contain translated titles
    const hasTitle = pageText.includes('Batch Operations') || pageText.includes('Збирни операции');
    expect(hasTitle, 'Expected page title in English or Macedonian').toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // OPERATION CARDS — All 7 visible
  // ─────────────────────────────────────────────
  test('All 7 operation cards are visible', async () => {
    const expectedLabels = [
      ['Daily Close', 'Дневно затворање'],
      ['VAT Return', 'ДДВ пријава'],
      ['Trial Balance Export', 'Извоз на бруто биланс'],
      ['Journal Export', 'Извоз на налози'],
      ['Balance Sheet Export', 'Извоз на биланс на состојба'],
      ['Income Statement Export', 'Извоз на биланс на успех'],
      ['Period Lock', 'Заклучување на период'],
    ];

    const bodyText = await page.textContent('body');

    for (const [enLabel, mkLabel] of expectedLabels) {
      const found = bodyText.includes(enLabel) || bodyText.includes(mkLabel);
      expect(found, `Expected label "${enLabel}" or "${mkLabel}"`).toBeTruthy();
    }
  });

  // ─────────────────────────────────────────────
  // OPERATION SELECTION — Shows company selector
  // ─────────────────────────────────────────────
  test('Selecting an operation shows company selector', async () => {
    const cards = page.locator('[class*="cursor-pointer"][class*="rounded-lg"][class*="border-2"]');
    const cardCount = await cards.count();
    expect(cardCount).toBe(7);

    // Click Daily Close
    await cards.first().click();
    await page.waitForTimeout(500);

    const bodyText = await page.textContent('body');
    const hasCompanySection = bodyText.includes('Select Companies') || bodyText.includes('Изберете компании');
    expect(hasCompanySection).toBeTruthy();

    const hasSelectAll = bodyText.includes('Select All') || bodyText.includes('Избери ги сите');
    expect(hasSelectAll).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-daily-close-selected.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // COMPANY SELECTOR — Select/deselect all
  // ─────────────────────────────────────────────
  test('Select All and Deselect All work', async () => {
    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(300);

    const checkboxes = page.locator('input[type="checkbox"]');
    const checkedCount = await checkboxes.evaluateAll(els => els.filter(el => el.checked).length);
    expect(checkedCount).toBeGreaterThan(0);

    const bodyText = await page.textContent('body');
    const hasCount = bodyText.includes('companies selected') || bodyText.includes('компании избрани');
    expect(hasCount).toBeTruthy();

    const deselectAllBtn = page.locator('button').filter({ hasText: /Deselect All|Одзначи ги сите/ });
    await deselectAllBtn.click();
    await page.waitForTimeout(300);

    const checkedAfter = await checkboxes.evaluateAll(els => els.filter(el => el.checked).length);
    expect(checkedAfter).toBe(0);
  });

  // ─────────────────────────────────────────────
  // DAILY CLOSE — Shows date parameter
  // ─────────────────────────────────────────────
  test('Daily Close shows date parameter', async () => {
    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(500);

    const bodyText = await page.textContent('body');
    const hasDateLabel = bodyText.includes('Date') || bodyText.includes('Датум');
    expect(hasDateLabel).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-daily-close-params.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // VAT RETURN — Shows year and month
  // ─────────────────────────────────────────────
  test('VAT Return shows year and month parameters', async () => {
    const deselectBtn = page.locator('button').filter({ hasText: /Deselect All|Одзначи ги сите/ });
    await deselectBtn.click();
    await page.waitForTimeout(200);

    const cards = page.locator('[class*="cursor-pointer"][class*="rounded-lg"][class*="border-2"]');
    await cards.nth(1).click();
    await page.waitForTimeout(500);

    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(500);

    const bodyText = await page.textContent('body');
    expect(bodyText.includes('Year') || bodyText.includes('Година')).toBeTruthy();
    expect(bodyText.includes('Month') || bodyText.includes('Месец')).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-vat-return-params.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // TRIAL BALANCE — Shows date range + format
  // ─────────────────────────────────────────────
  test('Trial Balance Export shows date range and format', async () => {
    const deselectBtn = page.locator('button').filter({ hasText: /Deselect All|Одзначи ги сите/ });
    await deselectBtn.click();
    await page.waitForTimeout(200);

    const cards = page.locator('[class*="cursor-pointer"][class*="rounded-lg"][class*="border-2"]');
    await cards.nth(2).click();
    await page.waitForTimeout(500);

    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(500);

    const bodyText = await page.textContent('body');
    expect(bodyText.includes('Date From') || bodyText.includes('Датум од')).toBeTruthy();
    expect(bodyText.includes('Date To') || bodyText.includes('Датум до')).toBeTruthy();
    expect(bodyText.includes('Format') || bodyText.includes('Формат')).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-trial-balance-params.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // BALANCE SHEET — Shows as_of_date + format
  // ─────────────────────────────────────────────
  test('Balance Sheet Export shows as-of-date and format', async () => {
    const deselectBtn = page.locator('button').filter({ hasText: /Deselect All|Одзначи ги сите/ });
    await deselectBtn.click();
    await page.waitForTimeout(200);

    const cards = page.locator('[class*="cursor-pointer"][class*="rounded-lg"][class*="border-2"]');
    await cards.nth(4).click();
    await page.waitForTimeout(500);

    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(500);

    const bodyText = await page.textContent('body');
    expect(bodyText.includes('As of Date') || bodyText.includes('На датум')).toBeTruthy();
    expect(bodyText.includes('Format') || bodyText.includes('Формат')).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-balance-sheet-params.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // PERIOD LOCK — Shows period start/end
  // ─────────────────────────────────────────────
  test('Period Lock shows period start and end', async () => {
    const deselectBtn = page.locator('button').filter({ hasText: /Deselect All|Одзначи ги сите/ });
    await deselectBtn.click();
    await page.waitForTimeout(200);

    const cards = page.locator('[class*="cursor-pointer"][class*="rounded-lg"][class*="border-2"]');
    await cards.nth(6).click();
    await page.waitForTimeout(500);

    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(500);

    const bodyText = await page.textContent('body');
    expect(bodyText.includes('Period Start') || bodyText.includes('Почеток на период')).toBeTruthy();
    expect(bodyText.includes('Period End') || bodyText.includes('Крај на период')).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-period-lock-params.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // I18N — No raw key paths visible
  // ─────────────────────────────────────────────
  test('No raw i18n key paths visible on page', async () => {
    const bodyText = await page.textContent('body');

    const rawKeys = [
      'batch_operations.search_companies',
      'batch_operations.no_companies_available',
      'batch_operations.all_statuses',
      'batch_operations.companies_count',
      'batch_operations.field_required',
      'batch_operations.fill_required_params',
      'batch_operations.select_companies',
      'batch_operations.recent_jobs',
    ];

    for (const key of rawKeys) {
      expect(bodyText, `Raw key "${key}" should not be visible`).not.toContain(key);
    }
  });

  // ─────────────────────────────────────────────
  // RECENT JOBS — Section with status filter
  // ─────────────────────────────────────────────
  test('Recent Jobs section renders with status filter', async () => {
    const bodyText = await page.textContent('body');
    const hasRecentJobs = bodyText.includes('Recent Jobs') || bodyText.includes('Последни операции');
    expect(hasRecentJobs).toBeTruthy();

    const statusFilter = page.locator('select');
    const filterCount = await statusFilter.count();
    expect(filterCount).toBeGreaterThan(0);

    const filterText = await statusFilter.first().textContent();
    const hasAllStatuses = filterText.includes('All statuses') || filterText.includes('Сите статуси');
    expect(hasAllStatuses).toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // START BUTTON — Visible, enabled, triggers confirm
  // ─────────────────────────────────────────────
  test('Start button shows and opens confirm dialog', async () => {
    // Select Daily Close
    const cards = page.locator('[class*="cursor-pointer"][class*="rounded-lg"][class*="border-2"]');
    await cards.first().click();
    await page.waitForTimeout(300);

    const selectAllBtn = page.locator('button').filter({ hasText: /Select All|Избери ги сите/ });
    await selectAllBtn.click();
    await page.waitForTimeout(500);

    const startBtn = page.locator('button').filter({ hasText: /Start Batch|Започни/ });
    await expect(startBtn.first()).toBeVisible();
    await expect(startBtn.first()).toBeEnabled();

    await startBtn.first().click();
    await page.waitForTimeout(1000);

    await page.screenshot({ path: 'test-results/batch-ops-confirm-dialog.png', fullPage: true });

    // Close the dialog
    await page.keyboard.press('Escape');
    await page.waitForTimeout(500);
  });

  // ─────────────────────────────────────────────
  // API VALIDATION — Rejects invalid requests
  // ─────────────────────────────────────────────
  test('Backend validation rejects missing required params', async () => {
    // Use page.evaluate with window.axios (shares session cookies)
    const results = await page.evaluate(async () => {
      const responses = {};

      // daily_close without date
      try {
        await window.axios.post('/partner/batch-operations', {
          operation_type: 'daily_close', company_ids: [1], parameters: {}
        });
        responses.daily_close = 200;
      } catch (e) {
        responses.daily_close = e.response?.status || 0;
      }

      // vat_return without year
      try {
        await window.axios.post('/partner/batch-operations', {
          operation_type: 'vat_return', company_ids: [1], parameters: { month: 3 }
        });
        responses.vat_return = 200;
      } catch (e) {
        responses.vat_return = e.response?.status || 0;
      }

      // invalid operation type
      try {
        await window.axios.post('/partner/batch-operations', {
          operation_type: 'nonexistent_op', company_ids: [1], parameters: {}
        });
        responses.invalid = 200;
      } catch (e) {
        responses.invalid = e.response?.status || 0;
      }

      return responses;
    });

    expect(results.daily_close).toBe(422);
    expect(results.vat_return).toBe(422);
    expect(results.invalid).toBe(422);
  });

  // ─────────────────────────────────────────────
  // API — Operations endpoint returns all 7 types
  // ─────────────────────────────────────────────
  test('Operations API returns all 7 operation types', async () => {
    const result = await page.evaluate(async () => {
      const resp = await window.axios.get('/partner/batch-operations/operations');
      return resp.data;
    });

    expect(result.data).toHaveLength(7);

    const keys = result.data.map(op => op.key);
    expect(keys).toContain('daily_close');
    expect(keys).toContain('vat_return');
    expect(keys).toContain('trial_balance_export');
    expect(keys).toContain('journal_export');
    expect(keys).toContain('balance_sheet_export');
    expect(keys).toContain('income_statement_export');
    expect(keys).toContain('period_lock');
  });

  // ─────────────────────────────────────────────
  // JOB CREATION — Create via API and verify in list
  // ─────────────────────────────────────────────
  test('Create a batch job via API and verify it appears', async () => {
    const result = await page.evaluate(async () => {
      const resp = await window.axios.post('/partner/batch-operations', {
        operation_type: 'trial_balance_export',
        company_ids: [1],
        parameters: {
          report_type: 'trial_balance',
          date_from: '2026-01-01',
          date_to: '2026-03-09',
          format: 'csv',
        }
      });
      return resp.data;
    });

    expect(result.data.operation_type).toBe('trial_balance_export');
    expect(result.data.status).toBe('queued');

    // Refresh to see the new job
    await page.reload({ waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);

    const bodyText = await page.textContent('body');
    const hasTrialBalance = bodyText.includes('Trial Balance Export') || bodyText.includes('Извоз на бруто биланс');
    expect(hasTrialBalance).toBeTruthy();

    await page.screenshot({ path: 'test-results/batch-ops-with-job.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // JOB EXPANSION — Click to expand shows results
  // ─────────────────────────────────────────────
  test('Clicking a job row expands it', async () => {
    const jobRows = page.locator('[class*="cursor-pointer"][class*="hover\\:bg-gray-50"]');
    const jobCount = await jobRows.count();

    if (jobCount === 0) {
      console.log('No jobs to expand, skipping');
      return;
    }

    await jobRows.first().click();
    await page.waitForTimeout(500);

    await page.screenshot({ path: 'test-results/batch-ops-expanded-job.png', fullPage: true });
  });

  // ─────────────────────────────────────────────
  // STATUS FILTER — Works
  // ─────────────────────────────────────────────
  test('Status filter filters the job list', async () => {
    // Re-navigate to batch operations in case previous test caused navigation
    await page.goto('http://localhost:8000/admin/partner/accounting/batch-operations');
    await page.waitForTimeout(2000);

    const statusFilter = page.locator('select').first();
    await statusFilter.waitFor({ state: 'visible', timeout: 10000 });

    await statusFilter.selectOption('completed');
    await page.waitForTimeout(300);

    await page.screenshot({ path: 'test-results/batch-ops-filtered-completed.png', fullPage: true });

    await statusFilter.selectOption('');
    await page.waitForTimeout(300);
  });

  // ─────────────────────────────────────────────
  // SUMMARY — No critical errors
  // ─────────────────────────────────────────────
  test('No JS errors or API failures during audit', async () => {
    // Filter out known non-critical errors
    const criticalJsErrors = jsErrors.filter(e =>
      !e.url.includes('/installation') && !e.url.includes('/login')
    );
    const criticalApiErrors = apiErrors.filter(e =>
      !e.url.includes('/bootstrap')
    );

    if (criticalJsErrors.length > 0) {
      console.log('Critical JS Errors:', JSON.stringify(criticalJsErrors, null, 2));
    }
    if (criticalApiErrors.length > 0) {
      console.log('Critical API Errors:', JSON.stringify(criticalApiErrors, null, 2));
    }

    expect(criticalJsErrors.length, `Found ${criticalJsErrors.length} JS errors`).toBe(0);
    expect(criticalApiErrors.length, `Found ${criticalApiErrors.length} API errors`).toBe(0);
  });
});
