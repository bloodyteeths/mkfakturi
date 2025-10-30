import { createI18n } from 'vue-i18n'

export default (messages) => {
  // Check localStorage for saved language preference, default to Macedonian
  const savedLocale = localStorage.getItem('invoiceshelf_locale') || 'mk'
  
  return createI18n({
    locale: savedLocale,
    fallbackLocale: 'en',
    messages
  })
}
