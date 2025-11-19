import { Dictionary } from '@/i18n/dictionaries'

export default function FAQ({ t }: { t: Dictionary }) {
  if (!t.faq) return null;

  return (
    <section className="section bg-slate-50">
      <div className="container max-w-4xl">
        <div className="text-center mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4 text-gray-900">
            {t.faq.title}
          </h2>
          <p className="text-lg text-gray-600">Common questions about Facturino.</p>
        </div>

        <div className="space-y-4">
          {t.faq.items.map((item, i) => (
            <details key={i} className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100">
              <summary className="flex items-center justify-between p-6 cursor-pointer list-none">
                <h3 className="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors pr-8">
                  {item.q}
                </h3>
                <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
                  <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                  </svg>
                </span>
              </summary>
              <div className="px-6 pb-6 text-gray-600 leading-relaxed animate-[fadeIn_0.3s_ease-out]">
                {item.a}
              </div>
            </details>
          ))}
        </div>
      </div>
    </section>
  )
}
