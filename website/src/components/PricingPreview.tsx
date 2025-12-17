import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function PricingPreview({ t, locale }: { t: Dictionary; locale: Locale }) {
  if (!t.pricingPreview) return null
  const p = t.pricingPreview
  return (
    <section className="section">
      <div className="container">
        <div className="mb-6 flex items-center justify-between">
          <h2 className="text-2xl font-bold md:text-3xl" style={{color:'var(--color-primary)'}}>
            {p.title}
          </h2>
          <Link href={`/${locale}/pricing`} className="btn-primary">{p.cta}</Link>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
          {p.plans.map((plan, i) => (
            <div className="card p-4 md:p-6" key={i}>
              <h3 className="mb-2 text-base md:text-lg font-semibold">{plan.name}</h3>
              <ul className="list-disc space-y-1 pl-4 md:pl-5 text-sm text-gray-700">
                {plan.bullets.map((b, j) => (
                  <li key={j}>{b}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

