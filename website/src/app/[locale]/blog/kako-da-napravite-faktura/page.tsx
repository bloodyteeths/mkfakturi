import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/kako-da-napravite-faktura', {
    title: {
      mk: 'Како да направите фактура: Чекор-по-чекор водич — Facturino',
      en: 'How to Create an Invoice: Step-by-Step Guide — Facturino',
      sq: 'Si të krijoni një faturë: Udhëzues hap pas hapi — Facturino',
      tr: 'Fatura nasıl oluşturulur: Adım adım rehber — Facturino',
    },
    description: {
      mk: 'Научете како правилно да креирате фактура во Македонија — задолжителни полиња, ЕДБ, ЕМБС, ДДВ, чести грешки и како Facturino го олеснува процесот.',
      en: 'Learn how to properly create an invoice in Macedonia — required fields, EDB, EMBS, VAT, common mistakes, and how Facturino simplifies the process.',
      sq: 'Mësoni si të krijoni saktë një faturë në Maqedoni — fushat e detyrueshme, EDB, EMBS, TVSH, gabime të zakonshme dhe si ndihmon Facturino.',
      tr: 'Makedonya\'da nasıl doğru fatura oluşturulacağını öğrenin — zorunlu alanlar, EDB, EMBS, KDV, yaygın hatalar ve Facturino nasıl kolaylaştırır.',
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
    title: 'Како да направите фактура: Чекор-по-чекор водич',
    publishDate: '3 февруари 2026',
    readTime: '6 мин читање',
    intro:
      'Креирањето правилна фактура е основа на секој бизнис. Во Македонија, фактурата мора да содржи одредени задолжителни елементи за да биде даночно признаена. Во овој водич ви покажуваме чекор по чекор како да направите фактура која е усогласена со Законот за ДДВ и која ќе ви обезбеди непречена наплата.',
    sections: [
      {
        title: 'Зошто е важна правилната фактура?',
        content:
          'Фактурата не е само документ за наплата — таа е и даночен документ. Неправилно издадена фактура може да доведе до одбивање на ДДВ одбитокот од страна на УЈП, казни при инспекција или проблеми со наплатата. Правилната фактура ја штити и вашата компанија и вашиот клиент, обезбедувајќи транспарентност и законска усогласеност.',
        items: null,
        steps: null,
      },
      {
        title: 'Чекор-по-чекор: Креирање фактура',
        content: null,
        items: null,
        steps: [
          {
            step: 'Внесете ги податоците за вашата компанија',
            desc: 'Наведете го целосниот назив на фирмата, адресата на седиштето, ЕДБ (единствен даночен број) и ЕМБС (единствен матичен број на субјект). Овие податоци мора да се на секоја фактура без исклучок.',
          },
          {
            step: 'Внесете ги податоците за купувачот',
            desc: 'Додајте го називот на компанијата-купувач, нејзината адреса, ЕДБ и ЕМБС. Ако купувачот е физичко лице, наведете го неговото име и адреса.',
          },
          {
            step: 'Поставете го бројот и датумот на фактурата',
            desc: 'Секоја фактура мора да има уникатен секвенцијален број. Датумот на издавање и датумот на промет (испорака) се задолжителни. Ако се различни, наведете ги двата.',
          },
          {
            step: 'Додајте ги ставките',
            desc: 'За секоја ставка наведете: опис на производот или услугата, количина, единечна цена без ДДВ, стапка на ДДВ (18%, 5% или 0%) и вкупен износ. Бидете прецизни во описите — нејасни описи може да предизвикаат проблеми при инспекција.',
          },
          {
            step: 'Пресметајте ДДВ и вкупен износ',
            desc: 'Прикажете го збирниот износ без ДДВ, износот на ДДВ по секоја стапка и вкупниот износ за плаќање. Ако имате ставки со различни ДДВ стапки, прикажете посебна рекапитулација.',
          },
          {
            step: 'Наведете услови за плаќање',
            desc: 'Додајте рок за плаќање (на пример 15 или 30 дена), банкарска сметка за уплата и евентуално повикување на број. Јасните услови за плаќање го забрзуваат процесот на наплата.',
          },
        ],
      },
      {
        title: 'Чести грешки при фактурирање',
        content: null,
        items: [
          'Погрешен или отсутен ЕДБ — без ЕДБ, фактурата не е даночно валидна',
          'Нејасен опис на ставките — „услуга" не е доволно, наведете конкретен опис',
          'Погрешна ДДВ стапка — проверете дали производот/услугата е со 18%, 5% или 0%',
          'Прескокнат секвенцијален број — фактурите мора да бидат нумерирани без прескокнување',
          'Отсутен датум на промет — датумот кога е извршена услугата/испораката е задолжителен',
          'Недоследна валута — износите мора да бидат во денари (МКД) или со јасно наведен курс',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го олеснува процесот',
        content:
          'Facturino автоматски ги пополнува задолжителните полиња, го генерира секвенцијалниот број, ја пресметува ДДВ рекапитулацијата и валидира дека сите податоци се комплетни пред испраќање. Можете да ја зачувате компанијата-купувач за повторна употреба, да креирате шаблони за повторливи фактури и да ги испраќате директно по е-пошта — сe од една платформа.',
        items: [
          'Автоматско пресметување на ДДВ по стапки (18%, 5%, 0%)',
          'Валидација на ЕДБ и ЕМБС пред испраќање',
          'Секвенцијално нумерирање без можност за прескокнување',
          'Генерирање на PDF и UBL XML формат истовремено',
          'Испраќање на фактури директно по е-пошта од платформата',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура во Македонија' },
      { slug: 'faktura-vs-proforma', title: 'Фактура vs профактура: Клучни разлики' },
      { slug: 'recurring-invoices-mk', title: 'Повторувачки фактури: Автоматизирајте ја наплатата' },
    ],
    cta: {
      title: 'Креирајте ја вашата прва фактура за 2 минути',
      desc: 'Без комплицирани поставки, без рачно пресметување. Facturino автоматски ги пополнува сите задолжителни полиња и генерира валидна фактура.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Guide',
    title: 'How to Create an Invoice: Step-by-Step Guide',
    publishDate: 'February 3, 2026',
    readTime: '6 min read',
    intro:
      'Creating a proper invoice is the foundation of every business. In Macedonia, an invoice must contain certain mandatory elements to be recognized for tax purposes. In this guide, we show you step by step how to create an invoice that complies with the VAT Law and ensures smooth payment collection.',
    sections: [
      {
        title: 'Why does a proper invoice matter?',
        content:
          'An invoice is not just a payment document — it is also a tax document. An improperly issued invoice can lead to rejection of the VAT deduction by the tax authority (UJP), penalties during inspection, or payment collection problems. A proper invoice protects both your company and your client, ensuring transparency and legal compliance.',
        items: null,
        steps: null,
      },
      {
        title: 'Step by step: Creating an invoice',
        content: null,
        items: null,
        steps: [
          {
            step: 'Enter your company details',
            desc: 'Include the full company name, registered address, EDB (unique tax number), and EMBS (unique entity registration number). These details must appear on every invoice without exception.',
          },
          {
            step: 'Enter the buyer details',
            desc: 'Add the buyer company name, their address, EDB, and EMBS. If the buyer is an individual, include their name and address.',
          },
          {
            step: 'Set the invoice number and date',
            desc: 'Every invoice must have a unique sequential number. The issue date and the supply date (date of delivery) are mandatory. If they differ, include both.',
          },
          {
            step: 'Add line items',
            desc: 'For each item, specify: a description of the product or service, quantity, unit price excluding VAT, VAT rate (18%, 5%, or 0%), and total amount. Be precise in descriptions — vague descriptions can cause problems during inspection.',
          },
          {
            step: 'Calculate VAT and total amount',
            desc: 'Show the subtotal excluding VAT, the VAT amount for each rate, and the grand total for payment. If you have items with different VAT rates, show a separate recapitulation.',
          },
          {
            step: 'Specify payment terms',
            desc: 'Add a payment deadline (for example 15 or 30 days), the bank account for payment, and optionally a reference number. Clear payment terms speed up the collection process.',
          },
        ],
      },
      {
        title: 'Common invoicing mistakes',
        content: null,
        items: [
          'Wrong or missing EDB — without an EDB, the invoice is not valid for tax purposes',
          'Vague item descriptions — "service" is not enough, provide a specific description',
          'Wrong VAT rate — verify whether the product/service falls under 18%, 5%, or 0%',
          'Skipped sequential number — invoices must be numbered without gaps',
          'Missing supply date — the date when the service was performed or goods delivered is mandatory',
          'Inconsistent currency — amounts must be in Macedonian denars (MKD) or with a clearly stated exchange rate',
        ],
        steps: null,
      },
      {
        title: 'How Facturino simplifies the process',
        content:
          'Facturino automatically fills in mandatory fields, generates sequential numbers, calculates the VAT recapitulation, and validates that all data is complete before sending. You can save buyer companies for reuse, create templates for recurring invoices, and send them directly by email — all from one platform.',
        items: [
          'Automatic VAT calculation by rate (18%, 5%, 0%)',
          'EDB and EMBS validation before sending',
          'Sequential numbering with no gaps possible',
          'Simultaneous PDF and UBL XML format generation',
          'Send invoices directly by email from the platform',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements in Macedonia' },
      { slug: 'faktura-vs-proforma', title: 'Invoice vs Proforma: Key Differences' },
      { slug: 'recurring-invoices-mk', title: 'Recurring Invoices: Automate Your Billing' },
    ],
    cta: {
      title: 'Create your first invoice in 2 minutes',
      desc: 'No complicated setup, no manual calculations. Facturino automatically fills in all mandatory fields and generates a valid invoice.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Si të krijoni një faturë: Udhëzues hap pas hapi',
    publishDate: '3 shkurt 2026',
    readTime: '6 min lexim',
    intro:
      'Krijimi i një fature të saktë është baza e çdo biznesi. Në Maqedoni, fatura duhet të përmbajë elemente të caktuara të detyrueshme për të qenë e njohur për qëllime tatimore. Në këtë udhëzues ju tregojmë hap pas hapi si të krijoni një faturë që përputhet me Ligjin për TVSH-në dhe siguron arkëtim pa probleme.',
    sections: [
      {
        title: 'Pse ka rëndësi fatura e saktë?',
        content:
          'Fatura nuk është vetëm dokument pagese — ajo është edhe dokument tatimor. Një faturë e lëshuar gabimisht mund të çojë në refuzimin e zbritjes së TVSH-së nga autoriteti tatimor (UJP), gjoba gjatë inspektimit ose probleme me arkëtimin. Fatura e saktë mbron edhe kompaninë tuaj edhe klientin, duke siguruar transparencë dhe pajtueshmëri ligjore.',
        items: null,
        steps: null,
      },
      {
        title: 'Hap pas hapi: Krijimi i faturës',
        content: null,
        items: null,
        steps: [
          {
            step: 'Futni të dhënat e kompanisë suaj',
            desc: 'Përfshini emrin e plotë të kompanisë, adresën e regjistruar, EDB (numri unik tatimor) dhe EMBS (numri unik i regjistrimit të subjektit). Këto të dhëna duhet të shfaqen në çdo faturë pa përjashtim.',
          },
          {
            step: 'Futni të dhënat e blerësit',
            desc: 'Shtoni emrin e kompanisë blerëse, adresën, EDB dhe EMBS. Nëse blerësi është person fizik, përfshini emrin dhe adresën e tij.',
          },
          {
            step: 'Vendosni numrin dhe datën e faturës',
            desc: 'Çdo faturë duhet të ketë numër unik sekuencial. Data e lëshimit dhe data e furnizimit (data e dorëzimit) janë të detyrueshme. Nëse ndryshojnë, përfshini të dyja.',
          },
          {
            step: 'Shtoni zërat',
            desc: 'Për çdo zë, specifikoni: përshkrimin e produktit ose shërbimit, sasinë, çmimin për njësi pa TVSH, normën e TVSH-së (18%, 5% ose 0%) dhe shumën totale. Jini të saktë në përshkrime — përshkrime të paqarta mund të shkaktojnë probleme gjatë inspektimit.',
          },
          {
            step: 'Llogaritni TVSH-në dhe shumën totale',
            desc: 'Tregoni nëntotalin pa TVSH, shumën e TVSH-së për çdo normë dhe totalin e përgjithshëm. Nëse keni zëra me norma të ndryshme TVSH-je, tregoni rekapitulim të veçantë.',
          },
          {
            step: 'Specifikoni kushtet e pagesës',
            desc: 'Shtoni afatin e pagesës (p.sh. 15 ose 30 ditë), llogarinë bankare dhe eventualisht numrin e referencës. Kushtet e qarta shpejtojnë procesin e arkëtimit.',
          },
        ],
      },
      {
        title: 'Gabime të zakonshme në faturim',
        content: null,
        items: [
          'EDB e gabuar ose mungon — pa EDB, fatura nuk është e vlefshme tatimore',
          'Përshkrime të paqarta — "shërbim" nuk mjafton, jepni përshkrim specifik',
          'Normë e gabuar TVSH-je — verifikoni nëse produkti/shërbimi ka 18%, 5% ose 0%',
          'Numër sekuencial i kapërcyer — faturat duhet numërohen pa boshllëqe',
          'Datë furnizimi mungon — data kur u krye shërbimi ose u dorëzua malli është e detyrueshme',
          'Monedhë e paqëndrueshme — shumat duhet të jenë në denarë (MKD) ose me kurs të qartë',
        ],
        steps: null,
      },
      {
        title: 'Si e thjeshton Facturino procesin',
        content:
          'Facturino plotëson automatikisht fushat e detyrueshme, gjeneron numra sekuencialë, llogarit rekapitulimin e TVSH-së dhe validon që të gjitha të dhënat janë të plota para dërgimit. Mund të ruani kompani blerëse për ripërdorim, të krijoni shabllone për fatura të përsëritura dhe t\'i dërgoni direkt me email — të gjitha nga një platformë.',
        items: [
          'Llogaritje automatike e TVSH-së sipas normës (18%, 5%, 0%)',
          'Validim i EDB dhe EMBS para dërgimit',
          'Numërim sekuencial pa mundësi kapërcimi',
          'Gjenerim i njëkohshëm i PDF dhe UBL XML',
          'Dërgim i faturave direkt me email nga platforma',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme të faturës në Maqedoni' },
      { slug: 'faktura-vs-proforma', title: 'Fatura vs profatura: Dallimet kryesore' },
      { slug: 'recurring-invoices-mk', title: 'Faturat e përsëritura: Automatizoni arkëtimin' },
    ],
    cta: {
      title: 'Krijoni faturën tuaj të parë në 2 minuta',
      desc: 'Pa konfigurime të ndërlikuara, pa llogaritje manuale. Facturino plotëson automatikisht fushat e detyrueshme dhe gjeneron faturë valide.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Rehber',
    title: 'Fatura nasıl oluşturulur: Adım adım rehber',
    publishDate: '3 Şubat 2026',
    readTime: '6 dk okuma',
    intro:
      'Doğru bir fatura oluşturmak her işletmenin temelidir. Makedonya\'da bir faturanın vergi açısından geçerli olabilmesi için belirli zorunlu unsurları içermesi gerekir. Bu rehberde KDV Yasasına uygun ve sorunsuz tahsilat sağlayan bir faturayı adım adım nasıl oluşturacağınızı gösteriyoruz.',
    sections: [
      {
        title: 'Doğru fatura neden önemlidir?',
        content:
          'Fatura yalnızca bir ödeme belgesi değildir — aynı zamanda bir vergi belgesidir. Hatalı düzenlenmiş bir fatura, vergi dairesi (UJP) tarafından KDV indiriminin reddine, denetim sırasında cezalara veya tahsilat sorunlarına yol açabilir. Doğru fatura hem şirketinizi hem müşterinizi koruyarak şeffaflık ve yasal uyumluluk sağlar.',
        items: null,
        steps: null,
      },
      {
        title: 'Adım adım: Fatura oluşturma',
        content: null,
        items: null,
        steps: [
          {
            step: 'Şirket bilgilerinizi girin',
            desc: 'Tam şirket adı, kayıtlı adres, EDB (benzersiz vergi numarası) ve EMBS (benzersiz kuruluş sicil numarası) dahil edin. Bu bilgiler istisnasız her faturada bulunmalıdır.',
          },
          {
            step: 'Alıcı bilgilerini girin',
            desc: 'Alıcı şirketin adını, adresini, EDB ve EMBS bilgilerini ekleyin. Alıcı bir bireyse adını ve adresini belirtin.',
          },
          {
            step: 'Fatura numarası ve tarihini ayarlayın',
            desc: 'Her faturanın benzersiz bir sıralı numarası olmalıdır. Düzenleme tarihi ve teslim tarihi (teslimat tarihi) zorunludur. Farklıysa her ikisini de belirtin.',
          },
          {
            step: 'Kalemleri ekleyin',
            desc: 'Her kalem için belirtin: ürün veya hizmet açıklaması, miktar, KDV hariç birim fiyat, KDV oranı (%18, %5 veya %0) ve toplam tutar. Açıklamalarda kesin olun — belirsiz açıklamalar denetim sırasında sorunlara yol açabilir.',
          },
          {
            step: 'KDV ve toplam tutarı hesaplayın',
            desc: 'KDV hariç ara toplamı, her oran için KDV tutarını ve ödeme için genel toplamı gösterin. Farklı KDV oranlı kalemleriniz varsa ayrı bir rekapitülasyon gösterin.',
          },
          {
            step: 'Ödeme koşullarını belirtin',
            desc: 'Ödeme vadesini (örneğin 15 veya 30 gün), ödeme için banka hesabını ve isteğe bağlı olarak referans numarasını ekleyin. Net ödeme koşulları tahsilat sürecini hızlandırır.',
          },
        ],
      },
      {
        title: 'Yaygın faturalama hataları',
        content: null,
        items: [
          'Yanlış veya eksik EDB — EDB olmadan fatura vergi açısından geçerli değildir',
          'Belirsiz kalem açıklamaları — "hizmet" yeterli değildir, spesifik açıklama yapın',
          'Yanlış KDV oranı — ürün/hizmetin %18, %5 veya %0 kapsamında olduğunu doğrulayın',
          'Atlanan sıralı numara — faturalar boşluk olmadan numaralandırılmalıdır',
          'Eksik teslim tarihi — hizmetin yapıldığı veya malların teslim edildiği tarih zorunludur',
          'Tutarsız para birimi — tutarlar Makedon dinarı (MKD) cinsinden veya açıkça belirtilmiş döviz kuruyla olmalıdır',
        ],
        steps: null,
      },
      {
        title: 'Facturino süreci nasıl kolaylaştırır',
        content:
          'Facturino zorunlu alanları otomatik doldurur, sıralı numaralar oluşturur, KDV rekapitülasyonunu hesaplar ve göndermeden önce tüm verilerin eksiksiz olduğunu doğrular. Alıcı şirketleri yeniden kullanım için kaydedebilir, tekrarlayan faturalar için şablonlar oluşturabilir ve doğrudan e-posta ile gönderebilirsiniz — hepsi tek platformdan.',
        items: [
          'Orana göre otomatik KDV hesaplaması (%18, %5, %0)',
          'Göndermeden önce EDB ve EMBS doğrulaması',
          'Boşluk bırakmayan sıralı numaralama',
          'Eş zamanlı PDF ve UBL XML format oluşturma',
          'Platformdan doğrudan e-posta ile fatura gönderme',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'zadolzitelni-elementi-faktura', title: "Makedonya'da faturanın zorunlu unsurları" },
      { slug: 'faktura-vs-proforma', title: 'Fatura vs proforma: Temel farklar' },
      { slug: 'recurring-invoices-mk', title: 'Tekrarlayan faturalar: Tahsilatı otomatikleştirin' },
    ],
    cta: {
      title: 'İlk faturanızı 2 dakikada oluşturun',
      desc: 'Karmaşık kurulum yok, manuel hesaplama yok. Facturino tüm zorunlu alanları otomatik doldurur ve geçerli bir fatura oluşturur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function KakoDaNapraviteFakturaPage({
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

                {/* Numbered steps */}
                {section.steps && (
                  <ol className="space-y-6 mt-4">
                    {section.steps.map((s, j) => (
                      <li key={j} className="flex items-start gap-4">
                        <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold flex items-center justify-center mt-0.5">
                          {j + 1}
                        </span>
                        <div>
                          <h3 className="font-semibold text-gray-900 text-lg">
                            {s.step}
                          </h3>
                          <p className="text-gray-600 leading-relaxed mt-1">
                            {s.desc}
                          </p>
                        </div>
                      </li>
                    ))}
                  </ol>
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
