import type { Metadata } from 'next'
import Navbar from '@/components/Navbar'
import Footer from '@/components/Footer'
import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, locales, Locale, defaultLocale } from '@/i18n/locales'

/** Map locale codes to OpenGraph locale format */
const ogLocaleMap: Record<Locale, string> = {
  mk: 'mk_MK',
  sq: 'sq_AL',
  tr: 'tr_TR',
  en: 'en_US',
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }): Promise<Metadata> {
  const { locale: localeParam } = await params
  const locale = isLocale(localeParam) ? localeParam : defaultLocale
  const t = await getDictionary(locale)

  const alternateLocales = locales.filter((l) => l !== locale).map((l) => ogLocaleMap[l])

  return {
    title: {
      template: '%s | Facturino',
      default: t.meta.title,
    },
    description: t.meta.description,
    openGraph: {
      type: 'website',
      siteName: 'Facturino',
      locale: ogLocaleMap[locale],
      alternateLocale: alternateLocales,
      images: [
        {
          url: '/brand/og-image.jpg',
          width: 1200,
          height: 630,
          alt: 'Facturino — AI accounting platform for North Macedonia',
        },
      ],
    },
    twitter: {
      card: 'summary_large_image',
      images: ['/brand/og-image.jpg'],
    },
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
// CLAUDE-CHECKPOINT
