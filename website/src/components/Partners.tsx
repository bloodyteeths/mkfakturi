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
    <section className="py-6 md:py-8 bg-gray-50 border-t border-gray-100">
      <div className="container px-4">
        <p className="text-center text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">
          {t.partners.title}
        </p>
        <div className="flex flex-wrap items-center justify-center gap-3 md:gap-4">
          {banks.map((bank) => (
            <div
              key={bank.abbr}
              className="flex items-center gap-2 rounded-lg bg-white px-4 py-2 shadow-sm border border-gray-100"
            >
              <div className="flex items-center justify-center w-7 h-7 rounded-md bg-indigo-50 text-indigo-600 font-bold text-xs flex-shrink-0">
                {bank.abbr}
              </div>
              <span className="text-sm font-medium text-gray-600 whitespace-nowrap">{bank.name}</span>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
