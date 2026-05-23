import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/drzavni-institucii-za-firmi', {
    title: {
      mk: 'Државни институции за фирми во Македонија: УЈП, ЦРСМ, Инспекторат — Кој е кој',
      en: 'Government Agencies for Business in North Macedonia: UJP, CRMS, Inspectorate — Who Is Who',
      sq: 'Institucionet shtetërore për biznese në Maqedoninë e Veriut: UJP, QRMV, Inspektorati — Kush është kush',
      tr: 'Kuzey Makedonya\'da İşletmeler İçin Devlet Kurumları: UJP, CRMS, Müfettişlik — Kim Kimdir',
    },
    description: {
      mk: 'Комплетен водич за сите државни институции со кои фирмите во Македонија контактираат: ЦРСМ, УЈП, ФПИОМ, ФЗОМ, АВРМ, Инспекторат за труд и Царинска управа. Кога, зошто и како.',
      en: 'Complete guide to all government agencies that businesses in North Macedonia interact with: CRMS, UJP, FPIOM, FZOM, AVRM, Labor Inspectorate, and Customs. When, why, and how.',
      sq: 'Udhëzues i plotë për të gjitha institucionet shtetërore me të cilat bizneset në Maqedoninë e Veriut bashkëveprojnë: QRMV, UJP, FPIOM, FZOM, AVRM, Inspektorati i Punës dhe Dogana.',
      tr: 'Kuzey Makedonya\'daki işletmelerin etkileşimde bulunduğu tüm devlet kurumlarının rehberi: CRMS, UJP, FPIOM, FZOM, AVRM, Çalışma Müfettişliği ve Gümrük.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Државни институции за фирми: УЈП, ЦРСМ, Инспекторат — Кој е кој',
    publishDate: '23 мај 2026',
    readTime: '9 мин читање',
    intro: 'Секоја фирма во Македонија е во редовен контакт со барем 5-6 државни институции — од регистрација, преку месечни даночни обврски, до годишно затворање. Но кој е кој? Каде се поднесува што? Овој водич ги објаснува сите клучни институции: ЦРСМ, УЈП, ФПИОМ, ФЗОМ, АВРМ, Државниот инспекторат за труд и Царинската управа — со точни веб-адреси, надлежности и практични совети.',
    sections: [
      {
        title: 'ЦРСМ — Централен регистар на РСМ',
        content: 'Централниот регистар (crm.com.mk) е првата институција со која секоја фирма се среќава. Тука се регистрира правното лице, се запишуваат промени во сопственоста, се менуваат директори и се поднесуваат годишни сметки. Без ЦРСМ, фирмата правно не постои.',
        items: [
          'Регистрација на ДООЕЛ, ДОО, ТП и други правни форми — цена: 3.000-5.000 МКД',
          'Промена на назив, седиште, основна главнина или управител',
          'Поднесување годишна сметка (рок: 28 февруари секоја година)',
          'Извод од Централен регистар — потребен за банки, тендери, договори',
          'Онлајн портал: crm.com.mk — повеќето постапки може електронски',
          'Бришење (ликвидација) на фирма — исто преку ЦРСМ',
        ],
        steps: null,
      },
      {
        title: 'УЈП — Управа за јавни приходи',
        content: 'УЈП (ujp.gov.mk) е даночната администрација и главниот регулатор на сите даночни обврски. Секоја фирма е должна да се регистрира за данок, да поднесува месечни и годишни пријави и да ги плаќа даноците навремено. УЈП управува со данок на добивка, ДДВ, персонален данок на доход и МПИН.',
        items: [
          'e-Tax портал (etax.ujp.gov.mk) — електронско поднесување на сите даночни обрасци',
          'МПИН (mpinform.ujp.gov.mk) — месечна пресметка за плати и придонеси, рок до 15-ти',
          'ДДВ пријава — месечна за обврзници со промет над 2 милиони МКД',
          'Данок на добивка — годишна пријава ДД-01, рок 15 март',
          'Персонален данок на доход — ПДД-ГДП за вработени и самовработени',
          'Даночна контрола и ревизија — УЈП може да побара проверка во секое време',
          'Даночно уверение — потребно за тендери и кредити',
        ],
        steps: null,
      },
      {
        title: 'ФПИОМ — Фонд за пензиско и инвалидско осигурување',
        content: 'ФПИОМ (piom.com.mk) е одговорен за евиденција на пензискиот стаж на вработените и за исплата на пензии. Иако придонесите се плаќаат преку МПИН (УЈП), ФПИОМ води евиденција за секој осигуреник поединечно. Работодавачите имаат обврска да пријават секој вработен и да обезбедат точни податоци за стажот.',
        items: [
          'Стапка на придонес: 18,8% од бруто плата (се задржува од плата)',
          'Евиденција на стаж — ФПИОМ ја проверува со МПИН податоците од УЈП',
          'М1/М2 обрасци — пријава и одјава на вработен',
          'Потврда за стаж — вработените ја бараат при аплицирање за пензија',
          'Онлајн увид: moj.piom.com.mk — проверка на евиденција',
          'Минимална основица за придонеси: 31.577 МКД, максимална: 1.010.464 МКД',
        ],
        steps: null,
      },
      {
        title: 'ФЗОМ — Фонд за здравствено осигурување',
        content: 'ФЗОМ (fzo.org.mk) обезбедува здравствена заштита на осигурениците. Придонесот за здравствено осигурување се пресметува и плаќа заедно со останатите придонеси преку МПИН, но ФЗОМ води посебна евиденција за здравствено осигурените лица.',
        items: [
          'Стапка на придонес: 7,5% од бруто плата',
          'Здравствена книшка — се издава врз основа на пријава преку МПИН',
          'Боледување до 30 дена — на товар на работодавачот',
          'Боледување над 30 дена — ФЗОМ ги надоместува трошоците',
          'Право на здравствена заштита за членови на семејство на осигуреникот',
          'Автоматска евиденција преку МПИН системот — не треба посебно пријавување',
        ],
        steps: null,
      },
      {
        title: 'АВРМ — Агенција за вработување',
        content: 'АВРМ (av.gov.mk) е надлежна за евиденција на невработени лица, посредување при вработување и спроведување на активни мерки за вработување. За работодавачите, АВРМ е клучна институција за субвенции, програми за нови вработувања и придонесот за невработеност.',
        items: [
          'Стапка на придонес за невработеност: 1,2% од бруто плата',
          'Програма за самовработување — субвенции до 307.000 МКД',
          'Субвенции за нови вработувања — ослободување од придонеси до 3 години',
          'Обуки и преквалификации — АВРМ организира бесплатни програми',
          'Пријавување на слободни работни места — задолжително за јавни огласи',
          'Младински гаранција — програма за млади до 29 години',
        ],
        steps: null,
      },
      {
        title: 'Државен инспекторат за труд',
        content: 'Државниот инспекторат за труд (dit.gov.mk) ги контролира работодавачите во поглед на почитување на трудовото законодавство, безбедноста на работното место и правата на вработените. Тука вработените ги пријавуваат неисплатени плати, нерегулирани работни односи и небезбедни услови.',
        items: [
          'Контрола на работни договори — дали се склучени во писмена форма',
          'Проверка на исплата на плати — дали се исплатени до 15-ти наредниот месец',
          'Безбедност и здравје при работа — инспекција на работни услови',
          'Пријави за мобинг, дискриминација и вознемирување на работно место',
          'Казни за работодавачи: 2.000-5.000 EUR за прекршоци на Законот за работни односи',
          'Вработените може да поднесат анонимна пријава',
          'Инспекторатот може да забрани работа до отстранување на недостатоците',
        ],
        steps: null,
      },
      {
        title: 'Царинска управа на РСМ',
        content: 'Царинската управа (customs.gov.mk) е надлежна за царински постапки при увоз и извоз, акцизи и Интрастат извештаи. Фирмите кои увезуваат или извезуваат стоки мораат да имаат ЕОРИ број и да ги почитуваат царинските тарифи.',
        items: [
          'ЕОРИ број — задолжителен за секоја фирма која тргува со странство',
          'Царински декларации — се поднесуваат електронски преку EXIM систем',
          'ДДВ при увоз — се плаќа на царина, потоа се одбива како претходен данок',
          'Акцизи — на горива, алкохол, тутун и енергенси',
          'Интрастат извештаи — месечни за фирми со промет во ЕУ над прагот',
          'Царински магацини и слободни зони — посебни режими',
          'Потекло на стоки — сертификати EUR.1 и ATR за преференцијален третман',
        ],
        steps: null,
      },
      {
        title: 'Кога контактирате со секоја институција',
        content: 'Животниот циклус на една фирма вклучува контакт со различни институции во различни фази. Еве го редоследот на типичните интеракции:',
        items: null,
        steps: [
          { step: 'Основање на фирма → ЦРСМ', desc: 'Регистрација на правно лице, добивање ЕМБС и извод. Цена 3.000-5.000 МКД, трае 1-3 работни дена.' },
          { step: 'Даночна регистрација → УЈП', desc: 'Добивање даночен број, регистрација за ДДВ (ако е применливо), пристап до e-Tax портал. Веднаш по регистрација во ЦРСМ.' },
          { step: 'Прво вработување → ФПИОМ + ФЗОМ + АВРМ', desc: 'Пријава на вработен со М1 образец. МПИН системот автоматски ги известува ФПИОМ, ФЗОМ и АВРМ.' },
          { step: 'Секој месец → УЈП (МПИН)', desc: 'Поднесување МПИН до 15-ти за плати и придонеси. ДДВ пријава до 25-ти (ако сте обврзник).' },
          { step: 'Годишно → ЦРСМ + УЈП', desc: 'Годишна сметка до ЦРСМ (28.02), данок на добивка до УЈП (15.03), ПДД-ГДП за персонален данок.' },
          { step: 'По потреба → Инспекторат, Царина', desc: 'Инспекторат при трудови спорови или инспекции. Царинска управа при увоз/извоз на стоки.' },
        ],
      },
      {
        title: 'Како Facturino помага со сите институции',
        content: 'Facturino ги интегрира сите потребни извештаи и обрасци на едно место. Наместо да жонглирате со 6-7 различни портали и рокови, Facturino автоматски ги генерира МПИН образците, ДДВ пријавите, годишните извештаи и ве потсетува пред секој рок.',
        items: [
          'Автоматско генерирање на МПИН — спремен XML за e-Tax',
          'ДДВ пресметка и евиденција — усогласена со УЈП барања',
          'Годишна сметка — биланс на состојба и биланс на успех по МКД стандарди',
          'Даночен календар со потсетници — никогаш нема да пропуштите рок',
          'Евиденција на вработени — стаж, плати, боледувања на едно место',
          'Извештаи за инспекција — подготвени ако Инспекторатот побара преглед',
          'Царински документи — фактури и про-форма фактури за увоз/извоз',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
      { slug: 'otvoranje-firma-mk', title: 'Како да отворите фирма во Македонија' },
      { slug: 'registracija-firma-cekor-po-cekor', title: 'Регистрација на фирма: Чекор по чекор водич' },
      { slug: 'kazni-ujp-2026', title: 'Казни од УЈП: Прекршоци и глоби 2026' },
    ],
    cta: {
      title: 'Сите институции, еден софтвер',
      desc: 'Facturino ги генерира сите обрасци и извештаи за УЈП, ЦРСМ и останатите институции.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Government Agencies for Business: UJP, CRMS, Inspectorate — Who Is Who',
    publishDate: 'May 23, 2026',
    readTime: '9 min read',
    intro: 'Every business in North Macedonia regularly interacts with at least 5-6 government agencies — from initial registration, through monthly tax obligations, to annual financial closing. But who is who? Where do you file what? This guide explains all the key institutions: CRMS, UJP, FPIOM, FZOM, AVRM, the State Labor Inspectorate, and the Customs Administration — with exact web addresses, responsibilities, and practical tips.',
    sections: [
      {
        title: 'CRMS — Central Registry of North Macedonia',
        content: 'The Central Registry (crm.com.mk) is the first institution every business encounters. This is where you register your legal entity, record ownership changes, appoint directors, and file annual accounts. Without CRMS, your company does not legally exist.',
        items: [
          'Registration of DOOEL, DOO, TP and other legal forms — cost: 3,000-5,000 MKD',
          'Change of company name, headquarters, share capital, or managing director',
          'Annual accounts filing (deadline: February 28 each year)',
          'Company extract from Central Registry — required for banks, tenders, contracts',
          'Online portal: crm.com.mk — most procedures available electronically',
          'Company dissolution (liquidation) — also through CRMS',
        ],
        steps: null,
      },
      {
        title: 'UJP — Public Revenue Office',
        content: 'UJP (ujp.gov.mk) is the tax administration and the main regulator of all tax obligations. Every company must register for tax, file monthly and annual returns, and pay taxes on time. UJP administers corporate income tax, VAT, personal income tax, and the MPIN payroll system.',
        items: [
          'e-Tax portal (etax.ujp.gov.mk) — electronic filing of all tax forms',
          'MPIN (mpinform.ujp.gov.mk) — monthly payroll and contribution calculation, deadline by 15th',
          'VAT return — monthly for taxpayers with turnover above 2 million MKD',
          'Corporate income tax — annual return DD-01, deadline March 15',
          'Personal income tax — PDD-GDP for employees and self-employed',
          'Tax audit and inspection — UJP can request a review at any time',
          'Tax clearance certificate — required for tenders and loans',
        ],
        steps: null,
      },
      {
        title: 'FPIOM — Pension and Disability Insurance Fund',
        content: 'FPIOM (piom.com.mk) is responsible for maintaining pension service records for employees and paying pensions. Although contributions are paid through MPIN (UJP), FPIOM keeps individual records for each insured person. Employers are obligated to register every employee and ensure accurate service data.',
        items: [
          'Contribution rate: 18.8% of gross salary (deducted from salary)',
          'Service record tracking — FPIOM verifies against MPIN data from UJP',
          'M1/M2 forms — employee registration and deregistration',
          'Service confirmation — employees request it when applying for pension',
          'Online access: moj.piom.com.mk — check your records',
          'Minimum contribution base: 31,577 MKD, maximum: 1,010,464 MKD',
        ],
        steps: null,
      },
      {
        title: 'FZOM — Health Insurance Fund',
        content: 'FZOM (fzo.org.mk) provides healthcare coverage for insured persons. Health insurance contributions are calculated and paid together with other contributions through MPIN, but FZOM maintains a separate registry of health-insured individuals.',
        items: [
          'Contribution rate: 7.5% of gross salary',
          'Health insurance card — issued based on MPIN registration',
          'Sick leave up to 30 days — covered by the employer',
          'Sick leave over 30 days — FZOM reimburses the costs',
          'Healthcare coverage for family members of the insured person',
          'Automatic registration through the MPIN system — no separate filing needed',
        ],
        steps: null,
      },
      {
        title: 'AVRM — Employment Agency',
        content: 'AVRM (av.gov.mk) is responsible for maintaining records of unemployed persons, employment mediation, and implementing active employment measures. For employers, AVRM is a key institution for subsidies, new hiring programs, and unemployment contributions.',
        items: [
          'Unemployment contribution rate: 1.2% of gross salary',
          'Self-employment program — subsidies up to 307,000 MKD',
          'New hire subsidies — contribution exemption for up to 3 years',
          'Training and requalification — AVRM organizes free programs',
          'Job vacancy registration — mandatory for public postings',
          'Youth Guarantee — program for young people up to 29 years old',
        ],
        steps: null,
      },
      {
        title: 'State Labor Inspectorate',
        content: 'The State Labor Inspectorate (dit.gov.mk) monitors employers regarding compliance with labor legislation, workplace safety, and employee rights. This is where employees report unpaid wages, unregulated employment relationships, and unsafe working conditions.',
        items: [
          'Employment contract control — whether contracts are in written form',
          'Salary payment verification — whether salaries are paid by the 15th of the following month',
          'Occupational health and safety — inspection of working conditions',
          'Reports of mobbing, discrimination, and workplace harassment',
          'Employer penalties: EUR 2,000-5,000 for violations of the Labor Relations Act',
          'Employees can file anonymous complaints',
          'The Inspectorate can halt operations until deficiencies are resolved',
        ],
        steps: null,
      },
      {
        title: 'Customs Administration of North Macedonia',
        content: 'The Customs Administration (customs.gov.mk) handles customs procedures for imports and exports, excise duties, and Intrastat reporting. Companies that import or export goods must have an EORI number and comply with customs tariffs.',
        items: [
          'EORI number — mandatory for every company trading internationally',
          'Customs declarations — filed electronically via the EXIM system',
          'Import VAT — paid at customs, then deducted as input tax',
          'Excise duties — on fuels, alcohol, tobacco, and energy products',
          'Intrastat reports — monthly for companies with EU trade above the threshold',
          'Customs warehouses and free zones — special regimes',
          'Origin of goods — EUR.1 and ATR certificates for preferential treatment',
        ],
        steps: null,
      },
      {
        title: 'When You Interact with Each Agency',
        content: 'The lifecycle of a business involves contact with different institutions at different stages. Here is the typical sequence of interactions:',
        items: null,
        steps: [
          { step: 'Company formation → CRMS', desc: 'Register your legal entity, obtain EMBS number and company extract. Cost 3,000-5,000 MKD, takes 1-3 business days.' },
          { step: 'Tax registration → UJP', desc: 'Get your tax number, register for VAT (if applicable), access the e-Tax portal. Immediately after CRMS registration.' },
          { step: 'First employee → FPIOM + FZOM + AVRM', desc: 'Register employee with M1 form. The MPIN system automatically notifies FPIOM, FZOM, and AVRM.' },
          { step: 'Every month → UJP (MPIN)', desc: 'File MPIN by the 15th for salaries and contributions. VAT return by the 25th (if you are a VAT payer).' },
          { step: 'Annually → CRMS + UJP', desc: 'Annual accounts to CRMS (Feb 28), corporate income tax to UJP (Mar 15), PDD-GDP for personal income tax.' },
          { step: 'As needed → Inspectorate, Customs', desc: 'Labor Inspectorate for disputes or inspections. Customs Administration for import/export of goods.' },
        ],
      },
      {
        title: 'How Facturino Helps with All Agencies',
        content: 'Facturino integrates all required reports and forms in one place. Instead of juggling 6-7 different portals and deadlines, Facturino automatically generates MPIN forms, VAT returns, annual reports, and reminds you before every deadline.',
        items: [
          'Automatic MPIN generation — XML ready for e-Tax',
          'VAT calculation and records — aligned with UJP requirements',
          'Annual accounts — balance sheet and income statement per MKD standards',
          'Tax calendar with reminders — never miss a deadline',
          'Employee records — service history, salaries, sick leave in one place',
          'Inspection-ready reports — prepared if the Inspectorate requests a review',
          'Customs documents — invoices and proforma invoices for import/export',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
      { slug: 'otvoranje-firma-mk', title: 'How to Open a Company in North Macedonia' },
      { slug: 'registracija-firma-cekor-po-cekor', title: 'Company Registration: Step-by-Step Checklist' },
      { slug: 'kazni-ujp-2026', title: 'UJP Penalties: Violations & Fines 2026' },
    ],
    cta: {
      title: 'All agencies, one software',
      desc: 'Facturino generates all forms and reports for UJP, CRMS, and all other institutions.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Institucionet shtetërore për biznese: UJP, QRMV, Inspektorati — Kush është kush',
    publishDate: '23 maj 2026',
    readTime: '9 min lexim',
    intro: 'Çdo biznes në Maqedoninë e Veriut bashkëvepron rregullisht me të paktën 5-6 institucione shtetërore — nga regjistrimi fillestar, përmes detyrimeve tatimore mujore, deri te mbyllja financiare vjetore. Por kush është kush? Ku dorëzohet çfarë? Ky udhëzues shpjegon të gjitha institucionet kyçe: QRMV, UJP, FPIOM, FZOM, AVRM, Inspektorati Shtetëror i Punës dhe Administrata Doganore — me adresa të sakta interneti, përgjegjësi dhe këshilla praktike.',
    sections: [
      {
        title: 'QRMV — Regjistri Qendror i Maqedonisë së Veriut',
        content: 'Regjistri Qendror (crm.com.mk) është institucioni i parë me të cilin çdo biznes takohet. Këtu regjistrohet personi juridik, regjistrohen ndryshimet e pronësisë, emërohen drejtorët dhe dorëzohen llogaritë vjetore. Pa QRMV, kompania juaj nuk ekziston ligjërisht.',
        items: [
          'Regjistrim i SHPKNJP, SHPK, TP dhe formave të tjera juridike — çmimi: 3.000-5.000 MKD',
          'Ndryshim i emrit, selisë, kapitalit themeltar ose drejtorit menaxhues',
          'Dorëzim i llogarive vjetore (afati: 28 shkurt çdo vit)',
          'Ekstrakt nga Regjistri Qendror — i nevojshëm për banka, tendera, kontrata',
          'Portali online: crm.com.mk — shumica e procedurave janë elektronike',
          'Shpërbërja (likuidimi) i kompanisë — gjithashtu përmes QRMV',
        ],
        steps: null,
      },
      {
        title: 'UJP — Zyra e të Ardhurave Publike',
        content: 'UJP (ujp.gov.mk) është administrata tatimore dhe rregullatori kryesor i të gjitha detyrimeve tatimore. Çdo kompani duhet të regjistrohet për tatim, të dorëzojë deklarata mujore dhe vjetore dhe të paguajë tatimet në kohë. UJP administron tatimin mbi fitimin, TVSH-në, tatimin mbi të ardhurat personale dhe sistemin MPIN.',
        items: [
          'Portali e-Tatimi (etax.ujp.gov.mk) — dorëzim elektronik i të gjitha formularëve tatimore',
          'MPIN (mpinform.ujp.gov.mk) — llogaritja mujore e pagave dhe kontributeve, afati deri 15-të',
          'Deklarata e TVSH-së — mujore për tatimpaguesit me qarkullim mbi 2 milionë MKD',
          'Tatimi mbi fitimin — deklarata vjetore DD-01, afati 15 mars',
          'Tatimi mbi të ardhurat personale — PDD-GDP për punonjës dhe vetëpunësuar',
          'Auditim tatimor dhe inspektim — UJP mund të kërkojë kontroll në çdo kohë',
          'Certifikatë e pastrimit tatimor — e nevojshme për tendera dhe kredi',
        ],
        steps: null,
      },
      {
        title: 'FPIOM — Fondi për Sigurim Pensional dhe Invalidor',
        content: 'FPIOM (piom.com.mk) është përgjegjës për mbajtjen e evidencës së stazhit pensional të punonjësve dhe pagesën e pensioneve. Edhe pse kontributet paguhen përmes MPIN (UJP), FPIOM mban evidencë individuale për çdo person të siguruar.',
        items: [
          'Norma e kontributit: 18,8% e pagës bruto (zbritet nga paga)',
          'Ndjekja e evidencës së stazhit — FPIOM verifikon me të dhënat MPIN nga UJP',
          'Formularët M1/M2 — regjistrim dhe çregjistrim i punonjësit',
          'Konfirmim stazhi — punonjësit e kërkojnë kur aplikojnë për pension',
          'Qasje online: moj.piom.com.mk — kontrolloni evidencën tuaj',
          'Baza minimale e kontributit: 31.577 MKD, maksimale: 1.010.464 MKD',
        ],
        steps: null,
      },
      {
        title: 'FZOM — Fondi për Sigurim Shëndetësor',
        content: 'FZOM (fzo.org.mk) ofron mbulim shëndetësor për personat e siguruar. Kontributet për sigurim shëndetësor llogariten dhe paguhen bashkë me kontributet e tjera përmes MPIN, por FZOM mban regjistër të veçantë.',
        items: [
          'Norma e kontributit: 7,5% e pagës bruto',
          'Librezë shëndetësore — lëshohet bazuar në regjistrimin MPIN',
          'Pushim mjekësor deri 30 ditë — në ngarkim të punëdhënësit',
          'Pushim mjekësor mbi 30 ditë — FZOM i rimburson shpenzimet',
          'Mbulim shëndetësor për anëtarët e familjes së personit të siguruar',
          'Regjistrim automatik përmes sistemit MPIN — nuk nevojitet dorëzim i veçantë',
        ],
        steps: null,
      },
      {
        title: 'AVRM — Agjencia e Punësimit',
        content: 'AVRM (av.gov.mk) është përgjegjëse për evidencën e personave të papunë, ndërmjetësimin për punësim dhe zbatimin e masave aktive të punësimit. Për punëdhënësit, AVRM është institucioni kyç për subvencione, programe për punësime të reja dhe kontributin e papunësisë.',
        items: [
          'Norma e kontributit për papunësi: 1,2% e pagës bruto',
          'Programi i vetëpunësimit — subvencione deri 307.000 MKD',
          'Subvencione për punësime të reja — lirim nga kontributet deri 3 vjet',
          'Trajnime dhe rikualifikime — AVRM organizon programe falas',
          'Regjistrim i vendeve të lira të punës — i detyrueshëm për shpallje publike',
          'Garancia Rinore — program për të rinj deri 29 vjeç',
        ],
        steps: null,
      },
      {
        title: 'Inspektorati Shtetëror i Punës',
        content: 'Inspektorati Shtetëror i Punës (dit.gov.mk) monitoron punëdhënësit në lidhje me pajtueshmërinë me legjislacionin e punës, sigurinë në vendin e punës dhe të drejtat e punonjësve. Këtu punonjësit raportojnë paga të papaguara, marrëdhënie pune të parregulluara dhe kushte të pasigurta pune.',
        items: [
          'Kontrolli i kontratave të punës — nëse kontrata janë në formë të shkruar',
          'Verifikimi i pagesës së pagave — nëse paguhen deri 15-të të muajit pasues',
          'Siguria dhe shëndeti në punë — inspektim i kushteve të punës',
          'Raportime për mobing, diskriminim dhe ngacmim në vendin e punës',
          'Gjoba për punëdhënësit: 2.000-5.000 EUR për shkelje të Ligjit për Marrëdhëniet e Punës',
          'Punonjësit mund të dorëzojnë ankesë anonime',
          'Inspektorati mund të ndalojë punën deri sa të eliminohen mangësitë',
        ],
        steps: null,
      },
      {
        title: 'Administrata Doganore e Maqedonisë së Veriut',
        content: 'Administrata Doganore (customs.gov.mk) trajton procedurat doganore për importe dhe eksporte, detyrimet e akcizës dhe raportimet Intrastat. Kompanitë që importojnë ose eksportojnë mallra duhet të kenë numër EORI.',
        items: [
          'Numri EORI — i detyrueshëm për çdo kompani që tregton ndërkombëtarisht',
          'Deklarata doganore — dorëzohen elektronikisht përmes sistemit EXIM',
          'TVSH në import — paguhet në doganë, pastaj zbritet si tatim hyrës',
          'Detyrime akcize — mbi karburante, alkool, duhan dhe produkte energjetike',
          'Raportime Intrastat — mujore për kompani me tregti BE mbi pragun',
          'Magazina doganore dhe zona të lira — regjime të veçanta',
          'Origjina e mallrave — certifikata EUR.1 dhe ATR për trajtim preferencial',
        ],
        steps: null,
      },
      {
        title: 'Kur bashkëveproni me çdo institucion',
        content: 'Cikli jetësor i një biznesi përfshin kontakt me institucione të ndryshme në faza të ndryshme. Ja sekuenca tipike e bashkëveprimeve:',
        items: null,
        steps: [
          { step: 'Themelimi i kompanisë → QRMV', desc: 'Regjistroni personin juridik, merrni numrin EMBS dhe ekstraktin. Çmimi 3.000-5.000 MKD, zgjat 1-3 ditë pune.' },
          { step: 'Regjistrimi tatimor → UJP', desc: 'Merrni numrin tatimor, regjistrohuni për TVSH (nëse aplikohet), qasje në portalin e-Tatimi. Menjëherë pas regjistrimit në QRMV.' },
          { step: 'Punonjësi i parë → FPIOM + FZOM + AVRM', desc: 'Regjistroni punonjësin me formularin M1. Sistemi MPIN njofton automatikisht FPIOM, FZOM dhe AVRM.' },
          { step: 'Çdo muaj → UJP (MPIN)', desc: 'Dorëzoni MPIN deri 15-të për paga dhe kontribute. Deklarata TVSH deri 25-të (nëse jeni tatimpagues).' },
          { step: 'Çdo vit → QRMV + UJP', desc: 'Llogaritë vjetore te QRMV (28.02), tatimi mbi fitimin te UJP (15.03), PDD-GDP për tatimin personal.' },
          { step: 'Sipas nevojës → Inspektorati, Dogana', desc: 'Inspektorati i Punës për mosmarrëveshje ose inspektime. Administrata Doganore për import/eksport mallrash.' },
        ],
      },
      {
        title: 'Si ndihmon Facturino me të gjitha institucionet',
        content: 'Facturino integron të gjitha raportet dhe formularët e nevojshme në një vend. Në vend që të jongloni me 6-7 portale dhe afate të ndryshme, Facturino gjeneron automatikisht formularët MPIN, deklaratat e TVSH-së, raportet vjetore dhe ju kujton para çdo afati.',
        items: [
          'Gjenerim automatik i MPIN — XML gati për e-Tatimi',
          'Llogaritja dhe evidenca e TVSH-së — e harmonizuar me kërkesat e UJP',
          'Llogaritë vjetore — bilanci i gjendjes dhe bilanci i suksesit sipas standardeve MKD',
          'Kalendar tatimor me kujtesa — kurrë nuk do ta humbni një afat',
          'Evidenca e punonjësve — stazhi, pagat, pushimet mjekësore në një vend',
          'Raporte gati për inspektim — të përgatitura nëse Inspektorati kërkon kontroll',
          'Dokumente doganore — fatura dhe profatura për import/eksport',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhëzues për llogaritjen mujore' },
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet për UJP' },
      { slug: 'otvoranje-firma-mk', title: 'Si të hapni kompani në Maqedoninë e Veriut' },
      { slug: 'registracija-firma-cekor-po-cekor', title: 'Regjistrimi i firmës: Udhëzues hap pas hapi' },
      { slug: 'kazni-ujp-2026', title: 'Gjobat e UJP: Shkeljet dhe gjobat 2026' },
    ],
    cta: {
      title: 'Të gjitha institucionet, një softuer',
      desc: 'Facturino gjeneron të gjitha formularët dhe raportet për UJP, QRMV dhe institucionet e tjera.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'İşletmeler İçin Devlet Kurumları: UJP, CRMS, Müfettişlik — Kim Kimdir',
    publishDate: '23 Mayıs 2026',
    readTime: '9 dk okuma',
    intro: 'Kuzey Makedonya\'daki her işletme düzenli olarak en az 5-6 devlet kurumu ile etkileşim halindedir — ilk kayıttan, aylık vergi yükümlülüklerine, yıllık mali kapanışa kadar. Ama kim kimdir? Neyi nereye verirsiniz? Bu rehber tüm önemli kurumları açıklar: CRMS, UJP, FPIOM, FZOM, AVRM, Devlet Çalışma Müfettişliği ve Gümrük İdaresi — tam web adresleri, sorumlulukları ve pratik ipuçlarıyla.',
    sections: [
      {
        title: 'CRMS — Kuzey Makedonya Merkez Sicili',
        content: 'Merkez Sicili (crm.com.mk), her işletmenin karşılaştığı ilk kurumdur. Tüzel kişiliğinizi burada tescil ettirir, mülkiyet değişikliklerini kaydeder, müdürleri atarsınız ve yıllık hesapları dosyalarsınız. CRMS olmadan, şirketiniz yasal olarak mevcut değildir.',
        items: [
          'DOOEL, DOO, TP ve diğer hukuki formların tescili — maliyet: 3.000-5.000 MKD',
          'Şirket adı, merkez, sermaye veya yönetici müdür değişikliği',
          'Yıllık hesap dosyalama (son tarih: her yıl 28 Şubat)',
          'Merkez Sicilinden şirket özeti — bankalar, ihaleler ve sözleşmeler için gerekli',
          'Çevrimiçi portal: crm.com.mk — çoğu işlem elektronik olarak yapılabilir',
          'Şirket tasfiyesi — yine CRMS üzerinden',
        ],
        steps: null,
      },
      {
        title: 'UJP — Kamu Gelir İdaresi',
        content: 'UJP (ujp.gov.mk), vergi idaresi ve tüm vergi yükümlülüklerinin ana düzenleyicisidir. Her şirket vergi kaydı yaptırmak, aylık ve yıllık beyanname vermek ve vergileri zamanında ödemek zorundadır. UJP kurumlar vergisi, KDV, kişisel gelir vergisi ve MPIN bordro sistemini yönetir.',
        items: [
          'e-Vergi portalı (etax.ujp.gov.mk) — tüm vergi formlarının elektronik dosyalanması',
          'MPIN (mpinform.ujp.gov.mk) — aylık bordro ve prim hesaplaması, son tarih 15\'i',
          'KDV beyannamesi — cirosu 2 milyon MKD\'yi aşan mükellefler için aylık',
          'Kurumlar vergisi — yıllık beyanname DD-01, son tarih 15 Mart',
          'Kişisel gelir vergisi — çalışanlar ve serbest meslek sahipleri için PDD-GDP',
          'Vergi denetimi ve teftiş — UJP her zaman inceleme talep edebilir',
          'Vergi temiz belgesi — ihaleler ve krediler için gerekli',
        ],
        steps: null,
      },
      {
        title: 'FPIOM — Emeklilik ve Maluliyet Sigortası Fonu',
        content: 'FPIOM (piom.com.mk), çalışanların emeklilik hizmet kayıtlarını tutmak ve emekli maaşı ödemekten sorumludur. Primler MPIN (UJP) üzerinden ödense de, FPIOM her sigortalı için bireysel kayıt tutar.',
        items: [
          'Prim oranı: brüt maaşın %18,8\'i (maaştan kesilir)',
          'Hizmet kaydı takibi — FPIOM, UJP\'den gelen MPIN verileriyle doğrular',
          'M1/M2 formları — çalışan kaydı ve çıkışı',
          'Hizmet onayı — çalışanlar emeklilik başvurusunda talep eder',
          'Çevrimiçi erişim: moj.piom.com.mk — kayıtlarınızı kontrol edin',
          'Asgari prim matrahı: 31.577 MKD, azami: 1.010.464 MKD',
        ],
        steps: null,
      },
      {
        title: 'FZOM — Sağlık Sigortası Fonu',
        content: 'FZOM (fzo.org.mk), sigortalı kişilere sağlık güvencesi sağlar. Sağlık sigortası primleri diğer primlerle birlikte MPIN üzerinden hesaplanır ve ödenir, ancak FZOM ayrı bir sağlık sigortalı kişi kaydı tutar.',
        items: [
          'Prim oranı: brüt maaşın %7,5\'i',
          'Sağlık sigortası kartı — MPIN kaydına dayalı olarak verilir',
          '30 güne kadar hastalık izni — işveren tarafından karşılanır',
          '30 günü aşan hastalık izni — FZOM masrafları geri öder',
          'Sigortalı kişinin aile üyeleri için sağlık güvencesi',
          'MPIN sistemi üzerinden otomatik kayıt — ayrı başvuru gerekmez',
        ],
        steps: null,
      },
      {
        title: 'AVRM — İstihdam Ajansı',
        content: 'AVRM (av.gov.mk), işsiz kişilerin kaydını tutmak, istihdam aracılığı yapmak ve aktif istihdam tedbirlerini uygulamaktan sorumludur. İşverenler için AVRM, sübvansiyonlar, yeni işe alım programları ve işsizlik primleri açısından kilit kurumdur.',
        items: [
          'İşsizlik primi oranı: brüt maaşın %1,2\'si',
          'Kendi işini kurma programı — 307.000 MKD\'ye kadar sübvansiyon',
          'Yeni işe alım sübvansiyonları — 3 yıla kadar prim muafiyeti',
          'Eğitim ve yeniden nitelendirme — AVRM ücretsiz programlar düzenler',
          'Açık iş pozisyonu kaydı — kamu ilanları için zorunlu',
          'Gençlik Garantisi — 29 yaşına kadar gençler için program',
        ],
        steps: null,
      },
      {
        title: 'Devlet Çalışma Müfettişliği',
        content: 'Devlet Çalışma Müfettişliği (dit.gov.mk), iş mevzuatına uyum, işyeri güvenliği ve çalışan hakları konusunda işverenleri denetler. Çalışanlar ödenmemiş maaşları, kayıt dışı iş ilişkilerini ve güvensiz çalışma koşullarını burada bildirirler.',
        items: [
          'İş sözleşmesi kontrolü — sözleşmelerin yazılı formda olup olmadığı',
          'Maaş ödeme doğrulaması — maaşların takip eden ayın 15\'ine kadar ödenip ödenmediği',
          'İş sağlığı ve güvenliği — çalışma koşullarının denetimi',
          'Mobbing, ayrımcılık ve işyeri tacizi bildirimleri',
          'İşveren cezaları: İş İlişkileri Kanunu ihlalleri için 2.000-5.000 EUR',
          'Çalışanlar anonim şikayet başvurusu yapabilir',
          'Müfettişlik, eksiklikler giderilene kadar faaliyetleri durdurabilir',
        ],
        steps: null,
      },
      {
        title: 'Kuzey Makedonya Gümrük İdaresi',
        content: 'Gümrük İdaresi (customs.gov.mk), ithalat ve ihracat gümrük işlemlerini, özel tüketim vergilerini ve Intrastat raporlamasını yönetir. İthalat veya ihracat yapan şirketlerin EORI numarasına sahip olması ve gümrük tarifelerine uyması gerekir.',
        items: [
          'EORI numarası — uluslararası ticaret yapan her şirket için zorunlu',
          'Gümrük beyannameleri — EXIM sistemi üzerinden elektronik olarak dosyalanır',
          'İthalat KDV\'si — gümrükte ödenir, sonra indirilecek vergi olarak düşülür',
          'Özel tüketim vergileri — yakıtlar, alkol, tütün ve enerji ürünleri üzerinde',
          'Intrastat raporları — eşiği aşan AB ticareti yapan şirketler için aylık',
          'Gümrük depoları ve serbest bölgeler — özel rejimler',
          'Malların menşei — tercihli muamele için EUR.1 ve ATR sertifikaları',
        ],
        steps: null,
      },
      {
        title: 'Her Kurumla Ne Zaman Etkileşim Kurarsınız',
        content: 'Bir işletmenin yaşam döngüsü, farklı aşamalarda farklı kurumlarla iletişimi içerir. İşte tipik etkileşim sırası:',
        items: null,
        steps: [
          { step: 'Şirket kuruluşu → CRMS', desc: 'Tüzel kişiliğinizi tescil ettirin, EMBS numaranızı ve şirket özetinizi alın. Maliyet 3.000-5.000 MKD, 1-3 iş günü sürer.' },
          { step: 'Vergi kaydı → UJP', desc: 'Vergi numaranızı alın, KDV\'ye kaydolun (geçerliyse), e-Vergi portalına erişin. CRMS kaydından hemen sonra.' },
          { step: 'İlk çalışan → FPIOM + FZOM + AVRM', desc: 'Çalışanı M1 formuyla kaydedin. MPIN sistemi FPIOM, FZOM ve AVRM\'yi otomatik olarak bilgilendirir.' },
          { step: 'Her ay → UJP (MPIN)', desc: 'Maaşlar ve primler için MPIN\'i 15\'ine kadar dosyalayın. KDV beyannamesi 25\'ine kadar (KDV mükellefi iseniz).' },
          { step: 'Yıllık → CRMS + UJP', desc: 'CRMS\'ye yıllık hesaplar (28.02), UJP\'ye kurumlar vergisi (15.03), kişisel gelir vergisi için PDD-GDP.' },
          { step: 'Gerektiğinde → Müfettişlik, Gümrük', desc: 'İş uyuşmazlıkları veya denetimler için Çalışma Müfettişliği. Mal ithalatı/ihracatı için Gümrük İdaresi.' },
        ],
      },
      {
        title: 'Facturino Tüm Kurumlara Nasıl Yardımcı Olur',
        content: 'Facturino, gerekli tüm raporları ve formları tek bir yerde birleştirir. 6-7 farklı portal ve son tarihle uğraşmak yerine, Facturino otomatik olarak MPIN formlarını, KDV beyannamelerini, yıllık raporları oluşturur ve her son tarihten önce hatırlatma gönderir.',
        items: [
          'Otomatik MPIN oluşturma — e-Vergi için hazır XML',
          'KDV hesaplama ve kayıtlar — UJP gereksinimleriyle uyumlu',
          'Yıllık hesaplar — MKD standartlarına göre bilanço ve gelir tablosu',
          'Hatırlatmalı vergi takvimi — hiçbir son tarihi kaçırmayın',
          'Çalışan kayıtları — hizmet geçmişi, maaşlar, hastalık izinleri tek yerde',
          'Denetime hazır raporlar — Müfettişlik inceleme talep ederse hazır',
          'Gümrük belgeleri — ithalat/ihracat için faturalar ve proforma faturalar',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'mpin-obrazec', title: 'MPIN Formu: Aylık Bordro Beyanname Rehberi' },
      { slug: 'rokovi-ujp-2026', title: 'Vergi Takvimi 2026: Tüm UJP Tarihleri' },
      { slug: 'otvoranje-firma-mk', title: 'Kuzey Makedonya\'da Şirket Nasıl Kurulur' },
      { slug: 'registracija-firma-cekor-po-cekor', title: 'Firma Kaydı: Adım Adım Kontrol Listesi' },
      { slug: 'kazni-ujp-2026', title: 'UJP Cezaları: İhlaller ve Para Cezaları 2026' },
    ],
    cta: {
      title: 'Tüm kurumlar, tek yazılım',
      desc: 'Facturino, UJP, CRMS ve diğer tüm kurumlar için gerekli formları ve raporları oluşturur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function DrzavniInstituciiPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = { mk: 'Блог', en: 'Blog', sq: 'Blog', tr: 'Blog' }[locale]
  const homeLabel = { mk: 'Почетна', en: 'Home', sq: 'Kryefaqja', tr: 'Ana Sayfa' }[locale]

  const articleLd = articleJsonLd({
    locale,
    slug: 'drzavni-institucii-za-firmi',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['ЦРСМ', 'UJP', 'МПИН', 'mpinform', 'државен инспекторат', 'government agencies macedonia', 'ФПИОМ', 'ФЗОМ', 'АВРМ', 'царинска управа'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/drzavni-institucii-za-firmi` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Што е ЦРСМ?', answer: 'ЦРСМ (Централен регистар на Република Северна Македонија) е институцијата каде се регистрираат сите правни лица, се запишуваат промени во сопственоста и се поднесуваат годишни сметки.' },
        { question: 'Што е УЈП?', answer: 'УЈП (Управа за јавни приходи) е даночната администрација на Македонија. Управува со данок на добивка, ДДВ, персонален данок на доход и МПИН системот за плати и придонеси.' },
        { question: 'Каде се регистрира МПИН?', answer: 'МПИН се регистрира електронски на порталот mpinform.ujp.gov.mk или лично во регионална канцеларија на УЈП. Потребен е МПИН-1 образец за регистрација на обврзник.' },
      ])) }} />
      {/* ARTICLE HEADER */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>
        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          <Link href={`/${locale}/blog`} className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors">{t.backLink}</Link>
          <div className="mb-4"><span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600">{t.tag}</span></div>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">{t.title}</h1>
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span className="flex items-center gap-1.5"><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>{t.publishDate}</span>
            <span className="flex items-center gap-1.5"><svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>{t.readTime}</span>
          </div>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">{t.intro}</p>
        </div>
      </section>

      {/* ARTICLE BODY */}
      <section className="py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="space-y-12">
            {t.sections.map((section, i) => (
              <div key={i}>
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{section.title}</h2>
                {section.content && (<p className="text-gray-700 leading-relaxed text-lg">{section.content}</p>)}
                {section.items && (
                  <ul className="space-y-3 mt-2">
                    {section.items.map((item, j) => (
                      <li key={j} className="flex items-start gap-3">
                        <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center"><svg className="w-3 h-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg></span>
                        <span className="text-gray-700 leading-relaxed">{item}</span>
                      </li>
                    ))}
                  </ul>
                )}
                {section.steps && (
                  <ol className="space-y-6 mt-4">
                    {section.steps.map((s, j) => (
                      <li key={j} className="flex items-start gap-4">
                        <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold flex items-center justify-center mt-0.5">{j + 1}</span>
                        <div><h3 className="font-semibold text-gray-900 text-lg">{s.step}</h3><p className="text-gray-600 leading-relaxed mt-1">{s.desc}</p></div>
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

      {/* BOTTOM CTA */}
      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />
        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">{t.cta.title}</h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">{t.cta.desc}</p>
          <a href="https://app.facturino.mk/signup" className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
            {t.cta.button}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </a>
        </div>
      </section>
    </main>
  )
}
