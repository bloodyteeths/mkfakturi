import Link from 'next/link'
import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function Hero({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <section className="section relative overflow-hidden overflow-x-hidden pt-24 md:pt-32 pb-16 md:pb-20 lg:pt-40 lg:pb-32">
      <div className="container relative z-10 grid items-center gap-12 lg:grid-cols-2 px-4 sm:px-6">
        <div className="space-y-8 text-center lg:text-left animate-[fadeIn_0.8s_ease-out]">
          <h1 className="text-3xl font-extrabold tracking-tight leading-[1.1] sm:text-4xl md:text-5xl lg:text-6xl text-gray-900">
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
            <a href="https://app.facturino.mk/signup" className="btn-primary w-full sm:w-auto group">
              {t.hero.primaryCta}
              <svg className="ml-2 w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </a>
            <Link href={`/${locale}/contact`} className="btn-accent w-full sm:w-auto">
              {t.hero.secondaryCta}
            </Link>
          </div>

          <div className="flex flex-col items-center lg:items-start gap-2 pt-2">
            <p className="text-sm font-semibold text-gray-700">{t.socialProof.stat}</p>
            <div className="flex items-center gap-5 text-xs text-gray-500">
              <span className="flex items-center gap-1.5">
                <svg className="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" />
                </svg>
                {t.socialProof.freeTrial}
              </span>
              <span className="flex items-center gap-1.5">
                <svg className="w-3.5 h-3.5 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2.5} d="M5 13l4 4L19 7" />
                </svg>
                {t.socialProof.noCreditCard}
              </span>
            </div>
          </div>

        </div>

        {/* Hero Visual — Real product screenshot in browser mockup */}
        <div className="relative order-1 lg:order-2 animate-[slideUp_1s_ease-out_0.3s_both]">
          <div className="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-cyan-500/10 rounded-2xl blur-2xl transform scale-95"></div>

          <div className="relative browser-frame">
            <div className="browser-frame-bar">
              <div className="browser-frame-dot"></div>
              <div className="browser-frame-dot"></div>
              <div className="browser-frame-dot"></div>
            </div>
            <Image
              src="/assets/screenshots/dashboard.png"
              alt="Facturino AI accounting dashboard — invoices, reports, and financial overview"
              width={1400}
              height={900}
              priority
              sizes="(max-width: 768px) 100vw, 700px"
              className="w-full h-auto"
            />
          </div>
        </div>
      </div>
    </section>
  )
}
