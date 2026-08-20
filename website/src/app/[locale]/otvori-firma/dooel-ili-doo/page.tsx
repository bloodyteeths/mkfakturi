import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/otvori-firma/dooel-ili-doo', {
    title: {
      mk: 'ДООЕЛ, ДОО или Трговец поединец: која правна форма да изберете',
      en: 'DOOEL, DOO or Sole Proprietor: Which Legal Form to Choose',
      sq: 'SHPKNJP, SHPK apo Tregtar individual: cilën formë ligjore të zgjidhni',
      tr: 'DOOEL, DOO veya Şahıs Şirketi: Hangi Hukuki Biçimi Seçmeli',
    },
    description: {
      mk: 'Споредба на правните форми во Македонија: ДООЕЛ (едно лице), ДОО (повеќе основачи), Трговец поединец и АД. Одговорност, капитал, даноци и книговодство — која форма за кој бизнис.',
      en: 'Comparison of legal forms in North Macedonia: DOOEL (single-member LLC), DOO (multi-member LLC), sole proprietor and joint-stock company. Liability, capital, taxes and bookkeeping — which form fits which business.',
      sq: 'Krahasim i formave ligjore në Maqedoni: SHPKNJP (një anëtar), SHPK (shumë themelues), Tregtar individual dhe SHA. Përgjegjësia, kapitali, tatimet dhe kontabiliteti.',
      tr: 'Kuzey Makedonya\'daki hukuki biçimlerin karşılaştırması: DOOEL (tek ortaklı), DOO (çok ortaklı), şahıs şirketi ve anonim şirket. Sorumluluk, sermaye, vergiler ve muhasebe.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Отвори фирма',
    tag: 'Правни форми',
    title: 'ДООЕЛ, ДОО или Трговец поединец: која правна форма да изберете',
    publishDate: '20 август 2026',
    readTime: '9 мин читање',
    intro:
      'Изборот на правна форма е првата и една од најважните одлуки при отворање фирма во Македонија. Од неа зависат вашата лична одговорност, даночниот третман, обемот на книговодство и можноста да имате партнери. Најчести форми се ДООЕЛ (друштво со ограничена одговорност на едно лице), ДОО (со повеќе основачи), Трговец поединец (ТП) и Акционерско друштво (АД). Овој водич ги споредува сите форми — по одговорност, капитал, даноци и сметководство — за да ја изберете вистинската за вашиот бизнис.',
    sections: [
      {
        title: 'Преглед на правните форми',
        content:
          'Пред да одлучите, важно е да ги разберете основните разлики меѓу формите. Секоја има свои предности и мани во однос на одговорност, капитал и администрација:',
        items: [
          'ДООЕЛ — друштво со ограничена одговорност основано од едно лице. Најпопуларниот избор за мали бизниси. Основачот одговара само до висината на вложениот капитал, не со личниот имот.',
          'ДОО — друштво со ограничена одговорност со двајца или повеќе основачи (содружници). Иста заштита како ДООЕЛ, но со договор за управување меѓу партнерите.',
          'Трговец поединец (ТП) — физичко лице кое тргува под свое име. Нема основен капитал и е наједноставен за водење, но основачот одговара со целиот личен имот.',
          'АД (Акционерско друштво) — за поголеми бизниси со поделен капитал на акции. Има повисок минимален капитал и построги правила; ретко се користи за стартапи, но е задолжителен за одредени дејности (банки, осигурување).',
        ],
        steps: null,
      },
      {
        title: 'ДООЕЛ — предности и мани',
        content:
          'ДООЕЛ е форма создадена токму за претприемачи кои работат сами но сакаат заштита на личниот имот. Регистрацијата се врши во Централниот регистар на РСМ (ЦРСМ), а даночниот број (ЕДБ) се добива преку УЈП.',
        items: [
          'Предност: ограничена одговорност — личниот имот (стан, автомобил, штедење) е заштитен од долгови на фирмата.',
          'Предност: еден основач — не ви требаат партнери, целосна контрола врз одлуките.',
          'Предност: кредибилитет — поголемите клиенти и институции полесно работат со друштво отколку со физичко лице.',
          'Мана: полно двострано книговодство — потребен е сметководител или софтвер како Facturino.',
          'Мана: повеќе даночни обврски — данок на добивка 10%, ДДВ ако прометот надмине 2.000.000 МКД, МПИН за плати.',
          'Идеален за: услужни дејности, ИТ, консалтинг, мали продавници и бизниси кои растат.',
        ],
        steps: null,
      },
      {
        title: 'ДОО — кога има повеќе основачи',
        content:
          'ДОО е фактички исто како ДООЕЛ, но со двајца или повеќе содружници. Ако основате бизнис со партнер, ДОО е природниот избор бидејќи го дефинира учеството и правата на секој основач.',
        items: [
          'Двајца или повеќе основачи — секој со дефиниран удел во капиталот и во добивката.',
          'Иста ограничена одговорност како ДООЕЛ — секој содружник ризикува само до својот влог.',
          'Договор за друштво — регулира управување, распределба на добивка, влез и излез на содружници.',
          'Даночниот третман е идентичен со ДООЕЛ: данок на добивка 10%, ДДВ при промет над 2.000.000 МКД.',
          'Совет: договорете јасни правила за одлучување и излез од самиот почеток — тоа спречува идни спорови.',
        ],
        steps: null,
      },
      {
        title: 'Трговец поединец — за најмали бизниси',
        content:
          'Трговецот поединец (ТП) е наједноставната форма — физичко лице регистрирано за вршење дејност под свое име. Погоден е за фриленсери и мали услужни дејности кои сакаат минимална администрација.',
        items: [
          'Без основен капитал — нема потреба да вложувате пари при регистрација.',
          'Наједноставно водење — поедноставено книговодство и помалку административни обврски.',
          'Клучна мана: неограничена одговорност — за долговите на бизнисот одговарате со целиот личен имот.',
          'Даноци: персонален данок на доход 10% на добивката; ДДВ регистрација задолжителна ако прометот надмине 2.000.000 МКД.',
          'Идеален за: соло фриленсери, занаетчии и мали дејности со низок ризик и мал промет.',
        ],
        steps: null,
      },
      {
        title: 'Споредба: одговорност, капитал, даноци, сметководство',
        content:
          'Еве директна споредба на четирите клучни фактори што ги одвојуваат формите. Користете ја оваа листа за да ги измерите вашите приоритети:',
        items: null,
        steps: [
          { step: 'Одговорност', desc: 'ДООЕЛ и ДОО — ограничена (само вложениот капитал). Трговец поединец — неограничена (целиот личен имот). АД — ограничена. Ако ризикот е висок, изберете форма со ограничена одговорност.' },
          { step: 'Капитал', desc: 'ДООЕЛ и ДОО имаат минимален основен капитал. Трговец поединец нема капитал. АД бара значително повисок капитал. За старт со мал буџет, ТП или ДООЕЛ се најпристапни.' },
          { step: 'Даноци', desc: 'ДООЕЛ/ДОО/АД плаќаат данок на добивка 10%. Трговец поединец плаќа персонален данок на доход 10% на добивката. Сите се регистрираат за ДДВ ако прометот надмине 2.000.000 МКД годишно.' },
          { step: 'Сметководство', desc: 'ДООЕЛ, ДОО и АД водат полно двострано книговодство и поднесуваат годишна сметка. Трговец поединец има поедноставено книговодство. Facturino работи за сите форми.' },
        ],
      },
      {
        title: 'Која форма за кого — препорака',
        content:
          'Нема универзално точен избор — зависи од вашиот ризик, буџет, планови за раст и дали работите сами. Еве практична препорака по тип бизнис:',
        items: [
          'Соло фриленсер со мал промет и низок ризик → Трговец поединец (наједноставно) или ДООЕЛ (ако сакате заштита на имотот).',
          'Мал бизнис што расте, работите сами → ДООЕЛ — ограничена одговорност плюс кредибилитет кон клиенти.',
          'Основате со партнер или инвеститор → ДОО — јасно дефинирани удели и правила меѓу содружниците.',
          'Планирате поголем капитал, инвеститори или специфична дејност (банка, осигурување) → АД.',
          'Без разлика на формата — Facturino ги покрива фактурите, ДДВ, платите и извештаите за УЈП од првиот ден.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'paushal-ili-ddv', title: 'Паушалец или ДДВ обврзник?' },
      { slug: 'za-stranci', title: 'Отворање фирма за странци' },
      { slug: 'trgovec-poedinec', title: 'Трговец поединец' },
    ],
    bottomCta: {
      title: 'Ја избравте формата? Стартувајте со Facturino.',
      subtitle: 'Фактури, ДДВ, плати и извештаи за УЈП — за ДООЕЛ, ДОО и ТП. Бесплатен план за почеток.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Start a company',
    tag: 'Legal Forms',
    title: 'DOOEL, DOO or Sole Proprietor: Which Legal Form to Choose',
    publishDate: 'August 20, 2026',
    readTime: '9 min read',
    intro:
      'Choosing a legal form is the first and one of the most important decisions when opening a company in North Macedonia. It determines your personal liability, tax treatment, bookkeeping burden, and whether you can have partners. The most common forms are DOOEL (single-member limited liability company), DOO (multi-member LLC), sole proprietor (trgovec poedinec), and joint-stock company (AD). This guide compares all forms — by liability, capital, taxes, and accounting — so you can choose the right one for your business.',
    sections: [
      {
        title: 'Overview of the legal forms',
        content:
          'Before deciding, it helps to understand the core differences between forms. Each has its own advantages and drawbacks regarding liability, capital, and administration:',
        items: [
          'DOOEL — a limited liability company founded by a single person. The most popular choice for small businesses. The founder is liable only up to the invested capital, not with personal assets.',
          'DOO — a limited liability company with two or more founders (partners). The same protection as a DOOEL, but with a management agreement between the partners.',
          'Sole proprietor (TP) — a natural person trading under their own name. No share capital and the simplest to run, but the founder is liable with all personal assets.',
          'AD (joint-stock company) — for larger businesses with capital divided into shares. Higher minimum capital and stricter rules; rarely used for startups but mandatory for certain activities (banks, insurance).',
        ],
        steps: null,
      },
      {
        title: 'DOOEL — advantages and drawbacks',
        content:
          'The DOOEL is a form built precisely for entrepreneurs who work alone but want to protect their personal assets. Registration is done at the Central Registry of North Macedonia (CRMS), and the tax number (EDB) is obtained through UJP.',
        items: [
          'Advantage: limited liability — personal assets (home, car, savings) are protected from company debts.',
          'Advantage: single founder — you need no partners and keep full control over decisions.',
          'Advantage: credibility — larger clients and institutions prefer working with a company over an individual.',
          'Drawback: full double-entry bookkeeping — you need an accountant or software such as Facturino.',
          'Drawback: more tax obligations — 10% corporate tax, VAT once turnover exceeds 2,000,000 MKD, MPIN for payroll.',
          'Ideal for: service businesses, IT, consulting, small shops, and businesses that are growing.',
        ],
        steps: null,
      },
      {
        title: 'DOO — when there are multiple founders',
        content:
          'A DOO is essentially the same as a DOOEL but with two or more partners. If you are founding a business with a partner, the DOO is the natural choice because it defines each founder\'s share and rights.',
        items: [
          'Two or more founders — each with a defined share in the capital and in the profit.',
          'The same limited liability as a DOOEL — each partner risks only their contribution.',
          'Partnership agreement — governs management, profit distribution, and entry or exit of partners.',
          'Tax treatment is identical to a DOOEL: 10% corporate tax, VAT once turnover exceeds 2,000,000 MKD.',
          'Tip: agree on clear decision-making and exit rules from the start — this prevents future disputes.',
        ],
        steps: null,
      },
      {
        title: 'Sole proprietor — for the smallest businesses',
        content:
          'The sole proprietor (TP) is the simplest form — a natural person registered to conduct activity under their own name. It suits freelancers and small service businesses that want minimal administration.',
        items: [
          'No share capital — you do not need to deposit money at registration.',
          'Simplest to run — simplified bookkeeping and fewer administrative obligations.',
          'Key drawback: unlimited liability — you are liable for business debts with all your personal assets.',
          'Taxes: 10% personal income tax on profit; VAT registration mandatory if turnover exceeds 2,000,000 MKD.',
          'Ideal for: solo freelancers, tradespeople, and small low-risk, low-turnover activities.',
        ],
        steps: null,
      },
      {
        title: 'Comparison: liability, capital, taxes, accounting',
        content:
          'Here is a direct comparison of the four key factors that separate the forms. Use this list to weigh your priorities:',
        items: null,
        steps: [
          { step: 'Liability', desc: 'DOOEL and DOO — limited (only the invested capital). Sole proprietor — unlimited (all personal assets). AD — limited. If risk is high, choose a form with limited liability.' },
          { step: 'Capital', desc: 'DOOEL and DOO have a minimum share capital. A sole proprietor has none. An AD requires substantially higher capital. For a low-budget start, TP or DOOEL are the most accessible.' },
          { step: 'Taxes', desc: 'DOOEL/DOO/AD pay 10% corporate tax. A sole proprietor pays 10% personal income tax on profit. All must register for VAT if turnover exceeds 2,000,000 MKD per year.' },
          { step: 'Accounting', desc: 'DOOEL, DOO, and AD keep full double-entry bookkeeping and file annual accounts. A sole proprietor uses simplified bookkeeping. Facturino works for all forms.' },
        ],
      },
      {
        title: 'Which form for whom — recommendation',
        content:
          'There is no universally correct choice — it depends on your risk, budget, growth plans, and whether you work alone. Here is a practical recommendation by business type:',
        items: [
          'Solo freelancer with low turnover and low risk → sole proprietor (simplest) or DOOEL (if you want asset protection).',
          'Growing small business, working alone → DOOEL — limited liability plus credibility with clients.',
          'Founding with a partner or investor → DOO — clearly defined shares and rules between partners.',
          'Planning larger capital, investors, or a specific activity (bank, insurance) → AD.',
          'Whatever the form — Facturino covers invoicing, VAT, payroll, and UJP reports from day one.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'paushal-ili-ddv', title: 'Flat-Rate Taxpayer or VAT-Registered?' },
      { slug: 'za-stranci', title: 'Opening a Company for Foreigners' },
      { slug: 'trgovec-poedinec', title: 'Sole Proprietor' },
    ],
    bottomCta: {
      title: 'Chosen your form? Start with Facturino.',
      subtitle: 'Invoicing, VAT, payroll, and UJP reports — for DOOEL, DOO, and sole proprietors. Free plan to get started.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Hap firmë',
    tag: 'Format ligjore',
    title: 'SHPKNJP, SHPK apo Tregtar individual: cilën formë ligjore të zgjidhni',
    publishDate: '20 gusht 2026',
    readTime: '9 min lexim',
    intro:
      'Zgjedhja e formës ligjore është vendimi i parë dhe një nga më të rëndësishmit kur hapni një firmë në Maqedoni. Prej saj varen përgjegjësia juaj personale, trajtimi tatimor, vëllimi i kontabilitetit dhe mundësia për të pasur partnerë. Format më të zakonshme janë SHPKNJP (shoqëri me përgjegjësi të kufizuar me një anëtar), SHPK (me shumë themelues), Tregtar individual (TP) dhe Shoqëri aksionare (SHA). Ky udhëzues i krahason të gjitha format — sipas përgjegjësisë, kapitalit, tatimeve dhe kontabilitetit.',
    sections: [
      {
        title: 'Përmbledhje e formave ligjore',
        content:
          'Para se të vendosni, ndihmon të kuptoni dallimet kryesore midis formave. Secila ka avantazhet dhe mangësitë e veta lidhur me përgjegjësinë, kapitalin dhe administrimin:',
        items: [
          'SHPKNJP — shoqëri me përgjegjësi të kufizuar e themeluar nga një person. Zgjedhja më e popullarizuar për bizneset e vogla. Themeluesi përgjigjet vetëm deri në lartësinë e kapitalit të investuar, jo me pasurinë personale.',
          'SHPK — shoqëri me përgjegjësi të kufizuar me dy ose më shumë themelues (partnerë). E njëjta mbrojtje si SHPKNJP, por me marrëveshje menaxhimi midis partnerëve.',
          'Tregtar individual (TP) — person fizik që tregton nën emrin e vet. Pa kapital themeltar dhe më i thjeshti për t\'u drejtuar, por themeluesi përgjigjet me gjithë pasurinë personale.',
          'SHA (shoqëri aksionare) — për biznese më të mëdha me kapital të ndarë në aksione. Kapital minimal më i lartë dhe rregulla më të rrepta; përdoret rrallë për startup-e, por është i detyrueshëm për aktivitete të caktuara (banka, sigurime).',
        ],
        steps: null,
      },
      {
        title: 'SHPKNJP — avantazhet dhe mangësitë',
        content:
          'SHPKNJP është formë e krijuar pikërisht për sipërmarrës që punojnë vetëm por duan të mbrojnë pasurinë personale. Regjistrimi bëhet në Regjistrin Qendror të RMV-së (RQRM), ndërsa numri tatimor (EDB) merret përmes DAP.',
        items: [
          'Avantazh: përgjegjësi e kufizuar — pasuria personale (banesa, makina, kursimet) mbrohet nga borxhet e firmës.',
          'Avantazh: një themelues — nuk ju duhen partnerë, kontroll i plotë mbi vendimet.',
          'Avantazh: kredibilitet — klientët dhe institucionet më të mëdha preferojnë të punojnë me një shoqëri sesa me një person fizik.',
          'Mangësi: kontabilitet i plotë dyfishor — nevojitet kontabilist ose softuer si Facturino.',
          'Mangësi: më shumë detyrime tatimore — tatimi mbi fitimin 10%, TVSH kur qarkullimi kalon 2.000.000 MKD, MPIN për pagat.',
          'Ideale për: biznese shërbimesh, IT, konsulencë, dyqane të vogla dhe biznese në rritje.',
        ],
        steps: null,
      },
      {
        title: 'SHPK — kur ka disa themelues',
        content:
          'SHPK është faktikisht e njëjtë me SHPKNJP, por me dy ose më shumë partnerë. Nëse themeloni një biznes me partner, SHPK është zgjedhja e natyrshme sepse përcakton pjesën dhe të drejtat e secilit themelues.',
        items: [
          'Dy ose më shumë themelues — secili me pjesë të përcaktuar në kapital dhe në fitim.',
          'E njëjta përgjegjësi e kufizuar si SHPKNJP — secili partner rrezikon vetëm kontributin e vet.',
          'Marrëveshja e shoqërisë — rregullon menaxhimin, shpërndarjen e fitimit, hyrjen dhe daljen e partnerëve.',
          'Trajtimi tatimor është identik me SHPKNJP: tatimi mbi fitimin 10%, TVSH kur qarkullimi kalon 2.000.000 MKD.',
          'Këshillë: bini dakord për rregulla të qarta vendimmarrjeje dhe daljeje që në fillim — kjo parandalon mosmarrëveshjet e ardhshme.',
        ],
        steps: null,
      },
      {
        title: 'Tregtar individual — për bizneset më të vogla',
        content:
          'Tregtari individual (TP) është forma më e thjeshtë — person fizik i regjistruar për të ushtruar veprimtari nën emrin e vet. I përshtatet freelancerëve dhe bizneseve të vogla shërbimi që duan administrim minimal.',
        items: [
          'Pa kapital themeltar — nuk keni nevojë të depozitoni para gjatë regjistrimit.',
          'Më i thjeshti për t\'u drejtuar — kontabilitet i thjeshtësuar dhe më pak detyrime administrative.',
          'Mangësia kryesore: përgjegjësi e pakufizuar — për borxhet e biznesit përgjigjeni me gjithë pasurinë personale.',
          'Tatimet: tatimi mbi të ardhurat personale 10% mbi fitimin; regjistrimi për TVSH i detyrueshëm nëse qarkullimi kalon 2.000.000 MKD.',
          'Ideal për: freelancerë solo, zejtarë dhe veprimtari të vogla me rrezik dhe qarkullim të ulët.',
        ],
        steps: null,
      },
      {
        title: 'Krahasim: përgjegjësia, kapitali, tatimet, kontabiliteti',
        content:
          'Ja një krahasim i drejtpërdrejtë i katër faktorëve kryesorë që i ndajnë format. Përdoreni këtë listë për të peshuar prioritetet tuaja:',
        items: null,
        steps: [
          { step: 'Përgjegjësia', desc: 'SHPKNJP dhe SHPK — e kufizuar (vetëm kapitali i investuar). Tregtar individual — e pakufizuar (gjithë pasuria personale). SHA — e kufizuar. Nëse rreziku është i lartë, zgjidhni formë me përgjegjësi të kufizuar.' },
          { step: 'Kapitali', desc: 'SHPKNJP dhe SHPK kanë kapital minimal themeltar. Tregtari individual nuk ka. SHA kërkon kapital dukshëm më të lartë. Për një fillim me buxhet të vogël, TP ose SHPKNJP janë më të përballueshmet.' },
          { step: 'Tatimet', desc: 'SHPKNJP/SHPK/SHA paguajnë tatim mbi fitimin 10%. Tregtari individual paguan tatim mbi të ardhurat personale 10% mbi fitimin. Të gjithë regjistrohen për TVSH nëse qarkullimi kalon 2.000.000 MKD në vit.' },
          { step: 'Kontabiliteti', desc: 'SHPKNJP, SHPK dhe SHA mbajnë kontabilitet të plotë dyfishor dhe dorëzojnë llogari vjetore. Tregtari individual ka kontabilitet të thjeshtësuar. Facturino punon për të gjitha format.' },
        ],
      },
      {
        title: 'Cila formë për kë — rekomandim',
        content:
          'Nuk ka zgjedhje universalisht të saktë — varet nga rreziku, buxheti, planet për rritje dhe nëse punoni vetëm. Ja një rekomandim praktik sipas llojit të biznesit:',
        items: [
          'Freelancer solo me qarkullim të ulët dhe rrezik të ulët → tregtar individual (më i thjeshti) ose SHPKNJP (nëse doni mbrojtje pasurie).',
          'Biznes i vogël në rritje, punoni vetëm → SHPKNJP — përgjegjësi e kufizuar plus kredibilitet ndaj klientëve.',
          'Themeloni me një partner ose investitor → SHPK — pjesë dhe rregulla të përcaktuara qartë midis partnerëve.',
          'Planifikoni kapital më të madh, investitorë ose veprimtari specifike (bankë, sigurime) → SHA.',
          'Pavarësisht formës — Facturino mbulon faturat, TVSH, pagat dhe raportet për DAP që nga dita e parë.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'paushal-ili-ddv', title: 'Tatimpagues paushall apo i regjistruar për TVSH?' },
      { slug: 'za-stranci', title: 'Hapja e firmës për të huaj' },
      { slug: 'trgovec-poedinec', title: 'Tregtar individual' },
    ],
    bottomCta: {
      title: 'E zgjodhët formën? Filloni me Facturino.',
      subtitle: 'Fatura, TVSH, paga dhe raporte për DAP — për SHPKNJP, SHPK dhe tregtarë individualë. Plan falas për fillim.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Şirket kur',
    tag: 'Hukuki biçimler',
    title: 'DOOEL, DOO veya Şahıs Şirketi: Hangi Hukuki Biçimi Seçmeli',
    publishDate: '20 Ağustos 2026',
    readTime: '9 dk okuma',
    intro:
      'Hukuki biçim seçimi, Kuzey Makedonya\'da şirket açarken verilen ilk ve en önemli kararlardan biridir. Kişisel sorumluluğunuzu, vergi muamelesini, muhasebe yükünü ve ortak alıp alamayacağınızı belirler. En yaygın biçimler DOOEL (tek ortaklı limited şirket), DOO (çok ortaklı limited şirket), şahıs şirketi (trgovec poedinec) ve anonim şirkettir (AD). Bu rehber tüm biçimleri sorumluluk, sermaye, vergiler ve muhasebe açısından karşılaştırır — böylece işletmeniz için doğru olanı seçebilirsiniz.',
    sections: [
      {
        title: 'Hukuki biçimlere genel bakış',
        content:
          'Karar vermeden önce biçimler arasındaki temel farkları anlamak faydalıdır. Her birinin sorumluluk, sermaye ve yönetim açısından kendi avantajları ve dezavantajları vardır:',
        items: [
          'DOOEL — tek bir kişi tarafından kurulan limited şirket. Küçük işletmeler için en popüler seçim. Kurucu yalnızca yatırılan sermaye kadar sorumludur, kişisel varlıklarıyla değil.',
          'DOO — iki veya daha fazla kurucusu (ortağı) olan limited şirket. DOOEL ile aynı koruma, ancak ortaklar arasında yönetim sözleşmesiyle.',
          'Şahıs şirketi (TP) — kendi adıyla ticaret yapan gerçek kişi. Sermaye yok ve yönetimi en basiti, ancak kurucu tüm kişisel varlıklarıyla sorumludur.',
          'AD (anonim şirket) — sermayesi hisselere bölünmüş daha büyük işletmeler için. Daha yüksek asgari sermaye ve daha katı kurallar; startuplar için nadiren kullanılır ancak belirli faaliyetler (bankalar, sigorta) için zorunludur.',
        ],
        steps: null,
      },
      {
        title: 'DOOEL — avantajlar ve dezavantajlar',
        content:
          'DOOEL, tek başına çalışan ancak kişisel varlıklarını korumak isteyen girişimciler için tam da bu amaçla oluşturulmuş bir biçimdir. Kayıt, Kuzey Makedonya Merkezi Sicilinde (CRMS) yapılır ve vergi numarası (EDB) UJP üzerinden alınır.',
        items: [
          'Avantaj: sınırlı sorumluluk — kişisel varlıklar (ev, araba, birikim) şirket borçlarından korunur.',
          'Avantaj: tek kurucu — ortağa ihtiyacınız yoktur, kararlar üzerinde tam kontrol.',
          'Avantaj: güvenilirlik — büyük müşteriler ve kurumlar bir gerçek kişi yerine şirketle çalışmayı tercih eder.',
          'Dezavantaj: tam çift taraflı muhasebe — bir muhasebeci veya Facturino gibi bir yazılım gerekir.',
          'Dezavantaj: daha fazla vergi yükümlülüğü — %10 kurumlar vergisi, ciro 2.000.000 MKD\'yi aşınca KDV, bordro için MPIN.',
          'İdeal olduğu alanlar: hizmet işletmeleri, BT, danışmanlık, küçük dükkanlar ve büyüyen işletmeler.',
        ],
        steps: null,
      },
      {
        title: 'DOO — birden fazla kurucu olduğunda',
        content:
          'DOO esasen DOOEL ile aynıdır, ancak iki veya daha fazla ortakla. Bir ortakla işletme kuruyorsanız, DOO doğal seçimdir çünkü her kurucunun payını ve haklarını tanımlar.',
        items: [
          'İki veya daha fazla kurucu — her biri sermayede ve kârda tanımlı bir payla.',
          'DOOEL ile aynı sınırlı sorumluluk — her ortak yalnızca kendi katkısı kadar risk alır.',
          'Ortaklık sözleşmesi — yönetimi, kâr dağıtımını ve ortakların giriş çıkışını düzenler.',
          'Vergi muamelesi DOOEL ile aynıdır: %10 kurumlar vergisi, ciro 2.000.000 MKD\'yi aşınca KDV.',
          'İpucu: baştan net karar alma ve çıkış kuralları üzerinde anlaşın — bu, gelecekteki anlaşmazlıkları önler.',
        ],
        steps: null,
      },
      {
        title: 'Şahıs şirketi — en küçük işletmeler için',
        content:
          'Şahıs şirketi (TP) en basit biçimdir — kendi adıyla faaliyet yürütmek için kayıtlı gerçek kişi. Minimum yönetim isteyen serbest çalışanlara ve küçük hizmet işletmelerine uygundur.',
        items: [
          'Sermaye yok — kayıt sırasında para yatırmanız gerekmez.',
          'Yönetimi en basiti — basitleştirilmiş muhasebe ve daha az idari yükümlülük.',
          'Temel dezavantaj: sınırsız sorumluluk — işletme borçları için tüm kişisel varlıklarınızla sorumlusunuz.',
          'Vergiler: kâr üzerinden %10 gelir vergisi; ciro 2.000.000 MKD\'yi aşarsa KDV kaydı zorunlu.',
          'İdeal olduğu alanlar: tek başına serbest çalışanlar, zanaatkârlar ve düşük riskli, düşük cirolu küçük faaliyetler.',
        ],
        steps: null,
      },
      {
        title: 'Karşılaştırma: sorumluluk, sermaye, vergiler, muhasebe',
        content:
          'İşte biçimleri ayıran dört temel faktörün doğrudan karşılaştırması. Önceliklerinizi tartmak için bu listeyi kullanın:',
        items: null,
        steps: [
          { step: 'Sorumluluk', desc: 'DOOEL ve DOO — sınırlı (yalnızca yatırılan sermaye). Şahıs şirketi — sınırsız (tüm kişisel varlıklar). AD — sınırlı. Risk yüksekse sınırlı sorumlu bir biçim seçin.' },
          { step: 'Sermaye', desc: 'DOOEL ve DOO\'nun asgari bir sermayesi vardır. Şahıs şirketinin yoktur. AD önemli ölçüde daha yüksek sermaye gerektirir. Düşük bütçeli bir başlangıç için TP veya DOOEL en erişilebilir olanlardır.' },
          { step: 'Vergiler', desc: 'DOOEL/DOO/AD %10 kurumlar vergisi öder. Şahıs şirketi kâr üzerinden %10 gelir vergisi öder. Ciro yılda 2.000.000 MKD\'yi aşarsa hepsi KDV\'ye kaydolmalıdır.' },
          { step: 'Muhasebe', desc: 'DOOEL, DOO ve AD tam çift taraflı muhasebe tutar ve yıllık hesap sunar. Şahıs şirketi basitleştirilmiş muhasebe kullanır. Facturino tüm biçimler için çalışır.' },
        ],
      },
      {
        title: 'Hangi biçim kime — öneri',
        content:
          'Evrensel olarak doğru bir seçim yoktur — riskinize, bütçenize, büyüme planlarınıza ve tek başınıza çalışıp çalışmadığınıza bağlıdır. İşte işletme türüne göre pratik bir öneri:',
        items: [
          'Düşük cirolu ve düşük riskli tek başına serbest çalışan → şahıs şirketi (en basiti) veya DOOEL (varlık koruması istiyorsanız).',
          'Büyüyen küçük işletme, tek başına çalışıyorsunuz → DOOEL — sınırlı sorumluluk artı müşterilere karşı güvenilirlik.',
          'Bir ortak veya yatırımcıyla kuruyorsunuz → DOO — ortaklar arasında net tanımlanmış paylar ve kurallar.',
          'Daha büyük sermaye, yatırımcılar veya belirli bir faaliyet (banka, sigorta) planlıyorsunuz → AD.',
          'Biçim ne olursa olsun — Facturino faturaları, KDV\'yi, bordroyu ve UJP raporlarını ilk günden kapsar.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'paushal-ili-ddv', title: 'Düz Oranlı Vergi Mükellefi mi KDV Mükellefi mi?' },
      { slug: 'za-stranci', title: 'Yabancılar İçin Şirket Kurma' },
      { slug: 'trgovec-poedinec', title: 'Şahıs Şirketi' },
    ],
    bottomCta: {
      title: 'Biçiminizi seçtiniz mi? Facturino ile başlayın.',
      subtitle: 'Faturalama, KDV, bordro ve UJP raporları — DOOEL, DOO ve şahıs şirketleri için. Başlamak için ücretsiz plan.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function DooelIliDooPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const hubLabel = locale === 'mk' ? 'Отвори фирма' : locale === 'sq' ? 'Hapja e firmës' : locale === 'tr' ? 'Şirket kurma' : 'Start a Company'
  const articleLd = articleJsonLd({
    locale,
    pathPrefix: 'otvori-firma',
    slug: 'dooel-ili-doo',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-20',
    tags: ['ДООЕЛ', 'ДОО', 'DOOEL', 'DOO', 'трговец поединец', 'legal form', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: hubLabel, href: `/${locale}/otvori-firma` },
    { name: t.title, href: `/${locale}/otvori-firma/dooel-ili-doo` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Која е разликата меѓу ДООЕЛ и ДОО?', answer: 'ДООЕЛ е друштво со ограничена одговорност основано од едно лице, додека ДОО има двајца или повеќе основачи. Даночниот третман и заштитата на личниот имот се идентични — разликата е само во бројот на содружници.' },
        { question: 'Дали Трговец поединец има ограничена одговорност?', answer: 'Не. Трговецот поединец одговара со целиот свој личен имот за долговите на бизнисот. За заштита на личниот имот изберете ДООЕЛ или ДОО, кои имаат ограничена одговорност до висината на вложениот капитал.' },
        { question: 'Која правна форма плаќа најмалку даноци?', answer: 'ДООЕЛ, ДОО и АД плаќаат данок на добивка 10%, додека Трговецот поединец плаќа персонален данок на доход 10% на добивката. Сите форми се регистрираат за ДДВ ако прометот надмине 2.000.000 МКД годишно.' },
        { question: 'Која форма е најдобра за фриленсер?', answer: 'За фриленсер со мал промет и низок ризик, Трговец поединец е наједноставен. Ако сакате заштита на личниот имот и поголем кредибилитет кон клиентите, ДООЕЛ е подобар избор.' },
        { question: 'Каде се регистрира фирмата?', answer: 'Сите правни форми се регистрираат во Централниот регистар на РСМ (ЦРСМ), а даночниот број (ЕДБ) се добива преку Управата за јавни приходи (УЈП).' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/otvori-firma`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={`/${locale}/otvori-firma/${ra.slug}`}
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
