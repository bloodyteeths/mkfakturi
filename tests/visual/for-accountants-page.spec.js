/**
 * For-Accountants Marketing Page — Production Smoke Tests
 *
 * Validates https://www.facturino.mk/{locale}/for-accountants in all 4 locales.
 *
 * Checks:
 *  1. Page loads (HTTP 200, no console errors)
 *  2. Six key sections visible: hero, pain points, solutions, year-end wizard,
 *     comparison table, pricing grid, bottom CTA
 *  3. Comparison table has 14 data rows with Facturino checkmarks
 *  4. No commission percentage (20%) anywhere in visible page text — CRITICAL
 *  5. Pricing grid shows 4 tiers (Start, Office, Pro, Elite)
 *  6. Monthly/Yearly toggle works and changes displayed prices
 *  7. Bottom CTA links to /partner/signup
 *  8. No broken images
 *
 * Usage:
 *   npx playwright test tests/visual/for-accountants-page.spec.js --project=chromium
 */

import { test, expect } from '@playwright/test';

const BASE = 'https://www.facturino.mk';

const LOCALES = [
  {
    code: 'mk',
    heroH1: /Една пријава/i,
    painH2: /познато ова/i,
    solutionsH2: /решава сите/i,
    yearEndH2: /Годишно затворање/i,
    comparisonH2: /наспроти конкуренцијата/i,
    pricingH2: /Пакети за сметководители/i,
    ctaH2: /Пробај бесплатно/i,
    monthly: 'Месечно',
    yearly: 'Годишно',
  },
  {
    code: 'sq',
    heroH1: /Një hyrje/i,
    painH2: /duket e njohur/i,
    solutionsH2: /i zgjidh/i,
    yearEndH2: /Mbyllja vjetore/i,
    comparisonH2: /kundrejt konkurrencës/i,
    pricingH2: /Paketat për kontabilistë/i,
    ctaH2: /Provoni falas/i,
    monthly: 'Mujore',
    yearly: 'Vjetore',
  },
  {
    code: 'tr',
    heroH1: /Tek giriş/i,
    painH2: /tanıdık/i,
    solutionsH2: /hepsini çözer/i,
    yearEndH2: /Yıl sonu kapanış/i,
    comparisonH2: /rakiplere karşı/i,
    pricingH2: /Muhasebeciler için paketler/i,
    ctaH2: /ücretsiz deneyin/i,
    monthly: 'Aylık',
    yearly: 'Yıllık',
  },
  {
    code: 'en',
    heroH1: /One login/i,
    painH2: /Sound familiar/i,
    solutionsH2: /solves them all/i,
    yearEndH2: /Year-end closing/i,
    comparisonH2: /vs the competition/i,
    pricingH2: /Plans for accountants/i,
    ctaH2: /Try free/i,
    monthly: 'Monthly',
    yearly: 'Yearly',
  },
];

for (const locale of LOCALES) {
  test.describe(`/${locale.code}/for-accountants`, () => {
    let consoleErrors;

    test.beforeEach(async ({ page }) => {
      consoleErrors = [];
      page.on('console', (msg) => {
        if (msg.type() === 'error') {
          consoleErrors.push(msg.text());
        }
      });
    });

    // ---------------------------------------------------------------
    // 1. Page loads without errors
    // ---------------------------------------------------------------
    test('page loads with HTTP 200 and no console errors', async ({ page }) => {
      const response = await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
        timeout: 30000,
      });
      expect(response.status()).toBe(200);

      const critical = consoleErrors.filter(
        (e) =>
          !e.includes('third-party') &&
          !e.includes('analytics') &&
          !e.includes('favicon') &&
          !e.includes('net::ERR_BLOCKED_BY_CLIENT') &&
          !e.includes('hydration')
      );
      expect(critical, `Unexpected console errors on ${locale.code} page`).toHaveLength(0);
    });

    // ---------------------------------------------------------------
    // 2. All key sections visible
    // ---------------------------------------------------------------
    test('all 6 key sections are visible', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      // Hero (h1)
      const h1 = page.locator('h1');
      await expect(h1).toBeVisible();
      await expect(h1).toHaveText(locale.heroH1);

      // Collect all h2 headings
      const h2s = page.locator('h2');
      const count = await h2s.count();
      expect(count).toBeGreaterThanOrEqual(5);

      const allH2Texts = [];
      for (let i = 0; i < count; i++) {
        allH2Texts.push(await h2s.nth(i).textContent());
      }
      const joined = allH2Texts.join(' ||| ');

      expect(joined).toMatch(locale.painH2);
      expect(joined).toMatch(locale.solutionsH2);
      expect(joined).toMatch(locale.yearEndH2);
      expect(joined).toMatch(locale.comparisonH2);
      expect(joined).toMatch(locale.pricingH2);
      expect(joined).toMatch(locale.ctaH2);
    });

    // ---------------------------------------------------------------
    // 3. Comparison table has 14 data rows with Facturino checkmarks
    // ---------------------------------------------------------------
    test('comparison table has 14 data rows with Facturino checkmarks', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      // 1 header + 14 data rows = 15 grid rows
      const tableRows = page.locator('div[class*="grid-cols-[1fr_auto_auto]"]');
      const rowCount = await tableRows.count();
      expect(rowCount).toBe(15);

      // Green checkmarks in the Facturino column
      const facturinoChecks = page.locator(
        'div[class*="grid-cols-[1fr_auto_auto]"] svg[class*="text-green"]'
      );
      const checkCount = await facturinoChecks.count();
      expect(checkCount).toBeGreaterThanOrEqual(14);
    });

    // ---------------------------------------------------------------
    // 4. No 20% commission in visible page text
    // ---------------------------------------------------------------
    test('no 20% commission mentioned on the page', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      // Only check VISIBLE text (excludes <script> JSON data)
      const visibleText = await page.evaluate(() => {
        const walker = document.createTreeWalker(
          document.body,
          NodeFilter.SHOW_TEXT,
          {
            acceptNode(node) {
              const el = node.parentElement;
              if (!el) return NodeFilter.FILTER_REJECT;
              const tag = el.tagName.toLowerCase();
              if (tag === 'script' || tag === 'style' || tag === 'noscript') {
                return NodeFilter.FILTER_REJECT;
              }
              const style = window.getComputedStyle(el);
              if (style.display === 'none' || style.visibility === 'hidden') {
                return NodeFilter.FILTER_REJECT;
              }
              return NodeFilter.FILTER_ACCEPT;
            },
          }
        );
        let text = '';
        while (walker.nextNode()) {
          text += walker.currentNode.textContent;
        }
        return text;
      });

      expect(visibleText).not.toContain('20%');
      expect(visibleText).not.toMatch(/20\s*%/);
    });

    // ---------------------------------------------------------------
    // 5. Pricing grid shows 4 tiers
    // ---------------------------------------------------------------
    test('pricing grid shows 4 tiers: Start, Office, Pro, Elite', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      const pricingGrid = page.locator(
        '.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4'
      );
      await expect(pricingGrid).toBeVisible();

      const cards = pricingGrid.locator('> div');
      expect(await cards.count()).toBe(4);

      const gridText = await pricingGrid.textContent();
      for (const tier of ['Start', 'Office', 'Pro', 'Elite']) {
        expect(gridText).toContain(tier);
      }
    });

    // ---------------------------------------------------------------
    // 6. Monthly/Yearly toggle works
    // ---------------------------------------------------------------
    test('monthly/yearly toggle switches pricing', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      // Use exact aria-label to avoid matching the hamburger menu toggle
      const toggleBtn = page.locator('button[aria-label="Toggle yearly billing"]');
      await expect(toggleBtn).toBeVisible();
      await toggleBtn.scrollIntoViewIfNeeded();
      await page.waitForTimeout(500);

      // Toggle knob starts at translate-x-0 (monthly mode)
      const knob = toggleBtn.locator('span');
      const initialClass = await knob.getAttribute('class');
      expect(initialClass).toContain('translate-x-0');

      // Capture initial pricing
      const pricingGrid = page.locator(
        '.grid.grid-cols-1.sm\\:grid-cols-2.lg\\:grid-cols-4'
      );
      const pricesBefore = await pricingGrid.textContent();

      // Click toggle -> switch to yearly
      await toggleBtn.click();
      await page.waitForTimeout(800);

      // Verify knob moved (no longer translate-x-0)
      const afterClass = await knob.getAttribute('class');
      expect(afterClass).not.toContain('translate-x-0');

      // Verify pricing text changed
      const pricesAfter = await pricingGrid.textContent();
      expect(pricesAfter).not.toBe(pricesBefore);

      // Click toggle -> switch back to monthly
      await toggleBtn.click();
      await page.waitForTimeout(800);

      const resetClass = await knob.getAttribute('class');
      expect(resetClass).toContain('translate-x-0');
    });

    // ---------------------------------------------------------------
    // 7. Bottom CTA links to /partner/signup
    // ---------------------------------------------------------------
    test('bottom CTA links to /partner/signup', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      const ctaLinks = page.locator('a[href*="partner/signup"]');
      const linkCount = await ctaLinks.count();
      expect(linkCount).toBeGreaterThanOrEqual(1);

      const lastCta = ctaLinks.last();
      await expect(lastCta).toBeVisible();

      const href = await lastCta.getAttribute('href');
      expect(href).toContain('partner/signup');
    });

    // ---------------------------------------------------------------
    // 8. No broken images
    // ---------------------------------------------------------------
    test('no broken images on the page', async ({ page }) => {
      await page.goto(`${BASE}/${locale.code}/for-accountants`, {
        waitUntil: 'networkidle',
      });

      // Scroll fully through the page to trigger lazy-loaded images
      await page.evaluate(async () => {
        const distance = 300;
        const delay = 150;
        const maxScroll = document.body.scrollHeight;
        for (let y = 0; y < maxScroll; y += distance) {
          window.scrollTo(0, y);
          await new Promise((r) => setTimeout(r, delay));
        }
        // Scroll to very bottom
        window.scrollTo(0, document.body.scrollHeight);
      });
      // Wait for all lazy images to finish loading
      await page.waitForTimeout(3000);

      // Verify all images have loaded
      const broken = await page.evaluate(() => {
        const imgs = Array.from(document.querySelectorAll('img'));
        const failures = [];
        for (const img of imgs) {
          const src = img.getAttribute('src') || '';
          // Skip data URIs
          if (src.startsWith('data:')) continue;
          // Skip invisible images (e.g., hidden by CSS)
          const rect = img.getBoundingClientRect();
          if (rect.width === 0 && rect.height === 0) continue;
          // Check if image loaded: naturalWidth > 0 means it decoded OK.
          // For Next.js fill images, also accept complete + currentSrc set.
          if (img.naturalWidth > 0) continue;
          if (img.complete && img.currentSrc && img.naturalWidth > 0) continue;
          // Image may use CSS object-fit/fill and have 0 natural dimensions
          // but still be rendered. Check via fetching.
          failures.push(img.currentSrc || img.src);
        }
        return failures;
      });

      expect(
        broken,
        `Broken images found: ${broken.join(', ')}`
      ).toHaveLength(0);
    });
  });
}
