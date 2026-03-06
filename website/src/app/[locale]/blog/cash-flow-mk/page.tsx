import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/cash-flow-mk', {
    title: {
      mk: 'Cash Flow: Зошто е позначаен од профитот — Facturino',
      en: 'Cash Flow: Why It Matters More Than Profit — Facturino',
      sq: 'Rrjedha e parasë: Pse ka më shumë rëndësi se fitimi — Facturino',
      tr: 'Nakit akışı: Neden kârdan daha önemlidir — Facturino',
    },
    description: {
      mk: 'Дознајте зошто готовинскиот тек е позначаен од профитот, како профитабилни бизниси пропаѓаат од лош cash flow, и како да го предвидите и подобрите.',
      en: 'Learn why cash flow matters more than profit, how profitable businesses fail from poor cash flow, and how to forecast and improve it.',
      sq: 'Mësoni pse rrjedha e parasë ka më shumë rëndësi se fitimi, si dështojnë bizneset fitimprurëse nga rrjedha e dobët dhe si ta parashikoni e përmirësoni.',
      tr: 'Nakit akışının kârdan neden daha önemli olduğunu, kârlı işletmelerin neden kötü nakit akışından başarısız olduğunu ve nasıl tahmin edip iyileştireceğinizi öğrenin.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Едукација',
    title: 'Cash Flow: Зошто е позначаен од профитот',
    publishDate: '14 февруари 2026',
    readTime: '7 мин читање',
    intro:
      'Многу сопственици на мали бизниси го мешаат профитот со готовинскиот тек (cash flow). Бизнис може да биде профитабилен на хартија, но да нема доволно готовина за да ги плати сметките. Во Македонија, каде доцните плаќања се норма, разбирањето на готовинскиот тек е клучно за преживување.',
    sections: [
      {
        title: 'Профит vs готовински тек: Која е разликата?',
        content:
          'Профитот е разликата меѓу приходите и расходите за одреден период — тоа е сметководствена мерка. Готовинскиот тек е реалното движење на парите — колку пари влегуваат и излегуваат од вашата сметка. Можете да имате фактурирано 100.000 денари профит, но ако клиентите не ви платиле, вашата сметка е празна. Профитот ви кажува дали бизнисот е успешен, но готовинскиот тек ви кажува дали може да преживее.',
        items: null,
        steps: null,
      },
      {
        title: 'Зошто профитабилни бизниси пропаѓаат',
        content:
          'Статистиките покажуваат дека 82% од бизнисите кои пропаѓаат имаат проблеми со готовинскиот тек. Ова е особено честа појава кај растечки бизниси — добивате поголеми нарачки, вработувате нови луѓе, купувате опрема, но плаќањата од клиентите доаѓаат со доцнење од 60-90 дена. Во меѓувреме, вашите обврски (плати, кирија, добавувачи, даноци) не чекаат.',
        items: [
          'Голем проект заврши, но клиентот плаќа за 90 дена — вие не можете да ги платите платите',
          'Сезонски бизнис има одличен профит на годишно ниво, но е без готовина 4 месеци од годината',
          'Компанија со висока маржа, но со еден голем клиент кој редовно доцни со плаќање',
          'Раст без планирање — повеќе вработени и трошоци, истите услови на плаќање',
        ],
        steps: null,
      },
      {
        title: 'Три типа на готовински тек',
        content:
          'За целосна слика на финансиското здравје на бизнисот, треба да ги разберете сите три типа на готовински тек.',
        items: null,
        steps: [
          {
            step: 'Оперативен готовински тек',
            desc: 'Ова е парите генерирани од основната дејност — продажба на производи или услуги, минус оперативните трошоци. Ова е најважниот показател. Ако оперативниот cash flow е негативен, бизнисот троши повеќе отколку што заработува од основната дејност.',
          },
          {
            step: 'Инвестициски готовински тек',
            desc: 'Паричните текови поврзани со купување или продавање на имот, опрема или инвестиции. Негативниот инвестициски cash flow не е лош — тоа значи дека инвестирате во раст. Но мора да биде покриен од оперативниот cash flow или финансирање.',
          },
          {
            step: 'Финансиски готовински тек',
            desc: 'Паричните текови од кредити, инвестиции на основачите или дивиденди. Вклучува примања од банкарски кредити, враќање на кредити и исплата на дивиденди. Здравиот бизнис не треба постојано да зависи од надворешно финансирање.',
          },
        ],
      },
      {
        title: 'Како да го подобрите готовинскиот тек',
        content: null,
        items: [
          'Скратете ги роковите за плаќање — наместо 60 дена, барајте 15 или 30 дена. Понудете попуст од 2% за рано плаќање.',
          'Фактурирајте веднаш — не чекајте крај на месецот. Испратете ја фактурата истиот ден кога ќе ја завршите работата.',
          'Следете ги достасаните фактури — автоматски потсетувања на 7, 14 и 30 дена после рокот.',
          'Барајте авансно плаќање — за поголеми проекти, барајте 30-50% аванс пред почеток на работата.',
          'Преговарајте подолги рокови со добавувачите — ако клиентите ви плаќаат за 30 дена, договорете 45 дена со добавувачите.',
          'Направете готовинска резерва — одвојте 10-15% од секој приход во резервен фонд за покривање на периоди со слаб cash flow.',
          'Планирајте однапред — направете месечна проекција на готовинскиот тек за следните 3-6 месеци.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го следи готовинскиот тек',
        content:
          'Facturino ви дава преглед на готовинскиот тек во реално време. Со увоз на банкарски изводи (CSV/MT940/PDF), трансакциите брзо се увезуваат и категоризираат. Можете да видите кои фактури се неплатени, колку пари очекувате да примите оваа недела и дали ќе имате доволно готовина за покривање на обврските. Системот автоматски испраќа потсетувања за доспеани фактури и ви помага да го оптимизирате циклусот на плаќање.',
        items: [
          'Dashboard со преглед на готовински тек во реално време',
          'Автоматски потсетувања за неплатени фактури',
          'Проекција на готовински тек за следните 30/60/90 дена',
          'Увоз на банкарски изводи (CSV/MT940/PDF)',
          'Извештаи за стареење на побарувањата (aging report)',
          'Преглед на доспеани обврски кон добавувачи',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи: 7 совети за мали бизниси' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Сметководство за почетници: Основи што секој бизнис ги знае' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Зошто табели не се доволни' },
    ],
    cta: {
      title: 'Никогаш повеќе без готовина',
      desc: 'Со Facturino секогаш знаете колку пари имате, колку очекувате и кога доспеваат вашите обврски.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Education',
    title: 'Cash Flow: Why It Matters More Than Profit',
    publishDate: 'February 14, 2026',
    readTime: '7 min read',
    intro:
      'Many small business owners confuse profit with cash flow. A business can be profitable on paper but lack the cash to pay its bills. In Macedonia, where late payments are the norm, understanding cash flow is critical for survival.',
    sections: [
      {
        title: 'Profit vs cash flow: What is the difference?',
        content:
          'Profit is the difference between revenue and expenses for a given period — it is an accounting measure. Cash flow is the actual movement of money — how much cash enters and leaves your account. You may have invoiced 100,000 denars in profit, but if your clients have not paid, your account is empty. Profit tells you whether the business is successful, but cash flow tells you whether it can survive.',
        items: null,
        steps: null,
      },
      {
        title: 'Why profitable businesses fail',
        content:
          'Statistics show that 82% of businesses that fail have cash flow problems. This is especially common with growing businesses — you receive larger orders, hire new people, buy equipment, but payments from clients come with a 60-90 day delay. Meanwhile, your obligations (salaries, rent, suppliers, taxes) do not wait.',
        items: [
          'A large project is completed, but the client pays in 90 days — you cannot cover payroll',
          'A seasonal business has excellent annual profit, but is cashless for 4 months of the year',
          'A company with high margins, but one large client who regularly pays late',
          'Growth without planning — more employees and costs, same payment terms',
        ],
        steps: null,
      },
      {
        title: 'Three types of cash flow',
        content:
          'For a complete picture of your business financial health, you need to understand all three types of cash flow.',
        items: null,
        steps: [
          {
            step: 'Operating cash flow',
            desc: 'This is the cash generated from core business activities — selling products or services, minus operating costs. This is the most important indicator. If operating cash flow is negative, the business spends more than it earns from its core activity.',
          },
          {
            step: 'Investing cash flow',
            desc: 'Cash flows related to buying or selling property, equipment, or investments. Negative investing cash flow is not necessarily bad — it means you are investing in growth. But it must be covered by operating cash flow or financing.',
          },
          {
            step: 'Financing cash flow',
            desc: 'Cash flows from loans, founder investments, or dividends. Includes proceeds from bank loans, loan repayments, and dividend payments. A healthy business should not constantly depend on external financing.',
          },
        ],
      },
      {
        title: 'How to improve your cash flow',
        content: null,
        items: [
          'Shorten payment terms — instead of 60 days, ask for 15 or 30 days. Offer a 2% discount for early payment.',
          'Invoice immediately — do not wait until the end of the month. Send the invoice the same day you complete the work.',
          'Track overdue invoices — set automatic reminders at 7, 14, and 30 days past due.',
          'Request advance payments — for larger projects, request 30-50% upfront before starting work.',
          'Negotiate longer terms with suppliers — if clients pay you in 30 days, arrange 45 days with suppliers.',
          'Build a cash reserve — set aside 10-15% of each payment into a reserve fund for low cash flow periods.',
          'Plan ahead — create a monthly cash flow projection for the next 3-6 months.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino tracks cash flow',
        content:
          'Facturino gives you a real-time cash flow overview. With bank statement import (CSV/MT940/PDF), transactions are quickly reconciled and categorized. You can see which invoices are unpaid, how much money you expect to receive this week, and whether you will have enough cash to cover obligations. The system automatically sends reminders for overdue invoices and helps you optimize the payment cycle.',
        items: [
          'Dashboard with real-time cash flow overview',
          'Automatic reminders for unpaid invoices',
          'Cash flow projection for the next 30/60/90 days',
          'Bank statement import (CSV/MT940/PDF)',
          'Accounts receivable aging reports',
          'Overview of payables due to suppliers',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management: 7 Tips for Small Businesses' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Accounting for Beginners: Basics Every Business Should Know' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Why Spreadsheets Are Not Enough' },
    ],
    cta: {
      title: 'Never run out of cash again',
      desc: 'With Facturino you always know how much money you have, how much you expect, and when your obligations are due.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Edukim',
    title: 'Rrjedha e parasë: Pse ka më shumë rëndësi se fitimi',
    publishDate: '14 shkurt 2026',
    readTime: '7 min lexim',
    intro:
      'Shumë pronarë biznesesh të vogla e ngatërrojnë fitimin me rrjedhën e parasë (cash flow). Një biznes mund të jetë fitimprurës në letër, por të mos ketë para të mjaftueshme për të paguar faturat. Në Maqedoni, ku pagesat e vonuara janë normë, kuptimi i rrjedhës së parasë është vendimtar për mbijetesë.',
    sections: [
      {
        title: 'Fitimi vs rrjedha e parasë: Cila është ndryshimi?',
        content:
          'Fitimi është diferenca midis të ardhurave dhe shpenzimeve për një periudhë — është masë kontabël. Rrjedha e parasë është lëvizja reale e parave — sa para hyjnë dhe dalin nga llogaria juaj. Mund të keni faturuar 100.000 denarë fitim, por nëse klientët nuk ju kanë paguar, llogaria juaj është bosh. Fitimi ju tregon nëse biznesi është i suksesshëm, por rrjedha e parasë ju tregon nëse ai mund të mbijetojë.',
        items: null,
        steps: null,
      },
      {
        title: 'Pse bizneset fitimprurëse dështojnë',
        content:
          'Statistikat tregojnë se 82% e bizneseve që dështojnë kanë probleme me rrjedhën e parasë. Kjo është veçanërisht e zakonshme me bizneset në rritje — merrni porosi më të mëdha, punësoni njerëz të rinj, blini pajisje, por pagesat nga klientët vijnë me vonesë 60-90 ditë. Ndërkohë, detyrimet tuaja (paga, qira, furnitorë, tatime) nuk presin.',
        items: [
          'Një projekt i madh përfundon, por klienti paguan pas 90 ditësh — ju nuk mund të mbuloni pagat',
          'Biznesi sezonal ka fitim të shkëlqyer vjetor, por mbetet pa para 4 muaj në vit',
          'Kompani me marzhe të larta, por me një klient të madh që rregullisht vonon pagesat',
          'Rritje pa planifikim — më shumë punonjës dhe kosto, të njëjtat kushte pagese',
        ],
        steps: null,
      },
      {
        title: 'Tre llojet e rrjedhës së parasë',
        content:
          'Për pamjen e plotë të shëndetit financiar të biznesit, duhet të kuptoni të tre llojet e rrjedhës së parasë.',
        items: null,
        steps: [
          {
            step: 'Rrjedha operative e parasë',
            desc: 'Kjo janë paratë e gjeneruara nga aktiviteti kryesor — shitja e produkteve ose shërbimeve, minus kostot operative. Ky është treguesi më i rëndësishëm. Nëse rrjedha operative është negative, biznesi shpenzon më shumë se sa fiton nga aktiviteti kryesor.',
          },
          {
            step: 'Rrjedha investuese e parasë',
            desc: 'Rrjedhat e parasë lidhur me blerjen ose shitjen e pronës, pajisjeve ose investimeve. Rrjedha investuese negative nuk është domosdoshmërisht e keqe — do të thotë se po investoni në rritje. Por duhet të mbulohet nga rrjedha operative ose financimi.',
          },
          {
            step: 'Rrjedha financuese e parasë',
            desc: 'Rrjedhat e parasë nga kreditë, investimet e themeluesve ose dividendët. Përfshin pranime nga kreditë bankare, kthime kreditesh dhe pagesa dividendësh. Biznesi i shëndetshëm nuk duhet të varet vazhdimisht nga financimi i jashtëm.',
          },
        ],
      },
      {
        title: 'Si ta përmirësoni rrjedhën e parasë',
        content: null,
        items: [
          'Shkurtoni afatet e pagesës — në vend të 60 ditëve, kërkoni 15 ose 30 ditë. Ofroni zbritje 2% për pagesë të hershme.',
          'Faturoni menjëherë — mos prisni fundin e muajit. Dërgoni faturën ditën që përfundoni punën.',
          'Gjurmoni faturat e vonuara — vendosni kujtues automatik në 7, 14 dhe 30 ditë pas afatit.',
          'Kërkoni pagesë paraprake — për projekte më të mëdha, kërkoni 30-50% paraprakisht para fillimit.',
          'Negocioni afate më të gjata me furnitorët — nëse klientët ju paguajnë për 30 ditë, merrni vesh 45 ditë me furnitorët.',
          'Ndërtoni rezervë parash — ndani 10-15% të çdo pagese në fond rezervë për periudhat me rrjedhë të ulët.',
          'Planifikoni përpara — krijoni projeksion mujor të rrjedhës së parasë për 3-6 muajt e ardhshëm.',
        ],
        steps: null,
      },
      {
        title: 'Si e gjurmon Facturino rrjedhën e parasë',
        content:
          'Facturino ju jep pamje të rrjedhës së parasë në kohë reale. Me importin e ekstrakteve bankare (CSV/MT940/PDF), transaksionet pajtohen dhe kategorizohen shpejt. Mund të shihni cilat fatura janë të papaguara, sa para prisni të merrni këtë javë dhe nëse do të keni para të mjaftueshme për detyrimet. Sistemi dërgon automatikisht kujtues për faturat e vonuara dhe ju ndihmon të optimizoni ciklin e pagesës.',
        items: [
          'Dashboard me pamje të rrjedhës së parasë në kohë reale',
          'Kujtues automatik për faturat e papaguara',
          'Projeksion i rrjedhës së parasë për 30/60/90 ditët e ardhshme',
          'Import i ekstrakteve bankare (CSV/MT940/PDF)',
          'Raporte të plakjes së arkëtimeve (aging report)',
          'Pamje e detyrimeve ndaj furnitorëve',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve: 7 këshilla për bizneset e vogla' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Kontabiliteti për fillestarë: Bazat që çdo biznes i njeh' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë' },
    ],
    cta: {
      title: 'Mos mbetni kurrë pa para',
      desc: 'Me Facturino gjithmonë dini sa para keni, sa prisni dhe kur skadojnë detyrimet tuaja.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Eğitim',
    title: 'Nakit akışı: Neden kârdan daha önemlidir',
    publishDate: '14 Şubat 2026',
    readTime: '7 dk okuma',
    intro:
      'Birçok küçük işletme sahibi kârı nakit akışıyla karıştırır. Bir işletme kâğıt üzerinde kârlı olabilir ancak faturalarını ödeyecek nakdi olmayabilir. Geç ödemelerin norm olduğu Makedonya\'da, nakit akışını anlamak hayatta kalmak için kritiktir.',
    sections: [
      {
        title: 'Kâr ile nakit akışı arasındaki fark nedir?',
        content:
          'Kâr, belirli bir dönemdeki gelir ve gider arasındaki farktır — muhasebe ölçüsüdür. Nakit akışı ise paranın gerçek hareketidir — hesabınıza ne kadar para girer ve çıkar. 100.000 dinar kâr faturalamış olabilirsiniz, ancak müşterileriniz ödemediyse hesabınız boştur. Kâr, işletmenin başarılı olup olmadığını söyler, ancak nakit akışı hayatta kalıp kalamayacağını söyler.',
        items: null,
        steps: null,
      },
      {
        title: 'Kârlı işletmeler neden başarısız olur',
        content:
          'İstatistikler, başarısız olan işletmelerin %82\'sinin nakit akışı sorunları yaşadığını göstermektedir. Bu özellikle büyüyen işletmelerde yaygındır — daha büyük siparişler alırsınız, yeni insanlar işe alırsınız, ekipman satın alırsınız, ancak müşterilerden ödemeler 60-90 gün gecikmeyle gelir. Bu arada yükümlülükleriniz (maaşlar, kira, tedarikçiler, vergiler) beklemez.',
        items: [
          'Büyük bir proje tamamlanır, ancak müşteri 90 günde öder — siz maaşları karşılayamazsınız',
          'Mevsimsel işletme yıllık mükemmel kâra sahiptir, ancak yılın 4 ayı nakitsiz kalır',
          'Yüksek marjlı ancak düzenli olarak geç ödeyen büyük bir müşterisi olan şirket',
          'Plansız büyüme — daha fazla çalışan ve maliyet, aynı ödeme koşulları',
        ],
        steps: null,
      },
      {
        title: 'Üç nakit akışı türü',
        content:
          'İşletmenizin mali sağlığının tam bir resmini görmek için üç nakit akışı türünü de anlamanız gerekir.',
        items: null,
        steps: [
          {
            step: 'İşletme nakit akışı',
            desc: 'Bu, temel iş faaliyetlerinden üretilen nakittir — ürün veya hizmet satışı, eksi işletme maliyetleri. Bu en önemli göstergedir. İşletme nakit akışı negatifse, işletme temel faaliyetinden kazandığından daha fazla harcıyor demektir.',
          },
          {
            step: 'Yatırım nakit akışı',
            desc: 'Mülk, ekipman veya yatırım alım satımıyla ilgili nakit akışlarıdır. Negatif yatırım nakit akışı mutlaka kötü değildir — büyümeye yatırım yaptığınız anlamına gelir. Ancak işletme nakit akışı veya finansmanla karşılanmalıdır.',
          },
          {
            step: 'Finansman nakit akışı',
            desc: 'Krediler, kurucu yatırımları veya temettülerden kaynaklanan nakit akışlarıdır. Banka kredilerinden elde edilen gelirleri, kredi geri ödemelerini ve temettü ödemelerini içerir. Sağlıklı bir işletme sürekli dış finansmana bağımlı olmamalıdır.',
          },
        ],
      },
      {
        title: 'Nakit akışınızı nasıl iyileştirebilirsiniz',
        content: null,
        items: [
          'Ödeme vadelerini kısaltın — 60 gün yerine 15 veya 30 gün isteyin. Erken ödeme için %2 indirim teklif edin.',
          'Hemen fatura kesin — ay sonunu beklemeyin. İşi tamamladığınız gün faturayı gönderin.',
          'Vadesi geçmiş faturaları takip edin — vadeden 7, 14 ve 30 gün sonra otomatik hatırlatmalar ayarlayın.',
          'Peşin ödeme talep edin — büyük projeler için işe başlamadan önce %30-50 avans isteyin.',
          'Tedarikçilerle daha uzun vadeler müzakere edin — müşteriler 30 günde ödüyorsa, tedarikçilerle 45 gün ayarlayın.',
          'Nakit rezerv oluşturun — düşük nakit akışı dönemleri için her ödemeden %10-15 ayırın.',
          'İleriye planlayın — önümüzdeki 3-6 ay için aylık nakit akışı projeksiyonu oluşturun.',
        ],
        steps: null,
      },
      {
        title: 'Facturino nakit akışını nasıl takip eder',
        content:
          'Facturino size gerçek zamanlı nakit akışı görünümü sunar. Banka ekstresi içe aktarma (CSV/MT940/PDF) ile işlemler hızla eşleştirilir ve sınıflandırılır. Hangi faturaların ödenmemiş olduğunu, bu hafta ne kadar para beklediğinizi ve yükümlülüklerinizi karşılayacak yeterli nakit olup olmadığını görebilirsiniz. Sistem vadesi geçmiş faturalar için otomatik hatırlatmalar gönderir ve ödeme döngüsünü optimize etmenize yardımcı olur.',
        items: [
          'Gerçek zamanlı nakit akışı görünümlü gösterge paneli',
          'Ödenmemiş faturalar için otomatik hatırlatmalar',
          'Önümüzdeki 30/60/90 gün için nakit akışı projeksiyonu',
          'Banka ekstresi içe aktarma (CSV/MT940/PDF)',
          'Alacak yaşlandırma raporları (aging report)',
          'Tedarikçilere olan borçların görünümü',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'upravljanje-so-rashodi', title: 'Gider yönetimi: Küçük işletmeler için 7 ipucu' },
      { slug: 'smetkovodstvo-za-pocetnici', title: 'Yeni başlayanlar için muhasebe: Her işletmenin bilmesi gerekenler' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Neden tablolar yetmez' },
    ],
    cta: {
      title: 'Bir daha asla nakitsiz kalmayın',
      desc: 'Facturino ile ne kadar paranız olduğunu, ne kadar beklediğinizi ve yükümlülüklerinizin ne zaman vadesi dolduğunu her zaman bilin.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function CashFlowMkPage({
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
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
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
          <a href="https://app.facturino.mk/signup" className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all">
            {t.cta.button}
            <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
          </a>
        </div>
      </section>
    </main>
  )
}
