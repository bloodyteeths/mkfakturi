import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'
import Image from 'next/image'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/kako-da-otvorite-firma-i-pocnete-so-fakturiranje', {
    title: {
      mk: 'Отворивте фирма? Еве како да почнете со фактурирање за 1 ден — Facturino',
      en: 'Just Registered a Company? Here\'s How to Start Invoicing in 1 Day — Facturino',
      sq: 'Hapët firmën? Ja si të filloni faturimin për 1 ditë — Facturino',
      tr: 'Şirket mi kurdunuz? 1 günde faturalamaya nasıl başlarsınız — Facturino',
    },
    description: {
      mk: 'Целосен водич за нови фирми во Македонија: од регистрација во Facturino до првата фактура за помалку од 1 ден. AI онбординг, Dashboard листа за проверка и повеќе.',
      en: 'Complete guide for new companies in Macedonia: from Facturino registration to your first invoice in less than 1 day. AI onboarding, Dashboard checklist and more.',
      sq: 'Udhëzues i plotë për firma të reja në Maqedoni: nga regjistrimi në Facturino deri te fatura e parë për më pak se 1 ditë. AI onboarding, listë kontrolli Dashboard dhe më shumë.',
      tr: 'Makedonya\'daki yeni şirketler için eksiksiz rehber: Facturino kaydından ilk faturaya 1 günden kısa sürede. AI onboarding, Dashboard kontrol listesi ve daha fazlası.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Отворивте фирма? Еве како да почнете со фактурирање за 1 ден',
    publishDate: '15 март 2026',
    readTime: '7 мин читање',
    intro: 'Штотуку ја регистриравте вашата фирма во ЦРРСМ. Честитки! Но сега доаѓа вистинскиот предизвик — како да ги средите финансиите од самиот почеток? Овој водич ве води чекор по чекор од првото логирање во Facturino до издавање на вашата прва фактура, сe за помалку од 1 ден.',
    heroAlt: 'Отворање фирма и почеток со фактурирање во Македонија - Facturino',
    sections: [
      {
        title: 'Чекор 1: Регистрација во Facturino',
        content: 'Регистрацијата трае помалку од 2 минути. Не ви треба кредитна картичка — бесплатниот план вклучува до 3 фактури месечно, доволно за да го тестирате системот. По регистрацијата, AI онбордингот ве води низ поставувањето на профилот на вашата компанија.',
        items: null,
        steps: [
          { step: 'Отворете signup страница', desc: 'Одете на app.facturino.mk/signup и пополнете го вашето име, е-пошта и лозинка. За неколку секунди ќе имате пристап до вашиот нов сметководствен систем.' },
          { step: 'Поставете го профилот на компанијата', desc: 'Внесете ги основните податоци за вашата фирма: ЕДБ (единствен даночен број), ЕМБС (матичен број), адреса на седиште и телефонски број. Ова се задолжителни полиња кои ќе се појавуваат на секоја фактура.' },
          { step: 'Додајте лого (опционално)', desc: 'Качете го логото на вашата фирма. Ова ќе се прикажува на сите фактури, понуди и извештаи. Поддржани формати: PNG, JPG, SVG.' },
          { step: 'Изберете ДДВ статус', desc: 'Означете дали вашата фирма е ДДВ обврзник. Ако сте, системот автоматски ќе го пресметува ДДВ по стапката од 18% (стандардна) или 5% (преференцијална) на секоја фактура.' },
        ],
      },
      {
        title: 'Чекор 2: Поврзете го изворот на податоци',
        content: 'Facturino ви дава три начини да започнете: увоз од Excel табела, мигрирање од друг софтвер (Пантеон, Неоком и др.) или почнување од нула. AI онбордингот ве води низ секоја опција и ви предлага најдобар пат за вашиот бизнис.',
        items: null,
        steps: [
          { step: 'Изберете извор на податоци', desc: 'На екранот за онбординг, изберете го изворот: „Увоз од Excel", „Мигрирање од друг софтвер" или „Почнувам од нула". Секоја опција има свој водич со детални упатства.' },
          { step: 'Увезете контен план (опционално)', desc: 'Ако мигрирате од друг систем, можете да го увезете вашиот контен план (сметковен план). Facturino поддржува стандардниот македонски контен план и автоматски ги мапира сметките.' },
          { step: 'Поврзете банкарска сметка', desc: 'Изберете ја вашата банка и внесете го бројот на трансакциска сметка. Подоцна можете да увезувате банковни изводи за автоматско усогласување на уплати со фактури.' },
          { step: 'AI асистент за поставување', desc: 'Ако запнете, AI асистентот ви помага во реално време. Поставете прашање на македонски и добивте одговор за неколку секунди — од „Каков ДДВ статус треба да одберам?" до „Како да увезам контен план?".' },
        ],
      },
      {
        title: 'Чекор 3: Издајте ја првата фактура',
        content: 'Сега кога компанијата е поставена, време е за најважниот чекор — вашата прва фактура. Facturino автоматски ги пополнува задолжителните полиња (ЕДБ, ЕМБС, датум, валута) и го пресметува ДДВ-то. Вие само го додавате клиентот и ставките.',
        items: null,
        steps: [
          { step: 'Додајте го вашиот прв клиент', desc: 'Одете во „Клиенти" и кликнете „Нов клиент". Внесете го името на фирмата, ЕДБ, адреса и е-пошта. Овие податоци автоматски ќе се повлекуваат при секое ново фактурирање.' },
          { step: 'Креирајте нова фактура', desc: 'Кликнете „Нова фактура", изберете го клиентот, додајте ги ставките (опис, количина, цена). ДДВ-то автоматски се пресметува. Системот ги пополнува сите задолжителни полиња барани од УЈП.' },
          { step: 'Прегледајте и испратете', desc: 'Прегледајте ја фактурата во PDF формат. Кога сте задоволни, испратете ја директно по е-пошта до клиентот или преземете го PDF-от и испратете го рачно. Фактурата автоматски се зачувува во системот.' },
          { step: 'Следете го статусот', desc: 'По испраќањето, следете дали фактурата е погледната, платена или доцни. Facturino ви испраќа нотификации и овозможува автоматски потсетници за неплатени фактури.' },
        ],
      },
      {
        title: 'Листа за проверка на Dashboard',
        content: 'По регистрацијата, на вашиот Dashboard ќе ви се прикаже листа за проверка со 5 чекори. Секој чекор автоматски се означува како завршен кога ќе ја извршите акцијата. Прогрес барот ви покажува колку далеку сте стигнале.',
        items: [
          'Профил на компанија — Пополнете ги ЕДБ, ЕМБС, адреса и лого. Ова е основата за секој документ.',
          'Банкарска сметка — Додајте ја вашата трансакциска сметка за да можете да увезувате изводи и да ги усогласувате уплатите.',
          'Прва фактура — Издајте ја вашата прва фактура и потврдете дека сите податоци се точни.',
          'Прво плаќање — Евидентирајте го првото примено плаќање и усогласете го со фактурата.',
          'Прв извештај — Генерирајте го вашиот прв финансиски извештај за да ја видите сликата на вашиот бизнис.',
        ],
        steps: null,
      },
      {
        title: 'Следни чекори',
        content: 'Откако ќе ја издадете првата фактура, Facturino отклучува цел свет на можности за автоматизирање на вашите финансии. Еве што можете да направите следно:',
        items: null,
        steps: [
          { step: 'Увоз на банковни изводи', desc: 'Увезете ги месечните банковни изводи и нека Facturino автоматски ги усогласи уплатите со фактурите. AI препознавањето точно идентификува кој клиент платил и за која фактура.' },
          { step: 'Рекурентни фактури', desc: 'Ако имате клиенти на кои им фактурирате редовно (месечно, квартално), поставете рекурентни фактури. Системот автоматски ги генерира и испраќа на зададениот датум.' },
          { step: 'Партнерска програма', desc: 'Ако сте сметководител со повеќе клиенти, разгледајте ја нашата партнерска програма. Управувајте со сите клиенти од една конзола и заработувајте 20% провизија на секоја претплата.' },
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Како да отворите фирма во Македонија' },
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура — чекор по чекор' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура во Македонија' },
    ],
    cta: {
      title: 'Подготвени сте за вашата прва фактура',
      desc: 'Регистрирајте се бесплатно и издајте ја вашата прва фактура за помалку од 1 ден.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Just Registered a Company? Here\'s How to Start Invoicing in 1 Day',
    publishDate: 'March 15, 2026',
    readTime: '7 min read',
    intro: 'You just registered your company at CRRM. Congratulations! But now comes the real challenge — how do you get your finances in order from the very start? This guide walks you step by step from your first login to Facturino to issuing your first invoice, all in less than 1 day.',
    heroAlt: 'Starting a company and beginning invoicing in Macedonia - Facturino',
    sections: [
      {
        title: 'Step 1: Register on Facturino',
        content: 'Registration takes less than 2 minutes. You do not need a credit card — the free plan includes up to 3 invoices per month, enough to test the system. After registration, the AI onboarding guides you through setting up your company profile.',
        items: null,
        steps: [
          { step: 'Open the signup page', desc: 'Go to app.facturino.mk/signup and fill in your name, email, and password. In a few seconds you will have access to your new accounting system.' },
          { step: 'Set up company profile', desc: 'Enter your company\'s basic information: EDB (tax identification number), EMBS (registration number), headquarters address, and phone number. These are mandatory fields that will appear on every invoice.' },
          { step: 'Add a logo (optional)', desc: 'Upload your company logo. It will be displayed on all invoices, quotes, and reports. Supported formats: PNG, JPG, SVG.' },
          { step: 'Select VAT status', desc: 'Indicate whether your company is a VAT payer. If so, the system will automatically calculate VAT at the rate of 18% (standard) or 5% (preferential) on every invoice.' },
        ],
      },
      {
        title: 'Step 2: Connect Your Data Source',
        content: 'Facturino gives you three ways to get started: import from an Excel spreadsheet, migrate from another software (Pantheon, Neocom, etc.), or start from scratch. The AI onboarding guides you through each option and suggests the best path for your business.',
        items: null,
        steps: [
          { step: 'Choose a data source', desc: 'On the onboarding screen, select your source: "Import from Excel", "Migrate from another software", or "Starting from scratch". Each option has its own guide with detailed instructions.' },
          { step: 'Import chart of accounts (optional)', desc: 'If you are migrating from another system, you can import your chart of accounts. Facturino supports the standard Macedonian chart of accounts and automatically maps the accounts.' },
          { step: 'Connect bank account', desc: 'Select your bank and enter your transaction account number. Later you can import bank statements for automatic reconciliation of payments with invoices.' },
          { step: 'AI setup assistant', desc: 'If you get stuck, the AI assistant helps you in real time. Ask a question in your language and get an answer in seconds — from "What VAT status should I choose?" to "How do I import a chart of accounts?".' },
        ],
      },
      {
        title: 'Step 3: Issue Your First Invoice',
        content: 'Now that your company is set up, it is time for the most important step — your first invoice. Facturino automatically fills in mandatory fields (EDB, EMBS, date, currency) and calculates VAT. You just add the customer and line items.',
        items: null,
        steps: [
          { step: 'Add your first customer', desc: 'Go to "Customers" and click "New Customer". Enter the company name, EDB, address, and email. This information will be automatically pulled in for every new invoice.' },
          { step: 'Create a new invoice', desc: 'Click "New Invoice", select the customer, add line items (description, quantity, price). VAT is automatically calculated. The system fills in all mandatory fields required by UJP.' },
          { step: 'Review and send', desc: 'Review the invoice in PDF format. When you are satisfied, send it directly via email to your customer or download the PDF and send it manually. The invoice is automatically saved in the system.' },
          { step: 'Track the status', desc: 'After sending, track whether the invoice has been viewed, paid, or is overdue. Facturino sends you notifications and enables automatic reminders for unpaid invoices.' },
        ],
      },
      {
        title: 'Dashboard Checklist',
        content: 'After registration, your Dashboard will display a checklist with 5 steps. Each step is automatically marked as completed when you perform the action. The progress bar shows you how far you have come.',
        items: [
          'Company profile — Fill in EDB, EMBS, address, and logo. This is the foundation for every document.',
          'Bank account — Add your transaction account so you can import statements and reconcile payments.',
          'First invoice — Issue your first invoice and confirm that all data is correct.',
          'First payment — Record your first received payment and reconcile it with the invoice.',
          'First report — Generate your first financial report to see the picture of your business.',
        ],
        steps: null,
      },
      {
        title: 'Next Steps',
        content: 'Once you issue your first invoice, Facturino unlocks a whole world of possibilities for automating your finances. Here is what you can do next:',
        items: null,
        steps: [
          { step: 'Bank statement import', desc: 'Import your monthly bank statements and let Facturino automatically reconcile payments with invoices. AI recognition accurately identifies which customer paid and for which invoice.' },
          { step: 'Recurring invoices', desc: 'If you have customers you invoice regularly (monthly, quarterly), set up recurring invoices. The system automatically generates and sends them on the scheduled date.' },
          { step: 'Partner program', desc: 'If you are an accountant with multiple clients, check out our partner program. Manage all clients from a single console and earn 20% commission on every subscription.' },
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'How to Register a Company in Macedonia' },
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice — Step by Step' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements in Macedonia' },
    ],
    cta: {
      title: 'You Are Ready for Your First Invoice',
      desc: 'Sign up free and issue your first invoice in less than 1 day.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Hapët firmën? Ja si të filloni faturimin për 1 ditë',
    publishDate: '15 mars 2026',
    readTime: '7 min lexim',
    intro: 'Sapo e regjistruat firmën tuaj në QRMM. Urime! Por tani vjen sfida e vërtetë — si t\'i rregulloni financat që nga fillimi? Ky udhëzues ju çon hap pas hapi nga hyrja e parë në Facturino deri te lëshimi i faturës tuaj të parë, të gjitha për më pak se 1 ditë.',
    heroAlt: 'Hapja e firmës dhe fillimi i faturimit në Maqedoni - Facturino',
    sections: [
      {
        title: 'Hapi 1: Regjistrimi në Facturino',
        content: 'Regjistrimi zgjat më pak se 2 minuta. Nuk ju nevojitet kartë krediti — plani falas përfshin deri në 3 fatura në muaj, mjaftueshëm për ta testuar sistemin. Pas regjistrimit, AI onboarding-u ju udhëzon përmes vendosjes së profilit të kompanisë suaj.',
        items: null,
        steps: [
          { step: 'Hapni faqen e regjistrimit', desc: 'Shkoni te app.facturino.mk/signup dhe plotësoni emrin, emailin dhe fjalëkalimin tuaj. Për disa sekonda do të keni qasje në sistemin tuaj të ri të kontabilitetit.' },
          { step: 'Vendosni profilin e kompanisë', desc: 'Vendosni informacionet bazë të firmës suaj: EDB (numri i identifikimit tatimor), EMBS (numri i regjistrimit), adresa e selisë dhe numri i telefonit. Këto janë fusha të detyrueshme që do të shfaqen në çdo faturë.' },
          { step: 'Shtoni logo (opsionale)', desc: 'Ngarkoni logon e firmës suaj. Ajo do të shfaqet në të gjitha faturat, ofertat dhe raportet. Formatet e mbështetura: PNG, JPG, SVG.' },
          { step: 'Zgjidhni statusin e TVSH-së', desc: 'Tregoni nëse firma juaj është pagues i TVSH-së. Nëse po, sistemi automatikisht do ta llogarisë TVSH-në me normën 18% (standarde) ose 5% (preferenciale) në çdo faturë.' },
        ],
      },
      {
        title: 'Hapi 2: Lidhni burimin e të dhënave',
        content: 'Facturino ju jep tre mënyra për të filluar: import nga tabela Excel, migrim nga softuer tjetër (Pantheon, Neocom etj.) ose fillim nga zeroja. AI onboarding-u ju udhëzon përmes çdo opsioni dhe ju sugjeron rrugën më të mirë për biznesin tuaj.',
        items: null,
        steps: [
          { step: 'Zgjidhni burimin e të dhënave', desc: 'Në ekranin e onboarding-ut, zgjidhni burimin tuaj: "Import nga Excel", "Migrim nga softuer tjetër" ose "Filloj nga zeroja". Çdo opsion ka udhëzuesin e vet me udhëzime të detajuara.' },
          { step: 'Importoni planin kontabël (opsionale)', desc: 'Nëse po migroni nga sistem tjetër, mund ta importoni planin tuaj kontabël. Facturino mbështet planin standard kontabël maqedonas dhe automatikisht i harton llogaritë.' },
          { step: 'Lidhni llogarinë bankare', desc: 'Zgjidhni bankën tuaj dhe vendosni numrin e llogarisë transaksionale. Më vonë mund të importoni ekstrakte bankare për pajtim automatik të pagesave me faturat.' },
          { step: 'AI asistenti për vendosje', desc: 'Nëse ngecni, AI asistenti ju ndihmon në kohë reale. Bëni pyetje në gjuhën tuaj dhe merrni përgjigje për disa sekonda — nga "Çfarë statusi TVSH duhet të zgjedh?" deri te "Si ta importoj planin kontabël?".' },
        ],
      },
      {
        title: 'Hapi 3: Lëshoni faturën e parë',
        content: 'Tani që kompania është vendosur, është koha për hapin më të rëndësishëm — fatura juaj e parë. Facturino automatikisht i plotëson fushat e detyrueshme (EDB, EMBS, data, valuta) dhe e llogarit TVSH-në. Ju vetëm shtoni klientin dhe artikujt.',
        items: null,
        steps: [
          { step: 'Shtoni klientin tuaj të parë', desc: 'Shkoni te "Klientët" dhe klikoni "Klient i ri". Vendosni emrin e firmës, EDB, adresën dhe emailin. Këto informacione automatikisht do të tërhiqen për çdo faturim të ri.' },
          { step: 'Krijoni faturë të re', desc: 'Klikoni "Faturë e re", zgjidhni klientin, shtoni artikujt (përshkrimi, sasia, çmimi). TVSH llogaritet automatikisht. Sistemi i plotëson të gjitha fushat e detyrueshme të kërkuara nga UJP.' },
          { step: 'Rishikoni dhe dërgoni', desc: 'Rishikoni faturën në format PDF. Kur jeni të kënaqur, dërgojeni direkt përmes emailit te klienti ose shkarkoni PDF-in dhe dërgojeni manualisht. Fatura ruhet automatikisht në sistem.' },
          { step: 'Ndiqni statusin', desc: 'Pas dërgimit, ndiqni nëse fatura është parë, paguar ose ka vonuar. Facturino ju dërgon njoftime dhe mundëson kujtesa automatike për faturat e papaguara.' },
        ],
      },
      {
        title: 'Lista e kontrollit në Dashboard',
        content: 'Pas regjistrimit, Dashboard-i juaj do të shfaqë listën e kontrollit me 5 hapa. Çdo hap automatikisht shënohet si i përfunduar kur e kryeni veprimin. Shiriti i progresit ju tregon sa larg keni arritur.',
        items: [
          'Profili i kompanisë — Plotësoni EDB, EMBS, adresën dhe logon. Kjo është baza për çdo dokument.',
          'Llogari bankare — Shtoni llogarinë tuaj transaksionale që të mund të importoni ekstrakte dhe të pajtoni pagesat.',
          'Fatura e parë — Lëshoni faturën tuaj të parë dhe konfirmoni që të gjitha të dhënat janë të sakta.',
          'Pagesa e parë — Regjistroni pagesën e parë të pranuar dhe pajtojeni me faturën.',
          'Raporti i parë — Gjeneroni raportin tuaj të parë financiar për ta parë pasqyrën e biznesit tuaj.',
        ],
        steps: null,
      },
      {
        title: 'Hapat e ardhshëm',
        content: 'Pasi të lëshoni faturën e parë, Facturino zhbllokon një botë të tërë mundësish për automatizimin e financave tuaja. Ja çfarë mund të bëni më tej:',
        items: null,
        steps: [
          { step: 'Import i ekstrakteve bankare', desc: 'Importoni ekstraktet tuaja mujore bankare dhe lini Facturino t\'i pajtojë automatikisht pagesat me faturat. Njohja AI identifikon me saktësi cili klient ka paguar dhe për cilën faturë.' },
          { step: 'Fatura rikurrente', desc: 'Nëse keni klientë që i faturoni rregullisht (mujorisht, tremujorsisht), vendosni fatura rikurrente. Sistemi automatikisht i gjeneron dhe i dërgon në datën e caktuar.' },
          { step: 'Programi i partneritetit', desc: 'Nëse jeni kontabilist me shumë klientë, shikoni programin tonë të partneritetit. Menaxhoni të gjithë klientët nga një konzolë e vetme dhe fitoni 20% komision në çdo abonim.' },
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Si të hapni firmë në Maqedoni' },
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni faturë — hap pas hapi' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme të faturës në Maqedoni' },
    ],
    cta: {
      title: 'Jeni gati për faturën tuaj të parë',
      desc: 'Regjistrohuni falas dhe lëshoni faturën tuaj të parë për më pak se 1 ditë.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Şirket mi kurdunuz? 1 günde faturalamaya nasıl başlarsınız',
    publishDate: '15 Mart 2026',
    readTime: '7 dk okuma',
    intro: 'Şirketinizi CRRM\'de kaydettiniz. Tebrikler! Ama asıl zorluk şimdi geliyor — finanslarınızı en başından nasıl düzene koyarsınız? Bu rehber sizi adım adım Facturino\'ya ilk girişten ilk faturanızı düzenlemeye kadar götürür, hepsi 1 günden kısa sürede.',
    heroAlt: 'Makedonya\'da şirket açma ve faturalamaya başlama - Facturino',
    sections: [
      {
        title: 'Adım 1: Facturino\'ya Kayıt',
        content: 'Kayıt 2 dakikadan az sürer. Kredi kartına ihtiyacınız yok — ücretsiz plan ayda 3 faturaya kadar içerir, sistemi test etmek için yeterli. Kayıttan sonra AI onboarding şirket profilinizi oluşturmanızda size rehberlik eder.',
        items: null,
        steps: [
          { step: 'Kayıt sayfasını açın', desc: 'app.facturino.mk/signup adresine gidin ve adınızı, e-postanızı ve şifrenizi doldurun. Birkaç saniye içinde yeni muhasebe sisteminize erişiminiz olacak.' },
          { step: 'Şirket profilini oluşturun', desc: 'Şirketinizin temel bilgilerini girin: EDB (vergi kimlik numarası), EMBS (kayıt numarası), merkez adresi ve telefon numarası. Bunlar her faturada görünecek zorunlu alanlardır.' },
          { step: 'Logo ekleyin (isteğe bağlı)', desc: 'Şirket logonuzu yükleyin. Tüm faturalarda, tekliflerde ve raporlarda görüntülenecektir. Desteklenen formatlar: PNG, JPG, SVG.' },
          { step: 'KDV durumunu seçin', desc: 'Şirketinizin KDV mükellefi olup olmadığını belirtin. Öyleyse, sistem her faturada otomatik olarak %18 (standart) veya %5 (tercihli) oranında KDV hesaplayacaktır.' },
        ],
      },
      {
        title: 'Adım 2: Veri Kaynağınızı Bağlayın',
        content: 'Facturino size başlamak için üç yol sunar: Excel tablosundan içe aktarma, başka bir yazılımdan (Pantheon, Neocom vb.) geçiş veya sıfırdan başlama. AI onboarding her seçenekte size rehberlik eder ve işletmeniz için en iyi yolu önerir.',
        items: null,
        steps: [
          { step: 'Veri kaynağını seçin', desc: 'Onboarding ekranında kaynağınızı seçin: "Excel\'den İçe Aktar", "Başka bir yazılımdan geçiş" veya "Sıfırdan başlıyorum". Her seçeneğin ayrıntılı talimatlarla kendi rehberi vardır.' },
          { step: 'Hesap planını içe aktarın (isteğe bağlı)', desc: 'Başka bir sistemden geçiş yapıyorsanız, hesap planınızı içe aktarabilirsiniz. Facturino standart Makedon hesap planını destekler ve hesapları otomatik olarak eşler.' },
          { step: 'Banka hesabını bağlayın', desc: 'Bankanızı seçin ve işlem hesap numaranızı girin. Daha sonra faturaların ödemelerle otomatik mutabakatı için banka ekstrelerini içe aktarabilirsiniz.' },
          { step: 'AI kurulum asistanı', desc: 'Takılırsanız, AI asistanı size gerçek zamanlı yardım eder. Kendi dilinizde soru sorun ve saniyeler içinde yanıt alın — "Hangi KDV durumunu seçmeliyim?" den "Hesap planını nasıl içe aktarırım?" a kadar.' },
        ],
      },
      {
        title: 'Adım 3: İlk Faturanızı Düzenleyin',
        content: 'Artık şirketiniz kurulduğuna göre, en önemli adımın zamanı geldi — ilk faturanız. Facturino zorunlu alanları (EDB, EMBS, tarih, para birimi) otomatik olarak doldurur ve KDV\'yi hesaplar. Siz sadece müşteriyi ve kalemleri eklersiniz.',
        items: null,
        steps: [
          { step: 'İlk müşterinizi ekleyin', desc: '"Müşteriler"e gidin ve "Yeni Müşteri"ye tıklayın. Şirket adını, EDB, adresi ve e-postayı girin. Bu bilgiler her yeni faturada otomatik olarak çekilecektir.' },
          { step: 'Yeni fatura oluşturun', desc: '"Yeni Fatura"ya tıklayın, müşteriyi seçin, kalemleri ekleyin (açıklama, miktar, fiyat). KDV otomatik olarak hesaplanır. Sistem UJP tarafından istenen tüm zorunlu alanları doldurur.' },
          { step: 'İnceleyin ve gönderin', desc: 'Faturayı PDF formatında inceleyin. Memnun olduğunuzda, müşterinize doğrudan e-posta ile gönderin veya PDF\'i indirin ve manuel olarak gönderin. Fatura otomatik olarak sistemde kaydedilir.' },
          { step: 'Durumu takip edin', desc: 'Gönderdikten sonra faturanın görüntülenip görüntülenmediğini, ödenip ödenmediğini veya gecikip gecikmediğini takip edin. Facturino size bildirim gönderir ve ödenmemiş faturalar için otomatik hatırlatıcılar sağlar.' },
        ],
      },
      {
        title: 'Dashboard Kontrol Listesi',
        content: 'Kayıttan sonra Dashboard\'unuzda 5 adımlı bir kontrol listesi görüntülenir. Her adım, eylemi gerçekleştirdiğinizde otomatik olarak tamamlandı olarak işaretlenir. İlerleme çubuğu ne kadar ilerlediğinizi gösterir.',
        items: [
          'Şirket profili — EDB, EMBS, adres ve logoyu doldurun. Bu her belgenin temelidir.',
          'Banka hesabı — Ekstre içe aktarabilmek ve ödemeleri mutabık kılabilmek için işlem hesabınızı ekleyin.',
          'İlk fatura — İlk faturanızı düzenleyin ve tüm verilerin doğru olduğunu onaylayın.',
          'İlk ödeme — Alınan ilk ödemeyi kaydedin ve fatura ile mutabık kılın.',
          'İlk rapor — İşletmenizin resmini görmek için ilk mali raporunuzu oluşturun.',
        ],
        steps: null,
      },
      {
        title: 'Sonraki Adımlar',
        content: 'İlk faturanızı düzenledikten sonra Facturino, finanslarınızı otomatikleştirmek için bir dizi olanak sunar. İşte bundan sonra yapabilecekleriniz:',
        items: null,
        steps: [
          { step: 'Banka ekstresi içe aktarma', desc: 'Aylık banka ekstrelerinizi içe aktarın ve Facturino\'nun ödemeleri faturalarla otomatik olarak eşleştirmesini sağlayın. AI tanıma, hangi müşterinin ödeme yaptığını ve hangi fatura için olduğunu doğru bir şekilde tanımlar.' },
          { step: 'Tekrarlayan faturalar', desc: 'Düzenli olarak (aylık, üç aylık) faturaladığınız müşterileriniz varsa, tekrarlayan faturalar ayarlayın. Sistem bunları planlanan tarihte otomatik olarak oluşturur ve gönderir.' },
          { step: 'Ortaklık programı', desc: 'Birden fazla müşterisi olan bir muhasebeciyseniz, ortaklık programımıza göz atın. Tüm müşterileri tek bir konsoldan yönetin ve her abonelikte %20 komisyon kazanın.' },
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Makedonya\'da nasıl şirket kurulur' },
      { slug: 'kako-da-napravite-faktura', title: 'Fatura nasıl oluşturulur — adım adım' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Makedonya\'da zorunlu fatura unsurları' },
    ],
    cta: {
      title: 'İlk faturanız için hazırsınız',
      desc: 'Ücretsiz kaydolun ve ilk faturanızı 1 günden kısa sürede düzenleyin.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function KakoDaOtvoriteFirmaPage({
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
          <Image src="/assets/images/blog/blog_company_onboarding.png" alt={t.heroAlt} className="w-full h-auto" width={1200} height={630} />
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
