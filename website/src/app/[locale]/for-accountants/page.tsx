import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { getDictionary } from '@/i18n/dictionaries'
import PageHero from '@/components/PageHero'
import PartnerPricingGrid from '@/components/PartnerPricingGrid'

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
      mk: 'AI платформа за сметководители од 1,784 ден/месец. 14 дена бесплатен пробен период. Управувајте со десетици клиенти од едно место.',
      sq: 'Platforme AI per kontabiliste nga 1,784 den/muaj. 14 dite prove falas. Menaxhoni dhjetera kliente nga nje vend.',
      tr: 'Muhasebeciler icin AI platformu 1,784 den/ay\'dan. 14 gun ucretsiz deneme. Onlarca musteriyi tek yerden yonetin.',
      en: 'AI platform for accountants from 1,784 MKD/month. 14-day free trial. Manage dozens of clients from one place.',
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
          body: 'Секоја влезна фактура — отвори, препиши го бројот, износите, ставките, ДДВ... со часови на рачна работа.',
        },
        {
          icon: 'alert',
          title: 'е-Фактура доаѓа, а немате алатка',
          body: 'УЈП најавува задолжителна е-Фактура, а вашиот софтвер сè уште нема поддршка.',
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
          title: 'Сите клиенти на едно место',
          body: 'Еден систем за сите компании. Префрлете се од клиент на клиент со еден клик.',
          icon: 'grid',
        },
        {
          title: 'Увоз на банкарски изводи',
          body: 'Увезете извод од банка и системот автоматски ги порамнува со вашите фактури.',
          icon: 'bank',
        },
        {
          title: 'е-Фактура: веќе подготвени',
          body: 'Испраќање на е-Фактура преку УЈП со електронски потпис. Масовно испраќање за повеќе клиенти.',
          icon: 'invoice',
        },
        {
          title: 'AI асистент за побрзо работење',
          body: 'Кажи „Фактура за Марков 3000 ден" и AI ја креира. Сликај фактура — AI ја чита. Банкарски извод — AI го порамнува.',
          icon: 'ai',
        },
        {
          title: 'Залихи и магацинско работење',
          body: 'Приемница, издатница, нарачки за набавка и автоматско книжење. Контрола на усогласеност.',
          icon: 'warehouse',
        },
        {
          title: 'Годишна завршна сметка',
          body: '6 чекори: проверка → преглед → корекции → затворачки книжења → извештаи → заклучување. Без стрес, без грешки.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Годишно затворање за 30 минути',
      sub: 'Волшебникот ве води чекор по чекор — од проверка до заклучување.',
      steps: [
        { num: '1', label: 'Проверка', desc: 'Автоматска проверка: дали сите фактури се финализирани, банките порамнети, ДДВ пријавите поднесени.' },
        { num: '2', label: 'Преглед', desc: 'Биланс на состојба, биланс на успех и бруто биланс — на еден екран.' },
        { num: '3', label: 'Корекции', desc: 'Амортизација, резервирања, временски разграничувања — или прескокнете ако сè е книжено.' },
        { num: '4', label: 'Затворање', desc: 'Автоматски затворачки книжења: приходи/расходи → задржана добивка. Данок 10% пресметан.' },
        { num: '5', label: 'Извештаи', desc: 'Готови извештаи за ЦРРМ и електронско поднесување. Пресметка на данок на добивка.' },
        { num: '6', label: 'Заклучи', desc: 'Период заклучен. Рок до 15 март — вие завршивте. Поништување достапно 24 часа.' },
      ],
    },
    comparison: {
      title: 'Facturino наспроти конкуренцијата',
      sub: 'Единствена платформа со AI во Македонија',
      rows: [
        { feature: 'AI скенирање (сликај фактура → книжи)', facturino: '✓', others: '✗' },
        { feature: 'AI порамнување со банка', facturino: '✓', others: '✗' },
        { feature: 'AI чат асистент (на македонски)', facturino: '✓', others: '✗' },
        { feature: 'Генератор на УЈП обрасци', facturino: '✓', others: '✗' },
        { feature: 'ПП30 налози + масовен извоз за банка', facturino: '✓', others: '~ Рачно' },
        { feature: 'Заклучување на периоди', facturino: '✓', others: '✗' },
        { feature: 'Заштита од дупликати (6-слојна)', facturino: '✓', others: '✗' },
        { feature: 'Консолидација на групации', facturino: '✓', others: '✗' },
        { feature: 'Наплата + каматен калкулатор', facturino: '✓', others: '✗' },
        { feature: 'е-Фактура + електронски потпис', facturino: '✓', others: '~ Додаток' },
        { feature: 'Увоз на банкарски изводи (3 формати)', facturino: '✓', others: '~ Ограничено' },
        { feature: 'Мулти-компанија портал (до 100+)', facturino: '✓', others: '~ По лиценца' },
        { feature: 'Мобилна апликација', facturino: '✓', others: '✗' },
        { feature: '14 дена бесплатен пробен период', facturino: '✓', others: '✗' },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / Zonel',
    },
    pricing: {
      title: 'Пакети за сметководители',
      sub: 'Сите функции на секој пакет — разликата е во лимитите. 14 дена бесплатен пробен период.',
    },
    bottomCta: {
      title: 'Пробај бесплатно 14 дена',
      sub: 'Без кредитна картичка. Без обврска. Целосен пристап со Start лимити.',
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
          body: 'Çdo faturë hyrëse — hapni, rishkruani numrin, shumat, zërat, TVSH... orë punë manuale.',
        },
        {
          icon: 'alert',
          title: 'e-Fatura po vjen, por nuk keni mjet',
          body: 'UJP njofton e-Faturë të detyrueshme, por softueri juaj ende nuk ka mbështetje.',
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
          title: 'Të gjithë klientët në një vend',
          body: 'Një sistem për të gjitha kompanitë. Kaloni nga klienti në klient me një klikim.',
          icon: 'grid',
        },
        {
          title: 'Import i ekstrakteve bankare',
          body: 'Importoni ekstrakt nga banka dhe sistemi automatikisht i pajton me faturat tuaja.',
          icon: 'bank',
        },
        {
          title: 'e-Fatura: tashmë gati',
          body: 'Dërgim i e-Faturës përmes UJP me nënshkrim elektronik. Dërgim masiv për shumë klientë.',
          icon: 'invoice',
        },
        {
          title: 'Asistent AI për punë më të shpejtë',
          body: 'Thuaj "Faturë për Markov 3000 den" dhe AI e krijon. Skanoni faturë — AI e lexon. Ekstrakt bankar — AI e pajton.',
          icon: 'ai',
        },
        {
          title: 'Inventar dhe menaxhim i magazinës',
          body: 'Fletëhyrje, fletëdalje, porosi furnizimi dhe regjistrim automatik. Kontroll i pajtueshmërisë.',
          icon: 'warehouse',
        },
        {
          title: 'Llogaria përfundimtare vjetore',
          body: '6 hapa: kontrolli → rishikim → korrigjim → regjistrimet mbyllëse → raporte → mbyllje. Pa stres, pa gabime.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Mbyllja vjetore për 30 minuta',
      sub: 'Magjistari ju udhëzon hap pas hapi — nga kontrolli deri në mbyllje.',
      steps: [
        { num: '1', label: 'Kontrolli', desc: 'Kontroll automatik: a janë finalizuar të gjitha faturat, bankat të pajtuara, deklaratat e TVSH-së të dorëzuara.' },
        { num: '2', label: 'Rishikim', desc: 'Bilanci i gjendjes, bilanci i suksesit dhe bilanci verifikues — në një ekran.' },
        { num: '3', label: 'Korrigjim', desc: 'Amortizim, provigjione, akruale — ose kapërceni nëse gjithçka është regjistruar.' },
        { num: '4', label: 'Mbyllje', desc: 'Regjistrimet mbyllëse automatike: të ardhurat/shpenzimet → fitime të mbajtura. Tatimi 10% i llogaritur.' },
        { num: '5', label: 'Raporte', desc: 'Raporte gati për QKRM dhe dorëzim elektronik. Llogaritje e tatimit mbi fitimin.' },
        { num: '6', label: 'Mbyll', desc: 'Periudha e mbyllur. Afati deri 15 mars — ju keni mbaruar. Anulim i disponueshëm 24 orë.' },
      ],
    },
    comparison: {
      title: 'Facturino kundrejt konkurrencës',
      sub: 'Platforma e vetme me AI në Maqedoni',
      rows: [
        { feature: 'AI skanim (fotografo faturë → regjistro)', facturino: '✓', others: '✗' },
        { feature: 'AI pajtim me bankën', facturino: '✓', others: '✗' },
        { feature: 'AI asistent chat (në maqedonisht)', facturino: '✓', others: '✗' },
        { feature: 'Gjenerator i formularëve UJP', facturino: '✓', others: '✗' },
        { feature: 'Urdhra PP30 + eksport masiv për bankë', facturino: '✓', others: '~ Manual' },
        { feature: 'Mbyllje e periudhave', facturino: '✓', others: '✗' },
        { feature: 'Mbrojtje nga dublikatat (6-shtresore)', facturino: '✓', others: '✗' },
        { feature: 'Konsolidim i grupeve', facturino: '✓', others: '✗' },
        { feature: 'Arkëtim + llogaritës interesi', facturino: '✓', others: '✗' },
        { feature: 'e-Fatura + nënshkrim elektronik', facturino: '✓', others: '~ Shtesë' },
        { feature: 'Import ekstrakte bankare (3 formate)', facturino: '✓', others: '~ Kufizuar' },
        { feature: 'Portal shumë-kompanish (deri 100+)', facturino: '✓', others: '~ Me licencë' },
        { feature: 'Aplikacion celular', facturino: '✓', others: '✗' },
        { feature: '14 ditë provë falas', facturino: '✓', others: '✗' },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / Zonel',
    },
    pricing: {
      title: 'Paketat për kontabilistë',
      sub: 'Të gjitha funksionet në çdo paketë — ndryshimi është në limitet. 14 ditë provë falas.',
    },
    bottomCta: {
      title: 'Provoni falas 14 ditë',
      sub: 'Pa kartë krediti. Pa detyrim. Qasje e plotë me limitet Start.',
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
          body: 'Her gelen fatura — açın, numarayı, tutarları, kalemleri, KDV\'yi kopyalayın... saatlerce elle çalışma.',
        },
        {
          icon: 'alert',
          title: 'e-Fatura geliyor ama aracınız yok',
          body: 'UJP zorunlu e-Fatura duyuruyor, ancak yazılımınızda henüz destek yok.',
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
          title: 'Tüm müşteriler tek yerde',
          body: 'Tüm şirketler için tek sistem. Bir tıkla müşteriden müşteriye geçin.',
          icon: 'grid',
        },
        {
          title: 'Banka ekstresi ithalatı',
          body: 'Bankadan ekstre aktarın ve sistem otomatik olarak faturalarınızla eşleştirir.',
          icon: 'bank',
        },
        {
          title: 'e-Fatura: şimdiden hazır',
          body: 'UJP üzerinden elektronik imzayla e-Fatura gönderimi. Birden fazla müşteri için toplu gönderim.',
          icon: 'invoice',
        },
        {
          title: 'Daha hızlı çalışma için AI asistan',
          body: '"Markov için fatura 3000 den" deyin ve AI oluşturur. Fatura fotoğraflayın — AI okur. Banka ekstresi — AI eşleştirir.',
          icon: 'ai',
        },
        {
          title: 'Stok ve depo yönetimi',
          body: 'Giriş fişi, çıkış fişi, satın alma siparişleri ve otomatik muhasebe kaydı. Uyum kontrolü.',
          icon: 'warehouse',
        },
        {
          title: 'Yıl sonu kapanış hesabı',
          body: '6 adım: kontrol → inceleme → düzeltme → kapanış kayıtları → raporlar → kilitleme. Stressiz, hatasız.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Yıl sonu kapanışı 30 dakikada',
      sub: 'Sihirbaz sizi adım adım yönlendirir — kontrolden kilitlemeye.',
      steps: [
        { num: '1', label: 'Kontrol', desc: 'Otomatik kontrol: tüm faturalar tamamlanmış mı, bankalar mutabık mı, KDV beyannameleri verilmiş mi.' },
        { num: '2', label: 'İnceleme', desc: 'Bilanço, gelir tablosu ve mizan — tek ekranda.' },
        { num: '3', label: 'Düzeltme', desc: 'Amortisman, karşılık, tahakkuk — veya hepsi kaydedilmişse atlayın.' },
        { num: '4', label: 'Kapanış', desc: 'Otomatik kapanış kayıtları: gelir/gider → dağıtılmamış kâr. %10 vergi hesaplanır.' },
        { num: '5', label: 'Raporlar', desc: 'CRMS ve elektronik başvuru için hazır raporlar. Kurumlar vergisi hesaplaması.' },
        { num: '6', label: 'Kilitle', desc: 'Dönem kilitlendi. 15 Mart\'a kadar süre var — siz bitirdiniz. 24 saat geri alma mümkün.' },
      ],
    },
    comparison: {
      title: 'Facturino rakiplere karşı',
      sub: 'Makedonya\'da AI\'lı tek platform',
      rows: [
        { feature: 'AI tarama (fatura fotoğrafla → kaydet)', facturino: '✓', others: '✗' },
        { feature: 'AI banka mutabakatı', facturino: '✓', others: '✗' },
        { feature: 'AI sohbet asistanı (Makedoncada)', facturino: '✓', others: '✗' },
        { feature: 'UJP form oluşturucu', facturino: '✓', others: '✗' },
        { feature: 'PP30 emirleri + toplu banka dışa aktarım', facturino: '✓', others: '~ Manuel' },
        { feature: 'Dönem kilitleme', facturino: '✓', others: '✗' },
        { feature: 'Mükerrer koruma (6 katmanlı)', facturino: '✓', others: '✗' },
        { feature: 'Grup konsolidasyonu', facturino: '✓', others: '✗' },
        { feature: 'Tahsilat + faiz hesaplayıcı', facturino: '✓', others: '✗' },
        { feature: 'e-Fatura + elektronik imza', facturino: '✓', others: '~ Eklenti' },
        { feature: 'Banka ekstresi ithalatı (3 format)', facturino: '✓', others: '~ Sınırlı' },
        { feature: 'Çok şirketli portal (100+ kadar)', facturino: '✓', others: '~ Lisans başına' },
        { feature: 'Mobil uygulama', facturino: '✓', others: '✗' },
        { feature: '14 gün ücretsiz deneme', facturino: '✓', others: '✗' },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / Zonel',
    },
    pricing: {
      title: 'Muhasebeciler için paketler',
      sub: 'Her pakette tüm özellikler — fark limitlerde. 14 gün ücretsiz deneme.',
    },
    bottomCta: {
      title: '14 gün ücretsiz deneyin',
      sub: 'Kredi kartı gerekmez. Taahhüt yok. Start limitleriyle tam erişim.',
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
          body: 'Every incoming invoice — open it, retype the number, amounts, line items, VAT... hours of manual work.',
        },
        {
          icon: 'alert',
          title: 'e-Invoice is coming but you have no tools',
          body: 'UJP announces mandatory e-Invoice, but your software still lacks support.',
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
          title: 'All clients in one place',
          body: 'One system for all companies. Switch from client to client with a single click.',
          icon: 'grid',
        },
        {
          title: 'Bank statement import',
          body: 'Import bank statements and the system automatically matches them with your invoices.',
          icon: 'bank',
        },
        {
          title: 'e-Invoice: already ready',
          body: 'Send e-Invoices via UJP with electronic signature. Bulk sending for multiple clients.',
          icon: 'invoice',
        },
        {
          title: 'AI assistant for faster work',
          body: 'Say "Invoice for Markov 3000 MKD" and AI creates it. Photo an invoice — AI reads it. Bank statement — AI reconciles it.',
          icon: 'ai',
        },
        {
          title: 'Inventory and warehouse management',
          body: 'Goods received, issued, purchase orders and automatic posting. Compliance control.',
          icon: 'warehouse',
        },
        {
          title: 'Year-end closing account',
          body: '6 steps: checklist → review → adjustments → closing entries → reports → lock. No stress, no errors.',
          icon: 'yearend',
        },
      ],
    },
    yearEnd: {
      title: 'Year-end closing in 30 minutes',
      sub: 'The wizard guides you step by step — from checklist to lock.',
      steps: [
        { num: '1', label: 'Checklist', desc: 'Automatic checks: are all invoices finalized, banks reconciled, VAT returns filed.' },
        { num: '2', label: 'Review', desc: 'Balance sheet, income statement, and trial balance — on one screen.' },
        { num: '3', label: 'Adjust', desc: 'Depreciation, provisions, accruals — or skip if everything is already recorded.' },
        { num: '4', label: 'Close', desc: 'Automatic closing entries: revenue/expenses → retained earnings. 10% tax calculated.' },
        { num: '5', label: 'Reports', desc: 'Ready-made reports for CRMS and electronic filing. Corporate tax calculation.' },
        { num: '6', label: 'Lock', desc: 'Period locked. Deadline March 15 — you\'re done. Undo available for 24 hours.' },
      ],
    },
    comparison: {
      title: 'Facturino vs the competition',
      sub: 'The only platform with AI in Macedonia',
      rows: [
        { feature: 'AI scanning (photo invoice → post)', facturino: '✓', others: '✗' },
        { feature: 'AI bank reconciliation', facturino: '✓', others: '✗' },
        { feature: 'AI chat assistant (in Macedonian)', facturino: '✓', others: '✗' },
        { feature: 'UJP tax forms generator', facturino: '✓', others: '✗' },
        { feature: 'PP30 orders + bulk bank export', facturino: '✓', others: '~ Manual' },
        { feature: 'Period locking', facturino: '✓', others: '✗' },
        { feature: 'Duplicate protection (6-layer)', facturino: '✓', others: '✗' },
        { feature: 'Group consolidation', facturino: '✓', others: '✗' },
        { feature: 'Collections + interest calculator', facturino: '✓', others: '✗' },
        { feature: 'e-Invoice + digital signature', facturino: '✓', others: '~ Add-on' },
        { feature: 'Bank statement import (3 formats)', facturino: '✓', others: '~ Limited' },
        { feature: 'Multi-company portal (up to 100+)', facturino: '✓', others: '~ Per license' },
        { feature: 'Mobile app', facturino: '✓', others: '✗' },
        { feature: '14-day free trial', facturino: '✓', others: '✗' },
      ],
      facturinoLabel: 'Facturino',
      othersLabel: 'PANTHEON / Zonel',
    },
    pricing: {
      title: 'Plans for accountants',
      sub: 'All features in every plan — the difference is in the limits. 14-day free trial.',
    },
    bottomCta: {
      title: 'Try free for 14 days',
      sub: 'No credit card required. No commitment. Full access with Start limits.',
      cta: 'Start for free',
    },
  },
} as const

/* SVG icon helpers */
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
function IconPartial() {
  return (
    <svg className="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
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
  const dict = await getDictionary(locale)
  const pp = dict.pricingPage!

  return (
    <main id="main-content" className="overflow-x-hidden">

      <PageHero
        image="/assets/images/hero_accountants.png"
        alt="Accountant working with dual monitors managing multiple client portfolios"
        title={t.hero.headline}
        subtitle={t.hero.sub}
        badge={t.hero.badge}
        cta={{ label: t.hero.cta, href: 'https://app.facturino.mk/partner/signup' }}
      />

      {/* PAIN POINTS */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-6 md:mb-14">
            <h2 className="text-2xl md:text-4xl font-bold mb-3 md:mb-4 text-gray-900">{t.pains.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-red-400 to-orange-400 mx-auto rounded-full"></div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-6 max-w-5xl mx-auto">
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

      {/* SOLUTIONS */}
      <section className="section">
        <div className="absolute inset-0 bg-grid-pattern opacity-[0.03] pointer-events-none"></div>
        <div className="container relative z-10 px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-6 md:mb-14">
            <h2 className="text-2xl md:text-4xl font-bold mb-3 md:mb-4 text-gray-900">{t.solutions.title}</h2>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-6 max-w-5xl mx-auto">
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

      {/* YEAR-END CLOSING WIZARD */}
      <section className="section bg-gradient-to-br from-emerald-50 to-cyan-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-6 md:mb-14">
            <h2 className="text-2xl md:text-4xl font-bold mb-3 md:mb-4 text-gray-900">{t.yearEnd.title}</h2>
            <p className="text-sm md:text-lg text-gray-600">{t.yearEnd.sub}</p>
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

      {/* COMPARISON TABLE */}
      <section className="section bg-slate-50">
        <div className="container px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-6 md:mb-14">
            <h2 className="text-2xl md:text-4xl font-bold mb-2 md:mb-3 text-gray-900">{t.comparison.title}</h2>
            <p className="text-sm md:text-lg text-gray-600">{t.comparison.sub}</p>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full mt-4"></div>
          </div>

          <div className="max-w-3xl mx-auto">
            <div className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lg">
              <div className="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-6 py-4 bg-gradient-to-r from-indigo-600 to-cyan-600 text-white text-sm font-bold">
                <div></div>
                <div className="w-28 text-center">{t.comparison.facturinoLabel}</div>
                <div className="w-28 text-center">{t.comparison.othersLabel}</div>
              </div>
              {t.comparison.rows.map((row: { feature: string; facturino: string; others: string }, i: number) => {
                const othersIsNo = row.others === '✗'
                const othersIsPartial = row.others.startsWith('~')
                return (
                  <div
                    key={i}
                    className={`grid grid-cols-[1fr_auto_auto] items-center gap-4 px-6 py-3.5 text-sm ${
                      i % 2 === 0 ? 'bg-white' : 'bg-slate-50'
                    } ${i < t.comparison.rows.length - 1 ? 'border-b border-gray-100' : ''}`}
                  >
                    <div className="text-gray-700 font-medium">{row.feature}</div>
                    <div className="w-28 flex justify-center items-center gap-1">
                      <IconCheck />
                    </div>
                    <div className="w-28 flex justify-center items-center gap-1">
                      {othersIsNo && <IconX />}
                      {othersIsPartial && <IconPartial />}
                      {othersIsPartial && <span className="text-xs text-amber-600 font-medium">{row.others.replace('~ ', '')}</span>}
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        </div>
      </section>

      {/* PRICING WITH TOGGLE */}
      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-50 to-cyan-50 pointer-events-none"></div>
        <div className="container relative z-10 px-4 sm:px-6">
          <div className="text-center max-w-3xl mx-auto mb-6 md:mb-10">
            <h2 className="text-2xl md:text-4xl font-bold mb-3 md:mb-4 text-gray-900">{t.pricing.title}</h2>
            <p className="text-sm md:text-lg text-gray-600">{t.pricing.sub}</p>
            <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full mt-4"></div>
          </div>

          <div className="max-w-6xl mx-auto">
            <PartnerPricingGrid
              plans={pp.partnerPlans as { name: string; price: string; priceYearly?: string; period: string; periodYearly?: string; bullets: string[]; popular: boolean }[]}
              popularBadge={pp.popularBadge}
              ctaPartner={pp.ctaPartner}
              includesPrevious={pp.includesPrevious}
              billingToggleMonthly={pp.billingToggleMonthly || 'Monthly'}
              billingToggleYearly={pp.billingToggleYearly || 'Yearly'}
              billingYearlySave={pp.billingYearlySave || '2 months free'}
            />
            <p className="text-center text-sm text-gray-500 mt-4">{pp.partnerSubtitle}</p>
          </div>
        </div>
      </section>

      {/* BOTTOM CTA */}
      <section className="py-12 lg:py-28 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600"></div>
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
        </div>

        <div className="container relative z-10 text-center text-white px-4 sm:px-6">
          <h2 className="text-2xl md:text-5xl font-extrabold mb-4 md:mb-6 tracking-tight">
            {t.bottomCta.title}
          </h2>
          <p className="text-base md:text-xl text-indigo-100 mb-6 md:mb-10 max-w-2xl mx-auto">
            {t.bottomCta.sub}
          </p>
          <a
            href="https://app.facturino.mk/partner/signup"
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
