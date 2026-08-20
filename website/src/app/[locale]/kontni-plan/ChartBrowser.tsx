'use client'

import { useMemo, useState } from 'react'
import Link from 'next/link'
import { type Locale } from '@/i18n/locales'
import { ACCOUNTS_TYPED, CHART_CLASSES } from '@/data/chartOfAccounts'

const UI: Record<Locale, {
  placeholder: string
  all: string
  results: (n: number) => string
  none: string
  code: string
  name: string
  klasa: string
  viewClass: string
}> = {
  mk: {
    placeholder: 'Пребарувај по код или име на сметка (пр. 1200 или „купувачи“)…',
    all: 'Сите класи',
    results: (n) => `${n} сметки`,
    none: 'Нема резултати. Обидете се со друг код или збор.',
    code: 'Код', name: 'Назив на сметка', klasa: 'Класа',
    viewClass: 'Види ја целата класа →',
  },
  sq: {
    placeholder: 'Kërko sipas kodit ose emrit të llogarisë (p.sh. 1200 ose "blerës")…',
    all: 'Të gjitha klasat',
    results: (n) => `${n} llogari`,
    none: 'Asnjë rezultat. Provoni një kod ose fjalë tjetër.',
    code: 'Kodi', name: 'Emri i llogarisë', klasa: 'Klasa',
    viewClass: 'Shiko të gjithë klasën →',
  },
  tr: {
    placeholder: 'Hesap kodu veya adına göre ara (örn. 1200 veya "alıcılar")…',
    all: 'Tüm sınıflar',
    results: (n) => `${n} hesap`,
    none: 'Sonuç yok. Başka bir kod veya kelime deneyin.',
    code: 'Kod', name: 'Hesap adı', klasa: 'Sınıf',
    viewClass: 'Tüm sınıfı görüntüle →',
  },
  en: {
    placeholder: 'Search by account code or name (e.g. 1200 or "customers")…',
    all: 'All classes',
    results: (n) => `${n} accounts`,
    none: 'No results. Try a different code or word.',
    code: 'Code', name: 'Account name', klasa: 'Class',
    viewClass: 'View the full class →',
  },
}

const TYPE_BADGE: Record<string, string> = {
  asset: 'bg-blue-100 text-blue-700',
  liability: 'bg-amber-100 text-amber-700',
  expense: 'bg-rose-100 text-rose-700',
  revenue: 'bg-emerald-100 text-emerald-700',
  equity: 'bg-violet-100 text-violet-700',
  result: 'bg-slate-200 text-slate-700',
}

export default function ChartBrowser({ locale }: { locale: Locale }) {
  const t = UI[locale]
  const [query, setQuery] = useState('')
  const [klasa, setKlasa] = useState<string | null>(null)

  const filtered = useMemo(() => {
    const q = query.trim().toLowerCase()
    return ACCOUNTS_TYPED.filter((a) => {
      if (klasa && a.code[0] !== klasa) return false
      if (!q) return true
      return a.code.toLowerCase().includes(q) || a.name.toLowerCase().includes(q)
    })
  }, [query, klasa])

  return (
    <div>
      <div className="sticky top-0 z-10 bg-white/90 backdrop-blur border-b border-gray-100 py-4">
        <input
          type="search"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={t.placeholder}
          className="w-full rounded-xl border border-gray-300 px-4 py-3 text-base focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none"
          aria-label={t.placeholder}
        />
        <div className="mt-3 flex flex-wrap gap-2">
          <button
            onClick={() => setKlasa(null)}
            className={`text-sm px-3 py-1.5 rounded-full font-medium transition-colors ${klasa === null ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
          >
            {t.all}
          </button>
          {CHART_CLASSES.map((c) => (
            <button
              key={c.digit}
              onClick={() => setKlasa(c.digit === klasa ? null : c.digit)}
              className={`text-sm px-3 py-1.5 rounded-full font-medium transition-colors ${klasa === c.digit ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}
              title={c.name[locale]}
            >
              {c.digit} · {c.name[locale]}
            </button>
          ))}
        </div>
        <p className="mt-2 text-sm text-gray-500">{t.results(filtered.length)}</p>
      </div>

      {klasa && (
        <div className="pt-4">
          <Link href={`/${locale}/kontni-plan/klasa-${klasa}`} className="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
            {t.viewClass}
          </Link>
        </div>
      )}

      {filtered.length === 0 ? (
        <p className="py-12 text-center text-gray-500">{t.none}</p>
      ) : (
        <div className="mt-4 overflow-hidden rounded-xl border border-gray-100">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-gray-500">
              <tr>
                <th className="px-4 py-2 font-semibold w-24">{t.code}</th>
                <th className="px-4 py-2 font-semibold">{t.name}</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {filtered.slice(0, 400).map((a) => (
                <tr key={a.code} className="hover:bg-indigo-50/40">
                  <td className="px-4 py-2 font-mono font-semibold text-gray-900">{a.code}</td>
                  <td className="px-4 py-2 text-gray-700">
                    <span className={`mr-2 inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase ${TYPE_BADGE[a.type] ?? 'bg-gray-100 text-gray-600'}`}>{a.type}</span>
                    {a.name}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
          {filtered.length > 400 && (
            <p className="px-4 py-3 text-center text-sm text-gray-500">
              {t.results(filtered.length)} — {locale === 'mk' ? 'стеснете го пребарувањето' : locale === 'sq' ? 'ngushtoni kërkimin' : locale === 'tr' ? 'aramayı daraltın' : 'narrow your search'}
            </p>
          )}
        </div>
      )}
    </div>
  )
}
