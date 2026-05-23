import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-transport', {
    title: {
      mk: 'Сметководство за транспорт: Гориво, патни налози и ДДВ',
      en: 'Transport & Logistics Accounting Macedonia: Fuel, Travel Orders & VAT',
      sq: 'Kontabiliteti për transport: Karburant, urdhra udhëtimi dhe TVSH',
      tr: 'Taşımacılık Muhasebesi Makedonya: Yakıt, Sefer Emirleri ve KDV',
    },
    description: {
      mk: 'Комплетен водич за сметководство во транспорт и логистика: следење гориво, меѓународен ДДВ 0%, патни налози, амортизација на возила, плати за возачи и патарини.',
      en: 'Complete guide to transport accounting in North Macedonia: fuel tracking, international 0% VAT, travel orders, vehicle depreciation, driver payroll and toll charges.',
      sq: 'Udhëzues i plotë për kontabilitetin e transportit në Maqedoni: ndjekja e karburantit, TVSH 0% ndërkombëtare, urdhra udhëtimi, amortizimi i automjeteve, pagat e shoferëve dhe taksat.',
      tr: 'Kuzey Makedonya\'da taşımacılık muhasebesi rehberi: yakıt takibi, uluslararası %0 KDV, sefer emirleri, araç amortismanı, şoför bordrosu ve geçiş ücretleri.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за транспорт: Гориво, патни налози и ДДВ',
    publishDate: '23 мај 2026',
    readTime: '12 мин читање',
    intro:
      'Транспортот и логистиката се еден од најсложените сектори за сметководство во Македонија. Дневно следење на гориво, меѓународен ДДВ со различни стапки, задолжителни патни налози за секое возење, амортизација на флота, специфични плати за возачи и безброј патарини и гранични такси — сè ова бара прецизно и навремено книговодство. Овој водич покрива сè што транспортните компании треба да знаат за правилно сметководство во 2026.',
    sections: [
      {
        title: 'Следење на трошоци за гориво',
        content:
          'Горивото е најголемиот трошок во транспортот — обично 30-40% од вкупните оперативни трошоци. Правилното евидентирање е клучно и за даночни цели и за контрола на профитабилноста:',
        items: [
          'Дневен дневник за гориво: секое полнење мора да се евидентира со датум, возило, километража, количина литри и цена по литар',
          'Одбивен ДДВ на гориво: ДДВ на гориво за деловни возила е целосно одбивен (18%). За мешана употреба (деловна + приватна) — само деловниот дел е одбивен',
          'Неодбивен ДДВ: Гориво за патнички возила кои не се регистрирани за транспорт — ДДВ НЕ е одбивен (Чл. 35 ЗДДВ)',
          'Флотни картички: Препорачливо е користење на флотни картички (Makpetrol, OKTA) — автоматско евидентирање, месечна збирна фактура, контрола по возач и возило',
          'Фискални сметки за гориво: Секоја сметка за гориво мора да содржи: назив на фирмата, ДДВ број, регистрација на возилото и количина',
          'Норма на потрошувачка: Секое возило треба да има утврдена норма (л/100км). Отстапување над 15% = знак за злоупотреба или дефект',
          'Гориво во странство: Полнење во странство — чувајте ги сметките, ДДВ се враќа преку VAT refund процедура (ако земјата дозволува)',
        ],
        steps: null,
      },
      {
        title: 'ДДВ за меѓународен транспорт',
        content:
          'Ова е најкомплексниот дел од транспортното сметководство. Правилата се строги и грешките водат до ревизија:',
        items: null,
        steps: [
          { step: 'Меѓународен транспорт = 0% ДДВ', desc: 'Транспорт на стока кој почнува или завршува надвор од Македонија се оданочува со 0% ДДВ (Чл. 24 ЗДДВ). Ова значи дека НЕ наплаќате ДДВ, но ИМАТЕ право на одбивен влезен ДДВ.' },
          { step: 'Домашен транспорт = 18% ДДВ', desc: 'Транспорт на стока внатре во Македонија се оданочува со стандардна стапка од 18%. Нема исклучоци за товарен транспорт.' },
          { step: 'CMR товарница е задолжителна', desc: 'За секој меѓународен транспорт мора да имате CMR документ (Convention Marchandises Routières). Без CMR — не можете да примените 0% стапка.' },
          { step: 'Влезен ДДВ на гориво во странство', desc: 'ДДВ платен на гориво во ЕУ земји може да се поврати преку 13-та Директива (VAT refund). Процедурата трае 6-12 месеци. Чувајте ги ОРИГИНАЛ фактурите.' },
          { step: 'Транзитен транспорт', desc: 'Стока што само транзитира низ Македонија (пр. Грција → Србија) — 0% ДДВ. Потребна е царинска документација за транзит (Т1/Т2).' },
        ],
      },
      {
        title: 'Патни налози — задолжителни за секое возење',
        content:
          'Патниот налог е најважниот документ во транспортната фирма. Без него, трошоците за гориво и дневници НЕ се признат расход:',
        items: [
          'Задолжителен за секое патување: Секое возење — домашно или меѓународно — мора да биде покриено со патен налог',
          'Содржина: Име на возачот, регистрација на возилото, релација (од-до), датум и час на поаѓање и пристигнување, цел на патувањето',
          'Домашни дневници: 1.700 МКД по ден (за патување над 8 часа). Ослободени од данок и придонеси',
          'Меѓународни дневници: Различни по земја — пр. Германија EUR 50, Србија EUR 35, Турција EUR 40, Грција EUR 45. Утврдени со Уредба на Владата',
          'Дневниците се неоданочиви: Дневниците до утврдениот лимит НЕ се оданочуваат. Над лимитот — се третираат како плата (10% ПДД + придонеси)',
          'Прилози кон налогот: Сметки за гориво, патарини, паркинг, хотел — сите се прилагаат кон патниот налог',
          'Рок за поднесување: Патниот налог мора да се заврши и достави до сметководство во рок од 5 работни дена по завршување на патувањето',
        ],
        steps: null,
      },
      {
        title: 'Амортизација на возила',
        content:
          'Возилата се најголемата основна средства во транспортната фирма. Правилната амортизација е клучна за даночната основа:',
        items: null,
        steps: [
          { step: 'Стандардна амортизација: 5 години (20% годишно)', desc: 'Камиони, товарни возила и комбиња се амортизираат по стапка од 20% годишно (линеарна метода). По 5 години — книговодствена вредност = 0, но возилото продолжува да се користи.' },
          { step: 'Одржување како тековен расход', desc: 'Редовно одржување (масло, гуми, филтри, кочници) е тековен расход — целосно одбивен во годината кога настанува. Не се капитализира.' },
          { step: 'Генерален ремонт = капитално подобрување', desc: 'Ако ремонтот го зголемува векот или капацитетот на возилото (нов мотор, надградба), тоа е капитално подобрување и се додава на набавната вредност.' },
          { step: 'Продажба на возило', desc: 'При продажба — разликата помеѓу продажна цена и книговодствена вредност е добивка (или загуба). Се оданочува со данок на добивка.' },
          { step: 'Лизинг vs купување', desc: 'Финансиски лизинг — возилото е на балансот, се амортизира. Оперативен лизинг — ратата е тековен расход, без амортизација.' },
        ],
      },
      {
        title: 'Плати за возачи — специфики',
        content:
          'Возачите имаат посебни права и додатоци кои значително ги зголемуваат трошоците за плати:',
        items: [
          'Ноќна работа (22:00-06:00): додаток 35% на основната плата (Чл. 106 ЗРО). Меѓународните возачи често возат ноќе',
          'Прекувремена работа: додаток 40% на основната плата. Максимум 8 часа неделно, 190 часа годишно',
          'Дневници — неоданочиви до лимит: Домашни 1.700 МКД/ден, меѓународни по земја. Над лимитот = плата со полни придонеси',
          'Задолжителни паузи за одмор: По AETR конвенцијата — 45 мин пауза по 4.5 часа возење. Дневен одмор минимум 11 часа. Неделен одмор 45 часа',
          'Тахограф е задолжителен: Сите возила над 3.5 тони мора да имаат дигитален тахограф. Податоците се чуваат 365 дена и се предмет на инспекција',
          'Лекарски преглед: Годишен задолжителен преглед за возачи на тешки товарни возила (категорија C/CE)',
          'Професионална компетентност (CPC): Обнова на секои 5 години — 35 часа обука. Трошокот е признат расход на фирмата',
        ],
        steps: null,
      },
      {
        title: 'Патарини и гранични такси',
        content:
          'Патарините и граничните такси се значаен трошок, особено за меѓународен транспорт:',
        items: null,
        steps: [
          { step: 'Домашни патарини', desc: 'Патарините на автопатиштата во Македонија (М1, М3, М4) се одбивен расход. Чувајте ги сметките или користете е-наплата за автоматска евиденција.' },
          { step: 'Странски патарини (виетки, е-toll)', desc: 'Патарини во ЕУ (Бугарска е-виетка, Грчка Egnatia, Србија путарина) — одбивен расход. Фактурите мора да бидат на име на фирмата.' },
          { step: 'Гранични такси и дозволи', desc: 'Транзитни дозволи (CEMT, билатерални) имаат такси. Еко-такси (Австрија, Швајцарија) — исто одбивен расход.' },
          { step: 'Царински такси', desc: 'При увоз/извоз — царинските такси и шпедитерските услуги се одбивен расход. ДДВ на царинските услуги е одбивен.' },
          { step: 'Казни и глоби', desc: 'ВНИМАНИЕ: Казни за сообраќајни прекршоци, претоварување или неисправен тахограф НЕ се признат расход за данок на добивка. Евидентирајте ги одделно.' },
        ],
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino е дизајниран со модули специјално за транспортни и логистички компании:',
        items: [
          'Патни налози со следење на товар: Креирајте патен налог за секое возење — возач, возило, релација, датуми, товар и автоматска пресметка на дневници',
          'Управување со возен парк: Евиденција на сите возила, регистрации, осигурувања, сервисни интервали и амортизација',
          'Автоматска пресметка на дневници: Домашни и меѓународни дневници по земја — автоматски пресметани според Уредбата на Владата',
          'Следење на гориво по возило: Евиденција на секое полнење, норма на потрошувачка, алерт за отстапување',
          'Модул за плати за возачи: Автоматска пресметка со ноќна работа, прекувремена, дневници и додатоци',
          'ДДВ за меѓународен транспорт: Автоматско раздвојување на 0% и 18% ДДВ по тип на услуга',
          'Евиденција на патарини и такси: Скенирајте ги сметките или внесете рачно — автоматски се поврзуваат со патниот налог',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија' },
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија' },
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи' },
      { slug: 'trudovo-pravo-osnovi', title: 'Трудово право: Основи за работодавци' },
      { slug: 'nabavki-i-narachki', title: 'Набавки и нарачки во Facturino' },
    ],
    bottomCta: {
      title: 'Транспортна фирма? Facturino ве покрива.',
      subtitle: 'Патни налози, гориво, дневници, возен парк и ДДВ 0%/18% — сè на едно место. Пробајте бесплатно.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Transport & Logistics Accounting Macedonia: Fuel, Travel Orders & VAT',
    publishDate: 'May 23, 2026',
    readTime: '12 min read',
    intro:
      'Transport and logistics is one of the most complex sectors for accounting in North Macedonia. Daily fuel tracking, international VAT with different rates, mandatory travel orders for every trip, fleet depreciation, driver-specific payroll and countless toll charges and border fees — all of this demands precise and timely bookkeeping. This guide covers everything transport companies need to know about proper accounting in 2026.',
    sections: [
      {
        title: 'Fuel expense tracking',
        content:
          'Fuel is the largest cost in transport — typically 30-40% of total operating expenses. Proper recording is critical for both tax purposes and profitability control:',
        items: [
          'Daily fuel log: every fill-up must be recorded with date, vehicle, mileage, litres and price per litre',
          'Deductible VAT on fuel: VAT on fuel for commercial vehicles is fully deductible (18%). For mixed use (business + personal) — only the business portion is deductible',
          'Non-deductible VAT: Fuel for passenger vehicles not registered for transport — VAT is NOT deductible (Art. 35 VAT Act)',
          'Fleet cards: Using fleet cards (Makpetrol, OKTA) is recommended — automatic recording, monthly consolidated invoice, control by driver and vehicle',
          'Fuel receipts: Every fuel receipt must contain: company name, VAT number, vehicle registration and quantity',
          'Consumption norm: Every vehicle should have a defined norm (l/100km). Deviation above 15% = sign of misuse or defect',
          'Fuel abroad: Fill-ups in foreign countries — keep the receipts, VAT can be recovered via VAT refund procedure (where the country allows)',
        ],
        steps: null,
      },
      {
        title: 'International transport VAT',
        content:
          'This is the most complex part of transport accounting. The rules are strict and mistakes lead to audits:',
        items: null,
        steps: [
          { step: 'International transport = 0% VAT', desc: 'Transport of goods that starts or ends outside North Macedonia is taxed at 0% VAT (Art. 24 VAT Act). This means you do NOT charge VAT, but you DO have the right to deduct input VAT.' },
          { step: 'Domestic transport = 18% VAT', desc: 'Transport of goods within North Macedonia is taxed at the standard rate of 18%. No exceptions for freight transport.' },
          { step: 'CMR waybill is mandatory', desc: 'For every international transport you must have a CMR document (Convention Marchandises Routieres). Without CMR — you cannot apply the 0% rate.' },
          { step: 'Input VAT on fuel abroad', desc: 'VAT paid on fuel in EU countries can be recovered via the 13th Directive (VAT refund). The procedure takes 6-12 months. Keep ORIGINAL invoices.' },
          { step: 'Transit transport', desc: 'Goods only transiting through North Macedonia (e.g. Greece to Serbia) — 0% VAT. Customs transit documentation (T1/T2) is required.' },
        ],
      },
      {
        title: 'Travel orders — mandatory for every trip',
        content:
          'The travel order is the most important document in a transport company. Without it, fuel expenses and per diems are NOT a recognized expense:',
        items: [
          'Mandatory for every trip: Every journey — domestic or international — must be covered by a travel order',
          'Required content: Driver name, vehicle registration, route (from-to), date and time of departure and arrival, purpose of travel',
          'Domestic per diem: 1,700 MKD per day (for trips exceeding 8 hours). Exempt from tax and contributions',
          'International per diem: Varies by country — e.g. Germany EUR 50, Serbia EUR 35, Turkey EUR 40, Greece EUR 45. Set by Government Decree',
          'Per diems are non-taxable: Per diems up to the set limit are NOT taxed. Above the limit — treated as salary (10% PIT + contributions)',
          'Attachments to the order: Fuel receipts, toll charges, parking, hotel — all are attached to the travel order',
          'Submission deadline: The travel order must be completed and submitted to accounting within 5 business days after the trip ends',
        ],
        steps: null,
      },
      {
        title: 'Vehicle depreciation',
        content:
          'Vehicles are the largest fixed assets in a transport company. Proper depreciation is key to the tax base:',
        items: null,
        steps: [
          { step: 'Standard depreciation: 5 years (20% per year)', desc: 'Trucks, freight vehicles and vans are depreciated at 20% per year (straight-line method). After 5 years — book value = 0, but the vehicle continues to be used.' },
          { step: 'Maintenance as current expense', desc: 'Regular maintenance (oil, tyres, filters, brakes) is a current expense — fully deductible in the year incurred. Not capitalised.' },
          { step: 'Major overhaul = capital improvement', desc: 'If the overhaul increases the life or capacity of the vehicle (new engine, upgrade), it is a capital improvement and is added to the acquisition cost.' },
          { step: 'Sale of a vehicle', desc: 'Upon sale — the difference between selling price and book value is a gain (or loss). Taxed with corporate income tax.' },
          { step: 'Leasing vs buying', desc: 'Finance lease — the vehicle is on the balance sheet, depreciated. Operating lease — the instalment is a current expense, no depreciation.' },
        ],
      },
      {
        title: 'Driver payroll specifics',
        content:
          'Drivers have specific rights and premiums that significantly increase payroll costs:',
        items: [
          'Night work (22:00-06:00): 35% premium on base salary (Art. 106 LRA). International drivers often drive at night',
          'Overtime: 40% premium on base salary. Maximum 8 hours per week, 190 hours per year',
          'Per diems — non-taxable up to limit: Domestic 1,700 MKD/day, international per country. Above limit = salary with full contributions',
          'Mandatory rest periods: Under AETR convention — 45 min break after 4.5 hours of driving. Daily rest minimum 11 hours. Weekly rest 45 hours',
          'Tachograph is mandatory: All vehicles over 3.5 tonnes must have a digital tachograph. Data is kept for 365 days and is subject to inspection',
          'Medical examination: Annual mandatory check-up for heavy goods vehicle drivers (category C/CE)',
          'Certificate of Professional Competence (CPC): Renewal every 5 years — 35 hours of training. The cost is a recognized company expense',
        ],
        steps: null,
      },
      {
        title: 'Toll charges and border fees',
        content:
          'Tolls and border fees are a significant cost, especially for international transport:',
        items: null,
        steps: [
          { step: 'Domestic tolls', desc: 'Tolls on North Macedonian motorways (M1, M3, M4) are a deductible expense. Keep the receipts or use e-toll for automatic recording.' },
          { step: 'Foreign tolls (vignettes, e-toll)', desc: 'Tolls in the EU (Bulgarian e-vignette, Greek Egnatia, Serbian toll) — deductible expense. Invoices must be in the company name.' },
          { step: 'Border fees and permits', desc: 'Transit permits (CEMT, bilateral) have fees. Eco-taxes (Austria, Switzerland) — also a deductible expense.' },
          { step: 'Customs duties', desc: 'On import/export — customs duties and freight forwarding services are deductible expenses. VAT on customs services is deductible.' },
          { step: 'Fines and penalties', desc: 'WARNING: Fines for traffic violations, overloading or faulty tachograph are NOT a recognized expense for corporate tax. Record them separately.' },
        ],
      },
      {
        title: 'How Facturino helps',
        content:
          'Facturino is designed with modules specifically for transport and logistics companies:',
        items: [
          'Travel orders with cargo tracking: Create a travel order for every trip — driver, vehicle, route, dates, cargo and automatic per diem calculation',
          'Fleet management: Record all vehicles, registrations, insurance, service intervals and depreciation',
          'Automatic per diem calculation: Domestic and international per diems by country — automatically calculated per Government Decree',
          'Fuel tracking by vehicle: Record every fill-up, consumption norm, deviation alerts',
          'Driver payroll module: Automatic calculation with night work, overtime, per diems and premiums',
          'International transport VAT: Automatic splitting of 0% and 18% VAT by service type',
          'Toll and fee tracking: Scan receipts or enter manually — automatically linked to the travel order',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for North Macedonia' },
      { slug: 'presmetka-na-plata-mk', title: 'Salary Calculation in North Macedonia' },
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management' },
      { slug: 'trudovo-pravo-osnovi', title: 'Labor Law: Basics for Employers' },
      { slug: 'nabavki-i-narachki', title: 'Purchase Orders & Procurement in Facturino' },
    ],
    bottomCta: {
      title: 'Transport company? Facturino has you covered.',
      subtitle: 'Travel orders, fuel, per diems, fleet management and 0%/18% VAT — all in one place. Try it free.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti për transport: Karburant, urdhra udhëtimi dhe TVSH',
    publishDate: '23 maj 2026',
    readTime: '12 min lexim',
    intro:
      'Transporti dhe logjistika janë një nga sektorët më kompleks për kontabilitetin në Maqedoni. Ndjekja ditore e karburantit, TVSH ndërkombëtare me norma të ndryshme, urdhra udhëtimi të detyrueshme për çdo udhëtim, amortizimi i flotës, pagat specifike për shoferë dhe taksa të panumërta doganore e rrugore — e gjithë kjo kërkon kontabilitet të saktë dhe në kohë. Ky udhëzues mbulon gjithçka që kompanitë e transportit duhet të dinë për kontabilitetin e duhur në 2026.',
    sections: [
      {
        title: 'Ndjekja e shpenzimeve të karburantit',
        content:
          'Karburanti është shpenzimi më i madh në transport — zakonisht 30-40% e shpenzimeve totale operative. Regjistrimi i duhur është kritik si për qëllime tatimore ashtu edhe për kontrollin e profitabilitetit:',
        items: [
          'Ditari ditor i karburantit: çdo mbushje duhet të regjistrohet me datë, automjet, kilometrazh, litra dhe çmim për litër',
          'TVSH e zbritshme në karburant: TVSH-ja në karburant për automjete komerciale zbritet plotësisht (18%). Për përdorim të përzier (biznes + personal) — vetëm pjesa e biznesit zbritet',
          'TVSH e pazbritshme: Karburant për automjete pasagjerësh që nuk janë të regjistruara për transport — TVSH NUK zbritet (Neni 35 Ligji për TVSH)',
          'Karta flote: Përdorimi i kartave të flotës (Makpetrol, OKTA) rekomandohet — regjistrim automatik, faturë mujore e konsoliduar, kontroll sipas shoferit dhe automjetit',
          'Faturat e karburantit: Çdo faturë karburanti duhet të përmbajë: emrin e kompanisë, numrin TVSH, targën e automjetit dhe sasinë',
          'Norma e konsumit: Çdo automjet duhet të ketë normë të përcaktuar (l/100km). Devijimi mbi 15% = shenjë keqpërdorimi ose defekti',
          'Karburant jashtë vendit: Mbushjet jashtë — ruani faturat, TVSH-ja mund të rikthehet përmes procedurës VAT refund (ku vendi e lejon)',
        ],
        steps: null,
      },
      {
        title: 'TVSH për transport ndërkombëtar',
        content:
          'Kjo është pjesa më komplekse e kontabilitetit të transportit. Rregullat janë strikte dhe gabimet çojnë në auditim:',
        items: null,
        steps: [
          { step: 'Transport ndërkombëtar = 0% TVSH', desc: 'Transporti i mallrave që fillon ose mbaron jashtë Maqedonisë tatohet me 0% TVSH (Neni 24 Ligji për TVSH). Kjo do të thotë që NUK ngarkoni TVSH, por KENI të drejtë të zbritni TVSH-në hyrëse.' },
          { step: 'Transport vendas = 18% TVSH', desc: 'Transporti i mallrave brenda Maqedonisë tatohet me normën standarde 18%. Pa përjashtime për transportin e mallrave.' },
          { step: 'Fletëngarkesa CMR është e detyrueshme', desc: 'Për çdo transport ndërkombëtar duhet të keni dokument CMR (Convention Marchandises Routières). Pa CMR — nuk mund të zbatoni normën 0%.' },
          { step: 'TVSH hyrëse në karburant jashtë vendit', desc: 'TVSH-ja e paguar në karburant në vendet e BE-së mund të rikthehet përmes Direktivës 13 (VAT refund). Procedura zgjat 6-12 muaj. Ruani faturat ORIGJINALE.' },
          { step: 'Transport tranzit', desc: 'Mallrat që vetëm kalojnë nëpër Maqedoni (psh. Greqi → Serbi) — 0% TVSH. Kërkohet dokumentacion doganor tranziti (T1/T2).' },
        ],
      },
      {
        title: 'Urdhrat e udhëtimit — të detyrueshme për çdo udhëtim',
        content:
          'Urdhri i udhëtimit është dokumenti më i rëndësishëm në kompaninë e transportit. Pa të, shpenzimet e karburantit dhe dietat NUK janë shpenzim i njohur:',
        items: [
          'I detyrueshëm për çdo udhëtim: Çdo udhëtim — vendas ose ndërkombëtar — duhet të mbulohet me urdhër udhëtimi',
          'Përmbajtja e kërkuar: Emri i shoferit, targa e automjetit, itinerari (nga-ku), data dhe ora e nisjes dhe mbërritjes, qëllimi i udhëtimit',
          'Dieta vendase: 1.700 MKD për ditë (për udhëtime mbi 8 orë). E përjashtuar nga tatimi dhe kontributet',
          'Dieta ndërkombëtare: Ndryshon sipas vendit — psh. Gjermani EUR 50, Serbi EUR 35, Turqi EUR 40, Greqi EUR 45. E përcaktuar me Dekret të Qeverisë',
          'Dietat janë të patatueshme: Dietat deri në limitin e përcaktuar NUK tatohen. Mbi limit — trajtohen si pagë (10% TAP + kontribute)',
          'Bashkëngjitjet në urdhër: Faturat e karburantit, taksat e rrugës, parkingu, hoteli — të gjitha bashkëngjiten në urdhrin e udhëtimit',
          'Afati i dorëzimit: Urdhri i udhëtimit duhet të përfundojë dhe dorëzohet në kontabilitet brenda 5 ditëve pune pas përfundimit të udhëtimit',
        ],
        steps: null,
      },
      {
        title: 'Amortizimi i automjeteve',
        content:
          'Automjetet janë aktivet fikse më të mëdha në kompaninë e transportit. Amortizimi i duhur është çelësi i bazës tatimore:',
        items: null,
        steps: [
          { step: 'Amortizim standard: 5 vjet (20% në vit)', desc: 'Kamionët, automjetet e mallrave dhe furgonat amortizohen me 20% në vit (metoda lineare). Pas 5 vjetësh — vlera kontabël = 0, por automjeti vazhdon të përdoret.' },
          { step: 'Mirëmbajtja si shpenzim aktual', desc: 'Mirëmbajtja e rregullt (vaji, gomat, filtrat, frenat) është shpenzim aktual — plotësisht i zbritshëm në vitin kur ndodh. Nuk kapitalizohet.' },
          { step: 'Riparim i madh = përmirësim kapital', desc: 'Nëse riparimi rrit jetën ose kapacitetin e automjetit (motor i ri, përmirësim), ai është përmirësim kapital dhe shtohet në vlerën e blerjes.' },
          { step: 'Shitja e automjetit', desc: 'Në shitje — dallimi midis çmimit të shitjes dhe vlerës kontabël është fitim (ose humbje). Tatohet me tatimin mbi fitimin korporativ.' },
          { step: 'Leasing vs blerje', desc: 'Leasing financiar — automjeti është në bilanc, amortizohet. Leasing operativ — kësti është shpenzim aktual, pa amortizim.' },
        ],
      },
      {
        title: 'Specifika të pagave për shoferë',
        content:
          'Shoferët kanë të drejta dhe shtesa specifike që rrisin ndjeshëm shpenzimet e pagave:',
        items: [
          'Punë natën (22:00-06:00): shtesë 35% mbi pagën bazë (Neni 106 LMP). Shoferët ndërkombëtarë shpesh ngasin natën',
          'Orar shtesë: shtesë 40% mbi pagën bazë. Maksimumi 8 orë në javë, 190 orë në vit',
          'Dieta — të patatueshme deri në limit: Vendase 1.700 MKD/ditë, ndërkombëtare sipas vendit. Mbi limit = pagë me kontribute të plota',
          'Periudha të detyrueshme pushimi: Sipas konventës AETR — 45 min pushim pas 4.5 orëve ngasje. Pushim ditor minimum 11 orë. Pushim javor 45 orë',
          'Tahografi është i detyrueshëm: Të gjithë automjetet mbi 3.5 ton duhet të kenë tahograf dixhital. Të dhënat ruhen 365 ditë dhe janë subjekt inspektimi',
          'Ekzaminim mjekësor: Kontroll vjetor i detyrueshëm për shoferët e automjeteve të rënda (kategoria C/CE)',
          'Certifikata e Kompetencës Profesionale (CPC): Rinovim çdo 5 vjet — 35 orë trajnim. Shpenzimi është shpenzim i njohur i kompanisë',
        ],
        steps: null,
      },
      {
        title: 'Taksat e rrugës dhe taksat kufitare',
        content:
          'Taksat e rrugës dhe taksat kufitare janë shpenzim i konsiderueshëm, veçanërisht për transport ndërkombëtar:',
        items: null,
        steps: [
          { step: 'Taksa vendase të rrugës', desc: 'Taksat në autostradhat e Maqedonisë (M1, M3, M4) janë shpenzim i zbritshëm. Ruani faturat ose përdorni e-toll për regjistrim automatik.' },
          { step: 'Taksa të huaja (vinjeta, e-toll)', desc: 'Taksat në BE (e-vinjeta bullgare, Egnatia greke, taksa serbe) — shpenzim i zbritshëm. Faturat duhet të jenë në emër të kompanisë.' },
          { step: 'Taksa kufitare dhe leje', desc: 'Lejet e tranzitit (CEMT, bilaterale) kanë tarifa. Eko-taksat (Austri, Zvicër) — gjithashtu shpenzim i zbritshëm.' },
          { step: 'Taksa doganore', desc: 'Në import/eksport — taksat doganore dhe shërbimet e spedicionit janë shpenzime të zbritshme. TVSH-ja në shërbimet doganore zbritet.' },
          { step: 'Gjoba dhe ndëshkime', desc: 'KUJDES: Gjobat për shkelje trafiku, mbingarkesë ose tahograf të prishur NUK janë shpenzim i njohur për tatimin mbi fitimin. Regjistroni veçanërisht.' },
        ],
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino është dizajnuar me module specifikisht për kompani transporti dhe logjistike:',
        items: [
          'Urdhra udhëtimi me ndjekje ngarkese: Krijoni urdhër udhëtimi për çdo udhëtim — shofer, automjet, itinerar, data, ngarkesë dhe llogaritje automatike e dietave',
          'Menaxhim i flotës: Regjistrim i të gjithë automjeteve, regjistrimeve, sigurimeve, intervaleve të servisit dhe amortizimit',
          'Llogaritje automatike e dietave: Dieta vendase dhe ndërkombëtare sipas vendit — të llogaritura automatikisht sipas Dekretit të Qeverisë',
          'Ndjekje karburanti sipas automjetit: Regjistrim i çdo mbushjeje, norma e konsumit, alarme devijimi',
          'Moduli i pagave për shoferë: Llogaritje automatike me punë natën, orar shtesë, dieta dhe shtesa',
          'TVSH për transport ndërkombëtar: Ndarje automatike e 0% dhe 18% TVSH sipas llojit të shërbimit',
          'Ndjekja e taksave të rrugës: Skanoni faturat ose futni manualisht — lidhen automatikisht me urdhrin e udhëtimit',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'Udhëzues TVSH për Maqedoninë' },
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni' },
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve' },
      { slug: 'trudovo-pravo-osnovi', title: 'E drejta e punës: Bazat për punëdhënësit' },
      { slug: 'nabavki-i-narachki', title: 'Porosi blerjeje dhe furnizime në Facturino' },
    ],
    bottomCta: {
      title: 'Kompani transporti? Facturino ju mbulon.',
      subtitle: 'Urdhra udhëtimi, karburant, dieta, flotë dhe TVSH 0%/18% — gjithçka në një vend. Provoni falas.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloğa dön',
    tag: 'Sektör',
    title: 'Taşımacılık Muhasebesi Makedonya: Yakıt, Sefer Emirleri ve KDV',
    publishDate: '23 Mayıs 2026',
    readTime: '12 dk okuma',
    intro:
      'Taşımacılık ve lojistik, Kuzey Makedonya\'da muhasebe açısından en karmaşık sektörlerden biridir. Günlük yakıt takibi, farklı oranlarda uluslararası KDV, her sefer için zorunlu sefer emirleri, filo amortismanı, şoförlere özel bordro ve sayısız geçiş ücreti ile sınır harçları — bunların tamamı hassas ve zamanında muhasebe gerektirir. Bu rehber, taşımacılık şirketlerinin 2026\'da doğru muhasebe hakkında bilmesi gereken her şeyi kapsar.',
    sections: [
      {
        title: 'Yakıt gideri takibi',
        content:
          'Yakıt, taşımacılıkta en büyük maliyettir — genellikle toplam işletme giderlerinin %30-40\'ı. Doğru kayıt hem vergi hem kârlılık kontrolü için kritiktir:',
        items: [
          'Günlük yakıt defteri: her dolum tarih, araç, kilometre, litre ve litre başına fiyatla kaydedilmelidir',
          'İndirilebilir yakıt KDV\'si: Ticari araçlar için yakıt KDV\'si tamamen indirilebilir (%18). Karma kullanımda (iş + özel) — yalnızca iş kısmı indirilebilir',
          'İndirilemeyen KDV: Taşımacılık için kayıtlı olmayan binek araçlar için yakıt — KDV İNDİRİLEMEZ (KDV Kanunu Md. 35)',
          'Filo kartları: Filo kartlarının (Makpetrol, OKTA) kullanımı önerilir — otomatik kayıt, aylık toplu fatura, şoför ve araç bazında kontrol',
          'Yakıt fişleri: Her yakıt fişinde bulunması gerekenler: şirket adı, KDV numarası, araç plakası ve miktar',
          'Tüketim normu: Her araç tanımlı norma sahip olmalıdır (l/100km). %15 üzeri sapma = kötüye kullanım veya arıza işareti',
          'Yurt dışında yakıt: Yurt dışında dolumlar — fişleri saklayın, KDV, VAT refund prosedürüyle geri alınabilir (ülke izin veriyorsa)',
        ],
        steps: null,
      },
      {
        title: 'Uluslararası taşımacılık KDV\'si',
        content:
          'Bu, taşımacılık muhasebesinin en karmaşık kısmıdır. Kurallar katıdır ve hatalar denetime yol açar:',
        items: null,
        steps: [
          { step: 'Uluslararası taşımacılık = %0 KDV', desc: 'Kuzey Makedonya dışında başlayan veya biten mal taşımacılığı %0 KDV ile vergilendirilir (KDV Kanunu Md. 24). Bu, KDV tahsil ETMEDİĞİNİZ ama giriş KDV\'sini indirme HAKKINIZ olduğu anlamına gelir.' },
          { step: 'Yurt içi taşımacılık = %18 KDV', desc: 'Kuzey Makedonya içindeki mal taşımacılığı standart %18 oranıyla vergilendirilir. Yük taşımacılığı için istisna yoktur.' },
          { step: 'CMR taşıma belgesi zorunludur', desc: 'Her uluslararası taşımacılık için CMR belgesi (Convention Marchandises Routières) olmalıdır. CMR olmadan — %0 oranını uygulayamazsınız.' },
          { step: 'Yurt dışı yakıtta giriş KDV\'si', desc: 'AB ülkelerinde yakıt için ödenen KDV, 13. Direktif (VAT refund) aracılığıyla geri alınabilir. Prosedür 6-12 ay sürer. ORİJİNAL faturaları saklayın.' },
          { step: 'Transit taşımacılık', desc: 'Yalnızca Kuzey Makedonya\'dan geçiş yapan mallar (örn. Yunanistan → Sırbistan) — %0 KDV. Gümrük transit belgeleri (T1/T2) gereklidir.' },
        ],
      },
      {
        title: 'Sefer emirleri — her yolculuk için zorunlu',
        content:
          'Sefer emri, taşımacılık şirketinde en önemli belgedir. Onsuz, yakıt giderleri ve harcırahlar tanınan gider DEĞİLDİR:',
        items: [
          'Her yolculuk için zorunlu: Her sefer — yurt içi veya uluslararası — bir sefer emriyle karşılanmalıdır',
          'Gerekli içerik: Şoför adı, araç plakası, güzergah (nereden-nereye), kalkış ve varış tarihi ve saati, yolculuk amacı',
          'Yurt içi harcırah: Günlük 1.700 MKD (8 saati aşan yolculuklar için). Vergi ve primlerden muaf',
          'Uluslararası harcırah: Ülkeye göre değişir — örn. Almanya EUR 50, Sırbistan EUR 35, Türkiye EUR 40, Yunanistan EUR 45. Hükümet Kararnamesiyle belirlenir',
          'Harcırahlar vergisizdir: Belirlenen limite kadar harcırahlar VERGİLENDİRİLMEZ. Limit üzerinde — maaş olarak değerlendirilir (%10 GV + primler)',
          'Emre ekler: Yakıt fişleri, geçiş ücretleri, otopark, otel — hepsi sefer emrine eklenir',
          'Teslim süresi: Sefer emri, yolculuk bittikten sonra 5 iş günü içinde tamamlanıp muhasebeye teslim edilmelidir',
        ],
        steps: null,
      },
      {
        title: 'Araç amortismanı',
        content:
          'Araçlar, taşımacılık şirketinde en büyük duran varlıklardır. Doğru amortisman vergi matrahı için kilit önemdedir:',
        items: null,
        steps: [
          { step: 'Standart amortisman: 5 yıl (yılda %20)', desc: 'Kamyonlar, yük araçları ve minibüsler yılda %20 oranında amortismana tabi tutulur (doğrusal yöntem). 5 yıl sonra — defter değeri = 0, ancak araç kullanılmaya devam eder.' },
          { step: 'Bakım cari gider olarak', desc: 'Düzenli bakım (yağ, lastik, filtre, fren) cari giderdir — oluştuğu yılda tamamen indirilebilir. Aktifleştirilmez.' },
          { step: 'Büyük onarım = sermaye iyileştirmesi', desc: 'Onarım aracın ömrünü veya kapasitesini artırıyorsa (yeni motor, yükseltme), bu sermaye iyileştirmesidir ve satın alma maliyetine eklenir.' },
          { step: 'Araç satışı', desc: 'Satışta — satış fiyatı ile defter değeri arasındaki fark kâr (veya zarar) oluşturur. Kurumlar vergisiyle vergilendirilir.' },
          { step: 'Leasing vs satın alma', desc: 'Finansal leasing — araç bilançoda yer alır, amortismana tabi. Operasyonel leasing — taksit cari giderdir, amortisman yok.' },
        ],
      },
      {
        title: 'Şoför bordro özellikleri',
        content:
          'Şoförlerin bordro maliyetlerini önemli ölçüde artıran özel hakları ve primleri vardır:',
        items: [
          'Gece çalışması (22:00-06:00): temel maaş üzerine %35 prim (İİK Md. 106). Uluslararası şoförler sıklıkla gece sürer',
          'Fazla mesai: temel maaş üzerine %40 prim. Haftada maksimum 8 saat, yılda 190 saat',
          'Harcırah — limite kadar vergisiz: Yurt içi 1.700 MKD/gün, uluslararası ülkeye göre. Limit üzeri = tam primli maaş',
          'Zorunlu dinlenme süreleri: AETR konvansiyonuna göre — 4,5 saat sürüşten sonra 45 dk mola. Günlük dinlenme minimum 11 saat. Haftalık dinlenme 45 saat',
          'Takograf zorunludur: 3,5 tonun üzerindeki tüm araçlarda dijital takograf bulunmalıdır. Veriler 365 gün saklanır ve denetime tabidir',
          'Sağlık muayenesi: Ağır yük araç şoförleri (C/CE kategorisi) için yıllık zorunlu kontrol',
          'Mesleki Yeterlilik Belgesi (SRC): 5 yılda bir yenileme — 35 saat eğitim. Maliyet şirketin tanınan gideridir',
        ],
        steps: null,
      },
      {
        title: 'Geçiş ücretleri ve sınır harçları',
        content:
          'Geçiş ücretleri ve sınır harçları, özellikle uluslararası taşımacılıkta önemli bir maliyettir:',
        items: null,
        steps: [
          { step: 'Yurt içi geçiş ücretleri', desc: 'Kuzey Makedonya otoyollarındaki (M1, M3, M4) geçiş ücretleri indirilebilir giderdir. Fişleri saklayın veya otomatik kayıt için e-toll kullanın.' },
          { step: 'Yabancı geçiş ücretleri (vignette, e-toll)', desc: 'AB\'deki geçiş ücretleri (Bulgar e-vinyeti, Yunan Egnatia, Sırp geçiş ücreti) — indirilebilir gider. Faturalar şirket adına olmalıdır.' },
          { step: 'Sınır harçları ve izinler', desc: 'Transit izinlerinin (CEMT, ikili) harçları vardır. Eko-vergiler (Avusturya, İsviçre) — aynı şekilde indirilebilir gider.' },
          { step: 'Gümrük vergileri', desc: 'İthalat/ihracatta — gümrük vergileri ve gümrük müşavirliği hizmetleri indirilebilir giderdir. Gümrük hizmetlerindeki KDV indirilebilir.' },
          { step: 'Cezalar ve para cezaları', desc: 'DİKKAT: Trafik ihlali, aşırı yükleme veya arızalı takograf cezaları kurumlar vergisi için tanınan gider DEĞİLDİR. Ayrı kaydedin.' },
        ],
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content:
          'Facturino, özellikle taşımacılık ve lojistik şirketleri için tasarlanmış modüllere sahiptir:',
        items: [
          'Kargo takipli sefer emirleri: Her sefer için sefer emri oluşturun — şoför, araç, güzergah, tarihler, kargo ve otomatik harcırah hesaplama',
          'Filo yönetimi: Tüm araçların, tescillerin, sigortaların, servis aralıklarının ve amortismanın kaydı',
          'Otomatik harcırah hesaplama: Ülkeye göre yurt içi ve uluslararası harcırahlar — Hükümet Kararnamesine göre otomatik hesaplanır',
          'Araç bazında yakıt takibi: Her dolumun kaydı, tüketim normu, sapma uyarıları',
          'Şoför bordro modülü: Gece çalışması, fazla mesai, harcırah ve primlerle otomatik hesaplama',
          'Uluslararası taşımacılık KDV\'si: Hizmet türüne göre %0 ve %18 KDV otomatik ayrımı',
          'Geçiş ücreti ve harç takibi: Fişleri tarayın veya manuel girin — otomatik olarak sefer emrine bağlanır',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV Rehberi' },
      { slug: 'presmetka-na-plata-mk', title: 'Makedonya\'da Maaş Hesaplama' },
      { slug: 'upravljanje-so-rashodi', title: 'Gider Yönetimi' },
      { slug: 'trudovo-pravo-osnovi', title: 'İş Hukuku: İşverenler İçin Temel Bilgiler' },
      { slug: 'nabavki-i-narachki', title: 'Facturino\'da Satın Alma Siparişleri ve Tedarik' },
    ],
    bottomCta: {
      title: 'Taşımacılık şirketi? Facturino yanınızda.',
      subtitle: 'Sefer emirleri, yakıt, harcırah, filo yönetimi ve %0/%18 KDV — hepsi bir arada. Ücretsiz deneyin.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaTransportPage({
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
    slug: 'smetkovodstvo-za-transport',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['транспорт', 'transport', 'гориво', 'fuel', 'патни налози', 'travel orders', 'ДДВ', 'VAT', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-transport` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Дали ДДВ за меѓународен транспорт е 0%?', answer: 'Да. Транспорт на стока кој почнува или завршува надвор од Македонија се оданочува со 0% ДДВ (Чл. 24 ЗДДВ). Мора да имате CMR товарница како доказ. Домашен транспорт е 18%. Важно: 0% стапка НЕ значи ослободување — имате право на одбивен влезен ДДВ.' },
        { question: 'Што е патен налог и зошто е задолжителен?', answer: 'Патниот налог е документ за секое возење (домашно или меѓународно) кој содржи: возач, возило, релација, датум/час и цел. Без патен налог, трошоците за гориво и дневници НЕ се признат расход при даночна контрола. Рок за поднесување: 5 работни дена по завршување.' },
        { question: 'Колку изнесуваат дневниците за возачи?', answer: 'Домашни дневници: 1.700 МКД по ден (за патување над 8 часа). Меѓународни: различни по земја — Германија EUR 50, Србија EUR 35, Турција EUR 40, Грција EUR 45. До утврдениот лимит се неоданочиви. Над лимитот се третираат како плата (10% ПДД + придонеси 28%).' },
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
