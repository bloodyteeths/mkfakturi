import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import PageHero from '@/components/PageHero'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/how-it-works', {
    title: {
      mk: 'Како работи — Facturino',
      sq: 'Si funksionon — Facturino',
      tr: 'Nasil calisir — Facturino',
      en: 'How It Works — Facturino',
    },
    description: {
      mk: 'Три едноставни чекори до целосна контрола на финансиите. Без обука, без инсталација — започнете за минути, не за денови.',
      sq: 'Tri hapa te thjeshte deri ne kontroll te plote te financave. Pa trajnim, pa instalim — filloni per minuta, jo per dite.',
      tr: 'Uc basit adimda finanslarinizin tam kontrolu. Egitim yok, kurulum yok — dakikalarda baslayin, gunlerde degil.',
      en: 'Three simple steps to full financial control. No training, no installation — get started in minutes, not days.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    hero: {
      title: 'Како работи Facturino?',
      subtitle:
        'Од регистрација до целосна контрола на финансиите — во три едноставни чекори. Без обука, без инсталација, без чекање.',
      cta: 'Започни бесплатно',
    },
    steps: [
      {
        num: '01',
        title: 'Поврзи ја компанијата',
        subtitle: 'Регистрирај се и поврзи ги твоите податоци за 5 минути.',
        bullets: [
          'Креирај сметка со email — без договори, без обврски.',
          'Внеси ДДВ број и основни податоци за компанијата.',
          'Поврзи банка — увези изводи во CSV/MT940/PDF формат.',
          'Постави контен план и ДДВ правила — или користи ги нашите готови темплејти за Македонија.',
        ],
      },
      {
        num: '02',
        title: 'Креирај фактури со AI',
        subtitle: 'Професионални фактури за минути, не за часови.',
        bullets: [
          'Креирај фактура во неколку клика — AI автоматски предлага ДДВ код и конто за секоја ставка.',
          'Испрати PDF фактура директно на клиентот преку email.',
          'Рекурентни фактури — постави еднаш, Facturino автоматски ги генерира секој месец.',
          'е-Фактура (UBL) спремна — усогласена со европски стандарди за електронско фактурирање.',
        ],
      },
      {
        num: '03',
        title: 'Порамни и следи',
        subtitle: 'Банкарски изводи, порамнување и анализи — на автопилот.',
        bullets: [
          'Увези банкарски изводи во CSV, MT940 или PDF формат.',
          'Полуавтоматско порамнување — AI ги матчира уплатите со фактурите.',
          'Следи го готовинскиот тек во реално време со визуелни графици.',
          'AI увиди — предупредувања за доцнење, трендови и предлози за оптимизација.',
        ],
      },
    ],
    benefits: {
      title: 'Што добивате со Facturino',
      items: [
        ['е-Фактура (UBL)', 'Електронско фактурирање усогласено со EU стандарди.'],
        ['Мулти-компанија', 'Водете повеќе фирми од една сметка.'],
        ['Мултијазичен UI', 'Македонски, албански, турски и англиски.'],
        ['IFRS извештаи', 'Биланс, добивка/загуба и готовински тек.'],
        ['Банкарски увоз', 'Увези изводи во CSV/MT940/PDF. PSD2 наскоро.'],
        ['AI асистент', 'Паметни предлози за конта, ДДВ и порамнување.'],
        ['Рекурентни фактури', 'Автоматско издавање секој месец.'],
        ['Безбедност', 'Енкриптирани податоци, SSL, GDPR усогласеност.'],
      ],
    },
    bottomCta: {
      title: 'Подготвени? Започнете за 2 минути.',
      subtitle: 'Бесплатен план — без кредитна картичка, без обврска.',
      cta: 'Креирај бесплатна сметка',
    },
  },
  sq: {
    hero: {
      title: 'Si funksionon Facturino?',
      subtitle:
        'Nga regjistrimi deri te kontrolli i plotë i financave — në tri hapa të thjeshtë. Pa trajnim, pa instalim, pa pritje.',
      cta: 'Fillo falas',
    },
    steps: [
      {
        num: '01',
        title: 'Lidh kompaninë',
        subtitle: 'Regjistrohu dhe lidh të dhënat në 5 minuta.',
        bullets: [
          'Krijo llogari me email — pa kontratë, pa detyrime.',
          'Fut numrin e TVSH-së dhe të dhënat bazë të kompanisë.',
          'Lidh bankën — importo ekstrakte në format CSV/MT940/PDF.',
          'Vendos planin kontabël dhe rregullat e TVSH-së — ose përdor shabllonet tona të gatshme.',
        ],
      },
      {
        num: '02',
        title: 'Krijo fatura me AI',
        subtitle: 'Fatura profesionale në minuta, jo në orë.',
        bullets: [
          'Krijo faturë me disa klikime — AI sugjeron kodin e TVSH-së dhe llogarinë për çdo rresht.',
          'Dërgo faturë PDF direkt te klienti përmes emailit.',
          'Fatura periodike — vendos njëherë, Facturino i gjeneron automatikisht çdo muaj.',
          'e-Faturë (UBL) e gatshme — në përputhje me standardet evropiane.',
        ],
      },
      {
        num: '03',
        title: 'Pajto dhe ndiq',
        subtitle: 'Ekstrakte bankare, pajtim dhe analiza — në autopilot.',
        bullets: [
          'Importo ekstrakte bankare në format CSV, MT940 ose PDF.',
          'Pajtim gjysmë-automatik — AI i përputh pagesat me faturat.',
          'Ndiq rrjedhën e parasë në kohë reale me grafiqe vizuale.',
          'Njohuri AI — paralajmërime për vonesa, trende dhe sugjerime për optimizim.',
        ],
      },
    ],
    benefits: {
      title: 'Çfarë merrni me Facturino',
      items: [
        ['e-Faturë (UBL)', 'Faturim elektronik në përputhje me standardet e BE.'],
        ['Shumë kompani', 'Menaxhoni disa firma nga një llogari.'],
        ['UI shumëgjuhësh', 'Maqedonisht, shqip, turqisht dhe anglisht.'],
        ['Raporte IFRS', 'Bilanci, fitim/humbje dhe rrjedha e parasë.'],
        ['Import bankar', 'Importo ekstrakte në CSV/MT940/PDF. PSD2 së shpejti.'],
        ['Asistent AI', 'Sugjerime të mençura për llogari, TVSH dhe pajtim.'],
        ['Fatura periodike', 'Lëshim automatik çdo muaj.'],
        ['Siguri', 'Të dhëna të enkriptuara, SSL, përputhje GDPR.'],
      ],
    },
    bottomCta: {
      title: 'Gati? Filloni në 2 minuta.',
      subtitle: 'Plan falas — pa kartë krediti, pa detyrim.',
      cta: 'Krijo llogari falas',
    },
  },
  tr: {
    hero: {
      title: 'Facturino nasil calisir?',
      subtitle:
        'Kayittan finanslarin tam kontrolune kadar — uc basit adimda. Egitim yok, kurulum yok, bekleme yok.',
      cta: 'Ucretsiz basla',
    },
    steps: [
      {
        num: '01',
        title: 'Sirketini bagla',
        subtitle: 'Kaydol ve verilerini 5 dakikada bagla.',
        bullets: [
          'E-posta ile hesap olustur — sozlesme yok, zorunluluk yok.',
          'KDV numarani ve sirket bilgilerini gir.',
          'Bankayi bagla — ekstreleri CSV/MT940/PDF formatinda ice aktar.',
          'Hesap plani ve KDV kurallarini ayarla — veya hazir Makedonya sablonlarimizi kullan.',
        ],
      },
      {
        num: '02',
        title: 'AI ile fatura olustur',
        subtitle: 'Dakikalarda profesyonel faturalar, saatler degil.',
        bullets: [
          'Birkac tikla fatura olustur — AI her satir icin KDV kodu ve hesap onerir.',
          'PDF faturayi e-posta ile dogrudan musteriye gonder.',
          'Tekrarlayan faturalar — bir kez ayarla, Facturino her ay otomatik olusturur.',
          'e-Fatura (UBL) hazir — Avrupa elektronik faturalandirma standartlarina uygun.',
        ],
      },
      {
        num: '03',
        title: 'Eslestir ve takip et',
        subtitle: 'Banka ekstreleri, mutabakat ve analizler — otopilotta.',
        bullets: [
          'Banka ekstrelerini CSV, MT940 veya PDF formatinda ice aktar.',
          'Yari otomatik mutabakat — AI odemeleri faturalarla eslestirir.',
          'Nakit akisini gercek zamanli gorsel grafiklerle takip et.',
          'AI icgoruler — gecikme uyarilari, trendler ve optimizasyon onerileri.',
        ],
      },
    ],
    benefits: {
      title: 'Facturino ile ne elde edersiniz',
      items: [
        ['e-Fatura (UBL)', 'AB standartlarina uygun elektronik faturalandirma.'],
        ['Cok sirket', 'Tek hesaptan birden fazla sirket yonetin.'],
        ['Cok dilli arayuz', 'Makedonca, Arnavutca, Turkce ve Ingilizce.'],
        ['IFRS raporlari', 'Bilanco, kar/zarar ve nakit akisi.'],
        ['Banka aktarimi', 'Ekstreleri CSV/MT940/PDF olarak ice aktar. PSD2 yakin zamanda.'],
        ['AI asistani', 'Hesaplar, KDV ve mutabakat icin akilli oneriler.'],
        ['Tekrarlayan faturalar', 'Her ay otomatik kesim.'],
        ['Guvenlik', 'Sifreli veriler, SSL, GDPR uyumlulugu.'],
      ],
    },
    bottomCta: {
      title: 'Hazir misiniz? 2 dakikada baslayin.',
      subtitle: 'Ucretsiz plan — kredi karti yok, zorunluluk yok.',
      cta: 'Ucretsiz hesap olustur',
    },
  },
  en: {
    hero: {
      title: 'How does Facturino work?',
      subtitle:
        'From sign-up to full financial control — in three simple steps. No training, no installation, no waiting.',
      cta: 'Start free',
    },
    steps: [
      {
        num: '01',
        title: 'Connect your company',
        subtitle: 'Sign up and connect your data in 5 minutes.',
        bullets: [
          'Create an account with email — no contracts, no commitments.',
          'Enter your VAT number and basic company details.',
          'Connect your bank — import statements in CSV/MT940/PDF format.',
          'Set up your chart of accounts and VAT rules — or use our ready-made Macedonian templates.',
        ],
      },
      {
        num: '02',
        title: 'Create invoices with AI',
        subtitle: 'Professional invoices in minutes, not hours.',
        bullets: [
          'Create an invoice in a few clicks — AI automatically suggests VAT codes and accounts for each line item.',
          'Send a PDF invoice directly to your client via email.',
          'Recurring invoices — set once, Facturino generates them automatically every month.',
          'e-Invoice (UBL) ready — compliant with European electronic invoicing standards.',
        ],
      },
      {
        num: '03',
        title: 'Reconcile & track',
        subtitle: 'Bank statements, reconciliation, and analytics — on autopilot.',
        bullets: [
          'Import bank statements in CSV, MT940, or PDF format.',
          'Semi-automatic reconciliation — AI matches payments to invoices.',
          'Track cash flow in real time with visual charts.',
          'AI insights — late-payment warnings, trends, and optimization suggestions.',
        ],
      },
    ],
    benefits: {
      title: 'What you get with Facturino',
      items: [
        ['e-Invoice (UBL)', 'Electronic invoicing compliant with EU standards.'],
        ['Multi-company', 'Manage multiple businesses from one account.'],
        ['Multilingual UI', 'Macedonian, Albanian, Turkish, and English.'],
        ['IFRS Reports', 'Balance sheet, profit & loss, and cash flow.'],
        ['Bank Import', 'Import statements in CSV/MT940/PDF. PSD2 coming soon.'],
        ['AI Assistant', 'Smart suggestions for accounts, VAT, and reconciliation.'],
        ['Recurring invoices', 'Automatic issuance every month.'],
        ['Security', 'Encrypted data, SSL, GDPR compliance.'],
      ],
    },
    bottomCta: {
      title: 'Ready? Get started in 2 minutes.',
      subtitle: 'Free plan — no credit card, no commitment.',
      cta: 'Create a free account',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Step icons (inline SVGs to avoid dependencies)                    */
/* ------------------------------------------------------------------ */
function IconConnect() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m9.86-2.02a4.5 4.5 0 00-1.242-7.244l-4.5-4.5a4.5 4.5 0 00-6.364 6.364L4.34 8.627" />
    </svg>
  )
}

function IconInvoice() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>
  )
}

function IconReconcile() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
    </svg>
  )
}

const stepIcons = [IconConnect, IconInvoice, IconReconcile]

/* Alternating gradient backgrounds for each step */
const stepGradients = [
  'from-indigo-600 to-indigo-500',
  'from-cyan-600 to-cyan-500',
  'from-indigo-600 to-cyan-500',
]

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function HowItWorksPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      <PageHero
        image="/assets/images/hero_howitworks.png"
        alt="Professional opening laptop at clean desk - getting started concept"
        title={t.hero.title}
        subtitle={t.hero.subtitle}
        cta={{ label: t.hero.cta, href: 'https://app.facturino.mk/signup' }}
      />

      {/* ============================================================ */}
      {/*  THREE STEPS                                                 */}
      {/* ============================================================ */}
      <section className="py-8 md:py-24">
        <div className="container px-4 sm:px-6">
          {t.steps.map((step, i) => {
            const Icon = stepIcons[i]
            const isEven = i % 2 === 1

            return (
              <div
                key={i}
                className={`flex flex-col ${isEven ? 'lg:flex-row-reverse' : 'lg:flex-row'} items-center gap-6 lg:gap-16 ${i !== 0 ? 'mt-10 md:mt-28' : ''}`}
              >
                {/* Visual / number side */}
                <div className="flex-shrink-0 w-full lg:w-5/12 flex justify-center">
                  <div className="relative">
                    {/* Large number watermark */}
                    <span className="absolute -top-6 -left-4 text-[80px] md:text-[160px] font-extrabold leading-none text-indigo-100/60 select-none pointer-events-none z-0">
                      {step.num}
                    </span>
                    {/* Icon card */}
                    <div
                      className={`relative z-10 w-28 h-28 md:w-52 md:h-52 rounded-3xl bg-gradient-to-br ${stepGradients[i]} shadow-xl flex items-center justify-center`}
                    >
                      <div className="flex flex-col items-center gap-3">
                        <Icon />
                        <span className="text-white/80 text-xs font-semibold uppercase tracking-widest">
                          Step {step.num}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Content side */}
                <div className="flex-1 w-full lg:w-7/12">
                  <div className="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600 mb-4">
                    <span className="w-6 h-6 rounded-full bg-indigo-600 text-white text-xs flex items-center justify-center font-bold">
                      {i + 1}
                    </span>
                    Step {step.num}
                  </div>
                  <h2 className="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 mb-3">
                    {step.title}
                  </h2>
                  <p className="text-lg text-gray-500 mb-6">{step.subtitle}</p>
                  <ul className="space-y-4">
                    {step.bullets.map((bullet, j) => (
                      <li key={j} className="flex items-start gap-3">
                        <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                          <svg
                            className="w-3 h-3 text-green-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={3}
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              d="M5 13l4 4L19 7"
                            />
                          </svg>
                        </span>
                        <span className="text-gray-700 leading-relaxed">
                          {bullet}
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
              </div>
            )
          })}
        </div>
      </section>

      {/* ============================================================ */}
      {/*  CONNECTOR LINE between steps and benefits                   */}
      {/* ============================================================ */}
      <div className="flex justify-center py-4">
        <div className="w-px h-16 bg-gradient-to-b from-indigo-200 to-transparent" />
      </div>

      {/* ============================================================ */}
      {/*  WHAT YOU GET — benefits grid                                */}
      {/* ============================================================ */}
      <section className="section bg-gray-50/60">
        <div className="container px-4 sm:px-6">
          <div className="text-center mb-6 md:mb-12">
            <h2 className="text-2xl sm:text-4xl font-bold text-gray-900 mb-4">
              {t.benefits.title}
            </h2>
            <div className="mx-auto w-20 h-1 rounded-full bg-gradient-to-r from-indigo-500 to-cyan-400" />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {t.benefits.items.map(([title, desc], i) => (
              <div
                key={i}
                className="card text-center group"
              >
                {/* Decorative dot */}
                <div className="mx-auto mb-4 w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center group-hover:bg-indigo-100 transition-colors">
                  <svg
                    className="w-5 h-5 text-indigo-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
                <h3 className="font-semibold text-gray-900 mb-1">{title}</h3>
                <p className="text-sm text-gray-500">{desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.bottomCta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.bottomCta.subtitle}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.bottomCta.cta}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
