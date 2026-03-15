import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/nabavki-i-narachki', {
    title: {
      mk: 'Дигитални нарачки за набавка: од барање до прием на стока — Facturino',
      en: 'Digital Purchase Orders: From Request to Goods Receipt — Facturino',
      sq: 'Porositë dixhitale te blerjes: nga kërkesa deri te pranimi i mallit — Facturino',
      tr: 'Dijital Satın Alma Siparişleri: Talepten Mal Kabulüne — Facturino',
    },
    description: {
      mk: 'Научете како Facturino ги дигитализира нарачките за набавка: целосен животен циклус, е-пошта до добавувач, заштита од прекумерен прием и автоматско креирање фактура.',
      en: 'Learn how Facturino digitizes purchase orders: full lifecycle tracking, email to supplier, over-receipt protection and automatic bill creation.',
      sq: 'Mësoni si Facturino i dixhitalizon porositë e blerjes: cikli i plotë jetësor, email te furnizuesi, mbrojtje nga pranimi i tepërt dhe krijim automatik i faturës.',
      tr: 'Facturino\'nun satın alma siparişlerini nasıl dijitalleştirdiğini öğrenin: tam yaşam döngüsü, tedarikçiye e-posta, fazla kabul koruması ve otomatik fatura oluşturma.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Производ',
    title: 'Дигитални нарачки за набавка: од барање до прием на стока',
    publishDate: '15 март 2026',
    readTime: '8 мин читање',
    intro: 'Повеќето македонски мали и средни претпријатија ги следат набавките преку Viber групи, тетратки и Excel табели. Резултатот? Изгубени нарачки, дуплирани набавки, непроверена стока и фактури без покритие. Постои подобар начин — дигитални нарачки за набавка со целосно следење на животниот циклус, од креирање до прием на стока и плаќање.',
    sections: [
      {
        title: 'Животен циклус на нарачка',
        content: 'Секоја нарачка за набавка во Facturino поминува низ јасно дефинирани чекори. Овој структуриран процес обезбедува транспарентност, контрола и целосна ревизорска трага — од моментот на барањето до финалното плаќање.',
        items: null,
        steps: [
          { step: 'Нацрт (Draft)', desc: 'Креирајте нова нарачка за набавка со додавање на добавувач, артикли, количини и цени. Додајте нов добавувач или артикал директно од формуларот без да ја напуштате страницата. Нацртот може да се уредува и дополнува колку што сакате пред да се поднесе на одобрување.' },
          { step: 'Чека одобрување (Pending Approval)', desc: 'Нарачката е поднесена за ревизија. Одговорното лице (менаџер, сопственик или финансиска служба) ја прегледува нарачката, ги проверува артиклите и вкупниот износ и одлучува дали да ја одобри или врати на корекција.' },
          { step: 'Одобрена (Approved)', desc: 'Нарачката е одобрена и подготвена за испраќање до добавувачот. Во овој момент, системот ги заклучува клучните полиња — артиклите, количините и цените стануваат неменливи за да се обезбеди интегритет на документот.' },
          { step: 'Испратена до добавувач (Sent)', desc: 'Нарачката се испраќа по е-пошта директно до добавувачот од Facturino. Системот го следи статусот на е-поштата: дали е успешно испратена, дали има грешка или добавувачот нема регистрирана е-пошта. Така секогаш знаете дали добавувачот ја примил нарачката.' },
          { step: 'Стока примена (Goods Received)', desc: 'Кога стоката ќе пристигне, евидентирајте го приемот артикал по артикал. Facturino автоматски го ограничува примањето на нарачаната количина — не може да примите повеќе отколку што сте нарачале, со што се спречуваат грешки и вишоци во магацинот.' },
          { step: 'Фактура креирана (Bill Created)', desc: 'По приемот на стоката, со еден клик креирајте влезна фактура (bill) директно од нарачката. Сите артикли, количини и цени автоматски се пренесуваат — без рачно внесување и без ризик од грешки.' },
        ],
      },
      {
        title: 'Клучни функции',
        content: 'Facturino нуди моќни функции за управување со набавки кои ги прават нарачките ефикасни, контролирани и целосно дигитални.',
        items: [
          'Креирање добавувач директно од нарачката — Нов добавувач? Додајте го без да ја напуштате формата за нарачка. Внесете назив, ЕДБ и контакт податоци и продолжете со нарачката.',
          'Креирање артикал на лице место — Нов артикал кој не е во базата? Додајте го со еден клик директно од линијата на нарачката. Без прекин на работниот тек.',
          'Следење на статус на е-пошта — За секоја испратена нарачка, системот евидентира: дали е-поштата е успешно доставена (sent), дали има грешка при испраќање (failed) или добавувачот нема внесено е-пошта (no_email).',
          'Заштита од прекумерен прием — Системот автоматски ја ограничува примената количина на максимално нарачаната. Ако нарачавте 100 единици, не може да примите 105 — заштита од грешки и злоупотреби.',
          'Автоматско креирање фактура — Од примената нарачка, со еден клик генерирајте влезна фактура со сите податоци пренесени автоматски.',
          'Повеќевалутна поддршка — Нарачувајте од меѓународни добавувачи во EUR, USD или било која валута. Системот автоматски ги конвертира износите.',
        ],
        steps: null,
      },
      {
        title: 'Зошто се важни нарачките за набавка',
        content: 'Дигиталните нарачки за набавка не се само удобност — тие се деловна потреба за секој бизнис кој сака да работи транспарентно и ефикасно.',
        items: [
          'Правна заштита — Секоја нарачка претставува документиран договор меѓу вас и добавувачот. Во случај на спор, имате целосна трага: кој нарачал, кога, колку и по која цена.',
          'Контрола на буџет — Одобрувањето пред испраќање спречува неавторизирани набавки. Менаџментот има увид во секој денар потрошен на набавки.',
          'Точност на залихи — Евиденцијата на прием артикал по артикал обезбедува дека магацинот точно ја рефлектира реалноста. Нема повеќе "фантомски" залихи.',
          'Ревизорска трага — Секоја промена на статус, секое одобрување и секое испраќање е евидентирано со датум, време и корисник. Подготвени за инспекција во секој момент.',
        ],
        steps: null,
      },
      {
        title: 'Пример: Нарачка за канцелариски материјал',
        content: 'Да видиме како изгледа целиот процес на практика, чекор по чекор.',
        items: null,
        steps: [
          { step: 'Креирајте нарачка', desc: 'Отворете го модулот за набавки и кликнете "Нова нарачка". Изберете го добавувачот "Пишувалка ДООЕЛ" — или креирајте го директно ако не е внесен. Додајте 3 артикли: 10 кутии хартија А4 (по 350 МКД), 5 тонери за печатач (по 1.200 МКД), 20 хемиски пенкала (по 50 МКД). Вкупно: 10.500 МКД.' },
          { step: 'Поднесете на одобрување', desc: 'Зачувајте ја нарачката и кликнете "Поднеси на одобрување". Нарачката добива статус "Чека одобрување" и менаџерот добива известување.' },
          { step: 'Одобрете ја нарачката', desc: 'Менаџерот ја прегледува нарачката, ги потврдува артиклите и износот и кликнува "Одобри". Нарачката добива статус "Одобрена".' },
          { step: 'Испратете по е-пошта', desc: 'Кликнете "Испрати до добавувач" — нарачката се праќа по е-пошта до Пишувалка ДООЕЛ. Системот го евидентира статусот: "Е-пошта испратена успешно".' },
          { step: 'Примете ја стоката', desc: 'Кога стоката ќе пристигне, отворете ја нарачката и кликнете "Прими стока". Внесете ги примените количини — системот не дозволува да внесете повеќе од нарачаното (на пр., максимум 10 кутии хартија, не 12).' },
          { step: 'Креирајте фактура', desc: 'По приемот, кликнете "Креирај фактура" — сите податоци автоматски се пренесуваат. Фактурата е подготвена за книжење и плаќање. Целиот процес е завршен без ниту еден рачно внесен број.' },
        ],
      },
      {
        title: 'Започнете со дигитални набавки',
        content: 'Facturino го трансформира хаосот на набавки во структуриран, контролиран процес. Без тетратки, без изгубени Viber пораки, без дуплирани нарачки. Регистрирајте се денес и започнете да управувате со набавките дигитално — бесплатно за до 3 нарачки месечно.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи во Facturino' },
      { slug: 'cash-flow-mk', title: 'Како да го следите готовинскиот тек' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
    ],
    cta: {
      title: 'Дигитализирајте ги вашите набавки',
      desc: 'Регистрирајте се бесплатно и започнете да управувате со нарачки за набавка — од барање до прием на стока.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Product',
    title: 'Digital Purchase Orders: From Request to Goods Receipt',
    publishDate: 'March 15, 2026',
    readTime: '8 min read',
    intro: 'Most Macedonian SMEs track purchases through Viber groups, notebooks, and Excel spreadsheets. The result? Lost orders, duplicate purchases, unverified goods, and invoices without backing. There is a better way — digital purchase orders with full lifecycle tracking, from creation to goods receipt and payment.',
    sections: [
      {
        title: 'Purchase Order Lifecycle',
        content: 'Every purchase order in Facturino goes through clearly defined steps. This structured process ensures transparency, control, and a complete audit trail — from the moment of request to final payment.',
        items: null,
        steps: [
          { step: 'Draft', desc: 'Create a new purchase order by adding a supplier, items, quantities, and prices. Add a new supplier or item directly from the form without leaving the page. The draft can be edited and updated as much as you want before submitting for approval.' },
          { step: 'Pending Approval', desc: 'The order has been submitted for review. The responsible person (manager, owner, or finance department) reviews the order, checks the items and total amount, and decides whether to approve or return it for corrections.' },
          { step: 'Approved', desc: 'The order is approved and ready to be sent to the supplier. At this point, the system locks key fields — items, quantities, and prices become immutable to ensure document integrity.' },
          { step: 'Sent to Supplier', desc: 'The order is sent by email directly to the supplier from Facturino. The system tracks the email status: whether it was successfully sent, whether there was an error, or whether the supplier has no registered email. So you always know if the supplier received the order.' },
          { step: 'Goods Received', desc: 'When goods arrive, record the receipt item by item. Facturino automatically caps the received quantity at the ordered quantity — you cannot receive more than you ordered, preventing errors and inventory surplus.' },
          { step: 'Bill Created', desc: 'After receiving goods, create an incoming bill directly from the order with one click. All items, quantities, and prices are automatically transferred — no manual entry and no risk of errors.' },
        ],
      },
      {
        title: 'Key Features',
        content: 'Facturino offers powerful procurement management features that make purchase orders efficient, controlled, and fully digital.',
        items: [
          'Inline supplier creation — New supplier? Add them without leaving the purchase order form. Enter name, tax number, and contact details and continue with the order.',
          'Inline item creation — New item not in the database? Add it with one click directly from the order line. No workflow interruption.',
          'Email status tracking — For every sent order, the system records: whether the email was successfully delivered (sent), whether there was a sending error (failed), or whether the supplier has no email on file (no_email).',
          'Over-receipt protection — The system automatically caps received quantity at the maximum ordered amount. If you ordered 100 units, you cannot receive 105 — protection against errors and abuse.',
          'Automatic bill creation — From a received order, generate an incoming bill with one click with all data transferred automatically.',
          'Multi-currency support — Order from international suppliers in EUR, USD, or any currency. The system automatically converts amounts.',
        ],
        steps: null,
      },
      {
        title: 'Why Purchase Orders Matter',
        content: 'Digital purchase orders are not just a convenience — they are a business necessity for any company that wants to operate transparently and efficiently.',
        items: [
          'Legal protection — Every order serves as a documented agreement between you and the supplier. In case of a dispute, you have a complete trail: who ordered, when, how much, and at what price.',
          'Budget control — Approval before sending prevents unauthorized purchases. Management has visibility into every denar spent on procurement.',
          'Inventory accuracy — Item-by-item receipt tracking ensures that the warehouse accurately reflects reality. No more "phantom" inventory.',
          'Audit trail — Every status change, every approval, and every dispatch is recorded with date, time, and user. Ready for inspection at any moment.',
        ],
        steps: null,
      },
      {
        title: 'Example: Office Supplies Purchase Order',
        content: 'Let us see how the entire process looks in practice, step by step.',
        items: null,
        steps: [
          { step: 'Create the order', desc: 'Open the procurement module and click "New Order". Select the supplier "Pishuvalka DOOEL" — or create them directly if not yet added. Add 3 items: 10 boxes of A4 paper (350 MKD each), 5 printer toners (1,200 MKD each), 20 ballpoint pens (50 MKD each). Total: 10,500 MKD.' },
          { step: 'Submit for approval', desc: 'Save the order and click "Submit for Approval". The order gets the status "Pending Approval" and the manager receives a notification.' },
          { step: 'Approve the order', desc: 'The manager reviews the order, confirms the items and amounts, and clicks "Approve". The order gets the status "Approved".' },
          { step: 'Send by email', desc: 'Click "Send to Supplier" — the order is emailed to Pishuvalka DOOEL. The system records the status: "Email sent successfully".' },
          { step: 'Receive the goods', desc: 'When goods arrive, open the order and click "Receive Goods". Enter the received quantities — the system does not allow entering more than ordered (e.g., maximum 10 boxes of paper, not 12).' },
          { step: 'Create a bill', desc: 'After receipt, click "Create Bill" — all data is automatically transferred. The bill is ready for posting and payment. The entire process is complete without a single manually entered number.' },
        ],
      },
      {
        title: 'Start With Digital Procurement',
        content: 'Facturino transforms procurement chaos into a structured, controlled process. No notebooks, no lost Viber messages, no duplicate orders. Sign up today and start managing purchases digitally — free for up to 3 orders per month.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management in Facturino' },
      { slug: 'cash-flow-mk', title: 'How to Track Your Cash Flow' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
    ],
    cta: {
      title: 'Digitize Your Procurement',
      desc: 'Sign up free and start managing purchase orders — from request to goods receipt.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Produkt',
    title: 'Porositë dixhitale te blerjes: nga kërkesa deri te pranimi i mallit',
    publishDate: '15 mars 2026',
    readTime: '8 min lexim',
    intro: 'Shumica e NVM-ve maqedonase i ndjekin blerjet përmes grupeve Viber, fletoreve dhe tabelave Excel. Rezultati? Porosi të humbura, blerje të dyfishta, mall i paverifikuar dhe fatura pa mbulim. Ka një mënyrë më të mirë — porosi dixhitale blerje me gjurmim të plotë të ciklit jetësor, nga krijimi deri te pranimi i mallit dhe pagesa.',
    sections: [
      {
        title: 'Cikli jetësor i porosisë',
        content: 'Çdo porosi blerje në Facturino kalon nëpër hapa të përcaktuara qartë. Ky proces i strukturuar siguron transparencë, kontroll dhe gjurmë të plotë auditimi — nga momenti i kërkesës deri te pagesa finale.',
        items: null,
        steps: [
          { step: 'Draft', desc: 'Krijoni një porosi të re blerje duke shtuar furnizuesin, artikujt, sasitë dhe çmimet. Shtoni një furnizues ose artikull të ri drejtpërdrejt nga formulari pa lënë faqen. Drafti mund të redaktohet dhe përditësohet sa të dëshironi para se ta dorëzoni për miratim.' },
          { step: 'Në pritje të miratimit', desc: 'Porosia është dorëzuar për rishikim. Personi përgjegjës (menaxheri, pronari ose departamenti i financave) rishikon porosinë, kontrollon artikujt dhe shumën totale dhe vendos nëse ta miratojë ose ta kthejë për korrigjim.' },
          { step: 'Miratuar', desc: 'Porosia është miratuar dhe gati për t\'u dërguar te furnizuesi. Në këtë pikë, sistemi kyç fushat kyçe — artikujt, sasitë dhe çmimet bëhen të pandryshueshme për të siguruar integritetin e dokumentit.' },
          { step: 'Dërguar te furnizuesi', desc: 'Porosia dërgohet me email drejtpërdrejt te furnizuesi nga Facturino. Sistemi gjurmon statusin e emailit: nëse u dërgua me sukses, nëse pati gabim, ose nëse furnizuesi nuk ka email të regjistruar. Kështu gjithmonë dini nëse furnizuesi e mori porosinë.' },
          { step: 'Malli i pranuar', desc: 'Kur malli mbërrin, regjistroni pranimin artikull pas artikulli. Facturino automatikisht e kufizon sasinë e pranuar në sasinë e porositur — nuk mund të pranoni më shumë sesa keni porositur, duke parandaluar gabime dhe tepricë inventari.' },
          { step: 'Fatura e krijuar', desc: 'Pas pranimit të mallit, krijoni një faturë hyrëse drejtpërdrejt nga porosia me një klik. Të gjithë artikujt, sasitë dhe çmimet transferohen automatikisht — pa futje manuale dhe pa rrezik gabimesh.' },
        ],
      },
      {
        title: 'Veçoritë kyçe',
        content: 'Facturino ofron veçori të fuqishme të menaxhimit të blerjeve që i bëjnë porositë efikase, të kontrolluara dhe plotësisht dixhitale.',
        items: [
          'Krijim i furnizuesit inline — Furnizues i ri? Shtojeni pa lënë formularin e porosisë. Futni emrin, numrin tatimor dhe detajet e kontaktit dhe vazhdoni me porosinë.',
          'Krijim i artikullit inline — Artikull i ri që nuk është në databazë? Shtojeni me një klik drejtpërdrejt nga linja e porosisë. Pa ndërprerje të rrjedhës së punës.',
          'Gjurmim i statusit të emailit — Për çdo porosi të dërguar, sistemi regjistron: nëse emaili u dorëzua me sukses (sent), nëse pati gabim dërgimi (failed), ose nëse furnizuesi nuk ka email në dosje (no_email).',
          'Mbrojtje nga pranimi i tepërt — Sistemi automatikisht e kufizon sasinë e pranuar në maksimumin e porositur. Nëse porositët 100 njësi, nuk mund të pranoni 105 — mbrojtje nga gabimet dhe abuzimet.',
          'Krijim automatik i faturës — Nga një porosi e pranuar, gjeneroni një faturë hyrëse me një klik me të gjitha të dhënat e transferuara automatikisht.',
          'Mbështetje shumëvalutore — Porositni nga furnizues ndërkombëtarë në EUR, USD ose çdo valutë. Sistemi automatikisht i konverton shumat.',
        ],
        steps: null,
      },
      {
        title: 'Pse janë të rëndësishme porositë e blerjes',
        content: 'Porositë dixhitale të blerjes nuk janë thjesht komoditet — ato janë nevojë biznesi për çdo kompani që dëshiron të punojë me transparencë dhe efikasitet.',
        items: [
          'Mbrojtje ligjore — Çdo porosi shërben si marrëveshje e dokumentuar midis jush dhe furnizuesit. Në rast mosmarrëveshjeje, keni gjurmë të plotë: kush porositi, kur, sa dhe me çfarë çmimi.',
          'Kontroll buxheti — Miratimi para dërgimit parandalon blerje të paautorizuara. Menaxhmenti ka vizibilitet mbi çdo denar të shpenzuar në blerje.',
          'Saktësi inventari — Gjurmimi i pranimit artikull pas artikulli siguron që magazina reflekton saktësisht realitetin. Jo më inventar "fantazmë".',
          'Gjurmë auditimi — Çdo ndryshim statusi, çdo miratim dhe çdo dërgesë regjistrohet me datë, kohë dhe përdorues. Gati për inspektim në çdo moment.',
        ],
        steps: null,
      },
      {
        title: 'Shembull: Porosi për materiale zyre',
        content: 'Le të shohim si duket i gjithë procesi në praktikë, hap pas hapi.',
        items: null,
        steps: [
          { step: 'Krijoni porosinë', desc: 'Hapni modulin e blerjeve dhe klikoni "Porosi e Re". Zgjidhni furnizuesin "Pishuvalka DOOEL" — ose krijojeni drejtpërdrejt nëse nuk është shtuar ende. Shtoni 3 artikuj: 10 kuti letër A4 (350 MKD secila), 5 tonera printeri (1.200 MKD secili), 20 stilolapsa (50 MKD secili). Totali: 10.500 MKD.' },
          { step: 'Dorëzoni për miratim', desc: 'Ruani porosinë dhe klikoni "Dorëzo për Miratim". Porosia merr statusin "Në pritje të miratimit" dhe menaxheri merr njoftim.' },
          { step: 'Miratoni porosinë', desc: 'Menaxheri rishikon porosinë, konfirmon artikujt dhe shumat dhe klikon "Mirato". Porosia merr statusin "Miratuar".' },
          { step: 'Dërgoni me email', desc: 'Klikoni "Dërgo te Furnizuesi" — porosia dërgohet me email te Pishuvalka DOOEL. Sistemi regjistron statusin: "Emaili u dërgua me sukses".' },
          { step: 'Pranoni mallin', desc: 'Kur malli mbërrin, hapni porosinë dhe klikoni "Prano Mallin". Futni sasitë e pranuara — sistemi nuk lejon futjen e më shumë sesa është porositur (p.sh., maksimum 10 kuti letër, jo 12).' },
          { step: 'Krijoni faturën', desc: 'Pas pranimit, klikoni "Krijo Faturë" — të gjitha të dhënat transferohen automatikisht. Fatura është gati për regjistrim dhe pagesë. I gjithë procesi përfundon pa asnjë numër të futur manualisht.' },
        ],
      },
      {
        title: 'Filloni me blerje dixhitale',
        content: 'Facturino e transformon kaosin e blerjeve në një proces të strukturuar dhe të kontrolluar. Pa fletore, pa mesazhe Viber të humbura, pa porosi të dyfishta. Regjistrohuni sot dhe filloni të menaxhoni blerjet dixhitalisht — falas për deri 3 porosi në muaj.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve në Facturino' },
      { slug: 'cash-flow-mk', title: 'Si ta ndiqni rrjedhën e parave' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
    ],
    cta: {
      title: 'Dixhitalizoni blerjet tuaja',
      desc: 'Regjistrohuni falas dhe filloni të menaxhoni porositë e blerjes — nga kërkesa deri te pranimi i mallit.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Ürün',
    title: 'Dijital Satın Alma Siparişleri: Talepten Mal Kabulüne',
    publishDate: '15 Mart 2026',
    readTime: '8 dk okuma',
    intro: 'Makedon KOBİ\'lerinin çoğu satın almaları Viber grupları, defterler ve Excel tabloları aracılığıyla takip eder. Sonuç? Kayıp siparişler, mükerrer satın almalar, doğrulanmamış mallar ve karşılığı olmayan faturalar. Daha iyi bir yol var — oluşturmadan mal kabulüne ve ödemeye kadar tam yaşam döngüsü takibi ile dijital satın alma siparişleri.',
    sections: [
      {
        title: 'Satın Alma Siparişi Yaşam Döngüsü',
        content: 'Facturino\'daki her satın alma siparişi net olarak tanımlanmış adımlardan geçer. Bu yapılandırılmış süreç, talep anından nihai ödemeye kadar şeffaflık, kontrol ve eksiksiz bir denetim izi sağlar.',
        items: null,
        steps: [
          { step: 'Taslak (Draft)', desc: 'Tedarikçi, kalemler, miktarlar ve fiyatlar ekleyerek yeni bir satın alma siparişi oluşturun. Sayfadan ayrılmadan doğrudan formdan yeni tedarikçi veya kalem ekleyin. Taslak, onaya göndermeden önce istediğiniz kadar düzenlenebilir ve güncellenebilir.' },
          { step: 'Onay Bekliyor', desc: 'Sipariş incelemeye gönderildi. Sorumlu kişi (yönetici, işletme sahibi veya finans departmanı) siparişi inceler, kalemleri ve toplam tutarı kontrol eder ve onaylayıp onaylamamaya veya düzeltmeye iade etmeye karar verir.' },
          { step: 'Onaylandı', desc: 'Sipariş onaylandı ve tedarikçiye gönderilmeye hazır. Bu noktada sistem kilit alanları kilitler — belge bütünlüğünü sağlamak için kalemler, miktarlar ve fiyatlar değiştirilemez hale gelir.' },
          { step: 'Tedarikçiye Gönderildi', desc: 'Sipariş, Facturino\'dan doğrudan tedarikçiye e-posta ile gönderilir. Sistem e-posta durumunu takip eder: başarıyla gönderilip gönderilmediği, hata olup olmadığı veya tedarikçinin kayıtlı e-postası olup olmadığı. Böylece tedarikçinin siparişi alıp almadığını her zaman bilirsiniz.' },
          { step: 'Mal Kabul Edildi', desc: 'Mallar geldiğinde, kabulü kalem kalem kaydedin. Facturino alınan miktarı otomatik olarak sipariş edilen miktarla sınırlar — sipariş ettiğinizden fazlasını alamazsınız, bu da hataları ve envanter fazlalığını önler.' },
          { step: 'Fatura Oluşturuldu', desc: 'Mal kabulünden sonra, tek tıklamayla doğrudan siparişten gelen fatura oluşturun. Tüm kalemler, miktarlar ve fiyatlar otomatik olarak aktarılır — manuel giriş yok ve hata riski yok.' },
        ],
      },
      {
        title: 'Temel Özellikler',
        content: 'Facturino, satın alma siparişlerini verimli, kontrollü ve tamamen dijital hale getiren güçlü tedarik yönetimi özellikleri sunar.',
        items: [
          'Satır içi tedarikçi oluşturma — Yeni tedarikçi? Satın alma sipariş formundan ayrılmadan ekleyin. Ad, vergi numarası ve iletişim bilgilerini girin ve siparişe devam edin.',
          'Satır içi kalem oluşturma — Veritabanında olmayan yeni kalem? Sipariş satırından tek tıklamayla ekleyin. İş akışı kesintisi yok.',
          'E-posta durumu takibi — Gönderilen her sipariş için sistem kaydeder: e-postanın başarıyla iletilip iletilmediği (sent), gönderim hatası olup olmadığı (failed) veya tedarikçinin dosyada e-postası olup olmadığı (no_email).',
          'Fazla kabul koruması — Sistem alınan miktarı otomatik olarak sipariş edilen maksimum miktarla sınırlar. 100 birim sipariş ettiyseniz, 105 alamazsınız — hatalara ve kötüye kullanıma karşı koruma.',
          'Otomatik fatura oluşturma — Alınan siparişten, tüm veriler otomatik olarak aktarılarak tek tıklamayla gelen fatura oluşturun.',
          'Çoklu para birimi desteği — EUR, USD veya herhangi bir para biriminde uluslararası tedarikçilerden sipariş verin. Sistem tutarları otomatik olarak dönüştürür.',
        ],
        steps: null,
      },
      {
        title: 'Satın Alma Siparişleri Neden Önemli',
        content: 'Dijital satın alma siparişleri sadece bir kolaylık değildir — şeffaf ve verimli çalışmak isteyen her işletme için bir iş gereksinimidir.',
        items: [
          'Yasal koruma — Her sipariş, siz ve tedarikçi arasında belgelenmiş bir anlaşma olarak hizmet eder. Anlaşmazlık durumunda, eksiksiz bir iz var: kim sipariş verdi, ne zaman, ne kadar ve hangi fiyattan.',
          'Bütçe kontrolü — Göndermeden önce onay, yetkisiz satın almaları önler. Yönetim, tedarike harcanan her kuruşu görebilir.',
          'Envanter doğruluğu — Kalem kalem kabul takibi, deponun gerçekliği doğru şekilde yansıtmasını sağlar. Artık "hayalet" envanter yok.',
          'Denetim izi — Her durum değişikliği, her onay ve her gönderim tarih, saat ve kullanıcı ile kaydedilir. Her an denetime hazır.',
        ],
        steps: null,
      },
      {
        title: 'Örnek: Ofis Malzemeleri Satın Alma Siparişi',
        content: 'Tüm sürecin pratikte nasıl göründüğünü adım adım görelim.',
        items: null,
        steps: [
          { step: 'Siparişi oluşturun', desc: 'Tedarik modülünü açın ve "Yeni Sipariş"e tıklayın. "Pishuvalka DOOEL" tedarikçisini seçin — veya henüz eklenmemişse doğrudan oluşturun. 3 kalem ekleyin: 10 kutu A4 kağıt (her biri 350 MKD), 5 yazıcı toneri (her biri 1.200 MKD), 20 tükenmez kalem (her biri 50 MKD). Toplam: 10.500 MKD.' },
          { step: 'Onaya gönderin', desc: 'Siparişi kaydedin ve "Onaya Gönder"e tıklayın. Sipariş "Onay Bekliyor" durumunu alır ve yönetici bildirim alır.' },
          { step: 'Siparişi onaylayın', desc: 'Yönetici siparişi inceler, kalemleri ve tutarları onaylar ve "Onayla"ya tıklar. Sipariş "Onaylandı" durumunu alır.' },
          { step: 'E-posta ile gönderin', desc: '"Tedarikçiye Gönder"e tıklayın — sipariş Pishuvalka DOOEL\'e e-posta ile gönderilir. Sistem durumu kaydeder: "E-posta başarıyla gönderildi".' },
          { step: 'Malları teslim alın', desc: 'Mallar geldiğinde, siparişi açın ve "Mal Kabul"e tıklayın. Teslim alınan miktarları girin — sistem sipariş edilenden fazlasının girilmesine izin vermez (ör. maksimum 10 kutu kağıt, 12 değil).' },
          { step: 'Fatura oluşturun', desc: 'Kabulden sonra "Fatura Oluştur"a tıklayın — tüm veriler otomatik olarak aktarılır. Fatura, kayıt ve ödeme için hazırdır. Tüm süreç, tek bir manuel girilen rakam olmadan tamamlanır.' },
        ],
      },
      {
        title: 'Dijital Tedarik ile Başlayın',
        content: 'Facturino, tedarik kaosunu yapılandırılmış, kontrollü bir sürece dönüştürür. Defter yok, kayıp Viber mesajları yok, mükerrer siparişler yok. Bugün kaydolun ve satın almaları dijital olarak yönetmeye başlayın — ayda 3 siparişe kadar ücretsiz.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Facturino\'da gider yönetimi' },
      { slug: 'cash-flow-mk', title: 'Nakit akışınızı nasıl takip edersiniz' },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
    ],
    cta: {
      title: 'Tedarik Sürecinizi Dijitalleştirin',
      desc: 'Ücretsiz kaydolun ve satın alma siparişlerini yönetmeye başlayın — talepten mal kabulüne.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function NabavkiINarachkiPage({
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
          <img src="/assets/images/blog/blog_purchase_orders.png" alt="Дигитални нарачки за набавка Facturino - управување со набавки во Македонија" className="w-full h-auto" />
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
