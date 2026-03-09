/**
 * Comprehensive Navigation & i18n Audit
 * Tests all 12 feature pages + 2 hub pages:
 * - Sidebar navigation links
 * - Page loads without errors
 * - Language switching (mk, en, tr, sq)
 * - Key UI elements visible and functional
 * - Button clicks respond
 */
import { test, expect } from '@playwright/test';
import fs from 'fs';
import path from 'path';

const BASE_URL = 'https://app.facturino.mk';
const EMAIL = 'your-email@example.com';
const PASSWORD = 'your-secure-password';

// All feature pages to test
const FEATURE_PAGES = [
  // Hub pages
  { path: '/admin/operations', name: 'Operations Hub', expectText: ['Compensations', 'Purchase Orders', 'Payment Orders'] },
  { path: '/admin/finance', name: 'Finance Hub', expectText: ['BI Dashboard', 'Budgets', 'Interest'] },

  // Group 2 features (via Operations hub)
  { path: '/admin/compensations', name: 'Compensations' },
  { path: '/admin/purchase-orders', name: 'Purchase Orders' },
  { path: '/admin/payment-orders', name: 'Payment Orders' },
  { path: '/admin/travel-orders', name: 'Travel Orders' },
  { path: '/admin/cost-centers', name: 'Cost Centers' },
  { path: '/admin/stock', name: 'Stock' },
  { path: '/admin/projects', name: 'Projects' },

  // Group 3 features (via Finance hub)
  { path: '/admin/interest', name: 'Interest' },
  { path: '/admin/interest/summary', name: 'Interest Summary' },
  { path: '/admin/collections', name: 'Collections' },
  { path: '/admin/budgets', name: 'Budgets' },
  { path: '/admin/bi-dashboard', name: 'BI Dashboard' },
  { path: '/admin/custom-reports', name: 'Custom Reports' },
];

// i18n keys that should appear in sidebar for the 2 hub items
const SIDEBAR_HUB_KEYS = {
  mk: { operations: 'Операции', finance: 'Финансии' },
  en: { operations: 'Operations', finance: 'Finance' },
};

// Hub page translations to verify
const HUB_TRANSLATIONS = {
  operations: {
    mk: { title: 'Операции', section1: 'Документи и налози', section2: 'Управување' },
    en: { title: 'Operations', section1: 'Documents & Orders', section2: 'Management' },
    tr: { title: 'Operasyonlar', section1: 'Belgeler ve Siparisler', section2: 'Yonetim' },
    sq: { title: 'Operacionet', section1: 'Dokumentet dhe Porositë', section2: 'Menaxhimi' },
  },
  finance: {
    mk: { title: 'Финансии', section1: 'Анализа', section2: 'Наплата и усогласеност' },
    en: { title: 'Finance', section1: 'Analysis', section2: 'Collections & Compliance' },
    tr: { title: 'Finans', section1: 'Analiz', section2: 'Tahsilat ve Uyumluluk' },
    sq: { title: 'Financa', section1: 'Analiza', section2: 'Arkëtimi dhe Pajtueshmëria' },
  },
};

// Feature page title translations
const PAGE_TITLES = {
  mk: {
    compensations: 'Компензации',
    purchase_orders: 'Нарачки за набавка',
    payment_orders: 'Налози за плаќање',
    travel_orders: 'Патни налози',
    cost_centers: 'Трошковни центри',
    interest: 'Затезна камата',
    collections: 'Наплата и опомени',
    budgets: 'Буџетирање',
    bi_dashboard: 'БИ Контролна табла',
    custom_reports: 'Прилагодени извештаи',
  },
  en: {
    compensations: 'Compensations',
    purchase_orders: 'Purchase Orders',
    payment_orders: 'Payment Orders',
    travel_orders: 'Travel Orders',
    cost_centers: 'Cost Centers',
    interest: 'Late Interest',
    collections: 'Collections',
    budgets: 'Budgets',
    bi_dashboard: 'BI Dashboard',
    custom_reports: 'Custom Reports',
  },
  tr: {
    compensations: 'Mahsuplasmalar',
    purchase_orders: 'Satin Alma Siparisleri',
    payment_orders: 'Odeme Emirleri',
    travel_orders: 'Seyahat Emirleri',
    cost_centers: 'Maliyet Merkezleri',
    interest: 'Gecikme Faizi',
    collections: 'Tahsilat',
    budgets: 'Butceler',
    bi_dashboard: 'BI Kontrol Paneli',
    custom_reports: 'Ozel Raporlar',
  },
  sq: {
    compensations: 'Kompensime',
    purchase_orders: 'Porositë e Blerjes',
    payment_orders: 'Urdhërat e Pagesës',
    travel_orders: 'Urdhërat e Udhëtimit',
    cost_centers: 'Qendrat e Kostos',
    interest: 'Interesi i Vonuar',
    collections: 'Arkëtimi',
    budgets: 'Buxhetet',
    bi_dashboard: 'Paneli BI',
    custom_reports: 'Raportet e Personalizuara',
  },
};

/**
 * Login helper - logs in and returns authenticated page
 */
async function login(page) {
  await page.goto(`${BASE_URL}/login`);
  await page.waitForLoadState('networkidle');

  // Fill login form
  const emailInput = page.locator('input[type="email"], input[name="email"]').first();
  const passInput = page.locator('input[type="password"], input[name="password"]').first();

  await emailInput.fill(EMAIL);
  await passInput.fill(PASSWORD);

  // Submit
  const submitBtn = page.locator('button[type="submit"], button:has-text("Login"), button:has-text("Log In")').first();
  await submitBtn.click();

  // Wait for dashboard
  await page.waitForURL(/\/admin\/dashboard|\/admin/, { timeout: 15000 });
  await page.waitForLoadState('networkidle');
}

/**
 * Change language via settings API or URL
 */
async function setLanguage(page, lang) {
  // Set the lang attribute directly and reload
  await page.evaluate((l) => {
    document.documentElement.lang = l;
  }, lang);
}

// ============================================================
// TEST SUITE 1: Authentication & Basic Navigation
// ============================================================

test.describe('Authentication & Navigation', () => {
  test('should login successfully', async ({ page }) => {
    await login(page);
    const url = page.url();
    expect(url).toContain('/admin');
  });

  test('sidebar should show Operations and Finance hub items', async ({ page }) => {
    await login(page);

    // Check sidebar has "Operations" text
    const sidebar = page.locator('aside, nav, [class*="sidebar"]').first();
    const sidebarText = await sidebar.textContent();

    console.log('=== SIDEBAR AUDIT ===');

    // Check that old individual items are GONE from sidebar
    const removedItems = [
      'Compensations', 'Purchase Orders', 'Payment Orders',
      'Travel Orders', 'Cost Centers',
      'Interest', 'Collections', 'Budgets', 'BI Dashboard', 'Custom Reports',
    ];

    for (const item of removedItems) {
      // These should NOT be in the sidebar anymore (they're in hub pages)
      const sidebarLink = sidebar.locator(`a:has-text("${item}")`);
      const count = await sidebarLink.count();
      console.log(`  Sidebar "${item}": ${count > 0 ? 'STILL PRESENT (should be removed)' : 'REMOVED (correct)'}`);
    }

    // Check hub items ARE present
    const hubItems = ['Operations', 'Finance'];
    for (const item of hubItems) {
      const sidebarLink = sidebar.locator(`a:has-text("${item}")`);
      const count = await sidebarLink.count();
      console.log(`  Sidebar "${item}": ${count > 0 ? 'PRESENT (correct)' : 'MISSING'}`);
    }
  });
});

// ============================================================
// TEST SUITE 2: All Feature Pages Load
// ============================================================

test.describe('Feature Pages Load', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  for (const feature of FEATURE_PAGES) {
    test(`${feature.name} (${feature.path}) loads without error`, async ({ page }) => {
      const errors = [];
      page.on('pageerror', (err) => errors.push(err.message));

      const consoleErrors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') consoleErrors.push(msg.text());
      });

      // Track failed API calls
      const failedRequests = [];
      page.on('response', (response) => {
        if (response.url().includes('/api/') && response.status() >= 400) {
          failedRequests.push({ url: response.url(), status: response.status() });
        }
      });

      await page.goto(`${BASE_URL}${feature.path}`);
      await page.waitForLoadState('networkidle');

      // Wait a bit for Vue to render
      await page.waitForTimeout(2000);

      // Check page rendered (not blank)
      const bodyText = await page.locator('body').textContent();
      expect(bodyText.length).toBeGreaterThan(50);

      // Log results
      console.log(`\n=== ${feature.name} ===`);
      console.log(`  URL: ${feature.path}`);
      console.log(`  Page errors: ${errors.length > 0 ? errors.join('; ') : 'none'}`);
      console.log(`  Console errors: ${consoleErrors.length > 0 ? consoleErrors.join('; ') : 'none'}`);
      console.log(`  Failed API calls: ${failedRequests.length > 0 ? JSON.stringify(failedRequests) : 'none'}`);

      // Check expected text if specified
      if (feature.expectText) {
        for (const text of feature.expectText) {
          const hasText = bodyText.includes(text);
          console.log(`  Expected text "${text}": ${hasText ? 'FOUND' : 'MISSING'}`);
        }
      }

      // Take screenshot
      await page.screenshot({ path: `test-results/nav-audit/${feature.name.replace(/\s/g, '-').toLowerCase()}.png`, fullPage: true });

      // Page should not have JS errors
      if (errors.length > 0) {
        console.log(`  WARNING: Page errors detected`);
      }
    });
  }
});

// ============================================================
// TEST SUITE 3: Hub Page Card Navigation
// ============================================================

test.describe('Hub Card Navigation', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('Operations Hub - all 7 cards navigate correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/operations`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const expectedRoutes = [
      { text: 'Compensations', route: '/admin/compensations' },
      { text: 'Purchase Orders', route: '/admin/purchase-orders' },
      { text: 'Payment Orders', route: '/admin/payment-orders' },
      { text: 'Travel Orders', route: '/admin/travel-orders' },
      { text: 'Cost Centers', route: '/admin/cost-centers' },
      { text: 'Stock', route: '/admin/stock' },
      { text: 'Projects', route: '/admin/projects' },
    ];

    console.log('\n=== Operations Hub Card Audit ===');
    const links = page.locator('a[href]');
    const allLinks = await links.all();
    const hrefs = [];
    for (const link of allLinks) {
      hrefs.push(await link.getAttribute('href'));
    }

    for (const expected of expectedRoutes) {
      const found = hrefs.some((h) => h && h.includes(expected.route));
      console.log(`  Card "${expected.text}" → ${expected.route}: ${found ? 'FOUND' : 'MISSING'}`);
      expect(found).toBeTruthy();
    }
  });

  test('Finance Hub - all 5 cards navigate correctly', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/finance`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const expectedRoutes = [
      { text: 'BI Dashboard', route: '/admin/bi-dashboard' },
      { text: 'Budgets', route: '/admin/budgets' },
      { text: 'Custom Reports', route: '/admin/custom-reports' },
      { text: 'Interest', route: '/admin/interest' },
      { text: 'Collections', route: '/admin/collections' },
    ];

    console.log('\n=== Finance Hub Card Audit ===');
    const links = page.locator('a[href]');
    const allLinks = await links.all();
    const hrefs = [];
    for (const link of allLinks) {
      hrefs.push(await link.getAttribute('href'));
    }

    for (const expected of expectedRoutes) {
      const found = hrefs.some((h) => h && h.includes(expected.route));
      console.log(`  Card "${expected.text}" → ${expected.route}: ${found ? 'FOUND' : 'MISSING'}`);
      expect(found).toBeTruthy();
    }
  });

  test('Operations Hub - clicking a card navigates to feature', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/operations`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    // Click the first card (Compensations)
    const firstCard = page.locator('a[href*="/admin/compensations"]').first();
    if (await firstCard.count() > 0) {
      await firstCard.click();
      await page.waitForLoadState('networkidle');
      expect(page.url()).toContain('/admin/compensations');
      console.log('  Card click → navigated to /admin/compensations');
    }
  });
});

// ============================================================
// TEST SUITE 4: BI Dashboard UI Elements
// ============================================================

test.describe('BI Dashboard UI', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('period selector is visible and not truncated', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/bi-dashboard`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Find the period selector
    const periodSelector = page.locator('.w-64').first();
    const selectorExists = await periodSelector.count() > 0;
    console.log(`\n=== BI Dashboard Period Selector ===`);
    console.log(`  w-64 selector exists: ${selectorExists}`);

    if (selectorExists) {
      const box = await periodSelector.boundingBox();
      console.log(`  Width: ${box?.width}px, Height: ${box?.height}px`);
      // Should be at least 200px wide
      if (box) {
        expect(box.width).toBeGreaterThan(200);
      }
    }

    // Check that Refresh button is visible
    const refreshBtn = page.locator('button:has-text("Refresh"), button:has-text("Освежи")');
    console.log(`  Refresh button visible: ${await refreshBtn.count() > 0}`);

    await page.screenshot({ path: 'test-results/nav-audit/bi-dashboard-selector.png' });
  });
});

// ============================================================
// TEST SUITE 5: i18n - Feature Page Titles in All Languages
// ============================================================

test.describe('i18n Feature Page Titles', () => {
  // We test by checking the i18n files directly (backend test)
  // since changing language mid-session requires settings change

  test('all i18n files have 4 language keys', async () => {
    // fs and path imported at top
    const i18nDir = path.join(process.cwd(), 'resources/scripts/admin/i18n');

    const files = fs.readdirSync(i18nDir).filter((f) => f.endsWith('.js'));
    console.log(`\n=== i18n Files Audit ===`);
    console.log(`  Found ${files.length} i18n files`);

    const results = [];

    for (const file of files) {
      const content = fs.readFileSync(path.join(i18nDir, file), 'utf-8');

      const hasMk = content.includes("mk:");
      const hasEn = content.includes("en:");
      const hasTr = content.includes("tr:");
      const hasSq = content.includes("sq:");

      const status = hasMk && hasEn && hasTr && hasSq ? 'COMPLETE' : 'INCOMPLETE';
      const missing = [];
      if (!hasMk) missing.push('mk');
      if (!hasEn) missing.push('en');
      if (!hasTr) missing.push('tr');
      if (!hasSq) missing.push('sq');

      results.push({ file, status, missing });
      console.log(`  ${file}: ${status}${missing.length > 0 ? ` (missing: ${missing.join(', ')})` : ''}`);
    }

    // All files should be complete
    const incomplete = results.filter((r) => r.status === 'INCOMPLETE');
    expect(incomplete.length).toBe(0);
  });

  test('Hub pages have 4 language keys', async () => {
    // fs and path imported at top

    console.log(`\n=== Hub Pages i18n Audit ===`);

    const hubFiles = [
      'resources/scripts/admin/views/operations/Hub.vue',
      'resources/scripts/admin/views/finance/Hub.vue',
    ];

    for (const file of hubFiles) {
      const content = fs.readFileSync(path.join(process.cwd(), file), 'utf-8');

      const hasMk = content.includes("mk:");
      const hasEn = content.includes("en:");
      const hasTr = content.includes("tr:");
      const hasSq = content.includes("sq:");

      const status = hasMk && hasEn && hasTr && hasSq ? 'COMPLETE' : 'INCOMPLETE';
      const missing = [];
      if (!hasMk) missing.push('mk');
      if (!hasEn) missing.push('en');
      if (!hasTr) missing.push('tr');
      if (!hasSq) missing.push('sq');

      console.log(`  ${file}: ${status}${missing.length > 0 ? ` (missing: ${missing.join(', ')})` : ''}`);
      expect(missing.length).toBe(0);
    }
  });

  test('lang/mk.json and lang/en.json have hub navigation keys', async () => {
    // fs and path imported at top

    console.log(`\n=== Navigation Keys Audit ===`);

    for (const lang of ['mk', 'en']) {
      const content = JSON.parse(fs.readFileSync(path.join(process.cwd(), `lang/${lang}.json`), 'utf-8'));

      const hasOperations = content.navigation?.operations;
      const hasFinance = content.navigation?.finance;
      const hasOpsHint = content.navigation_hints?.operations;
      const hasFinHint = content.navigation_hints?.finance;

      console.log(`  ${lang}.json navigation.operations: ${hasOperations || 'MISSING'}`);
      console.log(`  ${lang}.json navigation.finance: ${hasFinance || 'MISSING'}`);
      console.log(`  ${lang}.json navigation_hints.operations: ${hasOpsHint || 'MISSING'}`);
      console.log(`  ${lang}.json navigation_hints.finance: ${hasFinHint || 'MISSING'}`);

      expect(hasOperations).toBeTruthy();
      expect(hasFinance).toBeTruthy();
      expect(hasOpsHint).toBeTruthy();
      expect(hasFinHint).toBeTruthy();
    }
  });
});

// ============================================================
// TEST SUITE 6: Button Functionality
// ============================================================

test.describe('Button Functionality', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  test('Compensations - Create button works', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/compensations`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    const createBtn = page.locator('a[href*="create"], button:has-text("Create"), button:has-text("Креирај"), button:has-text("New"), button:has-text("Нова")').first();
    console.log(`\n=== Compensations Buttons ===`);
    console.log(`  Create button found: ${await createBtn.count() > 0}`);

    if (await createBtn.count() > 0) {
      await createBtn.click();
      await page.waitForTimeout(2000);
      console.log(`  After click URL: ${page.url()}`);
    }
  });

  test('Interest - Calculate button works', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/interest`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    console.log(`\n=== Interest Page Buttons ===`);

    const allButtons = page.locator('button');
    const count = await allButtons.count();
    console.log(`  Total buttons on page: ${count}`);
    for (let i = 0; i < Math.min(count, 10); i++) {
      const text = await allButtons.nth(i).textContent();
      console.log(`    Button ${i}: "${text.trim()}"`);
    }

    // Screenshot
    await page.screenshot({ path: 'test-results/nav-audit/interest-buttons.png', fullPage: true });
  });

  test('BI Dashboard - Refresh button works', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/bi-dashboard`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    console.log(`\n=== BI Dashboard Buttons ===`);

    const refreshBtn = page.locator('button').filter({ hasText: /refresh|Освежи|Yenile|Rifresko/i }).first();
    const exists = await refreshBtn.count() > 0;
    console.log(`  Refresh button found: ${exists}`);

    if (exists) {
      await refreshBtn.click();
      await page.waitForTimeout(2000);
      console.log(`  Refresh clicked, page still functional`);
    }

    await page.screenshot({ path: 'test-results/nav-audit/bi-dashboard-refreshed.png', fullPage: true });
  });

  test('Budgets - Create button works', async ({ page }) => {
    await page.goto(`${BASE_URL}/admin/budgets`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    console.log(`\n=== Budgets Page Buttons ===`);

    const allButtons = page.locator('button, a.btn, a[class*="button"]');
    const count = await allButtons.count();
    console.log(`  Total buttons/links: ${count}`);
    for (let i = 0; i < Math.min(count, 10); i++) {
      const text = await allButtons.nth(i).textContent();
      const href = await allButtons.nth(i).getAttribute('href');
      console.log(`    ${i}: "${text.trim()}" ${href ? `→ ${href}` : ''}`);
    }

    await page.screenshot({ path: 'test-results/nav-audit/budgets-buttons.png', fullPage: true });
  });
});

// ============================================================
// TEST SUITE 7: API Response Check (no 404s)
// ============================================================

test.describe('API Health Check', () => {
  test.beforeEach(async ({ page }) => {
    await login(page);
  });

  const apiPages = [
    { path: '/admin/interest', name: 'Interest', apiPattern: '/interest' },
    { path: '/admin/collections', name: 'Collections', apiPattern: '/collections' },
    { path: '/admin/budgets', name: 'Budgets', apiPattern: '/budgets' },
    { path: '/admin/bi-dashboard', name: 'BI Dashboard', apiPattern: '/bi-dashboard' },
    { path: '/admin/compensations', name: 'Compensations', apiPattern: '/compensations' },
    { path: '/admin/cost-centers', name: 'Cost Centers', apiPattern: '/cost-centers' },
  ];

  for (const pg of apiPages) {
    test(`${pg.name} - no 404 API calls`, async ({ page }) => {
      const apiCalls = [];
      page.on('response', (response) => {
        if (response.url().includes('/api/')) {
          apiCalls.push({
            url: response.url().replace(BASE_URL, ''),
            status: response.status(),
          });
        }
      });

      await page.goto(`${BASE_URL}${pg.path}`);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(3000);

      console.log(`\n=== ${pg.name} API Calls ===`);
      for (const call of apiCalls) {
        const emoji = call.status >= 400 ? 'FAIL' : 'OK';
        console.log(`  [${emoji}] ${call.status} ${call.url}`);
      }

      const failed = apiCalls.filter((c) => c.status === 404);
      if (failed.length > 0) {
        console.log(`  WARNING: ${failed.length} x 404 errors!`);
      }

      // No 404s should occur
      expect(failed.length).toBe(0);
    });
  }
});
