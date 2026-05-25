import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/bruto-neto-kalkulator-2026', {
    title: {
      mk: 'Бруто нето калкулатор 2026 — Бесплатна пресметка на плата',
      en: 'North Macedonia Gross to Net Salary Calculator 2026 — Free Tool',
      sq: 'Llogaritësi bruto neto 2026 — Mjeti falas për llogaritjen e pagës',
      tr: 'Brüt Net Maaş Hesaplayıcı 2026 — Kuzey Makedonya Ücretsiz Araç',
    },
    description: {
      mk: 'Бесплатен бруто нето калкулатор за 2026: пензиско 18.8%, здравствено 7.5%, данок 10%. Пресметајте ја нето платата од бруто за неколку секунди. Пример со 40.000 МКД бруто.',
      en: 'Free gross to net salary calculator for North Macedonia 2026: pension 18.8%, health 7.5%, income tax 10%. Calculate your net salary from gross in seconds. Example with 40,000 MKD gross.',
      sq: 'Llogaritës falas bruto neto për Maqedoninë 2026: pension 18.8%, shëndetësi 7.5%, tatim 10%. Llogaritni pagën neto nga bruto në sekonda. Shembull me 40.000 MKD bruto.',
      tr: 'Kuzey Makedonya 2026 brüt net maaş hesaplayıcı: emeklilik %18,8, sağlık %7,5, gelir vergisi %10. Brütten net maaşınızı saniyeler içinde hesaplayın. 40.000 MKD brüt örneği.',
    },
    datePublished: '2026-05-22',
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Алатка',
    title: 'Бруто нето калкулатор 2026',
    publishDate: '22 мај 2026',
    readTime: '5 мин читање',
    intro:
      'Колку пари ќе добиете на сметка од бруто платата? Со нашиот бесплатен бруто нето калкулатор за 2026 година, пресметајте ја нето платата за неколку секунди — со актуелните стапки на придонеси и данок за Македонија.',
    calculatorCta: 'Отвори го калкулаторот',
    sections: [
      {
        title: 'Како работи калкулаторот',
        content:
          'Внесете ја бруто платата и калкулаторот автоматски ги пресметува сите задолжителни придонеси (пензиско, здравствено, вработување) и данокот на личен доход. Резултатот е точната нето плата — износот што го добивате на банковната сметка. Калкулаторот ги користи официјалните стапки за 2026 година.',
        items: null,
        steps: null,
      },
      {
        title: 'Стапки на придонеси и данок за 2026',
        content:
          'Во Македонија, сите придонеси се одземаат од бруто платата (ги плаќа вработениот). Вкупните одбитоци изнесуваат 34,75% од бруто платата.',
        items: null,
        steps: [
          {
            step: 'Пензиско осигурување — 18,8%',
            desc: 'Најголемиот придонес. Го финансира пензискиот фонд (ПИОМ). Минимална основица: 50% од просечна плата. Максимална: 16 просечни плати.',
          },
          {
            step: 'Здравствено осигурување — 7,5%',
            desc: 'Придонес за Фондот за здравствено осигурување. Обезбедува здравствена заштита за вработениот и семејството.',
          },
          {
            step: 'Придонес за вработување — 1,2%',
            desc: 'Придонес за Агенцијата за вработување. Обезбедува право на надоместок при невработеност.',
          },
          {
            step: 'Данок на личен доход — 10%',
            desc: 'Рамна стапка од 10% на основицата (бруто минус придонеси). Се пресметува на: бруто - 27,5% = даночна основица.',
          },
        ],
      },
      {
        title: 'Пример: 40.000 МКД бруто',
        content:
          'Еве како се пресметува нето платата од 40.000 МКД бруто:',
        items: [
          'Бруто плата: 40.000 МКД',
          'Пензиско (18,8%): 40.000 x 0,188 = 7.520 МКД',
          'Здравствено (7,5%): 40.000 x 0,075 = 3.000 МКД',
          'Вработување (1,2%): 40.000 x 0,012 = 480 МКД',
          'Вкупно придонеси: 11.000 МКД',
          'Даночна основица: 40.000 - 11.000 = 29.000 МКД',
          'Данок (10%): 29.000 x 0,10 = 2.900 МКД',
          'Нето плата: 40.000 - 11.000 - 2.900 = 26.100 МКД',
        ],
        steps: null,
      },
      {
        title: 'Минимална плата 2026',
        content:
          'Минималната нето плата за 2026 година во Македонија изнесува 26.046 МКД (приближно 423 EUR). Секој работодавач е должен да исплати најмалку минимална плата за полно работно време (40 часа неделно). Минималната плата се ажурира еднаш годишно, обично во март.',
        items: null,
        steps: null,
      },
      {
        title: 'Трошок за работодавач vs. нето плата',
        content:
          'Во Македонија, работодавачот нема дополнителни придонеси над бруто платата. Тоа значи дека вкупниот трошок на работодавачот е еднаков на бруто платата. Сите придонеси (27,5%) и данокот (10% на основицата) се одземаат од бруто платата на вработениот.',
        items: [
          'Трошок за работодавач = Бруто плата',
          'Бруто 40.000 МКД → Нето 26.100 МКД (вработениот добива 65,25%)',
          'Разлика: 13.900 МКД оди за придонеси и данок',
          'Нема скриени трошоци за работодавачот над бруто платата',
        ],
        steps: null,
      },
      {
        title: 'Пресметајте ја платата со Facturino',
        content:
          'Наместо рачна пресметка, користете го нашиот бесплатен онлајн калкулатор за моментална бруто-нето пресметка. Калкулаторот ги користи актуелните стапки за 2026 и дава точен резултат за неколку секунди.',
        items: [
          'Бесплатен — без регистрација, без ограничувања',
          'Актуелни стапки за 2026 година',
          'Пресметка во реално време додека пишувате',
          'Детален преглед: придонеси, данок, нето плата',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата: Комплетен водич' },
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
      { slug: 'personalen-danok-na-dohod', title: 'Персонален данок на доход во Македонија' },
      { slug: 'trudovo-pravo-osnovi', title: 'Трудово право: 10 работи за работодавачи' },
      { slug: 'mpin-registracija-2026', title: 'МПИН регистрација 2026' },
    ],
    cta: {
      title: 'Пресметајте ја платата веднаш',
      desc: 'Бесплатен бруто-нето калкулатор со актуелни стапки за 2026 година. Без регистрација.',
      button: 'Отвори калкулатор',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Tool',
    title: 'North Macedonia Gross to Net Salary Calculator 2026',
    publishDate: 'May 22, 2026',
    readTime: '5 min read',
    intro:
      'How much will you take home from your gross salary? With our free gross to net calculator for 2026, calculate your net salary in seconds — using the current contribution and tax rates for North Macedonia.',
    calculatorCta: 'Open the calculator',
    sections: [
      {
        title: 'How the calculator works',
        content:
          'Enter your gross salary and the calculator automatically computes all mandatory contributions (pension, health, employment) and personal income tax. The result is your exact net salary — the amount you receive in your bank account. The calculator uses the official 2026 rates.',
        items: null,
        steps: null,
      },
      {
        title: '2026 contribution and tax rates',
        content:
          'In North Macedonia, all contributions are deducted from the gross salary (paid by the employee). Total deductions amount to 34.75% of the gross salary.',
        items: null,
        steps: [
          {
            step: 'Pension insurance — 18.8%',
            desc: 'The largest contribution. Funds the pension fund (PIOM). Minimum base: 50% of the average salary. Maximum: 16 average salaries.',
          },
          {
            step: 'Health insurance — 7.5%',
            desc: 'Contribution to the Health Insurance Fund. Provides healthcare coverage for the employee and family members.',
          },
          {
            step: 'Employment contribution — 1.2%',
            desc: 'Contribution to the Employment Agency. Provides the right to unemployment benefits.',
          },
          {
            step: 'Personal income tax — 10%',
            desc: 'Flat rate of 10% on the tax base (gross minus contributions). Calculated on: gross - 27.5% = tax base.',
          },
        ],
      },
      {
        title: 'Example: 40,000 MKD gross',
        content:
          'Here is how the net salary is calculated from 40,000 MKD gross:',
        items: [
          'Gross salary: 40,000 MKD',
          'Pension (18.8%): 40,000 x 0.188 = 7,520 MKD',
          'Health (7.5%): 40,000 x 0.075 = 3,000 MKD',
          'Employment (1.2%): 40,000 x 0.012 = 480 MKD',
          'Total contributions: 11,000 MKD',
          'Tax base: 40,000 - 11,000 = 29,000 MKD',
          'Income tax (10%): 29,000 x 0.10 = 2,900 MKD',
          'Net salary: 40,000 - 11,000 - 2,900 = 26,100 MKD',
        ],
        steps: null,
      },
      {
        title: 'Minimum wage 2026',
        content:
          'The minimum net wage for 2026 in North Macedonia is 26,046 MKD (approximately 423 EUR). Every employer must pay at least the minimum wage for full-time employment (40 hours per week). The minimum wage is updated annually, usually in March.',
        items: null,
        steps: null,
      },
      {
        title: 'Employer cost vs. net salary',
        content:
          'In North Macedonia, the employer has no additional contributions on top of the gross salary. This means the total employer cost equals the gross salary. All contributions (27.5%) and tax (10% on the base) are deducted from the employee\'s gross salary.',
        items: [
          'Employer cost = Gross salary',
          'Gross 40,000 MKD → Net 26,100 MKD (employee receives 65.25%)',
          'Difference: 13,900 MKD goes to contributions and tax',
          'No hidden costs for the employer above the gross salary',
        ],
        steps: null,
      },
      {
        title: 'Calculate your salary with Facturino',
        content:
          'Instead of manual calculation, use our free online calculator for instant gross-to-net conversion. The calculator uses the current 2026 rates and gives you an accurate result in seconds.',
        items: [
          'Free — no registration, no limits',
          'Current 2026 rates',
          'Real-time calculation as you type',
          'Detailed breakdown: contributions, tax, net salary',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Payroll Calculation: Complete Guide' },
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
      { slug: 'personalen-danok-na-dohod', title: 'Personal Income Tax in Macedonia' },
      { slug: 'trudovo-pravo-osnovi', title: 'Labor Law: 10 Things Every Employer Must Know' },
      { slug: 'mpin-registracija-2026', title: 'MPIN Registration 2026' },
    ],
    cta: {
      title: 'Calculate your salary now',
      desc: 'Free gross-to-net calculator with current 2026 rates. No registration required.',
      button: 'Open calculator',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Mjet',
    title: 'Llogaritësi bruto neto 2026 — Maqedonia e Veriut',
    publishDate: '22 maj 2026',
    readTime: '5 min lexim',
    intro:
      'Sa para do merrni në llogari nga paga bruto? Me llogaritësin tonë falas bruto neto për vitin 2026, llogaritni pagën neto në sekonda — me normat aktuale të kontributeve dhe tatimeve për Maqedoninë.',
    calculatorCta: 'Hap llogaritësin',
    sections: [
      {
        title: 'Si funksionon llogaritësi',
        content:
          'Shkruani pagën bruto dhe llogaritësi automatikisht i llogarit të gjitha kontributet e detyrueshme (pension, shëndetësi, punësim) dhe tatimin mbi të ardhurat personale. Rezultati është paga juaj e saktë neto — shuma që merrni në llogarinë bankare. Llogaritësi përdor normat zyrtare të 2026.',
        items: null,
        steps: null,
      },
      {
        title: 'Normat e kontributeve dhe tatimeve 2026',
        content:
          'Në Maqedoni, të gjitha kontributet zbriten nga paga bruto (paguhen nga punonjësi). Zbritjet totale arrijnë 34,75% të pagës bruto.',
        items: null,
        steps: [
          {
            step: 'Sigurimi pensional — 18,8%',
            desc: 'Kontributi më i madh. Financon fondin e pensioneve (PIOM). Baza minimale: 50% e pagës mesatare. Maksimale: 16 paga mesatare.',
          },
          {
            step: 'Sigurimi shëndetësor — 7,5%',
            desc: 'Kontribut për Fondin e Sigurimit Shëndetësor. Siguron mbulim shëndetësor për punonjësin dhe familjen.',
          },
          {
            step: 'Kontributi i punësimit — 1,2%',
            desc: 'Kontribut për Agjencinë e Punësimit. Siguron të drejtën e kompensimit të papunësisë.',
          },
          {
            step: 'Tatimi mbi të ardhurat personale — 10%',
            desc: 'Normë e sheshtë prej 10% mbi bazën tatimore (bruto minus kontributet). Llogaritet: bruto - 27,5% = baza tatimore.',
          },
        ],
      },
      {
        title: 'Shembull: 40.000 MKD bruto',
        content:
          'Ja si llogaritet paga neto nga 40.000 MKD bruto:',
        items: [
          'Paga bruto: 40.000 MKD',
          'Pension (18,8%): 40.000 x 0,188 = 7.520 MKD',
          'Shëndetësi (7,5%): 40.000 x 0,075 = 3.000 MKD',
          'Punësim (1,2%): 40.000 x 0,012 = 480 MKD',
          'Kontribute gjithsej: 11.000 MKD',
          'Baza tatimore: 40.000 - 11.000 = 29.000 MKD',
          'Tatim (10%): 29.000 x 0,10 = 2.900 MKD',
          'Paga neto: 40.000 - 11.000 - 2.900 = 26.100 MKD',
        ],
        steps: null,
      },
      {
        title: 'Paga minimale 2026',
        content:
          'Paga minimale neto për vitin 2026 në Maqedoni është 26.046 MKD (përafërsisht 423 EUR). Çdo punëdhënës duhet të paguajë të paktën pagën minimale për punësim me kohë të plotë (40 orë në javë). Paga minimale përditësohet çdo vit, zakonisht në mars.',
        items: null,
        steps: null,
      },
      {
        title: 'Kosto e punëdhënësit vs. paga neto',
        content:
          'Në Maqedoni, punëdhënësi nuk ka kontribute shtesë mbi pagën bruto. Kjo do të thotë se kosto totale e punëdhënësit është e barabartë me pagën bruto. Të gjitha kontributet (27,5%) dhe tatimi (10% mbi bazën) zbriten nga paga bruto e punonjësit.',
        items: [
          'Kosto e punëdhënësit = Paga bruto',
          'Bruto 40.000 MKD → Neto 26.100 MKD (punonjësi merr 65,25%)',
          'Diferenca: 13.900 MKD shkon për kontribute dhe tatim',
          'Asnjë kosto e fshehur për punëdhënësin mbi pagën bruto',
        ],
        steps: null,
      },
      {
        title: 'Llogaritni pagën me Facturino',
        content:
          'Në vend të llogaritjes manuale, përdorni llogaritësin tonë falas online për konvertim të menjëhershëm bruto-neto. Llogaritësi përdor normat aktuale të 2026 dhe jep rezultat të saktë në sekonda.',
        items: [
          'Falas — pa regjistrim, pa kufizime',
          'Normat aktuale të 2026',
          'Llogaritje në kohë reale ndërsa shkruani',
          'Ndarje e detajuar: kontribute, tatim, paga neto',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagave: Udhezues i plote' },
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhezues per llogaritjen mujore' },
      { slug: 'personalen-danok-na-dohod', title: 'Tatimi personal mbi te ardhurat ne Maqedoni' },
      { slug: 'trudovo-pravo-osnovi', title: 'E drejta e punes: 10 gjera qe cdo punedhenes duhet t\'i dije' },
      { slug: 'mpin-registracija-2026', title: 'Regjistrimi MPIN 2026' },
    ],
    cta: {
      title: 'Llogaritni pagen tuaj tani',
      desc: 'Llogarites falas bruto-neto me normat aktuale te 2026. Pa regjistrim.',
      button: 'Hap llogaritesin',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Arac',
    title: 'Brut Net Maas Hesaplayici 2026 — Kuzey Makedonya',
    publishDate: '22 Mayis 2026',
    readTime: '5 dk okuma',
    intro:
      'Brut maasinizdan elinize ne kadar gececek? 2026 yili icin ucretsiz brut net hesaplayicimizla net maasinizi saniyeler icinde hesaplayin — Kuzey Makedonya\'nin guncel katki ve vergi oranlariyla.',
    calculatorCta: 'Hesaplayiciyi ac',
    sections: [
      {
        title: 'Hesaplayici nasil calisiyor',
        content:
          'Brut maasinizi girin ve hesaplayici tum zorunlu katkilari (emeklilik, saglik, istihdam) ve kisisel gelir vergisini otomatik olarak hesaplar. Sonuc, tam net maasinizdir — banka hesabiniza yatan tutar. Hesaplayici resmi 2026 oranlarini kullanir.',
        items: null,
        steps: null,
      },
      {
        title: '2026 katki ve vergi oranlari',
        content:
          'Kuzey Makedonya\'da tum katkilar brut maastan dusulur (calisan tarafindan odenir). Toplam kesintiler brut maasin %34,75\'ini olusturur.',
        items: null,
        steps: [
          {
            step: 'Emeklilik sigortasi — %18,8',
            desc: 'En buyuk katki. Emeklilik fonunu (PIOM) finanse eder. Minimum matrah: ortalama maasin %50\'si. Maksimum: 16 ortalama maas.',
          },
          {
            step: 'Saglik sigortasi — %7,5',
            desc: 'Saglik Sigortasi Fonuna katki. Calisan ve aile uyeleri icin saglik hizmeti saglar.',
          },
          {
            step: 'Istihdam katkisi — %1,2',
            desc: 'Istihdam Ajansina katki. Issizlik odenegi hakki saglar.',
          },
          {
            step: 'Kisisel gelir vergisi — %10',
            desc: 'Vergi matrahi uzerinden %10 duz oran (brut eksi katkilar). Hesaplanir: brut - %27,5 = vergi matrahi.',
          },
        ],
      },
      {
        title: 'Ornek: 40.000 MKD brut',
        content:
          '40.000 MKD brut maastan net maas su sekilde hesaplanir:',
        items: [
          'Brut maas: 40.000 MKD',
          'Emeklilik (%18,8): 40.000 x 0,188 = 7.520 MKD',
          'Saglik (%7,5): 40.000 x 0,075 = 3.000 MKD',
          'Istihdam (%1,2): 40.000 x 0,012 = 480 MKD',
          'Toplam katkilar: 11.000 MKD',
          'Vergi matrahi: 40.000 - 11.000 = 29.000 MKD',
          'Gelir vergisi (%10): 29.000 x 0,10 = 2.900 MKD',
          'Net maas: 40.000 - 11.000 - 2.900 = 26.100 MKD',
        ],
        steps: null,
      },
      {
        title: 'Asgari ucret 2026',
        content:
          'Kuzey Makedonya\'da 2026 yili icin asgari net ucret 26.046 MKD\'dir (yaklasik 423 EUR). Her isveren tam zamanli istihdam (haftada 40 saat) icin en az asgari ucreti odemek zorundadir. Asgari ucret yillik olarak, genellikle mart ayinda guncellenir.',
        items: null,
        steps: null,
      },
      {
        title: 'Isveren maliyeti vs. net maas',
        content:
          'Kuzey Makedonya\'da isverenin brut maas uzerinde ek katkisi yoktur. Bu, toplam isveren maliyetinin brut maasa esit oldugu anlamina gelir. Tum katkilar (%27,5) ve vergi (matrah uzerinden %10) calisanin brut maasindan dusulur.',
        items: [
          'Isveren maliyeti = Brut maas',
          'Brut 40.000 MKD → Net 26.100 MKD (calisan %65,25 alir)',
          'Fark: 13.900 MKD katkilar ve vergiye gider',
          'Brut maasin uzerinde isveren icin gizli maliyet yok',
        ],
        steps: null,
      },
      {
        title: 'Maasinizi Facturino ile hesaplayin',
        content:
          'Manuel hesaplama yerine, aninda brut-net donusumu icin ucretsiz online hesaplayicimizi kullanin. Hesaplayici guncel 2026 oranlarini kullanir ve saniyeler icinde dogru sonuc verir.',
        items: [
          'Ucretsiz — kayit gerektirmez, sinir yok',
          'Guncel 2026 oranlari',
          'Yazarken gercek zamanli hesaplama',
          'Detayli doküm: katkilar, vergi, net maas',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili makaleler',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Maas hesaplama: Tam rehber' },
      { slug: 'mpin-obrazec', title: 'MPIN formu: Aylik hesaplama rehberi' },
      { slug: 'personalen-danok-na-dohod', title: 'Makedonya\'da kisisel gelir vergisi' },
      { slug: 'trudovo-pravo-osnovi', title: 'Is hukuku: Her isverenin bilmesi gereken 10 sey' },
      { slug: 'mpin-registracija-2026', title: 'MPIN kaydı 2026' },
    ],
    cta: {
      title: 'Maasinizi simdi hesaplayin',
      desc: 'Guncel 2026 oranlariyla ucretsiz brut-net hesaplayici. Kayit gerektirmez.',
      button: 'Hesaplayiciyi ac',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function BrutoNetoKalkulatorPage({
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
    slug: 'bruto-neto-kalkulator-2026',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-22',
    tags: ['bruto neto kalkulator', 'gross to net', 'salary calculator', 'north macedonia', '2026', 'бруто нето калкулатор'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/bruto-neto-kalkulator-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Колку е нето платата од 40.000 МКД бруто?', answer: 'Од 40.000 МКД бруто, нето платата изнесува 26.100 МКД. Одбитоците се: пензиско 7.520 МКД (18,8%), здравствено 3.000 МКД (7,5%), вработување 480 МКД (1,2%) и данок 2.900 МКД (10% на основицата).' },
        { question: 'Колку се стапките на придонеси во Македонија за 2026?', answer: 'Вкупните придонеси изнесуваат 27,5% од бруто платата: пензиско осигурување 18,8%, здравствено осигурување 7,5% и придонес за вработување 1,2%. Дополнително, данокот на личен доход е 10% на основицата (бруто минус придонеси).' },
        { question: 'Колку е личното ослободување од данок?', answer: 'Во Македонија нема класично лично ослободување. Данокот на личен доход од 10% се пресметува на даночната основица (бруто минус 27,5% придонеси). За вработени со минимална плата, целата пресметка е автоматски усогласена со законските минимуми.' },
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
          {/* Prominent CTA to calculator tool */}
          <div className="mt-8">
            <Link
              href={`/${locale}/alati/plata-kalkulator`}
              className="inline-flex items-center justify-center bg-indigo-600 text-white font-semibold rounded-full px-8 py-4 text-lg shadow-lg hover:bg-indigo-700 hover:shadow-xl hover:-translate-y-0.5 transition-all"
            >
              {t.calculatorCta}
              <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
            </Link>
          </div>
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

      {/* MID-ARTICLE CTA — Link to calculator */}
      <section className="py-8 md:py-12 bg-indigo-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6 text-center">
          <Link
            href={`/${locale}/alati/plata-kalkulator`}
            className="inline-flex items-center justify-center bg-indigo-600 text-white font-semibold rounded-full px-8 py-4 text-lg shadow-lg hover:bg-indigo-700 hover:shadow-xl hover:-translate-y-0.5 transition-all"
          >
            {t.calculatorCta}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </Link>
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
          <Link href={`/${locale}/alati/plata-kalkulator`} className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
            {t.cta.button}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </Link>
        </div>
      </section>
    </main>
  )
}
