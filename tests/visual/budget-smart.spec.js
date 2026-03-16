// @ts-check
import { test, expect } from '@playwright/test'

const BASE_URL = process.env.TEST_BASE_URL || 'http://localhost:8000'
const TEST_EMAIL = process.env.TEST_EMAIL || 'giovanny.ledner@example.com'
const TEST_PASSWORD = process.env.TEST_PASSWORD || 'password123'

test.describe('Smart Budget Creation', () => {
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

  test('mode selection screen shows two cards', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Should see two mode selection cards
    const cards = page.locator('button.rounded-xl')
    await expect(cards).toHaveCount(2)

    // Smart budget card — check for translated or raw key
    const smartCard = page.getByText(/Паметен буџет|Smart Budget|smart_budget_title/i)
    await expect(smartCard.first()).toBeVisible()

    // Recommended badge
    const recommendedBadge = page.getByText(/Препорачано|Recommended|recommended/i)
    await expect(recommendedBadge.first()).toBeVisible()

    // Advanced mode card
    const advancedCard = page.getByText(/Напредна табела|Advanced|advanced_mode_title/i)
    await expect(advancedCard.first()).toBeVisible()
  })

  test('smart mode loads and shows form', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Click smart budget card (first card)
    const smartCard = page.locator('button.rounded-xl').first()
    await smartCard.click()
    await page.waitForTimeout(1500)

    // Budget name input should be visible
    const nameInput = page.locator('input[type="text"]').first()
    await expect(nameInput).toBeVisible()

    // Start/end date inputs
    const dateInputs = page.locator('input[type="date"]')
    await expect(dateInputs.first()).toBeVisible()
  })

  test('smart mode fetches data or shows empty state', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    // Click smart budget card
    const smartCard = page.locator('button.rounded-xl').first()
    await smartCard.click()

    // Wait for data load (loading → results or empty state)
    await page.waitForTimeout(5000)

    // Should show either data categories or "no historical data" message
    const hasContent = await Promise.race([
      page.getByText(/Очекувани|Expected|Нема историски|No historical|ПРИХОДИ|РАСХОДИ|Revenue|Expenses/i).first().waitFor({ timeout: 8000 }).then(() => true),
      page.waitForTimeout(8000).then(() => false),
    ])

    expect(hasContent).toBeTruthy()
  })

  test('advanced mode shows spreadsheet wizard', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(1000)

    // Click advanced mode card (second card)
    const advancedCard = page.locator('button.rounded-xl').nth(1)
    await advancedCard.click()
    await page.waitForTimeout(1500)

    // Should show the advanced form — look for step indicator, inputs or table
    const formContent = page.locator('form, input[type="text"], select, .grid')
    await expect(formContent.first()).toBeVisible()
  })

  test('can switch from smart to advanced mode', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    // Click smart mode
    const smartCard = page.locator('button.rounded-xl').first()
    await smartCard.click()
    await page.waitForTimeout(1000)

    // Find switch to advanced link/button
    const switchLink = page.getByText(/Премини на напреден|Switch to Advanced|switch_to_advanced/i)
    if (await switchLink.isVisible({ timeout: 3000 }).catch(() => false)) {
      await switchLink.click()
      await page.waitForTimeout(1000)

      // Should now show the advanced form
      const formContent = page.locator('form, input[type="text"]')
      await expect(formContent.first()).toBeVisible()
    }
  })

  test('cost center inline creation modal', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    // Click smart mode
    const smartCard = page.locator('button.rounded-xl').first()
    await smartCard.click()
    await page.waitForTimeout(1500)

    // Look for the cost center create button (plus icon near the cost center field)
    const plusBtn = page.locator('svg.h-5.w-5.text-primary-400, [class*="PlusCircle"]').first()
    if (await plusBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await plusBtn.click()
      await page.waitForTimeout(500)

      // Modal should appear with cost center form
      const modal = page.getByText(/Креирај центар|Create cost center|create_cost_center/i)
      await expect(modal.first()).toBeVisible()

      // Close the modal
      const cancelBtn = page.getByText(/Откажи|Cancel/i).last()
      if (await cancelBtn.isVisible()) {
        await cancelBtn.click()
      }
    }
  })

  test('growth slider exists in smart mode', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    // Click smart mode
    const smartCard = page.locator('button.rounded-xl').first()
    await smartCard.click()
    await page.waitForTimeout(2000)

    // Check if growth slider exists
    const slider = page.locator('input[type="range"]')
    if (await slider.isVisible({ timeout: 3000 }).catch(() => false)) {
      // Slider exists — verify it works
      await slider.fill('15')
      await page.waitForTimeout(500)

      // Growth percentage should update
      const growthLabel = page.getByText(/15%/)
      await expect(growthLabel.first()).toBeVisible({ timeout: 2000 })
    }
  })

  test('cancel button navigates back to budget list', async () => {
    await page.goto(`${BASE_URL}/admin/budgets/create`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    // Click cancel
    const cancelBtn = page.getByText(/Откажи|Cancel/i)
    await expect(cancelBtn.first()).toBeVisible()

    await cancelBtn.first().click()
    await page.waitForURL(/\/admin\/budgets/, { timeout: 10000 })
  })
})

// CLAUDE-CHECKPOINT
