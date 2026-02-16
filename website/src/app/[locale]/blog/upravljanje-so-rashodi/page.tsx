import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/upravljanje-so-rashodi', {
    title: {
      mk: 'Управување со расходи: 7 совети за мали бизниси — Facturino',
      en: 'Expense Management: 7 Tips for Small Businesses — Facturino',
      sq: 'Menaxhimi i shpenzimeve: 7 këshilla për bizneset e vogla — Facturino',
      tr: 'Gider yönetimi: Küçük işletmeler için 7 ipucu — Facturino',
    },
    description: {
      mk: 'Научете како ефикасно да ги управувате деловните расходи со 7 практични совети — категоризација, следење во реално време, одвојување лични/деловни трошоци и подготовка за даноци.',
      en: 'Learn how to efficiently manage business expenses with 7 practical tips — categorization, real-time tracking, separating personal/business costs, and tax preparation.',
      sq: 'Mësoni si t\'i menaxhoni shpenzimet e biznesit me 7 këshilla praktike — kategorizimi, gjurmimi në kohë reale, ndarja e shpenzimeve personale/biznesore dhe përgatitja tatimore.',
      tr: 'İş giderlerini 7 pratik ipucuyla nasıl verimli yönetebileceğinizi öğrenin — sınıflandırma, gerçek zamanlı takip, kişisel/iş giderlerini ayırma ve vergi hazırlığı.',
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
    title: 'Управување со расходи: 7 совети за мали бизниси',
    publishDate: '13 февруари 2026',
    readTime: '6 мин читање',
    intro:
      'Управувањето со расходите е една од најважните задачи за секој мал бизнис. Лошото следење на трошоците може да доведе до неочекувани даночни обврски, проблеми со ликвидноста и дури до затворање на бизнисот. Во овој напис споделуваме 7 практични совети кои ќе ви помогнат да имате целосна контрола врз вашите деловни расходи.',
    sections: [
      {
        title: 'Зошто управувањето со расходи е критично?',
        content:
          'Многу мали бизниси пропаѓаат не затоа што немаат приходи, туку затоа што не знаат каде им одат парите. Без систематско следење на расходите, лесно може да пропуштите одбитоци при даночниот биланс, да преплатите за непотребни услуги или да останете без готовина во критичен момент. Управувањето со расходи не е само евидентирање — тоа е стратешка активност која директно влијае на профитабилноста на вашиот бизнис.',
        items: null,
        steps: null,
      },
      {
        title: '7 совети за ефикасно управување со расходи',
        content: null,
        items: null,
        steps: [
          {
            step: 'Категоризирајте ги сите расходи',
            desc: 'Поделете ги трошоците во јасни категории: закупнина, комунални трошоци, плати, маркетинг, канцелариски материјал, патни трошоци и слично. Категоризацијата ви помага да видите каде точно одат парите и каде може да заштедите. Користете конзистентни категории секој месец за да можете да ги споредувате трендовите. Со Facturino може автоматски да ги категоризирате расходите при внесување.',
          },
          {
            step: 'Чувајте ги сите фактури и сметки',
            desc: 'Секој расход мора да биде документиран. Скенирајте ги или фотографирајте ги сите фактури, фискални сметки и потврди. Дигиталните копии се валидни за даночни цели во Македонија и многу полесно се пребаруваат. Не чекајте крај на месецот — внесувајте ги расходите веднаш кога ќе настанат. Со Facturino можете да фотографирате фактура и автоматски да се препознае содржината.',
          },
          {
            step: 'Одвојте ги личните од деловните трошоци',
            desc: 'Ова е една од најчестите грешки кај малите бизниси. Мешањето на лични и деловни трошоци создава хаос при даночниот биланс и може да предизвика проблеми при ревизија од УЈП. Отворете посебна деловна сметка и користете посебна картичка за деловни трошоци. Никогаш не плаќајте лични трошоци од деловната сметка.',
          },
          {
            step: 'Следете ги расходите во реално време',
            desc: 'Не чекајте крај на месецот за да ги прегледате трошоците. Следењето во реално време ви овозможува да реагирате веднаш ако трошоците ги надминуваат планираните. Поставете месечни буџети за секоја категорија и добивајте известувања кога се приближувате кон лимитот. Facturino ви нуди преглед на расходите во секое време, директно од вашиот телефон.',
          },
          {
            step: 'Правете месечен преглед',
            desc: 'На крајот на секој месец, одвојте 30 минути за преглед на расходите. Споредете ги со претходниот месец и со буџетот. Побарајте необични или неочекувани трошоци. Проверете дали има дупликати или погрешни книжења. Овој едноставен ритуал може да ви заштеди илјадници денари годишно.',
          },
          {
            step: 'Користете софтвер, не табели',
            desc: 'Excel табелите се подобри од хартија, но далеку заостануваат зад специјализиран софтвер. Софтверот за сметководство автоматски ги категоризира трошоците, генерира извештаи, пресметува ДДВ и ви помага при даночниот биланс. Со Facturino добивате автоматска категоризација, скенирање на фактури, извештаи за расходи по категорија и период, и целосна интеграција со даночните обрасци.',
          },
          {
            step: 'Планирајте ги даноците однапред',
            desc: 'Не чекајте март за да дознаете колку данок должите. Следете ги одбитливите расходи во текот на годината — патни трошоци, репрезентација (до 50% одбитлива), амортизација на опрема, трошоци за обука на вработени. Редовното следење ви помага да ги оптимизирате даночните обврски легално. Facturino автоматски ги пресметува одбитливите расходи и ви покажува проценка на данокот во секое време.',
          },
        ],
      },
      {
        title: 'Последици од лошо управување со расходи',
        content:
          'Кога расходите не се следат систематски, последиците може да бидат сериозни. Даночните органи (УЈП) може да наметнат казни доколку не можете да ги документирате расходите при ревизија. Пропуштените одбитоци значат дека плаќате повеќе данок отколку што треба. Без јасна слика за трошоците, не можете да донесете информирани одлуки за развој на бизнисот.',
        items: [
          'Казни од УЈП за недокументирани расходи — од 500 до 5.000 EUR',
          'Пропуштени даночни одбитоци — просечно 15-20% повеќе данок',
          'Неможност за добивање кредит — банките бараат уредни финансиски извештаи',
          'Проблеми со ликвидноста — неочекувани трошоци без резерви',
          'Лоши деловни одлуки — без податоци нема стратегија',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ви помага со расходите',
        content:
          'Facturino е дизајниран за македонски бизниси и ги разбира локалните барања за сметководство. Автоматски ги категоризира расходите според македонскиот контен план, скенира фактури со OCR технологија и генерира извештаи спремни за УЈП. Со PSD2 банкарска интеграција, расходите од вашата сметка автоматски се внесуваат во системот — без рачно книжење.',
        items: [
          'Автоматска категоризација по македонски контен план',
          'OCR скенирање на фактури — сликај и внеси',
          'PSD2 банкарска интеграција за автоматско внесување',
          'Извештаи за расходи по категорија, период и проект',
          'Пресметка на одбитливи расходи за даночен биланс',
          'Мобилен пристап — следете ги расходите од секаде',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'cash-flow-mk', title: 'Cash Flow: Зошто е позначаен од профитот' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Сметководство за почетници: Основи што секој бизнис ги знае' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
    ],
    cta: {
      title: 'Преземете ја контролата врз вашите расходи',
      desc: 'Со Facturino секој денар е евидентиран. Автоматско категоризирање, скенирање на фактури и извештаи спремни за УЈП.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Tips',
    title: 'Expense Management: 7 Tips for Small Businesses',
    publishDate: 'February 13, 2026',
    readTime: '6 min read',
    intro:
      'Expense management is one of the most critical tasks for any small business. Poor cost tracking can lead to unexpected tax liabilities, liquidity issues, and even business closure. In this article, we share 7 practical tips to help you maintain full control over your business expenses.',
    sections: [
      {
        title: 'Why expense management is critical',
        content:
          'Many small businesses fail not because they lack revenue, but because they do not know where their money goes. Without systematic expense tracking, you can easily miss tax deductions, overpay for unnecessary services, or run out of cash at a critical moment. Expense management is not just record-keeping — it is a strategic activity that directly impacts your business profitability.',
        items: null,
        steps: null,
      },
      {
        title: '7 tips for effective expense management',
        content: null,
        items: null,
        steps: [
          {
            step: 'Categorize all expenses',
            desc: 'Divide costs into clear categories: rent, utilities, salaries, marketing, office supplies, travel expenses, and so on. Categorization helps you see exactly where the money goes and where you can save. Use consistent categories every month to compare trends. With Facturino, you can automatically categorize expenses upon entry.',
          },
          {
            step: 'Keep all invoices and receipts',
            desc: 'Every expense must be documented. Scan or photograph all invoices, fiscal receipts, and confirmations. Digital copies are valid for tax purposes in Macedonia and are much easier to search. Do not wait until the end of the month — enter expenses as soon as they occur. With Facturino, you can photograph an invoice and the content is automatically recognized.',
          },
          {
            step: 'Separate personal and business expenses',
            desc: 'This is one of the most common mistakes among small businesses. Mixing personal and business costs creates chaos during tax filing and can cause problems during a tax audit. Open a separate business account and use a dedicated card for business expenses. Never pay personal expenses from your business account.',
          },
          {
            step: 'Track expenses in real time',
            desc: 'Do not wait until the end of the month to review costs. Real-time tracking allows you to react immediately if expenses exceed planned amounts. Set monthly budgets for each category and receive notifications when you approach the limit. Facturino gives you an expense overview at any time, directly from your phone.',
          },
          {
            step: 'Do a monthly review',
            desc: 'At the end of each month, set aside 30 minutes to review expenses. Compare them with the previous month and with your budget. Look for unusual or unexpected costs. Check for duplicates or incorrect entries. This simple ritual can save you thousands annually.',
          },
          {
            step: 'Use software, not spreadsheets',
            desc: 'Excel spreadsheets are better than paper, but they fall far behind specialized software. Accounting software automatically categorizes costs, generates reports, calculates VAT, and helps with tax returns. With Facturino, you get automatic categorization, invoice scanning, expense reports by category and period, and full integration with tax forms.',
          },
          {
            step: 'Plan for taxes in advance',
            desc: 'Do not wait until March to find out how much tax you owe. Track deductible expenses throughout the year — travel costs, entertainment (up to 50% deductible), equipment depreciation, employee training costs. Regular tracking helps you optimize tax obligations legally. Facturino automatically calculates deductible expenses and shows you a tax estimate at any time.',
          },
        ],
      },
      {
        title: 'Consequences of poor expense management',
        content:
          'When expenses are not tracked systematically, the consequences can be serious. Tax authorities (UJP) may impose penalties if you cannot document expenses during an audit. Missed deductions mean you pay more tax than necessary. Without a clear picture of costs, you cannot make informed decisions about business growth.',
        items: [
          'Penalties from UJP for undocumented expenses — from 500 to 5,000 EUR',
          'Missed tax deductions — on average 15-20% more tax paid',
          'Inability to obtain credit — banks require orderly financial statements',
          'Liquidity problems — unexpected expenses without reserves',
          'Poor business decisions — without data there is no strategy',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps with expenses',
        content:
          'Facturino is designed for Macedonian businesses and understands local accounting requirements. It automatically categorizes expenses according to the Macedonian chart of accounts, scans invoices with OCR technology, and generates reports ready for UJP. With PSD2 bank integration, expenses from your account are automatically entered into the system — no manual bookkeeping needed.',
        items: [
          'Automatic categorization by Macedonian chart of accounts',
          'OCR invoice scanning — snap and enter',
          'PSD2 bank integration for automatic entry',
          'Expense reports by category, period, and project',
          'Deductible expense calculation for tax returns',
          'Mobile access — track expenses from anywhere',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'cash-flow-mk', title: 'Cash Flow: Why It Matters More Than Profit' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Accounting for Beginners: Basics Every Business Should Know' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
    ],
    cta: {
      title: 'Take control of your expenses',
      desc: 'With Facturino every denar is recorded. Automatic categorization, invoice scanning, and reports ready for UJP.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Këshilla',
    title: 'Menaxhimi i shpenzimeve: 7 këshilla për bizneset e vogla',
    publishDate: '13 shkurt 2026',
    readTime: '6 min lexim',
    intro:
      'Menaxhimi i shpenzimeve është një nga detyrat më të rëndësishme për çdo biznes të vogël. Gjurmimi i dobët i kostove mund të çojë në detyrime tatimore të papritura, probleme likuiditeti dhe madje mbyllje të biznesit. Në këtë artikull ndajmë 7 këshilla praktike që do t\'ju ndihmojnë të keni kontroll të plotë mbi shpenzimet e biznesit tuaj.',
    sections: [
      {
        title: 'Pse menaxhimi i shpenzimeve është kritik?',
        content:
          'Shumë biznese të vogla dështojnë jo sepse u mungojnë të ardhurat, por sepse nuk dinë ku shkojnë paratë. Pa gjurmim sistematik të shpenzimeve, lehtë mund të humbisni zbritje tatimore, të paguani tepër për shërbime të panevojshme ose të mbeteni pa para në momentin kritik. Menaxhimi i shpenzimeve nuk është vetëm evidencë — është aktivitet strategjik që ndikon drejtpërdrejt në profitabilitetin e biznesit tuaj.',
        items: null,
        steps: null,
      },
      {
        title: '7 këshilla për menaxhim efektiv të shpenzimeve',
        content: null,
        items: null,
        steps: [
          {
            step: 'Kategorizoni të gjitha shpenzimet',
            desc: 'Ndani kostot në kategori të qarta: qira, shërbime komunale, paga, marketing, materiale zyre, shpenzime udhëtimi etj. Kategorizimi ju ndihmon të shihni saktësisht ku shkojnë paratë dhe ku mund të kurseni. Përdorni kategori konzistente çdo muaj për të krahasuar trendet. Me Facturino mund t\'i kategorizoni automatikisht shpenzimet gjatë regjistrimit.',
          },
          {
            step: 'Ruani të gjitha faturat dhe dëftesat',
            desc: 'Çdo shpenzim duhet të jetë i dokumentuar. Skanoni ose fotografoni të gjitha faturat, dëftesat fiskale dhe konfirmimet. Kopjet dixhitale janë të vlefshme për qëllime tatimore në Maqedoni dhe janë shumë më të lehta për kërkim. Mos prisni fundin e muajit — regjistroni shpenzimet sapo të ndodhin. Me Facturino mund të fotografoni një faturë dhe përmbajtja njihet automatikisht.',
          },
          {
            step: 'Ndani shpenzimet personale nga ato të biznesit',
            desc: 'Kjo është një nga gabimet më të zakonshme tek bizneset e vogla. Përzierja e shpenzimeve personale dhe të biznesit krijon kaos gjatë deklarimit tatimor dhe mund të shkaktojë probleme gjatë kontrollit tatimor. Hapni llogari biznesi të veçantë dhe përdorni kartë të dedikuar. Asnjëherë mos paguani shpenzime personale nga llogaria e biznesit.',
          },
          {
            step: 'Gjurmoni shpenzimet në kohë reale',
            desc: 'Mos prisni fundin e muajit për të rishikuar kostot. Gjurmimi në kohë reale ju lejon të reagoni menjëherë nëse shpenzimet tejkalojnë planet. Vendosni buxhete mujore për çdo kategori dhe merrni njoftime kur i afroheni limitit. Facturino ju ofron pamje të shpenzimeve në çdo kohë, direkt nga telefoni.',
          },
          {
            step: 'Bëni rishikim mujor',
            desc: 'Në fund të çdo muaji, ndani 30 minuta për rishikimin e shpenzimeve. Krahasoni me muajin e kaluar dhe me buxhetin. Kërkoni kosto të pazakonshme ose të papritura. Kontrolloni për dublikate ose regjistrime të gabuara. Ky ritual i thjeshtë mund t\'ju kursejë mijëra çdo vit.',
          },
          {
            step: 'Përdorni softuer, jo tabela',
            desc: 'Tabelat Excel janë më të mira se letra, por mbeten larg pas softuerit të specializuar. Softueri i kontabilitetit automatikisht i kategorizon kostot, gjeneron raporte, llogarit TVSH-në dhe ndihmon me deklaratat tatimore. Me Facturino merrni kategorizim automatik, skanim faturash, raporte shpenzimesh sipas kategorisë dhe periudhës, dhe integrim të plotë me format tatimore.',
          },
          {
            step: 'Planifikoni tatimet paraprakisht',
            desc: 'Mos prisni marsin për të mësuar sa tatim keni borxh. Gjurmoni shpenzimet e zbritshme gjatë vitit — shpenzime udhëtimi, përfaqësim (deri 50% e zbritshme), amortizim pajisjesh, shpenzime trajnimi. Gjurmimi i rregullt ju ndihmon të optimizoni detyrimet tatimore ligjërisht. Facturino automatikisht llogarit shpenzimet e zbritshme dhe ju tregon vlerësim tatimi në çdo kohë.',
          },
        ],
      },
      {
        title: 'Pasojat e menaxhimit të dobët të shpenzimeve',
        content:
          'Kur shpenzimet nuk gjurmohen sistematikisht, pasojat mund të jenë serioze. Autoritetet tatimore (UJP) mund të vendosin gjoba nëse nuk mund t\'i dokumentoni shpenzimet gjatë kontrollit. Zbritjet e humbura nënkuptojnë që paguani më shumë tatim se ç\'duhet. Pa pamje të qartë të kostove, nuk mund të merrni vendime të informuara për rritjen e biznesit.',
        items: [
          'Gjoba nga UJP për shpenzime të padokumentuara — nga 500 deri 5,000 EUR',
          'Zbritje tatimore të humbura — mesatarisht 15-20% më shumë tatim',
          'Pamundësi për marrje krediti — bankat kërkojnë pasqyra financiare të rregullta',
          'Probleme likuiditeti — shpenzime të papritura pa rezerva',
          'Vendime të dobëta biznesi — pa të dhëna nuk ka strategji',
        ],
        steps: null,
      },
      {
        title: 'Si ju ndihmon Facturino me shpenzimet',
        content:
          'Facturino është projektuar për bizneset maqedonase dhe i kupton kërkesat lokale të kontabilitetit. Automatikisht i kategorizon shpenzimet sipas planit kontabël maqedonas, skanon faturat me teknologji OCR dhe gjeneron raporte gati për UJP. Me integrimin bankar PSD2, shpenzimet nga llogaria juaj regjistrohen automatikisht — pa kontabilitet manual.',
        items: [
          'Kategorizim automatik sipas planit kontabël maqedonas',
          'Skanim faturash OCR — fotografo dhe regjistro',
          'Integrim bankar PSD2 për regjistrim automatik',
          'Raporte shpenzimesh sipas kategorisë, periudhës dhe projektit',
          'Llogaritje e shpenzimeve të zbritshme për deklaratë tatimore',
          'Qasje mobile — gjurmoni shpenzimet nga kudo',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'cash-flow-mk', title: 'Cash Flow: Pse është më i rëndësishëm se fitimi' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Kontabiliteti për fillestarë: Bazat që çdo biznes i njeh' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
    ],
    cta: {
      title: 'Merrni kontrollin e shpenzimeve tuaja',
      desc: 'Me Facturino çdo denar është i regjistruar. Kategorizim automatik, skanim faturash dhe raporte gati për UJP.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'İpuçları',
    title: 'Gider yönetimi: Küçük işletmeler için 7 ipucu',
    publishDate: '13 Şubat 2026',
    readTime: '6 dk okuma',
    intro:
      'Gider yönetimi, her küçük işletme için en kritik görevlerden biridir. Kötü maliyet takibi, beklenmeyen vergi yükümlülüklerine, likidite sorunlarına ve hatta işletmenin kapanmasına yol açabilir. Bu makalede, iş giderleriniz üzerinde tam kontrol sahibi olmanıza yardımcı olacak 7 pratik ipucu paylaşıyoruz.',
    sections: [
      {
        title: 'Gider yönetimi neden kritiktir?',
        content:
          'Birçok küçük işletme gelir eksikliğinden değil, paralarının nereye gittiğini bilmedikleri için başarısız olur. Sistematik gider takibi olmadan, vergi indirimlerini kolayca kaçırabilir, gereksiz hizmetler için fazla ödeme yapabilir veya kritik bir anda nakit sıkıntısına düşebilirsiniz. Gider yönetimi sadece kayıt tutma değildir — işletmenizin kârlılığını doğrudan etkileyen stratejik bir faaliyettir.',
        items: null,
        steps: null,
      },
      {
        title: 'Etkili gider yönetimi için 7 ipucu',
        content: null,
        items: null,
        steps: [
          {
            step: 'Tüm giderleri sınıflandırın',
            desc: 'Maliyetleri net kategorilere ayırın: kira, faturalar, maaşlar, pazarlama, ofis malzemeleri, seyahat giderleri vb. Sınıflandırma, paranın tam olarak nereye gittiğini ve nerede tasarruf edebileceğinizi görmenize yardımcı olur. Trendleri karşılaştırabilmek için her ay tutarlı kategoriler kullanın. Facturino ile giderleri giriş sırasında otomatik olarak sınıflandırabilirsiniz.',
          },
          {
            step: 'Tüm faturaları ve makbuzları saklayın',
            desc: 'Her gider belgelenmelidir. Tüm faturaları, mali makbuzları ve onayları tarayın veya fotoğraflayın. Dijital kopyalar Makedonya\'da vergi amaçları için geçerlidir ve aranması çok daha kolaydır. Ay sonunu beklemeyin — giderleri oluştukça kaydedin. Facturino ile bir faturayı fotoğraflayabilir ve içerik otomatik olarak tanınır.',
          },
          {
            step: 'Kişisel ve iş giderlerini ayırın',
            desc: 'Bu, küçük işletmelerdeki en yaygın hatalardan biridir. Kişisel ve iş giderlerini karıştırmak vergi beyanında kaos yaratır ve vergi denetiminde sorunlara yol açabilir. Ayrı bir iş hesabı açın ve iş giderleri için özel bir kart kullanın. Kişisel giderleri asla iş hesabından ödemeyin.',
          },
          {
            step: 'Giderleri gerçek zamanlı takip edin',
            desc: 'Maliyetleri gözden geçirmek için ay sonunu beklemeyin. Gerçek zamanlı takip, giderler planlanan tutarları aştığında hemen tepki vermenizi sağlar. Her kategori için aylık bütçeler belirleyin ve limite yaklaştığınızda bildirim alın. Facturino, telefonunuzdan her an gider görünümü sunar.',
          },
          {
            step: 'Aylık inceleme yapın',
            desc: 'Her ayın sonunda giderleri gözden geçirmek için 30 dakika ayırın. Önceki ay ve bütçeyle karşılaştırın. Olağandışı veya beklenmeyen maliyetleri arayın. Tekrarlar veya hatalı girişler olup olmadığını kontrol edin. Bu basit ritüel yılda binlerce lira tasarruf ettirebilir.',
          },
          {
            step: 'Elektronik tablo değil, yazılım kullanın',
            desc: 'Excel tabloları kâğıttan iyidir ancak özel yazılımların çok gerisinde kalır. Muhasebe yazılımı maliyetleri otomatik olarak sınıflandırır, raporlar oluşturur, KDV hesaplar ve vergi beyanlarında yardımcı olur. Facturino ile otomatik sınıflandırma, fatura tarama, kategoriye ve döneme göre gider raporları ve vergi formlarıyla tam entegrasyon elde edersiniz.',
          },
          {
            step: 'Vergileri önceden planlayın',
            desc: 'Ne kadar vergi borçlu olduğunuzu öğrenmek için Mart ayını beklemeyin. Yıl boyunca indirilebilir giderleri takip edin — seyahat masrafları, temsil giderleri (en fazla %50 indirilebilir), ekipman amortismanı, çalışan eğitim masrafları. Düzenli takip, vergi yükümlülüklerini yasal olarak optimize etmenize yardımcı olur. Facturino indirilebilir giderleri otomatik hesaplar ve her an vergi tahmini gösterir.',
          },
        ],
      },
      {
        title: 'Kötü gider yönetiminin sonuçları',
        content:
          'Giderler sistematik olarak takip edilmediğinde sonuçlar ciddi olabilir. Vergi makamları (UJP), denetim sırasında giderleri belgeleyemezseniz ceza uygulayabilir. Kaçırılan indirimler, olması gerekenden daha fazla vergi ödediğiniz anlamına gelir. Maliyetlerin net bir resmini görmeden, işletme büyümesi hakkında bilinçli kararlar veremezsiniz.',
        items: [
          'UJP\'den belgesiz giderler için cezalar — 500 ile 5.000 EUR arası',
          'Kaçırılan vergi indirimleri — ortalama %15-20 daha fazla vergi',
          'Kredi alma imkânsızlığı — bankalar düzenli mali tablolar ister',
          'Likidite sorunları — rezervsiz beklenmeyen giderler',
          'Kötü iş kararları — veri olmadan strateji olmaz',
        ],
        steps: null,
      },
      {
        title: 'Facturino giderlerle nasıl yardımcı olur',
        content:
          'Facturino, Makedon işletmeleri için tasarlanmıştır ve yerel muhasebe gereksinimlerini anlar. Giderleri Makedon hesap planına göre otomatik sınıflandırır, OCR teknolojisiyle faturaları tarar ve UJP\'ye hazır raporlar oluşturur. PSD2 banka entegrasyonuyla, hesabınızdaki giderler otomatik olarak sisteme kaydedilir — manuel muhasebe gerekmez.',
        items: [
          'Makedon hesap planına göre otomatik sınıflandırma',
          'OCR fatura tarama — fotoğrafla ve kaydet',
          'Otomatik giriş için PSD2 banka entegrasyonu',
          'Kategoriye, döneme ve projeye göre gider raporları',
          'Vergi beyanı için indirilebilir gider hesaplama',
          'Mobil erişim — giderleri her yerden takip edin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'cash-flow-mk', title: 'Nakit akışı: Neden kârdan daha önemli' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Yeni başlayanlar için muhasebe: Her işletmenin bilmesi gerekenler' },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
    ],
    cta: {
      title: 'Giderlerinizin kontrolünü elinize alın',
      desc: 'Facturino ile her denar kayıt altında. Otomatik sınıflandırma, fatura tarama ve UJP\'ye hazır raporlar.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function UpravuvanjeSoRashodiPage({
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
