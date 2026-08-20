import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/minimalna-plata', {
    title: {
      mk: 'Минимална плата во Македонија 2026 — бруто и нето износ',
      en: 'Minimum Wage in North Macedonia 2026 — Gross & Net',
      sq: 'Paga minimale në Maqedoni 2026 — shuma bruto dhe neto',
      tr: 'Kuzey Makedonya\'da Asgari Ücret 2026 — Brüt ve Net',
    },
    description: {
      mk: 'Колку е минималната плата во Македонија 2026: бруто 38.507 МКД, нето 26.046 МКД (~423 EUR). Пресметка бруто→нето, придонеси 28%, данок 10%, лично ослободување и обврски на работодавачот.',
      en: 'How much is the minimum wage in North Macedonia 2026: gross 38,507 MKD, net 26,046 MKD (~EUR 423). Gross-to-net calculation, 28% contributions, 10% tax, personal allowance and employer obligations.',
      sq: 'Sa është paga minimale në Maqedoni 2026: bruto 38.507 MKD, neto 26.046 MKD (~423 EUR). Llogaritja bruto→neto, kontribute 28%, tatim 10%, zbritja personale dhe detyrimet e punëdhënësit.',
      tr: 'Kuzey Makedonya\'da asgari ücret 2026 ne kadar: brüt 38.507 MKD, net 26.046 MKD (~423 EUR). Brütten nete hesaplama, %28 katkı, %10 vergi, kişisel indirim ve işveren yükümlülükleri.',
    },
    datePublished: '2026-08-20',
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '← Ресурси',
    tag: 'Референца',
    title: 'Минимална плата во Македонија 2026: бруто и нето износ',
    publishDate: '20 август 2026',
    readTime: '7 мин читање',
    intro:
      'Минималната плата е најнискиот законски дозволен износ што работодавачот смее да го исплати за полно работно време. Според последните податоци, минималната бруто плата во Македонија изнесува 38.507 МКД, што одговара на нето износ од приближно 26.046 МКД (околу 423 EUR). Минималната плата се утврдува со владина уредба секоја година и расте со текот на времето — затоа секогаш проверете го актуелниот износ пред пресметка на платите.',
    sections: [
      {
        title: 'Што е минимална плата?',
        content:
          'Минималната плата е законски определен минимален износ на плата за работник со полно работно време (40 часа неделно). Ниту еден работодавач не смее да исплати помалку — исплата под минималната плата е прекршок според Законот за минимална плата и Законот за работни односи (ЗРО). Минималната плата се утврдува со владина уредба, врз основа на податоци од Државниот завод за статистика за просечната плата и животните трошоци, и се усогласува еднаш годишно.',
        items: null,
        steps: null,
      },
      {
        title: 'Колку е минималната плата во 2026 година?',
        content:
          'Според последните податоци, актуелните износи на минималната плата се следните. Овие бројки се менуваат секоја година со владина уредба, па третирајте ги како тековни, а не трајни вредности:',
        items: [
          'Минимална бруто плата: 38.507 МКД месечно (за полно работно време од 40 часа неделно)',
          'Минимална нето плата: приближно 26.046 МКД месечно',
          'Во евра: приближно 423 EUR нето по среден курс на НБРСМ (61,5 МКД за 1 EUR)',
          'Важи за сите дејности — нема посебна минимална плата по сектор или регион',
          'За скратено (пропорционално) работно време, минималната плата се пресметува сразмерно на часовите',
          'Износот се усогласува секоја година — секогаш проверете ја последната владина уредба пред пресметка',
        ],
        steps: null,
      },
      {
        title: 'Како се пресметува од бруто до нето',
        content:
          'Разликата помеѓу бруто и нето платата се состои од задолжителните социјални придонеси (28%) и данокот на личен доход (10%). Еве го редоследот на пресметка од бруто до нето:',
        items: null,
        steps: [
          {
            step: 'Почни од бруто платата',
            desc: 'Бруто платата е основицата за пресметка. За минималната плата тоа е 38.507 МКД. Ова е вкупниот трошок на работодавачот за платата — во македонскиот модел сите придонеси се одбиваат од бруто платата, без додаток на товар на работодавачот.',
          },
          {
            step: 'Одбиј ги придонесите (28% од бруто)',
            desc: 'Од бруто платата се одбиваат социјалните придонеси: пензиско и инвалидско осигурување 18,8%, здравствено 7,5%, придонес за вработување 1,2% и дополнителен придонес 0,5% — вкупно 28%. За 38.507 МКД тоа изнесува приближно 10.782 МКД.',
          },
          {
            step: 'Пресметај ја даночната основица минус личното ослободување',
            desc: 'Даночната основица е бруто платата минус придонесите минус месечното лично ослободување од 10.932 МКД. Личното ослободување ги намалува даночните обврски и за минималната плата практично ја сведува основицата на многу низок износ.',
          },
          {
            step: 'Одбиј данок на личен доход (10%)',
            desc: 'На даночната основица се пресметува рамен данок од 10%. Стапката е иста за сите нивоа на доход. Кај минималната плата данокот е минимален поради високото лично ослободување.',
          },
          {
            step: 'Добиј ја нето платата',
            desc: 'Нето платата е она што вработениот го добива на трансакциска сметка: бруто минус придонеси минус данок. За минималната бруто плата од 38.507 МКД, нето износот е приближно 26.046 МКД.',
          },
        ],
      },
      {
        title: 'Разбивка на одбитоците',
        content:
          'Ова е детален преглед на сите одбитоци што ја делат бруто од нето минималната плата. Сите придонеси се пресметуваат на бруто основицата:',
        items: [
          'Бруто плата: 38.507 МКД (100%)',
          'Пензиско и инвалидско осигурување (18,8%): приближно 7.239 МКД',
          'Здравствено осигурување (7,5%): приближно 2.888 МКД',
          'Придонес за вработување (1,2%): приближно 462 МКД',
          'Дополнителен придонес (0,5%): приближно 193 МКД',
          'Вкупно придонеси (28%): приближно 10.782 МКД',
          'Лично (месечно) ослободување за данок: 10.932 МКД',
          'Данок на личен доход (10% на основицата по ослободувањето): минимален износ',
          'Нето плата на рака: приближно 26.046 МКД',
        ],
        steps: null,
      },
      {
        title: 'На кого се однесува и обврски на работодавачот',
        content:
          'Минималната плата важи за секој работник со полно работно време, без оглед на дејноста. Работодавачот има конкретни законски обврски поврзани со неа:',
        items: [
          'Секој работник со полно работно време мора да прими најмалку минимална бруто плата',
          'Исплата под минималната плата е прекршок — казна за правно лице обично EUR 1.000-2.000',
          'Работникот со скратено работно време има право на пропорционален дел од минималната плата',
          'Придонесите и данокот мора да се уплатат пред или истовремено со нето платата',
          'Работодавачот поднесува МПИН образец до УЈП секој месец, најдоцна до 15-ти за претходниот месец',
          'Платата мора да се исплати на трансакциска сметка — исплата во готовина е забранета',
          'При покачување на минималната плата со нова уредба, работодавачот е должен веднаш да го примени новиот износ',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ја автоматизира пресметката',
        content:
          'Facturino автоматски ја пресметува платата од бруто до нето со актуелните стапки на придонеси и данок, го применува личното ослободување и го генерира МПИН образецот спремен за поднесување до УЈП. Кога владата ќе ја покачи минималната плата, системот се ажурира автоматски — без рачно менување на стапки.',
        items: [
          'Автоматска пресметка бруто→нето со актуелни стапки (28% придонеси, 10% данок)',
          'Проверка дали платата е под законски минимум и предупредување',
          'Генерирање на МПИН образец спремен за УЈП',
          'Автоматско применување на месечното лично ослободување',
          'Платни листи на македонски јазик по МПИН стандард',
          'Автоматско ажурирање при промена на минималната плата или стапките',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани ресурси',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Калкулатор за плата: бруто ↔ нето' },
      { slug: 'prosecna-plata', title: 'Просечна плата во Македонија' },
      { slug: 'danocni-stapki', title: 'Даночни стапки во Македонија' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Пресметка на плата: придонеси и даноци' },
    ],
    bottomCta: {
      title: 'Пресметајте ја платата точно',
      subtitle: 'Facturino ги применува актуелните стапки и минималната плата — автоматски, без грешки.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Resources',
    tag: 'Reference',
    title: 'Minimum Wage in North Macedonia 2026: Gross & Net',
    publishDate: 'August 20, 2026',
    readTime: '7 min read',
    intro:
      'The minimum wage is the lowest legally permitted amount an employer may pay for full-time work. Based on the latest data, the minimum gross wage in North Macedonia is 38,507 MKD, corresponding to a net amount of approximately 26,046 MKD (around EUR 423). The minimum wage is set by government decree each year and rises over time — so always check the current figure before running payroll.',
    sections: [
      {
        title: 'What is the minimum wage?',
        content:
          'The minimum wage is a legally defined minimum salary for a full-time worker (40 hours per week). No employer may pay less — paying below the minimum wage is a violation under the Minimum Wage Law and the Law on Labor Relations (LLR). The minimum wage is set by government decree, based on State Statistical Office data on average salary and cost of living, and is adjusted once a year.',
        items: null,
        steps: null,
      },
      {
        title: 'How much is the minimum wage in 2026?',
        content:
          'Based on the latest data, the current minimum wage figures are as follows. These numbers change every year by government decree, so treat them as current rather than permanent values:',
        items: [
          'Minimum gross wage: 38,507 MKD per month (for full-time work of 40 hours per week)',
          'Minimum net wage: approximately 26,046 MKD per month',
          'In euros: approximately EUR 423 net at the NBRSM mid rate (61.5 MKD per EUR)',
          'Applies to all industries — there is no sector-specific or regional minimum wage',
          'For part-time work, the minimum wage is calculated proportionally to the hours',
          'The amount is adjusted annually — always check the latest government decree before running payroll',
        ],
        steps: null,
      },
      {
        title: 'How gross-to-net is calculated',
        content:
          'The difference between gross and net salary consists of mandatory social contributions (28%) and personal income tax (10%). Here is the order of calculation from gross to net:',
        items: null,
        steps: [
          {
            step: 'Start from the gross salary',
            desc: 'The gross salary is the base for the calculation. For the minimum wage this is 38,507 MKD. This is the total employer cost for the salary — in the Macedonian model all contributions are deducted from the gross salary, with no employer add-on.',
          },
          {
            step: 'Deduct contributions (28% of gross)',
            desc: 'Social contributions are deducted from the gross salary: pension and disability insurance 18.8%, health 7.5%, unemployment 1.2%, and an additional contribution 0.5% — 28% in total. For 38,507 MKD this is approximately 10,782 MKD.',
          },
          {
            step: 'Compute the taxable base minus the personal allowance',
            desc: 'The taxable base is the gross salary minus contributions minus the monthly personal allowance of 10,932 MKD. The personal allowance reduces the tax liability and, for the minimum wage, effectively brings the base down to a very low amount.',
          },
          {
            step: 'Deduct personal income tax (10%)',
            desc: 'A flat 10% tax is applied to the taxable base. The rate is the same for all income levels. For the minimum wage the tax is minimal due to the high personal allowance.',
          },
          {
            step: 'Arrive at the net salary',
            desc: 'The net salary is what the employee receives in their bank account: gross minus contributions minus tax. For a minimum gross wage of 38,507 MKD, the net amount is approximately 26,046 MKD.',
          },
        ],
      },
      {
        title: 'Breakdown of deductions',
        content:
          'This is a detailed overview of all deductions that separate gross from net minimum wage. All contributions are calculated on the gross base:',
        items: [
          'Gross salary: 38,507 MKD (100%)',
          'Pension and disability insurance (18.8%): approximately 7,239 MKD',
          'Health insurance (7.5%): approximately 2,888 MKD',
          'Unemployment contribution (1.2%): approximately 462 MKD',
          'Additional contribution (0.5%): approximately 193 MKD',
          'Total contributions (28%): approximately 10,782 MKD',
          'Personal (monthly) tax allowance: 10,932 MKD',
          'Personal income tax (10% on the base after the allowance): minimal amount',
          'Net take-home salary: approximately 26,046 MKD',
        ],
        steps: null,
      },
      {
        title: 'Who it applies to and employer obligations',
        content:
          'The minimum wage applies to every full-time worker, regardless of industry. The employer has specific legal obligations related to it:',
        items: [
          'Every full-time worker must receive at least the minimum gross wage',
          'Paying below the minimum wage is a violation — the fine for legal entities is typically EUR 1,000-2,000',
          'A part-time worker is entitled to a proportional share of the minimum wage',
          'Contributions and tax must be remitted before or simultaneously with the net salary',
          'The employer files an MPIN form with UJP every month, no later than the 15th for the previous month',
          'The salary must be paid to a bank account — cash payments are prohibited',
          'When the minimum wage is raised by a new decree, the employer must apply the new amount immediately',
        ],
        steps: null,
      },
      {
        title: 'How Facturino automates the calculation',
        content:
          'Facturino automatically calculates the salary from gross to net using current contribution and tax rates, applies the personal allowance, and generates the MPIN form ready for UJP submission. When the government raises the minimum wage, the system updates automatically — with no manual rate changes.',
        items: [
          'Automatic gross-to-net calculation with current rates (28% contributions, 10% tax)',
          'Checks whether pay is below the legal minimum and warns you',
          'MPIN form generation ready for UJP',
          'Automatic application of the monthly personal allowance',
          'Pay slips in Macedonian per the MPIN standard',
          'Automatic updates when the minimum wage or rates change',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related resources',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Salary Calculator: Gross ↔ Net' },
      { slug: 'prosecna-plata', title: 'Average Salary in North Macedonia' },
      { slug: 'danocni-stapki', title: 'Tax Rates in North Macedonia' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Payroll Calculation: Contributions and Taxes' },
    ],
    bottomCta: {
      title: 'Calculate salaries accurately',
      subtitle: 'Facturino applies current rates and the minimum wage — automatically, error-free.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Burime',
    tag: 'Referencë',
    title: 'Paga minimale në Maqedoni 2026: shuma bruto dhe neto',
    publishDate: '20 gusht 2026',
    readTime: '7 min lexim',
    intro:
      'Paga minimale është shuma më e ulët e lejuar ligjërisht që punëdhënësi mund të paguajë për punë me kohë të plotë. Sipas të dhënave të fundit, paga minimale bruto në Maqedoni është 38.507 MKD, që i korrespondon një shume neto prej afërsisht 26.046 MKD (rreth 423 EUR). Paga minimale përcaktohet me vendim qeveritar çdo vit dhe rritet me kalimin e kohës — prandaj kontrolloni gjithmonë shumën aktuale para se të llogaritni pagat.',
    sections: [
      {
        title: 'Çfarë është paga minimale?',
        content:
          'Paga minimale është paga minimale e përcaktuar ligjërisht për një punëtor me kohë të plotë (40 orë në javë). Asnjë punëdhënës nuk mund të paguajë më pak — pagesa nën pagën minimale është kundërvajtje sipas Ligjit të Pagës Minimale dhe Ligjit të Marrëdhënieve të Punës (LMP). Paga minimale përcaktohet me vendim qeveritar, bazuar në të dhënat e Entit Shtetëror të Statistikës për pagën mesatare dhe koston e jetesës, dhe përshtatet një herë në vit.',
        items: null,
        steps: null,
      },
      {
        title: 'Sa është paga minimale në 2026?',
        content:
          'Sipas të dhënave të fundit, shifrat aktuale të pagës minimale janë si më poshtë. Këto shifra ndryshojnë çdo vit me vendim qeveritar, prandaj trajtojini si vlera aktuale dhe jo të përhershme:',
        items: [
          'Paga minimale bruto: 38.507 MKD në muaj (për punë me kohë të plotë prej 40 orësh në javë)',
          'Paga minimale neto: afërsisht 26.046 MKD në muaj',
          'Në euro: afërsisht 423 EUR neto sipas kursit mesatar të NBRSM (61,5 MKD për 1 EUR)',
          'Vlen për të gjitha industritë — nuk ka pagë minimale sektoriale ose rajonale',
          'Për punë me kohë të pjesshme, paga minimale llogaritet proporcionalisht me orët',
          'Shuma përshtatet çdo vit — kontrolloni gjithmonë vendimin e fundit qeveritar para llogaritjes',
        ],
        steps: null,
      },
      {
        title: 'Si llogaritet nga bruto në neto',
        content:
          'Diferenca mes pagës bruto dhe neto përbëhet nga kontributet e detyrueshme sociale (28%) dhe tatimi mbi të ardhurat personale (10%). Ja rendi i llogaritjes nga bruto në neto:',
        items: null,
        steps: [
          {
            step: 'Nis nga paga bruto',
            desc: 'Paga bruto është baza për llogaritjen. Për pagën minimale kjo është 38.507 MKD. Kjo është kosto totale e punëdhënësit për pagën — në modelin maqedonas të gjitha kontributet zbriten nga paga bruto, pa shtesë në ngarkim të punëdhënësit.',
          },
          {
            step: 'Zbrit kontributet (28% të bruto-s)',
            desc: 'Nga paga bruto zbriten kontributet sociale: sigurimi pensional dhe invalidor 18,8%, shëndetësi 7,5%, papunësi 1,2% dhe kontribut shtesë 0,5% — gjithsej 28%. Për 38.507 MKD kjo është afërsisht 10.782 MKD.',
          },
          {
            step: 'Llogarit bazën tatimore minus zbritjen personale',
            desc: 'Baza tatimore është paga bruto minus kontributet minus zbritja mujore personale prej 10.932 MKD. Zbritja personale ul detyrimin tatimor dhe, për pagën minimale, praktikisht e sjell bazën në një shumë shumë të ulët.',
          },
          {
            step: 'Zbrit tatimin mbi të ardhurat personale (10%)',
            desc: 'Mbi bazën tatimore aplikohet tatimi i sheshtë 10%. Norma është e njëjtë për të gjitha nivelet e të ardhurave. Për pagën minimale tatimi është minimal për shkak të zbritjes së lartë personale.',
          },
          {
            step: 'Arrij te paga neto',
            desc: 'Paga neto është ajo që punonjësi merr në llogarinë bankare: bruto minus kontributet minus tatimi. Për pagën minimale bruto prej 38.507 MKD, shuma neto është afërsisht 26.046 MKD.',
          },
        ],
      },
      {
        title: 'Ndarja e zbritjeve',
        content:
          'Ky është një pasqyrë e detajuar e të gjitha zbritjeve që ndajnë pagën minimale bruto nga neto. Të gjitha kontributet llogariten mbi bazën bruto:',
        items: [
          'Paga bruto: 38.507 MKD (100%)',
          'Sigurimi pensional dhe invalidor (18,8%): afërsisht 7.239 MKD',
          'Sigurimi shëndetësor (7,5%): afërsisht 2.888 MKD',
          'Kontributi i papunësisë (1,2%): afërsisht 462 MKD',
          'Kontribut shtesë (0,5%): afërsisht 193 MKD',
          'Kontribute gjithsej (28%): afërsisht 10.782 MKD',
          'Zbritja personale (mujore) tatimore: 10.932 MKD',
          'Tatimi mbi të ardhurat personale (10% mbi bazën pas zbritjes): shumë minimale',
          'Paga neto në dorë: afërsisht 26.046 MKD',
        ],
        steps: null,
      },
      {
        title: 'Kujt i vlen dhe detyrimet e punëdhënësit',
        content:
          'Paga minimale vlen për çdo punëtor me kohë të plotë, pavarësisht industrisë. Punëdhënësi ka detyrime ligjore konkrete lidhur me të:',
        items: [
          'Çdo punëtor me kohë të plotë duhet të marrë të paktën pagën minimale bruto',
          'Pagesa nën pagën minimale është kundërvajtje — gjoba për persona juridikë zakonisht EUR 1.000-2.000',
          'Punëtori me kohë të pjesshme ka të drejtë për pjesë proporcionale të pagës minimale',
          'Kontributet dhe tatimi duhet paguar para ose njëkohësisht me pagën neto',
          'Punëdhënësi dorëzon formularin MPIN në UJP çdo muaj, jo më vonë se data 15 për muajin e kaluar',
          'Paga duhet paguar në llogari bankare — pagesa me para në dorë është e ndaluar',
          'Kur paga minimale rritet me vendim të ri, punëdhënësi duhet të aplikojë menjëherë shumën e re',
        ],
        steps: null,
      },
      {
        title: 'Si e automatizon Facturino llogaritjen',
        content:
          'Facturino llogarit automatikisht pagën nga bruto në neto me normat aktuale të kontributeve dhe tatimit, aplikon zbritjen personale dhe gjeneron formularin MPIN gati për dorëzim në UJP. Kur qeveria rrit pagën minimale, sistemi përditësohet automatikisht — pa ndryshim manual të normave.',
        items: [
          'Llogaritje automatike bruto→neto me normat aktuale (28% kontribute, 10% tatim)',
          'Kontrollon nëse paga është nën minimumin ligjor dhe ju paralajmëron',
          'Gjenerim i formularit MPIN gati për UJP',
          'Aplikim automatik i zbritjes mujore personale',
          'Fletëpagesa në maqedonisht sipas standardit MPIN',
          'Përditësim automatik kur ndryshon paga minimale ose normat',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Burime të ngjashme',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Llogaritësi i pagës: bruto ↔ neto' },
      { slug: 'prosecna-plata', title: 'Paga mesatare në Maqedoni' },
      { slug: 'danocni-stapki', title: 'Normat tatimore në Maqedoni' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Llogaritja e pagës: kontributet dhe tatimet' },
    ],
    bottomCta: {
      title: 'Llogaritni pagat me saktësi',
      subtitle: 'Facturino aplikon normat aktuale dhe pagën minimale — automatikisht, pa gabime.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Kaynaklar',
    tag: 'Referans',
    title: 'Kuzey Makedonya\'da Asgari Ücret 2026: Brüt ve Net',
    publishDate: '20 Ağustos 2026',
    readTime: '7 dk okuma',
    intro:
      'Asgari ücret, işverenin tam zamanlı çalışma için ödeyebileceği yasal olarak izin verilen en düşük tutardır. Son verilere göre, Kuzey Makedonya\'da brüt asgari ücret 38.507 MKD olup, yaklaşık 26.046 MKD (yaklaşık 423 EUR) net tutara karşılık gelir. Asgari ücret her yıl hükümet kararnamesiyle belirlenir ve zamanla artar — bu nedenle bordro hesaplamadan önce her zaman güncel rakamı kontrol edin.',
    sections: [
      {
        title: 'Asgari ücret nedir?',
        content:
          'Asgari ücret, tam zamanlı bir işçi (haftada 40 saat) için yasal olarak tanımlanmış asgari maaştır. Hiçbir işveren daha az ödeyemez — asgari ücretin altında ödeme, Asgari Ücret Kanunu ve İş İlişkileri Kanunu (İİK) uyarınca bir ihlaldir. Asgari ücret, Devlet İstatistik Kurumu\'nun ortalama maaş ve yaşam maliyeti verilerine dayanarak hükümet kararnamesiyle belirlenir ve yılda bir kez ayarlanır.',
        items: null,
        steps: null,
      },
      {
        title: '2026\'da asgari ücret ne kadar?',
        content:
          'Son verilere göre, güncel asgari ücret rakamları aşağıdaki gibidir. Bu rakamlar her yıl hükümet kararnamesiyle değişir, bu nedenle bunları kalıcı değil güncel değerler olarak ele alın:',
        items: [
          'Brüt asgari ücret: aylık 38.507 MKD (haftada 40 saatlik tam zamanlı çalışma için)',
          'Net asgari ücret: aylık yaklaşık 26.046 MKD',
          'Euro cinsinden: NBRSM orta kuruyla yaklaşık 423 EUR net (1 EUR için 61,5 MKD)',
          'Tüm sektörler için geçerlidir — sektöre veya bölgeye özgü asgari ücret yoktur',
          'Yarı zamanlı çalışma için asgari ücret, saatlerle orantılı olarak hesaplanır',
          'Tutar her yıl ayarlanır — hesaplamadan önce her zaman en son hükümet kararnamesini kontrol edin',
        ],
        steps: null,
      },
      {
        title: 'Brütten nete nasıl hesaplanır',
        content:
          'Brüt ve net maaş arasındaki fark, zorunlu sosyal katkılardan (%28) ve kişisel gelir vergisinden (%10) oluşur. Brütten nete hesaplama sırası şöyledir:',
        items: null,
        steps: [
          {
            step: 'Brüt maaştan başlayın',
            desc: 'Brüt maaş, hesaplamanın temelidir. Asgari ücret için bu 38.507 MKD\'dir. Bu, maaş için toplam işveren maliyetidir — Makedon modelinde tüm katkılar brüt maaştan kesilir, işveren üzerine ek yük yoktur.',
          },
          {
            step: 'Katkıları düşün (brütün %28\'i)',
            desc: 'Brüt maaştan sosyal katkılar kesilir: emeklilik ve malullük sigortası %18,8, sağlık %7,5, işsizlik %1,2 ve ek katkı %0,5 — toplam %28. 38.507 MKD için bu yaklaşık 10.782 MKD\'dir.',
          },
          {
            step: 'Vergi matrahını kişisel indirim düşülerek hesaplayın',
            desc: 'Vergi matrahı, brüt maaş eksi katkılar eksi aylık 10.932 MKD kişisel indirimdir. Kişisel indirim vergi yükümlülüğünü azaltır ve asgari ücret için matrahı pratikte çok düşük bir tutara indirir.',
          },
          {
            step: 'Kişisel gelir vergisini düşün (%10)',
            desc: 'Vergi matrahına düz %10 vergi uygulanır. Oran tüm gelir seviyeleri için aynıdır. Asgari ücret için yüksek kişisel indirim nedeniyle vergi minimaldir.',
          },
          {
            step: 'Net maaşa ulaşın',
            desc: 'Net maaş, çalışanın banka hesabına aldığı tutardır: brüt eksi katkılar eksi vergi. 38.507 MKD brüt asgari ücret için net tutar yaklaşık 26.046 MKD\'dir.',
          },
        ],
      },
      {
        title: 'Kesintilerin dökümü',
        content:
          'Bu, brüt asgari ücreti netten ayıran tüm kesintilerin ayrıntılı bir dökümüdür. Tüm katkılar brüt matrah üzerinden hesaplanır:',
        items: [
          'Brüt maaş: 38.507 MKD (%100)',
          'Emeklilik ve malullük sigortası (%18,8): yaklaşık 7.239 MKD',
          'Sağlık sigortası (%7,5): yaklaşık 2.888 MKD',
          'İşsizlik katkısı (%1,2): yaklaşık 462 MKD',
          'Ek katkı (%0,5): yaklaşık 193 MKD',
          'Toplam katkılar (%28): yaklaşık 10.782 MKD',
          'Kişisel (aylık) vergi indirimi: 10.932 MKD',
          'Kişisel gelir vergisi (indirim sonrası matrah üzerinden %10): minimal tutar',
          'Net ele geçen maaş: yaklaşık 26.046 MKD',
        ],
        steps: null,
      },
      {
        title: 'Kimlere uygulanır ve işveren yükümlülükleri',
        content:
          'Asgari ücret, sektör fark etmeksizin her tam zamanlı işçi için geçerlidir. İşverenin bununla ilgili belirli yasal yükümlülükleri vardır:',
        items: [
          'Her tam zamanlı işçi en az brüt asgari ücreti almalıdır',
          'Asgari ücretin altında ödeme bir ihlaldir — tüzel kişiler için ceza genellikle EUR 1.000-2.000\'dir',
          'Yarı zamanlı işçi, asgari ücretin orantılı bir payına hak kazanır',
          'Katkılar ve vergi, net maaştan önce veya eş zamanlı ödenmelidir',
          'İşveren her ay, önceki ay için en geç 15\'ine kadar UJP\'ye MPIN formu sunar',
          'Maaş banka hesabına ödenmelidir — nakit ödeme yasaktır',
          'Yeni bir kararnameyle asgari ücret artırıldığında, işveren yeni tutarı derhal uygulamalıdır',
        ],
        steps: null,
      },
      {
        title: 'Facturino hesaplamayı nasıl otomatikleştirir',
        content:
          'Facturino, güncel katkı ve vergi oranlarını kullanarak maaşı brütten nete otomatik olarak hesaplar, kişisel indirimi uygular ve UJP\'ye gönderime hazır MPIN formunu oluşturur. Hükümet asgari ücreti artırdığında, sistem otomatik olarak güncellenir — manuel oran değişikliği olmadan.',
        items: [
          'Güncel oranlarla otomatik brütten nete hesaplama (%28 katkı, %10 vergi)',
          'Ödemenin yasal asgari ücretin altında olup olmadığını kontrol eder ve sizi uyarır',
          'UJP\'ye hazır MPIN formu oluşturma',
          'Aylık kişisel indirimin otomatik uygulanması',
          'MPIN standardına göre Makedonca bordro belgeleri',
          'Asgari ücret veya oranlar değiştiğinde otomatik güncelleme',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili kaynaklar',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Maaş Hesaplayıcı: Brüt ↔ Net' },
      { slug: 'prosecna-plata', title: 'Kuzey Makedonya\'da Ortalama Maaş' },
      { slug: 'danocni-stapki', title: 'Kuzey Makedonya\'da Vergi Oranları' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Bordro Hesaplama: Katkılar ve Vergiler' },
    ],
    bottomCta: {
      title: 'Maaşları doğru hesaplayın',
      subtitle: 'Facturino güncel oranları ve asgari ücreti uygular — otomatik, hatasız.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function MinimalnaPlataPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: t.title, href: `/${locale}/minimalna-plata` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Колку е минималната плата во Македонија за 2026?', answer: 'Минималната бруто плата изнесува 38.507 МКД месечно, што одговара на нето плата од приближно 26.046 МКД (околу 423 EUR). Износот се утврдува со владина уредба и се менува секоја година.' },
        { question: 'Колку е минималната нето плата?', answer: 'Минималната нето плата е приближно 26.046 МКД месечно — тоа е износот на рака по одбиток на придонеси (28%) и данок на личен доход (10%).' },
        { question: 'Како се пресметува нето од бруто минимална плата?', answer: 'Од бруто платата (38.507 МКД) се одбиваат придонесите од 28% (пензиско 18,8%, здравствено 7,5%, вработување 1,2%, дополнителен 0,5%), потоа на основицата намалена за личното ослободување од 10.932 МКД се пресметува данок од 10%. Резултатот е нето плата од приближно 26.046 МКД.' },
        { question: 'Кој ја утврдува минималната плата?', answer: 'Минималната плата се утврдува со владина уредба секоја година, врз основа на податоци од Државниот завод за статистика за просечната плата и животните трошоци. Затоа расте со текот на времето.' },
        { question: 'Дали може да се исплати плата под минималната?', answer: 'Не. Исплата под минималната плата за полно работно време е прекршок според Законот за минимална плата и ЗРО. Казната за правно лице обично изнесува EUR 1.000-2.000.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/resursi`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={`/${locale}/${ra.slug}`}
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
