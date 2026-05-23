import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-gradeznistvo', {
    title: {
      mk: 'Сметководство за градежништво: ситуации, материјали и подизведувачи',
      en: 'Construction Accounting Macedonia: Progress Billing, Materials & Subcontractors',
      sq: 'Kontabiliteti për ndërtimtari: situacione, materiale dhe nënkontraktorë',
      tr: 'İnşaat Muhasebesi Makedonya: Hakediş, Malzeme ve Taşeronlar',
    },
    description: {
      mk: 'Комплетен водич за сметководство во градежништво: ситуации и привремени ситуации, материјали по ПСЦ метод, подизведувачи, ДДВ 18% и 5%, аванси и долгорочни проекти по МСФИ 15.',
      en: 'Complete guide to construction accounting in North Macedonia: progress billing, WAC material tracking, subcontractors, 18% and 5% VAT, advance invoices, and multi-year project revenue recognition under IFRS 15.',
      sq: 'Udhëzues i plotë për kontabilitetin në ndërtimtari: situacione, materiale me metodën PMP, nënkontraktorë, TVSH 18% dhe 5%, paradhënie dhe projekte shumëvjeçare sipas SNRF 15.',
      tr: 'İnşaat muhasebesi rehberi: hakediş, PMA malzeme takibi, taşeronlar, %18 ve %5 KDV, avans faturalar ve UFRS 15 kapsamında çok yıllı proje gelir tanıma.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за градежништво: ситуации, материјали и подизведувачи',
    publishDate: '23 мај 2026',
    readTime: '13 мин читање',
    intro:
      'Градежништвото е еден од најкомплексните сектори за сметководствено водење: наплатата се врши по завршени фази (ситуации), материјалите поминуваат низ магацин и градилиште, подизведувачите имаат свои фактурни обврски, а ДДВ стапката зависи од типот на купувач. Проектите траат со месеци или години, што бара правилно распределување на приходи и трошоци. Овој водич покрива сè што градежните компании во Македонија треба да знаат за правилно книговодство во 2026.',
    sections: [
      {
        title: 'Ситуации (Progress Billing) — наплата по завршени фази',
        content:
          'Во градежништвото не се фактурира целата вредност одеднаш. Наплатата се врши преку привремени ситуации кои ги потврдува надзорот:',
        items: [
          'Привремена ситуација — документ кој ги прикажува завршените работи во одреден период (обично месечно). Ја потпишуваат изведувачот, надзорот и инвеститорот',
          'Конечна ситуација — финален пресмет по завршување на целиот проект. Ги вклучува сите претходни привремени ситуации и ја утврдува крајната вредност',
          'Задршка (ретенција) — инвеститорот задржува 5-10% од секоја ситуација како гаранција за квалитет. Се исплаќа по гарантниот рок (обично 12-24 месеци)',
          'Авансна ситуација — ако инвеститорот уплатил аванс, секоја привремена ситуација ја намалува авансната обврска пропорционално',
          'Надзорен инженер — мора да ја потврди секоја ситуација пред да стане основа за фактурирање. Без потврда = нема фактура',
          'Книговодствено: Секоја ситуација генерира побарување (класа 12) и приход (класа 6). Задршката е посебна сметка (побарување за гаранции)',
        ],
        steps: null,
      },
      {
        title: 'Евиденција на материјали — набавка, магацин и градилиште',
        content:
          'Материјалите се најголемата трошковна ставка во градежништвото (40-60% од вредноста на проектот). Правилната евиденција е критична:',
        items: null,
        steps: [
          { step: 'Набавка и прием', desc: 'Секоја набавка се евидентира со приемница и фактура. Цената мора да ги вклучи транспортот и утоварот. Книжење: задолжување сметка 302 (Материјали на залиха), одобрување 220 (Обврски кон добавувачи).' },
          { step: 'ПСЦ (WAC) метод за вреднување', desc: 'Македонија ја користи просечно-пондерираната цена (ПСЦ/WAC). При секоја нова набавка, просечната цена се пресметува повторно: (постоечка вредност + нова набавка) / вкупна количина.' },
          { step: 'Пренос од магацин на градилиште', desc: 'Кога материјалот оди на градилиште, се издава интерна испратница. Книжење: задолжување 302-2 (Материјали на градилиште), одобрување 302-1 (Материјали во магацин).' },
          { step: 'Норми за потрошувачка и кало', desc: 'Секој тип на работа има норматив за потрошувачка (пр. 0.4 м³ бетон за 1 м² ѕид). Потрошувачка над нормата = кало или расипување. Дозволено кало: цемент 1%, арматура 2%, дрво 3%.' },
          { step: 'Месечна инвентура на градилиште', desc: 'На крајот од секој месец, одговорниот на градилиштето прави попис на неупотребените материјали. Разликата помеѓу книговодствена и фактичка состојба мора да се документира.' },
        ],
      },
      {
        title: 'Подизведувачи — фактури, задршки и ИОС',
        content:
          'Градежните компании работат со десетици подизведувачи. Секој подизведувач носи свои сметководствени обврски:',
        items: [
          'Договор за подизведба — мора да содржи: обем на работа, единечни цени, рок, гарантен рок и процент на задршка. Без договор = ризик при даночна контрола',
          'Фактура од подизведувач — мора да ја прати секоја привремена ситуација. Проверете дали подизведувачот е ДДВ обврзник — ако е, фактурата мора да содржи ДДВ 18%',
          'Задршка кон подизведувачи — стандардно 5-10% се задржува како гаранција. Се евидентира на посебна сметка (обврска за гаранции)',
          'ИОС усогласување — На крајот од секој квартал, направете ИОС (Извод на Отворени Ставки) со секој подизведувач. Неусогласен ИОС = ризик при ревизија',
          'Компензација — Ако подизведувачот ви должи (пр. за материјал што го добил), направете компензација за да ги затворите меѓусебните обврски',
          'Даночни обврски — Ако ангажирате физичко лице (не фирма) како подизведувач, вие сте должни да платите придонеси и персонален данок (договор за дело)',
        ],
        steps: null,
      },
      {
        title: 'ДДВ специфики за градежништво',
        content:
          'Градежништвото има неколку ДДВ специфики кои не постојат во другите сектори:',
        items: null,
        steps: [
          { step: 'Стандардна стапка 18%', desc: 'Сите градежни работи (градба, реновирање, одржување) подлежат на ДДВ 18%. Фактурата се издава при секоја привремена ситуација.' },
          { step: 'Намалена стапка 5% за прв стан', desc: 'По Чл. 30 ЗДДВ, купувачи на прв стан за живеење плаќаат ДДВ 5% (наместо 18%). Услов: купувачот да нема друг имот и становот да е до 120 м². Изведувачот ја пресметува пониската стапка директно на фактурата.' },
          { step: 'Reverse charge за странски подизведувачи', desc: 'Ако ангажирате подизведувач од странство (пр. за специјализирана инсталација), важи механизмот за пренесено оданочување (reverse charge). Вие го пресметувате и плаќате ДДВ-то во Македонија.' },
          { step: 'Право на одбивка на влезен ДДВ', desc: 'Целиот влезен ДДВ на материјали, подизведувачи и опрема е одбитен — под услов да имате валидна фактура и да сте ДДВ обврзник.' },
          { step: 'ДДВ на аванс', desc: 'При примање на аванс, задолжително издавате авансна фактура со ДДВ. ДДВ-то на авансот се пријавува во периодот кога е примен авансот, не кога е завршена работата.' },
        ],
      },
      {
        title: 'Авансни фактури — примање и пресметување',
        content:
          'Авансите се стандардна пракса во градежништвото. Инвеститорот обично плаќа 10-30% аванс пред почеток на работа:',
        items: [
          'Авансна фактура (авансна ситуација) — се издава штом се прими авансот. Мора да содржи ДДВ и да биде евидентирана во ДДВ пријавата за тој период',
          'Книжење на примен аванс: задолжување 240 (Жиро сметка), одобрување 225 (Примени аванси). ДДВ: задолжување 225, одобрување 470 (ДДВ обврска)',
          'Пресметка со конечна фактура — кога ситуацијата ги надмине авансните уплати, се издава фактура за разликата. Претходно фактуриран ДДВ на аванс се одзема',
          'Повеќе аванси — ако проектот има повеќе фази со различни аванси, секој аванс се следи одделно и се пресметува при соодветната ситуација',
          'Неискористен аванс — ако проектот заврши со неискористен аванс, разликата се враќа или се пренесува на нов проект (со писмена согласност)',
          'ВАЖНО: Авансната фактура НЕ е профактура. Авансната фактура е полноправен даночен документ со ДДВ обврска',
        ],
        steps: null,
      },
      {
        title: 'Долгорочни проекти — МСФИ 15 и распределба на приходи',
        content:
          'Градежните проекти кои траат повеќе од 12 месеци имаат специфични барања за признавање на приходи и трошоци:',
        items: null,
        steps: [
          { step: 'МСФИ 15 — Приходи од договори со купувачи', desc: 'Приходот се признава по степен на завршеност (input или output метод). Не чекате проектот да заврши — приходот го книжите пропорционално на завршените работи.' },
          { step: 'Метод на степен на завршеност', desc: 'Input метод: приход = (вкупни настанати трошоци / проценети вкупни трошоци) × договорена цена. Output метод: приход по привремени ситуации потврдени од надзор.' },
          { step: 'Трошоци по проект (cost center)', desc: 'Секој проект е посебен центар на трошоци. Сите директни трошоци (материјали, работна рака, подизведувачи, механизација) се книжат по проект. Индиректни трошоци (администрација, канцеларија) се распределуваат по клуч.' },
          { step: 'Привремено финансирање', desc: 'Кога трошоците го надминуваат фактурираното (work in progress > billed), разликата е актива — „Договорна актива" (contract asset). Обратно = „Договорна обврска" (contract liability).' },
          { step: 'Ревизија на проценка', desc: 'На крајот од секој период, проценетите вкупни трошоци се ревидираат. Ако проектот станал понеповолен (загуба), целата очекувана загуба се признава ВЕДНАШ — не се чека крај на проект.' },
        ],
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino е дизајниран за компании кои работат со набавки, материјали и повеќе проекти истовремено:',
        items: [
          'Нарачки за набавка (Purchase Orders) — креирајте нарачки до добавувачи, следете ги статусите и автоматски генерирајте приемници',
          'Евиденција на материјали по ПСЦ метод — автоматска пресметка на просечно-пондерирана цена при секоја набавка',
          'Центри на трошоци по проект — распределете ги сите трошоци (материјали, плати, подизведувачи) по проект и следете ја профитабилноста',
          'ИОС усогласување со подизведувачи — генерирајте и испратете ИОС автоматски, следете ги неусогласените ставки',
          'Авансни фактури — издавајте авансни фактури со автоматско пресметување на ДДВ и следење на пресметки',
          'Извештаи по проект — приходи, трошоци, маржа и степен на завршеност за секој проект поединечно',
          'Модул за плати — пресметка за градежни работници со прекувремена, ноќна и теренски додаток',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Набавки и нарачки во Facturino' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија' },
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија' },
      { slug: 'javni-nabavki-fakturiranje', title: 'Јавни набавки и фактурирање' },
      { slug: 'nabavki-i-narachki', title: 'Набавки и нарачки: Водич' },
    ],
    bottomCta: {
      title: 'Градежна компанија? Facturino ви помага.',
      subtitle: 'Нарачки, материјали, центри на трошоци и ИОС — сè на едно место. Бесплатен план за почеток.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Construction Accounting Macedonia: Progress Billing, Materials & Subcontractors',
    publishDate: 'May 23, 2026',
    readTime: '13 min read',
    intro:
      'Construction is one of the most complex sectors for accounting: billing is done by completed phases (progress billing), materials pass through warehouses and construction sites, subcontractors have their own invoicing obligations, and the VAT rate depends on the buyer type. Projects last months or years, requiring proper revenue and cost allocation. This guide covers everything construction companies in North Macedonia need to know about proper bookkeeping in 2026.',
    sections: [
      {
        title: 'Progress billing — invoicing by completed phases',
        content:
          'In construction, you don\'t invoice the full contract value at once. Billing is done through interim certificates confirmed by the supervising engineer:',
        items: [
          'Interim certificate (situacija) — a document showing completed works in a given period (usually monthly). Signed by the contractor, supervising engineer, and investor',
          'Final certificate — the final settlement after project completion. Includes all previous interim certificates and establishes the final contract value',
          'Retention (5-10%) — the investor withholds 5-10% of each certificate as a quality guarantee. Released after the warranty period (typically 12-24 months)',
          'Advance deduction — if the investor paid an advance, each interim certificate proportionally reduces the advance obligation',
          'Supervising engineer — must confirm every certificate before it becomes a basis for invoicing. No confirmation = no invoice',
          'Accounting: Each certificate generates a receivable (class 12) and revenue (class 6). Retention is a separate account (guarantee receivables)',
        ],
        steps: null,
      },
      {
        title: 'Material tracking — procurement, warehouse and site',
        content:
          'Materials are the largest cost item in construction (40-60% of project value). Proper tracking is critical:',
        items: null,
        steps: [
          { step: 'Procurement and receipt', desc: 'Every purchase is recorded with a goods receipt note and invoice. The cost must include transport and loading. Posting: debit 302 (Materials in stock), credit 220 (Trade payables).' },
          { step: 'WAC (Weighted Average Cost) method', desc: 'North Macedonia uses the weighted average cost method. With each new purchase, the average cost is recalculated: (existing value + new purchase) / total quantity.' },
          { step: 'Transfer from warehouse to site', desc: 'When materials go to a construction site, an internal dispatch note is issued. Posting: debit 302-2 (Materials on site), credit 302-1 (Materials in warehouse).' },
          { step: 'Consumption norms and wastage', desc: 'Each type of work has a consumption standard (e.g., 0.4 m³ concrete per 1 m² wall). Consumption above the norm = wastage. Allowed wastage: cement 1%, rebar 2%, timber 3%.' },
          { step: 'Monthly site inventory', desc: 'At the end of each month, the site manager counts unused materials. The difference between book and actual stock must be documented.' },
        ],
      },
      {
        title: 'Subcontractor management — invoices, retention and IOS',
        content:
          'Construction companies work with dozens of subcontractors. Each subcontractor brings their own accounting obligations:',
        items: [
          'Subcontract agreement — must contain: scope of work, unit prices, deadline, warranty period, and retention percentage. No agreement = risk during tax audit',
          'Subcontractor invoice — must accompany each interim certificate. Verify whether the subcontractor is a VAT payer — if yes, the invoice must include 18% VAT',
          'Retention on subcontractors — typically 5-10% is withheld as a guarantee. Recorded in a separate account (guarantee liabilities)',
          'IOS reconciliation — At the end of each quarter, perform an IOS (Open Items Statement) with each subcontractor. Unreconciled IOS = audit risk',
          'Offsetting (compensation) — If the subcontractor owes you (e.g., for materials supplied), perform offsetting to close mutual obligations',
          'Tax obligations — If you engage an individual (not a company) as a subcontractor, you are obligated to pay contributions and personal income tax (work contract)',
        ],
        steps: null,
      },
      {
        title: 'VAT specifics for construction',
        content:
          'Construction has several VAT peculiarities that don\'t exist in other sectors:',
        items: null,
        steps: [
          { step: 'Standard rate 18%', desc: 'All construction works (building, renovation, maintenance) are subject to 18% VAT. An invoice is issued with each interim certificate.' },
          { step: 'Reduced rate 5% for first-time buyers', desc: 'Under Art. 30 of the VAT Act, first-time residential buyers pay 5% VAT (instead of 18%). Condition: the buyer must not own other property and the apartment must be up to 120 m². The contractor applies the lower rate directly on the invoice.' },
          { step: 'Reverse charge for foreign subcontractors', desc: 'If you engage a foreign subcontractor (e.g., for specialized installation), the reverse charge mechanism applies. You calculate and pay the VAT in North Macedonia.' },
          { step: 'Input VAT deduction right', desc: 'All input VAT on materials, subcontractors, and equipment is deductible — provided you have a valid invoice and are a registered VAT payer.' },
          { step: 'VAT on advances', desc: 'When receiving an advance, you must issue an advance invoice with VAT. The VAT on the advance is reported in the period when the advance was received, not when the work was completed.' },
        ],
      },
      {
        title: 'Advance invoices — receiving and clearing',
        content:
          'Advances are standard practice in construction. The investor usually pays 10-30% upfront before work begins:',
        items: [
          'Advance invoice — issued upon receiving the advance. Must include VAT and be recorded in the VAT return for that period',
          'Posting of received advance: debit 240 (Bank account), credit 225 (Advances received). VAT: debit 225, credit 470 (VAT liability)',
          'Clearing against final invoice — when the certificate exceeds advance payments, an invoice is issued for the difference. Previously invoiced VAT on the advance is deducted',
          'Multiple advances — if the project has multiple phases with different advances, each advance is tracked separately and cleared against the corresponding certificate',
          'Unused advance — if the project ends with an unused advance, the difference is returned or transferred to a new project (with written consent)',
          'IMPORTANT: An advance invoice is NOT a proforma. An advance invoice is a full tax document with VAT obligation',
        ],
        steps: null,
      },
      {
        title: 'Long-term projects — IFRS 15 and revenue allocation',
        content:
          'Construction projects lasting more than 12 months have specific requirements for revenue and cost recognition:',
        items: null,
        steps: [
          { step: 'IFRS 15 — Revenue from contracts with customers', desc: 'Revenue is recognized by the degree of completion (input or output method). You don\'t wait for the project to finish — revenue is posted proportionally to completed works.' },
          { step: 'Percentage of completion method', desc: 'Input method: revenue = (total costs incurred / estimated total costs) × contract price. Output method: revenue per interim certificates confirmed by the supervisor.' },
          { step: 'Cost centers per project', desc: 'Each project is a separate cost center. All direct costs (materials, labor, subcontractors, machinery) are posted per project. Indirect costs (admin, office) are allocated by key.' },
          { step: 'Interim financing', desc: 'When costs exceed billings (work in progress > billed), the difference is an asset — "Contract asset." The reverse = "Contract liability."' },
          { step: 'Estimate revision', desc: 'At the end of each period, estimated total costs are revised. If the project has become unfavorable (loss), the entire expected loss is recognized IMMEDIATELY — you don\'t wait for project completion.' },
        ],
      },
      {
        title: 'How Facturino helps',
        content:
          'Facturino is designed for companies that work with procurement, materials, and multiple projects simultaneously:',
        items: [
          'Purchase Orders — create orders to suppliers, track statuses, and automatically generate goods receipt notes',
          'Material tracking with WAC method — automatic weighted average cost calculation with every purchase',
          'Cost centers per project — allocate all costs (materials, payroll, subcontractors) by project and track profitability',
          'IOS reconciliation with subcontractors — generate and send IOS automatically, track unreconciled items',
          'Advance invoices — issue advance invoices with automatic VAT calculation and clearing tracking',
          'Project reports — revenue, costs, margin, and percentage of completion for each project individually',
          'Payroll module — calculation for construction workers with overtime, night shift, and field allowance',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Purchase Orders & Procurement in Facturino' },
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for North Macedonia' },
      { slug: 'presmetka-na-plata-mk', title: 'Salary Calculation in North Macedonia' },
      { slug: 'javni-nabavki-fakturiranje', title: 'Public Procurement and Invoicing' },
      { slug: 'nabavki-i-narachki', title: 'Procurement and Orders: Guide' },
    ],
    bottomCta: {
      title: 'Construction company? Facturino helps.',
      subtitle: 'Purchase orders, materials, cost centers, and IOS — all in one place. Free plan to get started.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti për ndërtimtari: situacione, materiale dhe nënkontraktorë',
    publishDate: '23 maj 2026',
    readTime: '13 min lexim',
    intro:
      'Ndërtimtaria është një nga sektorët më kompleks për kontabilitet: faturimi bëhet sipas fazave të përfunduara (situacione), materialet kalojnë nëpër magazinë dhe kantier, nënkontraktorët kanë detyrimet e tyre të faturimit, dhe norma e TVSH varet nga lloji i blerësit. Projektet zgjasin muaj ose vite, që kërkon shpërndarje të duhur të të ardhurave dhe shpenzimeve. Ky udhëzues mbulon gjithçka që kompanitë ndërtimore në Maqedoni duhet të dinë për kontabilitetin e duhur në 2026.',
    sections: [
      {
        title: 'Situacione (Progress Billing) — faturimi sipas fazave',
        content:
          'Në ndërtimtari nuk faturohet vlera e plotë menjëherë. Faturimi bëhet nëpërmjet situacioneve të përkohshme të konfirmuara nga inxhinieri mbikëqyrës:',
        items: [
          'Situacion i përkohshëm — dokument që tregon punët e përfunduara në një periudhë (zakonisht mujore). Nënshkruhet nga kontraktori, mbikëqyrësi dhe investitori',
          'Situacion përfundimtar — llogaritja finale pas përfundimit të projektit. Përfshin të gjitha situacionet e mëparshme dhe përcakton vlerën finale',
          'Mbajtje (retencioni) 5-10% — investitori mban 5-10% të çdo situacioni si garanci cilësie. Lirohet pas periudhës së garancisë (zakonisht 12-24 muaj)',
          'Zbritje e paradhënies — nëse investitori ka paguar paradhënie, çdo situacion e zvogëlon detyrimin e paradhënies proporcionalisht',
          'Inxhinieri mbikëqyrës — duhet të konfirmojë çdo situacion para se të bëhet bazë për faturim. Pa konfirmim = pa faturë',
          'Kontabilitet: Çdo situacion gjeneron një të arkëtueshme (klasa 12) dhe të ardhur (klasa 6). Retencioni është llogari e veçantë',
        ],
        steps: null,
      },
      {
        title: 'Evidenca e materialeve — furnizim, magazinë dhe kantier',
        content:
          'Materialet janë zëri më i madh i shpenzimeve në ndërtimtari (40-60% e vlerës së projektit). Evidenca e duhur është kritike:',
        items: null,
        steps: [
          { step: 'Furnizimi dhe pranimi', desc: 'Çdo blerje regjistrohet me fletëpranim dhe faturë. Çmimi duhet të përfshijë transportin dhe ngarkimin. Regjistrimi: debit 302 (Materiale në stok), kredit 220 (Detyrime ndaj furnizuesve).' },
          { step: 'Metoda PMP (Çmimi Mesatar i Ponderuar)', desc: 'Maqedonia përdor çmimin mesatar të ponderuar. Me çdo blerje të re, çmimi mesatar rillogaritet: (vlera ekzistuese + blerja e re) / sasia totale.' },
          { step: 'Transferimi nga magazina në kantier', desc: 'Kur materialet shkojnë në kantier, lëshohet fletëdërgim intern. Regjistrimi: debit 302-2 (Materiale në kantier), kredit 302-1 (Materiale në magazinë).' },
          { step: 'Normat e konsumit dhe humbjet', desc: 'Çdo lloj pune ka normativ konsumi (p.sh. 0.4 m³ beton për 1 m² mur). Konsumi mbi normën = humbje. Humbje e lejuar: çimento 1%, hekur 2%, dru 3%.' },
          { step: 'Inventari mujor i kantierit', desc: 'Në fund të çdo muaji, përgjegjësi i kantierit numëron materialet e papërdorura. Dallimi midis gjendjes kontabël dhe reale duhet të dokumentohet.' },
        ],
      },
      {
        title: 'Menaxhimi i nënkontraktorëve — fatura, mbajtje dhe IOS',
        content:
          'Kompanitë ndërtimore punojnë me dhjetëra nënkontraktorë. Çdo nënkontraktor sjell detyrime të veta kontabël:',
        items: [
          'Kontratë nënkontraktimi — duhet të përmbajë: fushën e punës, çmimet unitare, afatin, periudhën e garancisë dhe përqindjen e mbajtjes. Pa kontratë = rrezik në kontroll tatimor',
          'Fatura e nënkontraktorit — duhet të shoqërojë çdo situacion. Verifikoni nëse nënkontraktori është pagues TVSH — nëse po, fatura duhet të përfshijë TVSH 18%',
          'Mbajtja ndaj nënkontraktorëve — zakonisht 5-10% mbahet si garanci. Regjistrohet në llogari të veçantë (detyrime garancish)',
          'Rakordimi IOS — Në fund të çdo tremujori, kryeni IOS me çdo nënkontraktor. IOS i parakorduar = rrezik auditi',
          'Kompensimi — Nëse nënkontraktori ju detyrohet (p.sh. për materiale), kryeni kompensim për të mbyllur detyrimet e ndërsjella',
          'Detyrimet tatimore — Nëse angazhoni individ (jo kompani) si nënkontraktor, ju jeni të detyruar të paguani kontributet dhe tatimin personal (kontratë vepre)',
        ],
        steps: null,
      },
      {
        title: 'Specifikat e TVSH për ndërtimtari',
        content:
          'Ndërtimtaria ka disa specifika TVSH që nuk ekzistojnë në sektorë të tjerë:',
        items: null,
        steps: [
          { step: 'Norma standarde 18%', desc: 'Të gjitha punët ndërtimore (ndërtim, rinovim, mirëmbajtje) janë subjekt i TVSH 18%. Fatura lëshohet me çdo situacion.' },
          { step: 'Norma e reduktuar 5% për blerës të parë', desc: 'Sipas Nenit 30 të Ligjit për TVSH, blerësit e banesës së parë paguajnë TVSH 5% (në vend të 18%). Kusht: blerësi nuk duhet të ketë pronë tjetër dhe banesa duhet të jetë deri 120 m². Kontraktori zbaton normën më të ulët direkt në faturë.' },
          { step: 'Reverse charge për nënkontraktorë të huaj', desc: 'Nëse angazhoni nënkontraktor nga jashtë (p.sh. për instalim të specializuar), zbatohet mekanizmi i ngarkesës së kthyer. Ju llogaritni dhe paguani TVSH-në në Maqedoni.' },
          { step: 'E drejta e zbritjes së TVSH hyrëse', desc: 'E gjithë TVSH hyrëse për materiale, nënkontraktorë dhe pajisje është e zbritshme — me kusht që keni faturë të vlefshme dhe jeni pagues TVSH.' },
          { step: 'TVSH mbi paradhënie', desc: 'Kur pranoni paradhënie, duhet të lëshoni faturë paradhënie me TVSH. TVSH e paradhënies raportohet në periudhën kur pranohet, jo kur përfundohet puna.' },
        ],
      },
      {
        title: 'Faturat e paradhënies — pranimi dhe llogaritja',
        content:
          'Paradhëniet janë praktikë standarde në ndërtimtari. Investitori zakonisht paguan 10-30% para fillimit të punës:',
        items: [
          'Fatura e paradhënies — lëshohet sapo pranohet paradhënia. Duhet të përfshijë TVSH dhe të regjistrohet në deklaratën TVSH për atë periudhë',
          'Regjistrimi i paradhënies: debit 240 (Llogaria bankare), kredit 225 (Paradhënie të pranuara). TVSH: debit 225, kredit 470 (Detyrimi TVSH)',
          'Llogaritja me faturën finale — kur situacioni e kalon paradhënien, lëshohet faturë për diferencën. TVSH e faturuar më parë zbritet',
          'Paradhënie të shumta — nëse projekti ka faza me paradhënie të ndryshme, secila ndiqet veçanërisht dhe llogaritet me situacionin përkatës',
          'Paradhënie e papërdorur — nëse projekti përfundon me paradhënie të papërdorur, diferenca kthehet ose transferohet në projekt të ri (me pëlqim me shkrim)',
          'E RËNDËSISHME: Fatura e paradhënies NUK është profaturë. Ajo është dokument i plotë tatimor me detyrim TVSH',
        ],
        steps: null,
      },
      {
        title: 'Projekte afatgjata — SNRF 15 dhe shpërndarja e të ardhurave',
        content:
          'Projektet ndërtimore që zgjasin më shumë se 12 muaj kanë kërkesa specifike për njohjen e të ardhurave dhe shpenzimeve:',
        items: null,
        steps: [
          { step: 'SNRF 15 — Të ardhura nga kontratat me klientët', desc: 'Të ardhurat njihen sipas shkallës së përfundimit (metoda input ose output). Nuk prisni që projekti të përfundojë — të ardhurat regjistrohen proporcionalisht me punët e përfunduara.' },
          { step: 'Metoda e përqindjes së përfundimit', desc: 'Metoda input: të ardhura = (shpenzimet totale të ndodhura / shpenzimet totale të parashikuara) × çmimi kontraktual. Metoda output: të ardhura sipas situacioneve të konfirmuara.' },
          { step: 'Qendra kostoje sipas projektit', desc: 'Çdo projekt është qendër kostoje e veçantë. Të gjitha kostot direkte (materiale, punë, nënkontraktorë, makineri) regjistrohen sipas projektit. Kostot indirekte shpërndahen sipas çelësit.' },
          { step: 'Financimi i përkohshëm', desc: 'Kur kostot e kalojnë faturimin (punë në progres > faturuar), diferenca është aktiv — "Aktiv kontraktual." E kundërta = "Detyrim kontraktual."' },
          { step: 'Rishikimi i parashikimeve', desc: 'Në fund të çdo periudhe, shpenzimet totale të parashikuara rishikohen. Nëse projekti ka dalë me humbje, e gjithë humbja e pritur njihet MENJËHERË — nuk pritet përfundimi.' },
        ],
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino është dizajnuar për kompani që punojnë me furnizime, materiale dhe projekte të shumta njëkohësisht:',
        items: [
          'Porosi blerjeje (Purchase Orders) — krijoni porosi te furnizuesit, ndiqni statusin dhe gjeneroni fletëpranime automatikisht',
          'Evidenca e materialeve me metodën PMP — llogaritje automatike e çmimit mesatar të ponderuar me çdo blerje',
          'Qendra kostoje sipas projektit — shpërndani të gjitha kostot (materiale, paga, nënkontraktorë) sipas projektit dhe ndiqni profitabilitetin',
          'Rakordimi IOS me nënkontraktorë — gjeneroni dhe dërgoni IOS automatikisht, ndiqni zërat e parakorduar',
          'Faturat e paradhënies — lëshoni fatura paradhënie me llogaritje automatike të TVSH dhe ndjekje të llogaritjeve',
          'Raporte sipas projektit — të ardhura, shpenzime, marzh dhe shkallë përfundimi për çdo projekt',
          'Moduli i pagave — llogaritje për punëtorë ndërtimi me orar shtesë, natë dhe shtesë terreni',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Porosi blerjeje dhe furnizime në Facturino' },
      { slug: 'ddv-vodich-mk', title: 'Udhëzues TVSH për Maqedoninë' },
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni' },
      { slug: 'javni-nabavki-fakturiranje', title: 'Prokurimi publik dhe faturimi' },
      { slug: 'nabavki-i-narachki', title: 'Prokurime dhe porosi: Udhëzues' },
    ],
    bottomCta: {
      title: 'Kompani ndërtimore? Facturino ju ndihmon.',
      subtitle: 'Porosi, materiale, qendra kostoje dhe IOS — gjithçka në një vend. Plan falas për fillim.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloğa dön',
    tag: 'Sektör',
    title: 'İnşaat Muhasebesi Makedonya: Hakediş, Malzeme ve Taşeronlar',
    publishDate: '23 Mayıs 2026',
    readTime: '13 dk okuma',
    intro:
      'İnşaat, muhasebe açısından en karmaşık sektörlerden biridir: faturalama tamamlanan aşamalara göre (hakediş) yapılır, malzemeler depo ve şantiyeden geçer, taşeronların kendi fatura yükümlülükleri vardır ve KDV oranı alıcı türüne bağlıdır. Projeler aylar veya yıllar sürer, bu da gelir ve maliyetlerin doğru dağıtılmasını gerektirir. Bu rehber, Kuzey Makedonya\'daki inşaat şirketlerinin 2026\'da doğru muhasebe hakkında bilmesi gereken her şeyi kapsar.',
    sections: [
      {
        title: 'Hakediş (Progress Billing) — tamamlanan aşamalara göre faturalama',
        content:
          'İnşaatta sözleşme bedelinin tamamı bir kerede faturalanmaz. Faturalama, kontrol mühendisi tarafından onaylanan hakedişler aracılığıyla yapılır:',
        items: [
          'Ara hakediş — belirli bir dönemde (genellikle aylık) tamamlanan işleri gösteren belge. Yüklenici, kontrol mühendisi ve yatırımcı tarafından imzalanır',
          'Kesin hakediş — proje tamamlandıktan sonra yapılan nihai hesap. Önceki tüm ara hakedişleri içerir ve nihai sözleşme bedelini belirler',
          'Kesinti (retansiyon) %5-10 — yatırımcı kalite garantisi olarak her hakedişin %5-10\'unu tutar. Garanti süresi sonunda (genellikle 12-24 ay) serbest bırakılır',
          'Avans mahsubu — yatırımcı avans ödemişse, her ara hakediş avans yükümlülüğünü orantılı olarak azaltır',
          'Kontrol mühendisi — her hakedişi faturalama için onaylamalıdır. Onay yoksa = fatura yok',
          'Muhasebe: Her hakediş bir alacak (sınıf 12) ve gelir (sınıf 6) oluşturur. Kesinti ayrı bir hesaptır (garanti alacakları)',
        ],
        steps: null,
      },
      {
        title: 'Malzeme takibi — tedarik, depo ve şantiye',
        content:
          'Malzemeler inşaatta en büyük maliyet kalemidir (proje değerinin %40-60\'ı). Doğru takip kritik öneme sahiptir:',
        items: null,
        steps: [
          { step: 'Tedarik ve kabul', desc: 'Her satın alma irsaliye ve fatura ile kaydedilir. Maliyet nakliye ve yüklemeyi içermelidir. Kayıt: borç 302 (Stoktaki malzemeler), alacak 220 (Ticari borçlar).' },
          { step: 'PMA (Ağırlıklı Ortalama Maliyet) yöntemi', desc: 'Kuzey Makedonya ağırlıklı ortalama maliyet yöntemini kullanır. Her yeni satın almada ortalama maliyet yeniden hesaplanır: (mevcut değer + yeni satın alma) / toplam miktar.' },
          { step: 'Depodan şantiyeye transfer', desc: 'Malzemeler şantiyeye gittiğinde dahili sevk irsaliyesi düzenlenir. Kayıt: borç 302-2 (Şantiyedeki malzemeler), alacak 302-1 (Depodaki malzemeler).' },
          { step: 'Tüketim normları ve fire', desc: 'Her iş türünün tüketim normu vardır (örn. 1 m² duvar için 0,4 m³ beton). Normun üzerinde tüketim = fire. İzin verilen fire: çimento %1, demir %2, ahşap %3.' },
          { step: 'Aylık şantiye sayımı', desc: 'Her ayın sonunda şantiye sorumlusu kullanılmamış malzemeleri sayar. Defter ve fiili stok arasındaki fark belgelenmelidir.' },
        ],
      },
      {
        title: 'Taşeron yönetimi — faturalar, kesintiler ve IOS',
        content:
          'İnşaat şirketleri düzinelerce taşeronla çalışır. Her taşeron kendi muhasebe yükümlülüklerini getirir:',
        items: [
          'Taşeronluk sözleşmesi — içermelidir: iş kapsamı, birim fiyatlar, süre, garanti süresi ve kesinti yüzdesi. Sözleşme yoksa = vergi denetiminde risk',
          'Taşeron faturası — her ara hakedişe eşlik etmelidir. Taşeronun KDV mükellefi olup olmadığını kontrol edin — evetse, fatura %18 KDV içermelidir',
          'Taşeronlara kesinti — genellikle %5-10 garanti olarak tutulur. Ayrı hesapta (garanti borçları) kaydedilir',
          'IOS mutabakatı — Her çeyreğin sonunda her taşeronla IOS (Açık Kalemler Tablosu) yapın. Mutabık kalınmamış IOS = denetim riski',
          'Mahsup (kompanzasyon) — Taşeron size borçluysa (örn. sağlanan malzeme için), karşılıklı yükümlülükleri kapatmak için mahsup yapın',
          'Vergi yükümlülükleri — Bireysel (şirket değil) taşeron çalıştırıyorsanız, prim ve gelir vergisi ödemekle yükümlüsünüz (eser sözleşmesi)',
        ],
        steps: null,
      },
      {
        title: 'İnşaat için KDV özellikleri',
        content:
          'İnşaatın diğer sektörlerde bulunmayan birkaç KDV özelliği vardır:',
        items: null,
        steps: [
          { step: 'Standart oran %18', desc: 'Tüm inşaat işleri (yapım, tadilat, bakım) %18 KDV\'ye tabidir. Fatura her ara hakedişle birlikte düzenlenir.' },
          { step: 'İlk konut alıcıları için indirimli oran %5', desc: 'KDV Kanunu Md. 30\'a göre ilk konut alıcıları %5 KDV öder (%18 yerine). Koşul: alıcının başka mülkü olmamalı ve daire 120 m²\'ye kadar olmalı. Yüklenici düşük oranı doğrudan faturaya uygular.' },
          { step: 'Yabancı taşeronlar için ters yükleme', desc: 'Yabancı taşeron çalıştırıyorsanız (örn. uzmanlaşmış kurulum), ters yükleme mekanizması geçerlidir. KDV\'yi Kuzey Makedonya\'da siz hesaplar ve ödersiniz.' },
          { step: 'Giriş KDV\'si indirim hakkı', desc: 'Malzeme, taşeron ve ekipmandaki tüm giriş KDV\'si indirilebilir — geçerli faturanız olması ve KDV mükellefi olmanız koşuluyla.' },
          { step: 'Avanslarda KDV', desc: 'Avans alındığında, KDV\'li avans faturası düzenlemek zorunludur. Avans KDV\'si avansın alındığı dönemde beyan edilir, işin tamamlandığı dönemde değil.' },
        ],
      },
      {
        title: 'Avans faturaları — alma ve mahsup',
        content:
          'Avanslar inşaatta standart uygulamadır. Yatırımcı genellikle iş başlamadan önce %10-30 avans öder:',
        items: [
          'Avans faturası — avans alındığında düzenlenir. KDV içermeli ve o dönemin KDV beyannamesinde bildirilmelidir',
          'Alınan avansın kaydı: borç 240 (Banka hesabı), alacak 225 (Alınan avanslar). KDV: borç 225, alacak 470 (KDV borcu)',
          'Kesin faturayla mahsup — hakediş avans ödemelerini aştığında, fark için fatura düzenlenir. Daha önce faturalanan avans KDV\'si düşülür',
          'Birden fazla avans — proje farklı avanslarla birden fazla aşamaya sahipse, her avans ayrı takip edilir ve ilgili hakedişle mahsup edilir',
          'Kullanılmayan avans — proje kullanılmayan avansla biterse, fark iade edilir veya yeni projeye aktarılır (yazılı muvafakatla)',
          'ÖNEMLİ: Avans faturası proforma DEĞİLDİR. Avans faturası KDV yükümlülüğü olan tam bir vergi belgesidir',
        ],
        steps: null,
      },
      {
        title: 'Uzun vadeli projeler — UFRS 15 ve gelir dağıtımı',
        content:
          '12 aydan uzun süren inşaat projeleri gelir ve maliyet tanıma için özel gereksinimlere sahiptir:',
        items: null,
        steps: [
          { step: 'UFRS 15 — Müşterilerle yapılan sözleşmelerden gelir', desc: 'Gelir tamamlanma derecesine göre tanınır (girdi veya çıktı yöntemi). Projenin bitmesini beklemezsiniz — gelir tamamlanan işlerle orantılı olarak kaydedilir.' },
          { step: 'Tamamlanma yüzdesi yöntemi', desc: 'Girdi yöntemi: gelir = (toplam katlanılan maliyetler / tahmini toplam maliyetler) × sözleşme bedeli. Çıktı yöntemi: kontrol mühendisi onaylı ara hakedişlere göre gelir.' },
          { step: 'Proje bazlı maliyet merkezleri', desc: 'Her proje ayrı bir maliyet merkezidir. Tüm doğrudan maliyetler (malzeme, işçilik, taşeronlar, makine) proje bazında kaydedilir. Dolaylı maliyetler (yönetim, ofis) anahtara göre dağıtılır.' },
          { step: 'Ara finansman', desc: 'Maliyetler faturalamayı aştığında (devam eden iş > faturalanan), fark bir varlıktır — "Sözleşme varlığı." Tersi = "Sözleşme yükümlülüğü."' },
          { step: 'Tahmin revizyonu', desc: 'Her dönem sonunda tahmini toplam maliyetler revize edilir. Proje zarara dönmüşse, beklenen zararın tamamı DERHAL tanınır — proje bitimi beklenmez.' },
        ],
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content:
          'Facturino, tedarik, malzeme ve aynı anda birden fazla projeyle çalışan şirketler için tasarlanmıştır:',
        items: [
          'Satın Alma Siparişleri (Purchase Orders) — tedarikçilere sipariş oluşturun, durumları takip edin ve otomatik irsaliye oluşturun',
          'PMA yöntemiyle malzeme takibi — her satın almada otomatik ağırlıklı ortalama maliyet hesaplaması',
          'Proje bazlı maliyet merkezleri — tüm maliyetleri (malzeme, bordro, taşeronlar) proje bazında dağıtın ve kârlılığı takip edin',
          'Taşeronlarla IOS mutabakatı — IOS\'u otomatik oluşturun ve gönderin, mutabık kalınmamış kalemleri takip edin',
          'Avans faturaları — otomatik KDV hesaplaması ve mahsup takibi ile avans faturaları düzenleyin',
          'Proje bazlı raporlar — her proje için ayrı gelir, maliyet, marj ve tamamlanma yüzdesi',
          'Bordro modülü — fazla mesai, gece vardiyası ve saha tazminatıyla inşaat işçileri için hesaplama',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'nabavki-i-narachki', title: 'Facturino\'da Satın Alma Siparişleri ve Tedarik' },
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV Rehberi' },
      { slug: 'presmetka-na-plata-mk', title: 'Makedonya\'da Maaş Hesaplama' },
      { slug: 'javni-nabavki-fakturiranje', title: 'Kamu Alımları ve Faturalama' },
      { slug: 'nabavki-i-narachki', title: 'Tedarik ve Siparişler: Rehber' },
    ],
    bottomCta: {
      title: 'İnşaat şirketi? Facturino yardımcı olur.',
      subtitle: 'Satın alma siparişleri, malzeme, maliyet merkezleri ve IOS — hepsi tek yerde. Başlamak için ücretsiz plan.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaGradeznistvo({
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
    slug: 'smetkovodstvo-za-gradeznistvo',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['градежништво', 'construction', 'ситуации', 'progress billing', 'ДДВ', 'VAT', 'МСФИ 15', 'IFRS 15', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-gradeznistvo` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Дали ДДВ за градежништво е секогаш 18%?', answer: 'Не. Градежни работи за станбени објекти до 150 м² се оданочуваат со 5% ДДВ (Чл. 30 ЗДДВ). Комерцијални објекти, инфраструктура и реновирање се 18%. Подизведувачите секогаш фактурираат со 18% кон главниот изведувач.' },
        { question: 'Како се фактурираат ситуации во градежништвото?', answer: 'Привремената ситуација ја подготвува изведувачот, ја потврдува надзорниот инженер и ја одобрува инвеститорот. По потврда, изведувачот издава фактура за одобрениот износ. Задршка од 5-10% се евидентира на посебна сметка за гаранции.' },
        { question: 'Како се евидентираат подизведувачи?', answer: 'Секој подизведувач мора да издаде фактура што ја прати привремената ситуација. Проверете дали е ДДВ обврзник. Задржете 5-10% гаранција и правете квартален ИОС (Извод на отворени ставки). Ако ангажирате физичко лице — вие плаќате придонеси.' },
      ])) }} />

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
