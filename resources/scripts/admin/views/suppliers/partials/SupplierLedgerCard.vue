<template>
  <div class="mt-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">
        {{ $t('suppliers.ledger_card') }}
      </h3>

      <!-- Net Balance Badge -->
      <div v-if="meta.closing_balance !== undefined" class="flex items-center gap-2">
        <span class="text-sm text-gray-500">{{ $t('customers.net_balance') }}:</span>
        <span
          :class="[
            'px-3 py-1 rounded-full text-sm font-bold',
            meta.closing_balance > 0
              ? 'bg-green-100 text-green-700'
              : meta.closing_balance < 0
                ? 'bg-red-100 text-red-700'
                : 'bg-gray-100 text-gray-600'
          ]"
        >
          {{ formatAmount(meta.closing_balance) }}
          <span class="text-xs font-normal ml-1">
            {{ meta.closing_balance > 0 ? $t('customers.receivable') : meta.closing_balance < 0 ? $t('customers.payable') : '' }}
          </span>
        </span>
      </div>
    </div>

    <!-- Date Range Filters + Download -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600">{{ $t('general.from') }}:</label>
          <input
            v-model="fromDate"
            type="date"
            class="border border-gray-300 rounded px-2 py-1 text-sm"
          />
        </div>
        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600">{{ $t('general.to') }}:</label>
          <input
            v-model="toDate"
            type="date"
            class="border border-gray-300 rounded px-2 py-1 text-sm"
          />
        </div>
      </div>
      <button
        v-if="entries.length > 0"
        @click="downloadPdf"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        {{ $t('customers.download_ledger_pdf') }}
      </button>
    </div>

    <!-- Ledger Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('general.date') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('customers.document_type') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('customers.reference') }}
            </th>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('customers.account_code') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('customers.debit') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('customers.credit') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('customers.closing_balance') }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="isLoading">
            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
              {{ $t('general.loading') }}...
            </td>
          </tr>
          <tr v-else-if="entries.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-gray-400">
              {{ $t('general.no_records_found') }}
            </td>
          </tr>
          <tr
            v-for="(entry, index) in entries"
            :key="index"
            :class="entry.side === 'AP' ? 'bg-gray-50' : ''"
          >
            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
              {{ entry.date }}
            </td>
            <td class="px-4 py-2 text-sm whitespace-nowrap">
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                  typeClasses[entry.type] || 'bg-gray-100 text-gray-700'
                ]"
              >
                {{ typeLabels[entry.type] || entry.type }}
              </span>
              <span v-if="entry.side === 'AP'" class="ml-1 text-xs text-gray-400">AP</span>
            </td>
            <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">
              {{ entry.reference }}
            </td>
            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">
              <span v-if="entry.account_code" :title="entry.account_name">
                {{ entry.account_code }} — {{ entry.account_name }}
              </span>
              <span v-else class="text-gray-300">-</span>
            </td>
            <td class="px-4 py-2 text-sm text-right whitespace-nowrap">
              <span v-if="entry.debit" class="text-gray-900 font-medium">
                {{ formatAmount(entry.debit) }}
              </span>
              <span v-else class="text-gray-300">-</span>
            </td>
            <td class="px-4 py-2 text-sm text-right whitespace-nowrap">
              <span v-if="entry.credit" class="text-gray-900 font-medium">
                {{ formatAmount(entry.credit) }}
              </span>
              <span v-else class="text-gray-300">-</span>
            </td>
            <td class="px-4 py-2 text-sm text-right font-medium whitespace-nowrap"
                :class="entry.balance > 0 ? 'text-green-600' : entry.balance < 0 ? 'text-red-600' : 'text-gray-600'"
            >
              {{ formatAmount(entry.balance) }}
            </td>
          </tr>
        </tbody>
        <tfoot v-if="entries.length > 0" class="bg-gray-100 font-medium">
          <tr>
            <td colspan="4" class="px-4 py-3 text-sm text-gray-700 text-right">
              {{ $t('general.total') }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-900">
              {{ formatAmount(meta.total_debit) }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-900">
              {{ formatAmount(meta.total_credit) }}
            </td>
            <td class="px-4 py-3 text-sm text-right font-bold"
                :class="meta.closing_balance > 0 ? 'text-green-600' : meta.closing_balance < 0 ? 'text-red-600' : 'text-gray-600'"
            >
              {{ formatAmount(meta.closing_balance) }}
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const route = useRoute()

const entries = ref([])
const meta = ref({})
const isLoading = ref(false)

const now = new Date()
const fromDate = ref(`${now.getFullYear()}-01-01`)
const toDate = ref(`${now.getFullYear()}-12-31`)

const typeLabels = computed(() => ({
  invoice: t('invoices.invoice'),
  payment: t('payments.payment'),
  bill: t('bills.bill', 'Bill'),
  bill_payment: t('bills.payment', 'Bill Payment'),
}))

const typeClasses = {
  invoice: 'bg-blue-100 text-blue-700',
  payment: 'bg-green-100 text-green-700',
  bill: 'bg-orange-100 text-orange-700',
  bill_payment: 'bg-purple-100 text-purple-700',
}

function formatAmount(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount / 100)
}

function downloadPdf() {
  const url = `/api/v1/suppliers/${route.params.id}/ledger/pdf?from_date=${fromDate.value}&to_date=${toDate.value}&download=true`
  window.open(url, '_blank')
}

async function fetchLedger() {
  isLoading.value = true
  try {
    const response = await axios.get(`/suppliers/${route.params.id}/ledger`, {
      params: {
        from_date: fromDate.value,
        to_date: toDate.value,
      },
    })
    entries.value = response.data.data
    meta.value = response.data.meta
  } catch (e) {
    entries.value = []
    meta.value = {}
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchLedger)

watch([fromDate, toDate], fetchLedger)
</script>
