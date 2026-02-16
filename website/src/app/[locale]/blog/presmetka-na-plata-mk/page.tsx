import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/presmetka-na-plata-mk', {
    title: {
      mk: 'Пресметка на плата во Македонија: Придонеси и даноци — Facturino',
      en: 'Payroll Calculation in Macedonia: Contributions and Taxes — Facturino',
      sq: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet — Facturino',
      tr: 'Makedonya\'da bordro hesaplama: Katkılar ve vergiler — Facturino',
    },
    description: {
      mk: 'Целосен водич за пресметка на плата во Македонија — пензиски придонес (18.8%), здравствено (7.5%), вработување (1.2%), данок на доход (10%), МПИН и минимална плата.',
      en: 'Complete guide to payroll calculation in Macedonia — pension (18.8%), health insurance (7.5%), employment fund (1.2%), income tax (10%), MPIN filing, and minimum wage.',
      sq: 'Udhëzues i plotë për llogaritjen e pagës në Maqedoni — pension (18.8%), sigurim shëndetësor (7.5%), fondi i punësimit (1.2%), tatimi mbi të ardhurat (10%) dhe paga minimale.',
      tr: 'Makedonya\'da bordro hesaplama rehberi — emeklilik (%18,8), sağlık sigortası (%7,5), istihdam fonu (%1,2), gelir vergisi (%10) ve asgari ücret.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Водич',
    title: 'Пресметка на плата во Македонија: Придонеси и даноци',
    publishDate: '16 февруари 2026',
    readTime: '8 мин читање',
    intro:
      'Пресметката на плата во Македонија вклучува неколку задолжителни придонеси и даноци кои работодавачот мора правилно да ги пресмета и уплати. Во овој водич детално ги објаснуваме сите компоненти на платата — од бруто до нето, вклучувајќи ги стапките за 2026 година, минималната плата и обврските за поднесување на МПИН.',
    sections: [
      {
        title: 'Структура на платата: Бруто до нето',
        content:
          'Платата во Македонија се пресметува од бруто износ (вкупната цена на работодавачот) до нето износ (она што вработениот го добива на сметка). Разликата се состои од задолжителни социјални придонеси и данок на личен доход. Работодавачот е должен да ги пресмета, задржи и уплати сите придонеси и даноци пред исплата на нето платата.',
        items: null,
        steps: null,
      },
      {
        title: 'Задолжителни придонеси (стапки за 2026)',
        content:
          'Социјалните придонеси во Македонија се пресметуваат на бруто платата и ги плаќа вработениот (се задржуваат од бруто платата). Работодавачот е должен да ги пресмета и уплати.',
        items: null,
        steps: [
          {
            step: 'Пензиско и инвалидско осигурување — 18,8%',
            desc: 'Најголемиот придонес, наменет за пензискиот фонд (ПИОМ). Од овие 18,8%, дел оди во задолжителен пензиски фонд од вториот столб (6% за лица родени после 1967). Основицата е бруто платата, со минимална основица од 50% и максимална од 16 просечни плати.',
          },
          {
            step: 'Здравствено осигурување — 7,5%',
            desc: 'Придонес за Фондот за здравствено осигурување (ФЗО). Обезбедува здравствена заштита за вработениот и членовите на семејството. Се пресметува на бруто платата без горна граница.',
          },
          {
            step: 'Придонес за вработување — 1,2%',
            desc: 'Придонес за Агенцијата за вработување. Обезбедува право на надоместок за невработеност. Се пресметува на бруто платата, исто како и здравственото осигурување.',
          },
          {
            step: 'Данок на личен доход — 10%',
            desc: 'Данокот на личен доход се пресметува на основицата која е бруто платата минус социјалните придонеси (18,8% + 7,5% + 1,2% = 27,5%). Стапката е рамна — 10% за сите нивоа на доход. Се уплатува до УЈП месечно.',
          },
        ],
      },
      {
        title: 'Пример: Пресметка на плата',
        content:
          'Да претпоставиме дека бруто платата е 40.000 МКД. Еве како се пресметува нето платата:',
        items: [
          'Бруто плата: 40.000 МКД',
          'Пензиско осигурување (18,8%): 40.000 x 0,188 = 7.520 МКД',
          'Здравствено осигурување (7,5%): 40.000 x 0,075 = 3.000 МКД',
          'Придонес за вработување (1,2%): 40.000 x 0,012 = 480 МКД',
          'Вкупно придонеси (27,5%): 11.000 МКД',
          'Даночна основица: 40.000 - 11.000 = 29.000 МКД',
          'Данок на доход (10%): 29.000 x 0,10 = 2.900 МКД',
          'Нето плата: 40.000 - 11.000 - 2.900 = 26.100 МКД',
        ],
        steps: null,
      },
      {
        title: 'Минимална плата и МПИН',
        content: null,
        items: [
          'Минимална нето плата за 2026: 18.000 МКД (приближно 292 EUR). Секој работодавач е должен да исплати најмалку минимална плата за полно работно време.',
          'МПИН (Месечна пресметка на индивидуални наоди) — електронски образец кој се поднесува до УЈП секој месец. Содржи податоци за секој вработен: бруто плата, придонеси, данок и нето плата.',
          'Рок за поднесување на МПИН: најдоцна до 15-ти во месецот за претходниот месец. Казните за задоцнето поднесување се од 500 до 2.000 EUR.',
          'Уплата на придонеси и данок: мора да се изврши пред или истовремено со исплатата на нето платата. Не смеете да исплатите плата без претходна уплата на придонесите.',
          'Придонесите се уплаќаат на единствена сметка при Управата за јавни приходи (УЈП), која потоа ги распределува на соодветните фондови.',
        ],
        steps: null,
      },
      {
        title: 'Обврски на работодавачот',
        content: null,
        items: null,
        steps: [
          {
            step: 'Пресметајте ја платата точно',
            desc: 'Секој месец пресметајте ги сите компоненти: бруто плата, придонеси, данок и нето плата. Водете сметка за прекувремена работа, боледувања, годишен одмор и други варијабли кои влијаат на износот.',
          },
          {
            step: 'Поднесете МПИН до УЈП',
            desc: 'Електронски поднесете го МПИН образецот преку e-Tax порталот (etax.ujp.gov.mk) најдоцна до 15-ти во месецот. Образецот содржи детални податоци за секој вработен поединечно.',
          },
          {
            step: 'Уплатете ги придонесите',
            desc: 'Уплатете ги социјалните придонеси и данокот на единствената сметка при УЈП. Уплатата мора да биде извршена пред исплатата на нето платата на вработените.',
          },
          {
            step: 'Исплатете ја нето платата',
            desc: 'По уплатата на придонесите и данокот, исплатете ја нето платата на трансакциската сметка на секој вработен. Чувајте потврди за сите уплати.',
          },
          {
            step: 'Издадете платна листа',
            desc: 'Секој вработен има право да добие месечна платна листа (пејслип) со детален преглед на сите компоненти на платата: бруто износ, одбитоци за придонеси, данок и нето плата.',
          },
        ],
      },
      {
        title: 'Како Facturino ја автоматизира пресметката на плати',
        content:
          'Facturino го поедноставува целиот процес на пресметка на плати за македонски бизниси. Системот автоматски ги применува актуелните стапки на придонеси и даноци, генерира МПИН образец спремен за поднесување до УЈП и креира платни листи за секој вработен во формат усогласен со македонските прописи.',
        items: [
          'Автоматска пресметка на бруто-до-нето со актуелни стапки за 2026',
          'Генерирање на МПИН образец спремен за УЈП',
          'Платни листи на македонски јазик по МПИН стандард',
          'Поддршка за прекувремена работа, боледувања и одмори',
          'Автоматско ажурирање при промена на стапките или минималната плата',
          'Извештаи за вкупни трошоци за плати по месец, оддел и вработен',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
      { slug: 'trudovo-pravo-osnovi', title: 'Трудово право: 10 работи што секој работодавач мора да ги знае' },
      { slug: 'personalen-danok-na-dohod', title: 'Персонален данок на доход во Македонија' },
    ],
    cta: {
      title: 'Автоматизирајте ги платите со Facturino',
      desc: 'Точна пресметка, МПИН образец и платни листи — автоматски, без грешки, усогласено со македонските прописи.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Guide',
    title: 'Payroll Calculation in Macedonia: Contributions and Taxes',
    publishDate: 'February 16, 2026',
    readTime: '8 min read',
    intro:
      'Payroll calculation in Macedonia involves several mandatory contributions and taxes that employers must correctly calculate and remit. In this guide, we explain all salary components in detail — from gross to net, including 2026 rates, minimum wage, and MPIN filing obligations.',
    sections: [
      {
        title: 'Salary structure: Gross to net',
        content:
          'Salaries in Macedonia are calculated from the gross amount (total employer cost) to the net amount (what the employee receives). The difference consists of mandatory social contributions and personal income tax. The employer is obligated to calculate, withhold, and remit all contributions and taxes before paying the net salary.',
        items: null,
        steps: null,
      },
      {
        title: 'Mandatory contributions (2026 rates)',
        content:
          'Social contributions in Macedonia are calculated on the gross salary and are paid by the employee (withheld from the gross salary). The employer is responsible for calculating and remitting them.',
        items: null,
        steps: [
          {
            step: 'Pension and disability insurance — 18.8%',
            desc: 'The largest contribution, directed to the pension fund (PIOM). Of this 18.8%, a portion goes to the mandatory second-pillar pension fund (6% for persons born after 1967). The base is the gross salary, with a minimum base of 50% and maximum of 16 average salaries.',
          },
          {
            step: 'Health insurance — 7.5%',
            desc: 'Contribution to the Health Insurance Fund (FZO). Provides healthcare coverage for the employee and family members. Calculated on the gross salary with no upper limit.',
          },
          {
            step: 'Employment contribution — 1.2%',
            desc: 'Contribution to the Employment Agency. Provides the right to unemployment benefits. Calculated on the gross salary, same as health insurance.',
          },
          {
            step: 'Personal income tax — 10%',
            desc: 'Personal income tax is calculated on the base which is the gross salary minus social contributions (18.8% + 7.5% + 1.2% = 27.5%). The rate is flat — 10% for all income levels. Remitted to UJP monthly.',
          },
        ],
      },
      {
        title: 'Example: Salary calculation',
        content:
          'Let us assume the gross salary is 40,000 MKD. Here is how the net salary is calculated:',
        items: [
          'Gross salary: 40,000 MKD',
          'Pension insurance (18.8%): 40,000 x 0.188 = 7,520 MKD',
          'Health insurance (7.5%): 40,000 x 0.075 = 3,000 MKD',
          'Employment contribution (1.2%): 40,000 x 0.012 = 480 MKD',
          'Total contributions (27.5%): 11,000 MKD',
          'Tax base: 40,000 - 11,000 = 29,000 MKD',
          'Income tax (10%): 29,000 x 0.10 = 2,900 MKD',
          'Net salary: 40,000 - 11,000 - 2,900 = 26,100 MKD',
        ],
        steps: null,
      },
      {
        title: 'Minimum wage and MPIN',
        content: null,
        items: [
          'Minimum net wage for 2026: 18,000 MKD (approximately 292 EUR). Every employer must pay at least the minimum wage for full-time employment.',
          'MPIN (Monthly Calculation of Individual Findings) — an electronic form submitted to UJP every month. Contains data for each employee: gross salary, contributions, tax, and net salary.',
          'MPIN filing deadline: no later than the 15th of the month for the previous month. Late filing penalties range from 500 to 2,000 EUR.',
          'Contribution and tax payment: must be made before or simultaneously with the net salary payment. You may not pay salaries without prior contribution payment.',
          'Contributions are paid to a single account at the Public Revenue Office (UJP), which then distributes them to the respective funds.',
        ],
        steps: null,
      },
      {
        title: 'Employer obligations',
        content: null,
        items: null,
        steps: [
          {
            step: 'Calculate salary accurately',
            desc: 'Each month, calculate all components: gross salary, contributions, tax, and net salary. Account for overtime, sick leave, annual leave, and other variables that affect the amount.',
          },
          {
            step: 'Submit MPIN to UJP',
            desc: 'Electronically submit the MPIN form via the e-Tax portal (etax.ujp.gov.mk) no later than the 15th of the month. The form contains detailed data for each employee individually.',
          },
          {
            step: 'Remit contributions',
            desc: 'Pay social contributions and tax to the single account at UJP. Payment must be completed before paying net salaries to employees.',
          },
          {
            step: 'Pay net salary',
            desc: 'After remitting contributions and tax, pay the net salary to each employee\'s bank account. Keep receipts for all payments.',
          },
          {
            step: 'Issue pay slip',
            desc: 'Each employee has the right to receive a monthly pay slip with a detailed breakdown of all salary components: gross amount, contribution deductions, tax, and net salary.',
          },
        ],
      },
      {
        title: 'How Facturino automates payroll',
        content:
          'Facturino simplifies the entire payroll process for Macedonian businesses. The system automatically applies current contribution and tax rates, generates MPIN forms ready for UJP submission, and creates pay slips for each employee in a format compliant with Macedonian regulations.',
        items: [
          'Automatic gross-to-net calculation with current 2026 rates',
          'MPIN form generation ready for UJP',
          'Pay slips in Macedonian per MPIN standard',
          'Support for overtime, sick leave, and holidays',
          'Automatic updates when rates or minimum wage change',
          'Reports for total payroll costs by month, department, and employee',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
      { slug: 'trudovo-pravo-osnovi', title: 'Labor Law: 10 Things Every Employer Must Know' },
      { slug: 'personalen-danok-na-dohod', title: 'Personal Income Tax in Macedonia' },
    ],
    cta: {
      title: 'Automate payroll with Facturino',
      desc: 'Accurate calculations, MPIN forms, and pay slips — automatic, error-free, compliant with Macedonian regulations.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet',
    publishDate: '16 shkurt 2026',
    readTime: '8 min lexim',
    intro:
      'Llogaritja e pagës në Maqedoni përfshin disa kontribute dhe tatime të detyrueshme që punëdhënësi duhet t\'i llogarisë dhe dërgojë saktë. Në këtë udhëzues shpjegojmë në detaje të gjitha komponentet e pagës — nga bruto në neto, duke përfshirë normat për 2026, pagën minimale dhe detyrimet për dorëzimin e MPIN.',
    sections: [
      {
        title: 'Struktura e pagës: Bruto në neto',
        content:
          'Pagat në Maqedoni llogariten nga shuma bruto (kosto totale e punëdhënësit) në shumën neto (atë që punonjësi merr). Diferenca përbëhet nga kontributet e detyrueshme sociale dhe tatimi mbi të ardhurat personale. Punëdhënësi është i detyruar t\'i llogarisë, mbajë dhe dërgojë të gjitha kontributet dhe tatimet para pagesës së pagës neto.',
        items: null,
        steps: null,
      },
      {
        title: 'Kontributet e detyrueshme (normat 2026)',
        content:
          'Kontributet sociale në Maqedoni llogariten mbi pagën bruto dhe paguhen nga punonjësi (mbahen nga paga bruto). Punëdhënësi është përgjegjës për llogaritjen dhe dërgimin e tyre.',
        items: null,
        steps: [
          {
            step: 'Sigurimi pensional dhe invalidor — 18,8%',
            desc: 'Kontributi më i madh, i drejtuar fondit të pensioneve (PIOM). Nga këto 18,8%, një pjesë shkon në fondin e detyruar pensional të shtyllës së dytë (6% për personat e lindur pas 1967). Baza është paga bruto, me bazë minimale 50% dhe maksimale 16 paga mesatare.',
          },
          {
            step: 'Sigurimi shëndetësor — 7,5%',
            desc: 'Kontribut për Fondin e Sigurimit Shëndetësor (FZO). Siguron mbulim shëndetësor për punonjësin dhe anëtarët e familjes. Llogaritet mbi pagën bruto pa kufi të sipërm.',
          },
          {
            step: 'Kontributi i punësimit — 1,2%',
            desc: 'Kontribut për Agjencinë e Punësimit. Siguron të drejtën e kompensimit të papunësisë. Llogaritet mbi pagën bruto, njëlloj si sigurimi shëndetësor.',
          },
          {
            step: 'Tatimi mbi të ardhurat personale — 10%',
            desc: 'Tatimi mbi të ardhurat personale llogaritet mbi bazën e cila është paga bruto minus kontributet sociale (18,8% + 7,5% + 1,2% = 27,5%). Norma është e sheshtë — 10% për të gjitha nivelet e të ardhurave. Dërgohet në UJP mujorisht.',
          },
        ],
      },
      {
        title: 'Shembull: Llogaritja e pagës',
        content:
          'Le të supozojmë se paga bruto është 40.000 MKD. Ja si llogaritet paga neto:',
        items: [
          'Paga bruto: 40.000 MKD',
          'Sigurimi pensional (18,8%): 40.000 x 0,188 = 7.520 MKD',
          'Sigurimi shëndetësor (7,5%): 40.000 x 0,075 = 3.000 MKD',
          'Kontributi i punësimit (1,2%): 40.000 x 0,012 = 480 MKD',
          'Kontribute gjithsej (27,5%): 11.000 MKD',
          'Baza tatimore: 40.000 - 11.000 = 29.000 MKD',
          'Tatimi mbi të ardhurat (10%): 29.000 x 0,10 = 2.900 MKD',
          'Paga neto: 40.000 - 11.000 - 2.900 = 26.100 MKD',
        ],
        steps: null,
      },
      {
        title: 'Paga minimale dhe MPIN',
        content: null,
        items: [
          'Paga minimale neto për 2026: 18.000 MKD (përafërsisht 292 EUR). Çdo punëdhënës duhet të paguajë të paktën pagën minimale për punësim me kohë të plotë.',
          'MPIN (Llogaritja Mujore e Gjetjeve Individuale) — formular elektronik i dorëzuar në UJP çdo muaj. Përmban të dhëna për çdo punonjës: pagë bruto, kontribute, tatim dhe pagë neto.',
          'Afati i dorëzimit të MPIN: jo më vonë se data 15 e muajit për muajin e kaluar. Gjobat për dorëzim të vonuar janë nga 500 deri 2.000 EUR.',
          'Pagesa e kontributeve dhe tatimit: duhet bërë para ose njëkohësisht me pagesën e pagës neto. Nuk lejohet pagesa e pagave pa paguar kontributet paraprakisht.',
          'Kontributet paguhen në llogari të vetme në Zyrën e të Ardhurave Publike (UJP), e cila pastaj i shpërndan në fondet përkatëse.',
        ],
        steps: null,
      },
      {
        title: 'Detyrimet e punëdhënësit',
        content: null,
        items: null,
        steps: [
          {
            step: 'Llogaritni pagën saktë',
            desc: 'Çdo muaj llogaritni të gjitha komponentet: pagë bruto, kontribute, tatim dhe pagë neto. Kini parasysh punën jashtë orarit, pushimet mjekësore, pushimet vjetore dhe variabla të tjera.',
          },
          {
            step: 'Dorëzoni MPIN në UJP',
            desc: 'Dorëzoni elektronikisht formularin MPIN përmes portalit e-Tax (etax.ujp.gov.mk) jo më vonë se data 15. Formulari përmban të dhëna të detajuara për çdo punonjës.',
          },
          {
            step: 'Dërgoni kontributet',
            desc: 'Paguani kontributet sociale dhe tatimin në llogarinë e vetme në UJP. Pagesa duhet përfunduar para pagesës së pagave neto.',
          },
          {
            step: 'Paguani pagën neto',
            desc: 'Pas dërgimit të kontributeve dhe tatimit, paguani pagën neto në llogarinë bankare të çdo punonjësi. Ruani dëshmi për të gjitha pagesat.',
          },
          {
            step: 'Lëshoni fletëpagesë',
            desc: 'Çdo punonjës ka të drejtë të marrë fletëpagesë mujore me ndarje të detajuar: shumë bruto, zbritje kontributesh, tatim dhe pagë neto.',
          },
        ],
      },
      {
        title: 'Si i automatizon Facturino pagat',
        content:
          'Facturino thjeshton të gjithë procesin e pagave për bizneset maqedonase. Sistemi automatikisht aplikon normat aktuale të kontributeve dhe tatimeve, gjeneron formularin MPIN gati për dorëzim në UJP dhe krijon fletëpagesa për çdo punonjës në format të pajtueshëm me rregulloret maqedonase.',
        items: [
          'Llogaritje automatike bruto-në-neto me normat aktuale 2026',
          'Gjenerim i formularit MPIN gati për UJP',
          'Fletëpagesa në maqedonisht sipas standardit MPIN',
          'Mbështetje për punë jashtë orarit, pushime mjekësore dhe festa',
          'Përditësim automatik kur ndryshojnë normat ose paga minimale',
          'Raporte për kostot totale të pagave sipas muajit, departamentit dhe punonjësit',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhëzues për llogaritjen mujore' },
      { slug: 'trudovo-pravo-osnovi', title: "E drejta e punës: 10 gjëra që çdo punëdhënës duhet t'i dijë" },
      { slug: 'personalen-danok-na-dohod', title: 'Tatimi personal mbi të ardhurat në Maqedoni' },
    ],
    cta: {
      title: 'Automatizoni pagat me Facturino',
      desc: 'Llogaritje e saktë, formularë MPIN dhe fletëpagesa — automatikisht, pa gabime, në pajtim me rregulloret maqedonase.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Rehber',
    title: 'Makedonya\'da bordro hesaplama: Katkılar ve vergiler',
    publishDate: '16 Şubat 2026',
    readTime: '8 dk okuma',
    intro:
      'Makedonya\'da bordro hesaplama, işverenin doğru hesaplaması ve ödemesi gereken çeşitli zorunlu katkılar ve vergiler içerir. Bu rehberde tüm maaş bileşenlerini brütten nete kadar ayrıntılı açıklıyoruz — 2026 oranları, asgari ücret ve MPIN dosyalama yükümlülükleri dahil.',
    sections: [
      {
        title: 'Maaş yapısı: Brütten nete',
        content:
          'Makedonya\'da maaşlar brüt tutardan (toplam işveren maliyeti) net tutara (çalışanın aldığı) hesaplanır. Aradaki fark zorunlu sosyal katkılar ve kişisel gelir vergisinden oluşur. İşveren, net maaşı ödemeden önce tüm katkıları ve vergileri hesaplamak, kesintileri yapmak ve ödemekle yükümlüdür.',
        items: null,
        steps: null,
      },
      {
        title: 'Zorunlu katkılar (2026 oranları)',
        content:
          'Makedonya\'da sosyal katkılar brüt maaş üzerinden hesaplanır ve çalışan tarafından ödenir (brüt maaştan kesilir). İşveren bunları hesaplamak ve ödemekle sorumludur.',
        items: null,
        steps: [
          {
            step: 'Emeklilik ve malullük sigortası — %18,8',
            desc: 'En büyük katkı, emeklilik fonuna (PIOM) yönlendirilir. Bu %18,8\'in bir kısmı zorunlu ikinci sütun emeklilik fonuna gider (1967 sonrası doğanlar için %6). Matrah brüt maaştır, minimum matrah %50 ve maksimum 16 ortalama maaştır.',
          },
          {
            step: 'Sağlık sigortası — %7,5',
            desc: 'Sağlık Sigortası Fonuna (FZO) katkı. Çalışan ve aile üyeleri için sağlık hizmeti sağlar. Brüt maaş üzerinden üst limit olmaksızın hesaplanır.',
          },
          {
            step: 'İstihdam katkısı — %1,2',
            desc: 'İstihdam Ajansına katkı. İşsizlik ödeneği hakkı sağlar. Brüt maaş üzerinden, sağlık sigortasıyla aynı şekilde hesaplanır.',
          },
          {
            step: 'Kişisel gelir vergisi — %10',
            desc: 'Kişisel gelir vergisi, brüt maaştan sosyal katkılar düşüldükten sonraki matrah üzerinden hesaplanır (%18,8 + %7,5 + %1,2 = %27,5). Oran düzdür — tüm gelir seviyeleri için %10. UJP\'ye aylık olarak ödenir.',
          },
        ],
      },
      {
        title: 'Örnek: Maaş hesaplama',
        content:
          'Brüt maaşın 40.000 MKD olduğunu varsayalım. Net maaş şu şekilde hesaplanır:',
        items: [
          'Brüt maaş: 40.000 MKD',
          'Emeklilik sigortası (%18,8): 40.000 x 0,188 = 7.520 MKD',
          'Sağlık sigortası (%7,5): 40.000 x 0,075 = 3.000 MKD',
          'İstihdam katkısı (%1,2): 40.000 x 0,012 = 480 MKD',
          'Toplam katkılar (%27,5): 11.000 MKD',
          'Vergi matrahı: 40.000 - 11.000 = 29.000 MKD',
          'Gelir vergisi (%10): 29.000 x 0,10 = 2.900 MKD',
          'Net maaş: 40.000 - 11.000 - 2.900 = 26.100 MKD',
        ],
        steps: null,
      },
      {
        title: 'Asgari ücret ve MPIN',
        content: null,
        items: [
          '2026 asgari net ücret: 18.000 MKD (yaklaşık 292 EUR). Her işveren tam zamanlı istihdam için en az asgari ücreti ödemek zorundadır.',
          'MPIN (Bireysel Bulguların Aylık Hesaplaması) — her ay UJP\'ye sunulan elektronik form. Her çalışan için veri içerir: brüt maaş, katkılar, vergi ve net maaş.',
          'MPIN dosyalama son tarihi: önceki ay için ayın en geç 15\'ine kadar. Geç dosyalama cezaları 500 ile 2.000 EUR arasındadır.',
          'Katkı ve vergi ödemesi: net maaş ödemesinden önce veya eş zamanlı yapılmalıdır. Katkıları ödemeden maaş ödeyemezsiniz.',
          'Katkılar, Kamu Gelir Dairesindeki (UJP) tek hesaba ödenir ve ardından ilgili fonlara dağıtılır.',
        ],
        steps: null,
      },
      {
        title: 'İşveren yükümlülükleri',
        content: null,
        items: null,
        steps: [
          {
            step: 'Maaşı doğru hesaplayın',
            desc: 'Her ay tüm bileşenleri hesaplayın: brüt maaş, katkılar, vergi ve net maaş. Fazla mesai, hastalık izni, yıllık izin ve tutarı etkileyen diğer değişkenleri hesaba katın.',
          },
          {
            step: 'MPIN\'i UJP\'ye gönderin',
            desc: 'MPIN formunu e-Tax portalı (etax.ujp.gov.mk) aracılığıyla en geç ayın 15\'ine kadar elektronik olarak gönderin. Form her çalışan için ayrıntılı veri içerir.',
          },
          {
            step: 'Katkıları ödeyin',
            desc: 'Sosyal katkıları ve vergiyi UJP\'deki tek hesaba ödeyin. Ödeme, çalışanlara net maaş ödemeden önce tamamlanmalıdır.',
          },
          {
            step: 'Net maaşı ödeyin',
            desc: 'Katkıları ve vergiyi ödedikten sonra, her çalışanın banka hesabına net maaşı ödeyin. Tüm ödemelerin makbuzlarını saklayın.',
          },
          {
            step: 'Bordro belgesi düzenleyin',
            desc: 'Her çalışan, tüm maaş bileşenlerinin ayrıntılı dökümünü içeren aylık bordro belgesi (pay slip) alma hakkına sahiptir: brüt tutar, katkı kesintileri, vergi ve net maaş.',
          },
        ],
      },
      {
        title: 'Facturino bordroyu nasıl otomatikleştirir',
        content:
          'Facturino, Makedon işletmeleri için tüm bordro sürecini basitleştirir. Sistem, güncel katkı ve vergi oranlarını otomatik olarak uygular, UJP\'ye gönderime hazır MPIN formları oluşturur ve her çalışan için Makedon düzenlemelerine uygun formatta bordro belgeleri oluşturur.',
        items: [
          'Güncel 2026 oranlarıyla otomatik brütten nete hesaplama',
          'UJP\'ye hazır MPIN formu oluşturma',
          'MPIN standardına göre Makedonca bordro belgeleri',
          'Fazla mesai, hastalık izni ve tatil desteği',
          'Oranlar veya asgari ücret değiştiğinde otomatik güncelleme',
          'Aya, departmana ve çalışana göre toplam bordro maliyeti raporları',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'mpin-obrazec', title: 'MPIN formu: Aylık hesaplama rehberi' },
      { slug: 'trudovo-pravo-osnovi', title: 'İş hukuku: Her işverenin bilmesi gereken 10 şey' },
      { slug: 'personalen-danok-na-dohod', title: "Makedonya'da kişisel gelir vergisi" },
    ],
    cta: {
      title: 'Facturino ile bordroyu otomatikleştirin',
      desc: 'Doğru hesaplamalar, MPIN formları ve bordro belgeleri — otomatik, hatasız, Makedon düzenlemelerine uygun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function PresmetkaNaPlataMkPage({
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
