import Image from 'next/image'

interface PageHeroProps {
  title: string
  subtitle?: string
  image: string
  alt: string
  cta?: { label: string; href: string }
  badge?: string
}

export default function PageHero({ title, subtitle, image, alt, cta, badge }: PageHeroProps) {
  return (
    <section className="relative overflow-hidden pt-24 pb-16 md:pt-32 md:pb-20">
      {/* Background image with overlay */}
      <div className="absolute inset-0">
        <Image
          src={image}
          alt={alt}
          fill
          priority
          sizes="100vw"
          className="object-cover"
        />
        <div className="absolute inset-0 bg-gradient-to-r from-gray-900/80 via-gray-900/60 to-gray-900/40" />
      </div>

      <div className="container relative z-10 max-w-4xl text-center">
        {badge && (
          <span className="inline-block mb-4 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-sm text-sm font-semibold text-indigo-300 border border-white/20">
            {badge}
          </span>
        )}
        <h1 className="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-4">
          {title}
        </h1>
        {subtitle && (
          <p className="text-lg md:text-xl text-gray-200 max-w-2xl mx-auto mb-8 leading-relaxed">
            {subtitle}
          </p>
        )}
        {cta && (
          <a
            href={cta.href}
            className="inline-flex items-center gap-2 bg-indigo-600 text-white px-6 py-3 rounded-lg text-base font-semibold hover:bg-indigo-700 transition-colors"
          >
            {cta.label}
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
          </a>
        )}
      </div>
    </section>
  )
}
