import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

export default function Partners({ t }: { t: Dictionary }) {
  if (!t.partners) return null
  return (
    <section className="py-12 bg-gray-50/50">
      <div className="container">
        <h3 className="mb-8 text-center text-sm font-semibold uppercase tracking-wide text-gray-500">
          {t.partners.title}
        </h3>
        <div className="flex items-center justify-center">
          <div className="relative max-w-4xl">
            <Image
              src="/assets/images/bank_logos.png"
              alt="Macedonian Bank Logos"
              width={800}
              height={120}
              className="w-full h-auto opacity-60 hover:opacity-80 transition-opacity"
            />
          </div>
        </div>
      </div>
    </section>
  )
}

