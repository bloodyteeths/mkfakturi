/**
 * Interest Feature — Playwright Functional + API Test
 *
 * Uses Bearer token auth for all API tests.
 * Requires API_TOKEN env var or creates one via Sanctum login.
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

test.describe('Interest Feature Audit', () => {
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

  // ─── API TESTS ─────────────────────────────

  test('GET /interest returns 200 with pagination', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBe(true);
    expect(body).toHaveProperty('data');
    expect(body).toHaveProperty('meta');
    expect(body.meta).toHaveProperty('current_page');
    expect(body.meta).toHaveProperty('last_page');
    expect(body.meta).toHaveProperty('per_page');
    expect(body.meta).toHaveProperty('total');
    console.log(`  /interest: ${body.meta.total} total, page ${body.meta.current_page}/${body.meta.last_page}`);
  });

  test('GET /interest/summary returns complete structure', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest/summary`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBe(true);
    expect(body.data).toHaveProperty('total_interest');
    expect(body.data).toHaveProperty('annual_rate');
    expect(body.data).toHaveProperty('is_custom_rate');
    expect(body.data).toHaveProperty('default_rate');
    expect(typeof body.data.annual_rate).toBe('number');
    expect(typeof body.data.is_custom_rate).toBe('boolean');
    console.log(`  /summary: rate=${body.data.annual_rate}%, custom=${body.data.is_custom_rate}, total=${body.data.total_interest}`);
  });

  test('GET /interest/rate returns correct structure', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest/rate`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.data.default_rate).toBe(13.25);
    expect(body.data).toHaveProperty('annual_rate');
    expect(body.data).toHaveProperty('is_custom');
    console.log(`  /rate: annual=${body.data.annual_rate}%, custom=${body.data.is_custom}`);
  });

  test('POST /interest/rate rejects rate > 100', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/interest/rate`, {
      headers: h(), data: { annual_rate: 150 },
    });
    expect(resp.status()).toBe(422);
  });

  test('POST /interest/rate rejects negative rate', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/interest/rate`, {
      headers: h(), data: { annual_rate: -5 },
    });
    expect(resp.status()).toBe(422);
  });

  test('POST /interest/rate rejects missing rate', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/interest/rate`, {
      headers: h(), data: {},
    });
    expect(resp.status()).toBe(422);
  });

  test('POST /interest/rate saves custom rate + GET verifies', async () => {
    // Save
    const postResp = await page.request.post(`${BASE}/api/v1/interest/rate`, {
      headers: h(), data: { annual_rate: 10.5 },
    });
    expect(postResp.status()).toBe(200);
    const postBody = await postResp.json();
    expect(postBody.data.annual_rate).toBe(10.5);

    // Verify
    const getResp = await page.request.get(`${BASE}/api/v1/interest/rate`, { headers: h() });
    const getBody = await getResp.json();
    expect(getBody.data.annual_rate).toBe(10.5);
    expect(getBody.data.is_custom).toBe(true);
    console.log('  Set 10.5% -> GET confirms is_custom=true');
  });

  test('DELETE /interest/rate resets to statutory + GET verifies', async () => {
    // Reset
    const delResp = await page.request.delete(`${BASE}/api/v1/interest/rate`, { headers: h() });
    expect(delResp.status()).toBe(200);
    const delBody = await delResp.json();
    expect(delBody.data.annual_rate).toBe(13.25);

    // Verify
    const getResp = await page.request.get(`${BASE}/api/v1/interest/rate`, { headers: h() });
    const getBody = await getResp.json();
    expect(getBody.data.annual_rate).toBe(13.25);
    expect(getBody.data.is_custom).toBe(false);
    console.log('  DELETE -> GET confirms reset to 13.25%, is_custom=false');
  });

  test('GET /interest with date filters', async () => {
    const resp = await page.request.get(
      `${BASE}/api/v1/interest?date_from=2025-01-01&date_to=2026-12-31`,
      { headers: h() }
    );
    expect(resp.status()).toBe(200);
  });

  test('GET /interest with status filter', async () => {
    for (const status of ['calculated', 'invoiced', 'paid', 'waived']) {
      const resp = await page.request.get(`${BASE}/api/v1/interest?status=${status}`, { headers: h() });
      expect(resp.status()).toBe(200);
    }
    console.log('  All 4 status filters return 200');
  });

  test('GET /interest with customer_id filter', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest?customer_id=999999`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.data).toEqual([]);
  });

  test('GET /interest?limit=all returns unpaginated', async () => {
    const resp = await page.request.get(`${BASE}/api/v1/interest?limit=all`, { headers: h() });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBe(true);
    // No meta when limit=all
    expect(body.meta).toBeUndefined();
  });

  test('POST /interest/generate-note validates required fields', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/interest/generate-note`, {
      headers: h(), data: {},
    });
    expect(resp.status()).toBe(422);
  });

  test('POST /interest/calculate works', async () => {
    const resp = await page.request.post(`${BASE}/api/v1/interest/calculate`, {
      headers: h(), data: {},
    });
    // Should return 200 (even if 0 overdue invoices)
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body.success).toBe(true);
    expect(body).toHaveProperty('annual_rate');
    console.log(`  /calculate: ${body.data.length} calculations, rate=${body.annual_rate}%`);
  });

  // ─── PAGES LOAD WITHOUT 500 ────────────────
  test('Admin page responds (no 500)', async () => {
    const resp = await page.request.get(`${BASE}/admin/interest`);
    expect(resp.status()).toBeLessThan(500);
    console.log(`  Admin page: ${resp.status()}`);
  });

  test('Partner page responds (no 500)', async () => {
    const resp = await page.request.get(`${BASE}/admin/partner/accounting/interest`);
    expect(resp.status()).toBeLessThan(500);
    console.log(`  Partner page: ${resp.status()}`);
  });

  // ─── ERROR SUMMARY ─────────────────────────
  test('No API 404/500 errors during test run', async () => {
    expect(apiErrors.length).toBe(0);
  });
});
