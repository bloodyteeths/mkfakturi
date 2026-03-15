import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'
import Image from 'next/image'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/ai-skener-dokumenti', {
    title: {
      mk: 'AI скенирање фактури — автоматско книжење за 10 секунди | Facturino',
      en: 'AI Invoice Scanner — Automatic Bookkeeping in 10 Seconds | Facturino',
      sq: 'Skanimi AI i faturave — kontabilizim automatik në 10 sekonda | Facturino',
      tr: 'AI Fatura Tarayıcı — 10 Saniyede Otomatik Muhasebe | Facturino',
    },
    description: {
      mk: 'Скенирајте фактури, сметки и документи со AI. Автоматска класификација, екстракција на податоци и книжење за 10 секунди наместо 15 минути рачно.',
      en: 'Scan invoices, receipts and documents with AI. Automatic classification, data extraction and bookkeeping in 10 seconds instead of 15 minutes manually.',
      sq: 'Skanoni fatura, fatura dhe dokumente me AI. Klasifikim automatik, nxjerrje e dhënave dhe kontabilizim në 10 sekonda në vend të 15 minutave manualisht.',
      tr: 'Faturaları, fişleri ve belgeleri AI ile tarayın. Otomatik sınıflandırma, veri çıkarma ve 15 dakika yerine 10 saniyede muhasebe kaydı.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Производ',
    title: 'AI скенирање на фактури: добијте книжење за 10 секунди',
    publishDate: '15 март 2026',
    readTime: '8 мин читање',
    heroAlt: 'AI скенирање на фактури во Facturino — дигитализација на документи за сметководство во Македонија',
    intro: 'Секој сметководител и сопственик на мал бизнис во Македонија го знае проблемот: купишта хартиени фактури, рачно внесување на податоци, грешки при препишување и изгубени документи. Просечното време за рачно книжење на една фактура е 15 минути — помножете го тоа со стотици фактури месечно и добивате десетици часови потрошени на механичка работа. Facturino го решава овој проблем со AI Document Hub — скенирајте фактура, и добијте целосно книжење за само 10 секунди. Без рачно внесување, без грешки, без изгубени документи.',
    sections: [
      {
        title: 'Како работи AI скенерот за документи',
        content: 'AI скенерот во Facturino користи напредна вештачка интелигенција за да ги претвори вашите хартиени и дигитални документи во структурирани сметководствени записи. Целиот процес е дизајниран да биде едноставен, брз и точен — дури и за корисници без техничко искуство. Еве го чекор по чекор процесот на скенирање и автоматско книжење:',
        items: null,
        steps: [
          { step: 'Поставете или фотографирајте документ', desc: 'Поставете PDF, слика или фотографија на фактурата директно во Facturino. Може да користите компјутер, таблет или мобилен телефон. Поддржани формати вклучуваат PDF, JPG, PNG и HEIC. Нема ограничување на големината — системот обработува и повеќестранични документи.' },
          { step: 'AI класификација на типот документ', desc: 'Вештачката интелигенција автоматски го препознава типот на документот. Системот разликува 7 типа: влезна фактура, излезна фактура, фискална сметка, банков извод, даночен образец, договор и друг документ. Точноста на класификацијата е над 95% благодарение на моделите тренирани на македонски документи.' },
          { step: 'Екстракција на клучни податоци', desc: 'AI го анализира содржината на документот и автоматски ги извлекува сите релевантни полиња: износ, датум, ДДВ стапка, добавувач/клиент, број на фактура, ЕДБ и ЕМБС. Системот работи и со кирилични и со латинични документи, и препознава различни формати на македонски фактури.' },
          { step: 'Преглед и верификација', desc: 'Извлечените податоци се прикажуваат на прегледен екран каде можете да ги проверите и по потреба коригирате. Полињата кои AI ги препознал со висока сигурност се означени зелено, а оние со пониска сигурност жолто — за да знаете точно каде да обрнете внимание. Ова е клучниот чекор на човечка верификација.' },
          { step: 'Потврда со еден клик', desc: 'Откако ќе ги потврдите податоците, со еден клик на копчето „Потврди" се креира целосен сметководствен запис. Фактурата се книжи, добавувачот се додава (ако е нов), ДДВ-то се пресметува и трансакцијата се евидентира. Целиот процес трае просечно 10 секунди од момент на прикачување до готово книжење.' },
          { step: 'Ентитет е креиран — готово!', desc: 'Новиот запис е веднаш достапен во системот. Може да биде влезна фактура, трошок, банкова трансакција или друг ентитет — зависно од типот на скенираниот документ. Оригиналниот документ автоматски се прикачува како прилог и е поврзан со креираниот запис за идна ревизија.' },
        ],
      },
      {
        title: 'Што може да се скенира со Facturino',
        content: 'AI Document Hub не е ограничен само на фактури. Системот може да обработи широк спектар на деловни документи кои секојдневно ги среќаваат македонските бизниси и сметководители. Поддржани се 7 типа документи за автоматска обработка и книжење:',
        items: [
          'Влезни фактури од добавувачи — домашни и странски, хартиени и електронски',
          'Излезни фактури — верификација и книжење на сопствените фактури',
          'Фискални сметки — сметки од продавници, ресторани, бензински пумпи',
          'Банкови изводи — автоматско препознавање на трансакции од банков извод',
          'Даночни образци — ДДВ пријави, годишни даночни обрасци, УЈП документи',
          'Договори — екстракција на клучни услови, рокови и износи од договори',
          'Други деловни документи — понуди, нарачки, добавници, про-форма фактури',
        ],
        steps: null,
      },
      {
        title: 'Зошто AI скенирањето е подобро од рачно внесување',
        content: 'Рачното внесување на фактури е бавно, склоно кон грешки и скапо. AI скенирањето го елиминира секој од овие проблеми. Еве директна споредба меѓу традиционалното рачно книжење и автоматското AI книжење во Facturino:',
        items: [
          'Брзина: 10 секунди со AI наспроти 15 минути рачно — 90 пати побрзо',
          'Точност: AI + човечка верификација елиминира речиси сите грешки, наспроти 3-5% стапка на грешки при рачно внесување',
          'Трошок: Автоматизирано скенирање штеди 20-40 часа месечно за средно големо претпријатие',
          'Конзистентност: AI секогаш го применува истиот формат и правила, без варијации',
          'Трагливост: Секој скениран документ е автоматски архивиран и поврзан со книжењето',
          'Достапност: Скенирајте од било каде — канцеларија, дома или на пат, преку мобилен',
        ],
        steps: null,
      },
      {
        title: 'Безбедност и заштита на документите',
        content: 'Знаеме дека финансиските документи содржат чувствителни деловни информации. Затоа безбедноста е вградена во секој аспект на AI Document Hub. Сите документи се шифрирани при пренос (TLS 1.3) и при складирање (AES-256). Вашите податоци се чуваат на сервери во Европската Унија, во согласност со GDPR регулативата. Пристапот до документите е контролиран со повеќеслојна автентикација и ревизиони логови. Ниеден документ не се споделува со трети страни — AI обработката се случува во нашата приватна инфраструктура, а не преку јавни API-ја. Редовни безбедносни ревизии обезбедуваат дека вашите податоци се заштитени по најновите стандарди.',
        items: [
          'Шифрирање од крај до крај (TLS 1.3 + AES-256)',
          'Податоци складирани во EU, GDPR усогласено',
          'Приватна AI инфраструктура — без споделување со трети страни',
          'Ревизиони логови за секој пристап до документ',
          'Автоматски бекапи и disaster recovery',
        ],
        steps: null,
      },
      {
        title: 'Започнете со AI скенирање на документи',
        content: 'Регистрирајте се бесплатно на Facturino и пробајте го AI Document Hub со вашите фактури. Бесплатниот план вклучува можност за скенирање, а со Standard и Business плановите добивате неограничено скенирање и напредни функции. Нема потреба од кредитна картичка за да започнете. Ако сте сметководител, нашата партнерска програма ви дава пристап до AI скенирањето за сите ваши клиенти од една конзола — бесплатно за партнери.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
      { slug: 'facturino-vs-excel', title: 'Facturino наспроти Excel: зошто да преминете' },
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи: водич за бизниси' },
    ],
    cta: {
      title: 'Скенирајте ја првата фактура за 10 секунди',
      desc: 'Регистрирајте се бесплатно и дознајте колку време може да заштедите со AI скенирање на документи.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Product',
    title: 'AI Document Scanner: Get Bookkeeping Done in 10 Seconds',
    publishDate: 'March 15, 2026',
    readTime: '8 min read',
    heroAlt: 'AI invoice scanning in Facturino — document digitalization for accounting in Macedonia',
    intro: 'Every accountant and small business owner in Macedonia knows the problem: piles of paper invoices, manual data entry, transcription errors, and lost documents. The average time to manually process a single invoice is 15 minutes — multiply that by hundreds of invoices per month and you get dozens of hours spent on mechanical work. Facturino solves this problem with AI Document Hub — scan an invoice and get complete bookkeeping in just 10 seconds. No manual entry, no errors, no lost documents.',
    sections: [
      {
        title: 'How the AI Document Scanner Works',
        content: 'The AI scanner in Facturino uses advanced artificial intelligence to transform your paper and digital documents into structured accounting records. The entire process is designed to be simple, fast, and accurate — even for users without technical experience. Here is the step-by-step scanning and automatic bookkeeping process:',
        items: null,
        steps: [
          { step: 'Upload or Photograph a Document', desc: 'Upload a PDF, image, or photo of the invoice directly into Facturino. You can use a computer, tablet, or mobile phone. Supported formats include PDF, JPG, PNG, and HEIC. There is no size limit — the system processes multi-page documents as well.' },
          { step: 'AI Document Type Classification', desc: 'The artificial intelligence automatically recognizes the document type. The system distinguishes 7 types: incoming invoice, outgoing invoice, fiscal receipt, bank statement, tax form, contract, and other document. Classification accuracy exceeds 95% thanks to models trained on Macedonian documents.' },
          { step: 'Key Data Extraction', desc: 'AI analyzes the document content and automatically extracts all relevant fields: amount, date, VAT rate, supplier/client, invoice number, EDB, and EMBS. The system works with both Cyrillic and Latin documents and recognizes various Macedonian invoice formats.' },
          { step: 'Review and Verification', desc: 'Extracted data is displayed on a clear review screen where you can check and correct if needed. Fields that AI recognized with high confidence are marked green, while those with lower confidence are marked yellow — so you know exactly where to pay attention. This is the crucial human verification step.' },
          { step: 'One-Click Confirmation', desc: 'Once you confirm the data, a single click on the "Confirm" button creates a complete accounting record. The invoice is booked, the supplier is added (if new), VAT is calculated, and the transaction is recorded. The entire process averages 10 seconds from upload to completed bookkeeping.' },
          { step: 'Entity Created — Done!', desc: 'The new record is immediately available in the system. It can be an incoming invoice, expense, bank transaction, or other entity — depending on the scanned document type. The original document is automatically attached and linked to the created record for future audit reference.' },
        ],
      },
      {
        title: 'What Can Be Scanned with Facturino',
        content: 'AI Document Hub is not limited to invoices alone. The system can process a wide range of business documents that Macedonian businesses and accountants encounter daily. 7 document types are supported for automatic processing and bookkeeping:',
        items: [
          'Incoming invoices from suppliers — domestic and foreign, paper and electronic',
          'Outgoing invoices — verification and bookkeeping of your own invoices',
          'Fiscal receipts — receipts from stores, restaurants, gas stations',
          'Bank statements — automatic transaction recognition from bank statements',
          'Tax forms — VAT returns, annual tax forms, UJP documents',
          'Contracts — extraction of key terms, deadlines, and amounts from contracts',
          'Other business documents — quotes, orders, delivery notes, pro-forma invoices',
        ],
        steps: null,
      },
      {
        title: 'Why AI Scanning Beats Manual Data Entry',
        content: 'Manual invoice entry is slow, error-prone, and expensive. AI scanning eliminates each of these problems. Here is a direct comparison between traditional manual bookkeeping and automatic AI bookkeeping in Facturino:',
        items: [
          'Speed: 10 seconds with AI versus 15 minutes manually — 90 times faster',
          'Accuracy: AI + human verification eliminates virtually all errors, versus 3-5% error rate with manual entry',
          'Cost: Automated scanning saves 20-40 hours per month for a medium-sized business',
          'Consistency: AI always applies the same format and rules, without variations',
          'Traceability: Every scanned document is automatically archived and linked to the booking',
          'Accessibility: Scan from anywhere — office, home, or on the go via mobile',
        ],
        steps: null,
      },
      {
        title: 'Document Security and Protection',
        content: 'We know that financial documents contain sensitive business information. That is why security is built into every aspect of AI Document Hub. All documents are encrypted in transit (TLS 1.3) and at rest (AES-256). Your data is stored on servers in the European Union, in compliance with GDPR regulations. Document access is controlled through multi-layer authentication and audit logs. No document is shared with third parties — AI processing happens in our private infrastructure, not through public APIs. Regular security audits ensure your data is protected by the latest standards.',
        items: [
          'End-to-end encryption (TLS 1.3 + AES-256)',
          'Data stored in the EU, GDPR compliant',
          'Private AI infrastructure — no sharing with third parties',
          'Audit logs for every document access',
          'Automatic backups and disaster recovery',
        ],
        steps: null,
      },
      {
        title: 'Get Started with AI Document Scanning',
        content: 'Sign up free on Facturino and try AI Document Hub with your invoices. The free plan includes scanning capability, while Standard and Business plans give you unlimited scanning and advanced features. No credit card needed to get started. If you are an accountant, our partner program gives you access to AI scanning for all your clients from a single console — free for partners.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
      { slug: 'facturino-vs-excel', title: 'Facturino vs Excel: Why You Should Switch' },
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management: A Guide for Businesses' },
    ],
    cta: {
      title: 'Scan Your First Invoice in 10 Seconds',
      desc: 'Sign up free and discover how much time you can save with AI document scanning.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Produkt',
    title: 'Skanimi AI i dokumenteve: merrni kontabilizimin në 10 sekonda',
    publishDate: '15 mars 2026',
    readTime: '8 min lexim',
    heroAlt: 'Skanimi AI i faturave në Facturino — digjitalizimi i dokumenteve për kontabilitet në Maqedoni',
    intro: 'Çdo kontabilist dhe pronar i biznesit të vogël në Maqedoni e njeh problemin: grumbuj faturash letre, futje manuale e të dhënave, gabime në transkriptim dhe dokumente të humbura. Koha mesatare për përpunimin manual të një fature të vetme është 15 minuta — shumëzojeni atë me qindra fatura në muaj dhe merrni dhjetëra orë të shpenzuara në punë mekanike. Facturino e zgjidh këtë problem me AI Document Hub — skanoni një faturë dhe merrni kontabilizim të plotë në vetëm 10 sekonda. Pa futje manuale, pa gabime, pa dokumente të humbura.',
    sections: [
      {
        title: 'Si funksionon skaneri AI i dokumenteve',
        content: 'Skaneri AI në Facturino përdor inteligjencë artificiale të avancuar për të transformuar dokumentet tuaja letrore dhe dixhitale në regjistrime kontabël të strukturuara. I gjithë procesi është dizajnuar të jetë i thjeshtë, i shpejtë dhe i saktë — edhe për përdoruesit pa përvojë teknike. Ja procesi hap pas hapi i skanimit dhe kontabilizimit automatik:',
        items: null,
        steps: [
          { step: 'Ngarkoni ose fotografoni një dokument', desc: 'Ngarkoni një PDF, imazh ose foto të faturës direkt në Facturino. Mund të përdorni kompjuter, tablet ose telefon mobil. Formatet e mbështetura përfshijnë PDF, JPG, PNG dhe HEIC. Nuk ka kufizim në madhësi — sistemi përpunon edhe dokumente me shumë faqe.' },
          { step: 'Klasifikimi AI i llojit të dokumentit', desc: 'Inteligjenca artificiale njeh automatikisht llojin e dokumentit. Sistemi dallon 7 lloje: faturë hyrëse, faturë dalëse, kupon fiskal, ekstrakt bankar, formular tatimor, kontratë dhe dokument tjetër. Saktësia e klasifikimit kalon 95% falë modeleve të trajnuara me dokumente maqedonase.' },
          { step: 'Nxjerrja e të dhënave kyçe', desc: 'AI analizon përmbajtjen e dokumentit dhe nxjerr automatikisht të gjitha fushat relevante: shumën, datën, normën e TVSH-së, furnizuesin/klientin, numrin e faturës, EDB dhe EMBS. Sistemi punon me dokumente si cirilike ashtu edhe latine dhe njeh formate të ndryshme të faturave maqedonase.' },
          { step: 'Rishikimi dhe verifikimi', desc: 'Të dhënat e nxjerra shfaqen në një ekran rishikimi të qartë ku mund t\'i kontrolloni dhe korrigjoni nëse nevojitet. Fushat që AI i njohu me besueshmëri të lartë shënohen me jeshile, ndërsa ato me besueshmëri më të ulët me të verdhë — që të dini saktësisht ku të kushtoni vëmendje. Ky është hapi vendimtar i verifikimit njerëzor.' },
          { step: 'Konfirmim me një klik', desc: 'Pasi t\'i konfirmoni të dhënat, një klik i vetëm në butonin "Konfirmo" krijon një regjistrim kontabël të plotë. Fatura regjistrohet, furnizuesi shtohet (nëse është i ri), TVSH llogaritet dhe transaksioni regjistrohet. I gjithë procesi mesatarisht zgjat 10 sekonda nga ngarkimi deri te kontabilizimi i përfunduar.' },
          { step: 'Entiteti u krijua — gati!', desc: 'Regjistrimi i ri është menjëherë i disponueshëm në sistem. Mund të jetë faturë hyrëse, shpenzim, transaksion bankar ose entitet tjetër — varësisht nga lloji i dokumentit të skanuar. Dokumenti origjinal bashkëngjitet automatikisht dhe lidhet me regjistrimin e krijuar për referencë të ardhshme auditimi.' },
        ],
      },
      {
        title: 'Çfarë mund të skanohet me Facturino',
        content: 'AI Document Hub nuk kufizohet vetëm te faturat. Sistemi mund të përpunojë një gamë të gjerë dokumentesh biznesi që bizneset dhe kontabilistët maqedonas i hasin çdo ditë. 7 lloje dokumentesh mbështeten për përpunim automatik dhe kontabilizim:',
        items: [
          'Fatura hyrëse nga furnizuesit — vendas dhe të huaj, letrore dhe elektronike',
          'Fatura dalëse — verifikimi dhe kontabilizimi i faturave tuaja',
          'Kupona fiskalë — kupona nga dyqanet, restorantet, pikat e karburantit',
          'Ekstrakte bankare — njohje automatike e transaksioneve nga ekstrakti bankar',
          'Formularë tatimorë — deklarata TVSH, formularë tatimorë vjetorë, dokumente UJP',
          'Kontrata — nxjerrja e kushteve kyçe, afateve dhe shumave nga kontratat',
          'Dokumente të tjera biznesi — oferta, porosi, fletëshoqërimi, fatura pro-forma',
        ],
        steps: null,
      },
      {
        title: 'Pse skanimi AI është më i mirë se futja manuale',
        content: 'Futja manuale e faturave është e ngadaltë, e prirur ndaj gabimeve dhe e kushtueshme. Skanimi AI eliminon secilën nga këto probleme. Ja një krahasim i drejtpërdrejtë midis kontabilizimit manual tradicional dhe kontabilizimit automatik AI në Facturino:',
        items: [
          'Shpejtësia: 10 sekonda me AI kundrejt 15 minutave manualisht — 90 herë më shpejt',
          'Saktësia: AI + verifikimi njerëzor eliminon pothuajse të gjitha gabimet, kundrejt 3-5% normë gabimesh me futje manuale',
          'Kostoja: Skanimi i automatizuar kursen 20-40 orë në muaj për një ndërmarrje të mesme',
          'Konsistenca: AI gjithmonë aplikon të njëjtin format dhe rregulla, pa variacione',
          'Gjurmueshmëria: Çdo dokument i skanuar arkivohet automatikisht dhe lidhet me regjistrimin',
          'Aksesueshmëria: Skanoni nga kudo — zyra, shtëpia ose në rrugë përmes celularit',
        ],
        steps: null,
      },
      {
        title: 'Siguria dhe mbrojtja e dokumenteve',
        content: 'E dimë që dokumentet financiare përmbajnë informacione të ndjeshme biznesi. Prandaj siguria është e integruar në çdo aspekt të AI Document Hub. Të gjitha dokumentet enkriptohen gjatë transferimit (TLS 1.3) dhe gjatë ruajtjes (AES-256). Të dhënat tuaja ruhen në serverë në Bashkimin Evropian, në përputhje me rregulloret GDPR. Qasja te dokumentet kontrollohet përmes autentikimit me shumë shtresa dhe regjistrave të auditimit. Asnjë dokument nuk ndahet me palë të treta — përpunimi AI ndodh në infrastrukturën tonë private, jo përmes API-ve publike. Auditimet e rregullta të sigurisë sigurojnë që të dhënat tuaja mbrohen sipas standardeve më të fundit.',
        items: [
          'Enkriptim nga skaji në skaj (TLS 1.3 + AES-256)',
          'Të dhëna të ruajtura në BE, në përputhje me GDPR',
          'Infrastrukturë private AI — pa ndarje me palë të treta',
          'Regjistra auditimi për çdo qasje te dokumenti',
          'Backup automatik dhe rikuperim nga fatkeqësitë',
        ],
        steps: null,
      },
      {
        title: 'Filloni me skanimin AI të dokumenteve',
        content: 'Regjistrohuni falas në Facturino dhe provoni AI Document Hub me faturat tuaja. Plani falas përfshin mundësinë e skanimit, ndërsa planet Standard dhe Business ju japin skanim të pakufizuar dhe veçori të avancuara. Nuk nevojitet kartë krediti për të filluar. Nëse jeni kontabilist, programi ynë i partneritetit ju jep qasje te skanimi AI për të gjithë klientët tuaj nga një konzolë e vetme — falas për partnerët.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
      { slug: 'facturino-vs-excel', title: 'Facturino kundrejt Excel: pse të kaloni' },
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve: udhëzues për bizneset' },
    ],
    cta: {
      title: 'Skanoni faturën tuaj të parë në 10 sekonda',
      desc: 'Regjistrohuni falas dhe zbuloni sa kohë mund të kurseni me skanimin AI të dokumenteve.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Urun',
    title: 'AI Belge Tarayici: 10 Saniyede Muhasebe Kaydi Alin',
    publishDate: '15 Mart 2026',
    readTime: '8 dk okuma',
    heroAlt: 'Facturino\'da AI fatura tarama — Makedonya\'da muhasebe icin belge dijitallestirme',
    intro: 'Makedonya\'daki her muhasebeci ve kucuk isletme sahibi bu sorunu bilir: yiginla kagit fatura, manuel veri girisi, kopyalama hatalari ve kayip belgeler. Tek bir faturayi manuel olarak isleme suresi ortalama 15 dakikadir — bunu ayda yuzlerce faturayla carpin ve mekanik ise harcanan onlarca saat elde edin. Facturino bu sorunu AI Document Hub ile cozer — bir faturayi tarayin ve sadece 10 saniyede eksiksiz muhasebe kaydi alin. Manuel giris yok, hata yok, kayip belge yok.',
    sections: [
      {
        title: 'AI Belge Tarayici Nasil Calisir',
        content: 'Facturino\'daki AI tarayici, kagit ve dijital belgelerinizi yapilandirilmis muhasebe kayitlarina donusturmek icin gelismis yapay zeka kullanir. Tum surec basit, hizli ve dogru olacak sekilde tasarlanmistir — teknik deneyimi olmayan kullanicilar icin bile. Iste adim adim tarama ve otomatik muhasebe sureci:',
        items: null,
        steps: [
          { step: 'Belge yukleyin veya fotograflayin', desc: 'PDF, goruntu veya fatura fotografini dogrudan Facturino\'ya yukleyin. Bilgisayar, tablet veya cep telefonu kullanabilirsiniz. Desteklenen formatlar PDF, JPG, PNG ve HEIC\'dir. Boyut siniri yoktur — sistem cok sayfalik belgeleri de isler.' },
          { step: 'AI Belge Tipi Siniflandirmasi', desc: 'Yapay zeka belge tipini otomatik olarak tanir. Sistem 7 tipi ayirt eder: gelen fatura, giden fatura, mali fis, banka ekstresi, vergi formu, sozlesme ve diger belge. Siniflandirma dogrulugu, Makedon belgeleri uzerinde egitilmis modeller sayesinde %95\'i asar.' },
          { step: 'Anahtar Veri Cikarma', desc: 'AI belge icerigini analiz eder ve tum ilgili alanlari otomatik olarak cikarir: tutar, tarih, KDV orani, tedarikci/musteri, fatura numarasi, EDB ve EMBS. Sistem hem Kiril hem de Latin belgeleriyle calisir ve cesitli Makedon fatura formatlarini tanir.' },
          { step: 'Inceleme ve Dogrulama', desc: 'Cikarilan veriler, kontrol edebileceginiz ve gerekirse duzeltebileceginiz net bir inceleme ekraninda goruntulenir. AI\'nin yuksek guvenle tanidigi alanlar yesil, daha dusuk guvenli olanlar sari ile isaretlenir — boylece tam olarak nereye dikkat etmeniz gerektigini bilirsiniz. Bu, kritik insan dogrulama adimidir.' },
          { step: 'Tek Tikla Onay', desc: 'Verileri onayladiktan sonra, "Onayla" dugmesine tek bir tik eksiksiz bir muhasebe kaydi olusturur. Fatura kaydedilir, tedarikci eklenir (yeniyse), KDV hesaplanir ve islem kaydedilir. Tum surec yuklemeden tamamlanmis muhasebeye kadar ortalama 10 saniye surer.' },
          { step: 'Varlik Olusturuldu — Tamam!', desc: 'Yeni kayit sistemde hemen kullanilabilir. Taranan belge turune bagli olarak gelen fatura, gider, banka islemi veya baska bir varlik olabilir. Orijinal belge otomatik olarak eklenir ve gelecekteki denetim referansi icin olusturulan kayitla iliskilendirilir.' },
        ],
      },
      {
        title: 'Facturino ile Neler Taranabilir',
        content: 'AI Document Hub sadece faturalarla sinirli degildir. Sistem, Makedon isletmelerin ve muhasebecilerin gunluk olarak karsilastigi genis bir yelpazedeki is belgelerini isleyebilir. Otomatik isleme ve muhasebe icin 7 belge turu desteklenmektedir:',
        items: [
          'Tedarikci gelen faturalari — yurt ici ve yurt disi, kagit ve elektronik',
          'Giden faturalar — kendi faturalarinizin dogrulanmasi ve muhasebesi',
          'Mali fisler — magazalardan, restoranlardan, benzin istasyonlarindan fisler',
          'Banka ekstreleri — banka ekstresinden otomatik islem tanima',
          'Vergi formlari — KDV beyanameleri, yillik vergi formlari, UJP belgeleri',
          'Sozlesmeler — sozlesmelerden anahtar kosullarin, son tarihlerin ve tutarlarin cikarilmasi',
          'Diger is belgeleri — teklifler, siparisler, irsaliyeler, proforma faturalar',
        ],
        steps: null,
      },
      {
        title: 'Neden AI Tarama Manuel Veri Girisinden Ustun',
        content: 'Manuel fatura girisi yavas, hataya acik ve pahalidir. AI tarama bu sorunlarin her birini ortadan kaldirir. Iste geleneksel manuel muhasebe ile Facturino\'daki otomatik AI muhasebe arasinda dogrudan bir karsilastirma:',
        items: [
          'Hiz: AI ile 10 saniye, manuelle 15 dakika — 90 kat daha hizli',
          'Dogruluk: AI + insan dogrulamasi neredeyse tum hatalari ortadan kaldirir, manuel giriste %3-5 hata oranina karsi',
          'Maliyet: Otomatik tarama orta olcekli bir isletme icin ayda 20-40 saat tasarruf saglar',
          'Tutarlilik: AI her zaman ayni formati ve kurallari uygular, varyasyon olmadan',
          'Izlenebilirlik: Taranan her belge otomatik olarak arsivlenir ve kayitla iliskilendirilir',
          'Erisilebilirlik: Her yerden tarayin — ofis, ev veya yolda mobil uzerinden',
        ],
        steps: null,
      },
      {
        title: 'Belge Guvenligi ve Korumasi',
        content: 'Finansal belgelerin hassas is bilgileri icerdigini biliyoruz. Bu nedenle guvenlik, AI Document Hub\'in her yonune entegre edilmistir. Tum belgeler aktarim sirasinda (TLS 1.3) ve depolama sirasinda (AES-256) sifrelenir. Verileriniz GDPR duzenlemelerine uygun olarak Avrupa Birligi\'ndeki sunucularda depolanir. Belgelere erisim, cok katmanli kimlik dogrulama ve denetim gunlukleriyle kontrol edilir. Hicbir belge ucuncu taraflarla paylasilmaz — AI isleme, genel API\'ler araciligiyla degil, ozel altyapimizda gerceklesir. Duzenli guvenlik denetimleri verilerinizin en son standartlara gore korunmasini saglar.',
        items: [
          'Uctan uca sifreleme (TLS 1.3 + AES-256)',
          'Veriler AB\'de depolaniyor, GDPR uyumlu',
          'Ozel AI altyapisi — ucuncu taraflarla paylasim yok',
          'Her belge erisimi icin denetim gunlukleri',
          'Otomatik yedekleme ve felaket kurtarma',
        ],
        steps: null,
      },
      {
        title: 'AI Belge Tarama ile Baslayin',
        content: 'Facturino\'ya ucretsiz kaydolun ve AI Document Hub\'i faturalarinizla deneyin. Ucretsiz plan tarama ozelligini icerir, Standard ve Business planlari ise sinirsiz tarama ve gelismis ozellikler sunar. Baslamak icin kredi karti gerekmez. Muhasebeciyseniz, ortaklik programimiz tum musterileriniz icin tek bir konsoldan AI taramaya erisim saglar — ortaklar icin ucretsiz.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili makaleler',
    related: [
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs geleneksel muhasebe' },
      { slug: 'facturino-vs-excel', title: 'Facturino ve Excel: neden gecmelisiniz' },
      { slug: 'upravljanje-so-rashodi', title: 'Gider yonetimi: isletmeler icin rehber' },
    ],
    cta: {
      title: 'Ilk Faturanizi 10 Saniyede Tarayin',
      desc: 'Ucretsiz kaydolun ve AI belge tarama ile ne kadar zaman tasarruf edebileceginizi kesfedin.',
      button: 'Ucretsiz basla',
    },
  },
} as const

export default async function AiSkenerDokumentiPage({
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

      {/* HERO IMAGE */}
      <div className="container max-w-3xl mx-auto px-4 sm:px-6 mb-8">
        <div className="rounded-2xl overflow-hidden shadow-lg">
          <Image
            src="/assets/images/blog/blog_ai_document_scanner.png"
            alt={t.heroAlt}
            width={1200}
            height={630}
            className="w-full h-auto"
            priority
          />
        </div>
      </div>

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
