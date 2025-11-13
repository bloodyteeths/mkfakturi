import type { Metadata } from 'next'
import Navbar from '@/components/Navbar'
import Footer from '@/components/Footer'
import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, Locale, locales, defaultLocale } from '@/i18n/locales'
import '../globals.css'

export async function generateStaticParams() {
  return locales.map((l) => ({ locale: l }))
}

export async function generateMetadata({ params }: { params: { locale: string } }): Promise<Metadata> {
  const locale = isLocale(params.locale) ? params.locale : defaultLocale
  const t = await getDictionary(locale)
  return {
    title: t.meta.title,
    description: t.meta.description,
  }
}

export default async function LocaleLayout({
  children,
  params,
}: {
  children: React.ReactNode
  params: { locale: string }
}) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = await getDictionary(locale)
  return (
    <>
      <Navbar t={t} locale={locale} />
      {children}
      <Footer t={t} locale={locale} />
    </>
  )
}
