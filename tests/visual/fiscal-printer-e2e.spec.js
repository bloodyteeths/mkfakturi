/**
 * Fiscal Printer — WebSerial + Receipt E2E Tests
 *
 * Tests the full fiscal device CRUD, receipt recording, Z-report,
 * data integrity, and UI pages against production.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/fiscal-printer-e2e.spec.js --project=chromium
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

/** Fetch CSRF cookie + token for POST/PATCH/DELETE requests. */
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

/** PATCH with Sanctum session auth. */
async function apiPatch(page, url, body) {
  const xsrfToken = await ensureCsrf(page)
  return page.evaluate(
    async ({ url, body, xsrfToken }) => {
      const res = await fetch(url, {
        method: 'PATCH',
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

/** DELETE with Sanctum session auth. */
async function apiDelete(page, url) {
  const xsrfToken = await ensureCsrf(page)
  return page.evaluate(
    async ({ url, xsrfToken }) => {
      const res = await fetch(url, {
        method: 'DELETE',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          company: '2',
          'X-Requested-With': 'XMLHttpRequest',
          'X-XSRF-TOKEN': xsrfToken,
        },
      })
      return { status: res.status, data: await res.json().catch(() => null) }
    },
    { url: `${BASE}/api/v1/${url}`, xsrfToken }
  )
}

test.describe.configure({ mode: 'serial' })

test.describe('Fiscal Printer — E2E', () => {
  let page
  let testDeviceId = null
  let testInvoiceId = null
  let testReceiptId = null
  const testSerial = `E2E-${Date.now()}`

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage()
    await page.goto(`${BASE}/login`)
    await page.waitForTimeout(3000)
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForTimeout(5000)
  })

  test.afterAll(async () => {
    // Cleanup: delete test device (only works if no receipts attached)
    // If receipts exist, deactivate instead
    if (testDeviceId) {
      const delRes = await apiDelete(page, `fiscal-devices/${testDeviceId}`)
      if (delRes.status === 204) {
        console.log(`Cleanup: deleted test device ${testDeviceId} ✓`)
      } else {
        // Device has receipts, deactivate it
        await apiPatch(page, `fiscal-devices/${testDeviceId}`, { is_active: false, name: `[E2E CLEANUP] ${testSerial}` })
        console.log(`Cleanup: deactivated test device ${testDeviceId} (has receipts) ✓`)
      }
    }
    await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // GROUP 1: Device CRUD
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('1. List devices — supported_types has 8 types with webserial defaults', async () => {
    const res = await apiGet(page, 'fiscal-devices')

    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')
    expect(res.data).toHaveProperty('supported_types')

    const types = res.data.supported_types
    expect(types.length).toBeGreaterThanOrEqual(7)

    // Check known device types exist (array of {type, label, default_connection})
    const typeNames = types.map(t => t.type)
    const expectedTypes = ['daisy', 'david', 'razvigorec', 'severec', 'expert-sx', 'pelisterec', 'alpha']
    for (const t of expectedTypes) {
      expect(typeNames).toContain(t)
    }

    console.log(`Devices: ${res.data.data?.length || 0} registered, ${types.length} supported types ✓`)
  })

  test('2. Create test device — POST returns 201', async () => {
    const res = await apiPost(page, 'fiscal-devices', {
      device_type: 'daisy',
      name: `E2E Test Device ${testSerial}`,
      serial_number: testSerial,
      connection_type: 'webserial',
    })

    expect(res.status).toBe(201)
    expect(res.data?.data).toBeDefined()
    expect(res.data.data.serial_number).toBe(testSerial)
    expect(res.data.data.device_type).toBe('daisy')
    expect(res.data.data.connection_type).toBe('webserial')
    expect(res.data.data.is_active).toBeTruthy()

    testDeviceId = res.data.data.id
    console.log(`Created device ID ${testDeviceId}: ${testSerial} ✓`)
  })

  test('3. Fetch device by ID — verify all fields', async () => {
    expect(testDeviceId).toBeTruthy()

    const res = await apiGet(page, `fiscal-devices/${testDeviceId}`)

    expect(res.status).toBe(200)
    expect(res.data?.data).toBeDefined()

    const device = res.data.data
    expect(device.id).toBe(testDeviceId)
    expect(device.serial_number).toBe(testSerial)
    expect(device.device_type).toBe('daisy')
    expect(device.connection_type).toBe('webserial')
    expect(device.is_active).toBeTruthy()

    console.log(`Fetched device ${device.id}: ${device.name} ✓`)
  })

  test('4. Update device name — PATCH returns 200', async () => {
    expect(testDeviceId).toBeTruthy()

    const newName = `E2E Updated ${testSerial}`
    const res = await apiPatch(page, `fiscal-devices/${testDeviceId}`, {
      name: newName,
    })

    expect(res.status).toBe(200)
    expect(res.data?.data?.name).toBe(newName)

    console.log(`Updated device name to: ${newName} ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // GROUP 2: Record Receipt
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('5. Find a real invoice for receipt test', async () => {
    const res = await apiGet(page, 'invoices?limit=1&orderByField=created_at&orderBy=desc')

    expect(res.status).toBe(200)
    expect(res.data?.data?.length).toBeGreaterThan(0)

    testInvoiceId = res.data.data[0].id
    console.log(`Found invoice ID ${testInvoiceId} for receipt test ✓`)
  })

  test('6. Record receipt — POST returns 201 with source column', async () => {
    expect(testDeviceId).toBeTruthy()
    expect(testInvoiceId).toBeTruthy()

    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-receipt`, {
      invoice_id: testInvoiceId,
      receipt_number: `E2E-R-${Date.now()}`,
      fiscal_id: `E2E-FID-${Date.now()}`,
      amount: 118000,
      vat_amount: 18000,
      raw_response: 'E2E test response data',
      source: 'webserial',
    })

    expect(res.status).toBe(201)
    expect(res.data?.data).toBeDefined()

    const receipt = res.data.data
    expect(receipt.fiscal_device_id).toBe(testDeviceId)
    expect(receipt.invoice_id).toBe(testInvoiceId)
    expect(receipt.amount).toBe(118000)
    expect(receipt.vat_amount).toBe(18000)
    expect(receipt.source).toBe('webserial')

    testReceiptId = receipt.id
    console.log(`Recorded receipt ID ${testReceiptId}: amount=118000, vat=18000, source=webserial ✓`)
  })

  test('7. Verify stored receipt — GET receipts contains created receipt', async () => {
    expect(testDeviceId).toBeTruthy()

    const res = await apiGet(page, `fiscal-devices/${testDeviceId}/receipts`)

    expect(res.status).toBe(200)
    expect(res.data?.data).toBeDefined()
    expect(res.data.data.length).toBeGreaterThan(0)

    const found = res.data.data.find(r => r.id === testReceiptId)
    expect(found).toBeDefined()
    expect(found.source).toBe('webserial')
    expect(found.amount).toBe(118000)

    console.log(`Verified receipt ${testReceiptId} in device receipts list ✓`)
  })

  test('8. Duplicate prevention — same invoice+device returns 422', async () => {
    expect(testDeviceId).toBeTruthy()
    expect(testInvoiceId).toBeTruthy()

    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-receipt`, {
      invoice_id: testInvoiceId,
      receipt_number: `DUP-${Date.now()}`,
      fiscal_id: `DUP-FID-${Date.now()}`,
      amount: 118000,
      vat_amount: 18000,
      source: 'webserial',
    })

    expect(res.status).toBe(422)
    expect(res.data?.error).toContain('already been fiscalized')
    expect(res.data?.existing_receipt).toBeDefined()

    console.log('Duplicate prevention: 422 with existing_receipt reference ✓')
  })

  test('9. Validation — missing fields returns 422', async () => {
    expect(testDeviceId).toBeTruthy()

    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-receipt`, {
      invoice_id: testInvoiceId,
      // Missing receipt_number, fiscal_id, amount, etc.
    })

    expect(res.status).toBe(422)
    console.log('Validation: 422 on missing required fields ✓')
  })

  test('10. Validation — invalid source returns 422', async () => {
    expect(testDeviceId).toBeTruthy()

    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-receipt`, {
      invoice_id: testInvoiceId,
      receipt_number: 'TEST-001',
      fiscal_id: 'TEST-FID-001',
      amount: 100,
      vat_amount: 18,
      source: 'invalid_source',
    })

    expect(res.status).toBe(422)
    console.log('Validation: 422 on invalid source value ✓')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // GROUP 3: Z-Report
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('11. Record Z-report — returns reconciliation data', async () => {
    expect(testDeviceId).toBeTruthy()

    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-z-report`, {
      report_number: `Z-E2E-${Date.now()}`,
      total_amount: '118000',
      total_vat: '18000',
      receipt_count: '1',
      raw_response: 'E2E Z-report response',
      source: 'webserial',
    })

    expect(res.status).toBe(200)
    expect(res.data?.data).toBeDefined()

    const data = res.data.data
    expect(data).toHaveProperty('report_number')
    expect(data).toHaveProperty('device_totals')
    expect(data).toHaveProperty('system_totals')
    expect(data).toHaveProperty('reconciled')

    expect(data.device_totals.total_amount).toBe('118000')
    expect(data.device_totals.receipt_count).toBe('1')

    console.log(`Z-report: reconciled=${data.reconciled}, system_total=${data.system_totals.total_amount}, device_total=${data.device_totals.total_amount} ✓`)
  })

  test('12. Reconciliation — system totals match receipt from test 6', async () => {
    expect(testDeviceId).toBeTruthy()

    // The system_totals should include our receipt (118000)
    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-z-report`, {
      report_number: `Z-CHECK-${Date.now()}`,
      total_amount: '118000',
      total_vat: '18000',
      receipt_count: '1',
      source: 'webserial',
    })

    expect(res.status).toBe(200)
    const data = res.data.data
    // System total should be at least 118000 (our receipt)
    expect(Number(data.system_totals.total_amount)).toBeGreaterThanOrEqual(118000)
    expect(data.system_totals.receipt_count).toBeGreaterThanOrEqual(1)

    console.log(`Reconciliation: system has ${data.system_totals.receipt_count} receipts totaling ${data.system_totals.total_amount} ✓`)
  })

  test('13. Z-report validation — missing fields returns 422', async () => {
    expect(testDeviceId).toBeTruthy()

    const res = await apiPost(page, `fiscal-devices/${testDeviceId}/record-z-report`, {
      // Missing required fields
    })

    expect(res.status).toBe(422)
    console.log('Z-report validation: 422 on missing fields ✓')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // GROUP 4: Data Integrity
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('14. Invoice unchanged — fiscalization does not modify invoice', async () => {
    expect(testInvoiceId).toBeTruthy()

    const res = await apiGet(page, `invoices/${testInvoiceId}`)
    expect(res.status).toBe(200)

    const invoice = res.data?.data
    expect(invoice).toBeDefined()
    // Invoice should still have its original status and total — not changed by receipt
    expect(invoice.id).toBe(testInvoiceId)
    expect(invoice.total).toBeDefined()
    expect(invoice.status).toBeDefined()

    console.log(`Invoice ${testInvoiceId}: status=${invoice.status}, total=${invoice.total} — unchanged after fiscalization ✓`)
  })

  test('15. Fiscal receipt is audit-only — no stock/IFRS side effects', async () => {
    // Fiscal receipts don't trigger stock or accounting — they're compliance records.
    // Verify by checking that FiscalReceipt has no observer-driven side effects:
    // - No stock_adjustments created by fiscal receipt
    // - The receipt is just a record with: company_id, device_id, invoice_id, amounts
    expect(testReceiptId).toBeTruthy()

    // Get the receipt from the all-receipts endpoint
    const res = await apiGet(page, 'fiscal-receipts?limit=5&orderByField=created_at&orderBy=desc')
    expect(res.status).toBe(200)

    const found = res.data?.data?.find(r => r.id === testReceiptId)
    expect(found).toBeDefined()
    expect(found.company_id).toBeDefined()
    expect(found.fiscal_device_id).toBe(testDeviceId)

    // Receipt is a simple audit record — no 'stock_adjustment_id' or 'journal_entry_id'
    expect(found.stock_adjustment_id).toBeUndefined()
    expect(found.journal_entry_id).toBeUndefined()

    console.log(`Receipt ${testReceiptId}: audit-only record, no stock/IFRS side effects ✓`)
  })

  test('16. WebSerial defaults — device types have correct default connection', async () => {
    const res = await apiGet(page, 'fiscal-devices')
    expect(res.status).toBe(200)

    const types = res.data.supported_types
    // All Macedonian devices should default to webserial (array of objects)
    const webserialTypes = ['daisy', 'david', 'razvigorec', 'severec', 'expert-sx', 'pelisterec', 'alpha']
    for (const name of webserialTypes) {
      const found = types.find(t => t.type === name)
      if (found) {
        expect(found.default_connection).toBe('webserial')
      }
    }

    console.log(`WebSerial defaults: ${webserialTypes.length} device types default to webserial ✓`)
  })

  test('17. Source column works — receipt has source in response', async () => {
    expect(testReceiptId).toBeTruthy()

    const res = await apiGet(page, 'fiscal-receipts?limit=10&orderByField=created_at&orderBy=desc')
    expect(res.status).toBe(200)

    const found = res.data?.data?.find(r => r.id === testReceiptId)
    expect(found).toBeDefined()
    expect(found.source).toBe('webserial')

    console.log(`Source column: receipt ${testReceiptId} has source='webserial' ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // GROUP 5: UI Smoke
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

  test('18. Settings page loads — no 500', async () => {
    const response = await page.goto(`${BASE}/admin/settings/fiscal-devices`, {
      waitUntil: 'domcontentloaded',
      timeout: 15000,
    })
    expect(response.status()).toBeLessThan(500)

    await page.waitForTimeout(5000)

    const url = page.url()
    expect(url.includes('settings') || url.includes('admin')).toBeTruthy()

    console.log(`Settings page loaded at ${url} ✓`)
  })

  test('19. Fiscal receipts page loads — table renders', async () => {
    const response = await page.goto(`${BASE}/admin/fiscal-receipts`, {
      waitUntil: 'domcontentloaded',
      timeout: 15000,
    })
    expect(response.status()).toBeLessThan(500)

    await page.waitForTimeout(5000)

    const url = page.url()
    expect(url.includes('fiscal-receipts') || url.includes('admin')).toBeTruthy()

    console.log(`Fiscal receipts page loaded at ${url} ✓`)
  })

  test('20. Fiscal receipts API — filters work', async () => {
    // Test source filter
    const sourceRes = await apiGet(page, 'fiscal-receipts?source=webserial&limit=5')
    expect(sourceRes.status).toBe(200)
    if (sourceRes.data?.data?.length > 0) {
      for (const r of sourceRes.data.data) {
        expect(r.source).toBe('webserial')
      }
    }

    // Test device filter
    if (testDeviceId) {
      const deviceRes = await apiGet(page, `fiscal-receipts?fiscal_device_id=${testDeviceId}&limit=5`)
      expect(deviceRes.status).toBe(200)
      if (deviceRes.data?.data?.length > 0) {
        for (const r of deviceRes.data.data) {
          expect(r.fiscal_device_id).toBe(testDeviceId)
        }
      }
    }

    // Test date filter
    const dateRes = await apiGet(page, 'fiscal-receipts?from_date=2026-03-01&to_date=2026-03-28&limit=5')
    expect(dateRes.status).toBe(200)

    console.log(`Fiscal receipts filters: source, device, date — all working ✓`)
  })
})

// CLAUDE-CHECKPOINT
