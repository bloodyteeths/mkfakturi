import { ACCOUNTS } from './chartOfAccountsData'

export type Account = { code: string; parent: string; type: string; name: string }

export type ClassMeta = {
  digit: string
  type: string
  name: { mk: string; en: string; sq: string; tr: string }
}

// The nine account classes of the Macedonian chart (Правилник 174/2011).
// Class 5 does not exist in the MK chart.
export const CHART_CLASSES: ClassMeta[] = [
  { digit: '0', type: 'asset', name: { mk: 'Нетековни средства', en: 'Non-current assets', sq: 'Mjete afatgjata', tr: 'Duran varlıklar' } },
  { digit: '1', type: 'asset', name: { mk: 'Парични средства и краткорочни побарувања', en: 'Cash and short-term receivables', sq: 'Mjete monetare dhe të arkëtueshme afatshkurtra', tr: 'Nakit ve kısa vadeli alacaklar' } },
  { digit: '2', type: 'liability', name: { mk: 'Обврски и резервирања', en: 'Liabilities and provisions', sq: 'Detyrime dhe provizione', tr: 'Yükümlülükler ve karşılıklar' } },
  { digit: '3', type: 'asset', name: { mk: 'Залихи на суровини, материјали и ситен инвентар', en: 'Inventory of raw materials and supplies', sq: 'Inventar i lëndëve të para dhe materialeve', tr: 'Hammadde ve malzeme stokları' } },
  { digit: '4', type: 'expense', name: { mk: 'Трошоци и расходи од работењето', en: 'Operating costs and expenses', sq: 'Kosto dhe shpenzime operative', tr: 'Faaliyet maliyetleri ve giderleri' } },
  { digit: '6', type: 'asset', name: { mk: 'Залихи на производство, готови производи и стоки', en: 'Work-in-progress, finished goods and merchandise', sq: 'Inventar prodhimi, produkte të gatshme dhe mallra', tr: 'Üretim, mamul ve ticari mal stokları' } },
  { digit: '7', type: 'revenue', name: { mk: 'Приходи', en: 'Revenues', sq: 'Të ardhura', tr: 'Gelirler' } },
  { digit: '8', type: 'result', name: { mk: 'Резултати од работењето', en: 'Operating results', sq: 'Rezultatet operative', tr: 'Faaliyet sonuçları' } },
  { digit: '9', type: 'equity', name: { mk: 'Капитал, резерви и вонбилансна евиденција', en: 'Capital, reserves and off-balance records', sq: 'Kapitali, rezervat dhe evidenca jashtë bilancit', tr: 'Sermaye, yedekler ve bilanço dışı kayıtlar' } },
]

export const ACCOUNTS_TYPED: Account[] = ACCOUNTS.map(([code, parent, type, name]) => ({ code, parent, type, name }))

export function classMeta(digit: string): ClassMeta | undefined {
  return CHART_CLASSES.find((c) => c.digit === digit)
}

export function accountsForClass(digit: string): Account[] {
  return ACCOUNTS_TYPED.filter((a) => a.code.startsWith(digit))
}

// Group a class's accounts by their 3-digit parent (synthetic) code.
export function groupsForClass(digit: string): { parent: string; accounts: Account[] }[] {
  const map = new Map<string, Account[]>()
  for (const a of accountsForClass(digit)) {
    const list = map.get(a.parent) ?? []
    list.push(a)
    map.set(a.parent, list)
  }
  return Array.from(map.entries())
    .sort((a, b) => a[0].localeCompare(b[0]))
    .map(([parent, accounts]) => ({ parent, accounts: accounts.sort((x, y) => x.code.localeCompare(y.code)) }))
}

export const TOTAL_ACCOUNTS = ACCOUNTS_TYPED.length
