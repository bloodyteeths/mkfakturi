import { defaultLocale, isLocale, type Locale } from '@/i18n/locales'
import { buildPageMetadata } from '@/lib/metadata'
import { breadcrumbJsonLd } from '@/lib/jsonld'
import Link from 'next/link'
import { notFound } from 'next/navigation'
import { CHART_CLASSES, classMeta, groupsForClass, accountsForClass } from '@/data/chartOfAccounts'

const LOCALES = ['mk', 'sq', 'tr', 'en'] as const

export function generateStaticParams() {
  const params: { locale: string; klasa: string }[] = []
  for (const locale of LOCALES) {
    for (const c of CHART_CLASSES) {
      params.push({ locale, klasa: `klasa-${c.digit}` })
    }
  }
  return params
}

function digitFrom(klasa: string): string {
  return klasa.replace('klasa-', '')
}

const LABEL = {
  mk: { home: 'Почетна', root: 'Контен план', klasa: 'Класа', accounts: 'сметки', code: 'Код', name: 'Назив', back: '← Контен план', group: 'Група' },
  sq: { home: 'Ballina', root: 'Plani kontabël', klasa: 'Klasa', accounts: 'llogari', code: 'Kodi', name: 'Emri', back: '← Plani kontabël', group: 'Grupi' },
  tr: { home: 'Ana Sayfa', root: 'Hesap planı', klasa: 'Sınıf', accounts: 'hesap', code: 'Kod', name: 'Ad', back: '← Hesap planı', group: 'Grup' },
  en: { home: 'Home', root: 'Chart of accounts', klasa: 'Class', accounts: 'accounts', code: 'Code', name: 'Name', back: '← Chart of accounts', group: 'Group' },
} as const

export async function generateMetadata({ params }: { params: Promise<{ locale: string; klasa: string }> }) {
  const { locale, klasa } = await params
  const digit = digitFrom(klasa)
  const meta = classMeta(digit)
  if (!meta) return {}
  return buildPageMetadata(locale, `/kontni-plan/klasa-${digit}`, {
    title: {
      mk: `Класа ${digit}: ${meta.name.mk} — контен план (сметки ${digit}xxx)`,
      en: `Class ${digit}: ${meta.name.en} — chart of accounts (${digit}xxx)`,
      sq: `Klasa ${digit}: ${meta.name.sq} — plani kontabël (${digit}xxx)`,
      tr: `Sınıf ${digit}: ${meta.name.tr} — hesap planı (${digit}xxx)`,
    },
    description: {
      mk: `Сите сметки од класа ${digit} (${meta.name.mk}) во македонскиот контен план според Правилник 174/2011. Аналитички 4-цифрени сметки ${digit}xxx по групи.`,
      en: `All class ${digit} accounts (${meta.name.en}) in the Macedonian chart of accounts (Rulebook 174/2011). 4-digit ${digit}xxx accounts grouped.`,
      sq: `Të gjitha llogaritë e klasës ${digit} (${meta.name.sq}) në planin kontabël maqedonas (174/2011).`,
      tr: `Makedonya hesap planında sınıf ${digit} (${meta.name.tr}) hesaplarının tümü (174/2011).`,
    },
  })
}

const TYPE_BADGE: Record<string, string> = {
  asset: 'bg-blue-100 text-blue-700',
  liability: 'bg-amber-100 text-amber-700',
  expense: 'bg-rose-100 text-rose-700',
  revenue: 'bg-emerald-100 text-emerald-700',
  equity: 'bg-violet-100 text-violet-700',
  result: 'bg-slate-200 text-slate-700',
}

export default async function KlasaPage({ params }: { params: Promise<{ locale: string; klasa: string }> }) {
  const { locale: localeParam, klasa } = await params
  const locale: Locale = isLocale(localeParam) ? (localeParam as Locale) : defaultLocale
  const digit = digitFrom(klasa)
  const meta = classMeta(digit)
  if (!meta) notFound()

  const L = LABEL[locale]
  const groups = groupsForClass(digit)
  const count = accountsForClass(digit).length

  const breadcrumbLd = breadcrumbJsonLd([
    { name: L.home, href: `/${locale}` },
    { name: L.root, href: `/${locale}/kontni-plan` },
    { name: `${L.klasa} ${digit}: ${meta.name[locale]}`, href: `/${locale}/kontni-plan/klasa-${digit}` },
  ])

  return (
    <main id="main-content">
      <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbLd) }} />

      <div className="container max-w-4xl mx-auto px-4 sm:px-6 py-12">
        <Link href={`/${locale}/kontni-plan`} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium mb-6 inline-block">{L.back}</Link>

        <div className="flex items-center gap-4 mb-2">
          <span className="flex-shrink-0 w-12 h-12 rounded-xl bg-indigo-100 text-indigo-700 text-xl font-extrabold flex items-center justify-center">{digit}</span>
          <h1 className="text-2xl md:text-3xl font-extrabold text-gray-900">{L.klasa} {digit}: {meta.name[locale]}</h1>
        </div>
        <p className="text-gray-500 mb-8">{count} {L.accounts}</p>

        {/* Sibling class navigation */}
        <div className="flex flex-wrap gap-2 mb-10">
          {CHART_CLASSES.map((c) => (
            <Link key={c.digit} href={`/${locale}/kontni-plan/klasa-${c.digit}`}
              className={`text-sm px-3 py-1.5 rounded-full font-medium transition-colors ${c.digit === digit ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}>
              {c.digit}
            </Link>
          ))}
        </div>

        <div className="space-y-8">
          {groups.map((g) => (
            <section key={g.parent}>
              <h2 className="text-sm font-bold text-gray-400 uppercase tracking-wider mb-2">{L.group} {g.parent}</h2>
              <div className="overflow-hidden rounded-xl border border-gray-100">
                <table className="w-full text-sm">
                  <tbody className="divide-y divide-gray-100">
                    {g.accounts.map((a) => (
                      <tr key={a.code} className="hover:bg-indigo-50/40">
                        <td className="px-4 py-2 font-mono font-semibold text-gray-900 w-24 align-top">{a.code}</td>
                        <td className="px-4 py-2 text-gray-700">
                          <span className={`mr-2 inline-block rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase ${TYPE_BADGE[a.type] ?? 'bg-gray-100 text-gray-600'}`}>{a.type}</span>
                          {a.name}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </section>
          ))}
        </div>

        <div className="mt-16 bg-gradient-to-r from-indigo-600 to-cyan-500 rounded-2xl p-8 text-center text-white">
          <h2 className="text-2xl font-extrabold mb-3">{locale === 'mk' ? 'Автоматско книжење на овие сметки' : locale === 'sq' ? 'Kontabilizim automatik i këtyre llogarive' : locale === 'tr' ? 'Bu hesapların otomatik kaydı' : 'Automatic posting to these accounts'}</h2>
          <a href="https://app.facturino.mk/signup" className="inline-block mt-2 bg-white text-indigo-700 font-bold px-8 py-3.5 rounded-xl hover:bg-indigo-50 transition-colors text-lg shadow-lg">
            {locale === 'mk' ? 'Пробај бесплатно →' : locale === 'sq' ? 'Provoni falas →' : locale === 'tr' ? 'Ücretsiz deneyin →' : 'Try it free →'}
          </a>
        </div>
      </div>
    </main>
  )
}
// CLAUDE-CHECKPOINT
