import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/vodic', {
    title: {
      mk: 'Е-фактура во Македонија: целосен водич 2026',
      en: 'E-Invoicing in North Macedonia: Complete 2026 Guide',
      sq: 'E-fatura në Maqedoni: udhëzuesi i plotë 2026',
      tr: 'Kuzey Makedonya\'da E-Fatura: Eksiksiz 2026 Rehberi',
    },
    description: {
      mk: 'Целосен водич за е-фактура во Македонија 2026: што е UBL 2.1, зошто станува задолжителна, клучни датуми (B2G октомври 2026), QES потпис, регистрација во УЈП, предности и како Facturino го олеснува е-фактурирањето.',
      en: 'Complete guide to e-invoicing in North Macedonia 2026: what UBL 2.1 is, why it becomes mandatory, key dates (B2G October 2026), QES signing, UJP registration, benefits and how Facturino simplifies e-invoicing.',
      sq: 'Udhëzues i plotë për e-faturën në Maqedoni 2026: çfarë është UBL 2.1, pse bëhet e detyrueshme, datat kyçe (B2G tetor 2026), nënshkrimi QES, regjistrimi në DAP, përparësitë dhe si Facturino e lehtëson e-faturimin.',
      tr: 'Kuzey Makedonya e-fatura eksiksiz rehberi 2026: UBL 2.1 nedir, neden zorunlu oluyor, önemli tarihler (B2G Ekim 2026), QES imzası, UJP kaydı, avantajlar ve Facturino e-faturayı nasıl kolaylaştırır.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'Водич',
    title: 'Е-фактура во Македонија: целосен водич 2026',
    publishDate: '18 август 2026',
    readTime: '11 мин читање',
    intro:
      'Електронското фактурирање во Македонија повеќе не е иднина — тоа е закон што влегува во сила. Од октомври 2026 сите добавувачи на државни институции мораат да издаваат е-фактури, а во текот на 2027 обврската фазно се проширува на сите ДДВ обврзници. Овој целосен водич ги објаснува основите: што е е-фактура, зошто државата ја воведува, кои се клучните датуми, како функционира процесот, што ви треба за да почнете и кои се предностите. Ова е појдовна точка — за подетални теми следете ги поврзаните написи на крајот.',
    sections: [
      {
        title: 'Што е е-фактура?',
        content:
          'Е-фактура е фактура во структуриран дигитален формат — конкретно UBL 2.1 XML (Universal Business Language). Ова е клучната разлика која многумина ја пропуштаат: скенирана слика или PDF приложен на email НЕ е е-фактура. Вистинската е-фактура е машински читлив документ во кој секое поле (продавач, купувач, ставки, количини, единечни цени, ДДВ по стапки, вкупни износи) е структурирано така што компјутерските системи можат автоматски да го обработат без човечка интервенција. Е-фактурата е правно целосно еквивалентна на хартиената фактура и мора да биде потпишана со квалификуван електронски потпис (QES), кој гарантира автентичност и интегритет на документот.',
        items: null,
        steps: null,
      },
      {
        title: 'Зошто станува задолжителна?',
        content:
          'Правната основа за е-фактурирањето произлегува од Законот за ДДВ (ЗДДВ) и придружните подзаконски акти кои ги дефинираат условите за издавање фактури во електронски облик. Државата го воведува е-фактурирањето за да ги оствари следните цели:',
        items: [
          'Намалување на даночната евазија — структурираните податоци се тешки за манипулација и лесни за вкрстена проверка од страна на УЈП',
          'Забрзување на плаќањата во јавниот сектор — автоматизираната обработка ги скратува роковите за исплата',
          'Усогласување со ЕУ практиките — електронското фактурирање е стандард во Европската Унија и услов за интеграциските процеси',
          'Намалување на административните трошоци — помалку хартија, помалку рачно внесување, помалку грешки',
          'Подобра транспарентност и следливост на трансакциите во целиот стопански систем',
        ],
        steps: null,
      },
      {
        title: 'Клучни датуми',
        content:
          'Имплементацијата на е-фактура во Македонија е фазна. Еве ги главните пресвртници кои треба да ги имате на ум:',
        items: null,
        steps: [
          { step: 'Веднаш — Доброволна употреба', desc: 'Доброволното е-фактурирање е достапно ВЕДНАШ преку платформата на УЈП. Компаниите можат да почнат без да чекаат рок и да ги отстранат сите проблеми пред да стане задолжително.' },
          { step: 'Октомври 2026 — B2G задолжителна', desc: 'Сите добавувачи кои фактурираат кон државни институции, јавни претпријатија и буџетски корисници мораат да испраќаат е-фактури преку платформата на УЈП.' },
          { step: 'Јануари 2027 (планирано) — Големи B2B', desc: 'ДДВ обврзници со годишен промет над 8.000.000 МКД мораат да издаваат е-фактури за сите B2B трансакции.' },
          { step: 'Јули 2027 (планирано) — Сите ДДВ обврзници', desc: 'Задолжителност за сите ДДВ обврзници без оглед на промет — целосен премин кон електронско фактурирање.' },
        ],
      },
      {
        title: 'Како функционира е-фактурирањето?',
        content:
          'Иако технологијата зад е-фактурата е сложена, работниот тек за корисникот е логичен и предвидлив. Секоја е-фактура минува низ следните фази:',
        items: [
          'Креирање — ги внесувате податоците за фактурата (купувач, ставки, количини, цени, ДДВ) во вашиот софтвер, исто како досега',
          'Генерирање UBL 2.1 — софтверот автоматски ги претвора податоците во структуриран UBL 2.1 XML документ со уникатен идентификатор (UUID)',
          'QES потпис — документот се потпишува со квалификуван електронски потпис кој гарантира дека фактурата е автентична и неизменета',
          'Поднесување преку УЈП — потпишаната е-фактура се испраќа до примачот преку платформата efaktura.ujp.gov.mk или преку API интеграција',
          'Архивирање — оригиналниот UBL XML се чува минимум 10 години во непроменет формат за евентуални даночни контроли',
        ],
        steps: null,
      },
      {
        title: 'Што ви треба за да почнете',
        content:
          'Влезот во светот на е-фактурирањето бара неколку конкретни чекори. Со добра подготовка целиот процес трае неколку дена:',
        items: null,
        steps: [
          { step: 'Софтвер способен за UBL 2.1', desc: 'Не секој сметководствен софтвер генерира валиден UBL 2.1 XML. Проверете дали вашиот го поддржува или побарајте надградба. Facturino поддржува UBL 2.1 експорт нативно, без дополнителни алатки.' },
          { step: 'QES сертификат (квалификуван потпис)', desc: 'Набавете квалификуван електронски потпис од овластен издавач — Кибритон (kibriton.mk) или КИБС (KIBS). Цената е околу 2.000-5.000 МКД годишно, а сертификатот е достапен на USB токен или како cloud решение.' },
          { step: 'Регистрација во УЈП', desc: 'Посетете efaktura.ujp.gov.mk и регистрирајте ја вашата компанија. За регистрација ви треба ЕДБ (даночен број) и вашиот QES сертификат.' },
          { step: 'Тестирање во sandbox околина', desc: 'УЈП нуди тест (sandbox) околина каде можете да испраќате пробни е-фактури без правни последици. Искористете ја за да ги отстраните грешките пред продукција.' },
          { step: 'Обука на персоналот', desc: 'Запознајте ги вработените со новиот работен тек: креирај → потпиши со QES → испрати преку УЈП → архивирај. Сите вклучени лица треба да го разберат процесот.' },
        ],
      },
      {
        title: 'Предности на е-фактурирањето',
        content:
          'Освен што е законска обврска, е-фактурирањето носи вистински деловни придобивки кои често се потценуваат:',
        items: [
          'Побрзи плаќања — автоматизираната обработка кај примачот значи побрза наплата на вашите фактури',
          'Помалку грешки — структурираните податоци елиминираат грешки од рачно препишување и повторно внесување',
          'Пониски трошоци — нема печатење, коверти, поштарина или физичко архивирање',
          'Сигурна архива — 10-годишно електронско чување без ризик од оштетени или изгубени документи',
          'Правна сигурност — QES потписот дава неспорен доказ за автентичноста и интегритетот на фактурата',
          'Подготвеност за иднината — компаниите кои преминуваат сега имаат конкурентска предност пред роковите',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го олеснува е-фактурирањето',
        content:
          'Facturino е дизајниран за македонскиот пазар и целосно ги поддржува барањата за е-фактура. Наместо да купувате посебни алатки за секој чекор, добивате интегрирано решение: креирате фактура како обично, а Facturino автоматски генерира валиден UBL 2.1 XML со сите задолжителни полиња по Чл. 53 ЗДДВ и уникатен UUID. QES потпишувањето е вградено — не ви треба надворешен потписник. Резултатот е усогласена е-фактура спремна за поднесување преку УЈП, со автоматска 10-годишна архива. Почнете доброволно денес и бидете целосно спремни пред октомври 2026.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'rokovi-2026', title: 'Рокови за е-фактура 2026/2027' },
      { slug: 'ubl-format', title: 'UBL 2.1 формат' },
      { slug: 'qes-potpis', title: 'QES потпис' },
      { slug: 'za-javni-nabavki', title: 'Е-фактура за јавни набавки' },
      { slug: 'casuvanje-i-arhiva', title: 'Чување и е-архива' },
      { slug: 'casti-prasanja', title: 'Често поставувани прашања' },
    ],
    bottomCta: {
      title: 'Подготвени за е-фактура?',
      subtitle: 'Facturino поддржува UBL 2.1 и QES потпис — почнете бесплатно и бидете спремни пред сите.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'Guide',
    title: 'E-Invoicing in North Macedonia: Complete 2026 Guide',
    publishDate: 'August 18, 2026',
    readTime: '11 min read',
    intro:
      'Electronic invoicing in North Macedonia is no longer the future — it is a law coming into force. From October 2026, all suppliers to government institutions must issue e-invoices, and throughout 2027 the mandate phases in for all VAT-registered companies. This complete guide covers the fundamentals: what an e-invoice is, why the state is introducing it, the key dates, how the process works, what you need to get started, and the benefits. This is your starting point — for deeper topics follow the related articles at the end.',
    sections: [
      {
        title: 'What is an e-invoice?',
        content:
          'An e-invoice is an invoice in a structured digital format — specifically UBL 2.1 XML (Universal Business Language). This is the crucial distinction many people miss: a scanned image or a PDF attached to an email is NOT an e-invoice. A true e-invoice is a machine-readable document where every field (seller, buyer, line items, quantities, unit prices, VAT by rate, totals) is structured so computer systems can process it automatically without human intervention. The e-invoice is legally fully equivalent to a paper invoice and must be signed with a Qualified Electronic Signature (QES), which guarantees the document\'s authenticity and integrity.',
        items: null,
        steps: null,
      },
      {
        title: 'Why is it becoming mandatory?',
        content:
          'The legal basis for e-invoicing stems from the VAT Law (ZDDV) and accompanying bylaws that define the conditions for issuing invoices in electronic form. The state is introducing e-invoicing to achieve the following goals:',
        items: [
          'Reducing tax evasion — structured data is hard to manipulate and easy for the tax authority (UJP) to cross-check',
          'Speeding up public-sector payments — automated processing shortens disbursement timelines',
          'Aligning with EU practices — e-invoicing is the standard in the European Union and a condition for integration processes',
          'Cutting administrative costs — less paper, less manual entry, fewer errors',
          'Better transparency and traceability of transactions across the entire economy',
        ],
        steps: null,
      },
      {
        title: 'Key dates',
        content:
          'E-invoicing implementation in North Macedonia is phased. Here are the main milestones to keep in mind:',
        items: null,
        steps: [
          { step: 'Now — Voluntary adoption', desc: 'Voluntary e-invoicing is available NOW through the UJP platform. Companies can start without waiting for the deadline and iron out any issues before it becomes mandatory.' },
          { step: 'October 2026 — B2G mandatory', desc: 'All suppliers invoicing government institutions, public enterprises, and budget users must send e-invoices via the UJP platform.' },
          { step: 'January 2027 (planned) — Large B2B', desc: 'VAT-registered companies with annual turnover above 8,000,000 MKD must issue e-invoices for all B2B transactions.' },
          { step: 'July 2027 (planned) — All VAT payers', desc: 'Mandatory for all VAT-registered companies regardless of turnover — a full transition to electronic invoicing.' },
        ],
      },
      {
        title: 'How does e-invoicing work?',
        content:
          'Although the technology behind e-invoicing is complex, the workflow for the user is logical and predictable. Every e-invoice goes through the following stages:',
        items: [
          'Creation — you enter the invoice data (buyer, line items, quantities, prices, VAT) in your software, just like before',
          'UBL 2.1 generation — the software automatically converts the data into a structured UBL 2.1 XML document with a unique identifier (UUID)',
          'QES signing — the document is signed with a Qualified Electronic Signature that guarantees the invoice is authentic and unaltered',
          'Submission via UJP — the signed e-invoice is sent to the recipient through the efaktura.ujp.gov.mk platform or via API integration',
          'Archiving — the original UBL XML is stored for a minimum of 10 years in unaltered format for potential tax audits',
        ],
        steps: null,
      },
      {
        title: 'What you need to get started',
        content:
          'Entering the world of e-invoicing requires a few concrete steps. With good preparation the whole process takes a few days:',
        items: null,
        steps: [
          { step: 'UBL 2.1-capable software', desc: 'Not every accounting software generates valid UBL 2.1 XML. Check whether yours supports it or request an upgrade. Facturino supports UBL 2.1 export natively, with no extra tools.' },
          { step: 'QES certificate (qualified signature)', desc: 'Obtain a Qualified Electronic Signature from an authorized issuer — Kibriton (kibriton.mk) or KIBS. The cost is around 2,000-5,000 MKD annually, and the certificate comes on a USB token or as a cloud solution.' },
          { step: 'UJP registration', desc: 'Visit efaktura.ujp.gov.mk and register your company. Registration requires your EDB (tax number) and your QES certificate.' },
          { step: 'Testing in the sandbox environment', desc: 'UJP offers a test (sandbox) environment where you can send trial e-invoices without legal consequences. Use it to eliminate errors before going into production.' },
          { step: 'Staff training', desc: 'Familiarize employees with the new workflow: create → sign with QES → send via UJP → archive. Everyone involved should understand the process.' },
        ],
      },
      {
        title: 'Benefits of e-invoicing',
        content:
          'Beyond being a legal obligation, e-invoicing brings real business benefits that are often underestimated:',
        items: [
          'Faster payments — automated processing at the recipient means faster collection of your invoices',
          'Fewer errors — structured data eliminates mistakes from manual re-typing and re-entry',
          'Lower costs — no printing, envelopes, postage, or physical archiving',
          'Secure archive — 10-year electronic storage with no risk of damaged or lost documents',
          'Legal certainty — the QES signature provides indisputable proof of the invoice\'s authenticity and integrity',
          'Future readiness — companies that switch now gain a competitive edge ahead of the deadlines',
        ],
        steps: null,
      },
      {
        title: 'How Facturino simplifies e-invoicing',
        content:
          'Facturino is designed for the Macedonian market and fully supports the e-invoice requirements. Instead of buying separate tools for each step, you get an integrated solution: you create an invoice as usual, and Facturino automatically generates valid UBL 2.1 XML with all mandatory fields per Art. 53 VAT Law and a unique UUID. QES signing is built in — you don\'t need an external signer. The result is a compliant e-invoice ready for submission via UJP, with automatic 10-year archiving. Start voluntarily today and be fully ready before October 2026.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'rokovi-2026', title: 'E-Invoice Deadlines 2026/2027' },
      { slug: 'ubl-format', title: 'UBL 2.1 Format' },
      { slug: 'qes-potpis', title: 'QES Signature' },
      { slug: 'za-javni-nabavki', title: 'E-Invoice for Public Procurement' },
      { slug: 'casuvanje-i-arhiva', title: 'Storage and E-Archive' },
      { slug: 'casti-prasanja', title: 'Frequently Asked Questions' },
    ],
    bottomCta: {
      title: 'Ready for e-invoicing?',
      subtitle: 'Facturino supports UBL 2.1 and QES signing — start free and be prepared before everyone else.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Udhëzues',
    title: 'E-fatura në Maqedoni: udhëzuesi i plotë 2026',
    publishDate: '18 gusht 2026',
    readTime: '11 min lexim',
    intro:
      'Faturimi elektronik në Maqedoni nuk është më e ardhmja — është një ligj që po hyn në fuqi. Nga tetori 2026, të gjithë furnizuesit e institucioneve shtetërore duhet të lëshojnë e-fatura, dhe gjatë vitit 2027 detyrimi zbatohet me faza për të gjithë tatimpaguesit e TVSH-së. Ky udhëzues i plotë mbulon bazat: çfarë është e-fatura, pse shteti po e prezanton, datat kyçe, si funksionon procesi, çfarë ju nevojitet për të filluar dhe përparësitë. Ky është pikënisja juaj — për tema më të thelluara ndiqni artikujt e lidhur në fund.',
    sections: [
      {
        title: 'Çfarë është e-fatura?',
        content:
          'E-fatura është një faturë në format dixhital të strukturuar — konkretisht UBL 2.1 XML (Universal Business Language). Ky është dallimi vendimtar që shumë njerëz e humbasin: një imazh i skanuar ose një PDF i bashkëngjitur në email NUK është e-faturë. Një e-faturë e vërtetë është një dokument i lexueshëm nga makina ku çdo fushë (shitësi, blerësi, artikujt, sasitë, çmimet për njësi, TVSH-ja sipas normës, totalet) është e strukturuar në mënyrë që sistemet kompjuterike ta përpunojnë automatikisht pa ndërhyrje njerëzore. E-fatura është ligjërisht plotësisht ekuivalente me faturën në letër dhe duhet të nënshkruhet me Nënshkrim Elektronik të Kualifikuar (QES), i cili garanton autenticitetin dhe integritetin e dokumentit.',
        items: null,
        steps: null,
      },
      {
        title: 'Pse po bëhet e detyrueshme?',
        content:
          'Baza ligjore për e-faturimin rrjedh nga Ligji i TVSH-së (ZDDV) dhe aktet nënligjore shoqëruese që përcaktojnë kushtet për lëshimin e faturave në formë elektronike. Shteti po prezanton e-faturimin për të arritur qëllimet e mëposhtme:',
        items: [
          'Reduktimin e evazionit fiskal — të dhënat e strukturuara janë të vështira për t\'u manipuluar dhe të lehta për t\'u kontrolluar nga administrata tatimore (DAP)',
          'Përshpejtimin e pagesave në sektorin publik — përpunimi i automatizuar shkurton afatet e pagesës',
          'Përafrimin me praktikat e BE-së — e-faturimi është standard në Bashkimin Evropian dhe kusht për proceset integruese',
          'Uljen e kostove administrative — më pak letër, më pak futje manuale, më pak gabime',
          'Transparencë dhe gjurmueshmëri më të mirë të transaksioneve në të gjithë ekonominë',
        ],
        steps: null,
      },
      {
        title: 'Datat kyçe',
        content:
          'Zbatimi i e-faturës në Maqedoni është fazor. Ja pikat kryesore që duhet të keni parasysh:',
        items: null,
        steps: [
          { step: 'Tani — Adoptim vullnetar', desc: 'E-faturimi vullnetar është i disponueshëm TANI përmes platformës DAP. Kompanitë mund të fillojnë pa pritur afatin dhe të zgjidhin çdo problem para se të bëhet e detyrueshme.' },
          { step: 'Tetor 2026 — B2G e detyrueshme', desc: 'Të gjithë furnizuesit që faturojnë institucionet shtetërore, ndërmarrjet publike dhe përdoruesit buxhetorë duhet të dërgojnë e-fatura përmes platformës DAP.' },
          { step: 'Janar 2027 (planifikuar) — B2B të mëdha', desc: 'Tatimpaguesit e TVSH-së me qarkullim vjetor mbi 8.000.000 MKD duhet të lëshojnë e-fatura për të gjitha transaksionet B2B.' },
          { step: 'Korrik 2027 (planifikuar) — Të gjithë tatimpaguesit e TVSH-së', desc: 'E detyrueshme për të gjithë tatimpaguesit e TVSH-së pavarësisht qarkullimit — kalim i plotë në faturimin elektronik.' },
        ],
      },
      {
        title: 'Si funksionon e-faturimi?',
        content:
          'Megjithëse teknologjia pas e-faturës është komplekse, rrjedha e punës për përdoruesin është logjike dhe e parashikueshme. Çdo e-faturë kalon nëpër fazat e mëposhtme:',
        items: [
          'Krijimi — futni të dhënat e faturës (blerësi, artikujt, sasitë, çmimet, TVSH-ja) në softuerin tuaj, ashtu si më parë',
          'Gjenerimi UBL 2.1 — softueri i konverton automatikisht të dhënat në një dokument të strukturuar UBL 2.1 XML me identifikues unik (UUID)',
          'Nënshkrimi QES — dokumenti nënshkruhet me Nënshkrim Elektronik të Kualifikuar që garanton se fatura është autentike dhe e pandryshuar',
          'Dorëzimi përmes DAP — e-fatura e nënshkruar dërgohet te marrësi përmes platformës efaktura.ujp.gov.mk ose përmes integrimit API',
          'Arkivimi — UBL XML origjinal ruhet minimum 10 vjet në format të pandryshuar për kontrolle të mundshme tatimore',
        ],
        steps: null,
      },
      {
        title: 'Çfarë ju nevojitet për të filluar',
        content:
          'Hyrja në botën e e-faturimit kërkon disa hapa konkretë. Me përgatitje të mirë, i gjithë procesi zgjat disa ditë:',
        items: null,
        steps: [
          { step: 'Softuer i aftë për UBL 2.1', desc: 'Jo çdo softuer kontabiliteti gjeneron UBL 2.1 XML të vlefshëm. Kontrolloni nëse i juaji e mbështet ose kërkoni përditësim. Facturino mbështet eksportin UBL 2.1 në mënyrë vendase, pa mjete shtesë.' },
          { step: 'Certifikatë QES (nënshkrim i kualifikuar)', desc: 'Merrni një Nënshkrim Elektronik të Kualifikuar nga një lëshues i autorizuar — Kibriton (kibriton.mk) ose KIBS. Kostoja është rreth 2.000-5.000 MKD në vit, dhe certifikata vjen në token USB ose si zgjidhje cloud.' },
          { step: 'Regjistrimi në DAP', desc: 'Vizitoni efaktura.ujp.gov.mk dhe regjistroni kompaninë tuaj. Regjistrimi kërkon EDB-në tuaj (numrin tatimor) dhe certifikatën QES.' },
          { step: 'Testimi në mjedisin sandbox', desc: 'DAP ofron një mjedis testimi (sandbox) ku mund të dërgoni e-fatura prove pa pasoja ligjore. Përdoreni për të eliminuar gabimet para prodhimit.' },
          { step: 'Trajnimi i stafit', desc: 'Njihni punonjësit me rrjedhën e re: krijo → nënshkruaj me QES → dërgo përmes DAP → arkivo. Të gjithë të përfshirët duhet ta kuptojnë procesin.' },
        ],
      },
      {
        title: 'Përparësitë e e-faturimit',
        content:
          'Përveçse është detyrim ligjor, e-faturimi sjell përfitime reale të biznesit që shpesh nënvlerësohen:',
        items: [
          'Pagesa më të shpejta — përpunimi i automatizuar te marrësi do të thotë arkëtim më i shpejtë i faturave tuaja',
          'Më pak gabime — të dhënat e strukturuara eliminojnë gabimet nga rishkrimi dhe rifutja manuale',
          'Kosto më të ulëta — pa printim, zarfe, pullë postare ose arkivim fizik',
          'Arkiv i sigurt — ruajtje elektronike 10-vjeçare pa rrezik nga dokumente të dëmtuara ose të humbura',
          'Siguri ligjore — nënshkrimi QES jep provë të pakontestueshme për autenticitetin dhe integritetin e faturës',
          'Gatishmëri për të ardhmen — kompanitë që kalojnë tani fitojnë përparësi konkurruese para afateve',
        ],
        steps: null,
      },
      {
        title: 'Si e lehtëson Facturino e-faturimin',
        content:
          'Facturino është projektuar për tregun maqedonas dhe mbështet plotësisht kërkesat për e-faturë. Në vend që të blini mjete të veçanta për çdo hap, merrni një zgjidhje të integruar: krijoni një faturë si zakonisht, dhe Facturino gjeneron automatikisht UBL 2.1 XML të vlefshëm me të gjitha fushat e detyrueshme sipas Nenit 53 të Ligjit të TVSH-së dhe një UUID unik. Nënshkrimi QES është i integruar — nuk ju nevojitet nënshkrues i jashtëm. Rezultati është një e-faturë në përputhje, gati për dorëzim përmes DAP, me arkivim automatik 10-vjeçar. Filloni vullnetarisht sot dhe jini plotësisht gati para tetorit 2026.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'rokovi-2026', title: 'Afatet e e-faturës 2026/2027' },
      { slug: 'ubl-format', title: 'Formati UBL 2.1' },
      { slug: 'qes-potpis', title: 'Nënshkrimi QES' },
      { slug: 'za-javni-nabavki', title: 'E-fatura për prokurime publike' },
      { slug: 'casuvanje-i-arhiva', title: 'Ruajtja dhe e-arkiva' },
      { slug: 'casti-prasanja', title: 'Pyetjet më të shpeshta' },
    ],
    bottomCta: {
      title: 'Gati për e-faturën?',
      subtitle: 'Facturino mbështet UBL 2.1 dhe nënshkrimin QES — filloni falas dhe jini gati para të tjerëve.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'Rehber',
    title: 'Kuzey Makedonya\'da E-Fatura: Eksiksiz 2026 Rehberi',
    publishDate: '18 Ağustos 2026',
    readTime: '11 dk okuma',
    intro:
      'Kuzey Makedonya\'da elektronik faturalama artık gelecek değil — yürürlüğe giren bir yasadır. Ekim 2026\'dan itibaren devlet kurumlarının tüm tedarikçileri e-fatura düzenlemek zorunda ve 2027 boyunca zorunluluk tüm KDV mükelleflerine aşamalı olarak yayılıyor. Bu eksiksiz rehber temelleri kapsar: e-fatura nedir, devlet neden getiriyor, önemli tarihler, süreç nasıl işler, başlamak için neye ihtiyacınız var ve avantajlar. Bu sizin başlangıç noktanız — daha derin konular için sondaki ilgili yazıları takip edin.',
    sections: [
      {
        title: 'E-fatura nedir?',
        content:
          'E-fatura, yapılandırılmış dijital formatta — özellikle UBL 2.1 XML (Universal Business Language) — bir faturadır. Birçok kişinin gözden kaçırdığı can alıcı fark budur: taranmış bir görüntü veya e-postaya eklenmiş bir PDF e-fatura DEĞİLDİR. Gerçek bir e-fatura, her alanın (satıcı, alıcı, kalemler, miktarlar, birim fiyatlar, orana göre KDV, toplamlar) bilgisayar sistemleri tarafından insan müdahalesi olmadan otomatik işlenebilecek şekilde yapılandırıldığı, makine tarafından okunabilir bir belgedir. E-fatura yasal olarak kağıt faturayla tamamen eşdeğerdir ve belgenin özgünlüğünü ve bütünlüğünü garanti eden Nitelikli Elektronik İmza (QES) ile imzalanmalıdır.',
        items: null,
        steps: null,
      },
      {
        title: 'Neden zorunlu oluyor?',
        content:
          'E-faturalama için yasal dayanak, KDV Kanunu (ZDDV) ve faturaların elektronik biçimde düzenlenme koşullarını tanımlayan ilgili yönetmeliklerden kaynaklanır. Devlet, aşağıdaki hedeflere ulaşmak için e-faturalamayı getiriyor:',
        items: [
          'Vergi kaçakçılığını azaltma — yapılandırılmış verilerin manipüle edilmesi zor, vergi idaresi (UJP) tarafından çapraz kontrolü kolaydır',
          'Kamu sektörü ödemelerini hızlandırma — otomatik işleme ödeme sürelerini kısaltır',
          'AB uygulamalarıyla uyum — e-faturalama Avrupa Birliği\'nde standarttır ve entegrasyon süreçleri için bir koşuldur',
          'İdari maliyetleri düşürme — daha az kağıt, daha az manuel giriş, daha az hata',
          'Ekonominin genelinde işlemlerin daha iyi şeffaflığı ve izlenebilirliği',
        ],
        steps: null,
      },
      {
        title: 'Önemli tarihler',
        content:
          'Kuzey Makedonya\'da e-fatura uygulaması aşamalıdır. Aklınızda tutmanız gereken ana kilometre taşları:',
        items: null,
        steps: [
          { step: 'Şimdi — Gönüllü kullanım', desc: 'Gönüllü e-faturalama UJP platformu üzerinden ŞİMDİ mevcuttur. Şirketler son tarihi beklemeden başlayabilir ve zorunlu hale gelmeden önce sorunları çözebilir.' },
          { step: 'Ekim 2026 — B2G zorunlu', desc: 'Devlet kurumlarına, kamu işletmelerine ve bütçe kullanıcılarına fatura kesen tüm tedarikçiler UJP platformu üzerinden e-fatura göndermek zorunda.' },
          { step: 'Ocak 2027 (planlanan) — Büyük B2B', desc: 'Yıllık cirosu 8.000.000 MKD üzerinde olan KDV mükellefleri tüm B2B işlemler için e-fatura düzenlemeli.' },
          { step: 'Temmuz 2027 (planlanan) — Tüm KDV mükellefleri', desc: 'Ciroya bakılmaksızın tüm KDV mükellefleri için zorunlu — elektronik faturalamaya tam geçiş.' },
        ],
      },
      {
        title: 'E-faturalama nasıl işler?',
        content:
          'E-faturanın arkasındaki teknoloji karmaşık olsa da, kullanıcı için iş akışı mantıklı ve öngörülebilirdir. Her e-fatura aşağıdaki aşamalardan geçer:',
        items: [
          'Oluşturma — fatura verilerini (alıcı, kalemler, miktarlar, fiyatlar, KDV) yazılımınıza eskisi gibi girersiniz',
          'UBL 2.1 üretimi — yazılım verileri otomatik olarak benzersiz bir tanımlayıcıya (UUID) sahip yapılandırılmış UBL 2.1 XML belgesine dönüştürür',
          'QES imzalama — belge, faturanın özgün ve değiştirilmemiş olduğunu garanti eden Nitelikli Elektronik İmza ile imzalanır',
          'UJP üzerinden gönderim — imzalı e-fatura alıcıya efaktura.ujp.gov.mk platformu üzerinden veya API entegrasyonu ile gönderilir',
          'Arşivleme — orijinal UBL XML olası vergi denetimleri için değiştirilmemiş formatta en az 10 yıl saklanır',
        ],
        steps: null,
      },
      {
        title: 'Başlamak için neye ihtiyacınız var',
        content:
          'E-faturalama dünyasına girmek birkaç somut adım gerektirir. İyi bir hazırlıkla tüm süreç birkaç gün sürer:',
        items: null,
        steps: [
          { step: 'UBL 2.1 destekli yazılım', desc: 'Her muhasebe yazılımı geçerli UBL 2.1 XML üretmez. Sizinkinin destekleyip desteklemediğini kontrol edin veya bir yükseltme talep edin. Facturino UBL 2.1 dışa aktarımını ek araç olmadan doğal olarak destekler.' },
          { step: 'QES sertifikası (nitelikli imza)', desc: 'Yetkili bir sağlayıcıdan — Kibriton (kibriton.mk) veya KIBS — Nitelikli Elektronik İmza edinin. Maliyet yıllık yaklaşık 2.000-5.000 MKD\'dir ve sertifika USB token veya bulut çözümü olarak gelir.' },
          { step: 'UJP kaydı', desc: 'efaktura.ujp.gov.mk adresini ziyaret edin ve şirketinizi kaydedin. Kayıt EDB\'nizi (vergi numarası) ve QES sertifikanızı gerektirir.' },
          { step: 'Sandbox ortamında test', desc: 'UJP, yasal sonuç olmadan deneme e-faturaları gönderebileceğiniz bir test (sandbox) ortamı sunar. Üretime geçmeden önce hataları gidermek için kullanın.' },
          { step: 'Personel eğitimi', desc: 'Çalışanları yeni iş akışıyla tanıştırın: oluştur → QES ile imzala → UJP üzerinden gönder → arşivle. İlgili herkes süreci anlamalı.' },
        ],
      },
      {
        title: 'E-faturalamanın avantajları',
        content:
          'Yasal bir yükümlülük olmasının ötesinde, e-faturalama sıklıkla küçümsenen gerçek iş avantajları getirir:',
        items: [
          'Daha hızlı ödemeler — alıcıdaki otomatik işleme faturalarınızın daha hızlı tahsil edilmesi demektir',
          'Daha az hata — yapılandırılmış veriler manuel yeniden yazma ve yeniden girişten kaynaklanan hataları ortadan kaldırır',
          'Daha düşük maliyetler — baskı, zarf, posta ücreti veya fiziksel arşivleme yok',
          'Güvenli arşiv — hasarlı veya kayıp belge riski olmadan 10 yıllık elektronik saklama',
          'Yasal güvence — QES imzası faturanın özgünlüğü ve bütünlüğü için tartışmasız kanıt sağlar',
          'Geleceğe hazırlık — şimdi geçiş yapan şirketler son tarihlerden önce rekabet avantajı kazanır',
        ],
        steps: null,
      },
      {
        title: 'Facturino e-faturalamayı nasıl kolaylaştırır',
        content:
          'Facturino Makedonya pazarı için tasarlanmıştır ve e-fatura gereksinimlerini tam olarak destekler. Her adım için ayrı araçlar satın almak yerine entegre bir çözüm elde edersiniz: bir faturayı her zamanki gibi oluşturursunuz ve Facturino KDV Kanunu Madde 53\'e göre tüm zorunlu alanlar ve benzersiz bir UUID ile geçerli UBL 2.1 XML\'i otomatik olarak üretir. QES imzalama yerleşiktir — harici bir imzalayıcıya ihtiyacınız yoktur. Sonuç, otomatik 10 yıllık arşivleme ile UJP üzerinden gönderime hazır, uyumlu bir e-faturadır. Bugün gönüllü olarak başlayın ve Ekim 2026\'dan önce tamamen hazır olun.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'kako-da-izdadete', title: 'E-fatura nasıl düzenlenir' },
      { slug: 'rokovi-2026', title: 'E-fatura son tarihleri 2026/2027' },
      { slug: 'ubl-format', title: 'UBL 2.1 formatı' },
      { slug: 'qes-potpis', title: 'QES imzası' },
      { slug: 'za-javni-nabavki', title: 'Kamu ihaleleri için e-fatura' },
      { slug: 'casuvanje-i-arhiva', title: 'Saklama ve e-arşiv' },
      { slug: 'casti-prasanja', title: 'Sıkça sorulan sorular' },
    ],
    bottomCta: {
      title: 'E-faturaya hazır mısınız?',
      subtitle: 'Facturino UBL 2.1 ve QES imzasını destekler — ücretsiz başlayın ve herkesten önce hazır olun.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaVodicPage({
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
    slug: 'vodic',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'UBL', 'QES', 'UJP', 'Macedonia', 'водич'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/vodic` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Што е е-фактура?', answer: 'Е-фактура е фактура во структуриран UBL 2.1 XML формат, машински читлива и потпишана со квалификуван електронски потпис (QES). PDF или скенирана слика НЕ е е-фактура.' },
        { question: 'Кога станува задолжителна е-фактурата?', answer: 'Задолжителна е за B2G трансакции од октомври 2026. Планирано за големи B2B обврзници (промет над 8.000.000 МКД) од јануари 2027 и за сите ДДВ обврзници од јули 2027.' },
        { question: 'Што ми треба за да почнам со е-фактурирање?', answer: 'Потребен ви е софтвер способен за UBL 2.1, QES сертификат од овластен издавач (Кибритон или КИБС) и регистрација на платформата efaktura.ujp.gov.mk со ЕДБ.' },
        { question: 'Колку долго треба да се чуваат е-фактурите?', answer: 'Е-фактурите мора да се архивираат минимум 10 години во оригинален UBL XML формат за евентуални даночни контроли.' },
        { question: 'Колку чини QES сертификат?', answer: 'Квалификуван електронски потпис (QES) чини околу 2.000-5.000 МКД годишно и се набавува од Кибритон (kibriton.mk) или КИБС, на USB токен или како cloud решение.' },
        { question: 'Дали Facturino поддржува е-фактура?', answer: 'Да. Facturino нативно генерира валиден UBL 2.1 XML со сите задолжителни полиња по Чл. 53 ЗДДВ, вградено QES потпишување и автоматска 10-годишна архива.' },
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
