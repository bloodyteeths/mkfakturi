import { Dictionary } from '@/i18n/dictionaries'

export default function HowItWorks({ t }: { t: Dictionary }) {
  return (
    <section className="section bg-white overflow-hidden">
      <div className="container">
        <div className="text-center max-w-3xl mx-auto mb-20">
          <span className="text-indigo-600 font-semibold tracking-wider uppercase text-sm">Process</span>
          <h2 className="text-3xl md:text-4xl font-bold mt-2 mb-4 text-gray-900">
            {t.how.title}
          </h2>
          <p className="text-lg text-gray-600">Get started in minutes, not days.</p>
        </div>

        <div className="relative">
          {/* Connecting Line (Desktop) */}
          <div className="hidden md:block absolute top-12 left-0 w-full h-0.5 bg-gray-100">
            <div className="absolute top-0 left-0 h-full bg-gradient-to-r from-indigo-500 to-cyan-500 w-2/3 opacity-30"></div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-12">
            {t.how.steps.map((step, i) => (
              <div key={i} className="relative group">
                <div className="flex flex-col items-center text-center">
                  <div className="relative z-10 w-24 h-24 mb-8 rounded-full bg-white border-4 border-indigo-50 shadow-lg flex items-center justify-center group-hover:border-indigo-100 group-hover:scale-110 transition-all duration-300">
                    <span className="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-br from-indigo-600 to-cyan-500">
                      {i + 1}
                    </span>
                  </div>

                  <h3 className="text-xl font-bold text-gray-900 mb-4">{step.title.split('. ')[1] || step.title}</h3>
                  <p className="text-gray-600 leading-relaxed">
                    {step.body}
                  </p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
