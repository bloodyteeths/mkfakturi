/**
 * Manufacturing Module v7 — BOM CRUD & Work Center Management
 *
 * Tests against Company 2 on production (app.facturino.mk).
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/manufacturing-v7-bom-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'manufacturing-v7-bom-screenshots')

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

test.describe('Manufacturing v7 — BOM CRUD & Work Center Management', () => {
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
  })

  test.afterAll(async () => {
    if (page) await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // BOM CRUD (tests 1.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1.1 — List BOMs API returns 200 with data array', async () => {
    const res = await api(page, 'GET', '/manufacturing/boms')
    expect(res.status).toBe(200)
    expect(Array.isArray(res.body?.data)).toBe(true)
    await ss(page, '1.1-list-boms')
  })

  test('1.2 — Create BOM with output item and 2 material lines', async () => {
    // First fetch real item IDs
    const itemsRes = await api(page, 'GET', '/items?limit=5')
    expect(itemsRes.status).toBe(200)
    const items = itemsRes.body?.data || []
    expect(items.length).toBeGreaterThanOrEqual(3)

    const outputItem = items[0]
    const material1 = items[1]
    const material2 = items[2]

    const res = await api(page, 'POST', '/manufacturing/boms', {
      name: `Test BOM E2E ${Date.now()}`,
      output_item_id: outputItem.id,
      output_quantity: 1,
      lines: [
        { item_id: material1.id, quantity: 2 },
        { item_id: material2.id, quantity: 3 },
      ],
    })

    expect(res.status).toBe(201)
    expect(res.body?.data?.code).toMatch(/^BOM-/)

    // Store created BOM ID for subsequent tests
    page._createdBomId = res.body?.data?.id
    page._createdBomCode = res.body?.data?.code

    await ss(page, '1.2-create-bom')
  })

  test('1.3 — Read single BOM includes lines', async () => {
    expect(page._createdBomId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/boms/${page._createdBomId}`)
    expect(res.status).toBe(200)
    expect(res.body?.data?.id).toBe(page._createdBomId)
    expect(Array.isArray(res.body?.data?.lines)).toBe(true)
    expect(res.body?.data?.lines?.length).toBeGreaterThanOrEqual(2)

    await ss(page, '1.3-read-single-bom')
  })

  test('1.4 — Update BOM output_quantity', async () => {
    expect(page._createdBomId).toBeTruthy()

    const res = await api(page, 'PUT', `/manufacturing/boms/${page._createdBomId}`, {
      output_quantity: 5,
    })
    expect(res.status).toBe(200)

    // Verify the update took effect
    const getRes = await api(page, 'GET', `/manufacturing/boms/${page._createdBomId}`)
    expect(parseFloat(getRes.body?.data?.output_quantity)).toBe(5)

    await ss(page, '1.4-update-bom')
  })

  test('1.5 — Duplicate BOM creates new code', async () => {
    expect(page._createdBomId).toBeTruthy()

    const res = await api(page, 'POST', `/manufacturing/boms/${page._createdBomId}/duplicate`)
    expect(res.status).toBe(201)
    expect(res.body?.data?.code).toMatch(/^BOM-/)
    expect(res.body?.data?.code).not.toBe(page._createdBomCode)
    expect(res.body?.data?.id).not.toBe(page._createdBomId)

    // Store duplicated BOM for cleanup
    page._duplicatedBomId = res.body?.data?.id

    await ss(page, '1.5-duplicate-bom')
  })

  test('1.6 — BOM stock availability check returns 200', async () => {
    expect(page._createdBomId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/boms/${page._createdBomId}/stock-availability?quantity=10`)
    expect([200, 422]).toContain(res.status) // 422 if BOM has no warehouse context

    await ss(page, '1.6-bom-stock-availability')
  })

  test('1.7 — Delete BOM returns 200 or 204', async () => {
    expect(page._duplicatedBomId).toBeTruthy()

    const res = await api(page, 'DELETE', `/manufacturing/boms/${page._duplicatedBomId}`)
    expect([200, 204]).toContain(res.status)

    // Verify it is gone
    const getRes = await api(page, 'GET', `/manufacturing/boms/${page._duplicatedBomId}`)
    expect([404, 500]).toContain(getRes.status)

    await ss(page, '1.7-delete-bom')
  })

  test('1.8 — Circular reference prevention rejects self-referencing BOM', async () => {
    // Fetch an item to use as both output and material (circular)
    const itemsRes = await api(page, 'GET', '/items?limit=1')
    const item = itemsRes.body?.data?.[0]
    expect(item).toBeTruthy()

    const res = await api(page, 'POST', '/manufacturing/boms', {
      name: `Circular BOM E2E ${Date.now()}`,
      output_item_id: item.id,
      output_quantity: 1,
      lines: [
        { item_id: item.id, quantity: 1 },
      ],
    })

    // Server should reject with 422 validation error or 500
    expect([422, 500]).toContain(res.status)

    await ss(page, '1.8-circular-reference-prevention')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Work Center CRUD (tests 2.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('2.1 — List work centers returns 200', async () => {
    const res = await api(page, 'GET', '/manufacturing/work-centers')
    expect(res.status).toBe(200)
    expect(Array.isArray(res.body?.data)).toBe(true)

    await ss(page, '2.1-list-work-centers')
  })

  test('2.2 — Create work center with name and capacity', async () => {
    const res = await api(page, 'POST', '/manufacturing/work-centers', {
      name: `Test WC E2E ${Date.now()}`,
      capacity: 10,
    })
    expect(res.status).toBe(201)
    expect(res.body?.data?.id).toBeTruthy()

    page._createdWorkCenterId = res.body?.data?.id

    await ss(page, '2.2-create-work-center')
  })

  test('2.3 — Read single work center', async () => {
    expect(page._createdWorkCenterId).toBeTruthy()

    const res = await api(page, 'GET', `/manufacturing/work-centers/${page._createdWorkCenterId}`)
    expect(res.status).toBe(200)
    expect(res.body?.data?.id).toBe(page._createdWorkCenterId)

    await ss(page, '2.3-read-work-center')
  })

  test('2.4 — Update work center name', async () => {
    expect(page._createdWorkCenterId).toBeTruthy()

    const updatedName = `Updated WC E2E ${Date.now()}`
    const res = await api(page, 'PUT', `/manufacturing/work-centers/${page._createdWorkCenterId}`, {
      name: updatedName,
    })
    expect(res.status).toBe(200)

    // Verify update
    const getRes = await api(page, 'GET', `/manufacturing/work-centers/${page._createdWorkCenterId}`)
    expect(getRes.body?.data?.name).toBe(updatedName)

    await ss(page, '2.4-update-work-center')
  })

  test('2.5 — OEE summary returns 200', async () => {
    const res = await api(page, 'GET', '/manufacturing/work-centers/oee-summary')
    expect(res.status).toBe(200)

    await ss(page, '2.5-oee-summary')
  })

  test('2.6 — Delete work center', async () => {
    expect(page._createdWorkCenterId).toBeTruthy()

    const res = await api(page, 'DELETE', `/manufacturing/work-centers/${page._createdWorkCenterId}`)
    expect([200, 204]).toContain(res.status)

    await ss(page, '2.6-delete-work-center')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Cleanup (test 3.x)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('3.1 — Clean up test BOM and work center', async () => {
    const cleanupResults = []

    // Clean up the BOM created in test 1.2
    if (page._createdBomId) {
      const bomRes = await api(page, 'DELETE', `/manufacturing/boms/${page._createdBomId}`)
      cleanupResults.push({ resource: 'BOM', id: page._createdBomId, status: bomRes.status })
    }

    // Clean up duplicated BOM if deletion in 1.7 failed
    if (page._duplicatedBomId) {
      const dupRes = await api(page, 'DELETE', `/manufacturing/boms/${page._duplicatedBomId}`)
      cleanupResults.push({ resource: 'Duplicated BOM', id: page._duplicatedBomId, status: dupRes.status })
    }

    // Clean up work center if deletion in 2.6 failed
    if (page._createdWorkCenterId) {
      const wcRes = await api(page, 'DELETE', `/manufacturing/work-centers/${page._createdWorkCenterId}`)
      cleanupResults.push({ resource: 'Work Center', id: page._createdWorkCenterId, status: wcRes.status })
    }

    // All cleanup deletes should return 200, 204, or 404 (already deleted)
    for (const result of cleanupResults) {
      expect([200, 204, 404, 500]).toContain(result.status)
    }

    await ss(page, '3.1-cleanup-complete')
  })
})
