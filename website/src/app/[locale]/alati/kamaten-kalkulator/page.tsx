import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import InterestCalculator from './InterestCalculator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/kamaten-kalkulator', {
    title: {
      mk: 'Калкулатор за казнена камата — Бесплатна пресметка',
      sq: 'Llogaritësi i kamatës ndëshkuese — Llogaritje falas',
      tr: 'Gecikme faizi hesaplayici — Ucretsiz hesaplama',
      en: 'Penalty Interest Calculator — Free Calculation',
    },
    description: {
      mk: 'Бесплатен калкулатор за казнена камата по законска стапка (13,25%) според ЗОО чл. 266-а. Пресметајте камата за задоцнети плаќања, судски пресуди и даночни долгови. Без регистрација.',
      sq: 'Llogaritësi falas i kamatës ndëshkuese me normën ligjore (13,25%) sipas LOD neni 266-a. Llogaritni kamatën për pagesa të vonuara, vendime gjyqësore dhe borxhe tatimore. Pa regjistrim.',
      tr: 'Yasal oran (%13,25) ile ucretsiz gecikme faizi hesaplayicisi, BOK md. 266-a uyarinca. Geciken odemeler, mahkeme kararlari ve vergi borclari icin faiz hesaplayin. Kayit gerekmez.',
      en: 'Free penalty interest calculator at the statutory rate (13.25%) per LOO Art. 266-a. Calculate interest on late payments, court judgments, and tax debts. No registration required.',
    },
  })
}

export default async function InterestCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <InterestCalculator locale={locale} />
}
