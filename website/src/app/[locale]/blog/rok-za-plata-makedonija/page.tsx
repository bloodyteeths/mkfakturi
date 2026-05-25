import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/rok-za-plata-makedonija', {
    title: {
      mk: 'Рок за исплата на плата во Македонија: Што вели законот (2026)',
      en: 'Salary Payment Deadline in North Macedonia: What the Law Says (2026)',
      sq: 'Afati i pagesës së pagës në Maqedoni: Çfarë thotë ligji (2026)',
      tr: 'Kuzey Makedonya\'da Maaş Ödeme Süresi: Kanun Ne Diyor (2026)',
    },
    description: {
      mk: 'Законски рок за исплата на плата во Македонија: чл. 106 ЗРО, до 15-ти наредниот месец. Прекувремена 40%, ноќна 35%, празници 50%. Минимална плата 38.507 МКД. Казни EUR 1.000-3.000.',
      en: 'Legal salary payment deadline in North Macedonia: Art. 106 Labor Law, by the 15th of the following month. Overtime 40%, night shift 35%, holidays 50%. Minimum wage 38,507 MKD. Penalties EUR 1,000-3,000.',
      sq: 'Afati ligjor i pagesës së pagës në Maqedoni: neni 106 i Ligjit të Punës, deri më 15 të muajit pasardhës. Jashtë orarit 40%, nata 35%, festat 50%. Paga minimale 38.507 MKD. Gjobat EUR 1.000-3.000.',
      tr: 'Kuzey Makedonya\'da yasal maaş ödeme süresi: md. 106 İş Kanunu, takip eden ayın 15\'ine kadar. Fazla mesai %40, gece vardiyası %35, tatiller %50. Asgari ücret 38.507 MKD. Cezalar EUR 1.000-3.000.',
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
    title: 'Рок за исплата на плата: Што вели Законот за работни односи',
    publishDate: '23 мај 2026',
    readTime: '7 мин читање',
    intro:
      'Исплатата на плата во законски определениот рок е една од основните обврски на секој работодавач во Македонија. Законот за работни односи (ЗРО) јасно го дефинира рокот, додатоците за прекувремена и ноќна работа, и казните за задоцнето плаќање. Во овој водич детално ги објаснуваме сите законски одредби поврзани со рокот за исплата, правата на вработените и чекорите за заштита.',
    sections: [
      {
        title: 'Кога мора да се исплати платата?',
        content:
          'Според член 106 од Законот за работни односи, работодавачот е должен да ја исплати платата најдоцна до 15-ти во наредниот месец за претходниот месец. Овој рок е задолжителен и не може да се продолжи со интерни акти или договор за вработување.',
        items: [
          'Рок за исплата: до 15-ти во наредниот месец (чл. 106 ЗРО)',
          'Платата мора да се исплати на трансакциска сметка на вработениот',
          'Исплата во готовина е забранета — секоја исплата мора да биде преку банка',
          'Работодавачот е должен да достави платна листа (пејслип) со детален преглед на бруто, придонеси, данок и нето плата',
          'Придонесите и данокот мора да се уплатат пред или истовремено со нето платата',
          'Рокот важи и за дополнителни примања: надоместок за годишен одмор, боледување, прекувремена работа',
        ],
        steps: null,
      },
      {
        title: 'Додатоци на плата: прекувремена, ноќна, празнична работа',
        content:
          'Законот за работни односи предвидува задолжителни додатоци на платата за работа под посебни услови. Работодавачот е должен да ги пресмета и исплати заедно со основната плата.',
        items: null,
        steps: [
          {
            step: 'Прекувремена работа — додаток од 40%',
            desc: 'Според чл. 106 ЗРО, за секој час прекувремена работа вработениот има право на додаток од 40% на часовната стапка. Прекувремената работа не смее да надмине 8 часа неделно и 190 часа годишно.',
          },
          {
            step: 'Ноќна работа — додаток од 35%',
            desc: 'Ноќната работа (од 22:00 до 06:00) носи задолжителен додаток од 35%. Работодавачот мора да обезбеди посебна заштита за работници кои работат ноќна смена повеќе од 3 часа.',
          },
          {
            step: 'Работа на празници — додаток од 50%',
            desc: 'За работа на државни и верски празници утврдени со закон, вработениот има право на додаток од 50% на часовната стапка, плус основната плата за тој ден.',
          },
          {
            step: 'Работа во недела — додаток од 50%',
            desc: 'Работата во недела се смета за работа во ден на неделен одмор и носи додаток од 50%. Работодавачот е должен да обезбеди замена за денот на неделен одмор во текот на наредната недела.',
          },
          {
            step: 'Работен стаж — додаток од 0,5% по година',
            desc: 'За секоја година работен стаж, вработениот има право на додаток од 0,5% на основната плата. Додатокот за стаж е задолжителен и се пресметува кумулативно.',
          },
        ],
      },
      {
        title: 'Минимална плата за 2026 година',
        content:
          'Минималната бруто плата во Македонија за 2026 година изнесува 38.507 МКД месечно за полно работно време (40 часа неделно). Секој работодавач е должен да исплати најмалку минимална плата — исплата под овој износ е прекршок.',
        items: [
          'Минимална бруто плата 2026: 38.507 МКД месечно',
          'Минимална нето плата: приближно 26.046 МКД (по одбиток на придонеси 28% и данок 10%)',
          'Минималната плата се усогласува секоја година со раст на просечната плата и животните трошоци',
          'Важи за сите дејности — нема посебна минимална плата по сектор',
          'За скратено работно време, минималната плата се пресметува пропорционално',
          'Казна за исплата под минималната плата: EUR 1.000-2.000 за правно лице',
        ],
        steps: null,
      },
      {
        title: 'Што ако работодавачот задоцни со исплата?',
        content:
          'Доколку работодавачот не ја исплати платата во законскиот рок (до 15-ти наредниот месец), вработениот има право на законска камата за секој ден задоцнување и може да поднесе пријава до Државниот инспекторат за труд.',
        items: null,
        steps: [
          {
            step: 'Законска камата за задоцнување',
            desc: 'Од денот на доспевање (16-ти во месецот) до денот на исплата, работодавачот должи законска затезна камата. Стапката е референтна на НБРСМ + 8 процентни поени, односно приближно 10-11% годишно.',
          },
          {
            step: 'Казни за работодавачот',
            desc: 'Согласно чл. 265 ЗРО, за неисплата на плата во рок се предвидени глоби: EUR 1.000-3.000 за правно лице, EUR 500-1.000 за одговорно лице во правното лице. За повторен прекршок, глобите се зголемуваат.',
          },
          {
            step: 'Раскинување на договор по вина на работодавач',
            desc: 'Според чл. 100 ЗРО, доколку работодавачот не исплати плата два последователни месеци, вработениот може да го раскине договорот за вработување и да бара отштета во висина на изгубените плати.',
          },
          {
            step: 'Право на штрајк',
            desc: 'Масовно неисплаќање на плати може да биде основ за организирање штрајк согласно Законот за штрајк, доколку претходно се исцрпени можностите за мирно решавање.',
          },
        ],
      },
      {
        title: 'Како да пријавите неисплатена плата',
        content:
          'Доколку работодавачот не ја исплати платата, вработениот може да поднесе пријава до Државниот инспекторат за труд. Постапката е бесплатна и може да се поднесе електронски или лично.',
        items: null,
        steps: [
          {
            step: 'Соберете документација',
            desc: 'Подгответе копија од договорот за вработување, последните платни листи, извод од банка кој покажува дека платата не е примена и евентуална писмена комуникација со работодавачот.',
          },
          {
            step: 'Поднесете пријава до Инспекторатот за труд',
            desc: 'Пријавата може да се поднесе електронски на dit.gov.mk, лично во регионалната канцеларија или по пошта. Наведете го работодавачот, периодот на неисплата и приложете ги документите.',
          },
          {
            step: 'Инспекциски надзор',
            desc: 'Инспекторатот е должен да постапи по пријавата во рок од 15 дена. Инспекторот врши увид кај работодавачот, проверува документација и може да издаде решение за отстранување на неправилностите.',
          },
          {
            step: 'Принудна наплата',
            desc: 'Доколку работодавачот не го почитува решението, инспекторатот може да поднесе барање за принудна наплата и прекршочна пријава до надлежен суд. Вработениот може и самостојно да поднесе тужба пред основен суд.',
          },
          {
            step: 'Алтернатива: Медијација и суд',
            desc: 'Пред поднесување тужба, препорачливо е обид за медијација преку лиценциран медијатор. Медијацијата е побрза и поефтина. Доколку не успее, вработениот има право на тужба за неисплатена плата со рок на застареност од 3 години.',
          },
        ],
      },
      {
        title: 'Како Facturino помага со навремена исплата',
        content:
          'Facturino го автоматизира целиот процес на пресметка и исплата на плати, со вградено следење на законски рокови и автоматски потсетници. Системот е усогласен со македонските прописи и ги елиминира најчестите причини за задоцнета исплата.',
        items: [
          'Автоматски потсетник за рок на исплата (15-ти во месецот)',
          'Пресметка на сите додатоци: прекувремена (40%), ноќна (35%), празници (50%), стаж (0,5%/год)',
          'Генерирање на МПИН образец спремен за поднесување до УЈП',
          'Автоматска пресметка на придонеси по актуелни стапки (пензиско 18,8%, здравствено 7,5%, вработување 1,2%)',
          'Платни листи на македонски јазик по МПИН стандард',
          'Евиденција на работни часови, прекувремена работа и отсуства',
          'Извештаи за вкупни трошоци за плати по месец и вработен',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата: Придонеси и даноци' },
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
      { slug: 'trudovo-pravo-osnovi', title: 'Трудово право: 10 работи за секој работодавач' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Неисплатена плата: Како да пријавите до Инспекторат' },
      { slug: 'kazni-ujp-2026', title: 'Казни од УЈП: Што ве чека ако задоцните' },
    ],
    cta: {
      title: 'Никогаш не задоцнете со плати',
      desc: 'Facturino автоматски ги следи роковите, ги пресметува придонесите и генерира МПИН — без грешки, во рок.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Salary Payment Deadline: What the Labor Law Says',
    publishDate: 'May 23, 2026',
    readTime: '7 min read',
    intro:
      'Paying salaries within the legally prescribed deadline is one of the fundamental obligations of every employer in North Macedonia. The Law on Labor Relations (LLR) clearly defines the deadline, premiums for overtime and night work, and penalties for late payment. In this guide, we explain all legal provisions related to salary payment deadlines, employee rights, and protection steps.',
    sections: [
      {
        title: 'When must salary be paid?',
        content:
          'According to Article 106 of the Law on Labor Relations, the employer is obligated to pay the salary no later than the 15th of the following month for the previous month. This deadline is mandatory and cannot be extended by internal acts or employment contracts.',
        items: [
          'Payment deadline: by the 15th of the following month (Art. 106 LLR)',
          'Salary must be paid to the employee\'s bank account',
          'Cash payments are prohibited — every payment must go through a bank',
          'The employer must provide a pay slip with a detailed breakdown of gross, contributions, tax, and net salary',
          'Contributions and tax must be remitted before or simultaneously with the net salary',
          'The deadline also applies to supplementary payments: vacation pay, sick leave, overtime compensation',
        ],
        steps: null,
      },
      {
        title: 'Salary premiums: overtime, night shifts, holidays',
        content:
          'The Law on Labor Relations mandates compulsory salary premiums for work under special conditions. The employer must calculate and pay these together with the base salary.',
        items: null,
        steps: [
          {
            step: 'Overtime — 40% premium',
            desc: 'Per Art. 106 LLR, for each hour of overtime the employee is entitled to a 40% premium on the hourly rate. Overtime may not exceed 8 hours per week and 190 hours per year.',
          },
          {
            step: 'Night work — 35% premium',
            desc: 'Night work (10:00 PM to 6:00 AM) carries a mandatory 35% premium. The employer must provide special protection for workers performing night shifts exceeding 3 hours.',
          },
          {
            step: 'Holiday work — 50% premium',
            desc: 'For work on state and religious holidays established by law, the employee is entitled to a 50% premium on the hourly rate, in addition to the base salary for that day.',
          },
          {
            step: 'Sunday work — 50% premium',
            desc: 'Sunday work is considered work on a weekly rest day and carries a 50% premium. The employer must provide a substitute rest day during the following week.',
          },
          {
            step: 'Seniority — 0.5% per year',
            desc: 'For each year of employment, the employee is entitled to a 0.5% premium on the base salary. The seniority premium is mandatory and calculated cumulatively.',
          },
        ],
      },
      {
        title: 'Minimum wage for 2026',
        content:
          'The minimum gross salary in North Macedonia for 2026 is 38,507 MKD per month for full-time employment (40 hours per week). Every employer is obligated to pay at least the minimum wage — payment below this amount constitutes a violation.',
        items: [
          'Minimum gross wage 2026: 38,507 MKD per month',
          'Minimum net wage: approximately 26,046 MKD (after 28% contributions and 10% tax deduction)',
          'The minimum wage is adjusted annually in line with average salary growth and cost of living',
          'Applies to all industries — there is no sector-specific minimum wage',
          'For part-time work, the minimum wage is calculated proportionally',
          'Penalty for paying below minimum wage: EUR 1,000-2,000 for legal entities',
        ],
        steps: null,
      },
      {
        title: 'What happens if the employer pays late?',
        content:
          'If the employer fails to pay the salary within the legal deadline (by the 15th of the following month), the employee is entitled to statutory interest for each day of delay and may file a complaint with the State Labor Inspectorate.',
        items: null,
        steps: [
          {
            step: 'Statutory default interest',
            desc: 'From the due date (16th of the month) until the day of payment, the employer owes statutory default interest. The rate is the NBRSM reference rate plus 8 percentage points, approximately 10-11% annually.',
          },
          {
            step: 'Employer penalties',
            desc: 'Under Art. 265 LLR, the following fines apply for failure to pay salary on time: EUR 1,000-3,000 for legal entities, EUR 500-1,000 for the responsible person within the legal entity. Repeat offenses carry higher fines.',
          },
          {
            step: 'Contract termination by employee',
            desc: 'Under Art. 100 LLR, if the employer fails to pay salary for two consecutive months, the employee may terminate the employment contract and claim compensation equal to the lost wages.',
          },
          {
            step: 'Right to strike',
            desc: 'Mass non-payment of wages may constitute grounds for organizing a strike under the Strike Law, provided that peaceful resolution options have been exhausted first.',
          },
        ],
      },
      {
        title: 'How to report unpaid wages',
        content:
          'If the employer fails to pay the salary, the employee may file a complaint with the State Labor Inspectorate. The procedure is free of charge and can be submitted electronically or in person.',
        items: null,
        steps: [
          {
            step: 'Gather documentation',
            desc: 'Prepare a copy of the employment contract, recent pay slips, a bank statement showing that salary was not received, and any written communication with the employer.',
          },
          {
            step: 'File a complaint with the Labor Inspectorate',
            desc: 'The complaint can be submitted electronically at dit.gov.mk, in person at the regional office, or by mail. Specify the employer, the period of non-payment, and attach supporting documents.',
          },
          {
            step: 'Inspection procedure',
            desc: 'The Inspectorate is obligated to act on the complaint within 15 days. The inspector conducts an on-site review at the employer, examines documentation, and may issue an order to remedy the violations.',
          },
          {
            step: 'Enforcement',
            desc: 'If the employer does not comply with the order, the Inspectorate may file for enforcement and submit a misdemeanor complaint to the competent court. The employee may also independently file a lawsuit before the basic court.',
          },
          {
            step: 'Alternative: mediation and court',
            desc: 'Before filing a lawsuit, it is advisable to attempt mediation through a licensed mediator. Mediation is faster and less expensive. If it fails, the employee has the right to sue for unpaid wages with a statute of limitations of 3 years.',
          },
        ],
      },
      {
        title: 'How Facturino helps with timely payment',
        content:
          'Facturino automates the entire payroll calculation and payment process, with built-in legal deadline tracking and automatic reminders. The system is compliant with Macedonian regulations and eliminates the most common causes of late payment.',
        items: [
          'Automatic payment deadline reminder (15th of the month)',
          'Calculation of all premiums: overtime (40%), night (35%), holidays (50%), seniority (0.5%/yr)',
          'MPIN form generation ready for UJP submission',
          'Automatic contribution calculation at current rates (pension 18.8%, health 7.5%, employment 1.2%)',
          'Pay slips in Macedonian per MPIN standard',
          'Tracking of work hours, overtime, and absences',
          'Reports for total payroll costs by month and employee',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Payroll Calculation: Contributions and Taxes' },
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
      { slug: 'trudovo-pravo-osnovi', title: 'Labor Law: 10 Things Every Employer Must Know' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Unpaid Wages: How to Report to Labor Inspectorate' },
      { slug: 'kazni-ujp-2026', title: 'UJP Penalties: What Happens If You File Late' },
    ],
    cta: {
      title: 'Never miss a payroll deadline',
      desc: 'Facturino automatically tracks deadlines, calculates contributions, and generates MPIN — error-free, on time.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Afati i pagesës së pagës: Çfarë thotë Ligji i Punës',
    publishDate: '23 maj 2026',
    readTime: '7 min lexim',
    intro:
      'Pagesa e pagës brenda afatit ligjor është një nga detyrimet themelore të çdo punëdhënësi në Maqedoni. Ligji i Marrëdhënieve të Punës (LMP) e përcakton qartë afatin, shtesat për punë jashtë orarit dhe natën, si dhe gjobat për pagesë me vonesë. Në këtë udhëzues shpjegojmë të gjitha dispozitat ligjore lidhur me afatin e pagesës, të drejtat e punonjësve dhe hapat për mbrojtje.',
    sections: [
      {
        title: 'Kur duhet paguar paga?',
        content:
          'Sipas nenit 106 të Ligjit të Marrëdhënieve të Punës, punëdhënësi është i detyruar të paguajë pagën jo më vonë se data 15 e muajit pasardhës për muajin paraprak. Ky afat është i detyrueshëm dhe nuk mund të zgjatet me akte të brendshme ose kontratë punësimi.',
        items: [
          'Afati i pagesës: deri më 15 të muajit pasardhës (neni 106 LMP)',
          'Paga duhet paguar në llogarinë bankare të punonjësit',
          'Pagesat me para në dorë janë të ndaluara — çdo pagesë duhet të bëhet përmes bankës',
          'Punëdhënësi duhet të dorëzojë fletëpagesë me ndarje të detajuar: bruto, kontribute, tatim dhe neto',
          'Kontributet dhe tatimi duhet paguar para ose njëkohësisht me pagën neto',
          'Afati vlen edhe për pagesa shtesë: kompensim pushimi, pushim mjekësor, punë jashtë orarit',
        ],
        steps: null,
      },
      {
        title: 'Shtesat e pagës: jashtë orarit, natën, festat',
        content:
          'Ligji i Marrëdhënieve të Punës parashikon shtesa të detyrueshme të pagës për punë nën kushte të veçanta. Punëdhënësi duhet t\'i llogarisë dhe paguajë bashkë me pagën bazë.',
        items: null,
        steps: [
          {
            step: 'Puna jashtë orarit — shtesë 40%',
            desc: 'Sipas nenit 106 LMP, për çdo orë pune jashtë orarit punonjësi ka të drejtë për shtesë 40% mbi normën orare. Puna jashtë orarit nuk mund të kalojë 8 orë në javë dhe 190 orë në vit.',
          },
          {
            step: 'Puna natën — shtesë 35%',
            desc: 'Puna natën (nga ora 22:00 deri 06:00) mbart shtesë të detyrueshme 35%. Punëdhënësi duhet të sigurojë mbrojtje të veçantë për punëtorët që punojnë natën mbi 3 orë.',
          },
          {
            step: 'Puna në festa — shtesë 50%',
            desc: 'Për punë në festat shtetërore dhe fetare të përcaktuara me ligj, punonjësi ka të drejtë për shtesë 50% mbi normën orare, përveç pagës bazë për atë ditë.',
          },
          {
            step: 'Puna të dielën — shtesë 50%',
            desc: 'Puna të dielën konsiderohet punë në ditën e pushimit javor dhe mbart shtesë 50%. Punëdhënësi duhet të sigurojë ditë zëvendësuese pushimi gjatë javës pasardhëse.',
          },
          {
            step: 'Stazhi — 0,5% për vit',
            desc: 'Për çdo vit stazhi pune, punonjësi ka të drejtë për shtesë 0,5% mbi pagën bazë. Shtesa e stazhit është e detyrueshme dhe llogaritet kumulativisht.',
          },
        ],
      },
      {
        title: 'Paga minimale për 2026',
        content:
          'Paga minimale bruto në Maqedoni për 2026 është 38.507 MKD në muaj për punësim me kohë të plotë (40 orë në javë). Çdo punëdhënës duhet të paguajë të paktën pagën minimale — pagesa nën këtë shumë është kundërvajtje.',
        items: [
          'Paga minimale bruto 2026: 38.507 MKD në muaj',
          'Paga minimale neto: përafërsisht 26.046 MKD (pas zbritjes së kontributeve 28% dhe tatimit 10%)',
          'Paga minimale përshtatet çdo vit sipas rritjes së pagës mesatare dhe kostos së jetesës',
          'Vlen për të gjitha industritë — nuk ka pagë minimale sektoriale',
          'Për punë me kohë të pjesshme, paga minimale llogaritet proporcionalisht',
          'Gjoba për pagesë nën pagën minimale: EUR 1.000-2.000 për persona juridikë',
        ],
        steps: null,
      },
      {
        title: 'Çfarë ndodh nëse punëdhënësi vonon pagesën?',
        content:
          'Nëse punëdhënësi nuk e paguan pagën brenda afatit ligjor (deri më 15 të muajit pasardhës), punonjësi ka të drejtë për kamatë ligjore për çdo ditë vonesë dhe mund të paraqesë ankesë tek Inspektorati Shtetëror i Punës.',
        items: null,
        steps: [
          {
            step: 'Kamata ligjore e vonesës',
            desc: 'Nga data e maturimit (16-ta e muajit) deri në ditën e pagesës, punëdhënësi i detyrohet kamatë ligjore. Norma është referenca e NBRSM + 8 pikë përqindjeje, përafërsisht 10-11% në vit.',
          },
          {
            step: 'Gjobat për punëdhënësin',
            desc: 'Sipas nenit 265 LMP, gjobat për mospagesë të pagës në afat janë: EUR 1.000-3.000 për persona juridikë, EUR 500-1.000 për personin përgjegjës brenda personit juridik. Shkeljet e përsëritura mbartin gjoba më të larta.',
          },
          {
            step: 'Ndërprerja e kontratës nga punonjësi',
            desc: 'Sipas nenit 100 LMP, nëse punëdhënësi nuk paguan pagën për dy muaj radhazi, punonjësi mund ta ndërpresë kontratën e punësimit dhe të kërkojë dëmshpërblim të barabartë me pagat e humbura.',
          },
          {
            step: 'E drejta e grevës',
            desc: 'Mospagesa masive e pagave mund të përbëjë bazë për organizimin e grevës sipas Ligjit të Grevës, me kusht që opsionet e zgjidhjes paqësore të jenë shteruar paraprakisht.',
          },
        ],
      },
      {
        title: 'Si të raportoni pagën e papaguar',
        content:
          'Nëse punëdhënësi nuk e paguan pagën, punonjësi mund të paraqesë ankesë tek Inspektorati Shtetëror i Punës. Procedura është pa pagesë dhe mund të dorëzohet elektronikisht ose personalisht.',
        items: null,
        steps: [
          {
            step: 'Mblidhni dokumentacionin',
            desc: 'Përgatitni kopje të kontratës së punësimit, fletëpagesat e fundit, ekstrakt bankar që tregon se paga nuk është pranuar, dhe komunikimin e mundshëm me shkrim me punëdhënësin.',
          },
          {
            step: 'Paraqitni ankesë tek Inspektorati i Punës',
            desc: 'Ankesa mund të dorëzohet elektronikisht në dit.gov.mk, personalisht në zyrën rajonale, ose me postë. Specifikoni punëdhënësin, periudhën e mospagesës dhe bashkëngjitni dokumentet.',
          },
          {
            step: 'Procedura e inspektimit',
            desc: 'Inspektorati duhet të veprojë mbi ankesën brenda 15 ditëve. Inspektori bën vizitë tek punëdhënësi, shqyrton dokumentacionin dhe mund të lëshojë vendim për eliminimin e shkeljeve.',
          },
          {
            step: 'Ekzekutimi i detyruar',
            desc: 'Nëse punëdhënësi nuk e respekton vendimin, Inspektorati mund të paraqesë kërkesë për ekzekutim të detyruar dhe ankesë kundërvajtëse tek gjykata kompetente. Punonjësi gjithashtu mund të ngrejë padi në mënyrë të pavarur.',
          },
          {
            step: 'Alternativë: ndërmjetësimi dhe gjykata',
            desc: 'Para ngritjes së padisë, rekomandohet të provohet ndërmjetësimi përmes ndërmjetësit të licencuar. Ndërmjetësimi është më i shpejtë dhe më i lirë. Nëse dështon, punonjësi ka të drejtë padie me afat parashkrimi 3 vjeçar.',
          },
        ],
      },
      {
        title: 'Si ndihmon Facturino me pagesën në kohë',
        content:
          'Facturino e automatizon procesin e plotë të llogaritjes dhe pagesës së pagave, me ndjekje të integruar të afateve ligjore dhe kujtime automatike. Sistemi është në pajtim me rregulloret maqedonase dhe eliminon shkaqet më të zakonshme të pagesës me vonesë.',
        items: [
          'Kujtim automatik për afatin e pagesës (data 15 e muajit)',
          'Llogaritje e të gjitha shtesave: jashtë orarit (40%), natën (35%), festat (50%), stazhi (0,5%/vit)',
          'Gjenerim i formularit MPIN gati për dorëzim në UJP',
          'Llogaritje automatike e kontributeve me normat aktuale (pension 18,8%, shëndetësi 7,5%, punësim 1,2%)',
          'Fletëpagesa në maqedonisht sipas standardit MPIN',
          'Ndjekje e orëve të punës, punës jashtë orarit dhe mungesave',
          'Raporte për kostot totale të pagave sipas muajit dhe punonjësit',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës: Kontributet dhe tatimet' },
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhëzues për llogaritjen mujore' },
      { slug: 'trudovo-pravo-osnovi', title: 'E drejta e punës: 10 gjëra për çdo punëdhënës' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Paga e papaguar: Si të raportoni tek Inspektorati' },
      { slug: 'kazni-ujp-2026', title: 'Gjobat e DAP: Çfarë ndodh nëse vononi' },
    ],
    cta: {
      title: 'Mos vononi kurrë me pagat',
      desc: 'Facturino ndjek automatikisht afatet, llogarit kontributet dhe gjeneron MPIN — pa gabime, në kohë.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Maaş ödeme süresi: İş Kanunu ne diyor',
    publishDate: '23 Mayıs 2026',
    readTime: '7 dk okuma',
    intro:
      'Maaşları yasal sürede ödemek, Kuzey Makedonya\'daki her işverenin temel yükümlülüklerinden biridir. İş İlişkileri Kanunu (İİK) süreyi, fazla mesai ve gece çalışma primleri ile geç ödeme cezalarını açıkça tanımlar. Bu rehberde maaş ödeme süreleri, çalışan hakları ve koruma adımlarıyla ilgili tüm yasal hükümleri açıklıyoruz.',
    sections: [
      {
        title: 'Maaş ne zaman ödenmeli?',
        content:
          'İş İlişkileri Kanunu\'nun 106. maddesine göre, işveren maaşı önceki ay için takip eden ayın en geç 15\'ine kadar ödemekle yükümlüdür. Bu süre zorunludur ve iç düzenlemeler veya iş sözleşmesiyle uzatılamaz.',
        items: [
          'Ödeme süresi: takip eden ayın 15\'ine kadar (md. 106 İİK)',
          'Maaş çalışanın banka hesabına ödenmelidir',
          'Nakit ödeme yasaktır — her ödeme banka aracılığıyla yapılmalıdır',
          'İşveren, brüt, katkılar, vergi ve net maaşın ayrıntılı dökümünü içeren bordro belgesi vermek zorundadır',
          'Katkılar ve vergi, net maaştan önce veya eş zamanlı ödenmelidir',
          'Süre, ek ödemeler için de geçerlidir: izin ücreti, hastalık izni, fazla mesai tazminatı',
        ],
        steps: null,
      },
      {
        title: 'Maaş primleri: fazla mesai, gece vardiyası, tatiller',
        content:
          'İş İlişkileri Kanunu, özel koşullarda çalışma için zorunlu maaş primleri öngörür. İşveren bunları temel maaşla birlikte hesaplamak ve ödemek zorundadır.',
        items: null,
        steps: [
          {
            step: 'Fazla mesai — %40 prim',
            desc: 'İİK md. 106\'ya göre, her fazla mesai saati için çalışan, saat ücretinin %40\'ı oranında prime hak kazanır. Fazla mesai haftada 8 saati ve yılda 190 saati aşamaz.',
          },
          {
            step: 'Gece çalışması — %35 prim',
            desc: 'Gece çalışması (22:00-06:00) zorunlu %35 prim taşır. İşveren, 3 saatten fazla gece vardiyası yapan işçilere özel koruma sağlamalıdır.',
          },
          {
            step: 'Tatil çalışması — %50 prim',
            desc: 'Kanunla belirlenmiş resmi ve dini tatillerde çalışma için çalışan, o günkü temel maaşa ek olarak saat ücretinin %50\'si oranında prime hak kazanır.',
          },
          {
            step: 'Pazar çalışması — %50 prim',
            desc: 'Pazar günü çalışma, haftalık dinlenme gününde çalışma olarak kabul edilir ve %50 prim taşır. İşveren, sonraki hafta içinde ikame dinlenme günü sağlamalıdır.',
          },
          {
            step: 'Kıdem — yılda %0,5',
            desc: 'Her kıdem yılı için çalışan, temel maaşın %0,5\'i oranında prime hak kazanır. Kıdem primi zorunludur ve kümülatif olarak hesaplanır.',
          },
        ],
      },
      {
        title: '2026 asgari ücret',
        content:
          'Kuzey Makedonya\'da 2026 yılı için brüt asgari ücret, tam zamanlı çalışma (haftada 40 saat) için aylık 38.507 MKD\'dir. Her işveren en az asgari ücreti ödemek zorundadır — bu tutarın altında ödeme bir ihlaldir.',
        items: [
          '2026 brüt asgari ücret: aylık 38.507 MKD',
          'Net asgari ücret: yaklaşık 26.046 MKD (%28 katkı ve %10 vergi kesintisi sonrası)',
          'Asgari ücret, ortalama maaş artışı ve yaşam maliyetine göre yıllık olarak ayarlanır',
          'Tüm sektörler için geçerlidir — sektöre özel asgari ücret yoktur',
          'Yarı zamanlı çalışma için asgari ücret orantılı olarak hesaplanır',
          'Asgari ücretin altında ödeme cezası: tüzel kişiler için EUR 1.000-2.000',
        ],
        steps: null,
      },
      {
        title: 'İşveren geç öderse ne olur?',
        content:
          'İşveren maaşı yasal sürede (takip eden ayın 15\'ine kadar) ödemezse, çalışan her gecikme günü için yasal faize hak kazanır ve Devlet Çalışma Müfettişliğine şikayet başvurusunda bulunabilir.',
        items: null,
        steps: [
          {
            step: 'Yasal temerrüt faizi',
            desc: 'Vade tarihinden (ayın 16\'sı) ödeme gününe kadar işveren yasal temerrüt faizi borçlanır. Oran, NBRSM referans oranı artı 8 yüzde puanı, yaklaşık yıllık %10-11\'dir.',
          },
          {
            step: 'İşveren cezaları',
            desc: 'İİK md. 265\'e göre, maaşın zamanında ödenmemesi için şu cezalar uygulanır: tüzel kişiler için EUR 1.000-3.000, tüzel kişi içindeki sorumlu kişi için EUR 500-1.000. Tekrar eden ihlaller daha yüksek ceza gerektirir.',
          },
          {
            step: 'Çalışan tarafından sözleşme feshi',
            desc: 'İİK md. 100\'e göre, işveren art arda iki ay maaş ödemezse, çalışan iş sözleşmesini feshedebilir ve kaybedilen ücretler tutarında tazminat talep edebilir.',
          },
          {
            step: 'Grev hakkı',
            desc: 'Toplu maaş ödenmemesi, Grev Kanunu kapsamında grev düzenleme gerekçesi oluşturabilir, ancak barışçıl çözüm seçeneklerinin önceden tüketilmiş olması gerekir.',
          },
        ],
      },
      {
        title: 'Ödenmemiş maaş nasıl bildirilir',
        content:
          'İşveren maaşı ödemezse, çalışan Devlet Çalışma Müfettişliğine şikayet başvurusunda bulunabilir. Prosedür ücretsizdir ve elektronik olarak veya şahsen yapılabilir.',
        items: null,
        steps: [
          {
            step: 'Belgeleri toplayın',
            desc: 'İş sözleşmesinin bir kopyasını, son bordro belgelerini, maaşın alınmadığını gösteren banka ekstresi ve işverenle yapılan yazılı iletişimi hazırlayın.',
          },
          {
            step: 'Çalışma Müfettişliğine şikayet edin',
            desc: 'Şikayet, dit.gov.mk üzerinden elektronik olarak, bölge ofisine şahsen veya postayla yapılabilir. İşvereni, ödeme yapılmayan dönemi belirtin ve destekleyici belgeleri ekleyin.',
          },
          {
            step: 'Denetim prosedürü',
            desc: 'Müfettişlik, şikayet üzerine 15 gün içinde harekete geçmekle yükümlüdür. Müfettiş, işverende yerinde inceleme yapar, belgeleri inceler ve ihlallerin giderilmesi için emir verebilir.',
          },
          {
            step: 'İcra takibi',
            desc: 'İşveren emre uymazsa, Müfettişlik icra başvurusu yapabilir ve yetkili mahkemeye kabahat şikayeti sunabilir. Çalışan da bağımsız olarak temel mahkemede dava açabilir.',
          },
          {
            step: 'Alternatif: arabuluculuk ve mahkeme',
            desc: 'Dava açmadan önce, lisanslı arabulucu aracılığıyla arabuluculuk denenmesi tavsiye edilir. Arabuluculuk daha hızlı ve ucuzdur. Başarısız olursa, çalışanın 3 yıllık zamanaşımı süresinde ödenmemiş maaş davası açma hakkı vardır.',
          },
        ],
      },
      {
        title: 'Facturino zamanında ödemeye nasıl yardımcı olur',
        content:
          'Facturino, yerleşik yasal süre takibi ve otomatik hatırlatmalarla tüm bordro hesaplama ve ödeme sürecini otomatikleştirir. Sistem, Makedon düzenlemelerine uygundur ve geç ödemenin en yaygın nedenlerini ortadan kaldırır.',
        items: [
          'Otomatik ödeme süresi hatırlatması (ayın 15\'i)',
          'Tüm primlerin hesaplanması: fazla mesai (%40), gece (%35), tatiller (%50), kıdem (%0,5/yıl)',
          'UJP\'ye gönderime hazır MPIN formu oluşturma',
          'Güncel oranlarla otomatik katkı hesaplaması (emeklilik %18,8, sağlık %7,5, istihdam %1,2)',
          'MPIN standardına göre Makedonca bordro belgeleri',
          'Çalışma saatleri, fazla mesai ve devamsızlık takibi',
          'Aya ve çalışana göre toplam bordro maliyeti raporları',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Bordro Hesaplama: Katkılar ve Vergiler' },
      { slug: 'mpin-obrazec', title: 'MPIN Formu: Aylık Hesaplama Rehberi' },
      { slug: 'trudovo-pravo-osnovi', title: 'İş Hukuku: Her İşverenin Bilmesi Gereken 10 Şey' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Ödenmemiş Maaş: İş Müfettişliğine Nasıl Şikayet Edilir' },
      { slug: 'kazni-ujp-2026', title: 'UJP Cezaları: Geç Beyan Ederseniz Ne Olur' },
    ],
    cta: {
      title: 'Bordro süresini asla kaçırmayın',
      desc: 'Facturino süreleri otomatik takip eder, katkıları hesaplar ve MPIN oluşturur — hatasız, zamanında.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function RokZaPlataMakedonijaPage({
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
    slug: 'rok-za-plata-makedonija',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['salary payment deadline', 'north macedonia labour law', 'рок за плата', 'рок исплата плата', 'overtime premium', 'minimum wage', 'labor inspectorate'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/rok-za-plata-makedonija` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кога мора да се исплати платата во Македонија?', answer: 'Според член 106 од Законот за работни односи, платата мора да се исплати најдоцна до 15-ти во наредниот месец за претходниот месец.' },
        { question: 'Колку е минималната плата во Македонија 2026?', answer: 'Минималната бруто плата во 2026 изнесува 38.507 МКД месечно (приближно 626 EUR).' },
        { question: 'Колку е додатокот за прекувремена работа?', answer: 'Прекувремената работа се плаќа со 40% додаток на основната плата, ноќна работа со 35% додаток.' },
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
