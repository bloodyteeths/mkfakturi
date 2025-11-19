import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function CTA({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <section className="py-20 lg:py-32 relative overflow-hidden">
      <div className="absolute inset-0 bg-gradient-to-br from-indigo-600 to-cyan-600"></div>

      {/* Decorative circles */}
      <div className="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none">
        <div className="absolute top-10 left-10 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
        <div className="absolute bottom-10 right-10 w-96 h-96 bg-white/10 rounded-full blur-3xl"></div>
      </div>

      <div className="container relative z-10 text-center text-white">
        <h2 className="text-4xl md:text-5xl font-extrabold mb-6 tracking-tight">
          {t.cta.title}
        </h2>
        <p className="text-xl text-indigo-100 mb-10 max-w-2xl mx-auto">
          {t.cta.sub}
        </p>

        <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
          <Link
            href={`/${locale}/pricing`}
            className="px-8 py-4 bg-white text-indigo-600 rounded-xl font-bold text-lg shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
          >
            {t.cta.button}
          </Link>
          <Link
            href={`/${locale}/contact`}
            className="px-8 py-4 bg-indigo-700/50 text-white border border-white/20 rounded-xl font-bold text-lg hover:bg-indigo-700/70 transition-all duration-300 backdrop-blur-sm"
          >
            Contact Sales
          </Link>
        </div>

        <p className="mt-6 text-sm text-indigo-200 opacity-80">
          No credit card required • 14-day free trial • Cancel anytime
        </p>
      </div>
    </section>
  )
}
