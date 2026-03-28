/**
 * Manufacturing Module v7 — Full Production Lifecycle E2E Tests
 *
 * Tests material consumption, labor, overhead, completion, and status transitions
 * against Company 2 on production (app.facturino.mk).
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/manufacturing-v7-lifecycle-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'manufacturing-v7-lifecycle-screenshots')

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

const TODAY = new Date().toISOString().split('T')[0]

test.describe('Manufacturing v7 — Full Production Lifecycle', () => {
  test.describe.configure({ mode: 'serial' })

  let page
  let orderId = null
  let bomId = null
  let materialItemId = null
  let warehouseId = null
  let secondOrderId = null

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
  // SETUP (0.x) — Create test production order from first available BOM
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('0.1 — Create a test production order from first available BOM', async () => {
    // Fetch first available BOM
    const bomRes = await api(page, 'GET', '/manufacturing/boms?limit=1')
    expect(bomRes.status).toBe(200)
    const boms = bomRes.body?.data || []
    expect(boms.length).toBeGreaterThan(0)

    const bom = boms[0]
    bomId = bom.id

    // Extract a material item from the BOM for later consumption tests
    if (bom.materials && bom.materials.length > 0) {
      materialItemId = bom.materials[0].item_id || bom.materials[0].id
    } else if (bom.items && bom.items.length > 0) {
      materialItemId = bom.items[0].item_id || bom.items[0].id
    }

    // Create production order
    const orderRes = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: bomId,
      planned_quantity: 10,
      planned_start_date: TODAY,
      planned_end_date: TODAY,
    })
    expect([200, 201]).toContain(orderRes.status)
    expect(orderRes.body?.data?.id).toBeTruthy()

    orderId = orderRes.body.data.id
    warehouseId = orderRes.body.data.warehouse_id || null

    // Verify it was created as draft
    expect(orderRes.body.data.status).toBe('draft')

    await ss(page, '0.1-order-created')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // MATERIAL CONSUMPTION (1.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1.1 — Start production order — status changes to in_progress', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/start`)
    expect([200, 201]).toContain(res.status)
    expect(res.body?.data?.status).toBe('in_progress')

    await ss(page, '1.1-order-started')
  })

  test('1.2 — Add material consumption', async () => {
    expect(orderId).toBeTruthy()

    // Fetch order detail to get production_order_materials records
    const orderDetail = await api(page, 'GET', `/manufacturing/orders/${orderId}`)
    const materials = orderDetail.body?.data?.materials || []
    expect(materials.length).toBeGreaterThan(0)

    // Use the first material's ID (production_order_materials.id, not item_id)
    const material = materials[0]
    materialItemId = material.item_id
    warehouseId = orderDetail.body?.data?.output_warehouse_id || orderDetail.body?.data?.warehouse_id || null

    // If no warehouse on order, fetch company warehouses and use the first one
    if (!warehouseId) {
      const whRes = await api(page, 'GET', '/warehouses')
      const warehouses = whRes.body?.data || whRes.body || []
      if (warehouses.length > 0) {
        warehouseId = warehouses[0].id
      }
    }

    // If still no warehouse, try stock warehouse endpoint
    if (!warehouseId) {
      const swRes = await api(page, 'GET', '/stock/warehouses')
      const swarehouses = swRes.body?.data || swRes.body || []
      if (swarehouses.length > 0) {
        warehouseId = swarehouses[0].id
      }
    }

    // Last resort: create one
    if (!warehouseId) {
      const cwRes = await api(page, 'POST', '/warehouses', { name: 'Test Warehouse' })
      warehouseId = cwRes.body?.data?.id || cwRes.body?.id
    }

    expect(warehouseId).toBeTruthy()

    const body = {
      material_id: material.id,
      actual_quantity: 5,
      warehouse_id: warehouseId,
    }

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/materials`, body)
    expect([200, 201]).toContain(res.status)

    await ss(page, '1.2-material-added')
  })

  test('1.3 — Verify material shows in order detail', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/orders/${orderId}`)
    expect(res.status).toBe(200)

    const order = res.body?.data
    expect(order).toBeTruthy()

    // Check materials array exists and has entries
    const materials = order.materials || order.consumed_materials || []
    expect(materials.length).toBeGreaterThan(0)

    await ss(page, '1.3-material-in-order-detail')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // LABOR ENTRY (2.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('2.1 — Add labor entry', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/labor`, {
      description: 'Test labor',
      hours: 2,
      rate_per_hour: 50000,
      work_date: TODAY,
    })
    expect([200, 201]).toContain(res.status)

    await ss(page, '2.1-labor-added')
  })

  test('2.2 — Verify labor shows in order detail', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/orders/${orderId}`)
    expect(res.status).toBe(200)

    const order = res.body?.data
    expect(order).toBeTruthy()

    // Check labor entries exist
    const labor = order.labor_entries || order.labor || []
    expect(labor.length).toBeGreaterThan(0)

    await ss(page, '2.2-labor-in-order-detail')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // OVERHEAD ENTRY (3.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('3.1 — Add overhead entry', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/overhead`, {
      description: 'Test overhead',
      amount: 1000,
    })
    expect([200, 201]).toContain(res.status)

    await ss(page, '3.1-overhead-added')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // COMPLETION (4.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('4.1 — Complete production order', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/complete`, {
      actual_quantity: 10,
    })
    expect([200, 201]).toContain(res.status)
    expect(res.body?.data?.status).toBe('completed')

    await ss(page, '4.1-order-completed')
  })

  test('4.2 — Verify completed order has costs calculated', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/orders/${orderId}`)
    expect(res.status).toBe(200)

    const order = res.body?.data
    expect(order).toBeTruthy()
    expect(order.status).toBe('completed')

    // Verify cost fields exist (may be named differently)
    const hasCosts =
      order.total_material_cost !== undefined ||
      order.total_labor_cost !== undefined ||
      order.total_overhead_cost !== undefined ||
      order.total_cost !== undefined ||
      order.material_cost !== undefined ||
      order.labor_cost !== undefined
    expect(hasCosts).toBe(true)

    await ss(page, '4.2-order-costs-calculated')
  })

  test('4.3 — Verify order cannot be completed again', async () => {
    expect(orderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${orderId}/complete`, {
      actual_quantity: 10,
    })
    // Should return an error (400, 403, 409, or 422)
    expect(res.status).toBeGreaterThanOrEqual(400)

    await ss(page, '4.3-double-complete-rejected')
  })

  test('4.4 — Verify order cannot go back to draft (invalid transition)', async () => {
    expect(orderId).toBeTruthy()

    // Try to update status back to draft via PUT
    const res = await api(page, 'PUT', `/manufacturing/orders/${orderId}`, {
      status: 'draft',
    })
    // Should either fail (4xx) or silently ignore the status change
    if (res.status >= 200 && res.status < 300) {
      // If it returned 200, status should NOT be draft
      const check = await api(page, 'GET', `/manufacturing/orders/${orderId}`)
      expect(check.body?.data?.status).toBe('completed')
    } else {
      expect(res.status).toBeGreaterThanOrEqual(400)
    }

    await ss(page, '4.4-invalid-transition-rejected')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // STATUS TRANSITION VALIDATION (5.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('5.1 — Create another draft order', async () => {
    expect(bomId).toBeTruthy()

    const res = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: bomId,
      planned_quantity: 10,
      planned_start_date: TODAY,
      planned_end_date: TODAY,
    })
    expect([200, 201]).toContain(res.status)
    expect(res.body?.data?.status).toBe('draft')

    secondOrderId = res.body.data.id

    await ss(page, '5.1-second-order-created')
  })

  test('5.2 — Try to complete draft directly (skip in_progress) — should fail', async () => {
    expect(secondOrderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${secondOrderId}/complete`, {
      actual_quantity: 10,
    })
    // Completing a draft should fail — must start first
    expect(res.status).toBeGreaterThanOrEqual(400)

    await ss(page, '5.2-skip-in-progress-rejected')
  })

  test('5.3 — Cancel the draft — verify status = cancelled', async () => {
    expect(secondOrderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${secondOrderId}/cancel`)
    expect([200, 201]).toContain(res.status)
    expect(res.body?.data?.status).toBe('cancelled')

    await ss(page, '5.3-draft-cancelled')
  })

  test('5.4 — Try to start cancelled order — should fail', async () => {
    expect(secondOrderId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/orders/${secondOrderId}/start`)
    // Starting a cancelled order should fail
    expect(res.status).toBeGreaterThanOrEqual(400)

    await ss(page, '5.4-start-cancelled-rejected')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // FULL DASHBOARD SCREENSHOT (6.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('6.1 — Screenshot of completed order view showing costs', async () => {
    expect(orderId).toBeTruthy()

    await page.goto(`${BASE}/admin/manufacturing/orders/${orderId}`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    await ss(page, '6.1-completed-order-with-costs')
  })
})
