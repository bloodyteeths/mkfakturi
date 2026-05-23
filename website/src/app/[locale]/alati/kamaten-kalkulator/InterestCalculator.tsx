'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'Калкулатор за казнена камата',
    subtitle: 'Пресметајте законска казнена камата за задоцнети плаќања — моментално, без регистрација.',
    principalLabel: 'Главнина (долг)',
    currency: 'МКД',
    startDateLabel: 'Датум на доспевање',
    endDateLabel: 'Датум на пресметка',
    rateLabel: 'Годишна каматна стапка (%)',
    rateHint: 'Законска стапка: референтна стапка на НБРСМ + 8 п.п. = 13,25%',
    resultDays: 'Број на денови',
    resultDailyInterest: 'Дневна камата',
    resultTotalInterest: 'Вкупна камата',
    resultTotalAmount: 'Вкупен износ (главнина + камата)',
    ctaInline: 'Facturino автоматски пресметува казнена камата на задоцнети фактури.',
    ctaButton: 'Пробај бесплатно',
    eduTitle: 'Кога се применува казнена камата?',
    edu: [
      {
        heading: 'Задоцнето плаќање на фактури',
        text: 'Кога должникот не ја плаќа фактурата во договорениот рок, доверителот има право на казнена камата од денот на доспевање до денот на плаќање. Ова се применува автоматски без потреба од посебна договорна клаузула.',
      },
      {
        heading: 'Судски пресуди и решенија',
        text: 'По донесување на судска пресуда или извршно решение, должникот плаќа казнена камата на досудениот износ од денот на пресудата до денот на целосно плаќање.',
      },
      {
        heading: 'Даночни долгови',
        text: 'Управата за јавни приходи (УЈП) пресметува казнена камата за задоцнето плаќање на даноци, придонеси и други јавни давачки. Стапката е иста — референтна стапка на НБРСМ + 8 п.п.',
      },
    ],
    legalTitle: 'Правна основа',
    legalRate: 'Законска стапка',
    legalRateDesc: 'Според Законот за облигациони односи (ЗОО), член 266-а, стапката на казнена камата е еднаква на референтната стапка на НБРСМ зголемена за 8 процентни поени. Тековна стапка: 5,25% + 8 п.п. = 13,25% годишно.',
    legalClaim: 'Како да побарате казнена камата на фактура',
    legalClaimDesc: 'На фактурата наведете рок за плаќање и напомена дека по истекот на рокот ќе се пресметува казнена камата по законска стапка. Пример: „По истекот на рокот за плаќање се пресметува казнена камата согласно чл. 266-а од ЗОО."',
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Колку е законската стапка на казнена камата во Македонија?',
        a: 'Законската стапка на казнена камата во Северна Македонија е 13,25% годишно (од 2024). Таа се пресметува како референтна стапка на Народна банка (НБРСМ) од 5,25% зголемена за 8 процентни поени, согласно член 266-а од Законот за облигациони односи (ЗОО).',
      },
      {
        q: 'Од кој ден почнува да тече казнената камата?',
        a: 'Казнената камата почнува да тече од првиот ден по истекот на рокот за плаќање (датумот на доспевање). Ако фактурата има рок 15 дена, каматата тече од 16-тиот ден. Кај судски пресуди — од денот на донесување на пресудата.',
      },
      {
        q: 'Дали можам да наплатам казнена камата на моите фактури?',
        a: 'Да. Според ЗОО, доверителот има законско право на казнена камата при секое задоцнето плаќање, без потреба од посебен договор. Доволно е на фактурата да наведете рок за плаќање. Каматата се пресметува автоматски по законска стапка.',
      },
      {
        q: 'Дали казнената камата е оданочива?',
        a: 'Да, казнената камата претставува приход за доверителот и подлежи на данок на добивка (10%) за правни лица, односно персонален данок на доход (10%) за физички лица. Казнената камата не подлежи на ДДВ бидејќи не е надоместок за испорака на стока или услуга.',
      },
    ],
    ctaTitle: 'Автоматска пресметка на казнена камата',
    ctaSub: 'Facturino автоматски ја следи доспеаноста на фактурите и пресметува казнена камата за задоцнети плаќања.',
    ctaMainButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    backLink: '← Te gjitha mjetet',
    badge: 'Falas',
    h1: 'Llogaritesi i kamates ndeshkuese',
    subtitle: 'Llogaritni kamaten ligjore ndeshkuese per pagesa te vonuara — menjehere, pa regjistrim.',
    principalLabel: 'Shuma kryesore (borxhi)',
    currency: 'MKD',
    startDateLabel: 'Data e maturimit',
    endDateLabel: 'Data e llogaritjes',
    rateLabel: 'Norma vjetore e kamatos (%)',
    rateHint: 'Norma ligjore: norma referuese e BPRMV + 8 p.p. = 13,25%',
    resultDays: 'Numri i diteve',
    resultDailyInterest: 'Kamata ditore',
    resultTotalInterest: 'Kamata totale',
    resultTotalAmount: 'Shuma totale (kryegjeja + kamata)',
    ctaInline: 'Facturino automatikisht llogarit kamaten ndeshkuese per faturat e vonuara.',
    ctaButton: 'Provo falas',
    eduTitle: 'Kur zbatohet kamata ndeshkuese?',
    edu: [
      {
        heading: 'Pagesa e vonuar e faturave',
        text: 'Kur debitori nuk e paguan faturen brenda afatit te rene dakord, kreditori ka te drejte per kamate ndeshkuese nga data e maturimit deri ne diten e pageses. Kjo zbatohet automatikisht pa nevoje per klauzole kontraktuale.',
      },
      {
        heading: 'Vendime gjyqesore',
        text: 'Pas marrjes se vendimit gjyqesor ose urdhrit te ekzekutimit, debitori paguan kamate ndeshkuese mbi shumen e gjykuar nga data e vendimit deri ne pagesen e plote.',
      },
      {
        heading: 'Borxhe tatimore',
        text: 'Drejtoria e te Ardhurave Publike (DAP) llogarit kamate ndeshkuese per pagesa te vonuara te tatimeve, kontributeve dhe detyrimeve te tjera publike. Norma eshte e njejte — norma referuese e BPRMV + 8 p.p.',
      },
    ],
    legalTitle: 'Baza ligjore',
    legalRate: 'Norma ligjore',
    legalRateDesc: 'Sipas Ligjit per Marredheniet e Detyrimeve (LMD), neni 266-a, norma e kamatos ndeshkuese eshte e barabarte me normen referuese te BPRMV te rritur per 8 pike perqindjeje. Norma aktuale: 5,25% + 8 p.p. = 13,25% vjetore.',
    legalClaim: 'Si te kerkoni kamate ndeshkuese ne fature',
    legalClaimDesc: 'Ne fature shënoni afatin e pageses dhe shenimin qe pas afatit do te llogaritet kamata ndeshkuese me normen ligjore. Shembull: "Pas afatit te pageses llogaritet kamata ndeshkuese sipas nenit 266-a te LMD."',
    faqTitle: 'Pyetjet me te shpeshta',
    faq: [
      {
        q: 'Sa eshte norma ligjore e kamatos ndeshkuese ne Maqedoni?',
        a: 'Norma ligjore e kamatos ndeshkuese ne Maqedonine e Veriut eshte 13,25% ne vit (nga 2024). Ajo llogaritet si norma referuese e Bankes Popullore (BPRMV) prej 5,25% e rritur per 8 pike perqindjeje, sipas nenit 266-a te Ligjit per Marredheniet e Detyrimeve (LMD).',
      },
      {
        q: 'Nga cila dite fillon te ecë kamata ndeshkuese?',
        a: 'Kamata ndeshkuese fillon te ecë nga dita e pare pas kalimit te afatit te pageses (data e maturimit). Nese fatura ka afat 15 dite, kamata ec nga dita e 16-te. Per vendime gjyqesore — nga dita e marrjes se vendimit.',
      },
      {
        q: 'A mund te ngarkoj kamate ndeshkuese ne faturat e mia?',
        a: 'Po. Sipas LMD, kreditori ka te drejte ligjore per kamate ndeshkuese per cdo pagese te vonuar, pa nevoje per marreveshje te vecante. Mjafton te shenoni afatin e pageses ne fature. Kamata llogaritet automatikisht me normen ligjore.',
      },
      {
        q: 'A eshte e tatueshme kamata ndeshkuese?',
        a: 'Po, kamata ndeshkuese perfaqeson te ardhur per kreditorin dhe i nenshtrohet tatimit mbi fitimin (10%) per personat juridike, perkatesisht tatimit mbi te ardhurat personale (10%) per personat fizike. Kamata ndeshkuese nuk i nenshtrohet TVSH-se.',
      },
    ],
    ctaTitle: 'Llogaritje automatike e kamatos ndeshkuese',
    ctaSub: 'Facturino automatikisht ndjek maturimin e faturave dhe llogarit kamaten ndeshkuese per pagesa te vonuara.',
    ctaMainButton: 'Fillo falas — 14 dite',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    backLink: '← Tum araclar',
    badge: 'Ucretsiz',
    h1: 'Gecikme Faizi Hesaplayicisi',
    subtitle: 'Geciken odemeler icin yasal gecikme faizini hesaplayin — aninda, kayit gerekmez.',
    principalLabel: 'Anapara (borc)',
    currency: 'MKD',
    startDateLabel: 'Vade tarihi',
    endDateLabel: 'Hesaplama tarihi',
    rateLabel: 'Yillik faiz orani (%)',
    rateHint: 'Yasal oran: MBKM referans orani + 8 p.p. = %13,25',
    resultDays: 'Gun sayisi',
    resultDailyInterest: 'Gunluk faiz',
    resultTotalInterest: 'Toplam faiz',
    resultTotalAmount: 'Toplam tutar (anapara + faiz)',
    ctaInline: 'Facturino geciken faturalar icin gecikme faizini otomatik hesaplar.',
    ctaButton: 'Ucretsiz dene',
    eduTitle: 'Gecikme faizi ne zaman uygulanir?',
    edu: [
      {
        heading: 'Faturalarin gec odenmesi',
        text: 'Borclu faturayi kararlasilmis surede odemezse, alacakli vade tarihinden odeme gunune kadar gecikme faizi talep etme hakkina sahiptir. Bu, ozel bir sozlesme maddesi gerektirmeden otomatik olarak uygulanir.',
      },
      {
        heading: 'Mahkeme kararlari',
        text: 'Mahkeme karari veya icra emri verildikten sonra, borclu hukmedilen tutar uzerinden karar tarihinden tam odemeye kadar gecikme faizi oder.',
      },
      {
        heading: 'Vergi borclari',
        text: 'Gelir Idaresi (UJP) vergilerin, katkilarin ve diger kamu yukumlulerinin gec odenmesi icin gecikme faizi hesaplar. Oran aynidir — MBKM referans orani + 8 p.p.',
      },
    ],
    legalTitle: 'Yasal dayanak',
    legalRate: 'Yasal oran',
    legalRateDesc: 'Borclar Kanunu (BOK) madde 266-a uyarinca, gecikme faizi orani MBKM referans oranina 8 yuzde puani eklenerek belirlenir. Guncel oran: %5,25 + 8 p.p. = yillik %13,25.',
    legalClaim: 'Faturada gecikme faizi nasil talep edilir',
    legalClaimDesc: 'Faturada odeme suresi belirtin ve surenin dolmasindan sonra yasal oranla gecikme faizi hesaplanacagina dair not ekleyin. Ornek: "Odeme suresinin dolmasindan sonra BOK md. 266-a uyarinca gecikme faizi hesaplanacaktir."',
    faqTitle: 'Sik sorulan sorular',
    faq: [
      {
        q: 'Makedonya\'da yasal gecikme faizi orani nedir?',
        a: 'Kuzey Makedonya\'da yasal gecikme faizi orani yillik %13,25\'tir (2024 itibariyle). Merkez Bankasi (MBKM) referans orani %5,25\'e 8 yuzde puani eklenerek, Borclar Kanunu (BOK) madde 266-a uyarinca hesaplanir.',
      },
      {
        q: 'Gecikme faizi hangi gunden itibaren isler?',
        a: 'Gecikme faizi, odeme suresinin (vade tarihinin) sona erdigi gunden itibaren isler. Fatura 15 gunluk vadeli ise faiz 16. gunden baslar. Mahkeme kararlarinda — karar tarihinden itibaren.',
      },
      {
        q: 'Faturalarimda gecikme faizi talep edebilir miyim?',
        a: 'Evet. BOK uyarinca alacakli, ozel bir sozlesme olmaksizin her geciken odeme icin gecikme faizi talep etme yasal hakkina sahiptir. Faturada odeme suresi belirtmeniz yeterlidir. Faiz yasal oranla otomatik hesaplanir.',
      },
      {
        q: 'Gecikme faizi vergiye tabi midir?',
        a: 'Evet, gecikme faizi alacakli icin gelir niteligindedir ve tuzel kisiler icin kurumlar vergisine (%10), gercek kisiler icin gelir vergisine (%10) tabidir. Gecikme faizi mal veya hizmet teslimi karsiligi olmadigindan KDV\'ye tabi degildir.',
      },
    ],
    ctaTitle: 'Otomatik gecikme faizi hesaplama',
    ctaSub: 'Facturino fatura vadelerini otomatik takip eder ve geciken odemeler icin gecikme faizini hesaplar.',
    ctaMainButton: 'Ucretsiz basla — 14 gun',
    ctaSecondary: 'Demo planla',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'Penalty Interest Calculator',
    subtitle: 'Calculate statutory penalty interest on late payments — instantly, no registration required.',
    principalLabel: 'Principal (debt)',
    currency: 'MKD',
    startDateLabel: 'Due date',
    endDateLabel: 'Calculation date',
    rateLabel: 'Annual interest rate (%)',
    rateHint: 'Statutory rate: NBRSM reference rate + 8 p.p. = 13.25%',
    resultDays: 'Number of days',
    resultDailyInterest: 'Daily interest',
    resultTotalInterest: 'Total interest',
    resultTotalAmount: 'Total amount (principal + interest)',
    ctaInline: 'Facturino automatically calculates penalty interest on overdue invoices.',
    ctaButton: 'Try for free',
    eduTitle: 'When does penalty interest apply?',
    edu: [
      {
        heading: 'Late payment on invoices',
        text: 'When the debtor fails to pay an invoice within the agreed term, the creditor has the right to penalty interest from the due date until the day of payment. This applies automatically without requiring a special contractual clause.',
      },
      {
        heading: 'Court judgments and rulings',
        text: 'After a court judgment or enforcement order is issued, the debtor pays penalty interest on the awarded amount from the date of the judgment until full payment.',
      },
      {
        heading: 'Tax debts',
        text: 'The Public Revenue Office (UJP) calculates penalty interest on late payment of taxes, contributions, and other public levies. The rate is the same — NBRSM reference rate + 8 percentage points.',
      },
    ],
    legalTitle: 'Legal basis',
    legalRate: 'Statutory rate',
    legalRateDesc: 'Under the Law on Obligations (LOO), Article 266-a, the penalty interest rate equals the NBRSM reference rate plus 8 percentage points. Current rate: 5.25% + 8 p.p. = 13.25% annually.',
    legalClaim: 'How to claim penalty interest on an invoice',
    legalClaimDesc: 'On the invoice, state the payment term and add a note that penalty interest at the statutory rate will accrue after the deadline. Example: "After the payment deadline, penalty interest will be calculated pursuant to Art. 266-a of the LOO."',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      {
        q: 'What is the statutory penalty interest rate in Macedonia?',
        a: 'The statutory penalty interest rate in North Macedonia is 13.25% annually (as of 2024). It is calculated as the National Bank (NBRSM) reference rate of 5.25% plus 8 percentage points, pursuant to Article 266-a of the Law on Obligations (LOO).',
      },
      {
        q: 'When does penalty interest start accruing?',
        a: 'Penalty interest starts accruing from the first day after the payment deadline (due date) has passed. If an invoice has a 15-day term, interest runs from the 16th day. For court judgments — from the date the judgment is issued.',
      },
      {
        q: 'Can I charge penalty interest on my invoices?',
        a: 'Yes. Under the LOO, the creditor has a legal right to penalty interest on any late payment, without the need for a special agreement. It is sufficient to state the payment term on the invoice. Interest is calculated automatically at the statutory rate.',
      },
      {
        q: 'Is penalty interest taxable?',
        a: 'Yes, penalty interest constitutes income for the creditor and is subject to corporate income tax (10%) for legal entities, or personal income tax (10%) for individuals. Penalty interest is not subject to VAT as it is not consideration for a supply of goods or services.',
      },
    ],
    ctaTitle: 'Automatic penalty interest calculation',
    ctaSub: 'Facturino automatically tracks invoice due dates and calculates penalty interest on overdue payments.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

function formatNumber(n: number): string {
  return n.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(date: Date, locale: string): string {
  return date.toLocaleDateString(locale === 'mk' ? 'mk-MK' : locale === 'sq' ? 'sq-AL' : locale === 'tr' ? 'tr-TR' : 'en-GB', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

function todayString(): string {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

function thirtyDaysAgo(): string {
  const d = new Date()
  d.setDate(d.getDate() - 30)
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
}

export default function InterestCalculator({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])
  const [principal, setPrincipal] = useState('')
  const [startDate, setStartDate] = useState(thirtyDaysAgo)
  const [endDate, setEndDate] = useState(todayString)
  const [rate, setRate] = useState('13.25')

  const principalNum = parseFloat(principal.replace(/,/g, '')) || 0
  const rateNum = parseFloat(rate.replace(/,/g, '')) || 0

  const days = useMemo(() => {
    const start = new Date(startDate)
    const end = new Date(endDate)
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return 0
    const diff = end.getTime() - start.getTime()
    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)))
  }, [startDate, endDate])

  const dailyInterest = principalNum * (rateNum / 100) / 365
  const totalInterest = principalNum * (rateNum / 100) * days / 365
  const totalAmount = principalNum + totalInterest
  const hasResult = principalNum > 0 && days > 0

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
    url: `https://www.facturino.mk/${locale}/alati/kamaten-kalkulator`,
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
      { '@type': 'ListItem', position: 2, name: locale === 'mk' ? 'Алатки' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araclar' : 'Tools', item: `https://www.facturino.mk/${locale}/alati` },
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/kamaten-kalkulator` },
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
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite]" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-[float_6s_ease-in-out_infinite_2s]" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/alati`} className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors">
            {t.backLink}
          </Link>
          <span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600 mb-4">
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
            {/* Principal Input */}
            <div className="mb-6">
              <label htmlFor="interest-principal" className="block text-sm font-medium text-gray-700 mb-2">{t.principalLabel}</label>
              <div className="relative">
                <input
                  id="interest-principal"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  placeholder="0.00"
                  value={principal}
                  onChange={(e) => {
                    const val = e.target.value.replace(/[^0-9.,]/g, '')
                    setPrincipal(val)
                  }}
                  autoFocus
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
              </div>
            </div>

            {/* Date Inputs */}
            <div className="grid grid-cols-2 gap-4 mb-6">
              <div>
                <label htmlFor="interest-start" className="block text-sm font-medium text-gray-700 mb-2">{t.startDateLabel}</label>
                <input
                  id="interest-start"
                  type="date"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                />
              </div>
              <div>
                <label htmlFor="interest-end" className="block text-sm font-medium text-gray-700 mb-2">{t.endDateLabel}</label>
                <input
                  id="interest-end"
                  type="date"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                />
              </div>
            </div>

            {/* Rate Input */}
            <div className="mb-6">
              <label htmlFor="interest-rate" className="block text-sm font-medium text-gray-700 mb-2">{t.rateLabel}</label>
              <div className="relative">
                <input
                  id="interest-rate"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-10 text-lg font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  value={rate}
                  onChange={(e) => {
                    const val = e.target.value.replace(/[^0-9.,]/g, '')
                    setRate(val)
                  }}
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">%</span>
              </div>
              <p className="mt-1.5 text-xs text-gray-500">{t.rateHint}</p>
            </div>

            {/* Results */}
            {hasResult && (
              <div className="rounded-xl bg-gradient-to-br from-indigo-50 to-cyan-50 p-5 space-y-3 animate-[fadeIn_0.3s_ease-out]">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultDays}</span>
                  <span className="text-lg font-bold text-gray-900">{days}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultDailyInterest}</span>
                  <span className="text-base font-semibold text-gray-900">{formatNumber(dailyInterest)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center border-y border-indigo-100 py-3">
                  <span className="text-sm font-medium text-indigo-700">{t.resultTotalInterest}</span>
                  <span className="text-xl font-extrabold text-indigo-700">{formatNumber(totalInterest)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultTotalAmount}</span>
                  <span className="text-lg font-bold text-gray-900">{formatNumber(totalAmount)} {t.currency}</span>
                </div>
              </div>
            )}

            {/* Inline CTA */}
            {hasResult && (
              <div className="mt-4 flex items-center justify-between rounded-lg bg-indigo-600/5 border border-indigo-100 px-4 py-3">
                <p className="text-sm text-gray-700">{t.ctaInline}</p>
                <a
                  href={`${APP_URL}/signup`}
                  className="ml-3 flex-shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors whitespace-nowrap"
                >
                  {t.ctaButton} →
                </a>
              </div>
            )}
          </div>
        </div>
      </section>

      {/* Educational Content */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8">{t.eduTitle}</h2>
          <div className="grid gap-6 md:grid-cols-3">
            {t.edu.map((item, i) => (
              <div key={i} className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4 font-bold text-lg">
                  {i + 1}
                </div>
                <h3 className="text-base font-semibold text-gray-900 mb-2">{item.heading}</h3>
                <p className="text-sm text-gray-600 leading-relaxed">{item.text}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Legal Basis */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-xl mx-auto px-4 sm:px-6 space-y-4">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6">{t.legalTitle}</h2>
          <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
            <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
              <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">{t.legalRate}</h3>
              <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
              </span>
            </summary>
            <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.legalRateDesc}</div>
          </details>
          <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
            <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
              <h3 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">{t.legalClaim}</h3>
              <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
              </span>
            </summary>
            <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.legalClaimDesc}</div>
          </details>
        </div>
      </section>

      {/* FAQ Section */}
      <section className="section bg-slate-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">{t.faqTitle}</h2>
          <div className="space-y-4">
            {t.faq.map((item, i) => (
              <details key={i} className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
                <summary className="flex items-center justify-between p-6 cursor-pointer list-none">
                  <h3 className="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">
                    {item.q}
                  </h3>
                  <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
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
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-2xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">{t.ctaTitle}</h2>
          <p className="text-xl text-indigo-100 mb-8">{t.ctaSub}</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href={`${APP_URL}/signup`} className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
              {t.ctaMainButton}
            </a>
            <Link href={`/${locale}/contact`} className="px-8 py-4 bg-indigo-700/50 text-white border border-white/20 rounded-xl font-bold text-lg hover:bg-indigo-700/70 transition-all duration-300 backdrop-blur-sm">
              {t.ctaSecondary}
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}
