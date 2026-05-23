const BASE_URL = 'https://www.facturino.mk'

type ArticleJsonLdProps = {
  locale: string
  slug: string
  title: string
  description: string
  datePublished: string
  dateModified?: string
  tags?: string[]
}

export function articleJsonLd(props: ArticleJsonLdProps) {
  return {
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: props.title,
    description: props.description,
    datePublished: props.datePublished,
    dateModified: props.dateModified || props.datePublished,
    author: {
      '@type': 'Organization',
      name: 'Facturino',
      url: BASE_URL,
    },
    publisher: {
      '@type': 'Organization',
      name: 'Facturino',
      logo: {
        '@type': 'ImageObject',
        url: `${BASE_URL}/brand/facturino_logo.png`,
      },
    },
    mainEntityOfPage: {
      '@type': 'WebPage',
      '@id': `${BASE_URL}/${props.locale}/blog/${props.slug}`,
    },
    inLanguage: props.locale,
    ...(props.tags ? { keywords: props.tags.join(', ') } : {}),
  }
}

type BreadcrumbItem = { name: string; href: string }

export function breadcrumbJsonLd(items: BreadcrumbItem[]) {
  return {
    '@context': 'https://schema.org',
    '@type': 'BreadcrumbList',
    itemListElement: items.map((item, i) => ({
      '@type': 'ListItem',
      position: i + 1,
      name: item.name,
      item: item.href.startsWith('http') ? item.href : `${BASE_URL}${item.href}`,
    })),
  }
}
