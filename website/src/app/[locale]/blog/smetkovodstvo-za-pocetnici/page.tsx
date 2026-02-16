import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/smetkovodstvo-za-pocetnici', {
    title: {
      mk: 'Сметководство за почетници: Основи што секој бизнис ги знае | Facturino',
      en: 'Accounting for Beginners: Basics Every Business Should Know | Facturino',
      sq: 'Kontabiliteti për fillestarë: Bazat që çdo biznes duhet t\'i dijë | Facturino',
      tr: 'Yeni başlayanlar için muhasebe: Her işletmenin bilmesi gerekenler | Facturino',
    },
    description: {
      mk: 'Научете ги основите на сметководството: двојно книговодство, контен план, фактурирање, расходи, биланс на успех и биланс на состојба за мали бизниси.',
      en: 'Learn accounting basics: double-entry bookkeeping, chart of accounts, invoicing, expenses, profit and loss, and balance sheet concepts for small businesses.',
      sq: 'Mësoni bazat e kontabilitetit: regjistrimi i dyfishtë, plani kontabël, faturimi, shpenzimet, fitimi dhe humbja, dhe bilanci për bizneset e vogla.',
      tr: 'Muhasebe temellerini öğrenin: çift taraflı kayıt, hesap planı, faturalama, giderler, kar-zarar ve küçük işletmeler için bilanço kavramları.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Едукација',
    title: 'Сметководство за почетници: Основи што секој бизнис ги знае',
    publishDate: '12 февруари 2026',
    readTime: '8 мин читање',
    intro: 'Сметководството е јазикот на бизнисот. Без разлика дали сте фриленсер, сопственик на мал бизнис или штотуку регистриравте фирма, разбирањето на основите на сметководството е клучно за успешно водење на работите. Не треба да бидете експерт — но треба да ги знаете основните концепти за да можете да донесувате информирани одлуки, да комуницирате со вашиот сметководител и да го следите здравјето на вашиот бизнис.',
    sections: [
      {
        title: 'Што е двојно книговодство и зошто е важно',
        content: 'Двојното книговодство е основата на модерното сметководство. Принципот е едноставен: секоја деловна трансакција се евидентира на најмалку две места — на дебитна и на кредитна страна. Ако продадете услуга за 10.000 МКД, тоа се евидентира како приход (кредит) и како побарување од клиентот или готовина (дебит). Збирот на сите дебити секогаш мора да биде еднаков на збирот на сите кредити — ова е основното правило кое обезбедува точност на книгите. За мали бизниси во Македонија, двојното книговодство е законска обврска за сите правни лица (ДООЕЛ, ДОО). Единствено трговците поединци со паушално оданочување се ослободени од оваа обврска.',
        items: null,
        steps: null,
      },
      {
        title: 'Контен план: Мапата на вашите финансии',
        content: 'Контниот план (chart of accounts) е листа на сите сметки кои ги користите за да ги класифицирате финансиските трансакции. Тој е организиран во категории и секоја сметка има број и име.',
        items: [
          'Класа 0-3: Средства (актива) — она што го поседува фирмата: готовина, побарувања, опрема, залихи.',
          'Класа 4: Обврски (пасива) — она што фирмата го должи: кредити, обврски кон добавувачи, даноци.',
          'Класа 5: Капитал — основачки капитал и акумулирана добивка.',
          'Класа 6: Расходи — трошоци за работење: плати, кирии, материјали, услуги.',
          'Класа 7: Приходи — приходи од продажба на производи и услуги.',
          'Класа 8-9: Вонбилансни и затворачки сметки — специјални евиденции за крај на година.',
        ],
        steps: null,
      },
      {
        title: 'Фактурирање и расходи: Двата столба',
        content: 'Фактурирањето и следењето на расходите се двата основни столба на секојдневното сметководство. Секој приход мора да биде поткрепен со фактура, а секој расход мора да има документ (фактура, сметка, потврда). Во Македонија, фактурата мора да содржи задолжителни елементи: име и ЕДБ на издавачот и примачот, датум, опис на услугата или производот, количина, единечна цена, вкупна сума, и ДДВ ако сте обврзник. Расходите мора да бидат поврзани со деловната активност за да бидат признаени од УЈП. Приватни трошоци не можат да се одбијат од даночната основица.',
        items: [
          'Секоја фактура мора да има уникатен реден број.',
          'Рокот за плаќање мора да биде наведен на фактурата.',
          'Фактурите мора да се чуваат минимум 10 години.',
          'Расходите без документ не се признаваат од УЈП.',
          'Мешањето на приватни и деловни расходи е една од најчестите грешки.',
        ],
        steps: null,
      },
      {
        title: 'Биланс на успех и биланс на состојба',
        content: 'Овие два извештаја се основните финансиски извештаи кои секој бизнис мора да ги разбере. Тие ја раскажуваат приказната за финансиското здравје на фирмата.',
        items: null,
        steps: [
          { step: 'Биланс на успех (Profit & Loss)', desc: 'Го покажува финансискиот резултат за одреден период — обично месец, квартал или година. Едноставно кажано: приходи минус расходи = добивка (или загуба). Ако вашите приходи за месецот се 500.000 МКД, а расходите 350.000 МКД, вашата добивка е 150.000 МКД. На оваа добивка се плаќа данок на добивка од 10%.' },
          { step: 'Биланс на состојба (Balance Sheet)', desc: 'Ја покажува финансиската состојба на фирмата во одреден момент. Се состои од три дела: средства (актива) = обврски (пасива) + капитал. Средствата се она што го поседувате (пари, опрема, побарувања). Обврските се она што должите (кредити, долгови кон добавувачи). Капиталот е разликата — нето-вредноста на фирмата.' },
          { step: 'Готовински тек (Cash Flow)', desc: 'Го следи движењето на парите — колку влегуваат и колку излегуваат. Фирма може да биде профитабилна на хартија, но да нема готовина за плаќање на сметките. Затоа следењето на готовинскиот тек е критично, особено за нови бизниси.' },
        ],
      },
      {
        title: 'Кога да ангажирате сметководител',
        content: 'Секој бизнис во Македонија е обврзан да води книговодство, но не секој бизнис треба да ангажира сметководител од првиот ден. Еве ги знаците дека е време да побарате професионална помош:',
        items: [
          'Имате повеќе од 10-20 трансакции месечно — обемот станува тежок за самостојно водење.',
          'Сте регистрирани за ДДВ — ДДВ пресметките бараат прецизност и познавање на правилата.',
          'Имате вработени — МПИН пријавите и пресметките за плати се комплексни.',
          'Приближувате се до крајот на годината — годишната сметка и ДБ-ВП бараат стручно знаење.',
          'Растете брзо — кога обемот расте, грешките стануваат поскапи.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го олеснува сметководството',
        content: 'Facturino е создаден да ја премости јазот меѓу целосното сметководствено знаење и потребите на малите бизниси. Не мора да бидете сметководител за да го користите — системот автоматски ги класифицира трансакциите, ја пресметува ДДВ обврската и генерира извештаи кои вашиот сметководител или вие самите можете да ги користите.',
        items: [
          'Автоматска класификација на приходи и расходи според македонскиот контен план.',
          'Генерирање на биланс на успех и биланс на состојба во реално време.',
          'ДДВ пресметки и извештаи подготвени за поднесување до УЈП.',
          'Споделување со сметководител — дадете пристап без да испраќате папки.',
          'Извоз на податоци во формат компатибилен со популарните сметководствени софтвери.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Сметководството не мора да биде тешко',
      desc: 'Facturino ги автоматизира основните сметководствени задачи. Вие се фокусирате на бизнисот, ние на бројките.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Education',
    title: 'Accounting for Beginners: Basics Every Business Should Know',
    publishDate: 'February 12, 2026',
    readTime: '8 min read',
    intro: 'Accounting is the language of business. Whether you are a freelancer, small business owner, or have just registered a company, understanding accounting basics is key to running a successful operation. You do not need to be an expert — but you should know the core concepts so you can make informed decisions, communicate with your accountant, and monitor the health of your business.',
    sections: [
      {
        title: 'What is double-entry bookkeeping and why it matters',
        content: 'Double-entry bookkeeping is the foundation of modern accounting. The principle is simple: every business transaction is recorded in at least two places — on a debit side and a credit side. If you sell a service for 10,000 MKD, it is recorded as revenue (credit) and as a receivable from the client or cash (debit). The sum of all debits must always equal the sum of all credits — this is the fundamental rule that ensures accuracy of the books. For small businesses in Macedonia, double-entry bookkeeping is a legal requirement for all legal entities (DOOEL, DOO). Only sole traders with lump-sum taxation are exempt from this obligation.',
        items: null,
        steps: null,
      },
      {
        title: 'Chart of accounts: Your financial map',
        content: 'The chart of accounts is a list of all accounts used to classify financial transactions. It is organized into categories, and each account has a number and a name.',
        items: [
          'Class 0-3: Assets — what the company owns: cash, receivables, equipment, inventory.',
          'Class 4: Liabilities — what the company owes: loans, payables to suppliers, taxes.',
          'Class 5: Equity — founding capital and accumulated profit.',
          'Class 6: Expenses — operating costs: salaries, rent, materials, services.',
          'Class 7: Revenue — income from sales of products and services.',
          'Class 8-9: Off-balance and closing accounts — special records for year-end.',
        ],
        steps: null,
      },
      {
        title: 'Invoicing and expenses: The two pillars',
        content: 'Invoicing and expense tracking are the two fundamental pillars of day-to-day accounting. Every income must be backed by an invoice, and every expense must have a document (invoice, receipt, confirmation). In Macedonia, an invoice must contain mandatory elements: name and EDB of the issuer and recipient, date, description of the service or product, quantity, unit price, total amount, and VAT if you are a VAT taxpayer. Expenses must be related to business activity to be recognized by UJP. Personal costs cannot be deducted from the tax base.',
        items: [
          'Each invoice must have a unique sequential number.',
          'The payment deadline must be stated on the invoice.',
          'Invoices must be stored for a minimum of 10 years.',
          'Expenses without documentation are not recognized by UJP.',
          'Mixing personal and business expenses is one of the most common mistakes.',
        ],
        steps: null,
      },
      {
        title: 'Profit and loss statement and balance sheet',
        content: 'These two reports are the fundamental financial statements every business must understand. They tell the story of your company\'s financial health.',
        items: null,
        steps: [
          { step: 'Profit & Loss Statement', desc: 'Shows the financial result for a given period — usually a month, quarter, or year. Simply put: revenue minus expenses equals profit (or loss). If your monthly revenue is 500,000 MKD and expenses are 350,000 MKD, your profit is 150,000 MKD. Corporate income tax of 10% is paid on this profit.' },
          { step: 'Balance Sheet', desc: 'Shows the financial position of the company at a specific point in time. It consists of three parts: assets = liabilities + equity. Assets are what you own (cash, equipment, receivables). Liabilities are what you owe (loans, payables to suppliers). Equity is the difference — the net worth of the company.' },
          { step: 'Cash Flow Statement', desc: 'Tracks the movement of cash — how much comes in and how much goes out. A company can be profitable on paper but have no cash to pay its bills. That is why cash flow monitoring is critical, especially for new businesses.' },
        ],
      },
      {
        title: 'When to hire an accountant',
        content: 'Every business in Macedonia is required to keep books, but not every business needs to hire an accountant from day one. Here are the signs that it is time to seek professional help:',
        items: [
          'You have more than 10-20 transactions per month — the volume becomes hard to manage on your own.',
          'You are VAT-registered — VAT calculations require precision and knowledge of the rules.',
          'You have employees — MPIN returns and payroll calculations are complex.',
          'Year-end is approaching — annual accounts and the DB-VP return require expert knowledge.',
          'You are growing fast — as volume grows, mistakes become more costly.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino simplifies accounting',
        content: 'Facturino was built to bridge the gap between full accounting knowledge and the needs of small businesses. You do not need to be an accountant to use it — the system automatically classifies transactions, calculates VAT liability, and generates reports that you or your accountant can use.',
        items: [
          'Automatic classification of income and expenses according to the Macedonian chart of accounts.',
          'Real-time generation of profit and loss and balance sheet reports.',
          'VAT calculations and reports ready for submission to UJP.',
          'Share access with your accountant — no need to send folders.',
          'Data export in formats compatible with popular accounting software.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Accounting does not have to be hard',
      desc: 'Facturino automates core accounting tasks. You focus on the business, we handle the numbers.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Edukim',
    title: 'Kontabiliteti për fillestarë: Bazat që çdo biznes duhet t\'i dijë',
    publishDate: '12 shkurt 2026',
    readTime: '8 min lexim',
    intro: 'Kontabiliteti është gjuha e biznesit. Pavarësisht nëse jeni punëtor i pavarur, pronar i një biznesi të vogël ose sapo keni regjistruar një kompani, kuptimi i bazave të kontabilitetit është çelësi për menaxhimin e suksesshëm të punëve. Nuk keni nevojë të jeni ekspert — por duhet t\'i dini konceptet themelore që të mund të merrni vendime të informuara, të komunikoni me kontabilistin tuaj dhe të ndiqni shëndetin e biznesit tuaj.',
    sections: [
      {
        title: 'Çfarë është regjistrimi i dyfishtë dhe pse ka rëndësi',
        content: 'Regjistrimi i dyfishtë është themeli i kontabilitetit modern. Parimi është i thjeshtë: çdo transaksion biznesi regjistrohet në së paku dy vende — në anën e debitit dhe në anën e kreditit. Nëse shisni një shërbim për 10.000 MKD, regjistrohet si e ardhur (kredit) dhe si kërkesë ndaj klientit ose para të gatshme (debit). Shuma e të gjitha debiteve duhet gjithmonë të jetë e barabartë me shumën e të gjitha krediteve — ky është rregulli themelor që siguron saktësinë e librave. Për bizneset e vogla në Maqedoni, regjistrimi i dyfishtë është detyrim ligjor për të gjitha subjektet juridike (DOOEL, DOO). Vetëm tregtarët individualë me tatim të përgjithshëm janë të përjashtuar nga ky detyrim.',
        items: null,
        steps: null,
      },
      {
        title: 'Plani kontabël: Harta e financave tuaja',
        content: 'Plani kontabël është një listë e të gjitha llogarive të përdorura për të klasifikuar transaksionet financiare. Është i organizuar në kategori, dhe çdo llogari ka një numër dhe emër.',
        items: [
          'Klasa 0-3: Asetet — çfarë zotëron kompania: para të gatshme, kërkesa, pajisje, inventar.',
          'Klasa 4: Detyrimet — çfarë kompania detyron: kredi, detyrime ndaj furnizuesve, tatime.',
          'Klasa 5: Kapitali — kapitali themeltar dhe fitimi i akumuluar.',
          'Klasa 6: Shpenzimet — kostot operative: paga, qira, materiale, shërbime.',
          'Klasa 7: Të ardhurat — të ardhura nga shitja e produkteve dhe shërbimeve.',
          'Klasa 8-9: Llogaritë jashtëbilanciale dhe mbyllëse — evidenca speciale për fund-vitin.',
        ],
        steps: null,
      },
      {
        title: 'Faturimi dhe shpenzimet: Dy shtyllat',
        content: 'Faturimi dhe ndjekja e shpenzimeve janë dy shtyllat themelore të kontabilitetit të përditshëm. Çdo e ardhur duhet të mbështetet me faturë, dhe çdo shpenzim duhet të ketë dokument (faturë, faturinë, konfirmim). Në Maqedoni, fatura duhet të përmbajë elementë të detyrueshme: emrin dhe EDB të lëshuesit dhe marrësit, datën, përshkrimin e shërbimit ose produktit, sasinë, çmimin për njësi, shumën totale dhe TVSH nëse jeni tatimpagues i TVSH-së. Shpenzimet duhet të jenë të lidhura me aktivitetin afarist për t\'u njohur nga UJP. Kostot personale nuk mund të zbriten nga baza tatimore.',
        items: [
          'Çdo faturë duhet të ketë numër unik rendor.',
          'Afati i pagesës duhet të shënohet në faturë.',
          'Faturat duhet të ruhen minimalisht 10 vjet.',
          'Shpenzimet pa dokumentacion nuk njihen nga UJP.',
          'Përzierja e shpenzimeve personale dhe atyre të biznesit është një nga gabimet më të zakonshme.',
        ],
        steps: null,
      },
      {
        title: 'Pasqyra e fitimit dhe humbjes dhe bilanci',
        content: 'Këto dy raporte janë pasqyrat themelore financiare që çdo biznes duhet t\'i kuptojë. Ato tregojnë historinë e shëndetit financiar të kompanisë suaj.',
        items: null,
        steps: [
          { step: 'Pasqyra e fitimit dhe humbjes (Profit & Loss)', desc: 'Tregon rezultatin financiar për një periudhë të caktuar — zakonisht muaj, tremujor ose vit. Thjesht: të ardhurat minus shpenzimet = fitimi (ose humbja). Nëse të ardhurat tuaja mujore janë 500.000 MKD dhe shpenzimet 350.000 MKD, fitimi juaj është 150.000 MKD. Tatimi mbi fitimin prej 10% paguhet mbi këtë fitim.' },
          { step: 'Bilanci (Balance Sheet)', desc: 'Tregon pozitën financiare të kompanisë në një moment të caktuar. Përbëhet nga tri pjesë: asetet = detyrimet + kapitali. Asetet janë çfarë zotëroni (para, pajisje, kërkesa). Detyrimet janë çfarë detyroni (kredi, detyrime ndaj furnizuesve). Kapitali është diferenca — vlera neto e kompanisë.' },
          { step: 'Pasqyra e rrjedhës së parasë (Cash Flow)', desc: 'Ndjek lëvizjen e parasë — sa hyn dhe sa del. Një kompani mund të jetë fitimprurëse në letër por të mos ketë para për të paguar faturat. Prandaj ndjekja e rrjedhës së parasë është kritike, veçanërisht për bizneset e reja.' },
        ],
      },
      {
        title: 'Kur të angazhoni kontabilist',
        content: 'Çdo biznes në Maqedoni detyrohet të mbajë libra, por jo çdo biznes ka nevojë të angazhojë kontabilist nga dita e parë. Ja shenjat që është koha të kërkoni ndihmë profesionale:',
        items: [
          'Keni më shumë se 10-20 transaksione në muaj — vëllimi bëhet i vështirë për menaxhim vetanak.',
          'Jeni regjistruar për TVSH — llogaritjet e TVSH-së kërkojnë saktësi dhe njohuri të rregullave.',
          'Keni punonjës — deklaratat MPIN dhe llogaritjet e pagave janë komplekse.',
          'Fund-viti po afrohet — llogaritë vjetore dhe deklarata DB-VP kërkojnë njohuri ekspertize.',
          'Po rriteni shpejt — kur vëllimi rritet, gabimet bëhen më të kushtueshme.',
        ],
        steps: null,
      },
      {
        title: 'Si e thjeshton Facturino kontabilitetin',
        content: 'Facturino u ndërtua për të uruar hendekun midis njohurive të plota kontabël dhe nevojave të bizneseve të vogla. Nuk keni nevojë të jeni kontabilist për ta përdorur — sistemi automatikisht klasifikon transaksionet, llogarit detyrimet e TVSH-së dhe gjeneron raporte që ju ose kontabilisti juaj mund t\'i përdorni.',
        items: [
          'Klasifikim automatik i të ardhurave dhe shpenzimeve sipas planit kontabël maqedonas.',
          'Gjenerim në kohë reale i pasqyrave të fitimit dhe humbjes dhe bilancit.',
          'Llogaritje dhe raporte TVSH-je të gatshme për dorëzim në UJP.',
          'Ndani aksesin me kontabilistin — pa nevojë për të dërguar dosje.',
          'Eksport i të dhënave në formate të përputhshme me softueret e njohura kontabël.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Kontabiliteti nuk duhet të jetë i vështirë',
      desc: 'Facturino automatizon detyrat themelore kontabël. Ju fokusoheni te biznesi, ne kujdesemi për numrat.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Eğitim',
    title: 'Yeni başlayanlar için muhasebe: Her işletmenin bilmesi gerekenler',
    publishDate: '12 Şubat 2026',
    readTime: '8 dk okuma',
    intro: 'Muhasebe, iş dünyasının dilidir. İster serbest çalışan, ister küçük işletme sahibi olun ya da yeni bir şirket kurmuş olun, muhasebe temellerini anlamak başarılı bir iş yürütmenin anahtarıdır. Uzman olmanıza gerek yok — ancak bilinçli kararlar verebilmeniz, muhasebecinizle iletişim kurabilmeniz ve işletmenizin sağlığını izleyebilmeniz için temel kavramları bilmelisiniz.',
    sections: [
      {
        title: 'Çift taraflı kayıt nedir ve neden önemlidir',
        content: 'Çift taraflı kayıt, modern muhasebenin temelidir. İlke basittir: her ticari işlem en az iki yere — borç ve alacak tarafına — kaydedilir. 10.000 MKD\'lik bir hizmet satarsanız, bu gelir (alacak) ve müşteriden alacak veya nakit (borç) olarak kaydedilir. Tüm borçların toplamı her zaman tüm alacakların toplamına eşit olmalıdır — bu, defterlerin doğruluğunu sağlayan temel kuraldır. Makedonya\'daki küçük işletmeler için çift taraflı kayıt, tüm tüzel kişiler (DOOEL, DOO) için yasal bir gerekliliktir. Yalnızca götürü vergilendirme yapan şahıs tüccarları bu yükümlülükten muaftır.',
        items: null,
        steps: null,
      },
      {
        title: 'Hesap planı: Finansal haritanız',
        content: 'Hesap planı, finansal işlemleri sınıflandırmak için kullanılan tüm hesapların listesidir. Kategoriler halinde düzenlenmiştir ve her hesabın bir numarası ve adı vardır.',
        items: [
          'Sınıf 0-3: Varlıklar — şirketin sahip olduğu: nakit, alacaklar, ekipman, envanter.',
          'Sınıf 4: Yükümlülükler — şirketin borçlu olduğu: krediler, tedarikçilere borçlar, vergiler.',
          'Sınıf 5: Öz sermaye — kuruluş sermayesi ve birikmiş kar.',
          'Sınıf 6: Giderler — işletme maliyetleri: maaşlar, kira, malzeme, hizmetler.',
          'Sınıf 7: Gelir — ürün ve hizmet satışından elde edilen gelir.',
          'Sınıf 8-9: Bilanço dışı ve kapanış hesapları — yıl sonu özel kayıtları.',
        ],
        steps: null,
      },
      {
        title: 'Faturalama ve giderler: İki temel sütun',
        content: 'Faturalama ve gider takibi, günlük muhasebenin iki temel sütunudur. Her gelir bir faturayla desteklenmeli, her giderin bir belgesi (fatura, fiş, onay) olmalıdır. Makedonya\'da bir fatura zorunlu unsurlar içermelidir: düzenleyenin ve alıcının adı ve EDB\'si, tarih, hizmet veya ürün açıklaması, miktar, birim fiyat, toplam tutar ve KDV mükellefi iseniz KDV. Giderler, UJP tarafından tanınması için iş faaliyetiyle ilgili olmalıdır. Kişisel harcamalar vergi matrahından düşülemez.',
        items: [
          'Her faturanın benzersiz bir sıra numarası olmalıdır.',
          'Ödeme vadesi faturada belirtilmelidir.',
          'Faturalar en az 10 yıl saklanmalıdır.',
          'Belgesiz giderler UJP tarafından tanınmaz.',
          'Kişisel ve ticari giderleri karıştırmak en yaygın hatalardan biridir.',
        ],
        steps: null,
      },
      {
        title: 'Gelir tablosu ve bilanço',
        content: 'Bu iki rapor, her işletmenin anlaması gereken temel finansal tablolardır. Şirketinizin finansal sağlığının hikayesini anlatırlar.',
        items: null,
        steps: [
          { step: 'Gelir Tablosu (Kar/Zarar)', desc: 'Belirli bir dönem için finansal sonucu gösterir — genellikle ay, çeyrek veya yıl. Basitçe: gelir eksi gider = kar (veya zarar). Aylık geliriniz 500.000 MKD ve giderleriniz 350.000 MKD ise, karınız 150.000 MKD\'dir. Bu kar üzerinden %10 kurumlar vergisi ödenir.' },
          { step: 'Bilanço', desc: 'Şirketin belirli bir andaki finansal durumunu gösterir. Üç bölümden oluşur: varlıklar = yükümlülükler + öz sermaye. Varlıklar sahip olduklarınızdır (nakit, ekipman, alacaklar). Yükümlülükler borçlu olduklarınızdır (krediler, tedarikçilere borçlar). Öz sermaye farktır — şirketin net değeri.' },
          { step: 'Nakit Akış Tablosu', desc: 'Paranın hareketini izler — ne kadar girer ve ne kadar çıkar. Bir şirket kağıt üzerinde karlı olabilir ancak faturalarını ödeyecek nakdi olmayabilir. Bu yüzden nakit akışı izleme kritiktir, özellikle yeni işletmeler için.' },
        ],
      },
      {
        title: 'Ne zaman muhasebeci tutmalı',
        content: 'Makedonya\'daki her işletme defter tutmak zorundadır, ancak her işletmenin ilk günden muhasebeci tutması gerekmez. İşte profesyonel yardım aramanın zamanı geldiğinin işaretleri:',
        items: [
          'Ayda 10-20\'den fazla işleminiz var — hacim tek başına yönetmek için zorlaşıyor.',
          'KDV\'ye kayıtlısınız — KDV hesaplamaları hassasiyet ve kural bilgisi gerektirir.',
          'Çalışanlarınız var — MPIN beyannameleri ve bordro hesaplamaları karmaşıktır.',
          'Yıl sonu yaklaşıyor — yıllık hesaplar ve DB-VP beyannamesi uzmanlık bilgisi gerektirir.',
          'Hızla büyüyorsunuz — hacim arttıkça hatalar daha maliyetli olur.',
        ],
        steps: null,
      },
      {
        title: 'Facturino muhasebeyi nasıl kolaylaştırır',
        content: 'Facturino, tam muhasebe bilgisi ile küçük işletmelerin ihtiyaçları arasındaki boşluğu kapatmak için inşa edilmiştir. Kullanmak için muhasebeci olmanız gerekmez — sistem otomatik olarak işlemleri sınıflandırır, KDV yükümlülüğünü hesaplar ve sizin veya muhasebecinizin kullanabileceği raporlar oluşturur.',
        items: [
          'Makedon hesap planına göre gelir ve giderlerin otomatik sınıflandırılması.',
          'Gelir tablosu ve bilanço raporlarının gerçek zamanlı oluşturulması.',
          'UJP\'ye sunulmaya hazır KDV hesaplamaları ve raporları.',
          'Muhasebecinizle erişimi paylaşın — dosya göndermeye gerek yok.',
          'Popüler muhasebe yazılımlarıyla uyumlu formatlarda veri dışa aktarımı.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Muhasebe zor olmak zorunda değil',
      desc: 'Facturino temel muhasebe görevlerini otomatikleştirir. Siz işletmeye odaklanın, biz rakamlarla ilgilenelim.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function SmetkovodstvoZaPocetniciPage({
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
