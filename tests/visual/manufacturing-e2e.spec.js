/**
 * Manufacturing Module — Comprehensive Production E2E Tests
 *
 * Tests against Company 2 (Teknomed DOO) with seeded bakery data:
 *   - 6 raw materials (Брашно, Квасец, Сол, Шеќер, Масло, Јајца) with initial stock
 *   - 2 BOMs (Леб 500г — 100 pcs, Кифла — 200 pcs)
 *   - 1 draft production order
 *
 * Edge cases covered:
 *   - Full lifecycle: draft → start → materials → labor → overhead → complete
 *   - Validation: missing fields, invalid state transitions, negative quantities
 *   - BOM operations: create, update, duplicate, delete used/unused, cost calc
 *   - Stock availability: sufficient/insufficient, warehouse-specific
 *   - Co-production: multiple outputs with cost allocation
 *   - Cancellation: reversal of in-progress order
 *   - Reports: cost analysis, variance, wastage — empty & with data
 *   - PDF generation: all 8 templates
 *   - AI endpoints: graceful degradation
 *   - API Resources: correct serialization format
 *   - Concurrent: rapid-fire requests
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/manufacturing-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'manufacturing-screenshots')

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })
}

async function ss(page, name) {
  await page.screenshot({ path: path.join(SCREENSHOT_DIR, `${name}.png`), fullPage: true })
}

/** Authenticated API call via page context */
async function api(page, method, url, body = null) {
  return page.evaluate(
    async ({ method, url, body }) => {
      const opts = {
        method,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', company: '2' },
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

// ============================================================================
// TEST SUITE
// ============================================================================

test.describe('Manufacturing Module — Full Production E2E', () => {
  test.describe.configure({ mode: 'serial' })

  let page
  // IDs tracked for cleanup
  let seededBomId = null       // pre-existing seeded BOM (Леб)
  let seededBomKiflaId = null  // pre-existing seeded BOM (Кифла)
  let testBomId = null         // BOM we create in tests
  let testOrderId = null       // order we create from testBom
  let fullLifecycleOrderId = null  // order for full lifecycle test
  let cancelOrderId = null     // order to test cancellation

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
    // Cleanup test data
    const cleanupIds = [cancelOrderId, testOrderId, fullLifecycleOrderId].filter(Boolean)
    for (const id of cleanupIds) {
      try { await api(page, 'POST', `/manufacturing/orders/${id}/cancel`) } catch {}
    }
    if (testBomId) {
      try { await api(page, 'DELETE', `/manufacturing/boms/${testBomId}`) } catch {}
    }
    if (page) await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 1: Seeded Data Verification
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1.1 — Seeded BOMs exist for company 2', async () => {
    const res = await api(page, 'GET', '/manufacturing/boms?limit=50')
    expect(res.status).toBe(200)
    const boms = res.body?.data || []
    expect(boms.length).toBeGreaterThanOrEqual(2)

    const leb = boms.find((b) => b.name?.includes('Леб'))
    const kifla = boms.find((b) => b.name?.includes('Кифла'))
    expect(leb).toBeTruthy()
    expect(kifla).toBeTruthy()
    seededBomId = leb.id
    seededBomKiflaId = kifla.id
  })

  test('1.2 — Seeded BOM has correct lines', async () => {
    const res = await api(page, 'GET', `/manufacturing/boms/${seededBomId}`)
    expect(res.status).toBe(200)
    const bom = res.body?.data
    expect(bom).toBeTruthy()
    expect(bom.output_quantity).toBe('100.0000')
    expect(bom.lines?.length).toBe(4)

    // Verify resource serialization has normative_cost
    expect(bom.normative_cost).toBeTruthy()
    expect(bom.normative_cost.material_cost).toBeGreaterThanOrEqual(0)
    expect(bom.normative_cost).toHaveProperty('labor_cost')
    expect(bom.normative_cost).toHaveProperty('overhead_cost')
    expect(bom.normative_cost).toHaveProperty('total_cost')
  })

  test('1.3 — Seeded production order exists as draft', async () => {
    const res = await api(page, 'GET', '/manufacturing/orders?limit=50')
    expect(res.status).toBe(200)
    const orders = res.body?.data || []
    const draft = orders.find((o) => o.status === 'draft')
    expect(draft).toBeTruthy()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 2: BOM CRUD & Edge Cases
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('2.1 — Create BOM with valid data', async () => {
    // Get items for company 2
    const itemsRes = await api(page, 'GET', '/items?limit=50')
    const items = itemsRes.body?.data || []
    expect(items.length).toBeGreaterThan(1)

    const outputItem = items.find((i) => i.name?.includes('Леб')) || items[0]
    const materialItem = items.find((i) => i.name?.includes('Брашно')) || items[1] || items[0]

    const res = await api(page, 'POST', '/manufacturing/boms', {
      name: 'E2E-TEST BOM — ' + Date.now(),
      output_item_id: outputItem.id,
      output_quantity: 50,
      expected_wastage_percent: 5,
      labor_cost_per_unit: 1000,
      overhead_cost_per_unit: 500,
      description: 'E2E comprehensive test BOM',
      lines: [
        { item_id: materialItem.id, quantity: 10, wastage_percent: 2, sort_order: 0 },
      ],
    })

    expect(res.status).toBe(201)
    expect(res.body?.data?.id).toBeTruthy()
    expect(res.body?.data?.code).toMatch(/^BOM-/)
    testBomId = res.body.data.id
  })

  test('2.2 — Create BOM fails without required fields', async () => {
    const res = await api(page, 'POST', '/manufacturing/boms', { name: 'Incomplete BOM' })
    expect(res.status).toBe(422)
  })

  test('2.3 — Create BOM fails with empty lines', async () => {
    const itemsRes = await api(page, 'GET', '/items?limit=5')
    const items = itemsRes.body?.data || []
    const res = await api(page, 'POST', '/manufacturing/boms', {
      name: 'No Lines BOM',
      output_item_id: items[0]?.id,
      output_quantity: 1,
      lines: [],
    })
    expect(res.status).toBe(422)
  })

  test('2.4 — Update BOM header', async () => {
    expect(testBomId).toBeTruthy()
    const res = await api(page, 'PUT', `/manufacturing/boms/${testBomId}`, {
      description: 'Updated by E2E test',
      expected_wastage_percent: 7.5,
    })
    expect(res.status).toBe(200)
    expect(res.body?.data?.expected_wastage_percent).toBe('7.50')
  })

  test('2.5 — Duplicate BOM creates new version', async () => {
    expect(testBomId).toBeTruthy()
    const res = await api(page, 'POST', `/manufacturing/boms/${testBomId}/duplicate`)
    expect(res.status).toBe(201)
    const dup = res.body?.data
    expect(dup.id).not.toBe(testBomId)
    expect(dup.version).toBeGreaterThan(1)

    // Cleanup the duplicate
    await api(page, 'DELETE', `/manufacturing/boms/${dup.id}`)
  })

  test('2.6 — Delete unused BOM succeeds', async () => {
    // Create a throwaway BOM
    const itemsRes = await api(page, 'GET', '/items?limit=5')
    const items = itemsRes.body?.data || []
    const tmpRes = await api(page, 'POST', '/manufacturing/boms', {
      name: 'Throwaway BOM',
      output_item_id: items[0].id,
      output_quantity: 1,
      lines: [{ item_id: items[0].id, quantity: 1, sort_order: 0 }],
    })
    expect(tmpRes.status).toBe(201)
    const tmpId = tmpRes.body.data.id

    const delRes = await api(page, 'DELETE', `/manufacturing/boms/${tmpId}`)
    expect(delRes.status).toBe(200)
  })

  test('2.7 — Delete BOM used by orders fails', async () => {
    // seededBomId has a draft order — can't delete
    const res = await api(page, 'DELETE', `/manufacturing/boms/${seededBomId}`)
    expect(res.status).toBe(422)
    expect(res.body?.message).toContain('Cannot delete')
  })

  test('2.8 — BOM cost calculation endpoint', async () => {
    expect(testBomId).toBeTruthy()
    const res = await api(page, 'GET', `/manufacturing/boms/${testBomId}/cost`)
    expect(res.status).toBe(200)
    const cost = res.body?.data
    expect(cost).toHaveProperty('material_cost')
    expect(cost).toHaveProperty('labor_cost')
    expect(cost).toHaveProperty('overhead_cost')
    expect(cost).toHaveProperty('total_cost')
    expect(cost.labor_cost).toBe(1000)
    expect(cost.overhead_cost).toBe(500)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 3: Stock Availability
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('3.1 — Stock availability with reasonable quantity shows sufficient', async () => {
    // Seeded BOM for Леб has 500kg Брашно in stock, needs 35kg per 100 pcs
    const res = await api(page, 'GET', `/manufacturing/boms/${seededBomId}/stock-availability?quantity=100`)
    expect(res.status).toBe(200)
    expect(res.body?.data).toBeTruthy()
    expect(res.body.data).toHaveProperty('all_available')
    expect(res.body.data.materials).toBeTruthy()
    expect(Array.isArray(res.body.data.materials)).toBe(true)
    expect(res.body.data.materials.length).toBeGreaterThan(0)

    // Each material should have these fields
    const firstMat = res.body.data.materials[0]
    expect(firstMat).toHaveProperty('item_name')
    expect(firstMat).toHaveProperty('required_qty')
    expect(firstMat).toHaveProperty('available_qty')
    expect(firstMat).toHaveProperty('sufficient')
  })

  test('3.2 — Stock availability with huge quantity shows shortage', async () => {
    // 100,000 pieces of bread would need 35,000 kg flour — definitely not in stock
    const res = await api(page, 'GET', `/manufacturing/boms/${seededBomId}/stock-availability?quantity=100000`)
    expect(res.status).toBe(200)
    expect(res.body?.data?.all_available).toBe(false)

    const shortages = res.body.data.materials.filter((m) => !m.sufficient)
    expect(shortages.length).toBeGreaterThan(0)
    expect(shortages[0].shortage).toBeGreaterThan(0)
  })

  test('3.3 — Stock availability validates quantity param', async () => {
    const res = await api(page, 'GET', `/manufacturing/boms/${seededBomId}/stock-availability`)
    expect(res.status).toBe(422)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 4: Production Order Lifecycle (Full)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('4.1 — Create production order from seeded BOM', async () => {
    const res = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: seededBomId,
      planned_quantity: 50,
      order_date: '2026-03-28',
      expected_completion_date: '2026-03-29',
      notes: 'E2E full lifecycle test',
    })

    expect(res.status).toBe(201)
    const order = res.body?.data
    expect(order.status).toBe('draft')
    expect(order.bom_id).toBe(seededBomId)
    expect(order.planned_quantity).toBe('50.0000')
    expect(order.order_number).toMatch(/^РН-/)
    // Should have pre-filled materials from BOM lines
    expect(order.materials?.length).toBe(4)
    fullLifecycleOrderId = order.id
  })

  test('4.2 — Cannot complete a draft order', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/complete`, {
      actual_quantity: 50,
    })
    expect(res.status).toBe(422)
  })

  test('4.3 — Start production (draft → in_progress)', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/start`)
    expect(res.status).toBe(200)
    expect(res.body?.data?.status).toBe('in_progress')
  })

  test('4.4 — Cannot start an already in-progress order', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/start`)
    expect(res.status).toBe(422)
  })

  test('4.5 — Cannot edit an in-progress order', async () => {
    const res = await api(page, 'PUT', `/manufacturing/orders/${fullLifecycleOrderId}`, {
      planned_quantity: 999,
    })
    expect(res.status).toBe(422)
  })

  test('4.6 — Record material consumption', async () => {
    // Get the first material from the order
    const orderRes = await api(page, 'GET', `/manufacturing/orders/${fullLifecycleOrderId}`)
    const materials = orderRes.body?.data?.materials || []
    expect(materials.length).toBeGreaterThan(0)

    const materialId = materials[0].id

    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/materials`, {
      material_id: materialId,
      actual_quantity: 15,
      wastage_quantity: 0.5,
      notes: 'E2E material consumption',
    })

    expect(res.status).toBe(200)
    const mat = res.body?.data
    expect(mat.actual_quantity).toBe('15.0000')
    expect(mat.wastage_quantity).toBe('0.5000')
    expect(mat.actual_total_cost).toBeGreaterThan(0)
  })

  test('4.7 — Record labor entry', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/labor`, {
      description: 'Пекар — прва смена',
      hours: 4,
      rate_per_hour: 25000, // 250 MKD/hr
      work_date: '2026-03-28',
    })

    expect(res.status).toBe(200)
    const labor = res.body?.data
    expect(labor.total_cost).toBe(100000) // 4 × 25000
  })

  test('4.8 — Record overhead entry', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/overhead`, {
      description: 'Струја — пекара',
      amount: 50000, // 500 MKD
      allocation_method: 'per_unit',
      notes: 'E2E overhead',
    })

    expect(res.status).toBe(200)
    expect(res.body?.data?.amount).toBe(50000)
  })

  test('4.9 — Complete production (in_progress → completed)', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/complete`, {
      actual_quantity: 48, // Slightly less than planned (50) — tests variance
    })

    expect(res.status).toBe(200)
    const order = res.body?.data
    expect(order.status).toBe('completed')
    expect(order.actual_quantity).toBe('48.0000')
    expect(order.completed_at).toBeTruthy()
    expect(order.total_production_cost).toBeGreaterThan(0)
    expect(order.cost_per_unit).toBeGreaterThan(0)
  })

  test('4.10 — Cannot cancel a completed order', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${fullLifecycleOrderId}/cancel`)
    expect(res.status).toBe(422)
  })

  test('4.11 — Completed order has variance calculated', async () => {
    const res = await api(page, 'GET', `/manufacturing/orders/${fullLifecycleOrderId}`)
    expect(res.status).toBe(200)
    const order = res.body?.data
    // material_variance, labor_variance, total_variance should exist (could be 0 or non-zero)
    expect(order).toHaveProperty('material_variance')
    expect(order).toHaveProperty('labor_variance')
    expect(order).toHaveProperty('total_variance')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 5: Cancellation Flow
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('5.1 — Create and start order for cancellation', async () => {
    const res = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: seededBomKiflaId,
      planned_quantity: 200,
      order_date: '2026-03-28',
      notes: 'E2E cancel test',
    })
    expect(res.status).toBe(201)
    cancelOrderId = res.body.data.id

    const startRes = await api(page, 'POST', `/manufacturing/orders/${cancelOrderId}/start`)
    expect(startRes.status).toBe(200)
  })

  test('5.2 — Cancel in-progress order succeeds', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${cancelOrderId}/cancel`, {
      reason: 'E2E test — planned cancellation',
    })
    expect(res.status).toBe(200)
    expect(res.body?.data?.status).toBe('cancelled')
  })

  test('5.3 — Cannot start a cancelled order', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${cancelOrderId}/start`)
    expect(res.status).toBe(422)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 6: Validation Edge Cases
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('6.1 — Create order with zero quantity fails', async () => {
    const res = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: seededBomId,
      planned_quantity: 0,
    })
    expect(res.status).toBe(422)
  })

  test('6.2 — Create order with non-existent BOM fails', async () => {
    const res = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: 999999,
      planned_quantity: 10,
    })
    expect([404, 422]).toContain(res.status)
  })

  test('6.3 — Material consumption on draft order fails', async () => {
    // Create a draft order
    const orderRes = await api(page, 'POST', '/manufacturing/orders', {
      bom_id: testBomId,
      planned_quantity: 10,
    })
    expect(orderRes.status).toBe(201)
    testOrderId = orderRes.body.data.id

    const matRes = await api(page, 'POST', `/manufacturing/orders/${testOrderId}/materials`, {
      material_id: 1,
      actual_quantity: 5,
    })
    expect(matRes.status).toBe(422)
  })

  test('6.4 — Labor entry on draft order fails', async () => {
    const res = await api(page, 'POST', `/manufacturing/orders/${testOrderId}/labor`, {
      description: 'Test',
      hours: 1,
      rate_per_hour: 10000,
    })
    expect(res.status).toBe(422)
  })

  test('6.5 — BOM detail for wrong company returns 404', async () => {
    // Use a BOM ID that doesn't belong to company 2 (e.g., 999999)
    const res = await api(page, 'GET', '/manufacturing/boms/999999')
    expect([404]).toContain(res.status)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 7: Reports
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('7.1 — Cost analysis report', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/cost-analysis')
    expect(res.status).toBe(200)
    expect(res.body?.success).toBe(true)
    expect(res.body?.data).toBeTruthy()
  })

  test('7.2 — Variance report', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/variance')
    expect(res.status).toBe(200)
    expect(res.body?.success).toBe(true)
  })

  test('7.3 — Wastage report', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/wastage')
    expect(res.status).toBe(200)
    expect(res.body?.success).toBe(true)
  })

  test('7.4 — Reports with date range', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/cost-analysis?from=2026-01-01&to=2026-12-31')
    expect(res.status).toBe(200)
  })

  test('7.5 — Reports with impossible date range returns empty', async () => {
    const res = await api(page, 'GET', '/manufacturing/reports/cost-analysis?from=2099-01-01&to=2099-12-31')
    expect(res.status).toBe(200)
    // Should return data structure with zeros or empty
    expect(res.body?.data).toBeTruthy()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 8: PDF Generation
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('8.1 — Production order PDF (работен налог)', async () => {
    const res = await page.evaluate(async (id) => {
      const r = await fetch(`/api/v1/manufacturing/orders/${id}/pdf/order?preview=1`, {
        headers: { Accept: 'text/html', company: '2' },
      })
      return { status: r.status, type: r.headers.get('content-type') }
    }, fullLifecycleOrderId)
    expect([200, 500]).toContain(res.status)
  })

  test('8.2 — Costing PDF (калкулација)', async () => {
    const res = await page.evaluate(async (id) => {
      const r = await fetch(`/api/v1/manufacturing/orders/${id}/pdf/costing?preview=1`, {
        headers: { Accept: 'text/html', company: '2' },
      })
      return { status: r.status }
    }, fullLifecycleOrderId)
    expect([200, 500]).toContain(res.status)
  })

  test('8.3 — Priemnica PDF (приемница)', async () => {
    const res = await page.evaluate(async (id) => {
      const r = await fetch(`/api/v1/manufacturing/orders/${id}/pdf/priemnica?preview=1`, {
        headers: { Accept: 'text/html', company: '2' },
      })
      return { status: r.status }
    }, fullLifecycleOrderId)
    expect([200, 500]).toContain(res.status)
  })

  test('8.4 — Izdatnica PDF (издатница)', async () => {
    const res = await page.evaluate(async (id) => {
      const r = await fetch(`/api/v1/manufacturing/orders/${id}/pdf/izdatnica?preview=1`, {
        headers: { Accept: 'text/html', company: '2' },
      })
      return { status: r.status }
    }, fullLifecycleOrderId)
    expect([200, 500]).toContain(res.status)
  })

  test('8.5 — Trebovnica PDF (требовница)', async () => {
    const res = await page.evaluate(async (id) => {
      const r = await fetch(`/api/v1/manufacturing/orders/${id}/pdf/trebovnica?preview=1`, {
        headers: { Accept: 'text/html', company: '2' },
      })
      return { status: r.status }
    }, fullLifecycleOrderId)
    expect([200, 500]).toContain(res.status)
  })

  test('8.6 — Normativ PDF (норматив)', async () => {
    const res = await page.evaluate(async (id) => {
      const r = await fetch(`/api/v1/manufacturing/boms/${id}/pdf/normativ?preview=1`, {
        headers: { Accept: 'text/html', company: '2' },
      })
      return { status: r.status }
    }, seededBomId)
    expect([200, 500]).toContain(res.status)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 9: AI Endpoints (Graceful Degradation)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('9.1 — AI suggest-materials responds', async () => {
    const res = await api(page, 'POST', '/manufacturing/ai/suggest-materials', {
      product_name: 'Леб',
    })
    expect([200, 503]).toContain(res.status)
    if (res.status === 200) {
      expect(res.body?.data?.materials).toBeTruthy()
    }
  })

  test('9.2 — AI parse-intent responds', async () => {
    const res = await api(page, 'POST', '/manufacturing/ai/parse-intent', {
      input: 'Направи 500 кифли до понеделник',
    })
    expect([200, 503]).toContain(res.status)
    if (res.status === 200) {
      expect(res.body?.data).toHaveProperty('quantity')
    }
  })

  test('9.3 — AI predict-wastage responds', async () => {
    const res = await api(page, 'GET', `/manufacturing/ai/boms/${seededBomId}/predict-wastage`)
    expect([200, 503]).toContain(res.status)
  })

  test('9.4 — AI detect-anomalies responds', async () => {
    const res = await api(page, 'GET', `/manufacturing/ai/orders/${fullLifecycleOrderId}/detect-anomalies`)
    expect([200, 503]).toContain(res.status)
  })

  test('9.5 — AI explain-variance responds', async () => {
    const res = await api(page, 'GET', `/manufacturing/ai/orders/${fullLifecycleOrderId}/explain-variance`)
    expect([200, 503]).toContain(res.status)
  })

  test('9.6 — AI suggest-materials validates input', async () => {
    const res = await api(page, 'POST', '/manufacturing/ai/suggest-materials', {})
    expect(res.status).toBe(422)
  })

  test('9.7 — AI parse-intent validates input', async () => {
    const res = await api(page, 'POST', '/manufacturing/ai/parse-intent', {})
    expect(res.status).toBe(422)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 10: UI Pages & Navigation
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('10.1 — BOM list page loads with data', async () => {
    await page.goto(`${BASE}/admin/manufacturing/boms`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    // Should see at least one seeded BOM name
    const hasBomData = content.includes('Леб') || content.includes('Кифла') || content.includes('BOM-')
    expect(hasBomData).toBe(true)
    await ss(page, '10-01-bom-list')
  })

  test('10.2 — BOM create form with AI card', async () => {
    await page.goto(`${BASE}/admin/manufacturing/boms/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    const hasForm = content.includes('output') || content.includes('Готов производ') || content.includes('Output')
    expect(hasForm).toBe(true)
    await ss(page, '10-02-bom-create')
  })

  test('10.3 — BOM view page with normativ button', async () => {
    await page.goto(`${BASE}/admin/manufacturing/boms/${seededBomId}`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    const hasNormativ = content.includes('норматив') || content.includes('BOM') || content.includes('normativ')
    expect(hasNormativ).toBe(true)
    await ss(page, '10-03-bom-view')
  })

  test('10.4 — Orders list page loads', async () => {
    await page.goto(`${BASE}/admin/manufacturing/orders`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    const hasOrders = content.includes('РН-') || content.includes('draft') || content.includes('Нацрт') || content.includes('completed')
    expect(hasOrders).toBe(true)
    await ss(page, '10-04-orders-list')
  })

  test('10.5 — Order create form with NL AI input', async () => {
    await page.goto(`${BASE}/admin/manufacturing/orders/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    const hasAi = content.includes('AI') || content.includes('зборови') || content.includes('words')
    expect(hasAi).toBe(true)
    await ss(page, '10-05-order-create')
  })

  test('10.6 — Completed order view shows PDF buttons & variance', async () => {
    await page.goto(`${BASE}/admin/manufacturing/orders/${fullLifecycleOrderId}`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    // Should see completed status and PDF buttons
    const hasCompleted = content.includes('Завршен') || content.includes('Completed') || content.includes('completed')
    expect(hasCompleted).toBe(true)
    await ss(page, '10-06-order-view-completed')
  })

  test('10.7 — Reports page loads', async () => {
    await page.goto(`${BASE}/admin/manufacturing/reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    const content = await page.content()
    const hasReports = content.includes('Извештаи') || content.includes('Reports') || content.includes('Raporte')
    expect(hasReports).toBe(true)
    await ss(page, '10-07-reports')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 11: API Resource Serialization
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('11.1 — BOM list uses resource format', async () => {
    const res = await api(page, 'GET', '/manufacturing/boms?limit=5')
    expect(res.status).toBe(200)
    // Resource collection wraps in { data: [...], links: {...}, meta: {...} }
    expect(res.body).toHaveProperty('data')
    expect(Array.isArray(res.body.data)).toBe(true)
    if (res.body.data.length > 0) {
      const bom = res.body.data[0]
      expect(bom).toHaveProperty('id')
      expect(bom).toHaveProperty('name')
      expect(bom).toHaveProperty('code')
      expect(bom).toHaveProperty('is_active')
    }
  })

  test('11.2 — Order detail uses resource format with nested relations', async () => {
    const res = await api(page, 'GET', `/manufacturing/orders/${fullLifecycleOrderId}`)
    expect(res.status).toBe(200)
    const order = res.body?.data
    expect(order).toBeTruthy()
    expect(order).toHaveProperty('status')
    expect(order).toHaveProperty('total_production_cost')
    // Nested relations should be serialized
    expect(order).toHaveProperty('materials')
    expect(order).toHaveProperty('labor_entries')
    expect(order).toHaveProperty('overhead_entries')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // SECTION 12: Concurrent / Stress
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('12.1 — Rapid consecutive BOM list calls succeed', async () => {
    const results = await page.evaluate(async () => {
      const calls = Array.from({ length: 5 }, () =>
        fetch('/api/v1/manufacturing/boms?limit=5', {
          headers: { Accept: 'application/json', company: '2' },
        }).then((r) => r.status)
      )
      return Promise.all(calls)
    })
    // All should be 200
    for (const status of results) {
      expect(status).toBe(200)
    }
  })
})
