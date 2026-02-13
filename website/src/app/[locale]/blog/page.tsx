import Link from 'next/link'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog', {
    title: {
      mk: 'Блог — Facturino',
      sq: 'Blog — Facturino',
      tr: 'Blog — Facturino',
      en: 'Blog — Facturino',
    },
    description: {
      mk: 'Водичи, совети и новости за сметководство во Македонија. Годишна сметка, биланси, AOP ознаки и дигитално сметководство со Facturino.',
      sq: 'Udhëzues, këshilla dhe lajme për kontabilitetin në Maqedoni. Llogaritë vjetore, pasqyrat financiare dhe kontabiliteti dixhital me Facturino.',
      tr: 'Makedonya muhasebesi hakkında rehberler, ipuçları ve haberler. Yıllık hesaplar, mali tablolar ve Facturino ile dijital muhasebe.',
      en: 'Guides, tips and news about accounting in Macedonia. Annual accounts, financial statements, AOP codes and digital accounting with Facturino.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    hero: {
      title: 'Блог',
      subtitle: 'Водичи, совети и новости за сметководство во Македонија',
    },
    articles: [
      {
        slug: 'godishna-smetka-2025',
        title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ',
        excerpt: 'Сè што треба да знаете за годишната сметка — рокови, обрасци, чекор-по-чекор процес за поднесување до Централниот регистар.',
        date: '15 јануари 2026',
        tag: 'Водич',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Биланс на состојба и биланс на успех: AOP ознаки и структура',
        excerpt: 'Детален преглед на Образец 36 и Образец 37 — структура, AOP ознаки, АКТИВА/ПАСИВА и приходи/расходи за годишна сметка.',
        date: '20 јануари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Годишно затворање на книги: 6 чекори со Facturino',
        excerpt: 'Како Facturino го автоматизира годишното затворање — од преглед до заклучување, со UJP-формат извештаи.',
        date: '25 јануари 2026',
        tag: 'Производ',
      },
    ],
    readMore: 'Прочитај повеќе',
    bottomCta: {
      title: 'Подготвени? Започнете за 2 минути.',
      subtitle: 'Бесплатен план — без кредитна картичка, без обврска.',
      cta: 'Креирај бесплатна сметка',
    },
  },
  sq: {
    hero: {
      title: 'Blog',
      subtitle: 'Udhëzues, këshilla dhe lajme për kontabilitetin në Maqedoni',
    },
    articles: [
      {
        slug: 'godishna-smetka-2025',
        title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim në QRMK',
        excerpt: 'Gjithçka që duhet të dini për llogaritë vjetore — afatet, formularët, procesi hap pas hapi.',
        date: '15 janar 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Bilanci dhe pasqyra e të ardhurave: Kodet AOP dhe struktura',
        excerpt: 'Pasqyrë e detajuar e Formularit 36 dhe 37 — struktura, kodet AOP, aktivet/detyrimet dhe të ardhurat/shpenzimet.',
        date: '20 janar 2026',
        tag: 'Edukim',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Mbyllja e vitit: 6 hapa me Facturino',
        excerpt: 'Si Facturino e automatizon mbylljen e vitit — nga rishikimi deri te kyçja, me raporte në format UJP.',
        date: '25 janar 2026',
        tag: 'Produkt',
      },
    ],
    readMore: 'Lexo më shumë',
    bottomCta: {
      title: 'Gati? Filloni në 2 minuta.',
      subtitle: 'Plan falas — pa kartë krediti, pa detyrim.',
      cta: 'Krijo llogari falas',
    },
  },
  tr: {
    hero: {
      title: 'Blog',
      subtitle: 'Makedonya muhasebesi hakkında rehberler, ipuçları ve haberler',
    },
    articles: [
      {
        slug: 'godishna-smetka-2025',
        title: 'Yıllık hesaplar 2025: CRMS dosyalama rehberi',
        excerpt: 'Yıllık hesaplar hakkında bilmeniz gereken her şey — son tarihler, formlar, adım adım süreç.',
        date: '15 Ocak 2026',
        tag: 'Rehber',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Bilanço ve gelir tablosu: AOP kodları ve yapı',
        excerpt: 'Form 36 ve Form 37 detaylı inceleme — yapı, AOP kodları, varlıklar/yükümlülükler.',
        date: '20 Ocak 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Yıl sonu kapanışı: Facturino ile 6 adım',
        excerpt: 'Facturino yıl sonu kapanışını nasıl otomatikleştirir — incelemeden kilitlemeye, UJP formatında raporlar.',
        date: '25 Ocak 2026',
        tag: 'Ürün',
      },
    ],
    readMore: 'Devamını oku',
    bottomCta: {
      title: 'Hazır mısınız? 2 dakikada başlayın.',
      subtitle: 'Ücretsiz plan — kredi kartı yok, zorunluluk yok.',
      cta: 'Ücretsiz hesap oluştur',
    },
  },
  en: {
    hero: {
      title: 'Blog',
      subtitle: 'Guides, tips and news about accounting in Macedonia',
    },
    articles: [
      {
        slug: 'godishna-smetka-2025',
        title: 'Annual Accounts 2025: Complete Filing Guide for CRMS',
        excerpt: 'Everything you need to know about annual accounts — deadlines, forms, step-by-step CRMS filing process.',
        date: 'January 15, 2026',
        tag: 'Guide',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Balance Sheet & Income Statement: AOP Codes and Structure',
        excerpt: 'Detailed overview of Form 36 and Form 37 — structure, AOP codes, assets/liabilities and revenues/expenses.',
        date: 'January 20, 2026',
        tag: 'Education',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Year-End Closing: 6 Steps with Facturino',
        excerpt: 'How Facturino automates year-end closing — from review to lock, with UJP-format reports.',
        date: 'January 25, 2026',
        tag: 'Product',
      },
    ],
    readMore: 'Read more',
    bottomCta: {
      title: 'Ready? Get started in 2 minutes.',
      subtitle: 'Free plan — no credit card, no commitment.',
      cta: 'Create a free account',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function BlogPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ============================================================ */}
      {/*  HERO                                                        */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-16 md:pb-20">
        {/* Background blobs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>

        <div className="container relative z-10 text-center max-w-3xl mx-auto px-4 sm:px-6">
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            {t.hero.title}
          </h1>

          <p className="text-lg md:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto">
            {t.hero.subtitle}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  ARTICLES GRID                                               */}
      {/* ============================================================ */}
      <section className="py-16 md:py-24">
        <div className="container px-4 sm:px-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {t.articles.map((article) => (
              <Link
                key={article.slug}
                href={`/${locale}/blog/${article.slug}`}
                className="group flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden"
              >
                {/* Tag + Date header */}
                <div className="px-6 pt-6 pb-0 flex items-center justify-between">
                  <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">
                    {article.tag}
                  </span>
                  <span className="text-sm text-gray-400">{article.date}</span>
                </div>

                {/* Content */}
                <div className="flex flex-col flex-1 px-6 pt-4 pb-6">
                  <h2 className="text-lg font-bold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors leading-snug">
                    {article.title}
                  </h2>
                  <p className="text-gray-500 text-sm leading-relaxed flex-1">
                    {article.excerpt}
                  </p>
                  <span className="mt-4 inline-flex items-center text-sm font-semibold text-indigo-600 group-hover:text-cyan-600 transition-colors">
                    {t.readMore}
                    <svg
                      className="ml-1 w-4 h-4 group-hover:translate-x-1 transition-transform"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2}
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                      />
                    </svg>
                  </span>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.bottomCta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.bottomCta.subtitle}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.bottomCta.cta}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
