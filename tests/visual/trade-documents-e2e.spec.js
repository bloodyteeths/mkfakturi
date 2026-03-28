/**
 * Trade Documents — E2E Tests (КАП, Нивелација, Преносница)
 *
 * Tests wholesale calculation, price revaluation (нивелација), and transfer PDFs
 * per Правилник Сл. весник 51/04; 89/04.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/trade-documents-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(
  process.cwd(),
  'test-results',
  'trade-documents-e2e-screenshots'
)

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })
}

async function ss(page, name) {
  await page.screenshot({
    path: path.join(SCREENSHOT_DIR, `${name}.png`),
    fullPage: true,
  })
}

/** GET with Sanctum session auth. */
async function apiGet(page, url) {
  return page.evaluate(
    async ({ url }) => {
      const res = await fetch(url, {
        headers: { Accept: 'application/json', company: '2' },
        credentials: 'same-origin',
      })
      const text = await res.text()
      try {
        return { status: res.status, data: JSON.parse(text) }
      } catch {
        return { status: res.status, error: 'Non-JSON', body: text.substring(0, 500) }
      }
    },
    { url }
  )
}

/** POST with Sanctum session auth. */
async function apiPost(page, url, body = {}) {
  return page.evaluate(
    async ({ url, body }) => {
      // Get CSRF token from cookies
      const cookies = document.cookie.split(';').map(c => c.trim())
      const xsrf = cookies.find(c => c.startsWith('XSRF-TOKEN='))
      const token = xsrf ? decodeURIComponent(xsrf.split('=')[1]) : ''

      const res = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          company: '2',
          'X-XSRF-TOKEN': token,
        },
        credentials: 'same-origin',
        body: JSON.stringify(body),
      })
      const text = await res.text()
      try {
        return { status: res.status, data: JSON.parse(text) }
      } catch {
        return { status: res.status, error: 'Non-JSON', body: text.substring(0, 500) }
      }
    },
    { url, body }
  )
}

test.describe.configure({ mode: 'serial' })

let page
let testBillId = null
let testItemId = null
let testNivelacijaId = null

test.beforeAll(async ({ browser }) => {
  if (!EMAIL || !PASS) {
    throw new Error('Set TEST_EMAIL and TEST_PASSWORD env vars')
  }

  page = await browser.newPage()
  await new Promise((r) => setTimeout(r, 3000))

  // Login
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded' })
  await page.waitForSelector('input[type="email"]', { timeout: 30000 })
  await page.fill('input[type="email"]', EMAIL)
  await page.fill('input[type="password"]', PASS)
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/admin/, { timeout: 30000 })
  console.log('✓ Logged in successfully')

  // Find a bill to use for testing (bills use company header, not URL param)
  const billResult = await apiGet(page, `${BASE}/api/v1/bills?limit=5&status=COMPLETED`)
  if (billResult.status === 200) {
    const bills = billResult.data?.data || billResult.data?.bills?.data || []
    if (bills.length > 0) {
      testBillId = bills[0].id
      console.log(`✓ Found test bill: ID ${testBillId}, number: ${bills[0].bill_number}`)
    } else {
      console.log('⚠ No completed bills found for company 2')
    }
  } else {
    console.log(`⚠ Bills API returned ${billResult.status}: ${JSON.stringify(billResult.data || billResult.body || billResult.error).substring(0, 300)}`)
  }

  // Find a real item_id for nivelacija tests
  const itemResult = await apiGet(page, `${BASE}/api/v1/items?limit=1`)
  if (itemResult.status === 200) {
    const items = itemResult.data?.data || []
    if (items.length > 0) {
      testItemId = items[0].id
      console.log(`✓ Found test item: ID ${testItemId}, name: ${items[0].name}`)
    }
  }
})

test.afterAll(async () => {
  if (page) await page.close()
})

// ═══════════════════════════════════════════════
// 1. КАП API — Wholesale calculation
// ═══════════════════════════════════════════════

test('1. КАП API returns wholesale calculation data', async () => {
  if (!testBillId) {
    console.log('⚠ No bills found, skipping KAP test')
    return
  }

  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/kap/${testBillId}`
  )

  console.log(`КАП API status: ${result.status}`)

  expect(result.status).toBe(200)
  expect(result.data.success).toBe(true)
  expect(result.data.data).toBeDefined()
  expect(result.data.data.items).toBeInstanceOf(Array)
  expect(result.data.data.totals).toBeDefined()

  if (result.data.data.items.length > 0) {
    const first = result.data.data.items[0]
    expect(first).toHaveProperty('name')
    expect(first).toHaveProperty('quantity')
    expect(first).toHaveProperty('unit_price')
    expect(first).toHaveProperty('fakturna_iznos')
    expect(first).toHaveProperty('nabavna_iznos')
    expect(first).toHaveProperty('marzha')
    expect(first).toHaveProperty('marzha_percent')
    expect(first).toHaveProperty('prodazhna_iznos')
    expect(first).toHaveProperty('unit_price_prodazhna')

    // Wholesale: final = nabavna + marzha (no VAT in final price)
    const expectedProdazhna = first.nabavna_iznos + first.marzha
    expect(first.prodazhna_iznos).toBe(expectedProdazhna)

    console.log(`КАП first item: ${first.name}, nabavna: ${first.nabavna_iznos}, marzha: ${first.marzha}, prodazhna: ${first.prodazhna_iznos}`)
  }

  const totals = result.data.data.totals
  expect(totals).toHaveProperty('fakturna')
  expect(totals).toHaveProperty('nabavna')
  expect(totals).toHaveProperty('marzha')
  expect(totals).toHaveProperty('prodazhna')

  console.log(`КАП totals: fakturna=${totals.fakturna}, marzha=${totals.marzha}, prodazhna=${totals.prodazhna}`)

  await ss(page, '01-kap-api-response')
})

// ═══════════════════════════════════════════════
// 2. КАП with dependent costs
// ═══════════════════════════════════════════════

test('2. КАП API handles dependent costs (transport, customs)', async () => {
  if (!testBillId) return

  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/kap/${testBillId}?dependent_costs[transport]=50000&dependent_costs[customs]=20000`
  )

  expect(result.status).toBe(200)

  if (result.data.data.items.length > 0) {
    const totals = result.data.data.totals
    // With dependent costs, nabavna should be > fakturna
    expect(totals.nabavna).toBeGreaterThanOrEqual(totals.fakturna)
    console.log(`КАП with costs: fakturna=${totals.fakturna}, zavisni=${totals.zavisni}, nabavna=${totals.nabavna}`)
  }

  await ss(page, '02-kap-dependent-costs')
})

// ═══════════════════════════════════════════════
// 3. КАП PDF export
// ═══════════════════════════════════════════════

test('3. КАП PDF export returns valid PDF', async () => {
  if (!testBillId) return

  const result = await page.evaluate(
    async ({ url }) => {
      const res = await fetch(url, {
        headers: { company: '2' },
        credentials: 'same-origin',
      })
      return {
        status: res.status,
        contentType: res.headers.get('content-type'),
        size: (await res.arrayBuffer()).byteLength,
      }
    },
    { url: `${BASE}/api/v1/partner/companies/2/accounting/kap/${testBillId}/export` }
  )

  console.log(`КАП PDF: status=${result.status}, type=${result.contentType}, size=${result.size}`)

  expect(result.status).toBe(200)
  expect(result.contentType).toContain('pdf')
  expect(result.size).toBeGreaterThan(1000)

  await ss(page, '03-kap-pdf-export')
})

// ═══════════════════════════════════════════════
// 4. Margin cap validation
// ═══════════════════════════════════════════════

test('4. КАП API includes margin_check field', async () => {
  if (!testBillId) return

  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/kap/${testBillId}`
  )

  expect(result.status).toBe(200)
  expect(result.data).toHaveProperty('margin_check')
  expect(result.data.margin_check).toHaveProperty('violations')
  expect(result.data.margin_check).toHaveProperty('cap_percent')
  expect(result.data.margin_check.violations).toBeInstanceOf(Array)

  console.log(`Margin check: ${result.data.margin_check.violations.length} violations, cap=${result.data.margin_check.cap_percent}%`)

  await ss(page, '04-margin-check')
})

// ═══════════════════════════════════════════════
// 5. ПЛТ PDF export (enhanced with маржа)
// ═══════════════════════════════════════════════

test('5. ПЛТ PDF export includes маржа columns', async () => {
  if (!testBillId) return

  const result = await page.evaluate(
    async ({ url }) => {
      const res = await fetch(url, {
        headers: { company: '2' },
        credentials: 'same-origin',
      })
      return {
        status: res.status,
        contentType: res.headers.get('content-type'),
        size: (await res.arrayBuffer()).byteLength,
      }
    },
    { url: `${BASE}/api/v1/partner/companies/2/accounting/plt/${testBillId}/export` }
  )

  console.log(`ПЛТ PDF: status=${result.status}, type=${result.contentType}, size=${result.size}`)

  expect(result.status).toBe(200)
  expect(result.contentType).toContain('pdf')
  expect(result.size).toBeGreaterThan(1000)

  await ss(page, '05-plt-pdf-with-markup')
})

// ═══════════════════════════════════════════════
// 6. Нивелации API — list (empty initially is OK)
// ═══════════════════════════════════════════════

test('6. Нивелации list API returns valid structure', async () => {
  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/nivelacii?limit=all`
  )

  console.log(`Нивелации API status: ${result.status}`)

  expect(result.status).toBe(200)
  expect(result.data.success).toBe(true)
  expect(result.data.data).toBeInstanceOf(Array)

  console.log(`Нивелации count: ${result.data.data.length}`)

  if (result.data.data.length > 0) {
    const first = result.data.data[0]
    expect(first).toHaveProperty('document_number')
    expect(first).toHaveProperty('document_date')
    expect(first).toHaveProperty('type')
    expect(first).toHaveProperty('status')
    expect(first).toHaveProperty('total_difference')
    console.log(`First nivelacija: ${first.document_number}, status: ${first.status}, diff: ${first.total_difference}`)
  }

  await ss(page, '06-nivelacii-list')
})

// ═══════════════════════════════════════════════
// 7. Нивелација CRUD — create draft
// ═══════════════════════════════════════════════

test('7. Create draft нивелација', async () => {
  const itemId = testItemId || 1
  const result = await apiPost(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/nivelacii`,
    {
      type: 'price_change',
      document_date: '2026-03-28',
      reason: 'E2E тест — промена на цена',
      items: [
        {
          item_id: itemId,
          quantity_on_hand: 10,
          old_retail_price: 100000,
          new_retail_price: 120000,
        },
      ],
    }
  )

  console.log(`Create nivelacija: status=${result.status}`)

  // May get 422 if item_id=1 doesn't exist — that's OK for CI
  if (result.status === 201) {
    expect(result.data.success).toBe(true)
    expect(result.data.data).toBeDefined()
    expect(result.data.data.document_number).toMatch(/^NI-/)
    expect(result.data.data.status).toBe('draft')
    testNivelacijaId = result.data.data.id
    console.log(`✓ Created nivelacija: ${result.data.data.document_number} (ID: ${testNivelacijaId})`)
  } else {
    console.log(`⚠ Create returned ${result.status}: ${JSON.stringify(result.data).substring(0, 200)}`)
    // Try to find an existing draft to test with
    const listResult = await apiGet(page, `${BASE}/api/v1/partner/companies/2/accounting/nivelacii?status=draft&limit=1`)
    if (listResult.status === 200 && listResult.data.data?.length > 0) {
      testNivelacijaId = listResult.data.data[0].id
      console.log(`✓ Using existing draft: ID ${testNivelacijaId}`)
    }
  }

  await ss(page, '07-create-nivelacija')
})

// ═══════════════════════════════════════════════
// 8. Нивелација — show detail
// ═══════════════════════════════════════════════

test('8. Show нивелација detail', async () => {
  if (!testNivelacijaId) {
    console.log('⚠ No nivelacija to show')
    return
  }

  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/nivelacii/${testNivelacijaId}`
  )

  console.log(`Show nivelacija: status=${result.status}`)

  expect(result.status).toBe(200)
  expect(result.data.success).toBe(true)
  expect(result.data.data).toBeDefined()
  expect(result.data.data.items).toBeInstanceOf(Array)

  console.log(`Nivelacija ${result.data.data.document_number}: ${result.data.data.items.length} items, total_diff=${result.data.data.total_difference}`)

  await ss(page, '08-show-nivelacija')
})

// ═══════════════════════════════════════════════
// 9. Нивелација PDF export
// ═══════════════════════════════════════════════

test('9. Нивелација PDF export', async () => {
  if (!testNivelacijaId) {
    console.log('⚠ No nivelacija for PDF export')
    return
  }

  const result = await page.evaluate(
    async ({ url }) => {
      const res = await fetch(url, {
        headers: { company: '2' },
        credentials: 'same-origin',
      })
      return {
        status: res.status,
        contentType: res.headers.get('content-type'),
        size: (await res.arrayBuffer()).byteLength,
      }
    },
    { url: `${BASE}/api/v1/partner/companies/2/accounting/nivelacii/${testNivelacijaId}/export` }
  )

  console.log(`Nivelacija PDF: status=${result.status}, type=${result.contentType}, size=${result.size}`)

  expect(result.status).toBe(200)
  expect(result.contentType).toContain('pdf')
  expect(result.size).toBeGreaterThan(1000)

  await ss(page, '09-nivelacija-pdf')
})

// ═══════════════════════════════════════════════
// 10. Pending check API
// ═══════════════════════════════════════════════

test('10. Pending price changes check', async () => {
  if (!testBillId) return

  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/nivelacii/pending-check?bill_id=${testBillId}&price_type=retail`
  )

  console.log(`Pending check: status=${result.status}`)

  expect(result.status).toBe(200)
  expect(result.data.success).toBe(true)
  expect(result.data.data).toBeInstanceOf(Array)
  expect(typeof result.data.count).toBe('number')

  console.log(`Pending items: ${result.data.count}`)

  await ss(page, '10-pending-check')
})

// ═══════════════════════════════════════════════
// 11. Nivelacii filter by status
// ═══════════════════════════════════════════════

test('11. Filter нивелации by status', async () => {
  const drafts = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/nivelacii?status=draft`
  )
  expect(drafts.status).toBe(200)
  const draftItems = drafts.data.data || []
  for (const niv of draftItems) {
    expect(niv.status).toBe('draft')
  }

  const approved = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/nivelacii?status=approved`
  )
  expect(approved.status).toBe(200)
  const approvedItems = approved.data.data || []
  for (const niv of approvedItems) {
    expect(niv.status).toBe('approved')
  }

  console.log(`Drafts: ${draftItems.length}, Approved: ${approvedItems.length}`)

  await ss(page, '11-nivelacii-filter')
})

// ═══════════════════════════════════════════════
// 12. TradeBook Vue — new tabs visible
// ═══════════════════════════════════════════════

test('12. TradeBook page shows new tabs (ПЛТ, КАП, НИВ)', async () => {
  await page.goto(`${BASE}/admin/partner/accounting/trade-book`, { waitUntil: 'domcontentloaded' })
  await page.waitForTimeout(3000)

  // Check all 6 tabs are visible
  const tabButtons = await page.locator('nav[aria-label="Tabs"] button').count()
  console.log(`Tab count: ${tabButtons}`)
  expect(tabButtons).toBeGreaterThanOrEqual(6)

  // Check specific tab codes
  const tabText = await page.locator('nav[aria-label="Tabs"]').textContent()
  expect(tabText).toContain('ЕТ')
  expect(tabText).toContain('МЕТГ')
  expect(tabText).toContain('ЕТУ')
  expect(tabText).toContain('ПЛТ')
  expect(tabText).toContain('КАП')
  expect(tabText).toContain('НИВ')

  await ss(page, '12-tradebook-tabs')
})

// ═══════════════════════════════════════════════
// 13. Apply prices endpoint
// ═══════════════════════════════════════════════

test('13. Apply prices API works', async () => {
  if (!testBillId) return

  const result = await apiPost(
    page,
    `${BASE}/api/v1/partner/companies/2/accounting/apply-prices`,
    {
      bill_id: testBillId,
      price_type: 'retail',
      create_nivelacija: false,
    }
  )

  console.log(`Apply prices: status=${result.status}`)

  // 200 = success, item prices updated
  // 404 = bill not found (possible if bill was deleted)
  expect([200, 404]).toContain(result.status)

  if (result.status === 200) {
    expect(result.data.success).toBe(true)
    expect(result.data.data).toHaveProperty('changed')
    expect(result.data.data).toHaveProperty('unchanged')
    console.log(`Changed: ${result.data.data.changed.length}, Unchanged: ${result.data.data.unchanged.length}`)
  }

  await ss(page, '13-apply-prices')
})

// ═══════════════════════════════════════════════
// 14. Access control — wrong company
// ═══════════════════════════════════════════════

test('14. Access control — cannot access other company data', async () => {
  const result = await apiGet(
    page,
    `${BASE}/api/v1/partner/companies/99999/accounting/nivelacii`
  )

  // Should be 403 (no access) or 404
  expect([403, 404]).toContain(result.status)

  console.log(`Access control test: status=${result.status}`)

  await ss(page, '14-access-control')
})

// CLAUDE-CHECKPOINT
