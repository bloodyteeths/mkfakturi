<template>
  <div class="space-y-6">
    <!-- Loading -->
    <div v-if="store.isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-48"></div>
        <div v-for="i in 6" :key="i" class="flex space-x-4">
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <template v-else-if="store.summaryData">
      <!-- Profit/Loss Summary Card -->
      <div :class="[
        'rounded-lg shadow p-6',
        store.summaryData.summary.net_profit_before_tax >= 0 ? 'bg-green-50' : 'bg-red-50'
      ]">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
          {{ t('partner.accounting.year_end.result_for_year', { year: store.year }) }}
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ t('partner.accounting.year_end.revenue') }}</p>
            <p class="text-xl font-semibold text-gray-900">{{ formatMoney(store.summaryData.summary.total_revenue) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ t('partner.accounting.year_end.expenses') }}</p>
            <p class="text-xl font-semibold text-gray-900">{{ formatMoney(store.summaryData.summary.total_expenses) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ t('partner.accounting.year_end.profit_before_tax') }}</p>
            <p :class="['text-xl font-semibold', store.summaryData.summary.net_profit_before_tax >= 0 ? 'text-green-700' : 'text-red-700']">
              {{ formatMoney(store.summaryData.summary.net_profit_before_tax) }}
            </p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ t('partner.accounting.year_end.tax_percent', { rate: '10' }) }}</p>
            <p class="text-xl font-semibold text-gray-900">{{ formatMoney(store.summaryData.summary.income_tax) }}</p>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200">
          <p class="text-xs text-gray-500 uppercase">{{ t('partner.accounting.year_end.net_profit_loss') }}</p>
          <p :class="['text-2xl font-bold', store.summaryData.summary.net_profit_after_tax >= 0 ? 'text-green-700' : 'text-red-700']">
            {{ formatMoney(store.summaryData.summary.net_profit_after_tax) }}
          </p>
        </div>
      </div>

      <!-- Trial Balance -->
      <div v-if="store.summaryData.trial_balance" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h4 class="text-md font-medium text-gray-900">{{ t('partner.accounting.year_end.trial_balance_title') }}</h4>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('partner.accounting.year_end.account') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('partner.accounting.year_end.debit') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('partner.accounting.year_end.credit') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(account, i) in store.summaryData.trial_balance.accounts" :key="i">
                <td class="px-6 py-3 text-sm text-gray-900">{{ account.name }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ account.debit > 0 ? formatMoney(account.debit) : '' }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ account.credit > 0 ? formatMoney(account.credit) : '' }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr class="font-semibold">
                <td class="px-6 py-3 text-sm text-gray-900">{{ t('partner.accounting.year_end.total') }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(store.summaryData.trial_balance.total_debits) }}</td>
                <td class="px-6 py-3 text-sm text-right text-gray-900">{{ formatMoney(store.summaryData.trial_balance.total_credits) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Errors -->
      <div v-if="store.summaryData.has_error" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <p class="text-sm text-yellow-700">
          {{ t('partner.accounting.year_end.reports_unavailable') }}
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useYearEndClosingStore } from '@/scripts/admin/stores/year-end-closing'

const { t } = useI18n()
const store = useYearEndClosingStore()

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Math.abs(amount))
  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} МКД`
}

onMounted(() => {
  if (!store.summaryData) {
    store.fetchSummary()
  }
})
</script>
// CLAUDE-CHECKPOINT
