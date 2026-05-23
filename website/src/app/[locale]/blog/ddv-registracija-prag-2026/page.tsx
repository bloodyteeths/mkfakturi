import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/ddv-registracija-prag-2026', {
    title: {
      mk: 'ДДВ регистрација во Македонија 2026: Праг, постапка и обврски',
      en: 'VAT Registration in North Macedonia 2026: Threshold, Process & Obligations',
      sq: 'Regjistrimi për TVSH në Maqedoninë e Veriut 2026: Pragu, procesi dhe detyrimet',
      tr: 'Kuzey Makedonya KDV Kaydı 2026: Eşik, Süreç ve Yükümlülükler',
    },
    description: {
      mk: 'Комплетен водич за ДДВ регистрација: праг од 2.000.000 МКД, задолжителна vs доброволна регистрација, постапка во УЈП, даночни стапки (18%, 5%, 10%, 0%), ДДВ-04 пријава и како Facturino помага.',
      en: 'Complete guide to VAT registration in North Macedonia: 2,000,000 MKD threshold, mandatory vs voluntary registration, UJP process, tax rates (18%, 5%, 10%, 0%), DDV-04 filing and how Facturino helps.',
      sq: 'Udhëzues i plotë për regjistrimin e TVSH-së: pragu 2.000.000 MKD, regjistrimi i detyrueshëm vs vullnetar, procesi në UJP, normat tatimore (18%, 5%, 10%, 0%), deklarata DDV-04 dhe si ndihmon Facturino.',
      tr: 'KDV kaydı için eksiksiz rehber: 2.000.000 MKD eşiği, zorunlu vs gönüllü kayıt, UJP süreci, vergi oranları (%18, %5, %10, %0), DDV-04 beyannamesi ve Facturino nasıl yardımcı olur.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'ДДВ регистрација во Македонија 2026: Праг, постапка и обврски',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro: 'Данокот на додадена вредност (ДДВ) е најважниот индиректен данок во Македонија. Секој бизнис кој го надминува годишниот праг од 2.000.000 МКД промет е должен да се регистрира како ДДВ обврзник. Во овој водич детално објаснуваме кој мора да се регистрира, предностите на доброволната регистрација, целата постапка во УЈП, даночните стапки, обврските по регистрацијата, најчестите грешки и како Facturino го автоматизира целиот процес.',
    sections: [
      {
        title: 'Кој мора да се регистрира за ДДВ?',
        content: 'Согласно Законот за данокот на додадена вредност (Сл. весник 44/99, последни измени 2024), регистрацијата за ДДВ е задолжителна кога вкупниот годишен промет го надминува прагот од 2.000.000 МКД. Ова вклучува сите оданочиви испораки на добра и услуги извршени во рамките на деловната активност.',
        items: [
          'Задолжителна регистрација: промет над 2.000.000 МКД во претходните 12 месеци',
          'Доброволна регистрација: можна за секој бизнис без оглед на прометот',
          'Странски субјекти: задолжителна регистрација при вршење оданочиви активности во земјата',
          'Ново-основани фирми: можат доброволно да се регистрираат од денот на основањето',
          'Земјоделци и занаетчии: исто подлежат ако го надминат прагот',
          'Рокот за пријавување е 15 дена од денот на надминување на прагот',
        ],
        steps: null,
      },
      {
        title: 'Предности на доброволна регистрација',
        content: 'Иако прагот од 2.000.000 МКД може да изгледа далеку за помали бизниси, доброволната ДДВ регистрација носи значајни предности:',
        items: [
          'Одбивање на влезен ДДВ: враќање на ДДВ платен на набавки, опрема и услуги',
          'Кредибилитет: ДДВ бројот сигнализира сериозност и стабилност кај деловните партнери',
          'B2G подобност: државните тендери бараат ДДВ регистрација — без неа, не можете да учествувате',
          'Конкурентска предност: B2B клиентите претпочитаат добавувачи со ДДВ бидејќи и тие го одбиваат',
          'Нема казни за ненамерно надминување: ако растете брзо, веќе сте регистрирани',
          'Право на поврат: ако влезниот ДДВ > излезниот, УЈП ви враќа разлика',
        ],
        steps: null,
      },
      {
        title: 'Постапка за регистрација',
        content: 'Регистрацијата за ДДВ се врши во Управата за јавни приходи (УЈП). Процесот е електронски преку e-Tax порталот:',
        items: null,
        steps: [
          { step: 'Подгответе ги потребните документи', desc: 'Решение за регистрација од Централен регистар (ЦРРМ), тековна состојба не постара од 6 месеци, доказ за отворена банкарска сметка и бизнис план со проекции на прометот.' },
          { step: 'Регистрирајте се на etax.ujp.gov.mk', desc: 'Ако немате дигитален сертификат, набавете го од КИБС или друг овластен издавач. Потоа регистрирајте се на e-Tax порталот на УЈП.' },
          { step: 'Поднесете барање ДДВ-01', desc: 'Пополнете го образецот ДДВ-01 (Барање за регистрација за ДДВ) со сите податоци за фирмата, видот на дејноста и проектираниот годишен промет.' },
          { step: 'УЈП проверка и одобрување', desc: 'УЈП го разгледува барањето во рок од 15 работни дена. Може да побараат дополнителна документација или инспекција на деловниот простор.' },
          { step: 'Добијте ДДВ број (МК + EDB)', desc: 'По одобрувањето, добивате ДДВ идентификациски број кој почнува со МК. Овој број задолжително се наведува на секоја фактура.' },
          { step: 'Започнете со ДДВ евиденција', desc: 'Од денот на регистрацијата сте должни да водите ДДВ-01 (книга на влезни фактури) и ДДВ-02 (книга на излезни фактури) и да поднесувате квартална ДДВ-04 пријава.' },
        ],
      },
      {
        title: 'Стапки на ДДВ',
        content: 'Македонија применува четири стапки на ДДВ. Правилното класифицирање на стапката е клучно за усогласеност:',
        items: [
          '18% стандардна стапка: се применува на повеќето добра и услуги (електроника, професионални услуги, облека)',
          '5% намалена стапка: основни прехранбени производи, лекови, медицински помагала, учебници, книги',
          '10% стапка за угостителство: хотелско сместување, ресторански услуги (воведена 2019)',
          '0% нулта стапка: извоз на добра, меѓународен транспорт, испораки за дипломатски претставништва',
          'Ослободени без право на одбивање: финансиски услуги, осигурување, здравствени услуги, образование',
          'Мешан промет: ако имате и оданочиви и ослободени испораки, пропорционално ги делите влезните ДДВ кредити',
        ],
        steps: null,
      },
      {
        title: 'Обврски по регистрацијата',
        content: 'Откако ќе се регистрирате како ДДВ обврзник, имате низа законски обврски кои мора да ги исполнувате:',
        items: [
          'ДДВ-04 квартална пријава: се поднесува до 25-ти во месецот по завршувањето на кварталот (Q1→25 април, Q2→25 јули, Q3→25 октомври, Q4→25 јануари)',
          'ДДВ-01 книга: евиденција на сите влезни фактури со ДДВ, по добавувач, со износ и стапка',
          'ДДВ-02 книга: евиденција на сите излезни фактури со ДДВ, по купувач, со износ и стапка',
          'Фактурирање: секоја фактура мора да содржи ДДВ број, стапка, износ на ДДВ и вкупен износ',
          'Чување документација: фактури и книги мора да се чуваат минимум 10 години',
          'Месечно плаќање: ДДВ се плаќа до 25-ти во наредниот месец (или квартално, зависно од обемот)',
          'Промена на податоци: секоја промена (адреса, дејност, банкарска сметка) мора да се пријави во рок од 15 дена',
        ],
        steps: null,
      },
      {
        title: 'Најчести грешки',
        content: 'Овие грешки најчесто ги правaт новите ДДВ обврзници — избегнувајте ги:',
        items: [
          'Пропуштање на прагот: не го следите прометот и го надминувате лимитот од 2.000.000 МКД без регистрација — казна до 5.000 EUR',
          'Мешање на ослободен и оданочив промет: не водите одделна евиденција → неправилен одбиток на влезен ДДВ',
          'Неискористување на влезен ДДВ: заборавате да го побарате одбивањето на ДДВ од набавки → плаќате повеќе',
          'Задоцнето поднесување на ДДВ-04: казна од 250-500 EUR за одговорно лице + камата 0,03% дневно',
          'Непотполни фактури: фактури без ДДВ број, стапка или износ се невалидни за одбивање',
          'Одбивање ДДВ на непризнаени трошоци: репрезентација, лични возила и приватни трошоци не подлежат на одбивање',
        ],
        steps: null,
      },
      {
        title: 'Како Facturino помага?',
        content: 'Facturino ги автоматизира сите аспекти на ДДВ усогласеноста за вашиот бизнис:',
        items: [
          'Автоматско генерирање на ДДВ книги: ДДВ-01 (влезни) и ДДВ-02 (излезни) се генерираат автоматски од фактурите',
          'ДДВ-04 формулар: кварталната пријава се пополнува со еден клик, подготвена за поднесување во e-Tax',
          'Следење на прагот: Facturino го следи вашиот годишен промет и ве предупредува кога се приближувате до 2.000.000 МКД',
          'Правилна класификација на стапки: 18%, 5%, 10% и 0% се автоматски доделуваат на артикли и услуги',
          'Валидација на фактури: секоја фактура се проверува дали содржи сите задолжителни ДДВ елементи',
          'Извештаи и аналитика: прегледи на ДДВ обврска, влезен vs излезен ДДВ, право на поврат',
          'Архивирање: сите фактури и книги се зачувуваат дигитално во согласност со законскиот рок од 10 години',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'ddv-vodich-mk', title: 'ДДВ водич за Македонија: Сe што треба да знаете' },
      { slug: 'e-faktura-obvrska-2026', title: 'Е-фактура обврска 2026: Кој мора да издава?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Задолжителни елементи на фактура' },
      { slug: 'kazni-ujp-2026', title: 'Казни УЈП 2026: Што се случува ако задоцните со пријавата' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
    ],
    cta: {
      title: 'ДДВ книги и пријави, автоматски',
      desc: 'Facturino генерира ДДВ-01, ДДВ-02 и ДДВ-04 од вашите фактури — без рачно внесување.',
      button: 'Започнете бесплатно →',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'VAT Registration in North Macedonia 2026: Threshold, Process & Obligations',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro: 'Value Added Tax (VAT, locally known as DDV) is the most important indirect tax in North Macedonia. Every business whose annual turnover exceeds the 2,000,000 MKD threshold must register as a VAT payer. This guide explains who must register, the benefits of voluntary registration, the full UJP registration process, applicable tax rates, post-registration obligations, common mistakes, and how Facturino automates the entire workflow.',
    sections: [
      {
        title: 'Who Must Register for VAT?',
        content: 'Under the Law on Value Added Tax (Official Gazette 44/99, latest amendments 2024), VAT registration is mandatory when total annual turnover exceeds the 2,000,000 MKD threshold (approximately EUR 32,500). This includes all taxable supplies of goods and services performed within the scope of business activity.',
        items: [
          'Mandatory registration: turnover exceeding 2,000,000 MKD in the preceding 12 months',
          'Voluntary registration: available to any business regardless of turnover',
          'Foreign entities: mandatory registration when performing taxable activities in the country',
          'Newly established companies: may voluntarily register from the date of incorporation',
          'Farmers and craftsmen: equally subject to the threshold requirement',
          'Deadline to apply: 15 days from the date the threshold is exceeded',
        ],
        steps: null,
      },
      {
        title: 'Benefits of Voluntary Registration',
        content: 'Although the 2,000,000 MKD threshold may seem far off for smaller businesses, voluntary VAT registration carries significant advantages:',
        items: [
          'Input VAT deduction: recover VAT paid on purchases, equipment and services',
          'Credibility: a VAT number signals seriousness and stability to business partners',
          'B2G eligibility: government tenders require VAT registration — without it, you cannot participate',
          'Competitive edge: B2B clients prefer VAT-registered suppliers because they can deduct input VAT',
          'No penalties for unintentional threshold breach: if you grow fast, you are already registered',
          'Right to refund: when input VAT exceeds output VAT, UJP refunds the difference',
        ],
        steps: null,
      },
      {
        title: 'Registration Process',
        content: 'VAT registration is handled by UJP (Public Revenue Office). The process is electronic via the e-Tax portal:',
        items: null,
        steps: [
          { step: 'Prepare required documents', desc: 'Registration certificate from CRMS (Central Registry), current status certificate no older than 6 months, proof of opened bank account, and a business plan with projected turnover.' },
          { step: 'Register at etax.ujp.gov.mk', desc: 'If you do not have a digital certificate, obtain one from KIBS or another authorized issuer. Then register on the UJP e-Tax portal.' },
          { step: 'Submit the DDV-01 application', desc: 'Fill out the DDV-01 form (VAT Registration Application) with all company details, type of activity and projected annual turnover.' },
          { step: 'UJP review and approval', desc: 'UJP reviews the application within 15 working days. They may request additional documentation or conduct a business premises inspection.' },
          { step: 'Receive your VAT number (MK + EDB)', desc: 'Upon approval, you receive a VAT identification number starting with MK. This number must appear on every invoice.' },
          { step: 'Begin VAT record-keeping', desc: 'From the registration date you must maintain DDV-01 (input invoice ledger) and DDV-02 (output invoice ledger) and file quarterly DDV-04 returns.' },
        ],
      },
      {
        title: 'VAT Rates',
        content: 'North Macedonia applies four VAT rates. Correct rate classification is essential for compliance:',
        items: [
          '18% standard rate: applies to most goods and services (electronics, professional services, clothing)',
          '5% reduced rate: basic food products, medicines, medical devices, textbooks, books',
          '10% hospitality rate: hotel accommodation, restaurant services (introduced 2019)',
          '0% zero rate: export of goods, international transport, supplies to diplomatic missions',
          'Exempt without deduction right: financial services, insurance, healthcare, education',
          'Mixed supplies: if you have both taxable and exempt supplies, input VAT credits are split proportionally',
        ],
        steps: null,
      },
      {
        title: 'Obligations After Registration',
        content: 'Once registered as a VAT payer, you have a number of legal obligations that must be fulfilled:',
        items: [
          'DDV-04 quarterly return: due by the 25th of the month following the quarter end (Q1 Apr 25, Q2 Jul 25, Q3 Oct 25, Q4 Jan 25)',
          'DDV-01 ledger: record of all input invoices with VAT, by supplier, with amount and rate',
          'DDV-02 ledger: record of all output invoices with VAT, by customer, with amount and rate',
          'Invoicing: every invoice must include VAT number, rate, VAT amount and total amount',
          'Document retention: invoices and ledgers must be kept for a minimum of 10 years',
          'Monthly payment: VAT is payable by the 25th of the following month (or quarterly, depending on volume)',
          'Data changes: any changes (address, activity, bank account) must be reported within 15 days',
        ],
        steps: null,
      },
      {
        title: 'Common Mistakes',
        content: 'These are the most frequent mistakes made by new VAT payers — avoid them:',
        items: [
          'Missing the threshold: not tracking turnover and exceeding the 2,000,000 MKD limit without registering — fine up to EUR 5,000',
          'Mixing exempt and taxable supplies: not keeping separate records leads to incorrect input VAT deduction',
          'Not claiming input VAT: forgetting to claim VAT deduction on purchases means you pay more than necessary',
          'Late DDV-04 filing: fine of EUR 250-500 for the responsible person plus 0.03% daily interest',
          'Incomplete invoices: invoices without VAT number, rate or amount are invalid for deduction purposes',
          'Deducting VAT on non-deductible expenses: entertainment, personal vehicles and private expenses are not eligible for deduction',
        ],
        steps: null,
      },
      {
        title: 'How Facturino Helps',
        content: 'Facturino automates every aspect of VAT compliance for your business:',
        items: [
          'Automatic VAT ledger generation: DDV-01 (input) and DDV-02 (output) are generated automatically from your invoices',
          'DDV-04 form: the quarterly return is filled with one click, ready for e-Tax submission',
          'Threshold monitoring: Facturino tracks your annual turnover and alerts you when approaching the 2,000,000 MKD limit',
          'Correct rate classification: 18%, 5%, 10% and 0% rates are automatically assigned to items and services',
          'Invoice validation: every invoice is checked for all mandatory VAT elements',
          'Reports and analytics: VAT liability overview, input vs output VAT, refund eligibility',
          'Archiving: all invoices and ledgers are stored digitally in compliance with the 10-year legal retention period',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'ddv-vodich-mk', title: 'VAT Guide for Macedonia: Everything You Need to Know' },
      { slug: 'e-faktura-obvrska-2026', title: 'E-Invoice Obligation 2026: Who Must Issue?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Mandatory Invoice Elements' },
      { slug: 'kazni-ujp-2026', title: 'UJP Penalties 2026: What Happens If You File Late' },
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
    ],
    cta: {
      title: 'VAT books and returns, automated',
      desc: 'Facturino generates DDV-01, DDV-02 and DDV-04 from your invoices — no manual data entry.',
      button: 'Start Free →',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Regjistrimi për TVSH në Maqedoninë e Veriut 2026: Pragu, procesi dhe detyrimet',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro: 'Tatimi mbi Vlerën e Shtuar (TVSH, i njohur lokalisht si DDV) është tatimi indirekt më i rëndësishëm në Maqedoninë e Veriut. Çdo biznes me qarkullim vjetor mbi pragmin 2.000.000 MKD duhet të regjistrohet si pagues i TVSH-së. Ky udhëzues shpjegon kush duhet të regjistrohet, përfitimet e regjistrimit vullnetar, procesin e plotë në UJP, normat tatimore, detyrimet pas regjistrimit, gabimet e zakonshme dhe si Facturino e automatizon procesin.',
    sections: [
      {
        title: 'Kush duhet të regjistrohet për TVSH?',
        content: 'Sipas Ligjit për Tatimin mbi Vlerën e Shtuar (Gazeta Zyrtare 44/99, ndryshimet e fundit 2024), regjistrimi për TVSH është i detyrueshëm kur qarkullimi total vjetor kalon pragmin e 2.000.000 MKD (afërsisht 32.500 EUR). Kjo përfshin të gjitha furnizimet e tatueshme të mallrave dhe shërbimeve.',
        items: [
          'Regjistrimi i detyrueshëm: qarkullim mbi 2.000.000 MKD në 12 muajt e fundit',
          'Regjistrimi vullnetar: i disponueshëm për çdo biznes pavarësisht qarkullimit',
          'Subjektet e huaja: regjistrim i detyrueshëm kur kryejnë aktivitete të tatueshme në vend',
          'Kompanitë e reja: mund të regjistrohen vullnetarisht nga data e themelimit',
          'Fermerët dhe zejtarët: gjithashtu i nënshtrohen pragut',
          'Afati për aplikim: 15 ditë nga dita e kalimit të pragut',
        ],
        steps: null,
      },
      {
        title: 'Përfitimet e regjistrimit vullnetar',
        content: 'Edhe pse pragu 2.000.000 MKD mund të duket larg për bizneset e vogla, regjistrimi vullnetar për TVSH sjell përparësi të rëndësishme:',
        items: [
          'Zbritja e TVSH-së hyrëse: kthimi i TVSH-së të paguar për blerje, pajisje dhe shërbime',
          'Besueshmëria: numri i TVSH-së sinjalizon seriozitet dhe stabilitet ndaj partnerëve',
          'Përshtatshmëria B2G: tenderat shtetërore kërkojnë regjistrim TVSH — pa të nuk mund të merrni pjesë',
          'Epërsia konkurruese: klientët B2B preferojnë furnizues me TVSH sepse mund ta zbrisin atë',
          'Pa gjoba për kalim të paqëllimshëm: nëse rriteni shpejt, jeni tashmë të regjistruar',
          'E drejta e kthimit: kur TVSH hyrëse > TVSH dalëse, UJP kthen diferencën',
        ],
        steps: null,
      },
      {
        title: 'Procesi i regjistrimit',
        content: 'Regjistrimi për TVSH kryhet në UJP (Zyra e të Ardhurave Publike). Procesi është elektronik përmes portalit e-Tax:',
        items: null,
        steps: [
          { step: 'Përgatitni dokumentet e nevojshme', desc: 'Certifikata e regjistrimit nga QRMM (Regjistri Qendror), gjendja aktuale jo më e vjetër se 6 muaj, dëshmi e llogarisë bankare të hapur dhe plan biznesi me projeksione të qarkullimit.' },
          { step: 'Regjistrohuni në etax.ujp.gov.mk', desc: 'Nëse nuk keni certifikatë digjitale, merrni një nga KIBS ose lëshues tjetër i autorizuar. Pastaj regjistrohuni në portalin e-Tax të UJP.' },
          { step: 'Dorëzoni aplikimin DDV-01', desc: 'Plotësoni formularin DDV-01 (Kërkesë për Regjistrim TVSH) me të gjitha të dhënat e kompanisë, llojin e aktivitetit dhe qarkullimin vjetor të parashikuar.' },
          { step: 'Shqyrtimi dhe miratimi nga UJP', desc: 'UJP e shqyrton aplikimin brenda 15 ditëve të punës. Mund të kërkojnë dokumentacion shtesë ose inspektim të ambienteve.' },
          { step: 'Merrni numrin e TVSH (MK + EDB)', desc: 'Pas miratimit, merrni numrin identifikues të TVSH-së që fillon me MK. Ky numër duhet të shënohet në çdo faturë.' },
          { step: 'Filloni evidencën e TVSH-së', desc: 'Nga data e regjistrimit duhet të mbani DDV-01 (libri i faturave hyrëse) dhe DDV-02 (libri i faturave dalëse) dhe të dorëzoni deklaratën tremujore DDV-04.' },
        ],
      },
      {
        title: 'Normat e TVSH-së',
        content: 'Maqedonia e Veriut aplikon katër norma TVSH-je. Klasifikimi i saktë i normës është vendimtar për pajtueshmërinë:',
        items: [
          '18% norma standarde: aplikohet për shumicën e mallrave dhe shërbimeve (elektronikë, shërbime profesionale, veshje)',
          '5% norma e reduktuar: produkte ushqimore bazë, ilaçe, pajisje mjekësore, tekste shkollore, libra',
          '10% norma e hotelerisë: akomodim hotelier, shërbime restorantesh (e futur 2019)',
          '0% norma zero: eksport mallrash, transport ndërkombëtar, furnizime për misione diplomatike',
          'E përjashtuar pa të drejtë zbritjeje: shërbime financiare, sigurime, shëndetësi, arsim',
          'Furnizime të përziera: nëse keni furnizime të tatueshme dhe të përjashtuara, kreditë e TVSH-së ndahen proporcionalisht',
        ],
        steps: null,
      },
      {
        title: 'Detyrimet pas regjistrimit',
        content: 'Pasi të regjistroheni si pagues i TVSH-së, keni një numër detyrash ligjore që duhet t\'i përmbushni:',
        items: [
          'Deklarata tremujore DDV-04: afati deri më 25 të muajit pas përfundimit të tremujorit (T1→25 prill, T2→25 korrik, T3→25 tetor, T4→25 janar)',
          'Libri DDV-01: evidencë e të gjitha faturave hyrëse me TVSH, sipas furnizuesit, me shumë dhe normë',
          'Libri DDV-02: evidencë e të gjitha faturave dalëse me TVSH, sipas klientit, me shumë dhe normë',
          'Faturimi: çdo faturë duhet të përmbajë numrin e TVSH-së, normën, shumën e TVSH-së dhe shumën totale',
          'Ruajtja e dokumentacionit: faturat dhe librat duhet të ruhen minimumi 10 vjet',
          'Pagesa mujore: TVSH paguhet deri më 25 të muajit tjetër (ose tremujore, varësisht vëllimit)',
          'Ndryshime të dhënash: çdo ndryshim (adresë, aktivitet, llogari bankare) duhet raportuar brenda 15 ditëve',
        ],
        steps: null,
      },
      {
        title: 'Gabimet e zakonshme',
        content: 'Këto janë gabimet më të shpeshta të pagueses të rinj të TVSH-së — shmangni ato:',
        items: [
          'Moszbulimi i pragut: mosndjekja e qarkullimit dhe kalimi i limitit 2.000.000 MKD pa regjistrim — gjobë deri 5.000 EUR',
          'Përzierja e furnizimeve të përjashtuara me të tatueshme: mosmbajta e evidencës së ndarë çon në zbritje të gabuar',
          'Mosreklamimi i TVSH-së hyrëse: harresa për të kërkuar zbritjen e TVSH-së nga blerjet — paguani më shumë',
          'Dorëzim i vonuar i DDV-04: gjobë 250-500 EUR për personin përgjegjës + kamatë ditore 0,03%',
          'Fatura jo të plota: faturat pa numër TVSH-je, normë ose shumë janë të pavlefshme për zbritje',
          'Zbritja e TVSH-së për shpenzime jo të njohura: përfaqësimi, automjetet personale dhe shpenzimet private nuk kualifikohen',
        ],
        steps: null,
      },
      {
        title: 'Si ndihmon Facturino?',
        content: 'Facturino automatizon çdo aspekt të pajtueshmërisë së TVSH-së për biznesin tuaj:',
        items: [
          'Gjenerimi automatik i librave: DDV-01 (hyrëse) dhe DDV-02 (dalëse) gjenerohen automatikisht nga faturat',
          'Formulari DDV-04: deklarata tremujore plotësohet me një klikim, gati për dorëzim në e-Tax',
          'Monitorimi i pragut: Facturino ndjek qarkullimin vjetor dhe ju paralajmëron kur afroheni 2.000.000 MKD',
          'Klasifikimi i saktë i normave: 18%, 5%, 10% dhe 0% caktohen automatikisht për artikuj dhe shërbime',
          'Validimi i faturave: çdo faturë kontrollohet për të gjithë elementët e detyrueshëm të TVSH-së',
          'Raporte dhe analitikë: pasqyrë e detyrimit TVSH, hyrëse vs dalëse, e drejta e kthimit',
          'Arkivimi: të gjitha faturat dhe librat ruhen dixhitalisht sipas afatit ligjor 10-vjeçar',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'ddv-vodich-mk', title: 'Udhëzues TVSH për Maqedoninë: Gjithçka që duhet të dini' },
      { slug: 'e-faktura-obvrska-2026', title: 'Detyrimi i E-faturës 2026: Kush duhet të lëshojë?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Elementët e detyrueshëm të faturës' },
      { slug: 'kazni-ujp-2026', title: 'Gjobat e UJP 2026: Çfarë ndodh nëse dorëzoni me vonesë' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
    ],
    cta: {
      title: 'Librat e TVSH dhe deklaratat, automatikisht',
      desc: 'Facturino gjeneron DDV-01, DDV-02 dhe DDV-04 nga faturat tuaja — pa futje manuale.',
      button: 'Filloni falas →',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Kuzey Makedonya KDV Kaydı 2026: Eşik, Süreç ve Yükümlülükler',
    publishDate: '23 Mayıs 2026',
    readTime: '10 dk okuma',
    intro: 'Katma Değer Vergisi (KDV, yerel adıyla DDV), Kuzey Makedonya\'daki en önemli dolaylı vergidir. Yıllık cirosu 2.000.000 MKD eşiğini aşan her işletme KDV mükellefi olarak kayıt yaptırmak zorundadır. Bu rehber kimlerin kayıt olması gerektiğini, gönüllü kaydın avantajlarını, UJP kayıt sürecini, vergi oranlarını, kayıt sonrası yükümlülükleri, yaygın hataları ve Facturino\'nun tüm süreci nasıl otomatikleştirdiğini açıklamaktadır.',
    sections: [
      {
        title: 'KDV\'ye Kim Kayıt Olmalı?',
        content: 'Katma Değer Vergisi Kanunu\'na (Resmi Gazete 44/99, son değişiklikler 2024) göre, toplam yıllık ciro 2.000.000 MKD eşiğini (yaklaşık 32.500 EUR) aştığında KDV kaydı zorunludur. Bu, ticari faaliyet kapsamında gerçekleştirilen tüm vergilendirilebilir mal ve hizmet teslimlerini kapsar.',
        items: [
          'Zorunlu kayıt: son 12 ayda 2.000.000 MKD üzeri ciro',
          'Gönüllü kayıt: ciroya bakılmaksızın her işletme için mümkün',
          'Yabancı kuruluşlar: ülkede vergilendirilebilir faaliyet yürütürken zorunlu kayıt',
          'Yeni kurulan şirketler: kuruluş tarihinden itibaren gönüllü kayıt yapabilir',
          'Çiftçiler ve zanaatkarlar: eşiğe eşit olarak tabidirler',
          'Başvuru süresi: eşiğin aşıldığı tarihten itibaren 15 gün',
        ],
        steps: null,
      },
      {
        title: 'Gönüllü Kaydın Avantajları',
        content: '2.000.000 MKD eşiği küçük işletmeler için uzak görünse de, gönüllü KDV kaydı önemli avantajlar sağlar:',
        items: [
          'Giriş KDV indirimi: alımlar, ekipman ve hizmetler için ödenen KDV\'nin geri alınması',
          'Güvenilirlik: KDV numarası iş ortaklarına ciddiyet ve istikrar sinyali verir',
          'B2G uygunluğu: devlet ihaleleri KDV kaydı gerektirir — onsuz katılamazsınız',
          'Rekabet avantajı: B2B müşteriler KDV kayıtlı tedarikçileri tercih eder çünkü indirimi yapabilirler',
          'İstemeden eşik aşımında ceza yok: hızlı büyüyorsanız zaten kayıtlısınız',
          'İade hakkı: giriş KDV > çıkış KDV olduğunda UJP farkı iade eder',
        ],
        steps: null,
      },
      {
        title: 'Kayıt Süreci',
        content: 'KDV kaydı UJP (Kamu Gelir İdaresi) tarafından yürütülür. Süreç e-Tax portalı üzerinden elektroniktir:',
        items: null,
        steps: [
          { step: 'Gerekli belgeleri hazırlayın', desc: 'CRMS (Merkezi Sicil) kayıt belgesi, 6 aydan eski olmayan güncel durum belgesi, açılmış banka hesabı kanıtı ve ciro projeksiyonlu iş planı.' },
          { step: 'etax.ujp.gov.mk\'da kaydolun', desc: 'Dijital sertifikanız yoksa KIBS veya başka yetkili bir kuruluştan edinin. Ardından UJP e-Tax portalında kaydolun.' },
          { step: 'DDV-01 başvurusunu gönderin', desc: 'DDV-01 formunu (KDV Kaydı Başvurusu) tüm şirket bilgileri, faaliyet türü ve öngörülen yıllık ciro ile doldurun.' },
          { step: 'UJP incelemesi ve onayı', desc: 'UJP başvuruyu 15 iş günü içinde inceler. Ek belge talep edebilir veya iş yeri denetimi yapabilir.' },
          { step: 'KDV numaranızı alın (MK + EDB)', desc: 'Onay sonrası MK ile başlayan KDV kimlik numaranızı alırsınız. Bu numara her faturada zorunlu olarak belirtilmelidir.' },
          { step: 'KDV kayıt tutmaya başlayın', desc: 'Kayıt tarihinden itibaren DDV-01 (giriş fatura defteri) ve DDV-02 (çıkış fatura defteri) tutmanız ve üç aylık DDV-04 beyannamesi dosyalamanız gerekir.' },
        ],
      },
      {
        title: 'KDV Oranları',
        content: 'Kuzey Makedonya dört KDV oranı uygulamaktadır. Doğru oran sınıflandırması uyum için kritiktir:',
        items: [
          '%18 standart oran: çoğu mal ve hizmete uygulanır (elektronik, profesyonel hizmetler, giyim)',
          '%5 indirimli oran: temel gıda ürünleri, ilaçlar, tıbbi cihazlar, ders kitapları, kitaplar',
          '%10 ağırlama oranı: otel konaklaması, restoran hizmetleri (2019\'da yürürlüğe girdi)',
          '%0 sıfır oran: mal ihracatı, uluslararası taşımacılık, diplomatik misyonlara teslimler',
          'İndirim hakkı olmayan muafiyet: finansal hizmetler, sigorta, sağlık hizmetleri, eğitim',
          'Karma teslimler: hem vergilendirilebilir hem de muaf teslimleriniz varsa giriş KDV kredileri orantılı olarak bölünür',
        ],
        steps: null,
      },
      {
        title: 'Kayıt Sonrası Yükümlülükler',
        content: 'KDV mükellefi olarak kaydolduktan sonra yerine getirilmesi gereken bir dizi yasal yükümlülüğünüz vardır:',
        items: [
          'DDV-04 üç aylık beyanname: çeyrek sonunu takip eden ayın 25\'ine kadar (Ç1→25 Nisan, Ç2→25 Temmuz, Ç3→25 Ekim, Ç4→25 Ocak)',
          'DDV-01 defteri: tedarikçiye göre tutar ve oranla tüm giriş faturalarının kaydı',
          'DDV-02 defteri: müşteriye göre tutar ve oranla tüm çıkış faturalarının kaydı',
          'Faturalandırma: her fatura KDV numarası, oran, KDV tutarı ve toplam tutarı içermelidir',
          'Belge saklama: faturalar ve defterler en az 10 yıl saklanmalıdır',
          'Aylık ödeme: KDV takip eden ayın 25\'ine kadar ödenir (veya hacme göre üç aylık)',
          'Veri değişiklikleri: herhangi bir değişiklik (adres, faaliyet, banka hesabı) 15 gün içinde bildirilmelidir',
        ],
        steps: null,
      },
      {
        title: 'Yaygın Hatalar',
        content: 'Bunlar yeni KDV mükellefleri tarafından yapılan en sık hatalar — bunlardan kaçının:',
        items: [
          'Eşiği kaçırmak: ciroyu takip etmemek ve kayıt olmadan 2.000.000 MKD limitini aşmak — 5.000 EUR\'ya kadar ceza',
          'Muaf ve vergiye tabi teslimleri karıştırmak: ayrı kayıt tutmamak yanlış giriş KDV indirime yol açar',
          'Giriş KDV\'yi talep etmemek: alımlardaki KDV indirimini talep etmeyi unutmak — gereğinden fazla ödersiniz',
          'Geç DDV-04 dosyalama: sorumlu kişi için 250-500 EUR ceza + günlük %0,03 faiz',
          'Eksik faturalar: KDV numarası, oran veya tutar içermeyen faturalar indirim için geçersizdir',
          'İndirilemez giderlerde KDV indirimi: temsil-ağırlama, kişisel araçlar ve özel harcamalar indirime uygun değildir',
        ],
        steps: null,
      },
      {
        title: 'Facturino Nasıl Yardımcı Olur?',
        content: 'Facturino, işletmeniz için KDV uyumunun her yönünü otomatikleştirir:',
        items: [
          'Otomatik KDV defteri oluşturma: DDV-01 (giriş) ve DDV-02 (çıkış) faturalarınızdan otomatik oluşturulur',
          'DDV-04 formu: üç aylık beyanname tek tıkla doldurulur, e-Tax\'a gönderime hazır',
          'Eşik izleme: Facturino yıllık cironuzu takip eder ve 2.000.000 MKD\'ye yaklaştığınızda uyarır',
          'Doğru oran sınıflandırması: %18, %5, %10 ve %0 oranları ürün ve hizmetlere otomatik atanır',
          'Fatura doğrulama: her fatura tüm zorunlu KDV unsurları açısından kontrol edilir',
          'Raporlar ve analitik: KDV yükümlülüğü görünümü, giriş vs çıkış KDV, iade hakkı',
          'Arşivleme: tüm faturalar ve defterler 10 yıllık yasal saklama süresine uygun olarak dijital saklanır',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'ddv-vodich-mk', title: 'Makedonya için KDV Rehberi: Bilmeniz Gereken Her Şey' },
      { slug: 'e-faktura-obvrska-2026', title: 'E-Fatura Zorunluluğu 2026: Kim Düzenlemeli?' },
      { slug: 'zadolzitelni-elementi-faktura', title: 'Zorunlu Fatura Unsurları' },
      { slug: 'kazni-ujp-2026', title: 'UJP Cezaları 2026: Geç Başvuru Yaparsanız Ne Olur' },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, son tarihler ve hesaplama' },
    ],
    cta: {
      title: 'KDV defterleri ve beyannameler, otomatik',
      desc: 'Facturino faturalarınızdan DDV-01, DDV-02 ve DDV-04 oluşturur — manuel veri girişi yok.',
      button: 'Ücretsiz Başlayın →',
    },
  },
} as const

export default async function DdvRegistracijaPrag2026Page({
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
    slug: 'ddv-registracija-prag-2026',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['DDV', 'ДДВ', 'VAT', 'vat registration', 'north macedonia vat registration threshold 2026', 'ддв регистрација', 'vat number macedonia', 'DDV-04', 'UJP', '2026'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/ddv-registracija-prag-2026` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(articleLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Кој е прагот за задолжителна ДДВ регистрација во Македонија?', answer: 'Прагот за задолжителна ДДВ регистрација е 2.000.000 МКД годишен промет (приближно 32.500 EUR). Ако го надминете овој праг, мора да се регистрирате во рок од 15 дена.' },
        { question: 'Дали е можна доброволна ДДВ регистрација?', answer: 'Да, доброволна ДДВ регистрација е можна за секој бизнис без оглед на прометот. Предностите вклучуваат одбивање на влезен ДДВ, кредибилитет и подобност за државни тендери.' },
        { question: 'Кои се придобивките од ДДВ регистрација?', answer: 'Главните придобивки се: одбивање на влезен ДДВ на набавки и опрема, зголемен кредибилитет кај деловни партнери, подобност за B2G тендери и право на поврат ако влезниот ДДВ го надминува излезниот.' },
      ])) }} />
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
