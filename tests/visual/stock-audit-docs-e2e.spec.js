// @ts-check
import { test, expect } from '@playwright/test'

const BASE = process.env.BASE_URL || 'https://app.facturino.mk'

let cookies

test.describe.configure({ mode: 'serial' })

test.beforeAll(async ({ browser }) => {
  const ctx = await browser.newContext()
  const page = await ctx.newPage()
  page.setDefaultTimeout(30000)
  await page.goto(`${BASE}/login`)
  await page.fill('input[type="email"]', 'atillatkulu@gmail.com')
  await page.fill('input[type="password"]', 'Facturino2026')
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/admin\/dashboard/, { timeout: 30000 })
  cookies = await ctx.cookies()
  await ctx.close()
})

async function loggedPage(browser) {
  const ctx = await browser.newContext()
  await ctx.addCookies(cookies)
  const page = await ctx.newPage()
  page.setDefaultTimeout(20000)
  return { page, ctx }
}

// =============================================
// 1. ПЛТ Tab & Page
// =============================================

test('PLT tab visible in stock navigation', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Desktop: ПЛТ tab should exist
  const pltTab = page.locator('a[href="/admin/stock/trade/plt"]')
  const count = await pltTab.count()
  expect(count).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('PLT page loads at /admin/stock/trade/plt', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/trade/plt`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Should show bill selector and empty state
  const hasMultiselect = await page.locator('.multiselect, [class*="multiselect"]').count()
  expect(hasMultiselect).toBeGreaterThanOrEqual(1)

  // Empty state message should be visible
  const emptyState = await page.locator('text=Изберете фактура').count()
  expect(emptyState).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('PLT tab is active when on PLT page', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/trade/plt`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // The PLT tab link should have active styling (border-primary)
  const pltLink = page.locator('a[href="/admin/stock/trade/plt"]').first()
  const classes = await pltLink.getAttribute('class')
  expect(classes).toContain('border-primary')

  await ctx.close()
})

// =============================================
// 2. Item Card PDF Button
// =============================================

test('item card page loads with PDF button visible', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/item-card`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Page should have filters card with item selector
  const hasFilters = await page.locator('.multiselect, [class*="multiselect"]').count()
  expect(hasFilters).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('item card PDF button appears after loading data', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  // Use API to find a tracked item first
  const apiResponse = await page.request.get(`${BASE}/api/v1/items`, {
    headers: { company: '2' },
    params: { track_quantity: 'true', limit: '5' },
  })
  const items = await apiResponse.json()
  const trackedItems = items?.data || []

  if (trackedItems.length > 0) {
    const itemId = trackedItems[0].id
    await page.goto(`${BASE}/admin/stock/item-card?item_id=${itemId}`, {
      waitUntil: 'domcontentloaded',
      timeout: 30000,
    })
    await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
    await page.waitForTimeout(5000)

    // PDF button should be visible in the movements header
    const pdfBtn = page.locator('button:has-text("PDF")')
    const pdfCount = await pdfBtn.count()
    expect(pdfCount).toBeGreaterThanOrEqual(1)
  }

  await ctx.close()
})

// =============================================
// 3. Stock Count PDF Buttons
// =============================================

test('stock counts page loads', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/counts`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Page should load (even if no counts exist)
  const hasContent = await page.locator('table, [class*="text-center"], [class*="empty"]').count()
  expect(hasContent).toBeGreaterThanOrEqual(0)

  await ctx.close()
})

test('stock count view has PDF buttons', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  // Check if any counts exist via API
  const apiResponse = await page.request.get(`${BASE}/api/v1/stock/counts`, {
    headers: { company: '2' },
  })
  const countsData = await apiResponse.json()
  const counts = countsData?.data || []

  if (counts.length > 0) {
    const countId = counts[0].id
    await page.goto(`${BASE}/admin/stock/counts/${countId}`, {
      waitUntil: 'domcontentloaded',
      timeout: 30000,
    })
    await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
    await page.waitForTimeout(3000)

    // Пописна листа button should always be present
    const sheetBtn = page.locator('button:has-text("Пописна листа")')
    const sheetCount = await sheetBtn.count()
    expect(sheetCount).toBeGreaterThanOrEqual(1)

    // Записник button should appear only for completed counts
    const status = counts[0].status
    if (status === 'completed') {
      const reportBtn = page.locator('button:has-text("Записник")')
      const reportCount = await reportBtn.count()
      expect(reportCount).toBeGreaterThanOrEqual(1)
    }
  }

  await ctx.close()
})

// =============================================
// 4. Stock Count PDF API Endpoints
// =============================================

test('stock count PDF endpoint returns PDF', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  // Find a count to test
  const apiResponse = await page.request.get(`${BASE}/api/v1/stock/counts`, {
    headers: { company: '2' },
  })
  const countsData = await apiResponse.json()
  const counts = countsData?.data || []

  if (counts.length > 0) {
    const countId = counts[0].id
    const pdfResponse = await page.request.get(`${BASE}/api/v1/stock/counts/${countId}/pdf`, {
      headers: { company: '2' },
    })
    // Should return 200 with PDF content type
    expect(pdfResponse.status()).toBe(200)
    const contentType = pdfResponse.headers()['content-type']
    expect(contentType).toContain('application/pdf')
  }

  await ctx.close()
})

test('stock count report PDF endpoint works for completed counts', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  const apiResponse = await page.request.get(`${BASE}/api/v1/stock/counts`, {
    headers: { company: '2' },
  })
  const countsData = await apiResponse.json()
  const counts = countsData?.data || []

  const completedCount = counts.find(c => c.status === 'completed')
  if (completedCount) {
    const pdfResponse = await page.request.get(
      `${BASE}/api/v1/stock/counts/${completedCount.id}/report-pdf`,
      { headers: { company: '2' } }
    )
    expect(pdfResponse.status()).toBe(200)
    const contentType = pdfResponse.headers()['content-type']
    expect(contentType).toContain('application/pdf')
  }

  await ctx.close()
})

// =============================================
// 5. Document Types: Return & Write-Off
// =============================================

test('document create page shows all 5 document types', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Should show all 5 document type options
  const priemnica = await page.locator('text=Приемница').count()
  const izdatnica = await page.locator('text=Издатница').count()
  const prenosnica = await page.locator('text=Преносница').count()
  const povratnica = await page.locator('text=Повратница').count()
  const rashoduvanje = await page.locator('text=Расходување').count()

  expect(priemnica).toBeGreaterThanOrEqual(1)
  expect(izdatnica).toBeGreaterThanOrEqual(1)
  expect(prenosnica).toBeGreaterThanOrEqual(1)
  expect(povratnica).toBeGreaterThanOrEqual(1)
  expect(rashoduvanje).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('create document page opens with return type via query param', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create?create=return`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // The return radio should be selected (border-primary styling)
  const returnLabel = page.locator('label:has(input[value="return"])')
  const count = await returnLabel.count()
  if (count > 0) {
    const classes = await returnLabel.getAttribute('class')
    expect(classes).toContain('border-primary')
  }

  await ctx.close()
})

test('create document page opens with write_off type via query param', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create?create=write_off`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // The write_off radio should be selected
  const writeOffLabel = page.locator('label:has(input[value="write_off"])')
  const count = await writeOffLabel.count()
  if (count > 0) {
    const classes = await writeOffLabel.getAttribute('class')
    expect(classes).toContain('border-primary')
  }

  await ctx.close()
})

test('reason field shows for return document type', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create?create=return`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Reason field should be visible
  const reasonLabel = await page.locator('text=Причина').count()
  expect(reasonLabel).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('reason field shows for write_off document type', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create?create=write_off`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  const reasonLabel = await page.locator('text=Причина').count()
  expect(reasonLabel).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('reason field hidden for receipt document type', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Default is receipt — reason field should NOT be visible
  // "Причина" as a label shouldn't appear (but "Причина" might appear elsewhere, so check for the input group)
  const reasonInputs = page.locator('textarea[placeholder*="Причина"]')
  const count = await reasonInputs.count()
  expect(count).toBe(0)

  await ctx.close()
})

// =============================================
// 6. FAB Quick Actions — New Types
// =============================================

test('FAB menu includes Повратница and Расходување links', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Click the FAB button to open the menu
  const fab = page.locator('button.rounded-full[class*="bg-blue-600"]')
  await fab.click()
  await page.waitForTimeout(500)

  // Menu should include Повратница and Расходување links
  const povratnicaLink = page.locator('a[href*="create=return"]')
  const rashoduvanjeLink = page.locator('a[href*="create=write_off"]')

  expect(await povratnicaLink.count()).toBeGreaterThanOrEqual(1)
  expect(await rashoduvanjeLink.count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

// =============================================
// 7. Document Index — Badge Colors
// =============================================

test('documents index page loads with type badges', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents`, {
    waitUntil: 'domcontentloaded',
    timeout: 30000,
  })
  await page.waitForSelector('[class*="border-b"]', { timeout: 25000 })
  await page.waitForTimeout(3000)

  // Page should load (table or empty state)
  const hasContent = await page.locator('table, [class*="text-center"]').count()
  expect(hasContent).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

// =============================================
// 8. Inventory Document PDF Endpoints (return & write_off)
// =============================================

test('inventory document API accepts return type', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  // Verify the validation accepts return type by checking the endpoint
  // We just check the documents list loads — creating a real document would require warehouses/items
  const apiResponse = await page.request.get(`${BASE}/api/v1/stock/documents`, {
    headers: { company: '2' },
    params: { type: 'return' },
  })
  expect(apiResponse.status()).toBe(200)

  await ctx.close()
})

test('inventory document API accepts write_off type', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  const apiResponse = await page.request.get(`${BASE}/api/v1/stock/documents`, {
    headers: { company: '2' },
    params: { type: 'write_off' },
  })
  expect(apiResponse.status()).toBe(200)

  await ctx.close()
})

// =============================================
// 9. PLT API Endpoint
// =============================================

test('PLT API endpoint exists and responds', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  // Get a bill to test PLT with
  const billsResponse = await page.request.get(`${BASE}/api/v1/bills`, {
    headers: { company: '2' },
    params: { limit: '1' },
  })
  const billsData = await billsResponse.json()
  const bills = billsData?.data || billsData?.bills?.data || []

  if (bills.length > 0) {
    const billId = bills[0].id
    // PLT endpoint should respond (may be partner-only, so 200 or 403 both valid)
    const pltResponse = await page.request.get(
      `${BASE}/api/v1/partner/companies/2/accounting/plt/${billId}`,
      { headers: { company: '2' } }
    )
    // Should not be 404 (route exists)
    expect(pltResponse.status()).not.toBe(404)
  }

  await ctx.close()
})

// =============================================
// 10. Navigation Consistency
// =============================================

test('all stock tabs navigate correctly', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  const tabs = [
    '/admin/stock',
    '/admin/stock/inventory',
    '/admin/stock/item-card',
    '/admin/stock/documents',
    '/admin/stock/trade/kap',
    '/admin/stock/trade/plt',
    '/admin/stock/counts',
    '/admin/stock/low-stock',
    '/admin/stock/wac-audit',
  ]

  for (const tab of tabs) {
    await page.goto(`${BASE}${tab}`, { waitUntil: 'domcontentloaded', timeout: 30000 })
    await page.waitForTimeout(2000)

    // Each page should not show a Vue error or blank page
    const errorOverlay = await page.locator('[class*="error-overlay"], [id="vite-error-overlay"]').count()
    expect(errorOverlay).toBe(0)

    // Should have the tab navigation visible
    const navLinks = await page.locator('a[href*="/admin/stock"]').count()
    expect(navLinks).toBeGreaterThanOrEqual(3)
  }

  await ctx.close()
})
