import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/otvoranje-firma-mk', {
    title: {
      mk: 'Како да отворите фирма во Македонија: Комплетен водич | Facturino',
      en: 'How to Register a Company in Macedonia: Complete Guide | Facturino',
      sq: 'Si të hapni një kompani në Maqedoni: Udhëzues i plotë | Facturino',
      tr: 'Makedonya\'da şirket nasıl kurulur: Kapsamlı rehber | Facturino',
    },
    description: {
      mk: 'Детален водич за отворање фирма во Македонија: типови на друштва, регистрација во ЦРСМ, документи, ЕДБ, банкарска сметка и ДДВ регистрација.',
      en: 'Detailed guide to registering a company in Macedonia: company types, Central Registry registration, documents, tax number, bank account, and VAT registration.',
      sq: 'Udhëzues i detajuar për hapjen e kompanisë në Maqedoni: llojet e kompanive, regjistrimi në RQRM, dokumentet, numri tatimor, llogaria bankare dhe regjistrimi për TVSH.',
      tr: 'Makedonya\'da şirket kurma rehberi: şirket türleri, Merkez Sicil kaydı, belgeler, vergi numarası, banka hesabı ve KDV kaydı.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Како да отворите фирма во Македонија: Комплетен водич',
    publishDate: '11 февруари 2026',
    readTime: '9 мин читање',
    intro: 'Отворањето фирма во Македонија е релативно едноставен процес кој може да се заврши за неколку дена. Но без правилна подготовка, може да наидете на пречки кои ќе ве чинат и време и пари. Овој водич ве води чекор по чекор низ целиот процес — од избор на тип на друштво, преку регистрација во Централниот регистар, до добивање даночен број и отворање банкарска сметка. На крајот, ќе знаете точно што ви треба за да го стартувате вашиот бизнис.',
    sections: [
      {
        title: 'Типови на трговски друштва',
        content: 'Пред да започнете со регистрација, треба да одлучите каков тип на друштво ви одговара. Во Македонија најчесто се регистрираат следните форми:',
        items: [
          'ДООЕЛ (Друштво со ограничена одговорност на едно лице) — Најпопуларниот избор за мали бизниси. Еден основач, минимален основачки капитал од 5.000 EUR (во денарска противвредност). Одговорноста е ограничена на уплатениот капитал.',
          'ДОО (Друштво со ограничена одговорност) — За двајца или повеќе основачи. Ист минимален капитал, но со договор за управување меѓу содружниците. Идеално за партнерства.',
          'ТП (Трговец поединец) — За физички лица кои сакаат да тргуваат под свое име. Нема минимален капитал, но основачот одговара со целиот свој имот. Погоден за фриленсери и мали услужни дејности.',
          'АД (Акционерско друштво) — За поголеми бизниси. Минимален капитал од 25.000 EUR. Ретко се користи за стартапи, но е потребен за одредени дејности (осигурување, банки).',
        ],
        steps: null,
      },
      {
        title: 'Чекор по чекор: Регистрација на фирма',
        content: null,
        items: null,
        steps: [
          { step: 'Изберете име на фирма', desc: 'Проверете ја достапноста на името на порталот на Централниот регистар (crm.com.mk). Името мора да биде уникатно и не смее да биде слично на постоечка фирма. Може да резервирате име за 60 дена пред регистрацијата.' },
          { step: 'Подгответе ги потребните документи', desc: 'За ДООЕЛ ви требаат: лична карта или пасош, изјава за основање (нотарски заверена), одлука за именување на управител, уплата на основачки капитал на привремена сметка во банка, и адреса на седиште.' },
          { step: 'Поднесете пријава во Централен регистар (ЦРСМ)', desc: 'Пријавата може да се поднесе електронски преку e-Registar или лично во канцеларија на ЦРСМ. Електронската регистрација е побрза и поевтина. Таксата за ДООЕЛ е околу 2.300 МКД (37 EUR).' },
          { step: 'Добијте решение за регистрација', desc: 'ЦРСМ го обработува барањето во рок од 4 часа (електронски) до 1 ден (физички). По одобрувањето добивате ЕМБС (единствен матичен број на субјект) — ова е идентификацискиот број на фирмата.' },
          { step: 'Регистрирајте се во УЈП и добијте ЕДБ', desc: 'Управата за јавни приходи автоматски добива известување од ЦРСМ, но мора да поднесете пријава за регистрација на даночен обврзник и да добиете ЕДБ (единствен даночен број). Ова трае 1-3 работни дена.' },
          { step: 'Отворете банкарска сметка', desc: 'Со решението за регистрација и ЕДБ, отворете деловна сметка во банка. Споредете понуди — провизиите се разликуваат значително. Основачкиот капитал од привремената сметка се пренесува на деловната.' },
          { step: 'Регистрирајте се за ДДВ (ако е потребно)', desc: 'Ако очекувате годишен промет над 2.000.000 МКД, мора да се регистрирате за ДДВ. Можете и доброволно да се регистрирате — тоа е корисно ако вашите клиенти се ДДВ обврзници, бидејќи можете да си го вратите влезниот ДДВ.' },
        ],
      },
      {
        title: 'Трошоци за отворање фирма',
        content: 'Вкупните трошоци за отворање ДООЕЛ во Македонија се релативно ниски во споредба со другите земји. Еве преглед на главните трошоци:',
        items: [
          'Нотарска заверка на изјава за основање: 1.500–3.000 МКД (25–50 EUR).',
          'Такса за регистрација во ЦРСМ: 2.300 МКД (37 EUR) електронски, 3.400 МКД (55 EUR) физички.',
          'Основачки капитал: 5.000 EUR (во денарска противвредност) — ова не е трошок, туку влог кој останува на сметката на фирмата.',
          'Печат на фирмата: 500–1.500 МКД (8–25 EUR).',
          'Отворање банкарска сметка: Бесплатно во повеќето банки, но со месечни провизии од 200–500 МКД.',
          'Вкупно (без основачки капитал): Околу 5.000–8.000 МКД (80–130 EUR).',
        ],
        steps: null,
      },
      {
        title: 'Први обврски по регистрацијата',
        content: 'Откако ќе ја регистрирате фирмата, имате неколку непосредни обврски кои мора да ги исполните:',
        items: [
          'Пријавете се како вработен (управител) во Агенцијата за вработување и поднесете МПИН пријава за месечни придонеси.',
          'Воспоставете сметководствена евиденција — водете книговодство од првиот ден. Одлучете дали ќе ангажирате сметководител или ќе користите софтвер.',
          'Издавајте фактури за секоја услуга или продажба — секоја трансакција мора да биде документирана.',
          'Набавете фискален апарат ако продавате на физички лица — за Б2Ц трансакции фискална каса е задолжителна.',
          'Поднесувајте месечни МПИН пријави до 15-ти секој месец за претходниот месец.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ви помага од првиот ден',
        content: 'Facturino е создаден за нови бизниси во Македонија. Веднаш по регистрацијата можете да почнете да издавате професионални фактури, да ги следите расходите и да генерирате извештаи за УЈП. Нема потреба од сметководствено знаење — системот е интуитивен и ве води низ секој чекор.',
        items: [
          'Издавајте фактури со македонски формат и сите задолжителни полиња за УЈП.',
          'Автоматска пресметка на ДДВ на секоја фактура.',
          'Следење на приходи и расходи во реално време.',
          'Извештаи за данок на добивка, ДДВ и МПИН.',
          'Бесплатен почетен план — без ризик, без кредитна картичка.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Започнете го вашиот бизнис правилно',
      desc: 'Facturino е вашиот партнер од првиот ден. Издавајте фактури, следете расходи и бидете во тек со даночните обврски.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'How to Register a Company in Macedonia: Complete Guide',
    publishDate: 'February 11, 2026',
    readTime: '9 min read',
    intro: 'Registering a company in Macedonia is a relatively straightforward process that can be completed in a few days. But without proper preparation, you may encounter obstacles that cost you both time and money. This guide walks you through the entire process step by step — from choosing the company type, through Central Registry registration, to obtaining a tax number and opening a bank account. By the end, you will know exactly what you need to launch your business.',
    sections: [
      {
        title: 'Types of commercial entities',
        content: 'Before starting the registration process, you need to decide which type of entity suits your needs. The most commonly registered forms in Macedonia are:',
        items: [
          'DOOEL (Single-member LLC) — The most popular choice for small businesses. One founder, minimum share capital of EUR 5,000 (in MKD equivalent). Liability is limited to the paid-in capital.',
          'DOO (Multi-member LLC) — For two or more founders. Same minimum capital, but with a management agreement between partners. Ideal for partnerships.',
          'TP (Sole Trader) — For individuals who want to trade under their own name. No minimum capital, but the founder is liable with all personal assets. Suitable for freelancers and small service businesses.',
          'AD (Joint-Stock Company) — For larger businesses. Minimum capital of EUR 25,000. Rarely used for startups, but required for certain activities (insurance, banking).',
        ],
        steps: null,
      },
      {
        title: 'Step by step: Company registration',
        content: null,
        items: null,
        steps: [
          { step: 'Choose a company name', desc: 'Check name availability on the Central Registry portal (crm.com.mk). The name must be unique and must not be similar to an existing company. You can reserve a name for 60 days before registration.' },
          { step: 'Prepare the required documents', desc: 'For a DOOEL you need: ID card or passport, founding statement (notarized), decision on appointing a manager, payment of share capital to a temporary bank account, and registered office address.' },
          { step: 'Submit the application to the Central Registry (CRMS)', desc: 'The application can be submitted electronically via e-Registar or in person at a CRMS office. Electronic registration is faster and cheaper. The fee for a DOOEL is approximately 2,300 MKD (EUR 37).' },
          { step: 'Receive the registration decision', desc: 'CRMS processes the application within 4 hours (electronic) to 1 day (in-person). Upon approval, you receive an EMBS (unique entity identification number) — this is your company ID number.' },
          { step: 'Register with UJP and obtain an EDB', desc: 'The Public Revenue Office automatically receives notification from CRMS, but you must submit a taxpayer registration form and obtain an EDB (unique tax number). This takes 1-3 business days.' },
          { step: 'Open a bank account', desc: 'With the registration decision and EDB, open a business bank account. Compare offers — fees vary significantly. The share capital from the temporary account is transferred to the business account.' },
          { step: 'Register for VAT (if needed)', desc: 'If you expect annual turnover above 2,000,000 MKD, you must register for VAT. You can also register voluntarily — this is beneficial if your clients are VAT-registered, since you can reclaim input VAT.' },
        ],
      },
      {
        title: 'Costs of registering a company',
        content: 'The total cost of registering a DOOEL in Macedonia is relatively low compared to other countries. Here is an overview of the main costs:',
        items: [
          'Notarial certification of founding statement: 1,500-3,000 MKD (EUR 25-50).',
          'CRMS registration fee: 2,300 MKD (EUR 37) electronic, 3,400 MKD (EUR 55) in-person.',
          'Share capital: EUR 5,000 (in MKD equivalent) — this is not a cost but a deposit that remains in the company account.',
          'Company stamp: 500-1,500 MKD (EUR 8-25).',
          'Opening a bank account: Free at most banks, but with monthly fees of 200-500 MKD.',
          'Total (excluding share capital): Approximately 5,000-8,000 MKD (EUR 80-130).',
        ],
        steps: null,
      },
      {
        title: 'First obligations after registration',
        content: 'Once you register your company, you have several immediate obligations that must be fulfilled:',
        items: [
          'Register yourself as an employee (manager) with the Employment Agency and submit an MPIN return for monthly contributions.',
          'Establish accounting records — keep books from day one. Decide whether to hire an accountant or use software.',
          'Issue invoices for every service or sale — every transaction must be documented.',
          'Obtain a fiscal device if selling to individuals — a fiscal register is mandatory for B2C transactions.',
          'Submit monthly MPIN returns by the 15th of each month for the previous month.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino helps you from day one',
        content: 'Facturino was built for new businesses in Macedonia. Immediately after registration, you can start issuing professional invoices, tracking expenses, and generating reports for UJP. No accounting knowledge required — the system is intuitive and guides you through every step.',
        items: [
          'Issue invoices in Macedonian format with all mandatory fields required by UJP.',
          'Automatic VAT calculation on every invoice.',
          'Real-time income and expense tracking.',
          'Reports for corporate income tax, VAT, and MPIN.',
          'Free starter plan — no risk, no credit card required.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Start your business the right way',
      desc: 'Facturino is your partner from day one. Issue invoices, track expenses, and stay on top of your tax obligations.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Si të hapni një kompani në Maqedoni: Udhëzues i plotë',
    publishDate: '11 shkurt 2026',
    readTime: '9 min lexim',
    intro: 'Hapja e një kompanie në Maqedoni është një proces relativisht i thjeshtë që mund të përfundojë brenda disa ditëve. Por pa përgatitje të duhur, mund të hasni pengesa që ju kushtojnë kohë dhe para. Ky udhëzues ju çon hap pas hapi nëpër të gjithë procesin — nga zgjedhja e llojit të kompanisë, përmes regjistrimit në Regjistrin Qendror, deri te marrja e numrit tatimor dhe hapja e llogarisë bankare. Në fund, do të dini saktësisht çfarë ju nevojitet për të nisur biznesin tuaj.',
    sections: [
      {
        title: 'Llojet e subjekteve tregtare',
        content: 'Para se të filloni procesin e regjistrimit, duhet të vendosni cili lloj subjekti ju përshtatet. Format më të zakonshme të regjistruara në Maqedoni janë:',
        items: [
          'DOOEL (SHPK me një anëtar) — Zgjedhja më e njohur për bizneset e vogla. Një themelues, kapital minimal prej 5.000 EUR (në ekuivalent MKD). Përgjegjësia kufizohet në kapitalin e paguar.',
          'DOO (SHPK me shumë anëtarë) — Për dy ose më shumë themelues. I njëjti kapital minimal, por me marrëveshje menaxhimi midis partnerëve. Ideal për partneritete.',
          'TP (Tregtar individual) — Për individë që duan të tregtojnë nën emrin e tyre. Pa kapital minimal, por themeluesi përgjigjet me gjithë pasurinë personale. I përshtatshëm për punëtorë të pavarur dhe biznese të vogla shërbimi.',
          'AD (Shoqëri aksionare) — Për biznese më të mëdha. Kapital minimal prej 25.000 EUR. Përdoret rrallë për startup-e, por nevojitet për aktivitete të caktuara (sigurime, banka).',
        ],
        steps: null,
      },
      {
        title: 'Hap pas hapi: Regjistrimi i kompanisë',
        content: null,
        items: null,
        steps: [
          { step: 'Zgjidhni emrin e kompanisë', desc: 'Kontrolloni disponueshmërinë e emrit në portalin e Regjistrit Qendror (crm.com.mk). Emri duhet të jetë unik dhe nuk duhet të jetë i ngjashëm me ndonjë kompani ekzistuese. Mund ta rezervoni emrin për 60 ditë para regjistrimit.' },
          { step: 'Përgatitni dokumentet e nevojshme', desc: 'Për DOOEL ju nevojiten: letërnjoftim ose pasaportë, deklaratë themelimi (e noterizuar), vendim për emërimin e menaxherit, pagesa e kapitalit në llogari të përkohshme bankare dhe adresa e selisë.' },
          { step: 'Dorëzoni aplikimin në Regjistrin Qendror (RQRM)', desc: 'Aplikimi mund të dorëzohet elektronikisht përmes e-Registar ose personalisht në zyrën e RQRM. Regjistrimi elektronik është më i shpejtë dhe më i lirë. Tarifa për DOOEL është rreth 2.300 MKD (37 EUR).' },
          { step: 'Merrni vendimin e regjistrimit', desc: 'RQRM e përpunon kërkesën brenda 4 orëve (elektronik) deri në 1 ditë (personalisht). Pas miratimit merrni EMBS (numrin unik të identifikimit të subjektit) — ky është numri i identifikimit të kompanisë.' },
          { step: 'Regjistrohuni në UJP dhe merrni EDB', desc: 'Zyra e të Ardhurave Publike merr automatikisht njoftim nga RQRM, por duhet të dorëzoni formularin e regjistrimit të tatimpaguesit dhe të merrni EDB (numrin unik tatimor). Kjo zgjat 1-3 ditë pune.' },
          { step: 'Hapni llogari bankare', desc: 'Me vendimin e regjistrimit dhe EDB, hapni llogari bankare biznesi. Krahasoni ofertat — tarifat ndryshojnë ndjeshëm. Kapitali nga llogaria e përkohshme transferohet në llogarinë e biznesit.' },
          { step: 'Regjistrohuni për TVSH (nëse nevojitet)', desc: 'Nëse prisni qarkullim vjetor mbi 2.000.000 MKD, duhet të regjistroheni për TVSH. Mund të regjistroheni edhe vullnetarisht — kjo është e dobishme nëse klientët tuaj janë të regjistruar për TVSH, pasi mund të riktheni TVSH-në hyrëse.' },
        ],
      },
      {
        title: 'Kostot e regjistrimit të kompanisë',
        content: 'Kostoja totale e regjistrimit të një DOOEL në Maqedoni është relativisht e ulët krahasuar me vendet e tjera. Ja një përmbledhje e kostove kryesore:',
        items: [
          'Noterizimi i deklaratës së themelimit: 1.500-3.000 MKD (25-50 EUR).',
          'Tarifa e regjistrimit RQRM: 2.300 MKD (37 EUR) elektronik, 3.400 MKD (55 EUR) personalisht.',
          'Kapitali themeltar: 5.000 EUR (në ekuivalent MKD) — kjo nuk është kosto por depozitë që mbetet në llogarinë e kompanisë.',
          'Vula e kompanisë: 500-1.500 MKD (8-25 EUR).',
          'Hapja e llogarisë bankare: Falas në shumicën e bankave, por me tarifa mujore 200-500 MKD.',
          'Totali (pa kapitalin themeltar): Rreth 5.000-8.000 MKD (80-130 EUR).',
        ],
        steps: null,
      },
      {
        title: 'Detyrimet e para pas regjistrimit',
        content: 'Pasi ta regjistroni kompaninë, keni disa detyrime të menjëhershme që duhet t\'i plotësoni:',
        items: [
          'Regjistrohuni si punonjës (menaxher) në Agjencinë e Punësimit dhe dorëzoni deklaratën MPIN për kontribute mujore.',
          'Vendosni evidencën kontabël — mbani librat nga dita e parë. Vendosni nëse do të angazhoni kontabilist ose do të përdorni softuer.',
          'Lëshoni fatura për çdo shërbim ose shitje — çdo transaksion duhet të dokumentohet.',
          'Siguroni pajisje fiskale nëse shisni te individët — arka fiskale është e detyrueshme për transaksione B2C.',
          'Dorëzoni deklarata mujore MPIN deri më 15 të çdo muaji për muajin e kaluar.',
        ],
        steps: null,
      },
      {
        title: 'Si ju ndihmon Facturino nga dita e parë',
        content: 'Facturino u ndërtua për bizneset e reja në Maqedoni. Menjëherë pas regjistrimit mund të filloni të lëshoni fatura profesionale, të ndiqni shpenzimet dhe të gjeneroni raporte për UJP. Nuk nevojiten njohuri kontabiliteti — sistemi është intuitiv dhe ju udhëzon nëpër çdo hap.',
        items: [
          'Lëshoni fatura në format maqedonas me të gjitha fushat e detyrueshme nga UJP.',
          'Llogaritje automatike e TVSH-së në çdo faturë.',
          'Ndjekje e të ardhurave dhe shpenzimeve në kohë reale.',
          'Raporte për tatimin mbi fitimin, TVSH dhe MPIN.',
          'Plan fillestar falas — pa rrezik, pa kartë krediti.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'Niseni biznesin tuaj si duhet',
      desc: 'Facturino është partneri juaj nga dita e parë. Lëshoni fatura, ndiqni shpenzimet dhe qëndroni në kontroll me detyrimet tatimore.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Makedonya\'da şirket nasıl kurulur: Kapsamlı rehber',
    publishDate: '11 Şubat 2026',
    readTime: '9 dk okuma',
    intro: 'Makedonya\'da şirket kurmak, birkaç gün içinde tamamlanabilecek nispeten basit bir süreçtir. Ancak doğru hazırlık olmadan, hem zamana hem de paraya mal olabilecek engellerle karşılaşabilirsiniz. Bu rehber, şirket türü seçiminden Merkez Sicil kaydına, vergi numarası almadan banka hesabı açmaya kadar tüm süreçte sizi adım adım yönlendirir. Sonunda, işletmenizi başlatmak için tam olarak neye ihtiyacınız olduğunu bileceksiniz.',
    sections: [
      {
        title: 'Ticari şirket türleri',
        content: 'Kayıt sürecine başlamadan önce, hangi şirket türünün ihtiyaçlarınıza uygun olduğuna karar vermeniz gerekir. Makedonya\'da en yaygın kayıtlı formlar şunlardır:',
        items: [
          'DOOEL (Tek ortaklı limited şirket) — Küçük işletmeler için en popüler seçenek. Bir kurucu, minimum 5.000 EUR (MKD karşılığı) sermaye. Sorumluluk ödenen sermaye ile sınırlıdır.',
          'DOO (Çok ortaklı limited şirket) — İki veya daha fazla kurucu için. Aynı minimum sermaye, ancak ortaklar arasında yönetim sözleşmesi ile. Ortaklıklar için ideal.',
          'TP (Şahıs şirketi) — Kendi adıyla ticaret yapmak isteyen bireyler için. Minimum sermaye yok, ancak kurucu tüm kişisel varlıklarıyla sorumludur. Serbest çalışanlar ve küçük hizmet işletmeleri için uygundur.',
          'AD (Anonim şirket) — Daha büyük işletmeler için. Minimum 25.000 EUR sermaye. Startup\'lar için nadiren kullanılır, ancak belirli faaliyetler (sigorta, bankacılık) için gereklidir.',
        ],
        steps: null,
      },
      {
        title: 'Adım adım: Şirket kaydı',
        content: null,
        items: null,
        steps: [
          { step: 'Şirket adı seçin', desc: 'Merkez Sicil portalında (crm.com.mk) ad müsaitliğini kontrol edin. Ad benzersiz olmalı ve mevcut bir şirkete benzememelidir. Kayıttan 60 gün önce ad rezerve edebilirsiniz.' },
          { step: 'Gerekli belgeleri hazırlayın', desc: 'DOOEL için gerekenler: kimlik kartı veya pasaport, kuruluş beyanı (noter tasdikli), müdür atama kararı, geçici banka hesabına sermaye ödemesi ve merkez ofis adresi.' },
          { step: 'Başvuruyu Merkez Sicile (CRMS) sunun', desc: 'Başvuru e-Registar üzerinden elektronik olarak veya CRMS ofisinde şahsen yapılabilir. Elektronik kayıt daha hızlı ve ucuzdur. DOOEL için ücret yaklaşık 2.300 MKD (37 EUR)\'dir.' },
          { step: 'Kayıt kararını alın', desc: 'CRMS başvuruyu 4 saat (elektronik) ile 1 gün (şahsen) içinde işler. Onay üzerine EMBS (benzersiz kuruluş kimlik numarası) alırsınız — bu şirketinizin kimlik numarasıdır.' },
          { step: 'UJP\'ye kaydolun ve EDB alın', desc: 'Kamu Gelir İdaresi CRMS\'den otomatik bildirim alır, ancak vergi mükellefi kayıt formunu göndermeniz ve EDB (benzersiz vergi numarası) almanız gerekir. Bu 1-3 iş günü sürer.' },
          { step: 'Banka hesabı açın', desc: 'Kayıt kararı ve EDB ile ticari banka hesabı açın. Teklifleri karşılaştırın — ücretler önemli ölçüde farklılık gösterir. Geçici hesaptaki sermaye ticari hesaba aktarılır.' },
          { step: 'KDV\'ye kaydolun (gerekiyorsa)', desc: 'Yıllık cironuzun 2.000.000 MKD\'yi aşmasını bekliyorsanız, KDV\'ye kaydolmanız gerekir. Gönüllü olarak da kaydolabilirsiniz — müşterileriniz KDV kayıtlıysa bu faydalıdır, çünkü girdi KDV\'sini geri alabilirsiniz.' },
        ],
      },
      {
        title: 'Şirket kurma maliyetleri',
        content: 'Makedonya\'da DOOEL kayıt maliyeti diğer ülkelere kıyasla nispeten düşüktür. İşte temel maliyetlerin özeti:',
        items: [
          'Kuruluş beyanı noter tasdiki: 1.500-3.000 MKD (25-50 EUR).',
          'CRMS kayıt ücreti: 2.300 MKD (37 EUR) elektronik, 3.400 MKD (55 EUR) şahsen.',
          'Sermaye: 5.000 EUR (MKD karşılığı) — bu bir maliyet değil, şirket hesabında kalan bir depozitodur.',
          'Şirket mührü: 500-1.500 MKD (8-25 EUR).',
          'Banka hesabı açma: Çoğu bankada ücretsiz, ancak aylık 200-500 MKD komisyonla.',
          'Toplam (sermaye hariç): Yaklaşık 5.000-8.000 MKD (80-130 EUR).',
        ],
        steps: null,
      },
      {
        title: 'Kayıt sonrası ilk yükümlülükler',
        content: 'Şirketinizi kaydettikten sonra, yerine getirmeniz gereken birkaç acil yükümlülüğünüz vardır:',
        items: [
          'Kendinizi İstihdam Ajansı\'na çalışan (müdür) olarak kaydettirin ve aylık katkılar için MPIN beyannamesi verin.',
          'Muhasebe kayıtlarını oluşturun — ilk günden defter tutun. Muhasebeci mi tutacağınıza yoksa yazılım mı kullanacağınıza karar verin.',
          'Her hizmet veya satış için fatura düzenleyin — her işlem belgelenmelidir.',
          'Bireylere satış yapıyorsanız ödeme kaydedici edinin — B2C işlemleri için yazar kasa zorunludur.',
          'Önceki ay için her ayın 15\'ine kadar aylık MPIN beyannamesi verin.',
        ],
        steps: null,
      },
      {
        title: 'Facturino ilk günden nasıl yardımcı olur',
        content: 'Facturino, Makedonya\'daki yeni işletmeler için inşa edilmiştir. Kayıttan hemen sonra profesyonel faturalar düzenlemeye, giderleri takip etmeye ve UJP için raporlar oluşturmaya başlayabilirsiniz. Muhasebe bilgisi gerekmez — sistem sezgiseldir ve sizi her adımda yönlendirir.',
        items: [
          'UJP tarafından istenen tüm zorunlu alanlarla Makedon formatında fatura düzenleme.',
          'Her faturada otomatik KDV hesaplaması.',
          'Gerçek zamanlı gelir ve gider takibi.',
          'Kurumlar vergisi, KDV ve MPIN raporları.',
          'Ücretsiz başlangıç planı — risk yok, kredi kartı gerekmez.',
        ],
        steps: null,
      },
    ],
    cta: {
      title: 'İşletmenizi doğru şekilde başlatın',
      desc: 'Facturino ilk günden ortağınızdır. Fatura düzenleyin, giderleri takip edin ve vergi yükümlülüklerinizin kontrolünde olun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function OtvoranjeFirmaMkPage({
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
// CLAUDE-CHECKPOINT
