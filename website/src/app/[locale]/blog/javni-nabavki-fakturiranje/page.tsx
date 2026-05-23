import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/javni-nabavki-fakturiranje', {
    title: {
      mk: 'Фактурирање за јавни набавки: Водич за добавувачи на државата',
      en: 'Invoicing for Government Procurement: Supplier Guide for North Macedonia',
      sq: 'Faturimi për prokurime publike: Udhëzues për furnitorët e shtetit',
      tr: 'Kamu İhaleleri İçin Faturalama: Devlet Tedarikçi Rehberi',
    },
    description: {
      mk: 'Целосен водич за фактурирање кон државни институции во Македонија — ЕСЈН, е-фактура за B2G, рокови за плаќање, задолжителни полиња и најчести грешки при јавни набавки.',
      en: 'Complete guide to invoicing government institutions in North Macedonia — ESPP procurement, B2G e-invoice, payment terms, mandatory fields, and common rejection reasons.',
      sq: 'Udhëzues i plotë për faturimin e institucioneve shtetërore në Maqedoni — prokurimi ESPP, e-fatura B2G, afatet e pagesës, fushat e detyrueshme dhe arsyet e refuzimit.',
      tr: 'Kuzey Makedonya\'da devlet kurumlarına faturalama rehberi — ESPP ihalesi, B2G e-fatura, ödeme koşulları, zorunlu alanlar ve yaygın ret nedenleri.',
    },
    datePublished: '2026-05-23',
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Фактурирање за јавни набавки: Водич за добавувачи на државата',
    publishDate: '23 мај 2026',
    readTime: '9 мин читање',
    intro:
      'Државните институции во Македонија — министерства, општини, болници, училишта, агенции — се меѓу најголемите купувачи на стоки и услуги. Доколку вашата компанија добие договор за јавна набавка, мора да ги знаете специфичните правила за фактурирање: рокови за плаќање, барања за е-фактура и потребна документација. Овој водич покрива сè што еден добавувач треба да знае.',
    sections: [
      {
        title: 'Како функционираат јавните набавки (ЕСЈН)',
        content:
          'Системот за јавни набавки во Македонија е целосно електронски и регулиран со Законот за јавни набавки. Еве ги основните правила:',
        items: [
          'Сите набавки се објавуваат на ЕСЈН (Електронски систем за јавни набавки — e-nabavki.gov.mk)',
          'Видови тендери: отворена постапка, ограничена, преговарачка, набавка од мала вредност (<5.000 EUR)',
          'Поднесувањето понуди е електронски преку ЕСЈН порталот',
          'Доделувањето на договор се објавува јавно',
          'Рамковните спогодби се чести за повторливи набавки',
          'Потребни минимум 3 понуди за набавки од мала вредност',
        ],
        steps: null,
      },
      {
        title: 'Барања за фактура кон државни институции',
        content:
          'Фактурирањето кон државата има построги правила од стандардниот Б2Б промет. Секоја фактура мора да ги исполни следните услови:',
        items: [
          'Сите 15 задолжителни полиња на фактура по македонски закон мора да бидат присутни',
          'ЕДБ (даночен број) на двете страни — продавач и купувач',
          'Референтен број на договор/нарачка — задолжителен',
          'Референца на буџетска линија доколку е побарано во договорот',
          'Описот на стоки/услуги мора точно да одговара на договорот',
          'Пресметката на ДДВ мора да биде точна (државните институции не можат да одбиваат ДДВ)',
          'Фактурата мора да биде на македонски јазик',
          'Државните институции типично бараат е-фактура од октомври 2026',
        ],
        steps: null,
      },
      {
        title: 'Е-фактура за B2G (октомври 2026) — Големата промена',
        content:
          'Од октомври 2026, електронското фактурирање кон државни институции станува задолжително. Ова е најголемата промена во фактурирањето за јавни набавки:',
        items: [
          'B2G е-фактура задолжителна од октомври 2026',
          'Потребен UBL 2.1 XML формат',
          'Квалификуван електронски потпис (QES) е задолжителен',
          'Регистрирајте се на efaktura.ujp.gov.mk',
          'Тестирајте во sandbox околина пред да почнете live',
          'Мора да се генерираат и XML и PDF верзија',
          'Одбивање значи повторно издавање — одложува плаќање',
        ],
        steps: null,
      },
      {
        title: 'Рокови за плаќање и готовински тек — Што да очекувате',
        content:
          'Плаќањата од државни институции имаат специфичен тек. Разбирањето на процесот е клучно за планирање на готовинскиот тек:',
        items: null,
        steps: [
          { step: 'Потпишување на договор', desc: 'Роковите за плаќање се дефинирани во договорот — обично 30-60 дена од прием на фактура.' },
          { step: 'Испорака / завршена услуга', desc: 'По испораката се потпишува записник за прием (acceptance protocol) — без него не можете да фактурирате.' },
          { step: 'Поднесување на фактура', desc: 'Фактурата мора да се поднесе во рок од 8 дена од испораката согласно ЗДДВ.' },
          { step: 'Период на проверка', desc: 'Институцијата ја проверува фактурата — 15-30 дена за верификација на документацијата.' },
          { step: 'Плаќање', desc: 'Плаќањето се врши преку трезорска сметка (Трезор) — централен систем за сите буџетски корисници.' },
          { step: 'Решавање спорови', desc: 'Доколку фактурата е одбиена — исправете ги грешките и повторно поднесете. Секое одбивање го одложува плаќањето.' },
        ],
      },
      {
        title: 'Најчести грешки и одбивања',
        content:
          'Познавањето на најчестите причини за одбивање ви заштедува време и пари. Избегнете ги овие грешки:',
        items: [
          'Погрешен референтен број на договор — најчеста причина за одбивање',
          'Недостасува записник за прием (acceptance protocol)',
          'Грешка во пресметка на ДДВ — државата го плаќа бруто износот',
          'Датум на фактура пред датум на завршена испорака',
          'Недостасуваат задолжителни полиња (особено ЕДБ и референца на договор)',
          'Поднесување хартиена фактура кога е потребна е-фактура',
          'Погрешна референца на буџетска линија',
        ],
        steps: null,
      },
      {
        title: 'Совети за добавувачи на државата',
        content:
          'Практични совети за успешно фактурирање кон државни институции и одржување на стабилен готовински тек:',
        items: [
          'Секогаш наведете го бројот на договорот и нарачката',
          'Обезбедете потпишан записник за прием пред да фактурирате',
          'Двојно проверете ги сите 15 задолжителни полиња',
          'Поставете е-фактура пред октомври 2026',
          'Вкалкулирајте 30-60 дена рокови за плаќање во вашиот готовински тек',
          'Чувајте копии од целата документација за испорака',
          'Следете го ЕСЈН за нови можности за јавни набавки',
          'Размислете за рамковни спогодби за повторлива набавка',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'Е-фактура 2026: Кој мора и како да се подготвите' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура' },
      { slug: 'faktura-primer-mk', title: 'Фактура пример: Македонски образец' },
      { slug: 'najdobar-e-faktura-softver', title: 'Најдобар софтвер за е-Фактура 2026' },
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура: Чекор-по-чекор' },
    ],
    cta: {
      title: 'Facturino генерира усогласени е-фактури за државата',
      desc: 'Автоматска валидација на сите задолжителни полиња, UBL 2.1 и QES потпис — без ризик од одбивање.',
      button: 'Започнете бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Invoicing for Government Procurement: Supplier Guide for North Macedonia',
    publishDate: 'May 23, 2026',
    readTime: '9 min read',
    intro:
      'Government institutions in North Macedonia — ministries, municipalities, hospitals, schools, state agencies — are among the largest buyers of goods and services. If your company wins a public procurement contract, you need to know the specific invoicing rules: payment terms, e-Invoice requirements, and required documentation. This guide covers everything a supplier needs to know.',
    sections: [
      {
        title: 'How Government Procurement Works (ESPP)',
        content:
          'The public procurement system in North Macedonia is fully electronic and governed by the Public Procurement Law. Here are the key rules:',
        items: [
          'All procurements are published on ESPP (Electronic System for Public Procurement — e-nabavki.gov.mk)',
          'Tender types: open procedure, restricted, negotiated, small-value procurement (<EUR 5,000)',
          'Bid submission is electronic through the ESPP portal',
          'Contract award is published publicly',
          'Framework agreements are common for recurring purchases',
          'Minimum 3 bids required for small-value procurement',
        ],
        steps: null,
      },
      {
        title: 'Invoice Requirements for Government',
        content:
          'Invoicing government institutions has stricter rules than standard B2B transactions. Every invoice must meet the following conditions:',
        items: [
          'All 15 mandatory invoice fields per Macedonian law must be present',
          'EDB (tax number) of both parties — seller and buyer',
          'Contract/purchase order reference number is mandatory',
          'Budget line reference if required by the contract',
          'Goods/services description must match the contract exactly',
          'VAT calculation must be correct (government institutions cannot deduct VAT)',
          'Invoice must be in Macedonian language',
          'Government institutions typically require e-Invoice from October 2026',
        ],
        steps: null,
      },
      {
        title: 'e-Invoice for B2G (October 2026) — The Big Change',
        content:
          'Starting October 2026, electronic invoicing to government institutions becomes mandatory. This is the biggest change in public procurement invoicing:',
        items: [
          'B2G e-Invoice mandatory from October 2026',
          'UBL 2.1 XML format required',
          'Qualified Electronic Signature (QES) mandatory',
          'Register at efaktura.ujp.gov.mk',
          'Test in sandbox environment before going live',
          'Both XML and PDF must be generated',
          'Rejection means re-issue — delays payment',
        ],
        steps: null,
      },
      {
        title: 'Payment Terms and Cash Flow — What to Expect',
        content:
          'Payments from government institutions follow a specific flow. Understanding the process is key to planning your cash flow:',
        items: null,
        steps: [
          { step: 'Contract signing', desc: 'Payment terms are defined in the contract — usually 30-60 days from invoice receipt.' },
          { step: 'Delivery / service completion', desc: 'After delivery, an acceptance protocol is signed — you cannot invoice without it.' },
          { step: 'Invoice submission', desc: 'The invoice must be submitted within 8 days of delivery per the VAT Law (ZDDV).' },
          { step: 'Review period', desc: 'The institution reviews the invoice — 15-30 days for document verification.' },
          { step: 'Payment', desc: 'Payment is made via the Treasury account (Trezor) — a central system for all budget users.' },
          { step: 'Dispute resolution', desc: 'If the invoice is rejected — fix the errors and resubmit. Each rejection delays payment.' },
        ],
      },
      {
        title: 'Common Mistakes and Rejections',
        content:
          'Knowing the most common reasons for rejection saves you time and money. Avoid these mistakes:',
        items: [
          'Wrong contract reference number — most common reason for rejection',
          'Missing acceptance protocol (zapisnik za priem)',
          'VAT calculation error — government pays the gross amount',
          'Invoice date before delivery completion date',
          'Missing mandatory fields (especially EDB and contract reference)',
          'Submitting a paper invoice when e-Invoice is required',
          'Incorrect budget line reference',
        ],
        steps: null,
      },
      {
        title: 'Tips for Government Suppliers',
        content:
          'Practical tips for successful government invoicing and maintaining stable cash flow:',
        items: [
          'Always reference the contract and purchase order number',
          'Get the acceptance protocol signed before invoicing',
          'Double-check all 15 mandatory fields',
          'Set up e-Invoice before October 2026',
          'Factor 30-60 day payment terms into your cash flow',
          'Keep copies of all delivery documentation',
          'Monitor ESPP for new procurement opportunities',
          'Consider framework agreements for recurring supply',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'E-Invoice 2026: Who Must Comply & How to Prepare' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements in Macedonia' },
      { slug: 'faktura-primer-mk', title: 'Invoice Example: Macedonian Template' },
      { slug: 'najdobar-e-faktura-softver', title: 'Best E-Invoice Software 2026' },
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice: Step-by-Step' },
    ],
    cta: {
      title: 'Facturino generates compliant e-Invoices for government',
      desc: 'Automatic validation of all mandatory fields, UBL 2.1 and QES signing — no risk of rejection.',
      button: 'Start Free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Faturimi për prokurime publike: Udhëzues për furnitorët e shtetit',
    publishDate: '23 maj 2026',
    readTime: '9 min lexim',
    intro:
      'Institucionet shtetërore në Maqedoni — ministritë, komunat, spitalet, shkollat, agjencitë shtetërore — janë ndër blerësit më të mëdhenj të mallrave dhe shërbimeve. Nëse kompania juaj fiton një kontratë prokurimi publik, duhet të dini rregullat specifike të faturimit: afatet e pagesës, kërkesat për e-faturë dhe dokumentacionin e nevojshëm. Ky udhëzues mbulon gjithçka që një furnitor duhet të dijë.',
    sections: [
      {
        title: 'Si funksionon prokurimi publik (ESPP)',
        content:
          'Sistemi i prokurimit publik në Maqedoni është plotësisht elektronik dhe rregullohet nga Ligji për Prokurimet Publike. Ja rregullat kryesore:',
        items: [
          'Të gjitha prokurimet publikohen në ESPP (Sistemi Elektronik për Prokurime Publike — e-nabavki.gov.mk)',
          'Llojet e tenderëve: procedurë e hapur, e kufizuar, negociatuese, prokurim i vlerës së vogël (<5.000 EUR)',
          'Dorëzimi i ofertave është elektronik përmes portalit ESPP',
          'Dhënia e kontratës publikohet publikisht',
          'Marrëveshjet kornizë janë të zakonshme për blerje të përsëritura',
          'Nevojiten minimum 3 oferta për prokurim të vlerës së vogël',
        ],
        steps: null,
      },
      {
        title: 'Kërkesat e faturës për institucionet shtetërore',
        content:
          'Faturimi i institucioneve shtetërore ka rregulla më të rrepta se transaksionet standarde B2B. Çdo faturë duhet të plotësojë kushtet e mëposhtme:',
        items: [
          'Të gjitha 15 fushat e detyrueshme të faturës sipas ligjit maqedonas duhet të jenë prezente',
          'EDB (numri tatimor) i të dyja palëve — shitësi dhe blerësi',
          'Numri i referencës së kontratës/porosisë së blerjes është i detyrueshëm',
          'Referenca e linjës buxhetore nëse kërkohet nga kontrata',
          'Përshkrimi i mallrave/shërbimeve duhet të përputhet saktësisht me kontratën',
          'Llogaritja e TVSH-së duhet të jetë e saktë (institucionet shtetërore nuk mund të zbresin TVSH-në)',
          'Fatura duhet të jetë në gjuhën maqedonase',
          'Institucionet shtetërore zakonisht kërkojnë e-faturë nga tetori 2026',
        ],
        steps: null,
      },
      {
        title: 'E-fatura për B2G (tetor 2026) — Ndryshimi i madh',
        content:
          'Nga tetori 2026, faturimi elektronik ndaj institucioneve shtetërore bëhet i detyrueshëm. Ky është ndryshimi më i madh në faturimin e prokurimit publik:',
        items: [
          'E-fatura B2G e detyrueshme nga tetori 2026',
          'Kërkohet formati UBL 2.1 XML',
          'Nënshkrimi Elektronik i Kualifikuar (QES) është i detyrueshëm',
          'Regjistrohuni në efaktura.ujp.gov.mk',
          'Testoni në mjedisin sandbox para se të filloni live',
          'Duhet të gjenerohen si XML ashtu edhe PDF',
          'Refuzimi do të thotë rilëshim — vonon pagesën',
        ],
        steps: null,
      },
      {
        title: 'Afatet e pagesës dhe rrjedha e parave — Çfarë të prisni',
        content:
          'Pagesat nga institucionet shtetërore ndjekin një rrjedhë specifike. Kuptimi i procesit është çelësi për planifikimin e rrjedhës së parave:',
        items: null,
        steps: [
          { step: 'Nënshkrimi i kontratës', desc: 'Afatet e pagesës përcaktohen në kontratë — zakonisht 30-60 ditë nga marrja e faturës.' },
          { step: 'Dorëzimi / përfundimi i shërbimit', desc: 'Pas dorëzimit nënshkruhet protokolli i pranimit — nuk mund të faturoni pa të.' },
          { step: 'Dorëzimi i faturës', desc: 'Fatura duhet të dorëzohet brenda 8 ditëve nga dorëzimi sipas Ligjit të TVSH-së.' },
          { step: 'Periudha e rishikimit', desc: 'Institucioni rishikon faturën — 15-30 ditë për verifikimin e dokumenteve.' },
          { step: 'Pagesa', desc: 'Pagesa bëhet përmes llogarisë së Thesarit (Trezor) — sistem qendror për të gjithë përdoruesit buxhetorë.' },
          { step: 'Zgjidhja e mosmarrëveshjeve', desc: 'Nëse fatura refuzohet — korrigjoni gabimet dhe ridorëzoni. Çdo refuzim vonon pagesën.' },
        ],
      },
      {
        title: 'Gabimet më të zakonshme dhe refuzimet',
        content:
          'Njohja e arsyeve më të zakonshme për refuzim ju kursen kohë dhe para. Shmangni këto gabime:',
        items: [
          'Numër i gabuar i referencës së kontratës — arsyeja më e zakonshme për refuzim',
          'Mungon protokolli i pranimit (zapisnik za priem)',
          'Gabim në llogaritjen e TVSH-së — shteti paguan shumën bruto',
          'Data e faturës para datës së përfundimit të dorëzimit',
          'Mungojnë fushat e detyrueshme (veçanërisht EDB dhe referenca e kontratës)',
          'Dorëzimi i faturës në letër kur kërkohet e-faturë',
          'Referencë e gabuar e linjës buxhetore',
        ],
        steps: null,
      },
      {
        title: 'Këshilla për furnitorët e shtetit',
        content:
          'Këshilla praktike për faturim të suksesshëm ndaj institucioneve shtetërore dhe mbajtjen e rrjedhës së qëndrueshme të parave:',
        items: [
          'Gjithmonë referoni numrin e kontratës dhe porosisë së blerjes',
          'Merrni protokollin e pranimit të nënshkruar para se të faturoni',
          'Kontrolloni dyfish të gjitha 15 fushat e detyrueshme',
          'Konfiguroni e-faturën para tetorit 2026',
          'Përfshini afatet e pagesës 30-60 ditë në planifikimin e rrjedhës së parave',
          'Mbani kopje të gjithë dokumentacionit të dorëzimit',
          'Monitoroni ESPP për mundësi të reja prokurimi publik',
          'Konsideroni marrëveshje kornizë për furnizim të përsëritur',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'E-fatura 2026: Kush duhet dhe si të përgatiteni' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementet e detyrueshme të faturës' },
      { slug: 'faktura-primer-mk', title: 'Shembull fature: Modeli maqedonas' },
      { slug: 'najdobar-e-faktura-softver', title: 'Softueri më i mirë për e-Faturë 2026' },
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni faturë: Hap pas hapi' },
    ],
    cta: {
      title: 'Facturino gjeneron e-fatura në përputhje për shtetin',
      desc: 'Validim automatik i të gjitha fushave të detyrueshme, UBL 2.1 dhe nënshkrim QES — pa rrezik refuzimi.',
      button: 'Filloni falas',
    },
  },
  tr: {
    backLink: '← Blog\'a dön',
    tag: 'Rehber',
    title: 'Kamu İhaleleri İçin Faturalama: Devlet Tedarikçi Rehberi',
    publishDate: '23 Mayıs 2026',
    readTime: '9 dk okuma',
    intro:
      'Kuzey Makedonya\'daki devlet kurumları — bakanlıklar, belediyeler, hastaneler, okullar, devlet ajansları — mal ve hizmetlerin en büyük alıcıları arasındadır. Şirketiniz bir kamu ihale sözleşmesi kazanırsa, özel faturalama kurallarını bilmeniz gerekir: ödeme koşulları, e-fatura gereksinimleri ve gerekli belgeler. Bu rehber bir tedarikçinin bilmesi gereken her şeyi kapsar.',
    sections: [
      {
        title: 'Kamu İhaleleri Nasıl İşler (ESPP)',
        content:
          'Kuzey Makedonya\'daki kamu ihale sistemi tamamen elektroniktir ve Kamu İhale Kanunu ile düzenlenir. İşte temel kurallar:',
        items: [
          'Tüm ihaleler ESPP\'de yayınlanır (Elektronik Kamu İhale Sistemi — e-nabavki.gov.mk)',
          'İhale türleri: açık prosedür, sınırlı, müzakereli, küçük değerli alım (<5.000 EUR)',
          'Teklif sunumu ESPP portalı üzerinden elektronik olarak yapılır',
          'Sözleşme verilmesi kamuya açık olarak yayınlanır',
          'Tekrarlayan alımlar için çerçeve anlaşmaları yaygındır',
          'Küçük değerli alımlar için minimum 3 teklif gereklidir',
        ],
        steps: null,
      },
      {
        title: 'Devlet Kurumları İçin Fatura Gereksinimleri',
        content:
          'Devlet kurumlarına faturalama, standart B2B işlemlerden daha katı kurallara sahiptir. Her fatura aşağıdaki koşulları karşılamalıdır:',
        items: [
          'Makedonya yasasına göre 15 zorunlu fatura alanının tümü mevcut olmalıdır',
          'Her iki tarafın EDB\'si (vergi numarası) — satıcı ve alıcı',
          'Sözleşme/satın alma siparişi referans numarası zorunludur',
          'Sözleşmede gerekiyorsa bütçe kalemi referansı',
          'Mal/hizmet açıklaması sözleşmeyle tam olarak eşleşmelidir',
          'KDV hesaplaması doğru olmalıdır (devlet kurumları KDV indiremez)',
          'Fatura Makedonca dilinde olmalıdır',
          'Devlet kurumları genellikle Ekim 2026\'dan itibaren e-fatura talep eder',
        ],
        steps: null,
      },
      {
        title: 'B2G İçin e-Fatura (Ekim 2026) — Büyük Değişiklik',
        content:
          'Ekim 2026\'dan itibaren devlet kurumlarına elektronik faturalama zorunlu hale gelir. Bu, kamu ihale faturalamasındaki en büyük değişikliktir:',
        items: [
          'B2G e-fatura Ekim 2026\'dan itibaren zorunlu',
          'UBL 2.1 XML formatı gerekli',
          'Nitelikli Elektronik İmza (QES) zorunlu',
          'efaktura.ujp.gov.mk adresinde kaydolun',
          'Canlıya geçmeden önce sandbox ortamında test edin',
          'Hem XML hem de PDF üretilmelidir',
          'Ret, yeniden düzenleme anlamına gelir — ödemeyi geciktirir',
        ],
        steps: null,
      },
      {
        title: 'Ödeme Koşulları ve Nakit Akışı — Ne Beklenmeli',
        content:
          'Devlet kurumlarından yapılan ödemeler belirli bir akışı takip eder. Süreci anlamak nakit akışı planlaması için kritiktir:',
        items: null,
        steps: [
          { step: 'Sözleşme imzalama', desc: 'Ödeme koşulları sözleşmede tanımlanır — genellikle fatura alımından itibaren 30-60 gün.' },
          { step: 'Teslimat / hizmet tamamlama', desc: 'Teslimat sonrası kabul protokolü imzalanır — onsuz fatura kesemezsiniz.' },
          { step: 'Fatura sunumu', desc: 'Fatura, KDV Kanunu\'na göre teslimatın 8 günü içinde sunulmalıdır.' },
          { step: 'İnceleme süresi', desc: 'Kurum faturayı inceler — belge doğrulaması için 15-30 gün.' },
          { step: 'Ödeme', desc: 'Ödeme Hazine hesabı (Trezor) üzerinden yapılır — tüm bütçe kullanıcıları için merkezi sistem.' },
          { step: 'Uyuşmazlık çözümü', desc: 'Fatura reddedilirse — hataları düzeltin ve yeniden gönderin. Her ret ödemeyi geciktirir.' },
        ],
      },
      {
        title: 'Yaygın Hatalar ve Retler',
        content:
          'En yaygın ret nedenlerini bilmek size zaman ve para kazandırır. Bu hatalardan kaçının:',
        items: [
          'Yanlış sözleşme referans numarası — en yaygın ret nedeni',
          'Eksik kabul protokolü (zapisnik za priem)',
          'KDV hesaplama hatası — devlet brüt tutarı öder',
          'Fatura tarihi teslimat tamamlanma tarihinden önce',
          'Eksik zorunlu alanlar (özellikle EDB ve sözleşme referansı)',
          'E-fatura gerektiğinde kağıt fatura sunmak',
          'Yanlış bütçe kalemi referansı',
        ],
        steps: null,
      },
      {
        title: 'Devlet Tedarikçileri İçin İpuçları',
        content:
          'Devlet kurumlarına başarılı faturalama ve istikrarlı nakit akışı sürdürmek için pratik ipuçları:',
        items: [
          'Her zaman sözleşme ve satın alma sipariş numarasını belirtin',
          'Fatura kesmeden önce kabul protokolünü imzalatın',
          '15 zorunlu alanın tümünü çift kontrol edin',
          'Ekim 2026\'dan önce e-faturayı kurun',
          '30-60 günlük ödeme koşullarını nakit akışınıza dahil edin',
          'Tüm teslimat belgelerinin kopyalarını saklayın',
          'Yeni kamu ihale fırsatları için ESPP\'yi takip edin',
          'Tekrarlayan tedarik için çerçeve anlaşmaları değerlendirin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    related: [
      { slug: 'e-faktura-obvrska-2026', title: 'E-Fatura 2026: Kim Uymalı ve Nasıl Hazırlanmalı' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Makedonya\'da Zorunlu Fatura Unsurları' },
      { slug: 'faktura-primer-mk', title: 'Fatura Örneği: Makedonya Şablonu' },
      { slug: 'najdobar-e-faktura-softver', title: 'En İyi e-Fatura Yazılımı 2026' },
      { slug: 'kako-da-napravite-faktura', title: 'Fatura Nasıl Oluşturulur: Adım Adım' },
    ],
    cta: {
      title: 'Facturino devlet için uyumlu e-faturalar üretir',
      desc: 'Tüm zorunlu alanların otomatik doğrulaması, UBL 2.1 ve QES imzası — ret riski yok.',
      button: 'Ücretsiz başlayın',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function JavniNabavkiFakturiranjePage({
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
    slug: 'javni-nabavki-fakturiranje',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['јавни набавки', 'public procurement', 'ЕСЈН', 'B2G', 'e-invoice', 'government'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/javni-nabavki-fakturiranje` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Треба ли е-Фактура за државата?', answer: 'Да, од октомври 2026 е-фактура е задолжителна за сите B2G трансакции кон државни институции во UBL 2.1 формат со QES потпис.' },
        { question: 'Рок на плаќање?', answer: 'Државните институции плаќаат во рок од 30-60 дена од прием на фактурата, преку трезорска сметка (Трезор).' },
        { question: 'Што е ЕСЈН?', answer: 'ЕСЈН (Електронски систем за јавни набавки) е платформата на e-nabavki.gov.mk каде се објавуваат и спроведуваат сите јавни набавки.' },
      ])) }} />
      {/* ============================================================ */}
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        {/* Background blobs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>

        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          {/* Back link */}
          <Link
            href={`/${locale}/blog`}
            className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors"
          >
            {t.backLink}
          </Link>

          {/* Tag pill */}
          <div className="mb-4">
            <span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600">
              {t.tag}
            </span>
          </div>

          {/* Title */}
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            {t.title}
          </h1>

          {/* Meta info */}
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {t.publishDate}
            </span>
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {t.readTime}
            </span>
          </div>

          {/* Intro paragraph */}
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.intro}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  ARTICLE BODY                                                */}
      {/* ============================================================ */}
      <section className="py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="space-y-12">
            {t.sections.map((section, i) => (
              <div key={i}>
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                  {section.title}
                </h2>

                {/* Paragraph content */}
                {section.content && (
                  <p className="text-gray-700 leading-relaxed text-lg">
                    {section.content}
                  </p>
                )}

                {/* Bullet list items — green checks */}
                {section.items && (
                  <ul className="space-y-3 mt-2">
                    {section.items.map((item, j) => (
                      <li key={j} className="flex items-start gap-3">
                        <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                          <svg
                            className="w-3 h-3 text-green-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={3}
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              d="M5 13l4 4L19 7"
                            />
                          </svg>
                        </span>
                        <span className="text-gray-700 leading-relaxed">
                          {item}
                        </span>
                      </li>
                    ))}
                  </ul>
                )}

                {/* Numbered steps — indigo */}
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

      {/* RELATED ARTICLES — gray-50 bg */}
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

      {/* ============================================================ */}
      {/*  BOTTOM CTA — gradient indigo                                */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.cta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.cta.desc}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.cta.button}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
