import { Dictionary } from '@/i18n/dictionaries'

export default function HowItWorks({ t }: { t: Dictionary }) {
  return (
    <section className="section">
      <div className="container">
        <h2 className="mb-6 text-2xl font-bold md:text-3xl" style={{color:'var(--color-primary)'}}>
          {t.how.title}
        </h2>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          {t.how.steps.map((s, i) => (
            <div key={i} className="card">
              <h3 className="mb-1 text-lg font-semibold">{s.title}</h3>
              <p className="text-sm text-gray-600">{s.body}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

