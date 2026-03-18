import { Locale } from './locales'

export type FeatureCard = { title: string; body: string }

export type Dictionary = {
  meta: { title: string; description: string }
  nav: {
    features: string
    forAccountants?: string
    how: string
    efaktura: string
    integrations?: string
    pricing: string
    security: string
    contact: string
    contactSales: string
    start: string
    login: string
    language: string
    blog?: string
    showcase?: string
  }
  hero: { h1: string; sub: string; primaryCta: string; secondaryCta: string; claim: string; onlyPlatform: string }
  // extra tagline line for hero
  heroTagline?: string
  aiSection: { badge: string; title: string; subtitle: string; features: { title: string; desc: string }[] }
  featureGrid: { title: string; subtitle: string; features: { title: string; desc: string }[] }
  socialProof: { stat: string; freeTrial: string; noCreditCard: string }
  whyDifferent: { title: string; cards: FeatureCard[] }
  benefits: { title: string; badge: string; cards: { title: string; body: string }[] }
  how: { title: string; process: string; subtitle: string; steps: { title: string; body: string }[] }
  cta: { title: string; sub?: string; button: string }
  footer: { rights: string }
  partners?: { title: string; subtitle: string; logos: string[] }
  pricingPreview?: { title: string; cta: string; plans: { name: string; price?: string; bullets: string[]; popular?: boolean }[] }
  testimonials?: { title: string; subtitle: string; items: { quote: string; author: string }[] }
  faq?: { title: string; subtitle?: string; items: { q: string; a: string }[] }
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
    partnerSubtitle: string
    cta: string
    ctaPartner: string
    sepaNote: string
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
    title: 'Facturino — AI сметководство и е-Фактура за Македонија',
    description:
      'AI сметководствена платформа подготвена за е-Фактура. Повеќе клиенти, банкарски увоз, IFRS извештаи. Започнете бесплатно за 14 дена.'
  },
  nav: {
    features: 'Функции',
    forAccountants: 'За сметководители',
    how: 'Како работи',
    efaktura: 'Е‑Фактура',
    integrations: 'Интеграции',
    pricing: 'Цени',
    security: 'Безбедност',
    contact: 'Контакт',
    contactSales: 'Контакт со продажба',
    start: 'Започни бесплатно',
    login: 'Пријава',
    language: 'Јазик',
    blog: 'Блог',
    showcase: 'Преглед',
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
      'Единствена локална платформа што комбинира AI скенирање на документи, банкарски увоз (CSV/MT940/PDF), е‑Фактура и гласовни команди — прикачи фактура, AI ја чита и книжи за секунда.'
  },
  heroTagline: 'Сметководствениот софтвер што го очекувавте – конечно е тука.',
  aiSection: {
    badge: 'AI Финансиски Советник',
    title: 'Прашај ме било што за твојот бизнис',
    subtitle: 'Не само автоматизација — добиваш личен финансиски советник кој ги анализира твоите податоци и дава конкретни совети на македонски.',
    features: [
      { title: 'Разговарај со AI', desc: 'Прашај "Кој клиент ми должи најмногу?" или "Дали сум профитабилен?" — добиј одговор веднаш.' },
      { title: 'Рано предупредување за ризици', desc: 'AI те известува кога еден клиент станува преголем ризик или кога имаш задоцнети фактури.' },
      { title: 'Прогноза на готовина', desc: 'Гледај 90 дена напред — дали ќе имаш доволно пари на сметка следниот месец?' },
      { title: 'Совети за профит', desc: '"Колку да ги зголемам цените за да имам 500.000 профит?" — добиј конкретен план по артикли.' },
      { title: 'Кажи и креирај', desc: 'Напиши „Фактура за Марков, 5 часа по 3000" — AI ја креира за секунда. Ти само потврди.' },
      { title: 'Скенирај и внеси', desc: 'Прикачи PDF фактура — AI ја чита, ги извлекува ставките и ја внесува како сметка.' }
    ]
  },
  featureGrid: {
    title: 'Сè што ви треба за водење на вашиот бизнис',
    subtitle: 'Моќни функции дизајнирани за современи македонски бизниси и сметководители.',
    features: [
      { title: 'Подготвено за е‑Фактура', desc: 'Целосно усогласено со новите владини прописи. Поврзете се веднаш кога ќе се отвори API.' },
      { title: 'Банкарски увоз', desc: 'Увезете банкарски изводи (CSV/MT940/PDF) и порамнете со фактури полуавтоматски.' },
      { title: 'AI скенирање на документи', desc: 'Прикачи PDF или слика на фактура — AI ја чита, ги извлекува ставките, износите и ДДВ, и ја книжи автоматски.' },
      { title: 'Мулти‑клиент', desc: 'Совршено за сметководители кои управуваат со повеќе клиентски компании од едно место.' }
    ]
  },
  socialProof: { stat: '500+ сметководители веќе го користат Facturino', freeTrial: 'Бесплатен пробен период', noCreditCard: 'Без кредитна картичка' },
  whyDifferent: {
    title: 'Зошто Facturino е различен од било кој софтвер во Македонија',
    cards: [
      { title: 'AI скенирање на документи', body: 'Прикачи PDF/слика — AI ја чита фактурата, ги извлекува ставките, износите и ДДВ. Потврди со еден клик — книжењето е готово.' },
      { title: 'Е‑Фактура подготвен', body: 'Моделот веќе ги поддржува сите структури; поврзување штом UJP отвори продукциски API + QES.' },
      { title: 'Банкарски увоз', body: 'CSV/MT940/PDF увоз на изводи и полуавтоматско порамнување со фактури. PSD2 наскоро.' },
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
  partners: { title: 'Поврзано со', subtitle: 'Банкарски увоз (CSV/MT940/PDF)', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Пакети',
    cta: 'Види ги цените',
    plans: [
      { name: 'Starter', price: '740 ден/мес', bullets: ['Е‑Фактура подготвено', 'AI асистент', '25 AI прашања/месец'] },
      { name: 'Standard', price: '2,400 ден/мес', bullets: ['Мулти‑корисници', 'AI документ хаб', 'Автоматизации'], popular: true },
      { name: 'Business', price: '3,630 ден/мес', bullets: ['AI рекончилијација', 'Банкарски увоз', 'API и SLA'] }
    ]
  },
  faq: {
    title: 'ЧПП',
    subtitle: 'Најчести прашања за Facturino.',
    items: [
      { q: 'Дали сте подготвени за е‑Фактура?', a: 'Да, моделот е изграден околу е‑фактури и се поврзуваме штом UJP отвори продукциски API + QES.' },
      { q: 'Како функционира AI?', a: 'Предлага ДДВ/конта по ставка — човек секогаш потврдува/уредува.' },
      { q: 'Поддржувате ли банкарски увоз?', a: 'Да, CSV/MT940/PDF увоз на изводи и полуавтоматско порамнување. PSD2 директни конекции — наскоро.' },
      { q: 'Што може да го прашам AI советникот?', a: 'Било што за твојот бизнис! "Кој ми должи?", "Дали сум профитабилен?", "Што ако го изгубам најголемиот клиент?", "Како да го зголемам профитот?" — AI ги анализира твоите податоци и дава конкретни одговори на македонски.' },
      { q: 'Што е AI Документ Хаб?', a: 'Прикачете PDF или слика — AI ја класифицира, ги извлекува ставките, и со еден клик се книжи. Поддржува сметки, фактури, трошоци, банкарски трансакции и даночни обрасци.' },
      { q: 'Како работи AI Асистентот?', a: 'Напишете „Фактура за Марков 3000 ден" и AI ја креира за секунда. Поддржува фактури, сметки, трошоци и плаќања. AI го препознава клиентот и артиклите од вашата база.' }
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
          'Прашај било што: "Кој ми должи?", "Дали сум профитабилен?", "Како да го зголемам профитот?"',
          'Рано предупредување: AI те известува за ризици од зависност од клиенти',
          '90-дневна прогноза на паричен тек',
          'Совети за оптимизација на цени и профит',
          'Анализа на старост на побарувања (AR Aging) со топ должници',
          'Што-ако сценарија: "Што ако го изгубам најголемиот клиент?"'
        ]
      },
      {
        title: 'AI Документ Хаб',
        items: [
          'Прикачи PDF или слика — AI автоматски ја класифицира (сметка, фактура, трошок, банкарски извод, даночен образец)',
          'Извлекување на износи, датуми, ставки и ДДВ со Gemini Vision',
          'Преглед и корекција пред книжење — со еден клик потврди',
          'Поддржува 7 типови ентитети: сметки, фактури, трошоци, банкарски трансакции, артикли, даночни обрасци, договори',
          'Документот останува прикачен кон креираниот ентитет за лесна ревизија'
        ]
      },
      {
        title: 'Кажи и креирај (AI Асистент)',
        items: [
          'Напиши „Фактура за Марков, 5 часа по 3000" — AI ја креира за секунда',
          'Поддржува фактури, сметки, трошоци и плаќања на македонски и англиски',
          'AI го препознава клиентот, добавувачот и артиклите од вашата база',
          'Прашања како „Колку неплатени фактури имам?" добиваат одговор веднаш',
          'Нацрт систем: прегледај и потврди пред книжење',
          'Ако нешто е нејасно, AI бара појаснување — никогаш не креира погрешно'
        ]
      },
      {
        title: 'AI Банковно порамнување',
        items: [
          'Gemini AI ги споредува банкарските трансакции со фактури — вклучувајќи кирилични имиња',
          'Детекција на делумни плаќања и споени трансакции (split detection)',
          'Автоматска категоризација: плата, данок, кирија, добавувач, банкарска провизија и др.',
          '4-слоен pipeline: правила → детерминистичко → AI подобрување → AI категоризација',
          'Виолетови значки за AI совпаѓања — транспарентно и ревидибилно'
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
          'Увоз на банкарски изводи (CSV/MT940/PDF)',
          'Полуавтоматско порамнување со фактури',
          'PSD2 директни конекции (наскоро)'
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
      { name: 'Free', price: '0 ден', period: '/засекогаш', bullets: ['3 фактури/месец', '1 корисник', 'PDF извоз', '10 AI прашања/месец'], popular: false },
      { name: 'Starter', price: '740 ден', period: '/месец', bullets: ['30 фактури/месец', '1 корисник', 'Е‑Фактура (5/месец)', '25 AI прашања/месец', 'AI асистент'], popular: false },
      { name: 'Standard', price: '2,400 ден', period: '/месец', bullets: ['60 фактури/месец', '3 корисници', 'Е‑Фактура + QES (неограничено)', '75 AI прашања/месец', 'AI документ хаб'], popular: true },
      { name: 'Business', price: '3,630 ден', period: '/месец', bullets: ['150 фактури/месец', '5 корисници', 'Банкарски увоз (CSV/MT940/PDF)', '200 AI прашања/месец', 'AI рекончилијација'], popular: false },
      { name: 'Max', price: '9,170 ден', period: '/месец', bullets: ['Неограничено фактури', 'Неограничено корисници', '500 AI прашања/месец', 'Сите AI функции', 'IFRS извештаи'], popular: false }
    ],
    partnerPlans: [
      { name: 'Start', price: '1,784 ден', period: '/месец', bullets: ['15 компании', '50 AI кредити/месец', '3 банкарски сметки', '10 вработени', '5 е-Фактури/месец'], popular: false },
      { name: 'Office', price: '3,629 ден', period: '/месец', bullets: ['50 компании', '200 AI кредити/месец', '10 банкарски сметки', '50 вработени', '50 е-Фактури/месец'], popular: true },
      { name: 'Pro', price: '6,089 ден', period: '/месец', bullets: ['150 компании', '500 AI кредити/месец', '30 банкарски сметки', '200 вработени', 'Неограничено е-Фактури'], popular: false },
      { name: 'Elite', price: '12,239 ден', period: '/месец', bullets: ['Неограничено компании', 'Неограничено AI кредити', 'Неограничено банкарски сметки', 'Неограничено вработени', 'Неограничено е-Фактури'], popular: false },
    ],
    partnerSubtitle: 'За сметководствени канцеларии · + 308 ден/додатна лиценца',
    cta: 'Започни сега',
    ctaPartner: 'Пробај 30 дена бесплатно',
    sepaNote: 'Немате картичка? Изберете EUR за плаќање преку банкарски трансфер (SEPA).',
    comparisonTable: {
      title: 'Споредете ги пакетите',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Фактури месечно', values: ['3', '30', '60', '150', 'Неограничено'] },
        { feature: 'Корисници', values: ['1', '1', '3', '5', 'Неограничено'] },
        { feature: 'Клиенти', values: ['Неограничено', 'Неограничено', 'Неограничено', 'Неограничено', 'Неограничено'] },
        { feature: 'Е-Фактура (UJP)', values: [false, '5/месец', 'Неограничено', 'Неограничено', 'Неограничено'] },
        { feature: 'QES потпис', values: [false, false, true, true, true] },
        { feature: 'AI Прашања/месец', values: ['10', '25', '75', '200', '500'] },
        { feature: 'AI Документ Хаб', values: ['Класификација', 'Извлекување', true, true, true] },
        { feature: 'AI Асистент', values: [false, '5/месец', '25/месец', true, true] },
        { feature: 'AI Порамнување', values: [false, false, 'Предлози', true, true] },
        { feature: 'Банкарски увоз (CSV/MT940/PDF)', values: [false, false, false, true, true] },
        { feature: 'Авто-рекончилијација', values: [false, false, false, true, true] },
        { feature: 'API Пристап', values: [false, false, false, true, true] },
        { feature: 'Поддршка', values: ['Email', 'Email', 'Email/Chat', 'Приоритетна', 'WhatsApp'] }
      ]
    }
  }
}

const sq: Dictionary = {
  meta: {
    title: 'Facturino — Platformë AI kontabiliteti, gati për e-Faturë',
    description:
      'Platformë kontabiliteti me AI, gati për e-Faturë. Shumë-klientë, import bankar, raporte IFRS. Filloni provën falas 14-ditore tani.'
  },
  nav: {
    features: 'Veçoritë',
    forAccountants: 'Për kontabilistë',
    how: 'Si funksionon',
    efaktura: 'e‑Faturë',
    integrations: 'Integrimet',
    pricing: 'Çmimet',
    security: 'Siguria',
    contact: 'Kontakti',
    contactSales: 'Kontakto shitjet',
    start: 'Fillo falas',
    login: 'Hyr',
    language: 'Gjuha',
    blog: 'Blog',
    showcase: 'Permbledhje',
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
      'E vetmja platformë lokale që kombinon AI skanim dokumentesh, import bankar (CSV/MT940/PDF), e‑Faturë dhe komanda zanore — bashkangjitni faturë, AI e lexon dhe e regjistron për një sekondë.'
  },
  heroTagline: 'Softueri i kontabilitetit që keni pritur – më në fund është këtu.',
  aiSection: {
    badge: 'Këshilltar Financiar AI',
    title: 'Pyetni çdo gjë për biznesin tuaj',
    subtitle: 'Jo vetëm automatizim — merrni një këshilltar financiar personal që analizon të dhënat tuaja dhe jep këshilla konkrete.',
    features: [
      { title: 'Bisedoni me AI', desc: 'Pyesni "Cili klient më detyron më shumë?" ose "A jam profitabil?" — merrni përgjigje menjëherë.' },
      { title: 'Paralajmërim i hershëm për rreziqet', desc: 'AI ju njofton kur një klient bëhet rrezik i madh ose keni fatura të vonuara.' },
      { title: 'Parashikim i parasë', desc: 'Shikoni 90 ditë përpara — a do të keni mjaft para në llogari muajin e ardhshëm?' },
      { title: 'Këshilla për profit', desc: '"Sa duhet t\'i rris çmimet për të pasur 500.000 profit?" — merrni plan konkret për çdo produkt.' },
      { title: 'Thuaj dhe krijo', desc: 'Shkruaj "Faturë për Markov, 5 orë nga 3000" — AI e krijon për një sekondë. Ju vetëm konfirmoni.' },
      { title: 'Skanoni dhe regjistroni', desc: 'Bashkangjitni PDF faturë — AI e lexon, nxjerr zërat dhe e regjistron si llogari.' }
    ]
  },
  featureGrid: {
    title: 'Gjithçka që ju nevojitet për të drejtuar biznesin tuaj',
    subtitle: 'Veçori të fuqishme të dizajnuara për bizneset dhe kontabilistët modernë maqedonas.',
    features: [
      { title: 'Gati për e‑Faturë', desc: 'Plotësisht në përputhje me rregulloret e reja qeveritare. Lidhuni menjëherë kur të hapet API.' },
      { title: 'Import bankar', desc: 'Importoni ekstrakte bankare (CSV/MT940/PDF) dhe pajtoni me fatura gjysmë-automatikisht.' },
      { title: 'AI skanim dokumentesh', desc: 'Bashkangjitni PDF ose imazh fature — AI e lexon, nxjerr zërat, shumat dhe TVSH, dhe e regjistron automatikisht.' },
      { title: 'Multi-klient', desc: 'Perfekt për kontabilistët që menaxhojnë kompani të shumta klientësh nga një vend.' }
    ]
  },
  socialProof: { stat: '500+ kontabilistë tashmë e përdorin Facturino', freeTrial: 'Provë falas', noCreditCard: 'Pa kartë krediti' },
  whyDifferent: {
    title: 'Pse Facturino është ndryshe nga çdo softuer tjetër në Maqedoni',
    cards: [
      { title: 'AI skanim dokumentesh', body: 'Bashkangjitni PDF/imazh — AI lexon faturën, nxjerr zërat, shumat dhe TVSH. Konfirmoni me një klik — regjistrimi është gati.' },
      { title: 'Gati për e‑Faturë', body: 'Modeli mbështet të gjithë strukturën; lidhemi sapo UJP hapë API + QES.' },
      { title: 'Import bankar', body: 'CSV/MT940/PDF import i ekstrakteve dhe pajtim gjysmë‑automatik. PSD2 së shpejti.' },
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
  partners: { title: 'E lidhur me', subtitle: 'Import bankar (CSV/MT940/PDF)', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Planet',
    cta: 'Shiko çmimet',
    plans: [
      { name: 'Starter', price: '740 ден/muaj', bullets: ['Gati për e‑Faturë', 'Asistent AI', '25 pyetje AI/muaj'] },
      { name: 'Standard', price: '2,400 ден/muaj', bullets: ['Shumë përdorues', 'AI dokument hub', 'Automatizime'], popular: true },
      { name: 'Business', price: '3,630 ден/muaj', bullets: ['AI rekonçilim', 'Import bankar', 'API dhe SLA'] }
    ]
  },
  faq: {
    title: 'Pyetje të shpeshta',
    subtitle: 'Pyetjet më të shpeshta rreth Facturino.',
    items: [
      { q: 'A jeni gati për e‑Faturë?', a: 'Po, modeli është ndërtuar mbi e‑faturë dhe lidhemi sapo UJP hap API + QES.' },
      { q: 'Si punon AI?', a: 'Sugjeron TVSH/llogari për çdo rresht — njeriu gjithmonë konfirmon.' },
      { q: 'A mbështesni import bankar?', a: 'Po, CSV/MT940/PDF import i ekstrakteve dhe pajtim gjysmë‑automatik. PSD2 lidhje direkte — së shpejti.' },
      { q: 'Çfarë mund të pyes këshilltarin AI?', a: 'Çdo gjë për biznesin tuaj! "Kush më detyron?", "A jam profitabil?", "Çfarë nëse humb klientin më të madh?", "Si ta rris profitin?" — AI analizon të dhënat tuaja dhe jep përgjigje konkrete.' },
      { q: 'Çfarë është AI Dokument Hub?', a: 'Bashkangjitni PDF ose imazh — AI e klasifikon, nxjerr zërat, dhe me një klik regjistrohet. Mbështet fatura, shpenzime, transaksione bankare dhe formularë tatimorë.' },
      { q: 'Si punon Asistenti AI?', a: 'Shkruaj "Faturë për Markov 3000 den" dhe AI e krijon për një sekondë. Mbështet fatura, llogari, shpenzime dhe pagesa. AI njeh klientin dhe artikujt nga baza juaj.' }
    ]
  },
  cta: { title: 'Gati? Fillo falas sot.', sub: 'Pa kartë krediti • Provë falas 14 ditë • Anulo në çdo kohë', button: 'Fillo falas' },
  footer: { rights: '© Facturino. Të gjitha të drejtat e rezervuara.' },
  featuresPage: {
    heroTitle: "Veçori që nuk mund t'i anashkaloni",
    groups: [
      {
        title: 'Këshilltar Financiar AI',
        items: [
          'Pyesni çdo gjë: "Kush më detyron?", "A jam profitabil?", "Si ta rris profitin?"',
          'Paralajmërim i hershëm: AI ju njofton për rreziqet e varësisë nga klientët',
          'Parashikim 90-ditor i fluksit të parasë',
          'Këshilla për optimizim të çmimeve dhe profitit',
          'Analizë e moshës së borxheve (AR Aging) me debitorët kryesorë',
          'Skenarë çfarë-nëse: "Çfarë nëse humb klientin më të madh?"'
        ]
      },
      {
        title: 'AI Dokument Hub',
        items: [
          'Bashkangjitni PDF ose imazh — AI e klasifikon automatikisht (faturë, shpenzim, transaksion bankar, formular tatimor)',
          'Nxjerrje e shumave, datave, zërave dhe TVSH me Gemini Vision',
          'Rishikim dhe korrigjim para regjistrimit — konfirmoni me një klik',
          'Mbështet 7 lloje entitetesh: fatura, shpenzime, transaksione bankare, artikuj, formularë tatimorë, kontrata',
          'Dokumenti qëndron i bashkangjitur me entitetin e krijuar për revizion të lehtë'
        ]
      },
      {
        title: 'Thuaj dhe krijo (Asistent AI)',
        items: [
          'Shkruaj "Faturë për Markov, 5 orë nga 3000" — AI e krijon për një sekondë',
          'Mbështet fatura, llogari, shpenzime dhe pagesa në maqedonisht dhe anglisht',
          'AI njeh klientin, furnizuesin dhe artikujt nga baza juaj',
          'Pyetje si "Sa fatura të papaguara kam?" marrin përgjigje menjëherë',
          'Sistem drafti: rishikoni dhe konfirmoni para regjistrimit',
          'Nëse diçka është e paqartë, AI kërkon sqarim — nuk krijon gabimisht'
        ]
      },
      {
        title: 'AI Rekonçilim Bankar',
        items: [
          'Gemini AI krahason transaksionet bankare me fatura — përfshirë emra cirilike',
          'Detektim i pagesave të pjesshme dhe transaksioneve të bashkuara (split detection)',
          'Kategorizim automatik: pagë, tatim, qira, furnizues, komision bankar etj.',
          'Pipeline 4-shtresore: rregulla → deterministik → përmirësim AI → kategorizim AI',
          'Shenja vjollcë për përputhje AI — transparente dhe e auditueshme'
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
          'Import i ekstrakteve bankare (CSV/MT940/PDF)',
          'Pajtim gjysmë‑automatik me fatura',
          'PSD2 lidhje direkte (së shpejti)'
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
      { name: 'Free', price: '0 den', period: '/përgjithmonë', bullets: ['3 fatura/muaj', '1 përdorues', 'Eksport PDF', '10 pyetje AI/muaj'], popular: false },
      { name: 'Starter', price: '740 den', period: '/muaj', bullets: ['30 fatura/muaj', '1 përdorues', 'e‑Faturë (5/muaj)', '25 pyetje AI/muaj', 'Asistent AI'], popular: false },
      { name: 'Standard', price: '2,400 den', period: '/muaj', bullets: ['60 fatura/muaj', '3 përdorues', 'e‑Faturë + QES (pa limit)', '75 pyetje AI/muaj', 'AI dokument hub'], popular: true },
      { name: 'Business', price: '3,630 den', period: '/muaj', bullets: ['150 fatura/muaj', '5 përdorues', 'Import bankar (CSV/MT940/PDF)', '200 pyetje AI/muaj', 'AI rekonçilim'], popular: false },
      { name: 'Max', price: '9,170 den', period: '/muaj', bullets: ['Fatura pa limit', 'Përdorues pa limit', '500 pyetje AI/muaj', 'Të gjitha funksionet AI', 'Raporte IFRS'], popular: false }
    ],
    partnerPlans: [
      { name: 'Start', price: '1,784 den', period: '/muaj', bullets: ['15 kompani', '50 kredite AI/muaj', '3 llogari bankare', '10 punonjës', '5 e-Fatura/muaj'], popular: false },
      { name: 'Office', price: '3,629 den', period: '/muaj', bullets: ['50 kompani', '200 kredite AI/muaj', '10 llogari bankare', '50 punonjës', '50 e-Fatura/muaj'], popular: true },
      { name: 'Pro', price: '6,089 den', period: '/muaj', bullets: ['150 kompani', '500 kredite AI/muaj', '30 llogari bankare', '200 punonjës', 'e-Fatura pa limit'], popular: false },
      { name: 'Elite', price: '12,239 den', period: '/muaj', bullets: ['Kompani pa limit', 'Kredite AI pa limit', 'Llogari bankare pa limit', 'Punonjës pa limit', 'e-Fatura pa limit'], popular: false },
    ],
    partnerSubtitle: 'Për zyrat e kontabilitetit · + 308 den/licencë shtesë',
    cta: 'Fillo tani',
    ctaPartner: 'Provo 30 ditë falas',
    sepaNote: 'Nuk keni kartë? Zgjidhni EUR për të paguar me transfertë bankare (SEPA).',
    comparisonTable: {
      title: 'Krahasoni paketat',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Fatura në muaj', values: ['3', '30', '60', '150', 'Pa limit'] },
        { feature: 'Përdorues', values: ['1', '1', '3', '5', 'Pa limit'] },
        { feature: 'Klientë', values: ['Pa limit', 'Pa limit', 'Pa limit', 'Pa limit', 'Pa limit'] },
        { feature: 'e-Faturë (UJP)', values: [false, '5/muaj', 'Pa limit', 'Pa limit', 'Pa limit'] },
        { feature: 'QES nënshkrim', values: [false, false, true, true, true] },
        { feature: 'Pyetje AI/muaj', values: ['10', '25', '75', '200', '500'] },
        { feature: 'AI Dokument Hub', values: ['Klasifikim', 'Nxjerrje', true, true, true] },
        { feature: 'Asistent AI', values: [false, '5/muaj', '25/muaj', true, true] },
        { feature: 'AI Rekonçilim', values: [false, false, 'Sugjerime', true, true] },
        { feature: 'Import bankar (CSV/MT940/PDF)', values: [false, false, false, true, true] },
        { feature: 'Auto-rekonçilim', values: [false, false, false, true, true] },
        { feature: 'Qasje API', values: [false, false, false, true, true] },
        { feature: 'Mbështetje', values: ['Email', 'Email', 'Email/Chat', 'Prioritet', 'WhatsApp'] }
      ]
    }
  }
}

const tr: Dictionary = {
  meta: {
    title: 'Facturino — AI Muhasebe ve e-Fatura Platformu',
    description:
      'AI destekli muhasebe platformu, e-Fatura\'ya hazır. Çoklu müşteri, banka ekstresi içe aktarma, IFRS raporları. 14 gün ücretsiz deneyin.'
  },
  nav: {
    features: 'Özellikler',
    forAccountants: 'Muhasebeciler için',
    how: 'Nasıl çalışır',
    efaktura: 'e‑Fatura',
    integrations: 'Entegrasyonlar',
    pricing: 'Fiyatlar',
    security: 'Güvenlik',
    contact: 'İletişim',
    contactSales: 'Satışla İletişime Geçin',
    start: 'Ücretsiz başla',
    login: 'Giriş',
    language: 'Dil',
    blog: 'Blog',
    showcase: 'Onizleme',
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
      'AI belge tarama, banka ekstresi içe aktarma (CSV/MT940/PDF), e‑Fatura ve sesli komutları tek çatıda birleştiren tek yerel platform — fatura ekleyin, AI okur ve bir saniyede kaydeder.'
  },
  heroTagline: 'Beklediğiniz muhasebe yazılımı – nihayet burada.',
  aiSection: {
    badge: 'AI Mali Danışman',
    title: 'İşiniz hakkında her şeyi sorun',
    subtitle: 'Sadece otomasyon değil — verilerinizi analiz eden ve somut tavsiyeler veren kişisel bir mali danışman alırsınız.',
    features: [
      { title: 'AI ile Sohbet', desc: '"En çok borcu olan müşteri kim?" veya "Kârlı mıyım?" diye sorun — anında cevap alın.' },
      { title: 'Erken Risk Uyarısı', desc: 'AI, bir müşteri çok büyük risk haline geldiğinde veya gecikmiş faturalarınız olduğunda sizi bilgilendirir.' },
      { title: 'Nakit Tahmin', desc: '90 gün ileriye bakın — gelecek ay hesabınızda yeterli paranız olacak mı?' },
      { title: 'Kâr Tavsiyeleri', desc: '"500.000 kâr için fiyatları ne kadar artırmalıyım?" — ürün bazında somut plan alın.' },
      { title: 'Söyle ve oluştur', desc: '"Markov için fatura, 5 saat 3000\'den" yazın — AI bir saniyede oluşturur. Siz sadece onaylayın.' },
      { title: 'Tara ve kaydet', desc: 'PDF fatura ekleyin — AI okur, kalemleri çıkarır ve fatura olarak kaydeder.' }
    ]
  },
  featureGrid: {
    title: 'İşinizi yürütmek için ihtiyacınız olan her şey',
    subtitle: 'Modern Makedon işletmeleri ve muhasebeciler için tasarlanmış güçlü özellikler.',
    features: [
      { title: 'e-Fatura hazır', desc: 'Yeni hükümet düzenlemelerine tam uyumlu. API açıldığında anında bağlanın.' },
      { title: 'Banka içe aktarma', desc: 'Banka ekstrelerini (CSV/MT940/PDF) içe aktarın ve faturalarla yarı otomatik eşleştirin.' },
      { title: 'AI belge tarama', desc: 'PDF veya fatura görseli ekleyin — AI okur, kalemleri, tutarları ve KDV\'yi çıkarır ve otomatik kaydeder.' },
      { title: 'Çoklu kiracı', desc: 'Birden fazla müşteri şirketini tek yerden yöneten muhasebeciler için mükemmel.' }
    ]
  },
  socialProof: { stat: '500+ muhasebeci Facturino kullanıyor', freeTrial: 'Ucretsiz deneme', noCreditCard: 'Kredi kartı gerekmez' },
  whyDifferent: {
    title: 'Facturino neden Makedonya’daki diğer yazılımlardan farklı',
    cards: [
      { title: 'AI belge tarama', body: 'PDF/görsel ekleyin — AI faturayı okur, kalemleri, tutarları ve KDV\'yi çıkarır. Tek tıkla onaylayın — kayıt hazır.' },
      { title: 'e‑Fatura hazır', body: 'Model tüm yapıyı destekler; UJP üretim API + QES açılır açılmaz bağlanırız.' },
      { title: 'Banka içe aktarma', body: 'CSV/MT940/PDF ekstre içe aktarma ve yarı otomatik mutabakat. PSD2 yakında.' },
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
  partners: { title: 'Entegre edildi', subtitle: 'Banka içe aktarma (CSV/MT940/PDF)', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Paketler',
    cta: 'Fiyatları gör',
    plans: [
      { name: 'Starter', price: '740 ден/ay', bullets: ['e‑Fatura hazır', 'AI asistan', '25 AI soru/ay'] },
      { name: 'Standard', price: '2,400 ден/ay', bullets: ['Çoklu kullanıcı', 'AI belge merkezi', 'Otomasyonlar'], popular: true },
      { name: 'Business', price: '3,630 ден/ay', bullets: ['AI mutabakat', 'Banka içe aktarma', 'API ve SLA'] }
    ]
  },
  faq: {
    title: 'SSS',
    subtitle: 'Facturino hakkında sık sorulan sorular.',
    items: [
      { q: "e‑Fatura'ya hazır mısınız?", a: 'Evet, model e‑fatura verileriyle kurulu; UJP üretim API + QES açılınca bağlanıyoruz.' },
      { q: 'AI nasıl çalışır?', a: 'Her satır için KDV/hesap önerir — onay sizde.' },
      { q: 'Banka içe aktarma destekliyor musunuz?', a: 'Evet, CSV/MT940/PDF ekstre içe aktarma ve yarı otomatik mutabakat. PSD2 doğrudan bağlantılar — yakında.' },
      { q: 'AI danışmana ne sorabilirim?', a: 'İşinizle ilgili her şey! "Kim borçlu?", "Kârlı mıyım?", "En büyük müşteriyi kaybedersem ne olur?", "Kârı nasıl artırabilirim?" — AI verilerinizi analiz eder ve somut cevaplar verir.' },
      { q: 'AI Belge Merkezi nedir?', a: 'PDF veya görsel ekleyin — AI sınıflandırır, kalemleri çıkarır ve tek tıkla kaydeder. Faturalar, giderler, banka işlemleri ve vergi formlarını destekler.' },
      { q: 'AI Asistan nasıl çalışır?', a: '"Markov için fatura 3000 den" yazın ve AI bir saniyede oluşturur. Fatura, gider ve ödemeleri destekler. AI müşteriyi ve ürünleri veritabanınızdan tanır.' }
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
          'Her şeyi sorun: "Kim borçlu?", "Kârlı mıyım?", "Kârı nasıl artırabilirim?"',
          'Erken uyarı: AI müşteri bağımlılığı risklerini bildirir',
          '90 günlük nakit akışı tahmini',
          'Fiyat ve kâr optimizasyonu tavsiyeleri',
          'Alacak yaşlandırma analizi (AR Aging) ile en büyük borçlular',
          'Ya olursa senaryoları: "En büyük müşteriyi kaybedersem ne olur?"'
        ]
      },
      {
        title: 'AI Belge Merkezi',
        items: [
          'PDF veya görsel ekleyin — AI otomatik sınıflandırır (fatura, gider, banka ekstresi, vergi formu)',
          'Gemini Vision ile tutar, tarih, kalem ve KDV çıkarma',
          'Kayıt öncesi gözden geçirme ve düzeltme — tek tıkla onaylayın',
          '7 varlık türünü destekler: faturalar, giderler, banka işlemleri, ürünler, vergi formları, sözleşmeler',
          'Belge oluşturulan varlığa bağlı kalır — kolay denetim'
        ]
      },
      {
        title: 'Söyle ve Oluştur (AI Asistan)',
        items: [
          '"Markov için fatura, 5 saat 3000\'den" yazın — AI bir saniyede oluşturur',
          'Fatura, gider ve ödemeler Makedonca ve İngilizce desteklenir',
          'AI müşteriyi, tedarikçiyi ve ürünleri veritabanınızdan tanır',
          '"Kaç ödenmemiş faturam var?" gibi sorulara anında cevap',
          'Taslak sistemi: kayıt öncesi gözden geçirip onaylayın',
          'Belirsiz bir durum olursa AI açıklama ister — yanlış oluşturmaz'
        ]
      },
      {
        title: 'AI Banka Mutabakatı',
        items: [
          'Gemini AI banka işlemlerini faturalarla karşılaştırır — Kiril adları dahil',
          'Kısmi ödemeler ve birleştirilmiş işlemlerin tespiti (split detection)',
          'Otomatik kategorizasyon: maaş, vergi, kira, tedarikçi, banka komisyonu vb.',
          '4 katmanlı pipeline: kurallar → deterministik → AI iyileştirme → AI kategorizasyon',
          'AI eşleşmeleri için mor rozetler — şeffaf ve denetlenebilir'
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
          'Banka ekstresi içe aktarma (CSV/MT940/PDF)',
          'Yarı otomatik mutabakat',
          'PSD2 doğrudan bağlantılar (yakında)'
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
      { name: 'Free', price: '0 den', period: '/süresiz', bullets: ['3 fatura/ay', '1 kullanıcı', 'PDF dışa aktarma', '10 AI soru/ay'], popular: false },
      { name: 'Starter', price: '740 den', period: '/ay', bullets: ['30 fatura/ay', '1 kullanıcı', 'e‑Fatura (5/ay)', '25 AI soru/ay', 'AI asistan'], popular: false },
      { name: 'Standard', price: '2,400 den', period: '/ay', bullets: ['60 fatura/ay', '3 kullanıcı', 'e‑Fatura + QES (sınırsız)', '75 AI soru/ay', 'AI belge merkezi'], popular: true },
      { name: 'Business', price: '3,630 den', period: '/ay', bullets: ['150 fatura/ay', '5 kullanıcı', 'Banka içe aktarma (CSV/MT940/PDF)', '200 AI soru/ay', 'AI mutabakat'], popular: false },
      { name: 'Max', price: '9,170 den', period: '/ay', bullets: ['Sınırsız fatura', 'Sınırsız kullanıcı', '500 AI soru/ay', 'Tüm AI özellikleri', 'IFRS raporları'], popular: false }
    ],
    partnerPlans: [
      { name: 'Start', price: '1,784 den', period: '/ay', bullets: ['15 şirket', '50 AI kredisi/ay', '3 banka hesabı', '10 çalışan', '5 e-Fatura/ay'], popular: false },
      { name: 'Office', price: '3,629 den', period: '/ay', bullets: ['50 şirket', '200 AI kredisi/ay', '10 banka hesabı', '50 çalışan', '50 e-Fatura/ay'], popular: true },
      { name: 'Pro', price: '6,089 den', period: '/ay', bullets: ['150 şirket', '500 AI kredisi/ay', '30 banka hesabı', '200 çalışan', 'Sınırsız e-Fatura'], popular: false },
      { name: 'Elite', price: '12,239 den', period: '/ay', bullets: ['Sınırsız şirket', 'Sınırsız AI kredisi', 'Sınırsız banka hesabı', 'Sınırsız çalışan', 'Sınırsız e-Fatura'], popular: false },
    ],
    partnerSubtitle: 'Muhasebe ofisleri için · + 308 den/ek lisans',
    cta: 'Şimdi başla',
    ctaPartner: '30 gün ücretsiz dene',
    sepaNote: 'Kartınız yok mu? Banka havalesiyle ödeme yapmak için EUR seçin (SEPA).',
    comparisonTable: {
      title: 'Paketleri karşılaştırın',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Aylık Fatura', values: ['3', '30', '60', '150', 'Sınırsız'] },
        { feature: 'Kullanıcı', values: ['1', '1', '3', '5', 'Sınırsız'] },
        { feature: 'Müşteri', values: ['Sınırsız', 'Sınırsız', 'Sınırsız', 'Sınırsız', 'Sınırsız'] },
        { feature: 'e-Fatura (UJP)', values: [false, '5/ay', 'Sınırsız', 'Sınırsız', 'Sınırsız'] },
        { feature: 'QES imza', values: [false, false, true, true, true] },
        { feature: 'AI Soru/ay', values: ['10', '25', '75', '200', '500'] },
        { feature: 'AI Belge Merkezi', values: ['Sınıflandırma', 'Çıkarma', true, true, true] },
        { feature: 'AI Asistan', values: [false, '5/ay', '25/ay', true, true] },
        { feature: 'AI Mutabakat', values: [false, false, 'Öneriler', true, true] },
        { feature: 'Banka içe aktarma (CSV/MT940/PDF)', values: [false, false, false, true, true] },
        { feature: 'Otomatik eşleştirme', values: [false, false, false, true, true] },
        { feature: 'API Erişimi', values: [false, false, false, true, true] },
        { feature: 'Destek', values: ['Email', 'Email', 'Email/Chat', 'Öncelikli', 'WhatsApp'] }
      ]
    }
  }
}

const en: Dictionary = {
  meta: {
    title: 'Facturino — AI Accounting Platform for Macedonia',
    description:
      'AI-powered accounting platform ready for e-Invoice. Multi-client management, bank statement import, IFRS reports. Start your free 14-day trial today.'
  },
  nav: {
    features: 'Features',
    forAccountants: 'For Accountants',
    how: 'How It Works',
    efaktura: 'e-Invoice',
    integrations: 'Integrations',
    pricing: 'Pricing',
    security: 'Security',
    contact: 'Contact',
    contactSales: 'Contact Sales',
    start: 'Start Free',
    login: 'Login',
    language: 'Language',
    blog: 'Blog',
    showcase: 'Preview',
  },
  hero: {
    h1: 'The most powerful AI accounting platform in Macedonia, ready for e-Invoice.',
    sub:
      'Global platform-level power, but designed specifically for Macedonian accountants.',
    primaryCta: 'Start Free',
    secondaryCta: 'Schedule Demo',
    claim:
      'Facturino is the most advanced AI accounting platform in Macedonia, ready for the new e-Invoice system.',
    onlyPlatform:
      'The only local platform with AI document scanning, bank import (CSV/MT940/PDF), e-Invoice, and voice commands — upload an invoice, AI reads it and records it in seconds.'
  },
  heroTagline: 'The accounting software you have been waiting for — is finally here.',
  aiSection: {
    badge: 'AI Financial Advisor',
    title: 'Ask me anything about your business',
    subtitle: 'Not just automation — you get a personal financial advisor who analyzes your data and provides concrete advice.',
    features: [
      { title: 'Chat with AI', desc: 'Ask "Which client owes me the most?" or "Am I profitable?" — get an answer instantly.' },
      { title: 'Early Risk Warning', desc: 'AI notifies you when a client becomes too high-risk or when you have overdue invoices.' },
      { title: 'Cash Flow Forecast', desc: 'Look 90 days ahead — will you have enough money in your account next month?' },
      { title: 'Profit Advice', desc: '"How much should I raise prices to achieve 500,000 profit?" — get a concrete plan by product.' },
      { title: 'Say it, create it', desc: 'Type "Invoice for Markov, 5 hours at 3000" — AI creates it in seconds. You just review and send.' },
      { title: 'Scan & record', desc: 'Upload a PDF invoice — AI reads it, extracts line items, and records it as a bill.' }
    ]
  },
  featureGrid: {
    title: 'Everything you need to run your business',
    subtitle: 'Powerful features designed for modern Macedonian businesses and accountants.',
    features: [
      { title: 'e-Invoice Ready', desc: 'Fully compliant with new government regulations. Connect immediately when the API opens.' },
      { title: 'Bank Import', desc: 'Import bank statements (CSV/MT940/PDF) and semi-automatically reconcile with invoices.' },
      { title: 'AI Document Scanning', desc: 'Upload a PDF or photo of any invoice — AI reads it, extracts line items, amounts and VAT, and records it automatically.' },
      { title: 'Multi-Client', desc: 'Perfect for accountants managing multiple client companies from one place.' }
    ]
  },
  socialProof: { stat: '500+ accountants already use Facturino', freeTrial: 'Free trial', noCreditCard: 'No credit card required' },
  whyDifferent: {
    title: 'Why Facturino is different from any software in Macedonia',
    cards: [
      { title: 'AI Document Scanning', body: 'Upload a PDF or photo — AI reads the invoice, extracts line items, amounts and VAT. Confirm with one click — the entry is done.' },
      { title: 'e-Invoice Ready', body: 'The model already supports all structures; connecting as soon as UJP opens production API + QES.' },
      { title: 'Bank Import', body: 'CSV/MT940/PDF statement import with semi-automatic reconciliation. PSD2 coming soon.' },
      { title: 'Multi-Client for Offices', body: 'One login, many companies, separate accounts, reports, and permissions.' },
      { title: 'IFRS Reports', body: 'IFRS package built into the backend for professional reports.' },
      { title: 'Security', body: 'EU hosting, encryption, backups, and activity audit trails.' }
    ]
  },
  benefits: {
    title: 'Benefits',
    badge: 'Advantages',
    cards: [
      { title: 'Save Time', body: 'Complete month-end closing in hours, not days.' },
      { title: 'Work Faster', body: 'Onboard a new client in one afternoon.' },
      { title: 'Be Prepared', body: 'Prepare e-invoices from day one.' }
    ]
  },
  how: {
    title: 'How It Works',
    process: 'Process',
    subtitle: 'Get started in minutes, not days.',
    steps: [
      { title: '1. Connect Company', body: 'Activate e-Invoice and set up VAT/account settings.' },
      { title: '2. Create Invoice', body: 'AI suggests VAT and accounts; you confirm and send.' },
      { title: '3. Reconcile Receivables', body: 'Import bank statements and reconcile in minutes, not hours.' }
    ]
  },
  partners: { title: 'Integrates with', subtitle: 'Bank import (CSV/MT940/PDF)', logos: ['NLB', 'Stopanska', 'Komercijalna', 'Sparkasse', 'Halk', 'Eurostandard'] },
  pricingPreview: {
    title: 'Plans',
    cta: 'View Pricing',
    plans: [
      { name: 'Starter', price: '740 ден/mo', bullets: ['e-Invoice Ready', 'AI Assistant', '25 AI queries/month'] },
      { name: 'Standard', price: '2,400 ден/mo', bullets: ['Multi-users', 'AI Document Hub', 'Automations'], popular: true },
      { name: 'Business', price: '3,630 ден/mo', bullets: ['AI Reconciliation', 'Bank Import', 'API & SLA'] }
    ]
  },
  faq: {
    title: 'FAQ',
    subtitle: 'Common questions about Facturino.',
    items: [
      { q: 'Are you ready for e-Invoice?', a: 'Yes, the model is built around e-invoices and we connect as soon as UJP opens production API + QES.' },
      { q: 'How does AI work?', a: 'It suggests VAT/accounts per line item — a human always confirms/edits.' },
      { q: 'Do you support bank import?', a: 'Yes, CSV/MT940/PDF statement import with semi-automatic reconciliation. PSD2 direct connections — coming soon.' },
      { q: 'What can I ask the AI advisor?', a: 'Anything about your business! "Who owes me?", "Am I profitable?", "What if I lose my biggest client?", "How do I increase profit?" — AI analyzes your data and provides concrete answers.' },
      { q: 'What is the AI Document Hub?', a: 'Upload a PDF or image — AI classifies it, extracts line items, and records it with one click. Supports bills, invoices, expenses, bank transactions, and tax forms.' },
      { q: 'How does the AI Assistant work?', a: 'Type "Invoice for Markov 3000 MKD" and AI creates it in seconds. Supports invoices, bills, expenses and payments. AI recognizes customers and items from your database.' }
    ]
  },
  cta: { title: 'Ready? Start free today.', sub: 'No credit card required • 14-day free trial • Cancel anytime', button: 'Start Free' },
  footer: { rights: '© Facturino. All rights reserved.' },
  featuresPage: {
    heroTitle: 'Features You Cannot Miss',
    groups: [
      {
        title: 'AI Financial Advisor',
        items: [
          'Ask anything: "Who owes me?", "Am I profitable?", "How do I increase profit?"',
          'Early warning: AI notifies you about client dependency risks',
          '90-day cash flow forecast',
          'Price and profit optimization advice',
          'Accounts receivable aging analysis (AR Aging) with top debtors',
          'What-if scenarios: "What if I lose my biggest client?"'
        ]
      },
      {
        title: 'AI Document Hub',
        items: [
          'Upload a PDF or image — AI automatically classifies it (bill, invoice, expense, bank statement, tax form)',
          'Extract amounts, dates, line items and VAT using Gemini Vision',
          'Review and correct before posting — confirm with one click',
          'Supports 7 entity types: bills, invoices, expenses, bank transactions, items, tax forms, contracts',
          'Document stays attached to the created entity for easy auditing'
        ]
      },
      {
        title: 'Say It, Create It (AI Assistant)',
        items: [
          'Type "Invoice for Markov, 5 hours at 3000" — AI creates it in seconds',
          'Supports invoices, bills, expenses and payments in Macedonian and English',
          'AI recognizes customers, suppliers and items from your database',
          'Questions like "How many unpaid invoices do I have?" get instant answers',
          'Draft system: review and confirm before posting',
          'If something is unclear, AI asks for clarification — never creates incorrectly'
        ]
      },
      {
        title: 'AI Bank Reconciliation',
        items: [
          'Gemini AI compares bank transactions with invoices — including Cyrillic names',
          'Partial payment and merged transaction detection (split detection)',
          'Auto-categorization: salary, tax, rent, supplier, bank fee, and more',
          '4-layer pipeline: rules → deterministic → AI enhancement → AI categorization',
          'Purple badges for AI matches — transparent and auditable'
        ]
      },
      {
        title: 'e-Invoice & Compliance',
        items: [
          'Structured data: Tax IDs, VAT by rate, payment terms',
          'Ready to connect when UJP opens production API + QES',
          'Professional Macedonian-style PDF templates'
        ]
      },
      {
        title: 'Banking & Cash Flow',
        items: [
          'Bank statement import (CSV/MT940/PDF)',
          'Semi-automatic reconciliation with invoices',
          'PSD2 direct connections (coming soon)'
        ]
      },
      {
        title: 'For Accountants',
        items: [
          'One login → multiple companies',
          'Separate chart of accounts and reports',
          'Roles and permissions, audit trails'
        ]
      },
      {
        title: 'Security & Control',
        items: [
          'Encryption at rest and in transit',
          'Regular backups and EU hosting',
          'Audit logs for key activities'
        ]
      }
    ]
  },
  pricingPage: {
    h1: 'Pricing',
    sub: '14-day free trial. No obligation.',
    sectionCompany: 'For Companies',
    sectionPartner: 'For Accountants (Partners)',
    popularBadge: 'Popular',
    recommendedBadge: 'Recommended',
    includesPrevious: 'Includes everything in {plan}',
    companyPlans: [
      { name: 'Free', price: '0 MKD', period: '/forever', bullets: ['3 invoices/month', '1 user', 'PDF export', '10 AI queries/month'], popular: false },
      { name: 'Starter', price: '740 MKD', period: '/month', bullets: ['30 invoices/month', '1 user', 'e-Invoice (5/month)', '25 AI queries/month', 'AI Assistant'], popular: false },
      { name: 'Standard', price: '2,400 MKD', period: '/month', bullets: ['60 invoices/month', '3 users', 'Unlimited e-Invoice + QES', '75 AI queries/month', 'AI Document Hub'], popular: true },
      { name: 'Business', price: '3,630 MKD', period: '/month', bullets: ['150 invoices/month', '5 users', 'Bank import (CSV/MT940/PDF)', '200 AI queries/month', 'AI Reconciliation'], popular: false },
      { name: 'Max', price: '9,170 MKD', period: '/month', bullets: ['Unlimited invoices', 'Unlimited users', '500 AI queries/month', 'All AI features', 'IFRS reports'], popular: false }
    ],
    partnerPlans: [
      { name: 'Start', price: '1,784 MKD', period: '/month', bullets: ['15 companies', '50 AI credits/month', '3 bank accounts', '10 employees', '5 e-Invoices/month'], popular: false },
      { name: 'Office', price: '3,629 MKD', period: '/month', bullets: ['50 companies', '200 AI credits/month', '10 bank accounts', '50 employees', '50 e-Invoices/month'], popular: true },
      { name: 'Pro', price: '6,089 MKD', period: '/month', bullets: ['150 companies', '500 AI credits/month', '30 bank accounts', '200 employees', 'Unlimited e-Invoices'], popular: false },
      { name: 'Elite', price: '12,239 MKD', period: '/month', bullets: ['Unlimited companies', 'Unlimited AI credits', 'Unlimited bank accounts', 'Unlimited employees', 'Unlimited e-Invoices'], popular: false },
    ],
    partnerSubtitle: 'For accounting firms · + 308 MKD/additional seat',
    cta: 'Start Now',
    ctaPartner: 'Try 30 days free',
    sepaNote: 'No card? Choose EUR to pay via bank transfer (SEPA).',
    comparisonTable: {
      title: 'Compare Plans',
      plans: ['Free', 'Starter', 'Standard', 'Business', 'Max'],
      rows: [
        { feature: 'Invoices per Month', values: ['3', '30', '60', '150', 'Unlimited'] },
        { feature: 'Users', values: ['1', '1', '3', '5', 'Unlimited'] },
        { feature: 'Clients', values: ['Unlimited', 'Unlimited', 'Unlimited', 'Unlimited', 'Unlimited'] },
        { feature: 'e-Invoice (UJP)', values: [false, '5/month', 'Unlimited', 'Unlimited', 'Unlimited'] },
        { feature: 'QES Signing', values: [false, false, true, true, true] },
        { feature: 'AI Questions/month', values: ['10', '25', '75', '200', '500'] },
        { feature: 'AI Document Hub', values: ['Classify', 'Extract', true, true, true] },
        { feature: 'AI Assistant', values: [false, '5/month', '25/month', true, true] },
        { feature: 'AI Reconciliation', values: [false, false, 'Suggestions', true, true] },
        { feature: 'Bank Import (CSV/MT940/PDF)', values: [false, false, false, true, true] },
        { feature: 'Auto-Reconciliation', values: [false, false, false, true, true] },
        { feature: 'API Access', values: [false, false, false, true, true] },
        { feature: 'Support', values: ['Email', 'Email', 'Email/Chat', 'Priority', 'WhatsApp'] }
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
    case 'en':
      return en
  }
}
