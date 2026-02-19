import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/rokovi-ujp-2026', {
    title: {
      mk: 'Даночен календар 2026: Сите рокови за УЈП | Facturino',
      en: 'Tax Calendar 2026: All UJP Deadlines | Facturino',
      sq: 'Kalendari tatimor 2026: Të gjitha afatet e UJP | Facturino',
      tr: 'Vergi takvimi 2026: Tüm UJP son tarihleri | Facturino',
    },
    description: {
      mk: 'Комплетен даночен календар за 2026 со сите рокови за ДДВ, МПИН, аконтации на данок на добивка и годишни даночни пријави кон УЈП.',
      en: 'Complete 2026 tax calendar with all deadlines for VAT returns, MPIN payroll, corporate income tax advances, and annual returns to UJP.',
      sq: 'Kalendari i plotë tatimor 2026 me të gjitha afatet për TVSH, MPIN, parapagimet e tatimit mbi fitimin dhe deklaratat vjetore për UJP.',
      tr: '2026 vergi takvimi: KDV beyannameleri, MPIN bordro, kurumlar vergisi avansları ve UJP yıllık beyannameleri için tüm son tarihler.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Даночен календар 2026: Сите рокови за УЈП',
    publishDate: '8 февруари 2026',
    readTime: '8 мин читање',
    intro: 'Секоја година, стотици бизниси во Македонија добиваат казни заради пропуштени даночни рокови. Не мора да биде така. Овој комплетен даночен календар за 2026 ги покрива сите клучни датуми за поднесување пријави до Управата за јавни приходи (УЈП), вклучувајќи ДДВ, МПИН, данок на добивка и годишни пријави. Зачувајте ја оваа страница и никогаш повеќе нема да пропуштите рок.',
    sections: [
      {
        title: 'Месечни обврски: ДДВ и МПИН',
        content: 'Двете најчести месечни обврски за секој бизнис регистриран за ДДВ се поднесувањето на ДДВ-пријавата и МПИН-пријавата за плати. Овие рокови се повторуваат секој месец и нивното непочитување повлекува значајни казни.',
        items: [
          'ДДВ пријава (ДДВ-04): Се поднесува до 25-ти во месецот за претходниот месец. На пример, ДДВ за јануари се поднесува до 25 февруари.',
          'МПИН пријава за плати: Се поднесува до 15-ти во месецот за претходниот месец. Придонесите мора да бидат платени истиот ден.',
          'Аконтација на данок на добивка: Месечна уплата до 15-ти во месецот, пресметана врз основа на претходногодишниот данок.',
          'Персонален данок на доход (ПДД): За исплатени хонорари и авторски договори, се поднесува до 15-ти во наредниот месец.',
          'Аконтација на данок на доход за самостојни дејности: До 15-ти секој месец, според решение на УЈП.',
        ],
        steps: null,
      },
      {
        title: 'Квартални рокови',
        content: 'Некои обврски се поднесуваат на квартална основа, особено за бизниси со помал обем или за специфични даночни форми.',
        items: [
          'Квартална ДДВ пријава (за обврзници со промет под 25М МКД): До 25-ти по завршување на кварталот — 25 април, 25 јули, 25 октомври, 25 јануари 2027.',
          'Извештај за трансферни цени (за поврзани лица): Се поднесува заедно со годишната пријава, но подготовката е квартална.',
          'Статистички извештаи за НБМ: Кварталните извештаи имаат рок 20 дена по завршување на кварталот.',
        ],
        steps: null,
      },
      {
        title: 'Годишни рокови: Клучни датуми за 2026',
        content: null,
        items: null,
        steps: [
          { step: '28 февруари 2026', desc: 'Годишен извештај за исплатен ПДД (образец ПДД-ГИ) — работодавците мора да достават збирен извештај за сите исплати на физички лица во 2025.' },
          { step: '15 март 2026', desc: 'Годишна даночна пријава за данок на добивка (ДБ-ВП) — сите правни лица мора да ја поднесат пријавата за финансиската 2025 година, заедно со финансиските извештаи.' },
          { step: '15 март 2026', desc: 'Годишна сметка (завршна сметка) — се поднесува до Централниот регистар. Вклучува биланс на состојба, биланс на успех и дополнителни податоци.' },
          { step: '31 март 2026', desc: 'Годишна даночна пријава за персонален данок на доход (ПДД-ГДП) — физички лица и самостојни вршители на дејност ги пријавуваат сите приходи за 2025.' },
          { step: '30 април 2026', desc: 'Поднесување на финален МПИН за претходната година доколку има корекции и дополнителни пресметки за бонуси исплатени во Q1.' },
        ],
      },
      {
        title: 'Казни за пропуштени рокови',
        content: 'Управата за јавни приходи применува строги санкции за ненавремено поднесување или неплаќање на даночни обврски. Казните можат значително да го оптоваруваат буџетот на малите бизниси.',
        items: [
          'Ненавремено поднесување на ДДВ пријава: Казна од 250 до 1.000 EUR во денарска противвредност за правно лице.',
          'Задоцнето плаќање на даноци: Камата од 0,03% дневно (околу 11% годишно) врз неплатениот износ.',
          'Неподнесување на годишна сметка: Казна од 500 до 2.000 EUR и можност за бришење од Централен регистар.',
          'Неподнесување на МПИН: Казна од 250 до 1.000 EUR плус камата на неплатени придонеси.',
          'Повторени прекршоци: Можност за забрана на дејност и присилна наплата преку сметки на фирмата.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ви помага да ги испочитувате роковите',
        content: 'Facturino е дизајниран специјално за македонскиот даночен систем. Автоматски ги следи вашите обврски и ве известува пред секој рок. Нема потреба да помните датуми — системот работи за вас.',
        items: [
          'Автоматски пресметки за ДДВ врз основа на издадени и примени фактури.',
          'Генерирање на МПИН-пријави директно од платниот список.',
          'Потсетници на email и во апликацијата 7 дена и 2 дена пред секој рок.',
          'Извоз на податоци компатибилен со е-Даноци порталот на УЈП.',
          'Целосна историја на поднесени документи за ревизија.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
      { slug: 'godishna-smetka-2025', title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ' },
    ],
    cta: {
      title: 'Никогаш повеќе не пропуштајте рок',
      desc: 'Facturino автоматски ве известува за сите даночни рокови. Започнете бесплатно и заборавете на казните.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Tax Calendar 2026: All UJP Deadlines',
    publishDate: 'February 8, 2026',
    readTime: '8 min read',
    intro: 'Every year, hundreds of businesses in Macedonia receive penalties for missed tax deadlines. It does not have to be that way. This complete 2026 tax calendar covers all key filing dates for the Public Revenue Office (UJP), including VAT, MPIN payroll contributions, corporate income tax advances, and annual returns. Bookmark this page and never miss a deadline again.',
    sections: [
      {
        title: 'Monthly obligations: VAT and MPIN',
        content: 'The two most common monthly obligations for every VAT-registered business are the VAT return and the MPIN payroll return. These deadlines recur every month and non-compliance carries significant penalties.',
        items: [
          'VAT return (DDV-04): Filed by the 25th of the month for the previous month. For example, January VAT is due by February 25.',
          'MPIN payroll return: Filed by the 15th of the month for the previous month. Contributions must be paid on the same day.',
          'Corporate income tax advance: Monthly payment by the 15th, calculated based on the previous year\'s tax liability.',
          'Personal income tax (PDD): For paid honorariums and author contracts, filed by the 15th of the following month.',
          'Self-employment income tax advance: Due by the 15th each month, per UJP assessment.',
        ],
        steps: null,
      },
      {
        title: 'Quarterly deadlines',
        content: 'Some obligations are filed on a quarterly basis, particularly for businesses with smaller turnover or for specific tax forms.',
        items: [
          'Quarterly VAT return (for taxpayers with turnover under 25M MKD): Due by the 25th after the quarter ends — April 25, July 25, October 25, January 25, 2027.',
          'Transfer pricing report (for related parties): Filed together with the annual return, but preparation is quarterly.',
          'Statistical reports for NBRM: Quarterly reports are due 20 days after the quarter ends.',
        ],
        steps: null,
      },
      {
        title: 'Annual deadlines: Key dates for 2026',
        content: null,
        items: null,
        steps: [
          { step: 'February 28, 2026', desc: 'Annual PDD payment report (PDD-GI form) — employers must submit a summary report of all payments to individuals made in 2025.' },
          { step: 'March 15, 2026', desc: 'Annual corporate income tax return (DB-VP) — all legal entities must file for the 2025 fiscal year, along with financial statements.' },
          { step: 'March 15, 2026', desc: 'Annual accounts (final accounts) — filed with the Central Registry. Includes balance sheet, income statement, and supplementary data.' },
          { step: 'March 31, 2026', desc: 'Annual personal income tax return (PDD-GDP) — individuals and sole proprietors report all 2025 income.' },
          { step: 'April 30, 2026', desc: 'Final MPIN submission for the previous year if there are corrections and additional calculations for bonuses paid in Q1.' },
        ],
      },
      {
        title: 'Penalties for missed deadlines',
        content: 'The Public Revenue Office applies strict sanctions for late filing or non-payment of tax obligations. Penalties can significantly burden the budgets of small businesses.',
        items: [
          'Late VAT return filing: Fine of EUR 250 to 1,000 (in MKD equivalent) for legal entities.',
          'Late tax payment: Interest of 0.03% per day (approximately 11% annually) on the unpaid amount.',
          'Failure to file annual accounts: Fine of EUR 500 to 2,000 and possible deletion from the Central Registry.',
          'Failure to file MPIN: Fine of EUR 250 to 1,000 plus interest on unpaid contributions.',
          'Repeated violations: Possible business activity ban and forced collection through company bank accounts.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps you meet every deadline',
        content: 'Facturino is designed specifically for the Macedonian tax system. It automatically tracks your obligations and notifies you before each deadline. No need to remember dates — the system works for you.',
        items: [
          'Automatic VAT calculations based on issued and received invoices.',
          'MPIN return generation directly from the payroll list.',
          'Email and in-app reminders 7 days and 2 days before each deadline.',
          'Data export compatible with the UJP e-Danoci portal.',
          'Complete history of submitted documents for audit purposes.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
      { slug: 'godishna-smetka-2025', title: 'Annual Accounts 2025: Complete Filing Guide for CRMS' },
    ],
    cta: {
      title: 'Never miss a deadline again',
      desc: 'Facturino automatically notifies you of all tax deadlines. Start free and forget about penalties.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Kalendari tatimor 2026: Të gjitha afatet e UJP',
    publishDate: '8 shkurt 2026',
    readTime: '8 min lexim',
    intro: 'Çdo vit, qindra biznese në Maqedoni marrin gjoba për afate tatimore të humbura. Nuk duhet të jetë kështu. Ky kalendar i plotë tatimor për 2026 mbulon të gjitha datat kyçe të dorëzimit për Zyrën e të Ardhurave Publike (UJP), duke përfshirë TVSH-në, kontributet e pagave MPIN, parapagimet e tatimit mbi fitimin dhe deklaratat vjetore. Ruajeni këtë faqe dhe mos humbisni më kurrë një afat.',
    sections: [
      {
        title: 'Detyrimet mujore: TVSH dhe MPIN',
        content: 'Dy detyrimet më të zakonshme mujore për çdo biznes të regjistruar për TVSH janë deklarata e TVSH-së dhe deklarata e pagave MPIN. Këto afate përsëriten çdo muaj dhe mosrespektimi mbart gjoba të konsiderueshme.',
        items: [
          'Deklarata e TVSH-së (DDV-04): Dorëzohet deri më 25 të muajit për muajin e kaluar. Për shembull, TVSH e janarit dorëzohet deri më 25 shkurt.',
          'Deklarata MPIN për paga: Dorëzohet deri më 15 të muajit për muajin e kaluar. Kontributet duhet të paguhen në të njëjtën ditë.',
          'Parapagimi i tatimit mbi fitimin: Pagesa mujore deri më 15, e llogaritur bazuar në tatimin e vitit të kaluar.',
          'Tatimi mbi të ardhurat personale (PDD): Për honorare dhe kontrata autoriale, dorëzohet deri më 15 të muajit pasues.',
          'Parapagimi i tatimit për veprimtari të pavarur: Deri më 15 çdo muaj, sipas vlerësimit të UJP.',
        ],
        steps: null,
      },
      {
        title: 'Afatet tremujore',
        content: 'Disa detyrime dorëzohen në baza tremujore, veçanërisht për bizneset me qarkullim më të vogël ose për forma specifike tatimore.',
        items: [
          'Deklarata tremujore e TVSH-së (për tatimpagues me qarkullim nën 25M MKD): Deri më 25 pas përfundimit të tremujorit — 25 prill, 25 korrik, 25 tetor, 25 janar 2027.',
          'Raporti i çmimeve të transferimit (për palë të lidhura): Dorëzohet bashkë me deklaratën vjetore, por përgatitja është tremujore.',
          'Raportet statistikore për BPRM: Raportet tremujore kanë afat 20 ditë pas përfundimit të tremujorit.',
        ],
        steps: null,
      },
      {
        title: 'Afatet vjetore: Datat kyçe për 2026',
        content: null,
        items: null,
        steps: [
          { step: '28 shkurt 2026', desc: 'Raporti vjetor i pagesave PDD (formulari PDD-GI) — punëdhënësit duhet të dorëzojnë raport përmbledhës të të gjitha pagesave ndaj personave fizikë në 2025.' },
          { step: '15 mars 2026', desc: 'Deklarata vjetore e tatimit mbi fitimin (DB-VP) — të gjitha subjektet juridike duhet ta dorëzojnë për vitin fiskal 2025, bashkë me pasqyrat financiare.' },
          { step: '15 mars 2026', desc: 'Llogaritë vjetore (llogaritë përfundimtare) — dorëzohen në Regjistrin Qendror. Përfshijnë bilancin e gjendjes, bilancin e suksesit dhe të dhëna plotësuese.' },
          { step: '31 mars 2026', desc: 'Deklarata vjetore e tatimit mbi të ardhurat personale (PDD-GDP) — personat fizikë dhe tregtarët individualë raportojnë të gjitha të ardhurat e 2025.' },
          { step: '30 prill 2026', desc: 'Dorëzimi përfundimtar i MPIN për vitin e kaluar nëse ka korrigjime dhe llogaritje shtesë për bonuse të paguara në T1.' },
        ],
      },
      {
        title: 'Gjobat për afate të humbura',
        content: 'Zyra e të Ardhurave Publike aplikon sanksione të rrepta për dorëzim të vonuar ose mospagim të detyrimeve tatimore. Gjobat mund të rëndojnë ndjeshëm buxhetet e bizneseve të vogla.',
        items: [
          'Dorëzim i vonuar i deklaratës së TVSH-së: Gjobë nga 250 deri në 1.000 EUR (në ekuivalent MKD) për subjekte juridike.',
          'Pagesa e vonuar e tatimeve: Kamatë prej 0,03% në ditë (rreth 11% në vit) mbi shumën e papaguar.',
          'Mosdorëzimi i llogarive vjetore: Gjobë nga 500 deri në 2.000 EUR dhe mundësi fshirjeje nga Regjistri Qendror.',
          'Mosdorëzimi i MPIN: Gjobë nga 250 deri në 1.000 EUR plus kamatë mbi kontributet e papaguara.',
          'Shkelje të përsëritura: Mundësi ndalimi i veprimtarisë dhe arkëtim i detyruar përmes llogarive bankare.',
        ],
        steps: null,
      },
      {
        title: 'Si ju ndihmon Facturino të respektoni çdo afat',
        content: 'Facturino është projektuar posaçërisht për sistemin tatimor maqedonas. Automatikisht ndjek detyrimet tuaja dhe ju njofton para çdo afati. Nuk keni nevojë të mbani mend data — sistemi punon për ju.',
        items: [
          'Llogaritje automatike të TVSH-së bazuar në faturat e lëshuara dhe të marra.',
          'Gjenerimi i deklaratës MPIN direkt nga lista e pagave.',
          'Kujtesa me email dhe në aplikacion 7 ditë dhe 2 ditë para çdo afati.',
          'Eksport i të dhënave i përputhshëm me portalin e-Danoci të UJP.',
          'Histori e plotë e dokumenteve të dorëzuara për qëllime auditimi.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
      { slug: 'godishna-smetka-2025', title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim në QRMK' },
    ],
    cta: {
      title: 'Mos humbisni më kurrë një afat',
      desc: 'Facturino ju njofton automatikisht për të gjitha afatet tatimore. Filloni falas dhe harroni gjobat.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Vergi takvimi 2026: Tüm UJP son tarihleri',
    publishDate: '8 Şubat 2026',
    readTime: '8 dk okuma',
    intro: 'Her yıl Makedonya\'da yüzlerce işletme kaçırılan vergi son tarihleri nedeniyle ceza alıyor. Böyle olmak zorunda değil. Bu kapsamlı 2026 vergi takvimi, KDV, MPIN bordro katkıları, kurumlar vergisi avansları ve yıllık beyannameler dahil olmak üzere Kamu Gelir İdaresi\'ne (UJP) yapılacak tüm önemli başvuru tarihlerini kapsamaktadır. Bu sayfayı kaydedin ve bir daha asla son tarihi kaçırmayın.',
    sections: [
      {
        title: 'Aylık yükümlülükler: KDV ve MPIN',
        content: 'KDV\'ye kayıtlı her işletme için en yaygın iki aylık yükümlülük, KDV beyannamesi ve MPIN bordro beyannamesidir. Bu son tarihler her ay tekrarlanır ve uyumsuzluk önemli cezalar getirir.',
        items: [
          'KDV beyannamesi (DDV-04): Önceki ay için ayın 25\'ine kadar verilir. Örneğin, Ocak KDV\'si 25 Şubat\'a kadar ödenir.',
          'MPIN bordro beyannamesi: Önceki ay için ayın 15\'ine kadar verilir. Katkılar aynı gün ödenmelidir.',
          'Kurumlar vergisi avansı: Önceki yılın vergi yükümlülüğüne göre hesaplanan aylık ödeme, 15\'ine kadar.',
          'Kişisel gelir vergisi (PDD): Ödenen honoraryumlar ve telif sözleşmeleri için, ertesi ayın 15\'ine kadar verilir.',
          'Serbest meslek gelir vergisi avansı: UJP değerlendirmesine göre her ayın 15\'ine kadar ödenir.',
        ],
        steps: null,
      },
      {
        title: 'Üç aylık son tarihler',
        content: 'Bazı yükümlülükler üç aylık bazda verilir; özellikle daha düşük ciroya sahip işletmeler veya belirli vergi formları için.',
        items: [
          'Üç aylık KDV beyannamesi (cirosu 25M MKD altında olan mükellefler için): Çeyrek bitiminden sonra 25\'ine kadar — 25 Nisan, 25 Temmuz, 25 Ekim, 25 Ocak 2027.',
          'Transfer fiyatlandırma raporu (ilişkili taraflar için): Yıllık beyanname ile birlikte verilir, ancak hazırlık üç aylıktır.',
          'NBRM istatistik raporları: Üç aylık raporlar çeyrek bitiminden 20 gün sonra verilir.',
        ],
        steps: null,
      },
      {
        title: 'Yıllık son tarihler: 2026 için önemli tarihler',
        content: null,
        items: null,
        steps: [
          { step: '28 Şubat 2026', desc: 'Yıllık PDD ödeme raporu (PDD-GI formu) — işverenler 2025 yılında bireylere yapılan tüm ödemelerin özet raporunu sunmalıdır.' },
          { step: '15 Mart 2026', desc: 'Yıllık kurumlar vergisi beyannamesi (DB-VP) — tüm tüzel kişiler mali tablolarla birlikte 2025 mali yılı için başvurmalıdır.' },
          { step: '15 Mart 2026', desc: 'Yıllık hesaplar (kapanış hesapları) — Merkez Sicile verilir. Bilanço, gelir tablosu ve ek verileri içerir.' },
          { step: '31 Mart 2026', desc: 'Yıllık kişisel gelir vergisi beyannamesi (PDD-GDP) — bireyler ve serbest meslek sahipleri 2025 gelirlerinin tamamını bildirir.' },
          { step: '30 Nisan 2026', desc: 'Önceki yıl için düzeltmeler ve Q1\'de ödenen ikramiyeler için ek hesaplamalar varsa nihai MPIN sunumu.' },
        ],
      },
      {
        title: 'Kaçırılan son tarihler için cezalar',
        content: 'Kamu Gelir İdaresi, geç başvuru veya vergi yükümlülüklerinin ödenmemesi durumunda katı yaptırımlar uygulamaktadır. Cezalar küçük işletmelerin bütçelerini önemli ölçüde zorlayabilir.',
        items: [
          'Geç KDV beyannamesi: Tüzel kişiler için 250 ila 1.000 EUR (MKD karşılığı) para cezası.',
          'Geç vergi ödemesi: Ödenmemiş tutar üzerinden günlük %0,03 (yıllık yaklaşık %11) faiz.',
          'Yıllık hesapların verilmemesi: 500 ila 2.000 EUR para cezası ve Merkez Sicilden silinme olasılığı.',
          'MPIN verilmemesi: 250 ila 1.000 EUR para cezası artı ödenmemiş katkılar üzerinden faiz.',
          'Tekrarlanan ihlaller: Ticari faaliyet yasağı ve şirket banka hesapları aracılığıyla zorla tahsilat olasılığı.',
        ],
        steps: null,
      },
      {
        title: 'Facturino her son tarihe uymanıza nasıl yardımcı olur',
        content: 'Facturino özellikle Makedonya vergi sistemi için tasarlanmıştır. Yükümlülüklerinizi otomatik olarak takip eder ve her son tarihten önce sizi bilgilendirir. Tarihleri hatırlamanıza gerek yok — sistem sizin için çalışır.',
        items: [
          'Düzenlenen ve alınan faturalara dayalı otomatik KDV hesaplamaları.',
          'Bordro listesinden doğrudan MPIN beyannamesi oluşturma.',
          'Her son tarihten 7 gün ve 2 gün önce e-posta ve uygulama içi hatırlatmalar.',
          'UJP e-Danoci portalı ile uyumlu veri dışa aktarımı.',
          'Denetim amaçlı sunulan belgelerin tam geçmişi.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, tarihler ve hesaplama' },
      { slug: 'godishna-smetka-2025', title: 'Yıllık hesaplar 2025: CRMS dosyalama rehberi' },
    ],
    cta: {
      title: 'Bir daha asla son tarihi kaçırmayın',
      desc: 'Facturino tüm vergi son tarihleri hakkında sizi otomatik olarak bilgilendirir. Ücretsiz başlayın ve cezaları unutun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function RokoviUjp2026Page({
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
