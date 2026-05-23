import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/e-faktura-obvrska-2026', {
    title: {
      mk: 'Е-фактура 2026: Кој мора, кога почнува и како да се подготвите',
      en: 'E-Invoice Mandate North Macedonia 2026: Who Must Comply & How to Prepare',
      sq: 'E-fatura 2026: Kush duhet, kur fillon dhe si të përgatiteni',
      tr: 'E-Fatura Zorunluluğu 2026: Kim Uymalı ve Nasıl Hazırlanmalı',
    },
    description: {
      mk: 'Водич за е-фактура во Македонија 2026: задолжителност за B2G од октомври 2026, UBL 2.1 формат, QES потпис, регистрација во УЈП, технички барања и чекор-по-чекор подготовка.',
      en: 'Guide to e-invoicing in North Macedonia 2026: B2G mandate from October 2026, UBL 2.1 format, QES signing, UJP registration, technical requirements and step-by-step preparation.',
      sq: 'Udhëzues për e-faturën në Maqedoni 2026: detyrimi B2G nga tetori 2026, formati UBL 2.1, nënshkrimi QES, regjistrimi në DAP dhe përgatitja hap pas hapi.',
      tr: 'Kuzey Makedonya e-fatura rehberi 2026: Ekim 2026\'dan B2G zorunluluğu, UBL 2.1 formatı, QES imzası, UJP kaydı ve adım adım hazırlık.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Е-фактура 2026: Кој мора, кога почнува и како да се подготвите',
    publishDate: '23 мај 2026',
    readTime: '9 мин читање',
    intro:
      'Електронското фактурирање во Македонија преминува од опција во обврска. Од октомври 2026 сите добавувачи на државни институции мораат да испраќаат е-фактури во UBL 2.1 формат со квалификуван електронски потпис (QES). Во 2027 обврската се проширува на сите ДДВ обврзници. Овој водич објаснува кој е засегнат, кои се техничките барања и како да се подготвите навреме.',
    sections: [
      {
        title: 'Што е е-фактура?',
        content:
          'Е-фактура е фактура во структуриран дигитален формат (UBL 2.1 XML) — не скенирана слика или PDF приложен на email. Таа содржи машински читливи податоци за продавачот, купувачот, ставките, ДДВ и вкупните износи. Е-фактурата мора да биде потпишана со квалификуван електронски потпис (QES) и е правно еквивалентна на хартиена фактура. Разликата е клучна: PDF испратен по email НЕ е е-фактура — мора да биде UBL XML.',
        items: null,
        steps: null,
      },
      {
        title: 'Кога станува задолжителна?',
        content:
          'Имплементацијата на е-фактура во Македонија е фазна. Еве ги клучните датуми:',
        items: null,
        steps: [
          { step: 'Октомври 2026 — B2G задолжителна', desc: 'Сите компании кои фактурираат кон државни институции, јавни претпријатија и буџетски корисници мораат да испраќаат е-фактури преку платформата на УЈП.' },
          { step: 'Јануари 2027 (планирано) — Големи B2B', desc: 'ДДВ обврзници со годишен промет над 8.000.000 МКД мораат да издаваат е-фактури за сите B2B трансакции.' },
          { step: 'Јули 2027 (планирано) — Сите B2B', desc: 'Задолжителност за сите ДДВ обврзници без оглед на промет.' },
          { step: 'Веднаш — Доброволно', desc: 'Доброволна употреба е достапна ВЕДНАШ преку платформата на УЈП. Компаниите можат да почнат без да чекаат рок.' },
        ],
      },
      {
        title: 'Кој мора да издава е-фактура?',
        content:
          'Обврската се однесува на следните субјекти:',
        items: [
          'Компании кои продаваат на државни институции (B2G) — од октомври 2026',
          'Сите ДДВ обврзници за B2B трансакции — фазно од јануари 2027',
          'Компании кои примаат плаќања од буџетски корисници',
          'Учесници во системот за електронски јавни набавки (ЕСЈН)',
          'Странски компании со ДДВ регистрација во Македонија кои фактурираат кон МК субјекти',
        ],
        steps: null,
      },
      {
        title: 'Технички барања',
        content:
          'За да испраќате е-фактури, мора да ги исполните следните технички услови:',
        items: [
          'UBL 2.1 XML формат (Universal Business Language) — меѓународен стандард за структурирани фактури',
          'QES — квалификуван електронски потпис издаден од овластен провајдер (Кибритон, КИБС или друг)',
          'Уникатен идентификатор на фактура (UUID) за секоја фактура',
          'Сите задолжителни полиња по Чл. 53 ЗДДВ: ЕДБ на продавач и купувач, датум, ставки, ДДВ',
          'ЕДБ (даночен број) на купувачот мора да биде вклучен во XML-от',
          'Поднесување преку УЈП платформата за е-фактури или преку API интеграција',
          'Архивирање на е-фактурите минимум 10 години во оригинален формат',
        ],
        steps: null,
      },
      {
        title: 'Како да се подготвите',
        content:
          'Подготовката за е-фактура бара технички и организациски чекори. Почнете навреме — не чекајте последен момент:',
        items: null,
        steps: [
          { step: 'Проверете дали вашиот софтвер поддржува UBL 2.1', desc: 'Не секој сметководствен софтвер генерира UBL XML. Проверете или побарајте надградба. Facturino поддржува UBL 2.1 експорт нативно.' },
          { step: 'Набавете QES сертификат', desc: 'Контактирајте Кибритон (kibriton.mk) или друг овластен издавач. Цената е 2.000-5.000 МКД годишно. Сертификатот е на USB токен или cloud-based.' },
          { step: 'Регистрирајте се на УЈП платформата', desc: 'Посетете efaktura.ujp.gov.mk и регистрирајте ја вашата компанија. Ви треба ЕДБ и QES за регистрација.' },
          { step: 'Тестирајте во sandbox околина', desc: 'УЈП нуди тест околина каде можете да испраќате пробни е-фактури без правни последици. Искористете ја.' },
          { step: 'Обучете го персоналот', desc: 'Новиот работен тек: креирај фактура → потпиши со QES → испрати преку платформа → архивирај. Сите вклучени лица треба да го разберат процесот.' },
          { step: 'Ажурирајте ги шаблоните', desc: 'Проверете дали фактурите ги содржат сите задолжителни XML полиња: ЕДБ, ЕМБС, датуми, ставки со количини и единечни цени, ДДВ по стапки.' },
        ],
      },
      {
        title: 'Казни за неусогласеност',
        content:
          'Неусогласеноста со обврската за е-фактурирање носи конкретни последици:',
        items: [
          'Глоба EUR 500-3.000 за правно лице кое издава хартиена фактура наместо е-фактура (кога е задолжително)',
          'Глоба EUR 100-500 за одговорно лице во фирмата',
          'Буџетски корисници (државни институции) може да одбијат неелектронски фактури',
          'Можно исклучување од јавни набавки за компании кои не се усогласени',
          'Повторени прекршоци може да доведат до построги санкции',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'sto-e-e-faktura', title: 'Што е е-фактура и зошто е задолжителна?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура' },
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура: Чекор-по-чекор' },
    ],
    bottomCta: {
      title: 'Подготвени за е-фактура?',
      subtitle: 'Facturino поддржува UBL 2.1 и QES потпис — бидете спремни пред сите.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'E-Invoice Mandate North Macedonia 2026: Who Must Comply & How to Prepare',
    publishDate: 'May 23, 2026',
    readTime: '9 min read',
    intro:
      'Electronic invoicing in North Macedonia is moving from optional to mandatory. Starting October 2026, all suppliers to government institutions must send e-invoices in UBL 2.1 format with a Qualified Electronic Signature (QES). In 2027 the mandate extends to all VAT-registered companies. This guide explains who is affected, what the technical requirements are, and how to prepare on time.',
    sections: [
      {
        title: 'What is an e-invoice?',
        content:
          'An e-invoice is an invoice in a structured digital format (UBL 2.1 XML) — not a scanned image or a PDF attached to an email. It contains machine-readable data about the seller, buyer, line items, VAT, and totals. The e-invoice must be signed with a Qualified Electronic Signature (QES) and is legally equivalent to a paper invoice. The distinction matters: a PDF sent by email is NOT an e-invoice — it must be UBL XML.',
        items: null,
        steps: null,
      },
      {
        title: 'When does it become mandatory?',
        content:
          'E-invoicing implementation in North Macedonia follows a phased approach:',
        items: null,
        steps: [
          { step: 'October 2026 — B2G mandatory', desc: 'All companies invoicing government institutions, public enterprises, and budget users must send e-invoices via the UJP platform.' },
          { step: 'January 2027 (planned) — Large B2B', desc: 'VAT-registered companies with annual turnover above 8,000,000 MKD must issue e-invoices for all B2B transactions.' },
          { step: 'July 2027 (planned) — All B2B', desc: 'Mandatory for all VAT-registered companies regardless of turnover.' },
          { step: 'Now — Voluntary', desc: 'Voluntary adoption is available NOW through the UJP platform. Companies can start without waiting for the deadline.' },
        ],
      },
      {
        title: 'Who must issue e-invoices?',
        content:
          'The mandate applies to the following entities:',
        items: [
          'Companies selling to government institutions (B2G) — from October 2026',
          'All VAT-registered companies for B2B transactions — phased from January 2027',
          'Companies receiving payments from budget users',
          'Participants in the electronic public procurement system (ESPP)',
          'Foreign companies with MK VAT registration invoicing Macedonian entities',
        ],
        steps: null,
      },
      {
        title: 'Technical requirements',
        content:
          'To send e-invoices, you must meet the following technical conditions:',
        items: [
          'UBL 2.1 XML format (Universal Business Language) — international standard for structured invoices',
          'QES — Qualified Electronic Signature issued by an authorized provider (Kibriton, KIBS, or others)',
          'Unique invoice identifier (UUID) for each invoice',
          'All mandatory fields per Art. 53 VAT Law: seller and buyer EDB, date, line items, VAT',
          'Buyer\'s EDB (tax number) must be included in the XML',
          'Submission via UJP e-Invoice platform or API integration',
          'Archive e-invoices for minimum 10 years in original format',
        ],
        steps: null,
      },
      {
        title: 'How to prepare',
        content:
          'Preparing for e-invoicing requires both technical and organizational steps. Start early — don\'t wait for the deadline:',
        items: null,
        steps: [
          { step: 'Check if your software supports UBL 2.1', desc: 'Not every accounting software generates UBL XML. Verify or request an upgrade. Facturino supports UBL 2.1 export natively.' },
          { step: 'Obtain a QES certificate', desc: 'Contact Kibriton (kibriton.mk) or another authorized issuer. Cost is 2,000-5,000 MKD annually. Certificate comes on USB token or cloud-based.' },
          { step: 'Register on the UJP platform', desc: 'Visit efaktura.ujp.gov.mk and register your company. You need your EDB and QES for registration.' },
          { step: 'Test in the sandbox environment', desc: 'UJP offers a test environment where you can send trial e-invoices without legal consequences. Use it.' },
          { step: 'Train your staff', desc: 'New workflow: create invoice → sign with QES → send via platform → archive. All involved personnel should understand the process.' },
          { step: 'Update your templates', desc: 'Verify invoices contain all mandatory XML fields: EDB, EMBS, dates, line items with quantities and unit prices, VAT by rate.' },
        ],
      },
      {
        title: 'Penalties for non-compliance',
        content:
          'Non-compliance with e-invoicing obligations carries concrete consequences:',
        items: [
          'Fine EUR 500-3,000 for a legal entity issuing paper instead of e-invoice (when mandatory)',
          'Fine EUR 100-500 for the responsible person',
          'Government buyers may reject non-electronic invoices',
          'Possible exclusion from public procurement for non-compliant companies',
          'Repeated violations may lead to stricter sanctions',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'sto-e-e-faktura', title: 'What Is E-Invoice and Why Is It Mandatory?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements in Macedonia' },
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice: Step-by-Step' },
    ],
    bottomCta: {
      title: 'Ready for e-invoicing?',
      subtitle: 'Facturino supports UBL 2.1 and QES signing — be prepared before the deadline.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'E-fatura 2026: Kush duhet, kur fillon dhe si të përgatiteni',
    publishDate: '23 maj 2026',
    readTime: '9 min lexim',
    intro:
      'Faturimi elektronik në Maqedoni po kalon nga opsion në detyrim. Nga tetori 2026 të gjithë furnizuesit e institucioneve shtetërore duhet të dërgojnë e-fatura në formatin UBL 2.1 me Nënshkrim Elektronik të Kualifikuar (QES). Në 2027 detyrimi zgjerohet për të gjithë tatimpaguesit e TVSH-së. Ky udhëzues shpjegon kush preket, cilat janë kërkesat teknike dhe si të përgatiteni me kohë.',
    sections: [
      {
        title: 'Çfarë është e-fatura?',
        content:
          'E-fatura është faturë në format dixhital të strukturuar (UBL 2.1 XML) — jo imazh i skanuar ose PDF bashkëngjitur në email. Ajo përmban të dhëna të lexueshme nga makina për shitësin, blerësin, artikujt, TVSH-në dhe totalet. E-fatura duhet të nënshkruhet me Nënshkrim Elektronik të Kualifikuar (QES) dhe është ligjërisht ekuivalente me faturën në letër.',
        items: null,
        steps: null,
      },
      {
        title: 'Kur bëhet e detyrueshme?',
        content:
          'Zbatimi i e-faturës në Maqedoni ndjek një qasje fazore:',
        items: null,
        steps: [
          { step: 'Tetor 2026 — B2G e detyrueshme', desc: 'Të gjitha kompanitë që faturojnë institucionet shtetërore duhet të dërgojnë e-fatura përmes platformës DAP.' },
          { step: 'Janar 2027 (planifikuar) — B2B të mëdha', desc: 'Tatimpaguesit e TVSH-së me qarkullim vjetor mbi 8.000.000 MKD duhet të lëshojnë e-fatura për të gjitha transaksionet B2B.' },
          { step: 'Korrik 2027 (planifikuar) — Të gjitha B2B', desc: 'E detyrueshme për të gjithë tatimpaguesit e TVSH-së pavarësisht qarkullimit.' },
          { step: 'Tani — Vullnetare', desc: 'Adoptimi vullnetar është i disponueshëm TANI përmes platformës DAP.' },
        ],
      },
      {
        title: 'Kush duhet të lëshojë e-fatura?',
        content:
          'Detyrimi vlen për subjektet e mëposhtme:',
        items: [
          'Kompanitë që shesin tek institucionet shtetërore (B2G) — nga tetori 2026',
          'Të gjithë tatimpaguesit e TVSH-së për transaksione B2B — me faza nga janari 2027',
          'Kompanitë që marrin pagesa nga përdoruesit buxhetorë',
          'Pjesëmarrësit në sistemin elektronik të prokurimit publik (ESPP)',
          'Kompanitë e huaja me regjistrim TVSH në MK që faturojnë subjekte maqedonase',
        ],
        steps: null,
      },
      {
        title: 'Kërkesat teknike',
        content:
          'Për të dërguar e-fatura, duhet të plotësoni kushtet e mëposhtme teknike:',
        items: [
          'Formati UBL 2.1 XML (Universal Business Language)',
          'QES — Nënshkrim Elektronik i Kualifikuar nga ofrues i autorizuar (Kibriton, KIBS)',
          'Identifikues unik i faturës (UUID) për çdo faturë',
          'Të gjitha fushat e detyrueshme sipas Neni 53 Ligjit të TVSH-së',
          'EDB i blerësit duhet të përfshihet në XML',
          'Dorëzimi përmes platformës DAP ose integrimit API',
          'Arkivimi i e-faturave minimum 10 vjet në formatin origjinal',
        ],
        steps: null,
      },
      {
        title: 'Si të përgatiteni',
        content:
          'Përgatitja për e-faturën kërkon hapa teknike dhe organizative:',
        items: null,
        steps: [
          { step: 'Kontrolloni nëse softueri juaj mbështet UBL 2.1', desc: 'Jo çdo softuer kontabiliteti gjeneron UBL XML. Verifikoni ose kërkoni përditësim. Facturino mbështet eksportin UBL 2.1.' },
          { step: 'Merrni certifikatë QES', desc: 'Kontaktoni Kibriton (kibriton.mk). Kostoja është 2.000-5.000 MKD në vit.' },
          { step: 'Regjistrohuni në platformën DAP', desc: 'Vizitoni efaktura.ujp.gov.mk dhe regjistroni kompaninë tuaj.' },
          { step: 'Testoni në mjedisin sandbox', desc: 'DAP ofron mjedis testimi ku mund të dërgoni e-fatura provë pa pasoja ligjore.' },
          { step: 'Trajnoni stafin', desc: 'Rrjedha e re: krijo faturë → nënshkruaj me QES → dërgo → arkivo.' },
          { step: 'Përditësoni shabllonet', desc: 'Sigurohuni që faturat përmbajnë të gjitha fushat e detyrueshme XML.' },
        ],
      },
      {
        title: 'Gjobat për mospajtueshmëri',
        content:
          'Mospajtueshmëria me detyrimet e e-faturimit bart pasoja konkrete:',
        items: [
          'Gjobë EUR 500-3.000 për personin juridik që lëshon faturë letre në vend të e-faturës',
          'Gjobë EUR 100-500 për personin përgjegjës',
          'Blerësit buxhetorë mund të refuzojnë faturat joelektronike',
          'Përjashtim i mundshëm nga prokurimi publik',
          'Shkeljet e përsëritura mund të çojnë në sanksione më të rrepta',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të lidhur',
    relatedArticles: [
      { slug: 'sto-e-e-faktura', title: 'Çfarë është e-fatura dhe pse është e detyrueshme?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme të faturës' },
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni faturë: Hap pas hapi' },
    ],
    bottomCta: {
      title: 'Gati për e-faturën?',
      subtitle: 'Facturino mbështet UBL 2.1 dhe nënshkrimin QES — përgatituni para afatit.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Blog\'a dön',
    tag: 'Rehber',
    title: 'E-Fatura Zorunluluğu 2026: Kim Uymalı ve Nasıl Hazırlanmalı',
    publishDate: '23 Mayıs 2026',
    readTime: '9 dk okuma',
    intro:
      'Kuzey Makedonya\'da elektronik faturalama opsiyonelden zorunluya geçiyor. Ekim 2026\'dan itibaren devlet kurumlarının tüm tedarikçileri, QES imzalı UBL 2.1 formatında e-fatura göndermek zorunda. 2027\'de zorunluluk tüm KDV mükelleflerine genişliyor. Bu rehber kimin etkilendiğini, teknik gereksinimleri ve nasıl hazırlanacağınızı açıklıyor.',
    sections: [
      {
        title: 'E-fatura nedir?',
        content:
          'E-fatura, yapılandırılmış dijital formatta (UBL 2.1 XML) bir faturadır — taranmış görüntü veya e-postaya eklenmiş PDF değil. Satıcı, alıcı, kalemler, KDV ve toplamlar hakkında makine tarafından okunabilir veriler içerir. Nitelikli Elektronik İmza (QES) ile imzalanmalıdır ve yasal olarak kağıt faturayla eşdeğerdir.',
        items: null,
        steps: null,
      },
      {
        title: 'Ne zaman zorunlu oluyor?',
        content:
          'E-fatura uygulaması aşamalı bir yaklaşım izliyor:',
        items: null,
        steps: [
          { step: 'Ekim 2026 — B2G zorunlu', desc: 'Devlet kurumlarına fatura kesen tüm şirketler UJP platformu üzerinden e-fatura göndermek zorunda.' },
          { step: 'Ocak 2027 (planlanan) — Büyük B2B', desc: 'Yıllık cirosu 8.000.000 MKD üzerinde olan KDV mükellefleri tüm B2B işlemler için e-fatura düzenlemeli.' },
          { step: 'Temmuz 2027 (planlanan) — Tüm B2B', desc: 'Ciroya bakılmaksızın tüm KDV mükellefleri için zorunlu.' },
          { step: 'Şimdi — Gönüllü', desc: 'Gönüllü kullanım UJP platformu üzerinden ŞİMDİ mevcut.' },
        ],
      },
      {
        title: 'Kim e-fatura düzenlemeli?',
        content:
          'Zorunluluk aşağıdaki kuruluşları kapsar:',
        items: [
          'Devlet kurumlarına satan şirketler (B2G) — Ekim 2026\'dan itibaren',
          'Tüm KDV mükellefleri B2B işlemler için — Ocak 2027\'den aşamalı',
          'Bütçe kullanıcılarından ödeme alan şirketler',
          'Elektronik kamu ihale sistemine (ESPP) katılanlar',
          'MK KDV kaydı olan yabancı şirketler',
        ],
        steps: null,
      },
      {
        title: 'Teknik gereksinimler',
        content:
          'E-fatura göndermek için aşağıdaki teknik koşulları karşılamalısınız:',
        items: [
          'UBL 2.1 XML formatı (Universal Business Language)',
          'QES — yetkili sağlayıcıdan Nitelikli Elektronik İmza (Kibriton, KIBS)',
          'Her fatura için benzersiz tanımlayıcı (UUID)',
          'KDV Kanunu Madde 53\'e göre tüm zorunlu alanlar',
          'Alıcının EDB\'si (vergi numarası) XML\'de bulunmalı',
          'UJP e-Fatura platformu veya API entegrasyonu üzerinden gönderim',
          'E-faturaların orijinal formatta en az 10 yıl arşivlenmesi',
        ],
        steps: null,
      },
      {
        title: 'Nasıl hazırlanmalı',
        content:
          'E-faturaya hazırlık hem teknik hem organizasyonel adımlar gerektirir:',
        items: null,
        steps: [
          { step: 'Yazılımınızın UBL 2.1 destekleyip desteklemediğini kontrol edin', desc: 'Her muhasebe yazılımı UBL XML üretmez. Facturino UBL 2.1 dışa aktarımını doğal olarak destekler.' },
          { step: 'QES sertifikası edinin', desc: 'Kibriton (kibriton.mk) ile iletişime geçin. Yıllık maliyet 2.000-5.000 MKD.' },
          { step: 'UJP platformuna kaydolun', desc: 'efaktura.ujp.gov.mk adresini ziyaret edin ve şirketinizi kaydedin.' },
          { step: 'Sandbox ortamında test edin', desc: 'UJP yasal sonuç olmadan deneme e-faturaları gönderebileceğiniz bir test ortamı sunar.' },
          { step: 'Personeli eğitin', desc: 'Yeni iş akışı: fatura oluştur → QES ile imzala → platform üzerinden gönder → arşivle.' },
          { step: 'Şablonları güncelleyin', desc: 'Faturaların tüm zorunlu XML alanlarını içerdiğinden emin olun.' },
        ],
      },
      {
        title: 'Uyumsuzluk cezaları',
        content:
          'E-fatura yükümlülüklerine uyumsuzluk somut sonuçlar doğurur:',
        items: [
          'E-fatura yerine kağıt fatura düzenleyen tüzel kişiye 500-3.000 EUR para cezası',
          'Sorumlu kişiye 100-500 EUR para cezası',
          'Devlet alıcıları elektronik olmayan faturaları reddedebilir',
          'Uyumsuz şirketler kamu ihalelerinden çıkarılabilir',
          'Tekrarlanan ihlaller daha sıkı yaptırımlara yol açabilir',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'sto-e-e-faktura', title: 'E-fatura nedir ve neden zorunludur?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Makedonya\'da faturanın zorunlu unsurları' },
      { slug: 'kako-da-napravite-faktura', title: 'Fatura nasıl oluşturulur: Adım adım' },
    ],
    bottomCta: {
      title: 'E-faturaya hazır mısınız?',
      subtitle: 'Facturino UBL 2.1 ve QES imzasını destekler — son tarihten önce hazır olun.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function EFakturaObvrskaPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const blogLabel = locale === 'mk' ? 'Блог' : 'Blog'
  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'
  const articleLd = articleJsonLd({
    locale,
    slug: 'e-faktura-obvrska-2026',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['е-фактура', 'e-invoice', 'UBL', 'QES', 'UJP', 'Macedonia'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/e-faktura-obvrska-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/blog`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                    href={`/${locale}/blog/${ra.slug}`}
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
