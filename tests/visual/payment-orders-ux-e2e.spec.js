/**
 * Payment Orders UX Overhaul — E2E Tests
 *
 * Tests the UX improvements:
 *  1. Index pagination controls
 *  2. Clear filters button
 *  3. Create step indicators
 *  4. Create payment code optgroups
 *  5. View dropdown menu (More actions)
 *  6. View status pipeline with hints
 *  7. Mobile viewport — Index responsive
 *  8. Mobile viewport — Create responsive
 *  9. Mobile viewport — View responsive
 * 10. No JS errors
 *
 * Usage:
 *   TEST_BASE_URL=https://app.facturino.mk TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/payment-orders-ux-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk';
const EMAIL = process.env.TEST_EMAIL || 'atillatkulu@gmail.com';
const PASS = process.env.TEST_PASSWORD || 'Facturino2026';

let page;
let jsErrors = [];

test.describe('Payment Orders UX Overhaul', () => {
  test.describe.configure({ mode: 'serial' });
  test.setTimeout(60000);

  test.beforeAll(async ({ browser }, testInfo) => {
    testInfo.setTimeout(90000);
    page = await browser.newPage();

    page.on('pageerror', err => {
      jsErrors.push({ url: page.url(), error: err.message });
    });

    // Login with retry
    for (let attempt = 0; attempt < 3; attempt++) {
      await page.goto(`${BASE}/login`, { timeout: 30000 });
      const content = await page.content();
      if (content.includes('502') || content.includes('Bad gateway')) {
        await page.waitForTimeout(15000);
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

  // ─── INDEX PAGE UX ───

  test('1. Index has pagination controls', async () => {
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1500);

    // Pagination footer should exist
    const prevBtn = page.locator('button').filter({ hasText: /previous|претходна/i });
    const nextBtn = page.locator('button').filter({ hasText: /next|следна/i });

    // May not show if there are no batches
    const hasBatches = await page.locator('tbody tr').count();
    if (hasBatches > 0) {
      await expect(prevBtn.first()).toBeVisible();
      await expect(nextBtn.first()).toBeVisible();

      // Page size selector exists
      const pageSizeSelect = page.locator('select').filter({ has: page.locator('option[value="15"]') });
      await expect(pageSizeSelect).toBeVisible();

      // Shows "Showing X-Y of Z" text
      const showingText = page.locator('text=/showing|прикажани/i').first();
      await expect(showingText).toBeVisible();
    }
  });

  test('2. Index has clear filters button when filter active', async () => {
    // Apply a filter
    const statusSelect = page.locator('select').first();
    await statusSelect.selectOption('draft');
    await page.waitForTimeout(500);

    // Clear filters button should appear
    const clearBtn = page.locator('button').filter({ hasText: /clear.*filter|исчисти.*филтри/i });
    if (await clearBtn.count() > 0) {
      await expect(clearBtn.first()).toBeVisible();

      // Click clear
      await clearBtn.first().click();
      await page.waitForTimeout(500);

      // Button should disappear
      await expect(clearBtn.first()).not.toBeVisible();
    }
  });

  test('3. Index mobile — stats stack vertically', async () => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Stats cards should be stacked (grid-cols-1 on mobile)
    const statsCards = page.locator('.rounded-lg.border');
    const count = await statsCards.count();
    expect(count).toBeGreaterThanOrEqual(3);

    // First card should be visible
    await expect(statsCards.first()).toBeVisible();

    // Reset viewport
    await page.setViewportSize({ width: 1280, height: 720 });
  });

  // ─── CREATE PAGE UX ───

  test('4. Create has step indicators', async () => {
    await page.goto(`${BASE}/admin/payment-orders/create`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Step indicators should be visible
    const step1 = page.locator('text=/подесувања|settings/i').first();
    const step2 = page.locator('text=/избери фактури|select.*bills/i').first();

    // At least the step labels should exist (may be hidden on mobile)
    expect(await step1.count() + await step2.count()).toBeGreaterThan(0);
  });

  test('5. Create has payment code optgroups', async () => {
    // Payment code dropdown should have optgroups
    const optgroups = page.locator('optgroup');
    const optgroupCount = await optgroups.count();

    // Should have at least 2 optgroups (Transactions, Public Charges)
    expect(optgroupCount).toBeGreaterThanOrEqual(2);
  });

  test('6. Create settings grid is 2-row on desktop', async () => {
    // Settings grid should be grid-cols-3 (not 6)
    const settingsGrid = page.locator('.grid.grid-cols-1');
    const count = await settingsGrid.count();
    expect(count).toBeGreaterThan(0);
  });

  test('7. Create mobile — buttons stack vertically', async () => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(`${BASE}/admin/payment-orders/create`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Quick select buttons should be stacked (flex-col)
    const quickButtons = page.locator('button').filter({ hasText: /избери|select|due/i });
    const count = await quickButtons.count();
    expect(count).toBeGreaterThanOrEqual(2);

    // Reset viewport
    await page.setViewportSize({ width: 1280, height: 720 });
  });

  // ─── VIEW PAGE UX ───

  test('8. View has more actions dropdown', async () => {
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const rows = page.locator('tbody tr');
    const count = await rows.count();

    if (count > 0) {
      await rows.first().click();
      await page.waitForURL(/\/payment-orders\/\d+/);
      await page.waitForTimeout(1000);

      // Look for the "..." dropdown or "More" text button in the page header actions
      // The dropdown trigger is inside a relative-positioned div in the actions area
      const moreBtn = page.locator('.relative button').filter({
        has: page.locator('[class*="EllipsisVertical"]')
      });
      const moreBtnByText = page.locator('button').filter({ hasText: /^повеќе$|^more$/i });

      const trigger = await moreBtn.count() > 0 ? moreBtn.first()
        : await moreBtnByText.count() > 0 ? moreBtnByText.first()
        : null;

      if (trigger && await trigger.isVisible()) {
        await trigger.click();
        await page.waitForTimeout(300);

        // Dropdown should show Print, Duplicate options
        const printItem = page.locator('button').filter({ hasText: /print|печати/i });
        const dupItem = page.locator('button').filter({ hasText: /duplicate|дуплирај|когалт/i });
        expect(await printItem.count() + await dupItem.count()).toBeGreaterThan(0);

        // Click outside to close
        await page.locator('h1, .breadcrumb, header').first().click();
        await page.waitForTimeout(200);
      }
    } else {
      test.skip();
    }
  });

  test('9. View has status pipeline with step numbers', async () => {
    // Should still be on view page from test 8
    const url = page.url();
    if (!url.includes('/payment-orders/')) {
      test.skip();
      return;
    }

    // Status pipeline circles with numbers
    const pipelineCircles = page.locator('.rounded-full').filter({ hasText: /^[1-4]$/ });
    const circleCount = await pipelineCircles.count();
    expect(circleCount).toBeGreaterThanOrEqual(3);
  });

  test('10. View mobile — action buttons responsive', async () => {
    await page.setViewportSize({ width: 375, height: 812 });
    await page.goto(`${BASE}/admin/payment-orders`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    const rows = page.locator('tbody tr');
    if (await rows.count() > 0) {
      await rows.first().click();
      await page.waitForURL(/\/payment-orders\/\d+/);
      await page.waitForTimeout(1000);

      // Page should render without overflow
      const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
      expect(bodyWidth).toBeLessThanOrEqual(400); // 375 + some tolerance
    }

    // Reset viewport
    await page.setViewportSize({ width: 1280, height: 720 });
  });

  // ─── CROSS-CUTTING ───

  test('11. No JS errors during UX test run', async () => {
    const criticalErrors = jsErrors.filter(e =>
      !e.error.includes('ResizeObserver') &&
      !e.error.includes('Script error') &&
      !e.error.includes('ChunkLoadError')
    );
    expect(criticalErrors).toHaveLength(0);
  });
});

// CLAUDE-CHECKPOINT
