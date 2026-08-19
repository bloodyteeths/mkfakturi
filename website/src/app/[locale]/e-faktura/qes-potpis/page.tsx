import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/e-faktura/qes-potpis', {
    title: {
      mk: 'QES: квалификуван електронски потпис за е-фактура',
      en: 'QES: Qualified Electronic Signature for E-Invoices',
      sq: 'QES: Nënshkrimi Elektronik i Kualifikuar për e-fatura',
      tr: 'QES: E-Faturalar İçin Nitelikli Elektronik İmza',
    },
    description: {
      mk: 'Што е QES (квалификуван електронски потпис), зошто е задолжителен за секоја е-фактура, овластени издавачи во Македонија (Кибритон, КИБС), цена и важност, USB токен наспроти cloud потпис и како да набавите QES.',
      en: 'What QES (Qualified Electronic Signature) is, why it is mandatory for every e-invoice, authorized issuers in Macedonia (Kibriton, KIBS), cost and validity, USB token vs cloud signature, and how to obtain a QES.',
      sq: 'Çfarë është QES (Nënshkrimi Elektronik i Kualifikuar), pse është i detyrueshëm për çdo e-faturë, lëshuesit e autorizuar në Maqedoni (Kibriton, KIBS), kostoja dhe vlefshmëria, token USB kundrejt nënshkrimit cloud dhe si të merrni QES.',
      tr: 'QES (Nitelikli Elektronik İmza) nedir, neden her e-fatura için zorunludur, Makedonya\'daki yetkili sağlayıcılar (Kibriton, KIBS), maliyet ve geçerlilik, USB token ile cloud imza karşılaştırması ve QES nasıl alınır.',
    },
    datePublished: '2026-08-18',
  })
}

const copy = {
  mk: {
    backLink: '← Е-фактура',
    tag: 'QES потпис',
    title: 'QES: квалификуван електронски потпис за е-фактура',
    publishDate: '18 август 2026',
    readTime: '8 мин читање',
    intro:
      'Секоја е-фактура во Македонија мора да биде потпишана со квалификуван електронски потпис (QES) за да биде правно валидна. QES е највисокото ниво на електронски потпис — правно еквивалентен на своерачен потпис. Овој водич објаснува што е QES, зошто е задолжителен, кои се овластените издавачи (Кибритон, КИБС), колку чини, разликата помеѓу USB токен и cloud потпис, и како чекор-по-чекор да набавите свој сертификат.',
    sections: [
      {
        title: 'Што е QES?',
        content:
          'QES (Qualified Electronic Signature — квалификуван електронски потпис) е највисокото правно ниво на електронски потпис. За разлика од обичниот или напредниот електронски потпис, QES се создава со квалификуван сертификат издаден од овластен доверлив провајдер и е правно еквивалентен на своерачен потпис. Тоа значи дека документ потпишан со QES има иста правна тежина како хартиен документ потпишан со рака — без потреба од дополнителна нотарска заверка. QES гарантира три работи: идентитетот на потписникот (автентичност), дека содржината не е менувана по потпишувањето (интегритет) и дека потписникот не може да го негира потписот (непобитност).',
        items: null,
        steps: null,
      },
      {
        title: 'Зошто е задолжителен за е-фактура?',
        content:
          'Е-фактурата е структуриран UBL 2.1 XML документ, а не PDF или скенирана слика. За да биде правно валидна, секоја е-фактура мора да биде потпишана со QES пред да се поднесе преку платформата на УЈП (efaktura.ujp.gov.mk). Без QES потпис, XML документот не се смета за важечка е-фактура и УЈП ќе го одбие. Причините се:',
        items: [
          'Правна валидност — QES ѝ дава на е-фактурата иста тежина како хартиена фактура потпишана со рака',
          'Интегритет — потписот гарантира дека содржината (износи, ДДВ, ставки) не е менувана по издавањето',
          'Автентичност — потврдува дека фактурата навистина потекнува од регистрираниот издавач',
          'Непобитност — издавачот не може подоцна да го негира дека ја издал фактурата',
          'Усогласеност со УЈП — платформата ги отфрла сите е-фактури без валиден QES потпис',
        ],
        steps: null,
      },
      {
        title: 'Овластени издавачи во Македонија',
        content:
          'QES сертификат може да набавите само од овластен доверлив провајдер (quaified trust service provider) регистриран во Македонија. Двата главни издавачи се:',
        items: [
          'Кибритон (kibriton.mk) — овластен издавач на квалификувани сертификати за електронски потпис, нуди USB токени и cloud решенија за физички и правни лица',
          'КИБС (KIBS — Клириншки интербанкарски систем) — овластен провајдер кој издава квалификувани сертификати, вклучително и за компании и вработени',
          'Секој сертификат е врзан за конкретно физичко лице (со ЕМБГ) кое потпишува во име на компанијата',
          'За компаниска употреба, сертификатот вообичаено се издава на овластено лице (управител или сметководител) со наведен ЕДБ на фирмата',
        ],
        steps: null,
      },
      {
        title: 'Цена и важност',
        content:
          'Цената на QES сертификат зависи од издавачот и формата (USB токен или cloud), но се движи во разумни граници за годишна претплата:',
        items: [
          'Цена: приближно 2.000-5.000 МКД годишно, во зависност од провајдерот и типот на сертификат',
          'Важност: сертификатите вообичаено важат 1-2 години, по што мора да се обноват',
          'Обнова: пред истекот, контактирајте го издавачот за обнова — истечен сертификат значи дека не можете да потпишувате е-фактури',
          'USB токенот е еднократен трошок за хардверот плус годишна претплата за сертификатот',
          'Cloud потписот вообичаено е претплатнички модел без физички хардвер',
        ],
        steps: null,
      },
      {
        title: 'USB токен наспроти cloud потпис',
        content:
          'QES доаѓа во две форми — на физички USB токен или како cloud-based решение. Секоја има свои предности и недостатоци:',
        items: [
          'USB токен — предности: приватниот клуч никогаш не го напушта хардверот, што дава максимална безбедност; функционира офлајн; целосна контрола врз токенот',
          'USB токен — недостатоци: не може автоматски да потпишува на сервер (клучот е неекспортабилен), треба физичко приклучување, може да се изгуби или оштети, потпишува само еден по еден',
          'Cloud потпис — предности: достапен од секаде без хардвер, погоден за автоматско/масовно потпишување и интеграција со софтвер, полесно управување за повеќе корисници',
          'Cloud потпис — недостатоци: зависи од интернет конекција и од безбедноста на провајдерот, месечна/годишна претплата',
          'За автоматско потпишување на е-фактури преку софтвер, cloud или файл-базиран e-seal е попрактичен од USB токен',
        ],
        steps: null,
      },
      {
        title: 'Како да набавите QES',
        content:
          'Набавката на QES сертификат е директен процес што трае неколку дена. Следете ги овие чекори:',
        items: null,
        steps: [
          { step: 'Изберете провајдер', desc: 'Контактирајте Кибритон (kibriton.mk) или КИБС и изберете тип на сертификат — USB токен или cloud решение, во зависност од вашите потреби.' },
          { step: 'Поднесете документи на компанијата', desc: 'Доставете тековна состојба, ЕДБ (даночен број) на фирмата и лична идентификација на овластеното лице кое ќе потпишува.' },
          { step: 'Верификација на идентитет', desc: 'Издавачот мора да го верификува идентитетот на физичкото лице — вообичаено со лично присуство или преку валидиран процес на идентификација.' },
          { step: 'Примете токен или cloud податоци', desc: 'По одобрувањето, добивате USB токен со сертификатот или пристапни податоци за cloud потпис.' },
          { step: 'Инсталирајте и конфигурирајте', desc: 'Инсталирајте ги потребните драјвери и софтвер за токенот, или конфигурирајте го cloud пристапот во вашата апликација за фактурирање.' },
          { step: 'Тестирајте потпишување', desc: 'Потпишете пробна е-фактура и поднесете ја во sandbox околината на УЈП за да потврдите дека потписот е валиден пред продукциска употреба.' },
        ],
      },
      {
        title: 'QES со Facturino',
        content:
          'Facturino го интегрира QES потпишувањето директно во работниот тек за фактурирање. Наместо рачно да извезувате XML, да го потпишувате со надворешна алатка и потоа да го поднесувате, Facturino нативно генерира UBL 2.1 XML, го потпишува со вашиот QES сертификат (USB токен или cloud) и го поднесува на УЈП платформата — сè од еден екран. Така, издавањето на правно валидна е-фактура станува исто толку едноставно како и креирање обична фактура, без технички премин помеѓу алатки.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'vodic', title: 'Целосен водич за е-фактура' },
      { slug: 'kako-da-izdadete', title: 'Како да издадете е-фактура' },
      { slug: 'ubl-format', title: 'UBL 2.1 формат' },
      { slug: 'casti-prasanja', title: 'Често поставувани прашања' },
    ],
    bottomCta: {
      title: 'Подготвени за е-фактура?',
      subtitle: 'Facturino потпишува со QES и генерира UBL 2.1 — сè од еден екран.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← E-Invoice',
    tag: 'E-Signature',
    title: 'QES: Qualified Electronic Signature for E-Invoices',
    publishDate: 'August 18, 2026',
    readTime: '8 min read',
    intro:
      'Every e-invoice in North Macedonia must be signed with a Qualified Electronic Signature (QES) to be legally valid. QES is the highest legal tier of electronic signature — legally equivalent to a handwritten signature. This guide explains what QES is, why it is mandatory, who the authorized issuers are (Kibriton, KIBS), how much it costs, the difference between a USB token and a cloud signature, and how to obtain your certificate step by step.',
    sections: [
      {
        title: 'What is QES?',
        content:
          'QES (Qualified Electronic Signature) is the highest legal tier of electronic signature. Unlike a simple or advanced electronic signature, a QES is created with a qualified certificate issued by an authorized trust service provider and is legally equivalent to a handwritten signature. This means a document signed with QES carries the same legal weight as a paper document signed by hand — with no need for additional notarization. QES guarantees three things: the identity of the signer (authenticity), that the content has not been altered after signing (integrity), and that the signer cannot deny the signature (non-repudiation).',
        items: null,
        steps: null,
      },
      {
        title: 'Why is it mandatory for e-invoices?',
        content:
          'An e-invoice is a structured UBL 2.1 XML document, not a PDF or scanned image. To be legally valid, every e-invoice must be signed with QES before submission via the UJP platform (efaktura.ujp.gov.mk). Without a QES signature, the XML document is not considered a valid e-invoice and UJP will reject it. The reasons are:',
        items: [
          'Legal validity — QES gives the e-invoice the same weight as a paper invoice signed by hand',
          'Integrity — the signature guarantees the content (amounts, VAT, line items) has not been altered after issuance',
          'Authenticity — it confirms the invoice genuinely originates from the registered issuer',
          'Non-repudiation — the issuer cannot later deny having issued the invoice',
          'UJP compliance — the platform rejects any e-invoice without a valid QES signature',
        ],
        steps: null,
      },
      {
        title: 'Authorized issuers in Macedonia',
        content:
          'You can obtain a QES certificate only from an authorized qualified trust service provider registered in Macedonia. The two main issuers are:',
        items: [
          'Kibriton (kibriton.mk) — authorized issuer of qualified certificates for electronic signatures, offering USB tokens and cloud solutions for individuals and legal entities',
          'KIBS (Clearing Interbank Systems) — authorized provider issuing qualified certificates, including for companies and employees',
          'Each certificate is tied to a specific individual (with a national ID / EMBG) who signs on behalf of the company',
          'For corporate use, the certificate is usually issued to an authorized person (manager or accountant) with the company\'s EDB stated',
        ],
        steps: null,
      },
      {
        title: 'Cost and validity',
        content:
          'The cost of a QES certificate depends on the issuer and the form (USB token or cloud), but stays within reasonable bounds for an annual subscription:',
        items: [
          'Cost: approximately 2,000-5,000 MKD per year, depending on the provider and certificate type',
          'Validity: certificates typically last 1-2 years, after which they must be renewed',
          'Renewal: before expiry, contact the issuer to renew — an expired certificate means you cannot sign e-invoices',
          'A USB token is a one-time hardware cost plus an annual certificate subscription',
          'A cloud signature is usually a subscription model with no physical hardware',
        ],
        steps: null,
      },
      {
        title: 'USB token vs cloud signature',
        content:
          'QES comes in two forms — on a physical USB token or as a cloud-based solution. Each has its own pros and cons:',
        items: [
          'USB token — pros: the private key never leaves the hardware, providing maximum security; works offline; full control over the token',
          'USB token — cons: cannot sign automatically on a server (the key is non-exportable), requires physical plugging in, can be lost or damaged, signs one at a time',
          'Cloud signature — pros: available from anywhere without hardware, suited for automated/bulk signing and software integration, easier management for multiple users',
          'Cloud signature — cons: depends on internet connection and the provider\'s security, monthly/annual subscription',
          'For automated e-invoice signing via software, a cloud or file-based e-seal is more practical than a USB token',
        ],
        steps: null,
      },
      {
        title: 'How to obtain a QES',
        content:
          'Obtaining a QES certificate is a straightforward process that takes a few days. Follow these steps:',
        items: null,
        steps: [
          { step: 'Choose a provider', desc: 'Contact Kibriton (kibriton.mk) or KIBS and choose a certificate type — USB token or cloud solution, depending on your needs.' },
          { step: 'Submit company documents', desc: 'Provide the current company registration, the company EDB (tax number), and personal identification for the authorized person who will sign.' },
          { step: 'Identity verification', desc: 'The issuer must verify the identity of the individual — usually in person or through a validated identification process.' },
          { step: 'Receive token or cloud credentials', desc: 'After approval, you receive a USB token with the certificate or access credentials for cloud signing.' },
          { step: 'Install and configure', desc: 'Install the required drivers and token software, or configure cloud access in your invoicing application.' },
          { step: 'Test signing', desc: 'Sign a trial e-invoice and submit it to the UJP sandbox environment to confirm the signature is valid before production use.' },
        ],
      },
      {
        title: 'QES with Facturino',
        content:
          'Facturino integrates QES signing directly into the invoicing workflow. Instead of manually exporting XML, signing it with an external tool, and then submitting it, Facturino natively generates UBL 2.1 XML, signs it with your QES certificate (USB token or cloud), and submits it to the UJP platform — all from one screen. Issuing a legally valid e-invoice becomes as simple as creating a regular invoice, with no technical switching between tools.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'vodic', title: 'Complete Guide to E-Invoicing' },
      { slug: 'kako-da-izdadete', title: 'How to Issue an E-Invoice' },
      { slug: 'ubl-format', title: 'UBL 2.1 Format' },
      { slug: 'casti-prasanja', title: 'Frequently Asked Questions' },
    ],
    bottomCta: {
      title: 'Ready for e-invoicing?',
      subtitle: 'Facturino signs with QES and generates UBL 2.1 — all from one screen.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← E-fatura',
    tag: 'Nënshkrimi',
    title: 'QES: Nënshkrimi Elektronik i Kualifikuar për e-fatura',
    publishDate: '18 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Çdo e-faturë në Maqedoni duhet të nënshkruhet me Nënshkrim Elektronik të Kualifikuar (QES) për të qenë ligjërisht e vlefshme. QES është niveli më i lartë ligjor i nënshkrimit elektronik — ligjërisht ekuivalent me një nënshkrim me dorë. Ky udhëzues shpjegon çfarë është QES, pse është i detyrueshëm, kush janë lëshuesit e autorizuar (Kibriton, KIBS), sa kushton, ndryshimin midis një token USB dhe një nënshkrimi cloud, dhe si të merrni certifikatën tuaj hap pas hapi.',
    sections: [
      {
        title: 'Çfarë është QES?',
        content:
          'QES (Nënshkrimi Elektronik i Kualifikuar) është niveli më i lartë ligjor i nënshkrimit elektronik. Ndryshe nga një nënshkrim elektronik i thjeshtë ose i avancuar, QES krijohet me një certifikatë të kualifikuar të lëshuar nga një ofrues i autorizuar shërbimesh besimi dhe është ligjërisht ekuivalent me një nënshkrim me dorë. Kjo do të thotë se një dokument i nënshkruar me QES ka të njëjtën peshë ligjore si një dokument letre i nënshkruar me dorë — pa nevojë për vërtetim shtesë noterial. QES garanton tri gjëra: identitetin e nënshkruesit (autenticitetin), që përmbajtja nuk është ndryshuar pas nënshkrimit (integritetin) dhe që nënshkruesi nuk mund ta mohojë nënshkrimin (mospranueshmërinë).',
        items: null,
        steps: null,
      },
      {
        title: 'Pse është i detyrueshëm për e-faturat?',
        content:
          'E-fatura është një dokument i strukturuar UBL 2.1 XML, jo një PDF ose imazh i skanuar. Për të qenë ligjërisht e vlefshme, çdo e-faturë duhet të nënshkruhet me QES para dorëzimit përmes platformës DAP (efaktura.ujp.gov.mk). Pa një nënshkrim QES, dokumenti XML nuk konsiderohet e-faturë e vlefshme dhe DAP do ta refuzojë. Arsyet janë:',
        items: [
          'Vlefshmëri ligjore — QES i jep e-faturës të njëjtën peshë si një faturë letre e nënshkruar me dorë',
          'Integritet — nënshkrimi garanton se përmbajtja (shumat, TVSH, artikujt) nuk është ndryshuar pas lëshimit',
          'Autenticitet — konfirmon se fatura vjen vërtet nga lëshuesi i regjistruar',
          'Mospranueshmëri — lëshuesi nuk mund ta mohojë më vonë se e ka lëshuar faturën',
          'Pajtueshmëri me DAP — platforma refuzon çdo e-faturë pa një nënshkrim të vlefshëm QES',
        ],
        steps: null,
      },
      {
        title: 'Lëshuesit e autorizuar në Maqedoni',
        content:
          'Certifikatën QES mund ta merrni vetëm nga një ofrues i autorizuar shërbimesh besimi i kualifikuar i regjistruar në Maqedoni. Dy lëshuesit kryesorë janë:',
        items: [
          'Kibriton (kibriton.mk) — lëshues i autorizuar i certifikatave të kualifikuara për nënshkrime elektronike, ofron token USB dhe zgjidhje cloud për individë dhe persona juridikë',
          'KIBS (Sistemet Ndërbankare të Klerimit) — ofrues i autorizuar që lëshon certifikata të kualifikuara, përfshirë për kompani dhe punonjës',
          'Çdo certifikatë është e lidhur me një individ specifik (me numër personal / EMBG) që nënshkruan në emër të kompanisë',
          'Për përdorim korporativ, certifikata zakonisht i lëshohet një personi të autorizuar (menaxher ose kontabilist) me EDB-në e firmës të deklaruar',
        ],
        steps: null,
      },
      {
        title: 'Kostoja dhe vlefshmëria',
        content:
          'Kostoja e një certifikate QES varet nga lëshuesi dhe forma (token USB ose cloud), por qëndron brenda kufijve të arsyeshëm për një abonim vjetor:',
        items: [
          'Kostoja: përafërsisht 2.000-5.000 MKD në vit, në varësi të ofruesit dhe llojit të certifikatës',
          'Vlefshmëria: certifikatat zakonisht zgjasin 1-2 vjet, pas së cilës duhet të rinovohen',
          'Rinovimi: para skadimit, kontaktoni lëshuesin për rinovim — një certifikatë e skaduar do të thotë se nuk mund të nënshkruani e-fatura',
          'Një token USB është një kosto e njëhershme hardueri plus një abonim vjetor për certifikatën',
          'Një nënshkrim cloud zakonisht është një model abonimi pa harduer fizik',
        ],
        steps: null,
      },
      {
        title: 'Token USB kundrejt nënshkrimit cloud',
        content:
          'QES vjen në dy forma — në një token fizik USB ose si zgjidhje e bazuar në cloud. Secila ka avantazhet dhe disavantazhet e veta:',
        items: [
          'Token USB — avantazhet: çelësi privat nuk e lë kurrë harduerin, duke ofruar siguri maksimale; funksionon offline; kontroll të plotë mbi tokenin',
          'Token USB — disavantazhet: nuk mund të nënshkruajë automatikisht në server (çelësi është i paeksportueshëm), kërkon lidhje fizike, mund të humbet ose dëmtohet, nënshkruan një nga një',
          'Nënshkrim cloud — avantazhet: i disponueshëm nga kudo pa harduer, i përshtatshëm për nënshkrim automatik/masiv dhe integrim me softuer, menaxhim më i lehtë për shumë përdorues',
          'Nënshkrim cloud — disavantazhet: varet nga lidhja e internetit dhe siguria e ofruesit, abonim mujor/vjetor',
          'Për nënshkrim automatik të e-faturave përmes softuerit, një e-seal cloud ose i bazuar në skedar është më praktik se një token USB',
        ],
        steps: null,
      },
      {
        title: 'Si të merrni QES',
        content:
          'Marrja e një certifikate QES është një proces i drejtpërdrejtë që zgjat disa ditë. Ndiqni këto hapa:',
        items: null,
        steps: [
          { step: 'Zgjidhni një ofrues', desc: 'Kontaktoni Kibriton (kibriton.mk) ose KIBS dhe zgjidhni një lloj certifikate — token USB ose zgjidhje cloud, në varësi të nevojave tuaja.' },
          { step: 'Dorëzoni dokumentet e kompanisë', desc: 'Sigurojeni regjistrimin aktual të kompanisë, EDB-në (numrin tatimor) të firmës dhe identifikimin personal të personit të autorizuar që do të nënshkruajë.' },
          { step: 'Verifikimi i identitetit', desc: 'Lëshuesi duhet të verifikojë identitetin e individit — zakonisht personalisht ose përmes një procesi të validuar identifikimi.' },
          { step: 'Merrni tokenin ose kredencialet cloud', desc: 'Pas miratimit, merrni një token USB me certifikatën ose kredenciale aksesi për nënshkrim cloud.' },
          { step: 'Instaloni dhe konfiguroni', desc: 'Instaloni drajverët dhe softuerin e nevojshëm të tokenit, ose konfiguroni aksesin cloud në aplikacionin tuaj të faturimit.' },
          { step: 'Testoni nënshkrimin', desc: 'Nënshkruani një e-faturë provë dhe dorëzojeni në mjedisin sandbox të DAP për të konfirmuar se nënshkrimi është i vlefshëm para përdorimit në prodhim.' },
        ],
      },
      {
        title: 'QES me Facturino',
        content:
          'Facturino integron nënshkrimin QES drejtpërdrejt në rrjedhën e punës së faturimit. Në vend që të eksportoni manualisht XML, ta nënshkruani me një mjet të jashtëm dhe pastaj ta dorëzoni, Facturino gjeneron natyrshëm UBL 2.1 XML, e nënshkruan me certifikatën tuaj QES (token USB ose cloud) dhe e dorëzon në platformën DAP — të gjitha nga një ekran. Lëshimi i një e-fature ligjërisht të vlefshme bëhet po aq i thjeshtë sa krijimi i një fature të zakonshme, pa kalim teknik midis mjeteve.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'vodic', title: 'Udhëzues i plotë për e-faturën' },
      { slug: 'kako-da-izdadete', title: 'Si të lëshoni një e-faturë' },
      { slug: 'ubl-format', title: 'Formati UBL 2.1' },
      { slug: 'casti-prasanja', title: 'Pyetje të bëra shpesh' },
    ],
    bottomCta: {
      title: 'Gati për e-faturën?',
      subtitle: 'Facturino nënshkruan me QES dhe gjeneron UBL 2.1 — të gjitha nga një ekran.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← E-Fatura',
    tag: 'İmza',
    title: 'QES: E-Faturalar İçin Nitelikli Elektronik İmza',
    publishDate: '18 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Kuzey Makedonya\'daki her e-fatura, yasal olarak geçerli olması için Nitelikli Elektronik İmza (QES) ile imzalanmalıdır. QES, elektronik imzanın en yüksek yasal seviyesidir — el yazısı imzaya yasal olarak eşdeğerdir. Bu rehber QES\'in ne olduğunu, neden zorunlu olduğunu, yetkili sağlayıcıların kimler olduğunu (Kibriton, KIBS), maliyetini, USB token ile cloud imza arasındaki farkı ve sertifikanızı adım adım nasıl alacağınızı açıklar.',
    sections: [
      {
        title: 'QES nedir?',
        content:
          'QES (Nitelikli Elektronik İmza), elektronik imzanın en yüksek yasal seviyesidir. Basit veya gelişmiş elektronik imzanın aksine, QES yetkili bir güven hizmeti sağlayıcısı tarafından verilen nitelikli bir sertifikayla oluşturulur ve el yazısı imzaya yasal olarak eşdeğerdir. Bu, QES ile imzalanan bir belgenin, elle imzalanmış bir kağıt belgeyle aynı yasal ağırlığa sahip olduğu anlamına gelir — ek noter onayına gerek yoktur. QES üç şeyi garanti eder: imzalayanın kimliğini (özgünlük), içeriğin imzalamadan sonra değiştirilmediğini (bütünlük) ve imzalayanın imzayı inkar edemeyeceğini (inkar edilemezlik).',
        items: null,
        steps: null,
      },
      {
        title: 'E-faturalar için neden zorunludur?',
        content:
          'E-fatura, PDF veya taranmış görüntü değil, yapılandırılmış bir UBL 2.1 XML belgesidir. Yasal olarak geçerli olması için her e-fatura, UJP platformu (efaktura.ujp.gov.mk) üzerinden gönderilmeden önce QES ile imzalanmalıdır. QES imzası olmadan XML belgesi geçerli bir e-fatura sayılmaz ve UJP tarafından reddedilir. Nedenleri:',
        items: [
          'Yasal geçerlilik — QES, e-faturaya elle imzalanmış kağıt faturayla aynı ağırlığı verir',
          'Bütünlük — imza, içeriğin (tutarlar, KDV, kalemler) düzenlendikten sonra değiştirilmediğini garanti eder',
          'Özgünlük — faturanın gerçekten kayıtlı düzenleyiciden geldiğini doğrular',
          'İnkar edilemezlik — düzenleyici daha sonra faturayı düzenlediğini inkar edemez',
          'UJP uyumu — platform, geçerli QES imzası olmayan her e-faturayı reddeder',
        ],
        steps: null,
      },
      {
        title: 'Makedonya\'daki yetkili sağlayıcılar',
        content:
          'QES sertifikasını yalnızca Makedonya\'da kayıtlı yetkili bir nitelikli güven hizmeti sağlayıcısından alabilirsiniz. İki ana sağlayıcı şunlardır:',
        items: [
          'Kibriton (kibriton.mk) — elektronik imzalar için nitelikli sertifikaların yetkili sağlayıcısı, bireyler ve tüzel kişiler için USB token ve cloud çözümleri sunar',
          'KIBS (Bankalararası Takas Sistemleri) — şirketler ve çalışanlar dahil nitelikli sertifikalar veren yetkili sağlayıcı',
          'Her sertifika, şirket adına imzalayan belirli bir bireye (ulusal kimlik / EMBG ile) bağlıdır',
          'Kurumsal kullanım için sertifika genellikle firmanın EDB\'si belirtilerek yetkili bir kişiye (yönetici veya muhasebeci) verilir',
        ],
        steps: null,
      },
      {
        title: 'Maliyet ve geçerlilik',
        content:
          'Bir QES sertifikasının maliyeti sağlayıcıya ve biçime (USB token veya cloud) bağlıdır, ancak yıllık abonelik için makul sınırlar içinde kalır:',
        items: [
          'Maliyet: sağlayıcıya ve sertifika türüne bağlı olarak yılda yaklaşık 2.000-5.000 MKD',
          'Geçerlilik: sertifikalar genellikle 1-2 yıl sürer, ardından yenilenmeleri gerekir',
          'Yenileme: süresi dolmadan önce yenileme için sağlayıcıyla iletişime geçin — süresi dolmuş bir sertifika e-fatura imzalayamayacağınız anlamına gelir',
          'Bir USB token, tek seferlik donanım maliyeti artı yıllık sertifika aboneliğidir',
          'Cloud imza genellikle fiziksel donanım olmadan bir abonelik modelidir',
        ],
        steps: null,
      },
      {
        title: 'USB token ile cloud imza',
        content:
          'QES iki biçimde gelir — fiziksel bir USB token üzerinde veya cloud tabanlı bir çözüm olarak. Her birinin kendi avantajları ve dezavantajları vardır:',
        items: [
          'USB token — avantajları: özel anahtar donanımı asla terk etmez, maksimum güvenlik sağlar; çevrimdışı çalışır; token üzerinde tam kontrol',
          'USB token — dezavantajları: sunucuda otomatik imzalayamaz (anahtar dışa aktarılamaz), fiziksel bağlantı gerektirir, kaybolabilir veya hasar görebilir, tek tek imzalar',
          'Cloud imza — avantajları: donanım olmadan her yerden erişilebilir, otomatik/toplu imzalama ve yazılım entegrasyonu için uygun, birden çok kullanıcı için daha kolay yönetim',
          'Cloud imza — dezavantajları: internet bağlantısına ve sağlayıcının güvenliğine bağlıdır, aylık/yıllık abonelik',
          'Yazılım aracılığıyla otomatik e-fatura imzalamak için cloud veya dosya tabanlı bir e-mühür, USB tokenden daha pratiktir',
        ],
        steps: null,
      },
      {
        title: 'QES nasıl alınır',
        content:
          'Bir QES sertifikası almak, birkaç gün süren basit bir süreçtir. Şu adımları izleyin:',
        items: null,
        steps: [
          { step: 'Bir sağlayıcı seçin', desc: 'Kibriton (kibriton.mk) veya KIBS ile iletişime geçin ve ihtiyaçlarınıza göre bir sertifika türü seçin — USB token veya cloud çözümü.' },
          { step: 'Şirket belgelerini gönderin', desc: 'Güncel şirket kaydını, firmanın EDB\'sini (vergi numarası) ve imzalayacak yetkili kişinin kimlik belgesini sağlayın.' },
          { step: 'Kimlik doğrulama', desc: 'Sağlayıcı, bireyin kimliğini doğrulamalıdır — genellikle şahsen veya doğrulanmış bir kimlik doğrulama süreci aracılığıyla.' },
          { step: 'Token veya cloud kimlik bilgilerini alın', desc: 'Onaydan sonra, sertifikayla birlikte bir USB token veya cloud imzalama için erişim kimlik bilgileri alırsınız.' },
          { step: 'Kurun ve yapılandırın', desc: 'Gerekli sürücüleri ve token yazılımını kurun veya faturalama uygulamanızda cloud erişimini yapılandırın.' },
          { step: 'İmzalamayı test edin', desc: 'Bir deneme e-faturası imzalayın ve üretim kullanımından önce imzanın geçerli olduğunu doğrulamak için UJP sandbox ortamına gönderin.' },
        ],
      },
      {
        title: 'Facturino ile QES',
        content:
          'Facturino, QES imzalamayı doğrudan faturalama iş akışına entegre eder. XML\'i manuel olarak dışa aktarmak, harici bir araçla imzalamak ve ardından göndermek yerine, Facturino UBL 2.1 XML\'i doğal olarak oluşturur, QES sertifikanızla (USB token veya cloud) imzalar ve UJP platformuna gönderir — tümü tek bir ekrandan. Yasal olarak geçerli bir e-fatura düzenlemek, araçlar arasında teknik geçiş yapmadan sıradan bir fatura oluşturmak kadar basit hale gelir.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'vodic', title: 'E-Faturalama İçin Eksiksiz Rehber' },
      { slug: 'kako-da-izdadete', title: 'E-Fatura Nasıl Düzenlenir' },
      { slug: 'ubl-format', title: 'UBL 2.1 Formatı' },
      { slug: 'casti-prasanja', title: 'Sıkça Sorulan Sorular' },
    ],
    bottomCta: {
      title: 'E-faturaya hazır mısınız?',
      subtitle: 'Facturino QES ile imzalar ve UBL 2.1 üretir — tümü tek bir ekrandan.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function QesPotpisPage({
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
    slug: 'qes-potpis',
    title: t.title,
    description: t.intro,
    datePublished: '2026-08-18',
    tags: ['е-фактура', 'e-invoice', 'QES', 'квалификуван електронски потпис', 'Кибритон', 'КИБС', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: efLabel, href: `/${locale}/e-faktura` },
    { name: t.title, href: `/${locale}/e-faktura/qes-potpis` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Што е QES?', answer: 'QES (квалификуван електронски потпис) е највисокото правно ниво на електронски потпис, правно еквивалентен на своерачен потпис и задолжителен за секоја е-фактура.' },
        { question: 'Зошто е QES задолжителен за е-фактура?', answer: 'Без валиден QES потпис, UBL 2.1 XML документот не се смета за важечка е-фактура и платформата на УЈП го одбива. QES гарантира правна валидност, интегритет и автентичност.' },
        { question: 'Од каде да набавам QES во Македонија?', answer: 'QES сертификат може да набавите од овластените издавачи Кибритон (kibriton.mk) или КИБС. Цената е приближно 2.000-5.000 МКД годишно.' },
        { question: 'Која е разликата помеѓу USB токен и cloud потпис?', answer: 'USB токенот го чува приватниот клуч на хардвер и е поесбеден, но не може автоматски да потпишува на сервер. Cloud потписот е погоден за автоматско и масовно потпишување преку софтвер.' },
        { question: 'Колку време важи QES сертификатот?', answer: 'QES сертификатите вообичаено важат 1-2 години, по што мора да се обноват кај издавачот пред истекот за да продолжите да потпишувате е-фактури.' },
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
