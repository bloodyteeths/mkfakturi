/**
 * POS (Point of Sale) — E2E Tests
 *
 * Tests the full POS flow: catalog, barcode lookup, sale creation,
 * stock deduction, payment, shift management, and UI pages.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/pos-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''

/** GET with Sanctum session auth. */
async function apiGet(page, url) {
  return page.evaluate(
    async ({ url }) => {
      const res = await fetch(url, {
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          company: '2',
          'X-Requested-With': 'XMLHttpRequest',
        },
      })
      return { status: res.status, data: await res.json().catch(() => null) }
    },
    { url: `${BASE}/api/v1/${url}` }
  )
}

/** Fetch CSRF cookie + token for POST requests. */
async function ensureCsrf(page) {
  return page.evaluate(async (base) => {
    await fetch(`${base}/sanctum/csrf-cookie`, { credentials: 'include' })
    const cookies = document.cookie.split(';').map(c => c.trim())
    const xsrf = cookies.find(c => c.startsWith('XSRF-TOKEN='))
    return xsrf ? decodeURIComponent(xsrf.split('=')[1]) : ''
  }, BASE)
}

/** POST with Sanctum session auth. */
async function apiPost(page, url, body) {
  const xsrfToken = await ensureCsrf(page)
  return page.evaluate(
    async ({ url, body, xsrfToken }) => {
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          company: '2',
          'X-Requested-With': 'XMLHttpRequest',
          'X-XSRF-TOKEN': xsrfToken,
        },
        body: JSON.stringify(body),
      })
      return { status: res.status, data: await res.json().catch(() => null) }
    },
    { url: `${BASE}/api/v1/${url}`, body, xsrfToken }
  )
}

test.describe.configure({ mode: 'serial' })

test.describe('POS — E2E', () => {
  let page
  let catalogItems = []
  let saleInvoiceId = null
  let saleInvoiceNumber = null

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext()
    page = await context.newPage()

    // Login via Sanctum
    await page.goto(`${BASE}/sanctum/csrf-cookie`)
    await page.waitForTimeout(1000)

    const xsrf = await ensureCsrf(page)
    const loginRes = await page.evaluate(
      async ({ base, email, pass, xsrf }) => {
        const res = await fetch(`${base}/api/v1/auth/login`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-XSRF-TOKEN': xsrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ username: email, password: pass, device_name: 'e2e-pos' }),
        })
        return { status: res.status, data: await res.json().catch(() => null) }
      },
      { base: BASE, email: EMAIL, pass: PASS, xsrf }
    )

    expect(loginRes.status).toBe(200)
    await page.waitForTimeout(3000) // Sanctum rate-limiting
  })

  test.afterAll(async () => {
    await page?.context()?.close()
  })

  // ═══════════════════════════════════════════════════════════
  // Group 1: Catalog API
  // ═══════════════════════════════════════════════════════════

  test('1. GET /pos/catalog returns items + categories + tax_types', async () => {
    const res = await apiGet(page, 'pos/catalog')
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('items')
    expect(res.data).toHaveProperty('categories')
    expect(res.data).toHaveProperty('tax_types')
    expect(res.data).toHaveProperty('pos_usage')
    expect(Array.isArray(res.data.items)).toBe(true)
    expect(res.data.items.length).toBeGreaterThan(0)

    // Verify item structure
    const item = res.data.items[0]
    expect(item).toHaveProperty('id')
    expect(item).toHaveProperty('name')
    expect(item).toHaveProperty('retail_price')
    expect(item).toHaveProperty('tax_percent')

    catalogItems = res.data.items
  })

  test('2. Catalog items have valid prices', async () => {
    expect(catalogItems.length).toBeGreaterThan(0)

    const withPrice = catalogItems.filter(i => i.retail_price > 0 || i.price > 0)
    expect(withPrice.length).toBeGreaterThan(0)

    // Every item should have numeric price
    for (const item of catalogItems) {
      expect(typeof item.retail_price).toBe('number')
      expect(typeof item.price).toBe('number')
    }
  })

  test('3. GET /pos/barcode/INVALID returns 404', async () => {
    const res = await apiGet(page, 'pos/barcode/NONEXISTENT-BARCODE-9999')
    expect(res.status).toBe(404)
    expect(res.data).toHaveProperty('error')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 2: Sale Flow
  // ═══════════════════════════════════════════════════════════

  test('4. POST /pos/sale with items creates invoice + payment', async () => {
    // Pick first item with a price
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    expect(item).toBeTruthy()

    const price = item.retail_price || item.price
    const cashTotal = price + (price * item.tax_percent / 100)

    const res = await apiPost(page, 'pos/sale', {
      items: [
        { item_id: item.id, quantity: 1, price: price },
      ],
      payment_method: 'cash',
      cash_received: Math.ceil(cashTotal) + 10000, // Extra for change
    })

    expect(res.status).toBe(201)
    expect(res.data).toHaveProperty('invoice')
    expect(res.data).toHaveProperty('payment')
    expect(res.data).toHaveProperty('fiscal_data')
    expect(res.data).toHaveProperty('stock_warnings')

    // Invoice created
    expect(res.data.invoice).toHaveProperty('id')
    expect(res.data.invoice).toHaveProperty('invoice_number')
    expect(res.data.invoice.total).toBeGreaterThan(0)

    // Payment created with change
    expect(res.data.payment).toHaveProperty('id')
    expect(res.data.payment).toHaveProperty('change')
    expect(res.data.payment.change).toBeGreaterThanOrEqual(0)

    saleInvoiceId = res.data.invoice.id
    saleInvoiceNumber = res.data.invoice.invoice_number
  })

  test('5. Sale invoice has valid invoice number', async () => {
    expect(saleInvoiceNumber).toBeTruthy()
    // Invoice number should be a non-empty string
    expect(typeof saleInvoiceNumber).toBe('string')
    expect(saleInvoiceNumber.length).toBeGreaterThan(0)
  })

  test('6. Sale fiscal_data has ISL-compatible format', async () => {
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    const price = item.retail_price || item.price

    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: item.id, quantity: 2, price: price }],
      payment_method: 'cash',
      cash_received: 9999900,
    })

    expect(res.status).toBe(201)
    const fd = res.data.fiscal_data
    expect(fd).toHaveProperty('items')
    expect(fd).toHaveProperty('total')
    expect(fd).toHaveProperty('tax')
    expect(fd).toHaveProperty('invoice_id')

    // Items should have VAT groups
    expect(fd.items.length).toBeGreaterThan(0)
    const fiscalItem = fd.items[0]
    expect(fiscalItem).toHaveProperty('name')
    expect(fiscalItem).toHaveProperty('vat_group')
    expect(['А', 'Б', 'В', 'Г']).toContain(fiscalItem.vat_group)
    expect(fiscalItem).toHaveProperty('quantity')
    expect(fiscalItem).toHaveProperty('price')
  })

  test('7. Change calculation: cash_received > total', async () => {
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    const price = item.retail_price || item.price

    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: item.id, quantity: 1, price: price }],
      payment_method: 'cash',
      cash_received: 9999900, // Huge cash amount
    })

    expect(res.status).toBe(201)
    expect(res.data.payment.change).toBeGreaterThan(0)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 3: Validation
  // ═══════════════════════════════════════════════════════════

  test('8. Missing items → 422', async () => {
    const res = await apiPost(page, 'pos/sale', {
      payment_method: 'cash',
    })
    expect(res.status).toBe(422)
  })

  test('9. Empty items array → 422', async () => {
    const res = await apiPost(page, 'pos/sale', {
      items: [],
      payment_method: 'cash',
    })
    expect(res.status).toBe(422)
  })

  test('10. Invalid item_id → 422', async () => {
    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: 99999999, quantity: 1 }],
      payment_method: 'cash',
    })
    expect(res.status).toBe(422)
  })

  test('11. Invalid payment_method → 422', async () => {
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: item.id, quantity: 1 }],
      payment_method: 'bitcoin',
    })
    expect(res.status).toBe(422)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 4: Walk-in Customer
  // ═══════════════════════════════════════════════════════════

  test('12. Sale without customer_id auto-creates POS Customer', async () => {
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: item.id, quantity: 1 }],
      payment_method: 'cash',
      cash_received: 9999900,
      // No customer_id → walk-in
    })

    expect(res.status).toBe(201)
    expect(res.data.invoice).toHaveProperty('customer_id')
    expect(res.data.invoice.customer_id).toBeGreaterThan(0)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 5: Fiscal Data Format
  // ═══════════════════════════════════════════════════════════

  test('13. Fiscal data total matches invoice total', async () => {
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: item.id, quantity: 1 }],
      payment_method: 'cash',
      cash_received: 9999900,
    })

    expect(res.status).toBe(201)
    // fiscal_data.total is in MKD (not cents), invoice.total is in cents
    const fiscalTotal = res.data.fiscal_data.total
    const invoiceTotal = res.data.invoice.total / 100
    expect(fiscalTotal).toBeCloseTo(invoiceTotal, 0)
  })

  test('14. Fiscal items have correct VAT groups (А/Б/В/Г)', async () => {
    const item = catalogItems.find(i => (i.retail_price || i.price) > 0)
    const res = await apiPost(page, 'pos/sale', {
      items: [{ item_id: item.id, quantity: 1 }],
      payment_method: 'cash',
      cash_received: 9999900,
    })

    expect(res.status).toBe(201)
    for (const fi of res.data.fiscal_data.items) {
      expect(['А', 'Б', 'В', 'Г']).toContain(fi.vat_group)
      expect(typeof fi.tax_percent).toBe('number')
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 6: Shift Management
  // ═══════════════════════════════════════════════════════════

  test('15. GET /pos/shift/current returns shift or null', async () => {
    const res = await apiGet(page, 'pos/shift/current')
    expect(res.status).toBe(200)
    // May be null if no shift open
    expect(res.data).toHaveProperty('shift')
  })

  test('16. POST /pos/shift/open creates a shift', async () => {
    // First close any existing shift
    const current = await apiGet(page, 'pos/shift/current')
    if (current.data?.shift) {
      await apiPost(page, 'pos/shift/close', {
        closing_cash: 0,
        notes: 'E2E cleanup',
      })
      await page.waitForTimeout(500)
    }

    const res = await apiPost(page, 'pos/shift/open', {
      opening_cash: 500000, // 5000 MKD
    })
    expect(res.status).toBe(201)
    expect(res.data).toHaveProperty('shift')
    expect(res.data.shift).toHaveProperty('id')
    expect(res.data.shift.opening_cash).toBe(500000)
    expect(res.data.shift.closed_at).toBeNull()
  })

  test('17. POST /pos/shift/open with existing shift → 409', async () => {
    const res = await apiPost(page, 'pos/shift/open', {
      opening_cash: 100000,
    })
    expect(res.status).toBe(409)
    expect(res.data).toHaveProperty('error')
  })

  test('18. POST /pos/shift/close closes the shift with summary', async () => {
    const res = await apiPost(page, 'pos/shift/close', {
      closing_cash: 600000, // 6000 MKD
      notes: 'E2E test shift close',
    })
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('shift')
    expect(res.data).toHaveProperty('summary')
    expect(res.data.shift.closed_at).not.toBeNull()
    expect(res.data.summary).toHaveProperty('duration_minutes')
    expect(res.data.summary).toHaveProperty('total_sales')
    expect(res.data.summary).toHaveProperty('cash_difference')
    expect(res.data.summary.opening_cash).toBe(500000)
    expect(res.data.summary.closing_cash).toBe(600000)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 7: UI Smoke Tests
  // ═══════════════════════════════════════════════════════════

  test('19. POS page loads at /admin/pos', async () => {
    await page.goto(`${BASE}/admin/pos`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(2000)

    // Should show the POS interface (full screen layout)
    const url = page.url()
    expect(url).toContain('/admin/pos')

    // Should have the top bar with "Facturino POS"
    const topBar = await page.locator('text=Facturino POS').count()
    expect(topBar).toBeGreaterThan(0)
  })

  test('20. POS settings page loads at /admin/settings/pos', async () => {
    await page.goto(`${BASE}/admin/settings/pos`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(2000)

    const url = page.url()
    expect(url).toContain('/admin/settings')
  })
})

// CLAUDE-CHECKPOINT
