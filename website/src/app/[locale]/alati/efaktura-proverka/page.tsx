import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import EfakturaValidator from './EfakturaValidator'

const BASE_URL = 'https://www.facturino.mk'

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

  const appName = locale === 'mk' ? 'Е-Фактура UBL XML валидатор' : locale === 'sq' ? 'Validuesi UBL XML i e-Faturës' : locale === 'tr' ? 'E-Fatura UBL XML doğrulayıcı' : 'E-Invoice UBL XML Validator'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const toolsLabel = locale === 'mk' ? 'Алати' : locale === 'sq' ? 'Mjete' : locale === 'tr' ? 'Araçlar' : 'Tools'

  const webAppLd = {
    '@context': 'https://schema.org',
    '@type': 'WebApplication',
    name: appName,
    url: `${BASE_URL}/${locale}/alati/efaktura-proverka`,
    applicationCategory: 'BusinessApplication',
    operatingSystem: 'Web',
    inLanguage: locale,
    offers: { '@type': 'Offer', price: '0', priceCurrency: 'MKD' },
    publisher: { '@type': 'Organization', name: 'Facturino', url: BASE_URL },
  }
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: toolsLabel, href: `/${locale}/alati` },
    { name: appName, href: `/${locale}/alati/efaktura-proverka` },
  ])
  const faqLd = faqJsonLd([
    { question: 'Што проверува е-фактура валидаторот?', answer: 'Валидаторот проверува дали вашиот UBL 2.1 XML ги содржи сите задолжителни полиња по Чл. 53 ЗДДВ — ЕДБ на издавач и примач, датум, ставки со количини и цени, и ДДВ стапки.' },
    { question: 'Дали е бесплатна проверката?', answer: 'Да, проверката е целосно бесплатна и не бара регистрација.' },
    { question: 'Дали PDF е валидна е-фактура?', answer: 'Не. Само структуриран UBL 2.1 XML потпишан со QES е валидна е-фактура — не PDF или скенирана слика.' },
  ])

  return (
    <>
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(webAppLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />
      <EfakturaValidator locale={locale} />
    </>
  )
}
