import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/rokovi-2026', {
    title: {
      mk: 'Рокови за е-фактура 2026 и 2027: фази и датуми',
      en: 'E-Invoice Deadlines 2026 & 2027: Phases and Dates',
      sq: 'Afatet e e-faturës 2026 dhe 2027: fazat dhe datat',
      tr: 'E-Fatura Son Tarihleri 2026 ve 2027: Aşamalar ve Tarihler',
    },
    description: {
      mk: 'Сите рокови за е-фактура во Македонија: B2G задолжителна од октомври 2026, големи B2B од јануари 2027 (планирано), сите ДДВ обврзници од јули 2027 (планирано). Кој е засегнат, како да се подготвите и казни за неусогласеност.',
      en: 'All e-invoice deadlines in North Macedonia: B2G mandatory from October 2026, large B2B from January 2027 (planned), all VAT payers from July 2027 (planned). Who is affected, how to prepare, and penalties for non-compliance.',
      sq: 'Të gjitha afatet e e-faturës në Maqedoni: B2G e detyrueshme nga tetori 2026, B2B të mëdha nga janari 2027 (planifikuar), të gjithë tatimpaguesit e TVSH-së nga korriku 2027 (planifikuar). Kush preket, si të përgatiteni dhe gjobat.',
      tr: 'Kuzey Makedonya\'daki tüm e-fatura son tarihleri: Ekim 2026\'dan B2G zorunlu, Ocak 2027\'den büyük B2B (planlanan), Temmuz 2027\'den tüm KDV mükellefleri (planlanan). Kim etkilenir, nasıl hazırlanılır ve cezalar.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'Рокови',
    title: 'Рокови за е-фактура 2026 и 2027: фази и датуми',
    publishDate: '18 август 2026',
    readTime: '8 мин читање',
    intro:
      'Македонија ја воведува е-фактурата фазно, во неколку чекори распоредени низ 2026 и 2027 година. Првиот задолжителен рок е октомври 2026 за фактурирање кон државни институции (B2G), а потоа обврската постепено се проширува на приватниот сектор. Овој водич ги објаснува сите клучни датуми, кој е засегнат во секоја фаза, што да направите пред секој рок и какви казни следуваат ако не се усогласите навреме.',
    sections: [
      {
        title: 'Фазна имплементација — преглед',
        content:
          'Наместо еден единствен рок за сите, Македонија се одлучи за фазен пристап. Причината е практична: над 70.000 ДДВ обврзници не можат истовремено да ги надградат своите системи, да набават квалификуван електронски потпис (QES) и да се обучат. Фазниот пристап прво го опфаќа јавниот сектор (B2G), каде процесите се поконтролирани, а потоа постепено се шири кон приватните B2B трансакции — почнувајќи од најголемите компании. Важно: доброволната употреба е достапна веднаш преку платформата efaktura.ujp.gov.mk, така што секоја компанија може да почне и пред својот законски рок.',
        items: null,
        steps: null,
      },
      {
        title: 'Клучни датуми',
        content:
          'Еве ги сите рокови распоредени по фази. Датумите за 2027 се планирани и може да се менуваат — следете ги официјалните објави на УЈП:',
        items: null,
        steps: [
          { step: 'Октомври 2026 — B2G задолжителна', desc: 'Сите компании кои фактурираат кон државни институции, јавни претпријатија и буџетски корисници мораат да испраќаат е-фактури преку платформата на УЈП. Ова е првиот и единствен потврден задолжителен рок.' },
          { step: 'Јануари 2027 (планирано) — Големи B2B', desc: 'ДДВ обврзници со годишен промет над 8.000.000 МКД треба да издаваат е-фактури за сите B2B трансакции. Рокот е планиран и подлежи на официјална потврда.' },
          { step: 'Јули 2027 (планирано) — Сите ДДВ обврзници', desc: 'Обврската се проширува на сите ДДВ обврзници без оглед на прометот. Планиран рок кој ја комплетира транзицијата.' },
          { step: 'Веднаш — Доброволно', desc: 'Доброволната употреба е достапна ВЕДНАШ преку efaktura.ujp.gov.mk. Не мора да чекате законски рок за да почнете.' },
        ],
      },
      {
        title: 'Кој е засегнат во секоја фаза',
        content:
          'Проверете во која фаза спаѓа вашата компанија за да знаете кога станува задолжителна е-фактурата за вас:',
        items: [
          'Фаза 1 (окт. 2026): секој што фактурира кон државни институции, министерства, општини, јавни претпријатија и буџетски корисници (B2G)',
          'Фаза 1: учесници во системот за електронски јавни набавки (ЕСЈН)',
          'Фаза 2 (јан. 2027, планирано): ДДВ обврзници со годишен промет над 8.000.000 МКД за сите нивни B2B фактури',
          'Фаза 3 (јул. 2027, планирано): сите останати ДДВ обврзници, вклучително мали фирми и занаетчии',
          'Странски компании со ДДВ регистрација во Македонија кои фактурираат кон МК субјекти влегуваат според истите фази',
          'Секоја компанија што сака — доброволно, веднаш, без оглед на фаза',
        ],
        steps: null,
      },
      {
        title: 'Што да направите пред секој рок',
        content:
          'Подготовката е иста без оглед на која фаза припаѓате — само рокот се разликува. Поминете ги овие чекори навреме:',
        items: null,
        steps: [
          { step: 'Проверете дали вашиот софтвер поддржува UBL 2.1', desc: 'Е-фактурата мора да биде UBL 2.1 XML — не PDF или скенирана слика. Не секој сметководствен софтвер го генерира тоа. Facturino поддржува UBL 2.1 експорт нативно.' },
          { step: 'Набавете квалификуван електронски потпис (QES)', desc: 'Контактирајте Кибритон (kibriton.mk) или КИБС. Цената е 2.000-5.000 МКД годишно. Сертификатот е на USB токен или cloud-based и е задолжителен за секоја е-фактура.' },
          { step: 'Регистрирајте се на платформата на УЈП', desc: 'Посетете efaktura.ujp.gov.mk и регистрирајте ја компанијата. Ви требаат ЕДБ (даночен број) и QES за регистрација.' },
          { step: 'Тестирајте во sandbox околина', desc: 'УЈП нуди тест (sandbox) околина каде можете да испраќате пробни е-фактури без правни последици. Искористете ја пред живо да преминете.' },
          { step: 'Обучете го персоналот', desc: 'Новиот работен тек: креирај фактура → потпиши со QES → испрати преку платформа → архивирај 10 години. Сите вклучени лица треба да го разберат процесот.' },
        ],
      },
      {
        title: 'Казни ако не се усогласите навреме',
        content:
          'Пропуштањето на вашиот рок носи конкретни последици. Прекршочните одредби предвидуваат:',
        items: [
          'Глоба EUR 500-3.000 за правно лице кое издава хартиена фактура наместо е-фактура кога тоа е задолжително',
          'Глоба EUR 100-500 за одговорното лице во фирмата',
          'Буџетски корисници (државни институции) може да ги одбијат неелектронските фактури — што значи нема плаќање додека не испратите е-фактура',
          'Можно исклучување од јавни набавки за компании кои не се усогласени',
          'Повторени прекршоци може да доведат до построги санкции',
        ],
        steps: null,
      },
      {
        title: 'Совет: почнете доброволно сега',
        content:
          'Најпаметната стратегија е да не го чекате вашиот рок. Доброволната употреба е достапна веднаш и носи неколку предности: имате време мирно да го тестирате процесот во sandbox без притисок, го набавувате QES-от пред налетот на побарувачка (кога сите ќе бараат сертификати пред октомври 2026, редовите ќе бидат подолги), и вашиот персонал се навикнува на новиот работен тек постепено. Компаниите што почнуваат рано влегуваат во задолжителната фаза целосно подготвени, без итни проблеми во последен момент. Facturino поддржува UBL 2.1 и QES потпис нативно — можете да почнете со доброволно е-фактурирање денес.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'za-javni-nabavki', title: 'Е-фактура за јавни набавки' },
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'casti-prasanja', title: 'Често поставувани прашања' },
    ],
    bottomCta: {
      title: 'Спремни пред рокот?',
      subtitle: 'Facturino поддржува UBL 2.1 и QES потпис — почнете доброволно денес.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'Deadlines',
    title: 'E-Invoice Deadlines 2026 & 2027: Phases and Dates',
    publishDate: 'August 18, 2026',
    readTime: '8 min read',
    intro:
      'North Macedonia is rolling out e-invoicing in phases, spread across 2026 and 2027. The first mandatory deadline is October 2026 for invoicing government institutions (B2G), after which the obligation gradually extends to the private sector. This guide explains every key date, who is affected in each phase, what to do before each deadline, and the penalties for failing to comply on time.',
    sections: [
      {
        title: 'Phased rollout — overview',
        content:
          'Rather than a single deadline for everyone, North Macedonia chose a phased approach. The reason is practical: over 70,000 VAT-registered businesses cannot simultaneously upgrade their systems, obtain a Qualified Electronic Signature (QES), and train their staff. The phased approach starts with the public sector (B2G), where processes are more controlled, then gradually expands to private B2B transactions — beginning with the largest companies. Importantly, voluntary adoption is available now via the efaktura.ujp.gov.mk platform, so any company can start ahead of its legal deadline.',
        items: null,
        steps: null,
      },
      {
        title: 'Key dates',
        content:
          'Here are all deadlines organized by phase. The 2027 dates are planned and may change — follow the official UJP announcements:',
        items: null,
        steps: [
          { step: 'October 2026 — B2G mandatory', desc: 'All companies invoicing government institutions, public enterprises, and budget users must send e-invoices via the UJP platform. This is the first and only confirmed mandatory deadline.' },
          { step: 'January 2027 (planned) — Large B2B', desc: 'VAT-registered companies with annual turnover above 8,000,000 MKD should issue e-invoices for all B2B transactions. This deadline is planned and subject to official confirmation.' },
          { step: 'July 2027 (planned) — All VAT payers', desc: 'The obligation extends to all VAT-registered companies regardless of turnover. A planned deadline that completes the transition.' },
          { step: 'Now — Voluntary', desc: 'Voluntary adoption is available NOW via efaktura.ujp.gov.mk. You do not have to wait for a legal deadline to start.' },
        ],
      },
      {
        title: 'Who is affected in each phase',
        content:
          'Check which phase your company falls into so you know when e-invoicing becomes mandatory for you:',
        items: [
          'Phase 1 (Oct 2026): anyone invoicing government institutions, ministries, municipalities, public enterprises, and budget users (B2G)',
          'Phase 1: participants in the electronic public procurement system (ESPP)',
          'Phase 2 (Jan 2027, planned): VAT payers with annual turnover above 8,000,000 MKD for all their B2B invoices',
          'Phase 3 (Jul 2027, planned): all remaining VAT payers, including small firms and sole traders',
          'Foreign companies with MK VAT registration invoicing Macedonian entities enter under the same phases',
          'Any company that wants to — voluntarily, right now, regardless of phase',
        ],
        steps: null,
      },
      {
        title: 'What to do before each deadline',
        content:
          'Preparation is the same regardless of which phase you belong to — only the deadline differs. Work through these steps in good time:',
        items: null,
        steps: [
          { step: 'Check if your software supports UBL 2.1', desc: 'An e-invoice must be UBL 2.1 XML — not a PDF or scanned image. Not every accounting software generates it. Facturino supports UBL 2.1 export natively.' },
          { step: 'Obtain a Qualified Electronic Signature (QES)', desc: 'Contact Kibriton (kibriton.mk) or KIBS. The cost is 2,000-5,000 MKD annually. The certificate comes on a USB token or cloud-based and is mandatory for every e-invoice.' },
          { step: 'Register on the UJP platform', desc: 'Visit efaktura.ujp.gov.mk and register your company. You need your EDB (tax number) and QES to register.' },
          { step: 'Test in the sandbox environment', desc: 'UJP offers a test (sandbox) environment where you can send trial e-invoices without legal consequences. Use it before going live.' },
          { step: 'Train your staff', desc: 'New workflow: create invoice → sign with QES → send via platform → archive for 10 years. All involved personnel should understand the process.' },
        ],
      },
      {
        title: 'Penalties for late compliance',
        content:
          'Missing your deadline carries concrete consequences. The offense provisions foresee:',
        items: [
          'Fine EUR 500-3,000 for a legal entity issuing a paper invoice instead of an e-invoice when it is mandatory',
          'Fine EUR 100-500 for the responsible person in the company',
          'Budget buyers (government institutions) may reject non-electronic invoices — meaning no payment until you send an e-invoice',
          'Possible exclusion from public procurement for non-compliant companies',
          'Repeated violations may lead to stricter sanctions',
        ],
        steps: null,
      },
      {
        title: 'Tip: start voluntarily now',
        content:
          'The smartest strategy is not to wait for your deadline. Voluntary adoption is available immediately and brings several advantages: you have time to calmly test the process in the sandbox without pressure, you obtain your QES before the surge in demand (when everyone rushes for certificates before October 2026, queues will be longer), and your staff gradually gets used to the new workflow. Companies that start early enter the mandatory phase fully prepared, with no last-minute emergencies. Facturino supports UBL 2.1 and QES signing natively — you can start voluntary e-invoicing today.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoice' },
      { slug: 'za-javni-nabavki', title: 'E-Invoice for Public Procurement' },
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'casti-prasanja', title: 'Frequently Asked Questions' },
    ],
    bottomCta: {
      title: 'Ready before the deadline?',
      subtitle: 'Facturino supports UBL 2.1 and QES signing — start voluntarily today.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Afatet',
    title: 'Afatet e e-faturës 2026 dhe 2027: fazat dhe datat',
    publishDate: '18 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Maqedonia po e zbaton e-faturën me faza, të shpërndara gjatë vitit 2026 dhe 2027. Afati i parë i detyrueshëm është tetori 2026 për faturimin ndaj institucioneve shtetërore (B2G), pas së cilës detyrimi zgjerohet gradualisht te sektori privat. Ky udhëzues shpjegon çdo datë kyçe, kush preket në secilën fazë, çfarë duhet bërë para çdo afati dhe gjobat për mospajtueshmëri me kohë.',
    sections: [
      {
        title: 'Zbatimi fazor — përmbledhje',
        content:
          'Në vend të një afati të vetëm për të gjithë, Maqedonia zgjodhi një qasje fazore. Arsyeja është praktike: mbi 70.000 tatimpagues të TVSH-së nuk mund të përditësojnë njëkohësisht sistemet e tyre, të marrin Nënshkrim Elektronik të Kualifikuar (QES) dhe të trajnojnë stafin. Qasja fazore fillon me sektorin publik (B2G), ku proceset janë më të kontrolluara, pastaj zgjerohet gradualisht te transaksionet private B2B — duke filluar me kompanitë më të mëdha. E rëndësishme: adoptimi vullnetar është i disponueshëm tani përmes platformës efaktura.ujp.gov.mk, kështu që çdo kompani mund të fillojë para afatit të saj ligjor.',
        items: null,
        steps: null,
      },
      {
        title: 'Datat kyçe',
        content:
          'Këtu janë të gjitha afatet të organizuara sipas fazave. Datat për 2027 janë të planifikuara dhe mund të ndryshojnë — ndiqni njoftimet zyrtare të DAP:',
        items: null,
        steps: [
          { step: 'Tetor 2026 — B2G e detyrueshme', desc: 'Të gjitha kompanitë që faturojnë institucionet shtetërore, ndërmarrjet publike dhe përdoruesit buxhetorë duhet të dërgojnë e-fatura përmes platformës DAP. Ky është afati i parë dhe i vetmi i konfirmuar i detyrueshëm.' },
          { step: 'Janar 2027 (planifikuar) — B2B të mëdha', desc: 'Tatimpaguesit e TVSH-së me qarkullim vjetor mbi 8.000.000 MKD duhet të lëshojnë e-fatura për të gjitha transaksionet B2B. Ky afat është i planifikuar dhe i nënshtrohet konfirmimit zyrtar.' },
          { step: 'Korrik 2027 (planifikuar) — Të gjithë tatimpaguesit e TVSH-së', desc: 'Detyrimi zgjerohet për të gjithë tatimpaguesit e TVSH-së pavarësisht qarkullimit. Një afat i planifikuar që plotëson tranzicionin.' },
          { step: 'Tani — Vullnetare', desc: 'Adoptimi vullnetar është i disponueshëm TANI përmes efaktura.ujp.gov.mk. Nuk keni pse të prisni një afat ligjor për të filluar.' },
        ],
      },
      {
        title: 'Kush preket në secilën fazë',
        content:
          'Kontrolloni në cilën fazë bie kompania juaj për të ditur kur e-fatura bëhet e detyrueshme për ju:',
        items: [
          'Faza 1 (tet. 2026): kushdo që faturon institucionet shtetërore, ministritë, komunat, ndërmarrjet publike dhe përdoruesit buxhetorë (B2G)',
          'Faza 1: pjesëmarrësit në sistemin elektronik të prokurimit publik (ESPP)',
          'Faza 2 (jan. 2027, planifikuar): tatimpaguesit e TVSH-së me qarkullim vjetor mbi 8.000.000 MKD për të gjitha faturat e tyre B2B',
          'Faza 3 (korr. 2027, planifikuar): të gjithë tatimpaguesit e mbetur të TVSH-së, përfshirë firmat e vogla dhe zejtarët',
          'Kompanitë e huaja me regjistrim TVSH në MK që faturojnë subjekte maqedonase hyjnë sipas të njëjtave faza',
          'Çdo kompani që dëshiron — vullnetarisht, tani, pavarësisht fazës',
        ],
        steps: null,
      },
      {
        title: 'Çfarë duhet bërë para çdo afati',
        content:
          'Përgatitja është e njëjtë pavarësisht se në cilën fazë i përkisni — vetëm afati ndryshon. Kalojini këto hapa me kohë:',
        items: null,
        steps: [
          { step: 'Kontrolloni nëse softueri juaj mbështet UBL 2.1', desc: 'Një e-faturë duhet të jetë UBL 2.1 XML — jo PDF ose imazh i skanuar. Jo çdo softuer kontabiliteti e gjeneron atë. Facturino mbështet eksportin UBL 2.1 në mënyrë të natyrshme.' },
          { step: 'Merrni Nënshkrim Elektronik të Kualifikuar (QES)', desc: 'Kontaktoni Kibriton (kibriton.mk) ose KIBS. Kostoja është 2.000-5.000 MKD në vit. Certifikata vjen në token USB ose cloud dhe është e detyrueshme për çdo e-faturë.' },
          { step: 'Regjistrohuni në platformën DAP', desc: 'Vizitoni efaktura.ujp.gov.mk dhe regjistroni kompaninë tuaj. Ju nevojitet EDB (numri tatimor) dhe QES për regjistrim.' },
          { step: 'Testoni në mjedisin sandbox', desc: 'DAP ofron një mjedis testimi (sandbox) ku mund të dërgoni e-fatura provë pa pasoja ligjore. Përdoreni para se të kaloni në prodhim.' },
          { step: 'Trajnoni stafin', desc: 'Rrjedha e re: krijo faturë → nënshkruaj me QES → dërgo përmes platformës → arkivo për 10 vjet. Të gjithë personat e përfshirë duhet ta kuptojnë procesin.' },
        ],
      },
      {
        title: 'Gjobat për pajtueshmëri me vonesë',
        content:
          'Humbja e afatit tuaj bart pasoja konkrete. Dispozitat për kundërvajtje parashikojnë:',
        items: [
          'Gjobë EUR 500-3.000 për personin juridik që lëshon faturë letre në vend të e-faturës kur është e detyrueshme',
          'Gjobë EUR 100-500 për personin përgjegjës në kompani',
          'Blerësit buxhetorë (institucionet shtetërore) mund të refuzojnë faturat joelektronike — që do të thotë asnjë pagesë derisa të dërgoni e-faturë',
          'Përjashtim i mundshëm nga prokurimi publik për kompanitë jo në pajtueshmëri',
          'Shkeljet e përsëritura mund të çojnë në sanksione më të rrepta',
        ],
        steps: null,
      },
      {
        title: 'Këshillë: filloni vullnetarisht tani',
        content:
          'Strategjia më e mençur është të mos prisni afatin tuaj. Adoptimi vullnetar është i disponueshëm menjëherë dhe sjell disa avantazhe: keni kohë të testoni procesin me qetësi në sandbox pa presion, e merrni QES-in para vrullit të kërkesës (kur të gjithë nxitojnë për certifikata para tetorit 2026, radhët do të jenë më të gjata), dhe stafi juaj gradualisht mësohet me rrjedhën e re. Kompanitë që fillojnë herët hyjnë në fazën e detyrueshme plotësisht të përgatitura, pa urgjenca në minutën e fundit. Facturino mbështet UBL 2.1 dhe nënshkrimin QES në mënyrë të natyrshme — mund të filloni e-faturimin vullnetar sot.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'za-javni-nabavki', title: 'E-fatura për prokurime publike' },
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'casti-prasanja', title: 'Pyetje të bëra shpesh' },
    ],
    bottomCta: {
      title: 'Gati para afatit?',
      subtitle: 'Facturino mbështet UBL 2.1 dhe nënshkrimin QES — filloni vullnetarisht sot.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'Son Tarihler',
    title: 'E-Fatura Son Tarihleri 2026 ve 2027: Aşamalar ve Tarihler',
    publishDate: '18 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Kuzey Makedonya, e-faturayı 2026 ve 2027 boyunca yayılan aşamalar halinde uyguluyor. İlk zorunlu son tarih, devlet kurumlarına faturalama (B2G) için Ekim 2026\'dır; ardından yükümlülük kademeli olarak özel sektöre genişler. Bu rehber her kilit tarihi, her aşamada kimin etkilendiğini, her son tarihten önce ne yapılması gerektiğini ve zamanında uyum sağlanmazsa uygulanacak cezaları açıklar.',
    sections: [
      {
        title: 'Aşamalı geçiş — genel bakış',
        content:
          'Kuzey Makedonya, herkes için tek bir son tarih yerine aşamalı bir yaklaşım seçti. Nedeni pratik: 70.000\'den fazla KDV mükellefi aynı anda sistemlerini güncelleyemez, Nitelikli Elektronik İmza (QES) alamaz ve personelini eğitemez. Aşamalı yaklaşım, süreçlerin daha kontrollü olduğu kamu sektörüyle (B2G) başlar, ardından kademeli olarak özel B2B işlemlere — en büyük şirketlerden başlayarak — genişler. Önemlisi, gönüllü kullanım efaktura.ujp.gov.mk platformu üzerinden şimdi mevcuttur, böylece herhangi bir şirket yasal son tarihinden önce başlayabilir.',
        items: null,
        steps: null,
      },
      {
        title: 'Kilit tarihler',
        content:
          'İşte aşamalara göre düzenlenmiş tüm son tarihler. 2027 tarihleri planlanmıştır ve değişebilir — resmi UJP duyurularını takip edin:',
        items: null,
        steps: [
          { step: 'Ekim 2026 — B2G zorunlu', desc: 'Devlet kurumlarına, kamu işletmelerine ve bütçe kullanıcılarına fatura kesen tüm şirketler UJP platformu üzerinden e-fatura göndermek zorundadır. Bu, ilk ve tek onaylanmış zorunlu son tarihtir.' },
          { step: 'Ocak 2027 (planlanan) — Büyük B2B', desc: 'Yıllık cirosu 8.000.000 MKD üzerinde olan KDV mükellefleri tüm B2B işlemler için e-fatura düzenlemelidir. Bu son tarih planlanmıştır ve resmi onaya tabidir.' },
          { step: 'Temmuz 2027 (planlanan) — Tüm KDV mükellefleri', desc: 'Yükümlülük, ciroya bakılmaksızın tüm KDV mükelleflerine genişler. Geçişi tamamlayan planlanmış bir son tarih.' },
          { step: 'Şimdi — Gönüllü', desc: 'Gönüllü kullanım efaktura.ujp.gov.mk üzerinden ŞİMDİ mevcuttur. Başlamak için yasal bir son tarihi beklemeniz gerekmez.' },
        ],
      },
      {
        title: 'Her aşamada kim etkilenir',
        content:
          'E-faturanın sizin için ne zaman zorunlu olacağını bilmek için şirketinizin hangi aşamaya girdiğini kontrol edin:',
        items: [
          'Aşama 1 (Eki 2026): devlet kurumlarına, bakanlıklara, belediyelere, kamu işletmelerine ve bütçe kullanıcılarına fatura kesen herkes (B2G)',
          'Aşama 1: elektronik kamu ihale sistemine (ESPP) katılanlar',
          'Aşama 2 (Oca 2027, planlanan): yıllık cirosu 8.000.000 MKD üzerinde olan KDV mükellefleri tüm B2B faturaları için',
          'Aşama 3 (Tem 2027, planlanan): küçük firmalar ve esnaflar dahil kalan tüm KDV mükellefleri',
          'MK KDV kaydı olan ve Makedon kuruluşlara fatura kesen yabancı şirketler aynı aşamalar altına girer',
          'İsteyen her şirket — gönüllü olarak, şimdi, aşamadan bağımsız',
        ],
        steps: null,
      },
      {
        title: 'Her son tarihten önce ne yapmalı',
        content:
          'Hangi aşamaya ait olursanız olun hazırlık aynıdır — yalnızca son tarih değişir. Bu adımları zamanında tamamlayın:',
        items: null,
        steps: [
          { step: 'Yazılımınızın UBL 2.1 destekleyip desteklemediğini kontrol edin', desc: 'Bir e-fatura UBL 2.1 XML olmalıdır — PDF veya taranmış görüntü değil. Her muhasebe yazılımı bunu üretmez. Facturino UBL 2.1 dışa aktarımını doğal olarak destekler.' },
          { step: 'Nitelikli Elektronik İmza (QES) edinin', desc: 'Kibriton (kibriton.mk) veya KIBS ile iletişime geçin. Yıllık maliyet 2.000-5.000 MKD. Sertifika USB token veya bulut tabanlı gelir ve her e-fatura için zorunludur.' },
          { step: 'UJP platformuna kaydolun', desc: 'efaktura.ujp.gov.mk adresini ziyaret edin ve şirketinizi kaydedin. Kayıt için EDB\'niz (vergi numarası) ve QES gereklidir.' },
          { step: 'Sandbox ortamında test edin', desc: 'UJP, yasal sonuç olmadan deneme e-faturaları gönderebileceğiniz bir test (sandbox) ortamı sunar. Canlıya geçmeden önce kullanın.' },
          { step: 'Personeli eğitin', desc: 'Yeni iş akışı: fatura oluştur → QES ile imzala → platform üzerinden gönder → 10 yıl arşivle. İlgili tüm personel süreci anlamalıdır.' },
        ],
      },
      {
        title: 'Geç uyum cezaları',
        content:
          'Son tarihinizi kaçırmak somut sonuçlar doğurur. Kabahat hükümleri şunları öngörür:',
        items: [
          'Zorunlu olduğunda e-fatura yerine kağıt fatura düzenleyen tüzel kişiye 500-3.000 EUR para cezası',
          'Şirketteki sorumlu kişiye 100-500 EUR para cezası',
          'Bütçe alıcıları (devlet kurumları) elektronik olmayan faturaları reddedebilir — yani e-fatura göndermeden ödeme yapılmaz',
          'Uyumsuz şirketler için kamu ihalelerinden olası çıkarılma',
          'Tekrarlanan ihlaller daha sıkı yaptırımlara yol açabilir',
        ],
        steps: null,
      },
      {
        title: 'İpucu: gönüllü olarak şimdi başlayın',
        content:
          'En akıllı strateji son tarihinizi beklememektir. Gönüllü kullanım hemen mevcuttur ve birkaç avantaj sağlar: süreci baskı olmadan sandbox\'ta sakince test edecek zamanınız olur, QES\'inizi talep dalgasından önce alırsınız (Ekim 2026\'dan önce herkes sertifikaya koştuğunda kuyruklar daha uzun olacak) ve personeliniz yeni iş akışına kademeli olarak alışır. Erken başlayan şirketler zorunlu aşamaya son dakika acil durumları olmadan tam hazır girerler. Facturino UBL 2.1 ve QES imzalamayı doğal olarak destekler — gönüllü e-faturalamaya bugün başlayabilirsiniz.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-Fatura İçin Eksiksiz Rehber' },
      { slug: 'za-javni-nabavki', title: 'Kamu İhaleleri İçin E-Fatura' },
      { slug: 'kako-da-izdadete', title: 'E-Fatura Nasıl Düzenlenir' },
      { slug: 'casti-prasanja', title: 'Sıkça Sorulan Sorular' },
    ],
    bottomCta: {
      title: 'Son tarihten önce hazır mısınız?',
      subtitle: 'Facturino UBL 2.1 ve QES imzasını destekler — bugün gönüllü olarak başlayın.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaRokoviPage({
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
    slug: 'rokovi-2026',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'рокови', 'deadlines', 'UBL', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/rokovi-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кога станува задолжителна е-фактурата?', answer: 'Првиот задолжителен рок е октомври 2026 за фактурирање кон државни институции (B2G). За големи B2B со промет над 8.000.000 МКД се планира јануари 2027, а за сите ДДВ обврзници јули 2027.' },
        { question: 'Дали можам да почнам со е-фактура пред мојот рок?', answer: 'Да. Доброволната употреба е достапна веднаш преку платформата efaktura.ujp.gov.mk, без оглед на вашата фаза или законски рок.' },
        { question: 'Кои рокови се потврдени, а кои планирани?', answer: 'Само октомври 2026 (B2G) е потврден задолжителен рок. Датумите за јануари 2027 и јули 2027 се планирани и подлежат на официјална потврда од УЈП.' },
        { question: 'Што ми треба за да се подготвам пред рокот?', answer: 'Софтвер со поддршка за UBL 2.1, квалификуван електронски потпис (QES) од Кибритон или КИБС, регистрација на efaktura.ujp.gov.mk, тестирање во sandbox и обука на персоналот.' },
        { question: 'Какви казни следуваат ако не се усогласам навреме?', answer: 'Глоба од EUR 500-3.000 за правно лице и EUR 100-500 за одговорното лице. Буџетските корисници може да ги одбијат неелектронските фактури, а можно е и исклучување од јавни набавки.' },
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
