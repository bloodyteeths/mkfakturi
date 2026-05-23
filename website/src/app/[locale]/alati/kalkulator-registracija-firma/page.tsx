import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import RegistrationCalculator from './RegistrationCalculator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/kalkulator-registracija-firma', {
    title: {
      mk: 'Калкулатор за регистрација на фирма 2026 — Трошоци за отворање фирма',
      sq: 'Llogaritësi i regjistrimit te firma 2026 — Kostot e hapjes se firmes',
      tr: 'Firma Kayit Hesaplayici 2026 — Sirket Kurulus Maliyeti',
      en: 'Company Registration Cost Calculator 2026 — North Macedonia',
    },
    description: {
      mk: 'Бесплатен калкулатор за трошоци за регистрација на фирма во Македонија. ДООЕЛ, ДОО или Паушалец — ЦРСМ такси, нотар, печат, банка. Без регистрација.',
      sq: 'Llogarites falas per kostot e regjistrimit te firmes ne Maqedoni. SHPKNJP, SHPK ose Paushallist — taksa QRMV, noter, vule, banke. Pa regjistrim.',
      tr: 'Makedonya\'da sirket kayit maliyeti hesaplayici. Tek kisilik / cok ortakli / goturu — TSHM harclari, noter, muhur, banka. Ucretsiz.',
      en: 'Free company registration cost calculator for North Macedonia. Sole proprietorship, LLC, or lump-sum — CRSM fees, notary, seal, bank. No registration required.',
    },
  })
}

export default async function RegistrationCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <RegistrationCalculator locale={locale} />
}
