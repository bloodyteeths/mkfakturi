import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import TaxCalendar from './TaxCalendar'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/danocen-kalendar', {
    title: {
      mk: 'Даночен календар 2026 — Сите рокови за УЈП',
      sq: 'Kalendari Tatimor 2026 — Të gjitha afatet për DAP',
      tr: 'Vergi Takvimi 2026 — Tüm GGİ son tarihleri',
      en: 'Tax Calendar North Macedonia 2026 — All UJP Deadlines',
    },
    description: {
      mk: 'Интерактивен даночен календар за Северна Македонија 2026. Сите рокови за ДДВ, МПИН, данок на добивка и годишна сметка. Никогаш не пропуштајте рок.',
      sq: 'Kalendar tatimor interaktiv për Maqedoninë e Veriut 2026. Të gjitha afatet për TVSH, MPIN, tatimin mbi fitimin dhe llogaritë vjetore. Mos humbisni asnjë afat.',
      tr: 'Kuzey Makedonya 2026 interaktif vergi takvimi. KDV, MPIN, kurumlar vergisi ve yıllık hesaplar için tüm son tarihler. Hiçbir tarihi kaçırmayın.',
      en: 'Interactive tax calendar for North Macedonia 2026. All deadlines for VAT, MPIN, corporate tax, and annual accounts. Never miss a deadline.',
    },
  })
}

export default async function TaxCalendarPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <TaxCalendar locale={locale} />
}
