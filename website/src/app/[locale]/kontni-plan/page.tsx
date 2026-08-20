import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'
import ChartBrowser from './ChartBrowser'
import { CHART_CLASSES, TOTAL_ACCOUNTS } from '@/data/chartOfAccounts'

const BASE_URL = 'https://www.facturino.mk'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/kontni-plan', {
    title: {
      mk: 'Контен план на Македонија 2026 — сите сметки (Правилник 174/2011)',
      en: 'Macedonian Chart of Accounts 2026 — Full Reference (Rulebook 174/2011)',
      sq: 'Plani kontabël i Maqedonisë 2026 — të gjitha llogaritë (174/2011)',
      tr: 'Makedonya Hesap Planı 2026 — tüm hesaplar (Yönetmelik 174/2011)',
    },
    description: {
      mk: `Целосен контен (сметковен) план на Северна Македонија — ${TOTAL_ACCOUNTS} аналитички 4-цифрени сметки според Правилник 174/2011. Пребарувајте по код или назив на сметка. Бесплатна референца за сметководители.`,
      en: `Complete Macedonian chart of accounts — ${TOTAL_ACCOUNTS} 4-digit analytical accounts per Rulebook 174/2011. Search by code or account name. Free reference for accountants.`,
      sq: `Plani i plotë kontabël i Maqedonisë së Veriut — ${TOTAL_ACCOUNTS} llogari analitike 4-shifrore sipas Rregullores 174/2011. Kërkoni sipas kodit ose emrit.`,
      tr: `Kuzey Makedonya tam hesap planı — Yönetmelik 174/2011'e göre ${TOTAL_ACCOUNTS} adet 4 haneli analitik hesap. Koda veya ada göre arayın.`,
    },
  })
}

const COPY = {
  mk: {
    badge: 'Референца',
    title: 'Контен план на Северна Македонија',
    sub: `Целосна референца на сметковниот план според Правилник 174/2011 — ${TOTAL_ACCOUNTS} аналитички 4-цифрени сметки. Пребарувајте, филтрирајте по класа и разгледувајте бесплатно.`,
    classesTitle: 'Класи на сметки',
    home: 'Почетна', label: 'Контен план',
    ctaTitle: 'Автоматско книжење според МК контен план',
    ctaSub: 'Facturino го користи официјалниот македонски контен план за автоматско книжење и извештаи.',
    cta: 'Пробај бесплатно →',
  },
  sq: {
    badge: 'Referencë',
    title: 'Plani kontabël i Maqedonisë së Veriut',
    sub: `Referencë e plotë e planit kontabël sipas Rregullores 174/2011 — ${TOTAL_ACCOUNTS} llogari analitike 4-shifrore. Kërkoni, filtroni sipas klasës dhe shfletoni falas.`,
    classesTitle: 'Klasat e llogarive',
    home: 'Ballina', label: 'Plani kontabël',
    ctaTitle: 'Kontabilizim automatik sipas planit kontabël MK',
    ctaSub: 'Facturino përdor planin zyrtar kontabël maqedonas për kontabilizim dhe raporte automatike.',
    cta: 'Provoni falas →',
  },
  tr: {
    badge: 'Referans',
    title: 'Kuzey Makedonya Hesap Planı',
    sub: `Yönetmelik 174/2011'e göre tam hesap planı referansı — ${TOTAL_ACCOUNTS} adet 4 haneli analitik hesap. Arayın, sınıfa göre filtreleyin ve ücretsiz inceleyin.`,
    classesTitle: 'Hesap sınıfları',
    home: 'Ana Sayfa', label: 'Hesap planı',
    ctaTitle: 'MK hesap planına göre otomatik kayıt',
    ctaSub: 'Facturino, otomatik kayıt ve raporlar için resmi Makedonya hesap planını kullanır.',
    cta: 'Ücretsiz deneyin →',
  },
  en: {
    badge: 'Reference',
    title: 'Macedonian Chart of Accounts',
    sub: `Complete chart-of-accounts reference per Rulebook 174/2011 — ${TOTAL_ACCOUNTS} 4-digit analytical accounts. Search, filter by class and browse for free.`,
    classesTitle: 'Account classes',
    home: 'Home', label: 'Chart of accounts',
    ctaTitle: 'Automatic posting to the MK chart of accounts',
    ctaSub: 'Facturino uses the official Macedonian chart of accounts for automatic posting and reports.',
    cta: 'Try it free →',
  },
} as const

export default async function KontniPlanPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = COPY[locale]

  const breadcrumbLd = breadcrumbJsonLd([
    { name: t.home, href: `/${locale}` },
    { name: t.label, href: `/${locale}/kontni-plan` },
  ])
  const collectionLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: t.title,
    url: `${BASE_URL}/${locale}/kontni-plan`,
    inLanguage: locale,
    isPartOf: { '@type': 'WebSite', name: 'Facturino', url: BASE_URL },
    about: 'Macedonian chart of accounts (Правилник 174/2011)',
  }

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(collectionLd) }} />

      <section className="bg-gradient-to-b from-indigo-50 to-white">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6 py-12 md:py-16">
          <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">{t.badge}</span>
          <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">{t.title}</h1>
          <p className="text-lg text-gray-600 max-w-2xl">{t.sub}</p>
        </div>
      </section>

      <section className="container max-w-4xl mx-auto px-4 sm:px-6">
        <h2 className="text-xl font-bold text-gray-900 mt-8 mb-4">{t.classesTitle}</h2>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-8">
          {CHART_CLASSES.map((c) => (
            <Link key={c.digit} href={`/${locale}/kontni-plan/klasa-${c.digit}`} className="card group hover:border-indigo-200 flex items-center gap-3">
              <span className="flex-shrink-0 w-9 h-9 rounded-lg bg-indigo-100 text-indigo-700 font-bold flex items-center justify-center">{c.digit}</span>
              <span className="text-sm font-medium text-gray-800 group-hover:text-indigo-600 transition-colors">{c.name[locale]}</span>
            </Link>
          ))}
        </div>
      </section>

      <section className="container max-w-4xl mx-auto px-4 sm:px-6 pb-16">
        <ChartBrowser locale={locale} />
      </section>

      <section className="bg-gradient-to-br from-indigo-600 to-cyan-500">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6 py-12 text-center text-white">
          <h2 className="text-2xl md:text-3xl font-extrabold mb-3">{t.ctaTitle}</h2>
          <p className="text-indigo-100 mb-6 text-lg">{t.ctaSub}</p>
          <a href="https://app.facturino.mk/signup" className="inline-block bg-white text-indigo-700 font-bold px-8 py-3.5 rounded-xl hover:bg-indigo-50 transition-colors text-lg shadow-lg">{t.cta}</a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
