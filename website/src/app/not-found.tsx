import Link from 'next/link'

export const metadata = {
  title: 'Page Not Found \u2014 Facturino',
}

export default function NotFound() {
  return (
    <main className="flex min-h-screen flex-col items-center justify-center px-4 text-center">
      <h1 className="text-6xl font-bold" style={{ color: 'var(--color-primary)' }}>404</h1>
      <p className="mt-4 text-xl text-gray-600">Page not found / {'\u0421\u0442\u0440\u0430\u043D\u0438\u0446\u0430\u0442\u0430 \u043D\u0435 \u0435 \u043F\u0440\u043E\u043D\u0430\u0458\u0434\u0435\u043D\u0430'}</p>
      <p className="mt-2 text-gray-500">The page you are looking for does not exist.</p>
      <Link href="/mk" className="mt-8 inline-block rounded-md bg-indigo-600 px-6 py-3 text-white hover:bg-indigo-700 transition">
        Return Home / {'\u041F\u043E\u0447\u0435\u0442\u043D\u0430'}
      </Link>
    </main>
  )
}
