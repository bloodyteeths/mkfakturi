import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/smetkovodstvo-za-zemjodelstvo', {
    title: {
      mk: 'Сметководство за земјоделство: Субвенции, ДДВ 5% и ИПАРД грантови',
      en: 'Agriculture Accounting Macedonia: Subsidies, VAT & IPARD Grants',
      sq: 'Kontabiliteti për bujqësi: Subvencione, TVSH 5% dhe grante IPARD',
      tr: 'Tarım Muhasebesi Makedonya: Sübvansiyonlar, %5 KDV ve IPARD Hibeleri',
    },
    description: {
      mk: 'Комплетен водич за сметководство во земјоделство: ДДВ 5% за примарни производители, ИПАРД грантови, сезонски приходи, задруги, субвенции и закуп на земјиште.',
      en: 'Complete guide to agriculture accounting in North Macedonia: 5% VAT for primary producers, IPARD grants, seasonal income, cooperatives, subsidies and land lease deductions.',
      sq: 'Udhëzues i plotë për kontabilitetin bujqësor në Maqedoninë e Veriut: TVSH 5% për prodhuesit primar, grante IPARD, të ardhura sezonale, kooperativa, subvencione dhe qira toke.',
      tr: 'Kuzey Makedonya\'da tarım muhasebesi rehberi: Birincil üreticiler için %5 KDV, IPARD hibeleri, mevsimsel gelir, kooperatifler, sübvansiyonlar ve arazi kiralama indirimleri.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Сектор',
    title: 'Сметководство за земјоделство: Субвенции, ДДВ 5% и ИПАРД грантови',
    publishDate: '23 мај 2026',
    readTime: '12 мин читање',
    intro:
      'Земјоделството во Македонија има уникатни сметководствени предизвици: намалена ДДВ стапка од 5% за примарни производители, ИПАРД грантови со строги услови за документација, сезонски приходи зависни од берба, земјоделски задруги со специјален даночен третман и државни субвенции со различни рокови за признавање. Овој водич покрива сè што земјоделците и агро-бизнисите треба да знаат за правилно книговодство во 2026.',
    sections: [
      {
        title: 'ДДВ 5% за земјоделски производи: Кога важи?',
        content:
          'Според Чл. 30 од ЗДДВ, земјоделски производи продадени од примарни производители подлежат на намалена стапка од 5%. Но правилата се построги отколку што изгледаат:',
        items: [
          'ДДВ 5% — Свежо овошје, зеленчук, житарици, млеко, јајца, мед продадени од примарен производител (земјоделец)',
          'ДДВ 5% — Живи животни за исхрана (говеда, овци, свињи, живина) продадени од фарма',
          'ДДВ 18% — Преработени производи (сирење, кашкавал, конзервиран зеленчук, сокови) — дури и ако ги прави истиот земјоделец',
          'ДДВ 18% — Земјоделски производи продадени преку трговец/дистрибутер — 5% важи САМО за директна продажба од производител',
          'ДДВ 5% — Семе за сеидба, садници и ѓубрива (влезни материјали за земјоделство)',
          'ВАЖНО: За да користите 5%, мора да сте регистриран земјоделец во Регистарот на земјоделски стопанства при МЗШВ',
          'Мешана дејност: Ако фарма продава и свежо млеко (5%) и домашно сирење (18%), секоја ставка се фактурира одделно',
        ],
        steps: null,
      },
      {
        title: 'ИПАРД грантови: Сметководство за ЕУ ревизија',
        content:
          'ИПАРД (Инструмент за претпристапна помош за рурален развој) грантовите имаат строги барања за финансиска документација. Грешка во евиденцијата = враќање на целиот грант:',
        items: null,
        steps: [
          { step: 'Одделни трошковни центри', desc: 'Секој ИПАРД проект мора да има посебен трошковен центар. Средствата финансирани од грантот и сопствените средства мора да се евидентираат одделно — не смеат да се мешаат.' },
          { step: 'Задржување на средства 5 години', desc: 'Опремата и објектите купени со ИПАРД грант мора да се задржат минимум 5 години. Продажба или отуѓување пред тој рок = обврска за враќање на грантот.' },
          { step: 'Документација за набавки', desc: 'Секоја набавка над EUR 5.000 бара минимум 3 понуди. Целата кореспонденција, понуди и фактури мора да се чуваат 7 години по завршување на проектот.' },
          { step: 'Признавање на приходот', desc: 'ИПАРД грантот се признава како приход пропорционално со амортизацијата на средството. Ако опрема чини EUR 100.000 и се амортизира 10 години, годишно се признава EUR 10.000 грант-приход.' },
          { step: 'ДДВ на ИПАРД набавки', desc: 'ДДВ е НЕприфатлив трошок за ИПАРД. Ако сте ДДВ обврзник, ДДВ-то од набавките го одбивате нормално. Ако не сте — ДДВ е ваш трошок, но НЕ го покрива грантот.' },
        ],
      },
      {
        title: 'Сезонски приходи и управување со готовина',
        content:
          'Земјоделството е сезонска дејност — приходите доаѓаат во бранови, а трошоците се цела година. Ова бара посебен пристап:',
        items: [
          'Берба = концентриран приход: 60-80% од годишниот приход може да дојде во 2-3 месеци. Планирајте ја ликвидноста за останатите 9 месеци',
          'Авансни продажби: Договори за продажба на берба пред жетва се честа практика. Авансот се евидентира како обврска, не како приход, додека не се испорача стоката',
          'Трошоци за складирање: Ладилници, силоси и магацини генерираат трошоци и кога нема приход. Евидентирајте ги месечно',
          'Ценовна нестабилност: Цената на житариците варира и до 40% во текот на годината. Користете просечна набавна цена (WAC) за вреднување на залихата',
          'Осигурување на посеви: Признат расход. Полисата мора да покрива конкретни ризици (град, суша, поплава)',
          'Кало и природен загуб: Нормативен кало за жито е 2-3%, за овошје 5-8%. Над нормативот — оданочив расход',
        ],
        steps: null,
      },
      {
        title: 'Земјоделска задруга: Даночен третман',
        content:
          'Земјоделските задруги имаат специјален статус во македонското законодавство. Еве ги клучните правила:',
        items: null,
        steps: [
          { step: 'Што е земјоделска задруга?', desc: 'Правно лице основано од минимум 5 земјоделци за заедничко производство, преработка или продажба. Регулирано со Закон за задруги.' },
          { step: 'ДДВ статус', desc: 'Задругата може да биде ДДВ обврзник ако прометот надминува 2.000.000 МКД годишно. Откупот од членовите (примарни производители) е по 5% ДДВ. Продажбата на преработени производи е по 18%.' },
          { step: 'Трансакции со членови', desc: 'Откупот на производи од членови НЕ е продажба — тоа е внатрешен трансфер. Задругата издава откупен блок, не фактура. Членовите не плаќаат данок на овој приход до моментот на финална продажба.' },
          { step: 'Распределба на добивка', desc: 'Добивката се распределува пропорционално на учеството на секој член (количина испорачан производ). Секој член плаќа 10% ПДД на својот дел.' },
          { step: 'Даночни поволности', desc: 'Задругите имаат право на намален данок на добивка (50% од стандардната стапка) во првите 3 години. Реинвестирана добивка = ослободена од данок.' },
        ],
      },
      {
        title: 'Субвенции и директни плаќања: Евиденција и признавање',
        content:
          'Државните субвенции за земјоделство се значаен извор на приход. Правилното евидентирање е клучно за даночна усогласеност:',
        items: [
          'Директни плаќања по хектар: Субвенција од МЗШВ за обработливо земјиште. Се признава како приход во моментот на примање (готовинска основа)',
          'ИПАРД грантови: Капитални грантови за опрема и објекти. Се признаваат пропорционално со амортизацијата (MRS 20)',
          'Субвенции за млеко: Се исплаќаат месечно врз основа на испорачана количина. Евидентирајте ги одделно од продажниот приход',
          'Субвенции за тутун: Годишна исплата по берба. Рок за евиденција = датум на примање',
          'Субвенции за сточарство: По грло добиток. Бараат евиденција за бројот на грла (ушни маркици, ветеринарен регистар)',
          'ВАЖНО: Неискористени субвенции (ако не ги исполнувате условите) мора да се вратат и евидентираат како обврска',
          'Даночен третман: Субвенциите СЕ оданочиви — влегуваат во основата за данок на добивка. Не постои ослободување',
        ],
        steps: null,
      },
      {
        title: 'Закуп на земјиште: Одбитоци и документација',
        content:
          'Земјоделското земјиште се користи преку сопственост или закуп. За закупот да биде признат расход, потребна е правилна документација:',
        items: null,
        steps: [
          { step: 'Договор за закуп', desc: 'Мора да биде во писмена форма и заверен кај нотар. Договорот мора да содржи: парцела (КП број), површина, намена, рок и цена. Без договор = непризнат расход.' },
          { step: 'Регистрација во Катастар', desc: 'Договорот за закуп мора да биде запишан во имотен лист кај Агенцијата за катастар на недвижности. Нерегистриран закуп = ризик од непризнавање.' },
          { step: 'Пазарна цена', desc: 'Закупнината мора да одговара на пазарната вредност. УЈП може да ја оспори ако е значително повисока од просечната за регионот. Референца: МЗШВ објавува просечни закупнини по регион.' },
          { step: 'Плаќање преку банка', desc: 'Закупнината МОРА да се плаќа преку банкарска трансакција за да биде признат расход. Готовинско плаќање = непризнат расход за суми над 50.000 МКД.' },
          { step: 'Данок на закупнина', desc: 'Закуподавачот (сопственикот) плаќа 10% ПДД на примената закупнина. Ако е физичко лице, закупецот е должен да му задржи данок при исплата.' },
        ],
      },
      {
        title: 'Како Facturino помага',
        content:
          'Facturino има функционалности дизајнирани за потребите на земјоделците и агро-бизнисите:',
        items: [
          'Трошковни центри: Одделете ги грант-финансираните средства од сопствените — подготвени за ИПАРД ревизија',
          'ДДВ менаџмент: Автоматско пресметување на ДДВ 5% за примарни земјоделски производи и 18% за преработени',
          'Следење на субвенции: Одделна категорија за секој тип субвенција (директни плаќања, ИПАРД, млеко, тутун)',
          'Сезонско планирање: Извештаи за приходи и расходи по месеци — видете ја ликвидноста цела година',
          'Евиденција на средства: Регистар на опрема со датум на набавка, извор (грант/сопствен) и амортизација',
          'Документи за закуп: Складирајте ги договорите, нотарските заверки и катастарските записи на едно место',
          'Извештаи за ревизија: Генерирајте готови извештаи за УЈП или ИПАРД контрола',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Водич' },
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи' },
    ],
    bottomCta: {
      title: 'Сметководство за фарма? Facturino е бесплатен.',
      subtitle: 'Трошковни центри за ИПАРД, ДДВ 5%, субвенции и закуп — сè на едно место. Без месечна претплата.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Sector',
    title: 'Agriculture Accounting Macedonia: Subsidies, VAT & IPARD Grants',
    publishDate: 'May 23, 2026',
    readTime: '12 min read',
    intro:
      'Agriculture in North Macedonia presents unique accounting challenges: a reduced 5% VAT rate for primary producers, IPARD grants with strict documentation requirements, seasonal harvest-dependent income, agricultural cooperatives with special tax treatment, and government subsidies with different recognition timelines. This guide covers everything farmers and agribusinesses need to know about proper bookkeeping in 2026.',
    sections: [
      {
        title: 'Agricultural VAT at 5%: When does it apply?',
        content:
          'Under Art. 30 of the VAT Act, agricultural products sold by primary producers are subject to a reduced rate of 5%. But the rules are stricter than they appear:',
        items: [
          'VAT 5% — Fresh fruit, vegetables, grains, milk, eggs, honey sold by a primary producer (farmer)',
          'VAT 5% — Live animals for consumption (cattle, sheep, pigs, poultry) sold from the farm',
          'VAT 18% — Processed products (cheese, canned vegetables, juices) — even if made by the same farmer',
          'VAT 18% — Agricultural products sold through a trader/distributor — 5% applies ONLY to direct sales by the producer',
          'VAT 5% — Seeds, seedlings, and fertilizers (agricultural inputs)',
          'IMPORTANT: To use the 5% rate, you must be a registered farmer in the Registry of Agricultural Holdings at MAFWE',
          'Mixed activity: If a farm sells fresh milk (5%) and homemade cheese (18%), each item is invoiced separately',
        ],
        steps: null,
      },
      {
        title: 'IPARD grant accounting: Ready for EU audit',
        content:
          'IPARD (Instrument for Pre-Accession Assistance for Rural Development) grants have strict financial documentation requirements. An error in record-keeping = returning the entire grant:',
        items: null,
        steps: [
          { step: 'Separate cost centers', desc: 'Each IPARD project must have its own cost center. Grant-funded assets and self-funded assets must be recorded separately — they must not be mixed.' },
          { step: '5-year asset retention', desc: 'Equipment and facilities purchased with IPARD grants must be retained for a minimum of 5 years. Sale or disposal before that period = obligation to return the grant.' },
          { step: 'Procurement documentation', desc: 'Every purchase over EUR 5,000 requires a minimum of 3 quotes. All correspondence, quotes, and invoices must be kept for 7 years after project completion.' },
          { step: 'Income recognition', desc: 'The IPARD grant is recognized as income proportionally with the depreciation of the asset. If equipment costs EUR 100,000 and is depreciated over 10 years, EUR 10,000 of grant income is recognized annually.' },
          { step: 'VAT on IPARD purchases', desc: 'VAT is an ineligible cost for IPARD. If you are a VAT payer, you deduct VAT from purchases normally. If not — VAT is your cost, but the grant does NOT cover it.' },
        ],
      },
      {
        title: 'Seasonal income and cash flow management',
        content:
          'Agriculture is a seasonal activity — income arrives in waves, but costs run all year. This requires a special approach:',
        items: [
          'Harvest = concentrated income: 60-80% of annual revenue may come in 2-3 months. Plan liquidity for the remaining 9 months',
          'Advance sales: Pre-harvest sale contracts are common practice. The advance is recorded as a liability, not income, until the goods are delivered',
          'Storage costs: Cold rooms, silos, and warehouses generate costs even when there is no income. Record them monthly',
          'Price volatility: Grain prices can vary up to 40% during the year. Use weighted average cost (WAC) for inventory valuation',
          'Crop insurance: Recognized expense. The policy must cover specific risks (hail, drought, flood)',
          'Spoilage and natural loss: Standard loss for grain is 2-3%, for fruit 5-8%. Above the norm — taxable expense',
        ],
        steps: null,
      },
      {
        title: 'Agricultural cooperatives: Tax treatment',
        content:
          'Agricultural cooperatives have special status under Macedonian law. Here are the key rules:',
        items: null,
        steps: [
          { step: 'What is an agricultural cooperative?', desc: 'A legal entity founded by at least 5 farmers for joint production, processing, or sales. Regulated by the Law on Cooperatives.' },
          { step: 'VAT status', desc: 'The cooperative may be a VAT payer if turnover exceeds MKD 2,000,000 annually. Purchases from members (primary producers) are at 5% VAT. Sales of processed products are at 18%.' },
          { step: 'Member transactions', desc: 'Purchasing products from members is NOT a sale — it is an internal transfer. The cooperative issues a purchase note, not an invoice. Members do not pay tax on this income until the final sale.' },
          { step: 'Profit distribution', desc: 'Profits are distributed proportionally based on each member\'s contribution (quantity of product delivered). Each member pays 10% PIT on their share.' },
          { step: 'Tax incentives', desc: 'Cooperatives are entitled to reduced corporate tax (50% of the standard rate) in the first 3 years. Reinvested profit = exempt from tax.' },
        ],
      },
      {
        title: 'Subsidies and direct payments: Recording and recognition',
        content:
          'Government agricultural subsidies are a significant income source. Proper recording is key to tax compliance:',
        items: [
          'Direct payments per hectare: MAFWE subsidy for cultivated land. Recognized as income upon receipt (cash basis)',
          'IPARD grants: Capital grants for equipment and facilities. Recognized proportionally with depreciation (IAS 20)',
          'Milk subsidies: Paid monthly based on delivered quantity. Record them separately from sales revenue',
          'Tobacco subsidies: Annual payment after harvest. Recognition date = receipt date',
          'Livestock subsidies: Per head of livestock. Require records of headcount (ear tags, veterinary register)',
          'IMPORTANT: Unused subsidies (if you don\'t meet the conditions) must be returned and recorded as a liability',
          'Tax treatment: Subsidies ARE taxable — they enter the corporate tax base. There is no exemption',
        ],
        steps: null,
      },
      {
        title: 'Land lease deductions: Requirements and documentation',
        content:
          'Agricultural land is used through ownership or lease. For the lease to be a recognized expense, proper documentation is required:',
        items: null,
        steps: [
          { step: 'Lease agreement', desc: 'Must be in written form and notarized. The contract must contain: parcel (cadastral number), area, purpose, term, and price. Without a contract = non-deductible expense.' },
          { step: 'Registration at Cadastre', desc: 'The lease agreement must be registered in the property sheet at the Agency for Real Estate Cadastre. An unregistered lease = risk of non-recognition.' },
          { step: 'Market rate', desc: 'The rent must correspond to market value. UJP may challenge it if significantly higher than the regional average. Reference: MAFWE publishes average rents by region.' },
          { step: 'Bank payment', desc: 'Rent MUST be paid via bank transaction to be a recognized expense. Cash payment = non-deductible expense for amounts over MKD 50,000.' },
          { step: 'Rent tax', desc: 'The lessor (owner) pays 10% PIT on received rent. If the owner is an individual, the lessee must withhold tax at payment.' },
        ],
      },
      {
        title: 'How Facturino helps',
        content:
          'Facturino has features designed for the needs of farmers and agribusinesses:',
        items: [
          'Cost centers: Separate grant-funded assets from your own — ready for IPARD audits',
          'VAT management: Automatic calculation of 5% VAT for primary agricultural products and 18% for processed goods',
          'Subsidy tracking: Separate category for each subsidy type (direct payments, IPARD, milk, tobacco)',
          'Seasonal planning: Monthly income and expense reports — see your liquidity all year round',
          'Asset register: Equipment registry with purchase date, source (grant/own), and depreciation schedule',
          'Lease documents: Store contracts, notarial certifications, and cadastral records in one place',
          'Audit reports: Generate ready-made reports for UJP or IPARD inspections',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for North Macedonia' },
      { slug: 'danok-na-dobivka', title: 'Corporate Tax Guide' },
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management Guide' },
    ],
    bottomCta: {
      title: 'Farm accounting? Facturino is free.',
      subtitle: 'Cost centers for IPARD, 5% VAT, subsidies and land lease — all in one place. No monthly subscription.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Sektor',
    title: 'Kontabiliteti për bujqësi: Subvencione, TVSH 5% dhe grante IPARD',
    publishDate: '23 maj 2026',
    readTime: '12 min lexim',
    intro:
      'Bujqësia në Maqedoninë e Veriut paraqet sfida unike kontabël: normë e reduktuar TVSH prej 5% për prodhuesit primar, grante IPARD me kërkesa strikte dokumentacioni, të ardhura sezonale të varura nga korrja, kooperativa bujqësore me trajtim special tatimor dhe subvencione shtetërore me afate të ndryshme njohje. Ky udhëzues mbulon gjithçka që bujqit dhe agro-bizneset duhet të dinë për kontabilitetin e duhur në 2026.',
    sections: [
      {
        title: 'TVSH 5% për produkte bujqësore: Kur vlen?',
        content:
          'Sipas Nenit 30 të Ligjit për TVSH, produktet bujqësore të shitura nga prodhuesit primar i nënshtrohen normës së reduktuar prej 5%. Por rregullat janë më strikte se sa duken:',
        items: [
          'TVSH 5% — Fruta të freskëta, perime, drithëra, qumësht, vezë, mjaltë të shitura nga prodhuesi primar (bujku)',
          'TVSH 5% — Kafshë të gjalla për ushqim (gjedhë, dele, derra, shpendë) të shitura nga ferma',
          'TVSH 18% — Produkte të përpunuara (djathë, perime të konservuara, lëngje) — edhe nëse i bën i njëjti bujk',
          'TVSH 18% — Produkte bujqësore të shitura nëpërmjet tregtarit/distribuitorit — 5% vlen VETËM për shitje direkte nga prodhuesi',
          'TVSH 5% — Farë, fidanë dhe pleh (inpute bujqësore)',
          'E RËNDËSISHME: Për të përdorur normën 5%, duhet të jeni bujk i regjistruar në Regjistrin e Ekonomive Bujqësore pranë MBPEU',
          'Aktivitet i përzier: Nëse ferma shet qumësht të freskët (5%) dhe djathë shtëpiak (18%), çdo zë faturohet veçanërisht',
        ],
        steps: null,
      },
      {
        title: 'Kontabiliteti i granteve IPARD: Gati për auditin e BE-së',
        content:
          'Grantet IPARD (Instrumenti për Ndihmë Para-Aderimit për Zhvillim Rural) kanë kërkesa strikte për dokumentacion financiar. Gabim në evidencë = kthim i të gjithë grantit:',
        items: null,
        steps: [
          { step: 'Qendra kostoje të veçanta', desc: 'Çdo projekt IPARD duhet të ketë qendër kostoje të veçantë. Asetet e financuara nga granti dhe ato vetanake duhet të regjistrohen veçanërisht — nuk guxojnë të përzihen.' },
          { step: 'Mbajtja e aseteve 5 vjet', desc: 'Pajisjet dhe objektet e blera me grant IPARD duhet të mbahen minimumi 5 vjet. Shitja ose tjetërsimi para atij afati = detyrimi për kthim të grantit.' },
          { step: 'Dokumentacioni i furnizimit', desc: 'Çdo blerje mbi EUR 5.000 kërkon minimumi 3 oferta. I gjithë korrespondenca, ofertat dhe faturat duhet të ruhen 7 vjet pas përfundimit të projektit.' },
          { step: 'Njohja e të ardhurave', desc: 'Granti IPARD njihet si të ardhur proporcionalisht me amortizimin e asetit. Nëse pajisja kushton EUR 100.000 dhe amortizohet 10 vjet, çdo vit njihen EUR 10.000 të ardhur granti.' },
          { step: 'TVSH në blerjet IPARD', desc: 'TVSH është kosto e papranueshme për IPARD. Nëse jeni pagues i TVSH, TVSH-në nga blerjet e zbritni normalisht. Nëse jo — TVSH është kostoja juaj, por granti NUK e mbulon.' },
        ],
      },
      {
        title: 'Të ardhura sezonale dhe menaxhimi i likuiditetit',
        content:
          'Bujqësia është aktivitet sezonal — të ardhurat vijnë në valë, por shpenzimet vazhdojnë gjithë vitin. Kjo kërkon qasje të veçantë:',
        items: [
          'Korrja = të ardhura të përqendruara: 60-80% e të ardhurave vjetore mund të vijë brenda 2-3 muajsh. Planifikoni likuiditetin për 9 muajt e mbetur',
          'Shitje paraprake: Kontratat e shitjes para korrjes janë praktikë e zakonshme. Paradhënia regjistrohet si detyrim, jo si të ardhur, derisa malli të dorëzohet',
          'Shpenzime magazinimi: Frigoriferët, siloset dhe magazinat gjenerojnë shpenzime edhe kur nuk ka të ardhura. Regjistroni mujor',
          'Paqëndrueshmëria e çmimeve: Çmimet e drithërave variojnë deri 40% gjatë vitit. Përdorni koston mesatare të ponderuar (WAC) për vlerësimin e stokut',
          'Sigurimi i të mbjellave: Shpenzim i njohur. Polisa duhet të mbulojë rreziqe specifike (breshër, thatësi, përmbytje)',
          'Humbje natyrore: Norma për drithëra është 2-3%, për fruta 5-8%. Mbi normën — shpenzim i tatueshëm',
        ],
        steps: null,
      },
      {
        title: 'Kooperativa bujqësore: Trajtimi tatimor',
        content:
          'Kooperativat bujqësore kanë status special sipas legjislacionit maqedonas:',
        items: null,
        steps: [
          { step: 'Çfarë është kooperativa bujqësore?', desc: 'Person juridik i themeluar nga minimumi 5 bujq për prodhim, përpunim ose shitje të përbashkët. E rregulluar me Ligjin për Kooperativa.' },
          { step: 'Statusi TVSH', desc: 'Kooperativa mund të jetë pagues i TVSH nëse qarkullimi kalon 2.000.000 MKD në vit. Blerjet nga anëtarët (prodhues primar) janë me TVSH 5%. Shitja e produkteve të përpunuara me 18%.' },
          { step: 'Transaksionet me anëtarë', desc: 'Blerja e produkteve nga anëtarët NUK është shitje — ajo është transferim i brendshëm. Kooperativa lëshon fletë blerje, jo faturë. Anëtarët nuk paguajnë tatim mbi këtë të ardhur deri në shitjen finale.' },
          { step: 'Shpërndarja e fitimit', desc: 'Fitimi shpërndahet proporcionalisht sipas kontributit të çdo anëtari (sasia e produktit të dorëzuar). Çdo anëtar paguan 10% TAP mbi pjesën e tij.' },
          { step: 'Lehtësi tatimore', desc: 'Kooperativat kanë të drejtë për tatim të reduktuar mbi fitimin (50% e normës standarde) në 3 vitet e para. Fitimi i riinvestuar = i liruar nga tatimi.' },
        ],
      },
      {
        title: 'Subvencione dhe pagesa direkte: Evidenca dhe njohja',
        content:
          'Subvencionet shtetërore bujqësore janë burim i rëndësishëm të ardhurash. Regjistrimi i duhur është çelësi i përputhshmërisë tatimore:',
        items: [
          'Pagesa direkte për hektar: Subvencion nga MBPEU për tokë të punueshme. Njihet si të ardhur në momentin e marrjes (baza e parasë)',
          'Grante IPARD: Grante kapitale për pajisje dhe objekte. Njihen proporcionalisht me amortizimin (SNK 20)',
          'Subvencione për qumësht: Paguhen mujor sipas sasive të dorëzuara. Regjistrohen veçanërisht nga të ardhurat e shitjes',
          'Subvencione për duhan: Pagesë vjetore pas korrjes. Data e njohjes = data e marrjes',
          'Subvencione për blegtori: Për kokë bagëti. Kërkojnë evidencë për numrin e kokave (shenja veshi, regjistri veterinar)',
          'E RËNDËSISHME: Subvencionet e papërdorura (nëse nuk i plotësoni kushtet) duhet të kthehen dhe regjistrohen si detyrim',
          'Trajtimi tatimor: Subvencionet JANË të tatueshme — hyjnë në bazën e tatimit mbi fitimin. Nuk ka lirim',
        ],
        steps: null,
      },
      {
        title: 'Qiraja e tokës: Zbritje dhe dokumentacion',
        content:
          'Toka bujqësore përdoret nëpërmjet pronësisë ose qirasë. Që qiraja të jetë shpenzim i njohur, kërkohet dokumentacion i duhur:',
        items: null,
        steps: [
          { step: 'Kontrata e qirasë', desc: 'Duhet të jetë në formë të shkruar dhe e noterizuar. Kontrata duhet të përmbajë: parcelën (numri kadastral), sipërfaqen, destinimin, afatin dhe çmimin. Pa kontratë = shpenzim i panjohur.' },
          { step: 'Regjistrimi në Kadastër', desc: 'Kontrata e qirasë duhet të regjistrohet në fletën e pronësisë pranë Agjencisë për Kadastër. Qira e paregjistruar = rrezik i mosnjohjes.' },
          { step: 'Çmimi i tregut', desc: 'Qiraja duhet të përputhet me vlerën e tregut. DAP mund ta kontestojë nëse është dukshëm më e lartë se mesatarja rajonale. Referencë: MBPEU publikon qiratë mesatare sipas rajonit.' },
          { step: 'Pagesa nëpërmjet bankës', desc: 'Qiraja DUHET të paguhet nëpërmjet transaksionit bankar për të qenë shpenzim i njohur. Pagesa me para = shpenzim i panjohur për shuma mbi 50.000 MKD.' },
          { step: 'Tatimi mbi qiranë', desc: 'Qiradhënësi (pronari) paguan 10% TAP mbi qiranë e marrë. Nëse pronari është person fizik, qiramarrësi duhet t\'ia mbajë tatimin në pagesë.' },
        ],
      },
      {
        title: 'Si ndihmon Facturino',
        content:
          'Facturino ka funksionalitete të dizajnuara për nevojat e bujqve dhe agro-bizneseve:',
        items: [
          'Qendra kostoje: Ndani asetet e financuara nga granti nga ato vetanake — gati për auditin IPARD',
          'Menaxhimi TVSH: Llogaritje automatike e TVSH 5% për produkte primare bujqësore dhe 18% për të përpunuara',
          'Ndjekja e subvencioneve: Kategori e veçantë për çdo lloj subvencioni (pagesa direkte, IPARD, qumësht, duhan)',
          'Planifikim sezonal: Raporte mujore të ardhurave dhe shpenzimeve — shikoni likuiditetin gjithë vitin',
          'Regjistri i aseteve: Regjistër pajisjes me datë blerje, burim (grant/vetanak) dhe plan amortizimi',
          'Dokumente qiraje: Ruani kontratat, vertetësitë noteriale dhe regjistrat kadastrale në një vend',
          'Raporte auditi: Gjeneroni raporte të gatshme për DAP ose inspektimin IPARD',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'Udhëzues TVSH për Maqedoninë' },
      { slug: 'danok-na-dobivka', title: 'Udhëzues për tatimin mbi fitimin' },
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve' },
    ],
    bottomCta: {
      title: 'Kontabilitet për fermë? Facturino është falas.',
      subtitle: 'Qendra kostoje për IPARD, TVSH 5%, subvencione dhe qira toke — gjithçka në një vend. Pa abonim mujor.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloğa dön',
    tag: 'Sektör',
    title: 'Tarım Muhasebesi Makedonya: Sübvansiyonlar, %5 KDV ve IPARD Hibeleri',
    publishDate: '23 Mayıs 2026',
    readTime: '12 dk okuma',
    intro:
      'Kuzey Makedonya\'da tarım, benzersiz muhasebe zorlukları sunar: birincil üreticiler için %5 indirimli KDV oranı, katı belgelendirme gereklilikleri olan IPARD hibeleri, hasata bağlı mevsimsel gelir, özel vergi muamelesi gören tarım kooperatifleri ve farklı tanıma süreleri olan devlet sübvansiyonları. Bu rehber, çiftçilerin ve tarım işletmelerinin 2026\'da doğru muhasebe hakkında bilmesi gereken her şeyi kapsar.',
    sections: [
      {
        title: 'Tarım ürünlerinde %5 KDV: Ne zaman geçerli?',
        content:
          'KDV Kanunu Md. 30\'a göre, birincil üreticiler tarafından satılan tarım ürünleri %5 indirimli orana tabidir. Ancak kurallar göründüğünden daha katıdır:',
        items: [
          'KDV %5 — Birincil üretici (çiftçi) tarafından satılan taze meyve, sebze, tahıl, süt, yumurta, bal',
          'KDV %5 — Çiftlikten satılan canlı hayvanlar (sığır, koyun, domuz, kümes hayvanları)',
          'KDV %18 — İşlenmiş ürünler (peynir, konserve sebze, meyve suyu) — aynı çiftçi yapsa bile',
          'KDV %18 — Tüccar/distribütör aracılığıyla satılan tarım ürünleri — %5 YALNIZCA üreticiden doğrudan satışa uygulanır',
          'KDV %5 — Tohum, fide ve gübre (tarımsal girdiler)',
          'ÖNEMLİ: %5 oranını kullanmak için TBGM\'deki Tarımsal İşletmeler Sicili\'nde kayıtlı çiftçi olmanız gerekir',
          'Karma faaliyet: Çiftlik hem taze süt (%5) hem de ev yapımı peynir (%18) satıyorsa, her kalem ayrı faturalanır',
        ],
        steps: null,
      },
      {
        title: 'IPARD hibe muhasebesi: AB denetimine hazır',
        content:
          'IPARD (Kırsal Kalkınma için Katılım Öncesi Yardım Aracı) hibeleri katı finansal belgelendirme gereklilikleri taşır. Kayıt hatası = tüm hibenin iadesi:',
        items: null,
        steps: [
          { step: 'Ayrı maliyet merkezleri', desc: 'Her IPARD projesi kendi maliyet merkezine sahip olmalıdır. Hibe ile finanse edilen ve kendi finanse edilen varlıklar ayrı kaydedilmelidir — karıştırılmamalıdır.' },
          { step: '5 yıl varlık tutma', desc: 'IPARD hibesiyle alınan ekipman ve tesisler en az 5 yıl tutulmalıdır. Bu süreden önce satış veya elden çıkarma = hibeyi iade yükümlülüğü.' },
          { step: 'Tedarik belgelendirmesi', desc: 'EUR 5.000 üzerindeki her satın alma en az 3 teklif gerektirir. Tüm yazışmalar, teklifler ve faturalar proje tamamlandıktan sonra 7 yıl saklanmalıdır.' },
          { step: 'Gelir tanıma', desc: 'IPARD hibesi, varlığın amortismanıyla orantılı olarak gelir olarak tanınır. Ekipman EUR 100.000 maliyetinde ve 10 yıl amortisman süresi varsa, yıllık EUR 10.000 hibe geliri tanınır.' },
          { step: 'IPARD alımlarında KDV', desc: 'KDV, IPARD için uygun olmayan maliyettir. KDV mükellefi iseniz alımlardan KDV\'yi normal olarak düşersiniz. Değilseniz — KDV sizin maliyetinizdir, ancak hibe KAPSAMAZ.' },
        ],
      },
      {
        title: 'Mevsimsel gelir ve nakit akış yönetimi',
        content:
          'Tarım mevsimsel bir faaliyettir — gelirler dalgalar halinde gelir, ancak masraflar yıl boyunca devam eder. Bu özel bir yaklaşım gerektirir:',
        items: [
          'Hasat = yoğunlaşmış gelir: Yıllık gelirin %60-80\'i 2-3 ay içinde gelebilir. Kalan 9 ay için likiditeyi planlayın',
          'Avans satışlar: Hasat öncesi satış sözleşmeleri yaygın bir uygulamadır. Avans, mallar teslim edilene kadar gelir değil, yükümlülük olarak kaydedilir',
          'Depolama maliyetleri: Soğuk odalar, silolar ve depolar gelir olmadığında bile maliyet oluşturur. Aylık olarak kaydedin',
          'Fiyat oynaklığı: Tahıl fiyatları yıl içinde %40\'a kadar değişebilir. Stok değerlemesi için ağırlıklı ortalama maliyet (WAC) kullanın',
          'Ürün sigortası: Kabul edilen gider. Poliçe belirli riskleri (dolu, kuraklık, sel) kapsamalıdır',
          'Fire ve doğal kayıp: Tahıl için standart fire %2-3, meyve için %5-8. Normun üzerinde — vergilendirilebilir gider',
        ],
        steps: null,
      },
      {
        title: 'Tarım kooperatifleri: Vergi muamelesi',
        content:
          'Tarım kooperatifleri Makedonya mevzuatında özel statüye sahiptir:',
        items: null,
        steps: [
          { step: 'Tarım kooperatifi nedir?', desc: 'Ortak üretim, işleme veya satış için en az 5 çiftçi tarafından kurulan tüzel kişilik. Kooperatifler Kanunu ile düzenlenir.' },
          { step: 'KDV statüsü', desc: 'Kooperatif, cirosu yıllık 2.000.000 MKD\'yi aşarsa KDV mükellefi olabilir. Üyelerden (birincil üreticiler) alımlar %5 KDV iledir. İşlenmiş ürün satışı %18\'dir.' },
          { step: 'Üye işlemleri', desc: 'Üyelerden ürün alımı satış DEĞİLDİR — dahili transferdir. Kooperatif alım fişi düzenler, fatura değil. Üyeler bu gelir üzerinden nihai satışa kadar vergi ödemez.' },
          { step: 'Kâr dağıtımı', desc: 'Kârlar her üyenin katkısına (teslim edilen ürün miktarı) orantılı olarak dağıtılır. Her üye kendi payından %10 GV öder.' },
          { step: 'Vergi teşvikleri', desc: 'Kooperatifler ilk 3 yılda indirimli kurumlar vergisi hakkına sahiptir (standart oranın %50\'si). Yeniden yatırılan kâr = vergiden muaf.' },
        ],
      },
      {
        title: 'Sübvansiyonlar ve doğrudan ödemeler: Kayıt ve tanıma',
        content:
          'Devlet tarım sübvansiyonları önemli bir gelir kaynağıdır. Doğru kayıt, vergi uyumunun anahtarıdır:',
        items: [
          'Hektar başına doğrudan ödemeler: İşlenen arazi için TBGM sübvansiyonu. Alındığında gelir olarak tanınır (nakit esası)',
          'IPARD hibeleri: Ekipman ve tesisler için sermaye hibeleri. Amortismanla orantılı olarak tanınır (UMS 20)',
          'Süt sübvansiyonları: Teslim edilen miktara göre aylık ödenir. Satış gelirinden ayrı kaydedilir',
          'Tütün sübvansiyonları: Hasat sonrası yıllık ödeme. Tanıma tarihi = alım tarihi',
          'Hayvancılık sübvansiyonları: Hayvan başına. Baş sayısı kaydı gerektirir (kulak küpeleri, veteriner sicili)',
          'ÖNEMLİ: Kullanılmayan sübvansiyonlar (koşulları karşılamıyorsanız) iade edilmeli ve yükümlülük olarak kaydedilmelidir',
          'Vergi muamelesi: Sübvansiyonlar VERGİLENDİRİLEBİLİR — kurumlar vergisi matrahına girer. Muafiyet yoktur',
        ],
        steps: null,
      },
      {
        title: 'Arazi kiralama indirimleri: Gereksinimler ve belgelendirme',
        content:
          'Tarım arazisi mülkiyet veya kiralama yoluyla kullanılır. Kiralamanın kabul edilen gider olması için doğru belgelendirme gerekir:',
        items: null,
        steps: [
          { step: 'Kira sözleşmesi', desc: 'Yazılı formda ve noter onaylı olmalıdır. Sözleşmede şunlar bulunmalıdır: parsel (kadastro numarası), alan, amaç, süre ve fiyat. Sözleşme olmadan = indirilemeyen gider.' },
          { step: 'Kadastro\'da tescil', desc: 'Kira sözleşmesi Tapu ve Kadastro Ajansı\'nda tapu kütüğüne kaydedilmelidir. Tescil edilmemiş kiralama = tanınmama riski.' },
          { step: 'Piyasa fiyatı', desc: 'Kira piyasa değerine uygun olmalıdır. UJP, bölge ortalamasından önemli ölçüde yüksekse itiraz edebilir. Referans: TBGM bölgelere göre ortalama kiraları yayınlar.' },
          { step: 'Banka ile ödeme', desc: 'Kiranın kabul edilen gider olması için banka işlemiyle ödenmesi GEREKİR. Nakit ödeme = 50.000 MKD üzerindeki tutarlar için indirilemeyen gider.' },
          { step: 'Kira vergisi', desc: 'Kiralayan (mal sahibi) aldığı kira üzerinden %10 GV öder. Mal sahibi gerçek kişi ise, kiracı ödemede vergi kesintisi yapmalıdır.' },
        ],
      },
      {
        title: 'Facturino nasıl yardımcı olur',
        content:
          'Facturino, çiftçilerin ve tarım işletmelerinin ihtiyaçları için tasarlanmış özelliklere sahiptir:',
        items: [
          'Maliyet merkezleri: Hibe ile finanse edilen varlıkları kendinizinkinden ayırın — IPARD denetimine hazır',
          'KDV yönetimi: Birincil tarım ürünleri için %5 ve işlenmiş ürünler için %18 KDV otomatik hesaplama',
          'Sübvansiyon takibi: Her sübvansiyon türü için ayrı kategori (doğrudan ödemeler, IPARD, süt, tütün)',
          'Mevsimsel planlama: Aylık gelir ve gider raporları — yıl boyunca likiditinizi görün',
          'Varlık kaydı: Satın alma tarihi, kaynak (hibe/kendi) ve amortisman planı ile ekipman sicili',
          'Kiralama belgeleri: Sözleşmeleri, noter onaylarını ve kadastro kayıtlarını tek yerde saklayın',
          'Denetim raporları: UJP veya IPARD denetimi için hazır raporlar oluşturun',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'ddv-vodich-mk', title: 'Makedonya KDV Rehberi' },
      { slug: 'danok-na-dobivka', title: 'Kurumlar Vergisi Rehberi' },
      { slug: 'upravljanje-so-rashodi', title: 'Gider Yönetimi Rehberi' },
    ],
    bottomCta: {
      title: 'Çiftlik muhasebesi? Facturino ücretsiz.',
      subtitle: 'IPARD için maliyet merkezleri, %5 KDV, sübvansiyonlar ve arazi kiralama — hepsi tek yerde. Aylık abonelik yok.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function SmetkovodstvoZaZemjodelstvoPage({
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
    slug: 'smetkovodstvo-za-zemjodelstvo',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['земјоделство', 'agriculture', 'ДДВ', 'VAT', 'IPARD', 'субвенции', 'subsidies', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/smetkovodstvo-za-zemjodelstvo` },
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
