import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import CorporateTaxCalculator from './CorporateTaxCalculator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/danok-dobivka-kalkulator', {
    title: {
      mk: 'Калкулатор за данок на добивка Македонија 2026 — Бесплатна пресметка',
      sq: 'Llogaritësi i tatimit mbi fitimin Maqedoni 2026 — Llogaritje falas',
      tr: 'Kurumlar Vergisi Hesaplayıcı Makedonya 2026 — Ücretsiz hesaplama',
      en: 'Corporate Tax Calculator Macedonia 2026 — Free Calculation',
    },
    description: {
      mk: 'Бесплатен калкулатор за данок на добивка за Македонија. Пресметајте даночна основа, признати и непризнати трошоци, данок 10%. Рок: 15 март (ДБ-ВП). Без регистрација.',
      sq: 'Llogaritësi falas i tatimit mbi fitimin për Maqedoninë. Llogaritni bazën tatimore, shpenzimet e njohura dhe të panjohura, tatimin 10%. Afati: 15 mars (DB-VP). Pa regjistrim.',
      tr: 'Makedonya için ücretsiz kurumlar vergisi hesaplayıcı. Vergi matrahı, kabul edilen ve edilmeyen giderler, %10 vergi hesaplayın. Son tarih: 15 Mart (DB-VP). Kayıt gerekmez.',
      en: 'Free corporate tax calculator for Macedonia. Calculate tax base, deductible and non-deductible expenses, 10% tax. Deadline: March 15 (DB-VP form). No registration required.',
    },
  })
}

export default async function CorporateTaxCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <CorporateTaxCalculator locale={locale} />
}
