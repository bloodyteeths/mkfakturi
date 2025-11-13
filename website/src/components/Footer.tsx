import LanguageSwitcher from './LanguageSwitcher'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function Footer({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <footer className="mt-16 border-t bg-white">
      <div className="container flex flex-col items-center justify-between gap-4 py-6 md:flex-row">
        <p className="text-sm text-gray-500">{t.footer.rights}</p>
        <div className="flex items-center gap-3">
          <LanguageSwitcher current={locale} />
        </div>
      </div>
    </footer>
  )
}

