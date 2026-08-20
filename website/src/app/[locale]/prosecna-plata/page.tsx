import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/prosecna-plata', {
    title: {
      mk: 'Просечна плата во Македонија 2026 — бруто и нето',
      en: 'Average Salary in North Macedonia 2026 — Gross & Net',
      sq: 'Paga mesatare në Maqedoni 2026 — bruto dhe neto',
      tr: 'Kuzey Makedonya\'da Ortalama Maaş 2026 — Brüt ve Net',
    },
    description: {
      mk: 'Колку изнесува просечната плата во Македонија во 2026? Просечна бруто плата ~69.141 МКД според Државниот завод за статистика, пресметка на нето износ, што влијае на висината и споредба со минималната плата.',
      en: 'What is the average salary in North Macedonia in 2026? Average gross salary ~69,141 MKD per the State Statistical Office, net calculation, factors that affect it, and comparison with the minimum wage.',
      sq: 'Sa është paga mesatare në Maqedoni në 2026? Paga mesatare bruto ~69.141 MKD sipas Entit Shtetëror të Statistikës, llogaritja neto, faktorët që ndikojnë dhe krahasimi me pagën minimale.',
      tr: 'Kuzey Makedonya\'da 2026 ortalama maaş nedir? Devlet İstatistik Ofisine göre ortalama brüt maaş ~69.141 MKD, net hesaplama, etkileyen faktörler ve asgari ücretle karşılaştırma.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Ресурси',
    tag: 'Референца',
    title: 'Просечна плата во Македонија 2026 — бруто и нето',
    publishDate: '20 август 2026',
    readTime: '7 мин читање',
    intro:
      'Просечната плата е една од најбараните економски бројки во Македонија — важна за работодавачи кои поставуваат платни политики, за вработени кои проценуваат дали примањето е конкурентно и за аналитичари кои го следат стандардот на живот. Во овој водич објаснуваме што точно значи „просечна плата“, колку изнесува во 2026 според Државниот завод за статистика (ДЗС), како да ја пресметате нето од бруто и што влијае на нејзината висина.',
    sections: [
      {
        title: 'Што значи „просечна плата“: бруто наспроти нето',
        content:
          'Кога се зборува за просечна плата, важно е да се разграничи дали станува збор за бруто или нето износ — разликата е значителна. Бруто платата е вкупниот трошок пред одбитоци и ги вклучува социјалните придонеси (пензиско, здравствено, вработување) и данокот на личен доход. Нето платата е она што вработениот реално го добива на сметка, по одбивање на сите придонеси и данок. Државниот завод за статистика редовно објавува податоци и за просечна бруто и за просечна нето плата, па секогаш проверете кој податок се цитира.',
        items: null,
        steps: null,
      },
      {
        title: 'Колку изнесува просечната плата во 2026',
        content:
          'Според Државниот завод за статистика (последни податоци), просечната месечна БРУТО плата во Македонија изнесува приближно 69.141 МКД. Просечната НЕТО плата е пониска — по одбивање на придонесите (28%) и данокот на доход (10%) на просечно ниво, ориентационо се движи околу 47.000 МКД (приближен износ, зависен од личното ослободување и заокружувања). Овие бројки не се фиксни: ДЗС ги ажурира периодично (месечно и годишно), а просекот расте со текот на времето. За најнов податок секогаш проверете на официјалната страница на ДЗС (stat.gov.mk).',
        items: [
          'Просечна бруто плата: ~69.141 МКД (според ДЗС, последни податоци)',
          'Просечна нето плата: приближно ~47.000 МКД (проценка по одбивање придонеси и данок)',
          'Извор: Државен завод за статистика (ДЗС) — податоците се ажурираат периодично',
          'Просекот варира по дејност, регион и период — националната бројка е само референца',
        ],
        steps: null,
      },
      {
        title: 'Од бруто до нето: како се пресметува',
        content:
          'Нето износот се добива со одбивање на задолжителните придонеси и данокот од бруто платата. Еве ги чекорите применети на просечната бруто плата од 69.141 МКД:',
        items: null,
        steps: [
          { step: 'Тргнете од бруто платата', desc: 'Основицата за пресметка е бруто платата — во нашиот пример 69.141 МКД.' },
          { step: 'Одбијте ги придонесите (28%)', desc: 'Од бруто се одбиваат: пензиско и инвалидско 18,8%, здравствено 7,5%, вработување 1,2% и дополнителен придонес 0,5% — вкупно 28%. Тоа изнесува околу 19.360 МКД.' },
          { step: 'Пресметајте ја даночната основица', desc: 'Од износот по придонеси (69.141 − 19.360 ≈ 49.781 МКД) одбијте го личното ослободување од 10.932 МКД месечно. Даночна основица ≈ 38.849 МКД.' },
          { step: 'Одбијте данок на личен доход (10%)', desc: 'Данокот е 10% од основицата: 38.849 × 0,10 ≈ 3.885 МКД.' },
          { step: 'Добијте ја нето платата', desc: 'Нето = бруто − придонеси − данок ≈ 69.141 − 19.360 − 3.885 ≈ 45.900 МКД. Точниот износ зависи од заокружувањата за МПИН и од личното ослободување — затоа нето просекот го третираме како приближен.' },
        ],
      },
      {
        title: 'Што влијае на висината на платата',
        content:
          'Националниот просек е збирна бројка — реалната плата варира значително во зависност од повеќе фактори:',
        items: [
          'Дејност (сектор): ИТ, финансии и телекомуникации имаат натпросечни плати, додека угостителството и трговијата на мало често се под просекот.',
          'Регион: Скопскиот регион традиционално има највисоки плати, додека помалите општини се под националниот просек.',
          'Работно искуство и стаж: додаток за минат труд (стаж) и стручноста ја зголемуваат основната плата.',
          'Образование и квалификации: повисоко образование и специјализирани вештини носат повисоки примања.',
          'Големина и тип на работодавач: странски и големи компании обично плаќаат повеќе од микро бизниси.',
          'Работно време и додатоци: прекувремена работа, ноќна смена и работа на празник носат законски додатоци на основната плата.',
        ],
        steps: null,
      },
      {
        title: 'Просечна наспроти минимална плата',
        content:
          'Просечната плата не смее да се меша со минималната — законски загарантираниот минимум што секој работодавач мора да го исплати за полно работно време. За 2026 минималната плата изнесува бруто 38.507 МКД, односно нето 26.046 МКД (приближно 423 EUR). Тоа значи дека просечната бруто плата (~69.141 МКД) е речиси двојно повисока од минималната бруто плата. Разликата покажува колку е широка распределбата на примањата — многу вработени земаат околу или под просекот, што е типична карактеристика на дистрибуцијата на плати.',
        items: [
          'Минимална бруто плата 2026: 38.507 МКД',
          'Минимална нето плата 2026: 26.046 МКД (~423 EUR)',
          'Просечна бруто плата: ~69.141 МКД — приближно 1,8 пати над минималната',
          'Просекот е повисок од медијаната — мал број високи плати го „влечат“ просекот нагоре',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага со плати и МПИН',
        content:
          'Без разлика дали платите на просечно ниво или над него, точната пресметка е законска обврска. Facturino ја автоматизира целата пресметка на плати за македонски бизниси — од бруто до нето со актуелните стапки за 2026, генерирање на МПИН образец спремен за поднесување до УЈП и креирање платни листи усогласени со македонските прописи.',
        items: [
          'Автоматска бруто-до-нето пресметка со актуелните стапки (придонеси 28% + данок 10%)',
          'Автоматско применето лично ослободување и заокружување по МПИН стандард',
          'Генериран МПИН образец спремен за поднесување до УЈП',
          'Платни листи на македонски јазик за секој вработен',
          'Автоматско ажурирање при промена на стапките или минималната плата',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Калкулатор за плата: бруто во нето' },
      { slug: 'minimalna-plata', title: 'Минимална плата во Македонија 2026' },
      { slug: 'danocni-stapki', title: 'Даночни стапки во Македонија' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Пресметка на плата: придонеси и даноци' },
    ],
    bottomCta: {
      title: 'Автоматизирајте ги платите со Facturino',
      subtitle: 'Точна пресметка бруто-до-нето, МПИН образец и платни листи — усогласено со македонските прописи.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Resources',
    tag: 'Reference',
    title: 'Average Salary in North Macedonia 2026 — Gross & Net',
    publishDate: 'August 20, 2026',
    readTime: '7 min read',
    intro:
      'The average salary is one of the most searched economic figures in North Macedonia — important for employers setting pay policies, for employees assessing whether their income is competitive, and for analysts tracking the standard of living. This guide explains what "average salary" actually means, how much it is in 2026 according to the State Statistical Office, how to calculate net from gross, and what affects its level.',
    sections: [
      {
        title: 'What "average salary" means: gross vs net',
        content:
          'When discussing the average salary, it is essential to distinguish whether it refers to a gross or a net amount — the difference is significant. The gross salary is the total cost before deductions and includes social contributions (pension, health, employment) and personal income tax. The net salary is what the employee actually receives, after all contributions and tax are deducted. The State Statistical Office regularly publishes data on both average gross and average net salary, so always check which figure is being cited.',
        items: null,
        steps: null,
      },
      {
        title: 'How much is the average salary in 2026',
        content:
          'According to the State Statistical Office (latest data), the average monthly GROSS salary in North Macedonia is approximately 69,141 MKD. The average NET salary is lower — after deducting contributions (28%) and income tax (10%) at the average level, it is roughly around 47,000 MKD (an approximate figure, dependent on the personal deduction and rounding). These numbers are not fixed: the office updates them periodically (monthly and annually), and the average rises over time. For the latest figure, always check the official State Statistical Office site (stat.gov.mk).',
        items: [
          'Average gross salary: ~69,141 MKD (per the State Statistical Office, latest data)',
          'Average net salary: approximately ~47,000 MKD (estimate after deducting contributions and tax)',
          'Source: State Statistical Office (DZS) — data is updated periodically',
          'The average varies by activity, region, and period — the national figure is a reference only',
        ],
        steps: null,
      },
      {
        title: 'From gross to net: how it is calculated',
        content:
          'The net amount is obtained by deducting mandatory contributions and tax from the gross salary. Here are the steps applied to the average gross salary of 69,141 MKD:',
        items: null,
        steps: [
          { step: 'Start from the gross salary', desc: 'The calculation base is the gross salary — in our example 69,141 MKD.' },
          { step: 'Deduct contributions (28%)', desc: 'From gross, deduct: pension and disability 18.8%, health 7.5%, employment 1.2%, and additional contribution 0.5% — 28% in total. That amounts to roughly 19,360 MKD.' },
          { step: 'Calculate the tax base', desc: 'From the amount after contributions (69,141 − 19,360 ≈ 49,781 MKD), deduct the personal allowance of 10,932 MKD per month. Tax base ≈ 38,849 MKD.' },
          { step: 'Deduct personal income tax (10%)', desc: 'The tax is 10% of the base: 38,849 × 0.10 ≈ 3,885 MKD.' },
          { step: 'Arrive at the net salary', desc: 'Net = gross − contributions − tax ≈ 69,141 − 19,360 − 3,885 ≈ 45,900 MKD. The exact figure depends on MPIN rounding and the personal allowance — which is why we treat the net average as approximate.' },
        ],
      },
      {
        title: 'What affects the salary level',
        content:
          'The national average is an aggregate figure — the actual salary varies significantly depending on several factors:',
        items: [
          'Activity (sector): IT, finance, and telecommunications have above-average salaries, while hospitality and retail are often below average.',
          'Region: The Skopje region traditionally has the highest salaries, while smaller municipalities are below the national average.',
          'Work experience and seniority: seniority bonuses and expertise increase the base salary.',
          'Education and qualifications: higher education and specialized skills bring higher earnings.',
          'Employer size and type: foreign and large companies usually pay more than micro businesses.',
          'Working hours and bonuses: overtime, night shifts, and holiday work carry statutory add-ons to the base salary.',
        ],
        steps: null,
      },
      {
        title: 'Average vs minimum wage',
        content:
          'The average salary should not be confused with the minimum — the legally guaranteed floor every employer must pay for full-time work. For 2026 the minimum wage is gross 38,507 MKD, or net 26,046 MKD (approximately 423 EUR). This means the average gross salary (~69,141 MKD) is nearly double the minimum gross wage. The gap shows how wide the earnings distribution is — many employees earn around or below the average, a typical feature of salary distributions.',
        items: [
          'Minimum gross wage 2026: 38,507 MKD',
          'Minimum net wage 2026: 26,046 MKD (~423 EUR)',
          'Average gross salary: ~69,141 MKD — roughly 1.8 times above the minimum',
          'The average is higher than the median — a small number of high salaries pull the average up',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps with payroll and MPIN',
        content:
          'Whether you pay at the average level or above it, accurate calculation is a legal obligation. Facturino automates the entire payroll calculation for Macedonian businesses — from gross to net with current 2026 rates, generating the MPIN form ready for submission to UJP, and creating pay slips compliant with Macedonian regulations.',
        items: [
          'Automatic gross-to-net calculation with current rates (contributions 28% + tax 10%)',
          'Automatically applied personal allowance and MPIN-standard rounding',
          'Generated MPIN form ready for submission to UJP',
          'Pay slips in Macedonian for each employee',
          'Automatic updates when rates or the minimum wage change',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Salary Calculator: Gross to Net' },
      { slug: 'minimalna-plata', title: 'Minimum Wage in North Macedonia 2026' },
      { slug: 'danocni-stapki', title: 'Tax Rates in North Macedonia' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Payroll Calculation: Contributions and Taxes' },
    ],
    bottomCta: {
      title: 'Automate payroll with Facturino',
      subtitle: 'Accurate gross-to-net calculation, MPIN form, and pay slips — compliant with Macedonian regulations.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Burime',
    tag: 'Referencë',
    title: 'Paga mesatare në Maqedoni 2026 — bruto dhe neto',
    publishDate: '20 gusht 2026',
    readTime: '7 min lexim',
    intro:
      'Paga mesatare është një nga shifrat ekonomike më të kërkuara në Maqedoni — e rëndësishme për punëdhënësit që vendosin politikat e pagave, për punonjësit që vlerësojnë nëse të ardhurat e tyre janë konkurruese dhe për analistët që ndjekin standardin e jetesës. Ky udhëzues shpjegon çfarë do të thotë saktësisht "pagë mesatare", sa është në 2026 sipas Entit Shtetëror të Statistikës, si të llogaritni neton nga bruto dhe çfarë ndikon në nivelin e saj.',
    sections: [
      {
        title: 'Çfarë do të thotë "pagë mesatare": bruto kundrejt neto',
        content:
          'Kur flitet për pagën mesatare, është thelbësore të dallohet nëse bëhet fjalë për shumën bruto apo neto — diferenca është e konsiderueshme. Paga bruto është kostoja totale para zbritjeve dhe përfshin kontributet sociale (pension, shëndetësi, punësim) dhe tatimin mbi të ardhurat personale. Paga neto është ajo që punonjësi merr në të vërtetë, pas zbritjes së të gjitha kontributeve dhe tatimit. Enti Shtetëror i Statistikës publikon rregullisht të dhëna si për pagën mesatare bruto ashtu edhe për atë neto, ndaj kontrolloni gjithmonë cila shifër citohet.',
        items: null,
        steps: null,
      },
      {
        title: 'Sa është paga mesatare në 2026',
        content:
          'Sipas Entit Shtetëror të Statistikës (të dhënat e fundit), paga mesatare mujore BRUTO në Maqedoni është përafërsisht 69.141 MKD. Paga mesatare NETO është më e ulët — pas zbritjes së kontributeve (28%) dhe tatimit mbi të ardhurat (10%) në nivel mesatar, lëviz orientueshëm rreth 47.000 MKD (shifër e përafërt, që varet nga lirimi personal dhe rrumbullakimet). Këto shifra nuk janë fikse: Enti i përditëson periodikisht (mujore dhe vjetore), dhe mesatarja rritet me kalimin e kohës. Për shifrën më të re, kontrolloni gjithmonë faqen zyrtare të Entit Shtetëror të Statistikës (stat.gov.mk).',
        items: [
          'Paga mesatare bruto: ~69.141 MKD (sipas Entit Shtetëror të Statistikës, të dhënat e fundit)',
          'Paga mesatare neto: përafërsisht ~47.000 MKD (vlerësim pas zbritjes së kontributeve dhe tatimit)',
          'Burimi: Enti Shtetëror i Statistikës (DZS) — të dhënat përditësohen periodikisht',
          'Mesatarja ndryshon sipas veprimtarisë, rajonit dhe periudhës — shifra kombëtare është vetëm referencë',
        ],
        steps: null,
      },
      {
        title: 'Nga bruto në neto: si llogaritet',
        content:
          'Shuma neto merret duke zbritur kontributet e detyrueshme dhe tatimin nga paga bruto. Ja hapat e aplikuar mbi pagën mesatare bruto prej 69.141 MKD:',
        items: null,
        steps: [
          { step: 'Nisni nga paga bruto', desc: 'Baza e llogaritjes është paga bruto — në shembullin tonë 69.141 MKD.' },
          { step: 'Zbritni kontributet (28%)', desc: 'Nga bruto zbriten: pension dhe invaliditet 18,8%, shëndetësi 7,5%, punësim 1,2% dhe kontribut shtesë 0,5% — gjithsej 28%. Kjo është rreth 19.360 MKD.' },
          { step: 'Llogaritni bazën tatimore', desc: 'Nga shuma pas kontributeve (69.141 − 19.360 ≈ 49.781 MKD), zbritni lirimin personal prej 10.932 MKD në muaj. Baza tatimore ≈ 38.849 MKD.' },
          { step: 'Zbritni tatimin mbi të ardhurat (10%)', desc: 'Tatimi është 10% e bazës: 38.849 × 0,10 ≈ 3.885 MKD.' },
          { step: 'Merrni pagën neto', desc: 'Neto = bruto − kontribute − tatim ≈ 69.141 − 19.360 − 3.885 ≈ 45.900 MKD. Shifra e saktë varet nga rrumbullakimet për MPIN dhe nga lirimi personal — prandaj mesataren neto e trajtojmë si të përafërt.' },
        ],
      },
      {
        title: 'Çfarë ndikon në nivelin e pagës',
        content:
          'Mesatarja kombëtare është shifër e përgjithshme — paga reale ndryshon ndjeshëm në varësi të disa faktorëve:',
        items: [
          'Veprimtaria (sektori): IT, financat dhe telekomunikacioni kanë paga mbi mesataren, ndërsa mikpritja dhe tregtia me pakicë janë shpesh nën mesatare.',
          'Rajoni: rajoni i Shkupit tradicionalisht ka pagat më të larta, ndërsa komunat më të vogla janë nën mesataren kombëtare.',
          'Përvoja dhe vjetërsia: shtesa për vjetërsi dhe ekspertiza rrisin pagën bazë.',
          'Arsimi dhe kualifikimet: arsimi më i lartë dhe aftësitë e specializuara sjellin të ardhura më të larta.',
          'Madhësia dhe lloji i punëdhënësit: kompanitë e huaja dhe të mëdha zakonisht paguajnë më shumë se mikro bizneset.',
          'Orari i punës dhe shtesat: puna jashtë orarit, turni i natës dhe puna në festa bartin shtesa ligjore mbi pagën bazë.',
        ],
        steps: null,
      },
      {
        title: 'Paga mesatare kundrejt asaj minimale',
        content:
          'Paga mesatare nuk duhet ngatërruar me atë minimale — dyshemeja e garantuar ligjërisht që çdo punëdhënës duhet të paguajë për punë me kohë të plotë. Për 2026 paga minimale është bruto 38.507 MKD, ose neto 26.046 MKD (përafërsisht 423 EUR). Kjo do të thotë se paga mesatare bruto (~69.141 MKD) është pothuajse dyfishi i pagës minimale bruto. Diferenca tregon sa e gjerë është shpërndarja e të ardhurave — shumë punonjës marrin rreth ose nën mesatare, një tipar tipik i shpërndarjes së pagave.',
        items: [
          'Paga minimale bruto 2026: 38.507 MKD',
          'Paga minimale neto 2026: 26.046 MKD (~423 EUR)',
          'Paga mesatare bruto: ~69.141 MKD — përafërsisht 1,8 herë mbi minimalen',
          'Mesatarja është më e lartë se mediana — një numër i vogël pagash të larta e "tërheqin" mesataren lart',
        ],
        steps: null,
      },
      {
        title: 'Si ndihmon Facturino me pagat dhe MPIN',
        content:
          'Pavarësisht nëse paguani në nivel mesatar apo mbi të, llogaritja e saktë është detyrim ligjor. Facturino automatizon të gjithë llogaritjen e pagave për bizneset maqedonase — nga bruto në neto me normat aktuale për 2026, gjenerimin e formularit MPIN gati për dorëzim në UJP dhe krijimin e fletëpagesave në pajtim me rregulloret maqedonase.',
        items: [
          'Llogaritje automatike bruto-në-neto me normat aktuale (kontribute 28% + tatim 10%)',
          'Lirim personal i aplikuar automatikisht dhe rrumbullakim sipas standardit MPIN',
          'Formular MPIN i gjeneruar gati për dorëzim në UJP',
          'Fletëpagesa në maqedonisht për çdo punonjës',
          'Përditësim automatik kur ndryshojnë normat ose paga minimale',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Llogaritësi i pagës: bruto në neto' },
      { slug: 'minimalna-plata', title: 'Paga minimale në Maqedoni 2026' },
      { slug: 'danocni-stapki', title: 'Normat tatimore në Maqedoni' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Llogaritja e pagës: kontributet dhe tatimet' },
    ],
    bottomCta: {
      title: 'Automatizoni pagat me Facturino',
      subtitle: 'Llogaritje e saktë bruto-në-neto, formular MPIN dhe fletëpagesa — në pajtim me rregulloret maqedonase.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Kaynaklar',
    tag: 'Referans',
    title: 'Kuzey Makedonya\'da Ortalama Maaş 2026 — Brüt ve Net',
    publishDate: '20 Ağustos 2026',
    readTime: '7 dk okuma',
    intro:
      'Ortalama maaş, Kuzey Makedonya\'da en çok aranan ekonomik verilerden biridir — ücret politikası belirleyen işverenler, gelirlerinin rekabetçi olup olmadığını değerlendiren çalışanlar ve yaşam standardını izleyen analistler için önemlidir. Bu rehber "ortalama maaş"ın tam olarak ne anlama geldiğini, Devlet İstatistik Ofisine göre 2026\'da ne kadar olduğunu, brütten neti nasıl hesaplayacağınızı ve düzeyini nelerin etkilediğini açıklıyor.',
    sections: [
      {
        title: '"Ortalama maaş" ne demek: brüt ve net',
        content:
          'Ortalama maaştan bahsedilirken, brüt mü yoksa net tutara mı atıfta bulunulduğunu ayırt etmek çok önemlidir — fark önemlidir. Brüt maaş, kesintilerden önceki toplam maliyettir ve sosyal katkıları (emeklilik, sağlık, istihdam) ve kişisel gelir vergisini içerir. Net maaş, tüm katkılar ve vergi kesildikten sonra çalışanın gerçekte aldığı tutardır. Devlet İstatistik Ofisi düzenli olarak hem ortalama brüt hem de ortalama net maaş verilerini yayımlar, bu nedenle hangi rakamın alıntılandığını her zaman kontrol edin.',
        items: null,
        steps: null,
      },
      {
        title: '2026\'da ortalama maaş ne kadar',
        content:
          'Devlet İstatistik Ofisine göre (en son veriler), Kuzey Makedonya\'da aylık ortalama BRÜT maaş yaklaşık 69.141 MKD\'dir. Ortalama NET maaş daha düşüktür — ortalama düzeyde katkılar (%28) ve gelir vergisi (%10) düşüldükten sonra, yaklaşık olarak 47.000 MKD civarındadır (kişisel indirime ve yuvarlamalara bağlı yaklaşık bir rakam). Bu rakamlar sabit değildir: Ofis bunları periyodik olarak (aylık ve yıllık) günceller ve ortalama zamanla artar. En güncel rakam için her zaman Devlet İstatistik Ofisinin resmi sitesini (stat.gov.mk) kontrol edin.',
        items: [
          'Ortalama brüt maaş: ~69.141 MKD (Devlet İstatistik Ofisine göre, en son veriler)',
          'Ortalama net maaş: yaklaşık ~47.000 MKD (katkılar ve vergi düşüldükten sonra tahmin)',
          'Kaynak: Devlet İstatistik Ofisi (DZS) — veriler periyodik olarak güncellenir',
          'Ortalama; faaliyete, bölgeye ve döneme göre değişir — ulusal rakam yalnızca referanstır',
        ],
        steps: null,
      },
      {
        title: 'Brütten nete: nasıl hesaplanır',
        content:
          'Net tutar, brüt maaştan zorunlu katkıların ve verginin düşülmesiyle elde edilir. 69.141 MKD\'lik ortalama brüt maaşa uygulanan adımlar şöyle:',
        items: null,
        steps: [
          { step: 'Brüt maaştan başlayın', desc: 'Hesaplama matrahı brüt maaştır — örneğimizde 69.141 MKD.' },
          { step: 'Katkıları düşün (%28)', desc: 'Brütten düşülür: emeklilik ve malullük %18,8, sağlık %7,5, istihdam %1,2 ve ek katkı %0,5 — toplam %28. Bu yaklaşık 19.360 MKD eder.' },
          { step: 'Vergi matrahını hesaplayın', desc: 'Katkılardan sonraki tutardan (69.141 − 19.360 ≈ 49.781 MKD), aylık 10.932 MKD kişisel indirimi düşün. Vergi matrahı ≈ 38.849 MKD.' },
          { step: 'Kişisel gelir vergisini düşün (%10)', desc: 'Vergi, matrahın %10\'udur: 38.849 × 0,10 ≈ 3.885 MKD.' },
          { step: 'Net maaşa ulaşın', desc: 'Net = brüt − katkılar − vergi ≈ 69.141 − 19.360 − 3.885 ≈ 45.900 MKD. Kesin rakam MPIN yuvarlamalarına ve kişisel indirime bağlıdır — bu nedenle net ortalamayı yaklaşık olarak ele alıyoruz.' },
        ],
      },
      {
        title: 'Maaş düzeyini ne etkiler',
        content:
          'Ulusal ortalama toplu bir rakamdır — gerçek maaş birçok faktöre bağlı olarak önemli ölçüde değişir:',
        items: [
          'Faaliyet (sektör): BT, finans ve telekomünikasyon ortalamanın üzerinde maaşlara sahipken, konaklama ve perakende genellikle ortalamanın altındadır.',
          'Bölge: Üsküp bölgesi geleneksel olarak en yüksek maaşlara sahipken, daha küçük belediyeler ulusal ortalamanın altındadır.',
          'İş deneyimi ve kıdem: kıdem tazminatları ve uzmanlık temel maaşı artırır.',
          'Eğitim ve nitelikler: yüksek eğitim ve uzmanlaşmış beceriler daha yüksek kazanç getirir.',
          'İşveren büyüklüğü ve türü: yabancı ve büyük şirketler genellikle mikro işletmelerden daha fazla öder.',
          'Çalışma saatleri ve ek ödemeler: fazla mesai, gece vardiyası ve tatil çalışması temel maaşa yasal ek ödemeler getirir.',
        ],
        steps: null,
      },
      {
        title: 'Ortalama maaş ile asgari ücret karşılaştırması',
        content:
          'Ortalama maaş, asgari ücretle karıştırılmamalıdır — her işverenin tam zamanlı çalışma için ödemesi gereken yasal olarak garanti edilen alt sınır. 2026 için asgari ücret brüt 38.507 MKD veya net 26.046 MKD\'dir (yaklaşık 423 EUR). Bu, ortalama brüt maaşın (~69.141 MKD) asgari brüt ücretin neredeyse iki katı olduğu anlamına gelir. Fark, gelir dağılımının ne kadar geniş olduğunu gösterir — birçok çalışan ortalama civarında veya altında kazanır, bu maaş dağılımlarının tipik bir özelliğidir.',
        items: [
          'Asgari brüt ücret 2026: 38.507 MKD',
          'Asgari net ücret 2026: 26.046 MKD (~423 EUR)',
          'Ortalama brüt maaş: ~69.141 MKD — asgarinin yaklaşık 1,8 katı üzerinde',
          'Ortalama medyandan yüksektir — az sayıda yüksek maaş ortalamayı yukarı çeker',
        ],
        steps: null,
      },
      {
        title: 'Facturino bordro ve MPIN\'de nasıl yardımcı olur',
        content:
          'İster ortalama düzeyde ister üzerinde ödeme yapın, doğru hesaplama yasal bir yükümlülüktür. Facturino, Makedon işletmeleri için tüm bordro hesaplamasını otomatikleştirir — güncel 2026 oranlarıyla brütten nete, UJP\'ye gönderime hazır MPIN formu oluşturma ve Makedon düzenlemelerine uygun bordro belgeleri hazırlama.',
        items: [
          'Güncel oranlarla otomatik brütten nete hesaplama (katkılar %28 + vergi %10)',
          'Otomatik uygulanan kişisel indirim ve MPIN standardına göre yuvarlama',
          'UJP\'ye gönderime hazır oluşturulmuş MPIN formu',
          'Her çalışan için Makedonca bordro belgeleri',
          'Oranlar veya asgari ücret değiştiğinde otomatik güncelleme',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'alati/plata-kalkulator', title: 'Maaş hesaplayıcı: brütten nete' },
      { slug: 'minimalna-plata', title: 'Kuzey Makedonya\'da asgari ücret 2026' },
      { slug: 'danocni-stapki', title: 'Kuzey Makedonya\'da vergi oranları' },
      { slug: 'blog/presmetka-na-plata-mk', title: 'Bordro hesaplama: katkılar ve vergiler' },
    ],
    bottomCta: {
      title: 'Facturino ile bordroyu otomatikleştirin',
      subtitle: 'Doğru brütten nete hesaplama, MPIN formu ve bordro belgeleri — Makedon düzenlemelerine uygun.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function ProsecnaPlataPage({
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
    { name: t.title, href: `/${locale}/prosecna-plata` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Колку изнесува просечната плата во Македонија во 2026?', answer: 'Според Државниот завод за статистика (последни податоци), просечната месечна бруто плата изнесува приближно 69.141 МКД. Просечната нето плата е пониска — ориентационо околу 47.000 МКД по одбивање на придонесите (28%) и данокот на доход (10%).' },
        { question: 'Дали просечната плата е бруто или нето?', answer: 'ДЗС објавува и просечна бруто и просечна нето плата. Бруто платата е вкупниот трошок пред одбитоци, а нето платата е она што вработениот го добива на сметка по одбивање на придонесите и данокот. Секогаш проверете кој податок се цитира.' },
        { question: 'Која е разликата помеѓу просечната и минималната плата?', answer: 'Просечната бруто плата (~69.141 МКД) е речиси двојно повисока од минималната бруто плата за 2026 (38.507 МКД бруто, односно 26.046 МКД нето). Минималната плата е законски загарантиран минимум, додека просечната е статистички просек на сите плати.' },
        { question: 'Како да пресметам нето од бруто плата?', answer: 'Од бруто платата одбијте ги придонесите од 28% (пензиско 18,8%, здравствено 7,5%, вработување 1,2%, дополнителен 0,5%), потоа одбијте го личното ослободување од 10.932 МКД за да ја добиете даночната основица, и на неа примените данок од 10%. Нето = бруто − придонеси − данок.' },
        { question: 'Дали просечната плата се менува?', answer: 'Да. Државниот завод за статистика ги ажурира податоците периодично (месечно и годишно) и просекот расте со текот на времето. За најнов податок проверете на stat.gov.mk.' },
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
