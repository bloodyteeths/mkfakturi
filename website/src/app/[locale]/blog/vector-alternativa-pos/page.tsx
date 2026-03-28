import Image from 'next/image'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/vector-alternativa-pos', {
    title: {
      mk: 'Премин од Vector на Facturino POS: водич чекор по чекор | Facturino',
      sq: 'Kalimi nga Vector ne Facturino POS: hap pas hapi | Facturino',
      tr: 'Vector\'den Facturino POS\'a gecis: adim adim rehber | Facturino',
      en: 'Switching from Vector to Facturino POS: Step by Step Guide | Facturino',
    },
    description: {
      mk: 'Како да преминете од Vector POS на Facturino за 30 минути. Увоз на артикли, поврзување на фискален печатач и автоматско книжење \u2014 без техничар, без губење на податоци.',
      sq: 'Si te kaloni nga Vector POS ne Facturino per 30 minuta. Import i artikujve, lidhje e printerit fiskal dhe regjistrim automatik \u2014 pa teknik, pa humbje te dhenash.',
      tr: 'Vector POS\'tan Facturino\'ya 30 dakikada nasil gecerilir. Urun aktarimi, fiskal yazici baglantisi ve otomatik muhasebe \u2014 teknisyen ve veri kaybi olmadan.',
      en: 'How to switch from Vector POS to Facturino in 30 minutes. Import items, connect fiscal printer and auto-post accounting \u2014 no technician, no data loss.',
    },
  })
}

const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Водич',
    title: 'Премин од Vector на Facturino POS: чекор по чекор',
    publishDate: '28 март 2026',
    readTime: '7 мин читање',
    intro:
      'Ако користите Duna Vector и размислувате за промена, овој водич е за вас. Ќе ви покажеме како за 30 минути можете да ги преместите артиклите, да го поврзете фискалниот печатач и да почнете да продавате \u2014 со автоматско книжење, залиха и е-Фактура подготовка. Без техничар.',
    sections: [
      {
        title: 'Зошто да преминете од Vector?',
        content: 'Vector е добар софтвер, но е дизајниран за ера кога сметководството и POS-от беа два одделни света. Во 2026, со е-Фактура на хоризонтот, потребен ви е POS што:',
        items: [
          'Работи на таблет и телефон \u2014 не само Windows',
          'Автоматски книжи секоја продажба во IFRS',
          'Ажурира залиха во реално време',
          'Се поврзува со фискален печатач без COM порт',
          'Е подготвен за е-Фактура (октомври 2026)',
          'Е достапен од секаде \u2014 cloud',
          'Вашиот сметководител гледа се во реално време',
        ],
        steps: null,
      },
      {
        title: 'Чекор 1: Извезете артикли од Vector',
        content: null,
        items: null,
        steps: [
          { step: 'Отворете Vector', desc: 'Одете во Артикли \u2192 Листа на артикли.' },
          { step: 'Извезете во CSV', desc: 'Vector дозволува извоз на артикли во Excel/CSV формат. Извезете ги сите артикли со: Име, Шифра/Баркод, Цена, ДДВ група, Категорија.' },
          { step: 'Зачувајте го фајлот', desc: 'Зачувајте го CSV фајлот на десктоп \u2014 ќе ви треба во следниот чекор.' },
        ],
      },
      {
        title: 'Чекор 2: Регистрирајте се на Facturino',
        content: null,
        items: null,
        steps: [
          { step: 'Отворете app.facturino.mk', desc: 'Регистрирајте се бесплатно \u2014 само мејл и лозинка. Без кредитна картичка.' },
          { step: 'Внесете податоци за фирмата', desc: 'Име, ЕМБС, ДДВ број, адреса. Системот автоматски ги повлекува од ЦРРМ.' },
          { step: 'Изберете бесплатен план', desc: '30 POS продажби месечно \u2014 доволно да го тестирате комплетно.' },
        ],
      },
      {
        title: 'Чекор 3: Увезете ги артиклите',
        content: null,
        items: null,
        steps: [
          { step: 'Одете во Артикли \u2192 Увоз', desc: 'Кликнете на \u201CУвези од CSV\u201D.' },
          { step: 'Прикачете го CSV фајлот', desc: 'Системот автоматски ги мапира колоните: име, баркод, цена, ДДВ.' },
          { step: 'Потврдете и зачувајте', desc: 'Прегледајте ги артиклите и кликнете Зачувај. Сите артикли се внесени.' },
        ],
      },
      {
        title: 'Чекор 4: Поврзете фискален печатач',
        content: null,
        items: null,
        steps: [
          { step: 'Приклучете USB', desc: 'Приклучете го истиот фискален печатач на USB (не COM порт).' },
          { step: 'Одете во Поставки \u2192 Фискални уреди', desc: 'Кликнете \u201CПоврзи уред\u201D \u2014 Chrome ќе го покаже печатачот.' },
          { step: 'Изберете го уредот', desc: 'Готово! Фискалниот печатач е поврзан без драјвери.' },
        ],
      },
      {
        title: 'Чекор 5: Продавајте!',
        content: 'Отворете POS (/admin/pos), скенирајте артикл или изберете од листата, притиснете \u201CНаплати\u201D. Фактура, залиха, книжење и фискална сметка \u2014 автоматски. Вашиот сметководител веднаш ги гледа продажбите во бруто билансот.',
        items: null,
        steps: null,
      },
      {
        title: 'Што добивате со Facturino а го немате во Vector?',
        content: null,
        items: [
          'Автоматско IFRS книжење на секоја продажба (приход + ДДВ + наплата)',
          'Залиха што се ажурира автоматски на секоја продажба',
          'Cloud пристап \u2014 продавајте од таблет, проверете залиха од дома',
          'Фискален печатач преку Chrome WebSerial \u2014 без COM порт и драјвери',
          'е-Фактура подготвеност за октомври 2026',
          'AI финансиски советник \u2014 прашајте \u201CКолку продадов денес?\u201D',
          'Мониторинг на фискални измами',
          'Сметководител dashboard со реално-времен пристап',
          '4 јазици: македонски, албански, турски, англиски',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Најдобар POS софтвер за Македонија 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Како да поврзете фискален печатач во Chrome' },
      { slug: 'sto-e-e-faktura', title: 'Што е е-Фактура и зошто е задолжителна?' },
    ],
    cta: {
      title: 'Преминете од Vector за 30 минути',
      desc: 'Бесплатно, без техничар, без губење на артикли. Отворете Chrome и започнете.',
      button: 'Започни бесп��атно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Guide',
    title: 'Switching from Vector to Facturino POS: Step by Step',
    publishDate: '28 March 2026',
    readTime: '7 min read',
    intro:
      'If you use Duna Vector and are considering a switch, this guide is for you. We\'ll show you how in 30 minutes you can move your items, connect the fiscal printer and start selling \u2014 with automatic accounting, inventory and e-Invoice readiness. No technician.',
    sections: [
      { title: 'Why switch from Vector?', content: 'Vector is good software, but it was designed for an era when accounting and POS were two separate worlds. In 2026, with e-Invoice on the horizon, you need a POS that:', items: ['Works on tablet and phone \u2014 not just Windows', 'Auto-posts every sale to IFRS', 'Updates inventory in real time', 'Connects to fiscal printer without COM port', 'Is ready for e-Invoice (October 2026)', 'Is accessible from anywhere \u2014 cloud', 'Your accountant sees everything in real time'], steps: null },
      { title: 'Step 1: Export items from Vector', content: null, items: null, steps: [{ step: 'Open Vector', desc: 'Go to Items \u2192 Item List.' }, { step: 'Export to CSV', desc: 'Vector allows exporting items to Excel/CSV. Export all items with: Name, Code/Barcode, Price, VAT group, Category.' }, { step: 'Save the file', desc: 'Save the CSV file to desktop \u2014 you\'ll need it in the next step.' }] },
      { title: 'Step 2: Register on Facturino', content: null, items: null, steps: [{ step: 'Open app.facturino.mk', desc: 'Register free \u2014 just email and password. No credit card.' }, { step: 'Enter company details', desc: 'Name, company ID, VAT number, address. The system auto-pulls from the company registry.' }, { step: 'Choose free plan', desc: '30 POS sales per month \u2014 enough to test completely.' }] },
      { title: 'Step 3: Import items', content: null, items: null, steps: [{ step: 'Go to Items \u2192 Import', desc: 'Click "Import from CSV".' }, { step: 'Upload the CSV file', desc: 'The system auto-maps columns: name, barcode, price, VAT.' }, { step: 'Confirm and save', desc: 'Review items and click Save. All items are imported.' }] },
      { title: 'Step 4: Connect fiscal printer', content: null, items: null, steps: [{ step: 'Plug USB', desc: 'Connect the same fiscal printer via USB (not COM port).' }, { step: 'Go to Settings \u2192 Fiscal Devices', desc: 'Click "Connect Device" \u2014 Chrome will show the printer.' }, { step: 'Select the device', desc: 'Done! Fiscal printer connected without drivers.' }] },
      { title: 'Step 5: Start selling!', content: 'Open POS (/admin/pos), scan an item or select from the list, tap "Charge". Invoice, inventory, accounting and fiscal receipt \u2014 automatic. Your accountant immediately sees sales in the trial balance.', items: null, steps: null },
      { title: 'What you get with Facturino that Vector doesn\'t have', content: null, items: ['Automatic IFRS posting on every sale (revenue + VAT + payment)', 'Inventory that updates automatically on every sale', 'Cloud access \u2014 sell from tablet, check stock from home', 'Fiscal printer via Chrome WebSerial \u2014 no COM port or drivers', 'e-Invoice readiness for October 2026', 'AI financial advisor \u2014 ask "How much did I sell today?"', 'Fiscal fraud monitoring', 'Accountant dashboard with real-time access', '4 languages: Macedonian, Albanian, Turkish, English'], steps: null },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Best POS Software for Macedonia 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'How to connect fiscal printer in Chrome' },
      { slug: 'sto-e-e-faktura', title: 'What is e-Invoice and why is it mandatory?' },
    ],
    cta: { title: 'Switch from Vector in 30 minutes', desc: 'Free, no technician, no item loss. Open Chrome and start.', button: 'Start free' },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Udhezues',
    title: 'Kalimi nga Vector ne Facturino POS: hap pas hapi',
    publishDate: '28 mars 2026',
    readTime: '7 min lexim',
    intro: 'Nese perdorni Duna Vector dhe po mendoni per ndryshim, ky udhezues eshte per ju. Do t\'ju tregojme si per 30 minuta mund te levizni artikujt, te lidhni printerin fiskal dhe te filloni te shisni \u2014 me regjistrim automatik dhe stok.',
    sections: [
      { title: 'Pse te kaloni nga Vector?', content: 'Vector eshte softuer i mire, por eshte dizajnuar per nje epoke ku kontabiliteti dhe POS ishin dy bote te ndara. Ne 2026, me e-Fature ne horizont, ju nevojitet POS qe:', items: ['Punon ne tablet dhe telefon \u2014 jo vetem Windows', 'Regjistron automatikisht cdo shitje ne IFRS', 'Azhurnon stokun ne kohe reale', 'Lidhet me printer fiskal pa COM port', 'Eshte gati per e-Fature (Tetor 2026)', 'Qasje nga kudo \u2014 cloud', 'Kontabilisti juaj sheh gjithcka ne kohe reale'], steps: null },
      { title: 'Hapi 1: Eksportoni artikujt nga Vector', content: null, items: null, steps: [{ step: 'Hapni Vector', desc: 'Shkoni te Artikuj \u2192 Lista e artikujve.' }, { step: 'Eksportoni ne CSV', desc: 'Vector lejon eksportimin ne Excel/CSV. Eksportoni te gjitha me: Emer, Kod/Barkod, Cmim, Grupe TVSH, Kategori.' }, { step: 'Ruajeni fajllin', desc: 'Ruajeni CSV ne desktop \u2014 do t\'ju nevojitet ne hapin tjeter.' }] },
      { title: 'Hapi 2: Regjistrohuni ne Facturino', content: null, items: null, steps: [{ step: 'Hapni app.facturino.mk', desc: 'Regjistrohuni falas \u2014 vetem email dhe fjalekalim.' }, { step: 'Vendosni te dhenat e kompanise', desc: 'Emer, EMBS, numer TVSH, adrese.' }, { step: 'Zgjidhni planin falas', desc: '30 shitje POS ne muaj \u2014 mjaftueshem per testim.' }] },
      { title: 'Hapi 3: Importoni artikujt', content: null, items: null, steps: [{ step: 'Shkoni te Artikuj \u2192 Import', desc: 'Klikoni "Import nga CSV".' }, { step: 'Ngarkoni fajllin CSV', desc: 'Sistemi i mapon automatikisht kolonat.' }, { step: 'Konfirmoni dhe ruani', desc: 'Rishikoni artikujt dhe klikoni Ruaj.' }] },
      { title: 'Hapi 4: Lidhni printerin fiskal', content: null, items: null, steps: [{ step: 'Futni USB', desc: 'Lidhni te njejtin printer fiskal me USB.' }, { step: 'Shkoni te Cilesimet \u2192 Pajisje Fiskale', desc: 'Klikoni "Lidh Pajisjen".' }, { step: 'Zgjidhni pajisjen', desc: 'Gati! Printeri fiskal i lidhur pa driver.' }] },
      { title: 'Hapi 5: Filloni te shisni!', content: 'Hapni POS (/admin/pos), skanoni artikull ose zgjidhni nga lista, shtypni "Arketoni". Fature, stok, regjistrim dhe kupon fiskal \u2014 automatik.', items: null, steps: null },
      { title: 'Cfare merrni me Facturino qe Vector nuk e ka', content: null, items: ['Regjistrim automatik IFRS ne cdo shitje', 'Stok qe azhurnohet automatikisht', 'Qasje cloud \u2014 shisni nga tableti', 'Printer fiskal me Chrome WebSerial \u2014 pa COM port', 'Gatishmeri per e-Fature (Tetor 2026)', 'AI keshilltar financiar', 'Monitorim i mashtrimit fiskal', 'Dashboard per kontabilistin', '4 gjuhe: maqedonisht, shqip, turqisht, anglisht'], steps: null },
    ],
    relatedTitle: 'Artikuj te lidhur',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Softueri me i mire POS per Maqedoni 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Si te lidhni printer fiskal ne Chrome' },
      { slug: 'sto-e-e-faktura', title: 'Cfare eshte e-Fatura dhe pse eshte e detyrueshme?' },
    ],
    cta: { title: 'Kaloni nga Vector per 30 minuta', desc: 'Falas, pa teknik, pa humbje artikujsh.', button: 'Fillo falas' },
  },
  tr: {
    backLink: '\u2190 Bloga don',
    tag: 'Rehber',
    title: 'Vector\'den Facturino POS\'a gecis: adim adim',
    publishDate: '28 Mart 2026',
    readTime: '7 dk okuma',
    intro: 'Duna Vector kullaniyorsaniz ve degisiklik dusunuyorsaniz, bu rehber sizin icin. 30 dakikada urunleri nasil tasiyacaginizi, fiskal yaziciyi baglayacaginizi ve satisa baslayacaginizi gosterecegiz.',
    sections: [
      { title: 'Neden Vector\'den gecmeli?', content: 'Vector iyi bir yazilim, ama muhasebe ile POS\'un iki ayri dunya oldugu bir donem icin tasarlandi. 2026\'da e-Fatura ile:', items: ['Tablette ve telefonda calisir \u2014 sadece Windows degil', 'Her satisi otomatik IFRS\'ye kaydeder', 'Stoku anlik gunceller', 'COM port olmadan fiskal yaziciya baglanir', 'e-Fatura icin hazir (Ekim 2026)', 'Her yerden erisilebilir \u2014 cloud', 'Muhasebeci her seyi anlik gorur'], steps: null },
      { title: 'Adim 1: Vector\'den urunleri aktarin', content: null, items: null, steps: [{ step: 'Vector\'u acin', desc: 'Urunler \u2192 Urun Listesi\'ne gidin.' }, { step: 'CSV\'ye aktarin', desc: 'Tum urunleri Excel/CSV olarak aktarin: Ad, Kod/Barkod, Fiyat, KDV grubu, Kategori.' }, { step: 'Dosyayi kaydedin', desc: 'CSV dosyasini masaustune kaydedin.' }] },
      { title: 'Adim 2: Facturino\'ya kaydolun', content: null, items: null, steps: [{ step: 'app.facturino.mk\'yi acin', desc: 'Ucretsiz kaydolun \u2014 sadece e-posta ve sifre.' }, { step: 'Sirket bilgilerini girin', desc: 'Ad, EMBS, KDV numarasi, adres.' }, { step: 'Ucretsiz plani secin', desc: 'Ayda 30 POS satisi \u2014 tam test icin yeterli.' }] },
      { title: 'Adim 3: Urunleri aktarin', content: null, items: null, steps: [{ step: 'Urunler \u2192 Aktar\'a gidin', desc: '"CSV\'den aktar"a tiklayin.' }, { step: 'CSV dosyasini yukleyin', desc: 'Sistem sutunlari otomatik esler.' }, { step: 'Onaylayin ve kaydedin', desc: 'Urunleri inceleyin ve Kaydet\'e tiklayin.' }] },
      { title: 'Adim 4: Fiskal yaziciyi baglayin', content: null, items: null, steps: [{ step: 'USB\'ye takin', desc: 'Ayni fiskal yaziciyi USB ile baglayin.' }, { step: 'Ayarlar \u2192 Fiskal Cihazlar\'a gidin', desc: '"Cihaz Bagla"ya tiklayin.' }, { step: 'Cihazi secin', desc: 'Tamam! Fiskal yazici surucusuz baglandi.' }] },
      { title: 'Adim 5: Satisa baslayin!', content: 'POS\'u acin (/admin/pos), urun tarayin veya listeden secin, "Tahsil Et"e basin. Fatura, stok, muhasebe ve fiskal fis \u2014 otomatik.', items: null, steps: null },
      { title: 'Facturino ile ne kazanirsiniz?', content: null, items: ['Her satista otomatik IFRS kaydi', 'Otomatik stok guncelleme', 'Cloud erisim \u2014 tabletten satin, evden stok kontrol edin', 'Chrome WebSerial ile fiskal yazici \u2014 COM port yok', 'Ekim 2026 e-Fatura hazirligi', 'AI finansal danisma', 'Fiskal dolandiricilik izleme', 'Muhasebeci paneli', '4 dil: Makedonca, Arnavutca, Turkce, Ingilizce'], steps: null },
    ],
    relatedTitle: 'Ilgili yazilar',
    related: [
      { slug: 'pos-softver-makedonija', title: 'Makedonya\'da en iyi POS yazilimi 2026' },
      { slug: 'fiskalen-pecatac-chrome', title: 'Chrome\'da fiskal yazici nasil baglanir' },
      { slug: 'sto-e-e-faktura', title: 'e-Fatura nedir ve neden zorunlu?' },
    ],
    cta: { title: 'Vector\'den 30 dakikada gecin', desc: 'Ucretsiz, teknisyen yok, urun kaybi yok.', button: 'Ucretsiz basla' },
  },
} as const

export default async function VectorAlternativaPos({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
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

      <section className="pb-8">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="rounded-2xl overflow-hidden shadow-xl border border-gray-100">
            <Image src="/images/pos/bakery-scanning.png" alt="Shop owner using Facturino POS as Vector alternative" width={800} height={500} className="w-full h-auto" />
          </div>
        </div>
      </section>

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

      <section className="py-12 md:py-16 bg-gray-50">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <h2 className="text-2xl font-bold text-gray-900 mb-6">{t.relatedTitle}</h2>
          <div className="grid gap-4">
            {t.related.map((r) => (
              <Link key={r.slug} href={`/${locale}/blog/${r.slug}`} className="group flex items-center justify-between bg-white rounded-xl border border-gray-100 px-6 py-4 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all">
                <span className="text-gray-900 font-medium group-hover:text-indigo-600 transition-colors">{r.title}</span>
                <svg className="w-5 h-5 text-gray-400 group-hover:text-indigo-600 flex-shrink-0 ml-4 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}><path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" /></svg>
              </Link>
            ))}
          </div>
        </div>
      </section>

      <section className="section relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
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
