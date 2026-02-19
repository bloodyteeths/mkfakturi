import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/trudovo-pravo-osnovi', {
    title: {
      mk: 'Трудово право: 10 работи што секој работодавач мора да ги знае — Facturino',
      en: 'Labor Law: 10 Things Every Employer Must Know — Facturino',
      sq: 'E drejta e punës: 10 gjëra që çdo punëdhënës duhet t\'i dijë — Facturino',
      tr: 'İş hukuku: Her işverenin bilmesi gereken 10 şey — Facturino',
    },
    description: {
      mk: 'Дознајте ги 10-те најважни правила од трудовото право во Македонија: договори, работно време, одмори, отказни рокови и казни.',
      en: 'Learn the 10 most important labor law rules in Macedonia: contracts, working hours, leave, notice periods and penalties.',
      sq: 'Mësoni 10 rregullat më të rëndësishme të së drejtës së punës në Maqedoni: kontrata, orari, pushimet, afatet e njoftimit dhe gjobat.',
      tr: 'Makedonya\'da en önemli 10 iş hukuku kuralını öğrenin: sözleşmeler, çalışma saatleri, izinler, ihbar süreleri ve cezalar.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Едукација',
    title: 'Трудово право: 10 работи што секој работодавач мора да ги знае',
    publishDate: '18 февруари 2026',
    readTime: '8 мин читање',
    intro: 'Македонското трудово законодавство поставува јасни правила за работодавачите и вработените. Непознавањето на овие правила може да доведе до сериозни казни од Државниот инспекторат за труд. Овој водич ги покрива 10-те најважни аспекти на трудовото право кои секој работодавач во Македонија мора да ги знае и почитува.',
    sections: [
      {
        title: '1. Писмен договор за вработување',
        content: 'Секој работен однос мора да биде формализиран со писмен договор за вработување. Договорот мора да содржи: име и адреса на работодавачот и вработениот, датум на почеток, работно место и опис на работни задачи, работно време, плата, траење (определено или неопределено време). Договорот мора да се склучи пред денот на стапување на работа. Работодавачот е должен да го пријави вработениот во Агенцијата за вработување (АВРМ) и во Фондот за здравствено осигурување.',
        items: null,
      },
      {
        title: '2. Работно време: 40 часа неделно',
        content: 'Полното работно време во Македонија изнесува 40 часа неделно, односно 8 часа дневно. Работното време не може да биде подолго од 40 часа неделно, освен во случаи на прекувремена работа. Работодавачот е должен да обезбеди пауза од најмалку 30 минути за работно време подолго од 6 часа. Скратено работно време (помалку од 40 часа) може да се договори за работни места со посебни услови.',
        items: null,
      },
      {
        title: '3. Прекувремена работа',
        content: 'Прекувремената работа е дозволена, но строго регулирана. Вработениот не може да работи повеќе од 8 часа прекувремена работа неделно и максимум 190 часа годишно. Прекувремената работа мора да биде платена најмалку 135% од основната плата. Работодавачот е должен да води евиденција за прекувремена работа за секој вработен.',
        items: [
          'Максимум 8 часа прекувремена работа неделно',
          'Годишен лимит: 190 часа прекувремена работа',
          'Задолжителен додаток: минимум 35% над основна плата',
          'Работа ноќе (22:00 - 06:00): додаток од 35%',
          'Работа на државен празник: додаток од 50%',
          'Задолжителна писмена евиденција за секој час',
        ],
      },
      {
        title: '4. Минимална плата',
        content: 'Минималната плата во Македонија се утврдува со закон и се ажурира периодично. Ниту еден работодавач не смее да исплати плата пониска од утврдениот минимум. Минималната нето плата за 2026 година изнесува приближно 20.175 денари. Платата мора да се исплати најдоцна до 15-ти во наредниот месец. Работодавачот е должен да издаде платен лист (пејслип) со детална разбивка на бруто плата, придонеси и нето плата.',
        items: null,
      },
      {
        title: '5. Годишен одмор',
        content: 'Секој вработен има право на платен годишен одмор од најмалку 20 работни дена. Бројот на денови може да се зголеми врз основа на работен стаж, сложеност на работата и други критериуми, но не може да надмине 26 работни дена. Годишниот одмор може да се користи во делови, но еден дел мора да биде најмалку 10 последователни работни дена.',
        items: [
          'Минимум 20 работни дена годишен одмор',
          'Максимум 26 работни дена (со додатоци за стаж)',
          'Најмалку 10 последователни дена во еден дел',
          'Неискористен одмор се пренесува до 30 јуни следната година',
          'Работодавачот не може да го одбие одморот без основана причина',
          'При престанок на работен однос — право на компензација за неискористен одмор',
        ],
      },
      {
        title: '6. Боледување',
        content: 'Вработените имаат право на платено боледување. Првите 30 дена од боледувањето се на товар на работодавачот, кој исплаќа 70% од основната плата. По 30-тиот ден, боледувањето преминува на товар на Фондот за здравствено осигурување (ФЗОМ). За боледување е потребен лекарски извештај од матичен лекар или специјалист. Работодавачот е должен да го пријави боледувањето во МПИН образецот.',
        items: null,
      },
      {
        title: '7. Породилно отсуство',
        content: 'Работничките имаат право на породилно отсуство од 9 месеци со целосна плата (односно со надоместок од ФЗОМ). За близначка бременост и за трето и секое наредно дете, породилното отсуство изнесува 12 месеци. Татковците имаат право на платено отсуство од 7 работни дена по раѓање на дете. Работодавачот не смее да го откаже договорот за вработување на бремена работничка или работничка на породилно отсуство.',
        items: [
          'Породилно отсуство: 9 месеци со надоместок',
          'Близнаци или трето дете: 12 месеци',
          'Татковско отсуство: 7 работни дена',
          'Заштита од отказ за време на бременост и породилно',
          'Право на враќање на исто или еквивалентно работно место',
          'Забрана за ноќна и прекувремена работа за бремени работнички',
        ],
      },
      {
        title: '8. Отказни рокови',
        content: 'При давање отказ, и работодавачот и вработениот се должни да почитуваат отказен рок. Минималниот отказен рок е 1 месец, а може да биде до 3 месеци во зависност од работниот стаж и договорот. За време на пробниот период (до 6 месеци), отказниот рок е 7 дена. Работодавачот може да го ослободи вработениот од работа за време на отказниот рок, но мора да ја исплати платата за тој период.',
        items: null,
      },
      {
        title: '9. Правила за прекин на работен однос',
        content: 'Работодавачот може да го прекине работниот однос само од основани причини: деловни причини (технолошки вишок), лични причини (неспособност за работа) или причини на однесување (кршење на работна дисциплина). Пред откажување мора да се спроведе законска постапка.',
        items: [
          'Задолжително писмено предупредување пред отказ поради однесување',
          'Отказ поради деловни причини: задолжителна програма за решавање на вишокот',
          'Право на отпремнина: минимум 1 нето плата за секои 2 години стаж',
          'Забрана за дискриминаторски отказ (пол, возраст, етничка припадност)',
          'Вработениот може да поднесе тужба во рок од 15 дена',
          'Судска заштита: враќање на работа или надоместок на штета',
        ],
      },
      {
        title: '10. Казни од инспекцијата за труд',
        content: 'Државниот инспекторат за труд врши редовни и вонредни контроли кај работодавачите. Непочитувањето на трудовото законодавство повлекува сериозни казни кои може да го загрозат бизнисот.',
        items: [
          'Непријавен работник: казна од 3.000 до 5.000 евра во денарска противвредност',
          'Неисплатена плата: казна од 1.000 до 3.000 евра',
          'Непочитување на работно време: казна од 500 до 1.500 евра',
          'Непочитување на безбедност на работа: казна до 5.000 евра',
          'Повторен прекршок: казната се удвојува',
          'Тешки прекршоци: можно привремено затворање на деловна активност',
        ],
      },
      {
        title: 'Како Facturino ви помага со усогласеност',
        content: 'Facturino ви помага да ги почитувате сите законски обврски поврзани со вработените. Нашиот систем автоматски ги пресметува платите согласно минималната плата, го следи прекувремената работа, управува со годишните одмори и боледувањата, и генерира МПИН образци подготвени за УЈП. Со Facturino, секогаш сте во согласност со трудовото право.',
        items: [
          'Автоматска пресметка на плати согласно законски минимум',
          'Следење на прекувремена работа и лимити',
          'Управување со годишни одмори и боледувања',
          'Генерирање на МПИН и платни листови',
          'Потсетници за законски рокови',
          'Евиденција за инспекциски контроли',
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија: Придонеси и даноци' },
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
      { slug: 'personalen-danok-na-dohod', title: 'Персонален данок на доход во Македонија' },
    ],
    cta: {
      title: 'Бидете усогласени со трудовото право',
      desc: 'Facturino ви помага автоматски да ги почитувате сите законски обврски кон вработените.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Education',
    title: 'Labor Law: 10 Things Every Employer Must Know',
    publishDate: 'February 18, 2026',
    readTime: '8 min read',
    intro: 'Macedonian labor legislation sets clear rules for employers and employees. Not knowing these rules can lead to serious penalties from the State Labor Inspectorate. This guide covers the 10 most important aspects of labor law that every employer in Macedonia must know and comply with.',
    sections: [
      {
        title: '1. Written Employment Contract',
        content: 'Every employment relationship must be formalized with a written employment contract. The contract must contain: the name and address of the employer and employee, start date, workplace and job description, working hours, salary, and duration (fixed-term or indefinite). The contract must be concluded before the first day of work. The employer is required to register the employee with the Employment Agency (AVRM) and the Health Insurance Fund.',
        items: null,
      },
      {
        title: '2. Working Hours: 40 Hours Per Week',
        content: 'Full-time working hours in Macedonia are 40 hours per week, or 8 hours per day. Working hours cannot exceed 40 hours per week, except in cases of overtime. The employer must provide a break of at least 30 minutes for working periods longer than 6 hours. Reduced working hours (less than 40 hours) can be agreed upon for positions with special conditions.',
        items: null,
      },
      {
        title: '3. Overtime Work',
        content: 'Overtime work is permitted but strictly regulated. An employee cannot work more than 8 hours of overtime per week and a maximum of 190 hours per year. Overtime must be paid at least 135% of the base salary. The employer is required to keep records of overtime for each employee.',
        items: [
          'Maximum 8 hours of overtime per week',
          'Annual limit: 190 hours of overtime',
          'Mandatory supplement: minimum 35% above base salary',
          'Night work (22:00 - 06:00): 35% supplement',
          'Work on public holidays: 50% supplement',
          'Mandatory written records for every hour',
        ],
      },
      {
        title: '4. Minimum Wage',
        content: 'The minimum wage in Macedonia is determined by law and updated periodically. No employer may pay a salary below the established minimum. The net minimum wage for 2026 is approximately 20,175 denars. Salaries must be paid by the 15th of the following month. The employer is required to issue a payslip with a detailed breakdown of gross salary, contributions, and net salary.',
        items: null,
      },
      {
        title: '5. Annual Leave',
        content: 'Every employee has the right to paid annual leave of at least 20 working days. The number of days can increase based on years of service, job complexity, and other criteria, but cannot exceed 26 working days. Annual leave can be used in parts, but one portion must be at least 10 consecutive working days.',
        items: [
          'Minimum 20 working days of annual leave',
          'Maximum 26 working days (with seniority additions)',
          'At least 10 consecutive days in one portion',
          'Unused leave carries over until June 30 of the following year',
          'Employer cannot refuse leave without justified reason',
          'Upon termination — right to compensation for unused leave',
        ],
      },
      {
        title: '6. Sick Leave',
        content: 'Employees are entitled to paid sick leave. The first 30 days of sick leave are covered by the employer, who pays 70% of the base salary. After the 30th day, sick leave costs are transferred to the Health Insurance Fund (FZOM). Sick leave requires a medical report from a general practitioner or specialist. The employer must report sick leave in the MPIN form.',
        items: null,
      },
      {
        title: '7. Maternity Leave',
        content: 'Female employees are entitled to maternity leave of 9 months with full salary compensation (covered by FZOM). For twin pregnancies and the third or subsequent child, maternity leave is 12 months. Fathers are entitled to 7 paid working days of paternity leave after the birth of a child. The employer may not terminate the employment contract of a pregnant employee or an employee on maternity leave.',
        items: [
          'Maternity leave: 9 months with compensation',
          'Twins or third child: 12 months',
          'Paternity leave: 7 working days',
          'Protection from dismissal during pregnancy and maternity',
          'Right to return to the same or equivalent position',
          'Prohibition of night and overtime work for pregnant employees',
        ],
      },
      {
        title: '8. Notice Periods',
        content: 'When giving notice, both the employer and employee must observe a notice period. The minimum notice period is 1 month and can be up to 3 months depending on years of service and the contract. During the probation period (up to 6 months), the notice period is 7 days. The employer may release the employee from work during the notice period but must pay the salary for that period.',
        items: null,
      },
      {
        title: '9. Termination Rules',
        content: 'The employer may terminate employment only for justified reasons: business reasons (technological redundancy), personal reasons (inability to perform work), or behavioral reasons (breach of work discipline). A legal procedure must be followed before termination.',
        items: [
          'Mandatory written warning before termination for behavioral reasons',
          'Business redundancy: mandatory program for resolving surplus staff',
          'Severance pay: minimum 1 net salary for every 2 years of service',
          'Prohibition of discriminatory dismissal (gender, age, ethnicity)',
          'Employee can file a lawsuit within 15 days',
          'Court protection: reinstatement or damages compensation',
        ],
      },
      {
        title: '10. Labor Inspection Penalties',
        content: 'The State Labor Inspectorate conducts regular and extraordinary inspections of employers. Non-compliance with labor legislation carries serious penalties that can endanger the business.',
        items: [
          'Unregistered worker: fine of EUR 3,000 to 5,000 equivalent in denars',
          'Unpaid wages: fine of EUR 1,000 to 3,000',
          'Working hours violations: fine of EUR 500 to 1,500',
          'Workplace safety violations: fine up to EUR 5,000',
          'Repeat offense: the fine is doubled',
          'Serious violations: possible temporary business closure',
        ],
      },
      {
        title: 'How Facturino Helps With Compliance',
        content: 'Facturino helps you comply with all legal obligations related to employees. Our system automatically calculates salaries according to the minimum wage, tracks overtime, manages annual leave and sick leave, and generates MPIN forms ready for UJP. With Facturino, you are always in compliance with labor law.',
        items: [
          'Automatic salary calculation according to legal minimum',
          'Overtime tracking and limit monitoring',
          'Annual leave and sick leave management',
          'MPIN and payslip generation',
          'Reminders for legal deadlines',
          'Records ready for inspection audits',
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Payroll Calculation in Macedonia: Contributions and Taxes' },
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
      { slug: 'personalen-danok-na-dohod', title: 'Personal Income Tax in Macedonia' },
    ],
    cta: {
      title: 'Stay Compliant With Labor Law',
      desc: 'Facturino automatically helps you meet all legal obligations toward employees.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Edukim',
    title: 'E drejta e punës: 10 gjëra që çdo punëdhënës duhet t\'i dijë',
    publishDate: '18 shkurt 2026',
    readTime: '8 min lexim',
    intro: 'Legjislacioni i punës në Maqedoni vendos rregulla të qarta për punëdhënësit dhe punonjësit. Mosnjohja e këtyre rregullave mund të çojë në gjoba serioze nga Inspektorati Shtetëror i Punës. Ky udhëzues mbulon 10 aspektet më të rëndësishme të së drejtës së punës që çdo punëdhënës në Maqedoni duhet t\'i njohë dhe t\'i respektojë.',
    sections: [
      {
        title: '1. Kontratë pune e shkruar',
        content: 'Çdo marrëdhënie pune duhet të formalizohet me kontratë pune të shkruar. Kontrata duhet të përmbajë: emrin dhe adresën e punëdhënësit dhe punonjësit, datën e fillimit, vendin e punës dhe përshkrimin e detyrave, orarin e punës, pagën dhe kohëzgjatjen (afat i caktuar ose i pacaktuar). Kontrata duhet të lidhet para ditës së parë të punës. Punëdhënësi është i detyruar ta regjistrojë punonjësin në Agjencinë e Punësimit (AVRM) dhe në Fondin e Sigurimit Shëndetësor.',
        items: null,
      },
      {
        title: '2. Orari i punës: 40 orë në javë',
        content: 'Orari i plotë i punës në Maqedoni është 40 orë në javë, ose 8 orë në ditë. Orari i punës nuk mund të kalojë 40 orë në javë, përveç rasteve të punës jashtë orarit. Punëdhënësi duhet të sigurojë pushim prej së paku 30 minutash për periudha pune më të gjata se 6 orë. Orari i shkurtuar (më pak se 40 orë) mund të bihet dakord për pozicione me kushte të veçanta.',
        items: null,
      },
      {
        title: '3. Puna jashtë orarit',
        content: 'Puna jashtë orarit lejohet por rregullohet rreptësisht. Punonjësi nuk mund të punojë më shumë se 8 orë jashtë orarit në javë dhe maksimum 190 orë në vit. Puna jashtë orarit duhet të paguhet së paku 135% e pagës bazë. Punëdhënësi duhet të mbajë evidencë për punën jashtë orarit për çdo punonjës.',
        items: [
          'Maksimum 8 orë punë jashtë orarit në javë',
          'Limiti vjetor: 190 orë punë jashtë orarit',
          'Shtesë e detyrueshme: minimumi 35% mbi pagën bazë',
          'Puna natën (22:00 - 06:00): shtesë 35%',
          'Puna në festa zyrtare: shtesë 50%',
          'Evidencë e detyrueshme e shkruar për çdo orë',
        ],
      },
      {
        title: '4. Paga minimale',
        content: 'Paga minimale në Maqedoni përcaktohet me ligj dhe përditësohet periodikisht. Asnjë punëdhënës nuk mund të paguajë pagë më të ulët se minimumi i përcaktuar. Paga minimale neto për vitin 2026 është afërsisht 20.175 denarë. Paga duhet të paguhet deri më datën 15 të muajit pasues. Punëdhënësi duhet të lëshojë fletëpagesë me ndarje të detajuar të pagës bruto, kontributeve dhe pagës neto.',
        items: null,
      },
      {
        title: '5. Pushimi vjetor',
        content: 'Çdo punonjës ka të drejtë pushimi vjetor të paguar prej së paku 20 ditësh pune. Numri i ditëve mund të rritet bazuar në vitet e shërbimit, kompleksitetin e punës dhe kritere të tjera, por nuk mund të kalojë 26 ditë pune. Pushimi vjetor mund të përdoret në pjesë, por një pjesë duhet të jetë së paku 10 ditë pune të njëpasnjëshme.',
        items: [
          'Minimumi 20 ditë pune pushim vjetor',
          'Maksimumi 26 ditë pune (me shtesa për vjetërsi)',
          'Së paku 10 ditë të njëpasnjëshme në një pjesë',
          'Pushimi i papërdorur bartet deri më 30 qershor të vitit tjetër',
          'Punëdhënësi nuk mund ta refuzojë pushimin pa arsye të justifikuar',
          'Pas përfundimit të punës — e drejtë kompensimi për pushim të papërdorur',
        ],
      },
      {
        title: '6. Pushimi mjekësor',
        content: 'Punonjësit kanë të drejtë pushimi mjekësor të paguar. 30 ditët e para të pushimit mjekësor mbulohen nga punëdhënësi, i cili paguan 70% të pagës bazë. Pas ditës së 30-të, kostot e pushimit mjekësor kalojnë te Fondi i Sigurimit Shëndetësor (FZOM). Pushimi mjekësor kërkon raport mjekësor nga mjeku i përgjithshëm ose specialisti. Punëdhënësi duhet ta raportojë pushimin mjekësor në formularin MPIN.',
        items: null,
      },
      {
        title: '7. Pushimi i lindjes',
        content: 'Punonjëset femra kanë të drejtë pushimi lindjeje prej 9 muajsh me pagë të plotë (kompensim nga FZOM). Për shtatzëni binjake dhe fëmijën e tretë ose më pas, pushimi i lindjes është 12 muaj. Baballarët kanë të drejtë 7 ditësh pune pushim atësor të paguar pas lindjes së fëmijës. Punëdhënësi nuk mund ta ndërpresë kontratën e punës së punonjëses shtatzënë ose në pushim lindjeje.',
        items: [
          'Pushimi i lindjes: 9 muaj me kompensim',
          'Binjakë ose fëmija e tretë: 12 muaj',
          'Pushimi atësor: 7 ditë pune',
          'Mbrojtje nga shkarkimi gjatë shtatzënisë dhe lindjes',
          'E drejtë kthimi në pozicionin e njëjtë ose ekuivalent',
          'Ndalim i punës natën dhe jashtë orarit për punonjëset shtatzëna',
        ],
      },
      {
        title: '8. Afatet e njoftimit',
        content: 'Kur jepet njoftimi, si punëdhënësi ashtu edhe punonjësi duhet të respektojnë afatin e njoftimit. Afati minimal i njoftimit është 1 muaj dhe mund të jetë deri 3 muaj në varësi të viteve të shërbimit dhe kontratës. Gjatë periudhës provuese (deri 6 muaj), afati i njoftimit është 7 ditë. Punëdhënësi mund ta lirojë punonjësin nga puna gjatë periudhës së njoftimit por duhet ta paguajë pagën për atë periudhë.',
        items: null,
      },
      {
        title: '9. Rregullat e ndërprerjes',
        content: 'Punëdhënësi mund ta ndërpresë punësimin vetëm për arsye të justifikuara: arsye biznesi (tepricë teknologjike), arsye personale (paaftësi për punë) ose arsye sjelljeje (shkelje e disiplinës). Para ndërprerjes duhet ndjekur procedura ligjore.',
        items: [
          'Paralajmërim i detyrueshëm i shkruar para shkarkimit për arsye sjelljeje',
          'Teprica e biznesit: program i detyrueshëm për zgjidhjen e stafit tepër',
          'Kompensimi i largimit: minimumi 1 pagë neto për çdo 2 vjet shërbimi',
          'Ndalim i shkarkimit diskriminues (gjini, moshë, përkatësi etnike)',
          'Punonjësi mund të paraqesë padi brenda 15 ditësh',
          'Mbrojtja gjyqësore: rikthim në punë ose kompensim dëmi',
        ],
      },
      {
        title: '10. Gjobat nga inspeksioni i punës',
        content: 'Inspektorati Shtetëror i Punës kryen kontrolle të rregullta dhe të jashtëzakonshme te punëdhënësit. Mosrespektimi i legjislacionit të punës sjell gjoba serioze që mund ta rrezikojnë biznesin.',
        items: [
          'Punonjës i paregjistruar: gjobë 3.000 deri 5.000 euro ekuivalent në denarë',
          'Pagë e papaguar: gjobë 1.000 deri 3.000 euro',
          'Shkelje e orarit të punës: gjobë 500 deri 1.500 euro',
          'Shkelje e sigurisë në punë: gjobë deri 5.000 euro',
          'Shkelje e përsëritur: gjoba dyfishohet',
          'Shkelje serioze: mbyllje e mundshme e përkohshme e biznesit',
        ],
      },
      {
        title: 'Si ju ndihmon Facturino me përputhshmërinë',
        content: 'Facturino ju ndihmon të respektoni të gjitha detyrimet ligjore lidhur me punonjësit. Sistemi ynë llogarit automatikisht pagat sipas pagës minimale, ndjek punën jashtë orarit, menaxhon pushimet vjetore dhe mjekësore, dhe gjeneron formularë MPIN gati për UJP. Me Facturino, gjithmonë jeni në përputhje me ligjin e punës.',
        items: [
          'Llogaritje automatike e pagave sipas minimumit ligjor',
          'Ndjekje e punës jashtë orarit dhe limiteve',
          'Menaxhim i pushimeve vjetore dhe mjekësore',
          'Gjenerim i MPIN dhe fletëpagesave',
          'Kujtuese për afatet ligjore',
          'Evidencë gati për kontrolle inspektuese',
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet' },
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhëzues për llogaritjen mujore' },
      { slug: 'personalen-danok-na-dohod', title: 'Tatimi personal mbi të ardhurat në Maqedoni' },
    ],
    cta: {
      title: 'Qëndroni në përputhje me ligjin e punës',
      desc: 'Facturino ju ndihmon automatikisht të përmbushni të gjitha detyrimet ligjore ndaj punonjësve.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Eğitim',
    title: 'İş hukuku: Her işverenin bilmesi gereken 10 şey',
    publishDate: '18 Şubat 2026',
    readTime: '8 dk okuma',
    intro: 'Makedonya iş mevzuatı, işverenler ve çalışanlar için net kurallar koymaktadır. Bu kuralları bilmemek, Devlet Çalışma Müfettişliği\'nden ciddi cezalara yol açabilir. Bu rehber, Makedonya\'daki her işverenin bilmesi ve uyması gereken iş hukukunun en önemli 10 yönünü kapsamaktadır.',
    sections: [
      {
        title: '1. Yazılı İş Sözleşmesi',
        content: 'Her iş ilişkisi yazılı bir iş sözleşmesiyle resmileştirilmelidir. Sözleşme şunları içermelidir: işveren ve çalışanın adı ve adresi, başlangıç tarihi, işyeri ve görev tanımı, çalışma saatleri, maaş ve süre (belirli veya belirsiz süreli). Sözleşme, ilk iş gününden önce akdedilmelidir. İşveren, çalışanı İstihdam Ajansı (AVRM) ve Sağlık Sigortası Fonu\'na kaydetmekle yükümlüdür.',
        items: null,
      },
      {
        title: '2. Çalışma Saatleri: Haftada 40 Saat',
        content: 'Makedonya\'da tam zamanlı çalışma saatleri haftada 40 saat veya günde 8 saattir. Fazla mesai durumları hariç çalışma saatleri haftada 40 saati aşamaz. İşveren, 6 saatten uzun çalışma dönemleri için en az 30 dakikalık mola sağlamalıdır. Özel koşullara sahip pozisyonlar için azaltılmış çalışma saatleri (40 saatten az) kararlaştırılabilir.',
        items: null,
      },
      {
        title: '3. Fazla Mesai',
        content: 'Fazla mesaiye izin verilir ancak sıkı bir şekilde düzenlenir. Bir çalışan haftada 8 saatten ve yılda en fazla 190 saatten fazla mesai yapamaz. Fazla mesai, temel maaşın en az %135\'i oranında ödenmelidir. İşveren, her çalışan için fazla mesai kayıtları tutmak zorundadır.',
        items: [
          'Haftada maksimum 8 saat fazla mesai',
          'Yıllık limit: 190 saat fazla mesai',
          'Zorunlu ek ödeme: temel maaşın en az %35 üstü',
          'Gece çalışması (22:00 - 06:00): %35 ek ödeme',
          'Resmi tatillerde çalışma: %50 ek ödeme',
          'Her saat için zorunlu yazılı kayıt',
        ],
      },
      {
        title: '4. Asgari Ücret',
        content: 'Makedonya\'da asgari ücret yasayla belirlenir ve periyodik olarak güncellenir. Hiçbir işveren belirlenen asgari ücretten düşük maaş ödeyemez. 2026 yılı için net asgari ücret yaklaşık 20.175 denardır. Maaşlar takip eden ayın 15\'ine kadar ödenmelidir. İşveren, brüt maaş, katkı payları ve net maaşın ayrıntılı dökümünü içeren bir bordro düzenlemelidir.',
        items: null,
      },
      {
        title: '5. Yıllık İzin',
        content: 'Her çalışanın en az 20 iş günü ücretli yıllık izin hakkı vardır. Gün sayısı hizmet yılına, iş karmaşıklığına ve diğer kriterlere bağlı olarak artabilir ancak 26 iş gününü aşamaz. Yıllık izin parçalar halinde kullanılabilir ancak bir kısmı en az 10 ardışık iş günü olmalıdır.',
        items: [
          'Minimum 20 iş günü yıllık izin',
          'Maksimum 26 iş günü (kıdem eklemeleriyle)',
          'Bir kısımda en az 10 ardışık gün',
          'Kullanılmayan izin ertesi yılın 30 Haziran\'ına kadar devredilir',
          'İşveren haklı sebep olmadan izni reddedemez',
          'İş akdi feshinde — kullanılmayan izin için tazminat hakkı',
        ],
      },
      {
        title: '6. Hastalık İzni',
        content: 'Çalışanlar ücretli hastalık izni hakkına sahiptir. Hastalık izninin ilk 30 günü, temel maaşın %70\'ini ödeyen işveren tarafından karşılanır. 30. günden sonra hastalık izni maliyetleri Sağlık Sigortası Fonu\'na (FZOM) aktarılır. Hastalık izni için aile hekimi veya uzmandan tıbbi rapor gereklidir. İşveren, hastalık iznini MPIN formunda bildirmelidir.',
        items: null,
      },
      {
        title: '7. Doğum İzni',
        content: 'Kadın çalışanlar, tam maaş karşılığı (FZOM tarafından karşılanan) 9 aylık doğum izni hakkına sahiptir. İkiz gebelikler ve üçüncü veya sonraki çocuklar için doğum izni 12 aydır. Babalar, çocuğun doğumundan sonra 7 iş günü ücretli babalık izni hakkına sahiptir. İşveren, hamile bir çalışanın veya doğum iznindeki bir çalışanın iş sözleşmesini feshedemez.',
        items: [
          'Doğum izni: tazminatlı 9 ay',
          'İkizler veya üçüncü çocuk: 12 ay',
          'Babalık izni: 7 iş günü',
          'Hamilelik ve doğum süresince işten çıkarmaya karşı koruma',
          'Aynı veya eşdeğer pozisyona dönme hakkı',
          'Hamile çalışanlar için gece ve fazla mesai çalışması yasağı',
        ],
      },
      {
        title: '8. İhbar Süreleri',
        content: 'İhbar verilirken hem işveren hem de çalışan ihbar süresine uymalıdır. Minimum ihbar süresi 1 aydır ve hizmet yılı ile sözleşmeye bağlı olarak 3 aya kadar çıkabilir. Deneme süresi boyunca (6 aya kadar) ihbar süresi 7 gündür. İşveren, çalışanı ihbar süresi boyunca işten muaf tutabilir ancak o dönem için maaşı ödemelidir.',
        items: null,
      },
      {
        title: '9. Fesih Kuralları',
        content: 'İşveren, istihdamı yalnızca haklı nedenlerle sonlandırabilir: iş nedenleri (teknolojik fazlalık), kişisel nedenler (iş yapamama) veya davranış nedenleri (iş disiplini ihlali). Fesihten önce yasal prosedür izlenmelidir.',
        items: [
          'Davranış nedeniyle fesihten önce zorunlu yazılı uyarı',
          'İş fazlalığı: fazla personeli çözmek için zorunlu program',
          'Kıdem tazminatı: her 2 yıllık hizmet için minimum 1 net maaş',
          'Ayrımcı işten çıkarma yasağı (cinsiyet, yaş, etnisite)',
          'Çalışan 15 gün içinde dava açabilir',
          'Yargı koruması: işe iade veya tazminat',
        ],
      },
      {
        title: '10. Çalışma Müfettişliği Cezaları',
        content: 'Devlet Çalışma Müfettişliği, işverenlere yönelik olağan ve olağanüstü denetimler yürütür. İş mevzuatına uyumsuzluk, işletmeyi tehlikeye atabilecek ciddi cezalar doğurur.',
        items: [
          'Kayıt dışı çalışan: 3.000 ila 5.000 avro karşılığı denar ceza',
          'Ödenmemiş ücretler: 1.000 ila 3.000 avro ceza',
          'Çalışma saati ihlalleri: 500 ila 1.500 avro ceza',
          'İşyeri güvenliği ihlalleri: 5.000 avroya kadar ceza',
          'Tekrarlayan ihlal: ceza iki katına çıkar',
          'Ciddi ihlaller: olası geçici iş kapatma',
        ],
      },
      {
        title: 'Facturino Uyumlulukta Nasıl Yardımcı Olur',
        content: 'Facturino, çalışanlarla ilgili tüm yasal yükümlülüklere uymanıza yardımcı olur. Sistemimiz, asgari ücrete göre maaşları otomatik olarak hesaplar, fazla mesaiyi takip eder, yıllık izin ve hastalık izinlerini yönetir ve UJP\'ye hazır MPIN formları oluşturur. Facturino ile her zaman iş hukukuna uyumlu olursunuz.',
        items: [
          'Yasal asgari ücrete göre otomatik maaş hesaplama',
          'Fazla mesai takibi ve limit izleme',
          'Yıllık izin ve hastalık izni yönetimi',
          'MPIN ve bordro oluşturma',
          'Yasal son tarihler için hatırlatmalar',
          'Denetim kontrolleri için hazır kayıtlar',
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'presmetka-na-plata-mk', title: "Makedonya'da maaş hesaplama: Primler ve vergiler" },
      { slug: 'mpin-obrazec', title: 'MPIN formu: Aylık hesaplama rehberi' },
      { slug: 'personalen-danok-na-dohod', title: "Makedonya'da kişisel gelir vergisi" },
    ],
    cta: {
      title: 'İş Hukukuyla Uyumlu Kalın',
      desc: 'Facturino, çalışanlara yönelik tüm yasal yükümlülükleri otomatik olarak yerine getirmenize yardımcı olur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function TrudovoPravoOsnoviPage({
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
