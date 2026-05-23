import type { MetadataRoute } from 'next'

const BASE = 'https://www.facturino.mk'
const LOCALES = ['mk', 'sq', 'tr', 'en'] as const

const MARKETING_PAGES = [
  { path: '', priority: 1.0, changeFrequency: 'weekly' as const },
  { path: '/features', priority: 0.9, changeFrequency: 'monthly' as const },
  { path: '/pricing', priority: 0.9, changeFrequency: 'weekly' as const },
  { path: '/how-it-works', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/for-accountants', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/e-faktura', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/pos', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/integrations', priority: 0.7, changeFrequency: 'monthly' as const },
  { path: '/contact', priority: 0.6, changeFrequency: 'monthly' as const },
  { path: '/pregled', priority: 0.6, changeFrequency: 'monthly' as const },
  { path: '/privacy', priority: 0.3, changeFrequency: 'yearly' as const },
  { path: '/terms', priority: 0.3, changeFrequency: 'yearly' as const },
  { path: '/security', priority: 0.3, changeFrequency: 'yearly' as const },
]

const TOOL_PAGES = [
  { path: '/alati', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/alati/plata-kalkulator', priority: 0.9, changeFrequency: 'monthly' as const },
  { path: '/alati/ddv-kalkulator', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/alati/efaktura-proverka', priority: 0.7, changeFrequency: 'monthly' as const },
  { path: '/alati/kamaten-kalkulator', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/alati/danok-dobivka-kalkulator', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/alati/kalkulator-registracija-firma', priority: 0.8, changeFrequency: 'monthly' as const },
  { path: '/alati/danocen-kalendar', priority: 0.9, changeFrequency: 'monthly' as const },
]

const BLOG_POSTS: { slug: string; date: string }[] = [
  { slug: 'rokovi-ujp-2026', date: '2026-02-08' },
  { slug: 'trudovo-pravo-osnovi', date: '2026-02-18' },
  { slug: 'presmetka-na-plata-mk', date: '2026-02-16' },
  { slug: 'ddv-vodich-mk', date: '2026-02-02' },
  { slug: 'otvoranje-firma-mk', date: '2026-02-11' },
  { slug: 'kako-da-napravite-faktura', date: '2026-02-10' },
  { slug: 'sto-e-e-faktura', date: '2026-02-05' },
  { slug: 'smetkovodstvo-za-pocetnici', date: '2026-02-12' },
  { slug: 'mpin-obrazec', date: '2026-02-17' },
  { slug: 'faktura-vs-proforma', date: '2026-02-07' },
  { slug: 'pos-softver-makedonija', date: '2026-02-14' },
  { slug: 'ai-skener-dokumenti', date: '2026-02-19' },
  { slug: 'danok-na-dobivka', date: '2026-02-06' },
  { slug: 'personalen-danok-na-dohod', date: '2026-02-13' },
  { slug: 'digitalno-smetkovodstvo', date: '2026-02-15' },
  { slug: 'godishna-smetka-2025', date: '2026-02-04' },
  { slug: 'facturino-vs-excel', date: '2026-05-23' },
  { slug: 'zosto-facturino', date: '2026-02-03' },
  { slug: 'za-smetkovoditeli', date: '2026-02-09' },
  { slug: 'bilans-na-sostojba', date: '2026-02-21' },
  { slug: 'fiskalen-pecatac-chrome', date: '2026-02-22' },
  { slug: 'recurring-invoices-mk', date: '2026-02-23' },
  { slug: 'ai-asistent-smetkovodstvo', date: '2026-02-24' },
  { slug: 'ai-bankarski-usoglasuvanje', date: '2026-03-29' },
  { slug: 'vector-alternativa-pos', date: '2026-02-25' },
  { slug: 'upravljanje-so-rashodi', date: '2026-02-26' },
  { slug: 'budzet-i-kontrola-troshoci', date: '2026-02-27' },
  { slug: 'nabavki-i-narachki', date: '2026-02-28' },
  { slug: 'cash-flow-mk', date: '2026-03-01' },
  { slug: 'zadolzitelni-elementi-faktura', date: '2026-03-02' },
  { slug: 'paushalen-danochnik', date: '2026-03-03' },
  { slug: 'godishno-zatvoranje-facturino', date: '2026-03-04' },
  { slug: 'kako-da-otvorite-firma-i-pocnete-so-fakturiranje', date: '2026-03-05' },
  { slug: 'bruto-neto-kalkulator-2026', date: '2026-05-22' },
  { slug: 'faktura-primer-mk', date: '2026-05-22' },
  { slug: 'godishna-danocna-prijava-2026', date: '2026-05-22' },
  { slug: 'neisplatena-plata-prijavuvanje', date: '2026-05-23' },
  { slug: 'mpin-registracija-2026', date: '2026-05-23' },
  { slug: 'e-faktura-obvrska-2026', date: '2026-05-23' },
  { slug: 'rok-za-plata-makedonija', date: '2026-05-23' },
  { slug: 'ddv-registracija-prag-2026', date: '2026-05-23' },
  { slug: 'kazni-ujp-2026', date: '2026-05-23' },
  { slug: 'dooel-vodich-2026', date: '2026-05-23' },
  { slug: 'registracija-firma-cekor-po-cekor', date: '2026-05-23' },
  { slug: 'drzavni-institucii-za-firmi', date: '2026-05-23' },
  { slug: 'paket-za-nova-firma', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-restorani', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-trgovija', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-gradeznistvo', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-it-freelancer', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-transport', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-proizvodstvo', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-saloni', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-zemjodelstvo', date: '2026-05-23' },
  { slug: 'najdobar-smetkovodstven-softver-2026', date: '2026-05-23' },
  { slug: 'najdobar-pos-softver-2026', date: '2026-05-23' },
  { slug: 'najdobar-e-faktura-softver', date: '2026-05-23' },
  { slug: 'facturino-vs-pantheon', date: '2026-05-23' },
  { slug: 'javni-nabavki-fakturiranje', date: '2026-05-23' },
  { slug: 'smetkovodstvo-za-nevladini', date: '2026-05-23' },
  { slug: 'ifrs-izvesti-mk', date: '2026-05-23' },
]

function makeAlternates(path: string) {
  const languages: Record<string, string> = {}
  for (const l of LOCALES) {
    languages[l] = `${BASE}/${l}${path}`
  }
  languages['x-default'] = `${BASE}/mk${path}`
  return { languages }
}

export default function sitemap(): MetadataRoute.Sitemap {
  const entries: MetadataRoute.Sitemap = []

  for (const page of MARKETING_PAGES) {
    for (const locale of LOCALES) {
      entries.push({
        url: `${BASE}/${locale}${page.path}`,
        lastModified: new Date(),
        changeFrequency: page.changeFrequency,
        priority: page.priority,
        alternates: makeAlternates(page.path),
      })
    }
  }

  for (const tool of TOOL_PAGES) {
    for (const locale of LOCALES) {
      entries.push({
        url: `${BASE}/${locale}${tool.path}`,
        lastModified: new Date(),
        changeFrequency: tool.changeFrequency,
        priority: tool.priority,
        alternates: makeAlternates(tool.path),
      })
    }
  }

  entries.push(
    ...LOCALES.map((locale) => ({
      url: `${BASE}/${locale}/blog`,
      lastModified: new Date(),
      changeFrequency: 'weekly' as const,
      priority: 0.7,
      alternates: makeAlternates('/blog'),
    }))
  )

  for (const post of BLOG_POSTS) {
    const blogPath = `/blog/${post.slug}`
    for (const locale of LOCALES) {
      entries.push({
        url: `${BASE}/${locale}${blogPath}`,
        lastModified: new Date(post.date),
        changeFrequency: 'monthly',
        priority: 0.7,
        alternates: makeAlternates(blogPath),
      })
    }
  }

  return entries
}
