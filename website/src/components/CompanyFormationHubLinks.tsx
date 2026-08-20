import Link from 'next/link'
import { isLocale, defaultLocale, type Locale } from '@/i18n/locales'

// Reusable internal-linking callout that funnels link equity from existing
// company-formation blog posts + the registration calculator into the
// /otvori-firma hub. Fully localized (mk/sq/tr/en).

const COPY: Record<Locale, {
  eyebrow: string
  title: string
  sub: string
  links: { href: string; label: string }[]
  cta: string
}> = {
  mk: {
    eyebrow: 'Отвори фирма',
    title: 'Отворате фирма? Почнете од вистинското место',
    sub: 'Практични водичи: која правна форма, даночен режим и чекори за регистрација.',
    links: [
      { href: 'dooel-ili-doo', label: 'ДООЕЛ, ДОО или ТП — која форма?' },
      { href: 'paushal-ili-ddv', label: 'Паушалец или ДДВ обврзник?' },
      { href: 'trgovec-poedinec', label: 'Трговец поединец' },
      { href: 'za-stranci', label: 'Отворање фирма за странци' },
    ],
    cta: 'Целосен водич за отворање фирма →',
  },
  sq: {
    eyebrow: 'Hap firmë',
    title: 'Po hapni firmë? Filloni nga vendi i duhur',
    sub: 'Udhëzues praktikë: cila formë ligjore, regjimi tatimor dhe hapat e regjistrimit.',
    links: [
      { href: 'dooel-ili-doo', label: 'DOOEL, DOO apo TP — cila formë?' },
      { href: 'paushal-ili-ddv', label: 'Tatimpagues fiks apo TVSH?' },
      { href: 'trgovec-poedinec', label: 'Tregtar individual' },
      { href: 'za-stranci', label: 'Hapja e firmës për të huajt' },
    ],
    cta: 'Udhëzues i plotë për hapjen e firmës →',
  },
  tr: {
    eyebrow: 'Şirket kur',
    title: 'Şirket mi kuruyorsunuz? Doğru yerden başlayın',
    sub: 'Pratik rehberler: hangi hukuki biçim, vergi rejimi ve kayıt adımları.',
    links: [
      { href: 'dooel-ili-doo', label: 'DOOEL, DOO veya TP — hangi biçim?' },
      { href: 'paushal-ili-ddv', label: 'Götürü vergi mi KDV mi?' },
      { href: 'trgovec-poedinec', label: 'Şahıs işletmesi' },
      { href: 'za-stranci', label: 'Yabancılar için şirket kurma' },
    ],
    cta: 'Şirket kurma için kapsamlı rehber →',
  },
  en: {
    eyebrow: 'Start a Company',
    title: 'Starting a company? Begin in the right place',
    sub: 'Practical guides: which legal form, tax regime, and registration steps.',
    links: [
      { href: 'dooel-ili-doo', label: 'DOOEL, DOO or sole proprietor — which form?' },
      { href: 'paushal-ili-ddv', label: 'Lump-sum (paušal) or VAT-registered?' },
      { href: 'trgovec-poedinec', label: 'Sole proprietor (Trgovec Poedinec)' },
      { href: 'za-stranci', label: 'Opening a company as a foreigner' },
    ],
    cta: 'Complete company-formation guide →',
  },
}

export default function CompanyFormationHubLinks({ locale: localeParam }: { locale: string }) {
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const t = COPY[locale]

  return (
    <aside className="my-12 rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-cyan-50 p-6 sm:p-8">
      <span className="inline-block bg-emerald-100 text-emerald-700 text-xs font-semibold px-3 py-1 rounded-full mb-3">
        {t.eyebrow}
      </span>
      <h2 className="text-xl sm:text-2xl font-bold text-gray-900 mb-2">{t.title}</h2>
      <p className="text-gray-600 mb-5">{t.sub}</p>
      <ul className="grid gap-2 sm:grid-cols-2">
        {t.links.map((link) => (
          <li key={link.href}>
            <Link
              href={`/${locale}/otvori-firma/${link.href}`}
              className="flex items-center gap-2 text-emerald-700 hover:text-emerald-900 hover:underline font-medium"
            >
              <span className="text-emerald-400">→</span>
              {link.label}
            </Link>
          </li>
        ))}
      </ul>
      <Link
        href={`/${locale}/otvori-firma`}
        className="inline-block mt-5 font-semibold text-emerald-600 hover:text-emerald-800"
      >
        {t.cta}
      </Link>
    </aside>
  )
}
// CLAUDE-CHECKPOINT
