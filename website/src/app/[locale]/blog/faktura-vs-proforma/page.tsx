import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/faktura-vs-proforma', {
    title: {
      mk: 'Фактура vs профактура: Клучни разлики — Facturino',
      en: 'Invoice vs Proforma: Key Differences — Facturino',
      sq: 'Fatura vs profatura: Dallimet kryesore — Facturino',
      tr: 'Fatura ve proforma: Temel farklar — Facturino',
    },
    description: {
      mk: 'Дознајте ги разликите меѓу фактура и профактура — правен статус, ДДВ импликации, кога да ги користите и најдобри практики за македонски бизниси.',
      en: 'Learn the differences between an invoice and proforma — legal status, VAT implications, when to use each, and best practices for Macedonian businesses.',
      sq: 'Mësoni dallimet ndërmjet faturës dhe profaturës — statusi ligjor, implikimet e TVSH-së, kur t\'i përdorni dhe praktikat më të mira.',
      tr: 'Fatura ve proforma arasındaki farkları öğrenin — yasal statü, KDV etkileri, ne zaman kullanılır ve en iyi uygulamalar.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Едукација',
    title: 'Фактура vs профактура: Клучни разлики',
    publishDate: '7 февруари 2026',
    readTime: '5 мин читање',
    intro:
      'Фактура и профактура — два документи кои често се мешаат, но имаат суштински различна правна и даночна функција. Разбирањето на разликите е клучно за правилно водење на бизнисот во Македонија. Во овој напис објаснуваме што е секој документ, кога се користи и какви ДДВ импликации има.',
    sections: [
      {
        title: 'Што е фактура?',
        content:
          'Фактурата е официјален даночен документ кој го потврдува извршениот промет на стоки или услуги. Таа создава правна обврска за плаќање и е основа за пресметка и одбиток на ДДВ. Фактурата мора да ги содржи сите задолжителни елементи предвидени со Законот за ДДВ — ЕДБ на продавачот и купувачот, детален опис на ставките, ДДВ рекапитулација и вкупен износ. Секоја издадена фактура мора да биде евидентирана во книгите на компанијата и пријавена до УЈП.',
        items: null,
      },
      {
        title: 'Што е профактура?',
        content:
          'Профактурата (предфактура) е документ кој има информативен карактер. Таа претставува понуда или предлог за плаќање, но не создава законска обврска за купувачот. Профактурата се издава пред извршувањето на прометот — најчесто како предуслов за авансно плаќање или за информирање на купувачот за очекуваните трошоци. Профактурата не е даночен документ и не влегува во ДДВ евиденцијата.',
        items: null,
      },
      {
        title: 'Клучни разлики',
        content: null,
        items: [
          'Правен статус — фактурата е правно обврзувачка, профактурата не е',
          'ДДВ — фактурата влегува во ДДВ пријавата, профактурата не',
          'Обврска за плаќање — фактурата создава обврска, профактурата е само понуда',
          'Евиденција — фактурата мора да се евидентира во книгите, профактурата не',
          'Секвенцијален број — фактурата има задолжителен секвенцијален број, профактурата може да има слободна нумерација',
          'Датум на промет — фактурата го наведува датумот на извршен промет, профактурата нема промет',
          'Правна заштита — фактурата е доказ пред суд, профактурата не',
        ],
      },
      {
        title: 'Кога да користите профактура',
        content:
          'Профактурата е корисен деловен алат во неколку ситуации. Користете ја кога сакате да го информирате купувачот за цената пред да ја извршите услугата, кога барате авансно плаќање пред испорака, за царински цели при увоз/извоз или кога треба да добиете одобрение на буџет од клиентот пред да започнете со работа.',
        items: [
          'Авансно плаќање — испратете профактура за клиентот да плати пред испорака',
          'Понуда со детали — покажете точно што ќе биде фактурирано',
          'Царински формалности — потребна при увоз за декларирање на вредноста',
          'Буџетско одобрение — клиентот може да ја користи за интерно одобрување',
          'Меѓународна трговија — стандардна практика за прекугранични трансакции',
        ],
      },
      {
        title: 'Најдобри практики за македонски бизниси',
        content:
          'За да избегнете проблеми со УЈП и да одржите професионален однос со клиентите, следете ги овие најдобри практики при работа со фактури и профактури.',
        items: [
          'Секогаш издадете фактура по извршениот промет — профактурата не ја заменува фактурата',
          'Јасно означете го документот — напишете „ПРОФАКТУРА" за да нема забуна',
          'Не вклучувајте ги профактурите во ДДВ пријавата — тоа е грешка која може да доведе до казна',
          'Поврзете ја профактурата со фактурата — наведете го бројот на профактурата на конечната фактура',
          'Чувајте ги и двата документи — и профактурата и фактурата треба да бидат архивирани',
          'Користете софтвер кој ги разликува — Facturino автоматски ги раздвојува во посебни регистри',
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура: Чекор-по-чекор водич' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура во Македонија' },
      { slug: 'recurring-invoices-mk', title: 'Повторувачки фактури: Автоматизирајте ја наплатата' },
    ],
    cta: {
      title: 'Фактури и профактури — сe на едно место',
      desc: 'Facturino автоматски ги раздвојува фактурите од профактурите, ги води во посебни регистри и обезбедува точна ДДВ евиденција.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Education',
    title: 'Invoice vs Proforma: Key Differences',
    publishDate: 'February 7, 2026',
    readTime: '5 min read',
    intro:
      'Invoice and proforma invoice — two documents that are often confused but serve fundamentally different legal and tax functions. Understanding the differences is crucial for running a business properly in Macedonia. In this article, we explain what each document is, when to use it, and what VAT implications it carries.',
    sections: [
      {
        title: 'What is an invoice?',
        content:
          'An invoice is an official tax document that confirms the completed supply of goods or services. It creates a legal obligation for payment and serves as the basis for VAT calculation and deduction. An invoice must contain all mandatory elements prescribed by the VAT Law — the seller\'s and buyer\'s EDB, detailed item descriptions, VAT recapitulation, and the total amount. Every issued invoice must be recorded in the company\'s books and reported to UJP.',
        items: null,
      },
      {
        title: 'What is a proforma invoice?',
        content:
          'A proforma invoice is a document of an informational nature. It represents an offer or a payment proposal but does not create a legal obligation for the buyer. A proforma invoice is issued before the supply takes place — most commonly as a prerequisite for advance payment or to inform the buyer of expected costs. A proforma invoice is not a tax document and does not enter the VAT records.',
        items: null,
      },
      {
        title: 'Key differences',
        content: null,
        items: [
          'Legal status — an invoice is legally binding, a proforma is not',
          'VAT — an invoice enters the VAT return, a proforma does not',
          'Payment obligation — an invoice creates an obligation, a proforma is just an offer',
          'Record-keeping — an invoice must be recorded in the books, a proforma does not',
          'Sequential number — an invoice requires a mandatory sequential number, a proforma can have flexible numbering',
          'Supply date — an invoice states the date of completed supply, a proforma has no supply',
          'Legal protection — an invoice is evidence in court, a proforma is not',
        ],
      },
      {
        title: 'When to use a proforma invoice',
        content:
          'A proforma invoice is a useful business tool in several situations. Use it when you want to inform the buyer of the price before performing the service, when requesting advance payment before delivery, for customs purposes in import/export, or when you need budget approval from the client before starting work.',
        items: [
          'Advance payment — send a proforma so the client pays before delivery',
          'Detailed quote — show exactly what will be invoiced',
          'Customs formalities — required for import to declare the value',
          'Budget approval — the client can use it for internal approval',
          'International trade — standard practice for cross-border transactions',
        ],
      },
      {
        title: 'Best practices for Macedonian businesses',
        content:
          'To avoid issues with UJP and maintain a professional relationship with your clients, follow these best practices when working with invoices and proforma invoices.',
        items: [
          'Always issue an invoice after the supply is completed — a proforma does not replace an invoice',
          'Clearly label the document — write "PROFORMA" to avoid confusion',
          'Do not include proformas in your VAT return — this is a mistake that can lead to penalties',
          'Link the proforma to the invoice — reference the proforma number on the final invoice',
          'Keep both documents — both the proforma and invoice should be archived',
          'Use software that distinguishes them — Facturino automatically separates them into different registers',
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice: Step-by-Step Guide' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements in Macedonia' },
      { slug: 'recurring-invoices-mk', title: 'Recurring Invoices: Automate Your Billing' },
    ],
    cta: {
      title: 'Invoices and proformas — all in one place',
      desc: 'Facturino automatically separates invoices from proformas, maintains separate registers, and ensures accurate VAT records.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Edukim',
    title: 'Fatura vs profatura: Dallimet kryesore',
    publishDate: '7 shkurt 2026',
    readTime: '5 min lexim',
    intro:
      'Fatura dhe profatura — dy dokumente që shpesh ngatërrohen por shërbejnë funksione thelbësisht të ndryshme ligjore dhe tatimore. Kuptimi i dallimeve është vendimtar për drejtimin e saktë të biznesit në Maqedoni. Në këtë artikull shpjegojmë çfarë është secili dokument, kur të përdoret dhe çfarë implikimesh të TVSH-së ka.',
    sections: [
      {
        title: 'Çfarë është fatura?',
        content:
          'Fatura është dokument zyrtar tatimor që konfirmon furnizimin e përfunduar të mallrave ose shërbimeve. Ajo krijon detyrim ligjor për pagesë dhe shërben si bazë për llogaritjen dhe zbritjen e TVSH-së. Fatura duhet të përmbajë të gjitha elementet e detyrueshme të parashikuara nga Ligji për TVSH-në — EDB e shitësit dhe blerësit, përshkrime të detajuara të zërave, rekapitulim të TVSH-së dhe shumën totale. Çdo faturë e lëshuar duhet regjistruar në librat e kompanisë dhe raportuar në UJP.',
        items: null,
      },
      {
        title: 'Çfarë është profatura?',
        content:
          'Profatura është dokument me karakter informues. Ajo përfaqëson një ofertë ose propozim pagese por nuk krijon detyrim ligjor për blerësin. Profatura lëshohet para se të ndodhë furnizimi — zakonisht si parakusht për pagesë paraprake ose për të informuar blerësin për kostot e pritura. Profatura nuk është dokument tatimor dhe nuk hyn në evidencën e TVSH-së.',
        items: null,
      },
      {
        title: 'Dallimet kryesore',
        content: null,
        items: [
          'Statusi ligjor — fatura është ligjërisht detyruese, profatura nuk është',
          'TVSH — fatura hyn në deklaratën e TVSH-së, profatura jo',
          'Detyrimi i pagesës — fatura krijon detyrim, profatura është vetëm ofertë',
          'Evidenca — fatura duhet regjistruar në libra, profatura jo',
          'Numri sekuencial — fatura kërkon numër sekuencial të detyrueshëm, profatura mund të ketë numërim fleksibël',
          'Data e furnizimit — fatura tregon datën e furnizimit të përfunduar, profatura nuk ka furnizim',
          'Mbrojtja ligjore — fatura është provë në gjykatë, profatura jo',
        ],
      },
      {
        title: 'Kur të përdorni profaturën',
        content:
          'Profatura është mjet i dobishëm biznesi në disa situata. Përdoreni kur dëshironi ta informoni blerësin për çmimin para kryerjes së shërbimit, kur kërkoni pagesë paraprake para dorëzimit, për qëllime doganore në import/eksport ose kur nevojitet miratim buxheti nga klienti para fillimit të punës.',
        items: [
          'Pagesë paraprake — dërgoni profaturë që klienti të paguajë para dorëzimit',
          'Ofertë e detajuar — tregoni saktësisht çfarë do të faturohet',
          'Formalitete doganore — e nevojshme për import për deklarimin e vlerës',
          'Miratim buxheti — klienti mund ta përdorë për miratim të brendshëm',
          'Tregtia ndërkombëtare — praktikë standarde për transaksione ndërkufitare',
        ],
      },
      {
        title: 'Praktikat më të mira për bizneset maqedonase',
        content:
          'Për të shmangur problemet me UJP dhe për të mbajtur marrëdhënie profesionale me klientët, ndiqni këto praktika kur punoni me fatura dhe profatura.',
        items: [
          'Gjithmonë lëshoni faturë pas furnizimit — profatura nuk e zëvendëson faturën',
          'Etiketoni qartë dokumentin — shkruani "PROFATURË" për të shmangur konfuzionin',
          'Mos përfshini profaturat në deklaratën e TVSH-së — kjo gabim mund të çojë në gjoba',
          'Lidhni profaturën me faturën — referoni numrin e profaturës në faturën përfundimtare',
          'Ruani të dy dokumentet — profatura dhe fatura duhen arkivuar',
          'Përdorni softuer që i dallon — Facturino i ndan automatikisht në regjistra të ndryshëm',
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni një faturë: Udhëzues hap pas hapi' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme të faturës në Maqedoni' },
      { slug: 'recurring-invoices-mk', title: 'Faturat e përsëritura: Automatizoni arkëtimin' },
    ],
    cta: {
      title: 'Fatura dhe profatura — të gjitha në një vend',
      desc: 'Facturino i ndan automatikisht faturat nga profaturat, mban regjistra të veçantë dhe siguron evidencë të saktë të TVSH-së.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Eğitim',
    title: 'Fatura ve proforma: Temel farklar',
    publishDate: '7 Şubat 2026',
    readTime: '5 dk okuma',
    intro:
      'Fatura ve proforma fatura — sıklıkla karıştırılan ancak temelden farklı yasal ve vergisel işlevlere sahip iki belge. Farkları anlamak, Makedonya\'da doğru iş yönetimi için kritik öneme sahiptir. Bu makalede her belgenin ne olduğunu, ne zaman kullanılacağını ve KDV etkilerini açıklıyoruz.',
    sections: [
      {
        title: 'Fatura nedir?',
        content:
          'Fatura, mal veya hizmet tesliminin tamamlandığını belgeleyen resmi bir vergi belgesidir. Ödeme için yasal yükümlülük oluşturur ve KDV hesaplaması ile indiriminin temelini oluşturur. Fatura, KDV Yasasının öngördüğü tüm zorunlu unsurları içermelidir — satıcı ve alıcının EDB\'si, detaylı kalem açıklamaları, KDV rekapitülasyonu ve toplam tutar. Düzenlenen her fatura şirketin defterlerine kaydedilmeli ve UJP\'ye bildirilmelidir.',
        items: null,
      },
      {
        title: 'Proforma fatura nedir?',
        content:
          'Proforma fatura bilgilendirme niteliğinde bir belgedir. Bir teklifi veya ödeme önerisini temsil eder ancak alıcı için yasal yükümlülük oluşturmaz. Proforma fatura teslimattan önce düzenlenir — genellikle avans ödemesi için ön koşul olarak veya alıcıyı beklenen maliyetler hakkında bilgilendirmek için. Proforma fatura bir vergi belgesi değildir ve KDV kayıtlarına girmez.',
        items: null,
      },
      {
        title: 'Temel farklar',
        content: null,
        items: [
          'Yasal statü — fatura yasal olarak bağlayıcıdır, proforma değildir',
          'KDV — fatura KDV beyannamesine girer, proforma girmez',
          'Ödeme yükümlülüğü — fatura yükümlülük oluşturur, proforma sadece bir tekliftir',
          'Kayıt tutma — fatura defterlere kaydedilmelidir, proforma edilmez',
          'Sıralı numara — fatura zorunlu sıralı numara gerektirir, proforma esnek numaralamaya sahip olabilir',
          'Teslim tarihi — fatura tamamlanan teslim tarihini belirtir, proformada teslim yoktur',
          'Yasal koruma — fatura mahkemede delildir, proforma değildir',
        ],
      },
      {
        title: 'Proforma faturayı ne zaman kullanmalı',
        content:
          'Proforma fatura çeşitli durumlarda faydalı bir iş aracıdır. Hizmeti gerçekleştirmeden önce alıcıyı fiyat hakkında bilgilendirmek istediğinizde, teslimattan önce avans ödemesi talep ederken, ithalat/ihracatta gümrük işlemleri için veya işe başlamadan önce müşteriden bütçe onayı almanız gerektiğinde kullanın.',
        items: [
          'Avans ödemesi — müşterinin teslimattan önce ödemesi için proforma gönderin',
          'Detaylı teklif — tam olarak neyin faturalanacağını gösterin',
          'Gümrük işlemleri — ithalatta değer beyanı için gereklidir',
          'Bütçe onayı — müşteri dahili onay için kullanabilir',
          'Uluslararası ticaret — sınır ötesi işlemler için standart uygulama',
        ],
      },
      {
        title: 'Makedon işletmeleri için en iyi uygulamalar',
        content:
          'UJP ile sorun yaşamamak ve müşterilerinizle profesyonel ilişkinizi sürdürmek için fatura ve proforma faturalarla çalışırken bu en iyi uygulamaları takip edin.',
        items: [
          'Teslimat tamamlandıktan sonra mutlaka fatura düzenleyin — proforma faturanın yerini tutmaz',
          'Belgeyi net olarak etiketleyin — karışıklığı önlemek için "PROFORMA" yazın',
          'Proformaları KDV beyannamenize dahil etmeyin — bu cezaya yol açabilecek bir hatadır',
          'Proformayı faturaya bağlayın — nihai faturada proforma numarasına atıfta bulunun',
          'Her iki belgeyi de saklayın — hem proforma hem fatura arşivlenmelidir',
          'Ayırt eden yazılım kullanın — Facturino otomatik olarak farklı kayıtlara ayırır',
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Fatura nasıl oluşturulur: Adım adım rehber' },
      { slug: 'zadolzitelni-elementi-faktura', title: "Makedonya'da faturanın zorunlu unsurları" },
      { slug: 'recurring-invoices-mk', title: 'Tekrarlayan faturalar: Tahsilatı otomatikleştirin' },
    ],
    cta: {
      title: 'Faturalar ve proformalar — hepsi tek yerde',
      desc: 'Facturino faturaları proformalardan otomatik olarak ayırır, ayrı kayıtlar tutar ve doğru KDV kaydı sağlar.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function FakturaVsProformaPage({
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

      {/* RELATED ARTICLES */}
      <section className="py-12 md:py-16 bg-gray-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">{t.relatedTitle}</h2>
          <div className="grid gap-4">
            {t.related.map((r) => (
              <Link
                key={r.slug}
                href={`/${locale}/blog/${r.slug}`}
                className="group flex items-center justify-between bg-white rounded-xl border border-gray-100 px-6 py-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
              >
                <span className="text-gray-900 font-medium group-hover:text-indigo-600 transition-colors">{r.title}</span>
                <svg className="w-5 h-5 text-gray-400 group-hover:text-indigo-600 flex-shrink-0 ml-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </Link>
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
