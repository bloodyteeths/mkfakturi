import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import PageHero from '@/components/PageHero'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/e-faktura', {
    title: {
      mk: 'е-Фактура (e-Faktura) — Facturino',
      sq: 'e-Fatura — Facturino',
      tr: 'e-Fatura — Facturino',
      en: 'e-Invoice (e-Faktura) — Facturino',
    },
    description: {
      mk: 'Подготвени за UJP е-Фактура со QES потпис и структурирани UBL податоци. Бидете први кога системот ќе стане задолжителен.',
      sq: 'Gati per UJP e-Fature me nenshkrim QES dhe te dhena te strukturuara UBL. Behuni te paret kur sistemi behet i detyrueshem.',
      tr: 'UJP e-Fatura\'ya hazir: QES imza ve yapilandirilmis UBL verileri. Sistem zorunlu oldugunda ilk sirada olun.',
      en: 'UJP e-Invoice ready with QES signature and structured UBL data. Be first in line when the system becomes mandatory.',
    },
  })
}

const copy = {
  mk: {
    hero: {
      headline: 'е-Фактура: бидете подготвени пред сите',
      sub: 'Facturino веќе ја има инфраструктурата за е‑Фактура. Кога УЈП ќе го отвори продукцискиот API, вие ќе бидете први на ред.',
      cta: 'Подготви се денес',
    },
    timeline: {
      title: 'Каде сме сега?',
      steps: [
        {
          label: 'УЈП',
          status: 'in-progress',
          title: 'Развива продукциски API',
          body: 'УЈП го развива системот за е‑Фактура. Тестен период е во тек, финалниот API сe уште не е јавен.',
        },
        {
          label: 'Facturino',
          status: 'done',
          title: 'Структурирани податоци се подготвени',
          body: 'Нашиот модел веќе ги содржи сите полиња потребни за е‑Фактура: ИД, ДДВ, ставки, потписи.',
        },
        {
          label: 'Кога ќе стане задолжително',
          status: 'ready',
          title: 'Едно кликнување — поврзување',
          body: 'Нема миграција, нема panic-режим. Вие продолжувате да работите, ние ве поврзуваме.',
        },
      ],
    },
    features: {
      title: 'Што Facturino веќе поддржува',
      items: [
        {
          icon: 'id',
          title: 'ИД на купувач/продавач',
          body: 'ЕМБС и ЕДБ за секој субјект, спремни за валидација од УЈП.',
        },
        {
          icon: 'percent',
          title: 'ДДВ распад по стапка',
          body: 'Автоматски пресметки за 5% и 18%, со детален распад по ставка.',
        },
        {
          icon: 'payment',
          title: 'Рокови и начини на плаќање',
          body: 'Структурирани полиња за валута, рок, попуст и начин на плаќање.',
        },
        {
          icon: 'list',
          title: 'Структурирани ставки',
          body: 'Секој артикл/услуга со количина, единечна цена, ДДВ стапка и вкупна сума.',
        },
        {
          icon: 'signature',
          title: 'QES дигитален потпис',
          body: 'Подготвено за квалификуван електронски потпис (QES) кога ќе биде потребен.',
        },
        {
          icon: 'xml',
          title: 'UBL 2.1 формат',
          body: 'Податочниот модел следи UBL 2.1 стандард — готов за директна конверзија.',
        },
      ],
    },
    comparison: {
      title: 'Додека другите чекаат, вие сте подготвени',
      withFacturino: {
        label: 'Со Facturino',
        items: [
          'Веќе внесувате структурирани податоци',
          'е-Фактура се активира со еден клик',
          'Нема миграција, нема прилагодување',
          'Бизнисот продолжува без пречки',
        ],
      },
      withoutFacturino: {
        label: 'Без Facturino',
        items: [
          'Внесувате во PDF/Word без структура',
          'Морате да го менувате софтверот',
          'Миграција на податоци од нула',
          'Ризик од закаснување и казни',
        ],
      },
    },
    promise: {
      title: 'Нашето ветување',
      body: 'Кога официјалниот API + QES ќе бидат целосно достапни, Facturino се поврзува — без миграција, без дополнителна цена, без прекин на работата. Вашиот тек на работа останува ист.',
    },
    bottomCta: {
      title: 'Подготви се денес',
      sub: 'Бидете подготвени пред е‑Фактура да стане задолжителна. Започнете со Facturino бесплатно.',
      cta: 'Започни бесплатно',
    },
  },
  sq: {
    hero: {
      headline: 'e-Fatura: bëhuni gati para të gjithëve',
      sub: 'Facturino tashmë e ka infrastrukturën për e‑Faturë. Kur UJP të hapë API‑n e prodhimit, ju do të jeni të parët në radhë.',
      cta: 'Përgatitu sot',
    },
    timeline: {
      title: 'Ku jemi tani?',
      steps: [
        {
          label: 'UJP',
          status: 'in-progress',
          title: 'Po zhvillon API‑n e prodhimit',
          body: 'UJP po zhvillon sistemin e e‑Faturës. Periudha e testimit është në vazhdim, API‑ja përfundimtare ende nuk është publike.',
        },
        {
          label: 'Facturino',
          status: 'done',
          title: 'Të dhënat e strukturuara janë gati',
          body: 'Modeli ynë tashmë i përmban të gjitha fushat e nevojshme për e‑Faturë: ID, TVSH, artikuj, nënshkrime.',
        },
        {
          label: 'Kur bëhet e detyrueshme',
          status: 'ready',
          title: 'Një klikim — lidhje',
          body: 'Pa migrim, pa panik. Ju vazhdoni punën, ne ju lidhim.',
        },
      ],
    },
    features: {
      title: 'Çfarë Facturino tashmë mbështet',
      items: [
        {
          icon: 'id',
          title: 'ID blerës/shitës',
          body: 'NIPT dhe numër tatimor për çdo subjekt, gati për validim nga UJP.',
        },
        {
          icon: 'percent',
          title: 'Detajim TVSH sipas normës',
          body: 'Llogaritje automatike për 5% dhe 18%, me ndarje të detajuar sipas rreshtit.',
        },
        {
          icon: 'payment',
          title: 'Afate dhe mënyra pagese',
          body: 'Fusha të strukturuara për valutë, afat, zbritje dhe mënyrë pagese.',
        },
        {
          icon: 'list',
          title: 'Artikuj të strukturuar',
          body: 'Çdo artikull/shërbim me sasi, çmim njësi, normë TVSH dhe shumë totale.',
        },
        {
          icon: 'signature',
          title: 'Nënshkrim digjital QES',
          body: 'Gati për nënshkrim elektronik të kualifikuar (QES) kur të nevojitet.',
        },
        {
          icon: 'xml',
          title: 'Format UBL 2.1',
          body: 'Modeli i të dhënave ndjek standardin UBL 2.1 — gati për konvertim direkt.',
        },
      ],
    },
    comparison: {
      title: 'Ndërsa të tjerët presin, ju jeni gati',
      withFacturino: {
        label: 'Me Facturino',
        items: [
          'Tashmë futni të dhëna të strukturuara',
          'e-Fatura aktivizohet me një klikim',
          'Pa migrim, pa përshtatje',
          'Biznesi vazhdon pa ndërprerje',
        ],
      },
      withoutFacturino: {
        label: 'Pa Facturino',
        items: [
          'Futni në PDF/Word pa strukturë',
          'Duhet të ndryshoni softuerin',
          'Migrim i të dhënave nga zeroja',
          'Rrezik vonesash dhe gjobash',
        ],
      },
    },
    promise: {
      title: 'Premtimi ynë',
      body: 'Kur API‑ja zyrtare + QES të jenë plotësisht të disponueshme, Facturino lidhet — pa migrim, pa çmim shtesë, pa ndërprerje pune. Rrjedha juaj e punës mbetet e njëjtë.',
    },
    bottomCta: {
      title: 'Përgatitu sot',
      sub: 'Bëhuni gati para se e‑Fatura të bëhet e detyrueshme. Filloni me Facturino falas.',
      cta: 'Fillo falas',
    },
  },
  tr: {
    hero: {
      headline: 'e-Fatura: herkesten once hazir olun',
      sub: 'Facturino e‑Fatura altyapısına zaten sahip. UJP üretim API\'sini açtığında, siz ilk sırada olacaksınız.',
      cta: 'Bugün hazırlanın',
    },
    timeline: {
      title: 'Şu an neredeyiz?',
      steps: [
        {
          label: 'UJP',
          status: 'in-progress',
          title: 'Üretim API\'sini geliştiriyor',
          body: 'UJP e‑Fatura sistemini geliştiriyor. Test dönemi devam ediyor, nihai API henüz herkese açık değil.',
        },
        {
          label: 'Facturino',
          status: 'done',
          title: 'Yapılandırılmış veriler hazır',
          body: 'Modelimiz e‑Fatura için gerekli tüm alanları içeriyor: kimlik, KDV, kalemler, imzalar.',
        },
        {
          label: 'Zorunlu olduğunda',
          status: 'ready',
          title: 'Tek tıklama — bağlantı',
          body: 'Taşıma yok, panik yok. Siz çalışmaya devam edin, biz bağlantıyı kurarız.',
        },
      ],
    },
    features: {
      title: 'Facturino şimdiden neleri destekliyor',
      items: [
        {
          icon: 'id',
          title: 'Alıcı/satıcı kimlik numaraları',
          body: 'Her kuruluş için EMBŞ ve EDB, UJP doğrulamasına hazır.',
        },
        {
          icon: 'percent',
          title: 'Orana göre KDV dökümü',
          body: '%5 ve %18 için otomatik hesaplama, satır bazında detaylı döküm.',
        },
        {
          icon: 'payment',
          title: 'Vade ve ödeme yöntemleri',
          body: 'Para birimi, vade, indirim ve ödeme yöntemi için yapılandırılmış alanlar.',
        },
        {
          icon: 'list',
          title: 'Yapılandırılmış kalemler',
          body: 'Her ürün/hizmet miktar, birim fiyat, KDV oranı ve toplam tutar ile.',
        },
        {
          icon: 'signature',
          title: 'QES dijital imza',
          body: 'Gerektiğinde nitelikli elektronik imza (QES) için hazır.',
        },
        {
          icon: 'xml',
          title: 'UBL 2.1 formatı',
          body: 'Veri modeli UBL 2.1 standardını izler — doğrudan dönüşüme hazır.',
        },
      ],
    },
    comparison: {
      title: 'Diğerleri beklerken, siz hazırsınız',
      withFacturino: {
        label: 'Facturino ile',
        items: [
          'Zaten yapılandırılmış veri giriyorsunuz',
          'e-Fatura tek tıkla aktifleşir',
          'Taşıma yok, uyarlama yok',
          'İş kesintisiz devam eder',
        ],
      },
      withoutFacturino: {
        label: 'Facturino olmadan',
        items: [
          'PDF/Word\'e yapısız veri giriyorsunuz',
          'Yazılımı değiştirmeniz gerekiyor',
          'Sıfırdan veri taşıma',
          'Gecikme ve ceza riski',
        ],
      },
    },
    promise: {
      title: 'Sözümüz',
      body: 'Resmi API + QES tamamen kullanılabilir olduğunda, Facturino bağlanır — taşıma yok, ek ücret yok, iş kesintisi yok. İş akışınız aynı kalır.',
    },
    bottomCta: {
      title: 'Bugün hazırlanın',
      sub: 'e-Fatura zorunlu olmadan hazır olun. Facturino ile ücretsiz başlayın.',
      cta: 'Ücretsiz başla',
    },
  },
  en: {
    hero: {
      headline: 'e-Invoice: be ready before everyone else',
      sub: 'Facturino already has the infrastructure for e-Invoice. When UJP opens the production API, you will be first in line.',
      cta: 'Get ready today',
    },
    timeline: {
      title: 'Where are we now?',
      steps: [
        {
          label: 'UJP',
          status: 'in-progress',
          title: 'Developing production API',
          body: 'UJP is developing the e-Invoice system. Testing is underway, the final API is not yet public.',
        },
        {
          label: 'Facturino',
          status: 'done',
          title: 'Structured data is ready',
          body: 'Our model already contains all fields required for e-Invoice: IDs, VAT, line items, signatures.',
        },
        {
          label: 'When it becomes mandatory',
          status: 'ready',
          title: 'One click — connected',
          body: 'No migration, no panic. You keep working, we connect you.',
        },
      ],
    },
    features: {
      title: 'What Facturino already supports',
      items: [
        {
          icon: 'id',
          title: 'Buyer/seller tax IDs',
          body: 'EMBS and EDB for every entity, ready for UJP validation.',
        },
        {
          icon: 'percent',
          title: 'VAT breakdown by rate',
          body: 'Automatic calculations for 5% and 18%, with detailed per-line breakdown.',
        },
        {
          icon: 'payment',
          title: 'Payment terms and methods',
          body: 'Structured fields for currency, due date, discount, and payment method.',
        },
        {
          icon: 'list',
          title: 'Structured line items',
          body: 'Every product/service with quantity, unit price, VAT rate, and total amount.',
        },
        {
          icon: 'signature',
          title: 'QES digital signature',
          body: 'Ready for Qualified Electronic Signature (QES) when required.',
        },
        {
          icon: 'xml',
          title: 'UBL 2.1 format',
          body: 'Data model follows UBL 2.1 standard — ready for direct conversion.',
        },
      ],
    },
    comparison: {
      title: 'While others wait, you are ready',
      withFacturino: {
        label: 'With Facturino',
        items: [
          'Already entering structured data',
          'e-Invoice activates with one click',
          'No migration, no adjustments needed',
          'Business continues without interruption',
        ],
      },
      withoutFacturino: {
        label: 'Without Facturino',
        items: [
          'Entering data into PDF/Word without structure',
          'You need to change your software',
          'Data migration from scratch',
          'Risk of delays and penalties',
        ],
      },
    },
    promise: {
      title: 'Our promise',
      body: 'When the official API + QES become fully available, Facturino connects — no migration, no extra cost, no downtime. Your workflow stays the same.',
    },
    bottomCta: {
      title: 'Get ready today',
      sub: 'Be prepared before e-Invoice becomes mandatory. Start with Facturino for free.',
      cta: 'Start for free',
    },
  },
} as const

/* SVG icon helpers */
function IconId() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
    </svg>
  )
}
function IconPercent() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />
    </svg>
  )
}
function IconPayment() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
    </svg>
  )
}
function IconList() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
    </svg>
  )
}
function IconSignature() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
    </svg>
  )
}
function IconXml() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" />
    </svg>
  )
}
function IconCheck() {
  return (
    <svg className="w-5 h-5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
    </svg>
  )
}
function IconXMark() {
  return (
    <svg className="w-5 h-5 text-red-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
  )
}

const featureIcons: Record<string, () => React.JSX.Element> = {
  id: IconId,
  percent: IconPercent,
  payment: IconPayment,
  list: IconList,
  signature: IconSignature,
  xml: IconXml,
}

const timelineStatusStyles: Record<string, { ring: string; dot: string; line: string }> = {
  'in-progress': {
    ring: 'border-yellow-400 bg-yellow-50',
    dot: 'bg-yellow-400',
    line: 'bg-yellow-200',
  },
  done: {
    ring: 'border-green-400 bg-green-50',
    dot: 'bg-green-500',
    line: 'bg-green-200',
  },
  ready: {
    ring: 'border-indigo-400 bg-indigo-50',
    dot: 'bg-indigo-500',
    line: 'bg-indigo-200',
  },
}

export default async function EFakturaPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content" className="overflow-x-hidden">

      <PageHero
        image="/assets/images/hero_efaktura.png"
        alt="Digital invoice approval on tablet device"
        title={t.hero.headline}
        subtitle={t.hero.sub}
        cta={{ label: t.hero.cta, href: 'https://app.facturino.mk/signup' }}
      />

      {/* ── TIMELINE / STATUS ────────────────────────────────── */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.timeline.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="max-w-3xl mx-auto">
            <div className="relative">
              {t.timeline.steps.map((step, i) => {
                const styles = timelineStatusStyles[step.status]
                const isLast = i === t.timeline.steps.length - 1
                return (
                  <div key={i} className="relative flex gap-6 pb-10 last:pb-0">
                    {/* Vertical line */}
                    <div className="flex flex-col items-center">
                      <div className={`w-10 h-10 rounded-full border-2 flex items-center justify-center flex-shrink-0 ${styles.ring}`}>
                        <div className={`w-3 h-3 rounded-full ${styles.dot}`}></div>
                      </div>
                      {!isLast && (
                        <div className={`w-0.5 flex-1 mt-2 ${styles.line}`}></div>
                      )}
                    </div>
                    {/* Content */}
                    <div className="card flex-1 mb-2">
                      <div className="text-xs font-bold uppercase tracking-wider mb-1" style={{
                        color: step.status === 'in-progress' ? '#ca8a04' : step.status === 'done' ? '#16a34a' : '#4f46e5'
                      }}>
                        {step.label}
                      </div>
                      <h3 className="text-lg font-bold text-gray-900 mb-2">{step.title}</h3>
                      <p className="text-gray-600 text-sm leading-relaxed">{step.body}</p>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        </div>
      </section>

      {/* ── FEATURE GRID ─────────────────────────────────────── */}
      <section className="section">
        <div className="absolute inset-0 bg-grid-pattern opacity-[0.03] pointer-events-none"></div>
        <div className="container relative z-10 px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.features.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            {t.features.items.map((item, i) => {
              const Icon = featureIcons[item.icon]
              return (
                <div key={i} className="card group hover:border-indigo-200">
                  <div className="mb-4 w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                    <Icon />
                  </div>
                  <h3 className="text-lg font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition-colors">{item.title}</h3>
                  <p className="text-gray-600 text-sm leading-relaxed">{item.body}</p>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* ── COMPARISON ───────────────────────────────────────── */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.comparison.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            {/* With Facturino */}
            <div className="rounded-2xl border-2 border-green-200 bg-white p-8 shadow-lg relative overflow-hidden">
              <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-green-400 to-emerald-500"></div>
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                  <IconCheck />
                </div>
                <h3 className="text-xl font-bold text-gray-900">{t.comparison.withFacturino.label}</h3>
              </div>
              <ul className="space-y-4">
                {t.comparison.withFacturino.items.map((item, i) => (
                  <li key={i} className="flex items-start gap-3">
                    <IconCheck />
                    <span className="text-gray-700">{item}</span>
                  </li>
                ))}
              </ul>
            </div>

            {/* Without Facturino */}
            <div className="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm relative overflow-hidden">
              <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-300 to-red-400"></div>
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                  <IconXMark />
                </div>
                <h3 className="text-xl font-bold text-gray-900">{t.comparison.withoutFacturino.label}</h3>
              </div>
              <ul className="space-y-4">
                {t.comparison.withoutFacturino.items.map((item, i) => (
                  <li key={i} className="flex items-start gap-3">
                    <IconXMark />
                    <span className="text-gray-500">{item}</span>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>
      </section>

      {/* ── PROMISE ──────────────────────────────────────────── */}
      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-50 to-cyan-50 pointer-events-none"></div>
        <div className="container relative z-10 px-4 sm:px-6">
          <div className="max-w-3xl mx-auto text-center">
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-100 mb-6">
              <svg className="w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
              </svg>
            </div>
            <h2 className="text-3xl md:text-4xl font-bold mb-6 text-gray-900">{t.promise.title}</h2>
            <div className="glass-panel rounded-2xl p-8 md:p-10">
              <p className="text-lg md:text-xl text-gray-700 leading-relaxed">
                {t.promise.body}
              </p>
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
