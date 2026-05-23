import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/najdobar-pos-softver-2026', {
    title: {
      mk: 'Najdobar POS softver za Makedonija 2026: Sporedba | Facturino',
      en: 'Best POS Software for North Macedonia 2026: Comparison | Facturino',
      sq: 'Softueri me i mire POS per Maqedonine 2026: Krahasim | Facturino',
      tr: '2026 Makedonya En Iyi POS Yazilimi: Karsilastirma | Facturino',
    },
    description: {
      mk: 'Sporedba na POS sistemi dostapni vo Makedonija: Facturino, Vector, hardverski POS. Fiskalen pecatac, cloud, inventar, ceni i zakonski baranja za fiskalizacija.',
      en: 'Comparison of POS systems available in North Macedonia: Facturino, Vector, hardware POS. Fiscal printer, cloud, inventory, pricing and fiscal law requirements.',
      sq: 'Krahasim i sistemeve POS ne Maqedoni: Facturino, Vector, POS harduerik. Printer fiskal, cloud, stok, cmime dhe kerkesat ligjore per fiskalizim.',
      tr: 'Makedonya\'da mevcut POS sistemleri karsilastirmasi: Facturino, Vector, donanim POS. Fiskal yazici, cloud, stok, fiyat ve fiskalizasyon yasal gereksinimleri.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Споредба',
    title: 'Најдобар POS софтвер за Македонија 2026: споредба',
    publishDate: '23 мај 2026',
    readTime: '8 мин читање',
    intro:
      'Секој малопродажен и угостителски бизнис во Македонија мора да користи POS систем со интеграција на фискален печатач. Од 2019, фискалните уреди се задолжителни за сите B2C продажби (Закон за фискализација). Овој водич ги споредува достапните опции.',
    sections: [
      {
        title: 'Што бара POS во Македонија: законски барања',
        content: 'Пред да изберете POS софтвер, треба да ги знаете законските барања за фискализација во Македонија:',
        items: [
          'Фискален уред е задолжителен (9 одобрени уреди во МК)',
          'Фискален бон за секоја B2C продажба',
          'Дневен Z-извештај задолжително',
          'Електронско чување на журналот',
          'Касата мора да се поврзе со одобрен фискален печатач',
        ],
        steps: null,
      },
      {
        title: 'Facturino POS — бесплатно, cloud',
        content: 'Facturino POS е cloud-базирано решение што работи во Chrome на било кој уред, без инсталација. Најдобар за: ресторани, кафулиња, мала малопродажба.',
        items: [
          'Бесплатен план со основен POS',
          'Управување со маси за ресторани',
          'Kitchen Display System (KDS)',
          '10% ДДВ за ресторански услуги (Чл. 30а ЗДДВ)',
          'Скенирање на баркодови',
          'Следење на залиха',
          'Интеграција со фискален печатач (9 уреди)',
          'Cloud — работи на таблет/телефон',
          'Извештаи за продажба во реално време',
          'Поддршка за повеќе локации',
        ],
        steps: null,
      },
      {
        title: 'Vector — етаблиран МК POS',
        content: 'Vector (од Duna) е најраспространетиот десктоп POS во Македонија. Најдобар за: малопродажба, супермаркети.',
        items: [
          'Десктоп апликација',
          'Силен фокус на малопродажба',
          'Управување со залиха',
          'Интеграција со фискален печатач',
          'Локална поддршка и обука',
          'Само Windows',
        ],
        steps: null,
      },
      {
        title: 'Хардверски POS уреди',
        content: 'Специјализирани уреди со вграден софтвер. Најдобар за: високо-обемна малопродажба, аптеки.',
        items: [
          'All-in-one хардвер + софтвер',
          'Вграден фискален печатач',
          'Touch screen',
          'Брзо и способно',
          'Скапо (€500–2,000+)',
          'Ограничена флексибилност',
        ],
        steps: null,
      },
      {
        title: 'Cloud vs Desktop POS',
        content: 'Кој пристап е подобар за вашиот бизнис? Еве споредба по клучни критериуми:',
        items: null,
        steps: [
          { step: 'Почетна цена', desc: 'Cloud: €0 (бесплатен план). Desktop: €200–500+ еднократно плус техничар.' },
          { step: 'Месечна цена', desc: 'Cloud: €0–39/месец според план. Desktop: €0 по лиценца, но трошоци за одржување.' },
          { step: 'Ажурирања', desc: 'Cloud: автоматски, без техничар. Desktop: рачно, треба техничар.' },
          { step: 'Повеќе локации', desc: 'Cloud: да, од еден профил. Desktop: одделна инсталација на секоја локација.' },
          { step: 'Далечински пристап', desc: 'Cloud: да, од секаде. Desktop: само на компјутерот каде е инсталиран.' },
          { step: 'Хардверски барања', desc: 'Cloud: Chrome на било кој уред. Desktop: Windows PC со COM порт.' },
          { step: 'Зависност од интернет', desc: 'Cloud: потребен интернет. Desktop: работи офлајн.' },
          { step: 'Бекап на податоци', desc: 'Cloud: автоматски бекап. Desktop: рачно, ризик од губење.' },
        ],
      },
      {
        title: 'POS чеклиста: што да барате',
        content: 'При избор на POS софтвер, проверете дали ги поддржува овие функции:',
        items: [
          'Поддршка за фискален печатач (задолжително)',
          'Следење на залиха',
          'Управување со вработени',
          'Дневни извештаи',
          'Скенирање на баркод/QR',
          'База на клиенти',
          'Начини на плаќање (готовина + картичка)',
          'Управување со маси (за ресторани)',
          'Kitchen display (за ресторани)',
          'Интеграција со е-Фактура',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'pos-softver-makedonija', title: 'POS софтвер за малопродажба во Македонија 2026' },
      { slug: 'vector-alternativa-pos', title: 'Премин од Vector на Facturino POS' },
      { slug: 'smetkovodstvo-za-restorani', title: 'Сметководство за ресторани' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Како да поврзете фискален печатач во Chrome без драјвери' },
      { slug: 'sto-e-e-faktura', title: 'Што е е-Фактура и зошто е задолжителна?' },
    ],
    cta: {
      title: 'Пробајте Facturino POS бесплатно',
      desc: 'Без кредитна картичка, без договор. Отворете Chrome и започнете да продавате.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Comparison',
    title: 'Best POS Software for North Macedonia 2026: Comparison',
    publishDate: '23 May 2026',
    readTime: '8 min read',
    intro:
      'Every retail and food-service business in North Macedonia needs a POS system with fiscal printer integration. Since 2019, fiscal devices are mandatory for all B2C sales (Law on Fiscalization). This guide compares the available options so you can make an informed choice.',
    sections: [
      {
        title: 'What POS Needs in Macedonia: Legal Requirements',
        content: 'Before choosing POS software, you must understand the legal requirements for fiscalization in Macedonia:',
        items: [
          'Fiscal device mandatory (9 approved devices in MK)',
          'Fiscal receipt required for every B2C sale',
          'Daily Z-report required',
          'Electronic journal storage',
          'Cash register must connect to an approved fiscal printer',
        ],
        steps: null,
      },
      {
        title: 'Facturino POS — Free Tier, Cloud-Based',
        content: 'Facturino POS is a cloud-based solution that runs in Chrome on any device, no installation needed. Best for: restaurants, cafes, small retail.',
        items: [
          'Free tier with basic POS',
          'Table management for restaurants',
          'Kitchen Display System (KDS)',
          '10% VAT for restaurant food service (Art. 30a ZDDV)',
          'Barcode scanning',
          'Inventory tracking',
          'Fiscal printer integration (9 devices)',
          'Cloud-based — works on tablet/phone',
          'Real-time sales reports',
          'Multi-location support',
        ],
        steps: null,
      },
      {
        title: 'Vector — Established MK POS',
        content: 'Vector (by Duna) is the most widespread desktop POS in Macedonia. Best for: retail stores, supermarkets.',
        items: [
          'Desktop application',
          'Strong retail focus',
          'Inventory management',
          'Fiscal printer integration',
          'Local support and training',
          'Windows-only',
        ],
        steps: null,
      },
      {
        title: 'Dedicated Hardware POS',
        content: 'Specialized devices with built-in software. Best for: high-volume retail, pharmacies.',
        items: [
          'All-in-one hardware + software',
          'Built-in fiscal printer',
          'Touch screen',
          'Fast and reliable',
          'Expensive (€500–2,000+)',
          'Limited flexibility',
        ],
        steps: null,
      },
      {
        title: 'Cloud vs Desktop POS',
        content: 'Which approach is better for your business? Here is a comparison by key criteria:',
        items: null,
        steps: [
          { step: 'Setup cost', desc: 'Cloud: €0 (free plan). Desktop: €200–500+ one-time plus technician.' },
          { step: 'Monthly cost', desc: 'Cloud: €0–39/month depending on plan. Desktop: €0 license, but maintenance costs.' },
          { step: 'Updates', desc: 'Cloud: automatic, no technician. Desktop: manual, technician required.' },
          { step: 'Multi-location', desc: 'Cloud: yes, from one account. Desktop: separate installation per location.' },
          { step: 'Remote access', desc: 'Cloud: yes, from anywhere. Desktop: only on the installed computer.' },
          { step: 'Hardware requirements', desc: 'Cloud: Chrome on any device. Desktop: Windows PC with COM port.' },
          { step: 'Internet dependency', desc: 'Cloud: internet required. Desktop: works offline.' },
          { step: 'Data backup', desc: 'Cloud: automatic backup. Desktop: manual, risk of data loss.' },
        ],
      },
      {
        title: 'POS Features Checklist: What to Look For',
        content: 'When choosing POS software, verify it supports these features:',
        items: [
          'Fiscal printer support (mandatory)',
          'Inventory tracking',
          'Employee management',
          'Daily reports',
          'Barcode/QR scanning',
          'Customer database',
          'Payment methods (cash + card)',
          'Table management (for restaurants)',
          'Kitchen display (for restaurants)',
          'e-Invoice integration',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'pos-softver-makedonija', title: 'POS Software for Retail in Macedonia 2026' },
      { slug: 'vector-alternativa-pos', title: 'Switching from Vector to Facturino POS' },
      { slug: 'smetkovodstvo-za-restorani', title: 'Accounting for Restaurants' },
      { slug: 'fiskalen-pecatac-chrome', title: 'How to Connect a Fiscal Printer in Chrome Without Drivers' },
      { slug: 'sto-e-e-faktura', title: 'What Is e-Invoice and Why Is It Mandatory?' },
    ],
    cta: {
      title: 'Try Facturino POS free',
      desc: 'No credit card, no contract. Open Chrome and start selling.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Krahasim',
    title: 'Softueri me i mire POS per Maqedonine 2026: Krahasim',
    publishDate: '23 maj 2026',
    readTime: '8 min lexim',
    intro:
      'Cdo biznes i shitjes me pakice dhe i gastronomise ne Maqedoni duhet te perdore sistem POS me integrim te printerit fiskal. Qe nga 2019, pajisjet fiskale jane te detyrueshme per te gjitha shitjet B2C (Ligji per fiskalizim). Ky udhezues krahason opsionet e disponueshme.',
    sections: [
      {
        title: 'Cfare kerkon POS ne Maqedoni: Kerkesat ligjore',
        content: 'Para se te zgjidhni softuer POS, duhet ti dini kerkesat ligjore per fiskalizim ne Maqedoni:',
        items: [
          'Pajisja fiskale e detyrueshme (9 pajisje te miratuara ne MK)',
          'Kupon fiskal per cdo shitje B2C',
          'Raporti ditor Z i detyrueshem',
          'Ruajtje elektronike e zhurnalit',
          'Arka duhet te lidhet me printer fiskal te miratuar',
        ],
        steps: null,
      },
      {
        title: 'Facturino POS — Falas, Cloud',
        content: 'Facturino POS eshte zgjidhje cloud qe punon ne Chrome ne cdo pajisje, pa instalim. Me i miri per: restorante, kafe, shitje te vogla me pakice.',
        items: [
          'Plan falas me POS bazik',
          'Menaxhim i tavolinave per restorante',
          'Kitchen Display System (KDS)',
          '10% TVSH per sherbim gastronomik (Neni 30a LTVSH)',
          'Skanim i barkodeve',
          'Ndjekje e stokut',
          'Integrim me printer fiskal (9 pajisje)',
          'Cloud — punon ne tablet/telefon',
          'Raporte te shitjeve ne kohe reale',
          'Mbeshtetje per shume lokacione',
        ],
        steps: null,
      },
      {
        title: 'Vector — POS i etabluar ne MK',
        content: 'Vector (nga Duna) eshte POS-i desktop me i perhapur ne Maqedoni. Me i miri per: dyqane me pakice, supermarkete.',
        items: [
          'Aplikacion desktop',
          'Fokus i forte ne shitje me pakice',
          'Menaxhim i stokut',
          'Integrim me printer fiskal',
          'Mbeshtetje lokale dhe trajnim',
          'Vetem Windows',
        ],
        steps: null,
      },
      {
        title: 'POS Harduerik i Dedikuar',
        content: 'Pajisje te specializuara me softuer te integruar. Me i miri per: shitje me volum te larte, farmaci.',
        items: [
          'All-in-one harduer + softuer',
          'Printer fiskal i integruar',
          'Ekran me prekje',
          'I shpejte dhe i besueshem',
          'I shtrenjte (€500–2,000+)',
          'Fleksibilitet i kufizuar',
        ],
        steps: null,
      },
      {
        title: 'Cloud vs Desktop POS',
        content: 'Cila metode eshte me e mire per biznesin tuaj? Ja krahasimi sipas kritereve kryesore:',
        items: null,
        steps: [
          { step: 'Kosto fillestare', desc: 'Cloud: €0 (plan falas). Desktop: €200–500+ plus teknik.' },
          { step: 'Kosto mujore', desc: 'Cloud: €0–39/muaj sipas planit. Desktop: €0 licence, por kosto mirembajtjeje.' },
          { step: 'Perditesimet', desc: 'Cloud: automatike, pa teknik. Desktop: manuale, nevojitet teknik.' },
          { step: 'Shume lokacione', desc: 'Cloud: po, nga nje llogari. Desktop: instalim i vecante per cdo lokacion.' },
          { step: 'Qasje ne distance', desc: 'Cloud: po, nga kudo. Desktop: vetem ne kompjuterin e instaluar.' },
          { step: 'Kerkesat harduerike', desc: 'Cloud: Chrome ne cdo pajisje. Desktop: PC Windows me COM port.' },
          { step: 'Varesia nga interneti', desc: 'Cloud: nevojitet internet. Desktop: punon offline.' },
          { step: 'Kopje rezerve', desc: 'Cloud: kopje automatike. Desktop: manuale, rrezik humbjeje.' },
        ],
      },
      {
        title: 'Lista e vecorive POS: Cfare te kerkoni',
        content: 'Kur zgjidhni softuer POS, verifikoni qe mbeshtet keto vecori:',
        items: [
          'Mbeshtetje per printer fiskal (e detyrueshme)',
          'Ndjekje e stokut',
          'Menaxhim i punonjesve',
          'Raporte ditore',
          'Skanim i barkod/QR',
          'Baze e klienteve',
          'Metoda pagese (cash + karte)',
          'Menaxhim i tavolinave (per restorante)',
          'Kitchen display (per restorante)',
          'Integrim me e-Fature',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te lidhur',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Softuer POS per shitje me pakice ne Maqedoni 2026' },
      { slug: 'vector-alternativa-pos', title: 'Kalimi nga Vector ne Facturino POS' },
      { slug: 'smetkovodstvo-za-restorani', title: 'Kontabiliteti per restorante' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Si te lidhni printer fiskal ne Chrome pa driver' },
      { slug: 'sto-e-e-faktura', title: 'Cfare eshte e-Fatura dhe pse eshte e detyrueshme?' },
    ],
    cta: {
      title: 'Provoni Facturino POS falas',
      desc: 'Pa kartele krediti, pa kontrate. Hapni Chrome dhe filloni te shisni.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Karsilastirma',
    title: '2026 Makedonya En Iyi POS Yazilimi: Karsilastirma',
    publishDate: '23 Mayis 2026',
    readTime: '8 dk okuma',
    intro:
      'Makedonya\'daki her perakende ve yiyecek-icecek isletmesi, fiskal yazici entegrasyonlu bir POS sistemine ihtiyac duyar. 2019\'dan bu yana fiskal cihazlar tum B2C satislari icin zorunludur (Fiskalizasyon Yasasi). Bu rehber mevcut secenekleri karsilastirir.',
    sections: [
      {
        title: 'Makedonya\'da POS Gereksinimleri: Yasal Zorunluluklar',
        content: 'POS yazilimi secmeden once, Makedonya\'daki fiskalizasyon yasal gereksinimlerini bilmelisiniz:',
        items: [
          'Fiskal cihaz zorunlu (MK\'da 9 onayli cihaz)',
          'Her B2C satisi icin fiskal fis gerekli',
          'Gunluk Z-raporu zorunlu',
          'Elektronik jurnal saklama',
          'Kasa onayli fiskal yaziciya baglanmali',
        ],
        steps: null,
      },
      {
        title: 'Facturino POS — Ucretsiz, Cloud',
        content: 'Facturino POS, herhangi bir cihazda Chrome\'da calisan cloud tabanli bir cozumdur. Kurulum gerekmez. En iyisi: restoranlar, kafeler, kucuk perakende.',
        items: [
          'Temel POS ile ucretsiz plan',
          'Restoranlar icin masa yonetimi',
          'Kitchen Display System (KDS)',
          'Restoran yemek hizmeti icin %10 KDV (Md. 30a ZDDV)',
          'Barkod tarama',
          'Stok takibi',
          'Fiskal yazici entegrasyonu (9 cihaz)',
          'Cloud — tablette/telefonda calisir',
          'Anlik satis raporlari',
          'Coklu lokasyon destegi',
        ],
        steps: null,
      },
      {
        title: 'Vector — Yerlesik MK POS',
        content: 'Vector (Duna) Makedonya\'daki en yaygin masaustu POS\'tur. En iyisi: perakende magazalar, supermarketler.',
        items: [
          'Masaustu uygulamasi',
          'Guclu perakende odagi',
          'Stok yonetimi',
          'Fiskal yazici entegrasyonu',
          'Yerel destek ve egitim',
          'Yalnizca Windows',
        ],
        steps: null,
      },
      {
        title: 'Ozel Donanim POS',
        content: 'Yerlesik yazilimli ozel cihazlar. En iyisi: yuksek hacimli perakende, eczaneler.',
        items: [
          'Hepsi-bir-arada donanim + yazilim',
          'Yerlesik fiskal yazici',
          'Dokunmatik ekran',
          'Hizli ve guvenilir',
          'Pahali (€500–2,000+)',
          'Sinirli esneklik',
        ],
        steps: null,
      },
      {
        title: 'Cloud vs Masaustu POS',
        content: 'Isletmeniz icin hangi yaklasim daha iyi? Temel kriterlere gore karsilastirma:',
        items: null,
        steps: [
          { step: 'Baslangic maliyeti', desc: 'Cloud: €0 (ucretsiz plan). Masaustu: €200–500+ tek seferlik + teknisyen.' },
          { step: 'Aylik maliyet', desc: 'Cloud: €0–39/ay plana gore. Masaustu: €0 lisans, ama bakim maliyetleri.' },
          { step: 'Guncellemeler', desc: 'Cloud: otomatik, teknisyensiz. Masaustu: manuel, teknisyen gerekir.' },
          { step: 'Coklu lokasyon', desc: 'Cloud: evet, tek hesaptan. Masaustu: her lokasyona ayri kurulum.' },
          { step: 'Uzaktan erisim', desc: 'Cloud: evet, her yerden. Masaustu: sadece kurulu bilgisayarda.' },
          { step: 'Donanim gereksinimleri', desc: 'Cloud: herhangi bir cihazda Chrome. Masaustu: COM portlu Windows PC.' },
          { step: 'Internet bagimliligi', desc: 'Cloud: internet gerekli. Masaustu: cevrimdisi calisir.' },
          { step: 'Veri yedekleme', desc: 'Cloud: otomatik yedekleme. Masaustu: manuel, veri kaybi riski.' },
        ],
      },
      {
        title: 'POS Ozellik Kontrol Listesi: Nelere Bakmali',
        content: 'POS yazilimi secerken, su ozellikleri destekledigini dogrulayin:',
        items: [
          'Fiskal yazici destegi (zorunlu)',
          'Stok takibi',
          'Calisan yonetimi',
          'Gunluk raporlar',
          'Barkod/QR tarama',
          'Musteri veritabani',
          'Odeme yontemleri (nakit + kart)',
          'Masa yonetimi (restoranlar icin)',
          'Mutfak ekrani (restoranlar icin)',
          'e-Fatura entegrasyonu',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili yazilar',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Makedonya\'da perakende POS yazilimi 2026' },
      { slug: 'vector-alternativa-pos', title: 'Vector\'den Facturino POS\'a gecis' },
      { slug: 'smetkovodstvo-za-restorani', title: 'Restoranlar icin muhasebe' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Chrome\'da surucusuz fiskal yazici nasil baglanir' },
      { slug: 'sto-e-e-faktura', title: 'e-Fatura nedir ve neden zorunlu?' },
    ],
    cta: {
      title: 'Facturino POS\'u ucretsiz deneyin',
      desc: 'Kredi karti yok, sozlesme yok. Chrome\'u acin ve satisa baslayin.',
      button: 'Ucretsiz basla',
    },
  },
} as const

export default async function NajdobarPosSoftver2026({
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
    slug: 'najdobar-pos-softver-2026',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['POS software', 'comparison', 'fiscal printer', 'North Macedonia'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/najdobar-pos-softver-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кој POS софтвер е најдобар за Македонија?', answer: 'За мали бизниси, ресторани и кафулиња, Facturino POS е најдобар избор — бесплатен план, cloud, работи на било кој уред преку Chrome, автоматско книжење и поддршка за 9 фискални уреди.' },
        { question: 'Дали ми треба фискален печатач за POS?', answer: 'Да, од 2019 фискалните уреди се задолжителни за сите B2C продажби во Македонија (Закон за фискализација). Facturino поддржува 9 одобрени фискални печатачи преку WebSerial без драјвери.' },
        { question: 'Дали постои бесплатен POS софтвер во Македонија?', answer: 'Да, Facturino POS нуди бесплатен план со основен POS. Работи во Chrome на било кој уред, без инсталација и без кредитна картичка.' },
      ])) }} />
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

      <section className="py-12 md:py-16 bg-gray-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">{t.relatedTitle}</h2>
          <div className="grid gap-4">
            {t.related.map((r) => (
              <Link key={r.slug} href={`/${locale}/blog/${r.slug}`} className="group flex items-center justify-between bg-white rounded-xl border border-gray-100 px-6 py-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                <span className="text-gray-900 font-medium group-hover:text-indigo-600 transition-colors">{r.title}</span>
                <svg className="w-5 h-5 text-gray-400 group-hover:text-indigo-600 flex-shrink-0 ml-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
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
