import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/facturino-vs-excel', {
    title: {
      mk: 'Facturino vs Excel: Зошто табели не се доволни — Facturino',
      en: 'Facturino vs Excel: Why Spreadsheets Aren\'t Enough — Facturino',
      sq: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë — Facturino',
      tr: 'Facturino vs Excel: Neden tablolar yeterli değil — Facturino',
    },
    description: {
      mk: 'Споредба на Facturino и Excel за фактурирање и сметководство. Дознајте зошто табелите не се доволни за модерен бизнис.',
      en: 'Compare Facturino and Excel for invoicing and accounting. Learn why spreadsheets are not enough for modern business.',
      sq: 'Krahasoni Facturino dhe Excel për faturim dhe kontabilitet. Mësoni pse tabelat nuk mjaftojnë për biznesin modern.',
      tr: 'Faturalama ve muhasebe için Facturino ve Excel karşılaştırması. Tabloların modern iş için neden yeterli olmadığını öğrenin.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Производ',
    title: 'Facturino vs Excel: Зошто табели не се доволни',
    publishDate: '19 февруари 2026',
    readTime: '6 мин читање',
    intro: 'Многу мали и средни бизниси во Македонија сеуште ги водат фактурите и финансиите преку Excel табели. Иако Excel е моќна алатка за пресметки, тој едноставно не е создаден за фактурирање, сметководствена усогласеност и деловно управување. Во оваа статија го споредуваме Excel со Facturino и објаснуваме зошто преминот кон специјализиран софтвер е неопходен за растечки бизнис.',
    sections: [
      {
        title: 'Проблеми со Excel за фактурирање',
        content: 'Excel може да изгледа како едноставно решение за почетоците на бизнисот, но како што компанијата расте, проблемите стануваат сериозни. Мануелното внесување на податоци е склоно на грешки, а една погрешна формула може да доведе до неточни фактури и даночни проблеми.',
        items: [
          'Мануелни грешки — секое мануелно внесување носи ризик од грешка во износи, ДДВ или податоци за клиент',
          'Нема автоматизација — секоја фактура мора рачно да се креира, нумерира и испраќа',
          'Нема проверка на усогласеност — Excel не знае дали вашата фактура ги исполнува законските барања на УЈП',
          'Хаос со верзии — повеќе копии на ист фајл, нејасно која е последната верзија',
          'Нема повеќекориснички пристап — само една личност може да работи на фајлот истовремено',
          'Нема ревизорска трага — невозможно е да се следи кој што променил и кога',
          'Нема поддршка за е-Фактура — Excel не може да генерира UBL XML формат потребен за е-Фактура',
          'Нема автоматски извештаи — секој извештај мора рачно да се составува',
        ],
        steps: null,
      },
      {
        title: 'Предности на Facturino',
        content: 'Facturino е специјализиран софтвер за фактурирање и сметководство, создаден конкретно за потребите на македонскиот бизнис. За разлика од Excel, Facturino автоматизира повторливите задачи и обезбедува законска усогласеност од самиот почеток.',
        items: [
          'Автоматски пресметки — ДДВ, попусти и вкупни суми се пресметуваат автоматски без ризик од формулски грешки',
          'Законска усогласеност — секоја фактура автоматски ги содржи сите задолжителни полиња барани од УЈП',
          'е-Фактура подготвеност — генерирање на UBL XML фактури подготвени за испраќање преку е-Фактура системот',
          'Облак базиран — пристап од било каде, на било кој уред, без инсталација',
          'Повеќекориснички — целиот тим може да работи истовремено со различни нивоа на пристап',
          'Извештаи во реално време — приходи, расходи, профит и ДДВ извештаи се генерираат автоматски',
          'Банковна интеграција — PSD2 поврзување со вашата банка за автоматско препознавање на уплати',
          'Ревизорска трага — секоја промена е запишана со датум, време и корисник',
        ],
        steps: null,
      },
      {
        title: 'Споредба точка по точка',
        content: null,
        items: null,
        steps: [
          { step: 'Креирање фактура', desc: 'Excel: 10-15 минути за рачно пополнување на шаблон. Facturino: 2 минути со автоматско пополнување на податоци за клиент, производи и ДДВ.' },
          { step: 'ДДВ пресметка', desc: 'Excel: Рачни формули кои може да содржат грешки. Facturino: Автоматска пресметка по стапки од 5% и 18% со валидација.' },
          { step: 'Испраќање на клиент', desc: 'Excel: Рачно зачувување како PDF, прикачување на e-mail. Facturino: Еден клик за испраќање по e-mail или генерирање на линк за плаќање.' },
          { step: 'е-Фактура', desc: 'Excel: Невозможно без дополнителен софтвер. Facturino: Вградена поддршка за UBL XML и директно испраќање.' },
          { step: 'Извештаи', desc: 'Excel: Часови рачно составување на пивот табели. Facturino: Моментални извештаи за приходи, расходи, ДДВ и профитабилност.' },
          { step: 'Безбедност', desc: 'Excel: Фајлот може да се изгуби, корумпира или случајно избрише. Facturino: Облак со автоматски бекап, енкрипција и контрола на пристап.' },
        ],
      },
      {
        title: 'Кога Excel е навистина доволен?',
        content: 'Да бидеме фер — Excel може да биде адекватен за фрилансери со 1-2 фактури месечно кои немаат потреба од е-Фактура. Но штом бизнисот достигне 5+ фактури месечно, има вработени или работи со ДДВ, табелите стануваат повеќе пречка отколку помош. Преминот кон Facturino не е само надградба — тоа е инвестиција во точност, ефикасност и спокојство.',
        items: null,
        steps: null,
      },
      {
        title: 'Лесен премин од Excel',
        content: 'Разбираме дека промената може да изгледа застрашувачки. Затоа Facturino нуди лесен процес на премин. Можете да ги импортирате вашите клиенти, производи и фактури од CSV/Excel фајлови директно во Facturino. Нашиот тим за поддршка ви помага во секој чекор од процесот.',
        items: [
          'Импорт на постоечки клиенти од Excel/CSV',
          'Импорт на каталог на производи и услуги',
          'Историски фактури може да се внесат масовно',
          'Бесплатна техничка помош при премин',
          'Бесплатен план за тестирање пред целосен премин',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'zosto-facturino', title: '10 причини зошто македонски бизниси го избираат Facturino' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
      { slug: 'recurring-invoices-mk', title: 'Повторувачки фактури: Автоматизирајте ја наплатата' },
    ],
    cta: {
      title: 'Преминете од Excel кон Facturino',
      desc: 'Започнете бесплатно и видете зошто илјадници бизниси веќе го направија преминот.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Product',
    title: 'Facturino vs Excel: Why Spreadsheets Aren\'t Enough',
    publishDate: 'February 19, 2026',
    readTime: '6 min read',
    intro: 'Many small and medium businesses in Macedonia still manage their invoices and finances through Excel spreadsheets. While Excel is a powerful calculation tool, it simply was not designed for invoicing, accounting compliance, and business management. In this article, we compare Excel with Facturino and explain why switching to specialized software is essential for a growing business.',
    sections: [
      {
        title: 'Problems With Excel for Invoicing',
        content: 'Excel may seem like a simple solution in the early days of a business, but as the company grows, the problems become serious. Manual data entry is prone to errors, and a single wrong formula can lead to inaccurate invoices and tax issues.',
        items: [
          'Manual errors — every manual entry carries the risk of mistakes in amounts, VAT, or client data',
          'No automation — every invoice must be manually created, numbered, and sent',
          'No compliance checking — Excel has no way to verify whether your invoice meets UJP legal requirements',
          'Version chaos — multiple copies of the same file with no clarity on which is the latest version',
          'No multi-user access — only one person can work on the file at a time',
          'No audit trail — impossible to track who changed what and when',
          'No e-Invoice support — Excel cannot generate the UBL XML format required for e-Invoice',
          'No automatic reports — every report must be manually assembled',
        ],
        steps: null,
      },
      {
        title: 'Advantages of Facturino',
        content: 'Facturino is specialized invoicing and accounting software built specifically for the needs of Macedonian businesses. Unlike Excel, Facturino automates repetitive tasks and ensures legal compliance from the very start.',
        items: [
          'Automatic calculations — VAT, discounts, and totals are calculated automatically without the risk of formula errors',
          'Legal compliance — every invoice automatically includes all mandatory fields required by UJP',
          'e-Invoice ready — generates UBL XML invoices ready for submission through the e-Invoice system',
          'Cloud-based — access from anywhere, on any device, without installation',
          'Multi-user — the entire team can work simultaneously with different access levels',
          'Real-time reports — revenue, expenses, profit, and VAT reports are generated automatically',
          'Bank integration — PSD2 connection to your bank for automatic payment recognition',
          'Audit trail — every change is logged with date, time, and user',
        ],
        steps: null,
      },
      {
        title: 'Point-by-Point Comparison',
        content: null,
        items: null,
        steps: [
          { step: 'Creating Invoices', desc: 'Excel: 10-15 minutes to manually fill in a template. Facturino: 2 minutes with auto-populated client data, products, and VAT.' },
          { step: 'VAT Calculation', desc: 'Excel: Manual formulas that may contain errors. Facturino: Automatic calculation at 5% and 18% rates with validation.' },
          { step: 'Sending to Clients', desc: 'Excel: Manually save as PDF, attach to email. Facturino: One click to send via email or generate a payment link.' },
          { step: 'e-Invoice', desc: 'Excel: Impossible without additional software. Facturino: Built-in UBL XML support and direct submission.' },
          { step: 'Reports', desc: 'Excel: Hours of manually building pivot tables. Facturino: Instant reports for revenue, expenses, VAT, and profitability.' },
          { step: 'Security', desc: 'Excel: Files can be lost, corrupted, or accidentally deleted. Facturino: Cloud with automatic backup, encryption, and access control.' },
        ],
      },
      {
        title: 'When Is Excel Actually Enough?',
        content: 'To be fair, Excel can be adequate for freelancers with 1-2 invoices per month who do not need e-Invoice. But as soon as a business reaches 5+ invoices per month, has employees, or deals with VAT, spreadsheets become more of an obstacle than a help. Switching to Facturino is not just an upgrade — it is an investment in accuracy, efficiency, and peace of mind.',
        items: null,
        steps: null,
      },
      {
        title: 'Easy Migration From Excel',
        content: 'We understand that change can feel daunting. That is why Facturino offers a smooth migration process. You can import your clients, products, and invoices from CSV/Excel files directly into Facturino. Our support team helps you at every step of the process.',
        items: [
          'Import existing clients from Excel/CSV',
          'Import your product and service catalog',
          'Historical invoices can be bulk imported',
          'Free technical assistance during migration',
          'Free plan for testing before full migration',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'zosto-facturino', title: '10 Reasons Macedonian Businesses Choose Facturino' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
      { slug: 'recurring-invoices-mk', title: 'Recurring Invoices: Automate Your Billing' },
    ],
    cta: {
      title: 'Switch From Excel to Facturino',
      desc: 'Start free and see why thousands of businesses have already made the switch.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Produkt',
    title: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë',
    publishDate: '19 shkurt 2026',
    readTime: '6 min lexim',
    intro: 'Shumë biznese të vogla dhe të mesme në Maqedoni ende i menaxhojnë faturat dhe financat përmes tabelave Excel. Edhe pse Excel është mjet i fuqishëm llogaritjeje, ai thjesht nuk është krijuar për faturim, përputhshmëri kontabël dhe menaxhim biznesi. Në këtë artikull, krahasojmë Excel me Facturino dhe shpjegojmë pse kalimi në softuer të specializuar është thelbësor për një biznes në rritje.',
    sections: [
      {
        title: 'Problemet me Excel për faturim',
        content: 'Excel mund të duket si zgjidhje e thjeshtë në ditët e para të biznesit, por ndërsa kompania rritet, problemet bëhen serioze. Futja manuale e të dhënave është e prirur ndaj gabimeve, dhe një formulë e vetme e gabuar mund të çojë në fatura të pasakta dhe probleme tatimore.',
        items: [
          'Gabime manuale — çdo futje manuale mbart rrezikun e gabimeve në shuma, TVSH ose të dhëna të klientit',
          'Asnjë automatizim — çdo faturë duhet krijuar, numëruar dhe dërguar manualisht',
          'Asnjë kontroll përputhshmërie — Excel nuk ka mënyrë të verifikojë nëse fatura juaj plotëson kërkesat ligjore të UJP',
          'Kaos versionesh — kopje të shumta të skedarit të njëjtë pa qartësi se cila është versioni i fundit',
          'Asnjë qasje shumëpërdorueshe — vetëm një person mund të punojë në skedar njëkohësisht',
          'Asnjë gjurmë auditimi — e pamundur të ndiqet kush çfarë ndryshoi dhe kur',
          'Asnjë mbështetje për e-Faturë — Excel nuk mund të gjenerojë formatin UBL XML të kërkuar për e-Faturë',
          'Asnjë raport automatik — çdo raport duhet montuar manualisht',
        ],
        steps: null,
      },
      {
        title: 'Përparësitë e Facturino',
        content: 'Facturino është softuer i specializuar faturimi dhe kontabiliteti i ndërtuar posaçërisht për nevojat e bizneseve maqedonase. Ndryshe nga Excel, Facturino automatizon detyrat e përsëritura dhe siguron përputhshmërinë ligjore që nga fillimi.',
        items: [
          'Llogaritje automatike — TVSH, zbritjet dhe totalet llogariten automatikisht pa rrezik gabimesh formulash',
          'Përputhshmëri ligjore — çdo faturë automatikisht përfshin të gjitha fushat e detyrueshme të kërkuara nga UJP',
          'Gati për e-Faturë — gjeneron fatura UBL XML gati për dorëzim përmes sistemit e-Faturë',
          'Bazuar në cloud — qasje nga kudo, në çdo pajisje, pa instalim',
          'Shumëpërdorues — i gjithë ekipi mund të punojë njëkohësisht me nivele të ndryshme qasje',
          'Raporte në kohë reale — të ardhurat, shpenzimet, fitimi dhe raportet e TVSH gjenerohen automatikisht',
          'Integrim bankar — lidhje PSD2 me bankën tuaj për njohje automatike të pagesave',
          'Gjurmë auditimi — çdo ndryshim regjistrohet me datë, kohë dhe përdorues',
        ],
        steps: null,
      },
      {
        title: 'Krahasim pikë për pikë',
        content: null,
        items: null,
        steps: [
          { step: 'Krijimi i faturave', desc: 'Excel: 10-15 minuta për plotësim manual të shabllonit. Facturino: 2 minuta me plotësim automatik të të dhënave të klientit, produkteve dhe TVSH.' },
          { step: 'Llogaritja e TVSH', desc: 'Excel: Formula manuale që mund të përmbajnë gabime. Facturino: Llogaritje automatike me norma 5% dhe 18% me validim.' },
          { step: 'Dërgimi te klienti', desc: 'Excel: Ruajtje manuale si PDF, bashkëngjitje në email. Facturino: Një klik për dërgim me email ose gjenerim të linkut të pagesës.' },
          { step: 'e-Fatura', desc: 'Excel: E pamundur pa softuer shtesë. Facturino: Mbështetje e integruar UBL XML dhe dorëzim direkt.' },
          { step: 'Raporte', desc: 'Excel: Orë montimi manual të tabelave pivot. Facturino: Raporte të menjëhershme për të ardhura, shpenzime, TVSH dhe përfitueshmëri.' },
          { step: 'Siguria', desc: 'Excel: Skedarët mund të humbasin, korruptohen ose fshihen aksidentalisht. Facturino: Cloud me backup automatik, enkriptim dhe kontroll qasje.' },
        ],
      },
      {
        title: 'Kur është Excel vërtet i mjaftueshëm?',
        content: 'Për të qenë të drejtë, Excel mund të jetë adekuat për freelancer-ë me 1-2 fatura në muaj që nuk kanë nevojë për e-Faturë. Por sapo biznesi arrin 5+ fatura në muaj, ka punonjës ose merret me TVSH, tabelat bëhen më shumë pengesë sesa ndihmë. Kalimi në Facturino nuk është vetëm përmirësim — është investim në saktësi, efiçencë dhe qetësi mendore.',
        items: null,
        steps: null,
      },
      {
        title: 'Migrim i lehtë nga Excel',
        content: 'E kuptojmë që ndryshimi mund të duket frikësues. Prandaj Facturino ofron proces migrimi të butë. Mund t\'i importoni klientët, produktet dhe faturat tuaja nga skedarë CSV/Excel direkt në Facturino. Ekipi ynë i mbështetjes ju ndihmon në çdo hap të procesit.',
        items: [
          'Import i klientëve ekzistues nga Excel/CSV',
          'Import i katalogut të produkteve dhe shërbimeve',
          'Faturat historike mund të importohen në masë',
          'Ndihmë teknike falas gjatë migrimit',
          'Plan falas për testim para migrimit të plotë',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'zosto-facturino', title: '10 arsye pse bizneset maqedonase zgjedhin Facturino' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
      { slug: 'recurring-invoices-mk', title: 'Faturat e përsëritura: Automatizoni arkëtimin' },
    ],
    cta: {
      title: 'Kaloni nga Excel në Facturino',
      desc: 'Filloni falas dhe shihni pse mijëra biznese e kanë bërë tashmë kalimin.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Ürün',
    title: 'Facturino vs Excel: Neden tablolar yeterli değil',
    publishDate: '19 Şubat 2026',
    readTime: '6 dk okuma',
    intro: 'Makedonya\'daki birçok küçük ve orta ölçekli işletme hala faturalarını ve finanslarını Excel tabloları aracılığıyla yönetmektedir. Excel güçlü bir hesaplama aracı olsa da, faturalama, muhasebe uyumluluğu ve iş yönetimi için tasarlanmamıştır. Bu makalede Excel\'i Facturino ile karşılaştırıyor ve büyüyen bir işletme için uzmanlaşmış yazılıma geçmenin neden gerekli olduğunu açıklıyoruz.',
    sections: [
      {
        title: 'Faturalama İçin Excel\'in Sorunları',
        content: 'Excel, bir işletmenin ilk günlerinde basit bir çözüm gibi görünebilir, ancak şirket büyüdükçe sorunlar ciddileşir. Manuel veri girişi hatalara açıktır ve tek bir yanlış formül hatalı faturalara ve vergi sorunlarına yol açabilir.',
        items: [
          'Manuel hatalar — her manuel giriş tutarlarda, KDV\'de veya müşteri verilerinde hata riski taşır',
          'Otomasyon yok — her fatura manuel olarak oluşturulmalı, numaralandırılmalı ve gönderilmelidir',
          'Uyumluluk kontrolü yok — Excel, faturanızın UJP yasal gereksinimlerini karşılayıp karşılamadığını doğrulayamaz',
          'Versiyon kaaosu — aynı dosyanın birden fazla kopyası, hangisinin en son olduğu belirsiz',
          'Çok kullanıcılı erişim yok — dosya üzerinde aynı anda yalnızca bir kişi çalışabilir',
          'Denetim izi yok — kimin neyi ne zaman değiştirdiğini takip etmek imkansız',
          'e-Fatura desteği yok — Excel, e-Fatura için gereken UBL XML formatını oluşturamaz',
          'Otomatik raporlar yok — her rapor manuel olarak hazırlanmalıdır',
        ],
        steps: null,
      },
      {
        title: 'Facturino\'nun Avantajları',
        content: 'Facturino, özellikle Makedon işletmelerinin ihtiyaçları için oluşturulmuş uzmanlaşmış faturalama ve muhasebe yazılımıdır. Excel\'den farklı olarak Facturino, tekrarlayan görevleri otomatikleştirir ve en başından yasal uyumluluğu sağlar.',
        items: [
          'Otomatik hesaplamalar — KDV, indirimler ve toplamlar formül hatası riski olmadan otomatik olarak hesaplanır',
          'Yasal uyumluluk — her fatura UJP tarafından istenen tüm zorunlu alanları otomatik olarak içerir',
          'e-Fatura hazır — e-Fatura sistemi üzerinden gönderime hazır UBL XML faturaları oluşturur',
          'Bulut tabanlı — kurulum olmadan herhangi bir yerden, herhangi bir cihazdan erişim',
          'Çok kullanıcılı — tüm ekip farklı erişim seviyeleriyle aynı anda çalışabilir',
          'Gerçek zamanlı raporlar — gelir, gider, kar ve KDV raporları otomatik olarak oluşturulur',
          'Banka entegrasyonu — otomatik ödeme tanıma için bankanızla PSD2 bağlantısı',
          'Denetim izi — her değişiklik tarih, saat ve kullanıcı ile kaydedilir',
        ],
        steps: null,
      },
      {
        title: 'Nokta Nokta Karşılaştırma',
        content: null,
        items: null,
        steps: [
          { step: 'Fatura Oluşturma', desc: 'Excel: Şablonu manuel olarak doldurmak için 10-15 dakika. Facturino: Otomatik müşteri verileri, ürünler ve KDV ile 2 dakika.' },
          { step: 'KDV Hesaplama', desc: 'Excel: Hata içerebilen manuel formüller. Facturino: Doğrulama ile %5 ve %18 oranlarında otomatik hesaplama.' },
          { step: 'Müşteriye Gönderme', desc: 'Excel: Manuel olarak PDF olarak kaydetme, e-postaya ekleme. Facturino: E-posta ile göndermek veya ödeme bağlantısı oluşturmak için tek tıklama.' },
          { step: 'e-Fatura', desc: 'Excel: Ek yazılım olmadan imkansız. Facturino: Yerleşik UBL XML desteği ve doğrudan gönderim.' },
          { step: 'Raporlar', desc: 'Excel: Pivot tabloları manuel olarak oluşturmak için saatler. Facturino: Gelir, gider, KDV ve karlılık için anında raporlar.' },
          { step: 'Güvenlik', desc: 'Excel: Dosyalar kaybolabilir, bozulabilir veya yanlışlıkla silinebilir. Facturino: Otomatik yedekleme, şifreleme ve erişim kontrolü ile bulut.' },
        ],
      },
      {
        title: 'Excel Gerçekten Ne Zaman Yeterli?',
        content: 'Dürüst olmak gerekirse, Excel ayda 1-2 fatura kesen ve e-Fatura ihtiyacı olmayan serbest çalışanlar için yeterli olabilir. Ancak bir işletme ayda 5+ faturaya ulaştığında, çalışanları olduğunda veya KDV ile uğraştığında, tablolar yardımdan çok engel haline gelir. Facturino\'ya geçiş sadece bir yükseltme değildir — doğruluk, verimlilik ve huzur için bir yatırımdır.',
        items: null,
        steps: null,
      },
      {
        title: 'Excel\'den Kolay Geçiş',
        content: 'Değişimin göz korkutucu olabileceğini anlıyoruz. Bu yüzden Facturino sorunsuz bir geçiş süreci sunuyor. Müşterilerinizi, ürünlerinizi ve faturalarınızı CSV/Excel dosyalarından doğrudan Facturino\'ya aktarabilirsiniz. Destek ekibimiz sürecin her adımında size yardımcı olur.',
        items: [
          'Mevcut müşterileri Excel/CSV\'den içe aktarma',
          'Ürün ve hizmet kataloğunuzu içe aktarma',
          'Geçmiş faturalar toplu olarak içe aktarılabilir',
          'Geçiş sırasında ücretsiz teknik destek',
          'Tam geçişten önce test için ücretsiz plan',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'zosto-facturino', title: "Makedon işletmelerin Facturino'yu seçmesinin 10 nedeni" },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
      { slug: 'recurring-invoices-mk', title: 'Tekrarlayan faturalar: Tahsilatı otomatikleştirin' },
    ],
    cta: {
      title: 'Excel\'den Facturino\'ya Geçin',
      desc: 'Ücretsiz başlayın ve binlerce işletmenin neden zaten geçiş yaptığını görün.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function FacturinoVsExcelPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
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
// CLAUDE-CHECKPOINT
