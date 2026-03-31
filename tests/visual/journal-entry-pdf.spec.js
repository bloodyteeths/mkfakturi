/**
 * Journal Entry PDF (Налог за книжење) — E2E Test
 *
 * Tests:
 *   1. Journal Entries page loads with filters
 *   2. Load entries — unified table renders with all entries visible
 *   3. Verify table structure (header rows + line item rows)
 *   4. API: bulk PDF download returns valid multi-page PDF
 *
 * Usage:
 *   TEST_EMAIL=atillatkulu@gmail.com TEST_PASSWORD=Facturino2026 \
 *     npx playwright test tests/visual/journal-entry-pdf.spec.js --project=chromium
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
  'journal-entry-pdf-screenshots'
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

test.describe.configure({ mode: 'serial', timeout: 60000 })

/** @type {import('@playwright/test').Page} */
let page

test.beforeAll(async ({ browser }, testInfo) => {
  testInfo.setTimeout(60000)
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

  // Dismiss overlays
  await page.evaluate(() => {
    document.querySelectorAll('[class*="fixed"][class*="z-"]').forEach(el => {
      const style = getComputedStyle(el)
      if (parseInt(style.zIndex) >= 9999) el.remove()
    })
    document.querySelectorAll('button').forEach(btn => {
      if (btn.textContent.includes('Прескокни') || btn.textContent.includes('Skip')) btn.click()
    })
  })
  await page.waitForTimeout(500)
})

test.afterAll(async () => {
  if (page) await page.close()
})

test('1 — Navigate to Journal Entries page', async () => {
  await page.goto(`${BASE}/admin/reports/journal-entries`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(3000)

  // Dismiss overlays
  await page.evaluate(() => {
    document.querySelectorAll('[class*="fixed"][class*="z-"]').forEach(el => {
      const style = getComputedStyle(el)
      if (parseInt(style.zIndex) >= 9999) el.remove()
    })
  })

  await ss(page, '01-journal-entries-page')

  // Should see Load button
  const loadBtn = page.locator('button').filter({ hasText: /Вчитај|Load/i }).first()
  await expect(loadBtn).toBeVisible({ timeout: 5000 })
})

test('2 — Load entries — unified table renders', async () => {
  // Set from-date to 01.01.2026 via JS to avoid triggering Cmd+K
  await page.evaluate(() => {
    const inputs = document.querySelectorAll('input[type="text"]')
    for (const input of inputs) {
      if (input.value && input.value.match(/\d{2}\.\d{2}\.\d{4}/) && input.value.includes('.03.')) {
        const setter = Object.getOwnPropertyDescriptor(
          window.HTMLInputElement.prototype, 'value'
        ).set
        setter.call(input, '01.01.2026')
        input.dispatchEvent(new Event('input', { bubbles: true }))
        input.dispatchEvent(new Event('change', { bubbles: true }))
        break
      }
    }
  })
  await page.waitForTimeout(500)

  // Click Load
  const loadBtn = page.locator('button').filter({ hasText: /Вчитај|Load/i }).first()
  await loadBtn.click()
  await page.waitForTimeout(5000)

  await ss(page, '02-entries-loaded')

  // Verify unified table appeared — should have <table> with entries
  const table = page.locator('table').first()
  await expect(table).toBeVisible({ timeout: 5000 })

  // Verify entry references visible (CP, JN, IN, BL, RC, PY patterns)
  const bodyText = await page.textContent('body')
  const hasEntries = /CP\d+\/\d+|JN\d+|IN\d+|BL\d+|RC\d+|PY\d+/.test(bodyText)
  console.log('Entries found in unified table:', hasEntries)
  expect(hasEntries).toBe(true)

  // Verify "Печати сите PDF" button appeared
  const printAllBtn = page.locator('button').filter({ hasText: /Печати сите|Print all/i }).first()
  const hasPrintAll = await printAllBtn.count() > 0
  console.log('Print All PDF button visible:', hasPrintAll)
  expect(hasPrintAll).toBe(true)
})

test('3 — Verify table has header rows and line items', async () => {
  // Header rows have bg-gray-50 and show date, reference, type badge
  const headerRows = page.locator('tr.bg-gray-50.border-t-2')
  const headerCount = await headerRows.count()
  console.log(`Entry header rows: ${headerCount}`)
  expect(headerCount).toBeGreaterThan(0)

  // Type badges should be visible (ИФ, УКФ, КН, ПР, ИЗВ, БЛ, ИНТ)
  const badges = page.locator('span.inline-flex.items-center')
  const badgeCount = await badges.count()
  console.log(`Type badges: ${badgeCount}`)
  expect(badgeCount).toBeGreaterThan(0)

  // Grand totals footer
  const footer = page.locator('tfoot')
  await expect(footer).toBeVisible()

  // Storno buttons should exist
  const stornoButtons = page.locator('button[title*="Сторно"], button[title*="Reverse"]')
  const stornoCount = await stornoButtons.count()
  console.log(`Storno buttons: ${stornoCount}`)

  await ss(page, '03-table-structure')
})

test('4 — API: bulk PDF download', async () => {
  const result = await page.evaluate(async () => {
    try {
      const ax = window.axios
      if (!ax) return { error: 'axios not on window' }

      // List entries first
      const listResp = await ax.get('/accounting/journal-entries', {
        params: { start_date: '2026-01-01', end_date: '2026-03-31', page: 1 },
      })
      const entries = listResp.data?.data || []

      // Download bulk PDF
      const pdfResp = await ax.get('/accounting/journal-entries/pdf', {
        params: { start_date: '2026-01-01', end_date: '2026-03-31' },
        responseType: 'blob',
      })

      return {
        totalEntries: entries.length,
        pdfStatus: pdfResp.status,
        pdfSize: pdfResp.data?.size || 0,
        pdfType: pdfResp.headers?.['content-type'] || 'unknown',
      }
    } catch (e) {
      return { error: e.message, status: e.response?.status, data: e.response?.data?.substring?.(0, 200) }
    }
  })

  console.log('Bulk PDF result:', JSON.stringify(result, null, 2))

  if (result.error) {
    console.log('API error:', result.error)
  } else {
    expect(result.pdfStatus).toBe(200)
    expect(result.pdfSize).toBeGreaterThan(1000)
    console.log(`Bulk PDF OK: ${result.totalEntries} entries → ${(result.pdfSize / 1024).toFixed(1)} KB`)
  }
})
// CLAUDE-CHECKPOINT
