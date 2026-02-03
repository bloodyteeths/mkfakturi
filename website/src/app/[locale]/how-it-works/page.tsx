import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { getDictionary } from '@/i18n/dictionaries'

export default async function HowItWorksPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = await getDictionary(locale)

  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-6 text-3xl font-bold" style={{color:'var(--color-primary)'}}>
          {t.how.title}
        </h1>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          {t.how.steps.map((s, i) => (
            <div key={i} className="card">
              <h3 className="mb-1 text-lg font-semibold">{s.title}</h3>
              <p className="text-sm text-gray-600">{s.body}</p>
            </div>
          ))}
        </div>
      </div>
    </main>
  )
}

