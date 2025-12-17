import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

const featureIcons = [
    "/assets/images/icon_e_faktura_1763567066316.png",
    "/assets/images/icon_banking_1763567076635.png",
    "/assets/images/icon_security_1763567084197.png",
    "/assets/images/icon_ai_automation_1763567058570.png"
]

export default function FeatureGrid({ t }: { t: Dictionary }) {
    const fg = t.featureGrid

    return (
        <section className="section bg-white">
            <div className="container">
                <div className="text-center max-w-3xl mx-auto mb-16">
                    <h2 className="text-3xl font-bold mb-4 text-gray-900">{fg.title}</h2>
                    <p className="text-lg text-gray-600">{fg.subtitle}</p>
                </div>

                <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6">
                    {fg.features.map((f, i) => (
                        <div key={i} className="group p-3 md:p-6 rounded-xl md:rounded-2xl bg-slate-50 border border-slate-100 hover:bg-white hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                            <div className="hidden md:flex w-14 h-14 mb-6 rounded-xl bg-white shadow-sm items-center justify-center group-hover:scale-110 transition-transform">
                                <Image
                                    src={featureIcons[i]}
                                    alt={f.title}
                                    width={40}
                                    height={40}
                                    className="w-10 h-10"
                                />
                            </div>
                            <h3 className="text-base md:text-xl font-bold text-gray-900 mb-2 md:mb-3">{f.title}</h3>
                            <p className="text-gray-600 leading-relaxed text-sm">{f.desc}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    )
}
