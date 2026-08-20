import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/casti-prasanja', {
    title: {
      mk: 'Е-фактура: често поставувани прашања (ЧПП)',
      en: 'E-Invoicing FAQ: Frequently Asked Questions',
      sq: 'E-fatura: pyetjet e bëra shpesh (PBSH)',
      tr: 'E-Fatura: Sıkça Sorulan Sorular (SSS)',
    },
    description: {
      mk: 'Одговори на најчестите прашања за е-фактура во Македонија: што е UBL 2.1, кога станува задолжителна, QES потпис, регистрација во УЈП, цени, чување 10 години и казни за неусогласеност.',
      en: 'Answers to the most common e-invoicing questions in North Macedonia: what UBL 2.1 is, when it becomes mandatory, QES signing, UJP registration, costs, 10-year archiving, and penalties.',
      sq: 'Përgjigje për pyetjet më të shpeshta për e-faturën në Maqedoni: çfarë është UBL 2.1, kur bëhet e detyrueshme, nënshkrimi QES, regjistrimi në DAP, kostot, ruajtja 10 vjet dhe gjobat.',
      tr: 'Kuzey Makedonya e-fatura hakkında en sık sorulan soruların yanıtları: UBL 2.1 nedir, ne zaman zorunlu olur, QES imzası, UJP kaydı, maliyetler, 10 yıl arşivleme ve cezalar.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'ЧПП',
    title: 'Е-фактура: често поставувани прашања (ЧПП)',
    publishDate: '18 август 2026',
    readTime: '10 мин читање',
    intro:
      'Собравме одговори на најчестите прашања за е-фактурата во Македонија — од тоа што точно претставува е-фактура, преку роковите за задолжителна употреба, QES потписот и регистрацијата во УЈП, до цените, чувањето и казните. Ако тукушто почнувате со подготовките, овие одговори ќе ви дадат јасна слика за целиот процес.',
    sections: [
      {
        title: 'Што е е-фактура?',
        content:
          'Е-фактура е фактура во структуриран дигитален формат (UBL 2.1 XML) која содржи машински читливи податоци за продавачот, купувачот, ставките, ДДВ и вкупните износи. Таа е потпишана со квалификуван електронски потпис (QES) и е правно еквивалентна на хартиена фактура. За разлика од хартиена или скенирана фактура, е-фактурата може автоматски да се обработува од сметководствени и даночни системи.',
        items: null,
        steps: null,
      },
      {
        title: 'Дали PDF испратен по email е е-фактура?',
        content:
          'Не. PDF документ приложен на email НЕ е е-фактура, дури и ако изгледа професионално и содржи сите потребни податоци. Е-фактурата мора да биде во структуриран UBL 2.1 XML формат кој е машински читлив — PDF е слика наменета за човечко читање, не за автоматска обработка. Испраќањето PDF наместо UBL XML не ја исполнува законската обврска.',
        items: null,
        steps: null,
      },
      {
        title: 'Кога станува задолжителна?',
        content:
          'Имплементацијата е фазна. Од октомври 2026 е-фактурата станува задолжителна за B2G трансакции (кон државни институции и буџетски корисници). Од јануари 2027 (планирано) обврската се проширува на големи B2B обврзници со годишен промет над 8.000.000 МКД, а од јули 2027 (планирано) на сите ДДВ обврзници. Доброволната употреба е достапна веднаш преку платформата на УЈП.',
        items: null,
        steps: null,
      },
      {
        title: 'Кој мора да издава е-фактура?',
        content:
          'Прво се засегнати сите компании кои фактурираат кон државни институции, јавни претпријатија и буџетски корисници (B2G) — од октомври 2026. Потоа, фазно во текот на 2027, обврската опфаќа сите ДДВ обврзници за B2B трансакции, почнувајќи од оние со поголем промет. Странските компании со ДДВ регистрација во Македонија кои фактурираат кон МК субјекти исто така се засегнати.',
        items: null,
        steps: null,
      },
      {
        title: 'Што е QES и зошто е потребен?',
        content:
          'QES (Qualified Electronic Signature) е квалификуван електронски потпис — највисокото ниво на електронски потпис што правно е еквивалентно на своерачен потпис. Тој гарантира дека е-фактурата не е изменета по потпишувањето и недвосмислено го идентификува издавачот. Секоја е-фактура мора да биде потпишана со QES за да биде правно валидна и прифатена од платформата на УЈП.',
        items: null,
        steps: null,
      },
      {
        title: 'Каде да набавам QES?',
        content:
          'QES сертификат можете да набавите од овластен издавач на квалификувани сертификати, како Кибритон (kibriton.mk) или КИБС. Сертификатот доаѓа на USB токен или како cloud-based решение. Годишната цена вообичаено се движи од 2.000 до 5.000 МКД, во зависност од провајдерот и типот на сертификатот.',
        items: null,
        steps: null,
      },
      {
        title: 'Што е UBL 2.1?',
        content:
          'UBL 2.1 (Universal Business Language) е меѓународен отворен стандард за структурирани деловни документи во XML формат, вклучувајќи фактури. Македонија го усвои како формат за е-фактурирање. UBL го дефинира точниот распоред на податоците — продавач, купувач, ставки, количини, единечни цени, ДДВ по стапки — така што секој компатибилен систем може автоматски да ја чита и обработува фактурата.',
        items: null,
        steps: null,
      },
      {
        title: 'Како да се регистрирам на УЈП платформата?',
        content:
          'Регистрацијата се врши на платформата efaktura.ujp.gov.mk. За да ја регистрирате вашата компанија ви требаат ЕДБ (даночен број) и валиден QES сертификат. По регистрацијата имате пристап до sandbox (тест) околина каде можете да испраќате пробни е-фактури без правни последици, пред да преминете на продукциска употреба.',
        items: null,
        steps: null,
      },
      {
        title: 'Колку чини е-фактурирањето?',
        content:
          'Главниот трошок е QES сертификатот — вообичаено 2.000 до 5.000 МКД годишно по потпис. Самата платформа на УЈП за поднесување е-фактури нема директна такса за корисниците. Дополнителен трошок може да биде сметководствен или фактурен софтвер кој генерира UBL 2.1 XML — Facturino ова го поддржува нативно, без потреба од посебен модул.',
        items: null,
        steps: null,
      },
      {
        title: 'Колку долго морам да ги чувам е-фактурите?',
        content:
          'Е-фактурите мора да се чуваат минимум 10 години, во оригиналниот UBL 2.1 XML формат (не како PDF или печатена копија). Архивата мора да остане читлива и достапна за целиот период за потреби на даночна инспекција. Facturino автоматски ги архивира издадените и примените е-фактури во оригинален формат.',
        items: null,
        steps: null,
      },
      {
        title: 'Кои се казните за неусогласеност?',
        content:
          'За правно лице кое издава хартиена наместо е-фактура кога е тоа задолжително, глобата се движи од EUR 500 до 3.000, а за одговорното лице во фирмата од EUR 100 до 500. Дополнително, буџетските корисници може да ги одбијат неелектронските фактури, а неусогласените компании ризикуваат исклучување од јавни набавки.',
        items: null,
        steps: null,
      },
      {
        title: 'Дали можам да користам е-фактура доброволно сега?',
        content:
          'Да. Доброволната употреба на е-фактура е достапна веднаш преку платформата на УЈП, пред да настапат задолжителните рокови. Раното преминување ви дава време да ги тестирате процесите, да го обучите персоналот и да избегнете притисок пред крајните датуми. Компаниите кои почнуваат рано вообичаено имаат помазна транзиција.',
        items: null,
        steps: null,
      },
      {
        title: 'Дали Facturino поддржува е-фактура?',
        content:
          'Да. Facturino нативно поддржува генерирање на е-фактури во UBL 2.1 XML формат и потпишување со QES, без потреба од посебни надградби или додатоци. Фактурите ги содржат сите задолжителни полиња по Чл. 53 ЗДДВ, со уникатен идентификатор (UUID) по фактура, и се архивираат во оригинален формат.',
        items: null,
        steps: null,
      },
      {
        title: 'Која е разликата помеѓу B2G и B2B обврската?',
        content:
          'B2G (business-to-government) се однесува на фактурирање кон државни институции и буџетски корисници — оваа обврска стапува прва, во октомври 2026. B2B (business-to-business) се однесува на фактурирање помеѓу приватни компании и се воведува фазно во текот на 2027, почнувајќи од поголемите обврзници. Со други зборови, прво мора да ги електронизирате фактурите кон државата, а потоа и кон другите фирми.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'qes-potpis', title: 'QES потпис' },
      { slug: 'rokovi-2026', title: 'Рокови 2026/2027' },
    ],
    bottomCta: {
      title: 'Подготвени за е-фактура?',
      subtitle: 'Facturino поддржува UBL 2.1 и QES потпис — бидете спремни пред сите.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'FAQ',
    title: 'E-Invoicing FAQ: Frequently Asked Questions',
    publishDate: 'August 18, 2026',
    readTime: '10 min read',
    intro:
      'We gathered answers to the most common questions about e-invoicing in North Macedonia — from what an e-invoice actually is, through the deadlines for mandatory use, the QES signature and UJP registration, to costs, archiving and penalties. If you are just starting to prepare, these answers give you a clear picture of the whole process.',
    sections: [
      {
        title: 'What is an e-invoice?',
        content:
          'An e-invoice is an invoice in a structured digital format (UBL 2.1 XML) that contains machine-readable data about the seller, buyer, line items, VAT, and totals. It is signed with a Qualified Electronic Signature (QES) and is legally equivalent to a paper invoice. Unlike a paper or scanned invoice, an e-invoice can be processed automatically by accounting and tax systems.',
        items: null,
        steps: null,
      },
      {
        title: 'Is a PDF sent by email an e-invoice?',
        content:
          'No. A PDF document attached to an email is NOT an e-invoice, even if it looks professional and contains all the required data. An e-invoice must be in the structured, machine-readable UBL 2.1 XML format — a PDF is an image meant for human reading, not automatic processing. Sending a PDF instead of UBL XML does not satisfy the legal obligation.',
        items: null,
        steps: null,
      },
      {
        title: 'When does it become mandatory?',
        content:
          'Implementation is phased. From October 2026, e-invoicing becomes mandatory for B2G transactions (to government institutions and budget users). From January 2027 (planned) the mandate extends to large B2B taxpayers with annual turnover above 8,000,000 MKD, and from July 2027 (planned) to all VAT-registered companies. Voluntary use is available now through the UJP platform.',
        items: null,
        steps: null,
      },
      {
        title: 'Who must issue e-invoices?',
        content:
          'First affected are all companies invoicing government institutions, public enterprises, and budget users (B2G) — from October 2026. Then, phased throughout 2027, the mandate covers all VAT-registered companies for B2B transactions, starting with those with higher turnover. Foreign companies with MK VAT registration invoicing Macedonian entities are also affected.',
        items: null,
        steps: null,
      },
      {
        title: 'What is QES and why is it needed?',
        content:
          'QES (Qualified Electronic Signature) is the highest level of electronic signature, legally equivalent to a handwritten signature. It guarantees that the e-invoice has not been altered after signing and unambiguously identifies the issuer. Every e-invoice must be signed with a QES to be legally valid and accepted by the UJP platform.',
        items: null,
        steps: null,
      },
      {
        title: 'Where do I obtain a QES?',
        content:
          'You can obtain a QES certificate from an authorized issuer of qualified certificates, such as Kibriton (kibriton.mk) or KIBS. The certificate comes on a USB token or as a cloud-based solution. The annual cost typically ranges from 2,000 to 5,000 MKD, depending on the provider and certificate type.',
        items: null,
        steps: null,
      },
      {
        title: 'What is UBL 2.1?',
        content:
          'UBL 2.1 (Universal Business Language) is an international open standard for structured business documents in XML format, including invoices. North Macedonia adopted it as the e-invoicing format. UBL defines the exact layout of the data — seller, buyer, line items, quantities, unit prices, VAT by rate — so that any compatible system can automatically read and process the invoice.',
        items: null,
        steps: null,
      },
      {
        title: 'How do I register on the UJP platform?',
        content:
          'Registration is done on the efaktura.ujp.gov.mk platform. To register your company you need your EDB (tax number) and a valid QES certificate. After registration you have access to a sandbox (test) environment where you can send trial e-invoices without legal consequences before moving to production use.',
        items: null,
        steps: null,
      },
      {
        title: 'How much does e-invoicing cost?',
        content:
          'The main cost is the QES certificate — typically 2,000 to 5,000 MKD per year per signature. The UJP platform itself for submitting e-invoices has no direct fee for users. An additional cost may be accounting or invoicing software that generates UBL 2.1 XML — Facturino supports this natively, without a separate module.',
        items: null,
        steps: null,
      },
      {
        title: 'How long must I keep e-invoices?',
        content:
          'E-invoices must be kept for a minimum of 10 years, in the original UBL 2.1 XML format (not as a PDF or printed copy). The archive must remain readable and accessible for the entire period for tax inspection purposes. Facturino automatically archives issued and received e-invoices in their original format.',
        items: null,
        steps: null,
      },
      {
        title: 'What are the penalties for non-compliance?',
        content:
          'For a legal entity issuing a paper invoice instead of an e-invoice when it is mandatory, the fine ranges from EUR 500 to 3,000, and for the responsible person in the company from EUR 100 to 500. In addition, budget buyers may reject non-electronic invoices, and non-compliant companies risk exclusion from public procurement.',
        items: null,
        steps: null,
      },
      {
        title: 'Can I use e-invoicing voluntarily now?',
        content:
          'Yes. Voluntary use of e-invoicing is available now through the UJP platform, before the mandatory deadlines take effect. Switching early gives you time to test your processes, train your staff, and avoid pressure before the deadlines. Companies that start early typically have a smoother transition.',
        items: null,
        steps: null,
      },
      {
        title: 'Does Facturino support e-invoicing?',
        content:
          'Yes. Facturino natively supports generating e-invoices in UBL 2.1 XML format and signing them with QES, without the need for separate upgrades or add-ons. Invoices contain all mandatory fields per Art. 53 of the VAT Law, with a unique identifier (UUID) per invoice, and are archived in their original format.',
        items: null,
        steps: null,
      },
      {
        title: 'What is the difference between the B2G and B2B mandate?',
        content:
          'B2G (business-to-government) refers to invoicing government institutions and budget users — this obligation comes first, in October 2026. B2B (business-to-business) refers to invoicing between private companies and is introduced in phases throughout 2027, starting with larger taxpayers. In other words, you must first digitize invoices to the government, and then to other companies.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoicing' },
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'qes-potpis', title: 'QES Signature' },
      { slug: 'rokovi-2026', title: 'Deadlines 2026/2027' },
    ],
    bottomCta: {
      title: 'Ready for e-invoicing?',
      subtitle: 'Facturino supports UBL 2.1 and QES signing — be prepared before the deadline.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'PBSH',
    title: 'E-fatura: pyetjet e bëra shpesh (PBSH)',
    publishDate: '18 gusht 2026',
    readTime: '10 min lexim',
    intro:
      'Mblodhëm përgjigje për pyetjet më të shpeshta rreth e-faturës në Maqedoni — nga ajo se çfarë është realisht një e-faturë, tek afatet për përdorim të detyrueshëm, nënshkrimi QES dhe regjistrimi në DAP, deri te kostot, ruajtja dhe gjobat. Nëse sapo po filloni përgatitjet, këto përgjigje ju japin një pamje të qartë të gjithë procesit.',
    sections: [
      {
        title: 'Çfarë është e-fatura?',
        content:
          'E-fatura është një faturë në format dixhital të strukturuar (UBL 2.1 XML) që përmban të dhëna të lexueshme nga makina për shitësin, blerësin, artikujt, TVSH-në dhe totalet. Ajo nënshkruhet me Nënshkrim Elektronik të Kualifikuar (QES) dhe është ligjërisht ekuivalente me një faturë në letër. Ndryshe nga një faturë letre ose e skanuar, e-fatura mund të përpunohet automatikisht nga sistemet kontabël dhe tatimore.',
        items: null,
        steps: null,
      },
      {
        title: 'A është e-faturë një PDF i dërguar me email?',
        content:
          'Jo. Një dokument PDF i bashkëngjitur në email NUK është e-faturë, edhe nëse duket profesional dhe përmban të gjitha të dhënat e nevojshme. E-fatura duhet të jetë në formatin e strukturuar UBL 2.1 XML që lexohet nga makina — PDF-ja është një imazh i destinuar për lexim njerëzor, jo për përpunim automatik. Dërgimi i një PDF-je në vend të UBL XML nuk e plotëson detyrimin ligjor.',
        items: null,
        steps: null,
      },
      {
        title: 'Kur bëhet e detyrueshme?',
        content:
          'Zbatimi është fazor. Nga tetori 2026, e-fatura bëhet e detyrueshme për transaksionet B2G (drejt institucioneve shtetërore dhe përdoruesve buxhetorë). Nga janari 2027 (planifikuar) detyrimi zgjerohet për tatimpaguesit e mëdhenj B2B me qarkullim vjetor mbi 8.000.000 MKD, dhe nga korriku 2027 (planifikuar) për të gjithë tatimpaguesit e TVSH-së. Përdorimi vullnetar është i disponueshëm tani përmes platformës DAP.',
        items: null,
        steps: null,
      },
      {
        title: 'Kush duhet të lëshojë e-fatura?',
        content:
          'Fillimisht preken të gjitha kompanitë që faturojnë institucionet shtetërore, ndërmarrjet publike dhe përdoruesit buxhetorë (B2G) — nga tetori 2026. Më pas, me faza gjatë 2027, detyrimi mbulon të gjithë tatimpaguesit e TVSH-së për transaksionet B2B, duke filluar me ata me qarkullim më të lartë. Kompanitë e huaja me regjistrim TVSH në MK që faturojnë subjekte maqedonase gjithashtu preken.',
        items: null,
        steps: null,
      },
      {
        title: 'Çfarë është QES dhe pse nevojitet?',
        content:
          'QES (Nënshkrim Elektronik i Kualifikuar) është niveli më i lartë i nënshkrimit elektronik, ligjërisht ekuivalent me një nënshkrim me dorë. Ai garanton se e-fatura nuk është ndryshuar pas nënshkrimit dhe identifikon pa mëdyshje lëshuesin. Çdo e-faturë duhet të nënshkruhet me QES për të qenë ligjërisht e vlefshme dhe e pranuar nga platforma DAP.',
        items: null,
        steps: null,
      },
      {
        title: 'Ku ta marr një QES?',
        content:
          'Mund të merrni një certifikatë QES nga një lëshues i autorizuar i certifikatave të kualifikuara, si Kibriton (kibriton.mk) ose KIBS. Certifikata vjen në një token USB ose si zgjidhje cloud. Kostoja vjetore zakonisht varion nga 2.000 deri në 5.000 MKD, në varësi të ofruesit dhe llojit të certifikatës.',
        items: null,
        steps: null,
      },
      {
        title: 'Çfarë është UBL 2.1?',
        content:
          'UBL 2.1 (Universal Business Language) është një standard i hapur ndërkombëtar për dokumente biznesi të strukturuara në format XML, përfshirë faturat. Maqedonia e adoptoi si formatin e e-faturimit. UBL përcakton strukturën e saktë të të dhënave — shitës, blerës, artikuj, sasi, çmime njësie, TVSH sipas normave — në mënyrë që çdo sistem i pajtueshëm të lexojë dhe përpunojë automatikisht faturën.',
        items: null,
        steps: null,
      },
      {
        title: 'Si të regjistrohem në platformën DAP?',
        content:
          'Regjistrimi bëhet në platformën efaktura.ujp.gov.mk. Për të regjistruar kompaninë tuaj ju nevojitet EDB (numri tatimor) dhe një certifikatë QES e vlefshme. Pas regjistrimit keni qasje në një mjedis sandbox (testues) ku mund të dërgoni e-fatura provë pa pasoja ligjore para se të kaloni në përdorim produksioni.',
        items: null,
        steps: null,
      },
      {
        title: 'Sa kushton e-faturimi?',
        content:
          'Kostoja kryesore është certifikata QES — zakonisht 2.000 deri në 5.000 MKD në vit për nënshkrim. Vetë platforma DAP për dorëzimin e e-faturave nuk ka tarifë të drejtpërdrejtë për përdoruesit. Një kosto shtesë mund të jetë softueri kontabël ose i faturimit që gjeneron UBL 2.1 XML — Facturino e mbështet këtë nativisht, pa nevojë për një modul të veçantë.',
        items: null,
        steps: null,
      },
      {
        title: 'Sa gjatë duhet t\'i ruaj e-faturat?',
        content:
          'E-faturat duhet të ruhen minimum 10 vjet, në formatin origjinal UBL 2.1 XML (jo si PDF ose kopje e printuar). Arkivi duhet të mbetet i lexueshëm dhe i qasshëm gjatë gjithë periudhës për qëllime të inspektimit tatimor. Facturino arkivon automatikisht e-faturat e lëshuara dhe të pranuara në formatin e tyre origjinal.',
        items: null,
        steps: null,
      },
      {
        title: 'Cilat janë gjobat për mospajtueshmëri?',
        content:
          'Për një person juridik që lëshon faturë letre në vend të e-faturës kur është e detyrueshme, gjoba varion nga EUR 500 deri në 3.000, dhe për personin përgjegjës në kompani nga EUR 100 deri në 500. Për më tepër, blerësit buxhetorë mund të refuzojnë faturat joelektronike, dhe kompanitë jopajtuese rrezikojnë përjashtimin nga prokurimi publik.',
        items: null,
        steps: null,
      },
      {
        title: 'A mund ta përdor e-faturën vullnetarisht tani?',
        content:
          'Po. Përdorimi vullnetar i e-faturës është i disponueshëm tani përmes platformës DAP, para se të hyjnë në fuqi afatet e detyrueshme. Kalimi i hershëm ju jep kohë të testoni proceset, të trajnoni stafin dhe të shmangni presionin para afateve. Kompanitë që fillojnë herët zakonisht kanë një tranzicion më të butë.',
        items: null,
        steps: null,
      },
      {
        title: 'A e mbështet Facturino e-faturën?',
        content:
          'Po. Facturino mbështet nativisht gjenerimin e e-faturave në formatin UBL 2.1 XML dhe nënshkrimin e tyre me QES, pa nevojë për përditësime ose shtesa të veçanta. Faturat përmbajnë të gjitha fushat e detyrueshme sipas Nenit 53 të Ligjit të TVSH-së, me një identifikues unik (UUID) për faturë, dhe arkivohen në formatin e tyre origjinal.',
        items: null,
        steps: null,
      },
      {
        title: 'Cili është ndryshimi midis detyrimit B2G dhe B2B?',
        content:
          'B2G (biznes-drejt-qeverisë) i referohet faturimit të institucioneve shtetërore dhe përdoruesve buxhetorë — ky detyrim vjen i pari, në tetor 2026. B2B (biznes-drejt-biznesit) i referohet faturimit midis kompanive private dhe futet me faza gjatë 2027, duke filluar me tatimpaguesit më të mëdhenj. Me fjalë të tjera, fillimisht duhet t\'i dixhitalizoni faturat drejt qeverisë, dhe më pas drejt kompanive të tjera.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'qes-potpis', title: 'Nënshkrimi QES' },
      { slug: 'rokovi-2026', title: 'Afatet 2026/2027' },
    ],
    bottomCta: {
      title: 'Gati për e-faturën?',
      subtitle: 'Facturino mbështet UBL 2.1 dhe nënshkrimin QES — përgatituni para afatit.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'SSS',
    title: 'E-Fatura: Sıkça Sorulan Sorular (SSS)',
    publishDate: '18 Ağustos 2026',
    readTime: '10 dk okuma',
    intro:
      'Kuzey Makedonya\'da e-fatura hakkında en sık sorulan soruların yanıtlarını topladık — e-faturanın gerçekte ne olduğundan, zorunlu kullanım son tarihlerine, QES imzası ve UJP kaydından maliyetlere, arşivlemeye ve cezalara kadar. Hazırlıklara yeni başlıyorsanız, bu yanıtlar tüm süreç hakkında net bir tablo sunar.',
    sections: [
      {
        title: 'E-fatura nedir?',
        content:
          'E-fatura, satıcı, alıcı, kalemler, KDV ve toplamlar hakkında makine tarafından okunabilir veriler içeren, yapılandırılmış dijital formattaki (UBL 2.1 XML) bir faturadır. Nitelikli Elektronik İmza (QES) ile imzalanır ve yasal olarak kağıt faturayla eşdeğerdir. Kağıt veya taranmış faturanın aksine, e-fatura muhasebe ve vergi sistemleri tarafından otomatik olarak işlenebilir.',
        items: null,
        steps: null,
      },
      {
        title: 'E-postayla gönderilen bir PDF e-fatura mıdır?',
        content:
          'Hayır. E-postaya eklenmiş bir PDF belgesi, profesyonel görünse ve tüm gerekli verileri içerse bile e-fatura DEĞİLDİR. E-fatura, makine tarafından okunabilen yapılandırılmış UBL 2.1 XML formatında olmalıdır — PDF, otomatik işleme için değil, insan okuması için tasarlanmış bir görüntüdür. UBL XML yerine PDF göndermek yasal yükümlülüğü karşılamaz.',
        items: null,
        steps: null,
      },
      {
        title: 'Ne zaman zorunlu oluyor?',
        content:
          'Uygulama aşamalıdır. Ekim 2026\'dan itibaren e-fatura, B2G işlemleri (devlet kurumları ve bütçe kullanıcılarına) için zorunlu hale gelir. Ocak 2027\'den (planlanan) itibaren zorunluluk, yıllık cirosu 8.000.000 MKD üzerinde olan büyük B2B mükelleflerine, Temmuz 2027\'den (planlanan) itibaren ise tüm KDV mükelleflerine genişler. Gönüllü kullanım şimdi UJP platformu üzerinden mevcuttur.',
        items: null,
        steps: null,
      },
      {
        title: 'Kim e-fatura düzenlemeli?',
        content:
          'İlk etkilenenler, devlet kurumlarına, kamu işletmelerine ve bütçe kullanıcılarına fatura kesen tüm şirketlerdir (B2G) — Ekim 2026\'dan itibaren. Ardından, 2027 boyunca aşamalı olarak, zorunluluk B2B işlemleri için tüm KDV mükelleflerini kapsar; önce ciro büyük olanlardan başlar. MK KDV kaydı olup Makedonya kuruluşlarına fatura kesen yabancı şirketler de etkilenir.',
        items: null,
        steps: null,
      },
      {
        title: 'QES nedir ve neden gereklidir?',
        content:
          'QES (Nitelikli Elektronik İmza), elektronik imzanın en üst düzeyidir ve yasal olarak el yazısı imzayla eşdeğerdir. E-faturanın imzalandıktan sonra değiştirilmediğini garanti eder ve düzenleyeni açık bir şekilde tanımlar. Her e-faturanın yasal olarak geçerli olması ve UJP platformu tarafından kabul edilmesi için QES ile imzalanması gerekir.',
        items: null,
        steps: null,
      },
      {
        title: 'QES\'i nereden edinirim?',
        content:
          'QES sertifikasını Kibriton (kibriton.mk) veya KIBS gibi yetkili bir nitelikli sertifika sağlayıcısından edinebilirsiniz. Sertifika bir USB token üzerinde veya bulut tabanlı bir çözüm olarak gelir. Yıllık maliyet, sağlayıcıya ve sertifika türüne bağlı olarak genellikle 2.000 ile 5.000 MKD arasında değişir.',
        items: null,
        steps: null,
      },
      {
        title: 'UBL 2.1 nedir?',
        content:
          'UBL 2.1 (Universal Business Language), faturalar dahil olmak üzere XML formatındaki yapılandırılmış iş belgeleri için uluslararası açık bir standarttır. Kuzey Makedonya bunu e-fatura formatı olarak benimsedi. UBL, verilerin tam düzenini tanımlar — satıcı, alıcı, kalemler, miktarlar, birim fiyatlar, orana göre KDV — böylece uyumlu her sistem faturayı otomatik olarak okuyabilir ve işleyebilir.',
        items: null,
        steps: null,
      },
      {
        title: 'UJP platformuna nasıl kaydolurum?',
        content:
          'Kayıt, efaktura.ujp.gov.mk platformunda yapılır. Şirketinizi kaydetmek için EDB\'nize (vergi numarası) ve geçerli bir QES sertifikasına ihtiyacınız vardır. Kayıttan sonra, üretim kullanımına geçmeden önce yasal sonuç olmadan deneme e-faturaları gönderebileceğiniz bir sandbox (test) ortamına erişiminiz olur.',
        items: null,
        steps: null,
      },
      {
        title: 'E-fatura ne kadara mal olur?',
        content:
          'Ana maliyet QES sertifikasıdır — genellikle imza başına yılda 2.000 ile 5.000 MKD. E-fatura göndermek için UJP platformunun kendisinde kullanıcılar için doğrudan bir ücret yoktur. Ek bir maliyet, UBL 2.1 XML üreten muhasebe veya faturalama yazılımı olabilir — Facturino bunu ayrı bir modüle gerek kalmadan doğal olarak destekler.',
        items: null,
        steps: null,
      },
      {
        title: 'E-faturaları ne kadar süreyle saklamalıyım?',
        content:
          'E-faturalar, orijinal UBL 2.1 XML formatında (PDF veya basılı kopya olarak değil) en az 10 yıl saklanmalıdır. Arşiv, vergi denetimi amacıyla tüm süre boyunca okunabilir ve erişilebilir kalmalıdır. Facturino, düzenlenen ve alınan e-faturaları orijinal formatlarında otomatik olarak arşivler.',
        items: null,
        steps: null,
      },
      {
        title: 'Uyumsuzluk cezaları nelerdir?',
        content:
          'Zorunlu olduğunda e-fatura yerine kağıt fatura düzenleyen bir tüzel kişi için ceza 500 ile 3.000 EUR arasında, şirketteki sorumlu kişi için ise 100 ile 500 EUR arasında değişir. Ayrıca, bütçe alıcıları elektronik olmayan faturaları reddedebilir ve uyumsuz şirketler kamu ihalelerinden çıkarılma riskiyle karşı karşıya kalır.',
        items: null,
        steps: null,
      },
      {
        title: 'E-faturayı şimdi gönüllü olarak kullanabilir miyim?',
        content:
          'Evet. E-faturanın gönüllü kullanımı, zorunlu son tarihler yürürlüğe girmeden önce UJP platformu üzerinden şimdi mevcuttur. Erken geçiş, süreçlerinizi test etmek, personelinizi eğitmek ve son tarihlerden önceki baskıdan kaçınmak için size zaman tanır. Erken başlayan şirketler genellikle daha yumuşak bir geçiş yaşar.',
        items: null,
        steps: null,
      },
      {
        title: 'Facturino e-faturayı destekliyor mu?',
        content:
          'Evet. Facturino, ayrı yükseltmelere veya eklentilere gerek kalmadan e-faturaları UBL 2.1 XML formatında oluşturmayı ve QES ile imzalamayı doğal olarak destekler. Faturalar, KDV Kanunu Madde 53\'e göre tüm zorunlu alanları, fatura başına benzersiz bir tanımlayıcı (UUID) ile içerir ve orijinal formatlarında arşivlenir.',
        items: null,
        steps: null,
      },
      {
        title: 'B2G ve B2B zorunluluğu arasındaki fark nedir?',
        content:
          'B2G (işletmeden-devlete), devlet kurumlarına ve bütçe kullanıcılarına fatura kesmeyi ifade eder — bu yükümlülük ilk olarak Ekim 2026\'da gelir. B2B (işletmeden-işletmeye), özel şirketler arasında fatura kesmeyi ifade eder ve 2027 boyunca aşamalı olarak, daha büyük mükelleflerden başlayarak uygulanır. Başka bir deyişle, önce devlete kesilen faturaları, sonra da diğer şirketlere kesilenleri dijitalleştirmelisiniz.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-Fatura İçin Tam Rehber' },
      { slug: 'kako-da-izdadete', title: 'E-Fatura Nasıl Düzenlenir' },
      { slug: 'qes-potpis', title: 'QES İmzası' },
      { slug: 'rokovi-2026', title: 'Son Tarihler 2026/2027' },
    ],
    bottomCta: {
      title: 'E-faturaya hazır mısınız?',
      subtitle: 'Facturino UBL 2.1 ve QES imzasını destekler — son tarihten önce hazır olun.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaFaqPage({
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
    slug: 'casti-prasanja',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'FAQ', 'UBL', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/casti-prasanja` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Што е е-фактура?', answer: 'Е-фактура е фактура во структуриран дигитален формат (UBL 2.1 XML) со машински читливи податоци, потпишана со квалификуван електронски потпис (QES) и правно еквивалентна на хартиена фактура.' },
        { question: 'Дали PDF испратен по email е е-фактура?', answer: 'Не. PDF приложен на email не е е-фактура. Е-фактурата мора да биде во структуриран UBL 2.1 XML формат кој е машински читлив — PDF е слика за човечко читање, не за автоматска обработка.' },
        { question: 'Кога станува задолжителна е-фактурата?', answer: 'Од октомври 2026 за B2G трансакции, од јануари 2027 (планирано) за големи B2B обврзници со промет над 8.000.000 МКД, а од јули 2027 (планирано) за сите ДДВ обврзници. Доброволната употреба е достапна веднаш.' },
        { question: 'Кој мора да издава е-фактура?', answer: 'Прво компаниите кои фактурираат кон државни институции и буџетски корисници (B2G) од октомври 2026, потоа фазно во 2027 сите ДДВ обврзници за B2B трансакции, вклучувајќи странски компании со ДДВ регистрација во Македонија.' },
        { question: 'Што е QES и зошто е потребен?', answer: 'QES е квалификуван електронски потпис — највисокото ниво на електронски потпис, правно еквивалентно на своерачен потпис. Гарантира дека е-фактурата не е изменета и го идентификува издавачот. Секоја е-фактура мора да биде потпишана со QES.' },
        { question: 'Каде да набавам QES?', answer: 'QES сертификат се набавува од овластен издавач како Кибритон (kibriton.mk) или КИБС, на USB токен или cloud-based. Годишната цена вообичаено е од 2.000 до 5.000 МКД.' },
        { question: 'Што е UBL 2.1?', answer: 'UBL 2.1 (Universal Business Language) е меѓународен отворен XML стандард за структурирани фактури кој Македонија го усвои за е-фактурирање. Го дефинира точниот распоред на податоците за автоматска обработка.' },
        { question: 'Како да се регистрирам на УЈП платформата?', answer: 'Регистрацијата се врши на efaktura.ujp.gov.mk. Ви требаат ЕДБ и валиден QES сертификат. По регистрацијата имате пристап до sandbox тест околина за пробни е-фактури без правни последици.' },
        { question: 'Колку чини е-фактурирањето?', answer: 'Главниот трошок е QES сертификатот — вообичаено 2.000 до 5.000 МКД годишно. Платформата на УЈП нема директна такса за корисниците. Дополнителен трошок може да биде софтвер кој генерира UBL 2.1 XML — Facturino ова го поддржува нативно.' },
        { question: 'Колку долго морам да ги чувам е-фактурите?', answer: 'Минимум 10 години, во оригиналниот UBL 2.1 XML формат (не како PDF или печатена копија). Архивата мора да остане читлива и достапна за целиот период за потреби на даночна инспекција.' },
        { question: 'Кои се казните за неусогласеност?', answer: 'За правно лице глоба од EUR 500 до 3.000, а за одговорно лице од EUR 100 до 500. Дополнително, буџетските корисници може да ги одбијат неелектронските фактури, а неусогласените компании ризикуваат исклучување од јавни набавки.' },
        { question: 'Дали можам да користам е-фактура доброволно сега?', answer: 'Да. Доброволната употреба е достапна веднаш преку платформата на УЈП, пред задолжителните рокови. Раното преминување дава време за тестирање на процесите и обука на персоналот.' },
        { question: 'Дали Facturino поддржува е-фактура?', answer: 'Да. Facturino нативно поддржува генерирање на е-фактури во UBL 2.1 XML формат и потпишување со QES, со сите задолжителни полиња по Чл. 53 ЗДДВ, уникатен идентификатор (UUID) по фактура и архивирање во оригинален формат.' },
        { question: 'Која е разликата помеѓу B2G и B2B обврската?', answer: 'B2G (business-to-government) е фактурирање кон државни институции и буџетски корисници и стапува прва, во октомври 2026. B2B (business-to-business) е фактурирање помеѓу приватни компании и се воведува фазно во 2027, почнувајќи од поголемите обврзници.' },
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
