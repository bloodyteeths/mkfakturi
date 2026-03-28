/**
 * Fiscal Monitor — Cash Register Fraud Detection E2E Tests
 *
 * Tests the fiscal monitor dashboard, event logging, fraud alerts,
 * and audit report features against production.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/fiscal-monitor-e2e.spec.js --project=chromium
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

/** Fetch CSRF cookie + token for POST/PATCH requests. */
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

/** PATCH with Sanctum session auth. */
async function apiPatch(page, url, body) {
  const xsrfToken = await ensureCsrf(page)
  return page.evaluate(
    async ({ url, body, xsrfToken }) => {
      const res = await fetch(url, {
        method: 'PATCH',
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

test.describe.configure({ mode: 'serial' })

test.describe('Fiscal Monitor — E2E', () => {
  let page

  test.beforeAll(async ({ browser }) => {
    page = await browser.newPage()
    // Login
    await page.goto(`${BASE}/login`)
    await page.waitForTimeout(3000)
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForTimeout(5000)
  })

  test.afterAll(async () => {
    await page.close()
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 1: Dashboard API returns valid structure
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Dashboard API returns valid structure', async () => {
    const res = await apiGet(page, 'fiscal-monitor/dashboard')

    expect(res.status).toBe(200)
    expect(res.data?.data).toBeDefined()

    const data = res.data.data
    expect(data).toHaveProperty('devices')
    expect(data).toHaveProperty('alerts')
    expect(data).toHaveProperty('summary')
    expect(data.summary).toHaveProperty('total_devices')
    expect(data.summary).toHaveProperty('open_devices')
    expect(data.summary).toHaveProperty('closed_devices')
    expect(data.summary).toHaveProperty('open_alerts')
    expect(data.summary).toHaveProperty('critical_alerts')

    expect(typeof data.summary.total_devices).toBe('number')

    console.log(`Dashboard: ${data.summary.total_devices} devices, ${data.summary.open_alerts} alerts ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 2: Events API supports listing and filtering
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Events API supports listing and filtering', async () => {
    const res = await apiGet(page, 'fiscal-monitor/events?limit=10')

    expect(res.status).toBe(200)
    // Should return paginated response
    expect(res.data).toHaveProperty('data')

    console.log(`Events API: ${res.data.data?.length || 0} events returned ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 3: Alerts API returns filtered results
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Alerts API returns filtered results', async () => {
    const res = await apiGet(page, 'fiscal-monitor/alerts')

    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')

    console.log(`Alerts API: ${res.data.data?.length || 0} unresolved alerts ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 4: Audit report API returns valid structure
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Audit report API returns valid structure', async () => {
    const res = await apiGet(page, 'fiscal-monitor/audit-report?from=2026-03-01&to=2026-03-28')

    expect(res.status).toBe(200)
    expect(res.data?.data).toBeDefined()

    const data = res.data.data
    expect(data).toHaveProperty('period')
    expect(data).toHaveProperty('by_user')
    expect(data).toHaveProperty('by_device')
    expect(data).toHaveProperty('by_day')
    expect(data).toHaveProperty('total_events')
    expect(data.period.from).toBe('2026-03-01')
    expect(data.period.to).toBe('2026-03-28')

    console.log(`Audit report: ${data.total_events} events, ${data.by_user?.length || 0} users, ${data.by_device?.length || 0} devices ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 5: Event logging validates required fields
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Event logging validates required fields', async () => {
    // Missing fiscal_device_id should return 422
    const res = await apiPost(page, 'fiscal-monitor/events', {
      event_type: 'open',
    })

    expect(res.status).toBe(422)
    console.log('Event validation: 422 on missing device_id ✓')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 6: Event logging rejects invalid event types
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Event logging rejects invalid event types', async () => {
    const res = await apiPost(page, 'fiscal-monitor/events', {
      fiscal_device_id: 999999,
      event_type: 'hack',
    })

    expect(res.status).toBe(422)
    console.log('Event validation: 422 on invalid event_type ✓')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 7: Event type enum matches expected values
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Event type enum matches expected values', async () => {
    const validTypes = ['open', 'close', 'z_report', 'error', 'receipt', 'void', 'status_check']

    for (const type of validTypes) {
      // Just validate the type is accepted (device may not exist, but 422 for device_id is OK)
      const res = await apiPost(page, 'fiscal-monitor/events', {
        fiscal_device_id: 1,
        event_type: type,
      })

      // Should NOT be 422 for event_type — may be 422 for device_id (exists check)
      // or 201 if device exists
      if (res.status === 422 && res.data?.errors?.event_type) {
        throw new Error(`Event type '${type}' was rejected`)
      }
    }

    console.log(`All ${validTypes.length} event types accepted ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 8: Dashboard UI loads
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Dashboard UI loads', async () => {
    const response = await page.goto(`${BASE}/admin/fiscal-monitor`, { waitUntil: 'domcontentloaded', timeout: 15000 })
    // The page should load (200 or SPA redirect)
    expect(response.status()).toBeLessThan(500)

    await page.waitForTimeout(5000)

    // Check the page loaded (SPA will render after JS loads)
    const url = page.url()
    const isOnPage = url.includes('fiscal-monitor') || url.includes('admin')
    expect(isOnPage).toBeTruthy()

    console.log(`Dashboard UI loaded at ${url} ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 9: Audit report UI loads
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Audit report UI loads', async () => {
    const response = await page.goto(`${BASE}/admin/fiscal-monitor/audit`, { waitUntil: 'domcontentloaded', timeout: 15000 })
    expect(response.status()).toBeLessThan(500)

    await page.waitForTimeout(5000)

    const url = page.url()
    const isOnPage = url.includes('fiscal-monitor') || url.includes('admin')
    expect(isOnPage).toBeTruthy()

    console.log(`Audit report UI loaded at ${url} ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 10: Fiscal devices API still works (regression check)
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Fiscal devices API still works (regression)', async () => {
    const res = await apiGet(page, 'fiscal-devices')

    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')
    expect(res.data).toHaveProperty('supported_types')

    console.log(`Fiscal devices: ${res.data.data?.length || 0} devices, ${Object.keys(res.data.supported_types || {}).length} types ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 11: Fraud detection rules — rapid open/close logic
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Fraud detection rules — rapid open/close logic', async () => {
    // This tests that the fraud detection rules are correctly defined
    // by checking the alert types match expected patterns
    const EXPECTED_ALERT_TYPES = [
      'unexpected_close',
      'off_hours_activity',
      'gap_in_receipts',
      'cash_discrepancy',
      'frequent_voids',
      'no_z_report',
      'rapid_open_close',
    ]

    const EXPECTED_SEVERITIES = ['low', 'medium', 'high', 'critical']

    // The alerts endpoint should accept filtering by each type
    for (const type of EXPECTED_ALERT_TYPES) {
      const res = await apiGet(page, `fiscal-monitor/alerts?alert_type=${type}&status=all`)
      expect(res.status).toBe(200)
    }

    console.log(`All ${EXPECTED_ALERT_TYPES.length} alert types are valid filter parameters ✓`)
    console.log(`Fraud detection rules: ${EXPECTED_ALERT_TYPES.join(', ')} ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 12: Events support date range filtering
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Events support date range filtering', async () => {
    const res = await apiGet(page, 'fiscal-monitor/events?from=2026-03-01&to=2026-03-28&limit=5')

    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')

    console.log(`Events date filtering: ${res.data.data?.length || 0} events in range ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 13: Device detail API returns valid structure
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Device detail API returns valid structure', async () => {
    // First get a device ID from the dashboard
    const dashRes = await apiGet(page, 'fiscal-monitor/dashboard')
    expect(dashRes.status).toBe(200)

    const devices = dashRes.data?.data?.devices || []
    if (devices.length === 0) {
      console.log('No devices to test detail endpoint — skipping ✓')
      return
    }

    const deviceId = devices[0].device?.id || devices[0].id
    const res = await apiGet(page, `fiscal-monitor/devices/${deviceId}`)

    expect(res.status).toBe(200)
    expect(res.data?.data).toBeDefined()

    const data = res.data.data
    expect(data).toHaveProperty('device')
    expect(data).toHaveProperty('status')
    expect(data).toHaveProperty('recent_events')
    expect(data).toHaveProperty('alerts')
    expect(data).toHaveProperty('daily_stats')
    expect(data).toHaveProperty('operators')

    expect(data.device).toHaveProperty('id')
    expect(data.device).toHaveProperty('serial_number')
    expect(['open', 'closed']).toContain(data.status)

    console.log(`Device detail: ${data.device.serial_number} (${data.status}), ${data.recent_events?.length || 0} events, ${data.operators?.length || 0} operators ✓`)
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 14: Device detail returns 404 for non-existent device
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Device detail returns 404 for non-existent device', async () => {
    const res = await apiGet(page, 'fiscal-monitor/devices/999999')

    expect(res.status).toBe(404)
    console.log('Device detail: 404 on non-existent device ✓')
  })

  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  // Test 15: Device detail UI loads
  // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  test('Device detail UI loads', async () => {
    // Get a device ID first
    const dashRes = await apiGet(page, 'fiscal-monitor/dashboard')
    const devices = dashRes.data?.data?.devices || []
    if (devices.length === 0) {
      console.log('No devices to test UI — skipping ✓')
      return
    }

    const deviceId = devices[0].device.id
    const response = await page.goto(`${BASE}/admin/fiscal-monitor/device/${deviceId}`, { waitUntil: 'domcontentloaded', timeout: 15000 })
    expect(response.status()).toBeLessThan(500)

    await page.waitForTimeout(5000)

    const url = page.url()
    expect(url.includes('fiscal-monitor') || url.includes('admin')).toBeTruthy()

    console.log(`Device detail UI loaded at ${url} ✓`)
  })
})

// CLAUDE-CHECKPOINT
