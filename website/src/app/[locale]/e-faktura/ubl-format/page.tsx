import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/ubl-format', {
    title: {
      mk: 'UBL 2.1 формат за е-фактура: технички водич',
      en: 'UBL 2.1 Format for E-Invoices: Technical Guide',
      sq: 'Formati UBL 2.1 për e-faturë: udhëzues teknik',
      tr: 'E-Fatura İçin UBL 2.1 Formatı: Teknik Rehber',
    },
    description: {
      mk: 'Технички водич за UBL 2.1 форматот на е-фактура во Македонија: структура на XML, задолжителни полиња по Чл. 53 ЗДДВ, валидација, чести грешки и како Facturino генерира валиден UBL 2.1.',
      en: 'Technical guide to the UBL 2.1 e-invoice format in North Macedonia: XML structure, mandatory fields per Art. 53 VAT Law, validation, common errors, and how Facturino generates valid UBL 2.1.',
      sq: 'Udhëzues teknik për formatin UBL 2.1 të e-faturës në Maqedoni: struktura e XML, fushat e detyrueshme sipas Nenit 53 të TVSH-së, validimi, gabimet e shpeshta dhe si Facturino gjeneron UBL 2.1 valid.',
      tr: 'Kuzey Makedonya\'da UBL 2.1 e-fatura formatı teknik rehberi: XML yapısı, KDV Kanunu Madde 53\'e göre zorunlu alanlar, doğrulama, yaygın hatalar ve Facturino\'nun geçerli UBL 2.1 üretimi.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'Технички водич',
    title: 'UBL 2.1 формат за е-фактура: технички водич',
    publishDate: '18 август 2026',
    readTime: '8 мин читање',
    intro:
      'Е-фактурата во Македонија не е PDF ниту скенирана слика — таа е структуиран XML документ во UBL 2.1 формат, машински читлив и потпишан со квалификуван електронски потпис (QES). Овој технички водич објаснува што е UBL 2.1, зошто Македонија го усвои, како е структуирана една UBL е-фактура, кои полиња се задолжителни по Чл. 53 ЗДДВ, како се валидира и кои се најчестите грешки — и како Facturino генерира валиден UBL 2.1 без рачно пишување XML.',
    sections: [
      {
        title: 'Што е UBL 2.1?',
        content:
          'UBL (Universal Business Language) е меѓународен отворен стандард за електронски деловни документи базиран на XML, стандардизиран од OASIS. Верзијата 2.1 дефинира структуиран, машински читлив формат за фактури, нарачки, испратници и други деловни документи. За разлика од PDF или скенирана слика — кои се читливи само за човек — UBL 2.1 XML содржи податоци кои софтверот на купувачот, продавачот и даночниот орган можат автоматски да ги прочитаат, валидираат и обработат. Клучно: PDF или скен НЕ е валидна е-фактура. Само UBL 2.1 XML документ, потпишан со QES, е правно валидна е-фактура.',
        items: null,
        steps: null,
      },
      {
        title: 'Зошто Македонија го усвои UBL 2.1',
        content:
          'Македонија го избра UBL 2.1 како основен формат за националниот систем за е-фактурирање преку УЈП од неколку причини:',
        items: [
          'Меѓународна интероперабилност — UBL 2.1 е основа на европскиот стандард EN 16931, што ги усогласува МК фактурите со ЕУ практиките',
          'Отворен и бесплатен стандард — нема лиценцни трошоци, поддржан од сите сериозни ERP и сметководствени системи',
          'Машинска обработка — даночниот орган може автоматски да ги вкрстува податоците за ДДВ, што ја намалува измамата',
          'Долгорочна архивска стабилност — XML е текстуален формат читлив и по 10 години, за разлика од сопственички бинарни формати',
          'Единствена структура за сите обврзници — истиот формат важи за B2G, B2B и доброволна употреба',
        ],
        steps: null,
      },
      {
        title: 'Структура на UBL е-фактура',
        content:
          'Една UBL 2.1 е-фактура е XML документ составен од јасно дефинирани делови. Секој дел носи специфичен сегмент од податоци:',
        items: [
          'Заглавие на документот (document header) — тип на документ, број на фактура, датум на издавање, валута и уникатен идентификатор (UUID)',
          'Податоци за продавачот (seller party) — назив, адреса, ЕДБ (даночен број), ЕМБС и банкарски податоци на издавачот',
          'Податоци за купувачот (buyer party) — назив, адреса и ЕДБ на примачот; ЕДБ-то на купувачот е задолжително во XML-от',
          'Ставки на фактурата (invoice lines) — секоја стока или услуга поединечно: опис, количина, единечна цена, единица мерка и применета ДДВ стапка',
          'Даночни вкупни износи (tax totals) — сумирано ДДВ по секоја стапка (18%, 5%, 10%, ослободено), со основица и износ на данок',
          'Монетарни вкупни износи (monetary totals) — вкупно без ДДВ, вкупно ДДВ и вкупно за наплата',
        ],
        steps: null,
      },
      {
        title: 'Задолжителни XML полиња',
        content:
          'Согласно Чл. 53 од ЗДДВ, секоја е-фактура мора да ги содржи следните задолжителни податоци во UBL XML-от. Недостатокот на кое било од нив прави фактурата невалидна:',
        items: [
          'ЕДБ (даночен број) на продавачот — идентификува издавачот пред даночниот орган',
          'ЕДБ на купувачот — задолжително мора да биде вклучено во XML-от',
          'Датум на издавање на фактурата',
          'Ставки со количина и единечна цена за секоја стока или услуга',
          'ДДВ прикажан по стапка (18%, 5%, 10% или ослободено), со основица и износ',
          'Уникатен идентификатор (UUID) — единствен за секоја фактура, спречува дупликати',
        ],
        steps: null,
      },
      {
        title: 'Валидација и чести грешки',
        content:
          'Пред испраќање, УЈП платформата ја валидира секоја е-фактура спрема UBL 2.1 шемата и деловните правила. Овие се најчестите причини за одбивање:',
        items: null,
        steps: [
          { step: 'Грешка во шемата (schema validation)', desc: 'XML-от не одговара на UBL 2.1 XSD шемата — на пример недостасува задолжителен елемент или редоследот на елементите е погрешен. Валиден генератор го спречува ова автоматски.' },
          { step: 'Погрешно заокружување на ДДВ', desc: 'Збирот на ДДВ по ставки не се совпаѓа со декларираниот вкупен ДДВ поради погрешно заокружување. ДДВ мора да се пресметува и заокружува по стапка, а не на крајот.' },
          { step: 'Недостасува ЕДБ на купувачот', desc: 'Најчеста грешка — фактурата е генерирана без ЕДБ на примачот. За B2G и B2B фактури ова е задолжително поле и води до одбивање.' },
          { step: 'Невалиден UUID', desc: 'Идентификаторот е дупликат од претходна фактура или не е во валиден UUID формат. Секоја фактура мора да носи свој единствен UUID.' },
        ],
      },
      {
        title: 'Како Facturino генерира валиден UBL 2.1',
        content:
          'Со Facturino не пишувате XML рачно и не се грижите за шема или заокружување. Кога креирате фактура во нормалниот интерфејс, Facturino автоматски генерира UBL 2.1 XML усогласен со стандардот и барањата на УЈП: ги мапира сите задолжителни полиња по Чл. 53 ЗДДВ, пресметува и заокружува ДДВ по стапка, доделува уникатен UUID и го потпишува документот со вашиот QES. Резултатот е валидна е-фактура спремна за поднесување — без рачно пишување XML, без грешки во шемата и без ризик од одбивање. Запомнете: PDF или скен НЕ е е-фактура — само потпишан UBL 2.1 XML е правно валиден, а Facturino го генерира нативно.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'qes-potpis', title: 'QES потпис за е-фактура' },
      { slug: 'casuvanje-i-arhiva', title: 'Чување и е-архива на фактури' },
    ],
    bottomCta: {
      title: 'Валиден UBL 2.1 без рачно пишување XML',
      subtitle: 'Facturino генерира усогласен UBL 2.1 и го потпишува со QES — автоматски.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'Technical',
    title: 'UBL 2.1 Format for E-Invoices: Technical Guide',
    publishDate: 'August 18, 2026',
    readTime: '8 min read',
    intro:
      'An e-invoice in North Macedonia is not a PDF or a scanned image — it is a structured XML document in UBL 2.1 format, machine-readable and signed with a Qualified Electronic Signature (QES). This technical guide explains what UBL 2.1 is, why North Macedonia adopted it, how a UBL e-invoice is structured, which fields are mandatory under Art. 53 of the VAT Law, how validation works, the most common errors — and how Facturino generates valid UBL 2.1 without writing any XML by hand.',
    sections: [
      {
        title: 'What is UBL 2.1?',
        content:
          'UBL (Universal Business Language) is an international open standard for electronic business documents based on XML, standardized by OASIS. Version 2.1 defines a structured, machine-readable format for invoices, orders, dispatch advices, and other business documents. Unlike a PDF or scanned image — which are only human-readable — a UBL 2.1 XML contains data that the buyer\'s software, the seller\'s software, and the tax authority can automatically read, validate, and process. Crucially: a PDF or scan is NOT a valid e-invoice. Only a UBL 2.1 XML document, signed with QES, is a legally valid e-invoice.',
        items: null,
        steps: null,
      },
      {
        title: 'Why North Macedonia adopted UBL 2.1',
        content:
          'North Macedonia chose UBL 2.1 as the core format for its national e-invoicing system through UJP for several reasons:',
        items: [
          'International interoperability — UBL 2.1 underpins the European standard EN 16931, aligning MK invoices with EU practice',
          'Open and free standard — no licensing costs, supported by every serious ERP and accounting system',
          'Machine processing — the tax authority can automatically cross-check VAT data, reducing fraud',
          'Long-term archival stability — XML is a text format readable even after 10 years, unlike proprietary binary formats',
          'A single structure for all taxpayers — the same format applies to B2G, B2B, and voluntary use',
        ],
        steps: null,
      },
      {
        title: 'Structure of a UBL e-invoice',
        content:
          'A UBL 2.1 e-invoice is an XML document made up of clearly defined parts. Each part carries a specific segment of data:',
        items: [
          'Document header — document type, invoice number, issue date, currency, and unique identifier (UUID)',
          'Seller party — name, address, EDB (tax number), EMBS, and bank details of the issuer',
          'Buyer party — name, address, and EDB of the recipient; the buyer\'s EDB is mandatory in the XML',
          'Invoice lines — each good or service individually: description, quantity, unit price, unit of measure, and applied VAT rate',
          'Tax totals — VAT summed per rate (18%, 5%, 10%, exempt), with taxable base and tax amount',
          'Monetary totals — total excluding VAT, total VAT, and total amount payable',
        ],
        steps: null,
      },
      {
        title: 'Mandatory XML fields',
        content:
          'Under Art. 53 of the VAT Law, every e-invoice must contain the following mandatory data in the UBL XML. Missing any one of them makes the invoice invalid:',
        items: [
          'Seller\'s EDB (tax number) — identifies the issuer to the tax authority',
          'Buyer\'s EDB — must be included in the XML',
          'Invoice issue date',
          'Line items with quantity and unit price for each good or service',
          'VAT shown per rate (18%, 5%, 10%, or exempt), with taxable base and amount',
          'Unique identifier (UUID) — unique to each invoice, prevents duplicates',
        ],
        steps: null,
      },
      {
        title: 'Validation and common errors',
        content:
          'Before submission, the UJP platform validates each e-invoice against the UBL 2.1 schema and business rules. These are the most common reasons for rejection:',
        items: null,
        steps: [
          { step: 'Schema validation error', desc: 'The XML does not match the UBL 2.1 XSD schema — for example a mandatory element is missing or the element order is wrong. A valid generator prevents this automatically.' },
          { step: 'Wrong VAT rounding', desc: 'The sum of VAT per line does not match the declared total VAT because of incorrect rounding. VAT must be calculated and rounded per rate, not at the end.' },
          { step: 'Missing buyer EDB', desc: 'The most common error — the invoice is generated without the recipient\'s EDB. For B2G and B2B invoices this is a mandatory field and causes rejection.' },
          { step: 'Invalid UUID', desc: 'The identifier is a duplicate of a previous invoice or is not in valid UUID format. Every invoice must carry its own unique UUID.' },
        ],
      },
      {
        title: 'How Facturino generates valid UBL 2.1',
        content:
          'With Facturino you don\'t write XML by hand and you don\'t worry about schema or rounding. When you create an invoice in the normal interface, Facturino automatically generates UBL 2.1 XML compliant with the standard and UJP requirements: it maps all mandatory fields per Art. 53 VAT Law, calculates and rounds VAT per rate, assigns a unique UUID, and signs the document with your QES. The result is a valid e-invoice ready for submission — no manual XML, no schema errors, and no risk of rejection. Remember: a PDF or scan is NOT an e-invoice — only a signed UBL 2.1 XML is legally valid, and Facturino generates it natively.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoicing' },
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'qes-potpis', title: 'QES Signature for E-Invoices' },
      { slug: 'casuvanje-i-arhiva', title: 'Storage and E-Archiving of Invoices' },
    ],
    bottomCta: {
      title: 'Valid UBL 2.1 without writing XML',
      subtitle: 'Facturino generates compliant UBL 2.1 and signs it with QES — automatically.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Teknik',
    title: 'Formati UBL 2.1 për e-faturë: udhëzues teknik',
    publishDate: '18 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Një e-faturë në Maqedoni nuk është PDF apo imazh i skanuar — është një dokument XML i strukturuar në formatin UBL 2.1, i lexueshëm nga makina dhe i nënshkruar me Nënshkrim Elektronik të Kualifikuar (QES). Ky udhëzues teknik shpjegon çfarë është UBL 2.1, pse e adoptoi Maqedonia, si strukturohet një e-faturë UBL, cilat fusha janë të detyrueshme sipas Nenit 53 të Ligjit të TVSH-së, si funksionon validimi, gabimet më të shpeshta — dhe si Facturino gjeneron UBL 2.1 valid pa shkruar asnjë XML me dorë.',
    sections: [
      {
        title: 'Çfarë është UBL 2.1?',
        content:
          'UBL (Universal Business Language) është një standard ndërkombëtar i hapur për dokumente biznesi elektronike i bazuar në XML, i standardizuar nga OASIS. Versioni 2.1 përcakton një format të strukturuar, të lexueshëm nga makina, për fatura, porosi, fletëdërgesa dhe dokumente të tjera biznesi. Ndryshe nga PDF ose imazhi i skanuar — që lexohen vetëm nga njeriu — një XML UBL 2.1 përmban të dhëna që softueri i blerësit, i shitësit dhe autoriteti tatimor mund t\'i lexojnë, validojnë dhe përpunojnë automatikisht. Thelbësore: një PDF ose skanim NUK është e-faturë e vlefshme. Vetëm një dokument XML UBL 2.1, i nënshkruar me QES, është e-faturë ligjërisht e vlefshme.',
        items: null,
        steps: null,
      },
      {
        title: 'Pse Maqedonia e adoptoi UBL 2.1',
        content:
          'Maqedonia zgjodhi UBL 2.1 si formatin bazë për sistemin kombëtar të e-faturimit përmes DAP për disa arsye:',
        items: [
          'Ndërveprueshmëri ndërkombëtare — UBL 2.1 është baza e standardit evropian EN 16931, duke i harmonizuar faturat MK me praktikat e BE-së',
          'Standard i hapur dhe falas — pa kosto licence, i mbështetur nga çdo sistem serioz ERP dhe kontabiliteti',
          'Përpunim me makinë — autoriteti tatimor mund të kryqëzojë automatikisht të dhënat e TVSH-së, duke ulur mashtrimin',
          'Stabilitet arkivor afatgjatë — XML është format tekstual i lexueshëm edhe pas 10 vjetësh, ndryshe nga formatet binare pronësore',
          'Një strukturë e vetme për të gjithë tatimpaguesit — i njëjti format vlen për B2G, B2B dhe përdorim vullnetar',
        ],
        steps: null,
      },
      {
        title: 'Struktura e një e-fature UBL',
        content:
          'Një e-faturë UBL 2.1 është një dokument XML i përbërë nga pjesë të përcaktuara qartë. Çdo pjesë mban një segment specifik të të dhënave:',
        items: [
          'Kreu i dokumentit — lloji i dokumentit, numri i faturës, data e lëshimit, valuta dhe identifikuesi unik (UUID)',
          'Pala shitëse — emri, adresa, EDB (numri tatimor), EMBS dhe të dhënat bankare të lëshuesit',
          'Pala blerëse — emri, adresa dhe EDB i marrësit; EDB i blerësit është i detyrueshëm në XML',
          'Rreshtat e faturës — çdo mall ose shërbim veç e veç: përshkrimi, sasia, çmimi njësi, njësia matëse dhe norma e aplikuar e TVSH-së',
          'Totalet tatimore — TVSH e mbledhur sipas normës (18%, 5%, 10%, e përjashtuar), me bazën tatimore dhe shumën e tatimit',
          'Totalet monetare — totali pa TVSH, totali i TVSH-së dhe shuma totale për pagesë',
        ],
        steps: null,
      },
      {
        title: 'Fushat e detyrueshme XML',
        content:
          'Sipas Nenit 53 të Ligjit të TVSH-së, çdo e-faturë duhet të përmbajë të dhënat e mëposhtme të detyrueshme në XML UBL. Mungesa e ndonjërës prej tyre e bën faturën jo të vlefshme:',
        items: [
          'EDB (numri tatimor) i shitësit — identifikon lëshuesin para autoritetit tatimor',
          'EDB i blerësit — duhet të përfshihet në XML',
          'Data e lëshimit të faturës',
          'Rreshtat me sasi dhe çmim njësi për çdo mall ose shërbim',
          'TVSH e paraqitur sipas normës (18%, 5%, 10% ose e përjashtuar), me bazë dhe shumë',
          'Identifikues unik (UUID) — unik për çdo faturë, parandalon dublikatat',
        ],
        steps: null,
      },
      {
        title: 'Validimi dhe gabimet e shpeshta',
        content:
          'Para dorëzimit, platforma DAP validon çdo e-faturë kundrejt skemës UBL 2.1 dhe rregullave të biznesit. Këto janë arsyet më të shpeshta të refuzimit:',
        items: null,
        steps: [
          { step: 'Gabim në skemë (schema validation)', desc: 'XML nuk përputhet me skemën XSD të UBL 2.1 — për shembull mungon një element i detyrueshëm ose renditja e elementeve është e gabuar. Një gjenerator valid e parandalon këtë automatikisht.' },
          { step: 'Rrumbullakim i gabuar i TVSH-së', desc: 'Shuma e TVSH-së për rresht nuk përputhet me TVSH-në totale të deklaruar për shkak të rrumbullakimit të gabuar. TVSH duhet të llogaritet dhe rrumbullakohet sipas normës, jo në fund.' },
          { step: 'Mungon EDB i blerësit', desc: 'Gabimi më i shpeshtë — fatura gjenerohet pa EDB të marrësit. Për faturat B2G dhe B2B kjo është fushë e detyrueshme dhe çon në refuzim.' },
          { step: 'UUID jo i vlefshëm', desc: 'Identifikuesi është dublikat i një fature të mëparshme ose nuk është në format valid UUID. Çdo faturë duhet të mbajë UUID-in e vet unik.' },
        ],
      },
      {
        title: 'Si Facturino gjeneron UBL 2.1 valid',
        content:
          'Me Facturino nuk shkruani XML me dorë dhe nuk shqetësoheni për skemë ose rrumbullakim. Kur krijoni një faturë në ndërfaqen normale, Facturino gjeneron automatikisht XML UBL 2.1 në përputhje me standardin dhe kërkesat e DAP: mapon të gjitha fushat e detyrueshme sipas Nenit 53 të TVSH-së, llogarit dhe rrumbullakon TVSH-në sipas normës, cakton një UUID unik dhe e nënshkruan dokumentin me QES tuaj. Rezultati është një e-faturë e vlefshme gati për dorëzim — pa XML manual, pa gabime skeme dhe pa rrezik refuzimi. Mbani mend: një PDF ose skanim NUK është e-faturë — vetëm një XML UBL 2.1 i nënshkruar është ligjërisht i vlefshëm, dhe Facturino e gjeneron atë vendas.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'qes-potpis', title: 'Nënshkrimi QES për e-faturë' },
      { slug: 'casuvanje-i-arhiva', title: 'Ruajtja dhe e-arkivimi i faturave' },
    ],
    bottomCta: {
      title: 'UBL 2.1 valid pa shkruar XML',
      subtitle: 'Facturino gjeneron UBL 2.1 në përputhje dhe e nënshkruan me QES — automatikisht.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'Teknik',
    title: 'E-Fatura İçin UBL 2.1 Formatı: Teknik Rehber',
    publishDate: '18 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Kuzey Makedonya\'da bir e-fatura PDF veya taranmış görüntü değildir — UBL 2.1 formatında yapılandırılmış bir XML belgesidir, makine tarafından okunabilir ve Nitelikli Elektronik İmza (QES) ile imzalanmıştır. Bu teknik rehber, UBL 2.1\'in ne olduğunu, Kuzey Makedonya\'nın neden benimsediğini, bir UBL e-faturanın nasıl yapılandırıldığını, KDV Kanunu Madde 53\'e göre hangi alanların zorunlu olduğunu, doğrulamanın nasıl çalıştığını, en yaygın hataları — ve Facturino\'nun elle XML yazmadan geçerli UBL 2.1\'i nasıl ürettiğini açıklıyor.',
    sections: [
      {
        title: 'UBL 2.1 nedir?',
        content:
          'UBL (Universal Business Language), OASIS tarafından standartlaştırılan, XML tabanlı elektronik iş belgeleri için uluslararası açık bir standarttır. Sürüm 2.1, faturalar, siparişler, sevk irsaliyeleri ve diğer iş belgeleri için yapılandırılmış, makine tarafından okunabilir bir format tanımlar. Yalnızca insan tarafından okunabilen PDF veya taranmış görüntünün aksine, bir UBL 2.1 XML\'i alıcının yazılımının, satıcının yazılımının ve vergi dairesinin otomatik olarak okuyabileceği, doğrulayabileceği ve işleyebileceği veriler içerir. Önemli: bir PDF veya tarama geçerli bir e-fatura DEĞİLDİR. Yalnızca QES ile imzalanmış bir UBL 2.1 XML belgesi yasal olarak geçerli bir e-faturadır.',
        items: null,
        steps: null,
      },
      {
        title: 'Kuzey Makedonya neden UBL 2.1\'i benimsedi',
        content:
          'Kuzey Makedonya, UJP üzerinden ulusal e-fatura sistemi için temel format olarak UBL 2.1\'i birkaç nedenle seçti:',
        items: [
          'Uluslararası birlikte çalışabilirlik — UBL 2.1, Avrupa standardı EN 16931\'in temelidir ve MK faturalarını AB uygulamalarıyla uyumlu hale getirir',
          'Açık ve ücretsiz standart — lisans maliyeti yok, her ciddi ERP ve muhasebe sistemi tarafından desteklenir',
          'Makine işleme — vergi dairesi KDV verilerini otomatik olarak çapraz kontrol edebilir, dolandırıcılığı azaltır',
          'Uzun vadeli arşiv istikrarı — XML, tescilli ikili formatların aksine 10 yıl sonra bile okunabilen bir metin formatıdır',
          'Tüm mükellefler için tek bir yapı — aynı format B2G, B2B ve gönüllü kullanım için geçerlidir',
        ],
        steps: null,
      },
      {
        title: 'Bir UBL e-faturanın yapısı',
        content:
          'Bir UBL 2.1 e-fatura, açıkça tanımlanmış bölümlerden oluşan bir XML belgesidir. Her bölüm belirli bir veri segmentini taşır:',
        items: [
          'Belge başlığı — belge türü, fatura numarası, düzenlenme tarihi, para birimi ve benzersiz tanımlayıcı (UUID)',
          'Satıcı tarafı — düzenleyenin adı, adresi, EDB (vergi numarası), EMBS ve banka bilgileri',
          'Alıcı tarafı — alıcının adı, adresi ve EDB\'si; alıcının EDB\'si XML\'de zorunludur',
          'Fatura satırları — her mal veya hizmet ayrı ayrı: açıklama, miktar, birim fiyat, ölçü birimi ve uygulanan KDV oranı',
          'Vergi toplamları — orana göre toplanan KDV (%18, %5, %10, muaf), vergi matrahı ve vergi tutarı ile',
          'Parasal toplamlar — KDV hariç toplam, toplam KDV ve ödenecek toplam tutar',
        ],
        steps: null,
      },
      {
        title: 'Zorunlu XML alanları',
        content:
          'KDV Kanunu Madde 53\'e göre, her e-fatura UBL XML\'de aşağıdaki zorunlu verileri içermelidir. Bunlardan herhangi birinin eksik olması faturayı geçersiz kılar:',
        items: [
          'Satıcının EDB\'si (vergi numarası) — düzenleyeni vergi dairesine tanımlar',
          'Alıcının EDB\'si — XML\'e dahil edilmelidir',
          'Fatura düzenlenme tarihi',
          'Her mal veya hizmet için miktar ve birim fiyat içeren satırlar',
          'Orana göre gösterilen KDV (%18, %5, %10 veya muaf), matrah ve tutar ile',
          'Benzersiz tanımlayıcı (UUID) — her fatura için benzersiz, tekrarları önler',
        ],
        steps: null,
      },
      {
        title: 'Doğrulama ve yaygın hatalar',
        content:
          'Gönderimden önce UJP platformu her e-faturayı UBL 2.1 şemasına ve iş kurallarına göre doğrular. Bunlar reddedilmenin en yaygın nedenleridir:',
        items: null,
        steps: [
          { step: 'Şema doğrulama hatası (schema validation)', desc: 'XML, UBL 2.1 XSD şemasıyla eşleşmiyor — örneğin zorunlu bir öğe eksik veya öğe sırası yanlış. Geçerli bir üretici bunu otomatik olarak önler.' },
          { step: 'Yanlış KDV yuvarlaması', desc: 'Satır başına KDV toplamı, yanlış yuvarlama nedeniyle beyan edilen toplam KDV ile eşleşmiyor. KDV, sonda değil, orana göre hesaplanmalı ve yuvarlanmalıdır.' },
          { step: 'Alıcı EDB\'si eksik', desc: 'En yaygın hata — fatura alıcının EDB\'si olmadan üretilir. B2G ve B2B faturaları için bu zorunlu bir alandır ve reddedilmeye yol açar.' },
          { step: 'Geçersiz UUID', desc: 'Tanımlayıcı, önceki bir faturanın kopyasıdır veya geçerli UUID formatında değildir. Her fatura kendi benzersiz UUID\'sini taşımalıdır.' },
        ],
      },
      {
        title: 'Facturino geçerli UBL 2.1\'i nasıl üretir',
        content:
          'Facturino ile XML\'i elle yazmazsınız ve şema veya yuvarlama konusunda endişelenmezsiniz. Normal arayüzde bir fatura oluşturduğunuzda, Facturino standartla ve UJP gereksinimleriyle uyumlu UBL 2.1 XML\'i otomatik olarak üretir: KDV Kanunu Madde 53\'e göre tüm zorunlu alanları eşler, KDV\'yi orana göre hesaplar ve yuvarlar, benzersiz bir UUID atar ve belgeyi QES\'inizle imzalar. Sonuç, gönderime hazır geçerli bir e-faturadır — elle XML yok, şema hatası yok ve reddedilme riski yok. Unutmayın: bir PDF veya tarama e-fatura DEĞİLDİR — yalnızca imzalanmış bir UBL 2.1 XML yasal olarak geçerlidir ve Facturino bunu doğal olarak üretir.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-fatura için eksiksiz rehber' },
      { slug: 'kako-da-izdadete', title: 'E-fatura nasıl düzenlenir' },
      { slug: 'qes-potpis', title: 'E-fatura için QES imzası' },
      { slug: 'casuvanje-i-arhiva', title: 'Faturaların saklanması ve e-arşivlenmesi' },
    ],
    bottomCta: {
      title: 'XML yazmadan geçerli UBL 2.1',
      subtitle: 'Facturino uyumlu UBL 2.1 üretir ve QES ile imzalar — otomatik olarak.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function UblFormatPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const efLabel = locale === 'mk' ? 'Е-фактура' : locale === 'sq' ? 'E-fatura' : locale === 'tr' ? 'E-Fatura' : 'E-Invoice'
  const articleLd = articleJsonLd({
    locale,
    pathPrefix: 'e-faktura',
    slug: 'ubl-format',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'UBL', 'UBL 2.1', 'XML', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/ubl-format` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Што е UBL 2.1?', answer: 'UBL 2.1 (Universal Business Language) е меѓународен отворен XML стандард за структурирани деловни документи, стандардизиран од OASIS. Македонија го усвои како формат за е-фактурирање преку УЈП.' },
        { question: 'Дали PDF или скен е валидна е-фактура?', answer: 'Не. PDF или скенирана слика НЕ е валидна е-фактура. Само структуиран UBL 2.1 XML документ, потпишан со квалификуван електронски потпис (QES), е правно валидна е-фактура.' },
        { question: 'Кои полиња се задолжителни во UBL е-фактурата?', answer: 'Согласно Чл. 53 ЗДДВ задолжителни се: ЕДБ на продавачот и купувачот, датум на издавање, ставки со количина и единечна цена, ДДВ по стапка и уникатен идентификатор (UUID).' },
        { question: 'Кои се најчестите грешки при валидација?', answer: 'Најчести се: грешка во UBL 2.1 шемата, погрешно заокружување на ДДВ, недостаток на ЕДБ на купувачот и невалиден или дупликат UUID.' },
        { question: 'Дали Facturino генерира валиден UBL 2.1?', answer: 'Да. Facturino автоматски генерира UBL 2.1 XML усогласен со стандардот и барањата на УЈП, ги мапира сите задолжителни полиња, заокружува ДДВ по стапка, доделува UUID и потпишува со QES — без рачно пишување XML.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/e-faktura`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
            {t.backLink}
          </Link>

          <article>
            <header className="mb-10">
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                {t.tag}
              </span>
              <h1 className="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 leading-tight">
                {t.title}
              </h1>
              <p className="text-sm text-gray-500">
                {t.publishDate} · {t.readTime}
              </p>
            </header>

            <div className="prose prose-lg max-w-none">
              <p className="text-lg text-gray-700 leading-relaxed mb-8">{t.intro}</p>

              {t.sections.map((s, i) => (
                <section key={i} className="mb-10">
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">{s.title}</h2>
                  <p className="text-gray-700 leading-relaxed mb-4">{s.content}</p>

                  {s.items && (
                    <ul className="space-y-2 mb-4">
                      {s.items.map((item, j) => (
                        <li key={j} className="flex items-start gap-2">
                          <span className="text-blue-500 mt-1.5 text-xs">●</span>
                          <span className="text-gray-700">{item}</span>
                        </li>
                      ))}
                    </ul>
                  )}

                  {s.steps && (
                    <div className="space-y-4 mb-4">
                      {s.steps.map((step, j) => (
                        <div key={j} className="flex items-start gap-3">
                          <span className="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-sm font-bold">
                            {j + 1}
                          </span>
                          <div>
                            <p className="font-semibold text-gray-900">{step.step}</p>
                            <p className="text-gray-600 text-sm mt-1">{step.desc}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </section>
              ))}
            </div>

            <aside className="mt-12 pt-8 border-t border-gray-200">
              <h3 className="text-lg font-bold text-gray-900 mb-4">{t.relatedTitle}</h3>
              <div className="grid gap-3">
                {t.relatedArticles.map((ra, i) => (
                  <Link
                    key={i}
                    href={`/${locale}/e-faktura/${ra.slug}`}
                    className="text-blue-600 hover:text-blue-800 hover:underline"
                  >
                    {ra.title}
                  </Link>
                ))}
              </div>
            </aside>
          </article>

          <div className="mt-16 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl p-8 sm:p-12 text-center text-white">
            <h2 className="text-2xl sm:text-3xl font-extrabold mb-3">{t.bottomCta.title}</h2>
            <p className="text-blue-100 mb-6 text-lg">{t.bottomCta.subtitle}</p>
            <a
              href={t.bottomCta.href}
              className="inline-block bg-white text-blue-700 font-bold px-8 py-3.5 rounded-xl hover:bg-blue-50 transition-colors text-lg shadow-lg"
            >
              {t.bottomCta.cta}
            </a>
          </div>
        </div>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
