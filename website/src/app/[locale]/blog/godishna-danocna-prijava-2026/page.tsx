import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/godishna-danocna-prijava-2026', {
    title: {
      mk: 'Завршна сметка 2026: Рокови, документи и чекор-по-чекор водич',
      en: 'Annual Tax Return North Macedonia 2026: Deadlines & Step-by-Step Guide',
      sq: 'Llogaria vjetore 2026: Afatet, dokumentet dhe udhëzuesi hap pas hapi',
      tr: 'Kuzey Makedonya Yıllık Vergi Beyannamesi 2026: Tarihler ve Rehber',
    },
    description: {
      mk: 'Целосен водич за завршна сметка 2026: рок 15 март за ДБ-ВП, потребни документи, биланс на состојба, биланс на успех, чекор-по-чекор постапка за е-Такс и казни за задоцнување.',
      en: 'Complete guide to annual tax returns in North Macedonia 2026: March 15 DB-VP deadline, required documents, balance sheet, income statement, step-by-step e-Tax filing and late penalties.',
      sq: 'Udhëzues i plotë për llogarinë vjetore 2026: afati 15 mars për DB-VP, dokumentet e nevojshme, bilanci i gjendjes, bilanci i suksesit, procedura hap pas hapi për e-Tax dhe gjobat për vonesë.',
      tr: 'Kuzey Makedonya yıllık vergi beyannamesi 2026 rehberi: 15 Mart DB-VP son tarihi, gerekli belgeler, bilanço, gelir tablosu, adım adım e-Tax başvurusu ve gecikme cezaları.',
    },
    datePublished: '2026-05-22',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Завршна сметка 2026: Рокови, документи и чекор-по-чекор водич',
    publishDate: '22 мај 2026',
    readTime: '8 мин читање',
    intro: 'Секоја фирма во Северна Македонија — без оглед дали имала приходи или не — е должна да поднесе завршна сметка (годишна даночна пријава ДБ-ВП) до Управата за јавни приходи. Пропуштањето на рокот повлекува казни од 500 до 5.000 EUR. Овој водич ги покрива сите рокови, потребни документи и ви дава чекор-по-чекор постапка за безгрижно поднесување.',
    sections: [
      {
        title: 'Што е завршна сметка?',
        content: 'Завршната сметка (годишна даночна пријава) е збир на финансиски извештаи и даночна пријава (образец ДБ-ВП) кои секое правно лице мора да ги поднесе на крајот на фискалната година. Таа ги вклучува билансот на состојба, билансот на успех, извештајот за парични текови и белешки кон финансиските извештаи. Целта е да се утврди финансиската состојба на фирмата, да се пресмета данокот на добивка и да се обезбеди транспарентност пред државните органи. Дури и фирмите со нулти приходи мораат да поднесат завршна сметка — во спротивно ризикуваат казна и бришење од Централниот регистар.',
        items: null,
        steps: null,
      },
      {
        title: 'Рокови за 2026',
        content: 'Клучните рокови за поднесување на завршната сметка за фискалната 2025 година се:',
        items: null,
        steps: [
          { step: 'Данок на добивка (ДБ-ВП)', desc: 'Рок: 15 март 2026 за фискалната 2025 година. Ова е главниот рок за сите правни лица — се поднесува до УЈП.' },
          { step: 'Финансиски извештаи до ЦРРСМ', desc: '28 февруари за мали трговци и микро-претпријатија, 15 март за средни и големи претпријатија. Вклучува биланс на состојба, биланс на успех и белешки.' },
          { step: 'Годишно усогласување на ДДВ', desc: 'Рок: 25 март 2026. За сите обврзници за ДДВ, поднесување на годишна ДДВ пријава до УЈП.' },
          { step: 'Персонален данок на доход (годишна)', desc: 'Рок: 15 март 2026. Годишна даночна пријава за физички лица и самостојни вршители на дејност (ПДД-ГДП).' },
        ],
      },
      {
        title: 'Потребни документи',
        content: 'За успешно поднесување на завршната сметка, потребно е да ги подготвите следните документи:',
        items: [
          'Биланс на состојба (образец 36) — приказ на средствата, обврските и капиталот на 31.12.2025.',
          'Биланс на успех (образец 37) — приказ на приходите, расходите и финансискиот резултат за 2025.',
          'Извештај за парични текови — движење на готовината во текот на годината.',
          'Белешки кон финансиските извештаи — објаснувања на значајни ставки и сметководствени политики.',
          'Образец ДБ-ВП — годишна даночна пријава за данок на добивка.',
          'Попис на залихи — инвентурна листа на сите залихи на 31.12.2025.',
          'Аналитика на амортизација — регистар на основни средства со пресметана амортизација.',
        ],
        steps: null,
      },
      {
        title: 'Чекор-по-чекор постапка',
        content: 'Следете ги овие чекори за правилно затворање на годината и поднесување на завршната сметка:',
        items: null,
        steps: [
          { step: 'Затворете ги сите сметки и направете пробен биланс', desc: 'Проверете дали сите фактури, плаќања и банкарски трансакции се книжени. Генерирајте пробен биланс за да проверите дали дебитните и кредитните салда се усогласени.' },
          { step: 'Пресметајте амортизација на основни средства', desc: 'Пресметајте ја амортизацијата на основните средства за 2025 според прописите и додадете ги книжењата.' },
          { step: 'Усогласете ги банкарските извештаи со книгите', desc: 'Споредете ги салдата на банкарските сметки со изводите од банката. Секоја разлика мора да се разјасни и книжи.' },
          { step: 'Подгответе ги финансиските извештаи (образец 36 и 37)', desc: 'Изгенерирајте биланс на состојба, биланс на успех и извештај за парични текови. Проверете ги сите ставки.' },
          { step: 'Пресметајте данок на добивка (стапка 10%)', desc: 'Стапката на данок на добивка е 10% на даночната основица. Пополнете го образецот ДБ-ВП со сите корекции (непризнати расходи, даночни олеснувања).' },
          { step: 'Поднесете преку е-Даноци (etax.ujp.gov.mk)', desc: 'Најавете се на порталот е-Даноци на УЈП и поднесете ја пријавата ДБ-ВП електронски. Зачувајте го потврдниот број.' },
          { step: 'Поднесете финансиски извештаи до Централен регистар', desc: 'Поднесете ги финансиските извештаи до ЦРРСМ преку нивниот електронски систем. Рокот зависи од големината на фирмата.' },
          { step: 'Архивирајте ги сите документи за 10 години', desc: 'Според Законот за сметководство, сите финансиски документи мора да се чуваат најмалку 10 години.' },
        ],
      },
      {
        title: 'Казни за задоцнување',
        content: 'Управата за јавни приходи применува строги казни за ненавремено поднесување или неплаќање:',
        items: [
          'Казна од 500 до 5.000 EUR (во денарска противвредност) за правно лице кое нема да поднесе завршна сметка.',
          'Казна од 100 до 500 EUR за одговорното лице (управител или овластен сметководител).',
          'Камата од 0,03% дневно (околу 11% годишно) врз неплатениот износ на данокот.',
          'Можност за бришење од Централниот регистар по 2 години неподнесување.',
          'Кривична одговорност за даночна евазија при намерно затајување на приходи.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага',
        content: 'Facturino го автоматизира целиот процес на подготовка на завршна сметка, со што ви заштедува време и ги елиминира грешките:',
        items: [
          'Автоматски пробен биланс — генерирајте бруто биланс со еден клик, со сите книжења веќе усогласени.',
          'Генерирање на финансиски извештаи — биланс на состојба и биланс на успех се генерираат автоматски од вашите податоци.',
          'Потсетници за рокови — добивате известувања 7 дена и 2 дена пред секој клучен рок.',
          'Архивирање на документи — целосна ревизорска трага и историја за безгрижна ревизија.',
          'IFRS усогласеност — автоматско книжење според Меѓународните стандарди за финансиско известување.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    relatedArticles: [
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
      { slug: 'bilans-na-sostojba', title: 'Биланс на состојба: Целосен водич за 2026' },
    ],
    bottomCta: {
      title: 'Подгответе ја завршната сметка без стрес',
      subtitle: 'Автоматски биланси, аларми за рокови и e-Tax извоз.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Annual Tax Return North Macedonia 2026: Deadlines & Step-by-Step Guide',
    publishDate: 'May 22, 2026',
    readTime: '8 min read',
    intro: 'Every company registered in North Macedonia — even those with zero revenue — must file an annual tax return (DB-VP form) with the Public Revenue Office (UJP). Missing the deadline carries fines from EUR 500 to 5,000. This guide covers all deadlines, required documents, and gives you a step-by-step process for stress-free filing.',
    sections: [
      {
        title: 'What is the annual tax return?',
        content: 'The annual tax return (завршна сметка) is a set of financial statements and a tax declaration (DB-VP form) that every legal entity must submit at the end of the fiscal year. It includes the balance sheet, income statement, cash flow statement, and notes to the financial statements. The purpose is to determine the company\'s financial position, calculate corporate income tax, and ensure transparency before state authorities. Even companies with zero income must file — otherwise they risk fines and deletion from the Central Registry.',
        items: null,
        steps: null,
      },
      {
        title: 'Deadlines for 2026',
        content: 'Key deadlines for filing the annual return for fiscal year 2025:',
        items: null,
        steps: [
          { step: 'Corporate income tax (DB-VP)', desc: 'Deadline: March 15, 2026 for fiscal year 2025. This is the main deadline for all legal entities — filed with UJP.' },
          { step: 'Financial statements to Central Registry (CRRSM)', desc: 'February 28 for small traders and micro-enterprises, March 15 for medium and large enterprises. Includes balance sheet, income statement, and notes.' },
          { step: 'Annual VAT reconciliation', desc: 'Deadline: March 25, 2026. For all VAT-registered taxpayers, filing of the annual VAT return with UJP.' },
          { step: 'Personal income tax (annual)', desc: 'Deadline: March 15, 2026. Annual tax return for individuals and sole proprietors (PDD-GDP).' },
        ],
      },
      {
        title: 'Required documents',
        content: 'To successfully file the annual return, you need to prepare the following documents:',
        items: [
          'Balance sheet (Form 36) — overview of assets, liabilities, and equity as of December 31, 2025.',
          'Income statement / P&L (Form 37) — overview of revenues, expenses, and financial result for 2025.',
          'Cash flow statement — movement of cash throughout the year.',
          'Notes to the financial statements — explanations of significant items and accounting policies.',
          'DB-VP form — annual corporate income tax return.',
          'Inventory list — a complete inventory count of all stock as of December 31, 2025.',
          'Depreciation register — fixed asset register with calculated depreciation.',
        ],
        steps: null,
      },
      {
        title: 'Step-by-step process',
        content: 'Follow these steps for a proper year-end closing and annual return filing:',
        items: null,
        steps: [
          { step: 'Close all accounts and run trial balance', desc: 'Verify that all invoices, payments, and bank transactions are posted. Generate a trial balance to verify debit and credit balances match.' },
          { step: 'Calculate depreciation for fixed assets', desc: 'Calculate depreciation on fixed assets for 2025 according to regulations and post the journal entries.' },
          { step: 'Reconcile bank statements with books', desc: 'Compare bank account balances with bank statements. Any discrepancy must be investigated and posted.' },
          { step: 'Prepare financial statements (Forms 36 & 37)', desc: 'Generate the balance sheet, income statement, and cash flow statement. Review every line item.' },
          { step: 'Calculate corporate income tax (10% rate)', desc: 'The corporate income tax rate is 10% on the tax base. Fill in the DB-VP form with all adjustments (non-deductible expenses, tax reliefs).' },
          { step: 'Submit via e-Tax (etax.ujp.gov.mk)', desc: 'Log in to the UJP e-Tax portal and submit the DB-VP return electronically. Save the confirmation number.' },
          { step: 'Submit financial statements to Central Registry', desc: 'File financial statements with CRRSM through their electronic system. The deadline depends on company size.' },
          { step: 'Archive all documents for 10 years', desc: 'Under the Accounting Law, all financial documents must be retained for at least 10 years.' },
        ],
      },
      {
        title: 'Late filing penalties',
        content: 'The Public Revenue Office applies strict penalties for late filing or non-payment:',
        items: [
          'EUR 500 to 5,000 (in MKD equivalent) fine for legal entities that fail to file the annual return.',
          'EUR 100 to 500 fine for the responsible person (managing director or authorised accountant).',
          'Interest of 0.03% per day (approximately 11% annually) on the unpaid tax amount.',
          'Possible deletion from the Central Registry after 2 years of non-filing.',
          'Potential criminal liability for tax evasion in cases of deliberate income concealment.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps',
        content: 'Facturino automates the entire annual return preparation process, saving you time and eliminating errors:',
        items: [
          'Automated trial balance — generate a trial balance with one click, with all entries already reconciled.',
          'Financial statement generation — balance sheet and income statement are generated automatically from your data.',
          'Deadline reminders — receive notifications 7 days and 2 days before each key deadline.',
          'Document archiving — complete audit trail and history for hassle-free audits.',
          'IFRS compliance — automatic posting according to International Financial Reporting Standards.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
      { slug: 'bilans-na-sostojba', title: 'Balance Sheet: Complete Guide for 2026' },
    ],
    bottomCta: {
      title: 'Prepare Your Annual Return Stress-Free',
      subtitle: 'Automated balance sheets, deadline alerts and e-Tax export.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Llogaria vjetore 2026: Afatet, dokumentet dhe udhëzuesi hap pas hapi',
    publishDate: '22 maj 2026',
    readTime: '8 min lexim',
    intro: 'Çdo kompani e regjistruar në Maqedoninë e Veriut — edhe ato me zero të ardhura — duhet të dorëzojë llogarinë vjetore (deklaratën tatimore vjetore DB-VP) në Zyrën e të Ardhurave Publike (UJP). Humbja e afatit sjell gjoba nga 500 deri në 5.000 EUR. Ky udhëzues mbulon të gjitha afatet, dokumentet e nevojshme dhe jep procedurë hap pas hapi për dorëzim pa stres.',
    sections: [
      {
        title: 'Çfarë është llogaria vjetore?',
        content: 'Llogaria vjetore (завршна сметка) është një grup pasqyrash financiare dhe deklaratë tatimore (formulari DB-VP) që çdo subjekt juridik duhet ta dorëzojë në fund të vitit fiskal. Ajo përfshin bilancin e gjendjes, bilancin e suksesit, pasqyrën e rrjedhës së parasë dhe shënimet e pasqyrave financiare. Qëllimi është të përcaktohet gjendja financiare e kompanisë, të llogaritet tatimi mbi fitimin dhe të sigurohet transparencë para organeve shtetërore. Edhe kompanitë me zero të ardhura duhet ta dorëzojnë — përndryshe rrezikojnë gjobë dhe fshirje nga Regjistri Qendror.',
        items: null,
        steps: null,
      },
      {
        title: 'Afatet për 2026',
        content: 'Afatet kyçe për dorëzimin e llogarisë vjetore për vitin fiskal 2025:',
        items: null,
        steps: [
          { step: 'Tatimi mbi fitimin (DB-VP)', desc: 'Afati: 15 mars 2026 për vitin fiskal 2025. Ky është afati kryesor për të gjitha subjektet juridike — dorëzohet në UJP.' },
          { step: 'Pasqyrat financiare në Regjistrin Qendror (QRMK)', desc: '28 shkurt për tregtarë të vegjël dhe mikro-ndërmarrje, 15 mars për ndërmarrje të mesme dhe të mëdha. Përfshin bilancin e gjendjes, bilancin e suksesit dhe shënime.' },
          { step: 'Harmonizimi vjetor i TVSH-së', desc: 'Afati: 25 mars 2026. Për të gjithë tatimpaguesit e regjistruar për TVSH, dorëzimi i deklaratës vjetore të TVSH-së në UJP.' },
          { step: 'Tatimi mbi të ardhurat personale (vjetor)', desc: 'Afati: 15 mars 2026. Deklarata vjetore tatimore për persona fizikë dhe tregtarë individualë (PDD-GDP).' },
        ],
      },
      {
        title: 'Dokumentet e nevojshme',
        content: 'Për dorëzim të suksesshëm të llogarisë vjetore, duhet të përgatisni dokumentet e mëposhtme:',
        items: [
          'Bilanci i gjendjes (Formulari 36) — paraqitja e mjeteve, detyrimeve dhe kapitalit më 31.12.2025.',
          'Bilanci i suksesit (Formulari 37) — paraqitja e të ardhurave, shpenzimeve dhe rezultatit financiar për 2025.',
          'Pasqyra e rrjedhës së parasë — lëvizja e parasë së gatshme gjatë vitit.',
          'Shënimet e pasqyrave financiare — sqarime të zërave të rëndësishëm dhe politikave kontabël.',
          'Formulari DB-VP — deklarata vjetore e tatimit mbi fitimin.',
          'Lista e inventarit — numërim i plotë i të gjitha stoqeve më 31.12.2025.',
          'Regjistri i zhvlerësimit — regjistri i mjeteve fikse me zhvlerësimin e llogaritur.',
        ],
        steps: null,
      },
      {
        title: 'Procedura hap pas hapi',
        content: 'Ndiqni këto hapa për mbylljen e duhur të vitit dhe dorëzimin e llogarisë vjetore:',
        items: null,
        steps: [
          { step: 'Mbyllni të gjitha llogaritë dhe gjeneroni bilancin provues', desc: 'Verifikoni që të gjitha faturat, pagesat dhe transaksionet bankare janë regjistruar. Gjeneroni bilancin provues për të verifikuar saldet debitor dhe kreditor.' },
          { step: 'Llogaritni zhvlerësimin e mjeteve fikse', desc: 'Llogaritni zhvlerësimin e mjeteve fikse për 2025 sipas rregulloreve dhe regjistroni fletëkontablet.' },
          { step: 'Harmonizoni ekstrakte bankare me regjistrat', desc: 'Krahasoni saldet e llogarive bankare me ekstrakte nga banka. Çdo mospërputhje duhet hulumtuar dhe regjistruar.' },
          { step: 'Përgatitni pasqyrat financiare (Formularët 36 dhe 37)', desc: 'Gjeneroni bilancin e gjendjes, bilancin e suksesit dhe pasqyrën e rrjedhës së parasë. Rishikoni çdo zë.' },
          { step: 'Llogaritni tatimin mbi fitimin (norma 10%)', desc: 'Norma e tatimit mbi fitimin është 10% mbi bazën tatimore. Plotësoni formularin DB-VP me të gjitha rregullimet.' },
          { step: 'Dorëzoni përmes e-Tax (etax.ujp.gov.mk)', desc: 'Identifikohuni në portalin e-Tax të UJP dhe dorëzoni deklaratën DB-VP në mënyrë elektronike. Ruani numrin e konfirmimit.' },
          { step: 'Dorëzoni pasqyrat financiare në Regjistrin Qendror', desc: 'Dorëzoni pasqyrat financiare në QRMK përmes sistemit elektronik. Afati varet nga madhësia e kompanisë.' },
          { step: 'Arkivoni të gjitha dokumentet për 10 vjet', desc: 'Sipas Ligjit të Kontabilitetit, të gjitha dokumentet financiare duhet të ruhen së paku 10 vjet.' },
        ],
      },
      {
        title: 'Gjobat për dorëzim të vonuar',
        content: 'Zyra e të Ardhurave Publike aplikon gjoba të rrepta për dorëzim të vonuar ose mospagim:',
        items: [
          'Gjobë nga 500 deri në 5.000 EUR (në ekuivalent MKD) për subjekte juridike që nuk dorëzojnë llogarinë vjetore.',
          'Gjobë nga 100 deri në 500 EUR për personin përgjegjës (drejtorin ose kontabilistin e autorizuar).',
          'Kamatë prej 0,03% në ditë (rreth 11% në vit) mbi shumën e papaguar të tatimit.',
          'Mundësi fshirjeje nga Regjistri Qendror pas 2 vitesh mosdorëzim.',
          'Përgjegjësi penale për evazion fiskal në raste të fshehjes së qëllimshme të të ardhurave.',
        ],
        steps: null,
      },
      {
        title: 'Si ju ndihmon Facturino',
        content: 'Facturino automatizon të gjithë procesin e përgatitjes së llogarisë vjetore, duke ju kursyer kohë dhe duke eliminuar gabimet:',
        items: [
          'Bilanc provues automatik — gjeneroni bilancin provues me një klik, me të gjitha regjistrimet tashmë të harmonizuara.',
          'Gjenerim i pasqyrave financiare — bilanci i gjendjes dhe bilanci i suksesit gjenerohen automatikisht nga të dhënat tuaja.',
          'Kujtesa për afate — merrni njoftime 7 ditë dhe 2 ditë para çdo afati kyç.',
          'Arkivim i dokumenteve — gjurmë e plotë auditimi dhe histori për auditime pa stres.',
          'Përputhshmëri me SNRF — regjistrim automatik sipas Standardeve Ndërkombëtare të Raportimit Financiar.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet e UJP' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
      { slug: 'bilans-na-sostojba', title: 'Bilanci i gjendjes: Udhëzues i plotë për 2026' },
    ],
    bottomCta: {
      title: 'Përgatitni llogarinë vjetore pa stres',
      subtitle: 'Bilance automatike, alarme afatesh dhe eksport e-Tax.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Kuzey Makedonya Yıllık Vergi Beyannamesi 2026: Tarihler ve Rehber',
    publishDate: '22 Mayıs 2026',
    readTime: '8 dk okuma',
    intro: 'Kuzey Makedonya\'da kayıtlı her şirket — sıfır gelirli olanlar dahil — Kamu Gelir İdaresi\'ne (UJP) yıllık vergi beyannamesi (DB-VP formu) vermek zorundadır. Son tarihi kaçırmak 500 ila 5.000 EUR arasında para cezası getirir. Bu rehber tüm son tarihleri, gerekli belgeleri kapsar ve stressiz başvuru için adım adım bir süreç sunar.',
    sections: [
      {
        title: 'Yıllık vergi beyannamesi nedir?',
        content: 'Yıllık vergi beyannamesi (завршна сметка), her tüzel kişinin mali yılın sonunda sunması gereken mali tablolar ve vergi beyannamesi (DB-VP formu) setidir. Bilanço, gelir tablosu, nakit akış tablosu ve mali tablo dipnotlarını içerir. Amacı şirketin mali durumunu belirlemek, kurumlar vergisini hesaplamak ve devlet kurumları karşısında şeffaflık sağlamaktır. Sıfır gelirli şirketler bile beyanname vermek zorundadır — aksi takdirde para cezası ve Merkez Sicilden silinme riski taşırlar.',
        items: null,
        steps: null,
      },
      {
        title: '2026 son tarihleri',
        content: '2025 mali yılına ait yıllık beyanname için önemli son tarihler:',
        items: null,
        steps: [
          { step: 'Kurumlar vergisi (DB-VP)', desc: 'Son tarih: 15 Mart 2026, 2025 mali yılı için. Tüm tüzel kişiler için ana son tarih budur — UJP\'ye verilir.' },
          { step: 'Merkez Sicile (CRRSM) mali tablolar', desc: '28 Şubat küçük tüccarlar ve mikro işletmeler için, 15 Mart orta ve büyük işletmeler için. Bilanço, gelir tablosu ve dipnotları içerir.' },
          { step: 'Yıllık KDV mutabakatı', desc: 'Son tarih: 25 Mart 2026. KDV\'ye kayıtlı tüm mükellefler için, yıllık KDV beyannamesinin UJP\'ye sunulması.' },
          { step: 'Kişisel gelir vergisi (yıllık)', desc: 'Son tarih: 15 Mart 2026. Bireyler ve serbest meslek sahipleri için yıllık vergi beyannamesi (PDD-GDP).' },
        ],
      },
      {
        title: 'Gerekli belgeler',
        content: 'Yıllık beyannameyi başarıyla vermek için aşağıdaki belgeleri hazırlamanız gerekir:',
        items: [
          'Bilanço (Form 36) — 31.12.2025 itibarıyla şirketin varlıkları, borçları ve öz sermayesinin özeti.',
          'Gelir tablosu (Form 37) — 2025 yılı gelirleri, giderleri ve mali sonucunun özeti.',
          'Nakit akış tablosu — yıl boyunca nakit hareketleri.',
          'Mali tablo dipnotları — önemli kalemlerin ve muhasebe politikalarının açıklamaları.',
          'DB-VP formu — yıllık kurumlar vergisi beyannamesi.',
          'Envanter listesi — 31.12.2025 itibarıyla tüm stokların tam envanter sayımı.',
          'Amortisman kaydı — hesaplanmış amortismanla sabit kıymet kaydı.',
        ],
        steps: null,
      },
      {
        title: 'Adım adım süreç',
        content: 'Uygun yıl sonu kapanışı ve yıllık beyanname sunumu için bu adımları takip edin:',
        items: null,
        steps: [
          { step: 'Tüm hesapları kapatın ve mizan çalıştırın', desc: 'Tüm faturaların, ödemelerin ve banka işlemlerinin kaydedildiğini doğrulayın. Borç ve alacak bakiyelerinin eşleştiğini kontrol etmek için mizan oluşturun.' },
          { step: 'Sabit kıymetler için amortismanı hesaplayın', desc: '2025 yılı için düzenlemelere göre sabit kıymetlerin amortismanını hesaplayın ve yevmiye kayıtlarını yapın.' },
          { step: 'Banka hesap özetlerini defterlerle mutabık kılın', desc: 'Banka hesap bakiyelerini banka ekstreleriyle karşılaştırın. Her tutarsızlık araştırılmalı ve kaydedilmelidir.' },
          { step: 'Mali tabloları hazırlayın (Form 36 ve 37)', desc: 'Bilanço, gelir tablosu ve nakit akış tablosunu oluşturun. Her kalemi gözden geçirin.' },
          { step: 'Kurumlar vergisini hesaplayın (%10 oran)', desc: 'Kurumlar vergisi oranı vergi matrahı üzerinden %10\'dur. Tüm düzeltmelerle DB-VP formunu doldurun.' },
          { step: 'e-Tax (etax.ujp.gov.mk) üzerinden gönderin', desc: 'UJP e-Tax portalına giriş yapın ve DB-VP beyannamesini elektronik olarak gönderin. Onay numarasını kaydedin.' },
          { step: 'Mali tabloları Merkez Sicile gönderin', desc: 'Mali tabloları CRRSM\'ye elektronik sistemleri üzerinden sunun. Son tarih şirket büyüklüğüne göre değişir.' },
          { step: 'Tüm belgeleri 10 yıl arşivleyin', desc: 'Muhasebe Kanunu uyarınca, tüm mali belgeler en az 10 yıl saklanmalıdır.' },
        ],
      },
      {
        title: 'Gecikme cezaları',
        content: 'Kamu Gelir İdaresi geç başvuru veya ödeme yapılmaması durumunda katı cezalar uygulamaktadır:',
        items: [
          'Yıllık beyannameyi vermeyen tüzel kişiler için 500 ila 5.000 EUR (MKD karşılığı) para cezası.',
          'Sorumlu kişi (müdür veya yetkili muhasebeci) için 100 ila 500 EUR para cezası.',
          'Ödenmemiş vergi tutarı üzerinden günlük %0,03 (yıllık yaklaşık %11) faiz.',
          '2 yıl başvurulmaması halinde Merkez Sicilden silinme olasılığı.',
          'Gelirin kasıtlı olarak gizlenmesi durumunda vergi kaçakçılığı için cezai sorumluluk.',
        ],
        steps: null,
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content: 'Facturino yıllık beyanname hazırlama sürecinin tamamını otomatikleştirir, size zaman kazandırır ve hataları ortadan kaldırır:',
        items: [
          'Otomatik mizan — tek tıkla mizan oluşturun, tüm kayıtlar zaten mutabık.',
          'Mali tablo oluşturma — bilanço ve gelir tablosu verilerinizden otomatik olarak oluşturulur.',
          'Son tarih hatırlatmaları — her önemli son tarihten 7 gün ve 2 gün önce bildirim alın.',
          'Belge arşivleme — stressiz denetimler için tam denetim izi ve geçmiş.',
          'UFRS uyumluluğu — Uluslararası Finansal Raporlama Standartlarına göre otomatik kayıt.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    relatedArticles: [
      { slug: 'rokovi-ujp-2026', title: 'Vergi takvimi 2026: Tüm UJP son tarihleri' },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, tarihler ve hesaplama' },
      { slug: 'bilans-na-sostojba', title: 'Bilanço: 2026 için eksiksiz rehber' },
    ],
    bottomCta: {
      title: 'Yıllık beyannameyi stressiz hazırlayın',
      subtitle: 'Otomatik bilançolar, vade uyarıları ve e-Tax dışa aktarım.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function GodishnaDanocnaPrijavaPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = locale === 'mk' ? 'Блог' : locale === 'sq' ? 'Blog' : locale === 'tr' ? 'Blog' : 'Blog'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const articleLd = articleJsonLd({ locale, slug: 'godishna-danocna-prijava-2026', title: t.title, description: t.intro, datePublished: '2026-05-22', tags: ['завршна сметка', 'годишна пријава', 'annual tax return', 'macedonia'] })
  const breadcrumbLd = breadcrumbJsonLd([{ name: homeLabel, href: `/${locale}` }, { name: blogLabel, href: `/${locale}/blog` }, { name: t.title, href: `/${locale}/blog/godishna-danocna-prijava-2026` }])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/blog`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">{t.backLink}</Link>
          <article>
            <header className="mb-10">
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full mb-4">{t.tag}</span>
              <h1 className="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 leading-tight">{t.title}</h1>
              <p className="text-sm text-gray-500">{t.publishDate} · {t.readTime}</p>
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
                          <span className="text-blue-500 mt-1.5 text-xs">{'●'}</span>
                          <span className="text-gray-700">{item}</span>
                        </li>
                      ))}
                    </ul>
                  )}
                  {s.steps && (
                    <div className="space-y-4 mb-4">
                      {s.steps.map((step, j) => (
                        <div key={j} className="flex items-start gap-3">
                          <span className="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-sm font-bold">{j + 1}</span>
                          <div><p className="font-semibold text-gray-900">{step.step}</p><p className="text-gray-600 text-sm mt-1">{step.desc}</p></div>
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
                  <Link key={i} href={`/${locale}/blog/${ra.slug}`} className="text-blue-600 hover:text-blue-800 hover:underline">{ra.title}</Link>
                ))}
              </div>
            </aside>
          </article>
          <div className="mt-16 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl p-8 sm:p-12 text-center text-white">
            <h2 className="text-2xl sm:text-3xl font-extrabold mb-3">{t.bottomCta.title}</h2>
            <p className="text-blue-100 mb-6 text-lg">{t.bottomCta.subtitle}</p>
            <a href={t.bottomCta.href} className="inline-block bg-white text-blue-700 font-bold px-8 py-3.5 rounded-xl hover:bg-blue-50 transition-colors text-lg shadow-lg">{t.bottomCta.cta}</a>
          </div>
        </div>
      </div>
    </main>
  )
}
