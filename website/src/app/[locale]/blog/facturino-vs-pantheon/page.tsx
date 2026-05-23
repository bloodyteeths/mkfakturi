import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/facturino-vs-pantheon', {
    title: {
      mk: 'Facturino vs PANTHEON: Што е подобро за мали фирми? — Facturino',
      en: 'Facturino vs PANTHEON: Which Is Better for Small Businesses? — Facturino',
      sq: 'Facturino vs PANTHEON: Cili është më i mirë për bizneset e vogla? — Facturino',
      tr: 'Facturino vs PANTHEON: Küçük İşletmeler İçin Hangisi Daha İyi? — Facturino',
    },
    description: {
      mk: 'Чесна споредба на Facturino и PANTHEON. Дознајте кој софтвер е подобар избор за вашиот мал бизнис — цена, функции, облак, е-Фактура.',
      en: 'Honest comparison of Facturino and PANTHEON. Find out which software is the better choice for your small business — pricing, features, cloud, e-Invoice.',
      sq: 'Krahasim i sinqertë i Facturino dhe PANTHEON. Zbuloni cili softuer është zgjedhja më e mirë për biznesin tuaj të vogël — çmimi, veçoritë, cloud, e-Fatura.',
      tr: 'Facturino ve PANTHEON\'un dürüst karşılaştırması. Küçük işletmeniz için hangi yazılımın daha iyi olduğunu öğrenin — fiyat, özellikler, bulut, e-Fatura.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Споредба',
    title: 'Facturino vs PANTHEON: Што е подобро за мали фирми?',
    publishDate: '23 мај 2026',
    readTime: '8 мин читање',
    intro: 'PANTHEON од Datalab е најкористениот ERP систем во Македонија, доминантен во производство и трговија на големо. Facturino е понов, облак-базиран софтвер наменет за мали и средни претпријатија. Оваа споредба е чесна — PANTHEON победува на одредени точки, Facturino на други. Вистинскиот избор зависи од големината и потребите на вашиот бизнис.',
    sections: [
      {
        title: 'Каде PANTHEON победува',
        content: 'PANTHEON е зрел ERP систем со повеќе од две децении присуство на македонскиот пазар. За поголеми компании со комплексни потреби, тој нуди длабоки можности кои тешко се наоѓаат на друго место.',
        items: [
          '20+ години на МК пазар — докажан и доверлив систем',
          'Полн ERP — производство, планирање, магацин, човечки ресурси',
          'Длабоко управување со залихи — лотови, сериски броеви, рокови',
          'Комплексни извештаи и бизнис интелигенција',
          'Десктоп перформанси за тешки податоци и големи бази',
          'Голема партнерска мрежа за локална поддршка и имплементација',
          'Управување со повеќе компании за холдинг структури',
        ],
        steps: null,
      },
      {
        title: 'Каде PANTHEON се бори',
        content: 'Покрај силните страни, PANTHEON има и значајни предизвици, особено за мали и средни бизниси кои бараат едноставност и флексибилност.',
        items: [
          'Висока цена — лиценца + поддршка + обука може да надмине €500+/годишно',
          'Стрмна крива на учење — потребни се недели обука за основно користење',
          'Десктоп/клиент-сервер архитектура — ограничен далечински пристап',
          'Потребна ИТ инфраструктура — сервер, бекап, мрежа',
          'Интерфејсот изгледа застарено споредено со модерен SaaS софтвер',
          'е-Фактура бара дополнителна конфигурација и поставки',
          'Нема бесплатен план — минимална инвестиција €500+/годишно',
          'Ажурирањата бараат интервенција од ИТ тим',
        ],
        steps: null,
      },
      {
        title: 'Каде Facturino победува',
        content: 'Facturino е изграден од нула за облак, со фокус на едноставност, МК усогласеност и достапност за мали бизниси.',
        items: [
          'Облак-базиран — работи од било кој уред и прелистувач, без инсталација',
          'Бесплатен план — до 5 фактури месечно без трошок',
          'Модерен интерфејс — интуитивен, минимална обука потребна',
          'е-Фактура UBL 2.1 вградена од првиот ден',
          'Банковен увоз со AI порамнување — автоматско усогласување на трансакции',
          'ПОС со фискален печатач — поддршка за 9 фискални уреди',
          'Плати со генерирање на МПИН образец',
          'AI скенер за документи — OCR на фактури',
          'Автоматски ажурирања — без потреба од ИТ тим',
          'Започнува од €12/месечно (Стартер план)',
        ],
        steps: null,
      },
      {
        title: 'Каде Facturino се бори — да бидеме чесни',
        content: 'Facturino е понов производ и има ограничувања кои треба да ги знаете пред да донесете одлука.',
        items: [
          'Понов производ — помалку години на пазарот и помала историја',
          'Нема планирање на производство (BOM постои, но не MRP)',
          'Ограничен за комплексни мулти-магацински поставки',
          'Потребна стабилна интернет конекција за работа',
          'Помала партнерска/дилерска мрежа',
          'Помалку погоден за компании со 50+ вработени',
        ],
        steps: null,
      },
      {
        title: 'Споредба точка по точка',
        content: null,
        items: null,
        steps: [
          { step: 'Цена', desc: 'PANTHEON: €500+/годишно за лиценца + поддршка. Facturino: бесплатен план, платените започнуваат од €12/месечно до €149/месечно.' },
          { step: 'Облак пристап', desc: 'PANTHEON: примарно десктоп, облак верзијата бара дополнително. Facturino: 100% облак-базиран, пристап од било каде.' },
          { step: 'е-Фактура', desc: 'PANTHEON: достапна со дополнителна конфигурација. Facturino: UBL 2.1 вградена од самиот почеток, без дополнителни чекори.' },
          { step: 'ПОС', desc: 'PANTHEON: поддржува различни ПОС решенија. Facturino: вграден ПОС со поддршка за 9 фискални печатачи.' },
          { step: 'Плати / МПИН', desc: 'PANTHEON: комплетен HR модул со сите обрасци. Facturino: пресметка на плати со автоматско генерирање на МПИН.' },
          { step: 'Производство', desc: 'PANTHEON: полн MRP со планирање и нормативи. Facturino: основен BOM, но нема MRP планирање — PANTHEON јасно победува тука.' },
          { step: 'Банковна интеграција', desc: 'PANTHEON: увоз на изводи. Facturino: увоз + AI порамнување кое автоматски ги усогласува трансакциите.' },
          { step: 'AI функции', desc: 'PANTHEON: нема вградени AI можности. Facturino: AI скенер, AI порамнување, AI категоризација.' },
          { step: 'Крива на учење', desc: 'PANTHEON: недели обука потребни за основно користење. Facturino: повеќето корисници продуктивни за 30 минути.' },
          { step: 'МК усогласеност', desc: 'PANTHEON: целосно усогласен со МК прописи. Facturino: исто — ДДВ, е-Фактура, МПИН, фискални уреди.' },
        ],
      },
      {
        title: 'Кој треба да го избере кој софтвер?',
        content: 'Нема универзален одговор — правилниот избор зависи од типот и големината на вашиот бизнис.',
        items: [
          'Изберете PANTHEON ако: имате производствена компанија, 50+ вработени, потреба од напреден магацин со лотови, имате ИТ тим, и буџет за €500+/годишно',
          'Изберете Facturino ако: сте мало или средно претпријатие под 50 вработени, сакате облак пристап од било каде, ви треба е-Фактура без комплицирана поставка, сакате да започнете бесплатно, немате ИТ тим, и претпочитате модерен и едноставен интерфејс',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'najdobar-smetkovodstven-softver-2026', title: 'Најдобар сметководствен софтвер 2026' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Зошто табели не се доволни' },
      { slug: 'sto-e-e-faktura', title: 'Што е е-Фактура и како функционира?' },
    ],
    cta: {
      title: 'Пробајте го Facturino бесплатно',
      desc: '14-дневен пробен период на Standard план — без кредитна картичка, без обврска.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Comparison',
    title: 'Facturino vs PANTHEON: Which Is Better for Small Businesses?',
    publishDate: 'May 23, 2026',
    readTime: '8 min read',
    intro: 'PANTHEON by Datalab is the most widely used ERP system in Macedonia, dominant in manufacturing and wholesale. Facturino is a newer, cloud-native solution designed for small and medium enterprises. This comparison is honest — PANTHEON wins on some points, Facturino on others. The right choice depends on your business size and needs.',
    sections: [
      {
        title: 'Where PANTHEON Wins',
        content: 'PANTHEON is a mature ERP system with more than two decades of presence in the Macedonian market. For larger companies with complex needs, it offers deep capabilities that are hard to find elsewhere.',
        items: [
          '20+ years in the MK market — proven and trusted system',
          'Full ERP — manufacturing, production planning, warehouse, HR',
          'Deep inventory management — lot tracking, serial numbers, expiry dates',
          'Complex reporting and business intelligence',
          'Desktop performance for heavy data and large databases',
          'Large partner network for local support and implementation',
          'Multi-company management for holding structures',
        ],
        steps: null,
      },
      {
        title: 'Where PANTHEON Struggles',
        content: 'Despite its strengths, PANTHEON has significant challenges, especially for small and medium businesses that need simplicity and flexibility.',
        items: [
          'High cost — license + support + training can exceed €500+/year',
          'Steep learning curve — weeks of training needed for basic use',
          'Desktop/client-server architecture — limited remote access',
          'Requires IT infrastructure — server, backup, network',
          'UI feels dated compared to modern SaaS software',
          'e-Invoice requires additional configuration and setup',
          'No free tier — minimum investment €500+/year',
          'Updates require IT team intervention',
        ],
        steps: null,
      },
      {
        title: 'Where Facturino Wins',
        content: 'Facturino is built from the ground up for the cloud, with a focus on simplicity, MK compliance, and accessibility for small businesses.',
        items: [
          'Cloud-native — works from any device and browser, no installation needed',
          'Free tier available — up to 5 invoices per month at no cost',
          'Modern UI — intuitive, minimal training needed',
          'e-Invoice UBL 2.1 built-in from day one',
          'Bank feed import with AI reconciliation — automatic transaction matching',
          'POS with fiscal printer support — 9 fiscal devices supported',
          'Payroll with MPIN form generation',
          'AI document scanner — invoice OCR',
          'Automatic updates — no IT team needed',
          'Starts at €12/month (Starter plan)',
        ],
        steps: null,
      },
      {
        title: 'Where Facturino Struggles — Being Honest',
        content: 'Facturino is a newer product and has limitations you should know about before making a decision.',
        items: [
          'Newer product — fewer years of track record',
          'No manufacturing planning (BOM exists but no MRP)',
          'Limited for complex multi-warehouse setups',
          'Requires stable internet connection',
          'Smaller partner/reseller network',
          'Less suited for 50+ employee companies',
        ],
        steps: null,
      },
      {
        title: 'Head-to-Head Comparison',
        content: null,
        items: null,
        steps: [
          { step: 'Pricing', desc: 'PANTHEON: €500+/year for license + support. Facturino: free tier, paid plans start from €12/month up to €149/month.' },
          { step: 'Cloud Access', desc: 'PANTHEON: primarily desktop, cloud version requires additional setup. Facturino: 100% cloud-based, access from anywhere.' },
          { step: 'e-Invoice', desc: 'PANTHEON: available with additional configuration. Facturino: UBL 2.1 built-in from the start, no extra steps.' },
          { step: 'POS', desc: 'PANTHEON: supports various POS solutions. Facturino: built-in POS with support for 9 fiscal printers.' },
          { step: 'Payroll / MPIN', desc: 'PANTHEON: complete HR module with all forms. Facturino: payroll calculation with automatic MPIN generation.' },
          { step: 'Manufacturing', desc: 'PANTHEON: full MRP with planning and BOMs. Facturino: basic BOM, but no MRP planning — PANTHEON clearly wins here.' },
          { step: 'Bank Integration', desc: 'PANTHEON: statement import. Facturino: import + AI reconciliation that automatically matches transactions.' },
          { step: 'AI Features', desc: 'PANTHEON: no built-in AI capabilities. Facturino: AI scanner, AI reconciliation, AI categorization.' },
          { step: 'Learning Curve', desc: 'PANTHEON: weeks of training needed for basic use. Facturino: most users productive within 30 minutes.' },
          { step: 'MK Compliance', desc: 'PANTHEON: fully compliant with MK regulations. Facturino: same — VAT, e-Invoice, MPIN, fiscal devices.' },
        ],
      },
      {
        title: 'Who Should Choose What?',
        content: 'There is no universal answer — the right choice depends on your business type and size.',
        items: [
          'Choose PANTHEON if: you have a manufacturing company, 50+ employees, need advanced warehouse with lot tracking, have an IT team, and budget for €500+/year',
          'Choose Facturino if: you are an SME under 50 employees, want cloud access from anywhere, need e-Invoice without complex setup, want to start free, have no IT team, and prefer a modern and simple interface',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'najdobar-smetkovodstven-softver-2026', title: 'Best Accounting Software 2026' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Why Spreadsheets Aren\'t Enough' },
      { slug: 'sto-e-e-faktura', title: 'What Is e-Invoice and How Does It Work?' },
    ],
    cta: {
      title: 'Try Facturino for Free',
      desc: '14-day trial of the Standard plan — no credit card, no obligation.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Krahasim',
    title: 'Facturino vs PANTHEON: Cili është më i mirë për bizneset e vogla?',
    publishDate: '23 maj 2026',
    readTime: '8 min lexim',
    intro: 'PANTHEON nga Datalab është sistemi ERP më i përdorur në Maqedoni, dominues në prodhim dhe tregtinë me shumicë. Facturino është zgjidhje më e re, e bazuar në cloud, e krijuar për ndërmarrjet e vogla dhe të mesme. Ky krahasim është i sinqertë — PANTHEON fiton në disa pika, Facturino në të tjera. Zgjedhja e duhur varet nga madhësia dhe nevojat e biznesit tuaj.',
    sections: [
      {
        title: 'Ku fiton PANTHEON',
        content: 'PANTHEON është sistem ERP i pjekur me më shumë se dy dekada prezencë në tregun maqedonas. Për kompani më të mëdha me nevoja komplekse, ai ofron aftësi të thella që vështirë gjenden gjetkë.',
        items: [
          '20+ vjet në tregun MK — sistem i provuar dhe i besueshëm',
          'ERP i plotë — prodhim, planifikim, magazinë, burime njerëzore',
          'Menaxhim i thellë i inventarit — gjurmim lotesh, numra serialë, data skadence',
          'Raportim kompleks dhe inteligjencë biznesi',
          'Performancë desktop për të dhëna të rënda dhe baza të mëdha',
          'Rrjet i madh partnerësh për mbështetje lokale dhe implementim',
          'Menaxhim i shumë kompanive për struktura holding',
        ],
        steps: null,
      },
      {
        title: 'Ku vuan PANTHEON',
        content: 'Përkundër pikave të forta, PANTHEON ka sfida domethënëse, veçanërisht për bizneset e vogla dhe të mesme që kërkojnë thjeshtësi dhe fleksibilitet.',
        items: [
          'Kosto e lartë — licenca + mbështetja + trajnimi mund të tejkalojnë €500+/vit',
          'Kurbë e rreptë mësimi — nevojiten javë trajnimi për përdorim bazik',
          'Arkitekturë desktop/klient-server — qasje e kufizuar në distancë',
          'Kërkon infrastrukturë IT — server, backup, rrjet',
          'Ndërfaqja duket e vjetëruar krahasuar me softuer modern SaaS',
          'e-Fatura kërkon konfigurim dhe vendosje shtesë',
          'Asnjë plan falas — investim minimal €500+/vit',
          'Përditësimet kërkojnë ndërhyrje të ekipit IT',
        ],
        steps: null,
      },
      {
        title: 'Ku fiton Facturino',
        content: 'Facturino është ndërtuar nga zeroja për cloud, me fokus në thjeshtësi, përputhshmëri MK dhe akses për bizneset e vogla.',
        items: [
          'I bazuar në cloud — punon nga çdo pajisje dhe shfletues, pa instalim',
          'Plan falas i disponueshëm — deri në 5 fatura në muaj pa kosto',
          'Ndërfaqe moderne — intuitive, trajnim minimal i nevojshëm',
          'e-Fatura UBL 2.1 e integruar nga dita e parë',
          'Import bankar me pajtim AI — përputhje automatike e transaksioneve',
          'POS me mbështetje për printer fiskal — 9 pajisje fiskale të mbështetura',
          'Paga me gjenerim të formularit MPIN',
          'Skanues AI për dokumente — OCR i faturave',
          'Përditësime automatike — pa nevojë për ekip IT',
          'Fillon nga €12/muaj (plani Starter)',
        ],
        steps: null,
      },
      {
        title: 'Ku vuan Facturino — të jemi të sinqertë',
        content: 'Facturino është produkt më i ri dhe ka kufizime që duhet t\'i dini para se të merrni vendim.',
        items: [
          'Produkt më i ri — më pak vite histori në treg',
          'Asnjë planifikim prodhimi (BOM ekziston por jo MRP)',
          'I kufizuar për konfigurime komplekse shumë-magazinëshe',
          'Kërkon lidhje të qëndrueshme interneti',
          'Rrjet më i vogël partnerësh/rishitësish',
          'Më pak i përshtatshëm për kompani me 50+ punonjës',
        ],
        steps: null,
      },
      {
        title: 'Krahasim kokë më kokë',
        content: null,
        items: null,
        steps: [
          { step: 'Çmimi', desc: 'PANTHEON: €500+/vit për licencë + mbështetje. Facturino: plan falas, planet me pagesë fillojnë nga €12/muaj deri në €149/muaj.' },
          { step: 'Qasje Cloud', desc: 'PANTHEON: kryesisht desktop, versioni cloud kërkon vendosje shtesë. Facturino: 100% i bazuar në cloud, qasje nga kudo.' },
          { step: 'e-Fatura', desc: 'PANTHEON: e disponueshme me konfigurim shtesë. Facturino: UBL 2.1 e integruar nga fillimi, pa hapa shtesë.' },
          { step: 'POS', desc: 'PANTHEON: mbështet zgjidhje të ndryshme POS. Facturino: POS i integruar me mbështetje për 9 printerë fiskalë.' },
          { step: 'Paga / MPIN', desc: 'PANTHEON: modul i plotë HR me të gjitha formularët. Facturino: llogaritje pagash me gjenerim automatik të MPIN.' },
          { step: 'Prodhimi', desc: 'PANTHEON: MRP i plotë me planifikim dhe normativa. Facturino: BOM bazik, por pa planifikim MRP — PANTHEON fiton qartë këtu.' },
          { step: 'Integrim Bankar', desc: 'PANTHEON: import i ekstrakteve. Facturino: import + pajtim AI që përputhet automatikisht transaksionet.' },
          { step: 'Veçori AI', desc: 'PANTHEON: asnjë aftësi e integruar AI. Facturino: skanues AI, pajtim AI, kategorizim AI.' },
          { step: 'Kurba e Mësimit', desc: 'PANTHEON: nevojiten javë trajnimi për përdorim bazik. Facturino: shumica e përdoruesve produktivë brenda 30 minutave.' },
          { step: 'Përputhshmëri MK', desc: 'PANTHEON: plotësisht në përputhje me rregulloret MK. Facturino: njësoj — TVSH, e-Fatura, MPIN, pajisje fiskale.' },
        ],
      },
      {
        title: 'Kush duhet të zgjedhë çfarë?',
        content: 'Nuk ka përgjigje universale — zgjedhja e duhur varet nga lloji dhe madhësia e biznesit tuaj.',
        items: [
          'Zgjidhni PANTHEON nëse: keni kompani prodhuese, 50+ punonjës, nevojë për magazinë të avancuar me gjurmim lotesh, keni ekip IT, dhe buxhet për €500+/vit',
          'Zgjidhni Facturino nëse: jeni NVM nën 50 punonjës, doni qasje cloud nga kudo, keni nevojë për e-Fatura pa vendosje komplekse, doni të filloni falas, nuk keni ekip IT, dhe preferoni ndërfaqe moderne dhe të thjeshtë',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'najdobar-smetkovodstven-softver-2026', title: 'Softueri më i mirë i kontabilitetit 2026' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë' },
      { slug: 'sto-e-e-faktura', title: 'Çfarë është e-Fatura dhe si funksionon?' },
    ],
    cta: {
      title: 'Provoni Facturino falas',
      desc: 'Periudhë prove 14-ditore e planit Standard — pa kartë krediti, pa detyrim.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Karşılaştırma',
    title: 'Facturino vs PANTHEON: Küçük İşletmeler İçin Hangisi Daha İyi?',
    publishDate: '23 Mayıs 2026',
    readTime: '8 dk okuma',
    intro: 'Datalab\'ın PANTHEON\'u, Makedonya\'da en yaygın kullanılan ERP sistemidir ve üretim ile toptan ticarette baskındır. Facturino, küçük ve orta ölçekli işletmeler için tasarlanmış daha yeni, bulut tabanlı bir çözümdür. Bu karşılaştırma dürüsttür — PANTHEON bazı noktalarda kazanır, Facturino diğerlerinde. Doğru seçim, işletmenizin büyüklüğüne ve ihtiyaçlarına bağlıdır.',
    sections: [
      {
        title: 'PANTHEON\'un Kazandığı Yerler',
        content: 'PANTHEON, Makedon pazarında yirmi yılı aşkın varlığa sahip olgun bir ERP sistemidir. Karmaşık ihtiyaçları olan daha büyük şirketler için başka yerde bulunması zor derin yetenekler sunar.',
        items: [
          'MK pazarında 20+ yıl — kanıtlanmış ve güvenilir sistem',
          'Tam ERP — üretim, üretim planlama, depo, insan kaynakları',
          'Derin envanter yönetimi — lot takibi, seri numaraları, son kullanma tarihleri',
          'Karmaşık raporlama ve iş zekası',
          'Ağır veriler ve büyük veritabanları için masaüstü performansı',
          'Yerel destek ve uygulama için geniş iş ortağı ağı',
          'Holding yapıları için çoklu şirket yönetimi',
        ],
        steps: null,
      },
      {
        title: 'PANTHEON\'un Zorlandığı Yerler',
        content: 'Güçlü yanlarına rağmen, PANTHEON özellikle basitlik ve esneklik arayan küçük ve orta ölçekli işletmeler için önemli zorluklara sahiptir.',
        items: [
          'Yüksek maliyet — lisans + destek + eğitim €500+/yılı aşabilir',
          'Dik öğrenme eğrisi — temel kullanım için haftalarca eğitim gerekli',
          'Masaüstü/istemci-sunucu mimarisi — sınırlı uzaktan erişim',
          'IT altyapısı gerektirir — sunucu, yedekleme, ağ',
          'Arayüz modern SaaS yazılıma kıyasla eski görünüyor',
          'e-Fatura ek yapılandırma ve kurulum gerektirir',
          'Ücretsiz katman yok — minimum yatırım €500+/yıl',
          'Güncellemeler IT ekibi müdahalesi gerektirir',
        ],
        steps: null,
      },
      {
        title: 'Facturino\'nun Kazandığı Yerler',
        content: 'Facturino, basitlik, MK uyumluluğu ve küçük işletmeler için erişilebilirliğe odaklanarak bulut için sıfırdan inşa edilmiştir.',
        items: [
          'Bulut tabanlı — herhangi bir cihaz ve tarayıcıdan çalışır, kurulum gerektirmez',
          'Ücretsiz katman mevcut — ayda 5 faturaya kadar ücretsiz',
          'Modern arayüz — sezgisel, minimum eğitim gerekli',
          'e-Fatura UBL 2.1 ilk günden yerleşik',
          'AI mutabakat ile banka akışı içe aktarma — otomatik işlem eşleştirme',
          'Mali yazıcı destekli POS — 9 mali cihaz desteklenir',
          'MPIN form oluşturma ile bordro',
          'AI belge tarayıcı — fatura OCR',
          'Otomatik güncellemeler — IT ekibine gerek yok',
          'Ayda €12\'den başlar (Başlangıç planı)',
        ],
        steps: null,
      },
      {
        title: 'Facturino\'nun Zorlandığı Yerler — Dürüst Olalım',
        content: 'Facturino daha yeni bir üründür ve karar vermeden önce bilmeniz gereken sınırlamaları vardır.',
        items: [
          'Daha yeni ürün — daha az yıllık geçmiş',
          'Üretim planlaması yok (BOM var ama MRP yok)',
          'Karmaşık çoklu depo kurulumları için sınırlı',
          'Kararlı internet bağlantısı gerektirir',
          'Daha küçük iş ortağı/bayi ağı',
          '50+ çalışanlı şirketler için daha az uygun',
        ],
        steps: null,
      },
      {
        title: 'Birebir Karşılaştırma',
        content: null,
        items: null,
        steps: [
          { step: 'Fiyatlandırma', desc: 'PANTHEON: Lisans + destek için €500+/yıl. Facturino: ücretsiz katman, ücretli planlar €12/aydan €149/aya kadar.' },
          { step: 'Bulut Erişimi', desc: 'PANTHEON: öncelikle masaüstü, bulut sürümü ek kurulum gerektirir. Facturino: %100 bulut tabanlı, her yerden erişim.' },
          { step: 'e-Fatura', desc: 'PANTHEON: ek yapılandırma ile mevcut. Facturino: baştan yerleşik UBL 2.1, ekstra adım yok.' },
          { step: 'POS', desc: 'PANTHEON: çeşitli POS çözümlerini destekler. Facturino: 9 mali yazıcı desteğiyle yerleşik POS.' },
          { step: 'Bordro / MPIN', desc: 'PANTHEON: tüm formlarla eksiksiz İK modülü. Facturino: otomatik MPIN oluşturma ile bordro hesaplama.' },
          { step: 'Üretim', desc: 'PANTHEON: planlama ve ürün ağaçlarıyla tam MRP. Facturino: temel BOM, ancak MRP planlaması yok — PANTHEON burada açıkça kazanır.' },
          { step: 'Banka Entegrasyonu', desc: 'PANTHEON: ekstre içe aktarma. Facturino: içe aktarma + işlemleri otomatik eşleştiren AI mutabakat.' },
          { step: 'AI Özellikleri', desc: 'PANTHEON: yerleşik AI yeteneği yok. Facturino: AI tarayıcı, AI mutabakat, AI kategorizasyon.' },
          { step: 'Öğrenme Eğrisi', desc: 'PANTHEON: temel kullanım için haftalarca eğitim gerekli. Facturino: çoğu kullanıcı 30 dakika içinde üretken.' },
          { step: 'MK Uyumluluğu', desc: 'PANTHEON: MK düzenlemeleriyle tamamen uyumlu. Facturino: aynı — KDV, e-Fatura, MPIN, mali cihazlar.' },
        ],
      },
      {
        title: 'Kim Neyi Seçmeli?',
        content: 'Evrensel bir cevap yoktur — doğru seçim işletmenizin türüne ve büyüklüğüne bağlıdır.',
        items: [
          'PANTHEON\'u seçin eğer: üretim şirketiniz varsa, 50+ çalışanınız varsa, lot takipli gelişmiş depoya ihtiyacınız varsa, IT ekibiniz varsa ve €500+/yıl bütçeniz varsa',
          'Facturino\'yu seçin eğer: 50\'nin altında çalışanı olan bir KOBİ iseniz, her yerden bulut erişimi istiyorsanız, karmaşık kurulum olmadan e-Faturaya ihtiyacınız varsa, ücretsiz başlamak istiyorsanız, IT ekibiniz yoksa ve modern ve basit bir arayüz tercih ediyorsanız',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'najdobar-smetkovodstven-softver-2026', title: 'En İyi Muhasebe Yazılımı 2026' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Neden Tablolar Yeterli Değil' },
      { slug: 'sto-e-e-faktura', title: 'e-Fatura Nedir ve Nasıl Çalışır?' },
    ],
    cta: {
      title: 'Facturino\'yu Ücretsiz Deneyin',
      desc: 'Standard planın 14 günlük deneme süresi — kredi kartı yok, zorunluluk yok.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function FacturinoVsPantheonPage({
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
    slug: 'facturino-vs-pantheon',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['Facturino', 'PANTHEON', 'ERP', 'comparison', 'small business'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/facturino-vs-pantheon` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
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
