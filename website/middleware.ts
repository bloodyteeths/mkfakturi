import { NextResponse } from 'next/server'

// Supported locales
const locales = ['mk', 'sq', 'tr'] as const
type Locale = typeof locales[number]
const defaultLocale: Locale = 'mk'

function getLocale(pathname: string): Locale | null {
  const segment = pathname.split('/')[1]
  return locales.includes(segment as Locale) ? (segment as Locale) : null
}

export function middleware(request: Request) {
  const url = new URL(request.url)
  const { pathname } = url

  // 1) Canonical domain: redirect www.facturino.mk -> facturino.mk (301)
  const host = (request.headers.get('host') || '').toLowerCase()
  if (host === 'www.facturino.mk') {
    url.hostname = 'facturino.mk'
    return NextResponse.redirect(url, 301)
  }

  // Ignore next internal assets and API routes
  if (
    pathname.startsWith('/_next') ||
    pathname.startsWith('/api') ||
    pathname.includes('.')
  ) {
    return NextResponse.next()
  }

  const locale = getLocale(pathname)
  if (!locale) {
    const r = new URL(request.url)
    r.pathname = `/${defaultLocale}${pathname}`
    return NextResponse.redirect(r)
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next|.*\.).*)'],
}
