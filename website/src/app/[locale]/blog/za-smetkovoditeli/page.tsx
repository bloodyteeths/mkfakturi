import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/za-smetkovoditeli', {
    title: {
      mk: 'Зошто сметководителите преминуваат на Facturino — Facturino',
      en: 'Why Accountants Are Switching to Facturino — Facturino',
      sq: 'Pse kontabilistët po kalojnë në Facturino — Facturino',
      tr: 'Muhasebeciler neden Facturino\'ya geçiyor — Facturino',
    },
    description: {
      mk: 'Водич за сметководствени бироа: повеќе клиенти, помалку рачна работа. Банкарски увоз, е-Фактура, годишно затворање и 20% партнерска провизија.',
      en: 'Guide for accounting firms: more clients, less manual work. Bank statement import, e-Invoice, year-end closing and 20% partner commission.',
      sq: 'Udhëzues për zyrat e kontabilitetit: më shumë klientë, më pak punë manuale. Import bankar, e-Faturë, mbyllje vjetore dhe 20% komision partneriteti.',
      tr: 'Muhasebe büroları için rehber: daha fazla müşteri, daha az manuel iş. Banka ekstresi içe aktarma, e-Fatura, yıl sonu kapanışı ve %20 ortaklık komisyonu.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Зошто сметководителите преминуваат на Facturino: Водич за модерни сметководствени бироа',
    publishDate: '21 февруари 2026',
    readTime: '8 мин читање',
    intro: 'Управувањето со десетици клиенти, секој со свои фактури, банковни сметки и даночни обврски, е реалноста на секое сметководствено биро во Македонија. Во ера кога е-Фактурата станува задолжителна, а клиентите бараат моментален увид во своите финансии, традиционалните методи едноставно не се доволни. Facturino е изграден за да ги реши токму овие предизвици — давајќи им на сметководителите моќен алат за раст и ефикасност.',
    sections: [
      {
        title: 'Предизвици со кои се соочуваат македонските сметководствени бироа',
        content: 'Секој сметководител во Македонија ги знае овие проблеми. Клиентите доставуваат фактури на хартија, банковните изводи се проверуваат рачно, а секој месец е трка со времето за поднесување на извештаи до УЈП. Овие предизвици не само што одземаат време, туку го ограничуваат бројот на клиенти што може да ги опслужите.',
        items: [
          'Жонглирање со повеќе клиенти на различни системи — Excel, хартија, стари програми',
          'Рачно усогласување на банковни изводи со фактури — часови секоја недела',
          'Приближување на задолжителна е-Фактура — потреба од UBL 2.1 подготвеност',
          'Тесни грлиња при месечно и годишно затворање на книги',
          'Неможност за далечински пристап до податоци на клиентите',
          'Ризик од грешки при рачно пресметување на ДДВ, придонеси и плати',
        ],
        steps: null,
      },
      {
        title: 'Што мора да нуди модерниот софтвер за сметководители',
        content: 'Не секој софтвер за фактурирање е погоден за сметководствени бироа. Потребен е систем кој е изграден за управување со повеќе компании истовремено, со автоматизации кои го намалуваат рачниот труд и со целосна усогласеност со македонското законодавство.',
        items: [
          'Дашборд за повеќе компании — пристап до сите клиенти од едно место',
          'Увоз на банковни изводи (CSV/MT940/PDF)',
          'е-Фактура во UBL 2.1 формат — подготвеност за законската обврска',
          'AI-потпомогнато затворање на книги — автоматска класификација и усогласување',
          'Поддршка за МСФИ и МК ГААП (македонски сметководствени стандарди)',
          'МПИН, ДДВ пријави и годишни биланси во УЈП-формат',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го решава секој предизвик',
        content: 'Facturino е повеќе од софтвер за фактурирање — тој е целосна платформа за сметководствени бироа. Еве конкретно како ги адресира најголемите болни точки:',
        items: null,
        steps: [
          { step: 'Едно место за сите клиенти', desc: 'Со партнерската конзола, пристапувате до сите компании на клиентите од еден логин. Нема повеќе жонглирање со различни системи. Секој клиент има посебна компанија со свои фактури, расходи, банковни сметки и извештаи — а вие ги управувате сите од едно место.' },
          { step: 'Увоз на банкарски изводи', desc: 'Увезете ги банковните изводи на клиентите преку CSV, MT940 или PDF формат. Трансакциите брзо се увезуваат, уплатите се препознаваат и усогласуваат со фактурите. Заштедете 2-3 часа неделно по клиент на рачно проверување на изводи. PSD2 поврзување наскоро.' },
          { step: 'е-Фактура подготвеност', desc: 'Facturino генерира фактури во UBL 2.1 XML формат, подготвени за електронско поднесување. Кога е-Фактурата ќе стане задолжителна, вие и вашите клиенти ќе бидете подготвени — без дополнителен трошок или напор.' },
          { step: 'Автоматско годишно затворање', desc: 'Годишното затворање на книги е најстресниот период за секое биро. Facturino го автоматизира процесот — од преглед на отворени ставки, преку класификација на трансакции, до генерирање на биланс на состојба и биланс на успех во формат подготвен за ЦРСМ и УЈП.' },
          { step: 'МПИН и плати', desc: 'Пресметувајте плати за вработените на клиентите со автоматска пресметка на бруто/нето, придонеси и персонален данок. Генерирајте МПИН образци подготвени за поднесување до УЈП — без рачно пресметување, без грешки.' },
          { step: 'ДДВ и даночни извештаи', desc: 'Автоматска пресметка на влезен и излезен ДДВ по стапки од 18% и 5%. Месечни и квартални ДДВ пријави, данок на добивка и сите останати извештаи — генерирани автоматски во формат компатибилен со УЈП.' },
        ],
      },
      {
        title: 'Партнерска програма на Facturino',
        content: 'Facturino нуди посебна партнерска програма дизајнирана специјално за сметководствени бироа. Не само што добивате моќен алат за управување со клиенти, туку и заработувате додека растете.',
        items: [
          '20% месечна провизија од секоја претплата на вашите клиенти — пасивен приход кој расте со секој нов клиент',
          '22% провизија за годишни претплати — уште поголема заработка за долгорочни клиенти',
          'Бесплатно членство — нема трошок за влез, нема минимум клиенти',
          'Партнерски портал за следење на провизии, клиенти и приходи',
          'Посветен тим за поддршка со приоритетен одговор за партнери',
          'Бесплатна обука и онбординг за вас и вашиот тим',
        ],
        steps: null,
      },
      {
        title: 'Три чекори до трансформација на вашето биро',
        content: 'Преминувањето на Facturino е едноставно и може да се заврши за помалку од еден ден. Еве како:',
        items: null,
        steps: [
          { step: 'Регистрирајте се како партнер', desc: 'Пополнете ја кратката форма за партнерска регистрација. За помалку од 5 минути ќе имате пристап до партнерската конзола каде можете да ги додавате вашите клиенти и да управувате со сите нивни компании.' },
          { step: 'Онбордирајте го првиот клиент', desc: 'Додадете ја компанијата на вашиот прв клиент, импортирајте ги постоечките податоци од Excel или друг систем и увезете банковни изводи (CSV/MT940/PDF). За помалку од 30 минути, вашиот клиент ќе биде целосно поставен.' },
          { step: 'Скалирајте и заработувајте', desc: 'Со секој нов клиент што го додавате, вашата ефикасност расте благодарение на автоматизациите, а вашата провизија расте со 20% од секоја претплата. Бироа кои управуваат со 20+ клиенти преку Facturino заштедуваат над 40 часа месечно.' },
        ],
      },
      {
        title: 'Зошто сметководителите го избираат Facturino',
        content: 'Сметководствени бироа низ цела Македонија веќе ги искусуваат придобивките од Facturino. Еве што најчесто го истакнуваат:',
        items: [
          'Заштеда од 15-20 часа месечно на административна работа по клиент',
          'Нула грешки во ДДВ пријави и МПИН образци благодарение на автоматизацијата',
          'Можност за управување со 3x повеќе клиенти без дополнителен персонал',
          'Пасивен приход од 20% провизија — просечно 200-500€ месечно за бироа со 15+ клиенти',
          'Подготвеност за е-Фактура без дополнителна инвестиција',
          'Далечински пристап до сите податоци — работа од дома или од канцеларија',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
      { slug: 'zosto-facturino', title: '10 причини зошто македонски бизниси го избираат Facturino' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Годишно затворање на книги: 6 чекори со Facturino' },
    ],
    cta: {
      title: 'Трансформирајте го вашето сметководствено биро',
      desc: 'Станете партнер на Facturino — бесплатно членство, 20% провизија и моќни алатки за управување со клиенти.',
      button: 'Стани партнер',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Why Accountants Are Switching to Facturino: A Guide for Modern Accounting Firms',
    publishDate: 'February 21, 2026',
    readTime: '8 min read',
    intro: 'Managing dozens of clients, each with their own invoices, bank accounts, and tax obligations, is the reality of every accounting firm in Macedonia. In an era where e-Invoice is becoming mandatory and clients demand instant insight into their finances, traditional methods simply are not enough. Facturino is built to solve exactly these challenges — giving accountants a powerful tool for growth and efficiency.',
    sections: [
      {
        title: 'Challenges Facing Macedonian Accounting Firms',
        content: 'Every accountant in Macedonia knows these problems. Clients deliver invoices on paper, bank statements are checked manually, and every month is a race against time to file reports with UJP. These challenges not only consume time but limit the number of clients you can serve.',
        items: [
          'Juggling multiple clients on different systems — Excel, paper, legacy software',
          'Manual reconciliation of bank statements with invoices — hours every week',
          'Approaching mandatory e-Invoice — need for UBL 2.1 readiness',
          'Month-end and year-end closing bottlenecks',
          'Inability to remotely access client data',
          'Risk of errors in manual calculation of VAT, contributions, and payroll',
        ],
        steps: null,
      },
      {
        title: 'What Modern Accounting Software Must Offer',
        content: 'Not every invoicing software is suitable for accounting firms. You need a system built for managing multiple companies simultaneously, with automations that reduce manual effort and full compliance with Macedonian legislation.',
        items: [
          'Multi-company dashboard — access all clients from one place',
          'Bank statement import (CSV/MT940/PDF)',
          'e-Invoice in UBL 2.1 format — readiness for the legal requirement',
          'AI-assisted book closing — automatic classification and reconciliation',
          'Support for IFRS and MK GAAP (Macedonian accounting standards)',
          'MPIN, VAT returns, and annual statements in UJP format',
        ],
        steps: null,
      },
      {
        title: 'How Facturino Solves Each Challenge',
        content: 'Facturino is more than invoicing software — it is a complete platform for accounting firms. Here is specifically how it addresses the biggest pain points:',
        items: null,
        steps: [
          { step: 'One Place for All Clients', desc: 'With the partner console, you access all client companies from a single login. No more juggling different systems. Each client has a separate company with their own invoices, expenses, bank accounts, and reports — and you manage them all from one place.' },
          { step: 'Bank Statement Import', desc: 'Import client bank statements via CSV, MT940, or PDF format. Transactions are quickly imported, payments are recognized and reconciled with invoices. Save 2-3 hours per week per client on manual statement checking. PSD2 connection coming soon.' },
          { step: 'e-Invoice Ready', desc: 'Facturino generates invoices in UBL 2.1 XML format, ready for electronic submission. When e-Invoice becomes mandatory, you and your clients will be prepared — at no additional cost or effort.' },
          { step: 'Automatic Year-End Closing', desc: 'Year-end closing is the most stressful period for every firm. Facturino automates the process — from reviewing open items, through transaction classification, to generating balance sheets and income statements in CRMS and UJP-ready format.' },
          { step: 'MPIN and Payroll', desc: 'Calculate employee payroll for your clients with automatic gross/net calculation, contributions, and personal income tax. Generate MPIN forms ready for UJP submission — no manual calculation, no errors.' },
          { step: 'VAT and Tax Reports', desc: 'Automatic calculation of input and output VAT at 18% and 5% rates. Monthly and quarterly VAT returns, corporate income tax, and all other reports — generated automatically in UJP-compatible format.' },
        ],
      },
      {
        title: 'Facturino Partner Program',
        content: 'Facturino offers a dedicated partner program designed specifically for accounting firms. You not only get a powerful tool for managing clients, but also earn as you grow.',
        items: [
          '20% monthly commission on every client subscription — passive income that grows with each new client',
          '22% commission on annual subscriptions — even higher earnings for long-term clients',
          'Free membership — no entry cost, no minimum clients',
          'Partner portal for tracking commissions, clients, and revenue',
          'Dedicated support team with priority response for partners',
          'Free training and onboarding for you and your team',
        ],
        steps: null,
      },
      {
        title: 'Three Steps to Transform Your Practice',
        content: 'Switching to Facturino is simple and can be completed in less than one day. Here is how:',
        items: null,
        steps: [
          { step: 'Sign Up as a Partner', desc: 'Fill out the short partner registration form. In less than 5 minutes you will have access to the partner console where you can add your clients and manage all their companies.' },
          { step: 'Onboard Your First Client', desc: 'Add your first client\'s company, import existing data from Excel or another system, and import bank statements (CSV/MT940/PDF). In less than 30 minutes, your client will be fully set up.' },
          { step: 'Scale and Earn', desc: 'With every new client you add, your efficiency grows thanks to automations, and your commission grows by 20% of every subscription. Firms managing 20+ clients through Facturino save over 40 hours per month.' },
        ],
      },
      {
        title: 'Why Accountants Choose Facturino',
        content: 'Accounting firms across Macedonia are already experiencing the benefits of Facturino. Here is what they highlight most:',
        items: [
          'Saving 15-20 hours per month on administrative work per client',
          'Zero errors in VAT returns and MPIN forms thanks to automation',
          'Ability to manage 3x more clients without additional staff',
          'Passive income from 20% commission — averaging 200-500 EUR monthly for firms with 15+ clients',
          'e-Invoice readiness at no additional investment',
          'Remote access to all data — work from home or the office',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
      { slug: 'zosto-facturino', title: '10 Reasons Macedonian Businesses Choose Facturino' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Year-End Closing: 6 Steps with Facturino' },
    ],
    cta: {
      title: 'Transform Your Accounting Practice',
      desc: 'Become a Facturino partner — free membership, 20% commission, and powerful client management tools.',
      button: 'Become a partner',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Pse kontabilistët po kalojnë në Facturino: Udhëzues për zyrat moderne të kontabilitetit',
    publishDate: '21 shkurt 2026',
    readTime: '8 min lexim',
    intro: 'Menaxhimi i dhjetëra klientëve, secili me faturat, llogaritë bankare dhe detyrimet tatimore të veta, është realiteti i çdo zyre kontabiliteti në Maqedoni. Në një epokë kur e-Fatura po bëhet e detyrueshme dhe klientët kërkojnë pasqyrë të menjëhershme të financave të tyre, metodat tradicionale thjesht nuk mjaftojnë. Facturino është ndërtuar për të zgjidhur pikërisht këto sfida — duke u dhënë kontabilistëve një mjet të fuqishëm për rritje dhe efikasitet.',
    sections: [
      {
        title: 'Sfidat me të cilat përballen zyrat e kontabilitetit maqedonas',
        content: 'Çdo kontabilist në Maqedoni i njeh këto probleme. Klientët dorëzojnë fatura në letër, deklaratat bankare kontrollohen manualisht dhe çdo muaj është garë kundër kohës për dorëzimin e raporteve në DAP. Këto sfida jo vetëm konsumojnë kohë por kufizojnë numrin e klientëve që mundni t\'i shërbeni.',
        items: [
          'Zhonglim me klientë të shumtë në sisteme të ndryshme — Excel, letër, softuer i vjetër',
          'Pajtim manual i deklaratave bankare me faturat — orë çdo javë',
          'Afrimi i e-Faturës së detyrueshme — nevoja për gatishmëri UBL 2.1',
          'Ngushtica në mbylljen mujore dhe vjetore të librave',
          'Pamundësia për qasje në distancë te të dhënat e klientëve',
          'Rreziku i gabimeve në llogaritjen manuale të TVSH, kontributeve dhe pagave',
        ],
        steps: null,
      },
      {
        title: 'Çfarë duhet të ofrojë softueri modern për kontabilistë',
        content: 'Jo çdo softuer faturimi është i përshtatshëm për zyrat e kontabilitetit. Nevojitet një sistem i ndërtuar për menaxhimin e kompanive të shumta njëkohësisht, me automatizime që zvogëlojnë punën manuale dhe përputhshmëri të plotë me legjislacionin maqedonas.',
        items: [
          'Dëshbord për kompani të shumta — qasje te të gjithë klientët nga një vend',
          'Import i ekstrakteve bankare (CSV/MT940/PDF)',
          'e-Faturë në formatin UBL 2.1 — gatishmëri për kërkesën ligjore',
          'Mbyllje librash e ndihmuar nga AI — klasifikim dhe pajtim automatik',
          'Mbështetje për SNRF dhe MK GAAP (standarde kontabiliteti maqedonas)',
          'MPIN, deklarata TVSH dhe pasqyra vjetore në formatin e DAP',
        ],
        steps: null,
      },
      {
        title: 'Si Facturino e zgjidh çdo sfidë',
        content: 'Facturino është më shumë se softuer faturimi — është një platformë e plotë për zyrat e kontabilitetit. Ja si i adresën pikërisht pikat më të dhimbshme:',
        items: null,
        steps: [
          { step: 'Një vend për të gjithë klientët', desc: 'Me konzolën e partnerëve, qaseni te të gjitha kompanitë e klientëve nga një login i vetëm. Jo më zhonglim me sisteme të ndryshme. Çdo klient ka kompani të veçantë me faturat, shpenzimet, llogaritë bankare dhe raportet e veta — dhe ju i menaxhoni të gjitha nga një vend.' },
          { step: 'Import i ekstrakteve bankare', desc: 'Importoni ekstraktet bankare të klientëve përmes formatit CSV, MT940 ose PDF. Transaksionet importohen shpejt, pagesat njihen dhe pajtohen me faturat. Kurseni 2-3 orë në javë për klient në kontroll manual të deklaratave. Lidhja PSD2 së shpejti.' },
          { step: 'Gatishmëri për e-Faturë', desc: 'Facturino gjeneron fatura në formatin UBL 2.1 XML, gati për dorëzim elektronik. Kur e-Fatura bëhet e detyrueshme, ju dhe klientët tuaj do të jeni gati — pa kosto ose përpjekje shtesë.' },
          { step: 'Mbyllje automatike e vitit', desc: 'Mbyllja e vitit është periudha më stresuese për çdo zyrë. Facturino e automatizon procesin — nga rishikimi i zërave të hapur, përmes klasifikimit të transaksioneve, deri te gjenerimi i bilancit dhe pasqyrës së të ardhurave në format gati për QRMK dhe DAP.' },
          { step: 'MPIN dhe paga', desc: 'Llogaritni pagat e punonjësve të klientëve me llogaritje automatike bruto/neto, kontribute dhe tatim personal. Gjeneroni formularë MPIN gati për dorëzim në DAP — pa llogaritje manuale, pa gabime.' },
          { step: 'TVSH dhe raporte tatimore', desc: 'Llogaritje automatike e TVSH hyrëse dhe dalëse me norma 18% dhe 5%. Deklarata mujore dhe tremujore TVSH, tatim mbi fitimin dhe të gjitha raportet e tjera — gjeneruar automatikisht në format të përputhshëm me DAP.' },
        ],
      },
      {
        title: 'Programi i partneritetit Facturino',
        content: 'Facturino ofron një program të dedikuar partneriteti të dizajnuar posaçërisht për zyrat e kontabilitetit. Jo vetëm merrni një mjet të fuqishëm për menaxhimin e klientëve, por edhe fitoni ndërsa rriteni.',
        items: [
          '20% komision mujor nga çdo abonim klienti — të ardhura pasive që rriten me çdo klient të ri',
          '22% komision për abonime vjetore — fitime edhe më të mëdha për klientë afatgjatë',
          'Anëtarësim falas — pa kosto hyrjeje, pa minimum klientësh',
          'Portal partneriteti për ndjekjen e komisioneve, klientëve dhe të ardhurave',
          'Ekip mbështetjeje i dedikuar me përgjigje prioritare për partnerë',
          'Trajnim dhe onboarding falas për ju dhe ekipin tuaj',
        ],
        steps: null,
      },
      {
        title: 'Tre hapa për transformimin e zyrës suaj',
        content: 'Kalimi në Facturino është i thjeshtë dhe mund të përfundojë brenda një dite. Ja si:',
        items: null,
        steps: [
          { step: 'Regjistrohuni si partner', desc: 'Plotësoni formularin e shkurtër të regjistrimit të partnerëve. Brenda 5 minutash do të keni qasje te konzola e partnerëve ku mundni të shtoni klientët dhe të menaxhoni të gjitha kompanitë e tyre.' },
          { step: 'Regjistroni klientin e parë', desc: 'Shtoni kompaninë e klientit tuaj të parë, importoni të dhënat ekzistuese nga Excel ose sistem tjetër dhe importoni ekstraktet bankare (CSV/MT940/PDF). Brenda 30 minutash, klienti juaj do të jetë plotësisht i vendosur.' },
          { step: 'Shkallëzoni dhe fitoni', desc: 'Me çdo klient të ri që shtoni, efikasiteti juaj rritet falë automatizimeve dhe komisioni juaj rritet me 20% të çdo abonimi. Zyrat që menaxhojnë 20+ klientë përmes Facturino kursejnë mbi 40 orë në muaj.' },
        ],
      },
      {
        title: 'Pse kontabilistët zgjedhin Facturino',
        content: 'Zyrat e kontabilitetit në gjithë Maqedoninë tashmë po përjetojnë përfitimet e Facturino. Ja çfarë theksojnë më shpesh:',
        items: [
          'Kursim 15-20 orësh në muaj në punë administrative për klient',
          'Zero gabime në deklaratat e TVSH dhe formularët MPIN falë automatizimit',
          'Mundësia për menaxhimin e 3x më shumë klientëve pa personel shtesë',
          'Të ardhura pasive nga 20% komision — mesatarisht 200-500€ në muaj për zyra me 15+ klientë',
          'Gatishmëri për e-Faturë pa investim shtesë',
          'Qasje në distancë te të gjitha të dhënat — punë nga shtëpia ose zyra',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
      { slug: 'zosto-facturino', title: '10 arsye pse bizneset maqedonase zgjedhin Facturino' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Mbyllja e vitit: 6 hapa me Facturino' },
    ],
    cta: {
      title: 'Transformoni zyrën tuaj të kontabilitetit',
      desc: 'Bëhuni partner i Facturino — anëtarësim falas, 20% komision dhe mjete të fuqishme për menaxhimin e klientëve.',
      button: 'Bëhu partner',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Muhasebeciler neden Facturino\'ya geçiyor: Modern muhasebe büroları için rehber',
    publishDate: '21 Şubat 2026',
    readTime: '8 dk okuma',
    intro: 'Her biri kendi faturaları, banka hesapları ve vergi yükümlülükleri olan düzinelerce müşteriyi yönetmek, Makedonya\'daki her muhasebe bürosunun gerçekliğidir. e-Fatura\'nın zorunlu hale geldiği ve müşterilerin finanslarına anında erişim talep ettiği bir çağda, geleneksel yöntemler artık yeterli değildir. Facturino tam olarak bu zorlukları çözmek için inşa edilmiştir — muhasebecilere büyüme ve verimlilik için güçlü bir araç sunar.',
    sections: [
      {
        title: 'Makedon muhasebe bürolarının karşılaştığı zorluklar',
        content: 'Makedonya\'daki her muhasebeci bu sorunları bilir. Müşteriler kağıt fatura teslim eder, banka hesap özetleri manuel olarak kontrol edilir ve her ay UJP\'ye rapor sunmak için zamanla yarışılır. Bu zorluklar yalnızca zaman tüketmekle kalmaz, hizmet verebileceğiniz müşteri sayısını da sınırlar.',
        items: [
          'Farklı sistemlerde birden fazla müşteriye hizmet — Excel, kağıt, eski yazılımlar',
          'Banka hesap özetlerinin faturalarla manuel eşleştirilmesi — her hafta saatlerce',
          'Yaklaşan zorunlu e-Fatura — UBL 2.1 hazırlığı ihtiyacı',
          'Ay sonu ve yıl sonu kapanışlarında darboğazlar',
          'Müşteri verilerine uzaktan erişim imkansızlığı',
          'KDV, prim ve bordro hesaplamalarında manuel hata riski',
        ],
        steps: null,
      },
      {
        title: 'Modern muhasebe yazılımı neler sunmalı',
        content: 'Her faturalama yazılımı muhasebe büroları için uygun değildir. Birden fazla şirketi aynı anda yönetmek için inşa edilmiş, manuel çabayı azaltan otomasyonlara ve Makedon mevzuatıyla tam uyumluluğa sahip bir sisteme ihtiyaç vardır.',
        items: [
          'Çoklu şirket panosu — tüm müşterilere tek bir yerden erişim',
          'Banka ekstresi içe aktarma (CSV/MT940/PDF)',
          'UBL 2.1 formatında e-Fatura — yasal gereklilik için hazırlık',
          'Yapay zeka destekli defter kapanışı — otomatik sınıflandırma ve eşleştirme',
          'UFRS ve MK GAAP (Makedon muhasebe standartları) desteği',
          'MPIN, KDV beyannameleri ve yıllık tablolar UJP formatında',
        ],
        steps: null,
      },
      {
        title: 'Facturino her zorluğu nasıl çözüyor',
        content: 'Facturino bir faturalama yazılımından fazlasıdır — muhasebe büroları için eksiksiz bir platformdur. İşte en büyük sorun noktalarını nasıl ele aldığı:',
        items: null,
        steps: [
          { step: 'Tüm müşteriler için tek yer', desc: 'Ortak konsolle, tüm müşteri şirketlerine tek bir girişle erişirsiniz. Artık farklı sistemler arasında geçiş yapmak yok. Her müşterinin kendi faturaları, giderleri, banka hesapları ve raporlarıyla ayrı bir şirketi vardır — ve hepsini tek bir yerden yönetirsiniz.' },
          { step: 'Banka ekstresi içe aktarma', desc: 'Müşteri banka ekstrelerini CSV, MT940 veya PDF formatında içe aktarın. İşlemler hızla içe aktarılır, ödemeler tanınır ve faturalarla eşleştirilir. Müşteri başına haftada 2-3 saat manuel hesap özeti kontrolünden tasarruf edin. PSD2 bağlantısı yakında.' },
          { step: 'e-Fatura hazırlığı', desc: 'Facturino, UBL 2.1 XML formatında faturalar oluşturur, elektronik gönderime hazırdır. e-Fatura zorunlu hale geldiğinde, siz ve müşterileriniz hazır olacaksınız — ek maliyet veya çaba olmadan.' },
          { step: 'Otomatik yıl sonu kapanışı', desc: 'Yıl sonu kapanışı her büro için en stresli dönemdir. Facturino süreci otomatikleştirir — açık kalemlerin gözden geçirilmesinden, işlem sınıflandırmasına, CRMS ve UJP formatında bilanço ve gelir tablosu oluşturmaya kadar.' },
          { step: 'MPIN ve bordro', desc: 'Müşterilerinizin çalışanlarının maaşlarını otomatik brüt/net hesaplama, primler ve kişisel gelir vergisi ile hesaplayın. UJP\'ye sunuma hazır MPIN formları oluşturun — manuel hesaplama yok, hata yok.' },
          { step: 'KDV ve vergi raporları', desc: '%18 ve %5 oranlarında giriş ve çıkış KDV\'sinin otomatik hesaplanması. Aylık ve üç aylık KDV beyannameleri, kurumlar vergisi ve diğer tüm raporlar — UJP uyumlu formatta otomatik olarak oluşturulur.' },
        ],
      },
      {
        title: 'Facturino Ortaklık Programı',
        content: 'Facturino, özellikle muhasebe büroları için tasarlanmış özel bir ortaklık programı sunar. Müşteri yönetimi için güçlü bir araç edinmenin yanı sıra, büyüdükçe kazanırsınız.',
        items: [
          'Her müşteri aboneliğinden %20 aylık komisyon — her yeni müşteriyle büyüyen pasif gelir',
          'Yıllık aboneliklerde %22 komisyon — uzun vadeli müşteriler için daha yüksek kazanç',
          'Ücretsiz üyelik — giriş maliyeti yok, minimum müşteri yok',
          'Komisyonları, müşterileri ve gelirleri takip etmek için ortak portalı',
          'Ortaklar için öncelikli yanıtla özel destek ekibi',
          'Siz ve ekibiniz için ücretsiz eğitim ve onboarding',
        ],
        steps: null,
      },
      {
        title: 'Büronuzu dönüştürmek için üç adım',
        content: 'Facturino\'ya geçiş basittir ve bir günden kısa sürede tamamlanabilir. İşte nasıl:',
        items: null,
        steps: [
          { step: 'Ortak olarak kaydolun', desc: 'Kısa ortak kayıt formunu doldurun. 5 dakikadan kısa sürede müşterilerinizi ekleyebileceğiniz ve tüm şirketlerini yönetebileceğiniz ortak konsoluna erişiminiz olacak.' },
          { step: 'İlk müşterinizi ekleyin', desc: 'İlk müşterinizin şirketini ekleyin, mevcut verileri Excel veya başka bir sistemden içe aktarın ve banka ekstrelerini içe aktarın (CSV/MT940/PDF). 30 dakikadan kısa sürede müşteriniz tamamen kurulmuş olacak.' },
          { step: 'Ölçeklendirin ve kazanın', desc: 'Eklediğiniz her yeni müşteriyle, otomasyonlar sayesinde verimliliğiniz artar ve komisyonunuz her aboneliğin %20\'si kadar büyür. Facturino üzerinden 20+ müşteri yöneten bürolar ayda 40 saatten fazla tasarruf eder.' },
        ],
      },
      {
        title: 'Muhasebeciler neden Facturino\'yu seçiyor',
        content: 'Makedonya genelindeki muhasebe büroları Facturino\'nun avantajlarını zaten deneyimlemektedir. İşte en çok vurguladıkları:',
        items: [
          'Müşteri başına idari işlerde ayda 15-20 saat tasarruf',
          'Otomasyon sayesinde KDV beyannamelerinde ve MPIN formlarında sıfır hata',
          'Ek personel olmadan 3 kat daha fazla müşteri yönetme imkanı',
          '%20 komisyondan pasif gelir — 15+ müşterili bürolar için aylık ortalama 200-500€',
          'Ek yatırım olmadan e-Fatura hazırlığı',
          'Tüm verilere uzaktan erişim — evden veya ofisten çalışma',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
      { slug: 'zosto-facturino', title: 'Makedon işletmelerin Facturino\'yu seçmesinin 10 nedeni' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Yıl sonu kapanışı: Facturino ile 6 adım' },
    ],
    cta: {
      title: 'Muhasebe büronuzu dönüştürün',
      desc: 'Facturino ortağı olun — ücretsiz üyelik, %20 komisyon ve güçlü müşteri yönetim araçları.',
      button: 'Ortak ol',
    },
  },
} as const

export default async function ZaSmetkovoditeliPage({
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
          <a href="https://app.facturino.mk/partner/signup" className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
            {t.cta.button}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </a>
        </div>
      </section>
    </main>
  )
}
