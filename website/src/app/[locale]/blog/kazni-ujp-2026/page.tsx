import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/kazni-ujp-2026', {
    title: {
      mk: 'Казни УЈП 2026: Што се случува ако задоцните со пријавата',
      en: 'UJP Penalties North Macedonia 2026: What Happens If You File Late',
      sq: 'Gjobat e UJP 2026: Çfarë ndodh nëse dorëzoni me vonesë',
      tr: 'UJP Cezaları 2026: Geç Başvuru Yaparsanız Ne Olur',
    },
    description: {
      mk: 'Комплетен водич за казните на УЈП: доцнење ДДВ-04 до €5.000, задоцнет МПИН до €3.000, камата 0,03% дневно. Научете како да се заштитите.',
      en: 'Complete guide to UJP penalties in North Macedonia: late VAT returns up to €5,000, late MPIN up to €3,000, daily interest 0.03%. Learn how to protect your business.',
      sq: 'Udhëzues i plotë për gjobat e UJP: TVSH e vonuar deri €5.000, MPIN e vonuar deri €3.000, kamatë 0,03% në ditë. Mësoni si ta mbroni biznesin.',
      tr: 'UJP cezaları rehberi: geç KDV beyannamesi €5.000\'e kadar, geç MPIN €3.000\'e kadar, günlük %0,03 faiz. İşletmenizi nasıl korursunuz öğrenin.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'Казни УЈП 2026: Што се случува ако задоцните со пријавата',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro: 'Секој бизнис во Македонија мора да ги почитува даночните рокови определени од Управата за јавни приходи (УЈП). Но кога работата е напорна и роковите се кратат, грешки се случуваат. Последиците не се само административни — казните можат сериозно да го оптоваруваат буџетот на вашиот бизнис. Овој водич ги покрива сите видови прекршоци, износите на казните, каматните стапки и постапката за жалба, за да знаете точно што ве очекува — и како да го спречите.',
    sections: [
      {
        title: 'Видови прекршоци пред УЈП',
        content: 'Законот за даночна постапка и Законот за ДДВ предвидуваат казни за повеќе видови даночни прекршоци. Најчестите ситуации кои водат до санкции вклучуваат:',
        items: [
          'Ненавремено поднесување на ДДВ пријава (ДДВ-04) — рокот е 25-ти во месецот за претходниот период.',
          'Задоцнето поднесување на МПИН пријава за плати — рокот е 15-ти во месецот.',
          'Непоннесување на годишна сметка до Централниот регистар — рокот е 15 март.',
          'Задоцнета годишна даночна пријава за данок на добивка (ДБ-ВП) — рокот е 15 март.',
          'Неиздавање или нецелосно издавање фактури — секоја фактура мора да ги содржи сите задолжителни елементи.',
          'Неводење или нецелосно водење деловни книги — сметководствена евиденција е задолжителна за секое правно лице.',
        ],
        steps: null,
      },
      {
        title: 'Износи на казни по вид на прекршок',
        content: 'Казните варираат значително во зависност од типот на прекршокот и големината на субјектот. Подолу се вистинските распони на глоби предвидени во законодавството:',
        items: [
          'Ненавремена ДДВ пријава (ДДВ-04): €500 до €1.500 за мали субјекти; €1.500 до €5.000 за средни и големи субјекти.',
          'Задоцнет МПИН: €1.000 до €3.000 за правно лице, плус одговорно лице може да добие казна од €300 до €500 лично.',
          'Непоннесена годишна сметка (Годишна сметка): €1.500 до €3.000, плус можност за бришење од Централен регистар по две последователни години.',
          'Задоцнет данок на добивка (ДБ-ВП): €500 до €2.000 за правно лице, плус камата на целиот неплатен износ од денот на рокот.',
          'Фактури — неиздавање или погрешни податоци: €500 до €1.000 по фактура. При систематско непочитување, казните се кумулираат.',
          'Одговорно лице (управител): Покрај казната за фирмата, управителот лично може да биде казнет со €250 до €750 за секој прекршок.',
        ],
        steps: null,
      },
      {
        title: 'Затезна камата на неплатен данок',
        content: 'Покрај фиксните казни, Законот за даночна постапка предвидува затезна камата на секој неплатен даночен долг. Каматата тече од денот на истекот на рокот за плаќање до денот на целосната уплата.',
        items: [
          'Дневна стапка: 0,03% на неплатениот износ — што изнесува приближно 11% годишно.',
          'Каматата се пресметува автоматски — не треба да добиете известување. Системот на е-Даноци ја додава на секој неплатен долг.',
          'Пример: Ако должите 100.000 МКД данок и задоцните 90 дена, ќе платите дополнителни 2.700 МКД камата (100.000 × 0,03% × 90).',
          'Каматата тече и за време на жалбена постапка — освен ако судот не донесе решение за одложување.',
          'При принудна наплата (блокада на сметка), камата продолжува да тече до целосна исплата.',
        ],
        steps: null,
      },
      {
        title: 'Рокови за застарување',
        content: 'Правото на УЈП да утврди и наплати данок не трае вечно. Законот за даночна постапка предвидува рокови на застарување кои го ограничуваат периодот во кој УЈП може да ве санкционира.',
        items: null,
        steps: [
          { step: '5 години', desc: 'Стандартен рок за застарување на правото за утврдување и наплата на данок. Тече од 1 јануари во годината по годината во која требало да се плати данокот.' },
          { step: '10 години', desc: 'Апсолутен рок за застарување — без оглед на прекини, по 10 години данокот не може да се наплати.' },
          { step: '10 години (измама)', desc: 'За даночна измама (затајување данок, фалсификување документи), рокот за кривично гонење изнесува 10 години и тече независно од даночното застарување.' },
          { step: 'Прекин на застарување', desc: 'Секое дејствие на УЈП (инспекција, решение, опомена) го прекинува рокот и тој почнува одново. Затоа во пракса застарувањето ретко настапува.' },
        ],
      },
      {
        title: 'Постапка за жалба (приговор)',
        content: 'Ако сметате дека казната е неоснована или преголема, имате право на жалба. Постапката е јасно дефинирана со закон и треба да се следи внимателно за да се заштитите.',
        items: null,
        steps: [
          { step: 'Приговор во 15 дена', desc: 'По приемот на решението за казна, имате 15 дена да поднесете приговор (жалба) до второстепената комисија при Министерство за финансии.' },
          { step: 'Писмено образложение', desc: 'Приговорот мора да содржи конкретни правни и фактички причини зошто решението е неправилно. Генерално несогласување не е доволно.' },
          { step: 'Одговор од комисијата', desc: 'Второстепената комисија одлучува во рок од 60 дена. Може да го потврди, измени или укине решението.' },
          { step: 'Управен суд', desc: 'Ако приговорот е одбиен, можете да поднесете тужба до Управниот суд во рок од 30 дена. Судската постапка трае 6-12 месеци.' },
          { step: 'Виш управен суд', desc: 'Последна инстанца е Вишиот управен суд за конечна ревизија. Одлуката е правосилна и извршна.' },
        ],
      },
      {
        title: 'Амнестија и доброволно пријавување',
        content: 'Македонскиот даночен систем предвидува олеснувања за даночни обврзници кои доброволно ги пријавуваат своите грешки или пропусти пред да бидат откриени од инспекција.',
        items: [
          'Доброволно пријавување пред инспекција: Ако сами поднесете исправена пријава и го платите данокот пред УЈП да покрене постапка, казната се намалува за 50% до 70%.',
          'Корекција на ДДВ пријава: Можете да поднесете корегирана ДДВ-04 за претходни периоди без казна, доколку разликата е мала и нема елементи на измама.',
          'Договор за одложено плаќање: УЈП може да одобри плаќање на рати (до 12 месеци) за даночни долгови. Каматата тече, но нема принудна наплата.',
          'Програми за даночна амнестија: Владата периодично донесува закони за даночна амнестија кои овозможуваат отпис на камата и намалување на казни. Следете ги официјалните објави.',
          'Самопријавување на грешки во МПИН: Исправки на МПИН за претходни месеци се прифаќаат без санкции доколку разликите се минимални и платите се веќе исплатени.',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino ве заштитува од казни',
        content: 'Facturino е дизајниран специјално за македонскиот даночен систем. Наместо да зависите од паметење на датуми и рачни пресметки, системот автоматски работи за вас.',
        items: [
          'Потсетници за рокови: Email и push нотификации 7 дена и 2 дена пред секој даночен рок — ДДВ-04, МПИН, годишна сметка, данок на добивка.',
          'Автоматска пресметка на ДДВ: Врз основа на издадени и примени фактури, Facturino ја пресметува вашата ДДВ обврска во реално време.',
          'Генерирање на МПИН пријави: Директно од платниот список, без рачно внесување — без грешки, без казни.',
          'Даночен календар: Персонализиран календар со сите ваши рокови на едно место, синхронизиран со вашиот бизнис профил.',
          'Извоз за е-Даноци: Податоците се форматирани за директен увоз во порталот на УЈП, елиминирајќи грешки при рачно пренесување.',
          'Целосна ревизорска трага: Историја на сите поднесени документи, пресметки и промени — подготвени за секоја инспекција.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
    ],
    cta: {
      title: 'Заштитете го вашиот бизнис од казни',
      desc: 'Facturino автоматски ги следи сите даночни рокови и ве известува навреме. Започнете бесплатно и заборавете на стресот.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'UJP Penalties 2026: What Happens If You File Late',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro: 'Every business in North Macedonia must comply with tax filing deadlines set by the Public Revenue Office (UJP). But when work is overwhelming and deadlines pile up, mistakes happen. The consequences are not just administrative — penalties can seriously damage your business budget. This guide covers all types of violations, penalty amounts, interest rates, and the appeals process, so you know exactly what to expect — and how to prevent it.',
    sections: [
      {
        title: 'Types of UJP violations',
        content: 'The Law on Tax Procedure and the VAT Law prescribe penalties for several types of tax violations. The most common situations leading to sanctions include:',
        items: [
          'Late VAT return filing (DDV-04) — the deadline is the 25th of the month for the previous period.',
          'Late MPIN payroll return filing — the deadline is the 15th of the month.',
          'Failure to file annual accounts with the Central Registry — the deadline is March 15.',
          'Late corporate income tax return (DB-VP) — the deadline is March 15.',
          'Failure to issue invoices or issuing incomplete invoices — every invoice must contain all mandatory elements.',
          'Failure to maintain or incomplete maintenance of business books — accounting records are mandatory for every legal entity.',
        ],
        steps: null,
      },
      {
        title: 'Penalty amounts by violation type',
        content: 'Penalties vary significantly depending on the type of violation and the size of the entity. Below are the actual fine ranges prescribed by law:',
        items: [
          'Late VAT return (DDV-04): EUR 500 to EUR 1,500 for small entities; EUR 1,500 to EUR 5,000 for medium and large entities.',
          'Late MPIN: EUR 1,000 to EUR 3,000 for the legal entity, plus the responsible person may receive a personal fine of EUR 300 to EUR 500.',
          'Missing annual accounts (Godishna smetka): EUR 1,500 to EUR 3,000, plus possible deletion from the Central Registry after two consecutive years.',
          'Late corporate tax return (DB-VP): EUR 500 to EUR 2,000 for the legal entity, plus interest on the entire unpaid amount from the deadline date.',
          'Invoices — failure to issue or incorrect data: EUR 500 to EUR 1,000 per invoice. With systematic non-compliance, penalties accumulate.',
          'Responsible person (director): In addition to the company fine, the director may be personally fined EUR 250 to EUR 750 per violation.',
        ],
        steps: null,
      },
      {
        title: 'Daily interest on unpaid tax',
        content: 'In addition to fixed penalties, the Law on Tax Procedure prescribes default interest on every unpaid tax debt. Interest accrues from the day the payment deadline expires until the day of full payment.',
        items: [
          'Daily rate: 0.03% on the unpaid amount — which amounts to approximately 11% annually.',
          'Interest is calculated automatically — you do not need to receive a notification. The e-Danoci system adds it to every unpaid debt.',
          'Example: If you owe 100,000 MKD in tax and are 90 days late, you will pay an additional 2,700 MKD in interest (100,000 x 0.03% x 90).',
          'Interest accrues even during the appeals process — unless the court issues a decision to suspend it.',
          'During forced collection (account freeze), interest continues to accrue until full payment.',
        ],
        steps: null,
      },
      {
        title: 'Statute of limitations',
        content: 'The right of UJP to assess and collect tax does not last forever. The Law on Tax Procedure prescribes limitation periods that restrict the period in which UJP can sanction you.',
        items: null,
        steps: [
          { step: '5 years', desc: 'Standard limitation period for the right to assess and collect tax. Runs from January 1 of the year following the year in which the tax should have been paid.' },
          { step: '10 years', desc: 'Absolute limitation period — regardless of interruptions, after 10 years the tax cannot be collected.' },
          { step: '10 years (fraud)', desc: 'For tax fraud (tax evasion, document falsification), the criminal prosecution period is 10 years and runs independently of the tax limitation.' },
          { step: 'Interruption of limitation', desc: 'Any action by UJP (inspection, decision, reminder) interrupts the period and it restarts. This is why in practice, limitation rarely occurs.' },
        ],
      },
      {
        title: 'How to appeal (filing an objection)',
        content: 'If you believe the penalty is unfounded or excessive, you have the right to appeal. The procedure is clearly defined by law and should be followed carefully to protect yourself.',
        items: null,
        steps: [
          { step: 'Objection within 15 days', desc: 'After receiving the penalty decision, you have 15 days to file an objection (appeal) to the second-instance commission at the Ministry of Finance.' },
          { step: 'Written reasoning', desc: 'The objection must contain specific legal and factual reasons why the decision is incorrect. General disagreement is not sufficient.' },
          { step: 'Commission response', desc: 'The second-instance commission decides within 60 days. It can confirm, modify, or annul the decision.' },
          { step: 'Administrative Court', desc: 'If the objection is rejected, you can file a lawsuit with the Administrative Court within 30 days. Court proceedings last 6-12 months.' },
          { step: 'Higher Administrative Court', desc: 'The last instance is the Higher Administrative Court for final review. The decision is final and enforceable.' },
        ],
      },
      {
        title: 'Amnesty and voluntary disclosure',
        content: 'The Macedonian tax system provides relief for taxpayers who voluntarily report their errors or omissions before being discovered by inspection.',
        items: [
          'Voluntary disclosure before inspection: If you independently submit a corrected return and pay the tax before UJP initiates proceedings, the penalty is reduced by 50% to 70%.',
          'VAT return correction: You can submit a corrected DDV-04 for previous periods without penalty, provided the difference is small and there are no elements of fraud.',
          'Deferred payment agreement: UJP can approve installment payments (up to 12 months) for tax debts. Interest accrues, but there is no forced collection.',
          'Tax amnesty programs: The government periodically passes tax amnesty laws that allow interest write-offs and penalty reductions. Monitor official announcements.',
          'Self-reporting MPIN errors: MPIN corrections for previous months are accepted without sanctions if the differences are minimal and salaries have already been paid.',
        ],
        steps: null,
      },
      {
        title: 'How Facturino protects you from penalties',
        content: 'Facturino is designed specifically for the Macedonian tax system. Instead of relying on memorizing dates and manual calculations, the system works automatically for you.',
        items: [
          'Deadline reminders: Email and push notifications 7 days and 2 days before every tax deadline — DDV-04, MPIN, annual accounts, corporate tax.',
          'Automatic VAT calculation: Based on issued and received invoices, Facturino calculates your VAT obligation in real time.',
          'MPIN return generation: Directly from the payroll list, without manual entry — no errors, no penalties.',
          'Tax calendar: A personalized calendar with all your deadlines in one place, synchronized with your business profile.',
          'Export for e-Danoci: Data is formatted for direct import into the UJP portal, eliminating errors from manual data transfer.',
          'Complete audit trail: History of all submitted documents, calculations, and changes — ready for any inspection.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
      { slug: 'ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
    ],
    cta: {
      title: 'Protect your business from penalties',
      desc: 'Facturino automatically tracks all tax deadlines and notifies you on time. Start free and forget the stress.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Gjobat e UJP 2026: Çfarë ndodh nëse dorëzoni me vonesë',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro: 'Çdo biznes në Maqedoninë e Veriut duhet t\'i respektojë afatet e dorëzimit tatimor të përcaktuara nga Zyra e të Ardhurave Publike (UJP). Por kur puna është e ngarkuar dhe afatet afrohen, gabimet ndodhin. Pasojat nuk janë vetëm administrative — gjobat mund ta dëmtojnë seriozisht buxhetin e biznesit tuaj. Ky udhëzues mbulon të gjitha llojet e shkeljeve, shumat e gjobave, normat e kamatës dhe procedurën e ankimimit, që të dini saktësisht çfarë ju pret — dhe si ta parandaloni.',
    sections: [
      {
        title: 'Llojet e shkeljeve para UJP',
        content: 'Ligji për procedurën tatimore dhe Ligji për TVSH-në parashikojnë gjoba për disa lloje të shkeljeve tatimore. Situatat më të zakonshme që çojnë në sanksione përfshijnë:',
        items: [
          'Dorëzim i vonuar i deklaratës së TVSH-së (DDV-04) — afati është data 25 e muajit për periudhën paraprake.',
          'Dorëzim i vonuar i deklaratës MPIN për paga — afati është data 15 e muajit.',
          'Mosdorëzimi i llogarive vjetore në Regjistrin Qendror — afati është 15 mars.',
          'Deklaratë e vonuar e tatimit mbi fitimin (DB-VP) — afati është 15 mars.',
          'Moslëshimi i faturave ose lëshimi i faturave jo të plota — çdo faturë duhet të përmbajë të gjitha elementet e detyrueshme.',
          'Mosmbajtja ose mbajtja jo e plotë e librave të biznesit — evidenca kontabël është e detyrueshme për çdo subjekt juridik.',
        ],
        steps: null,
      },
      {
        title: 'Shumat e gjobave sipas llojit të shkeljes',
        content: 'Gjobat ndryshojnë ndjeshëm në varësi të llojit të shkeljes dhe madhësisë së subjektit. Më poshtë janë intervalet e vërteta të gjobave të parashikuara me ligj:',
        items: [
          'TVSH e vonuar (DDV-04): €500 deri €1.500 për subjekte të vogla; €1.500 deri €5.000 për subjekte të mesme dhe të mëdha.',
          'MPIN e vonuar: €1.000 deri €3.000 për subjektin juridik, plus personi përgjegjës mund të gjobitet personalisht me €300 deri €500.',
          'Llogaritë vjetore të padërguara: €1.500 deri €3.000, plus mundësia e fshirjes nga Regjistri Qendror pas dy viteve radhazi.',
          'Tatimi mbi fitimin i vonuar (DB-VP): €500 deri €2.000 për subjektin juridik, plus kamatë mbi të gjithë shumën e papaguar nga data e afatit.',
          'Faturat — moslëshim ose të dhëna të gabuara: €500 deri €1.000 për faturë. Me mosrespektim sistematik, gjobat grumbullohen.',
          'Personi përgjegjës (drejtori): Përveç gjobës së kompanisë, drejtori mund të gjobitet personalisht me €250 deri €750 për çdo shkelje.',
        ],
        steps: null,
      },
      {
        title: 'Kamata ditore mbi tatimin e papaguar',
        content: 'Përveç gjobave fikse, Ligji për procedurën tatimore parashikon kamatë ndëshkuese mbi çdo borxh tatimor të papaguar. Kamata rrjedh nga dita e skadimit të afatit të pagesës deri në ditën e pagesës së plotë.',
        items: [
          'Norma ditore: 0,03% mbi shumën e papaguar — që arrin afërsisht 11% në vit.',
          'Kamata llogaritet automatikisht — nuk keni nevojë të merrni njoftim. Sistemi e-Danoci e shton në çdo borxh të papaguar.',
          'Shembull: Nëse keni 100.000 MKD borxh tatimor dhe jeni 90 ditë vonë, do të paguani 2.700 MKD kamatë shtesë (100.000 × 0,03% × 90).',
          'Kamata rrjedh edhe gjatë procedurës së ankimimit — përveç nëse gjykata vendos pezullimin.',
          'Gjatë arkëtimit të detyruar (bllokimi i llogarisë), kamata vazhdon deri në pagesën e plotë.',
        ],
        steps: null,
      },
      {
        title: 'Afatet e parashkrimit',
        content: 'E drejta e UJP për të vlerësuar dhe mbledhur tatimin nuk zgjat përgjithmonë. Ligji për procedurën tatimore parashikon afate parashkrimi që kufizojnë periudhën brenda së cilës UJP mund t\'ju sanksionojë.',
        items: null,
        steps: [
          { step: '5 vite', desc: 'Afati standard i parashkrimit për të drejtën e vlerësimit dhe mbledhjes së tatimit. Fillon nga 1 janari i vitit pas vitit në të cilin tatimi duhej paguar.' },
          { step: '10 vite', desc: 'Afati absolut i parashkrimit — pavarësisht ndërprerjeve, pas 10 viteve tatimi nuk mund të mblidhet.' },
          { step: '10 vite (mashtrim)', desc: 'Për mashtrim tatimor (evazion tatimor, falsifikim dokumentesh), afati i ndjekjes penale është 10 vite dhe rrjedh pavarësisht parashkrimit tatimor.' },
          { step: 'Ndërprerja e parashkrimit', desc: 'Çdo veprim i UJP (inspektim, vendim, kujtesë) e ndërpret afatin dhe ai fillon nga e para. Prandaj në praktikë parashkrimi ndodh rrallë.' },
        ],
      },
      {
        title: 'Procedura e ankimimit',
        content: 'Nëse mendoni se gjoba është e pabazuar ose e tepruar, keni të drejtën e ankimimit. Procedura është e përcaktuar qartë me ligj dhe duhet ndjekur me kujdes për të mbrojtur veten.',
        items: null,
        steps: [
          { step: 'Ankimim brenda 15 ditëve', desc: 'Pas marrjes së vendimit për gjobë, keni 15 ditë për të paraqitur ankimim në komisionin e shkallës së dytë pranë Ministrisë së Financave.' },
          { step: 'Arsyetim me shkrim', desc: 'Ankimimi duhet të përmbajë arsye specifike juridike dhe faktike pse vendimi është i gabuar. Mospajtimi i përgjithshëm nuk mjafton.' },
          { step: 'Përgjigja e komisionit', desc: 'Komisioni i shkallës së dytë vendos brenda 60 ditëve. Mund ta konfirmojë, ndryshojë ose anulojë vendimin.' },
          { step: 'Gjykata Administrative', desc: 'Nëse ankimimi refuzohet, mundeni të paraqisni padi në Gjykatën Administrative brenda 30 ditëve. Procedura gjyqësore zgjat 6-12 muaj.' },
          { step: 'Gjykata e Lartë Administrative', desc: 'Instanca e fundit është Gjykata e Lartë Administrative për rishikim përfundimtar. Vendimi është i formës së prerë dhe i ekzekutueshëm.' },
        ],
      },
      {
        title: 'Amnistia dhe vetëdeklarimi vullnetar',
        content: 'Sistemi tatimor maqedonas parashikon lehtësime për tatimpaguesit që vullnetarisht i raportojnë gabimet ose lëshimet e tyre para se të zbulohen nga inspektimi.',
        items: [
          'Vetëdeklarim para inspektimit: Nëse pavarësisht dorëzoni deklaratë të korrigjuar dhe paguani tatimin para se UJP të fillojë procedurë, gjoba zvogëlohet me 50% deri 70%.',
          'Korrigjim i deklaratës së TVSH-së: Mundeni të dorëzoni DDV-04 të korrigjuar për periudha paraprake pa gjobë, me kusht që diferenca të jetë e vogël dhe pa elemente mashtrimi.',
          'Marrëveshje për pagesë me këste: UJP mund të miratojë pagesa me këste (deri 12 muaj) për borxhe tatimore. Kamata rrjedh, por nuk ka arkëtim të detyruar.',
          'Programe amnistie tatimore: Qeveria periodikisht nxjerr ligje për amnisti tatimore që mundësojnë fshirjen e kamatës dhe zvogëlimin e gjobave. Ndiqni njoftimet zyrtare.',
          'Vetëraportim i gabimeve MPIN: Korrigjimet e MPIN për muajt paraprake pranohen pa sanksione nëse diferencat janë minimale dhe pagat janë paguar tashmë.',
        ],
        steps: null,
      },
      {
        title: 'Si ju mbron Facturino nga gjobat',
        content: 'Facturino është projektuar posaçërisht për sistemin tatimor maqedonas. Në vend që të vareni nga mbajtja mend e datave dhe llogaritjet manuale, sistemi punon automatikisht për ju.',
        items: [
          'Kujtesa për afate: Njoftime me email dhe push 7 ditë dhe 2 ditë para çdo afati tatimor — DDV-04, MPIN, llogaritë vjetore, tatimi mbi fitimin.',
          'Llogaritje automatike e TVSH-së: Bazuar në faturat e lëshuara dhe të marra, Facturino e llogarit detyrimin tuaj të TVSH-së në kohë reale.',
          'Gjenerimi i deklaratës MPIN: Direkt nga lista e pagave, pa futje manuale — pa gabime, pa gjoba.',
          'Kalendari tatimor: Kalendar i personalizuar me të gjitha afatet tuaja në një vend, i sinkronizuar me profilin e biznesit tuaj.',
          'Eksport për e-Danoci: Të dhënat janë të formatuara për import direkt në portalin e UJP, duke eliminuar gabimet nga transferimi manual.',
          'Gjurmë e plotë auditimi: Histori e të gjitha dokumenteve të dorëzuara, llogaritjeve dhe ndryshimeve — gati për çdo inspektim.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet e UJP' },
      { slug: 'ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
    ],
    cta: {
      title: 'Mbroni biznesin tuaj nga gjobat',
      desc: 'Facturino ndjek automatikisht të gjitha afatet tatimore dhe ju njofton në kohë. Filloni falas dhe harroni stresin.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'UJP Cezaları 2026: Geç Başvuru Yaparsanız Ne Olur',
    publishDate: '23 Mayıs 2026',
    readTime: '10 dk okuma',
    intro: 'Kuzey Makedonya\'daki her işletme, Kamu Gelir İdaresi (UJP) tarafından belirlenen vergi beyanname son tarihlerine uymak zorundadır. Ancak iş yoğunluğu artıp son tarihler yaklaştığında hatalar kaçınılmazdır. Sonuçlar sadece idari değildir — cezalar işletmenizin bütçesine ciddi zarar verebilir. Bu rehber tüm ihlal türlerini, ceza tutarlarını, faiz oranlarını ve itiraz sürecini kapsamaktadır; böylece sizi tam olarak neyin beklediğini — ve nasıl önleyeceğinizi bilirsiniz.',
    sections: [
      {
        title: 'UJP ihlal türleri',
        content: 'Vergi Usul Kanunu ve KDV Kanunu, çeşitli vergi ihlalleri için cezalar öngörmektedir. Yaptırımlara yol açan en yaygın durumlar şunlardır:',
        items: [
          'Geç KDV beyannamesi (DDV-04) — son tarih, önceki dönem için ayın 25\'idir.',
          'Geç MPIN bordro beyannamesi — son tarih ayın 15\'idir.',
          'Yıllık hesapların Merkez Sicile verilmemesi — son tarih 15 Mart\'tır.',
          'Geç kurumlar vergisi beyannamesi (DB-VP) — son tarih 15 Mart\'tır.',
          'Fatura düzenlememek veya eksik fatura düzenlemek — her fatura tüm zorunlu unsurları içermelidir.',
          'İş defterlerinin tutulmaması veya eksik tutulması — muhasebe kayıtları her tüzel kişi için zorunludur.',
        ],
        steps: null,
      },
      {
        title: 'İhlal türüne göre ceza tutarları',
        content: 'Cezalar, ihlalin türüne ve işletmenin büyüklüğüne bağlı olarak önemli ölçüde değişmektedir. Aşağıda yasayla öngörülen gerçek ceza aralıkları yer almaktadır:',
        items: [
          'Geç KDV beyannamesi (DDV-04): Küçük işletmeler için €500 ile €1.500 arası; orta ve büyük işletmeler için €1.500 ile €5.000 arası.',
          'Geç MPIN: Tüzel kişi için €1.000 ile €3.000 arası, ayrıca sorumlu kişi €300 ile €500 arası kişisel ceza alabilir.',
          'Eksik yıllık hesaplar: €1.500 ile €3.000 arası, ayrıca art arda iki yıl sonunda Merkez Sicilden silinme olasılığı.',
          'Geç kurumlar vergisi beyannamesi (DB-VP): Tüzel kişi için €500 ile €2.000 arası, ayrıca son tarihten itibaren tüm ödenmemiş tutar üzerinden faiz.',
          'Fatura — düzenlememe veya yanlış veri: Fatura başına €500 ile €1.000 arası. Sistematik uyumsuzlukta cezalar birikir.',
          'Sorumlu kişi (müdür): Şirket cezasına ek olarak, müdür her ihlal için kişisel olarak €250 ile €750 arası cezalandırılabilir.',
        ],
        steps: null,
      },
      {
        title: 'Ödenmemiş vergi üzerinden günlük faiz',
        content: 'Sabit cezalara ek olarak, Vergi Usul Kanunu her ödenmemiş vergi borcu üzerinden gecikme faizi öngörmektedir. Faiz, ödeme süresinin dolduğu günden tam ödeme gününe kadar işler.',
        items: [
          'Günlük oran: Ödenmemiş tutar üzerinden %0,03 — yıllık yaklaşık %11\'e tekabül eder.',
          'Faiz otomatik olarak hesaplanır — bildirim almanıza gerek yoktur. e-Danoci sistemi bunu her ödenmemiş borca ekler.',
          'Örnek: 100.000 MKD vergi borcunuz varsa ve 90 gün geciktiyseniz, 2.700 MKD ek faiz ödersiniz (100.000 × %0,03 × 90).',
          'Faiz, itiraz süreci sırasında da işlemeye devam eder — mahkeme askıya alma kararı vermedikçe.',
          'Zorla tahsilat (hesap dondurma) sırasında faiz, tam ödeme yapılana kadar işlemeye devam eder.',
        ],
        steps: null,
      },
      {
        title: 'Zamanaşımı süreleri',
        content: 'UJP\'nin vergi tarh etme ve tahsil etme hakkı sonsuza kadar sürmez. Vergi Usul Kanunu, UJP\'nin sizi yaptırıma uğratabileceği süreyi kısıtlayan zamanaşımı süreleri öngörmektedir.',
        items: null,
        steps: [
          { step: '5 yıl', desc: 'Vergi tarh ve tahsil hakkı için standart zamanaşımı süresi. Verginin ödenmesi gereken yılı takip eden yılın 1 Ocak\'ından itibaren işler.' },
          { step: '10 yıl', desc: 'Mutlak zamanaşımı süresi — kesintilere bakılmaksızın, 10 yıl sonra vergi tahsil edilemez.' },
          { step: '10 yıl (dolandırıcılık)', desc: 'Vergi dolandırıcılığı (vergi kaçırma, belge sahteciliği) için cezai kovuşturma süresi 10 yıldır ve vergi zamanaşımından bağımsız olarak işler.' },
          { step: 'Zamanaşımının kesilmesi', desc: 'UJP\'nin herhangi bir eylemi (teftiş, karar, hatırlatma) süreyi keser ve yeniden başlatır. Bu nedenle uygulamada zamanaşımı nadiren gerçekleşir.' },
        ],
      },
      {
        title: 'İtiraz süreci',
        content: 'Cezanın haksız veya aşırı olduğunu düşünüyorsanız, itiraz hakkınız vardır. Prosedür kanunla açıkça belirlenmiştir ve kendinizi korumak için dikkatle takip edilmelidir.',
        items: null,
        steps: [
          { step: '15 gün içinde itiraz', desc: 'Ceza kararını aldıktan sonra, Maliye Bakanlığı bünyesindeki ikinci derece komisyona itiraz dilekçesi vermek için 15 gününüz vardır.' },
          { step: 'Yazılı gerekçe', desc: 'İtiraz, kararın neden yanlış olduğuna dair somut hukuki ve fiili gerekçeler içermelidir. Genel bir itiraz yeterli değildir.' },
          { step: 'Komisyon yanıtı', desc: 'İkinci derece komisyon 60 gün içinde karar verir. Kararı onaylayabilir, değiştirebilir veya iptal edebilir.' },
          { step: 'İdare Mahkemesi', desc: 'İtiraz reddedilirse, 30 gün içinde İdare Mahkemesine dava açabilirsiniz. Yargılama süreci 6-12 ay sürer.' },
          { step: 'Yüksek İdare Mahkemesi', desc: 'Son merci, nihai inceleme için Yüksek İdare Mahkemesidir. Karar kesin ve icra edilebilirdir.' },
        ],
      },
      {
        title: 'Af ve gönüllü beyan',
        content: 'Makedonya vergi sistemi, hatalarını veya eksikliklerini denetim tarafından keşfedilmeden önce gönüllü olarak bildiren mükelleflere kolaylıklar sağlamaktadır.',
        items: [
          'Denetim öncesi gönüllü beyan: Bağımsız olarak düzeltilmiş beyanname verip vergiyi UJP işlem başlatmadan önce öderseniz, ceza %50 ile %70 oranında azaltılır.',
          'KDV beyannamesi düzeltmesi: Farkın küçük olması ve dolandırıcılık unsuru bulunmaması koşuluyla, önceki dönemler için düzeltilmiş DDV-04 cezasız verilebilir.',
          'Ertelenmiş ödeme anlaşması: UJP, vergi borçları için taksitli ödemeyi (12 aya kadar) onaylayabilir. Faiz işler, ancak zorla tahsilat yapılmaz.',
          'Vergi affı programları: Hükümet dönemsel olarak faiz silme ve ceza indirimi sağlayan vergi affı kanunları çıkarmaktadır. Resmi duyuruları takip edin.',
          'MPIN hatalarının öz bildirimi: Farklar minimal olup maaşlar zaten ödenmişse, önceki aylar için MPIN düzeltmeleri yaptırımsız kabul edilir.',
        ],
        steps: null,
      },
      {
        title: 'Facturino sizi cezalardan nasıl korur',
        content: 'Facturino özellikle Makedonya vergi sistemi için tasarlanmıştır. Tarihleri ezberlemeye ve manuel hesaplamalara güvenmek yerine, sistem sizin için otomatik olarak çalışır.',
        items: [
          'Son tarih hatırlatmaları: Her vergi son tarihinden 7 gün ve 2 gün önce e-posta ve anlık bildirimler — DDV-04, MPIN, yıllık hesaplar, kurumlar vergisi.',
          'Otomatik KDV hesaplaması: Düzenlenen ve alınan faturalara dayalı olarak Facturino, KDV yükümlülüğünüzü gerçek zamanlı hesaplar.',
          'MPIN beyanname oluşturma: Doğrudan bordro listesinden, manuel giriş olmadan — hata yok, ceza yok.',
          'Vergi takvimi: Tüm son tarihleriniz tek bir yerde, işletme profilinizle senkronize kişiselleştirilmiş takvim.',
          'e-Danoci dışa aktarımı: Veriler, UJP portalına doğrudan aktarım için biçimlendirilmiştir ve manuel veri aktarımındaki hataları ortadan kaldırır.',
          'Eksiksiz denetim izi: Tüm sunulan belgelerin, hesaplamaların ve değişikliklerin geçmişi — her denetime hazır.',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'rokovi-ujp-2026', title: 'Vergi takvimi 2026: Tüm UJP son tarihleri' },
      { slug: 'ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, son tarihler ve hesaplama' },
    ],
    cta: {
      title: 'İşletmenizi cezalardan koruyun',
      desc: 'Facturino tüm vergi son tarihlerini otomatik olarak takip eder ve zamanında bilgilendirir. Ücretsiz başlayın ve stresi unutun.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function KazniUjp2026Page({
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
    slug: 'kazni-ujp-2026',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['казни УЈП', 'UJP penalties', 'доцнење пријава УЈП', 'глоба данок Македонија', 'tax fines', 'VAT penalty', 'MPIN late'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/kazni-ujp-2026` },
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
