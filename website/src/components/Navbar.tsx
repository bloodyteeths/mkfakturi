import Link from 'next/link'
import Image from 'next/image'
import LanguageSwitcher from './LanguageSwitcher'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

export default function Navbar({ t, locale }: { t: Dictionary; locale: Locale }) {
  return (
    <header className="sticky top-0 z-40 w-full border-b bg-white/80 backdrop-blur">
      <div className="container flex items-center justify-between py-3">
        <div className="flex items-center gap-3 md:gap-6">
          <Link href={`/${locale}`} className="flex items-center gap-2">
            <Image src="/brand/facturino_logo.png" alt="Facturino" width={28} height={28} />
            <span className="text-xl font-bold text-[color:var(--color-primary)]">Facturino</span>
          </Link>
          <nav className="hidden gap-5 text-sm md:flex">
            <Link href={`/${locale}/features`} className="hover:underline">
              {t.nav.features}
            </Link>
            {t.nav.forAccountants && (
              <Link href={`/${locale}/for-accountants`} className="hover:underline">
                {t.nav.forAccountants}
              </Link>
            )}
            <Link href={`/${locale}/how-it-works`} className="hover:underline">
              {t.nav.how}
            </Link>
            <Link href={`/${locale}/e-faktura`} className="hover:underline">
              {t.nav.efaktura}
            </Link>
            <Link href={`/${locale}/pricing`} className="hover:underline">
              {t.nav.pricing}
            </Link>
            <Link href={`/${locale}/security`} className="hover:underline">
              {t.nav.security}
            </Link>
            <Link href={`/${locale}/contact`} className="hover:underline">
              {t.nav.contact}
            </Link>
          </nav>
        </div>
        <div className="flex items-center gap-3">
          <LanguageSwitcher current={locale} />
          <Link href="#login" className="text-sm text-gray-700 hover:underline">
            {t.nav.login}
          </Link>
          <Link href={`/${locale}/pricing`} className="btn-accent text-sm">
            {t.nav.start}
          </Link>
        </div>
      </div>
    </header>
  )
}
