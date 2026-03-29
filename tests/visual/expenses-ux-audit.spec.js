/**
 * Expenses UX Audit — Screenshot every page state for review
 */
import { test, expect } from '@playwright/test'
import fs from 'fs'
import path from 'path'

const BASE = process.env.TEST_BASE_URL || 'https://app.facturino.mk'
const EMAIL = process.env.TEST_EMAIL || ''
const PASS = process.env.TEST_PASSWORD || ''
const DIR = path.join(process.cwd(), 'test-results', 'expenses-ux-audit')

if (!fs.existsSync(DIR)) fs.mkdirSync(DIR, { recursive: true })

async function ss(page, name) {
  await page.screenshot({ path: path.join(DIR, `${name}.png`), fullPage: true })
}

test.describe.configure({ mode: 'serial', timeout: 60000 })

let page

test.beforeAll(async ({ browser }) => {
  test.setTimeout(120000)
  if (!EMAIL || !PASS) throw new Error('Set TEST_EMAIL and TEST_PASSWORD')
  page = await browser.newPage({ viewport: { width: 1440, height: 900 } })
  await new Promise(r => setTimeout(r, 8000))
  await page.goto(`${BASE}/login`, { timeout: 60000, waitUntil: 'domcontentloaded' })
  await page.waitForSelector('input[name="email"], input[type="email"]', { timeout: 30000 })
  await page.fill('input[name="email"], input[type="email"]', EMAIL)
  await page.fill('input[name="password"], input[type="password"]', PASS)
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/admin/, { timeout: 30000 })
  await page.waitForTimeout(2000)
})

test.afterAll(async () => { await page?.close() })

test('01 — Index page full view', async () => {
  await page.goto(`${BASE}/admin/expenses`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)
  await ss(page, '01-index-full')
})

test('02 — Index with filters open', async () => {
  const filterBtn = page.locator('button', { hasText: /filter|филтер/i }).first()
  if (await filterBtn.isVisible()) {
    await filterBtn.click()
    await page.waitForTimeout(500)
  }
  await ss(page, '02-index-filters-open')
})

test('03 — Index dropdown menu', async () => {
  // Close filters first
  const filterBtn = page.locator('button', { hasText: /filter|филтер/i }).first()
  if (await filterBtn.isVisible()) await filterBtn.click()
  await page.waitForTimeout(300)

  const ellipsis = page.locator('table svg').last()
  if (await ellipsis.isVisible()) {
    await ellipsis.click()
    await page.waitForTimeout(500)
  }
  await ss(page, '03-index-dropdown')
  await page.locator('body').click()
  await page.waitForTimeout(200)
})

test('04 — Create page full view', async () => {
  await page.goto(`${BASE}/admin/expenses/create`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(3000)
  await ss(page, '04-create-full')
})

test('05 — Create page scrolled down', async () => {
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight))
  await page.waitForTimeout(500)
  await ss(page, '05-create-scrolled')
})

test('06 — View page', async () => {
  // Find first expense
  const resp = await page.evaluate(async (base) => {
    const r = await fetch(`${base}/api/v1/expenses?page=1&limit=1`, {
      headers: { Accept: 'application/json', company: '2' },
      credentials: 'same-origin',
    })
    return r.json()
  }, BASE)
  const id = resp?.data?.[0]?.id
  if (id) {
    await page.goto(`${BASE}/admin/expenses/${id}/view`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(2000)
    await ss(page, '06-view-page')
  }
})

test('07 — Edit page', async () => {
  const resp = await page.evaluate(async (base) => {
    const r = await fetch(`${base}/api/v1/expenses?page=1&limit=1`, {
      headers: { Accept: 'application/json', company: '2' },
      credentials: 'same-origin',
    })
    return r.json()
  }, BASE)
  const id = resp?.data?.[0]?.id
  if (id) {
    await page.goto(`${BASE}/admin/expenses/${id}/edit`)
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(3000)
    await ss(page, '07-edit-page')
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight))
    await page.waitForTimeout(300)
    await ss(page, '08-edit-scrolled')
  }
})

test('08 — Mobile viewport index', async () => {
  await page.setViewportSize({ width: 375, height: 812 })
  await page.goto(`${BASE}/admin/expenses`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(2000)
  await ss(page, '09-mobile-index')
})

test('09 — Mobile viewport create', async () => {
  await page.goto(`${BASE}/admin/expenses/create`)
  await page.waitForLoadState('networkidle')
  await page.waitForTimeout(3000)
  await ss(page, '10-mobile-create')
  await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight))
  await page.waitForTimeout(300)
  await ss(page, '11-mobile-create-scrolled')
  // Reset viewport
  await page.setViewportSize({ width: 1440, height: 900 })
})
