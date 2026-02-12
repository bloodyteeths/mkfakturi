import { locales, defaultLocale, type Locale, isLocale } from '@/i18n/locales'

const BASE_URL = 'https://www.facturino.mk'

type PageMeta = {
  title: Record<string, string>
  description: Record<string, string>
}

export function buildPageMetadata(locale: string, path: string, meta: PageMeta) {
  const lang: Locale = isLocale(locale) ? locale as Locale : defaultLocale
  const fullPath = path === '/' ? '' : path

  const languages: Record<string, string> = {}
  for (const l of locales) {
    languages[l] = `${BASE_URL}/${l}${fullPath}`
  }
  languages['x-default'] = `${BASE_URL}/${defaultLocale}${fullPath}`

  const localeUrl = `${BASE_URL}/${lang}${fullPath}`

  return {
    title: meta.title[lang] || meta.title.en,
    description: meta.description[lang] || meta.description.en,
    alternates: {
      canonical: localeUrl,
      languages,
    },
    openGraph: {
      title: meta.title[lang] || meta.title.en,
      description: meta.description[lang] || meta.description.en,
      url: localeUrl,
      type: 'website' as const,
    },
  }
}
// CLAUDE-CHECKPOINT
