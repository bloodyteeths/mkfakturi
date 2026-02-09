import Link from 'next/link'
import LanguageSwitcher from './LanguageSwitcher'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

const footerCopy = {
  mk: {
    companyInfo: 'Facturino by MK Accounting DOOEL, Скопје, Северна Македонија',
    product: 'Производ',
    features: 'Функции',
    pricing: 'Цени',
    integrations: 'Интеграции',
    company: 'Компанија',
    about: 'За нас',
    partners: 'Партнери',
    blog: 'Блог',
    legal: 'Правно',
    privacy: 'Политика за приватност',
    terms: 'Услови за користење',
    agpl: 'AGPL Лиценца',
    contact: 'Контакт',
    followUs: 'Следете не',
  },
  sq: {
    companyInfo: 'Facturino by MK Accounting DOOEL, Shkup, Maqedonia e Veriut',
    product: 'Produkti',
    features: 'Vecorite',
    pricing: 'Cmimet',
    integrations: 'Integrimet',
    company: 'Kompania',
    about: 'Rreth nesh',
    partners: 'Partneret',
    blog: 'Blog',
    legal: 'Ligjore',
    privacy: 'Politika e privatësisë',
    terms: 'Kushtet e shërbimit',
    agpl: 'Licenca AGPL',
    contact: 'Kontakti',
    followUs: 'Na ndiqni',
  },
  tr: {
    companyInfo: 'Facturino by MK Accounting DOOEL, Üsküp, Kuzey Makedonya',
    product: 'Ürün',
    features: 'Özellikler',
    pricing: 'Fiyatlar',
    integrations: 'Entegrasyonlar',
    company: 'Şirket',
    about: 'Hakkımızda',
    partners: 'İş Ortakları',
    blog: 'Blog',
    legal: 'Yasal',
    privacy: 'Gizlilik Politikası',
    terms: 'Kullanım Koşulları',
    agpl: 'AGPL Lisansı',
    contact: 'İletişim',
    followUs: 'Bizi Takip Edin',
  },
  en: {
    companyInfo: 'Facturino by MK Accounting DOOEL, Skopje, North Macedonia',
    product: 'Product',
    features: 'Features',
    pricing: 'Pricing',
    integrations: 'Integrations',
    company: 'Company',
    about: 'About',
    partners: 'Partners',
    blog: 'Blog',
    legal: 'Legal',
    privacy: 'Privacy Policy',
    terms: 'Terms of Service',
    agpl: 'AGPL License',
    contact: 'Contact',
    followUs: 'Follow Us',
  },
} as const

export default function Footer({ t, locale }: { t: Dictionary; locale: Locale }) {
  const f = footerCopy[locale]

  return (
    <footer className="mt-16 border-t bg-gray-50">
      <div className="container py-12">
        {/* Top section: Brand + Navigation columns */}
        <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-5">
          {/* Brand / Company info */}
          <div className="lg:col-span-2">
            <Link href={`/${locale}`} className="inline-block">
              <span className="text-2xl font-bold" style={{ color: 'var(--color-primary)' }}>
                Facturino
              </span>
            </Link>
            <p className="mt-3 text-sm text-gray-600 leading-relaxed max-w-xs">
              {f.companyInfo}
            </p>
            <a
              href="mailto:info@facturino.mk"
              className="mt-3 inline-block text-sm text-gray-600 hover:text-gray-900 transition-colors"
            >
              info@facturino.mk
            </a>

            {/* Social media links */}
            <div className="mt-4 flex items-center gap-3">
              <a
                href="#"
                aria-label="LinkedIn"
                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
              >
                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                </svg>
              </a>
              <a
                href="#"
                aria-label="Facebook"
                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
              >
                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                </svg>
              </a>
              <a
                href="#"
                aria-label="Instagram"
                className="flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]"
              >
                <svg className="h-4 w-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z" />
                </svg>
              </a>
            </div>
          </div>

          {/* Product column */}
          <div>
            <h3 className="mb-3 text-sm font-semibold text-gray-900 uppercase tracking-wider">
              {f.product}
            </h3>
            <ul className="space-y-2">
              <li>
                <Link
                  href={`/${locale}/features`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.features}
                </Link>
              </li>
              <li>
                <Link
                  href={`/${locale}/pricing`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.pricing}
                </Link>
              </li>
              <li>
                <Link
                  href={`/${locale}/e-faktura`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.integrations}
                </Link>
              </li>
            </ul>
          </div>

          {/* Company column */}
          <div>
            <h3 className="mb-3 text-sm font-semibold text-gray-900 uppercase tracking-wider">
              {f.company}
            </h3>
            <ul className="space-y-2">
              <li>
                <Link
                  href={`/${locale}/security`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.about}
                </Link>
              </li>
              <li>
                <Link
                  href={`/${locale}/for-accountants`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.partners}
                </Link>
              </li>
              <li>
                <Link
                  href={`/${locale}/contact`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.contact}
                </Link>
              </li>
            </ul>
          </div>

          {/* Legal column */}
          <div>
            <h3 className="mb-3 text-sm font-semibold text-gray-900 uppercase tracking-wider">
              {f.legal}
            </h3>
            <ul className="space-y-2">
              <li>
                <Link
                  href={`/${locale}/privacy`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.privacy}
                </Link>
              </li>
              <li>
                <Link
                  href={`/${locale}/terms`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.terms}
                </Link>
              </li>
              <li>
                <a
                  href="https://github.com/InvoiceShelf/InvoiceShelf"
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.agpl}
                </a>
              </li>
            </ul>
          </div>
        </div>

        {/* Bottom bar */}
        <div className="mt-10 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-6 md:flex-row">
          <p className="text-sm text-gray-500">{t.footer.rights}</p>
          <div className="flex items-center gap-4">
            <span className="text-xs text-gray-400">
              Built on{' '}
              <a
                href="https://github.com/InvoiceShelf/InvoiceShelf"
                target="_blank"
                rel="noopener noreferrer"
                className="underline hover:text-gray-600"
              >
                InvoiceShelf
              </a>
              {' '}(AGPL-3.0)
            </span>
            <LanguageSwitcher current={locale} />
          </div>
        </div>
      </div>
    </footer>
  )
}
// CLAUDE-CHECKPOINT
