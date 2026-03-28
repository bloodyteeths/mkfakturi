import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import SalaryCalculator from './SalaryCalculator'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati/plata-kalkulator', {
    title: {
      mk: 'Калкулатор за плата Македонија 2026 — Бруто Нето пресметка',
      sq: 'Llogaritësi i pagës Maqedoni 2026 — Llogaritja Bruto Neto',
      tr: 'Maaş hesaplayıcı Makedonya 2026 — Brüt Net hesaplama',
      en: 'Salary Calculator Macedonia 2026 — Gross to Net',
    },
    description: {
      mk: 'Бесплатен калкулатор за плата 2026. Пресметајте нето плата од бруто и обратно. Точни стапки за пензиско, здравствено, данок на доход. Без регистрација.',
      sq: 'Llogaritës falas i pagës 2026. Llogaritni pagën neto nga bruto dhe anasjelltas. Norma të sakta për pension, shëndetësi, tatim mbi të ardhurat. Pa regjistrim.',
      tr: '2026 ücretsiz maaş hesaplayıcı. Brütten net maaş hesaplayın ve tersi. Emeklilik, sağlık, gelir vergisi için doğru oranlar. Kayıt gerekmez.',
      en: 'Free 2026 salary calculator. Calculate net salary from gross and vice versa. Accurate rates for pension, health, income tax. No registration required.',
    },
  })
}

export default async function SalaryCalcPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  return <SalaryCalculator locale={locale} />
}
