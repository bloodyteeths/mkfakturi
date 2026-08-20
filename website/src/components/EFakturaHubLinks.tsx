import Link from 'next/link'
import { isLocale, defaultLocale, type Locale } from '@/i18n/locales'

// Reusable internal-linking callout that funnels link equity from high-traffic /
// high-authority pages (top blog posts, DDV calculator) into the /e-faktura hub.
// Fully localized (mk/sq/tr/en). Works in both server and client components.

const COPY: Record<Locale, {
  eyebrow: string
  title: string
  sub: string
  links: { slug: string; label: string }[]
  cta: string
}> = {
  mk: {
    eyebrow: 'Е-фактура',
    title: 'Задолжителната е-фактура доаѓа — подгответе се навреме',
    sub: 'Практични водичи за УЈП е-фактурирањето: рокови, чекори, UBL формат и QES потпис.',
    links: [
      { slug: 'vodic', label: 'Целосен водич за е-фактура 2026' },
      { slug: 'rokovi-2026', label: 'Рокови 2026/2027' },
      { slug: 'kako-da-izdadete', label: 'Како да издадете е-фактура' },
      { slug: 'za-javni-nabavki', label: 'Е-фактура за јавни набавки' },
      { slug: 'casti-prasanja', label: 'Често поставувани прашања' },
    ],
    cta: 'Види ги сите водичи →',
  },
  sq: {
    eyebrow: 'E-fatura',
    title: 'E-fatura e detyrueshme po vjen — përgatituni me kohë',
    sub: 'Udhëzues praktikë për e-faturimin DAP: afatet, hapat, formati UBL dhe nënshkrimi QES.',
    links: [
      { slug: 'vodic', label: 'Udhëzues i plotë për e-faturën 2026' },
      { slug: 'rokovi-2026', label: 'Afatet 2026/2027' },
      { slug: 'kako-da-izdadete', label: 'Si të lëshoni një e-faturë' },
      { slug: 'za-javni-nabavki', label: 'E-fatura për prokurime publike' },
      { slug: 'casti-prasanja', label: 'Pyetje të bëra shpesh' },
    ],
    cta: 'Shiko të gjithë udhëzuesit →',
  },
  tr: {
    eyebrow: 'E-fatura',
    title: 'Zorunlu e-fatura geliyor — zamanında hazırlanın',
    sub: 'UJP e-faturalama için pratik rehberler: son tarihler, adımlar, UBL formatı ve QES imzası.',
    links: [
      { slug: 'vodic', label: 'Kapsamlı e-fatura rehberi 2026' },
      { slug: 'rokovi-2026', label: 'Son tarihler 2026/2027' },
      { slug: 'kako-da-izdadete', label: 'E-fatura nasıl düzenlenir' },
      { slug: 'za-javni-nabavki', label: 'Kamu ihaleleri için e-fatura' },
      { slug: 'casti-prasanja', label: 'Sık sorulan sorular' },
    ],
    cta: 'Tüm rehberleri gör →',
  },
  en: {
    eyebrow: 'E-Invoicing',
    title: 'Mandatory e-invoicing is coming — prepare in time',
    sub: 'Practical guides to UJP e-invoicing: deadlines, steps, the UBL format and QES signing.',
    links: [
      { slug: 'vodic', label: 'Complete e-invoicing guide 2026' },
      { slug: 'rokovi-2026', label: 'Deadlines 2026/2027' },
      { slug: 'kako-da-izdadete', label: 'How to issue an e-invoice' },
      { slug: 'za-javni-nabavki', label: 'E-invoicing for public procurement' },
      { slug: 'casti-prasanja', label: 'Frequently asked questions' },
    ],
    cta: 'See all guides →',
  },
}

export default function EFakturaHubLinks({ locale: localeParam }: { locale: string }) {
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = COPY[locale]

  return (
    <aside className="my-12 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-cyan-50 p-6 sm:p-8">
      <span className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
        {t.eyebrow}
      </span>
      <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">{t.title}</h2>
      <p className="text-gray-600 mb-5">{t.sub}</p>
      <ul className="grid gap-2 sm:grid-cols-2">
        {t.links.map((link) => (
          <li key={link.slug}>
            <Link
              href={`/${locale}/e-faktura/${link.slug}`}
              className="flex items-center gap-2 text-indigo-700 hover:text-indigo-900 hover:underline font-medium"
            >
              <span className="text-indigo-400">→</span>
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
      <Link
        href={`/${locale}/e-faktura/vodic`}
        className="inline-block mt-5 font-semibold text-indigo-600 hover:text-indigo-800"
      >
        {t.cta}
      </Link>
    </aside>
  )
}
// CLAUDE-CHECKPOINT
