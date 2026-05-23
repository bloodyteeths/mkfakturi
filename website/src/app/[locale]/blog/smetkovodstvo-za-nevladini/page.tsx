import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-nevladini', {
    title: {
      mk: 'Сметководство за невладини и здруженија: Водич 2026',
      en: 'NGO & Association Accounting in North Macedonia: 2026 Guide',
      sq: 'Kontabiliteti për OJQ dhe shoqata: Udhëzues 2026',
      tr: 'STK ve Dernekler İçin Muhasebe: 2026 Rehberi',
    },
    description: {
      mk: 'Комплетен водич за сметководство на невладини организации, здруженија и фондации во Македонија — грантови, даночен третман, годишни извештаи до ЦРСМ и усогласеност со донатори.',
      en: 'Complete guide to accounting for NGOs, associations, and foundations in North Macedonia — grants, tax treatment, annual filing with CRMS, and donor compliance.',
      sq: 'Udhëzues i plotë për kontabilitetin e OJQ-ve, shoqatave dhe fondacioneve në Maqedoni — grante, trajtimi tatimor, raportimi vjetor në QRMK dhe pajtueshmëria me donatorët.',
      tr: 'Kuzey Makedonya\'da STK, dernek ve vakıflar için muhasebe rehberi — hibeler, vergi muamelesi, CRMS yıllık dosyalama ve bağışçı uyumu.',
    },
    datePublished: '2026-05-23',
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Сметководство за невладини и здруженија: Водич 2026',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro:
      'Невладините организации (НВО), здруженијата на граѓани и фондациите во Македонија имаат специфични сметководствени правила кои се разликуваат од комерцијалните компании. Тие не плаќаат данок на добивка на грант-приходите, но мора да ги следат проектните средства одделно, да известуваат до донаторите и да поднесуваат годишни сметки до ЦРСМ. Овој водич ги покрива сите специфики за правилно книговодство на невладиниот сектор во 2026.',
    sections: [
      {
        title: 'Правна рамка',
        content:
          'Невладините организации работат во специфична правна рамка која се разликува од трговските друштва:',
        items: [
          'Регулирани со Закон за здруженија и фондации (2010, изменет 2019)',
          'Се регистрираат во ЦРСМ (Централен регистар на Република Северна Македонија)',
          'Немаат акционери — управувани се од собрание + управен одбор',
          'Достапен статус на организација од јавен интерес (обезбедува даночни поволности)',
          'Мора да ги одвојат програмските/проектните средства од оперативните',
          'Задолжително годишно собрание на членови',
          'Статутарни промени се пријавуваат до ЦРСМ во рок од 30 дена',
        ],
        steps: null,
      },
      {
        title: 'Даночен третман на НВО',
        content:
          'Даночниот режим за невладини организации е значително поповолен од оној за трговски друштва, но има важни исклучоци:',
        items: [
          'Грант-приходите НЕ се оданочуваат (не се сметаат за приход за даночни цели)',
          'Приходите од комерцијална дејност СЕ оданочуваат со 10% ако надминат 1М МКД/годишно',
          'Регистрација за ДДВ е задолжителна ако комерцијалниот промет надмине 2М МКД',
          'Обврски за плати/придонеси се исти како за профитни организации (МПИН, придонеси, ПДД 10%)',
          'Примените донации се ослободени од данок (за НВО)',
          'Донациите дадени од компании се одбитни до 5% од приходот на компанијата',
          'Данок на имот се плаќа за поседуван недвижен имот',
          'Нема дистрибуција на дивиденди — целиот вишок се реинвестира во мисијата',
        ],
        steps: null,
      },
      {
        title: 'Сметководство за грантови — клучниот предизвик',
        content:
          'Грант-сметководството е најкомплексниот дел од финансиското управување на НВО. Секој грант има свои правила:',
        items: null,
        steps: [
          { step: 'Грант-договор', desc: 'Го дефинира буџетот по линии, подобните трошоци, периодите на известување и правилата за набавки. Ова е „уставот" на секој проект.' },
          { step: 'Посебна банкарска сметка', desc: 'Многу донатори бараат дедицирана сметка за проектот. Ова го олеснува следењето, но бара посебно книговодство за секоја сметка.' },
          { step: 'Распределба на трошоци', desc: 'Директните трошоци се книжат директно на проектот. Индиректните/оперативните трошоци се распределуваат по формула (обично % од директните трошоци или % од буџетот).' },
          { step: 'Финансиско известување', desc: 'Во формат на донаторот — обично квартално или полугодишно. Секој трошок мора да биде документиран со фактура/сметка и одобрение.' },
          { step: 'Ревизија', desc: 'Повеќето грантови над €50.000 бараат независна ревизија. Ревизорот ги проверува подобноста на трошоците и усогласеноста со грант-договорот.' },
          { step: 'Подобни vs неподобни трошоци', desc: 'Лични трошоци, забава, казни, банкарски провизии (освен ако не се дозволени) — никогаш не се подобни трошоци.' },
          { step: 'Ко-финансирање', desc: 'Сопствениот придонес мора да се следи одделно. Може да биде финансиски (свои средства) или во натура (волонтерски часови, простор).' },
          { step: 'Девизен курс', desc: 'Грантовите во EUR — се евидентираат по среден курс на НБРСМ на денот на трансакцијата. Курсните разлики се посебна ставка.' },
        ],
      },
      {
        title: 'Годишни обврски за известување',
        content:
          'НВО имаат обврски за годишно известување слични на трговските друштва, но со некои специфики:',
        items: [
          'Годишна сметка до ЦРСМ — рок 28 февруари',
          'Поедноставени финансиски извештаи за мали НВО (<50 вработени, <€2М приход)',
          'Наративен извештај за дејноста — се објавува јавно',
          'Даночен биланс (ДБ) само ако комерцијалната дејност надмине 1М МКД',
          'МПИН месечно за сите вработени',
          'ДДВ пријава квартално ако сте ДДВ обврзник',
          'Јавно достапни на веб-страната на ЦРСМ — транспарентност',
        ],
        steps: null,
      },
      {
        title: 'Совети за усогласеност со донатори',
        content:
          'Усогласеноста со барањата на донаторите е клучна за одржување на финансирањето и репутацијата:',
        items: [
          'Секогаш одвојте ги проектните и оперативните сметки',
          'Документирајте секој трошок со фактура/сметка + одобрение од проектен менаџер',
          'Водете временски листи (timesheets) за вработените чија плата се товари на проект',
          'Правила за набавки (3 понуди за >€2.500 кај ЕУ-финансирани проекти)',
          'Опремата купена со грант — задржете ја за периодот на проектот (обично 5 години)',
          'Пријавете финансиски неправилности до донаторот веднаш',
          'Користете дедицирани центри на трошоци за секој проект/грант',
        ],
        steps: null,
      },
      {
        title: 'Чести грешки',
        content:
          'Овие грешки може да доведат до враќање на средства, губење на идни грантови или казни:',
        items: [
          'Мешање на проектни средства со оперативни пари',
          'Недостаток на документација за набавки при големи купувања',
          'Неодвојување на комерцијални и грант-приходи за даночни цели',
          'Задоцнето поднесување на годишна сметка (казна 500-2.000 EUR)',
          'Неевидентирање на придонесите во натура како ко-финансирање',
          'Користење грант-средства за неподобни трошоци',
          'Непостоење документација за девизен курс при грантови во EUR',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Годишна сметка 2025: Целосен водич' },
      { slug: 'drzavni-institucii-za-firmi', title: 'Државни институции за фирми во Македонија' },
      { slug: 'ifrs-izvesti-mk', title: 'МСФИ извештаи во Македонија' },
    ],
    cta: {
      title: 'НВО? Facturino ви помага со проектно следење.',
      desc: 'Центри на трошоци по проект, грант-известување и годишни сметки — сè на едно место. Бесплатен план за почеток.',
      button: 'Започнете бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'NGO & Association Accounting in North Macedonia: 2026 Guide',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro:
      'NGOs (non-governmental organizations), citizen associations, and foundations in North Macedonia have specific accounting rules that differ from commercial companies. They don\'t pay corporate tax on grant income, but must track project funds separately, report to donors, and file annual accounts with the Central Registry (CRMS). This guide covers the specifics of proper bookkeeping for the non-profit sector in 2026.',
    sections: [
      {
        title: 'Legal framework',
        content:
          'Non-governmental organizations operate within a specific legal framework that differs from commercial entities:',
        items: [
          'Governed by the Law on Associations and Foundations (2010, amended 2019)',
          'Registered at CRMS (Central Registry of the Republic of North Macedonia)',
          'No shareholders — managed by assembly + board of directors',
          'Public benefit status available (provides tax benefits)',
          'Must separate program/project funds from operational funds',
          'Annual assembly meeting mandatory',
          'Statutory changes must be reported to CRMS within 30 days',
        ],
        steps: null,
      },
      {
        title: 'Tax treatment of NGOs',
        content:
          'The tax regime for non-governmental organizations is significantly more favorable than for commercial companies, but there are important exceptions:',
        items: [
          'Grant income is NOT taxed (not considered revenue for tax purposes)',
          'Commercial activity income IS taxed at 10% if exceeding 1M MKD/year',
          'VAT registration required if commercial turnover exceeds 2M MKD',
          'Salary/payroll obligations same as for-profit entities (MPIN, contributions, PIT 10%)',
          'Donations received are tax-free (for the NGO)',
          'Donations given by companies are deductible up to 5% of their revenue',
          'Property tax applies on owned real estate',
          'No dividend distribution — all surplus must be reinvested in the mission',
        ],
        steps: null,
      },
      {
        title: 'Grant accounting — the core challenge',
        content:
          'Grant accounting is the most complex part of NGO financial management. Each grant has its own rules:',
        items: null,
        steps: [
          { step: 'Grant agreement', desc: 'Defines the budget by lines, eligible costs, reporting periods, and procurement rules. This is the "constitution" of each project.' },
          { step: 'Separate bank account', desc: 'Many donors require a dedicated account for the project. This simplifies tracking but requires separate bookkeeping for each account.' },
          { step: 'Cost allocation', desc: 'Direct costs are posted directly to the project. Indirect/overhead costs are allocated by formula (usually % of direct costs or % of budget).' },
          { step: 'Financial reporting', desc: 'In donor format — usually quarterly or semi-annually. Every expense must be documented with an invoice/receipt and approval.' },
          { step: 'Audit', desc: 'Most grants above €50,000 require an independent audit. The auditor verifies cost eligibility and compliance with the grant agreement.' },
          { step: 'Eligible vs ineligible costs', desc: 'Personal expenses, entertainment, fines, bank fees (unless allowed) — these are never eligible costs.' },
          { step: 'Co-financing', desc: 'Own contribution must be tracked separately. Can be financial (own funds) or in-kind (volunteer hours, space).' },
          { step: 'Exchange rate', desc: 'Grants in EUR — recorded at the NBS middle rate on the transaction date. Exchange rate differences are a separate line item.' },
        ],
      },
      {
        title: 'Annual filing requirements',
        content:
          'NGOs have annual reporting obligations similar to commercial entities, but with some specifics:',
        items: [
          'Annual account due to CRMS by February 28',
          'Simplified financial statements for small NGOs (<50 employees, <€2M revenue)',
          'Activity report (narrative report) — published publicly',
          'Tax return (DB) only if commercial activity exceeds 1M MKD',
          'MPIN monthly for all employees',
          'VAT return quarterly if VAT registered',
          'Publicly available on the CRMS website — transparency',
        ],
        steps: null,
      },
      {
        title: 'Donor compliance tips',
        content:
          'Compliance with donor requirements is key to maintaining funding and reputation:',
        items: [
          'Always separate project and operational accounts',
          'Document every expense with invoice/receipt + project manager approval',
          'Keep time sheets for staff charged to projects',
          'Procurement rules (3 quotes for >€2,500 in EU-funded projects)',
          'Equipment purchased with grants — retain for the project period (usually 5 years)',
          'Report financial irregularities to the donor immediately',
          'Use dedicated cost centers per project/grant',
        ],
        steps: null,
      },
      {
        title: 'Common mistakes',
        content:
          'These mistakes can lead to fund recovery, loss of future grants, or penalties:',
        items: [
          'Mixing project funds with operational money',
          'Missing procurement documentation for large purchases',
          'Not separating commercial and grant income for tax purposes',
          'Late annual filing (penalty 500–2,000 EUR)',
          'Not tracking in-kind contributions as co-financing',
          'Using grant funds for ineligible expenses',
          'Not maintaining exchange rate documentation for EUR grants',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Annual Accounts 2025: Complete Filing Guide' },
      { slug: 'drzavni-institucii-za-firmi', title: 'Government Institutions for Businesses in Macedonia' },
      { slug: 'ifrs-izvesti-mk', title: 'IFRS Reporting in North Macedonia' },
    ],
    cta: {
      title: 'NGO? Facturino helps with project tracking.',
      desc: 'Cost centers per project, grant reporting, and annual accounts — all in one place. Free plan to get started.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Kontabiliteti për OJQ dhe shoqata: Udhëzues 2026',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro:
      'Organizatat joqeveritare (OJQ), shoqatat e qytetarëve dhe fondacionet në Maqedoni kanë rregulla specifike kontabiliteti që ndryshojnë nga kompanitë tregtare. Ato nuk paguajnë tatim mbi fitimin për të ardhurat nga grantet, por duhet t’i ndjekin fondet e projekteve veçanërisht, të raportojnë te donatorët dhe të dorëzojnë llogaritë vjetore në QRMK. Ky udhëzues mbulon specifikat e kontabilitetit të duhur për sektorin jofitimprurës në 2026.',
    sections: [
      {
        title: 'Kuadri ligjor',
        content:
          'Organizatat joqeveritare veprojnë brenda një kuadri specifik ligjor që ndryshon nga subjektet tregtare:',
        items: [
          'Rregullohen nga Ligji për shoqatat dhe fondacionet (2010, i ndryshuar 2019)',
          'Regjistrohen në QRMK (Regjistri Qendror i Republikës së Maqedonisë së Veriut)',
          'Nuk kanë aksionarë — menaxhohen nga kuvendi + bordi drejtues',
          'Statusi i organizatës me interes publik i disponueshëm (ofron përfitime tatimore)',
          'Duhet të ndajnë fondet programore/projektuese nga ato operative',
          'Mbledhja vjetore e anëtarëve është e detyrueshme',
          'Ndryshimet statutore raportohen në QRMK brenda 30 ditëve',
        ],
        steps: null,
      },
      {
        title: 'Trajtimi tatimor i OJQ-ve',
        content:
          'Regjimi tatimor për organizatat joqeveritare është shumë më i favorshëm se ai për kompanitë tregtare, por ka përjashtime të rëndësishme:',
        items: [
          'Të ardhurat nga grantet NUK tatohen (nuk konsiderohen të ardhura për qëllime tatimore)',
          'Të ardhurat nga aktiviteti tregtar tatohen me 10% nëse kalojnë 1M MKD/vit',
          'Regjistrimi për TVSH kërkohet nëse qarkullimi tregtar kalon 2M MKD',
          'Detyrimet për paga/kontribute janë njësoj si për organizatat fitimprurëse (MPIN, kontribute, TAP 10%)',
          'Donacionet e pranuara janë pa tatim (për OJQ-në)',
          'Donacionet nga kompanitë janë të zbritshme deri 5% të të ardhurave të tyre',
          'Tatimi mbi pronën zbatohet për pasuritë e paluajtshme',
          'Nuk ka shpërndarje dividenti — i gjithë teprica riinvestohet në mision',
        ],
        steps: null,
      },
      {
        title: 'Kontabiliteti i granteve — sfida kryesore',
        content:
          'Kontabiliteti i granteve është pjesa më komplekse e menaxhimit financiar të OJQ-ve. Çdo grant ka rregullat e veta:',
        items: null,
        steps: [
          { step: 'Marrëveshja e grantit', desc: 'Përcakton buxhetin sipas linjave, kostot e pranueshme, periudhat e raportimit dhe rregullat e prokurimit. Ky është "kushtetuta" e çdo projekti.' },
          { step: 'Llogari bankare e veçantë', desc: 'Shumë donatorë kërkojnë llogari të dedikuar për projektin. Kjo thjeshton ndjekjen por kërkon kontabilitet të veçantë për çdo llogari.' },
          { step: 'Shpërndarja e kostove', desc: 'Kostot direkte regjistrohen drejtpërdrejt në projekt. Kostot indirekte/operative shpërndahen sipas formulës (zakonisht % e kostove direkte ose % e buxhetit).' },
          { step: 'Raportimi financiar', desc: 'Në formatin e donatorit — zakonisht tremujor ose gjysmëvjetor. Çdo shpenzim duhet dokumentuar me faturë dhe miratim.' },
          { step: 'Auditimi', desc: 'Shumica e granteve mbi €50,000 kërkojnë audit të pavarur. Audituesi verifikon pranueshmërinë e kostove dhe pajtueshmërinë me marrëveshjen.' },
          { step: 'Kosto të pranueshme vs të papranueshme', desc: 'Shpenzimet personale, argëtimi, gjobat, tarifat bankare (përveç nëse lejohen) — nuk janë kurrë kosto të pranueshme.' },
          { step: 'Bashkëfinancimi', desc: 'Kontributi vetjak duhet ndjekur veçanërisht. Mund të jetë financiar (fonde vetjake) ose në natyrë (orë vullnetare, hapësirë).' },
          { step: 'Kursi i këmbimit', desc: 'Grantet në EUR — regjistrohen me kursin mesatar të BQK-së në datën e transaksionit. Diferencat e kursit janë zë i veçantë.' },
        ],
      },
      {
        title: 'Kërkesat vjetore të raportimit',
        content:
          'OJQ-të kanë detyrime vjetore raportimi të ngjashme me subjektet tregtare, por me disa specifika:',
        items: [
          'Llogaritë vjetore në QRMK — afati 28 shkurt',
          'Pasqyra financiare të thjeshtuara për OJQ të vogla (<50 punonjës, <€2M)',
          'Raporti i aktivitetit (raport narrativ) — publikohet publikisht',
          'Deklarata tatimore (DB) vetëm nëse aktiviteti tregtar kalon 1M MKD',
          'MPIN mujor për të gjithë punonjësit',
          'Deklarata TVSH tremujore nëse jeni pagues TVSH',
          'Publikisht i disponueshëm në faqen e QRMK-së — transparencë',
        ],
        steps: null,
      },
      {
        title: 'Këshilla për pajtueshmërinë me donatorët',
        content:
          'Pajtueshmëria me kërkesat e donatorëve është çelësi për ruajtjen e financimit dhe reputacionit:',
        items: [
          'Gjithmonë ndani llogaritë e projektit dhe operative',
          'Dokumentoni çdo shpenzim me faturë + miratim nga menaxheri i projektit',
          'Mbani fletë kohore (timesheets) për stafin e ngarkuar në projekte',
          'Rregullat e prokurimit (3 oferta për >€2,500 në projekte të financuara nga BE)',
          'Pajisjet e blera me grant — mbajini për periudhën e projektit (zakonisht 5 vjet)',
          'Raportoni parregullsitë financiare te donatori menjëherë',
          'Përdorni qendra kostoje të dedikuara për çdo projekt/grant',
        ],
        steps: null,
      },
      {
        title: 'Gabime të zakonshme',
        content:
          'Këto gabime mund të çojnë në kthimin e fondeve, humbjen e granteve të ardhshme ose gjoba:',
        items: [
          'Përzierja e fondeve të projektit me paratë operative',
          'Mungesa e dokumentacionit të prokurimit për blerje të mëdha',
          'Mosndarja e të ardhurave tregtare dhe granteve për qëllime tatimore',
          'Dorëzim i vonuar i llogarive vjetore (gjobë 500–2,000 EUR)',
          'Mosevidentimi i kontributeve në natyrë si bashkëfinancim',
          'Përdorimi i fondeve të grantit për shpenzime të papranueshme',
          'Mosruajtja e dokumentacionit të kursit të këmbimit për grante në EUR',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Llogaritë vjetore 2025: Udhëzues i plotë' },
      { slug: 'drzavni-institucii-za-firmi', title: 'Institucionet shtetërore për biznese në Maqedoni' },
      { slug: 'ifrs-izvesti-mk', title: 'Raportimi sipas SNRF në Maqedoni' },
    ],
    cta: {
      title: 'OJQ? Facturino ju ndihmon me ndjekjen e projekteve.',
      desc: 'Qendra kostoje sipas projektit, raportimi i granteve dhe llogaritë vjetore — gjithçka në një vend. Plan falas për fillim.',
      button: 'Filloni falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'STK ve Dernekler İçin Muhasebe: 2026 Rehberi',
    publishDate: '23 Mayıs 2026',
    readTime: '10 dk okuma',
    intro:
      'Kuzey Makedonya\'daki sivil toplum kuruluşları (STK), vatandaş dernekleri ve vakıflar, ticari şirketlerden farklı muhasebe kurallarına tabidir. Hibe gelirlerinden kurumlar vergisi ödemezler ancak proje fonlarını ayrı takip etmeli, bağışçılara rapor vermeli ve Merkezi Sicile (CRMS) yıllık hesap sunmalıdırlar. Bu rehber, 2026\'da kâr amacı gütmeyen sektör için muhasebe özelliklerini kapsar.',
    sections: [
      {
        title: 'Yasal çerçeve',
        content:
          'Sivil toplum kuruluşları, ticari kuruluşlardan farklı bir yasal çerçeve içinde faaliyet gösterir:',
        items: [
          'Dernekler ve Vakıflar Kanunu (2010, 2019 değişikliği) ile düzenlenir',
          'CRMS\'ye (Kuzey Makedonya Merkezi Sicili) kayıt olunur',
          'Hissedar yok — genel kurul + yönetim kurulu tarafından yönetilir',
          'Kamu yararı statüsü mevcut (vergi avantajları sağlar)',
          'Program/proje fonları operasyonel fonlardan ayrılmalıdır',
          'Yıllık üye genel kurulu zorunludur',
          'Tüzük değişiklikleri 30 gün içinde CRMS\'ye bildirilmelidir',
        ],
        steps: null,
      },
      {
        title: 'STK\'ların vergi muamelesi',
        content:
          'Sivil toplum kuruluşları için vergi rejimi ticari şirketlere kıyasla çok daha avantajlıdır, ancak önemli istisnalar vardır:',
        items: [
          'Hibe gelirleri vergilendirilMEZ (vergi açısından gelir sayılmaz)',
          'Ticari faaliyet geliri yıllık 1M MKD\'yi aşarsa %10 vergilendirilir',
          'Ticari ciro 2M MKD\'yi aşarsa KDV kaydı zorunludur',
          'Maaş/bordro yükümlülükleri kâr amaçlı kuruluşlarla aynıdır (MPIN, primler, GV %10)',
          'Alınan bağışlar vergiden muaftır (STK için)',
          'Şirketlerin verdiği bağışlar gelirlerinin %5\'ine kadar indirilebilir',
          'Sahip olunan gayrimenkullerde emlak vergisi uygulanır',
          'Kâr payı dağıtımı yok — tüm fazla misyona yeniden yatırılır',
        ],
        steps: null,
      },
      {
        title: 'Hibe muhasebesi — temel zorluk',
        content:
          'Hibe muhasebesi, STK mali yönetiminin en karmaşık kısmıdır. Her hibenin kendi kuralları vardır:',
        items: null,
        steps: [
          { step: 'Hibe sözleşmesi', desc: 'Bütçeyi kalem bazında, uygun maliyetleri, raporlama dönemlerini ve satın alma kurallarını tanımlar. Her projenin "anayasası"dır.' },
          { step: 'Ayrı banka hesabı', desc: 'Birçok bağışçı proje için özel hesap talep eder. Bu takibi kolaylaştırır ancak her hesap için ayrı muhasebe gerektirir.' },
          { step: 'Maliyet dağıtımı', desc: 'Doğrudan maliyetler projeye doğrudan kaydedilir. Dolaylı/genel giderler formüle göre dağıtılır (genellikle doğrudan maliyetlerin %\'si veya bütçenin %\'si).' },
          { step: 'Mali raporlama', desc: 'Bağışçı formatında — genellikle üç aylık veya altı aylık. Her gider fatura ve onay ile belgelenmelidir.' },
          { step: 'Denetim', desc: '€50.000\'un üzerindeki çoğu hibe bağımsız denetim gerektirir. Denetçi maliyet uygunluğunu ve sözleşme uyumunu doğrular.' },
          { step: 'Uygun vs uygun olmayan maliyetler', desc: 'Kişisel harcamalar, eğlence, cezalar, banka ücretleri (izin verilmedikçe) — bunlar asla uygun maliyet değildir.' },
          { step: 'Eş finansman', desc: 'Kendi katkısı ayrı takip edilmelidir. Finansal (kendi fonları) veya ayni (gönüllü saatleri, mekan) olabilir.' },
          { step: 'Döviz kuru', desc: 'EUR cinsinden hibeler — işlem tarihindeki NBRM orta kuruyla kaydedilir. Kur farkları ayrı kalemdir.' },
        ],
      },
      {
        title: 'Yıllık dosyalama gereksinimleri',
        content:
          'STK\'lar ticari kuruluşlara benzer yıllık raporlama yükümlülüklerine sahiptir, ancak bazı farklılıklar vardır:',
        items: [
          'CRMS\'ye yıllık hesap — son tarih 28 Şubat',
          'Küçük STK\'lar için basitleştirilmiş mali tablolar (<50 çalışan, <€2M)',
          'Faaliyet raporu (narratif rapor) — kamuya açık yayınlanır',
          'Vergi beyannamesi (DB) yalnızca ticari faaliyet 1M MKD\'yi aşarsa',
          'Tüm çalışanlar için aylık MPIN',
          'KDV mükellefi iseniz üç aylık KDV beyannamesi',
          'CRMS web sitesinde kamuya açık — şeffaflık',
        ],
        steps: null,
      },
      {
        title: 'Bağışçı uyum ipuçları',
        content:
          'Bağışçı gereksinimlerine uyum, finansmanı ve itibarı korumak için kritiktir:',
        items: [
          'Proje ve operasyonel hesapları her zaman ayırın',
          'Her gideri fatura + proje yöneticisi onayı ile belgeleyin',
          'Projelere yüklenen personel için zaman çizelgeleri tutun',
          'Satın alma kuralları (AB fonlu projelerde >€2.500 için 3 teklif)',
          'Hibeyle alınan ekipman — proje süresince saklayın (genellikle 5 yıl)',
          'Mali usulsüzlükleri derhal bağışçıya bildirin',
          'Her proje/hibe için özel maliyet merkezleri kullanın',
        ],
        steps: null,
      },
      {
        title: 'Yaygın hatalar',
        content:
          'Bu hatalar fon iadesi, gelecek hibelerin kaybı veya cezalara yol açabilir:',
        items: [
          'Proje fonlarını operasyonel parayla karıştırma',
          'Büyük alımlarda satın alma belgelendirmesinin eksikliği',
          'Ticari ve hibe gelirlerini vergi açısından ayırmama',
          'Geç yıllık dosyalama (ceza 500–2.000 EUR)',
          'Ayni katkıları eş finansman olarak takip etmeme',
          'Hibe fonlarını uygun olmayan giderler için kullanma',
          'EUR hibeler için döviz kuru belgelendirmesini tutmama',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Yıllık Hesaplar 2025: Eksiksiz Dosyalama Rehberi' },
      { slug: 'drzavni-institucii-za-firmi', title: 'Makedonya\'da İşletmeler İçin Devlet Kurumları' },
      { slug: 'ifrs-izvesti-mk', title: 'Kuzey Makedonya\'da UFRS Raporlama' },
    ],
    cta: {
      title: 'STK mısınız? Facturino proje takibinde yardımcı olur.',
      desc: 'Proje bazlı maliyet merkezleri, hibe raporlama ve yıllık hesaplar — hepsi tek yerde. Başlamak için ücretsiz plan.',
      button: 'Ücretsiz başlayın',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function SmetkovodstvoZaNevladini({
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
    slug: 'smetkovodstvo-za-nevladini',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['NGO', 'невладини', 'здруженија', 'associations', 'foundations', 'грантови', 'grants', 'Macedonia'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-nevladini` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      {/* ============================================================ */}
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        {/* Background blobs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>

        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          {/* Back link */}
          <Link
            href={`/${locale}/blog`}
            className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors"
          >
            {t.backLink}
          </Link>

          {/* Tag pill */}
          <div className="mb-4">
            <span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600">
              {t.tag}
            </span>
          </div>

          {/* Title */}
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            {t.title}
          </h1>

          {/* Meta info */}
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {t.publishDate}
            </span>
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {t.readTime}
            </span>
          </div>

          {/* Intro paragraph */}
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.intro}
          </p>
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
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                  {section.title}
                </h2>

                {/* Paragraph content */}
                {section.content && (
                  <p className="text-gray-700 leading-relaxed text-lg">
                    {section.content}
                  </p>
                )}

                {/* Bullet list items — green check */}
                {section.items && (
                  <ul className="space-y-3 mt-2">
                    {section.items.map((item, j) => (
                      <li key={j} className="flex items-start gap-3">
                        <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                          <svg
                            className="w-3 h-3 text-green-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={3}
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              d="M5 13l4 4L19 7"
                            />
                          </svg>
                        </span>
                        <span className="text-gray-700 leading-relaxed">
                          {item}
                        </span>
                      </li>
                    ))}
                  </ul>
                )}

                {/* Numbered steps — indigo */}
                {section.steps && (
                  <ol className="space-y-6 mt-4">
                    {section.steps.map((s, j) => (
                      <li key={j} className="flex items-start gap-4">
                        <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold flex items-center justify-center mt-0.5">
                          {j + 1}
                        </span>
                        <div>
                          <h3 className="font-semibold text-gray-900 text-lg">
                            {s.step}
                          </h3>
                          <p className="text-gray-600 leading-relaxed mt-1">
                            {s.desc}
                          </p>
                        </div>
                      </li>
                    ))}
                  </ol>
                )}
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* RELATED ARTICLES — gray-50 background */}
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
      {/*  BOTTOM CTA — gradient indigo                                */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.cta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.cta.desc}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.cta.button}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
