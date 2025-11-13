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
    start: string
    login: string
    language: string
  }
  hero: { h1: string; sub: string; primaryCta: string; secondaryCta: string; claim: string; onlyPlatform: string }
  // extra tagline line for hero
  heroTagline?: string
  socialProof: { trustedBy: string }
  whyDifferent: { title: string; cards: FeatureCard[] }
  benefits: { title: string; bullets: string[] }
  how: { title: string; steps: { title: string; body: string }[] }
  cta: { title: string; sub?: string; button: string }
  footer: { rights: string }
  partners?: { title: string; logos: string[] }
  pricingPreview?: { title: string; cta: string; plans: { name: string; bullets: string[] }[] }
  testimonials?: { title: string; items: { quote: string; author: string }[] }
  faq?: { title: string; items: { q: string; a: string }[] }
  featuresPage?: {
    heroTitle: string
    groups: { title: string; items: string[] }[]
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
    bullets: [
      'Завршете месечно затворање за часови, не за денови.',
      'Вклучете нов клиент за едно попладне.',
      'Подгответе е‑фактури од првиот ден.'
    ]
  },
  how: {
    title: 'Како работи',
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
    items: [
      { quote: 'Месечното затворање ни падна од 3 дена на неколку часа.', author: 'Ана, сметководител' },
      { quote: 'Изводите влегуваат директно и порамнувањето е лесно.', author: 'Игор, сопственик на фирма' }
    ]
  },
  faq: {
    title: 'ЧПП',
    items: [
      { q: 'Дали сте подготвени за е‑Фактура?', a: 'Да, моделот е изграден околу е‑фактури и се поврзуваме штом UJP отвори продукциски API + QES.' },
      { q: 'Како функционира AI?', a: 'Предлага ДДВ/конта по ставка — човек секогаш потврдува/уредува.' },
      { q: 'Поддржувате ли PSD2?', a: 'Да, вклучително увоз на изводи и полуавтоматско порамнување.' }
    ]
  },
  cta: { title: 'Подготвени сте? Започнете бесплатно денес.', button: 'Започни бесплатно' },
  footer: { rights: '© Facturino. Сите права задржани.' },
  featuresPage: {
    heroTitle: 'Функции што не можете да ги промашите',
    groups: [
      {
        title: 'AI и автоматизација',
        items: [
          'AI предлози за ДДВ категории и сметки по ставка',
          'Повторна употреба на шеми за побрзо месечно работење',
          'Рекурентни фактури, потсетници и едноставни работни текови'
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
    bullets: [
      'Mbyllni fund‑muajin për orë, jo ditë.',
      'Onboard‑oni një klient të ri në një pasdite.',
      'Përgatisni fatura gati për e‑Faturë që nga dita e parë.'
    ]
  },
  how: {
    title: 'Si funksionon',
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
    items: [
      { quote: 'Mbyllja mujore ra nga 3 ditë në disa orë.', author: 'Arta, kontabiliste' },
      { quote: 'Ekstraktet hyjnë direkt dhe pajtimi është i lehtë.', author: 'Blerim, pronar biznesi' }
    ]
  },
  faq: {
    title: 'Pyetje të shpeshta',
    items: [
      { q: 'A jeni gati për e‑Faturë?', a: 'Po, modeli është ndërtuar mbi e‑faturë dhe lidhemi sapo UJP hap API + QES.' },
      { q: 'Si punon AI?', a: 'Sugjeron TVSH/llogari për çdo rresht — njeriu gjithmonë konfirmon.' },
      { q: 'A mbështesni PSD2?', a: 'Po, import ekstraktesh dhe pajtim gjysmë‑automatik.' }
    ]
  },
  cta: { title: 'Gati? Fillo falas sot.', button: 'Fillo falas' },
  footer: { rights: '© Facturino. Të gjitha të drejtat e rezervuara.' },
  featuresPage: {
    heroTitle: 'Veçori që nuk mund t’i anashkaloni',
    groups: [
      {
        title: 'AI & Automatizim',
        items: [
          'Sugjerime AI për kategori TVSH dhe llogari për çdo rresht',
          'Rishfrytëzim i modeleve për punë më të shpejtë çdo muaj',
          'Fatura periodike, rikujtues dhe rrjedha pune bazike'
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
  heroTagline: 'Beklediğiniz muhasebe yazılımı sonunda geldi.',
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
    bullets: [
      'Aylık kapanışı günler değil saatlerde bitirin.',
      'Yeni bir müşteriyi bir öğleden sonra devreye alın.',
      'İlk günden e‑Fatura’ya hazır faturalar hazırlayın.'
    ]
  },
  how: {
    title: 'Nasıl çalışır',
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
    title: 'Müşterilerin sevdiği',
    items: [
      { quote: 'Aylık kapanışımız günlerden saatlere indi.', author: 'Selin, muhasebeci' },
      { quote: 'Ekstreler doğrudan iniyor, mutabakat çok kolay.', author: 'Emir, işletme sahibi' }
    ]
  },
  faq: {
    title: 'SSS',
    items: [
      { q: 'e‑Fatura’ya hazır mısınız?', a: 'Evet, model e‑fatura verileriyle kurulu; UJP üretim API + QES açılınca bağlanıyoruz.' },
      { q: 'AI nasıl çalışır?', a: 'Her satır için KDV/hesap önerir — onay sizde.' },
      { q: 'PSD2 destekliyor musunuz?', a: 'Evet, ekstre içe aktarma ve yarı otomatik mutabakat.' }
    ]
  },
  cta: { title: 'Hazır mısınız? Bugün ücretsiz başlayın.', button: 'Ücretsiz başla' },
  footer: { rights: '© Facturino. Tüm hakları saklıdır.' },
  featuresPage: {
    heroTitle: 'Gözden kaçırılmayacak özellikler',
    groups: [
      {
        title: 'Yapay zekâ ve otomasyon',
        items: [
          'Her satır için KDV ve hesap önerileri',
          'Tekrarlayan desenlerin yeniden kullanımı ile hız',
          'Tekrarlayan faturalar, hatırlatıcılar ve basit iş akışları'
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
