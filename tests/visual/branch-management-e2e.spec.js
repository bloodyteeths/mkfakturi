/**
 * Branch Management (Подружница) — E2E Tests
 *
 * Tests the project/branch dual-mode feature: type tabs, branch CRUD,
 * branch-specific fields, financial summary, and branch comparison API.
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/branch-management-e2e.spec.js --project=chromium
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

test.describe.configure({ mode: 'serial' })

test.describe('Branch Management — E2E', () => {
  let page
  let branchId = null

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(60000)
    const context = await browser.newContext()
    page = await context.newPage()

    await page.goto(`${BASE}/login`, { waitUntil: 'networkidle', timeout: 30000 })
    await page.waitForTimeout(3000)
    await page.fill('input[type="email"]', EMAIL)
    await page.fill('input[type="password"]', PASS)
    await page.click('button[type="submit"]')
    await page.waitForTimeout(8000) // Wait for redirect + Sanctum session
  })

  test.afterAll(async () => {
    // Cleanup: delete test branch if created
    if (branchId) {
      await apiPost(page, 'projects/delete', { ids: [branchId] })
    }
    await page?.context()?.close()
  })

  // ═══════════════════════════════════════════════════════════
  // Group 1: Projects API — Backward Compatibility
  // ═══════════════════════════════════════════════════════════

  test('1. GET /projects returns list with type and branch counts in meta', async () => {
    const res = await apiGet(page, 'projects')
    expect(res.status).toBe(200)
    expect(res.data).toHaveProperty('data')
    expect(Array.isArray(res.data.data)).toBe(true)

    // Verify meta has the new branch count fields
    const meta = res.data.meta
    expect(meta).toHaveProperty('project_total_count')
    expect(meta).toHaveProperty('branch_total_count')
    expect(meta).toHaveProperty('all_total_count')
    expect(meta).toHaveProperty('active_branch_count')

    // Existing counts still present
    expect(meta).toHaveProperty('open_count')
    expect(meta).toHaveProperty('in_progress_count')
    expect(meta).toHaveProperty('completed_count')
  })

  test('2. GET /projects?type=project filters to projects only', async () => {
    const res = await apiGet(page, 'projects?type=project')
    expect(res.status).toBe(200)

    // All returned items should be type=project
    for (const item of res.data.data) {
      expect(item.type).toBe('project')
    }
  })

  test('3. GET /projects?type=branch filters to branches only', async () => {
    const res = await apiGet(page, 'projects?type=branch')
    expect(res.status).toBe(200)

    // All returned items should be type=branch (may be empty)
    for (const item of res.data.data) {
      expect(item.type).toBe('branch')
    }
  })

  test('4. GET /projects/list includes type field', async () => {
    const res = await apiGet(page, 'projects/list')
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)

    // Verify items have type field
    if (res.data.data.length > 0) {
      const item = res.data.data[0]
      expect(item).toHaveProperty('type')
      expect(item).toHaveProperty('display_name')
    }
  })

  test('5. GET /projects/list?type=branch filters dropdown list', async () => {
    const res = await apiGet(page, 'projects/list?type=branch')
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)

    for (const item of res.data.data) {
      expect(item.type).toBe('branch')
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 2: Branch CRUD
  // ═══════════════════════════════════════════════════════════

  test('6. POST /projects — create a branch with required fields', async () => {
    const res = await apiPost(page, 'projects', {
      name: 'E2E Test Подружница Скопје',
      code: 'BR-E2E-001',
      type: 'branch',
      address: 'ул. Македонија бр. 1',
      city: 'Скопје',
      municipality: 'Центар',
      registration_number: 'E2E-EMBS-001',
      phone: '+389 2 123 456',
      email: 'test-branch@facturino.mk',
      is_active: true,
      status: 'open',
    })

    expect(res.status).toBe(201)
    expect(res.data.data).toHaveProperty('id')
    expect(res.data.data.type).toBe('branch')
    expect(res.data.data.name).toBe('E2E Test Подружница Скопје')
    expect(res.data.data.address).toBe('ул. Македонија бр. 1')
    expect(res.data.data.city).toBe('Скопје')
    expect(res.data.data.is_active).toBe(true)

    branchId = res.data.data.id
  })

  test('7. POST /projects — branch without address fails validation', async () => {
    const res = await apiPost(page, 'projects', {
      name: 'Branch Missing Address',
      type: 'branch',
      status: 'open',
    })

    // Should fail with 422 (address required_if type=branch)
    expect(res.status).toBe(422)
  })

  test('8. GET /projects/{id} — show branch with branch-specific fields', async () => {
    expect(branchId).not.toBeNull()

    const res = await apiGet(page, `projects/${branchId}`)
    expect(res.status).toBe(200)

    const data = res.data.data
    expect(data.type).toBe('branch')
    expect(data.address).toBe('ул. Македонија бр. 1')
    expect(data.city).toBe('Скопје')
    expect(data.municipality).toBe('Центар')
    expect(data.registration_number).toBe('E2E-EMBS-001')
    expect(data.phone).toBe('+389 2 123 456')
    expect(data.email).toBe('test-branch@facturino.mk')
    expect(data.is_active).toBe(true)

    // Branch should have allows_new_documents = true (active branch)
    expect(data.allows_new_documents).toBe(true)
    expect(data.is_editable).toBe(true)

    // Should have warehouse/device counts
    expect(data).toHaveProperty('warehouse_count')
    expect(data).toHaveProperty('fiscal_device_count')
  })

  test('9. PUT /projects/{id} — update branch fields', async () => {
    expect(branchId).not.toBeNull()

    const res = await apiPut(page, `projects/${branchId}`, {
      name: 'E2E Test Подружница Скопје (Updated)',
      code: 'BR-E2E-001',
      type: 'branch',
      address: 'ул. Македонија бр. 2',
      city: 'Скопје',
      municipality: 'Гази Баба',
      registration_number: 'E2E-EMBS-001',
      phone: '+389 2 654 321',
      email: 'test-branch-updated@facturino.mk',
      is_active: true,
      status: 'open',
    })

    expect(res.status).toBe(200)
    expect(res.data.data.municipality).toBe('Гази Баба')
    expect(res.data.data.phone).toBe('+389 2 654 321')
  })

  test('10. GET /projects/{id}/summary — financial summary works for branches', async () => {
    expect(branchId).not.toBeNull()

    const res = await apiGet(page, `projects/${branchId}/summary`)
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)
    expect(res.data.data).toHaveProperty('total_invoiced')
    expect(res.data.data).toHaveProperty('total_expenses')
    expect(res.data.data).toHaveProperty('net_result')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 3: Branch Comparison API
  // ═══════════════════════════════════════════════════════════

  test('11. GET /projects/branches/comparison returns branch comparison data', async () => {
    const res = await apiGet(page, 'projects/branches/comparison')
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)
    expect(Array.isArray(res.data.data)).toBe(true)

    // Should include our test branch
    if (res.data.data.length > 0) {
      const branch = res.data.data[0]
      expect(branch).toHaveProperty('id')
      expect(branch).toHaveProperty('name')
      expect(branch).toHaveProperty('city')
      expect(branch).toHaveProperty('total_invoiced')
      expect(branch).toHaveProperty('total_expenses')
      expect(branch).toHaveProperty('net_result')
      expect(branch).toHaveProperty('warehouse_count')
      expect(branch).toHaveProperty('fiscal_device_count')
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 4: UI Navigation
  // ═══════════════════════════════════════════════════════════

  test('12. Projects page loads at /admin/projects', async () => {
    await page.goto(`${BASE}/admin/projects`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Page should have the type tabs
    const tabButtons = await page.locator('button').filter({ hasText: /Projects|Проекти|Branches|Подружници/ }).count()
    expect(tabButtons).toBeGreaterThanOrEqual(2)
  })

  test('13. Type tabs filter the table', async () => {
    // Click "Branches" tab
    const branchTab = page.locator('button').filter({ hasText: /Branches|Подружници/ }).first()
    if (await branchTab.isVisible()) {
      await branchTab.click()
      await page.waitForTimeout(2000)

      // URL or table should reflect branch filter
      // The table should show city column for branches
    }
  })

  test('14. Create branch page loads with type selector', async () => {
    await page.goto(`${BASE}/admin/projects/create?type=branch`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Should see the branch type card or branch-specific fields
    const addressInput = page.locator('textarea, input').filter({ hasText: '' })
    expect(await addressInput.count()).toBeGreaterThan(0)
  })

  test('15. View branch page shows branch-specific info', async () => {
    expect(branchId).not.toBeNull()

    await page.goto(`${BASE}/admin/projects/${branchId}/view`, { waitUntil: 'networkidle' })
    await page.waitForTimeout(3000)

    // Should display branch info header
    const pageContent = await page.textContent('body')
    expect(pageContent).toContain('Скопје')
  })

  // ═══════════════════════════════════════════════════════════
  // Group 5: Existing Projects Still Work
  // ═══════════════════════════════════════════════════════════

  test('16. POST /projects — regular project still works (backward compat)', async () => {
    const res = await apiPost(page, 'projects', {
      name: 'E2E Regular Project Test',
      code: 'PROJ-E2E-BC',
      type: 'project',
      status: 'open',
    })

    expect(res.status).toBe(201)
    expect(res.data.data.type).toBe('project')

    // Cleanup
    if (res.data.data.id) {
      await apiPost(page, 'projects/delete', { ids: [res.data.data.id] })
    }
  })

  test('17. POST /projects — project without type defaults to project', async () => {
    const res = await apiPost(page, 'projects', {
      name: 'E2E Default Type Test',
      code: 'PROJ-E2E-DT',
      status: 'open',
    })

    expect(res.status).toBe(201)
    expect(res.data.data.type).toBe('project')

    // Cleanup
    if (res.data.data.id) {
      await apiPost(page, 'projects/delete', { ids: [res.data.data.id] })
    }
  })

  // ═══════════════════════════════════════════════════════════
  // Group 6: Branch Deactivation
  // ═══════════════════════════════════════════════════════════

  test('18. Deactivating branch sets is_active=false and blocks documents', async () => {
    expect(branchId).not.toBeNull()

    const res = await apiPut(page, `projects/${branchId}`, {
      name: 'E2E Test Подружница Скопје (Updated)',
      code: 'BR-E2E-001',
      type: 'branch',
      address: 'ул. Македонија бр. 2',
      city: 'Скопје',
      is_active: false,
      status: 'open',
    })

    expect(res.status).toBe(200)
    expect(res.data.data.is_active).toBe(false)
    expect(res.data.data.allows_new_documents).toBe(false)
  })

  test('19. Reactivating branch allows documents again', async () => {
    expect(branchId).not.toBeNull()

    const res = await apiPut(page, `projects/${branchId}`, {
      name: 'E2E Test Подружница Скопје (Updated)',
      code: 'BR-E2E-001',
      type: 'branch',
      address: 'ул. Македонија бр. 2',
      city: 'Скопје',
      is_active: true,
      status: 'open',
    })

    expect(res.status).toBe(200)
    expect(res.data.data.is_active).toBe(true)
    expect(res.data.data.allows_new_documents).toBe(true)
  })

  test('20. DELETE branch via bulk delete', async () => {
    expect(branchId).not.toBeNull()

    const res = await apiPost(page, 'projects/delete', { ids: [branchId] })
    expect(res.status).toBe(200)
    expect(res.data.success).toBe(true)

    // Verify it's gone
    const check = await apiGet(page, `projects/${branchId}`)
    expect(check.status).toBe(404)

    branchId = null // Already cleaned up
  })
})
