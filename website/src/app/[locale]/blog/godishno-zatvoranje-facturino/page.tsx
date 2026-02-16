import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/godishno-zatvoranje-facturino', {
    title: {
      mk: 'Годишно затворање на книги: 6 чекори со Facturino',
      en: 'Year-End Closing: 6 Steps with Facturino',
      sq: 'Mbyllja e vitit: 6 hapa me Facturino',
      tr: 'Yıl Sonu Kapanışı: Facturino ile 6 Adım',
    },
    description: {
      mk: 'Како Facturino го автоматизира годишното затворање — од преглед до заклучување, со UJP-формат извештаи и можност за поништување.',
      en: 'How Facturino automates year-end closing — from review to lock, with UJP-format reports and undo capability.',
      sq: 'Si Facturino e automatizon mbylljen e vitit — nga rishikimi deri te kyçja, me raporte në format UJP.',
      tr: 'Facturino yıl sonu kapanışını nasıl otomatikleştirir — incelemeden kilitlemeye, UJP formatında raporlar.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Производ',
    title: 'Годишно затворање на книги: 6 чекори со Facturino',
    publishDate: '25 јануари 2026',
    readTime: '6 мин читање',
    intro:
      'Годишното затворање на книги е еден од најстресните периоди за секој сметководител. Рачното пресметување, проверка на салда и генерирање извештаи може да трае денови. Facturino го сведува целиот процес на 6 автоматизирани чекори.',
    problemSection: {
      title: 'Проблемот: Рачно затворање трае денови',
      items: [
        'Рачно проверување на секоја сметка — дали салдата се точни',
        'Рачно генерирање на Образец 36 и 37 — со AOP ознаки',
        'Пресметување данок на добивка — со ризик од грешка',
        'Креирање затворачки книжења — дебитирање приходи, кредитирање расходи',
        'Подготовка на ДБ-ВП за УЈП — посебен формулар',
        'Архивирање и заклучување — без можност за случајна промена',
      ],
      comparison: {
        manual: { label: 'Рачно', time: '3-5 дена', risk: 'Висок ризик од грешки' },
        facturino: { label: 'Со Facturino', time: '30 минути', risk: 'Автоматска валидација' },
      },
    },
    steps: [
      {
        num: 1,
        title: 'Предпроверка (Preflight)',
        desc: 'Facturino автоматски проверува дали сè е подготвено за затворање:',
        checks: [
          'Дали сите трансакции се книжени и потврдени',
          'Дали бруто билансот е балансиран (Дугува = Побарува)',
          'Дали фискалната година не е веќе затворена',
          'Дали постои активна валута и контен план',
        ],
        result:
          'Ако нешто не е во ред, добивате јасна листа на проблеми за поправка.',
      },
      {
        num: 2,
        title: 'Преглед на финансии',
        desc: 'Целосен преглед на финансиската состојба пред затворање:',
        checks: [
          'Биланс на состојба — АКТИВА vs ПАСИВА',
          'Биланс на успех — приходи vs расходи',
          'Бруто биланс — сите конта со салда',
          'Нето добивка/загуба — финален резултат',
        ],
        result:
          'Прегледајте ги бројките и уверете се дека сè е точно пред да продолжите.',
      },
      {
        num: 3,
        title: 'Корекции (опционално)',
        desc: 'Ако забележите грешки или пропусти:',
        checks: [
          'Додадете пропуштени трансакции',
          'Коригирајте погрешни книжења',
          'Додадете амортизација ако е пропуштена',
          'Направете корекции за курсни разлики',
        ],
        result:
          'По корекциите, Facturino автоматски ги ажурира извештаите.',
      },
      {
        num: 4,
        title: 'Затворање на книгите',
        desc: 'Facturino автоматски ги генерира затворачките книжења:',
        checks: [
          'Дебитира сите приходни сметки (класа 6) \u2192 ги нулира',
          'Кредитира сите расходни сметки (класа 5) \u2192 ги нулира',
          'Разликата ја книжи на Задржана добивка (сметка 340)',
          'Книжи данок на добивка 10% ако има добивка',
        ],
        result:
          'Прво ги гледате во Preview режим, па потврдувате со еден клик.',
      },
      {
        num: 5,
        title: 'UJP извештаи',
        desc: 'Генерирање на извештаи во формат за поднесување:',
        checks: [
          'Образец 36 (Биланс на состојба) — PDF со AOP ознаки',
          'Образец 37 (Биланс на успех) — PDF со AOP ознаки',
          'Бруто биланс — PDF со Дугува/Побарува колони',
          'ДБ-ВП (Даночен биланс) — CSV за УЈП',
          'Pantheon XML / Zonel CSV — за ЦРСМ е-поднесување',
        ],
        result:
          'Сите извештаи се базирани на податоците ПРЕД затворање — бидејќи по затворање, приходите и расходите се нулирани.',
      },
      {
        num: 6,
        title: 'Заклучување',
        desc: 'Финален чекор — заклучете ја фискалната година:',
        checks: [
          'Фискалната година се означува како "затворена"',
          'Нови трансакции за таа година се блокирани',
          'Undo е можен во првите 24 часа — за секој случај',
          'По 24 часа, затворањето е перманентно',
        ],
        result:
          'Готово! Годишното затворање е завршено. Можете да продолжите со поднесување до ЦРСМ и УЈП.',
      },
    ],
    undoSection: {
      title: 'Можност за поништување',
      desc: 'Facturino дозволува поништување на затворањето во првите 24 часа. Ова е корисно ако забележите грешка по затворањето:',
      items: [
        'Сите затворачки книжења се бришат',
        'Фискалната година се отклучува',
        'Можете да направите корекции и повторно да затворите',
      ],
    },
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ' },
      { slug: 'bilans-na-sostojba', title: 'Биланс на состојба и биланс на успех: AOP ознаки и структура' },
      { slug: 'zosto-facturino', title: '10 причини зошто македонски бизниси го избираат Facturino' },
    ],
    ctaSection: {
      title: 'Годишна сметка за 30 минути, не за 3 дена',
      desc: 'Facturino го автоматизира целиот процес — од предпроверка до UJP извештаи. Без рачно пресметување, без стрес.',
      cta: 'Започни бесплатно',
      secondary: 'Погледни ги цените',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Product',
    title: 'Year-End Closing: 6 Steps with Facturino',
    publishDate: 'January 25, 2026',
    readTime: '6 min read',
    intro:
      'Year-end closing is one of the most stressful periods for every accountant. Manual calculations, balance verification and report generation can take days. Facturino reduces the entire process to 6 automated steps.',
    problemSection: {
      title: 'The problem: Manual closing takes days',
      items: [
        'Manually checking every account balance',
        'Manually generating Form 36 and 37 with AOP codes',
        'Calculating corporate income tax with risk of errors',
        'Creating closing journal entries',
        'Preparing DB-VP for UJP \u2014 a separate form',
        'Archiving and locking \u2014 preventing accidental changes',
      ],
      comparison: {
        manual: { label: 'Manual', time: '3-5 days', risk: 'High risk of errors' },
        facturino: { label: 'With Facturino', time: '30 minutes', risk: 'Automatic validation' },
      },
    },
    steps: [
      {
        num: 1,
        title: 'Preflight check',
        desc: 'Facturino automatically checks if everything is ready for closing:',
        checks: [
          'All transactions are posted and confirmed',
          'Trial balance is balanced (Debits = Credits)',
          'The fiscal year is not already closed',
          'Active currency and chart of accounts exist',
        ],
        result:
          'If something is wrong, you get a clear list of issues to fix.',
      },
      {
        num: 2,
        title: 'Financial review',
        desc: 'A full overview of the financial position before closing:',
        checks: [
          'Balance sheet \u2014 ASSETS vs LIABILITIES',
          'Income statement \u2014 revenue vs expenses',
          'Trial balance \u2014 all accounts with balances',
          'Net profit/loss \u2014 final result',
        ],
        result:
          'Review the numbers and make sure everything is correct before proceeding.',
      },
      {
        num: 3,
        title: 'Adjustments (optional)',
        desc: 'If you notice errors or omissions:',
        checks: [
          'Add missing transactions',
          'Correct wrong journal entries',
          'Add depreciation if missed',
          'Make exchange rate adjustments',
        ],
        result:
          'After adjustments, Facturino automatically updates the reports.',
      },
      {
        num: 4,
        title: 'Close the books',
        desc: 'Facturino automatically generates the closing entries:',
        checks: [
          'Debits all revenue accounts (class 6) \u2192 zeroes them out',
          'Credits all expense accounts (class 5) \u2192 zeroes them out',
          'Posts the difference to Retained Earnings (account 340)',
          'Posts 10% corporate income tax if there is profit',
        ],
        result:
          'First you see them in Preview mode, then confirm with one click.',
      },
      {
        num: 5,
        title: 'UJP reports',
        desc: 'Generate reports in submission-ready format:',
        checks: [
          'Form 36 (Balance Sheet) \u2014 PDF with AOP codes',
          'Form 37 (Income Statement) \u2014 PDF with AOP codes',
          'Trial balance \u2014 PDF with Debit/Credit columns',
          'DB-VP (Tax Balance) \u2014 CSV for UJP',
          'Pantheon XML / Zonel CSV \u2014 for CRSM e-filing',
        ],
        result:
          'All reports are based on data BEFORE closing \u2014 because after closing, revenue and expenses are zeroed out.',
      },
      {
        num: 6,
        title: 'Lock',
        desc: 'Final step \u2014 lock the fiscal year:',
        checks: [
          'The fiscal year is marked as "closed"',
          'New transactions for that year are blocked',
          'Undo is possible within the first 24 hours \u2014 just in case',
          'After 24 hours, the closing is permanent',
        ],
        result:
          'Done! The year-end closing is complete. You can proceed with filing to CRSM and UJP.',
      },
    ],
    undoSection: {
      title: 'Undo capability',
      desc: 'Facturino allows undoing the closing within the first 24 hours. This is useful if you notice an error after closing:',
      items: [
        'All closing entries are deleted',
        'The fiscal year is unlocked',
        'You can make corrections and close again',
      ],
    },
    relatedTitle: 'Related articles',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Annual Accounts 2025: Complete Filing Guide for CRMS' },
      { slug: 'bilans-na-sostojba', title: 'Balance Sheet & Income Statement: AOP Codes and Structure' },
      { slug: 'zosto-facturino', title: '10 Reasons Macedonian Businesses Choose Facturino' },
    ],
    ctaSection: {
      title: 'Annual accounts in 30 minutes, not 3 days',
      desc: 'Facturino automates the entire process \u2014 from preflight to UJP reports. No manual calculations, no stress.',
      cta: 'Start free',
      secondary: 'See pricing',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Produkt',
    title: 'Mbyllja e vitit: 6 hapa me Facturino',
    publishDate: '25 janar 2026',
    readTime: '6 min lexim',
    intro:
      'Mbyllja e vitit \u00ebsht\u00eb nj\u00eb nga periudhat m\u00eb stresuese p\u00ebr \u00e7do kontabilist. Llogaritjet manuale, verifikimi i bilanceve dhe gjenerimi i raporteve mund t\u00eb zgjas\u00eb dit\u00eb. Facturino e redukton t\u00eb gjith\u00eb procesin n\u00eb 6 hapa t\u00eb automatizuar.',
    problemSection: {
      title: 'Problemi: Mbyllja manuale zgjat dit\u00eb',
      items: [
        'Kontroll manual i \u00e7do llogarie \u2014 a jan\u00eb bilancet t\u00eb sakta',
        'Gjenerim manual i Formularit 36 dhe 37 \u2014 me kodet AOP',
        'Llogaritja e tatimit mbi fitimin \u2014 me rrezik gabimi',
        'Krijimi i regjistrimeve mb\u00ebyll\u00ebse \u2014 debitim i t\u00eb ardhurave, kreditim i shpenzimeve',
        'P\u00ebrgatitja e DB-VP p\u00ebr UJP \u2014 formular i ve\u00e7ant\u00eb',
        'Arkivim dhe ky\u00e7je \u2014 pa mund\u00ebsi p\u00ebr ndryshime aksidentale',
      ],
      comparison: {
        manual: { label: 'Manuale', time: '3-5 dit\u00eb', risk: 'Rrezik i lart\u00eb gabimesh' },
        facturino: { label: 'Me Facturino', time: '30 minuta', risk: 'Validim automatik' },
      },
    },
    steps: [
      {
        num: 1,
        title: 'Parakontroll (Preflight)',
        desc: 'Facturino kontrollon automatikisht n\u00ebse gjith\u00e7ka \u00ebsht\u00eb gati p\u00ebr mbyllje:',
        checks: [
          'T\u00eb gjitha transaksionet jan\u00eb regjistruar dhe konfirmuar',
          'Bilanci bruto \u00ebsht\u00eb i balancuar (Debiti = Krediti)',
          'Viti fiskal nuk \u00ebsht\u00eb tashm\u00eb i mbyllur',
          'Ekziston valut\u00eb aktive dhe plan kontab\u00ebl',
        ],
        result:
          'N\u00ebse di\u00e7ka nuk \u00ebsht\u00eb n\u00eb rregull, merrni nj\u00eb list\u00eb t\u00eb qart\u00eb problemesh p\u00ebr t\u2019u rregulluar.',
      },
      {
        num: 2,
        title: 'Rishikimi financiar',
        desc: 'Pik\u00ebpamje e plot\u00eb e gjendjes financiare para mbylljes:',
        checks: [
          'Bilanci i gjendjes \u2014 AKTIVE vs PASIVE',
          'Bilanci i suksesit \u2014 t\u00eb ardhura vs shpenzime',
          'Bilanci bruto \u2014 t\u00eb gjitha llogarit\u00eb me bilance',
          'Fitimi/humbja neto \u2014 rezultati p\u00ebrfundimtar',
        ],
        result:
          'Rishikoni shifrat dhe sigurohuni q\u00eb gjith\u00e7ka \u00ebsht\u00eb sakt\u00eb para se t\u00eb vazhdoni.',
      },
      {
        num: 3,
        title: 'Korrigjime (opsionale)',
        desc: 'N\u00ebse v\u00ebreni gabime ose mang\u00ebsi:',
        checks: [
          'Shtoni transaksione t\u00eb humbura',
          'Korrigjoni regjistrime t\u00eb gabuara',
          'Shtoni amortizimin n\u00ebse \u00ebsht\u00eb harruar',
          'B\u00ebni korrigjime p\u00ebr diferencat e kursit t\u00eb k\u00ebmbimit',
        ],
        result:
          'Pas korrigjimeve, Facturino i p\u00ebrdit\u00ebson raportet automatikisht.',
      },
      {
        num: 4,
        title: 'Mbyllja e librave',
        desc: 'Facturino gjeneron automatikisht regjistrimet mb\u00ebyll\u00ebse:',
        checks: [
          'Debiton t\u00eb gjitha llogarit\u00eb e t\u00eb ardhurave (klasa 6) \u2192 i zerjon',
          'Krediton t\u00eb gjitha llogarit\u00eb e shpenzimeve (klasa 5) \u2192 i zerjon',
          'Diferenc\u00ebn e regjistron n\u00eb Fitimin e Mbajtur (llogaria 340)',
          'Regjistron tatimin mbi fitimin 10% n\u00ebse ka fitim',
        ],
        result:
          'S\u00eb pari i shihni n\u00eb modalitetin Preview, pastaj konfirmoni me nj\u00eb klik.',
      },
      {
        num: 5,
        title: 'Raportet UJP',
        desc: 'Gjenerimi i raporteve n\u00eb format p\u00ebr dor\u00ebzim:',
        checks: [
          'Formulari 36 (Bilanci i gjendjes) \u2014 PDF me kodet AOP',
          'Formulari 37 (Bilanci i suksesit) \u2014 PDF me kodet AOP',
          'Bilanci bruto \u2014 PDF me kolonat Debit/Kredit',
          'DB-VP (Bilanci tatimor) \u2014 CSV p\u00ebr UJP',
          'Pantheon XML / Zonel CSV \u2014 p\u00ebr dor\u00ebzim elektronik n\u00eb CRSM',
        ],
        result:
          'T\u00eb gjitha raportet bazohen n\u00eb t\u00eb dh\u00ebnat PARA mbylljes \u2014 sepse pas mbylljes, t\u00eb ardhurat dhe shpenzimet jan\u00eb t\u00eb zerjuara.',
      },
      {
        num: 6,
        title: 'Ky\u00e7ja',
        desc: 'Hapi p\u00ebrfundimtar \u2014 ky\u00e7ni vitin fiskal:',
        checks: [
          'Viti fiskal sh\u00ebnohet si "i mbyllur"',
          'Transaksione t\u00eb reja p\u00ebr at\u00eb vit jan\u00eb t\u00eb bllokuara',
          'Undo \u00ebsht\u00eb i mundsh\u00ebm brenda 24 or\u00ebve t\u00eb para \u2014 p\u00ebr \u00e7do rast',
          'Pas 24 or\u00ebve, mbyllja \u00ebsht\u00eb permanente',
        ],
        result:
          'Gati! Mbyllja e vitit \u00ebsht\u00eb e p\u00ebrfunduar. Mund t\u00eb vazhdoni me dor\u00ebzimin n\u00eb CRSM dhe UJP.',
      },
    ],
    undoSection: {
      title: 'Mund\u00ebsia p\u00ebr anulim',
      desc: 'Facturino lejon anulimin e mbylljes brenda 24 or\u00ebve t\u00eb para. Kjo \u00ebsht\u00eb e dobishme n\u00ebse v\u00ebreni nj\u00eb gabim pas mbylljes:',
      items: [
        'T\u00eb gjitha regjistrimet mb\u00ebyll\u00ebse fshihen',
        'Viti fiskal zhbllokohet',
        'Mund t\u00eb b\u00ebni korrigjime dhe t\u00eb mbyllni p\u00ebrs\u00ebri',
      ],
    },
    relatedTitle: 'Artikuj t\u00eb ngjash\u00ebm',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Llogarit\u00eb vjetore 2025: Udh\u00ebzues i plot\u00eb p\u00ebr dor\u00ebzim n\u00eb QRMK' },
      { slug: 'bilans-na-sostojba', title: 'Bilanci dhe pasqyra e t\u00eb ardhurave: Kodet AOP dhe struktura' },
      { slug: 'zosto-facturino', title: '10 arsye pse bizneset maqedonase zgjedhin Facturino' },
    ],
    ctaSection: {
      title: 'Llogarit\u00eb vjetore n\u00eb 30 minuta, jo n\u00eb 3 dit\u00eb',
      desc: 'Facturino e automatizon t\u00eb gjith\u00eb procesin \u2014 nga parakontrolli deri te raportet UJP. Pa llogaritje manuale, pa stres.',
      cta: 'Fillo falas',
      secondary: 'Shiko \u00e7mimet',
    },
  },
  tr: {
    backLink: '\u2190 Bloga d\u00f6n',
    tag: '\u00dcr\u00fcn',
    title: 'Y\u0131l Sonu Kapan\u0131\u015f\u0131: Facturino ile 6 Ad\u0131m',
    publishDate: '25 Ocak 2026',
    readTime: '6 dk okuma',
    intro:
      'Y\u0131l sonu kapan\u0131\u015f\u0131, her muhasebeci i\u00e7in en stresli d\u00f6nemlerden biridir. Manuel hesaplamalar, bakiye do\u011frulama ve rapor olu\u015fturma g\u00fcnler s\u00fcrebilir. Facturino t\u00fcm s\u00fcreci 6 otomatik ad\u0131ma indirir.',
    problemSection: {
      title: 'Sorun: Manuel kapan\u0131\u015f g\u00fcnler s\u00fcrer',
      items: [
        'Her hesap bakiyesinin manuel kontrol\u00fc',
        'Form 36 ve 37\u2019nin AOP kodlar\u0131yla manuel olu\u015fturulmas\u0131',
        'Kurumlar vergisi hesaplamas\u0131 \u2014 hata riski ile',
        'Kapan\u0131\u015f yevmiye kay\u0131tlar\u0131n\u0131n olu\u015fturulmas\u0131',
        'UJP i\u00e7in DB-VP haz\u0131rlanmas\u0131 \u2014 ayr\u0131 bir form',
        'Ar\u015fivleme ve kilitleme \u2014 kazara de\u011fi\u015fikliklerin \u00f6nlenmesi',
      ],
      comparison: {
        manual: { label: 'Manuel', time: '3-5 g\u00fcn', risk: 'Y\u00fcksek hata riski' },
        facturino: { label: 'Facturino ile', time: '30 dakika', risk: 'Otomatik do\u011frulama' },
      },
    },
    steps: [
      {
        num: 1,
        title: '\u00d6n kontrol (Preflight)',
        desc: 'Facturino kapan\u0131\u015f i\u00e7in her \u015feyin haz\u0131r olup olmad\u0131\u011f\u0131n\u0131 otomatik kontrol eder:',
        checks: [
          'T\u00fcm i\u015flemler kaydedilmi\u015f ve onayland\u0131',
          'Mizan dengelidir (Bor\u00e7 = Alacak)',
          'Mali y\u0131l hen\u00fcz kapat\u0131lmam\u0131\u015f',
          'Aktif para birimi ve hesap plan\u0131 mevcut',
        ],
        result:
          'Bir \u015fey yanl\u0131\u015fsa, d\u00fczeltilmesi gereken sorunlar\u0131n net bir listesini al\u0131rs\u0131n\u0131z.',
      },
      {
        num: 2,
        title: 'Mali inceleme',
        desc: 'Kapan\u0131\u015f \u00f6ncesi mali durumun tam g\u00f6r\u00fcn\u00fcm\u00fc:',
        checks: [
          'Bilan\u00e7o \u2014 VARLIKLAR vs Y\u00dcK\u00dcML\u00dcL\u00dcKLER',
          'Gelir tablosu \u2014 gelirler vs giderler',
          'Mizan \u2014 bakiyeli t\u00fcm hesaplar',
          'Net k\u00e2r/zarar \u2014 nihai sonu\u00e7',
        ],
        result:
          'Rakamlar\u0131 inceleyin ve devam etmeden \u00f6nce her \u015feyin do\u011fru oldu\u011fundan emin olun.',
      },
      {
        num: 3,
        title: 'D\u00fczeltmeler (iste\u011fe ba\u011fl\u0131)',
        desc: 'Hatalar veya eksiklikler fark ederseniz:',
        checks: [
          'Eksik i\u015flemleri ekleyin',
          'Yanl\u0131\u015f yevmiye kay\u0131tlar\u0131n\u0131 d\u00fczeltin',
          'Atlanm\u0131\u015fsa amortizman ekleyin',
          'Kur fark\u0131 d\u00fczeltmeleri yap\u0131n',
        ],
        result:
          'D\u00fczeltmelerden sonra Facturino raporlar\u0131 otomatik g\u00fcnceller.',
      },
      {
        num: 4,
        title: 'Defterlerin kapat\u0131lmas\u0131',
        desc: 'Facturino kapan\u0131\u015f kay\u0131tlar\u0131n\u0131 otomatik olu\u015fturur:',
        checks: [
          'T\u00fcm gelir hesaplar\u0131n\u0131 (s\u0131n\u0131f 6) bor\u00e7land\u0131r\u0131r \u2192 s\u0131f\u0131rlar',
          'T\u00fcm gider hesaplar\u0131n\u0131 (s\u0131n\u0131f 5) alacakland\u0131r\u0131r \u2192 s\u0131f\u0131rlar',
          'Fark\u0131 Ge\u00e7mi\u015f Y\u0131llar K\u00e2r\u0131\u2019na (hesap 340) kaydeder',
          'K\u00e2r varsa %10 kurumlar vergisi kaydeder',
        ],
        result:
          '\u00d6nce \u00d6nizleme modunda g\u00f6r\u00fcrs\u00fcn\u00fcz, sonra tek t\u0131kla onaylarsiniz.',
      },
      {
        num: 5,
        title: 'UJP raporlar\u0131',
        desc: 'Sunuma haz\u0131r formatta rapor olu\u015fturma:',
        checks: [
          'Form 36 (Bilan\u00e7o) \u2014 AOP kodlu PDF',
          'Form 37 (Gelir Tablosu) \u2014 AOP kodlu PDF',
          'Mizan \u2014 Bor\u00e7/Alacak s\u00fctunlu PDF',
          'DB-VP (Vergi Bilan\u00e7osu) \u2014 UJP i\u00e7in CSV',
          'Pantheon XML / Zonel CSV \u2014 CRSM e-dosyalama i\u00e7in',
        ],
        result:
          'T\u00fcm raporlar kapan\u0131\u015f \u00d6NCES\u0130 verilere dayan\u0131r \u2014 \u00e7\u00fcnk\u00fc kapan\u0131\u015ftan sonra gelirler ve giderler s\u0131f\u0131rlanm\u0131\u015ft\u0131r.',
      },
      {
        num: 6,
        title: 'Kilitleme',
        desc: 'Son ad\u0131m \u2014 mali y\u0131l\u0131 kilitleyin:',
        checks: [
          'Mali y\u0131l "kapat\u0131ld\u0131" olarak i\u015faretlenir',
          'O y\u0131l i\u00e7in yeni i\u015flemler engellenir',
          'Geri alma ilk 24 saat i\u00e7inde m\u00fcmk\u00fcnd\u00fcr \u2014 her ihtimale kar\u015f\u0131',
          '24 saat sonra kapan\u0131\u015f kal\u0131c\u0131d\u0131r',
        ],
        result:
          'Tamam! Y\u0131l sonu kapan\u0131\u015f\u0131 tamamland\u0131. CRSM ve UJP\u2019ye dosyalama ile devam edebilirsiniz.',
      },
    ],
    undoSection: {
      title: 'Geri alma \u00f6zelli\u011fi',
      desc: 'Facturino ilk 24 saat i\u00e7inde kapan\u0131\u015f\u0131n geri al\u0131nmas\u0131na izin verir. Kapan\u0131\u015ftan sonra bir hata fark ederseniz kullan\u0131\u015fl\u0131d\u0131r:',
      items: [
        'T\u00fcm kapan\u0131\u015f kay\u0131tlar\u0131 silinir',
        'Mali y\u0131l kilidi a\u00e7\u0131l\u0131r',
        'D\u00fczeltmeler yapabilir ve tekrar kapatabilirsiniz',
      ],
    },
    relatedTitle: '\u0130lgili makaleler',
    related: [
      { slug: 'godishna-smetka-2025', title: 'Y\u0131ll\u0131k hesaplar 2025: CRMS dosyalama rehberi' },
      { slug: 'bilans-na-sostojba', title: 'Bilan\u00e7o ve gelir tablosu: AOP kodlar\u0131 ve yap\u0131' },
      { slug: 'zosto-facturino', title: "Makedon i\u015fletmelerin Facturino'yu se\u00e7mesinin 10 nedeni" },
    ],
    ctaSection: {
      title: 'Y\u0131ll\u0131k hesaplar 30 dakikada, 3 g\u00fcnde de\u011fil',
      desc: 'Facturino t\u00fcm s\u00fcreci otomatikle\u015ftirir \u2014 \u00f6n kontrolden UJP raporlar\u0131na. Manuel hesaplama yok, stres yok.',
      cta: '\u00dccretsiz ba\u015fla',
      secondary: 'Fiyatlar\u0131 g\u00f6r',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Step icons (inline SVGs to avoid dependencies)                    */
/* ------------------------------------------------------------------ */
function IconChecklist() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  )
}

function IconReview() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
      <path strokeLinecap="round" strokeLinejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
  )
}

function IconAdjust() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
    </svg>
  )
}

function IconClose() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
    </svg>
  )
}

function IconReports() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>
  )
}

function IconLock() {
  return (
    <svg className="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
    </svg>
  )
}

const stepIcons = [IconChecklist, IconReview, IconAdjust, IconClose, IconReports, IconLock]

/* Alternating gradient backgrounds for each step */
const stepGradients = [
  'from-indigo-600 to-indigo-500',
  'from-cyan-600 to-cyan-500',
  'from-indigo-600 to-cyan-500',
  'from-indigo-600 to-indigo-500',
  'from-cyan-600 to-cyan-500',
  'from-indigo-600 to-cyan-500',
]

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function YearEndClosingPage({
  params,
}: {
  params: Promise<{ locale: string }>
}) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]

  return (
    <main id="main-content">
      {/* ============================================================ */}
      {/*  BACK LINK                                                   */}
      {/* ============================================================ */}
      <section className="pt-24 md:pt-28 pb-0">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <Link
            href={`/${locale}/blog`}
            className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
          >
            {t.backLink}
          </Link>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  HEADER                                                      */}
      {/* ============================================================ */}
      <section className="pt-6 pb-8 md:pb-12">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <div className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600 mb-4">
            {t.tag}
          </div>
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-4">
            {t.title}
          </h1>
          <div className="flex items-center gap-4 text-sm text-gray-500">
            <span>{t.publishDate}</span>
            <span className="w-1 h-1 rounded-full bg-gray-300" />
            <span>{t.readTime}</span>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  INTRO                                                       */}
      {/* ============================================================ */}
      <section className="pb-12 md:pb-16">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.intro}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  PROBLEM SECTION                                             */}
      {/* ============================================================ */}
      <section className="pb-16 md:pb-20">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <div className="rounded-2xl border border-red-100 bg-red-50/50 p-6 md:p-8">
            <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-6">
              {t.problemSection.title}
            </h2>
            <ul className="space-y-3 mb-8">
              {t.problemSection.items.map((item, i) => (
                <li key={i} className="flex items-start gap-3">
                  <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-red-100 flex items-center justify-center">
                    <svg className="w-3 h-3 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </span>
                  <span className="text-gray-700 leading-relaxed">{item}</span>
                </li>
              ))}
            </ul>

            {/* Comparison table */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {/* Manual */}
              <div className="rounded-xl border border-red-200 bg-white p-5">
                <div className="text-sm font-semibold text-red-600 uppercase tracking-wide mb-3">
                  {t.problemSection.comparison.manual.label}
                </div>
                <div className="text-2xl font-bold text-gray-900 mb-1">
                  {t.problemSection.comparison.manual.time}
                </div>
                <div className="text-sm text-gray-500">
                  {t.problemSection.comparison.manual.risk}
                </div>
              </div>
              {/* Facturino */}
              <div className="rounded-xl border border-green-200 bg-white p-5">
                <div className="text-sm font-semibold text-green-600 uppercase tracking-wide mb-3">
                  {t.problemSection.comparison.facturino.label}
                </div>
                <div className="text-2xl font-bold text-gray-900 mb-1">
                  {t.problemSection.comparison.facturino.time}
                </div>
                <div className="text-sm text-gray-500">
                  {t.problemSection.comparison.facturino.risk}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  6 STEPS                                                     */}
      {/* ============================================================ */}
      <section className="pb-16 md:pb-24">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <div className="space-y-10 md:space-y-14">
            {t.steps.map((step, i) => {
              const Icon = stepIcons[i]
              return (
                <div key={i} className="relative">
                  {/* Connector line between steps */}
                  {i !== 0 && (
                    <div className="absolute -top-5 md:-top-7 left-8 w-px h-5 md:h-7 bg-gradient-to-b from-indigo-200 to-transparent" />
                  )}

                  <div className="rounded-2xl border border-gray-100 bg-white shadow-sm hover:shadow-md transition-shadow p-6 md:p-8">
                    {/* Step header */}
                    <div className="flex items-start gap-4 mb-5">
                      {/* Step number circle */}
                      <div
                        className={`flex-shrink-0 w-16 h-16 rounded-2xl bg-gradient-to-br ${stepGradients[i]} shadow-lg flex items-center justify-center`}
                      >
                        <Icon />
                      </div>
                      <div className="flex-1 pt-1">
                        <div className="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-600 mb-2">
                          <span className="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center font-bold">
                            {step.num}
                          </span>
                          Step {step.num}
                        </div>
                        <h2 className="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">
                          {step.title}
                        </h2>
                      </div>
                    </div>

                    {/* Description */}
                    <p className="text-gray-600 mb-5 leading-relaxed">
                      {step.desc}
                    </p>

                    {/* Checklist */}
                    <ul className="space-y-3 mb-5">
                      {step.checks.map((check, j) => (
                        <li key={j} className="flex items-start gap-3">
                          <span className="mt-1 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                            <svg className="w-3 h-3 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                              <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                          </span>
                          <span className="text-gray-700 leading-relaxed">{check}</span>
                        </li>
                      ))}
                    </ul>

                    {/* Result box */}
                    <div className="rounded-xl bg-gradient-to-r from-green-50 to-blue-50 border border-green-100/60 p-4">
                      <p className="text-sm text-gray-700 leading-relaxed">
                        <span className="font-semibold text-green-700 mr-1">&rarr;</span>
                        {step.result}
                      </p>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  UNDO SECTION                                                */}
      {/* ============================================================ */}
      <section className="pb-16 md:pb-20">
        <div className="container px-4 sm:px-6 max-w-4xl mx-auto">
          <div className="rounded-2xl border border-amber-200 bg-amber-50/60 p-6 md:p-8">
            <div className="flex items-start gap-3 mb-4">
              <span className="flex-shrink-0 mt-0.5">
                <svg className="w-6 h-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                  <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
              </span>
              <h2 className="text-xl sm:text-2xl font-bold text-gray-900">
                {t.undoSection.title}
              </h2>
            </div>
            <p className="text-gray-600 mb-5 leading-relaxed">
              {t.undoSection.desc}
            </p>
            <ul className="space-y-3">
              {t.undoSection.items.map((item, i) => (
                <li key={i} className="flex items-start gap-3">
                  <span className="mt-1 flex-shrink-0 w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center">
                    <svg className="w-3 h-3 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={3}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                  </span>
                  <span className="text-gray-700 leading-relaxed">{item}</span>
                </li>
              ))}
            </ul>
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

      {/* ============================================================ */}
      {/*  CTA SECTION                                                 */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.ctaSection.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.ctaSection.desc}
          </p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a
              href="https://app.facturino.mk/signup"
              className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
            >
              {t.ctaSection.cta}
              <svg className="ml-2 w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
            <Link
              href={`/${locale}/pricing`}
              className="text-white/90 hover:text-white underline underline-offset-4 font-medium transition-colors"
            >
              {t.ctaSection.secondary}
            </Link>
          </div>
        </div>
      </section>
    </main>
  )
}
// CLAUDE-CHECKPOINT
