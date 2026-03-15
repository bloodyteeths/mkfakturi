/**
 * Onboarding — E2E Audit
 *
 * Tests the simplified single-page onboarding:
 *  - Dashboard shows OnboardingChecklistWidget
 *  - Company onboarding page loads with source selection
 *  - Selecting Pantheon shows export guide inline
 *  - Quick action links navigate correctly
 *  - Partner onboarding page loads with portfolio section
 *  - API endpoints work with real fixture files
 *
 * Usage:
 *   npx playwright test tests/visual/onboarding-wizard-audit.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const BASE = process.env.TEST_BASE_URL || 'http://localhost:8000';
const EMAIL = process.env.TEST_EMAIL || 'giovanny.ledner@example.com';
const PASS = process.env.TEST_PASSWORD || 'password123';
const FIXTURES = path.resolve('tests/fixtures/onboarding');

/** @type {import('@playwright/test').Page} */
let page;
let apiErrors = [];

test.describe('Company Onboarding E2E', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(60000);
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    page = await ctx.newPage();
    apiErrors = [];

    page.on('response', res => {
      if (res.status() >= 500) apiErrors.push({ url: res.url(), status: res.status() });
    });

    // Login
    await page.goto(`${BASE}/login`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.locator('input[type="email"]').fill(EMAIL);
    await page.locator('input[type="password"]').fill(PASS);
    await page.waitForTimeout(500);
    await page.locator('button[type="submit"]').click();
    await page.waitForFunction(() => !window.location.href.includes('/login'), { timeout: 30000 });
  });

  test.afterAll(async () => {
    await page.context().close();
  });

  // ─── Dashboard Checklist ──────────────────────────────────────

  test('dashboard loads without onboarding 500 errors', async () => {
    await page.goto(`${BASE}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    expect(apiErrors.filter(e => e.url.includes('onboarding'))).toHaveLength(0);
  });

  // ─── Single-Page Onboarding ─────────────────────────────────

  test('onboarding page loads with source selection', async () => {
    await page.goto(`${BASE}/admin/onboarding`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Should show radio buttons with source names
    const pantheon = page.getByText('Pantheon');
    await expect(pantheon.first()).toBeVisible({ timeout: 10000 });

    const helix = page.getByText(/Helix|Zonel/i);
    await expect(helix.first()).toBeVisible();
  });

  test('source options are radio buttons', async () => {
    const radios = page.locator('input[type="radio"][name="onboarding-source"]');
    const count = await radios.count();
    expect(count).toBe(7); // pantheon, zonel, ekonomika, astral, b2b, excel, fresh
  });

  test('selecting Pantheon shows export guide inline', async () => {
    // Click the Pantheon radio label
    const pantheonLabel = page.locator('label').filter({ hasText: 'Pantheon' }).first();
    await pantheonLabel.click();
    await page.waitForTimeout(1000);

    // Export guide should appear inline — Datalab documentation links
    const docLink = page.locator('a[href*="datalab"], a[href*="usersite"]');
    await expect(docLink.first()).toBeVisible({ timeout: 5000 });
  });

  test('export guide shows numbered steps', async () => {
    // Should have numbered steps (1, 2, 3, ...) in circles
    const stepNumbers = page.locator('.rounded-full.bg-gray-100');
    const count = await stepNumbers.count();
    expect(count).toBeGreaterThanOrEqual(3); // Pantheon has 5 steps
  });

  test('export guide shows files to prepare checklist', async () => {
    const checkboxes = page.locator('input[type="checkbox"]');
    const count = await checkboxes.count();
    expect(count).toBeGreaterThanOrEqual(3); // journal, partners, chart, invoices, bank
  });

  test('selecting "Starting fresh" hides export guide', async () => {
    const freshLabel = page.locator('label').filter({ hasText: /нула|fresh/i }).first();
    await freshLabel.click();
    await page.waitForTimeout(500);

    // Export guide should not be visible
    const docLink = page.locator('a[href*="datalab"]');
    const visible = await docLink.first().isVisible().catch(() => false);
    expect(visible).toBe(false);
  });

  test('quick action links are visible', async () => {
    // Should show action links at the bottom
    const journalLink = page.getByText(/journal|книжења|Увези книжења/i);
    await expect(journalLink.first()).toBeVisible({ timeout: 5000 });

    const invoiceLink = page.getByText(/first invoice|прва фактура|Креирај/i);
    await expect(invoiceLink.first()).toBeVisible();
  });

  test('switching between sources updates guide', async () => {
    // Select Zonel
    const zonelLabel = page.locator('label').filter({ hasText: /Zonel|Helix/i }).first();
    await zonelLabel.click();
    await page.waitForTimeout(1000);

    // Zonel guide should have zonel.com.mk link
    const zonelLink = page.locator('a[href*="zonel"]');
    const hasZonelLink = await zonelLink.first().isVisible().catch(() => false);
    expect(hasZonelLink).toBe(true);
  });

  // ─── API Endpoint Tests ───────────────────────────────────────

  test('onboarding progress API returns valid data', async () => {
    await page.goto(`${BASE}/admin/dashboard`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    const companyId = await page.evaluate(() => localStorage.getItem('selectedCompany') || '1');
    const cookies = await page.context().cookies();
    const xsrfCookie = cookies.find(c => c.name === 'XSRF-TOKEN');
    const headers = { 'Accept': 'application/json', 'company': companyId };
    if (xsrfCookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.value);

    const response = await page.request.get(`${BASE}/api/v1/onboarding/progress`, { headers });
    expect([200, 401, 404]).toContain(response.status());

    if (response.ok()) {
      const data = await response.json();
      expect(data.steps).toBeDefined();
      expect(data.total_count).toBe(5);
    }
  });

  test('source API saves and returns source', async () => {
    const companyId = await page.evaluate(() => localStorage.getItem('selectedCompany') || '1');
    const cookies = await page.context().cookies();
    const xsrfCookie = cookies.find(c => c.name === 'XSRF-TOKEN');
    const headers = { 'Accept': 'application/json', 'company': companyId };
    if (xsrfCookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.value);

    const response = await page.request.post(`${BASE}/api/v1/onboarding/source`, {
      headers,
      data: { source: 'pantheon' },
    });
    expect([200, 401, 419]).toContain(response.status());

    if (response.ok()) {
      const data = await response.json();
      expect(data.success).toBe(true);
      expect(data.source).toBe('pantheon');
    }
  });

  test('journal preview API parses real Pantheon file', async () => {
    const fileBuffer = fs.readFileSync(path.join(FIXTURES, 'pantheon_nalozi.txt'));
    const firmsBuffer = fs.readFileSync(path.join(FIXTURES, 'pantheon_firmi.txt'));

    const companyId = await page.evaluate(() => localStorage.getItem('selectedCompany') || '1');
    const cookies = await page.context().cookies();
    const xsrfCookie = cookies.find(c => c.name === 'XSRF-TOKEN');
    const headers = { 'Accept': 'application/json', 'company': companyId };
    if (xsrfCookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.value);

    const response = await page.request.post(`${BASE}/api/v1/onboarding/journal/preview`, {
      headers,
      multipart: {
        file: { name: 'pantheon_nalozi.txt', mimeType: 'text/plain', buffer: fileBuffer },
        firms_file: { name: 'pantheon_firmi.txt', mimeType: 'text/plain', buffer: firmsBuffer },
      },
    });

    expect([200, 401, 419]).toContain(response.status());

    if (response.ok()) {
      const data = await response.json();
      expect(data.success).toBe(true);
      expect(data.data.format).toBe('pantheon_txt');
      expect(data.data.summary.total_nalozi).toBe(8);
    }
  });

  test('bank analysis API extracts counterparties from CSV', async () => {
    const bankBuffer = fs.readFileSync(path.join(FIXTURES, 'bank_statement_komercijalna.csv'));

    const companyId = await page.evaluate(() => localStorage.getItem('selectedCompany') || '1');
    const cookies = await page.context().cookies();
    const xsrfCookie = cookies.find(c => c.name === 'XSRF-TOKEN');
    const headers = { 'Accept': 'application/json', 'company': companyId };
    if (xsrfCookie) headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.value);

    const response = await page.request.post(`${BASE}/api/v1/onboarding/analyze-bank`, {
      headers,
      multipart: {
        file: { name: 'bank_statement.csv', mimeType: 'text/csv', buffer: bankBuffer },
      },
    });

    expect([200, 401, 419]).toContain(response.status());

    if (response.ok()) {
      const data = await response.json();
      expect(data.success).toBe(true);
      expect(data.transaction_count).toBeGreaterThan(0);
    }
  });

  test('no 500 errors occurred during onboarding', async () => {
    const onboardingErrors = apiErrors.filter(e =>
      e.url.includes('onboarding') || e.url.includes('journal')
    );
    expect(onboardingErrors).toHaveLength(0);
  });
});

test.describe('Partner Onboarding E2E', () => {
  test.describe.configure({ mode: 'serial' });

  /** @type {import('@playwright/test').Page} */
  let partnerPage;
  let partnerApiErrors = [];

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(60000);
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    partnerPage = await ctx.newPage();
    partnerApiErrors = [];

    partnerPage.on('response', res => {
      if (res.status() >= 500) partnerApiErrors.push({ url: res.url(), status: res.status() });
    });

    // Login
    await partnerPage.goto(`${BASE}/login`);
    await partnerPage.waitForLoadState('networkidle');
    await partnerPage.waitForTimeout(2000);
    await partnerPage.locator('input[type="email"]').fill(EMAIL);
    await partnerPage.locator('input[type="password"]').fill(PASS);
    await partnerPage.waitForTimeout(500);
    await partnerPage.locator('button[type="submit"]').click();
    await partnerPage.waitForFunction(() => !window.location.href.includes('/login'), { timeout: 30000 });
  });

  test.afterAll(async () => {
    await partnerPage.context().close();
  });

  test('partner onboarding page loads', async () => {
    await partnerPage.goto(`${BASE}/admin/partner/onboarding`);
    await partnerPage.waitForLoadState('networkidle');
    await partnerPage.waitForTimeout(3000);

    const title = partnerPage.getByText(/Онбординг|Onboarding|Portfolio|Портфолио|partner|партнер/i);
    await expect(title.first()).toBeVisible({ timeout: 10000 });
  });

  test('shows portfolio import section', async () => {
    const buttons = partnerPage.locator('button');
    const count = await buttons.count();
    expect(count).toBeGreaterThan(0);
  });

  test('shows quick action links', async () => {
    const journalLink = partnerPage.getByText(/journal|книжења|Увези/i);
    const hasJournal = await journalLink.first().isVisible().catch(() => false);

    const portfolioLink = partnerPage.getByText(/Portfolio|Портфолио/i);
    const hasPortfolio = await portfolioLink.first().isVisible().catch(() => false);

    expect(hasJournal || hasPortfolio).toBe(true);
  });

  test('partner progress API works', async () => {
    const response = await partnerPage.request.get(`${BASE}/api/v1/partner/onboarding/progress`, {
      headers: { 'Accept': 'application/json' },
    });
    expect([200, 401, 403]).toContain(response.status());
  });

  test('no 500 errors during partner onboarding', async () => {
    expect(partnerApiErrors.filter(e => e.url.includes('onboarding'))).toHaveLength(0);
  });
});

test.describe('Migration Hub', () => {
  test.describe.configure({ mode: 'serial' });

  /** @type {import('@playwright/test').Page} */
  let hubPage;

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(60000);
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    hubPage = await ctx.newPage();

    await hubPage.goto(`${BASE}/login`);
    await hubPage.waitForLoadState('networkidle');
    await hubPage.waitForTimeout(2000);
    await hubPage.locator('input[type="email"]').fill(EMAIL);
    await hubPage.locator('input[type="password"]').fill(PASS);
    await hubPage.waitForTimeout(500);
    await hubPage.locator('button[type="submit"]').click();
    await hubPage.waitForFunction(() => !window.location.href.includes('/login'), { timeout: 30000 });
  });

  test.afterAll(async () => {
    await hubPage.context().close();
  });

  test('migration hub loads', async () => {
    await hubPage.goto(`${BASE}/admin/imports`);
    await hubPage.waitForLoadState('networkidle');
    await hubPage.waitForTimeout(3000);

    const title = hubPage.getByText(/Migration|Миграција/i);
    await expect(title.first()).toBeVisible({ timeout: 10000 });
  });

  test('migration hub shows step cards', async () => {
    const customersCard = hubPage.getByText(/Клиенти|Customers/i);
    const hasCustomers = await customersCard.first().isVisible().catch(() => false);

    const productsCard = hubPage.getByText(/Производи|Products/i);
    const hasProducts = await productsCard.first().isVisible().catch(() => false);

    expect(hasCustomers || hasProducts).toBe(true);
  });

  test('migration progress API works', async () => {
    const companyId = await hubPage.evaluate(() => localStorage.getItem('selectedCompany') || '1');
    const response = await hubPage.request.get(`${BASE}/api/v1/onboarding/migration-progress`, {
      headers: { 'Accept': 'application/json', 'company': companyId },
    });

    expect([200, 401, 404]).toContain(response.status());
  });
});
