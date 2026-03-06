import Link from 'next/link'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/features', {
    title: {
      mk: 'Функции — Facturino',
      sq: 'Vecorite — Facturino',
      tr: 'Ozellikler — Facturino',
      en: 'Features — Facturino',
    },
    description: {
      mk: 'AI фактурирање, е-Фактура, увоз на банкарски изводи, IFRS извештаи и повеќе. Откријте ги сите функции на Facturino.',
      sq: 'Faturim me AI, e-Fature, import i ekstrakteve bankare, raporte IFRS dhe me shume. Zbuloni te gjitha vecorite e Facturino.',
      tr: 'AI faturalama, e-Fatura, banka ekstresi aktarimi, IFRS raporlari ve dahasi. Facturino ozelliklerini kesfedin.',
      en: 'AI invoicing, e-Invoice, bank statement import, IFRS reports and more. Discover all Facturino features.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – all 4 locales, no dictionary dependency             */
/* ------------------------------------------------------------------ */

const copy = {
  mk: {
    hero: {
      title: 'Функции што го прават вашиот бизнис подобар',
      subtitle:
        'Најнапредната AI сметководствена платформа во Македонија — е-Фактура, увоз на банкарски изводи, IFRS извештаи и AI финансиски советник на едно место.',
      cta: 'Започни бесплатно',
    },
    sections: [
      {
        icon: '\u{1F916}',
        badge: 'Водечка функција',
        title: 'AI Финансиски Советник',
        subtitle:
          'Не само автоматизација — личен финансиски советник кој ги анализира вашите податоци и дава конкретни совети на македонски.',
        features: [
          {
            title: 'Разговарај со AI',
            desc: 'Прашај "Кој ми должи најмногу?" или "Дали сум профитабилен?" — добиј одговор веднаш.',
          },
          {
            title: '90-дневна прогноза на готовина',
            desc: 'Гледај напред — дали ќе имаш доволно пари на сметка следниот месец?',
          },
          {
            title: 'Рано предупредување за ризици',
            desc: 'AI те известува кога еден клиент станува преголем ризик или кога зависиш премногу од еден извор на приходи.',
          },
          {
            title: 'Што-ако сценарија',
            desc: '"Што ако го изгубам најголемиот клиент?" — симулирај го влијанието пред да се случи.',
          },
          {
            title: 'Анализа на старост на побарувања',
            desc: 'AR Aging извештај со топ должници, групиран по 30/60/90 дена, со препораки за наплата.',
          },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'Ново',
        title: 'Материјално сметководство',
        subtitle:
          'Целосно магацинско работење со формални документи, WAC вреднување и автоматско книжење — по македонски стандарди.',
        features: [
          {
            title: 'Повеќе магацини',
            desc: 'Управувајте со неограничен број магацини со посебни залихи, локации и одговорни лица.',
          },
          {
            title: 'Приемница, издатница, преносница',
            desc: 'Формални магацински документи кои инспекторите ги бараат — автоматски генерирани со секое движење.',
          },
          {
            title: 'WAC вреднување (пондерирана цена)',
            desc: 'Автоматско пресметување на пондерирана просечна цена при секој влез — усогласено со МК стандарди.',
          },
          {
            title: 'Автоматско книжење (Класа 3, 6, 7)',
            desc: 'Секое движење на залиха автоматски го генерира книжењето во главна книга — без рачно контирање.',
          },
          {
            title: 'Известувања за ниски залихи',
            desc: 'Поставете минимално количество по артикл и добивајте предупредувања кога залихата е критична.',
          },
          {
            title: 'Баркод и SKU следење',
            desc: 'Скенирајте баркод или внесете SKU за брзо пронаоѓање, приемање и издавање на артикли.',
          },
        ],
      },
      {
        icon: '\u{1F4DC}',
        badge: '',
        title: 'Е-Фактура и усогласеност',
        subtitle:
          'Целосно подготвени за е-Фактура од денот кога UJP ќе го отвори продукцискиот API.',
        features: [
          {
            title: 'Структурирани податоци за UJP',
            desc: 'Даночен број, ДДВ разделен по стапка, рокови на плаќање — сè е веќе во правилен формат.',
          },
          {
            title: 'ДДВ и даночни правила',
            desc: 'Автоматско пресметување на ДДВ по македонски стапки (5%, 10%, 18%) и правилна класификација.',
          },
          {
            title: 'Професионални PDF шаблони',
            desc: 'Македонски изглед на фактури — лого, печат, банкарски информации и QR код за плаќање.',
          },
        ],
      },
      {
        icon: '\u{1F3E6}',
        badge: '',
        title: 'Банки и готовински тек',
        subtitle:
          'Увезете банкарски изводи од CSV, MT940 или PDF и порамнете ги автоматски со фактурите. PSD2 наскоро.',
        features: [
          {
            title: 'Увоз на банкарски изводи',
            desc: 'Увезете изводи од NLB, Стопанска, Комерцијална, Sparkasse и други банки во CSV или MT940 формат.',
          },
          {
            title: 'Полуавтоматско порамнување',
            desc: 'Системот препознава плаќања и ги поврзува со фактури — вие само потврдувате.',
          },
          {
            title: 'PDF/OCR скенирање',
            desc: 'Скенирајте банкарски извод во PDF — AI автоматски ги извлекува трансакциите.',
          },
        ],
      },
      {
        icon: '\u{1F3E2}',
        badge: '',
        title: 'Управување со повеќе компании',
        subtitle:
          'Едно најавување, десетици клиенти. Идеално за сметководствени канцеларии.',
        features: [
          {
            title: 'Едно најавување, многу компании',
            desc: 'Префрлете се меѓу клиенти со еден клик — без одјавување и повторно најавување.',
          },
          {
            title: 'Посебни подесувања по компанија',
            desc: 'Секоја компанија има свој сметководствен план, ДДВ подесувања и кориснички овластувања.',
          },
          {
            title: 'Групни операции',
            desc: 'Затворете месец, генерирајте извештаи или испратете потсетници за повеќе клиенти одеднаш.',
          },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: '',
        title: 'IFRS извештаи',
        subtitle:
          'Професионални финансиски извештаи по меѓународни стандарди — на македонски.',
        features: [
          {
            title: 'Биланс на состојба и добивка/загуба',
            desc: 'IFRS-усогласени извештаи генерирани автоматски од вашите книжења.',
          },
          {
            title: 'Извештај за парични текови',
            desc: 'Следете ги приливите и одливите на готовина со детален преглед по категорија.',
          },
          {
            title: 'Извоз во Excel и PDF',
            desc: 'Преземете ги извештаите во формат погоден за УЈП, ревизори или клиенти.',
          },
        ],
      },
      {
        icon: '\u{2699}\u{FE0F}',
        badge: '',
        title: 'Автоматизации и работни текови',
        subtitle:
          'Поставете правила еднаш — системот работи за вас.',
        features: [
          {
            title: 'Рекурентни фактури',
            desc: 'Месечни, квартални или годишни фактури — се генерираат и испраќаат автоматски.',
          },
          {
            title: 'Потсетници за плаќање',
            desc: 'Автоматски е-маил потсетници 7, 14 и 30 дена по рок на фактура.',
          },
          {
            title: 'Правила „ако неплатено → потсети"',
            desc: 'Дефинирајте сопствени правила: ако фактурата е неплатена X дена, испрати потсетник или ескалирај.',
          },
        ],
      },
    ],
    comparison: {
      text: 'Facturino е QuickBooks/Xero класа — но дизајниран за Македонија.',
      sub: 'Македонски ДДВ правила, локални банки, е-Фактура подготвеност и AI на македонски јазик.',
    },
    bottomCta: {
      title: 'Подготвени сте? Започнете бесплатно денес.',
      sub: 'Без кредитна картичка \u2022 14-дневен бесплатен пробен период \u2022 Откажете во секое време',
      button: 'Започни бесплатно',
    },
  },
  sq: {
    hero: {
      title: 'Vecorite qe e bejne biznesin tuaj me te mire',
      subtitle:
        'Platforma me e avancuar kontabiliteti me AI ne Maqedoni — e-Fature, import i ekstrakteve bankare, raporte IFRS dhe keshilltar financiar AI ne nje vend.',
      cta: 'Fillo falas',
    },
    sections: [
      {
        icon: '\u{1F916}',
        badge: 'Vecoria kryesore',
        title: 'Keshilltar Financiar AI',
        subtitle:
          'Jo vetem automatizim — nje keshilltar financiar personal qe analizon te dhenat tuaja dhe jep keshilla konkrete.',
        features: [
          {
            title: 'Bisedoni me AI',
            desc: 'Pyesni "Cili klient me detyron me shume?" ose "A jam profitabil?" — merrni pergjigie menjehere.',
          },
          {
            title: 'Parashikim 90-ditor i parase',
            desc: 'Shikoni perpara — a do te keni mjaft para ne llogari muajin e ardhshem?',
          },
          {
            title: 'Paralajmerim i hershem per rreziqet',
            desc: 'AI ju njofton kur nje klient behet rrezik i madh ose kur vareni shume nga nje burim te ardhurash.',
          },
          {
            title: 'Skenare cfare-nese',
            desc: '"Cfare nese humb klientin me te madh?" — simuloni ndikimin para se te ndodhe.',
          },
          {
            title: 'Analize e moshes se borxheve',
            desc: 'Raport AR Aging me debitoret kryesore, i grupuar 30/60/90 dite, me rekomandime per arketim.',
          },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'E re',
        title: 'Kontabiliteti Material',
        subtitle:
          'Menaxhim i plote i magazinave me dokumente formale, vleresim WAC dhe regjistrim automatik — sipas standardeve maqedonase.',
        features: [
          {
            title: 'Shume magazina',
            desc: 'Menaxhoni numrin e pakufizuar te magazinave me stok, vendndodhje dhe persona pergjegjes te ndara.',
          },
          {
            title: 'Fletehyrje, fletdalje, fletekalim',
            desc: 'Dokumente formale te magazines qe inspektoret i kerkojne — te gjeneruara automatikisht me cdo levizje.',
          },
          {
            title: 'Vleresim WAC (cmim mesatar i ponderuar)',
            desc: 'Llogaritje automatike e cmimit mesatar te ponderuar ne cdo hyrje — ne perputhje me standardet MK.',
          },
          {
            title: 'Regjistrim automatik (Klasa 3, 6, 7)',
            desc: 'Cdo levizje stoku gjeneron automatikisht regjistrimin ne librin e madh — pa kontim manual.',
          },
          {
            title: 'Njoftim per stok te ulet',
            desc: 'Vendosni sasine minimale per artikull dhe merrni paralajmerime kur stoku eshte kritik.',
          },
          {
            title: 'Gjurmim me barkod dhe SKU',
            desc: 'Skenoni barkodin ose vendosni SKU per gjetje, pranim dhe leshim te shpejte te artikujve.',
          },
        ],
      },
      {
        icon: '\u{1F4DC}',
        badge: '',
        title: 'e-Fature dhe pajtueshmeri',
        subtitle:
          'Plotesisht gati per e-Fature qe nga dita kur UJP hap API-n e prodhimit.',
        features: [
          {
            title: 'Te dhena te strukturuara per UJP',
            desc: 'NIPT, TVSH sipas normes, afate pagese — gjithcka ne formatin e duhur.',
          },
          {
            title: 'TVSH dhe rregulla tatimore',
            desc: 'Llogaritje automatike e TVSH sipas normave maqedonase (5%, 10%, 18%) dhe klasifikim i sakte.',
          },
          {
            title: 'PDF profesionale',
            desc: 'Pamje maqedonase e faturave — logo, vule, info bankare dhe kod QR per pagese.',
          },
        ],
      },
      {
        icon: '\u{1F3E6}',
        badge: '',
        title: 'Bankat dhe fluksi i parase',
        subtitle:
          'Importoni ekstrakte bankare nga CSV, MT940 ose PDF dhe pajtojini automatikisht me faturat. PSD2 se shpejti.',
        features: [
          {
            title: 'Import i ekstrakteve bankare',
            desc: 'Importoni ekstrakte nga NLB, Stopanska, Komercijalna, Sparkasse dhe banka te tjera ne format CSV ose MT940.',
          },
          {
            title: 'Pajtim gjysme-automatik',
            desc: 'Sistemi njeh pagesat dhe i lidh me faturat — ju vetem konfirmoni.',
          },
          {
            title: 'PDF/OCR skanim',
            desc: 'Skanoni ekstrakt bankar ne PDF — AI nxjerr automatikisht transaksionet.',
          },
        ],
      },
      {
        icon: '\u{1F3E2}',
        badge: '',
        title: 'Menaxhim i shume kompanive',
        subtitle:
          'Nje hyrje, dhjetera kliente. Ideale per zyra kontabiliteti.',
        features: [
          {
            title: 'Nje hyrje, shume kompani',
            desc: 'Kalo midis klienteve me nje klik — pa dale dhe pa hyre perseri.',
          },
          {
            title: 'Rregullime te vecanta per kompani',
            desc: 'Cdo kompani ka plan llogarish, rregullime TVSH dhe leje perdoruesish te veta.',
          },
          {
            title: 'Operacione grupore',
            desc: 'Mbyllni muajin, gjeneroni raporte ose dergoni rikujtues per shume kliente njekohesisht.',
          },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: '',
        title: 'Raporte IFRS',
        subtitle:
          'Raporte financiare profesionale sipas standardeve nderkombetare.',
        features: [
          {
            title: 'Bilanci dhe fitimi/humbja',
            desc: 'Raporte te pajtueshem me IFRS te gjeneruara automatikisht nga regjistrat tuaja.',
          },
          {
            title: 'Raport i fluksit te parase',
            desc: 'Ndiqni hyrjet dhe daljet e parase me pasqyre te detajuar sipas kategorise.',
          },
          {
            title: 'Eksport ne Excel dhe PDF',
            desc: 'Shkarkoni raportet ne format te pershtatshme per UJP, auditoret ose klientet.',
          },
        ],
      },
      {
        icon: '\u{2699}\u{FE0F}',
        badge: '',
        title: 'Automatizime dhe rrjedha pune',
        subtitle:
          'Vendosni rregullat nje here — sistemi punon per ju.',
        features: [
          {
            title: 'Fatura periodike',
            desc: 'Mujore, tremujore ose vjetore — gjenerehen dhe dergohen automatikisht.',
          },
          {
            title: 'Rikujtues per pagese',
            desc: 'Email automatik rikujtues 7, 14 dhe 30 dite pas afatit te fatures.',
          },
          {
            title: 'Rregulla "nese e papaguar \u2192 kujto"',
            desc: 'Percaktoni rregulla: nese fatura eshte e papaguar X dite, dergo rikujtues ose eskalim.',
          },
        ],
      },
    ],
    comparison: {
      text: 'Facturino eshte i klasit QuickBooks/Xero — por i dizajnuar per Maqedonine.',
      sub: 'Rregulla TVSH maqedonase, banka lokale, gatishmeri per e-Fature dhe AI ne gjuhen tuaj.',
    },
    bottomCta: {
      title: 'Gati? Fillo falas sot.',
      sub: 'Pa karte krediti \u2022 Prove falas 14 dite \u2022 Anulo ne cdo kohe',
      button: 'Fillo falas',
    },
  },
  tr: {
    hero: {
      title: 'Isinizi daha iyi yapan ozellikler',
      subtitle:
        "Makedonya'daki en gelismis AI muhasebe platformu — e-Fatura, banka ekstresi aktarimi, IFRS raporlari ve AI mali danisman tek catida.",
      cta: 'Ucretsiz basla',
    },
    sections: [
      {
        icon: '\u{1F916}',
        badge: 'Amiral gemisi ozellik',
        title: 'AI Mali Danisman',
        subtitle:
          'Sadece otomasyon degil — verilerinizi analiz eden ve somut tavsiyeler veren kisisel mali danisman.',
        features: [
          {
            title: 'AI ile Sohbet',
            desc: '"En cok borcu olan musteri kim?" veya "Karli miyim?" diye sorun — aninda cevap alin.',
          },
          {
            title: '90 gunluk nakit tahmini',
            desc: 'Ileriye bakin — gelecek ay hesabinizda yeterli paraniz olacak mi?',
          },
          {
            title: 'Erken risk uyarisi',
            desc: 'AI, bir musteri cok buyuk risk haline geldiginde veya tek gelir kaynagina cok bagimli oldugunuzda sizi bilgilendirir.',
          },
          {
            title: 'Ya olursa senaryolari',
            desc: '"En buyuk musteriyi kaybedersem ne olur?" — etkiyi gerceklesmeden once simulasyon yapin.',
          },
          {
            title: 'Alacak yaslandirma analizi',
            desc: 'AR Aging raporu: en buyuk borcluler, 30/60/90 gun gruplamasi, tahsilat tavsiyeleri.',
          },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'Yeni',
        title: 'Malzeme Muhasebesi',
        subtitle:
          'Resmi belgeler, WAC degerleme ve otomatik muhasebe kaydi ile eksiksiz depo yonetimi — Makedonya standartlarina uygun.',
        features: [
          {
            title: 'Birden fazla depo',
            desc: 'Sinirsis sayida depoyu ayri stok, konum ve sorumlu kisilerle yonetin.',
          },
          {
            title: 'Giris fisi, cikis fisi, transfer fisi',
            desc: 'Mufettislerin isteyecegi resmi depo belgeleri — her hareketle otomatik olusturulur.',
          },
          {
            title: 'WAC degerleme (agirlikli ortalama maliyet)',
            desc: 'Her giriste otomatik agirlikli ortalama maliyet hesaplama — MK standartlarina uygun.',
          },
          {
            title: 'Otomatik muhasebe kaydi (Sinif 3, 6, 7)',
            desc: 'Her stok hareketi buyuk defterde otomatik muhasebe kaydini olusturur — manuel kayit gerekmez.',
          },
          {
            title: 'Dusuk stok bildirimleri',
            desc: 'Urun bazinda minimum miktar belirleyin ve stok kritik seviyeye dustugunde uyari alin.',
          },
          {
            title: 'Barkod ve SKU takibi',
            desc: 'Barkod tarayin veya SKU girin — urunleri hizlica bulun, teslim alin ve sevk edin.',
          },
        ],
      },
      {
        icon: '\u{1F4DC}',
        badge: '',
        title: 'e-Fatura ve uyum',
        subtitle:
          "UJP uretim API'sini actigi andan itibaren e-Fatura'ya tamamen hazir.",
        features: [
          {
            title: 'UJP icin yapilandirilmis veri',
            desc: 'Vergi numarasi, oran bazinda KDV, odeme sartlari — her sey dogru formatta.',
          },
          {
            title: 'KDV ve vergi kurallari',
            desc: "Makedonya oranlarina gore otomatik KDV hesaplama (%5, %10, %18) ve dogru siniflandirma.",
          },
          {
            title: 'Profesyonel PDF sablonlari',
            desc: 'Makedon stilinde fatura gorunumu — logo, muhur, banka bilgileri ve odeme QR kodu.',
          },
        ],
      },
      {
        icon: '\u{1F3E6}',
        badge: '',
        title: 'Bankacilik ve nakit akisi',
        subtitle:
          'Banka ekstrelerini CSV, MT940 veya PDF olarak iceaktarin ve faturalarla otomatik eslestirin. PSD2 yakinda.',
        features: [
          {
            title: 'Banka ekstresi aktarimi',
            desc: 'NLB, Stopanska, Komercijalna, Sparkasse ve diger bankalardan ekstreleri CSV veya MT940 formatinda aktarin.',
          },
          {
            title: 'Yari otomatik mutabakat',
            desc: 'Sistem odemeleri tanir ve faturalarla eslestirir — siz sadece onaylarsiniz.',
          },
          {
            title: 'PDF/OCR tarama',
            desc: 'PDF banka ekstresini tarayin — AI islemleri otomatik olarak cikarir.',
          },
        ],
      },
      {
        icon: '\u{1F3E2}',
        badge: '',
        title: 'Coklu sirket yonetimi',
        subtitle:
          'Tek giris, onlarca musteri. Muhasebe ofisleri icin ideal.',
        features: [
          {
            title: 'Tek giris, cok sirket',
            desc: 'Musteriler arasinda tek tikla gecin — cikis yapip yeniden giris gerekmez.',
          },
          {
            title: 'Sirket bazinda ayarlar',
            desc: 'Her sirketin kendi hesap plani, KDV ayarlari ve kullanici yetkileri vardir.',
          },
          {
            title: 'Toplu islemler',
            desc: 'Ayi kapatin, rapor olusturun veya birden fazla musteri icin hatirlatici gonderin.',
          },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: '',
        title: 'IFRS raporlari',
        subtitle:
          'Uluslararasi standartlara uygun profesyonel finansal raporlar.',
        features: [
          {
            title: 'Bilanco ve gelir tablosu',
            desc: 'Kayitlarinizdan otomatik olarak olusturulan IFRS uyumlu raporlar.',
          },
          {
            title: 'Nakit akis raporu',
            desc: 'Nakit giris ve cikislari kategori bazinda detayli gorunumle izleyin.',
          },
          {
            title: "Excel ve PDF'ye aktarma",
            desc: 'Raporlari UJP, denetciler veya musteriler icin uygun formatta indirin.',
          },
        ],
      },
      {
        icon: '\u{2699}\u{FE0F}',
        badge: '',
        title: 'Otomasyonlar ve is akislari',
        subtitle:
          'Kurallari bir kez belirleyin — sistem sizin icin calisir.',
        features: [
          {
            title: 'Tekrarlayan faturalar',
            desc: 'Aylik, uc aylik veya yillik — otomatik olusturulur ve gonderilir.',
          },
          {
            title: 'Odeme hatirlaticilari',
            desc: 'Fatura vadesinden 7, 14 ve 30 gun sonra otomatik e-posta hatirlaticilari.',
          },
          {
            title: '"Odenmezse \u2192 hatirlat" kurallari',
            desc: 'Kendi kurallarinizi tanimlayin: fatura X gun odenmediyse hatirlatici gonderin veya eskalin edin.',
          },
        ],
      },
    ],
    comparison: {
      text: "Facturino QuickBooks/Xero sinifinda — ama Makedonya icin tasarlandi.",
      sub: "Makedonya KDV kurallari, yerel bankalar, e-Fatura hazirligi ve Turk/Makedon dilinde AI.",
    },
    bottomCta: {
      title: 'Hazir misiniz? Bugun ucretsiz baslayin.',
      sub: 'Kredi karti gerekmez \u2022 14 gun ucretsiz deneme \u2022 Istediginiz zaman iptal edin',
      button: 'Ucretsiz basla',
    },
  },
  en: {
    hero: {
      title: 'Features That Make Your Business Better',
      subtitle:
        'The most advanced AI accounting platform in Macedonia — e-Invoice, bank statement import, IFRS reports, and an AI financial advisor all in one place.',
      cta: 'Start Free',
    },
    sections: [
      {
        icon: '\u{1F916}',
        badge: 'Flagship Feature',
        title: 'AI Financial Advisor',
        subtitle:
          'Not just automation — a personal financial advisor that analyzes your data and provides concrete advice.',
        features: [
          {
            title: 'Chat with AI',
            desc: 'Ask "Who owes me the most?" or "Am I profitable?" — get an answer instantly.',
          },
          {
            title: '90-Day Cash Flow Forecast',
            desc: 'Look ahead — will you have enough money in your account next month?',
          },
          {
            title: 'Early Risk Warning',
            desc: 'AI notifies you when a client becomes too high-risk or when you depend too much on one revenue source.',
          },
          {
            title: 'What-If Scenarios',
            desc: '"What if I lose my biggest client?" — simulate the impact before it happens.',
          },
          {
            title: 'Accounts Receivable Aging',
            desc: 'AR Aging report with top debtors, grouped by 30/60/90 days, with collection recommendations.',
          },
        ],
      },
      {
        icon: '\u{1F4E6}',
        badge: 'New',
        title: 'Material Accounting',
        subtitle:
          'Complete warehouse management with formal documents, WAC valuation, and automatic GL posting — aligned with Macedonian standards.',
        features: [
          {
            title: 'Multi-Warehouse Support',
            desc: 'Manage unlimited warehouses with separate stock levels, locations, and responsible persons.',
          },
          {
            title: 'Goods Received, Issued & Transfer Notes',
            desc: 'Formal warehouse documents that inspectors require — automatically generated with every stock movement.',
          },
          {
            title: 'WAC Valuation (Weighted Average Cost)',
            desc: 'Automatic weighted average cost calculation on every stock-in — compliant with Macedonian accounting standards.',
          },
          {
            title: 'Automatic GL Posting (Class 3, 6, 7)',
            desc: 'Every inventory movement auto-generates the journal entry in the general ledger — no manual posting needed.',
          },
          {
            title: 'Low Stock Alerts',
            desc: 'Set minimum quantities per item and get warnings when stock drops to critical levels.',
          },
          {
            title: 'Barcode & SKU Tracking',
            desc: 'Scan a barcode or enter a SKU to quickly find, receive, and issue items from inventory.',
          },
        ],
      },
      {
        icon: '\u{1F4DC}',
        badge: '',
        title: 'e-Invoice & Compliance',
        subtitle:
          'Fully ready for e-Invoice from the day UJP opens the production API.',
        features: [
          {
            title: 'Structured Data for UJP',
            desc: 'Tax ID, VAT broken down by rate, payment terms — everything already in the correct format.',
          },
          {
            title: 'VAT & Tax Rules',
            desc: 'Automatic VAT calculation per Macedonian rates (5%, 10%, 18%) and correct classification.',
          },
          {
            title: 'Professional PDF Templates',
            desc: 'Macedonian-style invoice layout — logo, stamp, bank details, and QR code for payment.',
          },
        ],
      },
      {
        icon: '\u{1F3E6}',
        badge: '',
        title: 'Banking & Cash Flow',
        subtitle:
          'Import bank statements from CSV, MT940, or PDF and auto-reconcile with invoices. PSD2 coming soon.',
        features: [
          {
            title: 'Bank Statement Import',
            desc: 'Import statements from NLB, Stopanska, Komercijalna, Sparkasse, and other banks in CSV or MT940 format.',
          },
          {
            title: 'Semi-Automatic Reconciliation',
            desc: 'The system recognizes payments and matches them to invoices — you just confirm.',
          },
          {
            title: 'PDF/OCR Scanning',
            desc: 'Scan a bank statement PDF — AI automatically extracts the transactions.',
          },
        ],
      },
      {
        icon: '\u{1F3E2}',
        badge: '',
        title: 'Multi-Company Management',
        subtitle:
          'One login, dozens of clients. Ideal for accounting firms.',
        features: [
          {
            title: 'One Login, Many Companies',
            desc: 'Switch between clients with one click — no logging out and back in.',
          },
          {
            title: 'Per-Company Settings',
            desc: 'Each company has its own chart of accounts, VAT settings, and user permissions.',
          },
          {
            title: 'Batch Operations',
            desc: 'Close the month, generate reports, or send reminders for multiple clients at once.',
          },
        ],
      },
      {
        icon: '\u{1F4CA}',
        badge: '',
        title: 'IFRS Reporting',
        subtitle:
          'Professional financial reports according to international standards.',
        features: [
          {
            title: 'Balance Sheet & P&L',
            desc: 'IFRS-compliant reports generated automatically from your journal entries.',
          },
          {
            title: 'Cash Flow Statement',
            desc: 'Track cash inflows and outflows with a detailed breakdown by category.',
          },
          {
            title: 'Export to Excel & PDF',
            desc: 'Download reports in formats suitable for UJP, auditors, or clients.',
          },
        ],
      },
      {
        icon: '\u{2699}\u{FE0F}',
        badge: '',
        title: 'Automations & Workflows',
        subtitle:
          'Set up rules once — the system works for you.',
        features: [
          {
            title: 'Recurring Invoices',
            desc: 'Monthly, quarterly, or annual — generated and sent automatically.',
          },
          {
            title: 'Payment Reminders',
            desc: 'Automatic email reminders 7, 14, and 30 days past invoice due date.',
          },
          {
            title: '"If Unpaid \u2192 Remind" Rules',
            desc: 'Define your own rules: if an invoice is unpaid for X days, send a reminder or escalate.',
          },
        ],
      },
    ],
    comparison: {
      text: 'Facturino is QuickBooks/Xero-class — but built for Macedonia.',
      sub: 'Macedonian VAT rules, local banks, e-Invoice readiness, and AI in your language.',
    },
    bottomCta: {
      title: 'Ready? Start free today.',
      sub: 'No credit card required \u2022 14-day free trial \u2022 Cancel anytime',
      button: 'Start Free',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page Component                                                     */
/* ------------------------------------------------------------------ */

export default async function FeaturesPage({
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
      {/*  HERO                                                        */}
      {/* ============================================================ */}
      <section className="relative overflow-hidden bg-gradient-to-br from-[var(--color-primary)] to-[#1a2e5a] py-20 text-white md:py-28">
        {/* Decorative circles */}
        <div className="pointer-events-none absolute -right-20 -top-20 h-80 w-80 rounded-full bg-white/5" />
        <div className="pointer-events-none absolute -bottom-16 -left-16 h-64 w-64 rounded-full bg-white/5" />

        <div className="container relative mx-auto max-w-4xl px-4 text-center">
          <h1 className="mb-6 text-4xl font-extrabold leading-tight md:text-5xl lg:text-6xl">
            {t.hero.title}
          </h1>
          <p className="mx-auto mb-10 max-w-2xl text-lg text-white/80 md:text-xl">
            {t.hero.subtitle}
          </p>
          <Link
            href={`/${locale}/pricing`}
            className="inline-block rounded-lg bg-white px-8 py-4 text-lg font-bold text-[var(--color-primary)] shadow-lg transition hover:scale-105 hover:shadow-xl"
          >
            {t.hero.cta}
          </Link>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  FEATURE SECTIONS                                            */}
      {/* ============================================================ */}
      {t.sections.map((section, idx) => {
        const isEven = idx % 2 === 0
        const isAI = idx === 0 // First section is the flagship AI section

        if (isAI) {
          return (
            <section
              key={idx}
              className="relative overflow-hidden border-b-4 border-[var(--color-primary)]/20 bg-gradient-to-br from-indigo-50 via-white to-blue-50 py-16 md:py-24"
            >
              {/* Subtle grid pattern */}
              <div className="pointer-events-none absolute inset-0 opacity-[0.03]" style={{ backgroundImage: 'radial-gradient(circle, #000 1px, transparent 1px)', backgroundSize: '24px 24px' }} />

              <div className="container relative mx-auto max-w-6xl px-4">
                {/* Badge */}
                <div className="mb-4 flex items-center justify-center gap-2">
                  <span className="rounded-full bg-[var(--color-primary)] px-4 py-1.5 text-sm font-semibold text-white">
                    {section.icon} {section.badge}
                  </span>
                </div>

                <h2 className="mb-3 text-center text-3xl font-extrabold md:text-4xl" style={{ color: 'var(--color-primary)' }}>
                  {section.title}
                </h2>
                <p className="mx-auto mb-12 max-w-2xl text-center text-gray-600 md:text-lg">
                  {section.subtitle}
                </p>

                {/* AI chat-style demo bubble + features grid */}
                <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                  {section.features.map((feat, fi) => (
                    <div
                      key={fi}
                      className="group relative rounded-2xl border border-indigo-100 bg-white p-6 shadow-md transition hover:-translate-y-1 hover:shadow-xl"
                    >
                      <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-xl">
                        {fi === 0 ? '\u{1F4AC}' : fi === 1 ? '\u{1F4C8}' : fi === 2 ? '\u{26A0}\u{FE0F}' : fi === 3 ? '\u{1F52E}' : '\u{1F4CB}'}
                      </div>
                      <h3 className="mb-2 text-lg font-bold text-gray-900">
                        {feat.title}
                      </h3>
                      <p className="text-sm leading-relaxed text-gray-600">
                        {feat.desc}
                      </p>
                    </div>
                  ))}
                </div>
              </div>
            </section>
          )
        }

        return (
          <section
            key={idx}
            className={`py-16 md:py-20 ${isEven ? 'bg-white' : 'bg-gray-50'}`}
          >
            <div className="container mx-auto max-w-6xl px-4">
              <div className="mb-10 text-center md:text-left">
                <span className="mb-2 block text-4xl">{section.icon}</span>
                {section.badge && (
                  <span className="mb-3 inline-block rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                    {section.badge}
                  </span>
                )}
                <h2 className="mb-3 text-2xl font-extrabold text-gray-900 md:text-3xl">
                  {section.title}
                </h2>
                <p className="max-w-2xl text-gray-600 md:text-lg">
                  {section.subtitle}
                </p>
              </div>

              <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                {section.features.map((feat, fi) => (
                  <div
                    key={fi}
                    className="rounded-xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
                  >
                    <h3 className="mb-2 text-lg font-semibold text-gray-900">
                      {feat.title}
                    </h3>
                    <p className="text-sm leading-relaxed text-gray-600">
                      {feat.desc}
                    </p>
                  </div>
                ))}
              </div>
            </div>
          </section>
        )
      })}

      {/* ============================================================ */}
      {/*  COMPARISON BANNER                                           */}
      {/* ============================================================ */}
      <section className="bg-[var(--color-primary)] py-12 text-white md:py-16">
        <div className="container mx-auto max-w-4xl px-4 text-center">
          <p className="mb-3 text-2xl font-extrabold md:text-3xl">
            {t.comparison.text}
          </p>
          <p className="text-lg text-white/75">
            {t.comparison.sub}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
      <section className="bg-gray-50 py-16 md:py-20">
        <div className="container mx-auto max-w-3xl px-4 text-center">
          <h2 className="mb-4 text-3xl font-extrabold text-gray-900 md:text-4xl">
            {t.bottomCta.title}
          </h2>
          <p className="mb-8 text-gray-500">
            {t.bottomCta.sub}
          </p>
          <Link
            href={`/${locale}/pricing`}
            className="btn-primary inline-block px-10 py-4 text-lg font-bold"
          >
            {t.bottomCta.button}
          </Link>
        </div>
      </section>
    </main>
  )
}
