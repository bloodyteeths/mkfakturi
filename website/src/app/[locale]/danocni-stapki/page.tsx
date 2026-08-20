import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd, faqJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/danocni-stapki', {
    title: {
      mk: 'Даночни стапки во Македонија 2026 — ДДВ, данок на добивка, персонален данок',
      en: 'Tax Rates in North Macedonia 2026 — VAT, Profit Tax, Income Tax',
      sq: 'Normat tatimore në Maqedoni 2026 — TVSH, tatimi mbi fitimin, tatimi personal',
      tr: 'Kuzey Makedonya Vergi Oranları 2026 — KDV, Kurumlar Vergisi, Gelir Vergisi',
    },
    description: {
      mk: 'Целосна референца за даночните стапки во Македонија 2026: ДДВ 18%, 5% и 10%, данок на добивка 10%, персонален данок 10%, социјални придонеси 28% и основица за придонеси. Извор: УЈП.',
      en: 'Complete reference for tax rates in North Macedonia 2026: VAT 18%, 5% and 10%, profit tax 10%, personal income tax 10%, social contributions 28%, and contribution base. Source: UJP.',
      sq: 'Referencë e plotë për normat tatimore në Maqedoni 2026: TVSH 18%, 5% dhe 10%, tatimi mbi fitimin 10%, tatimi personal 10%, kontributet sociale 28% dhe baza e kontributeve. Burimi: DAP.',
      tr: 'Kuzey Makedonya 2026 vergi oranları için eksiksiz referans: KDV %18, %5 ve %10, kurumlar vergisi %10, gelir vergisi %10, sosyal katkılar %28 ve katkı matrahı. Kaynak: UJP.',
    },
    datePublished: '2026-08-20',
  })
}

const copy = {
  mk: {
    backLink: '← Ресурси',
    tag: 'Референца',
    title: 'Даночни стапки во Македонија 2026',
    publishDate: '20 август 2026',
    readTime: '8 мин читање',
    intro:
      'Оваа страница е ажурирана референца за сите главни даночни стапки во Северна Македонија за 2026 година: данок на додадена вредност (ДДВ), данок на добивка, персонален данок на доход и социјалните придонеси од плата. Стапките се утврдени со закон и се администрираат од Управата за јавни приходи (УЈП). Овде ги наоѓате точните проценти, праговите и основиците на едно место — идеално за брза проверка при фактурирање, пресметка на плата или годишна даночна пријава.',
    sections: [
      {
        title: 'Преглед на даночниот систем',
        content:
          'Даночниот систем во Македонија е познат по своите ниски и рамни стапки, што го прави еден од најповолните во регионот. Главните даноци што ги плаќаат бизнисите и физичките лица се: ДДВ на прометот на добра и услуги, данок на добивка на профитот на компаниите, персонален данок на доход на платите и другите приходи, и социјалните придонеси кои се одбиваат од бруто платата. Сите стапки наведени овде важат за 2026 година — имајте предвид дека стапките, праговите и основиците можат да се менуваат со закон, па секогаш проверете ги најновите објави на УЈП пред финална пресметка.',
        items: null,
        steps: null,
      },
      {
        title: 'Стапки на ДДВ: 18%, 5% и 10%',
        content:
          'ДДВ е најголемиот индиректен данок во Македонија и има три стапки. Регистрацијата за ДДВ е задолжителна кога вкупниот промет надминува 2.000.000 МКД. Еве што опфаќа секоја стапка:',
        items: [
          'Општа стапка 18% — се применува на повеќето добра и услуги: електроника, облека, професионални услуги, угостителство и транспорт.',
          'Намалена стапка 5% — за основни прехранбени производи (леб, млеко, масло), лекови и медицински помагала, книги и учебници.',
          'Намалена стапка 10% — за одделни категории добра и услуги предвидени со закон, како одредени угостителски и туристички услуги.',
          'Праг за регистрација — задолжителна ДДВ регистрација кога прометот надминува 2.000.000 МКД.',
          'Facturino автоматски ја применува точната ДДВ стапка по ставка и генерира извештај подготвен за УЈП.',
        ],
        steps: null,
      },
      {
        title: 'Данок на добивка: 10%',
        content:
          'Данокот на добивка (корпоративен данок) во Македонија изнесува рамни 10% на даночната основа. Даночната основа се пресметува како разлика меѓу вкупните приходи и вкупните признати расходи на компанијата во текот на фискалната година. Со стапка од 10%, македонскиот данок на добивка е меѓу најниските во Европа. Facturino автоматски ги следи приходите и расходите и генерира податоци подготвени за годишната пријава ДБ-ВП.',
        items: null,
        steps: null,
      },
      {
        title: 'Персонален данок и придонеси (28%)',
        content:
          'Платите во Македонија подлежат на персонален данок на доход и на социјални придонеси. Персоналниот данок изнесува рамни 10%, а личното ослободување е 10.932 МКД месечно кое ја намалува даночната основа. Социјалните придонеси се одбиваат од бруто платата и вкупно изнесуваат 28%, распределени вака:',
        items: [
          'Пензиско и инвалидско осигурување — 18,8%',
          'Здравствено осигурување — 7,5%',
          'Осигурување во случај на невработеност — 1,2%',
          'Дополнителен придонес (здравствено осигурување) — 0,5%',
          'Вкупно социјални придонеси — 28% од бруто платата',
          'Персонален данок на доход — 10% на основицата по личното ослободување',
          'Лично ослободување — 10.932 МКД месечно',
        ],
        steps: null,
      },
      {
        title: 'Како се оданочува плата: чекор по чекор',
        content:
          'Пресметката на нето платата од бруто платата минува низ неколку чекори. Основицата за придонеси во 2026 има минимум од 34.570 МКД и максимум од 1.106.256 МКД. Еве го редоследот:',
        items: null,
        steps: [
          { step: 'Почни од бруто платата', desc: 'Бруто платата е основа за целата пресметка. Таа е и вкупниот трошок за работодавачот — нема дополнителни давачки над бруто износот.' },
          { step: 'Одбиј ги придонесите (28%)', desc: 'Од бруто платата се одбиваат социјалните придонеси од 28% (пензиско 18,8%, здравствено 7,5%, невработеност 1,2%, дополнителен 0,5%). Основицата за придонеси е ограничена на минимум 34.570 и максимум 1.106.256 МКД.' },
          { step: 'Пресметај ја даночната основа', desc: 'Од износот по одбивање на придонесите се одзема личното ослободување од 10.932 МКД за да се добие основицата за персонален данок.' },
          { step: 'Примени персонален данок 10%', desc: 'На даночната основа се применува стапка од 10% персонален данок на доход.' },
          { step: 'Добиј ја нето платата', desc: 'Нето платата е бруто минус придонеси минус персонален данок. Facturino ја врши целата пресметка автоматски и заокружува на денар за МПІН пријавата.' },
        ],
      },
      {
        title: 'Како Facturino го олеснува тоа',
        content:
          'Facturino автоматски ги пресметува ДДВ, платите и данокот на добивка според актуелните стапки за 2026 година. Наместо рачно да ги следите праговите и процентите, платформата ги применува точните стапки при секое фактурирање и пресметка на плата, ги заокружува износите за УЈП и генерира извештаи подготвени за пријавите. Ова го намалува ризикот од грешки и заштедува значително време при усогласувањето со даночните обврски.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани ресурси',
    relatedArticles: [
      { slug: 'alati/ddv-kalkulator', title: 'ДДВ калкулатор' },
      { slug: 'alati/danok-dobivka-kalkulator', title: 'Калкулатор за данок на добивка' },
      { slug: 'danocni-obrasci', title: 'Даночни обрасци' },
      { slug: 'blog/ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
    ],
    bottomCta: {
      title: 'Автоматизирајте ги даночните пресметки',
      subtitle: 'Facturino ги пресметува ДДВ, платите и данокот на добивка според актуелните стапки.',
      cta: 'Започнете бесплатно →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  en: {
    backLink: '← Resources',
    tag: 'Reference',
    title: 'Tax Rates in North Macedonia 2026',
    publishDate: 'August 20, 2026',
    readTime: '8 min read',
    intro:
      'This page is an up-to-date reference for all the main tax rates in North Macedonia for 2026: value added tax (VAT), profit tax, personal income tax, and the social contributions on salaries. The rates are set by law and administered by the Public Revenue Office (UJP). Here you will find the exact percentages, thresholds, and bases in one place — ideal for a quick check when invoicing, running payroll, or filing an annual tax return.',
    sections: [
      {
        title: 'Overview of the tax system',
        content:
          'The Macedonian tax system is known for its low, flat rates, making it one of the most favorable in the region. The main taxes paid by businesses and individuals are: VAT on the supply of goods and services, profit tax on company profits, personal income tax on salaries and other income, and social contributions deducted from gross salary. All rates listed here apply for 2026 — keep in mind that rates, thresholds, and bases can change by law, so always verify the latest UJP announcements before a final calculation.',
        items: null,
        steps: null,
      },
      {
        title: 'VAT rates: 18%, 5% and 10%',
        content:
          'VAT is the largest indirect tax in North Macedonia and has three rates. VAT registration is mandatory once total turnover exceeds 2,000,000 MKD. Here is what each rate covers:',
        items: [
          'General rate 18% — applies to most goods and services: electronics, clothing, professional services, hospitality, and transport.',
          'Reduced rate 5% — for basic food products (bread, milk, butter), medicines and medical devices, books and textbooks.',
          'Reduced rate 10% — for specific categories of goods and services provided by law, such as certain hospitality and tourism services.',
          'Registration threshold — VAT registration is mandatory once turnover exceeds 2,000,000 MKD.',
          'Facturino automatically applies the correct VAT rate per line item and generates a report ready for UJP.',
        ],
        steps: null,
      },
      {
        title: 'Profit tax: 10%',
        content:
          'Profit tax (corporate tax) in North Macedonia is a flat 10% on the tax base. The tax base is calculated as the difference between total revenues and total recognized expenses of the company during the fiscal year. At a rate of 10%, Macedonian profit tax is among the lowest in Europe. Facturino automatically tracks revenues and expenses and generates data ready for the annual DB-VP return.',
        items: null,
        steps: null,
      },
      {
        title: 'Personal income tax and contributions (28%)',
        content:
          'Salaries in North Macedonia are subject to personal income tax and social contributions. Personal income tax is a flat 10%, and the personal deduction of 10,932 MKD per month reduces the tax base. Social contributions are deducted from the gross salary and total 28%, allocated as follows:',
        items: [
          'Pension and disability insurance — 18.8%',
          'Health insurance — 7.5%',
          'Unemployment insurance — 1.2%',
          'Additional contribution (health insurance) — 0.5%',
          'Total social contributions — 28% of gross salary',
          'Personal income tax — 10% on the base after the personal deduction',
          'Personal deduction — 10,932 MKD per month',
        ],
        steps: null,
      },
      {
        title: 'How a salary is taxed: step by step',
        content:
          'Calculating net salary from gross salary goes through several steps. The contribution base in 2026 has a minimum of 34,570 MKD and a maximum of 1,106,256 MKD. Here is the order:',
        items: null,
        steps: [
          { step: 'Start from the gross salary', desc: 'The gross salary is the basis for the whole calculation. It is also the total cost for the employer — there are no add-on charges above the gross amount.' },
          { step: 'Deduct the contributions (28%)', desc: 'Social contributions of 28% are deducted from the gross salary (pension 18.8%, health 7.5%, unemployment 1.2%, additional 0.5%). The contribution base is capped at a minimum of 34,570 and a maximum of 1,106,256 MKD.' },
          { step: 'Calculate the tax base', desc: 'The personal deduction of 10,932 MKD is subtracted from the amount after contributions to arrive at the base for personal income tax.' },
          { step: 'Apply 10% income tax', desc: 'A flat 10% personal income tax rate is applied to the tax base.' },
          { step: 'Arrive at the net salary', desc: 'The net salary is gross minus contributions minus personal income tax. Facturino performs the entire calculation automatically and rounds to the denar for the MPIN return.' },
        ],
      },
      {
        title: 'How Facturino handles it',
        content:
          'Facturino automatically calculates VAT, payroll, and profit tax according to the current rates for 2026. Instead of tracking thresholds and percentages manually, the platform applies the exact rates for every invoice and payroll run, rounds amounts for UJP, and generates reports ready for filing. This reduces the risk of errors and saves significant time in meeting tax obligations.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Related resources',
    relatedArticles: [
      { slug: 'alati/ddv-kalkulator', title: 'VAT Calculator' },
      { slug: 'alati/danok-dobivka-kalkulator', title: 'Profit Tax Calculator' },
      { slug: 'danocni-obrasci', title: 'Tax Forms' },
      { slug: 'blog/ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
    ],
    bottomCta: {
      title: 'Automate your tax calculations',
      subtitle: 'Facturino calculates VAT, payroll, and profit tax according to the current rates.',
      cta: 'Start Free →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  sq: {
    backLink: '← Burime',
    tag: 'Referencë',
    title: 'Normat tatimore në Maqedoni 2026',
    publishDate: '20 gusht 2026',
    readTime: '8 min lexim',
    intro:
      'Kjo faqe është një referencë e përditësuar për të gjitha normat kryesore tatimore në Maqedoninë e Veriut për vitin 2026: tatimi mbi vlerën e shtuar (TVSH), tatimi mbi fitimin, tatimi personal mbi të ardhurat dhe kontributet sociale mbi pagat. Normat përcaktohen me ligj dhe administrohen nga Zyra e të Ardhurave Publike (DAP). Këtu gjeni përqindjet e sakta, pragjet dhe bazat në një vend — ideale për një kontroll të shpejtë gjatë faturimit, llogaritjes së pagës ose deklaratës vjetore tatimore.',
    sections: [
      {
        title: 'Përmbledhje e sistemit tatimor',
        content:
          'Sistemi tatimor maqedonas njihet për normat e tij të ulëta dhe të sheshta, gjë që e bën një nga më të favorshmit në rajon. Tatimet kryesore që paguajnë bizneset dhe individët janë: TVSH mbi furnizimin e mallrave dhe shërbimeve, tatimi mbi fitimin e kompanive, tatimi personal mbi pagat dhe të ardhurat e tjera, dhe kontributet sociale që zbriten nga paga bruto. Të gjitha normat e listuara këtu vlejnë për 2026 — mbani parasysh se normat, pragjet dhe bazat mund të ndryshojnë me ligj, prandaj gjithmonë verifikoni njoftimet më të fundit të DAP para një llogaritjeje përfundimtare.',
        items: null,
        steps: null,
      },
      {
        title: 'Normat e TVSH-së: 18%, 5% dhe 10%',
        content:
          'TVSH është tatimi më i madh indirekt në Maqedoni dhe ka tre norma. Regjistrimi për TVSH është i detyrueshëm kur xhiroja totale tejkalon 2.000.000 MKD. Ja çfarë mbulon secila normë:',
        items: [
          'Norma e përgjithshme 18% — zbatohet për shumicën e mallrave dhe shërbimeve: elektronikë, veshje, shërbime profesionale, mikpritje dhe transport.',
          'Norma e ulur 5% — për produkte ushqimore bazë (bukë, qumësht, gjalpë), ilaçe dhe pajisje mjekësore, libra dhe tekste shkollore.',
          'Norma e ulur 10% — për kategori të veçanta mallrash dhe shërbimesh të parashikuara me ligj, si disa shërbime mikpritjeje dhe turizmi.',
          'Pragu i regjistrimit — regjistrimi për TVSH është i detyrueshëm kur xhiroja tejkalon 2.000.000 MKD.',
          'Facturino zbaton automatikisht normën e saktë të TVSH-së për çdo artikull dhe gjeneron raport të gatshëm për DAP.',
        ],
        steps: null,
      },
      {
        title: 'Tatimi mbi fitimin: 10%',
        content:
          'Tatimi mbi fitimin (tatimi korporativ) në Maqedoni është i sheshtë 10% mbi bazën tatimore. Baza tatimore llogaritet si diferenca midis të ardhurave totale dhe shpenzimeve totale të njohura të kompanisë gjatë vitit fiskal. Me normën 10%, tatimi maqedonas mbi fitimin është ndër më të ulëtat në Europë. Facturino ndjek automatikisht të ardhurat dhe shpenzimet dhe gjeneron të dhëna të gatshme për deklaratën vjetore DB-VP.',
        items: null,
        steps: null,
      },
      {
        title: 'Tatimi personal dhe kontributet (28%)',
        content:
          'Pagat në Maqedoni i nënshtrohen tatimit personal mbi të ardhurat dhe kontributeve sociale. Tatimi personal është i sheshtë 10%, dhe zbritja personale prej 10.932 MKD në muaj ul bazën tatimore. Kontributet sociale zbriten nga paga bruto dhe në total janë 28%, të shpërndara si më poshtë:',
        items: [
          'Sigurimi pensional dhe invalidor — 18,8%',
          'Sigurimi shëndetësor — 7,5%',
          'Sigurimi për papunësi — 1,2%',
          'Kontribut shtesë (sigurim shëndetësor) — 0,5%',
          'Total i kontributeve sociale — 28% e pagës bruto',
          'Tatimi personal mbi të ardhurat — 10% mbi bazën pas zbritjes personale',
          'Zbritja personale — 10.932 MKD në muaj',
        ],
        steps: null,
      },
      {
        title: 'Si tatohet një pagë: hap pas hapi',
        content:
          'Llogaritja e pagës neto nga paga bruto kalon nëpër disa hapa. Baza e kontributeve në 2026 ka një minimum prej 34.570 MKD dhe një maksimum prej 1.106.256 MKD. Ja rendi:',
        items: null,
        steps: [
          { step: 'Nisu nga paga bruto', desc: 'Paga bruto është baza për të gjithë llogaritjen. Ajo është gjithashtu kostoja totale për punëdhënësin — nuk ka pagesa shtesë mbi shumën bruto.' },
          { step: 'Zbrit kontributet (28%)', desc: 'Kontributet sociale prej 28% zbriten nga paga bruto (pensional 18,8%, shëndetësor 7,5%, papunësi 1,2%, shtesë 0,5%). Baza e kontributeve kufizohet me minimum 34.570 dhe maksimum 1.106.256 MKD.' },
          { step: 'Llogarit bazën tatimore', desc: 'Zbritja personale prej 10.932 MKD hiqet nga shuma pas kontributeve për të arritur bazën për tatimin personal.' },
          { step: 'Zbato tatimin personal 10%', desc: 'Një normë e sheshtë prej 10% tatim personal mbi të ardhurat zbatohet mbi bazën tatimore.' },
          { step: 'Arrij pagën neto', desc: 'Paga neto është bruto minus kontributet minus tatimi personal. Facturino kryen të gjithë llogaritjen automatikisht dhe rrumbullakos në denar për deklaratën MPIN.' },
        ],
      },
      {
        title: 'Si e menaxhon Facturino',
        content:
          'Facturino llogarit automatikisht TVSH-në, pagat dhe tatimin mbi fitimin sipas normave aktuale për 2026. Në vend që të ndiqni pragjet dhe përqindjet manualisht, platforma zbaton normat e sakta për çdo faturë dhe pagë, rrumbullakos shumat për DAP dhe gjeneron raporte të gatshme për dorëzim. Kjo ul rrezikun e gabimeve dhe kursen kohë të konsiderueshme në përmbushjen e detyrimeve tatimore.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'Burime të lidhura',
    relatedArticles: [
      { slug: 'alati/ddv-kalkulator', title: 'Kalkulator TVSH' },
      { slug: 'alati/danok-dobivka-kalkulator', title: 'Kalkulator i tatimit mbi fitimin' },
      { slug: 'danocni-obrasci', title: 'Formularë tatimorë' },
      { slug: 'blog/ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
    ],
    bottomCta: {
      title: 'Automatizoni llogaritjet tatimore',
      subtitle: 'Facturino llogarit TVSH-në, pagat dhe tatimin mbi fitimin sipas normave aktuale.',
      cta: 'Filloni falas →',
      href: 'https://app.facturino.mk/signup',
    },
  },
  tr: {
    backLink: '← Kaynaklar',
    tag: 'Referans',
    title: 'Kuzey Makedonya Vergi Oranları 2026',
    publishDate: '20 Ağustos 2026',
    readTime: '8 dk okuma',
    intro:
      'Bu sayfa, Kuzey Makedonya\'daki 2026 yılına ait tüm ana vergi oranları için güncel bir referanstır: katma değer vergisi (KDV), kurumlar vergisi, gelir vergisi ve maaşlar üzerindeki sosyal katkılar. Oranlar kanunla belirlenir ve Kamu Gelir İdaresi (UJP) tarafından yönetilir. Kesin yüzdeleri, eşikleri ve matrahları tek bir yerde bulacaksınız — faturalama, bordro hesaplama veya yıllık vergi beyannamesi sırasında hızlı bir kontrol için idealdir.',
    sections: [
      {
        title: 'Vergi sistemine genel bakış',
        content:
          'Makedonya vergi sistemi, düşük ve sabit oranlarıyla tanınır ve bu da onu bölgedeki en elverişli sistemlerden biri yapar. İşletmelerin ve bireylerin ödediği ana vergiler şunlardır: mal ve hizmet arzı üzerindeki KDV, şirket karları üzerindeki kurumlar vergisi, maaşlar ve diğer gelirler üzerindeki gelir vergisi ve brüt maaştan kesilen sosyal katkılar. Burada listelenen tüm oranlar 2026 için geçerlidir — oranların, eşiklerin ve matrahların kanunla değişebileceğini unutmayın, bu nedenle nihai bir hesaplamadan önce her zaman en son UJP duyurularını doğrulayın.',
        items: null,
        steps: null,
      },
      {
        title: 'KDV oranları: %18, %5 ve %10',
        content:
          'KDV, Kuzey Makedonya\'daki en büyük dolaylı vergidir ve üç oranı vardır. Toplam ciro 2.000.000 MKD\'yi aştığında KDV kaydı zorunludur. Her oranın kapsadığı şey şudur:',
        items: [
          'Genel oran %18 — çoğu mal ve hizmete uygulanır: elektronik, giyim, profesyonel hizmetler, konaklama ve ulaşım.',
          'İndirimli oran %5 — temel gıda ürünleri (ekmek, süt, tereyağı), ilaç ve tıbbi cihazlar, kitap ve ders kitapları için.',
          'İndirimli oran %10 — kanunla öngörülen belirli mal ve hizmet kategorileri için, örneğin bazı konaklama ve turizm hizmetleri.',
          'Kayıt eşiği — ciro 2.000.000 MKD\'yi aştığında KDV kaydı zorunludur.',
          'Facturino, her kalem için doğru KDV oranını otomatik olarak uygular ve UJP için hazır rapor oluşturur.',
        ],
        steps: null,
      },
      {
        title: 'Kurumlar vergisi: %10',
        content:
          'Kuzey Makedonya\'da kurumlar vergisi, vergi matrahı üzerinden sabit %10\'dur. Vergi matrahı, mali yıl boyunca şirketin toplam gelirleri ile toplam kabul edilen giderleri arasındaki fark olarak hesaplanır. %10 oranıyla Makedonya kurumlar vergisi, Avrupa\'nın en düşükleri arasındadır. Facturino, gelir ve giderleri otomatik olarak takip eder ve yıllık DB-VP beyannamesi için hazır veri üretir.',
        items: null,
        steps: null,
      },
      {
        title: 'Gelir vergisi ve katkılar (%28)',
        content:
          'Kuzey Makedonya\'daki maaşlar, gelir vergisine ve sosyal katkılara tabidir. Gelir vergisi sabit %10\'dur ve aylık 10.932 MKD\'lik kişisel indirim, vergi matrahını azaltır. Sosyal katkılar brüt maaştan kesilir ve toplam %28\'dir, şu şekilde dağıtılır:',
        items: [
          'Emeklilik ve maluliyet sigortası — %18,8',
          'Sağlık sigortası — %7,5',
          'İşsizlik sigortası — %1,2',
          'Ek katkı (sağlık sigortası) — %0,5',
          'Toplam sosyal katkılar — brüt maaşın %28\'i',
          'Gelir vergisi — kişisel indirim sonrası matrah üzerinden %10',
          'Kişisel indirim — aylık 10.932 MKD',
        ],
        steps: null,
      },
      {
        title: 'Bir maaş nasıl vergilendirilir: adım adım',
        content:
          'Brüt maaştan net maaşın hesaplanması birkaç adımdan geçer. 2026 katkı matrahının minimumu 34.570 MKD, maksimumu 1.106.256 MKD\'dir. Sıralama şöyledir:',
        items: null,
        steps: [
          { step: 'Brüt maaştan başlayın', desc: 'Brüt maaş, tüm hesaplamanın temelidir. Aynı zamanda işveren için toplam maliyettir — brüt tutarın üzerinde ek bir yük yoktur.' },
          { step: 'Katkıları düşün (%28)', desc: 'Brüt maaştan %28 sosyal katkı kesilir (emeklilik %18,8, sağlık %7,5, işsizlik %1,2, ek %0,5). Katkı matrahı minimum 34.570 ve maksimum 1.106.256 MKD ile sınırlandırılır.' },
          { step: 'Vergi matrahını hesaplayın', desc: 'Gelir vergisi matrahına ulaşmak için katkılar sonrası tutardan 10.932 MKD\'lik kişisel indirim çıkarılır.' },
          { step: '%10 gelir vergisi uygulayın', desc: 'Vergi matrahına sabit %10 oranında gelir vergisi uygulanır.' },
          { step: 'Net maaşa ulaşın', desc: 'Net maaş, brüt eksi katkılar eksi gelir vergisidir. Facturino tüm hesaplamayı otomatik yapar ve MPIN beyannamesi için denara yuvarlar.' },
        ],
      },
      {
        title: 'Facturino bunu nasıl yönetir',
        content:
          'Facturino, KDV, bordro ve kurumlar vergisini 2026 için geçerli oranlara göre otomatik olarak hesaplar. Eşikleri ve yüzdeleri elle takip etmek yerine, platform her fatura ve bordro işleminde kesin oranları uygular, tutarları UJP için yuvarlar ve dosyalama için hazır raporlar üretir. Bu, hata riskini azaltır ve vergi yükümlülüklerini yerine getirirken önemli ölçüde zaman kazandırır.',
        items: null,
        steps: null,
      },
    ],
    relatedTitle: 'İlgili kaynaklar',
    relatedArticles: [
      { slug: 'alati/ddv-kalkulator', title: 'KDV Hesaplayıcı' },
      { slug: 'alati/danok-dobivka-kalkulator', title: 'Kurumlar Vergisi Hesaplayıcı' },
      { slug: 'danocni-obrasci', title: 'Vergi Formları' },
      { slug: 'blog/ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
    ],
    bottomCta: {
      title: 'Vergi hesaplamalarını otomatikleştirin',
      subtitle: 'Facturino, KDV, bordro ve kurumlar vergisini güncel oranlara göre hesaplar.',
      cta: 'Ücretsiz başlayın →',
      href: 'https://app.facturino.mk/signup',
    },
  },
} as const

export default async function DanocniStapkiPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  const homeLabel = locale === 'mk' ? 'Почетна' : locale === 'sq' ? 'Ballina' : locale === 'tr' ? 'Ana Sayfa' : 'Home'

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: t.title, href: `/${locale}/danocni-stapki` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(faqJsonLd([
        { question: 'Колку изнесува ДДВ во Македонија во 2026?', answer: 'Општата стапка на ДДВ е 18%, а постојат и намалени стапки од 5% и 10%. Регистрацијата за ДДВ е задолжителна кога прометот надминува 2.000.000 МКД.' },
        { question: 'Колку е данокот на добивка?', answer: 'Данокот на добивка (корпоративен данок) во Македонија изнесува рамни 10% на даночната основа.' },
        { question: 'Колку е персоналниот данок на доход?', answer: 'Персоналниот данок на доход изнесува рамни 10%. Личното ослободување е 10.932 МКД месечно кое ја намалува даночната основа.' },
        { question: 'Колку изнесуваат социјалните придонеси од плата?', answer: 'Вкупните социјални придонеси од бруто платата изнесуваат 28%: пензиско 18,8%, здравствено 7,5%, невработеност 1,2% и дополнителен 0,5%.' },
        { question: 'Која е основицата за придонеси во 2026?', answer: 'Основицата за придонеси во 2026 има минимум од 34.570 МКД и максимум од 1.106.256 МКД. Стапките можат да се менуваат со закон — проверете на УЈП.' },
      ])) }} />

      <div className="bg-gradient-to-b from-blue-50 to-white min-h-screen">
        <div className="max-w-3xl mx-auto px-4 py-12 sm:px-6">
          <Link href={`/${locale}/resursi`} className="text-blue-600 hover:text-blue-800 text-sm font-medium mb-8 inline-block">
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
                  {s.content && (
                    <p className="text-gray-700 leading-relaxed mb-4">{s.content}</p>
                  )}

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
                    href={`/${locale}/${ra.slug}`}
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
// CLAUDE-CHECKPOINT
