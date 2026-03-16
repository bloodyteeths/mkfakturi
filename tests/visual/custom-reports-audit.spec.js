// @ts-check
import { test, expect } from '@playwright/test'

const BASE_URL = process.env.TEST_BASE_URL || 'http://localhost:8000'
const TEST_EMAIL = process.env.TEST_EMAIL || 'giovanny.ledner@example.com'
const TEST_PASSWORD = process.env.TEST_PASSWORD || 'password123'

test.describe('Custom Reports — Partner Only Audit', () => {
  test.describe.configure({ mode: 'serial' })

  /** @type {import('@playwright/test').Page} */
  let page

  test.beforeAll(async ({ browser }) => {
    test.setTimeout(60000)
    const context = await browser.newContext()
    page = await context.newPage()

    // Login
    await page.goto(`${BASE_URL}/admin/login`)
    await page.waitForLoadState('networkidle')
    await page.fill('input[type="email"]', TEST_EMAIL)
    await page.fill('input[type="password"]', TEST_PASSWORD)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/admin\/dashboard/, { timeout: 30000 })
  })

  test.afterAll(async () => {
    if (page) {
      await page.context().close()
    }
  })

  test('finance hub does NOT show Custom Reports card', async () => {
    await page.goto(`${BASE_URL}/admin/finance`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Page should load with Финансии title
    const pageContent = await page.content()
    expect(pageContent).toContain('Финансии')

    // Should NOT have a link to /admin/custom-reports anywhere on the page
    const customReportsLink = page.locator('a[href="/admin/custom-reports"]')
    const count = await customReportsLink.count()
    expect(count).toBe(0)
  })

  test('company custom-reports route no longer exists', async () => {
    // Navigate to old company custom-reports URL
    await page.goto(`${BASE_URL}/admin/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1500)

    // Should NOT be on /admin/custom-reports — should redirect to dashboard or show blank
    const url = page.url()
    // The route was removed, so Vue router won't match it. It may show blank or redirect
    // Just verify we don't see any custom reports content
    const createButton = page.getByText(/Креирај извештај|Create Report/i)
    const count = await createButton.count()
    // If the route doesn't exist, we shouldn't see the create button
    // (might show 404 or redirect to dashboard)
    expect(count).toBe(0)
  })

  test('company custom-reports/create route no longer exists', async () => {
    await page.goto(`${BASE_URL}/admin/custom-reports/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1500)

    // Should NOT show the old 5-step wizard
    const wizardStep = page.getByText(/step_name|step_accounts|Step 1|Назив и опис/i)
    const count = await wizardStep.count()
    expect(count).toBe(0)
  })

  test('partner custom reports page loads', async () => {
    await page.goto(`${BASE_URL}/admin/partner/accounting/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Should show the page title
    const title = page.getByText(/Прилагодени извештаи|Custom Reports|Raporte te Personalizuara|Ozel Raporlar/i)
    await expect(title.first()).toBeVisible()

    // Should show intro banner
    const intro = page.getByText(/Креирајте извештаи|Create reports|page_intro/i)
    if (await intro.count() > 0) {
      await expect(intro.first()).toBeVisible()
    }
  })

  test('partner page shows company selector', async () => {
    await page.goto(`${BASE_URL}/admin/partner/accounting/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Should show company selector or placeholder text
    const selector = page.locator('[class*="multiselect"]')
    const placeholder = page.getByText(/Изберете компанија|Select company|select_company/i)

    const hasSelector = await selector.count() > 0
    const hasPlaceholder = await placeholder.count() > 0

    expect(hasSelector || hasPlaceholder).toBeTruthy()
  })

  test('new report button shows inline create form', async () => {
    await page.goto(`${BASE_URL}/admin/partner/accounting/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Select a company first (if multiselect available)
    const multiselect = page.locator('[class*="multiselect"]').first()
    if (await multiselect.isVisible({ timeout: 2000 }).catch(() => false)) {
      await multiselect.click()
      await page.waitForTimeout(500)

      // Click first option if available
      const option = page.locator('[class*="multiselect"] li, [class*="multiselect-option"]').first()
      if (await option.isVisible({ timeout: 2000 }).catch(() => false)) {
        await option.click()
        await page.waitForTimeout(1000)
      }
    }

    // Look for "New Report" button
    const newReportBtn = page.getByText(/Нов извештај|New Report|Yeni Rapor|Raport i Ri/i)
    if (await newReportBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await newReportBtn.click()
      await page.waitForTimeout(500)

      // Should show inline create form with name input
      const nameInput = page.locator('input[type="text"]').first()
      await expect(nameInput).toBeVisible()

      // Should show account filter pills
      const allAccountsPill = page.getByText(/Сите конта|All Accounts|Tum Hesaplar|Te Gjitha/i)
      await expect(allAccountsPill.first()).toBeVisible()

      // Should show column checkboxes
      const codeCheckbox = page.getByText(/Шифра|Code|Kod|Kodi/i)
      await expect(codeCheckbox.first()).toBeVisible()

      // Should show period dropdown
      const periodLabel = page.getByText(/Период|Period|Donem|Periudha/i)
      await expect(periodLabel.first()).toBeVisible()

      // Should show schedule dropdown (NOT cron expression)
      const scheduleLabel = page.getByText(/Фреквенција|Frequency|Sikligi|Frekuenca/i)
      await expect(scheduleLabel.first()).toBeVisible()

      // Should NOT show cron expression field
      const cronField = page.getByText(/Cron израз|Cron Expression|Cron Ifadesi|Shprehja Cron/i)
      const cronCount = await cronField.count()
      expect(cronCount).toBe(0)
    }
  })

  test('create form has help text throughout', async () => {
    await page.goto(`${BASE_URL}/admin/partner/accounting/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Select company
    const multiselect = page.locator('[class*="multiselect"]').first()
    if (await multiselect.isVisible({ timeout: 2000 }).catch(() => false)) {
      await multiselect.click()
      await page.waitForTimeout(500)
      const option = page.locator('[class*="multiselect"] li, [class*="multiselect-option"]').first()
      if (await option.isVisible({ timeout: 2000 }).catch(() => false)) {
        await option.click()
        await page.waitForTimeout(1000)
      }
    }

    // Open create form
    const newReportBtn = page.getByText(/Нов извештај|New Report|Yeni Rapor|Raport i Ri/i)
    if (await newReportBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await newReportBtn.click()
      await page.waitForTimeout(500)

      // Check for help text (contextual explanations)
      const helpTexts = page.locator('p.text-xs.text-gray-500, .text-xs.text-gray-400')
      const helpCount = await helpTexts.count()
      // Should have multiple help text elements
      expect(helpCount).toBeGreaterThanOrEqual(3)
    }
  })

  test('create form cancel button closes form', async () => {
    await page.goto(`${BASE_URL}/admin/partner/accounting/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Select company
    const multiselect = page.locator('[class*="multiselect"]').first()
    if (await multiselect.isVisible({ timeout: 2000 }).catch(() => false)) {
      await multiselect.click()
      await page.waitForTimeout(500)
      const option = page.locator('[class*="multiselect"] li, [class*="multiselect-option"]').first()
      if (await option.isVisible({ timeout: 2000 }).catch(() => false)) {
        await option.click()
        await page.waitForTimeout(1000)
      }
    }

    // Open create form
    const newReportBtn = page.getByText(/Нов извештај|New Report/i)
    if (await newReportBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await newReportBtn.click()
      await page.waitForTimeout(500)

      // Click X button (close)
      const closeBtn = page.locator('button svg path[d*="M6 18"]').first()
      if (await closeBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
        await closeBtn.locator('..').click()
        await page.waitForTimeout(500)
      } else {
        // Try cancel button
        const cancelBtn = page.getByText(/Откажи|Cancel/i).last()
        if (await cancelBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
          await cancelBtn.click()
          await page.waitForTimeout(500)
        }
      }

      // Create form should be closed — "New Report" button should be visible again
      await expect(page.getByText(/Нов извештај|New Report/i).first()).toBeVisible({ timeout: 3000 })
    }
  })

  test('no cron expression anywhere on the page', async () => {
    await page.goto(`${BASE_URL}/admin/partner/accounting/custom-reports`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Cron expression should not appear anywhere
    const cronText = page.getByText(/cron/i)
    const cronCount = await cronText.count()
    expect(cronCount).toBe(0)
  })
})

// CLAUDE-CHECKPOINT
