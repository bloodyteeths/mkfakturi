'use client'

import { useState } from 'react'
import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function MobileMenu({ t, locale }: { t: Dictionary; locale: Locale }) {
    const [isOpen, setIsOpen] = useState(false)

    return (
        <div className="md:hidden">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="p-2 text-gray-600 hover:text-gray-900 focus:outline-none"
                aria-label="Toggle menu"
            >
                {isOpen ? (
                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                    </svg>
                ) : (
                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                )}
            </button>

            {isOpen && (
                <div className="absolute top-full left-0 w-full bg-white border-b shadow-lg py-4 px-4 flex flex-col gap-4 z-50">
                    <Link
                        href={`/${locale}/features`}
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.features}
                    </Link>
                    {t.nav.forAccountants && (
                        <Link
                            href={`/${locale}/for-accountants`}
                            className="text-gray-700 hover:text-indigo-600 font-medium"
                            onClick={() => setIsOpen(false)}
                        >
                            {t.nav.forAccountants}
                        </Link>
                    )}
                    <Link
                        href={`/${locale}/how-it-works`}
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.how}
                    </Link>
                    <Link
                        href={`/${locale}/e-faktura`}
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.efaktura}
                    </Link>
                    <Link
                        href={`/${locale}/pricing`}
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.pricing}
                    </Link>
                    <Link
                        href={`/${locale}/security`}
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.security}
                    </Link>
                    <Link
                        href={`/${locale}/contact`}
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.contact}
                    </Link>
                    <div className="h-px bg-gray-100 my-2"></div>
                    <Link
                        href="https://app.facturino.mk/admin"
                        className="text-gray-700 hover:text-indigo-600 font-medium"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.login}
                    </Link>
                    <Link
                        href={`/${locale}/pricing`}
                        className="btn-accent text-center justify-center"
                        onClick={() => setIsOpen(false)}
                    >
                        {t.nav.start}
                    </Link>
                </div>
            )}
        </div>
    )
}
