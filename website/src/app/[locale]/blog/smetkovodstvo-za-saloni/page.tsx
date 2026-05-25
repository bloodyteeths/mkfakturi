import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-saloni', {
    title: {
      mk: 'Сметководство за салони за убавина: POS, материјали и вработени',
      en: 'Beauty Salon Accounting in Macedonia: POS, Supplies & Employees',
      sq: 'Kontabiliteti per sallone bukurie: POS, materiale dhe punonjes',
      tr: 'Guzellik Salonu Muhasebesi Makedonya: POS, Malzeme ve Calisanlar',
    },
    description: {
      mk: 'Комплетен водич за сметководство во салони за убавина: услуга + производ на иста фактура, следење на материјали, провизии за вработени, фискален уред и најчести грешки.',
      en: 'Complete guide to beauty salon accounting in North Macedonia: mixed service and product invoicing, consumable tracking, employee commissions, fiscal device, and common mistakes.',
      sq: 'Udhezes i plote per kontabilitetin ne sallone bukurie ne Maqedoni: faturim i perzier sherbim dhe produkt, ndjekje e materialeve, provizione per punonjes, pajisje fiskale dhe gabimet me te shpeshta.',
      tr: 'Kuzey Makedonya\'da guzellik salonu muhasebesi rehberi: hizmet ve urun karisik faturalama, malzeme takibi, calisan komisyonlari, fiskal cihaz ve yaygin hatalar.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за салони за убавина: POS, материјали и вработени',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro:
      'Салоните за убавина — фризерски, козметички, маникир и спа центри — имаат единствени сметководствени предизвици: мешана фактура со услуга и производ, следење потрошувачка на материјали по клиент, различни модели на плаќање на вработени (фиксна плата, провизија, закуп на стол) и строги фискални обврски кон физички лица. Овој водич покрива сè што сопствениците на салони треба да знаат за правилно книговодство во 2026.',
    sections: [
      {
        title: 'Услуга + производ на иста фактура: ДДВ третман',
        content:
          'Салоните за убавина продаваат и услуги (шишање, фарбање, маникир) и производи (шампон, крема, лак). Двата типа можат да бидат на иста фактура, но важат исти правила:',
        items: [
          'ДДВ 18% на услуги — Шишање, фарбање, маникир, педикир, масажа, третмани за лице. Нема намалена стапка за услуги за убавина',
          'ДДВ 18% на производи — Шампони, кремови, бои за коса, лакови, козметика продадена на клиент',
          'Мешана фактура: Шишање (услуга, 18%) + продажба на шампон (производ, 18%) = иста стапка, но мора да бидат одделни ставки',
          'ВАЖНО: Дури и ако стапката е иста (18%), услугите и производите се различни типови промет — фактурата мора да ги раздвои',
          'Ако сте паушалец (нерегистриран за ДДВ): нема обврска за ДДВ, но мора да издавате фискална сметка за секоја продажба',
          'Праг за ДДВ регистрација: 2.000.000 МКД годишен промет. Над тој праг — задолжителна ДДВ регистрација',
          'Совет: Користете одделни шифри за услуги (Ш-001 Шишање) и производи (П-001 Шампон) за полесна евиденција',
        ],
        steps: null,
      },
      {
        title: 'Следење на потрошен материјал по услуга',
        content:
          'Најголемиот скриен трошок во салон е потрошувачкиот материјал — боја за коса, шампон, кремови, фолија, маски. Без следење, не знаете колку ве чини секоја услуга:',
        items: null,
        steps: [
          { step: 'Нормативна потрошувачка по услуга', desc: 'Дефинирајте колку материјал се троши по услуга: фарбање на средна коса = 80мл боја + 80мл оксиген + 1 фолија. Ова е основа за пресметка на трошок по клиент.' },
          { step: 'Евиденција на набавки', desc: 'Секоја набавка на материјал (боја, шампон, крема) се евидентира како трошок со датум, количина и цена. Добавувачите треба да испраќаат фактура.' },
          { step: 'Месечна инвентура', desc: 'Пребројте ги залихите на крајот од месецот. Разликата помеѓу набавено и продадено/потрошено = реална потрошувачка. Ако разликата е голема — истражете (кражба, расипување, лична употреба).' },
          { step: 'Трошок по клиент', desc: 'Пресметајте: (набавна цена на материјал + пропорционален дел од режија) / број на услуги = трошок по клиент. Идеален трошок за материјал е 10-15% од цената на услугата.' },
          { step: 'Раздвојување: за продажба vs за употреба', desc: 'Шампоните во витрина (за продажба) се залиха. Шампоните во работната станица (за миење) се потрошен материјал. Евидентирајте ги одделно.' },
        ],
      },
      {
        title: 'Модели на плаќање на вработени: провизии и закуп',
        content:
          'Салоните користат различни модели за компензација на фризери и козметичари. Секој модел има различен даночен третман:',
        items: null,
        steps: [
          { step: 'Фиксна плата + провизија', desc: 'Најчест модел. Основна бруто плата (пр. 25.000 МКД) + процент од остварен промет (10-30%). Целата провизија е дел од бруто платата — придонеси 28% и ПДД 10% се пресметуваат на целиот износ.' },
          { step: 'Само провизија (100% комисија)', desc: 'Ризичен модел. Ако вработениот е пријавен, минималната бруто плата е 38.507 МКД (2026) — не можете да платите помалку, дури и ако промет нема. Провизијата над минималната се додава.' },
          { step: 'Закуп на стол (chair rental)', desc: 'Фризерот плаќа месечен закуп за користење на стол/работна станица. Тој е самостоен вршител на дејност (СВД) или ДООЕЛ, не вработен. ВИЕ не плаќате придонеси за него — тој сам си поднесува МПИН.' },
          { step: 'Закуп на кабина (booth rental)', desc: 'Исто како закуп на стол, но за козметичари/масери кои користат одделна просторија. Договор за закуп + фактура за закупнина од закупецот.' },
          { step: 'ВНИМАНИЕ: Прикриен работен однос', desc: 'Ако „закупецот" работи по ваш распоред, со ваши клиенти и ваши материјали — УЈП може да го квалификува како прикриен работен однос. Казна: ретроактивни придонеси + 30% пенал.' },
        ],
      },
      {
        title: 'Каса, термини и бонови',
        content:
          'Салоните работат со мешавина на готовина, картичка, подарочни ваучери и депозити. Правилно управување со касата е клучно:',
        items: [
          'Готовина vs картичка: Евидентирајте го секое плаќање одделно. Дневниот депозит во банка мора да одговара на готовинскиот промет од Z-извештајот',
          'No-show (неповикан клиент): Ако наплаќате за неповикување — тоа е приход и мора да се евидентира. Ако не наплаќате — нема книжење',
          'Депозити/аванси: Ако клиентот плаќа аванс за закажан термин, тоа е обврска (не приход) додека услугата не се изврши. По извршување — се книжи како приход',
          'Подарочни ваучери: Продажба на ваучер = обврска. Искористување на ваучер = приход. Истечени ваучери = приход (по 12 месеци)',
          'Бакшиш: Бакшишот е личен приход на вработениот, не на салонот. Ако се собира преку каса — мора да се евидентира и оданочи',
          'Преплатени пакети (пр. 10 третмани за цена на 8): Приходот се признава пропорционално — по секое искористување, не одеднаш при продажба',
          'Дневна каса: Пребројте ја касата на крајот од денот. Разликата помеѓу системскиот и фактичкиот износ мора да се евидентира (вишок или кусок)',
        ],
        steps: null,
      },
      {
        title: 'Фискален уред: обврски за салони',
        content:
          'Секој салон за убавина кој продава услуги и/или производи на физички лица (B2C) мора да има фискален уред:',
        items: null,
        steps: [
          { step: 'Фискален уред е задолжителен', desc: 'Без исклучок — и за фризерски салон со 1 стол и за спа центар со 20 вработени. Казна за работа без фискален уред: EUR 2.000-5.000.' },
          { step: 'Секоја услуга = фискална сметка', desc: 'Шишање, фарбање, маникир — секоја услуга мора да биде регистрирана на фискален уред пред или веднаш по извршување.' },
          { step: 'Сторно процедура', desc: 'Ако клиентот се врати и бара поврат — сторно на фискална сметка може САМО со одобрение на менаџер. Документирајте причина и задржете копија.' },
          { step: 'Z-извештај (дневен збир)', desc: 'На крајот на секој работен ден — задолжителен Z-извештај. Чувајте ги 5 години. Фискалните податоци автоматски се пренесуваат до УЈП.' },
          { step: 'Продажба на производи', desc: 'Продажба на шампон или крема на клиент = одделна ставка на фискалната сметка, со точна шифра и ДДВ стапка.' },
        ],
      },
      {
        title: 'Најчести грешки во салоните',
        content:
          'Од нашето искуство со салони-клиенти, овие се грешките кои најчесто доведуваат до проблеми со УЈП:',
        items: [
          'Не се следи потрошен материјал — Боја, шампон и кремови се купуваат „на око" без евиденција. Резултат: не знаете колку ве чини секоја услуга и дали маржата е позитивна',
          'Лични производи мешани со деловни — Сопственикот користи салонски шампон дома или носи кремови за лична употреба. Ова е непризнат расход и ако УЈП го утврди — репроценка',
          'Бакшиш не се евидентира — Готовинскиот бакшиш „исчезнува" без евиденција. Ако е собран преку каса — тоа е приход на вработениот и мора да се оданочи',
          'Неформално плаќање на вработени — Провизии платени „на рака" без да бидат дел од бруто платата. Ризик: ретроактивни придонеси + казна',
          'Подарочни ваучери книжени како приход при продажба — Ваучерот е обврска додека не се искористи. Ако го книжите како приход при продажба, ќе платите ДДВ и данок предвреме',
          'Нема инвентура на залиха — Без месечна инвентура, не можете да ја утврдите реалната потрошувачка и евентуални загуби',
          'Работа без фискален уред — Некои салони работат „на рака" без фискална сметка. Ризик: казна EUR 2.000-5.000 + затворање на објектот до 30 дена',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino е дизајниран за мали бизниси како салони за убавина — со алатки кои ги решаваат точно овие предизвици:',
        items: [
          'POS бесплатен план: Издавајте фискални сметки за услуги и производи без месечна претплата',
          'Мешани фактури: Услуга (шишање) + производ (шампон) на иста фактура, со автоматско раздвојување по тип',
          'Залиха за материјали: Евидентирајте ја набавката и потрошувачката на боја, шампон, кремови — со автоматска пресметка на трошок по услуга',
          'Модул за плати: Пресметка на бруто плата со провизија, придонеси 28% и ПДД 10% — автоматски, со МПИН извештај',
          'Следење на расходи: Закупнина, струја, вода, набавка на материјали — категоризирани и готови за даночна пријава',
          'Подарочни ваучери и пакети: Евидентирајте ги правилно како обврска, со автоматско признавање на приход при искористување',
          'Интеграција со фискален печатач: Поддршка за 9 модели во Македонија, автоматски Z-извештај',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'POS софтвер за Македонија: Споредба' },
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија' },
      { slug: 'ddv-registracija-prag-2026', title: 'ДДВ регистрација: Кога е задолжителна?' },
      { slug: 'najdobar-pos-softver-2026', title: 'Најдобар POS софтвер 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Фискален печатач: Поврзување и употреба' },
    ],
    bottomCta: {
      title: 'POS за салон? Facturino е бесплатен.',
      subtitle: 'Услуги + производи, залиха, плати со провизија, фискален печатач — сè вклучено. Без месечна претплата.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Beauty Salon Accounting in Macedonia: POS, Supplies & Employees',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro:
      'Beauty salons — hair, cosmetics, nail, and spa studios — face unique accounting challenges: mixed invoices with services and products, tracking consumable usage per client, different employee compensation models (fixed salary, commission, chair rental), and strict fiscal obligations for B2C sales. This guide covers everything salon owners need to know about proper bookkeeping in 2026.',
    sections: [
      {
        title: 'Service + product on the same invoice: VAT treatment',
        content:
          'Beauty salons sell both services (haircuts, coloring, manicures) and products (shampoo, cream, nail polish). Both can appear on the same invoice, but the same rules apply:',
        items: [
          'VAT 18% on services — Haircuts, coloring, manicures, pedicures, massage, facial treatments. There is no reduced rate for beauty services',
          'VAT 18% on products — Shampoos, creams, hair dyes, nail polish, cosmetics sold to client',
          'Mixed invoice: Haircut (service, 18%) + shampoo sale (product, 18%) = same rate, but must be separate line items',
          'IMPORTANT: Even though the rate is the same (18%), services and products are different types of turnover — the invoice must separate them',
          'If you are a lump-sum taxpayer (not VAT registered): no VAT obligation, but you must issue a fiscal receipt for every sale',
          'VAT registration threshold: MKD 2,000,000 annual turnover. Above that — mandatory VAT registration',
          'Tip: Use separate codes for services (S-001 Haircut) and products (P-001 Shampoo) for easier record-keeping',
        ],
        steps: null,
      },
      {
        title: 'Tracking consumable usage per service',
        content:
          'The biggest hidden cost in a salon is consumable materials — hair dye, shampoo, creams, foil, masks. Without tracking, you don\'t know what each service actually costs:',
        items: null,
        steps: [
          { step: 'Standard consumption per service', desc: 'Define how much material is used per service: medium hair coloring = 80ml dye + 80ml developer + 1 foil sheet. This is the basis for calculating cost per client.' },
          { step: 'Purchase records', desc: 'Every material purchase (dye, shampoo, cream) is recorded as an expense with date, quantity, and price. Suppliers should send invoices.' },
          { step: 'Monthly inventory count', desc: 'Count your stock at month-end. The difference between purchased and sold/consumed = actual consumption. If the gap is large — investigate (theft, spoilage, personal use).' },
          { step: 'Cost per client', desc: 'Calculate: (material purchase cost + proportional overhead) / number of services = cost per client. Ideal material cost is 10-15% of the service price.' },
          { step: 'Separate: for sale vs for use', desc: 'Shampoos on the display shelf (for sale) are inventory. Shampoos at the workstation (for washing) are consumable materials. Record them separately.' },
        ],
      },
      {
        title: 'Employee compensation models: commissions and rental',
        content:
          'Salons use various compensation models for hairdressers and beauticians. Each model has different tax treatment:',
        items: null,
        steps: [
          { step: 'Fixed salary + commission', desc: 'Most common model. Base gross salary (e.g. MKD 25,000) + percentage of generated turnover (10-30%). The entire commission is part of gross salary — contributions at 28% and PIT at 10% are calculated on the full amount.' },
          { step: 'Commission only (100% commission)', desc: 'Risky model. If the employee is registered, the minimum gross salary is MKD 38,507 (2026) — you cannot pay less even if there is no turnover. Commission above minimum is added on top.' },
          { step: 'Chair rental', desc: 'The hairdresser pays monthly rent to use a chair/workstation. They are a sole proprietor (SVD) or LLC, not an employee. YOU do not pay contributions for them — they file their own MPIN.' },
          { step: 'Booth rental', desc: 'Same as chair rental, but for beauticians/masseuses who use a separate room. Requires a rental agreement + the tenant issues an invoice for rent.' },
          { step: 'WARNING: Disguised employment', desc: 'If the "tenant" works on your schedule, with your clients and your materials — UJP may classify this as disguised employment. Penalty: retroactive contributions + 30% surcharge.' },
        ],
      },
      {
        title: 'Cash management, appointments, and vouchers',
        content:
          'Salons deal with a mix of cash, card, gift vouchers, and deposits. Proper cash management is essential:',
        items: [
          'Cash vs card: Record every payment separately. The daily bank deposit must match cash turnover from the Z-report',
          'No-shows: If you charge for no-shows — that is revenue and must be recorded. If you don\'t charge — no entry needed',
          'Deposits/advances: If a client pays a deposit for a booked appointment, it is a liability (not revenue) until the service is performed. After completion — recorded as revenue',
          'Gift vouchers: Voucher sale = liability. Voucher redemption = revenue. Expired vouchers = revenue (after 12 months)',
          'Tips: Tips are the employee\'s personal income, not the salon\'s. If collected through the register — they must be recorded and taxed',
          'Prepaid packages (e.g. 10 treatments for the price of 8): Revenue is recognized proportionally — after each use, not all at once at the time of sale',
          'Daily cash count: Count the register at end of day. The difference between system and actual amount must be recorded (surplus or shortage)',
        ],
        steps: null,
      },
      {
        title: 'Fiscal device obligations for salons',
        content:
          'Every beauty salon selling services and/or products to individuals (B2C) must have a fiscal device:',
        items: null,
        steps: [
          { step: 'Fiscal device is mandatory', desc: 'No exceptions — for a 1-chair hair salon and a 20-employee spa center alike. Penalty for operating without a fiscal device: EUR 2,000-5,000.' },
          { step: 'Every service = fiscal receipt', desc: 'Haircut, coloring, manicure — every service must be registered on the fiscal device before or immediately after completion.' },
          { step: 'Void procedure', desc: 'If a client returns and requests a refund — voiding a fiscal receipt requires manager approval ONLY. Document the reason and keep a copy.' },
          { step: 'Z-report (daily summary)', desc: 'At the end of each business day — a mandatory Z-report. Keep them for 5 years. Fiscal data is automatically transmitted to UJP.' },
          { step: 'Product sales', desc: 'Selling a shampoo or cream to a client = a separate line item on the fiscal receipt, with the correct code and VAT rate.' },
        ],
      },
      {
        title: 'Common mistakes in salons',
        content:
          'From our experience with salon clients, these are the mistakes that most often lead to problems with UJP:',
        items: [
          'Not tracking consumables — Dye, shampoo, and creams are bought "by feel" without records. Result: you don\'t know what each service costs or whether your margin is positive',
          'Personal products mixed with business — The owner uses salon shampoo at home or takes creams for personal use. This is a non-deductible expense and if UJP detects it — reassessment',
          'Tips not recorded — Cash tips "disappear" without records. If collected through the register — it is the employee\'s income and must be taxed',
          'Informal employee payments — Commissions paid "under the table" without being part of gross salary. Risk: retroactive contributions + penalty',
          'Gift vouchers recorded as revenue at sale — A voucher is a liability until redeemed. If you record it as revenue at sale, you pay VAT and tax prematurely',
          'No inventory count — Without monthly inventory, you cannot determine actual consumption and potential losses',
          'Operating without a fiscal device — Some salons work "by hand" without fiscal receipts. Risk: EUR 2,000-5,000 fine + business closure for up to 30 days',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps',
        content:
          'Facturino is designed for small businesses like beauty salons — with tools that solve exactly these challenges:',
        items: [
          'Free POS plan: Issue fiscal receipts for services and products with no monthly subscription',
          'Mixed invoices: Service (haircut) + product (shampoo) on the same invoice, with automatic separation by type',
          'Inventory for supplies: Track purchases and consumption of dye, shampoo, creams — with automatic cost-per-service calculation',
          'Payroll module: Calculate gross salary with commission, 28% contributions, and 10% PIT — automatically, with MPIN report',
          'Expense tracking: Rent, electricity, water, material purchases — categorized and ready for tax return',
          'Gift vouchers and packages: Record them properly as liabilities, with automatic revenue recognition upon redemption',
          'Fiscal printer integration: Support for 9 models in North Macedonia, automatic Z-report',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'POS Software for North Macedonia: Comparison' },
      { slug: 'presmetka-na-plata-mk', title: 'Salary Calculation in North Macedonia' },
      { slug: 'ddv-registracija-prag-2026', title: 'VAT Registration: When Is It Mandatory?' },
      { slug: 'najdobar-pos-softver-2026', title: 'Best POS Software 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Fiscal Printer: Setup and Usage' },
    ],
    bottomCta: {
      title: 'POS for your salon? Facturino is free.',
      subtitle: 'Services + products, inventory, payroll with commissions, fiscal printer — all included. No monthly subscription.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti per sallone bukurie: POS, materiale dhe punonjes',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro:
      'Sallonet e bukurise — parukeri, kozmetike, manikyr dhe qendra spa — kane sfida unike kontabel: fature e perzier me sherbim dhe produkt, ndjekje e konsumit te materialeve per klient, modele te ndryshme kompensimi per punonjes (page fikse, provizion, qira karrige) dhe detyrime strikte fiskale per shitje B2C. Ky udhezes mbulon gjithcka qe pronaret e salloneve duhet te dine per kontabilitetin e duhur ne 2026.',
    sections: [
      {
        title: 'Sherbim + produkt ne te njejten fature: trajtimi TVSH',
        content:
          'Sallonet e bukurise shesin edhe sherbime (prerje flokesh, ngjyrosje, manikyr) edhe produkte (shampo, krem, llak). Te dy llojet mund te jene ne te njejten fature, por rregullat jane te njejta:',
        items: [
          'TVSH 18% per sherbime — Prerje flokesh, ngjyrosje, manikyr, pedikyr, masazh, trajtime fytyre. Nuk ka norme te reduktuar per sherbime bukurie',
          'TVSH 18% per produkte — Shampoja, krema, ngjyra flokesh, llaqe, kozmetike te shitura klientit',
          'Fature e perzier: Prerje flokesh (sherbim, 18%) + shitje shampo (produkt, 18%) = e njejta norme, por duhet te jene zera te ndare',
          'E RENDESISHME: Edhe pse norma eshte e njejte (18%), sherbimet dhe produktet jane lloje te ndryshme qarkullimi — fatura duhet t\'i ndaje',
          'Nese jeni tatimpagues me shume te pergjithshme (pa TVSH): asnje detyrim TVSH, por duhet te leshoni fature fiskale per cdo shitje',
          'Pragu per regjistrimin TVSH: 2.000.000 MKD qarkullim vjetor. Mbi kete — regjistrim i detyrueshem TVSH',
          'Keshille: Perdorni kode te ndara per sherbime (SH-001 Prerje) dhe produkte (P-001 Shampo) per evidence me te lehte',
        ],
        steps: null,
      },
      {
        title: 'Ndjekja e konsumit te materialeve per sherbim',
        content:
          'Shpenzimi me i madh i fshehur ne sallon eshte materiali konsumues — ngjyre flokesh, shampo, krema, fletushe, maska. Pa ndjekje, nuk dini sa ju kushton cdo sherbim:',
        items: null,
        steps: [
          { step: 'Konsumi standard per sherbim', desc: 'Percaktoni sa material perdoret per sherbim: ngjyrosje e flokeve mesatare = 80ml ngjyre + 80ml oksigjen + 1 fletushe. Kjo eshte baza per llogaritjen e kostos per klient.' },
          { step: 'Regjistrimi i blerjeve', desc: 'Cdo blerje materiali (ngjyre, shampo, krem) regjistrohet si shpenzim me date, sasi dhe cmim. Furnizuesit duhet te dergojne fature.' },
          { step: 'Inventari mujor', desc: 'Numroni stokun ne fund te muajit. Dallimi midis te bleres dhe te shitures/konsumuares = konsumi real. Nese dallimi eshte i madh — hetoni.' },
          { step: 'Kostoja per klient', desc: 'Llogaritni: (cmimi i blerjes se materialit + pjesa proporcionale e shpenzimeve) / numri i sherbimeve = kostoja per klient. Kostoja ideale per material eshte 10-15% e cmimit te sherbimit.' },
          { step: 'Ndarja: per shitje vs per perdorim', desc: 'Shampojat ne vitrine (per shitje) jane inventar. Shampojat ne stacionin e punes (per larje) jane material konsumues. Regjistroni vecanerisht.' },
        ],
      },
      {
        title: 'Modelet e kompensimit te punonjesve: provizione dhe qira',
        content:
          'Sallonet perdorin modele te ndryshme per kompensimin e parukiereve dhe kozmetisteve. Cdo model ka trajtim te ndryshem tatimor:',
        items: null,
        steps: [
          { step: 'Page fikse + provizion', desc: 'Modeli me i zakonshem. Paga baze bruto (p.sh. 25.000 MKD) + perqindje e qarkullimit te realizuar (10-30%). I gjithe provizioni eshte pjese e pages bruto — kontributet 28% dhe TAP 10% llogariten mbi shumen totale.' },
          { step: 'Vetem provizion (100% komision)', desc: 'Model i rrezikshem. Nese punonjesi eshte i regjistruar, paga minimale bruto eshte 38.507 MKD (2026) — nuk mund te paguani me pak. Provizioni mbi minimalen shtohet.' },
          { step: 'Qira karrige (chair rental)', desc: 'Parukieri paguan qira mujore per perdorimin e karriges/stacionit te punes. Ai eshte ushtrues i pavarur i veprimtarise, jo punonjes. JU nuk paguani kontribute per te — ai vete dorzon MPIN.' },
          { step: 'Qira kabine (booth rental)', desc: 'Njejte si qira karrige, por per kozmetiste/masoze qe perdorin dhome te ndare. Kontrate qiraje + fature per qirane nga qiramarresi.' },
          { step: 'KUJDES: Marredhenie e fshehur pune', desc: 'Nese "qiramarresi" punon sipas orarit tuaj, me klientet tuaj dhe materialet tuaja — DAP mund ta kualifikoje si marredhenie te fshehur pune. Gjobe: kontribute retroaktive + 30% shtese.' },
        ],
      },
      {
        title: 'Menaxhimi i arkes, termineve dhe bonove',
        content:
          'Sallonet punojne me perzierie te parave ne dore, kartes, vaucerave dhurate dhe depozitave. Menaxhimi i duhur i arkes eshte thelbesor:',
        items: [
          'Para ne dore vs karte: Regjistroni cdo pagese vecanerisht. Depozita ditore bankare duhet te perputhet me qarkullimin me para nga Z-raporti',
          'No-show (klient i paparaqitur): Nese naplatisni per mosparaqitje — ajo eshte te ardhur dhe duhet te regjistrohet. Nese nuk naplatisni — asnje regjistrim',
          'Depozita/avanse: Nese klienti paguan avans per termin te rezervuar, ajo eshte detyrim (jo te ardhur) derisa sherbimi te kryhet. Pas kryerjes — regjistrohet si te ardhur',
          'Vaucera dhurate: Shitja e vaucerit = detyrim. Perdorimi i vaucerit = te ardhur. Vaucera te skaduara = te ardhur (pas 12 muajsh)',
          'Bakshish: Bakshishi eshte te ardhur personale e punonjesit, jo e sallonit. Nese mblidhet permes arkes — duhet te regjistrohet dhe tatimohet',
          'Paketa te parapaguara (p.sh. 10 trajtime per cmimin e 8): Te ardhurat njehen proporcionalisht — pas cdo perdorimi, jo te gjitha menjehere',
          'Arka ditore: Numroni arken ne fund te dites. Dallimi midis shumes se sistemit dhe asaj reale duhet te regjistrohet',
        ],
        steps: null,
      },
      {
        title: 'Detyrimet e pajisjes fiskale per sallone',
        content:
          'Cdo sallon bukurie qe shet sherbime dhe/ose produkte te individet (B2C) duhet te kete pajisje fiskale:',
        items: null,
        steps: [
          { step: 'Pajisja fiskale eshte e detyrueshme', desc: 'Pa perjashtim — per parukeri me 1 karrige dhe per qender spa me 20 punonjes. Gjobe per punim pa pajisje fiskale: EUR 2.000-5.000.' },
          { step: 'Cdo sherbim = fature fiskale', desc: 'Prerje, ngjyrosje, manikyr — cdo sherbim duhet te regjistrohet ne pajisjen fiskale para ose menjehere pas kryerjes.' },
          { step: 'Procedura e anulimit', desc: 'Nese klienti kthehet dhe kerkon kthim — anulimi i fatures fiskale kerkon VETEM miratimin e menaxherit. Dokumentoni arsyen dhe ruani kopje.' },
          { step: 'Z-raporti (permbledhja ditore)', desc: 'Ne fund te cdo dite pune — Z-raport i detyrueshem. Ruani 5 vjet. Te dhenat fiskale transmetohen automatikisht te DAP.' },
          { step: 'Shitja e produkteve', desc: 'Shitja e shampos ose kremes klientit = zer i ndare ne faturen fiskale, me kodin e sakte dhe normen TVSH.' },
        ],
      },
      {
        title: 'Gabimet me te shpeshta ne sallone',
        content:
          'Nga pervoja jone me klientet-sallone, keto jane gabimet qe me shpesh cojne ne probleme me DAP:',
        items: [
          'Nuk ndiqet konsumi i materialeve — Ngjyra, shampo dhe krema blihen "me sy" pa evidence. Rezultati: nuk dini sa ju kushton cdo sherbim',
          'Produkte personale te perzierta me ato te biznesit — Pronari perdor shampon e sallonit ne shtepi. Ky eshte shpenzim i panjohur — nese DAP e zbulon, rivleresim',
          'Bakshishi nuk regjistrohet — Bakshishi me para "zhduket" pa evidence. Nese mblidhet permes arkes — eshte te ardhur e punonjesit dhe duhet te tatimohet',
          'Pagesat joformale te punonjesve — Provizione te paguara "nen dore" pa qene pjese e pages bruto. Rrezik: kontribute retroaktive + gjobe',
          'Vaucerat dhurate te regjistruara si te ardhura kur shiten — Vauceri eshte detyrim derisa te perdoret. Nese e regjistroni si te ardhur kur shitet, paguani TVSH parakohesisht',
          'Asnje inventar — Pa inventar mujor, nuk mund te percaktoni konsumin real dhe humbjet e mundshme',
          'Punim pa pajisje fiskale — Disa sallone punojne pa fature fiskale. Rrezik: gjobe EUR 2.000-5.000 + mbyllje e objektit deri ne 30 dite',
        ],
        steps: null,
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino eshte dizajnuar per biznese te vogla si sallonet e bukurise — me vegla qe i zgjidhin pikerisht keto sfida:',
        items: [
          'Plan POS falas: Leshoni fatura fiskale per sherbime dhe produkte pa abonim mujor',
          'Fatura te perzierta: Sherbim (prerje) + produkt (shampo) ne te njejten fature, me ndarje automatike sipas llojit',
          'Inventar per materiale: Ndiqni blerjet dhe konsumin e ngjyres, shampos, kremave — me llogaritje automatike te kostos per sherbim',
          'Moduli i pagave: Llogaritni pagen bruto me provizion, kontribute 28% dhe TAP 10% — automatikisht, me raport MPIN',
          'Ndjekje e shpenzimeve: Qiraja, rryma, uji, blerja e materialeve — te kategorizuara dhe gati per deklaraten tatimore',
          'Vaucera dhurate dhe paketa: Regjistroni si detyrime, me njohje automatike te te ardhurave kur perdoren',
          'Integrim me printer fiskal: Mbeshtetje per 9 modele ne Maqedoni, Z-raport automatik',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'Softuer POS per Maqedonine: Krahasim' },
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pages ne Maqedoni' },
      { slug: 'ddv-registracija-prag-2026', title: 'Regjistrimi TVSH: Kur eshte i detyrueshem?' },
      { slug: 'najdobar-pos-softver-2026', title: 'Softueri me i mire POS 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Printeri fiskal: Lidhja dhe perdorimi' },
    ],
    bottomCta: {
      title: 'POS per sallonin tuaj? Facturino eshte falas.',
      subtitle: 'Sherbime + produkte, inventar, paga me provizion, printer fiskal — gjithcka e perfshire. Pa abonim mujor.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Sektor',
    title: 'Guzellik Salonu Muhasebesi Makedonya: POS, Malzeme ve Calisanlar',
    publishDate: '23 Mayis 2026',
    readTime: '10 dk okuma',
    intro:
      'Guzellik salonlari — kuafor, kozmetik, manikur ve spa merkezleri — benzersiz muhasebe zorluklariyla karsi karsiya: hizmet ve urunun ayni faturada olmasi, musteri basina malzeme tuketiminin takibi, farkli calisan odeme modelleri (sabit maas, komisyon, koltuk kirasi) ve B2C satislar icin katI fiskal yukumlulukler. Bu rehber, salon sahiplerinin 2026\'da dogru muhasebe hakkinda bilmesi gereken her seyi kapsar.',
    sections: [
      {
        title: 'Hizmet + urun ayni faturada: KDV islemi',
        content:
          'Guzellik salonlari hem hizmet (sac kesimi, boyama, manikur) hem de urun (sampuan, krem, oje) satar. Her ikisi de ayni faturada olabilir, ancak ayni kurallar gecerlidir:',
        items: [
          'KDV %18 hizmetlerde — Sac kesimi, boyama, manikur, pedikur, masaj, yuz bakimi. Guzellik hizmetleri icin indirimli oran yoktur',
          'KDV %18 urunlerde — Sampuanlar, kremler, sac boyalari, ojeler, musteriye satilan kozmetik',
          'Karisik fatura: Sac kesimi (hizmet, %18) + sampuan satisi (urun, %18) = ayni oran, ancak ayri kalemler olmalidir',
          'ONEMLI: Oran ayni (%18) olsa bile, hizmetler ve urunler farkli ciro turleridir — fatura bunlari ayirmalidir',
          'Goturu vergi mükellefiyseniz (KDV\'ye kayitli degil): KDV yukumlulugu yok, ancak her satis icin fiskal fis duzenlemelisiniz',
          'KDV kayit esigi: 2.000.000 MKD yillik ciro. Ustunde — zorunlu KDV kaydi',
          'Ipucu: Hizmetler (H-001 Sac Kesimi) ve urunler (U-001 Sampuan) icin ayri kodlar kullanin',
        ],
        steps: null,
      },
      {
        title: 'Hizmet basina malzeme tuketiminin takibi',
        content:
          'Bir salondaki en buyuk gizli maliyet sarfmalzemedir — sac boyasi, sampuan, kremler, folyo, maskeler. Takip olmadan, her hizmetin gercekte ne kadara mal oldugunu bilemezsiniz:',
        items: null,
        steps: [
          { step: 'Hizmet basina standart tuketim', desc: 'Her hizmet icin ne kadar malzeme kullanildigini tanimlayin: orta sac boyama = 80ml boya + 80ml oksidan + 1 folyo yapragi. Bu, musteri basina maliyet hesaplamanin temelidir.' },
          { step: 'Satin alma kayitlari', desc: 'Her malzeme alimi (boya, sampuan, krem) tarih, miktar ve fiyatla bir gider olarak kaydedilir. Tedarikciler fatura gondermelidir.' },
          { step: 'Aylik envanter sayimi', desc: 'Ay sonunda stokunuzu sayın. Alinan ve satilan/tuketilen arasindaki fark = gercek tuketim. Fark buyukse — arastirin (hirsizlik, bozulma, kisisel kullanim).' },
          { step: 'Musteri basina maliyet', desc: 'Hesaplayin: (malzeme alis maliyeti + orantili genel gider) / hizmet sayisi = musteri basina maliyet. Ideal malzeme maliyeti hizmet fiyatinin %10-15\'idir.' },
          { step: 'Ayirma: satis icin vs kullanim icin', desc: 'Vitrindeki sampuanlar (satis icin) envanterdir. Is istasyonundaki sampuanlar (yikama icin) sarfmalzemedir. Ayri kaydedin.' },
        ],
      },
      {
        title: 'Calisan odeme modelleri: komisyonlar ve kiralama',
        content:
          'Salonlar kuaforler ve guzellik uzmanlari icin cesitli odeme modelleri kullanir. Her modelin farkli vergi islemi vardir:',
        items: null,
        steps: [
          { step: 'Sabit maas + komisyon', desc: 'En yaygin model. Temel brut maas (orn. 25.000 MKD) + elde edilen cironun yuzdesi (%10-30). Tum komisyon brut maasin parcasidir — %28 primler ve %10 GV toplam tutar uzerinden hesaplanir.' },
          { step: 'Sadece komisyon (%100 komisyon)', desc: 'Riskli model. Calisan kayitliysa, asgari brut ucret 38.507 MKD\'dir (2026) — ciro olmasa bile daha az odeyemezsiniz. Asgari ustiundeki komisyon eklenir.' },
          { step: 'Koltuk kirasi (chair rental)', desc: 'Kuafor, koltuk/is istasyonu kullanimi icin aylik kira oder. Serbest meslek erbabi veya LLC\'dir, calisan degil. SIZ onun icin prim odemezsiniz — kendi MPIN\'ini kendisi dosyalar.' },
          { step: 'Kabin kirasi (booth rental)', desc: 'Koltuk kirasiyla ayni, ancak ayri oda kullanan guzellik uzmanlari/masozler icindir. Kira sozlesmesi + kiracidan kira faturasi gerekir.' },
          { step: 'UYARI: Gizli istihdam', desc: '"Kiraci" sizin programinizda, sizin musterilerinizle ve sizin malzemelerinizle calisiyorsa — UJP bunu gizli istihdam olarak nitelendirebilir. Ceza: geriye donuk primler + %30 ek ucret.' },
        ],
      },
      {
        title: 'Kasa yonetimi, randevular ve kuponlar',
        content:
          'Salonlar nakit, kart, hediye kuponu ve depozit karisimi ile calisir. Dogru kasa yonetimi onemlidir:',
        items: [
          'Nakit vs kart: Her odemeyi ayri kaydedin. Gunluk banka mevduati Z-raporundaki nakit ciroyla eslesmelidir',
          'Gelmeme (no-show): Gelmeme icin ucret aliyorsaniz — bu gelirdir ve kaydedilmelidir. Almiyorsaniz — kayit gerekmez',
          'Depozitler/avanslar: Musteri rezervasyon icin avans oderse, hizmet yerine getirilene kadar bu bir yukumluluktur (gelir degil). Tamamlandiktan sonra — gelir olarak kaydedilir',
          'Hediye kuponlari: Kupon satisi = yukumluluk. Kupon kullanimi = gelir. Suresi dolan kuponlar = gelir (12 ay sonra)',
          'Bahsis: Bahsis calisanin kisisel geliridir, salonun degil. Kasa uzerinden toplaniyorsa — kaydedilmeli ve vergilendirilmelidir',
          'On odemeli paketler (orn. 8 fiyatina 10 islem): Gelir orantili olarak tanimlanir — her kullanimdan sonra, satista hepsi birden degil',
          'Gunluk kasa sayimi: Gun sonunda kasayi sayin. Sistem ve gercek tutar arasindaki fark kaydedilmelidir (fazla veya eksik)',
        ],
        steps: null,
      },
      {
        title: 'Salonlar icin fiskal cihaz yukumlulukleri',
        content:
          'Bireylere (B2C) hizmet ve/veya urun satan her guzellik salonu fiskal cihaza sahip olmalidir:',
        items: null,
        steps: [
          { step: 'Fiskal cihaz zorunludur', desc: 'Istisnasiz — 1 koltuklu kuafor salonu ve 20 calisanli spa merkezi icin ayni sekilde. Fiskal cihaz olmadan calisma cezasi: EUR 2.000-5.000.' },
          { step: 'Her hizmet = fiskal fis', desc: 'Sac kesimi, boyama, manikur — her hizmet tamamlanmadan once veya hemen sonra fiskal cihazda kaydedilmelidir.' },
          { step: 'Iptal proseduru', desc: 'Musteri geri gelir ve iade isterse — fiskal fis iptali YALNIZCA mudur onayi gerektirir. Nedeni belgelendirin ve kopya saklayin.' },
          { step: 'Z-raporu (gunluk ozet)', desc: 'Her is gununun sonunda — zorunlu Z-raporu. 5 yil saklayin. Fiskal veriler otomatik olarak UJP\'ye iletilir.' },
          { step: 'Urun satislari', desc: 'Musteriye sampuan veya krem satisi = fiskal fiste ayri kalem, dogru kod ve KDV oraniyla.' },
        ],
      },
      {
        title: 'Salonlarda yaygin hatalar',
        content:
          'Salon musterilerimizle olan deneyimimizden, UJP ile en sik soruna yol acan hatalar:',
        items: [
          'Sarfmalzeme takip edilmiyor — Boya, sampuan ve kremler kayit olmadan "goze gore" aliniyor. Sonuc: her hizmetin ne kadara mal oldugunu ve marjin pozitif olup olmadigini bilmiyorsunuz',
          'Kisisel urunler isle karisik — Salon sahibi salon sampuanini evde kullaniyor veya kisisel kullanim icin krem aliyor. Bu kabul edilmeyen giderdir — UJP tespit ederse yeniden degerlendirme',
          'Bahsis kaydedilmiyor — Nakit bahsis kayit olmadan "kayboluyor". Kasa uzerinden toplaniyorsa — calisanin geliridir ve vergilendirilmelidir',
          'Gayri resmi calisan odemeleri — Brut maasin parcasi olmadan "elden" odenen komisyonlar. Risk: geriye donuk primler + ceza',
          'Hediye kuponlari satista gelir olarak kaydediliyor — Kupon kullanilana kadar yukumluluktur. Satista gelir olarak kaydederseniz, KDV ve vergiyi erken odersiniz',
          'Envanter sayimi yok — Aylik envanter olmadan, gercek tuketimi ve olasi kayiplari belirleyemezsiniz',
          'Fiskal cihaz olmadan calisma — Bazi salonlar fiskal fis olmadan "elle" calisiyor. Risk: EUR 2.000-5.000 para cezasi + 30 gune kadar isyeri kapatma',
        ],
        steps: null,
      },
      {
        title: 'Facturino nasil yardimci olur',
        content:
          'Facturino, guzellik salonlari gibi kucuk isletmeler icin tasarlanmistir — tam da bu zorluklari cozen araclarla:',
        items: [
          'Ucretsiz POS plani: Aylik abonelik olmadan hizmetler ve urunler icin fiskal fis duzenleyin',
          'Karisik faturalar: Hizmet (sac kesimi) + urun (sampuan) ayni faturada, ture gore otomatik ayrim',
          'Malzeme icin envanter: Boya, sampuan, krem alim ve tuketimini takip edin — hizmet basina otomatik maliyet hesaplama',
          'Bordro modulu: Komisyonlu brut maas, %28 primler ve %10 GV hesaplayin — otomatik, MPIN raporuyla',
          'Gider takibi: Kira, elektrik, su, malzeme alimlari — kategorize edilmis ve vergi beyannamesi icin hazir',
          'Hediye kuponlari ve paketler: Yukumluluk olarak dogru kaydedin, kullanildiginda otomatik gelir tanima',
          'Fiskal yazici entegrasyonu: Kuzey Makedonya\'da 9 model destegi, otomatik Z-raporu',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili yazilar',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'Makedonya POS Yazilimi: Karsilastirma' },
      { slug: 'presmetka-na-plata-mk', title: 'Makedonya\'da Maas Hesaplama' },
      { slug: 'ddv-registracija-prag-2026', title: 'KDV Kaydi: Ne Zaman Zorunlu?' },
      { slug: 'najdobar-pos-softver-2026', title: 'En Iyi POS Yazilimi 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Fiskal Yazici: Kurulum ve Kullanim' },
    ],
    bottomCta: {
      title: 'Salonunuz icin POS? Facturino ucretsiz.',
      subtitle: 'Hizmetler + urunler, envanter, komisyonlu bordro, fiskal yazici — hepsi dahil. Aylik abonelik yok.',
      cta: 'Ucretsiz baslayin →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaSaloniPage({
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
    slug: 'smetkovodstvo-za-saloni',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['салон', 'salon', 'beauty', 'POS', 'провизија', 'commission', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-saloni` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Дали салон за убавина плаќа ДДВ?', answer: 'Ако годишниот промет надмине 2.000.000 МКД (~32.500 EUR), задолжителна е ДДВ регистрација. И услугите (шишање, маникир) и производите (шампон, крема) се оданочуваат со 18%. Ако сте паушалец под прагот — нема ДДВ обврска, но мора да издавате фискална сметка.' },
        { question: 'Како функционираат провизии за вработени во салон?', answer: 'Најчест модел е фиксна плата + провизија (10-30% од остварен промет). Целата провизија е дел од бруто платата — придонеси 28% и ПДД 10% се пресметуваат на целиот износ. Минимална бруто плата 2026 е 38.507 МКД — не можете да платите помалку.' },
        { question: 'Дали фризерски салон треба фискален уред?', answer: 'Да, секој салон кој продава услуги или производи на физички лица (B2C) е обврзан да користи фискален уред и да издава фискални сметки. Казните за работа без фискален уред се од 2.000 до 5.000 EUR за правно лице. Фискалниот уред мора да биде поврзан со УЈП.' },
      ])) }} />

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
