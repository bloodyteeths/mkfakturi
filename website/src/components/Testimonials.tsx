import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

export default function Testimonials({ t }: { t: Dictionary }) {
  // Fallback avatars
  const avatars = [
    "/assets/images/avatar_woman_professional_1763567113575.png",
    "/assets/images/avatar_man_business_1763567119792.png"
  ]

  return (
    <section className="section bg-slate-900 text-white overflow-hidden relative">
      {/* Background glow */}
      <div className="absolute top-0 left-1/2 -translate-x-1/2 w-full h-full max-w-7xl pointer-events-none">
        <div className="absolute top-1/4 left-1/4 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div className="absolute bottom-1/4 right-1/4 w-96 h-96 bg-cyan-600/20 rounded-full blur-3xl"></div>
      </div>

      <div className="container relative z-10">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4">
            {t.testimonials?.title || "Loved by businesses"}
          </h2>
          <p className="text-indigo-200 text-lg">Join hundreds of satisfied accountants and business owners.</p>
        </div>

        <div className="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
          {t.testimonials?.items.map((item, i) => (
            <div key={i} className="bg-white/5 backdrop-blur-lg border border-white/10 p-8 rounded-2xl hover:bg-white/10 transition-colors">
              <div className="flex gap-1 text-yellow-400 mb-6">
                {[...Array(5)].map((_, j) => (
                  <svg key={j} className="w-5 h-5 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                ))}
              </div>

              <blockquote className="text-lg font-medium leading-relaxed mb-6 text-gray-100">
                &quot;{item.quote}&quot;
              </blockquote>

              <div className="flex items-center gap-4">
                <div className="relative w-12 h-12 rounded-full overflow-hidden border-2 border-indigo-500/50">
                  <Image
                    src={avatars[i % avatars.length]}
                    alt={item.author}
                    fill
                    className="object-cover"
                  />
                </div>
                <div>
                  <div className="font-bold">{item.author.split(',')[0]}</div>
                  <div className="text-sm text-indigo-300">{item.author.split(',')[1]}</div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
