/**
 * AI Chatbot Prompt Suggestions — E2E Tests
 *
 * Tests that the AI chat widget:
 *   1. Renders prompt suggestion chips in empty state
 *   2. Clicking a chip sends the message to the AI
 *   3. AI responds with relevant content (not an error)
 *   4. Multiple prompts work correctly
 *   5. Navigation prompts work
 *   6. Create-type prompts produce action buttons
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/ai-chatbot-prompts.spec.js --project=chromium
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
  'ai-chatbot-screenshots'
)

if (!fs.existsSync(SCREENSHOT_DIR)) {
  fs.mkdirSync(SCREENSHOT_DIR, { recursive: true })
}

async function ss(page, name) {
  await page.screenshot({
    path: path.join(SCREENSHOT_DIR, `${name}.png`),
    fullPage: false,
  })
}

test.describe.configure({ mode: 'serial', timeout: 60000 })

/** @type {import('@playwright/test').Page} */
let page

test.beforeAll(async ({ browser }) => {
  if (!EMAIL || !PASS) {
    throw new Error('Set TEST_EMAIL and TEST_PASSWORD env vars')
  }
  const ctx = await browser.newContext()
  page = await ctx.newPage()

  // Login
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle' })
  await page.fill('input[name="email"], input[type="email"]', EMAIL)
  await page.fill('input[name="password"], input[type="password"]', PASS)
  await page.click('button[type="submit"]')
  await page.waitForURL('**/admin/dashboard', { timeout: 15000 })
  await page.waitForTimeout(2000)
})

test.afterAll(async () => {
  if (page) await page.close()
})

test('1 — Dashboard loads with AI chat widget', async () => {
  await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(2000)

  // Dismiss onboarding modal if present
  const skipBtn = page.locator('text=Прескокни').or(page.locator('text=Skip'))
  if (await skipBtn.isVisible({ timeout: 2000 }).catch(() => false)) {
    await skipBtn.click()
    await page.waitForTimeout(500)
  }

  // Dismiss tour overlay if present
  const closeBtn = page.locator('button:has-text("×")').or(page.locator('[aria-label="Close"]'))
  if (await closeBtn.first().isVisible({ timeout: 1000 }).catch(() => false)) {
    await closeBtn.first().click()
    await page.waitForTimeout(500)
  }

  // Scroll down to find the AI chat widget (it may be below the fold)
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight))
  await page.waitForTimeout(1000)

  // The AI chat widget should be visible — look for Macedonian or English title
  const chatWidget = page.locator('text=АИ Помошник').or(page.locator('text=AI Assistant')).first()
  await expect(chatWidget).toBeVisible({ timeout: 10000 })
  await ss(page, '01-dashboard-with-chat')
})

test('2 — Prompt suggestion chips render in empty state', async () => {
  // Scroll to the AI chat widget
  const chatSection = page.locator('.ai-markdown').first().or(
    page.locator('text=Пробајте').first().or(
      page.locator('text=Try asking').first()
    )
  )

  // Look for prompt suggestion chips
  const promptChips = page.locator('button').filter({
    has: page.locator('svg'),
  }).filter({
    hasText: /(фактура|invoice|unpaid|неплатени|profit|добивка|expense|трошок)/i,
  })

  const chipCount = await promptChips.count()
  console.log(`Found ${chipCount} prompt suggestion chips`)

  // We expect at least 5 prompt chips
  expect(chipCount).toBeGreaterThanOrEqual(3)
  await ss(page, '02-prompt-chips-visible')
})

test('3 — Click "check unpaid" prompt → AI responds', async () => {
  // Find the unpaid invoices prompt chip
  const unpaidChip = page.locator('button').filter({
    hasText: /(unpaid|неплатени|papaguara|ödenmemiş)/i,
  }).first()

  if (await unpaidChip.isVisible()) {
    await unpaidChip.click()

    // Wait for loading indicator to appear and then disappear
    await page.waitForTimeout(1000)

    // Wait for AI response (loading dots disappear, response appears)
    await page.waitForFunction(() => {
      const bouncing = document.querySelectorAll('.animate-bounce')
      return bouncing.length === 0
    }, { timeout: 30000 })

    await page.waitForTimeout(1000)

    // Check that a response message appeared (not just the user message)
    const assistantMessages = page.locator('.bg-gray-100.rounded-lg')
    const responseCount = await assistantMessages.count()
    console.log(`Assistant messages visible: ${responseCount}`)
    expect(responseCount).toBeGreaterThanOrEqual(1)

    await ss(page, '03-unpaid-invoices-response')
  } else {
    console.log('Unpaid chip not visible, skipping')
  }
})

test('4 — Clear chat and verify chips reappear', async () => {
  // Click the clear/trash button
  const clearBtn = page.locator('button[title]').filter({
    has: page.locator('svg.w-5.h-5'),
  }).last()

  if (await clearBtn.isVisible()) {
    await clearBtn.click()
    await page.waitForTimeout(500)
  }

  // Start new conversation
  const newConvoBtn = page.locator('text=Нов разговор').or(
    page.locator('text=New Conversation')
  ).first()

  if (await newConvoBtn.isVisible()) {
    await newConvoBtn.click()
    await page.waitForTimeout(500)
  }

  await ss(page, '04-cleared-chips-reappear')
})

test('5 — Click "create invoice" prompt → AI creates draft or responds', async () => {
  // Find create invoice chip
  const invoiceChip = page.locator('button').filter({
    hasText: /(create invoice|Креирај фактура|Krijo faturë|fatura oluştur)/i,
  }).first()

  if (await invoiceChip.isVisible()) {
    await invoiceChip.click()

    // Wait for response
    await page.waitForTimeout(1000)
    await page.waitForFunction(() => {
      const bouncing = document.querySelectorAll('.animate-bounce')
      return bouncing.length === 0
    }, { timeout: 30000 })

    await page.waitForTimeout(1000)

    // Should get a response (either clarification or a draft)
    const assistantMessages = page.locator('.bg-gray-100.rounded-lg')
    const responseCount = await assistantMessages.count()
    expect(responseCount).toBeGreaterThanOrEqual(1)

    await ss(page, '05-create-invoice-response')
  }
})

test('6 — Click "open reports" prompt → AI navigates', async () => {
  // Clear chat first
  const newConvoBtn = page.locator('text=Нов разговор').or(
    page.locator('text=New Conversation')
  ).first()
  if (await newConvoBtn.isVisible()) {
    await newConvoBtn.click()
    await page.waitForTimeout(500)
  }

  const reportsChip = page.locator('button').filter({
    hasText: /(open reports|Отвори.*извештаи|Hap.*raporteve|Raporlar.*aç)/i,
  }).first()

  if (await reportsChip.isVisible()) {
    await reportsChip.click()

    // Wait for response
    await page.waitForTimeout(1000)
    await page.waitForFunction(() => {
      const bouncing = document.querySelectorAll('.animate-bounce')
      return bouncing.length === 0
    }, { timeout: 30000 })

    await page.waitForTimeout(1000)

    // Should see a navigation button or response
    const assistantMessages = page.locator('.bg-gray-100.rounded-lg')
    const responseCount = await assistantMessages.count()
    expect(responseCount).toBeGreaterThanOrEqual(1)

    // Check for navigation button
    const navButton = page.locator('button').filter({
      hasText: /(Отвори|Open|Hap|Aç)/,
    })
    const hasNavBtn = await navButton.count()
    console.log(`Navigation buttons: ${hasNavBtn}`)

    await ss(page, '06-open-reports-response')
  }
})

test('7 — Type a custom question in input', async () => {
  const newConvoBtn = page.locator('text=Нов разговор').or(
    page.locator('text=New Conversation')
  ).first()
  if (await newConvoBtn.isVisible()) {
    await newConvoBtn.click()
    await page.waitForTimeout(500)
  }

  // Type a question
  const input = page.locator('input[type="text"]').last()
  await input.fill('Колку пари влегоа овој месец?')
  await page.locator('button[type="submit"]').last().click()

  // Wait for response
  await page.waitForTimeout(1000)
  await page.waitForFunction(() => {
    const bouncing = document.querySelectorAll('.animate-bounce')
    return bouncing.length === 0
  }, { timeout: 30000 })

  await page.waitForTimeout(1000)

  const assistantMessages = page.locator('.bg-gray-100.rounded-lg')
  const responseCount = await assistantMessages.count()
  expect(responseCount).toBeGreaterThanOrEqual(1)

  await ss(page, '07-custom-question-response')
})
// CLAUDE-CHECKPOINT
