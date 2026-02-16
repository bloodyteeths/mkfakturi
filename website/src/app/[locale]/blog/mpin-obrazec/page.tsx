import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/mpin-obrazec', {
    title: {
      mk: 'МПИН образец: Водич за месечна пресметка — Facturino',
      en: 'MPIN Form: Monthly Payroll Filing Guide — Facturino',
      sq: 'Formulari MPIN: Udhëzues për llogaritjen mujore — Facturino',
      tr: 'MPIN formu: Aylık bordro beyanname rehberi — Facturino',
    },
    description: {
      mk: 'Научете како правилно да го пополните МПИН образецот за месечна пресметка на плати и придонеси. Рокови, потребни податоци и електронско поднесување.',
      en: 'Learn how to correctly fill out the MPIN form for monthly payroll contributions. Deadlines, required data, and electronic filing guide.',
      sq: 'Mësoni si ta plotësoni saktë formularin MPIN për kontributet mujore të pagave. Afatet, të dhënat e nevojshme dhe dorëzimi elektronik.',
      tr: 'MPIN formunu aylık bordro katkıları için doğru şekilde nasıl dolduracağınızı öğrenin. Son tarihler, gerekli veriler ve elektronik beyanname rehberi.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'МПИН образец: Водич за месечна пресметка',
    publishDate: '17 февруари 2026',
    readTime: '7 мин читање',
    intro: 'МПИН образецот е еден од најважните документи за секој работодавач во Македонија. Секој месец, компаниите се должни да поднесат месечна пресметка за интегрирана наплата (МПИН) до Управата за јавни приходи (УЈП). Овој водич детално објаснува што е МПИН, кои податоци се потребни, како да го поднесете електронски и како да избегнете казни за задоцнето поднесување.',
    sections: [
      {
        title: 'Што е МПИН образец?',
        content: 'МПИН (Месечна Пресметка за Интегрирана Наплата) е задолжителен образец кој работодавачите го поднесуваат до УЈП секој месец. Овој документ ги содржи сите информации за пресметаните плати, даноците на личен доход и социјалните придонеси (пензиско, здравствено и осигурување во случај на невработеност) за сите вработени во компанијата. МПИН образецот е основата на даночната евиденција за вработени лица и е клучен за правилно функционирање на системот за социјално осигурување.',
        items: null,
        steps: null,
      },
      {
        title: 'Рок за поднесување и казни',
        content: 'МПИН образецот мора да се поднесе најдоцна до 15-ти во месецот по месецот за кој се врши пресметката. На пример, МПИН за јануари мора да се поднесе до 15 февруари. Ако рокот паѓа на викенд или државен празник, крајниот рок се поместува на следниот работен ден.',
        items: [
          'Казна за задоцнето поднесување: од 500 до 1.000 евра во денарска противвредност за правни лица',
          'Казна за одговорно лице: од 250 до 500 евра во денарска противвредност',
          'Повторно прекршување: казната може да се зголеми двојно',
          'Неподнесување повеќе од 3 месеци: може да доведе до даночна контрола',
          'Камата за задоцнето плаќање на придонеси: 0,03% дневно',
        ],
        steps: null,
      },
      {
        title: 'Потребни податоци за МПИН',
        content: 'За правилно пополнување на МПИН образецот потребни се детални информации за секој вработен. Подготовката на овие податоци може да биде комплексна, особено за компании со поголем број вработени.',
        items: [
          'ЕМБГ на вработениот и матичен број на работодавачот (ЕМБС)',
          'Бруто плата и нето плата за секој вработен',
          'Пресметка на персонален данок на доход (10%)',
          'Придонес за пензиско и инвалидско осигурување (18,8%)',
          'Придонес за здравствено осигурување (7,5%)',
          'Придонес за осигурување во случај на невработеност (1,2%)',
          'Додатоци за прекувремена работа, ноќна работа, работа на празник',
          'Боледувања, годишни одмори и други отсуства',
          'Број на работни часови и работни денови во месецот',
        ],
        steps: null,
      },
      {
        title: 'Како да поднесете МПИН електронски',
        content: null,
        items: null,
        steps: [
          { step: 'Регистрирајте се на е-Даноци порталот', desc: 'Посетете го порталот etax.ujp.gov.mk и регистрирајте се со дигитален сертификат. Потребен е квалификуван електронски потпис издаден од овластен издавач.' },
          { step: 'Подгответе ги податоците', desc: 'Соберете ги сите потребни информации за вработените: бруто плати, работни часови, отсуства и додатоци. Проверете дали ЕМБГ-ата се точни.' },
          { step: 'Пополнете го образецот', desc: 'Внесете ги податоците во МПИН образецот на порталот или импортирајте XML датотека генерирана од софтвер за плати.' },
          { step: 'Проверете ги пресметките', desc: 'Системот автоматски ги пресметува придонесите. Споредете ги со вашите пресметки и коригирајте ги евентуалните грешки.' },
          { step: 'Потпишете и поднесете', desc: 'Потпишете го образецот со дигитален сертификат и поднесете го. Зачувајте ја потврдата за поднесување.' },
        ],
      },
      {
        title: 'Најчести грешки при пополнување',
        content: 'Грешките во МПИН образецот може да доведат до одбивање на пресметката или дополнителни казни. Еве ги најчестите проблеми кои работодавачите ги среќаваат и како да ги избегнете.',
        items: [
          'Погрешен ЕМБГ на вработен — проверете го секој број двапати пред поднесување',
          'Неточна бруто плата — особено внимавајте на прекувремена работа и бонуси',
          'Неправилна пресметка на придонеси — користете ажурирани стапки',
          'Пропуштени вработени — проверете дали сите активни вработени се вклучени',
          'Неправилно пријавување на боледување — разликувајте боледување до 30 дена (на товар на работодавач) и над 30 дена (ФЗОМ)',
          'Погрешен период на пресметка — внимавајте да го изберете правилниот месец',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino го автоматизира МПИН',
        content: 'Facturino го елиминира ризикот од грешки и го поедноставува целиот процес на поднесување на МПИН. Нашиот систем автоматски ги пресметува сите придонеси врз основа на внесените бруто плати, ги валидира ЕМБГ-ата и генерира XML датотека подготвена за директно поднесување на е-Даноци порталот. Со Facturino, целиот процес кој нормално трае часови се завршува за неколку минути.',
        items: [
          'Автоматска пресметка на сите придонеси и персонален данок',
          'Валидација на ЕМБГ и ЕМБС пред поднесување',
          'Генерирање на УЈП-компатибилен XML фајл',
          'Автоматско пресметување на прекувремена работа и додатоци',
          'Историја на сите поднесени МПИН образци',
          'Потсетник пред истекот на рокот за поднесување',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Автоматизирајте го МПИН процесот',
      desc: 'Заштедете време и избегнете грешки со автоматско генерирање на МПИН образци.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'MPIN Form: Monthly Payroll Filing Guide',
    publishDate: 'February 17, 2026',
    readTime: '7 min read',
    intro: 'The MPIN form is one of the most important documents for every employer in Macedonia. Each month, companies are required to submit a Monthly Payroll Contribution Report (MPIN) to the Public Revenue Office (UJP). This guide explains in detail what MPIN is, what data is required, how to file electronically, and how to avoid penalties for late submission.',
    sections: [
      {
        title: 'What is the MPIN Form?',
        content: 'MPIN (Monthly Calculation for Integrated Collection) is a mandatory form that employers submit to UJP every month. This document contains all information about calculated salaries, personal income taxes, and social contributions (pension, health, and unemployment insurance) for all employees in the company. The MPIN form is the foundation of tax records for employed individuals and is crucial for the proper functioning of the social insurance system.',
        items: null,
        steps: null,
      },
      {
        title: 'Filing Deadline and Penalties',
        content: 'The MPIN form must be submitted no later than the 15th of the month following the calculation period. For example, the MPIN for January must be filed by February 15th. If the deadline falls on a weekend or public holiday, it is extended to the next business day.',
        items: [
          'Late filing penalty: EUR 500 to 1,000 equivalent in denars for legal entities',
          'Penalty for the responsible person: EUR 250 to 500 equivalent in denars',
          'Repeat offenses: the fine can be doubled',
          'Non-filing for more than 3 months: may trigger a tax audit',
          'Late payment interest on contributions: 0.03% daily',
        ],
        steps: null,
      },
      {
        title: 'Required Data for MPIN',
        content: 'Properly filling out the MPIN form requires detailed information for each employee. Preparing this data can be complex, especially for companies with a larger number of employees.',
        items: [
          'Employee EMBG (unique citizen number) and employer EMBS (registration number)',
          'Gross salary and net salary for each employee',
          'Personal income tax calculation (10%)',
          'Pension and disability insurance contribution (18.8%)',
          'Health insurance contribution (7.5%)',
          'Unemployment insurance contribution (1.2%)',
          'Supplements for overtime, night work, and holiday work',
          'Sick leave, annual leave, and other absences',
          'Number of working hours and working days in the month',
        ],
        steps: null,
      },
      {
        title: 'How to File MPIN Electronically',
        content: null,
        items: null,
        steps: [
          { step: 'Register on the e-Tax Portal', desc: 'Visit the etax.ujp.gov.mk portal and register with a digital certificate. A qualified electronic signature issued by an authorized provider is required.' },
          { step: 'Prepare the Data', desc: 'Gather all required employee information: gross salaries, working hours, absences, and supplements. Verify that all EMBG numbers are correct.' },
          { step: 'Fill Out the Form', desc: 'Enter the data into the MPIN form on the portal or import an XML file generated by payroll software.' },
          { step: 'Verify Calculations', desc: 'The system automatically calculates contributions. Compare them with your own calculations and correct any discrepancies.' },
          { step: 'Sign and Submit', desc: 'Sign the form with your digital certificate and submit it. Save the submission confirmation receipt.' },
        ],
      },
      {
        title: 'Common Filing Errors',
        content: 'Errors in the MPIN form can lead to rejection of the calculation or additional penalties. Here are the most common problems employers encounter and how to avoid them.',
        items: [
          'Incorrect employee EMBG — double-check each number before submission',
          'Wrong gross salary — pay special attention to overtime and bonuses',
          'Incorrect contribution calculations — use updated rates',
          'Missing employees — verify that all active employees are included',
          'Improper sick leave reporting — distinguish between sick leave up to 30 days (employer-covered) and over 30 days (FZOM-covered)',
          'Wrong calculation period — make sure you select the correct month',
        ],
        steps: null,
      },
      {
        title: 'How Facturino Automates MPIN',
        content: 'Facturino eliminates the risk of errors and simplifies the entire MPIN filing process. Our system automatically calculates all contributions based on entered gross salaries, validates EMBG numbers, and generates an XML file ready for direct submission to the e-Tax portal. With Facturino, a process that normally takes hours is completed in just minutes.',
        items: [
          'Automatic calculation of all contributions and personal income tax',
          'EMBG and EMBS validation before submission',
          'Generation of UJP-compatible XML file',
          'Automatic overtime and supplement calculations',
          'History of all submitted MPIN forms',
          'Reminder before the filing deadline',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Automate Your MPIN Process',
      desc: 'Save time and avoid errors with automatic MPIN form generation.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Formulari MPIN: Udhëzues për llogaritjen mujore',
    publishDate: '17 shkurt 2026',
    readTime: '7 min lexim',
    intro: 'Formulari MPIN është një nga dokumentet më të rëndësishme për çdo punëdhënës në Maqedoni. Çdo muaj, kompanitë janë të detyruara të dorëzojnë një Raport Mujor të Kontributeve të Pagave (MPIN) në Zyrën e të Ardhurave Publike (UJP). Ky udhëzues shpjegon në detaje çfarë është MPIN, cilat të dhëna kërkohen, si të dorëzohet elektronikisht dhe si të shmangni gjobat për dorëzim të vonuar.',
    sections: [
      {
        title: 'Çfarë është formulari MPIN?',
        content: 'MPIN (Llogaritja Mujore për Arkëtim të Integruar) është formular i detyrueshëm që punëdhënësit e dorëzojnë në UJP çdo muaj. Ky dokument përmban të gjitha informacionet për pagat e llogaritura, tatimet mbi të ardhurat personale dhe kontributet sociale (pensioni, shëndetësia dhe sigurimi nga papunësia) për të gjithë punonjësit në kompani. Formulari MPIN është themeli i evidencës tatimore për personat e punësuar dhe është vendimtar për funksionimin e duhur të sistemit të sigurimit social.',
        items: null,
        steps: null,
      },
      {
        title: 'Afati i dorëzimit dhe gjobat',
        content: 'Formulari MPIN duhet të dorëzohet jo më vonë se data 15 e muajit pas periudhës së llogaritjes. Për shembull, MPIN për janar duhet të dorëzohet deri më 15 shkurt. Nëse afati bie në fundjavë ose festë zyrtare, ai shtyhet në ditën e ardhshme të punës.',
        items: [
          'Gjobë për dorëzim të vonuar: 500 deri 1.000 euro ekuivalent në denarë për persona juridikë',
          'Gjobë për personin përgjegjës: 250 deri 500 euro ekuivalent në denarë',
          'Shkelje e përsëritur: gjoba mund të dyfishohet',
          'Mosdorëzimi për më shumë se 3 muaj: mund të shkaktojë kontroll tatimor',
          'Kamatë për vonesë pagese të kontributeve: 0,03% ditore',
        ],
        steps: null,
      },
      {
        title: 'Të dhënat e nevojshme për MPIN',
        content: 'Plotësimi i duhur i formularit MPIN kërkon informacione të detajuara për çdo punonjës. Përgatitja e këtyre të dhënave mund të jetë komplekse, veçanërisht për kompanitë me numër më të madh punonjësish.',
        items: [
          'EMBG i punonjësit (numri unik i qytetarit) dhe EMBS i punëdhënësit (numri i regjistrimit)',
          'Paga bruto dhe paga neto për çdo punonjës',
          'Llogaritja e tatimit mbi të ardhurat personale (10%)',
          'Kontributi për sigurim pensional dhe invalidor (18,8%)',
          'Kontributi për sigurim shëndetësor (7,5%)',
          'Kontributi për sigurim nga papunësia (1,2%)',
          'Shtesa për punë jashtë orarit, punë natën dhe punë në festë',
          'Pushim mjekësor, pushim vjetor dhe mungesat e tjera',
          'Numri i orëve dhe ditëve të punës në muaj',
        ],
        steps: null,
      },
      {
        title: 'Si të dorëzoni MPIN elektronikisht',
        content: null,
        items: null,
        steps: [
          { step: 'Regjistrohuni në portalin e-Tatimi', desc: 'Vizitoni portalin etax.ujp.gov.mk dhe regjistrohuni me certifikatë digjitale. Kërkohet nënshkrim elektronik i kualifikuar i lëshuar nga ofrues i autorizuar.' },
          { step: 'Përgatitni të dhënat', desc: 'Mblidhni të gjitha informacionet e nevojshme për punonjësit: pagat bruto, orët e punës, mungesat dhe shtesat. Verifikoni që të gjitha numrat EMBG janë të sakta.' },
          { step: 'Plotësoni formularin', desc: 'Futni të dhënat në formularin MPIN në portal ose importoni skedar XML të gjeneruar nga softueri i pagave.' },
          { step: 'Verifikoni llogaritjet', desc: 'Sistemi llogarit automatikisht kontributet. Krahasoni ato me llogaritjet tuaja dhe korrigjoni mospërputhjet.' },
          { step: 'Nënshkruani dhe dorëzoni', desc: 'Nënshkruani formularin me certifikatën tuaj digjitale dhe dorëzojeni. Ruani konfirmimin e dorëzimit.' },
        ],
      },
      {
        title: 'Gabimet më të zakonshme',
        content: 'Gabimet në formularin MPIN mund të çojnë në refuzimin e llogaritjes ose gjoba shtesë. Ja problemet më të zakonshme që punëdhënësit hasin dhe si t\'i shmangni ato.',
        items: [
          'EMBG i gabuar i punonjësit — kontrolloni çdo numër dy herë para dorëzimit',
          'Pagë bruto e pasaktë — kushtoni vëmendje të veçantë punës jashtë orarit dhe bonuseve',
          'Llogaritje e gabuar e kontributeve — përdorni normat e përditësuara',
          'Punonjës të munguar — verifikoni që të gjithë punonjësit aktivë janë përfshirë',
          'Raportim i gabuar i pushimit mjekësor — dalloni pushimin deri 30 ditë (në ngarkim të punëdhënësit) dhe mbi 30 ditë (FZOM)',
          'Periudhë e gabuar llogaritjeje — sigurohuni që keni zgjedhur muajin e duhur',
        ],
        steps: null,
      },
      {
        title: 'Si e automatizon Facturino MPIN-in',
        content: 'Facturino eliminon rrezikun e gabimeve dhe thjeshton të gjithë procesin e dorëzimit të MPIN. Sistemi ynë llogarit automatikisht të gjitha kontributet bazuar në pagat bruto të futura, validon numrat EMBG dhe gjeneron skedar XML gati për dorëzim direkt në portalin e-Tatimi. Me Facturino, një proces që normalisht zgjat orë përfundon në vetëm disa minuta.',
        items: [
          'Llogaritje automatike e të gjitha kontributeve dhe tatimit mbi të ardhurat',
          'Validim i EMBG dhe EMBS para dorëzimit',
          'Gjenerim i skedarit XML të përputhshëm me UJP',
          'Llogaritje automatike e punës jashtë orarit dhe shtesave',
          'Histori e të gjitha formularëve MPIN të dorëzuara',
          'Kujtesë para afatit të dorëzimit',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Automatizoni procesin tuaj MPIN',
      desc: 'Kurseni kohë dhe shmangni gabimet me gjenerim automatik të formularëve MPIN.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'MPIN formu: Aylık bordro beyanname rehberi',
    publishDate: '17 Şubat 2026',
    readTime: '7 dk okuma',
    intro: 'MPIN formu, Makedonya\'daki her işveren için en önemli belgelerden biridir. Her ay şirketler, Kamu Gelir İdaresi\'ne (UJP) Aylık Bordro Katkı Raporu (MPIN) sunmak zorundadır. Bu rehber, MPIN\'in ne olduğunu, hangi verilerin gerektiğini, elektronik olarak nasıl dosyalanacağını ve geç başvuru cezalarından nasıl kaçınılacağını ayrıntılı olarak açıklamaktadır.',
    sections: [
      {
        title: 'MPIN Formu Nedir?',
        content: 'MPIN (Entegre Tahsilat için Aylık Hesaplama), işverenlerin her ay UJP\'ye sunduğu zorunlu bir formdur. Bu belge, şirketteki tüm çalışanlar için hesaplanan maaşlar, gelir vergileri ve sosyal katkılar (emeklilik, sağlık ve işsizlik sigortası) hakkındaki tüm bilgileri içerir. MPIN formu, çalışan bireyler için vergi kayıtlarının temelidir ve sosyal sigorta sisteminin düzgün işlemesi için hayati önem taşır.',
        items: null,
        steps: null,
      },
      {
        title: 'Başvuru Süresi ve Cezalar',
        content: 'MPIN formu, hesaplama dönemini takip eden ayın 15\'ine kadar sunulmalıdır. Örneğin, Ocak ayı MPIN\'i 15 Şubat\'a kadar dosyalanmalıdır. Son tarih hafta sonuna veya resmi tatile denk gelirse, bir sonraki iş gününe uzatılır.',
        items: [
          'Geç başvuru cezası: Tüzel kişiler için 500 ila 1.000 avro karşılığı denar',
          'Sorumlu kişi için ceza: 250 ila 500 avro karşılığı denar',
          'Tekrarlayan ihlaller: ceza iki katına çıkabilir',
          '3 aydan fazla dosyalanmama: vergi denetimine yol açabilir',
          'Katkı paylarında geç ödeme faizi: günlük %0,03',
        ],
        steps: null,
      },
      {
        title: 'MPIN için Gerekli Veriler',
        content: 'MPIN formunun düzgün doldurulması, her çalışan için ayrıntılı bilgi gerektirir. Bu verilerin hazırlanması, özellikle daha fazla çalışanı olan şirketler için karmaşık olabilir.',
        items: [
          'Çalışan EMBG\'si (benzersiz vatandaş numarası) ve işveren EMBS\'si (kayıt numarası)',
          'Her çalışan için brüt maaş ve net maaş',
          'Kişisel gelir vergisi hesaplaması (%10)',
          'Emeklilik ve maluliyet sigortası katkısı (%18,8)',
          'Sağlık sigortası katkısı (%7,5)',
          'İşsizlik sigortası katkısı (%1,2)',
          'Fazla mesai, gece çalışması ve tatil çalışması ek ödemeleri',
          'Hastalık izni, yıllık izin ve diğer devamsızlıklar',
          'Aydaki çalışma saatleri ve çalışma günleri sayısı',
        ],
        steps: null,
      },
      {
        title: 'MPIN Elektronik Olarak Nasıl Dosyalanır',
        content: null,
        items: null,
        steps: [
          { step: 'e-Vergi Portalına Kaydolun', desc: 'etax.ujp.gov.mk portalını ziyaret edin ve dijital sertifika ile kaydolun. Yetkili bir sağlayıcı tarafından verilen nitelikli elektronik imza gereklidir.' },
          { step: 'Verileri Hazırlayın', desc: 'Tüm gerekli çalışan bilgilerini toplayın: brüt maaşlar, çalışma saatleri, devamsızlıklar ve ek ödemeler. Tüm EMBG numaralarının doğru olduğunu kontrol edin.' },
          { step: 'Formu Doldurun', desc: 'Portaldaki MPIN formuna verileri girin veya bordro yazılımı tarafından oluşturulan XML dosyasını içe aktarın.' },
          { step: 'Hesaplamaları Doğrulayın', desc: 'Sistem katkı paylarını otomatik olarak hesaplar. Bunları kendi hesaplamalarınızla karşılaştırın ve tutarsızlıkları düzeltin.' },
          { step: 'İmzalayın ve Gönderin', desc: 'Formu dijital sertifikanızla imzalayın ve gönderin. Gönderim onay makbuzunu saklayın.' },
        ],
      },
      {
        title: 'Yaygın Dosyalama Hataları',
        content: 'MPIN formundaki hatalar, hesaplamanın reddedilmesine veya ek cezalara yol açabilir. İşverenlerin karşılaştığı en yaygın sorunlar ve bunlardan nasıl kaçınılacağı aşağıda verilmiştir.',
        items: [
          'Yanlış çalışan EMBG\'si — göndermeden önce her numarayı iki kez kontrol edin',
          'Hatalı brüt maaş — fazla mesai ve ikramiyelere özellikle dikkat edin',
          'Yanlış katkı hesaplamaları — güncel oranları kullanın',
          'Eksik çalışanlar — tüm aktif çalışanların dahil edildiğini doğrulayın',
          'Uygunsuz hastalık izni bildirimi — 30 güne kadar (işveren tarafından karşılanan) ve 30 günün üzerindeki (FZOM tarafından karşılanan) hastalık izinlerini ayırt edin',
          'Yanlış hesaplama dönemi — doğru ayı seçtiğinizden emin olun',
        ],
        steps: null,
      },
      {
        title: 'Facturino MPIN\'i Nasıl Otomatikleştirir',
        content: 'Facturino, hata riskini ortadan kaldırır ve tüm MPIN dosyalama sürecini basitleştirir. Sistemimiz, girilen brüt maaşlara dayalı olarak tüm katkı paylarını otomatik olarak hesaplar, EMBG numaralarını doğrular ve e-Vergi portalına doğrudan gönderime hazır bir XML dosyası oluşturur. Facturino ile normalde saatler süren bir süreç yalnızca birkaç dakikada tamamlanır.',
        items: [
          'Tüm katkı paylarının ve kişisel gelir vergisinin otomatik hesaplanması',
          'Gönderim öncesi EMBG ve EMBS doğrulaması',
          'UJP uyumlu XML dosyası oluşturma',
          'Otomatik fazla mesai ve ek ödeme hesaplamaları',
          'Gönderilen tüm MPIN formlarının geçmişi',
          'Dosyalama son tarihinden önce hatırlatma',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'MPIN Sürecinizi Otomatikleştirin',
      desc: 'Otomatik MPIN form oluşturma ile zaman kazanın ve hatalardan kaçının.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function MpinObrazecPage({
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
// CLAUDE-CHECKPOINT
