import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/ai-asistent-smetkovodstvo', {
    title: {
      mk: 'Прашај го AI: Кој ми должи? Дали сум профитабилен? — Facturino',
      en: 'Ask AI: Who Owes Me? Am I Profitable? — Facturino',
      sq: 'Pyetni AI: Kush më ka borxh? A jam fitimprurës? — Facturino',
      tr: 'AI\'ya Sorun: Kim Bana Borçlu? Kârlı mıyım? — Facturino',
    },
    description: {
      mk: 'Дознајте како AI асистентот на Facturino одговара на финансиски прашања на македонски, креира фактури и анализира профитабилност — без сметководствено знаење.',
      en: 'Discover how Facturino\'s AI assistant answers financial questions in plain language, creates invoices, and analyzes profitability — no accounting knowledge needed.',
      sq: 'Zbuloni si asistenti AI i Facturino u përgjigjet pyetjeve financiare në gjuhën tuaj, krijon fatura dhe analizon fitimprurësinë — pa njohuri kontabiliteti.',
      tr: 'Facturino\'nun AI asistanının finansal sorulara nasıl yanıt verdiğini, fatura oluşturduğunu ve kârlılığı analiz ettiğini keşfedin — muhasebe bilgisi gerekmez.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Производ',
    title: 'Прашај го AI: Кој ми должи? Дали сум профитабилен?',
    publishDate: '15 март 2026',
    readTime: '8 мин читање',
    intro: 'Не ви треба диплома по сметководство за да ги разберете вашите финансии. Со AI асистентот на Facturino, поставувајте прашања на обичен македонски јазик и добивајте моментални одговори. Колку ви должат клиентите? Дали сте профитабилни овој месец? Колку ДДВ имате за плаќање? Едноставно прашајте — и AI ќе ви одговори за секунди.',
    sections: [
      {
        title: '5 прашања што може да ги поставите',
        content: 'AI асистентот на Facturino разбира природен македонски јазик. Не треба да знаете SQL, формули или кратенки — само прашајте како што би прашале колега. Еве 5 реални примери:',
        items: null,
        steps: [
          { step: '„Кој ми должи најмногу?"', desc: 'AI ги анализира сите неплатени фактури и ви покажува листа на должници подредени по износ. Гледате кој ви должи, колку денари и колку дена фактурата е доспеана. Идеално за следење на готовинскиот тек без рачно пребарување.' },
          { step: '„Колку ДДВ имам за овој квартал?"', desc: 'Моментална пресметка на влезен и излезен ДДВ за тековниот квартал. AI ги сумира сите фактури и трошоци, ја пресметува разликата и ви дава јасен одговор — подготвен за ДДВ пријавата до УЈП.' },
          { step: '„Дали сум профитабилен овој месец?"', desc: 'AI генерира краток извештај за приходи и расходи (P&L) за тековниот месец. Гледате вкупни приходи, вкупни трошоци и нето профит или загуба — сe на една реченица, без отворање на сложени извештаи.' },
          { step: '„Направи фактура за Маркет ДООЕЛ за 50,000 ден"', desc: 'AI не само одговара на прашања — може и да создаде документи. Од оваа команда, AI автоматски креира нацрт-фактура со пополнети податоци за клиентот, износот и ДДВ. Вие само потврдувате и испраќате.' },
          { step: '„Покажи ми ги сите доспеани фактури"', desc: 'Моментален преглед на сите фактури кои го поминале рокот за плаќање. AI ги подредува по старост и износ, за да знаете точно кои клиенти бараат потсетување. Заштедете време наместо рачно филтрирање.' },
        ],
      },
      {
        title: 'Како работи AI асистентот',
        content: 'AI асистентот на Facturino е вграден директно во апликацијата. Нема потреба од посебна инсталација, додатоци или техничко знаење. Еве го процесот чекор по чекор:',
        items: null,
        steps: [
          { step: 'Отворете го чет-прозорчето', desc: 'Во долниот десен агол на Facturino ќе видите иконче за разговор. Кликнете на него за да го отворите AI асистентот. Достапен е од секоја страница во апликацијата — Dashboard, фактури, трошоци или извештаи.' },
          { step: 'Поставете прашање на македонски', desc: 'Напишете го вашето прашање на обичен македонски јазик. AI го разбира контекстот и го обработува преку модели за природен јазик (NLP). Не треба специјална синтакса или команди — само пишувајте како во порака.' },
          { step: 'AI ја пребарува базата на податоци', desc: 'Системот безбедно ги пребарува вашите финансиски податоци — фактури, трошоци, клиенти, плаќања — и ги анализира за да одговори на вашето прашање. Сe се случува во реално време, без чекање.' },
          { step: 'Добивате форматиран одговор', desc: 'AI ви враќа јасен, форматиран одговор со бројки, табели или листи. Ако прашањето е команда (на пример „направи фактура"), AI креира AiDraft — нацрт кој вие го прегледувате и потврдувате пред да стане реален документ.' },
        ],
      },
      {
        title: 'Од прашање до фактура за 30 секунди',
        content: 'Најмоќната функција на AI асистентот е можноста да создава документи директно од текстуална команда. Наместо да навигирате низ менија, да пополнувате форми и да барате клиенти — едноставно кажете му на AI што сакате.',
        items: [
          'Кажете: „Направи фактура за Компанија X за 30,000 денари" — AI го наоѓа клиентот, го пополнува износот и ДДВ-то',
          'AI креира нацрт (AiDraft) — преглед на сите полиња пред потврда',
          'Прегледајте и корегирајте ако е потребно — имате целосна контрола',
          'Еден клик на „Потврди" — фактурата е креирана и подготвена за испраќање',
          'Целиот процес трае помалку од 30 секунди — наспроти 3-5 минути рачно',
        ],
        steps: null,
      },
      {
        title: 'Безбедност и приватност',
        content: 'Знаеме дека финансиските податоци се чувствителни. Затоа AI асистентот на Facturino е дизајниран со безбедноста на прво место.',
        items: [
          'AI не ги зачувува вашите разговори — секоја сесија е независна и привремена',
          'Сите податоци се шифрирани при пренос и при складирање (TLS + AES-256)',
          'Вашите финансиски податоци никогаш не се споделуваат со трети страни',
          'AI пристапува само до податоците на вашата компанија — целосна изолација меѓу корисниците',
          'Серверите се во Европа, усогласени со GDPR стандардите за заштита на податоци',
        ],
        steps: null,
      },
      {
        title: 'Пробајте го AI асистентот',
        content: 'AI асистентот е достапен на Standard план и погоре. Ако сe уште немате Facturino сметка, регистрирајте се бесплатно и надградете на Standard за да добиете пристап до AI функциите. Со 14-дневен пробен период на Standard планот, може да го тестирате AI асистентот без обврзување.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Сметководство за почетници: Од каде да почнете' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за македонски бизниси' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
    ],
    cta: {
      title: 'Прашајте го AI — одговорот е на 30 секунди',
      desc: 'Регистрирајте се и дознајте колку ви должат, дали сте профитабилни и направете фактура само со една реченица.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Product',
    title: 'Ask AI: Who Owes Me? Am I Profitable?',
    publishDate: 'March 15, 2026',
    readTime: '8 min read',
    intro: 'You don\'t need an accounting degree to understand your finances. With Facturino\'s AI assistant, ask questions in plain language and get instant answers. How much do your clients owe? Are you profitable this month? How much VAT do you owe? Simply ask — and AI will answer in seconds.',
    sections: [
      {
        title: '5 Questions You Can Ask',
        content: 'Facturino\'s AI assistant understands natural language. You don\'t need to know SQL, formulas, or shortcuts — just ask as you would ask a colleague. Here are 5 real examples:',
        items: null,
        steps: [
          { step: '"Who owes me the most?"', desc: 'AI analyzes all unpaid invoices and shows you a list of debtors sorted by amount. You see who owes you, how many denars, and how many days the invoice is overdue. Perfect for tracking cash flow without manual searching.' },
          { step: '"How much VAT do I have this quarter?"', desc: 'Instant calculation of input and output VAT for the current quarter. AI sums up all invoices and expenses, calculates the difference, and gives you a clear answer — ready for your VAT return to UJP.' },
          { step: '"Am I profitable this month?"', desc: 'AI generates a brief income and expense report (P&L) for the current month. You see total revenue, total expenses, and net profit or loss — all in one sentence, without opening complex reports.' },
          { step: '"Create an invoice for Market DOOEL for 50,000 den"', desc: 'AI doesn\'t just answer questions — it can create documents too. From this command, AI automatically creates a draft invoice with the client data, amount, and VAT pre-filled. You just confirm and send.' },
          { step: '"Show me all overdue invoices"', desc: 'Instant overview of all invoices past their payment deadline. AI sorts them by age and amount so you know exactly which clients need a reminder. Save time instead of filtering manually.' },
        ],
      },
      {
        title: 'How the AI Assistant Works',
        content: 'Facturino\'s AI assistant is built directly into the application. No separate installation, plugins, or technical knowledge required. Here\'s the process step by step:',
        items: null,
        steps: [
          { step: 'Open the chat widget', desc: 'In the bottom-right corner of Facturino, you\'ll see a chat icon. Click it to open the AI assistant. It\'s available from every page in the application — Dashboard, invoices, expenses, or reports.' },
          { step: 'Ask a question in your language', desc: 'Type your question in plain language. AI understands the context and processes it through natural language processing (NLP) models. No special syntax or commands needed — just type as you would in a message.' },
          { step: 'AI searches your database', desc: 'The system securely queries your financial data — invoices, expenses, clients, payments — and analyzes it to answer your question. Everything happens in real time, no waiting.' },
          { step: 'You get a formatted answer', desc: 'AI returns a clear, formatted response with numbers, tables, or lists. If the question is a command (e.g., "create an invoice"), AI creates an AiDraft — a draft you review and confirm before it becomes a real document.' },
        ],
      },
      {
        title: 'From Question to Invoice in 30 Seconds',
        content: 'The most powerful feature of the AI assistant is the ability to create documents directly from a text command. Instead of navigating menus, filling out forms, and searching for clients — simply tell AI what you want.',
        items: [
          'Say: "Create an invoice for Company X for 30,000 denars" — AI finds the client, fills in the amount and VAT',
          'AI creates a draft (AiDraft) — preview of all fields before confirmation',
          'Review and adjust if needed — you have full control',
          'One click on "Confirm" — the invoice is created and ready to send',
          'The entire process takes less than 30 seconds — compared to 3-5 minutes manually',
        ],
        steps: null,
      },
      {
        title: 'Security and Privacy',
        content: 'We know financial data is sensitive. That\'s why Facturino\'s AI assistant is designed with security first.',
        items: [
          'AI does not store your conversations — each session is independent and temporary',
          'All data is encrypted in transit and at rest (TLS + AES-256)',
          'Your financial data is never shared with third parties',
          'AI accesses only your company\'s data — complete isolation between users',
          'Servers are in Europe, compliant with GDPR data protection standards',
        ],
        steps: null,
      },
      {
        title: 'Try the AI Assistant',
        content: 'The AI assistant is available on the Standard plan and above. If you don\'t have a Facturino account yet, sign up for free and upgrade to Standard to access AI features. With a 14-day trial on the Standard plan, you can test the AI assistant with no commitment.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Accounting for Beginners: Where to Start' },
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for Macedonian Businesses' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
    ],
    cta: {
      title: 'Ask AI — The Answer Is 30 Seconds Away',
      desc: 'Sign up and find out who owes you, whether you\'re profitable, and create an invoice with just one sentence.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Produkt',
    title: 'Pyetni AI: Kush më ka borxh? A jam fitimprurës?',
    publishDate: '15 mars 2026',
    readTime: '8 min lexim',
    intro: 'Nuk keni nevojë për diplomë kontabiliteti për të kuptuar financat tuaja. Me asistentin AI të Facturino, bëni pyetje në gjuhën tuaj të zakonshme dhe merrni përgjigje të menjëhershme. Sa ju kanë borxh klientët? A jeni fitimprurës këtë muaj? Sa TVSH keni për të paguar? Thjesht pyetni — dhe AI do t\'ju përgjigjet brenda sekondash.',
    sections: [
      {
        title: '5 pyetje që mund t\'i bëni',
        content: 'Asistenti AI i Facturino kupton gjuhën natyrore. Nuk keni nevojë të dini SQL, formula ose shkurtesa — thjesht pyetni siç do t\'i pyesnit një kolegu. Ja 5 shembuj realë:',
        items: null,
        steps: [
          { step: '„Kush më ka borxh më shumë?"', desc: 'AI analizon të gjitha faturat e papaguara dhe ju tregon një listë debitorësh të renditur sipas shumës. Shihni kush ju ka borxh, sa denarë dhe sa ditë ka kaluar afati i faturës. Perfekte për ndjekjen e rrjedhës së parave pa kërkim manual.' },
          { step: '„Sa TVSH kam për këtë tremujor?"', desc: 'Llogaritje e menjëhershme e TVSH hyrëse dhe dalëse për tremujorin aktual. AI mbledh të gjitha faturat dhe shpenzimet, llogarit diferencën dhe ju jep një përgjigje të qartë — gati për deklaratën e TVSH në UJP.' },
          { step: '„A jam fitimprurës këtë muaj?"', desc: 'AI gjeneron një raport të shkurtër të të ardhurave dhe shpenzimeve (P&L) për muajin aktual. Shihni të ardhurat totale, shpenzimet totale dhe fitimin ose humbjen neto — gjithçka në një fjali, pa hapur raporte të ndërlikuara.' },
          { step: '„Krijo faturë për Market SHPK për 50,000 den"', desc: 'AI nuk përgjigjet vetëm pyetjeve — mund të krijojë edhe dokumente. Nga kjo komandë, AI automatikisht krijon një draft-faturë me të dhënat e klientit, shumën dhe TVSH të plotësuara. Ju thjesht konfirmoni dhe dërgoni.' },
          { step: '„Tregoji të gjitha faturat e vonuara"', desc: 'Pamje e menjëhershme e të gjitha faturave që kanë kaluar afatin e pagesës. AI i rendit sipas vjetërsisë dhe shumës, që të dini saktë cilët klientë kanë nevojë për kujtesë. Kurseni kohë në vend të filtrimit manual.' },
        ],
      },
      {
        title: 'Si funksionon asistenti AI',
        content: 'Asistenti AI i Facturino është ndërtuar direkt brenda aplikacionit. Nuk ka nevojë për instalim të veçantë, shtojca ose njohuri teknike. Ja procesi hap pas hapi:',
        items: null,
        steps: [
          { step: 'Hapni dritaren e bisedës', desc: 'Në këndin e poshtëm djathtas të Facturino do të shihni një ikonë bisede. Klikoni për ta hapur asistentin AI. Ai është i disponueshëm nga çdo faqe e aplikacionit — Dashboard, fatura, shpenzime ose raporte.' },
          { step: 'Bëni pyetje në gjuhën tuaj', desc: 'Shkruani pyetjen tuaj në gjuhë të zakonshme. AI kupton kontekstin dhe e përpunon përmes modeleve të përpunimit të gjuhës natyrore (NLP). Nuk ka nevojë sintaksë speciale ose komanda — thjesht shkruani si në mesazh.' },
          { step: 'AI kërkon në bazën e të dhënave', desc: 'Sistemi kërkon në mënyrë të sigurt të dhënat tuaja financiare — fatura, shpenzime, klientë, pagesa — dhe i analizon për t\'iu përgjigjur pyetjes. Gjithçka ndodh në kohë reale, pa pritje.' },
          { step: 'Merrni përgjigje të formatuar', desc: 'AI ju kthen një përgjigje të qartë, të formatuar me numra, tabela ose lista. Nëse pyetja është komandë (p.sh. „krijo faturë"), AI krijon një AiDraft — draft që ju e shqyrtoni dhe konfirmoni para se të bëhet dokument real.' },
        ],
      },
      {
        title: 'Nga pyetja te fatura për 30 sekonda',
        content: 'Veçoria më e fuqishme e asistentit AI është aftësia për të krijuar dokumente direkt nga një komandë teksti. Në vend të navigimit nëpër meny, plotësimit të formave dhe kërkimit të klientëve — thjesht tregojini AI çfarë dëshironi.',
        items: [
          'Thoni: „Krijo faturë për Kompaninë X për 30,000 denarë" — AI gjen klientin, plotëson shumën dhe TVSH',
          'AI krijon një draft (AiDraft) — pamje paraprake e të gjitha fushave para konfirmimit',
          'Shqyrtoni dhe rregulloni nëse nevojitet — keni kontroll të plotë',
          'Një klik në „Konfirmo" — fatura është krijuar dhe gati për dërgim',
          'I gjithë procesi zgjat më pak se 30 sekonda — krahasuar me 3-5 minuta manualisht',
        ],
        steps: null,
      },
      {
        title: 'Siguria dhe privatësia',
        content: 'E dimë që të dhënat financiare janë të ndjeshme. Prandaj asistenti AI i Facturino është dizajnuar me sigurinë në radhë të parë.',
        items: [
          'AI nuk i ruan bisedat tuaja — çdo sesion është i pavarur dhe i përkohshëm',
          'Të gjitha të dhënat janë të enkriptuara gjatë transmetimit dhe ruajtjes (TLS + AES-256)',
          'Të dhënat tuaja financiare nuk ndahen kurrë me palë të treta',
          'AI qaset vetëm tek të dhënat e kompanisë suaj — izolim i plotë midis përdoruesve',
          'Serverët janë në Europë, në përputhje me standardet GDPR të mbrojtjes së të dhënave',
        ],
        steps: null,
      },
      {
        title: 'Provoni asistentin AI',
        content: 'Asistenti AI është i disponueshëm në planin Standard dhe më lart. Nëse ende nuk keni llogari Facturino, regjistrohuni falas dhe përmirësoni në Standard për të pasur qasje në veçoritë AI. Me periudhën provuese 14-ditore në planin Standard, mund ta testoni asistentin AI pa angazhim.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Kontabiliteti për fillestarë: Ku të filloni' },
      { slug: 'ddv-vodich-mk', title: 'Udhëzues TVSH për bizneset maqedonase' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
    ],
    cta: {
      title: 'Pyetni AI — Përgjigja është 30 sekonda larg',
      desc: 'Regjistrohuni dhe zbuloni kush ju ka borxh, a jeni fitimprurës, dhe krijoni faturë vetëm me një fjali.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Ürün',
    title: 'AI\'ya Sorun: Kim Bana Borçlu? Kârlı mıyım?',
    publishDate: '15 Mart 2026',
    readTime: '8 dk okuma',
    intro: 'Finanslarınızı anlamak için muhasebe diplomasına ihtiyacınız yok. Facturino\'nun AI asistanı ile düz bir dille sorular sorun ve anında yanıtlar alın. Müşterileriniz size ne kadar borçlu? Bu ay kârlı mısınız? Ne kadar KDV borcunuz var? Sadece sorun — AI saniyeler içinde yanıtlayacak.',
    sections: [
      {
        title: 'Sorabileceğiniz 5 soru',
        content: 'Facturino\'nun AI asistanı doğal dili anlar. SQL, formül veya kısayol bilmenize gerek yok — bir meslektaşınıza sorar gibi sorun. İşte 5 gerçek örnek:',
        items: null,
        steps: [
          { step: '"Bana en çok kim borçlu?"', desc: 'AI tüm ödenmemiş faturaları analiz eder ve size tutara göre sıralanmış borçlu listesi gösterir. Kimin size borçlu olduğunu, kaç dinar olduğunu ve faturanın kaç gün geciktiğini görürsünüz. Manuel arama yapmadan nakit akışını takip etmek için mükemmel.' },
          { step: '"Bu çeyrek ne kadar KDV\'m var?"', desc: 'Mevcut çeyrek için giriş ve çıkış KDV\'sinin anında hesaplanması. AI tüm faturaları ve giderleri toplar, farkı hesaplar ve size net bir yanıt verir — UJP\'ye KDV beyannameniz için hazır.' },
          { step: '"Bu ay kârlı mıyım?"', desc: 'AI, mevcut ay için kısa bir gelir ve gider raporu (P&L) oluşturur. Toplam geliri, toplam giderleri ve net kâr veya zararı görürsünüz — karmaşık raporlar açmadan hepsi tek cümlede.' },
          { step: '"Market DOOEL için 50.000 den fatura oluştur"', desc: 'AI sadece soruları yanıtlamaz — belge de oluşturabilir. Bu komutla AI, müşteri verileri, tutar ve KDV önceden doldurulmuş bir taslak fatura otomatik olarak oluşturur. Siz sadece onaylayın ve gönderin.' },
          { step: '"Vadesi geçmiş tüm faturaları göster"', desc: 'Ödeme süresini aşmış tüm faturaların anında görünümü. AI bunları yaşa ve tutara göre sıralar, böylece hangi müşterilere hatırlatma gerektiğini tam olarak bilirsiniz. Manuel filtreleme yerine zamandan tasarruf edin.' },
        ],
      },
      {
        title: 'AI asistanı nasıl çalışır',
        content: 'Facturino\'nun AI asistanı doğrudan uygulamaya entegre edilmiştir. Ayrı kurulum, eklenti veya teknik bilgi gerekmez. İşte adım adım süreç:',
        items: null,
        steps: [
          { step: 'Sohbet penceresini açın', desc: 'Facturino\'nun sağ alt köşesinde bir sohbet simgesi göreceksiniz. AI asistanını açmak için tıklayın. Uygulamanın her sayfasından erişilebilir — Kontrol Paneli, faturalar, giderler veya raporlar.' },
          { step: 'Kendi dilinizde soru sorun', desc: 'Sorunuzu düz bir dille yazın. AI bağlamı anlar ve doğal dil işleme (NLP) modelleri aracılığıyla işler. Özel sözdizimi veya komut gerekmez — bir mesaj yazar gibi yazın.' },
          { step: 'AI veritabanınızı arar', desc: 'Sistem finansal verilerinizi güvenli bir şekilde sorgular — faturalar, giderler, müşteriler, ödemeler — ve sorunuzu yanıtlamak için analiz eder. Her şey gerçek zamanlı gerçekleşir, bekleme yok.' },
          { step: 'Biçimlendirilmiş yanıt alırsınız', desc: 'AI size sayılar, tablolar veya listeler içeren net, biçimlendirilmiş bir yanıt döndürür. Soru bir komutsa (örn. "fatura oluştur"), AI bir AiDraft — gerçek belge olmadan önce incelediğiniz ve onayladığınız bir taslak — oluşturur.' },
        ],
      },
      {
        title: 'Sorudan faturaya 30 saniyede',
        content: 'AI asistanının en güçlü özelliği, doğrudan bir metin komutundan belge oluşturma yeteneğidir. Menülerde gezinmek, formları doldurmak ve müşteri aramak yerine — AI\'ya ne istediğinizi söyleyin.',
        items: [
          'Söyleyin: "Şirket X için 30.000 dinar fatura oluştur" — AI müşteriyi bulur, tutarı ve KDV\'yi doldurur',
          'AI bir taslak (AiDraft) oluşturur — onaydan önce tüm alanların ön izlemesi',
          'Gerekirse inceleyin ve düzeltin — tam kontrole sahipsiniz',
          '"Onayla" butonuna bir tıklama — fatura oluşturuldu ve gönderime hazır',
          'Tüm süreç 30 saniyeden az sürer — manuel olarak 3-5 dakikayla karşılaştırıldığında',
        ],
        steps: null,
      },
      {
        title: 'Güvenlik ve gizlilik',
        content: 'Finansal verilerin hassas olduğunu biliyoruz. Bu yüzden Facturino\'nun AI asistanı güvenlik öncelikli olarak tasarlanmıştır.',
        items: [
          'AI konuşmalarınızı kaydetmez — her oturum bağımsız ve geçicidir',
          'Tüm veriler aktarım ve depolama sırasında şifrelenir (TLS + AES-256)',
          'Finansal verileriniz asla üçüncü taraflarla paylaşılmaz',
          'AI yalnızca şirketinizin verilerine erişir — kullanıcılar arasında tam izolasyon',
          'Sunucular Avrupa\'dadır, GDPR veri koruma standartlarına uygundur',
        ],
        steps: null,
      },
      {
        title: 'AI asistanını deneyin',
        content: 'AI asistanı Standard plan ve üzerinde kullanılabilir. Henüz bir Facturino hesabınız yoksa, ücretsiz kaydolun ve AI özelliklerine erişmek için Standard\'a yükseltin. Standard plandaki 14 günlük deneme süresiyle AI asistanını taahhüt olmadan test edebilirsiniz.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Yeni başlayanlar için muhasebe: Nereden başlamalı' },
      { slug: 'ddv-vodich-mk', title: 'Makedon işletmeler için KDV rehberi' },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
    ],
    cta: {
      title: 'AI\'ya sorun — Yanıt 30 saniye uzakta',
      desc: 'Kaydolun ve kimin size borçlu olduğunu, kârlı olup olmadığınızı öğrenin ve tek cümleyle fatura oluşturun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function AiAsistentSmetkovodstvoPage({
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

      {/* HERO IMAGE */}
      <div className="container max-w-3xl mx-auto px-4 sm:px-6 mb-8">
        <div className="rounded-2xl overflow-hidden shadow-lg">
          <img src="/assets/images/blog/blog_ai_assistant.png" alt="AI асистент за сметководство Facturino - вештачка интелигенција за финансии" className="w-full h-auto" />
        </div>
      </div>

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
