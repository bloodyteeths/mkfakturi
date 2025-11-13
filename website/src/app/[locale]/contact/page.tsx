"use client"
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { useMemo } from 'react'

const copy = {
  mk: { h1: 'Контакт', name: 'Име', email: 'Е‑пошта', message: 'Порака', submit: 'Испрати' },
  sq: { h1: 'Kontakti', name: 'Emri', email: 'Email', message: 'Mesazhi', submit: 'Dërgo' },
  tr: { h1: 'İletişim', name: 'Ad', email: 'E‑posta', message: 'Mesaj', submit: 'Gönder' }
} as const

export default function ContactPage({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = useMemo(() => copy[locale], [locale])

  return (
    <main className="section">
      <div className="container max-w-2xl">
        <h1 className="mb-6 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{t.h1}</h1>
        <form className="space-y-3">
          <div>
            <label className="mb-1 block text-sm text-gray-700">{t.name}</label>
            <input className="w-full rounded-md border border-gray-300 bg-white px-3 py-2" />
          </div>
          <div>
            <label className="mb-1 block text-sm text-gray-700">{t.email}</label>
            <input type="email" className="w-full rounded-md border border-gray-300 bg-white px-3 py-2" />
          </div>
          <div>
            <label className="mb-1 block text-sm text-gray-700">{t.message}</label>
            <textarea className="w-full rounded-md border border-gray-300 bg-white px-3 py-2" rows={5} />
          </div>
          <button className="btn-primary" type="button">{t.submit}</button>
        </form>
      </div>
    </main>
  )
}

