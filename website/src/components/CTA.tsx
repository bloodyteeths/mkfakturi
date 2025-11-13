import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function CTA({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <section className="section">
      <div className="container rounded-2xl bg-[color:var(--color-primary)] px-6 py-10 text-white">
        <div className="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h3 className="text-2xl font-bold md:text-3xl">{t.cta.title}</h3>
            {t.cta.sub && <p className="text-white/80">{t.cta.sub}</p>}
          </div>
          <Link href={`/${locale}/pricing`} className="btn-accent">
            {t.cta.button}
          </Link>
        </div>
      </div>
    </section>
  )
}

