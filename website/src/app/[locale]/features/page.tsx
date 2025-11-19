import { getDictionary } from '@/i18n/dictionaries'
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'

// Force dynamic rendering to ensure dictionary is fetched on each navigation
export const dynamic = 'force-dynamic'

export default async function FeaturesPage({ params }: { params: Promise<{ locale: string }> }) {
  const { locale: localeParam } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = await getDictionary(locale)
  const f = t.featuresPage!

  return (
    <main className="section">
      <div className="container">
        <h1 className="mb-2 text-3xl font-bold" style={{ color: 'var(--color-primary)' }}>
          {f.heroTitle}
        </h1>
        <p className="mb-8 max-w-3xl text-[color:var(--color-muted)]">{t.hero.onlyPlatform}</p>
        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
          {f.groups.map((g, i) => (
            <div key={i} className="card">
              <h2 className="mb-2 text-xl font-semibold">{g.title}</h2>
              <ul className="list-disc space-y-1 pl-5 text-sm text-gray-700">
                {g.items.map((it, idx) => (
                  <li key={idx}>{it}</li>
                ))}
              </ul>
            </div>
          ))}
        </div>
      </div>
    </main>
  )
}

