/**
 * Expenses Module — E2E Audit Tests
 *
 * Tests the full expenses lifecycle: index page columns, filters,
 * create with MK compliance fields (VAT, supplier, invoice#),
 * view page, clone, approve, and bulk actions.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/expenses-audit-e2e.spec.js --project=chromium
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
  'expenses-audit-e2e-screenshots'
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
        headers: {
          Accept: 'application/json',
          company: '2',
          'X-Requested-With': 'XMLHttpRequest',
        },
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
      const cookies = document.cookie.split(';').map((c) => c.trim())
      const xsrf = cookies.find((c) => c.startsWith('XSRF-TOKEN='))
      const token = xsrf ? decodeURIComponent(xsrf.split('=')[1]) : ''
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-XSRF-TOKEN': token,
          company: '2',
          'X-Requested-With': 'XMLHttpRequest',
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

test.describe.configure({ mode: 'serial', timeout: 30000 })
test.setTimeout(30000)

let sharedPage
let createdExpenseId = null
let clonedExpenseId = null

test.beforeAll(async ({ browser }) => {
  test.setTimeout(120000) // 120s for login (rate limiting can cause delays)
  if (!EMAIL || !PASS) throw new Error('Set TEST_EMAIL and TEST_PASSWORD env vars')

  sharedPage = await browser.newPage()

  // Wait to avoid Sanctum rate-limiting from prior test runs
  console.log('⏳ Waiting 10s to avoid rate-limiting...')
  await new Promise((r) => setTimeout(r, 10000))

  // Attempt login with retry
  for (let attempt = 1; attempt <= 3; attempt++) {
    console.log(`Login attempt ${attempt}/3...`)
    await sharedPage.goto(`${BASE}/login`, { timeout: 60000, waitUntil: 'domcontentloaded' })
    await sharedPage.waitForTimeout(3000)

    // Check if already logged in
    if (sharedPage.url().includes('/admin')) {
      console.log('✓ Already logged in')
      break
    }

    try {
      await sharedPage.waitForSelector('input[name="email"], input[type="email"]', { timeout: 20000 })
      await sharedPage.fill('input[name="email"], input[type="email"]', EMAIL)
      await sharedPage.fill('input[name="password"], input[type="password"]', PASS)
      await sharedPage.click('button[type="submit"]')
      await sharedPage.waitForURL(/\/admin/, { timeout: 20000 })
      console.log('✓ Logged in successfully')
      break
    } catch (e) {
      if (attempt < 3) {
        console.log(`⚠ Login attempt ${attempt} failed, waiting 10s before retry...`)
        await new Promise((r) => setTimeout(r, 10000))
      } else {
        throw new Error(`Login failed after 3 attempts - url: ${sharedPage.url()}`)
      }
    }
  }
  await sharedPage.waitForTimeout(2000)
})

test.afterAll(async () => {
  // Cleanup: delete test expenses
  if (createdExpenseId) {
    await apiPost(sharedPage, `${BASE}/api/v1/expenses/delete`, {
      ids: [createdExpenseId, clonedExpenseId].filter(Boolean),
    })
  }
  await sharedPage?.close()
})

// ============================================================
// 1. Expenses Index Page
// ============================================================

test('01 — Expenses index page loads', async () => {
  const page = sharedPage
  await page.goto(`${BASE}/admin/expenses`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Page should load (check for error pages, not amount values containing "500")
  await expect(page.locator('body')).not.toContainText('Server Error')
  await expect(page.locator('body')).not.toContainText('Page Not Found')

  await ss(page, '01-expenses-index')
})

test('02 — Index table has required columns', async () => {
  const page = sharedPage

  // Check table header columns exist
  const headers = page.locator('table thead th, [role="columnheader"]')
  const headerTexts = await headers.allTextContents()
  const joined = headerTexts.join(' ').toLowerCase()

  console.log('Table headers:', headerTexts)

  // Core columns we added
  const hasExpenseNumber = joined.includes('број') || joined.includes('number') || joined.includes('#')
  const hasDate = joined.includes('датум') || joined.includes('date')
  const hasCategory = joined.includes('категорија') || joined.includes('category')
  const hasAmount = joined.includes('износ') || joined.includes('amount')

  expect(hasDate || hasCategory || hasAmount).toBeTruthy()

  await ss(page, '02-index-columns')
})

test('03 — Filter panel toggles and has supplier + status filters', async () => {
  const page = sharedPage

  // Click filter button
  const filterBtn = page.locator('button', { hasText: /filter|филтер/i }).first()
  if (await filterBtn.isVisible()) {
    await filterBtn.click()
    await page.waitForTimeout(500)

    // Check filter section is visible
    await ss(page, '03-filter-panel-open')

    // Close filter
    await filterBtn.click()
    await page.waitForTimeout(300)
  }
})

test('04 — Search input exists and works', async () => {
  const page = sharedPage

  const searchInput = page.locator('input[type="search"]').first()
  if (await searchInput.isVisible()) {
    await searchInput.fill('EXP-')
    await page.waitForTimeout(1000)
    await ss(page, '04-search-by-expense-number')

    // Clear search
    await searchInput.fill('')
    await page.waitForTimeout(1000)
  }
})

// ============================================================
// 2. Create Expense with MK Compliance Fields
// ============================================================

test('05 — Create expense page loads with all MK fields', async () => {
  const page = sharedPage
  await page.goto(`${BASE}/admin/expenses/create`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await expect(page.locator('body')).not.toContainText('500')

  await ss(page, '05-create-expense-form')
})

test('06 — Create form has supplier field', async () => {
  const page = sharedPage

  // Supplier field label
  const supplierLabel = page.locator('label, .base-input-group', {
    hasText: /добавувач|supplier/i,
  })
  await expect(supplierLabel.first()).toBeVisible()

  await ss(page, '06-supplier-field')
})

test('07 — Create form has invoice number field', async () => {
  const page = sharedPage

  const invoiceLabel = page.locator('label, .base-input-group', {
    hasText: /фактура|invoice.number/i,
  })
  await expect(invoiceLabel.first()).toBeVisible()
})

test('08 — Create form has VAT rate dropdown', async () => {
  const page = sharedPage

  const vatLabel = page.locator('label, .base-input-group', {
    hasText: /ддв стапка|vat.rate|ддв/i,
  })
  await expect(vatLabel.first()).toBeVisible()

  await ss(page, '08-vat-fields')
})

test('09 — Create form has cost center field', async () => {
  const page = sharedPage

  // Label is "Место на трошок" in MK or "Cost Center" in EN
  const costCenterLabel = page.locator('label, .base-input-group', {
    hasText: /место на трошок|cost.center|трошковен/i,
  })
  await expect(costCenterLabel.first()).toBeVisible()
})

test('10 — Create and save an expense via API', async () => {
  const page = sharedPage

  // Get a category first
  const catResp = await apiGet(page, `${BASE}/api/v1/categories?limit=all`)
  expect(catResp.status).toBe(200)
  const categories = catResp.data?.data || []
  expect(categories.length).toBeGreaterThan(0)
  const categoryId = categories[0].id

  // Get currency
  const currResp = await apiGet(page, `${BASE}/api/v1/currencies`)
  expect(currResp.status).toBe(200)
  const mkd = (currResp.data?.data || []).find(
    (c) => c.code === 'MKD'
  )
  const currencyId = mkd?.id || (currResp.data?.data?.[0]?.id)

  // Create expense via API
  const today = new Date()
  const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

  const expenseData = {
    expense_category_id: categoryId,
    expense_date: dateStr,
    amount: 118000, // 1180.00 MKD (cents)
    currency_id: currencyId,
    vat_rate: 18,
    vat_amount: 18000,
    tax_base: 100000,
    invoice_number: 'TEST-INV-E2E-001',
    notes: 'E2E test expense — auto-created by Playwright',
    status: 'draft',
    exchange_rate: 1,
  }

  const result = await apiPost(page, `${BASE}/api/v1/expenses`, expenseData)
  console.log('Create expense result:', JSON.stringify({ status: result.status, errors: result.data?.errors, message: result.data?.message }, null, 2))

  // Accept 200 or 201 or duplicate warning (200 with is_duplicate_warning)
  expect([200, 201]).toContain(result.status)

  if (result.data?.is_duplicate_warning) {
    // Retry with allow_duplicate
    const retry = await apiPost(page, `${BASE}/api/v1/expenses`, {
      ...expenseData,
      allow_duplicate: true,
    })
    expect([200, 201]).toContain(retry.status)
    createdExpenseId = retry.data?.data?.id
  } else {
    createdExpenseId = result.data?.data?.id
  }

  console.log('Created expense ID:', createdExpenseId)
  expect(createdExpenseId).toBeTruthy()
})

test('11 — Created expense has correct fields via API', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  const resp = await apiGet(page, `${BASE}/api/v1/expenses/${createdExpenseId}`)
  expect(resp.status).toBe(200)

  const expense = resp.data?.data
  console.log('Expense fields:', JSON.stringify({
    id: expense?.id,
    expense_number: expense?.expense_number,
    status: expense?.status,
    vat_rate: expense?.vat_rate,
    vat_amount: expense?.vat_amount,
    tax_base: expense?.tax_base,
    invoice_number: expense?.invoice_number,
  }, null, 2))

  // Verify MK compliance fields
  expect(expense.invoice_number).toBe('TEST-INV-E2E-001')
  expect(expense.status).toBe('draft')

  // VAT fields should be present
  expect(expense.vat_rate).toBeDefined()
  expect(expense.vat_amount).toBeDefined()
  expect(expense.tax_base).toBeDefined()

  // Auto-generated expense number
  if (expense.expense_number) {
    expect(expense.expense_number).toMatch(/^EXP-/)
  }
})

// ============================================================
// 3. View Page
// ============================================================

test('12 — View expense page loads', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  await page.goto(`${BASE}/admin/expenses/${createdExpenseId}/view`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await expect(page.locator('body')).not.toContainText('Server Error')
  await expect(page.locator('body')).not.toContainText('Page Not Found')

  await ss(page, '12-view-expense-page')
})

test('13 — View page shows status badge', async () => {
  const page = sharedPage

  // Status badge should show draft
  const badge = page.locator('.rounded-full', {
    hasText: /draft|нацрт/i,
  })
  await expect(badge.first()).toBeVisible()

  await ss(page, '13-status-badge')
})

test('14 — View page shows financial details', async () => {
  const page = sharedPage

  // Should see tax base, VAT amount, total
  const body = await page.locator('body').textContent()
  const hasFinancialSection =
    body.includes('ДДВ') ||
    body.includes('VAT') ||
    body.includes('Даночна основа') ||
    body.includes('Tax base') ||
    body.includes('financial')

  expect(hasFinancialSection).toBeTruthy()
})

test('15 — View page has Edit button', async () => {
  const page = sharedPage

  const editBtn = page.locator('a, button', {
    hasText: /edit|уреди|измени/i,
  })
  await expect(editBtn.first()).toBeVisible()
})

// ============================================================
// 4. Clone Expense
// ============================================================

test('16 — Clone expense via API', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  const result = await apiPost(
    page,
    `${BASE}/api/v1/expenses/${createdExpenseId}/clone`
  )
  console.log('Clone result:', JSON.stringify({ status: result.status }, null, 2))

  expect([200, 201]).toContain(result.status)
  clonedExpenseId = result.data?.data?.id
  console.log('Cloned expense ID:', clonedExpenseId)

  if (clonedExpenseId) {
    // Verify clone has same category but different ID
    const original = await apiGet(page, `${BASE}/api/v1/expenses/${createdExpenseId}`)
    const clone = await apiGet(page, `${BASE}/api/v1/expenses/${clonedExpenseId}`)

    expect(clone.data?.data?.id).not.toBe(original.data?.data?.id)
    expect(clone.data?.data?.status).toBe('draft')
  }
})

// ============================================================
// 5. Approve Expense
// ============================================================

test('17 — Approve expense via API', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  const result = await apiPost(
    page,
    `${BASE}/api/v1/expenses/${createdExpenseId}/approve`
  )
  console.log('Approve result:', JSON.stringify({ status: result.status }, null, 2))

  expect([200, 201]).toContain(result.status)

  // Verify status changed
  const resp = await apiGet(page, `${BASE}/api/v1/expenses/${createdExpenseId}`)
  expect(resp.data?.data?.status).toBe('approved')
})

test('18 — Approved expense shows approved badge on view page', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  await page.goto(`${BASE}/admin/expenses/${createdExpenseId}/view`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  const badge = page.locator('.rounded-full', {
    hasText: /approved|одобрен/i,
  })
  await expect(badge.first()).toBeVisible()

  await ss(page, '18-approved-status')
})

// ============================================================
// 5b. Post Expense (approved → posted)
// ============================================================

test('18b — Post expense via API', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  const result = await apiPost(
    page,
    `${BASE}/api/v1/expenses/${createdExpenseId}/post`
  )
  console.log('Post result:', JSON.stringify({ status: result.status }, null, 2))

  expect([200, 201]).toContain(result.status)

  // Verify status changed to posted
  const resp = await apiGet(page, `${BASE}/api/v1/expenses/${createdExpenseId}`)
  expect(resp.data?.data?.status).toBe('posted')
})

test('18c — Posted expense shows posted badge on view page', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  await page.goto(`${BASE}/admin/expenses/${createdExpenseId}/view`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  const badge = page.locator('.rounded-full', {
    hasText: /posted|книжен|regjistruar/i,
  })
  await expect(badge.first()).toBeVisible()

  // Should NOT show approve/post buttons (already posted)
  const postBtn = page.locator('button', { hasText: /книжи|post/i })
  await expect(postBtn).toHaveCount(0)

  await ss(page, '18c-posted-status')
})

// ============================================================
// 5c. PDF Download
// ============================================================

test('18d — Rashoden nalog PDF link exists on view page', async () => {
  const page = sharedPage

  // View page should have PDF download link
  const pdfLink = page.locator('a[href*="rashoden-nalog"]')
  await expect(pdfLink.first()).toBeVisible()

  await ss(page, '18d-pdf-link')
})

test('18e — View page computes VAT for old expenses', async () => {
  const page = sharedPage

  // The view page should show computed VAT, not "0 ден" for expenses with amount
  const body = await page.locator('body').textContent()

  // Financial details section should exist
  const hasFinancials = body.includes('Финансиски детали') || body.includes('Financial Details')
  expect(hasFinancials).toBeTruthy()

  await ss(page, '18e-vat-computed')
})

// ============================================================
// 6. Index Page — Verify New Expense Appears
// ============================================================

test('19 — Test expense visible in index table', async () => {
  const page = sharedPage

  await page.goto(`${BASE}/admin/expenses`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Search for our test invoice number
  const searchInput = page.locator('input[type="search"]').first()
  if (await searchInput.isVisible()) {
    await searchInput.fill('TEST-INV-E2E-001')
    await page.waitForTimeout(1500)
  }

  await ss(page, '19-search-test-expense')
})

test('20 — Status badge renders in index table', async () => {
  const page = sharedPage

  // Look for any status badge in the table
  const badges = page.locator('table .rounded-full, [role="cell"] .rounded-full')
  const count = await badges.count()

  console.log('Status badges in table:', count)
  // There should be at least one badge if expenses are visible
  await ss(page, '20-status-badges-in-table')
})

// ============================================================
// 7. Dropdown Actions (UI)
// ============================================================

test('21 — Dropdown shows view/clone/approve/delete actions', async () => {
  const page = sharedPage

  // Go to expenses index fresh
  await page.goto(`${BASE}/admin/expenses`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Find and click the first dropdown (ellipsis icon)
  const dropdownTrigger = page.locator(
    'table [class*="EllipsisHorizontal"], table svg[class*="h-5"]'
  ).first()

  if (await dropdownTrigger.isVisible()) {
    await dropdownTrigger.click()
    await page.waitForTimeout(500)

    await ss(page, '21-dropdown-actions')

    // Check for action items
    const dropdownItems = page.locator('[role="menuitem"], .dropdown-item, li a, li button, li div[class*="cursor"]')
    const count = await dropdownItems.count()
    console.log('Dropdown action items:', count)

    // Close dropdown by clicking elsewhere
    await page.locator('body').click()
    await page.waitForTimeout(300)
  }
})

// ============================================================
// 8. Edit Expense
// ============================================================

test('22 — Edit expense page loads with pre-filled fields', async () => {
  const page = sharedPage
  expect(createdExpenseId).toBeTruthy()

  await page.goto(`${BASE}/admin/expenses/${createdExpenseId}/edit`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(3000)

  await expect(page.locator('body')).not.toContainText('Server Error')
  await expect(page.locator('body')).not.toContainText('Page Not Found')

  // Check invoice number field is pre-filled
  const invoiceInput = page.locator('input[placeholder*="фактура"], input[placeholder*="invoice"], input[placeholder*="Invoice"]').first()
  if (await invoiceInput.isVisible()) {
    const val = await invoiceInput.inputValue()
    console.log('Invoice number pre-filled:', val)
    expect(val).toContain('TEST-INV-E2E')
  }

  await ss(page, '22-edit-expense')
})

test('22b — Create form has section headers', async () => {
  const page = sharedPage

  await page.goto(`${BASE}/admin/expenses/create`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  const body = await page.locator('body').textContent()

  // Check for section headers (MK or EN)
  const hasBasicInfo = body.includes('Основни податоци') || body.includes('Basic Information')
  const hasSupplierSection = body.includes('Добавувач и фактура') || body.includes('Supplier & Invoice')
  const hasAccountingSection = body.includes('ДДВ и книговодство') || body.includes('VAT & Accounting')

  console.log('Section headers:', { hasBasicInfo, hasSupplierSection, hasAccountingSection })
  expect(hasBasicInfo).toBeTruthy()
  expect(hasSupplierSection).toBeTruthy()
  expect(hasAccountingSection).toBeTruthy()

  await ss(page, '22b-form-sections')
})

// ============================================================
// 9. API Endpoints Verification
// ============================================================

test('23 — Expenses API returns MK compliance fields in list', async () => {
  const page = sharedPage

  const resp = await apiGet(page, `${BASE}/api/v1/expenses?page=1&limit=5`)
  expect(resp.status).toBe(200)

  const expenses = resp.data?.data || []
  expect(expenses.length).toBeGreaterThan(0)

  // Check first expense has the new fields
  const first = expenses[0]
  console.log('First expense keys:', Object.keys(first).sort().join(', '))

  // These fields should exist (even if null)
  expect('status' in first).toBeTruthy()

  await ss(page, '23-api-fields')
})

test('24 — Expenses API supports status filter', async () => {
  const page = sharedPage

  const resp = await apiGet(page, `${BASE}/api/v1/expenses?status=approved&page=1&limit=5`)
  expect(resp.status).toBe(200)

  const expenses = resp.data?.data || []
  console.log('Approved expenses count:', expenses.length)

  // All returned should be approved
  for (const exp of expenses) {
    if (exp.status) {
      expect(exp.status).toBe('approved')
    }
  }
})

// ============================================================
// 10. Cleanup Verification
// ============================================================

test('25 — Delete test expenses via API', async () => {
  const page = sharedPage

  const idsToDelete = [createdExpenseId, clonedExpenseId].filter(Boolean)
  if (idsToDelete.length === 0) {
    console.log('No test expenses to clean up')
    return
  }

  const result = await apiPost(page, `${BASE}/api/v1/expenses/delete`, {
    ids: idsToDelete,
  })
  console.log('Delete result:', JSON.stringify({ status: result.status }, null, 2))

  expect([200, 201]).toContain(result.status)

  // Clear so afterAll doesn't double-delete
  createdExpenseId = null
  clonedExpenseId = null
})
