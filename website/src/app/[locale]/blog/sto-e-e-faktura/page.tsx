import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/sto-e-e-faktura', {
    title: {
      mk: 'Што е е-фактура и зошто е задолжителна? — Facturino',
      en: 'What is e-Invoice and Why is it Mandatory? — Facturino',
      sq: 'Çfarë është e-fatura dhe pse është e detyrueshme? — Facturino',
      tr: 'E-fatura nedir ve neden zorunludur? — Facturino',
    },
    description: {
      mk: 'Дознајте што е е-фактура во Македонија, кој е задолжен да ја користи, UBL форматот, предностите и како Facturino помага со усогласеност.',
      en: 'Learn what e-invoice is in Macedonia, who must use it, the UBL format, benefits, and how Facturino helps with compliance.',
      sq: 'Mësoni çfarë është e-fatura në Maqedoni, kush duhet ta përdorë, formati UBL, përfitimet dhe si ndihmon Facturino.',
      tr: 'Makedonya\'da e-fatura nedir, kimler kullanmalı, UBL formatı, avantajları ve Facturino nasıl yardımcı olur öğrenin.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Водич',
    title: 'Што е е-фактура и зошто е задолжителна?',
    publishDate: '1 февруари 2026',
    readTime: '7 мин читање',
    intro:
      'Електронското фактурирање веќе не е иднина — тоа е сегашност. Македонија донесе Закон за електронско фактурирање кој ги обврзува компаниите да преминат на дигитален формат. Во овој водич објаснуваме што точно е е-фактура, кој мора да ја користи, во кој формат се издава и зошто е подобра од хартиената фактура.',
    sections: [
      {
        title: 'Што е е-фактура?',
        content:
          'Е-фактура (електронска фактура) е фактура издадена, испратена и примена во структуриран електронски формат кој овозможува автоматска обработка. За разлика од скенирана PDF фактура или фактура испратена по е-пошта, е-фактурата содржи машински читливи податоци во стандардизиран XML формат. Ова значи дека софтверот на примачот може автоматски да ги прочита сите полиња — од износот и ДДВ-то до деталите за купувачот — без рачно внесување.',
        items: null,
      },
      {
        title: 'Законска основа и обврска',
        content:
          'Законот за електронско фактурирање во Република Северна Македонија ја воведува обврската за издавање е-фактури при трансакции со јавниот сектор (B2G), а постепено и при трансакции меѓу приватни компании (B2B). Целта е намалување на даночната евазија, зголемување на транспарентноста и модернизација на деловните процеси.',
        items: [
          'B2G трансакции (бизнис кон јавен сектор) — задолжително е-фактурирање',
          'B2B трансакции (бизнис кон бизнис) — постепено воведување со законски рокови',
          'Сите ДДВ обврзници се засегнати од регулативата',
          'Е-фактурата мора да биде во UBL 2.1 XML формат',
          'Дигиталниот потпис (QES) обезбедува автентичност и интегритет на документот',
        ],
      },
      {
        title: 'UBL формат: Стандардот зад е-фактурата',
        content:
          'UBL (Universal Business Language) верзија 2.1 е меѓународниот стандард кој го користи Македонија за е-фактури. UBL е XML базиран формат кој дефинира точна структура за сите податоци на фактурата. Секоја е-фактура содржи заглавие со податоци за издавачот и примачот, ставки со описи, количини и цени, ДДВ пресметка по стапки и вкупни износи. Предноста на UBL е интероперабилноста — фактурите можат да се разменуваат меѓу различни софтверски системи без загуба на податоци.',
        items: null,
      },
      {
        title: 'Предности на е-фактурирањето',
        content: null,
        items: [
          'Брзина — е-фактурата стигнува за секунди наместо денови по пошта',
          'Точност — автоматската обработка ги елиминира грешките при рачно внесување',
          'Усогласеност — е-фактурата автоматски ги исполнува законските барања',
          'Ревизорска трага — секоја фактура има дигитален потпис и временски печат',
          'Заштеда — нема трошоци за печатење, пликови и поштарина',
          'Еколошки — без хартија, без физичко складирање',
          'Побрзо плаќање — автоматска обработка значи побрз циклус на наплата',
        ],
      },
      {
        title: 'Како Facturino го олеснува е-фактурирањето',
        content:
          'Facturino е дизајниран специјално за македонскиот пазар и целосно го поддржува UBL 2.1 стандардот. Кога креирате фактура во Facturino, системот автоматски генерира валиден UBL XML документ со сите задолжителни полиња. Можете да ја потпишете со квалификуван електронски потпис (QES) директно од платформата, без потреба од надворешен софтвер.',
        items: [
          'Автоматско генерирање на UBL 2.1 XML за секоја фактура',
          'Вграден QES (квалификуван електронски потпис) за дигитално потпишување',
          'Валидација на сите задолжителни полиња пред испраќање',
          'Директно испраќање до примачот преку е-пошта или портал',
          'Архивирање и чување согласно законските барања',
        ],
      },
    ],
    cta: {
      title: 'Преминете на е-фактура со Facturino',
      desc: 'UBL формат, дигитален потпис и автоматска валидација — сe во една платформа. Бидете усогласени со закон без компликации.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Guide',
    title: 'What is e-Invoice and Why is it Mandatory?',
    publishDate: 'February 1, 2026',
    readTime: '7 min read',
    intro:
      'Electronic invoicing is no longer the future — it is the present. Macedonia has enacted an E-Invoicing Law that requires companies to transition to digital formats. In this guide, we explain what an e-invoice actually is, who must use it, what format it follows, and why it is better than paper invoices.',
    sections: [
      {
        title: 'What is an e-invoice?',
        content:
          'An e-invoice (electronic invoice) is an invoice that is issued, sent, and received in a structured electronic format enabling automatic processing. Unlike a scanned PDF or an invoice sent by email, an e-invoice contains machine-readable data in a standardized XML format. This means the recipient\'s software can automatically read every field — from the amount and VAT to the buyer details — without manual data entry.',
        items: null,
      },
      {
        title: 'Legal basis and obligation',
        content:
          'The Law on Electronic Invoicing in the Republic of North Macedonia introduces the obligation to issue e-invoices for public-sector transactions (B2G), with a gradual rollout for business-to-business transactions (B2B). The goal is to reduce tax evasion, increase transparency, and modernize business processes.',
        items: [
          'B2G transactions (business to government) — e-invoicing is mandatory',
          'B2B transactions (business to business) — phased introduction with legal deadlines',
          'All VAT-registered entities are affected by the regulation',
          'E-invoices must be in UBL 2.1 XML format',
          'A Qualified Electronic Signature (QES) ensures document authenticity and integrity',
        ],
      },
      {
        title: 'UBL format: The standard behind e-invoicing',
        content:
          'UBL (Universal Business Language) version 2.1 is the international standard used by Macedonia for e-invoices. UBL is an XML-based format that defines a precise structure for all invoice data. Each e-invoice contains a header with issuer and recipient details, line items with descriptions, quantities, and prices, VAT calculations by rate, and totals. The advantage of UBL is interoperability — invoices can be exchanged between different software systems without data loss.',
        items: null,
      },
      {
        title: 'Benefits of e-invoicing',
        content: null,
        items: [
          'Speed — an e-invoice arrives in seconds instead of days by post',
          'Accuracy — automatic processing eliminates manual data entry errors',
          'Compliance — e-invoices automatically meet legal requirements',
          'Audit trail — every invoice has a digital signature and timestamp',
          'Cost savings — no printing, envelopes, or postage costs',
          'Eco-friendly — no paper, no physical storage needed',
          'Faster payments — automatic processing means a shorter collection cycle',
        ],
      },
      {
        title: 'How Facturino simplifies e-invoicing',
        content:
          'Facturino is designed specifically for the Macedonian market and fully supports the UBL 2.1 standard. When you create an invoice in Facturino, the system automatically generates a valid UBL XML document with all mandatory fields. You can sign it with a Qualified Electronic Signature (QES) directly from the platform, without needing external software.',
        items: [
          'Automatic UBL 2.1 XML generation for every invoice',
          'Built-in QES (Qualified Electronic Signature) for digital signing',
          'Validation of all mandatory fields before sending',
          'Direct delivery to recipients via email or portal',
          'Archiving and storage in compliance with legal requirements',
        ],
      },
    ],
    cta: {
      title: 'Switch to e-invoicing with Facturino',
      desc: 'UBL format, digital signature, and automatic validation — all in one platform. Stay compliant without the complexity.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Çfarë është e-fatura dhe pse është e detyrueshme?',
    publishDate: '1 shkurt 2026',
    readTime: '7 min lexim',
    intro:
      'Faturimi elektronik nuk është më e ardhmja — është e tashmja. Maqedonia ka miratuar Ligjin për Faturimin Elektronik që i detyron kompanitë të kalojnë në format digjital. Në këtë udhëzues shpjegojmë çfarë është e-fatura, kush duhet ta përdorë, në çfarë formati lëshohet dhe pse është më e mirë se fatura në letër.',
    sections: [
      {
        title: 'Çfarë është e-fatura?',
        content:
          'E-fatura (fatura elektronike) është një faturë e lëshuar, dërguar dhe marrë në format elektronik të strukturuar që mundëson përpunim automatik. Ndryshe nga një PDF e skenuar ose një faturë e dërguar me email, e-fatura përmban të dhëna të lexueshme nga makina në format XML të standardizuar. Kjo do të thotë që softueri i marrësit mund të lexojë automatikisht çdo fushë — nga shuma dhe TVSH-ja deri te detajet e blerësit — pa futje manuale të të dhënave.',
        items: null,
      },
      {
        title: 'Baza ligjore dhe detyrimi',
        content:
          'Ligji për Faturimin Elektronik në Republikën e Maqedonisë së Veriut paraqet detyrimin për lëshimin e e-faturave për transaksionet me sektorin publik (B2G), me zbatim gradual edhe për transaksionet ndërmjet bizneseve (B2B). Qëllimi është zvogëlimi i evazionit tatimor, rritja e transparencës dhe modernizimi i proceseve të biznesit.',
        items: [
          'Transaksionet B2G (biznes ndaj qeverisë) — e-faturimi është i detyrueshëm',
          'Transaksionet B2B (biznes ndaj biznesit) — futje graduale me afate ligjore',
          'Të gjithë subjektet e regjistruar për TVSH preken nga rregullorja',
          'E-faturat duhet të jenë në format UBL 2.1 XML',
          'Nënshkrimi Elektronik i Kualifikuar (QES) siguron autenticitetin dhe integritetin',
        ],
      },
      {
        title: 'Formati UBL: Standardi pas e-faturimit',
        content:
          'UBL (Universal Business Language) versioni 2.1 është standardi ndërkombëtar që përdor Maqedonia për e-faturat. UBL është format i bazuar në XML që përcakton strukturë të saktë për të gjitha të dhënat e faturës. Çdo e-faturë përmban kokën me të dhëna të lëshuesit dhe marrësit, zëra me përshkrime, sasi dhe çmime, llogaritje të TVSH-së sipas normave dhe totale. Avantazhi i UBL-së është ndërveprimshmëria — faturat mund të shkëmbehen ndërmjet sistemeve të ndryshme pa humbje të dhënash.',
        items: null,
      },
      {
        title: 'Përfitimet e e-faturimit',
        content: null,
        items: [
          'Shpejtësi — e-fatura arrin në sekonda në vend të ditëve me postë',
          'Saktësi — përpunimi automatik eliminon gabimet e futjes manuale',
          'Pajtueshmëri — e-faturat plotësojnë automatikisht kërkesat ligjore',
          'Gjurmë auditimi — çdo faturë ka nënshkrim digjital dhe vulë kohore',
          'Kursim kostosh — pa printim, zarfe apo postë',
          'Miqësore me mjedisin — pa letër, pa ruajtje fizike',
          'Pagesa më të shpejta — përpunimi automatik shkurton ciklin e arkëtimit',
        ],
      },
      {
        title: 'Si e thjeshton Facturino e-faturimin',
        content:
          'Facturino është projektuar posaçërisht për tregun maqedonas dhe mbështet plotësisht standardin UBL 2.1. Kur krijoni një faturë në Facturino, sistemi gjeneron automatikisht dokument UBL XML valid me të gjitha fushat e detyrueshme. Mund ta nënshkruani me Nënshkrim Elektronik të Kualifikuar (QES) direkt nga platforma, pa nevojë për softuer të jashtëm.',
        items: [
          'Gjenerim automatik i UBL 2.1 XML për çdo faturë',
          'QES i integruar (Nënshkrim Elektronik i Kualifikuar) për nënshkrim digjital',
          'Validim i të gjitha fushave të detyrueshme para dërgimit',
          'Dërgim direkt te marrësi përmes emailit ose portalit',
          'Arkivim dhe ruajtje në përputhje me kërkesat ligjore',
        ],
      },
    ],
    cta: {
      title: 'Kaloni në e-faturim me Facturino',
      desc: 'Format UBL, nënshkrim digjital dhe validim automatik — të gjitha në një platformë. Qëndroni në pajtueshmëri pa komplikime.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Rehber',
    title: 'E-fatura nedir ve neden zorunludur?',
    publishDate: '1 Şubat 2026',
    readTime: '7 dk okuma',
    intro:
      'Elektronik faturalama artık gelecek değil — bugündür. Makedonya, şirketleri dijital formata geçmeye zorunlu kılan bir E-Fatura Yasası çıkardı. Bu rehberde e-faturanın ne olduğunu, kimlerin kullanması gerektiğini, hangi formatta düzenlendiğini ve kağıt faturadan neden daha iyi olduğunu açıklıyoruz.',
    sections: [
      {
        title: 'E-fatura nedir?',
        content:
          'E-fatura (elektronik fatura), otomatik işlemeyi mümkün kılan yapılandırılmış elektronik formatta düzenlenen, gönderilen ve alınan bir faturadır. Taranmış bir PDF veya e-posta ile gönderilen bir faturadan farklı olarak, e-fatura standartlaştırılmış XML formatında makine tarafından okunabilir veriler içerir. Bu, alıcının yazılımının her alanı — tutar ve KDV\'den alıcı bilgilerine kadar — manuel veri girişi olmadan otomatik olarak okuyabileceği anlamına gelir.',
        items: null,
      },
      {
        title: 'Yasal dayanak ve zorunluluk',
        content:
          'Kuzey Makedonya Cumhuriyeti\'ndeki Elektronik Faturalama Yasası, kamu sektörü işlemleri (B2G) için e-fatura düzenleme zorunluluğunu getirmekte, işletmeler arası işlemler (B2B) için ise kademeli bir geçiş öngörmektedir. Amaç vergi kaçakçılığını azaltmak, şeffaflığı artırmak ve iş süreçlerini modernize etmektir.',
        items: [
          'B2G işlemleri (işletmeden kamu kurumuna) — e-faturalama zorunludur',
          'B2B işlemleri (işletmeden işletmeye) — yasal tarihlerle kademeli geçiş',
          'KDV mükellefi olan tüm kuruluşlar düzenlemeden etkilenmektedir',
          'E-faturalar UBL 2.1 XML formatında olmalıdır',
          'Nitelikli Elektronik İmza (QES) belgenin özgünlüğünü ve bütünlüğünü sağlar',
        ],
      },
      {
        title: 'UBL formatı: E-faturanın arkasındaki standart',
        content:
          'UBL (Evrensel İş Dili) sürüm 2.1, Makedonya\'nın e-faturalar için kullandığı uluslararası standarttır. UBL, tüm fatura verileri için kesin bir yapı tanımlayan XML tabanlı bir formattır. Her e-fatura, düzenleyen ve alıcı bilgilerini içeren başlık, açıklama, miktar ve fiyat içeren kalemler, orana göre KDV hesaplamaları ve toplamları içerir. UBL\'nin avantajı birlikte çalışabilirliktir — faturalar veri kaybı olmadan farklı yazılım sistemleri arasında değiştirilebilir.',
        items: null,
      },
      {
        title: 'E-faturalamanın avantajları',
        content: null,
        items: [
          'Hız — e-fatura posta ile günler yerine saniyeler içinde ulaşır',
          'Doğruluk — otomatik işleme manuel veri girişi hatalarını ortadan kaldırır',
          'Uyumluluk — e-faturalar yasal gereksinimleri otomatik olarak karşılar',
          'Denetim izi — her faturanın dijital imzası ve zaman damgası vardır',
          'Maliyet tasarrufu — baskı, zarf veya posta masrafı yok',
          'Çevre dostu — kağıt yok, fiziksel depolama gerekmiyor',
          'Daha hızlı ödemeler — otomatik işleme tahsilat döngüsünü kısaltır',
        ],
      },
      {
        title: 'Facturino e-faturayı nasıl kolaylaştırır',
        content:
          'Facturino özellikle Makedonya pazarı için tasarlanmıştır ve UBL 2.1 standardını tam olarak destekler. Facturino\'da bir fatura oluşturduğunuzda, sistem tüm zorunlu alanlarla birlikte otomatik olarak geçerli bir UBL XML belgesi üretir. Harici yazılıma ihtiyaç duymadan doğrudan platformdan Nitelikli Elektronik İmza (QES) ile imzalayabilirsiniz.',
        items: [
          'Her fatura için otomatik UBL 2.1 XML oluşturma',
          'Dijital imzalama için yerleşik QES (Nitelikli Elektronik İmza)',
          'Göndermeden önce tüm zorunlu alanların doğrulanması',
          'E-posta veya portal aracılığıyla alıcıya doğrudan teslim',
          'Yasal gereksinimlere uygun arşivleme ve saklama',
        ],
      },
    ],
    cta: {
      title: 'Facturino ile e-faturaya geçin',
      desc: 'UBL formatı, dijital imza ve otomatik doğrulama — hepsi tek platformda. Karmaşıklık olmadan uyumlu kalın.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function StoEEFakturaPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ============================================================ */}
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        {/* Background blobs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>

        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          {/* Back link */}
          <Link
            href={`/${locale}/blog`}
            className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors"
          >
            {t.backLink}
          </Link>

          {/* Tag pill */}
          <div className="mb-4">
            <span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600">
              {t.tag}
            </span>
          </div>

          {/* Title */}
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            {t.title}
          </h1>

          {/* Meta info */}
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {t.publishDate}
            </span>
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {t.readTime}
            </span>
          </div>

          {/* Intro paragraph */}
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.intro}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  ARTICLE BODY                                                */}
      {/* ============================================================ */}
      <section className="py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="space-y-12">
            {t.sections.map((section, i) => (
              <div key={i}>
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                  {section.title}
                </h2>

                {/* Paragraph content */}
                {section.content && (
                  <p className="text-gray-700 leading-relaxed text-lg">
                    {section.content}
                  </p>
                )}

                {/* Bullet list items */}
                {section.items && (
                  <ul className="space-y-3 mt-2">
                    {section.items.map((item, j) => (
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
                          {item}
                        </span>
                      </li>
                    ))}
                  </ul>
                )}
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
            {t.cta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.cta.desc}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.cta.button}
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
// CLAUDE-CHECKPOINT
