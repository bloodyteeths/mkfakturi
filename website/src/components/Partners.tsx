import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

export default function Partners({ t }: { t: Dictionary }) {
  if (!t.partners) return null
  return (
    <section className="py-6 md:py-8 bg-gray-50/50">
      <div className="container px-4">
        <h3 className="mb-4 text-center text-xs font-semibold uppercase tracking-wide text-gray-400">
          {t.partners.title}
        </h3>
        <div className="flex items-center justify-center">
          <Image
            src="/assets/images/bank_logos.png"
            alt="Macedonian Bank Logos"
            width={600}
            height={80}
            className="w-auto h-12 md:h-16 opacity-50 hover:opacity-70 transition-opacity"
          />
        </div>
      </div>
    </section>
  )
}

