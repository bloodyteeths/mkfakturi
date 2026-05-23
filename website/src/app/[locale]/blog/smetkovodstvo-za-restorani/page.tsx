import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-restorani', {
    title: {
      mk: 'Сметководство за ресторани и кафулиња: ДДВ 10%, каса и трошоци',
      en: 'Restaurant Accounting in North Macedonia: 10% VAT, POS & Cost Control',
      sq: 'Kontabiliteti për restorante dhe kafene: TVSH 10%, arka dhe shpenzime',
      tr: 'Restoran Muhasebesi Makedonya: %10 KDV, Kasa ve Maliyet Kontrolü',
    },
    description: {
      mk: 'Комплетен водич за сметководство во угостителство: кога важи ДДВ 10% vs 18%, фискален печатач, дневна каса, трошоци за храна, бакшиш и сезонски вработени.',
      en: 'Complete guide to hospitality accounting in North Macedonia: when 10% vs 18% VAT applies, fiscal printer, daily cash, food costs, tips and seasonal employees.',
      sq: 'Udhëzues i plotë për kontabilitetin në hotelieri: kur vlen TVSH 10% vs 18%, printer fiskal, arka ditore, shpenzime ushqimi, bakshish dhe punëtorë sezonal.',
      tr: 'Kuzey Makedonya\'da otelcilik muhasebesi rehberi: %10 vs %18 KDV, fişkal yazıcı, günlük kasa, yemek maliyetleri, bahşiş ve mevsimlik çalışanlar.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за ресторани и кафулиња: ДДВ 10%, каса и трошоци',
    publishDate: '23 мај 2026',
    readTime: '11 мин читање',
    intro:
      'Ресторани, кафулиња, пицерии и барови имаат специфични сметководствени предизвици кои не постојат во другите сектори: два различни ДДВ стапки на иста маса, дневна каса со готовина и картичка, трошоци за храна што варираат дневно, бакшиш, сезонски вработени и строги фискални обврски. Овој водич покрива сè што сопствениците на угостителски објекти треба да знаат за правилно книговодство во 2026.',
    sections: [
      {
        title: 'ДДВ 10% vs 18%: Кога важи намалената стапка?',
        content:
          'Ова е најчестиот извор на грешки во угостителството. Правилото по Чл. 30-а ЗДДВ е јасно, но примената е сложена:',
        items: [
          'ДДВ 10% — Храна и безалкохолни пијалоци послужени во ресторант (конзумација на лице место)',
          'ДДВ 18% — Алкохолни пијалоци — СЕКОГАШ 18%, без разлика дали е на лице место или за носење',
          'ДДВ 18% — Храна за носење (takeaway/delivery) — се смета за продажба на стока, не за угостителска услуга',
          'ДДВ 18% — Кетеринг — се третира како продажба на стока со услуга, не како ресторанска услуга',
          'Мешани нарачки: Ако клиент нарачува јадење (10%) и пиво (18%), секоја ставка се фактурира со соодветната стапка',
          'Кафе во кафуле = 10% (безалкохолен пијалок на лице место). Кафе за носење = 18%',
          'ВАЖНО: Фискалниот уред мора правилно да ги раздвои ставките по ДДВ стапка на секоја сметка',
        ],
        steps: null,
      },
      {
        title: 'Фискален уред и дневна каса',
        content:
          'Секој ресторан кој продава на физички лица (B2C) мора да има фискален уред. Еве ги клучните обврски:',
        items: null,
        steps: [
          { step: 'Фискален уред е задолжителен', desc: 'Закон за фискализација — секоја B2C продажба мора да биде регистрирана на фискален уред. Казна за продажба без фискална сметка: EUR 2.000-5.000.' },
          { step: 'Дневен извештај (Z-извештај)', desc: 'На крајот на секој работен ден, фискалниот уред генерира Z-извештај (дневен збир). Овој извештај е задолжителен и мора да се чува 5 години.' },
          { step: 'Готовина vs картичка', desc: 'Одделно евидентирање на готовински и безготовински плаќања. Дневниот депозит во банка мора да одговара на готовинските примања.' },
          { step: 'Сторно и поврат', desc: 'Сторно на фискална сметка може САМО со одобрение на менаџер. Секој поврат мора да биде евидентиран и документиран.' },
          { step: 'Месечен извештај до УЈП', desc: 'Фискалните податоци автоматски се пренесуваат до УЈП. Несовпаѓање помеѓу фискален промет и ДДВ пријава = ревизија.' },
        ],
      },
      {
        title: 'Трошоци за храна и пијалоци (Food Cost)',
        content:
          'Контролата на трошоците за храна е клучна за профитабилност. Идеалниот food cost за ресторан е 28-35% од продажната цена:',
        items: [
          'Дневен прием на стока: евидентирај ги сите фактури од добавувачи (месо, зеленчук, пијалоци) — со датум, количина и цена',
          'Кало, растур, расипување: Во угостителството е нормално 2-5% загуба. Евидентирај го секој отпис со записник',
          'Рецептура (нормативи): Секое јадење мора да има нормативна калкулација — колку грама од секој состојок се троши. Ова е основа за пресметка на food cost',
          'Инвентура: Месечна инвентура на залиха е задолжителна. Разликата помеѓу книговодствена и фактичка залиха = кало или кражба',
          'FIFO метод: Храната се евидентира по принцип „прва влезена, прва излезена" — за да се избегне расипување и точна цена',
          'Маржа по производ: Следете ја маржата на секое јадење посебно. Тестенини имаат висока маржа (~70%), стекови ниска (~40%)',
        ],
        steps: null,
      },
      {
        title: 'Бакшиш, оброци за вработени и репрезентација',
        content:
          'Три области каде ресторатерите најчесто грешат:',
        items: null,
        steps: [
          { step: 'Бакшиш (tips)', desc: 'Бакшишот НЕ е приход на ресторанот — тој е личен приход на вработениот. Ако бакшишот се собира централно и се распределува, мора да се евидентира и оданочи како дел од платата (10% ПДД + придонеси).' },
          { step: 'Оброци за вработени', desc: 'Законски, оброците за вработени се признат расход до 20% од просечната плата. Над тој износ — непризнат расход за данок на добивка. Евидентирајте ги одделно од продажбата.' },
          { step: 'Репрезентација (деловни ручеци)', desc: 'Признат расход до 1% од вкупниот приход. Мора да имате фактура со име на гостинот и деловна цел. Над 1% — непризнат расход.' },
          { step: 'Конзумација на сопственикот', desc: 'Ако сопственикот јаде во ресторанот, тоа е ЛИЧНА потрошувачка и НЕ е признат расход. Евидентирајте одделно или издајте интерна фактура.' },
        ],
      },
      {
        title: 'Сезонски и хонорарни вработени',
        content:
          'Угостителството има висока флуктуација и сезонски работници. Еве ги правилата:',
        items: [
          'Договор на определено време — максимум 5 години вкупно (Чл. 46 ЗРО). За сезонска работа може да биде и пократок',
          'МПИН се поднесува за СЕКОЈ вработен, без разлика дали е на определено или неопределено време',
          'Минимална плата важи и за сезонски работници — 20.175 МКД бруто (2026)',
          'Прекувремена работа: максимум 8 часа неделно, 190 часа годишно. Додаток 40% на основна плата',
          'Ноќна работа (22:00-06:00): додаток 35%. Ресторани работат до доцна — ова е чест трошок',
          'Пробен период: максимум 6 месеци. За време на проба — полни придонеси и МПИН обврски',
          'Хигиенски минимум: Секој вработен во угостителство мора да има санитарна книшка — обнова годишно',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino има специјален POS режим дизајниран за ресторани и кафулиња:',
        items: [
          'POS режим со маса-менаџмент: Визуелна мапа на маси, отворени нарачки по маса, пренос помеѓу маси',
          'KDS (Kitchen Display System): Нарачките автоматски се прикажуваат на екранот во кујната',
          'Автоматско раздвојување на ДДВ 10% и 18% по тип на ставка',
          'Интеграција со фискални печатачи — поддржани 9 модели во Македонија',
          'Дневен, неделен и месечен извештај за промет, маржа и food cost',
          'Модул за плати — автоматска пресметка со прекувремена, ноќна и бакшиш',
          'Бесплатен план за ресторани — почнете без инвестиција',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'POS софтвер за Македонија: Споредба' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија' },
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија' },
    ],
    bottomCta: {
      title: 'POS за ресторан? Facturino е бесплатен.',
      subtitle: 'Маса-менаџмент, KDS, фискален печатач и ДДВ 10%/18% — сè вклучено. Без месечна претплата.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Restaurant Accounting in North Macedonia: 10% VAT, POS & Cost Control',
    publishDate: 'May 23, 2026',
    readTime: '11 min read',
    intro:
      'Restaurants, cafés, pizzerias, and bars face unique accounting challenges that don\'t exist in other sectors: two different VAT rates at the same table, daily cash with cards and cash, food costs that fluctuate daily, tips, seasonal employees, and strict fiscal obligations. This guide covers everything hospitality owners need to know about proper bookkeeping in 2026.',
    sections: [
      {
        title: '10% vs 18% VAT: When does the reduced rate apply?',
        content:
          'This is the most common source of errors in hospitality. The rule under Art. 30-a of the VAT Act is clear, but the application is complex:',
        items: [
          'VAT 10% — Food and non-alcoholic beverages served in a restaurant (on-premises consumption)',
          'VAT 18% — Alcoholic beverages — ALWAYS 18%, regardless of on-premises or takeaway',
          'VAT 18% — Takeaway/delivery food — treated as sale of goods, not a hospitality service',
          'VAT 18% — Catering — treated as sale of goods with service, not a restaurant service',
          'Mixed orders: If a customer orders a meal (10%) and beer (18%), each item is invoiced at its respective rate',
          'Coffee in a café = 10% (non-alcoholic beverage on premises). Coffee to go = 18%',
          'IMPORTANT: The fiscal device must correctly separate items by VAT rate on every receipt',
        ],
        steps: null,
      },
      {
        title: 'Fiscal device and daily cash',
        content:
          'Every restaurant selling to individuals (B2C) must have a fiscal device. Here are the key obligations:',
        items: null,
        steps: [
          { step: 'Fiscal device is mandatory', desc: 'Fiscalization Act — every B2C sale must be registered on a fiscal device. Penalty for selling without a fiscal receipt: EUR 2,000-5,000.' },
          { step: 'Daily report (Z-report)', desc: 'At the end of each business day, the fiscal device generates a Z-report (daily summary). This report is mandatory and must be kept for 5 years.' },
          { step: 'Cash vs card', desc: 'Separate recording of cash and non-cash payments. The daily bank deposit must match cash receipts.' },
          { step: 'Void and refund', desc: 'Voiding a fiscal receipt requires manager approval ONLY. Every refund must be recorded and documented.' },
          { step: 'Monthly report to UJP', desc: 'Fiscal data is automatically transmitted to UJP. Discrepancy between fiscal turnover and VAT return = audit trigger.' },
        ],
      },
      {
        title: 'Food and beverage costs (Food Cost)',
        content:
          'Food cost control is key to profitability. The ideal food cost for a restaurant is 28-35% of the selling price:',
        items: [
          'Daily goods receipt: record all supplier invoices (meat, vegetables, beverages) — with date, quantity and price',
          'Spoilage, waste, breakage: In hospitality, 2-5% loss is normal. Record every write-off with a protocol',
          'Recipe costing (norms): Every dish must have a standard recipe calculation — how many grams of each ingredient are used. This is the basis for food cost calculation',
          'Inventory: Monthly stock count is mandatory. The difference between book and actual stock = spoilage or theft',
          'FIFO method: Food is recorded on a "first in, first out" basis — to prevent spoilage and ensure accurate costing',
          'Margin per product: Track the margin on each dish separately. Pasta has a high margin (~70%), steaks a low one (~40%)',
        ],
        steps: null,
      },
      {
        title: 'Tips, employee meals, and entertainment',
        content:
          'Three areas where restaurant owners most commonly make mistakes:',
        items: null,
        steps: [
          { step: 'Tips', desc: 'Tips are NOT revenue of the restaurant — they are personal income of the employee. If tips are pooled and distributed centrally, they must be recorded and taxed as part of the salary (10% PIT + contributions).' },
          { step: 'Employee meals', desc: 'By law, employee meals are a recognized expense up to 20% of the average salary. Above that amount — non-deductible expense for corporate tax. Record them separately from sales.' },
          { step: 'Entertainment (business lunches)', desc: 'Recognized expense up to 1% of total revenue. You must have an invoice with the guest\'s name and business purpose. Above 1% — non-deductible expense.' },
          { step: 'Owner\'s personal consumption', desc: 'If the owner eats at the restaurant, that is PERSONAL consumption and is NOT a deductible expense. Record separately or issue an internal invoice.' },
        ],
      },
      {
        title: 'Seasonal and part-time employees',
        content:
          'Hospitality has high turnover and seasonal workers. Here are the rules:',
        items: [
          'Fixed-term contract — maximum 5 years total (Art. 46 LRA). For seasonal work it can be shorter',
          'MPIN must be filed for EVERY employee, regardless of fixed or permanent term',
          'Minimum wage applies to seasonal workers too — 20,175 MKD gross (2026)',
          'Overtime: maximum 8 hours per week, 190 hours per year. Premium 40% on base salary',
          'Night work (22:00-06:00): premium 35%. Restaurants operate late — this is a common cost',
          'Probation period: maximum 6 months. During probation — full contributions and MPIN obligations',
          'Hygiene minimum: Every hospitality employee must have a sanitary booklet — renewed annually',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps',
        content:
          'Facturino has a special POS mode designed for restaurants and cafés:',
        items: [
          'POS mode with table management: Visual table map, open orders per table, transfer between tables',
          'KDS (Kitchen Display System): Orders automatically appear on the kitchen screen',
          'Automatic VAT splitting between 10% and 18% by item type',
          'Integration with fiscal printers — 9 models supported in North Macedonia',
          'Daily, weekly, and monthly reports for turnover, margin, and food cost',
          'Payroll module — automatic calculation with overtime, night shift, and tips',
          'Free plan for restaurants — start without any investment',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'POS Software for North Macedonia: Comparison' },
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for North Macedonia' },
      { slug: 'presmetka-na-plata-mk', title: 'Salary Calculation in North Macedonia' },
    ],
    bottomCta: {
      title: 'POS for your restaurant? Facturino is free.',
      subtitle: 'Table management, KDS, fiscal printer, and 10%/18% VAT — all included. No monthly subscription.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti për restorante dhe kafene: TVSH 10%, arka dhe shpenzime',
    publishDate: '23 maj 2026',
    readTime: '11 min lexim',
    intro:
      'Restorantet, kafenetë, piceriat dhe baret kanë sfida specifike kontabël që nuk ekzistojnë në sektorë të tjerë: dy norma të ndryshme TVSH në të njëjtën tavolinë, arka ditore me para në dorë dhe kartë, shpenzime ushqimi që ndryshojnë çdo ditë, bakshish, punëtorë sezonal dhe detyrime strikte fiskale. Ky udhëzues mbulon gjithçka që pronarët e hotelierisë duhet të dinë për kontabilitetin e duhur në 2026.',
    sections: [
      {
        title: 'TVSH 10% vs 18%: Kur vlen norma e reduktuar?',
        content:
          'Ky është burimi më i shpeshtë i gabimeve në hotelieri. Rregulli sipas Nenit 30-a të Ligjit për TVSH është i qartë, por zbatimi është kompleks:',
        items: [
          'TVSH 10% — Ushqim dhe pije joalkoolike të shërbyera në restorant (konsumim në vend)',
          'TVSH 18% — Pije alkoolike — GJITHMONË 18%, pavarësisht nëse është në vend ose për marrje',
          'TVSH 18% — Ushqim për marrje (takeaway/delivery) — trajtohet si shitje malli, jo si shërbim hotelierik',
          'TVSH 18% — Katering — trajtohet si shitje malli me shërbim, jo si shërbim restoranti',
          'Porosi të përziera: Nëse klienti porosit ushqim (10%) dhe birrë (18%), secila zë faturohet me normën përkatëse',
          'Kafe në kafene = 10% (pije joalkoolike në vend). Kafe për marrje = 18%',
          'E RËNDËSISHME: Pajisja fiskale duhet t\'i ndajë saktë zërat sipas normës TVSH në çdo faturë',
        ],
        steps: null,
      },
      {
        title: 'Pajisja fiskale dhe arka ditore',
        content:
          'Çdo restorant që shet te individët (B2C) duhet të ketë pajisje fiskale:',
        items: null,
        steps: [
          { step: 'Pajisja fiskale është e detyrueshme', desc: 'Ligji për fiskalizim — çdo shitje B2C duhet të regjistrohet në pajisje fiskale. Gjobë për shitje pa faturë fiskale: EUR 2.000-5.000.' },
          { step: 'Raporti ditor (Z-raport)', desc: 'Në fund të çdo dite pune, pajisja fiskale gjeneron Z-raport (përmbledhje ditore). Ky raport është i detyrueshëm dhe duhet të ruhet 5 vjet.' },
          { step: 'Para në dorë vs kartë', desc: 'Regjistrimi i veçantë i pagesave me para në dorë dhe pa para. Depozita ditore bankare duhet të përputhet me arkëtimet me para.' },
          { step: 'Storno dhe kthim', desc: 'Anulimi i faturës fiskale kërkon VETËM miratimin e menaxherit. Çdo kthim duhet të regjistrohet dhe dokumentohet.' },
          { step: 'Raporti mujor për DAP', desc: 'Të dhënat fiskale transmetohen automatikisht te DAP. Mospërputhja midis qarkullimit fiskal dhe deklaratës TVSH = audit.' },
        ],
      },
      {
        title: 'Shpenzimet e ushqimit dhe pijes (Food Cost)',
        content:
          'Kontrolli i shpenzimeve të ushqimit është çelësi i profitabilitetit. Food cost ideal për restorant është 28-35% e çmimit të shitjes:',
        items: [
          'Pranimi ditor i mallit: regjistro të gjitha faturat nga furnizuesit (mish, perime, pije) — me datë, sasi dhe çmim',
          'Humbje, dëmtim, prishje: Në hotelieri 2-5% humbje është normale. Regjistro çdo fshirje me procesverbal',
          'Recetimi (normativat): Çdo gjellë duhet të ketë llogaritje normative — sa gram nga çdo përbërës shpenzohet',
          'Inventari: Numërimi mujor i stokut është i detyrueshëm. Dallimi midis stokut kontabël dhe real = humbje ose vjedhje',
          'Metoda FIFO: Ushqimi regjistrohet sipas parimit "i pari hyrë, i pari dalë" — për të parandaluar prishjen',
          'Marzhi sipas produktit: Ndiqni marzhin e çdo gjelle veçanërisht. Makaronat kanë marzh të lartë (~70%), biftekët të ulët (~40%)',
        ],
        steps: null,
      },
      {
        title: 'Bakshishi, ushqimi i punëtorëve dhe përfaqësimi',
        content:
          'Tre fusha ku pronarët e restoranteve gabojnë më shpesh:',
        items: null,
        steps: [
          { step: 'Bakshishi', desc: 'Bakshishi NUK është të ardhur i restorantit — ai është të ardhur personale i punëtorit. Nëse bakshishi mblidhet centralisht dhe shpërndahet, duhet të regjistrohet dhe tatimohet si pjesë e pagës.' },
          { step: 'Ushqimi i punëtorëve', desc: 'Sipas ligjit, ushqimi i punëtorëve është shpenzim i njohur deri në 20% të pagës mesatare. Mbi këtë shumë — shpenzim i panjohur për tatimin mbi fitimin.' },
          { step: 'Përfaqësimi (dreka biznesi)', desc: 'Shpenzim i njohur deri në 1% të të ardhurave totale. Duhet të keni faturë me emrin e mysafirit dhe qëllimin e biznesit. Mbi 1% — shpenzim i panjohur.' },
          { step: 'Konsumi personal i pronarit', desc: 'Nëse pronari ha në restorant, ajo është konsum PERSONAL dhe NUK është shpenzim i njohur. Regjistroni veçanërisht ose lëshoni faturë interne.' },
        ],
      },
      {
        title: 'Punëtorë sezonal dhe me orar të shkurtër',
        content:
          'Hoteleria ka fluktuation të lartë dhe punëtorë sezonal:',
        items: [
          'Kontratë me afat — maksimumi 5 vjet gjithsej (Neni 46 LMP). Për punë sezonale mund të jetë më e shkurtër',
          'MPIN duhet të dorëzohet për ÇDO punëtor, pavarësisht afatit',
          'Paga minimale vlen edhe për punëtorë sezonal — 20.175 MKD bruto (2026)',
          'Orar shtesë: maksimumi 8 orë në javë, 190 orë në vit. Shtesë 40% mbi pagën bazë',
          'Punë natën (22:00-06:00): shtesë 35%. Restorantet punojnë vonë — ky është shpenzim i shpeshtë',
          'Periudhë prove: maksimumi 6 muaj. Gjatë provës — kontribute dhe detyrime MPIN të plota',
          'Minimumi higjenik: Çdo punëtor hotelierik duhet të ketë librezë sanitare — rinovohet çdo vit',
        ],
        steps: null,
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino ka regjim POS të veçantë të dizajnuar për restorante dhe kafene:',
        items: [
          'Regjimi POS me menaxhim tavolinash: Hartë vizuele e tavolinave, porosi të hapura sipas tavolinës, transferim midis tavolinave',
          'KDS (Kitchen Display System): Porositë shfaqen automatikisht në ekranin e kuzhinës',
          'Ndarje automatike e TVSH 10% dhe 18% sipas llojit të zërit',
          'Integrim me printerat fiskalë — 9 modele të mbështetura në Maqedoni',
          'Raporte ditore, javore dhe mujore për qarkullimin, marzhin dhe food cost',
          'Moduli i pagave — llogaritje automatike me orar shtesë, natë dhe bakshish',
          'Plan falas për restorante — filloni pa investim',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'Softuer POS për Maqedoninë: Krahasim' },
      { slug: 'ddv-vodich-mk', title: 'Udhëzues TVSH për Maqedoninë' },
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni' },
    ],
    bottomCta: {
      title: 'POS për restorantin tuaj? Facturino është falas.',
      subtitle: 'Menaxhim tavolinash, KDS, printer fiskal dhe TVSH 10%/18% — gjithçka e përfshirë. Pa abonim mujor.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloğa dön',
    tag: 'Sektör',
    title: 'Restoran Muhasebesi Makedonya: %10 KDV, Kasa ve Maliyet Kontrolü',
    publishDate: '23 Mayıs 2026',
    readTime: '11 dk okuma',
    intro:
      'Restoranlar, kafeler, pizzacılar ve barlar diğer sektörlerde bulunmayan benzersiz muhasebe zorluklarıyla karşı karşıyadır: aynı masada iki farklı KDV oranı, nakit ve kartla günlük kasa, günlük değişen yemek maliyetleri, bahşiş, mevsimlik çalışanlar ve katı fişkal yükümlülükler. Bu rehber, otelcilik işletme sahiplerinin 2026\'da doğru muhasebe hakkında bilmesi gereken her şeyi kapsar.',
    sections: [
      {
        title: '%10 vs %18 KDV: İndirimli oran ne zaman geçerli?',
        content:
          'Bu, otelcilikte en yaygın hata kaynağıdır. KDV Kanunu Md. 30-a kuralı açık, ancak uygulama karmaşıktır:',
        items: [
          'KDV %10 — Restoranda servis edilen yiyecek ve alkolsüz içecekler (yerinde tüketim)',
          'KDV %18 — Alkollü içecekler — HER ZAMAN %18, yerinde veya paket fark etmez',
          'KDV %18 — Paket/teslimat yemekleri — mal satışı olarak kabul edilir, otelcilik hizmeti değil',
          'KDV %18 — Katering — hizmetle birlikte mal satışı olarak kabul edilir, restoran hizmeti değil',
          'Karma siparişler: Müşteri yemek (%10) ve bira (%18) sipariş ederse, her kalem kendi oranıyla faturalanır',
          'Kafede kahve = %10 (yerinde alkolsüz içecek). Paket kahve = %18',
          'ÖNEMLİ: Fişkal cihaz her fişte kalemleri KDV oranına göre doğru ayırmalıdır',
        ],
        steps: null,
      },
      {
        title: 'Fişkal cihaz ve günlük kasa',
        content:
          'Bireylere (B2C) satan her restoran fişkal cihaza sahip olmalıdır:',
        items: null,
        steps: [
          { step: 'Fişkal cihaz zorunludur', desc: 'Fişkalizasyon Kanunu — her B2C satışı fişkal cihazda kayıtlanmalıdır. Fişkal fiş olmadan satış cezası: EUR 2.000-5.000.' },
          { step: 'Günlük rapor (Z-raporu)', desc: 'Her iş gününün sonunda fişkal cihaz Z-raporu (günlük özet) üretir. Bu rapor zorunludur ve 5 yıl saklanmalıdır.' },
          { step: 'Nakit vs kart', desc: 'Nakit ve nakitsiz ödemelerin ayrı kaydı. Günlük banka mevduatı nakit tahsilatlarla eşleşmelidir.' },
          { step: 'İptal ve iade', desc: 'Fişkal fiş iptali YALNIZCA müdür onayı gerektirir. Her iade kayıtlanmalı ve belgelenmelidir.' },
          { step: 'UJP\'ye aylık rapor', desc: 'Fişkal veriler otomatik olarak UJP\'ye iletilir. Fişkal ciro ile KDV beyannamesi arasındaki uyumsuzluk = denetim tetikleyicisi.' },
        ],
      },
      {
        title: 'Yiyecek ve içecek maliyetleri (Food Cost)',
        content:
          'Yemek maliyet kontrolü kârlılığın anahtarıdır. Bir restoran için ideal food cost satış fiyatının %28-35\'idir:',
        items: [
          'Günlük mal girişi: tüm tedarikçi faturalarını kaydedin (et, sebze, içecek) — tarih, miktar ve fiyatla',
          'Fire, bozulma, kırılma: Otelcilikte %2-5 kayıp normaldir. Her silmeyi tutanakla kaydedin',
          'Reçete maliyetlendirme (normlar): Her yemek standart reçete hesabına sahip olmalıdır — her malzemeden kaç gram kullanılır',
          'Envanter: Aylık stok sayımı zorunludur. Defter ve gerçek stok farkı = fire veya hırsızlık',
          'FIFO yöntemi: Yiyecekler "ilk giren, ilk çıkar" prensibine göre kaydedilir — bozulmayı önlemek ve doğru maliyet için',
          'Ürün başına marj: Her yemeğin marjını ayrı takip edin. Makarna yüksek marjlı (~%70), biftek düşük (~%40)',
        ],
        steps: null,
      },
      {
        title: 'Bahşiş, çalışan yemekleri ve temsil giderleri',
        content:
          'Restoran sahiplerinin en sık hata yaptığı üç alan:',
        items: null,
        steps: [
          { step: 'Bahşiş', desc: 'Bahşiş restoranın geliri DEĞİLDİR — çalışanın kişisel geliridir. Bahşişler merkezi olarak toplanıp dağıtılırsa, maaşın parçası olarak kaydedilmeli ve vergilendirilmelidir.' },
          { step: 'Çalışan yemekleri', desc: 'Yasaya göre, çalışan yemekleri ortalama maaşın %20\'sine kadar kabul edilen giderdir. Bu tutarın üzerinde — kurumlar vergisi için kabul edilmeyen gider.' },
          { step: 'Temsil giderleri (iş yemekleri)', desc: 'Toplam gelirin %1\'ine kadar kabul edilen gider. Konuğun adı ve iş amacı olan fatura gerekir. %1 üzerinde — kabul edilmeyen gider.' },
          { step: 'Sahibin kişisel tüketimi', desc: 'Sahip restoranda yemek yerse, bu KİŞİSEL tüketimdir ve kabul edilen gider DEĞİLDİR. Ayrı kaydedin veya dahili fatura düzenleyin.' },
        ],
      },
      {
        title: 'Mevsimlik ve yarı zamanlı çalışanlar',
        content:
          'Otelcilik yüksek devir hızına ve mevsimlik çalışanlara sahiptir:',
        items: [
          'Belirli süreli sözleşme — toplam maksimum 5 yıl (İİK Md. 46). Mevsimlik iş için daha kısa olabilir',
          'MPIN her çalışan için dosyalanmalıdır, belirli veya süresiz sözleşme fark etmez',
          'Asgari ücret mevsimlik çalışanlar için de geçerlidir — 20.175 MKD brüt (2026)',
          'Fazla mesai: haftada maksimum 8 saat, yılda 190 saat. Temel maaş üzerine %40 prim',
          'Gece çalışması (22:00-06:00): %35 prim. Restoranlar geç saatlere kadar çalışır — bu yaygın bir maliyet',
          'Deneme süresi: maksimum 6 ay. Deneme süresinde — tam primler ve MPIN yükümlülükleri',
          'Hijyen minimum: Her otelcilik çalışanı sağlık cüzdanına sahip olmalıdır — yıllık yenilenir',
        ],
        steps: null,
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content:
          'Facturino, restoranlar ve kafeler için tasarlanmış özel bir POS moduna sahiptir:',
        items: [
          'Masa yönetimli POS modu: Görsel masa haritası, masa başına açık siparişler, masalar arası transfer',
          'KDS (Kitchen Display System): Siparişler otomatik olarak mutfak ekranında görünür',
          'Kalem türüne göre %10 ve %18 KDV otomatik ayrımı',
          'Fişkal yazıcı entegrasyonu — Kuzey Makedonya\'da 9 model desteklenir',
          'Günlük, haftalık ve aylık ciro, marj ve food cost raporları',
          'Bordro modülü — fazla mesai, gece vardiyası ve bahşişle otomatik hesaplama',
          'Restoranlar için ücretsiz plan — yatırım olmadan başlayın',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'pos-softver-makedonija', title: 'Makedonya POS Yazılımı: Karşılaştırma' },
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV Rehberi' },
      { slug: 'presmetka-na-plata-mk', title: 'Makedonya\'da Maaş Hesaplama' },
    ],
    bottomCta: {
      title: 'Restoranınız için POS? Facturino ücretsiz.',
      subtitle: 'Masa yönetimi, KDS, fişkal yazıcı ve %10/%18 KDV — hepsi dahil. Aylık abonelik yok.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaRestoraniPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = locale === 'mk' ? 'Блог' : 'Blog'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const articleLd = articleJsonLd({
    locale,
    slug: 'smetkovodstvo-za-restorani',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['ресторан', 'restaurant', 'ДДВ', 'VAT', 'POS', 'food cost', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-restorani` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/blog`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={`/${locale}/blog/${ra.slug}`}
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
