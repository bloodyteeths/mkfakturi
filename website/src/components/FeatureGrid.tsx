import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

function IconEFaktura() {
  return (
    <svg className="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15a2.25 2.25 0 012.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
    </svg>
  )
}

function IconBank() {
  return (
    <svg className="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
    </svg>
  )
}

function IconScan() {
  return (
    <svg className="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M7.5 3.75H6A2.25 2.25 0 003.75 6v1.5M16.5 3.75H18A2.25 2.25 0 0120.25 6v1.5m0 9V18A2.25 2.25 0 0118 20.25h-1.5m-9 0H6A2.25 2.25 0 013.75 18v-1.5M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>
  )
}

function IconUsers() {
  return (
    <svg className="w-7 h-7 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
    </svg>
  )
}

const featureIcons = [IconEFaktura, IconBank, IconScan, IconUsers]

export default function FeatureGrid({ t }: { t: Dictionary }) {
    const fg = t.featureGrid

    return (
        <section className="section bg-white">
            <div className="container">
                <div className="text-center max-w-3xl mx-auto mb-6 md:mb-12">
                    <h2 className="text-2xl md:text-3xl font-bold mb-3 md:mb-4 text-gray-900">{fg.title}</h2>
                    <p className="text-lg text-gray-600">{fg.subtitle}</p>
                </div>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-12">
                    {fg.features.map((f, i) => {
                        const Icon = featureIcons[i] || IconScan
                        return (
                            <div key={i} className="group p-4 md:p-6 rounded-xl md:rounded-2xl bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                                <div className="hidden md:flex w-12 h-12 mb-5 rounded-xl bg-indigo-50 items-center justify-center group-hover:bg-indigo-100 transition-colors">
                                    <Icon />
                                </div>
                                <h3 className="text-base md:text-lg font-bold text-gray-900 mb-2">{f.title}</h3>
                                <p className="text-gray-600 leading-relaxed text-sm">{f.desc}</p>
                            </div>
                        )
                    })}
                </div>

                {/* Product screenshot showcase — hidden on mobile to save scroll */}
                <div className="hidden md:block browser-frame max-w-4xl mx-auto">
                    <div className="browser-frame-bar">
                        <div className="browser-frame-dot"></div>
                        <div className="browser-frame-dot"></div>
                        <div className="browser-frame-dot"></div>
                    </div>
                    <Image
                        src="/assets/screenshots/invoices.png"
                        alt="Facturino invoice management — create, send, and track invoices"
                        width={1400}
                        height={900}
                        sizes="(max-width: 768px) 100vw, 896px"
                        className="w-full h-auto"
                    />
                </div>
            </div>
        </section>
    )
}
