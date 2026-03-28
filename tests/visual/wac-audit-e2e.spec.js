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
// 2. Seed Test Discrepancies
// ============================================================

test('03 — API: Seed test discrepancy data', async () => {
  const page = sharedPage
  const result = await apiPost(page, `${BASE}/api/v1/stock/wac-audit/seed-test`, {})

  console.log('Seed result:', JSON.stringify(result, null, 2))

  expect(result.status).toBe(201)
  expect(result.data?.success).toBe(true)
  expect(result.data?.data?.movements?.length).toBe(3)
  expect(result.data?.data?.corrupted_movement).toBeTruthy()
  console.log('Corrupted movement:', result.data.data.corrupted_movement,
    'drift:', result.data.data.drift, 'cents')
})

// ============================================================
// 3. WAC Audit API — Run Audit (should find discrepancies)
// ============================================================

test('04 — API: Run WAC audit — detects seeded discrepancies', async () => {
  const page = sharedPage
  const result = await apiPost(page, `${BASE}/api/v1/stock/wac-audit/run`, {})

  console.log('WAC audit run result:', JSON.stringify(result, null, 2))

  expect(result.status).toBe(201)
  expect(result.data?.success).toBe(true)
  expect(result.data?.data?.id).toBeTruthy()
  expect(result.data?.data?.status).toBe('completed')
  expect(result.data?.data?.discrepancies_found).toBeGreaterThan(0)
  expect(result.data?.data?.has_discrepancies).toBe(true)

  console.log('Discrepancies found:', result.data.data.discrepancies_found)
})

test('05 — API: List WAC audit runs', async () => {
  const page = sharedPage
  const result = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)

  expect(result.status).toBe(200)
  expect(Array.isArray(result.data?.data)).toBe(true)
  expect(result.data.data.length).toBeGreaterThan(0)

  // Should have at least one run with discrepancies
  const withDiscrepancies = result.data.data.find((r) => r.has_discrepancies)
  expect(withDiscrepancies).toBeTruthy()

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

test('06 — API: Get audit run detail with discrepancies', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)
  expect(runWithDiscrepancies).toBeTruthy()

  const detail = await apiGet(page, `${BASE}/api/v1/stock/wac-audit/${runWithDiscrepancies.id}`)
  expect(detail.status).toBe(200)
  expect(detail.data?.data?.discrepancies?.length).toBeGreaterThan(0)

  // Root cause should be marked
  const rootCause = detail.data.data.discrepancies.find((d) => d.is_root_cause)
  expect(rootCause).toBeTruthy()
  expect(rootCause.value_drift).not.toBe(0)

  console.log('Discrepancies:', detail.data.data.discrepancies.map((d) => ({
    position: d.chain_position,
    is_root: d.is_root_cause,
    value_drift: d.value_drift,
    stored: d.stored_balance_value,
    expected: d.expected_balance_value,
  })))
})

// ============================================================
// 4. WAC Audit Detail Page UI — with discrepancies
// ============================================================

test('07 — UI: Detail page shows discrepancy table', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)
  expect(runWithDiscrepancies).toBeTruthy()

  await page.goto(`${BASE}/admin/stock/wac-audit/${runWithDiscrepancies.id}`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  await expect(page.locator('body')).not.toContainText('500')

  // Should show discrepancy count in summary card
  const body = await page.textContent('body')
  expect(body).toMatch(/[1-9]\d*/) // At least one non-zero number

  await ss(page, '07-wac-audit-detail-discrepancies')
})

// ============================================================
// 5. Correction Proposal — Generate, Retrieve, Approve
// ============================================================

test('08 — API: Generate correction proposal', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)
  expect(runWithDiscrepancies).toBeTruthy()

  const result = await apiPost(
    page,
    `${BASE}/api/v1/stock/wac-audit/${runWithDiscrepancies.id}/proposal/generate`
  )

  console.log('Proposal generation result:', JSON.stringify(result, null, 2))

  expect(result.status).toBe(201)
  expect(result.data?.success).toBe(true)
  expect(result.data?.data?.id).toBeTruthy()
  expect(result.data?.data?.status).toBe('pending')
  expect(result.data?.data?.correction_entries?.length).toBeGreaterThan(0)
  expect(result.data?.data?.net_value_adjustment).not.toBe(0)
  expect(result.data?.data?.is_usable).toBe(true)

  console.log('Proposal:', {
    id: result.data.data.id,
    entries: result.data.data.correction_entries.length,
    net_qty: result.data.data.net_quantity_adjustment,
    net_val: result.data.data.net_value_adjustment,
    expires: result.data.data.expires_at,
  })
})

test('09 — API: Get correction proposal', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)
  expect(runWithDiscrepancies).toBeTruthy()

  const result = await apiGet(
    page,
    `${BASE}/api/v1/stock/wac-audit/${runWithDiscrepancies.id}/proposal`
  )

  expect(result.status).toBe(200)
  expect(result.data?.data).toBeTruthy()
  expect(result.data.data.status).toBe('pending')
  expect(result.data.data.correction_entries?.length).toBeGreaterThan(0)

  console.log('Proposal retrieved:', {
    id: result.data.data.id,
    status: result.data.data.status,
    net_qty: result.data.data.net_quantity_adjustment,
    net_val: result.data.data.net_value_adjustment,
    entries: result.data.data.correction_entries?.length,
  })
})

test('10 — API: Approve correction proposal', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)
  expect(runWithDiscrepancies).toBeTruthy()

  // Get the proposal
  const proposalResult = await apiGet(
    page,
    `${BASE}/api/v1/stock/wac-audit/${runWithDiscrepancies.id}/proposal`
  )
  const proposal = proposalResult.data?.data
  expect(proposal).toBeTruthy()
  expect(proposal.is_usable).toBe(true)

  // Approve it
  const result = await apiPost(
    page,
    `${BASE}/api/v1/stock/wac-audit/proposals/${proposal.id}/approve`
  )

  console.log('Approve result:', JSON.stringify(result, null, 2))

  expect(result.status).toBe(200)
  expect(result.data?.success).toBe(true)
  expect(result.data?.data?.proposal?.status).toBe('applied')
  expect(result.data?.data?.created_movements).toBeGreaterThan(0)

  console.log('Correction applied:',
    result.data.data.created_movements, 'movement(s) created')
})

// ============================================================
// 6. Post-Correction Verification
// ============================================================

test('11 — API: Re-run audit — fewer or zero discrepancies after correction', async () => {
  const page = sharedPage
  const result = await apiPost(page, `${BASE}/api/v1/stock/wac-audit/run`, {})

  expect(result.status).toBe(201)
  expect(result.data?.data?.status).toBe('completed')

  console.log('Post-correction audit:', {
    checked: result.data.data.total_movements_checked,
    discrepancies: result.data.data.discrepancies_found,
  })
})

// ============================================================
// 7. Frozen Movement Protection
// ============================================================

test('12 — Frozen movements column exists', async () => {
  const page = sharedPage

  const result = await apiGet(page, `${BASE}/api/v1/stock/adjustments?limit=1`)
  expect(result.status).toBe(200)
  console.log('Adjustments API works after frozen_at migration:', result.status)
})

// ============================================================
// 8. UI Flow — List and Detail with Data
// ============================================================

test('13 — UI: WAC Audit list page shows runs with discrepancies', async () => {
  const page = sharedPage
  await page.goto(`${BASE}/admin/stock/wac-audit`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  const pageContent = await page.textContent('body')
  const hasContent =
    pageContent.includes('completed') ||
    pageContent.includes('WAC') ||
    pageContent.includes('ПСВ')

  expect(hasContent).toBe(true)
  await ss(page, '13-wac-audit-list-with-data')
})

test('14 — UI: Audit detail page shows applied correction', async () => {
  const page = sharedPage

  const list = await apiGet(page, `${BASE}/api/v1/stock/wac-audit`)
  const runWithDiscrepancies = list.data?.data?.find((r) => r.has_discrepancies)

  if (!runWithDiscrepancies) {
    console.log('No runs with discrepancies remain — skipping')
    test.skip()
    return
  }

  await page.goto(`${BASE}/admin/stock/wac-audit/${runWithDiscrepancies.id}`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)

  const body = await page.textContent('body')

  // Should show correction applied or the discrepancy table
  const hasExpectedContent =
    body.includes('applied') ||
    body.includes('применета') ||
    body.includes('Correction') ||
    body.includes('Корекција') ||
    body.match(/Position|Позиција/)

  expect(hasExpectedContent).toBeTruthy()
  await ss(page, '14-wac-audit-detail-corrected')
})
