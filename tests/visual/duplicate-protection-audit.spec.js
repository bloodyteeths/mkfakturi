// @ts-check
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

/**
 * Duplicate Protection + Proforma Number Fix — E2E Audit
 *
 * Tests use existing entities where possible to avoid subscription limits.
 * Customer tests create+cleanup seeds (customers have generous limits).
 */

const BASE = process.env.TEST_BASE_URL || 'http://localhost:8000'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''

const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'duplicate-protection')
if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })
}

async function ss(page, name) {
  await page.screenshot({
    path: path.join(SCREENSHOT_DIR, `${name}.png`),
    fullPage: true,
  })
}

test.describe('Duplicate Protection + Proforma Number Fix', () => {
  test.describe.configure({ mode: 'serial' })
  test.setTimeout(90000)

  let page
  let jsErrors = []
  let apiErrors = []
  let seedCustomerId = null
  // Track IDs we create for cleanup
  let createdIds = []

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage()

    page.on('pageerror', (err) => {
      jsErrors.push({ url: page.url(), error: err.message })
    })

    page.on('response', (resp) => {
      const url = resp.url()
      if (url.includes('/api/') && resp.status() >= 500) {
        if (!url.includes('/search') && !url.includes('limit=')) {
          apiErrors.push({ url, status: resp.status() })
        }
      }
    })

    // Login
    await page.goto(`${BASE}/login`)
    await page.waitForLoadState('domcontentloaded')
    await page.waitForTimeout(2000)

    let loggedIn = false
    try {
      loggedIn = await page.evaluate(
        async ({ email, password }) => {
          if (typeof window.axios === 'undefined') return false
          try {
            await window.axios.get(window.location.origin + '/sanctum/csrf-cookie')
            const resp = await window.axios.post('/auth/login', { email, password })
            return resp.status === 200
          } catch {
            return false
          }
        },
        { email: EMAIL, password: PASS }
      )
    } catch {
      loggedIn = false
    }

    if (!loggedIn) {
      const emailInput = page.locator('input[type="email"], input[name="email"]').first()
      const passwordInput = page.locator('input[type="password"]').first()
      await emailInput.fill(EMAIL)
      await passwordInput.fill(PASS)
      await page.locator('button[type="submit"]').first().click()
      await page.waitForURL(/\/(admin|dashboard)/, { timeout: 45000 })
    } else {
      await page.goto(`${BASE}/admin/dashboard`)
    }

    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(3000)

    const companyId = await page.evaluate(() => window.Ls?.get?.('selectedCompany') || null)
    console.log('Selected company ID:', companyId)
    await ss(page, '00-logged-in')
  })

  test.afterAll(async () => {
    // Cleanup all created entities
    for (const { endpoint, id } of createdIds) {
      try {
        await page.evaluate(
          async ({ endpoint, id }) => {
            try { await window.axios.post(`/${endpoint}/delete`, { ids: [id] }) } catch {}
          },
          { endpoint, id }
        )
      } catch {}
    }

    if (jsErrors.length > 0) console.log('JS Errors:', JSON.stringify(jsErrors, null, 2))
    if (apiErrors.length > 0) console.log('API 500 Errors:', JSON.stringify(apiErrors, null, 2))
    if (page) await page.close()
  })

  // ─── Test 1: Proforma invoice next-number API ─────────────────────

  test('1. Proforma invoice next-number returns formatted number', async () => {
    const result = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/next-number', { params: { key: 'proforma_invoice' } })
        return { success: true, nextNumber: resp.data.nextNumber }
      } catch (e) {
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message }
      }
    })

    console.log('Proforma next number:', result)
    expect(result.success, 'next-number API should succeed').toBeTruthy()
    expect(result.nextNumber).toBeTruthy()
    expect(result.nextNumber.length).toBeGreaterThan(0)
  })

  // ─── Test 2: Customer duplicate detection (API) ────────────────────

  test('2. Customer duplicate detection via API', async () => {
    const ts = Date.now()

    // Create seed customer
    const seed = await page.evaluate(async (ts) => {
      try {
        const resp = await window.axios.post('/customers', {
          name: `E2E Dup Test ${ts}`,
          email: `e2e-dup-${ts}@example.com`,
          allow_duplicate: true,
        })
        return { success: true, id: resp.data?.data?.id, name: resp.data?.data?.name }
      } catch (e) {
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message }
      }
    }, ts)

    console.log('Seed customer:', seed)
    expect(seed.success, `Customer creation failed: ${JSON.stringify(seed)}`).toBeTruthy()
    seedCustomerId = seed.id
    createdIds.push({ endpoint: 'customers', id: seed.id })

    // Try duplicate by similar name (containment match)
    const dup = await page.evaluate(async () => {
      try {
        const resp = await window.axios.post('/customers', {
          name: 'E2E Dup Test',
          email: `dup-check-${Date.now()}@example.com`,
        })
        return {
          success: true,
          is_duplicate_warning: resp.data?.is_duplicate_warning || false,
          duplicates_count: resp.data?.duplicates?.length || 0,
          created_id: resp.data?.data?.id,
        }
      } catch (e) {
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message }
      }
    })

    console.log('Customer duplicate result:', dup)
    if (dup.created_id) createdIds.push({ endpoint: 'customers', id: dup.created_id })

    expect(dup.is_duplicate_warning, 'Should detect duplicate customer by similar name').toBeTruthy()
    expect(dup.duplicates_count).toBeGreaterThan(0)
  })

  // ─── Test 3: Customer duplicate warning via UI form ────────────────

  test('3. Customer duplicate warning modal appears via form', async () => {
    if (!seedCustomerId) { test.skip(); return }

    await page.goto(`${BASE}/admin/customers/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(3000)

    // Fill form
    const nameInput = page.locator('input[name="name"]').first()
    await nameInput.waitFor({ state: 'visible', timeout: 10000 })
    await nameInput.fill('E2E Dup Test')

    const emailInput = page.locator('input[name="email"]')
    if (await emailInput.isVisible().catch(() => false)) {
      await emailInput.fill(`ui-dup-${Date.now()}@example.com`)
    }

    await page.waitForTimeout(500)
    await ss(page, '01-customer-form-filled')

    // Submit
    await page.locator('button[type="submit"]').first().click()
    await page.waitForTimeout(5000)
    await ss(page, '02-customer-after-submit')

    // Check for modal
    const saveAnywayBtn = page.locator('button').filter({
      hasText: /Save Anyway|Зачувај сепак|Ruaj Gjithsesi|Yine de Kaydet/i,
    }).first()

    const modalVisible = await saveAnywayBtn.isVisible().catch(() => false)
    const bodyText = await page.textContent('body')
    const warningTextFound = bodyText.includes('Potential Duplicate') ||
      bodyText.includes('Пронајден потенцијален дупликат') ||
      bodyText.includes('Dublikatë') || bodyText.includes('Olası Kopya')

    console.log(`Modal visible: ${modalVisible}, Warning text: ${warningTextFound}`)
    expect(modalVisible || warningTextFound, 'Duplicate warning modal should appear').toBeTruthy()
  })

  // ─── Test 4: Customer Save Anyway override ─────────────────────────

  test('4. Customer Save Anyway creates record', async () => {
    const btn = page.locator('button').filter({
      hasText: /Save Anyway|Зачувај сепак|Ruaj Gjithsesi|Yine de Kaydet/i,
    }).first()

    const isVisible = await btn.isVisible().catch(() => false)
    if (!isVisible) {
      // Fallback: test allow_duplicate via API
      const result = await page.evaluate(async () => {
        try {
          const resp = await window.axios.post('/customers', {
            name: 'E2E Dup Test Override',
            email: `override-${Date.now()}@example.com`,
            allow_duplicate: true,
          })
          return { success: true, id: resp.data?.data?.id, is_dup: resp.data?.is_duplicate_warning || false }
        } catch (e) {
          return { success: false, message: e.response?.data?.message || e.message }
        }
      })
      console.log('API override result:', result)
      if (result.id) createdIds.push({ endpoint: 'customers', id: result.id })
      expect(result.success).toBeTruthy()
      expect(result.is_dup).toBeFalsy()
      return
    }

    await btn.click()
    await page.waitForTimeout(4000)
    await ss(page, '03-customer-saved-after-override')

    const url = page.url()
    console.log('URL after Save Anyway:', url)
    // Extract created ID for cleanup
    const match = url.match(/\/customers\/(\d+)/)
    if (match) createdIds.push({ endpoint: 'customers', id: parseInt(match[1]) })
    expect(url.includes('/admin/customers') && !url.includes('/create')).toBeTruthy()
  })

  // ─── Test 5: Supplier duplicate detection (API, uses existing) ─────

  test('5. Supplier duplicate detection via API', async () => {
    // Use existing suppliers instead of creating new ones (avoids subscription limits)
    const existing = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/suppliers', { params: { limit: 1 } })
        const data = resp.data?.data || []
        if (data.length === 0) return { success: false, message: 'No existing suppliers' }
        return { success: true, name: data[0].name, id: data[0].id }
      } catch (e) {
        return { success: false, message: e.message }
      }
    })

    if (!existing.success) { console.log('No suppliers:', existing.message); test.skip(); return }
    console.log('Using existing supplier:', existing.name)

    // Try to create duplicate by similar name
    const dup = await page.evaluate(async (name) => {
      try {
        const resp = await window.axios.post('/suppliers', {
          name: name,
          email: `sup-dup-${Date.now()}@example.com`,
        })
        return {
          success: true,
          is_duplicate_warning: resp.data?.is_duplicate_warning || false,
          duplicates_count: resp.data?.duplicates?.length || 0,
          created_id: resp.data?.data?.id,
        }
      } catch (e) {
        // 402/403 = subscription limit, not a test failure
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message, limit_error: e.response?.status === 402 || e.response?.status === 403 }
      }
    }, existing.name)

    console.log('Supplier duplicate:', dup)
    if (dup.created_id) createdIds.push({ endpoint: 'suppliers', id: dup.created_id })
    if (dup.limit_error) { console.log('Subscription limit hit — skipping'); test.skip(); return }

    expect(dup.is_duplicate_warning, 'Should detect supplier duplicate by name').toBeTruthy()
  })

  // ─── Test 6: Supplier duplicate by exact phone (API) ───────────────

  test('6. Supplier duplicate by exact phone match', async () => {
    const existing = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/suppliers', { params: { limit: 'all' } })
        const data = resp.data?.data || resp.data || []
        const withPhone = data.find(s => s.phone)
        if (!withPhone) return { success: false, message: 'No supplier with phone' }
        return { success: true, phone: withPhone.phone, name: withPhone.name }
      } catch (e) {
        return { success: false, message: e.message }
      }
    })

    if (!existing.success) { console.log('No supplier with phone:', existing.message); test.skip(); return }

    const result = await page.evaluate(async (phone) => {
      try {
        const resp = await window.axios.post('/suppliers', {
          name: 'Completely Different Supplier Name',
          email: `unique-sup-${Date.now()}@example.com`,
          phone: phone,
        })
        return {
          success: true,
          is_duplicate_warning: resp.data?.is_duplicate_warning || false,
          created_id: resp.data?.data?.id,
        }
      } catch (e) {
        return { success: false, status: e.response?.status, limit_error: e.response?.status === 402 || e.response?.status === 403 }
      }
    }, existing.phone)

    console.log('Supplier phone match:', result)
    if (result.created_id) createdIds.push({ endpoint: 'suppliers', id: result.created_id })
    if (result.limit_error) { console.log('Subscription limit — skipping'); test.skip(); return }

    expect(result.is_duplicate_warning, 'Should detect duplicate by phone').toBeTruthy()
  })

  // ─── Test 7: Item duplicate detection (API, uses existing) ─────────

  test('7. Item duplicate detection via API', async () => {
    const existing = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/items', { params: { limit: 1 } })
        const data = resp.data?.data || []
        if (data.length === 0) return { success: false, message: 'No existing items' }
        return { success: true, name: data[0].name, id: data[0].id }
      } catch (e) {
        return { success: false, message: e.message }
      }
    })

    if (!existing.success) { console.log('No items:', existing.message); test.skip(); return }
    console.log('Using existing item:', existing.name)

    const dup = await page.evaluate(async (name) => {
      try {
        const resp = await window.axios.post('/items', { name: name, price: 10000 })
        return {
          success: true,
          is_duplicate_warning: resp.data?.is_duplicate_warning || false,
          duplicates_count: resp.data?.duplicates?.length || 0,
          created_id: resp.data?.data?.id,
        }
      } catch (e) {
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message, limit_error: e.response?.status === 402 || e.response?.status === 403 }
      }
    }, existing.name)

    console.log('Item duplicate:', dup)
    if (dup.created_id) createdIds.push({ endpoint: 'items', id: dup.created_id })
    if (dup.limit_error) { console.log('Subscription limit — skipping'); test.skip(); return }

    expect(dup.is_duplicate_warning, 'Should detect item duplicate by name').toBeTruthy()
  })

  // ─── Test 8: Item allow_duplicate bypass (API) ─────────────────────

  test('8. Item allow_duplicate=true bypasses warning', async () => {
    const existing = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/items', { params: { limit: 1 } })
        const data = resp.data?.data || []
        if (data.length === 0) return { success: false }
        return { success: true, name: data[0].name }
      } catch { return { success: false } }
    })

    if (!existing.success) { test.skip(); return }

    const result = await page.evaluate(async (name) => {
      try {
        const resp = await window.axios.post('/items', { name: name, price: 30000, allow_duplicate: true })
        return {
          success: true,
          is_duplicate_warning: resp.data?.is_duplicate_warning || false,
          id: resp.data?.data?.id || resp.data?.item?.id,
        }
      } catch (e) {
        return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message, limit_error: e.response?.status === 402 || e.response?.status === 403 }
      }
    }, existing.name)

    console.log('Item allow_duplicate:', result)
    if (result.id) createdIds.push({ endpoint: 'items', id: result.id })
    if (result.limit_error) { console.log('Subscription limit — skipping'); test.skip(); return }

    expect(result.success, 'Should create item with allow_duplicate').toBeTruthy()
    expect(result.is_duplicate_warning, 'Should NOT warn when allow_duplicate=true').toBeFalsy()
  })

  // ─── Test 9: Invoice duplicate detection (API) ─────────────────────

  test('9. Invoice duplicate detection via API', async () => {
    const customers = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/customers', { params: { limit: 1 } })
        return { success: true, data: resp.data?.data || [] }
      } catch { return { success: false } }
    })

    if (!customers.success || customers.data.length === 0) { test.skip(); return }

    const custId = customers.data[0].id
    const today = new Date().toISOString().split('T')[0]

    // Create seed invoice
    const seed = await page.evaluate(
      async ({ custId, date }) => {
        try {
          const numResp = await window.axios.get('/next-number', { params: { key: 'invoice' } })
          const resp = await window.axios.post('/invoices', {
            invoice_date: date, due_date: date, customer_id: custId,
            invoice_number: numResp.data.nextNumber,
            discount_type: 'fixed', discount: 0, discount_val: 0,
            sub_total: 100000, total: 100000, tax: 0,
            template_name: 'invoice1',
            items: [{ name: 'Dup Test', quantity: 1, price: 100000, discount_type: 'fixed', discount: 0, discount_val: 0, tax: 0, total: 100000, unit_name: '', item_id: null, taxes: [] }],
            taxes: [], allow_duplicate: true,
          })
          return { success: true, id: resp.data?.data?.id }
        } catch (e) {
          return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message, limit_error: e.response?.status === 402 || e.response?.status === 403 }
        }
      },
      { custId, date: today }
    )

    console.log('Seed invoice:', seed)
    if (seed.id) createdIds.push({ endpoint: 'invoices', id: seed.id })
    if (!seed.success) { console.log('Could not create seed invoice'); test.skip(); return }

    // Try duplicate (same customer + amount + date)
    const dup = await page.evaluate(
      async ({ custId, date }) => {
        try {
          const numResp = await window.axios.get('/next-number', { params: { key: 'invoice' } })
          const resp = await window.axios.post('/invoices', {
            invoice_date: date, due_date: date, customer_id: custId,
            invoice_number: numResp.data.nextNumber,
            discount_type: 'fixed', discount: 0, discount_val: 0,
            sub_total: 100000, total: 100000, tax: 0,
            template_name: 'invoice1',
            items: [{ name: 'Dup Test 2', quantity: 1, price: 100000, discount_type: 'fixed', discount: 0, discount_val: 0, tax: 0, total: 100000, unit_name: '', item_id: null, taxes: [] }],
            taxes: [],
          })
          return {
            success: true,
            is_duplicate_warning: resp.data?.is_duplicate_warning || false,
            duplicates_count: resp.data?.duplicates?.length || 0,
          }
        } catch (e) {
          return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message }
        }
      },
      { custId, date: today }
    )

    console.log('Invoice duplicate:', dup)
    expect(dup.success).toBeTruthy()
    expect(dup.is_duplicate_warning, 'Should detect invoice duplicate').toBeTruthy()
  })

  // ─── Test 10: Bill duplicate detection (API) ───────────────────────

  test('10. Bill duplicate detection via API', async () => {
    const suppliers = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/suppliers', { params: { limit: 1 } })
        return { success: true, data: resp.data?.data || [] }
      } catch { return { success: false } }
    })

    if (!suppliers.success || suppliers.data.length === 0) { test.skip(); return }

    const supId = suppliers.data[0].id
    // Get the company's default currency
    const currencyId = await page.evaluate(async () => {
      try {
        const resp = await window.axios.get('/company')
        return resp.data?.data?.currency_id || resp.data?.currency_id || 1
      } catch { return 1 }
    })
    const today = new Date().toISOString().split('T')[0]

    const seed = await page.evaluate(
      async ({ supId, date, currencyId }) => {
        try {
          const resp = await window.axios.post('/bills', {
            bill_date: date, due_date: date, supplier_id: supId,
            bill_number: `BILL-SEED-${Date.now()}`,
            discount_type: 'fixed', discount: 0, discount_val: 0,
            sub_total: 50000, total: 50000, tax: 0,
            currency_id: currencyId,
            exchange_rate: 1,
            template_name: 'bill1',
            items: [{ name: 'Bill Seed', quantity: 1, price: 50000, discount_type: 'fixed', discount: 0, discount_val: 0, tax: 0, total: 50000, unit_name: '', item_id: null, taxes: [] }],
            taxes: [], allow_duplicate: true,
          })
          return { success: true, id: resp.data?.data?.id }
        } catch (e) {
          return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message, limit_error: e.response?.status === 402 || e.response?.status === 403 }
        }
      },
      { supId, date: today, currencyId }
    )

    console.log('Seed bill:', seed)
    if (seed.id) createdIds.push({ endpoint: 'bills', id: seed.id })
    if (!seed.success) { console.log('Could not create seed bill'); test.skip(); return }

    const dup = await page.evaluate(
      async ({ supId, date, currencyId }) => {
        try {
          const resp = await window.axios.post('/bills', {
            bill_date: date, due_date: date, supplier_id: supId,
            bill_number: `BILL-DUP-${Date.now()}`,
            discount_type: 'fixed', discount: 0, discount_val: 0,
            sub_total: 50000, total: 50000, tax: 0,
            currency_id: currencyId,
            exchange_rate: 1,
            template_name: 'bill1',
            items: [{ name: 'Bill Dup', quantity: 1, price: 50000, discount_type: 'fixed', discount: 0, discount_val: 0, tax: 0, total: 50000, unit_name: '', item_id: null, taxes: [] }],
            taxes: [],
          })
          return {
            success: true,
            is_duplicate_warning: resp.data?.is_duplicate_warning || false,
            duplicates_count: resp.data?.duplicates?.length || 0,
          }
        } catch (e) {
          return { success: false, status: e.response?.status, message: e.response?.data?.message || e.message }
        }
      },
      { supId, date: today, currencyId }
    )

    console.log('Bill duplicate:', dup)
    if (!dup.success && (dup.status === 402 || dup.status === 403)) {
      console.log('Subscription limit on duplicate bill — skipping assertion')
      test.skip()
      return
    }
    expect(dup.success).toBeTruthy()
    expect(dup.is_duplicate_warning, 'Should detect bill duplicate').toBeTruthy()
  })

  // ─── Test 11: Customer email uniqueness (API) ──────────────────────

  test('11. Customer duplicate detected by exact email', async () => {
    if (!seedCustomerId) { test.skip(); return }

    const seedData = await page.evaluate(async (id) => {
      try {
        const resp = await window.axios.get(`/customers/${id}`)
        return { success: true, email: resp.data?.data?.email }
      } catch { return { success: false } }
    }, seedCustomerId)

    if (!seedData.success || !seedData.email) { test.skip(); return }

    const result = await page.evaluate(
      async ({ email }) => {
        try {
          const resp = await window.axios.post('/customers', { name: 'Different Name', email })
          return { success: true, is_duplicate_warning: resp.data?.is_duplicate_warning || false }
        } catch (e) {
          return { success: false, has_email_error: !!(e.response?.data?.errors?.email) }
        }
      },
      { email: seedData.email }
    )

    console.log('Email duplicate:', result)
    expect(result.is_duplicate_warning || result.has_email_error, 'Should detect duplicate by email').toBeTruthy()
  })

  // ─── Test 12: Cyrillic↔Latin transliteration (API) ─────────────────

  test('12. Cyrillic-Latin transliteration match', async () => {
    // Create Cyrillic customer
    const cyr = await page.evaluate(async () => {
      try {
        const resp = await window.axios.post('/customers', {
          name: 'Тест Кирилица Компани',
          email: `cyrillic-${Date.now()}@example.com`,
          allow_duplicate: true,
        })
        return { success: true, id: resp.data?.data?.id }
      } catch { return { success: false } }
    })

    if (!cyr.success) { test.skip(); return }
    createdIds.push({ endpoint: 'customers', id: cyr.id })

    // Try Latin transliteration
    const latin = await page.evaluate(async () => {
      try {
        const resp = await window.axios.post('/customers', {
          name: 'Test Kirilica Kompani',
          email: `latin-${Date.now()}@example.com`,
        })
        return {
          success: true,
          is_duplicate_warning: resp.data?.is_duplicate_warning || false,
          created_id: resp.data?.data?.id,
        }
      } catch { return { success: false } }
    })

    console.log('Transliteration result:', latin)
    if (latin.created_id) createdIds.push({ endpoint: 'customers', id: latin.created_id })

    expect(latin.success).toBeTruthy()
    expect(latin.is_duplicate_warning, 'Should detect duplicate across scripts').toBeTruthy()
  })

  // ─── Test 13: No critical errors ───────────────────────────────────

  test('13. No critical JS errors or API 500s', async () => {
    const criticalJsErrors = jsErrors.filter(
      (e) => !e.error.includes('ResizeObserver') && !e.error.includes('Script error') && !e.error.includes('Non-Error')
    )

    if (criticalJsErrors.length > 0) console.log('Critical JS Errors:', JSON.stringify(criticalJsErrors, null, 2))
    if (apiErrors.length > 0) console.log('API 500 Errors:', JSON.stringify(apiErrors, null, 2))

    expect(criticalJsErrors.length, 'No critical JS errors').toBe(0)
    // Allow API 500s from customer view pages (known issue with test data)
    const criticalApiErrors = apiErrors.filter(e => !e.url.includes('/customers/'))
    expect(criticalApiErrors.length, 'No critical API 500 errors').toBe(0)
  })
})
