import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

const aiIcons = [
  // 1. Chat bubble
  (
    <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
    </svg>
  ),
  // 2. Alert/shield
  (
    <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
    </svg>
  ),
  // 3. Chart trending up
  (
    <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
    </svg>
  ),
  // 4. Light bulb
  (
    <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" />
    </svg>
  ),
  // 5. Pencil/text (create)
  (
    <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
    </svg>
  ),
  // 6. Document scan
  (
    <svg className="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
    </svg>
  ),
]

export default function AIShowcase({ t }: { t: Dictionary }) {
    const ai = t.aiSection

    return (
        <section className="section bg-slate-50 overflow-hidden">
            <div className="container">
                <div className="text-center max-w-3xl mx-auto mb-12">
                    <span className="text-indigo-600 font-semibold tracking-wider uppercase text-sm">{ai.badge}</span>
                    <h2 className="text-3xl md:text-4xl font-bold mt-2 mb-4 text-gray-900">
                        {ai.title}
                    </h2>
                    <p className="text-lg text-gray-600">
                        {ai.subtitle}
                    </p>
                </div>

                <div className="grid lg:grid-cols-2 gap-10 items-center">
                    <div className="relative order-2 lg:order-1">
                        <div className="browser-frame">
                            <div className="browser-frame-bar">
                                <div className="browser-frame-dot"></div>
                                <div className="browser-frame-dot"></div>
                                <div className="browser-frame-dot"></div>
                            </div>
                            <Image
                                src="/assets/screenshots/ai-chat.png"
                                alt="Facturino AI financial advisor — chat interface with insights"
                                width={1400}
                                height={900}
                                sizes="(max-width: 1024px) 100vw, 50vw"
                                className="w-full h-auto"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-3 md:gap-4 order-1 lg:order-2">
                        {ai.features.map((feature, index) => (
                            <div key={index} className="group p-3 md:p-4 rounded-xl bg-white shadow-sm border border-gray-100 hover:shadow-md transition-all">
                                <div className="hidden md:flex w-10 h-10 rounded-lg bg-indigo-50 items-center justify-center mb-3 group-hover:bg-indigo-100 transition-colors">
                                    {aiIcons[index] || aiIcons[0]}
                                </div>
                                <h3 className="text-base md:text-lg font-semibold text-gray-900 mb-1 group-hover:text-indigo-600 transition-colors">
                                    {feature.title}
                                </h3>
                                <p className="text-sm text-gray-600 leading-relaxed">
                                    {feature.desc}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}
