import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

export default function Partners({ t }: { t: Dictionary }) {
  if (!t.partners) return null
  return (
    <section className="py-10 md:py-14 bg-gray-50">
      <div className="container px-4">
        <div className="text-center mb-6">
          <h3 className="text-sm font-semibold uppercase tracking-wider text-gray-500">
            {t.partners.title}
          </h3>
          {t.partners.subtitle && (
            <p className="mt-1 text-xs text-gray-400">
              {t.partners.subtitle}
            </p>
          )}
        </div>
        <div className="flex items-center justify-center">
          <Image
            src="/assets/images/bank_logos.png"
            alt="Macedonian Bank Logos"
            width={800}
            height={100}
            className="w-auto h-16 md:h-20 opacity-60 hover:opacity-80 transition-opacity"
          />
        </div>
      </div>
    </section>
  )
}

