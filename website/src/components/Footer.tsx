import Link from 'next/link'
import Image from 'next/image'
import LanguageSwitcher from './LanguageSwitcher'
import { Dictionary } from '@/i18n/dictionaries'
import { Locale } from '@/i18n/locales'

const footerCopy = {
  mk: {
    companyInfo: 'Facturino by Facturino DOOEL, Скопје, Северна Македонија',
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
    companyInfo: 'Facturino by Facturino DOOEL, Shkup, Maqedonia e Veriut',
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
    companyInfo: 'Facturino by Facturino DOOEL, Üsküp, Kuzey Makedonya',
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
    companyInfo: 'Facturino by Facturino DOOEL, Skopje, North Macedonia',
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
            <Link href={`/${locale}`} className="inline-flex items-center gap-2">
              <Image src="/brand/facturino_logo.png" alt="Facturino" width={80} height={80} className="w-10 h-10" />
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

            {/* Social media links removed - placeholder URLs replaced when real profiles are available */}
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
                  href={`/${locale}/integrations`}
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
              <li>
                <Link
                  href={`/${locale}/blog`}
                  className="text-sm text-gray-600 hover:text-gray-900 transition-colors"
                >
                  {f.blog}
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
            <span className="text-xs text-gray-500">
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
