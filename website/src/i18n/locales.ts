export const locales = ['mk', 'sq', 'tr', 'en'] as const
export type Locale = typeof locales[number]
export const defaultLocale: Locale = 'mk'

export function isLocale(input: string): input is Locale {
  return (locales as readonly string[]).includes(input)
}

