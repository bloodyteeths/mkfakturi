import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'
import Image from 'next/image'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/ai-bankarski-usoglasuvanje', {
    title: {
      mk: 'Банкарско усогласување со AI: од 3 часа на 3 минути — Facturino',
      en: 'AI Bank Reconciliation: From 3 Hours to 3 Minutes — Facturino',
      sq: 'Rakordimi bankar me AI: nga 3 orë në 3 minuta — Facturino',
      tr: 'AI Banka Mutabakatı: 3 Saatten 3 Dakikaya — Facturino',
    },
    description: {
      mk: 'Автоматско банкарско усогласување со AI за македонски бизниси. 4-слојна pipeline за усогласување на банковни изводи и трансакции. Поддржани сите 9 банки.',
      en: 'Automatic AI bank reconciliation for Macedonian businesses. 4-layer pipeline for matching bank statements and transactions. All 9 banks supported.',
      sq: 'Rakordim automatik bankar me AI për bizneset maqedonase. Pipeline 4-shtresore për përputhjen e ekstrakteve bankare dhe transaksioneve. 9 banka të mbështetura.',
      tr: 'Makedon işletmeleri için otomatik AI banka mutabakatı. Banka ekstrelerini ve işlemleri eşleştirmek için 4 katmanlı pipeline. 9 banka destekleniyor.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Производ',
    title: 'Банкарско усогласување со AI: од 3 часа на 3 минути',
    publishDate: '15 март 2026',
    readTime: '9 мин читање',
    heroAlt: 'AI банкарско усогласување Facturino - автоматско усогласување на банковни изводи',
    intro: 'Секој сметководител и финансиски менаџер го знае проблемот: крајот на месецот доаѓа, а со него и купот банковни изводи. 200, 300, понекогаш и 500 трансакции треба рачно да се усогласат со фактури, плаќања и книжења. Тоа е 3 часа монотона работа, секој месец. Facturino го решава овој проблем со интелигентна AI технологија — целиот процес на банкарско усогласување сега трае само 3 минути. Автоматско книжење, паметно препознавање на трансакции и целосна усогласеност со банковните изводи на сите македонски банки.',
    sections: [
      {
        title: '4-слојна AI pipeline за усогласување',
        content: 'Facturino користи напредна 4-слојна AI pipeline архитектура за усогласување на банковните трансакции. Секој слој додава дополнително ниво на интелигенција, обезбедувајќи максимална точност и минимална потреба за рачна интервенција. Овој хибриден пристап комбинира детерминистички правила со вештачка интелигенција за да постигне стапка на автоматско усогласување од над 95%.',
        items: null,
        steps: [
          { step: 'Слој 1: Правила (Rules)', desc: 'Првиот слој користи егзактно совпаѓање базирано на правила. Кога уплатата точно одговара на износот на фактурата, со ист датум и референтен број — системот веднаш ја поврзува. Ова опфаќа околу 60% од сите трансакции: редовни клиенти, месечни плаќања, фиксни износи. Совпаѓањето е моментално и 100% точно. Правилата се конфигурабилни — можете да додадете сопствени правила за специфични добавувачи или клиенти.' },
          { step: 'Слој 2: Детерминистичко совпаѓање', desc: 'За трансакциите кои не се фатени од првиот слој, вториот слој применува детерминистичко совпаѓање по износ, датум и опис. На пример, ако уплатата е точно 15.000 МКД и имате фактура за истиот износ издадена во блиска временска рамка — системот ги поврзува. Овој слој ги обработува и делумните уплати, каде клиентот плаќа фактура во повеќе рати. Алгоритмот користи тежински бодови за точност на износот, временска близина и текстуална сличност.' },
          { step: 'Слој 3: AI подобрување (Enhancement)', desc: 'Третиот слој е каде вештачката интелигенција блесне. AI моделот анализира нејасни случаи: различно напишани имиња на компании (на пример „ДООЕЛ" vs „Д.О.О.Е.Л."), заокружени износи, трансакции со неколкудневно доцнење. AI учи од вашите претходни одлуки — ако претходно сте поврзале уплата од „ПЕТРОВ ТРЕЈД" со клиентот „Петров Трејд ДООЕЛ", системот автоматски ги поврзува во иднина. Ова fuzzy совпаѓање користи NLP (обработка на природен јазик) за да го разбере контекстот на секоја трансакција.' },
          { step: 'Слој 4: AI категоризација', desc: 'Последниот слој се справува со целосно нови трансакции кои немаат совпаѓање со ниту една фактура. Наместо да ги остави неусогласени, AI автоматски ги категоризира: „Трошок за канцелариски материјал", „Комунални услуги", „Провизија за банкарска услуга". Категоризацијата учи од вашите претходни книжења и со време станува се попрецизна. Нови трансакции автоматски добиваат предлог за конто и категорија, а вие само потврдувате или менувате со еден клик.' },
        ],
      },
      {
        title: 'Поддржани банки во Македонија',
        content: 'Facturino поддржува увоз на банковни изводи од сите 9 активни комерцијални банки во Македонија. Без разлика дали користите CSV експорт од е-банкарство, стандарден MT940 формат или скениран PDF извод — Facturino автоматски ги препознава и парсира трансакциите. Секоја банка има свој формат, и нашиот систем е обучен да ги чита сите варијанти без грешка.',
        items: [
          'НЛБ Банка — CSV, MT940 и PDF формати од е-банкарство и iKliring',
          'Стопанска Банка АД Скопје — извоз преку е-банкарство во CSV и MT940',
          'Комерцијална Банка — поддршка за сите дигитални формати на изводи',
          'Халкбанк АД Скопје — CSV увоз од Халк е-банкарство платформа',
          'Шпаркасе Банка — MT940 и CSV од NetBanking и SmartBanking',
          'ПроКредит Банка — стандардни CSV изводи од ProB@nking',
          'Силк Роуд Банка — CSV и PDF формати',
          'ТТК Банка — CSV извоз од електронско банкарство',
          'Охридска Банка — CSV и MT940 формати',
        ],
        steps: null,
      },
      {
        title: 'Реален пример: 200 трансакции за 3 минути',
        content: 'Да замислиме реален сценарио: сметководителка Марија работи за трговска компанија со 200 трансакции месечно на сметката во Стопанска Банка. Пред Facturino, Марија поминувала 3 часа секој месец рачно усогласувајќи ги изводите. Со Facturino AI pipeline, процесот изгледа целосно поинаку:',
        items: [
          '185 трансакции (92.5%) — автоматски усогласени преку Слој 1 и 2 (правила и детерминистичко совпаѓање). Редовни клиенти, месечни закупнини, фиксни плаќања — сe е поврзано моментално.',
          '10 трансакции (5%) — AI предложи совпаѓање преку Слој 3 (fuzzy matching). Заокружени износи, различни називи — AI ги препознал врз основа на историјата. Марија само кликнува „Потврди".',
          '5 трансакции (2.5%) — нови, непознати трансакции. AI Слој 4 автоматски ги категоризирал: банкарска провизија, комунални трошоци, ситна набавка. Марија прегледува и по потреба коригира.',
          'Вкупно време: 3 минути наместо 3 часа. Заштеда од 2 часа и 57 минути секој месец.',
          'Годишна заштеда: 35 часа работно време — еквивалент на речиси цела работна недела!',
        ],
        steps: null,
      },
      {
        title: 'Кој може да го користи AI усогласувањето',
        content: 'AI банкарското усогласување е достапно за корисниците на Business планот на Facturino. Business планот чини 3.630 МКД месечно (59 EUR) и вклучува не само AI усогласување, туку и PSD2 банкарски конекции, напредна аналитика и неограничени фактури. За сметководствените фирми кои се дел од нашата партнерска програма, AI усогласувањето е бесплатно вклучено — дополнителна причина да се придружите на програмата.',
        items: [
          'Business план (3.630 МКД/месечно) — целосен пристап до AI усогласување, PSD2 конекции и напредна аналитика',
          'Партнери (сметководствени фирми) — бесплатен пристап до сите AI функции преку партнерската програма',
          'Пробен период од 14 дена — тестирајте го AI усогласувањето бесплатно на Standard план',
          'Free и Starter планови — рачно усогласување достапно, AI надградба со еден клик',
        ],
        steps: null,
      },
      {
        title: 'PSD2 Open Banking — следното ниво',
        content: 'Facturino активно работи на PSD2 (Open Banking) интеграција со македонските банки. Наскоро, наместо рачно да преземате CSV или PDF изводи, вашата банковна сметка ќе биде директно поврзана со Facturino. Трансакциите ќе се синхронизираат автоматски, а AI pipeline ќе ги усогласува во реално време. Замислете: секоја уплата од клиент автоматски се поврзува со фактурата и се книжи, без вие воопшто да интервенирате. Ова е иднината на сметководството во Македонија.',
        items: null,
        steps: null,
      },
      {
        title: 'Започнете со автоматско усогласување',
        content: 'Подгответе ги банковните изводи и видете ја разликата уште денес. Регистрирајте се за 14-дневен пробен период, поврзете ја вашата банка и оставете AI да ја заврши работата. Процесот е едноставен: увезете го изводот, кликнете „Усогласи" и прегледајте ги резултатите. За помалку од 3 минути, вашите банковни трансакции ќе бидат усогласени со фактурите и книжењата.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'cash-flow-mk', title: 'Управување со парични текови во Македонија' },
      { slug: 'upravljanje-so-rashodi', title: 'Управување со расходи: целосен водич' },
      { slug: 'digitalno-smetkovodstvo', title: 'Дигитално vs традиционално сметководство' },
    ],
    cta: {
      title: 'Автоматизирајте го банкарското усогласување',
      desc: 'Започнете бесплатно и видете како AI ги усогласува вашите трансакции за 3 минути наместо 3 часа.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Product',
    title: 'AI Bank Reconciliation: From 3 Hours to 3 Minutes',
    publishDate: 'March 15, 2026',
    readTime: '9 min read',
    heroAlt: 'AI bank reconciliation Facturino - automatic bank statement matching',
    intro: 'Every accountant and financial manager knows the problem: month-end arrives, and with it comes the pile of bank statements. 200, 300, sometimes 500 transactions that need to be manually matched with invoices, payments, and journal entries. That is 3 hours of monotonous work, every single month. Facturino solves this problem with intelligent AI technology — the entire bank reconciliation process now takes just 3 minutes. Automatic posting, smart transaction recognition, and full reconciliation with bank statements from all Macedonian banks.',
    sections: [
      {
        title: '4-Layer AI Reconciliation Pipeline',
        content: 'Facturino uses an advanced 4-layer AI pipeline architecture for reconciling bank transactions. Each layer adds an additional level of intelligence, ensuring maximum accuracy and minimum need for manual intervention. This hybrid approach combines deterministic rules with artificial intelligence to achieve an automatic reconciliation rate of over 95%.',
        items: null,
        steps: [
          { step: 'Layer 1: Rules (Exact Matching)', desc: 'The first layer uses exact rule-based matching. When a payment exactly matches an invoice amount, with the same date and reference number — the system connects them instantly. This covers approximately 60% of all transactions: regular clients, monthly payments, fixed amounts. The matching is instantaneous and 100% accurate. Rules are configurable — you can add your own rules for specific suppliers or clients.' },
          { step: 'Layer 2: Deterministic Matching', desc: 'For transactions not caught by the first layer, the second layer applies deterministic matching by amount, date, and description. For example, if a payment is exactly 15,000 MKD and you have an invoice for the same amount issued within a close time frame — the system connects them. This layer also handles partial payments, where a client pays an invoice in multiple installments. The algorithm uses weighted scores for amount accuracy, temporal proximity, and textual similarity.' },
          { step: 'Layer 3: AI Enhancement (Fuzzy Matching)', desc: 'The third layer is where artificial intelligence truly shines. The AI model analyzes ambiguous cases: differently written company names (e.g., "DOOEL" vs "D.O.O.E.L."), rounded amounts, transactions with delays of several days. The AI learns from your previous decisions — if you previously matched a payment from "PETROV TRADE" with the client "Petrov Trade DOOEL", the system automatically connects them in the future. This fuzzy matching uses NLP (natural language processing) to understand the context of each transaction.' },
          { step: 'Layer 4: AI Categorization', desc: 'The final layer handles completely new transactions that have no match with any invoice. Instead of leaving them unreconciled, the AI automatically categorizes them: "Office supplies expense", "Utilities", "Bank service fee". The categorization learns from your previous journal entries and becomes increasingly precise over time. New transactions automatically receive a suggested account and category, and you simply confirm or change with a single click.' },
        ],
      },
      {
        title: 'Supported Banks in Macedonia',
        content: 'Facturino supports bank statement import from all 9 active commercial banks in Macedonia. Whether you use CSV export from e-banking, standard MT940 format, or scanned PDF statements — Facturino automatically recognizes and parses the transactions. Each bank has its own format, and our system is trained to read all variants without errors.',
        items: [
          'NLB Banka — CSV, MT940, and PDF formats from e-banking and iKliring',
          'Stopanska Banka AD Skopje — export via e-banking in CSV and MT940',
          'Komercijalna Banka — support for all digital statement formats',
          'Halkbank AD Skopje — CSV import from Halk e-banking platform',
          'Sparkasse Banka — MT940 and CSV from NetBanking and SmartBanking',
          'ProCredit Banka — standard CSV statements from ProB@nking',
          'Silk Road Banka — CSV and PDF formats',
          'TTK Banka — CSV export from electronic banking',
          'Ohridska Banka — CSV and MT940 formats',
        ],
        steps: null,
      },
      {
        title: 'Real Example: 200 Transactions in 3 Minutes',
        content: 'Let us imagine a real scenario: accountant Marija works for a trading company with 200 monthly transactions at Stopanska Banka. Before Facturino, Marija spent 3 hours every month manually reconciling statements. With Facturino AI pipeline, the process looks completely different:',
        items: [
          '185 transactions (92.5%) — automatically reconciled via Layer 1 and 2 (rules and deterministic matching). Regular clients, monthly rents, fixed payments — everything connected instantly.',
          '10 transactions (5%) — AI suggested matches via Layer 3 (fuzzy matching). Rounded amounts, different names — AI recognized them based on history. Marija just clicks "Confirm".',
          '5 transactions (2.5%) — new, unknown transactions. AI Layer 4 automatically categorized them: bank fee, utility costs, small purchase. Marija reviews and corrects if needed.',
          'Total time: 3 minutes instead of 3 hours. Saving 2 hours and 57 minutes every month.',
          'Annual saving: 35 hours of working time — equivalent to almost an entire working week!',
        ],
        steps: null,
      },
      {
        title: 'Who Can Use AI Reconciliation',
        content: 'AI bank reconciliation is available for Facturino Business plan users. The Business plan costs 3,630 MKD per month (EUR 59) and includes not only AI reconciliation but also PSD2 bank connections, advanced analytics, and unlimited invoices. For accounting firms that are part of our partner program, AI reconciliation is included for free — an additional reason to join the program.',
        items: [
          'Business plan (3,630 MKD/month) — full access to AI reconciliation, PSD2 connections, and advanced analytics',
          'Partners (accounting firms) — free access to all AI features through the partner program',
          '14-day trial period — test AI reconciliation free on Standard plan',
          'Free and Starter plans — manual reconciliation available, AI upgrade with one click',
        ],
        steps: null,
      },
      {
        title: 'PSD2 Open Banking — The Next Level',
        content: 'Facturino is actively working on PSD2 (Open Banking) integration with Macedonian banks. Soon, instead of manually downloading CSV or PDF statements, your bank account will be directly connected to Facturino. Transactions will synchronize automatically, and the AI pipeline will reconcile them in real time. Imagine: every client payment is automatically matched with the invoice and posted, without any intervention from you. This is the future of accounting in Macedonia.',
        items: null,
        steps: null,
      },
      {
        title: 'Start with Automatic Reconciliation',
        content: 'Prepare your bank statements and see the difference today. Sign up for a 14-day trial, connect your bank, and let the AI do the work. The process is simple: import the statement, click "Reconcile", and review the results. In less than 3 minutes, your bank transactions will be matched with invoices and journal entries.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'cash-flow-mk', title: 'Cash Flow Management in Macedonia' },
      { slug: 'upravljanje-so-rashodi', title: 'Expense Management: Complete Guide' },
      { slug: 'digitalno-smetkovodstvo', title: 'Digital vs Traditional Accounting' },
    ],
    cta: {
      title: 'Automate Your Bank Reconciliation',
      desc: 'Start free and see how AI reconciles your transactions in 3 minutes instead of 3 hours.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Produkt',
    title: 'Rakordimi bankar me AI: nga 3 ore ne 3 minuta',
    publishDate: '15 mars 2026',
    readTime: '9 min lexim',
    heroAlt: 'Rakordimi bankar me AI Facturino - perputhje automatike e ekstrakteve bankare',
    intro: 'Cdo kontabilist dhe menaxher financiar e njeh problemin: fundi i muajit vjen, dhe me te vjen grumbulli i ekstrakteve bankare. 200, 300, ndonjehere edhe 500 transaksione qe duhet te perputhen manualisht me faturat, pagesat dhe regjistrat kontabel. Kjo eshte 3 ore pune monotone, cdo muaj. Facturino e zgjidh kete problem me teknologji inteligjente AI — i gjithe procesi i rakordimit bankar tani zgjat vetem 3 minuta. Regjistrim automatik, njohje inteligjente e transaksioneve dhe perputhshmeri e plote me ekstraktet bankare te te gjitha bankave maqedonase.',
    sections: [
      {
        title: 'Pipeline AI 4-shtresore per rakordim',
        content: 'Facturino perdor nje arkitekture te avancuar te pipeline AI me 4 shtresa per rakordimin e transaksioneve bankare. Secila shtrese shton nje nivel shtese inteligjence, duke siguruar saktesi maksimale dhe nevoje minimale per nderhyrje manuale. Ky qasje hibride kombinon rregulla deterministike me inteligjence artificiale per te arritur nje norme rakordimi automatik mbi 95%.',
        items: null,
        steps: [
          { step: 'Shtresa 1: Rregullat (Perputhje e sakte)', desc: 'Shtresa e pare perdor perputhje te sakte te bazuar ne rregulla. Kur nje pagese perputhet saktesisht me shumen e fatures, me te njejten date dhe numer reference — sistemi i lidh menjehere. Kjo mbulon rreth 60% te te gjitha transaksioneve: kliente te rregullt, pagesa mujore, shuma fikse. Perputhja eshte e menjehershme dhe 100% e sakte. Rregullat jane te konfigurueshme — mund te shtoni rregullat tuaja per furnizues ose kliente specifike.' },
          { step: 'Shtresa 2: Perputhje deterministike', desc: 'Per transaksionet qe nuk kapen nga shtresa e pare, shtresa e dyte aplikon perputhje deterministike sipas shumes, dates dhe pershkrimit. Per shembull, nese nje pagese eshte saktesisht 15,000 MKD dhe keni nje fature per te njejten shume te leshuar brenda nje afati kohor te afert — sistemi i lidh. Kjo shtrese trajton gjithashtu pagesat e pjesshme, ku klienti paguan nje fature ne disa keste. Algoritmi perdor pikë te peshuar per saktesine e shumes, afersin kohore dhe ngjashmerine tekstuale.' },
          { step: 'Shtresa 3: Permiresim AI (Perputhje fuzzy)', desc: 'Shtresa e trete eshte aty ku inteligjenca artificiale vertete shkëlqen. Modeli AI analizon rastet e paqarta: emra kompanish te shkruar ndryshe (p.sh. "DOOEL" vs "D.O.O.E.L."), shuma te rrumbullakosura, transaksione me vonese disa ditore. AI meson nga vendimet tuaja te meparshme — nese me pare keni lidhur nje pagese nga "PETROV TRADE" me klientin "Petrov Trade DOOEL", sistemi automatikisht i lidh ne te ardhmen. Kjo perputhje fuzzy perdor NLP (perpunim te gjuhes natyrale) per te kuptuar kontekstin e cdo transaksioni.' },
          { step: 'Shtresa 4: Kategorizim AI', desc: 'Shtresa e fundit trajton transaksionet krejtesisht te reja qe nuk kane perputhje me asnje fature. Ne vend qe t\'i le te parakordura, AI automatikisht i kategorizon: "Shpenzime per materiale zyre", "Sherbime komunale", "Komision per sherbim bankar". Kategorizimi meson nga regjistrat tuaja kontabel te meparshme dhe behet gjithmone me preciz me kalimin e kohes. Transaksionet e reja automatikisht marrin nje sugjerim per llogari dhe kategori, dhe ju thjesht konfirmoni ose ndryshoni me nje klik te vetem.' },
        ],
      },
      {
        title: 'Bankat e mbeshtetura ne Maqedoni',
        content: 'Facturino mbeshtet importin e ekstrakteve bankare nga te gjitha 9 bankat komerciale aktive ne Maqedoni. Pa marre parasysh nese perdorni eksport CSV nga e-bankingu, formatin standard MT940 ose ekstrakte PDF te skanuara — Facturino automatikisht i njeh dhe i parson transaksionet. Secila banke ka formatin e vet, dhe sistemi yne eshte i trajnuar per t\'i lexuar te gjitha variantet pa gabime.',
        items: [
          'NLB Banka — formate CSV, MT940 dhe PDF nga e-bankingu dhe iKliring',
          'Stopanska Banka AD Shkup — eksport permes e-bankingut ne CSV dhe MT940',
          'Komercijalna Banka — mbeshtetje per te gjitha formatet dixhitale te ekstrakteve',
          'Halkbank AD Shkup — import CSV nga platforma Halk e-banking',
          'Sparkasse Banka — MT940 dhe CSV nga NetBanking dhe SmartBanking',
          'ProCredit Banka — ekstrakte standarde CSV nga ProB@nking',
          'Silk Road Banka — formate CSV dhe PDF',
          'TTK Banka — eksport CSV nga bankingu elektronik',
          'Ohridska Banka — formate CSV dhe MT940',
        ],
        steps: null,
      },
      {
        title: 'Shembull real: 200 transaksione per 3 minuta',
        content: 'Le te imagjinojme nje skenar real: kontabilistja Marija punon per nje kompani tregtare me 200 transaksione mujore ne Stopanska Banka. Para Facturino, Marija kalonte 3 ore cdo muaj duke rakorduar manualisht ekstraktet. Me pipeline-in AI te Facturino, procesi duket krejt ndryshe:',
        items: [
          '185 transaksione (92.5%) — rakorduar automatikisht permes Shtreses 1 dhe 2 (rregulla dhe perputhje deterministike). Kliente te rregullt, qira mujore, pagesa fikse — gjithcka e lidhur menjehere.',
          '10 transaksione (5%) — AI sugjeroi perputhje permes Shtreses 3 (perputhje fuzzy). Shuma te rrumbullakosura, emra te ndryshem — AI i njohu ne baze te historise. Marija thjesht klikon "Konfirmo".',
          '5 transaksione (2.5%) — te reja, transaksione te panjohura. Shtresa 4 AI automatikisht i kategorizoi: komision bankar, shpenzime komunale, blerje e vogel. Marija shqyrton dhe korrigjion nese eshte e nevojshme.',
          'Koha totale: 3 minuta ne vend te 3 oreve. Kursim prej 2 oreve dhe 57 minutave cdo muaj.',
          'Kursimi vjetor: 35 ore kohe pune — ekuivalent me pothuajse nje jave te tere pune!',
        ],
        steps: null,
      },
      {
        title: 'Kush mund ta perdore rakordimin AI',
        content: 'Rakordimi bankar AI eshte i disponueshem per perdoruesit e planit Business te Facturino. Plani Business kushton 3,630 MKD ne muaj (59 EUR) dhe perfshine jo vetem rakordimin AI, por edhe lidhjet bankare PSD2, analitike te avancuar dhe fatura te pakufizuara. Per firmat e kontabilitetit qe jane pjese e programit tone te partneritetit, rakordimi AI eshte i perfshire falas — nje arsye shtese per t\'iu bashkuar programit.',
        items: [
          'Plani Business (3,630 MKD/muaj) — qasje e plote ne rakordimin AI, lidhjet PSD2 dhe analitike te avancuar',
          'Partnere (firma kontabiliteti) — qasje falas ne te gjitha funksionet AI permes programit te partneritetit',
          'Periudhe prove 14 ditore — testoni rakordimin AI falas ne planin Standard',
          'Planet Free dhe Starter — rakordim manual i disponueshem, permirëesim AI me nje klik',
        ],
        steps: null,
      },
      {
        title: 'PSD2 Open Banking — Niveli tjeter',
        content: 'Facturino eshte duke punuar aktivisht ne integrimin PSD2 (Open Banking) me bankat maqedonase. Se shpejti, ne vend qe te shkarkoni manualisht ekstrakte CSV ose PDF, llogaria juaj bankare do te jete e lidhur drejtperdrejt me Facturino. Transaksionet do te sinkronizohen automatikisht, dhe pipeline AI do t\'i rakordon ne kohe reale. Imagjinoni: cdo pagese e klientit automatikisht perputhet me faturen dhe regjistrohet, pa asnje nderhyrje nga ana juaj. Kjo eshte e ardhmja e kontabilitetit ne Maqedoni.',
        items: null,
        steps: null,
      },
      {
        title: 'Filloni me rakordimin automatik',
        content: 'Pergatitni ekstraktet tuaja bankare dhe shihni ndryshimin qe sot. Regjistrohuni per nje periudhe prove 14-ditore, lidhni banken tuaj dhe lini AI te beje punen. Procesi eshte i thjeshte: importoni ekstraktin, klikoni "Rakordo" dhe shqyrtoni rezultatet. Per me pak se 3 minuta, transaksionet tuaja bankare do te perputhen me faturat dhe regjistrat kontabel.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj te ngjashem',
    related: [
      { slug: 'cash-flow-mk', title: 'Menaxhimi i rrjedhes se parave ne Maqedoni' },
      { slug: 'upravljanje-so-rashodi', title: 'Menaxhimi i shpenzimeve: Udherrëfyes i plote' },
      { slug: 'digitalno-smetkovodstvo', title: 'Kontabiliteti dixhital vs tradicional' },
    ],
    cta: {
      title: 'Automatizoni rakordimin tuaj bankar',
      desc: 'Filloni falas dhe shihni se si AI i rakordon transaksionet tuaja per 3 minuta ne vend te 3 oreve.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga don',
    tag: 'Urun',
    title: 'AI Banka Mutabakati: 3 Saatten 3 Dakikaya',
    publishDate: '15 Mart 2026',
    readTime: '9 dk okuma',
    heroAlt: 'AI banka mutabakati Facturino - otomatik banka ekstresi eslestirme',
    intro: 'Her muhasebeci ve finans yoneticisi bu sorunu bilir: ay sonu gelir ve beraberinde banka ekstreleri yigini da gelir. 200, 300, bazen 500 islem faturalar, odemeler ve muhasebe kayitlariyla manuel olarak eslestirilmesi gerekir. Bu her ay 3 saat monoton calismadir. Facturino bu sorunu akilli AI teknolojisiyle cozuyor — tum banka mutabakat sureci artik sadece 3 dakika suruyor. Otomatik kayit, akilli islem tanima ve tum Makedon bankalarindan banka ekstreleriyle tam mutabakat.',
    sections: [
      {
        title: '4 Katmanli AI Mutabakat Pipeline',
        content: 'Facturino, banka islemlerinin mutabakati icin gelismis 4 katmanli bir AI pipeline mimarisi kullanir. Her katman ek bir zeka seviyesi ekleyerek maksimum dogruluk ve minimum manuel mudahale ihtiyaci saglar. Bu hibrit yaklasim, %95\'in uzerinde otomatik mutabakat orani elde etmek icin deterministik kurallari yapay zeka ile birlestirir.',
        items: null,
        steps: [
          { step: 'Katman 1: Kurallar (Tam Eslestirme)', desc: 'Ilk katman, kural tabanli tam eslestirme kullanir. Bir odeme, ayni tarih ve referans numarasi ile bir fatura tutarina tam olarak uydugundan — sistem onlari aninda baglar. Bu, tum islemlerin yaklasik %60\'ini kapsar: duzenli musteriler, aylik odemeler, sabit tutarlar. Eslestirme anlik ve %100 dogrudir. Kurallar yapilandirabilir — belirli tedarikciler veya musteriler icin kendi kurallarinizi ekleyebilirsiniz.' },
          { step: 'Katman 2: Deterministik Eslestirme', desc: 'Ilk katman tarafindan yakalanmayan islemler icin, ikinci katman tutar, tarih ve aciklamaya gore deterministik eslestirme uygular. Ornegin, bir odeme tam olarak 15.000 MKD ise ve yakin bir zaman diliminde kesilen ayni tutarda bir faturaniz varsa — sistem onlari baglar. Bu katman ayrica bir musterinin bir faturayi birden fazla taksitte odedigi kismi odemeleri de isler. Algoritma, tutar dogrulugu, zamansal yakinlik ve metinsel benzerlik icin agirlikli puanlar kullanir.' },
          { step: 'Katman 3: AI Gelistirme (Bulanik Eslestirme)', desc: 'Ucuncu katman, yapay zekanin gercekten parliadigi yerdir. AI modeli belirsiz vakalari analiz eder: farkli yazilmis sirket adlari (ornegin "DOOEL" vs "D.O.O.E.L."), yuvarlatilmis tutarlar, birkac gun gecikmeli islemler. AI onceki kararlarinizdan ogrenir — daha once "PETROV TRADE"den gelen bir odemeyi "Petrov Trade DOOEL" musterisiyle eslestirdiyseniz, sistem gelecekte otomatik olarak baglar. Bu bulanik eslestirme, her islemin baglamini anlamak icin NLP (dogal dil isleme) kullanir.' },
          { step: 'Katman 4: AI Kategorizasyon', desc: 'Son katman, hicbir faturayla eslesmeyen tamamen yeni islemleri ele alir. Bunlari mutabakatsiz birakmak yerine, AI otomatik olarak kategorize eder: "Ofis malzemesi gideri", "Hizmet bedelleri", "Banka hizmet ucreti". Kategorizasyon onceki muhasebe kayitlarinizdan ogrenir ve zamanla giderek daha hassas hale gelir. Yeni islemler otomatik olarak onerilen bir hesap ve kategori alir ve siz sadece tek bir tiklamayla onaylar veya degistirirsiniz.' },
        ],
      },
      {
        title: 'Makedonya\'da Desteklenen Bankalar',
        content: 'Facturino, Makedonya\'daki 9 aktif ticari bankanin tumunden banka ekstresi iceaktarimini destekler. E-bankaciliktan CSV disa aktarimi, standart MT940 formati veya taranmis PDF ekstreler kullaniyor olun — Facturino islemleri otomatik olarak tanir ve ayristirir. Her bankanin kendi formati vardir ve sistemimiz tum varyantlari hatasiz okumak icin egitilmistir.',
        items: [
          'NLB Banka — e-bankacilik ve iKliring\'den CSV, MT940 ve PDF formatlari',
          'Stopanska Banka AD Uskup — e-bankacilik uzerinden CSV ve MT940 disa aktarimi',
          'Komercijalna Banka — tum dijital ekstre formatlari destegi',
          'Halkbank AD Uskup — Halk e-bankacilik platformundan CSV ice aktarimi',
          'Sparkasse Banka — NetBanking ve SmartBanking\'den MT940 ve CSV',
          'ProCredit Banka — ProB@nking\'den standart CSV ekstreler',
          'Silk Road Banka — CSV ve PDF formatlari',
          'TTK Banka — elektronik bankaciliktan CSV disa aktarimi',
          'Ohridska Banka — CSV ve MT940 formatlari',
        ],
        steps: null,
      },
      {
        title: 'Gercek Ornek: 3 Dakikada 200 Islem',
        content: 'Gercek bir senaryo hayal edelim: muhasebeci Marija, Stopanska Banka\'da aylik 200 islemi olan bir ticaret sirketinde calisiyor. Facturino\'dan once Marija her ay ekstreleri manuel olarak mutabik kilarak 3 saat harciyordu. Facturino AI pipeline ile surecin nasil goz uktu:',
        items: [
          '185 islem (%92,5) — Katman 1 ve 2 ile otomatik mutabakat (kurallar ve deterministik eslestirme). Duzenli musteriler, aylik kiralar, sabit odemeler — her sey aninda baglandi.',
          '10 islem (%5) — AI, Katman 3 ile eslestirme onerdi (bulanik eslestirme). Yuvarlanan tutarlar, farkli adlar — AI gecmise dayanarak onlari tanidi. Marija sadece "Onayla"yi tiklar.',
          '5 islem (%2,5) — yeni, bilinmeyen islemler. AI Katman 4 otomatik olarak kategorize etti: banka ucreti, fatura giderleri, kucuk alisveris. Marija inceler ve gerekirse duzeltir.',
          'Toplam sure: 3 saat yerine 3 dakika. Her ay 2 saat 57 dakika tasarruf.',
          'Yillik tasarruf: 35 saat calisma suresi — neredeyse tam bir calisma haftasina esdeger!',
        ],
        steps: null,
      },
      {
        title: 'AI Mutabakatini Kimler Kullanabilir',
        content: 'AI banka mutabakati, Facturino Business plan kullanicilari icin mevcuttur. Business plan ayda 3.630 MKD\'dir (59 EUR) ve yalnizca AI mutabakatini degil, ayni zamanda PSD2 banka baglantilarini, gelismis analitigi ve sinirslz faturayi da icerir. Ortaklik programimizin parcasi olan muhasebe firmalari icin AI mutabakati ucretsiz olarak dahildir — programa katilmak icin ek bir neden.',
        items: [
          'Business plan (ayda 3.630 MKD) — AI mutabakati, PSD2 baglantilar ve gelismis analitik icin tam erisim',
          'Ortaklar (muhasebe firmalari) — ortaklik programi araciligiyla tum AI ozelliklerine ucretsiz erisim',
          '14 gunluk deneme suresi — Standard planda AI mutabakatini ucretsiz test edin',
          'Free ve Starter planlar — manuel mutabakat mevcut, tek tikla AI yukseltmesi',
        ],
        steps: null,
      },
      {
        title: 'PSD2 Acik Bankacilik — Sonraki Seviye',
        content: 'Facturino, Makedon bankalariyla PSD2 (Acik Bankacilik) entegrasyonu uzerinde aktif olarak calismaktadir. Yakinda, CSV veya PDF ekstreleri manuel olarak indirmek yerine banka hesabiniz dogrudan Facturino\'ya bagli olacak. Islemler otomatik olarak senkronize edilecek ve AI pipeline bunlari gercek zamanli olarak mutabik kilacak. Hayal edin: her musteri odemesi otomatik olarak faturayla eslestirilir ve kaydedilir, sizden herhangi bir mudahale olmadan. Bu, Makedonya\'da muhasebenin gelecegi.',
        items: null,
        steps: null,
      },
      {
        title: 'Otomatik Mutabakata Baslayin',
        content: 'Banka ekstrelerinizi hazirlayin ve farki bugun gorun. 14 gunluk deneme icin kaydolun, bankanizi baglayin ve AI\'nin isi yapmasina izin verin. Surec basittir: ekstreyi ice aktarin, "Mutabakat Yap" tiklayin ve sonuclari inceleyin. 3 dakikadan kisa surede banka islemleriniz faturalar ve muhasebe kayitlariyla eslestirilmis olacak.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Ilgili makaleler',
    related: [
      { slug: 'cash-flow-mk', title: 'Makedonya\'da Nakit Akisi Yonetimi' },
      { slug: 'upravljanje-so-rashodi', title: 'Gider Yonetimi: Eksiksiz Rehber' },
      { slug: 'digitalno-smetkovodstvo', title: 'Dijital vs Geleneksel Muhasebe' },
    ],
    cta: {
      title: 'Banka Mutabakatinizi Otomatiklestirin',
      desc: 'Ucretsiz baslayin ve AI\'nin islemlerinizi 3 saat yerine 3 dakikada nasil mutabik kildigini gorun.',
      button: 'Ucretsiz basla',
    },
  },
} as const

export default async function AiBankarskiUsoglasuvanjePage({
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
            src="/assets/images/blog/blog_ai_bank_reconciliation.png"
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
