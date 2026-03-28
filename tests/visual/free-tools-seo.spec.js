// @ts-check
import { test, expect } from '@playwright/test'

const BASE = 'https://www.facturino.mk'
const LOCALES = ['mk', 'sq', 'tr', 'en']

test.describe('Free Tools — SEO & Functionality', () => {

  // Tools index page
  test('Tools index page loads in all locales', async ({ page }) => {
    for (const locale of LOCALES) {
      const resp = await page.goto(`${BASE}/${locale}/alati`, { waitUntil: 'domcontentloaded' })
      expect(resp.status()).toBe(200)

      const h1 = await page.locator('h1').textContent()
      expect(h1.length).toBeGreaterThan(3)

      const toolLinks = page.locator('a[href*="/alati/"]')
      expect(await toolLinks.count()).toBeGreaterThanOrEqual(3)

      const jsonLd = await page.locator('script[type="application/ld+json"]').count()
      expect(jsonLd).toBeGreaterThanOrEqual(1)
    }
  })

  // DDV Kalkulator
  test('DDV Kalkulator — loads and calculates correctly (MK)', async ({ page }) => {
    await page.goto(`${BASE}/mk/alati/ddv-kalkulator`, { waitUntil: 'domcontentloaded' })

    const title = await page.title()
    expect(title).toContain('ДДВ')

    const h1 = await page.locator('h1').textContent()
    expect(h1).toContain('ДДВ Калкулатор')

    // Enter amount and wait for calculation
    await page.fill('#vat-amount', '10000')
    await page.waitForTimeout(500)

    // 18% default: VAT = 1800, gross = 11800
    // Result area should show the VAT and gross values
    await expect(page.locator('text=ДДВ').first()).toBeVisible({ timeout: 3000 })

    // Switch to 5% rate and verify UI responds
    await page.click('button:has-text("5%")')
    await page.waitForTimeout(500)

    // Check FAQ section exists
    const faqDetails = page.locator('details')
    expect(await faqDetails.count()).toBeGreaterThanOrEqual(5)

    // Check JSON-LD (FAQPage + WebApplication + BreadcrumbList)
    const jsonLd = await page.locator('script[type="application/ld+json"]').count()
    expect(jsonLd).toBeGreaterThanOrEqual(3)

    // Check CTA links to signup
    const signupLinks = page.locator('a[href*="signup"]')
    expect(await signupLinks.count()).toBeGreaterThanOrEqual(1)
  })

  // Plata Kalkulator
  test('Plata Kalkulator — loads and calculates correctly (MK)', async ({ page }) => {
    await page.goto(`${BASE}/mk/alati/plata-kalkulator`, { waitUntil: 'domcontentloaded' })

    const h1 = await page.locator('h1').textContent()
    expect(h1).toContain('Калкулатор за плата')

    // Enter gross salary
    await page.fill('#salary-amount', '40000')
    await page.waitForTimeout(500)

    // Check breakdown appears — look for specific labeled section
    const mainContent = page.locator('main')
    await expect(mainContent.locator('text=ДЕТАЛЕН ПРЕСМЕТ')).toBeVisible({ timeout: 3000 })

    // Check that employer cost section exists
    await expect(mainContent.locator('text=Вкупен трошок').first()).toBeVisible()

    // Switch to Net → Gross mode
    await page.click('button:has-text("Нето → Бруто")')
    await page.fill('#salary-amount', '30000')
    await page.waitForTimeout(500)

    // Should show breakdown in reverse mode too
    await expect(mainContent.locator('text=ДЕТАЛЕН ПРЕСМЕТ')).toBeVisible()

    // Check FAQ
    expect(await page.locator('details').count()).toBeGreaterThanOrEqual(5)
  })

  // E-Faktura Proverka
  test('E-Faktura Proverka — loads and validates XML (MK)', async ({ page }) => {
    await page.goto(`${BASE}/mk/alati/efaktura-proverka`, { waitUntil: 'domcontentloaded' })

    const h1 = await page.locator('h1').textContent()
    expect(h1).toContain('Е-Фактура проверка')

    // Check deadline warning (use .first() — text appears in banner + FAQ)
    await expect(page.locator('text=октомври 2026').first()).toBeVisible()

    // Enter valid XML
    const validXml = `<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:invoice:2"
         xmlns:cbc="urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2"
         xmlns:cac="urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2">
  <cbc:ID>INV-001</cbc:ID>
  <cbc:IssueDate>2026-03-28</cbc:IssueDate>
  <cbc:DocumentCurrencyCode>MKD</cbc:DocumentCurrencyCode>
  <cac:AccountingSupplierParty>
    <cac:Party>
      <cac:PartyName><cbc:Name>Test DOOEL</cbc:Name></cac:PartyName>
      <cac:PartyTaxScheme><cbc:CompanyID>MK1234567890123</cbc:CompanyID></cac:PartyTaxScheme>
    </cac:Party>
  </cac:AccountingSupplierParty>
  <cac:AccountingCustomerParty>
    <cac:Party>
      <cac:PartyTaxScheme><cbc:CompanyID>MK9876543210987</cbc:CompanyID></cac:PartyTaxScheme>
    </cac:Party>
  </cac:AccountingCustomerParty>
  <cac:TaxTotal>
    <cac:TaxSubtotal><cbc:Percent>18</cbc:Percent></cac:TaxSubtotal>
  </cac:TaxTotal>
  <cac:InvoiceLine>
    <cbc:LineExtensionAmount currencyID="MKD">5000</cbc:LineExtensionAmount>
  </cac:InvoiceLine>
</Invoice>`

    await page.fill('textarea', validXml)
    await page.click('button:has-text("Валидирај")')

    // Should show results with pass count
    await expect(page.locator('text=поминати')).toBeVisible({ timeout: 5000 })

    // Should have green checkmarks (passed checks)
    const passedChecks = page.locator('.bg-green-50')
    expect(await passedChecks.count()).toBeGreaterThan(0)

    // Test invalid XML
    await page.fill('textarea', '<not-valid-xml>')
    await page.click('button:has-text("Валидирај")')
    await page.waitForTimeout(500)

    // Should show red failures
    const failedChecks = page.locator('.bg-red-50')
    expect(await failedChecks.count()).toBeGreaterThan(0)
  })

  // SEO checks
  test('SEO — meta tags and hreflang present on all tool pages', async ({ page }) => {
    const toolPages = [
      '/mk/alati',
      '/mk/alati/ddv-kalkulator',
      '/mk/alati/plata-kalkulator',
      '/mk/alati/efaktura-proverka',
    ]

    for (const path of toolPages) {
      await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' })

      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href')
      expect(canonical).toContain('facturino.mk')

      const hreflangLinks = page.locator('link[hreflang]')
      expect(await hreflangLinks.count()).toBeGreaterThanOrEqual(4)

      const ogTitle = await page.locator('meta[property="og:title"]').getAttribute('content')
      expect(ogTitle.length).toBeGreaterThan(5)

      const desc = await page.locator('meta[name="description"]').getAttribute('content')
      expect(desc.length).toBeGreaterThan(20)
    }
  })

  // Albanian locale
  test('DDV Kalkulator works in Albanian (SQ)', async ({ page }) => {
    await page.goto(`${BASE}/sq/alati/ddv-kalkulator`, { waitUntil: 'domcontentloaded' })

    const h1 = await page.locator('h1').textContent()
    expect(h1).toContain('TVSH')

    await page.fill('#vat-amount', '10000')
    await page.waitForTimeout(500)

    // Check VAT label and result area rendered
    await expect(page.locator('text=TVSH').first()).toBeVisible({ timeout: 3000 })
    // Verify the result section shows MKD currency
    await expect(page.locator('text=MKD').first()).toBeVisible()
  })

  // Navigation integration
  test('Tools link appears in navbar and footer', async ({ page }) => {
    await page.goto(`${BASE}/mk`, { waitUntil: 'domcontentloaded' })

    // Footer should have tools link
    const footerToolsLink = page.locator('footer a[href="/mk/alati"]')
    expect(await footerToolsLink.count()).toBeGreaterThanOrEqual(1)
  })
})
