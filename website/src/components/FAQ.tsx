import { Dictionary } from '@/i18n/dictionaries'

export default function FAQ({ t }: { t: Dictionary }) {
  if (!t.faq) return null
  const data = t.faq
  return (
    <section className="section">
      <div className="container">
        <h2 className="mb-6 text-2xl font-bold md:text-3xl" style={{color:'var(--color-primary)'}}>
          {data.title}
        </h2>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {data.items.map((it, i) => (
            <div key={i} className="card">
              <h3 className="mb-1 text-lg font-semibold">{it.q}</h3>
              <p className="text-sm text-gray-700">{it.a}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

