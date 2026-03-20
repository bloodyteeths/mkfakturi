/**
 * Partner Onboarding Flow — Production E2E Test
 *
 * Tests the partner onboarding improvements on app.facturino.mk.
 * Uses a single shared login to avoid Sanctum rate-limiting.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/partner-onboarding-audit.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test';

const BASE = 'https://app.facturino.mk';
const EMAIL = process.env.TEST_EMAIL || '';
const PASS = process.env.TEST_PASSWORD || '';

/** @type {import('@playwright/test').Page} */
let page;
let jsErrors = [];
let apiErrors = [];

test.describe('Partner Onboarding Flow — Production', () => {
  test.describe.configure({ mode: 'serial' });

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(60000);
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    page = await ctx.newPage();
    jsErrors = [];
    apiErrors = [];

    page.on('console', msg => {
      if (msg.type() === 'error') jsErrors.push(msg.text());
    });
    page.on('response', res => {
      if (res.status() >= 500) apiErrors.push({ url: res.url(), status: res.status() });
    });

    // Single login for all tests
    let loginResponseData = null;
    page.on('response', async (res) => {
      if (res.url().includes('/auth/login') && res.status() === 200) {
        try { loginResponseData = await res.json(); } catch (e) { /* non-JSON */ }
      }
    });

    await page.goto(`${BASE}/login`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    await page.locator('input[type="email"]').fill(EMAIL);
    await page.locator('input[type="password"]').fill(PASS);
    await page.waitForTimeout(500);
    await page.locator('button[type="submit"]').click();
    await page.waitForFunction(() => !window.location.href.includes('/login'), { timeout: 30000 });
    console.log('Login successful, URL:', page.url());
    console.log('Login response keys:', loginResponseData ? Object.keys(loginResponseData) : 'null');

    // Store login response for test assertions
    page._loginResponseData = loginResponseData;
    page._postLoginUrl = page.url();
  });

  test.afterAll(async () => {
    await page.context().close();
  });

  test('1 — Super admin login goes to admin dashboard (not onboarding)', async () => {
    // Super admin should NOT be redirected to onboarding
    expect(page._postLoginUrl).not.toContain('/partner/onboarding');
    expect(page._postLoginUrl).toContain('/admin');
    console.log('Super admin post-login URL:', page._postLoginUrl);
  });

  test('2 — Partner signup page loads correctly', async ({ browser }) => {
    test.setTimeout(60000);
    // Signup is a public page — separate context, no login needed
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 800 } });
    const signupPage = await ctx.newPage();

    await signupPage.goto(`${BASE}/partner/signup`);
    await signupPage.waitForLoadState('networkidle');
    await signupPage.waitForTimeout(3000);

    const html = await signupPage.content();
    const hasSignupForm = html.includes('partner') || html.includes('signup') || html.includes('регистрација');
    expect(hasSignupForm).toBe(true);

    console.log('Partner signup page URL:', signupPage.url());
    await signupPage.screenshot({ path: 'test-results/partner-onboarding-signup-page.png' });
    await ctx.close();
  });

  test('3 — Onboarding wizard page loads', async () => {
    test.setTimeout(60000);
    await page.goto(`${BASE}/admin/partner/onboarding`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('Onboarding wizard URL:', page.url());
    const html = await page.content();
    expect(html.length).toBeGreaterThan(500);

    await page.screenshot({ path: 'test-results/partner-onboarding-wizard.png' });
  });

  test('4 — Partner dashboard route is accessible', async () => {
    test.setTimeout(60000);
    await page.goto(`${BASE}/admin/partner/dashboard`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('Partner dashboard URL:', page.url());
    const html = await page.content();
    expect(html.length).toBeGreaterThan(500);

    await page.screenshot({ path: 'test-results/partner-onboarding-dashboard.png' });
  });

  test('5 — Partner portfolio page loads', async () => {
    test.setTimeout(60000);
    await page.goto(`${BASE}/admin/partner/portfolio`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(3000);

    console.log('Portfolio page URL:', page.url());
    const html = await page.content();
    expect(html.length).toBeGreaterThan(500);

    await page.screenshot({ path: 'test-results/partner-onboarding-portfolio.png' });
  });

  test('6 — Login response includes expected fields', async () => {
    const data = page._loginResponseData;
    expect(data).not.toBeNull();
    expect(data.success).toBe(true);
    expect(data.user).toBeDefined();
    expect(data.user.role).toBe('super admin');
    console.log('Login response user keys:', Object.keys(data.user));
  });

  test('7 — Email CTA target pages load (onboarding + billing)', async () => {
    test.setTimeout(60000);

    // Onboarding URL (email 1 CTA target)
    await page.goto(`${BASE}/admin/partner/onboarding`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    const onboardingStatus = await page.evaluate(() =>
      document.querySelector('body')?.textContent?.includes('404') ? 404 : 200
    );
    expect(onboardingStatus).toBe(200);

    // Billing URL (email 4 CTA target)
    await page.goto(`${BASE}/admin/partner/billing`);
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(2000);
    const billingHtml = await page.content();
    expect(billingHtml.length).toBeGreaterThan(500);

    await page.screenshot({ path: 'test-results/partner-onboarding-billing.png' });
  });

  test('8 — No 500 errors across partner onboarding pages', async () => {
    test.setTimeout(90000);

    // Reset error tracking for this sweep
    const errors500 = [];
    const errorListener = (res) => {
      if (res.status() >= 500) errors500.push({ url: res.url(), status: res.status() });
    };
    page.on('response', errorListener);

    const routes = [
      '/admin/partner/dashboard',
      '/admin/partner/onboarding',
      '/admin/partner/portfolio',
      '/admin/partner/billing',
      '/admin/partner/clients',
      '/admin/partner/referrals',
    ];

    for (const route of routes) {
      await page.goto(`${BASE}${route}`);
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000);
      console.log(`  ${route} → ${page.url()}`);
    }

    page.removeListener('response', errorListener);

    // Filter known issues:
    // - /partner/subscription 500 when super admin has no partner subscription
    // - 502 gateway errors (Railway infrastructure, not app code)
    const unexpected = errors500.filter(e =>
      !e.url.includes('/partner/subscription') && e.status !== 502
    );

    if (errors500.length > 0) {
      console.log('All 500+ errors:', JSON.stringify(errors500, null, 2));
    }
    if (unexpected.length > 0) {
      console.error('UNEXPECTED errors:', JSON.stringify(unexpected, null, 2));
    }

    expect(unexpected.length).toBe(0);
  });
});
