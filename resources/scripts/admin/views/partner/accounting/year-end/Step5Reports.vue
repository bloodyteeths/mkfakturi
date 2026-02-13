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
          <a href="https://e-submit.crm.com.mk" target="_blank" rel="noopener" class="font-medium underline">
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
        <table class="min-w-full divide-y divide-gray-200">
          <tbody class="divide-y divide-gray-200">
            <tr>
              <td class="px-4 py-2 text-sm text-gray-600">{{ t('partner.accounting.year_end.total_revenue') }}</td>
              <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatMoney(taxSummary.revenue) }}</td>
            </tr>
            <tr>
              <td class="px-4 py-2 text-sm text-gray-600">{{ t('partner.accounting.year_end.total_expenses') }}</td>
              <td class="px-4 py-2 text-sm text-right font-medium text-gray-900">{{ formatMoney(taxSummary.expenses) }}</td>
            </tr>
            <tr class="bg-gray-50">
              <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ t('partner.accounting.year_end.profit_before_tax') }}</td>
              <td class="px-4 py-2 text-sm text-right font-bold text-gray-900">{{ formatMoney(taxSummary.profit_before_tax) }}</td>
            </tr>
            <tr>
              <td class="px-4 py-2 text-sm text-gray-600">{{ t('partner.accounting.year_end.income_tax_rate', { rate: taxSummary.income_tax_rate }) }}</td>
              <td class="px-4 py-2 text-sm text-right font-medium text-red-600">{{ formatMoney(taxSummary.income_tax) }}</td>
            </tr>
            <tr class="bg-green-50">
              <td class="px-4 py-2 text-sm font-medium text-green-900">{{ t('partner.accounting.year_end.net_profit') }}</td>
              <td class="px-4 py-2 text-sm text-right font-bold text-green-700">{{ formatMoney(taxSummary.net_profit) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-sm text-yellow-700">
          {{ t('partner.accounting.year_end.db_vp_instructions') }}
          <a href="https://etax.ujp.gov.mk" target="_blank" rel="noopener" class="font-medium underline">
            etax.ujp.gov.mk
          </a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const store = useYearEndClosingStore()
const notificationStore = useNotificationStore()
const taxSummary = ref(null)

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Math.abs(amount)) + ' МКД'
}

async function downloadReport(type) {
  // Use existing report export endpoint
  try {
    const companyId = window.axios.defaults.headers?.common?.company
    window.open(`/api/v1/year-end/${store.year}/reports/${type}`, '_blank')
  } catch {
    notificationStore.showNotification({ type: 'error', message: t('partner.accounting.year_end.download_error') })
  }
}

async function exportJournal(format) {
  try {
    window.open(`/api/v1/accounting/journals/export?format=${format}&start_date=${store.year}-01-01&end_date=${store.year}-12-31`, '_blank')
  } catch {
    notificationStore.showNotification({ type: 'error', message: t('partner.accounting.year_end.export_error') })
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
// CLAUDE-CHECKPOINT
