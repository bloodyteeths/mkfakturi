import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import VatCalculator from './VatCalculator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/ddv-kalkulator', {
    title: {
      mk: 'ДДВ Калкулатор Македонија 2026 — Бесплатна пресметка на ДДВ',
      sq: 'Llogaritësi i TVSH-së Maqedoni 2026 — Llogaritje falas e TVSH-së',
      tr: 'KDV Hesaplayıcı Makedonya 2026 — Ücretsiz KDV hesaplama',
      en: 'VAT Calculator Macedonia 2026 — Free VAT Calculation',
    },
    description: {
      mk: 'Бесплатен ДДВ калкулатор за Македонија. Пресметајте ДДВ 18%, 5% и 10% — со и без ДДВ. Точни стапки за 2026 според ЗДДВ. Без регистрација.',
      sq: 'Llogaritësi falas i TVSH-së për Maqedoninë. Llogaritni TVSH 18%, 5% dhe 10% — me dhe pa TVSH. Norma të sakta 2026 sipas LTVSH. Pa regjistrim.',
      tr: 'Makedonya için ücretsiz KDV hesaplayıcı. %18, %5 ve %10 KDV hesaplayın — KDV dahil ve hariç. KDVK\'ya göre doğru 2026 oranları. Kayıt gerekmez.',
      en: 'Free VAT calculator for Macedonia. Calculate 18%, 5%, and 10% VAT — inclusive and exclusive. Accurate 2026 rates per VAT Law. No registration required.',
    },
  })
}

export default async function VatCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <VatCalculator locale={locale} />
}
