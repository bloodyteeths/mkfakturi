<template>
  <div class="grid gap-8 pt-10">
    <!-- Filters Card -->
    <div class="p-6 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Account selector -->
        <BaseInputGroup :label="$t('reports.accounting.cash_book.select_account', 'Cash Account')" required>
          <BaseMultiselect
            v-model="filters.account_code"
            :options="cashAccounts"
            :searchable="true"
            track-by="code"
            label="display_name"
            value-prop="code"
            :loading="isLoadingAccounts"
            :placeholder="$t('reports.accounting.cash_book.select_account_placeholder', 'Select cash account...')"
          />
        </BaseInputGroup>

        <!-- Start date -->
        <BaseInputGroup :label="$t('general.from_date')" required>
          <BaseDatePicker
            v-model="filters.start_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <!-- End date -->
        <BaseInputGroup :label="$t('general.to_date')" required>
          <BaseDatePicker
            v-model="filters.end_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <!-- Load button -->
        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
            :loading="isLoading"
            :disabled="!canLoad"
            @click="loadCashBook"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="entries.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="rounded-lg bg-blue-50 shadow p-6">
        <p class="text-sm font-medium text-blue-700">{{ $t('reports.accounting.cash_book.opening_balance', 'Opening Balance') }}</p>
        <p class="mt-1 text-2xl font-bold text-blue-900">{{ formatMoney(openingBalance) }}</p>
      </div>
      <div class="rounded-lg bg-green-50 shadow p-6">
        <p class="text-sm font-medium text-green-700">{{ $t('reports.accounting.cash_book.cash_in', 'Cash In (Debit)') }}</p>
        <p class="mt-1 text-2xl font-bold text-green-900">{{ formatMoney(totalDebit) }}</p>
      </div>
      <div class="rounded-lg bg-red-50 shadow p-6">
        <p class="text-sm font-medium text-red-700">{{ $t('reports.accounting.cash_book.cash_out', 'Cash Out (Credit)') }}</p>
        <p class="mt-1 text-2xl font-bold text-red-900">{{ formatMoney(totalCredit) }}</p>
      </div>
      <div class="rounded-lg bg-purple-50 shadow p-6">
        <p class="text-sm font-medium text-purple-700">{{ $t('reports.accounting.cash_book.closing_balance', 'Closing Balance') }}</p>
        <p class="mt-1 text-2xl font-bold text-purple-900">{{ formatMoney(closingBalance) }}</p>
      </div>
    </div>

    <!-- Cash Book Table -->
    <div v-if="entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <div>
          <h3 class="text-lg font-medium text-gray-900">{{ selectedAccountName }}</h3>
          <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
        </div>
        <div class="flex space-x-2">
          <BaseButton variant="primary-outline" @click="exportCsv">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            CSV
          </BaseButton>
          <BaseButton variant="primary" :loading="isExportingPdf" @click="exportPdf">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="DocumentArrowDownIcon" />
            </template>
            PDF
          </BaseButton>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.cash_book.document', 'Document') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.cash_book.cash_in', 'Cash In') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.cash_book.cash_out', 'Cash Out') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.cash_book.balance', 'Balance') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <!-- Opening balance row -->
            <tr class="bg-blue-50">
              <td class="px-4 py-3 text-sm font-medium text-blue-800">{{ filters.start_date }}</td>
              <td class="px-4 py-3 text-sm text-blue-800" colspan="2">{{ $t('reports.accounting.cash_book.opening_balance', 'Opening Balance') }}</td>
              <td class="px-4 py-3"></td>
              <td class="px-4 py-3"></td>
              <td class="px-4 py-3 text-sm text-right font-bold text-blue-800">{{ formatMoney(openingBalance) }}</td>
            </tr>
            <tr v-for="(entry, i) in entries" :key="i" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ formatDate(entry.date) }}</td>
              <td class="px-4 py-3 text-sm text-primary-600 font-medium">{{ entry.reference || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ entry.description || '-' }}</td>
              <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">{{ entry.debit > 0 ? formatMoney(entry.debit) : '' }}</td>
              <td class="px-4 py-3 text-sm text-right text-red-600 font-medium">{{ entry.credit > 0 ? formatMoney(entry.credit) : '' }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium" :class="entry.runningBalance < 0 ? 'text-red-600' : 'text-gray-900'">
                {{ formatMoney(entry.runningBalance) }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-100 font-semibold">
            <tr>
              <td colspan="3" class="px-4 py-3 text-sm">{{ $t('general.total') }} ({{ entries.length }} {{ $t('reports.accounting.cash_book.entries_label', 'entries') }})</td>
              <td class="px-4 py-3 text-sm text-right text-green-700">{{ formatMoney(totalDebit) }}</td>
              <td class="px-4 py-3 text-sm text-right text-red-700">{{ formatMoney(totalCredit) }}</td>
              <td class="px-4 py-3 text-sm text-right font-bold">{{ formatMoney(closingBalance) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="hasSearched && !isLoading && entries.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('reports.accounting.cash_book.no_data', 'No cash book entries') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('reports.accounting.cash_book.no_data_description', 'No transactions found for the selected period and account.') }}</p>
    </div>

    <!-- Initial State -->
    <div v-if="!hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('reports.accounting.cash_book.select_prompt', 'Select a cash account') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('reports.accounting.cash_book.select_hint', 'Choose a cash account (e.g. 100 Cash) and date range to view the cash book.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAccountStore } from '@/scripts/admin/stores/account'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const accountStore = useAccountStore()
const companyStore = useCompanyStore()
const globalStore = useGlobalStore()

globalStore.downloadReport = downloadReport

const cashAccounts = ref([])
const entries = ref([])
const glData = ref(null)
const isLoading = ref(false)
const isLoadingAccounts = ref(false)
const isExportingPdf = ref(false)
const hasSearched = ref(false)

const filters = ref({
  account_code: null,
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: new Date().toISOString().slice(0, 10),
})

const canLoad = computed(() => {
  return filters.value.account_code && filters.value.start_date && filters.value.end_date
})

const selectedAccountName = computed(() => {
  if (!filters.value.account_code) return ''
  const account = cashAccounts.value.find(a => a.code === filters.value.account_code)
  return account ? account.display_name : filters.value.account_code
})

const openingBalance = computed(() => {
  if (!glData.value) return 0
  return (glData.value.opening_debit || 0) - (glData.value.opening_credit || 0)
})

const totalDebit = computed(() => entries.value.reduce((s, e) => s + (e.debit || 0), 0))
const totalCredit = computed(() => entries.value.reduce((s, e) => s + (e.credit || 0), 0))
const closingBalance = computed(() => openingBalance.value + totalDebit.value - totalCredit.value)

onMounted(async () => {
  isLoadingAccounts.value = true
  try {
    const response = await accountStore.fetchAccounts({ active: true })
    const allAccounts = response.data.data || []
    // Filter to cash accounts (codes starting with 10)
    cashAccounts.value = allAccounts
      .filter(a => a.code && (a.code.startsWith('10') || a.code === '10'))
      .map(a => ({ ...a, display_name: `${a.code} - ${a.name}` }))
    // Auto-select if only one
    if (cashAccounts.value.length === 1) {
      filters.value.account_code = cashAccounts.value[0].code
    }
  } catch (error) {
    console.error('Failed to load accounts:', error)
  } finally {
    isLoadingAccounts.value = false
  }
})

async function loadCashBook() {
  if (!canLoad.value) return

  isLoading.value = true
  hasSearched.value = true
  entries.value = []
  glData.value = null

  try {
    const response = await window.axios.get('/accounting/cash-book', {
      params: {
        account_code: filters.value.account_code,
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
      },
    })

    const data = response.data?.data || response.data
    glData.value = data

    // Calculate running balance for each entry
    let running = (data.opening_debit || 0) - (data.opening_credit || 0)
    entries.value = (data.entries || []).map(e => {
      running += (e.debit || 0) - (e.credit || 0)
      return { ...e, runningBalance: running }
    })
  } catch (error) {
    console.error('Failed to load cash book:', error)
  } finally {
    isLoading.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return dateStr
    return d.toLocaleDateString('mk-MK', { year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC' })
  } catch { return dateStr }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const currency = companyStore.selectedCompanyCurrency
  const absAmount = Math.abs(amount)
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(absAmount)
  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} ${currency?.code || 'MKD'}`
}

function exportCsv() {
  if (!entries.value.length) return
  const headers = ['Date', 'Document', 'Description', 'Cash In', 'Cash Out', 'Balance']
  const rows = entries.value.map(e => [
    e.date || '',
    e.reference || '',
    e.description || '',
    e.debit > 0 ? (e.debit / 100).toFixed(2) : '',
    e.credit > 0 ? (e.credit / 100).toFixed(2) : '',
    (e.runningBalance / 100).toFixed(2),
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `kasova_kniga_${filters.value.account_code}_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
}

async function exportPdf() {
  if (!canLoad.value) return
  isExportingPdf.value = true
  try {
    const response = await window.axios.get('/accounting/cash-book/export', {
      params: {
        account_code: filters.value.account_code,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `kasova_kniga_${filters.value.account_code}_${filters.value.start_date}_${filters.value.end_date}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('PDF export failed:', error)
  } finally {
    isExportingPdf.value = false
  }
}

function downloadReport() {
  exportPdf()
}
</script>

// CLAUDE-CHECKPOINT: CashBook report component
