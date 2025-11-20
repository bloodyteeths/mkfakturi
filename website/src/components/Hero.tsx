import Link from 'next/link'
import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function Hero({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <section className="section relative overflow-hidden pt-32 pb-20 lg:pt-40 lg:pb-32">
      {/* Background Elements */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none z-0">
        <div className="absolute top-20 left-10 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
        <div className="absolute top-20 right-10 w-72 h-72 bg-cyan-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
        <div className="absolute -bottom-8 left-20 w-72 h-72 bg-indigo-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>
      </div>

      <div className="container relative z-10 grid items-center gap-12 lg:grid-cols-2">
        <div className="space-y-8 text-center lg:text-left animate-[fadeIn_0.8s_ease-out]">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/80 backdrop-blur-sm border border-indigo-100 px-4 py-1.5 text-sm font-semibold text-indigo-600 shadow-sm hover:shadow-md transition-all cursor-default">
            <span className="relative flex h-2 w-2">
              <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
              <span className="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
            </span>
            {t.hero.claim}
          </div>

          <h1 className="text-5xl font-extrabold tracking-tight leading-[1.1] md:text-6xl lg:text-7xl text-gray-900">
            {t.hero.h1.split('AI')[0]}
            <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-cyan-500">AI</span>
            {t.hero.h1.split('AI')[1]}
          </h1>

          <div className="space-y-4">
            {t.heroTagline && (
              <p className="text-xl font-medium text-gray-900">{t.heroTagline}</p>
            )}
            <p className="text-lg text-gray-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
              {t.hero.sub}
            </p>
          </div>

          <div className="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-4">
            <Link href={`/${locale}/pricing`} className="btn-primary w-full sm:w-auto group">
              {t.hero.primaryCta}
              <svg className="ml-2 w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </Link>
            <Link href={`/${locale}/contact`} className="btn-accent w-full sm:w-auto">
              {t.hero.secondaryCta}
            </Link>
          </div>


        </div>

        {/* Hero Visual */}
        <div className="relative order-1 lg:order-2 animate-[slideUp_1s_ease-out_0.3s_both]">
          {/* Glow effect */}
          <div className="absolute inset-0 bg-gradient-to-br from-indigo-500/20 to-cyan-500/20 rounded-3xl blur-3xl transform scale-95"></div>

          {/* Image container */}
          <div className="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white/50 backdrop-blur-sm">
            <Image
              src="/assets/images/hero_skopje.png"
              alt="Facturino Dashboard"
              width={700}
              height={500}
              priority
              className="w-full h-auto"
            />
          </div>

          {/* Floating elements */}
          <div className="absolute bottom-6 left-6 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-4 max-w-xs animate-float">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                <svg className="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <div>
                <p className="text-xs text-gray-500 font-medium">Status</p>
                <p className="text-sm font-bold text-gray-900">AI активно</p>
              </div>
            </div>
          </div>

          <div className="absolute top-6 right-6 bg-white/80 backdrop-blur-sm rounded-2xl shadow-xl p-4 animate-float animation-delay-2000">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center">
                <svg className="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div>
                <p className="text-xs text-gray-500">e-Faktura</p>
                <p className="text-sm font-bold text-green-600">Готово</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Social Proof */}

    </section>
  )
}
