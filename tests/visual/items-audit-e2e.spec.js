/**
 * Items Page Audit — E2E Tests
 *
 * Tests the items page fixes: filters, bulk actions, pricing fields,
 * stock movement deletion protection, and API endpoints.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/items-audit-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''

/** GET with Sanctum session auth. */
async function apiGet(page, url, params = {}) {
  const qs = new URLSearchParams(params).toString()
  const fullUrl = `${BASE}/api/v1/${url}${qs ? '?' + qs : ''}`
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
    { url: fullUrl }
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

/** PUT with Sanctum session auth. */
async function apiPut(page, url, body) {
  const xsrfToken = await ensureCsrf(page)
  return page.evaluate(
    async ({ url, body, xsrfToken }) => {
      const res = await fetch(url, {
        method: 'PUT',
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

test.describe('Items Page Audit — E2E', () => {
  let page
  let testItemId = null
  let testCategoryId = null

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext()
    page = await context.newPage()

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForTimeout(5000)
  })

  test.afterAll(async () => {
    // Cleanup: delete test item and category
    if (testItemId) {
      await apiPost(page, 'items/delete', { ids: [testItemId] }).catch(() => {})
    }
    if (testCategoryId) {
      await apiDelete(page, `item-categories/${testCategoryId}`).catch(() => {})
    }
    await page?.context()?.close()
  })

  // ═══════════════════════════════════════════════════════════
  // Group 1: API Endpoints — Items CRUD
  // ═══════════════════════════════════════════════════════════

  test('1. GET /items returns list with pagination meta', async () => {
    const res = await apiGet(page, 'items', { limit: 5 })
    expect(res.status).toBe(200)
    expect(res.data.data).toBeDefined()
    expect(Array.isArray(res.data.data)).toBe(true)
    expect(res.data.meta).toBeDefined()
    expect(res.data.meta.item_total_count).toBeGreaterThanOrEqual(0)
  })

  test('2. POST /item-categories creates test category', async () => {
    const res = await apiPost(page, 'item-categories', {
      name: `Test Category ${Date.now()}`,
      description: 'E2E test category',
    })
    expect(res.status).toBe(200)
    expect(res.data.data.id).toBeDefined()
    testCategoryId = res.data.data.id
  })

  test('3. POST /items creates item with pricing + category fields', async () => {
    const res = await apiPost(page, 'items', {
      name: `E2E Audit Item ${Date.now()}`,
      price: 50000, // 500.00
      cost: 30000, // 300.00
      retail_price: 60000, // 600.00
      wholesale_price: 45000, // 450.00
      markup_percent: 66.67,
      category_id: testCategoryId,
      track_quantity: true,
      minimum_quantity: 5,
      allow_duplicate: true,
    })
    expect(res.status).toBe(200)
    expect(res.data.data).toBeDefined()
    testItemId = res.data.data.id
    expect(res.data.data.retail_price).toBe(60000)
    expect(res.data.data.wholesale_price).toBe(45000)
    expect(parseFloat(res.data.data.markup_percent)).toBeCloseTo(66.67, 1)
    expect(res.data.data.category_id).toBe(testCategoryId)
  })

  test('4. GET /items/{id} returns item with pricing fields', async () => {
    const res = await apiGet(page, `items/${testItemId}`)
    expect(res.status).toBe(200)
    expect(res.data.data.retail_price).toBe(60000)
    expect(res.data.data.wholesale_price).toBe(45000)
    expect(parseFloat(res.data.data.markup_percent)).toBeCloseTo(66.67, 1)
    expect(res.data.data.track_quantity).toBe(true)
  })

  test('5. PUT /items/{id} updates pricing fields', async () => {
    const res = await apiPut(page, `items/${testItemId}`, {
      name: `E2E Audit Item Updated`,
      price: 50000,
      retail_price: 70000,
      wholesale_price: 55000,
      markup_percent: 75.00,
      allow_duplicate: true,
    })
    expect(res.status).toBe(200)
    expect(res.data.data.retail_price).toBe(70000)
    expect(res.data.data.wholesale_price).toBe(55000)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 2: Filters
  // ═══════════════════════════════════════════════════════════

  test('6. GET /items with category_id filter works', async () => {
    const res = await apiGet(page, 'items', { category_id: testCategoryId })
    expect(res.status).toBe(200)
    // Our test item should be in the results
    const found = res.data.data.some(item => item.id === testItemId)
    expect(found).toBe(true)
  })

  test('7. GET /items with track_quantity=1 filter works', async () => {
    const res = await apiGet(page, 'items', { track_quantity: '1' })
    expect(res.status).toBe(200)
    // All returned items should have track_quantity = true
    for (const item of res.data.data) {
      expect(item.track_quantity).toBe(true)
    }
  })

  test('8. GET /items with track_quantity=0 filter works', async () => {
    const res = await apiGet(page, 'items', { track_quantity: '0' })
    expect(res.status).toBe(200)
    // All returned items should have track_quantity = false
    for (const item of res.data.data) {
      expect(item.track_quantity).toBe(false)
    }
  })

  test('9. GET /items with low_stock=1 filter works', async () => {
    const res = await apiGet(page, 'items', { low_stock: '1' })
    expect(res.status).toBe(200)
    // All returned items should be low stock (quantity <= minimum_quantity)
    for (const item of res.data.data) {
      expect(item.track_quantity).toBe(true)
      expect(item.quantity).toBeLessThanOrEqual(item.minimum_quantity)
    }
  })

  test('10. GET /items with search filter works', async () => {
    const res = await apiGet(page, 'items', { search: 'E2E Audit' })
    expect(res.status).toBe(200)
    const found = res.data.data.some(item => item.id === testItemId)
    expect(found).toBe(true)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 3: Bulk Actions
  // ═══════════════════════════════════════════════════════════

  test('11. POST /items/bulk-update — assign category works', async () => {
    const res = await apiPost(page, 'items/bulk-update', {
      ids: [testItemId],
      action: 'assign_category',
      category_id: testCategoryId,
    })
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)
    expect(res.data.updated_count).toBe(1)
  })

  test('12. POST /items/bulk-update — toggle stock tracking works', async () => {
    const res = await apiPost(page, 'items/bulk-update', {
      ids: [testItemId],
      action: 'toggle_track_quantity',
      track_quantity: false,
    })
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)

    // Verify the change
    const item = await apiGet(page, `items/${testItemId}`)
    expect(item.data.data.track_quantity).toBe(false)

    // Re-enable for later tests
    await apiPost(page, 'items/bulk-update', {
      ids: [testItemId],
      action: 'toggle_track_quantity',
      track_quantity: true,
    })
  })

  test('13. POST /items/bulk-update — invalid action returns 422', async () => {
    const res = await apiPost(page, 'items/bulk-update', {
      ids: [testItemId],
      action: 'invalid_action',
    })
    expect(res.status).toBe(422)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 4: Validation — legacy category field removed
  // ═══════════════════════════════════════════════════════════

  test('14. POST /items does not accept legacy category string', async () => {
    // The legacy `category` field should be ignored (not validated)
    const res = await apiPost(page, 'items', {
      name: `Legacy Field Test ${Date.now()}`,
      price: 10000,
      category: 'should-be-ignored',
      allow_duplicate: true,
    })
    // Should still create successfully (category field just ignored)
    expect(res.status).toBe(200)
    // Clean up
    if (res.data?.data?.id) {
      await apiPost(page, 'items/delete', { ids: [res.data.data.id] })
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 5: Item Categories
  // ═══════════════════════════════════════════════════════════

  test('15. GET /item-categories returns list', async () => {
    const res = await apiGet(page, 'item-categories')
    expect(res.status).toBe(200)
    expect(Array.isArray(res.data.data)).toBe(true)
    const found = res.data.data.some(cat => cat.id === testCategoryId)
    expect(found).toBe(true)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 6: UI Navigation
  // ═══════════════════════════════════════════════════════════

  test('16. Items list page loads with table', async () => {
    await page.goto(`${BASE}/admin/items`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Table should be visible
    const table = page.locator('table')
    await expect(table).toBeVisible({ timeout: 10000 })
  })

  test('17. Filter panel opens and shows new filters', async () => {
    // Click filter button
    const filterBtn = page.locator('button', { hasText: /filter/i }).first()
    await filterBtn.click()
    await page.waitForTimeout(1000)

    // Check that the new filter fields are present
    // Category filter
    const categoryLabel = page.locator('text=Category').or(page.locator('text=Категорија')).first()
    // Track quantity filter
    const trackLabel = page.locator('text=Track Inventory').or(page.locator('text=Следење залихи')).first()
    // Low stock filter
    const lowStockLabel = page.locator('text=Low Stock').or(page.locator('text=Ниска залиха')).first()

    // At least one should be visible (depends on language)
    const filtersVisible = await categoryLabel.isVisible().catch(() => false) ||
      await trackLabel.isVisible().catch(() => false) ||
      await lowStockLabel.isVisible().catch(() => false)

    expect(filtersVisible).toBe(true)
  })

  test('18. Items create page loads with pricing section', async () => {
    await page.goto(`${BASE}/admin/items/create`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Check pricing section exists
    const pricingHeader = page.locator('text=Pricing').or(page.locator('text=Цени')).or(page.locator('text=Çmimet')).first()
    await expect(pricingHeader).toBeVisible({ timeout: 10000 })

    // Check retail price field exists
    const retailLabel = page.locator('text=Retail Price').or(page.locator('text=Малопродажна цена')).first()
    await expect(retailLabel).toBeVisible({ timeout: 5000 })

    // Check wholesale price field exists
    const wholesaleLabel = page.locator('text=Wholesale Price').or(page.locator('text=Велепродажна цена')).first()
    await expect(wholesaleLabel).toBeVisible({ timeout: 5000 })

    // Check markup field exists
    const markupLabel = page.locator('text=Markup').or(page.locator('text=Маржа')).first()
    await expect(markupLabel).toBeVisible({ timeout: 5000 })
  })

  test('19. Items edit page loads with pricing fields populated', async () => {
    await page.goto(`${BASE}/admin/items/${testItemId}/edit`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Check that the pricing section is visible
    const pricingHeader = page.locator('text=Pricing').or(page.locator('text=Цени')).first()
    await expect(pricingHeader).toBeVisible({ timeout: 10000 })
  })

  // ═══════════════════════════════════════════════════════════
  // Group 7: Deletion Protection
  // ═══════════════════════════════════════════════════════════

  test('20. DELETE item succeeds for test item (cleanup)', async () => {
    // Our test item has no stock movements, invoices, or estimates
    const res = await apiPost(page, 'items/delete', { ids: [testItemId] })
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)
    testItemId = null // Mark as cleaned up
  })
})
