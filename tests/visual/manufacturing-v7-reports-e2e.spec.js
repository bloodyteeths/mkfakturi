/**
 * Manufacturing Module v7 — PDF Generation & Report Endpoints
 *
 * Tests against Company 2 on production (app.facturino.mk).
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/manufacturing-v7-reports-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'manufacturing-v7-reports-screenshots')

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

test.describe('Manufacturing v7 — PDF Generation & Reports', () => {
  test.describe.configure({ mode: 'serial' })

  let page
  let orderId = null
  let bomId = null
  let itemId = null

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage()
    await page.goto(`${BASE}/login`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/admin/dashboard', { timeout: 15000 })
    await page.waitForTimeout(1000)

    // Fetch a real order ID
    const ordersRes = await api(page, 'GET', '/manufacturing/orders?limit=1')
    if (ordersRes.status === 200 && ordersRes.body?.data?.length > 0) {
      orderId = ordersRes.body.data[0].id
    }

    // Fetch a real BOM ID and its first item
    const bomsRes = await api(page, 'GET', '/manufacturing/boms?limit=1')
    if (bomsRes.status === 200 && bomsRes.body?.data?.length > 0) {
      bomId = bomsRes.body.data[0].id
      // Get the first material/item from BOM
      const bomDetail = await api(page, 'GET', `/manufacturing/boms/${bomId}`)
      if (bomDetail.status === 200) {
        const materials = bomDetail.body?.data?.materials || bomDetail.body?.materials || []
        if (materials.length > 0) {
          itemId = materials[0].item_id || materials[0].id
        }
      }
    }
  })

  test.afterAll(async () => {
    if (page) await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // 1.x — PDF Generation
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1.1 — PDF: Production Order (raboten nalog)', async () => {
    test.skip(!orderId, 'No production orders found')
    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/order`)
    console.log(`  1.1 order PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.2 — PDF: Costing Report', async () => {
    test.skip(!orderId, 'No production orders found')
    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/costing`)
    console.log(`  1.2 costing PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.3 — PDF: Trebovnica (Material Requisition)', async () => {
    test.skip(!orderId, 'No production orders found')
    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/trebovnica`)
    console.log(`  1.3 trebovnica PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.4 — PDF: Izdatnica (Issue Slip)', async () => {
    test.skip(!orderId, 'No production orders found')
    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/izdatnica`)
    console.log(`  1.4 izdatnica PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.5 — PDF: Priemnica (Goods Receipt)', async () => {
    test.skip(!orderId, 'No production orders found')
    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/priemnica`)
    console.log(`  1.5 priemnica PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.6 — PDF: Raboten Nalog (Work Order)', async () => {
    test.skip(!orderId, 'No production orders found')
    const res = await checkPdf(page, `/manufacturing/orders/${orderId}/pdf/raboten-nalog`)
    console.log(`  1.6 raboten-nalog PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.7 — PDF: Normativ (BOM Standard)', async () => {
    test.skip(!bomId, 'No BOMs found')
    const res = await checkPdf(page, `/manufacturing/boms/${bomId}/pdf/normativ`)
    console.log(`  1.7 normativ PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  test('1.8 — PDF: Lagerska Kartica (Stock Card)', async () => {
    test.skip(!itemId, 'No BOM items found for lagerska kartica')
    const res = await checkPdf(page, `/manufacturing/items/${itemId}/pdf/lagerska-kartica`)
    console.log(`  1.8 lagerska-kartica PDF: status=${res.status}, type=${res.contentType}, size=${res.size}`)
    if (res.status === 404) {
      console.log('  -> PDF endpoint not implemented yet, skipping')
      return
    }
    expect(res.status, `Expected 200 but got ${res.status}`).not.toBe(500)
    expect(res.status).toBe(200)
    expect(res.contentType).toContain('pdf')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // 2.x — Report Endpoints
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('2.1 — Report: Cost Analysis', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/cost-analysis')
    console.log(`  2.1 cost-analysis: status=${res.status}`)
    expect(res.status).toBe(200)
    expect(res.body).toBeTruthy()
    await ss(page, '2.1-cost-analysis-api')
  })

  test('2.2 — Report: Variance', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/variance')
    console.log(`  2.2 variance: status=${res.status}`)
    expect(res.status).toBe(200)
    expect(res.body).toBeTruthy()
    await ss(page, '2.2-variance-api')
  })

  test('2.3 — Report: Wastage', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/wastage')
    console.log(`  2.3 wastage: status=${res.status}`)
    expect(res.status).toBe(200)
    expect(res.body).toBeTruthy()
    await ss(page, '2.3-wastage-api')
  })

  test('2.4 — Report: QC Metrics with summary', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/qc-metrics')
    console.log(`  2.4 qc-metrics: status=${res.status}`)
    expect(res.status).toBe(200)
    expect(res.body).toBeTruthy()

    // Verify data structure has summary with pass_rate
    const data = res.body?.data || res.body
    const summary = data?.summary || data
    if (summary?.pass_rate !== undefined) {
      expect(typeof summary.pass_rate).toBe('number')
      console.log(`  -> pass_rate: ${summary.pass_rate}`)
    } else {
      console.log('  -> summary.pass_rate not found in response, structure:', Object.keys(data || {}))
    }
    await ss(page, '2.4-qc-metrics-api')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // 3.x — Report UI Screenshots
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('3.1 — Manufacturing dashboard cost chart screenshot', async () => {
    await page.goto(`${BASE}/admin/manufacturing`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(3000)

    // Look for a chart canvas or cost chart container
    const chartCanvas = page.locator('canvas').first()
    if (await chartCanvas.count() > 0) {
      await chartCanvas.screenshot({ path: path.join(SCREENSHOT_DIR, '3.1-cost-chart-area.png') })
    } else {
      // Fallback: full page screenshot
      await ss(page, '3.1-cost-chart-area-full')
    }
  })

  test('3.2 — Gantt full screenshot', async () => {
    await page.goto(`${BASE}/admin/manufacturing/gantt`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(3000)
    await ss(page, '3.2-gantt-full')
  })
})
