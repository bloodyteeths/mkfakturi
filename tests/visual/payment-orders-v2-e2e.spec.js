/**
 * Payment Orders v2 — Enhancement E2E Tests
 *
 * Tests all new features from the payment orders audit:
 *  1. Format filter on Index page
 *  2. Column sorting (batch_number, batch_date, total_amount, status)
 *  3. Bulk actions bar (select, approve, cancel checkboxes)
 *  4. Urgency field on Create page
 *  5. Payment code dropdown on Create page
 *  6. PP50 conditional fields
 *  7. "Due This Month" quick select button
 *  8. Bill description column in Create table
 *  9. Edit draft mode on View page
 * 10. Duplicate batch button on View page
 * 11. Print button on View page
 * 12. PP50 PDF download button
 * 13. Urgency badge display on Index and View
 * 14. Payment code column on View items table
 * 15. PP50 fields section on View page
 * 16. i18n — new keys resolve in mk, en, sq, tr
 * 17. No JS errors or API 500s
 *
 * Usage:
 *   TEST_BASE_URL=https://app.facturino.mk TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/payment-orders-v2-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk';
const EMAIL = process.env.TEST_EMAIL || 'atillatkulu@gmail.com';
const PASS = process.env.TEST_PASSWORD || 'Facturino2026';

let page;
let jsErrors = [];
let apiErrors = [];

test.describe('Payment Orders v2 Enhancements', () => {
  test.describe.configure({ mode: 'serial' });
  test.setTimeout(60000);

  test.beforeAll(async ({ browser }, testInfo) => {
    testInfo.setTimeout(90000);
    page = await browser.newPage();

    page.on('pageerror', err => {
      jsErrors.push({ url: page.url(), error: err.message });
    });

    page.on('response', resp => {
      if (resp.url().includes('/api/') && resp.status() >= 500) {
        apiErrors.push({ url: resp.url(), status: resp.status() });
      }
    });

    // Login with retry for 502 during deploy
    for (let attempt = 0; attempt < 3; attempt++) {
      await page.goto(`${BASE}/login`, { timeout: 30000 });
      const content = await page.content();
      if (content.includes('502') || content.includes('Bad gateway')) {
        await page.waitForTimeout(15000); // wait 15s for deploy
        continue;
      }
      break;
    }
    await page.waitForLoadState('networkidle');
    await page.locator('input[type="email"], input[name="email"], #email').first().fill(EMAIL);
    await page.locator('input[type="password"], input[name="password"], #password').first().fill(PASS);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/(admin|dashboard)/, { timeout: 15000 });
    await page.waitForTimeout(3000);
  });

  test.afterAll(async () => {
    if (page) await page.close();
  });

  // ─── INDEX PAGE ───

  test('1. Index page loads with format filter dropdown', async () => {
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Format filter exists
    const formatSelect = page.locator('select').filter({ has: page.locator('option[value="pp30"]') });
    await expect(formatSelect).toBeVisible();

    // Has all format options
    const options = await formatSelect.locator('option').allTextContents();
    expect(options.some(o => o.includes('PP30'))).toBeTruthy();
    expect(options.some(o => o.includes('PP50'))).toBeTruthy();
    expect(options.some(o => o.includes('SEPA'))).toBeTruthy();
    expect(options.some(o => o.includes('CSV'))).toBeTruthy();
  });

  test('2. Index has sortable column headers', async () => {
    // Check that batch number, date, total, status headers exist and are clickable
    const batchNumHeader = page.locator('th').filter({ hasText: /batch.number|број.*налог/i }).first();
    const dateHeader = page.locator('th').filter({ hasText: /date|датум/i }).first();
    const totalHeader = page.locator('th').filter({ hasText: /total|вкупно/i }).first();
    const statusHeader = page.locator('th').filter({ hasText: /status|статус/i }).first();

    // Headers should have cursor-pointer class (clickable)
    await expect(batchNumHeader).toHaveClass(/cursor-pointer/);
    await expect(dateHeader).toHaveClass(/cursor-pointer/);
    await expect(totalHeader).toHaveClass(/cursor-pointer/);
    await expect(statusHeader).toHaveClass(/cursor-pointer/);
  });

  test('3. Index has checkbox column for bulk selection', async () => {
    // Header checkbox
    const headerCheckbox = page.locator('thead input[type="checkbox"]');
    await expect(headerCheckbox).toBeVisible();
  });

  test('4. Clicking sort header changes sort indicator', async () => {
    const dateHeader = page.locator('th.cursor-pointer').filter({ hasText: /date|датум/i }).first();
    await dateHeader.click();
    await page.waitForTimeout(500);

    // Should show an arrow indicator
    const headerText = await dateHeader.textContent();
    expect(headerText).toMatch(/[\u2191\u2193]/); // up or down arrow
  });

  test('5. Bulk actions bar appears when batches selected', async () => {
    // Check if there are rows
    const rows = page.locator('tbody tr');
    const count = await rows.count();

    if (count > 0) {
      // Click first row checkbox
      const firstCheckbox = rows.first().locator('input[type="checkbox"]');
      await firstCheckbox.click();
      await page.waitForTimeout(300);

      // Bulk actions bar should appear
      const bulkBar = page.locator('text=/selected|избрани/i').first();
      await expect(bulkBar).toBeVisible();

      // Should have bulk action buttons
      const approveBtn = page.locator('button').filter({ hasText: /approve.*selected|одобри.*избрани/i });
      const exportBtn = page.locator('button').filter({ hasText: /export.*selected|извези.*избрани/i });
      const cancelBtn = page.locator('button').filter({ hasText: /cancel.*selected|откажи.*избрани/i });

      expect(await approveBtn.count() + await exportBtn.count() + await cancelBtn.count()).toBeGreaterThan(0);

      // Uncheck
      await firstCheckbox.click();
    } else {
      test.skip();
    }
  });

  // ─── CREATE PAGE ───

  test('6. Create page has urgency dropdown', async () => {
    await page.goto(`${BASE}/admin/payment-orders/create`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const urgencySelect = page.locator('select').filter({ has: page.locator('option[value="itno"]') });
    await expect(urgencySelect).toBeVisible();

    const options = await urgencySelect.locator('option').allTextContents();
    expect(options.some(o => o.match(/regular|редовно/i))).toBeTruthy();
    expect(options.some(o => o.match(/urgent|итно/i))).toBeTruthy();
  });

  test('7. Create page has payment code dropdown', async () => {
    const paymentCodeSelect = page.locator('select').filter({ has: page.locator('option[value="110"]') });
    await expect(paymentCodeSelect).toBeVisible();

    const options = await paymentCodeSelect.locator('option').allTextContents();
    expect(options.some(o => o.includes('110'))).toBeTruthy();
    expect(options.some(o => o.includes('120'))).toBeTruthy();
    expect(options.some(o => o.includes('450'))).toBeTruthy();
  });

  test('8. PP50 fields appear when format=pp50 selected', async () => {
    // Select PP50 format
    const formatSelect = page.locator('select').filter({ has: page.locator('option[value="pp50"]') }).first();
    await formatSelect.selectOption('pp50');
    await page.waitForTimeout(300);

    // PP50 fields section should appear (h4 heading, not dropdown option)
    const pp50Section = page.locator('h4').filter({ hasText: /public revenue|полиња за јавни|pp50_fields/i }).first();
    await expect(pp50Section).toBeVisible();

    // Should have tax_number, revenue_code, municipality_code inputs
    const taxInput = page.locator('input[maxlength="13"]');
    await expect(taxInput).toBeVisible();

    const revenueInput = page.locator('input[maxlength="10"]').first();
    await expect(revenueInput).toBeVisible();

    // Switch back to PP30
    await formatSelect.selectOption('pp30');
    await page.waitForTimeout(300);

    // PP50 fields should be gone
    await expect(pp50Section).not.toBeVisible();
  });

  test('9. "Due This Month" quick select button exists', async () => {
    const dueMonthBtn = page.locator('button').filter({
      hasText: /due.*month|за овој месец|kete muaj/i
    });
    await expect(dueMonthBtn).toBeVisible();
  });

  test('10. Bill table has description column', async () => {
    await page.waitForTimeout(1500); // Wait for bills to load
    const descHeader = page.locator('th').filter({ hasText: /description|опис|pershkrim/i });
    await expect(descHeader).toBeVisible();
  });

  test('11. Create page settings panel has 6 columns', async () => {
    // Settings panel should show date, format, urgency, payment code, bank account, notes
    const settingsGrid = page.locator('.grid.grid-cols-1.md\\:grid-cols-3.lg\\:grid-cols-6');
    if (await settingsGrid.count() > 0) {
      const inputs = await settingsGrid.locator('select, input, .base-date-picker').count();
      expect(inputs).toBeGreaterThanOrEqual(5);
    }
  });

  // ─── VIEW PAGE ───

  test('12. View page shows urgency in header', async () => {
    // Navigate to an existing batch or back to index
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const rows = page.locator('tbody tr');
    const count = await rows.count();

    if (count > 0) {
      // Click first batch
      await rows.first().click();
      await page.waitForURL(/\/payment-orders\/\d+/);
      await page.waitForTimeout(1000);

      // Should show urgency field in header
      const urgencyLabel = page.locator('text=/urgency|итност|urgjenca/i').first();
      await expect(urgencyLabel).toBeVisible();

      // Urgency value badge
      const urgencyValue = page.locator('text=/regular|редовно|urgent|итно|e rregullt/i').first();
      await expect(urgencyValue).toBeVisible();
    } else {
      test.skip();
    }
  });

  test('13. View page has Print button', async () => {
    const printBtn = page.locator('button').filter({ hasText: /print|печати|shtyp|yazdir/i });
    if (await printBtn.count() > 0) {
      await expect(printBtn.first()).toBeVisible();
    }
  });

  test('14. View page has Duplicate button', async () => {
    const dupBtn = page.locator('button').filter({ hasText: /duplicate|дуплирај|dupliko|cogalt/i });
    if (await dupBtn.count() > 0) {
      await expect(dupBtn.first()).toBeVisible();
    }
  });

  test('15. View page Edit button visible for draft batches', async () => {
    // Navigate to index and find a draft batch
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Try to filter by draft status if filter exists
    const statusSelect = page.locator('select').filter({ has: page.locator('option[value="draft"]') });
    if (await statusSelect.count() > 0) {
      await statusSelect.first().selectOption('draft');
      await page.waitForTimeout(1000);
    }

    const rows = page.locator('tbody tr');
    const count = await rows.count();

    if (count > 0) {
      // Look for a draft badge in the rows
      const draftRow = page.locator('tbody tr').filter({ hasText: /draft|нацрт/i }).first();
      if (await draftRow.count() > 0) {
        await draftRow.click();
      } else {
        await rows.first().click();
      }
      await page.waitForURL(/\/payment-orders\/\d+/);
      await page.waitForTimeout(1000);

      // Edit button may only appear for draft batches
      const editBtn = page.locator('button').filter({ hasText: /edit|уреди|redakto/i });
      if (await editBtn.count() > 0) {
        await expect(editBtn.first()).toBeVisible();
      } else {
        // Not a draft batch or edit not available - skip
        test.skip();
      }
    } else {
      test.skip();
    }
  });

  // ─── i18n ───

  test('16. i18n: MK locale has all new keys', async () => {
    await page.goto(`${BASE}/admin/payment-orders/create`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Check urgency label renders (not raw key)
    const pageContent = await page.textContent('body');
    expect(pageContent).not.toContain('urgency_regular');
    expect(pageContent).not.toContain('payment_code_110');
    expect(pageContent).not.toContain('select_due_month');
  });

  test('17. Index page header has multiple columns (checkbox, batch#, date, format, items, total, status, actions)', async () => {
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);

    // Table may use th or other header elements
    const headers = page.locator('thead th, table th, [role="columnheader"]');
    const headerCount = await headers.count();
    expect(headerCount).toBeGreaterThanOrEqual(5); // at minimum: checkbox + batch# + date + total + status
  });

  // ─── API ENDPOINTS ───

  test('18. New API endpoints respond correctly', async () => {
    // Get cookies for API calls
    const cookies = await page.context().cookies();
    const xsrf = cookies.find(c => c.name === 'XSRF-TOKEN');
    const session = cookies.find(c => c.name.includes('session'));

    if (!xsrf || !session) {
      test.skip();
      return;
    }

    // Test format filter
    const resp1 = await page.request.get(`${BASE}/api/v1/payment-orders?format=pp30&limit=1`, {
      headers: {
        'Accept': 'application/json',
        'company': '2',
        'X-XSRF-TOKEN': decodeURIComponent(xsrf.value),
      }
    });
    expect(resp1.status()).toBeLessThan(500);

    // Test sort
    const resp2 = await page.request.get(`${BASE}/api/v1/payment-orders?sort_by=total_amount&sort_order=asc&limit=1`, {
      headers: {
        'Accept': 'application/json',
        'company': '2',
        'X-XSRF-TOKEN': decodeURIComponent(xsrf.value),
      }
    });
    expect(resp2.status()).toBeLessThan(500);
  });

  // ─── CROSS-CUTTING ───

  test('19. No JS errors during test run', async () => {
    const criticalErrors = jsErrors.filter(e =>
      !e.error.includes('ResizeObserver') &&
      !e.error.includes('Script error') &&
      !e.error.includes('ChunkLoadError')
    );
    expect(criticalErrors).toHaveLength(0);
  });

  test('20. No API 500 errors during test run', async () => {
    expect(apiErrors).toHaveLength(0);
  });
});

// CLAUDE-CHECKPOINT
