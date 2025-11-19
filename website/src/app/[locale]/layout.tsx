import type { Metadata } from 'next'
import Navbar from '@/components/Navbar'
import Footer from '@/components/Footer'
import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, Locale, locales, defaultLocale } from '@/i18n/locales'
import '../globals.css'



export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const locale = isLocale(params.locale) ? params.locale : defaultLocale
  const t = await getDictionary(locale)
  return {
    title: t.meta.title,
    description: t.meta.description,
  }
}

// Note: Next.js 16 types `params` as possibly a Promise.
// Loosen the annotation to keep builds green across versions.
export default async function LocaleLayout(props: any) {
  const { children, params } = props
  const resolvedParams = typeof params?.then === 'function' ? await params : params
  const locale: Locale = isLocale(resolvedParams?.locale) ? (resolvedParams.locale as Locale) : defaultLocale
  const t = await getDictionary(locale)
  return (
    <>
      <Navbar t={t} locale={locale} />
      {children}
      <Footer t={t} locale={locale} />
    </>
  )
}
