import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/otvori-firma', {
    title: {
      mk: 'Како да отворите фирма во Македонија 2026: целосен водич',
      en: 'How to Start a Company in North Macedonia 2026: Complete Guide',
      sq: 'Si të hapni firmë në Maqedoni 2026: udhëzues i plotë',
      tr: 'Kuzey Makedonya\'da Şirket Kurma 2026: Kapsamlı Rehber',
    },
    description: {
      mk: 'Целосен водич 2026 за отворање фирма во Македонија: правни форми (ДООЕЛ, ДОО, ТП, АД), избор на форма, регистрација во ЦРСМ, потребни документи, паушал или ДДВ и првите чекори по отворањето.',
      en: 'Complete 2026 guide to starting a company in North Macedonia: legal forms (DOOEL, DOO, sole trader, AD), choosing a form, Central Registry registration, required documents, lump-sum vs VAT, and first steps after opening.',
      sq: 'Udhëzues i plotë 2026 për hapjen e firmës në Maqedoni: format juridike (SHPKNJP, SHPK, tregtar individual, SHA), zgjedhja e formës, regjistrimi në RQRM, dokumentet, paushall apo TVSH dhe hapat e parë.',
      tr: '2026 Kuzey Makedonya\'da şirket kurma rehberi: hukuki biçimler (DOOEL, DOO, şahıs şirketi, AD), biçim seçimi, Merkez Sicil kaydı, gerekli belgeler, düz oranlı vergi mi KDV mi ve ilk adımlar.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Почетна',
    tag: 'Водич',
    title: 'Како да отворите фирма во Македонија 2026: целосен водич',
    publishDate: '20 август 2026',
    readTime: '11 мин читање',
    intro:
      'Отворањето фирма во Македонија е достижно за секого — процесот е дигитализиран и во најголем дел може да се заврши за неколку дена. Но пред да поднесете пријава, важно е да ја изберете вистинската правна форма, да ги подготвите документите и да разберете кои даночни обврски ве чекаат. Овој водич е појдовна точка на нашиот центар за отворање фирма: тука ги објаснуваме сите форми (ДООЕЛ, ДОО, ТП, АД), чекорите за регистрација во ЦРСМ, потребните документи, изборот меѓу паушал и ДДВ, како и првите чекори штом фирмата заживее. За секоја тема имате и подетален посебен водич поврзан на крајот.',
    sections: [
      {
        title: 'Кои правни форми постојат',
        content:
          'Првата одлука што ја носите е каков тип на друштво да регистрирате. Во Македонија најчесто се среќаваат четири форми, секоја со различна одговорност, капитал и намена:',
        items: [
          'ДООЕЛ (Друштво со ограничена одговорност на едно лице) — Најпопуларниот избор за мали бизниси. Еден основач, ограничена одговорност (личниот имот е заштитен), полно книговодство. Идеален за фриленсери, продавници и услужни дејности кои растат.',
          'ДОО (Друштво со ограничена одговорност) — Иста форма како ДООЕЛ, но со двајца или повеќе основачи и договор за управување помеѓу содружниците. Погоден за партнерства и заеднички проекти.',
          'ТП (Трговец поединец) — Физичко лице кое тргува под свое име. Нема основен капитал, но основачот одговара со целиот свој личен имот. Наједноставна форма за фриленсери и мали услужни дејности.',
          'АД (Акционерско друштво) — За поголеми бизниси со повеќе акционери и повисок капитал. Ретко се користи за стартапи, но е потребен за одредени регулирани дејности (осигурување, банки).',
        ],
        steps: null,
      },
      {
        title: 'Како да изберете форма',
        content:
          'Нема универзално „најдобра“ форма — изборот зависи од бројот на основачи, ризикот, планираниот промет и дали ќе вработувате. Еве едноставни насоки:',
        items: [
          'Изберете ДООЕЛ ако сте единствен основач, сакате ограничена одговорност и планирате бизнис што расте — ова е случајот за огромното мнозинство нови фирми.',
          'Изберете ДОО ако основате заедно со партнер или повеќе лица и ви треба јасен договор за управување и поделба на удели.',
          'Изберете ТП ако сте фриленсер или мала услужна дејност со низок ризик, сакате наједноставна администрација и немате потреба од ограничена одговорност.',
          'Размислете за АД само ако планирате поголем капитал, повеќе акционери или дејност која законски бара акционерско друштво.',
          'Ако се двоумите, ДООЕЛ е најбезбедниот стандарден избор — комбинира заштита на личниот имот со едноставна структура на еден основач.',
        ],
        steps: null,
      },
      {
        title: 'Чекори за регистрација во ЦРСМ',
        content:
          'Регистрацијата се води преку Централниот регистар на Република Северна Македонија (ЦРСМ). Процесот е ист во основа за сите форми, а разликите се во документите. Еве го текот чекор по чекор:',
        items: null,
        steps: [
          { step: 'Подгответе документи и проверете име', desc: 'Одлучете за форма, подгответе лична карта/пасош на основачот и проверете дали саканото име е слободно на порталот на ЦРСМ. Името мора да биде уникатно; може да го резервирате пред регистрацијата.' },
          { step: 'Поднесете пријава во Централен регистар (ЦРСМ)', desc: 'Пријавата се поднесува електронски преку системот на ЦРСМ или преку овластен регистрационен агент. За ДООЕЛ обично се приложуваат изјава за основање (нотарски заверена), одлука за управител и адреса на седиште.' },
          { step: 'Добијте ЕМБС и ЕДБ', desc: 'По одобрувањето, ЦРСМ ви издава решение за упис и ЕМБС (единствен матичен број на субјект). Даночниот број ЕДБ се доделува преку УЈП — со тоа фирмата е официјално основана.' },
          { step: 'Регистрирајте се во УЈП', desc: 'Управата за јавни приходи (УЈП) го евидентира новиот даночен обврзник. Тука се определува и вашиот даночен режим — паушал или ДДВ обврзник (повеќе подолу).' },
          { step: 'Отворете банкарска сметка', desc: 'Со решението за упис и ЕДБ отворете деловна банкарска сметка. Споредете провизии — тие се разликуваат по банки. На оваа сметка ќе примате уплати и ќе ги плаќате обврските.' },
        ],
      },
      {
        title: 'Потребни документи и капитал',
        content:
          'Точната листа зависи од формата и од тоа дали регистрирате самостојно или преку агент. Ова се вообичаените ставки за ДООЕЛ — секогаш проверете ги актуелните барања на ЦРСМ пред поднесување, бидејќи тие може да се менуваат:',
        items: [
          'Лична карта или пасош на основачот (и на управителот, ако е различно лице).',
          'Изјава за основање на друштвото, нотарски заверена.',
          'Одлука за именување на управител.',
          'Адреса на седиште на фирмата.',
          'Основен капитал — уплатен согласно законските барања за избраната форма. За ТП не се бара основен капитал, додека акционерските друштва имаат повисоки прагови. Точниот износ и начин на уплата проверете ги кај ЦРСМ.',
          'Совет: конкретните износи на такси и капитал може да се менуваат — не потпирајте се на застарени бројки, туку проверете директно кај ЦРСМ или кај овластен агент.',
        ],
        steps: null,
      },
      {
        title: 'Даночен режим: паушал или ДДВ',
        content:
          'Штом фирмата е основана, треба да го разберете вашиот даночен режим. Двете клучни прашања се дали може да работите како паушалец и кога станувате ДДВ обврзник:',
        items: [
          'Паушал — поедноставен режим со фиксен данок наменет за помали дејности што ги исполнуваат условите. Помалку администрација, но со ограничувања по промет и вид на дејност.',
          'ДДВ регистрација станува задолжителна кога годишниот промет ќе надмине 2.000.000 МКД. Тогаш издавате фактури со ДДВ и поднесувате редовни ДДВ пријави.',
          'Доброволна ДДВ регистрација е можна и под прагот — корисна ако вашите клиенти се ДДВ обврзници и сакате да го вратите влезниот ДДВ.',
          'Изборот влијае на цените, книговодството и обврските — ако не сте сигурни, консултирајте се со сметководител пред да одлучите.',
        ],
        steps: null,
      },
      {
        title: 'Првите чекори по отворањето',
        content:
          'Регистрацијата е само почеток. Веднаш штом фирмата заживее, имате неколку непосредни задачи што ја поставуваат основата за уредно работење:',
        items: [
          'Отворете деловна банкарска сметка и држете ги личните и деловните средства одвоени.',
          'Средете ја евиденцијата кон УЈП — потврдете го даночниот режим и роковите за пријави.',
          'Почнете да издавате фактури за секоја продажба или услуга уште од првиот ден.',
          'Подгответе се за е-фактура — електронското фактурирање станува задолжително, па изберете софтвер кој поддржува UBL 2.1 и е спремен за УЈП платформата.',
          'Воспоставете книговодство од самиот старт — не чекајте документите да се натрупаат.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага на новите фирми',
        content:
          'Facturino е создаден токму за нови фирми во Македонија. Веднаш по регистрацијата можете да почнете да фактурирате професионално, без сметководствено предзнаење — системот ве води низ секој чекор и е спремен за идните обврски.',
        items: [
          'Издавајте фактури во македонски формат со сите задолжителни полиња за УЈП — уште истиот ден.',
          'Автоматска пресметка на ДДВ и следење на приходи и расходи во реално време.',
          'Спремен за е-фактура: поддршка за UBL 2.1 и електронско фактурирање пред роковите.',
          'Извештаи и евиденција што ги олеснуваат обврските кон УЈП.',
          'Бесплатен почетен план — без ризик и без кредитна картичка.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Продлабочете се',
    relatedArticles: [
      { href: `/mk/otvori-firma/dooel-ili-doo`, title: 'ДООЕЛ, ДОО или ТП — која форма?' },
      { href: `/mk/otvori-firma/paushal-ili-ddv`, title: 'Паушалец или ДДВ обврзник?' },
      { href: `/mk/otvori-firma/za-stranci`, title: 'Отворање фирма за странци' },
      { href: `/mk/otvori-firma/trgovec-poedinec`, title: 'Трговец поединец' },
      { href: `/mk/blog/registracija-firma-cekor-po-cekor`, title: 'Регистрација чекор-по-чекор' },
      { href: `/mk/blog/dooel-vodich-2026`, title: 'ДООЕЛ: даноци и обврски' },
      { href: `/mk/blog/paket-za-nova-firma`, title: 'Чеклиста за нова фирма (првите 30 дена)' },
    ],
    bottomCta: {
      title: 'Штотуку отворивте фирма?',
      subtitle: 'Почнете да фактурирате уште денес — Facturino е спремен за е-фактура. Бесплатен план за почеток.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Home',
    tag: 'Guide',
    title: 'How to Start a Company in North Macedonia 2026: Complete Guide',
    publishDate: 'August 20, 2026',
    readTime: '11 min read',
    intro:
      'Starting a company in North Macedonia is within reach for anyone — the process is digitized and can largely be completed in a few days. But before you file, it matters that you choose the right legal form, prepare your documents, and understand which tax obligations await. This guide is the starting point of our company-formation hub: here we explain all the forms (DOOEL, DOO, sole trader, AD), the steps to register at the Central Registry, the required documents, the choice between lump-sum tax and VAT, and the first steps once the company is live. Each topic has a dedicated, more detailed guide linked at the end.',
    sections: [
      {
        title: 'What legal forms exist',
        content:
          'The first decision you make is which type of entity to register. In North Macedonia four forms are most common, each with different liability, capital, and purpose:',
        items: [
          'DOOEL (single-member LLC) — The most popular choice for small businesses. One founder, limited liability (personal assets protected), full bookkeeping. Ideal for freelancers, shops, and growing service businesses.',
          'DOO (multi-member LLC) — The same form as a DOOEL, but with two or more founders and a management agreement between partners. Suited for partnerships and joint projects.',
          'TP (sole trader) — A natural person trading under their own name. No share capital, but the founder is liable with all personal assets. The simplest form for freelancers and small service businesses.',
          'AD (joint-stock company) — For larger businesses with multiple shareholders and higher capital. Rarely used for startups, but required for certain regulated activities (insurance, banking).',
        ],
        steps: null,
      },
      {
        title: 'How to choose a form',
        content:
          'There is no universally "best" form — the choice depends on the number of founders, risk, expected turnover, and whether you will hire. Here are simple guidelines:',
        items: [
          'Choose a DOOEL if you are the sole founder, want limited liability, and plan a growing business — this fits the vast majority of new companies.',
          'Choose a DOO if you are founding together with a partner or several people and need a clear management agreement and division of shares.',
          'Choose a TP if you are a freelancer or a low-risk small service business, want the simplest administration, and don\'t need limited liability.',
          'Consider an AD only if you plan larger capital, multiple shareholders, or an activity that legally requires a joint-stock company.',
          'If in doubt, a DOOEL is the safest default — it combines personal-asset protection with a simple single-founder structure.',
        ],
        steps: null,
      },
      {
        title: 'Steps to register at the Central Registry (CRMS)',
        content:
          'Registration runs through the Central Registry of the Republic of North Macedonia (CRMS). The process is essentially the same for all forms, with differences in the documents. Here is the flow step by step:',
        items: null,
        steps: [
          { step: 'Prepare documents and check the name', desc: 'Decide on a form, prepare the founder\'s ID card/passport, and check whether your desired name is available on the CRMS portal. The name must be unique; you can reserve it before registration.' },
          { step: 'Submit the application to the Central Registry (CRMS)', desc: 'The application is filed electronically through the CRMS system or via an authorized registration agent. For a DOOEL you typically attach a founding statement (notarized), a decision on the manager, and a registered office address.' },
          { step: 'Receive your EMBS and EDB', desc: 'Upon approval, CRMS issues a registration decision and an EMBS (unique entity identification number). The tax number (EDB) is assigned via UJP — at which point the company is officially established.' },
          { step: 'Register with UJP', desc: 'The Public Revenue Office (UJP) records the new taxpayer. This is also where your tax regime is set — lump-sum or VAT-registered (more below).' },
          { step: 'Open a bank account', desc: 'With the registration decision and EDB, open a business bank account. Compare fees — they vary between banks. This account is where you receive payments and pay your obligations.' },
        ],
      },
      {
        title: 'Required documents and capital',
        content:
          'The exact list depends on the form and whether you register yourself or through an agent. These are the usual items for a DOOEL — always verify the current CRMS requirements before filing, as they can change:',
        items: [
          'ID card or passport of the founder (and of the manager, if a different person).',
          'Founding statement of the company, notarized.',
          'Decision appointing a manager.',
          'Registered office address of the company.',
          'Share capital — paid in accordance with the legal requirements for the chosen form. A TP requires no share capital, while joint-stock companies have higher thresholds. Check the exact amount and payment method with CRMS.',
          'Tip: specific fee and capital amounts can change — don\'t rely on outdated figures, verify directly with CRMS or an authorized agent.',
        ],
        steps: null,
      },
      {
        title: 'Tax regime: lump-sum or VAT',
        content:
          'Once the company is established, you need to understand your tax regime. The two key questions are whether you can operate as a lump-sum taxpayer and when you become VAT-registered:',
        items: [
          'Lump-sum — a simplified regime with a fixed tax intended for smaller activities that meet the conditions. Less administration, but with limits on turnover and type of activity.',
          'VAT registration becomes mandatory when annual turnover exceeds 2,000,000 MKD. From then you issue invoices with VAT and file regular VAT returns.',
          'Voluntary VAT registration is possible below the threshold too — useful if your clients are VAT-registered and you want to reclaim input VAT.',
          'The choice affects pricing, bookkeeping, and obligations — if unsure, consult an accountant before deciding.',
        ],
        steps: null,
      },
      {
        title: 'First steps after opening',
        content:
          'Registration is only the beginning. As soon as the company is live, you have a few immediate tasks that set the foundation for orderly operations:',
        items: [
          'Open a business bank account and keep personal and business funds separate.',
          'Sort out your records with UJP — confirm the tax regime and filing deadlines.',
          'Start issuing invoices for every sale or service from day one.',
          'Get ready for e-invoicing — electronic invoicing is becoming mandatory, so choose software that supports UBL 2.1 and is ready for the UJP platform.',
          'Establish bookkeeping from the very start — don\'t wait for documents to pile up.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps new companies',
        content:
          'Facturino was built specifically for new companies in North Macedonia. Right after registration you can start invoicing professionally, with no prior accounting knowledge — the system guides you through every step and is ready for upcoming obligations.',
        items: [
          'Issue invoices in Macedonian format with all mandatory UJP fields — on the very same day.',
          'Automatic VAT calculation and real-time income and expense tracking.',
          'E-invoice ready: support for UBL 2.1 and electronic invoicing ahead of the deadlines.',
          'Reports and records that make UJP obligations easier.',
          'Free starter plan — no risk and no credit card.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Go deeper',
    relatedArticles: [
      { href: `/en/otvori-firma/dooel-ili-doo`, title: 'DOOEL, DOO or Sole Trader — which form?' },
      { href: `/en/otvori-firma/paushal-ili-ddv`, title: 'Lump-sum or VAT-registered?' },
      { href: `/en/otvori-firma/za-stranci`, title: 'Starting a company as a foreigner' },
      { href: `/en/otvori-firma/trgovec-poedinec`, title: 'Sole trader (TP)' },
      { href: `/en/blog/registracija-firma-cekor-po-cekor`, title: 'Registration step-by-step' },
      { href: `/en/blog/dooel-vodich-2026`, title: 'DOOEL: taxes and obligations' },
      { href: `/en/blog/paket-za-nova-firma`, title: 'New-company checklist (first 30 days)' },
    ],
    bottomCta: {
      title: 'Just opened a company?',
      subtitle: 'Start invoicing today — Facturino is e-invoice ready. Free plan to get started.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Ballina',
    tag: 'Udhëzues',
    title: 'Si të hapni firmë në Maqedoni 2026: udhëzues i plotë',
    publishDate: '20 gusht 2026',
    readTime: '11 min lexim',
    intro:
      'Hapja e një firme në Maqedoni është e arritshme për këdo — procesi është i dixhitalizuar dhe në pjesën më të madhe mund të përfundojë brenda disa ditëve. Por para se të aplikoni, është e rëndësishme të zgjidhni formën e duhur juridike, të përgatitni dokumentet dhe të kuptoni cilat detyrime tatimore ju presin. Ky udhëzues është pikënisja e qendrës sonë për hapjen e firmës: këtu shpjegojmë të gjitha format (SHPKNJP, SHPK, tregtar individual, SHA), hapat e regjistrimit në RQRM, dokumentet e nevojshme, zgjedhjen midis paushallit dhe TVSH-së, si dhe hapat e parë sapo firma të fillojë punën. Për çdo temë keni edhe një udhëzues të veçantë e më të detajuar të lidhur në fund.',
    sections: [
      {
        title: 'Cilat forma juridike ekzistojnë',
        content:
          'Vendimi i parë që merrni është cili lloj subjekti të regjistrohet. Në Maqedoni katër forma janë më të zakonshmet, secila me përgjegjësi, kapital dhe qëllim të ndryshëm:',
        items: [
          'SHPKNJP (SHPK me një anëtar / ДООЕЛ) — Zgjedhja më e popullarizuar për bizneset e vogla. Një themelues, përgjegjësi e kufizuar (pasuria personale e mbrojtur), kontabilitet i plotë. Ideale për freelancerë, dyqane dhe biznese shërbimesh në rritje.',
          'SHPK (SHPK me shumë anëtarë / ДОО) — E njëjta formë si SHPKNJP, por me dy ose më shumë themelues dhe marrëveshje menaxhimi midis partnerëve. E përshtatshme për partneritete dhe projekte të përbashkëta.',
          'TP (tregtar individual) — Person fizik që tregton nën emrin e vet. Pa kapital themeltar, por themeluesi përgjigjet me gjithë pasurinë personale. Forma më e thjeshtë për freelancerë dhe biznese të vogla shërbimi.',
          'SHA (shoqëri aksionare / АД) — Për biznese më të mëdha me disa aksionarë dhe kapital më të lartë. Përdoret rrallë për startup-e, por nevojitet për aktivitete të caktuara të rregulluara (sigurime, banka).',
        ],
        steps: null,
      },
      {
        title: 'Si të zgjidhni formën',
        content:
          'Nuk ka një formë universalisht "më të mirë" — zgjedhja varet nga numri i themeluesve, risku, qarkullimi i pritur dhe nëse do të punësoni. Ja disa udhëzime të thjeshta:',
        items: [
          'Zgjidhni SHPKNJP nëse jeni themeluesi i vetëm, dëshironi përgjegjësi të kufizuar dhe planifikoni një biznes në rritje — kjo vlen për shumicën dërrmuese të firmave të reja.',
          'Zgjidhni SHPK nëse themeloni së bashku me një partner ose disa persona dhe ju nevojitet marrëveshje e qartë menaxhimi dhe ndarje e kuotave.',
          'Zgjidhni TP nëse jeni freelancer ose biznes i vogël shërbimi me rrezik të ulët, dëshironi administrimin më të thjeshtë dhe nuk keni nevojë për përgjegjësi të kufizuar.',
          'Mendoni për SHA vetëm nëse planifikoni kapital më të madh, disa aksionarë ose një veprimtari që ligjërisht kërkon shoqëri aksionare.',
          'Nëse jeni në dilemë, SHPKNJP është zgjedhja standarde më e sigurt — kombinon mbrojtjen e pasurisë personale me një strukturë të thjeshtë me një themelues.',
        ],
        steps: null,
      },
      {
        title: 'Hapat për regjistrim në RQRM',
        content:
          'Regjistrimi bëhet përmes Regjistrit Qendror të Republikës së Maqedonisë së Veriut (RQRM). Procesi është në thelb i njëjtë për të gjitha format, me dallime në dokumente. Ja rrjedha hap pas hapi:',
        items: null,
        steps: [
          { step: 'Përgatitni dokumentet dhe kontrolloni emrin', desc: 'Vendosni për një formë, përgatitni letërnjoftimin/pasaportën e themeluesit dhe kontrolloni nëse emri i dëshiruar është i lirë në portalin e RQRM. Emri duhet të jetë unik; mund ta rezervoni para regjistrimit.' },
          { step: 'Dorëzoni aplikimin në Regjistrin Qendror (RQRM)', desc: 'Aplikimi dorëzohet elektronikisht përmes sistemit të RQRM ose përmes një agjenti të autorizuar regjistrimi. Për SHPKNJP zakonisht bashkëngjitni deklaratën e themelimit (të noterizuar), vendimin për menaxherin dhe adresën e selisë.' },
          { step: 'Merrni EMBS dhe EDB', desc: 'Pas miratimit, RQRM lëshon vendimin e regjistrimit dhe EMBS (numrin unik të identifikimit të subjektit). Numri tatimor (EDB) caktohet përmes DAP — me çka firma është themeluar zyrtarisht.' },
          { step: 'Regjistrohuni në DAP', desc: 'Drejtoria e të Ardhurave Publike (DAP) evidenton tatimpaguesin e ri. Këtu caktohet edhe regjimi juaj tatimor — paushall ose i regjistruar për TVSH (më poshtë).' },
          { step: 'Hapni llogari bankare', desc: 'Me vendimin e regjistrimit dhe EDB hapni llogari bankare biznesi. Krahasoni tarifat — ato ndryshojnë midis bankave. Në këtë llogari merrni pagesa dhe paguani detyrimet.' },
        ],
      },
      {
        title: 'Dokumentet e nevojshme dhe kapitali',
        content:
          'Lista e saktë varet nga forma dhe nga fakti nëse regjistroheni vetë apo përmes agjenti. Këto janë artikujt e zakonshëm për SHPKNJP — kontrolloni gjithmonë kërkesat aktuale të RQRM para dorëzimit, pasi mund të ndryshojnë:',
        items: [
          'Letërnjoftim ose pasaportë e themeluesit (dhe e menaxherit, nëse është person tjetër).',
          'Deklarata e themelimit të shoqërisë, e noterizuar.',
          'Vendimi për emërimin e menaxherit.',
          'Adresa e selisë së firmës.',
          'Kapitali themeltar — i paguar sipas kërkesave ligjore për formën e zgjedhur. TP nuk kërkon kapital themeltar, ndërsa shoqëritë aksionare kanë prag më të lartë. Kontrolloni shumën e saktë dhe mënyrën e pagesës në RQRM.',
          'Këshillë: shumat konkrete të tarifave dhe kapitalit mund të ndryshojnë — mos u mbështetni në shifra të vjetruara, verifikoni drejtpërdrejt në RQRM ose te një agjent i autorizuar.',
        ],
        steps: null,
      },
      {
        title: 'Regjimi tatimor: paushall apo TVSH',
        content:
          'Sapo firma të themelohet, duhet të kuptoni regjimin tuaj tatimor. Dy pyetjet kyçe janë nëse mund të punoni si paushalist dhe kur bëheni i regjistruar për TVSH:',
        items: [
          'Paushall — regjim i thjeshtuar me tatim fiks i destinuar për veprimtari më të vogla që plotësojnë kushtet. Më pak administrim, por me kufizime në qarkullim dhe lloj veprimtarie.',
          'Regjistrimi për TVSH bëhet i detyrueshëm kur qarkullimi vjetor kalon 2.000.000 MKD. Nga atëherë lëshoni fatura me TVSH dhe dorëzoni deklarata të rregullta TVSH.',
          'Regjistrimi vullnetar për TVSH është i mundur edhe nën prag — i dobishëm nëse klientët tuaj janë të regjistruar për TVSH dhe dëshironi të riktheni TVSH-në hyrëse.',
          'Zgjedhja ndikon në çmime, kontabilitet dhe detyrime — nëse jeni të pasigurt, konsultohuni me një kontabilist para se të vendosni.',
        ],
        steps: null,
      },
      {
        title: 'Hapat e parë pas hapjes',
        content:
          'Regjistrimi është vetëm fillimi. Sapo firma të fillojë punën, keni disa detyra të menjëhershme që vendosin themelin për një punë të rregullt:',
        items: [
          'Hapni llogari bankare biznesi dhe mbani të ndara fondet personale nga ato të biznesit.',
          'Rregulloni evidencën te DAP — konfirmoni regjimin tatimor dhe afatet e deklarimit.',
          'Filloni të lëshoni fatura për çdo shitje ose shërbim që nga dita e parë.',
          'Përgatituni për e-faturën — faturimi elektronik po bëhet i detyrueshëm, prandaj zgjidhni softuer që mbështet UBL 2.1 dhe është gati për platformën e DAP.',
          'Vendosni kontabilitetin që nga fillimi — mos prisni të grumbullohen dokumentet.',
        ],
        steps: null,
      },
      {
        title: 'Si i ndihmon Facturino firmat e reja',
        content:
          'Facturino u ndërtua posaçërisht për firmat e reja në Maqedoni. Menjëherë pas regjistrimit mund të filloni të faturoni në mënyrë profesionale, pa njohuri paraprake kontabiliteti — sistemi ju udhëzon në çdo hap dhe është gati për detyrimet e ardhshme.',
        items: [
          'Lëshoni fatura në format maqedonas me të gjitha fushat e detyrueshme të DAP — po atë ditë.',
          'Llogaritje automatike e TVSH-së dhe ndjekje në kohë reale e të ardhurave e shpenzimeve.',
          'Gati për e-faturë: mbështetje për UBL 2.1 dhe faturim elektronik para afateve.',
          'Raporte dhe evidenca që i lehtësojnë detyrimet ndaj DAP.',
          'Plan fillestar falas — pa rrezik dhe pa kartë krediti.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Thelloni njohuritë',
    relatedArticles: [
      { href: `/sq/otvori-firma/dooel-ili-doo`, title: 'SHPKNJP, SHPK apo TP — cila formë?' },
      { href: `/sq/otvori-firma/paushal-ili-ddv`, title: 'Paushalist apo i regjistruar për TVSH?' },
      { href: `/sq/otvori-firma/za-stranci`, title: 'Hapja e firmës për të huajt' },
      { href: `/sq/otvori-firma/trgovec-poedinec`, title: 'Tregtar individual (TP)' },
      { href: `/sq/blog/registracija-firma-cekor-po-cekor`, title: 'Regjistrimi hap pas hapi' },
      { href: `/sq/blog/dooel-vodich-2026`, title: 'SHPKNJP: tatimet dhe detyrimet' },
      { href: `/sq/blog/paket-za-nova-firma`, title: 'Çeklista për firmë të re (30 ditët e para)' },
    ],
    bottomCta: {
      title: 'Sapo hapët firmë?',
      subtitle: 'Filloni të faturoni sot — Facturino është gati për e-faturë. Plan falas për fillim.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Ana Sayfa',
    tag: 'Rehber',
    title: 'Kuzey Makedonya\'da Şirket Kurma 2026: Kapsamlı Rehber',
    publishDate: '20 Ağustos 2026',
    readTime: '11 dk okuma',
    intro:
      'Kuzey Makedonya\'da şirket kurmak herkesin ulaşabileceği bir şeydir — süreç dijitalleşmiştir ve büyük ölçüde birkaç günde tamamlanabilir. Ancak başvurmadan önce doğru hukuki biçimi seçmeniz, belgelerinizi hazırlamanız ve sizi hangi vergi yükümlülüklerinin beklediğini anlamanız önemlidir. Bu rehber, şirket kurma merkezimizin başlangıç noktasıdır: burada tüm biçimleri (DOOEL, DOO, şahıs şirketi, AD), Merkez Sicil kaydının adımlarını, gerekli belgeleri, düz oranlı vergi ile KDV arasındaki seçimi ve şirket faaliyete geçtikten sonraki ilk adımları açıklıyoruz. Her konu için sonda bağlantısı verilen ayrıntılı özel bir rehber de mevcuttur.',
    sections: [
      {
        title: 'Hangi hukuki biçimler var',
        content:
          'Vereceğiniz ilk karar hangi tür kuruluşu kaydettireceğinizdir. Kuzey Makedonya\'da dört biçim en yaygınıdır, her biri farklı sorumluluk, sermaye ve amaç taşır:',
        items: [
          'DOOEL (tek ortaklı limited şirket) — Küçük işletmeler için en popüler seçenek. Bir kurucu, sınırlı sorumluluk (kişisel varlıklar korunur), tam muhasebe. Serbest çalışanlar, dükkanlar ve büyüyen hizmet işletmeleri için ideal.',
          'DOO (çok ortaklı limited şirket) — DOOEL ile aynı biçim, ancak iki veya daha fazla kurucu ve ortaklar arası yönetim sözleşmesiyle. Ortaklıklar ve ortak projeler için uygun.',
          'TP (şahıs şirketi) — Kendi adıyla ticaret yapan gerçek kişi. Sermaye yok, ancak kurucu tüm kişisel varlıklarıyla sorumludur. Serbest çalışanlar ve küçük hizmet işletmeleri için en basit biçim.',
          'AD (anonim şirket) — Birden çok hissedarı ve daha yüksek sermayesi olan büyük işletmeler için. Startup\'lar için nadiren kullanılır, ancak belirli düzenlenmiş faaliyetler (sigorta, bankacılık) için gereklidir.',
        ],
        steps: null,
      },
      {
        title: 'Biçim nasıl seçilir',
        content:
          'Evrensel olarak "en iyi" bir biçim yoktur — seçim, kurucu sayısına, riske, beklenen ciroya ve çalışan alıp almayacağınıza bağlıdır. İşte basit yönergeler:',
        items: [
          'Tek kurucuysanız, sınırlı sorumluluk istiyorsanız ve büyüyen bir işletme planlıyorsanız DOOEL seçin — yeni şirketlerin büyük çoğunluğu için geçerlidir.',
          'Bir ortak veya birkaç kişiyle birlikte kuruyorsanız ve net bir yönetim sözleşmesi ile pay bölüşümü gerekiyorsa DOO seçin.',
          'Serbest çalışan veya düşük riskli küçük bir hizmet işletmesiyseniz, en basit yönetimi istiyorsanız ve sınırlı sorumluluğa ihtiyacınız yoksa TP seçin.',
          'Yalnızca daha büyük sermaye, birden çok hissedar veya yasal olarak anonim şirket gerektiren bir faaliyet planlıyorsanız AD\'yi düşünün.',
          'Kararsızsanız, DOOEL en güvenli varsayılan seçimdir — kişisel varlık korumasını basit tek kurucu yapısıyla birleştirir.',
        ],
        steps: null,
      },
      {
        title: 'Merkez Sicile (CRMS) kayıt adımları',
        content:
          'Kayıt, Kuzey Makedonya Cumhuriyeti Merkez Sicili (CRMS) üzerinden yürür. Süreç tüm biçimler için esasen aynıdır, farklar belgelerdedir. İşte adım adım akış:',
        items: null,
        steps: [
          { step: 'Belgeleri hazırlayın ve adı kontrol edin', desc: 'Bir biçime karar verin, kurucunun kimlik kartını/pasaportunu hazırlayın ve istediğiniz adın CRMS portalında müsait olup olmadığını kontrol edin. Ad benzersiz olmalı; kayıttan önce rezerve edebilirsiniz.' },
          { step: 'Başvuruyu Merkez Sicile (CRMS) sunun', desc: 'Başvuru, CRMS sistemi üzerinden elektronik olarak veya yetkili bir kayıt aracısı aracılığıyla yapılır. DOOEL için genellikle kuruluş beyanı (noter tasdikli), müdür kararı ve merkez ofis adresi eklersiniz.' },
          { step: 'EMBS ve EDB alın', desc: 'Onay üzerine CRMS bir kayıt kararı ve EMBS (benzersiz kuruluş kimlik numarası) verir. Vergi numarası (EDB) UJP üzerinden atanır — bu noktada şirket resmen kurulmuş olur.' },
          { step: 'UJP\'ye kaydolun', desc: 'Kamu Gelir İdaresi (UJP) yeni mükellefi kaydeder. Vergi rejiminiz de burada belirlenir — düz oranlı veya KDV mükellefi (aşağıda daha fazlası).' },
          { step: 'Banka hesabı açın', desc: 'Kayıt kararı ve EDB ile bir ticari banka hesabı açın. Ücretleri karşılaştırın — bankalar arasında değişir. Ödemeleri bu hesaptan alır ve yükümlülüklerinizi buradan ödersiniz.' },
        ],
      },
      {
        title: 'Gerekli belgeler ve sermaye',
        content:
          'Tam liste biçime ve kendiniz mi yoksa bir aracı aracılığıyla mı kaydolduğunuza bağlıdır. DOOEL için olağan kalemler şunlardır — değişebileceğinden, başvurudan önce daima güncel CRMS gereksinimlerini doğrulayın:',
        items: [
          'Kurucunun kimlik kartı veya pasaportu (farklı kişiyse müdürün de).',
          'Şirketin kuruluş beyanı, noter tasdikli.',
          'Müdür atama kararı.',
          'Şirketin merkez ofis adresi.',
          'Sermaye — seçilen biçim için yasal gereksinimlere uygun ödenir. TP sermaye gerektirmez, anonim şirketlerin ise daha yüksek eşiği vardır. Tam tutarı ve ödeme yöntemini CRMS ile kontrol edin.',
          'İpucu: belirli ücret ve sermaye tutarları değişebilir — güncel olmayan rakamlara güvenmeyin, doğrudan CRMS veya yetkili bir aracıdan doğrulayın.',
        ],
        steps: null,
      },
      {
        title: 'Vergi rejimi: düz oranlı mı KDV mi',
        content:
          'Şirket kurulduktan sonra vergi rejiminizi anlamanız gerekir. İki temel soru, düz oranlı mükellef olarak çalışıp çalışamayacağınız ve ne zaman KDV mükellefi olduğunuzdur:',
        items: [
          'Düz oranlı — koşulları karşılayan küçük faaliyetler için sabit vergili basitleştirilmiş bir rejim. Daha az yönetim, ancak ciro ve faaliyet türü konusunda sınırlarla.',
          'KDV kaydı, yıllık ciro 2.000.000 MKD\'yi aştığında zorunlu olur. O andan itibaren KDV\'li fatura düzenler ve düzenli KDV beyannameleri verirsiniz.',
          'Eşiğin altında gönüllü KDV kaydı da mümkündür — müşterileriniz KDV mükellefiyse ve girdi KDV\'sini geri almak istiyorsanız faydalıdır.',
          'Seçim fiyatlandırmayı, muhasebeyi ve yükümlülükleri etkiler — emin değilseniz karar vermeden önce bir muhasebeciye danışın.',
        ],
        steps: null,
      },
      {
        title: 'Açılıştan sonraki ilk adımlar',
        content:
          'Kayıt yalnızca başlangıçtır. Şirket faaliyete geçer geçmez, düzenli işleyişin temelini atan birkaç acil göreviniz olur:',
        items: [
          'Bir ticari banka hesabı açın ve kişisel ile ticari fonları ayrı tutun.',
          'UJP kayıtlarınızı düzenleyin — vergi rejimini ve beyanname tarihlerini onaylayın.',
          'İlk günden itibaren her satış veya hizmet için fatura düzenlemeye başlayın.',
          'E-faturaya hazırlanın — elektronik faturalama zorunlu hale geliyor, bu yüzden UBL 2.1 destekleyen ve UJP platformuna hazır bir yazılım seçin.',
          'Muhasebeyi en baştan kurun — belgelerin birikmesini beklemeyin.',
        ],
        steps: null,
      },
      {
        title: 'Facturino yeni şirketlere nasıl yardımcı olur',
        content:
          'Facturino özellikle Kuzey Makedonya\'daki yeni şirketler için inşa edilmiştir. Kayıttan hemen sonra ön muhasebe bilgisi olmadan profesyonelce fatura kesmeye başlayabilirsiniz — sistem sizi her adımda yönlendirir ve gelecekteki yükümlülüklere hazırdır.',
        items: [
          'Tüm zorunlu UJP alanlarıyla Makedon formatında fatura düzenleyin — aynı gün.',
          'Otomatik KDV hesaplaması ve gerçek zamanlı gelir gider takibi.',
          'E-faturaya hazır: UBL 2.1 desteği ve son tarihlerden önce elektronik faturalama.',
          'UJP yükümlülüklerini kolaylaştıran raporlar ve kayıtlar.',
          'Ücretsiz başlangıç planı — risk yok ve kredi kartı gerekmez.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Daha derine inin',
    relatedArticles: [
      { href: `/tr/otvori-firma/dooel-ili-doo`, title: 'DOOEL, DOO veya TP — hangi biçim?' },
      { href: `/tr/otvori-firma/paushal-ili-ddv`, title: 'Düz oranlı mı KDV mükellefi mi?' },
      { href: `/tr/otvori-firma/za-stranci`, title: 'Yabancılar için şirket kurma' },
      { href: `/tr/otvori-firma/trgovec-poedinec`, title: 'Şahıs şirketi (TP)' },
      { href: `/tr/blog/registracija-firma-cekor-po-cekor`, title: 'Adım adım kayıt' },
      { href: `/tr/blog/dooel-vodich-2026`, title: 'DOOEL: vergiler ve yükümlülükler' },
      { href: `/tr/blog/paket-za-nova-firma`, title: 'Yeni şirket kontrol listesi (ilk 30 gün)' },
    ],
    bottomCta: {
      title: 'Yeni mi şirket kurdunuz?',
      subtitle: 'Bugün fatura kesmeye başlayın — Facturino e-faturaya hazır. Başlamak için ücretsiz plan.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function OtvoriFirmaPillarPage({
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
    slug: '',
    pathPrefix: 'otvori-firma',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-20',
    tags: ['отворање фирма', 'company registration', 'DOOEL', 'ДОО', 'ЦРСМ', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: hubLabel, href: `/${locale}/otvori-firma` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Која правна форма да изберам за нова фирма?', answer: 'За огромното мнозинство нови бизниси најдобар избор е ДООЕЛ — еден основач, ограничена одговорност и заштитен личен имот. ДОО е за двајца или повеќе основачи, а ТП (трговец поединец) за фриленсери кои сакаат наједноставна форма без основен капитал.' },
        { question: 'Каде се регистрира фирма во Македонија?', answer: 'Фирмата се регистрира во Централниот регистар на Република Северна Македонија (ЦРСМ), електронски или преку овластен агент. По уписот добивате ЕМБС, а даночниот број ЕДБ се доделува преку УЈП.' },
        { question: 'Колку трае отворањето фирма?', answer: 'Процесот е дигитализиран и во најголем дел може да заврши за неколку дена, во зависност од подготвеноста на документите и брзината на обработка во ЦРСМ.' },
        { question: 'Кога морам да се регистрирам за ДДВ?', answer: 'ДДВ регистрацијата станува задолжителна кога годишниот промет ќе надмине 2.000.000 МКД. Можна е и доброволна регистрација под прагот, што е корисно ако вашите клиенти се ДДВ обврзници.' },
        { question: 'Дали новата фирма мора да издава е-фактури?', answer: 'Електронското фактурирање станува задолжително во Македонија, па новите фирми треба да изберат софтвер спремен за е-фактура (UBL 2.1) и УЈП платформата уште од старт.' },
        { question: 'Кои се првите чекори по отворањето?', answer: 'Отворете деловна банкарска сметка, потврдете го даночниот режим кон УЈП, почнете да издавате фактури од првиот ден и воспоставете уредно книговодство.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={ra.href}
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
