import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/ddv-vodich-mk', {
    title: {
      mk: 'ДДВ во Македонија: Целосен водич за 2026 | Facturino',
      en: 'VAT in Macedonia: Complete Guide for 2026 | Facturino',
      sq: 'TVSH në Maqedoni: Udhëzues i plotë për 2026 | Facturino',
      tr: 'Makedonya\'da KDV: 2026 için kapsamlı rehber | Facturino',
    },
    description: {
      mk: 'Целосен водич за ДДВ во Македонија: стапки (18% и 5%), праг за регистрација, периоди на пријавување, ослободувања и казни. Ажурирано за 2026.',
      en: 'Complete VAT guide for Macedonia: rates (18% and 5%), registration threshold, filing periods, exemptions, and penalties. Updated for 2026.',
      sq: 'Udhëzues i plotë për TVSH-në në Maqedoni: normat (18% dhe 5%), pragu i regjistrimit, periudhat e deklarimit, përjashtimet dhe gjobat. Përditësuar për 2026.',
      tr: 'Makedonya için kapsamlı KDV rehberi: oranlar (%18 ve %5), kayıt eşiği, beyanname dönemleri, muafiyetler ve cezalar. 2026 için güncellenmiştir.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'ДДВ во Македонија: Целосен водич за 2026',
    publishDate: '2 февруари 2026',
    readTime: '9 мин читање',
    intro: 'Данокот на додадена вредност (ДДВ) е еден од најважните даночни обврски за секој бизнис во Македонија. Без разлика дали сте новоотворена фирма или искусен претприемач, разбирањето на правилата за ДДВ е клучно за усогласеност со Управата за јавни приходи (УЈП) и избегнување казни. Овој водич ги покрива сите аспекти на ДДВ системот — од стапки и регистрација до пријавување и ослободувања.',
    sections: [
      {
        title: 'Стапки на ДДВ во Македонија',
        content: 'Во Македонија постојат две стапки на ДДВ. Стандардната стапка од 18% се применува на повеќето добра и услуги, вклучувајќи електроника, облека, професионални услуги, угостителство и транспорт. Намалената стапка од 5% се применува на основни прехранбени производи (леб, млеко, масло), лекови и медицински помагала, книги и учебници, водоснабдување и софтвер и ИТ услуги (од 2024 година). Нулта стапка (0%) се применува на извозот на добра и услуги надвор од Македонија, како и на меѓународен транспорт.',
        items: null,
      },
      {
        title: 'Кој мора да се регистрира за ДДВ?',
        content: 'Регистрацијата за ДДВ е задолжителна за сите правни лица и индивидуални трговци чиј вкупен промет во претходните 12 месеци надминува 8.000.000 денари (приближно 130.000 евра). Доброволната регистрација е можна и под овој праг, што може да биде поволно доколку имате значителни трошоци со ДДВ (на пример, набавка на опрема или суровини).',
        items: [
          'Задолжителна регистрација — Промет над 8.000.000 МКД во последните 12 месеци.',
          'Доброволна регистрација — Можна за секој даночен обврзник, без минимален праг.',
          'Рок за регистрација — Најдоцна 15 дена по месецот кога е надминат прагот.',
          'ЕДБ број — По регистрацијата добивате единствен ДДВ идентификационен број (МК + 13 цифри).',
          'Дерегистрација — Можна ако прометот паѓа под 8.000.000 МКД две последователни години.',
        ],
      },
      {
        title: 'Периоди и рокови за пријавување',
        content: 'ДДВ пријавата се поднесува до УЈП електронски преку системот е-Даноци. Периодот на пријавување зависи од годишниот промет на компанијата.',
        items: [
          'Месечно пријавување — За компании со годишен промет над 25.000.000 МКД. Рок: 25-ти во наредниот месец.',
          'Квартално пријавување — За компании со годишен промет под 25.000.000 МКД. Рок: 25-ти во месецот по завршувањето на кварталот.',
          'Електронско поднесување — Сите ДДВ пријави задолжително се поднесуваат електронски преку е-Даноци.',
          'Плаќање — ДДВ обврската мора да се плати до истиот рок кога се поднесува пријавата.',
          'Поврат на ДДВ — Ако влезниот ДДВ е поголем од излезниот, можете да побарате поврат или пренос во следниот период.',
        ],
      },
      {
        title: 'Влезен и излезен ДДВ',
        content: 'Разбирањето на разликата меѓу влезен и излезен ДДВ е основа за правилно пресметување на вашата ДДВ обврска. Излезен ДДВ е данокот што го наплатувате на вашите клиенти при продажба на добра или услуги. Овој износ го собирате од клиентите и го плаќате на УЈП. Влезен ДДВ е данокот што го плаќате на вашите добавувачи при набавка на добра или услуги за вашиот бизнис. Овој износ го одбивате од вашата ДДВ обврска. Вашата ДДВ обврска = Излезен ДДВ − Влезен ДДВ. Ако резултатот е позитивен, плаќате ДДВ на УЈП. Ако е негативен, побарувате поврат или го пренесувате во следниот период. Facturino автоматски ги пресметува влезниот и излезниот ДДВ и генерира извештај подготвен за пријавата до УЈП.',
        items: null,
      },
      {
        title: 'Ослободувања и казни',
        content: 'Одредени активности се ослободени од ДДВ, додека непочитувањето на правилата повлекува сериозни казни.',
        items: [
          'Финансиски услуги — Банкарство, осигурување и трговија со хартии од вредност се ослободени од ДДВ.',
          'Здравствени услуги — Медицински третмани и болнички услуги се ослободени.',
          'Образование — Услуги на јавни и приватни образовни институции.',
          'Казна за ненавремена пријава — Од 500 до 5.000 евра за правни лица.',
          'Казна за неплаќање — Камата од 0,03% дневно на неплатениот износ.',
          'Казна за нерегистрирање — До 10.000 евра за вршење дејност без ДДВ регистрација.',
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
      { slug: 'godishna-smetka-2025', title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ' },
    ],
    cta: {
      title: 'Поедноставете го ДДВ пријавувањето',
      desc: 'Facturino автоматски го пресметува ДДВ и генерира извештаи подготвени за УЈП.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'VAT in Macedonia: Complete Guide for 2026',
    publishDate: 'February 2, 2026',
    readTime: '9 min read',
    intro: 'Value Added Tax (VAT) is one of the most important tax obligations for every business in Macedonia. Whether you are a newly established company or an experienced entrepreneur, understanding VAT rules is essential for compliance with the Public Revenue Office (UJP) and avoiding penalties. This guide covers all aspects of the VAT system — from rates and registration to filing and exemptions.',
    sections: [
      {
        title: 'VAT rates in Macedonia',
        content: 'Macedonia has two VAT rates. The standard rate of 18% applies to most goods and services, including electronics, clothing, professional services, hospitality, and transportation. The reduced rate of 5% applies to basic food products (bread, milk, butter), medicines and medical devices, books and textbooks, water supply, and software and IT services (since 2024). The zero rate (0%) applies to exports of goods and services outside Macedonia, as well as international transport.',
        items: null,
      },
      {
        title: 'Who must register for VAT?',
        content: 'VAT registration is mandatory for all legal entities and sole traders whose total turnover in the previous 12 months exceeds 8,000,000 MKD (approximately EUR 130,000). Voluntary registration is also possible below this threshold, which can be beneficial if you have significant VAT-bearing expenses (for example, purchasing equipment or raw materials).',
        items: [
          'Mandatory registration — Turnover exceeding 8,000,000 MKD in the last 12 months.',
          'Voluntary registration — Available to any taxpayer, with no minimum threshold.',
          'Registration deadline — No later than 15 days after the month the threshold was exceeded.',
          'VAT number — After registration, you receive a unique VAT identification number (MK + 13 digits).',
          'Deregistration — Possible if turnover falls below 8,000,000 MKD for two consecutive years.',
        ],
      },
      {
        title: 'Filing periods and deadlines',
        content: 'VAT returns are submitted to UJP electronically through the e-Tax system. The filing period depends on the company\'s annual turnover.',
        items: [
          'Monthly filing — For companies with annual turnover above 25,000,000 MKD. Deadline: 25th of the following month.',
          'Quarterly filing — For companies with annual turnover below 25,000,000 MKD. Deadline: 25th of the month after the quarter ends.',
          'Electronic submission — All VAT returns must be submitted electronically via e-Tax.',
          'Payment — The VAT liability must be paid by the same deadline as the return submission.',
          'VAT refund — If input VAT exceeds output VAT, you can request a refund or carry it forward to the next period.',
        ],
      },
      {
        title: 'Input and output VAT',
        content: 'Understanding the difference between input and output VAT is fundamental to correctly calculating your VAT liability. Output VAT is the tax you charge your customers when selling goods or services. You collect this amount from customers and pay it to UJP. Input VAT is the tax you pay to your suppliers when purchasing goods or services for your business. You deduct this amount from your VAT liability. Your VAT liability = Output VAT minus Input VAT. If the result is positive, you pay VAT to UJP. If negative, you request a refund or carry it forward to the next period. Facturino automatically calculates input and output VAT and generates a report ready for your UJP submission.',
        items: null,
      },
      {
        title: 'Exemptions and penalties',
        content: 'Certain activities are exempt from VAT, while non-compliance carries serious penalties.',
        items: [
          'Financial services — Banking, insurance, and securities trading are VAT-exempt.',
          'Healthcare services — Medical treatments and hospital services are exempt.',
          'Education — Services of public and private educational institutions.',
          'Penalty for late filing — EUR 500 to EUR 5,000 for legal entities.',
          'Penalty for non-payment — Interest of 0.03% per day on the unpaid amount.',
          'Penalty for non-registration — Up to EUR 10,000 for conducting business without VAT registration.',
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
      { slug: 'godishna-smetka-2025', title: 'Annual Accounts 2025: Complete Filing Guide for CRMS' },
    ],
    cta: {
      title: 'Simplify your VAT filing',
      desc: 'Facturino automatically calculates VAT and generates reports ready for UJP.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026',
    publishDate: '2 shkurt 2026',
    readTime: '9 min lexim',
    intro: 'Tatimi mbi Vlerën e Shtuar (TVSH) është një nga detyrimet më të rëndësishme tatimore për çdo biznes në Maqedoni. Pavarësisht nëse jeni kompani e sapokrijuar ose sipërmarrës me përvojë, kuptimi i rregullave të TVSH-së është thelbësor për përputhshmërinë me Zyrën e të Ardhurave Publike (UJP) dhe shmangien e gjobave. Ky udhëzues mbulon të gjitha aspektet e sistemit të TVSH-së — nga normat dhe regjistrimi deri te deklarimi dhe përjashtimet.',
    sections: [
      {
        title: 'Normat e TVSH-së në Maqedoni',
        content: 'Maqedonia ka dy norma TVSH-je. Norma standarde prej 18% zbatohet për shumicën e mallrave dhe shërbimeve, përfshirë elektronikën, veshjet, shërbimet profesionale, mikpritjen dhe transportin. Norma e ulur prej 5% zbatohet për produkte ushqimore bazë (bukë, qumësht, gjalpë), ilaçe dhe pajisje mjekësore, libra dhe tekste shkollore, furnizim me ujë, dhe softuerë e shërbime IT (që nga 2024). Norma zero (0%) zbatohet për eksportin e mallrave dhe shërbimeve jashtë Maqedonisë, si dhe transportin ndërkombëtar.',
        items: null,
      },
      {
        title: 'Kush duhet të regjistrohet për TVSH?',
        content: 'Regjistrimi për TVSH është i detyrueshëm për të gjitha subjektet juridike dhe tregtarët individualë, xhiroja totale e të cilëve në 12 muajt e mëparshëm kalon 8.000.000 MKD (afërsisht 130.000 euro). Regjistrimi vullnetar është gjithashtu i mundshëm nën këtë prag, gjë që mund të jetë e dobishme nëse keni shpenzime të konsiderueshme me TVSH (për shembull, blerja e pajisjeve ose lëndëve të para).',
        items: [
          'Regjistrim i detyrueshëm — Xhiro mbi 8.000.000 MKD në 12 muajt e fundit.',
          'Regjistrim vullnetar — I disponueshëm për çdo tatimpagues, pa prag minimal.',
          'Afati i regjistrimit — Jo më vonë se 15 ditë pas muajit kur pragu u tejkalua.',
          'Numri i TVSH-së — Pas regjistrimit, merrni një numër unik identifikimi TVSH-je (MK + 13 shifra).',
          'Çregjistrim — I mundshëm nëse xhiroja bie nën 8.000.000 MKD për dy vite radhazi.',
        ],
      },
      {
        title: 'Periudhat dhe afatet e deklarimit',
        content: 'Deklaratat e TVSH-së dorëzohen në UJP elektronikisht përmes sistemit e-Tatim. Periudha e deklarimit varet nga xhiroja vjetore e kompanisë.',
        items: [
          'Deklarim mujor — Për kompani me xhiro vjetore mbi 25.000.000 MKD. Afati: data 25 e muajit pasardhës.',
          'Deklarim tremujor — Për kompani me xhiro vjetore nën 25.000.000 MKD. Afati: data 25 e muajit pas përfundimit të tremujorit.',
          'Dorëzim elektronik — Të gjitha deklaratat e TVSH-së duhet të dorëzohen elektronikisht përmes e-Tatim.',
          'Pagesa — Detyrimi i TVSH-së duhet të paguhet deri në të njëjtin afat si dorëzimi i deklaratës.',
          'Rimbursim i TVSH-së — Nëse TVSH-ja hyrëse tejkalon TVSH-në dalëse, mund të kërkoni rimbursim ose ta transferoni në periudhën e ardhshme.',
        ],
      },
      {
        title: 'TVSH hyrëse dhe dalëse',
        content: 'Kuptimi i dallimit midis TVSH-së hyrëse dhe dalëse është themelor për llogaritjen e saktë të detyrimit tuaj të TVSH-së. TVSH dalëse është tatimi që ju ngarkoni klientëve tuaj kur shisni mallra ose shërbime. Ju e mbledhni këtë shumë nga klientët dhe e paguani në UJP. TVSH hyrëse është tatimi që paguani te furnitorët tuaj kur blini mallra ose shërbime për biznesin tuaj. Ju e zbritni këtë shumë nga detyrimi juaj i TVSH-së. Detyrimi juaj i TVSH-së = TVSH dalëse minus TVSH hyrëse. Nëse rezultati është pozitiv, paguani TVSH në UJP. Nëse është negativ, kërkoni rimbursim ose e transferoni në periudhën e ardhshme. Facturino automatikisht llogarit TVSH-në hyrëse dhe dalëse dhe gjeneron raport të gatshëm për dorëzimin tuaj në UJP.',
        items: null,
      },
      {
        title: 'Përjashtimet dhe gjobat',
        content: 'Aktivitete të caktuara janë të përjashtuara nga TVSH-ja, ndërsa mospërputhja mbart gjoba serioze.',
        items: [
          'Shërbime financiare — Bankingu, sigurimet dhe tregtia e letrave me vlerë janë të përjashtuara nga TVSH-ja.',
          'Shërbime shëndetësore — Trajtimet mjekësore dhe shërbimet spitalore janë të përjashtuara.',
          'Arsimi — Shërbimet e institucioneve arsimore publike dhe private.',
          'Gjobë për deklarim të vonuar — 500 deri 5.000 euro për subjektet juridike.',
          'Gjobë për mospagesë — Interes prej 0,03% në ditë mbi shumën e papaguar.',
          'Gjobë për mosregjistrim — Deri 10.000 euro për kryerje biznesi pa regjistrim TVSH-je.',
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet për DAP' },
      { slug: 'godishna-smetka-2025', title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim në QRMK' },
    ],
    cta: {
      title: 'Thjeshtoni deklarimin e TVSH-së',
      desc: 'Facturino automatikisht llogarit TVSH-në dhe gjeneron raporte të gatshme për UJP.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Makedonya\'da KDV: 2026 için kapsamlı rehber',
    publishDate: '2 Şubat 2026',
    readTime: '9 dk okuma',
    intro: 'Katma Değer Vergisi (KDV), Makedonya\'daki her işletme için en önemli vergi yükümlülüklerinden biridir. İster yeni kurulmuş bir şirket ister deneyimli bir girişimci olun, KDV kurallarını anlamak Kamu Gelir İdaresi (UJP) ile uyumluluk ve cezalardan kaçınmak için hayati önem taşır. Bu rehber, KDV sisteminin tüm yönlerini kapsar — oranlar ve kayıttan beyanname verme ve muafiyetlere kadar.',
    sections: [
      {
        title: 'Makedonya\'da KDV oranları',
        content: 'Makedonya\'da iki KDV oranı bulunmaktadır. %18\'lik standart oran, elektronik, giyim, profesyonel hizmetler, konaklama ve ulaşım dahil olmak üzere çoğu mal ve hizmete uygulanır. %5\'lik indirimli oran, temel gıda ürünlerine (ekmek, süt, tereyağı), ilaç ve tıbbi cihazlara, kitap ve ders kitaplarına, su teminatına ve yazılım ile BT hizmetlerine (2024\'ten itibaren) uygulanır. Sıfır oran (%0), Makedonya dışına mal ve hizmet ihracatına ve uluslararası taşımacılığa uygulanır.',
        items: null,
      },
      {
        title: 'KDV\'ye kim kayıt olmak zorundadır?',
        content: 'KDV kaydı, önceki 12 aydaki toplam cirosu 8.000.000 MKD\'yi (yaklaşık 130.000 euro) aşan tüm tüzel kişiler ve bireysel tüccarlar için zorunludur. Bu eşiğin altında da gönüllü kayıt mümkündür; bu, önemli KDV yüklü giderleriniz varsa (örneğin ekipman veya hammadde satın alma) avantajlı olabilir.',
        items: [
          'Zorunlu kayıt — Son 12 ayda 8.000.000 MKD\'yi aşan ciro.',
          'Gönüllü kayıt — Minimum eşik olmaksızın her vergi mükellefi için mevcuttur.',
          'Kayıt süresi — Eşiğin aşıldığı aydan sonra en geç 15 gün.',
          'KDV numarası — Kayıt sonrası benzersiz bir KDV kimlik numarası alırsınız (MK + 13 hane).',
          'Kayıt silme — Ciro art arda iki yıl 8.000.000 MKD\'nin altına düşerse mümkündür.',
        ],
      },
      {
        title: 'Beyanname dönemleri ve son tarihler',
        content: 'KDV beyannameleri, e-Vergi sistemi aracılığıyla UJP\'ye elektronik olarak sunulur. Beyanname dönemi, şirketin yıllık cirosuna bağlıdır.',
        items: [
          'Aylık beyanname — Yıllık cirosu 25.000.000 MKD\'nin üzerinde olan şirketler için. Son tarih: takip eden ayın 25\'i.',
          'Üç aylık beyanname — Yıllık cirosu 25.000.000 MKD\'nin altında olan şirketler için. Son tarih: çeyreğin sona erdiği aydan sonraki ayın 25\'i.',
          'Elektronik sunum — Tüm KDV beyannameleri e-Vergi üzerinden elektronik olarak sunulmalıdır.',
          'Ödeme — KDV yükümlülüğü, beyanname sunumuyla aynı son tarihe kadar ödenmelidir.',
          'KDV iadesi — Giriş KDV\'si çıkış KDV\'sini aşarsa, iade talep edebilir veya bir sonraki döneme devredebilirsiniz.',
        ],
      },
      {
        title: 'Giriş ve çıkış KDV\'si',
        content: 'Giriş ve çıkış KDV\'si arasındaki farkı anlamak, KDV yükümlülüğünüzü doğru hesaplamak için temeldir. Çıkış KDV\'si, mal veya hizmet satarken müşterilerinize uyguladığınız vergidir. Bu tutarı müşterilerden tahsil eder ve UJP\'ye ödersiniz. Giriş KDV\'si, işletmeniz için mal veya hizmet satın alırken tedarikçilerinize ödediğiniz vergidir. Bu tutarı KDV yükümlülüğünüzden düşersiniz. KDV yükümlülüğünüz = Çıkış KDV\'si eksi Giriş KDV\'si. Sonuç pozitifse UJP\'ye KDV ödersiniz. Negatifse iade talep eder veya bir sonraki döneme devredersiniz. Facturino, giriş ve çıkış KDV\'sini otomatik olarak hesaplar ve UJP sunumunuz için hazır rapor oluşturur.',
        items: null,
      },
      {
        title: 'Muafiyetler ve cezalar',
        content: 'Belirli faaliyetler KDV\'den muaftır, uyumsuzluk ise ciddi cezalar gerektirir.',
        items: [
          'Finansal hizmetler — Bankacılık, sigorta ve menkul kıymet ticareti KDV\'den muaftır.',
          'Sağlık hizmetleri — Tıbbi tedaviler ve hastane hizmetleri muaftır.',
          'Eğitim — Kamu ve özel eğitim kurumlarının hizmetleri.',
          'Geç beyanname cezası — Tüzel kişiler için 500 ila 5.000 euro.',
          'Ödememe cezası — Ödenmemiş tutar üzerinden günlük %0,03 faiz.',
          'Kayıt dışı faaliyet cezası — KDV kaydı olmadan iş yapma için 10.000 euroya kadar.',
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, tarihler ve hesaplama' },
      { slug: 'rokovi-ujp-2026', title: 'Vergi takvimi 2026: Tüm UJP tarihleri' },
      { slug: 'godishna-smetka-2025', title: 'Yıllık hesaplar 2025: CRMS dosyalama rehberi' },
    ],
    cta: {
      title: 'KDV beyannamelerinizi basitleştirin',
      desc: 'Facturino, KDV\'yi otomatik hesaplar ve UJP için hazır raporlar oluşturur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function DdvVodichMkPage({
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
