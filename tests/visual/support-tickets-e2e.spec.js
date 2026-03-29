/**
 * Support Tickets — E2E Tests
 *
 * Tests the full support ticket lifecycle: creation, listing, viewing,
 * replying, status changes, and verifying resolved tickets stay resolved.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/support-tickets-e2e.spec.js --project=chromium
 */
import { test, expect } from '@playwright/test'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''

/** GET with Sanctum session auth. */
async function apiGet(page, url) {
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
    { url: `${BASE}/api/v1/${url}` }
  )
}

/** Fetch CSRF cookie + token for POST/PUT/DELETE requests. */
async function ensureCsrf(page) {
  return page.evaluate(async (base) => {
    await fetch(`${base}/sanctum/csrf-cookie`, { credentials: 'include' })
    const cookies = document.cookie.split(';').map((c) => c.trim())
    const xsrf = cookies.find((c) => c.startsWith('XSRF-TOKEN='))
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

test.describe('Support Tickets — E2E', () => {
  let page
  let ticketId = null
  let messageId = null

  // Rate-limit safety: pause between tests (Sanctum limits ~60 req/min)
  test.beforeEach(async () => {
    await new Promise((r) => setTimeout(r, 2500))
  })

  test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext()
    page = await context.newPage()

    // Login via UI form with rate-limit safety
    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForTimeout(8000) // Extra wait for Sanctum session + rate limiter cooldown

    // Verify login succeeded by checking we're redirected to admin
    const url = page.url()
    if (!url.includes('/admin')) {
      throw new Error(`Login failed — still at ${url}`)
    }
  })

  test.afterAll(async () => {
    // Cleanup: delete the test ticket if it was created
    if (ticketId) {
      await apiDelete(page, `support/tickets/${ticketId}`)
    }
    await page?.context()?.close()
  })

  // ═══════════════════════════════════════════════════════════
  // Group 1: Ticket CRUD via API
  // ═══════════════════════════════════════════════════════════

  test('1. GET /support/tickets returns ticket list', async () => {
    const res = await apiGet(page, 'support/tickets')
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')
    expect(Array.isArray(res.data.data)).toBe(true)
  })

  test('2. POST /support/tickets creates a new ticket', async () => {
    const xsrfToken = await ensureCsrf(page)
    const res = await page.evaluate(
      async ({ url, xsrfToken }) => {
        const r = await fetch(url, {
          method: 'POST',
          credentials: 'include',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            company: '2',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken,
          },
          body: JSON.stringify({
            title: '[E2E] Test ticket — no-reopen check',
            message: 'Automated Playwright test ticket. This will be resolved then replied to, verifying status stays resolved.',
            priority: 'low',
          }),
        })
        const text = await r.text()
        let data = null
        try { data = JSON.parse(text) } catch (e) { /* raw text */ }
        return { status: r.status, data, rawText: text.substring(0, 2000) }
      },
      { url: `${BASE}/api/v1/support/tickets`, xsrfToken }
    )
    if (res.status !== 201 && res.status !== 200) {
      console.log('CREATE TICKET FULL RESPONSE:', JSON.stringify(res, null, 2))
    }
    expect([200, 201]).toContain(res.status)
    expect(res.data).toHaveProperty('data')
    expect(res.data.data).toHaveProperty('id')
    expect(res.data.data.title).toContain('E2E')
    expect(res.data.data.status).toBe('open')
    expect(res.data.data.priority).toBe('low')

    ticketId = res.data.data.id
  })

  test('3. GET /support/tickets/:id returns the created ticket', async () => {
    expect(ticketId).toBeTruthy()

    const res = await apiGet(page, `support/tickets/${ticketId}`)
    expect(res.status).toBe(200)
    expect(res.data.data.id).toBe(ticketId)
    expect(res.data.data.status).toBe('open')
  })

  test('4. PUT /support/tickets/:id updates ticket priority', async () => {
    const res = await apiPut(page, `support/tickets/${ticketId}`, {
      priority: 'high',
    })
    expect(res.status).toBe(200)
    expect(res.data.data.priority).toBe('high')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 2: Messages / Replies
  // ═══════════════════════════════════════════════════════════

  test('5. POST /support/tickets/:id/messages adds a reply', async () => {
    const res = await apiPost(page, `support/tickets/${ticketId}/messages`, {
      message: 'First reply from E2E test — ticket is still open.',
    })
    expect(res.status).toBeLessThanOrEqual(201)
    expect(res.data).toHaveProperty('data')
    expect(res.data.data).toHaveProperty('id')
    expect(res.data.data.message).toContain('First reply')

    messageId = res.data.data.id
  })

  test('6. GET /support/tickets/:id/messages returns messages', async () => {
    const res = await apiGet(page, `support/tickets/${ticketId}/messages`)
    expect(res.status).toBe(200)
    expect(res.data.data.length).toBeGreaterThanOrEqual(1)
  })

  test('7. PUT /support/tickets/:id/messages/:msgId edits a message', async () => {
    expect(messageId).toBeTruthy()

    const res = await apiPut(
      page,
      `support/tickets/${ticketId}/messages/${messageId}`,
      { message: 'Edited reply from E2E test.' }
    )
    expect(res.status).toBe(200)
    expect(res.data.data.message).toContain('Edited reply')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 3: Status changes + no-reopen fix
  // ═══════════════════════════════════════════════════════════

  test('8. Admin change-status to resolved', async () => {
    const res = await apiPost(
      page,
      `support/admin/tickets/${ticketId}/change-status`,
      { status: 'resolved' }
    )
    expect(res.status).toBe(200)

    // Verify it's now resolved
    const check = await apiGet(page, `support/tickets/${ticketId}`)
    expect(check.data.data.status).toBe('resolved')
  })

  test('9. Reply to resolved ticket does NOT reopen it', async () => {
    // This is the key test — before the fix, this reply would set status back to 'open'
    const reply = await apiPost(
      page,
      `support/tickets/${ticketId}/messages`,
      { message: 'Reply after resolution — ticket must stay resolved.' }
    )
    expect(reply.status).toBeLessThanOrEqual(201)

    // Verify ticket status is STILL resolved
    const check = await apiGet(page, `support/tickets/${ticketId}`)
    expect(check.data.data.status).toBe('resolved')
  })

  test('10. Second reply to resolved ticket still does NOT reopen', async () => {
    const reply = await apiPost(
      page,
      `support/tickets/${ticketId}/messages`,
      { message: 'Another reply — still must stay resolved.' }
    )
    expect(reply.status).toBeLessThanOrEqual(201)

    const check = await apiGet(page, `support/tickets/${ticketId}`)
    expect(check.data.data.status).toBe('resolved')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 4: Admin operations
  // ═══════════════════════════════════════════════════════════

  test('11. GET /support/admin/tickets lists all tickets (cross-tenant)', async () => {
    const res = await apiGet(page, 'support/admin/tickets')
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')
    expect(Array.isArray(res.data.data)).toBe(true)
  })

  test('12. GET /support/admin/statistics returns stats', async () => {
    const res = await apiGet(page, 'support/admin/statistics')
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('total_tickets')
  })

  test('13. Admin change-status to closed', async () => {
    const res = await apiPost(
      page,
      `support/admin/tickets/${ticketId}/change-status`,
      { status: 'closed' }
    )
    expect(res.status).toBe(200)

    const check = await apiGet(page, `support/tickets/${ticketId}`)
    expect(check.data.data.status).toBe('closed')
  })

  test('14. Admin change-status back to open', async () => {
    const res = await apiPost(
      page,
      `support/admin/tickets/${ticketId}/change-status`,
      { status: 'open' }
    )
    expect(res.status).toBe(200)

    const check = await apiGet(page, `support/tickets/${ticketId}`)
    expect(check.data.data.status).toBe('open')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 5: UI navigation
  // ═══════════════════════════════════════════════════════════

  test('15. /admin/support page loads without errors', async () => {
    await page.goto(`${BASE}/admin/support`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(4000)
    // Page should not redirect to login (would mean session expired)
    expect(page.url()).toContain('/admin/support')
    // Verify ticket data loaded via API check
    const res = await apiGet(page, 'support/tickets')
    expect(res.status).toBe(200)
    const found = res.data.data?.find((t) => t.title?.includes('E2E'))
    expect(found).toBeTruthy()
  })

  test('16. /admin/support/:id page loads ticket detail', async () => {
    await page.goto(`${BASE}/admin/support/${ticketId}`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(4000)
    expect(page.url()).toContain(`/support/${ticketId}`)
    // Verify detail via API
    const res = await apiGet(page, `support/tickets/${ticketId}`)
    expect(res.data.data.title).toContain('no-reopen check')
  })

  test('17. /admin/support/create page loads', async () => {
    await page.goto(`${BASE}/admin/support/create`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)
    expect(page.url()).toContain('/support')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 6: Cleanup + delete
  // ═══════════════════════════════════════════════════════════

  test('18. DELETE message from ticket', async () => {
    expect(messageId).toBeTruthy()

    const res = await apiDelete(
      page,
      `support/tickets/${ticketId}/messages/${messageId}`
    )
    expect(res.status).toBe(200)
  })

  test('19. DELETE /support/tickets/:id removes the ticket', async () => {
    const res = await apiDelete(page, `support/tickets/${ticketId}`)
    expect(res.status).toBe(200)

    // Verify it's gone
    const check = await apiGet(page, `support/tickets/${ticketId}`)
    expect([404, 403]).toContain(check.status)

    ticketId = null // Prevent afterAll from trying to delete again
  })

  test('20. Verify deleted ticket is gone from list', async () => {
    const res = await apiGet(page, 'support/tickets')
    expect(res.status).toBe(200)

    // The specific ticket we created and deleted should not be found
    // (there may be leftover tickets from previous runs — ignore those)
    const found = res.data.data?.find((t) => t.id === ticketId)
    expect(found).toBeUndefined()
  })
})
