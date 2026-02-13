import Link from 'next/link'
import Image from 'next/image'
import LanguageSwitcher from './LanguageSwitcher'
import MobileMenu from './MobileMenu'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'
const APP_URL = process.env.NEXT_PUBLIC_APP_URL || 'https://app.facturino.mk'

export default function Navbar({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <header className="sticky top-0 z-40 w-full border-b border-gray-100 bg-white/90 backdrop-blur-md shadow-sm">
      <div className="container flex items-center justify-between h-16">
        {/* Logo */}
        <Link href={`/${locale}`} className="flex items-center gap-2.5 flex-shrink-0">
          <Image src="/brand/facturino_logo.png" alt="Facturino" width={80} height={80} priority sizes="80px" className="w-9 h-9" />
          <span className="text-xl font-bold text-[color:var(--color-primary)]">Facturino</span>
        </Link>

        {/* Desktop Nav */}
        <nav className="hidden lg:flex items-center gap-1">
          <Link href={`/${locale}/features`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
            {t.nav.features}
          </Link>
          <Link href={`/${locale}/e-faktura`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
            {t.nav.efaktura}
          </Link>
          {t.nav.integrations && (
            <Link href={`/${locale}/integrations`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
              {t.nav.integrations}
            </Link>
          )}
          <Link href={`/${locale}/pricing`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
            {t.nav.pricing}
          </Link>
          {t.nav.forAccountants && (
            <Link href={`/${locale}/for-accountants`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
              {t.nav.forAccountants}
            </Link>
          )}
          <Link href={`/${locale}/contact`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
            {t.nav.contact}
          </Link>
          {t.nav.blog && (
            <Link href={`/${locale}/blog`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-indigo-600 rounded-lg hover:bg-indigo-50 transition-colors">
              {t.nav.blog}
            </Link>
          )}
        </nav>

        {/* Desktop Actions */}
        <div className="hidden lg:flex items-center gap-3">
          <LanguageSwitcher current={locale} />
          <Link href={`${APP_URL}/admin`} className="px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors">
            {t.nav.login}
          </Link>
          <Link href={`/${locale}/pricing`} className="btn-accent text-sm">
            {t.nav.start}
          </Link>
        </div>

        {/* Mobile Actions */}
        <div className="flex lg:hidden items-center gap-2">
          <LanguageSwitcher current={locale} />
          <MobileMenu t={t} locale={locale} />
        </div>
      </div>
    </header>
  )
}
