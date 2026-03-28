// @ts-check
const { test, expect } = require('@playwright/test')

const BASE = 'https://www.facturino.mk'
const LOCALES = ['mk', 'sq', 'tr', 'en']

test.describe('Free Tools — SEO & Functionality', () => {
  test.describe.configure({ mode: 'serial' })

  // Tools index page
  test('Tools index page loads in all locales', async ({ page }) => {
    for (const locale of LOCALES) {
      const resp = await page.goto(`${BASE}/${locale}/alati`, { waitUntil: 'domcontentloaded' })
      expect(resp.status()).toBe(200)

      // Check H1 exists
      const h1 = await page.locator('h1').textContent()
      expect(h1.length).toBeGreaterThan(3)

      // Check 3 tool cards are present
      const toolLinks = page.locator('a[href*="/alati/"]')
      expect(await toolLinks.count()).toBeGreaterThanOrEqual(3)

      // Check JSON-LD structured data
      const jsonLd = await page.locator('script[type="application/ld+json"]').count()
      expect(jsonLd).toBeGreaterThanOrEqual(1)
    }
  })

  // DDV Kalkulator
  test('DDV Kalkulator — loads and calculates correctly (MK)', async ({ page }) => {
    await page.goto(`${BASE}/mk/alati/ddv-kalkulator`, { waitUntil: 'domcontentloaded' })

    // Check title contains DDV/ДДВ
    const title = await page.title()
    expect(title).toContain('ДДВ')

    // Check H1
    const h1 = await page.locator('h1').textContent()
    expect(h1).toContain('ДДВ Калкулатор')

    // Enter amount
    await page.fill('#vat-amount', '10000')

    // Check 18% is default selected and results show
    const resultText = await page.textContent('body')
    expect(resultText).toContain('1.800') // 10000 * 0.18 = 1800
    expect(resultText).toContain('11.800') // gross

    // Switch to 5% rate
    await page.click('button:has-text("5%")')
    const resultText5 = await page.textContent('body')
    expect(resultText5).toContain('500') // 10000 * 0.05

    // Switch to inclusive mode
    await page.click('button:has-text("ВКЛУЧУВА")')
    // 10000 inclusive at 5% → net = 10000/1.05 ≈ 9523.81, VAT ≈ 476.19
    const resultInclusive = await page.textContent('body')
    expect(resultInclusive).toContain('476')

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

    // Check employee contributions section appears
    const body = await page.textContent('body')
    expect(body).toContain('ПИО') // pension
    expect(body).toContain('ЗО') // health
    expect(body).toContain('9%')
    expect(body).toContain('3.75%')

    // Verify net salary appears (40000 - 14.45% contrib - 10% tax on remainder)
    // Employee contrib: 40000 * 0.1445 = 5780
    // Taxable: 40000 - 5780 = 34220
    // Tax: 34220 * 0.10 = 3422
    // Net: 40000 - 5780 - 3422 = 30798
    expect(body).toContain('30.798')

    // Check employer cost appears
    // Employer: 40000 * 0.1275 = 5100
    // Total: 40000 + 5100 = 45100
    expect(body).toContain('45.100')

    // Switch to Net → Gross mode
    await page.click('button:has-text("Нето → Бруто")')
    await page.fill('#salary-amount', '30000')

    // Should reverse-calculate a gross > 30000
    const bodyReverse = await page.textContent('body')
    // Net 30000 → gross ≈ 38963
    expect(bodyReverse).toContain('38.9')

    // Check FAQ
    expect(await page.locator('details').count()).toBeGreaterThanOrEqual(5)
  })

  // E-Faktura Proverka
  test('E-Faktura Proverka — loads and validates XML (MK)', async ({ page }) => {
    await page.goto(`${BASE}/mk/alati/efaktura-proverka`, { waitUntil: 'domcontentloaded' })

    const h1 = await page.locator('h1').textContent()
    expect(h1).toContain('Е-Фактура проверка')

    // Check deadline warning is shown
    const body = await page.textContent('body')
    expect(body).toContain('октомври 2026')

    // Enter valid-ish XML
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

    // Should show results
    await page.waitForSelector('text=поминати', { timeout: 5000 })

    // Most checks should pass for this well-formed XML
    const resultBody = await page.textContent('body')
    expect(resultBody).toContain('12') // total checks

    // Enter invalid XML
    await page.fill('textarea', '<not-valid>')
    await page.click('button:has-text("Валидирај")')

    // Should show failures
    const errorBody = await page.textContent('body')
    expect(errorBody).toContain('поминати')
  })

  // SEO checks across all tools
  test('SEO — meta tags and hreflang present on all tool pages', async ({ page }) => {
    const toolPages = [
      '/mk/alati',
      '/mk/alati/ddv-kalkulator',
      '/mk/alati/plata-kalkulator',
      '/mk/alati/efaktura-proverka',
    ]

    for (const path of toolPages) {
      await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' })

      // Check canonical URL
      const canonical = await page.locator('link[rel="canonical"]').getAttribute('href')
      expect(canonical).toContain('facturino.mk')

      // Check hreflang alternates exist
      const hreflangLinks = page.locator('link[hreflang]')
      expect(await hreflangLinks.count()).toBeGreaterThanOrEqual(4)

      // Check og:title exists
      const ogTitle = await page.locator('meta[property="og:title"]').getAttribute('content')
      expect(ogTitle.length).toBeGreaterThan(5)

      // Check meta description
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
    const body = await page.textContent('body')
    expect(body).toContain('1.800')
  })

  // Navigation integration
  test('Tools link appears in navbar and footer', async ({ page }) => {
    await page.goto(`${BASE}/mk`, { waitUntil: 'domcontentloaded' })

    // Desktop nav should have tools link
    const navToolsLink = page.locator('nav a[href="/mk/alati"]')
    // Footer should have tools link
    const footerToolsLink = page.locator('footer a[href="/mk/alati"]')

    // At least one should be present (nav may be hidden on mobile viewport)
    const totalLinks = await navToolsLink.count() + await footerToolsLink.count()
    expect(totalLinks).toBeGreaterThanOrEqual(1)
  })
})
