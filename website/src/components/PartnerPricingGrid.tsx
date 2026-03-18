'use client'

import { useState } from 'react'

interface PartnerPlan {
  name: string
  price: string
  priceYearly?: string
  period: string
  periodYearly?: string
  bullets: string[]
  popular: boolean
}

interface PartnerPricingGridProps {
  plans: PartnerPlan[]
  popularBadge: string
  ctaPartner: string
  includesPrevious: string
  billingToggleMonthly: string
  billingToggleYearly: string
  billingYearlySave: string
}

export default function PartnerPricingGrid({
  plans,
  popularBadge,
  ctaPartner,
  includesPrevious,
  billingToggleMonthly,
  billingToggleYearly,
  billingYearlySave,
}: PartnerPricingGridProps) {
  const [isYearly, setIsYearly] = useState(false)

  return (
    <>
      {/* Monthly/Yearly Toggle */}
      <div className="flex items-center justify-center gap-3 mb-8">
        <span className={`text-sm font-medium ${!isYearly ? 'text-gray-900' : 'text-gray-400'}`}>
          {billingToggleMonthly}
        </span>
        <button
          onClick={() => setIsYearly(!isYearly)}
          className={`relative w-14 h-7 rounded-full transition-colors duration-300 ${isYearly ? 'bg-green-500' : 'bg-gray-300'}`}
          aria-label="Toggle yearly billing"
        >
          <span
            className={`absolute top-0.5 left-0.5 w-6 h-6 bg-white rounded-full shadow transition-transform duration-300 ${isYearly ? 'translate-x-7' : 'translate-x-0'}`}
          />
        </button>
        <span className={`text-sm font-medium ${isYearly ? 'text-gray-900' : 'text-gray-400'}`}>
          {billingToggleYearly}
        </span>
        {isYearly && (
          <span className="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">
            {billingYearlySave}
          </span>
        )}
      </div>

      <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        {plans.map((p, i) => {
          const previousPlanName = i > 0 ? plans[i - 1].name : null
          const bullets = previousPlanName
            ? [includesPrevious.replace('{plan}', previousPlanName), ...p.bullets]
            : p.bullets

          const displayPrice = isYearly && p.priceYearly ? p.priceYearly : p.price
          const displayPeriod = isYearly && p.periodYearly ? p.periodYearly : p.period

          return (
            <div key={i} className={`relative flex flex-col bg-white rounded-2xl shadow-sm border transition-all duration-300 hover:shadow-xl hover:-translate-y-1 ${p.popular ? 'border-green-500 ring-1 ring-green-500 z-10 scale-105' : 'border-gray-200'}`}>
              {p.popular && (
                <div className="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-gradient-to-r from-green-600 to-emerald-500 px-4 py-1 text-xs font-bold text-white whitespace-nowrap shadow-md">
                  {popularBadge}
                </div>
              )}

              <div className="p-6 flex-grow">
                <h3 className="mb-4 text-lg font-bold text-gray-900">{p.name}</h3>
                <div className="mb-6">
                  <span className="text-4xl font-extrabold text-gray-900">{displayPrice}</span>
                  <span className="text-sm text-gray-500 font-medium">{displayPeriod}</span>
                </div>

                <ul className="space-y-4 mb-8">
                  {bullets.map((b, j) => (
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
                <a
                  href="https://app.facturino.mk/partner/signup"
                  className={`block w-full py-3 px-4 rounded-xl text-center font-bold transition-all ${p.popular
                    ? 'bg-green-600 text-white hover:bg-green-700 shadow-lg hover:shadow-green-500/30'
                    : 'bg-gray-50 text-gray-900 hover:bg-gray-100 border border-gray-200'
                    }`}
                >
                  {ctaPartner}
                </a>
              </div>
            </div>
          )
        })}
      </div>
    </>
  )
}
