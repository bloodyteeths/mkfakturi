import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/digitalno-smetkovodstvo', {
    title: {
      mk: 'Дигитално vs традиционално сметководство — Facturino',
      en: 'Digital vs Traditional Accounting — Facturino',
      sq: 'Kontabiliteti dixhital kundrejt atij tradicional — Facturino',
      tr: 'Dijital ve geleneksel muhasebe karşılaştırması — Facturino',
    },
    description: {
      mk: 'Споредба на дигитално и традиционално сметководство — предности на автоматизацијата, точноста, пристапот од далечина и усогласеноста со прописите.',
      en: 'Comparison of digital and traditional accounting — advantages of automation, accuracy, remote access, and regulatory compliance.',
      sq: 'Krahasim i kontabilitetit dixhital dhe tradicional — përparësitë e automatizimit, saktësisë, qasjes nga distanca dhe pajtueshmërisë rregullative.',
      tr: 'Dijital ve geleneksel muhasebenin karşılaştırması — otomasyon, doğruluk, uzaktan erişim ve mevzuat uyumluluğunun avantajları.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Совети',
    title: 'Дигитално vs традиционално сметководство',
    publishDate: '15 февруари 2026',
    readTime: '6 мин читање',
    intro:
      'Сметководството минува низ дигитална трансформација. Додека многу бизниси во Македонија сеуште користат хартиени документи и Excel табели, дигиталното сметководство нуди значителни предности во точност, брзина и усогласеност. Во овој напис споредуваме двата пристапа и објаснуваме зошто преминот кон дигитално сметководство е неизбежен.',
    sections: [
      {
        title: 'Што е традиционално сметководство?',
        content:
          'Традиционалното сметководство се потпира на хартиени документи, рачно книжење и Excel табели. Фактурите се печатат, потпишуваат и складираат во фасикли. Книжењата се внесуваат рачно, извештаите се составуваат на крајот на месецот, а комуникацијата со сметководителот е преку физички средби или е-пошта со прикачени документи. Овој пристап функционираше со децении, но има сериозни ограничувања во денешниот дигитален свет.',
        items: null,
      },
      {
        title: 'Предности на дигиталното сметководство',
        content: null,
        items: [
          'Автоматизација на повторливите задачи — книжења, пресметки на ДДВ, генерирање фактури и потсетувања за плаќање се целосно автоматизирани. Ова заштедува десетици часови месечно.',
          'Точност и елиминација на грешки — рачното внесување е подложно на грешки. Дигиталните системи автоматски ги пресметуваат сумите, го проверуваат балансот и ве предупредуваат за неконзистентности.',
          'Пристап во реално време — наместо да чекате крај на месецот за извештаи, имате постојан увид во финансиската состојба. Профит, расходи, ДДВ обврски — сe е видливо во секој момент.',
          'Работа од далечина — пристапете до сметките од канцеларија, дома или на пат. Вашиот сметководител може да работи на истите податоци без физичко присуство.',
          'Усогласеност со прописите — дигиталните системи автоматски ги следат промените во даночните закони и ги применуваат новите стапки и обрасци.',
          'Ревизорска трага (audit trail) — секоја промена е запишана со датум, време и корисник. Ова е незаменливо при ревизија од УЈП или надворешен ревизор.',
        ],
      },
      {
        title: 'Споредба: Дигитално vs традиционално',
        content: null,
        items: [
          'Брзина на книжење: Дигитално — секунди (автоматски); Традиционално — минути (рачно)',
          'Генерирање извештаи: Дигитално — моментално, со еден клик; Традиционално — часови до денови',
          'Ризик од грешки: Дигитално — минимален (автоматски проверки); Традиционално — висок (човечки фактор)',
          'Пристап до податоци: Дигитално — од секаде, 24/7; Традиционално — само од канцеларија',
          'Складирање: Дигитално — облак, безбедно и неограничено; Традиционално — хартија, физички простор потребен',
          'Усогласеност: Дигитално — автоматски ажурирања; Традиционално — рачно следење на промените',
          'Трошок: Дигитално — месечна претплата; Традиционално — скриени трошоци (хартија, складирање, време)',
        ],
      },
      {
        title: 'Предизвици при преминот кон дигитално',
        content:
          'Преминот кон дигитално сметководство не е без предизвици. Потребна е почетна инвестиција во време за учење на новиот систем, миграција на постоечките податоци и промена на навиките. Некои вработени може да се спротивставуваат на промената, особено ако долго работеле со хартиени документи.',
        items: [
          'Крива на учење — потребни се 2-4 недели за прилагодување на новиот систем',
          'Миграција на податоци — историските документи треба да се дигитализираат',
          'Интернет зависност — потребна е стабилна интернет конекција',
          'Безбедносни грижи — податоците мора да бидат заштитени и шифрирани',
          'Промена на навики — вработените треба да ги прифатат новите процеси',
        ],
      },
      {
        title: 'Како Facturino ја олеснува дигиталната трансформација',
        content:
          'Facturino е дизајниран специфично за македонски бизниси и го олеснува преминот кон дигитално сметководство. Интерфејсот е на македонски јазик, поддржува македонски контен план, генерира фактури со ДДВ согласно македонските прописи и се интегрира со банкарскиот систем преку PSD2. Нема потреба од технички познавања — системот е интуитивен и може да се започне за помалку од 10 минути.',
        items: [
          'Интерфејс на македонски јазик со македонски контен план',
          'Автоматско генерирање на е-фактури согласно УЈП барањата',
          'OCR скенирање — фотографирајте хартиена фактура и системот ја дигитализира',
          'PSD2 банкарска интеграција за автоматско книжење',
          'Облак складирање — сите документи безбедно зачувани и достапни од секаде',
          'Бесплатна миграција — нашиот тим ви помага при преносот на постоечките податоци',
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Зошто табели не се доволни' },
      { slug: 'za-smetkovoditeli', title: 'Зошто сметководителите преминуваат на Facturino' },
      { slug: 'zosto-facturino', title: '10 причини зошто македонски бизниси го избираат Facturino' },
    ],
    cta: {
      title: 'Преминете на дигитално сметководство денес',
      desc: 'Facturino го прави преминот лесен. Македонски интерфејс, автоматско книжење и облак пристап — спремен за вашиот бизнис.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Tips',
    title: 'Digital vs Traditional Accounting',
    publishDate: 'February 15, 2026',
    readTime: '6 min read',
    intro:
      'Accounting is undergoing a digital transformation. While many businesses in Macedonia still use paper documents and Excel spreadsheets, digital accounting offers significant advantages in accuracy, speed, and compliance. In this article, we compare the two approaches and explain why the shift to digital accounting is inevitable.',
    sections: [
      {
        title: 'What is traditional accounting?',
        content:
          'Traditional accounting relies on paper documents, manual bookkeeping, and Excel spreadsheets. Invoices are printed, signed, and stored in folders. Entries are made manually, reports are compiled at the end of the month, and communication with the accountant happens through physical meetings or email attachments. This approach worked for decades, but it has serious limitations in today\'s digital world.',
        items: null,
      },
      {
        title: 'Advantages of digital accounting',
        content: null,
        items: [
          'Automation of repetitive tasks — bookkeeping entries, VAT calculations, invoice generation, and payment reminders are fully automated. This saves dozens of hours per month.',
          'Accuracy and error elimination — manual entry is prone to mistakes. Digital systems automatically calculate amounts, verify balance, and alert you to inconsistencies.',
          'Real-time access — instead of waiting until month-end for reports, you have constant visibility into your financial position. Profit, expenses, VAT obligations — everything is visible at any moment.',
          'Remote work — access your accounts from the office, home, or on the road. Your accountant can work on the same data without physical presence.',
          'Regulatory compliance — digital systems automatically track changes in tax laws and apply new rates and forms.',
          'Audit trail — every change is recorded with date, time, and user. This is invaluable during a UJP audit or external review.',
        ],
      },
      {
        title: 'Comparison: Digital vs traditional',
        content: null,
        items: [
          'Booking speed: Digital — seconds (automatic); Traditional — minutes (manual)',
          'Report generation: Digital — instant, one click; Traditional — hours to days',
          'Error risk: Digital — minimal (automatic checks); Traditional — high (human factor)',
          'Data access: Digital — anywhere, 24/7; Traditional — office only',
          'Storage: Digital — cloud, secure and unlimited; Traditional — paper, physical space required',
          'Compliance: Digital — automatic updates; Traditional — manual tracking of changes',
          'Cost: Digital — monthly subscription; Traditional — hidden costs (paper, storage, time)',
        ],
      },
      {
        title: 'Challenges of switching to digital',
        content:
          'The transition to digital accounting is not without challenges. It requires an initial time investment to learn the new system, migrate existing data, and change habits. Some employees may resist change, especially if they have worked with paper documents for a long time.',
        items: [
          'Learning curve — 2-4 weeks needed to adapt to the new system',
          'Data migration — historical documents need to be digitized',
          'Internet dependency — a stable internet connection is required',
          'Security concerns — data must be protected and encrypted',
          'Habit change — employees need to accept new processes',
        ],
      },
      {
        title: 'How Facturino eases the digital transformation',
        content:
          'Facturino is designed specifically for Macedonian businesses and makes the transition to digital accounting smooth. The interface is in Macedonian, supports the Macedonian chart of accounts, generates VAT-compliant invoices per Macedonian regulations, and integrates with the banking system via PSD2. No technical knowledge is needed — the system is intuitive and can be started in under 10 minutes.',
        items: [
          'Macedonian interface with Macedonian chart of accounts',
          'Automatic e-invoice generation per UJP requirements',
          'OCR scanning — photograph a paper invoice and the system digitizes it',
          'PSD2 bank integration for automatic bookkeeping',
          'Cloud storage — all documents securely saved and accessible from anywhere',
          'Free migration — our team helps you transfer existing data',
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Why Spreadsheets Are Not Enough' },
      { slug: 'za-smetkovoditeli', title: 'Why Accountants Are Switching to Facturino' },
      { slug: 'zosto-facturino', title: '10 Reasons Macedonian Businesses Choose Facturino' },
    ],
    cta: {
      title: 'Switch to digital accounting today',
      desc: 'Facturino makes the transition easy. Macedonian interface, automatic bookkeeping, and cloud access — ready for your business.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Këshilla',
    title: 'Kontabiliteti dixhital kundrejt atij tradicional',
    publishDate: '15 shkurt 2026',
    readTime: '6 min lexim',
    intro:
      'Kontabiliteti po kalon përmes transformimit dixhital. Ndërsa shumë biznese në Maqedoni ende përdorin dokumente letre dhe tabela Excel, kontabiliteti dixhital ofron përparësi të konsiderueshme në saktësi, shpejtësi dhe pajtueshmëri. Në këtë artikull krahasojmë dy qasjet dhe shpjegojmë pse kalimi në kontabilitet dixhital është i pashmangshëm.',
    sections: [
      {
        title: 'Çfarë është kontabiliteti tradicional?',
        content:
          'Kontabiliteti tradicional mbështetet në dokumente letre, kontabilitet manual dhe tabela Excel. Faturat printohen, nënshkruhen dhe ruhen në dosje. Regjistrimet bëhen manualisht, raportet përpilohen në fund të muajit dhe komunikimi me kontabilistin bëhet përmes takimeve fizike ose bashkëngjitjeve me email. Kjo qasje funksionoi për dekada, por ka kufizime serioze në botën dixhitale të sotme.',
        items: null,
      },
      {
        title: 'Përparësitë e kontabilitetit dixhital',
        content: null,
        items: [
          'Automatizimi i detyrave përsëritëse — regjistrimet kontabël, llogaritjet e TVSH-së, gjenerimi i faturave dhe kujtesat e pagesës janë plotësisht të automatizuara. Kjo kursen dhjetëra orë në muaj.',
          'Saktësi dhe eliminim gabimesh — regjistrimi manual është i prirur ndaj gabimeve. Sistemet dixhitale automatikisht llogarisin shumat, verifikojnë bilancin dhe ju njoftojnë për mospërputhje.',
          'Qasje në kohë reale — në vend se të prisni fundin e muajit për raporte, keni pamje të vazhdueshme në gjendjen financiare. Fitimi, shpenzimet, detyrimet e TVSH-së — gjithçka është e dukshme në çdo moment.',
          'Punë në distancë — qasuni llogarive nga zyra, shtëpia ose rruga. Kontabilisti juaj mund të punojë në të njëjtat të dhëna pa prezencë fizike.',
          'Pajtueshmëri rregullative — sistemet dixhitale automatikisht gjurmojnë ndryshimet në ligjet tatimore dhe aplikojnë normativa e forma të reja.',
          'Gjurmë auditimi (audit trail) — çdo ndryshim regjistrohet me datë, kohë dhe përdorues. Kjo është e paçmueshme gjatë kontrollit tatimor ose rishikimit të jashtëm.',
        ],
      },
      {
        title: 'Krahasim: Dixhital vs tradicional',
        content: null,
        items: [
          'Shpejtësia e regjistrimit: Dixhital — sekonda (automatik); Tradicional — minuta (manual)',
          'Gjenerimi i raporteve: Dixhital — i menjëhershëm, me një klikim; Tradicional — orë deri ditë',
          'Risku i gabimeve: Dixhital — minimal (kontrolle automatike); Tradicional — i lartë (faktori njerzor)',
          'Qasja në të dhëna: Dixhital — nga kudo, 24/7; Tradicional — vetëm nga zyra',
          'Ruajtja: Dixhital — cloud, e sigurt dhe e pakufizuar; Tradicional — letër, kërkohet hapësirë fizike',
          'Pajtueshmëria: Dixhital — përditësime automatike; Tradicional — gjurmim manual i ndryshimeve',
          'Kosto: Dixhital — abonim mujor; Tradicional — kosto të fshehura (letër, ruajtje, kohë)',
        ],
      },
      {
        title: 'Sfidat e kalimit në dixhital',
        content:
          'Kalimi në kontabilitet dixhital nuk është pa sfida. Kërkon investim fillestar kohe për të mësuar sistemin e ri, migruar të dhënat ekzistuese dhe ndryshuar zakonet. Disa punonjës mund t\'i kundërshtojnë ndryshimet, veçanërisht nëse kanë punuar me dokumente letre për kohë të gjatë.',
        items: [
          'Kurbë mësimi — nevojiten 2-4 javë për t\'u adaptuar me sistemin e ri',
          'Migrim të dhënash — dokumentet historike duhet të dixhitalizohen',
          'Varësi nga interneti — kërkohet lidhje interneti e qëndrueshme',
          'Shqetësime sigurie — të dhënat duhet të mbrohen dhe enkriptohen',
          'Ndryshim zakonesh — punonjësit duhet të pranojnë proceset e reja',
        ],
      },
      {
        title: 'Si e lehtëson Facturino transformimin dixhital',
        content:
          'Facturino është projektuar specifikisht për bizneset maqedonase dhe e bën kalimin në kontabilitet dixhital të lehtë. Ndërfaqja është në gjuhën maqedonase, mbështet planin kontabël maqedonas, gjeneron fatura me TVSH sipas rregulloreve maqedonase dhe integrohet me sistemin bankar përmes PSD2. Nuk nevojitet njohuri teknike — sistemi është intuitiv dhe mund të fillohet në më pak se 10 minuta.',
        items: [
          'Ndërfaqe maqedonase me plan kontabël maqedonas',
          'Gjenerim automatik i e-faturave sipas kërkesave të UJP',
          'Skanim OCR — fotografoni faturën letër dhe sistemi e dixhitalizon',
          'Integrim bankar PSD2 për kontabilitet automatik',
          'Ruajtje cloud — të gjitha dokumentet të ruajtura me siguri dhe të qasshme nga kudo',
          'Migrim falas — ekipi ynë ju ndihmon me transferimin e të dhënave ekzistuese',
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë' },
      { slug: 'za-smetkovoditeli', title: 'Pse kontabilistët po kalojnë në Facturino' },
      { slug: 'zosto-facturino', title: '10 arsye pse bizneset maqedonase zgjedhin Facturino' },
    ],
    cta: {
      title: 'Kaloni në kontabilitet dixhital sot',
      desc: 'Facturino e bën kalimin të lehtë. Ndërfaqe maqedonase, kontabilitet automatik dhe qasje cloud — gati për biznesin tuaj.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'İpuçları',
    title: 'Dijital ve geleneksel muhasebe karşılaştırması',
    publishDate: '15 Şubat 2026',
    readTime: '6 dk okuma',
    intro:
      'Muhasebe dijital bir dönüşüm geçiriyor. Makedonya\'daki birçok işletme hâlâ kâğıt belgeler ve Excel tabloları kullansa da, dijital muhasebe doğruluk, hız ve uyumluluk açısından önemli avantajlar sunuyor. Bu makalede iki yaklaşımı karşılaştırıyor ve dijital muhasebeye geçişin neden kaçınılmaz olduğunu açıklıyoruz.',
    sections: [
      {
        title: 'Geleneksel muhasebe nedir?',
        content:
          'Geleneksel muhasebe kâğıt belgelere, manuel defter tutmaya ve Excel tablolarına dayanır. Faturalar basılır, imzalanır ve dosyalarda saklanır. Kayıtlar elle girilir, raporlar ay sonunda hazırlanır ve muhasebeciyle iletişim fiziksel toplantılar veya e-posta ekleri aracılığıyla gerçekleşir. Bu yaklaşım onlarca yıl işe yaradı, ancak günümüzün dijital dünyasında ciddi sınırlamaları var.',
        items: null,
      },
      {
        title: 'Dijital muhasebenin avantajları',
        content: null,
        items: [
          'Tekrarlayan görevlerin otomasyonu — muhasebe kayıtları, KDV hesaplamaları, fatura oluşturma ve ödeme hatırlatmaları tamamen otomatiktir. Bu ayda onlarca saat tasarruf sağlar.',
          'Doğruluk ve hata eliminasyonu — manuel giriş hatalara açıktır. Dijital sistemler tutarları otomatik hesaplar, dengeyi doğrular ve tutarsızlıklar konusunda sizi uyarır.',
          'Gerçek zamanlı erişim — raporlar için ay sonunu beklemek yerine, mali durumunuza sürekli görünürlüğünüz olur. Kâr, giderler, KDV yükümlülükleri — her şey her an görünürdür.',
          'Uzaktan çalışma — hesaplarınıza ofisten, evden veya yoldan erişin. Muhasebeciniz fiziksel olarak bulunmadan aynı veriler üzerinde çalışabilir.',
          'Mevzuat uyumluluğu — dijital sistemler vergi yasalarındaki değişiklikleri otomatik olarak takip eder ve yeni oranları ve formları uygular.',
          'Denetim izi (audit trail) — her değişiklik tarih, saat ve kullanıcıyla kaydedilir. Bu UJP denetiminde veya dış incelemede paha biçilmezdir.',
        ],
      },
      {
        title: 'Karşılaştırma: Dijital ve geleneksel',
        content: null,
        items: [
          'Kayıt hızı: Dijital — saniyeler (otomatik); Geleneksel — dakikalar (manuel)',
          'Rapor oluşturma: Dijital — anlık, tek tıkla; Geleneksel — saatlerden günlere',
          'Hata riski: Dijital — minimum (otomatik kontroller); Geleneksel — yüksek (insan faktörü)',
          'Veri erişimi: Dijital — her yerden, 7/24; Geleneksel — sadece ofisten',
          'Depolama: Dijital — bulut, güvenli ve sınırsız; Geleneksel — kâğıt, fiziksel alan gerekli',
          'Uyumluluk: Dijital — otomatik güncellemeler; Geleneksel — değişikliklerin manuel takibi',
          'Maliyet: Dijital — aylık abonelik; Geleneksel — gizli maliyetler (kâğıt, depolama, zaman)',
        ],
      },
      {
        title: 'Dijitale geçişin zorlukları',
        content:
          'Dijital muhasebeye geçiş zorluklardan yoksun değildir. Yeni sistemi öğrenmek, mevcut verileri taşımak ve alışkanlıkları değiştirmek için başlangıç zaman yatırımı gerektirir. Bazı çalışanlar, özellikle uzun süre kâğıt belgelerle çalışmışlarsa değişime direnebilir.',
        items: [
          'Öğrenme eğrisi — yeni sisteme uyum sağlamak için 2-4 hafta gerekli',
          'Veri göçü — geçmiş belgeler dijitalleştirilmeli',
          'İnternet bağımlılığı — kararlı internet bağlantısı gerekli',
          'Güvenlik endişeleri — veriler korunmalı ve şifrelenmeli',
          'Alışkanlık değişikliği — çalışanların yeni süreçleri kabul etmesi gerekli',
        ],
      },
      {
        title: 'Facturino dijital dönüşümü nasıl kolaylaştırır',
        content:
          'Facturino özellikle Makedon işletmeleri için tasarlanmıştır ve dijital muhasebeye geçişi kolaylaştırır. Arayüz Makedoncadır, Makedon hesap planını destekler, Makedon düzenlemelerine uygun KDV\'li faturalar oluşturur ve PSD2 aracılığıyla bankacılık sistemiyle entegre olur. Teknik bilgi gerekmez — sistem sezgiseldir ve 10 dakikadan kısa sürede başlatılabilir.',
        items: [
          'Makedon hesap planıyla Makedonca arayüz',
          'UJP gereksinimlerine göre otomatik e-fatura oluşturma',
          'OCR tarama — kâğıt faturayı fotoğraflayın, sistem dijitalleştirir',
          'Otomatik muhasebe için PSD2 banka entegrasyonu',
          'Bulut depolama — tüm belgeler güvenle saklanır ve her yerden erişilebilir',
          'Ücretsiz göç — ekibimiz mevcut verileri aktarmanıza yardımcı olur',
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Neden tablolar yetmez' },
      { slug: 'za-smetkovoditeli', title: 'Muhasebeciler neden Facturino\'ya geçiyor' },
      { slug: 'zosto-facturino', title: "Makedon işletmelerin Facturino'yu seçmesinin 10 nedeni" },
    ],
    cta: {
      title: 'Bugün dijital muhasebeye geçin',
      desc: 'Facturino geçişi kolaylaştırır. Makedonca arayüz, otomatik muhasebe ve bulut erişimi — işletmeniz için hazır.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function DigitalnoSmetkovodstvoPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ============================================================ */}
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
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

      {/* ============================================================ */}
      {/*  ARTICLE BODY                                                */}
      {/* ============================================================ */}
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

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
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
