import { defaultLocale, isLocale, Locale } from '@/i18n/locales'

const copy = {
  mk: {
    h1: 'За сметководствени канцеларии',
    sub: 'Една пријава → десетици клиенти. Максимална контрола и брзина.',
    blocks: [
      ['Мулти‑компанија', 'Секој клиент е посебна компанија со сопствени сметки, документи и овластувања.'],
      ['AI асистент', 'Предлози за ДДВ и конта по ставка; човек секогаш потврдува.'],
      ['PSD2 банки', 'Увоз на изводи и полуавтоматско порамнување за минути.'],
      ['IFRS извештаи', 'Пакет IFRS за професионални извештаи по клиент.'],
      ['Работни текови', 'Рекурентни фактури, потсетници и правила „ако неплатено → потсети“.'],
      ['Мултијазичен UI', 'MK, SQ, TR – секој корисник го избира својот јазик.']
    ]
  },
  sq: {
    h1: 'Për zyrat kontabile',
    sub: 'Një hyrje → dhjetëra klientë. Kontroll dhe shpejtësi maksimale.',
    blocks: [
      ['Shumë kompani', 'Çdo klient si kompani e veçantë me llogari, dokumente dhe leje.'],
      ['Asistent AI', 'Sugjerime për TVSH dhe llogari për çdo rresht; njeriu konfirmon.'],
      ['Banka PSD2', 'Import i ekstrakteve dhe pajtim në minuta.'],
      ['Raporte IFRS', 'Paketë IFRS për raporte profesionale për klient.'],
      ['Rrjedha pune', 'Fatura periodike, rikujtues dhe rregulla “nëse e papaguar → kujto”.'],
      ['Ndërfaqe shumëgjuhëshe', 'MK, SQ, TR – çdo përdorues zgjedh gjuhën.']
    ]
  },
  tr: {
    h1: 'Muhasebe ofisleri için',
    sub: 'Tek giriş → onlarca müşteri. En üst düzey kontrol ve hız.',
    blocks: [
      ['Çok şirket', 'Her müşteri ayrı şirket; ayrı hesaplar, belgeler ve yetkiler.'],
      ['Yapay zekâ asistanı', 'Her satır için KDV ve hesap önerileri; onay sizde.'],
      ['PSD2 bankalar', 'Ekstre içe aktarımı ve dakikalarda mutabakat.'],
      ['IFRS raporları', 'Her müşteri için profesyonel IFRS raporları.'],
      ['İş akışları', 'Tekrarlayan faturalar, hatırlatıcılar ve "ödenmediyse → hatırlat" kuralı.'],
      ['Çok dilli arayüz', 'MK, SQ, TR – kullanıcı bazında dil seçimi.']
    ]
  },
  en: {
    h1: 'For Accounting Firms',
    sub: 'One login → dozens of clients. Maximum control and speed.',
    blocks: [
      ['Multi-company', 'Each client is a separate company with its own accounts, documents, and permissions.'],
      ['AI Assistant', 'VAT and account suggestions per line item; human always confirms.'],
      ['PSD2 Banks', 'Import statements and semi-automatic reconciliation in minutes.'],
      ['IFRS Reports', 'IFRS package for professional reports per client.'],
      ['Workflows', 'Recurring invoices, reminders, and "if unpaid → remind" rules.'],
      ['Multilingual UI', 'MK, SQ, TR, EN – each user chooses their language.']
    ]
  }
} as const

export default async function ForAccountantsPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = copy[locale]
  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-2 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{t.h1}</h1>
        <p className="mb-6 text-[color:var(--color-muted)]">{t.sub}</p>
        {/* Comparison sentence to set positioning */}
        {locale === 'mk' && (
          <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <p className="font-medium">
              Facturino е софтвер на ниво на QuickBooks/Xero класа – но со логика, извештаи и DDV правила за Македонија.
            </p>
          </div>
        )}
        {locale === 'sq' && (
          <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <p className="font-medium">
              Facturino është i klasit QuickBooks/Xero – por me logjikë, raporte dhe rregulla TVSH për Maqedoninë.
            </p>
          </div>
        )}
        {locale === 'tr' && (
          <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <p className="font-medium">
              QuickBooks/Xero seviyesinde bir deneyim, ama Makedonya mevzuatına göre tasarlanmış.
            </p>
          </div>
        )}
        {locale === 'en' && (
          <div className="mb-6 rounded-lg bg-white p-4 shadow-sm">
            <p className="font-medium">
              Facturino is QuickBooks/Xero-class software – but with logic, reports, and VAT rules for Macedonia.
            </p>
          </div>
        )}
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {t.blocks.map(([title, body], i) => (
            <div key={i} className="card">
              <h2 className="mb-1 text-lg font-semibold">{title}</h2>
              <p className="text-sm text-gray-700">{body}</p>
            </div>
          ))}
        </div>
      </div>
    </main>
  )
}
