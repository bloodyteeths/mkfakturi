import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, Locale, defaultLocale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import FAQ from '@/components/FAQ'
import ComparisonTable from '@/components/ComparisonTable'
import PageHero from '@/components/PageHero'
import PartnerPricingGrid from '@/components/PartnerPricingGrid'
import CompanyPricingGrid from '@/components/CompanyPricingGrid'

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

          <CompanyPricingGrid
            plans={companyPlans}
            popularBadge={popularBadge}
            cta={cta}
            includesPrevious={t.pricingPage!.includesPrevious}
            billingToggleMonthly={billingToggleMonthly || 'Monthly'}
            billingToggleYearly={billingToggleYearly || 'Yearly'}
            billingYearlySave={billingYearlySave || '2 months free'}
            sepaNote={sepaNote}
          />
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
