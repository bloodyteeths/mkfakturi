// @ts-check
import { test, expect } from '@playwright/test'

const BASE = process.env.BASE_URL || 'https://app.facturino.mk'

let cookies

test.describe.configure({ mode: 'serial' })

test.beforeAll(async ({ browser }) => {
  test.setTimeout(90000)
  const ctx = await browser.newContext()
  const page = await ctx.newPage()
  page.setDefaultTimeout(60000)
  await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 })
  await page.waitForSelector('input[type="email"]', { timeout: 30000 })
  await page.fill('input[type="email"]', 'atillatkulu@gmail.com')
  await page.fill('input[type="password"]', 'Facturino2026')
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/admin\/dashboard/, { timeout: 60000 })
  await page.waitForTimeout(3000)
  cookies = await ctx.cookies()
  await ctx.close()
})

async function loggedPage(browser) {
  const ctx = await browser.newContext()
  await ctx.addCookies(cookies)
  const page = await ctx.newPage()
  page.setDefaultTimeout(30000)
  return { page, ctx }
}

/** Wait for the Vue SPA to render the stock page */
async function waitForStockPage(page) {
  // Wait for the nav — new structure has 5 top-level items including dropdown buttons
  await page.waitForSelector('a[href="/admin/stock"]', { timeout: 30000 })
  await page.waitForTimeout(2000)
}

/** Safely parse JSON from API response, return null if HTML */
async function safeJson(response) {
  const ct = response.headers()['content-type'] || ''
  if (!ct.includes('json')) return null
  try { return await response.json() } catch { return null }
}

// =============================================
// 1. Navigation Structure — 5 items (was 12)
// =============================================

test('navigation shows 5 top-level items: Преглед, Залиха, Документи, Трговија, Анализа', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  // Direct link
  expect(await page.locator('a[href="/admin/stock"]').count()).toBeGreaterThanOrEqual(1)

  // Dropdown buttons (desktop) — use direct child buttons only
  const desktopNav = page.locator('.hidden.md\\:flex')
  const buttons = desktopNav.locator('> div > button, > button')
  const buttonTexts = await buttons.allTextContents()
  const labels = buttonTexts.map(t => t.trim().replace(/\s+/g, ' '))
  expect(labels.some(l => l.includes('Залиха'))).toBe(true)
  expect(labels.some(l => l.includes('Документи'))).toBe(true)
  expect(labels.some(l => l.includes('Трговија'))).toBe(true)
  expect(labels.some(l => l.includes('Анализа'))).toBe(true)

  await ctx.close()
})

test('Залиха dropdown shows 3 items with descriptions', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  // Open dropdown
  await page.getByRole('main').locator('button:has-text("Залиха")').first().click()
  await page.waitForTimeout(300)

  // Check 3 items exist with descriptions
  expect(await page.locator('a[href="/admin/stock/inventory"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/item-card"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/warehouses"]').count()).toBeGreaterThanOrEqual(1)

  // Descriptions visible
  expect(await page.locator('text=Тековна залиха по артикл').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=Движења по артикл').count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('Документи dropdown shows documents, stocktake, adjustments', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  await page.getByRole('main').locator('button:has-text("Документи")').first().click()
  await page.waitForTimeout(300)

  expect(await page.locator('a[href="/admin/stock/documents"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/counts"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/adjustments"]').count()).toBeGreaterThanOrEqual(1)

  // Descriptions
  expect(await page.locator('text=Приемници, издатници, повратници').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=Пописни листи и записници').count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('Трговија dropdown shows Нивелации, КАП, ПЛТ with full names', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  await page.getByRole('main').locator('button:has-text("Трговија")').first().click()
  await page.waitForTimeout(300)

  expect(await page.locator('a[href="/admin/stock/trade/nivelacii"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/trade/kap"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/trade/plt"]').count()).toBeGreaterThanOrEqual(1)

  // Full names visible (not just abbreviations)
  expect(await page.locator('text=КАП — Калкулација').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=ПЛТ — Малопродажба').count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('Анализа dropdown shows low stock with badge and WAC audit', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  await page.getByRole('main').locator('button:has-text("Анализа")').first().click()
  await page.waitForTimeout(300)

  expect(await page.locator('a[href="/admin/stock/low-stock"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href="/admin/stock/wac-audit"]').count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('dropdown closes when clicking outside', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  // Open dropdown
  await page.getByRole('main').locator('button:has-text("Залиха")').first().click()
  await page.waitForTimeout(300)
  expect(await page.locator('a[href="/admin/stock/inventory"]').count()).toBeGreaterThanOrEqual(1)

  // Click outside (on the page body)
  await page.locator('body').click({ position: { x: 10, y: 10 } })
  await page.waitForTimeout(300)

  // Dropdown items should be gone
  const dropdownPanel = page.locator('.absolute.left-0.top-full')
  expect(await dropdownPanel.count()).toBe(0)

  await ctx.close()
})

// =============================================
// 2. Active State Highlighting
// =============================================

test('dropdown button highlights when child page is active', async ({ browser }) => {
  test.setTimeout(60000)
  const { page, ctx } = await loggedPage(browser)
  page.setDefaultTimeout(45000)
  await page.goto(`${BASE}/admin/stock/inventory`, { waitUntil: 'domcontentloaded', timeout: 45000 })
  await waitForStockPage(page)

  // Залиха button should have active styling
  const zalihaBtn = page.getByRole('main').locator('button:has-text("Залиха")').first()
  const classes = await zalihaBtn.getAttribute('class')
  expect(classes).toContain('border-primary')

  await ctx.close()
})

test('trade dropdown highlights when on PLT page', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/trade/plt`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  const tradeBtn = page.getByRole('main').locator('button:has-text("Трговија")').first()
  const classes = await tradeBtn.getAttribute('class')
  expect(classes).toContain('border-primary')

  await ctx.close()
})

// =============================================
// 3. Item Card PDF Button
// =============================================

test('item card PDF button appears after selecting item', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/item-card`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  const multiselect = page.locator('.multiselect, [class*="multiselect"]').first()
  await multiselect.click()
  await page.waitForTimeout(1500)
  const firstOption = page.locator('.multiselect-option, [class*="multiselect-option"]').first()
  if (await firstOption.count() > 0) {
    await firstOption.click()
    await page.waitForTimeout(3000)
    expect(await page.locator('button:has-text("PDF")').count()).toBeGreaterThanOrEqual(1)
  }

  await ctx.close()
})

// =============================================
// 4. Stock Count PDF Buttons
// =============================================

test('stock count PDF endpoint returns PDF', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/counts`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  const apiResponse = await ctx.request.get(`${BASE}/api/v1/stock/counts`, { headers: { company: '2' } })
  const countsData = await safeJson(apiResponse)
  const counts = countsData?.data || []

  if (counts.length > 0) {
    const pdfResponse = await ctx.request.get(`${BASE}/api/v1/stock/counts/${counts[0].id}/pdf`, { headers: { company: '2' } })
    expect(pdfResponse.status()).toBe(200)
    expect(pdfResponse.headers()['content-type']).toContain('application/pdf')
  }

  await ctx.close()
})

// =============================================
// 5. Document Types: Return & Write-Off
// =============================================

test('document create page shows all 5 document types', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents/create`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  expect(await page.locator('text=Приемница').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=Издатница').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=Преносница').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=Повратница').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('text=Расходување').count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

test('reason field shows for return type, hidden for receipt', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)

  // Return type — reason visible
  await page.goto(`${BASE}/admin/stock/documents/create?create=return`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)
  expect(await page.locator('text=Причина').count()).toBeGreaterThanOrEqual(1)

  // Receipt type — reason hidden
  await page.goto(`${BASE}/admin/stock/documents/create`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)
  expect(await page.locator('textarea[placeholder*="Причина"]').count()).toBe(0)

  await ctx.close()
})

// =============================================
// 6. FAB Quick Actions
// =============================================

test('FAB menu includes Повратница and Расходување links', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  await page.locator('button.rounded-full[class*="bg-blue-600"]').click()
  await page.waitForTimeout(500)

  expect(await page.locator('a[href*="create=return"]').count()).toBeGreaterThanOrEqual(1)
  expect(await page.locator('a[href*="create=write_off"]').count()).toBeGreaterThanOrEqual(1)

  await ctx.close()
})

// =============================================
// 7. API Endpoints
// =============================================

test('stock documents API accepts return and write_off type', async ({ browser }) => {
  const { page, ctx } = await loggedPage(browser)
  await page.goto(`${BASE}/admin/stock/documents`, { waitUntil: 'domcontentloaded', timeout: 30000 })
  await waitForStockPage(page)

  const returnResp = await ctx.request.get(`${BASE}/api/v1/stock/documents`, { headers: { company: '2' }, params: { type: 'return' } })
  expect(returnResp.status()).toBe(200)

  const writeOffResp = await ctx.request.get(`${BASE}/api/v1/stock/documents`, { headers: { company: '2' }, params: { type: 'write_off' } })
  expect(writeOffResp.status()).toBe(200)

  await ctx.close()
})

// =============================================
// 8. All pages navigate without errors
// =============================================

test('all stock pages render without errors', async ({ browser }) => {
  test.setTimeout(120000)
  const { page, ctx } = await loggedPage(browser)
  page.setDefaultTimeout(40000)
  const pages = [
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

  for (const url of pages) {
    await page.goto(`${BASE}${url}`, { waitUntil: 'domcontentloaded', timeout: 40000 })
    await page.waitForSelector('a[href="/admin/stock"]', { timeout: 30000 })
    await page.waitForTimeout(1000)

    const errorOverlay = await page.locator('[class*="error-overlay"], [id="vite-error-overlay"]').count()
    expect(errorOverlay).toBe(0)
  }

  await ctx.close()
})
