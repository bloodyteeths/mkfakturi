import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/budzet-i-kontrola-troshoci', {
    title: {
      mk: 'Буџетирање за мали фирми: Контролирај ги трошоците пред да те контролираат тие — Facturino',
      en: 'Budgeting for Small Businesses: Control Your Costs Before They Control You — Facturino',
      sq: 'Buxhetimi për biznese të vogla: Kontrollo shpenzimet para se të të kontrollojnë ato — Facturino',
      tr: 'Küçük İşletmeler İçin Bütçeleme: Masrafları Sizi Kontrol Etmeden Önce Kontrol Edin — Facturino',
    },
    description: {
      mk: 'Научете како да направите буџет за вашата мала фирма во Facturino. Центри на трошоци, BI Dashboard и практични совети за буџетирање во Македонија.',
      en: 'Learn how to create a budget for your small business in Facturino. Cost centers, BI Dashboard, and practical budgeting tips for businesses in Macedonia.',
      sq: 'Mësoni si të krijoni një buxhet për biznesin tuaj të vogël në Facturino. Qendra kostosh, BI Dashboard dhe këshilla praktike buxhetimi për biznese në Maqedoni.',
      tr: 'Facturino\'da küçük işletmeniz için nasıl bütçe oluşturacağınızı öğrenin. Maliyet merkezleri, BI Dashboard ve Makedonya\'daki işletmeler için pratik bütçeleme ipuçları.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Едукација',
    title: 'Буџетирање за мали фирми: Контролирај ги трошоците пред да те контролираат тие',
    publishDate: '15 март 2026',
    readTime: '9 мин читање',
    intro: 'Според истражувањата, околу 80% од малите и средни претпријатија во Македонија немаат формален буџет. Резултатот? Ненадејни трошоци ги уриваат маржите, даночната сезона носи непријатни изненадувања, а проблемите со готовинскиот тек стануваат хроничен проблем. Добрата вест е дека буџетирањето не мора да биде комплицирано — со правилниот алат, можете да ги контролирате трошоците пред тие да ве контролираат вас.',
    sections: [
      {
        title: 'Зошто ви треба буџет',
        content: 'Буџетот не е само табела со бројки — тоа е вашиот финансиски компас. Без него, одлуките се носат „на око", а грешките се откриваат кога е предоцна. Еве зошто секој бизнис, без разлика на големината, треба буџет:',
        items: [
          'Видливост во трошењето — знаете точно каде одат парите секој месец',
          'Рано предупредување за прекумерно трошење — реагирате пред да стане критично',
          'Подобра позиција при преговори со добавувачи — имате конкретни бројки за поддршка',
          'Самоуверени одлуки за вработување — знаете дали можете да си дозволите нов вработен',
          'Даночно планирање — нема изненадувања кога ќе дојде ДДВ-04 или данок на добивка',
        ],
        steps: null,
      },
      {
        title: 'Како да направите буџет во Facturino',
        content: 'Facturino го прави буџетирањето едноставно и интуитивно. Во 5 чекори имате целосен преглед и контрола над финансиите:',
        items: null,
        steps: [
          { step: 'Поставете временски период', desc: 'Изберете дали буџетот е месечен, квартален или годишен. За мали фирми, препорачуваме месечен буџет со квартални ревизии. Ова ви дава доволно флексибилност да реагирате на промени, а сепак одржувате долгорочна перспектива.' },
          { step: 'Дефинирајте категории', desc: 'Поделете ги трошоците во логички категории: кирија, плати, маркетинг, материјали, комунални услуги, транспорт, одржување. Facturino доаѓа со предефинирани категории за македонски бизниси, но можете да ги прилагодите според вашите потреби.' },
          { step: 'Поставете износи по категорија', desc: 'За секоја категорија, внесете го планираниот месечен износ. Користете ги историските податоци од Facturino за реалистични проценки — системот автоматски ви ги покажува просечните трошоци од претходните месеци.' },
          { step: 'Следете ги актуелните трошоци во реално време', desc: 'Штом буџетот е активен, Facturino автоматски ги споредува реалните трошоци со планираните. Визуелни графикони покажуваат каде сте во однос на буџетот — зелено значи во рамки, жолто значи внимание, црвено значи пречекорување.' },
          { step: 'Добивајте известувања при приближување до лимитите', desc: 'Поставете прагови за известувања (на пример 80% од буџетот). Facturino ве известува преку email или нотификација во апликацијата кога категоријата се приближува до лимитот, давајќи ви време да реагирате.' },
        ],
      },
      {
        title: 'Центри на трошоци — Знајте каде оди секој денар',
        content: 'Центрите на трошоци ви дозволуваат да ги алоцирате расходите по оддели, проекти или локации. Наместо да гледате еден збирен број за „маркетинг", можете да видите точно колку трошите по канал, кампања или тим. Ова е клучно за донесување информирани одлуки и оптимизација на профитабилноста.',
        items: [
          'Маркетинг одделение — следете ROI по кампања и канал, откријте што навистина носи резултати',
          'Производство — контролирајте ги суровините, енергијата и трошоците за одржување по производна линија',
          'Продажба — споредете ги трошоците за аквизиција на клиенти по продавач или регион',
          'Администрација — следете ги оперативните трошоци и идентификувајте можности за заштеда',
        ],
        steps: null,
      },
      {
        title: 'BI Dashboard — Сe на еден екран',
        content: 'BI Dashboard во Facturino ви дава визуелен преглед на целокупното финансиско здравје на вашиот бизнис во реално време. Наместо да ровите низ табели и извештаи, сe е прикажано преку интерактивни графикони и индикатори на едно место:',
        items: [
          'Тренд на приходи — месечна динамика на продажба со споредба по претходни периоди',
          'Разбивка на трошоци — кои категории „јадат" најмногу од буџетот, прикажано преку pie и bar графикони',
          'Искористеност на буџетот — процент на потрошен буџет по категорија со визуелни индикатори',
          'Топ клиенти — кои клиенти генерираат најмногу приход и профит за вашиот бизнис',
          'Доспеани фактури — преглед на ненаплатени побарувања со стареење и приоритизација',
        ],
        steps: null,
      },
      {
        title: 'Практичен пример: Мала продавница во Скопје',
        content: 'Да видиме како буџетирањето изгледа во пракса. Замислете мала продавница за облека во Скопје со 4 вработени:',
        items: null,
        steps: [
          { step: 'Цел за приходи: 500.000 МКД/месечно', desc: 'Врз основа на историските податоци и сезонските трендови, сопственикот поставува реалистична месечна цел. Facturino автоматски го следи напредокот кон целта и прикажува проценка дали ќе биде достигната до крајот на месецот.' },
          { step: 'Фиксни трошоци: кирија 30.000, плати 200.000, комуналии 15.000 МКД', desc: 'Овие трошоци се предвидливи и месечно исти. Во Facturino, ги поставувате како фиксни ставки во буџетот — системот ги одзема од приходите автоматски и ви покажува колку останува за варијабилни трошоци и профит.' },
          { step: 'Варијабилни трошоци: залиха 150.000, маркетинг 20.000 МКД', desc: 'Залихата и маркетингот варираат месечно. Со центрите на трошоци, сопственикот може да следи точно колку троши по добавувач за залиха и по канал за маркетинг (Facebook, Google, флаери). Ако маркетингот на Facebook не дава резултати, парите се пренасочуваат.' },
          { step: 'Целна маржа на профит: 17% (85.000 МКД)', desc: 'Со приход од 500.000 и вкупни трошоци од 415.000 МКД, целната маржа е 17%. BI Dashboard-от покажува во реално време дали маржата се одржува или се намалува, давајќи време за корективни мерки пред да биде предоцна.' },
          { step: 'Месечна ревизија и прилагодување', desc: 'На крајот на секој месец, сопственикот ги анализира отстапувањата: Дали залихата беше повисока од планираното? Дали маркетингот даде резултати? Facturino генерира извештај Буџет vs Актуелно со едно кликнување, покажувајќи точно каде се отстапувањата.' },
        ],
      },
      {
        title: 'Започнете со буџетирање денес',
        content: 'Не чекајте крајот на годината за да дознаете дека сте потрошиле повеќе отколку што заработивте. Со Facturino, буџетирањето е едноставно, визуелно и автоматизирано. Регистрирајте се, внесете ги вашите категории и износи, и за 15 минути имате целосен буџет со автоматско следење. Вашите финансии заслужуваат повеќе од „на око" — дајте им структура со Facturino.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'cash-flow-mk', title: 'Како да го подобрите готовинскиот тек на вашата фирма' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Водич за мали претпријатија' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Годишно затворање на книги: 6 чекори со Facturino' },
    ],
    cta: {
      title: 'Ставете ги трошоците под контрола',
      desc: 'Регистрирајте се бесплатно и започнете со буџетирање во Facturino — буџети, центри на трошоци и BI Dashboard на дофат.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Education',
    title: 'Budgeting for Small Businesses: Control Your Costs Before They Control You',
    publishDate: 'March 15, 2026',
    readTime: '9 min read',
    intro: 'Studies show that around 80% of small and medium enterprises in Macedonia don\'t have a formal budget. The result? Unexpected expenses destroy margins, tax season brings unpleasant surprises, and cash flow problems become chronic. The good news is that budgeting doesn\'t have to be complicated — with the right tool, you can control your costs before they control you.',
    sections: [
      {
        title: 'Why You Need a Budget',
        content: 'A budget isn\'t just a spreadsheet with numbers — it\'s your financial compass. Without one, decisions are made by guesswork, and mistakes are discovered when it\'s too late. Here\'s why every business, regardless of size, needs a budget:',
        items: [
          'Spending visibility — know exactly where money goes every month',
          'Early warning for overspending — react before it becomes critical',
          'Better negotiation position with suppliers — you have concrete numbers to back you up',
          'Confident hiring decisions — know whether you can afford a new employee',
          'Tax planning — no surprises when VAT returns or corporate tax are due',
        ],
        steps: null,
      },
      {
        title: 'How to Create a Budget in Facturino',
        content: 'Facturino makes budgeting simple and intuitive. In 5 steps you have full visibility and control over your finances:',
        items: null,
        steps: [
          { step: 'Set a time period', desc: 'Choose whether the budget is monthly, quarterly, or annual. For small businesses, we recommend a monthly budget with quarterly reviews. This gives you enough flexibility to react to changes while maintaining a long-term perspective.' },
          { step: 'Define categories', desc: 'Divide expenses into logical categories: rent, salaries, marketing, materials, utilities, transport, maintenance. Facturino comes with predefined categories for Macedonian businesses, but you can customize them to fit your needs.' },
          { step: 'Set amounts per category', desc: 'For each category, enter the planned monthly amount. Use historical data from Facturino for realistic estimates — the system automatically shows you average costs from previous months.' },
          { step: 'Track actuals vs budget in real time', desc: 'Once the budget is active, Facturino automatically compares real costs with planned ones. Visual charts show where you stand relative to the budget — green means within range, yellow means caution, red means overspending.' },
          { step: 'Get alerts when approaching limits', desc: 'Set notification thresholds (for example, 80% of budget). Facturino notifies you via email or in-app notification when a category is approaching its limit, giving you time to react.' },
        ],
      },
      {
        title: 'Cost Centers — Know Where Every Denar Goes',
        content: 'Cost centers allow you to allocate expenses by department, project, or location. Instead of seeing a single aggregate number for "marketing," you can see exactly how much you\'re spending per channel, campaign, or team. This is key to making informed decisions and optimizing profitability.',
        items: [
          'Marketing department — track ROI by campaign and channel, discover what truly drives results',
          'Production — control raw materials, energy, and maintenance costs per production line',
          'Sales — compare client acquisition costs by salesperson or region',
          'Administration — track operational costs and identify savings opportunities',
        ],
        steps: null,
      },
      {
        title: 'BI Dashboard — Everything on One Screen',
        content: 'The BI Dashboard in Facturino gives you a visual overview of your entire business\'s financial health in real time. Instead of digging through tables and reports, everything is displayed through interactive charts and indicators in one place:',
        items: [
          'Revenue trends — monthly sales dynamics with comparison to previous periods',
          'Expense breakdown — which categories consume the most budget, shown through pie and bar charts',
          'Budget utilization — percentage of budget spent per category with visual indicators',
          'Top customers — which clients generate the most revenue and profit for your business',
          'Overdue invoices — overview of uncollected receivables with aging and prioritization',
        ],
        steps: null,
      },
      {
        title: 'Practical Example: A Small Shop in Skopje',
        content: 'Let\'s see how budgeting looks in practice. Imagine a small clothing store in Skopje with 4 employees:',
        items: null,
        steps: [
          { step: 'Revenue target: 500,000 MKD/month', desc: 'Based on historical data and seasonal trends, the owner sets a realistic monthly target. Facturino automatically tracks progress toward the goal and shows an estimate of whether it will be reached by month\'s end.' },
          { step: 'Fixed costs: rent 30,000, salaries 200,000, utilities 15,000 MKD', desc: 'These costs are predictable and the same every month. In Facturino, you set them as fixed items in the budget — the system deducts them from revenue automatically and shows how much remains for variable costs and profit.' },
          { step: 'Variable costs: inventory 150,000, marketing 20,000 MKD', desc: 'Inventory and marketing vary monthly. With cost centers, the owner can track exactly how much is spent per supplier for inventory and per channel for marketing (Facebook, Google, flyers). If Facebook marketing isn\'t delivering results, funds are redirected.' },
          { step: 'Target profit margin: 17% (85,000 MKD)', desc: 'With revenue of 500,000 and total costs of 415,000 MKD, the target margin is 17%. The BI Dashboard shows in real time whether the margin is holding or declining, giving time for corrective measures before it\'s too late.' },
          { step: 'Monthly review and adjustment', desc: 'At the end of each month, the owner analyzes deviations: Was inventory higher than planned? Did marketing deliver results? Facturino generates a Budget vs Actual report with one click, showing exactly where the deviations are.' },
        ],
      },
      {
        title: 'Start Budgeting Today',
        content: 'Don\'t wait until year-end to discover you\'ve spent more than you\'ve earned. With Facturino, budgeting is simple, visual, and automated. Sign up, enter your categories and amounts, and in 15 minutes you have a complete budget with automatic tracking. Your finances deserve more than guesswork — give them structure with Facturino.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'cash-flow-mk', title: 'How to Improve Your Business Cash Flow' },
      { slug: 'danok-na-dobivka', title: 'Corporate Tax: A Guide for Small Businesses' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Year-End Closing: 6 Steps with Facturino' },
    ],
    cta: {
      title: 'Put Your Costs Under Control',
      desc: 'Sign up free and start budgeting in Facturino — budgets, cost centers, and BI Dashboard at your fingertips.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Edukim',
    title: 'Buxhetimi per biznese te vogla: Kontrollo shpenzimet para se te te kontrollojne ato',
    publishDate: '15 mars 2026',
    readTime: '9 min lexim',
    intro: 'Sipas studimeve, rreth 80% e ndermarrjeve te vogla dhe te mesme ne Maqedoni nuk kane nje buxhet formal. Rezultati? Shpenzimet e papritura shkaterrojne marzhet, sezoni tatimor sjell surpriza te pakendshme, dhe problemet e rrjedhes se parase behen kronike. Lajmi i mire eshte se buxhetimi nuk duhet te jete i komplikuar — me mjetin e duhur, mund t\'i kontrolloni shpenzimet para se ato t\'ju kontrollojne juve.',
    sections: [
      {
        title: 'Pse ju nevojitet nje buxhet',
        content: 'Nje buxhet nuk eshte vetem nje tabele me numra — eshte busulla juaj financiare. Pa te, vendimet merren me hamendje, dhe gabimet zbulohen kur eshte shume vone. Ja pse cdo biznes, pavaresisht nga madhesia, ka nevoje per buxhet:',
        items: [
          'Dukshmeri ne shpenzime — e dini saktesisht ku shkojne parate cdo muaj',
          'Paralajmerim i hershem per mbishpenzim — reagoni para se te behet kritike',
          'Pozicion me i mire ne negociata me furnizuesit — keni numra konkrete per mbeshtetje',
          'Vendime te sigurta per punesim — e dini nese mund te perballoni nje punonjes te ri',
          'Planifikim tatimor — asnje surprize kur vjen TVSH ose tatimi mbi fitimin',
        ],
        steps: null,
      },
      {
        title: 'Si te krijoni nje buxhet ne Facturino',
        content: 'Facturino e ben buxhetimin te thjeshte dhe intuitiv. Ne 5 hapa keni pamje te plote dhe kontroll mbi financat tuaja:',
        items: null,
        steps: [
          { step: 'Percaktoni periudhen kohore', desc: 'Zgjidhni nese buxheti eshte mujor, tremujor ose vjetor. Per biznese te vogla, rekomandojme buxhet mujor me rishikime tremujore. Kjo ju jep fleksibilitet te mjaftueshem per te reaguar ndaj ndryshimeve duke ruajtur perspektiven afatgjate.' },
          { step: 'Percaktoni kategorite', desc: 'Ndani shpenzimet ne kategori logjike: qira, paga, marketing, materiale, sherbime komunale, transport, mirembajtje. Facturino vjen me kategori te paracaktuara per bizneset maqedonase, por mund t\'i personalizoni sipas nevojave tuaja.' },
          { step: 'Percaktoni shumat per kategori', desc: 'Per cdo kategori, vendosni shumen e planifikuar mujore. Perdorni te dhenat historike nga Facturino per vleresime realiste — sistemi automatikisht ju tregon kostot mesatare nga muajt e meparshem.' },
          { step: 'Ndiqni shpenzimet aktuale ne kohe reale', desc: 'Pasi buxheti eshte aktiv, Facturino automatikisht krahason kostot reale me ato te planifikuara. Grafikonet vizuale tregojne ku qendroni ne raport me buxhetin — jeshile do te thote brenda kufijve, e verdhe do te thote kujdes, e kuqe do te thote tejkalim.' },
          { step: 'Merrni njoftime kur i afroheni kufijve', desc: 'Percaktoni pragje njoftimi (per shembull 80% e buxhetit). Facturino ju njofton permes email-it ose njoftimit ne aplikacion kur nje kategori po i afrohet kufirit, duke ju dhene kohe per te reaguar.' },
        ],
      },
      {
        title: 'Qendrat e kostove — Dijeni ku shkon cdo denar',
        content: 'Qendrat e kostove ju lejojne te alokoni shpenzimet sipas departamentit, projektit ose vendndodhjes. Ne vend qe te shihni nje numer te vetem permbledhes per "marketingun", mund te shihni saktesisht sa po shpenzoni per kanal, fushate ose ekip. Kjo eshte celesi per marrjen e vendimeve te informuara dhe optimizimin e perfitimshmeriise.',
        items: [
          'Departamenti i marketingut — ndiqni ROI per fushate dhe kanal, zbuloni cfare sjell vertete rezultate',
          'Prodhimi — kontrolloni lendet e para, energjine dhe kostot e mirembajtjes per linje prodhimi',
          'Shitjet — krahasoni kostot e blerjes se klienteve per shites ose rajon',
          'Administrata — ndiqni kostot operacionale dhe identifikoni mundesi kursimi',
        ],
        steps: null,
      },
      {
        title: 'BI Dashboard — Gjithcka ne nje ekran',
        content: 'BI Dashboard ne Facturino ju jep nje pamje vizuale te shendetit te pergjithshem financiar te biznesit tuaj ne kohe reale. Ne vend qe te kerrkoni neper tabela dhe raporte, gjithcka tregohet permes grafikoneve interaktive dhe indikatoreve ne nje vend:',
        items: [
          'Trendet e te ardhurave — dinamika mujore e shitjeve me krahasim me periudhat e meparshme',
          'Zberthimi i shpenzimeve — cilat kategori "hane" me shume nga buxheti, te treguara permes grafikoneve pie dhe bar',
          'Perdorimi i buxhetit — perqindja e buxhetit te shpenzuar per kategori me indikatore vizuale',
          'Klientet kryesore — cilet kliente gjenerojne me shume te ardhura dhe fitim per biznesin tuaj',
          'Faturat e vonuara — pamje e arkëtimeve te papaguara me vjetërsim dhe prioritizim',
        ],
        steps: null,
      },
      {
        title: 'Shembull praktik: Nje dyqan i vogel ne Shkup',
        content: 'Le te shohim si duket buxhetimi ne praktike. Imagjinoni nje dyqan te vogel veshjesh ne Shkup me 4 punonjes:',
        items: null,
        steps: [
          { step: 'Objektivi i te ardhurave: 500,000 MKD/muaj', desc: 'Bazuar ne te dhenat historike dhe trendet sezonale, pronari vendos nje objektiv mujor realist. Facturino automatikisht ndjek progresin drejt objektivit dhe tregon nje vleresim nese do te arrihet deri ne fund te muajit.' },
          { step: 'Kostot fikse: qira 30,000, paga 200,000, komunale 15,000 MKD', desc: 'Keto kosto jane te parashikueshme dhe te njejta cdo muaj. Ne Facturino, i vendosni si zera fikse ne buxhet — sistemi i zbret nga te ardhurat automatikisht dhe tregon sa mbetet per kostot variabile dhe fitimin.' },
          { step: 'Kostot variabile: inventar 150,000, marketing 20,000 MKD', desc: 'Inventari dhe marketingu ndryshojne cdo muaj. Me qendrat e kostove, pronari mund te ndjeke saktesisht sa shpenzohet per furnizues per inventar dhe per kanal per marketing (Facebook, Google, fletushka). Nese marketingu ne Facebook nuk jep rezultate, fondet ridrejtohen.' },
          { step: 'Marzhi i synuar i fitimit: 17% (85,000 MKD)', desc: 'Me te ardhura prej 500,000 dhe kosto totale prej 415,000 MKD, marzhi i synuar eshte 17%. BI Dashboard tregon ne kohe reale nese marzhi po mbahet ose po ulet, duke dhene kohe per masa korrigjuese para se te jete shume vone.' },
          { step: 'Rishikim mujor dhe pershtaje', desc: 'Ne fund te cdo muaji, pronari analizon deviimet: A ishte inventari me i larte se i planifikuari? A dha rezultate marketingu? Facturino gjeneron nje raport Buxhet vs Aktual me nje klik, duke treguar saktesisht ku jane deviimet.' },
        ],
      },
      {
        title: 'Filloni buxhetimin sot',
        content: 'Mos prisni deri ne fund te vitit per te zbuluar se keni shpenzuar me shume sesa keni fituar. Me Facturino, buxhetimi eshte i thjeshte, vizual dhe i automatizuar. Regjistrohuni, vendosni kategorite dhe shumat tuaja, dhe ne 15 minuta keni nje buxhet te plote me ndjekje automatike. Financat tuaja meritojne me shume se hamendje — jepuni strukture me Facturino.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    related: [
      { slug: 'cash-flow-mk', title: 'Si ta permiresoni rrjedhen e parase te biznesit tuaj' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Udherrëfyes per biznese te vogla' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Mbyllja e vitit: 6 hapa me Facturino' },
    ],
    cta: {
      title: 'Vendosni shpenzimet nen kontroll',
      desc: 'Regjistrohuni falas dhe filloni buxhetimin ne Facturino — buxhete, qendra kostosh dhe BI Dashboard ne gishtat tuaj.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Egitim',
    title: 'Kucuk Isletmeler Icin Butceleme: Masraflari Sizi Kontrol Etmeden Once Kontrol Edin',
    publishDate: '15 Mart 2026',
    readTime: '9 dk okuma',
    intro: 'Arastirmalara gore, Makedonya\'daki kucuk ve orta olcekli isletmelerin yaklasik %80\'inin resmi bir butcesi yoktur. Sonuc? Beklenmedik masraflar marjlari yok eder, vergi doneminde hoslanilmayan surprizler yasanir ve nakit akisi sorunlari kronik hale gelir. Iyi haber su ki butceleme karmasik olmak zorunda degil — dogru aracla masraflarinizi onlar sizi kontrol etmeden once kontrol edebilirsiniz.',
    sections: [
      {
        title: 'Neden bir butceye ihtiyaciniz var',
        content: 'Butce sadece rakamlardan olusan bir tablo degildir — finansal pusulanizdir. Butce olmadan kararlar tahminle alinir ve hatalar cok gec fark edilir. Iste buyuklugunden bagimsiz olarak her isletmenin butceye ihtiyac duymasinin nedenleri:',
        items: [
          'Harcama gorunurlugu — paranin her ay tam olarak nereye gittigini bilin',
          'Asiri harcama icin erken uyari — kritik hale gelmeden once tepki verin',
          'Tedarikci muzakerelerinde daha iyi pozisyon — destekleyici somut rakamlariniz var',
          'Guvencli ise alim kararlari — yeni bir calisan alip alamayacaginizi bilin',
          'Vergi planlamasi — KDV veya kurumlar vergisi odeme zamani geldiginde surpriz yok',
        ],
        steps: null,
      },
      {
        title: 'Facturino\'da butce nasil olusturulur',
        content: 'Facturino butcelemeyi basit ve sezgisel hale getirir. 5 adimda finanslariniz uzerinde tam gorus ve kontrol elde edersiniz:',
        items: null,
        steps: [
          { step: 'Zaman dilimini belirleyin', desc: 'Butcenin aylik, uc aylik veya yillik olup olmayacagini secin. Kucuk isletmeler icin uc aylik incelemelerle aylik butce oneriyoruz. Bu, uzun vadeli bakis acisini korurken degisikliklere tepki vermek icin yeterli esneklik saglar.' },
          { step: 'Kategorileri tanimlayin', desc: 'Masraflari mantiksal kategorilere boleun: kira, maaslar, pazarlama, malzemeler, kamu hizmetleri, ulasim, bakim. Facturino, Makedon isletmeleri icin onceden tanimlanmis kategorilerle birlikte gelir, ancak ihtiyaclariniza gore ozellesirebilirsiniz.' },
          { step: 'Kategori basina tutarlari belirleyin', desc: 'Her kategori icin planlanan aylik tutari girin. Gercekci tahminler icin Facturino\'daki gecmis verileri kullanin — sistem otomatik olarak onceki aylardaki ortalama maliyetleri gosterir.' },
          { step: 'Gerceklesen giderleri gercek zamanli olarak izleyin', desc: 'Butce aktif oldugunda, Facturino gercek maliyetleri planlananlarla otomatik olarak karsilastirir. Gorsel grafikler butceye gore nerede oldugunuzu gosterir — yesil sinirlar icinde, sari dikkat, kirmizi asim anlamina gelir.' },
          { step: 'Sinirlara yaklasinca uyarilar alin', desc: 'Bildirim esikleri belirleyin (ornegin butcenin %80\'i). Facturino, bir kategori sinirina yaklastiginda e-posta veya uygulama ici bildirimle sizi uyarir ve tepki vermeniz icin zaman tanir.' },
        ],
      },
      {
        title: 'Maliyet merkezleri — Her denarin nereye gittigini bilin',
        content: 'Maliyet merkezleri, masraflari departmana, projeye veya konuma gore ayirmaniza olanak tanir. "Pazarlama" icin tek bir toplam rakam gormek yerine, kanal, kampanya veya ekip basina tam olarak ne kadar harcadiginizi gorebilirsiniz. Bu, bilinçli kararlar almak ve karliligi optimize etmek icin anahtardir.',
        items: [
          'Pazarlama departmani — kampanya ve kanal bazinda ROI\'yi izleyin, gercekten sonuc getireni kesfedin',
          'Uretim — hammadde, enerji ve bakim maliyetlerini uretim hatti bazinda kontrol edin',
          'Satis — musteri edinme maliyetlerini satici veya bolge bazinda karsilastirin',
          'Yonetim — operasyonel maliyetleri izleyin ve tasarruf firsatlarini belirleyin',
        ],
        steps: null,
      },
      {
        title: 'BI Dashboard — Her sey tek ekranda',
        content: 'Facturino\'daki BI Dashboard, isletmenizin genel finansal sagliginin gercek zamanli gorsel bir ozetini sunar. Tablolar ve raporlar arasinda aramak yerine, her sey tek bir yerde interaktif grafikler ve gostergeler araciligiyla goruntulenir:',
        items: [
          'Gelir trendleri — onceki donemlerle karsilastirmali aylik satis dinamikleri',
          'Masraf dokuumu — hangi kategorilerin butceden en cok "yedigini" pasta ve cubuk grafiklerle gosteren',
          'Butce kullanimi — gorsel gostergelerle kategori basina harcanan butce yuzdesi',
          'En iyi musteriler — isletmeniz icin en cok gelir ve kar ureten musteriler',
          'Vadesi gecmis faturalar — yasi ve onceliklendirilmesi ile tahsil edilmemis alacaklara genel bakis',
        ],
        steps: null,
      },
      {
        title: 'Pratik ornek: Uskup\'te kucuk bir dukkan',
        content: 'Butcelemenin pratikte nasil gorunduugunu gorelim. 4 calisani olan Uskup\'te kucuk bir giyim magazasi hayal edin:',
        items: null,
        steps: [
          { step: 'Gelir hedefi: ayda 500.000 MKD', desc: 'Gecmis verilere ve mevsimsel trendlere dayanarak, isletme sahibi gercekci bir aylik hedef belirler. Facturino otomatik olarak hedefe dogru ilerlemeyi izler ve ay sonuna kadar ulasip ulasilmayacagina dair bir tahmin gosterir.' },
          { step: 'Sabit maliyetler: kira 30.000, maaslar 200.000, faturalar 15.000 MKD', desc: 'Bu maliyetler ongorebulebilir ve her ay aynidir. Facturino\'da bunlari butcede sabit kalemler olarak ayarlarsiniz — sistem bunlari gelirden otomatik olarak duser ve degisken maliyetler ve kar icin ne kadar kaldigini gosterir.' },
          { step: 'Degisken maliyetler: stok 150.000, pazarlama 20.000 MKD', desc: 'Stok ve pazarlama aydan aya degisir. Maliyet merkezleriyle, isletme sahibi stok icin tedarikci basina ve pazarlama icin kanal basina (Facebook, Google, el ilani) tam olarak ne kadar harcadigini izleyebilir. Facebook pazarlamasi sonuc vermiyorsa, fonlar yonlendirilir.' },
          { step: 'Hedef kar marji: %17 (85.000 MKD)', desc: '500.000 gelir ve 415.000 MKD toplam maliyetle hedef marj %17\'dir. BI Dashboard, marjin korunup korunmadigini veya dusup dusmedgini gercek zamanli olarak gosterir ve cok gec olmadan duzeltici onlemler icin zaman tanir.' },
          { step: 'Aylik inceleme ve ayarlama', desc: 'Her ayin sonunda, isletme sahibi sapmalari analiz eder: Stok planlanandan yuksek miydi? Pazarlama sonuc verdi mi? Facturino tek tikla Butce - Gerceklesen raporu ureterek sapmalarin tam olarak nerede oldugunu gosterir.' },
        ],
      },
      {
        title: 'Bugun butcelemeye baslayin',
        content: 'Kazandiginizdan fazla harcadiginizi kesfetmek icin yil sonunu beklemeyin. Facturino ile butceleme basit, gorsel ve otomatiktir. Kaydolun, kategorilerinizi ve tutarlarinizi girin, 15 dakikada otomatik izlemeli eksiksiz bir butceniz olsun. Finanslariniz tahmin yuruttmekten daha fazlasini hak ediyor — Facturino ile onlara yapi kazandirin.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili makaleler',
    related: [
      { slug: 'cash-flow-mk', title: 'Isletmenizin nakit akisini nasil iyilestirirsiniz' },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Kucuk isletmeler icin rehber' },
      { slug: 'godishno-zatvoranje-facturino', title: 'Yil sonu kapanisi: Facturino ile 6 adim' },
    ],
    cta: {
      title: 'Masraflarinizi kontrol altina alin',
      desc: 'Ucretsiz kaydolun ve Facturino\'da butcelemeye baslayin — butceler, maliyet merkezleri ve BI Dashboard parmaklarinizin ucunda.',
      button: 'Ucretsiz basla',
    },
  },
} as const

export default async function BudzetKontrolaTroshociPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
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

      {/* HERO IMAGE */}
      <div className="container max-w-3xl mx-auto px-4 sm:px-6 mb-8">
        <div className="rounded-2xl overflow-hidden shadow-lg">
          <img src="/assets/images/blog/blog_budget_cost_control.png" alt="Буџетирање и контрола на трошоци Facturino - финансиско планирање за мали фирми" className="w-full h-auto" />
        </div>
      </div>

      {/* ARTICLE BODY */}
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

      {/* BOTTOM CTA */}
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
