import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/dooel-vodich-2026', {
    title: {
      mk: 'ДООЕЛ во Македонија 2026: Даноци, обврски и годишна сметка',
      en: 'Single-Member LLC (DOOEL) in North Macedonia 2026: Taxes, Obligations & Annual Accounts',
      sq: 'SHPKNJP në Maqedoni 2026: Tatimet, detyrimet dhe llogaria vjetore',
      tr: 'Kuzey Makedonya DOOEL 2026: Vergiler, Yükümlülükler ve Yıllık Hesaplar',
    },
    description: {
      mk: 'Комплетен водич за ДООЕЛ: даночни стапки (10% данок на добивка, ДДВ при >2М МКД), годишна сметка, обврски кон УЈП и споредба со ДОО и паушалец.',
      en: 'Complete guide to DOOEL (single-member LLC) in North Macedonia: 10% corporate tax, VAT threshold at 2M MKD, annual filing, UJP obligations and comparison with DOO and sole trader.',
      sq: 'Udhëzues i plotë për SHPKNJP: tatimi 10% mbi fitimin, TVSH mbi 2M MKD, llogaria vjetore, detyrimet ndaj DAP dhe krahasimi me SHPK dhe paushalist.',
      tr: 'DOOEL rehberi: %10 kurumlar vergisi, 2M MKD KDV eşiği, yıllık hesaplar, UJP yükümlülükleri ve DOO/düz oranlı vergi karşılaştırması.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'ДООЕЛ во Македонија 2026: Даноци, обврски и годишна сметка',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro:
      'ДООЕЛ (Друштво со ограничена одговорност основано од едно лице) е најпопуларната правна форма за мали бизниси во Македонија. Со основен капитал од само 5.000 МКД и ограничена одговорност, ДООЕЛ е идеален за фриленсери, мали продавници и услужни дејности. Но обврските не се мали — данок на добивка, ДДВ, МПИН, годишна сметка и редовно книговодство. Овој водич ги покрива сите даночни обврски, рокови и практични совети за водење на ДООЕЛ во 2026.',
    sections: [
      {
        title: 'Што е ДООЕЛ?',
        content:
          'ДООЕЛ е трговско друштво основано од едно физичко или правно лице. Основачот има ограничена одговорност — одговара само до висината на вложениот капитал, не со личниот имот. Ова е клучната разлика од паушалец (самостоен вршител на дејност) каде одговорноста е неограничена.',
        items: [
          'Минимален основен капитал: 5.000 МКД (околу €80)',
          'Основач: 1 лице (физичко или правно)',
          'Ограничена одговорност — личниот имот е заштитен',
          'Регистрација во ЦРСМ (Централен регистар) — трае 1-2 работни дена',
          'Добива ЕДБ (Единствен Даночен Број) автоматски при регистрација',
          'Може да вработува неограничен број работници',
        ],
        steps: null,
      },
      {
        title: 'Даночни обврски на ДООЕЛ',
        content:
          'ДООЕЛ плаќа неколку видови даноци. Еве ги стапките и роковите за 2026:',
        items: null,
        steps: [
          { step: 'Данок на добивка — 10%', desc: 'Се пресметува на добивката (приходи минус признати расходи). Аконтациите се плаќаат месечно до 15-ти, годишен данок на добивка (ДД) до 15 март наредната година.' },
          { step: 'ДДВ — 18% / 5% / 10%', desc: 'Задолжителна регистрација кога годишниот промет ќе надмине 2.000.000 МКД. Стандардна стапка 18%, намалена 5% (храна, лекови), 10% (угостителство). ДДВ-04 пријава квартално.' },
          { step: 'Персонален данок на доход — 10%', desc: 'На платите на вработените. Се задржува при исплата и се уплаќа со МПИН образец месечно до 15-ти.' },
          { step: 'Придонеси — 28% од бруто плата', desc: 'Пензиско 18,8%, здравствено 7,5%, невработеност 1,2%, дополнително 0,5%. Сите се одбиваат од бруто платата.' },
          { step: 'Данок на дивиденда — 10%', desc: 'Кога основачот повлекува добивка како дивиденда. Се пресметува на нето дивидендата.' },
        ],
      },
      {
        title: 'Годишна сметка и финансиски извештаи',
        content:
          'Секој ДООЕЛ е должен да поднесе годишна сметка до 28 февруари (за претходната година) и ДБ-ВП (Данок на добивка — Даночен биланс) до 15 март. Еве што ви треба:',
        items: [
          'Биланс на состојба (актива = пасива)',
          'Биланс на успех (приходи, расходи, добивка/загуба)',
          'ДБ-ВП образец — даночен биланс за данок на добивка',
          'Поднесување во електронска форма преку e-Tax (etax.ujp.gov.mk)',
          'Мали ДООЕЛ (под 50 вработени, под €2М приход) водат поедноставено книговодство',
          'Ревизија е задолжителна САМО за ДООЕЛ со приход над €2М или над 50 вработени',
        ],
        steps: null,
      },
      {
        title: 'Месечни обврски и рокови',
        content:
          'ДООЕЛ има редовни месечни обврски кон УЈП и ФПИОМ:',
        items: null,
        steps: [
          { step: 'До 15-ти — МПИН образец', desc: 'Месечна пријава за плати и придонеси за сите вработени. Се поднесува електронски преку mpinform.ujp.gov.mk.' },
          { step: 'До 15-ти — Исплата на плата', desc: 'Плата мора да биде исплатена до 15-ти во наредниот месец (Чл. 106 ЗРО). Придонесите мора да бидат уплатени истиот ден.' },
          { step: 'До 15-ти — Аконтација данок на добивка', desc: 'Месечна аконтација = 1/12 од последниот годишен данок на добивка.' },
          { step: 'До 25-ти — ДДВ-04 (квартално)', desc: 'Ако сте ДДВ обврзник — квартална пријава. Рокови: 25 април, 25 јули, 25 октомври, 25 јануари.' },
          { step: 'Дневно — Книговодствена евиденција', desc: 'Редовно книжење на фактури, трошоци, банкарски извод, каса. Со Facturino ова е автоматизирано.' },
        ],
      },
      {
        title: 'ДООЕЛ vs ДОО vs Паушалец',
        content:
          'Која правна форма е најдобра за вас зависи од вашиот промет, ризик и потреба за вработени:',
        items: [
          'ДООЕЛ — 1 основач, 5.000 МКД капитал, ограничена одговорност, полно книговодство. Идеален за мали бизниси кои растат.',
          'ДОО — 2+ основачи (или 1 лице со повеќе фирми), 5.000 МКД капитал, ограничена одговорност. За партнерства и поголеми проекти.',
          'Паушалец — Без основен капитал, неограничена одговорност, фиксен данок (~5.000-15.000 МКД/месец). За фриленсери со промет под 3М МКД/год.',
          'ДООЕЛ е најдобар избор кога: имате промет над 1М МКД, сакате ограничена одговорност, планирате вработени, или работите со поголеми клиенти кои бараат фактури со ДДВ.',
        ],
        steps: null,
      },
      {
        title: 'Практични совети за нов ДООЕЛ',
        content:
          'Ако штотуку отворивте ДООЕЛ или планирате, еве неколку практични совети:',
        items: [
          'Отворете деловна сметка веднаш — не мешајте лични и деловни средства',
          'Изберете сметководствен софтвер од почеток — не чекајте да се натрупаат документи',
          'Следете го прагот за ДДВ (2М МКД) — доброволна регистрација може да биде корисна ако имате големи влезни фактури',
          'Чувајте ги сите фактури и фискални сметки минимум 10 години',
          'Не повлекувајте дивиденда пред да завршите годишна сметка — платете данок прво',
          'Ангажирајте сметководител или користете Facturino — грешките во УЈП пријавите носат казни',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'otvoranje-firma-mk', title: 'Како да отворите фирма во Македонија' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пријава' },
      { slug: 'godishna-danocna-prijava-2026', title: 'Годишна даночна пријава 2026' },
    ],
    bottomCta: {
      title: 'ДООЕЛ? Facturino е за вас.',
      subtitle: 'Фактури, ДДВ, плати и годишна сметка — сè на едно место. Бесплатен план за почеток.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Single-Member LLC (DOOEL) in North Macedonia 2026: Taxes, Obligations & Annual Accounts',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro:
      'DOOEL (Друштво со ограничена одговорност основано од едно лице) is the most popular legal form for small businesses in North Macedonia. With a minimum share capital of just 5,000 MKD (about €80) and limited liability, DOOEL is ideal for freelancers, small shops, and service businesses. But the obligations are real — corporate tax, VAT, MPIN, annual accounts, and regular bookkeeping. This guide covers all tax obligations, deadlines, and practical tips for running a DOOEL in 2026.',
    sections: [
      {
        title: 'What is a DOOEL?',
        content:
          'A DOOEL is a limited liability company founded by a single person (natural or legal). The founder\'s liability is limited to the invested capital — personal assets are protected. This is the key difference from a sole trader (paušalec) where liability is unlimited.',
        items: [
          'Minimum share capital: 5,000 MKD (approximately €80)',
          'Founder: 1 person (natural or legal entity)',
          'Limited liability — personal assets are protected',
          'Registration at CRMS (Central Registry) — takes 1-2 business days',
          'Receives EDB (tax identification number) automatically upon registration',
          'Can employ unlimited number of workers',
        ],
        steps: null,
      },
      {
        title: 'Tax obligations of a DOOEL',
        content:
          'A DOOEL pays several types of taxes. Here are the rates and deadlines for 2026:',
        items: null,
        steps: [
          { step: 'Corporate tax — 10%', desc: 'Calculated on profit (revenue minus recognized expenses). Monthly advance payments due by the 15th; annual corporate tax return (DD) due by March 15 of the following year.' },
          { step: 'VAT — 18% / 5% / 10%', desc: 'Mandatory registration when annual turnover exceeds 2,000,000 MKD. Standard rate 18%, reduced 5% (food, medicines), 10% (hospitality). DDV-04 filed quarterly.' },
          { step: 'Personal income tax — 10%', desc: 'On employee salaries. Withheld at source and paid via MPIN form monthly by the 15th.' },
          { step: 'Contributions — 28% of gross salary', desc: 'Pension 18.8%, health 7.5%, unemployment 1.2%, supplementary 0.5%. All deducted from gross salary.' },
          { step: 'Dividend tax — 10%', desc: 'When the founder withdraws profit as a dividend. Calculated on the net dividend amount.' },
        ],
      },
      {
        title: 'Annual accounts and financial statements',
        content:
          'Every DOOEL must submit annual accounts by February 28 (for the previous year) and DB-VP (corporate tax return) by March 15. Here\'s what you need:',
        items: [
          'Balance sheet (assets = liabilities + equity)',
          'Income statement (revenue, expenses, profit/loss)',
          'DB-VP form — tax balance for corporate tax',
          'Electronic submission via e-Tax (etax.ujp.gov.mk)',
          'Small DOOELs (under 50 employees, under €2M revenue) use simplified bookkeeping',
          'Audit is mandatory ONLY for DOOELs with revenue over €2M or over 50 employees',
        ],
        steps: null,
      },
      {
        title: 'Monthly obligations and deadlines',
        content:
          'A DOOEL has regular monthly obligations to UJP and FPIOM:',
        items: null,
        steps: [
          { step: 'By the 15th — MPIN form', desc: 'Monthly declaration of salaries and contributions for all employees. Filed electronically via mpinform.ujp.gov.mk.' },
          { step: 'By the 15th — Salary payment', desc: 'Salary must be paid by the 15th of the following month (Art. 106 Labor Relations Act). Contributions must be paid the same day.' },
          { step: 'By the 15th — Corporate tax advance', desc: 'Monthly advance = 1/12 of the last annual corporate tax.' },
          { step: 'By the 25th — DDV-04 (quarterly)', desc: 'If VAT-registered — quarterly return. Deadlines: April 25, July 25, October 25, January 25.' },
          { step: 'Daily — Bookkeeping records', desc: 'Regular recording of invoices, expenses, bank statements, cash. With Facturino this is automated.' },
        ],
      },
      {
        title: 'DOOEL vs DOO vs Sole Trader',
        content:
          'Which legal form is best for you depends on your turnover, risk, and need for employees:',
        items: [
          'DOOEL — 1 founder, 5,000 MKD capital, limited liability, full bookkeeping. Ideal for growing small businesses.',
          'DOO — 2+ founders (or 1 person with multiple companies), 5,000 MKD capital, limited liability. For partnerships and larger projects.',
          'Sole Trader (Paušalec) — No minimum capital, unlimited liability, flat tax (~5,000-15,000 MKD/month). For freelancers with turnover under 3M MKD/year.',
          'DOOEL is the best choice when: turnover exceeds 1M MKD, you want limited liability, you plan to hire employees, or you work with larger clients requiring VAT invoices.',
        ],
        steps: null,
      },
      {
        title: 'Practical tips for a new DOOEL',
        content:
          'If you just opened a DOOEL or are planning to, here are practical tips:',
        items: [
          'Open a business bank account immediately — don\'t mix personal and business funds',
          'Choose accounting software from the start — don\'t wait for documents to pile up',
          'Monitor the VAT threshold (2M MKD) — voluntary registration can be beneficial if you have large input invoices',
          'Keep all invoices and fiscal receipts for a minimum of 10 years',
          'Don\'t withdraw dividends before completing annual accounts — pay tax first',
          'Hire an accountant or use Facturino — mistakes in UJP filings carry penalties',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'otvoranje-firma-mk', title: 'How to Register a Company in North Macedonia' },
      { slug: 'danok-na-dobivka', title: 'Corporate Tax: Rates, Deadlines & Filing' },
      { slug: 'godishna-danocna-prijava-2026', title: 'Annual Tax Return North Macedonia 2026' },
    ],
    bottomCta: {
      title: 'Running a DOOEL? Facturino is built for you.',
      subtitle: 'Invoicing, VAT, payroll and annual accounts — all in one place. Free plan to get started.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'SHPKNJP në Maqedoni 2026: Tatimet, detyrimet dhe llogaria vjetore',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro:
      'SHPKNJP (ДООЕЛ) është forma juridike më e popullarizuar për bizneset e vogla në Maqedoni. Me kapital themeltar prej vetëm 5.000 MKD (rreth €80) dhe përgjegjësi të kufizuar, SHPKNJP është ideale për freelancerë, dyqane të vogla dhe biznese shërbimesh. Por detyrimet janë reale — tatimi mbi fitimin, TVSH, MPIN, llogaria vjetore dhe kontabiliteti i rregullt. Ky udhëzues mbulon të gjitha detyrimet tatimore, afatet dhe këshillat praktike për vitin 2026.',
    sections: [
      {
        title: 'Çfarë është SHPKNJP?',
        content:
          'SHPKNJP është shoqëri tregtare e themeluar nga një person fizik ose juridik. Themeluesi ka përgjegjësi të kufizuar — përgjigjet vetëm deri në lartësinë e kapitalit të investuar, jo me pasurinë personale.',
        items: [
          'Kapitali minimal: 5.000 MKD (rreth €80)',
          'Themelues: 1 person (fizik ose juridik)',
          'Përgjegjësi e kufizuar — pasuria personale është e mbrojtur',
          'Regjistrimi në QRMK (Regjistri Qendror) — zgjat 1-2 ditë pune',
          'Merr EDB (numër identifikimi tatimor) automatikisht pas regjistrimit',
          'Mund të punësojë numër të pakufizuar punëtorësh',
        ],
        steps: null,
      },
      {
        title: 'Detyrimet tatimore të SHPKNJP',
        content:
          'SHPKNJP paguan disa lloje tatimesh. Ja normat dhe afatet për 2026:',
        items: null,
        steps: [
          { step: 'Tatimi mbi fitimin — 10%', desc: 'Llogaritet mbi fitimin (të ardhurat minus shpenzimet e njohura). Akontacionet mujore deri më 15; tatimi vjetor (DD) deri më 15 mars të vitit pasardhës.' },
          { step: 'TVSH — 18% / 5% / 10%', desc: 'Regjistrimi i detyrueshëm kur qarkullimi vjetor kalon 2.000.000 MKD. Norma standarde 18%, e reduktuar 5% (ushqim, ilaçe), 10% (hotelieri). DDV-04 tremujore.' },
          { step: 'Tatimi mbi të ardhurat personale — 10%', desc: 'Mbi pagat e punëtorëve. Mbahet në burim dhe paguhet me formularin MPIN mujor deri më 15.' },
          { step: 'Kontributet — 28% e pagës bruto', desc: 'Pensioni 18,8%, shëndetësia 7,5%, papunësia 1,2%, shtesë 0,5%. Të gjitha zbriten nga paga bruto.' },
          { step: 'Tatimi mbi dividendën — 10%', desc: 'Kur themeluesi tërheq fitimin si dividendë. Llogaritet mbi dividendën neto.' },
        ],
      },
      {
        title: 'Llogaria vjetore dhe pasqyrat financiare',
        content:
          'Çdo SHPKNJP duhet të dorëzojë llogarinë vjetore deri më 28 shkurt (për vitin e kaluar) dhe DB-VP deri më 15 mars:',
        items: [
          'Bilanci i gjendjes (aktivi = pasivi + kapitali)',
          'Bilanci i suksesit (të ardhurat, shpenzimet, fitimi/humbja)',
          'Formulari DB-VP — bilanci tatimor për tatimin mbi fitimin',
          'Dorëzimi elektronik përmes e-Tax (etax.ujp.gov.mk)',
          'SHPKNJP të vogla (nën 50 punëtorë, nën €2M të ardhura) përdorin kontabilitet të thjeshtësuar',
          'Auditimi është i detyrueshëm VETËM për SHPKNJP me të ardhura mbi €2M ose mbi 50 punëtorë',
        ],
        steps: null,
      },
      {
        title: 'Detyrimet mujore dhe afatet',
        content:
          'SHPKNJP ka detyrime mujore të rregullta ndaj DAP dhe FPIOM:',
        items: null,
        steps: [
          { step: 'Deri më 15 — Formulari MPIN', desc: 'Deklarata mujore e pagave dhe kontributeve për të gjithë punëtorët. Dorëzohet elektronikisht përmes mpinform.ujp.gov.mk.' },
          { step: 'Deri më 15 — Pagesa e pagës', desc: 'Paga duhet të paguhet deri më 15 të muajit pasardhës (Neni 106 LMP). Kontributet duhet të paguhen po atë ditë.' },
          { step: 'Deri më 15 — Akontacion tatimi mbi fitimin', desc: 'Akontacioni mujor = 1/12 e tatimit vjetor të fundit mbi fitimin.' },
          { step: 'Deri më 25 — DDV-04 (tremujore)', desc: 'Nëse jeni regjistruar për TVSH — deklarata tremujore. Afatet: 25 prill, 25 korrik, 25 tetor, 25 janar.' },
          { step: 'Ditore — Evidenca kontabël', desc: 'Regjistrimi i rregullt i faturave, shpenzimeve, ekstraktit bankar, arkës. Me Facturino kjo është e automatizuar.' },
        ],
      },
      {
        title: 'SHPKNJP vs SHPK vs Paushalist',
        content:
          'Cila formë juridike është më e mira për ju varet nga qarkullimi, risku dhe nevoja për punëtorë:',
        items: [
          'SHPKNJP — 1 themelues, 5.000 MKD kapital, përgjegjësi e kufizuar, kontabilitet i plotë. Ideale për biznese të vogla në rritje.',
          'SHPK — 2+ themelues, 5.000 MKD kapital, përgjegjësi e kufizuar. Për partneritete dhe projekte më të mëdha.',
          'Paushalist — Pa kapital minimal, përgjegjësi e pakufizuar, tatim fiks (~5.000-15.000 MKD/muaj). Për freelancerë me qarkullim nën 3M MKD/vit.',
          'SHPKNJP është zgjidhja më e mirë kur: qarkullimi kalon 1M MKD, dëshironi përgjegjësi të kufizuar, planifikoni punëtorë, ose punoni me klientë më të mëdhenj.',
        ],
        steps: null,
      },
      {
        title: 'Këshilla praktike për SHPKNJP të re',
        content:
          'Nëse sapo keni hapur SHPKNJP ose planifikoni, ja disa këshilla praktike:',
        items: [
          'Hapni llogari bankare biznesi menjëherë — mos i përzieni fondet personale me ato të biznesit',
          'Zgjidhni softuer kontabiliteti që në fillim — mos prisni të grumbullohen dokumentet',
          'Ndiqni pragun e TVSH (2M MKD) — regjistrimi vullnetar mund të jetë i dobishëm nëse keni fatura hyrëse të mëdha',
          'Ruajini të gjitha faturat dhe kuponat fiskale minimumi 10 vjet',
          'Mos tërhiqni dividendë para se të përfundoni llogarinë vjetore — paguani tatimin fillimisht',
          'Angazhoni kontabilist ose përdorni Facturino — gabimet në deklaratat e DAP sjellin gjoba',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'otvoranje-firma-mk', title: 'Si të regjistroni firmë në Maqedoni' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe deklarimi' },
      { slug: 'godishna-danocna-prijava-2026', title: 'Deklarata vjetore tatimore 2026' },
    ],
    bottomCta: {
      title: 'Keni SHPKNJP? Facturino është për ju.',
      subtitle: 'Fatura, TVSH, paga dhe llogaria vjetore — gjithçka në një vend. Plan falas për fillim.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloğa dön',
    tag: 'Rehber',
    title: 'Kuzey Makedonya DOOEL 2026: Vergiler, Yükümlülükler ve Yıllık Hesaplar',
    publishDate: '23 Mayıs 2026',
    readTime: '10 dk okuma',
    intro:
      'DOOEL (tek kişilik limited şirket), Kuzey Makedonya\'da küçük işletmeler için en popüler hukuki biçimdir. Yalnızca 5.000 MKD (yaklaşık €80) sermaye ve sınırlı sorumlulukla, DOOEL serbest çalışanlar, küçük dükkanlar ve hizmet işletmeleri için idealdir. Ancak yükümlülükler gerçektir — kurumlar vergisi, KDV, MPIN, yıllık hesaplar ve düzenli muhasebe. Bu rehber 2026\'daki tüm vergi yükümlülüklerini, tarihleri ve pratik ipuçlarını kapsar.',
    sections: [
      {
        title: 'DOOEL nedir?',
        content:
          'DOOEL, tek bir gerçek veya tüzel kişi tarafından kurulan sınırlı sorumlu ticaret şirketidir. Kurucunun sorumluluğu yatırılan sermayeyle sınırlıdır — kişisel varlıklar korunur.',
        items: [
          'Asgari sermaye: 5.000 MKD (yaklaşık €80)',
          'Kurucu: 1 kişi (gerçek veya tüzel kişi)',
          'Sınırlı sorumluluk — kişisel varlıklar korunur',
          'CRMS (Merkezi Sicil) kaydı — 1-2 iş günü sürer',
          'Kayıt sırasında otomatik olarak EDB (vergi numarası) alır',
          'Sınırsız sayıda çalışan istihdam edebilir',
        ],
        steps: null,
      },
      {
        title: 'DOOEL\'in vergi yükümlülükleri',
        content:
          'Bir DOOEL birkaç türde vergi öder. 2026 oranları ve tarihleri:',
        items: null,
        steps: [
          { step: 'Kurumlar vergisi — %10', desc: 'Kâr üzerinden hesaplanır (gelir eksi kabul edilen giderler). Aylık avans ödemeleri 15\'ine kadar; yıllık kurumlar vergisi beyannamesi (DD) ertesi yıl 15 Mart\'a kadar.' },
          { step: 'KDV — %18 / %5 / %10', desc: 'Yıllık ciro 2.000.000 MKD\'yi aştığında zorunlu kayıt. Standart oran %18, indirimli %5 (gıda, ilaç), %10 (otelcilik). DDV-04 üç aylık beyanname.' },
          { step: 'Gelir vergisi — %10', desc: 'Çalışan maaşları üzerinden. Kaynakta kesilerek MPIN formuyla aylık 15\'ine kadar ödenir.' },
          { step: 'Primler — brüt maaşın %28\'i', desc: 'Emeklilik %18,8, sağlık %7,5, işsizlik %1,2, ek %0,5. Tümü brüt maaştan düşülür.' },
          { step: 'Temettü vergisi — %10', desc: 'Kurucu kârı temettü olarak çektiğinde. Net temettü tutarı üzerinden hesaplanır.' },
        ],
      },
      {
        title: 'Yıllık hesaplar ve mali tablolar',
        content:
          'Her DOOEL, yıllık hesapları 28 Şubat\'a kadar (önceki yıl için) ve DB-VP\'yi 15 Mart\'a kadar sunmalıdır:',
        items: [
          'Bilanço (aktifler = pasifler + özkaynak)',
          'Gelir tablosu (gelirler, giderler, kâr/zarar)',
          'DB-VP formu — kurumlar vergisi vergi bilançosu',
          'e-Tax üzerinden elektronik sunum (etax.ujp.gov.mk)',
          'Küçük DOOEL\'ler (50\'den az çalışan, €2M\'den az gelir) basitleştirilmiş muhasebe kullanır',
          'Denetim YALNIZCA €2M\'den fazla geliri veya 50\'den fazla çalışanı olan DOOEL\'ler için zorunludur',
        ],
        steps: null,
      },
      {
        title: 'Aylık yükümlülükler ve tarihler',
        content:
          'DOOEL\'in UJP ve FPIOM\'a düzenli aylık yükümlülükleri vardır:',
        items: null,
        steps: [
          { step: '15\'ine kadar — MPIN formu', desc: 'Tüm çalışanlar için maaş ve prim aylık beyanı. mpinform.ujp.gov.mk üzerinden elektronik dosyalanır.' },
          { step: '15\'ine kadar — Maaş ödemesi', desc: 'Maaş, takip eden ayın 15\'ine kadar ödenmelidir (İş İlişkileri Kanunu Md. 106). Primler aynı gün ödenmelidir.' },
          { step: '15\'ine kadar — Kurumlar vergisi avansı', desc: 'Aylık avans = son yıllık kurumlar vergisinin 1/12\'si.' },
          { step: '25\'ine kadar — DDV-04 (üç aylık)', desc: 'KDV mükelleliyseniz — üç aylık beyanname. Tarihler: 25 Nisan, 25 Temmuz, 25 Ekim, 25 Ocak.' },
          { step: 'Günlük — Muhasebe kayıtları', desc: 'Faturaların, giderlerin, banka ekstresinin düzenli kaydı. Facturino ile bu otomatikleştirilir.' },
        ],
      },
      {
        title: 'DOOEL vs DOO vs Düz Oranlı Vergi',
        content:
          'Hangi hukuki biçimin sizin için en iyi olduğu cironuza, riskinize ve çalışan ihtiyacınıza bağlıdır:',
        items: [
          'DOOEL — 1 kurucu, 5.000 MKD sermaye, sınırlı sorumluluk, tam muhasebe. Büyüyen küçük işletmeler için ideal.',
          'DOO — 2+ kurucu, 5.000 MKD sermaye, sınırlı sorumluluk. Ortaklıklar ve daha büyük projeler için.',
          'Düz Oranlı Vergi (Paušalec) — Asgari sermaye yok, sınırsız sorumluluk, sabit vergi (~5.000-15.000 MKD/ay). Yıllık 3M MKD\'nin altında cirolu serbest çalışanlar için.',
          'DOOEL en iyi seçimdir: ciro 1M MKD\'yi aştığında, sınırlı sorumluluk istediğinizde, çalışan planladığınızda veya KDV\'li fatura isteyen büyük müşterilerle çalıştığınızda.',
        ],
        steps: null,
      },
      {
        title: 'Yeni DOOEL için pratik ipuçları',
        content:
          'DOOEL\'inizi yeni açtıysanız veya planlıyorsanız, pratik ipuçları:',
        items: [
          'Hemen ticari banka hesabı açın — kişisel ve ticari fonları karıştırmayın',
          'Baştan muhasebe yazılımı seçin — belgelerin birikmesini beklemeyin',
          'KDV eşiğini izleyin (2M MKD) — büyük girdi faturalarınız varsa gönüllü kayıt faydalı olabilir',
          'Tüm faturaları ve fiş makbuzlarını en az 10 yıl saklayın',
          'Yıllık hesapları tamamlamadan temettü çekmeyin — önce vergiyi ödeyin',
          'Muhasebeci tutun veya Facturino kullanın — UJP beyannamelerindeki hatalar ceza getirir',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'otvoranje-firma-mk', title: 'Kuzey Makedonya\'da Şirket Nasıl Kurulur' },
      { slug: 'danok-na-dobivka', title: 'Kurumlar Vergisi: Oranlar, Tarihler ve Beyanname' },
      { slug: 'godishna-danocna-prijava-2026', title: 'Yıllık Vergi Beyannamesi 2026' },
    ],
    bottomCta: {
      title: 'DOOEL\'iniz mi var? Facturino tam size göre.',
      subtitle: 'Faturalama, KDV, bordro ve yıllık hesaplar — hepsi tek yerde. Başlamak için ücretsiz plan.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function DooelVodichPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = locale === 'mk' ? 'Блог' : 'Blog'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const articleLd = articleJsonLd({
    locale,
    slug: 'dooel-vodich-2026',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['ДООЕЛ', 'DOOEL', 'LLC', 'company', 'tax', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/dooel-vodich-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/blog`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={`/${locale}/blog/${ra.slug}`}
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
