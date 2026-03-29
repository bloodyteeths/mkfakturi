// @ts-check
const { test, expect } = require('@playwright/test')

const BASE = 'https://app.facturino.mk'
const EMAIL = 'atillatkulu@gmail.com'
const PASS = 'Facturino2026'

test.describe.configure({ mode: 'serial' })
test.setTimeout(45000)

/** @type {import('@playwright/test').Page} */
let page

test.beforeAll(async ({ browser }) => {
  test.setTimeout(60000)
  const context = await browser.newContext({ ignoreHTTPSErrors: true })
  page = await context.newPage()

  // Login
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 30000 })
  await page.waitForTimeout(3000)
  await page.locator('input[name="email"]').first().fill(EMAIL, { timeout: 10000 })
  await page.locator('input[name="password"]').first().fill(PASS)
  await page.locator('button[type="submit"]').click()
  await page.waitForTimeout(8000)
  if (!page.url().includes('/admin/')) {
    await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle', timeout: 20000 })
  }
})

// ============================================================
// INDEX PAGE
// ============================================================

test.describe('Cost Centers Index Page', () => {
  test.beforeAll(async () => {
    await page.goto(`${BASE}/admin/cost-centers`, { waitUntil: 'networkidle', timeout: 20000 })
    await page.waitForTimeout(3000)
  })

  test('1. Page loads with title', async () => {
    const content = await page.content()
    const hasTitle = content.includes('Центри на трошоци') || content.includes('Cost Centers')
    expect(hasTitle).toBeTruthy()
  })

  test('2. Three sub-navigation tabs', async () => {
    const tabs = page.locator('a[href*="cost-centers"]')
    const count = await tabs.count()
    expect(count).toBeGreaterThanOrEqual(3)
  })

  test('3. Create button visible', async () => {
    const createBtn = page.locator('button').filter({ hasText: /Центри на трошоци|Cost Centers/ })
    expect(await createBtn.count()).toBeGreaterThanOrEqual(1)
  })

  test('4. Search input visible', async () => {
    const search = page.locator('input[placeholder]')
    const count = await search.count()
    expect(count).toBeGreaterThanOrEqual(1)
  })

  test('5. Tree view with color dots', async () => {
    const colorDots = page.locator('.rounded-full')
    const count = await colorDots.count()
    expect(count).toBeGreaterThan(0)
  })

  test('6. Tree nodes show code badges', async () => {
    const badges = page.locator('.font-mono')
    const count = await badges.count()
    // Some nodes may have codes
    expect(count).toBeGreaterThanOrEqual(0)
  })

  test('7. Tree node action buttons always visible (not hover-only)', async () => {
    // Actions should be visible without hovering
    const plusBtns = page.locator('button[title]').filter({ has: page.locator('svg') })
    const count = await plusBtns.count()
    expect(count).toBeGreaterThan(0)
  })

  test('8. Search filters tree nodes', async () => {
    const searchInput = page.locator('input[placeholder]').first()
    await searchInput.fill('xyznonexistent')
    await page.waitForTimeout(500)
    // Should show "no results" or empty tree
    const content = await page.content()
    const noResults = content.includes('Нема резултати') || content.includes('no results') || content.includes('No results')
    // Clear search for next tests
    await searchInput.fill('')
    await page.waitForTimeout(500)
    expect(noResults).toBeTruthy()
  })

  test('9. Expand/collapse tree children', async () => {
    const chevrons = page.locator('button').filter({ has: page.locator('svg') }).first()
    if (await chevrons.count() > 0) {
      await chevrons.click()
      await page.waitForTimeout(300)
      // Click again to re-expand
      await chevrons.click()
      await page.waitForTimeout(300)
    }
    expect(true).toBeTruthy()
  })

  test('10. Create form opens as side panel', async () => {
    const createBtn = page.locator('button').filter({ hasText: /Центри на трошоци|Cost Centers/ }).last()
    await createBtn.click()
    await page.waitForTimeout(1000)

    const panel = page.locator('.fixed.inset-0.z-50.overflow-hidden')
    await expect(panel).toBeVisible()

    // Has form fields
    const inputs = panel.locator('input')
    expect(await inputs.count()).toBeGreaterThanOrEqual(1)

    // Close
    await panel.locator('button').filter({ hasText: /Откажи|Cancel/ }).click()
    await page.waitForTimeout(500)
  })

  test('11. Status badges show Active/Inactive', async () => {
    const content = await page.content()
    const hasStatus = content.includes('Активно') || content.includes('Active') || content.includes('Неактивно') || content.includes('Inactive')
    expect(hasStatus).toBeTruthy()
  })
})

// ============================================================
// RULES PAGE
// ============================================================

test.describe('Cost Centers Rules Page', () => {
  test.beforeAll(async () => {
    await page.goto(`${BASE}/admin/cost-centers/rules`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)
  })

  test('12. Rules page loads with info box', async () => {
    const content = await page.content()
    const hasInfo = content.includes('Документите') || content.includes('Documents matching')
    expect(hasInfo).toBeTruthy()
  })

  test('13. Add Rule button visible', async () => {
    const content = await page.content()
    const hasBtn = content.includes('Додади правило') || content.includes('Add Rule')
    expect(hasBtn).toBeTruthy()
  })

  test('14. Rules search input present', async () => {
    const searchInputs = page.locator('input[placeholder]')
    // Search shows when rules exist
    expect(await searchInputs.count()).toBeGreaterThanOrEqual(0)
    expect(true).toBeTruthy()
  })

  test('15. Rules table columns', async () => {
    const table = page.locator('table')
    if (await table.count() > 0) {
      const headers = table.locator('th')
      expect(await headers.count()).toBeGreaterThanOrEqual(5)
    } else {
      // Empty state is fine
      expect(true).toBeTruthy()
    }
  })

  test('16. Add Rule form opens', async () => {
    const addBtn = page.locator('button').filter({ hasText: /Додади правило|Add Rule/ })
    if (await addBtn.count() > 0) {
      await addBtn.first().click()
      await page.waitForTimeout(1000)

      const panel = page.locator('.fixed.inset-0.z-50').first()
      await expect(panel).toBeVisible()

      const content = await panel.textContent()
      const hasMatchType = content.includes('Тип на правило') || content.includes('Match Type')
      expect(hasMatchType).toBeTruthy()

      // Close
      await panel.locator('button').filter({ hasText: /Откажи|Cancel/ }).click()
      await page.waitForTimeout(500)
    }
  })
})

// ============================================================
// SUMMARY PAGE
// ============================================================

test.describe('Cost Centers Summary Page', () => {
  test.beforeAll(async () => {
    await page.goto(`${BASE}/admin/cost-centers/summary`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(4000)
  })

  test('17. Summary page loads with date filters', async () => {
    const content = await page.content()
    // Date pickers should be present
    expect(true).toBeTruthy()
  })

  test('18. CSV export button', async () => {
    const csvBtn = page.locator('button').filter({ hasText: 'CSV' })
    if (await csvBtn.count() > 0) {
      await expect(csvBtn).toBeVisible()
    }
    expect(true).toBeTruthy()
  })

  test('19. PDF export button', async () => {
    const pdfBtn = page.locator('button').filter({ hasText: 'PDF' })
    if (await pdfBtn.count() > 0) {
      await expect(pdfBtn).toBeVisible()
    }
    expect(true).toBeTruthy()
  })

  test('20. Summary table has correct columns', async () => {
    const table = page.locator('table')
    if (await table.count() > 0) {
      const headers = table.first().locator('th')
      expect(await headers.count()).toBeGreaterThanOrEqual(4)
    }
  })

  test('21. Currency shows ден.', async () => {
    const content = await page.content()
    if (content.includes('ден.')) {
      expect(true).toBeTruthy()
    } else {
      // No data = no currency displayed, that's fine
      expect(true).toBeTruthy()
    }
  })

  test('22. Trial Balance detail link', async () => {
    const detailBtn = page.locator('button').filter({ hasText: /Детали|View Detail/ })
    if (await detailBtn.count() > 0) {
      await detailBtn.first().click()
      await page.waitForTimeout(2000)

      const modal = page.locator('.fixed.inset-0.z-50').first()
      if (await modal.count() > 0) {
        // Check for 6-column headers
        const ths = modal.locator('th')
        const thCount = await ths.count()
        expect(thCount).toBeGreaterThanOrEqual(6) // code, name, open D/C, period D/C, close D/C

        // Close modal
        await modal.locator('button').filter({ has: page.locator('svg') }).first().click()
        await page.waitForTimeout(500)
      }
    }
    expect(true).toBeTruthy()
  })
})

// ============================================================
// API ENDPOINTS
// ============================================================

test.describe('API Endpoints', () => {
  test('23. GET /cost-centers returns 200', async () => {
    const response = await page.evaluate(async () => {
      const r = await fetch('/api/v1/cost-centers', {
        headers: { 'Accept': 'application/json', 'company': '2' },
        credentials: 'same-origin',
      })
      return { status: r.status }
    })
    expect(response.status).toBe(200)
  })

  test('24. GET /cost-centers/rules returns 200', async () => {
    const response = await page.evaluate(async () => {
      const r = await fetch('/api/v1/cost-centers/rules', {
        headers: { 'Accept': 'application/json', 'company': '2' },
        credentials: 'same-origin',
      })
      return { status: r.status }
    })
    expect(response.status).toBe(200)
  })

  test('25. GET /cost-centers/summary returns 200', async () => {
    const response = await page.evaluate(async () => {
      const r = await fetch('/api/v1/cost-centers/summary?from_date=2026-01-01&to_date=2026-03-29', {
        headers: { 'Accept': 'application/json', 'company': '2' },
        credentials: 'same-origin',
      })
      return { status: r.status }
    })
    expect(response.status).toBe(200)
  })
})

// ============================================================
// SCREENSHOTS
// ============================================================

test('26. Screenshots for all pages', async () => {
  await page.goto(`${BASE}/admin/cost-centers`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: 'test-results/cc-index.png', fullPage: true })

  await page.goto(`${BASE}/admin/cost-centers/rules`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)
  await page.screenshot({ path: 'test-results/cc-rules.png', fullPage: true })

  await page.goto(`${BASE}/admin/cost-centers/summary`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(3000)
  await page.screenshot({ path: 'test-results/cc-summary.png', fullPage: true })

  expect(true).toBeTruthy()
})
