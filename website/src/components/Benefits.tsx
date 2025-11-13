import { Dictionary } from '@/i18n/dictionaries'

export default function Benefits({ t }: { t: Dictionary }) {
  return (
    <section className="section">
      <div className="container">
        <h2 className="mb-6 text-2xl font-bold md:text-3xl" style={{color:'var(--color-primary)'}}>
          {t.benefits.title}
        </h2>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          {t.benefits.bullets.map((b, i) => (
            <div key={i} className="card text-center font-medium text-gray-800">
              {b}
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}

