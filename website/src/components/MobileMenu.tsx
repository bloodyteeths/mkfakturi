'use client'

import { useState, useEffect, useRef } from 'react'
import Link from 'next/link'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function MobileMenu({ t, locale }: { t: Dictionary; locale: Locale }) {
    const [isOpen, setIsOpen] = useState(false)
    const menuRef = useRef<HTMLDivElement>(null)

    // Close on outside click
    useEffect(() => {
        if (!isOpen) return
        function handleClick(e: MouseEvent) {
            if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
                setIsOpen(false)
            }
        }
        document.addEventListener('click', handleClick, true)
        return () => document.removeEventListener('click', handleClick, true)
    }, [isOpen])

    const linkClass = "text-gray-700 hover:text-indigo-600 hover:bg-indigo-50/60 font-medium text-[15px] py-2 px-3 rounded-lg transition-colors block"

    return (
        <div className="lg:hidden relative" ref={menuRef}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 rounded-lg"
                aria-label="Toggle menu"
                aria-expanded={isOpen}
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
                <div
                    className="absolute right-0 top-full mt-2 w-64 bg-white rounded-xl shadow-xl border border-gray-100 z-50 max-h-[calc(100dvh-5rem)] overflow-y-auto"
                    role="dialog"
                    aria-modal="true"
                >
                    <nav className="py-2 px-2" aria-label="Mobile navigation">
                        <Link href={`/${locale}/features`} className={linkClass} onClick={() => setIsOpen(false)}>
                            {t.nav.features}
                        </Link>
                        {t.nav.showcase && (
                            <Link href={`/${locale}/pregled`} className={linkClass} onClick={() => setIsOpen(false)}>
                                {t.nav.showcase}
                            </Link>
                        )}
                        {t.nav.forAccountants && (
                            <Link href={`/${locale}/for-accountants`} className={linkClass} onClick={() => setIsOpen(false)}>
                                {t.nav.forAccountants}
                            </Link>
                        )}
                        <Link href={`/${locale}/how-it-works`} className={linkClass} onClick={() => setIsOpen(false)}>
                            {t.nav.how}
                        </Link>
                        <Link href={`/${locale}/e-faktura`} className={linkClass} onClick={() => setIsOpen(false)}>
                            {t.nav.efaktura}
                        </Link>
                        {t.nav.pos && (
                            <Link href={`/${locale}/pos`} className={linkClass} onClick={() => setIsOpen(false)}>
                                {t.nav.pos}
                            </Link>
                        )}
                        {t.nav.integrations && (
                            <Link href={`/${locale}/integrations`} className={linkClass} onClick={() => setIsOpen(false)}>
                                {t.nav.integrations}
                            </Link>
                        )}
                        <Link href={`/${locale}/pricing`} className={linkClass} onClick={() => setIsOpen(false)}>
                            {t.nav.pricing}
                        </Link>
                        <Link href={`/${locale}/security`} className={linkClass} onClick={() => setIsOpen(false)}>
                            {t.nav.security}
                        </Link>
                        <Link href={`/${locale}/contact`} className={linkClass} onClick={() => setIsOpen(false)}>
                            {t.nav.contact}
                        </Link>
                        {t.nav.blog && (
                            <Link href={`/${locale}/blog`} className={linkClass} onClick={() => setIsOpen(false)}>
                                {t.nav.blog}
                            </Link>
                        )}
                        {t.nav.tools && (
                            <Link href={`/${locale}/alati`} className={linkClass} onClick={() => setIsOpen(false)}>
                                {t.nav.tools}
                            </Link>
                        )}
                    </nav>
                    <div className="border-t border-gray-100 px-3 py-2.5 space-y-1.5">
                        <Link
                            href="https://app.facturino.mk/admin"
                            className="block text-center text-gray-600 hover:text-indigo-600 font-medium text-sm py-1.5"
                            onClick={() => setIsOpen(false)}
                        >
                            {t.nav.login}
                        </Link>
                        <a
                            href="https://app.facturino.mk/signup"
                            className="block w-full text-center bg-indigo-600 text-white py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition-colors"
                            onClick={() => setIsOpen(false)}
                        >
                            {t.nav.start}
                        </a>
                    </div>
                </div>
            )}
        </div>
    )
}
