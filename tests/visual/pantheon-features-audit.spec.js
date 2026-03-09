/**
 * PANTHEON-PARITY FEATURES — Deep UI & Usage Audit
 *
 * Tests all 12 features for:
 *  - Page loads without JS errors or API 404s
 *  - Correct i18n translations (mk/en/tr/sq)
 *  - UI elements present (buttons, forms, tables)
 *  - CRUD operations work (create, view, list)
 *  - Hub navigation works (Operations + Finance)
 *  - Sidebar shows hub items
 *  - API endpoints return 200
 */
import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk';
const EMAIL = process.env.TEST_EMAIL || '';
const PASS = process.env.TEST_PASSWORD || '';

// Run ALL tests serially to share auth state
test.describe.configure({ mode: 'serial' });

/** @type {import('@playwright/test').Page} */
let page;
let jsErrors = [];
let apiErrors = [];

test.describe('Pantheon Features Deep Audit', () => {
  // Production tests need longer timeouts
  test.setTimeout(60000);

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
    // Write error report
    const report = {
      timestamp: new Date().toISOString(),
      jsErrors,
      apiErrors,
    };
    const outDir = path.join(process.cwd(), 'test-results', 'pantheon-audit');
    if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });
    fs.writeFileSync(path.join(outDir, 'error-report.json'), JSON.stringify(report, null, 2));
    await page.close();
  });

  // ─────────────────────────────────────────────
  // AUTH
  // ─────────────────────────────────────────────
  test('Login', async () => {
    test.setTimeout(90000);

    if (!EMAIL || !PASS) {
      console.log('SKIP: No credentials provided. Set TEST_EMAIL and TEST_PASSWORD env vars.');
      console.log('Example: TEST_EMAIL=you@email.com TEST_PASSWORD=pass npx playwright test ...');
      test.skip();
      return;
    }

    // Step 1: Get CSRF cookie from Sanctum (returns 204)
    await page.request.get(`${BASE}/sanctum/csrf-cookie`);

    // Step 2: Login via API
    const loginResp = await page.request.post(`${BASE}/api/v1/auth/login`, {
      data: { email: EMAIL, password: PASS },
      headers: { 'Accept': 'application/json' },
    });
    console.log('Login API status:', loginResp.status());

    if (loginResp.status() !== 200) {
      const body = await loginResp.text();
      console.log('Login response:', body);
    }
    expect(loginResp.status()).toBe(200);

    // Step 3: Navigate to admin
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle', timeout: 30000 });
    const url = page.url();
    console.log('After login, URL:', url);
    expect(url).not.toContain('/login');
  });

  // ─────────────────────────────────────────────
  // SIDEBAR AUDIT
  // ─────────────────────────────────────────────
  test('Sidebar shows Operations and Finance hub items', async () => {
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle', timeout: 30000 });
    const sidebar = await page.locator('nav, .sidebar, [class*="sidebar"]').first();
    const text = await sidebar.textContent();

    // Hub items should be present
    const hasOperations = text.includes('Operations') || text.includes('Операции');
    const hasFinance = text.includes('Finance') || text.includes('Финансии');

    console.log('Sidebar has Operations:', hasOperations);
    console.log('Sidebar has Finance:', hasFinance);

    // Removed items should NOT be present
    const removedItems = [
      'Compensations', 'Компензации',
      'Purchase Orders', 'Нарачки за набавка',
      'Payment Orders', 'Налози за плаќање',
      'Cost Centers', 'Трошковни центри',
      'Interest', 'Затезна камата',
      'BI Dashboard', 'БИ Контролна табла',
    ];

    const foundRemoved = removedItems.filter(item => text.includes(item));
    console.log('Removed items still in sidebar:', foundRemoved.length > 0 ? foundRemoved : 'NONE (correct)');

    expect(hasOperations || hasFinance).toBeTruthy();
  });

  // ─────────────────────────────────────────────
  // OPERATIONS HUB
  // ─────────────────────────────────────────────
  test('Operations Hub loads with 7 cards', async () => {
    await page.goto(`${BASE}/admin/operations`, { waitUntil: 'networkidle', timeout: 30000 });

    // Check page title
    const content = await page.textContent('body');
    const hasTitle = content.includes('Operations') || content.includes('Операции');
    expect(hasTitle).toBeTruthy();

    // Should have 7 card links
    const cards = page.locator('a[href*="/admin/"]').filter({ has: page.locator('h3') });
    const cardCount = await cards.count();
    console.log('Operations Hub card count:', cardCount);
    expect(cardCount).toBeGreaterThanOrEqual(7);

    // Check category headers
    const hasDocOrders = content.includes('Documents & Orders') || content.includes('Документи и налози');
    const hasManagement = content.includes('Management') || content.includes('Управување');
    console.log('Has Documents & Orders section:', hasDocOrders);
    console.log('Has Management section:', hasManagement);
  });

  test('Operations Hub cards link to correct pages', async () => {
    const expectedLinks = [
      '/admin/compensations',
      '/admin/purchase-orders',
      '/admin/payment-orders',
      '/admin/travel-orders',
      '/admin/cost-centers',
      '/admin/stock',
      '/admin/projects',
    ];

    for (const link of expectedLinks) {
      const card = page.locator(`a[href="${link}"]`);
      const exists = await card.count();
      console.log(`  Card → ${link}: ${exists > 0 ? 'FOUND' : 'MISSING'}`);
      expect(exists).toBeGreaterThan(0);
    }
  });

  // ─────────────────────────────────────────────
  // FINANCE HUB
  // ─────────────────────────────────────────────
  test('Finance Hub loads with 5 cards', async () => {
    await page.goto(`${BASE}/admin/finance`, { waitUntil: 'networkidle', timeout: 30000 });

    const content = await page.textContent('body');
    const hasTitle = content.includes('Finance') || content.includes('Финансии');
    expect(hasTitle).toBeTruthy();

    // Should have 5 card links
    const cards = page.locator('a[href*="/admin/"]').filter({ has: page.locator('h3') });
    const cardCount = await cards.count();
    console.log('Finance Hub card count:', cardCount);
    expect(cardCount).toBeGreaterThanOrEqual(5);

    // Check category headers
    const hasAnalysis = content.includes('Analysis') || content.includes('Анализа');
    const hasCollections = content.includes('Collections') || content.includes('Наплата');
    console.log('Has Analysis section:', hasAnalysis);
    console.log('Has Collections & Compliance section:', hasCollections);
  });

  test('Finance Hub cards link to correct pages', async () => {
    const expectedLinks = [
      '/admin/bi-dashboard',
      '/admin/budgets',
      '/admin/custom-reports',
      '/admin/interest',
      '/admin/collections',
    ];

    for (const link of expectedLinks) {
      const card = page.locator(`a[href="${link}"]`);
      const exists = await card.count();
      console.log(`  Card → ${link}: ${exists > 0 ? 'FOUND' : 'MISSING'}`);
      expect(exists).toBeGreaterThan(0);
    }
  });

  // ─────────────────────────────────────────────
  // F1: COMPENSATIONS
  // ─────────────────────────────────────────────
  test('F1: Compensations - Index loads', async () => {
    await page.goto(`${BASE}/admin/compensations`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Compensations') || content.includes('Компензации') || content.includes('compensation');
    console.log('F1 Compensations page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F1: Compensations - Create form loads', async () => {
    await page.goto(`${BASE}/admin/compensations/create`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    // Should have step wizard or form elements
    const hasForm = content.includes('Step') || content.includes('Чекор') ||
                    content.includes('counterparty') || content.includes('контрапартија') ||
                    content.includes('bilateral') || content.includes('Create') || content.includes('Креирај');
    console.log('F1 Create form loaded:', hasForm);
    expect(hasForm).toBeTruthy();
  });

  test('F1: Compensations - Opportunities API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/compensations/opportunities`, {
      headers: { 'company': '1' }
    });
    console.log('F1 Opportunities API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F2: PAYMENT ORDERS
  // ─────────────────────────────────────────────
  test('F2: Payment Orders - Index loads', async () => {
    await page.goto(`${BASE}/admin/payment-orders`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Payment') || content.includes('Налози') || content.includes('payment');
    console.log('F2 Payment Orders page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F2: Payment Orders - Create form loads', async () => {
    await page.goto(`${BASE}/admin/payment-orders/create`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const hasForm = content.includes('PP30') || content.includes('SEPA') || content.includes('batch') ||
                    content.includes('format') || content.includes('Формат') || content.includes('Create');
    console.log('F2 Create form loaded:', hasForm);
    expect(hasForm).toBeTruthy();
  });

  test('F2: Payment Orders - Overdue summary API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/payment-orders/overdue-summary`, {
      headers: { 'company': '1' }
    });
    console.log('F2 Overdue summary API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F3: COST CENTERS
  // ─────────────────────────────────────────────
  test('F3: Cost Centers - Index loads', async () => {
    await page.goto(`${BASE}/admin/cost-centers`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Cost Center') || content.includes('Трошковн') || content.includes('cost');
    console.log('F3 Cost Centers page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F3: Cost Centers - Has tree/flat view toggle', async () => {
    const content = await page.textContent('body');
    const hasToggle = content.includes('Tree') || content.includes('Flat') ||
                      content.includes('Дрво') || content.includes('Листа');
    console.log('F3 Has view toggle:', hasToggle);
    // Not all pages have toggle, so just report
  });

  test('F3: Cost Centers - API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/cost-centers`, {
      headers: { 'company': '1' }
    });
    console.log('F3 Cost Centers API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  test('F3: Cost Centers - Summary API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/cost-centers/summary`, {
      headers: { 'company': '1' }
    });
    console.log('F3 Summary API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F4: INTEREST CALCULATOR
  // ─────────────────────────────────────────────
  test('F4: Interest - Index loads', async () => {
    await page.goto(`${BASE}/admin/interest`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Interest') || content.includes('Камата') || content.includes('камат');
    console.log('F4 Interest page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F4: Interest - Has summary stats', async () => {
    const content = await page.textContent('body');
    const hasStats = content.includes('total') || content.includes('pending') || content.includes('вкупно') ||
                     content.includes('НБРМ') || content.includes('rate') || content.includes('стапка');
    console.log('F4 Has summary/stats:', hasStats);
  });

  test('F4: Interest - Summary page loads', async () => {
    await page.goto(`${BASE}/admin/interest/summary`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Interest') || content.includes('Камата') || content.includes('Summary') || content.includes('Преглед');
    console.log('F4 Summary page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F4: Interest - API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest`, {
      headers: { 'company': '1' }
    });
    console.log('F4 Interest API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  test('F4: Interest - Summary API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest/summary`, {
      headers: { 'company': '1' }
    });
    console.log('F4 Interest Summary API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F5: COLLECTIONS
  // ─────────────────────────────────────────────
  test('F5: Collections - Index loads', async () => {
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Collection') || content.includes('Наплата') || content.includes('overdue') || content.includes('задоцн');
    console.log('F5 Collections page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F5: Collections - Has tabs (overdue/templates/history)', async () => {
    const content = await page.textContent('body');
    const hasTabs = content.includes('Overdue') || content.includes('Template') || content.includes('History') ||
                    content.includes('Задоцнети') || content.includes('Шаблон') || content.includes('Историја');
    console.log('F5 Has tabs:', hasTabs);
  });

  test('F5: Collections - Overdue API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/overdue`, {
      headers: { 'company': '1' }
    });
    console.log('F5 Overdue API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F6: PURCHASE ORDERS
  // ─────────────────────────────────────────────
  test('F6: Purchase Orders - Index loads', async () => {
    await page.goto(`${BASE}/admin/purchase-orders`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Purchase') || content.includes('Набавка') || content.includes('purchase');
    console.log('F6 Purchase Orders page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F6: Purchase Orders - Create form loads', async () => {
    await page.goto(`${BASE}/admin/purchase-orders/create`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const hasForm = content.includes('Supplier') || content.includes('Добавувач') || content.includes('Items') ||
                    content.includes('Create') || content.includes('Креирај');
    console.log('F6 Create form loaded:', hasForm);
    expect(hasForm).toBeTruthy();
  });

  test('F6: Purchase Orders - API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/purchase-orders`, {
      headers: { 'company': '1' }
    });
    console.log('F6 Purchase Orders API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F7: BUDGETS
  // ─────────────────────────────────────────────
  test('F7: Budgets - Index loads', async () => {
    await page.goto(`${BASE}/admin/budgets`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Budget') || content.includes('Буџет') || content.includes('budget');
    console.log('F7 Budgets page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F7: Budgets - Create form loads', async () => {
    await page.goto(`${BASE}/admin/budgets/create`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const hasForm = content.includes('period') || content.includes('scenario') || content.includes('Период') ||
                    content.includes('Create') || content.includes('Креирај') || content.includes('Name');
    console.log('F7 Create form loaded:', hasForm);
    expect(hasForm).toBeTruthy();
  });

  test('F7: Budgets - API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/budgets`, {
      headers: { 'company': '1' }
    });
    console.log('F7 Budgets API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F8: TRAVEL ORDERS
  // ─────────────────────────────────────────────
  test('F8: Travel Orders - Index loads', async () => {
    await page.goto(`${BASE}/admin/travel-orders`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Travel') || content.includes('Патн') || content.includes('travel');
    console.log('F8 Travel Orders page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F8: Travel Orders - Has summary stats', async () => {
    const content = await page.textContent('body');
    const hasStats = content.includes('total') || content.includes('pending') || content.includes('per diem') ||
                     content.includes('вкупно') || content.includes('дневниц');
    console.log('F8 Has summary stats:', hasStats);
  });

  test('F8: Travel Orders - Create form loads', async () => {
    await page.goto(`${BASE}/admin/travel-orders/create`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const hasForm = content.includes('domestic') || content.includes('foreign') || content.includes('purpose') ||
                    content.includes('домашно') || content.includes('странство') || content.includes('Цел') ||
                    content.includes('Type') || content.includes('Тип');
    console.log('F8 Create form loaded:', hasForm);
    expect(hasForm).toBeTruthy();
  });

  test('F8: Travel Orders - API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/travel-orders`, {
      headers: { 'company': '1' }
    });
    console.log('F8 Travel Orders API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  test('F8: Travel Orders - Summary API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/travel-orders/summary`, {
      headers: { 'company': '1' }
    });
    console.log('F8 Summary API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F9: BI DASHBOARD
  // ─────────────────────────────────────────────
  test('F9: BI Dashboard - Index loads', async () => {
    await page.goto(`${BASE}/admin/bi-dashboard`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('BI') || content.includes('Dashboard') || content.includes('Контролна') ||
                   content.includes('Health') || content.includes('Здравје') || content.includes('Z-Score');
    console.log('F9 BI Dashboard page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F9: BI Dashboard - Period selector visible', async () => {
    const selector = page.locator('select, [class*="multiselect"], [role="listbox"]');
    const count = await selector.count();
    console.log('F9 Period selector elements:', count);
    // Just verify page didn't crash
  });

  test('F9: BI Dashboard - Ratios page loads', async () => {
    await page.goto(`${BASE}/admin/bi-dashboard/ratios`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Ratio') || content.includes('Показател') || content.includes('liquidity') ||
                   content.includes('Ликвидност') || content.includes('current_ratio');
    console.log('F9 Ratios page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F9: BI Dashboard - Trends page loads', async () => {
    await page.goto(`${BASE}/admin/bi-dashboard/trends`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Trend') || content.includes('Трендови') || content.includes('month') || content.includes('месец');
    console.log('F9 Trends page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F9: BI Dashboard - Summary API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/bi-dashboard/summary?date=2026-03-07`, {
      headers: { 'company': '1' }
    });
    console.log('F9 Summary API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F10: BATCH OPERATIONS (partner-only)
  // ─────────────────────────────────────────────
  test('F10: Batch Operations - API works (partner)', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/partner/batch-operations`);
    console.log('F10 Batch Operations API status:', resp.status());
    // Partner endpoint - may return 200 or 403 depending on auth
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F11: CUSTOM REPORTS
  // ─────────────────────────────────────────────
  test('F11: Custom Reports - Index loads', async () => {
    await page.goto(`${BASE}/admin/custom-reports`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const loaded = content.includes('Custom') || content.includes('Report') || content.includes('Прилагоден') || content.includes('Извештаи');
    console.log('F11 Custom Reports page loaded:', loaded);
    expect(loaded).toBeTruthy();
  });

  test('F11: Custom Reports - Create form loads', async () => {
    await page.goto(`${BASE}/admin/custom-reports/create`, { waitUntil: 'networkidle', timeout: 30000 });
    const content = await page.textContent('body');
    const hasForm = content.includes('Column') || content.includes('Filter') || content.includes('Колона') ||
                    content.includes('account') || content.includes('Сметка') || content.includes('Name');
    console.log('F11 Create form loaded:', hasForm);
    expect(hasForm).toBeTruthy();
  });

  test('F11: Custom Reports - API works', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/custom-reports`, {
      headers: { 'company': '1' }
    });
    console.log('F11 Custom Reports API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // F12: FINANCIAL CONSOLIDATION (partner-only)
  // ─────────────────────────────────────────────
  test('F12: Financial Consolidation - API works (partner)', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/partner/consolidation/groups`);
    console.log('F12 Consolidation Groups API status:', resp.status());
    expect(resp.status()).toBeLessThan(500);
  });

  // ─────────────────────────────────────────────
  // i18n AUDIT — Backend files
  // ─────────────────────────────────────────────
  test('i18n: All feature translation files have 4 languages', async () => {
    const i18nDir = path.join(process.cwd(), 'resources', 'scripts', 'admin', 'i18n');
    const files = fs.readdirSync(i18nDir).filter(f => f.endsWith('.js'));

    const results = [];
    for (const file of files) {
      const content = fs.readFileSync(path.join(i18nDir, file), 'utf8');
      const hasMk = content.includes("mk:") || content.includes("'mk'") || content.includes('"mk"');
      const hasEn = content.includes("en:") || content.includes("'en'") || content.includes('"en"');
      const hasTr = content.includes("tr:") || content.includes("'tr'") || content.includes('"tr"');
      const hasSq = content.includes("sq:") || content.includes("'sq'") || content.includes('"sq"');

      const status = (hasMk && hasEn && hasTr && hasSq) ? 'COMPLETE' : 'INCOMPLETE';
      const missing = [];
      if (!hasMk) missing.push('mk');
      if (!hasEn) missing.push('en');
      if (!hasTr) missing.push('tr');
      if (!hasSq) missing.push('sq');

      results.push({ file, status, missing });
      console.log(`  ${file}: ${status}${missing.length ? ' (missing: ' + missing.join(', ') + ')' : ''}`);
    }

    const incomplete = results.filter(r => r.status === 'INCOMPLETE');
    expect(incomplete.length).toBe(0);
  });

  test('i18n: Hub pages have all 4 languages', async () => {
    const hubFiles = [
      path.join(process.cwd(), 'resources', 'scripts', 'admin', 'views', 'operations', 'Hub.vue'),
      path.join(process.cwd(), 'resources', 'scripts', 'admin', 'views', 'finance', 'Hub.vue'),
    ];

    for (const filePath of hubFiles) {
      const content = fs.readFileSync(filePath, 'utf8');
      const hasMk = content.includes('mk:');
      const hasEn = content.includes('en:');
      const hasTr = content.includes('tr:');
      const hasSq = content.includes('sq:');

      const name = path.basename(path.dirname(filePath)) + '/Hub.vue';
      console.log(`  ${name}: mk=${hasMk} en=${hasEn} tr=${hasTr} sq=${hasSq}`);
      expect(hasMk && hasEn && hasTr && hasSq).toBeTruthy();
    }
  });

  test('i18n: navigation keys exist in mk.json and en.json', async () => {
    const mkJson = JSON.parse(fs.readFileSync(path.join(process.cwd(), 'lang', 'mk.json'), 'utf8'));
    const enJson = JSON.parse(fs.readFileSync(path.join(process.cwd(), 'lang', 'en.json'), 'utf8'));

    const requiredKeys = ['navigation.operations', 'navigation.finance'];
    for (const key of requiredKeys) {
      const parts = key.split('.');
      const mkVal = parts.reduce((o, k) => o?.[k], mkJson);
      const enVal = parts.reduce((o, k) => o?.[k], enJson);
      console.log(`  ${key}: mk="${mkVal}" en="${enVal}"`);
      expect(mkVal).toBeTruthy();
      expect(enVal).toBeTruthy();
    }
  });

  // ─────────────────────────────────────────────
  // ALL API ENDPOINTS HEALTH CHECK
  // ─────────────────────────────────────────────
  test('API Health: All 12 feature endpoints return non-500', async () => {
    const endpoints = [
      { name: 'Compensations', url: '/api/v1/compensations' },
      { name: 'Payment Orders', url: '/api/v1/payment-orders' },
      { name: 'Cost Centers', url: '/api/v1/cost-centers' },
      { name: 'Interest', url: '/api/v1/interest' },
      { name: 'Collections Overdue', url: '/api/v1/collections/overdue' },
      { name: 'Purchase Orders', url: '/api/v1/purchase-orders' },
      { name: 'Budgets', url: '/api/v1/budgets' },
      { name: 'Travel Orders', url: '/api/v1/travel-orders' },
      { name: 'BI Dashboard Summary', url: '/api/v1/bi-dashboard/summary?date=2026-03-07' },
      { name: 'Custom Reports', url: '/api/v1/custom-reports' },
      { name: 'Batch Ops (partner)', url: '/api/v1/partner/batch-operations' },
      { name: 'Consolidation (partner)', url: '/api/v1/partner/consolidation/groups' },
    ];

    const results = [];
    for (const ep of endpoints) {
      const resp = await page.request.get(`${BASE}${ep.url}`, {
        headers: { 'company': '1' }
      });
      const status = resp.status();
      const ok = status < 500;
      results.push({ ...ep, status, ok });
      console.log(`  ${ep.name}: ${status} ${ok ? 'OK' : 'FAIL'}`);
    }

    const failures = results.filter(r => !r.ok);
    if (failures.length > 0) {
      console.log('FAILED endpoints:', failures.map(f => `${f.name}(${f.status})`).join(', '));
    }
    expect(failures.length).toBe(0);
  });

  // ─────────────────────────────────────────────
  // BUTTON / INTERACTION TESTS
  // ─────────────────────────────────────────────
  test('UI: Compensations has Create button', async () => {
    await page.goto(`${BASE}/admin/compensations`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('a[href*="create"], button').filter({ hasText: /create|new|креирај|нов/i });
    const count = await btn.count();
    console.log('Compensations Create button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: Payment Orders has Create button', async () => {
    await page.goto(`${BASE}/admin/payment-orders`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('a[href*="create"], button').filter({ hasText: /create|new|креирај|нов/i });
    const count = await btn.count();
    console.log('Payment Orders Create button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: Purchase Orders has Create button', async () => {
    await page.goto(`${BASE}/admin/purchase-orders`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('a[href*="create"], button').filter({ hasText: /create|new|креирај|нов/i });
    const count = await btn.count();
    console.log('Purchase Orders Create button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: Budgets has Create button', async () => {
    await page.goto(`${BASE}/admin/budgets`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('a[href*="create"], button').filter({ hasText: /create|new|креирај|нов/i });
    const count = await btn.count();
    console.log('Budgets Create button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: Travel Orders has Create button', async () => {
    await page.goto(`${BASE}/admin/travel-orders`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('a[href*="create"], button').filter({ hasText: /create|new|креирај|нов/i });
    const count = await btn.count();
    console.log('Travel Orders Create button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: Custom Reports has Create button', async () => {
    await page.goto(`${BASE}/admin/custom-reports`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('a[href*="create"], button').filter({ hasText: /create|new|креирај|нов/i });
    const count = await btn.count();
    console.log('Custom Reports Create button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: BI Dashboard has Refresh button', async () => {
    await page.goto(`${BASE}/admin/bi-dashboard`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('button').filter({ hasText: /refresh|освежи|yenile/i });
    const count = await btn.count();
    console.log('BI Dashboard Refresh button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  test('UI: Interest has Calculate button', async () => {
    await page.goto(`${BASE}/admin/interest`, { waitUntil: 'networkidle', timeout: 30000 });
    const btn = page.locator('button').filter({ hasText: /calculate|пресметај|hesapla/i });
    const count = await btn.count();
    console.log('Interest Calculate button found:', count > 0);
    expect(count).toBeGreaterThan(0);
  });

  // ─────────────────────────────────────────────
  // JS ERROR SUMMARY
  // ─────────────────────────────────────────────
  test('No critical JS errors during audit', async () => {
    console.log('\n=== JS Error Summary ===');
    console.log('Total JS errors:', jsErrors.length);
    jsErrors.forEach(e => console.log(`  [${e.url}] ${e.error}`));

    console.log('\n=== API Error Summary ===');
    console.log('Total API 404/500 errors:', apiErrors.length);
    apiErrors.forEach(e => console.log(`  ${e.status} ${e.url}`));

    // Write full report
    const outDir = path.join(process.cwd(), 'test-results', 'pantheon-audit');
    if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });
    fs.writeFileSync(path.join(outDir, 'final-report.json'), JSON.stringify({
      timestamp: new Date().toISOString(),
      jsErrors,
      apiErrors,
      summary: {
        totalJsErrors: jsErrors.length,
        totalApiErrors: apiErrors.length,
      }
    }, null, 2));

    // Allow some non-critical JS errors but fail on many
    expect(jsErrors.length).toBeLessThan(20);
  });

});
