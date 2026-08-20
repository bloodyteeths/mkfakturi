import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/za-javni-nabavki', {
    title: {
      mk: 'Е-фактура за јавни набавки (B2G): Обврска од октомври 2026',
      en: 'E-Invoicing for Public Procurement (B2G): Mandatory from October 2026',
      sq: 'E-fatura për prokurime publike (B2G): E detyrueshme nga tetori 2026',
      tr: 'Kamu İhaleleri İçin E-Fatura (B2G): Ekim 2026\'dan Zorunlu',
    },
    description: {
      mk: 'B2G е-фактурирање во Македонија: обврска за добавувачите на државни институции, јавни претпријатија и буџетски корисници од октомври 2026 преку УЈП. Врска со ЕСЈН, чекори за фактурирање и совети за добавувачите.',
      en: 'B2G e-invoicing in North Macedonia: mandatory for suppliers to state institutions, public enterprises and budget users from October 2026 via UJP. Link to electronic public procurement, invoicing steps and supplier tips.',
      sq: 'Faturimi B2G në Maqedoni: i detyrueshëm për furnizuesit e institucioneve shtetërore, ndërmarrjeve publike dhe përdoruesve buxhetorë nga tetori 2026 përmes DAP. Lidhja me prokurimin elektronik dhe këshilla për furnizuesit.',
      tr: 'Kuzey Makedonya\'da B2G e-faturalama: Ekim 2026\'dan itibaren devlet kurumları, kamu işletmeleri ve bütçe kullanıcılarının tedarikçileri için UJP üzerinden zorunlu. Elektronik kamu ihalesi bağlantısı ve tedarikçi ipuçları.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'Јавни набавки',
    title: 'Е-фактура за јавни набавки (B2G)',
    publishDate: '18 август 2026',
    readTime: '8 мин читање',
    intro:
      'Ако вашата компанија продава стоки или услуги на држава — министерства, општини, јавни претпријатија или буџетски корисници — е-фактурирањето станува обврска пред сите останати. Од октомври 2026 сите B2G (business-to-government) трансакции мораат да се фактурираат електронски преку платформата на УЈП, во UBL 2.1 формат со квалификуван електронски потпис (QES). Овој водич објаснува што значи B2G, каква е врската со електронските јавни набавки и како да фактурирате кон буџетски корисници без ризик од одбивање или исклучување.',
    sections: [
      {
        title: 'Што значи B2G и кого засега',
        content:
          'B2G (business-to-government) ги опфаќа сите фактури кои бизнис ги издава кон државни субјекти. За разлика од B2B (помеѓу компании) или B2C (кон потрошувачи), кај B2G купувачот е дел од јавниот сектор и потпаѓа под построги правила за трошење на јавни средства. Обврската за е-фактура кај B2G ги засега следните добавувачи:',
        items: [
          'Добавувачи на државни институции — министерства, агенции, инспекторати, судови',
          'Добавувачи на единиците на локалната самоуправа — општини и град Скопје',
          'Добавувачи на јавни претпријатија — вклучувајќи големи комунални компании (водовод, топлификација, електродистрибуција, отпад)',
          'Добавувачи на буџетски корисници — јавни здравствени установи, училишта, универзитети, фондови',
          'Изведувачи и подизведувачи по договори склучени преку јавни набавки',
        ],
        steps: null,
      },
      {
        title: 'Обврската од октомври 2026',
        content:
          'Имплементацијата на е-фактура е фазна, но B2G оди прв. Од октомври 2026 фактурите кон јавниот сектор мораат да се издаваат и доставуваат електронски преку УЈП — хартиена или PDF фактура повеќе нема да биде прифатлива за буџетските корисници. Еве го распоредот во контекст на јавните набавки:',
        items: null,
        steps: [
          { step: 'Октомври 2026 — B2G задолжителна', desc: 'Сите добавувачи на државни институции, јавни претпријатија и буџетски корисници мораат да испраќаат е-фактури преку платформата на УЈП (efaktura.ujp.gov.mk) во UBL 2.1 формат со QES потпис.' },
          { step: 'Јануари 2027 (планирано) — Големи B2B', desc: 'ДДВ обврзници со годишен промет над 8.000.000 МКД мораат да издаваат е-фактури за сите B2B трансакции. Многу добавувачи на државата спаѓаат и во оваа група.' },
          { step: 'Јули 2027 (планирано) — Сите ДДВ обврзници', desc: 'Задолжителност за сите ДДВ обврзници без оглед на промет.' },
          { step: 'Веднаш — Доброволно', desc: 'Доброволната употреба е достапна ВЕДНАШ. Добавувачите на државата треба да почнат сега, за да бидат спремни пред рокот во октомври 2026.' },
        ],
      },
      {
        title: 'Врска со електронски јавни набавки (ЕСЈН)',
        content:
          'Македонија веќе има развиен систем за електронски јавни набавки (ЕСЈН) — тендерите, понудите и договорите се спроведуваат дигитално. Е-фактурата е логичен последен чекор во тој дигитален синџир: откако ќе добиете договор преку јавна набавка, и наплатата се затвора електронски. Учесниците во јавните набавки мораат да бидат спремни за е-фактура пред да склучат договор, зашто наручителот (буџетскиот корисник) ќе очекува електронска фактура при извршување на договорот. Практично, способноста за издавање е-фактура станува предуслов за работа со државата — не само техничка формалност, туку дел од вашата подготвеност како понудувач.',
        items: null,
        steps: null,
      },
      {
        title: 'Како да фактурирате кон буџетски корисници',
        content:
          'Фактурирањето кон буџетски корисник низ системот на УЈП следи јасен работен тек. Овие чекори важат од моментот кога имате договор и треба да наплатите:',
        items: null,
        steps: [
          { step: 'Регистрирајте се на УЈП платформата', desc: 'Посетете efaktura.ujp.gov.mk и регистрирајте ја вашата компанија. Ви требаат ЕДБ (даночен број) и QES сертификат за пристап.' },
          { step: 'Осигурете дека купувачот има ЕДБ', desc: 'Секој буџетски корисник има свој ЕДБ и мора точно да го внесете во фактурата. Погрешен или недостасувачки ЕДБ на институцијата е најчеста причина за одбивање на е-фактура.' },
          { step: 'Издајте UBL 2.1 е-фактура со QES', desc: 'Генерирајте фактура во UBL 2.1 XML формат со сите задолжителни полиња по Чл. 53 ЗДДВ (ЕДБ на продавач и купувач, датум, ставки, ДДВ, UUID) и потпишете ја со квалификуван електронски потпис.' },
          { step: 'Поднесете ја преку УЈП', desc: 'Доставете ја е-фактурата преку платформата на УЈП или преку API интеграција. Системот ја рутира кон буџетскиот корисник.' },
          { step: 'Следете ја доставата и статусот', desc: 'Проверете дали фактурата е примена, прифатена или одбиена. УЈП платформата покажува статус на секоја доставена е-фактура.' },
        ],
      },
      {
        title: 'Периоди на преглед и плаќање; одбивање и корекција',
        content:
          'Буџетските корисници ги прегледуваат е-фактурите пред одобрување за плаќање. Ако фактурата има грешка — погрешен ЕДБ, погрешен износ, недостасувачки податоци или неусогласеност со договорот — институцијата може да ја одбие. Одбиена е-фактура не се плаќа додека не ја исправите. Важно е да знаете:',
        items: [
          'Буџетските корисници може да одбијат неелектронска (хартиена или PDF) фактура во целост — е-фактурата е единствениот прифатлив формат од октомври 2026',
          'При одбивање, добивате известување со причина преку платформата — исправете го проблемот и повторно доставете',
          'Корекциите се прават со издавање нова е-фактура или одобрен документ (сторно/книжно одобрение) во UBL формат — не се менува веќе доставената фактура',
          'Периодите на плаќање зависат од договорот и од буџетскиот циклус на институцијата — електронската достава ја забрзува обработката, но не го менува договорениот рок',
          'Секоја исправка исто така мора да е потпишана со QES и архивирана 10 години',
        ],
        steps: null,
      },
      {
        title: 'Совети за добавувачите на државата',
        content:
          'Работата со јавниот сектор бара дисциплина. Овие практични совети ќе ви помогнат да избегнете задоцнети плаќања и проблеми:',
        items: [
          'Не чекајте октомври 2026 — почнете доброволно сега и решете ги техничките проблеми додека нема притисок',
          'Проверете двојно го ЕДБ на секоја институција пред да издадете фактура — тоа е бр. 1 причина за одбивање',
          'Чувајте ги референците на договорот и бројот на јавната набавка во фактурата за побрза обработка кај наручителот',
          'Ако сте голем добавувач (на пр. на комунални претпријатија), уверете се дека вашиот софтвер поддржува UBL 2.1 масовно, а не рачно по фактура',
          'Набавете QES навреме — сертификатот од Кибритон (kibriton.mk) или КИБС чини околу 2.000–5.000 МКД годишно',
          'Тестирајте во sandbox околината на УЈП пред првата вистинска фактура',
          'Запомнете: неусогласеноста може да значи одбивање на плаќање и, во потешки случаи, исклучување од идни јавни набавки',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'rokovi-2026', title: 'Рокови 2026/2027' },
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'casti-prasanja', title: 'Често поставувани прашања' },
    ],
    bottomCta: {
      title: 'Фактурирате кон државата?',
      subtitle: 'Facturino поддржува UBL 2.1 и QES потпис нативно — бидете спремни за B2G пред октомври 2026.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'Public Sector',
    title: 'E-Invoicing for Public Procurement (B2G)',
    publishDate: 'August 18, 2026',
    readTime: '8 min read',
    intro:
      'If your company sells goods or services to the state — ministries, municipalities, public enterprises or budget users — e-invoicing becomes mandatory for you before anyone else. From October 2026, all B2G (business-to-government) transactions must be invoiced electronically through the UJP platform, in UBL 2.1 format with a Qualified Electronic Signature (QES). This guide explains what B2G means, how it links to electronic public procurement, and how to invoice budget users without risking rejection or exclusion.',
    sections: [
      {
        title: 'What B2G means and who is affected',
        content:
          'B2G (business-to-government) covers all invoices a business issues to state entities. Unlike B2B (between companies) or B2C (to consumers), in B2G the buyer is part of the public sector and is subject to stricter rules on spending public funds. The B2G e-invoice mandate affects the following suppliers:',
        items: [
          'Suppliers to state institutions — ministries, agencies, inspectorates, courts',
          'Suppliers to local self-government units — municipalities and the City of Skopje',
          'Suppliers to public enterprises — including large utilities (water, district heating, power distribution, waste)',
          'Suppliers to budget users — public health institutions, schools, universities, funds',
          'Contractors and subcontractors under contracts awarded through public procurement',
        ],
        steps: null,
      },
      {
        title: 'The mandate from October 2026',
        content:
          'E-invoicing implementation is phased, but B2G goes first. From October 2026 invoices to the public sector must be issued and delivered electronically through UJP — a paper or PDF invoice will no longer be acceptable to budget users. Here is the timeline in the context of public procurement:',
        items: null,
        steps: [
          { step: 'October 2026 — B2G mandatory', desc: 'All suppliers to state institutions, public enterprises and budget users must send e-invoices via the UJP platform (efaktura.ujp.gov.mk) in UBL 2.1 format with a QES signature.' },
          { step: 'January 2027 (planned) — Large B2B', desc: 'VAT-registered companies with annual turnover above 8,000,000 MKD must issue e-invoices for all B2B transactions. Many state suppliers also fall into this group.' },
          { step: 'July 2027 (planned) — All VAT payers', desc: 'Mandatory for all VAT-registered companies regardless of turnover.' },
          { step: 'Now — Voluntary', desc: 'Voluntary adoption is available NOW. State suppliers should start today to be ready before the October 2026 deadline.' },
        ],
      },
      {
        title: 'Link to electronic public procurement (ESPP)',
        content:
          'North Macedonia already has a developed electronic public procurement system (ESPP) — tenders, bids and contracts are conducted digitally. The e-invoice is the logical final step in that digital chain: once you win a contract through public procurement, payment is closed electronically too. Participants in public procurement must be e-invoice ready before signing a contract, because the contracting authority (the budget user) will expect an electronic invoice when the contract is executed. In practice, the ability to issue an e-invoice becomes a prerequisite for doing business with the state — not just a technical formality, but part of your readiness as a bidder.',
        items: null,
        steps: null,
      },
      {
        title: 'How to invoice budget users',
        content:
          'Invoicing a budget user through the UJP system follows a clear workflow. These steps apply from the moment you have a contract and need to get paid:',
        items: null,
        steps: [
          { step: 'Register on the UJP platform', desc: 'Visit efaktura.ujp.gov.mk and register your company. You need your EDB (tax number) and a QES certificate for access.' },
          { step: 'Ensure the buyer institution has an EDB', desc: 'Every budget user has its own EDB and you must enter it correctly on the invoice. A wrong or missing institution EDB is the most common reason an e-invoice is rejected.' },
          { step: 'Issue a UBL 2.1 e-invoice with QES', desc: 'Generate the invoice in UBL 2.1 XML format with all mandatory fields per Art. 53 VAT Law (seller and buyer EDB, date, line items, VAT, UUID) and sign it with a Qualified Electronic Signature.' },
          { step: 'Submit it via UJP', desc: 'Deliver the e-invoice through the UJP platform or via API integration. The system routes it to the budget user.' },
          { step: 'Track delivery and status', desc: 'Check whether the invoice was received, accepted or rejected. The UJP platform shows the status of each delivered e-invoice.' },
        ],
      },
      {
        title: 'Review and payment periods; rejection and correction',
        content:
          'Budget users review e-invoices before approving them for payment. If an invoice has an error — wrong EDB, wrong amount, missing data or a mismatch with the contract — the institution may reject it. A rejected e-invoice is not paid until you correct it. Key things to know:',
        items: [
          'Budget users may reject a non-electronic (paper or PDF) invoice entirely — the e-invoice is the only acceptable format from October 2026',
          'On rejection, you receive a notification with the reason through the platform — fix the issue and resubmit',
          'Corrections are made by issuing a new e-invoice or an approved document (credit note) in UBL format — you do not alter an invoice already delivered',
          'Payment periods depend on the contract and the institution\'s budget cycle — electronic delivery speeds up processing but does not change the agreed deadline',
          'Every correction must also be signed with QES and archived for 10 years',
        ],
        steps: null,
      },
      {
        title: 'Tips for suppliers to the state',
        content:
          'Working with the public sector requires discipline. These practical tips will help you avoid late payments and problems:',
        items: [
          'Don\'t wait for October 2026 — start voluntarily now and solve technical issues while there is no pressure',
          'Double-check each institution\'s EDB before issuing an invoice — it is the number 1 reason for rejection',
          'Keep the contract reference and public procurement number on the invoice for faster processing by the contracting authority',
          'If you are a large supplier (e.g. to utilities), make sure your software supports UBL 2.1 in bulk, not manually per invoice',
          'Obtain your QES in time — a certificate from Kibriton (kibriton.mk) or KIBS costs around 2,000–5,000 MKD per year',
          'Test in the UJP sandbox environment before your first real invoice',
          'Remember: non-compliance can mean rejected payment and, in more serious cases, exclusion from future public procurement',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoicing' },
      { slug: 'rokovi-2026', title: 'Deadlines 2026/2027' },
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'casti-prasanja', title: 'Frequently Asked Questions' },
    ],
    bottomCta: {
      title: 'Invoicing the state?',
      subtitle: 'Facturino supports UBL 2.1 and QES signing natively — be ready for B2G before October 2026.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Prokurime',
    title: 'E-fatura për prokurime publike (B2G)',
    publishDate: '18 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Nëse kompania juaj shet mallra ose shërbime tek shteti — ministri, komuna, ndërmarrje publike ose përdorues buxhetorë — faturimi elektronik bëhet i detyrueshëm për ju para të gjithëve. Nga tetori 2026, të gjitha transaksionet B2G (business-to-government) duhet të faturohen në mënyrë elektronike përmes platformës DAP, në formatin UBL 2.1 me Nënshkrim Elektronik të Kualifikuar (QES). Ky udhëzues shpjegon çfarë do të thotë B2G, si lidhet me prokurimin elektronik publik dhe si të faturoni përdoruesit buxhetorë pa rrezikun e refuzimit ose përjashtimit.',
    sections: [
      {
        title: 'Çfarë do të thotë B2G dhe kush preket',
        content:
          'B2G (business-to-government) mbulon të gjitha faturat që një biznes lëshon tek subjektet shtetërore. Ndryshe nga B2B (mes kompanive) ose B2C (tek konsumatorët), tek B2G blerësi është pjesë e sektorit publik dhe u nënshtrohet rregullave më të rrepta për shpenzimin e fondeve publike. Detyrimi B2G për e-faturë prek furnizuesit e mëposhtëm:',
        items: [
          'Furnizuesit e institucioneve shtetërore — ministri, agjenci, inspektorate, gjykata',
          'Furnizuesit e njësive të vetëqeverisjes vendore — komunat dhe qyteti i Shkupit',
          'Furnizuesit e ndërmarrjeve publike — përfshirë kompanitë e mëdha komunale (ujësjellës, ngrohje, shpërndarje energjie, mbetje)',
          'Furnizuesit e përdoruesve buxhetorë — institucione publike shëndetësore, shkolla, universitete, fonde',
          'Kontraktorët dhe nënkontraktorët sipas kontratave të lidhura përmes prokurimit publik',
        ],
        steps: null,
      },
      {
        title: 'Detyrimi nga tetori 2026',
        content:
          'Zbatimi i e-faturës është fazor, por B2G shkon i pari. Nga tetori 2026 faturat drejt sektorit publik duhet të lëshohen dhe dorëzohen në mënyrë elektronike përmes DAP — një faturë letre ose PDF nuk do të jetë më e pranueshme për përdoruesit buxhetorë. Ja afati në kontekstin e prokurimit publik:',
        items: null,
        steps: [
          { step: 'Tetor 2026 — B2G e detyrueshme', desc: 'Të gjithë furnizuesit e institucioneve shtetërore, ndërmarrjeve publike dhe përdoruesve buxhetorë duhet të dërgojnë e-fatura përmes platformës DAP (efaktura.ujp.gov.mk) në formatin UBL 2.1 me nënshkrim QES.' },
          { step: 'Janar 2027 (planifikuar) — B2B të mëdha', desc: 'Tatimpaguesit e TVSH-së me qarkullim vjetor mbi 8.000.000 MKD duhet të lëshojnë e-fatura për të gjitha transaksionet B2B. Shumë furnizues të shtetit bien edhe në këtë grup.' },
          { step: 'Korrik 2027 (planifikuar) — Të gjithë tatimpaguesit e TVSH-së', desc: 'E detyrueshme për të gjithë tatimpaguesit e TVSH-së pavarësisht qarkullimit.' },
          { step: 'Tani — Vullnetare', desc: 'Adoptimi vullnetar është i disponueshëm TANI. Furnizuesit e shtetit duhet të fillojnë sot për të qenë gati para afatit të tetorit 2026.' },
        ],
      },
      {
        title: 'Lidhja me prokurimin elektronik publik (ESPP)',
        content:
          'Maqedonia tashmë ka një sistem të zhvilluar të prokurimit elektronik publik (ESPP) — tenderët, ofertat dhe kontratat kryhen në mënyrë dixhitale. E-fatura është hapi logjik i fundit në atë zinxhir dixhital: pasi fitoni një kontratë përmes prokurimit publik, edhe pagesa mbyllet elektronikisht. Pjesëmarrësit në prokurimin publik duhet të jenë gati për e-faturë para se të nënshkruajnë një kontratë, sepse autoriteti kontraktues (përdoruesi buxhetor) do të presë një faturë elektronike gjatë ekzekutimit të kontratës. Në praktikë, aftësia për të lëshuar një e-faturë bëhet parakusht për të bërë biznes me shtetin — jo thjesht një formalitet teknik, por pjesë e gatishmërisë suaj si ofertues.',
        items: null,
        steps: null,
      },
      {
        title: 'Si të faturoni përdoruesit buxhetorë',
        content:
          'Faturimi i një përdoruesi buxhetor përmes sistemit DAP ndjek një rrjedhë të qartë pune. Këto hapa vlejnë nga momenti kur keni një kontratë dhe duhet të paguheni:',
        items: null,
        steps: [
          { step: 'Regjistrohuni në platformën DAP', desc: 'Vizitoni efaktura.ujp.gov.mk dhe regjistroni kompaninë tuaj. Ju duhet EDB (numri tatimor) dhe një certifikatë QES për qasje.' },
          { step: 'Sigurohuni që institucioni blerës ka EDB', desc: 'Çdo përdorues buxhetor ka EDB-në e vet dhe duhet ta futni saktë në faturë. Një EDB i gabuar ose që mungon i institucionit është arsyeja më e zakonshme e refuzimit të një e-fature.' },
          { step: 'Lëshoni një e-faturë UBL 2.1 me QES', desc: 'Gjeneroni faturën në formatin UBL 2.1 XML me të gjitha fushat e detyrueshme sipas Nenit 53 të Ligjit të TVSH-së (EDB i shitësit dhe blerësit, data, artikujt, TVSH, UUID) dhe nënshkruani me Nënshkrim Elektronik të Kualifikuar.' },
          { step: 'Dorëzoni përmes DAP', desc: 'Dorëzoni e-faturën përmes platformës DAP ose integrimit API. Sistemi e ruton tek përdoruesi buxhetor.' },
          { step: 'Ndiqni dorëzimin dhe statusin', desc: 'Kontrolloni nëse fatura u prit, u pranua ose u refuzua. Platforma DAP shfaq statusin e çdo e-fature të dorëzuar.' },
        ],
      },
      {
        title: 'Periudhat e shqyrtimit dhe pagesës; refuzimi dhe korrigjimi',
        content:
          'Përdoruesit buxhetorë i shqyrtojnë e-faturat para se t\'i miratojnë për pagesë. Nëse një faturë ka gabim — EDB i gabuar, shumë e gabuar, të dhëna që mungojnë ose mospërputhje me kontratën — institucioni mund ta refuzojë. Një e-faturë e refuzuar nuk paguhet derisa ta korrigjoni. Gjërat kryesore për të ditur:',
        items: [
          'Përdoruesit buxhetorë mund të refuzojnë tërësisht një faturë joelektronike (letre ose PDF) — e-fatura është formati i vetëm i pranueshëm nga tetori 2026',
          'Në rast refuzimi, merrni një njoftim me arsyen përmes platformës — rregulloni problemin dhe ridorëzoni',
          'Korrigjimet bëhen duke lëshuar një e-faturë të re ose një dokument të miratuar (notë krediti) në formatin UBL — nuk ndryshoni një faturë tashmë të dorëzuar',
          'Periudhat e pagesës varen nga kontrata dhe cikli buxhetor i institucionit — dorëzimi elektronik përshpejton përpunimin por nuk ndryshon afatin e rënë dakord',
          'Çdo korrigjim duhet gjithashtu të nënshkruhet me QES dhe të arkivohet për 10 vjet',
        ],
        steps: null,
      },
      {
        title: 'Këshilla për furnizuesit e shtetit',
        content:
          'Puna me sektorin publik kërkon disiplinë. Këto këshilla praktike do t\'ju ndihmojnë të shmangni pagesat e vonuara dhe problemet:',
        items: [
          'Mos prisni tetorin 2026 — filloni vullnetarisht tani dhe zgjidhni problemet teknike ndërsa nuk ka presion',
          'Kontrolloni dyfish EDB-në e çdo institucioni para se të lëshoni një faturë — është arsyeja nr. 1 e refuzimit',
          'Mbani referencën e kontratës dhe numrin e prokurimit publik në faturë për përpunim më të shpejtë nga autoriteti kontraktues',
          'Nëse jeni furnizues i madh (p.sh. i ndërmarrjeve komunale), sigurohuni që softueri juaj mbështet UBL 2.1 në masë, jo manualisht për çdo faturë',
          'Merrni QES në kohë — një certifikatë nga Kibriton (kibriton.mk) ose KIBS kushton rreth 2.000–5.000 MKD në vit',
          'Testoni në mjedisin sandbox të DAP para faturës suaj të parë reale',
          'Mbani mend: mospajtueshmëria mund të nënkuptojë pagesë të refuzuar dhe, në raste më serioze, përjashtim nga prokurimet publike të ardhshme',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'rokovi-2026', title: 'Afatet 2026/2027' },
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'casti-prasanja', title: 'Pyetjet e bëra shpesh' },
    ],
    bottomCta: {
      title: 'Faturoni shtetin?',
      subtitle: 'Facturino mbështet UBL 2.1 dhe nënshkrimin QES në mënyrë natyrale — jini gati për B2G para tetorit 2026.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'Kamu',
    title: 'Kamu İhaleleri İçin E-Fatura (B2G)',
    publishDate: '18 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Şirketiniz devlete — bakanlıklara, belediyelere, kamu işletmelerine veya bütçe kullanıcılarına — mal veya hizmet satıyorsa, e-faturalama sizin için herkesten önce zorunlu hale gelir. Ekim 2026\'dan itibaren tüm B2G (business-to-government) işlemleri, UJP platformu üzerinden, QES imzalı UBL 2.1 formatında elektronik olarak faturalanmalıdır. Bu rehber B2G\'nin ne anlama geldiğini, elektronik kamu ihalesiyle nasıl bağlantılı olduğunu ve bütçe kullanıcılarını reddedilme veya hariç tutulma riski olmadan nasıl faturalayacağınızı açıklar.',
    sections: [
      {
        title: 'B2G ne demek ve kim etkilenir',
        content:
          'B2G (business-to-government), bir işletmenin devlet kurumlarına düzenlediği tüm faturaları kapsar. B2B\'den (şirketler arası) veya B2C\'den (tüketicilere) farklı olarak, B2G\'de alıcı kamu sektörünün bir parçasıdır ve kamu fonlarının harcanmasına ilişkin daha sıkı kurallara tabidir. B2G e-fatura zorunluluğu aşağıdaki tedarikçileri etkiler:',
        items: [
          'Devlet kurumlarının tedarikçileri — bakanlıklar, ajanslar, müfettişlikler, mahkemeler',
          'Yerel öz yönetim birimlerinin tedarikçileri — belediyeler ve Üsküp şehri',
          'Kamu işletmelerinin tedarikçileri — büyük altyapı kuruluşları dahil (su, bölgesel ısıtma, elektrik dağıtımı, atık)',
          'Bütçe kullanıcılarının tedarikçileri — kamu sağlık kurumları, okullar, üniversiteler, fonlar',
          'Kamu ihalesi yoluyla verilen sözleşmeler kapsamındaki yükleniciler ve alt yükleniciler',
        ],
        steps: null,
      },
      {
        title: 'Ekim 2026\'dan zorunluluk',
        content:
          'E-fatura uygulaması aşamalıdır, ancak B2G önce gelir. Ekim 2026\'dan itibaren kamu sektörüne yönelik faturalar UJP üzerinden elektronik olarak düzenlenmeli ve teslim edilmelidir — kağıt veya PDF fatura artık bütçe kullanıcıları için kabul edilebilir olmayacaktır. Kamu ihalesi bağlamında takvim şöyle:',
        items: null,
        steps: [
          { step: 'Ekim 2026 — B2G zorunlu', desc: 'Devlet kurumlarının, kamu işletmelerinin ve bütçe kullanıcılarının tüm tedarikçileri, UJP platformu (efaktura.ujp.gov.mk) üzerinden QES imzalı UBL 2.1 formatında e-fatura göndermek zorunda.' },
          { step: 'Ocak 2027 (planlanan) — Büyük B2B', desc: 'Yıllık cirosu 8.000.000 MKD üzerinde olan KDV mükellefleri tüm B2B işlemler için e-fatura düzenlemeli. Birçok devlet tedarikçisi de bu gruba girer.' },
          { step: 'Temmuz 2027 (planlanan) — Tüm KDV mükellefleri', desc: 'Ciroya bakılmaksızın tüm KDV mükellefleri için zorunlu.' },
          { step: 'Şimdi — Gönüllü', desc: 'Gönüllü kullanım ŞİMDİ mevcut. Devlet tedarikçileri Ekim 2026 son tarihinden önce hazır olmak için bugün başlamalı.' },
        ],
      },
      {
        title: 'Elektronik kamu ihalesiyle (ESPP) bağlantı',
        content:
          'Kuzey Makedonya\'nın halihazırda gelişmiş bir elektronik kamu ihale sistemi (ESPP) var — ihaleler, teklifler ve sözleşmeler dijital olarak yürütülür. E-fatura, bu dijital zincirin mantıksal son adımıdır: kamu ihalesi yoluyla bir sözleşme kazandığınızda ödeme de elektronik olarak kapatılır. Kamu ihalesine katılanlar, bir sözleşme imzalamadan önce e-faturaya hazır olmalıdır, çünkü sözleşme makamı (bütçe kullanıcısı) sözleşme yürütülürken elektronik fatura bekleyecektir. Pratikte, e-fatura düzenleyebilme yeteneği devletle iş yapmanın bir ön koşulu haline gelir — yalnızca teknik bir formalite değil, teklif veren olarak hazırlığınızın bir parçasıdır.',
        items: null,
        steps: null,
      },
      {
        title: 'Bütçe kullanıcıları nasıl faturalanır',
        content:
          'UJP sistemi üzerinden bir bütçe kullanıcısını faturalamak açık bir iş akışını izler. Bu adımlar, bir sözleşmeniz olduğu ve ödeme almanız gereken andan itibaren geçerlidir:',
        items: null,
        steps: [
          { step: 'UJP platformuna kaydolun', desc: 'efaktura.ujp.gov.mk adresini ziyaret edin ve şirketinizi kaydedin. Erişim için EDB (vergi numarası) ve bir QES sertifikası gereklidir.' },
          { step: 'Alıcı kurumun EDB\'sinin olduğundan emin olun', desc: 'Her bütçe kullanıcısının kendi EDB\'si vardır ve bunu faturaya doğru girmelisiniz. Kurumun yanlış veya eksik EDB\'si, bir e-faturanın reddedilmesinin en yaygın nedenidir.' },
          { step: 'QES ile UBL 2.1 e-fatura düzenleyin', desc: 'Faturayı KDV Kanunu Madde 53\'e göre tüm zorunlu alanlarla (satıcı ve alıcı EDB, tarih, kalemler, KDV, UUID) UBL 2.1 XML formatında oluşturun ve Nitelikli Elektronik İmza ile imzalayın.' },
          { step: 'UJP üzerinden gönderin', desc: 'E-faturayı UJP platformu veya API entegrasyonu üzerinden teslim edin. Sistem, faturayı bütçe kullanıcısına yönlendirir.' },
          { step: 'Teslimatı ve durumu takip edin', desc: 'Faturanın alınıp alınmadığını, kabul edilip edilmediğini veya reddedilip edilmediğini kontrol edin. UJP platformu, teslim edilen her e-faturanın durumunu gösterir.' },
        ],
      },
      {
        title: 'İnceleme ve ödeme dönemleri; red ve düzeltme',
        content:
          'Bütçe kullanıcıları e-faturaları ödeme için onaylamadan önce inceler. Bir faturada hata varsa — yanlış EDB, yanlış tutar, eksik veri veya sözleşmeyle uyumsuzluk — kurum reddedebilir. Reddedilen bir e-fatura, siz düzeltene kadar ödenmez. Bilinmesi gereken önemli noktalar:',
        items: [
          'Bütçe kullanıcıları elektronik olmayan (kağıt veya PDF) bir faturayı tamamen reddedebilir — e-fatura Ekim 2026\'dan itibaren tek kabul edilebilir formattır',
          'Red durumunda, platform üzerinden nedeniyle birlikte bir bildirim alırsınız — sorunu düzeltin ve yeniden gönderin',
          'Düzeltmeler, UBL formatında yeni bir e-fatura veya onaylı bir belge (alacak dekontu) düzenlenerek yapılır — teslim edilmiş bir faturayı değiştirmezsiniz',
          'Ödeme dönemleri sözleşmeye ve kurumun bütçe döngüsüne bağlıdır — elektronik teslimat işlemeyi hızlandırır ancak kararlaştırılan son tarihi değiştirmez',
          'Her düzeltme de QES ile imzalanmalı ve 10 yıl arşivlenmelidir',
        ],
        steps: null,
      },
      {
        title: 'Devlet tedarikçileri için ipuçları',
        content:
          'Kamu sektörüyle çalışmak disiplin gerektirir. Bu pratik ipuçları geç ödemelerden ve sorunlardan kaçınmanıza yardımcı olacaktır:',
        items: [
          'Ekim 2026\'yı beklemeyin — şimdi gönüllü olarak başlayın ve baskı yokken teknik sorunları çözün',
          'Bir fatura düzenlemeden önce her kurumun EDB\'sini iki kez kontrol edin — red nedeni 1 numaradır',
          'Sözleşme makamı tarafından daha hızlı işlem için sözleşme referansını ve kamu ihale numarasını faturada tutun',
          'Büyük bir tedarikçiyseniz (ör. altyapı kuruluşlarına), yazılımınızın UBL 2.1\'i fatura başına manuel değil, toplu olarak desteklediğinden emin olun',
          'QES\'inizi zamanında edinin — Kibriton (kibriton.mk) veya KIBS\'ten bir sertifika yılda yaklaşık 2.000–5.000 MKD tutar',
          'İlk gerçek faturanızdan önce UJP sandbox ortamında test edin',
          'Unutmayın: uyumsuzluk, reddedilen ödeme ve daha ciddi durumlarda gelecekteki kamu ihalelerinden hariç tutulma anlamına gelebilir',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-Fatura İçin Eksiksiz Rehber' },
      { slug: 'rokovi-2026', title: 'Son Tarihler 2026/2027' },
      { slug: 'kako-da-izdadete', title: 'E-Fatura Nasıl Düzenlenir' },
      { slug: 'casti-prasanja', title: 'Sıkça Sorulan Sorular' },
    ],
    bottomCta: {
      title: 'Devlete mi fatura kesiyorsunuz?',
      subtitle: 'Facturino UBL 2.1 ve QES imzasını doğal olarak destekler — Ekim 2026\'dan önce B2G için hazır olun.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaZaJavniNabavkiPage({
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
    slug: 'za-javni-nabavki',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'B2G', 'јавни набавки', 'public procurement', 'UBL', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/za-javni-nabavki` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кога станува задолжителна е-фактурата за јавни набавки (B2G)?', answer: 'Е-фактурата за B2G е задолжителна од октомври 2026 — сите добавувачи на државни институции, јавни претпријатија и буџетски корисници мораат да фактурираат електронски преку УЈП.' },
        { question: 'Дали државна институција може да одбие хартиена фактура?', answer: 'Да. Од октомври 2026 буџетските корисници може да ја одбијат неелектронската (хартиена или PDF) фактура во целост — прифатлива е само UBL 2.1 е-фактура со QES потпис.' },
        { question: 'Каква е врската меѓу е-фактурата и електронските јавни набавки?', answer: 'Е-фактурата е последниот дигитален чекор по добивање договор преку јавна набавка. Учесниците мораат да бидат спремни за е-фактура пред да склучат договор, зашто наручителот ќе очекува електронска фактура при извршување.' },
        { question: 'Што се случува ако е-фактурата кон буџетски корисник е одбиена?', answer: 'Одбиена е-фактура не се плаќа додека не се исправи. Добивате известување со причина преку платформата; корекцијата се прави со нова е-фактура или книжно одобрение во UBL формат, повторно потпишано со QES.' },
        { question: 'Кои се последиците ако добавувачот не е усогласен?', answer: 'Неусогласеноста може да значи одбивање на плаќање, глоба (EUR 500–3.000 за правно лице, EUR 100–500 за одговорно лице) и, во потешки случаи, исклучување од идни јавни набавки.' },
        { question: 'Што ми треба за да фактурирам кон државата?', answer: 'Потребни се ЕДБ, QES сертификат (Кибритон или КИБС, ~2.000–5.000 МКД годишно), регистрација на efaktura.ujp.gov.mk и софтвер што поддржува UBL 2.1 — како Facturino.' },
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
