import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/paushalen-danochnik', {
    title: {
      mk: 'Паушалец во Македонија: Услови, ограничувања и обврски | Facturino',
      en: 'Lump-Sum Taxation in Macedonia: Requirements and Limits | Facturino',
      sq: 'Tatimi i përgjithshëm në Maqedoni: Kushtet dhe kufijtë | Facturino',
      tr: 'Makedonya\'da götürü vergi: Koşullar ve sınırlar | Facturino',
    },
    description: {
      mk: 'Сe што треба да знаете за паушално оданочување во Македонија: услови, праг од 3 милиони МКД, исклучени дејности и обврски кон УЈП.',
      en: 'Everything you need to know about lump-sum taxation in Macedonia: eligibility, 3M MKD threshold, excluded activities, and UJP obligations.',
      sq: 'Gjithçka që duhet të dini për tatimin e përgjithshëm në Maqedoni: kriteret, pragu 3M MKD, aktivitetet e përjashtuara dhe detyrimet ndaj UJP.',
      tr: 'Makedonya\'da götürü vergilendirme hakkında bilmeniz gereken her şey: uygunluk, 3M MKD eşiği, hariç tutulan faaliyetler ve UJP yükümlülükleri.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Едукација',
    title: 'Паушалец во Македонија: Услови, ограничувања и обврски',
    publishDate: '10 февруари 2026',
    readTime: '6 мин читање',
    intro: 'Паушалното оданочување е еден од најпопуларните даночни режими за мали бизниси и фриленсери во Македонија. Наместо да водите целосно сметководство, плаќате фиксен месечен данок. Но овој режим не е достапен за сите — постојат строги услови и ограничувања. Во овој водич објаснуваме кој може да биде паушалец, какви се обврските и кога е подобро да преминете на редовно оданочување.',
    sections: [
      {
        title: 'Што е паушално оданочување?',
        content: 'Паушалното оданочување е поедноставен даночен режим кој им овозможува на мали бизниси и самостојни вршители на дејност да плаќаат фиксен данок без потреба од водење комплетно двојно книговодство. Данокот се пресметува врз основа на нормирани трошоци — УЈП претпоставува дека одреден процент од вашиот приход се трошоци, и данокот се пресметува само на остатокот. Овој систем е идеален за мали услужни дејности, фриленсери, занаетчии и трговци со мал обем на работа.',
        items: null,
        steps: null,
      },
      {
        title: 'Услови за паушално оданочување',
        content: 'За да користите паушален режим, мора да ги исполните следните услови:',
        items: [
          'Годишен приход до 3.000.000 МКД (приближно 48.800 EUR) — ова е апсолутниот праг. Ако го надминете, автоматски преминувате на редовно оданочување.',
          'Не сте ДДВ обврзник — паушалците не можат да бидат регистрирани за ДДВ. Ако вашиот годишен промет надмине 2.000.000 МКД, мора да се регистрирате за ДДВ и автоматски го губите паушалниот статус.',
          'Дејноста не е исклучена — одредени дејности не можат да користат паушално оданочување: адвокати, нотари, извршители, сметководители, даночни советници, лекари со приватна пракса и други слободни професии.',
          'Сте регистриран како ТП (трговец поединец) или самостоен вршител на дејност — правните лица (ДООЕЛ, ДОО) не можат да бидат паушалци.',
          'Поднесување на барање до УЈП — статусот не е автоматски. Мора да поднесете барање за паушално оданочување до 31 јануари за тековната година.',
        ],
        steps: null,
      },
      {
        title: 'Како се пресметува данокот',
        content: null,
        items: null,
        steps: [
          { step: 'Утврдување на вкупен годишен приход', desc: 'Се собираат сите приходи од дејноста за календарската година. Ова вклучува фактурирани услуги, продажба на производи и други приходи поврзани со дејноста.' },
          { step: 'Примена на нормирани трошоци', desc: 'УЈП признава нормирани трошоци во висина од 50% до 80% од приходот, зависно од дејноста. На пример, за занаетчиски услуги се признаваат 80% трошоци, а за консултантски услуги 50%.' },
          { step: 'Пресметка на даночна основица', desc: 'Од вкупниот приход се одземаат нормираните трошоци. Остатокот е даночната основица.' },
          { step: 'Примена на даночна стапка', desc: 'На даночната основица се применува стапка од 10% за персонален данок на доход. Плус, се плаќаат и придонеси за пензиско и здравствено осигурување.' },
        ],
      },
      {
        title: 'Предности и недостатоци',
        content: 'Паушалното оданочување има свои предности, но и ограничувања кои треба да ги земете предвид пред да се одлучите за овој режим.',
        items: [
          'Предност: Поедноставена администрација — нема потреба од двојно книговодство, само евиденција на приходи.',
          'Предност: Пониски трошоци за сметководство — не ви треба целосна сметководствена услуга.',
          'Предност: Предвидлив данок — знаете однапред колку ќе платите.',
          'Недостаток: Не можете да одбивате реални трошоци — ако имате високи реални трошоци, можеби плаќате повеќе данок отколку со редовен режим.',
          'Недостаток: Ограничен раст — штом го надминете прагот од 3М МКД, мора да преминете на редовно оданочување.',
          'Недостаток: Нема ДДВ одбиток — не можете да го вратите ДДВ на набавки, што е проблем ако имате значителни влезни трошоци.',
        ],
        steps: null,
      },
      {
        title: 'Facturino и паушалните даночници',
        content: 'Иако паушалците имаат поедноставени обврски, сепак мора да издаваат фактури и да водат евиденција на приходите. Facturino е совршен алат и за паушалните даночници — брзо и лесно издавајте фактури, следете ги приходите и добивајте автоматски извештаи. Системот ве предупредува кога се приближувате до прагот од 3М МКД, за да можете навреме да донесете одлука за преминување на редовно оданочување.',
        items: [
          'Едноставно издавање на фактури без потреба од сметководствено знаење.',
          'Автоматско следење на годишниот приход со предупредување при приближување до прагот.',
          'Извештаи за УЈП компатибилни со барањата за паушалци.',
          'Преглед на сите издадени фактури на едно место.',
          'Лесен премин на редовно оданочување кога ќе дојде време.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Како да отворите фирма во Македонија: Комплетен водич' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
      { slug: 'personalen-danok-na-dohod', title: 'Персонален данок на доход во Македонија' },
    ],
    cta: {
      title: 'Издавајте фактури едноставно',
      desc: 'Без разлика дали сте паушалец или редовен даночник, Facturino го прави фактурирањето лесно и брзо.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Education',
    title: 'Lump-Sum Taxation in Macedonia: Requirements and Limits',
    publishDate: 'February 10, 2026',
    readTime: '6 min read',
    intro: 'Lump-sum taxation is one of the most popular tax regimes for small businesses and freelancers in Macedonia. Instead of maintaining full accounting records, you pay a fixed monthly tax. But this regime is not available to everyone — there are strict conditions and limitations. In this guide we explain who can be a lump-sum taxpayer, what the obligations are, and when it is better to switch to regular taxation.',
    sections: [
      {
        title: 'What is lump-sum taxation?',
        content: 'Lump-sum taxation is a simplified tax regime that allows small businesses and self-employed individuals to pay a fixed tax without the need for complete double-entry bookkeeping. The tax is calculated based on normalized expenses — UJP assumes that a certain percentage of your revenue consists of expenses, and the tax is calculated only on the remainder. This system is ideal for small service businesses, freelancers, craftspeople, and traders with low turnover.',
        items: null,
        steps: null,
      },
      {
        title: 'Eligibility criteria',
        content: 'To use the lump-sum regime, you must meet the following conditions:',
        items: [
          'Annual revenue up to 3,000,000 MKD (approximately EUR 48,800) — this is the absolute threshold. If you exceed it, you automatically switch to regular taxation.',
          'Not a VAT taxpayer — lump-sum taxpayers cannot be registered for VAT. If your annual turnover exceeds 2,000,000 MKD, you must register for VAT and automatically lose lump-sum status.',
          'Activity is not excluded — certain activities cannot use lump-sum taxation: lawyers, notaries, enforcement agents, accountants, tax advisors, doctors with private practice, and other liberal professions.',
          'Registered as a TP (sole trader) or self-employed — legal entities (DOOEL, DOO) cannot be lump-sum taxpayers.',
          'Application submitted to UJP — the status is not automatic. You must submit a lump-sum taxation application by January 31 for the current year.',
        ],
        steps: null,
      },
      {
        title: 'How the tax is calculated',
        content: null,
        items: null,
        steps: [
          { step: 'Determine total annual revenue', desc: 'All income from the business activity is summed for the calendar year. This includes invoiced services, product sales, and other activity-related income.' },
          { step: 'Apply normalized expenses', desc: 'UJP recognizes normalized expenses of 50% to 80% of revenue, depending on the activity. For example, craft services get 80% recognized expenses, while consulting services get 50%.' },
          { step: 'Calculate the tax base', desc: 'The normalized expenses are subtracted from total revenue. The remainder is the tax base.' },
          { step: 'Apply the tax rate', desc: 'A personal income tax rate of 10% is applied to the tax base. Additionally, pension and health insurance contributions must be paid.' },
        ],
      },
      {
        title: 'Advantages and disadvantages',
        content: 'Lump-sum taxation has its advantages, but also limitations you should consider before choosing this regime.',
        items: [
          'Advantage: Simplified administration — no need for double-entry bookkeeping, just revenue records.',
          'Advantage: Lower accounting costs — you do not need a full accounting service.',
          'Advantage: Predictable tax — you know in advance how much you will pay.',
          'Disadvantage: Cannot deduct actual expenses — if you have high actual costs, you may pay more tax than with the regular regime.',
          'Disadvantage: Limited growth — once you exceed the 3M MKD threshold, you must switch to regular taxation.',
          'Disadvantage: No VAT deduction — you cannot reclaim VAT on purchases, which is a problem if you have significant input costs.',
        ],
        steps: null,
      },
      {
        title: 'Facturino and lump-sum taxpayers',
        content: 'Although lump-sum taxpayers have simplified obligations, they still must issue invoices and keep revenue records. Facturino is the perfect tool for lump-sum taxpayers too — quickly and easily issue invoices, track revenue, and get automatic reports. The system warns you when you are approaching the 3M MKD threshold, so you can make a timely decision about switching to regular taxation.',
        items: [
          'Simple invoice issuance without accounting knowledge required.',
          'Automatic annual revenue tracking with threshold proximity warnings.',
          'Reports compatible with UJP requirements for lump-sum taxpayers.',
          'Overview of all issued invoices in one place.',
          'Easy transition to regular taxation when the time comes.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'How to Register a Company in Macedonia: Complete Guide' },
      { slug: 'ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
      { slug: 'personalen-danok-na-dohod', title: 'Personal Income Tax in Macedonia' },
    ],
    cta: {
      title: 'Issue invoices the easy way',
      desc: 'Whether you are a lump-sum or regular taxpayer, Facturino makes invoicing quick and easy.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Edukim',
    title: 'Tatimi i përgjithshëm në Maqedoni: Kushtet dhe kufijtë',
    publishDate: '10 shkurt 2026',
    readTime: '6 min lexim',
    intro: 'Tatimi i përgjithshëm është një nga regjimet tatimore më të njohura për bizneset e vogla dhe punëtorët e pavarur në Maqedoni. Në vend që të mbani kontabilitet të plotë, paguani një tatim mujor fiks. Por ky regjim nuk është i disponueshëm për të gjithë — ekzistojnë kushte dhe kufizime të rrepta. Në këtë udhëzues shpjegojmë kush mund të jetë tatimpagues i përgjithshëm, cilat janë detyrimet dhe kur është më mirë të kaloni në tatim të rregullt.',
    sections: [
      {
        title: 'Çfarë është tatimi i përgjithshëm?',
        content: 'Tatimi i përgjithshëm është një regjim tatimor i thjeshtuar që u mundëson bizneseve të vogla dhe personave të vetëpunësuar të paguajnë tatim fiks pa nevojën e kontabilitetit të plotë me regjistrim të dyfishtë. Tatimi llogaritet bazuar në shpenzime të normalizuara — UJP supozon se një përqindje e caktuar e të ardhurave tuaja janë shpenzime, dhe tatimi llogaritet vetëm mbi pjesën e mbetur. Ky sistem është ideal për biznese të vogla shërbimi, punëtorë të pavarur, zejtarë dhe tregtarë me qarkullim të ulët.',
        items: null,
        steps: null,
      },
      {
        title: 'Kriteret e pranueshmërisë',
        content: 'Për të përdorur regjimin e përgjithshëm, duhet të plotësoni kushtet e mëposhtme:',
        items: [
          'Të ardhura vjetore deri në 3.000.000 MKD (rreth 48.800 EUR) — ky është pragu absolut. Nëse e tejkaloni, automatikisht kaloni në tatim të rregullt.',
          'Nuk jeni tatimpagues i TVSH-së — tatimpaguesit e përgjithshëm nuk mund të regjistrohen për TVSH. Nëse qarkullimi juaj vjetor tejkalon 2.000.000 MKD, duhet të regjistroheni për TVSH dhe automatikisht e humbni statusin e përgjithshëm.',
          'Aktiviteti nuk është i përjashtuar — disa aktivitete nuk mund të përdorin tatim të përgjithshëm: avokatë, noterë, përmbarues, kontabilistë, këshilltarë tatimorë, mjekë me praktikë private dhe profesione të tjera të lira.',
          'Jeni regjistruar si TP (tregtar individual) ose i vetëpunësuar — subjektet juridike (DOOEL, DOO) nuk mund të jenë tatimpagues të përgjithshëm.',
          'Kërkesa e dorëzuar në UJP — statusi nuk është automatik. Duhet të dorëzoni kërkesë për tatim të përgjithshëm deri më 31 janar për vitin aktual.',
        ],
        steps: null,
      },
      {
        title: 'Si llogaritet tatimi',
        content: null,
        items: null,
        steps: [
          { step: 'Përcaktoni të ardhurat totale vjetore', desc: 'Mblidhen të gjitha të ardhurat nga aktiviteti afarist për vitin kalendarik. Kjo përfshin shërbimet e faturuara, shitjen e produkteve dhe të ardhura të tjera të lidhura me aktivitetin.' },
          { step: 'Aplikoni shpenzimet e normalizuara', desc: 'UJP njeh shpenzime të normalizuara nga 50% deri në 80% të të ardhurave, varësisht nga aktiviteti. Për shembull, shërbimet e zejtarisë marrin 80% shpenzime të njohura, ndërsa shërbimet konsulente 50%.' },
          { step: 'Llogaritni bazën tatimore', desc: 'Shpenzimet e normalizuara zbriten nga të ardhurat totale. Pjesa e mbetur është baza tatimore.' },
          { step: 'Aplikoni normën tatimore', desc: 'Norma e tatimit mbi të ardhurat personale prej 10% aplikohet mbi bazën tatimore. Gjithashtu, duhet të paguhen kontributet e pensionit dhe sigurimit shëndetësor.' },
        ],
      },
      {
        title: 'Përparësitë dhe disavantazhet',
        content: 'Tatimi i përgjithshëm ka përparësitë e veta, por edhe kufizime që duhet t\'i merrni parasysh para se të zgjidhni këtë regjim.',
        items: [
          'Përparësi: Administrim i thjeshtuar — nuk ka nevojë për kontabilitet me regjistrim të dyfishtë, vetëm evidencë të ardhurave.',
          'Përparësi: Kosto më të ulëta kontabiliteti — nuk keni nevojë për shërbim të plotë kontabiliteti.',
          'Përparësi: Tatim i parashikueshëm — e dini paraprakisht sa do të paguani.',
          'Disavantazh: Nuk mund të zbritni shpenzimet reale — nëse keni kosto reale të larta, mund të paguani më shumë tatim sesa me regjimin e rregullt.',
          'Disavantazh: Rritje e kufizuar — sapo ta tejkaloni pragun prej 3M MKD, duhet të kaloni në tatim të rregullt.',
          'Disavantazh: Pa zbritje TVSH-je — nuk mund ta riktheni TVSH-në në blerje, gjë që është problem nëse keni kosto hyrëse të konsiderueshme.',
        ],
        steps: null,
      },
      {
        title: 'Facturino dhe tatimpaguesit e përgjithshëm',
        content: 'Edhe pse tatimpaguesit e përgjithshëm kanë detyrime të thjeshtuara, prapëseprapë duhet të lëshojnë fatura dhe të mbajnë evidencë të ardhurave. Facturino është mjeti perfekt edhe për tatimpaguesit e përgjithshëm — shpejt dhe lehtë lëshoni fatura, ndiqni të ardhurat dhe merrni raporte automatike. Sistemi ju paralajmëron kur po i afroheni pragut të 3M MKD, që të mund të merrni vendim në kohë për kalimin në tatim të rregullt.',
        items: [
          'Lëshim i thjeshtë i faturave pa nevojë për njohuri kontabiliteti.',
          'Ndjekje automatike e të ardhurave vjetore me paralajmërime kur afroheni pragut.',
          'Raporte të përputhshme me kërkesat e UJP për tatimpagues të përgjithshëm.',
          'Përmbledhje e të gjitha faturave të lëshuara në një vend.',
          'Kalim i lehtë në tatim të rregullt kur të vijë koha.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'otvoranje-firma-mk', title: 'Si të hapni firmë në Maqedoni: Udhëzues i plotë' },
      { slug: 'ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
      { slug: 'personalen-danok-na-dohod', title: 'Tatimi personal mbi të ardhurat në Maqedoni' },
    ],
    cta: {
      title: 'Lëshoni fatura thjesht',
      desc: 'Pavarësisht nëse jeni tatimpagues i përgjithshëm apo i rregullt, Facturino e bën faturimin të shpejtë dhe të lehtë.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Eğitim',
    title: 'Makedonya\'da götürü vergi: Koşullar ve sınırlar',
    publishDate: '10 Şubat 2026',
    readTime: '6 dk okuma',
    intro: 'Götürü vergilendirme, Makedonya\'daki küçük işletmeler ve serbest çalışanlar için en popüler vergi rejimlerinden biridir. Tam muhasebe kayıtları tutmak yerine sabit bir aylık vergi ödersiniz. Ancak bu rejim herkes için mevcut değildir — katı koşullar ve sınırlamalar vardır. Bu rehberde kimler götürü vergi mükellefi olabilir, yükümlülükler nelerdir ve ne zaman normal vergilendirmeye geçmek daha iyidir açıklıyoruz.',
    sections: [
      {
        title: 'Götürü vergilendirme nedir?',
        content: 'Götürü vergilendirme, küçük işletmelerin ve serbest meslek sahiplerinin tam çift taraflı muhasebe kaydı tutmaya gerek kalmadan sabit vergi ödemelerine olanak tanıyan basitleştirilmiş bir vergi rejimidir. Vergi, normalleştirilmiş giderlere göre hesaplanır — UJP, gelirinizin belirli bir yüzdesinin gider olduğunu varsayar ve vergi yalnızca kalan kısım üzerinden hesaplanır. Bu sistem küçük hizmet işletmeleri, serbest çalışanlar, zanaatkarlar ve düşük cirolu tüccarlar için idealdir.',
        items: null,
        steps: null,
      },
      {
        title: 'Uygunluk kriterleri',
        content: 'Götürü rejimi kullanmak için aşağıdaki koşulları karşılamanız gerekir:',
        items: [
          'Yıllık gelir 3.000.000 MKD\'ye kadar (yaklaşık 48.800 EUR) — bu mutlak eşiktir. Aşarsanız otomatik olarak normal vergilendirmeye geçersiniz.',
          'KDV mükellefi değilsiniz — götürü vergi mükellefleri KDV\'ye kayıtlı olamaz. Yıllık cironuz 2.000.000 MKD\'yi aşarsa, KDV\'ye kaydolmanız gerekir ve götürü statüsünü otomatik olarak kaybedersiniz.',
          'Faaliyet hariç tutulmuş değil — belirli faaliyetler götürü vergilendirme kullanamaz: avukatlar, noterler, icra memurları, muhasebeciler, vergi danışmanları, özel muayenehanesi olan doktorlar ve diğer serbest meslekler.',
          'TP (tek kişilik tüccar) veya serbest meslek olarak kayıtlısınız — tüzel kişiler (DOOEL, DOO) götürü vergi mükellefi olamaz.',
          'UJP\'ye başvuru yapılmış — statü otomatik değildir. Cari yıl için 31 Ocak\'a kadar götürü vergilendirme başvurusu yapmalısınız.',
        ],
        steps: null,
      },
      {
        title: 'Vergi nasıl hesaplanır',
        content: null,
        items: null,
        steps: [
          { step: 'Toplam yıllık geliri belirleyin', desc: 'Takvim yılı için iş faaliyetinden elde edilen tüm gelirler toplanır. Bu, faturalandırılmış hizmetleri, ürün satışlarını ve faaliyetle ilgili diğer gelirleri içerir.' },
          { step: 'Normalleştirilmiş giderleri uygulayın', desc: 'UJP, faaliyete bağlı olarak gelirin %50 ila %80\'ini normalleştirilmiş gider olarak kabul eder. Örneğin, zanaat hizmetlerinde %80 gider tanınırken, danışmanlık hizmetlerinde %50 tanınır.' },
          { step: 'Vergi matrahını hesaplayın', desc: 'Normalleştirilmiş giderler toplam gelirden düşülür. Kalan kısım vergi matrahıdır.' },
          { step: 'Vergi oranını uygulayın', desc: 'Vergi matrahına %10 kişisel gelir vergisi oranı uygulanır. Ayrıca emeklilik ve sağlık sigortası katkıları da ödenmelidir.' },
        ],
      },
      {
        title: 'Avantajlar ve dezavantajlar',
        content: 'Götürü vergilendirmenin avantajları vardır, ancak bu rejimi seçmeden önce dikkate almanız gereken sınırlamaları da vardır.',
        items: [
          'Avantaj: Basitleştirilmiş yönetim — çift taraflı muhasebe gerekmez, sadece gelir kaydı.',
          'Avantaj: Daha düşük muhasebe maliyetleri — tam muhasebe hizmetine ihtiyacınız yoktur.',
          'Avantaj: Öngörülebilir vergi — ne kadar ödeyeceğinizi önceden bilirsiniz.',
          'Dezavantaj: Gerçek giderleri düşemezsiniz — yüksek gerçek maliyetleriniz varsa, normal rejime göre daha fazla vergi ödeyebilirsiniz.',
          'Dezavantaj: Sınırlı büyüme — 3M MKD eşiğini aştığınızda normal vergilendirmeye geçmeniz gerekir.',
          'Dezavantaj: KDV indirimi yok — alışlardaki KDV\'yi geri alamazsınız; önemli girdi maliyetleriniz varsa bu bir sorun olabilir.',
        ],
        steps: null,
      },
      {
        title: 'Facturino ve götürü vergi mükellefleri',
        content: 'Götürü vergi mükellefleri basitleştirilmiş yükümlülüklere sahip olsa da, yine de fatura düzenlemek ve gelir kaydı tutmak zorundadır. Facturino, götürü vergi mükellefleri için de mükemmel bir araçtır — hızlı ve kolay fatura düzenleyin, geliri takip edin ve otomatik raporlar alın. Sistem, 3M MKD eşiğine yaklaştığınızda sizi uyarır, böylece normal vergilendirmeye geçiş için zamanında karar verebilirsiniz.',
        items: [
          'Muhasebe bilgisi gerektirmeden basit fatura düzenleme.',
          'Eşik yaklaşım uyarıları ile otomatik yıllık gelir takibi.',
          'Götürü vergi mükellefleri için UJP gereksinimleriyle uyumlu raporlar.',
          'Düzenlenen tüm faturaların tek bir yerde görünümü.',
          'Zamanı geldiğinde normal vergilendirmeye kolay geçiş.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'otvoranje-firma-mk', title: "Makedonya'da şirket nasıl kurulur: Eksiksiz rehber" },
      { slug: 'ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
      { slug: 'personalen-danok-na-dohod', title: "Makedonya'da kişisel gelir vergisi" },
    ],
    cta: {
      title: 'Faturaları kolay düzenleyin',
      desc: 'Götürü veya normal vergi mükellefi olun, Facturino faturalamayı hızlı ve kolay hale getirir.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function PaushalenDanochnikPage({
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
