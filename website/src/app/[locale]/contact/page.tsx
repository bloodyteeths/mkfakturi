import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import ContactForm from './ContactForm'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/contact', {
    title: {
      mk: 'Контакт — Facturino',
      sq: 'Kontakti — Facturino',
      tr: 'Iletisim — Facturino',
      en: 'Contact Us — Facturino',
    },
    description: {
      mk: 'Контактирајте го тимот на Facturino за прашања, поддршка или демо. Ние сме тука да помогнеме со вашето сметководство.',
      sq: 'Kontaktoni ekipin e Facturino per pyetje, mbeshtetje ose demo. Jemi ketu per t\'ju ndihmuar me kontabilitetin tuaj.',
      tr: 'Sorular, destek veya demo icin Facturino ekibiyle iletisime gecin. Muhasebenizde size yardimci olmak icin buradayiz.',
      en: 'Get in touch with the Facturino team for questions, support, or a demo. We are here to help with your accounting needs.',
    },
  })
}

export default async function ContactPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <ContactForm locale={locale} />
}
