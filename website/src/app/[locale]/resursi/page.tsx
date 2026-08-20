import Link from 'next/link'
import { isLocale, defaultLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd } from '@/lib/jsonld'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/resursi', {
    title: {
      mk: 'Ресурси за бизниси во Македонија — алатки, водичи и калкулатори',
      sq: 'Burime për bizneset në Maqedoni — mjete, udhëzues dhe llogaritës',
      tr: 'Kuzey Makedonya için işletme kaynakları — araçlar, kılavuzlar ve hesaplayıcılar',
      en: 'Business Resources for North Macedonia — Tools, Guides & Calculators',
    },
    description: {
      mk: 'Централно место за отворање и водење фирма во Македонија: бесплатни калкулатори, водичи за е-фактура, даноци, плати, ДДВ, фактурирање и сметководство — сè на едно место.',
      sq: 'Qendra e vetme për të hapur dhe drejtuar një kompani në Maqedoni: llogaritës falas, udhëzues për e-faturën, tatimet, pagat, TVSH-në, faturimin dhe kontabilitetin — të gjitha në një vend.',
      tr: 'Kuzey Makedonya\'da şirket kurmak ve yönetmek için tek durak: ücretsiz hesaplayıcılar, e-fatura, vergi, maaş, KDV, faturalama ve muhasebe kılavuzları — hepsi tek yerde.',
      en: 'The all-in-one hub for starting and running a company in North Macedonia: free calculators, e-invoicing guides, taxes, payroll, VAT, invoicing and accounting — all in one place.',
    },
  })
}

const copy = {
  mk: {
    badge: 'Бизнис хаб на Македонија',
    h1: 'Ресурси за бизниси во Македонија',
    subtitle:
      'Сè што ви треба за да отворите и водите фирма во Македонија на едно место — бесплатни алатки, детални водичи и практични калкулатори.',
    categories: [
      {
        heading: 'Бесплатни алатки',
        items: [
          { href: '/alati/ddv-kalkulator', title: 'ДДВ калкулатор', desc: 'Пресметајте ДДВ за сите стапки — 18%, 5% и 10%, со обратен пресмет.' },
          { href: '/alati/plata-kalkulator', title: 'Бруто-нето калкулатор на плата', desc: 'Претворете бруто во нето плата и обратно со точни стапки за 2026.' },
          { href: '/alati/kamaten-kalkulator', title: 'Каматен калкулатор', desc: 'Пресметајте казнена камата по законска стапка за доцни плаќања.' },
          { href: '/alati/danok-dobivka-kalkulator', title: 'Калкулатор данок на добивка', desc: 'Пресметајте данок на добивка од 10% со признати и непризнати расходи.' },
          { href: '/alati/danocen-kalendar', title: 'Даночен календар', desc: 'Сите даночни рокови за годината: МПИН, ДДВ-04, годишна сметка.' },
          { href: '/alati/kalkulator-registracija-firma', title: 'Калкулатор: трошоци за регистрација', desc: 'Колку чини отворање фирма — ДООЕЛ, ДОО или паушалец.' },
          { href: '/alati/efaktura-proverka', title: 'е-Фактура UBL валидатор', desc: 'Проверете дали вашиот UBL XML е валиден пред испраќање до УЈП.' },
        ],
      },
      {
        heading: 'е-Фактура',
        items: [
          { href: '/e-faktura/vodic', title: 'Целосен водич за е-фактура 2026', desc: 'Сè за задолжителното е-фактурирање во Македонија на едно место.' },
          { href: '/e-faktura/rokovi-2026', title: 'Рокови 2026/2027', desc: 'Клучните датуми за воведување на е-фактурата по фази.' },
          { href: '/e-faktura/kako-da-izdadete', title: 'Како да издадете е-фактура', desc: 'Чекор-по-чекор упатство за издавање и испраќање е-фактура.' },
          { href: '/e-faktura/za-javni-nabavki', title: 'Е-фактура за јавни набавки', desc: 'Специфични правила при фактурирање кон државни институции.' },
          { href: '/e-faktura/casti-prasanja', title: 'Често поставувани прашања', desc: 'Одговори на најчестите дилеми околу е-фактурата.' },
        ],
      },
      {
        heading: 'Отворање фирма',
        items: [
          { href: '/otvori-firma', title: 'Целосен водич за отворање фирма', desc: 'Од идеја до регистрирана фирма — целиот процес објаснет.' },
          { href: '/otvori-firma/dooel-ili-doo', title: 'ДООЕЛ, ДОО или ТП?', desc: 'Која правна форма е најдобра за вашиот бизнис и зошто.' },
          { href: '/otvori-firma/paushal-ili-ddv', title: 'Паушалец или ДДВ обврзник?', desc: 'Споредба на двата даночни режими и кога кој се исплати.' },
          { href: '/otvori-firma/za-stranci', title: 'Отворање фирма за странци', desc: 'Како странски државјанин може да отвори фирма во Македонија.' },
          { href: '/blog/paket-za-nova-firma', title: 'Чеклиста за нова фирма', desc: 'Практична листа со сите чекори по регистрацијата.' },
        ],
      },
      {
        heading: 'Плати и работни односи',
        items: [
          { href: '/blog/presmetka-na-plata-mk', title: 'Пресметка на плата', desc: 'Како се пресметува плата во Македонија — придонеси и данок.' },
          { href: '/blog/mpin-obrazec', title: 'МПИН образец', desc: 'Што е МПИН, кога се поднесува и како се пополнува.' },
          { href: '/blog/trudovo-pravo-osnovi', title: 'Основи на трудово право', desc: 'Права и обврски на работодавачите и вработените.' },
          { href: '/blog/rok-za-plata-makedonija', title: 'Рок за исплата на плата', desc: 'Законските рокови за исплата на плата и последиците од доцнење.' },
        ],
      },
      {
        heading: 'Даноци и ДДВ',
        items: [
          { href: '/blog/ddv-vodich-mk', title: 'Водич за ДДВ', desc: 'Регистрација, стапки, ДДВ-04 и пресметка на данокот на додадена вредност.' },
          { href: '/blog/danok-na-dobivka', title: 'Данок на добивка', desc: 'Како се пресметува данокот на добивка и што е признат расход.' },
          { href: '/blog/personalen-danok-na-dohod', title: 'Персонален данок на доход', desc: 'ПДД за физички лица, стапки и обврски за пријавување.' },
          { href: '/blog/godishna-danocna-prijava-2026', title: 'Годишна даночна пријава', desc: 'Кој, кога и како поднесува годишна даночна пријава.' },
        ],
      },
      {
        heading: 'Фактурирање',
        items: [
          { href: '/blog/kako-da-napravite-faktura', title: 'Како да направите фактура', desc: 'Целиот процес на изготвување исправна фактура чекор по чекор.' },
          { href: '/blog/faktura-vs-proforma', title: 'Фактура наспроти профактура', desc: 'Која е разликата и кога се користи секоја од нив.' },
          { href: '/blog/zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура', desc: 'Што мора да содржи секоја фактура според законот.' },
          { href: '/blog/sto-e-e-faktura', title: 'Што е е-фактура', desc: 'Основите за електронската фактура и зошто е важна.' },
        ],
      },
      {
        heading: 'Сметководство и извештаи',
        items: [
          { href: '/blog/smetkovodstvo-za-pocetnici', title: 'Сметководство за почетници', desc: 'Основни поими за секој претприемач што почнува од нула.' },
          { href: '/blog/bilans-na-sostojba', title: 'Биланс на состојба', desc: 'Што покажува билансот на состојба и како да го прочитате.' },
          { href: '/blog/cash-flow-mk', title: 'Извештај за парични текови', desc: 'Како да го следите приливот и одливот на пари во фирмата.' },
          { href: '/blog/digitalno-smetkovodstvo', title: 'Дигитално сметководство', desc: 'Предностите на автоматизирано, облак-базирано сметководство.' },
        ],
      },
    ],
    ctaTitle: 'Автоматизирајте го целото сметководство',
    ctaSub: 'Facturino ги обединува фактурите, ДДВ, платите и е-фактурата во една платформа за вашата фирма.',
    ctaButton: 'Започни бесплатно — 14 дена',
  },
  sq: {
    badge: 'Qendra e biznesit e Maqedonisë',
    h1: 'Burime për bizneset në Maqedoni',
    subtitle:
      'Gjithçka që ju nevojitet për të hapur dhe drejtuar një kompani në Maqedoni në një vend — mjete falas, udhëzues të detajuar dhe llogaritës praktikë.',
    categories: [
      {
        heading: 'Mjete falas',
        items: [
          { href: '/alati/ddv-kalkulator', title: 'Llogaritësi i TVSH-së', desc: 'Llogaritni TVSH-në për të gjitha normat — 18%, 5% dhe 10%, me llogaritje të kundërt.' },
          { href: '/alati/plata-kalkulator', title: 'Llogaritësi bruto-neto i pagës', desc: 'Ktheni pagën bruto në neto dhe anasjelltas me norma të sakta për 2026.' },
          { href: '/alati/kamaten-kalkulator', title: 'Llogaritësi i kamatës', desc: 'Llogaritni kamatën ndëshkuese me normën ligjore për pagesa të vonuara.' },
          { href: '/alati/danok-dobivka-kalkulator', title: 'Llogaritësi i tatimit mbi fitimin', desc: 'Llogaritni tatimin mbi fitimin 10% me shpenzime të njohura dhe të panjohura.' },
          { href: '/alati/danocen-kalendar', title: 'Kalendari tatimor', desc: 'Të gjitha afatet tatimore të vitit: MPIN, DDV-04, llogaria vjetore.' },
          { href: '/alati/kalkulator-registracija-firma', title: 'Llogaritësi: shpenzimet e regjistrimit', desc: 'Sa kushton hapja e firmës — SHPKNJP, SHPK ose paushalist.' },
          { href: '/alati/efaktura-proverka', title: 'Validuesi UBL i e-Faturës', desc: 'Kontrolloni nëse XML-ja juaj UBL është e vlefshme para dërgimit në UJP.' },
        ],
      },
      {
        heading: 'e-Fatura',
        items: [
          { href: '/e-faktura/vodic', title: 'Udhëzuesi i plotë për e-faturën 2026', desc: 'Gjithçka për e-faturimin e detyrueshëm në Maqedoni në një vend.' },
          { href: '/e-faktura/rokovi-2026', title: 'Afatet 2026/2027', desc: 'Datat kyçe për futjen e e-faturës sipas fazave.' },
          { href: '/e-faktura/kako-da-izdadete', title: 'Si të lëshoni një e-faturë', desc: 'Udhëzim hap pas hapi për lëshimin dhe dërgimin e e-faturës.' },
          { href: '/e-faktura/za-javni-nabavki', title: 'e-Fatura për prokurimet publike', desc: 'Rregulla specifike gjatë faturimit ndaj institucioneve shtetërore.' },
          { href: '/e-faktura/casti-prasanja', title: 'Pyetje të shpeshta', desc: 'Përgjigje për dilemat më të zakonshme rreth e-faturës.' },
        ],
      },
      {
        heading: 'Hapja e firmës',
        items: [
          { href: '/otvori-firma', title: 'Udhëzuesi i plotë për hapjen e firmës', desc: 'Nga ideja te firma e regjistruar — i gjithë procesi i shpjeguar.' },
          { href: '/otvori-firma/dooel-ili-doo', title: 'SHPKNJP, SHPK apo TP?', desc: 'Cila formë juridike është më e mira për biznesin tuaj dhe pse.' },
          { href: '/otvori-firma/paushal-ili-ddv', title: 'Paushalist apo obligues i TVSH-së?', desc: 'Krahasim i dy regjimeve tatimore dhe kur secili ia vlen.' },
          { href: '/otvori-firma/za-stranci', title: 'Hapja e firmës për të huajt', desc: 'Si mund një shtetas i huaj të hapë firmë në Maqedoni.' },
          { href: '/blog/paket-za-nova-firma', title: 'Lista kontrolluese për firmë të re', desc: 'Listë praktike me të gjithë hapat pas regjistrimit.' },
        ],
      },
      {
        heading: 'Pagat dhe marrëdhëniet e punës',
        items: [
          { href: '/blog/presmetka-na-plata-mk', title: 'Llogaritja e pagës', desc: 'Si llogaritet paga në Maqedoni — kontributet dhe tatimi.' },
          { href: '/blog/mpin-obrazec', title: 'Formulari MPIN', desc: 'Çfarë është MPIN, kur dorëzohet dhe si plotësohet.' },
          { href: '/blog/trudovo-pravo-osnovi', title: 'Bazat e së drejtës së punës', desc: 'Të drejtat dhe detyrimet e punëdhënësve dhe të punësuarve.' },
          { href: '/blog/rok-za-plata-makedonija', title: 'Afati i pagesës së pagës', desc: 'Afatet ligjore për pagesën e pagës dhe pasojat e vonesës.' },
        ],
      },
      {
        heading: 'Tatimet dhe TVSH',
        items: [
          { href: '/blog/ddv-vodich-mk', title: 'Udhëzues për TVSH-në', desc: 'Regjistrimi, normat, DDV-04 dhe llogaritja e tatimit mbi vlerën e shtuar.' },
          { href: '/blog/danok-na-dobivka', title: 'Tatimi mbi fitimin', desc: 'Si llogaritet tatimi mbi fitimin dhe çfarë është shpenzim i njohur.' },
          { href: '/blog/personalen-danok-na-dohod', title: 'Tatimi personal mbi të ardhurat', desc: 'TPA për personat fizikë, normat dhe detyrimet e deklarimit.' },
          { href: '/blog/godishna-danocna-prijava-2026', title: 'Deklarata vjetore tatimore', desc: 'Kush, kur dhe si dorëzon deklaratën vjetore tatimore.' },
        ],
      },
      {
        heading: 'Faturimi',
        items: [
          { href: '/blog/kako-da-napravite-faktura', title: 'Si të bëni një faturë', desc: 'I gjithë procesi i hartimit të një fature të rregullt hap pas hapi.' },
          { href: '/blog/faktura-vs-proforma', title: 'Fatura kundrejt profaturës', desc: 'Cili është ndryshimi dhe kur përdoret secila prej tyre.' },
          { href: '/blog/zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme të faturës', desc: 'Çfarë duhet të përmbajë çdo faturë sipas ligjit.' },
          { href: '/blog/sto-e-e-faktura', title: 'Çfarë është e-fatura', desc: 'Bazat e faturës elektronike dhe pse është e rëndësishme.' },
        ],
      },
      {
        heading: 'Kontabiliteti dhe raportet',
        items: [
          { href: '/blog/smetkovodstvo-za-pocetnici', title: 'Kontabiliteti për fillestarë', desc: 'Konceptet bazë për çdo sipërmarrës që fillon nga zeroja.' },
          { href: '/blog/bilans-na-sostojba', title: 'Bilanci i gjendjes', desc: 'Çfarë tregon bilanci i gjendjes dhe si ta lexoni atë.' },
          { href: '/blog/cash-flow-mk', title: 'Raporti i rrjedhës së parasë', desc: 'Si të ndiqni hyrjet dhe daljet e parasë në firmë.' },
          { href: '/blog/digitalno-smetkovodstvo', title: 'Kontabiliteti digjital', desc: 'Përparësitë e kontabilitetit të automatizuar në cloud.' },
        ],
      },
    ],
    ctaTitle: 'Automatizoni të gjithë kontabilitetin',
    ctaSub: 'Facturino bashkon faturat, TVSH-në, pagat dhe e-faturën në një platformë të vetme për firmën tuaj.',
    ctaButton: 'Fillo falas — 14 ditë',
  },
  tr: {
    badge: 'Kuzey Makedonya iş merkezi',
    h1: 'Kuzey Makedonya için işletme kaynakları',
    subtitle:
      'Kuzey Makedonya\'da bir şirket kurmak ve yönetmek için ihtiyacınız olan her şey tek yerde — ücretsiz araçlar, ayrıntılı kılavuzlar ve pratik hesaplayıcılar.',
    categories: [
      {
        heading: 'Ücretsiz araçlar',
        items: [
          { href: '/alati/ddv-kalkulator', title: 'KDV hesaplayıcı', desc: 'Tüm oranlar için KDV hesaplayın — %18, %5 ve %10, ters hesaplama ile.' },
          { href: '/alati/plata-kalkulator', title: 'Brüt-net maaş hesaplayıcı', desc: 'Brüt maaşı nete ve tersine çevirin, 2026 için doğru oranlarla.' },
          { href: '/alati/kamaten-kalkulator', title: 'Faiz hesaplayıcı', desc: 'Geç ödemeler için yasal oranla ceza faizi hesaplayın.' },
          { href: '/alati/danok-dobivka-kalkulator', title: 'Kurumlar vergisi hesaplayıcı', desc: 'Kabul edilen ve edilmeyen giderlerle %10 kurumlar vergisi hesaplayın.' },
          { href: '/alati/danocen-kalendar', title: 'Vergi takvimi', desc: 'Yılın tüm vergi tarihleri: MPIN, DDV-04, yıllık hesaplar.' },
          { href: '/alati/kalkulator-registracija-firma', title: 'Hesaplayıcı: kayıt maliyetleri', desc: 'Şirket kurmanın maliyeti — DOOEL, DOO veya düz oranlı.' },
          { href: '/alati/efaktura-proverka', title: 'e-Fatura UBL doğrulayıcı', desc: 'UBL XML\'inizin UJP\'ye göndermeden önce geçerli olup olmadığını kontrol edin.' },
        ],
      },
      {
        heading: 'e-Fatura',
        items: [
          { href: '/e-faktura/vodic', title: '2026 e-Fatura tam kılavuzu', desc: 'Kuzey Makedonya\'daki zorunlu e-faturalama hakkında her şey tek yerde.' },
          { href: '/e-faktura/rokovi-2026', title: 'Son tarihler 2026/2027', desc: 'e-Faturanın aşamalı olarak devreye alınması için kilit tarihler.' },
          { href: '/e-faktura/kako-da-izdadete', title: 'e-Fatura nasıl düzenlenir', desc: 'e-Fatura düzenleme ve gönderme için adım adım rehber.' },
          { href: '/e-faktura/za-javni-nabavki', title: 'Kamu ihaleleri için e-Fatura', desc: 'Devlet kurumlarına faturalamada özel kurallar.' },
          { href: '/e-faktura/casti-prasanja', title: 'Sıkça sorulan sorular', desc: 'e-Fatura hakkında en yaygın soruların yanıtları.' },
        ],
      },
      {
        heading: 'Şirket kurma',
        items: [
          { href: '/otvori-firma', title: 'Şirket kurmanın tam kılavuzu', desc: 'Fikirden kayıtlı şirkete — tüm süreç açıklanmış.' },
          { href: '/otvori-firma/dooel-ili-doo', title: 'DOOEL, DOO mu yoksa TP mi?', desc: 'İşletmeniz için hangi hukuki biçim en iyisi ve neden.' },
          { href: '/otvori-firma/paushal-ili-ddv', title: 'Düz oranlı mı KDV mükellefi mi?', desc: 'İki vergi rejiminin karşılaştırması ve hangisinin ne zaman uygun olduğu.' },
          { href: '/otvori-firma/za-stranci', title: 'Yabancılar için şirket kurma', desc: 'Bir yabancı uyruklu Kuzey Makedonya\'da nasıl şirket kurabilir.' },
          { href: '/blog/paket-za-nova-firma', title: 'Yeni şirket kontrol listesi', desc: 'Kayıttan sonraki tüm adımlarla pratik bir liste.' },
        ],
      },
      {
        heading: 'Maaş ve iş ilişkileri',
        items: [
          { href: '/blog/presmetka-na-plata-mk', title: 'Maaş hesaplama', desc: 'Kuzey Makedonya\'da maaş nasıl hesaplanır — katkı payları ve vergi.' },
          { href: '/blog/mpin-obrazec', title: 'MPIN formu', desc: 'MPIN nedir, ne zaman verilir ve nasıl doldurulur.' },
          { href: '/blog/trudovo-pravo-osnovi', title: 'İş hukukunun temelleri', desc: 'İşverenlerin ve çalışanların hakları ve yükümlülükleri.' },
          { href: '/blog/rok-za-plata-makedonija', title: 'Maaş ödeme süresi', desc: 'Maaş ödemesi için yasal süreler ve gecikmenin sonuçları.' },
        ],
      },
      {
        heading: 'Vergiler ve KDV',
        items: [
          { href: '/blog/ddv-vodich-mk', title: 'KDV kılavuzu', desc: 'Kayıt, oranlar, DDV-04 ve katma değer vergisinin hesaplanması.' },
          { href: '/blog/danok-na-dobivka', title: 'Kurumlar vergisi', desc: 'Kurumlar vergisi nasıl hesaplanır ve kabul edilen gider nedir.' },
          { href: '/blog/personalen-danok-na-dohod', title: 'Kişisel gelir vergisi', desc: 'Gerçek kişiler için gelir vergisi, oranlar ve beyan yükümlülükleri.' },
          { href: '/blog/godishna-danocna-prijava-2026', title: 'Yıllık vergi beyannamesi', desc: 'Yıllık vergi beyannamesini kim, ne zaman ve nasıl verir.' },
        ],
      },
      {
        heading: 'Faturalama',
        items: [
          { href: '/blog/kako-da-napravite-faktura', title: 'Nasıl fatura oluşturulur', desc: 'Doğru bir fatura hazırlamanın tüm süreci adım adım.' },
          { href: '/blog/faktura-vs-proforma', title: 'Fatura ile proforma karşılaştırması', desc: 'Aradaki fark nedir ve her biri ne zaman kullanılır.' },
          { href: '/blog/zadolzitelni-elementi-faktura', title: 'Faturanın zorunlu unsurları', desc: 'Yasaya göre her faturanın içermesi gerekenler.' },
          { href: '/blog/sto-e-e-faktura', title: 'e-Fatura nedir', desc: 'Elektronik faturanın temelleri ve neden önemli olduğu.' },
        ],
      },
      {
        heading: 'Muhasebe ve raporlar',
        items: [
          { href: '/blog/smetkovodstvo-za-pocetnici', title: 'Yeni başlayanlar için muhasebe', desc: 'Sıfırdan başlayan her girişimci için temel kavramlar.' },
          { href: '/blog/bilans-na-sostojba', title: 'Bilanço', desc: 'Bilançonun neyi gösterdiği ve nasıl okunacağı.' },
          { href: '/blog/cash-flow-mk', title: 'Nakit akış raporu', desc: 'Şirketteki nakit giriş ve çıkışlarını nasıl izleyeceğiniz.' },
          { href: '/blog/digitalno-smetkovodstvo', title: 'Dijital muhasebe', desc: 'Otomatik, bulut tabanlı muhasebenin avantajları.' },
        ],
      },
    ],
    ctaTitle: 'Tüm muhasebenizi otomatikleştirin',
    ctaSub: 'Facturino faturaları, KDV\'yi, maaşları ve e-faturayı şirketiniz için tek bir platformda birleştirir.',
    ctaButton: 'Ücretsiz başla — 14 gün',
  },
  en: {
    badge: 'North Macedonia business hub',
    h1: 'Business Resources for North Macedonia',
    subtitle:
      'Everything you need to start and run a company in North Macedonia in one place — free tools, in-depth guides and practical calculators.',
    categories: [
      {
        heading: 'Free tools',
        items: [
          { href: '/alati/ddv-kalkulator', title: 'VAT calculator', desc: 'Calculate VAT for all rates — 18%, 5% and 10%, with reverse calculation.' },
          { href: '/alati/plata-kalkulator', title: 'Gross-to-net salary calculator', desc: 'Convert gross to net salary and back with accurate 2026 rates.' },
          { href: '/alati/kamaten-kalkulator', title: 'Interest calculator', desc: 'Calculate penalty interest at the statutory rate for late payments.' },
          { href: '/alati/danok-dobivka-kalkulator', title: 'Corporate tax calculator', desc: 'Calculate 10% corporate tax with recognized and non-deductible expenses.' },
          { href: '/alati/danocen-kalendar', title: 'Tax calendar', desc: 'All tax deadlines for the year: MPIN, DDV-04, annual accounts.' },
          { href: '/alati/kalkulator-registracija-firma', title: 'Calculator: company registration costs', desc: 'How much it costs to start a company — DOOEL, DOO or sole trader.' },
          { href: '/alati/efaktura-proverka', title: 'e-Invoice UBL validator', desc: 'Check whether your UBL XML is valid before submitting to UJP.' },
        ],
      },
      {
        heading: 'E-invoicing',
        items: [
          { href: '/e-faktura/vodic', title: 'Complete e-invoicing guide 2026', desc: 'Everything about mandatory e-invoicing in North Macedonia in one place.' },
          { href: '/e-faktura/rokovi-2026', title: 'Deadlines 2026/2027', desc: 'The key dates for the phased rollout of e-invoicing.' },
          { href: '/e-faktura/kako-da-izdadete', title: 'How to issue an e-invoice', desc: 'A step-by-step guide to issuing and sending an e-invoice.' },
          { href: '/e-faktura/za-javni-nabavki', title: 'E-invoice for public procurement', desc: 'Specific rules when invoicing government institutions.' },
          { href: '/e-faktura/casti-prasanja', title: 'Frequently asked questions', desc: 'Answers to the most common questions about e-invoicing.' },
        ],
      },
      {
        heading: 'Starting a company',
        items: [
          { href: '/otvori-firma', title: 'Complete guide to starting a company', desc: 'From idea to registered company — the whole process explained.' },
          { href: '/otvori-firma/dooel-ili-doo', title: 'DOOEL, DOO or sole trader?', desc: 'Which legal form is best for your business and why.' },
          { href: '/otvori-firma/paushal-ili-ddv', title: 'Flat-rate or VAT payer?', desc: 'A comparison of the two tax regimes and when each pays off.' },
          { href: '/otvori-firma/za-stranci', title: 'Starting a company for foreigners', desc: 'How a foreign national can open a company in North Macedonia.' },
          { href: '/blog/paket-za-nova-firma', title: 'New company checklist', desc: 'A practical list with all the steps after registration.' },
        ],
      },
      {
        heading: 'Payroll & labor',
        items: [
          { href: '/blog/presmetka-na-plata-mk', title: 'Salary calculation', desc: 'How salary is calculated in North Macedonia — contributions and tax.' },
          { href: '/blog/mpin-obrazec', title: 'MPIN form', desc: 'What MPIN is, when it is filed and how it is completed.' },
          { href: '/blog/trudovo-pravo-osnovi', title: 'Labor law basics', desc: 'The rights and obligations of employers and employees.' },
          { href: '/blog/rok-za-plata-makedonija', title: 'Salary payment deadline', desc: 'The legal deadlines for paying salaries and the consequences of delay.' },
        ],
      },
      {
        heading: 'Taxes & VAT',
        items: [
          { href: '/blog/ddv-vodich-mk', title: 'VAT guide', desc: 'Registration, rates, DDV-04 and calculating value-added tax.' },
          { href: '/blog/danok-na-dobivka', title: 'Corporate tax', desc: 'How corporate tax is calculated and what counts as a recognized expense.' },
          { href: '/blog/personalen-danok-na-dohod', title: 'Personal income tax', desc: 'Income tax for individuals, rates and reporting obligations.' },
          { href: '/blog/godishna-danocna-prijava-2026', title: 'Annual tax return', desc: 'Who files the annual tax return, when and how.' },
        ],
      },
      {
        heading: 'Invoicing',
        items: [
          { href: '/blog/kako-da-napravite-faktura', title: 'How to create an invoice', desc: 'The whole process of preparing a correct invoice, step by step.' },
          { href: '/blog/faktura-vs-proforma', title: 'Invoice vs. proforma', desc: 'What the difference is and when each one is used.' },
          { href: '/blog/zadolzitelni-elementi-faktura', title: 'Mandatory invoice elements', desc: 'What every invoice must contain according to the law.' },
          { href: '/blog/sto-e-e-faktura', title: 'What is an e-invoice', desc: 'The basics of the electronic invoice and why it matters.' },
        ],
      },
      {
        heading: 'Accounting & reports',
        items: [
          { href: '/blog/smetkovodstvo-za-pocetnici', title: 'Accounting for beginners', desc: 'Core concepts for every entrepreneur starting from scratch.' },
          { href: '/blog/bilans-na-sostojba', title: 'Balance sheet', desc: 'What the balance sheet shows and how to read it.' },
          { href: '/blog/cash-flow-mk', title: 'Cash flow statement', desc: 'How to track the inflow and outflow of cash in your company.' },
          { href: '/blog/digitalno-smetkovodstvo', title: 'Digital accounting', desc: 'The advantages of automated, cloud-based accounting.' },
        ],
      },
    ],
    ctaTitle: 'Automate your entire accounting',
    ctaSub: 'Facturino unifies invoices, VAT, payroll and e-invoicing into a single platform for your company.',
    ctaButton: 'Start free — 14 days',
  },
} as const

const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

const homeLabels = { mk: 'Почетна', sq: 'Ballina', tr: 'Ana Sayfa', en: 'Home' } as const
const resursiLabels = { mk: 'Ресурси', sq: 'Burime', tr: 'Kaynaklar', en: 'Resources' } as const

export default async function ResourcesIndexPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabels[locale], href: `/${locale}` },
    { name: resursiLabels[locale], href: `/${locale}/resursi` },
  ])

  const collectionLd = {
    '@context': 'https://schema.org',
    '@type': 'CollectionPage',
    name: t.h1,
    description: t.subtitle,
    url: `https://www.facturino.mk/${locale}/resursi`,
    inLanguage: locale,
    mainEntity: {
      '@type': 'ItemList',
      itemListElement: t.categories.map((cat, i) => ({
        '@type': 'ListItem',
        position: i + 1,
        name: cat.heading,
      })),
    },
  }

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(collectionLd) }} />

      {/* Hero Section */}
      <section className="relative overflow-hidden pt-20 pb-10 md:pt-32 md:pb-20">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-3xl mx-auto">
          <span className="inline-flex items-center rounded-full bg-white/15 backdrop-blur-sm px-4 py-1.5 text-sm font-semibold text-white border border-white/20 mb-6">
            {t.badge}
          </span>
          <h1 className="text-3xl md:text-5xl font-extrabold tracking-tight leading-tight mb-4">
            {t.h1}
          </h1>
          <p className="text-lg md:text-xl text-indigo-100 leading-relaxed">
            {t.subtitle}
          </p>
        </div>
      </section>

      {/* Category Sections */}
      <section className="section">
        <div className="container max-w-5xl mx-auto space-y-16">
          {t.categories.map((cat) => (
            <div key={cat.heading}>
              <h2 className="text-2xl md:text-3xl font-extrabold text-gray-900 mb-6 tracking-tight">
                {cat.heading}
              </h2>
              <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                {cat.items.map((item) => (
                  <Link
                    key={item.href}
                    href={`/${locale}${item.href}`}
                    className="group p-5 md:p-6 rounded-2xl bg-white border border-gray-200 hover:border-indigo-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300"
                  >
                    <h3 className="text-base md:text-lg font-bold text-gray-900 mb-2 group-hover:text-indigo-600 transition-colors">
                      {item.title}
                    </h3>
                    <p className="text-sm text-gray-600 leading-relaxed">{item.desc}</p>
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-12 lg:py-24 relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600" />
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
          <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl" />
        </div>
        <div className="container relative z-10 text-center text-white max-w-2xl mx-auto">
          <h2 className="text-3xl md:text-4xl font-extrabold mb-4 tracking-tight">{t.ctaTitle}</h2>
          <p className="text-xl text-indigo-100 mb-8">{t.ctaSub}</p>
          <a
            href={`${APP_URL}/signup`}
            className="inline-block px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
          >
            {t.ctaButton}
          </a>
        </div>
      </section>
    </main>
  )
}

// CLAUDE-CHECKPOINT
