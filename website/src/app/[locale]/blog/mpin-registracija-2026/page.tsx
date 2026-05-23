import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/mpin-registracija-2026', {
    title: {
      mk: 'МПИН регистрација и поднесување 2026: Комплетен водич',
      en: 'MPIN Registration & Filing North Macedonia 2026: Complete Guide',
      sq: 'Regjistrimi dhe dorëzimi MPIN 2026: Udhëzues i plotë',
      tr: 'MPIN Kaydı ve Dosyalama 2026: Eksiksiz Rehber',
    },
    description: {
      mk: 'Што е МПИН, кој мора да се регистрира, постапка за регистрација во УЈП, месечни рокови за поднесување, потребни податоци и казни за задоцнување.',
      en: 'What is MPIN, who must register, UJP registration process, monthly filing deadlines, required data fields and late filing penalties in North Macedonia.',
      sq: 'Çfarë është MPIN, kush duhet të regjistrohet, procesi i regjistrimit në UJP, afatet mujore, të dhënat e nevojshme dhe gjobat për vonesë.',
      tr: 'MPIN nedir, kim kayıt olmalı, UJP kayıt süreci, aylık beyanname tarihleri, gerekli veriler ve gecikme cezaları.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'МПИН регистрација и поднесување 2026: Комплетен водич',
    publishDate: '23 мај 2026',
    readTime: '8 мин читање',
    intro: 'МПИН (Месечна Пресметка за Интегрирана Наплата) е задолжителна месечна пријава за секој работодавач во Македонија. Во овој водич детално објаснуваме што е МПИН, кој мора да се регистрира, целата постапка за регистрација во УЈП, месечните рокови, потребните податоци за секој вработен и казните за неподнесување или задоцнето поднесување.',
    sections: [
      {
        title: 'Што е МПИН?',
        content: 'МПИН значи Месечна Пресметка за Интегрирана Наплата (Monthly Integrated Payment Calculation). Тоа е месечната декларација за плати која секој работодавач мора да ја поднесе до Управата за јавни приходи (УЈП). МПИН ги покрива сите придонеси на вработените и персоналниот данок на доход. Се поднесува електронски преку порталот e-Tax (etax.ujp.gov.mk).',
        items: null,
        steps: null,
      },
      {
        title: 'Кој мора да поднесува МПИН?',
        content: 'Следните субјекти се должни да поднесуваат МПИН до УЈП:',
        items: [
          'Секој работодавач со барем 1 вработен',
          'Самовработени кои сами си исплаќаат плата',
          'Субјекти кои исплаќаат по авторски договор или привремена работа',
          'Странски работодавачи со вработени во Македонија',
          'Државни институции и јавни претпријатија',
        ],
        steps: null,
      },
      {
        title: 'Регистрација во системот',
        content: 'За да поднесувате МПИН, прво мора да се регистрирате како обврзник во системот на УЈП. Постапката вклучува:',
        items: null,
        steps: [
          { step: 'Регистрирајте се на etax.ujp.gov.mk', desc: 'Посетете го порталот etax.ujp.gov.mk и регистрирајте се како работодавач со дигитален сертификат.' },
          { step: 'Поднесете МПИН-1 образец', desc: 'Поднесете МПИН-1 образец (регистрација на обврзник за придонеси) во регионалната канцеларија на УЈП.' },
          { step: 'Добијте го вашиот МПИН идентификатор', desc: 'По обработката, добивате МПИН идентификатор кој одговара на вашиот ЕДБ (единствен даночен број).' },
          { step: 'Регистрирајте ги вработените со МПИН-2', desc: 'Регистрирајте го секој вработен со МПИН-2 образец (регистрација на вработен).' },
          { step: 'Инсталирајте софтвер или користете веб портал', desc: 'Инсталирајте го e-Tax софтверот или користете го веб порталот за месечно поднесување на МПИН.' },
        ],
      },
      {
        title: 'Месечни рокови',
        content: 'МПИН мора да се поднесе до 15-ти во СЛЕДНИОТ месец (ист рок како исплата на плата согласно чл. 106 од ЗРО).',
        items: [
          'Јануарска плата → МПИН се поднесува до 15 февруари',
          'Поднесување преку e-Tax портал (etax.ujp.gov.mk)',
          'Плаќање на придонеси истиот ден како поднесувањето',
          'Задоцнето поднесување = автоматска камата (0,03% дневно)',
          'Декемвриска плата → МПИН се поднесува до 15 јануари следната година',
        ],
        steps: null,
      },
      {
        title: 'Податоци во МПИН',
        content: 'МПИН образецот содржи детални информации за секој вработен. Еве ги задолжителните полиња:',
        items: [
          'Лични податоци на вработениот (ЕМБГ, име, адреса)',
          'Бруто плата',
          'Основица за придонеси (ограничена: мин. 31.577 / макс. 1.010.464 МКД)',
          'Придонес за пензиско осигурување (18,8% од основицата)',
          'Придонес за здравствено осигурување (7,5% од основицата)',
          'Придонес за осигурување од невработеност (1,2% од основицата)',
          'Дополнителен придонес (0,5% од основицата)',
          'Персонален данок на доход (10% од даночната основица)',
          'Работни часови и тип (редовни, прекувремени, ноќни)',
        ],
        steps: null,
      },
      {
        title: 'Казни за неподнесување',
        content: 'Неподнесувањето или задоцнетото поднесување на МПИН повлекува сериозни последици:',
        items: [
          'Казна од 1.000-3.000 евра за задоцнето поднесување (правно лице)',
          'Казна од 300-500 евра за одговорно лице',
          'Камата 0,03% дневно на неплатени придонеси',
          'УЈП може да ги блокира банкарските сметки за неплатени придонеси',
          'Повторени прекршувања → кривична одговорност',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'mpin-obrazec', title: 'МПИН образец: Водич за месечна пресметка' },
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија: Придонеси и даноци' },
      { slug: 'trudovo-pravo-osnovi', title: 'Трудово право: 10 работи што секој работодавач мора да ги знае' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Неисплатена плата: Како да пријавите до Инспекторат за труд' },
      { slug: 'rok-za-plata-makedonija', title: 'Рок за исплата на плата: Што вели законот' },
    ],
    cta: {
      title: 'МПИН автоматски, без грешки',
      desc: 'Facturino генерира МПИН од пресметаните плати — поднесете за 2 клика.',
      button: 'Започнете бесплатно →',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'MPIN Registration & Filing North Macedonia 2026: Complete Guide',
    publishDate: 'May 23, 2026',
    readTime: '8 min read',
    intro: 'MPIN (Monthly Integrated Payment Calculation) is the mandatory monthly payroll declaration for every employer in North Macedonia. This guide covers what MPIN is, who must register, the full UJP registration process, monthly filing deadlines, required data fields for each employee, and penalties for late or missed filings.',
    sections: [
      {
        title: 'What is MPIN?',
        content: 'MPIN stands for Monthly Integrated Payment Calculation (Месечна Пресметка за Интегрирана Наплата). It is the monthly payroll declaration that every employer must submit to UJP (Public Revenue Office). MPIN covers all employee contributions and personal income tax. It is submitted electronically via the e-Tax portal (etax.ujp.gov.mk).',
        items: null,
        steps: null,
      },
      {
        title: 'Who Must File MPIN?',
        content: 'The following entities are required to file MPIN with UJP:',
        items: [
          'Every employer with at least 1 employee',
          'Self-employed individuals who pay themselves a salary',
          'Entities paying per-contract (author agreements) or temporary work',
          'Foreign employers with employees in North Macedonia',
          'State institutions and public enterprises',
        ],
        steps: null,
      },
      {
        title: 'System Registration',
        content: 'To file MPIN, you must first register as a contribution payer in the UJP system. The process includes:',
        items: null,
        steps: [
          { step: 'Register at etax.ujp.gov.mk', desc: 'Visit the etax.ujp.gov.mk portal and register as an employer using a digital certificate.' },
          { step: 'Submit the MPIN-1 form', desc: 'Submit the MPIN-1 form (contribution payer registration) at the regional UJP office.' },
          { step: 'Receive your MPIN identifier', desc: 'After processing, you receive your MPIN identifier which matches your EDB (unique tax number).' },
          { step: 'Register employees with MPIN-2', desc: 'Register each employee using the MPIN-2 form (employee registration).' },
          { step: 'Install software or use web portal', desc: 'Install the e-Tax software or use the web portal for monthly MPIN submissions.' },
        ],
      },
      {
        title: 'Monthly Deadlines',
        content: 'MPIN must be filed by the 15th of the FOLLOWING month (same deadline as salary payment per Art. 106 Labor Relations Law).',
        items: [
          'January salary → MPIN due by February 15',
          'Filing via e-Tax portal (etax.ujp.gov.mk)',
          'Payment of contributions same day as filing',
          'Late filing = automatic interest (0.03% daily)',
          'December salary → MPIN due by January 15 of next year',
        ],
        steps: null,
      },
      {
        title: 'Required Data in MPIN',
        content: 'The MPIN form contains detailed information for each employee. Here are the mandatory fields:',
        items: [
          'Employee personal data (EMBG, name, address)',
          'Gross salary amount',
          'Contribution base (capped at min 31,577 / max 1,010,464 MKD)',
          'Pension contribution (18.8% of base)',
          'Health contribution (7.5% of base)',
          'Unemployment contribution (1.2% of base)',
          'Additional contribution (0.5% of base)',
          'Personal income tax (10% of taxable base)',
          'Working hours and type (regular, overtime, night)',
        ],
        steps: null,
      },
      {
        title: 'Penalties for Non-Filing',
        content: 'Failure to file or late filing of MPIN carries serious consequences:',
        items: [
          'EUR 1,000-3,000 fine for late filing (legal entity)',
          'EUR 300-500 fine for the responsible person',
          'Interest 0.03% daily on unpaid contributions',
          'UJP can block company bank accounts for unpaid contributions',
          'Repeated violations → criminal liability',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'mpin-obrazec', title: 'MPIN Form: Monthly Payroll Filing Guide' },
      { slug: 'presmetka-na-plata-mk', title: 'Payroll Calculation in Macedonia: Contributions and Taxes' },
      { slug: 'trudovo-pravo-osnovi', title: 'Labor Law: 10 Things Every Employer Must Know' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Unpaid Wages: How to Report to the Labor Inspectorate' },
      { slug: 'rok-za-plata-makedonija', title: 'Salary Payment Deadline: What the Law Says' },
    ],
    cta: {
      title: 'MPIN filing, automated',
      desc: 'Facturino generates MPIN from calculated payroll — submit in 2 clicks.',
      button: 'Start Free →',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Regjistrimi dhe dorëzimi MPIN 2026: Udhëzues i plotë',
    publishDate: '23 maj 2026',
    readTime: '8 min lexim',
    intro: 'MPIN (Llogaritja Mujore për Arkëtim të Integruar) është deklarata e detyrueshme mujore e pagave për çdo punëdhënës në Maqedoninë e Veriut. Ky udhëzues mbulon çfarë është MPIN, kush duhet të regjistrohet, procesin e plotë të regjistrimit në UJP, afatet mujore, fushat e të dhënave të nevojshme dhe gjobat për dorëzim të vonuar.',
    sections: [
      {
        title: 'Çfarë është MPIN?',
        content: 'MPIN do të thotë Llogaritja Mujore për Arkëtim të Integruar (Месечна Пресметка за Интегрирана Наплата). Është deklarata mujore e pagave që çdo punëdhënës duhet ta dorëzojë në UJP (Zyra e të Ardhurave Publike). MPIN mbulon të gjitha kontributet e punonjësve dhe tatimin mbi të ardhurat personale. Dorëzohet elektronikisht përmes portalit e-Tax (etax.ujp.gov.mk).',
        items: null,
        steps: null,
      },
      {
        title: 'Kush duhet të dorëzojë MPIN?',
        content: 'Subjektet e mëposhtme janë të detyruara të dorëzojnë MPIN në UJP:',
        items: [
          'Çdo punëdhënës me të paktën 1 punonjës',
          'Të vetëpunësuarit që paguajnë veten me pagë',
          'Subjektet që paguajnë me kontratë (marrëveshje autori) ose punë të përkohshme',
          'Punëdhënësit e huaj me punonjës në Maqedoninë e Veriut',
          'Institucionet shtetërore dhe ndërmarrjet publike',
        ],
        steps: null,
      },
      {
        title: 'Regjistrimi në sistem',
        content: 'Për të dorëzuar MPIN, fillimisht duhet të regjistroheni si pagues kontributesh në sistemin e UJP. Procesi përfshin:',
        items: null,
        steps: [
          { step: 'Regjistrohuni në etax.ujp.gov.mk', desc: 'Vizitoni portalin etax.ujp.gov.mk dhe regjistrohuni si punëdhënës me certifikatë digjitale.' },
          { step: 'Dorëzoni formularin MPIN-1', desc: 'Dorëzoni formularin MPIN-1 (regjistrimi i pagues së kontributeve) në zyrën rajonale të UJP.' },
          { step: 'Merrni identifikuesin tuaj MPIN', desc: 'Pas përpunimit, merrni identifikuesin MPIN që përputhet me EDB-në tuaj (numri unik tatimor).' },
          { step: 'Regjistroni punonjësit me MPIN-2', desc: 'Regjistroni çdo punonjës duke përdorur formularin MPIN-2 (regjistrimi i punonjësit).' },
          { step: 'Instaloni softuerin ose përdorni portalin', desc: 'Instaloni softuerin e-Tax ose përdorni portalin ueb për dorëzimet mujore të MPIN.' },
        ],
      },
      {
        title: 'Afatet mujore',
        content: 'MPIN duhet të dorëzohet deri më 15 të muajit PASARDHËS (i njëjti afat si pagesa e pagës sipas Nenit 106 të Ligjit të Marrëdhënieve të Punës).',
        items: [
          'Paga e janarit → MPIN afati deri më 15 shkurt',
          'Dorëzimi përmes portalit e-Tax (etax.ujp.gov.mk)',
          'Pagesa e kontributeve ditën e njëjtë me dorëzimin',
          'Dorëzim i vonuar = kamatë automatike (0,03% ditore)',
          'Paga e dhjetorit → MPIN afati deri më 15 janar të vitit tjetër',
        ],
        steps: null,
      },
      {
        title: 'Të dhënat në MPIN',
        content: 'Formulari MPIN përmban informacione të detajuara për çdo punonjës. Ja fushat e detyrueshme:',
        items: [
          'Të dhënat personale të punonjësit (EMBG, emri, adresa)',
          'Shuma e pagës bruto',
          'Baza e kontributeve (e kufizuar: min 31.577 / maks 1.010.464 MKD)',
          'Kontributi pensional (18,8% e bazës)',
          'Kontributi shëndetësor (7,5% e bazës)',
          'Kontributi i papunësisë (1,2% e bazës)',
          'Kontributi shtesë (0,5% e bazës)',
          'Tatimi mbi të ardhurat personale (10% e bazës tatimore)',
          'Orët e punës dhe lloji (i rregullt, jashtë orarit, natën)',
        ],
        steps: null,
      },
      {
        title: 'Gjobat për mosdorëzim',
        content: 'Mosdorëzimi ose dorëzimi i vonuar i MPIN sjell pasoja serioze:',
        items: [
          'Gjobë 1.000-3.000 euro për dorëzim të vonuar (person juridik)',
          'Gjobë 300-500 euro për personin përgjegjës',
          'Kamatë 0,03% ditore mbi kontributet e papaguara',
          'UJP mund të bllokojë llogaritë bankare për kontribute të papaguara',
          'Shkelje të përsëritura → përgjegjësi penale',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'mpin-obrazec', title: 'Formulari MPIN: Udhëzues për llogaritjen mujore' },
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet' },
      { slug: 'trudovo-pravo-osnovi', title: "E drejta e punës: 10 gjëra që çdo punëdhënës duhet t'i dijë" },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Paga e papaguar: Si të raportoni tek Inspektorati i Punës' },
      { slug: 'rok-za-plata-makedonija', title: 'Afati i pagesës së pagës: Çfarë thotë ligji' },
    ],
    cta: {
      title: 'MPIN automatik, pa gabime',
      desc: 'Facturino gjeneron MPIN nga pagat e llogaritura — dorëzoni me 2 klikime.',
      button: 'Filloni falas →',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'MPIN Kaydı ve Dosyalama 2026: Eksiksiz Rehber',
    publishDate: '23 Mayıs 2026',
    readTime: '8 dk okuma',
    intro: 'MPIN (Aylık Entegre Ödeme Hesaplaması), Kuzey Makedonya\'daki her işveren için zorunlu aylık bordro beyannamesidir. Bu rehber, MPIN\'in ne olduğunu, kimin kayıt olması gerektiğini, tam UJP kayıt sürecini, aylık son tarihleri, gerekli veri alanlarını ve gecikme cezalarını kapsamaktadır.',
    sections: [
      {
        title: 'MPIN Nedir?',
        content: 'MPIN, Aylık Entegre Ödeme Hesaplaması (Месечна Пресметка за Интегрирана Наплата) anlamına gelir. Her işverenin Kamu Gelir İdaresi\'ne (UJP) sunması gereken aylık bordro beyannamesidir. MPIN, tüm çalışan katkı paylarını ve kişisel gelir vergisini kapsar. e-Tax portalı (etax.ujp.gov.mk) üzerinden elektronik olarak sunulur.',
        items: null,
        steps: null,
      },
      {
        title: 'MPIN\'i Kim Dosyalamalı?',
        content: 'Aşağıdaki kuruluşlar UJP\'ye MPIN sunmak zorundadır:',
        items: [
          'En az 1 çalışanı olan her işveren',
          'Kendine maaş ödeyen serbest meslek sahipleri',
          'Sözleşme bazında (yazar sözleşmeleri) veya geçici iş ödeyen kuruluşlar',
          'Kuzey Makedonya\'da çalışanları olan yabancı işverenler',
          'Devlet kurumları ve kamu işletmeleri',
        ],
        steps: null,
      },
      {
        title: 'Sistem Kaydı',
        content: 'MPIN dosyalamak için önce UJP sistemine katkı payı ödeyen olarak kayıt olmanız gerekir. Süreç şunları içerir:',
        items: null,
        steps: [
          { step: 'etax.ujp.gov.mk\'da kaydolun', desc: 'etax.ujp.gov.mk portalını ziyaret edin ve dijital sertifika ile işveren olarak kaydolun.' },
          { step: 'MPIN-1 formunu gönderin', desc: 'MPIN-1 formunu (katkı payı ödeyen kaydı) bölgesel UJP ofisine sunun.' },
          { step: 'MPIN tanımlayıcınızı alın', desc: 'İşlem sonrası, EDB\'nizle (benzersiz vergi numarası) eşleşen MPIN tanımlayıcınızı alırsınız.' },
          { step: 'Çalışanları MPIN-2 ile kaydedin', desc: 'MPIN-2 formunu (çalışan kaydı) kullanarak her çalışanı kaydedin.' },
          { step: 'Yazılımı kurun veya web portalını kullanın', desc: 'e-Tax yazılımını kurun veya aylık MPIN gönderimleri için web portalını kullanın.' },
        ],
      },
      {
        title: 'Aylık Son Tarihler',
        content: 'MPIN, TAKİP EDEN ayın 15\'ine kadar dosyalanmalıdır (İş Kanunu Madde 106 uyarınca maaş ödemesi ile aynı son tarih).',
        items: [
          'Ocak maaşı → MPIN son tarihi 15 Şubat',
          'e-Tax portalı (etax.ujp.gov.mk) üzerinden dosyalama',
          'Katkı payı ödemesi dosyalama ile aynı gün',
          'Geç dosyalama = otomatik faiz (günlük %0,03)',
          'Aralık maaşı → MPIN son tarihi gelecek yılın 15 Ocak\'ı',
        ],
        steps: null,
      },
      {
        title: 'MPIN\'deki Gerekli Veriler',
        content: 'MPIN formu her çalışan için ayrıntılı bilgi içerir. Zorunlu alanlar:',
        items: [
          'Çalışan kişisel verileri (EMBG, ad, adres)',
          'Brüt maaş tutarı',
          'Katkı payı matrahı (sınırlı: min 31.577 / maks 1.010.464 MKD)',
          'Emeklilik katkısı (matrahın %18,8\'i)',
          'Sağlık katkısı (matrahın %7,5\'i)',
          'İşsizlik katkısı (matrahın %1,2\'si)',
          'Ek katkı (matrahın %0,5\'i)',
          'Kişisel gelir vergisi (vergi matrahının %10\'u)',
          'Çalışma saatleri ve türü (normal, fazla mesai, gece)',
        ],
        steps: null,
      },
      {
        title: 'Dosyalamama Cezaları',
        content: 'MPIN\'in dosyalanmaması veya geç dosyalanması ciddi sonuçlar doğurur:',
        items: [
          'Geç dosyalama için 1.000-3.000 avro ceza (tüzel kişi)',
          'Sorumlu kişi için 300-500 avro ceza',
          'Ödenmemiş katkı payları üzerinden günlük %0,03 faiz',
          'UJP, ödenmemiş katkı payları için şirket banka hesaplarını bloke edebilir',
          'Tekrarlanan ihlaller → cezai sorumluluk',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'mpin-obrazec', title: 'MPIN Formu: Aylık Bordro Beyanname Rehberi' },
      { slug: 'presmetka-na-plata-mk', title: "Makedonya'da Maaş Hesaplama: Primler ve Vergiler" },
      { slug: 'trudovo-pravo-osnovi', title: 'İş Hukuku: Her İşverenin Bilmesi Gereken 10 Şey' },
      { slug: 'neisplatena-plata-prijavuvanje', title: 'Ödenmemiş Maaş: Çalışma Müfettişliğine Nasıl Şikayet Edilir' },
      { slug: 'rok-za-plata-makedonija', title: 'Maaş Ödeme Süresi: Kanun Ne Diyor' },
    ],
    cta: {
      title: 'MPIN otomatik, hatasız',
      desc: 'Facturino hesaplanan bordrolardan MPIN oluşturur — 2 tıklamayla gönderin.',
      button: 'Ücretsiz Başlayın →',
    },
  },
} as const

export default async function MpinRegistracija2026Page({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = { mk: 'Блог', en: 'Blog', sq: 'Blog', tr: 'Blog' }[locale]
  const homeLabel = { mk: 'Почетна', en: 'Home', sq: 'Kryefaqja', tr: 'Ana Sayfa' }[locale]

  const articleLd = articleJsonLd({
    locale,
    slug: 'mpin-registracija-2026',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['MPIN', 'МПИН', 'mpinform', 'registration', 'UJP', 'payroll', 'north macedonia', 'регистрација', '2026'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/mpin-registracija-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кој мора да се регистрира за МПИН?', answer: 'Секој работодавач со барем 1 вработен, самовработени кои си исплаќаат плата, субјекти со авторски договори и странски работодавачи со вработени во Македонија.' },
        { question: 'Како се регистрира МПИН?', answer: 'Регистрацијата се врши на etax.ujp.gov.mk со дигитален сертификат и поднесување на МПИН-1 образец во регионалната канцеларија на УЈП.' },
        { question: 'Кој е месечниот рок за МПИН?', answer: 'МПИН мора да се поднесе до 15-ти во наредниот месец. На пример, за јануарските плати рокот е 15 февруари. Задоцнувањето носи казни од 1.000-3.000 евра.' },
      ])) }} />
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
