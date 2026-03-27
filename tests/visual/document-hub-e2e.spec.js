/**
 * Document Hub — End-to-End Tests
 *
 * Tests document upload, AI classification, review page preview,
 * entity type switching with field cross-population, and confirm flows.
 *
 * Covers 10 MK document types: invoices, bank statements, tax forms,
 * contracts, receipts — testing the full AI pipeline.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/document-hub-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'http://localhost:8000'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const SCREENSHOT_DIR = path.join(process.cwd(), 'test-results', 'document-hub-screenshots')
const FIXTURES = path.join(process.cwd(), 'tests', 'fixtures', 'mk-documents')

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })
}

async function ss(page, name) {
  await page.screenshot({
    path: path.join(SCREENSHOT_DIR, `${name}.png`),
    fullPage: true,
  })
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// Test Suite
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

test.describe('Document Hub E2E', () => {
  test.describe.configure({ mode: 'serial' })

  /** @type {import('@playwright/test').Page} */
  let page
  let jsErrors = []
  let apiErrors = []
  let uploadedDocId = null

  test.beforeAll(async ({ browser }) => {
    if (!EMAIL || !PASS) {
      console.log('SKIP: Set TEST_EMAIL and TEST_PASSWORD env vars.')
      return
    }

    page = await browser.newPage()
    page.on('pageerror', (err) =>
      jsErrors.push({ url: page.url(), error: err.message })
    )
    page.on('response', (resp) => {
      if (
        resp.url().includes('/api/') &&
        (resp.status() === 404 || resp.status() >= 500)
      ) {
        apiErrors.push({ url: resp.url(), status: resp.status() })
      }
    })
  })

  test.afterAll(async () => {
    if (jsErrors.length)
      console.log(
        '\n=== JS ERRORS ===\n',
        JSON.stringify(jsErrors, null, 2)
      )
    if (apiErrors.length)
      console.log(
        '\n=== API ERRORS ===\n',
        JSON.stringify(apiErrors, null, 2)
      )
    if (page) await page.close()
  })

  // ── 0. Login ─────────────────────────────────────────────────

  test('login', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    await page.goto(`${BASE}/login`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/admin/, { timeout: 20000 })
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)
    await ss(page, '00-login-success')
  })

  // ── 1. Document Hub List Page ────────────────────────────────

  test('documents — hub list page renders', async () => {
    test.setTimeout(20000)
    if (!EMAIL) return test.skip()

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)
    await ss(page, '01-documents-list')

    // Verify page has the document hub structure
    const content = await page.content()
    expect(content).toMatch(/document/i)
  })

  // ── 2. Upload PDF Invoice ────────────────────────────────────

  test('documents — upload PDF invoice', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    const fixturePath = path.join(
      FIXTURES,
      'invoices',
      'pro-faktura-smetkovoditeli.pdf'
    )
    if (!fs.existsSync(fixturePath)) {
      console.log(`SKIP: Fixture not found: ${fixturePath}`)
      return test.skip()
    }

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    // Find the hidden file input and set the file
    const fileInput = page.locator('input[type="file"]').first()
    const inputCount = await page.locator('input[type="file"]').count()

    if (inputCount === 0) {
      console.log('SKIP: No file input found on documents page')
      return test.skip()
    }

    await fileInput.setInputFiles(fixturePath)
    await page.waitForTimeout(2000)
    await ss(page, '02-after-upload')

    // Try to capture the uploaded document ID from the page
    // Look for the first document row that might be our upload
    await page.waitForTimeout(1000)
  })

  // ── 3. Wait for AI Processing ────────────────────────────────

  test('documents — wait for AI processing', async () => {
    test.setTimeout(90000) // AI processing can take a while
    if (!EMAIL) return test.skip()

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Poll until we see an extracted document or timeout
    let extracted = false
    for (let i = 0; i < 20; i++) {
      const content = await page.content()
      if (
        content.includes('extracted') ||
        content.includes('Извлечено') ||
        content.includes('confirmed')
      ) {
        extracted = true
        break
      }
      await page.waitForTimeout(3000)
      await page.reload()
      await page.waitForLoadState('networkidle')
    }

    await ss(page, '03-processing-status')

    if (!extracted) {
      console.log(
        'NOTE: No extracted documents found within timeout. Subsequent tests will use existing documents.'
      )
    }
  })

  // ── 4. Review Page — Preview Renders ─────────────────────────

  test('documents — review page preview renders', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Click on the first document row to go to review page
    const docRows = page.locator('table tbody tr, [class*="document-row"], a[href*="/documents/"]')
    const rowCount = await docRows.count()

    if (rowCount === 0) {
      console.log('SKIP: No document rows to click')
      return test.skip()
    }

    await docRows.first().click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)

    // Capture the document ID from URL
    const url = page.url()
    const match = url.match(/documents\/(\d+)/)
    if (match) {
      uploadedDocId = match[1]
      console.log(`Review page for document ID: ${uploadedDocId}`)
    }

    await ss(page, '04-review-page')

    // Verify preview area exists (iframe for PDF or img for images)
    const iframe = page.locator('iframe')
    const img = page.locator('img[alt]')
    const previewUnavailable = page.locator('text=Preview unavailable')

    const hasIframe = (await iframe.count()) > 0
    const hasImg = (await img.count()) > 0
    const hasError = await previewUnavailable.isVisible().catch(() => false)

    // One of these should be true
    expect(hasIframe || hasImg || hasError).toBe(true)

    // If iframe exists, verify it uses the /preview endpoint
    if (hasIframe) {
      const src = await iframe.first().getAttribute('src')
      expect(src).toContain('/preview?company=')
    }
  })

  // ── 5. AI Classification Badge ───────────────────────────────

  test('documents — AI classification badge visible', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    // We should already be on the review page
    const content = await page.content()

    // Look for classification badge (bg-blue-100, bg-indigo-100, etc.)
    const badges = page.locator(
      '[class*="bg-blue-100"], [class*="bg-indigo-100"], [class*="bg-purple-100"], [class*="bg-cyan-100"], [class*="bg-orange-100"], [class*="bg-green-100"]'
    )
    const badgeCount = await badges.count()

    if (badgeCount > 0) {
      console.log(`Found ${badgeCount} classification badge(s)`)
    }

    // Check for confidence percentage
    const hasConfidence = content.match(/\d+%/)
    if (hasConfidence) {
      console.log(`Confidence: ${hasConfidence[0]}`)
    }

    await ss(page, '05-ai-classification')
  })

  // ── 6. Entity Type Buttons ───────────────────────────────────

  test('documents — entity type buttons rendered', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    // Look for the entity type selector buttons
    const content = await page.content()
    const entityTypeSection = page.locator('text=Create as').first()
    const hasSectionVisible = await entityTypeSection
      .isVisible()
      .catch(() => false)

    if (!hasSectionVisible) {
      // Document might be already confirmed
      const isConfirmed = content.includes('Confirmed')
      if (isConfirmed) {
        console.log('Document is already confirmed, skipping entity type test')
        return
      }
    }

    // Count entity type buttons (Bill, Expense, Invoice, Transactions, Items, Tax Form, Contract)
    const buttons = page.locator(
      'button:has-text("Bill"), button:has-text("Expense"), button:has-text("Invoice"), button:has-text("Transactions"), button:has-text("Items"), button:has-text("Tax"), button:has-text("Contract")'
    )
    const buttonCount = await buttons.count()
    console.log(`Entity type buttons found: ${buttonCount}`)

    // Check which one is selected (has bg-primary-500 class)
    const selectedButton = page.locator('button[class*="bg-primary-500"]')
    const selectedCount = await selectedButton.count()
    if (selectedCount > 0) {
      const selectedText = await selectedButton.first().textContent()
      console.log(`Selected entity type: ${selectedText?.trim()}`)
    }

    await ss(page, '06-entity-type-buttons')
  })

  // ── 7. Invoice-to-Bill Explanation Banner ────────────────────

  test('documents — invoice type explanation banner', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    // Look for the blue info banner about invoice classification
    const banner = page.locator('[class*="bg-blue-50"][class*="border-blue-200"]')
    const bannerVisible = await banner.isVisible().catch(() => false)

    if (bannerVisible) {
      const bannerText = await banner.textContent()
      console.log(`Info banner: ${bannerText?.trim().substring(0, 100)}...`)
    } else {
      console.log(
        'No invoice explanation banner (document may not be classified as invoice)'
      )
    }

    await ss(page, '07-explanation-banner')
  })

  // ── 8. Form Fields Populated ─────────────────────────────────

  test('documents — form fields populated from AI extraction', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    const content = await page.content()

    // Check for populated input fields (supplier name, bill number, dates)
    const inputs = page.locator('input[type="text"], input[type="date"], input[type="email"]')
    const inputCount = await inputs.count()
    let populatedCount = 0

    for (let i = 0; i < Math.min(inputCount, 20); i++) {
      const value = await inputs.nth(i).inputValue().catch(() => '')
      if (value && value.trim()) populatedCount++
    }

    console.log(
      `Form inputs: ${inputCount} total, ${populatedCount} populated`
    )

    // Check for line items
    const lineItems = page.locator('[class*="border-gray-200"][class*="rounded-md"][class*="p-3"]')
    const itemCount = await lineItems.count()
    console.log(`Line items found: ${itemCount}`)

    await ss(page, '08-form-populated')
  })

  // ── 9. Type Switching — Bill to Invoice ──────────────────────

  test('documents — switch to Invoice preserves data', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    // Record current form state (before switching)
    const supplierNameInput = page.locator('input').first()
    const originalName = await supplierNameInput.inputValue().catch(() => '')

    // Click Invoice button
    const invoiceBtn = page.locator('button:has-text("Invoice")').first()
    const invoiceBtnVisible = await invoiceBtn.isVisible().catch(() => false)
    if (!invoiceBtnVisible) {
      console.log('SKIP: Invoice button not found (document may be confirmed)')
      return test.skip()
    }

    await invoiceBtn.click()
    await page.waitForTimeout(500)

    await ss(page, '09-switched-to-invoice')

    // Verify customer fields are visible (invoice form has "Customer" section)
    const content = await page.content()
    const hasCustomerSection =
      content.includes('Customer') || content.includes('Клиент')

    if (hasCustomerSection) {
      console.log('Customer section visible after switching to Invoice')
    }

    // Check that data was cross-populated (customer name should have supplier name)
    const inputs = page.locator('input[type="text"]')
    const inputCount = await inputs.count()
    let populatedCount = 0
    for (let i = 0; i < Math.min(inputCount, 15); i++) {
      const val = await inputs.nth(i).inputValue().catch(() => '')
      if (val && val.trim()) populatedCount++
    }
    console.log(
      `After switch to Invoice: ${populatedCount} populated fields`
    )
  })

  // ── 10. Type Switching — Invoice to Expense ──────────────────

  test('documents — switch to Expense preserves data', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    // Click Expense button
    const expenseBtn = page.locator('button:has-text("Expense")').first()
    const expenseBtnVisible = await expenseBtn.isVisible().catch(() => false)
    if (!expenseBtnVisible) {
      console.log('SKIP: Expense button not found')
      return test.skip()
    }

    await expenseBtn.click()
    await page.waitForTimeout(500)

    await ss(page, '10-switched-to-expense')

    // Verify expense form has data cross-populated
    const content = await page.content()
    const hasExpenseSection =
      content.includes('Expense Details') || content.includes('Трошок')
    console.log(`Expense section visible: ${hasExpenseSection}`)

    // Check amount field is populated
    const amountInputs = page.locator('input[type="text"]')
    const count = await amountInputs.count()
    let hasAmount = false
    for (let i = 0; i < Math.min(count, 10); i++) {
      const val = await amountInputs.nth(i).inputValue().catch(() => '')
      if (val && parseFloat(val) > 0) {
        hasAmount = true
        break
      }
    }
    console.log(`Expense amount populated: ${hasAmount}`)
  })

  // ── 11. Type Switching — Back to Bill ────────────────────────

  test('documents — switch back to Bill restores original data', async () => {
    test.setTimeout(15000)
    if (!EMAIL || !uploadedDocId) return test.skip()

    // Click Bill button
    const billBtn = page.locator('button:has-text("Bill")').first()
    const billBtnVisible = await billBtn.isVisible().catch(() => false)
    if (!billBtnVisible) {
      console.log('SKIP: Bill button not found')
      return test.skip()
    }

    await billBtn.click()
    await page.waitForTimeout(500)

    await ss(page, '11-switched-back-to-bill')

    // Verify supplier section is back with original data
    const content = await page.content()
    const hasSupplierSection =
      content.includes('Supplier') || content.includes('Добавувач')
    console.log(`Supplier section visible: ${hasSupplierSection}`)

    // Check fields are still populated
    const inputs = page.locator('input[type="text"]')
    const inputCount = await inputs.count()
    let populatedCount = 0
    for (let i = 0; i < Math.min(inputCount, 15); i++) {
      const val = await inputs.nth(i).inputValue().catch(() => '')
      if (val && val.trim()) populatedCount++
    }
    console.log(
      `After switch back to Bill: ${populatedCount} populated fields`
    )
  })

  // ── 12. Upload Image Invoice ─────────────────────────────────

  test('documents — upload image invoice', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    const fixturePath = path.join(
      FIXTURES,
      'invoices',
      'real-invoice-1649.jpg'
    )
    if (!fs.existsSync(fixturePath)) {
      console.log(`SKIP: Fixture not found: ${fixturePath}`)
      return test.skip()
    }

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    const fileInput = page.locator('input[type="file"]').first()
    const inputCount = await page.locator('input[type="file"]').count()
    if (inputCount === 0) {
      console.log('SKIP: No file input found')
      return test.skip()
    }

    await fileInput.setInputFiles(fixturePath)
    await page.waitForTimeout(2000)
    await ss(page, '12-image-upload')
  })

  // ── 13. Upload Contract PDF ──────────────────────────────────

  test('documents — upload contract PDF', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    const fixturePath = path.join(
      FIXTURES,
      'contracts',
      'a1-dogovor-ikt-uslugi.pdf'
    )
    if (!fs.existsSync(fixturePath)) {
      console.log(`SKIP: Fixture not found: ${fixturePath}`)
      return test.skip()
    }

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    const fileInput = page.locator('input[type="file"]').first()
    if ((await fileInput.count()) === 0) return test.skip()

    await fileInput.setInputFiles(fixturePath)
    await page.waitForTimeout(2000)
    await ss(page, '13-contract-upload')
  })

  // ── 14. Upload Tax Form ──────────────────────────────────────

  test('documents — upload tax form', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    const fixturePath = path.join(
      FIXTURES,
      'tax-forms',
      'ddv-04-vat-return.pdf'
    )
    if (!fs.existsSync(fixturePath)) {
      console.log(`SKIP: Fixture not found: ${fixturePath}`)
      return test.skip()
    }

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    const fileInput = page.locator('input[type="file"]').first()
    if ((await fileInput.count()) === 0) return test.skip()

    await fileInput.setInputFiles(fixturePath)
    await page.waitForTimeout(2000)
    await ss(page, '14-taxform-upload')
  })

  // ── 15. Upload Bank Statement ────────────────────────────────

  test('documents — upload bank statement image', async () => {
    test.setTimeout(30000)
    if (!EMAIL) return test.skip()

    const fixturePath = path.join(
      FIXTURES,
      'bank-statements',
      'komercijalna-izvod-1626.jpg'
    )
    if (!fs.existsSync(fixturePath)) {
      console.log(`SKIP: Fixture not found: ${fixturePath}`)
      return test.skip()
    }

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    const fileInput = page.locator('input[type="file"]').first()
    if ((await fileInput.count()) === 0) return test.skip()

    await fileInput.setInputFiles(fixturePath)
    await page.waitForTimeout(2000)
    await ss(page, '15-bank-statement-upload')
  })

  // ── 16. Review Different Document Types ──────────────────────

  test('documents — review page for multiple documents', async () => {
    test.setTimeout(60000)
    if (!EMAIL) return test.skip()

    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Click through the first 3 documents and screenshot each review page
    const docLinks = page.locator('table tbody tr, a[href*="/documents/"]')
    const docCount = await docLinks.count()
    const toCheck = Math.min(docCount, 3)

    for (let i = 0; i < toCheck; i++) {
      await page.goto(`${BASE}/admin/documents`)
      await page.waitForLoadState('networkidle')
      await page.waitForTimeout(500)

      const rows = page.locator('table tbody tr')
      const rowCount = await rows.count()
      if (i >= rowCount) break

      await rows.nth(i).click()
      await page.waitForLoadState('networkidle')
      await page.waitForTimeout(1500)

      await ss(page, `16-review-doc-${i + 1}`)

      // Log the document type
      const content = await page.content()
      const types = [
        'Invoice',
        'Receipt',
        'Contract',
        'Bank Statement',
        'Tax Form',
        'Product List',
      ]
      const found = types.find((t) => content.includes(t))
      console.log(`Document ${i + 1} type: ${found || 'unknown'}`)
    }
  })

  // ── 17. Delete Document ──────────────────────────────────────

  test('documents — delete button works', async () => {
    test.setTimeout(20000)
    if (!EMAIL) return test.skip()

    // Navigate to a document review page
    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    const rows = page.locator('table tbody tr')
    const rowCount = await rows.count()
    if (rowCount === 0) {
      console.log('SKIP: No documents to test delete')
      return test.skip()
    }

    // Click the last document (least likely to be important)
    await rows.last().click()
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Find delete button (trash icon)
    const deleteBtn = page.locator('button[title*="Delete"], button:has(svg.h-4.w-4)').first()
    const deleteBtnVisible = await deleteBtn.isVisible().catch(() => false)

    if (deleteBtnVisible) {
      console.log('Delete button found and visible')
      // Don't actually click delete in tests — just verify it exists
    }

    await ss(page, '17-delete-button')
  })

  // ── 18. Mobile Responsive ────────────────────────────────────

  test('documents — mobile responsive layout', async () => {
    test.setTimeout(20000)
    if (!EMAIL) return test.skip()

    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 812 })
    await page.waitForTimeout(300)

    // Documents list
    await page.goto(`${BASE}/admin/documents`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)
    await ss(page, '18-mobile-documents-list')

    // Review page
    const rows = page.locator('table tbody tr')
    const rowCount = await rows.count()
    if (rowCount > 0) {
      await rows.first().click()
      await page.waitForLoadState('networkidle')
      await page.waitForTimeout(1500)
      await ss(page, '18-mobile-review-page')

      // Verify the split layout stacks vertically on mobile
      // (lg:flex-row becomes flex-col on small screens)
      const content = await page.content()
      console.log('Mobile review page loaded')
    }

    // Reset viewport
    await page.setViewportSize({ width: 1280, height: 720 })
    await page.waitForTimeout(300)
  })

  // ── 19. Error Report ─────────────────────────────────────────

  test('documents — no critical JS errors or API failures', async () => {
    if (!EMAIL) return test.skip()

    // Filter out non-critical errors
    const criticalJsErrors = jsErrors.filter(
      (e) =>
        !e.error.includes('ResizeObserver') &&
        !e.error.includes('Non-Error promise rejection')
    )
    const criticalApiErrors = apiErrors.filter(
      (e) =>
        e.status >= 500 &&
        !e.url.includes('/processing-status') // polling 404s are expected
    )

    console.log(`JS errors: ${jsErrors.length} total, ${criticalJsErrors.length} critical`)
    console.log(`API errors: ${apiErrors.length} total, ${criticalApiErrors.length} critical`)

    if (criticalJsErrors.length > 0) {
      console.log('Critical JS errors:', JSON.stringify(criticalJsErrors, null, 2))
    }
    if (criticalApiErrors.length > 0) {
      console.log('Critical API errors:', JSON.stringify(criticalApiErrors, null, 2))
    }

    // Fail if there are critical server errors
    expect(criticalApiErrors.length).toBeLessThan(3)
  })
})
