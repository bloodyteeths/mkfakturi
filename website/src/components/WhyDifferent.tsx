import { Dictionary } from '@/i18n/dictionaries'

export default function WhyDifferent({ t }: { t: Dictionary }) {
  return (
    <section className="section relative overflow-hidden">
      {/* Background decoration */}
      <div className="absolute inset-0 bg-grid-pattern opacity-[0.03] pointer-events-none"></div>

      <div className="container relative z-10">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-6 text-gray-900">
            {t.whyDifferent.title}
          </h2>
          <div className="h-1 w-20 bg-gradient-to-r from-indigo-500 to-cyan-500 mx-auto rounded-full"></div>
        </div>

        <div className="grid grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
          {t.whyDifferent.cards.map((c, i) => (
            <div key={i} className="card group hover:border-indigo-200 p-4 md:p-6">
              <div className="hidden md:flex mb-4 w-10 h-10 rounded-lg bg-indigo-50 items-center justify-center text-indigo-600 font-bold text-lg flex-shrink-0 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                {i + 1}
              </div>
              <h3 className="mb-2 md:mb-3 text-base md:text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{c.title}</h3>
              <p className="text-gray-600 leading-relaxed text-sm">{c.body}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

