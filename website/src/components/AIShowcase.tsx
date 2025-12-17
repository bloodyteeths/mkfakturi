import Image from 'next/image'
import { Dictionary } from '@/i18n/dictionaries'

export default function AIShowcase({ t }: { t: Dictionary }) {
    const ai = t.aiSection

    return (
        <section className="section bg-slate-50 overflow-hidden">
            <div className="container">
                <div className="text-center max-w-3xl mx-auto mb-16">
                    <span className="text-indigo-600 font-semibold tracking-wider uppercase text-sm">{ai.badge}</span>
                    <h2 className="text-3xl md:text-4xl font-bold mt-2 mb-4 text-gray-900">
                        {ai.title}
                    </h2>
                    <p className="text-lg text-gray-600">
                        {ai.subtitle}
                    </p>
                </div>

                <div className="grid lg:grid-cols-2 gap-12 items-center">
                    <div className="relative order-2 lg:order-1">
                        <div className="absolute inset-0 bg-gradient-to-r from-indigo-500/10 to-cyan-500/10 rounded-3xl transform rotate-3 scale-105 blur-xl"></div>
                        <div className="relative bg-white rounded-3xl shadow-xl border border-gray-100 p-2 overflow-hidden">
                            <Image
                                src="/assets/images/ai_workflow_diagram_1763567032376.png"
                                alt="AI Workflow Diagram"
                                width={600}
                                height={400}
                                className="w-full h-auto rounded-2xl"
                            />
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4 md:gap-6 order-1 lg:order-2">
                        {ai.features.map((feature, index) => (
                            <div key={index} className="group p-3 md:p-4 rounded-xl bg-white shadow-sm border border-gray-100 hover:shadow-md transition-all">
                                <div className="w-10 h-10 md:w-12 md:h-12 rounded-lg bg-indigo-50 flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                    <Image
                                        src="/assets/images/icon_ai_automation_1763567058570.png"
                                        alt="AI Icon"
                                        width={28}
                                        height={28}
                                        className="w-6 h-6 md:w-7 md:h-7"
                                    />
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
