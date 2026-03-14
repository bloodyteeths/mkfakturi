import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/for-accountants', {
    title: {
      mk: 'За сметководители — Facturino',
      sq: 'Per kontabiliste — Facturino',
      tr: 'Muhasebeciler icin — Facturino',
      en: 'For Accountants — Facturino',
    },
    description: {
      mk: 'Бесплатна мулти-клиент платформа за сметководители. Додајте ги сите компании бесплатно. Управувајте со десетици клиенти од едно место.',
      sq: 'Platforme falas shume-klientesh per kontabiliste. Shtoni te gjitha kompanite falas. Menaxhoni dhjetera kliente nga nje vend.',
      tr: 'Muhasebeciler icin ucretsiz coklu musteri platformu. Tum sirketleri ucretsiz ekleyin. Onlarca musteriyi tek yerden yonetin.',
      en: 'Free multi-client platform for accountants. Add all companies for free. Manage dozens of clients from one place.',
    },
  })
}

const copy = {
  mk: {
    hero: {
      badge: 'Партнерска програма',
      headline: 'Една пријава → десетици клиенти',
      sub: 'Завршете ја работата за 10 клиенти за времето кое сега ви треба за 3. Facturino е изграден за сметководствени канцеларии.',
      cta: 'Регистрирај се како партнер',
    },
    pains: {
      title: 'Дали ви е познато ова?',
      items: [
        {
          icon: 'switch',
          title: 'Различен софтвер за секој клиент',
          body: 'Влегувате во 5 различни системи, паметите 5 лозинки, и ниту еден не работи како што сакате.',
        },
        {
          icon: 'clock',
          title: 'Рачно внесување на секоја фактура',
          body: 'Секоја влезна фактура — отвори PDF, препиши го бројот, износите, ставките, ДДВ... со часови на рачна работа. Грешка во една цифра — и сметката не штима.',
        },
        {
          icon: 'alert',
          title: 'е-Фактура доаѓа, а немате алатка',
          body: 'УЈП најавува задолжителна е‑Фактура, а вашиот софтвер сe уште нема структурирани податоци.',
        },
        {
          icon: 'calendar',
          title: 'Месечно затворање трае денови',
          body: 'Крај на месец значи прекувремена работа, стрес и ризик од грешки при затворање на книгите.',
        },
      ],
    },
    solutions: {
      title: 'Facturino ги решава сите',
      items: [
        {
          title: 'Мулти‑компанија: еден контролен панел',
          body: 'Сите клиенти во еден систем. Префрлете се од компанија на компанија со еден клик.',
          icon: 'grid',
        },
        {
          title: 'Банкарски увоз (CSV/MT940/PDF)',
          body: 'Увоз на изводи од банка во CSV, MT940 или PDF формат и интелигентно порамнување. Без рачно внесување.',
          icon: 'bank',
        },
        {
          title: 'е-Фактура: веќе подготвени',
          body: 'Структурирани податоци по UBL 2.1 стандард. Кога УЈП ќе го отвори API‑то, ние се поврзуваме.',
          icon: 'invoice',
        },
        {
          title: 'AI асистент за побрзо затворање',
          body: 'Кажи „Фактура за Марков 3000 ден" и AI ја креира. Скенирај документ — AI го чита. Банковни изводи — AI ги порамнува. Предлози за конта и ДДВ стапки.',
          icon: 'ai',
        },
        {
          title: 'Материјално сметководство',
          body: 'Приемница, издатница, преносница + WAC вреднување + автоматско книжење на Класа 3, 6, 7. Инспекторите ќе бидат задоволни.',
          icon: 'warehouse',
        },
        {
          title: 'Волшебник за годишно затворање',
          body: '6 чекори: проверка → преглед → корекции → затворачки книжења → извештаи → заклучување. PDF за ЦРРМ, XML/CSV за е-поднесување, пресметка за ДБ-ВП.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Годишно затворање за 30 минути',
      sub: 'Волшебникот ве води чекор по чекор — од проверка до заклучување. Без стрес, без грешки.',
      steps: [
        { num: '1', label: 'Проверка', desc: 'Автоматска проверка: дали сите фактури се финализирани, банките порамнети, ДДВ пријавите поднесени.' },
        { num: '2', label: 'Преглед', desc: 'Биланс на состојба, биланс на успех и бруто биланс — на еден екран.' },
        { num: '3', label: 'Корекции', desc: 'Амортизација, резервирања, временски разграничувања — или прескокнете ако сè е книжено.' },
        { num: '4', label: 'Затворање', desc: 'Автоматски затворачки книжења: класа 5/6 → задржана добивка. Данок 10% пресметан.' },
        { num: '5', label: 'Извештаи', desc: 'PDF за ЦРРМ, Pantheon XML / Zonel CSV за е-поднесување, пресметка за ДБ-ВП.' },
        { num: '6', label: 'Заклучи', desc: 'Период заклучен. Рок до 15 март — вие завршивте. Поништување достапно 24 часа.' },
      ],
    },
    comparison: {
      title: 'Facturino = QuickBooks/Xero за Македонија',
      sub: 'Светски квалитет, локална логика',
      rows: [
        { feature: 'Мултијазичен UI (MK, SQ, TR, EN)', facturino: true, others: false },
        { feature: 'ДДВ правила за МК (5%, 18%)', facturino: true, others: false },
        { feature: 'Банкарски увоз (CSV/MT940/PDF)', facturino: true, others: false },
        { feature: 'е-Фактура подготвеност (UBL 2.1)', facturino: true, others: false },
        { feature: 'AI скенирање на документи (PDF/слика → книжење)', facturino: true, others: false },
        { feature: 'AI контирање и предлози', facturino: true, others: false },
        { feature: 'IFRS извештаи', facturino: true, others: true },
        { feature: 'Мулти‑компанија од еден акаунт', facturino: true, others: false },
        { feature: 'Материјално сметководство (WAC, GL)', facturino: true, others: false },
        { feature: 'Волшебник за годишно затворање (6 чекори)', facturino: true, others: false },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / MiniMax',
    },
    partner: {
      title: 'Бесплатно за сметководители',
      sub: 'Додајте ги сите ваши компании бесплатно. Еден систем за сите клиенти — без трошоци за вас.',
      benefits: [
        'Портфолио: додајте неограничено компании бесплатно',
        '3 месеци Standard за сите компании при старт',
        'Партнер контролен панел со статистики',
        'Приоритетна техничка поддршка',
      ],
      cta: 'Стани партнер',
      visualRecurring: 'бесплатен пристап',
      visualDashboard: 'Портфолио панел',
      visualAnalytics: 'статистики и покриеност',
      visualSupport: 'Приоритетна поддршка',
    },
    bottomCta: {
      title: 'Пробај бесплатно 14 дена',
      sub: 'Без кредитна картичка. Без обврска. Целосен пристап.',
      cta: 'Започни бесплатно',
    },
  },
  sq: {
    hero: {
      badge: 'Programi i partneritetit',
      headline: 'Një hyrje → dhjetëra klientë',
      sub: 'Kryeni punën për 10 klientë në kohën që tani ju duhet për 3. Facturino është ndërtuar për zyra kontabiliteti.',
      cta: 'Regjistrohu si partner',
    },
    pains: {
      title: 'A ju duket e njohur kjo?',
      items: [
        {
          icon: 'switch',
          title: 'Softuer i ndryshëm për çdo klient',
          body: 'Hyni në 5 sisteme të ndryshme, mbani mend 5 fjalëkalime, dhe asnjëri nuk funksionon si duhet.',
        },
        {
          icon: 'clock',
          title: 'Futje manuale e çdo fature',
          body: 'Çdo faturë hyrëse — hapni PDF, rishkruani numrin, shumat, zërat, TVSH... orë punë manuale. Një gabim i vetëm — dhe llogaria nuk del.',
        },
        {
          icon: 'alert',
          title: 'e-Fatura po vjen, por nuk keni mjet',
          body: 'UJP njofton e‑Faturë të detyrueshme, por softueri juaj ende nuk ka të dhëna të strukturuara.',
        },
        {
          icon: 'calendar',
          title: 'Mbyllja mujore zgjat ditë',
          body: 'Fundi i muajit do të thotë punë jashtë orarit, stres dhe rrezik gabimesh gjatë mbylljes së librave.',
        },
      ],
    },
    solutions: {
      title: 'Facturino i zgjidh të gjitha',
      items: [
        {
          title: 'Shumë kompani: një panel kontrolli',
          body: 'Të gjithë klientët në një sistem. Kaloni nga kompania në kompani me një klikim.',
          icon: 'grid',
        },
        {
          title: 'Import bankar (CSV/MT940/PDF)',
          body: 'Import i ekstrakteve bankare ne format CSV, MT940 ose PDF dhe pajtim inteligjent. Pa futje manuale.',
          icon: 'bank',
        },
        {
          title: 'e-Fatura: tashmë gati',
          body: 'Të dhëna të strukturuara sipas standardit UBL 2.1. Kur UJP hap API‑n, ne lidhemi.',
          icon: 'invoice',
        },
        {
          title: 'Asistent AI për mbyllje më të shpejtë',
          body: 'Thuaj "Faturë për Markov 3000 den" dhe AI e krijon. Skanoni dokument — AI e lexon. Ekstrakte bankare — AI i pajton. Sugjerime për llogari dhe TVSH.',
          icon: 'ai',
        },
        {
          title: 'Kontabiliteti Material',
          body: 'Fletëhyrje, fletëdalje, fletëkalim + vlerësim WAC + regjistrim automatik në Klasa 3, 6, 7. Inspektorët do të jenë të kënaqur.',
          icon: 'warehouse',
        },
        {
          title: 'Magjistari i mbylljes vjetore',
          body: '6 hapa: kontrolli → rishikim → korrigjim → regjistrimet mbyllëse → raporte → mbyllje. PDF për QKRM, XML/CSV për dorëzim elektronik, llogaritje DB-VP.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Mbyllja vjetore për 30 minuta',
      sub: 'Magjistari ju udhëzon hap pas hapi — nga kontrolli deri në mbyllje. Pa stres, pa gabime.',
      steps: [
        { num: '1', label: 'Kontrolli', desc: 'Kontroll automatik: a janë finalizuar të gjitha faturat, bankat të pajtuara, deklaratat e TVSH-së të dorëzuara.' },
        { num: '2', label: 'Rishikim', desc: 'Bilanci i gjendjes, bilanci i suksesit dhe bilanci verifikues — në një ekran.' },
        { num: '3', label: 'Korrigjim', desc: 'Amortizim, provigjione, akruale — ose kapërceni nëse gjithçka është regjistruar.' },
        { num: '4', label: 'Mbyllje', desc: 'Regjistrimet mbyllëse automatike: klasa 5/6 → fitime të mbajtura. Tatimi 10% i llogaritur.' },
        { num: '5', label: 'Raporte', desc: 'PDF për QKRM, Pantheon XML / Zonel CSV për dorëzim elektronik, llogaritje DB-VP.' },
        { num: '6', label: 'Mbyll', desc: 'Periudha e mbyllur. Afati deri 15 mars — ju keni mbaruar. Anulim i disponueshëm 24 orë.' },
      ],
    },
    comparison: {
      title: 'Facturino = QuickBooks/Xero për Maqedoninë',
      sub: 'Cilësi botërore, logjikë lokale',
      rows: [
        { feature: 'Ndërfaqe shumëgjuhëshe (MK, SQ, TR, EN)', facturino: true, others: false },
        { feature: 'Rregulla TVSH për MK (5%, 18%)', facturino: true, others: false },
        { feature: 'Import bankar (CSV/MT940/PDF)', facturino: true, others: false },
        { feature: 'Gatishmëri e-Fatura (UBL 2.1)', facturino: true, others: false },
        { feature: 'AI skanim dokumentesh (PDF/imazh → regjistrim)', facturino: true, others: false },
        { feature: 'Kontim dhe sugjerime me AI', facturino: true, others: false },
        { feature: 'Raporte IFRS', facturino: true, others: true },
        { feature: 'Shumë kompani nga një llogari', facturino: true, others: false },
        { feature: 'Kontabilitet material (WAC, GL)', facturino: true, others: false },
        { feature: 'Magjistari i mbylljes vjetore (6 hapa)', facturino: true, others: false },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / MiniMax',
    },
    partner: {
      title: 'Falas për kontabilistët',
      sub: 'Shtoni të gjitha kompanitë tuaja falas. Një sistem për të gjithë klientët — pa kosto për ju.',
      benefits: [
        'Portofol: shtoni kompani të pakufizuara falas',
        '3 muaj Standard për të gjitha kompanitë në fillim',
        'Panel kontrolli partneri me statistika',
        'Mbështetje teknike me prioritet',
      ],
      cta: 'Bëhu partner',
      visualRecurring: 'qasje falas',
      visualDashboard: 'Paneli i portofolit',
      visualAnalytics: 'statistika dhe mbulim',
      visualSupport: 'Mbështetje me prioritet',
    },
    bottomCta: {
      title: 'Provoni falas 14 ditë',
      sub: 'Pa kartë krediti. Pa detyrim. Qasje e plotë.',
      cta: 'Fillo falas',
    },
  },
  tr: {
    hero: {
      badge: 'Partner programi',
      headline: 'Tek giriş → onlarca müşteri',
      sub: '10 müşterinin işini, şu an 3 müşteriye harcadığınız sürede bitirin. Facturino muhasebe ofisleri için tasarlandı.',
      cta: 'Partner olarak kaydol',
    },
    pains: {
      title: 'Bunlar tanıdık mı?',
      items: [
        {
          icon: 'switch',
          title: 'Her müşteri için farklı yazılım',
          body: '5 farklı sisteme girip 5 şifre ezberliyorsunuz ve hiçbiri istediğiniz gibi çalışmıyor.',
        },
        {
          icon: 'clock',
          title: 'Her faturayı elle girme',
          body: 'Her gelen fatura — PDF\'yi açın, numarayı, tutarları, kalemleri, KDV\'yi kopyalayın... saatlerce elle çalışma. Bir hanede hata — ve hesap tutmuyor.',
        },
        {
          icon: 'alert',
          title: 'e-Fatura geliyor ama aracınız yok',
          body: 'UJP zorunlu e‑Fatura duyuruyor, ancak yazılımınızda hâlâ yapılandırılmış veri desteği yok.',
        },
        {
          icon: 'calendar',
          title: 'Ay sonu kapanışı günler sürüyor',
          body: 'Ay sonu fazla mesai, stres ve defter kapatırken hata riski demek.',
        },
      ],
    },
    solutions: {
      title: 'Facturino hepsini çözer',
      items: [
        {
          title: 'Çok şirket: tek kontrol paneli',
          body: 'Tüm müşteriler tek sistemde. Bir tıkla şirketten şirkete geçin.',
          icon: 'grid',
        },
        {
          title: 'Banka ithalatı (CSV/MT940/PDF)',
          body: 'CSV, MT940 veya PDF formatında ekstre aktarımı ve akıllı mutabakat. Manuel giriş yok.',
          icon: 'bank',
        },
        {
          title: 'e-Fatura: şimdiden hazır',
          body: 'UBL 2.1 standardında yapılandırılmış veriler. UJP API\'yi açınca biz bağlanırız.',
          icon: 'invoice',
        },
        {
          title: 'Daha hızlı kapanış için AI asistan',
          body: '"Markov için fatura 3000 den" yazın ve AI oluşturur. Belge tarayın — AI okur. Banka ekstreleri — AI mutabakat yapar. Hesap ve KDV önerileri.',
          icon: 'ai',
        },
        {
          title: 'Malzeme Muhasebesi',
          body: 'Giriş fişi, çıkış fişi, transfer fişi + WAC değerleme + Sınıf 3, 6, 7 otomatik muhasebe kaydı. Müfettişler memnun kalacak.',
          icon: 'warehouse',
        },
        {
          title: 'Yıl sonu kapanış sihirbazı',
          body: '6 adım: kontrol → inceleme → düzeltme → kapanış kayıtları → raporlar → kilitleme. CRMS için PDF, e-başvuru için XML/CSV, DB-VP hesaplama.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Yıl sonu kapanışı 30 dakikada',
      sub: 'Sihirbaz sizi adım adım yönlendirir — kontrolden kilitlemeye. Stressiz, hatasız.',
      steps: [
        { num: '1', label: 'Kontrol', desc: 'Otomatik kontrol: tüm faturalar tamamlanmış mı, bankalar mutabık mı, KDV beyannameleri verilmiş mi.' },
        { num: '2', label: 'İnceleme', desc: 'Bilanço, gelir tablosu ve mizan — tek ekranda.' },
        { num: '3', label: 'Düzeltme', desc: 'Amortisman, karşılık, tahakkuk — veya hepsi kaydedilmişse atlayın.' },
        { num: '4', label: 'Kapanış', desc: 'Otomatik kapanış kayıtları: sınıf 5/6 → dağıtılmamış kâr. %10 vergi hesaplanır.' },
        { num: '5', label: 'Raporlar', desc: 'CRMS için PDF, Pantheon XML / Zonel CSV e-başvuru için, DB-VP hesaplaması.' },
        { num: '6', label: 'Kilitle', desc: 'Dönem kilitlendi. 15 Mart\'a kadar süre var — siz bitirdiniz. 24 saat geri alma mümkün.' },
      ],
    },
    comparison: {
      title: 'Facturino = Makedonya için QuickBooks/Xero',
      sub: 'Dünya kalitesi, yerel mantık',
      rows: [
        { feature: 'Çok dilli arayüz (MK, SQ, TR, EN)', facturino: true, others: false },
        { feature: 'MK KDV kuralları (5%, 18%)', facturino: true, others: false },
        { feature: 'Banka ithalatı (CSV/MT940/PDF)', facturino: true, others: false },
        { feature: 'e-Fatura hazırlığı (UBL 2.1)', facturino: true, others: false },
        { feature: 'AI belge tarama (PDF/görsel → kayıt)', facturino: true, others: false },
        { feature: 'AI muhasebe kaydı ve öneriler', facturino: true, others: false },
        { feature: 'IFRS raporları', facturino: true, others: true },
        { feature: 'Tek hesaptan çok şirket', facturino: true, others: false },
        { feature: 'Malzeme muhasebesi (WAC, GL)', facturino: true, others: false },
        { feature: 'Yıl sonu kapanış sihirbazı (6 adım)', facturino: true, others: false },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / MiniMax',
    },
    partner: {
      title: 'Muhasebeciler için ücretsiz',
      sub: 'Tüm şirketlerinizi ücretsiz ekleyin. Tüm müşteriler için tek sistem — size maliyeti yok.',
      benefits: [
        'Portföy: sınırsız şirketi ücretsiz ekleyin',
        'Başlangıçta tüm şirketler için 3 ay Standard',
        'İstatistikli partner kontrol paneli',
        'Öncelikli teknik destek',
      ],
      cta: 'Partner ol',
      visualRecurring: 'ücretsiz erişim',
      visualDashboard: 'Portföy paneli',
      visualAnalytics: 'istatistikler ve kapsama',
      visualSupport: 'Öncelikli destek',
    },
    bottomCta: {
      title: '14 gün ücretsiz deneyin',
      sub: 'Kredi kartı gerekmez. Taahhüt yok. Tam erişim.',
      cta: 'Ücretsiz başla',
    },
  },
  en: {
    hero: {
      badge: 'Partner Program',
      headline: 'One login → dozens of clients',
      sub: 'Complete the work for 10 clients in the time it now takes you for 3. Facturino is built for accounting firms.',
      cta: 'Sign up as a partner',
    },
    pains: {
      title: 'Sound familiar?',
      items: [
        {
          icon: 'switch',
          title: 'Different software for every client',
          body: 'You log into 5 different systems, remember 5 passwords, and none of them work the way you want.',
        },
        {
          icon: 'clock',
          title: 'Manually entering every invoice',
          body: 'Every incoming invoice — open the PDF, retype the number, amounts, line items, VAT... hours of manual work. One wrong digit — and the account doesn\'t balance.',
        },
        {
          icon: 'alert',
          title: 'e-Invoice is coming but you have no tools',
          body: 'UJP announces mandatory e-Invoice, but your software still lacks structured data support.',
        },
        {
          icon: 'calendar',
          title: 'Month-end closing takes days',
          body: 'End of month means overtime, stress, and risk of errors when closing the books.',
        },
      ],
    },
    solutions: {
      title: 'Facturino solves them all',
      items: [
        {
          title: 'Multi-company: one dashboard',
          body: 'All clients in one system. Switch from company to company with a single click.',
          icon: 'grid',
        },
        {
          title: 'Bank Import (CSV/MT940/PDF)',
          body: 'Import bank statements in CSV, MT940, or PDF format with intelligent matching. No manual entry.',
          icon: 'bank',
        },
        {
          title: 'e-Invoice: already ready',
          body: 'Structured data following UBL 2.1 standard. When UJP opens the API, we connect.',
          icon: 'invoice',
        },
        {
          title: 'AI assistant for faster closing',
          body: 'Say "Invoice for Markov 3000 MKD" and AI creates it. Scan a document — AI reads it. Bank statements — AI reconciles them. Account and VAT suggestions included.',
          icon: 'ai',
        },
        {
          title: 'Material Accounting',
          body: 'Goods received, issued & transfer notes + WAC valuation + automatic GL posting to Class 3, 6, 7 accounts. Inspectors will be satisfied.',
          icon: 'warehouse',
        },
        {
          title: 'Year-End Closing Wizard',
          body: '6 steps: checklist → review → adjustments → closing entries → reports → lock. PDF for CRMS, XML/CSV for e-filing, DB-VP tax calculation.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Year-end closing in 30 minutes',
      sub: 'The wizard guides you step by step — from checklist to lock. No stress, no errors.',
      steps: [
        { num: '1', label: 'Checklist', desc: 'Automatic checks: are all invoices finalized, banks reconciled, VAT returns filed.' },
        { num: '2', label: 'Review', desc: 'Balance sheet, income statement, and trial balance — on one screen.' },
        { num: '3', label: 'Adjust', desc: 'Depreciation, provisions, accruals — or skip if everything is already recorded.' },
        { num: '4', label: 'Close', desc: 'Automatic closing entries: class 5/6 → retained earnings. 10% tax calculated.' },
        { num: '5', label: 'Reports', desc: 'PDF for CRMS, Pantheon XML / Zonel CSV for e-filing, DB-VP tax calculation.' },
        { num: '6', label: 'Lock', desc: 'Period locked. Deadline March 15 — you\'re done. Undo available for 24 hours.' },
      ],
    },
    comparison: {
      title: 'Facturino = QuickBooks/Xero for Macedonia',
      sub: 'World-class quality, local logic',
      rows: [
        { feature: 'Multilingual UI (MK, SQ, TR, EN)', facturino: true, others: false },
        { feature: 'MK VAT rules (5%, 18%)', facturino: true, others: false },
        { feature: 'Bank import (CSV/MT940/PDF)', facturino: true, others: false },
        { feature: 'e-Invoice readiness (UBL 2.1)', facturino: true, others: false },
        { feature: 'AI document scanning (PDF/photo → entry)', facturino: true, others: false },
        { feature: 'AI journal entries and suggestions', facturino: true, others: false },
        { feature: 'IFRS reports', facturino: true, others: true },
        { feature: 'Multi-company from one account', facturino: true, others: false },
        { feature: 'Material accounting (WAC, GL)', facturino: true, others: false },
        { feature: 'Year-end closing wizard (6 steps)', facturino: true, others: false },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / MiniMax',
    },
    partner: {
      title: 'Free for accountants',
      sub: 'Add all your companies for free. One system for all clients — no cost to you.',
      benefits: [
        'Portfolio: add unlimited companies for free',
        '3 months Standard for all companies at start',
        'Partner dashboard with analytics',
        'Priority technical support',
      ],
      cta: 'Become a partner',
      visualRecurring: 'free access',
      visualDashboard: 'Portfolio dashboard',
      visualAnalytics: 'analytics & coverage',
      visualSupport: 'Priority support',
    },
    bottomCta: {
      title: 'Try free for 14 days',
      sub: 'No credit card required. No commitment. Full access.',
      cta: 'Start for free',
    },
  },
} as const

/* SVG icon helpers – kept inline to avoid extra component files */
function IconSwitch() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
    </svg>
  )
}
function IconClock() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  )
}
function IconAlert() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
  )
}
function IconCalendar() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
    </svg>
  )
}
function IconGrid() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
    </svg>
  )
}
function IconBank() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
    </svg>
  )
}
function IconInvoice() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>
  )
}
function IconAI() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
    </svg>
  )
}
function IconWarehouse() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
    </svg>
  )
}
function IconYearEnd() {
  return (
    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  )
}
function IconCheck() {
  return (
    <svg className="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
    </svg>
  )
}
function IconX() {
  return (
    <svg className="w-5 h-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
    </svg>
  )
}
function IconStar() {
  return (
    <svg className="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
    </svg>
  )
}

const painIcons: Record<string, () => React.JSX.Element> = {
  switch: IconSwitch,
  clock: IconClock,
  alert: IconAlert,
  calendar: IconCalendar,
}

const solutionIcons: Record<string, () => React.JSX.Element> = {
  grid: IconGrid,
  bank: IconBank,
  invoice: IconInvoice,
  ai: IconAI,
  warehouse: IconWarehouse,
  yearend: IconYearEnd,
}

export default async function ForAccountantsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content" className="overflow-x-hidden">

      {/* ── HERO ─────────────────────────────────────────────── */}
      <section className="relative overflow-hidden pt-28 pb-20 md:pt-36 md:pb-28">
        {/* Background blobs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-20 left-10 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
          <div className="absolute top-20 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30"></div>
        </div>

        <div className="container relative z-10 text-center max-w-4xl mx-auto px-4 sm:px-6">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/80 backdrop-blur-sm border border-indigo-100 px-4 py-1.5 text-sm font-semibold text-indigo-600 shadow-sm mb-8">
            <span className="relative flex h-2 w-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
            </span>
            {t.hero.badge}
          </div>
          <h1 className="text-4xl sm:text-5xl md:text-6xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            {t.hero.headline.split('→')[0]}
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500">→</span>
            {t.hero.headline.split('→')[1]}
          </h1>
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed max-w-2xl mx-auto mb-10">
            {t.hero.sub}
          </p>
          <a
            href="https://app.facturino.mk/partner/signup"
            className="btn-primary text-lg px-8 py-4"
          >
            {t.hero.cta}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        </div>
      </section>

      {/* ── PAIN POINTS ──────────────────────────────────────── */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.pains.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-red-400 to-orange-400 mx-auto rounded-full"></div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-5xl mx-auto">
            {t.pains.items.map((item, i) => {
              const Icon = painIcons[item.icon]
              return (
                <div key={i} className="card group border-l-4 border-l-red-400 hover:border-l-red-500">
                  <div className="flex items-start gap-4">
                    <div className="flex-shrink-0 w-12 h-12 rounded-xl bg-red-50 flex items-center justify-center text-red-500 group-hover:bg-red-500 group-hover:text-white transition-colors">
                      <Icon />
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-gray-900 mb-1">{item.title}</h3>
                      <p className="text-gray-600 text-sm leading-relaxed">{item.body}</p>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* ── SOLUTIONS ────────────────────────────────────────── */}
      <section className="section">
        <div className="absolute inset-0 bg-grid-pattern opacity-[0.03] pointer-events-none"></div>
        <div className="container relative z-10 px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.solutions.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 max-w-5xl mx-auto">
            {t.solutions.items.map((item, i) => {
              const Icon = solutionIcons[item.icon]
              return (
                <div key={i} className="card group hover:border-indigo-200">
                  <div className="flex items-start gap-4">
                    <div className="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                      <Icon />
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-gray-900 mb-1 group-hover:text-indigo-600 transition-colors">{item.title}</h3>
                      <p className="text-gray-600 text-sm leading-relaxed">{item.body}</p>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* ── YEAR-END CLOSING WIZARD ─────────────────────────── */}
      <section className="section bg-gradient-to-br from-emerald-50 to-cyan-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <div className="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-4 py-1.5 text-sm font-semibold text-emerald-700 mb-6">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
              </svg>
              NEW
            </div>
            <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.yearEnd.title}</h2>
            <p className="text-lg text-gray-600">{t.yearEnd.sub}</p>
            <div className="h-1 w-20 bg-gradient-to-r from-emerald-500 to-cyan-500 mx-auto rounded-full mt-4"></div>
          </div>

          <div className="max-w-4xl mx-auto">
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              {t.yearEnd.steps.map((step, i) => (
                <div key={i} className="relative bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
                  <div className="flex items-center gap-3 mb-3">
                    <div className="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center text-sm font-bold">
                      {step.num}
                    </div>
                    <h3 className="font-bold text-gray-900">{step.label}</h3>
                  </div>
                  <p className="text-sm text-gray-600 leading-relaxed">{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* ── COMPARISON TABLE ─────────────────────────────────── */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-14">
            <h2 className="text-3xl md:text-4xl font-bold mb-3 text-gray-900">{t.comparison.title}</h2>
            <p className="text-lg text-gray-600">{t.comparison.sub}</p>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full mt-4"></div>
          </div>

          <div className="max-w-3xl mx-auto">
            <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg">
              {/* Table header */}
              <div className="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-6 py-4 bg-gradient-to-r from-indigo-600 to-cyan-600 text-white text-sm font-bold">
                <div></div>
                <div className="w-28 text-center">{t.comparison.facturinoLabel}</div>
                <div className="w-28 text-center">{t.comparison.othersLabel}</div>
              </div>
              {/* Table rows */}
              {t.comparison.rows.map((row, i) => (
                <div
                  key={i}
                  className={`grid grid-cols-[1fr_auto_auto] items-center gap-4 px-6 py-3.5 text-sm ${
                    i % 2 === 0 ? 'bg-white' : 'bg-slate-50'
                  } ${i < t.comparison.rows.length - 1 ? 'border-b border-gray-100' : ''}`}
                >
                  <div className="text-gray-700 font-medium">{row.feature}</div>
                  <div className="w-28 flex justify-center">
                    {row.facturino ? <IconCheck /> : <IconX />}
                  </div>
                  <div className="w-28 flex justify-center">
                    {row.others ? <IconCheck /> : <IconX />}
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* ── PARTNER PROGRAM ──────────────────────────────────── */}
      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-50 to-cyan-50 pointer-events-none"></div>
        <div className="container relative z-10 px-4 sm:px-6">
          <div className="max-w-4xl mx-auto">
            <div className="grid md:grid-cols-2 gap-10 items-center">
              {/* Left: copy */}
              <div>
                <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">{t.partner.title}</h2>
                <p className="text-gray-600 text-lg mb-8 leading-relaxed">{t.partner.sub}</p>
                <ul className="space-y-4 mb-8">
                  {t.partner.benefits.map((b, i) => (
                    <li key={i} className="flex items-start gap-3">
                      <div className="flex-shrink-0 mt-0.5">
                        <IconCheck />
                      </div>
                      <span className="text-gray-700">{b}</span>
                    </li>
                  ))}
                </ul>
                <a
                  href="https://app.facturino.mk/partner/signup"
                  className="btn-primary inline-flex"
                >
                  {t.partner.cta}
                  <svg className="ml-2 w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
                  </svg>
                </a>
              </div>
              {/* Right: visual card */}
              <div className="relative">
                <div className="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 rounded-3xl blur-2xl transform scale-95"></div>
                <div className="relative glass-panel rounded-2xl p-8 space-y-6">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-green-600 font-bold text-xl">
                      <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div>
                      <p className="text-2xl font-extrabold text-gray-900">0 ден</p>
                      <p className="text-sm text-gray-500">{t.partner.visualRecurring}</p>
                    </div>
                  </div>
                  <div className="h-px bg-gray-200"></div>
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-xl bg-cyan-100 flex items-center justify-center">
                      <IconGrid />
                    </div>
                    <div>
                      <p className="text-lg font-bold text-gray-900">{t.partner.visualDashboard}</p>
                      <p className="text-sm text-gray-500">{t.partner.visualAnalytics}</p>
                    </div>
                  </div>
                  <div className="h-px bg-gray-200"></div>
                  <div className="flex items-center gap-3">
                    <div className="flex -space-x-1">
                      {[...Array(5)].map((_, i) => (
                        <span key={i}><IconStar /></span>
                      ))}
                    </div>
                    <p className="text-sm text-gray-500 font-medium">{t.partner.visualSupport}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ── BOTTOM CTA ───────────────────────────────────────── */}
      <section className="py-20 lg:py-28 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600"></div>
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div className="container relative z-10 text-center text-white px-4 sm:px-6">
          <h2 className="text-4xl md:text-5xl font-extrabold mb-6 tracking-tight">
            {t.bottomCta.title}
          </h2>
          <p className="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
            {t.bottomCta.sub}
          </p>
          <a
            href="https://app.facturino.mk/register"
            className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300 inline-flex items-center"
          >
            {t.bottomCta.cta}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        </div>
      </section>

    </main>
  )
}
