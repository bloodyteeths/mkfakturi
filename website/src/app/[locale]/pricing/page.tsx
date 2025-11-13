import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import Link from 'next/link'

const copy = {
  mk: {
    h1: 'Цени',
    sub: '14‑дневен бесплатен пробен период. Без обврска.',
    plans: [
      { name: 'Starter', price: '—', bullets: ['Е‑Фактура подготвено', '1 корисник', 'AI предлози (основно)'] },
      { name: 'Pro', price: '—', bullets: ['Мулти‑корисници/улоги', 'PSD2 изводи', 'Автоматизации и приоритетна поддршка'] },
      { name: 'Business', price: '—', bullets: ['Повеќе компании', 'API и напредни овластувања', 'SLA'] }
    ],
    cta: 'Побарај понуда'
  },
  sq: {
    h1: 'Çmimet',
    sub: 'Provë falas 14 ditë. Pa detyrim.',
    plans: [
      { name: 'Starter', price: '—', bullets: ['Gati për e‑Faturë', '1 përdorues', 'Sugjerime AI (bazë)'] },
      { name: 'Pro', price: '—', bullets: ['Shumë përdorues/role', 'Ekstrakte PSD2', 'Automatizime dhe suport prioritar'] },
      { name: 'Business', price: '—', bullets: ['Shumë kompani', 'API & leje të avancuara', 'SLA'] }
    ],
    cta: 'Kërko ofertë'
  },
  tr: {
    h1: 'Fiyatlar',
    sub: '14 gün ücretsiz deneme. Taahhüt yok.',
    plans: [
      { name: 'Starter', price: '—', bullets: ['e‑Fatura hazır', '1 kullanıcı', 'AI önerileri (temel)'] },
      { name: 'Pro', price: '—', bullets: ['Çoklu kullanıcı/roller', 'PSD2 ekstreleri', 'Otomasyonlar ve öncelikli destek'] },
      { name: 'Business', price: '—', bullets: ['Birden çok şirket', 'API ve gelişmiş yetkiler', 'SLA'] }
    ],
    cta: 'Teklif iste'
  }
} as const

export default function PricingPage({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = copy[locale]
  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-2 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{t.h1}</h1>
        <p className="mb-8 text-[color:var(--color-muted)]">{t.sub}</p>
        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
          {t.plans.map((p, i) => (
            <div key={i} className="card">
              <h2 className="mb-2 text-xl font-semibold">{p.name}</h2>
              <div className="mb-3 text-3xl font-bold">{p.price}</div>
              <ul className="mb-4 list-disc space-y-1 pl-5 text-sm text-gray-700">
                {p.bullets.map((b, j) => (
                  <li key={j}>{b}</li>
                ))}
              </ul>
              <Link href={`/${locale}/contact`} className="btn-primary w-full text-center">
                {t.cta}
              </Link>
            </div>
          ))}
        </div>
      </div>
    </main>
  )
}

