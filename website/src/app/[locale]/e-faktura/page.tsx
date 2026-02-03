import { defaultLocale, isLocale, Locale } from '@/i18n/locales'

const message = {
  mk: 'Facturino веќе е изграден околу структурираните податоци за е‑фактури и е подготвен да се поврзе со официјалниот систем за е‑Фактура во моментот кога УЈП ќе го отвори продукцискиот API и QES интеграцијата. До моментот кога е‑Фактура ќе стане задолжителна, вашиот процес во Facturino ќе биде веќе поставен.',
  sq: 'Facturino tashmë është ndërtuar mbi të dhënat e strukturuara të e‑faturave dhe është gati të lidhet me sistemin zyrtar të e‑Faturës sapo UJP të hapë API‑n prodhuese dhe integrimin QES. Në momentin kur e‑Fatura bëhet e detyrueshme, rrjedha e punës në Facturino do të jetë gati.',
  tr: 'Facturino, e‑fatura için yapılandırılmış veriler etrafında inşa edildi ve UJP üretim API ile QES entegrasyonu açıldığı anda resmî e‑Fatura sistemine bağlanmaya hazır. e‑Fatura zorunlu hâle geldiğinde, Facturino iş akışınız zaten kurulmuş olacak.',
  en: 'Facturino is already built around structured e-invoice data and is ready to connect to the official e-Invoice system as soon as UJP opens the production API and QES integration. By the time e-Invoice becomes mandatory, your workflow in Facturino will already be set up.'
} as const

export default async function EFakturaPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale

  const bullets: Record<Locale, string[]> = {
    mk: [
      'Поддршка за ИД на купувач/продавач',
      'ДДВ распад по стапка',
      'Рокови и начини на плаќање',
      'Структурирани ставки по артикл/услуга'
    ],
    sq: [
      'Mbështetje për NIPT të blerësit/shitësit',
      'Detajim i TVSH‑së sipas normës',
      'Afate dhe mënyra pagese',
      'Të dhëna të strukturuara për artikull/shërbim'
    ],
    tr: [
      'Alıcı/satıcı vergi numaraları',
      'Orana göre KDV dökümü',
      'Vade ve ödeme yöntemleri',
      'Satır bazında yapılandırılmış kalem verisi'
    ],
    en: [
      'Buyer/seller tax ID support',
      'VAT breakdown by rate',
      'Payment terms and methods',
      'Structured line items per product/service'
    ]
  }

  const headings: Record<Locale, { h1: string; promise: string; ready: string }> = {
    mk: {
      h1: 'Е‑Фактура: пред вас, не зад вас',
      promise:
        'Ветување: кога официјалниот API + QES ќе бидат целосно достапни, Facturino се поврзува – без миграција.',
      ready: 'Нашиот модел веќе ги поддржува:'
    },
    sq: {
      h1: 'e‑Faturë: një hap përpara',
      promise:
        'Premtim: sapo API‑ja zyrtare + QES të jenë plotësisht të disponueshme, Facturino lidhet – pa migrim.',
      ready: 'Modeli ynë tashmë mbështet:'
    },
    tr: {
      h1: 'e‑Fatura: bir adım önde',
      promise:
        'Söz: Resmî API + QES akışı tamamen açıldığında Facturino bağlanır – taşımaya gerek yok.',
      ready: 'Modelimiz şimdiden destekliyor:'
    },
    en: {
      h1: 'e-Invoice: Ahead of the Curve',
      promise:
        'Promise: When the official API + QES become fully available, Facturino connects - no migration needed.',
      ready: 'Our model already supports:'
    }
  }

  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-3 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{headings[locale].h1}</h1>
        <p className="mb-4 text-[color:var(--color-muted)]">{message[locale]}</p>
        <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
          <p className="font-medium">{headings[locale].promise}</p>
        </div>
        <h2 className="mb-2 text-xl font-semibold">{headings[locale].ready}</h2>
        <ul className="list-disc space-y-1 pl-5 text-gray-700">
          {bullets[locale].map((b, i) => (
            <li key={i}>{b}</li>
          ))}
        </ul>
      </div>
    </main>
  )
}

