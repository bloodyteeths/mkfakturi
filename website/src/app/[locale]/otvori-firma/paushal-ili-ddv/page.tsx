import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/otvori-firma/paushal-ili-ddv', {
    title: {
      mk: 'Паушалец или ДДВ обврзник: даночен режим за нова фирма | Facturino',
      en: 'Lump-Sum (Paušal) or VAT-Registered: Tax Regime for a New Company | Facturino',
      sq: 'Tatimi i përgjithshëm (paushall) apo pagues i TVSH-së: regjimi tatimor për firmë të re | Facturino',
      tr: 'Götürü (Paušal) ya da KDV Mükellefi: Yeni Şirket İçin Vergi Rejimi | Facturino',
    },
    description: {
      mk: 'Како да изберете даночен режим за нова фирма во Македонија: паушален данок vs ДДВ обврзник, услови, праг од 2.000.000 МКД за задолжителна ДДВ регистрација, обврски и препорака.',
      en: 'How to choose a tax regime for a new company in North Macedonia: lump-sum (paušal) vs VAT-registered, eligibility, 2,000,000 MKD mandatory VAT threshold, obligations and a recommendation.',
      sq: 'Si të zgjidhni regjimin tatimor për një firmë të re në Maqedoni: tatimi i përgjithshëm (paushall) vs pagues i TVSH-së, kushtet, pragu 2.000.000 MKD për regjistrim të detyrueshëm dhe rekomandim.',
      tr: 'Kuzey Makedonya\'da yeni şirket için vergi rejimi nasıl seçilir: götürü (paušal) vs KDV mükellefi, koşullar, 2.000.000 MKD zorunlu KDV eşiği, yükümlülükler ve öneri.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Отвори фирма',
    tag: 'Даноци',
    title: 'Паушалец или ДДВ обврзник: даночен режим за нова фирма',
    publishDate: '20 август 2026',
    readTime: '8 мин читање',
    intro:
      'Кога отворате нова фирма во Македонија, една од првите одлуки е даночниот режим. Дали ќе бидете паушален даночник со поедноставено плаќање, или ќе работите како редовен ДДВ обврзник со целосна евиденција? Изборот зависи од вашата дејност, очекуваниот промет и структурата на трошоците. Овој водич ги објаснува двата режима, прагот за задолжителна ДДВ регистрација и дава јасна препорака за нова фирма.',
    sections: [
      {
        title: 'Што е паушален данок',
        content:
          'Паушалниот данок е поедноставен даночен режим наменет за мали самостојни вршители на дејност — трговци поединци (ТП), занаетчии и фриленсери со мал обем на работа. Наместо да водите целосно двојно книговодство, плаќате фиксен данок пресметан врз основа на нормирани трошоци: УЈП претпоставува дека одреден процент од вашиот приход се трошоци и данокот се пресметува само на остатокот. Стапката на персоналниот данок на доход е 10%. Овој режим е идеален за услужни дејности со ниски реални трошоци кои сакаат предвидлива и едноставна администрација.',
        items: null,
        steps: null,
      },
      {
        title: 'Услови и ограничувања за паушал',
        content:
          'Паушалниот режим не е достапен за секого. За да го користите, мора да ги исполните следните услови:',
        items: [
          'Не сте ДДВ обврзник — паушалците не можат да бидат регистрирани за ДДВ. Ако вашиот годишен промет надмине 2.000.000 МКД, мора да се регистрирате за ДДВ и автоматски го губите паушалниот статус.',
          'Регистрирани сте како трговец поединец (ТП) или самостоен вршител на дејност — правните лица (ДООЕЛ, ДОО) не можат да бидат паушалци.',
          'Дејноста не е исклучена — слободни професии како адвокати, нотари, извршители, сметководители и даночни советници не можат да користат паушал.',
          'Поднесувате барање до УЈП — статусот не е автоматски, се бара со барање за паушално оданочување.',
          'Плаќате и придонеси за пензиско и здравствено осигурување покрај персоналниот данок од 10%.',
        ],
        steps: null,
      },
      {
        title: 'Што значи да си ДДВ обврзник',
        content:
          'ДДВ обврзник е бизнис регистриран за данок на додадена вредност во УЈП. Тоа значи дека на своите фактури наплаќате ДДВ (18% стандардна, 5% или 10% намалени стапки), но истовремено имате право да го одбиете влезниот ДДВ платен на набавки. Обврските се поголеми: водење книга на влезни (ДДВ-01) и излезни (ДДВ-02) фактури, како и поднесување квартална ДДВ-04 пријава до УЈП. Секоја фактура мора да содржи ДДВ број, стапка и износ на ДДВ. Овој режим носи поголема администрација, но и предности како одбиток на влезен ДДВ и подобност за државни тендери.',
        items: null,
        steps: null,
      },
      {
        title: 'Праг за задолжителна ДДВ регистрација',
        content:
          'Клучниот број кој го определува вашиот режим е прагот за ДДВ регистрација:',
        items: [
          'Задолжителна регистрација: годишен промет над 2.000.000 МКД (приближно 32.500 EUR) во претходните 12 месеци. Штом го надминете, мора да се регистрирате за ДДВ во рок од 15 дена.',
          'Доброволна регистрација: под прагот, секој бизнис може доброволно да се регистрира за ДДВ — корисно за B2B работа и одбивање на влезен ДДВ.',
          'Надминувањето на прагот автоматски го исклучува паушалниот статус — паушалец не може да биде ДДВ обврзник.',
          'Странските субјекти кои вршат оданочиви активности во земјата подлежат на задолжителна регистрација без оглед на прагот.',
        ],
        steps: null,
      },
      {
        title: 'Споредба: паушал vs ДДВ',
        content:
          'Двата режима се разликуваат по трошоци, обврски и евиденција. Еве ги клучните разлики:',
        items: [
          'Евиденција: паушал бара само евиденција на приходи; ДДВ обврзник води ДДВ-01, ДДВ-02 и поднесува ДДВ-04.',
          'Данок: паушал плаќа 10% на нормирана основица; ДДВ обврзник наплаќа ДДВ на продажба и одбива на набавки.',
          'Одбиток на влезен ДДВ: паушалец НЕ може да го врати ДДВ на набавки; ДДВ обврзник може.',
          'Трошоци за сметководство: паушал е поевтин — нема потреба од целосна сметководствена услуга.',
          'Кредибилитет и B2B: ДДВ бројот е предност кај деловни партнери и задолжителен за државни тендери.',
          'Раст: паушал е ограничен со прагот од 2.000.000 МКД; над него мора да преминете на ДДВ режим.',
        ],
        steps: null,
      },
      {
        title: 'Кој режим за нова фирма — препорака',
        content:
          'Изборот зависи од вашиот профил. Еве насоки за нова фирма:',
        items: null,
        steps: [
          { step: 'Мал услужен бизнис со ниски трошоци → паушал', desc: 'Ако сте ТП или фриленсер со очекуван промет далеку под 2.000.000 МКД и малку реални трошоци, паушалот нуди најмала администрација и предвидлив данок.' },
          { step: 'B2B работа или државни тендери → ДДВ обврзник', desc: 'Ако продавате на други фирми или сакате да учествувате во јавни набавки, доброволната ДДВ регистрација носи кредибилитет и одбиток на влезен ДДВ.' },
          { step: 'Значителни влезни трошоци → ДДВ обврзник', desc: 'Ако набавувате многу стока, опрема или услуги со ДДВ, како ДДВ обврзник го враќате тој ДДВ — што паушалец не може.' },
          { step: 'Брз раст над прагот → планирајте ДДВ однапред', desc: 'Ако очекувате брзо да го надминете прагот од 2.000.000 МКД, доброволната регистрација однапред ве штеди од итно префрлување и казни.' },
          { step: 'Правно лице (ДООЕЛ/ДОО) → редовен режим', desc: 'Правните лица не можат да бидат паушалци — работат по редовен режим со можност за доброволна или задолжителна ДДВ регистрација.' },
        ],
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'ДООЕЛ, ДОО или ТП? Која правна форма да изберете' },
      { slug: 'za-stranci', title: 'Отворање фирма за странци во Македонија' },
      { slug: 'trgovec-poedinec', title: 'Трговец поединец (ТП): водич за самостојна дејност' },
    ],
    bottomCta: {
      title: 'Спремни за вашата нова фирма?',
      subtitle: 'Facturino работи и за паушалци и за ДДВ обврзници — фактури, ДДВ извештаи и е-фактура на едно место.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Start a company',
    tag: 'Taxes',
    title: 'Lump-Sum (Paušal) or VAT-Registered: Tax Regime for a New Company',
    publishDate: 'August 20, 2026',
    readTime: '8 min read',
    intro:
      'When you open a new company in North Macedonia, one of the first decisions is your tax regime. Will you be a lump-sum (paušal) taxpayer with simplified payments, or a regular VAT-registered business with full record-keeping? The choice depends on your activity, expected turnover and cost structure. This guide explains both regimes, the mandatory VAT registration threshold, and gives a clear recommendation for a new company.',
    sections: [
      {
        title: 'What is lump-sum (paušal) tax',
        content:
          'Lump-sum tax is a simplified tax regime intended for small self-employed individuals — sole traders (TP), craftspeople and freelancers with low turnover. Instead of maintaining full double-entry bookkeeping, you pay a fixed tax calculated on normalized expenses: UJP assumes a certain percentage of your revenue consists of costs, and tax is applied only to the remainder. The personal income tax rate is 10%. This regime is ideal for service businesses with low actual costs that want predictable and simple administration.',
        items: null,
        steps: null,
      },
      {
        title: 'Eligibility and limits for lump-sum',
        content:
          'The lump-sum regime is not available to everyone. To use it, you must meet the following conditions:',
        items: [
          'Not a VAT taxpayer — lump-sum taxpayers cannot be registered for VAT. If your annual turnover exceeds 2,000,000 MKD, you must register for VAT and automatically lose lump-sum status.',
          'Registered as a sole trader (TP) or self-employed — legal entities (DOOEL, DOO) cannot be lump-sum taxpayers.',
          'Activity is not excluded — liberal professions such as lawyers, notaries, enforcement agents, accountants and tax advisors cannot use lump-sum.',
          'You submit an application to UJP — the status is not automatic; it requires a lump-sum taxation application.',
          'You also pay pension and health insurance contributions in addition to the 10% personal income tax.',
        ],
        steps: null,
      },
      {
        title: 'What it means to be VAT-registered',
        content:
          'A VAT payer is a business registered for value added tax with UJP. It means you charge VAT on your invoices (18% standard, 5% or 10% reduced rates), but you also have the right to deduct input VAT paid on purchases. The obligations are larger: keeping input (DDV-01) and output (DDV-02) invoice ledgers, and filing a quarterly DDV-04 return with UJP. Every invoice must include a VAT number, rate and VAT amount. This regime carries more administration, but also advantages such as input VAT deduction and eligibility for government tenders.',
        items: null,
        steps: null,
      },
      {
        title: 'Mandatory VAT registration threshold',
        content:
          'The key number that determines your regime is the VAT registration threshold:',
        items: [
          'Mandatory registration: annual turnover above 2,000,000 MKD (approximately EUR 32,500) in the preceding 12 months. Once exceeded, you must register for VAT within 15 days.',
          'Voluntary registration: below the threshold, any business may voluntarily register for VAT — useful for B2B work and input VAT deduction.',
          'Exceeding the threshold automatically ends lump-sum status — a lump-sum taxpayer cannot be VAT-registered.',
          'Foreign entities performing taxable activities in the country are subject to mandatory registration regardless of the threshold.',
        ],
        steps: null,
      },
      {
        title: 'Comparison: lump-sum vs VAT',
        content:
          'The two regimes differ in cost, obligations and record-keeping. Here are the key differences:',
        items: [
          'Record-keeping: lump-sum requires only revenue records; a VAT payer keeps DDV-01, DDV-02 and files DDV-04.',
          'Tax: lump-sum pays 10% on a normalized base; a VAT payer charges VAT on sales and deducts it on purchases.',
          'Input VAT deduction: a lump-sum taxpayer CANNOT reclaim VAT on purchases; a VAT payer can.',
          'Accounting cost: lump-sum is cheaper — no full accounting service needed.',
          'Credibility and B2B: a VAT number is an advantage with business partners and mandatory for government tenders.',
          'Growth: lump-sum is capped by the 2,000,000 MKD threshold; above it you must switch to the VAT regime.',
        ],
        steps: null,
      },
      {
        title: 'Which regime for a new company — recommendation',
        content:
          'The choice depends on your profile. Here is guidance for a new company:',
        items: null,
        steps: [
          { step: 'Small service business with low costs → lump-sum', desc: 'If you are a sole trader or freelancer with expected turnover well below 2,000,000 MKD and few actual costs, lump-sum offers the least administration and predictable tax.' },
          { step: 'B2B work or government tenders → VAT-registered', desc: 'If you sell to other companies or want to participate in public procurement, voluntary VAT registration brings credibility and input VAT deduction.' },
          { step: 'Significant input costs → VAT-registered', desc: 'If you buy a lot of goods, equipment or services with VAT, as a VAT payer you reclaim that VAT — which a lump-sum taxpayer cannot.' },
          { step: 'Rapid growth past the threshold → plan VAT ahead', desc: 'If you expect to quickly exceed the 2,000,000 MKD threshold, registering voluntarily in advance saves you from an urgent switch and penalties.' },
          { step: 'Legal entity (DOOEL/DOO) → regular regime', desc: 'Legal entities cannot be lump-sum taxpayers — they operate under the regular regime with the option of voluntary or mandatory VAT registration.' },
        ],
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO or TP? Which Legal Form to Choose' },
      { slug: 'za-stranci', title: 'Starting a Company as a Foreigner in Macedonia' },
      { slug: 'trgovec-poedinec', title: 'Sole Trader (TP): A Guide to Self-Employment' },
    ],
    bottomCta: {
      title: 'Ready for your new company?',
      subtitle: 'Facturino works for both lump-sum and VAT-registered businesses — invoices, VAT reports and e-invoicing in one place.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Hap firmë',
    tag: 'Tatimet',
    title: 'Tatimi i përgjithshëm (paushall) apo pagues i TVSH-së: regjimi tatimor për firmë të re',
    publishDate: '20 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Kur hapni një firmë të re në Maqedoni, një nga vendimet e para është regjimi tatimor. A do të jeni tatimpagues i përgjithshëm (paushall) me pagesa të thjeshtuara, apo pagues i rregullt i TVSH-së me evidencë të plotë? Zgjedhja varet nga aktiviteti juaj, qarkullimi i pritur dhe struktura e kostove. Ky udhëzues shpjegon të dy regjimet, pragun për regjistrim të detyrueshëm të TVSH-së dhe jep një rekomandim të qartë për firmë të re.',
    sections: [
      {
        title: 'Çfarë është tatimi i përgjithshëm (paushall)',
        content:
          'Tatimi i përgjithshëm është një regjim tatimor i thjeshtuar i destinuar për persona të vetëpunësuar të vegjël — tregtarë individualë (TP), zejtarë dhe punëtorë të pavarur me qarkullim të ulët. Në vend që të mbani kontabilitet të plotë me regjistrim të dyfishtë, paguani një tatim fiks të llogaritur mbi shpenzime të normalizuara: UJP supozon se një përqindje e caktuar e të ardhurave janë shpenzime dhe tatimi aplikohet vetëm mbi pjesën e mbetur. Norma e tatimit personal mbi të ardhurat është 10%. Ky regjim është ideal për biznese shërbimi me kosto reale të ulëta që duan administrim të parashikueshëm dhe të thjeshtë.',
        items: null,
        steps: null,
      },
      {
        title: 'Kushtet dhe kufizimet për paushall',
        content:
          'Regjimi i përgjithshëm nuk është i disponueshëm për të gjithë. Për ta përdorur, duhet të plotësoni kushtet e mëposhtme:',
        items: [
          'Nuk jeni pagues i TVSH-së — tatimpaguesit e përgjithshëm nuk mund të regjistrohen për TVSH. Nëse qarkullimi juaj vjetor tejkalon 2.000.000 MKD, duhet të regjistroheni për TVSH dhe automatikisht e humbni statusin.',
          'Jeni regjistruar si tregtar individual (TP) ose i vetëpunësuar — subjektet juridike (DOOEL, DOO) nuk mund të jenë tatimpagues të përgjithshëm.',
          'Aktiviteti nuk është i përjashtuar — profesionet e lira si avokatë, noterë, përmbarues, kontabilistë dhe këshilltarë tatimorë nuk mund të përdorin paushall.',
          'Dorëzoni kërkesë në UJP — statusi nuk është automatik; kërkon një kërkesë për tatim të përgjithshëm.',
          'Paguani gjithashtu kontributet e pensionit dhe sigurimit shëndetësor përveç tatimit personal prej 10%.',
        ],
        steps: null,
      },
      {
        title: 'Çfarë do të thotë të jesh pagues i TVSH-së',
        content:
          'Një pagues i TVSH-së është një biznes i regjistruar për tatimin mbi vlerën e shtuar në UJP. Kjo do të thotë se ngarkoni TVSH në faturat tuaja (18% standarde, 5% ose 10% të reduktuara), por gjithashtu keni të drejtë të zbritni TVSH-në hyrëse të paguar për blerje. Detyrimet janë më të mëdha: mbajtja e librave të faturave hyrëse (DDV-01) dhe dalëse (DDV-02), si dhe dorëzimi i deklaratës tremujore DDV-04 në UJP. Çdo faturë duhet të përmbajë numrin e TVSH-së, normën dhe shumën e TVSH-së. Ky regjim bart më shumë administrim, por edhe përparësi si zbritja e TVSH-së hyrëse dhe përshtatshmëria për tenderat shtetërore.',
        items: null,
        steps: null,
      },
      {
        title: 'Pragu për regjistrim të detyrueshëm të TVSH-së',
        content:
          'Numri kyç që përcakton regjimin tuaj është pragu për regjistrim TVSH:',
        items: [
          'Regjistrimi i detyrueshëm: qarkullim vjetor mbi 2.000.000 MKD (afërsisht 32.500 EUR) në 12 muajt e fundit. Sapo ta tejkaloni, duhet të regjistroheni për TVSH brenda 15 ditëve.',
          'Regjistrimi vullnetar: nën prag, çdo biznes mund të regjistrohet vullnetarisht për TVSH — i dobishëm për punë B2B dhe zbritjen e TVSH-së hyrëse.',
          'Tejkalimi i pragut automatikisht i jep fund statusit të përgjithshëm — një tatimpagues i përgjithshëm nuk mund të jetë pagues i TVSH-së.',
          'Subjektet e huaja që kryejnë aktivitete të tatueshme në vend i nënshtrohen regjistrimit të detyrueshëm pavarësisht pragut.',
        ],
        steps: null,
      },
      {
        title: 'Krahasimi: paushall vs TVSH',
        content:
          'Të dy regjimet ndryshojnë në kosto, detyrime dhe evidencë. Këto janë ndryshimet kryesore:',
        items: [
          'Evidenca: paushalli kërkon vetëm evidencë të ardhurash; një pagues i TVSH-së mban DDV-01, DDV-02 dhe dorëzon DDV-04.',
          'Tatimi: paushalli paguan 10% mbi një bazë të normalizuar; një pagues i TVSH-së ngarkon TVSH në shitje dhe e zbret në blerje.',
          'Zbritja e TVSH-së hyrëse: një tatimpagues i përgjithshëm NUK mund ta rikthejë TVSH-në në blerje; një pagues i TVSH-së mundet.',
          'Kostoja e kontabilitetit: paushalli është më i lirë — pa nevojë për shërbim të plotë kontabiliteti.',
          'Besueshmëria dhe B2B: numri i TVSH-së është përparësi tek partnerët dhe i detyrueshëm për tenderat shtetërore.',
          'Rritja: paushalli kufizohet nga pragu 2.000.000 MKD; mbi të duhet të kaloni në regjimin e TVSH-së.',
        ],
        steps: null,
      },
      {
        title: 'Cili regjim për firmë të re — rekomandim',
        content:
          'Zgjedhja varet nga profili juaj. Këto janë udhëzime për firmë të re:',
        items: null,
        steps: [
          { step: 'Biznes i vogël shërbimi me kosto të ulëta → paushall', desc: 'Nëse jeni tregtar individual ose i pavarur me qarkullim të pritur shumë nën 2.000.000 MKD dhe pak kosto reale, paushalli ofron administrimin më të vogël dhe tatim të parashikueshëm.' },
          { step: 'Punë B2B ose tenderat shtetërore → pagues i TVSH-së', desc: 'Nëse shisni tek kompani të tjera ose doni të merrni pjesë në prokurime publike, regjistrimi vullnetar i TVSH-së sjell besueshmëri dhe zbritje të TVSH-së hyrëse.' },
          { step: 'Kosto hyrëse të konsiderueshme → pagues i TVSH-së', desc: 'Nëse blini shumë mallra, pajisje ose shërbime me TVSH, si pagues i TVSH-së e riktheni atë TVSH — gjë që një tatimpagues i përgjithshëm nuk mundet.' },
          { step: 'Rritje e shpejtë mbi prag → planifikoni TVSH paraprakisht', desc: 'Nëse prisni ta tejkaloni shpejt pragun 2.000.000 MKD, regjistrimi vullnetar paraprakisht ju kursen nga një kalim urgjent dhe gjoba.' },
          { step: 'Subjekt juridik (DOOEL/DOO) → regjim i rregullt', desc: 'Subjektet juridike nuk mund të jenë tatimpagues të përgjithshëm — ato veprojnë sipas regjimit të rregullt me opsionin e regjistrimit vullnetar ose të detyrueshëm të TVSH-së.' },
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO apo TP? Cilën formë juridike të zgjidhni' },
      { slug: 'za-stranci', title: 'Hapja e firmës si i huaj në Maqedoni' },
      { slug: 'trgovec-poedinec', title: 'Tregtar individual (TP): udhëzues për vetëpunësim' },
    ],
    bottomCta: {
      title: 'Gati për firmën tuaj të re?',
      subtitle: 'Facturino funksionon si për tatimpaguesit e përgjithshëm ashtu edhe për paguesit e TVSH-së — fatura, raporte TVSH dhe e-faturë në një vend.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Şirket kur',
    tag: 'Vergiler',
    title: 'Götürü (Paušal) ya da KDV Mükellefi: Yeni Şirket İçin Vergi Rejimi',
    publishDate: '20 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Kuzey Makedonya\'da yeni bir şirket açtığınızda ilk kararlardan biri vergi rejiminizdir. Basitleştirilmiş ödemelerle götürü (paušal) vergi mükellefi mi olacaksınız, yoksa tam kayıt tutan normal bir KDV mükellefi mi? Seçim faaliyetinize, beklenen cironuza ve maliyet yapınıza bağlıdır. Bu rehber her iki rejimi, zorunlu KDV kayıt eşiğini açıklar ve yeni bir şirket için net bir öneri sunar.',
    sections: [
      {
        title: 'Götürü (paušal) vergi nedir',
        content:
          'Götürü vergi, düşük cirolu küçük serbest meslek sahipleri — tek kişilik tüccarlar (TP), zanaatkarlar ve serbest çalışanlar — için tasarlanmış basitleştirilmiş bir vergi rejimidir. Tam çift taraflı muhasebe tutmak yerine, normalleştirilmiş giderler üzerinden hesaplanan sabit bir vergi ödersiniz: UJP gelirinizin belirli bir yüzdesinin gider olduğunu varsayar ve vergi yalnızca kalan kısma uygulanır. Kişisel gelir vergisi oranı %10\'dur. Bu rejim, öngörülebilir ve basit yönetim isteyen düşük gerçek maliyetli hizmet işletmeleri için idealdir.',
        items: null,
        steps: null,
      },
      {
        title: 'Götürü için koşullar ve sınırlamalar',
        content:
          'Götürü rejim herkes için mevcut değildir. Kullanmak için aşağıdaki koşulları karşılamanız gerekir:',
        items: [
          'KDV mükellefi değilsiniz — götürü vergi mükellefleri KDV\'ye kayıtlı olamaz. Yıllık cironuz 2.000.000 MKD\'yi aşarsa, KDV\'ye kaydolmanız gerekir ve statüyü otomatik olarak kaybedersiniz.',
          'Tek kişilik tüccar (TP) veya serbest meslek olarak kayıtlısınız — tüzel kişiler (DOOEL, DOO) götürü vergi mükellefi olamaz.',
          'Faaliyet hariç tutulmuş değil — avukatlar, noterler, icra memurları, muhasebeciler ve vergi danışmanları gibi serbest meslekler götürüyü kullanamaz.',
          'UJP\'ye başvuru yaparsınız — statü otomatik değildir; götürü vergilendirme başvurusu gerektirir.',
          '%10 kişisel gelir vergisine ek olarak emeklilik ve sağlık sigortası katkılarını da ödersiniz.',
        ],
        steps: null,
      },
      {
        title: 'KDV mükellefi olmak ne anlama gelir',
        content:
          'KDV mükellefi, UJP\'de katma değer vergisi için kayıtlı bir işletmedir. Bu, faturalarınızda KDV tahsil ettiğiniz (%18 standart, %5 veya %10 indirimli oranlar) anlamına gelir, ancak alımlarda ödenen giriş KDV\'sini indirme hakkınız da vardır. Yükümlülükler daha büyüktür: giriş (DDV-01) ve çıkış (DDV-02) fatura defterlerinin tutulması ve UJP\'ye üç aylık DDV-04 beyannamesinin verilmesi. Her fatura bir KDV numarası, oran ve KDV tutarı içermelidir. Bu rejim daha fazla yönetim gerektirir, ancak giriş KDV indirimi ve devlet ihalelerine uygunluk gibi avantajlar da sağlar.',
        items: null,
        steps: null,
      },
      {
        title: 'Zorunlu KDV kayıt eşiği',
        content:
          'Rejiminizi belirleyen anahtar sayı KDV kayıt eşiğidir:',
        items: [
          'Zorunlu kayıt: son 12 ayda 2.000.000 MKD (yaklaşık 32.500 EUR) üzerinde yıllık ciro. Aştığınızda 15 gün içinde KDV\'ye kaydolmanız gerekir.',
          'Gönüllü kayıt: eşiğin altında, her işletme gönüllü olarak KDV\'ye kaydolabilir — B2B işler ve giriş KDV indirimi için yararlıdır.',
          'Eşiğin aşılması götürü statüsünü otomatik olarak sona erdirir — bir götürü vergi mükellefi KDV\'ye kayıtlı olamaz.',
          'Ülkede vergilendirilebilir faaliyet yürüten yabancı kuruluşlar eşikten bağımsız olarak zorunlu kayda tabidir.',
        ],
        steps: null,
      },
      {
        title: 'Karşılaştırma: götürü vs KDV',
        content:
          'İki rejim maliyet, yükümlülükler ve kayıt tutma açısından farklıdır. İşte temel farklar:',
        items: [
          'Kayıt tutma: götürü yalnızca gelir kaydı gerektirir; bir KDV mükellefi DDV-01, DDV-02 tutar ve DDV-04 verir.',
          'Vergi: götürü normalleştirilmiş bir matrah üzerinden %10 öder; bir KDV mükellefi satışta KDV tahsil eder ve alışta indirir.',
          'Giriş KDV indirimi: bir götürü vergi mükellefi alımlardaki KDV\'yi geri ALAMAZ; bir KDV mükellefi alabilir.',
          'Muhasebe maliyeti: götürü daha ucuzdur — tam muhasebe hizmetine gerek yoktur.',
          'Güvenilirlik ve B2B: bir KDV numarası iş ortakları nezdinde avantajdır ve devlet ihaleleri için zorunludur.',
          'Büyüme: götürü 2.000.000 MKD eşiği ile sınırlıdır; üzerinde KDV rejimine geçmeniz gerekir.',
        ],
        steps: null,
      },
      {
        title: 'Yeni şirket için hangi rejim — öneri',
        content:
          'Seçim profilinize bağlıdır. İşte yeni bir şirket için rehberlik:',
        items: null,
        steps: [
          { step: 'Düşük maliyetli küçük hizmet işletmesi → götürü', desc: 'Beklenen cirosu 2.000.000 MKD\'nin çok altında ve az gerçek maliyeti olan bir tek kişilik tüccar veya serbest çalışansanız, götürü en az yönetimi ve öngörülebilir vergiyi sunar.' },
          { step: 'B2B işler veya devlet ihaleleri → KDV mükellefi', desc: 'Diğer şirketlere satış yapıyorsanız veya kamu ihalelerine katılmak istiyorsanız, gönüllü KDV kaydı güvenilirlik ve giriş KDV indirimi getirir.' },
          { step: 'Önemli giriş maliyetleri → KDV mükellefi', desc: 'KDV\'li çok sayıda mal, ekipman veya hizmet satın alıyorsanız, KDV mükellefi olarak bu KDV\'yi geri alırsınız — bir götürü mükellefi bunu yapamaz.' },
          { step: 'Eşiğin üzerine hızlı büyüme → KDV\'yi önceden planlayın', desc: '2.000.000 MKD eşiğini hızla aşmayı bekliyorsanız, önceden gönüllü kayıt sizi acil geçişten ve cezalardan kurtarır.' },
          { step: 'Tüzel kişi (DOOEL/DOO) → normal rejim', desc: 'Tüzel kişiler götürü vergi mükellefi olamaz — gönüllü veya zorunlu KDV kaydı seçeneğiyle normal rejim altında faaliyet gösterirler.' },
        ],
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'dooel-ili-doo', title: 'DOOEL, DOO ya da TP? Hangi Hukuki Formu Seçmeli' },
      { slug: 'za-stranci', title: 'Yabancı Olarak Makedonya\'da Şirket Kurma' },
      { slug: 'trgovec-poedinec', title: 'Tek Kişilik Tüccar (TP): Serbest Meslek Rehberi' },
    ],
    bottomCta: {
      title: 'Yeni şirketiniz için hazır mısınız?',
      subtitle: 'Facturino hem götürü hem de KDV mükellefi işletmeler için çalışır — faturalar, KDV raporları ve e-fatura tek bir yerde.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function PaushalIliDdvPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const homeLabel = { mk: 'Почетна', en: 'Home', sq: 'Ballina', tr: 'Ana Sayfa' }[locale]
  const hubLabel = { mk: 'Отвори фирма', en: 'Start a Company', sq: 'Hapja e firmës', tr: 'Şirket kurma' }[locale]

  const articleLd = articleJsonLd({
    locale,
    pathPrefix: 'otvori-firma',
    slug: 'paushal-ili-ddv',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-08-20',
    tags: ['паушал', 'ДДВ', 'paušal', 'lump-sum tax', 'VAT', 'tax regime', 'nova firma', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: hubLabel, href: `/${locale}/otvori-firma` },
    { name: t.title, href: `/${locale}/otvori-firma/paushal-ili-ddv` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кој е прагот за задолжителна ДДВ регистрација?', answer: 'Прагот за задолжителна ДДВ регистрација е 2.000.000 МКД годишен промет (приближно 32.500 EUR) во претходните 12 месеци. Под прагот, регистрацијата е доброволна.' },
        { question: 'Дали паушалец може да биде ДДВ обврзник?', answer: 'Не. Паушалците не можат да бидат регистрирани за ДДВ. Штом го надминете прагот од 2.000.000 МКД, мора да се регистрирате за ДДВ и автоматски го губите паушалниот статус.' },
        { question: 'Која е даночната стапка за паушал?', answer: 'Персоналниот данок на доход е 10%, пресметан на нормирана основица. Дополнително се плаќаат придонеси за пензиско и здравствено осигурување.' },
        { question: 'Дали правно лице (ДООЕЛ или ДОО) може да биде паушалец?', answer: 'Не. Само трговци поединци (ТП) и самостојни вршители на дејност можат да бидат паушалци. Правните лица работат по редовен режим.' },
        { question: 'Кој режим е подобар за нова фирма?', answer: 'Зависи од профилот: паушал за мали услужни дејности со ниски трошоци и промет под прагот; ДДВ обврзник за B2B работа, државни тендери или значителни влезни трошоци каде одбитокот на влезен ДДВ носи предност.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/otvori-firma`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
            {t.backLink}
          </Link>

          <article>
            <header className="mb-10">
              <span className="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                {t.tag}
              </span>
              <h1 className="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-3 leading-tight">
                {t.title}
              </h1>
              <p className="text-sm text-gray-500">
                {t.publishDate} · {t.readTime}
              </p>
            </header>

            <div className="prose prose-lg max-w-none">
              <p className="text-lg text-gray-700 leading-relaxed mb-8">{t.intro}</p>

              {t.sections.map((s, i) => (
                <section key={i} className="mb-10">
                  <h2 className="text-2xl font-bold text-gray-900 mb-4">{s.title}</h2>
                  <p className="text-gray-700 leading-relaxed mb-4">{s.content}</p>

                  {s.items && (
                    <ul className="space-y-2 mb-4">
                      {s.items.map((item, j) => (
                        <li key={j} className="flex items-start gap-2">
                          <span className="text-blue-500 mt-1.5 text-xs">●</span>
                          <span className="text-gray-700">{item}</span>
                        </li>
                      ))}
                    </ul>
                  )}

                  {s.steps && (
                    <div className="space-y-4 mb-4">
                      {s.steps.map((step, j) => (
                        <div key={j} className="flex items-start gap-3">
                          <span className="flex-shrink-0 w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-sm font-bold">
                            {j + 1}
                          </span>
                          <div>
                            <p className="font-semibold text-gray-900">{step.step}</p>
                            <p className="text-gray-600 text-sm mt-1">{step.desc}</p>
                          </div>
                        </div>
                      ))}
                    </div>
                  )}
                </section>
              ))}
            </div>

            <aside className="mt-12 pt-8 border-t border-gray-200">
              <h3 className="text-lg font-bold text-gray-900 mb-4">{t.relatedTitle}</h3>
              <div className="grid gap-3">
                {t.relatedArticles.map((ra, i) => (
                  <Link
                    key={i}
                    href={`/${locale}/otvori-firma/${ra.slug}`}
                    className="text-blue-600 hover:text-blue-800 hover:underline"
                  >
                    {ra.title}
                  </Link>
                ))}
              </div>
            </aside>
          </article>

          <div className="mt-16 bg-gradient-to-r from-blue-600 to-cyan-500 rounded-2xl p-8 sm:p-12 text-center text-white">
            <h2 className="text-2xl sm:text-3xl font-extrabold mb-3">{t.bottomCta.title}</h2>
            <p className="text-blue-100 mb-6 text-lg">{t.bottomCta.subtitle}</p>
            <a
              href={t.bottomCta.href}
              className="inline-block bg-white text-blue-700 font-bold px-8 py-3.5 rounded-xl hover:bg-blue-50 transition-colors text-lg shadow-lg"
            >
              {t.bottomCta.cta}
            </a>
          </div>
        </div>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
