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
  const { pathname } = new URL(request.url)

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
    const url = new URL(request.url)
    url.pathname = `/${defaultLocale}${pathname}`
    return NextResponse.redirect(url)
  }

  return NextResponse.next()
}

export const config = {
  matcher: ['/((?!_next|.*\.).*)'],
}

