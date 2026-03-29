import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import poMessages from '@/scripts/admin/i18n/payment-orders.js'

const localeMap = {
  mk: 'mk-MK',
  en: 'en-US',
  tr: 'tr-TR',
  sq: 'sq-AL',
}

const currencySymbols = {
  MKD: 'ден.',
  EUR: '€',
}

/**
 * Shared composable for Payment Orders pages.
 * Provides locale detection, i18n helper, and formatting utilities.
 *
 * Usage:
 *   const { t, formatMoney, formatDate, formatLabel, statusClass, statusLabel, currentLocale } = usePaymentOrders()
 */
export function usePaymentOrders() {
  const currentLocale = ref(document.documentElement.lang || 'mk')
  const formattedLocale = computed(() => localeMap[currentLocale.value] || 'mk-MK')

  let observer = null

  onMounted(() => {
    observer = new MutationObserver(() => {
      currentLocale.value = document.documentElement.lang || 'mk'
    })
    observer.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['lang'],
    })
  })

  onBeforeUnmount(() => {
    if (observer) {
      observer.disconnect()
      observer = null
    }
  })

  /**
   * Translate a payment-orders i18n key.
   * Falls back to English, then returns the raw key.
   */
  function t(key, fallback) {
    return (
      poMessages[currentLocale.value]?.payment_orders?.[key] ||
      poMessages['en']?.payment_orders?.[key] ||
      fallback ||
      key
    )
  }

  /**
   * Format an amount in cents to a locale-aware money string.
   * @param {number|null|undefined} amount - Amount in cents
   * @param {string} [currencyCode='MKD'] - Currency code (MKD or EUR)
   * @returns {string} Formatted string, e.g. "1,234.56 ден." or "€1,234.56"
   */
  function formatMoney(amount, currencyCode = 'MKD') {
    if (amount === null || amount === undefined) return '-'
    const value = Math.abs(amount) / 100
    const sign = amount < 0 ? '-' : ''
    const formatted = new Intl.NumberFormat(formattedLocale.value, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value)

    const code = (currencyCode || 'MKD').toUpperCase()
    if (code === 'EUR') {
      return sign + '€' + formatted
    }
    // Default: MKD (ден.)
    const suffix = currencySymbols[code] || currencySymbols.MKD
    return sign + formatted + ' ' + suffix
  }

  /**
   * Format a date string using the current locale.
   * @param {string|null} dateStr - ISO date string
   * @returns {string} Formatted date or '-'
   */
  function formatDate(dateStr) {
    if (!dateStr) return '-'
    const d = new Date(dateStr)
    return d.toLocaleDateString(formattedLocale.value, {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    })
  }

  /**
   * Format a payment order format code to its display label.
   * @param {string} format - e.g. 'pp30', 'sepa_sct'
   * @returns {string} Display label, e.g. 'PP30', 'SEPA'
   */
  function formatLabel(format) {
    const labels = {
      pp30: 'PP30',
      pp50: 'PP50',
      sepa_sct: 'SEPA',
      csv: 'CSV',
    }
    return labels[format] || format
  }

  /**
   * Return Tailwind classes for a batch status badge.
   * @param {string} status
   * @returns {string} Tailwind class string
   */
  function statusClass(status) {
    const classes = {
      draft: 'bg-gray-100 text-gray-700',
      pending_approval: 'bg-yellow-100 text-yellow-700',
      approved: 'bg-blue-100 text-blue-700',
      exported: 'bg-indigo-100 text-indigo-700',
      sent_to_bank: 'bg-purple-100 text-purple-700',
      confirmed: 'bg-green-100 text-green-700',
      cancelled: 'bg-red-100 text-red-700',
    }
    return classes[status] || 'bg-gray-100 text-gray-700'
  }

  /**
   * Return a translated label for a batch status.
   * @param {string} status
   * @returns {string} Translated status label
   */
  function statusLabel(status) {
    const labels = {
      draft: t('status_draft'),
      pending_approval: t('status_pending', 'Pending'),
      approved: t('status_approved'),
      exported: t('status_exported'),
      sent_to_bank: t('status_sent', 'Sent to Bank'),
      confirmed: t('status_confirmed'),
      cancelled: t('cancelled', 'Cancelled'),
    }
    return labels[status] || status
  }

  return {
    currentLocale,
    formattedLocale,
    t,
    formatMoney,
    formatDate,
    formatLabel,
    statusClass,
    statusLabel,
  }
}

// CLAUDE-CHECKPOINT
