/**
 * Manufacturing Module v7 — Planning, Dashboard, BOM Cost, Import, Co-Production & AI E2E Tests
 *
 * Tests planning endpoints, dashboard, BOM cost, import execute, co-production PDF,
 * work center OEE, and AI endpoints against Company 2 on production (app.facturino.mk).
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/manufacturing-v7-planning-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'manufacturing-v7-planning-screenshots')

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })
}

async function ss(page, name) {
  await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${name}.png`), fullPage: true })
}

async function api(page, method, url, body = null) {
  return page.evaluate(
    async ({ method, url, body }) => {
      const xsrf = decodeURIComponent(
        document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=').slice(1).join('=') || ''
      )
      const opts = {
        method,
        credentials: 'same-origin',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          company: '2',
          ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
        },
      }
      if (body) opts.body = JSON.stringify(body)
      const res = await fetch(`/api/v1${url}`, opts)
      const text = await res.text()
      let parsed = null
      try { parsed = JSON.parse(text) } catch {}
      return { status: res.status, body: parsed, raw: text.substring(0, 500) }
    },
    { method, url, body }
  )
}

async function checkPdf(page, url) {
  return page.evaluate(async (url) => {
    const xsrf = decodeURIComponent(
      document.cookie.split('; ').find(c => c.startsWith('XSRF-TOKEN='))?.split('=').slice(1).join('=') || ''
    )
    const res = await fetch(`/api/v1${url}`, {
      credentials: 'same-origin',
      headers: {
        Accept: 'application/pdf',
        company: '2',
        ...(xsrf ? { 'X-XSRF-TOKEN': xsrf } : {}),
      },
    })
    return {
      status: res.status,
      contentType: res.headers.get('content-type'),
      size: parseInt(res.headers.get('content-length') || '0')
    }
  }, url)
}

test.describe('Manufacturing v7 — Planning, Dashboard, BOM Cost, Import, Co-Production & AI', () => {
  test.describe.configure({ mode: 'serial' })

  let page
  let orderId = null
  let bomId = null
  let workCenterId = null

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage()
    await page.goto(`${BASE}/login`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/admin/dashboard', { timeout: 15000 })
    await page.waitForTimeout(1000)
  })

  test.afterAll(async () => {
    if (page) await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SETUP (0.x) — Fetch first order, BOM, and work center IDs
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('0.1 — Fetch first production order, BOM, and work center', async () => {
    // Fetch first order
    const orderRes = await api(page, 'GET', '/manufacturing/orders?limit=1')
    expect(orderRes.status).toBe(200)
    const orders = orderRes.body?.data || []
    expect(orders.length).toBeGreaterThan(0)
    orderId = orders[0].id
    console.log(`  Order ID: ${orderId}`)

    // Fetch first BOM
    const bomRes = await api(page, 'GET', '/manufacturing/boms?limit=1')
    expect(bomRes.status).toBe(200)
    const boms = bomRes.body?.data || []
    expect(boms.length).toBeGreaterThan(0)
    bomId = boms[0].id
    console.log(`  BOM ID: ${bomId}`)

    // Fetch first work center
    const wcRes = await api(page, 'GET', '/manufacturing/work-centers?limit=1')
    expect(wcRes.status).toBe(200)
    const wcs = wcRes.body?.data || []
    if (wcs.length > 0) {
      workCenterId = wcs[0].id
      console.log(`  Work Center ID: ${workCenterId}`)
    } else {
      // Create a work center if none exists
      const createRes = await api(page, 'POST', '/manufacturing/work-centers', {
        name: 'Test Work Center',
        capacity: 100,
      })
      expect([200, 201]).toContain(createRes.status)
      workCenterId = createRes.body?.data?.id
      console.log(`  Created Work Center ID: ${workCenterId}`)
    }

    expect(orderId).toBeTruthy()
    expect(bomId).toBeTruthy()
    expect(workCenterId).toBeTruthy()

    // Store on page for cross-test reference
    page._ = { orderId, bomId, workCenterId }

    await ss(page, '0.1-setup-ids-fetched')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // DASHBOARD & PLANNING (1.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1.1 — GET /manufacturing/dashboard — verify 200 with data', async () => {
    const res = await api(page, 'GET', '/manufacturing/dashboard')
    expect(res.status).toBe(200)

    const data = res.body?.data || res.body
    expect(data).toBeTruthy()

    // Dashboard should return an object with some keys
    const keys = Object.keys(data)
    console.log(`  Dashboard keys: ${keys.join(', ')}`)
    expect(keys.length).toBeGreaterThan(0)

    await ss(page, '1.1-dashboard-data')
  })

  test('1.2 — POST /manufacturing/smart-reorder — empty items array', async () => {
    const res = await api(page, 'POST', '/manufacturing/smart-reorder', {
      items: [],
    })

    // 200 (success with empty result) or 422 (validation: items required) are both valid
    expect([200, 422]).toContain(res.status)
    console.log(`  Smart reorder status: ${res.status}`)

    await ss(page, '1.2-smart-reorder-empty')
  })

  test('1.3 — GET /manufacturing/net-requirements — verify 200 with data array', async () => {
    const res = await api(page, 'GET', '/manufacturing/net-requirements?limit=10')
    expect(res.status).toBe(200)

    const data = res.body?.data || res.body
    expect(data).toBeTruthy()

    // Should be an array or object with data
    if (Array.isArray(data)) {
      console.log(`  Net requirements: ${data.length} items`)
    } else {
      console.log(`  Net requirements keys: ${Object.keys(data).join(', ')}`)
    }

    await ss(page, '1.3-net-requirements')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // BOM COST (2.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('2.1 — GET /manufacturing/boms/{id}/cost — verify 200 with cost data', async () => {
    expect(bomId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/boms/${bomId}/cost`)
    expect(res.status).toBe(200)

    const data = res.body?.data || res.body
    expect(data).toBeTruthy()

    // Should contain cost-related fields
    const hasCost =
      data.total_cost !== undefined ||
      data.material_cost !== undefined ||
      data.cost !== undefined ||
      data.unit_cost !== undefined ||
      data.materials !== undefined
    expect(hasCost).toBe(true)

    console.log(`  BOM cost keys: ${Object.keys(data).join(', ')}`)
    await ss(page, '2.1-bom-cost')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // WORK CENTER OEE (3.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('3.1 — GET /manufacturing/work-centers/{id}/oee — verify 200 with OEE metrics', async () => {
    expect(workCenterId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/work-centers/${workCenterId}/oee`)
    expect(res.status).toBe(200)

    const data = res.body?.data || res.body
    expect(data).toBeTruthy()

    // Should contain OEE-related fields
    const hasOee =
      data.oee !== undefined ||
      data.availability !== undefined ||
      data.performance !== undefined ||
      data.quality !== undefined ||
      data.overall !== undefined
    expect(hasOee).toBe(true)

    console.log(`  OEE keys: ${Object.keys(data).join(', ')}`)
    await ss(page, '3.1-work-center-oee')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // CO-PRODUCTION PDF (4.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('4.1 — GET /manufacturing/orders/{id}/pdf/co-production — check PDF or 404', async () => {
    expect(orderId).toBeTruthy()

    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/co-production`)

    // 200 with PDF content, or 404 if order has no co-products — both acceptable
    // 500 is a server error and should fail
    expect(res.status).not.toBe(500)
    expect([200, 404, 422]).toContain(res.status) // 422 if order has no co-products

    if (res.status === 200) {
      expect(res.contentType).toContain('pdf')
      console.log(`  Co-production PDF: ${res.size} bytes`)
    } else {
      console.log(`  Co-production PDF: 404 (order has no co-products)`)
    }

    await ss(page, '4.1-co-production-pdf')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // PANTHEON IMPORT EXECUTE (5.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('5.1 — POST /manufacturing/import — without file, expect 422 validation error', async () => {
    const res = await api(page, 'POST', '/manufacturing/import', {})

    // Without a file, should return 422 validation error
    expect(res.status).toBe(422)
    console.log(`  Import without file: ${res.status} — ${res.raw?.substring(0, 200)}`)

    await ss(page, '5.1-import-no-file-422')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // AI FEATURES (6.x) — These hit Gemini API, so be gentle
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('6.1 — POST /manufacturing/ai/suggest-materials — with BOM ID', async () => {
    expect(bomId).toBeTruthy()

    const res = await api(page, 'POST', '/manufacturing/ai/suggest-materials', {
      bom_id: bomId,
      context: 'test',
    })

    // Accept 200 (AI response), 422 (validation), or 503 (Gemini unavailable)
    // The key is that the endpoint EXISTS and does not return 404 or 500
    expect([200, 422, 503]).toContain(res.status)
    console.log(`  AI suggest-materials: ${res.status}`)

    await ss(page, '6.1-ai-suggest-materials')
  })

  test('6.2 — GET /manufacturing/ai/boms/{id}/predict-wastage — verify response', async () => {
    expect(bomId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/ai/boms/${bomId}/predict-wastage`)

    // Accept 200, 422, or 503
    expect([200, 422, 503]).toContain(res.status)
    console.log(`  AI predict-wastage: ${res.status}`)

    if (res.status === 200) {
      const data = res.body?.data || res.body
      console.log(`  Wastage prediction keys: ${Object.keys(data || {}).join(', ')}`)
    }

    await ss(page, '6.2-ai-predict-wastage')
  })

  test('6.3 — GET /manufacturing/ai/orders/{id}/detect-anomalies — verify response', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/ai/orders/${orderId}/detect-anomalies`)

    // Accept 200, 422, or 503
    expect([200, 422, 503]).toContain(res.status)
    console.log(`  AI detect-anomalies: ${res.status}`)

    if (res.status === 200) {
      const data = res.body?.data || res.body
      console.log(`  Anomaly detection keys: ${Object.keys(data || {}).join(', ')}`)
    }

    await ss(page, '6.3-ai-detect-anomalies')
  })

  test('6.4 — POST /manufacturing/ai/parse-intent — with natural language text', async () => {
    const res = await api(page, 'POST', '/manufacturing/ai/parse-intent', {
      text: 'create production order for 100 units of bread',
    })

    // Accept 200, 422, or 503
    expect([200, 422, 503]).toContain(res.status)
    console.log(`  AI parse-intent: ${res.status}`)

    if (res.status === 200) {
      const data = res.body?.data || res.body
      console.log(`  Parsed intent keys: ${Object.keys(data || {}).join(', ')}`)
    }

    await ss(page, '6.4-ai-parse-intent')
  })

  test('6.5 — GET /manufacturing/ai/orders/{id}/explain-variance — verify response', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/ai/orders/${orderId}/explain-variance`)

    // Accept 200, 422, or 503
    expect([200, 422, 503]).toContain(res.status)
    console.log(`  AI explain-variance: ${res.status}`)

    if (res.status === 200) {
      const data = res.body?.data || res.body
      console.log(`  Variance explanation keys: ${Object.keys(data || {}).join(', ')}`)
    }

    await ss(page, '6.5-ai-explain-variance')
  })
})
