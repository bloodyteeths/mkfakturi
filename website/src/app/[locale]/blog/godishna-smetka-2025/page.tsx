import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/godishna-smetka-2025', {
    title: {
      mk: 'Годишна сметка 2025: Целосен водич — Facturino',
      en: 'Annual Accounts 2025: Complete Filing Guide — Facturino',
      sq: 'Llogaritë vjetore 2025: Udhëzues i plotë — Facturino',
      tr: 'Yıllık Hesaplar 2025: Dosyalama Rehberi — Facturino',
    },
    description: {
      mk: 'Целосен водич за поднесување годишна сметка до ЦРСМ — рокови, потребни документи, Образец 36 и 37, ДБ-ВП и чекор-по-чекор процес.',
      en: 'Complete guide to filing annual accounts with CRMS — deadlines, required documents, Form 36 & 37, DB-VP and step-by-step process.',
      sq: 'Udhëzues i plotë për dorëzimin e llogarive vjetore në QRMK — afatet, dokumentet e nevojshme dhe procesi hap pas hapi.',
      tr: 'CRMS ile yıllık hesap dosyalama rehberi — son tarihler, gerekli belgeler ve adım adım süreç.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Водич',
    title: 'Годишна сметка 2025: Целосен водич за поднесување до ЦРСМ',
    publishDate: '15 јануари 2026',
    readTime: '8 мин читање',
    intro:
      'Секоја компанија во Македонија е должна да поднесе годишна сметка до Централниот регистар (ЦРСМ). Во овој водич објаснуваме кои документи се потребни, кои се роковите и како да го завршите процесот без стрес.',
    sections: [
      {
        title: 'Што е годишна сметка?',
        content:
          'Годишната сметка е збир на финансиски извештаи кои ја прикажуваат финансиската состојба и резултатите на компанијата за одредена деловна година. Сите правни субјекти регистрирани во Република Северна Македонија се обврзани да ја поднесат до ЦРСМ (Централен регистар на Република Северна Македонија).',
        items: null,
        steps: null,
      },
      {
        title: 'Кои документи се потребни?',
        content: null,
        items: [
          'Биланс на состојба (Образец 36) — прикажува средства (АКТИВА) и обврски + капитал (ПАСИВА)',
          'Биланс на успех (Образец 37) — приходи и расходи од работењето за годината',
          'Бруто биланс (пробен биланс) — листа на сите сметки со дугува/побарува салда',
          'Даночен биланс (ДБ-ВП) — пресметка на данок на добивка (10% стапка)',
          'Белешки кон финансиските извештаи — дополнителни објаснувања',
          'Извештај за готовински текови (СПД) — за средни и големи друштва',
          'Извештај за промени во капиталот (ДЕ) — за средни и големи друштва',
        ],
        steps: null,
      },
      {
        title: 'Рокови за поднесување',
        content: null,
        items: [
          'Хартиена форма до ЦРСМ: 28 февруари (следна година)',
          'Електронско поднесување до ЦРСМ: 15 март (следна година)',
          'Даночен биланс (ДБ-ВП) до УЈП: 15 март (следна година)',
          'Аконтативен данок на добивка: месечно до 15-ти (1/12 од годишниот данок)',
        ],
        steps: null,
      },
      {
        title: 'Чекор-по-чекор: Како да поднесете',
        content: null,
        items: null,
        steps: [
          {
            step: '1. Затворете ги книгите',
            desc: 'Направете книжења за крај на година — затворете ги приходните и расходните сметки и пренесете ги салдата.',
          },
          {
            step: '2. Генерирајте извештаи',
            desc: 'Подгответе Образец 36 (биланс на состојба), Образец 37 (биланс на успех), бруто биланс и ДБ-ВП.',
          },
          {
            step: '3. Проверете ги AOP ознаките',
            desc: 'Секоја позиција во извештаите има AOP код (аналитичка ознака на позиција). Уверете се дека сумите се точни и билансот е балансиран.',
          },
          {
            step: '4. Поднесете електронски до ЦРСМ',
            desc: 'Отворете го порталот e-submit.crm.com.mk, прикачете ги извештаите во XML/CSV формат и потпишете со дигитален сертификат.',
          },
          {
            step: '5. Поднесете ДБ-ВП до УЈП',
            desc: 'На порталот etax.ujp.gov.mk пополнете го даночниот биланс. Основицата е добивката пред оданочување, стапката е 10%.',
          },
          {
            step: '6. Архивирајте',
            desc: 'Зачувајте копии од сите поднесоци и потврди. Годишната сметка мора да се чува минимум 10 години.',
          },
        ],
      },
      {
        title: 'Чести грешки',
        content: null,
        items: [
          'Пропуштање на рокот — казните за задоцнето поднесување се од 500 до 3.000 EUR',
          'Небалансиран биланс — АКТИВА мора да биде еднаква на ПАСИВА',
          'Погрешни AOP ознаки — секоја ставка мора да е на точната позиција',
          'Заборавен ДБ-ВП — даночниот биланс е посебен документ кој се поднесува до УЈП, не до ЦРСМ',
          'Непотврдено електронско поднесување — проверете дали сте добиле потврда од системот',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Биланс на состојба и биланс на успех: AOP ознаки и структура' },
      { slug: 'rokovi-ujp-2026', title: 'Даночен календар 2026: Сите рокови за УЈП' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
    ],
    cta: {
      title: 'Facturino ги генерира сите извештаи автоматски',
      desc: 'Биланс на состојба, биланс на успех, бруто биланс и ДБ-ВП — во UJP формат, спремни за поднесување. Без рачно пресметување, без стрес.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Guide',
    title: 'Annual Accounts 2025: Complete Filing Guide for CRMS',
    publishDate: 'January 15, 2026',
    readTime: '8 min read',
    intro:
      'Every company in Macedonia is required to file annual accounts with the Central Registry (CRMS). This guide covers all required documents, deadlines, and the step-by-step filing process.',
    sections: [
      {
        title: 'What are annual accounts?',
        content:
          "Annual accounts are a set of financial statements showing a company's financial position and results for a specific fiscal year. All legal entities registered in the Republic of North Macedonia must file them with the Central Registry (CRMS).",
        items: null,
        steps: null,
      },
      {
        title: 'Required documents',
        content: null,
        items: [
          'Balance Sheet (Form 36) — shows assets (AKTIVA) and liabilities + equity (PASIVA)',
          'Income Statement (Form 37) — revenues and expenses for the year',
          'Trial Balance — list of all accounts with debit/credit balances',
          'Tax Return (DB-VP) — corporate income tax calculation (10% rate)',
          'Notes to financial statements — additional explanations',
          'Cash Flow Statement (SPD) — for medium and large companies',
          'Statement of Changes in Equity (DE) — for medium and large companies',
        ],
        steps: null,
      },
      {
        title: 'Filing deadlines',
        content: null,
        items: [
          'Paper submission to CRMS: February 28 (following year)',
          'Electronic submission to CRMS: March 15 (following year)',
          'Tax return (DB-VP) to UJP: March 15 (following year)',
          'Advance tax payments: monthly by the 15th (1/12 of annual tax)',
        ],
        steps: null,
      },
      {
        title: 'Step by step: How to file',
        content: null,
        items: null,
        steps: [
          {
            step: '1. Close the books',
            desc: 'Make year-end journal entries — close revenue and expense accounts and carry forward balances.',
          },
          {
            step: '2. Generate reports',
            desc: 'Prepare Form 36 (Balance Sheet), Form 37 (Income Statement), Trial Balance and DB-VP.',
          },
          {
            step: '3. Verify AOP codes',
            desc: 'Each position has an AOP code (analytical position identifier). Ensure amounts are correct and the balance sheet balances.',
          },
          {
            step: '4. File electronically with CRMS',
            desc: 'Open the portal e-submit.crm.com.mk, upload reports in XML/CSV format, and sign with a digital certificate.',
          },
          {
            step: '5. File DB-VP with UJP',
            desc: 'On the portal etax.ujp.gov.mk, complete the tax return. The base is profit before tax, the rate is 10%.',
          },
          {
            step: '6. Archive',
            desc: 'Keep copies of all submissions and confirmations. Annual accounts must be kept for a minimum of 10 years.',
          },
        ],
      },
      {
        title: 'Common mistakes',
        content: null,
        items: [
          'Missing the deadline — late filing penalties range from 500 to 3,000 EUR',
          'Unbalanced balance sheet — Assets must equal Liabilities + Equity',
          'Wrong AOP codes — each item must be in the correct position',
          'Forgotten DB-VP — the tax return is a separate document filed with UJP, not CRMS',
          'Unconfirmed electronic submission — verify you received confirmation from the system',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Balance Sheet & Income Statement: AOP Codes and Structure' },
      { slug: 'rokovi-ujp-2026', title: 'Tax Calendar 2026: All UJP Deadlines' },
      { slug: 'ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
    ],
    cta: {
      title: 'Facturino generates all reports automatically',
      desc: 'Balance sheet, income statement, trial balance and DB-VP — in UJP format, ready to file. No manual calculations, no stress.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Udhëzues',
    title: 'Llogaritë vjetore 2025: Udhëzues i plotë për dorëzim në QRMK',
    publishDate: '15 janar 2026',
    readTime: '8 min lexim',
    intro:
      'Çdo kompani në Maqedoni duhet të dorëzojë llogaritë vjetore në Regjistrin Qendror (QRMK). Ky udhëzues mbulon dokumentet e nevojshme, afatet dhe procesin hap pas hapi.',
    sections: [
      {
        title: 'Çfarë janë llogaritë vjetore?',
        content:
          "Llogaritë vjetore janë një grup pasqyrash financiare që tregojnë gjendjen financiare dhe rezultatet e kompanisë. Të gjitha subjektet juridike në RMV duhet t'i dorëzojnë në Regjistrin Qendror.",
        items: null,
        steps: null,
      },
      {
        title: 'Dokumentet e nevojshme',
        content: null,
        items: [
          'Bilanci (Formulari 36) — tregon aktivet dhe detyrimet + kapitalin',
          'Pasqyra e të ardhurave (Formulari 37) — të ardhurat dhe shpenzimet',
          'Bilanci provues — lista e llogarive me saldet debi/kredi',
          'Deklarata tatimore (DB-VP) — tatimi mbi fitimin (10%)',
          'Shënime mbi pasqyrat financiare',
          'Pasqyra e rrjedhës së parasë (SPD) — për kompani mesatare dhe të mëdha',
          'Pasqyra e ndryshimeve në kapital (DE) — për kompani mesatare dhe të mëdha',
        ],
        steps: null,
      },
      {
        title: 'Afatet e dorëzimit',
        content: null,
        items: [
          'Dorëzim në letër: 28 shkurt',
          'Dorëzim elektronik: 15 mars',
          'Deklarata tatimore (DB-VP) në UJP: 15 mars',
          'Pagesa paraprake: mujore deri më 15',
        ],
        steps: null,
      },
      {
        title: 'Hap pas hapi: Si të dorëzoni',
        content: null,
        items: null,
        steps: [
          {
            step: '1. Mbyllni librat',
            desc: 'Bëni regjistrime të fundvitit — mbyllni llogaritë e të ardhurave dhe shpenzimeve.',
          },
          {
            step: '2. Gjeneroni raportet',
            desc: 'Përgatitni Formularin 36, 37, bilancin provues dhe DB-VP.',
          },
          {
            step: '3. Verifikoni kodet AOP',
            desc: 'Çdo pozicion ka kod AOP. Sigurohuni që shumat janë korrekte.',
          },
          {
            step: '4. Dorëzoni elektronikisht',
            desc: 'Hapni portalin e-submit.crm.com.mk dhe ngarkoni raportet.',
          },
          {
            step: '5. Dorëzoni DB-VP në UJP',
            desc: 'Në portalin etax.ujp.gov.mk plotësoni deklaratën tatimore.',
          },
          {
            step: '6. Arkivoni',
            desc: 'Ruani kopje të dorëzimeve. Llogaritë vjetore duhen ruajtur minimum 10 vjet.',
          },
        ],
      },
      {
        title: 'Gabime të zakonshme',
        content: null,
        items: [
          'Humbja e afatit — gjobat janë 500 deri 3,000 EUR',
          'Bilanci i pabalancuar — Aktivet duhet të jenë të barabarta me Detyrimet + Kapitalin',
          'Kode AOP të gabuara',
          'DB-VP e harruar — dorëzohet veçmas në UJP',
          'Dorëzim i pakonfirmuar — verifikoni konfirmimin',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Bilanci dhe pasqyra e të ardhurave: Kodet AOP dhe struktura' },
      { slug: 'rokovi-ujp-2026', title: 'Kalendari tatimor 2026: Të gjitha afatet për DAP' },
      { slug: 'ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
    ],
    cta: {
      title: 'Facturino i gjeneron të gjitha raportet automatikisht',
      desc: 'Bilanci, pasqyra e të ardhurave, bilanci provues dhe DB-VP — në format UJP. Pa llogaritje manuale.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Rehber',
    title: 'Yıllık Hesaplar 2025: CRMS Dosyalama Rehberi',
    publishDate: '15 Ocak 2026',
    readTime: '8 dk okuma',
    intro:
      "Makedonya'daki her şirket yıllık hesaplarını Merkezi Sicile (CRMS) dosyalamak zorundadır. Bu rehber gerekli belgeleri, son tarihleri ve süreci kapsar.",
    sections: [
      {
        title: 'Yıllık hesaplar nedir?',
        content:
          "Yıllık hesaplar, şirketin mali durumunu ve sonuçlarını gösteren mali tablolar setidir. Kuzey Makedonya'da kayıtlı tüm tüzel kişiler bunları CRMS'ye sunmalıdır.",
        items: null,
        steps: null,
      },
      {
        title: 'Gerekli belgeler',
        content: null,
        items: [
          'Bilanço (Form 36) — varlıkları ve yükümlülükleri gösterir',
          'Gelir tablosu (Form 37) — gelirler ve giderler',
          'Mizan — tüm hesapların borç/alacak bakiyeleri',
          'Vergi beyannamesi (DB-VP) — kurumlar vergisi (%10)',
          'Mali tablo dipnotları',
          'Nakit akış tablosu (SPD) — orta ve büyük şirketler için',
          'Özkaynak değişim tablosu (DE) — orta ve büyük şirketler için',
        ],
        steps: null,
      },
      {
        title: 'Dosyalama son tarihleri',
        content: null,
        items: [
          'Kağıt dosyalama: 28 Şubat',
          'Elektronik dosyalama: 15 Mart',
          'Vergi beyannamesi (DB-VP): 15 Mart',
          'Geçici vergi ödemeleri: ayda bir 15\'ine kadar',
        ],
        steps: null,
      },
      {
        title: 'Adım adım: Nasıl dosyalanır',
        content: null,
        items: null,
        steps: [
          {
            step: '1. Defterleri kapatın',
            desc: 'Yıl sonu yevmiye kayıtlarını yapın — gelir ve gider hesaplarını kapatın.',
          },
          {
            step: '2. Raporları oluşturun',
            desc: "Form 36, Form 37, mizan ve DB-VP'yi hazırlayın.",
          },
          {
            step: '3. AOP kodlarını doğrulayın',
            desc: 'Her pozisyonun AOP kodu vardır. Tutarların doğru olduğundan emin olun.',
          },
          {
            step: '4. Elektronik dosyalayın',
            desc: 'e-submit.crm.com.mk portalını açın ve raporları yükleyin.',
          },
          {
            step: '5. DB-VP\'yi UJP\'ye dosyalayın',
            desc: 'etax.ujp.gov.mk portalında vergi beyannamesini doldurun.',
          },
          {
            step: '6. Arşivleyin',
            desc: 'Tüm dosyalama kopyalarını saklayın. Yıllık hesaplar minimum 10 yıl saklanmalıdır.',
          },
        ],
      },
      {
        title: 'Yaygın hatalar',
        content: null,
        items: [
          'Son tarihi kaçırma — cezalar 500-3.000 EUR arası',
          'Dengesiz bilanço — Varlıklar = Yükümlülükler + Özkaynaklar olmalı',
          'Yanlış AOP kodları',
          "Unutulan DB-VP — UJP'ye ayrı dosyalanır",
          'Onaylanmamış elektronik dosyalama — onay aldığınızı doğrulayın',
        ],
        steps: null,
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'bilans-na-sostojba', title: 'Bilanço ve gelir tablosu: AOP kodları ve yapı' },
      { slug: 'rokovi-ujp-2026', title: 'Vergi takvimi 2026: Tüm UJP tarihleri' },
      { slug: 'ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
    ],
    cta: {
      title: 'Facturino tüm raporları otomatik oluşturur',
      desc: 'Bilanço, gelir tablosu, mizan ve DB-VP — UJP formatında, dosyalamaya hazır.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function GodishnaSmetka2025Page({
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
      {/*  ARTICLE HEADER                                              */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden pt-24 md:pt-32 pb-12 md:pb-16">
        {/* Background blobs */}
        <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
          <div className="absolute top-10 left-10 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob" />
          <div className="absolute top-10 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-25 animate-blob animation-delay-2000" />
        </div>

        <div className="container relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
          {/* Back link */}
          <Link
            href={`/${locale}/blog`}
            className="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800 font-medium mb-8 transition-colors"
          >
            {t.backLink}
          </Link>

          {/* Tag pill */}
          <div className="mb-4">
            <span className="inline-flex items-center rounded-full bg-indigo-50 px-4 py-1.5 text-sm font-semibold text-indigo-600">
              {t.tag}
            </span>
          </div>

          {/* Title */}
          <h1 className="text-3xl sm:text-4xl md:text-5xl font-extrabold tracking-tight leading-[1.1] text-gray-900 mb-6">
            {t.title}
          </h1>

          {/* Meta info */}
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {t.publishDate}
            </span>
            <span className="flex items-center gap-1.5">
              <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {t.readTime}
            </span>
          </div>

          {/* Intro paragraph */}
          <p className="text-lg md:text-xl text-gray-600 leading-relaxed">
            {t.intro}
          </p>
        </div>
      </section>

      {/* ============================================================ */}
      {/*  ARTICLE BODY                                                */}
      {/* ============================================================ */}
      <section className="py-12 md:py-16">
        <div className="container max-w-3xl mx-auto px-4 sm:px-6">
          <div className="space-y-12">
            {t.sections.map((section, i) => (
              <div key={i}>
                <h2 className="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                  {section.title}
                </h2>

                {/* Paragraph content */}
                {section.content && (
                  <p className="text-gray-700 leading-relaxed text-lg">
                    {section.content}
                  </p>
                )}

                {/* Bullet list items */}
                {section.items && (
                  <ul className="space-y-3 mt-2">
                    {section.items.map((item, j) => (
                      <li key={j} className="flex items-start gap-3">
                        <span className="mt-1.5 flex-shrink-0 w-5 h-5 rounded-full bg-green-100 flex items-center justify-center">
                          <svg
                            className="w-3 h-3 text-green-600"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={3}
                          >
                            <path
                              strokeLinecap="round"
                              strokeLinejoin="round"
                              d="M5 13l4 4L19 7"
                            />
                          </svg>
                        </span>
                        <span className="text-gray-700 leading-relaxed">
                          {item}
                        </span>
                      </li>
                    ))}
                  </ul>
                )}

                {/* Numbered steps */}
                {section.steps && (
                  <ol className="space-y-6 mt-4">
                    {section.steps.map((s, j) => (
                      <li key={j} className="flex items-start gap-4">
                        <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 text-sm font-bold flex items-center justify-center mt-0.5">
                          {j + 1}
                        </span>
                        <div>
                          <h3 className="font-semibold text-gray-900 text-lg">
                            {s.step}
                          </h3>
                          <p className="text-gray-600 leading-relaxed mt-1">
                            {s.desc}
                          </p>
                        </div>
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

      {/* ============================================================ */}
      {/*  BOTTOM CTA                                                  */}
      {/* ============================================================ */}
      <section className="section relative overflow-hidden">
        {/* Background gradient */}
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-800" />
        {/* Decorative circles */}
        <div className="absolute top-0 right-0 w-96 h-96 bg-cyan-400/10 rounded-full translate-x-1/2 -translate-y-1/2" />
        <div className="absolute bottom-0 left-0 w-72 h-72 bg-indigo-400/10 rounded-full -translate-x-1/2 translate-y-1/2" />

        <div className="container relative z-10 text-center px-4 sm:px-6 py-8 md:py-12">
          <h2 className="text-3xl sm:text-4xl md:text-5xl font-bold text-white mb-4">
            {t.cta.title}
          </h2>
          <p className="text-lg text-indigo-200 mb-10 max-w-xl mx-auto">
            {t.cta.desc}
          </p>
          <a
            href="https://app.facturino.mk/signup"
            className="inline-flex items-center justify-center bg-white text-indigo-700 font-semibold rounded-full px-8 py-4 text-lg shadow-xl hover:shadow-2xl hover:-translate-y-0.5 transition-all"
          >
            {t.cta.button}
            <svg
              className="ml-2 w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M13 7l5 5m0 0l-5 5m5-5H6"
              />
            </svg>
          </a>
        </div>
      </section>
    </main>
  )
}
