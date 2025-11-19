import Link from 'next/link'
import { getDictionary } from '@/i18n/dictionaries'
import { isLocale, Locale, defaultLocale } from '@/i18n/locales'
import FAQ from '@/components/FAQ'
import ComparisonTable from '@/components/ComparisonTable'

export default async function PricingPage({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = await getDictionary(locale)

  if (!t.pricingPage) return null
  const { h1, sub, sectionCompany, sectionPartner, popularBadge, recommendedBadge, companyPlans, partnerPlans, cta, ctaPartner } = t.pricingPage

  return (
    <main className="min-h-screen bg-slate-50">
      {/* Hero Section */}
      <section className="pt-32 pb-20 bg-slate-900 text-white relative overflow-hidden">
        <div className="absolute inset-0 bg-gradient-to-br from-indigo-900 to-slate-900"></div>
        <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
          <div className="absolute top-20 left-1/4 w-96 h-96 bg-indigo-500/10 rounded-full blur-3xl"></div>
          <div className="absolute bottom-20 right-1/4 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
        </div>

        <div className="container relative z-10 text-center">
          <h1 className="text-4xl md:text-6xl font-extrabold mb-6 tracking-tight">
            {h1}
          </h1>
          <p className="text-xl text-indigo-200 max-w-2xl mx-auto">
            {sub}
          </p>
        </div>
      </section>

      <div className="container -mt-10 relative z-20 pb-20">
        {/* Company Pricing */}
        <div className="mb-24">
          <div className="flex items-center justify-center gap-4 mb-12">
            <h2 className="text-2xl font-bold text-gray-900">{sectionCompany}</h2>
            <div className="h-px w-12 bg-gray-200"></div>
          </div>

          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
            {companyPlans.map((p, i) => (
              <div key={i} className={`relative flex flex-col bg-white rounded-2xl shadow-sm border transition-all duration-300 hover:shadow-xl hover:-translate-y-1 ${p.popular ? 'border-indigo-500 ring-1 ring-indigo-500 z-10 scale-105' : 'border-gray-200'}`}>
                {p.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-indigo-600 to-cyan-500 px-4 py-1 text-xs font-bold text-white whitespace-nowrap shadow-md">
                    {popularBadge}
                  </div>
                )}

                <div className="p-6 flex-grow">
                  <h3 className="mb-4 text-lg font-bold text-gray-900">{p.name}</h3>
                  <div className="mb-6">
                    <span className="text-4xl font-extrabold text-gray-900">{p.price}</span>
                    <span className="text-sm text-gray-500 font-medium">{p.period}</span>
                  </div>

                  <ul className="space-y-4 mb-8">
                    {p.bullets.map((b, j) => (
                      <li key={j} className="flex items-start text-sm text-gray-600">
                        <svg className="w-5 h-5 mr-3 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{b}</span>
                      </li>
                    ))}
                  </ul>
                </div>

                <div className="p-6 pt-0 mt-auto">
                  <Link
                    href={`/${locale}/contact`}
                    className={`block w-full py-3 px-4 rounded-xl text-center font-bold transition-all ${p.popular
                      ? 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-lg hover:shadow-indigo-500/30'
                      : 'bg-gray-50 text-gray-900 hover:bg-gray-100 border border-gray-200'
                      }`}
                  >
                    {cta}
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Partner Pricing */}
        <div className="max-w-5xl mx-auto">
          <div className="flex items-center justify-center gap-4 mb-12">
            <h2 className="text-2xl font-bold text-gray-900">{sectionPartner}</h2>
            <div className="h-px w-12 bg-gray-200"></div>
          </div>

          <div className="grid grid-cols-1 gap-8 md:grid-cols-2">
            {partnerPlans.map((p, i) => (
              <div key={i} className={`relative flex flex-col bg-white rounded-2xl shadow-sm border transition-all duration-300 hover:shadow-xl ${p.popular ? 'border-green-500 ring-1 ring-green-500' : 'border-gray-200'}`}>
                {p.popular && (
                  <div className="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-green-600 px-4 py-1 text-xs font-bold text-white whitespace-nowrap shadow-md">
                    {recommendedBadge}
                  </div>
                )}

                <div className="p-8 flex-grow">
                  <div className="flex justify-between items-start mb-6">
                    <div>
                      <h3 className="text-xl font-bold text-gray-900">{p.name}</h3>
                      <p className="text-sm text-gray-500 mt-1">For accounting firms</p>
                    </div>
                    <div className="text-right">
                      <div className="text-3xl font-extrabold text-gray-900">{p.price}</div>
                      <div className="text-sm text-gray-500">{p.period}</div>
                    </div>
                  </div>

                  <div className="h-px w-full bg-gray-100 mb-6"></div>

                  <ul className="space-y-4 mb-8">
                    {p.bullets.map((b, j) => (
                      <li key={j} className="flex items-start text-sm text-gray-600">
                        <div className="w-6 h-6 rounded-full bg-green-50 text-green-600 flex items-center justify-center mr-3 flex-shrink-0">
                          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                          </svg>
                        </div>
                        <span className="pt-0.5">{b}</span>
                      </li>
                    ))}
                  </ul>
                </div>

                <div className="p-8 pt-0 mt-auto">
                  <Link
                    href={`/${locale}/contact`}
                    className={`block w-full py-4 px-6 rounded-xl text-center font-bold transition-all ${p.popular
                      ? 'bg-green-600 text-white hover:bg-green-700 shadow-lg hover:shadow-green-500/30'
                      : 'bg-gray-50 text-gray-900 hover:bg-gray-100 border border-gray-200'
                      }`}
                  >
                    {ctaPartner}
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>



      <ComparisonTable t={t} />

      <FAQ t={t} />
    </main >
  )
}
