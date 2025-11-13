import { Dictionary } from '@/i18n/dictionaries'

export default function Partners({ t }: { t: Dictionary }) {
  if (!t.partners) return null
  return (
    <section className="section">
      <div className="container">
        <h3 className="mb-4 text-center text-sm font-semibold uppercase tracking-wide text-gray-500">
          {t.partners.title}
        </h3>
        <div className="flex flex-wrap items-center justify-center gap-6 text-gray-400">
          {t.partners.logos.map((name, i) => (
            <div key={i} className="rounded-md border bg-white px-4 py-2 text-sm">
              {name}
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

