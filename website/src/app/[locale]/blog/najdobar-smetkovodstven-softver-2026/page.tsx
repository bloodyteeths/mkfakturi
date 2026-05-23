import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/najdobar-smetkovodstven-softver-2026', {
    title: {
      mk: '7 сметководствени софтвери за Северна Македонија 2026 — Facturino',
      en: '7 Accounting Software for North Macedonia 2026 — Facturino',
      sq: '7 softuer kontabiliteti per Maqedonine e Veriut 2026 — Facturino',
      tr: 'Kuzey Makedonya 2026 icin 7 Muhasebe Yazilimi — Facturino',
    },
    description: {
      mk: 'Објективна споредба на 7 сметководствени софтвери достапни во Македонија: PANTHEON, Facturino, Accent, Helix, Excel и повеќе. Најдете го вистинскиот за вашиот бизнис.',
      en: 'Objective comparison of 7 accounting software solutions available in North Macedonia: PANTHEON, Facturino, Accent, Helix, Excel and more. Find the right fit for your business.',
      sq: 'Krahasim objektiv i 7 softuereve te kontabilitetit te disponueshem ne Maqedoni: PANTHEON, Facturino, Accent, Helix, Excel e me shume. Gjeni zgjidhjen e duhur per biznesin tuaj.',
      tr: 'Kuzey Makedonya\'da mevcut 7 muhasebe yaziliminin objektif karsilastirmasi: PANTHEON, Facturino, Accent, Helix, Excel ve daha fazlasi. Isletmeniz icin dogru olanini bulun.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Споредба',
    title: '7 сметководствени софтвери за Северна Македонија 2026',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro: 'Македонија има ограничен избор на сметководствен софтвер — повеќето бизниси користат Excel, PANTHEON или хартиено сметководство. Овој водич ги споредува 7-те најчести решенија објективно — со предности, недостатоци и препорака за кого е најдобар секој од нив.',
    sections: [
      {
        title: '1. PANTHEON (Datalab) — доминантен ERP',
        content: 'PANTHEON е најраспространетиот бизнис софтвер во Македонија, производ на словенечката компанија Datalab. Полн е ERP систем кој покрива сметководство, магацинско работење, производство и човечки ресурси. Најдобар за: производствени компании, велепродажба и фирми со 10+ вработени.',
        items: [
          'Desktop + client-server архитектура — претежно on-premise инсталација',
          'Полн ERP: магацин, производство, HR, сметководство',
          'Цена од 1.500+ MKD/месечно по корисник',
          'Стрмна крива на учење — потребна е обука',
          'Силна локална мрежа на поддршка и имплементатори',
          'Ограничен облак пристап — претежно десктоп работење',
          'Најдобар за големи компании со сложени потреби',
        ],
        steps: null,
      },
      {
        title: '2. Facturino — облак софтвер за МК бизниси',
        content: 'Facturino е модерен, облак-базиран софтвер создаден специјално за македонскиот пазар. Нуди фактурирање, сметководство, плати, POS и банкарско порамнување во една платформа. Најдобар за: мали и средни бизниси, фрилансери и сметководители со портфолио.',
        items: [
          'Облак/SaaS — пристап од било каде, на било кој уред',
          'Бесплатен план на располагање (до 3 фактури/месечно)',
          'е-Фактура UBL подготвеност за задолжителната обврска од октомври 2026',
          'Банкарски увоз (CSV/MT940/PDF) со AI порамнување',
          'AI скенер на документи — автоматско читање на фактури',
          'POS со поддршка за фискален печатач',
          'Плати модул со МПИН образец за УЈП',
          'Мобилно прилагоден интерфејс',
        ],
        steps: null,
      },
      {
        title: '3. Accent — традиционално МК сметководство',
        content: 'Accent е домашен македонски софтвер кој е популарен меѓу традиционалните сметководствени канцеларии. Нуди солидно книговодство, но со постар интерфејс и ограничени модерни функции. Најдобар за: сметководители навикнати на десктоп софтвер.',
        items: [
          'Desktop софтвер — локална инсталација',
          'Силен во класично книговодство и двојно книжење',
          'Постар интерфејс — Windows XP/7 стил',
          'Лиценца по корисник (еднократна купопродажба + годишно одржување)',
          'Локална МК поддршка на македонски',
          'Нема облак пристап ни мобилна верзија',
        ],
        steps: null,
      },
      {
        title: '4. Helix — уште една МК опција',
        content: 'Helix е Windows-базиран софтвер кој покрива основно фактурирање и книговодство. Популарен меѓу некои помали сметководствени канцеларии. Најдобар за: многу мали бизниси со основни потреби.',
        items: [
          'Windows-базиран десктоп софтвер',
          'Покрива основно фактурирање и книговодство',
          'Ограничена автоматизација',
          'Локална поддршка на македонски',
          'Нема е-Фактура поддршка',
          'Нема облак ни мобилен пристап',
        ],
        steps: null,
      },
      {
        title: '5. Excel / Google Sheets — DIY пристап',
        content: 'Excel и Google Sheets се најчесто користениот „софтвер“ за сметководство во Македонија — особено меѓу нови бизниси и фрилансери. Флексибилен но со сериозни ограничувања. Најдобар за: соло фрилансери со <5 фактури/месечно.',
        items: [
          'Бесплатно или ниска цена',
          'Флексибилно — може да се прилагоди на се',
          'Нема проверка на усогласеност со УЈП',
          'Нема е-Фактура поддршка',
          'Се е мануелно — нема автоматизација',
          'Хаос со верзии — нејасно која е последната верзија',
          'Нема ревизорска трага',
        ],
        steps: null,
      },
      {
        title: 'Споредбена табела',
        content: 'Еве како се споредуваат клучните функции кај секој софтвер:',
        items: null,
        steps: [
          { step: 'е-Фактура (UBL)', desc: 'Facturino: Да, вградено. PANTHEON: Да, со модул. Accent: Не. Helix: Не. Excel: Не.' },
          { step: 'Облак пристап', desc: 'Facturino: Да, целосно. PANTHEON: Ограничено (Xtension). Accent: Не. Helix: Не. Excel: Google Sheets — да, но не е сметководство.' },
          { step: 'Бесплатен план', desc: 'Facturino: Да, до 3 фактури/месечно. PANTHEON: Не. Accent: Не. Helix: Не. Excel: Бесплатно (но без функции).' },
          { step: 'Плати / МПИН', desc: 'Facturino: Да, со МПИН образец. PANTHEON: Да, полн HR модул. Accent: Основно. Helix: Не. Excel: Мануелно.' },
          { step: 'Банкарска интеграција', desc: 'Facturino: CSV/MT940/PDF увоз + AI порамнување. PANTHEON: Да, со модул. Accent: Ограничено. Helix: Не. Excel: Мануелно.' },
          { step: 'POS / Фискален', desc: 'Facturino: Да, со фискален печатач. PANTHEON: Да. Accent: Не. Helix: Не. Excel: Не.' },
          { step: 'AI функции', desc: 'Facturino: AI скенер, AI порамнување, AI категоризација. PANTHEON: Не. Accent: Не. Helix: Не. Excel: Не.' },
          { step: 'Мобилна апликација', desc: 'Facturino: Да, web responsive. PANTHEON: Ограничено. Accent: Не. Helix: Не. Excel: Google Sheets мобилно.' },
        ],
      },
      {
        title: 'Како да изберете',
        content: 'Изборот на софтвер зависи од повеќе фактори. Нема едно решение кое одговара на сите — но со задолжителната е-Фактура од октомври 2026, дигитализацијата не е повеќе опција, туку обврска.',
        items: [
          'Големина на бизнисот — за 10+ вработени, PANTHEON е силен; за 1-20, Facturino е оптимален',
          'е-Фактура барање — задолжително B2G од октомври 2026, само Facturino и PANTHEON имаат вградена поддршка',
          'Буџет — Facturino нуди бесплатен план; PANTHEON почнува од 1.500+ MKD/месечно',
          'Повеќекориснички потреби — облак софтверот нуди истовремен пристап за цел тим',
          'Облак vs десктоп — десктоп бара одржување, бекапи, IT поддршка; облакот е секогаш ажуриран',
          'Индустриски функции — производство и магацин бараат PANTHEON; трговија и услуги работат одлично со Facturino',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Зошто табели не се доволни' },
      { slug: 'e-faktura-obvrska-2026', title: 'е-Фактура обврска 2026: Што треба да знаете' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
      { slug: 'facturino-vs-pantheon', title: 'Facturino vs PANTHEON: Што е подобро за мали фирми?' },
      { slug: 'zosto-facturino', title: '10 причини зошто македонски бизниси го избираат Facturino' },
    ],
    cta: {
      title: 'Пробајте го Facturino бесплатно',
      desc: 'Видете дали одговара на вашиот бизнис — без обврска, без кредитна картичка.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Comparison',
    title: '7 Accounting Software for North Macedonia 2026',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro: 'North Macedonia has limited choices for accounting software — most businesses use Excel, PANTHEON, or paper-based bookkeeping. This guide compares the 7 most common solutions objectively — with strengths, weaknesses, and recommendations for who each one is best for.',
    sections: [
      {
        title: '1. PANTHEON (Datalab) — The Dominant ERP',
        content: 'PANTHEON is the most widely used business software in Macedonia, a product of Slovenian company Datalab. It is a full ERP system covering accounting, warehouse management, manufacturing, and human resources. Best for: manufacturing companies, wholesale, and businesses with 10+ employees.',
        items: [
          'Desktop + client-server architecture — primarily on-premise installation',
          'Full ERP: warehouse, manufacturing, HR, accounting',
          'Pricing from 1,500+ MKD/month per user',
          'Steep learning curve — training required',
          'Strong local support network and implementation partners',
          'Limited cloud access — primarily desktop operation',
          'Best for large companies with complex needs',
        ],
        steps: null,
      },
      {
        title: '2. Facturino — Cloud-Native for MK Businesses',
        content: 'Facturino is a modern, cloud-based software built specifically for the Macedonian market. It offers invoicing, accounting, payroll, POS, and bank reconciliation in one platform. Best for: SMEs, freelancers, and accountants managing client portfolios.',
        items: [
          'Cloud/SaaS — access from anywhere, on any device',
          'Free tier available (up to 3 invoices/month)',
          'e-Invoice UBL ready for the mandatory October 2026 requirement',
          'Bank feed import (CSV/MT940/PDF) with AI reconciliation',
          'AI document scanner — automatic invoice reading',
          'POS with fiscal printer support',
          'Payroll module with MPIN form for UJP',
          'Mobile-friendly responsive interface',
        ],
        steps: null,
      },
      {
        title: '3. Accent — Traditional MK Accounting',
        content: 'Accent is a domestic Macedonian software popular among traditional accounting offices. It offers solid bookkeeping but with an older interface and limited modern features. Best for: accountants comfortable with traditional desktop software.',
        items: [
          'Desktop software — local installation',
          'Strong in classic bookkeeping and double-entry',
          'Older interface — Windows XP/7 style',
          'Per-user license (one-time purchase + annual maintenance)',
          'Local MK support in Macedonian',
          'No cloud access or mobile version',
        ],
        steps: null,
      },
      {
        title: '4. Helix — Another MK Option',
        content: 'Helix is a Windows-based software covering basic invoicing and bookkeeping. Popular among some smaller accounting offices. Best for: very small businesses with basic needs.',
        items: [
          'Windows-based desktop software',
          'Covers basic invoicing and bookkeeping',
          'Limited automation',
          'Local support in Macedonian',
          'No e-Invoice support',
          'No cloud or mobile access',
        ],
        steps: null,
      },
      {
        title: '5. Excel / Google Sheets — The DIY Approach',
        content: 'Excel and Google Sheets are the most commonly used "software" for accounting in Macedonia — especially among new businesses and freelancers. Flexible but with serious limitations. Best for: solo freelancers with <5 invoices/month.',
        items: [
          'Free or low cost',
          'Flexible — can be adapted to anything',
          'No UJP compliance checking',
          'No e-Invoice support',
          'Everything is manual — no automation',
          'Version chaos — unclear which is the latest version',
          'No audit trail',
        ],
        steps: null,
      },
      {
        title: 'Comparison Table',
        content: 'Here is how key features compare across each software:',
        items: null,
        steps: [
          { step: 'e-Invoice (UBL)', desc: 'Facturino: Yes, built-in. PANTHEON: Yes, with module. Accent: No. Helix: No. Excel: No.' },
          { step: 'Cloud Access', desc: 'Facturino: Yes, fully. PANTHEON: Limited (Xtension). Accent: No. Helix: No. Excel: Google Sheets — yes, but it is not accounting.' },
          { step: 'Free Tier', desc: 'Facturino: Yes, up to 3 invoices/month. PANTHEON: No. Accent: No. Helix: No. Excel: Free (but no features).' },
          { step: 'Payroll / MPIN', desc: 'Facturino: Yes, with MPIN form. PANTHEON: Yes, full HR module. Accent: Basic. Helix: No. Excel: Manual.' },
          { step: 'Bank Integration', desc: 'Facturino: CSV/MT940/PDF import + AI reconciliation. PANTHEON: Yes, with module. Accent: Limited. Helix: No. Excel: Manual.' },
          { step: 'POS / Fiscal', desc: 'Facturino: Yes, with fiscal printer. PANTHEON: Yes. Accent: No. Helix: No. Excel: No.' },
          { step: 'AI Features', desc: 'Facturino: AI scanner, AI reconciliation, AI categorization. PANTHEON: No. Accent: No. Helix: No. Excel: No.' },
          { step: 'Mobile App', desc: 'Facturino: Yes, web responsive. PANTHEON: Limited. Accent: No. Helix: No. Excel: Google Sheets mobile.' },
        ],
      },
      {
        title: 'How to Choose',
        content: 'The choice of software depends on several factors. There is no single solution that fits everyone — but with mandatory e-Invoice from October 2026, digitalization is no longer optional, it is an obligation.',
        items: [
          'Business size — for 10+ employees, PANTHEON is strong; for 1-20, Facturino is optimal',
          'e-Invoice requirement — mandatory B2G from October 2026, only Facturino and PANTHEON have built-in support',
          'Budget — Facturino offers a free plan; PANTHEON starts from 1,500+ MKD/month',
          'Multi-user needs — cloud software provides simultaneous access for the whole team',
          'Cloud vs desktop — desktop requires maintenance, backups, IT support; cloud is always up to date',
          'Industry-specific features — manufacturing and warehouse need PANTHEON; retail and services work great with Facturino',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Why Spreadsheets Aren\'t Enough' },
      { slug: 'e-faktura-obvrska-2026', title: 'e-Invoice Obligation 2026: What You Need to Know' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
      { slug: 'facturino-vs-pantheon', title: 'Facturino vs PANTHEON: Which Is Better for Small Businesses?' },
      { slug: 'zosto-facturino', title: '10 Reasons Why Macedonian Businesses Choose Facturino' },
    ],
    cta: {
      title: 'Try Facturino Free',
      desc: 'See if it fits your business — no commitment, no credit card required.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Krahasim',
    title: '7 softuer kontabiliteti per Maqedonine e Veriut 2026',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro: 'Maqedonia e Veriut ka zgjedhje te kufizuara per softuer kontabiliteti — shumica e bizneseve perdorin Excel, PANTHEON ose kontabilitet me leter. Ky udherrефes krahason 7 zgjidhjet me te zakonshme ne menyre objektive — me perparesi, dobesite dhe rekomandimet per kujt i pershtatet me se miri.',
    sections: [
      {
        title: '1. PANTHEON (Datalab) — ERP dominues',
        content: 'PANTHEON eshte softueri me i perdorur i biznesit ne Maqedoni, produkt i kompanise sllovene Datalab. Eshte sistem i plote ERP qe mbulon kontabilitetin, menaxhimin e magazines, prodhimin dhe burimet njerezore. Me i miri per: kompani prodhuese, shumicen e madhe dhe biznese me 10+ punonjes.',
        items: [
          'Arkitekture desktop + klient-server — kryesisht instalim on-premise',
          'ERP i plote: magazine, prodhim, HR, kontabilitet',
          'Cmimi nga 1,500+ MKD/muaj per perdorues',
          'Kurbe e rrепte mesimi — kerkohet trajnim',
          'Rrjet i forte i mbeshtетjes lokale dhe partnere implementimi',
          'Qasje e kufizuar ne cloud — kryesisht pune desktop',
          'Me i miri per kompani te medha me nevoja te nderplikuara',
        ],
        steps: null,
      },
      {
        title: '2. Facturino — Cloud per bizneset e MK',
        content: 'Facturino eshte softuer modern, i bazuar ne cloud, i ndertuar posaçerisht per tregun maqedonas. Ofron faturim, kontabilitet, paga, POS dhe pajtim bankar ne nje platforme. Me i miri per: NVM, freelancer dhe kontabiliste qe menaxhojne portfolio klientesh.',
        items: [
          'Cloud/SaaS — qasje nga kudo, ne çdo pajisje',
          'Plan falas i disponueshem (deri 3 fatura/muaj)',
          'e-Fature UBL gati per detyrimin e tetorit 2026',
          'Import bankar (CSV/MT940/PDF) me pajtim AI',
          'Skaner AI dokumentesh — lexim automatik faturash',
          'POS me mbeshtетje per printer fiskal',
          'Modul pagash me formularin MPIN per UJP',
          'Nderfaqe responsive per celular',
        ],
        steps: null,
      },
      {
        title: '3. Accent — Kontabilitet tradicional MK',
        content: 'Accent eshte softuer vendas maqedonas i popullarizuar midis zyrave tradicionale te kontabilitetit. Ofron kontabilitet solid por me nderfaqe me te vjeter dhe veçori moderne te kufizuara. Me i miri per: kontabiliste te rehatshëm me softuer desktop tradicional.',
        items: [
          'Softuer desktop — instalim lokal',
          'I forte ne kontabilitet klasik dhe regjistrim te dyfishte',
          'Nderfaqe me e vjeter — stil Windows XP/7',
          'Licence per perdorues (blerje njehershme + mirembajtje vjetore)',
          'Mbeshtетje lokale MK ne maqedonisht',
          'Asnje qasje cloud ose version celulari',
        ],
        steps: null,
      },
      {
        title: '4. Helix — Nje opsion tjeter MK',
        content: 'Helix eshte softuer i bazuar ne Windows qe mbulon faturimin dhe kontabilitetin baze. I popullarizuar midis disa zyrave me te vogla te kontabilitetit. Me i miri per: biznese shume te vogla me nevoja bazike.',
        items: [
          'Softuer desktop i bazuar ne Windows',
          'Mbulon faturimin dhe kontabilitetin baze',
          'Automatizim i kufizuar',
          'Mbeshtетje lokale ne maqedonisht',
          'Asnje mbeshtетje e-Fature',
          'Asnje qasje cloud ose celulari',
        ],
        steps: null,
      },
      {
        title: '5. Excel / Google Sheets — Qasja DIY',
        content: 'Excel dhe Google Sheets jane "softueri" me i perdorur per kontabilitet ne Maqedoni — veçanerisht midis bizneseve te reja dhe freelancer-eve. Fleksibel por me kufizime serioze. Me i miri per: freelancer solo me <5 fatura/muaj.',
        items: [
          'Falas ose çmim i ulet',
          'Fleksibel — mund te pershtatет per çdo gje',
          'Asnje kontroll perputhshmerie me UJP',
          'Asnje mbeshtетje e-Fature',
          'Gjithçka manuale — asnje automatizim',
          'Kaos versionesh — e paqarte cili eshte versioni i fundit',
          'Asnje gjurme auditimi',
        ],
        steps: null,
      },
      {
        title: 'Tabela e krahasimit',
        content: 'Ja si krahasohen veçorite kyçe per çdo softuer:',
        items: null,
        steps: [
          { step: 'e-Fature (UBL)', desc: 'Facturino: Po, i integruar. PANTHEON: Po, me modul. Accent: Jo. Helix: Jo. Excel: Jo.' },
          { step: 'Qasje cloud', desc: 'Facturino: Po, plotesisht. PANTHEON: E kufizuar (Xtension). Accent: Jo. Helix: Jo. Excel: Google Sheets — po, por nuk eshte kontabilitet.' },
          { step: 'Plan falas', desc: 'Facturino: Po, deri 3 fatura/muaj. PANTHEON: Jo. Accent: Jo. Helix: Jo. Excel: Falas (por pa veçori).' },
          { step: 'Paga / MPIN', desc: 'Facturino: Po, me formularin MPIN. PANTHEON: Po, modul i plote HR. Accent: Bazik. Helix: Jo. Excel: Manual.' },
          { step: 'Integrim bankar', desc: 'Facturino: Import CSV/MT940/PDF + pajtim AI. PANTHEON: Po, me modul. Accent: I kufizuar. Helix: Jo. Excel: Manual.' },
          { step: 'POS / Fiskal', desc: 'Facturino: Po, me printer fiskal. PANTHEON: Po. Accent: Jo. Helix: Jo. Excel: Jo.' },
          { step: 'Veçori AI', desc: 'Facturino: Skaner AI, pajtim AI, kategorizim AI. PANTHEON: Jo. Accent: Jo. Helix: Jo. Excel: Jo.' },
          { step: 'Aplikacion celulari', desc: 'Facturino: Po, web responsive. PANTHEON: I kufizuar. Accent: Jo. Helix: Jo. Excel: Google Sheets celular.' },
        ],
      },
      {
        title: 'Si te zgjidhni',
        content: 'Zgjedhja e softuerit varet nga disa faktore. Nuk ka nje zgjidhje qe i pershtatет te gjitheve — por me e-Faturen e detyrueshme nga tetori 2026, digjitalizimi nuk eshte me opsion, eshte detyrim.',
        items: [
          'Madhesia e biznesit — per 10+ punonjes, PANTHEON eshte i forte; per 1-20, Facturino eshte optimal',
          'Kerkesa per e-Fature — e detyrueshme B2G nga tetori 2026, vetem Facturino dhe PANTHEON kane mbeshtетje te integruar',
          'Buxheti — Facturino ofron plan falas; PANTHEON fillon nga 1,500+ MKD/muaj',
          'Nevoja shumeperdorueshe — softueri cloud siguron qasje te njekohshme per te gjithe ekipin',
          'Cloud vs desktop — desktop kerkon mirembajtje, backup, mbeshtетje IT; cloud eshte gjithmone i perditesuar',
          'Veçori specifike per industrine — prodhimi dhe magazina kerkojne PANTHEON; tregtia dhe sherbimet funksionojne shkelqyeshem me Facturino',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Pse tabelat nuk mjaftojne' },
      { slug: 'e-faktura-obvrska-2026', title: 'Detyrimi per e-Fature 2026: Çfare duhet te dini' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
      { slug: 'facturino-vs-pantheon', title: 'Facturino vs PANTHEON: Cili eshte me i mire per bizneset e vogla?' },
      { slug: 'zosto-facturino', title: '10 arsye pse bizneset maqedonase zgjedhin Facturino' },
    ],
    cta: {
      title: 'Provoni Facturino falas',
      desc: 'Shihni nese i pershtatет biznesit tuaj — pa angazhim, pa karte krediti.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Karsilastirma',
    title: 'Kuzey Makedonya 2026 icin 7 Muhasebe Yazilimi',
    publishDate: '23 Mayis 2026',
    readTime: '10 dk okuma',
    intro: 'Kuzey Makedonya\'da muhasebe yazilimi secenekleri sinirlidir — cogu isletme Excel, PANTHEON veya kagit bazli muhasebe kullanir. Bu rehber, en yaygin 7 cozumu objektif olarak karsilastirir — guclu yanlari, zayif yanlari ve her birinin en uygun oldugu kisiler icin onerilerle.',
    sections: [
      {
        title: '1. PANTHEON (Datalab) — Baskin ERP',
        content: 'PANTHEON, Makedonya\'da en yaygin kullanilan is yazilimidir ve Sloven sirketi Datalab\'in bir urunudur. Muhasebe, depo yonetimi, uretim ve insan kaynaklarini kapsayan tam bir ERP sistemidir. En iyisi: uretim sirketleri, toptan satis ve 10+ calisani olan isletmeler.',
        items: [
          'Desktop + istemci-sunucu mimarisi — oncelikli olarak yerinde kurulum',
          'Tam ERP: depo, uretim, IK, muhasebe',
          'Kullanici basina aylik 1.500+ MKD\'den baslayan fiyat',
          'Dik ogrenme egrisi — egitim gerektirir',
          'Guclu yerel destek agi ve uygulama ortaklari',
          'Sinirli bulut erisimi — oncelikli olarak masaustu calisma',
          'Karmasik ihtiyaclari olan buyuk sirketler icin en iyisi',
        ],
        steps: null,
      },
      {
        title: '2. Facturino — MK Isletmeleri icin Bulut Yazilimi',
        content: 'Facturino, ozellikle Makedon pazari icin olusturulmus modern, bulut tabanli bir yazilimdir. Tek bir platformda faturalama, muhasebe, bordro, POS ve banka mutabakatini sunar. En iyisi: KOBi\'ler, serbest calisanlar ve musteri portfoylerini yoneten muhasebeciler.',
        items: [
          'Bulut/SaaS — her yerden, her cihazdan erisim',
          'Ucretsiz plan mevcut (ayda 3 faturaya kadar)',
          'Ekim 2026 zorunlulugu icin e-Fatura UBL hazir',
          'Banka veri aktarimi (CSV/MT940/PDF) ile AI mutabakati',
          'AI belge tarayici — otomatik fatura okuma',
          'Mali yazici destekli POS',
          'UJP icin MPIN formlu bordro modulu',
          'Mobil uyumlu duyarli arayuz',
        ],
        steps: null,
      },
      {
        title: '3. Accent — Geleneksel MK Muhasebesi',
        content: 'Accent, geleneksel muhasebe buroları arasinda populer olan yerli bir Makedon yazilimidir. Saglam defter tutma sunar ancak eski bir arayuz ve sinirli modern ozelliklerle. En iyisi: geleneksel masaustu yazilima aliskin muhasebeciler.',
        items: [
          'Masaustu yazilimi — yerel kurulum',
          'Klasik defter tutma ve cift kayitta guclu',
          'Eski arayuz — Windows XP/7 stili',
          'Kullanici basina lisans (tek seferlik satin alma + yillik bakim)',
          'Makedoncada yerel MK destegi',
          'Bulut erisimi veya mobil surum yok',
        ],
        steps: null,
      },
      {
        title: '4. Helix — Baska Bir MK Secenegi',
        content: 'Helix, temel faturalama ve defter tutmayi kapsayan Windows tabanli bir yazilimdir. Bazi kucuk muhasebe buroları arasinda populerdir. En iyisi: temel ihtiyaclari olan cok kucuk isletmeler.',
        items: [
          'Windows tabanli masaustu yazilimi',
          'Temel faturalama ve defter tutmayi kapsar',
          'Sinirli otomasyon',
          'Makedoncada yerel destek',
          'e-Fatura destegi yok',
          'Bulut veya mobil erisim yok',
        ],
        steps: null,
      },
      {
        title: '5. Excel / Google Sheets — Kendin Yap Yaklasimi',
        content: 'Excel ve Google Sheets, Makedonya\'da muhasebe icin en yaygin kullanilan "yazilimdir" — ozellikle yeni isletmeler ve serbest calisanlar arasinda. Esnek ama ciddi sinirlamalarla. En iyisi: ayda <5 fatura kesen solo serbest calisanlar.',
        items: [
          'Ucretsiz veya dusuk maliyet',
          'Esnek — her seye uyarlanabilir',
          'UJP uyumluluk kontrolu yok',
          'e-Fatura destegi yok',
          'Her sey manuel — otomasyon yok',
          'Versiyon kaosu — hangisinin en son oldugu belirsiz',
          'Denetim izi yok',
        ],
        steps: null,
      },
      {
        title: 'Karsilastirma Tablosu',
        content: 'Her yazilimda temel ozelliklerin nasil karsilastirildigi:',
        items: null,
        steps: [
          { step: 'e-Fatura (UBL)', desc: 'Facturino: Evet, yerlesik. PANTHEON: Evet, modulle. Accent: Hayir. Helix: Hayir. Excel: Hayir.' },
          { step: 'Bulut Erisimi', desc: 'Facturino: Evet, tamamen. PANTHEON: Sinirli (Xtension). Accent: Hayir. Helix: Hayir. Excel: Google Sheets — evet, ama muhasebe degil.' },
          { step: 'Ucretsiz Plan', desc: 'Facturino: Evet, ayda 3 faturaya kadar. PANTHEON: Hayir. Accent: Hayir. Helix: Hayir. Excel: Ucretsiz (ama ozellik yok).' },
          { step: 'Bordro / MPIN', desc: 'Facturino: Evet, MPIN formuyla. PANTHEON: Evet, tam IK modulu. Accent: Temel. Helix: Hayir. Excel: Manuel.' },
          { step: 'Banka Entegrasyonu', desc: 'Facturino: CSV/MT940/PDF aktarimi + AI mutabakat. PANTHEON: Evet, modulle. Accent: Sinirli. Helix: Hayir. Excel: Manuel.' },
          { step: 'POS / Mali', desc: 'Facturino: Evet, mali yaziciyla. PANTHEON: Evet. Accent: Hayir. Helix: Hayir. Excel: Hayir.' },
          { step: 'AI Ozellikleri', desc: 'Facturino: AI tarayici, AI mutabakat, AI kategorizasyon. PANTHEON: Hayir. Accent: Hayir. Helix: Hayir. Excel: Hayir.' },
          { step: 'Mobil Uygulama', desc: 'Facturino: Evet, web responsive. PANTHEON: Sinirli. Accent: Hayir. Helix: Hayir. Excel: Google Sheets mobil.' },
        ],
      },
      {
        title: 'Nasil Secilir',
        content: 'Yazilim secimi birden fazla faktore baglidir. Herkese uyan tek bir cozum yoktur — ancak Ekim 2026\'dan itibaren zorunlu e-Fatura ile dijitallesme artik bir secenek degil, bir zorunluluktur.',
        items: [
          'Isletme buyuklugu — 10+ calisan icin PANTHEON gucludur; 1-20 icin Facturino optimaldir',
          'e-Fatura gereksinimi — Ekim 2026\'dan itibaren zorunlu B2G, sadece Facturino ve PANTHEON yerlesik destege sahip',
          'Butce — Facturino ucretsiz plan sunar; PANTHEON aylik 1.500+ MKD\'den baslar',
          'Cok kullanicili ihtiyaclar — bulut yazilimi tum ekip icin esitli erisim saglar',
          'Bulut vs masaustu — masaustu bakim, yedekleme, BT destegi gerektirir; bulut her zaman gunceldir',
          'Sektore ozel ozellikler — uretim ve depo PANTHEON gerektirir; perakende ve hizmetler Facturino ile mukemmel calisir',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili makaleler',
    related: [
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Neden tablolar yeterli degil' },
      { slug: 'e-faktura-obvrska-2026', title: 'e-Fatura Zorunlulugu 2026: Bilmeniz Gerekenler' },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
      { slug: 'facturino-vs-pantheon', title: 'Facturino vs PANTHEON: Kucuk Isletmeler Icin Hangisi Daha Iyi?' },
      { slug: 'zosto-facturino', title: 'Makedon isletmelerin Facturino\'yu tercih etmesinin 10 nedeni' },
    ],
    cta: {
      title: 'Facturino\'yu Ucretsiz Deneyin',
      desc: 'Isletmenize uygun olup olmadigini gorun — taahhut yok, kredi karti gerekmiyor.',
      button: 'Ucretsiz basla',
    },
  },
} as const

export default async function NajdobarSmetkovodstvenSoftverPage({
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
    slug: 'najdobar-smetkovodstven-softver-2026',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['accounting software', 'Macedonia', 'PANTHEON', 'Facturino', 'comparison', 'e-Invoice'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/najdobar-smetkovodstven-softver-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кој е најдобар сметководствен софтвер во Македонија?', answer: 'Зависи од потребите. За мали бизниси и фрилансери, Facturino е оптимален — облак, бесплатен план, е-Фактура. За големи производствени компании, PANTHEON нуди подлабок ERP.' },
        { question: 'PANTHEON или Facturino — кој да го изберам?', answer: 'Изберете PANTHEON ако имате 50+ вработени, производство и буџет за 500+ EUR/годишно. Изберете Facturino ако сте мал бизнис, сакате облак пристап и бесплатен почеток.' },
        { question: 'Дали има бесплатен сметководствен софтвер во Македонија?', answer: 'Да, Facturino нуди бесплатен план со до 3 фактури месечно. Идеален за нови бизниси и фрилансери. Платените планови започнуваат од 12 EUR/месечно.' },
      ])) }} />
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
