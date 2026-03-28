'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

const copy = {
  mk: {
    backLink: '← Сите алатки',
    badge: 'Бесплатно',
    h1: 'ДДВ Калкулатор',
    subtitle: 'Пресметајте данок на додадена вредност за сите стапки во Македонија — моментално, без регистрација.',
    amountLabel: 'Износ',
    currency: 'МКД',
    rateLabel: 'ДДВ стапка',
    rates: {
      '18': '18% — Стандардна',
      '5': '5% — Намалена',
      '10': '10% — Угостителство',
    },
    directionLabel: 'Пресметка',
    exclusive: 'Износот е БЕЗ ДДВ',
    inclusive: 'Износот ВКЛУЧУВА ДДВ',
    resultNet: 'Нето (без ДДВ)',
    resultVat: 'ДДВ',
    resultGross: 'Бруто (со ДДВ)',
    reverseCharge: 'Обратен пресмет (Reverse Charge)',
    reverseChargeDesc: 'При увоз на услуги од странство (чл. 12 ЗДДВ), купувачот го пресметува и плаќа ДДВ. Износот на ДДВ е 0 ден за продавачот, но купувачот го евидентира во ДДВ пријавата.',
    exempt: 'Ослободено од ДДВ',
    exemptDesc: 'Одредени дејности се ослободени од ДДВ (чл. 23-36 ЗДДВ): здравство, образование, финансиски услуги, осигурување, изнајмување станбен простор.',
    ctaInline: 'Facturino автоматски го пресметува ДДВ на секоја фактура.',
    ctaButton: 'Пробај бесплатно',
    faqTitle: 'Најчесто поставувани прашања',
    faq: [
      {
        q: 'Колку е стандардната стапка на ДДВ во Македонија?',
        a: 'Стандардната стапка на ДДВ во Северна Македонија е 18%. Таа се применува на повеќето стоки и услуги. Намалената стапка од 5% важи за основни прехранбени производи, лекови, книги и други производи наведени во чл. 30 од ЗДДВ. Стапката од 10% важи за угостителски услуги.',
      },
      {
        q: 'Кога треба да се регистрирам за ДДВ?',
        a: 'Доколку вашиот вкупен промет во претходните 12 месеци надмине 2.000.000 денари (приближно 32.500 евра), должни сте да се регистрирате како ДДВ обврзник при Управата за јавни приходи (УЈП). Можете и доброволно да се регистрирате ако прометот е помал.',
      },
      {
        q: 'Како се пресметува ДДВ од бруто износ?',
        a: 'За да го извлечете ДДВ од бруто износ со стапка од 18%, поделете го бруто износот со 1.18, па одземете го нето износот од бруто. Пример: 11.800 ÷ 1.18 = 10.000 нето, ДДВ = 1.800 денари.',
      },
      {
        q: 'Дали софтверските услуги плаќаат 5% ДДВ?',
        a: 'Не. Софтверските услуги и IT консалтинг подлежат на стандардната стапка од 18% ДДВ. Намалената стапка од 5% важи само за производи и услуги таксативно наведени во Законот за ДДВ (храна, лекови, книги итн.).',
      },
      {
        q: 'Кој е рокот за поднесување ДДВ пријава?',
        a: 'ДДВ пријавата (Образец ДДВ-04) се поднесува до 25-ти во месецот по истекот на даночниот период. За месечни обврзници — секој месец. За тримесечни — на секои 3 месеци. Пријавата се поднесува електронски преку е-Даноци порталот на УЈП.',
      },
    ],
    ctaTitle: 'Автоматска пресметка на ДДВ',
    ctaSub: 'Facturino го пресметува ДДВ автоматски на секоја фактура, понуда и расход. Генерира е-фактура спремна за УЈП.',
    ctaMainButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    backLink: '← Të gjitha mjetet',
    badge: 'Falas',
    h1: 'Llogaritësi i TVSH-së',
    subtitle: 'Llogaritni tatimin mbi vlerën e shtuar për të gjitha normat në Maqedoni — menjëherë, pa regjistrim.',
    amountLabel: 'Shuma',
    currency: 'MKD',
    rateLabel: 'Norma TVSH',
    rates: {
      '18': '18% — Standarde',
      '5': '5% — E reduktuar',
      '10': '10% — Hoteleri',
    },
    directionLabel: 'Llogaritja',
    exclusive: 'Shuma është PA TVSH',
    inclusive: 'Shuma PËRFSHIN TVSH',
    resultNet: 'Neto (pa TVSH)',
    resultVat: 'TVSH',
    resultGross: 'Bruto (me TVSH)',
    reverseCharge: 'Ngarkesë e kundërt (Reverse Charge)',
    reverseChargeDesc: 'Kur importoni shërbime nga jashtë (neni 12 LTVSH), blerësi llogarit dhe paguan TVSH-në. Shuma TVSH është 0 den për shitësin, por blerësi e regjistron në deklaratën e TVSH-së.',
    exempt: 'E përjashtuar nga TVSH',
    exemptDesc: 'Aktivitete të caktuara janë të përjashtuara nga TVSH (neni 23-36 LTVSH): shëndetësi, arsim, shërbime financiare, sigurime, qiradhënie banesash.',
    ctaInline: 'Facturino automatikisht llogarit TVSH-në në çdo faturë.',
    ctaButton: 'Provo falas',
    faqTitle: 'Pyetjet më të shpeshta',
    faq: [
      {
        q: 'Sa është norma standarde e TVSH-së në Maqedoni?',
        a: 'Norma standarde e TVSH-së në Maqedoninë e Veriut është 18%. Ajo zbatohet për shumicën e mallrave dhe shërbimeve. Norma e reduktuar 5% vlen për ushqimet bazë, barnat, librat. Norma 10% vlen për shërbimet hoteliere.',
      },
      {
        q: 'Kur duhet të regjistrohem për TVSH?',
        a: 'Nëse qarkullimi juaj total në 12 muajt e fundit kalon 2,000,000 denarë (rreth 32,500 euro), jeni të detyruar të regjistroheni si obligues i TVSH-së pranë DAP. Mund të regjistroheni edhe vullnetarisht.',
      },
      {
        q: 'Si llogaritet TVSH nga shuma bruto?',
        a: 'Për të nxjerrë TVSH-në nga shuma bruto me normë 18%, ndani shumën bruto me 1.18, pastaj zbritni neto nga bruto. Shembull: 11,800 ÷ 1.18 = 10,000 neto, TVSH = 1,800 denarë.',
      },
      {
        q: 'A paguajnë shërbimet softuerike 5% TVSH?',
        a: 'Jo. Shërbimet softuerike dhe konsulenca IT i nënshtrohen normës standarde 18% TVSH. Norma 5% vlen vetëm për produkte dhe shërbime të listuara taksativisht në Ligjin e TVSH-së.',
      },
      {
        q: 'Cili është afati për dorëzimin e deklaratës TVSH?',
        a: 'Deklarata e TVSH-së (Formulari DDV-04) dorëzohet deri më 25 të muajit pas përfundimit të periudhës tatimore. Dorëzohet elektronikisht përmes portalit e-Tatimi të DAP.',
      },
    ],
    ctaTitle: 'Llogaritje automatike e TVSH-së',
    ctaSub: 'Facturino llogarit TVSH-në automatikisht në çdo faturë, ofertë dhe shpenzim. Gjeneron e-faturë gati për UJP.',
    ctaMainButton: 'Fillo falas — 14 ditë',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    backLink: '← Tüm araçlar',
    badge: 'Ücretsiz',
    h1: 'KDV Hesaplayıcı',
    subtitle: 'Makedonya\'daki tüm oranlar için katma değer vergisini hesaplayın — anında, kayıt gerekmez.',
    amountLabel: 'Tutar',
    currency: 'MKD',
    rateLabel: 'KDV oranı',
    rates: {
      '18': '%18 — Standart',
      '5': '%5 — İndirimli',
      '10': '%10 — Konaklama',
    },
    directionLabel: 'Hesaplama',
    exclusive: 'Tutar KDV HARİÇ',
    inclusive: 'Tutar KDV DAHİL',
    resultNet: 'Net (KDV hariç)',
    resultVat: 'KDV',
    resultGross: 'Brüt (KDV dahil)',
    reverseCharge: 'Ters yükleme (Reverse Charge)',
    reverseChargeDesc: 'Yurt dışından hizmet ithal ederken (md. 12 KDVK), alıcı KDV\'yi hesaplar ve öder. Satıcı için KDV tutarı 0 den\'dir, ancak alıcı bunu KDV beyannamesinde beyan eder.',
    exempt: 'KDV\'den muaf',
    exemptDesc: 'Bazı faaliyetler KDV\'den muaftır (md. 23-36 KDVK): sağlık, eğitim, finansal hizmetler, sigorta, konut kiralama.',
    ctaInline: 'Facturino her faturada KDV\'yi otomatik hesaplar.',
    ctaButton: 'Ücretsiz dene',
    faqTitle: 'Sık sorulan sorular',
    faq: [
      {
        q: 'Makedonya\'da standart KDV oranı nedir?',
        a: 'Kuzey Makedonya\'da standart KDV oranı %18\'dir. Çoğu mal ve hizmet için geçerlidir. İndirimli %5 oranı temel gıda, ilaç, kitaplar için geçerlidir. %10 oranı konaklama hizmetleri içindir.',
      },
      {
        q: 'KDV için ne zaman kayıt yaptırmalıyım?',
        a: 'Son 12 aydaki toplam cironuz 2.000.000 denarı (yaklaşık 32.500 euro) aşarsa, GGİ\'ye KDV mükellefi olarak kayıt yaptırmanız zorunludur. Gönüllü olarak da kayıt yaptırabilirsiniz.',
      },
      {
        q: 'Brüt tutardan KDV nasıl hesaplanır?',
        a: '%18 oranlı brüt tutardan KDV çıkarmak için brüt tutarı 1,18\'e bölün, sonra netoyu brütten çıkarın. Örnek: 11.800 ÷ 1.18 = 10.000 net, KDV = 1.800 denar.',
      },
      {
        q: 'Yazılım hizmetleri %5 KDV mi öder?',
        a: 'Hayır. Yazılım hizmetleri ve BT danışmanlığı standart %18 KDV oranına tabidir. %5 oranı yalnızca KDV Kanunu\'nda sayılan ürün ve hizmetler için geçerlidir.',
      },
      {
        q: 'KDV beyannamesi verme süresi nedir?',
        a: 'KDV beyannamesi (Form DDV-04) vergi döneminin bitiminden sonraki ayın 25\'ine kadar verilir. GGİ\'nin e-Vergi portalı üzerinden elektronik olarak gönderilir.',
      },
    ],
    ctaTitle: 'Otomatik KDV hesaplama',
    ctaSub: 'Facturino her fatura, teklif ve giderde KDV\'yi otomatik hesaplar. UJP için hazır e-fatura oluşturur.',
    ctaMainButton: 'Ücretsiz başla — 14 gün',
    ctaSecondary: 'Demo planla',
  },
  en: {
    backLink: '← All tools',
    badge: 'Free',
    h1: 'VAT Calculator',
    subtitle: 'Calculate value-added tax for all Macedonian rates — instantly, no registration required.',
    amountLabel: 'Amount',
    currency: 'MKD',
    rateLabel: 'VAT rate',
    rates: {
      '18': '18% — Standard',
      '5': '5% — Reduced',
      '10': '10% — Hospitality',
    },
    directionLabel: 'Calculation',
    exclusive: 'Amount EXCLUDES VAT',
    inclusive: 'Amount INCLUDES VAT',
    resultNet: 'Net (excl. VAT)',
    resultVat: 'VAT',
    resultGross: 'Gross (incl. VAT)',
    reverseCharge: 'Reverse Charge',
    reverseChargeDesc: 'When importing services from abroad (Art. 12 VAT Law), the buyer calculates and pays the VAT. The VAT amount is 0 MKD for the seller, but the buyer records it in the VAT return.',
    exempt: 'VAT Exempt',
    exemptDesc: 'Certain activities are VAT exempt (Art. 23-36 VAT Law): healthcare, education, financial services, insurance, residential leasing.',
    ctaInline: 'Facturino automatically calculates VAT on every invoice.',
    ctaButton: 'Try for free',
    faqTitle: 'Frequently Asked Questions',
    faq: [
      {
        q: 'What is the standard VAT rate in Macedonia?',
        a: 'The standard VAT rate in North Macedonia is 18%. It applies to most goods and services. The reduced 5% rate applies to basic food, medicine, books and other items listed in Art. 30 of the VAT Law. The 10% rate applies to hospitality services.',
      },
      {
        q: 'When do I need to register for VAT?',
        a: 'If your total turnover in the previous 12 months exceeds 2,000,000 MKD (approximately €32,500), you are required to register as a VAT payer with the Public Revenue Office (UJP). You can also register voluntarily if your turnover is lower.',
      },
      {
        q: 'How is VAT calculated from a gross amount?',
        a: 'To extract VAT from a gross amount at 18%, divide the gross by 1.18, then subtract the net from gross. Example: 11,800 ÷ 1.18 = 10,000 net, VAT = 1,800 MKD.',
      },
      {
        q: 'Do software services pay 5% VAT?',
        a: 'No. Software services and IT consulting are subject to the standard 18% VAT rate. The 5% rate only applies to products and services explicitly listed in the VAT Law (food, medicine, books, etc.).',
      },
      {
        q: 'What is the deadline for filing VAT returns?',
        a: 'The VAT return (Form DDV-04) must be filed by the 25th of the month following the tax period. For monthly filers — every month. For quarterly — every 3 months. Filed electronically via UJP\'s e-Tax portal.',
      },
    ],
    ctaTitle: 'Automatic VAT calculation',
    ctaSub: 'Facturino automatically calculates VAT on every invoice, estimate, and expense. Generates e-invoices ready for UJP.',
    ctaMainButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

function formatNumber(n: number): string {
  return n.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

export default function VatCalculator({ locale }: { locale: Locale }) {
  const t = useMemo(() => copy[locale], [locale])
  const [amount, setAmount] = useState('')
  const [rate, setRate] = useState<'18' | '5' | '10'>('18')
  const [inclusive, setInclusive] = useState(false)

  const rateNum = parseInt(rate) / 100
  const amountNum = parseFloat(amount.replace(/,/g, '')) || 0

  let net: number, vat: number, gross: number
  if (inclusive) {
    gross = amountNum
    net = gross / (1 + rateNum)
    vat = gross - net
  } else {
    net = amountNum
    vat = net * rateNum
    gross = net + vat
  }

  const hasResult = amountNum > 0

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
    url: `https://www.facturino.mk/${locale}/alati/ddv-kalkulator`,
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
      { '@type': 'ListItem', position: 3, name: t.h1, item: `https://www.facturino.mk/${locale}/alati/ddv-kalkulator` },
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
            {/* Amount Input */}
            <div className="mb-6">
              <label htmlFor="vat-amount" className="block text-sm font-medium text-gray-700 mb-2">{t.amountLabel}</label>
              <div className="relative">
                <input
                  id="vat-amount"
                  type="text"
                  inputMode="decimal"
                  className="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 pr-16 text-lg font-semibold text-gray-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 focus:outline-none transition-colors"
                  placeholder="0.00"
                  value={amount}
                  onChange={(e) => {
                    const val = e.target.value.replace(/[^0-9.,]/g, '')
                    setAmount(val)
                  }}
                  autoFocus
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm font-medium text-gray-400">{t.currency}</span>
              </div>
            </div>

            {/* Rate Selection */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">{t.rateLabel}</label>
              <div className="grid grid-cols-3 gap-2">
                {(['18', '5', '10'] as const).map((r) => (
                  <button
                    key={r}
                    onClick={() => setRate(r)}
                    className={`px-3 py-2.5 rounded-xl text-sm font-semibold transition-all ${
                      rate === r
                        ? 'bg-indigo-600 text-white shadow-md'
                        : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                    }`}
                  >
                    {t.rates[r]}
                  </button>
                ))}
              </div>
            </div>

            {/* Direction Toggle */}
            <div className="mb-6">
              <label className="block text-sm font-medium text-gray-700 mb-2">{t.directionLabel}</label>
              <div className="grid grid-cols-2 gap-2">
                <button
                  onClick={() => setInclusive(false)}
                  className={`px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                    !inclusive
                      ? 'bg-indigo-600 text-white shadow-md'
                      : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                  }`}
                >
                  {t.exclusive}
                </button>
                <button
                  onClick={() => setInclusive(true)}
                  className={`px-3 py-2.5 rounded-xl text-sm font-medium transition-all ${
                    inclusive
                      ? 'bg-indigo-600 text-white shadow-md'
                      : 'bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200'
                  }`}
                >
                  {t.inclusive}
                </button>
              </div>
            </div>

            {/* Results */}
            {hasResult && (
              <div className="rounded-xl bg-gradient-to-br from-indigo-50 to-cyan-50 p-5 space-y-3 animate-[fadeIn_0.3s_ease-out]">
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultNet}</span>
                  <span className="text-lg font-bold text-gray-900">{formatNumber(net)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center border-y border-indigo-100 py-3">
                  <span className="text-sm font-medium text-indigo-700">{t.resultVat} ({rate}%)</span>
                  <span className="text-xl font-extrabold text-indigo-700">{formatNumber(vat)} {t.currency}</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-sm text-gray-600">{t.resultGross}</span>
                  <span className="text-lg font-bold text-gray-900">{formatNumber(gross)} {t.currency}</span>
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

      {/* Additional Info: Reverse Charge & Exempt */}
      <section className="pb-12 md:pb-16">
        <div className="container max-w-xl mx-auto px-4 sm:px-6 space-y-4">
          <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
            <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
              <h2 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">{t.reverseCharge}</h2>
              <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
              </span>
            </summary>
            <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.reverseChargeDesc}</div>
          </details>
          <details className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
            <summary className="flex items-center justify-between p-5 cursor-pointer list-none">
              <h2 className="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">{t.exempt}</h2>
              <span className="flex-shrink-0 w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" /></svg>
              </span>
            </summary>
            <div className="px-5 pb-5 text-gray-600 leading-relaxed text-sm">{t.exemptDesc}</div>
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
