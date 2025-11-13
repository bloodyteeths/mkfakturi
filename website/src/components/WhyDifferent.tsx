import { Dictionary } from '@/i18n/dictionaries'

export default function WhyDifferent({ t }: { t: Dictionary }) {
  return (
    <section className="section">
      <div className="container">
        <h2 className="mb-6 text-2xl font-bold md:text-3xl" style={{color:'var(--color-primary)'}}>
          {t.whyDifferent.title}
        </h2>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {t.whyDifferent.cards.map((c, i) => (
            <div key={i} className="card">
              <h3 className="mb-1 text-lg font-semibold">{c.title}</h3>
              <p className="text-sm text-gray-600">{c.body}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

