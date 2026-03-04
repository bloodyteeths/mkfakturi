import Image from 'next/image'

type FeatureAccordionProps = {
  icon: string
  title: string
  description: string
  bullets: readonly string[]
  screenshots: readonly { src: string; alt: string }[]
  defaultOpen?: boolean
  ctaText: string
  ctaHref: string
}

export default function FeatureAccordion({
  icon,
  title,
  description,
  bullets,
  screenshots,
  defaultOpen,
  ctaText,
  ctaHref,
}: FeatureAccordionProps) {
  return (
    <details
      className="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md open:shadow-md open:ring-1 open:ring-indigo-100"
      {...(defaultOpen ? { open: true } : {})}
    >
      <summary className="flex items-center justify-between p-6 cursor-pointer list-none">
        <div className="flex items-center gap-3">
          <span className="text-2xl flex-shrink-0">{icon}</span>
          <h3 className="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
            {title}
          </h3>
        </div>
        <span className="flex-shrink-0 w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition-transform duration-300 group-open:rotate-180 group-open:bg-indigo-600 group-open:text-white">
          <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
          </svg>
        </span>
      </summary>

      <div className="px-6 pb-6 animate-[fadeIn_0.3s_ease-out]">
        <p className="text-gray-600 mb-5">{description}</p>

        {screenshots.map((img, i) => (
          <div key={i} className="mb-4 rounded-xl overflow-hidden border border-gray-200 shadow-sm">
            <Image
              src={img.src}
              alt={img.alt}
              width={1200}
              height={750}
              className="w-full h-auto"
              loading="lazy"
              sizes="(max-width: 768px) 100vw, (max-width: 1200px) 80vw, 900px"
            />
          </div>
        ))}

        <ul className="space-y-2 mb-6 mt-4">
          {bullets.map((b, i) => (
            <li key={i} className="flex items-start gap-2.5">
              <svg className="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
              </svg>
              <span className="text-gray-700 text-sm">{b}</span>
            </li>
          ))}
        </ul>

        <a
          href={ctaHref}
          className="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white rounded-lg transition-colors"
          style={{ background: 'var(--color-primary)' }}
        >
          {ctaText}
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7l5 5m0 0l-5 5m5-5H6" />
          </svg>
        </a>
      </div>
    </details>
  )
}
