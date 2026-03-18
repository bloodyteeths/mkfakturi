import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, Locale, defaultLocale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import FAQ from '@/components/FAQ'
import ComparisonTable from '@/components/ComparisonTable'
import PageHero from '@/components/PageHero'
import PartnerPricingGrid from '@/components/PartnerPricingGrid'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/pricing', {
    title: {
      mk: 'Цени и пакети — Facturino',
      sq: 'Cmimet dhe Planet — Facturino',
      tr: 'Fiyatlar ve Paketler — Facturino',
      en: 'Pricing & Plans — Facturino',
    },
    description: {
      mk: 'Бесплатен план, Standard од 2,400 ден/месец, без кредитна картичка. Споредете ги сите пакети на Facturino и започнете бесплатно 14 дена.',
      sq: 'Plan falas, Standard nga 2,400 den/muaj, pa kartë krediti. Krahasoni të gjitha paketat e Facturino dhe filloni falas për 14 ditë.',
      tr: 'Ücretsiz plan, Standard 2,400 den/ay\'dan başlayan fiyatlar, kredi kartı gerekmez. Facturino paketlerini karşılaştırın, 14 gün ücretsiz deneyin.',
      en: 'Free plan, Standard from 2,400 MKD/month, no credit card required. Compare all Facturino plans and start your free 14-day trial today.',
    },
  })
}

export default async function PricingPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = await getDictionary(locale)

  if (!t.pricingPage) return null
  const { h1, sub, sectionCompany, sectionPartner, popularBadge, recommendedBadge, partnerSubtitle, companyPlans, partnerPlans, cta, ctaPartner, sepaNote, billingToggleMonthly, billingToggleYearly, billingYearlySave } = t.pricingPage

  const softwareAppLd = {
    '@context': 'https://schema.org',
    '@type': 'SoftwareApplication',
    name: 'Facturino',
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    url: 'https://www.facturino.mk',
    offers: companyPlans.map((p) => ({
      '@type': 'Offer',
      name: p.name,
      price: p.price.replace(/[^\d]/g, ''),
      priceCurrency: 'MKD',
      description: p.bullets.join(', '),
    })),
  }

  return (
    <main id="main-content" className="min-h-screen bg-slate-50">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(softwareAppLd) }}
      />
      {/* Hero Section */}
      <PageHero
        image="/assets/images/hero_pricing.png"
        alt="Entrepreneur reviewing pricing plans on tablet in modern office"
        title={h1}
        subtitle={sub}
      />

      <div className="container -mt-10 relative z-20 pb-20">
        {/* Company Pricing */}
        <div className="mb-12 md:mb-24">
          <div className="flex items-center justify-center gap-4 mb-6 md:mb-12">
            <h2 className="text-2xl font-bold text-gray-900">{sectionCompany}</h2>
            <div className="h-px w-12 bg-gray-200"></div>
          </div>

          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            {companyPlans.map((p, i) => {
              const previousPlanName = i > 0 ? companyPlans[i - 1].name : null
              const bullets = previousPlanName
                ? [t.pricingPage!.includesPrevious.replace('{plan}', previousPlanName), ...p.bullets]
                : p.bullets

              return (
                <div key={i} className={`relative flex flex-col bg-white rounded-2xl shadow-sm border transition-all duration-300 hover:shadow-xl hover:-translate-y-1 ${p.popular ? 'border-indigo-500 ring-1 ring-indigo-500 z-10 scale-105' : 'border-gray-200'}`}>
                  {p.popular && (
                    <div className="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500 px-4 py-1 text-xs font-bold text-white whitespace-nowrap shadow-md">
                      {popularBadge}
                    </div>
                  )}

                  <div className="p-6 flex-grow">
                    <h3 className="mb-4 text-lg font-bold text-gray-900">{p.name}</h3>
                    <div className="mb-6">
                      <span className="text-4xl font-extrabold text-gray-900">{p.price}</span>
                      <span className="text-sm text-gray-500 font-medium">{p.period}</span>
                    </div>

                    <ul className="space-y-4 mb-8">
                      {bullets.map((b, j) => (
                        <li key={j} className="flex items-start text-sm text-gray-600">
                          <svg className="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                          <span>{b}</span>
                        </li>
                      ))}
                    </ul>
                  </div>

                  <div className="p-6 pt-0 mt-auto">
                    <a
                      href="https://app.facturino.mk/signup"
                      className={`block w-full py-3 px-4 rounded-xl text-center font-bold transition-all ${p.popular
                        ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg hover:shadow-indigo-500/30'
                        : 'bg-gray-50 text-gray-900 hover:bg-gray-100 border border-gray-200'
                        }`}
                    >
                      {cta}
                    </a>
                  </div>
                </div>
              )
            })}
          </div>

          {/* SEPA Note */}
          {sepaNote && (
            <div className="mt-8 text-center">
              <p className="inline-flex items-center gap-2 text-sm text-gray-500 bg-white border border-gray-200 rounded-full px-5 py-2.5 shadow-sm">
                <svg className="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                {sepaNote}
              </p>
            </div>
          )}
        </div>

        {/* Partner Pricing */}
        <div>
          <div className="flex items-center justify-center gap-4 mb-2 md:mb-4">
            <h2 className="text-2xl font-bold text-gray-900">{sectionPartner}</h2>
            <div className="h-px w-12 bg-gray-200"></div>
          </div>
          <p className="text-center text-sm text-gray-500 mb-6 md:mb-8">{partnerSubtitle}</p>

          <PartnerPricingGrid
            plans={partnerPlans}
            popularBadge={popularBadge}
            ctaPartner={ctaPartner}
            includesPrevious={t.pricingPage!.includesPrevious}
            billingToggleMonthly={billingToggleMonthly || 'Monthly'}
            billingToggleYearly={billingToggleYearly || 'Yearly'}
            billingYearlySave={billingYearlySave || '2 months free'}
          />
        </div>
      </div>



      <ComparisonTable t={t} />

      <FAQ t={t} />
    </main >
  )
}
