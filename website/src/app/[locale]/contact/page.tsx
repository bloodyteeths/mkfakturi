"use client"
import { defaultLocale, isLocale, Locale } from '@/i18n/locales'
import { useMemo, useState } from 'react'

const copy = {
  mk: {
    h1: 'Контакт',
    name: 'Име',
    email: 'Е‑пошта',
    message: 'Порака',
    submit: 'Испрати',
    sending: 'Се испраќа...',
    success: 'Пораката е испратена успешно!',
    error: 'Грешка при испраќање. Обидете се повторно.',
  },
  sq: {
    h1: 'Kontakti',
    name: 'Emri',
    email: 'Email',
    message: 'Mesazhi',
    submit: 'Dërgo',
    sending: 'Duke dërguar...',
    success: 'Mesazhi u dërgua me sukses!',
    error: 'Gabim gjatë dërgimit. Provoni përsëri.',
  },
  tr: {
    h1: 'İletişim',
    name: 'Ad',
    email: 'E‑posta',
    message: 'Mesaj',
    submit: 'Gönder',
    sending: 'Gönderiliyor...',
    success: 'Mesaj başarıyla gönderildi!',
    error: 'Gönderim hatası. Tekrar deneyin.',
  },
  en: {
    h1: 'Contact',
    name: 'Name',
    email: 'Email',
    message: 'Message',
    submit: 'Send',
    sending: 'Sending...',
    success: 'Message sent successfully!',
    error: 'Error sending. Please try again.',
  }
} as const

export default function ContactPage({ params }: { params: { locale: string } }) {
  const locale: Locale = isLocale(params.locale) ? (params.locale as Locale) : defaultLocale
  const t = useMemo(() => copy[locale], [locale])

  const [formData, setFormData] = useState({ name: '', email: '', message: '' })
  const [status, setStatus] = useState<'idle' | 'sending' | 'success' | 'error'>('idle')

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    setStatus('sending')

    try {
      const response = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData),
      })

      if (response.ok) {
        setStatus('success')
        setFormData({ name: '', email: '', message: '' })
      } else {
        setStatus('error')
      }
    } catch {
      setStatus('error')
    }
  }

  return (
    <main className="section">
      <div className="container max-w-2xl">
        <h1 className="mb-6 text-3xl font-bold" style={{color:'var(--color-primary)'}}>{t.h1}</h1>

        {status === 'success' && (
          <div className="mb-4 rounded-md bg-green-50 p-4 text-green-800">
            {t.success}
          </div>
        )}

        {status === 'error' && (
          <div className="mb-4 rounded-md bg-red-50 p-4 text-red-800">
            {t.error}
          </div>
        )}

        <form className="space-y-3" onSubmit={handleSubmit}>
          <div>
            <label className="mb-1 block text-sm text-gray-700">{t.name}</label>
            <input
              className="w-full rounded-md border border-gray-300 bg-white px-3 py-2"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
              disabled={status === 'sending'}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm text-gray-700">{t.email}</label>
            <input
              type="email"
              className="w-full rounded-md border border-gray-300 bg-white px-3 py-2"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              required
              disabled={status === 'sending'}
            />
          </div>
          <div>
            <label className="mb-1 block text-sm text-gray-700">{t.message}</label>
            <textarea
              className="w-full rounded-md border border-gray-300 bg-white px-3 py-2"
              rows={5}
              value={formData.message}
              onChange={(e) => setFormData({ ...formData, message: e.target.value })}
              required
              disabled={status === 'sending'}
            />
          </div>
          <button
            className="btn-primary disabled:opacity-50"
            type="submit"
            disabled={status === 'sending'}
          >
            {status === 'sending' ? t.sending : t.submit}
          </button>
        </form>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
