import { defaultLocale, isLocale, Locale } from '@/i18n/locales'

const copy = {
  mk: {
    h1: 'Безбедност и доверливост',
    blocks: [
      ['Енкрипција', 'Податоци шифрирани при пренос и во мирување.'],
      ['Хостирање во ЕУ', 'Инфраструктура во ЕУ регион со редовни резервни копии.'],
      ['Пристап и улоги', 'Рол‑базиран пристап: сопственик, сметководител, вработен.'],
      ['Аудит траги', 'Бележиме клучни активности за целосна трага.']
    ]
  },
  sq: {
    h1: 'Siguria dhe privatësia',
    blocks: [
      ['Enkriptimi', 'Të dhëna të enkriptuara në transit dhe në pushim.'],
      ['Strehim në BE', 'Infrastrukturë në rajonin e BE‑së me kopje rezervë të rregullta.'],
      ['Qasje & role', 'Qasje e bazuar në role: pronar, kontabilist, staf.'],
      ['Gjurmë auditi', 'Regjistrojmë veprimet kyçe për gjurmë të plota.']
    ]
  },
  tr: {
    h1: 'Güvenlik ve gizlilik',
    blocks: [
      ['Şifreleme', 'Veriler aktarımda ve depoda şifrelenir.'],
      ['AB bölgesi barındırma', 'AB bölgesinde altyapı ve düzenli yedekler.'],
      ['Erişim ve roller', 'Rollere dayalı erişim: sahip, muhasebeci, personel.'],
      ['Audit kayıtları', 'Tam izlenebilirlik için kritik işlemler kaydedilir.']
    ]
  }
} as const

export default function SecurityPage({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = copy[locale]
  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-6 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{t.h1}</h1>
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

