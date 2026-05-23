import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildArticleMetadata } from '@/lib/metadata'
import { articleJsonLd, breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildArticleMetadata(locale, '/blog/ifrs-izvesti-mk', {
    title: {
      mk: 'МСФИ/IFRS извештаи во Македонија: Кој мора и како',
      en: 'IFRS Reporting in North Macedonia: Who Must and How',
      sq: 'Raportet SNRF/IFRS në Maqedoni: Kush duhet dhe si',
      tr: 'Makedonya\'da UFRS/IFRS Raporları: Kim Zorunlu ve Nasıl',
    },
    description: {
      mk: 'Водич за МСФИ/IFRS извештаи во Македонија: кои компании мора да применуваат IFRS, разлики со МСС, задолжителни финансиски извештаи, рокови и практични совети за транзиција.',
      en: 'Guide to IFRS reporting in North Macedonia: which companies must apply IFRS, differences from MAS, required financial statements, deadlines, and practical transition tips.',
      sq: 'Udhëzues për raportet SNRF/IFRS në Maqedoni: cilat kompani duhet të zbatojnë IFRS, dallimet nga SNK, pasqyrat financiare të detyrueshme, afatet dhe këshilla praktike për tranzicion.',
      tr: 'Makedonya\'da UFRS/IFRS raporlama rehberi: hangi şirketler UFRS uygulamalı, MMS\'den farklar, zorunlu mali tablolar, son tarihler ve geçiş için pratik ipuçları.',
    },
    datePublished: '2026-05-23',
  })
}

const copy = {
  mk: {
    backLink: '← Назад кон блог',
    tag: 'Водич',
    title: 'МСФИ/IFRS извештаи во Македонија: Кој мора и како',
    publishDate: '23 мај 2026',
    readTime: '10 мин читање',
    intro: 'Северна Македонија ги усвои Меѓународните стандарди за финансиско известување (IFRS/МСФИ) за големите субјекти. Помалите компании користат поедноставени Македонски сметководствени стандарди (МСС — базирани на IFRS за МСП). Разбирањето која рамка важи за вашата компанија е клучно за усогласеност. Овој водич објаснува кој мора да користи IFRS, како функционира известувањето и кои се практичните разлики.',
    sections: [
      {
        title: 'Кој мора да користи IFRS во Македонија?',
        content: 'Согласно Законот за трговски друштва и Законот за сметководство, следните субјекти се задолжени да применуваат полни IFRS/МСФИ стандарди:',
        items: [
          'Котирани компании на Македонска берза (МБ)',
          'Банки и финансиски институции (регулирани од НБРСМ)',
          'Осигурителни компании',
          'Големи компании кои исполнуваат 2 од 3 критериуми: вкупна актива >€2М, приход >€4М, >50 вработени',
          'Инвестициски фондови и пензиски фондови',
          'Државни претпријатија над праговите за големина',
          'Сите останати субјекти користат МСС (Македонски сметководствени стандарди)',
        ],
        steps: null,
      },
      {
        title: 'МСС вс IFRS — Клучни разлики',
        content: 'Разликите меѓу двата стандарди се значајни и влијаат на сложеноста, обелоденувањата и признавањето на позициите:',
        items: null,
        steps: [
          { step: 'Финансиски извештаи', desc: 'МСС: Биланс на состојба + Биланс на успех + Белешки. IFRS: Полн комплет — БС, БУ, Парични текови, Промени во капитал, Белешки.' },
          { step: 'Фер вредност', desc: 'МСС: Ограничена употреба. IFRS: Широка примена — финансиски инструменти, инвестициски имоти.' },
          { step: 'Обелоденувања', desc: 'МСС: Поедноставени. IFRS: Опсежни — можат да бидат 100+ страници за банки.' },
          { step: 'Консолидација', desc: 'МСС: Само ако >50% контрола. IFRS: Комплексни правила вклучувајќи VIE и заеднички аранжмани.' },
          { step: 'Признавање на приходи', desc: 'МСС: Поедноставни критериуми. IFRS 15: Модел во 5 чекори со анализа на договори.' },
          { step: 'Лизинг', desc: 'МСС: Разлика меѓу оперативен и финансиски лизинг. IFRS 16: Сите лизинзи на билансот.' },
          { step: 'Обезвреднување', desc: 'МСС: Базирано на индикатори. IFRS 9: Модел на очекувани кредитни загуби за финансиски средства.' },
        ],
      },
      {
        title: 'Задолжителни финансиски извештаи',
        content: 'Компаниите кои применуваат IFRS мора да поднесат комплетен сет на финансиски извештаи:',
        items: [
          'Биланс на состојба (Balance Sheet / Statement of Financial Position)',
          'Биланс на успех (Income Statement / Statement of Comprehensive Income)',
          'Извештај за парични текови (Cash Flow Statement — директен или индиректен метод)',
          'Извештај за промени во капиталот (Statement of Changes in Equity)',
          'Белешки (Нотес — сметководствени политики, расчленувања, контингенции)',
          'Управен извештај (Management Report — за котирани компании)',
        ],
        steps: null,
      },
      {
        title: 'Рокови за поднесување и постапка',
        content: null,
        items: null,
        steps: [
          { step: 'Затворање на годината (31 декември)', desc: 'Фискалната година во Македонија се поклопува со календарската година.' },
          { step: 'Нацрт на финансиски извештаи', desc: 'Подготовка на нацрт-извештаите до крајот на јануари.' },
          { step: 'Ревизија (ако е задолжителна)', desc: 'Ревизијата мора да биде завршена пред поднесување, обично во февруари.' },
          { step: 'Поднесување на годишна сметка (ЦРСМ)', desc: 'Рок: до 28 февруари во Централен регистар на РСМ.' },
          { step: 'Даночен биланс ДБ-ВП (УЈП)', desc: 'Рок: до 15 март на etax.ujp.gov.mk.' },
          { step: 'Публикација', desc: 'ЦРСМ ги публикува извештаите на својата веб-страница — јавен пристап.' },
          { step: 'Ревизорски извештај', desc: 'Ако е задолжителна ревизија — се поднесува заедно со годишната сметка.' },
        ],
      },
      {
        title: 'Кога е задолжителна ревизија?',
        content: 'Законската ревизија е задолжителна за следните категории:',
        items: [
          'Сите IFRS приготвувачи (котирани, банки, осигурување)',
          'Компании со приход >€4М',
          'Компании со >50 вработени',
          'Компании со вкупна актива >€2М',
          'Субјекти од јавен интерес',
          'НВО со статус на јавна корист кои примаат >€100.000',
          'Ревизорот мора да биде лиценциран од ИСОС (Институт за сметководители и овластени ревизори)',
        ],
        steps: null,
      },
      {
        title: 'Практични совети за IFRS транзиција',
        content: 'Преминот од МСС на IFRS е комплексен процес. Еве неколку практични совети:',
        items: [
          'Започнете рано — IFRS 1 (First-time Adoption) бара почетен биланс',
          'Идентификувајте ги разликите меѓу МСС и IFRS за вашата компанија',
          'Мерењето на фер вредност може да бара независни проценки',
          'Обучете го сметководствениот кадар на новите стандарди',
          'Размислете за меѓупериодично IFRS известување пред задолжителното преминување',
          'Софтверот мора да поддржува IFRS контен план и известување',
          'Консултирајте се со ревизорот за време на транзицијата — не потоа',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Биланс на состојба и биланс на успех: AOP ознаки' },
      { slug: 'godishna-smetka-2025', title: 'Годишна сметка 2025: Целосен водич за поднесување' },
      { slug: 'danok-na-dobivka', title: 'Данок на добивка: Стапки, рокови и пресметка' },
    ],
    cta: {
      title: 'Facturino поддржува IFRS контен план и известување',
      desc: 'IFRS-усогласен контен план, автоматски финансиски извештаи и GL книжења — сё во една платформа.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '← Back to blog',
    tag: 'Guide',
    title: 'IFRS Reporting in North Macedonia: Who Must and How',
    publishDate: 'May 23, 2026',
    readTime: '10 min read',
    intro: 'North Macedonia adopted IFRS (International Financial Reporting Standards) for large entities. Smaller companies use simplified Macedonian Accounting Standards (MAS — based on IFRS for SMEs). Understanding which framework applies to your company is critical for compliance. This guide explains who must use IFRS, how reporting works, and the practical differences.',
    sections: [
      {
        title: 'Who Must Use IFRS in North Macedonia?',
        content: 'Under the Law on Trade Companies and the Law on Accounting, the following entities are required to apply full IFRS standards:',
        items: [
          'Listed companies on the Macedonian Stock Exchange (MSE)',
          'Banks and financial institutions (regulated by NBRSM)',
          'Insurance companies',
          'Large companies meeting 2 of 3 criteria: total assets >€2M, revenue >€4M, >50 employees',
          'Investment funds and pension funds',
          'State-owned enterprises above size thresholds',
          'All other entities use MAS (Macedonian Accounting Standards)',
        ],
        steps: null,
      },
      {
        title: 'MAS vs IFRS — Key Differences',
        content: 'The differences between the two frameworks are significant and affect complexity, disclosures, and recognition of items:',
        items: null,
        steps: [
          { step: 'Financial Statements', desc: 'MAS: Balance sheet + Income statement + Notes. IFRS: Full set — BS, IS, Cash Flow, Changes in Equity, Notes.' },
          { step: 'Fair Value', desc: 'MAS: Limited use. IFRS: Extensive — financial instruments, investment property.' },
          { step: 'Disclosures', desc: 'MAS: Simplified. IFRS: Extensive — can be 100+ pages for banks.' },
          { step: 'Consolidation', desc: 'MAS: Only if >50% control. IFRS: Complex rules including VIEs and joint arrangements.' },
          { step: 'Revenue Recognition', desc: 'MAS: Simpler criteria. IFRS 15: 5-step model with contract analysis.' },
          { step: 'Leases', desc: 'MAS: Operating vs finance distinction. IFRS 16: All leases on balance sheet.' },
          { step: 'Impairment', desc: 'MAS: Indicator-based. IFRS 9: Expected credit loss model for financial assets.' },
        ],
      },
      {
        title: 'Required Financial Statements',
        content: 'Companies applying IFRS must submit a complete set of financial statements:',
        items: [
          'Balance Sheet (Statement of Financial Position)',
          'Income Statement (Statement of Comprehensive Income)',
          'Cash Flow Statement (direct or indirect method)',
          'Statement of Changes in Equity',
          'Notes (accounting policies, breakdowns, contingencies)',
          'Management Report (for listed companies)',
        ],
        steps: null,
      },
      {
        title: 'Filing Deadlines and Process',
        content: null,
        items: null,
        steps: [
          { step: 'Year-end closing (December 31)', desc: 'The fiscal year in Macedonia coincides with the calendar year.' },
          { step: 'Draft financial statements', desc: 'Preparation of draft statements by the end of January.' },
          { step: 'Audit (if required)', desc: 'Audit must be completed before filing, usually in February.' },
          { step: 'Annual account filing (CRSM)', desc: 'Deadline: February 28 to the Central Registry of North Macedonia.' },
          { step: 'Tax balance DB-VP filing (UJP)', desc: 'Deadline: March 15 at etax.ujp.gov.mk.' },
          { step: 'Publication', desc: 'CRSM publishes the statements on its website — public access.' },
          { step: 'Audit report submission', desc: 'If audit is mandatory — submitted together with the annual account.' },
        ],
      },
      {
        title: 'When Is Audit Mandatory?',
        content: 'Statutory audit is mandatory for the following categories:',
        items: [
          'All IFRS preparers (listed, banks, insurance)',
          'Companies with revenue >€4M',
          'Companies with >50 employees',
          'Companies with total assets >€2M',
          'Public interest entities',
          'NGOs with public benefit status receiving >€100,000',
          'Statutory auditor must be licensed by ISOS (Institute of Accountants and Certified Auditors)',
        ],
        steps: null,
      },
      {
        title: 'Practical Tips for IFRS Transition',
        content: 'The transition from MAS to IFRS is a complex process. Here are some practical tips:',
        items: [
          'Start early — IFRS 1 (First-time Adoption) requires an opening balance sheet',
          'Identify differences between MAS and IFRS for your company',
          'Fair value measurement may require independent valuations',
          'Train accounting staff on the new standards',
          'Consider interim IFRS reporting before the mandatory switch',
          'Software must support IFRS chart of accounts and reporting',
          'Consult with your auditor during the transition — not after',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Balance Sheet & Income Statement: AOP Codes' },
      { slug: 'godishna-smetka-2025', title: 'Annual Accounts 2025: Complete Filing Guide' },
      { slug: 'danok-na-dobivka', title: 'Corporate Income Tax: Rates, Deadlines and Calculation' },
    ],
    cta: {
      title: 'Facturino supports IFRS chart of accounts and reporting',
      desc: 'IFRS-compliant chart of accounts, automated financial reports, and GL postings — all in one platform.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '← Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Raportet SNRF/IFRS në Maqedoni: Kush duhet dhe si',
    publishDate: '23 maj 2026',
    readTime: '10 min lexim',
    intro: 'Maqedonia e Veriut miratoi Standardet Ndërkombëtare të Raportimit Financiar (IFRS/SNRF) për subjektet e mëdha. Kompanitë më të vogla përdorin Standardet e Thjeshtuara Maqedonase të Kontabilitetit (SNK — të bazuara në IFRS për NVM). Të kuptuarit se cila kornizë zbatohet për kompaninë tuaj është vendimtare për përputhshmërinë. Ky udhëzues shpjegon kush duhet të përdorë IFRS, si funksionon raportimi dhe dallimet praktike.',
    sections: [
      {
        title: 'Kush duhet të përdorë IFRS në Maqedoni?',
        content: 'Sipas Ligjit për Shoqëritë Tregtare dhe Ligjit për Kontabilitetin, subjektet e mëposhtme janë të detyruara të zbatojnë standardet e plota IFRS/SNRF:',
        items: [
          'Kompanitë e listuara në Bursen e Maqedonisë (BM)',
          'Bankat dhe institucionet financiare (të rregulluara nga BBVM)',
          'Kompanitë e sigurimeve',
          'Kompanitë e mëdha që plotësojnë 2 nga 3 kritere: aktivet totale >€2M, të ardhurat >€4M, >50 punonjës',
          'Fondet e investimeve dhe fondet e pensioneve',
          'Ndërmarrjet shtetërore mbi pragjet e madhësisë',
          'Të gjithë subjektet e tjera përdorin SNK (Standardet Maqedonase të Kontabilitetit)',
        ],
        steps: null,
      },
      {
        title: 'SNK vs IFRS — Dallimet kryesore',
        content: 'Dallimet midis dy kornizave janë të rënësishme dhe ndikojnë në kompleksitetin, shpalosjet dhe njohjen e zërave:',
        items: null,
        steps: [
          { step: 'Pasqyrat financiare', desc: 'SNK: Bilanci + Pasqyra e të ardhurave + Shënime. IFRS: Kompleti i plotë — Bilanc, PA, Rrjedha monetare, Ndryshimet në kapital, Shënime.' },
          { step: 'Vlera e drejtë', desc: 'SNK: Përdorim i kufizuar. IFRS: I gjerë — instrumente financiare, pronë investimi.' },
          { step: 'Shpalosjet', desc: 'SNK: Të thjeshtuara. IFRS: Të gjera — mund të jenë 100+ faqe për bankat.' },
          { step: 'Konsolidimi', desc: 'SNK: Vetëm nëse >50% kontroll. IFRS: Rregulla komplekse përfshirë VIE dhe marrëveshje të përbashkëta.' },
          { step: 'Njohja e të ardhurave', desc: 'SNK: Kritere më të thjeshta. IFRS 15: Modeli me 5 hapa me analizë kontrate.' },
          { step: 'Qiratë', desc: 'SNK: Dallimi operativ vs financiar. IFRS 16: Të gjitha qiratë në bilanc.' },
          { step: 'Zhvlerësimi', desc: 'SNK: I bazuar në tregues. IFRS 9: Modeli i humbjeve të pritura të kreditit për aktivet financiare.' },
        ],
      },
      {
        title: 'Pasqyrat financiare të detyrueshme',
        content: 'Kompanitë që zbatojnë IFRS duhet të dorëzojnë një komplet të plotë pasqyrash financiare:',
        items: [
          'Bilanci (Pasqyra e Pozicionit Financiar)',
          'Pasqyra e të ardhurave (Pasqyra e të Ardhurave Gjithëpërfshirëse)',
          'Pasqyra e rrjedhave monetare (metoda direkte ose indirekte)',
          'Pasqyra e ndryshimeve në kapital',
          'Shënime (politikat kontabël, anëtarësimet, kontigjenca)',
          'Raporti i menaxhimit (për kompanitë e listuara)',
        ],
        steps: null,
      },
      {
        title: 'Afatet e dorëzimit dhe procesi',
        content: null,
        items: null,
        steps: [
          { step: 'Mbyllja e vitit (31 dhjetor)', desc: 'Viti fiskal në Maqedoni përputhet me vitin kalendarik.' },
          { step: 'Projekt-pasqyrat financiare', desc: 'Përgatitja e projekt-pasqyrave deri në fund të janarit.' },
          { step: 'Auditimi (nëse është i detyrueshëm)', desc: 'Auditimi duhet të përfundojë para dorëzimit, zakonisht në shkurt.' },
          { step: 'Dorëzimi i llogarive vjetore (QRMK)', desc: 'Afati: 28 shkurt në Regjistrin Qendror të Maqedonisë së Veriut.' },
          { step: 'Bilanci tatimor DB-VP (DAP)', desc: 'Afati: 15 mars në etax.ujp.gov.mk.' },
          { step: 'Publikimi', desc: 'QRMK i publikon pasqyrat në faqen e vet — akses publik.' },
          { step: 'Dorëzimi i raportit të auditimit', desc: 'Nëse auditimi është i detyrueshëm — dorëzohet së bashku me llogaritë vjetore.' },
        ],
      },
      {
        title: 'Kur është i detyrueshëm auditimi?',
        content: 'Auditimi ligjor është i detyrueshëm për kategoritë e mëposhtme:',
        items: [
          'Të gjithë përgatitësit IFRS (të listuara, banka, sigurime)',
          'Kompanitë me të ardhura >€4M',
          'Kompanitë me >50 punonjës',
          'Kompanitë me aktive totale >€2M',
          'Subjektet e interesit publik',
          'OJQ me status të përfitimit publik që marrin >€100.000',
          'Auditori ligjor duhet të jetë i licencuar nga ISOS (Instituti i Kontabilistëve dhe Auditorëve të Certifikuar)',
        ],
        steps: null,
      },
      {
        title: 'Këshilla praktike për tranzicionin IFRS',
        content: 'Kalimi nga SNK në IFRS është një proces kompleks. Ja disa këshilla praktike:',
        items: [
          'Filloni herët — IFRS 1 (Miratimi për Herën e Parë) kërkon bilanc hapje',
          'Identifikoni dallimet midis SNK dhe IFRS për kompaninë tuaj',
          'Matja e vlerës së drejtë mund të kërkojë vlerësime të pavarura',
          'Trajnoni stafin kontabël për standardet e reja',
          'Konsideroni raportimin e ndërmjetëm IFRS para kalimit të detyrueshëm',
          'Softueri duhet të mbështesë planin kontabël IFRS dhe raportimin',
          'Konsultohuni me auditorin gjatë tranzicionit — jo pas tij',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Bilanci dhe pasqyra e të ardhurave: Kodet AOP' },
      { slug: 'godishna-smetka-2025', title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim' },
      { slug: 'danok-na-dobivka', title: 'Tatimi mbi fitimin: Normat, afatet dhe llogaritja' },
    ],
    cta: {
      title: 'Facturino mbështet planin kontabël IFRS dhe raportimin',
      desc: 'Plan kontabël në përputhje me IFRS, raporte financiare automatike dhe regjistrime GL — të gjitha në një platformë.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '← Bloga dön',
    tag: 'Rehber',
    title: 'Makedonya\'da UFRS/IFRS Raporları: Kim Zorunlu ve Nasıl',
    publishDate: '23 Mayıs 2026',
    readTime: '10 dk okuma',
    intro: 'Kuzey Makedonya, büyük kuruluşlar için Uluslararası Finansal Raporlama Standartlarını (UFRS/IFRS) benimsemiştir. Daha küçük şirketler, basitleştirilmiş Makedonya Muhasebe Standartlarını (MMS — KOBİ\'ler için UFRS\'ye dayalı) kullanır. Şirketiniz için hangi çerçevenin geçerli olduğunu anlamak, uyum için kritik önem taşır. Bu rehber, kimlerin UFRS kullanması gerektiğini, raporlamanın nasıl işlediğini ve pratik farkları açıklar.',
    sections: [
      {
        title: 'Makedonya\'da Kim UFRS Kullanmalı?',
        content: 'Ticaret Şirketleri Kanunu ve Muhasebe Kanunu\'na göre, aşağıdaki kuruluşlar tam UFRS/IFRS standartlarını uygulamakla yükümlüdür:',
        items: [
          'Makedonya Menkul Kıymetler Borsası\'nda (MKB) listelenen şirketler',
          'Bankalar ve finansal kuruluşlar (KMMB tarafından düzenlenir)',
          'Sigorta şirketleri',
          'Büyük şirketler — 3 kriterden 2\'sini karşılayanlar: toplam varlıklar >€2M, gelir >€4M, >50 çalışan',
          'Yatırım fonları ve emeklilik fonları',
          'Büyüklük eşiklerinin üzerindeki kamu iktisadi teşebbüsleri',
          'Diğer tüm kuruluşlar MMS (Makedonya Muhasebe Standartları) kullanır',
        ],
        steps: null,
      },
      {
        title: 'MMS vs UFRS — Temel Farklar',
        content: 'İki çerçeve arasındaki farklar önemlidir ve karmaşıklığı, açıklamaları ve kalemlerin tanınmasını etkiler:',
        items: null,
        steps: [
          { step: 'Mali tablolar', desc: 'MMS: Bilanço + Gelir tablosu + Dipnotlar. UFRS: Tam set — Bilanço, Gelir tablosu, Nakit akışı, Özsermaye değişimleri, Dipnotlar.' },
          { step: 'Gerçeğe uygun değer', desc: 'MMS: Sınırlı kullanım. UFRS: Kapsamlı — finansal araçlar, yatırım amaçlı gayrimenkuller.' },
          { step: 'Açıklamalar', desc: 'MMS: Basitleştirilmiş. UFRS: Kapsamlı — bankalar için 100+ sayfa olabilir.' },
          { step: 'Konsolidasyon', desc: 'MMS: Yalnızca >%50 kontrol durumunda. UFRS: VIE ve ortak düzenlemeler dahil karmaşık kurallar.' },
          { step: 'Hasılat tanıma', desc: 'MMS: Daha basit kriterler. UFRS 15: Sözleşme analiziyle 5 adımlı model.' },
          { step: 'Kiralamalar', desc: 'MMS: Faaliyet ve finansal kiralama ayrımı. UFRS 16: Tüm kiralamalar bilançoda.' },
          { step: 'Değer düşüklüğü', desc: 'MMS: Gösterge tabanlı. UFRS 9: Finansal varlıklar için beklenen kredi zararı modeli.' },
        ],
      },
      {
        title: 'Zorunlu mali tablolar',
        content: 'UFRS uygulayan şirketler eksiksiz bir mali tablo seti sunmalıdır:',
        items: [
          'Bilanço (Finansal Durum Tablosu)',
          'Gelir tablosu (Kapsamlı Gelir Tablosu)',
          'Nakit akış tablosu (doğrudan veya dolaylı yöntem)',
          'Özsermaye değişim tablosu',
          'Dipnotlar (muhasebe politikaları, dökümler, koşullu yükümlülükler)',
          'Yönetim raporu (listelenen şirketler için)',
        ],
        steps: null,
      },
      {
        title: 'Başvuru tarihleri ve süreci',
        content: null,
        items: null,
        steps: [
          { step: 'Yıl sonu kapanışı (31 Aralık)', desc: 'Makedonya\'da mali yıl takvim yılıyla örtüşür.' },
          { step: 'Taslak mali tablolar', desc: 'Taslak tabloların Ocak sonuna kadar hazırlanması.' },
          { step: 'Denetim (gerekiyorsa)', desc: 'Denetim, başvurudan önce tamamlanmalıdır, genellikle Şubat ayında.' },
          { step: 'Yıllık hesap başvurusu (MKTS)', desc: 'Son tarih: 28 Şubat, Kuzey Makedonya Merkezi Sicili\'ne.' },
          { step: 'Vergi bilançosu DB-VP başvurusu (KGİ)', desc: 'Son tarih: 15 Mart, etax.ujp.gov.mk adresinde.' },
          { step: 'Yayın', desc: 'MKTS tabloları web sitesinde yayınlar — kamuya açık erişim.' },
          { step: 'Denetim raporu gönderimi', desc: 'Denetim zorunluysa — yıllık hesapla birlikte sunulur.' },
        ],
      },
      {
        title: 'Denetim ne zaman zorunludur?',
        content: 'Yasal denetim aşağıdaki kategoriler için zorunludur:',
        items: [
          'Tüm UFRS hazırlayıcıları (listelenen, bankalar, sigorta)',
          'Geliri >€4M olan şirketler',
          '>50 çalışanı olan şirketler',
          'Toplam varlıkları >€2M olan şirketler',
          'Kamu yararına faaliyet gösteren kuruluşlar',
          '>€100.000 alan kamu yararı statüsüne sahip STK\'lar',
          'Yasal denetçi ISOS (Muhasebeciler ve Yetkili Denetçiler Enstitüsü) tarafından lisanslanmış olmalıdır',
        ],
        steps: null,
      },
      {
        title: 'UFRS geçişi için pratik ipuçları',
        content: 'MMS\'den UFRS\'ye geçiş karmaşık bir süreçtir. İşte bazı pratik ipuçları:',
        items: [
          'Erken başlayın — UFRS 1 (ilk kez benimseme) açılış bilançosu gerektirir',
          'Şirketiniz için MMS ve UFRS arasındaki farkları belirleyin',
          'Gerçeğe uygun değer ölçümü bağımsız değerlemeler gerektirebilir',
          'Muhasebe personelini yeni standartlar konusunda eğitin',
          'Zorunlu geçişten önce ara dönem UFRS raporlamasını düşünün',
          'Yazılımın UFRS hesap planını ve raporlamayı desteklemesi gerekir',
          'Geçiş sırasında denetçinizle istişare edin — sonrasında değil',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Bilanço ve gelir tablosu: AOP kodları' },
      { slug: 'godishna-smetka-2025', title: 'Yıllık hesaplar 2025: Tam dosyalama rehberi' },
      { slug: 'danok-na-dobivka', title: 'Kurumlar vergisi: Oranlar, son tarihler ve hesaplama' },
    ],
    cta: {
      title: 'Facturino UFRS hesap planını ve raporlamayı destekler',
      desc: 'UFRS uyumlu hesap planı, otomatik mali raporlar ve GL kayıtları — hepsi tek platformda.',
      button: 'Ücretsiz başla',
    },
  },
} as const

export default async function IfrsIzvestiMkPage({
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
    slug: 'ifrs-izvesti-mk',
    title: t.title,
    description: t.intro.slice(0, 200),
    datePublished: '2026-05-23',
    tags: ['IFRS', 'MSFI', 'financial reporting', 'accounting standards', 'North Macedonia'],
  })

  const breadcrumbLd = breadcrumbJsonLd([
    { name: homeLabel, href: `/${locale}` },
    { name: blogLabel, href: `/${locale}/blog` },
    { name: t.title, href: `/${locale}/blog/ifrs-izvesti-mk` },
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
