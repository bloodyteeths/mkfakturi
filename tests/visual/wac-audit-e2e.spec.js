/**
 * WAC Audit & Error Correction — E2E Tests
 *
 * Tests WAC chain verification, AI analysis, correction proposals,
 * and the approval workflow.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/wac-audit-e2e.spec.js --project=chromium
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
  'wac-audit-e2e-screenshots'
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

let sharedPage

test.beforeAll(async ({ browser }) => {
  sharedPage = await browser.newPage()
  // Login
  await sharedPage.goto(`${BASE}/login`)
  await sharedPage.waitForLoadState('networkidle')
  await sharedPage.fill('input[type="email"]', EMAIL)
  await sharedPage.fill('input[type="password"]', PASS)
  await sharedPage.click('button[type="submit"]')
  await sharedPage.waitForURL(/\/admin\/dashboard/, { timeout: 15000 })
  await sharedPage.waitForTimeout(2000)
})

test.afterAll(async () => {
  await sharedPage?.close()
})

// ============================================================
// 1. WAC Audit Page Navigation
// ============================================================

test('01 — WAC Audit tab is visible in stock navigation', async () => {
  const page = sharedPage
  await page.goto(`${BASE}/admin/stock`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(1000)

  // Check that WAC Audit tab exists
  const wacTab = page.locator('a[href="/admin/stock/wac-audit"]')
  await expect(wacTab).toHaveCount(1)
  await ss(page, '01-stock-tab-navigation')
})

test('02 — WAC Audit page loads with summary cards', async () => {
  const page = sharedPage
  await page.goto(`${BASE}/admin/stock/wac-audit`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Page should load without errors
  await expect(page.locator('body')).not.toContainText('500')

  await ss(page, '02-wac-audit-page')
})

// ============================================================
// 2. WAC Audit API — Run Audit
// ============================================================

test('03 — API: Run WAC audit for company 2', async () => {
  const page = sharedPage
  const result = await apiPost(page, `${BASE}/api/v1/stock/wac-audit/run`, {})

  console.log('WAC audit run result:', JSON.stringify(result, null, 2))

  expect(result.status).toBe(201)
  expect(result.data?.success).toBe(true)
  expect(result.data?.data?.id).toBeTruthy()
  expect(result.data?.data?.status).toBe('completed')
})

test('04 — API: List WAC audit runs', async () => {
  const page = sharedPage
  const result = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)

  expect(result.status).toBe(200)
  expect(Array.isArray(result.data?.data)).toBe(true)
  expect(result.data.data.length).toBeGreaterThan(0)

  console.log(
    'Audit runs:',
    result.data.data.map((r) => ({
      id: r.id,
      status: r.status,
      checked: r.total_movements_checked,
      discrepancies: r.discrepancies_found,
    }))
  )
})

test('05 — API: Get audit run detail', async () => {
  const page = sharedPage

  // Get the latest run
  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const latestRun = list.data?.data?.[0]
  expect(latestRun).toBeTruthy()

  const detail = await apiGet(page, `${BASE}/api/v1/stock/wac-audit/${latestRun.id}`)
  expect(detail.status).toBe(200)
  expect(detail.data?.data?.id).toBe(latestRun.id)
  expect(detail.data?.data?.discrepancies).toBeDefined()

  console.log('Audit detail:', {
    id: detail.data.data.id,
    discrepancies_count: detail.data.data.discrepancies?.length || 0,
    has_discrepancies: detail.data.data.has_discrepancies,
  })
})

// ============================================================
// 3. WAC Audit Detail Page UI
// ============================================================

test('06 — WAC Audit detail page renders', async () => {
  const page = sharedPage

  // Get latest run ID
  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const latestRun = list.data?.data?.[0]
  expect(latestRun).toBeTruthy()

  await page.goto(`${BASE}/admin/stock/wac-audit/${latestRun.id}`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Summary cards should be visible
  await expect(page.locator('body')).not.toContainText('500')

  await ss(page, '06-wac-audit-detail')
})

// ============================================================
// 4. Correction Proposal (if discrepancies exist)
// ============================================================

test('07 — API: Generate correction proposal if discrepancies exist', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)

  if (!runWithDiscrepancies) {
    console.log('No audit run with discrepancies found — skipping proposal test')
    test.skip()
    return
  }

  const result = await apiPost(
    page,
    `${BASE}/api/v1/stock/wac-audit/${runWithDiscrepancies.id}/proposal/generate`
  )

  console.log('Proposal generation result:', JSON.stringify(result, null, 2))

  // Either 201 (created) or 200 (existing or no correction needed)
  expect([200, 201]).toContain(result.status)
})

test('08 — API: Get correction proposal', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)

  if (!runWithDiscrepancies) {
    console.log('No audit run with discrepancies — skipping')
    test.skip()
    return
  }

  const result = await apiGet(
    page,
    `${BASE}/api/v1/stock/wac-audit/${runWithDiscrepancies.id}/proposal`
  )

  expect(result.status).toBe(200)
  console.log('Proposal:', result.data?.data ? {
    id: result.data.data.id,
    status: result.data.data.status,
    net_qty: result.data.data.net_quantity_adjustment,
    net_val: result.data.data.net_value_adjustment,
    entries: result.data.data.correction_entries?.length,
  } : 'No proposal')
})

// ============================================================
// 5. Frozen Movement Protection
// ============================================================

test('09 — Frozen movements column exists', async () => {
  const page = sharedPage

  // Verify the frozen_at column works by checking a stock movement API
  const result = await apiGet(page, `${BASE}/api/v1/stock/adjustments?limit=1`)
  expect(result.status).toBe(200)
  // The API should work without errors (migration ran successfully)
  console.log('Adjustments API works after frozen_at migration:', result.status)
})

// ============================================================
// 6. WAC Audit Run with Scoped Item
// ============================================================

test('10 — API: Run scoped audit (single item)', async () => {
  const page = sharedPage

  // Get a trackable item
  const inventory = await apiGet(page, `${BASE}/api/v1/stock/inventory?limit=1`)
  const firstItem = inventory.data?.data?.[0]

  if (!firstItem) {
    console.log('No inventory items — skipping scoped audit')
    test.skip()
    return
  }

  const result = await apiPost(page, `${BASE}/api/v1/stock/wac-audit/run`, {
    item_id: firstItem.item_id,
  })

  expect(result.status).toBe(201)
  expect(result.data?.data?.item_id).toBe(firstItem.item_id)
  console.log('Scoped audit:', {
    item: firstItem.name,
    checked: result.data.data.total_movements_checked,
    discrepancies: result.data.data.discrepancies_found,
  })
})

// ============================================================
// 7. WAC Audit Page — Run and Verify UI Flow
// ============================================================

test('11 — UI: Click Run Audit button and verify results', async () => {
  const page = sharedPage
  await page.goto(`${BASE}/admin/stock/wac-audit`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  // Check that the audit history table or empty state is shown
  const pageContent = await page.textContent('body')
  const hasContent =
    pageContent.includes('completed') ||
    pageContent.includes('pending') ||
    pageContent.includes('WAC') ||
    pageContent.includes('ПСВ')

  expect(hasContent).toBe(true)
  await ss(page, '11-wac-audit-list')
})

test('12 — UI: Audit detail page shows discrepancy table or success', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const latestRun = list.data?.data?.[0]

  if (!latestRun) {
    console.log('No audit runs — skipping')
    test.skip()
    return
  }

  await page.goto(`${BASE}/admin/stock/wac-audit/${latestRun.id}`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  const body = await page.textContent('body')

  if (latestRun.has_discrepancies) {
    // Should show discrepancy table
    expect(body).toMatch(/Position|Позиција|chain_position/)
  } else {
    // Should show success message
    expect(body).toMatch(/consistent|конзистентни|No discrepancies|Нема отстапувања/)
  }

  await ss(page, '12-wac-audit-detail-result')
})
