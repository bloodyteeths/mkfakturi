import { Dictionary } from '@/i18n/dictionaries'

export default function Testimonials({ t }: { t: Dictionary }) {
  if (!t.testimonials) return null
  const data = t.testimonials
  return (
    <section className="section">
      <div className="container">
        <h2 className="mb-6 text-2xl font-bold md:text-3xl" style={{color:'var(--color-primary)'}}>
          {data.title}
        </h2>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {data.items.map((it, i) => (
            <div key={i} className="card">
              <p className="mb-2 text-gray-800">“{it.quote}”</p>
              <p className="text-sm text-gray-500">— {it.author}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

