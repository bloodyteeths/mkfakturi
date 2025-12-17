import { Locale } from './locales'

export type FeatureCard = { title: string; body: string }

export type Dictionary = {
  meta: { title: string; description: string }
  nav: {
    features: string
    forAccountants?: string
    how: string
    efaktura: string
    pricing: string
    security: string
    contact: string
    contactSales: string
    start: string
    login: string
    language: string
  }
  hero: { h1: string; sub: string; primaryCta: string; secondaryCta: string; claim: string; onlyPlatform: string }
  // extra tagline line for hero
  heroTagline?: string
  aiSection: { badge: string; title: string; subtitle: string; features: { title: string; desc: string }[] }
  featureGrid: { title: string; subtitle: string; features: { title: string; desc: string }[] }
  socialProof: { trustedBy: string }
  whyDifferent: { title: string; cards: FeatureCard[] }
  benefits: { title: string; badge: string; cards: { title: string; body: string }[] }
  how: { title: string; process: string; subtitle: string; steps: { title: string; body: string }[] }
  cta: { title: string; sub?: string; button: string }
  footer: { rights: string }
  partners?: { title: string; logos: string[] }
  pricingPreview?: { title: string; cta: string; plans: { name: string; bullets: string[] }[] }
  testimonials?: { title: string; subtitle: string; items: { quote: string; author: string }[] }
  faq?: { title: string; items: { q: string; a: string }[] }
  featuresPage?: {
    heroTitle: string
    groups: { title: string; items: string[] }[]
  }
  pricingPage?: {
    h1: string
    sub: string
    sectionCompany: string
    sectionPartner: string
    popularBadge: string
    recommendedBadge: string
    includesPrevious: string
    companyPlans: { name: string; price: string; period: string; bullets: string[]; popular: boolean }[]
    partnerPlans: { name: string; price: string; period: string; bullets: string[]; popular: boolean }[]
    cta: string
    ctaPartner: string
    comparisonTable: {
      title: string
      plans: string[]
      rows: { feature: string; values: (string | boolean)[] }[]
    }
  }
}

// Dictionaries per locale
const mk: Dictionary = {
  meta: {
    title: 'Facturino — Најнапредна AI сметководствена платформа во Македонија',
    description:
      'AI + е‑Фактура подготвен систем за македонски сметководители. Мулти‑клиент, PSD2 банки, IFRS извештаи.'
  },
  nav: {
    features: 'Функции',
    forAccountants: 'За сметководители',
    how: 'Како работи',
    efaktura: 'Е‑Фактура',
    pricing: 'Цени',
    security: 'Безбедност',
    contact: 'Контакт',
    contactSales: 'Контакт со продажба',
    start: 'Започни бесплатно',
    login: 'Пријава',
    language: 'Јазик'
  },
  hero: {
    h1: 'Најмоќната AI сметководствена платформа во Македонија, подготвена за е‑Фактура.',
    sub:
      'Моќ на ниво на глобални платформи, но дизајнирана специјално за македонски сметководители.',
    primaryCta: 'Започни бесплатно',
    secondaryCta: 'Закажи демо',
    claim:
      'Facturino е најнапредната AI сметководствена платформа во Македонија, подготвена за новиот систем за е‑Фактура.',
    onlyPlatform:
      'Единствена локална платформа што комбинира AI, македонски сметководствени правила, подготвеност за е‑Фактура и PSD2 банкарски поврзувања – на едно место.'
  },
  heroTagline: 'Сметководствениот софтвер што го очекувавте – конечно е тука.',
  aiSection: {
    badge: 'AI Финансиски Советник',
    title: 'Прашај ме било што за твојот бизнис',
    subtitle: 'Не само автоматизација — добиваш личен финансиски советник кој ги анализира твоите податоци и дава конкретни совети на македонски.',
    features: [
      { title: '💬 Разговарај со AI', desc: 'Прашај "Кој клиент ми должи најмногу?" или "Дали сум профитабилен?" — добиј одговор веднаш.' },
      { title: '⚠️ Рано предупредување за ризици', desc: 'AI те известува кога еден клиент станува преголем ризик или кога имаш задоцнети фактури.' },
      { title: '📊 Прогноза на готовина', desc: 'Гледај 90 дена напред — дали ќе имаш доволно пари на сметка следниот месец?' },
      { title: '🎯 Совети за профит', desc: '"Колку да ги зголемам цените за да имам 500.000 профит?" — добиј конкретен план по артикли.' }
    ]
  },
  featureGrid: {
    title: 'Сè што ви треба за водење на вашиот бизнис',
    subtitle: 'Моќни функции дизајнирани за современи македонски бизниси и сметководители.',
    features: [
      { title: 'Подготвено за е‑Фактура', desc: 'Целосно усогласено со новите владини прописи. Поврзете се веднаш кога ќе се отвори API.' },
      { title: 'Банкарска интеграција', desc: 'Поврзете ги вашите локални банкарски сметки за ажурирања на трансакции во реално време.' },
      { title: 'Безбедност на банкарско ниво', desc: 'Вашите податоци се енкриптирани и безбедно чувани во ЕУ дата центри.' },
      { title: 'Мулти‑клиент', desc: 'Совршено за сметководители кои управуваат со повеќе клиентски компании од едно место.' }
    ]
  },
  socialProof: { trustedBy: 'Доверено од сметководители и мали бизниси' },
  whyDifferent: {
    title: 'Зошто Facturino е различен од било кој софтвер во Македонија',
    cards: [
      { title: 'AI фактурирање и книжење', body: 'Паметни предлози за ДДВ категории и сметки по ставка – вие потврдувате.' },
      { title: 'Е‑Фактура подготвен', body: 'Моделот веќе ги поддржува сите структури; поврзување штом UJP отвори продукциски API + QES.' },
      { title: 'PSD2 банки', body: 'Изводи директно во Facturino и полуавтоматско порамнување со фактури.' },
      { title: 'Мулти‑клиент за канцеларии', body: 'Едно најавување, многу компании, посебни сметки, извештаи и овластувања.' },
      { title: 'IFRS извештаи', body: 'IFRS пакет вграден во заднината за професионални извештаи.' },
      { title: 'Безбедност', body: 'ЕU‑хостинг, енкрипција, резервни копии и трагови на активности.' }
    ]
  },
  benefits: {
    title: 'Бенефити',
    badge: 'Предности',
    cards: [
      { title: 'Заштедете време', body: 'Завршете месечно затворање за часови, не за денови.' },
      { title: 'Работете побрзо', body: 'Вклучете нов клиент за едно попладне.' },
      { title: 'Бидете подготвени', body: 'Подгответе е‑фактури од првиот ден.' }
    ]
  },
  how: {
    title: 'Како работи',
    process: 'Процес',
    subtitle: 'Почнете за минути, не за денови.',
    steps: [
      { title: '1. Поврзи компанија', body: 'Активирај е‑Фактура и подесувања за ДДВ/смeтки.' },
      { title: '2. Креирај фактура', body: 'AI предлага ДДВ и конта; вие потврдувате и испраќате.' },
      { title: '3. Порамни побарувања', body: 'Увезете извод и усогласете за минути, не за часови.' }
    ]
  },
  partners: { title: 'Доверено од', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Пакети',
    cta: 'Види ги цените',
    plans: [
      { name: 'Starter', bullets: ['Е‑Фактура подготвено', '1 корисник', 'AI предлози (основно)'] },
      { name: 'Pro', bullets: ['Мулти‑корисници/улоги', 'PSD2 изводи', 'Автоматизации'] },
      { name: 'Business', bullets: ['Повеќе компании', 'API и напредни овластувања', 'SLA'] }
    ]
  },
  testimonials: {
    title: 'Обожуван од клиенти',
    subtitle: 'Придружете се на стотици задоволни сметководители и сопственици на бизниси.',
    items: [
      { quote: 'Месечното затворање ни падна од 3 дена на неколку часа.', author: 'Ана, сметководител' },
      { quote: 'Изводите влегуваат директно и порамнувањето е лесно.', author: 'Игор, сопственик на фирма' },
      { quote: 'е‑Фактура интеграцијата е совршена. Заштедивме многу време.', author: 'Марија, финансиски директор' },
      { quote: 'AI ми кажа дека 85% од приходот ми доаѓа од еден клиент. Веднаш почнав да барам нови.', author: 'Стефан, сопственик на мала фирма' }
    ]
  },
  faq: {
    title: 'ЧПП',
    items: [
      { q: 'Дали сте подготвени за е‑Фактура?', a: 'Да, моделот е изграден околу е‑фактури и се поврзуваме штом UJP отвори продукциски API + QES.' },
      { q: 'Како функционира AI?', a: 'Предлага ДДВ/конта по ставка — човек секогаш потврдува/уредува.' },
      { q: 'Поддржувате ли PSD2?', a: 'Да, вклучително увоз на изводи и полуавтоматско порамнување.' },
      { q: 'Што може да го прашам AI советникот?', a: 'Било што за твојот бизнис! "Кој ми должи?", "Дали сум профитабилен?", "Што ако го изгубам најголемиот клиент?", "Како да го зголемам профитот?" — AI ги анализира твоите податоци и дава конкретни одговори на македонски.' }
    ]
  },
  cta: { title: 'Подготвени сте? Започнете бесплатно денес.', sub: 'Без кредитна картичка • 14-дневен бесплатен пробен период • Откажете во секое време', button: 'Започни бесплатно' },
  footer: { rights: '© Facturino. Сите права задржани.' },
  featuresPage: {
    heroTitle: 'Функции што не можете да ги промашите',
    groups: [
      {
        title: 'AI Финансиски Советник',
        items: [
          '💬 Прашај било што: "Кој ми должи?", "Дали сум профитабилен?", "Како да го зголемам профитот?"',
          '⚠️ Рано предупредување: AI те известува за ризици од зависност од клиенти',
          '📊 90-дневна прогноза на паричен тек',
          '🎯 Совети за оптимизација на цени и профит',
          'Анализа на старост на побарувања (AR Aging) со топ должници',
          'Што-ако сценарија: "Што ако го изгубам најголемиот клиент?"'
        ]
      },
      {
        title: 'Е‑Фактура и усогласеност',
        items: [
          'Структурирани податоци: ИД за данок, ДДВ по стапка, рокови на плаќање',
          'Подготвено за поврзување кога UJP отвора продукциски API + QES',
          'Професионални македонски PDF изгледи'
        ]
      },
      {
        title: 'Банки и готовински тек',
        items: [
          'PSD2 поврзувања со локални банки',
          'Увоз на изводи и полуавтоматско порамнување',
          'CSV/MT940 како алтернатива'
        ]
      },
      {
        title: 'За сметководители',
        items: [
          'Едно најавување → повеќе компании',
          'Посебни сметководствени планови и извештаи',
          'Роли и овластувања, траги на активности'
        ]
      },
      {
        title: 'Безбедност и контрола',
        items: [
          'Енкрипција во мирување и пренос',
          'Редовни резервни копии и ЕУ хостинг',
          'Аудит логови за клучни активности'
        ]
      }
    ]
  },
  pricingPage: {
    h1: 'Цени',
    sub: '14‑дневен бесплатен пробен период. Без обврска.',
    sectionCompany: 'За компании',
    sectionPartner: 'За сметководители (партнери)',
    popularBadge: 'Популарно',
    recommendedBadge: 'Препорачано',
    includesPrevious: 'Вклучува сè од {plan}',
    companyPlans: [
      { name: 'Free', price: '€0', period: '/засекогаш', bullets: ['5 фактури/месец', '1 корисник', 'PDF извоз', 'Основни шаблони'], popular: false },
      { name: 'Starter', price: '€12', period: '/месец', bullets: ['50 фактури/месец', '1 корисник', 'Неограничено клиенти', 'Email фактури'], popular: false },
      { name: 'Standard', price: '€29', period: '/месец', bullets: ['200 фактури/месец', '3 корисници', 'Е‑Фактура испраќање', 'QES потпис', 'AI увоз'], popular: true },
      { name: 'Business', price: '€59', period: '/месец', bullets: ['1000 фактури/месец', '5 корисници', 'Банкарски изводи', 'Авто-категоризација', 'Full AI увоз'], popular: false },
      { name: 'Max', price: '€149', period: '/месец', bullets: ['Се неограничено', 'API пристап', 'Мулти-локации', 'IFRS извештаи', 'WhatsApp поддршка'], popular: false }
    ],
    partnerPlans: [
      { name: 'Partner', price: 'Бесплатно', period: '', bullets: ['Неограничено клиенти', 'Партнер портал', '20% рекурентна провизија', 'Следење на заработка'], popular: true },
      { name: 'Partner Plus', price: '€29', period: '/месec', bullets: ['Сè од Partner', 'Фактурирање за канцеларија', 'Напредни извештаи', '22% провизија', 'Приоритетна поддршка'], popular: false }
    ],
    cta: 'Започни сега',
    ctaPartner: 'Придружи се',
    comparisonTable: {
      title: 'Споредете ги пакетите',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Фактури месечно', values: ['5', '50', '200', '1000', 'Неограничено'] },
        { feature: 'Корисници', values: ['1', '1', '3', '5', 'Неограничено'] },
        { feature: 'Клиенти', values: ['Неограничено', 'Неограничено', 'Неограничено', 'Неограничено', 'Неограничено'] },
        { feature: 'Е-Фактура (UJP)', values: [false, false, true, true, true] },
        { feature: 'AI Предлози', values: [false, 'Basic', 'Standard', 'Advanced', 'Advanced'] },
        { feature: 'Банкарски изводи (PSD2)', values: [false, false, true, true, true] },
        { feature: 'API Пристап', values: [false, false, false, true, true] },
        { feature: 'Поддршка', values: ['Email', 'Email', 'Email/Chat', 'Prioritetna', 'WhatsApp'] }
      ]
    }
  }
}

const sq: Dictionary = {
  meta: {
    title: 'Facturino — Platforma më e avancuar me AI dhe gati për e‑Faturë',
    description:
      'AI + e‑Faturë për kontabilistët në Maqedoni. Shumë‑klientë, banka PSD2, raporte IFRS.'
  },
  nav: {
    features: 'Veçoritë',
    forAccountants: 'Për kontabilistë',
    how: 'Si funksionon',
    efaktura: 'e‑Faturë',
    pricing: 'Çmimet',
    security: 'Siguria',
    contact: 'Kontakti',
    contactSales: 'Kontakto shitjet',
    start: 'Fillo falas',
    login: 'Hyr',
    language: 'Gjuha'
  },
  hero: {
    h1: 'Platforma më e fuqishme kontabiliteti me AI në Maqedoni, gati për e‑Faturë.',
    sub:
      'Fuqia e platformave globale, por e ndërtuar posaçërisht për kontabilistët në Maqedoni.',
    primaryCta: 'Fillo falas',
    secondaryCta: 'Rezervo demo',
    claim:
      'Facturino është platforma më e avancuar kontabiliteti me AI në Maqedoni, gati për sistemin e ri të e‑Faturës.',
    onlyPlatform:
      'E vetmja platformë lokale që kombinon AI, rregullat kontabël maqedonase, gatishmërinë për e‑Faturë dhe lidhjet bankare PSD2 – në një vend.'
  },
  heroTagline: 'Softueri i kontabilitetit që keni pritur – më në fund është këtu.',
  aiSection: {
    badge: 'Këshilltar Financiar AI',
    title: 'Pyetni çdo gjë për biznesin tuaj',
    subtitle: 'Jo vetëm automatizim — merrni një këshilltar financiar personal që analizon të dhënat tuaja dhe jep këshilla konkrete.',
    features: [
      { title: '💬 Bisedoni me AI', desc: 'Pyesni "Cili klient më detyron më shumë?" ose "A jam profitabil?" — merrni përgjigje menjëherë.' },
      { title: '⚠️ Paralajmërim i hershëm për rreziqet', desc: 'AI ju njofton kur një klient bëhet rrezik i madh ose keni fatura të vonuara.' },
      { title: '📊 Parashikim i parasë', desc: 'Shikoni 90 ditë përpara — a do të keni mjaft para në llogari muajin e ardhshëm?' },
      { title: '🎯 Këshilla për profit', desc: '"Sa duhet t\'i rris çmimet për të pasur 500.000 profit?" — merrni plan konkret për çdo produkt.' }
    ]
  },
  featureGrid: {
    title: 'Gjithçka që ju nevojitet për të drejtuar biznesin tuaj',
    subtitle: 'Veçori të fuqishme të dizajnuara për bizneset dhe kontabilistët modernë maqedonas.',
    features: [
      { title: 'Gati për e‑Faturë', desc: 'Plotësisht në përputhje me rregulloret e reja qeveritare. Lidhuni menjëherë kur të hapet API.' },
      { title: 'Integrim bankar', desc: 'Lidhni llogaritë tuaja bankare lokale për përditësime transaksionesh në kohë reale.' },
      { title: 'Siguri në nivel bankar', desc: 'Të dhënat tuaja janë të enkriptuara dhe ruhen në mënyrë të sigurt në qendrat e të dhënave të BE-së.' },
      { title: 'Multi-klient', desc: 'Perfekt për kontabilistët që menaxhojnë kompani të shumta klientësh nga një vend.' }
    ]
  },
  socialProof: { trustedBy: 'E besuar nga kontabilistët dhe bizneset e vogla' },
  whyDifferent: {
    title: 'Pse Facturino është ndryshe nga çdo softuer tjetër në Maqedoni',
    cards: [
      { title: 'Faturim & kodim me AI', body: 'Sugjerime të mençura për TVSH dhe llogari për çdo rresht – ju konfirmoni.' },
      { title: 'Gati për e‑Faturë', body: 'Modeli mbështet të gjithë strukturën; lidhemi sapo UJP hapë API + QES.' },
      { title: 'Banka PSD2', body: 'Ekstraktet hyjnë direkt në Facturino dhe pajtimi gjysmë‑automatik.' },
      { title: 'Shumë‑klientë për zyra', body: 'Një hyrje, shumë kompani, llogari/raporte/rollet të ndara.' },
      { title: 'Raporte IFRS', body: 'Paketa IFRS e integruar për raporte profesionale.' },
      { title: 'Siguri', body: 'Strehim në BE, enkriptim, kopje rezervë dhe audit‑trail.' }
    ]
  },
  benefits: {
    title: 'Përfitime',
    badge: 'Avantazhet',
    cards: [
      { title: 'Kurseni kohë', body: 'Mbyllni fund‑muajin për orë, jo ditë.' },
      { title: 'Punoni më shpejt', body: 'Onboard‑oni një klient të ri në një pasdite.' },
      { title: 'Bëhuni gati', body: 'Përgatisni fatura gati për e‑Faturë që nga dita e parë.' }
    ]
  },
  how: {
    title: 'Si funksionon',
    process: 'Procesi',
    subtitle: 'Filloni për minuta, jo ditë.',
    steps: [
      { title: '1. Lidh kompaninë', body: 'Aktivizo e‑Faturën dhe rregullimet e TVSH/llogarive.' },
      { title: '2. Krijo faturë', body: 'AI sugjeron TVSH dhe llogari; ju konfirmoni dhe dërgoni.' },
      { title: '3. Pajtimi', body: 'Importoni ekstraktin dhe pajtoni në minuta, jo orë.' }
    ]
  },
  partners: { title: 'E besuar nga', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Planet',
    cta: 'Shiko çmimet',
    plans: [
      { name: 'Starter', bullets: ['Gati për e‑Faturë', '1 përdorues', 'AI (bazë)'] },
      { name: 'Pro', bullets: ['Shumë përdorues/role', 'Ekstrakte PSD2', 'Automatizime'] },
      { name: 'Business', bullets: ['Shumë kompani', 'API & leje të avancuara', 'SLA'] }
    ]
  },
  testimonials: {
    title: 'I dashur nga klientët',
    subtitle: 'Bashkohuni me qindra kontabilistë dhe pronarë biznesesh të kënaqur.',
    items: [
      { quote: 'Mbyllja e fund‑muajit ra nga 3 ditë në disa orë.', author: 'Arta, kontabiliste' },
      { quote: 'Ekstraktet hyjnë direkt dhe përputhja është e lehtë.', author: 'Blerim, pronar biznesi' },
      { quote: 'Integrimi i e‑Faturës është i përkryer. Kemi kursyer shumë kohë.', author: 'Maria, drejtoreshë financiare' },
      { quote: 'AI më tha se 85% e të ardhurave vijnë nga një klient. Fillova menjëherë të kërkoj të rinj.', author: 'Stefan, pronar i biznesit të vogël' }
    ]
  },
  faq: {
    title: 'Pyetje të shpeshta',
    items: [
      { q: 'A jeni gati për e‑Faturë?', a: 'Po, modeli është ndërtuar mbi e‑faturë dhe lidhemi sapo UJP hap API + QES.' },
      { q: 'Si punon AI?', a: 'Sugjeron TVSH/llogari për çdo rresht — njeriu gjithmonë konfirmon.' },
      { q: 'A mbështesni PSD2?', a: 'Po, import ekstraktesh dhe pajtim gjysmë‑automatik.' },
      { q: 'Çfarë mund të pyes këshilltarin AI?', a: 'Çdo gjë për biznesin tuaj! "Kush më detyron?", "A jam profitabil?", "Çfarë nëse humb klientin më të madh?", "Si ta rris profitin?" — AI analizon të dhënat tuaja dhe jep përgjigje konkrete.' }
    ]
  },
  cta: { title: 'Gati? Fillo falas sot.', sub: 'Pa kartë krediti • Provë falas 14 ditë • Anulo në çdo kohë', button: 'Fillo falas' },
  footer: { rights: '© Facturino. Të gjitha të drejtat e rezervuara.' },
  featuresPage: {
    heroTitle: 'Veçori që nuk mund t'i anashkaloni',
    groups: [
      {
        title: 'Këshilltar Financiar AI',
        items: [
          '💬 Pyesni çdo gjë: "Kush më detyron?", "A jam profitabil?", "Si ta rris profitin?"',
          '⚠️ Paralajmërim i hershëm: AI ju njofton për rreziqet e varësisë nga klientët',
          '📊 Parashikim 90-ditor i fluksit të parasë',
          '🎯 Këshilla për optimizim të çmimeve dhe profitit',
          'Analizë e moshës së borxheve (AR Aging) me debitorët kryesorë',
          'Skenarë çfarë-nëse: "Çfarë nëse humb klientin më të madh?"'
        ]
      },
      {
        title: 'e‑Faturë & Pajtueshmëri',
        items: [
          'Të dhëna të strukturuara: NIPT, TVSH sipas normës, afate pagese',
          'Gati për lidhje sapo UJP hap API + QES',
          'PDF profesionale në stil maqedonas'
        ]
      },
      {
        title: 'Bankat & Flukset e parasë',
        items: [
          'Lidhje PSD2 me bankat lokale',
          'Import i ekstrakteve dhe pajtim gjysmë‑automatik',
          'CSV/MT940 si alternativë'
        ]
      },
      {
        title: 'Për kontabilistë',
        items: [
          'Një hyrje → shumë kompani',
          'Plane llogarish dhe raporte të ndara',
          'Role dhe leje, audit trail'
        ]
      },
      {
        title: 'Siguri & Kontroll',
        items: [
          'Enkriptim në transit dhe në pushim',
          'Kopje rezervë të rregullta dhe strehim në BE',
          'Gjurmë auditimi për veprimet kyçe'
        ]
      }
    ]
  },
  pricingPage: {
    h1: 'Çmimet',
    sub: 'Provë falas 14 ditë. Pa detyrim.',
    sectionCompany: 'Për kompani',
    sectionPartner: 'Për kontabilistë (partnerë)',
    popularBadge: 'Popullor',
    recommendedBadge: 'I rekomanduar',
    includesPrevious: 'Përfshin gjithçka në {plan}',
    companyPlans: [
      { name: 'Free', price: '€0', period: '/përgjithmonë', bullets: ['5 fatura/muaj', '1 përdorues', 'Eksport PDF', 'Shabllone bazë'], popular: false },
      { name: 'Starter', price: '€12', period: '/muaj', bullets: ['50 fatura/muaj', '1 përdorues', 'Klientë të pakufizuar', 'Email fatura'], popular: false },
      { name: 'Standard', price: '€29', period: '/muaj', bullets: ['200 fatura/muaj', '3 përdorues', 'Dërgim e‑Faturë', 'Nënshkrim QES', 'Import AI'], popular: true },
      { name: 'Business', price: '€59', period: '/muaj', bullets: ['1000 fatura/muaj', '5 përdorues', 'Ekstrakte bankare', 'Auto-kategorizim', 'Import AI i plotë'], popular: false },
      { name: 'Max', price: '€149', period: '/muaj', bullets: ['Çdo gjë e pakufizuar', 'Qasje API', 'Multi-lokacione', 'Raporte IFRS', 'Suport WhatsApp'], popular: false }
    ],
    partnerPlans: [
      { name: 'Partner', price: 'Falas', period: '', bullets: ['Klientë të pakufizuar', 'Portal partneri', 'Komision 20% rekurent', 'Ndjekje fitimesh'], popular: true },
      { name: 'Partner Plus', price: '€29', period: '/muaj', bullets: ['Gjithçka nga Partner', 'Faturim për zyrë', 'Raporte të avancuara', 'Komision 22%', 'Suport prioritar'], popular: false }
    ],
    cta: 'Fillo tani',
    ctaPartner: 'Bashkohu',
    comparisonTable: {
      title: 'Krahasoni paketat',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Fatura në muaj', values: ['5', '50', '200', '1000', 'Pa limit'] },
        { feature: 'Përdorues', values: ['1', '1', '3', '5', 'Pa limit'] },
        { feature: 'Klientë', values: ['Pa limit', 'Pa limit', 'Pa limit', 'Pa limit', 'Pa limit'] },
        { feature: 'e-Faturë (UJP)', values: [false, false, true, true, true] },
        { feature: 'Sugjerime AI', values: [false, 'Bazike', 'Standard', 'E avancuar', 'E avancuar'] },
        { feature: 'Ekstrakte bankare (PSD2)', values: [false, false, true, true, true] },
        { feature: 'Qasje API', values: [false, false, false, true, true] },
        { feature: 'Mbështetje', values: ['Email', 'Email', 'Email/Chat', 'Prioritet', 'WhatsApp'] }
      ]
    }
  }
}

const tr: Dictionary = {
  meta: {
    title: 'Facturino — Makedonya için en gelişmiş yapay zekâ ve e‑Fatura hazır platform',
    description:
      'AI + e‑Fatura hazır, muhasebeciler için. Çoklu müşteri, PSD2 bankalar, IFRS raporları.'
  },
  nav: {
    features: 'Özellikler',
    forAccountants: 'Muhasebeciler için',
    how: 'Nasıl çalışır',
    efaktura: 'e‑Fatura',
    pricing: 'Fiyatlar',
    security: 'Güvenlik',
    contact: 'İletişim',
    contactSales: 'Satışla İletişime Geçin',
    start: 'Ücretsiz başla',
    login: 'Giriş',
    language: 'Dil'
  },
  hero: {
    h1: 'Makedonya’daki en güçlü yapay zekâ destekli ve e‑Fatura’ya hazır muhasebe platformu.',
    sub:
      'Global yazılımlar seviyesinde güç, ama Makedonya’daki muhasebeciler için özel tasarlandı.',
    primaryCta: 'Ücretsiz başla',
    secondaryCta: 'Demo planla',
    claim:
      'Facturino, Makedonya için özel geliştirilmiş, en gelişmiş yapay zekâ destekli ve e‑Fatura’ya hazır muhasebe platformudur.',
    onlyPlatform:
      'Yapay zekâ, Makedonya muhasebe kuralları, e‑Fatura hazırlığı ve PSD2 banka bağlantılarını tek çatı altında birleştiren tek yerel platform.'
  },
  heroTagline: 'Beklediğiniz muhasebe yazılımı – nihayet burada.',
  aiSection: {
    badge: 'AI Mali Danışman',
    title: 'İşiniz hakkında her şeyi sorun',
    subtitle: 'Sadece otomasyon değil — verilerinizi analiz eden ve somut tavsiyeler veren kişisel bir mali danışman alırsınız.',
    features: [
      { title: '💬 AI ile Sohbet', desc: '"En çok borcu olan müşteri kim?" veya "Kârlı mıyım?" diye sorun — anında cevap alın.' },
      { title: '⚠️ Erken Risk Uyarısı', desc: 'AI, bir müşteri çok büyük risk haline geldiğinde veya gecikmiş faturalarınız olduğunda sizi bilgilendirir.' },
      { title: '📊 Nakit Tahmin', desc: '90 gün ileriye bakın — gelecek ay hesabınızda yeterli paranız olacak mı?' },
      { title: '🎯 Kâr Tavsiyeleri', desc: '"500.000 kâr için fiyatları ne kadar artırmalıyım?" — ürün bazında somut plan alın.' }
    ]
  },
  featureGrid: {
    title: 'İşinizi yürütmek için ihtiyacınız olan her şey',
    subtitle: 'Modern Makedon işletmeleri ve muhasebeciler için tasarlanmış güçlü özellikler.',
    features: [
      { title: 'e-Fatura hazır', desc: 'Yeni hükümet düzenlemelerine tam uyumlu. API açıldığında anında bağlanın.' },
      { title: 'Banka entegrasyonu', desc: 'Gerçek zamanlı işlem güncellemeleri için yerel banka hesaplarınızı bağlayın.' },
      { title: 'Banka seviyesinde güvenlik', desc: 'Verileriniz şifrelenir ve AB merkezli veri merkezlerinde güvenle saklanır.' },
      { title: 'Çoklu kiracı', desc: 'Birden fazla müşteri şirketini tek yerden yöneten muhasebeciler için mükemmel.' }
    ]
  },
  socialProof: { trustedBy: 'Muhasebeciler ve KOBİ’ler tarafından güveniliyor' },
  whyDifferent: {
    title: 'Facturino neden Makedonya’daki diğer yazılımlardan farklı',
    cards: [
      { title: 'AI faturalama ve kodlama', body: 'Her satır için KDV ve hesap önerileri – onay sizde.' },
      { title: 'e‑Fatura hazır', body: 'Model tüm yapıyı destekler; UJP üretim API + QES açılır açılmaz bağlanırız.' },
      { title: 'PSD2 bankalar', body: 'Ekstreler doğrudan Facturino’ya iner; yarı otomatik mutabakat.' },
      { title: 'Muhasebe ofisleri için çoklu müşteri', body: 'Tek giriş, çok şirket, ayrı hesaplar/raporlar/yetkiler.' },
      { title: 'IFRS raporları', body: 'Arka planda IFRS paketiyle profesyonel raporlar.' },
      { title: 'Güvenlik', body: 'AB bölgesi barındırma, şifreleme, yedekler ve işlem günlükleri.' }
    ]
  },
  benefits: {
    title: 'Faydalar',
    badge: 'Avantajlar',
    cards: [
      { title: 'Zaman Kazanın', body: 'Aylık kapanışı günler değil saatlerde bitirin.' },
      { title: 'Daha Hızlı Çalışın', body: 'Yeni bir müşteriyi bir öğleden sonra devreye alın.' },
      { title: 'Hazır Olun', body: 'İlk günden e‑Fatura’ya hazır faturalar hazırlayın.' }
    ]
  },
  how: {
    title: 'Nasıl çalışır',
    process: 'Süreç',
    subtitle: 'Dakikalar içinde başlayın, günler değil.',
    steps: [
      { title: '1. Şirketi bağlayın', body: 'e‑Fatura’yı ve KDV/hesap ayarlarını etkinleştirin.' },
      { title: '2. Fatura oluşturun', body: 'AI KDV ve hesap önerir; siz onaylayıp gönderirsiniz.' },
      { title: '3. Mutabakat', body: 'Ekstreyi içe aktarın ve dakikalarda eşleştirin.' }
    ]
  },
  partners: { title: 'Güvenilen', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Paketler',
    cta: 'Fiyatları gör',
    plans: [
      { name: 'Starter', bullets: ['e‑Fatura hazır', '1 kullanıcı', 'AI (temel)'] },
      { name: 'Pro', bullets: ['Çoklu kullanıcı/roller', 'PSD2 ekstreleri', 'Otomasyonlar'] },
      { name: 'Business', bullets: ['Çok şirket', 'API ve gelişmiş yetkiler', 'SLA'] }
    ]
  },
  testimonials: {
    title: 'Müşteriler tarafından seviliyor',
    subtitle: 'Yüzlerce memnun muhasebeci ve işletme sahibine katılın.',
    items: [
      { quote: 'Ay sonu kapanışı 3 günden birkaç saate düştü.', author: 'Selin, muhasebeci' },
      { quote: 'Ekstreler doğrudan iniyor ve uzlaştırma kolay.', author: 'Emir, işletme sahibi' },
      { quote: 'e‑Fatura entegrasyonu mükemmel. Çok zaman kazandık.', author: 'Maria, mali müdür' },
      { quote: 'AI gelirimin %85\'inin tek müşteriden geldiğini söyledi. Hemen yeni müşteri aramaya başladım.', author: 'Stefan, küçük işletme sahibi' }
    ]
  },
  faq: {
    title: 'SSS',
    items: [
      { q: 'e‑Fatura'ya hazır mısınız?', a: 'Evet, model e‑fatura verileriyle kurulu; UJP üretim API + QES açılınca bağlanıyoruz.' },
      { q: 'AI nasıl çalışır?', a: 'Her satır için KDV/hesap önerir — onay sizde.' },
      { q: 'PSD2 destekliyor musunuz?', a: 'Evet, ekstre içe aktarma ve yarı otomatik mutabakat.' },
      { q: 'AI danışmana ne sorabilirim?', a: 'İşinizle ilgili her şey! "Kim borçlu?", "Kârlı mıyım?", "En büyük müşteriyi kaybedersem ne olur?", "Kârı nasıl artırabilirim?" — AI verilerinizi analiz eder ve somut cevaplar verir.' }
    ]
  },
  cta: { title: 'Hazır mısınız? Bugün ücretsiz başlayın.', sub: 'Kredi kartı gerekmez • 14 gün ücretsiz deneme • İstediğiniz zaman iptal edin', button: 'Ücretsiz başla' },
  footer: { rights: '© Facturino. Tüm hakları saklıdır.' },
  featuresPage: {
    heroTitle: 'Gözden kaçırılmayacak özellikler',
    groups: [
      {
        title: 'AI Mali Danışman',
        items: [
          '💬 Her şeyi sorun: "Kim borçlu?", "Kârlı mıyım?", "Kârı nasıl artırabilirim?"',
          '⚠️ Erken uyarı: AI müşteri bağımlılığı risklerini bildirir',
          '📊 90 günlük nakit akışı tahmini',
          '🎯 Fiyat ve kâr optimizasyonu tavsiyeleri',
          'Alacak yaşlandırma analizi (AR Aging) ile en büyük borçlular',
          'Ya olursa senaryoları: "En büyük müşteriyi kaybedersem ne olur?"'
        ]
      },
      {
        title: 'e‑Fatura ve uyum',
        items: [
          'Yapılandırılmış veri: vergi numaraları, oran bazında KDV, ödeme şartları',
          'UJP üretim API + QES açılınca bağlanmaya hazır',
          'Makedon stilinde profesyonel PDF şablonları'
        ]
      },
      {
        title: 'Bankacılık ve nakit akışı',
        items: [
          'Yerel bankalara PSD2 bağlantılar',
          'Ekstre içe aktarımı ve yarı otomatik mutabakat',
          'Alternatif olarak CSV/MT940'
        ]
      },
      {
        title: 'Muhasebeciler için',
        items: [
          'Tek giriş → birden çok şirket',
          'Ayrı hesap planları ve raporlar',
          'Roller, izinler ve işlem günlükleri'
        ]
      },
      {
        title: 'Güvenlik ve kontrol',
        items: [
          'Aktarımda ve depoda şifreleme',
          'Düzenli yedekler ve AB bölgesi barındırma',
          'Kritik işlemler için audit logları'
        ]
      }
    ]
  },
  pricingPage: {
    h1: 'Fiyatlar',
    sub: '14 gün ücretsiz deneme. Taahhüt yok.',
    sectionCompany: 'Şirketler için',
    sectionPartner: 'Muhasebeciler için (iş ortakları)',
    popularBadge: 'Popüler',
    recommendedBadge: 'Önerilen',
    includesPrevious: '{plan} paketindeki her şey dahil',
    companyPlans: [
      { name: 'Free', price: '€0', period: '/süresiz', bullets: ['5 fatura/ay', '1 kullanıcı', 'PDF dışa aktarma', 'Temel şablonlar'], popular: false },
      { name: 'Starter', price: '€12', period: '/ay', bullets: ['50 fatura/ay', '1 kullanıcı', 'Sınırsız müşteri', 'Email fatura'], popular: false },
      { name: 'Standard', price: '€29', period: '/ay', bullets: ['200 fatura/ay', '3 kullanıcı', 'e‑Fatura gönderim', 'QES imza', 'AI içe aktarma'], popular: true },
      { name: 'Business', price: '€59', period: '/ay', bullets: ['1000 fatura/ay', '5 kullanıcı', 'Banka ekstreleri', 'Otomatik kategorizasyon', 'Tam AI içe aktarma'], popular: false },
      { name: 'Max', price: '€149', period: '/ay', bullets: ['Her şey sınırsız', 'API erişimi', 'Çoklu lokasyon', 'IFRS raporları', 'WhatsApp destek'], popular: false }
    ],
    partnerPlans: [
      { name: 'Partner', price: 'Ücretsiz', period: '', bullets: ['Sınırsız müşteri', 'Partner portalı', '%20 tekrarlayan komisyon', 'Kazanç takibi'], popular: true },
      { name: 'Partner Plus', price: '€29', period: '/ay', bullets: ['Partner\'ın tümü', 'Ofis için faturalama', 'Gelişmiş raporlar', '%22 komisyon', 'Öncelikli destek'], popular: false }
    ],
    cta: 'Şimdi başla',
    ctaPartner: 'Katıl',
    comparisonTable: {
      title: 'Paketleri karşılaştırın',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Aylık Fatura', values: ['5', '50', '200', '1000', 'Sınırsız'] },
        { feature: 'Kullanıcı', values: ['1', '1', '3', '5', 'Sınırsız'] },
        { feature: 'Müşteri', values: ['Sınırsız', 'Sınırsız', 'Sınırsız', 'Sınırsız', 'Sınırsız'] },
        { feature: 'e-Fatura (UJP)', values: [false, false, true, true, true] },
        { feature: 'AI Önerileri', values: [false, 'Temel', 'Standart', 'Gelişmiş', 'Gelişmiş'] },
        { feature: 'Banka Ekstreleri (PSD2)', values: [false, false, true, true, true] },
        { feature: 'API Erişimi', values: [false, false, false, true, true] },
        { feature: 'Destek', values: ['Email', 'Email', 'Email/Chat', 'Öncelikli', 'WhatsApp'] }
      ]
    }
  }
}

export async function getDictionary(locale: Locale): Promise<Dictionary> {
  switch (locale) {
    case 'mk':
      return mk
    case 'sq':
      return sq
    case 'tr':
      return tr
  }
}
