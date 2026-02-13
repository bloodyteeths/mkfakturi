import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/bilans-na-sostojba', {
    title: {
      mk: 'Биланс на состојба и биланс на успех: AOP ознаки — Facturino',
      en: 'Balance Sheet & Income Statement: AOP Codes — Facturino',
      sq: 'Bilanci dhe pasqyra e t\u00eb ardhurave: Kodet AOP — Facturino',
      tr: 'Bilan\u00e7o ve gelir tablosu: AOP kodlar\u0131 — Facturino',
    },
    description: {
      mk: 'Детален водич за Образец 36 и 37 — АКТИВА, ПАСИВА, приходи, расходи, AOP ознаки и практични примери за годишна сметка.',
      en: 'Detailed guide to Form 36 and 37 — assets, liabilities, revenues, expenses, AOP codes and practical examples for annual accounts.',
      sq: 'Udh\u00ebzues i detajuar p\u00ebr Formularin 36 dhe 37 — aktivet, detyrimet, t\u00eb ardhurat, shpenzimet dhe kodet AOP.',
      tr: 'Form 36 ve 37 detayl\u0131 rehber — varl\u0131klar, y\u00fck\u00fcml\u00fcl\u00fckler, gelirler, giderler ve AOP kodlar\u0131.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Types                                                              */
/* ------------------------------------------------------------------ */
type AopRow = { aop: string; name: string; desc: string }

type Subsection = {
  title: string
  desc?: string
  table: AopRow[]
}

type Section = {
  id: string
  title: string
  desc: string
  subsections?: Subsection[]
  rule?: string
  tips?: string[]
  formula?: string
}

type LocaleCopy = {
  title: string
  publishDate: string
  readTime: string
  tag: string
  intro: string
  sections: Section[]
  ctaSection: { title: string; desc: string; cta: string }
  backLink: string
  tableHeaders: { aop: string; name: string; desc: string }
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy: Record<Locale, LocaleCopy> = {
  mk: {
    title: 'Биланс на состојба и биланс на успех: AOP ознаки и структура',
    publishDate: '20 јануари 2026',
    readTime: '10 мин читање',
    tag: 'Едукација',
    intro:
      'AOP ознаките (аналитичка ознака на позиција) се стандардизирани кодови кои ги означуваат позициите во финансиските извештаи. Секоја компанија во Македонија мора да ги користи при поднесување на годишната сметка. Во овој водич ги објаснуваме структурата на Образец 36 и 37 со практични примери.',
    sections: [
      {
        id: 'bilans-sostojba',
        title: 'Биланс на состојба (Образец 36)',
        desc: 'Билансот на состојба (Balance Sheet) ја прикажува финансиската позиција на компанијата на одреден датум — обично 31.12. Се состои од два дела:',
        subsections: [
          {
            title: 'АКТИВА (Средства)',
            desc: 'Сè што компанијата поседува или има право да побарува.',
            table: [
              { aop: '001', name: 'ВКУПНА АКТИВА', desc: 'Збир на сите средства' },
              { aop: '002', name: 'Нетековни средства', desc: 'Основни средства, нематеријални средства, долгорочни вложувања' },
              { aop: '003', name: 'Нематеријални средства', desc: 'Патенти, лиценци, софтвер, гудвил' },
              { aop: '010', name: 'Материјални средства', desc: 'Земјиште, згради, опрема, возила' },
              { aop: '020', name: 'Долгорочни финансиски вложувања', desc: 'Акции, удели, долгорочни кредити' },
              { aop: '030', name: 'Тековни средства', desc: 'Залихи, побарувања, парични средства' },
              { aop: '031', name: 'Залихи', desc: 'Стоки, материјали, производство' },
              { aop: '040', name: 'Краткорочни побарувања', desc: 'Побарувања од купувачи, аванси' },
              { aop: '050', name: 'Парични средства', desc: 'Каса, жиро сметка, девизна сметка' },
            ],
          },
          {
            title: 'ПАСИВА (Обврски и капитал)',
            desc: 'Извори на финансирање — капитал на сопственикот и обврски кон трети лица.',
            table: [
              { aop: '060', name: 'ВКУПНА ПАСИВА', desc: 'Збир на капитал + обврски (= АКТИВА)' },
              { aop: '061', name: 'Капитал и резерви', desc: 'Основен капитал, задржана добивка, резерви' },
              { aop: '062', name: 'Основен капитал', desc: 'Регистриран капитал на друштвото' },
              { aop: '070', name: 'Задржана добивка', desc: 'Акумулирана добивка од претходни години' },
              { aop: '075', name: 'Долгорочни обврски', desc: 'Кредити > 1 година, обврзници' },
              { aop: '085', name: 'Краткорочни обврски', desc: 'Добавувачи, плати, даноци, кредити < 1 година' },
              { aop: '090', name: 'Обврски кон добавувачи', desc: 'Неплатени фактури' },
              { aop: '100', name: 'Обврски за даноци и придонеси', desc: 'ДДВ, данок на добивка, придонеси' },
            ],
          },
        ],
        rule: 'ПРАВИЛО: АКТИВА (AOP 001) = ПАСИВА (AOP 060). Ако не се совпаѓаат, билансот не е балансиран и ќе биде одбиен.',
      },
      {
        id: 'bilans-uspeh',
        title: 'Биланс на успех (Образец 37)',
        desc: 'Билансот на успех (Income Statement) ги прикажува приходите и расходите за годината. Крајниот резултат е добивка или загуба.',
        subsections: [
          {
            title: 'I. Приходи од работењето',
            table: [
              { aop: '201', name: 'Приходи од продажба', desc: 'Фактурирана продажба на стоки и услуги' },
              { aop: '210', name: 'Останати приходи', desc: 'Субвенции, кирии, наплатени отписи' },
              { aop: '220', name: 'Финансиски приходи', desc: 'Камати, курсни разлики (+)' },
              { aop: '246', name: 'ВКУПНИ ПРИХОДИ', desc: 'Збир на сите приходи' },
            ],
          },
          {
            title: 'II. Расходи од работењето',
            table: [
              { aop: '251', name: 'Набавна вредност на продадени стоки', desc: 'Трошок за продадена стока' },
              { aop: '260', name: 'Трошоци за вработени', desc: 'Бруто плати, придонеси, надоместоци' },
              { aop: '270', name: 'Амортизација', desc: 'Трошење на основни средства' },
              { aop: '275', name: 'Останати расходи', desc: 'Кирии, телефон, сметководствени услуги' },
              { aop: '280', name: 'Финансиски расходи', desc: 'Камати, курсни разлики (-)' },
              { aop: '293', name: 'ВКУПНИ РАСХОДИ', desc: 'Збир на сите расходи' },
            ],
          },
          {
            title: 'III. Резултат',
            table: [
              { aop: '244', name: 'Добивка од работењето', desc: 'Приходи > Расходи' },
              { aop: '245', name: 'Загуба од работењето', desc: 'Расходи > Приходи' },
              { aop: '248', name: 'Добивка пред оданочување', desc: 'Вкупна добивка пред данок' },
              { aop: '250', name: 'Данок на добивка', desc: '10% од основицата' },
              { aop: '255', name: 'Нето добивка', desc: 'Крајна добивка по одбивање данок' },
              { aop: '256', name: 'Нето загуба', desc: 'Крајна загуба' },
            ],
          },
        ],
      },
      {
        id: 'bruto-bilans',
        title: 'Бруто биланс (пробен биланс)',
        desc: 'Бруто билансот е листа на сите конта со колони: Дугува, Побарува, Салдо Дугува, Салдо Побарува. Служи за внатрешна контрола — вкупно Дугува мора да е еднакво на вкупно Побарува.',
        tips: [
          'Дугува = Побарува (мора да биде балансирано)',
          'Ако не е балансирано, има грешка во книжењата',
          'Бруто билансот не се поднесува официјално, но е основа за изготвување на Образец 36 и 37',
        ],
      },
      {
        id: 'db-vp',
        title: 'Даночен биланс (ДБ-ВП)',
        desc: 'Даночниот биланс (ДБ-ВП = Даночен биланс на вкупен приход) е формулар за пресметка на данокот на добивка. Се поднесува до УЈП (Управа за јавни приходи).',
        formula: 'Добивка пред оданочување \u00d7 10% = Данок на добивка',
        tips: [
          'Основица = Добивка пред оданочување (AOP 248)',
          'Стапка = 10% за сите компании (фиксна)',
          'Ако компанијата има загуба (AOP 249), данокот е 0',
          'Аконтативен данок се плаќа месечно (1/12 од претходногодишниот данок)',
          'Поднесување: до 15 март на etax.ujp.gov.mk',
        ],
      },
    ],
    ctaSection: {
      title: 'Facturino автоматски ги пополнува AOP ознаките',
      desc: 'Не треба рачно да ги пребарувате AOP кодовите. Facturino автоматски ги мапира сметките од контниот план на точните AOP позиции во Образец 36, 37 и ДБ-ВП.',
      cta: 'Започни бесплатно',
    },
    backLink: '\u2190 Назад кон блог',
    tableHeaders: { aop: 'AOP', name: 'Назив', desc: 'Опис' },
  },
  en: {
    title: 'Balance Sheet & Income Statement: AOP Codes and Structure',
    publishDate: 'January 20, 2026',
    readTime: '10 min read',
    tag: 'Education',
    intro:
      'AOP codes (analytical position identifiers) are standardized codes marking positions in Macedonian financial statements. Every company must use them when filing annual accounts. This guide explains the structure of Form 36 and 37 with practical examples.',
    sections: [
      {
        id: 'bilans-sostojba',
        title: 'Balance Sheet (Form 36)',
        desc: 'The Balance Sheet shows the financial position of a company at a specific date \u2014 usually December 31. It consists of two parts:',
        subsections: [
          {
            title: 'ASSETS (AKTIVA)',
            desc: 'Everything the company owns or has the right to claim.',
            table: [
              { aop: '001', name: 'TOTAL ASSETS', desc: 'Sum of all assets' },
              { aop: '002', name: 'Non-current assets', desc: 'Fixed assets, intangible assets, long-term investments' },
              { aop: '003', name: 'Intangible assets', desc: 'Patents, licenses, software, goodwill' },
              { aop: '010', name: 'Tangible assets', desc: 'Land, buildings, equipment, vehicles' },
              { aop: '020', name: 'Long-term financial investments', desc: 'Shares, stakes, long-term loans' },
              { aop: '030', name: 'Current assets', desc: 'Inventories, receivables, cash' },
              { aop: '031', name: 'Inventories', desc: 'Goods, materials, work in progress' },
              { aop: '040', name: 'Short-term receivables', desc: 'Trade receivables, advances' },
              { aop: '050', name: 'Cash and cash equivalents', desc: 'Cash register, bank account, foreign currency account' },
            ],
          },
          {
            title: 'LIABILITIES & EQUITY (PASIVA)',
            desc: 'Sources of financing \u2014 owner\u2019s equity and obligations to third parties.',
            table: [
              { aop: '060', name: 'TOTAL LIABILITIES & EQUITY', desc: 'Sum of equity + liabilities (= ASSETS)' },
              { aop: '061', name: 'Equity and reserves', desc: 'Share capital, retained earnings, reserves' },
              { aop: '062', name: 'Share capital', desc: 'Registered capital of the company' },
              { aop: '070', name: 'Retained earnings', desc: 'Accumulated profit from prior years' },
              { aop: '075', name: 'Long-term liabilities', desc: 'Loans > 1 year, bonds' },
              { aop: '085', name: 'Short-term liabilities', desc: 'Suppliers, salaries, taxes, loans < 1 year' },
              { aop: '090', name: 'Trade payables', desc: 'Unpaid invoices' },
              { aop: '100', name: 'Tax and contribution liabilities', desc: 'VAT, corporate income tax, contributions' },
            ],
          },
        ],
        rule: 'RULE: ASSETS (AOP 001) = LIABILITIES & EQUITY (AOP 060). If they don\u2019t match, the balance sheet is unbalanced and will be rejected.',
      },
      {
        id: 'bilans-uspeh',
        title: 'Income Statement (Form 37)',
        desc: 'The Income Statement shows revenues and expenses for the year. The final result is profit or loss.',
        subsections: [
          {
            title: 'I. Operating revenues',
            table: [
              { aop: '201', name: 'Sales revenue', desc: 'Invoiced sales of goods and services' },
              { aop: '210', name: 'Other revenues', desc: 'Subsidies, rent income, recovered write-offs' },
              { aop: '220', name: 'Financial revenues', desc: 'Interest, exchange rate gains (+)' },
              { aop: '246', name: 'TOTAL REVENUES', desc: 'Sum of all revenues' },
            ],
          },
          {
            title: 'II. Operating expenses',
            table: [
              { aop: '251', name: 'Cost of goods sold', desc: 'Cost of merchandise sold' },
              { aop: '260', name: 'Employee expenses', desc: 'Gross salaries, contributions, benefits' },
              { aop: '270', name: 'Depreciation', desc: 'Wear and tear of fixed assets' },
              { aop: '275', name: 'Other expenses', desc: 'Rent, telephone, accounting services' },
              { aop: '280', name: 'Financial expenses', desc: 'Interest, exchange rate losses (-)' },
              { aop: '293', name: 'TOTAL EXPENSES', desc: 'Sum of all expenses' },
            ],
          },
          {
            title: 'III. Result',
            table: [
              { aop: '244', name: 'Operating profit', desc: 'Revenues > Expenses' },
              { aop: '245', name: 'Operating loss', desc: 'Expenses > Revenues' },
              { aop: '248', name: 'Profit before tax', desc: 'Total profit before tax' },
              { aop: '250', name: 'Corporate income tax', desc: '10% of the tax base' },
              { aop: '255', name: 'Net profit', desc: 'Final profit after tax deduction' },
              { aop: '256', name: 'Net loss', desc: 'Final loss' },
            ],
          },
        ],
      },
      {
        id: 'bruto-bilans',
        title: 'Trial Balance (Gross Balance)',
        desc: 'The trial balance is a list of all accounts with columns: Debit, Credit, Debit Balance, Credit Balance. It serves as an internal control \u2014 total Debits must equal total Credits.',
        tips: [
          'Debits = Credits (must be balanced)',
          'If not balanced, there is an error in the bookkeeping entries',
          'The trial balance is not officially submitted, but it is the basis for preparing Form 36 and 37',
        ],
      },
      {
        id: 'db-vp',
        title: 'Tax Return (DB-VP)',
        desc: 'The tax return (DB-VP = Tax Balance on Total Revenue) is a form for calculating corporate income tax. It is submitted to the Public Revenue Office (UJP).',
        formula: 'Profit before tax \u00d7 10% = Corporate income tax',
        tips: [
          'Tax base = Profit before tax (AOP 248)',
          'Rate = 10% for all companies (fixed)',
          'If the company has a loss (AOP 249), the tax is 0',
          'Advance tax is paid monthly (1/12 of previous year\u2019s tax)',
          'Filing deadline: March 15 at etax.ujp.gov.mk',
        ],
      },
    ],
    ctaSection: {
      title: 'Facturino automatically fills AOP codes',
      desc: 'No need to manually look up AOP codes. Facturino maps chart of accounts to the correct AOP positions in Form 36, 37, and DB-VP.',
      cta: 'Start free',
    },
    backLink: '\u2190 Back to blog',
    tableHeaders: { aop: 'AOP', name: 'Name', desc: 'Description' },
  },
  sq: {
    title: 'Bilanci dhe pasqyra e t\u00eb ardhurave: Kodet AOP dhe struktura',
    publishDate: '20 janar 2026',
    readTime: '10 min lexim',
    tag: 'Edukim',
    intro:
      'Kodet AOP jan\u00eb kode t\u00eb standardizuara q\u00eb sh\u00ebnojn\u00eb pozicionet n\u00eb pasqyrat financiare maqedonase. \u00c7do kompani duhet t\'i p\u00ebrdor\u00eb gjat\u00eb dor\u00ebzimit t\u00eb llogarive vjetore. Ky udh\u00ebzues shpjegon struktur\u00ebn e Formularit 36 dhe 37 me shembuj praktik\u00eb.',
    sections: [
      {
        id: 'bilans-sostojba',
        title: 'Bilanci (Formulari 36)',
        desc: 'Bilanci tregon pozicionin financiar t\u00eb kompanisë n\u00eb nj\u00eb dat\u00eb t\u00eb caktuar \u2014 zakonisht 31 dhjetor. P\u00ebrb\u00ebhet nga dy pjes\u00eb:',
        subsections: [
          {
            title: 'AKTIVET (Pasurit\u00eb)',
            desc: 'Gjith\u00e7ka q\u00eb kompania zot\u00ebron ose ka t\u00eb drejt\u00eb t\u00eb k\u00ebrkoj\u00eb.',
            table: [
              { aop: '001', name: 'AKTIVET TOTALE', desc: 'Shuma e t\u00eb gjitha aktiveve' },
              { aop: '002', name: 'Aktivet afatgjata', desc: 'Aktivet fikse, aktivet jomateriale, investimet afatgjata' },
              { aop: '003', name: 'Aktivet jomateriale', desc: 'Patenta, licenca, softuer, emri i mirë' },
              { aop: '010', name: 'Aktivet materiale', desc: 'Toka, ndërtesat, pajisjet, automjetet' },
              { aop: '020', name: 'Investimet financiare afatgjata', desc: 'Aksionet, kuotat, kreditë afatgjata' },
              { aop: '030', name: 'Aktivet rrjedhëse', desc: 'Inventar\u00eb, t\u00eb ark\u00ebtueshme, para' },
              { aop: '031', name: 'Inventar\u00eb', desc: 'Mallra, materiale, prodhim n\u00eb proces' },
              { aop: '040', name: 'T\u00eb ark\u00ebtueshme afatshkurtra', desc: 'T\u00eb ark\u00ebtueshme tregtare, parapagimet' },
              { aop: '050', name: 'Mjetet monetare', desc: 'Arka, llogaria bankare, llogaria n\u00eb valut\u00eb' },
            ],
          },
          {
            title: 'DETYRIMET DHE KAPITALI (Pasiva)',
            desc: 'Burimet e financimit \u2014 kapitali i pronarit dhe detyrimet ndaj pal\u00ebve t\u00eb treta.',
            table: [
              { aop: '060', name: 'TOTALI DETYRIME + KAPITAL', desc: 'Shuma e kapitalit + detyrimeve (= AKTIVET)' },
              { aop: '061', name: 'Kapitali dhe rezervat', desc: 'Kapitali themeltar, fitimi i mbajtur, rezervat' },
              { aop: '062', name: 'Kapitali themeltar', desc: 'Kapitali i regjistruar i shoq\u00ebris\u00eb' },
              { aop: '070', name: 'Fitimi i mbajtur', desc: 'Fitimi i akumuluar nga vitet e m\u00ebparshme' },
              { aop: '075', name: 'Detyrimet afatgjata', desc: 'Kredi > 1 vit, obligacione' },
              { aop: '085', name: 'Detyrimet afatshkurtra', desc: 'Furnitor\u00ebt, pagat, tatimet, kredi < 1 vit' },
              { aop: '090', name: 'Detyrimet tregtare', desc: 'Fatura t\u00eb papaguara' },
              { aop: '100', name: 'Detyrimet p\u00ebr tatime dhe kontribute', desc: 'TVSH, tatimi mbi fitimin, kontributet' },
            ],
          },
        ],
        rule: 'RREGULL: AKTIVET (AOP 001) = DETYRIMET + KAPITALI (AOP 060). N\u00ebse nuk p\u00ebrputhen, bilanci nuk \u00ebsht\u00eb i balancuar dhe do t\u00eb refuzohet.',
      },
      {
        id: 'bilans-uspeh',
        title: 'Pasqyra e t\u00eb ardhurave (Formulari 37)',
        desc: 'Pasqyra e t\u00eb ardhurave tregon t\u00eb ardhurat dhe shpenzimet p\u00ebr vitin. Rezultati p\u00ebrfundimtar \u00ebsht\u00eb fitim ose humbje.',
        subsections: [
          {
            title: 'I. T\u00eb ardhurat operative',
            table: [
              { aop: '201', name: 'T\u00eb ardhurat nga shitja', desc: 'Shitjet e faturuara t\u00eb mallrave dhe sh\u00ebrbimeve' },
              { aop: '210', name: 'T\u00eb ardhura t\u00eb tjera', desc: 'Subvencione, qira, fshirje t\u00eb rikuperuara' },
              { aop: '220', name: 'T\u00eb ardhurat financiare', desc: 'Interesa, fitime nga kursi i k\u00ebmbimit (+)' },
              { aop: '246', name: 'T\u00cb ARDHURAT TOTALE', desc: 'Shuma e t\u00eb gjitha t\u00eb ardhurave' },
            ],
          },
          {
            title: 'II. Shpenzimet operative',
            table: [
              { aop: '251', name: 'Kostoja e mallrave t\u00eb shitura', desc: 'Kostoja e mallrave t\u00eb shitura' },
              { aop: '260', name: 'Shpenzimet p\u00ebr punonj\u00ebsit', desc: 'Paga bruto, kontribute, p\u00ebrfitime' },
              { aop: '270', name: 'Amortizimi', desc: 'Konsumimi i aktiveve fikse' },
              { aop: '275', name: 'Shpenzime t\u00eb tjera', desc: 'Qira, telefon, sh\u00ebrbime kontabilit\u00ebti' },
              { aop: '280', name: 'Shpenzimet financiare', desc: 'Interesa, humbje nga kursi i k\u00ebmbimit (-)' },
              { aop: '293', name: 'SHPENZIMET TOTALE', desc: 'Shuma e t\u00eb gjitha shpenzimeve' },
            ],
          },
          {
            title: 'III. Rezultati',
            table: [
              { aop: '244', name: 'Fitimi operativ', desc: 'T\u00eb ardhurat > Shpenzimet' },
              { aop: '245', name: 'Humbja operative', desc: 'Shpenzimet > T\u00eb ardhurat' },
              { aop: '248', name: 'Fitimi para tatimit', desc: 'Fitimi total para tatimit' },
              { aop: '250', name: 'Tatimi mbi fitimin', desc: '10% e baz\u00ebs tatimore' },
              { aop: '255', name: 'Fitimi neto', desc: 'Fitimi p\u00ebrfundimtar pas zbritjes s\u00eb tatimit' },
              { aop: '256', name: 'Humbja neto', desc: 'Humbja p\u00ebrfundimtare' },
            ],
          },
        ],
      },
      {
        id: 'bruto-bilans',
        title: 'Bilanci provues (Bilanci bruto)',
        desc: 'Bilanci provues \u00ebsht\u00eb nj\u00eb list\u00eb e t\u00eb gjitha llogarive me kolona: Debi, Kredi, Saldo Debi, Saldo Kredi. Sh\u00ebrben si kontroll i brendsh\u00ebm \u2014 totali Debi duhet t\u00eb jet\u00eb i barabart\u00eb me totalin Kredi.',
        tips: [
          'Debi = Kredi (duhet t\u00eb jet\u00eb e balancuar)',
          'N\u00ebse nuk \u00ebsht\u00eb e balancuar, ka gabim n\u00eb regjistrime',
          'Bilanci provues nuk dor\u00ebzohet zyrtarisht, por \u00ebsht\u00eb baza p\u00ebr p\u00ebrgatitjen e Formularit 36 dhe 37',
        ],
      },
      {
        id: 'db-vp',
        title: 'Deklarata tatimore (DB-VP)',
        desc: 'Deklarata tatimore (DB-VP = Bilanci tatimor mbi t\u00eb ardhurat totale) \u00ebsht\u00eb nj\u00eb formular p\u00ebr llogaritjen e tatimit mbi fitimin. Dor\u00ebzohet n\u00eb Drejtorin\u00eb e t\u00eb Ardhurave Publike (UJP).',
        formula: 'Fitimi para tatimit \u00d7 10% = Tatimi mbi fitimin',
        tips: [
          'Baza tatimore = Fitimi para tatimit (AOP 248)',
          'Shkalla = 10% p\u00ebr t\u00eb gjitha kompanitë (fikse)',
          'N\u00ebse kompania ka humbje (AOP 249), tatimi \u00ebsht\u00eb 0',
          'Tatimi akontativ paguhet \u00e7do muaj (1/12 e tatimit t\u00eb vitit t\u00eb kaluar)',
          'Afati i dor\u00ebzimit: deri m\u00eb 15 mars n\u00eb etax.ujp.gov.mk',
        ],
      },
    ],
    ctaSection: {
      title: 'Facturino i plot\u00ebson automatikisht kodet AOP',
      desc: 'Pa k\u00ebrkim manual t\u00eb kodeve AOP. Facturino i harton llogaritë nga plani kontab\u00ebl n\u00eb pozicionet e sakta AOP n\u00eb Formularin 36, 37 dhe DB-VP.',
      cta: 'Fillo falas',
    },
    backLink: '\u2190 Kthehu te blogu',
    tableHeaders: { aop: 'AOP', name: 'Em\u00ebrtimi', desc: 'P\u00ebrshkrimi' },
  },
  tr: {
    title: 'Bilan\u00e7o ve gelir tablosu: AOP kodlar\u0131 ve yap\u0131',
    publishDate: '20 Ocak 2026',
    readTime: '10 dk okuma',
    tag: 'E\u011fitim',
    intro:
      'AOP kodlar\u0131, Makedonya mali tablolar\u0131ndaki pozisyonlar\u0131 belirleyen standart kodlard\u0131r. Her \u015firket y\u0131ll\u0131k hesaplar\u0131 sunarken bunlar\u0131 kullanmal\u0131d\u0131r. Bu k\u0131lavuz, Form 36 ve 37\'nin yap\u0131s\u0131n\u0131 pratik \u00f6rneklerle a\u00e7\u0131klamaktad\u0131r.',
    sections: [
      {
        id: 'bilans-sostojba',
        title: 'Bilan\u00e7o (Form 36)',
        desc: 'Bilan\u00e7o, \u015firketin belirli bir tarihteki mali durumunu g\u00f6sterir \u2014 genellikle 31 Aral\u0131k. \u0130ki b\u00f6l\u00fcmden olu\u015fur:',
        subsections: [
          {
            title: 'VARLIKLAR (Aktiva)',
            desc: '\u015eirketin sahip oldu\u011fu veya talep etme hakk\u0131na sahip oldu\u011fu her \u015fey.',
            table: [
              { aop: '001', name: 'TOPLAM VARLIKLAR', desc: 'T\u00fcm varl\u0131klar\u0131n toplam\u0131' },
              { aop: '002', name: 'Duran varl\u0131klar', desc: 'Sabit varl\u0131klar, maddi olmayan varl\u0131klar, uzun vadeli yat\u0131r\u0131mlar' },
              { aop: '003', name: 'Maddi olmayan varl\u0131klar', desc: 'Patentler, lisanslar, yaz\u0131l\u0131m, \u015ferefiye' },
              { aop: '010', name: 'Maddi varl\u0131klar', desc: 'Arazi, binalar, ekipman, ara\u00e7lar' },
              { aop: '020', name: 'Uzun vadeli finansal yat\u0131r\u0131mlar', desc: 'Hisseler, paylar, uzun vadeli krediler' },
              { aop: '030', name: 'D\u00f6nen varl\u0131klar', desc: 'Stoklar, alacaklar, nakit' },
              { aop: '031', name: 'Stoklar', desc: 'Mallar, malzemeler, \u00fcretim s\u00fcreci' },
              { aop: '040', name: 'K\u0131sa vadeli alacaklar', desc: 'Ticari alacaklar, avanslar' },
              { aop: '050', name: 'Nakit ve nakit benzerleri', desc: 'Kasa, banka hesab\u0131, d\u00f6viz hesab\u0131' },
            ],
          },
          {
            title: 'Y\u00dcK\u00dcML\u00dcL\u00dcKLER VE \u00d6ZSERMAYE (Pasiva)',
            desc: 'Finansman kaynaklar\u0131 \u2014 \u015firket sahibinin \u00f6zsermayesi ve \u00fc\u00e7\u00fcnc\u00fc taraflara olan y\u00fck\u00fcml\u00fcl\u00fckler.',
            table: [
              { aop: '060', name: 'TOPLAM Y\u00dcK\u00dcML\u00dcL\u00dcKLER + \u00d6ZSERMAYE', desc: '\u00d6zsermaye + y\u00fck\u00fcml\u00fcl\u00fckler toplam\u0131 (= VARLIKLAR)' },
              { aop: '061', name: '\u00d6zsermaye ve yedekler', desc: 'Sermaye, da\u011f\u0131t\u0131lmam\u0131\u015f k\u00e2rlar, yedekler' },
              { aop: '062', name: 'Esas sermaye', desc: '\u015eirketin tescilli sermayesi' },
              { aop: '070', name: 'Da\u011f\u0131t\u0131lmam\u0131\u015f k\u00e2rlar', desc: '\u00d6nceki y\u0131llardan birikmi\u015f k\u00e2r' },
              { aop: '075', name: 'Uzun vadeli y\u00fck\u00fcml\u00fcl\u00fckler', desc: 'Krediler > 1 y\u0131l, tahviller' },
              { aop: '085', name: 'K\u0131sa vadeli y\u00fck\u00fcml\u00fcl\u00fckler', desc: 'Tedarik\u00e7iler, maa\u015flar, vergiler, krediler < 1 y\u0131l' },
              { aop: '090', name: 'Ticari bor\u00e7lar', desc: '\u00d6denmemi\u015f faturalar' },
              { aop: '100', name: 'Vergi ve katk\u0131 y\u00fck\u00fcml\u00fcl\u00fckleri', desc: 'KDV, kurumlar vergisi, katk\u0131lar' },
            ],
          },
        ],
        rule: 'KURAL: VARLIKLAR (AOP 001) = Y\u00dcK\u00dcML\u00dcL\u00dcKLER + \u00d6ZSERMAYE (AOP 060). E\u015fle\u015fmezlerse bilan\u00e7o dengesizdir ve reddedilecektir.',
      },
      {
        id: 'bilans-uspeh',
        title: 'Gelir Tablosu (Form 37)',
        desc: 'Gelir tablosu, y\u0131l i\u00e7indeki gelirleri ve giderleri g\u00f6sterir. Nihai sonu\u00e7 k\u00e2r veya zarard\u0131r.',
        subsections: [
          {
            title: 'I. Faaliyet gelirleri',
            table: [
              { aop: '201', name: 'Sat\u0131\u015f gelirleri', desc: 'Faturalanan mal ve hizmet sat\u0131\u015flar\u0131' },
              { aop: '210', name: 'Di\u011fer gelirler', desc: 'S\u00fcbvansiyonlar, kira geliri, tahsil edilen silmeler' },
              { aop: '220', name: 'Finansal gelirler', desc: 'Faiz, kur farklar\u0131 (+)' },
              { aop: '246', name: 'TOPLAM GEL\u0130RLER', desc: 'T\u00fcm gelirlerin toplam\u0131' },
            ],
          },
          {
            title: 'II. Faaliyet giderleri',
            table: [
              { aop: '251', name: 'Sat\u0131lan mallar\u0131n maliyeti', desc: 'Sat\u0131lan mal\u0131n maliyeti' },
              { aop: '260', name: '\u00c7al\u0131\u015fan giderleri', desc: 'Br\u00fct maa\u015flar, katk\u0131lar, yan haklar' },
              { aop: '270', name: 'Amortisman', desc: 'Sabit varl\u0131klar\u0131n y\u0131pranmas\u0131' },
              { aop: '275', name: 'Di\u011fer giderler', desc: 'Kira, telefon, muhasebe hizmetleri' },
              { aop: '280', name: 'Finansal giderler', desc: 'Faiz, kur farklar\u0131 (-)' },
              { aop: '293', name: 'TOPLAM G\u0130DERLER', desc: 'T\u00fcm giderlerin toplam\u0131' },
            ],
          },
          {
            title: 'III. Sonu\u00e7',
            table: [
              { aop: '244', name: 'Faaliyet k\u00e2r\u0131', desc: 'Gelirler > Giderler' },
              { aop: '245', name: 'Faaliyet zarar\u0131', desc: 'Giderler > Gelirler' },
              { aop: '248', name: 'Vergi \u00f6ncesi k\u00e2r', desc: 'Vergi \u00f6ncesi toplam k\u00e2r' },
              { aop: '250', name: 'Kurumlar vergisi', desc: 'Matrah\u0131n %10\'u' },
              { aop: '255', name: 'Net k\u00e2r', desc: 'Vergi sonras\u0131 nihai k\u00e2r' },
              { aop: '256', name: 'Net zarar', desc: 'Nihai zarar' },
            ],
          },
        ],
      },
      {
        id: 'bruto-bilans',
        title: 'Mizanpa\u00e7a (Br\u00fct bilan\u00e7o)',
        desc: 'Mizanpa\u00e7a, t\u00fcm hesaplar\u0131n listesidir: Bor\u00e7, Alacak, Bor\u00e7 Bakiyesi, Alacak Bakiyesi s\u00fctunlar\u0131yla. \u0130\u00e7 kontrol amac\u0131yla kullan\u0131l\u0131r \u2014 toplam Bor\u00e7, toplam Alacak\'a e\u015fit olmal\u0131d\u0131r.',
        tips: [
          'Bor\u00e7 = Alacak (dengeli olmal\u0131d\u0131r)',
          'Dengeli de\u011filse, kay\u0131tlarda hata vard\u0131r',
          'Mizanpa\u00e7a resmi olarak sunulmaz, ancak Form 36 ve 37\'nin haz\u0131rlanmas\u0131n\u0131n temelidir',
        ],
      },
      {
        id: 'db-vp',
        title: 'Vergi beyannamesi (DB-VP)',
        desc: 'Vergi beyannamesi (DB-VP = Toplam Gelir Vergi Beyannamesi) kurumlar vergisinin hesaplanmas\u0131 i\u00e7in bir formd\u0131r. Kamu Gelir \u0130daresi\'ne (UJP) sunulur.',
        formula: 'Vergi \u00f6ncesi k\u00e2r \u00d7 %10 = Kurumlar vergisi',
        tips: [
          'Matrah = Vergi \u00f6ncesi k\u00e2r (AOP 248)',
          'Oran = T\u00fcm \u015firketler i\u00e7in %10 (sabit)',
          '\u015eirketin zarar\u0131 varsa (AOP 249), vergi 0\'d\u0131r',
          'Avans vergi ayl\u0131k \u00f6denir (ge\u00e7en y\u0131l\u0131n vergisinin 1/12\'si)',
          'Ba\u015fvuru tarihi: 15 Mart\'a kadar etax.ujp.gov.mk adresinde',
        ],
      },
    ],
    ctaSection: {
      title: 'Facturino AOP kodlar\u0131n\u0131 otomatik doldurur',
      desc: 'AOP kodlar\u0131n\u0131 manuel aramaya gerek yok. Facturino hesap plan\u0131ndaki hesaplar\u0131 Form 36, 37 ve DB-VP\'deki do\u011fru AOP pozisyonlar\u0131na e\u015fler.',
      cta: '\u00dccretsiz ba\u015fla',
    },
    backLink: '\u2190 Bloga d\u00f6n',
    tableHeaders: { aop: 'AOP', name: 'Ad\u0131', desc: 'A\u00e7\u0131klama' },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function BilansNaSostojbaPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ============================================================ */}
      {/*  BACK LINK                                                   */}
      {/* ============================================================ */}
      <section className="pt-24 md:pt-32 pb-0">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <Link
            href={`/${locale}/blog`}
            className="text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
          >
            {t.backLink}
          </Link>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
      <section className="pt-6 pb-8 md:pb-12">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <div className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600 mb-4">
            {t.tag}
          </div>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-4">
            {t.title}
          </h1>
          <div className="flex items-center gap-4 text-gray-500 text-sm">
            <span>{t.publishDate}</span>
            <span className="w-1 h-1 rounded-full bg-gray-300" />
            <span>{t.readTime}</span>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  INTRO                                                       */}
      {/* ============================================================ */}
      <section className="pb-10">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.intro}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  SECTIONS                                                    */}
      {/* ============================================================ */}
      {t.sections.map((section) => (
        <section key={section.id} id={section.id} className="pb-12 md:pb-16">
          <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
            <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
              {section.title}
            </h2>
            <p className="text-gray-600 leading-relaxed mb-6">
              {section.desc}
            </p>

            {/* Subsections with AOP tables */}
            {section.subsections?.map((sub, si) => (
              <div key={si} className="mb-8">
                <h3 className="text-xl font-semibold text-gray-800 mb-2">
                  {sub.title}
                </h3>
                {sub.desc && (
                  <p className="text-gray-500 mb-4">{sub.desc}</p>
                )}
                <div className="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                  <table className="w-full text-sm text-left">
                    <thead className="sticky top-0 z-10">
                      <tr className="bg-indigo-600 text-white">
                        <th className="px-4 py-3 font-semibold whitespace-nowrap rounded-tl-xl">
                          {t.tableHeaders.aop}
                        </th>
                        <th className="px-4 py-3 font-semibold">
                          {t.tableHeaders.name}
                        </th>
                        <th className="px-4 py-3 font-semibold rounded-tr-xl">
                          {t.tableHeaders.desc}
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      {sub.table.map((row, ri) => (
                        <tr
                          key={ri}
                          className={`border-b border-gray-100 ${
                            ri % 2 === 0 ? 'bg-white' : 'bg-gray-50/60'
                          } hover:bg-indigo-50/40 transition-colors`}
                        >
                          <td className="px-4 py-3 font-mono font-bold text-indigo-600 whitespace-nowrap">
                            {row.aop}
                          </td>
                          <td className="px-4 py-3 font-medium text-gray-900">
                            {row.name}
                          </td>
                          <td className="px-4 py-3 text-gray-500">
                            {row.desc}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              </div>
            ))}

            {/* Rule / warning box */}
            {section.rule && (
              <div className="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-6 py-4">
                <div className="flex items-start gap-3">
                  <svg
                    className="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"
                    />
                  </svg>
                  <p className="text-amber-800 font-medium leading-relaxed">
                    {section.rule}
                  </p>
                </div>
              </div>
            )}

            {/* Formula highlight box */}
            {section.formula && (
              <div className="mt-6 rounded-xl border border-indigo-200 bg-indigo-50 px-6 py-5 text-center">
                <p className="text-xl md:text-2xl font-bold text-indigo-700 font-mono">
                  {section.formula}
                </p>
              </div>
            )}

            {/* Tips / bullet list */}
            {section.tips && (
              <ul className="mt-6 space-y-3">
                {section.tips.map((tip, ti) => (
                  <li key={ti} className="flex items-start gap-3">
                    <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                      <svg
                        className="w-3 h-3 text-green-600"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={3}
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M5 13l4 4L19 7"
                        />
                      </svg>
                    </span>
                    <span className="text-gray-700 leading-relaxed">
                      {tip}
                    </span>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </section>
      ))}

      {/* ============================================================ */}
      {/*  CTA SECTION                                                 */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.ctaSection.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.ctaSection.desc}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.ctaSection.cta}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
