import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import InvoiceGenerator from './InvoiceGenerator'

const BASE_URL = 'https://www.facturino.mk'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/faktura-generator', {
    title: {
      mk: 'Генератор на фактури — бесплатно направи фактура онлајн (МК)',
      en: 'Free Invoice Generator — Create an Invoice Online (Macedonia)',
      sq: 'Gjenerator faturash — krijoni faturë online falas (MK)',
      tr: 'Fatura Oluşturucu — çevrimiçi ücretsiz fatura (MK)',
    },
    description: {
      mk: 'Бесплатен генератор на фактури за Македонија. Направете професионална фактура со ДДВ (18%, 10%, 5%), испечатете или зачувајте PDF. Без регистрација.',
      en: 'Free invoice generator for Macedonia. Create a professional invoice with VAT (18%, 10%, 5%), print or save as PDF. No registration.',
      sq: 'Gjenerator falas faturash për Maqedoninë. Krijoni faturë profesionale me TVSH (18%, 10%, 5%), printoni ose ruani PDF. Pa regjistrim.',
      tr: 'Makedonya için ücretsiz fatura oluşturucu. KDV ile (18%, 10%, 5%) profesyonel fatura oluşturun, yazdırın veya PDF kaydedin. Kayıt yok.',
    },
  })
}

const HERO = {
  mk: { badge: 'Бесплатна алатка', title: 'Генератор на фактури', sub: 'Направете професионална фактура за неколку минути — со ДДВ пресметка според македонските стапки. Испечатете или зачувајте како PDF. Без регистрација.' },
  sq: { badge: 'Mjet falas', title: 'Gjenerator faturash', sub: 'Krijoni një faturë profesionale për pak minuta — me llogaritje TVSH sipas normave maqedonase. Printoni ose ruani si PDF. Pa regjistrim.' },
  tr: { badge: 'Ücretsiz araç', title: 'Fatura oluşturucu', sub: 'Birkaç dakikada profesyonel fatura oluşturun — Makedonya oranlarına göre KDV hesabıyla. Yazdırın veya PDF kaydedin. Kayıt yok.' },
  en: { badge: 'Free tool', title: 'Invoice generator', sub: 'Create a professional invoice in minutes — with VAT calculated at Macedonian rates. Print or save as PDF. No registration.' },
} as const

export default async function FakturaGeneratorPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const h = HERO[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const toolsLabel = locale === 'mk' ? 'Алати' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araçlar' : 'Tools'

  const webAppLd = {
    '@context': 'https://schema.org', '@type': 'WebApplication', name: h.title,
    url: `${BASE_URL}/${locale}/alati/faktura-generator`, applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web', inLanguage: locale, offers: { '@type': 'Offer', price: '0', priceCurrency: 'MKD' },
    publisher: { '@type': 'Organization', name: 'Facturino', url: BASE_URL },
  }
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: toolsLabel, href: `/${locale}/alati` },
    { name: h.title, href: `/${locale}/alati/faktura-generator` },
  ])
  const faqLd = faqJsonLd([
    { question: 'Дали генераторот на фактури е бесплатен?', answer: 'Да, целосно бесплатен и без регистрација. Пополнете ги податоците, испечатете или зачувајте како PDF.' },
    { question: 'Кои ДДВ стапки се поддржани?', answer: 'Поддржани се македонските ДДВ стапки: 18% (општа), 10%, 5% и 0%. ДДВ се пресметува автоматски по ставка.' },
    { question: 'Дали оваа фактура е е-фактура?', answer: 'Не. Ова е PDF/печатена фактура. За задолжителната УЈП е-фактура (UBL 2.1 + QES) користете ја платформата Facturino.' },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(webAppLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />

      <section className="no-print bg-gradient-to-b from-indigo-50 to-white">
        <div className="container max-w-6xl mx-auto px-4 sm:px-6 py-10 md:py-14">
          <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">{h.badge}</span>
          <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">{h.title}</h1>
          <p className="text-lg text-gray-600 max-w-2xl">{h.sub}</p>
        </div>
      </section>

      <section className="container max-w-6xl mx-auto px-4 sm:px-6 py-8">
        <InvoiceGenerator locale={locale} />
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
