import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-it-freelancer', {
    title: {
      mk: 'Сметководство за ИТ фрилансери и компании: Фактурирање, данок и ДДВ',
      en: 'Freelancer & IT Company Accounting Macedonia: Invoicing, Tax & VAT',
      sq: 'Kontabiliteti per IT freelancer dhe kompani: Faturim, tatim dhe TVSH',
      tr: 'Serbest Çalışan ve IT Şirketleri İçin Muhasebe: Faturalama, Vergi ve KDV',
    },
    description: {
      mk: 'Целосен водич за ИТ фрилансери во Македонија: повеќевалутно фактурирање (EUR/USD), ДДВ на извезени услуги, ДООЕЛ vs паушалец, приход од Upwork/Fiverr, канцеларија дома и чести грешки.',
      en: 'Complete guide for IT freelancers in Macedonia: multi-currency invoicing (EUR/USD), VAT on exported services, DOOEL vs flat-rate tax, Upwork/Fiverr income, home office deductions and common mistakes.',
      sq: 'Udhezues i plote per IT freelancer ne Maqedoni: faturim shumevolutor (EUR/USD), TVSH per sherbime te eksportuara, DOOEL vs tatim i sheshte, te ardhura nga Upwork/Fiverr, zyra ne shtepi dhe gabime te zakonshme.',
      tr: 'Makedonya\'daki IT serbest calisanlar icin rehber: cok para birimli faturalama (EUR/USD), ihrac edilen hizmetlerde KDV, DOOEL ve goturu vergi, Upwork/Fiverr geliri, ev ofisi kesintileri ve yaygin hatalar.',
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
    tag: 'Сектор',
    title: 'Сметководство за ИТ фрилансери и компании во Македонија',
    publishDate: '23 мај 2026',
    readTime: '9 мин читање',
    intro:
      'ИТ секторот во Македонија расте брзо — се повеќе програмери, дизајнери и консултанти работат како фрилансери за странски клиенти. Но, правилното сметководство е клучно: повеќевалутно фактурирање, ДДВ на извезени услуги, избор меѓу ДООЕЛ и паушалец, и декларирање на приходи од платформи како Upwork и Fiverr. Во овој водич ги покриваме сите аспекти што ИТ фрилансерите мора да ги знаат.',
    sections: [
      {
        title: 'Повеќевалутно фактурирање (EUR/USD)',
        content:
          'Кога фактурирате странски клиенти во EUR или USD, функционалната валута на вашата фирма останува МКД. Секоја фактура мора да го содржи износот во странска валута и еквивалентот во МКД пресметан по средниот курс на НБРСМ на денот на фактурата. Курсните разлики при наплата се книжат како финансиски приход или расход.',
        items: [
          'Секоја фактура мора да содржи износ во МКД покрај странската валута',
          'Курсот се зема од НБРСМ (Народна банка) на денот на издавање на фактурата',
          'Позитивни курсни разлики = финансиски приход, негативни = финансиски расход',
          'Водете евиденција за сите курсни разлики — потребни се за годишна сметка',
          'PayPal/Wise/Payoneer трансферите се конвертираат по банкарскиот курс — разликата со НБРСМ е курсна разлика',
        ],
        steps: null,
      },
      {
        title: 'ДДВ на извезени услуги',
        content:
          'ИТ услугите за странски клиенти (B2B) се ослободени од ДДВ со стапка 0% според член 30 став 1 точка 23 од ЗДДВ — местото на прометот е каде примателот има седиште. Но, ова не значи дека не треба да се регистрирате за ДДВ.',
        items: [
          'Б2Б услуги за ЕУ/меѓународни клиенти: ДДВ 0% (reverse charge — клиентот го плаќа ДДВ во својата земја)',
          'Мора да се регистрирате за ДДВ ако годишниот промет надмине 2.000.000 МКД (~32.500 EUR)',
          'Регистрацијата за ДДВ е корисна и под прагот — ви овозможува одбиток на влезен ДДВ (лаптоп, софтвер, канцеларија)',
          'Извезените услуги се пријавуваат во ДДВ-04 образецот како ослободен промет',
          'Чувајте доказ дека клиентот е бизнис (VAT ID, договор, профил на компанијата)',
        ],
        steps: null,
      },
      {
        title: 'ДООЕЛ vs Паушалец: Кој статус е подобар?',
        content:
          'Фрилансерите во Македонија најчесто избираат меѓу два модели: ДООЕЛ (друштво со ограничена одговорност на едно лице) и паушален даночник. Изборот зависи од годишниот приход и структурата на трошоците.',
        items: null,
        steps: [
          {
            step: 'Паушалец (фиксен месечен данок)',
            desc: 'Паушалниот даночник плаќа фиксен месечен данок од ~5.000-15.000 МКД (зависно од дејноста и општина). Нема обврска за водење книговодство, ниту за поднесување на ДДВ пријава. Максимален годишен приход: 3.000.000 МКД (~48.800 EUR). Идеално за почетници со низок промет.',
          },
          {
            step: 'ДООЕЛ (10% данок на добивка)',
            desc: 'ДООЕЛ плаќа 10% данок на добивка (приход минус признати трошоци). Задолжително водење на двојно книговодство. Мора да се регистрира за ДДВ над 2М МКД промет. Подобро за фрилансери со високи трошоци (опрема, софтвер, поднаеми) бидејќи трошоците го намалуваат данокот.',
          },
          {
            step: 'Кога да преминете од паушалец на ДООЕЛ?',
            desc: 'Ако годишниот приход надмине 3М МКД, мора да преминете. Но и под прагот, ДООЕЛ е подобар ако трошоците се над 30% од приходот — ефективниот данок на ДООЕЛ е понизок. Исто, ДООЕЛ дава поголема кредибилност кај странски клиенти.',
          },
        ],
      },
      {
        title: 'Приход од странски платформи',
        content:
          'Приходите од Upwork, Fiverr, Toptal и слични платформи се целосно оданочиви во Македонија. Не постои изземање — без разлика дали парите се на PayPal, Wise или директно на банкарска сметка.',
        items: [
          'Целиот приход од платформите мора да се декларира — вклучувајќи го и делот задржан од платформата (провизија)',
          'Провизијата на платформата (Upwork 10%, Fiverr 20%) е признат деловен трошок — го намалува данокот',
          'PayPal/Wise трансфери до македонска банка: банката известува до УЈП за девизен прилив',
          'Препорака: отворете посебна деловна сметка за фрилансерски приходи — полесно за евиденција',
          'Чувајте извештаи од платформата (statements) како доказ за приход и провизии',
          'Ако клиент плаќа директно (не преку платформа), задолжително издадете фактура',
        ],
        steps: null,
      },
      {
        title: 'Одбиток за канцеларија дома',
        content:
          'Ако работите од дома, дел од трошоците за станот/куќата може да се одбијат како деловен трошок. Одбитокот е пропорционален — базиран на процентот на просторот што го користите за работа.',
        items: null,
        steps: [
          {
            step: 'Определете го работниот простор',
            desc: 'Измерете ја површината на просторијата што ја користите исклучиво за работа. Ако станот е 60м² и работната соба е 12м², тоа е 20% од просторот.',
          },
          {
            step: 'Пресметајте го одбитокот',
            desc: 'Применете го процентот на кирија, струја, греење, интернет и вода. Пример: 20% од 15.000 МКД кирија = 3.000 МКД месечен одбиток. Годишно: 36.000 МКД помалку данок.',
          },
          {
            step: 'Документирајте се',
            desc: 'Потребен е договор за закуп (или доказ за сопственост), сметки за комуналии на ваше име и фотографии на работниот простор. УЈП може да побара доказ при инспекција.',
          },
          {
            step: 'Алтернатива: коворкинг простор',
            desc: 'Месечната членарина за коворкинг е 100% признат деловен трошок — полесно за документирање. Skopje има повеќе опции: Impact Hub, Sektor, Creative Hub итн.',
          },
        ],
      },
      {
        title: 'Чести грешки на ИТ фрилансерите',
        content:
          'Од нашето искуство со стотици ИТ фрилансери, ова се најчестите грешки кои водат кон проблеми со УЈП или непотребно плаќање повеќе данок:',
        items: [
          'Не водат посебна деловна сметка — мешање на лични и деловни трансакции го отежнува книговодството и создава проблеми при инспекција',
          'Не издаваат фактура за странски клиенти — секој приход мора да биде покриен со фактура, дури и кога плаќањето е преку PayPal',
          'Не бараат одбиток на влезен ДДВ — лаптоп, софтвер лиценци, хостинг, домени — сето ова е ДДВ одбиток ако сте ДДВ обврзник',
          'Заборавуваат квартална ДДВ-04 пријава — рокот е 25-ти во месецот по завршување на кварталот. Задоцнето поднесување = казна',
          'Не евидентираат курсни разлики — при конверзија од EUR/USD во МКД секогаш има разлика. Мора да се книжи',
          'Не планираат за данок на добивка — ставаат 0 МКД на страна и на крајот на годината немаат за данок. Препорака: одвојувајте 15% од секој приход',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага на ИТ фрилансерите',
        content:
          'Facturino е создаден токму за македонски бизниси кои работат со странски клиенти. Системот го автоматизира целиот процес — од фактурирање до ДДВ пријави.',
        items: [
          'Повеќевалутно фактурирање со автоматски курс од НБРСМ — без рачно внесување',
          'Рекурентни (повторувачки) фактури за месечни клиенти — автоматско издавање секој месец',
          'Евиденција на трошоци со скенирање на фактури (OCR) — лаптоп, софтвер, хостинг',
          'ДДВ менаџмент: автоматско пресметување на влезен и излезен ДДВ, подготовка на ДДВ-04',
          'Банкарско порамнување: автоматско поврзување на PayPal/Wise уплати со фактури',
          'Извештаи за приход/расход по клиент, месец и валута — секогаш знаете колку заработувате',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија: Регистрација, стапки и обврски' },
      { slug: 'dooel-vodich-2026', title: 'Како да отворите ДООЕЛ во 2026: Чекор по чекор' },
      { slug: 'faktura-primer-mk', title: 'Пример за фактура: Задолжителни елементи во Македонија' },
    ],
    cta: {
      title: 'Фактурирајте професионално, од ден еден',
      desc: 'Повеќевалутни фактури, автоматски курс, ДДВ пресметка и евиденција на трошоци — се во Facturino.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Accounting for IT Freelancers and Companies in Macedonia',
    publishDate: 'May 23, 2026',
    readTime: '9 min read',
    intro:
      'The IT sector in Macedonia is growing fast — more developers, designers, and consultants are working as freelancers for foreign clients. But proper accounting is crucial: multi-currency invoicing, VAT on exported services, choosing between DOOEL and flat-rate tax, and declaring income from platforms like Upwork and Fiverr. In this guide we cover everything IT freelancers need to know.',
    sections: [
      {
        title: 'Multi-currency invoicing (EUR/USD)',
        content:
          'When invoicing foreign clients in EUR or USD, your company\'s functional currency remains MKD. Every invoice must include the amount in the foreign currency and the MKD equivalent calculated at the NBRSM (National Bank) mid-rate on the invoice date. Exchange rate differences on collection are recorded as financial income or expense.',
        items: [
          'Every invoice must include the MKD amount alongside the foreign currency',
          'The exchange rate is taken from NBRSM on the date the invoice is issued',
          'Positive exchange differences = financial income, negative = financial expense',
          'Keep records of all exchange differences — required for the annual financial statement',
          'PayPal/Wise/Payoneer transfers convert at the bank rate — the difference with NBRSM is an exchange difference',
        ],
        steps: null,
      },
      {
        title: 'VAT on exported services',
        content:
          'IT services provided to foreign clients (B2B) are VAT-exempt at 0% under Article 30, paragraph 1, point 23 of the VAT Law — the place of supply is where the recipient is established. However, this does not mean you do not need to register for VAT.',
        items: [
          'B2B services for EU/international clients: 0% VAT (reverse charge — the client pays VAT in their country)',
          'You must register for VAT if annual turnover exceeds 2,000,000 MKD (~32,500 EUR)',
          'VAT registration is beneficial even below the threshold — it enables input VAT deductions (laptop, software, office)',
          'Exported services are reported on the DDV-04 form as exempt turnover',
          'Keep proof that the client is a business (VAT ID, contract, company profile)',
        ],
        steps: null,
      },
      {
        title: 'DOOEL vs flat-rate tax: Which status is better?',
        content:
          'Freelancers in Macedonia typically choose between two models: DOOEL (single-member limited liability company) and flat-rate taxpayer. The choice depends on annual income and expense structure.',
        items: null,
        steps: [
          {
            step: 'Flat-rate taxpayer (fixed monthly tax)',
            desc: 'The flat-rate taxpayer pays a fixed monthly tax of approximately 5,000-15,000 MKD (depending on activity and municipality). No bookkeeping obligation, no VAT filing requirement. Maximum annual income: 3,000,000 MKD (~48,800 EUR). Ideal for beginners with low turnover.',
          },
          {
            step: 'DOOEL (10% profit tax)',
            desc: 'A DOOEL pays 10% tax on profit (income minus recognized expenses). Double-entry bookkeeping is mandatory. Must register for VAT above 2M MKD turnover. Better for freelancers with high expenses (equipment, software, subcontracting) since expenses reduce the tax base.',
          },
          {
            step: 'When to switch from flat-rate to DOOEL?',
            desc: 'If annual income exceeds 3M MKD, you must switch. But even below the threshold, DOOEL is better if expenses exceed 30% of income — the effective DOOEL tax rate becomes lower. Additionally, DOOEL provides greater credibility with foreign clients.',
          },
        ],
      },
      {
        title: 'Foreign platform income',
        content:
          'Income from Upwork, Fiverr, Toptal and similar platforms is fully taxable in Macedonia. There is no exemption — regardless of whether the money is in PayPal, Wise, or directly in a bank account.',
        items: [
          'All platform income must be declared — including the portion withheld by the platform (commission)',
          'Platform commission (Upwork 10%, Fiverr 20%) is a recognized business expense — it reduces your tax',
          'PayPal/Wise transfers to a Macedonian bank: the bank reports foreign currency inflows to UJP',
          'Recommendation: open a separate business account for freelancer income — easier for record-keeping',
          'Keep platform statements as proof of income and commissions',
          'If a client pays directly (not through a platform), you must issue an invoice',
        ],
        steps: null,
      },
      {
        title: 'Home office deduction',
        content:
          'If you work from home, a portion of your apartment/house expenses can be deducted as a business expense. The deduction is proportional — based on the percentage of space used for work.',
        items: null,
        steps: [
          {
            step: 'Determine the workspace',
            desc: 'Measure the area of the room used exclusively for work. If the apartment is 60m² and the office is 12m², that is 20% of the space.',
          },
          {
            step: 'Calculate the deduction',
            desc: 'Apply the percentage to rent, electricity, heating, internet, and water. Example: 20% of 15,000 MKD rent = 3,000 MKD monthly deduction. Annually: 36,000 MKD less tax.',
          },
          {
            step: 'Document everything',
            desc: 'You need a lease agreement (or proof of ownership), utility bills in your name, and photos of the workspace. UJP may request proof during an inspection.',
          },
          {
            step: 'Alternative: coworking space',
            desc: 'Monthly coworking membership is 100% recognized as a business expense — easier to document. Skopje has several options: Impact Hub, Sektor, Creative Hub, etc.',
          },
        ],
      },
      {
        title: 'Common mistakes IT freelancers make',
        content:
          'From our experience with hundreds of IT freelancers, these are the most common mistakes that lead to problems with UJP or unnecessarily paying more tax:',
        items: [
          'Not keeping a separate business account — mixing personal and business transactions complicates bookkeeping and creates problems during inspections',
          'Not issuing invoices for foreign clients — every income must be covered by an invoice, even when payment is via PayPal',
          'Not claiming input VAT deductions — laptops, software licenses, hosting, domains — all are VAT deductible if you are VAT-registered',
          'Forgetting the quarterly DDV-04 filing — the deadline is the 25th of the month following the quarter end. Late filing = penalty',
          'Not recording exchange rate differences — when converting from EUR/USD to MKD there is always a difference. It must be recorded',
          'Not planning for profit tax — setting aside 0 MKD and having nothing for taxes at year-end. Recommendation: set aside 15% of every income',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps IT freelancers',
        content:
          'Facturino is built specifically for Macedonian businesses working with foreign clients. The system automates the entire process — from invoicing to VAT filings.',
        items: [
          'Multi-currency invoicing with automatic NBRSM exchange rates — no manual entry',
          'Recurring invoices for monthly clients — automatic issuance every month',
          'Expense tracking with invoice scanning (OCR) — laptops, software, hosting',
          'VAT management: automatic input and output VAT calculation, DDV-04 preparation',
          'Bank reconciliation: automatic matching of PayPal/Wise payments with invoices',
          'Reports by client, month, and currency — always know how much you are earning',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for Macedonia: Registration, Rates and Obligations' },
      { slug: 'dooel-vodich-2026', title: 'How to Open a DOOEL in 2026: Step by Step' },
      { slug: 'faktura-primer-mk', title: 'Invoice Example: Mandatory Elements in Macedonia' },
    ],
    cta: {
      title: 'Invoice professionally, from day one',
      desc: 'Multi-currency invoices, automatic exchange rates, VAT calculation and expense tracking — all in Facturino.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti per IT freelancer dhe kompani ne Maqedoni',
    publishDate: '23 maj 2026',
    readTime: '9 min lexim',
    intro:
      'Sektori IT ne Maqedoni po rritet shpejt — me shume programues, dizajnere dhe konsulente po punojne si freelancer per klientet e huaj. Por, kontabiliteti i drejte eshte vendimtar: faturim shumevolutor, TVSH per sherbime te eksportuara, zgjedhja mes DOOEL dhe tatimit te sheshte, dhe deklarimi i te ardhurave nga platformat si Upwork dhe Fiverr. Ne kete udhezues mbulojme gjithcka qe IT freelancerat duhet ta dine.',
    sections: [
      {
        title: 'Faturimi shumevolutor (EUR/USD)',
        content:
          'Kur faturoni kliente te huaj ne EUR ose USD, valuta funksionale e kompanise suaj mbetet MKD. Cdo fature duhet te perfshije shumen ne valuten e huaj dhe ekuivalentin ne MKD te llogaritur me kursin mesatar te BQRM ne diten e fatures. Diferencat e kursit gjate arketimit regjistrohen si te ardhura ose shpenzime financiare.',
        items: [
          'Cdo fature duhet te perfshije shumen ne MKD prane valutes se huaj',
          'Kursi merret nga BQRM (Banka Qendrore) ne diten e leshimit te fatures',
          'Diferenca pozitive te kursit = te ardhura financiare, negative = shpenzime financiare',
          'Mbani evidencen e te gjitha diferencave te kursit — nevojiten per pasqyren vjetore financiare',
          'Transfertat PayPal/Wise/Payoneer konvertohen me kursin bankar — diferenca me BQRM eshte diference kursi',
        ],
        steps: null,
      },
      {
        title: 'TVSH per sherbime te eksportuara',
        content:
          'Sherbimet IT per kliente te huaj (B2B) jane te liruara nga TVSH me norme 0% sipas nenit 30, paragrafi 1, pika 23 e Ligjit per TVSH — vendi i furnizimit eshte ku ndodhet marresi. Megjithate, kjo nuk do te thote qe nuk duhet te regjistroheni per TVSH.',
        items: [
          'Sherbime B2B per kliente te BE/nderkombetare: TVSH 0% (reverse charge — klienti paguan TVSH ne vendin e tij)',
          'Duhet te regjistroheni per TVSH nese qarkullimi vjetor kalon 2.000.000 MKD (~32.500 EUR)',
          'Regjistrimi per TVSH eshte i dobishem edhe nen prag — mundeson zbritje te TVSH se hyrjes (laptop, softuer, zyre)',
          'Sherbimet e eksportuara raportohen ne formularin DDV-04 si qarkullim i liruar',
          'Ruani deshmite qe klienti eshte biznes (numri i TVSH, kontrate, profili i kompanise)',
        ],
        steps: null,
      },
      {
        title: 'DOOEL vs tatim i sheshte: Cili status eshte me i mire?',
        content:
          'Freelancerat ne Maqedoni zakonisht zgjedhin mes dy modeleve: DOOEL (shoqeri me pergjegjesi te kufizuar me nje anetar) dhe tatimpagues me norme te sheshte. Zgjedhja varet nga te ardhurat vjetore dhe struktura e shpenzimeve.',
        items: null,
        steps: [
          {
            step: 'Tatimpagues me norme te sheshte (tatim mujor fiks)',
            desc: 'Tatimpaguesi me norme te sheshte paguan nje tatim mujor fiks rreth 5.000-15.000 MKD (varesisht nga aktiviteti dhe komuna). Pa detyrim per mbajtje librash, pa detyrim per deklarim TVSH. Te ardhura maksimale vjetore: 3.000.000 MKD (~48.800 EUR). Ideal per fillestar me qarkullim te ulet.',
          },
          {
            step: 'DOOEL (10% tatim mbi fitimin)',
            desc: 'DOOEL paguan 10% tatim mbi fitimin (te ardhurat minus shpenzimet e njohura). Kontabiliteti i dyfishte eshte i detyrueshem. Duhet te regjistrohet per TVSH mbi 2M MKD qarkullim. Me i mire per freelancer me shpenzime te larta (pajisje, softuer, nenkontrakte) sepse shpenzimet e ulin bazen tatimore.',
          },
          {
            step: 'Kur te kaloni nga norma e sheshte ne DOOEL?',
            desc: 'Nese te ardhurat vjetore kalojne 3M MKD, duhet te kaloni. Por edhe nen prag, DOOEL eshte me i mire nese shpenzimet kalojne 30% te te ardhurave — norma efektive e tatimit DOOEL behet me e ulet. Gjithashtu, DOOEL jep kredibilitet me te madh me kliente te huaj.',
          },
        ],
      },
      {
        title: 'Te ardhura nga platformat e huaja',
        content:
          'Te ardhurat nga Upwork, Fiverr, Toptal dhe platforma te ngjashme jane plotesisht te tatueshme ne Maqedoni. Nuk ka perjashtim — pavaresisht nese parate jane ne PayPal, Wise ose direkt ne llogari bankare.',
        items: [
          'Te gjitha te ardhurat nga platformat duhet te deklarohen — duke perfshire pjesen e mbajtur nga platforma (komision)',
          'Komisioni i platformes (Upwork 10%, Fiverr 20%) eshte shpenzim biznesi i njohur — e ul tatimin',
          'Transfertat PayPal/Wise ne banke maqedonase: banka raporton hyrjet ne valute te huaj ne UJP',
          'Rekomandim: hapni llogari biznesi te vecante per te ardhurat e freelancer — me e lehte per evidencen',
          'Ruani raportet e platformes (statements) si deshmim per te ardhurat dhe komisionet',
          'Nese klienti paguan direkt (jo permes platformes), duhet patjeter te leshoni fature',
        ],
        steps: null,
      },
      {
        title: 'Zbritja per zyren ne shtepi',
        content:
          'Nese punoni nga shtepia, nje pjese e shpenzimeve te baneses mund te zbriten si shpenzim biznesi. Zbritja eshte proporcionale — e bazuar ne perqindjen e hapesires se perdorur per pune.',
        items: null,
        steps: [
          {
            step: 'Percaktoni hapesiren e punes',
            desc: 'Matni siperfaqen e dhomes se perdorur ekskluzivisht per pune. Nese banesa eshte 60m² dhe zyra eshte 12m², kjo eshte 20% e hapesires.',
          },
          {
            step: 'Llogaritni zbritjen',
            desc: 'Aplikoni perqindjen ne qira, rryme, ngrohje, internet dhe uje. Shembull: 20% e 15.000 MKD qira = 3.000 MKD zbritje mujore. Vjetore: 36.000 MKD me pak tatim.',
          },
          {
            step: 'Dokumentoni gjithcka',
            desc: 'Nevojitet kontrate qiraje (ose deshmim pronesie), fatura komunale ne emrin tuaj dhe foto te hapesires se punes. UJP mund te kerkoje deshmim gjate inspektimit.',
          },
          {
            step: 'Alternative: hapesire bashkepunimi (coworking)',
            desc: 'Anetaresimi mujor ne coworking eshte 100% i njohur si shpenzim biznesi — me i lehte per dokumentim. Shkupi ka disa opsione: Impact Hub, Sektor, Creative Hub etj.',
          },
        ],
      },
      {
        title: 'Gabimet e zakonshme te IT freelancerave',
        content:
          'Nga pervoja jone me qindra IT freelancer, keto jane gabimet me te zakonshme qe cojne ne probleme me UJP ose pagim te panevojshem te me shume tatimeve:',
        items: [
          'Nuk mbajne llogari biznesi te vecante — perzierja e transaksioneve personale dhe te biznesit e veshtireson kontabilitetin dhe krijon probleme gjate inspektimeve',
          'Nuk leshojne fatura per kliente te huaj — cdo e ardhur duhet te mbulohet me fature, edhe kur pagesa eshte permes PayPal',
          'Nuk kerkojne zbritje te TVSH se hyrjes — laptop, licenca softueri, hosting, domaine — te gjitha jane te zbritshme nese jeni i regjistruar per TVSH',
          'Harrojne deklarimin tremujor DDV-04 — afati eshte data 25 e muajit pas mbylljes se tremujorit. Deklarim i vonuar = gjobe',
          'Nuk regjistrojne diferencat e kursit — kur konvertoni nga EUR/USD ne MKD gjithmone ka diference. Duhet te regjistrohet',
          'Nuk planifikojne per tatimin mbi fitimin — vendosin 0 MKD menjane dhe ne fund te vitit nuk kane per tatime. Rekomandim: vendosni menjane 15% te cdo te ardhure',
        ],
        steps: null,
      },
      {
        title: 'Si i ndihmon Facturino IT freelancerat',
        content:
          'Facturino eshte ndertuar posacerisht per bizneset maqedonase qe punojne me kliente te huaj. Sistemi automatizon te gjithe procesin — nga faturimi deri te deklaratat e TVSH.',
        items: [
          'Faturim shumevolutor me kurse automatike nga BQRM — pa futje manuale',
          'Fatura te perseritura per kliente mujore — leshim automatik cdo muaj',
          'Gjurmim shpenzimesh me skanim faturash (OCR) — laptop, softuer, hosting',
          'Menaxhim TVSH: llogaritje automatike e TVSH se hyrjes dhe daljes, pergatitje DDV-04',
          'Rakordim bankar: perputhjje automatike e pagesave PayPal/Wise me faturat',
          'Raporte sipas klientit, muajit dhe valutes — gjithmone dini sa po fitoni',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    related: [
      { slug: 'ddv-vodich-mk', title: 'Udhezues TVSH per Maqedonine: Regjistrimi, normat dhe detyrimet' },
      { slug: 'dooel-vodich-2026', title: 'Si te hapni DOOEL ne 2026: Hap pas hapi' },
      { slug: 'faktura-primer-mk', title: 'Shembull fature: Elementet e detyrueshme ne Maqedoni' },
    ],
    cta: {
      title: 'Faturoni profesionalisht, qe nga dita e pare',
      desc: 'Fatura shumevolutore, kurse automatike, llogaritje TVSH dhe gjurmim shpenzimesh — te gjitha ne Facturino.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Sektor',
    title: 'Makedonya\'da IT Serbest Calisanlar ve Sirketler Icin Muhasebe',
    publishDate: '23 Mayis 2026',
    readTime: '9 dk okuma',
    intro:
      'Makedonya\'daki IT sektoru hizla buyuyor — daha fazla gelistirici, tasarimci ve danismanlar yabanci musteriler icin serbest calisan olarak calisiyor. Ancak dogru muhasebe kritik oneme sahiptir: cok para birimli faturalama, ihrac edilen hizmetlerde KDV, DOOEL ve goturu vergi arasinda secim ve Upwork ile Fiverr gibi platformlardan elde edilen gelirin beyani. Bu rehberde IT serbest calisanlarin bilmesi gereken her seyi ele aliyoruz.',
    sections: [
      {
        title: 'Cok para birimli faturalama (EUR/USD)',
        content:
          'Yabanci musterileri EUR veya USD olarak faturalarken, sirketinizin fonksiyonel para birimi MKD olarak kalir. Her fatura yabanci para birimindeki tutari ve fatura tarihindeki NBRSM (Merkez Bankasi) orta kurundan hesaplanan MKD karsiliini icermelidir. Tahsilattaki kur farklari finansal gelir veya gider olarak kaydedilir.',
        items: [
          'Her fatura yabanci para biriminin yaninda MKD tutarini icermelidir',
          'Kur, faturanin duzenlendii tarihte NBRSM\'den alinir',
          'Pozitif kur farklari = finansal gelir, negatif = finansal gider',
          'Tum kur farklarinin kaydini tutun — yillik mali tablo icin gereklidir',
          'PayPal/Wise/Payoneer transferleri banka kurundan donusturulur — NBRSM ile fark bir kur farkidir',
        ],
        steps: null,
      },
      {
        title: 'Ihrac edilen hizmetlerde KDV',
        content:
          'Yabanci musterilere sunulan IT hizmetleri (B2B), KDV Kanunu\'nun 30. maddesi, 1. fikrasi, 23. noktasi kapsaminda %0 KDV ile muaftir — tedarik yeri alicinin bulunduu yerdir. Ancak bu, KDV\'ye kaydolmaniz gerekmeyecei anlamina gelmez.',
        items: [
          'AB/uluslararasi musteriler icin B2B hizmetler: %0 KDV (ters yukleme — musteri kendi ulkesinde KDV oder)',
          'Yillik ciro 2.000.000 MKD\'yi (~32.500 EUR) asarsa KDV\'ye kaydolmaniz zorunludur',
          'KDV kaydinin esigin altinda bile faydalari vardir — giris KDV indirimi saglar (laptop, yazilim, ofis)',
          'Ihrac edilen hizmetler DDV-04 formunda muaf ciro olarak raporlanir',
          'Musterinin bir isletme olduguna dair kanitlari saklayin (KDV numarasi, sozlesme, sirket profili)',
        ],
        steps: null,
      },
      {
        title: 'DOOEL ve goturu vergi: Hangi statu daha iyi?',
        content:
          'Makedonya\'daki serbest calisanlar genellikle iki model arasinda secim yapar: DOOEL (tek ortakli limited sirket) ve goturu vergi mukellefi. Secim yillik gelire ve gider yapisina balidir.',
        items: null,
        steps: [
          {
            step: 'Goturu vergi mukellefi (sabit aylik vergi)',
            desc: 'Goturu vergi mukellefi, yaklasik 5.000-15.000 MKD (faaliyete ve belediyeye bagli) sabit aylik vergi oder. Defter tutma zorunluluu yoktur, KDV beyannamesi gereklilii yoktur. Maksimum yillik gelir: 3.000.000 MKD (~48.800 EUR). Dusuk cirolu yeni baslayanlar icin idealdir.',
          },
          {
            step: 'DOOEL (%10 kar vergisi)',
            desc: 'DOOEL, kar uzerinden %10 vergi oder (gelir eksi tanimis giderler). Cift girisli muhasebe zorunludur. 2M MKD cironun uzerinde KDV\'ye kaydolmasi gerekir. Yuksek giderleri olan serbest calisanlar icin daha iyidir (ekipman, yazilim, alt yuklenici) cunku giderler vergi matrahini dusurur.',
          },
          {
            step: 'Goturu vergiden DOOEL\'e ne zaman gecilmeli?',
            desc: 'Yillik gelir 3M MKD\'yi asarsa, gecmeniz zorunludur. Ancak esigin altinda bile, giderler gelirin %30\'unu asiyorsa DOOEL daha iyidir — efektif DOOEL vergi orani daha dusuk olur. Ayrica DOOEL yabanci musteriler nezdinde daha fazla guvenilirlik saglar.',
          },
        ],
      },
      {
        title: 'Yabanci platform geliri',
        content:
          'Upwork, Fiverr, Toptal ve benzeri platformlardan elde edilen gelir Makedonya\'da tamamen vergiye tabidir. Muafiyet yoktur — paranin PayPal\'da, Wise\'da veya dogrudan banka hesabinda olup olmadii onemli deildir.',
        items: [
          'Tum platform gelirleri beyan edilmelidir — platform tarafindan tutulan kisim (komisyon) dahil',
          'Platform komisyonu (Upwork %10, Fiverr %20) taninis isletme gideridir — verginizi dusurur',
          'Makedon bankasina PayPal/Wise transferleri: banka doviz girislerini UJP\'ye bildirir',
          'Oneri: serbest calisan gelirleri icin ayri bir isletme hesabi acin — kayit tutma icin daha kolay',
          'Gelir ve komisyon kaniti olarak platform raporlarini (statements) saklayin',
          'Musteri dogrudan odeme yapiyorsa (platform araciliiyla deil), mutlaka fatura duzenlemelisiniz',
        ],
        steps: null,
      },
      {
        title: 'Ev ofisi indirimi',
        content:
          'Evden calisiyorsaniz, daire/ev giderlerinizin bir kismi isletme gideri olarak indirilebilir. Indirim orantilidir — is icin kullanilan alan yuzdesine dayalidir.',
        items: null,
        steps: [
          {
            step: 'Calisma alanini belirleyin',
            desc: 'Yalnizca is icin kullanilan odanin alanini olcun. Daire 60m² ve ofis 12m² ise, bu alanin %20\'sidir.',
          },
          {
            step: 'Indirimi hesaplayin',
            desc: 'Yuzedeyi kira, elektrik, isitma, internet ve suya uygalayin. Ornek: 15.000 MKD kiranin %20\'si = 3.000 MKD aylik indirim. Yillik: 36.000 MKD daha az vergi.',
          },
          {
            step: 'Her seyi belgeleyin',
            desc: 'Kira sozlesmesi (veya mulkiyet kaniti), adiniza kayitli fatura belgeleri ve calisma alaninin fotograflari gereklidir. UJP denetim sirasinda kanit isteyebilir.',
          },
          {
            step: 'Alternatif: ortak calisma alani (coworking)',
            desc: 'Aylik coworking uyeligl %100 taninis isletme gideridir — belgelenmesi daha kolaydir. Uskup\'te cesitli secenekler vardir: Impact Hub, Sektor, Creative Hub vb.',
          },
        ],
      },
      {
        title: 'IT serbest calisanlarin yaptii yaygin hatalar',
        content:
          'Yuzlerce IT serbest calisanla olan deneyimimizden, UJP ile sorunlara veya gereksiz yere daha fazla vergi odemeye yol acan en yaygin hatalar sunlardir:',
        items: [
          'Ayri isletme hesabi tutmamak — kisisel ve ticari islemlerin karistirilmasi muhasebeyi zorlastirir ve denetimlerde sorun yaratir',
          'Yabanci musteriler icin fatura duzenlememek — her gelir fatura ile kaplanmalidir, odeme PayPal uzerinden olsa bile',
          'Giris KDV indirimi talep etmemek — laptoplar, yazilim lisanslari, hosting, alan adlari — KDV muelle iseniz bunlarin hepsi KDV indirilebilir',
          'Ucaylik DDV-04 beyannamesini unutmak — son tarih ceyrek sonunu takip eden ayin 25\'idir. Gec beyanname = ceza',
          'Kur farklarini kaydetmemek — EUR/USD\'den MKD\'ye donusturmede her zaman bir fark olur. Kaydedilmelidir',
          'Kar vergisi icin planlama yapmamak — 0 MKD ayirmak ve yil sonunda vergi icin parasi olmamak. Oneri: her gelirin %15\'ini ayirin',
        ],
        steps: null,
      },
      {
        title: 'Facturino IT serbest calisanlara nasil yardimci olur',
        content:
          'Facturino, yabanci musterilerle calisan Makedon isletmeleri icin ozel olarak insa edilmistir. Sistem tum sureci otomatiklestirir — faturalamanin KDV beyanlarına kadar.',
        items: [
          'NBRSM\'den otomatik kurlarla cok para birimli faturalama — manuel giris yok',
          'Aylik musteriler icin tekrarlanan faturalar — her ay otomatik duzenleme',
          'Fatura tarama (OCR) ile gider takibi — laptoplar, yazilim, hosting',
          'KDV yonetimi: otomatik giris ve cikis KDV hesaplamasi, DDV-04 hazirlii',
          'Banka mutabakati: PayPal/Wise odemelerinin faturalarla otomatik eslestirilmesi',
          'Musteriye, aya ve para birimine gore raporlar — ne kadar kazandiinizi her zaman bilin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili makaleler',
    related: [
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV Rehberi: Kayit, Oranlar ve Yukumlulukler' },
      { slug: 'dooel-vodich-2026', title: '2026\'da DOOEL Nasil Acilir: Adim Adim' },
      { slug: 'faktura-primer-mk', title: 'Fatura Ornegi: Makedonya\'da Zorunlu Unsurlar' },
    ],
    cta: {
      title: 'Ilk gunden profesyonel faturalayin',
      desc: 'Cok para birimli faturalar, otomatik kurlar, KDV hesaplamasi ve gider takibi — hepsi Facturino\'da.',
      button: 'Ucretsiz basla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function SmetkovodstvoZaItFreelancerPage({
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
    slug: 'smetkovodstvo-za-it-freelancer',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['freelancer', 'IT accounting', 'north macedonia', 'VAT', 'multi-currency', 'invoicing', 'DOOEL'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-it-freelancer` },
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
