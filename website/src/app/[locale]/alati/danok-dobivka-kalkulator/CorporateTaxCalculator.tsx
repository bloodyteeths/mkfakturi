'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'Калкулатор за данок на добивка',
    subtitle: 'Пресметајте го данокот на добивка (корпоративен данок) за Македонија — моментално, без регистрација.',
    revenueLabel: 'Вкупен приход',
    deductibleLabel: 'Признати трошоци',
    nonDeductibleLabel: 'Непризнати трошоци',
    currency: 'МКД',
    revenueHelp: 'Вкупен промет (продажба на стоки, услуги, останати приходи)',
    deductibleHelp: 'Кирија, плати, материјали, амортизација, деловни патувања',
    nonDeductibleHelp: 'Репрезентација >1%, глоби, донации >5%, лични трошоци',
    resultAdjustedProfit: 'Бруто добивка (приход - признати трошоци)',
    resultNonDeductible: 'Непризнати трошоци (додаток на основа)',
    resultTaxBase: 'Даночна основа',
    resultTax: 'Данок на добивка (10%)',
    resultNetProfit: 'Нето добивка по данок',
    taxRate: '10%',
    ctaInline: 'Facturino автоматски ги следи приходите и трошоците за даночна пресметка.',
    ctaButton: 'Пробај бесплатно',
    eduTitle: 'Признати трошоци (ги намалуваат даночната основа)',
    eduItems: [
      'Кирија за деловен простор',
      'Плати и придонеси за вработени',
      'Материјали и суровини',
      'Амортизација на основни средства',
      'Деловни патувања и дневници',
      'Комунални услуги (струја, вода, телефон)',
      'Професионални услуги (сметководство, правни)',
      'Осигурување на имот и возила',
    ],
    nonEduTitle: 'Непризнати трошоци (НЕ ја намалуваат основата)',
    nonEduItems: [
      'Репрезентација над 1% од вкупниот приход',
      'Глоби и казни од државни органи',
      'Донации над 5% од вкупниот приход',
      'Лични трошоци на сопственикот',
      'Трошоци без уредна документација',
      'Камати на заеми над пазарна каматна стапка',
    ],
    deadlineTitle: 'Рок за поднесување',
    deadlineText: 'Годишната даночна пријава (образец ДБ-ВП) се поднесува до 15 март наредната година. Месечните аконтации се 1/12 од данокот на претходната година, и се плаќаат до 15-ти во секој месец.',
    advanceTitle: 'Месечни аконтации',
    advanceText: 'Секој месец се плаќа аконтација од 1/12 од данокот утврден во претходната година. Аконтациите се плаќаат до 15-ти во тековниот месец за претходниот месец.',
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Колку е стапката на данок на добивка во Македонија?',
        a: 'Стапката на данок на добивка во Северна Македонија е 10% (единствена стапка). Се применува на даночната основа — разликата помеѓу вкупните приходи и признатите трошоци, зголемена за непризнатите трошоци. Ова е една од најниските стапки во Европа.',
      },
      {
        q: 'Кои трошоци ја намалуваат даночната основа?',
        a: 'Признати (одбитни) трошоци се: плати и придонеси, кирија, материјали, амортизација, деловни патувања, комунални услуги, професионални услуги и осигурување. Трошоците мора да се поврзани со деловната активност и документирани со уредни фактури.',
      },
      {
        q: 'Кога е рокот за годишна даночна пријава?',
        a: 'Годишната даночна пријава за данок на добивка (образец ДБ-ВП) се поднесува до 15 март наредната година, електронски преку е-Даноци порталот на УЈП. На пример, за 2025 година, рокот е 15 март 2026.',
      },
      {
        q: 'Дали треба да плаќам месечни аконтации?',
        a: 'Да. Правните лица плаќаат месечни аконтации на данок на добивка, во износ од 1/12 од данокот утврден во претходната година. Аконтациите се плаќаат до 15-ти во месецот. Новоосновани фирми не плаќаат аконтации во првата година.',
      },
    ],
    ctaTitle: 'Автоматска пресметка на данок на добивка',
    ctaSub: 'Facturino ги следи вашите приходи и трошоци и автоматски ја пресметува даночната основа. Генерира извештаи спремни за УЈП.',
    ctaMainButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    backLink: '← Të gjitha mjetet',
    badge: 'Falas',
    h1: 'Llogaritësi i tatimit mbi fitimin',
    subtitle: 'Llogaritni tatimin mbi fitimin (tatimin korporativ) për Maqedoninë — menjëherë, pa regjistrim.',
    revenueLabel: 'Të ardhura totale',
    deductibleLabel: 'Shpenzime të njohura',
    nonDeductibleLabel: 'Shpenzime të panjohura',
    currency: 'MKD',
    revenueHelp: 'Qarkullimi total (shitje mallrash, shërbimesh, të ardhura tjera)',
    deductibleHelp: 'Qira, paga, materiale, amortizim, udhëtime pune',
    nonDeductibleHelp: 'Reprezentacion >1%, gjoba, donacione >5%, shpenzime personale',
    resultAdjustedProfit: 'Fitimi bruto (të ardhura - shpenzime të njohura)',
    resultNonDeductible: 'Shpenzime të panjohura (shtesë në bazë)',
    resultTaxBase: 'Baza tatimore',
    resultTax: 'Tatimi mbi fitimin (10%)',
    resultNetProfit: 'Fitimi neto pas tatimit',
    taxRate: '10%',
    ctaInline: 'Facturino automatikisht ndjek të ardhurat dhe shpenzimet për llogaritjen tatimore.',
    ctaButton: 'Provo falas',
    eduTitle: 'Shpenzime të njohura (ulin bazën tatimore)',
    eduItems: [
      'Qira për hapësirë biznesi',
      'Paga dhe kontribute për punonjësit',
      'Materiale dhe lëndë të para',
      'Amortizimi i mjeteve themelore',
      'Udhëtime pune dhe dieta',
      'Shërbime komunale (rrymë, ujë, telefon)',
      'Shërbime profesionale (kontabilitet, juridike)',
      'Sigurime të pronës dhe automjeteve',
    ],
    nonEduTitle: 'Shpenzime të panjohura (NUK e ulin bazën)',
    nonEduItems: [
      'Reprezentacion mbi 1% të të ardhurave totale',
      'Gjoba dhe dënime nga organet shtetërore',
      'Donacione mbi 5% të të ardhurave totale',
      'Shpenzime personale të pronarit',
      'Shpenzime pa dokumentacion të rregullt',
      'Kamata mbi kreditë mbi normën e tregut',
    ],
    deadlineTitle: 'Afati i dorëzimit',
    deadlineText: 'Deklarata vjetore tatimore (formulari DB-VP) dorëzohet deri më 15 mars të vitit pasardhës. Akontimet mujore janë 1/12 e tatimit të vitit të kaluar dhe paguhen deri më 15 të çdo muaji.',
    advanceTitle: 'Akontimet mujore',
    advanceText: 'Çdo muaj paguhet akontime prej 1/12 e tatimit të përcaktuar në vitin e kaluar. Akontimet paguhen deri më 15 të muajit aktual për muajin e kaluar.',
    faqTitle: 'Pyetjet më të shpeshta',
    faq: [
      {
        q: 'Sa është norma e tatimit mbi fitimin në Maqedoni?',
        a: 'Norma e tatimit mbi fitimin në Maqedoninë e Veriut është 10% (normë e vetme). Zbatohet mbi bazën tatimore — diferencën ndërmjet të ardhurave totale dhe shpenzimeve të njohura, plus shpenzimet e panjohura. Kjo është njëra nga normat më të ulëta në Europë.',
      },
      {
        q: 'Cilat shpenzime e ulin bazën tatimore?',
        a: 'Shpenzime të njohura (të zbritshme) janë: paga dhe kontribute, qira, materiale, amortizim, udhëtime pune, shërbime komunale, shërbime profesionale dhe sigurime. Shpenzimet duhet të lidhen me aktivitetin e biznesit dhe të dokumentohen me fatura të rregullta.',
      },
      {
        q: 'Kur është afati për deklaratën vjetore tatimore?',
        a: 'Deklarata vjetore tatimore për tatimin mbi fitimin (formulari DB-VP) dorëzohet deri më 15 mars të vitit pasardhës, elektronikisht përmes portalit e-Tatimi të DAP. Për shembull, për vitin 2025, afati është 15 mars 2026.',
      },
      {
        q: 'A duhet të paguaj akontim mujore?',
        a: 'Po. Personat juridikë paguajnë akontim mujore të tatimit mbi fitimin, në shumën prej 1/12 të tatimit të përcaktuar në vitin e kaluar. Akontimet paguhen deri më 15 të muajit. Firmat e sapothemeluara nuk paguajnë akontim në vitin e parë.',
      },
    ],
    ctaTitle: 'Llogaritje automatike e tatimit mbi fitimin',
    ctaSub: 'Facturino ndjek të ardhurat dhe shpenzimet tuaja dhe automatikisht llogarit bazën tatimore. Gjeneron raporte gati për DAP.',
    ctaMainButton: 'Fillo falas — 14 ditë',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    backLink: '← Tüm araçlar',
    badge: 'Ücretsiz',
    h1: 'Kurumlar Vergisi Hesaplayıcı',
    subtitle: 'Makedonya için kurumlar vergisini hesaplayın — anında, kayıt gerekmez.',
    revenueLabel: 'Toplam gelir',
    deductibleLabel: 'İndirilebilir giderler',
    nonDeductibleLabel: 'İndirilemeyen giderler',
    currency: 'MKD',
    revenueHelp: 'Toplam ciro (mal satışı, hizmetler, diğer gelirler)',
    deductibleHelp: 'Kira, maaşlar, malzeme, amortisman, iş seyahatleri',
    nonDeductibleHelp: 'Temsil >%1, para cezaları, bağışlar >%5, kişisel giderler',
    resultAdjustedProfit: 'Brüt kar (gelir - indirilebilir giderler)',
    resultNonDeductible: 'İndirilemeyen giderler (matrah eklentisi)',
    resultTaxBase: 'Vergi matrahı',
    resultTax: 'Kurumlar vergisi (%10)',
    resultNetProfit: 'Vergi sonrası net kar',
    taxRate: '%10',
    ctaInline: 'Facturino gelir ve giderleri vergi hesaplaması için otomatik takip eder.',
    ctaButton: 'Ücretsiz dene',
    eduTitle: 'İndirilebilir giderler (vergi matrahını düşürür)',
    eduItems: [
      'İşyeri kirası',
      'Çalışan maaşları ve primleri',
      'Malzeme ve hammaddeler',
      'Sabit varlık amortismanı',
      'İş seyahatleri ve harcırahlar',
      'Faturalı giderler (elektrik, su, telefon)',
      'Profesyonel hizmetler (muhasebe, hukuk)',
      'Mülk ve araç sigortası',
    ],
    nonEduTitle: 'İndirilemeyen giderler (matrahı DÜŞÜRMEZ)',
    nonEduItems: [
      'Toplam gelirin %1\'ini aşan temsil giderleri',
      'Devlet kurumlarından gelen para cezaları',
      'Toplam gelirin %5\'ini aşan bağışlar',
      'İşletme sahibinin kişisel giderleri',
      'Düzgün belgelendirilmemiş giderler',
      'Piyasa faiz oranını aşan kredi faizleri',
    ],
    deadlineTitle: 'Beyanname verme süresi',
    deadlineText: 'Yıllık vergi beyannamesi (DB-VP formu) ertesi yılın 15 Mart\'ına kadar verilir. Aylık avans ödemeleri önceki yılın vergisinin 1/12\'sidir ve her ayın 15\'ine kadar ödenir.',
    advanceTitle: 'Aylık avans ödemeleri',
    advanceText: 'Her ay önceki yılda belirlenen verginin 1/12\'si kadar avans ödenir. Avanslar cari ayın 15\'ine kadar önceki ay için ödenir.',
    faqTitle: 'Sık sorulan sorular',
    faq: [
      {
        q: 'Makedonya\'da kurumlar vergisi oranı nedir?',
        a: 'Kuzey Makedonya\'da kurumlar vergisi oranı %10\'dur (tek oran). Vergi matrahı üzerinden uygulanır — toplam gelir ile indirilebilir giderler arasındaki fark, artı indirilemeyen giderler. Bu, Avrupa\'daki en düşük oranlardan biridir.',
      },
      {
        q: 'Hangi giderler vergi matrahını düşürür?',
        a: 'İndirilebilir giderler: maaşlar ve primler, kira, malzeme, amortisman, iş seyahatleri, faturalı giderler, profesyonel hizmetler ve sigorta. Giderlerin ticari faaliyetle ilgili olması ve düzgün faturalarla belgelenmesi gerekir.',
      },
      {
        q: 'Yıllık vergi beyannamesi ne zaman verilir?',
        a: 'Kurumlar vergisi yıllık beyannamesi (DB-VP formu) ertesi yılın 15 Mart\'ına kadar GGİ\'nin e-Vergi portalı üzerinden elektronik olarak verilir. Örneğin, 2025 yılı için son tarih 15 Mart 2026\'dır.',
      },
      {
        q: 'Aylık avans ödemesi yapmam gerekiyor mu?',
        a: 'Evet. Tüzel kişiler, önceki yılda belirlenen verginin 1/12\'si tutarında aylık avans ödemesi yapar. Avanslar ayın 15\'ine kadar ödenir. Yeni kurulan şirketler ilk yıl avans ödemesi yapmaz.',
      },
    ],
    ctaTitle: 'Otomatik kurumlar vergisi hesaplama',
    ctaSub: 'Facturino gelir ve giderlerinizi takip eder ve vergi matrahını otomatik hesaplar. GGİ için hazır raporlar oluşturur.',
    ctaMainButton: 'Ücretsiz başla — 14 gün',
    ctaSecondary: 'Demo planla',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'Corporate Tax Calculator',
    subtitle: 'Calculate corporate income tax for Macedonia — instantly, no registration required.',
    revenueLabel: 'Total revenue',
    deductibleLabel: 'Deductible expenses',
    nonDeductibleLabel: 'Non-deductible expenses',
    currency: 'MKD',
    revenueHelp: 'Total turnover (sale of goods, services, other income)',
    deductibleHelp: 'Rent, salaries, materials, depreciation, business travel',
    nonDeductibleHelp: 'Entertainment >1% of revenue, fines, donations >5%, personal expenses',
    resultAdjustedProfit: 'Gross profit (revenue - deductible expenses)',
    resultNonDeductible: 'Non-deductible expenses (added to base)',
    resultTaxBase: 'Tax base',
    resultTax: 'Corporate tax (10%)',
    resultNetProfit: 'Net profit after tax',
    taxRate: '10%',
    ctaInline: 'Facturino automatically tracks income and expenses for tax calculation.',
    ctaButton: 'Try for free',
    eduTitle: 'Deductible expenses (reduce the tax base)',
    eduItems: [
      'Business premises rent',
      'Employee salaries and contributions',
      'Materials and raw goods',
      'Depreciation of fixed assets',
      'Business travel and per diems',
      'Utility bills (electricity, water, phone)',
      'Professional services (accounting, legal)',
      'Property and vehicle insurance',
    ],
    nonEduTitle: 'Non-deductible expenses (do NOT reduce the base)',
    nonEduItems: [
      'Entertainment exceeding 1% of total revenue',
      'Fines and penalties from government bodies',
      'Donations exceeding 5% of total revenue',
      'Personal expenses of the owner',
      'Expenses without proper documentation',
      'Loan interest above market rate',
    ],
    deadlineTitle: 'Filing deadline',
    deadlineText: 'The annual tax return (DB-VP form) must be filed by March 15 of the following year. Monthly advance payments are 1/12 of the previous year\'s tax and are due by the 15th of each month.',
    advanceTitle: 'Monthly advance payments',
    advanceText: 'Each month, an advance of 1/12 of the tax determined in the previous year is paid. Advances are due by the 15th of the current month for the previous month.',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      {
        q: 'What is the corporate tax rate in Macedonia?',
        a: 'The corporate income tax rate in North Macedonia is 10% (flat rate). It is applied to the tax base — the difference between total revenue and deductible expenses, plus non-deductible expenses. This is one of the lowest rates in Europe.',
      },
      {
        q: 'Which expenses reduce my tax base?',
        a: 'Deductible expenses include: salaries and contributions, rent, materials, depreciation, business travel, utility bills, professional services, and insurance. Expenses must be related to the business activity and documented with proper invoices.',
      },
      {
        q: 'When is the annual corporate tax return due?',
        a: 'The annual corporate tax return (DB-VP form) must be filed by March 15 of the following year, electronically via UJP\'s e-Tax portal. For example, for 2025, the deadline is March 15, 2026.',
      },
      {
        q: 'Do I need to make monthly advance payments?',
        a: 'Yes. Legal entities make monthly advance payments of 1/12 of the tax determined in the previous year. Advances are due by the 15th of each month. Newly established companies do not make advance payments in their first year.',
      },
    ],
    ctaTitle: 'Automatic corporate tax calculation',
    ctaSub: 'Facturino tracks your income and expenses and automatically calculates the tax base. Generates reports ready for UJP.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

function formatNumber(n: number): string {
  return n.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export default function CorporateTaxCalculator({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])
  const [revenue, setRevenue] = useState('')
  const [deductible, setDeductible] = useState('')
  const [nonDeductible, setNonDeductible] = useState('')

  const revenueNum = parseFloat(revenue.replace(/[^0-9.]/g, '')) || 0
  const deductibleNum = parseFloat(deductible.replace(/[^0-9.]/g, '')) || 0
  const nonDeductibleNum = parseFloat(nonDeductible.replace(/[^0-9.]/g, '')) || 0

  const adjustedProfit = Math.max(revenueNum - deductibleNum, 0)
  const taxBase = Math.max(adjustedProfit + nonDeductibleNum, 0)
  const tax = taxBase * 0.1
  const netProfit = Math.max(taxBase - tax, 0)

  const hasResult = revenueNum > 0

  const handleInput = (setter: (v: string) => void) => (e: React.ChangeEvent<HTMLInputElement>) => {
    const val = e.target.value.replace(/[^0-9.,]/g, '')
    setter(val)
  }

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
    url: `https://www.facturino.mk/${locale}/alati/danok-dobivka-kalkulator`,
    applicationCategory: 'FinanceApplication',
    operatingSystem: 'All',
    offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
    author: {
      '@type': 'Organization',
      name: 'Facturino',
      url: 'https://www.facturino.mk',
    },
  }

  const breadcrumbLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Facturino', item: `https://www.facturino.mk/${locale}` },
      { '@type': 'ListItem', position: 2, name: locale === 'mk' ? 'Алатки' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araçlar' : 'Tools', item: `https://www.facturino.mk/${locale}/alati` },
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/danok-dobivka-kalkulator` },
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
          <div className="absolute top-10 left-10 w-72 h-72 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite]" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-teal-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite_2s]" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/alati`} className="inline-flex items-center text-sm text-emerald-600 hover:text-emerald-800 font-medium mb-8 transition-colors">
            {t.backLink}
          </Link>
          <span className="inline-flex items-center rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-semibold text-emerald-600 mb-4">
            {t.badge}
          </span>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-4">
            {t.h1}
          </h1>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.subtitle}
          </p>
        </div>
      </section>

      {/* Calculator */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-xl mx-auto px-4 sm:px-6">
          <div className="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8">
            {/* Revenue Input */}
            <div className="mb-6">
              <label htmlFor="tax-revenue" className="block text-sm font-medium text-gray-700 mb-1">{t.revenueLabel}</label>
              <p className="text-xs text-gray-400 mb-2">{t.revenueHelp}</p>
              <div className="relative">
                <input
                  id="tax-revenue"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:outline-none transition-colors"
                  placeholder="0.00"
                  value={revenue}
                  onChange={handleInput(setRevenue)}
                  autoFocus
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
              </div>
            </div>

            {/* Deductible Expenses Input */}
            <div className="mb-6">
              <label htmlFor="tax-deductible" className="block text-sm font-medium text-gray-700 mb-1">{t.deductibleLabel}</label>
              <p className="text-xs text-gray-400 mb-2">{t.deductibleHelp}</p>
              <div className="relative">
                <input
                  id="tax-deductible"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:outline-none transition-colors"
                  placeholder="0.00"
                  value={deductible}
                  onChange={handleInput(setDeductible)}
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
              </div>
            </div>

            {/* Non-Deductible Expenses Input */}
            <div className="mb-6">
              <label htmlFor="tax-nondeductible" className="block text-sm font-medium text-gray-700 mb-1">{t.nonDeductibleLabel}</label>
              <p className="text-xs text-gray-400 mb-2">{t.nonDeductibleHelp}</p>
              <div className="relative">
                <input
                  id="tax-nondeductible"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:outline-none transition-colors"
                  placeholder="0.00"
                  value={nonDeductible}
                  onChange={handleInput(setNonDeductible)}
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
              </div>
            </div>

            {/* Results */}
            {hasResult && (
              <div className="rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 p-5 space-y-3 animate-[fadeIn_0.3s_ease-out]">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultAdjustedProfit}</span>
                  <span className="text-lg font-bold text-gray-900">{formatNumber(adjustedProfit)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultNonDeductible}</span>
                  <span className="text-lg font-bold text-red-600">+{formatNumber(nonDeductibleNum)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center border-t border-emerald-100 pt-3">
                  <span className="text-sm font-medium text-gray-700">{t.resultTaxBase}</span>
                  <span className="text-lg font-bold text-gray-900">{formatNumber(taxBase)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center border-y border-emerald-100 py-3">
                  <span className="text-sm font-medium text-emerald-700">{t.resultTax}</span>
                  <span className="text-xl font-extrabold text-emerald-700">{formatNumber(tax)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultNetProfit}</span>
                  <span className="text-lg font-bold text-gray-900">{formatNumber(netProfit)} {t.currency}</span>
                </div>
              </div>
            )}

            {/* Inline CTA */}
            {hasResult && (
              <div className="mt-4 flex items-center justify-between rounded-lg bg-emerald-600/5 border border-emerald-100 px-4 py-3">
                <p className="text-sm text-gray-700">{t.ctaInline}</p>
                <a
                  href={`${APP_URL}/signup`}
                  className="ml-3 flex-shrink-0 text-sm font-semibold text-emerald-600 hover:text-emerald-800 transition-colors whitespace-nowrap"
                >
                  {t.ctaButton} →
                </a>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Educational Content: Deductible & Non-Deductible */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="grid md:grid-cols-2 gap-6">
            {/* Deductible Expenses Card */}
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <div className="flex items-center gap-3 mb-4">
                <span className="flex-shrink-0 w-10 h-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
                  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" /></svg>
                </span>
                <h2 className="text-lg font-bold text-gray-900">{t.eduTitle}</h2>
              </div>
              <ul className="space-y-2">
                {t.eduItems.map((item, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                    <span className="flex-shrink-0 w-1.5 h-1.5 rounded-full bg-emerald-400 mt-1.5" />
                    {item}
                  </li>
                ))}
              </ul>
            </div>

            {/* Non-Deductible Expenses Card */}
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <div className="flex items-center gap-3 mb-4">
                <span className="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" /></svg>
                </span>
                <h2 className="text-lg font-bold text-gray-900">{t.nonEduTitle}</h2>
              </div>
              <ul className="space-y-2">
                {t.nonEduItems.map((item, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm text-gray-600">
                    <span className="flex-shrink-0 w-1.5 h-1.5 rounded-full bg-red-400 mt-1.5" />
                    {item}
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* Deadline & Advance Payments Info */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6 space-y-4">
          <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-emerald-100">
            <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
              <h2 className="text-base font-semibold text-gray-900 group-hover:text-emerald-600 transition-colors pr-8">{t.deadlineTitle}</h2>
              <span className="flex-shrink-0 w-7 h-7 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-emerald-600 group-open:text-white">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
              </span>
            </summary>
            <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.deadlineText}</div>
          </details>
          <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-emerald-100">
            <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
              <h2 className="text-base font-semibold text-gray-900 group-hover:text-emerald-600 transition-colors pr-8">{t.advanceTitle}</h2>
              <span className="flex-shrink-0 w-7 h-7 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-emerald-600 group-open:text-white">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
              </span>
            </summary>
            <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.advanceText}</div>
          </details>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="section bg-slate-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.faqTitle}</h2>
          <div className="space-y-4">
            {t.faq.map((item, i) => (
              <details key={i} className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-emerald-100">
                <summary className="flex items-center justify-between p-6 cursor-pointer list-none">
                  <h3 className="text-lg font-semibold text-gray-900 group-hover:text-emerald-600 transition-colors pr-8">
                    {item.q}
                  </h3>
                  <span className="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-emerald-600 group-open:text-white">
                    <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                    </svg>
                  </span>
                </summary>
                <div className="px-6 pb-6 text-gray-600 leading-relaxed">
                  {item.a}
                </div>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* Bottom CTA */}
      <section className="py-12 lg:py-24 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-emerald-600 to-teal-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-2xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">{t.ctaTitle}</h2>
          <p className="text-xl text-emerald-100 mb-8">{t.ctaSub}</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href={`${APP_URL}/signup`} className="px-8 py-4 bg-white text-emerald-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
              {t.ctaMainButton}
            </a>
            <Link href={`/${locale}/contact`} className="px-8 py-4 bg-emerald-700/50 text-white border border-white/20 rounded-xl font-bold text-lg hover:bg-emerald-700/70 transition-all duration-300 backdrop-blur-sm">
              {t.ctaSecondary}
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}
