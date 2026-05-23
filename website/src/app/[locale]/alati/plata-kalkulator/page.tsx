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
      mk: 'Бесплатен бруто-нето калкулатор 2026. Пресметајте нето плата од бруто: пензиско 18.8%, здравствено 7.5%, данок 10%. Минимална плата 20.175 МКД. Без регистрација.',
      sq: 'Llogaritësi falas bruto-neto 2026. Llogaritni pagën: pension 18.8%, shëndetësi 7.5%, tatim 10%. Paga minimale 20.175 MKD. Pa regjistrim.',
      tr: 'Ücretsiz 2026 brüt-net hesaplayıcı. Emeklilik %18,8, sağlık %7,5, gelir vergisi %10. Asgari ücret 20.175 MKD. Kayıt gerekmez.',
      en: 'Free 2026 gross-to-net salary calculator for North Macedonia. Pension 18.8%, health 7.5%, income tax 10%. Minimum wage 20,175 MKD. No registration required.',
    },
  })
}

export default async function SalaryCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <SalaryCalculator locale={locale} />
}
