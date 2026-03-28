import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import EfakturaValidator from './EfakturaValidator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/efaktura-proverka', {
    title: {
      mk: 'Е-Фактура проверка — UBL XML валидатор за Македонија',
      sq: 'Verifikimi i e-Faturës — Validuesi UBL XML për Maqedoninë',
      tr: 'E-Fatura doğrulama — Makedonya için UBL XML doğrulayıcı',
      en: 'E-Invoice Validator — UBL XML Checker for Macedonia',
    },
    description: {
      mk: 'Бесплатна проверка на е-фактура UBL XML за Македонија. 12 валидации: ЕДБ на издавач/примач, датум, ДДВ стапки, ставки. Подгответе се за октомври 2026. Без регистрација.',
      sq: 'Verifikim falas i e-faturës UBL XML për Maqedoninë. 12 validime: EDB i furnizuesit/blerësit, datë, norma TVSH, artikuj. Përgatituni për tetor 2026. Pa regjistrim.',
      tr: 'Makedonya için ücretsiz e-fatura UBL XML doğrulaması. 12 kontrol: tedarikçi/alıcı VKN, tarih, KDV oranları, kalemler. Ekim 2026\'ya hazırlanın. Kayıt gerekmez.',
      en: 'Free e-invoice UBL XML validation for Macedonia. 12 checks: supplier/buyer tax ID, date, VAT rates, line items. Prepare for October 2026. No registration required.',
    },
  })
}

export default async function EfakturaPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <EfakturaValidator locale={locale} />
}
