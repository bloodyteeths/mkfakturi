import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Hero from '@/components/Hero'
import FeatureGrid from '@/components/FeatureGrid'
import AIShowcase from '@/components/AIShowcase'
import WhyDifferent from '@/components/WhyDifferent'
import Benefits from '@/components/Benefits'
import HowItWorks from '@/components/HowItWorks'
import PricingPreview from '@/components/PricingPreview'
import Testimonials from '@/components/Testimonials'
import FAQ from '@/components/FAQ'
import CTA from '@/components/CTA'
import Partners from '@/components/Partners'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/', {
    title: {
      mk: 'Facturino — AI сметководство и е-Фактура за Македонија',
      sq: 'Facturino — Platformë AI kontabiliteti, gati për e-Faturë',
      tr: 'Facturino — AI Muhasebe ve e-Fatura Platformu',
      en: 'Facturino — AI Accounting Platform for Macedonia',
    },
    description: {
      mk: 'AI сметководствена платформа подготвена за е-Фактура. Повеќе клиенти, банкарски увоз, IFRS извештаи. Започнете бесплатно за 14 дена.',
      sq: 'Platformë kontabiliteti me AI, gati për e-Faturë. Shumë-klientë, import bankar, raporte IFRS. Filloni provën falas 14-ditore tani.',
      tr: 'AI destekli muhasebe platformu, e-Fatura\'ya hazır. Çoklu müşteri, banka ekstresi içe aktarma, IFRS raporları. 14 gün ücretsiz deneyin.',
      en: 'AI-powered accounting platform ready for e-Invoice. Multi-client management, bank statement import, IFRS reports. Start your free 14-day trial today.',
    },
  })
}

export default async function Landing({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = await getDictionary(locale)

  const faqItems = t.faq?.items || []

  const organizationLd = {
    '@context': 'https://schema.org',
    '@type': 'Organization',
    name: 'Facturino',
    url: 'https://www.facturino.mk',
    logo: 'https://www.facturino.mk/brand/facturino_logo.png',
    description: 'AI-powered accounting platform for Macedonia',
    address: { '@type': 'PostalAddress', addressLocality: 'Skopje', addressCountry: 'MK' },
    sameAs: [],
  }

  const faqLd = faqItems.length > 0 ? {
    '@context': 'https://schema.org',
    '@type': 'FAQPage',
    mainEntity: faqItems.map((item) => ({
      '@type': 'Question',
      name: item.q,
      acceptedAnswer: {
        '@type': 'Answer',
        text: item.a,
      },
    })),
  } : null

  return (
    <main id="main-content" className="overflow-x-hidden">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(organizationLd) }}
      />
      {faqLd && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }}
        />
      )}
      <Hero t={t} locale={locale} />
      <Partners t={t} />
      <FeatureGrid t={t} />
      <AIShowcase t={t} />
      <WhyDifferent t={t} />
      <Benefits t={t} />
      <HowItWorks t={t} />
      <Testimonials t={t} />
      <PricingPreview t={t} locale={locale} />
      <FAQ t={t} />
      <CTA t={t} locale={locale} />
    </main>
  )
}
