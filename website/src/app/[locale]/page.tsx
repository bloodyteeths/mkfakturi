import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
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

// Force dynamic rendering to ensure dictionary is fetched on each navigation
export const dynamic = 'force-dynamic'

export default async function Landing({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = await getDictionary(locale)

  return (
    <main className="overflow-x-hidden">
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
