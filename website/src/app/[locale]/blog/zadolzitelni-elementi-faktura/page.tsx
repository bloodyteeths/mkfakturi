import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import Link from 'next/link'

export function generateStaticParams() {
  return [{ locale: 'mk' }, { locale: 'sq' }, { locale: 'tr' }, { locale: 'en' }]
}

export async function generateMetadata({ params }: { params: Promise<{ locale: string }> }) {
  const { locale } = await params
  return buildPageMetadata(locale, '/blog/zadolzitelni-elementi-faktura', {
    title: {
      mk: 'Задолжителни елементи на фактура во Македонија — Facturino',
      en: 'Mandatory Invoice Elements in Macedonia — Facturino',
      sq: 'Elementet e detyrueshme të faturës në Maqedoni — Facturino',
      tr: 'Makedonya\'da zorunlu fatura unsurları — Facturino',
    },
    description: {
      mk: 'Комплетна листа на задолжителни елементи на фактура според Законот за ДДВ — ЕДБ, ЕМБС, ставки, ДДВ рекапитулација и казни за неусогласеност.',
      en: 'Complete list of mandatory invoice elements under the VAT Law — EDB, EMBS, line items, VAT recapitulation, and penalties for non-compliance.',
      sq: 'Lista e plotë e elementeve të detyrueshme të faturës sipas Ligjit për TVSH — EDB, EMBS, zërat, rekapitulimi i TVSH-së dhe gjobat.',
      tr: 'KDV Yasası kapsamında zorunlu fatura unsurlarının tam listesi — EDB, EMBS, kalemler, KDV rekapitülasyonu ve uyumsuzluk cezaları.',
    },
  })
}

/* ------------------------------------------------------------------ */
/*  Inline copy – 4 locales (mk, sq, tr, en)                         */
/* ------------------------------------------------------------------ */
const copy = {
  mk: {
    backLink: '\u2190 Назад кон блог',
    tag: 'Едукација',
    title: 'Задолжителни елементи на фактура во Македонија',
    publishDate: '5 февруари 2026',
    readTime: '5 мин читање',
    intro:
      'Според Законот за данокот на додадена вредност (ДДВ) во Македонија, секоја фактура мора да содржи одредени задолжителни елементи. Отсуството на кој било од овие елементи може да ја направи фактурата даночно невалидна, што води до одбивање на ДДВ одбитокот или казни при инспекција. Во овој напис ги наведуваме сите елементи кои мора да ги содржи вашата фактура.',
    sections: [
      {
        title: 'Податоци за издавачот (продавачот)',
        content:
          'Секоја фактура мора да започне со комплетни податоци за компанијата која ја издава. Овие податоци го идентификуваат продавачот пред даночните органи и пред купувачот.',
        items: [
          'Целосен назив на фирмата — онака како е регистрирана во ЦРСМ',
          'Адреса на седиштето — улица, број, поштенски код и град',
          'ЕДБ (Единствен даночен број) — 13-цифрен број издаден од УЈП',
          'ЕМБС (Единствен матичен број на субјект) — 7-цифрен број од ЦРСМ',
          'Банкарска сметка — трансакциската сметка за прием на уплати',
          'Телефон и е-пошта — опционално, но препорачано за професионалност',
        ],
      },
      {
        title: 'Податоци за примачот (купувачот)',
        content:
          'Фактурата мора да го идентификува купувачот со истата прецизност. Без точни податоци за купувачот, фактурата не може да се користи за одбиток на ДДВ.',
        items: [
          'Целосен назив на компанијата-купувач',
          'Адреса на седиштето на купувачот',
          'ЕДБ на купувачот — задолжителен за Б2Б фактури',
          'ЕМБС на купувачот — задолжителен елемент',
          'За физички лица — име, презиме и адреса',
        ],
      },
      {
        title: 'Број, датум и идентификација',
        content:
          'Секоја фактура мора да има уникатна идентификација која овозможува следливост и контрола.',
        items: [
          'Секвенцијален број на фактурата — без прескокнување, по хронолошки ред',
          'Датум на издавање — денот кога фактурата е создадена',
          'Датум на промет — денот кога е извршена испораката или услугата',
          'Место на промет — каде е извршен прометот (важно за ДДВ)',
          'Рок за плаќање — до кога купувачот треба да плати',
        ],
      },
      {
        title: 'Ставки, ДДВ и вкупен износ',
        content:
          'Срцето на фактурата се ставките и пресметката на ДДВ. Секоја ставка мора да биде детално опишана, а ДДВ мора да биде прикажан посебно.',
        items: [
          'Опис на секоја ставка — производ или услуга, доволно детален за идентификација',
          'Количина и единица мерка — број на единици, килограми, часови итн.',
          'Единечна цена без ДДВ — цената по единица пред пресметка на данок',
          'ДДВ стапка — 18% (стандардна), 5% (намалена) или 0% (ослободено)',
          'Износ на ДДВ по ставка — пресметаниот данок за секоја ставка',
          'Вкупен износ по ставка — единечна цена x количина + ДДВ',
          'Рекапитулација на ДДВ — збирна табела по стапки (основица + ДДВ)',
          'Вкупен износ за плаќање — финалната сума која купувачот ја должи',
        ],
      },
      {
        title: 'Казни за неусогласеност',
        content:
          'Непочитувањето на законските барања за фактурирање повлекува сериозни последици. Управата за јавни приходи (УЈП) има право да изрече казни при инспекциски контроли.',
        items: [
          'Парична казна од 500 до 3.000 EUR за правно лице за секоја неправилна фактура',
          'Одбивање на ДДВ одбитокот за купувачот — ако фактурата нема валиден ЕДБ или ДДВ рекапитулација',
          'Дополнителна казна од 200 до 500 EUR за одговорното лице во компанијата',
          'Повторни прекршоци можат да доведат до забрана за вршење дејност',
          'УЈП може да побара корекција на фактурата (кредитно известување) и повторно издавање',
        ],
      },
    ],
    relatedTitle: 'Поврзани статии',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Како да направите фактура: Чекор-по-чекор водич' },
      { slug: 'sto-e-e-faktura', title: 'Што е е-фактура и зошто е задолжителна?' },
      { slug: 'ddv-vodich-mk', title: 'ДДВ во Македонија: Целосен водич за 2026' },
    ],
    cta: {
      title: 'Facturino гарантира комплетна фактура',
      desc: 'Секоја фактура издадена преку Facturino ги содржи сите задолжителни елементи. Системот валидира пред испраќање — нема ризик од казни.',
      button: 'Започни бесплатно',
    },
  },
  en: {
    backLink: '\u2190 Back to blog',
    tag: 'Education',
    title: 'Mandatory Invoice Elements in Macedonia',
    publishDate: 'February 5, 2026',
    readTime: '5 min read',
    intro:
      'Under the Value Added Tax (VAT) Law in Macedonia, every invoice must contain certain mandatory elements. The absence of any of these elements can render the invoice invalid for tax purposes, leading to rejection of the VAT deduction or penalties during inspection. In this article, we list every element your invoice must contain.',
    sections: [
      {
        title: 'Issuer (seller) information',
        content:
          'Every invoice must begin with complete information about the issuing company. This data identifies the seller before tax authorities and the buyer.',
        items: [
          'Full company name — as registered with the Central Registry (CRMS)',
          'Registered address — street, number, postal code, and city',
          'EDB (Unique Tax Number) — a 13-digit number issued by UJP',
          'EMBS (Unique Entity Registration Number) — a 7-digit number from CRMS',
          'Bank account — the transaction account for receiving payments',
          'Phone and email — optional but recommended for professionalism',
        ],
      },
      {
        title: 'Recipient (buyer) information',
        content:
          'The invoice must identify the buyer with the same precision. Without accurate buyer information, the invoice cannot be used for VAT deduction.',
        items: [
          'Full buyer company name',
          'Buyer registered address',
          'Buyer EDB — mandatory for B2B invoices',
          'Buyer EMBS — a mandatory element',
          'For individuals — first name, last name, and address',
        ],
      },
      {
        title: 'Number, date, and identification',
        content:
          'Every invoice must have unique identification that enables traceability and control.',
        items: [
          'Sequential invoice number — no gaps, in chronological order',
          'Issue date — the day the invoice was created',
          'Supply date — the day the delivery or service was performed',
          'Place of supply — where the transaction occurred (important for VAT)',
          'Payment deadline — by when the buyer should pay',
        ],
      },
      {
        title: 'Line items, VAT, and total amount',
        content:
          'The heart of the invoice is the line items and VAT calculation. Each item must be described in detail, and VAT must be shown separately.',
        items: [
          'Description of each item — product or service, detailed enough for identification',
          'Quantity and unit of measurement — number of units, kilograms, hours, etc.',
          'Unit price excluding VAT — the price per unit before tax',
          'VAT rate — 18% (standard), 5% (reduced), or 0% (exempt)',
          'VAT amount per item — the calculated tax for each line item',
          'Total amount per item — unit price x quantity + VAT',
          'VAT recapitulation — summary table by rate (tax base + VAT)',
          'Grand total for payment — the final amount owed by the buyer',
        ],
      },
      {
        title: 'Penalties for non-compliance',
        content:
          'Failure to comply with legal invoicing requirements carries serious consequences. The Public Revenue Office (UJP) has the authority to impose penalties during inspection audits.',
        items: [
          'Fine of 500 to 3,000 EUR per legal entity for each improper invoice',
          'Rejection of VAT deduction for the buyer — if the invoice lacks a valid EDB or VAT recapitulation',
          'Additional fine of 200 to 500 EUR for the responsible person within the company',
          'Repeat offenses can lead to a ban on conducting business',
          'UJP may require invoice correction (credit note) and re-issuance',
        ],
      },
    ],
    relatedTitle: 'Related articles',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'How to Create an Invoice: Step-by-Step Guide' },
      { slug: 'sto-e-e-faktura', title: 'What Is E-Invoice and Why Is It Mandatory?' },
      { slug: 'ddv-vodich-mk', title: 'VAT in Macedonia: Complete Guide for 2026' },
    ],
    cta: {
      title: 'Facturino guarantees a complete invoice',
      desc: 'Every invoice issued through Facturino contains all mandatory elements. The system validates before sending — no risk of penalties.',
      button: 'Start free',
    },
  },
  sq: {
    backLink: '\u2190 Kthehu te blogu',
    tag: 'Edukim',
    title: 'Elementet e detyrueshme të faturës në Maqedoni',
    publishDate: '5 shkurt 2026',
    readTime: '5 min lexim',
    intro:
      'Sipas Ligjit për Tatimin mbi Vlerën e Shtuar (TVSH) në Maqedoni, çdo faturë duhet të përmbajë elemente të caktuara të detyrueshme. Mungesa e ndonjërit prej tyre mund ta bëjë faturën të pavlefshme për qëllime tatimore, duke çuar në refuzimin e zbritjes së TVSH-së ose gjoba gjatë inspektimit. Në këtë artikull listojmë çdo element që duhet ta përmbajë fatura juaj.',
    sections: [
      {
        title: 'Informacioni i lëshuesit (shitësit)',
        content:
          'Çdo faturë duhet të fillojë me informacion të plotë për kompaninë lëshuese. Këto të dhëna identifikojnë shitësin para autoriteteve tatimore dhe blerësit.',
        items: [
          'Emri i plotë i kompanisë — siç është regjistruar në Regjistrin Qendror (QRMK)',
          'Adresa e regjistruar — rruga, numri, kodi postar dhe qyteti',
          'EDB (Numri Unik Tatimor) — numër 13-shifror i lëshuar nga UJP',
          'EMBS (Numri Unik i Regjistrimit të Subjektit) — numër 7-shifror nga QRMK',
          'Llogarija bankare — llogarija e transaksionit për pranimin e pagesave',
          'Telefoni dhe emaili — opsionale por e rekomanduar për profesionalizëm',
        ],
      },
      {
        title: 'Informacioni i marrësit (blerësit)',
        content:
          'Fatura duhet të identifikojë blerësin me të njëjtën saktësi. Pa informacion të saktë të blerësit, fatura nuk mund të përdoret për zbritje të TVSH-së.',
        items: [
          'Emri i plotë i kompanisë blerëse',
          'Adresa e regjistruar e blerësit',
          'EDB e blerësit — e detyrueshme për faturat B2B',
          'EMBS e blerësit — element i detyrueshëm',
          'Për persona fizikë — emri, mbiemri dhe adresa',
        ],
      },
      {
        title: 'Numri, data dhe identifikimi',
        content:
          'Çdo faturë duhet të ketë identifikim unik që mundëson gjurmueshmëri dhe kontroll.',
        items: [
          'Numri sekuencial i faturës — pa boshllëqe, në rend kronologjik',
          'Data e lëshimit — dita kur u krijua fatura',
          'Data e furnizimit — dita kur u krye dorëzimi ose shërbimi',
          'Vendi i furnizimit — ku ndodhi transaksioni (i rëndësishëm për TVSH-në)',
          'Afati i pagesës — deri kur duhet të paguajë blerësi',
        ],
      },
      {
        title: 'Zërat, TVSH-ja dhe shuma totale',
        content:
          'Zemra e faturës janë zërat dhe llogaritja e TVSH-së. Çdo zë duhet përshkruar në detaje dhe TVSH-ja duhet treguar veçmas.',
        items: [
          'Përshkrimi i çdo zëri — produkti ose shërbimi, mjaft i detajuar për identifikim',
          'Sasia dhe njësia matëse — numri i njësive, kilogramë, orë etj.',
          'Çmimi për njësi pa TVSH — çmimi para tatimit',
          'Norma e TVSH-së — 18% (standarde), 5% (e reduktuar) ose 0% (e përjashtuar)',
          'Shuma e TVSH-së për zë — tatimi i llogaritur për çdo zë',
          'Shuma totale për zë — çmimi x sasia + TVSH',
          'Rekapitulimi i TVSH-së — tabelë përmbledhëse sipas normës (baza + TVSH)',
          'Shuma totale për pagesë — shuma finale që i detyrohet blerësi',
        ],
      },
      {
        title: 'Gjobat për mospajtueshmëri',
        content:
          'Mosrespektimi i kërkesave ligjore për faturimin sjell pasoja serioze. Drejtoria e të Ardhurave Publike (UJP) ka autoritetin të vendosë gjoba gjatë auditimeve inspektuese.',
        items: [
          'Gjobë nga 500 deri 3,000 EUR për subjektin juridik për çdo faturë të parregullt',
          'Refuzimi i zbritjes së TVSH-së për blerësin — nëse fatura nuk ka EDB valid ose rekapitulim TVSH-je',
          'Gjobë shtesë nga 200 deri 500 EUR për personin përgjegjës brenda kompanisë',
          'Shkeljet e përsëritura mund të çojnë në ndalim të ushtrimit të veprimtarisë',
          'UJP mund të kërkojë korrigjim fature (notë kreditimi) dhe rilëshim',
        ],
      },
    ],
    relatedTitle: 'Artikuj të ngjashëm',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Si të krijoni një faturë: Udhëzues hap pas hapi' },
      { slug: 'sto-e-e-faktura', title: 'Çfarë është e-fatura dhe pse është e detyrueshme?' },
      { slug: 'ddv-vodich-mk', title: 'TVSH në Maqedoni: Udhëzues i plotë për 2026' },
    ],
    cta: {
      title: 'Facturino garanton faturë të plotë',
      desc: 'Çdo faturë e lëshuar përmes Facturino përmban të gjitha elementet e detyrueshme. Sistemi validon para dërgimit — pa rrezik gjobash.',
      button: 'Fillo falas',
    },
  },
  tr: {
    backLink: '\u2190 Bloga dön',
    tag: 'Eğitim',
    title: 'Makedonya\'da zorunlu fatura unsurları',
    publishDate: '5 Şubat 2026',
    readTime: '5 dk okuma',
    intro:
      'Makedonya\'daki Katma Değer Vergisi (KDV) Yasasına göre her faturanın belirli zorunlu unsurları içermesi gerekir. Bu unsurlardan herhangi birinin eksikliği faturayı vergi açısından geçersiz kılabilir, KDV indiriminin reddine veya denetim sırasında cezalara yol açabilir. Bu makalede faturanızın içermesi gereken her unsuru listeliyoruz.',
    sections: [
      {
        title: 'Düzenleyen (satıcı) bilgileri',
        content:
          'Her fatura, düzenleyen şirketin tam bilgileriyle başlamalıdır. Bu veriler satıcıyı vergi makamları ve alıcı nezdinde tanımlar.',
        items: [
          'Tam şirket adı — Merkezi Sicil\'e (CRMS) kayıtlı haliyle',
          'Kayıtlı adres — sokak, numara, posta kodu ve şehir',
          'EDB (Benzersiz Vergi Numarası) — UJP tarafından verilen 13 haneli numara',
          'EMBS (Benzersiz Kuruluş Sicil Numarası) — CRMS\'den 7 haneli numara',
          'Banka hesabı — ödemeleri almak için kullanılan işlem hesabı',
          'Telefon ve e-posta — isteğe bağlı ancak profesyonellik için önerilir',
        ],
      },
      {
        title: 'Alıcı bilgileri',
        content:
          'Fatura, alıcıyı aynı hassasiyetle tanımlamalıdır. Doğru alıcı bilgileri olmadan fatura KDV indirimi için kullanılamaz.',
        items: [
          'Alıcı şirketin tam adı',
          'Alıcının kayıtlı adresi',
          'Alıcının EDB\'si — B2B faturalar için zorunlu',
          'Alıcının EMBS\'si — zorunlu unsur',
          'Bireyler için — ad, soyad ve adres',
        ],
      },
      {
        title: 'Numara, tarih ve kimlik bilgileri',
        content:
          'Her faturanın izlenebilirlik ve kontrolü sağlayan benzersiz bir kimliği olmalıdır.',
        items: [
          'Sıralı fatura numarası — boşluk olmadan, kronolojik sırayla',
          'Düzenleme tarihi — faturanın oluşturulduğu gün',
          'Teslim tarihi — teslimat veya hizmetin gerçekleştirildiği gün',
          'Teslim yeri — işlemin gerçekleştiği yer (KDV için önemli)',
          'Ödeme vadesi — alıcının ne zamana kadar ödemesi gerektiği',
        ],
      },
      {
        title: 'Kalemler, KDV ve toplam tutar',
        content:
          'Faturanın kalbi kalemler ve KDV hesaplamasıdır. Her kalem ayrıntılı olarak tanımlanmalı ve KDV ayrı gösterilmelidir.',
        items: [
          'Her kalemin açıklaması — ürün veya hizmet, tanımlama için yeterince detaylı',
          'Miktar ve ölçü birimi — adet, kilogram, saat vb.',
          'KDV hariç birim fiyat — vergi öncesi birim başına fiyat',
          'KDV oranı — %18 (standart), %5 (indirimli) veya %0 (muaf)',
          'Kalem başına KDV tutarı — her kalem için hesaplanan vergi',
          'Kalem başına toplam tutar — birim fiyat x miktar + KDV',
          'KDV rekapitülasyonu — orana göre özet tablo (matrah + KDV)',
          'Ödeme için genel toplam — alıcının borçlu olduğu nihai tutar',
        ],
      },
      {
        title: 'Uyumsuzluk cezaları',
        content:
          'Yasal faturalama gereksinimlerine uyulmaması ciddi sonuçlar doğurur. Kamu Gelir İdaresi (UJP) denetim incelemeleri sırasında ceza uygulama yetkisine sahiptir.',
        items: [
          'Her hatalı fatura için tüzel kişiye 500 ila 3.000 EUR para cezası',
          'Alıcı için KDV indiriminin reddi — faturada geçerli EDB veya KDV rekapitülasyonu yoksa',
          'Şirket içindeki sorumlu kişiye 200 ila 500 EUR ek para cezası',
          'Tekrarlanan ihlaller ticari faaliyet yasağına yol açabilir',
          'UJP fatura düzeltmesi (alacak dekontu) ve yeniden düzenleme talep edebilir',
        ],
      },
    ],
    relatedTitle: 'İlgili makaleler',
    related: [
      { slug: 'kako-da-napravite-faktura', title: 'Fatura nasıl oluşturulur: Adım adım rehber' },
      { slug: 'sto-e-e-faktura', title: 'E-fatura nedir ve neden zorunludur?' },
      { slug: 'ddv-vodich-mk', title: "Makedonya'da KDV: 2026 için eksiksiz rehber" },
    ],
    cta: {
      title: 'Facturino eksiksiz fatura garantisi verir',
      desc: 'Facturino ile düzenlenen her fatura tüm zorunlu unsurları içerir. Sistem göndermeden önce doğrular — ceza riski yoktur.',
      button: 'Ücretsiz başla',
    },
  },
} as const

/* ------------------------------------------------------------------ */
/*  Page component                                                    */
/* ------------------------------------------------------------------ */
export default async function ZadolzitelniElementiFakturaPage({
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
