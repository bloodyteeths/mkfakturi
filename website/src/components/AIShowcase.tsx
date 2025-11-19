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

                    <div className="space-y-8 order-1 lg:order-2">
                        {ai.features.map((feature, index) => (
                            <div key={index} className="flex gap-4 group">
                                <div className="flex-shrink-0 w-12 h-12 rounded-xl bg-white shadow-sm border border-gray-100 flex items-center justify-center group-hover:scale-110 group-hover:shadow-md transition-all duration-300">
                                    <Image
                                        src="/assets/images/icon_ai_automation_1763567058570.png"
                                        alt="AI Icon"
                                        width={32}
                                        height={32}
                                        className="w-8 h-8"
                                    />
                                </div>
                                <div>
                                    <h3 className="text-xl font-semibold text-gray-900 mb-2 group-hover:text-indigo-600 transition-colors">
                                        {feature.title}
                                    </h3>
                                    <p className="text-gray-600 leading-relaxed">
                                        {feature.desc}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    )
}
