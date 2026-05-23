import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/registracija-firma-cekor-po-cekor', {
    title: {
      mk: 'Регистрација на фирма во Македонија 2026: Чекор по чекор водич',
      en: 'Company Registration in North Macedonia 2026: Step-by-Step Checklist',
      sq: 'Regjistrimi i firmës në Maqedoni 2026: Udhëzues hap pas hapi',
      tr: 'Kuzey Makedonya\'da Firma Kaydı 2026: Adım Adım Kontrol Listesi',
    },
    description: {
      mk: 'Комплетен водич за отворање фирма во Македонија: правна форма (ДООЕЛ, ДОО, паушалец), регистрација во ЦРСМ, ЕДБ, банкарска сметка, МПИН, ДДВ и прва фактура.',
      en: 'Complete guide to starting a business in North Macedonia: legal form (DOOEL, DOO, sole trader), CRMS registration, EDB, bank account, MPIN, VAT and first invoice.',
      sq: 'Udhëzues i plotë për hapjen e firmës në Maqedoni: forma juridike (SHPKNJP, SHPK, paushalist), regjistrimi në QRMK, EDB, llogari bankare, MPIN, TVSH dhe fatura e parë.',
      tr: 'Kuzey Makedonya\'da iş kurma rehberi: hukuki biçim (DOOEL, DOO, düz oranlı), CRMS kaydı, EDB, banka hesabı, MPIN, KDV ve ilk fatura.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Регистрација на фирма во Македонија 2026: Чекор по чекор водич',
    publishDate: '23 мај 2026',
    readTime: '12 мин читање',
    intro:
      'Отворањето фирма во Македонија е побрзо отколку што мислите — целата постапка може да се заврши за 5-7 работни дена. Но грешките на почетокот чинат скапо: погрешна правна форма, пропуштен рок за ДДВ или заборавена МПИН регистрација. Овој водич ве води чекор по чекор — од изборот на правна форма до издавањето на првата фактура — со реални цени, рокови и линкови до институциите.',
    sections: [
      {
        title: '1. Изберете правна форма',
        content:
          'Првата одлука е клучна: ДООЕЛ, ДОО или паушалец? Секоја форма има различни обврски, одговорност и даночен третман. Еве споредба:',
        items: [
          'ДООЕЛ (Друштво со ограничена одговорност од едно лице) — 1 основач, 5.000 МКД основен капитал, ограничена одговорност. Полно книговодство. Најчест избор за мали бизниси.',
          'ДОО (Друштво со ограничена одговорност) — 2+ основачи, 5.000 МКД капитал, ограничена одговорност. За партнерства и заеднички проекти.',
          'Паушалец (Самостоен вршител на дејност) — Без основен капитал, неограничена одговорност (одговарате со личен имот!). Фиксен месечен данок ~5.000-15.000 МКД. Промет до 3.000.000 МКД годишно.',
          'Совет: Ако планирате промет над 1М МКД, сакате ограничена одговорност или ќе вработувате луѓе — изберете ДООЕЛ. Паушалец е добар само за фриленсери со мал промет.',
        ],
        steps: null,
      },
      {
        title: '2. Регистрација во ЦРСМ (Централен регистар)',
        content:
          'ЦРСМ (Централен Регистар на Северна Македонија) е првата институција каде се регистрира вашата фирма. Постапката е електронска или лично:',
        items: null,
        steps: [
          { step: 'Подгответе основачки акт', desc: 'За ДООЕЛ: Одлука за основање (едностран акт). За ДОО: Договор за основање меѓу сите основачи. Документот мора да содржи: назив, седиште, дејност (шифра), основен капитал, управител.' },
          { step: 'Заверете го кај нотар', desc: 'Нотарска заверка на основачкиот акт и ЗП образец (примерок од потпис на управителот). Цена: 2.000-4.000 МКД зависно од нотарот.' },
          { step: 'Поднесете пријава до ЦРСМ', desc: 'Поднесете ја пријавата за упис со сите документи. Може онлајн преку e-registration.crm.com.mk или лично. Такса: 3.000 МКД за ДООЕЛ, 5.000 МКД за ДОО.' },
          { step: 'Добијте ЕМБС и Решение', desc: 'По 1-2 работни дена добивате Решение за упис и ЕМБС (Единствен матичен број на субјект) — ова е основниот идентификатор на вашата фирма.' },
        ],
      },
      {
        title: '3. Добијте ЕДБ и регистрирајте се во УЈП',
        content:
          'ЕДБ (Единствен Даночен Број) се доделува автоматски при регистрација во ЦРСМ. Но мора да се регистрирате и во системот на УЈП (Управа за јавни приходи):',
        items: null,
        steps: [
          { step: 'ЕДБ — автоматски', desc: 'По уписот во ЦРСМ, УЈП автоматски ви доделува ЕДБ. Го наоѓате на Решението од ЦРСМ или на etax.ujp.gov.mk.' },
          { step: 'Регистрација на etax.ujp.gov.mk', desc: 'Креирајте корисничка сметка на порталот e-Tax. Потребен е дигитален сертификат (се набавува од КИБС или Македонски Телеком, цена ~2.000 МКД).' },
          { step: 'Изберете даночен режим', desc: 'Одлучете дали сте ДДВ обврзник (задолжително при промет >2М МКД) и дали ќе плаќате реален данок на добивка (10%) или паушал.' },
        ],
      },
      {
        title: '4. Отворете деловна банкарска сметка',
        content:
          'Без деловна сметка не можете да примате уплати или плаќате обврски. Банката ви е потребна и за поврзување со Facturino за автоматски банкарски извод. Потребни документи:',
        items: [
          'Решение од ЦРСМ (оригинал или заверена копија)',
          'ЗП образец — заверен примерок од потпис на управителот',
          'Лична карта на управителот',
          'Тековна состојба од ЦРСМ (не постара од 6 месеци)',
          'Некои банки бараат и печат на фирмата (иако не е задолжителен по закон)',
        ],
        steps: null,
      },
      {
        title: '5. МПИН регистрација',
        content:
          'Ако ќе вработувате луѓе (вклучувајќи се себеси како управител со плата), мора да се регистрирате за МПИН пред првото вработување:',
        items: null,
        steps: [
          { step: 'Регистрирајте се на mpinform.ujp.gov.mk', desc: 'Поднесете МПИН-1 образец (регистрација на обврзник за придонеси). Може електронски или лично во регионална канцеларија на УЈП.' },
          { step: 'Регистрирајте го секој вработен со МПИН-2', desc: 'За секој нов вработен се поднесува МПИН-2 образец со ЕМБГ, датум на вработување и работно место.' },
          { step: 'Месечно поднесување до 15-ти', desc: 'Секој месец поднесувате МПИН со бруто плати, придонеси (28%) и персонален данок (10%). Рок: 15-ти во наредниот месец.' },
        ],
      },
      {
        title: '6. Одлука за ДДВ',
        content:
          'ДДВ регистрацијата е клучна одлука. Еве ги правилата:',
        items: [
          'Задолжителна регистрација: кога годишниот промет ќе надмине 2.000.000 МКД (околу €32.000). Рок: 15 дена од денот на надминување.',
          'Доброволна регистрација: можете да се регистрирате и пред да го достигнете прагот. Корисно ако имате големи влезни фактури (ДДВ на набавки го одбивате).',
          'Стандардна стапка: 18%. Намалена: 5% (храна, лекови, книги), 10% (угостителство).',
          'ДДВ-04 пријава: се поднесува квартално (до 25-ти по завршување на кварталот).',
          'Без ДДВ: ако сте под прагот и немате големи набавки, подобро е да останете без ДДВ — поедноставно книговодство.',
          'Прегледајте го нашиот детален водич за ДДВ регистрација за сите рокови и обврски.',
        ],
        steps: null,
      },
      {
        title: '7. Прва фактура и фискален апарат',
        content:
          'Сега сте подготвени да почнете да работите. Но внимавајте на правилата за фактурирање:',
        items: null,
        steps: [
          { step: 'Б2Б (фирма на фирма)', desc: 'За продажба на други фирми ви треба само фактура со задолжителни елементи: ЕМБС, ЕДБ, датум, опис, износ, ДДВ (ако сте обврзник). Не е потребен фискален апарат.' },
          { step: 'Б2Ц (продажба на граѓани)', desc: 'За малопродажба кон физички лица ЗАДОЛЖИТЕЛЕН е фискален апарат. Казните за работа без фискален се од 2.000 до 5.000 евра.' },
          { step: 'Facturino + фискален печатач', desc: 'Facturino поддржува интеграција со фискални печатачи — креирајте фактура во софтверот и автоматски се печати фискална сметка.' },
          { step: 'Е-фактура', desc: 'Од 2024 е-фактурата е задолжителна за трансакции со државни институции (Б2Г). За Б2Б засега е доброволна но се препорачува.' },
        ],
      },
      {
        title: '8. Facturino за нови фирми',
        content:
          'Со Facturino можете да почнете веднаш — без сложена поставка, без скапи лиценци:',
        items: [
          'Бесплатен план: до 5 фактури месечно, 1 корисник, основни извештаи. Доволно за нова фирма.',
          'Фактурирање: креирајте професионални фактури на македонски со сите задолжителни елементи (ЕМБС, ЕДБ, ДДВ).',
          'Трошоци: евидентирајте ги сите расходи, скенирајте фактури со AI и автоматски книжете.',
          'Плати: целосна пресметка на плати со МПИН, придонеси и персонален данок — усогласено со МК законодавство.',
          'Банкарски извод: поврзете ја деловната сметка и автоматски порамнувајте фактури со уплати.',
          'Годишна сметка: на крајот на годината — биланс на состојба, биланс на успех и ДБ-ВП се генерираат автоматски.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани написи',
    relatedArticles: [
      { slug: 'dooel-vodich-2026', title: 'ДООЕЛ во Македонија 2026: Даноци, обврски и годишна сметка' },
      { slug: 'ddv-registracija-prag-2026', title: 'ДДВ регистрација: Праг, постапка и рокови 2026' },
      { slug: 'mpin-registracija-2026', title: 'МПИН регистрација и поднесување 2026' },
    ],
    bottomCta: {
      title: 'Отворивте фирма? Следен чекор — Facturino.',
      subtitle: 'Фактури, трошоци, плати и даноци — сè на едно место. Бесплатен план за почеток.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'Company Registration in North Macedonia 2026: Step-by-Step Checklist',
    publishDate: 'May 23, 2026',
    readTime: '12 min read',
    intro:
      'Starting a business in North Macedonia is faster than you think — the entire process can be completed in 5-7 business days. But mistakes at the start are expensive: wrong legal form, missed VAT deadline, or forgotten MPIN registration. This guide walks you through every step — from choosing a legal form to issuing your first invoice — with real costs, deadlines, and links to the relevant institutions.',
    sections: [
      {
        title: '1. Choose your legal form',
        content:
          'The first decision is critical: DOOEL, DOO, or sole trader? Each form has different obligations, liability, and tax treatment. Here is a comparison:',
        items: [
          'DOOEL (single-member LLC) — 1 founder, 5,000 MKD share capital, limited liability. Full bookkeeping required. The most common choice for small businesses.',
          'DOO (LLC with 2+ founders) — 2+ founders, 5,000 MKD capital, limited liability. For partnerships and joint ventures.',
          'Sole trader (Pausalec) — No minimum capital, unlimited liability (you are personally liable!). Fixed monthly tax ~5,000-15,000 MKD. Annual turnover up to 3,000,000 MKD.',
          'Tip: If you plan turnover above 1M MKD, want limited liability, or will hire employees — choose DOOEL. Sole trader is only suitable for freelancers with low turnover.',
        ],
        steps: null,
      },
      {
        title: '2. Registration at CRMS (Central Registry)',
        content:
          'CRMS (Central Registry of North Macedonia) is the first institution where you register your company. The process can be done online or in person:',
        items: null,
        steps: [
          { step: 'Prepare the founding act', desc: 'For DOOEL: Decision to establish (unilateral act). For DOO: Founding agreement among all founders. The document must contain: company name, registered office, activity (NACE code), share capital, and managing director.' },
          { step: 'Notarize the documents', desc: 'Notary certification of the founding act and ZP form (specimen signature of the managing director). Cost: 2,000-4,000 MKD depending on the notary.' },
          { step: 'Submit the application to CRMS', desc: 'Submit the registration application with all documents. Available online via e-registration.crm.com.mk or in person. Fee: 3,000 MKD for DOOEL, 5,000 MKD for DOO.' },
          { step: 'Receive your EMBS and Registration Decision', desc: 'Within 1-2 business days you receive the Registration Decision and EMBS (Unique Entity Registration Number) — this is your company\'s primary identifier.' },
        ],
      },
      {
        title: '3. Get your EDB and register with UJP',
        content:
          'EDB (Unique Tax Number) is assigned automatically upon CRMS registration. But you must also register in the UJP (Public Revenue Office) system:',
        items: null,
        steps: [
          { step: 'EDB — automatic', desc: 'After CRMS registration, UJP automatically assigns your EDB. You can find it on the CRMS Decision or at etax.ujp.gov.mk.' },
          { step: 'Register at etax.ujp.gov.mk', desc: 'Create a user account on the e-Tax portal. A digital certificate is required (obtained from KIBS or Makedonski Telekom, cost ~2,000 MKD).' },
          { step: 'Choose your tax regime', desc: 'Decide whether you are a VAT payer (mandatory when turnover exceeds 2M MKD) and whether you will pay actual corporate tax (10%) or flat rate.' },
        ],
      },
      {
        title: '4. Open a business bank account',
        content:
          'Without a business account you cannot receive payments or pay obligations. You also need a bank account to connect with Facturino for automatic bank statement import. Required documents:',
        items: [
          'CRMS Registration Decision (original or certified copy)',
          'ZP form — notarized specimen signature of the managing director',
          'Managing director\'s personal ID',
          'Current status certificate from CRMS (not older than 6 months)',
          'Some banks also request a company seal (though no longer legally required)',
        ],
        steps: null,
      },
      {
        title: '5. MPIN registration',
        content:
          'If you will employ anyone (including yourself as a salaried managing director), you must register for MPIN before the first employment:',
        items: null,
        steps: [
          { step: 'Register at mpinform.ujp.gov.mk', desc: 'Submit the MPIN-1 form (contribution payer registration). Can be done electronically or in person at a regional UJP office.' },
          { step: 'Register each employee with MPIN-2', desc: 'For each new employee, submit an MPIN-2 form with their EMBG (personal ID number), employment start date, and position.' },
          { step: 'Monthly filing by the 15th', desc: 'Each month you file MPIN with gross salaries, contributions (28%), and personal income tax (10%). Deadline: 15th of the following month.' },
        ],
      },
      {
        title: '6. VAT decision',
        content:
          'VAT registration is a key decision. Here are the rules:',
        items: [
          'Mandatory registration: when annual turnover exceeds 2,000,000 MKD (approximately EUR 32,000). Deadline: 15 days from the date of exceeding the threshold.',
          'Voluntary registration: you can register before reaching the threshold. Useful if you have large input invoices (you can deduct input VAT).',
          'Standard rate: 18%. Reduced: 5% (food, medicine, books), 10% (hospitality).',
          'DDV-04 return: filed quarterly (by the 25th after the end of the quarter).',
          'Without VAT: if you are below the threshold and don\'t have large purchases, it\'s better to stay non-VAT — simpler bookkeeping.',
          'See our detailed VAT registration guide for all deadlines and obligations.',
        ],
        steps: null,
      },
      {
        title: '7. First invoice and fiscal device',
        content:
          'Now you are ready to start operating. But pay attention to invoicing rules:',
        items: null,
        steps: [
          { step: 'B2B (business to business)', desc: 'For sales to other companies you only need an invoice with mandatory elements: EMBS, EDB, date, description, amount, VAT (if registered). No fiscal device required.' },
          { step: 'B2C (sales to consumers)', desc: 'For retail sales to individuals a fiscal device is MANDATORY. Penalties for operating without one range from EUR 2,000 to EUR 5,000.' },
          { step: 'Facturino + fiscal printer', desc: 'Facturino supports integration with fiscal printers — create an invoice in the software and a fiscal receipt is printed automatically.' },
          { step: 'E-invoice', desc: 'Since 2024, e-invoicing is mandatory for transactions with government institutions (B2G). For B2B it is currently voluntary but recommended.' },
        ],
      },
      {
        title: '8. Facturino for new companies',
        content:
          'With Facturino you can start immediately — no complex setup, no expensive licenses:',
        items: [
          'Free plan: up to 5 invoices per month, 1 user, basic reports. Enough for a new company.',
          'Invoicing: create professional invoices in Macedonian with all mandatory elements (EMBS, EDB, VAT).',
          'Expenses: record all expenses, scan invoices with AI, and auto-post to the ledger.',
          'Payroll: full salary calculation with MPIN, contributions, and personal income tax — compliant with MK legislation.',
          'Bank feed: connect your business account and automatically reconcile invoices with payments.',
          'Annual accounts: at year-end — balance sheet, income statement, and DB-VP are generated automatically.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    relatedArticles: [
      { slug: 'dooel-vodich-2026', title: 'Single-Member LLC (DOOEL) in North Macedonia 2026' },
      { slug: 'ddv-registracija-prag-2026', title: 'VAT Registration: Threshold, Process & Deadlines 2026' },
      { slug: 'mpin-registracija-2026', title: 'MPIN Registration & Filing 2026' },
    ],
    bottomCta: {
      title: 'Just registered your company? Next step — Facturino.',
      subtitle: 'Invoices, expenses, payroll and taxes — all in one place. Free plan to get started.',
      cta: 'Start for free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Regjistrimi i firmës në Maqedoni 2026: Udhëzues hap pas hapi',
    publishDate: '23 maj 2026',
    readTime: '12 min lexim',
    intro:
      'Hapja e firmës në Maqedoni është më e shpejtë nga sa mendoni — i gjithë procesi mund të përfundojë brenda 5-7 ditëve të punës. Por gabimet në fillim kushtojnë shtrenjtë: forma e gabuar juridike, afati i humbur i TVSH-së ose regjistrimi i harruar i MPIN. Ky udhëzues ju çon hap pas hapi — nga zgjedhja e formës juridike deri te lëshimi i faturës së parë — me çmime reale, afate dhe lidhje drejt institucioneve.',
    sections: [
      {
        title: '1. Zgjidhni formën juridike',
        content:
          'Vendimi i parë është vendimtar: SHPKNJP, SHPK apo paushalist? Secila formë ka detyrime, përgjegjësi dhe trajtim tatimor të ndryshëm. Ja një krahasim:',
        items: [
          'SHPKNJP (ДООЕЛ — shoqëri me përgjegjësi të kufizuar nga 1 person) — 1 themelues, 5.000 MKD kapital themeltar, përgjegjësi e kufizuar. Kontabilitet i plotë. Zgjidhja më e shpeshtë për biznese të vogla.',
          'SHPK (ДОО — shoqëri me përgjegjësi të kufizuar) — 2+ themelues, 5.000 MKD kapital, përgjegjësi e kufizuar. Për partneritete dhe projekte të përbashkëta.',
          'Paushalist (Tregtar i vetëm) — Pa kapital minimal, përgjegjësi e pakufizuar (përgjigjeni me pasurinë personale!). Tatim fiks mujor ~5.000-15.000 MKD. Qarkullim vjetor deri 3.000.000 MKD.',
          'Këshillë: Nëse planifikoni qarkullim mbi 1M MKD, dëshironi përgjegjësi të kufizuar ose do të punësoni njerëz — zgjidhni SHPKNJP. Paushalist është vetëm për freelancerë me qarkullim të ulët.',
        ],
        steps: null,
      },
      {
        title: '2. Regjistrimi në QRMK (Regjistri Qendror)',
        content:
          'QRMK (Regjistri Qendror i Maqedonisë së Veriut) është institucioni i parë ku regjistroni firmën tuaj. Procesi mund të bëhet online ose personalisht:',
        items: null,
        steps: [
          { step: 'Përgatitni aktin themeltar', desc: 'Për SHPKNJP: Vendim për themelim (akt njëpalësh). Për SHPK: Kontratë themelimi ndërmjet të gjithë themeluesve. Dokumenti duhet të përmbajë: emrin, selinë, veprimtarinë (kodin NACE), kapitalin themeltar, administratorin.' },
          { step: 'Noterizoni dokumentet', desc: 'Noterizimi i aktit themeltar dhe formularit ZP (mostra e nënshkrimit të administratorit). Çmimi: 2.000-4.000 MKD varësisht nga notari.' },
          { step: 'Dorëzoni aplikimin në QRMK', desc: 'Dorëzoni aplikimin për regjistrim me të gjitha dokumentet. Mundësi online përmes e-registration.crm.com.mk ose personalisht. Taksë: 3.000 MKD për SHPKNJP, 5.000 MKD për SHPK.' },
          { step: 'Merrni EMBS dhe Vendimin', desc: 'Brenda 1-2 ditëve të punës merrni Vendimin për regjistrim dhe EMBS (Numri unik i regjistrimit) — ky është identifikuesi kryesor i firmës suaj.' },
        ],
      },
      {
        title: '3. Merrni EDB dhe regjistrohuni në DAP',
        content:
          'EDB (Numri Unik Tatimor) caktohet automatikisht gjatë regjistrimit në QRMK. Por duhet të regjistroheni edhe në sistemin e DAP (Drejtoria e të Ardhurave Publike):',
        items: null,
        steps: [
          { step: 'EDB — automatikisht', desc: 'Pas regjistrimit në QRMK, DAP automatikisht cakton EDB-në tuaj. E gjeni në Vendimin e QRMK ose në etax.ujp.gov.mk.' },
          { step: 'Regjistrohuni në etax.ujp.gov.mk', desc: 'Krijoni llogari përdoruesi në portalin e-Tax. Nevojitet certifikatë digjitale (merret nga KIBS ose Makedonski Telekom, çmimi ~2.000 MKD).' },
          { step: 'Zgjidhni regjimin tatimor', desc: 'Vendosni nëse jeni pagues i TVSH (i detyrueshëm kur qarkullimi kalon 2M MKD) dhe nëse do të paguani tatim real mbi fitimin (10%) ose normë fikse.' },
        ],
      },
      {
        title: '4. Hapni llogari bankare biznesi',
        content:
          'Pa llogari biznesi nuk mundeni të pranoni pagesa ose të paguani detyrime. Llogaria bankare nevojitet edhe për lidhjen me Facturino për importimin automatik të ekstraktit. Dokumentet e nevojshme:',
        items: [
          'Vendimi i regjistrimit nga QRMK (origjinal ose kopje e noterizuar)',
          'Formulari ZP — mostra e noterizuar e nënshkrimit të administratorit',
          'Letërnjoftimi i administratorit',
          'Certifikatë e gjendjes aktuale nga QRMK (jo më e vjetër se 6 muaj)',
          'Disa banka kërkojnë edhe vulën e firmës (edhe pse nuk është më e detyrueshme ligjërisht)',
        ],
        steps: null,
      },
      {
        title: '5. Regjistrimi MPIN',
        content:
          'Nëse do të punësoni dikë (përfshirë veten si administrator me pagë), duhet të regjistroheni për MPIN para punësimit të parë:',
        items: null,
        steps: [
          { step: 'Regjistrohuni në mpinform.ujp.gov.mk', desc: 'Dorëzoni formularin MPIN-1 (regjistrimi i paguesit të kontributeve). Mund të bëhet elektronikisht ose personalisht në zyrën rajonale të DAP.' },
          { step: 'Regjistroni çdo punonjës me MPIN-2', desc: 'Për çdo punonjës të ri dorëzoni formularin MPIN-2 me EMBG, datën e fillimit të punës dhe pozitën.' },
          { step: 'Dorëzimi mujor deri më 15', desc: 'Çdo muaj dorëzoni MPIN me pagat bruto, kontributet (28%) dhe tatimin personal (10%). Afati: 15 i muajit pasardhës.' },
        ],
      },
      {
        title: '6. Vendimi për TVSH',
        content:
          'Regjistrimi për TVSH është vendim kyç. Ja rregullat:',
        items: [
          'Regjistrimi i detyrueshëm: kur qarkullimi vjetor kalon 2.000.000 MKD (rreth €32.000). Afati: 15 ditë nga dita e kalimit.',
          'Regjistrimi vullnetar: mundeni të regjistroheni edhe para arritjes së pragut. E dobishme nëse keni fatura hyrëse të mëdha (mundeni të zbrisni TVSH-në hyrëse).',
          'Norma standarde: 18%. E reduktuar: 5% (ushqim, ilaçe, libra), 10% (hotelieri).',
          'Deklarata DDV-04: dorëzohet tremujorisht (deri më 25 pas përfundimit të tremujorit).',
          'Pa TVSH: nëse jeni nën prag dhe nuk keni blerje të mëdha, është më mirë të qëndroni pa TVSH — kontabilitet më i thjeshtë.',
          'Shikoni udhëzuesin tonë të detajuar për regjistrimin e TVSH për të gjitha afatet dhe detyrimet.',
        ],
        steps: null,
      },
      {
        title: '7. Fatura e parë dhe paisja fiskale',
        content:
          'Tani jeni gati të filloni punën. Por kujdes me rregullat e faturimit:',
        items: null,
        steps: [
          { step: 'B2B (biznes me biznes)', desc: 'Për shitje ndaj firmave të tjera nevojitet vetëm faturë me elementet e detyrueshme: EMBS, EDB, data, përshkrimi, shuma, TVSH (nëse jeni regjistruar). Nuk nevojitet paisje fiskale.' },
          { step: 'B2C (shitje ndaj konsumatorëve)', desc: 'Për shitje me pakicë ndaj personave fizikë paisja fiskale është E DETYRUESHME. Gjobat për punë pa paisje fiskale janë nga 2.000 deri 5.000 euro.' },
          { step: 'Facturino + printer fiskal', desc: 'Facturino mbështet integrimin me printerë fiskalë — krijoni faturën në softuer dhe kuponi fiskal printohet automatikisht.' },
          { step: 'E-fatura', desc: 'Nga 2024 e-fatura është e detyrueshme për transaksione me institucione shtetërore (B2G). Për B2B aktualisht është vullnetare por rekomandohet.' },
        ],
      },
      {
        title: '8. Facturino për firma të reja',
        content:
          'Me Facturino mundeni të filloni menjëherë — pa konfigurim kompleks, pa licensa të shtrenjta:',
        items: [
          'Plani falas: deri 5 fatura në muaj, 1 përdorues, raporte bazike. Mjaft për firmë të re.',
          'Faturim: krijoni fatura profesionale në maqedonisht me të gjitha elementet e detyrueshme (EMBS, EDB, TVSH).',
          'Shpenzime: regjistroni të gjitha shpenzimet, skanoni faturat me AI dhe regjistrim automatik.',
          'Paga: llogaritje e plotë e pagave me MPIN, kontribute dhe tatim personal — në përputhje me legjislacionin MK.',
          'Ekstrakti bankar: lidhni llogarinë e biznesit dhe poramnoni automatikisht faturat me pagesat.',
          'Llogaria vjetore: në fund të vitit — bilanci i gjendjes, bilanci i suksesit dhe DB-VP gjenepohen automatikisht.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    relatedArticles: [
      { slug: 'dooel-vodich-2026', title: 'SHPKNJP në Maqedoni 2026: Tatimet, detyrimet dhe llogaria vjetore' },
      { slug: 'ddv-registracija-prag-2026', title: 'Regjistrimi TVSH: Pragu, procesi dhe afatet 2026' },
      { slug: 'mpin-registracija-2026', title: 'Regjistrimi dhe dorëzimi MPIN 2026' },
    ],
    bottomCta: {
      title: 'Hapët firmën? Hapi tjetër — Facturino.',
      subtitle: 'Fatura, shpenzime, paga dhe tatime — gjithçka në një vend. Plan falas për fillim.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Bloğa dön',
    tag: 'Rehber',
    title: 'Kuzey Makedonya\'da Firma Kaydı 2026: Adım Adım Kontrol Listesi',
    publishDate: '23 Mayıs 2026',
    readTime: '12 dk okuma',
    intro:
      'Kuzey Makedonya\'da iş kurmak düşündüğünüzden daha hızlıdır — tüm süreç 5-7 iş günü içinde tamamlanabilir. Ancak başlangıçtaki hatalar pahalıya mal olur: yanlış hukuki biçim, kaçırılan KDV son tarihi veya unutulan MPIN kaydı. Bu rehber sizi adım adım yönlendirir — hukuki biçim seçiminden ilk faturanıza kadar — gerçek maliyetler, son tarihler ve kurumların bağlantılarıyla birlikte.',
    sections: [
      {
        title: '1. Hukuki biçiminizi seçin',
        content:
          'İlk karar kritiktir: DOOEL, DOO veya düz oranlı vergi? Her biçimin farklı yükümlülükleri, sorumluluğu ve vergi muamelesi vardır. Karşılaştırma:',
        items: [
          'DOOEL (tek kişilik limited şirket) — 1 kurucu, 5.000 MKD sermaye, sınırlı sorumluluk. Tam muhasebe gerekli. Küçük işletmeler için en yaygın tercih.',
          'DOO (2+ ortaklı limited şirket) — 2+ kurucu, 5.000 MKD sermaye, sınırlı sorumluluk. Ortaklıklar ve ortak girişimler için.',
          'Düz oranlı vergi (Paušalec) — Asgari sermaye yok, sınırsız sorumluluk (kişisel varlıklarınızla sorumlusunuz!). Sabit aylık vergi ~5.000-15.000 MKD. Yıllık ciro 3.000.000 MKD\'ye kadar.',
          'İpucu: 1M MKD üzeri ciro planlıyorsanız, sınırlı sorumluluk istiyorsanız veya çalışan alacaksanız — DOOEL seçin. Düz oranlı vergi yalnızca düşük cirolu serbest çalışanlar için uygundur.',
        ],
        steps: null,
      },
      {
        title: '2. CRMS\'de kayıt (Merkezi Sicil)',
        content:
          'CRMS (Kuzey Makedonya Merkezi Sicili), şirketinizi kaydettiğiniz ilk kurumdur. İşlem online veya yüz yüze yapılabilir:',
        items: null,
        steps: [
          { step: 'Kuruluş belgesini hazırlayın', desc: 'DOOEL için: Kuruluş kararı (tek taraflı belge). DOO için: Tüm kurucular arasında kuruluş sözleşmesi. Belge şunları içermelidir: şirket adı, kayıtlı ofis, faaliyet (NACE kodu), sermaye ve genel müdür.' },
          { step: 'Belgeleri noterde onaylayın', desc: 'Kuruluş belgesinin ve ZP formunun (genel müdür imza örneği) noter tasdiki. Maliyet: notere göre 2.000-4.000 MKD.' },
          { step: 'CRMS\'ye başvuru yapın', desc: 'Tüm belgelerle kayıt başvurusunu sunun. e-registration.crm.com.mk üzerinden çevrimiçi veya yüz yüze yapılabilir. Harç: DOOEL için 3.000 MKD, DOO için 5.000 MKD.' },
          { step: 'EMBS ve Kayıt Kararı alın', desc: '1-2 iş günü içinde Kayıt Kararı ve EMBS (Benzersiz Kuruluş Kayıt Numarası) alırsınız — bu şirketinizin birincil tanımlayıcısıdır.' },
        ],
      },
      {
        title: '3. EDB\'nizi alın ve UJP\'ye kaydolun',
        content:
          'EDB (Benzersiz Vergi Numarası) CRMS kaydıyla birlikte otomatik olarak atanır. Ancak UJP (Kamu Gelir İdaresi) sistemine de kaydolmanız gerekir:',
        items: null,
        steps: [
          { step: 'EDB — otomatik', desc: 'CRMS kaydının ardından UJP otomatik olarak EDB\'nizi atar. CRMS Kararında veya etax.ujp.gov.mk\'da bulabilirsiniz.' },
          { step: 'etax.ujp.gov.mk\'ya kaydolun', desc: 'e-Tax portalında kullanıcı hesabı oluşturun. Dijital sertifika gereklidir (KIBS veya Makedonski Telekom\'dan temin edilir, maliyet ~2.000 MKD).' },
          { step: 'Vergi rejimini seçin', desc: 'KDV mükellefi olup olmadığınıza (ciro 2M MKD\'yi aştığında zorunlu) ve gerçek kurumlar vergisi (%10) mi yoksa düz oranlı mı ödeyeceğinize karar verin.' },
        ],
      },
      {
        title: '4. Ticari banka hesabı açın',
        content:
          'Ticari hesap olmadan ödeme alamaz veya yükümlülüklerinizi ödeyemezsiniz. Facturino ile otomatik banka ekstresi bağlantısı için de banka hesabı gereklidir. Gerekli belgeler:',
        items: [
          'CRMS Kayıt Kararı (orijinal veya onaylı kopya)',
          'ZP formu — genel müdürün noter onaylı imza örneği',
          'Genel müdürün kimlik belgesi',
          'CRMS\'den güncel durum belgesi (6 aydan eski olmayan)',
          'Bazı bankalar şirket mührü de ister (yasal olarak artık zorunlu olmasa da)',
        ],
        steps: null,
      },
      {
        title: '5. MPIN kaydı',
        content:
          'Herhangi birini istihdam edecekseniz (maaşlı genel müdür olarak kendiniz dahil), ilk istihdamdan önce MPIN\'e kaydolmanız gerekir:',
        items: null,
        steps: [
          { step: 'mpinform.ujp.gov.mk\'ya kaydolun', desc: 'MPIN-1 formunu (katkı payı ödeyen kaydı) gönderin. Elektronik olarak veya bölgesel UJP ofisinde yüz yüze yapılabilir.' },
          { step: 'Her çalışanı MPIN-2 ile kaydedin', desc: 'Her yeni çalışan için EMBG (TC kimlik numarası), işe başlama tarihi ve pozisyon içeren MPIN-2 formu gönderin.' },
          { step: '15\'ine kadar aylık dosyalama', desc: 'Her ay brüt maaşlar, primler (%28) ve gelir vergisi (%10) ile MPIN dosyalarsınız. Son tarih: takip eden ayın 15\'i.' },
        ],
      },
      {
        title: '6. KDV kararı',
        content:
          'KDV kaydı önemli bir karardır. Kurallar:',
        items: [
          'Zorunlu kayıt: yıllık ciro 2.000.000 MKD\'yi (yaklaşık €32.000) aştığında. Son tarih: eşiğin aşıldığı tarihten itibaren 15 gün.',
          'Gönüllü kayıt: eşiğe ulaşmadan da kaydolabilirsiniz. Büyük girdi faturalarınız varsa faydalıdır (girdi KDV\'sini düşebilirsiniz).',
          'Standart oran: %18. İndirimli: %5 (gıda, ilaç, kitap), %10 (otelcilik).',
          'DDV-04 beyannamesi: üç ayda bir dosyalanır (çeyreğin bitiminden sonra 25\'ine kadar).',
          'KDV\'siz: eşiğin altındaysanız ve büyük alımlarınız yoksa, KDV\'siz kalmak daha iyidir — daha basit muhasebe.',
          'Tüm tarihler ve yükümlülükler için ayrıntılı KDV kayıt rehberimize bakın.',
        ],
        steps: null,
      },
      {
        title: '7. İlk fatura ve mali cihaz',
        content:
          'Artık çalışmaya başlamaya hazırsınız. Ancak faturalama kurallarına dikkat edin:',
        items: null,
        steps: [
          { step: 'B2B (işletmeden işletmeye)', desc: 'Diğer şirketlere satış için yalnızca zorunlu unsurları içeren fatura gerekir: EMBS, EDB, tarih, açıklama, tutar, KDV (kayıtlıysanız). Mali cihaz gerekmez.' },
          { step: 'B2C (tüketiciye satış)', desc: 'Bireylere perakende satış için mali cihaz ZORUNLUDUR. Cihaz olmadan çalışmanın cezası 2.000 ile 5.000 avro arasıdır.' },
          { step: 'Facturino + mali yazıcı', desc: 'Facturino mali yazıcı entegrasyonunu destekler — yazılımda fatura oluşturun, mali fiş otomatik olarak yazdırılır.' },
          { step: 'E-fatura', desc: '2024\'ten itibaren e-fatura devlet kurumlarıyla yapılan işlemler (B2G) için zorunludur. B2B için şimdilik isteğe bağlıdır ancak tavsiye edilir.' },
        ],
      },
      {
        title: '8. Yeni şirketler için Facturino',
        content:
          'Facturino ile hemen başlayabilirsiniz — karmaşık kurulum yok, pahalı lisans yok:',
        items: [
          'Ücretsiz plan: ayda 5 faturaya kadar, 1 kullanıcı, temel raporlar. Yeni bir şirket için yeterli.',
          'Faturalama: tüm zorunlu unsurlarla (EMBS, EDB, KDV) Makedoncada profesyonel faturalar oluşturun.',
          'Giderler: tüm giderleri kaydedin, faturaları AI ile tarayın ve otomatik deftere kaydedin.',
          'Bordro: MPIN, primler ve gelir vergisiyle tam maaş hesaplaması — MK mevzuatına uyumlu.',
          'Banka ekstresi: ticari hesabınızı bağlayın ve faturaları ödemelerle otomatik eşleştirin.',
          'Yıllık hesaplar: yıl sonunda — bilanço, gelir tablosu ve DB-VP otomatik olarak oluşturulur.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili yazılar',
    relatedArticles: [
      { slug: 'dooel-vodich-2026', title: 'Kuzey Makedonya DOOEL 2026: Vergiler, Yükümlülükler ve Yıllık Hesaplar' },
      { slug: 'ddv-registracija-prag-2026', title: 'KDV Kaydı: Eşik, Süreç ve Tarihler 2026' },
      { slug: 'mpin-registracija-2026', title: 'MPIN Kaydı ve Dosyalama 2026' },
    ],
    bottomCta: {
      title: 'Firma mı kurdunuz? Sonraki adım — Facturino.',
      subtitle: 'Fatura, gider, bordro ve vergi — hepsi tek yerde. Başlamak için ücretsiz plan.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function RegistracijaFirmaPage({
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
    slug: 'registracija-firma-cekor-po-cekor',
    title: t.title,
    description: t.intro,
    datePublished: '2026-05-23',
    tags: ['регистрација', 'фирма', 'ДООЕЛ', 'DOOEL', 'company registration', 'Macedonia', 'ЦРСМ', 'ЕДБ', 'МПИН', 'ДДВ'],
  })
  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/registracija-firma-cekor-po-cekor` },
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
