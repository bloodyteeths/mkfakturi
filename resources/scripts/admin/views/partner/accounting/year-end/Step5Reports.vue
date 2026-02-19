<template>
  <div class="space-y-6">
    <div class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ t('partner.accounting.year_end.step5_title') }}
      </h3>
      <p class="text-sm text-gray-500 mb-6">
        {{ t('partner.accounting.year_end.step5_desc') }}
      </p>
    </div>

    <!-- Paper Submission -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center mb-4">
        <BaseIcon name="DocumentTextIcon" class="h-6 w-6 text-gray-400 mr-2" />
        <h4 class="text-md font-medium text-gray-900">{{ t('partner.accounting.year_end.paper_submission') }}</h4>
      </div>
      <p class="text-sm text-gray-500 mb-4">
        {{ t('partner.accounting.year_end.paper_deadline') }}
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <button
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          @click="downloadReport('balance-sheet')"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 w-5 text-primary-500 mr-3" />
          <span class="text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.balance_sheet') }}</span>
        </button>
        <button
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          @click="downloadReport('income-statement')"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 w-5 text-primary-500 mr-3" />
          <span class="text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.income_statement') }}</span>
        </button>
        <button
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          @click="downloadReport('trial-balance')"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 w-5 text-primary-500 mr-3" />
          <span class="text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.trial_balance_report') }}</span>
        </button>
        <button
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          @click="downloadReport('notes')"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 w-5 text-primary-500 mr-3" />
          <span class="text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.notes') }}</span>
        </button>
      </div>
    </div>

    <!-- Electronic Submission -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center mb-4">
        <BaseIcon name="ComputerDesktopIcon" class="h-6 w-6 text-gray-400 mr-2" />
        <h4 class="text-md font-medium text-gray-900">{{ t('partner.accounting.year_end.electronic_submission') }}</h4>
      </div>
      <p class="text-sm text-gray-500 mb-4">
        {{ t('partner.accounting.year_end.electronic_deadline') }}
      </p>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <button
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          @click="exportJournal('pantheon')"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 w-5 text-blue-500 mr-3" />
          <span class="text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.pantheon_xml') }}</span>
        </button>
        <button
          class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          @click="exportJournal('zonel')"
        >
          <BaseIcon name="ArrowDownTrayIcon" class="h-5 w-5 text-blue-500 mr-3" />
          <span class="text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.zonel_csv') }}</span>
        </button>
      </div>
      <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
        <p class="text-sm text-blue-700">
          {{ t('partner.accounting.year_end.upload_export_to') }}
          <a href="https://e-submit.crm.com.mk/sso/login.aspx" target="_blank" rel="noopener" class="font-medium underline">
            e-submit.crm.com.mk
          </a>
        </p>
      </div>
    </div>

    <!-- Corporate Tax DB-VP -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center mb-4">
        <BaseIcon name="CalculatorIcon" class="h-6 w-6 text-gray-400 mr-2" />
        <h4 class="text-md font-medium text-gray-900">{{ t('partner.accounting.year_end.corporate_tax') }}</h4>
      </div>
      <p class="text-sm text-gray-500 mb-4">
        {{ t('partner.accounting.year_end.corporate_tax_deadline') }}
      </p>

      <div v-if="taxSummary" class="overflow-x-auto">
        <div class="mb-3 text-xs text-gray-500">
          {{ taxSummary.form_name || 'Даночен биланс на вкупен приход' }} ({{ taxSummary.form }})
        </div>
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Ред.</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Позиција</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">Износ</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <template v-if="taxSummary.rows">
              <tr v-for="row in taxSummary.rows" :key="row.row" :class="[5, 7, 9].includes(row.row) ? 'bg-gray-50 font-medium' : ''">
                <td class="px-4 py-2 text-sm text-gray-500">{{ row.row }}</td>
                <td class="px-4 py-2 text-sm text-gray-900">{{ row.label }}</td>
                <td class="px-4 py-2 text-sm text-right font-medium" :class="row.row === 9 && row.value > 0 ? 'text-red-600' : 'text-gray-900'">
                  {{ typeof row.value === 'string' ? row.value : formatMoney(row.value) }}
                </td>
              </tr>
            </template>
            <template v-else>
              <tr>
                <td class="px-4 py-2 text-sm text-gray-600" colspan="2">{{ t('partner.accounting.year_end.total_revenue') }}</td>
                <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatMoney(taxSummary.summary?.total_revenue ?? taxSummary.revenue) }}</td>
              </tr>
              <tr>
                <td class="px-4 py-2 text-sm text-gray-600" colspan="2">{{ t('partner.accounting.year_end.total_expenses') }}</td>
                <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatMoney(taxSummary.summary?.total_expenses ?? taxSummary.expenses) }}</td>
              </tr>
              <tr class="bg-gray-50">
                <td class="px-4 py-2 text-sm font-medium text-gray-900" colspan="2">{{ t('partner.accounting.year_end.profit_before_tax') }}</td>
                <td class="px-4 py-2 text-sm text-right font-bold text-gray-900">{{ formatMoney(taxSummary.summary?.profit_before_tax ?? taxSummary.profit_before_tax) }}</td>
              </tr>
              <tr>
                <td class="px-4 py-2 text-sm text-gray-600" colspan="2">{{ t('partner.accounting.year_end.income_tax_rate', { rate: 10 }) }}</td>
                <td class="px-4 py-2 text-sm text-right font-medium text-red-600">{{ formatMoney(taxSummary.summary?.income_tax ?? taxSummary.income_tax) }}</td>
              </tr>
              <tr class="bg-green-50">
                <td class="px-4 py-2 text-sm font-medium text-green-900" colspan="2">{{ t('partner.accounting.year_end.net_profit') }}</td>
                <td class="px-4 py-2 text-sm text-right font-bold text-green-700">{{ formatMoney(taxSummary.summary?.net_profit ?? taxSummary.net_profit) }}</td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-700">
          {{ t('partner.accounting.year_end.db_vp_instructions') }}
          <a href="https://etax.ujp.gov.mk" target="_blank" rel="noopener" class="font-medium underline">
            etax.ujp.gov.mk
          </a>
          <span class="text-xs text-yellow-600 ml-1">({{ t('partner.accounting.year_end.gov_ssl_note') }})</span>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const store = useYearEndClosingStore()
const notificationStore = useNotificationStore()
const taxSummary = ref(null)
const isDownloading = ref(null)

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Math.abs(amount)) + ' МКД'
}

function triggerBlobDownload(data, filename, contentType) {
  const blob = new Blob([data], { type: contentType })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}

async function downloadReport(type) {
  isDownloading.value = type
  try {
    const response = await axios.get(`/year-end/${store.year}/reports/${type}`, {
      params: { format: 'pdf' },
      responseType: 'blob',
    })

    const contentType = response.headers['content-type'] || 'application/pdf'
    const ext = contentType.includes('pdf') ? 'pdf' : 'csv'
    triggerBlobDownload(response.data, `${type}-${store.year}.${ext}`, contentType)
  } catch {
    notificationStore.showNotification({ type: 'error', message: t('partner.accounting.year_end.download_error') })
  } finally {
    isDownloading.value = null
  }
}

async function exportJournal(format) {
  isDownloading.value = format
  try {
    const response = await axios.get('/accounting/journals/export', {
      params: {
        from: `${store.year}-01-01`,
        to: `${store.year}-12-31`,
        format: format,
      },
      responseType: 'blob',
    })

    const ext = format === 'pantheon' ? 'xml' : 'csv'
    triggerBlobDownload(response.data, `journal-${format}-${store.year}.${ext}`, response.headers['content-type'])
  } catch (error) {
    let message = t('partner.accounting.year_end.export_error')
    // Try to read error from blob response
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        if (json.error) message = `${message}: ${json.error}`
      } catch { /* use default message */ }
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isDownloading.value = null
  }
}

onMounted(async () => {
  try {
    taxSummary.value = await store.fetchTaxSummary()
  } catch {
    // Tax summary is optional
  }
})
</script>
