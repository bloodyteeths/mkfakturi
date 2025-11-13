import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import Hero from '@/components/Hero'
import WhyDifferent from '@/components/WhyDifferent'
import Benefits from '@/components/Benefits'
import HowItWorks from '@/components/HowItWorks'
import CTA from '@/components/CTA'
import Partners from '@/components/Partners'
import PricingPreview from '@/components/PricingPreview'
import Testimonials from '@/components/Testimonials'
import FAQ from '@/components/FAQ'

export default async function Landing({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = await getDictionary(locale)

  return (
    <main>
      <Hero t={t} locale={locale} />
      <Partners t={t} />
      <WhyDifferent t={t} />
      <Benefits t={t} />
      <HowItWorks t={t} />
      <PricingPreview t={t} locale={locale} />
      <Testimonials t={t} />
      <FAQ t={t} />
      <CTA t={t} locale={locale} />
    </main>
  )
}
