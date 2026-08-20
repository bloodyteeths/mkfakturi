import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/casuvanje-i-arhiva', {
    title: {
      mk: 'Чување и е-архива на е-фактури: 10-годишна обврска',
      en: 'Storage & E-Archiving of E-Invoices: The 10-Year Rule',
      sq: 'Ruajtja dhe e-arkivimi i e-faturave: detyrimi 10-vjeçar',
      tr: 'E-Faturaların Saklanması ve E-Arşivi: 10 Yıl Kuralı',
    },
    description: {
      mk: 'Обврска за чување на е-фактури минимум 10 години во оригинален UBL 2.1 XML формат со зачуван QES потпис. Барања за е-архива, најдобри практики и ризици при неусогласено чување.',
      en: 'Obligation to keep e-invoices for a minimum of 10 years in original UBL 2.1 XML format with the QES signature preserved. E-archive requirements, best practices, and the risks of non-compliant storage.',
      sq: 'Detyrimi për ruajtjen e e-faturave minimum 10 vjet në formatin origjinal UBL 2.1 XML me nënshkrimin QES të ruajtur. Kërkesat për e-arkivë, praktikat më të mira dhe rreziqet e ruajtjes jopajtueshme.',
      tr: 'E-faturaların QES imzası korunarak orijinal UBL 2.1 XML formatında en az 10 yıl saklanması yükümlülüğü. E-arşiv gereksinimleri, en iyi uygulamalar ve uyumsuz saklamanın riskleri.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'Архивирање',
    title: 'Чување и е-архива на е-фактури: 10-годишна обврска',
    publishDate: '18 август 2026',
    readTime: '8 мин читање',
    intro:
      'Издавањето е-фактура е само првиот чекор — законот бара таа да се чува правилно долги години. Е-фактурите мораат да се архивираат минимум 10 години во својот оригинален UBL 2.1 XML формат, со зачуван квалификуван електронски потпис (QES). Печатен PDF не е доволен и не ја исполнува обврската. Овој водич објаснува што значи законско е-архивирање, кои се барањата за интегритет и читливост, најдобрите практики и како Facturino ги чува вашите фактури и документи безбедно во платформата.',
    sections: [
      {
        title: 'Законска обврска за архивирање',
        content:
          'Според даночните прописи, е-фактурите мора да се чуваат минимум 10 години сметано од крајот на годината во која се издадени. Клучно е дека фактурата мора да остане во својот оригинален електронски формат — истиот структуриран UBL 2.1 XML во кој е издадена и потпишана. Не е дозволено оригиналот да се замени со скенирана копија или испечатена верзија. Обврската важи подеднакво за издадените (излезни) и примените (влезни) е-фактури, бидејќи и двете се дел од даночната документација која УЈП може да ја побара при контрола.',
        items: null,
        steps: null,
      },
      {
        title: 'Што значи „оригинален формат“',
        content:
          'Оригиналниот формат на една е-фактура не е PDF-от што го гледате на екран, туку самиот UBL 2.1 XML документ заедно со неговиот QES потпис. Потписот е математички врзан за содржината на XML-от и служи како доказ дека фактурата не е изменета по издавањето. Ако чувате само испечатен или PDF „преглед“, го губите токму тоа што го прави документот правно валиден:',
        items: [
          'UBL 2.1 XML датотеката е вистинскиот оригинал — таа содржи структурирани, машински читливи податоци',
          'QES потписот мора да се сочува заедно со XML-от — без него не може да се докаже автентичноста',
          'PDF или испечатена копија е само визуелен приказ, не и правно валиден оригинал',
          'Секоја измена на XML-от го поништува потписот, па оригиналот мора да остане недопрен',
        ],
        steps: null,
      },
      {
        title: 'Барања за е-архива',
        content:
          'За да биде архивирањето усогласено, чувањето мора да гарантира дека фактурите остануваат веродостојни и достапни низ целиот 10-годишен период. Основните барања се:',
        items: [
          'Интегритет — содржината на фактурата не смее да се менува, а секоја промена мора да биде откриена (тука помага QES потписот)',
          'Автентичност — мора да е недвосмислено кој ја издал фактурата и кому е упатена',
          'Читливост — фактурите мора да останат читливи и обработливи во текот на целите 10 години, без оглед на промени во софтверот',
          'Пронајдливост — при даночна контрола фактурите мора брзо да се пронајдат и да се достават на УЈП во оригинален електронски формат',
          'Траен пристап — форматот и медиумот за чување мора да останат достапни и по надградби на системите',
        ],
        steps: null,
      },
      {
        title: 'Најдобри практики за е-архивирање',
        content:
          'Усогласеното е-архивирање е повеќе организациски отколку технички предизвик. Следните чекори помагаат да се обезбеди дека вашите е-фактури ќе бидат безбедни и достапни низ целата законска обврска:',
        items: null,
        steps: [
          { step: 'Чувајте го оригиналниот XML со потписот', desc: 'За секоја фактура архивирајте ја UBL 2.1 XML датотеката заедно со нејзиниот QES потпис — не само PDF преглед. Ова е правно валидниот оригинал.' },
          { step: 'Обезбедете сигурни резервни копии (backup)', desc: 'Држете најмалку една дополнителна копија на друга локација. Единечна копија на еден диск е ризик — дефект или бришење значи трајна загуба.' },
          { step: 'Индексирано и пребарливо чување', desc: 'Организирајте ги фактурите по година, број, партнер и ЕДБ така што секоја може брзо да се пронајде — особено кога УЈП побара конкретна фактура.' },
          { step: 'Контрола на пристап', desc: 'Ограничете кој може да ги гледа, менува или брише архивираните фактури. Води евиденција за пристапот за да се спречи неовластена измена.' },
          { step: 'Политика за задржување', desc: 'Дефинирајте јасно правило дека фактурите се чуваат минимум 10 години и автоматизирајте го задржувањето наместо рачно да управувате со датотеки.' },
        ],
      },
      {
        title: 'Ризици при неусогласено чување',
        content:
          'Несоодветното архивирање на е-фактури не е само технички пропуст — може да доведе до сериозни последици при даночна контрола:',
        items: [
          'Изгубени оригинали — ако се чува само PDF наместо потпишаниот XML, оригиналот повеќе не постои и не може да се докаже автентичноста',
          'Нечитливи датотеки — стари формати или оштетени медиуми може да ги направат фактурите неупотребливи пред истекот на 10-те години',
          'Неуспешна даночна контрола — ако УЈП побара фактура во оригинален формат, а вие не можете да ја доставите, документацијата се смета за некомплетна',
          'Казни — некомплетна или неправилно чувана даночна документација носи глоба EUR 500–3.000 за правно лице и EUR 100–500 за одговорно лице',
          'Оспорен одбиток на ДДВ — фактури без валиден оригинал може да бидат отфрлени како основа за одбиток на претходен ДДВ',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ги чува и обезбедува е-фактурите',
        content:
          'Facturino нуди безбедно електронско чување на вашите фактури и документи директно во платформата, така што не морате рачно да управувате со датотеки и резервни копии. Тоа што го добивате:',
        items: [
          'Безбедно чување на фактурите и придружните документи во рамки на платформата, достапни во секое време',
          'Редовни резервни копии на податоците, така што фактурите остануваат заштитени од случајна загуба',
          'Едноставно пронаоѓање — пребарувајте и филтрирајте фактури по година, партнер, број или ЕДБ за секунди',
          'Организирана евиденција на издадени и примени фактури на едно место, подготвена за даночна контрола',
          'Оригиналната UBL 2.1 XML содржина и податоците за е-фактурата се задржуваат заедно со записот за фактурата',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'ubl-format', title: 'UBL 2.1 формат' },
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'casti-prasanja', title: 'Често поставувани прашања' },
    ],
    bottomCta: {
      title: 'Безбедно чување на вашите фактури',
      subtitle: 'Facturino ги чува вашите фактури и документи безбедно во платформата — со резервни копии и лесно пронаоѓање.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'Archiving',
    title: 'Storage & E-Archiving of E-Invoices: The 10-Year Rule',
    publishDate: 'August 18, 2026',
    readTime: '8 min read',
    intro:
      'Issuing an e-invoice is only the first step — the law requires it to be stored correctly for many years. E-invoices must be archived for a minimum of 10 years in their original UBL 2.1 XML format, with the Qualified Electronic Signature (QES) preserved. A printed PDF is not enough and does not satisfy the obligation. This guide explains what legally compliant e-archiving means, the requirements for integrity and readability, best practices, and how Facturino securely stores your invoices and documents within the platform.',
    sections: [
      {
        title: 'The legal archiving obligation',
        content:
          'Under tax regulations, e-invoices must be kept for a minimum of 10 years, counted from the end of the year in which they were issued. Crucially, the invoice must remain in its original electronic format — the same structured UBL 2.1 XML in which it was issued and signed. It is not permitted to replace the original with a scanned copy or a printed version. The obligation applies equally to issued (outbound) and received (inbound) e-invoices, since both are part of the tax records that the UJP may request during an inspection.',
        items: null,
        steps: null,
      },
      {
        title: 'What "original format" means',
        content:
          'The original format of an e-invoice is not the PDF you see on screen, but the UBL 2.1 XML document itself together with its QES signature. The signature is mathematically bound to the content of the XML and serves as proof that the invoice has not been altered after issuance. If you keep only a printed or PDF "preview", you lose exactly what makes the document legally valid:',
        items: [
          'The UBL 2.1 XML file is the true original — it holds structured, machine-readable data',
          'The QES signature must be preserved together with the XML — without it, authenticity cannot be proven',
          'A PDF or printout is only a visual rendering, not a legally valid original',
          'Any change to the XML invalidates the signature, so the original must remain untouched',
        ],
        steps: null,
      },
      {
        title: 'E-archive requirements',
        content:
          'For archiving to be compliant, storage must guarantee that invoices stay trustworthy and accessible throughout the entire 10-year period. The core requirements are:',
        items: [
          'Integrity — the invoice content must not change, and any alteration must be detectable (the QES signature helps here)',
          'Authenticity — it must be unambiguous who issued the invoice and to whom it is addressed',
          'Readability — invoices must remain readable and processable for the full 10 years, regardless of software changes',
          'Retrievability — during a tax inspection, invoices must be quickly found and delivered to the UJP in their original electronic format',
          'Durable access — the storage format and medium must remain accessible even after system upgrades',
        ],
        steps: null,
      },
      {
        title: 'Best practices for e-archiving',
        content:
          'Compliant e-archiving is more of an organizational than a technical challenge. The following steps help ensure your e-invoices stay safe and accessible throughout the entire legal retention period:',
        items: null,
        steps: [
          { step: 'Keep the original XML with its signature', desc: 'For every invoice, archive the UBL 2.1 XML file together with its QES signature — not just a PDF preview. This is the legally valid original.' },
          { step: 'Maintain secure backups', desc: 'Keep at least one additional copy in a separate location. A single copy on one disk is a risk — a failure or deletion means permanent loss.' },
          { step: 'Indexed, searchable storage', desc: 'Organize invoices by year, number, partner, and tax number (EDB) so each one can be found quickly — especially when the UJP requests a specific invoice.' },
          { step: 'Access control', desc: 'Limit who can view, change, or delete archived invoices. Keep an access log to prevent unauthorized alteration.' },
          { step: 'Retention policy', desc: 'Define a clear rule that invoices are kept for a minimum of 10 years and automate retention rather than managing files by hand.' },
        ],
      },
      {
        title: 'Risks of non-compliant storage',
        content:
          'Improper archiving of e-invoices is not just a technical oversight — it can lead to serious consequences during a tax inspection:',
        items: [
          'Lost originals — if only a PDF is kept instead of the signed XML, the original no longer exists and authenticity cannot be proven',
          'Unreadable files — old formats or damaged media can render invoices unusable before the 10 years elapse',
          'Failed tax inspection — if the UJP requests an invoice in its original format and you cannot provide it, the records are deemed incomplete',
          'Penalties — incomplete or improperly stored tax records carry a fine of EUR 500–3,000 for a legal entity and EUR 100–500 for the responsible person',
          'Disputed VAT deduction — invoices without a valid original may be rejected as a basis for input VAT deduction',
        ],
        steps: null,
      },
      {
        title: 'How Facturino stores and protects your e-invoices',
        content:
          'Facturino provides secure electronic storage of your invoices and documents directly within the platform, so you do not have to manage files and backups by hand. What you get:',
        items: [
          'Secure storage of invoices and supporting documents within the platform, available at any time',
          'Regular data backups, so invoices stay protected from accidental loss',
          'Easy retrieval — search and filter invoices by year, partner, number, or tax number in seconds',
          'Organized records of issued and received invoices in one place, ready for a tax inspection',
          'The original UBL 2.1 XML content and e-invoice data are retained together with the invoice record',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoice' },
      { slug: 'ubl-format', title: 'UBL 2.1 Format' },
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'casti-prasanja', title: 'Frequently Asked Questions' },
    ],
    bottomCta: {
      title: 'Store your invoices securely',
      subtitle: 'Facturino keeps your invoices and documents safe within the platform — with backups and easy retrieval.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Arkivimi',
    title: 'Ruajtja dhe e-arkivimi i e-faturave: detyrimi 10-vjeçar',
    publishDate: '18 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Lëshimi i një e-fature është vetëm hapi i parë — ligji kërkon që ajo të ruhet saktë për shumë vite. E-faturat duhet të arkivohen minimum 10 vjet në formatin e tyre origjinal UBL 2.1 XML, me Nënshkrimin Elektronik të Kualifikuar (QES) të ruajtur. Një PDF i printuar nuk mjafton dhe nuk e përmbush detyrimin. Ky udhëzues shpjegon çfarë do të thotë e-arkivim ligjërisht i pajtueshëm, kërkesat për integritet dhe lexueshmëri, praktikat më të mira dhe si Facturino ruan në mënyrë të sigurt faturat dhe dokumentet tuaja brenda platformës.',
    sections: [
      {
        title: 'Detyrimi ligjor për arkivim',
        content:
          'Sipas rregulloreve tatimore, e-faturat duhet të ruhen minimum 10 vjet, të llogaritura nga fundi i vitit në të cilin janë lëshuar. Ajo që është thelbësore është se fatura duhet të mbetet në formatin e saj origjinal elektronik — i njëjti UBL 2.1 XML i strukturuar në të cilin është lëshuar dhe nënshkruar. Nuk lejohet që origjinali të zëvendësohet me një kopje të skanuar ose një version të printuar. Detyrimi vlen njësoj për e-faturat e lëshuara (dalëse) dhe të pranuara (hyrëse), pasi të dyja janë pjesë e dokumentacionit tatimor që DAP mund ta kërkojë gjatë një kontrolli.',
        items: null,
        steps: null,
      },
      {
        title: 'Çfarë do të thotë „format origjinal”',
        content:
          'Formati origjinal i një e-fature nuk është PDF-ja që shihni në ekran, por vetë dokumenti UBL 2.1 XML së bashku me nënshkrimin e tij QES. Nënshkrimi është i lidhur matematikisht me përmbajtjen e XML-së dhe shërben si provë se fatura nuk është ndryshuar pas lëshimit. Nëse ruani vetëm një „pamje” të printuar ose PDF, humbni pikërisht atë që e bën dokumentin ligjërisht të vlefshëm:',
        items: [
          'Skedari UBL 2.1 XML është origjinali i vërtetë — ai përmban të dhëna të strukturuara, të lexueshme nga makina',
          'Nënshkrimi QES duhet të ruhet së bashku me XML-në — pa të, autenticiteti nuk mund të provohet',
          'Një PDF ose printim është vetëm një paraqitje vizuale, jo një origjinal ligjërisht i vlefshëm',
          'Çdo ndryshim në XML e zhvlerëson nënshkrimin, prandaj origjinali duhet të mbetet i paprekur',
        ],
        steps: null,
      },
      {
        title: 'Kërkesat për e-arkivë',
        content:
          'Që arkivimi të jetë i pajtueshëm, ruajtja duhet të garantojë që faturat mbeten të besueshme dhe të aksesueshme gjatë gjithë periudhës 10-vjeçare. Kërkesat themelore janë:',
        items: [
          'Integritet — përmbajtja e faturës nuk duhet të ndryshojë, dhe çdo ndryshim duhet të jetë i zbulueshëm (këtu ndihmon nënshkrimi QES)',
          'Autenticitet — duhet të jetë e qartë se kush e ka lëshuar faturën dhe kujt i drejtohet',
          'Lexueshmëri — faturat duhet të mbeten të lexueshme dhe të përpunueshme për të gjitha 10 vitet, pavarësisht ndryshimeve në softuer',
          'Gjetshmëri — gjatë një kontrolli tatimor, faturat duhet të gjenden shpejt dhe të dorëzohen te DAP në formatin e tyre origjinal elektronik',
          'Akses i qëndrueshëm — formati dhe media e ruajtjes duhet të mbeten të aksesueshme edhe pas përditësimeve të sistemit',
        ],
        steps: null,
      },
      {
        title: 'Praktikat më të mira për e-arkivim',
        content:
          'E-arkivimi i pajtueshëm është më shumë një sfidë organizative sesa teknike. Hapat e mëposhtëm ndihmojnë të sigurohet që e-faturat tuaja të mbeten të sigurta dhe të aksesueshme gjatë gjithë periudhës ligjore të ruajtjes:',
        items: null,
        steps: [
          { step: 'Ruajeni XML-në origjinale me nënshkrimin e saj', desc: 'Për çdo faturë, arkivoni skedarin UBL 2.1 XML së bashku me nënshkrimin e tij QES — jo vetëm një pamje PDF. Ky është origjinali ligjërisht i vlefshëm.' },
          { step: 'Mbani kopje rezervë të sigurta (backup)', desc: 'Mbani të paktën një kopje shtesë në një vendndodhje të veçantë. Një kopje e vetme në një disk është rrezik — një defekt ose fshirje do të thotë humbje e përhershme.' },
          { step: 'Ruajtje e indeksuar dhe e kërkueshme', desc: 'Organizoni faturat sipas vitit, numrit, partnerit dhe numrit tatimor (EDB) që secila të gjendet shpejt — sidomos kur DAP kërkon një faturë specifike.' },
          { step: 'Kontroll i aksesit', desc: 'Kufizoni kush mund të shikojë, ndryshojë ose fshijë faturat e arkivuara. Mbani një regjistër aksesi për të parandaluar ndryshime të paautorizuara.' },
          { step: 'Politikë e ruajtjes', desc: 'Përcaktoni një rregull të qartë që faturat ruhen minimum 10 vjet dhe automatizoni ruajtjen në vend që t\'i menaxhoni skedarët me dorë.' },
        ],
      },
      {
        title: 'Rreziqet e ruajtjes jopajtueshme',
        content:
          'Arkivimi jo i duhur i e-faturave nuk është vetëm një pakujdesi teknike — mund të çojë në pasoja serioze gjatë një kontrolli tatimor:',
        items: [
          'Origjinale të humbura — nëse ruhet vetëm një PDF në vend të XML-së së nënshkruar, origjinali nuk ekziston më dhe autenticiteti nuk mund të provohet',
          'Skedarë të palexueshëm — formatet e vjetra ose media e dëmtuar mund t\'i bëjnë faturat të papërdorshme para se të kalojnë 10 vitet',
          'Kontroll tatimor i dështuar — nëse DAP kërkon një faturë në formatin origjinal dhe ju nuk mund ta jepni, dokumentacioni konsiderohet i paplotë',
          'Gjoba — dokumentacioni tatimor i paplotë ose i ruajtur gabimisht bart një gjobë EUR 500–3.000 për personin juridik dhe EUR 100–500 për personin përgjegjës',
          'Zbritje e kontestuar e TVSH-së — faturat pa një origjinal të vlefshëm mund të refuzohen si bazë për zbritjen e TVSH-së së parapaguar',
        ],
        steps: null,
      },
      {
        title: 'Si Facturino ruan dhe mbron e-faturat tuaja',
        content:
          'Facturino ofron ruajtje elektronike të sigurt të faturave dhe dokumenteve tuaja drejtpërdrejt brenda platformës, që të mos u duhet t\'i menaxhoni skedarët dhe kopjet rezervë me dorë. Çfarë përfitoni:',
        items: [
          'Ruajtje e sigurt e faturave dhe dokumenteve shoqëruese brenda platformës, të disponueshme në çdo kohë',
          'Kopje rezervë të rregullta të të dhënave, kështu që faturat mbeten të mbrojtura nga humbja aksidentale',
          'Gjetje e lehtë — kërkoni dhe filtroni faturat sipas vitit, partnerit, numrit ose numrit tatimor në sekonda',
          'Regjistra të organizuar të faturave të lëshuara dhe të pranuara në një vend, gati për një kontroll tatimor',
          'Përmbajtja origjinale UBL 2.1 XML dhe të dhënat e e-faturës ruhen së bashku me regjistrimin e faturës',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'ubl-format', title: 'Formati UBL 2.1' },
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'casti-prasanja', title: 'Pyetjet më të shpeshta' },
    ],
    bottomCta: {
      title: 'Ruani faturat tuaja në mënyrë të sigurt',
      subtitle: 'Facturino i mban faturat dhe dokumentet tuaja të sigurta brenda platformës — me kopje rezervë dhe gjetje të lehtë.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'Arşivleme',
    title: 'E-Faturaların Saklanması ve E-Arşivi: 10 Yıl Kuralı',
    publishDate: '18 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Bir e-fatura düzenlemek yalnızca ilk adımdır — kanun, onun uzun yıllar boyunca doğru şekilde saklanmasını gerektirir. E-faturalar, Nitelikli Elektronik İmza (QES) korunarak orijinal UBL 2.1 XML formatında en az 10 yıl arşivlenmelidir. Yazdırılmış bir PDF yeterli değildir ve yükümlülüğü karşılamaz. Bu rehber, yasal olarak uyumlu e-arşivlemenin ne anlama geldiğini, bütünlük ve okunabilirlik gereksinimlerini, en iyi uygulamaları ve Facturino\'nun faturalarınızı ve belgelerinizi platform içinde nasıl güvenle sakladığını açıklar.',
    sections: [
      {
        title: 'Yasal arşivleme yükümlülüğü',
        content:
          'Vergi mevzuatına göre e-faturalar, düzenlendikleri yılın sonundan itibaren hesaplanmak üzere en az 10 yıl saklanmalıdır. Önemli olan, faturanın orijinal elektronik formatında kalması gerektiğidir — düzenlendiği ve imzalandığı aynı yapılandırılmış UBL 2.1 XML. Orijinalin taranmış bir kopya veya yazdırılmış bir sürümle değiştirilmesine izin verilmez. Yükümlülük, hem düzenlenen (giden) hem de alınan (gelen) e-faturalar için eşit olarak geçerlidir, çünkü ikisi de UJP\'nin bir denetim sırasında talep edebileceği vergi kayıtlarının parçasıdır.',
        items: null,
        steps: null,
      },
      {
        title: '„Orijinal format” ne demektir',
        content:
          'Bir e-faturanın orijinal formatı, ekranda gördüğünüz PDF değil, QES imzasıyla birlikte UBL 2.1 XML belgesinin kendisidir. İmza, XML\'in içeriğine matematiksel olarak bağlıdır ve faturanın düzenlendikten sonra değiştirilmediğinin kanıtı olarak hizmet eder. Yalnızca yazdırılmış veya PDF bir „önizleme” saklarsanız, belgeyi yasal olarak geçerli kılan şeyi tam olarak kaybedersiniz:',
        items: [
          'UBL 2.1 XML dosyası gerçek orijinaldir — yapılandırılmış, makine tarafından okunabilir veriler içerir',
          'QES imzası XML ile birlikte korunmalıdır — onsuz özgünlük kanıtlanamaz',
          'Bir PDF veya çıktı yalnızca görsel bir sunumdur, yasal olarak geçerli bir orijinal değildir',
          'XML\'deki herhangi bir değişiklik imzayı geçersiz kılar, bu nedenle orijinal el değmemiş kalmalıdır',
        ],
        steps: null,
      },
      {
        title: 'E-arşiv gereksinimleri',
        content:
          'Arşivlemenin uyumlu olması için saklama, faturaların 10 yıllık dönem boyunca güvenilir ve erişilebilir kalmasını garanti etmelidir. Temel gereksinimler şunlardır:',
        items: [
          'Bütünlük — fatura içeriği değişmemeli ve herhangi bir değişiklik tespit edilebilir olmalıdır (QES imzası burada yardımcı olur)',
          'Özgünlük — faturayı kimin düzenlediği ve kime hitap ettiği açık olmalıdır',
          'Okunabilirlik — faturalar, yazılım değişikliklerinden bağımsız olarak 10 yıl boyunca okunabilir ve işlenebilir kalmalıdır',
          'Bulunabilirlik — bir vergi denetimi sırasında faturalar hızlıca bulunmalı ve orijinal elektronik formatında UJP\'ye teslim edilmelidir',
          'Kalıcı erişim — saklama formatı ve ortamı, sistem yükseltmelerinden sonra bile erişilebilir kalmalıdır',
        ],
        steps: null,
      },
      {
        title: 'E-arşivleme için en iyi uygulamalar',
        content:
          'Uyumlu e-arşivleme, teknik olmaktan çok organizasyonel bir zorluktur. Aşağıdaki adımlar, e-faturalarınızın tüm yasal saklama süresi boyunca güvende ve erişilebilir kalmasını sağlamaya yardımcı olur:',
        items: null,
        steps: [
          { step: 'Orijinal XML\'i imzasıyla birlikte saklayın', desc: 'Her fatura için UBL 2.1 XML dosyasını QES imzasıyla birlikte arşivleyin — yalnızca bir PDF önizleme değil. Bu, yasal olarak geçerli orijinaldir.' },
          { step: 'Güvenli yedekler bulundurun', desc: 'Ayrı bir konumda en az bir ek kopya bulundurun. Tek bir diskteki tek bir kopya risktir — bir arıza veya silme kalıcı kayıp anlamına gelir.' },
          { step: 'İndekslenmiş, aranabilir saklama', desc: 'Faturaları yıl, numara, iş ortağı ve vergi numarasına (EDB) göre düzenleyin, böylece her biri hızlıca bulunabilir — özellikle UJP belirli bir faturayı talep ettiğinde.' },
          { step: 'Erişim kontrolü', desc: 'Arşivlenmiş faturaları kimin görüntüleyebileceğini, değiştirebileceğini veya silebileceğini sınırlayın. Yetkisiz değişiklikleri önlemek için bir erişim günlüğü tutun.' },
          { step: 'Saklama politikası', desc: 'Faturaların en az 10 yıl saklandığına dair açık bir kural tanımlayın ve dosyaları elle yönetmek yerine saklamayı otomatikleştirin.' },
        ],
      },
      {
        title: 'Uyumsuz saklamanın riskleri',
        content:
          'E-faturaların yanlış arşivlenmesi yalnızca teknik bir gözden kaçırma değildir — bir vergi denetimi sırasında ciddi sonuçlara yol açabilir:',
        items: [
          'Kaybolan orijinaller — imzalı XML yerine yalnızca bir PDF saklanırsa, orijinal artık mevcut değildir ve özgünlük kanıtlanamaz',
          'Okunamayan dosyalar — eski formatlar veya hasarlı ortamlar, 10 yıl geçmeden faturaları kullanılamaz hale getirebilir',
          'Başarısız vergi denetimi — UJP bir faturayı orijinal formatında talep eder ve siz sağlayamazsanız, kayıtlar eksik sayılır',
          'Cezalar — eksik veya yanlış saklanan vergi kayıtları, tüzel kişi için 500–3.000 EUR ve sorumlu kişi için 100–500 EUR para cezası taşır',
          'İtiraz edilen KDV indirimi — geçerli bir orijinali olmayan faturalar, indirilecek KDV\'ye esas olarak reddedilebilir',
        ],
        steps: null,
      },
      {
        title: 'Facturino e-faturalarınızı nasıl saklar ve korur',
        content:
          'Facturino, faturalarınızın ve belgelerinizin güvenli elektronik saklanmasını doğrudan platform içinde sağlar, böylece dosyaları ve yedekleri elle yönetmeniz gerekmez. Elde ettikleriniz:',
        items: [
          'Faturaların ve destekleyici belgelerin platform içinde güvenli saklanması, her zaman erişilebilir',
          'Düzenli veri yedeklemeleri, böylece faturalar kazara kayıptan korunur',
          'Kolay erişim — faturaları yıl, iş ortağı, numara veya vergi numarasına göre saniyeler içinde arayın ve filtreleyin',
          'Düzenlenen ve alınan faturaların tek bir yerde düzenli kayıtları, vergi denetimine hazır',
          'Orijinal UBL 2.1 XML içeriği ve e-fatura verileri, fatura kaydıyla birlikte saklanır',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-Fatura için Kapsamlı Rehber' },
      { slug: 'ubl-format', title: 'UBL 2.1 Formatı' },
      { slug: 'kako-da-izdadete', title: 'E-Fatura Nasıl Düzenlenir' },
      { slug: 'casti-prasanja', title: 'Sıkça Sorulan Sorular' },
    ],
    bottomCta: {
      title: 'Faturalarınızı güvenle saklayın',
      subtitle: 'Facturino faturalarınızı ve belgelerinizi platform içinde güvende tutar — yedeklemeler ve kolay erişim ile.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaCasuvanjeIArhivaPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const efLabel = locale === 'mk' ? 'Е-фактура' : locale === 'sq' ? 'E-fatura' : locale === 'tr' ? 'E-Fatura' : 'E-Invoice'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const articleLd = articleJsonLd({
    locale,
    pathPrefix: 'e-faktura',
    slug: 'casuvanje-i-arhiva',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'архива', 'e-archive', 'UBL', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/casuvanje-i-arhiva` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Колку долго мора да се чуваат е-фактурите?', answer: 'Е-фактурите мора да се чуваат минимум 10 години сметано од крајот на годината во која се издадени, во нивниот оригинален UBL 2.1 XML формат.' },
        { question: 'Дали е доволно да чувам PDF од е-фактурата?', answer: 'Не. Оригиналот е UBL 2.1 XML документот заедно со неговиот QES потпис. PDF или испечатена копија е само визуелен приказ и не е правно валиден оригинал.' },
        { question: 'Што значи чување во оригинален формат?', answer: 'Значи дека се чува самата UBL 2.1 XML датотека со зачуван квалификуван електронски потпис (QES), а не скенирана или испечатена верзија.' },
        { question: 'Кои се ризиците ако не ги чувам правилно е-фактурите?', answer: 'Изгубени оригинали, нечитливи датотеки, неуспешна даночна контрола, оспорен одбиток на ДДВ и глоба EUR 500–3.000 за правно лице и EUR 100–500 за одговорно лице.' },
        { question: 'Како Facturino помага со чувањето?', answer: 'Facturino нуди безбедно електронско чување на фактурите и документите во платформата, со редовни резервни копии и лесно пронаоѓање по година, партнер, број или ЕДБ.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/e-faktura`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={`/${locale}/e-faktura/${ra.slug}`}
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
