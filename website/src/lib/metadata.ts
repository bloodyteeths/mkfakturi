import { locales, defaultLocale, type Locale, isLocale } from '@/i18n/locales'

const BASE_URL = 'https://www.facturino.mk'

type PageMeta = {
  title: Record<string, string>
  description: Record<string, string>
}

type ArticleMeta = PageMeta & {
  datePublished: string
  dateModified?: string
  author?: string
}

function buildAlternates(lang: Locale, fullPath: string) {
  const languages: Record<string, string> = {}
  for (const l of locales) {
    languages[l] = `${BASE_URL}/${l}${fullPath}`
  }
  languages['x-default'] = `${BASE_URL}/${defaultLocale}${fullPath}`
  return { canonical: `${BASE_URL}/${lang}${fullPath}`, languages }
}

function ogImageUrl(title: string, locale: string, type: string = 'page') {
  return `${BASE_URL}/api/og?title=${encodeURIComponent(title)}&locale=${locale}&type=${type}`
}

export function buildPageMetadata(locale: string, path: string, meta: PageMeta) {
  const lang: Locale = isLocale(locale) ? locale as Locale : defaultLocale
  const fullPath = path === '/' ? '' : path
  const title = meta.title[lang] || meta.title.en
  const description = meta.description[lang] || meta.description.en
  const localeUrl = `${BASE_URL}/${lang}${fullPath}`

  return {
    title,
    description,
    alternates: buildAlternates(lang, fullPath),
    openGraph: {
      title,
      description,
      url: localeUrl,
      type: 'website' as const,
      images: [{ url: ogImageUrl(title, lang), width: 1200, height: 630, alt: title }],
    },
    twitter: {
      card: 'summary_large_image' as const,
      title,
      description,
      images: [ogImageUrl(title, lang)],
    },
  }
}

export function buildArticleMetadata(locale: string, path: string, meta: ArticleMeta) {
  const lang: Locale = isLocale(locale) ? locale as Locale : defaultLocale
  const base = buildPageMetadata(locale, path, meta)
  const title = meta.title[lang] || meta.title.en
  const description = meta.description[lang] || meta.description.en

  return {
    ...base,
    authors: [{ name: meta.author || 'Facturino' }],
    openGraph: {
      ...base.openGraph,
      type: 'article' as const,
      publishedTime: meta.datePublished,
      modifiedTime: meta.dateModified || meta.datePublished,
      authors: [meta.author || 'Facturino'],
      images: [{ url: ogImageUrl(title, lang, 'article'), width: 1200, height: 630, alt: title }],
    },
    twitter: {
      card: 'summary_large_image' as const,
      title,
      description,
      images: [ogImageUrl(title, lang, 'article')],
    },
  }
}
