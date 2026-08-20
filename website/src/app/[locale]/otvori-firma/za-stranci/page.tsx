import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/otvori-firma/za-stranci', {
    title: {
      mk: 'Отворање фирма во Македонија за странци (нерезиденти) 2026',
      en: 'Opening a Company in North Macedonia as a Foreigner (Non-Resident) 2026',
      sq: 'Hapja e firmës në Maqedoni për të huajt (jorezidentë) 2026',
      tr: 'Yabancılar (Yerleşik Olmayanlar) için Kuzey Makedonya\'da Şirket Kurma 2026',
    },
    description: {
      mk: 'Може ли странец да отвори фирма во Македонија? Да. Водич за нерезиденти: ДООЕЛ/ДОО, потребни документи, регистрација во ЦРСМ, ЕДБ од УЈП, банкарска сметка, даноци 10% и далечинско управување со Facturino.',
      en: 'Can a foreigner open a company in North Macedonia? Yes. Guide for non-residents: DOOEL/DOO, required documents, registration at CRMS, EDB from UJP, bank account, 10% taxes and remote management with Facturino.',
      sq: 'A mund të hapë një i huaj firmë në Maqedoni? Po. Udhëzues për jorezidentët: SHPKNJP/SHPK, dokumentet e nevojshme, regjistrimi në RQRM, EDB nga DAP, llogaria bankare, tatimet 10% dhe menaxhimi në distancë me Facturino.',
      tr: 'Bir yabancı Kuzey Makedonya\'da şirket kurabilir mi? Evet. Yerleşik olmayanlar için rehber: DOOEL/DOO, gerekli belgeler, CRMS kaydı, UJP\'den EDB, banka hesabı, %10 vergiler ve Facturino ile uzaktan yönetim.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Отвори фирма',
    tag: 'За странци',
    title: 'Отворање фирма во Македонија за странци (нерезиденти)',
    publishDate: '20 август 2026',
    readTime: '8 мин читање',
    intro:
      'Странските претприемачи сè почесто ја избираат Северна Македонија за седиште на бизнис — благодарение на ниските даноци (данок на добивка од само 10%), едноставната регистрација и пристапот до европскиот пазар. Добрата вест: да, странец (нерезидент) може да поседува и основа фирма во Македонија без да биде резидент. Овој водич објаснува кои правни форми се достапни, какви документи се потребни, чекорите за регистрација и даночните обврски. Напомена: барањата за документи, престој и виза варираат и се менуваат — секогаш проверете ги актуелните услови со Централниот регистар (ЦРСМ) и/или адвокат пред да започнете.',
    sections: [
      {
        title: 'Дали странец може да отвори фирма во Македонија?',
        content:
          'Да. Македонското законодавство им дозволува на странски физички и правни лица да основаат и поседуваат трговско друштво под истите услови како и домашните лица. Не е потребно да имате македонско државјанство или постојан престој за да бидете основач или сопственик на фирма. Странците можат да бидат единствени основачи на ДООЕЛ или содружници во ДОО, а исто така можат да бидат и управители на друштвото. Сепак, прашањата поврзани со престој, работна виза и физичко присуство се одделни од сопственоста на фирмата — за нив консултирајте се со адвокат или со надлежните институции.',
        items: null,
        steps: null,
      },
      {
        title: 'Правни форми достапни за странци',
        content:
          'Странците најчесто ги избираат истите правни форми како и домашните претприемачи. Двете најпопуларни се:',
        items: [
          'ДООЕЛ (Друштво со ограничена одговорност на едно лице) — Идеален за еден странски основач. Ограничена одговорност — одговарате само до висината на вложениот капитал, а не со личниот имот. Најчест избор за мали и средни бизниси.',
          'ДОО (Друштво со ограничена одговорност) — За двајца или повеќе основачи, домашни или странски, во било која комбинација. Погоден за партнерства и заеднички вложувања меѓу странски и локални партнери.',
          'И кај ДООЕЛ и кај ДОО одговорноста е ограничена, а книговодството е полно — потребна е редовна сметководствена евиденција и годишна сметка.',
          'Изборот меѓу ДООЕЛ и ДОО зависи од бројот на основачи и структурата на сопственост. За детали видете го нашиот водич „ДООЕЛ, ДОО или ТП?“.',
        ],
        steps: null,
      },
      {
        title: 'Потребни документи за нерезиденти',
        content:
          'Точната листа на документи зависи од тоа дали основачот е физичко или правно лице и се менува со прописите — оваа листа е општа насока. Задолжително проверете ги актуелните барања со ЦРСМ или адвокат:',
        items: [
          'Важечки пасош на странскиот основач (обично со заверен превод на македонски).',
          'Изјава за основање на друштвото, нотарски заверена.',
          'Одлука за именување управител (управителот може да биде и странец).',
          'Адреса на седиште на фирмата во Македонија.',
          'Доказ за уплата на основачки капитал на привремена банкарска сметка.',
          'Ако основач е странско правно лице — извод од регистарот на матичната држава, апостил и заверен превод.',
          'Дополнителни документи може да се бараат во зависност од дејноста — потврдете со ЦРСМ или адвокат.',
        ],
        steps: null,
      },
      {
        title: 'Чекори за регистрација',
        content:
          'Процесот за странци е сличен на оној за домашни основачи. Клучните чекори се:',
        items: null,
        steps: [
          { step: 'Регистрирајте се во Централен регистар (ЦРСМ)', desc: 'Поднесете пријава за основање во Централниот регистар на РСМ. По одобрувањето добивате ЕМБС (единствен матичен број на субјект) — идентификацискиот број на фирмата. Странците често го спроведуваат процесот преку овластен полномошник или адвокат во Македонија.' },
          { step: 'Определете управител и седиште', desc: 'Секое друштво мора да има управител и регистрирана адреса на седиште во Македонија. Управителот може да биде странец. Ако не сте физички присутни, разгледајте ангажирање на локален застапник.' },
          { step: 'Добијте ЕДБ од УЈП', desc: 'По регистрацијата, фирмата мора да добие ЕДБ (единствен даночен број) од Управата за јавни приходи (УЈП). Ова е потребно за фактурирање, даночни пријави и деловно работење.' },
          { step: 'Отворете банкарска сметка', desc: 'Со решението за регистрација и ЕДБ, отворете деловна сметка во банка. За нерезиденти банките може да бараат дополнителна документација и лична проверка — проверете ги условите однапред со избраната банка.' },
        ],
      },
      {
        title: 'Даноци и обврски',
        content:
          'Штом фирмата е основана, важат истите даночни правила како за домашните друштва:',
        items: [
          'Данок на добивка — 10%, една од најниските стапки во Европа. Се пресметува на добивката (приходи минус признати расходи).',
          'ДДВ — задолжителна регистрација кога годишниот промет ќе надмине 2.000.000 МКД. Може и доброволна регистрација порано.',
          'Е-фактура — електронското фактурирање станува задолжително фазно (B2G од октомври 2026). Подгответе се навреме.',
          'Редовно книговодство и годишна сметка — секое друштво води сметководствена евиденција и поднесува годишни финансиски извештаи до УЈП.',
          'Месечни обврски (МПИН, аконтации) доколку имате вработени или исплаќате плата на управителот.',
        ],
        steps: null,
      },
      {
        title: 'Практични совети за странски сопственици',
        content:
          'Управувањето со фирма во Македонија од странство е сосема изводливо со правилна поставеност. Неколку совети:',
        items: [
          'Ангажирајте локален сметководител или адвокат — тие ги знаат актуелните барања на ЦРСМ и УЈП и ќе ве водат низ процесот.',
          'Проверете ги условите за банкарска сметка за нерезиденти однапред — секоја банка има различни барања за идентификација.',
          'Водете го бизнисот на далечина со Facturino — фактурирање, следење на расходи и УЈП извештаи од каде било, со поддршка за е-фактура.',
          'Facturino нуди интерфејс на англиски јазик, што го олеснува работењето за странските сопственици кои не зборуваат македонски.',
          'Секогаш потврдете ги актуелните правни и даночни барања со надлежните институции — прописите се менуваат.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'ДООЕЛ, ДОО или ТП?' },
      { slug: 'paushal-ili-ddv', title: 'Паушалец или ДДВ обврзник?' },
      { slug: 'trgovec-poedinec', title: 'Трговец поединец' },
    ],
    bottomCta: {
      title: 'Управувајте со вашата македонска фирма од каде било',
      subtitle: 'Facturino поддржува далечинско работење, англиски интерфејс и е-фактура. Идеален за странски сопственици.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Start a company',
    tag: 'For Foreigners',
    title: 'Opening a Company in North Macedonia as a Foreigner (Non-Resident)',
    publishDate: 'August 20, 2026',
    readTime: '8 min read',
    intro:
      'Foreign entrepreneurs increasingly choose North Macedonia to base their business — thanks to low taxes (corporate tax of just 10%), simple registration, and access to the European market. The good news: yes, a foreigner (non-resident) can own and found a company in North Macedonia without being a resident. This guide explains which legal forms are available, what documents are required, the registration steps, and the tax obligations. Note: requirements for documents, residence, and visas vary and change over time — always verify the current conditions with the Central Registry (CRMS) and/or a lawyer before you start.',
    sections: [
      {
        title: 'Can a foreigner open a company in North Macedonia?',
        content:
          'Yes. Macedonian law allows foreign natural and legal persons to found and own a commercial company under the same conditions as domestic persons. You do not need Macedonian citizenship or permanent residence to be a founder or owner of a company. Foreigners can be the sole founder of a DOOEL or partners in a DOO, and can also serve as the company\'s manager. However, matters related to residence, work visas, and physical presence are separate from company ownership — for those, consult a lawyer or the relevant institutions.',
        items: null,
        steps: null,
      },
      {
        title: 'Legal forms available to foreigners',
        content:
          'Foreigners most often choose the same legal forms as domestic entrepreneurs. The two most popular are:',
        items: [
          'DOOEL (Single-member LLC) — Ideal for a single foreign founder. Limited liability — you are liable only up to the amount of invested capital, not with personal assets. The most common choice for small and medium businesses.',
          'DOO (Multi-member LLC) — For two or more founders, domestic or foreign, in any combination. Suitable for partnerships and joint ventures between foreign and local partners.',
          'Both DOOEL and DOO offer limited liability and require full bookkeeping — regular accounting records and annual accounts are mandatory.',
          'The choice between DOOEL and DOO depends on the number of founders and the ownership structure. For details, see our guide "DOOEL, DOO or Sole Trader?".',
        ],
        steps: null,
      },
      {
        title: 'Documents required for non-residents',
        content:
          'The exact list of documents depends on whether the founder is a natural or legal person and changes with regulations — this list is general guidance. Be sure to verify the current requirements with CRMS or a lawyer:',
        items: [
          'Valid passport of the foreign founder (usually with a certified translation into Macedonian).',
          'Founding statement of the company, notarized.',
          'Decision on appointing a manager (the manager may also be a foreigner).',
          'Registered office address for the company in North Macedonia.',
          'Proof of payment of share capital to a temporary bank account.',
          'If the founder is a foreign legal entity — an extract from the register of the home country, apostille, and certified translation.',
          'Additional documents may be required depending on the business activity — confirm with CRMS or a lawyer.',
        ],
        steps: null,
      },
      {
        title: 'Registration steps',
        content:
          'The process for foreigners is similar to that for domestic founders. The key steps are:',
        items: null,
        steps: [
          { step: 'Register at the Central Registry (CRMS)', desc: 'Submit a founding application to the Central Registry of North Macedonia. Upon approval, you receive an EMBS (unique entity identification number) — the company\'s ID number. Foreigners often carry out the process through an authorized proxy or a lawyer in North Macedonia.' },
          { step: 'Appoint a manager and registered address', desc: 'Every company must have a manager and a registered office address in North Macedonia. The manager can be a foreigner. If you are not physically present, consider engaging a local representative.' },
          { step: 'Obtain an EDB from UJP', desc: 'After registration, the company must obtain an EDB (unique tax number) from the Public Revenue Office (UJP). This is required for invoicing, tax filings, and business operations.' },
          { step: 'Open a bank account', desc: 'With the registration decision and EDB, open a business bank account. For non-residents, banks may require additional documentation and identity verification — check the conditions in advance with your chosen bank.' },
        ],
      },
      {
        title: 'Taxes and obligations',
        content:
          'Once the company is founded, the same tax rules apply as for domestic companies:',
        items: [
          'Corporate tax — 10%, one of the lowest rates in Europe. Calculated on profit (revenue minus recognized expenses).',
          'VAT — mandatory registration once annual turnover exceeds 2,000,000 MKD. Voluntary registration is also possible earlier.',
          'E-invoice — electronic invoicing is becoming mandatory in phases (B2G from October 2026). Prepare in time.',
          'Regular bookkeeping and annual accounts — every company keeps accounting records and submits annual financial statements to UJP.',
          'Monthly obligations (MPIN, advance payments) if you have employees or pay a salary to the manager.',
        ],
        steps: null,
      },
      {
        title: 'Practical tips for foreign owners',
        content:
          'Managing a company in North Macedonia from abroad is entirely feasible with the right setup. A few tips:',
        items: [
          'Hire a local accountant or lawyer — they know the current CRMS and UJP requirements and will guide you through the process.',
          'Check the conditions for a non-resident bank account in advance — each bank has different identification requirements.',
          'Run the business remotely with Facturino — invoicing, expense tracking, and UJP reports from anywhere, with e-invoice support.',
          'Facturino offers an English-language interface, making it easier for foreign owners who do not speak Macedonian.',
          'Always confirm the current legal and tax requirements with the relevant institutions — regulations change.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO or Sole Trader?' },
      { slug: 'paushal-ili-ddv', title: 'Flat-Rate or VAT Taxpayer?' },
      { slug: 'trgovec-poedinec', title: 'Sole Trader' },
    ],
    bottomCta: {
      title: 'Manage your Macedonian company from anywhere',
      subtitle: 'Facturino supports remote operation, an English interface, and e-invoice. Ideal for foreign owners.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Hap firmë',
    tag: 'Për të huajt',
    title: 'Hapja e firmës në Maqedoni për të huajt (jorezidentë)',
    publishDate: '20 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Sipërmarrësit e huaj gjithnjë e më shpesh zgjedhin Maqedoninë e Veriut për të vendosur biznesin e tyre — falë tatimeve të ulëta (tatimi mbi fitimin vetëm 10%), regjistrimit të thjeshtë dhe qasjes në tregun evropian. Lajmi i mirë: po, një i huaj (jorezident) mund të zotërojë dhe të themelojë një firmë në Maqedoni pa qenë rezident. Ky udhëzues shpjegon cilat forma juridike janë të disponueshme, çfarë dokumentesh nevojiten, hapat e regjistrimit dhe detyrimet tatimore. Vërejtje: kërkesat për dokumente, qëndrim dhe vizë ndryshojnë me kohën — verifikoni gjithmonë kushtet aktuale me Regjistrin Qendror (RQRM) dhe/ose një avokat para se të filloni.',
    sections: [
      {
        title: 'A mund të hapë një i huaj firmë në Maqedoni?',
        content:
          'Po. Legjislacioni maqedonas u lejon personave të huaj fizikë dhe juridikë të themelojnë dhe të zotërojnë një shoqëri tregtare në të njëjtat kushte si personat vendas. Nuk ju nevojitet shtetësia maqedonase apo qëndrimi i përhershëm për të qenë themelues ose pronar i një firme. Të huajt mund të jenë themelues i vetëm i një SHPKNJP ose partnerë në një SHPK, dhe gjithashtu mund të jenë menaxherë të shoqërisë. Megjithatë, çështjet lidhur me qëndrimin, vizat e punës dhe praninë fizike janë të ndara nga pronësia e firmës — për to konsultohuni me një avokat ose me institucionet përkatëse.',
        items: null,
        steps: null,
      },
      {
        title: 'Format juridike të disponueshme për të huajt',
        content:
          'Të huajt zakonisht zgjedhin të njëjtat forma juridike si sipërmarrësit vendas. Dy më të popullarizuarat janë:',
        items: [
          'SHPKNJP (SHPK me një anëtar) — Ideale për një themelues të vetëm të huaj. Përgjegjësi e kufizuar — përgjigjeni vetëm deri në lartësinë e kapitalit të investuar, jo me pasurinë personale. Zgjedhja më e zakonshme për bizneset e vogla dhe të mesme.',
          'SHPK (SHPK me shumë anëtarë) — Për dy ose më shumë themelues, vendas ose të huaj, në çdo kombinim. E përshtatshme për partneritete dhe ndërmarrje të përbashkëta midis partnerëve të huaj dhe vendas.',
          'Si SHPKNJP ashtu edhe SHPK ofrojnë përgjegjësi të kufizuar dhe kërkojnë kontabilitet të plotë — evidenca e rregullt kontabël dhe llogaria vjetore janë të detyrueshme.',
          'Zgjedhja midis SHPKNJP dhe SHPK varet nga numri i themeluesve dhe struktura e pronësisë. Për detaje, shihni udhëzuesin tonë "SHPKNJP, SHPK apo Tregtar individual?".',
        ],
        steps: null,
      },
      {
        title: 'Dokumentet e nevojshme për jorezidentët',
        content:
          'Lista e saktë e dokumenteve varet nëse themeluesi është person fizik apo juridik dhe ndryshon me rregulloret — kjo listë është udhëzim i përgjithshëm. Sigurohuni të verifikoni kërkesat aktuale me RQRM ose një avokat:',
        items: [
          'Pasaportë e vlefshme e themeluesit të huaj (zakonisht me përkthim të certifikuar në maqedonisht).',
          'Deklaratë themelimi e shoqërisë, e noterizuar.',
          'Vendim për emërimin e menaxherit (menaxheri mund të jetë edhe i huaj).',
          'Adresa e regjistruar e selisë së firmës në Maqedoni.',
          'Dëshmi për pagesën e kapitalit themeltar në një llogari të përkohshme bankare.',
          'Nëse themeluesi është person juridik i huaj — ekstrakt nga regjistri i vendit të origjinës, apostilë dhe përkthim i certifikuar.',
          'Dokumente shtesë mund të kërkohen në varësi të veprimtarisë — konfirmoni me RQRM ose një avokat.',
        ],
        steps: null,
      },
      {
        title: 'Hapat e regjistrimit',
        content:
          'Procesi për të huajt është i ngjashëm me atë për themeluesit vendas. Hapat kryesorë janë:',
        items: null,
        steps: [
          { step: 'Regjistrohuni në Regjistrin Qendror (RQRM)', desc: 'Dorëzoni një aplikim themelimi në Regjistrin Qendror të Maqedonisë së Veriut. Pas miratimit merrni EMBS (numrin unik të identifikimit të subjektit) — numrin e identifikimit të firmës. Të huajt shpesh e kryejnë procesin përmes një përfaqësuesi të autorizuar ose një avokati në Maqedoni.' },
          { step: 'Emëroni menaxher dhe adresë selie', desc: 'Çdo shoqëri duhet të ketë menaxher dhe adresë të regjistruar selie në Maqedoni. Menaxheri mund të jetë i huaj. Nëse nuk jeni fizikisht i pranishëm, konsideroni angazhimin e një përfaqësuesi vendor.' },
          { step: 'Merrni EDB nga DAP', desc: 'Pas regjistrimit, firma duhet të marrë EDB (numrin unik tatimor) nga Drejtoria e të Ardhurave Publike (DAP). Kjo nevojitet për faturim, deklarata tatimore dhe operacione biznesi.' },
          { step: 'Hapni llogari bankare', desc: 'Me vendimin e regjistrimit dhe EDB, hapni llogari bankare biznesi. Për jorezidentët, bankat mund të kërkojnë dokumentacion shtesë dhe verifikim identiteti — kontrolloni kushtet paraprakisht me bankën e zgjedhur.' },
        ],
      },
      {
        title: 'Tatimet dhe detyrimet',
        content:
          'Sapo firma të themelohet, vlejnë të njëjtat rregulla tatimore si për shoqëritë vendase:',
        items: [
          'Tatimi mbi fitimin — 10%, një nga normat më të ulëta në Evropë. Llogaritet mbi fitimin (të ardhurat minus shpenzimet e njohura).',
          'TVSH — regjistrimi i detyrueshëm kur qarkullimi vjetor kalon 2.000.000 MKD. E mundshme edhe regjistrimi vullnetar më herët.',
          'E-fatura — faturimi elektronik po bëhet i detyrueshëm me faza (B2G nga tetori 2026). Përgatituni me kohë.',
          'Kontabilitet i rregullt dhe llogari vjetore — çdo shoqëri mban evidencë kontabël dhe dorëzon pasqyra financiare vjetore te DAP.',
          'Detyrime mujore (MPIN, akontacione) nëse keni punëtorë ose paguani pagë menaxherit.',
        ],
        steps: null,
      },
      {
        title: 'Këshilla praktike për pronarët e huaj',
        content:
          'Menaxhimi i një firme në Maqedoni nga jashtë është plotësisht i realizueshëm me konfigurimin e duhur. Disa këshilla:',
        items: [
          'Angazhoni një kontabilist ose avokat vendor — ata i njohin kërkesat aktuale të RQRM dhe DAP dhe do t\'ju udhëzojnë nëpër proces.',
          'Kontrolloni paraprakisht kushtet për llogari bankare jorezidenti — çdo bankë ka kërkesa të ndryshme identifikimi.',
          'Drejtoni biznesin në distancë me Facturino — faturim, ndjekje shpenzimesh dhe raporte DAP nga kudo, me mbështetje për e-faturë.',
          'Facturino ofron një ndërfaqe në gjuhën angleze, duke e bërë më të lehtë për pronarët e huaj që nuk flasin maqedonisht.',
          'Konfirmoni gjithmonë kërkesat aktuale ligjore dhe tatimore me institucionet përkatëse — rregulloret ndryshojnë.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'SHPKNJP, SHPK apo Tregtar individual?' },
      { slug: 'paushal-ili-ddv', title: 'Paushalist apo tatimpagues i TVSH-së?' },
      { slug: 'trgovec-poedinec', title: 'Tregtar individual' },
    ],
    bottomCta: {
      title: 'Menaxhoni firmën tuaj maqedonase nga kudo',
      subtitle: 'Facturino mbështet operimin në distancë, ndërfaqe në anglisht dhe e-faturë. Ideal për pronarët e huaj.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Şirket kur',
    tag: 'Yabancılar için',
    title: 'Yabancılar (Yerleşik Olmayanlar) için Kuzey Makedonya\'da Şirket Kurma',
    publishDate: '20 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Yabancı girişimciler, işlerini kurmak için giderek daha çok Kuzey Makedonya\'yı seçiyor — düşük vergiler (sadece %10 kurumlar vergisi), basit kayıt ve Avrupa pazarına erişim sayesinde. İyi haber: evet, bir yabancı (yerleşik olmayan), yerleşik olmadan Kuzey Makedonya\'da bir şirkete sahip olabilir ve kurabilir. Bu rehber hangi hukuki biçimlerin mevcut olduğunu, hangi belgelerin gerektiğini, kayıt adımlarını ve vergi yükümlülüklerini açıklar. Not: Belge, ikamet ve vize gereksinimleri değişir ve zamanla güncellenir — başlamadan önce güncel koşulları her zaman Merkez Sicil (CRMS) ve/veya bir avukatla doğrulayın.',
    sections: [
      {
        title: 'Bir yabancı Kuzey Makedonya\'da şirket kurabilir mi?',
        content:
          'Evet. Makedon mevzuatı, yabancı gerçek ve tüzel kişilerin yerli kişilerle aynı koşullarda ticari şirket kurmasına ve sahip olmasına izin verir. Bir şirketin kurucusu veya sahibi olmak için Makedon vatandaşlığına ya da daimi ikamete ihtiyacınız yoktur. Yabancılar bir DOOEL\'in tek kurucusu veya bir DOO\'da ortak olabilir ve ayrıca şirketin müdürü olarak görev yapabilir. Ancak ikamet, çalışma vizesi ve fiziksel varlıkla ilgili konular şirket sahipliğinden ayrıdır — bunlar için bir avukata veya ilgili kurumlara danışın.',
        items: null,
        steps: null,
      },
      {
        title: 'Yabancılara açık hukuki biçimler',
        content:
          'Yabancılar çoğunlukla yerli girişimcilerle aynı hukuki biçimleri seçer. En popüler ikisi:',
        items: [
          'DOOEL (Tek ortaklı limited şirket) — Tek bir yabancı kurucu için idealdir. Sınırlı sorumluluk — yalnızca yatırılan sermaye tutarına kadar sorumlusunuz, kişisel varlıklarla değil. Küçük ve orta ölçekli işletmeler için en yaygın seçim.',
          'DOO (Çok ortaklı limited şirket) — İki veya daha fazla kurucu için, yerli veya yabancı, herhangi bir kombinasyonda. Yabancı ve yerel ortaklar arasındaki ortaklıklar ve ortak girişimler için uygundur.',
          'Hem DOOEL hem DOO sınırlı sorumluluk sunar ve tam muhasebe gerektirir — düzenli muhasebe kayıtları ve yıllık hesaplar zorunludur.',
          'DOOEL ile DOO arasındaki seçim, kurucu sayısına ve sahiplik yapısına bağlıdır. Ayrıntılar için "DOOEL, DOO veya Şahıs şirketi?" rehberimize bakın.',
        ],
        steps: null,
      },
      {
        title: 'Yerleşik olmayanlar için gerekli belgeler',
        content:
          'Belgelerin tam listesi, kurucunun gerçek mi yoksa tüzel kişi mi olduğuna bağlıdır ve mevzuatla değişir — bu liste genel bir rehberdir. Güncel gereksinimleri CRMS veya bir avukatla doğruladığınızdan emin olun:',
        items: [
          'Yabancı kurucunun geçerli pasaportu (genellikle Makedoncaya onaylı çeviriyle).',
          'Şirketin kuruluş beyanı, noter tasdikli.',
          'Müdür atama kararı (müdür de yabancı olabilir).',
          'Şirket için Kuzey Makedonya\'da kayıtlı merkez ofis adresi.',
          'Sermayenin geçici banka hesabına ödendiğine dair kanıt.',
          'Kurucu yabancı tüzel kişiyse — menşe ülkenin sicilinden bir kayıt örneği, apostil ve onaylı çeviri.',
          'Faaliyete bağlı olarak ek belgeler istenebilir — CRMS veya bir avukatla teyit edin.',
        ],
        steps: null,
      },
      {
        title: 'Kayıt adımları',
        content:
          'Yabancılar için süreç, yerli kurucularınkine benzer. Temel adımlar şunlardır:',
        items: null,
        steps: [
          { step: 'Merkez Sicile (CRMS) kaydolun', desc: 'Kuzey Makedonya Merkez Siciline bir kuruluş başvurusu sunun. Onay üzerine EMBS (benzersiz kuruluş kimlik numarası) alırsınız — şirketin kimlik numarası. Yabancılar süreci genellikle Kuzey Makedonya\'da yetkili bir vekil veya avukat aracılığıyla yürütür.' },
          { step: 'Müdür ve kayıtlı adres atayın', desc: 'Her şirketin Kuzey Makedonya\'da bir müdürü ve kayıtlı merkez ofis adresi olmalıdır. Müdür yabancı olabilir. Fiziksel olarak hazır değilseniz, yerel bir temsilci görevlendirmeyi düşünün.' },
          { step: 'UJP\'den EDB alın', desc: 'Kayıttan sonra şirket, Kamu Gelir İdaresi\'nden (UJP) EDB (benzersiz vergi numarası) almalıdır. Bu, faturalama, vergi beyanları ve iş faaliyetleri için gereklidir.' },
          { step: 'Banka hesabı açın', desc: 'Kayıt kararı ve EDB ile ticari banka hesabı açın. Yerleşik olmayanlar için bankalar ek belge ve kimlik doğrulaması isteyebilir — koşulları seçtiğiniz bankayla önceden kontrol edin.' },
        ],
      },
      {
        title: 'Vergiler ve yükümlülükler',
        content:
          'Şirket kurulduğunda, yerli şirketlerle aynı vergi kuralları geçerlidir:',
        items: [
          'Kurumlar vergisi — %10, Avrupa\'daki en düşük oranlardan biri. Kâr üzerinden hesaplanır (gelir eksi kabul edilen giderler).',
          'KDV — yıllık ciro 2.000.000 MKD\'yi aştığında zorunlu kayıt. Daha erken gönüllü kayıt da mümkündür.',
          'E-fatura — elektronik faturalama aşamalı olarak zorunlu hale geliyor (B2G Ekim 2026\'dan). Zamanında hazırlanın.',
          'Düzenli muhasebe ve yıllık hesaplar — her şirket muhasebe kaydı tutar ve UJP\'ye yıllık mali tablolar sunar.',
          'Çalışanınız varsa veya müdüre maaş ödüyorsanız aylık yükümlülükler (MPIN, avans ödemeleri).',
        ],
        steps: null,
      },
      {
        title: 'Yabancı sahipler için pratik ipuçları',
        content:
          'Kuzey Makedonya\'daki bir şirketi yurt dışından yönetmek, doğru kurulumla tamamen mümkündür. Birkaç ipucu:',
        items: [
          'Yerel bir muhasebeci veya avukat tutun — güncel CRMS ve UJP gereksinimlerini bilirler ve sizi süreç boyunca yönlendirirler.',
          'Yerleşik olmayan banka hesabı koşullarını önceden kontrol edin — her bankanın farklı kimlik gereksinimleri vardır.',
          'İşinizi Facturino ile uzaktan yönetin — her yerden faturalama, gider takibi ve UJP raporları, e-fatura desteğiyle.',
          'Facturino, Makedonca konuşmayan yabancı sahipler için işleri kolaylaştıran İngilizce bir arayüz sunar.',
          'Güncel yasal ve vergi gereksinimlerini her zaman ilgili kurumlarla teyit edin — mevzuat değişir.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO veya Şahıs şirketi?' },
      { slug: 'paushal-ili-ddv', title: 'Düz Oranlı mı KDV Mükellefi mi?' },
      { slug: 'trgovec-poedinec', title: 'Şahıs şirketi' },
    ],
    bottomCta: {
      title: 'Makedon şirketinizi her yerden yönetin',
      subtitle: 'Facturino uzaktan çalışmayı, İngilizce arayüzü ve e-faturayı destekler. Yabancı sahipler için ideal.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function OtvoriFirmaZaStranciPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const hubLabel = locale === 'mk' ? 'Отвори фирма' : locale === 'sq' ? 'Hapja e firmës' : locale === 'tr' ? 'Şirket kurma' : 'Start a Company'
  const articleLd = articleJsonLd({
    locale,
    pathPrefix: 'otvori-firma',
    slug: 'za-stranci',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-20',
    tags: ['странци', 'foreigners', 'non-resident', 'company registration', 'ДООЕЛ', 'north macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: hubLabel, href: `/${locale}/otvori-firma` },
    { name: t.title, href: `/${locale}/otvori-firma/za-stranci` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Дали странец може да отвори фирма во Македонија?', answer: 'Да. Странски физички и правни лица можат да основаат и поседуваат фирма (ДООЕЛ или ДОО) во Македонија под истите услови како домашните лица, без потреба од државјанство или постојан престој.' },
        { question: 'Која правна форма е најдобра за странец?', answer: 'Најчесто се избираат ДООЕЛ (за еден основач) и ДОО (за двајца или повеќе основачи). И двете нудат ограничена одговорност — личниот имот е заштитен.' },
        { question: 'Какви документи се потребни за нерезидент?', answer: 'Општо: важечки пасош, нотарски заверена изјава за основање, одлука за именување управител, адреса на седиште и доказ за уплата на капитал. Точната листа се менува — проверете со ЦРСМ или адвокат.' },
        { question: 'Колку е данокот на добивка во Македонија?', answer: 'Данокот на добивка е 10%, една од најниските стапки во Европа. ДДВ регистрацијата е задолжителна кога прометот ќе надмине 2.000.000 МКД.' },
        { question: 'Може ли да управувам со фирмата од странство?', answer: 'Да. Со локален сметководител и алатка како Facturino (со англиски интерфејс и поддршка за е-фактура) можете да фактурирате и да поднесувате извештаи до УЈП од каде било.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/otvori-firma`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
            {t.backLink}
          </Link>

          <article>
            <header className="mb-10">
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                {t.tag}
              </span>
              <h1 className="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 leading-tight">
                {t.title}
              </h1>
              <p className="text-sm text-gray-500">
                {t.publishDate} · {t.readTime}
              </p>
            </header>

            <div className="prose prose-lg max-w-none">
              <p className="text-lg text-gray-700 leading-relaxed mb-8">{t.intro}</p>

              {t.sections.map((s, i) => (
                <section key={i} className="mb-10">
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">{s.title}</h2>
                  <p className="text-gray-700 leading-relaxed mb-4">{s.content}</p>

                  {s.items && (
                    <ul className="space-y-2 mb-4">
                      {s.items.map((item, j) => (
                        <li key={j} className="flex items-start gap-2">
                          <span className="text-blue-500 mt-1.5 text-xs">●</span>
                          <span className="text-gray-700">{item}</span>
                        </li>
                      ))}
                    </ul>
                  )}

                  {s.steps && (
                    <div className="space-y-4 mb-4">
                      {s.steps.map((step, j) => (
                        <div key={j} className="flex items-start gap-3">
                          <span className="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-sm font-bold">
                            {j + 1}
                          </span>
                          <div>
                            <p className="font-semibold text-gray-900">{step.step}</p>
                            <p className="text-gray-600 text-sm mt-1">{step.desc}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </section>
              ))}
            </div>

            <aside className="mt-12 pt-8 border-t border-gray-200">
              <h3 className="text-lg font-bold text-gray-900 mb-4">{t.relatedTitle}</h3>
              <div className="grid gap-3">
                {t.relatedArticles.map((ra, i) => (
                  <Link
                    key={i}
                    href={`/${locale}/otvori-firma/${ra.slug}`}
                    className="text-blue-600 hover:text-blue-800 hover:underline"
                  >
                    {ra.title}
                  </Link>
                ))}
              </div>
            </aside>
          </article>

          <div className="mt-16 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl p-8 sm:p-12 text-center text-white">
            <h2 className="text-2xl sm:text-3xl font-extrabold mb-3">{t.bottomCta.title}</h2>
            <p className="text-blue-100 mb-6 text-lg">{t.bottomCta.subtitle}</p>
            <a
              href={t.bottomCta.href}
              className="inline-block bg-white text-blue-700 font-bold px-8 py-3.5 rounded-xl hover:bg-blue-50 transition-colors text-lg shadow-lg"
            >
              {t.bottomCta.cta}
            </a>
          </div>
        </div>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
