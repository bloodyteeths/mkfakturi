import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function PricingPreview({ t, locale }: { t: Dictionary; locale: Locale }) {
  if (!t.pricingPreview) return null
  const p = t.pricingPreview
  return (
    <section className="section">
      <div className="container">
        <div className="text-center max-w-3xl mx-auto mb-6 md:mb-12">
          <h2 className="text-3xl md:text-4xl font-bold text-gray-900">
            {p.title}
          </h2>
        </div>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 max-w-4xl mx-auto">
          {p.plans.map((plan, i) => (
            <div className={`relative rounded-2xl border p-5 md:p-6 transition-all hover:shadow-lg ${i === 1 ? 'border-indigo-200 bg-indigo-50/30 shadow-md ring-1 ring-indigo-100' : 'border-gray-200 bg-white'}`} key={i}>
              {plan.popular && (
                <span className="absolute -top-3 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs font-semibold px-3 py-1 rounded-full">
                  {t.pricingPage?.popularBadge || 'Popular'}
                </span>
              )}
              <h3 className="text-lg font-bold text-gray-900 mb-1">{plan.name}</h3>
              {plan.price && (
                <p className="text-2xl font-extrabold text-indigo-600 mb-4">{plan.price}</p>
              )}
              <ul className="space-y-2 text-sm text-gray-600">
                {plan.bullets.map((b, j) => (
                  <li key={j} className="flex items-start gap-2">
                    <svg className="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                      <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {b}
                  </li>
                ))}
              </ul>
            </div>
          ))}
        </div>
        <div className="text-center mt-8">
          <Link href={`/${locale}/pricing`} className="btn-primary">{p.cta}</Link>
        </div>
      </div>
    </section>
  )
}
