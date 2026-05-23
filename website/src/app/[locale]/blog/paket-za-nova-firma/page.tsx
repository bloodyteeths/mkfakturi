import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/paket-za-nova-firma', {
    title: {
      mk: 'Нова фирма чеклиста: 15 работи во првите 30 дена — 2026',
      en: 'New Company Checklist: 15 Things to Do in Your First 30 Days',
      sq: 'Lista e kompanisë së re: 15 gjëra për 30 ditët e para',
      tr: 'Yeni Şirket Kontrol Listesi: İlk 30 Günde Yapılacak 15 Şey',
    },
    description: {
      mk: 'Комплетна чеклиста за нова фирма во Македонија: ЦРСМ регистрација, ЕМБС, банкарска сметка, УЈП, МПИН, ДДВ, фискален апарат, платен список. 15 чекори групирани по денови.',
      en: 'Complete checklist for starting a business in North Macedonia: CRMS registration, EMBS, bank account, UJP tax setup, MPIN, VAT, fiscal device, payroll. 15 steps grouped by timeline.',
      sq: 'Lista e plotë për hapjen e biznesit në Maqedoni: regjistrimi RQRM, EMBS, llogari bankare, UJP, MPIN, TVSH, pajisje fiskale, paga. 15 hapa sipas ditëve.',
      tr: 'Kuzey Makedonya\'da iş kurma kontrol listesi: CRMS kaydı, EMBS, banka hesabı, UJP vergi kurulumu, MPIN, KDV, yazar kasa, bordro. 15 adım zaman çizelgesine göre.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Чеклиста',
    title: 'Нова фирма: 15 работи во првите 30 дена',
    publishDate: '23 мај 2026',
    readTime: '12 мин читање',
    intro: 'Го регистриравте вашиот ДООЕЛ — честитки! Но регистрацијата е само почеток. Следните 30 дена се клучни: мора да отворите банкарска сметка, да се пријавите во УЈП, да поставите МПИН, да одлучите за ДДВ, да почнете да издавате фактури и да ги следите расходите. Ако пропуштите рок — казни. Ако не водите евиденција — проблеми со ревизија. Оваа чеклиста од 15 чекори, групирани по денови, ве води низ секоја обврска за да не пропуштите ништо.',
    sections: [
      {
        title: 'Ден 1–5: Правна основа',
        content: 'Во првите неколку дена по регистрацијата, фокусирајте се на правната инфраструктура — документите и сметките без кои не можете да работите.',
        items: null,
        steps: [
          { step: 'Завршете ја ЦРСМ регистрацијата и добијте ЕМБС', desc: 'По поднесувањето на пријавата во Централниот регистар (електронски или физички), добивате решение за регистрација и ЕМБС број — уникатен идентификатор на вашата фирма. Електронски: 4 часа, физички: 1 ден. Таксата е 2.300 МКД електронски или 3.400 МКД физички. Чувајте го решението — ви треба за сите наредни чекори.' },
          { step: 'Отворете деловна банкарска сметка', desc: 'Со решението од ЦРСМ и ЕДБ бројот, отворете деловна (жиро) сметка. Споредете понуди: месечните провизии варираат од 200 до 500 МКД, а некои банки нудат бесплатни првични месеци. Основачкиот капитал од привремената сметка се пренесува тука. Никогаш не мешајте лични и деловни средства — ова е најчестата грешка на нови фирми.' },
          { step: 'Набавете печат (фирмин штембил) — 500–1.500 МКД', desc: 'Иако печатот формално не е задолжителен по Законот за трговски друштва, практично е неопходен: банките, нотарите и државните институции го бараат. Печатот содржи: име на фирмата, ЕМБС, седиште. Цена: 500–1.500 МКД во печатарница. Нарачајте го веднаш — ви треба за отворање сметка во некои банки.' },
        ],
      },
      {
        title: 'Ден 5–10: Даночно поставување',
        content: 'Откако ја имате банкарската сметка и правните документи, време е да се регистрирате во даночниот систем. Ова е делот каде што повеќето нови сопственици се губат — но не мора да биде компликувано.',
        items: null,
        steps: [
          { step: 'Регистрирајте се во УЈП (etax.ujp.gov.mk)', desc: 'Управата за јавни приходи (УЈП) автоматски добива известување од ЦРСМ, но вие мора да поднесете пријава за регистрација на даночен обврзник и да добиете ЕДБ (единствен даночен број). Ова е вашиот даночен идентификатор за сите пријави, фактури и комуникација со УЈП. Рок: 1–3 работни дена. Направете и електронски пристап на e-Tax порталот — оттаму поднесувате ДДВ пријави, МПИН и годишни извештаи.' },
          { step: 'Поставете МПИН за платен список', desc: 'МПИН (Месечна пријава за интегрирана наплата) е задолжителна за секоја фирма — дури и ако сте единствен вработен (управител). Преку МПИН ги пријавувате месечните придонеси: пензиско (18,8%), здравствено (7,5%), невработеност (1,2%), дополнително (0,5%) = вкупно 28%. Рок: до 15-ти секој месец за претходниот месец. Доцнење = казна од УЈП.' },
          { step: 'Одлучете за ДДВ (ДДВ) регистрација — доброволно vs задолжително', desc: 'Задолжителна ДДВ регистрација: годишен промет над 2.000.000 МКД. Доброволна регистрација: можете и под тој праг — корисно ако вашите клиенти се ДДВ обврзници, бидејќи можете да си го вратите влезниот ДДВ. Стандардна стапка: 18%. Намалена: 5% (основни производи). Ако не се регистрирате, не можете да наплаќате ДДВ — но и не можете да го враќате. Размислете добро пред одлуката.' },
          { step: 'Изберете сметководствен метод — упростено или целосно книговодство', desc: 'Упростено книговодство: За фирми со годишен промет до 3.000.000 МКД и до 10 вработени. Поедноставна евиденција, помалку извештаи. Целосно (двојно) книговодство: Задолжително за поголеми фирми и ДДВ обврзници. Побарува биланс на состојба, биланс на успех и аналитика. Повеќето нови ДООЕЛ-и започнуваат со упростено книговодство и преминуваат на целосно кога ќе пораснат.' },
        ],
      },
      {
        title: 'Ден 10–20: Оперативно поставување',
        content: 'Правната и даночната основа е поставена. Сега е време да го воспоставите секојдневното работење — софтвер, фактурирање, фискализација и следење на трошоци.',
        items: null,
        steps: [
          { step: 'Поставете сметководствен софтвер (Facturino бесплатен план)', desc: 'Не чекајте да се натрупаат фактури и сметки. Од првиот ден евидентирајте ги сите приходи и расходи. Facturino нуди бесплатен план за нови фирми: издавање фактури во македонски формат со сите задолжителни полиња за УЈП, автоматска пресметка на ДДВ, следење на расходи, и извештаи за данок на добивка. Регистрирајте се на facturino.mk — без кредитна картичка, без ризик.' },
          { step: 'Издајте ја првата фактура', desc: 'Секоја фактура мора да ги содржи: име и адреса на издавач и примач, ЕМБС и ЕДБ на двете страни, реден број на фактура, датум, опис на услуга/производ, количина, единечна цена, вкупен износ, ДДВ (ако сте обврзник), рок за плаќање и жиро-сметка. Facturino ги пополнува сите овие полиња автоматски — само внесете ги ставките.' },
          { step: 'Поставете фискален апарат ако продавате на физички лица (Б2Ц)', desc: 'Ако продавате стоки или услуги на физички лица, фискална каса е задолжителна по Закон за регистрирање на готовински плаќања. Трошок: 15.000–40.000 МКД за апарат + годишно одржување. Facturino поддржува интеграција со фискални апарати. Ако продавате само на фирми (Б2Б) и плаќањата се преку жиро-сметка, фискален апарат не ви треба.' },
          { step: 'Поставете следење на расходи — банкарски фид или рачно', desc: 'Секој денар потрошен за бизнисот мора да биде евидентиран и поткрепен со фактура или сметка. Два начина: (1) Рачно внесување: фотографирајте ги сметките и внесете ги во Facturino. (2) Банкарски фид: поврзете ја деловната сметка со Facturino за автоматски увоз на трансакции — системот ги категоризира со AI. Чувајте ги сите фактури минимум 5 години — ова е законска обврска.' },
        ],
      },
      {
        title: 'Ден 20–30: Тим и раст',
        content: 'Ако планирате да вработите луѓе, да ги разберете придонесите или да се подготвите за првата даночна пријава — последните 10 дена од првиот месец се идеални за тоа.',
        items: null,
        steps: [
          { step: 'Вработете го првиот вработен — договор, МПИН, здравствена', desc: 'За секој вработен ви треба: потпишан договор за вработување (минимум плата: 20.175 МКД бруто за 2026), пријава на МПИН во рок од 8 дена од почетокот на работа, пријава за здравствено осигурување во ФЗОМ, и здравствена книшка. Сите придонеси се одбиваат од бруто платата — работодавецот не плаќа дополнително врз бруто. Facturino го автоматизира целиот процес на пресметка бруто-нето.' },
          { step: 'Поставете платен список — разберете ја бруто-нето пресметката', desc: 'Од бруто платата се одбиваат: пензиско 18,8%, здравствено 7,5%, невработеност 1,2%, дополнително 0,5% = 28% придонеси. Потоа персонален данок 10% на (бруто – придонеси – лично ослободување 10.390 МКД). Пример: бруто 30.000 МКД → придонеси 8.400 → даночна основа 11.210 → данок 1.121 → нето 20.479 МКД. Рок за исплата: до 15-ти секој месец. Facturino ја прави оваа пресметка автоматски.' },
          { step: 'Обезбедете професионално осигурување (ако е применливо)', desc: 'Некои дејности бараат задолжително професионално осигурување: сметководители, ревизори, адвокати, архитекти, лекари. Дури и ако не е задолжително, препорачуваме осигурување од одговорност — заштитува ве од побарувања на клиенти. Цена: 5.000–20.000 МКД годишно, зависно од дејноста и покритието.' },
          { step: 'Планирајте ја првата даночна пријава — знајте ги роковите', desc: 'Месечни обврски: МПИН до 15-ти. ДДВ пријава (ако сте обврзник): до 25-ти секој месец или квартално. Аконтација на данок на добивка: квартално, до 15-ти во наредниот месец. Годишна даночна пријава: до 28 февруари за претходната година. Годишна сметка: до 15 март. Направете календар со сите рокови — или користете Facturino кој ве потсетува автоматски.' },
        ],
      },
      {
        title: 'Чести грешки на нови фирми',
        content: 'Овие грешки ги гледаме постојано кај нови сопственици. Избегнете ги за да заштедите пари, нерви и време:',
        items: [
          'Мешање на лични и деловни сметки — ако не ги раздвоите финансиите, ревизија од УЈП е кошмар. Отворете посебна деловна сметка и никогаш не плаќајте лични трошоци од неа.',
          'Пропуштање на прагот за ДДВ и доцна регистрација — ако го надминете прагот од 2.000.000 МКД без да се регистрирате, следуваат казни и ретроактивна ДДВ обврска. Следете го прометот месечно.',
          'Нечување на фактури и сметки — законска обврска е да ги чувате минимум 5 години. Без документација, секој расход е непризнаен. Скенирајте или фотографирајте — Facturino ги складира дигитално.',
          'Доцнење со МПИН пријава — рокот е 15-ти секој месец. Доцнење значи казна од 500 до 1.000 EUR за прв прекршок, и повеќе за повторен. Поставете потсетник или користете автоматски платен список.',
          'Немање правилен образец за фактура — фактура без сите задолжителни полиња (ЕМБС, ЕДБ, реден број, ДДВ ако е применливо) може да биде одбиена од УЈП. Користете софтвер кој ги гарантира сите полиња.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ви помага',
        content: 'Facturino е создаден специјално за нови фирми во Македонија. Наместо да учите сметководство, софтверот ве води чекор по чекор:',
        items: [
          'Бесплатен план за нови фирми — без кредитна картичка, без ризик, без временско ограничување.',
          'Фактури во македонски формат со сите задолжителни полиња за УЈП — автоматски пополнети.',
          'Автоматска пресметка на ДДВ (18%, 5%, 10%) на секоја фактура и извештај.',
          'Банкарски фид: автоматски увоз на трансакции од деловната сметка и AI категоризација.',
          'Платен список: бруто-нето пресметка, МПИН образец, придонеси — се е автоматски.',
          'Потсетници за рокови: МПИН до 15-ти, ДДВ до 25-ти, годишна сметка до 15 март.',
          'Извештаи за данок на добивка, биланс на состојба и биланс на успех.',
          'Дигитална архива: сите фактури, сметки и документи на едно место, заштитени и достапни.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Како да отворите фирма во Македонија: Комплетен водич' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Сметководство за почетници: Основи што секој бизнис ги знае' },
      { slug: 'mpin-registracija-2026', title: 'МПИН регистрација 2026: Чекор по чекор водич' },
    ],
    cta: {
      title: 'Започнете правилно од ден 1',
      desc: 'Facturino е вашиот партнер за првите 30 дена и понатаму. Фактури, расходи, платен список — се на едно место.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Checklist',
    title: 'New Company Checklist: 15 Things to Do in Your First 30 Days',
    publishDate: 'May 23, 2026',
    readTime: '12 min read',
    intro: 'You registered your company — congratulations! But registration is just the beginning. The next 30 days are critical: you need to open a bank account, register with the tax office, set up payroll reporting, decide on VAT, start issuing invoices, and track your expenses. Miss a deadline and you face penalties. Fail to keep records and you are exposed in an audit. This 15-step checklist, grouped by timeline, walks you through every obligation so you do not miss a thing.',
    sections: [
      {
        title: 'Days 1–5: Legal Foundation',
        content: 'In the first few days after registration, focus on the legal infrastructure — the documents and accounts without which you cannot operate.',
        items: null,
        steps: [
          { step: 'Complete CRMS registration and get your EMBS', desc: 'After submitting your application to the Central Registry (electronically or in person), you receive a registration decision and EMBS number — your company\'s unique identifier. Electronic processing: 4 hours; in-person: 1 day. The fee is 2,300 MKD (electronic) or 3,400 MKD (in-person). Keep the decision document safe — you need it for every subsequent step.' },
          { step: 'Open a business bank account', desc: 'With the CRMS decision and your EDB (tax number), open a business (giro) account. Compare offers: monthly fees range from 200 to 500 MKD, and some banks offer free initial months. The share capital from the temporary account transfers here. Never mix personal and business funds — this is the most common mistake new companies make.' },
          { step: 'Get your company seal (stamp) — 500–1,500 MKD', desc: 'Although the seal is not formally required by the Company Law, it is practically essential: banks, notaries, and government institutions require it. The seal contains: company name, EMBS, registered address. Cost: 500–1,500 MKD at a stamp shop. Order it immediately — some banks require it to open an account.' },
        ],
      },
      {
        title: 'Days 5–10: Tax Setup',
        content: 'Once you have your bank account and legal documents, it is time to register in the tax system. This is where most new owners get lost — but it does not have to be complicated.',
        items: null,
        steps: [
          { step: 'Register at UJP (etax.ujp.gov.mk)', desc: 'The Public Revenue Office (UJP) automatically receives notification from CRMS, but you must submit a taxpayer registration form and obtain your EDB (unique tax number). This is your tax identifier for all filings, invoices, and communication with UJP. Timeline: 1–3 business days. Also set up electronic access to the e-Tax portal — this is where you submit VAT returns, MPIN, and annual reports.' },
          { step: 'Set up MPIN for payroll', desc: 'MPIN (Monthly Integrated Collection Return) is mandatory for every company — even if you are the sole employee (director). Through MPIN you report monthly contributions: pension (18.8%), health (7.5%), unemployment (1.2%), supplementary (0.5%) = 28% total. Deadline: by the 15th of each month for the previous month. Late filing means penalties from UJP.' },
          { step: 'Decide on VAT registration — voluntary vs mandatory', desc: 'Mandatory VAT registration: annual turnover above 2,000,000 MKD. Voluntary registration: you can register below that threshold — beneficial if your clients are VAT-registered, since you can reclaim input VAT. Standard rate: 18%. Reduced: 5% (essential goods). If you do not register, you cannot charge VAT — but you also cannot reclaim it. Think carefully before deciding.' },
          { step: 'Choose your accounting method — simplified or full', desc: 'Simplified bookkeeping: For companies with annual turnover up to 3,000,000 MKD and up to 10 employees. Simpler record-keeping, fewer reports. Full (double-entry) bookkeeping: Mandatory for larger companies and VAT payers. Requires a balance sheet, income statement, and analytics. Most new DOOELs start with simplified bookkeeping and switch to full when they grow.' },
        ],
      },
      {
        title: 'Days 10–20: Operations',
        content: 'The legal and tax foundation is in place. Now it is time to set up daily operations — software, invoicing, fiscalization, and expense tracking.',
        items: null,
        steps: [
          { step: 'Set up accounting software (Facturino free plan)', desc: 'Do not wait for invoices and receipts to pile up. From day one, record every income and expense. Facturino offers a free plan for new companies: invoice creation in Macedonian format with all mandatory UJP fields, automatic VAT calculation, expense tracking, and tax reports. Sign up at facturino.mk — no credit card, no risk.' },
          { step: 'Issue your first invoice', desc: 'Every invoice must contain: issuer and recipient name and address, EMBS and EDB for both parties, sequential invoice number, date, description of service/product, quantity, unit price, total amount, VAT (if registered), payment deadline, and bank account. Facturino fills in all these fields automatically — just enter the line items.' },
          { step: 'Set up a fiscal device if doing retail (B2C)', desc: 'If you sell goods or services to individuals, a fiscal register is mandatory under the Law on Cash Payment Registration. Cost: 15,000–40,000 MKD for the device plus annual maintenance. Facturino supports fiscal device integration. If you sell only to businesses (B2B) and payments go through bank accounts, a fiscal device is not required.' },
          { step: 'Set up expense tracking — bank feed or manual', desc: 'Every denar spent on the business must be recorded and supported by an invoice or receipt. Two approaches: (1) Manual entry: photograph receipts and enter them in Facturino. (2) Bank feed: connect your business account to Facturino for automatic transaction import — the system categorizes them with AI. Keep all invoices for at least 5 years — this is a legal obligation.' },
        ],
      },
      {
        title: 'Days 20–30: Team & Growth',
        content: 'If you plan to hire people, understand contributions, or prepare for your first tax filing — the last 10 days of month one are ideal for this.',
        items: null,
        steps: [
          { step: 'Hire your first employee — contract, MPIN, health card', desc: 'For each employee you need: a signed employment contract (minimum gross salary: 20,175 MKD for 2026), MPIN registration within 8 days of starting work, health insurance registration with FZOM, and a health booklet. All contributions are deducted from the gross salary — the employer does not pay additional amounts on top of gross. Facturino automates the entire gross-to-net calculation.' },
          { step: 'Set up payroll — understand gross-to-net calculation', desc: 'From the gross salary, deductions are: pension 18.8%, health 7.5%, unemployment 1.2%, supplementary 0.5% = 28% contributions. Then personal income tax 10% on (gross minus contributions minus personal deduction of 10,390 MKD). Example: gross 30,000 MKD, contributions 8,400, taxable base 11,210, tax 1,121, net 20,479 MKD. Payment deadline: by the 15th of each month. Facturino calculates this automatically.' },
          { step: 'Arrange professional liability insurance (if applicable)', desc: 'Certain professions require mandatory professional insurance: accountants, auditors, lawyers, architects, doctors. Even if not mandatory, we recommend liability insurance — it protects you from client claims. Cost: 5,000–20,000 MKD annually, depending on the profession and coverage.' },
          { step: 'Plan for your first tax filing — know the deadlines', desc: 'Monthly obligations: MPIN by the 15th. VAT return (if registered): by the 25th of each month or quarterly. Corporate income tax advance: quarterly, by the 15th of the following month. Annual tax return: by February 28 for the previous year. Annual financial statements: by March 15. Create a calendar with all deadlines — or use Facturino, which sends automatic reminders.' },
        ],
      },
      {
        title: 'Common mistakes new companies make',
        content: 'We see these mistakes constantly from new business owners. Avoid them to save money, stress, and time:',
        items: [
          'Mixing personal and business accounts — if you do not separate finances, a UJP audit becomes a nightmare. Open a dedicated business account and never pay personal expenses from it.',
          'Missing the VAT threshold and registering late — if you exceed the 2,000,000 MKD threshold without registering, penalties and retroactive VAT obligations follow. Monitor your turnover monthly.',
          'Not keeping invoices and receipts — you are legally required to keep them for at least 5 years. Without documentation, every expense is disallowed. Scan or photograph them — Facturino stores them digitally.',
          'Late MPIN filing — the deadline is the 15th of each month. Being late means a penalty of EUR 500 to 1,000 for a first offense, and more for repeat violations. Set a reminder or use automated payroll.',
          'Not having a proper invoice template — an invoice missing mandatory fields (EMBS, EDB, sequential number, VAT if applicable) can be rejected by UJP. Use software that guarantees all fields are present.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps',
        content: 'Facturino was built specifically for new companies in North Macedonia. Instead of learning accounting, the software guides you step by step:',
        items: [
          'Free plan for new companies — no credit card, no risk, no time limit.',
          'Invoices in Macedonian format with all mandatory UJP fields — auto-filled.',
          'Automatic VAT calculation (18%, 5%, 10%) on every invoice and report.',
          'Bank feed: automatic transaction import from your business account with AI categorization.',
          'Payroll: gross-to-net calculation, MPIN form, contributions — all automated.',
          'Deadline reminders: MPIN by the 15th, VAT by the 25th, annual accounts by March 15.',
          'Reports for corporate income tax, balance sheet, and income statement.',
          'Digital archive: all invoices, receipts, and documents in one place, secure and accessible.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'How to Register a Company in Macedonia: Complete Guide' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Accounting for Beginners: Basics Every Business Should Know' },
      { slug: 'mpin-registracija-2026', title: 'MPIN Registration 2026: Step-by-Step Guide' },
    ],
    cta: {
      title: 'Start right from day 1',
      desc: 'Facturino is your partner for the first 30 days and beyond. Invoices, expenses, payroll — everything in one place.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Lista kontrolluese',
    title: 'Kompani e re: 15 gjëra për 30 ditët e para',
    publishDate: '23 maj 2026',
    readTime: '12 min lexim',
    intro: 'E regjistruat kompaninë tuaj — urime! Por regjistrimi është vetëm fillimi. 30 ditët e ardhshme janë vendimtare: duhet të hapni llogari bankare, të regjistroheni në zyrën tatimore, të vendosni raportimin MPIN, të vendosni për TVSH-në, të filloni lëshimin e faturave dhe të ndiqni shpenzimet. Nëse humbisni një afat — gjoba. Nëse nuk mbani evidencë — probleme me auditimin. Kjo listë kontrolluese me 15 hapa, e grupuar sipas ditëve, ju çon nëpër çdo detyrim që të mos humbisni asgjë.',
    sections: [
      {
        title: 'Ditët 1–5: Baza ligjore',
        content: 'Në ditët e para pas regjistrimit, fokusohuni në infrastrukturën ligjore — dokumentet dhe llogaritë pa të cilat nuk mund të operoni.',
        items: null,
        steps: [
          { step: 'Përfundoni regjistrimin RQRM dhe merrni EMBS', desc: 'Pas dorëzimit të aplikimit në Regjistrin Qendror (elektronikisht ose personalisht), merrni vendimin e regjistrimit dhe numrin EMBS — identifikuesin unik të kompanisë suaj. Përpunimi elektronik: 4 orë; personalisht: 1 ditë. Tarifa është 2.300 MKD (elektronik) ose 3.400 MKD (personalisht). Ruajeni vendimin — ju nevojitet për çdo hap të ardhshëm.' },
          { step: 'Hapni llogari bankare biznesi', desc: 'Me vendimin e RQRM dhe numrin EDB (numri tatimor), hapni llogari biznesi (xhiro). Krahasoni ofertat: tarifat mujore variojnë nga 200 deri në 500 MKD, dhe disa banka ofrojnë muaj fillestarë falas. Kapitali nga llogaria e përkohshme transferohet këtu. Asnjëherë mos përzieni fondet personale me ato të biznesit — kjo është gabimi më i zakonshëm i kompanive të reja.' },
          { step: 'Merrni vulën e kompanisë — 500–1.500 MKD', desc: 'Edhe pse vula nuk kërkohet formalisht nga Ligji për Shoqëritë Tregtare, praktikisht është e domosdoshme: bankat, noterët dhe institucionet qeveritare e kërkojnë. Vula përmban: emrin e kompanisë, EMBS, adresën. Kostoja: 500–1.500 MKD. Porositeni menjëherë — disa banka e kërkojnë për hapjen e llogarisë.' },
        ],
      },
      {
        title: 'Ditët 5–10: Vendosja tatimore',
        content: 'Pasi të keni llogarinë bankare dhe dokumentet ligjore, është koha të regjistroheni në sistemin tatimor. Kjo është pjesa ku shumica e pronarëve të rinj humbasin — por nuk duhet të jetë e komplikuar.',
        items: null,
        steps: [
          { step: 'Regjistrohuni në UJP (etax.ujp.gov.mk)', desc: 'Zyra e të Ardhurave Publike (UJP) merr automatikisht njoftim nga RQRM, por ju duhet të dorëzoni formularin e regjistrimit të tatimpaguesit dhe të merrni EDB (numrin unik tatimor). Ky është identifikuesi juaj tatimor për të gjitha deklarimet, faturat dhe komunikimin me UJP. Afati: 1–3 ditë pune. Gjithashtu vendosni qasjen elektronike në portalin e-Tax — aty dorëzoni deklarimet TVSH, MPIN dhe raportet vjetore.' },
          { step: 'Vendosni MPIN për listën e pagave', desc: 'MPIN (Deklarata Mujore e Mbledhjes së Integruar) është e detyrueshme për çdo kompani — edhe nëse jeni punonjësi i vetëm (drejtor). Përmes MPIN raportoni kontributet mujore: pension (18,8%), shëndetësi (7,5%), papunësi (1,2%), shtesë (0,5%) = 28% gjithsej. Afati: deri më 15 të çdo muaji për muajin e kaluar. Vonesë = gjobë nga UJP.' },
          { step: 'Vendosni për regjistrimin TVSH — vullnetar vs i detyrueshëm', desc: 'Regjistrimi i detyrueshëm TVSH: qarkullimi vjetor mbi 2.000.000 MKD. Regjistrimi vullnetar: mund të regjistroheni edhe nën atë prag — i dobishëm nëse klientët tuaj janë të regjistruar për TVSH, pasi mund të riktheni TVSH-në hyrëse. Norma standarde: 18%. E reduktuar: 5% (produkte bazë). Nëse nuk regjistroheni, nuk mund të faturoni TVSH — por as ta riktheni. Mendoni mirë para vendimit.' },
          { step: 'Zgjidhni metodën e kontabilitetit — e thjeshtuar ose e plotë', desc: 'Kontabiliteti i thjeshtuar: Për kompani me qarkullim vjetor deri në 3.000.000 MKD dhe deri në 10 punonjës. Evidencë më e thjeshtë, më pak raporte. Kontabiliteti i plotë (me regjistrim të dyfishtë): I detyrueshëm për kompani më të mëdha dhe tatimpagues TVSH. Kërkon bilanc, pasqyrë të ardhurash dhe analitikë. Shumica e DOOEL-ve të reja fillojnë me kontabilitet të thjeshtuar dhe kalojnë në të plotë kur rriten.' },
        ],
      },
      {
        title: 'Ditët 10–20: Operacionet',
        content: 'Baza ligjore dhe tatimore është vendosur. Tani është koha të vendosni operacionet e përditshme — softuer, faturim, fiskalizim dhe ndjekje shpenzimesh.',
        items: null,
        steps: [
          { step: 'Vendosni softuer kontabiliteti (plani falas Facturino)', desc: 'Mos prisni të grumbullohen faturat dhe faturat. Nga dita e parë regjistroni çdo të ardhur dhe shpenzim. Facturino ofron plan falas për kompani të reja: krijim faturash në format maqedonas me të gjitha fushat e detyrueshme UJP, llogaritje automatike TVSH, ndjekje shpenzimesh dhe raporte tatimore. Regjistrohuni në facturino.mk — pa kartë krediti, pa rrezik.' },
          { step: 'Lëshoni faturën e parë', desc: 'Çdo faturë duhet të përmbajë: emrin dhe adresën e lëshuesit dhe marrësit, EMBS dhe EDB për të dyja palët, numrin rendor të faturës, datën, përshkrimin e shërbimit/produktit, sasinë, çmimin për njësi, shumën totale, TVSH (nëse jeni i regjistruar), afatin e pagesës dhe llogarinë bankare. Facturino i plotëson automatikisht të gjitha këto fusha — thjesht shtoni artikujt.' },
          { step: 'Vendosni pajisje fiskale nëse shisni me pakicë (B2C)', desc: 'Nëse shisni mallra ose shërbime te individë, arka fiskale është e detyrueshme sipas Ligjit për Regjistrimin e Pagesave me Para të Gatshme. Kostoja: 15.000–40.000 MKD për pajisjen plus mirëmbajtje vjetore. Facturino mbështet integrimin me pajisje fiskale. Nëse shisni vetëm te bizneset (B2B) dhe pagesat shkojnë përmes llogarive bankare, pajisja fiskale nuk nevojitet.' },
          { step: 'Vendosni ndjekjen e shpenzimeve — furnizim bankar ose manual', desc: 'Çdo denar i shpenzuar për biznesin duhet të regjistrohet dhe mbështetet me faturë. Dy qasje: (1) Regjistrim manual: fotografoni faturat dhe futini në Facturino. (2) Furnizim bankar: lidhni llogarinë e biznesit me Facturino për import automatik transaksionesh — sistemi i kategorizon me AI. Ruani të gjitha faturat për së paku 5 vjet — kjo është detyrim ligjor.' },
        ],
      },
      {
        title: 'Ditët 20–30: Ekipi dhe rritja',
        content: 'Nëse planifikoni të punësoni njerëz, të kuptoni kontributet ose të përgatiteni për deklarimin e parë tatimor — 10 ditët e fundit të muajit të parë janë ideale për këtë.',
        items: null,
        steps: [
          { step: 'Punësoni punonjësin e parë — kontratë, MPIN, kartelë shëndetësore', desc: 'Për çdo punonjës ju nevojitet: kontratë pune e nënshkruar (paga minimale bruto: 20.175 MKD për 2026), regjistrim MPIN brenda 8 ditëve nga fillimi i punës, regjistrim për sigurim shëndetësor në FZOM dhe kartelë shëndetësore. Të gjitha kontributet zbriten nga paga bruto — punëdhënësi nuk paguan shuma shtesë mbi bruto. Facturino automatizon gjithë llogaritjen bruto-neto.' },
          { step: 'Vendosni listën e pagave — kuptoni llogaritjen bruto-neto', desc: 'Nga paga bruto zbriten: pension 18,8%, shëndetësi 7,5%, papunësi 1,2%, shtesë 0,5% = 28% kontribute. Pastaj tatimi personal mbi të ardhurat 10% mbi (bruto minus kontribute minus zbritja personale 10.390 MKD). Shembull: bruto 30.000 MKD → kontribute 8.400 → baza tatueshme 11.210 → tatimi 1.121 → neto 20.479 MKD. Afati i pagesës: deri më 15 të çdo muaji. Facturino e bën këtë llogaritje automatikisht.' },
          { step: 'Siguroni sigurim profesional (nëse aplikohet)', desc: 'Disa profesione kërkojnë sigurim profesional të detyrueshëm: kontabilistë, auditorë, avokatë, arkitektë, mjekë. Edhe nëse nuk është e detyrueshme, rekomandojmë sigurim përgjegjësie — ju mbron nga pretendimet e klientëve. Kostoja: 5.000–20.000 MKD në vit, sipas profesionit dhe mbulimit.' },
          { step: 'Planifikoni deklarimin e parë tatimor — njihni afatet', desc: 'Detyrimet mujore: MPIN deri më 15. Deklarata TVSH (nëse jeni i regjistruar): deri më 25 të çdo muaji ose tremujore. Akontacion tatimi mbi fitimin: tremujore, deri më 15 të muajit pasardhës. Deklarata vjetore tatimore: deri më 28 shkurt për vitin e kaluar. Pasqyrat financiare vjetore: deri më 15 mars. Krijoni kalendar me të gjitha afatet — ose përdorni Facturino që dërgon kujtesa automatike.' },
        ],
      },
      {
        title: 'Gabimet e zakonshme të kompanive të reja',
        content: 'Këto gabime i shohim vazhdimisht te pronarët e rinj të biznesit. Shmangini për të kursyer para, stres dhe kohë:',
        items: [
          'Përzierja e llogarive personale dhe të biznesit — nëse nuk i ndani financat, auditimi UJP bëhet makth. Hapni llogari biznesi të dedikuar dhe asnjëherë mos paguani shpenzime personale prej saj.',
          'Humbja e pragut TVSH dhe regjistrimi me vonesë — nëse e tejkaloni pragun e 2.000.000 MKD pa u regjistruar, vijojnë gjobat dhe detyrimi retroaktiv TVSH. Monitoroni qarkullimin mujorisht.',
          'Mosruajtja e faturave — jeni të detyruar ligjërisht t\'i ruani për së paku 5 vjet. Pa dokumentacion, çdo shpenzim nuk pranohet. Skanoni ose fotografoni — Facturino i ruan dixhitalisht.',
          'Vonesa me deklarimin MPIN — afati është 15 i çdo muaji. Vonesa sjell gjobë prej 500 deri 1.000 EUR për shkeljen e parë, dhe më shumë për përsëritje. Vendosni kujtesë ose përdorni listë pagash automatike.',
          'Mosgjetja e shabllonit të duhur të faturës — faturë pa fushat e detyrueshme (EMBS, EDB, numri rendor, TVSH nëse aplikohet) mund të refuzohet nga UJP. Përdorni softuer që garanton të gjitha fushat.',
        ],
        steps: null,
      },
      {
        title: 'Si ju ndihmon Facturino',
        content: 'Facturino u ndërtua posaçërisht për kompani të reja në Maqedoninë e Veriut. Në vend që të mësoni kontabilitet, softueri ju udhëzon hap pas hapi:',
        items: [
          'Plan falas për kompani të reja — pa kartë krediti, pa rrezik, pa kufizim kohor.',
          'Fatura në format maqedonas me të gjitha fushat e detyrueshme UJP — të plotësuara automatikisht.',
          'Llogaritje automatike TVSH (18%, 5%, 10%) në çdo faturë dhe raport.',
          'Furnizim bankar: import automatik transaksionesh nga llogaria e biznesit me kategorizim AI.',
          'Lista e pagave: llogaritja bruto-neto, formulari MPIN, kontributet — gjithçka e automatizuar.',
          'Kujtesa për afate: MPIN deri më 15, TVSH deri më 25, llogaritë vjetore deri më 15 mars.',
          'Raporte për tatimin mbi fitimin, bilancin dhe pasqyrën e të ardhurave.',
          'Arkiv dixhital: të gjitha faturat dhe dokumentet në një vend, të sigurta dhe të aksesueshme.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Si të hapni kompani në Maqedoni: Udhëzues i plotë' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Kontabiliteti për fillestarë: Bazat që çdo biznes i njeh' },
      { slug: 'mpin-registracija-2026', title: 'Regjistrimi MPIN 2026: Udhëzues hap pas hapi' },
    ],
    cta: {
      title: 'Filloni si duhet nga dita 1',
      desc: 'Facturino është partneri juaj për 30 ditët e para dhe më tej. Fatura, shpenzime, lista pagash — gjithçka në një vend.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Kontrol Listesi',
    title: 'Yeni Şirket: İlk 30 Günde Yapılacak 15 Şey',
    publishDate: '23 Mayıs 2026',
    readTime: '12 dk okuma',
    intro: 'Şirketinizi tescil ettirdiniz — tebrikler! Ancak tescil sadece başlangıçtır. Sonraki 30 gün kritiktir: banka hesabı açmanız, vergi dairesine kayıt yaptırmanız, bordro raporlamasını ayarlamanız, KDV hakkında karar vermeniz, fatura düzenlemeye başlamanız ve giderlerinizi takip etmeniz gerekir. Bir süreyi kaçırırsanız ceza alırsınız. Kayıt tutmazsanız denetimde sorun yaşarsınız. Günlere göre gruplandırılmış bu 15 adımlık kontrol listesi, hiçbir şeyi kaçırmamanız için sizi her yükümlülükte yönlendirir.',
    sections: [
      {
        title: 'Gün 1–5: Hukuki Temel',
        content: 'Tescilden sonraki ilk birkaç gün, hukuki altyapıya odaklanın — onsuz çalışamayacağınız belgeler ve hesaplar.',
        items: null,
        steps: [
          { step: 'CRMS kaydını tamamlayın ve EMBS alın', desc: 'Merkez Sicile (elektronik veya şahsen) başvurunuzu sunduktan sonra, tescil kararı ve EMBS numarası — şirketinizin benzersiz tanımlayıcısı — alırsınız. Elektronik işlem: 4 saat; şahsen: 1 gün. Ücret elektronik 2.300 MKD veya şahsen 3.400 MKD\'dir. Kararı saklayın — sonraki her adım için gereklidir.' },
          { step: 'Ticari banka hesabı açın', desc: 'CRMS kararı ve EDB (vergi numarası) ile ticari (cari) hesap açın. Teklifleri karşılaştırın: aylık ücretler 200 ile 500 MKD arasında değişir ve bazı bankalar ücretsiz başlangıç ayları sunar. Geçici hesaptaki sermaye buraya aktarılır. Kişisel ve ticari fonları asla karıştırmayın — yeni şirketlerin en yaygın hatasıdır.' },
          { step: 'Şirket mührünü alın — 500–1.500 MKD', desc: 'Mühür Şirketler Kanunu tarafından resmi olarak zorunlu tutulmasa da, pratikte gereklidir: bankalar, noterler ve devlet kurumları ister. Mühür içeriği: şirket adı, EMBS, kayıtlı adres. Maliyet: 500–1.500 MKD. Hemen sipariş edin — bazı bankalar hesap açmak için mühür ister.' },
        ],
      },
      {
        title: 'Gün 5–10: Vergi Kurulumu',
        content: 'Banka hesabınız ve hukuki belgeleriniz hazır olduğunda, vergi sistemine kaydolma zamanı gelmiştir. Çoğu yeni işletme sahibinin kaybolduğu yer burasıdır — ama karmaşık olmak zorunda değildir.',
        items: null,
        steps: [
          { step: 'UJP\'ye kaydolun (etax.ujp.gov.mk)', desc: 'Kamu Gelir İdaresi (UJP) CRMS\'den otomatik bildirim alır, ancak vergi mükellefi kayıt formunu göndermeniz ve EDB (benzersiz vergi numarası) almanız gerekir. Bu, tüm beyannameler, faturalar ve UJP ile iletişim için vergi kimliğinizdir. Süre: 1–3 iş günü. Ayrıca e-Tax portalına elektronik erişim kurun — KDV beyannameleri, MPIN ve yıllık raporlar buradan gönderilir.' },
          { step: 'Bordro için MPIN ayarlayın', desc: 'MPIN (Aylık Entegre Tahsilat Beyannamesi) her şirket için zorunludur — tek çalışan (müdür) siz olsanız bile. MPIN aracılığıyla aylık katkıları bildirirsiniz: emeklilik (%18,8), sağlık (%7,5), işsizlik (%1,2), ek (%0,5) = toplam %28. Son tarih: her ayın 15\'i bir önceki ay için. Gecikme UJP\'den ceza anlamına gelir.' },
          { step: 'KDV kaydı hakkında karar verin — gönüllü vs zorunlu', desc: 'Zorunlu KDV kaydı: yıllık ciro 2.000.000 MKD üzerinde. Gönüllü kayıt: bu eşiğin altında da kaydolabilirsiniz — müşterileriniz KDV kayıtlıysa faydalıdır, çünkü girdi KDV\'sini geri alabilirsiniz. Standart oran: %18. İndirimli: %5 (temel ürünler). Kayıt olmazsanız KDV tahsil edemezsiniz — ama geri de alamazsınız. Karar vermeden önce iyi düşünün.' },
          { step: 'Muhasebe yönteminizi seçin — basitleştirilmiş veya tam', desc: 'Basitleştirilmiş defter tutma: Yıllık cirosu 3.000.000 MKD\'ye kadar olan ve 10\'a kadar çalışanı olan şirketler için. Daha basit kayıt tutma, daha az rapor. Tam (çift taraflı) defter tutma: Daha büyük şirketler ve KDV mükellefleri için zorunlu. Bilanço, gelir tablosu ve analitik gerektirir. Çoğu yeni DOOEL basitleştirilmiş defter tutmayla başlar ve büyüdükçe tama geçer.' },
        ],
      },
      {
        title: 'Gün 10–20: Operasyonlar',
        content: 'Hukuki ve vergisel temel kuruldu. Şimdi günlük operasyonları ayarlama zamanı — yazılım, faturalama, fiskalizasyon ve gider takibi.',
        items: null,
        steps: [
          { step: 'Muhasebe yazılımı kurun (Facturino ücretsiz plan)', desc: 'Faturaların ve makbuzların birikmesini beklemeyin. İlk günden itibaren her gelir ve gideri kaydedin. Facturino yeni şirketler için ücretsiz plan sunar: tüm zorunlu UJP alanlarıyla Makedon formatında fatura oluşturma, otomatik KDV hesaplaması, gider takibi ve vergi raporları. facturino.mk\'da kaydolun — kredi kartı yok, risk yok.' },
          { step: 'İlk faturanızı düzenleyin', desc: 'Her fatura şunları içermelidir: düzenleyen ve alıcının adı ve adresi, her iki tarafın EMBS ve EDB\'si, sıralı fatura numarası, tarih, hizmet/ürün açıklaması, miktar, birim fiyat, toplam tutar, KDV (kayıtlıysanız), ödeme vadesi ve banka hesabı. Facturino tüm bu alanları otomatik doldurur — sadece kalemleri girin.' },
          { step: 'Perakende satış yapıyorsanız (B2C) yazar kasa kurun', desc: 'Bireylere mal veya hizmet satıyorsanız, Nakit Ödeme Kaydı Kanunu uyarınca yazar kasa zorunludur. Maliyet: cihaz için 15.000–40.000 MKD artı yıllık bakım. Facturino yazar kasa entegrasyonunu destekler. Sadece işletmelere (B2B) satış yapıyorsanız ve ödemeler banka hesapları üzerinden geçiyorsa, yazar kasa gerekmez.' },
          { step: 'Gider takibi kurun — banka beslemesi veya manuel', desc: 'İşletme için harcanan her denar kaydedilmeli ve fatura veya makbuzla desteklenmelidir. İki yaklaşım: (1) Manuel giriş: makbuzları fotoğraflayın ve Facturino\'ya girin. (2) Banka beslemesi: otomatik işlem aktarımı için ticari hesabınızı Facturino\'ya bağlayın — sistem AI ile kategorize eder. Tüm faturaları en az 5 yıl saklayın — bu yasal zorunluluktur.' },
        ],
      },
      {
        title: 'Gün 20–30: Ekip ve Büyüme',
        content: 'İstihdam etmeyi, katkıları anlamayı veya ilk vergi beyannamenize hazırlanmayı planlıyorsanız — ilk ayın son 10 günü bunun için idealdir.',
        items: null,
        steps: [
          { step: 'İlk çalışanınızı işe alın — sözleşme, MPIN, sağlık kartı', desc: 'Her çalışan için şunlar gerekli: imzalı iş sözleşmesi (2026 için asgari brüt maaş: 20.175 MKD), işe başlamadan itibaren 8 gün içinde MPIN kaydı, FZOM\'da sağlık sigortası kaydı ve sağlık cüzdanı. Tüm katkılar brüt maaştan düşülür — işveren brüt üzerine ek ödeme yapmaz. Facturino tüm brüt-net hesaplamasını otomatikleştirir.' },
          { step: 'Bordro kurun — brüt-net hesaplamayı anlayın', desc: 'Brüt maaştan kesintiler: emeklilik %18,8, sağlık %7,5, işsizlik %1,2, ek %0,5 = %28 katkı. Sonra kişisel gelir vergisi %10 (brüt eksi katkılar eksi kişisel indirim 10.390 MKD). Örnek: brüt 30.000 MKD → katkılar 8.400 → matrah 11.210 → vergi 1.121 → net 20.479 MKD. Ödeme tarihi: her ayın 15\'ine kadar. Facturino bunu otomatik hesaplar.' },
          { step: 'Mesleki sorumluluk sigortası yaptırın (varsa)', desc: 'Bazı meslekler zorunlu mesleki sigorta gerektirir: muhasebeciler, denetçiler, avukatlar, mimarlar, doktorlar. Zorunlu olmasa bile sorumluluk sigortası öneriyoruz — sizi müşteri taleplerinden korur. Maliyet: meslek ve kapsama göre yıllık 5.000–20.000 MKD.' },
          { step: 'İlk vergi beyannamenizi planlayın — son tarihleri bilin', desc: 'Aylık yükümlülükler: MPIN 15\'ine kadar. KDV beyannamesi (kayıtlıysanız): her ayın 25\'ine kadar veya üç aylık. Kurumlar vergisi avansı: üç aylık, takip eden ayın 15\'ine kadar. Yıllık vergi beyannamesi: önceki yıl için 28 Şubat\'a kadar. Yıllık mali tablolar: 15 Mart\'a kadar. Tüm son tarihlerle bir takvim oluşturun — veya otomatik hatırlatma gönderen Facturino\'yu kullanın.' },
        ],
      },
      {
        title: 'Yeni şirketlerin yaygın hataları',
        content: 'Bu hataları yeni işletme sahiplerinden sürekli görüyoruz. Para, stres ve zaman tasarrufu için bunlardan kaçının:',
        items: [
          'Kişisel ve ticari hesapları karıştırma — finansları ayırmazsanız, UJP denetimi kabusa döner. Ayrı bir ticari hesap açın ve asla ondan kişisel harcama yapmayın.',
          'KDV eşiğini kaçırma ve geç kayıt — 2.000.000 MKD eşiğini kayıt olmadan aşarsanız, cezalar ve geriye dönük KDV yükümlülüğü takip eder. Cironuzu aylık izleyin.',
          'Fatura ve makbuzları saklamamak — en az 5 yıl saklamak yasal zorunluluktur. Belge olmadan her gider reddedilir. Tarayın veya fotoğraflayın — Facturino dijital olarak saklar.',
          'MPIN beyanında gecikme — son tarih her ayın 15\'idir. Gecikme, ilk ihlal için 500 ile 1.000 EUR arasında ceza anlamına gelir ve tekrarda daha fazla. Hatırlatıcı kurun veya otomatik bordro kullanın.',
          'Uygun fatura şablonu olmaması — zorunlu alanları eksik (EMBS, EDB, sıra numarası, varsa KDV) bir fatura UJP tarafından reddedilebilir. Tüm alanların mevcut olduğunu garanti eden yazılım kullanın.',
        ],
        steps: null,
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content: 'Facturino özellikle Kuzey Makedonya\'daki yeni şirketler için inşa edildi. Muhasebe öğrenmek yerine, yazılım sizi adım adım yönlendirir:',
        items: [
          'Yeni şirketler için ücretsiz plan — kredi kartı yok, risk yok, süre sınırı yok.',
          'Tüm zorunlu UJP alanlarıyla Makedon formatında faturalar — otomatik doldurulmuş.',
          'Her fatura ve raporda otomatik KDV hesaplaması (%18, %5, %10).',
          'Banka beslemesi: AI kategorizasyonuyla ticari hesabınızdan otomatik işlem aktarımı.',
          'Bordro: brüt-net hesaplama, MPIN formu, katkılar — hepsi otomatik.',
          'Son tarih hatırlatıcıları: MPIN 15\'ine kadar, KDV 25\'ine kadar, yıllık hesaplar 15 Mart\'a kadar.',
          'Kurumlar vergisi, bilanço ve gelir tablosu raporları.',
          'Dijital arşiv: tüm faturalar, makbuzlar ve belgeler tek yerde, güvenli ve erişilebilir.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Makedonya\'da şirket nasıl kurulur: Kapsamlı rehber' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Yeni başlayanlar için muhasebe: Her işletmenin bilmesi gerekenler' },
      { slug: 'mpin-registracija-2026', title: 'MPIN Kaydı 2026: Adım Adım Rehber' },
    ],
    cta: {
      title: '1. günden doğru başlayın',
      desc: 'Facturino ilk 30 gün ve sonrasında ortağınızdır. Faturalar, giderler, bordro — her şey tek yerde.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function PaketZaNovaFirmaPage({
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
    slug: 'paket-za-nova-firma',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['нова фирма чеклиста', 'starting a business in macedonia', 'отворање фирма', 'МПИН', 'ДДВ', 'УЈП', 'checklist'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/paket-za-nova-firma` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
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
