import { Dictionary } from '@/i18n/dictionaries'

const banks = [
  { name: 'Стопанска Банка', abbr: 'SB' },
  { name: 'Комерцијална Банка', abbr: 'KB' },
  { name: 'NLB Банка', abbr: 'NLB' },
  { name: 'Sparkasse Банка', abbr: 'SP' },
  { name: 'Халк Банка', abbr: 'HB' },
  { name: 'Silk Road Bank', abbr: 'SR' },
]

export default function Partners({ t }: { t: Dictionary }) {
  if (!t.partners) return null
  return (
    <section className="py-12 md:py-16 bg-gray-50 border-t border-gray-100">
      <div className="container px-4">
        <div className="text-center mb-8">
          <h3 className="text-sm font-semibold uppercase tracking-wider text-gray-400">
            {t.partners.title}
          </h3>
          {t.partners.subtitle && (
            <p className="mt-1.5 text-xs text-gray-400">
              {t.partners.subtitle}
            </p>
          )}
        </div>
        <div className="flex flex-wrap items-center justify-center gap-4 md:gap-6">
          {banks.map((bank) => (
            <div
              key={bank.abbr}
              className="flex items-center gap-2.5 rounded-xl bg-white px-5 py-3 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all"
            >
              <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 font-bold text-xs flex-shrink-0">
                {bank.abbr}
              </div>
              <span className="text-sm font-medium text-gray-700 whitespace-nowrap">{bank.name}</span>
            </div>
          ))}
        </div>
        <p className="text-center text-xs text-gray-400 mt-6">
          CSV/MT940/PDF Bank Import
        </p>
      </div>
    </section>
  )
}
