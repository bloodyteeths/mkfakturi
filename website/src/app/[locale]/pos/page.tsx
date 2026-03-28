import Image from 'next/image'
import Link from 'next/link'
import PageHero from '@/components/PageHero'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/pos', {
    title: {
      mk: 'POS софтвер Македонија \u2014 бесплатна каса со фискален печатач | Facturino',
      sq: 'POS sistem Maqedoni \u2014 arke falas me printer fiskal | Facturino',
      tr: 'POS yazilimi Makedonya \u2014 ucretsiz kasa fiskal yazici ile | Facturino',
      en: 'POS Software Macedonia \u2014 Free Register with Fiscal Printer | Facturino',
    },
    description: {
      mk: 'Бесплатен POS софтвер за малопродажба и угостителство во Македонија. Фискален печатач без драјвери (WebSerial), автоматско книжење, залиха и е-Фактура. Алтернатива за Vector, Accent, Jongis, PANTHEON \u2014 работи на таблет и компјутер без инсталација.',
      sq: 'POS falas per shitje me pakice dhe hoteleri ne Maqedoni. Printer fiskal pa driver (WebSerial), regjistrim automatik, stok dhe e-Fature. Alternativ per Vector, Accent, Jongis, PANTHEON \u2014 punon ne tablet dhe kompjuter pa instalim.',
      tr: 'Makedonya\'da perakende ve restoran icin ucretsiz POS yazilimi. Surucusuz fiskal yazici (WebSerial), otomatik muhasebe, stok ve e-Fatura. Vector, Accent, Jongis, PANTHEON alternatifi \u2014 tablette ve bilgisayarda kurulum gerektirmez.',
      en: 'Free POS software for retail and hospitality in Macedonia. Driverless fiscal printer (WebSerial), automatic IFRS accounting, real-time inventory and e-Invoice. Alternative to Vector, Accent, Jongis, PANTHEON \u2014 runs on tablet and desktop, no installation.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – all 4 locales                                       */
/* ------------------------------------------------------------------ */

const copy = {
  mk: {
    hero: {
      title: 'POS каса што книжи, фискализира и одзема залиха — автоматски',
      subtitle:
        'Отворете Chrome. Скенирајте. Наплатете. Фактура, залиха, книговодство и фискална сметка — со еден клик. Бесплатно.',
      cta: 'Започни бесплатно',
      badge: 'Бесплатен POS',
    },
    painPoints: {
      title: 'Ви е познато ова?',
      subtitle: 'Секој мал бизнис во Македонија се бори со истите проблеми',
      items: [
        {
          icon: '\u{1F4B8}',
          title: 'Десктоп POS чини 300\u20AC + техничар',
          desc: 'Плаќате 300\u20AC за десктоп програм, па уште 50\u20AC за инсталација. А работи само на Windows.',
        },
        {
          icon: '\u{1F5A5}',
          title: 'Само на еден компјутер',
          desc: 'Ако ви се расипе компјутерот, губите се. Нема cloud, нема бекап, нема пристап од друг уред.',
        },
        {
          icon: '\u{1F4C3}',
          title: 'Рачно внесување во книговодство',
          desc: 'Сметководителот ги препишува Z-извештаите рачно. Секој ден, секоја ставка. Во 2026.',
        },
        {
          icon: '\u{1F4E6}',
          title: 'Залиха во Excel',
          desc: 'Продавате артикл на каса, но залихата не се ажурира. Дознавате дека ви нема стока кога купувачот стои пред вас.',
        },
        {
          icon: '\u{1F50C}',
          title: 'Фискален печатач — мачење',
          desc: 'COM порт, драјвери, Windows-only DLL. Секоја промена на компјутер = повик до техничар.',
        },
        {
          icon: '\u{26A0}',
          title: 'е-Фактура доаѓа',
          desc: 'Од Октомври 2026 е-Фактура е задолжителна. Вашиот тековен POS не е подготвен. А Facturino е.',
        },
      ],
    },
    solution: {
      title: 'Facturino POS: еден клик = пет акции',
      subtitle: 'Касиерот притиска \u201CНаплати\u201D \u2014 системот автоматски:',
      steps: [
        { num: '1', title: 'Креира фактура', desc: 'Со ставки, ДДВ и број на фактура' },
        { num: '2', title: 'Одзема залиха', desc: 'Автоматски од избраниот магацин' },
        { num: '3', title: 'Книжи во IFRS', desc: 'Приход, ДДВ и наплата \u2014 автоматски' },
        { num: '4', title: 'Прима плаќање', desc: 'Готовина или картичка, со кусур' },
        { num: '5', title: 'Печати фискална', desc: 'WebSerial \u2014 директно од Chrome' },
      ],
    },
    features: [
      {
        icon: '\u{1F4F1}',
        badge: 'Без инсталација',
        title: 'Работи на секој уред',
        subtitle: 'Отворете Chrome на компјутер, таблет или телефон. Нема инсталација, нема Windows. Работи и на Android таблет од 5,000 МКД.',
        items: [
          { title: 'Полноекрански интерфејс', desc: 'Touch-оптимизиран за екрани од 7 до 27 инчи' },
          { title: 'Баркод скенер', desc: 'USB или Bluetooth скенер \u2014 plug & play' },
          { title: 'Работи офлајн', desc: 'PWA \u2014 продолжете да продавате и без интернет' },
          { title: 'Тастатурни кратенки', desc: 'F1=пребарај, F2=наплати, F3=бриши, Esc=откажи' },
        ],
      },
      {
        icon: '\u{1F4B3}',
        badge: 'Автоматско книжење',
        title: 'Без рачно препишување',
        subtitle: 'Секоја продажба автоматски се книжи: приход, ДДВ (18%/5%/10%), наплата. Сметководителот гледа се во реално време.',
        items: [
          { title: 'IFRS автоматско книжење', desc: 'Секоја фактура \u2192 дневник, главна книга, бруто биланс' },
          { title: 'ДДВ автоматски', desc: '18%, 5%, 10% или ослободено \u2014 по артикл' },
          { title: 'Сметководител dashboard', desc: 'Вашиот сметководител има свој пристап \u2014 гледа се, не менува ништо' },
          { title: 'Готово за е-Фактура', desc: 'Кога УЈП ќе го отвори API-от, вие сте подготвени' },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'Залиха во реално време',
        title: 'Никогаш повеќе \u201CНемаме\u201D',
        subtitle: 'Продадовте артикл? Залихата веднаш се ажурира. Добивте стока? Скенирајте приемница. Се е поврзано.',
        items: [
          { title: 'Автоматско одземање', desc: 'Секоја POS продажба ја намалува залихата' },
          { title: 'Предупредување за ниска залиха', desc: 'Добивте известување кога артикл паѓа под минимум' },
          { title: 'Повеќе магацини', desc: 'Продавница, магацин, филијала \u2014 секој со своја залиха' },
          { title: 'WAC пресметка', desc: 'Автоматска пондерирана просечна цена по секоја набавка' },
        ],
      },
      {
        icon: '\u{1F5A8}',
        badge: 'WebSerial',
        title: 'Фискален печатач \u2014 без драјвери',
        subtitle: 'Поврзете го фискалниот печатач преку USB. Chrome го препознава директно \u2014 без COM порт, без DLL, без техничар.',
        items: [
          { title: 'ISL протокол', desc: 'Поддржува Datecs, Tremol, Daisy и други ISL печатачи' },
          { title: 'Plug & play', desc: 'Приклучете USB \u2192 Chrome прашува \u2192 готово' },
          { title: 'ДДВ групи А/Б/В/Г', desc: 'Автоматско мапирање: А=18%, Б=5%, В=10%, Г=0%' },
          { title: 'Мониторинг на измами', desc: 'AI го следи секој фискален настан \u2014 алармира за сомнителни активности' },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: 'Смени и извештаи',
        title: 'Контрола на касиери',
        subtitle: 'Отвори смена, затвори смена \u2014 со почетна и крајна каса. Секоја продажба е врзана за касиер.',
        items: [
          { title: 'Отвори/затвори смена', desc: 'Внесете почетна каса, на крај видете разлика' },
          { title: 'Z-извештај', desc: 'Вкупна продажба, поврати, готовина vs. картичка' },
          { title: 'По касиер', desc: 'Секој касиер \u2014 своја статистика и одговорност' },
          { title: 'Паркирај продажба', desc: 'Паркирајте ја продажбата, послужете друг купувач, вратете се подоцна' },
        ],
      },
    ],
    comparison: {
      title: 'Facturino POS vs. конкуренција',
      headers: ['Функција', 'Facturino', 'Десктоп POS (~300\u20AC)', 'Локален POS (~200\u20AC)', 'ERP системи (500+\u20AC/год)'],
      rows: [
        ['Цена', 'Бесплатно*', '300\u20AC + техничар', '~200\u20AC', '500-1,200\u20AC/год'],
        ['Работи на таблет', '\u2705 Било кој уред', '\u274C Само Windows', '\u274C Windows', '\u274C Windows'],
        ['Cloud пристап', '\u2705 Секаде', '\u274C Локално', '\u274C Локално', '\u2705 Опционо'],
        ['Автоматско книжење', '\u2705 IFRS', '\u274C Рачно', '\u274C Рачно', '\u2705 Интегрирано'],
        ['Залиха на продажба', '\u2705 Автоматски', '\u274C Рачно', '\u2705 Делумно', '\u2705 Да'],
        ['Фискален печатач', '\u2705 WebSerial', '\u2705 COM порт', '\u2705 COM порт', '\u2705 COM порт'],
        ['е-Фактура подготвен', '\u2705 Да', '\u274C Не', '\u274C Не', '\u274C Не'],
        ['AI советник', '\u2705 Да', '\u274C Не', '\u274C Не', '\u274C Не'],
        ['Инсталација', 'Отвори Chrome', 'Техничар', 'Техничар', 'Техничар + сервер'],
      ],
      footnote: '*Бесплатно до 30 продажби/месец. Starter: 12\u20AC за 60, Standard: 39\u20AC за 500.',
    },
    faq: {
      title: 'Најчести прашања',
      items: [
        {
          q: 'Дали навистина е бесплатно?',
          a: 'Да. Бесплатниот план дозволува 30 POS продажби месечно \u2014 доволно да го тестирате. Без кредитна картичка, без договор.',
        },
        {
          q: 'Кои фискални печатачи се поддржани?',
          a: 'Сите ISL-компатибилни печатачи: Datecs, Tremol, Daisy. Поврзувате преку USB \u2014 Chrome го препознава директно, без драјвери.',
        },
        {
          q: 'Дали работи без интернет?',
          a: 'POS интерфејсот работи офлајн (PWA). Продажбите се синхронизираат кога ќе се врати конекцијата. Фискалниот печатач работи локално.',
        },
        {
          q: 'Дали можам да го користам на таблет?',
          a: 'Да! Отворете Chrome на Android или iPad таблет. Touch-оптимизиран интерфејс со големи копчиња. Android таблет од 5,000 МКД е доволен.',
        },
        {
          q: 'Што ако веќе имам друг POS?',
          a: 'Можете да ги увезете артиклите и категориите преку CSV. Нема потреба од техничар \u2014 отворете Facturino, внесете ги артиклите и започнете.',
        },
        {
          q: 'Дали мојот сметководител има пристап?',
          a: 'Да. Facturino е единствениот POS каде сметководителот гледа се во реално време \u2014 продажби, ДДВ, залиха, бруто биланс. Без препишување.',
        },
        {
          q: 'Колку чини е-Фактура?',
          a: 'е-Фактура е вклучена во сите планови. Кога УЈП ќе го отвори API-от, вашите фактури автоматски ќе се испраќаат.',
        },
      ],
    },
    bottomCta: {
      title: 'Заменете го вашиот POS за 2 минути',
      sub: 'Отворете Chrome. Внесете артикли. Скенирајте. Наплатете. Без инсталација, без техничар, без чекање.',
      button: 'Започни бесплатно',
    },
  },
  sq: {
    hero: {
      title: 'POS arke qe regjistron, fiskalizon dhe zbret stokun \u2014 automatikisht',
      subtitle:
        'Hapni Chrome. Skanoni. Arkëtoni. Fatura, stoku, kontabiliteti dhe kuponi fiskal \u2014 me nje klik. Falas.',
      cta: 'Fillo falas',
      badge: 'POS Falas',
    },
    painPoints: {
      title: 'Ju duket e njohur kjo?',
      subtitle: 'Cdo biznes i vogel ne Maqedoni lufton me te njejtat probleme',
      items: [
        {
          icon: '\u{1F4B8}',
          title: 'POS desktop kushton 300\u20AC + teknik',
          desc: 'Paguani 300\u20AC per program desktop, plus 50\u20AC per instalim. Dhe punon vetem ne Windows.',
        },
        {
          icon: '\u{1F5A5}',
          title: 'Vetem ne nje kompjuter',
          desc: 'Nese ju prishet kompjuteri, humbni gjithcka. Pa cloud, pa backup, pa qasje nga pajisje tjeter.',
        },
        {
          icon: '\u{1F4C3}',
          title: 'Regjistrim manual ne kontabilitet',
          desc: 'Kontabilisti i kopjon Z-raportet me dore. Cdo dite, cdo ze. Ne vitin 2026.',
        },
        {
          icon: '\u{1F4E6}',
          title: 'Stoku ne Excel',
          desc: 'Shisni artikull ne arke, por stoku nuk azhurnohet. E merrni vesh se nuk keni mall kur bleresi qendron para jush.',
        },
        {
          icon: '\u{1F50C}',
          title: 'Printer fiskal \u2014 mundim',
          desc: 'COM port, driver, DLL vetem per Windows. Cdo ndryshim kompjuteri = thirrje teknikut.',
        },
        {
          icon: '\u{26A0}',
          title: 'e-Fatura po vjen',
          desc: 'Nga Tetori 2026 e-Fatura eshte e detyrueshme. POS-i juaj aktual nuk eshte gati. Facturino eshte.',
        },
      ],
    },
    solution: {
      title: 'Facturino POS: nje klik = pese veprime',
      subtitle: 'Arkëtari shtyp \u201CArk\u00ebtoni\u201D \u2014 sistemi automatikisht:',
      steps: [
        { num: '1', title: 'Krijon fature', desc: 'Me ze, TVSH dhe numer fature' },
        { num: '2', title: 'Zbret stokun', desc: 'Automatikisht nga magazina e zgjedhur' },
        { num: '3', title: 'Regjistron ne IFRS', desc: 'Te ardhura, TVSH dhe pagesë \u2014 automatikisht' },
        { num: '4', title: 'Pranon pagese', desc: 'Para ne dore ose kartele, me kusur' },
        { num: '5', title: 'Printon fiskalin', desc: 'WebSerial \u2014 direkt nga Chrome' },
      ],
    },
    features: [
      {
        icon: '\u{1F4F1}',
        badge: 'Pa instalim',
        title: 'Punon ne cdo pajisje',
        subtitle: 'Hapni Chrome ne kompjuter, tablet ose telefon. Pa instalim, pa Windows. Punon edhe ne tablet Android prej 5,000 MKD.',
        items: [
          { title: 'Ekran i plote', desc: 'Optimizuar per ekrane nga 7 deri 27 inch' },
          { title: 'Skaner barkodi', desc: 'USB ose Bluetooth \u2014 plug & play' },
          { title: 'Punon offline', desc: 'PWA \u2014 vazhdoni te shisni edhe pa internet' },
          { title: 'Shkurtore tastiere', desc: 'F1=kerko, F2=arketoni, F3=fshij, Esc=anulo' },
        ],
      },
      {
        icon: '\u{1F4B3}',
        badge: 'Regjistrim automatik',
        title: 'Pa kopjim manual',
        subtitle: 'Cdo shitje regjistrohet automatikisht: te ardhura, TVSH (18%/5%/10%), pagese. Kontabilisti sheh gjithcka ne kohe reale.',
        items: [
          { title: 'IFRS automatik', desc: 'Cdo fature \u2192 ditar, liber kryesor, bilanc' },
          { title: 'TVSH automatik', desc: '18%, 5%, 10% ose e perliruar \u2014 per artikull' },
          { title: 'Dashboard kontabilisti', desc: 'Kontabilisti ka qasje te vet \u2014 sheh gjithcka, nuk ndryshon asgje' },
          { title: 'Gati per e-Fature', desc: 'Kur UJP te hape API-n, ju jeni gati' },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'Stok ne kohe reale',
        title: 'Kurre me \u201CNuk kemi\u201D',
        subtitle: 'Shitet artikull? Stoku azhurnohet menjehere. Pranuat mall? Skanoni pranimin. Gjithcka e lidhur.',
        items: [
          { title: 'Zbritje automatike', desc: 'Cdo shitje POS e zbret stokun' },
          { title: 'Paralajmerim per stok te ulet', desc: 'Njoftim kur artikulli bie nen minimum' },
          { title: 'Shume magazina', desc: 'Dyqan, magazine, filial \u2014 secili me stokun e vet' },
          { title: 'Llogaritje WAC', desc: 'Cmim mesatar automatik pas cdo furnizimi' },
        ],
      },
      {
        icon: '\u{1F5A8}',
        badge: 'WebSerial',
        title: 'Printer fiskal \u2014 pa driver',
        subtitle: 'Lidhni printerin fiskal me USB. Chrome e njeh direkt \u2014 pa COM port, pa DLL, pa teknik.',
        items: [
          { title: 'Protokolli ISL', desc: 'Mbeshtet Datecs, Tremol, Daisy dhe printera tjere ISL' },
          { title: 'Plug & play', desc: 'Futni USB \u2192 Chrome pyet \u2192 gati' },
          { title: 'Grupe TVSH A/B/V/G', desc: 'Mapim automatik: A=18%, B=5%, V=10%, G=0%' },
          { title: 'Monitorim mashtrimi', desc: 'AI ndjek cdo ngjarje fiskale \u2014 alarmon per aktivitete te dyshimta' },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: 'Turne dhe raporte',
        title: 'Kontroll i arkëtareve',
        subtitle: 'Hap turnin, mbyll turnin \u2014 me arke fillestare dhe perfundimtare. Cdo shitje e lidhur me arketarin.',
        items: [
          { title: 'Hap/mbyll turn', desc: 'Vendosni arken fillestare, ne fund shihni diferencen' },
          { title: 'Z-raport', desc: 'Shitje totale, kthime, para ne dore vs. kartele' },
          { title: 'Per arketar', desc: 'Cdo arketar \u2014 statistikat e veta dhe pergjegjesia' },
          { title: 'Parko shitjen', desc: 'Parkoni shitjen, sherbeni bleresit tjeter, kthehuni me vone' },
        ],
      },
    ],
    comparison: {
      title: 'Facturino POS vs. konkurrenca',
      headers: ['Vecoria', 'Facturino', 'POS Desktop (~300\u20AC)', 'POS Lokal (~200\u20AC)', 'Sisteme ERP (500+\u20AC/vit)'],
      rows: [
        ['Cmimi', 'Falas*', '300\u20AC + teknik', '~200\u20AC', '500-1,200\u20AC/vit'],
        ['Punon ne tablet', '\u2705 Cdo pajisje', '\u274C Vetem Windows', '\u274C Windows', '\u274C Windows'],
        ['Qasje cloud', '\u2705 Kudo', '\u274C Lokale', '\u274C Lokale', '\u2705 Opsionale'],
        ['Regjistrim automatik', '\u2705 IFRS', '\u274C Manual', '\u274C Manual', '\u2705 I integruar'],
        ['Stok ne shitje', '\u2705 Automatik', '\u274C Manual', '\u2705 Pjeserisht', '\u2705 Po'],
        ['Printer fiskal', '\u2705 WebSerial', '\u2705 COM port', '\u2705 COM port', '\u2705 COM port'],
        ['Gati per e-Fature', '\u2705 Po', '\u274C Jo', '\u274C Jo', '\u274C Jo'],
        ['AI keshilltar', '\u2705 Po', '\u274C Jo', '\u274C Jo', '\u274C Jo'],
        ['Instalimi', 'Hapni Chrome', 'Teknik', 'Teknik', 'Teknik + server'],
      ],
      footnote: '*Falas deri ne 30 shitje/muaj. Starter: 12\u20AC per 60, Standard: 39\u20AC per 500.',
    },
    faq: {
      title: 'Pyetjet me te shpeshta',
      items: [
        {
          q: 'A eshte vertet falas?',
          a: 'Po. Plani falas lejon 30 shitje POS ne muaj \u2014 mjaftueshem per te testuar. Pa kartele krediti, pa kontrate.',
        },
        {
          q: 'Cilat printera fiskal mbeshteten?',
          a: 'Te gjithe printerat ISL-kompatibel: Datecs, Tremol, Daisy. Lidheni me USB \u2014 Chrome e njeh direkt, pa driver.',
        },
        {
          q: 'A punon pa internet?',
          a: 'Nderfaqja POS punon offline (PWA). Shitjet sinkronizohen kur kthehet lidhja. Printeri fiskal punon lokalisht.',
        },
        {
          q: 'A mund ta perdor ne tablet?',
          a: 'Po! Hapni Chrome ne Android ose iPad. Nderfaqe touch me butona te medhenj. Tablet Android nga 5,000 MKD mjafton.',
        },
        {
          q: 'Po nese tashme kam POS tjeter?',
          a: 'Mund ti importoni artikujt dhe kategorite me CSV. Pa nevoje per teknik \u2014 hapni Facturino, vendosni artikujt dhe filloni.',
        },
        {
          q: 'A ka qasje kontabilisti?',
          a: 'Po. Facturino eshte i vetmi POS ku kontabilisti sheh gjithcka ne kohe reale \u2014 shitje, TVSH, stok, bilanc. Pa kopjim.',
        },
        {
          q: 'Sa kushton e-Fatura?',
          a: 'e-Fatura eshte e perfshire ne te gjitha planet. Kur UJP te hape API-n, faturat tuaja do te dergohen automatikisht.',
        },
      ],
    },
    bottomCta: {
      title: 'Zevendesoni POS-in tuaj per 2 minuta',
      sub: 'Hapni Chrome. Vendosni artikujt. Skanoni. Arketoni. Pa instalim, pa teknik, pa pritje.',
      button: 'Fillo falas',
    },
  },
  tr: {
    hero: {
      title: 'Otomatik kaydeden, fiskallestiren ve stok dusen POS kasasi',
      subtitle:
        'Chrome\'u acin. Tarayin. Tahsil edin. Fatura, stok, muhasebe ve fiskal fis \u2014 tek tikla. Ucretsiz.',
      cta: 'Ucretsiz basla',
      badge: 'Ucretsiz POS',
    },
    painPoints: {
      title: 'Bu size tanidik geliyor mu?',
      subtitle: 'Makedonya\'daki her kucuk isletme ayni sorunlarla mucadele ediyor',
      items: [
        {
          icon: '\u{1F4B8}',
          title: 'Masaustu POS 300\u20AC + teknisyen',
          desc: 'Masaustu program icin 300\u20AC, kurulum icin 50\u20AC daha. Ve sadece Windows\'da calisiyor.',
        },
        {
          icon: '\u{1F5A5}',
          title: 'Sadece bir bilgisayarda',
          desc: 'Bilgisayar bozulursa her seyi kaybedersiniz. Cloud yok, yedek yok, baska cihazdan erisim yok.',
        },
        {
          icon: '\u{1F4C3}',
          title: 'Muhasebeye elle giris',
          desc: 'Muhasebeci Z-raporlarini elle kopyalar. Her gun, her kalem. 2026\'da.',
        },
        {
          icon: '\u{1F4E6}',
          title: 'Stok Excel\'de',
          desc: 'Kasada urun satarsiniz ama stok guncellenmez. Musteri karsinizdayken stokta olmadigini ogrenirsiniz.',
        },
        {
          icon: '\u{1F50C}',
          title: 'Fiskal yazici \u2014 eziyet',
          desc: 'COM port, surucu, Windows DLL. Her bilgisayar degisikligi = teknisyen cagrisi.',
        },
        {
          icon: '\u{26A0}',
          title: 'e-Fatura geliyor',
          desc: 'Ekim 2026\'dan itibaren e-Fatura zorunlu. Mevcut POS\'unuz hazir degil. Facturino hazir.',
        },
      ],
    },
    solution: {
      title: 'Facturino POS: bir tik = bes islem',
      subtitle: 'Kasiyer \u201CTahsil Et\u201D\u2019e basar \u2014 sistem otomatik olarak:',
      steps: [
        { num: '1', title: 'Fatura olusturur', desc: 'Kalemler, KDV ve fatura numarasiyla' },
        { num: '2', title: 'Stok dusu yapar', desc: 'Secilen depodan otomatik' },
        { num: '3', title: 'IFRS\'ye kaydeder', desc: 'Gelir, KDV ve tahsilat \u2014 otomatik' },
        { num: '4', title: 'Odeme alir', desc: 'Nakit veya kart, ustuyle' },
        { num: '5', title: 'Fiskal fis basar', desc: 'WebSerial \u2014 dogrudan Chrome\'dan' },
      ],
    },
    features: [
      {
        icon: '\u{1F4F1}',
        badge: 'Kurulum gereksiz',
        title: 'Her cihazda calisir',
        subtitle: 'Bilgisayar, tablet veya telefonda Chrome\'u acin. Kurulum yok, Windows yok. 5.000 MKD\'lik Android tablette bile calisir.',
        items: [
          { title: 'Tam ekran arayuz', desc: '7-27 inc ekranlar icin dokunma optimizasyonlu' },
          { title: 'Barkod okuyucu', desc: 'USB veya Bluetooth \u2014 tak ve calistir' },
          { title: 'Cevrimdisi calisir', desc: 'PWA \u2014 internet olmadan da satisa devam' },
          { title: 'Klavye kisayollari', desc: 'F1=ara, F2=tahsil, F3=sil, Esc=iptal' },
        ],
      },
      {
        icon: '\u{1F4B3}',
        badge: 'Otomatik kayit',
        title: 'Elle kopyalama yok',
        subtitle: 'Her satis otomatik kaydedilir: gelir, KDV (18%/5%/10%), tahsilat. Muhasebeci her seyi anlik gorur.',
        items: [
          { title: 'IFRS otomatik kayit', desc: 'Her fatura \u2192 yevmiye, buyuk defter, mizan' },
          { title: 'KDV otomatik', desc: '18%, 5%, 10% veya muaf \u2014 urune gore' },
          { title: 'Muhasebeci paneli', desc: 'Muhasebecinin kendi erisimi \u2014 her seyi gorur, hicbir sey degistirmez' },
          { title: 'e-Fatura\'ya hazir', desc: 'UJP API\'sini actiginda siz hazirsiniz' },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'Anlik stok',
        title: 'Bir daha asla \u201CYok\u201D demek yok',
        subtitle: 'Urun sattiniz mi? Stok aninda guncellenir. Mal geldi mi? Irsaliye tarayin. Her sey bagli.',
        items: [
          { title: 'Otomatik dusme', desc: 'Her POS satisi stoku duser' },
          { title: 'Dusuk stok uyarisi', desc: 'Urun minimumun altina dustugunde bildirim' },
          { title: 'Coklu depo', desc: 'Magaza, depo, sube \u2014 her biri kendi stokuyla' },
          { title: 'WAC hesaplama', desc: 'Her alistirmadan sonra otomatik agirlikli ortalama maliyet' },
        ],
      },
      {
        icon: '\u{1F5A8}',
        badge: 'WebSerial',
        title: 'Fiskal yazici \u2014 surucusuz',
        subtitle: 'Fiskal yaziciyi USB ile baglayin. Chrome dogrudan tanir \u2014 COM port yok, DLL yok, teknisyen yok.',
        items: [
          { title: 'ISL protokolu', desc: 'Datecs, Tremol, Daisy ve diger ISL yazicilari destekler' },
          { title: 'Tak ve calistir', desc: 'USB takin \u2192 Chrome sorar \u2192 hazir' },
          { title: 'KDV gruplari A/B/V/G', desc: 'Otomatik esleme: A=18%, B=5%, V=10%, G=0%' },
          { title: 'Dolandiricilik izleme', desc: 'AI her fiskal olayi izler \u2014 suphelilerde alarm verir' },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: 'Vardiyalar ve raporlar',
        title: 'Kasiyer kontrolu',
        subtitle: 'Vardiya ac, vardiya kapat \u2014 baslangic ve bitis kasasiyla. Her satis kasiyere bagli.',
        items: [
          { title: 'Vardiya ac/kapat', desc: 'Baslangic kasasini girin, sonunda farki gorun' },
          { title: 'Z-rapor', desc: 'Toplam satis, iade, nakit vs. kart' },
          { title: 'Kasiyere gore', desc: 'Her kasiyer \u2014 kendi istatistikleri ve sorumlulugu' },
          { title: 'Satisi parkla', desc: 'Satisi parklayin, baska musteriyi karsilayin, sonra donun' },
        ],
      },
    ],
    comparison: {
      title: 'Facturino POS vs. rakipler',
      headers: ['Ozellik', 'Facturino', 'Masaustu POS (~300\u20AC)', 'Yerel POS (~200\u20AC)', 'ERP Sistemleri (500+\u20AC/yil)'],
      rows: [
        ['Fiyat', 'Ucretsiz*', '300\u20AC + teknisyen', '~200\u20AC', '500-1,200\u20AC/yil'],
        ['Tablette calisir', '\u2705 Her cihaz', '\u274C Sadece Windows', '\u274C Windows', '\u274C Windows'],
        ['Cloud erisim', '\u2705 Her yerden', '\u274C Yerel', '\u274C Yerel', '\u2705 Opsiyonel'],
        ['Otomatik kayit', '\u2705 IFRS', '\u274C Elle', '\u274C Elle', '\u2705 Entegre'],
        ['Satista stok', '\u2705 Otomatik', '\u274C Elle', '\u2705 Kismi', '\u2705 Evet'],
        ['Fiskal yazici', '\u2705 WebSerial', '\u2705 COM port', '\u2705 COM port', '\u2705 COM port'],
        ['e-Fatura hazir', '\u2705 Evet', '\u274C Hayir', '\u274C Hayir', '\u274C Hayir'],
        ['AI danisma', '\u2705 Evet', '\u274C Hayir', '\u274C Hayir', '\u274C Hayir'],
        ['Kurulum', 'Chrome acin', 'Teknisyen', 'Teknisyen', 'Teknisyen + sunucu'],
      ],
      footnote: '*30 satisa kadar ucretsiz/ay. Starter: 12\u20AC 60 satis, Standard: 39\u20AC 500 satis.',
    },
    faq: {
      title: 'Sikca sorulan sorular',
      items: [
        {
          q: 'Gercekten ucretsiz mi?',
          a: 'Evet. Ucretsiz plan ayda 30 POS satisina izin verir \u2014 test etmek icin yeterli. Kredi karti veya sozlesme gerekmez.',
        },
        {
          q: 'Hangi fiskal yazicilar destekleniyor?',
          a: 'Tum ISL uyumlu yazicilar: Datecs, Tremol, Daisy. USB ile baglayin \u2014 Chrome dogrudan tanir, surucu gerekmez.',
        },
        {
          q: 'Internet olmadan calisir mi?',
          a: 'POS arayuzu cevrimdisi calisir (PWA). Satislar baglanti gelince senkronize edilir. Fiskal yazici yerel calisir.',
        },
        {
          q: 'Tablette kullanabilir miyim?',
          a: 'Evet! Android veya iPad\'de Chrome acin. Buyuk butonlarla dokunma arayuzu. 5.000 MKD Android tablet yeterli.',
        },
        {
          q: 'Zaten baska POS varsa?',
          a: 'Urunleri ve kategorileri CSV ile aktarabilirsiniz. Teknisyen gerekmez \u2014 Facturino\'yu acin, urunleri girin ve baslayin.',
        },
        {
          q: 'Muhasebecinin erisimi var mi?',
          a: 'Evet. Facturino muhasebecinin her seyi anlik gordugu tek POS \u2014 satislar, KDV, stok, mizan. Kopyalama yok.',
        },
        {
          q: 'e-Fatura ne kadar?',
          a: 'e-Fatura tum planlara dahildir. UJP API\'sini actiginda faturalariniz otomatik gonderilecek.',
        },
      ],
    },
    bottomCta: {
      title: 'POS\'unuzu 2 dakikada degistirin',
      sub: 'Chrome\'u acin. Urunleri girin. Tarayin. Tahsil edin. Kurulum yok, teknisyen yok, bekleme yok.',
      button: 'Ucretsiz basla',
    },
  },
  en: {
    hero: {
      title: 'POS that books, fiscalizes and deducts stock \u2014 automatically',
      subtitle:
        'Open Chrome. Scan. Charge. Invoice, inventory, accounting and fiscal receipt \u2014 one click. Free.',
      cta: 'Start free',
      badge: 'Free POS',
    },
    painPoints: {
      title: 'Does this sound familiar?',
      subtitle: 'Every small business in Macedonia struggles with the same problems',
      items: [
        {
          icon: '\u{1F4B8}',
          title: 'Desktop POS costs \u20AC300 + technician',
          desc: 'You pay \u20AC300 for a desktop program, plus \u20AC50 for installation. And it only runs on Windows.',
        },
        {
          icon: '\u{1F5A5}',
          title: 'Tied to one computer',
          desc: 'If your computer breaks, you lose everything. No cloud, no backup, no access from another device.',
        },
        {
          icon: '\u{1F4C3}',
          title: 'Manual accounting entry',
          desc: 'Your accountant re-types Z-reports by hand. Every day, every line item. In 2026.',
        },
        {
          icon: '\u{1F4E6}',
          title: 'Inventory in Excel',
          desc: 'You sell an item at the register but inventory doesn\u2019t update. You discover you\u2019re out of stock when the customer is standing in front of you.',
        },
        {
          icon: '\u{1F50C}',
          title: 'Fiscal printer \u2014 nightmare',
          desc: 'COM port, drivers, Windows-only DLL. Every computer change = technician call.',
        },
        {
          icon: '\u{26A0}',
          title: 'e-Invoice is coming',
          desc: 'From October 2026, e-Invoice is mandatory. Your current POS isn\u2019t ready. Facturino is.',
        },
      ],
    },
    solution: {
      title: 'Facturino POS: one click = five actions',
      subtitle: 'The cashier taps \u201CCharge\u201D \u2014 the system automatically:',
      steps: [
        { num: '1', title: 'Creates invoice', desc: 'With line items, VAT and invoice number' },
        { num: '2', title: 'Deducts stock', desc: 'Automatically from the selected warehouse' },
        { num: '3', title: 'Posts to IFRS', desc: 'Revenue, VAT and payment \u2014 automatically' },
        { num: '4', title: 'Accepts payment', desc: 'Cash or card, with change calculation' },
        { num: '5', title: 'Prints fiscal receipt', desc: 'WebSerial \u2014 directly from Chrome' },
      ],
    },
    features: [
      {
        icon: '\u{1F4F1}',
        badge: 'Zero installation',
        title: 'Works on any device',
        subtitle: 'Open Chrome on a computer, tablet or phone. No installation, no Windows. Works even on a 5,000 MKD Android tablet.',
        items: [
          { title: 'Full-screen interface', desc: 'Touch-optimized for 7 to 27 inch screens' },
          { title: 'Barcode scanner', desc: 'USB or Bluetooth scanner \u2014 plug & play' },
          { title: 'Works offline', desc: 'PWA \u2014 keep selling even without internet' },
          { title: 'Keyboard shortcuts', desc: 'F1=search, F2=charge, F3=clear, Esc=cancel' },
        ],
      },
      {
        icon: '\u{1F4B3}',
        badge: 'Automatic booking',
        title: 'No manual re-entry',
        subtitle: 'Every sale auto-posts: revenue, VAT (18%/5%/10%), payment. Your accountant sees everything in real time.',
        items: [
          { title: 'IFRS auto-posting', desc: 'Every invoice \u2192 journal, general ledger, trial balance' },
          { title: 'VAT automatic', desc: '18%, 5%, 10% or exempt \u2014 per item' },
          { title: 'Accountant dashboard', desc: 'Your accountant gets their own access \u2014 read-only, real-time' },
          { title: 'e-Invoice ready', desc: 'When UJP opens the API, you\u2019re first in line' },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'Real-time inventory',
        title: 'Never say \u201COut of stock\u201D again',
        subtitle: 'Sold an item? Stock updates instantly. Received goods? Scan the receipt. Everything is connected.',
        items: [
          { title: 'Auto deduction', desc: 'Every POS sale reduces stock' },
          { title: 'Low stock warning', desc: 'Get notified when an item drops below minimum' },
          { title: 'Multiple warehouses', desc: 'Store, warehouse, branch \u2014 each with its own stock' },
          { title: 'WAC calculation', desc: 'Automatic weighted average cost after every purchase' },
        ],
      },
      {
        icon: '\u{1F5A8}',
        badge: 'WebSerial',
        title: 'Fiscal printer \u2014 no drivers',
        subtitle: 'Connect your fiscal printer via USB. Chrome recognizes it directly \u2014 no COM port, no DLL, no technician.',
        items: [
          { title: 'ISL protocol', desc: 'Supports Datecs, Tremol, Daisy and other ISL printers' },
          { title: 'Plug & play', desc: 'Plug USB \u2192 Chrome asks \u2192 done' },
          { title: 'VAT groups A/B/V/G', desc: 'Auto-mapping: A=18%, B=5%, V=10%, G=0%' },
          { title: 'Fraud monitoring', desc: 'AI tracks every fiscal event \u2014 alerts on suspicious activity' },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: 'Shifts & reports',
        title: 'Cashier control',
        subtitle: 'Open shift, close shift \u2014 with opening and closing cash. Every sale is tied to a cashier.',
        items: [
          { title: 'Open/close shift', desc: 'Enter opening cash, see the difference at the end' },
          { title: 'Z-report', desc: 'Total sales, returns, cash vs. card' },
          { title: 'Per cashier', desc: 'Each cashier \u2014 their own stats and accountability' },
          { title: 'Park sale', desc: 'Park the sale, serve another customer, come back later' },
        ],
      },
    ],
    comparison: {
      title: 'Facturino POS vs. competition',
      headers: ['Feature', 'Facturino', 'Desktop POS (~\u20AC300)', 'Local POS (~\u20AC200)', 'ERP Systems (\u20AC500+/year)'],
      rows: [
        ['Price', 'Free*', '\u20AC300 + technician', '~\u20AC200', '\u20AC500-1,200/year'],
        ['Works on tablet', '\u2705 Any device', '\u274C Windows only', '\u274C Windows', '\u274C Windows'],
        ['Cloud access', '\u2705 Anywhere', '\u274C Local', '\u274C Local', '\u2705 Optional'],
        ['Auto accounting', '\u2705 IFRS', '\u274C Manual', '\u274C Manual', '\u2705 Integrated'],
        ['Stock on sale', '\u2705 Automatic', '\u274C Manual', '\u2705 Partial', '\u2705 Yes'],
        ['Fiscal printer', '\u2705 WebSerial', '\u2705 COM port', '\u2705 COM port', '\u2705 COM port'],
        ['e-Invoice ready', '\u2705 Yes', '\u274C No', '\u274C No', '\u274C No'],
        ['AI advisor', '\u2705 Yes', '\u274C No', '\u274C No', '\u274C No'],
        ['Installation', 'Open Chrome', 'Technician', 'Technician', 'Technician + server'],
      ],
      footnote: '*Free up to 30 sales/month. Starter: \u20AC12 for 60, Standard: \u20AC39 for 500.',
    },
    faq: {
      title: 'Frequently asked questions',
      items: [
        {
          q: 'Is it really free?',
          a: 'Yes. The free plan allows 30 POS sales per month \u2014 enough to test it. No credit card, no contract.',
        },
        {
          q: 'Which fiscal printers are supported?',
          a: 'All ISL-compatible printers: Datecs, Tremol, Daisy. Connect via USB \u2014 Chrome recognizes it directly, no drivers needed.',
        },
        {
          q: 'Does it work offline?',
          a: 'The POS interface works offline (PWA). Sales sync when the connection returns. The fiscal printer works locally.',
        },
        {
          q: 'Can I use it on a tablet?',
          a: 'Yes! Open Chrome on an Android or iPad tablet. Touch-optimized interface with large buttons. A 5,000 MKD Android tablet is enough.',
        },
        {
          q: 'What if I already have another POS?',
          a: 'You can import items and categories via CSV. No technician needed \u2014 open Facturino, enter your items and start selling.',
        },
        {
          q: 'Does my accountant have access?',
          a: 'Yes. Facturino is the only POS where your accountant sees everything in real time \u2014 sales, VAT, inventory, trial balance. No re-typing.',
        },
        {
          q: 'How much does e-Invoice cost?',
          a: 'e-Invoice is included in all plans. When UJP opens the API, your invoices will be sent automatically.',
        },
      ],
    },
    bottomCta: {
      title: 'Replace your POS in 2 minutes',
      sub: 'Open Chrome. Enter items. Scan. Charge. No installation, no technician, no waiting.',
      button: 'Start free',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                     */
/* ------------------------------------------------------------------ */

export default async function PosPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ── Hero ── */}
      <PageHero
        image="/images/pos/hero-shop-owner.png"
        alt="Macedonian shop owner using Facturino POS on tablet with fiscal printer"
        title={t.hero.title}
        subtitle={t.hero.subtitle}
        cta={{ label: t.hero.cta, href: `https://app.facturino.mk/signup` }}
        badge={t.hero.badge}
      />

      {/* ── Pain Points ── */}
      <section className="py-10 md:py-20 bg-gray-50">
        <div className="container mx-auto max-w-6xl px-4">
          <div className="text-center mb-8 md:mb-14">
            <h2 className="text-2xl md:text-4xl font-extrabold text-gray-900 mb-3">
              {t.painPoints.title}
            </h2>
            <p className="text-gray-500 text-sm md:text-lg max-w-2xl mx-auto">
              {t.painPoints.subtitle}
            </p>
          </div>

          <div className="grid gap-4 md:gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
            {t.painPoints.items.map((item, i) => (
              <div
                key={i}
                className="bg-white rounded-xl border border-gray-200 p-5 md:p-6 shadow-sm hover:shadow-md transition-shadow"
              >
                <span className="text-2xl md:text-3xl block mb-3">{item.icon}</span>
                <h3 className="text-sm md:text-base font-bold text-gray-900 mb-1.5">
                  {item.title}
                </h3>
                <p className="text-xs md:text-sm text-gray-500 leading-relaxed">{item.desc}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* ── Solution: One Click = Five Actions ── */}
      <section className="py-10 md:py-20 bg-white">
        <div className="container mx-auto max-w-5xl px-4">
          <div className="text-center mb-8 md:mb-14">
            <h2 className="text-2xl md:text-4xl font-extrabold text-gray-900 mb-3">
              {t.solution.title}
            </h2>
            <p className="text-gray-500 text-sm md:text-lg">{t.solution.subtitle}</p>
          </div>

          <div className="relative">
            {/* Connector line */}
            <div className="hidden md:block absolute left-1/2 top-0 bottom-0 w-0.5 bg-indigo-100" />

            <div className="space-y-6 md:space-y-0 md:grid md:grid-cols-5 md:gap-4">
              {t.solution.steps.map((step, i) => (
                <div key={i} className="text-center relative">
                  <div className="relative z-10 w-12 h-12 mx-auto rounded-full bg-indigo-600 text-white flex items-center justify-center text-lg font-black shadow-lg shadow-indigo-500/30">
                    {step.num}
                  </div>
                  <h3 className="mt-3 text-sm md:text-base font-bold text-gray-900">
                    {step.title}
                  </h3>
                  <p className="mt-1 text-xs md:text-sm text-gray-500">{step.desc}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* ── Real POS UI Screenshot ── */}
      <section className="py-10 md:py-16 bg-white">
        <div className="container mx-auto max-w-6xl px-4">
          <div className="rounded-2xl overflow-hidden shadow-2xl shadow-indigo-500/10 border border-gray-200">
            <Image
              src="/images/pos/pos-ui-real.png"
              alt="Facturino POS interface — real screenshot with product photos and cart"
              width={1440}
              height={900}
              className="w-full h-auto"
              priority
            />
          </div>
        </div>
      </section>

      {/* ── Feature Sections with images ── */}
      {(() => {
        const featureImages = [
          { src: '/images/pos/pos-ui-tablet.png', alt: 'Facturino POS on tablet device' },
          { src: '/images/pos/bakery-scanning.png', alt: 'Bakery owner scanning barcode with POS' },
          { src: '/images/pos/bazaar-vendor.png', alt: 'Market vendor using POS with fiscal printer' },
          { src: '/images/pos/kafana-waitress-pos.png', alt: 'Macedonian kafana waitress taking payment on phone with Facturino POS' },
          { src: '/images/pos/multi-location.png', alt: 'Multi-location POS analytics dashboard' },
        ]
        return t.features.map((section, idx) => {
        const isEven = idx % 2 === 0
        const img = featureImages[idx]
        return (
          <section
            key={idx}
            className={`py-8 md:py-20 ${isEven ? 'bg-gray-50' : 'bg-white'}`}
          >
            <div className="container mx-auto max-w-6xl px-4">
              <div className={`flex flex-col ${isEven ? 'md:flex-row' : 'md:flex-row-reverse'} gap-8 md:gap-12 items-center`}>
                {/* Image */}
                {img && (
                  <div className="w-full md:w-1/2 flex-shrink-0">
                    <div className="rounded-2xl overflow-hidden shadow-xl border border-gray-100">
                      <Image src={img.src} alt={img.alt} width={700} height={440} className="w-full h-auto" />
                    </div>
                  </div>
                )}

                {/* Text + features */}
                <div className={img ? 'w-full md:w-1/2' : 'w-full'}>
              <div className="mb-6 md:mb-10">
                <span className="mb-1 block text-2xl md:text-4xl">{section.icon}</span>
                {section.badge && (
                  <span className="mb-2 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                    {section.badge}
                  </span>
                )}
                <h2 className="mb-2 text-xl font-extrabold text-gray-900 md:text-3xl">
                  {section.title}
                </h2>
                <p className="max-w-2xl text-sm md:text-lg text-gray-600">
                  {section.subtitle}
                </p>
              </div>

              <div className={`grid gap-3 md:gap-6 ${img ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-2 lg:grid-cols-4'}`}>
                {section.items.map((feat, fi) => (
                  <div
                    key={fi}
                    className="rounded-xl border border-gray-200 bg-white p-3 md:p-5 shadow-sm transition hover:shadow-md"
                  >
                    <h3 className="mb-1 md:mb-2 text-sm md:text-base font-semibold text-gray-900">
                      {feat.title}
                    </h3>
                    <p className="text-xs md:text-sm leading-relaxed text-gray-600">
                      {feat.desc}
                    </p>
                  </div>
                ))}
              </div>
                </div>
              </div>
            </div>
          </section>
        )
      })
      })()}

      {/* ── Comparison Table ── */}
      <section className="py-10 md:py-20 bg-white">
        <div className="container mx-auto max-w-6xl px-4">
          <h2 className="text-2xl md:text-4xl font-extrabold text-gray-900 mb-8 text-center">
            {t.comparison.title}
          </h2>

          <div className="overflow-x-auto -mx-4 px-4">
            <table className="w-full min-w-[640px] text-sm">
              <thead>
                <tr className="border-b-2 border-gray-200">
                  {t.comparison.headers.map((h, i) => (
                    <th
                      key={i}
                      className={`py-3 px-3 text-left font-bold ${
                        i === 1
                          ? 'text-indigo-600 bg-indigo-50 rounded-t-lg'
                          : 'text-gray-900'
                      }`}
                    >
                      {h}
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {t.comparison.rows.map((row, ri) => (
                  <tr
                    key={ri}
                    className="border-b border-gray-100 hover:bg-gray-50 transition-colors"
                  >
                    {row.map((cell, ci) => (
                      <td
                        key={ci}
                        className={`py-3 px-3 ${
                          ci === 1
                            ? 'font-semibold text-indigo-600 bg-indigo-50/50'
                            : ci === 0
                            ? 'font-medium text-gray-900'
                            : 'text-gray-600'
                        }`}
                      >
                        {cell}
                      </td>
                    ))}
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <p className="mt-4 text-xs text-gray-400 text-center">{t.comparison.footnote}</p>
        </div>
      </section>

      {/* ── FAQ ── */}
      <section className="py-10 md:py-20 bg-white">
        <div className="container mx-auto max-w-3xl px-4">
          <h2 className="text-2xl md:text-4xl font-extrabold text-gray-900 mb-8 text-center">
            {t.faq.title}
          </h2>

          <div className="space-y-4">
            {t.faq.items.map((item, i) => (
              <details
                key={i}
                className="group rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-shadow"
              >
                <summary className="flex items-center justify-between cursor-pointer px-5 py-4 text-sm md:text-base font-semibold text-gray-900 select-none">
                  {item.q}
                  <svg
                    className="w-5 h-5 text-gray-400 transition-transform group-open:rotate-180 flex-shrink-0 ml-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      d="M19 9l-7 7-7-7"
                    />
                  </svg>
                </summary>
                <div className="px-5 pb-4 text-sm text-gray-600 leading-relaxed">
                  {item.a}
                </div>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* ── Bottom CTA ── */}
      <section className="bg-gradient-to-r from-indigo-600 to-indigo-700 py-12 md:py-20">
        <div className="container mx-auto max-w-3xl px-4 text-center">
          <h2 className="text-3xl md:text-4xl font-extrabold text-white mb-4">
            {t.bottomCta.title}
          </h2>
          <p className="text-indigo-200 mb-8 text-sm md:text-lg">{t.bottomCta.sub}</p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center gap-2 bg-white text-indigo-600 px-8 py-4 rounded-xl text-lg font-bold hover:bg-gray-50 transition-colors shadow-xl"
          >
            {t.bottomCta.button}
            <svg
              className="w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              strokeWidth={2.5}
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
