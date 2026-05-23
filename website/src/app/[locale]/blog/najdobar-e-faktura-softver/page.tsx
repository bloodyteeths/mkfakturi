import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/najdobar-e-faktura-softver', {
    title: {
      mk: 'Најдобар софтвер за е-Фактура 2026: Подготвени за октомври?',
      en: 'Best E-Invoice Software 2026: Ready for October?',
      sq: 'Softueri me i mire e-Fature 2026: Gati per tetor?',
      tr: '2026 En Iyi e-Fatura Yazilimi: Ekim\'e Hazir misiniz?',
    },
    description: {
      mk: 'Споредба на софтверски решенија за е-Фактура во Македонија 2026: Facturino, PANTHEON, УЈП портал и DIY. Кој е подготвен за B2G мандатот од октомври?',
      en: 'Comparison of e-invoice software for North Macedonia 2026: Facturino, PANTHEON, UJP portal, and DIY. Who is ready for the October B2G mandate?',
      sq: 'Krahasim i zgjidhjeve softuerike per e-Fature ne Maqedoni 2026: Facturino, PANTHEON, portali DAP dhe DIY. Kush eshte gati per detyrimin B2G te tetorit?',
      tr: 'Kuzey Makedonya 2026 e-fatura yazilimi karsilastirmasi: Facturino, PANTHEON, UJP portali ve DIY. Ekim B2G zorunluluguna kim hazir?',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Споредба',
    title: 'Најдобар софтвер за е-Фактура 2026: Подготвени за октомври?',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro: 'Македонија го воведува задолжителното електронско фактурирање за B2G (бизнис-кон-држава) трансакции од октомври 2026. B2B фактурирањето засега е доброволно. Секој бизнис кој работи со државни институции има потреба од усогласен софтвер за е-Фактура. Овој водич ги споредува достапните решенија и објаснува што ви треба.',
    sections: [
      {
        title: 'Што е е-Фактура во Македонија?',
        content: 'Е-фактурата е структуриран дигитален документ во UBL 2.1 XML формат кој го заменува хартиеното фактурирање. Еве ги техничките барања:',
        items: [
          'UBL 2.1 XML формат (OASIS стандард) — меѓународно признат формат за структурирани фактури',
          'Квалификуван електронски потпис (QES) задолжителен за секоја е-фактура',
          'Регистрација преку порталот за е-Фактура на УЈП (efaktura.ujp.gov.mk)',
          'B2G задолжителна од октомври 2026, B2B доброволна (очекувана задолжителност 2027-2028)',
          'XML-от мора да ги содржи сите 15 задолжителни полиња согласно македонскиот закон',
          'Тест околина достапна на УЈП за валидација пред продукција',
        ],
        steps: null,
      },
      {
        title: 'Facturino — вградена е-Фактура',
        content: 'Facturino е единственото македонско решение со полно вградена поддршка за е-Фактура — од генерирање до поднесување. Подготвеност: Да, полно усогласено.',
        items: [
          'UBL 2.1 XML генерирање вградено во софтверот',
          'QES потпишување интегрирано во процесот',
          'Директно поднесување до УЈП портал',
          'Автоматска валидација на задолжителни полиња',
          'Работи за B2G и B2B фактурирање',
          'PDF + XML двоен излез за секоја фактура',
          'Облак базиран — без инсталација на софтвер',
          'Бесплатен план вклучува е-Фактура поддршка',
          'Масовно генерирање на е-фактури (batch)',
        ],
        steps: null,
      },
      {
        title: 'PANTHEON — ERP со модул за е-Фактура',
        content: 'PANTHEON е полн ERP систем кој нуди модул за е-Фактура како дел од пошироката платформа. Подготвеност: Да, со конфигурација.',
        items: [
          'UBL XML експорт достапен',
          'QES потпишување преку надворешен провајдер',
          'Бара десктоп инсталација',
          'Дел од полн ERP пакет — повисока цена',
          'Силен за голем обем на фактурирање',
        ],
        steps: null,
      },
      {
        title: 'УЈП Портал (Рачно) — бесплатна државна опција',
        content: 'УЈП нуди бесплатен портал за рачно поднесување на е-фактури — но без автоматизација. Подготвеност: Да, но само рачно.',
        items: [
          'Бесплатен за користење',
          'Рачно поднесување на XML',
          'Без автоматизација — една фактура во едно време',
          'Без интеграција со сметководствен софтвер',
          'Погоден за многу низок обем (1-5 фактури/месечно)',
          'Достапен на efaktura.ujp.gov.mk',
        ],
        steps: null,
      },
      {
        title: 'DIY (XML Генерирање) — техничка опција',
        content: 'Генерирање на UBL XML со програмски код е опција за компании со развојни тимови. Подготвеност: Зависи од квалитетот на имплементацијата.',
        items: [
          'Генерирање на UBL XML програмски',
          'Бара развојни ресурси (програмери)',
          'QES потпишување одделно — не е вклучено',
          'Без валидација на полиња — висок ризик од одбивање',
          'Товар за одржување при промена на регулатива',
        ],
        steps: null,
      },
      {
        title: 'Споредба на решенија',
        content: null,
        items: null,
        steps: [
          { step: 'UBL 2.1 усогласеност', desc: 'Facturino: Полно вградено. PANTHEON: Да, со конфигурација. УЈП: Рачно. DIY: Зависи од имплементација.' },
          { step: 'QES потпишување', desc: 'Facturino: Интегрирано. PANTHEON: Надворешен провајдер. УЈП: Потребен одделно. DIY: Одделно.' },
          { step: 'Масовна обработка (Batch)', desc: 'Facturino: Да, масовно генерирање. PANTHEON: Да. УЈП: Не — една по една. DIY: Зависи.' },
          { step: 'Сметководствена интеграција', desc: 'Facturino: Полно интегрирано. PANTHEON: Полно интегрирано. УЈП: Нема. DIY: Бара развој.' },
          { step: 'Облак пристап', desc: 'Facturino: Да, полно облак. PANTHEON: Десктоп (има облак верзија). УЈП: Веб портал. DIY: Зависи.' },
          { step: 'Цена', desc: 'Facturino: Бесплатен план вклучен. PANTHEON: Повисока цена (ERP пакет). УЈП: Бесплатно. DIY: Трошоци за развој.' },
          { step: 'Време за поставување', desc: 'Facturino: Минути — регистрација и почнување. PANTHEON: Денови/недели за инсталација. УЈП: Минути. DIY: Недели/месеци.' },
          { step: 'Поддршка', desc: 'Facturino: Локална поддршка на македонски. PANTHEON: Дистрибутерска поддршка. УЈП: Ограничена. DIY: Внатрешна.' },
        ],
      },
      {
        title: 'Како да се подготвите за октомври 2026',
        content: 'Следете ги овие чекори за да бидете подготвени навреме:',
        items: [
          'Регистрирајте се на efaktura.ujp.gov.mk',
          'Набавете QES сертификат од овластен издавач (Кибритон, КИБС)',
          'Тестирајте во sandbox околината на УЈП',
          'Изберете софтвер за е-Фактура',
          'Обучете го персоналот за новиот работен тек',
          'Ажурирајте ги шаблоните за фактури',
          'Известете ги државните клиенти за подготвеноста',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'Е-фактура 2026: Кој мора, кога почнува и како да се подготвите' },
      { slug: 'sto-e-e-faktura', title: 'Што е е-фактура и зошто е задолжителна?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура' },
    ],
    cta: {
      title: 'Подготвени за е-Фактура?',
      desc: 'Facturino поддржува UBL 2.1 и QES потпис — започнете бесплатно и бидете спремни пред сите.',
      button: 'Започнете бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Comparison',
    title: 'Best E-Invoice Software 2026: Ready for October?',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro: 'North Macedonia is mandating e-Invoice for B2G (business-to-government) transactions starting October 2026. B2B is currently voluntary. Every business that works with government institutions needs compliant e-Invoice software. This guide compares what is available and what you need.',
    sections: [
      {
        title: 'What is e-Invoice in North Macedonia?',
        content: 'E-invoicing in North Macedonia follows international standards with specific local requirements. Here are the technical requirements:',
        items: [
          'UBL 2.1 XML format (OASIS standard) — internationally recognized structured invoice format',
          'Qualified Electronic Signature (QES) required for every e-invoice',
          'Registration through UJP e-Invoice portal (efaktura.ujp.gov.mk)',
          'B2G mandatory from October 2026, B2B voluntary (expected mandatory 2027-2028)',
          'XML must contain all 15 mandatory invoice fields per MK law',
          'Testing environment available at UJP for validation before production',
        ],
        steps: null,
      },
      {
        title: 'Facturino — Built-in E-Invoice',
        content: 'Facturino is the only Macedonian solution with fully built-in e-Invoice support — from generation to submission. Readiness: Yes, fully compliant.',
        items: [
          'UBL 2.1 XML generation built into the software',
          'QES signing integrated into the workflow',
          'Direct UJP portal submission',
          'Automatic validation of mandatory fields',
          'Works for both B2G and B2B invoicing',
          'PDF + XML dual output for every invoice',
          'Cloud-based — no software installation needed',
          'Free tier includes e-Invoice support',
          'Batch e-invoice generation for high volume',
        ],
        steps: null,
      },
      {
        title: 'PANTHEON — ERP with E-Invoice Module',
        content: 'PANTHEON is a full ERP system that offers an e-Invoice module as part of its broader platform. Readiness: Yes, with configuration.',
        items: [
          'UBL XML export available',
          'QES signing through external provider',
          'Requires desktop installation',
          'Part of full ERP suite — higher cost',
          'Strong for large volume invoicing',
        ],
        steps: null,
      },
      {
        title: 'UJP Portal (Manual) — Free Government Option',
        content: 'UJP offers a free portal for manual e-invoice submission — but without any automation. Readiness: Yes, but manual only.',
        items: [
          'Free to use',
          'Manual XML upload',
          'No automation — one invoice at a time',
          'No integration with accounting software',
          'Suitable for very low volume (1-5 invoices/month)',
          'Accessible at efaktura.ujp.gov.mk',
        ],
        steps: null,
      },
      {
        title: 'DIY (XML Generation) — Technical Option',
        content: 'Generating UBL XML programmatically is an option for companies with development teams. Readiness: Depends on implementation quality.',
        items: [
          'Generate UBL XML programmatically',
          'Requires developer resources',
          'QES signing handled separately — not included',
          'No field validation — high risk of rejection',
          'Maintenance burden when regulations change',
        ],
        steps: null,
      },
      {
        title: 'Solution Comparison',
        content: null,
        items: null,
        steps: [
          { step: 'UBL 2.1 Compliance', desc: 'Facturino: Fully built-in. PANTHEON: Yes, with configuration. UJP: Manual. DIY: Depends on implementation.' },
          { step: 'QES Signing', desc: 'Facturino: Integrated. PANTHEON: External provider. UJP: Required separately. DIY: Separately.' },
          { step: 'Batch Processing', desc: 'Facturino: Yes, batch generation. PANTHEON: Yes. UJP: No — one at a time. DIY: Depends.' },
          { step: 'Accounting Integration', desc: 'Facturino: Fully integrated. PANTHEON: Fully integrated. UJP: None. DIY: Requires development.' },
          { step: 'Cloud Access', desc: 'Facturino: Yes, fully cloud. PANTHEON: Desktop (cloud version available). UJP: Web portal. DIY: Depends.' },
          { step: 'Cost', desc: 'Facturino: Free tier included. PANTHEON: Higher cost (ERP package). UJP: Free. DIY: Development costs.' },
          { step: 'Setup Time', desc: 'Facturino: Minutes — register and start. PANTHEON: Days/weeks for installation. UJP: Minutes. DIY: Weeks/months.' },
          { step: 'Support', desc: 'Facturino: Local support in Macedonian. PANTHEON: Distributor support. UJP: Limited. DIY: Internal.' },
        ],
      },
      {
        title: 'How to Prepare for October 2026',
        content: 'Follow this checklist to be ready on time:',
        items: [
          'Register at efaktura.ujp.gov.mk',
          'Obtain a QES certificate from an authorized CA (Kibriton, KIBS)',
          'Test with the UJP sandbox environment',
          'Choose your e-Invoice software',
          'Train staff on the new workflow',
          'Update invoice templates',
          'Notify government clients of your readiness',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'E-Invoice Mandate 2026: Who Must Comply & How to Prepare' },
      { slug: 'sto-e-e-faktura', title: 'What Is E-Invoice and Why Is It Mandatory?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements in Macedonia' },
    ],
    cta: {
      title: 'Get E-Invoice Ready',
      desc: 'Facturino supports UBL 2.1 and QES signing — start free and be prepared before the deadline.',
      button: 'Start Free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Krahasim',
    title: 'Softueri me i mire e-Fature 2026: Gati per tetor?',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro: 'Maqedonia e Veriut po e ben te detyrueshme e-faturen per transaksione B2G (biznes-ndaj-qeverise) duke filluar nga tetori 2026. B2B eshte aktualisht vullnetare. Cdo biznes qe punon me institucione shteterore ka nevoje per softuer te perputhshem per e-Fature. Ky udhezes krahason zgjidhjet e disponueshme dhe shpjegon cfare ju nevojitet.',
    sections: [
      {
        title: 'Cfare eshte e-Fatura ne Maqedoni?',
        content: 'E-faturimi ne Maqedoni ndjek standarde nderkombetare me kerkesa specifike lokale. Ja kerkesat teknike:',
        items: [
          'Formati UBL 2.1 XML (standardi OASIS) — format nderkombetar per fatura te strukturuara',
          'Nenskrimi Elektronik i Kualifikuar (QES) i detyrueshem per cdo e-fature',
          'Regjistrimi permes portalit te e-Fatures se DAP (efaktura.ujp.gov.mk)',
          'B2G e detyrueshme nga tetori 2026, B2B vullnetare (pritet te detyrueshme 2027-2028)',
          'XML duhet te permbaje te gjitha 15 fushat e detyrueshme sipas ligjit MK',
          'Mjedis testimi i disponueshem ne DAP per validim para prodhimit',
        ],
        steps: null,
      },
      {
        title: 'Facturino — E-Fature e integruar',
        content: 'Facturino eshte zgjidhja e vetme maqedonase me mbeshtetje te plote te integruar per e-Fature — nga gjenerimi deri ne dorezim. Gatishmeria: Po, plotesisht e perputhshme.',
        items: [
          'Gjenerimi UBL 2.1 XML i integruar ne softuer',
          'Nenskrimi QES i integruar ne rrjedhen e punes',
          'Dorezim direkt ne portalin DAP',
          'Validim automatik i fushave te detyrueshme',
          'Punon per faturim B2G dhe B2B',
          'Dalje dyfishe PDF + XML per cdo fature',
          'Bazuar ne cloud — pa nevoje per instalim softueri',
          'Plani falas perfshin mbeshtetjen e e-Fatures',
          'Gjenerim i e-faturave ne grup (batch) per volum te larte',
        ],
        steps: null,
      },
      {
        title: 'PANTHEON — ERP me modul e-Fature',
        content: 'PANTHEON eshte sistem i plote ERP qe ofron modul per e-Fature si pjese e platformes se gjere. Gatishmeria: Po, me konfigurim.',
        items: [
          'Eksport UBL XML i disponueshem',
          'Nenskrimi QES permes ofresit te jashtem',
          'Kerkon instalim ne desktop',
          'Pjese e paketes se plote ERP — kosto me e larte',
          'I forte per volum te madh faturimi',
        ],
        steps: null,
      },
      {
        title: 'Portali DAP (Manual) — Opsioni qeveritar falas',
        content: 'DAP ofron portal falas per dorezim manual te e-faturave — por pa asnje automatizim. Gatishmeria: Po, por vetem manualisht.',
        items: [
          'Falas per perdorim',
          'Ngarkim manual i XML',
          'Pa automatizim — nje fature ne nje kohe',
          'Pa integrim me softuer kontabiliteti',
          'I pershtatshem per volum shume te ulet (1-5 fatura/muaj)',
          'I aksesueshm ne efaktura.ujp.gov.mk',
        ],
        steps: null,
      },
      {
        title: 'DIY (Gjenerimi XML) — Opsioni teknik',
        content: 'Gjenerimi i UBL XML programatikisht eshte opsion per kompanite me ekipe zhvillimi. Gatishmeria: Varet nga cilesia e implementimit.',
        items: [
          'Gjeneroni UBL XML programatikisht',
          'Kerkon burime zhvilluesish',
          'Nenskrimi QES trajtohet vecmas — nuk eshte i perfshire',
          'Pa validim fushash — rrezik i larte refuzimi',
          'Ngarkese mirembajtjeje kur ndryshojne rregulloret',
        ],
        steps: null,
      },
      {
        title: 'Krahasim i zgjidhjeve',
        content: null,
        items: null,
        steps: [
          { step: 'Perputhshmeri UBL 2.1', desc: 'Facturino: Plotesisht i integruar. PANTHEON: Po, me konfigurim. DAP: Manual. DIY: Varet nga implementimi.' },
          { step: 'Nenskrimi QES', desc: 'Facturino: I integruar. PANTHEON: Ofres i jashtem. DAP: Kerkohet vecmas. DIY: Vecmas.' },
          { step: 'Perpunim ne grup', desc: 'Facturino: Po, gjenerim ne grup. PANTHEON: Po. DAP: Jo — nje nga nje. DIY: Varet.' },
          { step: 'Integrim kontabiliteti', desc: 'Facturino: Plotesisht i integruar. PANTHEON: Plotesisht i integruar. DAP: Asnje. DIY: Kerkon zhvillim.' },
          { step: 'Qasje cloud', desc: 'Facturino: Po, plotesisht cloud. PANTHEON: Desktop (version cloud i disponueshem). DAP: Portal uebi. DIY: Varet.' },
          { step: 'Kostoja', desc: 'Facturino: Plan falas i perfshire. PANTHEON: Kosto me e larte (paket ERP). DAP: Falas. DIY: Kosto zhvillimi.' },
          { step: 'Koha e konfigurimit', desc: 'Facturino: Minuta — regjistrohu dhe fillo. PANTHEON: Dite/jave per instalim. DAP: Minuta. DIY: Jave/muaj.' },
          { step: 'Mbeshtetja', desc: 'Facturino: Mbeshtetje lokale ne maqedonisht. PANTHEON: Mbeshtetje distributori. DAP: E kufizuar. DIY: E brendshme.' },
        ],
      },
      {
        title: 'Si te pergatiteni per tetor 2026',
        content: 'Ndiqni kete liste kontrolli per te qene gati me kohe:',
        items: [
          'Regjistrohuni ne efaktura.ujp.gov.mk',
          'Merrni certifikate QES nga CA e autorizuar (Kibriton, KIBS)',
          'Testoni ne mjedisin sandbox te DAP',
          'Zgjidhni softuerin tuaj per e-Fature',
          'Trajnoni stafin per rrjedhen e re te punes',
          'Perditesoni shabllonet e faturave',
          'Njoftoni klientet qeveritare per gatishmerine tuaj',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te lidhur',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'E-fatura 2026: Kush duhet, kur fillon dhe si te pergatiteni' },
      { slug: 'sto-e-e-faktura', title: 'Cfare eshte e-fatura dhe pse eshte e detyrueshme?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme te fatures' },
    ],
    cta: {
      title: 'Behuni gati per e-Faturen',
      desc: 'Facturino mbeshtet UBL 2.1 dhe nenshkrimin QES — filloni falas dhe pergatituni para afatit.',
      button: 'Filloni falas',
    },
  },
  tr: {
    backLink: '← Blog\'a don',
    tag: 'Karsilastirma',
    title: '2026 En Iyi e-Fatura Yazilimi: Ekim\'e Hazir misiniz?',
    publishDate: '23 Mayis 2026',
    readTime: '10 dk okuma',
    intro: 'Kuzey Makedonya, Ekim 2026\'dan itibaren B2G (isletmeden-devlete) islemleri icin e-Faturayi zorunlu hale getiriyor. B2B su anda gonulludur. Devlet kurumlariyla calisan her isletmenin uyumlu e-fatura yazilimina ihtiyaci var. Bu rehber mevcut cozumleri karsilastiriyor ve neye ihtiyaciniz oldugunu acikliyor.',
    sections: [
      {
        title: 'Kuzey Makedonya\'da e-Fatura nedir?',
        content: 'Kuzey Makedonya\'da e-faturalama, yerel gereksinimlerle birlikte uluslararasi standartlari takip eder. Teknik gereksinimler:',
        items: [
          'UBL 2.1 XML formati (OASIS standardi) — yapilandirilmis faturalar icin uluslararasi taninan format',
          'Nitelikli Elektronik Imza (QES) her e-fatura icin zorunlu',
          'UJP e-Fatura portali uzerinden kayit (efaktura.ujp.gov.mk)',
          'B2G Ekim 2026\'dan zorunlu, B2B gonullu (2027-2028 zorunlu bekleniyor)',
          'XML, MK yasasina gore tum 15 zorunlu fatura alanini icermeli',
          'Uretim oncesi dogrulama icin UJP\'de test ortami mevcut',
        ],
        steps: null,
      },
      {
        title: 'Facturino — Yerlesik E-Fatura',
        content: 'Facturino, olusturmadan gonderime kadar tam yerlesik e-Fatura destegine sahip tek Makedon cozumdur. Hazirlik: Evet, tam uyumlu.',
        items: [
          'UBL 2.1 XML olusturma yazilima yerlesik',
          'QES imzalama is akisina entegre',
          'UJP portalina dogrudan gonderim',
          'Zorunlu alanlarin otomatik dogrulamasi',
          'Hem B2G hem B2B faturalama icin calisir',
          'Her fatura icin PDF + XML cift cikti',
          'Bulut tabanli — yazilim kurulumu gerektirmez',
          'Ucretsiz plan e-Fatura destegi icerir',
          'Yuksek hacim icin toplu e-fatura olusturma',
        ],
        steps: null,
      },
      {
        title: 'PANTHEON — e-Fatura Modullu ERP',
        content: 'PANTHEON, daha genis platformunun parcasi olarak e-Fatura modulu sunan tam bir ERP sistemidir. Hazirlik: Evet, yapilandirma ile.',
        items: [
          'UBL XML disa aktarma mevcut',
          'Dis saglayici araciligiyla QES imzalama',
          'Masaustu kurulumu gerektirir',
          'Tam ERP paketinin parcasi — daha yuksek maliyet',
          'Buyuk hacimli faturalama icin guclu',
        ],
        steps: null,
      },
      {
        title: 'UJP Portali (Manuel) — Ucretsiz Devlet Secenegi',
        content: 'UJP, e-faturalarin manuel gonderimi icin ucretsiz bir portal sunar — ancak otomasyon yoktur. Hazirlik: Evet, ancak yalnizca manuel.',
        items: [
          'Kullanimi ucretsiz',
          'Manuel XML yukleme',
          'Otomasyon yok — bir seferde bir fatura',
          'Muhasebe yazilimiyla entegrasyon yok',
          'Cok dusuk hacim icin uygun (ayda 1-5 fatura)',
          'efaktura.ujp.gov.mk adresinden erisilebilir',
        ],
        steps: null,
      },
      {
        title: 'DIY (XML Olusturma) — Teknik Secenek',
        content: 'UBL XML\'i programatik olarak olusturmak, gelistirme ekiplerine sahip sirketler icin bir secenektir. Hazirlik: Uygulama kalitesine baglidir.',
        items: [
          'UBL XML\'i programatik olarak olusturun',
          'Gelistirici kaynaklari gerektirir',
          'QES imzalama ayri olarak ele alinir — dahil degildir',
          'Alan dogrulamasi yok — yuksek red riski',
          'Duzenlemeler degistiginde bakim yuku',
        ],
        steps: null,
      },
      {
        title: 'Cozum Karsilastirmasi',
        content: null,
        items: null,
        steps: [
          { step: 'UBL 2.1 Uyumlulugu', desc: 'Facturino: Tam yerlesik. PANTHEON: Evet, yapilandirma ile. UJP: Manuel. DIY: Uygulamaya baglidir.' },
          { step: 'QES Imzalama', desc: 'Facturino: Entegre. PANTHEON: Dis saglayici. UJP: Ayri olarak gerekli. DIY: Ayri.' },
          { step: 'Toplu Isleme', desc: 'Facturino: Evet, toplu olusturma. PANTHEON: Evet. UJP: Hayir — teker teker. DIY: Degisir.' },
          { step: 'Muhasebe Entegrasyonu', desc: 'Facturino: Tam entegre. PANTHEON: Tam entegre. UJP: Yok. DIY: Gelistirme gerektirir.' },
          { step: 'Bulut Erisimi', desc: 'Facturino: Evet, tam bulut. PANTHEON: Masaustu (bulut surumu mevcut). UJP: Web portali. DIY: Degisir.' },
          { step: 'Maliyet', desc: 'Facturino: Ucretsiz plan dahil. PANTHEON: Daha yuksek maliyet (ERP paketi). UJP: Ucretsiz. DIY: Gelistirme maliyetleri.' },
          { step: 'Kurulum Suresi', desc: 'Facturino: Dakikalar — kayit olun ve baslayin. PANTHEON: Kurulum icin gunler/haftalar. UJP: Dakikalar. DIY: Haftalar/aylar.' },
          { step: 'Destek', desc: 'Facturino: Makedoncada yerel destek. PANTHEON: Distributor destegi. UJP: Sinirli. DIY: Dahili.' },
        ],
      },
      {
        title: 'Ekim 2026\'ya Nasil Hazirlanmali',
        content: 'Zamaninda hazir olmak icin bu kontrol listesini takip edin:',
        items: [
          'efaktura.ujp.gov.mk adresine kaydolun',
          'Yetkili CA\'dan QES sertifikasi edinin (Kibriton, KIBS)',
          'UJP sandbox ortaminda test edin',
          'e-Fatura yaziliminizi secin',
          'Personeli yeni is akisi hakkinda egitin',
          'Fatura sablonlarini guncelleyin',
          'Devlet musterilerini hazirliginiz hakkinda bilgilendirin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili yazilar',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'E-fatura zorunlulugu 2026: Kim uymali ve nasil hazirlanmali' },
      { slug: 'sto-e-e-faktura', title: 'E-fatura nedir ve neden zorunludur?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Makedonya\'da faturanin zorunlu unsurlari' },
    ],
    cta: {
      title: 'E-Faturaya Hazir Olun',
      desc: 'Facturino UBL 2.1 ve QES imzasini destekler — ucretsiz baslayin ve son tarihten once hazir olun.',
      button: 'Ucretsiz baslayin',
    },
  },
} as const

export default async function NajdobarEFakturaSoftverPage({
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
    slug: 'najdobar-e-faktura-softver',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['e-invoice', 'e-faktura', 'software comparison', 'UBL', 'QES', 'Macedonia', 'B2G'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/najdobar-e-faktura-softver` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
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
