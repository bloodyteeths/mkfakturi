import Link from 'next/link'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import PageHero from '@/components/PageHero'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog', {
    title: {
      mk: 'Блог — Facturino',
      sq: 'Blog — Facturino',
      tr: 'Blog — Facturino',
      en: 'Blog — Facturino',
    },
    description: {
      mk: 'Водичи, совети и новости за сметководство во Македонија. Годишна сметка, биланси, AOP ознаки и дигитално сметководство со Facturino.',
      sq: 'Udhëzues, këshilla dhe lajme për kontabilitetin në Maqedoni. Llogaritë vjetore, pasqyrat financiare dhe kontabiliteti dixhital me Facturino.',
      tr: 'Makedonya muhasebesi hakkında rehberler, ipuçları ve haberler. Yıllık hesaplar, mali tablolar ve Facturino ile dijital muhasebe.',
      en: 'Guides, tips and news about accounting in Macedonia. Annual accounts, financial statements, AOP codes and digital accounting with Facturino.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    hero: {
      title: 'Блог',
      subtitle: 'Водичи, совети и новости за сметководство во Македонија',
    },
    articles: [
      {
        slug: 'e-faktura-obvrska-2026',
        title: 'Е-фактура 2026: Кој мора, кога почнува и како да се подготвите',
        excerpt: 'B2G задолжителна од октомври 2026, UBL 2.1 формат, QES потпис — комплетен водич за подготовка.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'mpin-registracija-2026',
        title: 'МПИН регистрација и поднесување 2026: Комплетен водич',
        excerpt: 'Што е МПИН, кој мора да се регистрира, постапка во УЈП, месечни рокови и казни за задоцнување.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'neisplatena-plata-prijavuvanje',
        title: 'Неисплатена плата: Како да пријавите до Инспекторат за труд',
        excerpt: 'Чекор-по-чекор пријава, потребни документи, казни за работодавачот и права на вработениот.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'rok-za-plata-makedonija',
        title: 'Рок за исплата на плата во Македонија: Што вели законот',
        excerpt: 'Чл. 106 ЗРО: рок до 15-ти, прекувремена 40%, ноќна 35%, минимална плата 20.175 МКД и казни.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'ddv-registracija-prag-2026',
        title: 'ДДВ регистрација: Праг, постапка и обврски 2026',
        excerpt: 'Праг 2М МКД, доброволна регистрација, постапка во УЈП, стапки 18%/5%/10%/0% и ДДВ-04 пријава.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'kazni-ujp-2026',
        title: 'Казни од УЈП: Што ве чека ако задоцните со пријавите',
        excerpt: 'Казни по вид на прекршок, дневна камата 0,03%, застареност, жалбен процес и амнестија.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'dooel-vodich-2026',
        title: 'ДООЕЛ во Македонија 2026: Даноци, обврски и годишна сметка',
        excerpt: '10% данок на добивка, ДДВ при >2М МКД, МПИН, годишна сметка и споредба со ДОО и паушалец.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'registracija-firma-cekor-po-cekor',
        title: 'Регистрација на фирма 2026: Чекор-по-чекор чеклиста',
        excerpt: 'ДООЕЛ vs ДОО vs паушалец, ЦРСМ регистрација, ЕДБ, МПИН, ДДВ одлука и прва фактура.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'drzavni-institucii-za-firmi',
        title: 'Државни институции за фирми: УЈП, ЦРСМ, Инспекторат — кој е кој',
        excerpt: 'Водич низ сите државни институции со кои фирмата комуницира: УЈП, ЦРСМ, ФПИОМ, ФЗОМ, АВРМ.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'paket-za-nova-firma',
        title: 'Нова фирма чеклиста: 15 работи за првите 30 дена',
        excerpt: 'Ден 1-5 регистрација, ден 5-10 даноци, ден 10-20 операции, ден 20-30 вработени — комплетна чеклиста.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'smetkovodstvo-za-restorani',
        title: 'Сметководство за ресторани и кафулиња: ДДВ 10%, каса и трошоци',
        excerpt: 'ДДВ 10% vs 18% по Чл. 30-а, фискален уред, food cost, бакшиш и сезонски вработени.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-trgovija',
        title: 'Сметководство за продавница: Залиха, маржа и фискален печатач',
        excerpt: 'WAC/FIFO залиха, трговска маржа, КАП/Нивелација, фискален уред и управување со добавувачи.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-gradeznistvo',
        title: 'Сметководство за градежни фирми: Ситуации, материјали и подизведувачи',
        excerpt: 'Ситуации (progress billing), материјални трошоци, ДДВ 5%/18%, аванси и повеќегодишни проекти.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-it-freelancer',
        title: 'Сметководство за фриленсери и IT компании: Фактури, данок и ДДВ',
        excerpt: 'Мулти-валутно фактурирање, ДДВ 0% на извоз, ДООЕЛ vs паушалец, Upwork/Fiverr приходи.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-transport',
        title: 'Сметководство за транспорт: Гориво, патни налози и ДДВ',
        excerpt: 'Меѓународен транспорт 0% ДДВ, патни налози, дневници, амортизација и трошоци за гориво.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-proizvodstvo',
        title: 'Сметководство за производство: Материјали, калкулации и трошоци',
        excerpt: 'BOM, производствени налози, WAC, кало/растур, калкулација на цена и анализа на варијанси.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-saloni',
        title: 'Сметководство за салони за убавина: Каса, материјали и вработени',
        excerpt: 'Услуга + производ на иста сметка, потрошен материјал, провизии, каса и фискален уред.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'smetkovodstvo-za-zemjodelstvo',
        title: 'Сметководство за земјоделци: Субвенции, ДДВ и ИПАРД',
        excerpt: 'ДДВ 5% за земјоделски производи, ИПАРД грантови, сезонски приход, субвенции и закуп на земја.',
        date: '23 мај 2026',
        tag: 'Сектор',
      },
      {
        slug: 'najdobar-smetkovodstven-softver-2026',
        title: '7 сметководствени софтвери за Македонија 2026: Споредба',
        excerpt: 'Споредба на Facturino, PANTHEON, Accent, Helix и Excel — функции, цени, е-Фактура и МК усогласеност.',
        date: '23 мај 2026',
        tag: 'Споредба',
      },
      {
        slug: 'najdobar-pos-softver-2026',
        title: 'Најдобар POS софтвер за Македонија 2026: Споредба',
        excerpt: 'Споредба на POS системи: Facturino, Vector, Пиксел и други — функции, цени и фискален печатач.',
        date: '23 мај 2026',
        tag: 'Споредба',
      },
      {
        slug: 'najdobar-e-faktura-softver',
        title: 'Најдобар софтвер за е-Фактура 2026: Подготвени за октомври?',
        excerpt: 'Споредба на решенија за е-Фактура: UBL 2.1 усогласеност, QES потпис и интеграција со УЈП.',
        date: '23 мај 2026',
        tag: 'Споредба',
      },
      {
        slug: 'facturino-vs-pantheon',
        title: 'Facturino vs PANTHEON: Што е подобро за мали фирми?',
        excerpt: 'Фер споредба: PANTHEON = моќен но комплексен. Facturino = поедноставен, поевтин, cloud-native.',
        date: '23 мај 2026',
        tag: 'Споредба',
      },
      {
        slug: 'javni-nabavki-fakturiranje',
        title: 'Фактурирање за јавни набавки: Водич за добавувачи',
        excerpt: 'Како да фактурирате на државни институции — е-Фактура за B2G, ЕСЈН портал и рокови на плаќање.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'smetkovodstvo-za-nevladini',
        title: 'Сметководство за невладини и здруженија: Водич 2026',
        excerpt: 'НВО книговодство: грант сметководство, ДДВ ослободување, ЦРСМ извештаи и барања на донатори.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'ifrs-izvesti-mk',
        title: 'МСФИ/IFRS извештаи во Македонија: Кој мора и како',
        excerpt: 'Кои компании мораат МСФИ, разлики со МСС, формат на финансиски извештаи и ревизорски барања.',
        date: '23 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'godishna-danocna-prijava-2026',
        title: 'Завршна сметка 2026: Рокови, документи и чекор-по-чекор водич',
        excerpt: 'Целосен водич за завршна сметка — рок 15 март за ДБ-ВП, потребни документи, биланси и постапка за е-Такс.',
        date: '22 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'faktura-primer-mk',
        title: 'Фактура пример: Како изгледа правилна фактура во Македонија',
        excerpt: '15 задолжителни елементи, комплетен пример со ДДВ и најчести грешки при фактурирање.',
        date: '22 мај 2026',
        tag: 'Водич',
      },
      {
        slug: 'bruto-neto-kalkulator-2026',
        title: 'Бруто нето калкулатор 2026: Пресметај ја платата онлајн',
        excerpt: 'Бесплатен калкулатор за бруто-нето плата — пензиско 18.8%, здравствено 7.5%, данок 10% и пример со 40.000 МКД.',
        date: '22 мај 2026',
        tag: 'Алатка',
      },
      {
        slug: 'ai-skener-dokumenti',
        title: 'AI скенер за документи: Скенирај фактура, добиј книжење за 10 секунди',
        excerpt: 'Како AI Document Hub автоматски ги класифицира, извлекува и книжи вашите фактури, сметки и договори.',
        date: '15 март 2026',
        tag: 'Производ',
      },
      {
        slug: 'ai-bankarski-usoglasuvanje',
        title: 'Банкарско усогласување со AI: од 3 часа на 3 минути',
        excerpt: '4-слојна AI pipeline за автоматско усогласување на банковни изводи со фактури — поддржани сите 9 македонски банки.',
        date: '15 март 2026',
        tag: 'Производ',
      },
      {
        slug: 'kako-da-otvorite-firma-i-pocnete-so-fakturiranje',
        title: 'Отворивте фирма? Еве како да почнете со фактурирање за 1 ден',
        excerpt: 'Од регистрација до прва фактура — чекор-по-чекор водич за нови бизниси во Македонија.',
        date: '15 март 2026',
        tag: 'Водич',
      },
      {
        slug: 'ai-asistent-smetkovodstvo',
        title: 'Прашај го AI: Кој ми должи? Дали сум профитабилен?',
        excerpt: 'AI асистент за сметководство — поставете прашања на македонски и добијте моментални одговори за вашите финансии.',
        date: '15 март 2026',
        tag: 'Производ',
      },
      {
        slug: 'nabavki-i-narachki',
        title: 'Дигитални нарачки за набавка: од барање до прием на стока',
        excerpt: 'Целосен животен циклус на нарачки — креирање, одобрување, испраќање, прием и автоматско книжење.',
        date: '15 март 2026',
        tag: 'Производ',
      },
      {
        slug: 'budzet-i-kontrola-troshoci',
        title: 'Буџетирање за мали фирми: Контролирај ги трошоците пред да те контролираат тие',
        excerpt: 'Практичен водич за буџетирање, центри на трошоци и BI dashboard за мали и средни бизниси.',
        date: '15 март 2026',
        tag: 'Едукација',
      },
      {
        slug: 'godishna-smetka-2025',
        title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ',
        excerpt: 'Сè што треба да знаете за годишната сметка — рокови, обрасци, чекор-по-чекор процес за поднесување до Централниот регистар.',
        date: '15 јануари 2026',
        tag: 'Водич',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Биланс на состојба и биланс на успех: AOP ознаки и структура',
        excerpt: 'Детален преглед на Образец 36 и Образец 37 — структура, AOP ознаки, АКТИВА/ПАСИВА и приходи/расходи за годишна сметка.',
        date: '20 јануари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Годишно затворање на книги: 6 чекори со Facturino',
        excerpt: 'Како Facturino го автоматизира годишното затворање — од преглед до заклучување, со UJP-формат извештаи.',
        date: '25 јануари 2026',
        tag: 'Производ',
      },
      {
        slug: 'sto-e-e-faktura',
        title: 'Што е е-фактура и зошто е задолжителна?',
        excerpt: 'Сè за електронската фактура во Македонија — законска рамка, рокови за имплементација и како да се подготвите.',
        date: '1 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'kako-da-napravite-faktura',
        title: 'Како да направите фактура: Чекор-по-чекор водич',
        excerpt: 'Комплетен водич за креирање фактура — од основни податоци до испраќање и архивирање.',
        date: '2 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'zadolzitelni-elementi-faktura',
        title: 'Задолжителни елементи на фактура во Македонија',
        excerpt: 'Кои елементи мора да ги содржи секоја фактура според Законот за ДДВ и Правилникот за книговодство.',
        date: '3 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'faktura-vs-proforma',
        title: 'Фактура vs профактура: Клучни разлики',
        excerpt: 'Што е профактура, кога се користи и по што се разликува од даночна фактура — практичен преглед.',
        date: '4 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'recurring-invoices-mk',
        title: 'Повторувачки фактури: Автоматизирајте ја наплатата',
        excerpt: 'Како да поставите автоматско фактурирање за месечни услуги и претплати со Facturino.',
        date: '5 февруари 2026',
        tag: 'Производ',
      },
      {
        slug: 'ddv-vodich-mk',
        title: 'ДДВ во Македонија: Целосен водич за 2026',
        excerpt: 'Стапки, регистрација, ДДВ пријава и ослободувања — сè што треба да знаете за данокот на додадена вредност.',
        date: '6 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'danok-na-dobivka',
        title: 'Данок на добивка: Стапки, рокови и пресметка',
        excerpt: 'Како се пресметува данокот на добивка, кои трошоци се признаени и кога се поднесува ДБ.',
        date: '7 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'personalen-danok-na-dohod',
        title: 'Персонален данок на доход во Македонија',
        excerpt: 'Преглед на стапки, ослободувања и обврски за персонален данок на доход за физички лица и вработени.',
        date: '8 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'rokovi-ujp-2026',
        title: 'Даночен календар 2026: Сите рокови за УЈП',
        excerpt: 'Комплетен преглед на сите даночни рокови за 2026 — ДДВ, данок на добивка, МПИН и годишна сметка.',
        date: '9 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'paushalen-danochnik',
        title: 'Паушалец во Македонија: Услови, ограничувања и обврски',
        excerpt: 'Кој може да биде паушалец, колку изнесува паушалниот данок и кои се ограничувањата.',
        date: '10 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'otvoranje-firma-mk',
        title: 'Како да отворите фирма во Македонија: Комплетен водич',
        excerpt: 'Чекор-по-чекор процес за регистрација на ДООЕЛ или ДОО — документи, трошоци и рокови.',
        date: '11 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'smetkovodstvo-za-pocetnici',
        title: 'Сметководство за почетници: Основи што секој бизнис ги знае',
        excerpt: 'Основни сметководствени концепти — билансен метод, конто план, книжења и финансиски извештаи.',
        date: '12 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'upravljanje-so-rashodi',
        title: 'Управување со расходи: 7 совети за мали бизниси',
        excerpt: 'Практични совети за контрола на трошоци, категоризација на расходи и оптимизација на буџетот.',
        date: '13 февруари 2026',
        tag: 'Совети',
      },
      {
        slug: 'cash-flow-mk',
        title: 'Cash Flow: Зошто е позначаен од профитот',
        excerpt: 'Зошто готовинскиот тек е клучен за преживување на бизнисот и како да го подобрите.',
        date: '14 февруари 2026',
        tag: 'Совети',
      },
      {
        slug: 'digitalno-smetkovodstvo',
        title: 'Дигитално vs традиционално сметководство',
        excerpt: 'Предности на дигитално сметководство — автоматизација, точност, достапност и заштеда на време.',
        date: '15 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'presmetka-na-plata-mk',
        title: 'Пресметка на плата во Македонија: Придонеси и даноци',
        excerpt: 'Детален преглед на бруто/нето плата, задолжителни придонеси и персонален данок на доход.',
        date: '16 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'mpin-obrazec',
        title: 'МПИН образец: Водич за месечна пресметка',
        excerpt: 'Како правилно да го пополните МПИН образецот — полиња, рокови и најчести грешки.',
        date: '17 февруари 2026',
        tag: 'Водич',
      },
      {
        slug: 'trudovo-pravo-osnovi',
        title: 'Трудово право: 10 работи што секој работодавач мора да ги знае',
        excerpt: 'Основни обврски на работодавачот — договори, работно време, одмори, отказ и заштита на работници.',
        date: '18 февруари 2026',
        tag: 'Едукација',
      },
      {
        slug: 'facturino-vs-excel',
        title: 'Facturino vs Excel: Зошто табели не се доволни',
        excerpt: 'Зошто специјализиран софтвер е подобар од Excel за фактурирање и сметководство.',
        date: '19 февруари 2026',
        tag: 'Производ',
      },
      {
        slug: 'zosto-facturino',
        title: '10 причини зошто македонски бизниси го избираат Facturino',
        excerpt: 'Од локализација до автоматизација — зошто Facturino е најдобриот избор за МК бизниси.',
        date: '20 февруари 2026',
        tag: 'Производ',
      },
      {
        slug: 'za-smetkovoditeli',
        title: 'Зошто сметководителите преминуваат на Facturino',
        excerpt: 'Водич за сметководствени бироа: повеќе клиенти, помалку рачна работа, 20% партнерска провизија.',
        date: '21 февруари 2026',
        tag: 'Водич',
      },
    ],
    readMore: 'Прочитај повеќе',
    bottomCta: {
      title: 'Подготвени? Започнете за 2 минути.',
      subtitle: 'Бесплатен план — без кредитна картичка, без обврска.',
      cta: 'Креирај бесплатна сметка',
    },
  },
  sq: {
    hero: {
      title: 'Blog',
      subtitle: 'Udhëzues, këshilla dhe lajme për kontabilitetin në Maqedoni',
    },
    articles: [
      {
        slug: 'godishna-danocna-prijava-2026',
        title: 'Llogaria vjetore 2026: Afatet, dokumentet dhe udhëzuesi hap pas hapi',
        excerpt: 'Udhëzues i plotë për llogarinë vjetore — afati 15 mars për DB-VP, dokumentet e nevojshme dhe procesi e-Tax.',
        date: '22 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'faktura-primer-mk',
        title: 'Shembull fature: Si duket një faturë e saktë në Maqedoni',
        excerpt: '15 elementet e detyrueshme, shembull i plotë me TVSH dhe gabimet më të shpeshta gjatë faturimit.',
        date: '22 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'bruto-neto-kalkulator-2026',
        title: 'Llogaritësi bruto neto 2026: Llogaritni pagën online',
        excerpt: 'Llogaritës falas bruto-neto — pension 18.8%, shëndetësi 7.5%, tatim 10% dhe shembull me 40,000 MKD.',
        date: '22 maj 2026',
        tag: 'Mjet',
      },
      {
        slug: 'e-faktura-obvrska-2026',
        title: 'E-faturë 2026: Kush duhet, kur fillon dhe si të përgatiteni',
        excerpt: 'Afatet e mandatit të e-faturës, kërkesat teknike UBL 2.1 dhe QES, dhe 6 hapa për përgatitje.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'mpin-registracija-2026',
        title: 'Regjistrimi dhe dorëzimi i MPIN 2026: Udhëzues i plotë',
        excerpt: 'Çfarë është MPIN, kush duhet ta dorëzojë, regjistrimi në UJP, afatet mujore dhe gjobat.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'neisplatena-plata-prijavuvanje',
        title: 'Paga e papaguar: Si të ankoheni te Inspektorati i Punës',
        excerpt: 'Procesi hap pas hapi për raportimin e pagës së papaguar — dokumentet, afatet ligjore dhe gjobat për punëdhënësin.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'rok-za-plata-makedonija',
        title: 'Afati i pagës në Maqedoni: Çfarë thotë ligji',
        excerpt: 'Neni 106 LMP: afati deri më 15, orar shtesë 40%, natë 35%, paga minimale 20.175 MKD dhe gjobat.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'ddv-registracija-prag-2026',
        title: 'Regjistrimi për TVSH: Pragu, procesi dhe detyrimet 2026',
        excerpt: 'Pragu 2M MKD, regjistrimi vullnetar, procesi në DAP, normat 18%/5%/10%/0% dhe deklarata DDV-04.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'kazni-ujp-2026',
        title: 'Gjobat e DAP: Çfarë ju pret nëse vononi me deklaratat',
        excerpt: 'Gjobat sipas llojit të shkeljes, kamata ditore 0,03%, parashkrimi, procesi i ankesës dhe amnistia.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'dooel-vodich-2026',
        title: 'SHPKNJP në Maqedoni 2026: Tatimet, detyrimet dhe llogaria vjetore',
        excerpt: '10% tatim mbi fitimin, TVSH mbi 2M MKD, MPIN, llogaria vjetore dhe krahasimi me SHPK dhe paushalist.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'registracija-firma-cekor-po-cekor',
        title: 'Regjistrimi i firmës 2026: Lista kontrolluese hap pas hapi',
        excerpt: 'SHPKNJP vs SHPK vs paushalist, regjistrimi QRMK, EDB, MPIN, vendimi për TVSH dhe fatura e parë.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'drzavni-institucii-za-firmi',
        title: 'Institucionet shtetërore për firma: DAP, QRMK, Inspektorati — kush është kush',
        excerpt: 'Udhëzues nëpër institucionet shtetërore: DAP, QRMK, FPIOM, FSHM, APRM.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'paket-za-nova-firma',
        title: 'Lista kontrolluese e firmës së re: 15 gjëra për 30 ditët e para',
        excerpt: 'Ditët 1-5 regjistrimi, 5-10 tatimet, 10-20 operacionet, 20-30 punëtorët — lista e plotë.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'smetkovodstvo-za-restorani',
        title: 'Kontabiliteti për restorante dhe kafene: TVSH 10%, arka dhe shpenzime',
        excerpt: 'TVSH 10% vs 18% sipas Nenit 30-a, pajisja fiskale, food cost, bakshish dhe punëtorë sezonal.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-trgovija',
        title: 'Kontabiliteti për dyqane: Stoku, marzhi dhe printeri fiskal',
        excerpt: 'WAC/FIFO stoku, marzhi tregtar, KAP/Nivelacion, pajisja fiskale dhe menaxhimi i furnizuesve.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-gradeznistvo',
        title: 'Kontabiliteti për ndërtim: Faturim progresiv, materiale dhe nënkontraktorë',
        excerpt: 'Faturimi progresiv, shpenzime materialesh, TVSH 5%/18%, avanse dhe projekte shumëvjeçare.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-it-freelancer',
        title: 'Kontabiliteti për freelancerë dhe IT: Fatura, tatim dhe TVSH',
        excerpt: 'Faturim shumëvalutor, TVSH 0% për eksport, SHPKNJP vs paushalist, të ardhura Upwork/Fiverr.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-transport',
        title: 'Kontabiliteti për transport: Karburant, urdhëra udhëtimi dhe TVSH',
        excerpt: 'Transport ndërkombëtar 0% TVSH, urdhëra udhëtimi, dieta, amortizim dhe shpenzime karburanti.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-proizvodstvo',
        title: 'Kontabiliteti për prodhim: Materialet, llogaritjet dhe shpenzimet',
        excerpt: 'BOM, urdhëra prodhimi, WAC, humbje/fire, llogaritja e çmimit dhe analiza e variancave.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-saloni',
        title: 'Kontabiliteti për sallone bukurie: Arka, materiale dhe punëtorë',
        excerpt: 'Shërbim + produkt në të njëjtën faturë, materiale konsumi, komisione, arka dhe pajisja fiskale.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'smetkovodstvo-za-zemjodelstvo',
        title: 'Kontabiliteti për bujqësi: Subvencione, TVSH dhe IPARD',
        excerpt: 'TVSH 5% për produkte bujqësore, grante IPARD, të ardhura sezonale, subvencione dhe qira toke.',
        date: '23 maj 2026',
        tag: 'Sektor',
      },
      {
        slug: 'najdobar-smetkovodstven-softver-2026',
        title: '7 softuerë kontabiliteti për Maqedoninë 2026: Krahasim',
        excerpt: 'Krahasim i Facturino, PANTHEON, Accent, Helix dhe Excel — veçori, çmime, e-Faturë dhe përputhshmëri.',
        date: '23 maj 2026',
        tag: 'Krahasim',
      },
      {
        slug: 'najdobar-pos-softver-2026',
        title: 'Softueri më i mirë POS për Maqedoninë 2026: Krahasim',
        excerpt: 'Krahasim i sistemeve POS: Facturino, Vector, Pixel dhe të tjerë — veçori, çmime dhe printer fiskal.',
        date: '23 maj 2026',
        tag: 'Krahasim',
      },
      {
        slug: 'najdobar-e-faktura-softver',
        title: 'Softueri më i mirë e-Faturë 2026: Gati për tetor?',
        excerpt: 'Krahasim i zgjidhjeve e-Faturë: përputhshmëri UBL 2.1, nënshkrim QES dhe integrim me DAP.',
        date: '23 maj 2026',
        tag: 'Krahasim',
      },
      {
        slug: 'facturino-vs-pantheon',
        title: 'Facturino vs PANTHEON: Cili është më i mirë për bizneset e vogla?',
        excerpt: 'Krahasim i drejtë: PANTHEON = i fuqishëm por kompleks. Facturino = më i thjeshtë, më lirë, cloud-native.',
        date: '23 maj 2026',
        tag: 'Krahasim',
      },
      {
        slug: 'javni-nabavki-fakturiranje',
        title: 'Faturimi për prokurime publike: Udhëzues për furnitorë',
        excerpt: 'Si të faturoni institucionet shtetërore — e-Faturë për B2G, portali ESPP dhe afatet e pagesës.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'smetkovodstvo-za-nevladini',
        title: 'Kontabiliteti për OJQ dhe shoqata: Udhëzues 2026',
        excerpt: 'Kontabilitet OJQ: grante, përjashtim nga TVSH, raporte QRMK dhe kërkesat e donatorëve.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'ifrs-izvesti-mk',
        title: 'Raportet SNRF/IFRS në Maqedoni: Kush duhet dhe si',
        excerpt: 'Cilat kompani duhet SNRF, dallimet me SKK, formati i raporteve financiare dhe kërkesat e auditimit.',
        date: '23 maj 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'ai-skener-dokumenti',
        title: 'AI skaner dokumentesh: Skanoni faturën, merrni regjistrimin për 10 sekonda',
        excerpt: 'Si AI Document Hub klasifikon, nxjerr dhe regjistron automatikisht faturat, faturat dhe kontratat tuaja.',
        date: '15 mars 2026',
        tag: 'Produkt',
      },
      {
        slug: 'ai-bankarski-usoglasuvanje',
        title: 'Pajtimi bankar me AI: nga 3 orë në 3 minuta',
        excerpt: 'Pipeline AI me 4 shtresa për pajtimin automatik të ekstrakteve bankare me faturat — të gjitha 9 bankat maqedonase.',
        date: '15 mars 2026',
        tag: 'Produkt',
      },
      {
        slug: 'kako-da-otvorite-firma-i-pocnete-so-fakturiranje',
        title: 'Hapët firmë? Ja si të filloni me faturim për 1 ditë',
        excerpt: 'Nga regjistrimi te fatura e parë — udhëzues hap pas hapi për bizneset e reja në Maqedoni.',
        date: '15 mars 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'ai-asistent-smetkovodstvo',
        title: 'Pyetni AI: Kush më detyrohet? A jam fitimprurës?',
        excerpt: 'Asistent AI për kontabilitet — bëni pyetje në maqedonisht dhe merrni përgjigje të menjëhershme për financat tuaja.',
        date: '15 mars 2026',
        tag: 'Produkt',
      },
      {
        slug: 'nabavki-i-narachki',
        title: 'Porositë dixhitale të furnizimit: nga kërkesa te pranimi i mallit',
        excerpt: 'Cikli i plotë i jetës së porosive — krijimi, miratimi, dërgimi, pranimi dhe regjistrimi automatik.',
        date: '15 mars 2026',
        tag: 'Produkt',
      },
      {
        slug: 'budzet-i-kontrola-troshoci',
        title: 'Buxhetimi për firma të vogla: Kontrolloni shpenzimet para se t\'ju kontrollojnë ato',
        excerpt: 'Udhëzues praktik për buxhetimin, qendrat e kostove dhe BI dashboard për bizneset e vogla dhe të mesme.',
        date: '15 mars 2026',
        tag: 'Edukim',
      },
      {
        slug: 'godishna-smetka-2025',
        title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim në QRMK',
        excerpt: 'Gjithçka që duhet të dini për llogaritë vjetore — afatet, formularët, procesi hap pas hapi.',
        date: '15 janar 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Bilanci dhe pasqyra e të ardhurave: Kodet AOP dhe struktura',
        excerpt: 'Pasqyrë e detajuar e Formularit 36 dhe 37 — struktura, kodet AOP, aktivet/detyrimet dhe të ardhurat/shpenzimet.',
        date: '20 janar 2026',
        tag: 'Edukim',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Mbyllja e vitit: 6 hapa me Facturino',
        excerpt: 'Si Facturino e automatizon mbylljen e vitit — nga rishikimi deri te kyçja, me raporte në format UJP.',
        date: '25 janar 2026',
        tag: 'Produkt',
      },
      {
        slug: 'sto-e-e-faktura',
        title: 'Çfarë është e-fatura dhe pse është e detyrueshme?',
        excerpt: 'Gjithçka rreth faturës elektronike në Maqedoni — korniza ligjore, afatet dhe si të përgatiteni.',
        date: '1 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'kako-da-napravite-faktura',
        title: 'Si të krijoni një faturë: Udhëzues hap pas hapi',
        excerpt: 'Udhëzues i plotë për krijimin e faturës — nga të dhënat bazë deri te dërgimi dhe arkivimi.',
        date: '2 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'zadolzitelni-elementi-faktura',
        title: 'Elementet e detyrueshme të faturës në Maqedoni',
        excerpt: 'Cilat elemente duhet t\'i përmbajë çdo faturë sipas Ligjit për TVSH-në dhe rregulloreve kontabël.',
        date: '3 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'faktura-vs-proforma',
        title: 'Fatura vs profatura: Dallimet kryesore',
        excerpt: 'Çfarë është profatura, kur përdoret dhe si dallon nga fatura tatimore — pasqyrë praktike.',
        date: '4 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'recurring-invoices-mk',
        title: 'Faturat e përsëritura: Automatizoni arkëtimin',
        excerpt: 'Si të vendosni faturimin automatik për shërbime mujore dhe abonime me Facturino.',
        date: '5 shkurt 2026',
        tag: 'Produkt',
      },
      {
        slug: 'ddv-vodich-mk',
        title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026',
        excerpt: 'Normat, regjistrimi, deklarata e TVSH-së dhe përjashtimet — gjithçka për tatimin mbi vlerën e shtuar.',
        date: '6 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'danok-na-dobivka',
        title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja',
        excerpt: 'Si llogaritet tatimi mbi fitimin, cilat shpenzime njihen dhe kur dorëzohet deklarata.',
        date: '7 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'personalen-danok-na-dohod',
        title: 'Tatimi personal mbi të ardhurat në Maqedoni',
        excerpt: 'Pasqyrë e normave, përjashtimeve dhe detyrimeve për tatimin personal mbi të ardhurat.',
        date: '8 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'rokovi-ujp-2026',
        title: 'Kalendari tatimor 2026: Të gjitha afatet për DAP',
        excerpt: 'Pasqyrë e plotë e afateve tatimore për 2026 — TVSH, tatimi mbi fitimin, MPIN dhe llogaritë vjetore.',
        date: '9 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'paushalen-danochnik',
        title: 'Tatimpaguesi paushall në Maqedoni: Kushtet dhe detyrimet',
        excerpt: 'Kush mund të jetë paushalist, sa është tatimi paushall dhe cilat janë kufizimet.',
        date: '10 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'otvoranje-firma-mk',
        title: 'Si të hapni firmë në Maqedoni: Udhëzues i plotë',
        excerpt: 'Procesi hap pas hapi për regjistrimin e SHPKNJP ose SHPK — dokumentet, kostot dhe afatet.',
        date: '11 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'smetkovodstvo-za-pocetnici',
        title: 'Kontabiliteti për fillestarë: Bazat që çdo biznes i njeh',
        excerpt: 'Konceptet bazë të kontabilitetit — metoda bilancit, plani kontabël, regjistrime dhe pasqyra financiare.',
        date: '12 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'upravljanje-so-rashodi',
        title: 'Menaxhimi i shpenzimeve: 7 këshilla për bizneset e vogla',
        excerpt: 'Këshilla praktike për kontrollin e kostove, kategorizimin e shpenzimeve dhe optimizimin e buxhetit.',
        date: '13 shkurt 2026',
        tag: 'Këshilla',
      },
      {
        slug: 'cash-flow-mk',
        title: 'Cash Flow: Pse është më i rëndësishëm se fitimi',
        excerpt: 'Pse rrjedha e parave është kyçe për mbijetesën e biznesit dhe si ta përmirësoni.',
        date: '14 shkurt 2026',
        tag: 'Këshilla',
      },
      {
        slug: 'digitalno-smetkovodstvo',
        title: 'Kontabiliteti dixhital vs tradicional',
        excerpt: 'Përparësitë e kontabilitetit dixhital — automatizimi, saktësia, aksesueshmëria dhe kursimi i kohës.',
        date: '15 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'presmetka-na-plata-mk',
        title: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet',
        excerpt: 'Pasqyrë e detajuar e pagës bruto/neto, kontributeve të detyrueshme dhe tatimit personal.',
        date: '16 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'mpin-obrazec',
        title: 'Formulari MPIN: Udhëzues për llogaritjen mujore',
        excerpt: 'Si ta plotësoni saktë formularin MPIN — fushat, afatet dhe gabimet më të shpeshta.',
        date: '17 shkurt 2026',
        tag: 'Udhëzues',
      },
      {
        slug: 'trudovo-pravo-osnovi',
        title: 'E drejta e punës: 10 gjëra që çdo punëdhënës duhet t\'i dijë',
        excerpt: 'Detyrimet bazë të punëdhënësit — kontrata, orari, pushimet, largimi dhe mbrojtja e punëtorëve.',
        date: '18 shkurt 2026',
        tag: 'Edukim',
      },
      {
        slug: 'facturino-vs-excel',
        title: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë',
        excerpt: 'Pse softueri i specializuar është më i mirë se Excel për faturim dhe kontabilitet.',
        date: '19 shkurt 2026',
        tag: 'Produkt',
      },
      {
        slug: 'zosto-facturino',
        title: '10 arsye pse bizneset maqedonase zgjedhin Facturino',
        excerpt: 'Nga lokalizimi te automatizimi — pse Facturino është zgjidhja më e mirë për bizneset e MK.',
        date: '20 shkurt 2026',
        tag: 'Produkt',
      },
      {
        slug: 'za-smetkovoditeli',
        title: 'Pse kontabilistët po kalojnë në Facturino',
        excerpt: 'Udhëzues për zyrat e kontabilitetit: më shumë klientë, më pak punë manuale, 20% komision partneriteti.',
        date: '21 shkurt 2026',
        tag: 'Udhëzues',
      },
    ],
    readMore: 'Lexo më shumë',
    bottomCta: {
      title: 'Gati? Filloni në 2 minuta.',
      subtitle: 'Plan falas — pa kartë krediti, pa detyrim.',
      cta: 'Krijo llogari falas',
    },
  },
  tr: {
    hero: {
      title: 'Blog',
      subtitle: 'Makedonya muhasebesi hakkında rehberler, ipuçları ve haberler',
    },
    articles: [
      {
        slug: 'godishna-danocna-prijava-2026',
        title: 'Kuzey Makedonya Yıllık Vergi Beyannamesi 2026: Tarihler ve Rehber',
        excerpt: 'Yıllık beyanname için eksiksiz rehber — DB-VP 15 Mart son tarihi, gerekli belgeler ve e-Tax süreci.',
        date: '22 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'faktura-primer-mk',
        title: 'Fatura örneği: Makedonya\'da doğru fatura nasıl görünür',
        excerpt: '15 zorunlu unsur, KDV\'li eksiksiz örnek ve en sık yapılan faturalama hataları.',
        date: '22 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'e-faktura-obvrska-2026',
        title: 'E-fatura 2026: Kim zorunlu, ne zaman başlıyor ve nasıl hazırlanılır',
        excerpt: 'E-fatura zorunluluğu tarihleri, UBL 2.1 ve QES teknik gereksinimleri ve hazırlık için 6 adım.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'mpin-registracija-2026',
        title: 'MPIN kaydı ve dosyalama 2026: Eksiksiz rehber',
        excerpt: 'MPIN nedir, kim dosyalamalı, UJP kaydı, aylık tarihler ve cezalar.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'neisplatena-plata-prijavuvanje',
        title: 'Ödenmemiş maaş: İş Müfettişliğine nasıl şikayet edilir',
        excerpt: 'Ödenmemiş maaşı bildirmek için adım adım süreç — belgeler, yasal tarihler ve işveren cezaları.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'rok-za-plata-makedonija',
        title: 'Makedonya\'da maaş ödeme tarihi: Kanun ne diyor',
        excerpt: 'Md. 106 İİK: 15\'ine kadar ödeme, fazla mesai %40, gece %35, asgari ücret 20.175 MKD ve cezalar.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'ddv-registracija-prag-2026',
        title: 'KDV kaydı: Eşik, süreç ve yükümlülükler 2026',
        excerpt: '2M MKD eşiği, gönüllü kayıt, UJP süreci, %18/%5/%10/%0 oranları ve DDV-04 beyannamesi.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'kazni-ujp-2026',
        title: 'UJP cezaları: Beyannameleri geciktirirseniz ne olur',
        excerpt: 'İhlal türüne göre cezalar, günlük %0,03 faiz, zamanaşımı, itiraz süreci ve af.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'dooel-vodich-2026',
        title: 'Kuzey Makedonya DOOEL 2026: Vergiler, yükümlülükler ve yıllık hesaplar',
        excerpt: '%10 kurumlar vergisi, 2M MKD KDV eşiği, MPIN, yıllık hesaplar ve DOO/düz oranlı karşılaştırma.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'registracija-firma-cekor-po-cekor',
        title: 'Şirket kaydı 2026: Adım adım kontrol listesi',
        excerpt: 'DOOEL vs DOO vs düz oranlı, CRMS kaydı, EDB, MPIN, KDV kararı ve ilk fatura.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'drzavni-institucii-za-firmi',
        title: 'İşletmeler için devlet kurumları: UJP, CRMS, Müfettişlik — kim kimdir',
        excerpt: 'Tüm devlet kurumları rehberi: UJP, CRMS, FPIOM, FZOM, AVRM.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'paket-za-nova-firma',
        title: 'Yeni şirket kontrol listesi: İlk 30 günde 15 iş',
        excerpt: 'Gün 1-5 kayıt, 5-10 vergiler, 10-20 operasyonlar, 20-30 çalışanlar — eksiksiz liste.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'smetkovodstvo-za-restorani',
        title: 'Restoran Muhasebesi: %10 KDV, Kasa ve Maliyet Kontrolü',
        excerpt: 'KDV %10 vs %18 Md. 30-a uygulaması, fişkal cihaz, food cost, bahşiş ve mevsimlik çalışanlar.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-trgovija',
        title: 'Mağaza Muhasebesi: Stok, Marj ve Fişkal Yazıcı',
        excerpt: 'WAC/FIFO stok, ticari marj, KAP/Nivelasyon, fişkal cihaz ve tedarikçi yönetimi.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-gradeznistvo',
        title: 'İnşaat Muhasebesi: Hakediş, Malzeme ve Taşeronlar',
        excerpt: 'Hakediş faturaları, malzeme maliyetleri, %5/%18 KDV, avanslar ve çok yıllık projeler.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-it-freelancer',
        title: 'Serbest Çalışan ve IT Muhasebesi: Fatura, Vergi ve KDV',
        excerpt: 'Çok para birimli faturalama, ihracat %0 KDV, DOOEL vs düz oranlı, Upwork/Fiverr gelirleri.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-transport',
        title: 'Taşımacılık Muhasebesi: Yakıt, Yol Emirleri ve KDV',
        excerpt: 'Uluslararası taşımacılık %0 KDV, yol emirleri, harcırah, amortisman ve yakıt giderleri.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-proizvodstvo',
        title: 'Üretim Muhasebesi: Malzeme, Hesaplama ve Maliyetler',
        excerpt: 'BOM, üretim emirleri, WAC, fire/israf, fiyat hesaplama ve varyans analizi.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-saloni',
        title: 'Güzellik Salonu Muhasebesi: Kasa, Malzeme ve Çalışanlar',
        excerpt: 'Aynı fişte hizmet + ürün, sarf malzemeleri, komisyonlar, kasa ve fişkal cihaz.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'smetkovodstvo-za-zemjodelstvo',
        title: 'Tarım Muhasebesi: Sübvansiyonlar, KDV ve IPARD',
        excerpt: 'Tarım ürünleri %5 KDV, IPARD hibeleri, mevsimlik gelir, sübvansiyonlar ve arazi kirası.',
        date: '23 Mayıs 2026',
        tag: 'Sektör',
      },
      {
        slug: 'najdobar-smetkovodstven-softver-2026',
        title: '2026 Makedonya için 7 Muhasebe Yazılımı: Karşılaştırma',
        excerpt: 'Facturino, PANTHEON, Accent, Helix ve Excel karşılaştırması — özellikler, fiyatlar, e-Fatura ve uyumluluk.',
        date: '23 Mayıs 2026',
        tag: 'Karşılaştırma',
      },
      {
        slug: 'najdobar-pos-softver-2026',
        title: '2026 Makedonya En İyi POS Yazılımı: Karşılaştırma',
        excerpt: 'POS sistemleri karşılaştırması: Facturino, Vector, Pixel ve diğerleri — özellikler, fiyatlar ve fişkal yazıcı.',
        date: '23 Mayıs 2026',
        tag: 'Karşılaştırma',
      },
      {
        slug: 'najdobar-e-faktura-softver',
        title: '2026 En İyi e-Fatura Yazılımı: Ekim\'e Hazır mısınız?',
        excerpt: 'e-Fatura çözümleri karşılaştırması: UBL 2.1 uyumluluğu, QES imzası ve UJP entegrasyonu.',
        date: '23 Mayıs 2026',
        tag: 'Karşılaştırma',
      },
      {
        slug: 'facturino-vs-pantheon',
        title: 'Facturino vs PANTHEON: Küçük İşletmeler İçin Hangisi Daha İyi?',
        excerpt: 'Adil karşılaştırma: PANTHEON = güçlü ama karmaşık. Facturino = daha basit, daha ucuz, cloud-native.',
        date: '23 Mayıs 2026',
        tag: 'Karşılaştırma',
      },
      {
        slug: 'javni-nabavki-fakturiranje',
        title: 'Kamu İhaleleri İçin Faturalama: Tedarikçi Rehberi',
        excerpt: 'Devlet kurumlarına nasıl fatura kesilir — B2G e-Fatura, ESPP portalı ve ödeme süreleri.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'smetkovodstvo-za-nevladini',
        title: 'STK ve Dernekler İçin Muhasebe: 2026 Rehberi',
        excerpt: 'STK muhasebesi: hibe muhasebesi, KDV muafiyeti, CRMS raporları ve bağışçı gereksinimleri.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'ifrs-izvesti-mk',
        title: 'Makedonya\'da UFRS/IFRS Raporları: Kim Zorunlu ve Nasıl',
        excerpt: 'Hangi şirketler UFRS zorundadır, MSS farklılıkları, mali tablo formatı ve denetim gereksinimleri.',
        date: '23 Mayıs 2026',
        tag: 'Rehber',
      },
      {
        slug: 'bruto-neto-kalkulator-2026',
        title: 'Brüt net hesaplayıcı 2026: Maaşınızı çevrimiçi hesaplayın',
        excerpt: 'Ücretsiz brüt-net hesaplayıcı — emeklilik %18,8, sağlık %7,5, vergi %10 ve 40.000 MKD örneği.',
        date: '22 Mayıs 2026',
        tag: 'Araç',
      },
      {
        slug: 'ai-skener-dokumenti',
        title: 'AI belge tarayıcı: Faturayı tarayın, 10 saniyede kayıt alın',
        excerpt: 'AI Document Hub faturalarınızı, makbuzlarınızı ve sözleşmelerinizi otomatik olarak nasıl sınıflandırır, çıkarır ve kaydeder.',
        date: '15 Mart 2026',
        tag: 'Ürün',
      },
      {
        slug: 'ai-bankarski-usoglasuvanje',
        title: 'AI ile banka mutabakatı: 3 saatten 3 dakikaya',
        excerpt: 'Banka ekstrelerini faturalarla otomatik eşleştirmek için 4 katmanlı AI pipeline — 9 Makedon bankası destekleniyor.',
        date: '15 Mart 2026',
        tag: 'Ürün',
      },
      {
        slug: 'kako-da-otvorite-firma-i-pocnete-so-fakturiranje',
        title: 'Şirket mi kurdunuz? İşte 1 günde faturalamaya nasıl başlarsınız',
        excerpt: 'Kayıttan ilk faturaya — Makedonya\'daki yeni işletmeler için adım adım rehber.',
        date: '15 Mart 2026',
        tag: 'Rehber',
      },
      {
        slug: 'ai-asistent-smetkovodstvo',
        title: 'AI\'ya sorun: Kim bana borçlu? Kârlı mıyım?',
        excerpt: 'Muhasebe için AI asistanı — Makedoncada sorular sorun ve finanslarınız hakkında anında cevaplar alın.',
        date: '15 Mart 2026',
        tag: 'Ürün',
      },
      {
        slug: 'nabavki-i-narachki',
        title: 'Dijital satın alma siparişleri: talepten mal teslimine',
        excerpt: 'Siparişlerin tam yaşam döngüsü — oluşturma, onay, gönderim, teslim alma ve otomatik kayıt.',
        date: '15 Mart 2026',
        tag: 'Ürün',
      },
      {
        slug: 'budzet-i-kontrola-troshoci',
        title: 'Küçük işletmeler için bütçeleme: Masrafları sizi kontrol etmeden önce kontrol edin',
        excerpt: 'Bütçeleme, maliyet merkezleri ve küçük ve orta işletmeler için BI dashboard pratik rehberi.',
        date: '15 Mart 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'godishna-smetka-2025',
        title: 'Yıllık hesaplar 2025: CRMS dosyalama rehberi',
        excerpt: 'Yıllık hesaplar hakkında bilmeniz gereken her şey — son tarihler, formlar, adım adım süreç.',
        date: '15 Ocak 2026',
        tag: 'Rehber',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Bilanço ve gelir tablosu: AOP kodları ve yapı',
        excerpt: 'Form 36 ve Form 37 detaylı inceleme — yapı, AOP kodları, varlıklar/yükümlülükler.',
        date: '20 Ocak 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Yıl sonu kapanışı: Facturino ile 6 adım',
        excerpt: 'Facturino yıl sonu kapanışını nasıl otomatikleştirir — incelemeden kilitlemeye, UJP formatında raporlar.',
        date: '25 Ocak 2026',
        tag: 'Ürün',
      },
      {
        slug: 'sto-e-e-faktura',
        title: 'E-fatura nedir ve neden zorunludur?',
        excerpt: 'Makedonya\'da elektronik fatura hakkında her şey — yasal çerçeve, uygulama tarihleri ve hazırlık.',
        date: '1 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'kako-da-napravite-faktura',
        title: 'Fatura nasıl oluşturulur: Adım adım rehber',
        excerpt: 'Fatura oluşturma için eksiksiz rehber — temel bilgilerden gönderim ve arşivlemeye kadar.',
        date: '2 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'zadolzitelni-elementi-faktura',
        title: 'Makedonya\'da faturanın zorunlu unsurları',
        excerpt: 'KDV Kanunu ve muhasebe yönetmeliklerine göre her faturada bulunması gereken unsurlar.',
        date: '3 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'faktura-vs-proforma',
        title: 'Fatura vs proforma: Temel farklar',
        excerpt: 'Proforma nedir, ne zaman kullanılır ve vergi faturasından farkı — pratik genel bakış.',
        date: '4 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'recurring-invoices-mk',
        title: 'Tekrarlayan faturalar: Tahsilatı otomatikleştirin',
        excerpt: 'Facturino ile aylık hizmetler ve abonelikler için otomatik faturalamayı nasıl kurarsınız.',
        date: '5 Şubat 2026',
        tag: 'Ürün',
      },
      {
        slug: 'ddv-vodich-mk',
        title: 'Makedonya\'da KDV: 2026 için eksiksiz rehber',
        excerpt: 'Oranlar, kayıt, KDV beyannamesi ve muafiyetler — katma değer vergisi hakkında bilmeniz gereken her şey.',
        date: '6 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'danok-na-dobivka',
        title: 'Kurumlar vergisi: Oranlar, tarihler ve hesaplama',
        excerpt: 'Kurumlar vergisi nasıl hesaplanır, hangi giderler kabul edilir ve beyanname ne zaman verilir.',
        date: '7 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'personalen-danok-na-dohod',
        title: 'Makedonya\'da kişisel gelir vergisi',
        excerpt: 'Kişisel gelir vergisi oranları, muafiyetler ve yükümlülükler hakkında genel bakış.',
        date: '8 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'rokovi-ujp-2026',
        title: 'Vergi takvimi 2026: Tüm UJP tarihleri',
        excerpt: '2026 için tüm vergi tarihlerinin eksiksiz listesi — KDV, kurumlar vergisi, MPIN ve yıllık hesaplar.',
        date: '9 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'paushalen-danochnik',
        title: 'Makedonya\'da götürü vergi mükellefi: Koşullar ve yükümlülükler',
        excerpt: 'Kim götürü mükellef olabilir, götürü vergi ne kadardır ve sınırlamalar nelerdir.',
        date: '10 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'otvoranje-firma-mk',
        title: 'Makedonya\'da şirket nasıl kurulur: Eksiksiz rehber',
        excerpt: 'DOOEL veya DOO kaydı için adım adım süreç — belgeler, maliyetler ve tarihler.',
        date: '11 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'smetkovodstvo-za-pocetnici',
        title: 'Yeni başlayanlar için muhasebe: Her işletmenin bilmesi gerekenler',
        excerpt: 'Temel muhasebe kavramları — bilanço yöntemi, hesap planı, kayıtlar ve mali tablolar.',
        date: '12 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'upravljanje-so-rashodi',
        title: 'Gider yönetimi: Küçük işletmeler için 7 ipucu',
        excerpt: 'Maliyet kontrolü, gider sınıflandırması ve bütçe optimizasyonu için pratik ipuçları.',
        date: '13 Şubat 2026',
        tag: 'İpuçları',
      },
      {
        slug: 'cash-flow-mk',
        title: 'Nakit akışı: Neden kârdan daha önemli',
        excerpt: 'Nakit akışının işletme hayatta kalması için neden kritik olduğu ve nasıl iyileştirileceği.',
        date: '14 Şubat 2026',
        tag: 'İpuçları',
      },
      {
        slug: 'digitalno-smetkovodstvo',
        title: 'Dijital vs geleneksel muhasebe',
        excerpt: 'Dijital muhasebenin avantajları — otomasyon, doğruluk, erişilebilirlik ve zaman tasarrufu.',
        date: '15 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'presmetka-na-plata-mk',
        title: 'Makedonya\'da maaş hesaplama: Primler ve vergiler',
        excerpt: 'Brüt/net maaş, zorunlu primler ve kişisel gelir vergisinin detaylı incelemesi.',
        date: '16 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'mpin-obrazec',
        title: 'MPIN formu: Aylık hesaplama rehberi',
        excerpt: 'MPIN formunu doğru nasıl doldurursunuz — alanlar, tarihler ve en sık yapılan hatalar.',
        date: '17 Şubat 2026',
        tag: 'Rehber',
      },
      {
        slug: 'trudovo-pravo-osnovi',
        title: 'İş hukuku: Her işverenin bilmesi gereken 10 şey',
        excerpt: 'İşverenin temel yükümlülükleri — sözleşmeler, çalışma saatleri, izinler, fesih ve işçi koruması.',
        date: '18 Şubat 2026',
        tag: 'Eğitim',
      },
      {
        slug: 'facturino-vs-excel',
        title: 'Facturino vs Excel: Neden tablolar yetmez',
        excerpt: 'Uzmanlaşmış yazılımın faturalama ve muhasebe için neden Excel\'den daha iyi olduğu.',
        date: '19 Şubat 2026',
        tag: 'Ürün',
      },
      {
        slug: 'zosto-facturino',
        title: 'Makedon işletmelerin Facturino\'yu seçmesinin 10 nedeni',
        excerpt: 'Yerelleştirmeden otomasyona — Facturino\'nun MK işletmeleri için neden en iyi seçim olduğu.',
        date: '20 Şubat 2026',
        tag: 'Ürün',
      },
      {
        slug: 'za-smetkovoditeli',
        title: 'Muhasebeciler neden Facturino\'ya geçiyor',
        excerpt: 'Muhasebe büroları için rehber: daha fazla müşteri, daha az manuel iş, %20 ortaklık komisyonu.',
        date: '21 Şubat 2026',
        tag: 'Rehber',
      },
    ],
    readMore: 'Devamını oku',
    bottomCta: {
      title: 'Hazır mısınız? 2 dakikada başlayın.',
      subtitle: 'Ücretsiz plan — kredi kartı yok, zorunluluk yok.',
      cta: 'Ücretsiz hesap oluştur',
    },
  },
  en: {
    hero: {
      title: 'Blog',
      subtitle: 'Guides, tips and news about accounting in Macedonia',
    },
    articles: [
      {
        slug: 'godishna-danocna-prijava-2026',
        title: 'Annual Tax Return North Macedonia 2026: Deadlines & Step-by-Step Guide',
        excerpt: 'Complete guide to annual tax returns — March 15 DB-VP deadline, required documents and e-Tax filing process.',
        date: 'May 22, 2026',
        tag: 'Guide',
      },
      {
        slug: 'faktura-primer-mk',
        title: 'Invoice Example North Macedonia: What a Proper Invoice Looks Like',
        excerpt: '15 mandatory elements, complete example with VAT and most common invoicing mistakes.',
        date: 'May 22, 2026',
        tag: 'Guide',
      },
      {
        slug: 'bruto-neto-kalkulator-2026',
        title: 'North Macedonia Gross to Net Salary Calculator 2026',
        excerpt: 'Free gross-to-net calculator — pension 18.8%, health 7.5%, income tax 10% and worked example with 40,000 MKD.',
        date: 'May 22, 2026',
        tag: 'Tool',
      },
      {
        slug: 'e-faktura-obvrska-2026',
        title: 'E-Invoice Mandate North Macedonia 2026: Who Must Comply & How',
        excerpt: 'E-invoice mandate timeline, UBL 2.1 and QES technical requirements, and 6 steps to prepare.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'mpin-registracija-2026',
        title: 'MPIN Registration & Filing North Macedonia 2026: Complete Guide',
        excerpt: 'What is MPIN, who must file, UJP registration process, monthly deadlines and penalties.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'neisplatena-plata-prijavuvanje',
        title: 'Unpaid Wages North Macedonia: How to Report to Labor Inspectorate',
        excerpt: 'Step-by-step process for reporting unpaid wages — documents, legal deadlines and employer penalties.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'rok-za-plata-makedonija',
        title: 'Salary Payment Deadline North Macedonia: What the Law Says',
        excerpt: 'Art. 106 LRA: deadline by 15th, overtime 40%, night shift 35%, minimum wage 20,175 MKD and penalties.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'ddv-registracija-prag-2026',
        title: 'VAT Registration North Macedonia 2026: Threshold, Process & Obligations',
        excerpt: '2M MKD threshold, voluntary registration, UJP process, 18%/5%/10%/0% rates and DDV-04 filing.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'kazni-ujp-2026',
        title: 'UJP Penalties North Macedonia: What Happens If You File Late',
        excerpt: 'Penalties by violation type, 0.03% daily interest, statute of limitations, appeal process and amnesty.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'dooel-vodich-2026',
        title: 'Single-Member LLC (DOOEL) in North Macedonia 2026: Taxes & Obligations',
        excerpt: '10% corporate tax, VAT at >2M MKD, MPIN, annual accounts and comparison with DOO and sole trader.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'registracija-firma-cekor-po-cekor',
        title: 'Company Registration North Macedonia 2026: Step-by-Step Checklist',
        excerpt: 'DOOEL vs DOO vs sole trader, CRMS registration, EDB, MPIN, VAT decision and first invoice.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'drzavni-institucii-za-firmi',
        title: 'Government Agencies for Business: UJP, CRMS, Inspectorate — Who Is Who',
        excerpt: 'Guide through all government agencies: UJP, CRMS, FPIOM, FZOM, AVRM.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'paket-za-nova-firma',
        title: 'New Company Checklist: 15 Things to Do in Your First 30 Days',
        excerpt: 'Days 1-5 registration, 5-10 taxes, 10-20 operations, 20-30 employees — complete checklist.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'smetkovodstvo-za-restorani',
        title: 'Restaurant Accounting North Macedonia: 10% VAT, POS & Cost Control',
        excerpt: '10% vs 18% VAT under Art. 30-a, fiscal device, food cost, tips and seasonal employees.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-trgovija',
        title: 'Retail Accounting Macedonia: Inventory, Margins & Fiscal Printer',
        excerpt: 'WAC/FIFO inventory, trade margins, KAP/Nivelation, fiscal device and supplier management.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-gradeznistvo',
        title: 'Construction Accounting Macedonia: Progress Billing, Materials & Subcontractors',
        excerpt: 'Progress billing, material costs, 5%/18% VAT, advance payments and multi-year projects.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-it-freelancer',
        title: 'Freelancer & IT Accounting Macedonia: Invoicing, Tax & VAT',
        excerpt: 'Multi-currency invoicing, 0% VAT on exports, DOOEL vs sole trader, Upwork/Fiverr income.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-transport',
        title: 'Transport & Logistics Accounting Macedonia: Fuel, Travel Orders & VAT',
        excerpt: 'International transport 0% VAT, travel orders, per diem, depreciation and fuel expenses.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-proizvodstvo',
        title: 'Manufacturing Accounting Macedonia: BOM, Cost Analysis & Inventory',
        excerpt: 'BOM, production orders, WAC, wastage/scrap, cost pricing and variance analysis.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-saloni',
        title: 'Beauty Salon Accounting Macedonia: POS, Supplies & Employees',
        excerpt: 'Service + product on same receipt, consumables, commissions, cash and fiscal device.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'smetkovodstvo-za-zemjodelstvo',
        title: 'Agriculture Accounting Macedonia: Subsidies, VAT & IPARD Grants',
        excerpt: '5% VAT on agricultural products, IPARD grants, seasonal income, subsidies and land lease.',
        date: 'May 23, 2026',
        tag: 'Sector',
      },
      {
        slug: 'najdobar-smetkovodstven-softver-2026',
        title: '7 Accounting Software for North Macedonia 2026: Comparison',
        excerpt: 'Compare Facturino, PANTHEON, Accent, Helix and Excel — features, pricing, e-Invoice and MK compliance.',
        date: 'May 23, 2026',
        tag: 'Comparison',
      },
      {
        slug: 'najdobar-pos-softver-2026',
        title: 'Best POS Software for North Macedonia 2026: Comparison',
        excerpt: 'POS system comparison: Facturino, Vector, Pixel and others — features, pricing and fiscal printer.',
        date: 'May 23, 2026',
        tag: 'Comparison',
      },
      {
        slug: 'najdobar-e-faktura-softver',
        title: 'Best E-Invoice Software 2026: Ready for October?',
        excerpt: 'E-invoice solution comparison: UBL 2.1 compliance, QES signing and UJP integration.',
        date: 'May 23, 2026',
        tag: 'Comparison',
      },
      {
        slug: 'facturino-vs-pantheon',
        title: 'Facturino vs PANTHEON: Which Is Better for Small Businesses?',
        excerpt: 'Fair comparison: PANTHEON = powerful but complex. Facturino = simpler, cheaper, cloud-native.',
        date: 'May 23, 2026',
        tag: 'Comparison',
      },
      {
        slug: 'javni-nabavki-fakturiranje',
        title: 'Invoicing for Government Procurement: Supplier Guide',
        excerpt: 'How to invoice government institutions — B2G e-Invoice, ESPP portal and payment terms.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'smetkovodstvo-za-nevladini',
        title: 'NGO & Association Accounting: 2026 Guide',
        excerpt: 'NGO accounting: grant accounting, VAT exemptions, CRMS reports and donor requirements.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'ifrs-izvesti-mk',
        title: 'IFRS Reporting in North Macedonia: Who Must and How',
        excerpt: 'Which companies must use IFRS, differences from MAS, financial statement format and audit requirements.',
        date: 'May 23, 2026',
        tag: 'Guide',
      },
      {
        slug: 'ai-skener-dokumenti',
        title: 'AI Document Scanner: Scan an Invoice, Get a Journal Entry in 10 Seconds',
        excerpt: 'How AI Document Hub automatically classifies, extracts and records your invoices, receipts and contracts.',
        date: 'March 15, 2026',
        tag: 'Product',
      },
      {
        slug: 'ai-bankarski-usoglasuvanje',
        title: 'AI Bank Reconciliation: From 3 Hours to 3 Minutes',
        excerpt: '4-layer AI pipeline for automatic bank statement reconciliation — all 9 Macedonian banks supported.',
        date: 'March 15, 2026',
        tag: 'Product',
      },
      {
        slug: 'kako-da-otvorite-firma-i-pocnete-so-fakturiranje',
        title: 'Just Registered a Company? Here\'s How to Start Invoicing in 1 Day',
        excerpt: 'From registration to first invoice — step-by-step guide for new businesses in Macedonia.',
        date: 'March 15, 2026',
        tag: 'Guide',
      },
      {
        slug: 'ai-asistent-smetkovodstvo',
        title: 'Ask AI: Who Owes Me? Am I Profitable?',
        excerpt: 'AI accounting assistant — ask questions in plain language and get instant answers about your finances.',
        date: 'March 15, 2026',
        tag: 'Product',
      },
      {
        slug: 'nabavki-i-narachki',
        title: 'Digital Purchase Orders: From Request to Goods Receipt',
        excerpt: 'Complete purchase order lifecycle — creation, approval, sending, receipt and automatic journal entry.',
        date: 'March 15, 2026',
        tag: 'Product',
      },
      {
        slug: 'budzet-i-kontrola-troshoci',
        title: 'Budgeting for Small Businesses: Control Costs Before They Control You',
        excerpt: 'Practical guide to budgeting, cost centers and BI dashboard for small and medium businesses.',
        date: 'March 15, 2026',
        tag: 'Education',
      },
      {
        slug: 'godishna-smetka-2025',
        title: 'Annual Accounts 2025: Complete Filing Guide for CRMS',
        excerpt: 'Everything you need to know about annual accounts — deadlines, forms, step-by-step CRMS filing process.',
        date: 'January 15, 2026',
        tag: 'Guide',
      },
      {
        slug: 'bilans-na-sostojba',
        title: 'Balance Sheet & Income Statement: AOP Codes and Structure',
        excerpt: 'Detailed overview of Form 36 and Form 37 — structure, AOP codes, assets/liabilities and revenues/expenses.',
        date: 'January 20, 2026',
        tag: 'Education',
      },
      {
        slug: 'godishno-zatvoranje-facturino',
        title: 'Year-End Closing: 6 Steps with Facturino',
        excerpt: 'How Facturino automates year-end closing — from review to lock, with UJP-format reports.',
        date: 'January 25, 2026',
        tag: 'Product',
      },
      {
        slug: 'sto-e-e-faktura',
        title: 'What Is E-Invoice and Why Is It Mandatory?',
        excerpt: 'Everything about electronic invoicing in Macedonia — legal framework, implementation deadlines and how to prepare.',
        date: 'February 1, 2026',
        tag: 'Guide',
      },
      {
        slug: 'kako-da-napravite-faktura',
        title: 'How to Create an Invoice: Step-by-Step Guide',
        excerpt: 'Complete guide to creating an invoice — from basic data to sending and archiving.',
        date: 'February 2, 2026',
        tag: 'Guide',
      },
      {
        slug: 'zadolzitelni-elementi-faktura',
        title: 'Mandatory Invoice Elements in Macedonia',
        excerpt: 'Which elements every invoice must contain according to the VAT Law and accounting regulations.',
        date: 'February 3, 2026',
        tag: 'Education',
      },
      {
        slug: 'faktura-vs-proforma',
        title: 'Invoice vs Proforma: Key Differences',
        excerpt: 'What is a proforma invoice, when is it used and how it differs from a tax invoice — practical overview.',
        date: 'February 4, 2026',
        tag: 'Education',
      },
      {
        slug: 'recurring-invoices-mk',
        title: 'Recurring Invoices: Automate Your Billing',
        excerpt: 'How to set up automatic invoicing for monthly services and subscriptions with Facturino.',
        date: 'February 5, 2026',
        tag: 'Product',
      },
      {
        slug: 'ddv-vodich-mk',
        title: 'VAT in Macedonia: Complete Guide for 2026',
        excerpt: 'Rates, registration, VAT returns and exemptions — everything about value-added tax.',
        date: 'February 6, 2026',
        tag: 'Guide',
      },
      {
        slug: 'danok-na-dobivka',
        title: 'Corporate Income Tax: Rates, Deadlines and Calculation',
        excerpt: 'How corporate income tax is calculated, which expenses are recognized and when to file.',
        date: 'February 7, 2026',
        tag: 'Education',
      },
      {
        slug: 'personalen-danok-na-dohod',
        title: 'Personal Income Tax in Macedonia',
        excerpt: 'Overview of rates, exemptions and obligations for personal income tax for individuals and employees.',
        date: 'February 8, 2026',
        tag: 'Education',
      },
      {
        slug: 'rokovi-ujp-2026',
        title: 'Tax Calendar 2026: All UJP Deadlines',
        excerpt: 'Complete overview of all tax deadlines for 2026 — VAT, corporate tax, MPIN and annual accounts.',
        date: 'February 9, 2026',
        tag: 'Guide',
      },
      {
        slug: 'paushalen-danochnik',
        title: 'Lump-Sum Taxation in Macedonia: Conditions and Obligations',
        excerpt: 'Who can be a lump-sum taxpayer, how much is the tax and what are the limitations.',
        date: 'February 10, 2026',
        tag: 'Education',
      },
      {
        slug: 'otvoranje-firma-mk',
        title: 'How to Register a Company in Macedonia: Complete Guide',
        excerpt: 'Step-by-step process for registering a DOOEL or DOO — documents, costs and deadlines.',
        date: 'February 11, 2026',
        tag: 'Guide',
      },
      {
        slug: 'smetkovodstvo-za-pocetnici',
        title: 'Accounting for Beginners: Basics Every Business Should Know',
        excerpt: 'Basic accounting concepts — balance method, chart of accounts, journal entries and financial statements.',
        date: 'February 12, 2026',
        tag: 'Education',
      },
      {
        slug: 'upravljanje-so-rashodi',
        title: 'Expense Management: 7 Tips for Small Businesses',
        excerpt: 'Practical tips for cost control, expense categorization and budget optimization.',
        date: 'February 13, 2026',
        tag: 'Tips',
      },
      {
        slug: 'cash-flow-mk',
        title: 'Cash Flow: Why It Matters More Than Profit',
        excerpt: 'Why cash flow is critical for business survival and how to improve it.',
        date: 'February 14, 2026',
        tag: 'Tips',
      },
      {
        slug: 'digitalno-smetkovodstvo',
        title: 'Digital vs Traditional Accounting',
        excerpt: 'Advantages of digital accounting — automation, accuracy, accessibility and time savings.',
        date: 'February 15, 2026',
        tag: 'Education',
      },
      {
        slug: 'presmetka-na-plata-mk',
        title: 'Payroll Calculation in Macedonia: Contributions and Taxes',
        excerpt: 'Detailed overview of gross/net salary, mandatory contributions and personal income tax.',
        date: 'February 16, 2026',
        tag: 'Guide',
      },
      {
        slug: 'mpin-obrazec',
        title: 'MPIN Form: Monthly Payroll Filing Guide',
        excerpt: 'How to correctly fill out the MPIN form — fields, deadlines and most common mistakes.',
        date: 'February 17, 2026',
        tag: 'Guide',
      },
      {
        slug: 'trudovo-pravo-osnovi',
        title: 'Labor Law: 10 Things Every Employer Must Know',
        excerpt: 'Basic employer obligations — contracts, working hours, leave, termination and worker protection.',
        date: 'February 18, 2026',
        tag: 'Education',
      },
      {
        slug: 'facturino-vs-excel',
        title: 'Facturino vs Excel: Why Spreadsheets Are Not Enough',
        excerpt: 'Why specialized software is better than Excel for invoicing and accounting.',
        date: 'February 19, 2026',
        tag: 'Product',
      },
      {
        slug: 'zosto-facturino',
        title: '10 Reasons Macedonian Businesses Choose Facturino',
        excerpt: 'From localization to automation — why Facturino is the best choice for MK businesses.',
        date: 'February 20, 2026',
        tag: 'Product',
      },
      {
        slug: 'za-smetkovoditeli',
        title: 'Why Accountants Are Switching to Facturino',
        excerpt: 'Guide for accounting firms: more clients, less manual work, 20% partner commission.',
        date: 'February 21, 2026',
        tag: 'Guide',
      },
    ],
    readMore: 'Read more',
    bottomCta: {
      title: 'Ready? Get started in 2 minutes.',
      subtitle: 'Free plan — no credit card, no commitment.',
      cta: 'Create a free account',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function BlogPage({
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
      {/*  HERO                                                        */}
      {/* ============================================================ */}
      <PageHero
        image="/assets/images/hero_blog.png"
        alt="Laptop with accounting books and coffee on wooden desk"
        title={t.hero.title}
        subtitle={t.hero.subtitle}
      />

      {/* ============================================================ */}
      {/*  ARTICLES GRID                                               */}
      {/* ============================================================ */}
      <section className="py-16 md:py-24">
        <div className="container px-4 sm:px-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            {t.articles.map((article) => (
              <Link
                key={article.slug}
                href={`/${locale}/blog/${article.slug}`}
                className="group flex flex-col bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 overflow-hidden"
              >
                {/* Tag + Date header */}
                <div className="px-6 pt-6 pb-0 flex items-center justify-between">
                  <span className="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600">
                    {article.tag}
                  </span>
                  <span className="text-sm text-gray-400">{article.date}</span>
                </div>

                {/* Content */}
                <div className="flex flex-col flex-1 px-6 pt-4 pb-6">
                  <h2 className="text-lg font-bold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors leading-snug">
                    {article.title}
                  </h2>
                  <p className="text-gray-500 text-sm leading-relaxed flex-1">
                    {article.excerpt}
                  </p>
                  <span className="mt-4 inline-flex items-center text-sm font-semibold text-indigo-600 group-hover:text-cyan-600 transition-colors">
                    {t.readMore}
                    <svg
                      className="ml-1 w-4 h-4 group-hover:translate-x-1 transition-transform"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      strokeWidth={2}
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M13 7l5 5m0 0l-5 5m5-5H6"
                      />
                    </svg>
                  </span>
                </div>
              </Link>
            ))}
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.bottomCta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.bottomCta.subtitle}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.bottomCta.cta}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
