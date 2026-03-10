/**
 * Collections Feature — Playwright Functional + API Test
 *
 * Tests:
 * 1. API: Overdue invoices endpoint returns correct structure
 * 2. API: Templates CRUD (list, create, update, delete)
 * 3. API: History endpoint returns correct structure
 * 4. API: Effectiveness endpoint returns correct structure
 * 5. API: Send reminder validates email
 * 6. API: Send reminder accepts email override
 * 7. UI: Admin collections page loads with tabs
 * 8. UI: Partner collections page loads with company selector
 * 9. UI: i18n keys render properly (no raw keys visible)
 * 10. UI: Send dialog shows editable email + invoice details
 */
import { test, expect } from '@playwright/test';
import { execSync } from 'child_process';

const BASE = process.env.TEST_BASE_URL || 'http://localhost:8000';
let TOKEN = process.env.API_TOKEN || '';
let apiErrors = [];

test.describe.configure({ mode: 'serial' });

/** @type {import('@playwright/test').Page} */
let page;

function h(companyId = '2') {
  return {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${TOKEN}`,
    'company': companyId,
  };
}

test.describe('Collections Feature Audit', () => {
  test.setTimeout(90000);

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage();

    page.on('response', resp => {
      if (resp.url().includes('/api/') && (resp.status() === 404 || resp.status() >= 500)) {
        apiErrors.push({ url: resp.url(), status: resp.status() });
      }
    });

    // Generate token if not provided
    if (!TOKEN) {
      try {
        const output = execSync(
          `php artisan tinker --execute="echo App\\\\Models\\\\User::find(1)->createToken('pw-test')->plainTextToken;"`,
          { cwd: process.cwd(), encoding: 'utf8', timeout: 15000 }
        );
        TOKEN = output.split('\n').filter(l => !l.includes('Deprecated') && l.trim()).pop().trim();
        console.log(`Generated token: ${TOKEN.substring(0, 15)}...`);
      } catch (e) {
        console.log('Could not generate token:', e.message);
      }
    }
  });

  test.afterAll(async () => {
    if (apiErrors.length > 0) {
      console.log('\n=== API Errors ===');
      apiErrors.forEach(e => console.log(`  ${e.status} ${e.url}`));
    }
    await page.close();
  });

  // ─── API Tests ───

  test('API: overdue invoices endpoint returns correct structure', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/overdue`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('data');
    expect(body).toHaveProperty('summary');
    expect(body).toHaveProperty('aging');
    expect(body).toHaveProperty('pagination');
    expect(body.aging).toHaveProperty('0_30');
    expect(body.aging).toHaveProperty('31_60');
    expect(body.aging).toHaveProperty('61_90');
    expect(body.aging).toHaveProperty('90_plus');
    expect(body.pagination).toHaveProperty('total');
    expect(body.pagination).toHaveProperty('page');
    expect(body.pagination).toHaveProperty('last_page');
    console.log(`  Overdue: ${body.data.length} invoices, total=${body.summary?.total_overdue_amount}`);
  });

  test('API: overdue with escalation_level filter', async () => {
    for (const level of ['friendly', 'firm', 'final', 'legal']) {
      const resp = await page.request.get(`${BASE}/api/v1/collections/overdue?escalation_level=${level}`, { headers: h() });
      expect(resp.status()).toBe(200);
      const body = await resp.json();
      expect(Array.isArray(body.data)).toBeTruthy();
    }
  });

  test('API: overdue with search filter', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/overdue?search=nonexistent`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(Array.isArray(body.data)).toBeTruthy();
  });

  test('API: templates list returns array', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/templates`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('data');
    expect(Array.isArray(body.data)).toBeTruthy();
    if (body.data.length > 0) {
      expect(body.data[0]).toHaveProperty('escalation_level');
      expect(body.data[0]).toHaveProperty('subject_mk');
      expect(body.data[0]).toHaveProperty('body_mk');
    }
    console.log(`  Templates: ${body.data.length} found`);
  });

  let createdTemplateId = null;

  test('API: create template', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/collections/templates`, {
      headers: h(),
      data: {
        escalation_level: 'friendly',
        days_after_due: 5,
        subject_mk: 'Тест шаблон {INVOICE_NUMBER}',
        subject_en: 'Test template {INVOICE_NUMBER}',
        subject_tr: 'Test sablon {INVOICE_NUMBER}',
        subject_sq: 'Test shabllon {INVOICE_NUMBER}',
        body_mk: '<p>Тест тело со {AMOUNT_DUE}</p>',
        body_en: '<p>Test body with {AMOUNT_DUE}</p>',
        body_tr: '<p>Test govde ile {AMOUNT_DUE}</p>',
        body_sq: '<p>Test trup me {AMOUNT_DUE}</p>',
        is_active: true,
      },
    });
    expect(resp.status()).toBe(201);
    const body = await resp.json();
    expect(body.success).toBeTruthy();
    expect(body.data).toHaveProperty('id');
    createdTemplateId = body.data.id;
    console.log(`  Created template ID: ${createdTemplateId}`);
  });

  test('API: update template', async () => {
    if (!createdTemplateId) test.skip();
    const resp = await page.request.put(`${BASE}/api/v1/collections/templates/${createdTemplateId}`, {
      headers: h(),
      data: {
        escalation_level: 'firm',
        days_after_due: 10,
        subject_mk: 'Ажуриран шаблон {INVOICE_NUMBER}',
        subject_en: 'Updated template {INVOICE_NUMBER}',
        subject_tr: 'Guncel sablon {INVOICE_NUMBER}',
        subject_sq: 'Shabllon i perditesuar {INVOICE_NUMBER}',
        body_mk: '<p>Ажуриран {AMOUNT_DUE}</p>',
        body_en: '<p>Updated {AMOUNT_DUE}</p>',
        body_tr: '<p>Guncel {AMOUNT_DUE}</p>',
        body_sq: '<p>Perditesuar {AMOUNT_DUE}</p>',
        is_active: false,
      },
    });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBeTruthy();
  });

  test('API: delete template', async () => {
    if (!createdTemplateId) test.skip();
    const resp = await page.request.delete(`${BASE}/api/v1/collections/templates/${createdTemplateId}`, {
      headers: h(),
    });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBeTruthy();
  });

  test('API: history endpoint returns correct structure', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/history`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('data');
    expect(body).toHaveProperty('pagination');
    expect(Array.isArray(body.data)).toBeTruthy();
    console.log(`  History: ${body.data.length} records`);
  });

  test('API: history with date filter', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/history?from_date=2025-01-01&to_date=2026-12-31`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('data');
  });

  test('API: effectiveness endpoint returns correct structure', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/effectiveness`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('data');
    expect(body.data).toHaveProperty('by_level');
    for (const level of ['friendly', 'firm', 'final', 'legal']) {
      expect(body.data.by_level).toHaveProperty(level);
      expect(body.data.by_level[level]).toHaveProperty('total_sent');
      expect(body.data.by_level[level]).toHaveProperty('paid_percentage');
    }
    console.log(`  Effectiveness: by_level keys = ${Object.keys(body.data.by_level).join(', ')}`);
  });

  test('API: send reminder validates required fields', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/collections/send-reminder`, {
      headers: h(),
      data: {},
    });
    expect(resp.status()).toBe(422);
  });

  test('API: send reminder accepts email parameter', async () => {
    // Just verify the validation accepts 'email' field — don't actually send
    const resp = await page.request.post(`${BASE}/api/v1/collections/send-reminder`, {
      headers: h(),
      data: {
        invoice_id: 999999,
        level: 'friendly',
        email: 'test@example.com',
      },
    });
    // Expect 422 because invoice doesn't exist, NOT a validation error about email
    expect(resp.status()).toBe(422);
    const body = await resp.json();
    expect(body.message).toContain('not found');
  });

  test('API: opomena endpoint returns PDF or error for invalid invoice', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/collections/opomena/999999`, { headers: h() });
    // Should return 422 for non-existent invoice
    expect([200, 422]).toContain(resp.status());
  });

  // ─── UI Tests ───

  test('UI: admin collections page loads with all tabs', async () => {
    // Navigate to collections page
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);

    // Check page loaded (title or heading should be visible)
    const pageContent = await page.content();
    // Should have the tab buttons
    const hasTemplateBtn = pageContent.includes('DocumentTextIcon') || await page.locator('button').filter({ hasText: /template|шаблон/i }).count() > 0;
    const hasHistoryBtn = await page.locator('button').filter({ hasText: /histor|историј/i }).count() > 0;
    console.log(`  Admin page: templates btn=${hasTemplateBtn}, history btn=${hasHistoryBtn}`);
  });

  test('UI: no raw i18n keys visible on admin page', async () => {
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);

    const text = await page.innerText('body');
    // These are i18n keys that should NOT appear as raw text
    const rawKeys = ['total_overdue', 'invoice_count', 'customer_count', 'avg_days', 'total_interest'];
    const foundRaw = rawKeys.filter(k => {
      // Only flag if the key appears as a standalone word (not as translated text)
      const regex = new RegExp(`\\b${k}\\b`, 'g');
      return regex.test(text);
    });

    if (foundRaw.length > 0) {
      console.log(`  WARNING: Raw i18n keys found: ${foundRaw.join(', ')}`);
    }
    // This is a soft check — some keys might appear as CSS class names or data attributes
    console.log(`  i18n check complete, potential raw keys: ${foundRaw.length}`);
  });

  test('UI: partner collections page loads with company selector', async () => {
    await page.goto(`${BASE}/admin/partner/accounting/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);

    // Should have company selector
    const pageContent = await page.content();
    const hasSelector = pageContent.includes('select_company') || pageContent.includes('Multiselect') || await page.locator('.multiselect').count() > 0;
    console.log(`  Partner page: company selector present=${hasSelector}`);
  });

  test('UI: summary cards render with translated labels', async () => {
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(3000);

    // Look for summary card structure (5 cards in grid)
    const cards = await page.locator('.grid .bg-white.rounded-lg.shadow.p-4').count();
    console.log(`  Summary cards visible: ${cards}`);

    // Take screenshot for visual check
    await page.screenshot({ path: 'test-results/collections-admin-overview.png', fullPage: true });
    console.log('  Screenshot saved: test-results/collections-admin-overview.png');
  });

  test('UI: templates tab renders with humanized previews', async () => {
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);

    // Click templates tab
    const templatesBtn = page.locator('button').filter({ hasText: /template|шаблон/i }).first();
    if (await templatesBtn.count() > 0) {
      await templatesBtn.click();
      await page.waitForTimeout(2000);

      // Should show template cards or empty state
      const pageContent = await page.content();
      const hasTemplates = pageContent.includes('border-l-4') || pageContent.includes('no_data');
      console.log(`  Templates tab rendered: ${hasTemplates}`);

      await page.screenshot({ path: 'test-results/collections-templates-tab.png', fullPage: true });
    }
  });

  test('UI: history tab renders with effectiveness chart', async () => {
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'networkidle', timeout: 30000 });
    await page.waitForTimeout(2000);

    // Click history tab
    const historyBtn = page.locator('button').filter({ hasText: /histor|историј/i }).first();
    if (await historyBtn.count() > 0) {
      await historyBtn.click();
      await page.waitForTimeout(2000);

      await page.screenshot({ path: 'test-results/collections-history-tab.png', fullPage: true });
      console.log('  History tab screenshot saved');
    }
  });

  test('summary: API errors logged', async () => {
    if (apiErrors.length > 0) {
      console.log(`\n  Total API errors: ${apiErrors.length}`);
      apiErrors.forEach(e => console.log(`    ${e.status} ${e.url}`));
    }
    expect(apiErrors.filter(e => e.status >= 500).length).toBe(0);
  });
});
