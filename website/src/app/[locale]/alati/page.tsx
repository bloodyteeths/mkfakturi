import Link from 'next/link'
import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/alati', {
    title: {
      mk: 'Бесплатни алатки за сметководство — ДДВ, Плата, Е-Фактура',
      sq: 'Mjete falas për kontabilitet — TVSH, Pagë, E-Faturë',
      tr: 'Ücretsiz muhasebe araçları — KDV, Maaş, E-Fatura',
      en: 'Free Accounting Tools — VAT, Salary, E-Invoice',
    },
    description: {
      mk: 'Бесплатни онлајн алатки за македонски бизниси: ДДВ калкулатор, калкулатор за нето плата 2026, и валидатор за е-фактура UBL XML. Без регистрација.',
      sq: 'Mjete falas online për bizneset maqedonase: Llogaritësi TVSH, llogaritësi i pagës neto 2026, dhe validuesi i e-faturës UBL XML. Pa regjistrim.',
      tr: 'Makedon işletmeleri için ücretsiz çevrimiçi araçlar: KDV hesaplayıcı, 2026 net maaş hesaplayıcı ve e-fatura UBL XML doğrulayıcı. Kayıt gerekmez.',
      en: 'Free online tools for Macedonian businesses: VAT calculator, 2026 net salary calculator, and e-invoice UBL XML validator. No registration required.',
    },
  })
}

const copy = {
  mk: {
    badge: 'Бесплатно, без регистрација',
    h1: 'Бесплатни алатки за сметководство',
    subtitle: 'Онлајн калкулатори и алатки направени за македонски бизниси. Користете ги веднаш — без регистрација, без чекање.',
    tools: [
      {
        icon: 'calculator',
        title: 'ДДВ Калкулатор',
        desc: 'Пресметајте ДДВ за сите стапки во Македонија — 18%, 5% и 10%. Вклучен и обратен пресмет.',
        href: '/alati/ddv-kalkulator',
        cta: 'Отвори калкулатор',
      },
      {
        icon: 'salary',
        title: 'Калкулатор за плата',
        desc: 'Пресметка на нето плата од бруто и обратно. Точни стапки за придонеси и данок за 2026.',
        href: '/alati/plata-kalkulator',
        cta: 'Пресметај плата',
      },
      {
        icon: 'xml',
        title: 'Е-Фактура проверка',
        desc: 'Валидирајте го вашиот UBL XML пред да го испратите до УЈП. 12 проверки за комплетност.',
        href: '/alati/efaktura-proverka',
        cta: 'Провери XML',
      },
    ],
    ctaTitle: 'Овие алатки се само почеток',
    ctaSub: 'Facturino автоматски го пресметува ДДВ, платите и генерира е-фактури за целата фирма.',
    ctaButton: 'Започни бесплатно — 14 дена',
    ctaSecondary: 'Закажи демо',
  },
  sq: {
    badge: 'Falas, pa regjistrim',
    h1: 'Mjete falas për kontabilitet',
    subtitle: 'Llogaritës dhe mjete online të krijuara për bizneset maqedonase. Përdorini menjëherë — pa regjistrim, pa pritje.',
    tools: [
      {
        icon: 'calculator',
        title: 'Llogaritësi i TVSH-së',
        desc: 'Llogaritni TVSH-në për të gjitha normat në Maqedoni — 18%, 5% dhe 10%. Përfshirë llogaritjen e kundërt.',
        href: '/alati/ddv-kalkulator',
        cta: 'Hap llogaritësin',
      },
      {
        icon: 'salary',
        title: 'Llogaritësi i pagës',
        desc: 'Llogaritja e pagës neto nga bruto dhe anasjelltas. Norma të sakta kontributesh dhe tatimi për 2026.',
        href: '/alati/plata-kalkulator',
        cta: 'Llogarit pagën',
      },
      {
        icon: 'xml',
        title: 'Verifikimi i e-Faturës',
        desc: 'Validoni XML-në tuaj UBL para se ta dërgoni në UJP. 12 kontrolle për plotësinë.',
        href: '/alati/efaktura-proverka',
        cta: 'Verifiko XML',
      },
    ],
    ctaTitle: 'Këto mjete janë vetëm fillimi',
    ctaSub: 'Facturino automatikisht llogarit TVSH-në, pagat dhe gjeneron e-fatura për të gjithë kompaninë.',
    ctaButton: 'Fillo falas — 14 ditë',
    ctaSecondary: 'Cakto demo',
  },
  tr: {
    badge: 'Ücretsiz, kayıt gerekmez',
    h1: 'Ücretsiz muhasebe araçları',
    subtitle: 'Makedon işletmeleri için tasarlanmış çevrimiçi hesaplayıcılar ve araçlar. Hemen kullanın — kayıt yok, bekleme yok.',
    tools: [
      {
        icon: 'calculator',
        title: 'KDV Hesaplayıcı',
        desc: 'Makedonya\'daki tüm oranlar için KDV hesaplayın — %18, %5 ve %10. Ters hesaplama dahil.',
        href: '/alati/ddv-kalkulator',
        cta: 'Hesaplayıcıyı aç',
      },
      {
        icon: 'salary',
        title: 'Maaş hesaplayıcı',
        desc: 'Brütten net maaş hesaplama ve tersi. 2026 için doğru katkı payı ve vergi oranları.',
        href: '/alati/plata-kalkulator',
        cta: 'Maaş hesapla',
      },
      {
        icon: 'xml',
        title: 'E-Fatura doğrulama',
        desc: 'UBL XML\'inizi UJP\'ye göndermeden önce doğrulayın. Tamlık için 12 kontrol.',
        href: '/alati/efaktura-proverka',
        cta: 'XML doğrula',
      },
    ],
    ctaTitle: 'Bu araçlar sadece başlangıç',
    ctaSub: 'Facturino KDV\'yi, maaşları otomatik hesaplar ve tüm şirket için e-fatura oluşturur.',
    ctaButton: 'Ücretsiz başla — 14 gün',
    ctaSecondary: 'Demo planla',
  },
  en: {
    badge: 'Free, no registration',
    h1: 'Free Accounting Tools',
    subtitle: 'Online calculators and tools built for Macedonian businesses. Use them instantly — no registration, no waiting.',
    tools: [
      {
        icon: 'calculator',
        title: 'VAT Calculator',
        desc: 'Calculate VAT for all Macedonian rates — 18%, 5%, and 10%. Reverse calculation included.',
        href: '/alati/ddv-kalkulator',
        cta: 'Open calculator',
      },
      {
        icon: 'salary',
        title: 'Salary Calculator',
        desc: 'Net salary from gross and vice versa. Accurate 2026 contribution and tax rates.',
        href: '/alati/plata-kalkulator',
        cta: 'Calculate salary',
      },
      {
        icon: 'xml',
        title: 'E-Invoice Validator',
        desc: 'Validate your UBL XML before submitting to UJP. 12 completeness checks.',
        href: '/alati/efaktura-proverka',
        cta: 'Validate XML',
      },
    ],
    ctaTitle: 'These tools are just the beginning',
    ctaSub: 'Facturino automatically calculates VAT, salaries, and generates e-invoices for your entire company.',
    ctaButton: 'Start free — 14 days',
    ctaSecondary: 'Schedule demo',
  },
} as const

function CalculatorIcon() {
  return (
    <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm2.25-4.5h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm0 2.25h.008v.008H10.5v-.008zm2.25-4.5h.008v.008H12.75v-.008zm0 2.25h.008v.008H12.75v-.008zm2.25-6.75h.008v.008H15v-.008zm0 2.25h.008v.008H15v-.008zM6 6.75A.75.75 0 016.75 6h10.5a.75.75 0 01.75.75v10.5a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V6.75z" />
    </svg>
  )
}

function SalaryIcon() {
  return (
    <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18v-.008zm-12 0h.008v.008H6v-.008z" />
    </svg>
  )
}

function XmlIcon() {
  return (
    <svg className="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
    </svg>
  )
}

const iconMap = { calculator: CalculatorIcon, salary: SalaryIcon, xml: XmlIcon }

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

export default async function ToolsIndexPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const toolsLd = {
    '@context': 'https://schema.org',
    '@type': 'ItemList',
    name: t.h1,
    description: t.subtitle,
    itemListElement: t.tools.map((tool, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: tool.title,
      description: tool.desc,
      url: `https://www.facturino.mk/${locale}${tool.href}`,
    })),
  }

  const breadcrumbLd = {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: [
      { '@type': 'ListItem', position: 1, name: 'Facturino', item: `https://www.facturino.mk/${locale}` },
      { '@type': 'ListItem', position: 2, name: t.h1, item: `https://www.facturino.mk/${locale}/alati` },
    ],
  }

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(toolsLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      {/* Hero Section */}
      <section className="relative overflow-hidden pt-20 pb-10 md:pt-32 md:pb-20">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-3xl mx-auto">
          <span className="inline-flex items-center rounded-full bg-white/15 backdrop-blur-sm px-4 py-1.5 text-sm font-semibold text-white border border-white/20 mb-6">
            {t.badge}
          </span>
          <h1 className="text-3xl md:text-5xl font-extrabold tracking-tight leading-tight mb-4">
            {t.h1}
          </h1>
          <p className="text-lg md:text-xl text-indigo-100 leading-relaxed">
            {t.subtitle}
          </p>
        </div>
      </section>

      {/* Tools Grid */}
      <section className="section">
        <div className="container max-w-5xl mx-auto">
          <div className="grid md:grid-cols-3 gap-6">
            {t.tools.map((tool) => {
              const Icon = iconMap[tool.icon as keyof typeof iconMap]
              return (
                <Link
                  key={tool.href}
                  href={`/${locale}${tool.href}`}
                  className="group p-6 md:p-8 rounded-2xl bg-white border border-gray-200 hover:border-indigo-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                >
                  <div className="w-14 h-14 mb-5 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-100 transition-colors">
                    <Icon />
                  </div>
                  <h2 className="text-xl font-bold text-gray-900 mb-2">{tool.title}</h2>
                  <p className="text-gray-600 leading-relaxed mb-4">{tool.desc}</p>
                  <span className="inline-flex items-center text-sm font-semibold text-indigo-600 group-hover:gap-2 transition-all">
                    {tool.cta}
                    <svg className="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                  </span>
                </Link>
              )
            })}
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-12 lg:py-24 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-2xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">{t.ctaTitle}</h2>
          <p className="text-xl text-indigo-100 mb-8">{t.ctaSub}</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a
              href={`${APP_URL}/signup`}
              className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
            >
              {t.ctaButton}
            </a>
            <Link
              href={`/${locale}/contact`}
              className="px-8 py-4 bg-indigo-700/50 text-white border border-white/20 rounded-xl font-bold text-lg hover:bg-indigo-700/70 transition-all duration-300 backdrop-blur-sm"
            >
              {t.ctaSecondary}
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}
