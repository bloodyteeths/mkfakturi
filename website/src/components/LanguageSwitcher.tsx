"use client"
import { usePathname, useRouter } from 'next/navigation'
import { locales, Locale } from '@/i18n/locales'

function replaceLocale(pathname: string, next: Locale) {
  const parts = pathname.split('/')
  parts[1] = next
  return parts.join('/') || `/${next}`
}

export default function LanguageSwitcher({ current }: { current: Locale }) {
  const pathname = usePathname()
  const router = useRouter()

  return (
    <select
      className="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm"
      value={current}
      onChange={(e) => router.push(replaceLocale(pathname || '/', e.target.value as Locale))}
    >
      {locales.map((l) => (
        <option key={l} value={l}>
          {l.toUpperCase()}
        </option>
      ))}
    </select>
  )
}

