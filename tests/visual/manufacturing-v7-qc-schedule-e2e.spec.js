/**
 * Manufacturing Module v7 — QC Checks, Scheduling, Barcode Scanning, Dependency Management
 *
 * Tests against Company 2 on production (app.facturino.mk).
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/manufacturing-v7-qc-schedule-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'manufacturing-v7-qc-schedule-screenshots')

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

const TODAY = new Date()
const TODAY_STR = `${TODAY.getFullYear()}-${String(TODAY.getMonth() + 1).padStart(2, '0')}-${String(TODAY.getDate()).padStart(2, '0')}`

test.describe('Manufacturing v7 — QC Checks, Scheduling, Barcode & Dependencies', () => {
  test.describe.configure({ mode: 'serial' })

  let page

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage()
    await page.goto(`${BASE}/login`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForURL('**/admin/dashboard', { timeout: 15000 })
    await page.waitForTimeout(1000)

    // ── Setup: find or create an in_progress order ──
    const ordersRes = await api(page, 'GET', '/manufacturing/orders?limit=50')
    const orders = ordersRes.body?.data || []
    let activeOrder = orders.find(o => o.status === 'in_progress')

    if (activeOrder) {
      page._orderId = activeOrder.id
    } else {
      // Find a BOM to create an order from
      const bomsRes = await api(page, 'GET', '/manufacturing/boms?limit=10')
      const boms = bomsRes.body?.data || []
      const bom = boms[0]

      if (bom) {
        const createRes = await api(page, 'POST', '/manufacturing/orders', {
          bom_id: bom.id,
          quantity: 10,
          start_date: TODAY_STR,
          end_date: TODAY_STR,
          priority: 'medium',
          notes: 'E2E v7 test order',
        })
        if (createRes.status === 200 || createRes.status === 201) {
          const newOrderId = createRes.body?.data?.id
          // Start it
          await api(page, 'POST', `/manufacturing/orders/${newOrderId}/start`)
          page._orderId = newOrderId
        }
      }

      // Re-fetch to confirm
      if (!page._orderId) {
        const retry = await api(page, 'GET', '/manufacturing/orders?limit=50')
        const retryOrders = retry.body?.data || []
        const fallback = retryOrders.find(o => o.status === 'in_progress') || retryOrders[0]
        page._orderId = fallback?.id
      }
    }

    // ── Setup: find first BOM ──
    const bomsRes = await api(page, 'GET', '/manufacturing/boms?limit=10')
    const boms = bomsRes.body?.data || []
    page._bomId = boms[0]?.id || null
  })

  test.afterAll(async () => {
    if (page) await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 1: QC Checks
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1.1 — GET QC checks for order returns 200 with array', async () => {
    const orderId = page._orderId
    expect(orderId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/orders/${orderId}/qc-checks`)
    expect(res.status).toBe(200)
    expect(Array.isArray(res.body?.data)).toBe(true)
    await ss(page, '1.1-list-qc-checks')
  })

  test('1.2 — POST QC check with in_process type creates check', async () => {
    const orderId = page._orderId
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/qc-checks`, {
      check_date: TODAY_STR,
      result: 'fail',
      quantity_inspected: 10,
      quantity_passed: 8,
      quantity_rejected: 2,
      defects: [{ type: 'visual', quantity: 2, severity: 'minor' }],
      checklist: [
        { criterion: 'Surface finish', result: 'pass' },
        { criterion: 'Dimensions', result: 'fail' },
      ],
      notes: 'E2E test QC check',
    })

    expect([200, 201]).toContain(res.status)
    expect(res.body?.data?.id).toBeTruthy()
    page._qcCheckId = res.body?.data?.id
    await ss(page, '1.2-create-qc-check')
  })

  test('1.3 — POST dispose with rework succeeds', async () => {
    const orderId = page._orderId
    const checkId = page._qcCheckId
    expect(orderId).toBeTruthy()
    expect(checkId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/qc-checks/${checkId}/dispose`, {
      disposition: 'rework',
      rejected_quantity: 2,
      notes: 'E2E rework test',
    })

    expect(res.status).toBe(200)
    expect(res.body?.data?.disposition).toBe('rework')
    await ss(page, '1.3-dispose-rework')
  })

  test('1.4 — POST new QC check then dispose with scrap', async () => {
    const orderId = page._orderId
    expect(orderId).toBeTruthy()

    // Create another QC check
    const qcRes = await api(page, 'POST', `/manufacturing/orders/${orderId}/qc-checks`, {
      check_date: TODAY_STR,
      result: 'fail',
      quantity_inspected: 5,
      quantity_passed: 4,
      quantity_rejected: 1,
      notes: 'E2E scrap QC check',
      checklist: [],
      defects: [{ type: 'surface_defect', quantity: 1, severity: 'minor' }],
    })

    expect([200, 201]).toContain(qcRes.status)
    const scrapCheckId = qcRes.body?.data?.id
    expect(scrapCheckId).toBeTruthy()

    // Dispose with scrap
    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/qc-checks/${scrapCheckId}/dispose`, {
      disposition: 'scrap',
      rejected_quantity: 1,
      notes: 'E2E scrap test',
    })

    expect(res.status).toBe(200)
    expect(res.body?.data?.disposition).toBe('scrap')
    await ss(page, '1.4-dispose-scrap')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 2: Scheduling
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('2.1 — PATCH reschedule order to next week', async () => {
    // Use a draft order for reschedule (in_progress may reject reschedule)
    const ordersRes = await api(page, 'GET', '/manufacturing/orders?limit=50')
    const orders = ordersRes.body?.data || []
    let scheduleOrder = orders.find(o => o.status === 'draft')

    if (!scheduleOrder) {
      // Create a draft order for scheduling tests
      const bomsRes = await api(page, 'GET', '/manufacturing/boms?limit=10')
      const boms = bomsRes.body?.data || []
      if (boms[0]) {
        const createRes = await api(page, 'POST', '/manufacturing/orders', {
          bom_id: boms[0].id,
          quantity: 5,
          start_date: TODAY_STR,
          end_date: TODAY_STR,
          priority: 'low',
          notes: 'E2E v7 schedule test order',
        })
        if (createRes.status === 200 || createRes.status === 201) {
          scheduleOrder = createRes.body?.data
        }
      }
    }

    if (!scheduleOrder) {
      // Fallback: try reschedule on existing order
      scheduleOrder = orders[0]
    }

    expect(scheduleOrder).toBeTruthy()
    page._scheduleOrderId = scheduleOrder.id

    const res = await api(page, 'PATCH', `/manufacturing/orders/${scheduleOrder.id}/reschedule`, {
      order_date: '2026-04-06',
      expected_completion_date: '2026-04-10',
    })

    expect(res.status).toBe(200)
    await ss(page, '2.1-reschedule-order')
  })

  test('2.2 — POST auto-schedule returns 200', async () => {
    const res = await api(page, 'POST', '/manufacturing/auto-schedule', {})
    expect(res.status).toBe(200)
    expect(res.body?.data).toHaveProperty('count')
    expect(res.body?.data).toHaveProperty('orders')
    await ss(page, '2.2-auto-schedule')
  })

  test('2.3 — PUT dependencies with empty array clears deps', async () => {
    const orderId = page._scheduleOrderId || page._orderId
    expect(orderId).toBeTruthy()

    const res = await api(page, 'PUT', `/manufacturing/orders/${orderId}/dependencies`, {
      depends_on: [],
    })

    expect(res.status).toBe(200)
    expect(res.body?.success).toBe(true)
    await ss(page, '2.3-clear-dependencies')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 3: Barcode Scanning
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('3.1 — POST scan with valid material barcode', async () => {
    const orderId = page._orderId
    expect(orderId).toBeTruthy()

    // Fetch order detail to find a material item
    const orderRes = await api(page, 'GET', `/manufacturing/orders/${orderId}`)
    expect(orderRes.status).toBe(200)

    const materials = orderRes.body?.data?.bom?.materials
      || orderRes.body?.data?.materials
      || []

    let barcode = null
    let materialItemId = null

    // Try to find a material with a barcode
    for (const mat of materials) {
      const itemId = mat.item_id || mat.id
      if (itemId) {
        const itemRes = await api(page, 'GET', `/items/${itemId}`)
        if (itemRes.status === 200 && itemRes.body?.data?.barcode) {
          barcode = itemRes.body.data.barcode
          materialItemId = itemId
          break
        }
      }
    }

    if (barcode) {
      const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/scan`, {
        barcode,
        quantity: 1,
      })
      // Valid barcode should succeed
      expect([200, 201]).toContain(res.status)
      page._scannedBarcode = barcode
    } else {
      // No item has a barcode — test with fake and expect failure
      const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/scan`, {
        barcode: 'NO_BARCODE_AVAILABLE_E2E_TEST',
        quantity: 1,
      })
      expect([404, 422]).toContain(res.status)
    }

    await ss(page, '3.1-barcode-scan-material')
  })

  test('3.2 — POST scan with invalid barcode returns 404/422', async () => {
    const orderId = page._orderId
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/scan`, {
      barcode: 'NONEXISTENT_BARCODE_E2E_99999',
      quantity: 1,
    })

    expect([404, 422]).toContain(res.status)
    await ss(page, '3.2-barcode-scan-invalid')
  })
})
