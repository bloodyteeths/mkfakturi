import type { Metadata } from 'next'
import Navbar from '@/components/Navbar'
import Footer from '@/components/Footer'
import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, Locale, defaultLocale } from '@/i18n/locales'
import '../globals.css'



export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale: localeParam } = await params
  const locale = isLocale(localeParam) ? localeParam : defaultLocale
  const t = await getDictionary(locale)
  return {
    title: t.meta.title,
    description: t.meta.description,
  }
}

// Note: Next.js 16 types `params` as possibly a Promise.
// Loosen the annotation to keep builds green across versions.
export default async function LocaleLayout({
  children,
  params,
}: {
  children: React.ReactNode
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = await getDictionary(locale)
  return (
    <>
      <Navbar t={t} locale={locale} />
      {children}
      <Footer t={t} locale={locale} />
    </>
  )
}
