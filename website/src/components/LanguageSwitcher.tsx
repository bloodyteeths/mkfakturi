"use client"
import { usePathname } from 'next/navigation'
import { locales, Locale } from '@/i18n/locales'

function replaceLocale(pathname: string, next: Locale) {
  const parts = pathname.split('/')
  parts[1] = next
  return parts.join('/') || `/${next}`
}

export default function LanguageSwitcher({ current }: { current: Locale }) {
  const pathname = usePathname()

  const handleChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    const newLocale = e.target.value as Locale
    const newPath = replaceLocale(pathname || '/', newLocale)
    // Force full page reload to ensure server re-renders with new locale
    window.location.href = newPath
  }

  return (
    <select
      className="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm"
      value={current}
      onChange={handleChange}
    >
      {locales.map((l) => (
        <option key={l} value={l}>
          {l.toUpperCase()}
        </option>
      ))}
    </select>
  )
}

