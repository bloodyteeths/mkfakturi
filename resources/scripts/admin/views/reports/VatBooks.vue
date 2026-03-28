<template>
  <div class="grid gap-8 pt-10">
    <!-- Filters Card -->
    <div class="p-6 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            @click="loadVatBooks"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div v-if="hasSearched && !isLoading" class="mb-2 border-b border-gray-200">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium"
          :class="activeTab === tab.key
            ? 'border-primary-500 text-primary-600'
            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span v-if="tab.count > 0" class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
            {{ tab.count }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Rate Summary -->
    <div v-if="hasSearched && !isLoading && currentRateSummary.length > 0" class="bg-white rounded-lg shadow p-4">
      <h4 class="text-sm font-semibold text-gray-700 mb-3">
        {{ $t('reports.accounting.vat_books.rate_summary', 'Summary by VAT Rate') }}
      </h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <div
          v-for="summary in currentRateSummary"
          :key="summary.rate"
          class="flex items-center justify-between rounded-lg border p-3"
          :class="summary.rate === 18 ? 'border-blue-200 bg-blue-50' : summary.rate === 5 ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'"
        >
          <div>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold" :class="rateClass(summary.rate)">
              {{ summary.rate }}%
            </span>
            <span class="ml-2 text-xs text-gray-500">{{ summary.count }} inv.</span>
          </div>
          <div class="text-right">
            <div class="text-xs text-gray-500">{{ $t('reports.accounting.vat_books.taxable_base', 'Base') }}: {{ formatMoney(summary.taxable_base) }}</div>
            <div class="text-sm font-semibold" :class="summary.rate === 18 ? 'text-blue-700' : summary.rate === 5 ? 'text-green-700' : 'text-gray-700'">
              {{ $t('reports.accounting.vat_books.vat_amount', 'VAT') }}: {{ formatMoney(summary.vat_amount) }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Invoice Table -->
    <div v-if="currentEntries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-medium text-gray-900">
            {{ activeTab === 'output'
              ? $t('reports.accounting.vat_books.output_book', 'Output Book (Sales)')
              : $t('reports.accounting.vat_books.input_book', 'Input Book (Purchases)') }}
          </h3>
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
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.vat_books.invoice_number', 'Number') }}</th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ activeTab === 'output'
                  ? $t('reports.accounting.vat_books.customer', 'Customer')
                  : $t('reports.accounting.vat_books.supplier', 'Supplier') }}
              </th>
              <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.vat_books.tax_id', 'Tax ID') }}</th>
              <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.vat_books.taxable_base', 'Base') }}</th>
              <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.vat_books.rate', 'Rate') }}</th>
              <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.vat_books.vat_amount', 'VAT') }}</th>
              <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.vat_books.total', 'Total') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr
              v-for="(entry, i) in currentEntries"
              :key="entry.id"
              class="hover:bg-gray-50"
              :class="{
                'bg-red-50': entry.doc_type === 'credit_note',
                'bg-yellow-50': entry.vat_amount === 0 && entry.total > 0 && entry.doc_type !== 'credit_note',
              }"
            >
              <td class="px-3 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
              <td class="px-3 py-3 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
              <td class="px-3 py-3 text-sm font-medium">
                <span :class="entry.doc_type === 'credit_note' ? 'text-red-600' : 'text-primary-600'">
                  {{ entry.number }}
                </span>
                <span v-if="entry.doc_type === 'credit_note'" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">CN</span>
              </td>
              <td class="px-3 py-3 text-sm text-gray-900 max-w-xs truncate" :title="entry.party_name">{{ entry.party_name }}</td>
              <td class="px-3 py-3 text-sm text-gray-600 font-mono text-xs">{{ entry.party_tax_id || '-' }}</td>
              <td class="px-3 py-3 text-sm text-right" :class="entry.taxable_base < 0 ? 'text-red-600' : 'text-gray-900'">{{ formatMoney(entry.taxable_base) }}</td>
              <td class="px-3 py-3 text-sm text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold" :class="rateClass(entry.vat_rate)">
                  {{ entry.vat_rate }}%
                </span>
              </td>
              <td class="px-3 py-3 text-sm text-right" :class="entry.vat_amount < 0 ? 'text-red-600' : 'text-gray-900'">{{ formatMoney(entry.vat_amount) }}</td>
              <td class="px-3 py-3 text-sm text-right font-medium" :class="entry.total < 0 ? 'text-red-600' : 'text-gray-900'">{{ formatMoney(entry.total) }}</td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-100 font-semibold">
            <tr>
              <td colspan="5" class="px-3 py-3 text-sm">{{ $t('general.total') }} ({{ currentEntries.length }})</td>
              <td class="px-3 py-3 text-sm text-right">{{ formatMoney(currentTotals.taxable_base) }}</td>
              <td class="px-3 py-3"></td>
              <td class="px-3 py-3 text-sm text-right">{{ formatMoney(currentTotals.vat_amount) }}</td>
              <td class="px-3 py-3 text-sm text-right font-bold">{{ formatMoney(currentTotals.total) }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="hasSearched && !isLoading && outputEntries.length === 0 && inputEntries.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('reports.accounting.vat_books.no_data', 'No VAT book entries') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('reports.accounting.vat_books.no_data_description', 'No invoices or bills found for the selected period.') }}</p>
    </div>

    <!-- Initial State -->
    <div v-if="!hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('reports.accounting.vat_books.select_prompt', 'Select a period') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('reports.accounting.vat_books.select_hint', 'Choose a date range to view input and output VAT books.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const { t } = useI18n()
const companyStore = useCompanyStore()
const globalStore = useGlobalStore()

globalStore.downloadReport = downloadReport

const isLoading = ref(false)
const isExportingPdf = ref(false)
const hasSearched = ref(false)
const activeTab = ref('output')

const outputEntries = ref([])
const inputEntries = ref([])
const outputByRate = ref([])
const inputByRate = ref([])

const filters = ref({
  start_date: `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}-01`,
  end_date: new Date().toISOString().slice(0, 10),
})

const canLoad = computed(() => filters.value.start_date && filters.value.end_date)

const tabs = computed(() => [
  { key: 'output', label: t('reports.accounting.vat_books.output_tab', 'Output (Sales)'), count: outputEntries.value.length },
  { key: 'input', label: t('reports.accounting.vat_books.input_tab', 'Input (Purchases)'), count: inputEntries.value.length },
])

const currentEntries = computed(() => activeTab.value === 'output' ? outputEntries.value : inputEntries.value)
const currentRateSummary = computed(() => activeTab.value === 'output' ? outputByRate.value : inputByRate.value)

const currentTotals = computed(() => {
  const entries = currentEntries.value
  return {
    taxable_base: entries.reduce((s, e) => s + (e.taxable_base || 0), 0),
    vat_amount: entries.reduce((s, e) => s + (e.vat_amount || 0), 0),
    total: entries.reduce((s, e) => s + (e.total || 0), 0),
  }
})

async function loadVatBooks() {
  if (!canLoad.value) return
  isLoading.value = true
  hasSearched.value = true
  outputEntries.value = []
  inputEntries.value = []
  outputByRate.value = []
  inputByRate.value = []

  try {
    const response = await window.axios.get('/accounting/vat-books', {
      params: {
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
    })

    const data = response.data?.data || {}
    outputEntries.value = data.output || []
    inputEntries.value = data.input || []
    outputByRate.value = data.output_by_rate || []
    inputByRate.value = data.input_by_rate || []
  } catch (error) {
    console.error('Failed to load VAT books:', error)
  } finally {
    isLoading.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const currency = companyStore.selectedCompanyCurrency
  const absAmount = Math.abs(amount) / 100
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(absAmount)
  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} ${currency?.code || 'MKD'}`
}

function rateClass(rate) {
  if (rate >= 18) return 'bg-blue-100 text-blue-800'
  if (rate >= 5) return 'bg-green-100 text-green-800'
  return 'bg-gray-100 text-gray-600'
}

function exportCsv() {
  const entries = currentEntries.value
  if (!entries.length) return
  const headers = ['#', 'Date', 'Number', 'Party', 'Tax ID', 'Taxable Base', 'VAT Rate', 'VAT Amount', 'Total']
  const rows = entries.map((e, i) => [
    i + 1,
    e.date || '',
    e.number || '',
    e.party_name || '',
    e.party_tax_id || '',
    (e.taxable_base / 100).toFixed(2),
    e.vat_rate,
    (e.vat_amount / 100).toFixed(2),
    (e.total / 100).toFixed(2),
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  const type = activeTab.value === 'input' ? 'vlezni' : 'izlezni'
  link.download = `kniga_ddv_${type}_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
}

async function exportPdf() {
  isExportingPdf.value = true
  try {
    const response = await window.axios.get('/accounting/vat-books/export', {
      params: {
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
        type: activeTab.value,
      },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    const type = activeTab.value === 'input' ? 'vlezni' : 'izlezni'
    link.setAttribute('download', `kniga_ddv_${type}_${filters.value.start_date}_${filters.value.end_date}.pdf`)
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

// CLAUDE-CHECKPOINT: VatBooks report component
