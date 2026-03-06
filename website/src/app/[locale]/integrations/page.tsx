import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/integrations', {
    title: {
      mk: 'Интеграции — Facturino',
      sq: 'Integrimet — Facturino',
      tr: 'Entegrasyonlar — Facturino',
      en: 'Integrations — Facturino',
    },
    description: {
      mk: 'Поврзете го Facturino со 9 македонски банки, UJP е-Фактура, NBRM курсна листа, WooCommerce, Viber и повеќе.',
      sq: 'Lidhni Facturino me 9 banka maqedonase, UJP e-Faturë, NBRM kurse, WooCommerce, Viber dhe më shumë.',
      tr: 'Facturino\'yu 9 Makedon bankası, UJP e-Fatura, NBRM kurları, WooCommerce, Viber ve daha fazlasıyla bağlayın.',
      en: 'Connect Facturino to 9 Macedonian banks, UJP e-Invoice, NBRM exchange rates, WooCommerce, Viber and more.',
    },
  })
}

const copy = {
  mk: {
    badge: 'Поврзан',
    hero: {
      headline: 'Поврзан со сè што му треба на вашиот бизнис',
      sub: '9 банки, државни системи, е-трговија и повеќе — на едно место.',
      cta: 'Започни бесплатно',
    },
    categories: {
      title: 'Интеграции по категорија',
      items: [
        {
          icon: 'banking',
          title: 'Банки и плаќања',
          desc: 'Поврзете ги вашите банкарски сметки и примајте плаќања.',
          integrations: ['NLB', 'Стопанска', 'Комерцијална', 'Шпаркасе', 'Халк', 'ПроКредит', 'ТТК', 'Силк Роуд', 'Охридска'],
          extras: ['MT940 & CSV увоз', 'PDF/OCR скенирање'],
          comingSoon: ['PSD2 Open Banking'],
        },
        {
          icon: 'government',
          title: 'Држава и даноци',
          desc: 'Усогласеност со македонските даночни прописи.',
          integrations: ['UJP е-Фактура (QES)', 'ДДВ-04 пријава', 'NBRM курсна листа', 'Централен Регистар', 'КИБС сертификати'],
          extras: [],
        },
        {
          icon: 'ecommerce',
          title: 'Е-трговија',
          desc: 'Синхронизирајте ги вашите онлајн продавници.',
          integrations: ['WooCommerce'],
          extras: [],
          comingSoon: ['Shopify', 'Magento', 'Ananas.mk'],
        },
        {
          icon: 'notifications',
          title: 'Известувања',
          desc: 'Испраќајте фактури и потсетници на вашите клиенти.',
          integrations: ['Viber Business', 'Email (SMTP, Mailgun, SES)'],
          extras: [],
          comingSoon: ['SMS порта'],
        },
        {
          icon: 'ai',
          title: 'AI и автоматизација',
          desc: 'Автоматизирајте рутински задачи со AI.',
          integrations: ['AI инсајти (GPT-4, Claude, Gemini)', 'OCR скенирање на фактури', 'Интелигентен увоз', 'Фискална сметка DataMatrix'],
          extras: [],
        },
      ],
    },
    timeline: {
      title: 'Статус на интеграции',
      live: { label: 'Активно', items: ['9 банки (CSV/MT940/PDF)', 'Е-Фактура (QES)', 'NBRM курсна листа', 'AI инсајти', 'OCR скенирање'] },
      soon: { label: 'Наскоро', items: ['PSD2 Open Banking', 'WooCommerce', 'Viber', 'Централен Регистар', 'eID/OneID'] },
      planned: { label: 'Планирано', items: ['Фискални уреди', 'МПИН', 'Ananas.mk'] },
    },
    bottomCta: {
      title: 'Подготвени за поврзување?',
      sub: 'Започнете со Facturino и поврзете го вашиот бизнис со сè што ви треба.',
      cta: 'Започни бесплатно',
    },
  },
  sq: {
    badge: 'I lidhur',
    hero: {
      headline: 'I lidhur me gjithçka që i nevojitet biznesit tuaj',
      sub: '9 banka, sisteme qeveritare, e-tregti dhe më shumë — në një vend.',
      cta: 'Fillo falas',
    },
    categories: {
      title: 'Integrime sipas kategorisë',
      items: [
        {
          icon: 'banking',
          title: 'Banka dhe pagesa',
          desc: 'Lidhni llogaritë bankare dhe pranoni pagesa.',
          integrations: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'ProCredit', 'TTK', 'Silk Road', 'Ohridska'],
          extras: ['MT940 & CSV import', 'PDF/OCR skanim'],
          comingSoon: ['PSD2 Open Banking'],
        },
        {
          icon: 'government',
          title: 'Qeveria dhe tatimet',
          desc: 'Pajtueshmëri me rregulloret tatimore maqedonase.',
          integrations: ['UJP e-Faturë (QES)', 'Deklarata DDV-04', 'NBRM kurse', 'Regjistri Qendror', 'Certifikata KIBS'],
          extras: [],
        },
        {
          icon: 'ecommerce',
          title: 'E-tregti',
          desc: 'Sinkronizoni dyqanet tuaja online.',
          integrations: ['WooCommerce'],
          extras: [],
          comingSoon: ['Shopify', 'Magento', 'Ananas.mk'],
        },
        {
          icon: 'notifications',
          title: 'Njoftimet',
          desc: 'Dërgoni fatura dhe kujtesa klientëve tuaj.',
          integrations: ['Viber Business', 'Email (SMTP, Mailgun, SES)'],
          extras: [],
          comingSoon: ['SMS gateway'],
        },
        {
          icon: 'ai',
          title: 'AI dhe automatizim',
          desc: 'Automatizoni detyrat rutinë me AI.',
          integrations: ['AI insights (GPT-4, Claude, Gemini)', 'OCR skanim faturash', 'Import inteligjent', 'Fiskale DataMatrix'],
          extras: [],
        },
      ],
    },
    timeline: {
      title: 'Statusi i integrimeve',
      live: { label: 'Aktive', items: ['9 banka (CSV/MT940/PDF)', 'e-Faturë (QES)', 'NBRM kurse', 'AI insights', 'OCR skanim'] },
      soon: { label: 'Së shpejti', items: ['PSD2 Open Banking', 'WooCommerce', 'Viber', 'Regjistri Qendror', 'eID/OneID'] },
      planned: { label: 'Planifikuar', items: ['Pajisje fiskale', 'MPIN', 'Ananas.mk'] },
    },
    bottomCta: {
      title: 'Gati për t\'u lidhur?',
      sub: 'Filloni me Facturino dhe lidhni biznesin tuaj me gjithçka që ju nevojitet.',
      cta: 'Fillo falas',
    },
  },
  tr: {
    badge: 'Bağlı',
    hero: {
      headline: 'İşletmenizin ihtiyaç duyduğu her şeye bağlı',
      sub: '9 banka, devlet sistemleri, e-ticaret ve daha fazlası — tek çatı altında.',
      cta: 'Ücretsiz başla',
    },
    categories: {
      title: 'Kategoriye göre entegrasyonlar',
      items: [
        {
          icon: 'banking',
          title: 'Bankalar ve ödemeler',
          desc: 'Banka hesaplarınızı bağlayın ve ödeme alın.',
          integrations: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'ProCredit', 'TTK', 'Silk Road', 'Ohridska'],
          extras: ['MT940 & CSV içe aktarma', 'PDF/OCR tarama'],
          comingSoon: ['PSD2 Open Banking'],
        },
        {
          icon: 'government',
          title: 'Devlet ve vergiler',
          desc: 'Makedon vergi düzenlemelerine uyumluluk.',
          integrations: ['UJP e-Fatura (QES)', 'KDV-04 beyannamesi', 'NBRM döviz kurları', 'Merkez Sicili', 'KIBS sertifikaları'],
          extras: [],
        },
        {
          icon: 'ecommerce',
          title: 'E-ticaret',
          desc: 'Online mağazalarınızı senkronize edin.',
          integrations: ['WooCommerce'],
          extras: [],
          comingSoon: ['Shopify', 'Magento', 'Ananas.mk'],
        },
        {
          icon: 'notifications',
          title: 'Bildirimler',
          desc: 'Müşterilerinize fatura ve hatırlatma gönderin.',
          integrations: ['Viber Business', 'Email (SMTP, Mailgun, SES)'],
          extras: [],
          comingSoon: ['SMS gateway'],
        },
        {
          icon: 'ai',
          title: 'AI ve otomasyon',
          desc: 'Rutin görevleri AI ile otomatikleştirin.',
          integrations: ['AI içgörüler (GPT-4, Claude, Gemini)', 'OCR fatura tarama', 'Akıllı içe aktarma', 'Mali fiş DataMatrix'],
          extras: [],
        },
      ],
    },
    timeline: {
      title: 'Entegrasyon durumu',
      live: { label: 'Aktif', items: ['9 banka (CSV/MT940/PDF)', 'e-Fatura (QES)', 'NBRM kurları', 'AI içgörüler', 'OCR tarama'] },
      soon: { label: 'Yakında', items: ['PSD2 Open Banking', 'WooCommerce', 'Viber', 'Merkez Sicili', 'eID/OneID'] },
      planned: { label: 'Planlanmış', items: ['Mali cihazlar', 'MPIN', 'Ananas.mk'] },
    },
    bottomCta: {
      title: 'Bağlanmaya hazır mısınız?',
      sub: 'Facturino ile başlayın ve işletmenizi ihtiyacınız olan her şeye bağlayın.',
      cta: 'Ücretsiz başla',
    },
  },
  en: {
    badge: 'Connected',
    hero: {
      headline: 'Connected to Everything Your Business Needs',
      sub: '9 banks, government systems, e-commerce, and more — all in one place.',
      cta: 'Start Free',
    },
    categories: {
      title: 'Integrations by Category',
      items: [
        {
          icon: 'banking',
          title: 'Banking & Payments',
          desc: 'Connect your bank accounts and accept payments.',
          integrations: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'ProCredit', 'TTK', 'Silk Road', 'Ohridska'],
          extras: ['MT940 & CSV import', 'PDF/OCR scanning'],
          comingSoon: ['PSD2 Open Banking'],
        },
        {
          icon: 'government',
          title: 'Government & Tax',
          desc: 'Compliance with Macedonian tax regulations.',
          integrations: ['UJP e-Invoice (QES)', 'DDV-04 VAT return', 'NBRM exchange rates', 'Central Registry', 'KIBS certificates'],
          extras: [],
        },
        {
          icon: 'ecommerce',
          title: 'E-Commerce',
          desc: 'Sync your online stores.',
          integrations: ['WooCommerce'],
          extras: [],
          comingSoon: ['Shopify', 'Magento', 'Ananas.mk'],
        },
        {
          icon: 'notifications',
          title: 'Notifications',
          desc: 'Send invoices and reminders to your clients.',
          integrations: ['Viber Business', 'Email (SMTP, Mailgun, SES)'],
          extras: [],
          comingSoon: ['SMS gateway'],
        },
        {
          icon: 'ai',
          title: 'AI & Automation',
          desc: 'Automate routine tasks with AI.',
          integrations: ['AI insights (GPT-4, Claude, Gemini)', 'OCR invoice scanning', 'Intelligent data import', 'Fiscal receipt DataMatrix'],
          extras: [],
        },
      ],
    },
    timeline: {
      title: 'Integration Status',
      live: { label: 'Live', items: ['9 banks (CSV/MT940/PDF)', 'e-Invoice (QES)', 'NBRM exchange rates', 'AI insights', 'OCR scanning'] },
      soon: { label: 'Coming Soon', items: ['PSD2 Open Banking', 'WooCommerce', 'Viber', 'Central Registry', 'eID/OneID'] },
      planned: { label: 'Planned', items: ['Fiscal devices', 'MPIN', 'Ananas.mk'] },
    },
    bottomCta: {
      title: 'Ready to Connect?',
      sub: 'Start with Facturino and connect your business to everything you need.',
      cta: 'Start Free',
    },
  },
} as const

/* Category icon SVGs */
function IconBanking() {
  return (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
    </svg>
  )
}
function IconGovernment() {
  return (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
    </svg>
  )
}
function IconEcommerce() {
  return (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
    </svg>
  )
}
function IconNotifications() {
  return (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
    </svg>
  )
}
function IconAi() {
  return (
    <svg className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
    </svg>
  )
}

const categoryIcons: Record<string, () => React.JSX.Element> = {
  banking: IconBanking,
  government: IconGovernment,
  ecommerce: IconEcommerce,
  notifications: IconNotifications,
  ai: IconAi,
}

const categoryColors: Record<string, { bg: string; text: string; hoverBg: string }> = {
  banking: { bg: 'bg-emerald-50', text: 'text-emerald-600', hoverBg: 'group-hover:bg-emerald-600' },
  government: { bg: 'bg-blue-50', text: 'text-blue-600', hoverBg: 'group-hover:bg-blue-600' },
  ecommerce: { bg: 'bg-purple-50', text: 'text-purple-600', hoverBg: 'group-hover:bg-purple-600' },
  notifications: { bg: 'bg-orange-50', text: 'text-orange-600', hoverBg: 'group-hover:bg-orange-600' },
  ai: { bg: 'bg-indigo-50', text: 'text-indigo-600', hoverBg: 'group-hover:bg-indigo-600' },
}

export default async function IntegrationsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content" className="overflow-x-hidden">

      {/* ── HERO ─────────────────────────────────────────────── */}
      <section className="relative overflow-hidden pt-28 pb-20 md:pt-36 md:pb-28">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-20 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
          <div className="absolute top-10 right-20 w-80 h-80 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25"></div>
          <div className="absolute -bottom-10 left-1/3 w-64 h-64 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
        </div>

        <div className="container relative z-10 text-center max-w-4xl mx-auto px-4 sm:px-6">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/80 backdrop-blur-sm border border-indigo-200 px-4 py-1.5 text-sm font-semibold text-indigo-700 shadow-sm mb-8">
            <span className="relative flex h-2 w-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
            </span>
            {t.badge}
          </div>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500">
              {t.hero.headline}
            </span>
          </h1>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto mb-10">
            {t.hero.sub}
          </p>
          <a
            href="https://app.facturino.mk/register"
            className="btn-primary text-lg px-8 py-4"
          >
            {t.hero.cta}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        </div>
      </section>

      {/* ── CATEGORY GRID ────────────────────────────────────── */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.categories.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
            {t.categories.items.map((cat, i) => {
              const Icon = categoryIcons[cat.icon]
              const colors = categoryColors[cat.icon]
              return (
                <div key={i} className={`card group hover:shadow-lg transition-shadow ${i >= 3 ? 'md:col-span-1 lg:col-span-1' : ''}`}>
                  <div className={`mb-4 w-14 h-14 rounded-xl ${colors.bg} flex items-center justify-center ${colors.text} ${colors.hoverBg} group-hover:text-white transition-colors`}>
                    <Icon />
                  </div>
                  <h3 className="text-xl font-bold text-gray-900 mb-2">{cat.title}</h3>
                  <p className="text-gray-500 text-sm mb-4">{cat.desc}</p>

                  {/* Integration badges */}
                  <div className="flex flex-wrap gap-2 mb-3">
                    {cat.integrations.map((name, j) => (
                      <span key={j} className="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                        {name}
                      </span>
                    ))}
                    {cat.extras.map((name, j) => (
                      <span key={`e-${j}`} className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">
                        {name}
                      </span>
                    ))}
                  </div>

                  {/* Coming soon badges */}
                  {'comingSoon' in cat && cat.comingSoon && (cat.comingSoon as readonly string[]).length > 0 && (
                    <div className="flex flex-wrap gap-2 mt-2 pt-2 border-t border-gray-100">
                      {(cat.comingSoon as readonly string[]).map((name, j) => (
                        <span key={`cs-${j}`} className="inline-flex items-center rounded-full bg-yellow-50 px-3 py-1 text-xs font-medium text-yellow-700">
                          {name}
                          <span className="ml-1 text-[10px] opacity-60">*</span>
                        </span>
                      ))}
                    </div>
                  )}
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* ── STATUS TIMELINE ──────────────────────────────────── */}
      <section className="section">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.timeline.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
            {/* Live */}
            <div className="rounded-2xl border-2 border-green-200 bg-white p-6 shadow-sm">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-3 h-3 rounded-full bg-green-500"></div>
                <h3 className="text-lg font-bold text-green-700">{t.timeline.live.label}</h3>
              </div>
              <ul className="space-y-3">
                {t.timeline.live.items.map((item, i) => (
                  <li key={i} className="flex items-start gap-2">
                    <svg className="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span className="text-sm text-gray-700">{item}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Coming Soon */}
            <div className="rounded-2xl border-2 border-yellow-200 bg-white p-6 shadow-sm">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-3 h-3 rounded-full bg-yellow-400"></div>
                <h3 className="text-lg font-bold text-yellow-700">{t.timeline.soon.label}</h3>
              </div>
              <ul className="space-y-3">
                {t.timeline.soon.items.map((item, i) => (
                  <li key={i} className="flex items-start gap-2">
                    <svg className="w-5 h-5 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span className="text-sm text-gray-700">{item}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Planned */}
            <div className="rounded-2xl border-2 border-gray-200 bg-white p-6 shadow-sm">
              <div className="flex items-center gap-3 mb-5">
                <div className="w-3 h-3 rounded-full bg-gray-400"></div>
                <h3 className="text-lg font-bold text-gray-600">{t.timeline.planned.label}</h3>
              </div>
              <ul className="space-y-3">
                {t.timeline.planned.items.map((item, i) => (
                  <li key={i} className="flex items-start gap-2">
                    <svg className="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM12.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0zM18.75 12a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                    </svg>
                    <span className="text-sm text-gray-500">{item}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* ── BOTTOM CTA ───────────────────────────────────────── */}
      <section className="py-20 lg:py-28 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600"></div>
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div className="container relative z-10 text-center text-white px-4 sm:px-6">
          <h2 className="text-4xl md:text-5xl font-extrabold mb-6 tracking-tight">
            {t.bottomCta.title}
          </h2>
          <p className="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
            {t.bottomCta.sub}
          </p>
          <a
            href="https://app.facturino.mk/register"
            className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 inline-flex items-center"
          >
            {t.bottomCta.cta}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        </div>
      </section>

    </main>
  )
}
