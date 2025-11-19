import { Dictionary } from '@/i18n/dictionaries'

export default function ComparisonTable({ t }: { t: Dictionary }) {
    if (!t.pricingPage?.comparisonTable) return null
    const { title, plans, rows } = t.pricingPage.comparisonTable

    return (
        <section className="py-20 bg-white">
            <div className="container">
                <h2 className="text-3xl font-bold text-center mb-12 text-gray-900">{title}</h2>
                <div className="overflow-x-auto rounded-2xl border border-gray-200 shadow-sm">
                    <table className="w-full min-w-[800px]">
                        <thead>
                            <tr className="bg-gray-50">
                                <th className="text-left p-6 font-bold text-gray-900 sticky left-0 bg-gray-50 z-10">Feature</th>
                                {plans.map((plan, i) => (
                                    <th key={i} className="p-6 text-center font-bold text-gray-900">
                                        {plan}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {rows.map((row, i) => (
                                <tr key={i} className="hover:bg-gray-50/50 transition-colors">
                                    <td className="p-4 pl-6 font-medium text-gray-900 sticky left-0 bg-white z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">{row.feature}</td>
                                    {row.values.map((val, j) => (
                                        <td key={j} className="p-4 text-center text-gray-600">
                                            {typeof val === 'boolean' ? (
                                                val ? (
                                                    <div className="flex justify-center">
                                                        <div className="w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center">
                                                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-300">—</span>
                                                )
                                            ) : (
                                                <span className="font-medium text-gray-900">{val}</span>
                                            )}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    )
}
