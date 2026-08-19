import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/kako-da-izdadete', {
    title: {
      mk: 'Како да издадете е-фактура: чекор-по-чекор',
      en: 'How to Issue an E-Invoice: Step-by-Step',
      sq: 'Si të lëshoni një e-faturë: hap pas hapi',
      tr: 'E-Fatura Nasıl Düzenlenir: Adım Adım',
    },
    description: {
      mk: 'Практично упатство како да издадете е-фактура во Македонија: регистрација на УЈП, набавка на QES, генерирање на UBL 2.1 XML, потпишување, поднесување, потврда и архивирање 10 години.',
      en: 'Practical how-to for issuing an e-invoice in North Macedonia: UJP registration, obtaining QES, generating UBL 2.1 XML, signing, submission, confirmation and 10-year archiving.',
      sq: 'Udhëzim praktik për të lëshuar një e-faturë në Maqedoni: regjistrimi në DAP, marrja e QES, gjenerimi i UBL 2.1 XML, nënshkrimi, dorëzimi, konfirmimi dhe arkivimi 10 vjet.',
      tr: 'Kuzey Makedonya\'da e-fatura düzenleme kılavuzu: UJP kaydı, QES edinme, UBL 2.1 XML oluşturma, imzalama, gönderim, onay ve 10 yıl arşivleme.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'Упатство',
    title: 'Како да издадете е-фактура: чекор-по-чекор',
    publishDate: '18 август 2026',
    readTime: '8 мин читање',
    intro:
      'Издавањето на е-фактура не е исто како испраќање PDF по email. Е-фактурата е структуриран UBL 2.1 XML документ, потпишан со квалификуван електронски потпис (QES) и поднесен преку платформата на УЈП. Ова упатство ве води чекор-по-чекор — од подготовката пред да почнете, преку самото издавање, до тоа што следи по испраќањето. Доброволната употреба е достапна веднаш, а B2G обврската започнува од октомври 2026.',
    sections: [
      {
        title: 'Што ви треба пред да почнете',
        content:
          'Пред да издадете својата прва е-фактура, проверете дали ги имате следните предуслови подготвени:',
        items: [
          'ЕДБ (даночен број) на вашата компанија — активна ДДВ регистрација',
          'Квалификуван електронски потпис (QES) од овластен издавач — Кибритон (kibriton.mk) или КИБС, цена ~2.000-5.000 МКД годишно, на USB токен или cloud',
          'Софтвер способен да генерира UBL 2.1 XML — не секој сметководствен програм го поддржува ова. Facturino го поддржува нативно',
          'Регистрирана сметка на платформата efaktura.ujp.gov.mk на УЈП',
          'ЕДБ на купувачот на кого му издавате фактура',
        ],
        steps: null,
      },
      {
        title: 'Чекор-по-чекор издавање е-фактура',
        content:
          'Кога сите предуслови се исполнети, самиот процес на издавање тече вака:',
        items: null,
        steps: [
          { step: 'Регистрирајте се на efaktura.ujp.gov.mk', desc: 'Отворете сметка на платформата на УЈП со ЕДБ на вашата компанија и QES. Ова е еднократен чекор — потоа само се најавувате.' },
          { step: 'Набавете и активирајте QES', desc: 'Ако сè уште немате квалификуван потпис, набавете го од Кибритон или КИБС. Тестирајте дека потписот работи пред првото издавање.' },
          { step: 'Внесете ги податоците на фактурата', desc: 'Внесете ЕДБ на купувачот, ставки (количина, единечна цена, опис), и ДДВ по стапка (18%, 10% или 5%). Секое поле мора да биде точно — грешките се одбиваат.' },
          { step: 'Генерирајте UBL 2.1 XML', desc: 'Софтверот ги трансформира внесените податоци во структуриран UBL 2.1 XML документ со уникатен идентификатор (UUID) за фактурата.' },
          { step: 'Потпишете со QES', desc: 'Применете го квалификуваниот електронски потпис на XML-от. Ова ја прави фактурата правно валидна и еквивалентна на хартиена.' },
          { step: 'Поднесете преку УЈП платформата или API', desc: 'Испратете ја потпишаната е-фактура преку веб-платформата на УЈП или преку директна API интеграција доколку вашиот софтвер ја поддржува.' },
          { step: 'Потврдете достава/прием', desc: 'Проверете го статусот на фактурата на платформата — испратена, доставена и примена од купувачот. Чувајте ја потврдата.' },
          { step: 'Архивирајте минимум 10 години', desc: 'Зачувајте ја оригиналната UBL XML датотека најмалку 10 години. Архивата мора да биде во оригинален формат, не како PDF или печатена копија.' },
        ],
      },
      {
        title: 'Задолжителни полиња на е-фактурата',
        content:
          'Според Чл. 53 од ЗДДВ, секоја е-фактура мора да ги содржи следните податоци во XML-от:',
        items: [
          'ЕДБ на продавачот (издавач на фактурата)',
          'ЕДБ на купувачот (примач на фактурата)',
          'Датум на издавање на фактурата',
          'Ставки со количина, единечна цена и опис на секоја позиција',
          'ДДВ прикажан по стапка (18%, 10%, 5%) со основица и износ',
          'Уникатен идентификатор на фактурата (UUID)',
          'Вкупен износ со и без ДДВ',
        ],
        steps: null,
      },
      {
        title: 'Чести грешки при издавање',
        content:
          'Овие грешки се причина број еден за одбиени или невалидни е-фактури — избегнете ги:',
        items: [
          'Испраќање PDF наместо UBL XML — PDF по email НЕ е е-фактура и не се прифаќа',
          'Недостаток на ЕДБ на купувачот во XML-от — задолжително поле по Чл. 53',
          'Погрешна ДДВ стапка или погрешна основица за пресметка',
          'Фактура без QES потпис — неподпишана фактура е правно невалидна',
          'Недостаток на уникатен идентификатор (UUID) на фактурата',
          'Неархивирање или архивирање само како печатена копија наместо оригинален UBL XML',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го автоматизира процесот',
        content:
          'Наместо рачно да генерирате XML, да го потпишувате и да го поднесувате, Facturino го автоматизира целиот тек во неколку клика:',
        items: null,
        steps: [
          { step: 'Креирајте фактура нормално', desc: 'Внесете купувач, ставки и ДДВ во познатиот интерфејс — исто како обична фактура.' },
          { step: 'Автоматско генерирање на UBL 2.1', desc: 'Facturino автоматски ги трансформира податоците во валиден UBL 2.1 XML со сите задолжителни полиња и UUID.' },
          { step: 'Потпис со QES', desc: 'Вашиот квалификуван потпис се применува автоматски на генерираниот XML.' },
          { step: 'Испраќање со еден клик', desc: 'Потпишаната е-фактура се поднесува директно преку УЈП интеграцијата — без рачно префрлање на датотеки.' },
        ],
      },
      {
        title: 'Што по издавањето',
        content:
          'Издавањето не е крајот на процесот. Еве што следи по успешно испратената е-фактура:',
        items: [
          'Потврда за достава — проверете дека фактурата е доставена и примена од купувачот преку статусот на платформата',
          'Корекции — ако има грешка, издајте одобрение (credit note) како посебна е-фактура; не менувајте ја оригиналната',
          'Одобренија и сторнирања се исто така структурирани UBL документи потпишани со QES',
          'Архивирање — чувајте ги сите е-фактури, одобренија и потврди минимум 10 години во оригинален UBL формат',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'qes-potpis', title: 'QES потпис' },
      { slug: 'ubl-format', title: 'UBL 2.1 формат' },
      { slug: 'rokovi-2026', title: 'Рокови 2026/2027' },
    ],
    bottomCta: {
      title: 'Издавајте е-фактури за минути',
      subtitle: 'Facturino генерира UBL 2.1, потпишува со QES и испраќа преку УЈП — автоматски.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'How-to',
    title: 'How to Issue an E-Invoice: Step-by-Step',
    publishDate: 'August 18, 2026',
    readTime: '8 min read',
    intro:
      'Issuing an e-invoice is not the same as sending a PDF by email. An e-invoice is a structured UBL 2.1 XML document, signed with a Qualified Electronic Signature (QES) and submitted via the UJP platform. This guide walks you through it step by step — from preparation before you start, through the actual issuing, to what comes after sending. Voluntary use is available now, and the B2G mandate begins in October 2026.',
    sections: [
      {
        title: 'What you need before you start',
        content:
          'Before issuing your first e-invoice, make sure you have the following prerequisites ready:',
        items: [
          'Your company EDB (tax number) — active VAT registration',
          'A Qualified Electronic Signature (QES) from an authorized issuer — Kibriton (kibriton.mk) or KIBS, cost ~2,000-5,000 MKD per year, on a USB token or cloud',
          'Software capable of generating UBL 2.1 XML — not every accounting program supports this. Facturino supports it natively',
          'A registered account on the UJP platform efaktura.ujp.gov.mk',
          'The EDB of the buyer you are invoicing',
        ],
        steps: null,
      },
      {
        title: 'Step-by-step: issuing an e-invoice',
        content:
          'Once all prerequisites are met, the issuing process flows like this:',
        items: null,
        steps: [
          { step: 'Register on efaktura.ujp.gov.mk', desc: 'Open an account on the UJP platform with your company EDB and QES. This is a one-time step — afterwards you simply log in.' },
          { step: 'Obtain and activate a QES', desc: 'If you do not yet have a qualified signature, obtain one from Kibriton or KIBS. Test that the signature works before your first issue.' },
          { step: 'Enter the invoice data', desc: 'Enter the buyer EDB, line items (quantity, unit price, description), and VAT by rate (18%, 10% or 5%). Every field must be correct — errors get rejected.' },
          { step: 'Generate UBL 2.1 XML', desc: 'The software transforms the entered data into a structured UBL 2.1 XML document with a unique identifier (UUID) for the invoice.' },
          { step: 'Sign with QES', desc: 'Apply the qualified electronic signature to the XML. This makes the invoice legally valid and equivalent to paper.' },
          { step: 'Submit via the UJP platform or API', desc: 'Send the signed e-invoice through the UJP web platform or via direct API integration if your software supports it.' },
          { step: 'Confirm delivery/receipt', desc: 'Check the invoice status on the platform — sent, delivered, and received by the buyer. Keep the confirmation.' },
          { step: 'Archive for at least 10 years', desc: 'Store the original UBL XML file for at least 10 years. The archive must be in the original format, not as a PDF or printed copy.' },
        ],
      },
      {
        title: 'Mandatory e-invoice fields',
        content:
          'Under Art. 53 of the VAT Law, every e-invoice must contain the following data in the XML:',
        items: [
          'Seller EDB (invoice issuer)',
          'Buyer EDB (invoice recipient)',
          'Invoice issue date',
          'Line items with quantity, unit price and description for each position',
          'VAT shown by rate (18%, 10%, 5%) with base and amount',
          'Unique invoice identifier (UUID)',
          'Total amount with and without VAT',
        ],
        steps: null,
      },
      {
        title: 'Common issuing mistakes',
        content:
          'These mistakes are the number one reason for rejected or invalid e-invoices — avoid them:',
        items: [
          'Sending a PDF instead of UBL XML — a PDF by email is NOT an e-invoice and is not accepted',
          'Missing buyer EDB in the XML — a mandatory field under Art. 53',
          'Wrong VAT rate or wrong tax base for calculation',
          'Invoice without a QES signature — an unsigned invoice is legally invalid',
          'Missing unique invoice identifier (UUID)',
          'Not archiving, or archiving only as a printed copy instead of the original UBL XML',
        ],
        steps: null,
      },
      {
        title: 'How Facturino automates the process',
        content:
          'Instead of manually generating XML, signing it, and submitting it, Facturino automates the entire flow in a few clicks:',
        items: null,
        steps: [
          { step: 'Create an invoice normally', desc: 'Enter the buyer, line items and VAT in the familiar interface — just like an ordinary invoice.' },
          { step: 'Automatic UBL 2.1 generation', desc: 'Facturino automatically transforms the data into valid UBL 2.1 XML with all mandatory fields and a UUID.' },
          { step: 'Sign with QES', desc: 'Your qualified signature is applied automatically to the generated XML.' },
          { step: 'Send in one click', desc: 'The signed e-invoice is submitted directly through the UJP integration — no manual file transfers.' },
        ],
      },
      {
        title: 'What happens after issuing',
        content:
          'Issuing is not the end of the process. Here is what follows a successfully sent e-invoice:',
        items: [
          'Delivery confirmation — verify the invoice was delivered and received by the buyer via the platform status',
          'Corrections — if there is an error, issue a credit note as a separate e-invoice; do not alter the original',
          'Credit notes and cancellations are also structured UBL documents signed with QES',
          'Archiving — keep all e-invoices, credit notes and confirmations for at least 10 years in the original UBL format',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoicing' },
      { slug: 'qes-potpis', title: 'QES Signature' },
      { slug: 'ubl-format', title: 'UBL 2.1 Format' },
      { slug: 'rokovi-2026', title: 'Deadlines 2026/2027' },
    ],
    bottomCta: {
      title: 'Issue e-invoices in minutes',
      subtitle: 'Facturino generates UBL 2.1, signs with QES and sends via UJP — automatically.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Udhëzim',
    title: 'Si të lëshoni një e-faturë: hap pas hapi',
    publishDate: '18 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Lëshimi i një e-fature nuk është njësoj si dërgimi i një PDF-je me email. E-fatura është një dokument i strukturuar UBL 2.1 XML, i nënshkruar me Nënshkrim Elektronik të Kualifikuar (QES) dhe i dorëzuar përmes platformës DAP. Ky udhëzues ju çon hap pas hapi — nga përgatitja para se të filloni, te vetë lëshimi, deri te ajo që vjen pas dërgimit. Përdorimi vullnetar është i disponueshëm tani, ndërsa detyrimi B2G fillon në tetor 2026.',
    sections: [
      {
        title: 'Çfarë ju duhet para se të filloni',
        content:
          'Para se të lëshoni e-faturën tuaj të parë, sigurohuni që keni gati parakushtet e mëposhtme:',
        items: [
          'EDB (numri tatimor) i kompanisë suaj — regjistrim aktiv i TVSH-së',
          'Nënshkrim Elektronik i Kualifikuar (QES) nga një lëshues i autorizuar — Kibriton (kibriton.mk) ose KIBS, kosto ~2.000-5.000 MKD në vit, në token USB ose cloud',
          'Softuer i aftë të gjenerojë UBL 2.1 XML — jo çdo program kontabiliteti e mbështet këtë. Facturino e mbështet natyrshëm',
          'Një llogari e regjistruar në platformën DAP efaktura.ujp.gov.mk',
          'EDB i blerësit të cilit po i lëshoni faturën',
        ],
        steps: null,
      },
      {
        title: 'Hap pas hapi: lëshimi i një e-fature',
        content:
          'Kur të gjitha parakushtet janë plotësuar, vetë procesi i lëshimit rrjedh kështu:',
        items: null,
        steps: [
          { step: 'Regjistrohuni në efaktura.ujp.gov.mk', desc: 'Hapni një llogari në platformën DAP me EDB-në e kompanisë suaj dhe QES. Ky është një hap i njëhershëm — më pas thjesht identifikoheni.' },
          { step: 'Merrni dhe aktivizoni një QES', desc: 'Nëse ende nuk keni një nënshkrim të kualifikuar, merrni një nga Kibriton ose KIBS. Testoni që nënshkrimi funksionon para lëshimit të parë.' },
          { step: 'Futni të dhënat e faturës', desc: 'Futni EDB-në e blerësit, artikujt (sasi, çmim njësie, përshkrim) dhe TVSH-në sipas normës (18%, 10% ose 5%). Çdo fushë duhet të jetë e saktë — gabimet refuzohen.' },
          { step: 'Gjeneroni UBL 2.1 XML', desc: 'Softueri i transformon të dhënat e futura në një dokument të strukturuar UBL 2.1 XML me një identifikues unik (UUID) për faturën.' },
          { step: 'Nënshkruani me QES', desc: 'Aplikoni nënshkrimin elektronik të kualifikuar në XML. Kjo e bën faturën ligjërisht të vlefshme dhe ekuivalente me letrën.' },
          { step: 'Dorëzoni përmes platformës DAP ose API', desc: 'Dërgoni e-faturën e nënshkruar përmes platformës web të DAP ose përmes integrimit të drejtpërdrejtë API nëse softueri juaj e mbështet.' },
          { step: 'Konfirmoni dorëzimin/marrjen', desc: 'Kontrolloni statusin e faturës në platformë — e dërguar, e dorëzuar dhe e marrë nga blerësi. Ruani konfirmimin.' },
          { step: 'Arkivoni për të paktën 10 vjet', desc: 'Ruani skedarin origjinal UBL XML për të paktën 10 vjet. Arkivi duhet të jetë në formatin origjinal, jo si PDF ose kopje e printuar.' },
        ],
      },
      {
        title: 'Fushat e detyrueshme të e-faturës',
        content:
          'Sipas Nenit 53 të Ligjit të TVSH-së, çdo e-faturë duhet të përmbajë të dhënat e mëposhtme në XML:',
        items: [
          'EDB i shitësit (lëshuesi i faturës)',
          'EDB i blerësit (marrësi i faturës)',
          'Data e lëshimit të faturës',
          'Artikujt me sasi, çmim njësie dhe përshkrim për çdo pozicion',
          'TVSH e shfaqur sipas normës (18%, 10%, 5%) me bazë dhe shumë',
          'Identifikues unik i faturës (UUID)',
          'Shuma totale me dhe pa TVSH',
        ],
        steps: null,
      },
      {
        title: 'Gabimet e shpeshta gjatë lëshimit',
        content:
          'Këto gabime janë arsyeja numër një për e-fatura të refuzuara ose të pavlefshme — shmangni ato:',
        items: [
          'Dërgimi i një PDF-je në vend të UBL XML — një PDF me email NUK është e-faturë dhe nuk pranohet',
          'Mungesa e EDB-së së blerësit në XML — fushë e detyrueshme sipas Nenit 53',
          'Normë TVSH-je e gabuar ose bazë tatimore e gabuar për llogaritje',
          'Faturë pa nënshkrim QES — një faturë e panënshkruar është ligjërisht e pavlefshme',
          'Mungesa e identifikuesit unik të faturës (UUID)',
          'Mosarkivimi, ose arkivimi vetëm si kopje e printuar në vend të UBL XML origjinal',
        ],
        steps: null,
      },
      {
        title: 'Si Facturino e automatizon procesin',
        content:
          'Në vend që të gjeneroni XML manualisht, ta nënshkruani dhe ta dorëzoni, Facturino e automatizon të gjithë rrjedhën në pak klikime:',
        items: null,
        steps: [
          { step: 'Krijoni një faturë normalisht', desc: 'Futni blerësin, artikujt dhe TVSH-në në ndërfaqen e njohur — njësoj si një faturë e zakonshme.' },
          { step: 'Gjenerim automatik i UBL 2.1', desc: 'Facturino automatikisht i transformon të dhënat në UBL 2.1 XML të vlefshme me të gjitha fushat e detyrueshme dhe një UUID.' },
          { step: 'Nënshkrim me QES', desc: 'Nënshkrimi juaj i kualifikuar aplikohet automatikisht në XML-në e gjeneruar.' },
          { step: 'Dërgoni me një klik', desc: 'E-fatura e nënshkruar dorëzohet drejtpërdrejt përmes integrimit DAP — pa transferime manuale skedarësh.' },
        ],
      },
      {
        title: 'Çfarë ndodh pas lëshimit',
        content:
          'Lëshimi nuk është fundi i procesit. Ja çfarë vjen pas një e-fature të dërguar me sukses:',
        items: [
          'Konfirmimi i dorëzimit — verifikoni që fatura u dorëzua dhe u mor nga blerësi përmes statusit të platformës',
          'Korrigjimet — nëse ka gabim, lëshoni një notë krediti (credit note) si e-faturë të veçantë; mos e ndryshoni origjinalin',
          'Notat e kreditit dhe anulimet janë gjithashtu dokumente të strukturuara UBL të nënshkruara me QES',
          'Arkivimi — ruani të gjitha e-faturat, notat e kreditit dhe konfirmimet për të paktën 10 vjet në formatin origjinal UBL',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'qes-potpis', title: 'Nënshkrimi QES' },
      { slug: 'ubl-format', title: 'Formati UBL 2.1' },
      { slug: 'rokovi-2026', title: 'Afatet 2026/2027' },
    ],
    bottomCta: {
      title: 'Lëshoni e-fatura në minuta',
      subtitle: 'Facturino gjeneron UBL 2.1, nënshkruan me QES dhe dërgon përmes DAP — automatikisht.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'Kılavuz',
    title: 'E-Fatura Nasıl Düzenlenir: Adım Adım',
    publishDate: '18 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'E-fatura düzenlemek, e-posta ile PDF göndermekle aynı şey değildir. E-fatura, yapılandırılmış bir UBL 2.1 XML belgesidir, Nitelikli Elektronik İmza (QES) ile imzalanır ve UJP platformu üzerinden gönderilir. Bu kılavuz sizi adım adım yönlendirir — başlamadan önceki hazırlıktan, fatura düzenlemenin kendisine, gönderimden sonra gelenlere kadar. Gönüllü kullanım şimdi mevcut, B2G zorunluluğu Ekim 2026\'da başlıyor.',
    sections: [
      {
        title: 'Başlamadan önce neye ihtiyacınız var',
        content:
          'İlk e-faturanızı düzenlemeden önce aşağıdaki ön koşulların hazır olduğundan emin olun:',
        items: [
          'Şirketinizin EDB\'si (vergi numarası) — aktif KDV kaydı',
          'Yetkili bir sağlayıcıdan Nitelikli Elektronik İmza (QES) — Kibriton (kibriton.mk) veya KIBS, maliyet ~2.000-5.000 MKD yıllık, USB token veya bulut üzerinde',
          'UBL 2.1 XML üretebilen yazılım — her muhasebe programı bunu desteklemez. Facturino doğal olarak destekler',
          'UJP platformu efaktura.ujp.gov.mk üzerinde kayıtlı bir hesap',
          'Fatura kestiğiniz alıcının EDB\'si',
        ],
        steps: null,
      },
      {
        title: 'Adım adım: e-fatura düzenleme',
        content:
          'Tüm ön koşullar karşılandığında, düzenleme süreci şöyle işler:',
        items: null,
        steps: [
          { step: 'efaktura.ujp.gov.mk\'ye kaydolun', desc: 'Şirket EDB\'niz ve QES ile UJP platformunda bir hesap açın. Bu tek seferlik bir adımdır — ardından sadece giriş yaparsınız.' },
          { step: 'QES edinin ve etkinleştirin', desc: 'Henüz nitelikli imzanız yoksa, Kibriton veya KIBS\'ten edinin. İlk düzenlemeden önce imzanın çalıştığını test edin.' },
          { step: 'Fatura verilerini girin', desc: 'Alıcı EDB\'sini, kalemleri (miktar, birim fiyat, açıklama) ve orana göre KDV\'yi (%18, %10 veya %5) girin. Her alan doğru olmalı — hatalar reddedilir.' },
          { step: 'UBL 2.1 XML oluşturun', desc: 'Yazılım, girilen verileri fatura için benzersiz tanımlayıcı (UUID) içeren yapılandırılmış bir UBL 2.1 XML belgesine dönüştürür.' },
          { step: 'QES ile imzalayın', desc: 'Nitelikli elektronik imzayı XML\'e uygulayın. Bu, faturayı yasal olarak geçerli ve kağıt faturayla eşdeğer kılar.' },
          { step: 'UJP platformu veya API üzerinden gönderin', desc: 'İmzalı e-faturayı UJP web platformu üzerinden veya yazılımınız destekliyorsa doğrudan API entegrasyonu ile gönderin.' },
          { step: 'Teslimatı/alımı onaylayın', desc: 'Faturanın durumunu platformda kontrol edin — gönderildi, teslim edildi ve alıcı tarafından alındı. Onayı saklayın.' },
          { step: 'En az 10 yıl arşivleyin', desc: 'Orijinal UBL XML dosyasını en az 10 yıl saklayın. Arşiv orijinal formatta olmalı, PDF veya basılı kopya olarak değil.' },
        ],
      },
      {
        title: 'Zorunlu e-fatura alanları',
        content:
          'KDV Kanunu Madde 53\'e göre her e-fatura XML\'de aşağıdaki verileri içermelidir:',
        items: [
          'Satıcı EDB\'si (fatura düzenleyen)',
          'Alıcı EDB\'si (fatura alan)',
          'Fatura düzenleme tarihi',
          'Her pozisyon için miktar, birim fiyat ve açıklama içeren kalemler',
          'Orana göre gösterilen KDV (%18, %10, %5) matrah ve tutar ile',
          'Benzersiz fatura tanımlayıcısı (UUID)',
          'KDV dahil ve hariç toplam tutar',
        ],
        steps: null,
      },
      {
        title: 'Düzenlemede sık yapılan hatalar',
        content:
          'Bu hatalar reddedilen veya geçersiz e-faturaların bir numaralı nedenidir — bunlardan kaçının:',
        items: [
          'UBL XML yerine PDF göndermek — e-posta ile PDF e-fatura DEĞİLDİR ve kabul edilmez',
          'XML\'de alıcı EDB\'sinin eksik olması — Madde 53\'e göre zorunlu alan',
          'Yanlış KDV oranı veya hesaplama için yanlış matrah',
          'QES imzası olmayan fatura — imzasız fatura yasal olarak geçersizdir',
          'Benzersiz fatura tanımlayıcısının (UUID) eksik olması',
          'Arşivlememe veya orijinal UBL XML yerine yalnızca basılı kopya olarak arşivleme',
        ],
        steps: null,
      },
      {
        title: 'Facturino süreci nasıl otomatikleştirir',
        content:
          'XML\'i manuel oluşturmak, imzalamak ve göndermek yerine, Facturino tüm akışı birkaç tıklamayla otomatikleştirir:',
        items: null,
        steps: [
          { step: 'Faturayı normal şekilde oluşturun', desc: 'Alıcıyı, kalemleri ve KDV\'yi tanıdık arayüzde girin — sıradan bir fatura gibi.' },
          { step: 'Otomatik UBL 2.1 oluşturma', desc: 'Facturino, verileri tüm zorunlu alanlar ve bir UUID ile geçerli UBL 2.1 XML\'e otomatik dönüştürür.' },
          { step: 'QES ile imzalama', desc: 'Nitelikli imzanız oluşturulan XML\'e otomatik uygulanır.' },
          { step: 'Tek tıkla gönderin', desc: 'İmzalı e-fatura, UJP entegrasyonu üzerinden doğrudan gönderilir — manuel dosya transferi yok.' },
        ],
      },
      {
        title: 'Düzenlemeden sonra ne olur',
        content:
          'Düzenleme sürecin sonu değildir. Başarıyla gönderilen bir e-faturadan sonra gelenler:',
        items: [
          'Teslimat onayı — faturanın platform durumu üzerinden alıcıya teslim edildiğini ve alındığını doğrulayın',
          'Düzeltmeler — hata varsa, ayrı bir e-fatura olarak iade faturası (credit note) düzenleyin; orijinali değiştirmeyin',
          'İade faturaları ve iptaller de QES ile imzalanan yapılandırılmış UBL belgeleridir',
          'Arşivleme — tüm e-faturaları, iade faturalarını ve onayları orijinal UBL formatında en az 10 yıl saklayın',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-Faturaya Kapsamlı Kılavuz' },
      { slug: 'qes-potpis', title: 'QES İmzası' },
      { slug: 'ubl-format', title: 'UBL 2.1 Formatı' },
      { slug: 'rokovi-2026', title: 'Son Tarihler 2026/2027' },
    ],
    bottomCta: {
      title: 'Dakikalar içinde e-fatura düzenleyin',
      subtitle: 'Facturino UBL 2.1 oluşturur, QES ile imzalar ve UJP üzerinden gönderir — otomatik olarak.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaKakoDaIzdadetePage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const efLabel = locale === 'mk' ? 'Е-фактура' : locale === 'sq' ? 'E-fatura' : locale === 'tr' ? 'E-Fatura' : 'E-Invoice'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const articleLd = articleJsonLd({
    locale,
    pathPrefix: 'e-faktura',
    slug: 'kako-da-izdadete',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'UBL', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/kako-da-izdadete` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Дали PDF по email е е-фактура?', answer: 'Не. Е-фактурата е структуриран UBL 2.1 XML документ потпишан со QES. PDF испратен по email НЕ се смета за е-фактура и не се прифаќа преку платформата на УЈП.' },
        { question: 'Што ми треба за да издадам е-фактура?', answer: 'Ви требаат ЕДБ на компанијата, квалификуван електронски потпис (QES) од Кибритон или КИБС, софтвер што генерира UBL 2.1 XML и регистрирана сметка на efaktura.ujp.gov.mk.' },
        { question: 'Колку долго мора да ги чувам е-фактурите?', answer: 'Е-фактурите мора да се архивираат минимум 10 години во оригинален UBL XML формат — не како печатена копија или PDF.' },
        { question: 'Кои се задолжителните полиња на е-фактурата?', answer: 'Според Чл. 53 од ЗДДВ: ЕДБ на продавачот и купувачот, датум, ставки со количина и единечна цена, ДДВ по стапка и уникатен идентификатор (UUID).' },
        { question: 'Како да поправам грешка на веќе издадена е-фактура?', answer: 'Не ја менувајте оригиналната фактура. Издајте одобрение (credit note) како посебна структурирана UBL е-фактура потпишана со QES.' },
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
