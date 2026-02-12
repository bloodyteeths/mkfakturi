import { NextResponse, type NextRequest } from 'next/server'

const SUPPORTED_LOCALES = ['mk', 'sq', 'tr', 'en']
const DEFAULT_LOCALE = 'mk'

/**
 * Middleware that extracts the locale from the URL path and passes it
 * to the root layout via a custom request header. This is necessary
 * because the root layout sits above the [locale] dynamic segment
 * and cannot access route params directly.
 */
export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl
  const firstSegment = pathname.split('/').filter(Boolean)[0] ?? ''
  const locale = SUPPORTED_LOCALES.includes(firstSegment)
    ? firstSegment
    : DEFAULT_LOCALE

  // Clone request headers and add x-locale so the root layout Server
  // Component can read it via headers(). NextResponse.next({ request })
  // forwards the modified request headers to downstream Server Components.
  const requestHeaders = new Headers(request.headers)
  requestHeaders.set('x-locale', locale)

  return NextResponse.next({
    request: { headers: requestHeaders },
  })
}

export const config = {
  // Run on all routes except static files and Next.js internals
  matcher: ['/((?!_next/static|_next/image|favicon.ico|brand/).*)'],
}
