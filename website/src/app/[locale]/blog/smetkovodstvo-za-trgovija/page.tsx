import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-trgovija', {
    title: {
      mk: 'Сметководство за трговија: Залихи, маржа и фискален печатач',
      en: 'Retail Accounting Macedonia: Inventory, Margins & Fiscal Printer',
      sq: 'Kontabiliteti për tregti: Stoqe, marzhi dhe printer fiskal',
      tr: 'Perakende Muhasebe Makedonya: Stok, Marj ve Fişkal Yazıcı',
    },
    description: {
      mk: 'Комплетен водич за сметководство во трговија: WAC и FIFO методи, маржа и калкулација, фискална фискализација, КАП, нивелација, преносница, сезонски залихи и добавувачи.',
      en: 'Complete guide to retail accounting in North Macedonia: WAC and FIFO methods, margin calculation, fiscal printer compliance, trade documents (KAP, price adjustment, transfer), seasonal stock and supplier management.',
      sq: 'Udhezues i plote per kontabilitetin ne tregti: metodat WAC dhe FIFO, llogaritja e marzhit, fiskalizimi, dokumentet tregtare (KAP, nivelacion, transferim), stoqe sezonale dhe furnizues.',
      tr: 'Kuzey Makedonya perakende muhasebesi rehberi: WAC ve FIFO yontemleri, marj hesaplama, fiskal yazici uyumu, ticaret belgeleri (KAP, fiyat ayarlama, transfer), mevsimlik stok ve tedarikci yonetimi.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за трговија: Залихи, маржа и фискален печатач',
    publishDate: '23 мај 2026',
    readTime: '12 мин читање',
    intro:
      'Трговијата — и малопродажна и големопродажна — е основата на македонската економија. Но правилно водење на залихи, пресметка на маржа, фискална усогласеност и трговски документи е предизвик дури и за искусни сопственици. Овој водич покрива сè: од WAC калкулација до КАП и нивелација, од фискален печатач до сезонски залихи и добавувачи.',
    sections: [
      {
        title: 'Управување со залихи: WAC и FIFO методи',
        content:
          'Правилната евиденција на залихите е темелот на трговското сметководство. Два методи се најчести во Македонија:',
        items: [
          'WAC (Weighted Average Cost / Пондериран просечен трошок) — по секоја набавка, просечната цена се пресметува повторно. Пример: имате 100 парчиња по 50 МКД и купувате уште 200 по 60 МКД → нова WAC = (100×50 + 200×60) / 300 = 56,67 МКД',
          'FIFO (First In, First Out / Прв влезен, прв излезен) — стоката што прва влегла прва се продава. Погоден за лесно расиплива стока (храна, козметика)',
          'Физичка инвентура vs книговодствена — задолжителна годишна физичка инвентура (Чл. 17 ЗТД). Разликата = кало, растур, кршење или кражба',
          'Кало и растур (природна загуба) — дозволен процент на загуба зависи од видот на стоката. Пр: мелени производи 0,5%, свежо овошје 3%, замрзнато месо 1%',
          'Нормативи за кало — правилник на компанијата определува дозволен % за секоја категорија. Загуба над нормативот = оданочив расход',
          'Залиха на минимум/максимум — поставете минимално ниво за автоматско нарачување и максимално за да избегнете прекумерно складирање',
          'Баркод систем — секој артикл со единствен баркод за точна евиденција на влез, излез и моментална залиха',
        ],
        steps: null,
      },
      {
        title: 'Маржа и калкулација: Трговска маржа во МКД',
        content:
          'Разбирањето на маржата е клучно за профитабилна трговија. Во Македонија се користат два термина кои често се мешаат:',
        items: null,
        steps: [
          { step: 'Маржа (Margin)', desc: 'Процент од продажната цена. Формула: Маржа = (Продажна цена − Набавна цена) / Продажна цена × 100. Пример: набавна 600 МКД, продажна 1.000 МКД → маржа = 40%.' },
          { step: 'Наценка (Markup)', desc: 'Процент на набавната цена. Формула: Наценка = (Продажна цена − Набавна цена) / Набавна цена × 100. Ист пример: наценка = 66,7%. ВАЖНО: 40% маржа ≠ 40% наценка!' },
          { step: 'Калкулација со ДДВ', desc: 'Набавна цена без ДДВ + наценка = малопродажна цена без ДДВ. Потоа се додава ДДВ 18%. Пример: набавна 847 МКД (без ДДВ) + 40% наценка = 1.186 МКД + 18% ДДВ = 1.399 МКД малопродажна.' },
          { step: 'PLT маржа (Преглед на листа на трговска маржа)', desc: 'Задолжителен документ кој ја покажува набавната цена, наценката и продажната цена за секој артикл. Основа за контрола на профитабилноста.' },
          { step: 'Мешовита маржа по категории', desc: 'Различни категории имаат различна маржа: прехрана 15-25%, облека 50-100%, електроника 10-20%, козметика 40-80%. Следете ја маржата по категорија, не само вкупно.' },
        ],
      },
      {
        title: 'Фискален печатач: Закон, уреди и казни',
        content:
          'Секој трговец кој продава на физички лица (B2C) е обврзан да користи фискален уред. Законот за фискализација е строг:',
        items: [
          'Закон за регистрирање на готовински плаќања — секоја B2C продажба мора да биде регистрирана на фискален уред, без исклучок',
          '9 одобрени модели на фискални уреди во Македонија — листа на УЈП се ажурира годишно. Проверете ја пред купување',
          'Z-извештај (дневен извештај) — задолжителен на крајот на секој работен ден. Мора да се чува минимум 5 години',
          'Периодичен извештај — месечен збирен извештај од фискалниот уред. Се поднесува автоматски до серверот на УЈП',
          'Казни за неусогласеност: EUR 2.000-5.000 за правно лице, EUR 500-1.500 за одговорно лице. При повторен прекршок — двојна казна',
          'Сервисен преглед — годишен задолжителен сервис на фискалниот уред од овластен сервисер. Без сервис = неисправен уред = казна',
          'Фискална сметка мора да содржи: назив на фирмата, ЕДБ, датум и час, назив и количина на стоката, единечна и вкупна цена, ДДВ стапка и износ, фискален број',
        ],
        steps: null,
      },
      {
        title: 'Големопродажни документи: КАП, Нивелација, Преносница',
        content:
          'Трговијата на големо и мало бара специфични документи кои многу трговци ги занемаруваат:',
        items: null,
        steps: [
          { step: 'КАП (Калкулација)', desc: 'Документ кој ја пресметува малопродажната цена од набавната. Содржи: набавна цена без ДДВ, зависни трошоци (транспорт, царина), наценка, ДДВ, и крајна малопродажна цена. Задолжителен за секоја примена стока.' },
          { step: 'Нивелација (Price Adjustment)', desc: 'Кога ја менувате продажната цена — нагоре или надолу — мора да се издаде нивелација. Ги евидентира: стара цена, нова цена, разлика во вредност, причина. Нивелација нагоре = приход, надолу = расход.' },
          { step: 'Преносница (Transfer Note)', desc: 'При пренос на стока помеѓу две продавници/магацини на истата фирма. Не е продажба — нема ДДВ. Содржи: од каде, до каде, количина, вредност. Двата магацина мора да ги ажурираат залихите.' },
          { step: 'PLT маржа (Trade Margin List)', desc: 'Преглед на сите артикли со набавна цена, наценка/маржа и продажна цена. Се користи за анализа на профитабилност и за подготовка на попис.' },
          { step: 'Примка (Goods Receipt Note)', desc: 'Документ за прием на стока од добавувач. Ја потврдува количината и квалитетот на примената стока. Основа за книжење на залиха и обврски кон добавувач.' },
        ],
      },
      {
        title: 'Сезонски залихи и годишна проценка',
        content:
          'Управувањето со сезонски залихи е критично за трговци со облека, обувки, прехрана и земјоделски производи:',
        items: [
          'Зимска/летна колекција — навремено нарачување 2-3 месеци пред сезона. Задоцнето нарачување = загубен промет',
          'Распродажби (clearance sales) — попуст на залиха од минатата сезона. Евидентирајте ја нивелацијата надолу пред почеток на распродажба',
          'Годишна проценка на залихи (IAS 2) — залихите се вреднуваат по ПОНИСКАТА од: набавната цена (cost) или нето-продажната вредност (NRV). Ако пазарната цена паднала под набавната — обврска е да се направи отпис',
          'NRV (Net Realisable Value) = очекувана продажна цена − трошоци за довршување − трошоци за продажба. Пример: стока купена за 1.000 МКД, но може да се продаде само за 700 МКД → отпис од 300 МКД',
          'Застарена залиха — стока што не се продала повеќе од 12 месеци треба да се разгледа за отпис или донација',
          'Сезонска инвентура — препорачливо е да се направи физички попис на крајот на секоја сезона, не само годишно',
          'Даночен третман на отпис — отписот е признат расход за данок на добивка, но мора да биде поткрепен со записник и комисија',
        ],
        steps: null,
      },
      {
        title: 'Добавувачи: Плаќања, рокови и ИОС',
        content:
          'Правилното управување со добавувачите е клучно за ликвидноста и профитабилноста на трговската фирма:',
        items: null,
        steps: [
          { step: 'Рок на плаќање (payment terms)', desc: 'Стандарден рок во Македонија е 30-60 дена. Закон за финансиска дисциплина: максимум 60 дена за B2B (Чл. 5). Казни за доцнење: камата од 10% годишно.' },
          { step: 'Каса сконто (early payment discount)', desc: 'Попуст за рано плаќање, типично 2-3% ако се плати во 10-15 дена. Пример: 2/10 net 30 = 2% попуст ако платите во 10 дена, иначе полн износ во 30. Ова е ефективно 36% годишен поврат — секогаш искористете го!' },
          { step: 'ИОС (Извод на отворени ставки)', desc: 'Квартален документ помеѓу купувач и добавувач кој ги усогласува неплатените фактури. Законска обврска — мора да се размени минимум на 30.06 и 31.12. Несовпаѓање = ризик од ревизија.' },
          { step: 'Компензација (Offset)', desc: 'Ако и вие должите на добавувачот, и тој ви должи вам — можете да направите компензација (пребивање). Документ: Записник за компензација со потписи на двете страни.' },
          { step: 'Аванс на добавувач', desc: 'Авансно плаќање пред прием на стока. Евидентирајте го како побарување (класа 1), не како расход. По прием на стока — затворете го авансот со фактурата.' },
        ],
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino е дизајниран за македонски трговци — со сите алатки за залихи, маржа и фискална усогласеност:',
        items: [
          'POS режим за малопродажба: Брза продажба со баркод скенер, картичка и готовина, автоматски фискален печатач',
          'Автоматска WAC калкулација: По секоја набавка, системот автоматски ја пресметува новата просечна цена',
          'Интеграција со 9 фискални печатачи одобрени во Македонија — Z-извештај, периодичен извештај, автоматско праќање до УЈП',
          'Трговски документи: КАП, нивелација, преносница и PLT маржа — генерирање со еден клик',
          'Залихи по локации: Следете ги залихите во повеќе продавници/магацини, со автоматска преносница при трансфер',
          'ИОС модул: Автоматско генерирање на ИОС за добавувачи и клиенти, со усогласување на отворени ставки',
          'Извештаи за маржа: По артикл, категорија и период — знајте точно каде заработувате и каде губите',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'POS софтвер за Македонија: Споредба' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија' },
      { slug: 'nabavki-i-narachki', title: 'Набавки и нарачки: Водич' },
      { slug: 'najdobar-pos-softver-2026', title: 'Најдобар POS софтвер 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Фискален печатач: Chrome интеграција' },
    ],
    bottomCta: {
      title: 'Залихи, маржа и фискален печатач — сè во Facturino.',
      subtitle: 'WAC калкулација, КАП, нивелација и интеграција со фискален уред. Бесплатен план за трговци.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Retail Accounting Macedonia: Inventory, Margins & Fiscal Printer',
    publishDate: 'May 23, 2026',
    readTime: '12 min read',
    intro:
      'Retail and wholesale trade is the backbone of the Macedonian economy. But proper inventory management, margin calculation, fiscal compliance, and trade documents remain challenging even for experienced owners. This guide covers everything: from WAC calculation to trade documents (KAP, price adjustment, transfer), from fiscal printer compliance to seasonal stock and supplier management.',
    sections: [
      {
        title: 'Inventory Management: WAC and FIFO Methods',
        content:
          'Proper inventory tracking is the foundation of trade accounting. Two methods are most common in North Macedonia:',
        items: [
          'WAC (Weighted Average Cost) — after each purchase, the average cost is recalculated. Example: you have 100 units at 50 MKD and buy 200 more at 60 MKD → new WAC = (100x50 + 200x60) / 300 = 56.67 MKD',
          'FIFO (First In, First Out) — goods that arrived first are sold first. Ideal for perishable goods (food, cosmetics)',
          'Physical count vs book inventory — annual physical inventory is mandatory (Art. 17 Trade Act). Difference = shrinkage, spoilage, breakage, or theft',
          'Shrinkage and natural loss — the allowed loss percentage depends on the type of goods. E.g.: ground products 0.5%, fresh fruit 3%, frozen meat 1%',
          'Loss norms — the company\'s internal policy defines the allowed % per category. Loss exceeding the norm = taxable expense',
          'Min/max stock levels — set minimum levels for automatic reordering and maximum levels to avoid overstocking',
          'Barcode system — every item with a unique barcode for accurate tracking of inflows, outflows, and current stock',
        ],
        steps: null,
      },
      {
        title: 'Margin Calculation: Trade Margin in MKD',
        content:
          'Understanding margin is crucial for profitable trade. In North Macedonia, two terms are often confused:',
        items: null,
        steps: [
          { step: 'Margin', desc: 'Percentage of the selling price. Formula: Margin = (Selling Price - Cost Price) / Selling Price x 100. Example: cost 600 MKD, selling 1,000 MKD → margin = 40%.' },
          { step: 'Markup', desc: 'Percentage on cost price. Formula: Markup = (Selling Price - Cost Price) / Cost Price x 100. Same example: markup = 66.7%. IMPORTANT: 40% margin ≠ 40% markup!' },
          { step: 'Calculation with VAT', desc: 'Cost price (ex-VAT) + markup = retail price (ex-VAT). Then add 18% VAT. Example: cost 847 MKD (ex-VAT) + 40% markup = 1,186 MKD + 18% VAT = 1,399 MKD retail.' },
          { step: 'PLT Margin (Trade Margin List)', desc: 'A mandatory document showing the cost price, markup, and selling price for each item. Essential for profitability control.' },
          { step: 'Blended margin by category', desc: 'Different categories have different margins: food 15-25%, clothing 50-100%, electronics 10-20%, cosmetics 40-80%. Track margin by category, not just overall.' },
        ],
      },
      {
        title: 'Fiscal Printer: Law, Devices, and Penalties',
        content:
          'Every retailer selling to individuals (B2C) is required to use a fiscal device. The Fiscalization Act is strict:',
        items: [
          'Cash Payment Registration Act — every B2C sale must be registered on a fiscal device, no exceptions',
          '9 approved fiscal device models in North Macedonia — the UJP list is updated annually. Check before purchasing',
          'Z-report (daily report) — mandatory at the end of each business day. Must be kept for a minimum of 5 years',
          'Periodic report — monthly summary report from the fiscal device. Submitted automatically to the UJP server',
          'Non-compliance penalties: EUR 2,000-5,000 for the legal entity, EUR 500-1,500 for the responsible person. Repeat offense — double penalty',
          'Annual service — mandatory annual service of the fiscal device by an authorized service provider. No service = defective device = penalty',
          'Fiscal receipt must contain: company name, tax ID (EDB), date and time, item name and quantity, unit and total price, VAT rate and amount, fiscal number',
        ],
        steps: null,
      },
      {
        title: 'Wholesale Trade Documents: KAP, Price Adjustment, Transfer Note',
        content:
          'Wholesale and retail trade requires specific documents that many traders neglect:',
        items: null,
        steps: [
          { step: 'KAP (Calculation Sheet)', desc: 'A document that calculates the retail price from the cost. Contains: cost price (ex-VAT), dependent costs (transport, customs), markup, VAT, and final retail price. Mandatory for every goods receipt.' },
          { step: 'Price Adjustment (Nivelacija)', desc: 'When you change the selling price — up or down — a price adjustment document must be issued. Records: old price, new price, value difference, reason. Upward adjustment = revenue, downward = expense.' },
          { step: 'Transfer Note (Prenosnica)', desc: 'For transferring goods between two stores/warehouses of the same company. Not a sale — no VAT. Contains: from where, to where, quantity, value. Both locations must update their stock.' },
          { step: 'PLT Margin (Trade Margin List)', desc: 'Overview of all items with cost price, markup/margin, and selling price. Used for profitability analysis and stocktake preparation.' },
          { step: 'Goods Receipt Note (Primka)', desc: 'Document for receiving goods from a supplier. Confirms the quantity and quality of received goods. Basis for recording stock and liabilities to the supplier.' },
        ],
      },
      {
        title: 'Seasonal Stock and Year-End Valuation',
        content:
          'Managing seasonal inventory is critical for retailers in clothing, footwear, food, and agricultural products:',
        items: [
          'Winter/summer collections — order 2-3 months before the season. Late ordering = lost revenue',
          'Clearance sales — discounts on last season\'s stock. Record the downward price adjustment before starting the sale',
          'Year-end stock valuation (IAS 2) — inventory is valued at the LOWER of: cost or net realisable value (NRV). If the market price has fallen below cost — a write-down is mandatory',
          'NRV (Net Realisable Value) = expected selling price - costs to complete - costs to sell. Example: goods purchased for 1,000 MKD but can only sell for 700 MKD → write-down of 300 MKD',
          'Obsolete stock — goods unsold for more than 12 months should be reviewed for write-off or donation',
          'Seasonal stocktake — it is recommended to do a physical count at the end of each season, not just annually',
          'Tax treatment of write-offs — the write-off is a recognized expense for corporate tax, but must be supported by a protocol and commission',
        ],
        steps: null,
      },
      {
        title: 'Supplier Payments, Terms, and IOS',
        content:
          'Proper supplier management is key to the liquidity and profitability of a trading company:',
        items: null,
        steps: [
          { step: 'Payment terms', desc: 'Standard terms in North Macedonia are 30-60 days. Financial Discipline Act: maximum 60 days for B2B (Art. 5). Late payment penalty: 10% annual interest.' },
          { step: 'Cash discount (early payment)', desc: 'Discount for early payment, typically 2-3% if paid within 10-15 days. Example: 2/10 net 30 = 2% discount if paid in 10 days, otherwise full amount in 30. This is effectively 36% annual return — always take it!' },
          { step: 'IOS (Open Items Statement)', desc: 'A quarterly document between buyer and supplier reconciling unpaid invoices. Legal obligation — must be exchanged at minimum on 30 June and 31 December. Discrepancy = audit risk.' },
          { step: 'Offset (Compensation)', desc: 'If you owe the supplier and the supplier owes you — you can offset (net off). Document: Offset protocol signed by both parties.' },
          { step: 'Supplier advance', desc: 'Advance payment before goods receipt. Record as a receivable (class 1), not an expense. After goods receipt — close the advance against the invoice.' },
        ],
      },
      {
        title: 'How Facturino Helps',
        content:
          'Facturino is designed for Macedonian retailers — with all the tools for inventory, margins, and fiscal compliance:',
        items: [
          'POS mode for retail: Fast sales with barcode scanner, card and cash, automatic fiscal printer',
          'Automatic WAC calculation: After each purchase, the system automatically recalculates the new average cost',
          'Integration with 9 fiscal printers approved in North Macedonia — Z-report, periodic report, automatic submission to UJP',
          'Trade documents: KAP, price adjustment, transfer note, and PLT margin — generate with one click',
          'Multi-location inventory: Track stock across multiple stores/warehouses with automatic transfer notes',
          'IOS module: Automatic generation of open items statements for suppliers and customers, with reconciliation',
          'Margin reports: By item, category, and period — know exactly where you earn and where you lose',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'POS Software for North Macedonia: Comparison' },
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for North Macedonia' },
      { slug: 'nabavki-i-narachki', title: 'Procurement and Orders: Guide' },
      { slug: 'najdobar-pos-softver-2026', title: 'Best POS Software 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Fiscal Printer: Chrome Integration' },
    ],
    bottomCta: {
      title: 'Inventory, margins & fiscal printer — all in Facturino.',
      subtitle: 'WAC calculation, KAP, price adjustment, and fiscal device integration. Free plan for retailers.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti per tregti: Stoqe, marzhi dhe printer fiskal',
    publishDate: '23 maj 2026',
    readTime: '12 min lexim',
    intro:
      'Tregtia — si me pakice ashtu edhe me shumice — eshte shtylla kurrizore e ekonomise maqedonase. Por menaxhimi i duhur i stoqeve, llogaritja e marzhit, perputhja fiskale dhe dokumentet tregtare mbeten sfiduese edhe per pronare me pervoje. Ky udhezues mbulon gjithcka: nga llogaritja WAC deri te dokumentet tregtare (KAP, nivelacion, transferim), nga printeri fiskal deri te stoqet sezonale dhe menaxhimi i furnizuesve.',
    sections: [
      {
        title: 'Menaxhimi i stoqeve: Metodat WAC dhe FIFO',
        content:
          'Gjurmimi i duhur i stoqeve eshte themeli i kontabilitetit tregtar. Dy metoda jane me te zakonshme ne Maqedoni:',
        items: [
          'WAC (Weighted Average Cost / Kosto mesatare e ponderuar) — pas cdo blerje, kostoja mesatare rillogaritet. Shembull: keni 100 copa me 50 MKD dhe blini edhe 200 me 60 MKD → WAC e re = (100x50 + 200x60) / 300 = 56,67 MKD',
          'FIFO (First In, First Out / I pari hyri, i pari doli) — malli qe hyri i pari shitet i pari. Ideal per mallra qe prishen lehte (ushqim, kozmetike)',
          'Numrimi fizik vs stoqet kontabel — inventari fizik vjetor eshte i detyrueshem (Neni 17 Ligji per Tregti). Dallimi = humbje, prishje, thyerje ose vjedhje',
          'Kalo dhe humbje natyrore — perqindja e lejuar e humbjes varet nga lloji i mallit. Psh: produkte te bluara 0,5%, fruta te fresketa 3%, mish i ngrire 1%',
          'Normat e humbjes — politika e brendshme e kompanise percakton perqindjen e lejuar per cdo kategori. Humbja mbi normen = shpenzim i tatueshem',
          'Nivelet min/maks te stoqeve — vendosni nivelin minimal per porosi automatike dhe maksimalin per te shmangur mbistokimin',
          'Sistemi i barkodit — cdo artikull me barkod unik per gjurmim te sakte te hyrjeve, daljeve dhe stoqeve momentale',
        ],
        steps: null,
      },
      {
        title: 'Llogaritja e marzhit: Marzhi tregtar ne MKD',
        content:
          'Kuptimi i marzhit eshte thelbsor per tregti profitabile. Ne Maqedoni, dy terma shpesh ngatrrohen:',
        items: null,
        steps: [
          { step: 'Marzhi (Margin)', desc: 'Perqindje e cmimit te shitjes. Formula: Marzhi = (Cmimi i shitjes - Cmimi i blerjes) / Cmimi i shitjes x 100. Shembull: blerje 600 MKD, shitje 1.000 MKD → marzhi = 40%.' },
          { step: 'Mbicmimi (Markup)', desc: 'Perqindje mbi cmimin e blerjes. Formula: Mbicmimi = (Cmimi i shitjes - Cmimi i blerjes) / Cmimi i blerjes x 100. Shembulli i njejte: mbicmimi = 66,7%. E RENDESISHME: 40% marzh ≠ 40% mbicmim!' },
          { step: 'Llogaritja me TVSH', desc: 'Cmimi i blerjes (pa TVSH) + mbicmimi = cmimi i pakices (pa TVSH). Pastaj shtohet TVSH 18%. Shembull: blerje 847 MKD (pa TVSH) + 40% mbicmim = 1.186 MKD + 18% TVSH = 1.399 MKD pakice.' },
          { step: 'PLT marzhi (Lista e marzhit tregtar)', desc: 'Dokument i detyrueshem qe tregon cmimin e blerjes, mbicmimin dhe cmimin e shitjes per cdo artikull. Baze per kontrollin e profitabilitetit.' },
          { step: 'Marzhi i perzier sipas kategorive', desc: 'Kategori te ndryshme kane marzhe te ndryshme: ushqim 15-25%, veshje 50-100%, elektronike 10-20%, kozmetike 40-80%. Ndiqni marzhin sipas kategorise, jo vetem ne total.' },
        ],
      },
      {
        title: 'Printeri fiskal: Ligji, pajisjet dhe gjobat',
        content:
          'Cdo tregtar qe shet te individet (B2C) eshte i detyruar te perdore pajisje fiskale. Ligji per fiskalizim eshte i rrept:',
        items: [
          'Ligji per regjistrimin e pagesave me para — cdo shitje B2C duhet te regjistrohet ne pajisje fiskale, pa perjashtim',
          '9 modele te miratuara te pajisjeve fiskale ne Maqedoni — lista e DAP-it perditsohet cdo vit. Kontrolloni para blerjes',
          'Z-raporti (raporti ditor) — i detyrueshem ne fund te cdo dite pune. Duhet te ruhet minimumi 5 vjet',
          'Raporti periodik — raporti permbledhes mujor nga pajisja fiskale. Dergohet automatikisht ne serverin e DAP-it',
          'Gjobat per mosperputhje: EUR 2.000-5.000 per subjektin juridik, EUR 500-1.500 per personin pergjegjes. Shkelje e perseritur — gjobe e dyfishte',
          'Sherbimi vjetor — sherbim i detyrueshem vjetor i pajisjes fiskale nga sherbyes i autorizuar. Pa sherbim = pajisje e prishur = gjobe',
          'Fatura fiskale duhet te permbaje: emrin e kompanise, NUI, daten dhe oren, emrin dhe sasine e mallit, cmimin per njesi dhe total, normen dhe shumen e TVSH, numrin fiskal',
        ],
        steps: null,
      },
      {
        title: 'Dokumentet tregtare: KAP, Nivelacion, Transferim',
        content:
          'Tregtia me shumice dhe pakice kerkon dokumente specifike qe shume tregtare i neglizhojne:',
        items: null,
        steps: [
          { step: 'KAP (Fleta e llogaritjes)', desc: 'Dokument qe llogarit cmimin e pakices nga kostoja. Permban: cmimin e blerjes (pa TVSH), shpenzimet e varura (transport, dogane), mbicmimin, TVSH, dhe cmimin perfundimtar te pakices. I detyrueshem per cdo pranim malli.' },
          { step: 'Nivelacioni (Rregullimi i cmimeve)', desc: 'Kur ndryshoni cmimin e shitjes — lart ose posht — duhet te leshohet dokument nivelacioni. Regjistron: cmimin e vjeter, cmimin e ri, diferencen ne vlere, arsyen. Rregullim lart = te ardhur, posht = shpenzim.' },
          { step: 'Transferimi (Nota e transferimit)', desc: 'Per transferimin e mallrave midis dy dyqaneve/magazinave te se njejtes kompani. Nuk eshte shitje — pa TVSH. Permban: nga ku, ku, sasine, vleren. Te dy lokacionet duhet te perditesojne stoqet.' },
          { step: 'PLT marzhi (Lista e marzhit tregtar)', desc: 'Permbledhje e te gjitha artikujve me cmimin e blerjes, mbicmimin/marzhin dhe cmimin e shitjes. Perdoret per analizen e profitabilitetit dhe pergatitjen e inventarit.' },
          { step: 'Primka (Nota e pranimit te mallit)', desc: 'Dokument per pranimin e mallit nga furnizuesi. Konfirmon sasine dhe cilesine e mallit te pranuar. Baze per regjistrimin e stoqeve dhe detyrimeve ndaj furnizuesit.' },
        ],
      },
      {
        title: 'Stoqet sezonale dhe vleresimi ne fund te vitit',
        content:
          'Menaxhimi i stoqeve sezonale eshte kritik per tregtaret e veshjeve, kepuceve, ushqimit dhe produkteve bujqesore:',
        items: [
          'Koleksionet dimer/vere — porosisni 2-3 muaj para sezonit. Porosi e vonuar = te ardhura te humbura',
          'Shitje me zbritje (clearance) — zbritje ne stoqen e sezonit te kaluar. Regjistroni nivelacionin posht para fillimit te shitjes',
          'Vleresimi vjetor i stoqeve (IAS 2) — stoqet vleresohen me te ULETEN nga: kostoja ose vlera neto e realizueshme (NRV). Nese cmimi i tregut ka rene nen koston — fshirja eshte e detyrueshme',
          'NRV (Vlera Neto e Realizueshme) = cmimi i pritur i shitjes - shpenzimet per perfundim - shpenzimet per shitje. Shembull: mall i blere per 1.000 MKD por mund te shitet vetem per 700 MKD → fshirje 300 MKD',
          'Stoqe e vjeteruar — malli i pashitur per me shume se 12 muaj duhet te rishikohet per fshirje ose dhurimi',
          'Inventari sezonal — rekomandohet numrim fizik ne fund te cdo sezoni, jo vetem ne baze vjetore',
          'Trajtimi tatimor i fshirjes — fshirja eshte shpenzim i njohur per tatimin mbi fitimin, por duhet te mbeshtetet me procesverbal dhe komision',
        ],
        steps: null,
      },
      {
        title: 'Pagesat e furnizuesve, afatet dhe IOS',
        content:
          'Menaxhimi i duhur i furnizuesve eshte celesi i likuiditetit dhe profitabilitetit te nje kompanie tregtare:',
        items: null,
        steps: [
          { step: 'Afatet e pageses', desc: 'Afatet standarde ne Maqedoni jane 30-60 dite. Ligji per disipline financiare: maksimum 60 dite per B2B (Neni 5). Gjoba per vonese: kamate 10% vjetore.' },
          { step: 'Skonto (zbritje per pagese te hershme)', desc: 'Zbritje per pagese te hershme, zakonisht 2-3% nese paguhet ne 10-15 dite. Shembull: 2/10 neto 30 = 2% zbritje nese paguani ne 10 dite, perndryshe shuma e plote ne 30. Ky eshte efektivisht 36% kthim vjetor — perdoreni gjithmone!' },
          { step: 'IOS (Pasqyra e stavkave te hapura)', desc: 'Dokument tremujor midis bleresit dhe furnizuesit qe perputhet faturat e papaguara. Detyrim ligjor — duhet te shkembehet minimumi me 30 qershor dhe 31 dhjetor. Mosperputhje = rrezik auditimi.' },
          { step: 'Kompensimi (Offset)', desc: 'Nese ju i keni borxh furnizuesit dhe furnizuesi ju ka borxh juve — mundeni te beni kompensim. Dokument: Procesverbal kompensimi me nenshkrime te te dyja paleve.' },
          { step: 'Paradhenie per furnizuesin', desc: 'Pagese paraprake para pranimit te mallit. Regjistroni si kerkim (klasa 1), jo si shpenzim. Pas pranimit te mallit — mbyllni paradheniein me faturen.' },
        ],
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino eshte dizajnuar per tregtaret maqedonas — me te gjitha mjetet per stoqe, marzhe dhe perputhje fiskale:',
        items: [
          'Regjimi POS per pakice: Shitje e shpejte me skaner barkodi, karte dhe para, printer fiskal automatik',
          'Llogaritja automatike WAC: Pas cdo blerje, sistemi automatikisht rillogarit koston mesatare te re',
          'Integrim me 9 printerat fiskale te miratuara ne Maqedoni — Z-raport, raport periodik, dergim automatik te DAP',
          'Dokumentet tregtare: KAP, nivelacion, transferim dhe PLT marzhi — gjenerim me nje klik',
          'Stoqe sipas lokacioneve: Ndiqni stoqet ne shume dyqane/magazina, me nota transferimi automatike',
          'Moduli IOS: Gjenerim automatik i pasqyrave te stavkave te hapura per furnizues dhe kliente, me perputhje',
          'Raporte marzhi: Sipas artikullit, kategorise dhe periudhes — dijeni saktesisht ku fitoni dhe ku humbni',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'Softuer POS per Maqedonine: Krahasim' },
      { slug: 'ddv-vodich-mk', title: 'Udhezues TVSH per Maqedonine' },
      { slug: 'nabavki-i-narachki', title: 'Prokurime dhe porosi: Udhezues' },
      { slug: 'najdobar-pos-softver-2026', title: 'Softueri me i mire POS 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Printeri fiskal: Integrimi Chrome' },
    ],
    bottomCta: {
      title: 'Stoqe, marzhe dhe printer fiskal — te gjitha ne Facturino.',
      subtitle: 'Llogaritja WAC, KAP, nivelacion dhe integrim me pajisje fiskale. Plan falas per tregtare.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Sektor',
    title: 'Perakende Muhasebe Makedonya: Stok, Marj ve Fiskal Yazici',
    publishDate: '23 Mayis 2026',
    readTime: '12 dk okuma',
    intro:
      'Perakende ve toptan ticaret, Makedonya ekonomisinin bel kemigidir. Ancak dogru stok yonetimi, marj hesaplama, fiskal uyum ve ticaret belgeleri deneyimli isletme sahipleri icin bile zorlayici olmaya devam etmektedir. Bu rehber her seyi kapsar: WAC hesaplamasindan ticaret belgelerine (KAP, fiyat ayarlama, transfer), fiskal yazicidan mevsimlik stok ve tedarikci yonetimine.',
    sections: [
      {
        title: 'Stok Yonetimi: WAC ve FIFO Yontemleri',
        content:
          'Dogru stok takibi, ticaret muhasebesinin temelidir. Kuzey Makedonya\'da iki yontem en yaygindir:',
        items: [
          'WAC (Agirlikli Ortalama Maliyet) — her alisveristen sonra ortalama maliyet yeniden hesaplanir. Ornek: 50 MKD\'den 100 biriminiz var ve 60 MKD\'den 200 birim daha aliyorsunuz → yeni WAC = (100x50 + 200x60) / 300 = 56,67 MKD',
          'FIFO (Ilk Giren, Ilk Cikan) — once gelen mallar once satilir. Bozulabilir mallar icin ideal (gida, kozmetik)',
          'Fiziksel sayim vs defter stoku — yillik fiziksel envanter zorunludur (Ticaret Kanunu Md. 17). Fark = fire, bozulma, kirilma veya hirsizlik',
          'Fire ve dogal kayip — izin verilen kayip yuzdesi malin turune bagli. Orn: ogutulmus urunler %0,5, taze meyve %3, dondurulmus et %1',
          'Kayip normlari — sirketin ic politikasi her kategori icin izin verilen %\'yi belirler. Normu asan kayip = vergiye tabi gider',
          'Min/maks stok seviyeleri — otomatik siparis icin minimum ve asiri stogu onlemek icin maksimum seviye belirleyin',
          'Barkod sistemi — her urun icin benzersiz barkod ile giris, cikis ve anlik stok takibi',
        ],
        steps: null,
      },
      {
        title: 'Marj Hesaplama: MKD\'de Ticaret Marji',
        content:
          'Marji anlamak karli ticaret icin kritiktir. Kuzey Makedonya\'da sik karistirilan iki terim vardir:',
        items: null,
        steps: [
          { step: 'Marj (Margin)', desc: 'Satis fiyatinin yuzdesi. Formul: Marj = (Satis Fiyati - Alis Fiyati) / Satis Fiyati x 100. Ornek: alis 600 MKD, satis 1.000 MKD → marj = %40.' },
          { step: 'Kar Marji (Markup)', desc: 'Alis fiyati uzerine yuzde. Formul: Kar Marji = (Satis Fiyati - Alis Fiyati) / Alis Fiyati x 100. Ayni ornek: kar marji = %66,7. ONEMLI: %40 marj ≠ %40 kar marji!' },
          { step: 'KDV ile hesaplama', desc: 'Alis fiyati (KDV haric) + kar marji = perakende fiyat (KDV haric). Sonra %18 KDV eklenir. Ornek: alis 847 MKD (KDV haric) + %40 kar marji = 1.186 MKD + %18 KDV = 1.399 MKD perakende.' },
          { step: 'PLT Marji (Ticaret Marj Listesi)', desc: 'Her urun icin alis fiyatini, kar marjini ve satis fiyatini gosteren zorunlu belge. Karlilik kontrolu icin temel.' },
          { step: 'Kategoriye gore karisik marj', desc: 'Farkli kategorilerin farkli marjlari vardir: gida %15-25, giyim %50-100, elektronik %10-20, kozmetik %40-80. Marji yalnizca toplamda degil, kategoriye gore takip edin.' },
        ],
      },
      {
        title: 'Fiskal Yazici: Kanun, Cihazlar ve Cezalar',
        content:
          'Bireylere (B2C) satan her perakendeci fiskal cihaz kullanmak zorundadir. Fiskalizasyon Kanunu katdir:',
        items: [
          'Nakit Odeme Kayit Kanunu — her B2C satisi fiskal cihazda kaydedilmelidir, istisnasiz',
          'Kuzey Makedonya\'da 9 onaylanmis fiskal cihaz modeli — UJP listesi yillik guncellenir. Satin almadan once kontrol edin',
          'Z-raporu (gunluk rapor) — her is gununun sonunda zorunlu. Minimum 5 yil saklanmalidir',
          'Periyodik rapor — fiskal cihazdan aylik ozet rapor. Otomatik olarak UJP sunucusuna gonderilir',
          'Uyumsuzluk cezalari: Tuzel kisi icin EUR 2.000-5.000, sorumlu kisi icin EUR 500-1.500. Tekrar eden ihlal — cift ceza',
          'Yillik servis — yetkili servis saglayici tarafindan fiskal cihazin zorunlu yillik servisi. Servis yok = arizali cihaz = ceza',
          'Fiskal fis icermeli: sirket adi, vergi numarasi (EDB), tarih ve saat, urun adi ve miktari, birim ve toplam fiyat, KDV orani ve tutari, fiskal numara',
        ],
        steps: null,
      },
      {
        title: 'Toptan Ticaret Belgeleri: KAP, Fiyat Ayarlama, Transfer Notu',
        content:
          'Toptan ve perakende ticaret, bircok tuccarin ihmal ettigi ozel belgeler gerektirir:',
        items: null,
        steps: [
          { step: 'KAP (Hesaplama Sayfasi)', desc: 'Perakende fiyati maliyetten hesaplayan belge. Icerir: alis fiyati (KDV haric), bagimli maliyetler (nakliye, gumruk), kar marji, KDV ve nihai perakende fiyat. Her mal girisi icin zorunludur.' },
          { step: 'Fiyat Ayarlama (Nivelacija)', desc: 'Satis fiyatini degistirdiginizde — yukari veya asagi — fiyat ayarlama belgesi duzenlenmeli. Kaydeder: eski fiyat, yeni fiyat, deger farki, neden. Yukari ayarlama = gelir, asagi = gider.' },
          { step: 'Transfer Notu (Prenosnica)', desc: 'Ayni sirketin iki magazasi/deposu arasinda mal transferi icin. Satis degil — KDV yok. Icerir: nereden, nereye, miktar, deger. Her iki konum stoklarini guncellemeli.' },
          { step: 'PLT Marji (Ticaret Marj Listesi)', desc: 'Tum urunlerin alis fiyati, kar marji ve satis fiyatiyla genel gorunumu. Karlilik analizi ve sayim hazirligi icin kullanilir.' },
          { step: 'Mal Kabul Notu (Primka)', desc: 'Tedarikciden mal kabulu icin belge. Alinan malin miktar ve kalitesini onaylar. Stok ve tedarikciye borcun kaydedilmesinin temeli.' },
        ],
      },
      {
        title: 'Mevsimlik Stok ve Yil Sonu Degerleme',
        content:
          'Mevsimlik stok yonetimi giyim, ayakkabi, gida ve tarim urunleri perakendecileri icin kritiktir:',
        items: [
          'Kis/yaz koleksiyonlari — sezondan 2-3 ay once siparis verin. Gec siparis = kayip gelir',
          'Sezon sonu satislari (clearance) — gecen sezon stogunda indirim. Satisa baslamadan once asagi fiyat ayarlamasini kaydedin',
          'Yil sonu stok degerleme (IAS 2) — stoklar DUSUK olanla degerlendirilir: maliyet veya net gerceklestirilebilir deger (NRV). Piyasa fiyati maliyetin altina dustuyse — deger dusurme zorunludur',
          'NRV (Net Gerceklestirilebilir Deger) = beklenen satis fiyati - tamamlama maliyetleri - satis maliyetleri. Ornek: 1.000 MKD\'ye alinan mal ama yalnizca 700 MKD\'ye satilabilir → 300 MKD deger dusurme',
          'Eskimis stok — 12 aydan fazla satilmamis mallar silme veya bagis icin gozden gecirilmeli',
          'Mevsimlik envanter — yalnizca yillik degil, her sezonun sonunda fiziksel sayim yapilmasi onerilir',
          'Silmelerin vergi muamelesi — silme kurumlar vergisi icin kabul edilen giderdir, ancak tutanak ve komisyonla desteklenmeli',
        ],
        steps: null,
      },
      {
        title: 'Tedarikci Odemeleri, Vadeler ve IOS',
        content:
          'Dogru tedarikci yonetimi bir ticaret sirketinin likiditesi ve karliligi icin anahtardir:',
        items: null,
        steps: [
          { step: 'Odeme vadeleri', desc: 'Kuzey Makedonya\'da standart vadeler 30-60 gundur. Mali Disiplin Kanunu: B2B icin maksimum 60 gun (Md. 5). Gec odeme cezasi: yillik %10 faiz.' },
          { step: 'Nakit iskonto (erken odeme)', desc: 'Erken odeme indirimi, genellikle 10-15 gun icinde odenirse %2-3. Ornek: 2/10 net 30 = 10 gunde oderseniz %2 indirim, aksi halde 30 gunde tam tutar. Bu etkin olarak yillik %36 getiri — her zaman yararlnin!' },
          { step: 'IOS (Acik Kalemler Tablosu)', desc: 'Alici ve tedarikci arasinda odenmemis faturalari uzlastiran uc aylik belge. Yasal zorunluluk — en az 30 Haziran ve 31 Aralik\'ta degistirilmeli. Uyumsuzluk = denetim riski.' },
          { step: 'Mahsup (Kompensasyon)', desc: 'Tedarikciye borcunuz varsa ve tedarikci de size borcluysa — mahsup yapabilirsiniz. Belge: Her iki tarafin imzaladigi mahsup protokolu.' },
          { step: 'Tedarikci avansi', desc: 'Mal kabulunden once on odeme. Gider olarak degil, alacak (sinif 1) olarak kaydedin. Mal kabulunden sonra — avansi faturayla kapatin.' },
        ],
      },
      {
        title: 'Facturino Nasil Yardimci Olur',
        content:
          'Facturino, Makedon perakendeciler icin tasarlanmistir — stok, marj ve fiskal uyum icin tum araclarla:',
        items: [
          'Perakende icin POS modu: Barkod tarayici, kart ve nakit ile hizli satis, otomatik fiskal yazici',
          'Otomatik WAC hesaplama: Her alimdan sonra sistem otomatik olarak yeni ortalama maliyeti hesaplar',
          'Kuzey Makedonya\'da onaylanmis 9 fiskal yaziciyla entegrasyon — Z-raporu, periyodik rapor, UJP\'ye otomatik gonderim',
          'Ticaret belgeleri: KAP, fiyat ayarlama, transfer notu ve PLT marji — tek tikla olusturma',
          'Coklu konum stoku: Otomatik transfer notlariyla birden fazla magaza/depoda stok takibi',
          'IOS modulu: Tedarikci ve musteriler icin acik kalem tablolarinin otomatik olusturulmasi ve uzlastirilmasi',
          'Marj raporlari: Urun, kategori ve doneme gore — tam olarak nerede kazandiginizi ve nerede kaybettiginizi bilin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili yazilar',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'Makedonya POS Yazilimi: Karsilastirma' },
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV Rehberi' },
      { slug: 'nabavki-i-narachki', title: 'Tedarik ve Siparisler: Rehber' },
      { slug: 'najdobar-pos-softver-2026', title: 'En Iyi POS Yazilimi 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Fiskal Yazici: Chrome Entegrasyonu' },
    ],
    bottomCta: {
      title: 'Stok, marj ve fiskal yazici — hepsi Facturino\'da.',
      subtitle: 'WAC hesaplama, KAP, fiyat ayarlama ve fiskal cihaz entegrasyonu. Perakendeciler icin ucretsiz plan.',
      cta: 'Ucretsiz baslayin →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaTrgovijaPage({
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
    slug: 'smetkovodstvo-za-trgovija',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['трговија', 'retail', 'залихи', 'inventory', 'маржа', 'margin', 'фискален', 'fiscal', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-trgovija` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Дали треба фискален уред за трговија?', answer: 'Да, секој трговец кој продава на физички лица (B2C) е обврзан да користи фискален уред. Казните за работа без фискален уред се од 2.000 до 5.000 EUR за правно лице.' },
        { question: 'Како да водам залиха во трговија?', answer: 'Најчесто се користат WAC (пондериран просечен трошок) и FIFO методи. WAC ја пресметува просечната цена по секоја набавка, додека FIFO ја продава најстарата залиха прва. Задолжителна е годишна физичка инвентура.' },
        { question: 'Како се пресметува маржа во трговија?', answer: 'Маржа = (Продажна цена − Набавна цена) / Продажна цена × 100. Наценка = (Продажна цена − Набавна цена) / Набавна цена × 100. Важно: 40% маржа не е исто што и 40% наценка.' },
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
