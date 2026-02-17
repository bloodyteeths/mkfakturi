import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/zosto-facturino', {
    title: {
      mk: '10 причини зошто македонски бизниси го избираат Facturino — Facturino',
      en: '10 Reasons Why Macedonian Businesses Choose Facturino — Facturino',
      sq: '10 arsye pse bizneset maqedonase zgjedhin Facturino — Facturino',
      tr: 'Makedon işletmelerin Facturino\'yu tercih etmesinin 10 nedeni — Facturino',
    },
    description: {
      mk: 'Дознајте ги 10-те главни причини зошто македонските бизниси го избираат Facturino: е-Фактура, ДДВ, УЈП извештаи, PSD2 банки и повеќе.',
      en: 'Discover the 10 main reasons why Macedonian businesses choose Facturino: e-Invoice, VAT, UJP reports, PSD2 banking and more.',
      sq: 'Zbuloni 10 arsyet kryesore pse bizneset maqedonase zgjedhin Facturino: e-Faturë, TVSH, raporte UJP, banka PSD2 dhe më shumë.',
      tr: 'Makedon işletmelerin Facturino\'yu tercih etmesinin 10 ana nedenini keşfedin: e-Fatura, KDV, UJP raporları, PSD2 bankacılık ve daha fazlası.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Производ',
    title: '10 причини зошто македонски бизниси го избираат Facturino',
    publishDate: '20 февруари 2026',
    readTime: '7 мин читање',
    intro: 'Во светот на дигитализацијата, изборот на правилен софтвер за фактурирање и сметководство може да направи огромна разлика за вашиот бизнис. Facturino е создаден специјално за македонскиот пазар, со длабоко разбирање на локалните закони, даночни правила и деловни потреби. Еве ги 10-те главни причини зошто сe повеќе македонски бизниси го избираат Facturino.',
    sections: [
      {
        title: 'Зошто Facturino?',
        content: 'Facturino не е уште еден генерички софтвер за фактурирање преведен на македонски. Тој е изграден од темел за македонскиот бизнис екосистем — од даночните стапки и законските формати, до банковните интеграции и државните извештаи. Секоја функција е дизајнирана да ви заштеди време и да обезбеди целосна усогласеност со македонското законодавство.',
        items: null,
        steps: [
          { step: 'Создаден за македонско право', desc: 'Facturino ги познава сите барања на македонското даночно законодавство. Фактурите автоматски ги содржат сите задолжителни полиња барани од УЈП, вклучувајќи ЕДБ, ЕМБС, правилно форматирани износи и законски забелешки. Нема потреба да проверувате дали вашата фактура е законски валидна — Facturino тоа го прави автоматски.' },
          { step: 'Усогласеност со е-Фактура', desc: 'Македонија воведува задолжителна е-Фактура за B2G и B2B трансакции. Facturino е подготвен за овој преод со вградена поддршка за UBL XML формат и директно поднесување. Не чекајте последен момент — бидете подготвени од денес.' },
          { step: 'Автоматска ДДВ пресметка', desc: 'Facturino автоматски го пресметува ДДВ по стапките важечки во Македонија (18% стандардна, 5% преференцијална). Системот следи дали сте ДДВ обврзник, го пресметува влезниот и излезниот ДДВ и генерира ДДВ извештаи подготвени за поднесување до УЈП.' },
          { step: 'УЈП-формат извештаи', desc: 'Сите извештаи во Facturino се генерираат во формати компатибилни со барањата на Управата за јавни приходи. Месечни ДДВ извештаи, годишни биланси, МПИН образци за плати — сe е автоматизирано и подготвено за поднесување.' },
          { step: 'PSD2 банковна поврзаност', desc: 'Поврзете ја вашата банковна сметка со Facturino преку PSD2 (Open Banking) стандардот. Автоматски ги увезувате банковните трансакции, ги препознавате уплатите од клиенти и ги усогласувате со фактурите. Нема повеќе рачно проверување на банкарски изводи.' },
          { step: 'Повеќејазичност (МК/SQ/TR/EN)', desc: 'Facturino работи на 4 јазика: македонски, албански, турски и англиски. Ова е особено важно за бизниси кои работат со клиенти од различни етнички заедници или извозуваат. Фактурите може да се генерираат на јазикот на клиентот.' },
          { step: 'Бесплатен план на располагање', desc: 'Започнете без финансиски ризик со бесплатниот план кој вклучува до 3 фактури месечно. Идеален за фрилансери и нови бизниси кои сакаат да го тестираат системот пред да се обврзат. Надградете кога сте подготвени, без притисок.' },
          { step: 'Облак базиран пристап', desc: 'Facturino е целосно облак базиран — пристапете од компјутер, таблет или телефон, од канцеларија или на пат. Нема инсталација, нема ажурирања, нема губење на податоци. Вашите финансии се секогаш достапни и безбедни со автоматски бекапи.' },
          { step: 'Партнерска програма за сметководители', desc: 'Сметководствените фирми добиваат посебни услови преку нашата партнерска програма. Управувајте со сите клиенти од една конзола, добивајте провизија од 20% за месечни претплати и пристапете до напредни функции. Идеално за сметководители кои сакаат да го дигитализираат своето работење.' },
          { step: 'Брз и посветен тим за поддршка', desc: 'Нашиот тим за поддршка е лоциран во Македонија и одговара на македонски, албански, турски и англиски. Просечно време на одговор е под 2 часа за работни денови. Добивате помош од луѓе кои го разбираат македонскиот бизнис контекст, не од генерички чет-бот.' },
        ],
      },
      {
        title: 'Што велат нашите корисници',
        content: 'Бизниси од различни индустрии во Македонија веќе го користат Facturino за секојдневно фактурирање и управување со финансии. Од мали продавници до ИТ компании, од сметководствени фирми до производствени претпријатија — Facturino им помага да заштедат време, да избегнат грешки и да бидат секогаш усогласени со законите.',
        items: [
          'Заштеда од 5-10 часа месечно на административна работа',
          'Нула грешки во фактурите од усвојувањето на Facturino',
          'Моментално генерирање на извештаи кои порано траеја со часови',
          'Автоматско препознавање на уплати заштедува 2-3 часа неделно',
          'Подготвеност за е-Фактура без дополнителен трошок или напор',
        ],
        steps: null,
      },
      {
        title: 'Започнете денес',
        content: 'Регистрацијата трае помалку од 2 минути. Нема потреба од кредитна картичка за бесплатниот план. Импортирајте ги вашите постоечки податоци од Excel или друг систем и започнете да фактурирате веднаш. Ако ви треба помош, нашиот тим е тука да ви помогне со секој чекор од процесот.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'za-smetkovoditeli', title: 'Зошто сметководителите преминуваат на Facturino' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Годишно затворање на книги: 6 чекори со Facturino' },
    ],
    cta: {
      title: 'Придружете се на илјадници македонски бизниси',
      desc: 'Регистрирајте се бесплатно и дознајте зошто Facturino е број 1 избор за фактурирање во Македонија.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Product',
    title: '10 Reasons Why Macedonian Businesses Choose Facturino',
    publishDate: 'February 20, 2026',
    readTime: '7 min read',
    intro: 'In the world of digitalization, choosing the right invoicing and accounting software can make a huge difference for your business. Facturino is built specifically for the Macedonian market, with a deep understanding of local laws, tax rules, and business needs. Here are the 10 main reasons why more and more Macedonian businesses are choosing Facturino.',
    sections: [
      {
        title: 'Why Facturino?',
        content: 'Facturino is not just another generic invoicing software translated into Macedonian. It is built from the ground up for the Macedonian business ecosystem — from tax rates and legal formats, to bank integrations and government reports. Every feature is designed to save you time and ensure full compliance with Macedonian legislation.',
        items: null,
        steps: [
          { step: 'Built for Macedonian Law', desc: 'Facturino knows all the requirements of Macedonian tax legislation. Invoices automatically include all mandatory fields required by UJP, including EDB, EMBS, properly formatted amounts, and legal notes. No need to check whether your invoice is legally valid — Facturino does it automatically.' },
          { step: 'e-Invoice Compliant', desc: 'Macedonia is introducing mandatory e-Invoice for B2G and B2B transactions. Facturino is ready for this transition with built-in UBL XML format support and direct submission. Do not wait until the last moment — be prepared from today.' },
          { step: 'Automatic VAT Calculation', desc: 'Facturino automatically calculates VAT at the rates applicable in Macedonia (18% standard, 5% preferential). The system tracks your VAT status, calculates input and output VAT, and generates VAT reports ready for submission to UJP.' },
          { step: 'UJP-Format Reports', desc: 'All reports in Facturino are generated in formats compatible with Public Revenue Office requirements. Monthly VAT reports, annual balance sheets, MPIN payroll forms — everything is automated and ready for submission.' },
          { step: 'PSD2 Bank Connection', desc: 'Connect your bank account to Facturino through the PSD2 (Open Banking) standard. Automatically import bank transactions, recognize client payments, and reconcile them with invoices. No more manual checking of bank statements.' },
          { step: 'Multi-Language (MK/SQ/TR/EN)', desc: 'Facturino works in 4 languages: Macedonian, Albanian, Turkish, and English. This is especially important for businesses working with clients from different ethnic communities or exporting. Invoices can be generated in the client\'s language.' },
          { step: 'Free Plan Available', desc: 'Start with zero financial risk using the free plan that includes up to 3 invoices per month. Ideal for freelancers and new businesses that want to test the system before committing. Upgrade when you are ready, no pressure.' },
          { step: 'Cloud-Based Access', desc: 'Facturino is fully cloud-based — access from a computer, tablet, or phone, from the office or on the go. No installation, no updates, no data loss. Your finances are always accessible and secure with automatic backups.' },
          { step: 'Partner Program for Accountants', desc: 'Accounting firms get special conditions through our partner program. Manage all clients from a single console, earn 20% commission on monthly subscriptions, and access advanced features. Ideal for accountants who want to digitalize their operations.' },
          { step: 'Fast and Dedicated Support Team', desc: 'Our support team is located in Macedonia and responds in Macedonian, Albanian, Turkish, and English. Average response time is under 2 hours on business days. You get help from people who understand the Macedonian business context, not from a generic chatbot.' },
        ],
      },
      {
        title: 'What Our Users Say',
        content: 'Businesses from various industries in Macedonia are already using Facturino for daily invoicing and financial management. From small shops to IT companies, from accounting firms to manufacturing enterprises — Facturino helps them save time, avoid errors, and always stay compliant with the law.',
        items: [
          'Saving 5-10 hours per month on administrative work',
          'Zero invoice errors since adopting Facturino',
          'Instant report generation that used to take hours',
          'Automatic payment recognition saves 2-3 hours weekly',
          'e-Invoice readiness at no additional cost or effort',
        ],
        steps: null,
      },
      {
        title: 'Get Started Today',
        content: 'Registration takes less than 2 minutes. No credit card needed for the free plan. Import your existing data from Excel or another system and start invoicing right away. If you need help, our team is here to assist you with every step of the process.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'za-smetkovoditeli', title: 'Why Accountants Are Switching to Facturino' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Year-End Closing: 6 Steps with Facturino' },
    ],
    cta: {
      title: 'Join Thousands of Macedonian Businesses',
      desc: 'Sign up free and discover why Facturino is the number 1 choice for invoicing in Macedonia.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Produkt',
    title: '10 arsye pse bizneset maqedonase zgjedhin Facturino',
    publishDate: '20 shkurt 2026',
    readTime: '7 min lexim',
    intro: 'Në botën e digjitalizimit, zgjedhja e softuerit të duhur të faturimit dhe kontabilitetit mund të bëjë ndryshim të madh për biznesin tuaj. Facturino është ndërtuar posaçërisht për tregun maqedonas, me kuptim të thellë të ligjeve lokale, rregullave tatimore dhe nevojave të biznesit. Ja 10 arsyet kryesore pse gjithnjë e më shumë biznese maqedonase zgjedhin Facturino.',
    sections: [
      {
        title: 'Pse Facturino?',
        content: 'Facturino nuk është thjesht një softuer tjetër gjenerik faturimi i përkthyer në maqedonisht. Ai është ndërtuar nga themeli për ekasistemin e biznesit maqedonas — nga normat tatimore dhe formatet ligjore, te integrimet bankare dhe raportet qeveritare. Çdo veçori është dizajnuar për t\'ju kursyer kohë dhe për të siguruar përputhshmëri të plotë me legjislacionin maqedonas.',
        items: null,
        steps: [
          { step: 'Ndërtuar për ligjin maqedonas', desc: 'Facturino i njeh të gjitha kërkesat e legjislacionit tatimor maqedonas. Faturat automatikisht përfshijnë të gjitha fushat e detyrueshme të kërkuara nga UJP, duke përfshirë EDB, EMBS, shumat e formatuara siç duhet dhe shënimet ligjore. Nuk ka nevojë të kontrolloni nëse fatura juaj është ligjërisht e vlefshme — Facturino e bën automatikisht.' },
          { step: 'Përputhje me e-Faturën', desc: 'Maqedonia po prezanton e-Faturën e detyrueshme për transaksionet B2G dhe B2B. Facturino është gati për këtë tranzicion me mbështetje të integruar të formatit UBL XML dhe dorëzim direkt. Mos prisni deri në momentin e fundit — bëhuni gati që sot.' },
          { step: 'Llogaritje automatike e TVSH', desc: 'Facturino llogarit automatikisht TVSH-në me normat e aplikueshme në Maqedoni (18% standarde, 5% preferenciale). Sistemi ndjek statusin tuaj të TVSH, llogarit TVSH hyrëse dhe dalëse, dhe gjeneron raporte TVSH gati për dorëzim në UJP.' },
          { step: 'Raporte në formatin e UJP', desc: 'Të gjitha raportet në Facturino gjenerohen në formate të përputhshme me kërkesat e Zyrës së të Ardhurave Publike. Raportet mujore të TVSH, bilancet vjetore, formularët MPIN të pagave — gjithçka është e automatizuar dhe gati për dorëzim.' },
          { step: 'Lidhje bankare PSD2', desc: 'Lidhni llogarinë tuaj bankare me Facturino përmes standardit PSD2 (Open Banking). Importoni automatikisht transaksionet bankare, njihni pagesat e klientëve dhe pajtojini ato me faturat. Jo më kontroll manual i deklaratave bankare.' },
          { step: 'Shumëgjuhësi (MK/SQ/TR/EN)', desc: 'Facturino punon në 4 gjuhë: maqedonisht, shqip, turqisht dhe anglisht. Kjo është veçanërisht e rëndësishme për bizneset që punojnë me klientë nga komunitete të ndryshme etnike ose eksportojnë. Faturat mund të gjenerohen në gjuhën e klientit.' },
          { step: 'Plan falas i disponueshëm', desc: 'Filloni pa rrezik financiar me planin falas që përfshin deri 3 fatura në muaj. Ideal për freelancer-ë dhe biznese të reja që duan ta testojnë sistemin para se të angazhohen. Përmirësoni kur jeni gati, pa presion.' },
          { step: 'Qasje e bazuar në cloud', desc: 'Facturino është plotësisht i bazuar në cloud — qasje nga kompjuteri, tableti ose telefoni, nga zyra ose në rrugë. Pa instalim, pa përditësime, pa humbje të dhënash. Financat tuaja janë gjithmonë të qasshme dhe të sigurta me backup automatik.' },
          { step: 'Program partneriteti për kontabilistë', desc: 'Firmat e kontabilitetit marrin kushte të veçanta përmes programit tonë të partneritetit. Menaxhoni të gjithë klientët nga një konzolë e vetme, fitoni 20% komision në abonimet mujore dhe qasuni në veçori të avancuara. Ideal për kontabilistë që duan ta digjitalizojnë punën e tyre.' },
          { step: 'Ekip mbështetjeje i shpejtë dhe i dedikuar', desc: 'Ekipi ynë i mbështetjes ndodhet në Maqedoni dhe përgjigjet në maqedonisht, shqip, turqisht dhe anglisht. Koha mesatare e përgjigjes është nën 2 orë në ditët e punës. Merrni ndihmë nga njerëz që e kuptojnë kontekstin e biznesit maqedonas, jo nga një chatbot gjenerik.' },
        ],
      },
      {
        title: 'Çfarë thonë përdoruesit tanë',
        content: 'Biznese nga industri të ndryshme në Maqedoni tashmë përdorin Facturino për faturim ditor dhe menaxhim financiar. Nga dyqane të vogla te kompani IT, nga firma kontabiliteti te ndërmarrje prodhuese — Facturino i ndihmon të kursejnë kohë, të shmangin gabimet dhe të qëndrojnë gjithmonë në përputhje me ligjin.',
        items: [
          'Kursim 5-10 orësh në muaj në punë administrative',
          'Zero gabime faturash që nga miratimi i Facturino',
          'Gjenerim i menjëhershëm i raporteve që dikur zgjaste orë',
          'Njohja automatike e pagesave kursen 2-3 orë në javë',
          'Gatishmëri për e-Faturë pa kosto ose përpjekje shtesë',
        ],
        steps: null,
      },
      {
        title: 'Filloni sot',
        content: 'Regjistrimi zgjat më pak se 2 minuta. Nuk kërkohet kartë krediti për planin falas. Importoni të dhënat tuaja ekzistuese nga Excel ose sistem tjetër dhe filloni faturimin menjëherë. Nëse keni nevojë për ndihmë, ekipi ynë është këtu për t\'ju ndihmuar me çdo hap të procesit.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'za-smetkovoditeli', title: 'Pse kontabilistët po kalojnë në Facturino' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Mbyllja e vitit: 6 hapa me Facturino' },
    ],
    cta: {
      title: 'Bashkohuni me mijëra biznese maqedonase',
      desc: 'Regjistrohuni falas dhe zbuloni pse Facturino është zgjedhja numër 1 për faturim në Maqedoni.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Ürün',
    title: 'Makedon işletmelerin Facturino\'yu tercih etmesinin 10 nedeni',
    publishDate: '20 Şubat 2026',
    readTime: '7 dk okuma',
    intro: 'Dijitalleşme dünyasında, doğru faturalama ve muhasebe yazılımını seçmek işletmeniz için büyük bir fark yaratabilir. Facturino, yerel yasalar, vergi kuralları ve iş ihtiyaçları hakkında derin bir anlayışla özellikle Makedon pazarı için inşa edilmiştir. İşte giderek daha fazla Makedon işletmenin Facturino\'yu tercih etmesinin 10 ana nedeni.',
    sections: [
      {
        title: 'Neden Facturino?',
        content: 'Facturino, Makedoncaya çevrilmiş sıradan bir faturalama yazılımı değildir. Makedon iş ekosistemi için sıfırdan inşa edilmiştir — vergi oranlarından ve yasal formatlardan banka entegrasyonlarına ve devlet raporlarına kadar. Her özellik size zaman kazandırmak ve Makedon mevzuatıyla tam uyumluluğu sağlamak için tasarlanmıştır.',
        items: null,
        steps: [
          { step: 'Makedon Hukuku İçin İnşa Edildi', desc: 'Facturino, Makedon vergi mevzuatının tüm gereksinimlerini bilir. Faturalar, EDB, EMBS, düzgün biçimlendirilmiş tutarlar ve yasal notlar dahil UJP tarafından istenen tüm zorunlu alanları otomatik olarak içerir. Faturanızın yasal olarak geçerli olup olmadığını kontrol etmenize gerek yok — Facturino bunu otomatik olarak yapar.' },
          { step: 'e-Fatura Uyumlu', desc: 'Makedonya, B2G ve B2B işlemleri için zorunlu e-Fatura getirmektedir. Facturino, yerleşik UBL XML format desteği ve doğrudan gönderim ile bu geçişe hazırdır. Son ana kadar beklemeyin — bugünden hazır olun.' },
          { step: 'Otomatik KDV Hesaplama', desc: 'Facturino, Makedonya\'da geçerli oranlarda (%18 standart, %5 tercihli) KDV\'yi otomatik olarak hesaplar. Sistem KDV durumunuzu takip eder, giriş ve çıkış KDV\'sini hesaplar ve UJP\'ye sunulmaya hazır KDV raporları oluşturur.' },
          { step: 'UJP Formatında Raporlar', desc: 'Facturino\'daki tüm raporlar, Kamu Gelir İdaresi gereksinimleriyle uyumlu formatlarda oluşturulur. Aylık KDV raporları, yıllık bilançolar, MPIN bordro formları — her şey otomatikleştirilmiş ve sunuma hazırdır.' },
          { step: 'PSD2 Banka Bağlantısı', desc: 'PSD2 (Açık Bankacılık) standardı aracılığıyla banka hesabınızı Facturino\'ya bağlayın. Banka işlemlerini otomatik olarak içe aktarın, müşteri ödemelerini tanıyın ve bunları faturalarla eşleştirin. Artık banka hesap özetlerini manuel olarak kontrol etmenize gerek yok.' },
          { step: 'Çok Dilli (MK/SQ/TR/EN)', desc: 'Facturino 4 dilde çalışır: Makedonca, Arnavutça, Türkçe ve İngilizce. Bu, farklı etnik topluluklardan müşterilerle çalışan veya ihracat yapan işletmeler için özellikle önemlidir. Faturalar müşterinin dilinde oluşturulabilir.' },
          { step: 'Ücretsiz Plan Mevcut', desc: 'Ayda 3 faturaya kadar içeren ücretsiz planla sıfır finansal riskle başlayın. Sistemi taahhütte bulunmadan önce test etmek isteyen serbest çalışanlar ve yeni işletmeler için ideal. Hazır olduğunuzda yükseltin, baskı yok.' },
          { step: 'Bulut Tabanlı Erişim', desc: 'Facturino tamamen bulut tabanlıdır — bilgisayardan, tabletten veya telefondan, ofisten veya yolda erişin. Kurulum yok, güncelleme yok, veri kaybı yok. Finanslarınız otomatik yedeklemelerle her zaman erişilebilir ve güvenlidir.' },
          { step: 'Muhasebeciler İçin Ortaklık Programı', desc: 'Muhasebe firmaları ortaklık programımız aracılığıyla özel koşullar alır. Tüm müşterileri tek bir konsoldan yönetin, aylık aboneliklerde %20 komisyon kazanın ve gelişmiş özelliklere erişin. Operasyonlarını dijitalleştirmek isteyen muhasebeciler için ideal.' },
          { step: 'Hızlı ve Özel Destek Ekibi', desc: 'Destek ekibimiz Makedonya\'da bulunmaktadır ve Makedonca, Arnavutça, Türkçe ve İngilizce olarak yanıt verir. İş günlerinde ortalama yanıt süresi 2 saatin altındadır. Genel bir chatbot\'tan değil, Makedon iş bağlamını anlayan insanlardan yardım alırsınız.' },
        ],
      },
      {
        title: 'Kullanıcılarımız Ne Diyor',
        content: 'Makedonya\'daki çeşitli sektörlerden işletmeler günlük faturalama ve finansal yönetim için Facturino\'yu zaten kullanmaktadır. Küçük dükkanlardan BT şirketlerine, muhasebe firmalarından üretim işletmelerine kadar — Facturino zaman tasarrufu sağlamalarına, hatalardan kaçınmalarına ve her zaman yasalara uyumlu kalmalarına yardımcı olmaktadır.',
        items: [
          'İdari işlerde ayda 5-10 saat tasarruf',
          'Facturino\'yu benimsedikten sonra sıfır fatura hatası',
          'Eskiden saatler süren raporların anında oluşturulması',
          'Otomatik ödeme tanıma haftada 2-3 saat tasarruf sağlıyor',
          'Ek maliyet veya çaba olmadan e-Fatura hazırlığı',
        ],
        steps: null,
      },
      {
        title: 'Bugün Başlayın',
        content: 'Kayıt 2 dakikadan az sürer. Ücretsiz plan için kredi kartı gerekmez. Mevcut verilerinizi Excel veya başka bir sistemden içe aktarın ve hemen faturalamaya başlayın. Yardıma ihtiyacınız olursa, ekibimiz sürecin her adımında size yardımcı olmak için burada.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'za-smetkovoditeli', title: 'Muhasebeciler neden Facturino\'ya geçiyor' },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Yıl sonu kapanışı: Facturino ile 6 adım' },
    ],
    cta: {
      title: 'Binlerce Makedon İşletmeye Katılın',
      desc: 'Ücretsiz kaydolun ve Facturino\'nun neden Makedonya\'da faturalama için 1 numaralı tercih olduğunu keşfedin.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function ZostoFacturinoPage({
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
