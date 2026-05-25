import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd } from '@/lib/jsonld'
import SalaryCalculator from './SalaryCalculator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/plata-kalkulator', {
    title: {
      mk: 'Бруто нето калкулатор 2026 — Пресметка на плата Македонија',
      sq: 'Llogaritësi bruto neto 2026 — Paga në Maqedoni',
      tr: 'Brüt Net Hesaplayıcı 2026 — Kuzey Makedonya Maaş Hesaplama',
      en: 'North Macedonia Salary Calculator 2026 — Free Gross to Net',
    },
    description: {
      mk: 'Бесплатен бруто-нето калкулатор 2026. Пресметајте нето плата: придонеси 28%, данок 10%, лично ослободување 10.932 МКД. Минимална бруто плата 38.507 МКД. Без регистрација.',
      sq: 'Llogaritësi falas bruto-neto 2026. Llogaritni pagën: kontribute 28%, tatim 10%, zbritje personale 10.932 MKD. Paga minimale bruto 38.507 MKD. Pa regjistrim.',
      tr: 'Ücretsiz 2026 brüt-net hesaplayıcı. Katkı payları %28, gelir vergisi %10, kişisel indirim 10.932 MKD. Asgari brüt ücret 38.507 MKD. Kayıt gerekmez.',
      en: 'Free 2026 gross-to-net salary calculator for North Macedonia. Contributions 28%, income tax 10%, personal deduction 10,932 MKD. Minimum gross wage 38,507 MKD. No registration required.',
    },
  })
}

export default async function SalaryCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <SalaryCalculator locale={locale} />
}
