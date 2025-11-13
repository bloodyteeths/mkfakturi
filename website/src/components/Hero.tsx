import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function Hero({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <section className="section">
      <div className="container grid items-center gap-8 md:grid-cols-2">
        <div className="space-y-5">
          <span className="inline-block rounded-full bg-[color:var(--color-accent)]/15 px-3 py-1 text-xs font-semibold text-[color:var(--color-accent)]">
            {t.hero.claim}
          </span>
          <h1 className="text-3xl font-extrabold leading-snug md:text-5xl" style={{color:'var(--color-primary)'}}>
            {t.hero.h1}
          </h1>
          {t.heroTagline && (
            <p className="text-xl font-semibold text-gray-900">{t.heroTagline}</p>
          )}
          <p className="text-lg text-[color:var(--color-muted)]">{t.hero.sub}</p>
          <p className="text-sm text-gray-600">{t.hero.onlyPlatform}</p>
          <div className="flex flex-wrap gap-3 pt-2">
            <Link href={`/${locale}/pricing`} className="btn-primary">
              {t.hero.primaryCta}
            </Link>
            <Link href={`/${locale}/contact`} className="btn-accent">
              {t.hero.secondaryCta}
            </Link>
          </div>
          <div className="pt-3 text-sm text-gray-600">{t.socialProof.trustedBy}</div>
        </div>
        <div className="order-first h-[320px] rounded-2xl bg-gradient-to-br from-[color:var(--color-primary)] to-[#457B9D] md:order-last">
          {/* Placeholder for product screenshot/video */}
          <div className="flex h-full items-center justify-center text-white/90">Product screenshot/video</div>
        </div>
      </div>
    </section>
  )
}
