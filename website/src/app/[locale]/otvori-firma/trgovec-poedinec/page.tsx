import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/otvori-firma/trgovec-poedinec', {
    title: {
      mk: 'Трговец поединец (ТП): регистрација, даноци и обврски',
      en: 'Sole Proprietor (Trgovec Poedinec): Registration, Taxes and Obligations',
      sq: 'Tregtar individual (TP): regjistrimi, tatimet dhe detyrimet',
      tr: 'Şahıs İşletmesi (Trgovec Poedinec): Kayıt, Vergiler ve Yükümlülükler',
    },
    description: {
      mk: 'Целосен водич за трговец поединец (ТП) во Македонија: што е ТП, лична одговорност наспроти ДООЕЛ, за кого е погоден, регистрација во ЦРСМ, ЕДБ преку УЈП, паушал или ДДВ.',
      en: 'Complete guide to sole proprietor (Trgovec Poedinec) in North Macedonia: what a TP is, personal liability vs DOOEL, who it suits, registration at CRMS, EDB via UJP, lump-sum or VAT.',
      sq: 'Udhëzues i plotë për tregtar individual (TP) në Maqedoni: çfarë është TP, përgjegjësia personale kundrejt DOOEL, për kë përshtatet, regjistrimi në RQRM, EDB përmes UJP, tatim paushall ose TVSH.',
      tr: 'Kuzey Makedonya\'da şahıs işletmesi (Trgovec Poedinec) için eksiksiz rehber: TP nedir, kişisel sorumluluk ve DOOEL, kime uygundur, CRMS kaydı, UJP üzerinden EDB, götürü veya KDV.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Отвори фирма',
    tag: 'Трговец поединец',
    title: 'Трговец поединец (ТП): регистрација, даноци и обврски',
    publishDate: '20 август 2026',
    readTime: '8 мин читање',
    intro:
      'Трговецот поединец (ТП) е наједноставната форма за започнување бизнис во Македонија. Тоа е физичко лице кое врши регистрирана трговска дејност под свое име, без потреба од основачки капитал. ТП е популарен избор за фриленсери, занаетчии и мали услужни дејности со низок ризик. Но за разлика од ДООЕЛ, ТП одговара со целиот свој личен имот. Овој водич објаснува што е ТП, како се регистрира, кои даноци се плаќаат и кога оваа форма е вистинскиот избор.',
    sections: [
      {
        title: 'Што е трговец поединец (ТП)?',
        content:
          'Трговец поединец е физичко лице кое самостојно врши регистрирана трговска дејност заради остварување добивка. За разлика од трговските друштва (ДООЕЛ, ДОО), ТП не е посебно правно лице — тоа е самиот претприемач кој регистрирал дејност под свое име. Нема законски пропишан минимален основачки капитал, што го прави ТП наједноставната и најевтината форма за старт. Токму затоа е погоден за физички лица кои сакаат брзо и без бирократија да почнат да фактурираат за својата работа.',
        items: null,
        steps: null,
      },
      {
        title: 'ТП наспроти ДООЕЛ: клучната разлика',
        content:
          'Најважната разлика меѓу ТП и ДООЕЛ е одговорноста. Оваа разлика треба внимателно да ја разгледате пред да одлучите:',
        items: [
          'Одговорност: ТП одговара со целиот свој личен имот за долговите на дејноста. ДООЕЛ е правно лице со ограничена одговорност — одговара само до висината на основачкиот капитал.',
          'Основачки капитал: ТП нема пропишан минимален капитал. ДООЕЛ бара минимален капитал од 5.000 EUR (во денарска противвредност).',
          'Правен статус: ТП не е посебно правно лице — тоа е самото физичко лице. ДООЕЛ е одвоено правно лице од основачот.',
          'Сложеност: ТП има поедноставена администрација и често може да користи паушално оданочување. ДООЕЛ води целосно двојно книговодство.',
          'Ризик: ТП е погоден за дејности со низок ризик. За дејности со поголеми долгови или ризик, ограничената одговорност на ДООЕЛ е побезбедна.',
        ],
        steps: null,
      },
      {
        title: 'За кого е погоден ТП?',
        content:
          'ТП не е за секого. Формата е најсоодветна за мали, нискоризични дејности каде претприемачот работи самостојно:',
        items: [
          'Фриленсери — дизајнери, програмери, копирајтери, консултанти и други кои работат самостојно.',
          'Занаетчии — фризери, кројачи, часовничари, поправки и слични услужни дејности.',
          'Мали услужни дејности со низок ризик и мал обем на работа.',
          'Претприемачи кои сакаат брз и евтин старт без основачки капитал.',
          'Лица кои очекуваат приход под прагот за ДДВ и сакаат да користат паушал.',
        ],
        steps: null,
      },
      {
        title: 'Регистрација на ТП: чекор по чекор',
        content:
          'Регистрацијата на ТП е побрза и поевтина од регистрацијата на трговско друштво. Еве ги главните чекори:',
        items: null,
        steps: [
          { step: 'Изберете име и дејност', desc: 'Определете под кое име ќе тргувате и која е основната дејност (шифра на дејност). Проверете ја достапноста на името на порталот на Централниот регистар (crm.com.mk).' },
          { step: 'Регистрирајте се во Централен регистар (ЦРСМ)', desc: 'Поднесете пријава за упис на трговец поединец во Централниот регистар на РСМ. Регистрацијата може да се направи електронски или лично. По уписот добивате ЕМБС — идентификациски број на субјектот.' },
          { step: 'Добијте ЕДБ преку УЈП', desc: 'Регистрирајте се како даночен обврзник во Управата за јавни приходи (УЈП) и добијте ЕДБ (единствен даночен број). Ова е задолжително за да можете да фактурирате и да плаќате даноци.' },
          { step: 'Изберете даночен режим', desc: 'Одлучете дали ќе користите паушално оданочување (ако ги исполнувате условите) или редовно оданочување. Барањето за паушал се поднесува до УЈП во пропишаниот рок.' },
          { step: 'Отворете сметка и почнете да фактурирате', desc: 'Отворете деловна сметка во банка и почнете да издавате фактури за вашите услуги. Од првиот ден водете уредна евиденција на приходите.' },
        ],
      },
      {
        title: 'Даночен режим: паушал или редовно',
        content:
          'ТП има два основни начини на оданочување. Изборот зависи од вашиот приход и видот на дејноста:',
        items: [
          'Паушално оданочување — достапно ако ги исполнувате условите (меѓу другото, годишен приход под пропишаниот праг и дејноста да не е исклучена). Плаќате поедноставен фиксен данок без целосно книговодство.',
          'Редовно оданочување — ако не исполнувате услови за паушал или го надминете прагот. Данокот на доход е 10% на даночната основица.',
          'ДДВ регистрација — станува задолжителна кога годишниот промет ќе надмине 2.000.000 МКД. Тогаш го губите паушалниот статус и мора да пресметувате и плаќате ДДВ.',
          'Придонеси — покрај данокот, ТП плаќа и придонеси за пензиско и здравствено осигурување.',
        ],
        steps: null,
      },
      {
        title: 'Предности, мани и како Facturino помага',
        content:
          'ТП е брз и евтин за старт, но носи лична одговорност. Ако сте еднолична дејност, Facturino го олеснува фактурирањето и водењето евиденција без потреба од сметководствено знаење:',
        items: [
          'Предност: наједноставна и најевтина форма за старт, без основачки капитал.',
          'Предност: поедноставена администрација и можност за паушал.',
          'Мана: лична одговорност со целиот имот — ризик за подолжни дејности.',
          'Facturino: издавајте професионални фактури со македонски формат и сите задолжителни полиња за УЈП.',
          'Facturino: следете ги приходите во реално време и добијте предупредување кога се приближувате до прагот за ДДВ.',
          'Facturino: бесплатен почетен план — идеален за еднолична дејност како ТП.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'ДООЕЛ, ДОО или ТП?' },
      { slug: 'paushal-ili-ddv', title: 'Паушалец или ДДВ обврзник?' },
      { slug: 'za-stranci', title: 'Отворање фирма за странци' },
    ],
    bottomCta: {
      title: 'Регистриравте ТП? Почнете да фактурирате',
      subtitle: 'Facturino е совршен за еднолична дејност — брзо издавање фактури и следење приходи, без сметководствено знаење.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Start a company',
    tag: 'Sole Proprietor',
    title: 'Sole Proprietor (Trgovec Poedinec): Registration, Taxes and Obligations',
    publishDate: 'August 20, 2026',
    readTime: '8 min read',
    intro:
      'A sole proprietor (Trgovec Poedinec, or TP) is the simplest way to start a business in North Macedonia. It is a natural person carrying out a registered commercial activity under their own name, with no required share capital. TP is a popular choice for freelancers, craftspeople, and small low-risk service businesses. But unlike a DOOEL, a sole proprietor is liable with all their personal assets. This guide explains what a TP is, how to register, which taxes apply, and when this form is the right choice.',
    sections: [
      {
        title: 'What is a sole proprietor (TP)?',
        content:
          'A sole proprietor is a natural person who independently carries out a registered commercial activity for profit. Unlike commercial companies (DOOEL, DOO), a TP is not a separate legal entity — it is the entrepreneur themselves, running an activity registered under their own name. There is no legally prescribed minimum share capital, which makes the TP the simplest and cheapest form to start. That is exactly why it suits individuals who want to start invoicing for their work quickly and without bureaucracy.',
        items: null,
        steps: null,
      },
      {
        title: 'TP vs DOOEL: the key difference',
        content:
          'The most important difference between a TP and a DOOEL is liability. Consider this difference carefully before deciding:',
        items: [
          'Liability: A TP is liable with all their personal assets for the debts of the business. A DOOEL is a legal entity with limited liability — liable only up to the amount of the share capital.',
          'Share capital: A TP has no prescribed minimum capital. A DOOEL requires a minimum capital of EUR 5,000 (in MKD equivalent).',
          'Legal status: A TP is not a separate legal entity — it is the natural person themselves. A DOOEL is a legal entity separate from its founder.',
          'Complexity: A TP has simplified administration and can often use lump-sum taxation. A DOOEL keeps full double-entry bookkeeping.',
          'Risk: A TP suits low-risk activities. For activities with larger debts or risk, the limited liability of a DOOEL is safer.',
        ],
        steps: null,
      },
      {
        title: 'Who is a TP suited for?',
        content:
          'A TP is not for everyone. The form is most appropriate for small, low-risk activities where the entrepreneur works independently:',
        items: [
          'Freelancers — designers, developers, copywriters, consultants, and others who work independently.',
          'Craftspeople — hairdressers, tailors, watchmakers, repairs, and similar service activities.',
          'Small service businesses with low risk and low turnover.',
          'Entrepreneurs who want a fast, cheap start with no share capital.',
          'Individuals expecting income below the VAT threshold who want to use lump-sum taxation.',
        ],
        steps: null,
      },
      {
        title: 'Registering a TP: step by step',
        content:
          'Registering a TP is faster and cheaper than registering a commercial company. Here are the main steps:',
        items: null,
        steps: [
          { step: 'Choose a name and activity', desc: 'Decide the name you will trade under and your primary activity (activity code). Check name availability on the Central Registry portal (crm.com.mk).' },
          { step: 'Register at the Central Registry (CRMS)', desc: 'Submit an application to register as a sole proprietor at the Central Registry of North Macedonia. Registration can be done electronically or in person. After registration you receive an EMBS — the entity identification number.' },
          { step: 'Obtain an EDB via UJP', desc: 'Register as a taxpayer with the Public Revenue Office (UJP) and obtain an EDB (unique tax number). This is required in order to issue invoices and pay taxes.' },
          { step: 'Choose a tax regime', desc: 'Decide whether to use lump-sum taxation (if you meet the conditions) or regular taxation. The lump-sum application is submitted to UJP within the prescribed deadline.' },
          { step: 'Open an account and start invoicing', desc: 'Open a business bank account and start issuing invoices for your services. Keep orderly records of your income from day one.' },
        ],
      },
      {
        title: 'Tax regime: lump-sum or regular',
        content:
          'A TP has two basic ways of being taxed. The choice depends on your income and the type of activity:',
        items: [
          'Lump-sum taxation — available if you meet the conditions (among others, annual income below the prescribed threshold and the activity not being excluded). You pay a simplified fixed tax without full bookkeeping.',
          'Regular taxation — if you do not qualify for lump-sum or exceed the threshold. Income tax is 10% on the tax base.',
          'VAT registration — becomes mandatory once annual turnover exceeds 2,000,000 MKD. At that point you lose lump-sum status and must calculate and pay VAT.',
          'Contributions — in addition to tax, a TP pays pension and health insurance contributions.',
        ],
        steps: null,
      },
      {
        title: 'Advantages, drawbacks, and how Facturino helps',
        content:
          'A TP is fast and cheap to start, but carries personal liability. If you are a one-person business, Facturino makes invoicing and record-keeping easy without accounting knowledge:',
        items: [
          'Advantage: the simplest and cheapest form to start, with no share capital.',
          'Advantage: simplified administration and the option of lump-sum taxation.',
          'Drawback: personal liability with all your assets — a risk for higher-debt activities.',
          'Facturino: issue professional invoices in Macedonian format with all mandatory UJP fields.',
          'Facturino: track income in real time and get a warning when you approach the VAT threshold.',
          'Facturino: a free starter plan — ideal for a one-person business like a TP.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO or TP?' },
      { slug: 'paushal-ili-ddv', title: 'Lump-Sum or VAT Taxpayer?' },
      { slug: 'za-stranci', title: 'Registering a Company as a Foreigner' },
    ],
    bottomCta: {
      title: 'Registered a TP? Start invoicing',
      subtitle: 'Facturino is perfect for a one-person business — fast invoicing and income tracking, no accounting knowledge needed.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Hap firmë',
    tag: 'Tregtar individual',
    title: 'Tregtar individual (TP): regjistrimi, tatimet dhe detyrimet',
    publishDate: '20 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Tregtari individual (Trgovec Poedinec, ose TP) është forma më e thjeshtë për të filluar një biznes në Maqedoni. Është një person fizik që ushtron një veprimtari tregtare të regjistruar nën emrin e vet, pa kapital themeltar të kërkuar. TP është zgjedhje e njohur për punëtorë të pavarur, zejtarë dhe biznese të vogla shërbimi me rrezik të ulët. Por ndryshe nga DOOEL, tregtari individual përgjigjet me gjithë pasurinë e vet personale. Ky udhëzues shpjegon çfarë është TP, si regjistrohet, cilat tatime paguhen dhe kur kjo formë është zgjedhja e duhur.',
    sections: [
      {
        title: 'Çfarë është tregtari individual (TP)?',
        content:
          'Tregtari individual është një person fizik që ushtron në mënyrë të pavarur një veprimtari tregtare të regjistruar për fitim. Ndryshe nga shoqëritë tregtare (DOOEL, DOO), TP nuk është subjekt juridik i veçantë — është vetë sipërmarrësi që ka regjistruar veprimtari nën emrin e vet. Nuk ka kapital themeltar minimal të përcaktuar me ligj, gjë që e bën TP formën më të thjeshtë dhe më të lirë për të filluar. Pikërisht për këtë është i përshtatshëm për persona fizikë që duan të fillojnë të faturojnë për punën e tyre shpejt dhe pa burokraci.',
        items: null,
        steps: null,
      },
      {
        title: 'TP kundrejt DOOEL: dallimi kryesor',
        content:
          'Dallimi më i rëndësishëm midis TP dhe DOOEL është përgjegjësia. Merreni parasysh me kujdes këtë dallim para se të vendosni:',
        items: [
          'Përgjegjësia: TP përgjigjet me gjithë pasurinë e vet personale për borxhet e veprimtarisë. DOOEL është subjekt juridik me përgjegjësi të kufizuar — përgjigjet vetëm deri në shumën e kapitalit themeltar.',
          'Kapitali themeltar: TP nuk ka kapital minimal të përcaktuar. DOOEL kërkon kapital minimal prej 5.000 EUR (në ekuivalent MKD).',
          'Statusi juridik: TP nuk është subjekt juridik i veçantë — është vetë personi fizik. DOOEL është subjekt juridik i ndarë nga themeluesi.',
          'Ndërlikueshmëria: TP ka administrim të thjeshtuar dhe shpesh mund të përdorë tatim paushall. DOOEL mban kontabilitet të plotë me regjistrim të dyfishtë.',
          'Rreziku: TP është i përshtatshëm për veprimtari me rrezik të ulët. Për veprimtari me borxhe ose rrezik më të madh, përgjegjësia e kufizuar e DOOEL është më e sigurt.',
        ],
        steps: null,
      },
      {
        title: 'Për kë përshtatet TP?',
        content:
          'TP nuk është për të gjithë. Forma është më e përshtatshme për veprimtari të vogla me rrezik të ulët ku sipërmarrësi punon në mënyrë të pavarur:',
        items: [
          'Punëtorë të pavarur — dizajnerë, programues, kopistë, konsulentë dhe të tjerë që punojnë vetë.',
          'Zejtarë — floktarë, rrobaqepës, orëndreqës, riparime dhe veprimtari të ngjashme shërbimi.',
          'Biznese të vogla shërbimi me rrezik të ulët dhe qarkullim të vogël.',
          'Sipërmarrës që duan një fillim të shpejtë dhe të lirë pa kapital themeltar.',
          'Persona që presin të ardhura nën pragun e TVSH-së dhe duan të përdorin tatim paushall.',
        ],
        steps: null,
      },
      {
        title: 'Regjistrimi i TP: hap pas hapi',
        content:
          'Regjistrimi i TP është më i shpejtë dhe më i lirë se regjistrimi i një shoqërie tregtare. Ja hapat kryesorë:',
        items: null,
        steps: [
          { step: 'Zgjidhni emrin dhe veprimtarinë', desc: 'Përcaktoni emrin nën të cilin do të tregtoni dhe veprimtarinë kryesore (kodi i veprimtarisë). Kontrolloni disponueshmërinë e emrit në portalin e Regjistrit Qendror (crm.com.mk).' },
          { step: 'Regjistrohuni në Regjistrin Qendror (RQRM)', desc: 'Dorëzoni aplikimin për regjistrim si tregtar individual në Regjistrin Qendror të Maqedonisë. Regjistrimi mund të bëhet elektronikisht ose personalisht. Pas regjistrimit merrni EMBS — numrin e identifikimit të subjektit.' },
          { step: 'Merrni EDB përmes UJP', desc: 'Regjistrohuni si tatimpagues në Zyrën e të Ardhurave Publike (UJP) dhe merrni EDB (numrin unik tatimor). Kjo është e detyrueshme për të lëshuar fatura dhe për të paguar tatime.' },
          { step: 'Zgjidhni regjimin tatimor', desc: 'Vendosni nëse do të përdorni tatim paushall (nëse i plotësoni kushtet) apo tatim të rregullt. Kërkesa për paushall dorëzohet në UJP brenda afatit të përcaktuar.' },
          { step: 'Hapni llogari dhe filloni të faturoni', desc: 'Hapni një llogari bankare biznesi dhe filloni të lëshoni fatura për shërbimet tuaja. Mbani evidencë të rregullt të të ardhurave që nga dita e parë.' },
        ],
      },
      {
        title: 'Regjimi tatimor: paushall ose i rregullt',
        content:
          'TP ka dy mënyra bazë të tatimit. Zgjedhja varet nga të ardhurat tuaja dhe lloji i veprimtarisë:',
        items: [
          'Tatim paushall — i disponueshëm nëse i plotësoni kushtet (ndër të tjera, të ardhura vjetore nën pragun e përcaktuar dhe veprimtaria të mos jetë e përjashtuar). Paguani një tatim fiks të thjeshtuar pa kontabilitet të plotë.',
          'Tatim i rregullt — nëse nuk kualifikoheni për paushall ose e tejkaloni pragun. Tatimi mbi të ardhurat është 10% mbi bazën tatimore.',
          'Regjistrimi për TVSH — bëhet i detyrueshëm kur qarkullimi vjetor tejkalon 2.000.000 MKD. Atëherë humbisni statusin paushall dhe duhet të llogaritni e paguani TVSH.',
          'Kontributet — përveç tatimit, TP paguan edhe kontribute për sigurim pensional dhe shëndetësor.',
        ],
        steps: null,
      },
      {
        title: 'Përparësitë, mangësitë dhe si ndihmon Facturino',
        content:
          'TP është i shpejtë dhe i lirë për të filluar, por bart përgjegjësi personale. Nëse jeni biznes njëpersonash, Facturino e lehtëson faturimin dhe mbajtjen e evidencës pa nevojë për njohuri kontabiliteti:',
        items: [
          'Përparësi: forma më e thjeshtë dhe më e lirë për të filluar, pa kapital themeltar.',
          'Përparësi: administrim i thjeshtuar dhe mundësia e tatimit paushall.',
          'Mangësi: përgjegjësi personale me gjithë pasurinë — rrezik për veprimtari me borxhe më të larta.',
          'Facturino: lëshoni fatura profesionale në format maqedonas me të gjitha fushat e detyrueshme të UJP.',
          'Facturino: ndiqni të ardhurat në kohë reale dhe merrni paralajmërim kur i afroheni pragut të TVSH-së.',
          'Facturino: plan fillestar falas — ideal për një biznes njëpersonash si TP.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO apo TP?' },
      { slug: 'paushal-ili-ddv', title: 'Tatimpagues paushall apo i TVSH-së?' },
      { slug: 'za-stranci', title: 'Hapja e firmës për të huajt' },
    ],
    bottomCta: {
      title: 'Regjistruat TP? Filloni të faturoni',
      subtitle: 'Facturino është perfekt për një biznes njëpersonash — faturim i shpejtë dhe ndjekje të ardhurash, pa nevojë për njohuri kontabiliteti.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Şirket kur',
    tag: 'Şahıs işletmesi',
    title: 'Şahıs İşletmesi (Trgovec Poedinec): Kayıt, Vergiler ve Yükümlülükler',
    publishDate: '20 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Şahıs işletmesi (Trgovec Poedinec veya TP), Kuzey Makedonya\'da iş kurmanın en basit yoludur. Kendi adı altında kayıtlı ticari faaliyet yürüten, sermaye gerektirmeyen bir gerçek kişidir. TP; serbest çalışanlar, zanaatkarlar ve düşük riskli küçük hizmet işletmeleri için popüler bir tercihtir. Ancak DOOEL\'in aksine, şahıs işletmesi tüm kişisel varlıklarıyla sorumludur. Bu rehber TP\'nin ne olduğunu, nasıl kaydedileceğini, hangi vergilerin uygulandığını ve bu biçimin ne zaman doğru seçim olduğunu açıklar.',
    sections: [
      {
        title: 'Şahıs işletmesi (TP) nedir?',
        content:
          'Şahıs işletmesi, kar amacıyla bağımsız olarak kayıtlı ticari faaliyet yürüten bir gerçek kişidir. Ticari şirketlerin (DOOEL, DOO) aksine, TP ayrı bir tüzel kişi değildir — kendi adı altında faaliyet kaydettirmiş girişimcinin kendisidir. Yasal olarak öngörülmüş bir asgari sermaye yoktur; bu da TP\'yi başlamak için en basit ve en ucuz biçim yapar. Tam da bu nedenle, işleri için hızlı ve bürokrasisiz fatura kesmeye başlamak isteyen gerçek kişiler için uygundur.',
        items: null,
        steps: null,
      },
      {
        title: 'TP ve DOOEL: temel fark',
        content:
          'TP ile DOOEL arasındaki en önemli fark sorumluluktur. Karar vermeden önce bu farkı dikkatlice değerlendirin:',
        items: [
          'Sorumluluk: TP, işletmenin borçları için tüm kişisel varlıklarıyla sorumludur. DOOEL, sınırlı sorumlu bir tüzel kişidir — yalnızca sermaye tutarı kadar sorumludur.',
          'Sermaye: TP\'nin öngörülmüş asgari sermayesi yoktur. DOOEL, minimum 5.000 EUR (MKD karşılığı) sermaye gerektirir.',
          'Hukuki statü: TP ayrı bir tüzel kişi değildir — gerçek kişinin kendisidir. DOOEL, kurucusundan ayrı bir tüzel kişidir.',
          'Karmaşıklık: TP\'nin basitleştirilmiş bir yönetimi vardır ve genellikle götürü vergilendirme kullanabilir. DOOEL tam çift taraflı muhasebe tutar.',
          'Risk: TP düşük riskli faaliyetler için uygundur. Daha yüksek borç veya risk içeren faaliyetler için DOOEL\'in sınırlı sorumluluğu daha güvenlidir.',
        ],
        steps: null,
      },
      {
        title: 'TP kimin için uygundur?',
        content:
          'TP herkes için değildir. Bu biçim, girişimcinin bağımsız çalıştığı küçük, düşük riskli faaliyetler için en uygunudur:',
        items: [
          'Serbest çalışanlar — tasarımcılar, geliştiriciler, metin yazarları, danışmanlar ve bağımsız çalışan diğerleri.',
          'Zanaatkarlar — kuaförler, terziler, saatçiler, tamirler ve benzeri hizmet faaliyetleri.',
          'Düşük riskli ve düşük cirolu küçük hizmet işletmeleri.',
          'Sermaye olmadan hızlı ve ucuz bir başlangıç isteyen girişimciler.',
          'KDV eşiğinin altında gelir bekleyen ve götürü vergilendirme kullanmak isteyen kişiler.',
        ],
        steps: null,
      },
      {
        title: 'TP kaydı: adım adım',
        content:
          'TP kaydı, bir ticari şirket kaydından daha hızlı ve daha ucuzdur. İşte başlıca adımlar:',
        items: null,
        steps: [
          { step: 'Ad ve faaliyet seçin', desc: 'Hangi ad altında ticaret yapacağınızı ve temel faaliyetinizi (faaliyet kodu) belirleyin. Merkez Sicil portalında (crm.com.mk) ad müsaitliğini kontrol edin.' },
          { step: 'Merkez Sicile (CRMS) kaydolun', desc: 'Kuzey Makedonya Merkez Sicilinde şahıs işletmesi olarak kayıt başvurusu yapın. Kayıt elektronik olarak veya şahsen yapılabilir. Kayıttan sonra EMBS — kuruluş kimlik numarası — alırsınız.' },
          { step: 'UJP üzerinden EDB alın', desc: 'Kamu Gelir İdaresine (UJP) vergi mükellefi olarak kaydolun ve EDB (benzersiz vergi numarası) alın. Fatura kesmek ve vergi ödemek için bu zorunludur.' },
          { step: 'Vergi rejimini seçin', desc: 'Götürü vergilendirme (koşulları karşılıyorsanız) mı yoksa normal vergilendirme mi kullanacağınıza karar verin. Götürü başvurusu öngörülen süre içinde UJP\'ye sunulur.' },
          { step: 'Hesap açın ve fatura kesmeye başlayın', desc: 'Ticari banka hesabı açın ve hizmetleriniz için fatura kesmeye başlayın. İlk günden itibaren gelirlerinizin düzenli kaydını tutun.' },
        ],
      },
      {
        title: 'Vergi rejimi: götürü veya normal',
        content:
          'TP\'nin iki temel vergilendirme yolu vardır. Seçim, gelirinize ve faaliyet türüne bağlıdır:',
        items: [
          'Götürü vergilendirme — koşulları karşılarsanız kullanılabilir (diğerlerinin yanı sıra, öngörülen eşiğin altında yıllık gelir ve faaliyetin hariç tutulmamış olması). Tam muhasebe olmadan basitleştirilmiş sabit vergi ödersiniz.',
          'Normal vergilendirme — götürü için uygun değilseniz veya eşiği aşarsanız. Gelir vergisi, vergi matrahı üzerinden %10\'dur.',
          'KDV kaydı — yıllık ciro 2.000.000 MKD\'yi aştığında zorunlu hale gelir. O noktada götürü statüsünü kaybeder ve KDV hesaplayıp ödemeniz gerekir.',
          'Katkılar — verginin yanı sıra TP, emeklilik ve sağlık sigortası katkıları da öder.',
        ],
        steps: null,
      },
      {
        title: 'Avantajlar, dezavantajlar ve Facturino nasıl yardımcı olur',
        content:
          'TP başlamak için hızlı ve ucuzdur, ancak kişisel sorumluluk taşır. Tek kişilik bir işletmeyseniz, Facturino muhasebe bilgisi olmadan faturalamayı ve kayıt tutmayı kolaylaştırır:',
        items: [
          'Avantaj: sermayesiz, başlamak için en basit ve en ucuz biçim.',
          'Avantaj: basitleştirilmiş yönetim ve götürü vergilendirme seçeneği.',
          'Dezavantaj: tüm varlıklarınızla kişisel sorumluluk — daha yüksek borçlu faaliyetler için bir risk.',
          'Facturino: tüm zorunlu UJP alanlarıyla Makedon formatında profesyonel faturalar düzenleyin.',
          'Facturino: geliri gerçek zamanlı takip edin ve KDV eşiğine yaklaştığınızda uyarı alın.',
          'Facturino: ücretsiz başlangıç planı — TP gibi tek kişilik bir işletme için ideal.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO mu yoksa TP mi?' },
      { slug: 'paushal-ili-ddv', title: 'Götürü mü yoksa KDV mükellefi mi?' },
      { slug: 'za-stranci', title: 'Yabancılar için şirket kurma' },
    ],
    bottomCta: {
      title: 'TP kaydettiniz mi? Fatura kesmeye başlayın',
      subtitle: 'Facturino tek kişilik bir işletme için mükemmeldir — hızlı faturalama ve gelir takibi, muhasebe bilgisi gerekmez.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function TrgovecPoedinecPage({
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
    slug: 'trgovec-poedinec',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-20',
    tags: ['трговец поединец', 'sole proprietor', 'ТП', 'TP', 'ЦРСМ', 'УЈП', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: hubLabel, href: `/${locale}/otvori-firma` },
    { name: t.title, href: `/${locale}/otvori-firma/trgovec-poedinec` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Што е трговец поединец (ТП)?', answer: 'Трговец поединец е физичко лице кое самостојно врши регистрирана трговска дејност под свое име, без потреба од основачки капитал. За разлика од ДООЕЛ, ТП одговара со целиот свој личен имот.' },
        { question: 'Која е разликата меѓу ТП и ДООЕЛ?', answer: 'Клучната разлика е одговорноста. ТП одговара со целиот личен имот, додека ДООЕЛ е правно лице со ограничена одговорност до висината на основачкиот капитал. ТП нема пропишан минимален капитал, ДООЕЛ бара 5.000 EUR.' },
        { question: 'Каде се регистрира ТП?', answer: 'ТП се регистрира во Централниот регистар на РСМ (ЦРСМ), по што се добива ЕМБС. Потоа се регистрира како даночен обврзник во УЈП и се добива ЕДБ.' },
        { question: 'Може ли ТП да користи паушал?', answer: 'Да, трговец поединец може да користи паушално оданочување ако ги исполнува условите (годишен приход под пропишаниот праг и дејноста да не е исклучена). Во спротивно се применува редовно оданочување со данок на доход од 10%.' },
        { question: 'Кога ТП мора да се регистрира за ДДВ?', answer: 'ДДВ регистрацијата станува задолжителна кога годишниот промет ќе надмине 2.000.000 МКД. Во тој случај ТП го губи паушалниот статус.' },
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
