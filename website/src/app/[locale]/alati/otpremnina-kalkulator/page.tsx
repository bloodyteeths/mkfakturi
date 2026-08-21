import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import OtpremninaCalculator from './OtpremninaCalculator'

const BASE_URL = 'https://www.facturino.mk'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/otpremnina-kalkulator', {
    title: {
      mk: 'Калкулатор за отпремнина 2026 — пресметка според ЗРО (Македонија)',
      en: 'Severance Calculator 2026 — Macedonia (Labour Law)',
      sq: 'Kalkulator i kompensimit (otpremnina) 2026 — Maqedoni',
      tr: 'Kıdem tazminatı hesaplayıcı 2026 — Makedonya',
    },
    description: {
      mk: 'Бесплатна пресметка на отпремнина при технолошки вишок или пензионирање, според Законот за работни односи. Внесете нето плата и стаж. Без регистрација.',
      en: 'Free severance calculation for redundancy or retirement under the Macedonian Labour Law. Enter net salary and years of service. No registration.',
      sq: 'Llogaritje falas e kompensimit për tepricë ose pensionim sipas Ligjit të Punës. Pa regjistrim.',
      tr: 'İş Kanununa göre işten çıkarma veya emeklilik kıdem tazminatı ücretsiz hesabı. Kayıt gerekmez.',
    },
  })
}

const HERO = {
  mk: { badge: 'Бесплатна алатка', title: 'Калкулатор за отпремнина', sub: 'Пресметајте отпремнина при технолошки вишок или пензионирање според Законот за работни односи. Внесете просечна нето плата и работен стаж.' },
  sq: { badge: 'Mjet falas', title: 'Kalkulator i kompensimit', sub: 'Llogaritni kompensimin (otpremnina) për tepricë teknologjike ose pensionim sipas Ligjit të Marrëdhënieve të Punës.' },
  tr: { badge: 'Ücretsiz araç', title: 'Kıdem tazminatı hesaplayıcı', sub: 'İş İlişkileri Kanununa göre teknolojik fazlalık veya emeklilik kıdem tazminatını hesaplayın.' },
  en: { badge: 'Free tool', title: 'Severance calculator', sub: 'Calculate severance for redundancy or retirement under the Macedonian Law on Labour Relations. Enter average net salary and years of service.' },
} as const

export default async function OtpremninaPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const h = HERO[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const toolsLabel = locale === 'mk' ? 'Алати' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araçlar' : 'Tools'

  const webAppLd = {
    '@context': 'https://schema.org', '@type': 'WebApplication', name: h.title,
    url: `${BASE_URL}/${locale}/alati/otpremnina-kalkulator`, applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web', inLanguage: locale, offers: { '@type': 'Offer', price: '0', priceCurrency: 'MKD' },
    publisher: { '@type': 'Organization', name: 'Facturino', url: BASE_URL },
  }
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: toolsLabel, href: `/${locale}/alati` },
    { name: h.title, href: `/${locale}/alati/otpremnina-kalkulator` },
  ])
  const faqLd = faqJsonLd([
    { question: 'Колкава е отпремнината при технолошки вишок?', answer: 'Според Чл. 97 од ЗРО, отпремнината зависи од работниот стаж кај истиот работодавач: 1 нето плата до 5 години, па се зголемува до 6 нето плати за над 25 години стаж.' },
    { question: 'Колкава е отпремнината при пензионирање?', answer: 'При заминување во пензија, работникот има право на отпремнина во висина од 2 просечни нето плати.' },
    { question: 'Која нето плата се зема за пресметка?', answer: 'Се зема просечната нето плата на работникот за последните шест месеци пред престанокот на работниот однос.' },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(webAppLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />

      <section className="bg-gradient-to-b from-indigo-50 to-white">
        <div className="container max-w-5xl mx-auto px-4 sm:px-6 py-10 md:py-14">
          <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">{h.badge}</span>
          <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">{h.title}</h1>
          <p className="text-lg text-gray-600 max-w-2xl">{h.sub}</p>
        </div>
      </section>

      <section className="container max-w-5xl mx-auto px-4 sm:px-6 py-8">
        <OtpremninaCalculator locale={locale} />
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
