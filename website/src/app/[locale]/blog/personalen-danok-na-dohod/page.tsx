import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/personalen-danok-na-dohod', {
    title: {
      mk: 'Персонален данок на доход во Македонија | Facturino',
      en: 'Personal Income Tax in Macedonia | Facturino',
      sq: 'Tatimi mbi të ardhurat personale në Maqedoni | Facturino',
      tr: 'Makedonya\'da kişisel gelir vergisi | Facturino',
    },
    description: {
      mk: 'Водич за персоналниот данок на доход: стапка од 10%, оданочиви приходи, неоданочиви надоместоци, рок за годишна пријава и обврски за фриленсери.',
      en: 'Guide to personal income tax: 10% rate, taxable income types, non-taxable allowances, annual return deadline, and freelancer obligations.',
      sq: 'Udhëzues për tatimin mbi të ardhurat personale: norma 10%, llojet e të ardhurave të tatueshme, shtesat e patatueshme, afati i deklaratës vjetore dhe detyrimet e freelancer-ëve.',
      tr: 'Kişisel gelir vergisi rehberi: %10 oran, vergilendirilebilir gelir türleri, vergiden muaf ödenekler, yıllık beyanname süresi ve serbest çalışan yükümlülükleri.',
    },
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Едукација',
    title: 'Персонален данок на доход во Македонија',
    publishDate: '6 февруари 2026',
    readTime: '7 мин читање',
    intro: 'Персоналниот данок на доход (ПДД) ги засега сите физички лица кои остваруваат приход во Македонија — од вработени и фриленсери до лица кои издаваат имот под закуп. Со единствена стапка од 10%, Македонија има еден од најпоедноставените даночни системи во регионот. Сепак, постојат правила за тоа кои приходи се оданочиви, кои се ослободени и кои рокови мора да се почитуваат. Во овој водич ги покриваме сите клучни аспекти.',
    sections: [
      {
        title: 'Стапка и основни правила',
        content: 'Стапката на персоналниот данок на доход во Македонија е единствена и изнесува 10% на оданочивиот доход. Ова важи за сите видови приходи — плата, хонорари, приходи од издавање имот, капитални добивки и друго. Даночен обврзник е секое физичко лице — резидент на Македонија кое остварува приход во или надвор од земјата, како и нерезиденти кои остваруваат приход од извори во Македонија. За вработени, данокот го задржува и уплатува работодавецот при секоја исплата на плата. За самовработени и фриленсери, обврската за пријавување и плаќање е на самото лице.',
        items: null,
        steps: null,
      },
      {
        title: 'Видови оданочиви приходи',
        content: 'Персоналниот данок на доход се применува на повеќе категории приходи.',
        items: [
          'Плата и надоместоци — Основна плата, бонуси, прекувремена работа, регрес за годишен одмор и сите други примања од работен однос.',
          'Приходи од самостојна дејност — Добивка на индивидуални трговци (самовработени), вклучувајќи професионални услуги и занаетчиство.',
          'Приходи од хонорари — Авторски хонорари, хонорари за договори на дело, консултантски услуги и повремена работа.',
          'Приходи од издавање имот — Закупнина од станови, канцеларии, магацини или земјиште.',
          'Капитални добивки — Добивка од продажба на недвижности, акции, удели или други хартии од вредност.',
          'Приходи од камати и дивиденди — Камати на депозити и дивиденди од сопственички удели (оданочени со 10%).',
          'Приходи од странство — Македонски резиденти плаќаат данок и на приходи остварени надвор од земјата.',
        ],
        steps: null,
      },
      {
        title: 'Неоданочиви надоместоци и одбитоци',
        content: 'Одредени примања се целосно или делумно ослободени од персонален данок на доход.',
        items: [
          'Лично ослободување — Секој даночен обврзник има право на основно лично ослободување кое ја намалува даночната основа.',
          'Дневници за службено патување — До пропишаниот износ се ослободени од данок.',
          'Надомест за превоз до работа — Во рамки на законски утврдениот лимит.',
          'Надомест за храна — Регресот за исхрана до 2.500 МКД месечно е ослободен.',
          'Отпремнина при пензионирање — До пропишаниот износ е неоданочива.',
          'Стипендии — Стипендии за образование доделени од државни институции.',
          'Социјални трансфери — Детски додаток, социјална помош и инвалидски надоместоци.',
        ],
        steps: null,
      },
      {
        title: 'Годишна даночна пријава: Чекор по чекор',
        content: null,
        items: null,
        steps: [
          { step: 'Соберете ги сите документи', desc: 'Обезбедете ги потврдите за примени приходи од сите извори — плати, хонорари, закупнини, капитални добивки. Работодавците издаваат годишна потврда за задржан данок.' },
          { step: 'Пресметајте го вкупниот приход', desc: 'Соберете ги сите оданочиви приходи и одземете ги признатите одбитоци и лични ослободувања за да ја добиете даночната основа.' },
          { step: 'Пополнете ја пријавата ПДД-ГДП', desc: 'Внесете ги податоците во образецот ПДД-ГДП преку системот е-Даноци на УЈП. Образецот ги содржи сите категории приходи и одбитоци.' },
          { step: 'Поднесете до 15 март', desc: 'Годишната пријава за персонален данок на доход мора да се поднесе електронски до УЈП најдоцна до 15 март во тековната година за претходната календарска година.' },
          { step: 'Платете или побарајте поврат', desc: 'Ако вкупниот данок е поголем од задржаниот данок во текот на годината, платете ја разликата. Ако е помал, побарајте поврат од УЈП.' },
        ],
      },
      {
        title: 'Обврски за фриленсери и самовработени',
        content: 'Фриленсерите и самовработените имаат дополнителни обврски во однос на вработените. Тие мора самостојно да го пресметуваат и уплаќаат данокот и придонесите. Секој фриленсер кој работи по договор на дело или авторски договор е обврзан да плаќа 10% данок на бруто хонорарот намален за нормирани трошоци (обично 25-50% во зависност од дејноста). Дополнително, самовработените мора да водат сметководство, да издаваат фактури и да поднесуваат месечни и годишни пријави до УЈП. Facturino ја поедноставува оваа постапка со автоматско пресметување на данокот при секоја фактура, следење на приходите и генерирање на извештаи подготвени за даночната пријава.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Пресметка на плата во Македонија: Придонеси и даноци' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
    ],
    cta: {
      title: 'Поедноставете го пресметувањето на данокот',
      desc: 'Facturino автоматски го пресметува данокот на доход и генерира извештаи за УЈП.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Education',
    title: 'Personal Income Tax in Macedonia',
    publishDate: 'February 6, 2026',
    readTime: '7 min read',
    intro: 'Personal income tax (PIT) affects all individuals who earn income in Macedonia — from employees and freelancers to people renting out property. With a flat rate of 10%, Macedonia has one of the most simplified tax systems in the region. However, there are rules about which types of income are taxable, which are exempt, and which deadlines must be met. In this guide, we cover all the key aspects.',
    sections: [
      {
        title: 'Rate and basic rules',
        content: 'The personal income tax rate in Macedonia is a flat 10% on taxable income. This applies to all types of income — salary, fees, rental income, capital gains, and more. Every individual who is a resident of Macedonia and earns income domestically or abroad is a taxpayer, as are non-residents who earn income from sources in Macedonia. For employees, the employer withholds and remits the tax with each salary payment. For self-employed individuals and freelancers, the obligation to report and pay falls on the individual.',
        items: null,
        steps: null,
      },
      {
        title: 'Types of taxable income',
        content: 'Personal income tax applies to several categories of income.',
        items: [
          'Salary and compensation — Base salary, bonuses, overtime, vacation allowance, and all other employment-related payments.',
          'Self-employment income — Profit of sole traders (self-employed), including professional services and crafts.',
          'Fee-based income — Author royalties, service contract fees, consulting services, and occasional work.',
          'Rental income — Rent from apartments, offices, warehouses, or land.',
          'Capital gains — Profit from the sale of real estate, shares, ownership stakes, or other securities.',
          'Interest and dividend income — Deposit interest and dividends from ownership shares (taxed at 10%).',
          'Foreign income — Macedonian residents pay tax on income earned outside the country as well.',
        ],
        steps: null,
      },
      {
        title: 'Non-taxable allowances and deductions',
        content: 'Certain payments are fully or partially exempt from personal income tax.',
        items: [
          'Personal allowance — Every taxpayer is entitled to a basic personal allowance that reduces the tax base.',
          'Per diems for business travel — Exempt up to the prescribed amount.',
          'Commuting allowance — Within the legally determined limit.',
          'Meal allowance — The meal subsidy up to 2,500 MKD per month is exempt.',
          'Retirement severance — Non-taxable up to the prescribed amount.',
          'Scholarships — Educational scholarships awarded by government institutions.',
          'Social transfers — Child allowance, social assistance, and disability benefits.',
        ],
        steps: null,
      },
      {
        title: 'Annual tax return: Step by step',
        content: null,
        items: null,
        steps: [
          { step: 'Gather all documents', desc: 'Obtain income certificates from all sources — salaries, fees, rentals, capital gains. Employers issue an annual certificate for withheld tax.' },
          { step: 'Calculate total income', desc: 'Add up all taxable income and subtract recognized deductions and personal allowances to arrive at the tax base.' },
          { step: 'Fill out the PDD-GDP form', desc: 'Enter the data into the PDD-GDP form through UJP\'s e-Tax system. The form contains all income categories and deductions.' },
          { step: 'Submit by March 15', desc: 'The annual personal income tax return must be submitted electronically to UJP no later than March 15 of the current year for the previous calendar year.' },
          { step: 'Pay or request a refund', desc: 'If total tax exceeds the amount withheld during the year, pay the difference. If less, request a refund from UJP.' },
        ],
      },
      {
        title: 'Obligations for freelancers and self-employed',
        content: 'Freelancers and self-employed individuals have additional obligations compared to employees. They must independently calculate and remit tax and contributions. Every freelancer working under a service or author contract is required to pay 10% tax on gross fees reduced by standardized expenses (typically 25-50% depending on the activity). Additionally, self-employed individuals must keep accounting records, issue invoices, and submit monthly and annual returns to UJP. Facturino simplifies this process with automatic tax calculation on every invoice, income tracking, and generation of reports ready for the tax return.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Payroll Calculation in Macedonia: Contributions and Taxes' },
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
    ],
    cta: {
      title: 'Simplify your tax calculations',
      desc: 'Facturino automatically calculates income tax and generates reports for UJP.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Edukim',
    title: 'Tatimi mbi të ardhurat personale në Maqedoni',
    publishDate: '6 shkurt 2026',
    readTime: '7 min lexim',
    intro: 'Tatimi mbi të ardhurat personale (TAP) prek të gjithë individët që fitojnë të ardhura në Maqedoni — nga punonjësit dhe freelancer-ët te personat që japin me qira pronë. Me normën e sheshtë 10%, Maqedonia ka një nga sistemet tatimore më të thjeshtëzuara në rajon. Megjithatë, ka rregulla për llojet e të ardhurave që janë të tatueshme, cilat janë të përjashtuara dhe cilat afate duhet të respektohen. Në këtë udhëzues, mbulojmë të gjitha aspektet kyçe.',
    sections: [
      {
        title: 'Norma dhe rregullat bazë',
        content: 'Norma e tatimit mbi të ardhurat personale në Maqedoni është e sheshtë 10% mbi të ardhurën e tatueshme. Kjo zbatohet për të gjitha llojet e të ardhurave — paga, honorare, të ardhura nga qiraja, fitime kapitale dhe të tjera. Çdo individ që është rezident i Maqedonisë dhe fiton të ardhura brenda ose jashtë vendit është tatimpagues, si edhe jorezidentët që fitojnë të ardhura nga burime në Maqedoni. Për punonjësit, punëdhënësi mban dhe dërgon tatimin me çdo pagesë page. Për individët e vetëpunësuar dhe freelancer-ët, detyrimi për të deklaruar dhe paguar bie mbi vetë individin.',
        items: null,
        steps: null,
      },
      {
        title: 'Llojet e të ardhurave të tatueshme',
        content: 'Tatimi mbi të ardhurat personale zbatohet për disa kategori të ardhurave.',
        items: [
          'Paga dhe kompensime — Paga bazë, bonuse, punë jashtë orarit, shtesa pushimesh dhe të gjitha pagesat e tjera nga marrëdhënia e punës.',
          'Të ardhura nga vetëpunësimi — Fitimi i tregtarëve individualë (vetëpunësuar), përfshirë shërbimet profesionale dhe zanatçinërinë.',
          'Të ardhura nga honorare — Honorare autorësh, tarifa kontratash shërbimi, shërbime konsulence dhe punë rastësore.',
          'Të ardhura nga qiraja — Qiraja nga apartamente, zyra, magazina ose tokë.',
          'Fitime kapitale — Fitim nga shitja e pasurive të paluajtshme, aksioneve, kuotave ose letrave të tjera me vlerë.',
          'Të ardhura nga interesi dhe dividendët — Interesi i depozitave dhe dividendët nga kuotat e pronësisë (tatuar me 10%).',
          'Të ardhura nga jashtë — Rezidentët maqedonas paguajnë tatim edhe për të ardhurat e fituara jashtë vendit.',
        ],
        steps: null,
      },
      {
        title: 'Shtesa të patatueshme dhe zbritje',
        content: 'Pagesa të caktuara janë plotësisht ose pjesërisht të përjashtuara nga tatimi mbi të ardhurat personale.',
        items: [
          'Shtesë personale — Çdo tatimpagues ka të drejtë për një shtesë personale bazë që ul bazën tatimore.',
          'Dieta për udhëtime pune — Të përjashtuara deri në shumën e përcaktuar.',
          'Shtesë transporti — Brenda limitit të përcaktuar ligjërisht.',
          'Shtesë ushqimi — Subvencioni i ushqimit deri 2.500 MKD në muaj është i përjashtuar.',
          'Shpërblim pensionimi — I patatueshëm deri në shumën e përcaktuar.',
          'Bursa — Bursa arsimore të dhëna nga institucionet qeveritare.',
          'Transferta sociale — Shtesë fëmijësh, ndihmë sociale dhe përfitime invaliditeti.',
        ],
        steps: null,
      },
      {
        title: 'Deklarata vjetore tatimore: Hap pas hapi',
        content: null,
        items: null,
        steps: [
          { step: 'Mbledhni të gjitha dokumentet', desc: 'Siguroni çertifikatat e të ardhurave nga të gjitha burimet — paga, honorare, qira, fitime kapitale. Punëdhënësit lëshojnë çertifikatë vjetore për tatimin e mbajtur.' },
          { step: 'Llogaritni të ardhurën totale', desc: 'Mbledhni të gjitha të ardhurat e tatueshme dhe zbritni zbritjet e njohura dhe shtesat personale për të arritur bazën tatimore.' },
          { step: 'Plotësoni formularin PDD-GDP', desc: 'Futni të dhënat në formularin PDD-GDP përmes sistemit e-Tatim të UJP-së. Formulari përmban të gjitha kategoritë e të ardhurave dhe zbritjet.' },
          { step: 'Dorëzoni deri më 15 mars', desc: 'Deklarata vjetore e tatimit mbi të ardhurat personale duhet të dorëzohet elektronikisht pranë UJP-së jo më vonë se 15 mars të vitit aktual për vitin e mëparshëm kalendarik.' },
          { step: 'Paguani ose kërkoni rimbursim', desc: 'Nëse tatimi total tejkalon shumën e mbajtur gjatë vitit, paguani diferencën. Nëse është më pak, kërkoni rimbursim nga UJP.' },
        ],
      },
      {
        title: 'Detyrimet për freelancer-ët dhe vetëpunësuarit',
        content: 'Freelancer-ët dhe individët e vetëpunësuar kanë detyrime shtesë krahasuar me punonjësit. Ata duhet të llogaritsin dhe dërgojnë në mënyrë të pavarur tatimin dhe kontributet. Çdo freelancer që punon me kontratë shërbimi ose autorësie është i detyruar të paguajë 10% tatim mbi honorarin bruto të ulur për shpenzime të standardizuara (zakonisht 25-50% në varësi të aktivitetit). Gjithashtu, individët e vetëpunësuar duhet të mbajnë regjistrime kontabël, të lëshojnë fatura dhe të dorëzojnë deklarata mujore dhe vjetore pranë UJP-së. Facturino e thjeshton këtë proces me llogaritje automatike të tatimit në çdo faturë, ndjekje të të ardhurave dhe gjenerim të raporteve gati për deklaratën tatimore.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'presmetka-na-plata-mk', title: 'Llogaritja e pagës në Maqedoni: Kontributet dhe tatimet' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet për DAP' },
    ],
    cta: {
      title: 'Thjeshtoni llogaritjet tuaja tatimore',
      desc: 'Facturino automatikisht llogarit tatimin mbi të ardhurat dhe gjeneron raporte për UJP.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Eğitim',
    title: 'Makedonya\'da kişisel gelir vergisi',
    publishDate: '6 Şubat 2026',
    readTime: '7 dk okuma',
    intro: 'Kişisel gelir vergisi (KGV), Makedonya\'da gelir elde eden tüm bireyleri etkiler — çalışanlardan ve serbest çalışanlardan mülk kiralayanlarına kadar. Sabit %10 oranıyla Makedonya, bölgedeki en basitleştirilmiş vergi sistemlerinden birine sahiptir. Ancak, hangi gelir türlerinin vergilendirildiği, hangilerinin muaf olduğu ve hangi son tarihlere uyulması gerektiğine dair kurallar vardır. Bu rehberde tüm önemli hususları ele alıyoruz.',
    sections: [
      {
        title: 'Oran ve temel kurallar',
        content: 'Makedonya\'da kişisel gelir vergisi oranı, vergilendirilebilir gelir üzerinden sabit %10\'dur. Bu, tüm gelir türleri için geçerlidir — maaş, ücretler, kira geliri, sermaye kazançları ve diğerleri. Makedonya\'da ikamet eden ve yurt içinde veya yurt dışında gelir elde eden her birey vergi mükellefidir; aynı şekilde Makedonya\'daki kaynaklardan gelir elde eden yerleşik olmayanlar da vergi mükellefidir. Çalışanlar için işveren, her maaş ödemesinde vergiyi keser ve öder. Serbest çalışanlar ve freelancer\'lar için beyan ve ödeme yükümlülüğü bireyin kendisine aittir.',
        items: null,
        steps: null,
      },
      {
        title: 'Vergilendirilebilir gelir türleri',
        content: 'Kişisel gelir vergisi çeşitli gelir kategorilerine uygulanır.',
        items: [
          'Maaş ve tazminatlar — Temel maaş, ikramiyeler, fazla mesai, izin ödeneği ve istihdam ilişkisinden kaynaklanan diğer tüm ödemeler.',
          'Serbest meslek geliri — Bireysel tüccarların (serbest çalışanlar) karı, profesyonel hizmetler ve zanaatkarlık dahil.',
          'Ücret bazlı gelir — Telif ücretleri, hizmet sözleşmesi ücretleri, danışmanlık hizmetleri ve arızi çalışma.',
          'Kira geliri — Daireler, ofisler, depolar veya arazilerden elde edilen kira.',
          'Sermaye kazançları — Gayrimenkul, hisse senedi, ortaklık payları veya diğer menkul kıymetlerin satışından elde edilen kar.',
          'Faiz ve temettü geliri — Mevduat faizi ve ortaklık paylarından temettüler (%10 vergilendirilir).',
          'Yurt dışı geliri — Makedonya mukimleri yurt dışında elde ettikleri gelir üzerinden de vergi öder.',
        ],
        steps: null,
      },
      {
        title: 'Vergiden muaf ödenekler ve indirimler',
        content: 'Belirli ödemeler kişisel gelir vergisinden tamamen veya kısmen muaftır.',
        items: [
          'Kişisel indirim — Her vergi mükellefi, vergi matrahını azaltan temel kişisel indirim hakkına sahiptir.',
          'İş seyahati harcırahları — Belirlenen tutara kadar vergiden muaftır.',
          'İşe gidiş-geliş ödeneği — Yasal olarak belirlenen limit dahilinde.',
          'Yemek ödeneği — Aylık 2.500 MKD\'ye kadar yemek desteği muaftır.',
          'Emeklilik tazminatı — Belirlenen tutara kadar vergiden muaftır.',
          'Burslar — Devlet kurumları tarafından verilen eğitim bursları.',
          'Sosyal transferler — Çocuk yardımı, sosyal yardım ve engellilik ödenekleri.',
        ],
        steps: null,
      },
      {
        title: 'Yıllık vergi beyannamesi: Adım adım',
        content: null,
        items: null,
        steps: [
          { step: 'Tüm belgeleri toplayın', desc: 'Tüm kaynaklardan gelir sertifikalarını alın — maaşlar, ücretler, kiralar, sermaye kazançları. İşverenler, kesilen vergi için yıllık sertifika düzenler.' },
          { step: 'Toplam geliri hesaplayın', desc: 'Tüm vergilendirilebilir geliri toplayın ve vergi matrahına ulaşmak için tanınan indirimleri ve kişisel ödenekleri çıkarın.' },
          { step: 'PDD-GDP formunu doldurun', desc: 'UJP\'nin e-Vergi sistemi aracılığıyla PDD-GDP formuna verileri girin. Form, tüm gelir kategorilerini ve indirimleri içerir.' },
          { step: '15 Mart\'a kadar gönderin', desc: 'Yıllık kişisel gelir vergisi beyannamesi, bir önceki takvim yılı için cari yılın en geç 15 Mart\'ına kadar UJP\'ye elektronik olarak sunulmalıdır.' },
          { step: 'Ödeyin veya iade talep edin', desc: 'Toplam vergi yıl boyunca kesilen tutarı aşarsa, farkı ödeyin. Daha azsa, UJP\'den iade talep edin.' },
        ],
      },
      {
        title: 'Serbest çalışanlar ve freelancer\'lar için yükümlülükler',
        content: 'Serbest çalışanlar ve freelancer\'lar, çalışanlara kıyasla ek yükümlülüklere sahiptir. Vergi ve katkı paylarını bağımsız olarak hesaplayıp ödemeleri gerekir. Hizmet veya telif sözleşmesiyle çalışan her freelancer, standartlaştırılmış giderlerle (faaliyete bağlı olarak genellikle %25-50) azaltılmış brüt ücret üzerinden %10 vergi ödemek zorundadır. Ayrıca serbest çalışanlar muhasebe kayıtları tutmalı, fatura düzenlemeli ve UJP\'ye aylık ve yıllık beyannameler sunmalıdır. Facturino, her faturada otomatik vergi hesaplaması, gelir takibi ve vergi beyannamesi için hazır raporlar oluşturarak bu süreci basitleştirir.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'presmetka-na-plata-mk', title: "Makedonya'da maaş hesaplama: Primler ve vergiler" },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, tarihler ve hesaplama' },
      { slug: 'rokovi-ujp-2026', title: 'Vergi takvimi 2026: Tüm UJP tarihleri' },
    ],
    cta: {
      title: 'Vergi hesaplamalarınızı basitleştirin',
      desc: 'Facturino gelir vergisini otomatik hesaplar ve UJP için raporlar oluşturur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function PersonalenDanokNaDohodPage({
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
// CLAUDE-CHECKPOINT
