'use client'

import { useMemo, useState, useCallback } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

const UBL_NS = 'urn:oasis:names:specification:ubl:invoice:2'
const UBL_NS_ALT = 'urn:oasis:names:specification:ubl:2.1'
const CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2'
const CAC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2'

type Severity = 'error' | 'warning'
type CheckResult = { id: string; label: string; severity: Severity; passed: boolean; detail?: string }

function getTextNS(doc: Document, ns: string, tag: string): string | null {
  const el = doc.getElementsByTagNameNS(ns, tag)[0]
  return el?.textContent?.trim() || null
}

function getCountNS(doc: Document, ns: string, tag: string): number {
  return doc.getElementsByTagNameNS(ns, tag).length
}

// Fallback: try without namespace (some generators don't use NS properly)
function getText(doc: Document, tag: string): string | null {
  return getTextNS(doc, CBC_NS, tag) || doc.getElementsByTagName(`cbc:${tag}`)[0]?.textContent?.trim() || doc.getElementsByTagName(tag)[0]?.textContent?.trim() || null
}

function getCount(doc: Document, tag: string): number {
  const ns = getCountNS(doc, CAC_NS, tag)
  if (ns > 0) return ns
  const prefixed = doc.getElementsByTagName(`cac:${tag}`).length
  if (prefixed > 0) return prefixed
  return doc.getElementsByTagName(tag).length
}

function validateXml(xmlString: string, labels: Record<string, string>): CheckResult[] {
  const results: CheckResult[] = []

  // Check 1: Valid XML
  const parser = new DOMParser()
  const doc = parser.parseFromString(xmlString, 'text/xml')
  const parseError = doc.getElementsByTagName('parsererror')
  if (parseError.length > 0) {
    results.push({ id: 'valid-xml', label: labels.validXml, severity: 'error', passed: false, detail: parseError[0].textContent || '' })
    return results // Can't continue if XML is invalid
  }
  results.push({ id: 'valid-xml', label: labels.validXml, severity: 'error', passed: true })

  // Check 2: Root element is Invoice or CreditNote
  const root = doc.documentElement
  const rootName = root.localName || root.tagName
  const isInvoice = rootName === 'Invoice' || rootName.endsWith(':Invoice')
  const isCreditNote = rootName === 'CreditNote' || rootName.endsWith(':CreditNote')
  results.push({ id: 'root-element', label: labels.rootElement, severity: 'error', passed: isInvoice || isCreditNote, detail: !isInvoice && !isCreditNote ? `Found: <${rootName}>` : undefined })

  // Check 3: Invoice number
  const invoiceId = getText(doc, 'ID')
  results.push({ id: 'invoice-id', label: labels.invoiceId, severity: 'error', passed: !!invoiceId, detail: invoiceId || undefined })

  // Check 4: Issue date
  const issueDate = getText(doc, 'IssueDate')
  const dateValid = issueDate ? /^\d{4}-\d{2}-\d{2}$/.test(issueDate) : false
  results.push({ id: 'issue-date', label: labels.issueDate, severity: 'error', passed: dateValid, detail: issueDate || undefined })

  // Check 5: Supplier Tax ID (EDB)
  const supplierParty = doc.getElementsByTagNameNS(CAC_NS, 'AccountingSupplierParty')[0] || doc.getElementsByTagName('cac:AccountingSupplierParty')[0] || doc.getElementsByTagName('AccountingSupplierParty')[0]
  let supplierEdb = ''
  if (supplierParty) {
    const taxScheme = supplierParty.getElementsByTagNameNS(CBC_NS, 'CompanyID')[0] || supplierParty.getElementsByTagName('cbc:CompanyID')[0] || supplierParty.getElementsByTagName('CompanyID')[0]
    supplierEdb = taxScheme?.textContent?.trim() || ''
  }
  const edbPattern = /^(MK)?\d{7,13}$/
  results.push({ id: 'supplier-edb', label: labels.supplierEdb, severity: 'error', passed: edbPattern.test(supplierEdb), detail: supplierEdb || undefined })

  // Check 6: Buyer Tax ID
  const buyerParty = doc.getElementsByTagNameNS(CAC_NS, 'AccountingCustomerParty')[0] || doc.getElementsByTagName('cac:AccountingCustomerParty')[0] || doc.getElementsByTagName('AccountingCustomerParty')[0]
  let buyerEdb = ''
  if (buyerParty) {
    const taxScheme = buyerParty.getElementsByTagNameNS(CBC_NS, 'CompanyID')[0] || buyerParty.getElementsByTagName('cbc:CompanyID')[0] || buyerParty.getElementsByTagName('CompanyID')[0]
    buyerEdb = taxScheme?.textContent?.trim() || ''
  }
  results.push({ id: 'buyer-edb', label: labels.buyerEdb, severity: 'warning', passed: !!buyerEdb, detail: buyerEdb || undefined })

  // Check 7: At least 1 line item
  const lineCount = getCount(doc, 'InvoiceLine') || getCount(doc, 'CreditNoteLine')
  results.push({ id: 'line-items', label: labels.lineItems, severity: 'error', passed: lineCount > 0, detail: `${lineCount}` })

  // Check 8: Line amounts present
  const lineAmounts = getCountNS(doc, CBC_NS, 'LineExtensionAmount') || doc.getElementsByTagName('cbc:LineExtensionAmount').length || doc.getElementsByTagName('LineExtensionAmount').length
  results.push({ id: 'line-amounts', label: labels.lineAmounts, severity: 'error', passed: lineAmounts > 0 })

  // Check 9: VAT breakdown exists
  const taxSubtotal = getCount(doc, 'TaxSubtotal')
  results.push({ id: 'vat-breakdown', label: labels.vatBreakdown, severity: 'warning', passed: taxSubtotal > 0 })

  // Check 10: VAT rate valid (0, 5, 10, 18)
  const validRates = [0, 5, 10, 18]
  const percentEls = doc.getElementsByTagNameNS(CBC_NS, 'Percent').length > 0
    ? doc.getElementsByTagNameNS(CBC_NS, 'Percent')
    : doc.getElementsByTagName('cbc:Percent').length > 0
      ? doc.getElementsByTagName('cbc:Percent')
      : doc.getElementsByTagName('Percent')
  let allRatesValid = true
  const foundRates: number[] = []
  for (let i = 0; i < percentEls.length; i++) {
    const val = parseFloat(percentEls[i].textContent || '')
    if (!isNaN(val)) {
      foundRates.push(val)
      if (!validRates.includes(val)) allRatesValid = false
    }
  }
  results.push({ id: 'vat-rates', label: labels.vatRates, severity: 'warning', passed: allRatesValid, detail: foundRates.length > 0 ? foundRates.join('%, ') + '%' : undefined })

  // Check 11: Currency is MKD
  const currency = getText(doc, 'DocumentCurrencyCode')
  results.push({ id: 'currency', label: labels.currency, severity: 'warning', passed: currency === 'MKD', detail: currency || undefined })

  // Check 12: Supplier name present
  let supplierName = ''
  if (supplierParty) {
    const nameEl = supplierParty.getElementsByTagNameNS(CBC_NS, 'Name')[0] || supplierParty.getElementsByTagName('cbc:Name')[0] || supplierParty.getElementsByTagName('Name')[0]
    supplierName = nameEl?.textContent?.trim() || ''
  }
  results.push({ id: 'supplier-name', label: labels.supplierName, severity: 'warning', passed: !!supplierName, detail: supplierName || undefined })

  return results
}

const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'Е-Фактура проверка',
    subtitle: 'Валидирајте го вашиот UBL XML пред да го испратите до УЈП. 12 проверки за комплетност и усогласеност со македонскиот стандард.',
    tabPaste: 'Залепи XML',
    tabUpload: 'Прикачи датотека',
    placeholder: '<?xml version="1.0" encoding="UTF-8"?>\n<Invoice xmlns="urn:oasis:names:specification:ubl:invoice:2">\n  ...\n</Invoice>',
    uploadLabel: 'Повлечете .xml датотека тука или кликнете за избор',
    uploadHint: 'Само .xml датотеки, макс. 5MB',
    validateBtn: 'Валидирај',
    resultTitle: 'Резултати од проверка',
    passed: 'поминати',
    of: 'од',
    checks: 'про��ерки',
    labels: {
      validXml: 'Валиден XML формат',
      rootElement: 'Коренски елемент (Invoice/CreditNote)',
      invoiceId: 'Број на фактура (cbc:ID)',
      issueDate: 'Датум на издавање (cbc:IssueDate)',
      supplierEdb: 'ЕДБ на издавач (MK + 7-13 цифри)',
      buyerEdb: 'ЕДБ на примач',
      lineItems: 'Барем 1 ставка (InvoiceLine)',
      lineAmounts: 'Износи на ставки (LineExtensionAmount)',
      vatBreakdown: 'ДДВ пресмет (TaxSubtotal)',
      vatRates: 'Валидни ДДВ стапки (0/5/10/18%)',
      currency: 'Валута = MKD',
      supplierName: 'Име на издавач',
    },
    deadline: 'Од 1 октомври 2026, е-фактура е задолжителна за сите бизниси во Македонија.',
    ctaInline: 'Facturino автоматски генерира валиден UBL XML за секоја фактур��.',
    ctaButton: 'Пробај бесплатно',
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Што е е-фактура и зошто е задолжителна?',
        a: 'Е-фактура е електронска фактура во UBL XML формат (EN 16931) која се доставува преку системот на УЈП. Од октомври 2026 сите регистрирани ДДВ обврзници ќе мораат да ги испраќаат фактурите електронски. Целта е намалување на даночна евазија и автоматизација на даночната контрола.',
      },
      {
        q: 'Кој UBL формат се користи во Македонија?',
        a: 'Македонија го користи UBL 2.1 стандардот (ISO/IEC 19845:2015) со EN 16931 профил. XML-от мора да содржи: податоци за издавач и примач (вклучувајќи ЕДБ), ставки со износи, и ДДВ пресмет по стапки.',
      },
      {
        q: 'Што е ЕДБ (Единствен Даночен Број)?',
        a: 'ЕДБ е единствениот даночен идентификационен број доделен од УЈП. Се состои од 13 цифри (или MK + 13 цифри за меѓународни трансакции). Секоја е-фактура мора да го содржи ЕДБ на издавачот и примачот.',
      },
      {
        q: 'Дали оваа алатка ги валидира дигиталните потписи?',
        a: 'Не. Оваа алатка ги проверува структурата и задолжителните полиња на UBL XML-от. За валидација на XAdES дигитален потпис потребна е целосна е-фактура платформа како Facturino.',
      },
      {
        q: 'Што ако мојот XML не поминува?',
        a: 'Проверете ги специфичните грешки прикажани во резултатите. Најчести проблеми: погрешен ЕДБ формат, недостасува датум, невалидна ДДВ стапка. Facturino генерира валиден XML автоматски — нема потреба од рачно уредување.',
      },
    ],
    ctaTitle: 'Автоматска е-фактура',
    ctaSub: 'Facturino генерира валиден UBL XML, го потпишува дигитално со XAdES, и го доставува до УЈП — автоматски.',
    ctaMainButton: 'Започни бесплатно — 14 де��а',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    backLink: '← Të gjitha mjetet',
    badge: 'Falas',
    h1: 'Verifikimi i e-Faturës',
    subtitle: 'Validoni XML-në tuaj UBL para se ta dërgoni në UJP. 12 kontrolle për plotësinë dhe përputhshmëri me standardin maqedonas.',
    tabPaste: 'Ngjit XML',
    tabUpload: 'Ngarko skedar',
    placeholder: '<?xml version="1.0" encoding="UTF-8"?>\n<Invoice xmlns="urn:oasis:names:specification:ubl:invoice:2">\n  ...\n</Invoice>',
    uploadLabel: 'Tërhiqni skedarin .xml këtu ose klikoni për të zgjedhur',
    uploadHint: 'Vetëm skedarë .xml, maks. 5MB',
    validateBtn: 'Valido',
    resultTitle: 'Rezultatet e verifikimit',
    passed: 'kaluan',
    of: 'nga',
    checks: 'kontrolle',
    labels: {
      validXml: 'Format valid XML',
      rootElement: 'Elementi rrënjë (Invoice/CreditNote)',
      invoiceId: 'Numri i faturës (cbc:ID)',
      issueDate: 'Data e lëshimit (cbc:IssueDate)',
      supplierEdb: 'EDB i furnizuesit (MK + 7-13 shifra)',
      buyerEdb: 'EDB i blerësit',
      lineItems: 'Së paku 1 artikull (InvoiceLine)',
      lineAmounts: 'Shumat e artikujve (LineExtensionAmount)',
      vatBreakdown: 'Llogaritja TVSH (TaxSubtotal)',
      vatRates: 'Norma valide TVSH (0/5/10/18%)',
      currency: 'Valuta = MKD',
      supplierName: 'Emri i furnizuesit',
    },
    deadline: 'Nga 1 tetori 2026, e-fatura është e detyrueshme për të gjitha bizneset në Maqedoni.',
    ctaInline: 'Facturino automatikisht gjeneron UBL XML valid për çdo faturë.',
    ctaButton: 'Provo falas',
    faqTitle: 'Pyetjet më të shpeshta',
    faq: [
      { q: 'Çfarë është e-fatura dhe pse është e detyrueshme?', a: 'E-fatura është faturë elektronike në format UBL XML (EN 16931) që dorëzohet përmes sistemit UJP. Nga tetori 2026 të gjithë obliguesit e TVSH-së duhet të dërgojnë faturat elektronikisht.' },
      { q: 'Cili format UBL përdoret në Maqedoni?', a: 'Maqedonia përdor standardin UBL 2.1 (ISO/IEC 19845:2015) me profilin EN 16931.' },
      { q: 'Çfarë është EDB?', a: 'EDB është numri unik tatimor i identifikimit i dhënë nga DAP. Përbëhet nga 13 shifra.' },
      { q: 'A i validon kjo mjet nënshkrimet dixhitale?', a: 'Jo. Kjo mjet kontrollon strukturën dhe fushat e detyrueshme. Për validim XAdES nevojitet platformë e plotë si Facturino.' },
      { q: 'Çfarë nëse XML im nuk kalon?', a: 'Kontrolloni gabimet specifike. Facturino gjeneron XML valid automatikisht.' },
    ],
    ctaTitle: 'E-faturë automatike',
    ctaSub: 'Facturino gjeneron UBL XML valid, e nënshkruan dixhitalisht me XAdES, dhe e dorëzon në UJP — automatikisht.',
    ctaMainButton: 'Fillo falas — 14 ditë',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    backLink: '← Tüm araçlar',
    badge: 'Ücretsiz',
    h1: 'E-Fatura doğrulama',
    subtitle: 'UBL XML\'inizi UJP\'ye göndermeden önce doğrulayın. Makedon standardına uygunluk için 12 kontrol.',
    tabPaste: 'XML yapıştır',
    tabUpload: 'Dosya yükle',
    placeholder: '<?xml version="1.0" encoding="UTF-8"?>\n<Invoice xmlns="urn:oasis:names:specification:ubl:invoice:2">\n  ...\n</Invoice>',
    uploadLabel: '.xml dosyasını buraya sürükleyin veya seçmek için tıklayın',
    uploadHint: 'Yalnızca .xml dosyaları, maks. 5MB',
    validateBtn: 'Doğrula',
    resultTitle: 'Doğrulama sonuçları',
    passed: 'geçti',
    of: '/',
    checks: 'kontrol',
    labels: {
      validXml: 'Geçerli XML formatı',
      rootElement: 'Kök öğe (Invoice/CreditNote)',
      invoiceId: 'Fatura numarası (cbc:ID)',
      issueDate: 'Düzenlenme tarihi (cbc:IssueDate)',
      supplierEdb: 'Tedarikçi VKN (MK + 7-13 hane)',
      buyerEdb: 'Alıcı VKN',
      lineItems: 'En az 1 kalem (InvoiceLine)',
      lineAmounts: 'Kalem tutarları (LineExtensionAmount)',
      vatBreakdown: 'KDV dökümü (TaxSubtotal)',
      vatRates: 'Geçerli KDV oranları (0/5/10/18%)',
      currency: 'Para birimi = MKD',
      supplierName: 'Tedarikçi adı',
    },
    deadline: '1 Ekim 2026\'dan itibaren e-fatura Makedonya\'daki tüm işletmeler için zorunludur.',
    ctaInline: 'Facturino her fatura için otomatik olarak geçerli UBL XML oluşturur.',
    ctaButton: 'Ücretsiz dene',
    faqTitle: 'Sık sorulan sorular',
    faq: [
      { q: 'E-fatura nedir ve neden zorunlu?', a: 'E-fatura, UJP sistemi üzerinden iletilen UBL XML formatında (EN 16931) elektronik faturadır. Ekim 2026\'dan itibaren tüm KDV mükellefleri faturaları elektronik göndermelidir.' },
      { q: 'Makedonya\'da hangi UBL formatı kullanılır?', a: 'Makedonya EN 16931 profiliyle UBL 2.1 standardını (ISO/IEC 19845:2015) kullanır.' },
      { q: 'EDB nedir?', a: 'EDB, GGİ tarafından verilen benzersiz vergi kimlik numarasıdır. 13 haneden oluşur.' },
      { q: 'Bu araç dijital imzaları doğrular mı?', a: 'Hayır. Bu araç yapıyı ve zorunlu alanları kontrol eder. XAdES doğrulaması için Facturino gibi tam bir platform gerekir.' },
      { q: 'XML\'im geçemezse ne yapmalıyım?', a: 'Belirtilen hataları kontrol edin. Facturino otomatik olarak geçerli XML oluşturur.' },
    ],
    ctaTitle: 'Otomatik e-fatura',
    ctaSub: 'Facturino geçerli UBL XML oluşturur, XAdES ile dijital imzalar ve UJP\'ye iletir — otomatik olarak.',
    ctaMainButton: 'Ücretsiz başla — 14 gün',
    ctaSecondary: 'Demo planla',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'E-Invoice Validator',
    subtitle: 'Validate your UBL XML before submitting to UJP. 12 checks for completeness and compliance with the Macedonian standard.',
    tabPaste: 'Paste XML',
    tabUpload: 'Upload file',
    placeholder: '<?xml version="1.0" encoding="UTF-8"?>\n<Invoice xmlns="urn:oasis:names:specification:ubl:invoice:2">\n  ...\n</Invoice>',
    uploadLabel: 'Drag .xml file here or click to select',
    uploadHint: 'Only .xml files, max 5MB',
    validateBtn: 'Validate',
    resultTitle: 'Validation results',
    passed: 'passed',
    of: 'of',
    checks: 'checks',
    labels: {
      validXml: 'Valid XML format',
      rootElement: 'Root element (Invoice/CreditNote)',
      invoiceId: 'Invoice number (cbc:ID)',
      issueDate: 'Issue date (cbc:IssueDate)',
      supplierEdb: 'Supplier Tax ID (MK + 7-13 digits)',
      buyerEdb: 'Buyer Tax ID',
      lineItems: 'At least 1 line item (InvoiceLine)',
      lineAmounts: 'Line amounts (LineExtensionAmount)',
      vatBreakdown: 'VAT breakdown (TaxSubtotal)',
      vatRates: 'Valid VAT rates (0/5/10/18%)',
      currency: 'Currency = MKD',
      supplierName: 'Supplier name',
    },
    deadline: 'From October 1, 2026, e-invoicing is mandatory for all businesses in Macedonia.',
    ctaInline: 'Facturino automatically generates valid UBL XML for every invoice.',
    ctaButton: 'Try for free',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      { q: 'What is e-invoicing and why is it mandatory?', a: 'E-invoicing is an electronic invoice in UBL XML format (EN 16931) submitted through the UJP system. From October 2026, all registered VAT payers must send invoices electronically.' },
      { q: 'Which UBL format is used in Macedonia?', a: 'Macedonia uses the UBL 2.1 standard (ISO/IEC 19845:2015) with the EN 16931 profile.' },
      { q: 'What is EDB (Tax ID)?', a: 'EDB is the unique tax identification number assigned by UJP. It consists of 13 digits (or MK + 13 digits for international transactions).' },
      { q: 'Does this tool validate digital signatures?', a: 'No. This tool checks structure and required fields. For XAdES digital signature validation, a full platform like Facturino is needed.' },
      { q: 'What if my XML fails?', a: 'Check the specific errors shown. Most common: wrong tax ID format, missing date, invalid VAT rate. Facturino generates valid XML automatically.' },
    ],
    ctaTitle: 'Automatic e-invoicing',
    ctaSub: 'Facturino generates valid UBL XML, digitally signs with XAdES, and submits to UJP ��� automatically.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

export default function EfakturaValidator({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])
  const [tab, setTab] = useState<'paste' | 'upload'>('paste')
  const [xml, setXml] = useState('')
  const [fileName, setFileName] = useState('')
  const [results, setResults] = useState<CheckResult[] | null>(null)
  const [dragOver, setDragOver] = useState(false)

  const handleValidate = useCallback(() => {
    if (!xml.trim()) return
    setResults(validateXml(xml, t.labels))
  }, [xml, t.labels])

  const handleFile = useCallback((file: File) => {
    if (!file.name.endsWith('.xml') || file.size > 5 * 1024 * 1024) return
    setFileName(file.name)
    const reader = new FileReader()
    reader.onload = (e) => {
      const text = e.target?.result as string
      setXml(text)
      setResults(validateXml(text, t.labels))
    }
    reader.readAsText(file)
  }, [t.labels])

  const passedCount = results?.filter((r) => r.passed).length || 0
  const totalCount = results?.length || 0

  const faqLd = {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: t.faq.map((item) => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: { '@type': 'Answer', text: item.a },
    })),
  }

  const webAppLd = {
    '@context': 'https://schema.org',
    '@type': 'WebApplication',
    name: t.h1,
    description: t.subtitle,
    url: `https://www.facturino.mk/${locale}/alati/efaktura-proverka`,
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'All',
    offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
    author: { '@type': 'Organization', name: 'Facturino', url: 'https://www.facturino.mk' },
  }

  const breadcrumbLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Facturino', item: `https://www.facturino.mk/${locale}` },
      { '@type': 'ListItem', position: 2, name: locale === 'mk' ? 'Алатки' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araçlar' : 'Tools', item: `https://www.facturino.mk/${locale}/alati` },
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/efaktura-proverka` },
    ],
  }

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(webAppLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      {/* Hero */}
      <section className="relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite]" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-red-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite_2s]" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/alati`} className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors">
            {t.backLink}
          </Link>
          <span className="inline-flex items-center rounded-full bg-orange-50 px-4 py-1.5 text-sm font-semibold text-orange-700 mb-4">
            {t.badge}
          </span>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-4">
            {t.h1}
          </h1>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">{t.subtitle}</p>
          <div className="mt-4 inline-flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-4 py-2 text-sm text-amber-800">
            <svg className="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
            {t.deadline}
          </div>
        </div>
      </section>

      {/* Validator */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-2xl mx-auto px-4 sm:px-6">
          <div className="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8">
            {/* Tabs */}
            <div className="grid grid-cols-2 gap-2 mb-6">
              <button
                onClick={() => { setTab('paste'); setResults(null) }}
                className={`px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${tab === 'paste' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'}`}
              >
                {t.tabPaste}
              </button>
              <button
                onClick={() => { setTab('upload'); setResults(null) }}
                className={`px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${tab === 'upload' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'}`}
              >
                {t.tabUpload}
              </button>
            </div>

            {tab === 'paste' ? (
              <div className="mb-4">
                <textarea
                  className="w-full h-48 rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 font-mono text-sm text-gray-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none resize-y"
                  placeholder={t.placeholder}
                  value={xml}
                  onChange={(e) => { setXml(e.target.value); setResults(null) }}
                />
              </div>
            ) : (
              <div
                className={`mb-4 rounded-xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer ${dragOver ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 bg-gray-50 hover:border-gray-400'}`}
                onDragOver={(e) => { e.preventDefault(); setDragOver(true) }}
                onDragLeave={() => setDragOver(false)}
                onDrop={(e) => { e.preventDefault(); setDragOver(false); const f = e.dataTransfer.files[0]; if (f) handleFile(f) }}
                onClick={() => { const input = document.createElement('input'); input.type = 'file'; input.accept = '.xml'; input.onchange = (e) => { const f = (e.target as HTMLInputElement).files?.[0]; if (f) handleFile(f) }; input.click() }}
              >
                <svg className="w-10 h-10 mx-auto mb-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>
                <p className="text-sm text-gray-600 font-medium">{t.uploadLabel}</p>
                <p className="text-xs text-gray-400 mt-1">{t.uploadHint}</p>
                {fileName && <p className="mt-2 text-sm font-medium text-indigo-600">{fileName}</p>}
              </div>
            )}

            {tab === 'paste' && (
              <button
                onClick={handleValidate}
                disabled={!xml.trim()}
                className="w-full py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              >
                {t.validateBtn}
              </button>
            )}

            {/* Results */}
            {results && (
              <div className="mt-6 animate-[fadeIn_0.3s_ease-out]">
                <div className="flex items-center justify-between mb-4">
                  <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider">{t.resultTitle}</h3>
                  <span className={`text-sm font-bold px-3 py-1 rounded-full ${passedCount === totalCount ? 'bg-green-100 text-green-800' : passedCount >= totalCount * 0.7 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800'}`}>
                    {passedCount} {t.of} {totalCount} {t.checks} {t.passed}
                  </span>
                </div>
                <div className="space-y-2">
                  {results.map((r) => (
                    <div key={r.id} className={`flex items-start gap-3 p-3 rounded-lg ${r.passed ? 'bg-green-50' : r.severity === 'error' ? 'bg-red-50' : 'bg-amber-50'}`}>
                      {r.passed ? (
                        <svg className="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" /></svg>
                      ) : r.severity === 'error' ? (
                        <svg className="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M6 18L18 6M6 6l12 12" /></svg>
                      ) : (
                        <svg className="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M12 9v2m0 4h.01" /></svg>
                      )}
                      <div className="flex-1 min-w-0">
                        <p className={`text-sm font-medium ${r.passed ? 'text-green-800' : r.severity === 'error' ? 'text-red-800' : 'text-amber-800'}`}>{r.label}</p>
                        {r.detail && <p className="text-xs text-gray-500 mt-0.5 font-mono truncate">{r.detail}</p>}
                      </div>
                    </div>
                  ))}
                </div>

                {/* Inline CTA */}
                <div className="mt-4 flex items-center justify-between rounded-lg bg-indigo-600/5 border border-indigo-100 px-4 py-3">
                  <p className="text-sm text-gray-700">{t.ctaInline}</p>
                  <a href={`${APP_URL}/signup`} className="ml-3 flex-shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors whitespace-nowrap">
                    {t.ctaButton} →
                  </a>
                </div>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* FAQ */}
      <section className="section bg-slate-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.faqTitle}</h2>
          <div className="space-y-4">
            {t.faq.map((item, i) => (
              <details key={i} className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
                <summary className="flex items-center justify-between p-6 cursor-pointer list-none">
                  <h3 className="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">{item.q}</h3>
                  <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
                  </span>
                </summary>
                <div className="px-6 pb-6 text-gray-600 leading-relaxed">{item.a}</div>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* Bottom CTA */}
      <section className="py-12 lg:py-24 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-2xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">{t.ctaTitle}</h2>
          <p className="text-xl text-indigo-100 mb-8">{t.ctaSub}</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href={`${APP_URL}/signup`} className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">{t.ctaMainButton}</a>
            <Link href={`/${locale}/contact`} className="px-8 py-4 bg-indigo-700/50 text-white border border-white/20 rounded-xl font-bold text-lg hover:bg-indigo-700/70 transition-all duration-300 backdrop-blur-sm">{t.ctaSecondary}</Link>
          </div>
        </div>
      </section>
    </main>
  )
}
