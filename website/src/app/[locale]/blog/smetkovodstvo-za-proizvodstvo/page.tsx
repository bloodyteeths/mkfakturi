import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-proizvodstvo', {
    title: {
      mk: 'Сметководство за производство: BOM, калкулација и залихи — Facturino',
      en: 'Manufacturing Accounting: BOM, Cost Analysis & Inventory — Facturino',
      sq: 'Kontabiliteti i prodhimit: BOM, analizë kostoje dhe inventar — Facturino',
      tr: 'Üretim Muhasebesi: BOM, Maliyet Analizi ve Stok — Facturino',
    },
    description: {
      mk: 'Комплетен водич за сметководство во производство: рецептури (BOM), калкулација на трошоци, вреднување на готови производи по МСС 2, загуби, производни налози и ценовна политика.',
      en: 'Complete guide to manufacturing accounting: bill of materials (BOM), cost allocation, finished goods valuation per IAS 2, wastage, production orders and product pricing.',
      sq: 'Udhezues i plote per kontabilitetin e prodhimit: lista e materialeve (BOM), alokimi i kostove, vleresimi i produkteve te gatshme sipas SNK 2, humbjet, urdheresa prodhimi dhe cmimi.',
      tr: 'Uretim muhasebesi rehberi: urun agaci (BOM), maliyet dagilimi, mamul degerleme (UMS 2), fire, uretim emirleri ve fiyatlandirma.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за производство: BOM, калкулација и залихи',
    publishDate: '23 мај 2026',
    readTime: '12 мин читање',
    intro:
      'Производствените претпријатија во Македонија се соочуваат со специфични сметководствени предизвици: рецептури (BOM) со повеќе нивоа, алокација на директни и индиректни трошоци, вреднување на готови производи, евиденција на загуби и отпад, производни налози и ценовна калкулација. Овој водич покрива сè што производителите треба да знаат за правилно финансиско управување во 2026 — од креирање на рецептура до анализа на рентабилност.',
    sections: [
      {
        title: '1. Рецептура (Bill of Materials — BOM)',
        content:
          'BOM (Bill of Materials) е листа на сите суровини, полупроизводи и компоненти потребни за производство на еден готов производ. Секој производител мора да има точна рецептура за секој производ — без неа, невозможна е правилна калкулација на цена, контрола на залихи и планирање на набавки.',
        items: [
          'Еднонивовски BOM — Едноставни производи со директна листа на суровини (на пр. леб: брашно, квасец, сол, вода)',
          'Повеќенивовски BOM — Комплексни производи каде еден полупроизвод влегува во друг (на пр. тесто → пица → замрзната пица во кутија)',
          'Количини по единица — Точна количина на секоја суровина за производство на 1 единица готов производ (на пр. 0,5 кг брашно за 1 леб)',
          'Мерни единици — Конверзија помеѓу мерни единици (кг → г, л → мл) мора да биде конзистентна низ целиот BOM',
          'Верзионирање — Кога менувате рецептура, зачувајте ја старата верзија за ревизорска трага и споредба на трошоци',
          'Стандардна vs фактичка потрошувачка — Разликата помеѓу нормативната и реалната потрошувачка е клучен индикатор за ефикасност',
        ],
        steps: null,
      },
      {
        title: '2. Алокација на производствени трошоци',
        content:
          'Производствениот трошок се состои од три компоненти. Точната алокација на секоја компонента е основа за правилна калкулација и данок на добивка:',
        items: null,
        steps: [
          { step: 'Директни материјали', desc: 'Суровини кои директно влегуваат во производот (брашно, челик, ткаенина). Се евидентираат по набавна цена користејќи WAC (пондериран просек) или FIFO метод. Facturino автоматски го пресметува WAC при секоја набавка.' },
          { step: 'Директен труд', desc: 'Плати на работници кои директно работат на производната линија. Вклучува основна плата, прекувремена работа (40%), ноќна смена (35%) и придонеси (28%). Се алоцира по производствен налог или по работни часови.' },
          { step: 'Општи производствени трошоци (overhead)', desc: 'Индиректни трошоци: амортизација на машини, одржување, струја, закупнина на погон, контрола на квалитет. Се алоцираат по клуч — најчесто пропорционално на директните работни часови или машинските часови.' },
          { step: 'Стандардна калкулација (Standard Costing)', desc: 'Унапред одредени стандардни трошоци по единица. На крај на период се пресметува варијанса (разлика) помеѓу стандардниот и фактичкиот трошок — позитивна (заштеда) или негативна (прекумерен трошок).' },
          { step: 'Фактичка калкулација (Actual Costing)', desc: 'Реални трошоци евидентирани по секој производствен налог. Попрецизна, но побавна и побарачка за евиденција. Препорачлива за мали серии и нарачки по порачка.' },
        ],
      },
      {
        title: '3. Вреднување на готови производи',
        content:
          'По МСС 2 (Залихи), готовите производи се евидентираат по пониската вредност од набавната цена (cost) и нето реализационата вредност (NRV). Ова е задолжително за сите претпријатија во Македонија кои применуваат МСФИ:',
        items: [
          'Апсорпциона калкулација (Absorption Costing) — Во цената на производот се вклучуваат СИТЕ производствени трошоци: директни материјали + директен труд + фиксни и варијабилни overhead. Ова е задолжителен метод по МСС 2 за екстерно известување',
          'Варијабилна калкулација (Variable Costing) — Само варијабилни трошоци влегуваат во цената. Фиксните overhead се трошок на период. Корисна за интерни одлуки, но НЕ е дозволена за официјални финансиски извештаи',
          'Нето реализациона вредност (NRV) — Очекувана продажна цена минус трошоци за доработка и продажба. Ако NRV < cost, залихите мора да се отпишат до NRV',
          'Производство во тек (WIP) — Недовршени производи кои се наоѓаат на производната линија. WIP = директни материјали потрошени + директен труд уплатен + дел од overhead. На крај на период, WIP се вреднува по степен на завршеност',
          'Евиденција на магацин — Секој влез/излез од магацин за готови производи мора да биде документиран со приемница/издатница',
          'Инвентура — Задолжителна годишна физичка инвентура. Разликата помеѓу книговодствена и фактичка залиха = кало/растур или утврден манко',
        ],
        steps: null,
      },
      {
        title: '4. Загуби, отпад и ко-производство',
        content:
          'Во секој производствен процес постојат загуби. Правилното евидентирање на загубите е критично за точна калкулација и за данок на добивка:',
        items: null,
        steps: [
          { step: 'Нормални загуби (Normal Wastage)', desc: 'Очекуван губиток кој е инхерентен на производствениот процес (на пр. 3% загуба при сечење ткаенина, испарување при готвење). Нормалните загуби се дел од производствениот трошок и се вклучуваат во цената на готовиот производ.' },
          { step: 'Абнормални загуби (Abnormal Wastage)', desc: 'Неочекувани загуби поради дефект, грешка на оператор, расипана суровина. Абнормалните загуби НЕ се вклучуваат во цената на производот — тие се евидентираат како трошок на период (расход во Биланс на успех).' },
          { step: 'Ко-производство (Joint Products)', desc: 'Кога од еден процес настануваат два или повеќе производи (на пр. рафинерија: бензин + дизел + керозин). Заедничките трошоци се алоцираат по метод на релативна продажна вредност или физичка мерка.' },
          { step: 'Нуспроизводи (By-products)', desc: 'Споредни производи со мала вредност (на пр. пилевина при обработка на дрво). Приходот од нуспроизводи се одзема од трошокот на главниот производ или се евидентира како посебен приход.' },
          { step: 'Отпад со вредност (Scrap)', desc: 'Отпаден материјал кој може да се продаде (метален отпад, хартиен рекал). Приходот од отпад ги намалува производствените трошоци. Евидентирајте ја залихата на отпад одделно.' },
        ],
      },
      {
        title: '5. Производни налози',
        content:
          'Производниот налог е централен документ кој го поврзува планирањето, издавањето на суровини, производството и приемот на готови производи:',
        items: null,
        steps: [
          { step: 'Планирање', desc: 'Креирајте производен налог со избор на производ, количина и планиран датум. Системот автоматски ја повлекува рецептурата (BOM) и ги пресметува потребните суровини. Проверете ја достапноста на залихи пред да продолжите.' },
          { step: 'Издавање на суровини', desc: 'Суровините се издаваат од магацинот по издатница. Залихата на суровини автоматски се намалува. Ако нема доволно залиха, системот предупредува — опција за делумно издавање или креирање на нарачка за набавка.' },
          { step: 'Производство', desc: 'За време на производството се евидентираат директните работни часови, машинските часови и евентуалните загуби. Секоја работна станица (work center) има дефинирана капацитетна стапка.' },
          { step: 'Прием на готови производи', desc: 'По завршување на производството, готовите производи се примаат во магацин за готови производи по приемница. Количината може да биде помала од планираната (загуби) или поголема (подобар принос).' },
          { step: 'Анализа на варијанси', desc: 'Споредба помеѓу планираните и фактичките трошоци: варијанса на материјали (количина × цена), варијанса на труд (часови × стапка), варијанса на overhead. Овие извештаи се клучни за подобрување на ефикасноста.' },
        ],
      },
      {
        title: '6. Ценовна политика на производи',
        content:
          'Правилната калкулација на производствен трошок е основа за ценовна политика. Еве ги трите главни пристапи:',
        items: [
          'Cost-plus (трошок + маржа) — Најчест метод. Производствен трошок + посакувана маржа (на пр. 100 МКД трошок + 30% маржа = 130 МКД продажна цена). Едноставен, но не го зема предвид пазарот',
          'Маржа на покритие (Contribution Margin) — Продажна цена минус варијабилни трошоци. Покажува колку секој производ придонесува за покривање на фиксните трошоци и за добивка. Критично за одлуки за асортиман',
          'Точка на рентабилност (Break-even) — Колку единици мора да продадете за да ги покриете сите трошоци (фиксни + варијабилни). Формула: Фиксни трошоци ÷ (Продажна цена − Варијабилен трошок по единица)',
          'Целна калкулација (Target Costing) — Тргнувате од пазарната цена и посакуваната маржа, па назад ги пресметувате максималните дозволени трошоци. Корисно кога пазарот ја диктира цената',
          'Трансферни цени — Ако имате повеќе погони или поврзани друштва, трансферната цена помеѓу нив мора да биде по принцип „на оддалечена рака" (arm\'s length) за даночни цели',
          'ДДВ калкулација — Не заборавајте: продажната цена е БЕЗ ДДВ (18%). За крајниот потрошувач прикажувајте ја цената со вклучен ДДВ',
        ],
        steps: null,
      },
      {
        title: '7. Како Facturino помага',
        content:
          'Facturino има комплетен модул за производство дизајниран за македонски производители:',
        items: [
          'BOM модул — Креирајте рецептури со повеќе нивоа, дефинирајте суровини, полупроизводи и мерни единици. Автоматска пресметка на потребни суровини при креирање на производен налог',
          'Производни налози — Целосен животен циклус: планирање → издавање суровини → производство → прием на готови производи. Следење на статус во реално време',
          'Работни станици (Work Centers) — Дефинирајте ги вашите машини и линии со капацитет, трошок по час и ефикасност. Автоматска алокација на overhead',
          'WAC автоматски — Пондерираниот просечен трошок (WAC) се пресметува автоматски при секоја набавка и при секој производствен налог. Нема рачни пресметки',
          'Извештаи за варијанси — Споредба на стандардни vs фактички трошоци по производ, по налог и по период. Идентификувајте каде губите пари',
          'Евиденција на загуби — Нормални и абнормални загуби се евидентираат одделно. Автоматско книжење: нормални → во цена, абнормални → расход',
          'Ценовна калкулација — Cost-plus, маржа на покритие и точка на рентабилност — сè во еден екран. Симулирајте ценовни сценарија пред да одлучите',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Дигитални нарачки за набавка' },
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи во Facturino' },
      { slug: 'bilans-na-sostojba', title: 'Биланс на состојба: водич' },
    ],
    bottomCta: {
      title: 'Производство? Facturino го поедноставува.',
      subtitle: 'BOM, производни налози, WAC и варијанси — сè автоматски. Регистрирајте се бесплатно.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Manufacturing Accounting: BOM, Cost Analysis & Inventory',
    publishDate: 'May 23, 2026',
    readTime: '12 min read',
    intro:
      'Manufacturing businesses in North Macedonia face unique accounting challenges: multi-level bills of materials (BOM), direct and indirect cost allocation, finished goods valuation, wastage and scrap tracking, production orders and product pricing. This guide covers everything manufacturers need to know about proper financial management in 2026 — from creating a recipe to profitability analysis.',
    sections: [
      {
        title: '1. Bill of Materials (BOM)',
        content:
          'A BOM (Bill of Materials) is a list of all raw materials, semi-finished products and components needed to manufacture one finished product. Every manufacturer must have an accurate BOM for each product — without it, proper cost calculation, inventory control and procurement planning are impossible.',
        items: [
          'Single-level BOM — Simple products with a direct list of raw materials (e.g. bread: flour, yeast, salt, water)',
          'Multi-level BOM — Complex products where one semi-finished product feeds into another (e.g. dough → pizza → frozen pizza in a box)',
          'Quantities per unit — Exact quantity of each raw material needed to produce 1 unit of finished product (e.g. 0.5 kg flour for 1 loaf)',
          'Units of measure — Conversion between units (kg → g, L → mL) must be consistent throughout the BOM',
          'Versioning — When you change a recipe, keep the old version for audit trail and cost comparison',
          'Standard vs actual consumption — The difference between normative and real consumption is a key efficiency indicator',
        ],
        steps: null,
      },
      {
        title: '2. Production Cost Allocation',
        content:
          'Manufacturing cost consists of three components. Accurate allocation of each component is the foundation for proper costing and corporate tax:',
        items: null,
        steps: [
          { step: 'Direct Materials', desc: 'Raw materials that go directly into the product (flour, steel, fabric). Recorded at purchase price using WAC (weighted average cost) or FIFO method. Facturino automatically calculates WAC with every purchase.' },
          { step: 'Direct Labour', desc: 'Wages of workers who work directly on the production line. Includes base salary, overtime (40%), night shift (35%) and contributions (28%). Allocated per production order or per labour hours.' },
          { step: 'Manufacturing Overhead', desc: 'Indirect costs: machine depreciation, maintenance, electricity, factory rent, quality control. Allocated by a cost driver — most commonly proportional to direct labour hours or machine hours.' },
          { step: 'Standard Costing', desc: 'Pre-determined standard costs per unit. At the end of the period, variances (differences) between standard and actual costs are calculated — favourable (savings) or unfavourable (excess cost).' },
          { step: 'Actual Costing', desc: 'Real costs recorded per production order. More precise but slower and more demanding to track. Recommended for small batches and custom orders.' },
        ],
      },
      {
        title: '3. Finished Goods Valuation',
        content:
          'Under IAS 2 (Inventories), finished goods are recorded at the lower of cost and net realisable value (NRV). This is mandatory for all enterprises in North Macedonia applying IFRS:',
        items: [
          'Absorption Costing — ALL manufacturing costs are included in product cost: direct materials + direct labour + fixed and variable overhead. This is the mandatory method under IAS 2 for external reporting',
          'Variable Costing — Only variable costs are included in product cost. Fixed overhead is a period expense. Useful for internal decisions but NOT permitted for official financial statements',
          'Net Realisable Value (NRV) — Expected selling price minus costs to complete and sell. If NRV < cost, inventories must be written down to NRV',
          'Work-in-Progress (WIP) — Unfinished products on the production line. WIP = direct materials consumed + direct labour paid + share of overhead. At period end, WIP is valued by degree of completion',
          'Warehouse records — Every entry/exit from the finished goods warehouse must be documented with a goods receipt/issue note',
          'Stocktake — Mandatory annual physical inventory. The difference between book and actual stock = spoilage or identified shortage',
        ],
        steps: null,
      },
      {
        title: '4. Wastage and Co-production',
        content:
          'Every manufacturing process involves losses. Proper recording of wastage is critical for accurate costing and for corporate tax:',
        items: null,
        steps: [
          { step: 'Normal Wastage', desc: 'Expected loss inherent to the production process (e.g. 3% loss when cutting fabric, evaporation during cooking). Normal wastage is part of the manufacturing cost and is included in the price of the finished product.' },
          { step: 'Abnormal Wastage', desc: 'Unexpected losses due to defects, operator error or spoiled raw materials. Abnormal wastage is NOT included in product cost — it is recorded as a period expense (expense in the Income Statement).' },
          { step: 'Joint Products', desc: 'When two or more products result from a single process (e.g. refinery: petrol + diesel + kerosene). Joint costs are allocated by relative sales value method or physical measure method.' },
          { step: 'By-products', desc: 'Secondary products with small value (e.g. sawdust from wood processing). Revenue from by-products is deducted from the main product cost or recorded as separate income.' },
          { step: 'Scrap', desc: 'Waste material that can be sold (metal scrap, paper offcuts). Scrap revenue reduces manufacturing costs. Track scrap inventory separately.' },
        ],
      },
      {
        title: '5. Production Orders',
        content:
          'The production order is the central document linking planning, material issue, production and receipt of finished goods:',
        items: null,
        steps: [
          { step: 'Planning', desc: 'Create a production order by selecting the product, quantity and planned date. The system automatically pulls the recipe (BOM) and calculates required raw materials. Check stock availability before proceeding.' },
          { step: 'Material Issue', desc: 'Raw materials are issued from the warehouse via an issue note. Raw material stock is automatically reduced. If stock is insufficient, the system warns — option for partial issue or creating a purchase order.' },
          { step: 'Production', desc: 'During production, direct labour hours, machine hours and any wastage are recorded. Each work centre has a defined capacity rate.' },
          { step: 'Finished Goods Receipt', desc: 'After production is complete, finished goods are received into the finished goods warehouse via a receipt note. The quantity may be less than planned (wastage) or more (better yield).' },
          { step: 'Variance Analysis', desc: 'Comparison between planned and actual costs: material variance (quantity x price), labour variance (hours x rate), overhead variance. These reports are key to improving efficiency.' },
        ],
      },
      {
        title: '6. Product Pricing',
        content:
          'Accurate manufacturing cost calculation is the foundation for pricing policy. Here are the three main approaches:',
        items: [
          'Cost-plus — Most common method. Manufacturing cost + desired margin (e.g. 100 MKD cost + 30% margin = 130 MKD selling price). Simple, but does not account for market conditions',
          'Contribution Margin — Selling price minus variable costs. Shows how much each product contributes to covering fixed costs and profit. Critical for product mix decisions',
          'Break-even Point — How many units you must sell to cover all costs (fixed + variable). Formula: Fixed Costs / (Selling Price - Variable Cost per Unit)',
          'Target Costing — Start from the market price and desired margin, then work backwards to calculate maximum allowable costs. Useful when the market dictates the price',
          'Transfer Pricing — If you have multiple plants or related entities, the transfer price between them must follow the arm\'s length principle for tax purposes',
          'VAT calculation — Remember: the selling price is EXCLUDING VAT (18%). For end consumers, display the price with VAT included',
        ],
        steps: null,
      },
      {
        title: '7. How Facturino Helps',
        content:
          'Facturino has a complete manufacturing module designed for Macedonian producers:',
        items: [
          'BOM module — Create multi-level recipes, define raw materials, semi-finished products and units of measure. Automatic calculation of required materials when creating a production order',
          'Production orders — Full lifecycle: planning → material issue → production → finished goods receipt. Real-time status tracking',
          'Work Centres — Define your machines and lines with capacity, cost per hour and efficiency. Automatic overhead allocation',
          'Automatic WAC — Weighted average cost is calculated automatically with every purchase and every production order. No manual calculations',
          'Variance reports — Standard vs actual cost comparison by product, by order and by period. Identify where you are losing money',
          'Wastage tracking — Normal and abnormal wastage recorded separately. Automatic posting: normal → product cost, abnormal → expense',
          'Pricing calculator — Cost-plus, contribution margin and break-even — all on one screen. Simulate pricing scenarios before you decide',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Digital Purchase Orders' },
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management in Facturino' },
      { slug: 'bilans-na-sostojba', title: 'Balance Sheet: A Guide' },
    ],
    bottomCta: {
      title: 'Manufacturing? Facturino simplifies it.',
      subtitle: 'BOM, production orders, WAC and variances — all automatic. Sign up for free.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti i prodhimit: BOM, analiza e kostove dhe inventar',
    publishDate: '23 maj 2026',
    readTime: '12 min lexim',
    intro:
      'Ndermarrjet prodhuese ne Maqedoni perballen me sfida specifike kontabel: lista materialesh (BOM) me shume nivele, alokimi i kostove direkte dhe indirekte, vleresimi i produkteve te gatshme, evidentimi i humbjeve dhe mbeturinave, urdheresa prodhimi dhe llogaritja e cmimit. Ky udhezues mbulon gjithcka qe prodhuesit duhet te dine per menaxhimin e duhur financiar ne 2026 — nga krijimi i recetes deri te analiza e rentabilitetit.',
    sections: [
      {
        title: '1. Lista e materialeve (Bill of Materials — BOM)',
        content:
          'BOM (Bill of Materials) eshte lista e te gjitha lendeve te para, gjysemprodukteve dhe komponenteve te nevojshme per prodhimin e nje produkti te gatshem. Cdo prodhues duhet te kete BOM te sakte per cdo produkt — pa te, llogaritja e duhur e cmimit, kontrolli i inventarit dhe planifikimi i blerjeve jane te pamundura.',
        items: [
          'BOM nje-nivelor — Produkte te thjeshta me liste direkte te lendeve te para (p.sh. buke: miell, maja, kripe, uje)',
          'BOM shume-nivelor — Produkte komplekse ku nje gjysemprodukt hyn ne tjetrin (p.sh. brume → pica → pica e ngrire ne kuti)',
          'Sasira per njesi — Sasia e sakte e cdo lende te pare per prodhimin e 1 njesi produkti te gatshem (p.sh. 0,5 kg miell per 1 buke)',
          'Njesite matese — Konvertimi midis njesi matese (kg → g, L → mL) duhet te jete konsistent ne te gjithe BOM-in',
          'Versionimi — Kur ndryshoni receten, ruani versionin e vjeter per gjurme auditimi dhe krahasim kostosh',
          'Konsumi standard vs real — Dallimi midis konsumit normativ dhe real eshte tregues kryesor i efikasitetit',
        ],
        steps: null,
      },
      {
        title: '2. Alokimi i kostove te prodhimit',
        content:
          'Kostoja e prodhimit perbehet nga tre komponente. Alokimi i sakte i seciles komponente eshte baza per llogaritjen e duhur dhe tatimin mbi fitimin:',
        items: null,
        steps: [
          { step: 'Materialet direkte', desc: 'Lendet e para qe hyjne drejtperdrejt ne produkt (miell, celik, pelhure). Regjistrohen me cmimin e blerjes duke perdorur WAC (kostoja mesatare e ponderuar) ose metoden FIFO. Facturino e llogarit automatikisht WAC me cdo blerje.' },
          { step: 'Puna direkte', desc: 'Pagat e punetoreve qe punojne drejtperdrejt ne linjen e prodhimit. Perfshine pagen baze, orar shtese (40%), naten (35%) dhe kontributet (28%). Alokohet per urdher prodhimi ose per ore pune.' },
          { step: 'Shpenzimet e pergjithshme te prodhimit (overhead)', desc: 'Kosto indirekte: amortizimi i makinerive, mirembajtja, energjia elektrike, qiraja e impiantit, kontrolli i cilesise. Alokohen me nje celes — me shpesh ne menyre proporcionale me oret e punes direkte ose oret e makinerise.' },
          { step: 'Kostoja standarde (Standard Costing)', desc: 'Kosto standarde te paracaktuara per njesi. Ne fund te periudhes llogariten variancet (dallimet) midis kostos standarde dhe asaj reale — te favorshme (kursime) ose te pafavorshme (kosto e tepert).' },
          { step: 'Kostoja reale (Actual Costing)', desc: 'Kosto reale te regjistruara per cdo urdher prodhimi. Me e sakta, por me e ngadalta dhe me kerguese per evidencion. Rekomandohet per serite e vogla dhe porosite sipas porosise.' },
        ],
      },
      {
        title: '3. Vleresimi i produkteve te gatshme',
        content:
          'Sipas SNK 2 (Inventaret), produktet e gatshme regjistrohen me vleren me te ulet midis kostos dhe vleres neto te realizueshme (NRV). Kjo eshte e detyruar per te gjitha ndermarrjet ne Maqedoni qe zbatojne SNRF:',
        items: [
          'Kostoja e absorbimit (Absorption Costing) — TE GJITHA kostot e prodhimit perfshihen ne cmimin e produktit: materialet direkte + puna direkte + overhead fikse dhe variabile. Kjo eshte metoda e detyruar sipas SNK 2 per raportimin e jashtem',
          'Kostoja variabile (Variable Costing) — Vetem kostot variabile perfshihen ne cmimin e produktit. Overhead-i fiks eshte shpenzim periudhe. E dobishme per vendimet interne, por NUK lejohet per pasqyrat zyrtare financiare',
          'Vlera neto e realizueshme (NRV) — Cmimi i pritur i shitjes minus kostot per perfundimin dhe shitjen. Nese NRV < kosto, inventaret duhet te fshihen deri ne NRV',
          'Prodhimi ne proces (WIP) — Produkte te paperfunduara ne linjen e prodhimit. WIP = materialet direkte te konsumura + puna direkte e paguar + pjesa e overhead-it. Ne fund te periudhes, WIP vleresohet sipas shkales se perfundimit',
          'Evidenca e magazines — Cdo hyrje/dalje nga magazina e produkteve te gatshme duhet te dokumentohet me fletehyrje/fletadalje',
          'Inventarizimi — Inventarizimi fizik vjetor i detyrueshem. Dallimi midis stokut kontabel dhe real = humbje ose mungese e identifikuar',
        ],
        steps: null,
      },
      {
        title: '4. Humbjet, mbeturinat dhe ko-prodhimi',
        content:
          'Ne cdo proces prodhimi ka humbje. Regjistrimi i duhur i humbjeve eshte kritik per llogaritjen e sakte dhe per tatimin mbi fitimin:',
        items: null,
        steps: [
          { step: 'Humbjet normale (Normal Wastage)', desc: 'Humbje e pritur qe eshte e natyrshme per procesin e prodhimit (p.sh. 3% humbje gjate prerjes se pelhures, avullimi gjate gatimit). Humbjet normale jane pjese e kostos se prodhimit dhe perfshihen ne cmimin e produktit te gatshem.' },
          { step: 'Humbjet anormale (Abnormal Wastage)', desc: 'Humbje te papritura per shkak te defekteve, gabimit te operatorit, lendes se pare te prishur. Humbjet anormale NUK perfshihen ne cmimin e produktit — regjistrohen si shpenzim periudhe (shpenzim ne Pasqyren e te Ardhurave).' },
          { step: 'Ko-produkte (Joint Products)', desc: 'Kur nga nje proces rezultojne dy ose me shume produkte (p.sh. rafineri: benzine + nafte + kerozine). Kostot e perbashketa alokohen me metoden e vleres relative te shitjes ose mases fizike.' },
          { step: 'Nusprodukte (By-products)', desc: 'Produkte dytesor me vlere te vogel (p.sh. tallashi nga perpunimi i drurit). Te ardhurat nga nusproduktet zbriten nga kosto e produktit kryesor ose regjistrohen si te ardhur te vecanta.' },
          { step: 'Mbeturina me vlere (Scrap)', desc: 'Material mbetje qe mund te shitet (mbetje metali, copa letre). Te ardhurat nga mbeturinat zvogelojne kostot e prodhimit. Ndiqni inventarin e mbeturinave vecanerisht.' },
        ],
      },
      {
        title: '5. Urdheresa prodhimi',
        content:
          'Urdheri i prodhimit eshte dokumenti qendror qe lidh planifikimin, leshimin e materialeve, prodhimin dhe pranimin e produkteve te gatshme:',
        items: null,
        steps: [
          { step: 'Planifikimi', desc: 'Krijoni urdher prodhimi duke zgjedhur produktin, sasine dhe daten e planifikuar. Sistemi automatikisht terheq receten (BOM) dhe llogarit materialet e nevojshme. Kontrolloni disponueshmerine e stokut para se te vazhdoni.' },
          { step: 'Leshimi i materialeve', desc: 'Lendet e para leshohen nga magazina me fletadalje. Stoku i lendeve te para zvogelohet automatikisht. Nese stoku eshte i pamjaftueshem, sistemi paralajmeron — mundesi per leshim te pjesshem ose krijim porosi blerje.' },
          { step: 'Prodhimi', desc: 'Gjate prodhimit regjistrohen oret e punes direkte, oret e makinerise dhe humbjet eventuale. Cdo qender pune (work center) ka norme kapaciteti te percaktuara.' },
          { step: 'Pranimi i produkteve te gatshme', desc: 'Pas perfundimit te prodhimit, produktet e gatshme pranohen ne magazinen e produkteve te gatshme me fletehyrje. Sasia mund te jete me e vogel se e planifikuara (humbje) ose me e madhe (rendiment me i mire).' },
          { step: 'Analiza e varianceve', desc: 'Krahasimi midis kostove te planifikuara dhe reale: varianca e materialeve (sasi x cmim), varianca e punes (ore x norme), varianca e overhead-it. Keto raporte jane celes per permiresimin e efikasitetit.' },
        ],
      },
      {
        title: '6. Cmimi i produkteve',
        content:
          'Llogaritja e sakte e kostos se prodhimit eshte baza per politiken e cmimeve. Ja tre qasjet kryesore:',
        items: [
          'Cost-plus (kosto + marzh) — Metoda me e shpeshte. Kostoja e prodhimit + marzhi i deshiruar (p.sh. 100 MKD kosto + 30% marzh = 130 MKD cmim shitje). E thjeshte, por nuk merr parasysh tregun',
          'Marzhi i kontributit (Contribution Margin) — Cmimi i shitjes minus kostot variabile. Tregon sa cdo produkt kontribuon per mbulimin e kostove fikse dhe per fitimin. Kritik per vendimet e asortimentit',
          'Pika e rentabilitetit (Break-even) — Sa njesi duhet te shisni per te mbulur te gjitha kostot (fikse + variabile). Formula: Kostot fikse / (Cmimi i shitjes - Kosto variabile per njesi)',
          'Kostoja e synuar (Target Costing) — Filloni nga cmimi i tregut dhe marzhi i deshiruar, pastaj llogaritni mbrapsht kostot maksimale te lejuara. E dobishme kur tregu e dikton cmimin',
          'Cmimet e transferimit — Nese keni shume impiante ose entitete te lidhura, cmimi i transferimit midis tyre duhet te ndiqe parimin "arm\'s length" per qellime tatimore',
          'Llogaritja e TVSH — Mos harroni: cmimi i shitjes eshte PA TVSH (18%). Per konsumatorin final, shfaqni cmimin me TVSH te perfshire',
        ],
        steps: null,
      },
      {
        title: '7. Si ndihmon Facturino',
        content:
          'Facturino ka modul te plote prodhimi te dizajnuar per prodhuesit maqedonas:',
        items: [
          'Moduli BOM — Krijoni receta me shume nivele, percaktoni lendet e para, gjysemproduktet dhe njesite matese. Llogaritje automatike e materialeve te nevojshme kur krijohet urdher prodhimi',
          'Urdheresa prodhimi — Cikli i plote jetesor: planifikim → leshim materialesh → prodhim → pranim i produkteve te gatshme. Gjurmim i statusit ne kohe reale',
          'Qendrat e punes (Work Centers) — Percaktoni makinerite dhe linjat tuaja me kapacitet, kosto per ore dhe efikasitet. Alokim automatik i overhead-it',
          'WAC automatik — Kostoja mesatare e ponderuar llogaritet automatikisht me cdo blerje dhe cdo urdher prodhimi. Pa llogaritje manuale',
          'Raporte variancesh — Krahasim i kostos standarde vs reale sipas produktit, urdherit dhe periudhes. Identifikoni ku po humbni para',
          'Gjurmim i humbjeve — Humbjet normale dhe anormale regjistrohen vecanerisht. Regjistrim automatik: normale → ne kosto, anormale → shpenzim',
          'Llogarites cmimi — Cost-plus, marzh kontributi dhe pika e rentabilitetit — te gjitha ne nje ekran. Simuloni skenare cmimesh para se te vendosni',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Porosi dixhitale blerje' },
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve ne Facturino' },
      { slug: 'bilans-na-sostojba', title: 'Bilanci: udhezues' },
    ],
    bottomCta: {
      title: 'Prodhim? Facturino e thjeshton.',
      subtitle: 'BOM, urdheresa prodhimi, WAC dhe varianca — te gjitha automatikisht. Regjistrohuni falas.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Sektor',
    title: 'Uretim Muhasebesi: BOM, Maliyet Analizi ve Stok',
    publishDate: '23 Mayis 2026',
    readTime: '12 dk okuma',
    intro:
      'Kuzey Makedonya\'daki uretim isletmeleri benzersiz muhasebe zorluklariyla karsi karsiya: cok seviyeli urun agaclari (BOM), dogrudan ve dolayli maliyet dagilimi, mamul degerleme, fire ve hurda takibi, uretim emirleri ve urun fiyatlandirmasi. Bu rehber, ureticilerin 2026\'da dogru finansal yonetim hakkinda bilmesi gereken her seyi kapsar — recete olusturmadan karlilik analizine.',
    sections: [
      {
        title: '1. Urun Agaci (Bill of Materials — BOM)',
        content:
          'BOM (Bill of Materials), bir mamul urunun uretimi icin gereken tum hammaddeler, yari mamul ve bilesenlerin listesidir. Her uretici, her urun icin dogru bir BOM\'a sahip olmalidir — onsuz dogru maliyet hesaplama, stok kontrolu ve satin alma planlamasi mumkun degildir.',
        items: [
          'Tek seviyeli BOM — Dogrudan hammadde listesi olan basit urunler (orn. ekmek: un, maya, tuz, su)',
          'Cok seviyeli BOM — Bir yari mamulin digerine girdigi karmasik urunler (orn. hamur → pizza → kutulu dondurulmus pizza)',
          'Birim basina miktarlar — 1 birim mamul uretmek icin gereken her hammaddenin tam miktari (orn. 1 ekmek icin 0,5 kg un)',
          'Olcu birimleri — Birimler arasi donusum (kg → g, L → mL) tum BOM boyunca tutarli olmalidir',
          'Versiyonlama — Receteyi degistirdiginizde, denetim izi ve maliyet karsilastirmasi icin eski versiyonu saklayin',
          'Standart vs gercek tuketim — Normatif ve gercek tuketim arasindaki fark, verimlilik icin anahtar gostergedir',
        ],
        steps: null,
      },
      {
        title: '2. Uretim Maliyet Dagilimi',
        content:
          'Uretim maliyeti uc bilesenden olusur. Her bilesenin dogru dagilimi, uygun maliyetlendirme ve kurumlar vergisi icin temeldir:',
        items: null,
        steps: [
          { step: 'Dogrudan Malzemeler', desc: 'Urune dogrudan giren hammaddeler (un, celik, kumas). WAC (agirlikli ortalama maliyet) veya FIFO yontemiyle satin alma fiyatindan kaydedilir. Facturino her satin almada WAC\'i otomatik hesaplar.' },
          { step: 'Dogrudan Iscilik', desc: 'Uretim hattinda dogrudan calisan iscilerin ucretleri. Temel ucret, fazla mesai (%40), gece vardiyasi (%35) ve primler (%28) dahildir. Uretim emri basina veya iscilik saati basina dagitilir.' },
          { step: 'Genel Uretim Giderleri (Overhead)', desc: 'Dolayli maliyetler: makine amortismani, bakim, elektrik, fabrika kirasi, kalite kontrol. Bir maliyet suruculeyiciye gore dagitilir — en yaygin olarak dogrudan iscilik saatleri veya makine saatleriyle orantili.' },
          { step: 'Standart Maliyetleme', desc: 'Birim basina onceden belirlenmis standart maliyetler. Donem sonunda standart ve gercek maliyetler arasindaki sapma (varyans) hesaplanir — olumlu (tasarruf) veya olumsuz (asiri maliyet).' },
          { step: 'Gercek Maliyetleme', desc: 'Her uretim emri basina kaydedilen gercek maliyetler. Daha kesin ama daha yavas ve takibi daha zahmetli. Kucuk partiler ve ozel siparisler icin onerilir.' },
        ],
      },
      {
        title: '3. Mamul Degerleme',
        content:
          'UMS 2 (Stoklar) kapsaminda, mamul urunler maliyet ve net gerceklesilebilir degerin (NRV) dusuk olanindan kaydedilir. Bu, Kuzey Makedonya\'da UFRS uygulayan tum isletmeler icin zorunludur:',
        items: [
          'Tam Maliyet (Absorption Costing) — TUM uretim maliyetleri urun maliyetine dahil edilir: dogrudan malzemeler + dogrudan iscilik + sabit ve degisken overhead. UMS 2 kapsaminda dis raporlama icin zorunlu yontemdir',
          'Degisken Maliyet (Variable Costing) — Sadece degisken maliyetler urun maliyetine dahil edilir. Sabit overhead donem gideridir. Ic kararlar icin faydali ancak resmi finansal tablolar icin izin verilmez',
          'Net Gerceklesilebilir Deger (NRV) — Beklenen satis fiyati eksi tamamlama ve satma maliyetleri. NRV < maliyet ise, stoklar NRV\'ye yazilmalidir',
          'Yari Mamul (WIP) — Uretim hattindaki tamamlanmamis urunler. WIP = tuketilen dogrudan malzeme + odenen dogrudan iscilik + overhead payi. Donem sonunda WIP, tamamlanma derecesine gore degerlanir',
          'Depo kayitlari — Mamul deposundan her giris/cikis, giris/cikis fisiyle belgelenmeli',
          'Sayim — Zorunlu yillik fiziksel envanter. Defter ve gercek stok arasi fark = fire veya tespit edilen eksik',
        ],
        steps: null,
      },
      {
        title: '4. Fire, Hurda ve Ortak Uretim',
        content:
          'Her uretim surecinde kayiplar vardir. Firenin dogru kaydedilmesi, dogru maliyetlendirme ve kurumlar vergisi icin kritiktir:',
        items: null,
        steps: [
          { step: 'Normal Fire', desc: 'Uretim surecinin dogasinda olan beklenen kayip (orn. kumas kesiminde %3 kayip, pisirmede buharlasma). Normal fire, uretim maliyetinin bir parcasidir ve mamul urunun fiyatina dahil edilir.' },
          { step: 'Anormal Fire', desc: 'Kusurlar, operator hatasi veya bozulmus hammadde nedeniyle beklenmeyen kayiplar. Anormal fire, urun maliyetine dahil edilMEZ — donem gideri olarak kaydedilir (Gelir Tablosu\'nda gider).' },
          { step: 'Ortak Urunler (Joint Products)', desc: 'Tek bir surecten iki veya daha fazla urun elde edildiginde (orn. rafineri: benzin + dizel + gazyagi). Ortak maliyetler, goreeli satis degeri yontemi veya fiziksel olcu yontemiyle dagitilir.' },
          { step: 'Yan Urunler (By-products)', desc: 'Dusuk degerli ikincil urunler (orn. ahsap islemeden talasvs). Yan urun geliri, ana urun maliyetinden dusulur veya ayri gelir olarak kaydedilir.' },
          { step: 'Hurda', desc: 'Satilabiilen atik malzeme (metal hurda, kagit kirpintilari). Hurda geliri uretim maliyetlerini azaltir. Hurda envanterini ayri takip edin.' },
        ],
      },
      {
        title: '5. Uretim Emirleri',
        content:
          'Uretim emri, planlamayi, malzeme cikisini, uretimi ve mamul girisi birbirine baglayan merkezi belgedir:',
        items: null,
        steps: [
          { step: 'Planlama', desc: 'Urun, miktar ve planlanan tarih secerek uretim emri olusturun. Sistem otomatik olarak receteyi (BOM) ceker ve gereken hammaddeleri hesaplar. Devam etmeden once stok durumunu kontrol edin.' },
          { step: 'Malzeme Cikisi', desc: 'Hammaddeler, cikis fisiyle depodan verilir. Hammadde stoku otomatik azalir. Stok yetersizse sistem uyarir — kismi cikis veya satin alma siparisi olusturma secenegi.' },
          { step: 'Uretim', desc: 'Uretim sirasinda dogrudan iscilik saatleri, makine saatleri ve olasi fireler kaydedilir. Her is merkezi (work center) tanimli kapasite oranina sahiptir.' },
          { step: 'Mamul Girisi', desc: 'Uretim tamamlandiktan sonra, mamuller giris fisiyle mamul deposuna alinir. Miktar, planlanandan az (fire) veya fazla (daha iyi verim) olabilir.' },
          { step: 'Varyans Analizi', desc: 'Planlanan ve gercek maliyetler arasi karsilastirma: malzeme varyansi (miktar x fiyat), iscilik varyansi (saat x oran), overhead varyansi. Bu raporlar verimliligi artirmanin anahtaridir.' },
        ],
      },
      {
        title: '6. Urun Fiyatlandirma',
        content:
          'Dogru uretim maliyet hesaplamasi, fiyatlandirma politikasinin temelidir. Iste uc temel yaklasim:',
        items: [
          'Maliyet-arti (Cost-plus) — En yaygin yontem. Uretim maliyeti + istenen marj (orn. 100 MKD maliyet + %30 marj = 130 MKD satis fiyati). Basit, ancak piyasa kosullarini dikkate almaz',
          'Katki Marji (Contribution Margin) — Satis fiyati eksi degisken maliyetler. Her urunun sabit maliyetleri karsilamaya ve kara ne kadar katki sagladigini gosterir. Urun karmasimi kararlari icin kritik',
          'Basabas Noktasi (Break-even) — Tum maliyetleri (sabit + degisken) karsilamak icin kac birim satmaniz gerekir. Formul: Sabit Maliyetler / (Satis Fiyati - Birim Basina Degisken Maliyet)',
          'Hedef Maliyetleme (Target Costing) — Piyasa fiyati ve istenen marjdan baslayip, izin verilen maksimum maliyetleri geriye dogru hesaplayin. Piyasanin fiyati belirledigi durumlarda faydali',
          'Transfer Fiyatlandirma — Birden fazla tesisiniz veya iliskili sirketiniz varsa, aralarindaki transfer fiyati vergi amaciyla "emsal bedel" (arm\'s length) ilkesine uygun olmalidir',
          'KDV hesaplamasi — Unutmayin: satis fiyati KDV HARIC\'tir (%18). Son tuketici icin KDV dahil fiyati gosterin',
        ],
        steps: null,
      },
      {
        title: '7. Facturino Nasil Yardimci Olur',
        content:
          'Facturino, Makedon ureticiler icin tasarlanmis eksiksiz bir uretim modulune sahiptir:',
        items: [
          'BOM modulu — Cok seviyeli receteler olusturun, hammaddeler, yari mamuller ve olcu birimleri tanimlayin. Uretim emri olusturulurken gereken malzemelerin otomatik hesaplanmasi',
          'Uretim emirleri — Tam yasam dongusu: planlama → malzeme cikisi → uretim → mamul girisi. Gercek zamanli durum takibi',
          'Is Merkezleri (Work Centers) — Makinelerinizi ve hatlarinizi kapasite, saat basina maliyet ve verimlilikle tanimlayin. Otomatik overhead dagilimi',
          'Otomatik WAC — Agirlikli ortalama maliyet her satin alma ve uretim emriyle otomatik hesaplanir. Manuel hesaplama yok',
          'Varyans raporlari — Urun, siparis ve donem bazinda standart ve gercek maliyet karsilastirmasi. Nerede para kaybetitiginizi belirleyin',
          'Fire takibi — Normal ve anormal fire ayri ayri kaydedilir. Otomatik muhasebe kaydi: normal → urun maliyeti, anormal → gider',
          'Fiyat hesaplayici — Maliyet-arti, katki marji ve basabas — hepsi tek ekranda. Karar vermeden once fiyat senaryolarini simule edin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili yazilar',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Dijital Satin Alma Siparisleri' },
      { slug: 'upravljanje-so-rashodi', title: 'Facturino\'da Gider Yonetimi' },
      { slug: 'bilans-na-sostojba', title: 'Bilanco: Rehber' },
    ],
    bottomCta: {
      title: 'Uretim? Facturino kolaylastirir.',
      subtitle: 'BOM, uretim emirleri, WAC ve varyanslar — hepsi otomatik. Ucretsiz kaydolun.',
      cta: 'Ucretsiz baslayin →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaProizvodstvoPage({
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
    slug: 'smetkovodstvo-za-proizvodstvo',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['manufacturing', 'BOM', 'production', 'cost accounting', 'inventory', 'IAS 2', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-proizvodstvo` },
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
