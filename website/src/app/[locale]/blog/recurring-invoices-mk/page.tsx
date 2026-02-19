import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/recurring-invoices-mk', {
    title: {
      mk: 'Повторувачки фактури: Автоматизирајте ја наплатата | Facturino',
      en: 'Recurring Invoices: Automate Your Billing | Facturino',
      sq: 'Faturat e përsëritura: Automatizoni faturimin | Facturino',
      tr: 'Tekrarlanan faturalar: Faturalandırmayı otomatikleştirin | Facturino',
    },
    description: {
      mk: 'Научете како повторувачките фактури го автоматизираат наплаќањето за SaaS, закупнини и долгорочни договори. Водич за поставување со Facturino.',
      en: 'Learn how recurring invoices automate billing for SaaS, rentals and retainer contracts. Setup guide with Facturino.',
      sq: 'Mësoni si faturat e përsëritura automatizojnë faturimin për SaaS, qiratë dhe kontratat. Udhëzues konfigurimi me Facturino.',
      tr: 'Tekrarlanan faturaların SaaS, kira ve hizmet sözleşmeleri için faturalamayı nasıl otomatikleştirdiğini öğrenin. Facturino ile kurulum rehberi.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Совети',
    title: 'Повторувачки фактури: Автоматизирајте ја наплатата',
    publishDate: '9 февруари 2026',
    readTime: '6 мин читање',
    intro: 'Секој бизнис кој работи со претплати, месечни закупнини или долгорочни договори знае колку време одзема рачното креирање фактури секој месец. Повторувачките фактури го елиминираат тој проблем — автоматски се генерираат, испраќаат и следат. Во овој водич ќе објасниме што се повторувачки фактури, зошто се важни и како да ги поставите во Facturino за целосна автоматизација на наплатата.',
    sections: [
      {
        title: 'Што се повторувачки фактури?',
        content: 'Повторувачка фактура е фактура која автоматски се генерира на одреден временски интервал — неделно, месечно, квартално или годишно. Наместо рачно да креирате нова фактура секој пат кога клиентот треба да плати, системот тоа го прави автоматски. Ова е особено корисно за бизниси кои имаат предвидливи приходи: SaaS компании, сметководствени бироа, агенции за дигитален маркетинг, фирми за одржување, закупнини на недвижности и консултантски услуги со ретејнер договори.',
        items: null,
        steps: null,
      },
      {
        title: 'Предности за вашиот бизнис',
        content: 'Автоматизацијата на фактурирањето носи повеќекратни бенефиции кои директно влијаат на вашиот cash flow и ефикасноста на тимот.',
        items: [
          'Заштеда на време — Елиминирајте часови рачна работа секој месец. Фактурите се креираат и испраќаат без ваша интервенција.',
          'Помалку грешки — Автоматското генерирање ги елиминира грешките при внесување податоци, погрешни износи и заборавени ставки.',
          'Подобар cash flow — Фактурите пристигнуваат на време, секој месец, што значи побрзо плаќање и предвидлив приход.',
          'Професионален имиџ — Клиентите добиваат конзистентни, добро форматирани фактури на точно определен ден.',
          'Автоматски потсетници — Facturino може да испрати потсетник доколку фактурата не е платена до рокот.',
          'Намалување на доцни плаќања — Со автоматски потсетници и јасни рокови, клиентите плаќаат навремено.',
        ],
        steps: null,
      },
      {
        title: 'Кои бизниси имаат најголема корист?',
        content: 'Иако повторувачките фактури се корисни за секој бизнис, некои индустрии имаат особена потреба од нив.',
        items: [
          'SaaS и софтверски компании — Месечни или годишни претплати за софтверски лиценци.',
          'Сметководствени бироа — Месечни пакети за книговодствени услуги со фиксна цена.',
          'Агенции и консултанти — Ретејнер договори за маркетинг, дизајн или правни услуги.',
          'Закупнини — Месечна наплата за канцелариски простор, магацини или опрема.',
          'Услуги за одржување — Месечни договори за IT поддршка, чистење или безбедност.',
          'Фитнес центри и клубови — Членарини кои се наплаќаат на месечна или годишна основа.',
        ],
        steps: null,
      },
      {
        title: 'Како да поставите повторувачки фактури во Facturino',
        content: null,
        items: null,
        steps: [
          { step: 'Креирајте шаблон на фактура', desc: 'Одберете го клиентот, додадете ги ставките со цени и ДДВ стапка. Овој шаблон ќе биде основа за секоја автоматски генерирана фактура.' },
          { step: 'Поставете фреквенција', desc: 'Изберете колку често да се генерира фактурата: неделно, месечно, квартално или годишно. Поставете датум на почеток и опционално датум на завршување.' },
          { step: 'Конфигурирајте потсетници', desc: 'Активирајте автоматски потсетници за неплатени фактури. Можете да поставите потсетник 3, 7 и 14 дена по рокот за плаќање.' },
          { step: 'Активирајте и следете', desc: 'Откако ќе ја активирате повторувачката фактура, Facturino автоматски ќе генерира и испраќа фактури на поставениот интервал. Следете го статусот во вашиот dashboard.' },
        ],
      },
      {
        title: 'Управување со доцни плаќања',
        content: 'Доцните плаќања се еден од најголемите предизвици за малите бизниси. Facturino ви помага да ги минимизирате преку неколку механизми. Автоматските потсетници се испраќаат по е-пошта кога фактурата е достасана но неплатена. Можете да поставите серија потсетници — прв по 3 дена, втор по 7 дена и финален по 14 дена. Дополнително, секоја фактура има јасно означен рок за плаќање и статус (платена, неплатена, достасана). Dashboard-от ви покажува преглед на сите достасани фактури за брза акција.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура: Чекор-по-чекор водич' },
      { slug: 'cash-flow-mk', title: 'Cash Flow: Зошто е позначаен од профитот' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Зошто табели не се доволни' },
    ],
    cta: {
      title: 'Автоматизирајте го фактурирањето денес',
      desc: 'Поставете повторувачки фактури за неколку минути и заборавете на рачното фактурирање.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Tips',
    title: 'Recurring Invoices: Automate Your Billing',
    publishDate: 'February 9, 2026',
    readTime: '6 min read',
    intro: 'Any business that works with subscriptions, monthly rentals, or long-term contracts knows how time-consuming it is to manually create invoices every month. Recurring invoices eliminate that problem — they are automatically generated, sent, and tracked. In this guide, we explain what recurring invoices are, why they matter, and how to set them up in Facturino for complete billing automation.',
    sections: [
      {
        title: 'What are recurring invoices?',
        content: 'A recurring invoice is an invoice that is automatically generated at a set time interval — weekly, monthly, quarterly, or annually. Instead of manually creating a new invoice each time a client needs to pay, the system does it automatically. This is especially useful for businesses with predictable revenue streams: SaaS companies, accounting firms, digital marketing agencies, maintenance companies, property rentals, and consulting services with retainer agreements.',
        items: null,
        steps: null,
      },
      {
        title: 'Benefits for your business',
        content: 'Automating your invoicing process brings multiple benefits that directly impact your cash flow and team efficiency.',
        items: [
          'Time savings — Eliminate hours of manual work every month. Invoices are created and sent without your intervention.',
          'Fewer errors — Automatic generation eliminates data entry mistakes, incorrect amounts, and forgotten line items.',
          'Better cash flow — Invoices arrive on time, every month, which means faster payments and predictable revenue.',
          'Professional image — Clients receive consistent, well-formatted invoices on a precisely defined schedule.',
          'Automatic reminders — Facturino can send a reminder if an invoice is not paid by the due date.',
          'Reduced late payments — With automatic reminders and clear deadlines, clients pay on time.',
        ],
        steps: null,
      },
      {
        title: 'Which businesses benefit most?',
        content: 'While recurring invoices are useful for any business, some industries have a particular need for them.',
        items: [
          'SaaS and software companies — Monthly or annual subscriptions for software licenses.',
          'Accounting firms — Monthly packages for bookkeeping services at a fixed price.',
          'Agencies and consultants — Retainer agreements for marketing, design, or legal services.',
          'Rentals — Monthly billing for office space, warehouses, or equipment.',
          'Maintenance services — Monthly contracts for IT support, cleaning, or security.',
          'Fitness centers and clubs — Memberships billed on a monthly or annual basis.',
        ],
        steps: null,
      },
      {
        title: 'How to set up recurring invoices in Facturino',
        content: null,
        items: null,
        steps: [
          { step: 'Create an invoice template', desc: 'Select the client, add line items with prices and VAT rate. This template will serve as the basis for each automatically generated invoice.' },
          { step: 'Set the frequency', desc: 'Choose how often the invoice should be generated: weekly, monthly, quarterly, or annually. Set a start date and optionally an end date.' },
          { step: 'Configure reminders', desc: 'Enable automatic reminders for unpaid invoices. You can set reminders for 3, 7, and 14 days after the payment due date.' },
          { step: 'Activate and monitor', desc: 'Once you activate the recurring invoice, Facturino will automatically generate and send invoices at the set interval. Monitor the status in your dashboard.' },
        ],
      },
      {
        title: 'Managing late payments',
        content: 'Late payments are one of the biggest challenges for small businesses. Facturino helps you minimize them through several mechanisms. Automatic reminders are sent by email when an invoice is due but unpaid. You can set up a series of reminders — the first after 3 days, the second after 7 days, and a final one after 14 days. Additionally, each invoice has a clearly marked due date and status (paid, unpaid, overdue). The dashboard shows you an overview of all overdue invoices for quick action.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice: Step-by-Step Guide' },
      { slug: 'cash-flow-mk', title: 'Cash Flow: Why It Matters More Than Profit' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Why Spreadsheets Are Not Enough' },
    ],
    cta: {
      title: 'Automate your invoicing today',
      desc: 'Set up recurring invoices in minutes and forget about manual billing.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Këshilla',
    title: 'Faturat e përsëritura: Automatizoni faturimin',
    publishDate: '9 shkurt 2026',
    readTime: '6 min lexim',
    intro: 'Çdo biznes që punon me abonime, qira mujore ose kontrata afatgjata e di sa kohë harxhohet duke krijuar fatura manualisht çdo muaj. Faturat e përsëritura e eliminojnë këtë problem — ato gjenerohen, dërgohen dhe ndiqen automatikisht. Në këtë udhëzues, shpjegojmë çfarë janë faturat e përsëritura, pse kanë rëndësi dhe si t\'i konfiguroni në Facturino për automatizim të plotë të faturimit.',
    sections: [
      {
        title: 'Çfarë janë faturat e përsëritura?',
        content: 'Një faturë e përsëritur është një faturë që gjenerohet automatikisht në një interval të caktuar kohor — javore, mujore, tremujore ose vjetore. Në vend që të krijoni manualisht një faturë të re çdo herë kur klienti duhet të paguajë, sistemi e bën automatikisht. Kjo është veçanërisht e dobishme për bizneset me të ardhura të parashikueshme: kompani SaaS, zyra kontabiliteti, agjenci marketingu dixhital, kompani mirëmbajtjeje, qira pronash dhe shërbime konsulence me marrëveshje retainer.',
        items: null,
        steps: null,
      },
      {
        title: 'Përfitimet për biznesin tuaj',
        content: 'Automatizimi i procesit të faturimit sjell përfitime të shumta që ndikojnë drejtpërdrejt në rrjedhën tuaj të parasë dhe efikasitetin e ekipit.',
        items: [
          'Kursim kohe — Eliminoni orë pune manuale çdo muaj. Faturat krijohen dhe dërgohen pa ndërhyrjen tuaj.',
          'Më pak gabime — Gjenerimi automatik eliminon gabimet e futjes së të dhënave, shumat e pasakta dhe zërat e harruara.',
          'Rrjedhë më e mirë parash — Faturat arrijnë në kohë, çdo muaj, që do të thotë pagesa më të shpejta dhe të ardhura të parashikueshme.',
          'Imazh profesional — Klientët marrin fatura konsistente, të formatuara mirë në një orar të përcaktuar saktë.',
          'Kujtesa automatike — Facturino mund të dërgojë kujtesë nëse fatura nuk paguhet deri në datën e duhur.',
          'Reduktim i pagesave të vonuara — Me kujtesa automatike dhe afate të qarta, klientët paguajnë në kohë.',
        ],
        steps: null,
      },
      {
        title: 'Cilat biznese përfitojnë më shumë?',
        content: 'Edhe pse faturat e përsëritura janë të dobishme për çdo biznes, disa industri kanë nevojë të veçantë për to.',
        items: [
          'Kompani SaaS dhe softuerësh — Abonime mujore ose vjetore për licenca softuerësh.',
          'Zyra kontabiliteti — Paketa mujore për shërbime kontabiliteti me çmim fiks.',
          'Agjenci dhe konsulentë — Marrëveshje retainer për marketing, dizajn ose shërbime juridike.',
          'Qira — Faturim mujor për hapësira zyrash, magazina ose pajisje.',
          'Shërbime mirëmbajtjeje — Kontrata mujore për mbështetje IT, pastrim ose siguri.',
          'Qendra fitnesi dhe klube — Anëtarësi që faturohen në baza mujore ose vjetore.',
        ],
        steps: null,
      },
      {
        title: 'Si të konfiguroni faturat e përsëritura në Facturino',
        content: null,
        items: null,
        steps: [
          { step: 'Krijoni një shablon fature', desc: 'Zgjidhni klientin, shtoni zërat me çmime dhe normën e TVSH-së. Ky shablon do të shërbejë si bazë për çdo faturë të gjeneruar automatikisht.' },
          { step: 'Vendosni frekuencën', desc: 'Zgjidhni sa shpesh duhet të gjenerohet fatura: javore, mujore, tremujore ose vjetore. Vendosni datën e fillimit dhe opcionalisht datën e përfundimit.' },
          { step: 'Konfiguroni kujtesat', desc: 'Aktivizoni kujtesat automatike për faturat e papaguara. Mund të vendosni kujtesa 3, 7 dhe 14 ditë pas datës së pagesës.' },
          { step: 'Aktivizoni dhe monitoroni', desc: 'Pasi të aktivizoni faturën e përsëritur, Facturino do të gjenerojë dhe dërgojë automatikisht fatura në intervalin e vendosur. Monitoroni statusin në panelin tuaj.' },
        ],
      },
      {
        title: 'Menaxhimi i pagesave të vonuara',
        content: 'Pagesat e vonuara janë një nga sfidat më të mëdha për bizneset e vogla. Facturino ju ndihmon t\'i minimizoni përmes disa mekanizmave. Kujtesat automatike dërgohen me email kur fatura ka arritur por nuk është paguar. Mund të vendosni një seri kujtesash — e para pas 3 ditësh, e dyta pas 7 ditësh dhe e fundit pas 14 ditësh. Gjithashtu, çdo faturë ka një datë pagese të shënuar qartë dhe status (e paguar, e papaguar, e vonuar). Paneli ju tregon një pasqyrë të të gjitha faturave të vonuara për veprim të shpejtë.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni një faturë: Udhëzues hap pas hapi' },
      { slug: 'cash-flow-mk', title: 'Cash Flow: Pse është më i rëndësishëm se fitimi' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Pse tabelat nuk mjaftojnë' },
    ],
    cta: {
      title: 'Automatizoni faturimin sot',
      desc: 'Konfiguroni faturat e përsëritura brenda minutash dhe harroni faturimin manual.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'İpuçları',
    title: 'Tekrarlanan faturalar: Faturalandırmayı otomatikleştirin',
    publishDate: '9 Şubat 2026',
    readTime: '6 dk okuma',
    intro: 'Abonelikler, aylık kiralar veya uzun vadeli sözleşmelerle çalışan her işletme, her ay manuel olarak fatura oluşturmanın ne kadar zaman aldığını bilir. Tekrarlanan faturalar bu sorunu ortadan kaldırır — otomatik olarak oluşturulur, gönderilir ve takip edilir. Bu rehberde, tekrarlanan faturaların ne olduğunu, neden önemli olduğunu ve tam faturalandırma otomasyonu için Facturino\'da nasıl kurulacağını açıklıyoruz.',
    sections: [
      {
        title: 'Tekrarlanan faturalar nedir?',
        content: 'Tekrarlanan fatura, belirli bir zaman aralığında — haftalık, aylık, üç aylık veya yıllık olarak — otomatik olarak oluşturulan bir faturadır. Bir müşterinin her ödeme yapması gerektiğinde manuel olarak yeni bir fatura oluşturmak yerine, sistem bunu otomatik olarak yapar. Bu, öngörülebilir gelir akışlarına sahip işletmeler için özellikle yararlıdır: SaaS şirketleri, muhasebe firmaları, dijital pazarlama ajansları, bakım şirketleri, gayrimenkul kiraları ve danışmanlık hizmetleri.',
        items: null,
        steps: null,
      },
      {
        title: 'İşletmeniz için avantajları',
        content: 'Faturalandırma sürecinizi otomatikleştirmek, nakit akışınızı ve ekip verimliliğinizi doğrudan etkileyen birçok fayda sağlar.',
        items: [
          'Zaman tasarrufu — Her ay saatlerce süren manuel çalışmayı ortadan kaldırın. Faturalar müdahaleniz olmadan oluşturulur ve gönderilir.',
          'Daha az hata — Otomatik oluşturma, veri girişi hatalarını, yanlış tutarları ve unutulan kalemleri ortadan kaldırır.',
          'Daha iyi nakit akışı — Faturalar her ay zamanında ulaşır, bu da daha hızlı ödemeler ve öngörülebilir gelir anlamına gelir.',
          'Profesyonel imaj — Müşteriler, tam olarak belirlenmiş bir programa göre tutarlı, iyi biçimlendirilmiş faturalar alır.',
          'Otomatik hatırlatmalar — Facturino, bir fatura vadesinde ödenmezse hatırlatma gönderebilir.',
          'Gecikmiş ödemelerin azaltılması — Otomatik hatırlatmalar ve net vadeler sayesinde müşteriler zamanında öder.',
        ],
        steps: null,
      },
      {
        title: 'Hangi işletmeler en çok faydalanır?',
        content: 'Tekrarlanan faturalar her işletme için yararlı olsa da, bazı sektörlerin bunlara özellikle ihtiyacı vardır.',
        items: [
          'SaaS ve yazılım şirketleri — Yazılım lisansları için aylık veya yıllık abonelikler.',
          'Muhasebe firmaları — Sabit fiyatlı defter tutma hizmetleri için aylık paketler.',
          'Ajanslar ve danışmanlar — Pazarlama, tasarım veya hukuk hizmetleri için retainer anlaşmaları.',
          'Kiralar — Ofis alanı, depo veya ekipman için aylık faturalandırma.',
          'Bakım hizmetleri — BT desteği, temizlik veya güvenlik için aylık sözleşmeler.',
          'Spor salonları ve kulüpler — Aylık veya yıllık bazda faturalanan üyelikler.',
        ],
        steps: null,
      },
      {
        title: 'Facturino\'da tekrarlanan faturaları nasıl kurarsınız',
        content: null,
        items: null,
        steps: [
          { step: 'Fatura şablonu oluşturun', desc: 'Müşteriyi seçin, fiyatlar ve KDV oranıyla kalemleri ekleyin. Bu şablon, otomatik olarak oluşturulan her faturanın temeli olacaktır.' },
          { step: 'Frekansı ayarlayın', desc: 'Faturanın ne sıklıkta oluşturulacağını seçin: haftalık, aylık, üç aylık veya yıllık. Başlangıç tarihi ve isteğe bağlı olarak bitiş tarihi belirleyin.' },
          { step: 'Hatırlatmaları yapılandırın', desc: 'Ödenmemiş faturalar için otomatik hatırlatmaları etkinleştirin. Ödeme vadesinden 3, 7 ve 14 gün sonra hatırlatmalar ayarlayabilirsiniz.' },
          { step: 'Etkinleştirin ve izleyin', desc: 'Tekrarlanan faturayı etkinleştirdikten sonra Facturino, belirlenen aralıkta otomatik olarak fatura oluşturup gönderecektir. Durumu kontrol panelinizden izleyin.' },
        ],
      },
      {
        title: 'Gecikmiş ödemelerin yönetimi',
        content: 'Gecikmiş ödemeler, küçük işletmeler için en büyük zorluklardan biridir. Facturino, bunları çeşitli mekanizmalarla en aza indirmenize yardımcı olur. Bir fatura vadesi geldiğinde ancak ödenmediğinde otomatik hatırlatmalar e-posta ile gönderilir. Bir dizi hatırlatma ayarlayabilirsiniz — ilki 3 gün sonra, ikincisi 7 gün sonra ve sonuncusu 14 gün sonra. Ayrıca her faturada açıkça işaretlenmiş bir vade tarihi ve durum (ödenmiş, ödenmemiş, gecikmiş) bulunur. Kontrol paneli, hızlı aksiyon için tüm gecikmiş faturaların genel görünümünü sunar.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Fatura nasıl oluşturulur: Adım adım rehber' },
      { slug: 'cash-flow-mk', title: 'Nakit akışı: Neden kârdan daha önemli' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Neden tablolar yetmez' },
    ],
    cta: {
      title: 'Faturalandırmayı bugün otomatikleştirin',
      desc: 'Tekrarlanan faturaları dakikalar içinde kurun ve manuel faturalamayı unutun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function RecurringInvoicesMkPage({
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
