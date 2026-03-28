'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

// Macedonian payroll rates 2026 (matching MacedonianPayrollTaxService.php)
const RATES = {
  employee: {
    pension: 0.09,        // ПИО - пензиско
    health: 0.0375,       // ЗО - здравствено
    unemployment: 0.012,  // Невработеност
    additional: 0.005,    // Дополнителен (проф. болести)
  },
  employer: {
    pension: 0.09,        // ПИО - пензиско
    health: 0.0375,       // ЗО - здравствено
  },
  incomeTax: 0.10,
  minBase: 31577,         // Минимална основица
  maxBase: 1010464,       // Максимална основица
  minNetSalary: 18000,    // Минимална нето плата
}

const EMPLOYEE_TOTAL = RATES.employee.pension + RATES.employee.health + RATES.employee.unemployment + RATES.employee.additional // 0.1445
const EMPLOYER_TOTAL = RATES.employer.pension + RATES.employer.health // 0.1275

function calcGrossToNet(gross: number) {
  const base = Math.min(Math.max(gross, RATES.minBase), RATES.maxBase)
  const empPension = base * RATES.employee.pension
  const empHealth = base * RATES.employee.health
  const empUnemployment = base * RATES.employee.unemployment
  const empAdditional = base * RATES.employee.additional
  const totalEmployee = empPension + empHealth + empUnemployment + empAdditional
  const taxableBase = gross - totalEmployee
  const incomeTax = Math.max(0, taxableBase * RATES.incomeTax)
  const net = gross - totalEmployee - incomeTax
  const erPension = base * RATES.employer.pension
  const erHealth = base * RATES.employer.health
  const totalEmployer = erPension + erHealth
  const totalCost = gross + totalEmployer

  return {
    gross, net, taxableBase, incomeTax,
    employee: { pension: empPension, health: empHealth, unemployment: empUnemployment, additional: empAdditional, total: totalEmployee },
    employer: { pension: erPension, health: erHealth, total: totalEmployer },
    totalCost,
  }
}

function calcNetToGross(targetNet: number): ReturnType<typeof calcGrossToNet> {
  // Binary search for gross that yields the target net
  let lo = targetNet, hi = targetNet * 2
  for (let i = 0; i < 50; i++) {
    const mid = (lo + hi) / 2
    const result = calcGrossToNet(mid)
    if (result.net < targetNet) lo = mid
    else hi = mid
  }
  return calcGrossToNet((lo + hi) / 2)
}

function fmt(n: number): string {
  return Math.round(n).toLocaleString('mk-MK')
}

const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'Калкулатор за плата',
    subtitle: 'Пресметајте нето плата од бруто и обратно — со точни стапки за придонеси и данок за Македонија 2026.',
    tabGrossToNet: 'Бруто → Нето',
    tabNetToGross: 'Нето → Бруто',
    grossLabel: 'Бруто плата',
    netLabel: 'Посакувана нето плата',
    currency: 'МКД',
    resultTitle: 'Детален пресмет',
    grossSalary: 'Бруто плата',
    employeeContrib: 'Придонеси на товар на вработен',
    pension: 'Пензиско осигурување (ПИО)',
    health: 'Здравствено осигурување (ЗО)',
    unemployment: 'Осигурување за невработеност',
    additional: 'Дополнителен придонес',
    totalEmployee: 'Вкупно придонеси (вработен)',
    taxableBase: 'Даночна основица',
    incomeTax: 'Данок на личен доход (10%)',
    netSalary: 'Нето плата',
    employerContrib: 'Придонеси на товар на работодавач',
    pensionEr: 'Пензиско осигурување (ПИО)',
    healthEr: 'Здравствено осигурување (ЗО)',
    totalEmployer: 'Вкупно придонеси (работодавач)',
    totalCost: 'Вкупен трошок за работодавачот',
    minNote: 'Минимална нето плата: 18.000 МКД',
    ctaInline: 'Пресметај плати за сите вработени одеднаш.',
    ctaButton: 'Пробај бесплатно',
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Колку е минималната плата во Македонија во 2026?',
        a: 'Минималната нето плата во Северна Македонија изнесува 18.000 денари месечно. Минималната бруто плата (со придонеси) изнесува приближно 23.400 денари, во зависност од основицата за придонеси.',
      },
      {
        q: 'Колку изнесуваат придонесите за вработен?',
        a: 'Вкупните придонеси на товар на вработениот се 14.45%: пензиско 9%, здравствено 3.75%, невработеност 1.2%, дополнителен 0.5%. Работодавачот плаќа дополнителни 12.75% (пензиско 9% + здравствено 3.75%).',
      },
      {
        q: 'Како се пресметува данокот на доход?',
        a: 'Данокот на личен доход е 10% од даночната основица. Даночната основица = бруто плата минус придонесите на вработениот. Пример: бруто 40.000 - придонеси 5.780 = основица 34.220, данок = 3.422 денари.',
      },
      {
        q: 'Кој е рокот за МПИН (месечна пресметка)?',
        a: 'МПИН пријавата се поднесува најдоцна до 15-ти во месецот за претходниот месец. Платата мора да биде исплатена до крајот на тековниот месец за претходниот.',
      },
      {
        q: 'Дали постои максимална основица за придонеси?',
        a: 'Да. Максималната месечна основица за пресметка на придонеси во 2026 изнесува 1.010.464 денари. Минималната основица е 31.577 денари.',
      },
    ],
    ctaTitle: 'Автоматска пресметка на плати',
    ctaSub: 'Facturino пресметува плати за сите вработени, генерира МПИН, и следи трошоци по одделенија.',
    ctaMainButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    backLink: '← Të gjitha mjetet',
    badge: 'Falas',
    h1: 'Llogaritësi i pagës',
    subtitle: 'Llogaritni pagën neto nga bruto dhe anasjelltas — me norma të sakta kontributesh dhe tatimi për Maqedoninë 2026.',
    tabGrossToNet: 'Bruto → Neto',
    tabNetToGross: 'Neto → Bruto',
    grossLabel: 'Paga bruto',
    netLabel: 'Paga neto e dëshiruar',
    currency: 'MKD',
    resultTitle: 'Llogaritja e detajuar',
    grossSalary: 'Paga bruto',
    employeeContrib: 'Kontributet e punëtorit',
    pension: 'Sigurim pensional (PIO)',
    health: 'Sigurim shëndetësor (ZO)',
    unemployment: 'Sigurim papunësie',
    additional: 'Kontribut shtesë',
    totalEmployee: 'Gjithsej kontribute (punëtor)',
    taxableBase: 'Baza tatimore',
    incomeTax: 'Tatim mbi të ardhurat (10%)',
    netSalary: 'Paga neto',
    employerContrib: 'Kontributet e punëdhënësit',
    pensionEr: 'Sigurim pensional (PIO)',
    healthEr: 'Sigurim shëndetësor (ZO)',
    totalEmployer: 'Gjithsej kontribute (punëdhënës)',
    totalCost: 'Kosto totale për punëdhënësin',
    minNote: 'Paga minimale neto: 18,000 MKD',
    ctaInline: 'Llogarit pagat për të gjithë punonjësit njëherësh.',
    ctaButton: 'Provo falas',
    faqTitle: 'Pyetjet më të shpeshta',
    faq: [
      {
        q: 'Sa është paga minimale në Maqedoni në 2026?',
        a: 'Paga minimale neto në Maqedoninë e Veriut është 18,000 denarë në muaj. Paga minimale bruto (me kontribute) është afërsisht 23,400 denarë.',
      },
      {
        q: 'Sa janë kontributet e punëtorit?',
        a: 'Kontributet totale të punëtorit janë 14.45%: pension 9%, shëndetësi 3.75%, papunësi 1.2%, shtesë 0.5%. Punëdhënësi paguan 12.75% shtesë.',
      },
      {
        q: 'Si llogaritet tatimi mbi të ardhurat?',
        a: 'Tatimi mbi të ardhurat personale është 10% e bazës tatimore. Baza = paga bruto minus kontributet e punëtorit.',
      },
      {
        q: 'Cili është afati për MPIN?',
        a: 'Deklarata MPIN dorëzohet deri më 15 të muajit për muajin e kaluar. Paga duhet të paguhet deri në fund të muajit aktual.',
      },
      {
        q: 'A ka bazë maksimale për kontribute?',
        a: 'Po. Baza maksimale mujore për kontribute në 2026 është 1,010,464 denarë. Baza minimale është 31,577 denarë.',
      },
    ],
    ctaTitle: 'Llogaritje automatike e pagave',
    ctaSub: 'Facturino llogarit pagat për të gjithë punonjësit, gjeneron MPIN, dhe ndjek kostot sipas departamenteve.',
    ctaMainButton: 'Fillo falas — 14 ditë',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    backLink: '← Tüm araçlar',
    badge: 'Ücretsiz',
    h1: 'Maaş hesaplayıcı',
    subtitle: 'Brütten net maaş ve tersini hesaplayın — Makedonya 2026 için doğru katkı payı ve vergi oranlarıyla.',
    tabGrossToNet: 'Brüt → Net',
    tabNetToGross: 'Net → Brüt',
    grossLabel: 'Brüt maaş',
    netLabel: 'İstenen net maaş',
    currency: 'MKD',
    resultTitle: 'Detaylı hesaplama',
    grossSalary: 'Brüt maaş',
    employeeContrib: 'Çalışan katkı payları',
    pension: 'Emeklilik sigortası (PİO)',
    health: 'Sağlık sigortası (ZO)',
    unemployment: 'İşsizlik sigortası',
    additional: 'Ek katkı payı',
    totalEmployee: 'Toplam katkı (çalışan)',
    taxableBase: 'Vergi matrahı',
    incomeTax: 'Gelir vergisi (%10)',
    netSalary: 'Net maaş',
    employerContrib: 'İşveren katkı payları',
    pensionEr: 'Emeklilik sigortası (PİO)',
    healthEr: 'Sağlık sigortası (ZO)',
    totalEmployer: 'Toplam katkı (işveren)',
    totalCost: 'İşveren için toplam maliyet',
    minNote: 'Asgari net maaş: 18.000 MKD',
    ctaInline: 'Tüm çalışanların maaşlarını bir seferde hesaplayın.',
    ctaButton: 'Ücretsiz dene',
    faqTitle: 'Sık sorulan sorular',
    faq: [
      {
        q: '2026\'da Makedonya\'da asgari maaş ne kadar?',
        a: 'Kuzey Makedonya\'da asgari net maaş aylık 18.000 denardır. Asgari brüt maaş (katkı paylarıyla) yaklaşık 23.400 denardır.',
      },
      {
        q: 'Çalışan katkı payları ne kadar?',
        a: 'Toplam çalışan katkı payları %14,45: emeklilik %9, sağlık %3,75, işsizlik %1,2, ek %0,5. İşveren ek %12,75 öder.',
      },
      {
        q: 'Gelir vergisi nasıl hesaplanır?',
        a: 'Kişisel gelir vergisi vergi matrahının %10\'udur. Matrah = brüt maaş eksi çalışan katkı payları.',
      },
      {
        q: 'MPIN için son tarih nedir?',
        a: 'MPIN beyannamesi önceki ay için ayın 15\'ine kadar verilir. Maaş cari ayın sonuna kadar ödenmelidir.',
      },
      {
        q: 'Katkı payları için azami matrah var mı?',
        a: 'Evet. 2026\'da aylık azami katkı matrahı 1.010.464 denardır. Asgari matrah 31.577 denardır.',
      },
    ],
    ctaTitle: 'Otomatik maaş hesaplama',
    ctaSub: 'Facturino tüm çalışanlar için maaş hesaplar, MPIN oluşturur ve departman bazında maliyetleri takip eder.',
    ctaMainButton: 'Ücretsiz başla — 14 gün',
    ctaSecondary: 'Demo planla',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'Salary Calculator',
    subtitle: 'Calculate net salary from gross and vice versa — with accurate contribution and tax rates for Macedonia 2026.',
    tabGrossToNet: 'Gross → Net',
    tabNetToGross: 'Net → Gross',
    grossLabel: 'Gross salary',
    netLabel: 'Desired net salary',
    currency: 'MKD',
    resultTitle: 'Detailed breakdown',
    grossSalary: 'Gross salary',
    employeeContrib: 'Employee contributions',
    pension: 'Pension insurance (PIO)',
    health: 'Health insurance (ZO)',
    unemployment: 'Unemployment insurance',
    additional: 'Additional contribution',
    totalEmployee: 'Total contributions (employee)',
    taxableBase: 'Taxable base',
    incomeTax: 'Personal income tax (10%)',
    netSalary: 'Net salary',
    employerContrib: 'Employer contributions',
    pensionEr: 'Pension insurance (PIO)',
    healthEr: 'Health insurance (ZO)',
    totalEmployer: 'Total contributions (employer)',
    totalCost: 'Total cost for employer',
    minNote: 'Minimum net salary: 18,000 MKD',
    ctaInline: 'Calculate salaries for all employees at once.',
    ctaButton: 'Try for free',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      {
        q: 'What is the minimum salary in Macedonia in 2026?',
        a: 'The minimum net salary in North Macedonia is 18,000 MKD per month. The minimum gross salary (with contributions) is approximately 23,400 MKD.',
      },
      {
        q: 'How much are employee contributions?',
        a: 'Total employee contributions are 14.45%: pension 9%, health 3.75%, unemployment 1.2%, additional 0.5%. The employer pays an additional 12.75%.',
      },
      {
        q: 'How is income tax calculated?',
        a: 'Personal income tax is 10% of the taxable base. Taxable base = gross salary minus employee contributions.',
      },
      {
        q: 'What is the deadline for MPIN?',
        a: 'The MPIN declaration must be filed by the 15th of the month for the previous month. Salary must be paid by the end of the current month.',
      },
      {
        q: 'Is there a maximum contribution base?',
        a: 'Yes. The maximum monthly contribution base in 2026 is 1,010,464 MKD. The minimum base is 31,577 MKD.',
      },
    ],
    ctaTitle: 'Automatic salary calculation',
    ctaSub: 'Facturino calculates salaries for all employees, generates MPIN, and tracks costs by department.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

export default function SalaryCalculator({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])
  const [amount, setAmount] = useState('')
  const [mode, setMode] = useState<'gross' | 'net'>('gross')

  const amountNum = parseFloat(amount.replace(/,/g, '')) || 0
  const result = amountNum > 0
    ? mode === 'gross' ? calcGrossToNet(amountNum) : calcNetToGross(amountNum)
    : null

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
    url: `https://www.facturino.mk/${locale}/alati/plata-kalkulator`,
    applicationCategory: 'FinanceApplication',
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
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/plata-kalkulator` },
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
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite_2s]" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/alati`} className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors">
            {t.backLink}
          </Link>
          <span className="inline-flex items-center rounded-full bg-emerald-50 px-4 py-1.5 text-sm font-semibold text-emerald-700 mb-4">
            {t.badge}
          </span>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-4">
            {t.h1}
          </h1>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">{t.subtitle}</p>
        </div>
      </section>

      {/* Calculator */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-xl mx-auto px-4 sm:px-6">
          <div className="bg-white rounded-2xl shadow-xl border border-gray-100 p-6 md:p-8">
            {/* Mode Tabs */}
            <div className="grid grid-cols-2 gap-2 mb-6">
              <button
                onClick={() => { setMode('gross'); setAmount('') }}
                className={`px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                  mode === 'gross' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                }`}
              >
                {t.tabGrossToNet}
              </button>
              <button
                onClick={() => { setMode('net'); setAmount('') }}
                className={`px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                  mode === 'net' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                }`}
              >
                {t.tabNetToGross}
              </button>
            </div>

            {/* Input */}
            <div className="mb-6">
              <label htmlFor="salary-amount" className="block text-sm font-medium text-gray-700 mb-2">
                {mode === 'gross' ? t.grossLabel : t.netLabel}
              </label>
              <div className="relative">
                <input
                  id="salary-amount"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  placeholder="0"
                  value={amount}
                  onChange={(e) => setAmount(e.target.value.replace(/[^0-9.,]/g, ''))}
                  autoFocus
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
              </div>
              <p className="mt-2 text-xs text-gray-500">{t.minNote}</p>
            </div>

            {/* Results */}
            {result && (
              <div className="space-y-4 animate-[fadeIn_0.3s_ease-out]">
                <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider">{t.resultTitle}</h3>

                {/* Gross */}
                <div className="rounded-xl bg-gray-50 p-4">
                  <div className="flex justify-between items-center">
                    <span className="text-sm font-medium text-gray-700">{t.grossSalary}</span>
                    <span className="text-lg font-bold text-gray-900">{fmt(result.gross)} {t.currency}</span>
                  </div>
                </div>

                {/* Employee Contributions */}
                <div className="rounded-xl border border-amber-100 bg-amber-50/50 p-4 space-y-2">
                  <p className="text-xs font-semibold text-amber-800 uppercase tracking-wider mb-2">{t.employeeContrib}</p>
                  <Row label={`${t.pension} (9%)`} value={result.employee.pension} currency={t.currency} />
                  <Row label={`${t.health} (3.75%)`} value={result.employee.health} currency={t.currency} />
                  <Row label={`${t.unemployment} (1.2%)`} value={result.employee.unemployment} currency={t.currency} />
                  <Row label={`${t.additional} (0.5%)`} value={result.employee.additional} currency={t.currency} />
                  <div className="border-t border-amber-200 pt-2 flex justify-between">
                    <span className="text-sm font-semibold text-amber-900">{t.totalEmployee} (14.45%)</span>
                    <span className="text-sm font-bold text-amber-900">−{fmt(result.employee.total)} {t.currency}</span>
                  </div>
                </div>

                {/* Tax */}
                <div className="rounded-xl border border-red-100 bg-red-50/50 p-4 space-y-2">
                  <Row label={t.taxableBase} value={result.taxableBase} currency={t.currency} />
                  <div className="border-t border-red-200 pt-2 flex justify-between">
                    <span className="text-sm font-semibold text-red-900">{t.incomeTax}</span>
                    <span className="text-sm font-bold text-red-900">−{fmt(result.incomeTax)} {t.currency}</span>
                  </div>
                </div>

                {/* Net */}
                <div className="rounded-xl bg-gradient-to-br from-emerald-50 to-green-50 border border-emerald-200 p-4">
                  <div className="flex justify-between items-center">
                    <span className="text-base font-bold text-emerald-800">{t.netSalary}</span>
                    <span className="text-2xl font-extrabold text-emerald-700">{fmt(result.net)} {t.currency}</span>
                  </div>
                </div>

                {/* Visual bar */}
                <div className="h-4 rounded-full overflow-hidden flex">
                  <div className="bg-emerald-500" style={{ width: `${(result.net / result.totalCost) * 100}%` }} title={t.netSalary} />
                  <div className="bg-amber-400" style={{ width: `${(result.employee.total / result.totalCost) * 100}%` }} title={t.totalEmployee} />
                  <div className="bg-red-400" style={{ width: `${(result.incomeTax / result.totalCost) * 100}%` }} title={t.incomeTax} />
                  <div className="bg-blue-400" style={{ width: `${(result.employer.total / result.totalCost) * 100}%` }} title={t.totalEmployer} />
                </div>
                <div className="flex flex-wrap gap-3 text-xs text-gray-600">
                  <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block" />{t.netSalary}</span>
                  <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block" />{t.employeeContrib}</span>
                  <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-red-400 inline-block" />{t.incomeTax}</span>
                  <span className="flex items-center gap-1"><span className="w-2.5 h-2.5 rounded-full bg-blue-400 inline-block" />{t.employerContrib}</span>
                </div>

                {/* Employer */}
                <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-4 space-y-2">
                  <p className="text-xs font-semibold text-blue-800 uppercase tracking-wider mb-2">{t.employerContrib}</p>
                  <Row label={`${t.pensionEr} (9%)`} value={result.employer.pension} currency={t.currency} />
                  <Row label={`${t.healthEr} (3.75%)`} value={result.employer.health} currency={t.currency} />
                  <div className="border-t border-blue-200 pt-2 flex justify-between">
                    <span className="text-sm font-semibold text-blue-900">{t.totalEmployer} (12.75%)</span>
                    <span className="text-sm font-bold text-blue-900">{fmt(result.employer.total)} {t.currency}</span>
                  </div>
                </div>

                {/* Total Cost */}
                <div className="rounded-xl bg-gradient-to-br from-indigo-50 to-purple-50 border border-indigo-200 p-4">
                  <div className="flex justify-between items-center">
                    <span className="text-base font-bold text-indigo-800">{t.totalCost}</span>
                    <span className="text-2xl font-extrabold text-indigo-700">{fmt(result.totalCost)} {t.currency}</span>
                  </div>
                </div>

                {/* Inline CTA */}
                <div className="flex items-center justify-between rounded-lg bg-indigo-600/5 border border-indigo-100 px-4 py-3">
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

function Row({ label, value, currency }: { label: string; value: number; currency: string }) {
  return (
    <div className="flex justify-between items-center">
      <span className="text-sm text-gray-600">{label}</span>
      <span className="text-sm font-medium text-gray-900">{fmt(value)} {currency}</span>
    </div>
  )
}
