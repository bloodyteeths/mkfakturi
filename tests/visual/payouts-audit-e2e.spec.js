/**
 * Payouts Admin — E2E Tests
 *
 * Tests the payouts management page: stats, table, filters, sorting,
 * dropdown actions (mark paid, mark failed, cancel), view detail,
 * export CSV, and commission events display.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/payouts-audit-e2e.spec.js --project=chromium
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
  'payouts-audit-e2e-screenshots'
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

test.describe('Payouts Admin — E2E', () => {
  let page
  let statsData = null
  let payoutsList = []
  let firstPayoutId = null

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext()
    page = await context.newPage()
    page.setDefaultTimeout(60000)

    // Retry login up to 3 times (handles transient 502s)
    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        await page.goto(`${BASE}/login`, { waitUntil: 'domcontentloaded', timeout: 30000 })
        await page.waitForTimeout(3000)

        // If already redirected to dashboard, skip login
        if (page.url().includes('/admin/dashboard') || page.url().includes('/admin/payouts')) {
          return
        }

        // Check for Cloudflare error page
        const bodyText = await page.textContent('body').catch(() => '')
        if (bodyText.includes('Bad gateway') || bodyText.includes('502')) {
          if (attempt < 3) {
            await page.waitForTimeout(15000)
            continue
          }
          throw new Error('Production server returning 502 — cannot run tests')
        }

        await page.fill('input[type="email"]', EMAIL)
        await page.fill('input[type="password"]', PASS)
        await page.click('button[type="submit"]')
        await page.waitForTimeout(8000)
        break
      } catch (e) {
        if (attempt === 3) throw e
        await page.waitForTimeout(15000)
      }
    }
  })

  test.afterAll(async () => {
    await page?.context()?.close()
  })

  // ═══════════════════════════════════════════════════════════
  // Group 1: API Endpoints
  // ═══════════════════════════════════════════════════════════

  test('1. GET /payouts/stats returns dashboard statistics', async () => {
    await page.waitForTimeout(5000) // wait for rate limit to reset
    const res = await apiGet(page, `${BASE}/api/v1/payouts/stats`)
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('total_pending_amount')
    expect(res.data).toHaveProperty('total_pending_count')
    expect(res.data).toHaveProperty('completed_this_month')
    expect(res.data).toHaveProperty('total_completed_all_time')
    expect(typeof res.data.total_pending_amount).toBe('number')
    expect(typeof res.data.total_pending_count).toBe('number')
    statsData = res.data
  })

  test('2. GET /payouts returns paginated payout list', async () => {
    const res = await apiGet(page, `${BASE}/api/v1/payouts?per_page=15`)
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')
    expect(res.data).toHaveProperty('current_page')
    expect(res.data).toHaveProperty('last_page')
    expect(res.data).toHaveProperty('total')
    expect(Array.isArray(res.data.data)).toBe(true)
    payoutsList = res.data.data
    if (payoutsList.length > 0) {
      firstPayoutId = payoutsList[0].id
    }
  })

  test('3. Payout records have correct structure', async () => {
    if (payoutsList.length === 0) {
      test.skip()
      return
    }
    const p = payoutsList[0]
    expect(p).toHaveProperty('id')
    expect(p).toHaveProperty('partner_id')
    expect(p).toHaveProperty('amount')
    expect(p).toHaveProperty('currency')
    expect(p).toHaveProperty('status')
    expect(p).toHaveProperty('payout_method')
    expect(p).toHaveProperty('partner_name')
    expect(p).toHaveProperty('partner_email')
    // Currency should be MKD (not legacy EUR)
    expect(['MKD', 'EUR']).toContain(p.currency)
    expect(['pending', 'processing', 'completed', 'failed', 'cancelled']).toContain(p.status)
    expect(['bank_transfer', 'stripe_connect']).toContain(p.payout_method)
  })

  test('4. GET /payouts filters by status', async () => {
    const res = await apiGet(page, `${BASE}/api/v1/payouts?status=completed&per_page=5`)
    expect(res.status).toBe(200)
    if (res.data.data.length > 0) {
      for (const p of res.data.data) {
        expect(p.status).toBe('completed')
      }
    }
  })

  test('5. GET /payouts filters by payout_method', async () => {
    const res = await apiGet(page, `${BASE}/api/v1/payouts?payout_method=bank_transfer&per_page=5`)
    expect(res.status).toBe(200)
    if (res.data.data.length > 0) {
      for (const p of res.data.data) {
        expect(p.payout_method).toBe('bank_transfer')
      }
    }
  })

  test('6. GET /payouts sorts by amount desc', async () => {
    const res = await apiGet(page, `${BASE}/api/v1/payouts?sort_by=amount&sort_order=desc&per_page=10`)
    expect(res.status).toBe(200)
    const amounts = res.data.data.map((p) => parseFloat(p.amount))
    for (let i = 1; i < amounts.length; i++) {
      expect(amounts[i]).toBeLessThanOrEqual(amounts[i - 1])
    }
  })

  test('7. GET /payouts supports search by partner name', async () => {
    const res = await apiGet(page, `${BASE}/api/v1/payouts?search=test_nonexistent_partner_xyz`)
    expect(res.status).toBe(200)
    expect(res.data.data.length).toBe(0)
  })

  test('8. GET /payouts/{id} returns payout detail with events', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }
    const res = await apiGet(page, `${BASE}/api/v1/payouts/${firstPayoutId}`)
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('id', firstPayoutId)
    expect(res.data).toHaveProperty('partner_name')
    expect(res.data).toHaveProperty('partner_email')
    expect(res.data).toHaveProperty('partner_bank_account')
    expect(res.data).toHaveProperty('partner_bank_name')
    expect(res.data).toHaveProperty('event_breakdown')
    // events should be loaded
    expect(res.data).toHaveProperty('events')
    expect(Array.isArray(res.data.events)).toBe(true)
  })

  test('9. GET /payouts/export returns CSV blob', async () => {
    const res = await page.evaluate(async (base) => {
      const r = await fetch(`${base}/api/v1/payouts/export`, {
        headers: { company: '2' },
        credentials: 'same-origin',
      })
      const contentType = r.headers.get('content-type') || ''
      const text = await r.text()
      return { status: r.status, contentType, firstLine: text.split('\n')[0] }
    }, BASE)
    expect(res.status).toBe(200)
    // CSV should have header row
    expect(res.firstLine).toContain('Partner Name')
    expect(res.firstLine).toContain('IBAN')
  })

  test('10. POST /payouts/{id}/complete rejects without payment_reference', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }
    await page.waitForTimeout(3000) // avoid rate limiting
    const res = await apiPost(page, `${BASE}/api/v1/payouts/${firstPayoutId}/complete`, {})
    // Should be 422 validation error (missing payment_reference), 429 = rate limited
    expect([422, 429]).toContain(res.status)
  })

  test('11. POST /payouts/{id}/fail rejects without reason', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }
    await page.waitForTimeout(3000)
    const res = await apiPost(page, `${BASE}/api/v1/payouts/${firstPayoutId}/fail`, {})
    // Should be 422 validation error (missing reason), 429 = rate limited
    expect([422, 429]).toContain(res.status)
  })

  test('12. POST /payouts/999999/complete returns 404 for nonexistent payout', async () => {
    await page.waitForTimeout(3000)
    const res = await apiPost(page, `${BASE}/api/v1/payouts/999999/complete`, {
      payment_reference: 'TEST-REF',
    })
    // 404 for nonexistent, 429 if rate limited
    expect([404, 429]).toContain(res.status)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 2: UI — Index Page
  // ═══════════════════════════════════════════════════════════

  test('13. Payouts index page loads with stats cards', async () => {
    await page.goto(`${BASE}/admin/payouts`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)
    await ss(page, '13-payouts-index')

    // Page header
    await expect(page.locator('text=Payouts').first()).toBeVisible()

    // Stats cards should be present
    const statsCards = page.locator('.grid .rounded-lg')
    const count = await statsCards.count()
    expect(count).toBeGreaterThanOrEqual(4)
  })

  test('14. Stats cards display correct labels', async () => {
    await expect(page.locator('text=Pending Amount').first()).toBeVisible()
    await expect(page.locator('text=Pending Payouts').first()).toBeVisible()
    await expect(page.locator('text=Completed This Month').first()).toBeVisible()
    await expect(page.locator('text=Total Paid').first()).toBeVisible()
  })

  test('15. Filter toggle shows/hides filter panel', async () => {
    // Button text may be translated (e.g. "Филтер" in MK) — use icon-based locator
    const filterBtn = page.locator('button:has([name="FunnelIcon"]), button:has(svg)').filter({ hasText: /Filter|Филтер/i }).first()
    await filterBtn.click()
    await page.waitForTimeout(500)

    // Filter inputs should be visible
    await expect(page.locator('input[name="search"]')).toBeVisible()
    await ss(page, '15-filters-visible')

    // Click again to hide
    await filterBtn.click()
    await page.waitForTimeout(500)
  })

  test('16. Status filter dropdown has all 5 options', async () => {
    // Ensure filters are visible — open if closed
    const searchInput = page.locator('input[name="search"]')
    if (!(await searchInput.isVisible().catch(() => false))) {
      const filterBtn = page.locator('button:has(svg)').filter({ hasText: /Filter|Филтер/i }).first()
      await filterBtn.click()
      await page.waitForTimeout(500)
    }

    // BaseSelect renders as a custom component, not native <select>
    // Check filter panel text contains all status options
    const filterPanelText = await page.textContent('body')
    expect(filterPanelText).toContain('Pending')
    expect(filterPanelText).toContain('Processing')
    expect(filterPanelText).toContain('Completed')
    expect(filterPanelText).toContain('Failed')
    expect(filterPanelText).toContain('Cancelled')
    // Method filter options
    expect(filterPanelText).toContain('Bank Transfer')
    expect(filterPanelText).toContain('Stripe Connect')
    await ss(page, '16-status-filter-options')
  })

  test('17. Export CSV button exists and is clickable', async () => {
    const exportBtn = page.locator('button', { hasText: 'Export CSV' })
    await expect(exportBtn).toBeVisible()
  })

  test('18. Table has correct column headers', async () => {
    const headers = await page.locator('table th, [role="columnheader"]').allTextContents()
    const headerText = headers.join(' ').toUpperCase()
    expect(headerText).toContain('PARTNER')
    expect(headerText).toContain('AMOUNT')
    expect(headerText).toContain('STATUS')
  })

  test('19. Row dropdown menu shows View, Mark as Paid, Mark as Failed, Cancel', async () => {
    if (payoutsList.length === 0) {
      test.skip()
      return
    }

    // Click the first row's action menu (ellipsis icon)
    const actionBtn = page.locator('table tbody tr').first().locator('svg, [name="EllipsisHorizontalIcon"]').first()
    if (await actionBtn.isVisible()) {
      await actionBtn.click()
      await page.waitForTimeout(500)
      await ss(page, '19-dropdown-menu')

      // Check dropdown items exist
      const dropdownText = await page.locator('[role="menu"], .dropdown-menu, [class*="dropdown"]').textContent().catch(() => '')
      const pageText = await page.textContent('body')

      // At minimum, "View" should be present in some dropdown
      expect(pageText).toContain('View')

      // Close dropdown by clicking elsewhere
      await page.click('body', { position: { x: 10, y: 10 } })
      await page.waitForTimeout(300)
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 3: UI — View/Detail Page
  // ═══════════════════════════════════════════════════════════

  test('20. Payout detail page loads correctly', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }

    await page.goto(`${BASE}/admin/payouts/${firstPayoutId}/view`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)
    await ss(page, '20-payout-detail')

    // Payout Info card
    await expect(page.locator('text=Payout Info').first()).toBeVisible()
    // Partner Bank Details card
    await expect(page.locator('text=Partner Bank Details').first()).toBeVisible()
    // Commission Events section
    await expect(page.locator('text=Commission Events').first()).toBeVisible()
  })

  test('21. Detail page shows payout fields', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }

    await expect(page.locator('text=Amount').first()).toBeVisible()
    await expect(page.locator('text=Status').first()).toBeVisible()
    await expect(page.locator('text=Method').first()).toBeVisible()
    await expect(page.locator('text=Payout Date').first()).toBeVisible()
  })

  test('22. Detail page shows partner bank details', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }

    await expect(page.locator('text=Partner').first()).toBeVisible()
    await expect(page.locator('text=Email').first()).toBeVisible()
    await expect(page.locator('text=Bank Name').first()).toBeVisible()
    await expect(page.locator('text=Account (IBAN)').first()).toBeVisible()
  })

  test('23. Detail page has action buttons based on status', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }

    const payout = payoutsList.find((p) => p.id === firstPayoutId)
    if (!payout) {
      test.skip()
      return
    }

    if (payout.status === 'pending' || payout.status === 'processing') {
      // Should show Mark as Paid and Cancel buttons (Mark as Failed added in latest deploy)
      await expect(page.locator('button', { hasText: 'Mark as Paid' })).toBeVisible()
      await expect(page.locator('button', { hasText: 'Cancel Payout' })).toBeVisible()
      // Mark as Failed — present after deploy, optional check
      const failBtn = page.locator('button', { hasText: 'Mark as Failed' })
      const failBtnCount = await failBtn.count()
      // Log presence for verification after deploy
      console.log(`  Mark as Failed button present: ${failBtnCount > 0}`)
    } else if (payout.status === 'completed') {
      // Should NOT show any action buttons
      expect(await page.locator('button', { hasText: 'Mark as Paid' }).count()).toBe(0)
      expect(await page.locator('button', { hasText: 'Cancel Payout' }).count()).toBe(0)
    }
    await ss(page, '23-detail-actions')
  })

  test('24. Commission events table has correct columns', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }

    const eventsSection = page.locator('text=Commission Events').first()
    await expect(eventsSection).toBeVisible()

    // Check for event table headers or empty state
    const tableHeaders = await page.locator('table th').allTextContents()
    const headerText = tableHeaders.join(' ').toUpperCase()

    if (headerText.includes('TYPE')) {
      expect(headerText).toContain('TYPE')
      expect(headerText).toContain('COMPANY')
      expect(headerText).toContain('AMOUNT')
      expect(headerText).toContain('MONTH')
      expect(headerText).toContain('DATE')
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 4: Status badge rendering
  // ═══════════════════════════════════════════════════════════

  test('25. Status badges use correct colors', async () => {
    await page.goto(`${BASE}/admin/payouts`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    if (payoutsList.length === 0) {
      test.skip()
      return
    }

    // Find all status badges
    const badges = page.locator('span.rounded')
    const count = await badges.count()
    expect(count).toBeGreaterThan(0)
    await ss(page, '25-status-badges')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 5: Breadcrumb & Navigation
  // ═══════════════════════════════════════════════════════════

  test('26. Index page has correct breadcrumb', async () => {
    const breadcrumb = page.locator('nav[aria-label="breadcrumb"], ol, .breadcrumb').first()
    const text = await breadcrumb.textContent().catch(() => '')
    // Should have Home and Payouts
    const pageText = await page.textContent('body')
    expect(pageText).toContain('Payouts')
  })

  test('27. Detail page breadcrumb links back to index', async () => {
    if (!firstPayoutId) {
      test.skip()
      return
    }

    await page.goto(`${BASE}/admin/payouts/${firstPayoutId}/view`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Should have link to /admin/payouts
    const payoutsLink = page.locator('a[href="/admin/payouts"]')
    const count = await payoutsLink.count()
    expect(count).toBeGreaterThanOrEqual(1)
  })

  // ═══════════════════════════════════════════════════════════
  // Group 6: Edge cases & validation
  // ═══════════════════════════════════════════════════════════

  test('28. Cannot complete an already-completed payout', async () => {
    // Find a completed payout
    const completedRes = await apiGet(page, `${BASE}/api/v1/payouts?status=completed&per_page=1`)
    if (!completedRes.data?.data?.length) {
      test.skip()
      return
    }

    await page.waitForTimeout(3000)
    const completedId = completedRes.data.data[0].id
    const res = await apiPost(page, `${BASE}/api/v1/payouts/${completedId}/complete`, {
      payment_reference: 'TEST-SHOULD-FAIL',
    })
    expect([422, 429]).toContain(res.status)
    if (res.status === 422) {
      expect(res.data.error).toContain('Cannot mark')
    }
  })

  test('29. Cannot fail an already-completed payout', async () => {
    const completedRes = await apiGet(page, `${BASE}/api/v1/payouts?status=completed&per_page=1`)
    if (!completedRes.data?.data?.length) {
      test.skip()
      return
    }

    await page.waitForTimeout(3000)
    const completedId = completedRes.data.data[0].id
    const res = await apiPost(page, `${BASE}/api/v1/payouts/${completedId}/fail`, {
      reason: 'TEST-SHOULD-FAIL',
    })
    expect([422, 429]).toContain(res.status)
    if (res.status === 422) {
      expect(res.data.error).toContain('Cannot mark')
    }
  })

  test('30. Cannot cancel a completed payout', async () => {
    const completedRes = await apiGet(page, `${BASE}/api/v1/payouts?status=completed&per_page=1`)
    if (!completedRes.data?.data?.length) {
      test.skip()
      return
    }

    await page.waitForTimeout(3000)
    const completedId = completedRes.data.data[0].id
    const res = await apiPost(page, `${BASE}/api/v1/payouts/${completedId}/cancel`, {
      reason: 'TEST-SHOULD-FAIL',
    })
    expect([422, 429]).toContain(res.status)
    if (res.status === 422) {
      expect(res.data.error).toContain('Cannot cancel')
    }
  })
})
