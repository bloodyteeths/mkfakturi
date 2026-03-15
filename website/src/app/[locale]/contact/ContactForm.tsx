"use client"
import { useMemo, useState } from 'react'
import { type Locale } from '@/i18n/locales'
import PageHero from '@/components/PageHero'

const copy = {
  mk: {
    h1: 'Контакт',
    subtitle: 'Контактирајте го тимот на Facturino за прашања, поддршка или демо.',
    name: 'Име',
    email: 'Е\u2011пошта',
    message: 'Порака',
    submit: 'Испрати',
    sending: 'Се испраќа...',
    success: 'Пораката е испратена успешно!',
    error: 'Грешка при испраќање. Обидете се повторно.',
  },
  sq: {
    h1: 'Kontakti',
    subtitle: 'Kontaktoni ekipin e Facturino p\u00ebr pyetje, mb\u00ebshtetje ose demo.',
    name: 'Emri',
    email: 'Email',
    message: 'Mesazhi',
    submit: 'D\u00ebrgo',
    sending: 'Duke d\u00ebrguar...',
    success: 'Mesazhi u d\u00ebrgua me sukses!',
    error: 'Gabim gjat\u00eb d\u00ebrgimit. Provoni p\u00ebrs\u00ebri.',
  },
  tr: {
    h1: '\u0130leti\u015fim',
    subtitle: 'Sorular, destek veya demo i\u00e7in Facturino ekibiyle ileti\u015fime ge\u00e7in.',
    name: 'Ad',
    email: 'E\u2011posta',
    message: 'Mesaj',
    submit: 'G\u00f6nder',
    sending: 'G\u00f6nderiliyor...',
    success: 'Mesaj ba\u015far\u0131yla g\u00f6nderildi!',
    error: 'G\u00f6nderim hatas\u0131. Tekrar deneyin.',
  },
  en: {
    h1: 'Contact',
    subtitle: 'Get in touch with the Facturino team for questions, support, or a demo.',
    name: 'Name',
    email: 'Email',
    message: 'Message',
    submit: 'Send',
    sending: 'Sending...',
    success: 'Message sent successfully!',
    error: 'Error sending. Please try again.',
  }
} as const

export default function ContactForm({ locale }: { locale: Locale }) {
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
    <main id="main-content">
      <PageHero
        image="/assets/images/hero_contact.png"
        alt="Friendly team in bright modern office waving and smiling"
        title={t.h1}
        subtitle={t.subtitle}
      />

      <div className="section container max-w-2xl">
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
            <label htmlFor="contact-name" className="mb-1 block text-sm text-gray-700">{t.name}</label>
            <input
              id="contact-name"
              className="w-full rounded-md border border-gray-300 bg-white px-3 py-2"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
              required
              disabled={status === 'sending'}
            />
          </div>
          <div>
            <label htmlFor="contact-email" className="mb-1 block text-sm text-gray-700">{t.email}</label>
            <input
              id="contact-email"
              type="email"
              className="w-full rounded-md border border-gray-300 bg-white px-3 py-2"
              value={formData.email}
              onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              required
              disabled={status === 'sending'}
            />
          </div>
          <div>
            <label htmlFor="contact-message" className="mb-1 block text-sm text-gray-700">{t.message}</label>
            <textarea
              id="contact-message"
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
