import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

const BASE_URL = 'https://www.facturino.mk'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/prashanja-i-odgovori', {
    title: {
      mk: 'Прашања и одговори за бизнис и сметководство во Македонија',
      en: 'Business & Accounting Q&A for North Macedonia',
      sq: 'Pyetje dhe përgjigje për biznes dhe kontabilitet në Maqedoni',
      tr: 'Kuzey Makedonya için İş ve Muhasebe Soru-Cevap',
    },
    description: {
      mk: 'Најчести прашања што ги поставуваат македонски сопственици на бизниси и сметководители — основање фирма, ДДВ, е-фактура, плати, даноци и годишна сметка. Јасни, точни одговори според актуелната пракса.',
      en: 'The most common questions asked by Macedonian business owners and accountants — company registration, VAT, e-invoicing, payroll, taxes and annual accounts. Clear, accurate answers based on current practice.',
      sq: 'Pyetjet më të shpeshta të pronarëve dhe kontabilistëve maqedonas — regjistrim firme, TVSH, e-faturë, paga, tatime dhe llogari vjetore. Përgjigje të qarta e të sakta sipas praktikës aktuale.',
      tr: 'Makedonyalı işletme sahiplerinin ve muhasebecilerin en sık sorduğu sorular — şirket kuruluşu, KDV, e-fatura, bordro, vergiler ve yıllık hesaplar. Güncel uygulamaya dayalı net ve doğru yanıtlar.',
    },
  })
}

const copy = {
  mk: {
    hero: {
      badge: 'Прашања и одговори',
      title: 'Прашања и одговори за бизнис во Македонија',
      sub: 'Вистински прашања што ги поставуваат сопственици на фирми и сметководители во Северна Македонија — со кратки и точни одговори, групирани по тема.',
    },
    categories: [
      {
        heading: 'Основање фирма',
        qa: [
          {
            q: 'Како да отворам фирма во Македонија?',
            a: 'Регистрацијата се врши преку Централниот регистар на Република Северна Македонија (ЦРМ), лично или преку регистрационен агент. Потребни се лична карта, назив на фирмата, седиште и уплата на основната главнина. По регистрацијата добивате ЕМБС и даночен број (ЕДБ), а потоа отворате трансакциска сметка во банка. Facturino ве води чекор по чекор — видете го водичот за отворање фирма.',
          },
          {
            q: 'Која е разликата помеѓу ДООЕЛ и ДОО?',
            a: 'ДООЕЛ (друштво со ограничена одговорност основано од едно лице) има само еден основач, додека ДОО има двајца или повеќе. И двете се друштва со ограничена одговорност — сопствениците одговараат само до висината на вложениот капитал, не со личниот имот. Освен бројот на основачи, даночниот и сметководствениот третман е практично ист.',
          },
          {
            q: 'Колку чини да се отвори фирма?',
            a: 'Основните трошоци вклучуваат таксата за регистрација во Централниот регистар и минималната основна главнина за ДООЕЛ/ДОО, која се уплаќа на привремена сметка. Дополнителни трошоци можат да бидат нотар, печат и регистрационен агент доколку не поднесувате сами. Точните износи зависат од видот на друштвото и дали ангажирате агент.',
          },
          {
            q: 'Дали странец може да отвори фирма?',
            a: 'Да, странско физичко или правно лице може да основа и да биде сопственик на друштво во Северна Македонија под исти услови како домашните лица. Потребен е пасош или друг документ за идентификација, а за некои дејности може да важат посебни услови. Странец не мора да има престој во земјата за да биде сопственик или управител.',
          },
        ],
      },
      {
        heading: 'ДДВ (данок на додадена вредност)',
        qa: [
          {
            q: 'Кога морам да се регистрирам за ДДВ?',
            a: 'Регистрацијата за ДДВ станува задолжителна кога вкупниот промет во претходната година надмине 2.000.000 денари. Под тој праг регистрацијата е доброволна. По надминувањето сте должни да поднесете пријава за регистрација во пропишаниот рок и почнувате да пресметувате ДДВ на вашите фактури.',
          },
          {
            q: 'Кои се ДДВ стапките во Македонија?',
            a: 'Општата ДДВ стапка е 18%. Постои повластена стапка од 5% (на пример за основни прехранбени производи, лекови и одредени добра) и стапка од 10% за одредени добра и услуги. Правилното распоредување на стапката по ставка е клучно за точна ДДВ пресметка.',
          },
          {
            q: 'Кога се поднесува ДДВ-04 пријавата?',
            a: 'ДДВ пријавата (образец ДДВ-04) се поднесува до 25-ти во месецот за претходниот даночен период — месечно или тримесечно, во зависност од прометот. Пријавата се доставува електронски преку системот на УЈП, а даночната обврска се плаќа во истиот рок. Facturino ги пресметува влезниот и излезниот ДДВ автоматски од фактурите.',
          },
        ],
      },
      {
        heading: 'Фактурирање и е-фактура',
        qa: [
          {
            q: 'Што е е-фактура и кога станува задолжителна?',
            a: 'Е-фактурата е фактура во структуриран електронски формат што може автоматски да се обработи, а не само PDF. Во Северна Македонија електронското фактурирање станува задолжително постепено — најпрво за трансакции со јавниот сектор (B2G) од октомври 2026 година. Видете го нашиот водич за е-фактура за деталите и роковите.',
          },
          {
            q: 'Дали PDF е е-фактура?',
            a: 'Не. Обичен PDF или скенирана фактура не се смета за е-фактура во смисла на регулативата. Вистинската е-фактура е во структуриран формат (UBL 2.1) и е дигитално потпишана со квалификуван електронски потпис (QES), за да може системот на другата страна автоматски да ја прочита и валидира.',
          },
          {
            q: 'Кои се задолжителните елементи на фактурата?',
            a: 'Фактурата мора да содржи назив, адреса и даночен број на издавачот и примачот, датум на издавање и датум на промет, реден број на фактурата, опис и количина на добрата/услугите, единечна цена, ДДВ стапка и износ на ДДВ, како и вкупен износ за плаќање. Недостаток на задолжителен елемент може да ја направи фактурата неважечка за одбивка на претходен ДДВ.',
          },
        ],
      },
      {
        heading: 'Плати и придонеси',
        qa: [
          {
            q: 'Колкава е минималната плата во 2026?',
            a: 'За 2026 година минималната плата изнесува околу 38.507 денари бруто, односно приближно 26.046 денари нето. Минималната плата се утврдува со закон и се усогласува периодично. Работодавачот не смее да исплати помалку од законски утврдениот минимум за полно работно време.',
          },
          {
            q: 'Како се пресметува нето плата од бруто?',
            a: 'Од бруто платата прво се одбиваат социјалните придонеси (пензиско, здравствено, осигурување во случај на невработеност и дополнителен придонес) кои вкупно изнесуваат 28%. Потоа, по одземање на личното ослободување од 10.932 денари месечно, на остатокот се пресметува персонален данок од 10%. Останатото е нето плата. Пробајте го нашиот калкулатор за плата за точна пресметка.',
          },
          {
            q: 'Што е МПИН?',
            a: 'МПИН (Месечна пресметка за интегрирана наплата) е електронскиот образец што работодавачот го поднесува до УЈП за секоја исплата на плата. Тој ги содржи бруто платите, придонесите и данокот за секој вработен. Без прифатен МПИН не може да се исплати плата, па точноста на пресметката е клучна.',
          },
        ],
      },
      {
        heading: 'Даноци',
        qa: [
          {
            q: 'Колкав е данокот на добивка?',
            a: 'Стапката на данокот на добивка (данок на добивка на правни лица) во Северна Македонија изнесува 10%. Данокот се пресметува на оданочивата добивка утврдена со даночен биланс, по усогласувања на приходите и расходите според даночните прописи. Пријавата се поднесува годишно.',
          },
          {
            q: 'Колкав е персоналниот данок на доход?',
            a: 'Персоналниот данок на доход изнесува 10% и се применува на плати, приходи од самостојна дејност, авторски договори и други видови доход. За платите данокот се задржува и уплаќа од работодавачот преку МПИН. За други приходи обврската за пријавување паѓа на физичкото лице.',
          },
          {
            q: 'Што е паушален даночник?',
            a: 'Паушалниот даночник е даночен обврзник (обично мал занаетчија или самостоен вршител на дејност) кому даночната обврска му се утврдува во паушален (фиксен) износ, наместо врз основа на реални книговодствени резултати. Тоа поедноставува евиденцијата, но важи само за одредени дејности и до одреден праг на приход.',
          },
        ],
      },
      {
        heading: 'Сметководство',
        qa: [
          {
            q: 'Дали ми треба сметководител?',
            a: 'Секое трговско друштво во Македонија е должно да води деловни книги според сметководствените прописи, што во пракса налага ангажирање овластен сметководител или сметководствено биро. Можете да ги водите книгите и внатрешно доколку имате стручно лице. Facturino го автоматизира најголемиот дел од работата — фактурирање, книжење и извештаи — и ја олеснува соработката со вашиот сметководител.',
          },
          {
            q: 'Што е годишна сметка и кога се поднесува?',
            a: 'Годишната сметка е сет финансиски извештаи (биланс на состојба, биланс на успех и придружни обрасци) што фирмата ги поднесува за завршената деловна година. Таа се доставува до Централниот регистар, вообичаено во првите месеци од новата година според пропишаниот рок. Годишната сметка е основа и за даночниот биланс и пресметката на данокот на добивка.',
          },
          {
            q: 'Како Facturino помага?',
            a: 'Facturino е сметководствен систем прилагоден за Северна Македонија — издавање фактури и е-фактури, автоматско книжење според македонскиот контен план, пресметка на ДДВ и плати, како и подготовка на извештаи. Со тоа се намалуваат рачните грешки и се заштедува време, а вие и вашиот сметководител работите на исти, ажурни податоци.',
          },
        ],
      },
    ],
    ctaTitle: 'Водете го бизнисот полесно со Facturino',
    ctaBtn: 'Пробај бесплатно →',
  },
  en: {
    hero: {
      badge: 'Q&A',
      title: 'Business Q&A for North Macedonia',
      sub: 'Real questions that company owners and accountants in North Macedonia ask — with short, accurate answers, grouped by topic.',
    },
    categories: [
      {
        heading: 'Starting a company',
        qa: [
          {
            q: 'How do I open a company in North Macedonia?',
            a: 'Registration is done through the Central Registry of the Republic of North Macedonia (CRM), either in person or via a registration agent. You need an ID document, a company name, a registered seat and payment of the founding capital. After registration you receive an EMBS number and a tax number (EDB), then open a bank transaction account. Facturino guides you step by step — see the company registration guide.',
          },
          {
            q: 'What is the difference between DOOEL and DOO?',
            a: 'A DOOEL (limited liability company founded by a single person) has only one founder, while a DOO has two or more. Both are limited liability companies — owners are liable only up to their invested capital, not with personal assets. Apart from the number of founders, the tax and accounting treatment is practically identical.',
          },
          {
            q: 'How much does it cost to open a company?',
            a: 'The core costs include the Central Registry registration fee and the minimum founding capital for a DOOEL/DOO, paid into a temporary account. Additional costs may include a notary, a company seal and a registration agent if you do not file yourself. Exact amounts depend on the company type and whether you engage an agent.',
          },
          {
            q: 'Can a foreigner open a company?',
            a: 'Yes. A foreign individual or legal entity can found and own a company in North Macedonia under the same conditions as domestic persons. A passport or other identity document is required, and some activities may have special conditions. A foreigner does not need residence in the country to be an owner or manager.',
          },
        ],
      },
      {
        heading: 'VAT (value added tax)',
        qa: [
          {
            q: 'When must I register for VAT?',
            a: 'VAT registration becomes mandatory once total turnover in the previous year exceeds MKD 2,000,000. Below that threshold registration is voluntary. Once you cross it, you must file a registration application within the prescribed deadline and start charging VAT on your invoices.',
          },
          {
            q: 'What are the VAT rates in North Macedonia?',
            a: 'The standard VAT rate is 18%. There is a reduced rate of 5% (for example on basic foodstuffs, medicines and certain goods) and a 10% rate on certain goods and services. Applying the correct rate per line item is essential for an accurate VAT calculation.',
          },
          {
            q: 'When is the DDV-04 return filed?',
            a: 'The VAT return (form DDV-04) is filed by the 25th of the month for the previous tax period — monthly or quarterly depending on turnover. It is submitted electronically through the PRO (UJP) system, and the tax liability is paid within the same deadline. Facturino calculates input and output VAT automatically from your invoices.',
          },
        ],
      },
      {
        heading: 'Invoicing and e-invoices',
        qa: [
          {
            q: 'What is an e-invoice and when does it become mandatory?',
            a: 'An e-invoice is an invoice in a structured electronic format that can be processed automatically, not just a PDF. In North Macedonia electronic invoicing becomes mandatory gradually — first for transactions with the public sector (B2G) from October 2026. See our e-invoice guide for the details and deadlines.',
          },
          {
            q: 'Is a PDF an e-invoice?',
            a: 'No. A plain PDF or a scanned invoice does not count as an e-invoice under the regulation. A genuine e-invoice is in a structured format (UBL 2.1) and is digitally signed with a qualified electronic signature (QES), so the other party’s system can read and validate it automatically.',
          },
          {
            q: 'What are the mandatory invoice elements?',
            a: 'An invoice must contain the name, address and tax number of the issuer and recipient, the issue date and the date of supply, the invoice sequence number, a description and quantity of goods/services, unit price, VAT rate and VAT amount, and the total amount due. A missing mandatory element can make the invoice invalid for input VAT deduction.',
          },
        ],
      },
      {
        heading: 'Payroll and contributions',
        qa: [
          {
            q: 'What is the minimum wage in 2026?',
            a: 'For 2026 the minimum wage is around MKD 38,507 gross, i.e. approximately MKD 26,046 net. The minimum wage is set by law and adjusted periodically. An employer may not pay less than the statutory minimum for full-time work.',
          },
          {
            q: 'How is net salary calculated from gross?',
            a: 'From the gross salary, social contributions are first deducted (pension, health, unemployment insurance and an additional contribution), totalling 28%. Then, after subtracting the personal allowance of MKD 10,932 per month, a 10% personal income tax is applied to the remainder. What is left is the net salary. Try our salary calculator for an exact figure.',
          },
          {
            q: 'What is MPIN?',
            a: 'MPIN (Monthly Calculation for Integrated Collection) is the electronic form the employer submits to the PRO (UJP) for each salary payment. It contains the gross salaries, contributions and tax for each employee. Salaries cannot be paid without an accepted MPIN, so the accuracy of the calculation is crucial.',
          },
        ],
      },
      {
        heading: 'Taxes',
        qa: [
          {
            q: 'What is the corporate profit tax rate?',
            a: 'The corporate profit tax rate in North Macedonia is 10%. The tax is calculated on the taxable profit established in the tax balance, after adjusting revenues and expenses under the tax rules. The return is filed annually.',
          },
          {
            q: 'What is the personal income tax rate?',
            a: 'Personal income tax is 10% and applies to salaries, income from independent activity, author contracts and other types of income. For salaries the tax is withheld and paid by the employer through MPIN. For other income the reporting obligation falls on the individual.',
          },
          {
            q: 'What is a lump-sum taxpayer (paušal)?',
            a: 'A lump-sum taxpayer (usually a small craftsperson or sole proprietor) has their tax liability set as a fixed lump sum instead of being based on actual bookkeeping results. This simplifies record-keeping but applies only to certain activities and up to a defined income threshold.',
          },
        ],
      },
      {
        heading: 'Accounting',
        qa: [
          {
            q: 'Do I need an accountant?',
            a: 'Every commercial company in North Macedonia is required to keep business books under the accounting rules, which in practice means engaging a licensed accountant or accounting firm. You may keep the books in-house if you have a qualified person. Facturino automates most of the work — invoicing, posting and reports — and makes collaboration with your accountant easier.',
          },
          {
            q: 'What is the annual account and when is it filed?',
            a: 'The annual account is a set of financial statements (balance sheet, income statement and accompanying forms) that a company files for the completed business year. It is submitted to the Central Registry, typically in the first months of the new year within the prescribed deadline. The annual account is also the basis for the tax balance and the profit tax calculation.',
          },
          {
            q: 'How does Facturino help?',
            a: 'Facturino is an accounting system tailored for North Macedonia — issuing invoices and e-invoices, automatic posting to the Macedonian chart of accounts, VAT and payroll calculation, and report preparation. This reduces manual errors and saves time, while you and your accountant work on the same, up-to-date data.',
          },
        ],
      },
    ],
    ctaTitle: 'Run your business more easily with Facturino',
    ctaBtn: 'Try it free →',
  },
  sq: {
    hero: {
      badge: 'Pyetje & Përgjigje',
      title: 'Pyetje dhe përgjigje për biznes në Maqedoni',
      sub: 'Pyetje reale që i bëjnë pronarët e firmave dhe kontabilistët në Maqedoninë e Veriut — me përgjigje të shkurtra e të sakta, të grupuara sipas temës.',
    },
    categories: [
      {
        heading: 'Themelimi i firmës',
        qa: [
          {
            q: 'Si të hap një firmë në Maqedoninë e Veriut?',
            a: 'Regjistrimi bëhet përmes Regjistrit Qendror të Republikës së Maqedonisë së Veriut (RQM), personalisht ose përmes një agjenti regjistrimi. Ju nevojitet dokument identifikimi, emri i firmës, selia dhe pagesa e kapitalit themeltar. Pas regjistrimit merrni numrin EMBS dhe numrin tatimor (EDB), pastaj hapni një llogari transaksioni në bankë. Facturino ju udhëheq hap pas hapi — shihni udhëzuesin për hapjen e firmës.',
          },
          {
            q: 'Cili është ndryshimi mes DOOEL dhe DOO?',
            a: 'DOOEL (shoqëri me përgjegjësi të kufizuar e themeluar nga një person) ka vetëm një themelues, ndërsa DOO ka dy ose më shumë. Të dyja janë shoqëri me përgjegjësi të kufizuar — pronarët përgjigjen vetëm deri në lartësinë e kapitalit të investuar, jo me pasurinë personale. Përveç numrit të themeluesve, trajtimi tatimor dhe kontabël është praktikisht i njëjtë.',
          },
          {
            q: 'Sa kushton të hapësh një firmë?',
            a: 'Kostot bazë përfshijnë tarifën e regjistrimit në Regjistrin Qendror dhe kapitalin minimal themeltar për DOOEL/DOO, që paguhet në një llogari të përkohshme. Kosto shtesë mund të jenë noteri, vula dhe agjenti i regjistrimit nëse nuk aplikoni vetë. Shumat e sakta varen nga lloji i shoqërisë dhe nëse angazhoni një agjent.',
          },
          {
            q: 'A mund të hapë një i huaj një firmë?',
            a: 'Po. Një person i huaj fizik ose juridik mund të themelojë dhe të jetë pronar i një shoqërie në Maqedoninë e Veriut me të njëjtat kushte si personat vendas. Nevojitet pasaportë ose dokument tjetër identifikimi, dhe disa veprimtari mund të kenë kushte të veçanta. Një i huaj nuk ka nevojë të ketë qëndrim në vend për të qenë pronar ose menaxher.',
          },
        ],
      },
      {
        heading: 'TVSH (tatimi mbi vlerën e shtuar)',
        qa: [
          {
            q: 'Kur duhet të regjistrohem për TVSH?',
            a: 'Regjistrimi për TVSH bëhet i detyrueshëm kur qarkullimi total i vitit të kaluar tejkalon 2.000.000 denarë. Nën këtë prag regjistrimi është vullnetar. Pasi ta tejkaloni, jeni të detyruar të paraqisni kërkesën për regjistrim brenda afatit të përcaktuar dhe filloni të llogarisni TVSH në faturat tuaja.',
          },
          {
            q: 'Cilat janë normat e TVSH në Maqedoni?',
            a: 'Norma standarde e TVSH-së është 18%. Ekziston një normë e reduktuar prej 5% (për shembull për ushqime bazë, ilaçe dhe disa mallra) dhe një normë prej 10% për disa mallra e shërbime. Zbatimi i normës së saktë për çdo zë është thelbësor për një llogaritje të saktë të TVSH-së.',
          },
          {
            q: 'Kur paraqitet deklarata DDV-04?',
            a: 'Deklarata e TVSH-së (formulari DDV-04) paraqitet deri më 25 të muajit për periudhën e mëparshme tatimore — mujore ose tremujore, në varësi të qarkullimit. Ajo dorëzohet elektronikisht përmes sistemit të UJP-së, dhe detyrimi tatimor paguhet brenda të njëjtit afat. Facturino llogarit automatikisht TVSH-në hyrëse dhe dalëse nga faturat tuaja.',
          },
        ],
      },
      {
        heading: 'Faturimi dhe e-fatura',
        qa: [
          {
            q: 'Çfarë është e-fatura dhe kur bëhet e detyrueshme?',
            a: 'E-fatura është një faturë në format elektronik të strukturuar që mund të përpunohet automatikisht, jo thjesht një PDF. Në Maqedoninë e Veriut faturimi elektronik bëhet i detyrueshëm gradualisht — së pari për transaksionet me sektorin publik (B2G) nga tetori 2026. Shihni udhëzuesin tonë për e-faturën për detajet dhe afatet.',
          },
          {
            q: 'A është një PDF e-faturë?',
            a: 'Jo. Një PDF i thjeshtë ose një faturë e skanuar nuk konsiderohet e-faturë sipas rregullores. E-fatura e vërtetë është në format të strukturuar (UBL 2.1) dhe është e nënshkruar dixhitalisht me nënshkrim elektronik të kualifikuar (QES), që sistemi i palës tjetër ta lexojë dhe validojë automatikisht.',
          },
          {
            q: 'Cilat janë elementet e detyrueshme të faturës?',
            a: 'Fatura duhet të përmbajë emrin, adresën dhe numrin tatimor të lëshuesit dhe marrësit, datën e lëshimit dhe datën e qarkullimit, numrin rendor të faturës, përshkrimin dhe sasinë e mallrave/shërbimeve, çmimin njësi, normën e TVSH-së dhe shumën e TVSH-së, si dhe shumën totale për pagesë. Mungesa e një elementi të detyrueshëm mund ta bëjë faturën të pavlefshme për zbritjen e TVSH-së hyrëse.',
          },
        ],
      },
      {
        heading: 'Pagat dhe kontributet',
        qa: [
          {
            q: 'Sa është paga minimale në 2026?',
            a: 'Për vitin 2026 paga minimale është rreth 38.507 denarë bruto, pra afërsisht 26.046 denarë neto. Paga minimale përcaktohet me ligj dhe harmonizohet periodikisht. Punëdhënësi nuk mund të paguajë më pak se minimumi i përcaktuar me ligj për punën me kohë të plotë.',
          },
          {
            q: 'Si llogaritet paga neto nga bruto?',
            a: 'Nga paga bruto fillimisht zbriten kontributet sociale (pensional, shëndetësor, sigurimi për papunësi dhe një kontribut shtesë), të cilat totalisht janë 28%. Pastaj, pas zbritjes së lirimit personal prej 10.932 denarë në muaj, mbi pjesën e mbetur zbatohet tatimi personal prej 10%. Ajo që mbetet është paga neto. Provoni kalkulatorin tonë të pagës për një shifër të saktë.',
          },
          {
            q: 'Çfarë është MPIN?',
            a: 'MPIN (Llogaritja Mujore për Arkëtim të Integruar) është formulari elektronik që punëdhënësi ia dorëzon UJP-së për çdo pagesë page. Ai përmban pagat bruto, kontributet dhe tatimin për çdo të punësuar. Pa një MPIN të pranuar nuk mund të paguhet paga, prandaj saktësia e llogaritjes është thelbësore.',
          },
        ],
      },
      {
        heading: 'Tatimet',
        qa: [
          {
            q: 'Sa është tatimi mbi fitimin?',
            a: 'Norma e tatimit mbi fitimin (tatimi mbi fitimin e personave juridikë) në Maqedoninë e Veriut është 10%. Tatimi llogaritet mbi fitimin e tatueshëm të përcaktuar në bilancin tatimor, pas harmonizimit të të ardhurave dhe shpenzimeve sipas rregullave tatimore. Deklarata paraqitet çdo vit.',
          },
          {
            q: 'Sa është tatimi personal mbi të ardhurat?',
            a: 'Tatimi personal mbi të ardhurat është 10% dhe zbatohet mbi pagat, të ardhurat nga veprimtaria e pavarur, kontratat e autorit dhe lloje të tjera të ardhurash. Për pagat tatimi mbahet dhe paguhet nga punëdhënësi përmes MPIN. Për të ardhura të tjera detyrimi për raportim bie mbi personin fizik.',
          },
          {
            q: 'Çfarë është tatimpaguesi me shumë fikse (paushall)?',
            a: 'Tatimpaguesi me shumë fikse (zakonisht një zejtar i vogël ose tregtar i pavarur) e ka detyrimin tatimor të përcaktuar në një shumë fikse, në vend të bazuar në rezultatet reale të kontabilitetit. Kjo e thjeshton evidencën, por vlen vetëm për disa veprimtari dhe deri në një prag të caktuar të ardhurash.',
          },
        ],
      },
      {
        heading: 'Kontabiliteti',
        qa: [
          {
            q: 'A më duhet një kontabilist?',
            a: 'Çdo shoqëri tregtare në Maqedoni është e detyruar të mbajë libra afaristë sipas rregullave të kontabilitetit, gjë që në praktikë kërkon angazhimin e një kontabilisti të licencuar ose byroje kontabiliteti. Mund t’i mbani librat edhe brenda firmës nëse keni një person të kualifikuar. Facturino automatizon pjesën më të madhe të punës — faturimin, kontabilizimin dhe raportet — dhe e lehtëson bashkëpunimin me kontabilistin tuaj.',
          },
          {
            q: 'Çfarë është llogaria vjetore dhe kur paraqitet?',
            a: 'Llogaria vjetore është një grup pasqyrash financiare (bilanci i gjendjes, bilanci i suksesit dhe formularët shoqërues) që firma i paraqet për vitin afarist të përfunduar. Ajo dorëzohet në Regjistrin Qendror, zakonisht në muajt e parë të vitit të ri brenda afatit të përcaktuar. Llogaria vjetore është edhe baza për bilancin tatimor dhe llogaritjen e tatimit mbi fitimin.',
          },
          {
            q: 'Si ndihmon Facturino?',
            a: 'Facturino është një sistem kontabiliteti i përshtatur për Maqedoninë e Veriut — lëshim faturash dhe e-faturash, kontabilizim automatik sipas planit kontabël maqedonas, llogaritje të TVSH-së dhe pagave, si dhe përgatitje raportesh. Kjo redukton gabimet manuale dhe kursen kohë, ndërsa ju dhe kontabilisti juaj punoni mbi të njëjtat të dhëna të përditësuara.',
          },
        ],
      },
    ],
    ctaTitle: 'Drejtoni biznesin tuaj më lehtë me Facturino',
    ctaBtn: 'Provoni falas →',
  },
  tr: {
    hero: {
      badge: 'Soru-Cevap',
      title: 'Kuzey Makedonya için İş Soru-Cevap',
      sub: 'Kuzey Makedonya’daki şirket sahiplerinin ve muhasebecilerin sorduğu gerçek sorular — konuya göre gruplanmış kısa ve doğru yanıtlarla.',
    },
    categories: [
      {
        heading: 'Şirket kurma',
        qa: [
          {
            q: 'Kuzey Makedonya’da nasıl şirket açarım?',
            a: 'Kayıt, Kuzey Makedonya Cumhuriyeti Merkezi Sicil (CRM) üzerinden bizzat ya da bir kayıt acentesi aracılığıyla yapılır. Kimlik belgesi, şirket adı, kayıtlı merkez ve kuruluş sermayesinin yatırılması gerekir. Kayıttan sonra bir EMBS numarası ve vergi numarası (EDB) alır, ardından bankada bir işlem hesabı açarsınız. Facturino sizi adım adım yönlendirir — şirket kurma kılavuzuna bakın.',
          },
          {
            q: 'DOOEL ile DOO arasındaki fark nedir?',
            a: 'DOOEL (tek kişi tarafından kurulan limited şirket) yalnızca bir kurucuya sahiptir; DOO ise iki veya daha fazla kurucuya sahiptir. Her ikisi de limited şirkettir — sahipler yalnızca yatırdıkları sermaye kadar sorumludur, kişisel malvarlıklarıyla değil. Kurucu sayısı dışında vergi ve muhasebe uygulaması pratikte aynıdır.',
          },
          {
            q: 'Şirket açmak ne kadara mal olur?',
            a: 'Temel maliyetler, Merkezi Sicil kayıt ücretini ve DOOEL/DOO için geçici bir hesaba yatırılan asgari kuruluş sermayesini içerir. Kendiniz başvurmuyorsanız noter, şirket kaşesi ve kayıt acentesi gibi ek maliyetler olabilir. Kesin tutarlar şirket türüne ve bir acente ile çalışıp çalışmadığınıza bağlıdır.',
          },
          {
            q: 'Bir yabancı şirket açabilir mi?',
            a: 'Evet. Yabancı bir gerçek veya tüzel kişi, yerli kişilerle aynı koşullarda Kuzey Makedonya’da şirket kurabilir ve sahibi olabilir. Pasaport veya başka bir kimlik belgesi gerekir ve bazı faaliyetler için özel koşullar geçerli olabilir. Bir yabancının sahip veya yönetici olması için ülkede ikamet etmesi gerekmez.',
          },
        ],
      },
      {
        heading: 'KDV (katma değer vergisi)',
        qa: [
          {
            q: 'KDV için ne zaman kayıt olmam gerekir?',
            a: 'KDV kaydı, önceki yıldaki toplam ciro 2.000.000 denarı aştığında zorunlu hale gelir. Bu eşiğin altında kayıt isteğe bağlıdır. Eşiği aştıktan sonra belirlenen süre içinde kayıt başvurusu yapmanız ve faturalarınızda KDV hesaplamaya başlamanız gerekir.',
          },
          {
            q: 'Kuzey Makedonya’da KDV oranları nelerdir?',
            a: 'Standart KDV oranı %18’dir. İndirimli bir oran olan %5 (örneğin temel gıda maddeleri, ilaçlar ve belirli mallar için) ve belirli mal ve hizmetler için %10 oranı vardır. Doğru bir KDV hesabı için her kalemde doğru oranın uygulanması esastır.',
          },
          {
            q: 'DDV-04 beyannamesi ne zaman verilir?',
            a: 'KDV beyannamesi (DDV-04 formu) önceki vergi dönemi için ayın 25’ine kadar verilir — ciroya bağlı olarak aylık veya üç aylık. Beyanname UJP sistemi üzerinden elektronik olarak sunulur ve vergi borcu aynı süre içinde ödenir. Facturino, faturalarınızdan giriş ve çıkış KDV’sini otomatik olarak hesaplar.',
          },
        ],
      },
      {
        heading: 'Faturalama ve e-fatura',
        qa: [
          {
            q: 'E-fatura nedir ve ne zaman zorunlu olur?',
            a: 'E-fatura, yalnızca bir PDF değil, otomatik olarak işlenebilen yapılandırılmış elektronik formatta bir faturadır. Kuzey Makedonya’da elektronik faturalama kademeli olarak zorunlu hale gelir — önce Ekim 2026’dan itibaren kamu sektörüyle (B2G) yapılan işlemler için. Ayrıntılar ve son tarihler için e-fatura kılavuzumuza bakın.',
          },
          {
            q: 'Bir PDF e-fatura mıdır?',
            a: 'Hayır. Düz bir PDF veya taranmış bir fatura, mevzuata göre e-fatura sayılmaz. Gerçek bir e-fatura yapılandırılmış formattadır (UBL 2.1) ve karşı tarafın sisteminin otomatik olarak okuyup doğrulayabilmesi için nitelikli elektronik imza (QES) ile dijital olarak imzalanır.',
          },
          {
            q: 'Zorunlu fatura unsurları nelerdir?',
            a: 'Bir fatura; düzenleyen ve alıcının adını, adresini ve vergi numarasını, düzenleme tarihini ve teslim tarihini, fatura sıra numarasını, mal/hizmetlerin açıklamasını ve miktarını, birim fiyatı, KDV oranını ve KDV tutarını ve ödenecek toplam tutarı içermelidir. Zorunlu bir unsurun eksikliği, faturayı giriş KDV indirimi için geçersiz kılabilir.',
          },
        ],
      },
      {
        heading: 'Bordro ve katkı payları',
        qa: [
          {
            q: '2026’da asgari ücret ne kadar?',
            a: '2026 yılı için asgari ücret yaklaşık 38.507 denar brüt, yani yaklaşık 26.046 denar nettir. Asgari ücret yasayla belirlenir ve dönemsel olarak güncellenir. Bir işveren, tam zamanlı çalışma için yasal asgari ücretin altında ödeme yapamaz.',
          },
          {
            q: 'Net maaş brütten nasıl hesaplanır?',
            a: 'Brüt maaştan önce sosyal katkı payları (emeklilik, sağlık, işsizlik sigortası ve ek katkı payı) düşülür; bunlar toplamda %28’dir. Ardından, aylık 10.932 denarlık kişisel istisna çıkarıldıktan sonra kalan tutara %10 kişisel gelir vergisi uygulanır. Geriye kalan net maaştır. Kesin bir rakam için maaş hesaplayıcımızı deneyin.',
          },
          {
            q: 'MPIN nedir?',
            a: 'MPIN (Entegre Tahsilat için Aylık Hesaplama), işverenin her maaş ödemesi için UJP’ye sunduğu elektronik formdur. Her çalışan için brüt maaşları, katkı paylarını ve vergiyi içerir. Kabul edilmiş bir MPIN olmadan maaş ödenemez, bu yüzden hesaplamanın doğruluğu çok önemlidir.',
          },
        ],
      },
      {
        heading: 'Vergiler',
        qa: [
          {
            q: 'Kurumlar kazanç vergisi oranı nedir?',
            a: 'Kuzey Makedonya’da kurumlar kazanç vergisi oranı %10’dur. Vergi, vergi kurallarına göre gelir ve giderler düzeltildikten sonra vergi bilançosunda belirlenen vergilendirilebilir kazanç üzerinden hesaplanır. Beyanname yıllık olarak verilir.',
          },
          {
            q: 'Kişisel gelir vergisi oranı nedir?',
            a: 'Kişisel gelir vergisi %10’dur ve maaşlara, bağımsız faaliyet gelirlerine, telif sözleşmelerine ve diğer gelir türlerine uygulanır. Maaşlar için vergi, işveren tarafından MPIN aracılığıyla kesilir ve ödenir. Diğer gelirler için beyan yükümlülüğü gerçek kişiye aittir.',
          },
          {
            q: 'Götürü vergi mükellefi (paušal) nedir?',
            a: 'Götürü vergi mükellefi (genellikle küçük bir zanaatkâr veya şahıs şirketi sahibi), vergi yükümlülüğü gerçek muhasebe sonuçlarına göre değil, sabit bir götürü tutar olarak belirlenen kişidir. Bu, kayıt tutmayı basitleştirir ancak yalnızca belirli faaliyetler ve belirli bir gelir eşiğine kadar geçerlidir.',
          },
        ],
      },
      {
        heading: 'Muhasebe',
        qa: [
          {
            q: 'Muhasebeciye ihtiyacım var mı?',
            a: 'Kuzey Makedonya’daki her ticari şirket, muhasebe kurallarına göre ticari defterler tutmakla yükümlüdür; bu da pratikte lisanslı bir muhasebeci veya muhasebe bürosuyla çalışmayı gerektirir. Nitelikli bir kişiniz varsa defterleri şirket içinde de tutabilirsiniz. Facturino işin büyük kısmını otomatikleştirir — faturalama, kayıt ve raporlar — ve muhasebecinizle iş birliğini kolaylaştırır.',
          },
          {
            q: 'Yıllık hesap nedir ve ne zaman verilir?',
            a: 'Yıllık hesap, bir şirketin tamamlanan iş yılı için sunduğu bir dizi finansal tablodur (bilanço, gelir tablosu ve ekli formlar). Merkezi Sicile, genellikle yeni yılın ilk aylarında, belirlenen süre içinde sunulur. Yıllık hesap ayrıca vergi bilançosunun ve kazanç vergisi hesabının da temelidir.',
          },
          {
            q: 'Facturino nasıl yardımcı olur?',
            a: 'Facturino, Kuzey Makedonya için uyarlanmış bir muhasebe sistemidir — fatura ve e-fatura düzenleme, Makedonya hesap planına göre otomatik kayıt, KDV ve bordro hesaplama ve rapor hazırlama. Bu, manuel hataları azaltır ve zaman kazandırır; siz ve muhasebeciniz aynı, güncel veriler üzerinde çalışırsınız.',
          },
        ],
      },
    ],
    ctaTitle: 'İşinizi Facturino ile daha kolay yönetin',
    ctaBtn: 'Ücretsiz deneyin →',
  },
} as const

type QA = { q: string; a: string }

const HOME_LABEL: Record<Locale, string> = {
  mk: 'Почетна',
  sq: 'Ballina',
  tr: 'Ana Sayfa',
  en: 'Home',
}

// Internal links surfaced in a few answers, keyed by Macedonian question text.
const LINKS: Record<string, { label: Record<Locale, string>; href: (l: Locale) => string }> = {
  'Како да отворам фирма во Македонија?': {
    label: { mk: 'Водич за отворање фирма', en: 'Company registration guide', sq: 'Udhëzuesi për hapjen e firmës', tr: 'Şirket kurma kılavuzu' },
    href: (l) => `/${l}/otvori-firma`,
  },
  'Што е е-фактура и кога станува задолжителна?': {
    label: { mk: 'Водич за е-фактура', en: 'E-invoice guide', sq: 'Udhëzuesi për e-faturën', tr: 'E-fatura kılavuzu' },
    href: (l) => `/${l}/e-faktura/vodic`,
  },
  'Како се пресметува нето плата од бруто?': {
    label: { mk: 'Калкулатор за плата', en: 'Salary calculator', sq: 'Kalkulatori i pagës', tr: 'Maaş hesaplayıcı' },
    href: (l) => `/${l}/alati/plata-kalkulator`,
  },
}

export default async function PrashanjaIOdgovoriPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const breadcrumbLd = breadcrumbJsonLd([
    { name: HOME_LABEL[locale], href: `/${locale}` },
    { name: t.hero.title, href: `/${locale}/prashanja-i-odgovori` },
  ])

  // ALL Q&A always in Macedonian for the FAQPage rich-result schema.
  const faqLd = faqJsonLd(
    copy.mk.categories.flatMap((c) => c.qa.map((item: QA) => ({ question: item.q, answer: item.a })))
  )

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqLd) }} />

      <section className="bg-gradient-to-b from-indigo-50 to-white">
        <div className="container max-w-4xl mx-auto px-4 sm:px-6 py-12 md:py-16">
          <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">
            {t.hero.badge}
          </span>
          <h1 className="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">{t.hero.title}</h1>
          <p className="text-lg text-gray-600 max-w-2xl">{t.hero.sub}</p>
        </div>
      </section>

      <section className="container max-w-4xl mx-auto px-4 sm:px-6 py-10 md:py-14">
        <div className="space-y-12">
          {t.categories.map((cat, ci) => (
            <div key={ci}>
              <h2 className="text-xl md:text-2xl font-bold text-gray-900 mb-5 pb-2 border-b border-gray-100">
                {cat.heading}
              </h2>
              <div className="space-y-6">
                {cat.qa.map((item: QA, qi) => {
                  const mkQuestion = copy.mk.categories[ci].qa[qi].q
                  const link = LINKS[mkQuestion]
                  return (
                    <div key={qi} className="card">
                      <h3 className="text-base md:text-lg font-semibold text-gray-900 mb-2">{item.q}</h3>
                      <p className="text-gray-600 leading-relaxed">{item.a}</p>
                      {link && (
                        <Link
                          href={link.href(locale)}
                          className="inline-block mt-3 text-sm font-medium text-indigo-600 hover:text-indigo-700"
                        >
                          {link.label[locale]} →
                        </Link>
                      )}
                    </div>
                  )
                })}
              </div>
            </div>
          ))}
        </div>
      </section>

      <section className="bg-gradient-to-br from-indigo-600 to-cyan-500">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6 py-12 text-center text-white">
          <h2 className="text-2xl md:text-3xl font-extrabold mb-6">{t.ctaTitle}</h2>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-block bg-white text-indigo-700 font-bold px-8 py-3.5 rounded-xl hover:bg-indigo-50 transition-colors text-lg shadow-lg"
          >
            {t.ctaBtn}
          </a>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
