<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.cash_book', 'Касова Книга')">
      <template #actions>
        <div v-if="entries.length > 0" class="flex space-x-2">
          <BaseButton variant="primary-outline" @click="exportCsv">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            CSV
          </BaseButton>
          <BaseButton variant="primary" :loading="isExportingPdf" @click="previewPdf">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="EyeIcon" />
            </template>
            PDF
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="name"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>

    <!-- IFRS Not Enabled -->
    <div v-if="selectedCompanyId && ifrsChecked && !ifrsEnabled" class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-6">
      <div class="flex items-start">
        <BaseIcon name="ExclamationTriangleIcon" class="h-6 w-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" />
        <h3 class="text-sm font-medium text-yellow-800">{{ $t('partner.accounting.ifrs_not_enabled_title', 'Accounting is not enabled for this company') }}</h3>
      </div>
    </div>

    <template v-if="selectedCompanyId && ifrsEnabled">
      <!-- Filters -->
      <div class="p-6 bg-white rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <BaseInputGroup :label="$t('partner.accounting.cash_account', 'Сметка на каса')" required>
            <BaseMultiselect
              v-model="filters.account_id"
              :options="cashAccounts"
              :searchable="true"
              label="display_name"
              track-by="display_name"
              value-prop="id"
              :loading="isLoadingAccounts"
              :placeholder="$t('partner.accounting.select_cash_account', 'Изберете сметка...')"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('general.from_date')" required>
            <BaseDatePicker v-model="filters.start_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('general.to_date')" required>
            <BaseDatePicker v-model="filters.end_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <div class="flex items-end">
            <BaseButton variant="primary" class="w-full" :loading="isLoading" :disabled="!filters.account_id" @click="loadCashBook">
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
              </template>
              {{ $t('general.load') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Summary Cards -->
      <div v-if="entries.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="rounded-lg bg-blue-50 shadow p-6">
          <p class="text-sm font-medium text-blue-700">{{ $t('partner.accounting.opening_balance', 'Почетно салдо') }}</p>
          <p class="mt-1 text-2xl font-bold text-blue-900">{{ formatMoney(openingBalance) }}</p>
        </div>
        <div class="rounded-lg bg-green-50 shadow p-6">
          <p class="text-sm font-medium text-green-700">{{ $t('partner.accounting.cash_in', 'Прими (Должи)') }}</p>
          <p class="mt-1 text-2xl font-bold text-green-900">{{ formatMoney(totalCashIn) }}</p>
        </div>
        <div class="rounded-lg bg-red-50 shadow p-6">
          <p class="text-sm font-medium text-red-700">{{ $t('partner.accounting.cash_out', 'Издади (Побарува)') }}</p>
          <p class="mt-1 text-2xl font-bold text-red-900">{{ formatMoney(totalCashOut) }}</p>
        </div>
        <div class="rounded-lg bg-purple-50 shadow p-6">
          <p class="text-sm font-medium text-purple-700">{{ $t('partner.accounting.closing_balance', 'Крајно салдо') }}</p>
          <p class="mt-1 text-2xl font-bold text-purple-900">{{ formatMoney(closingBalance) }}</p>
        </div>
      </div>

      <!-- Cash Book Table -->
      <div v-if="entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">
            {{ selectedAccountName }}
          </h3>
          <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.document', 'Документ') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.cash_in', 'Прими') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.cash_out', 'Издади') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.balance', 'Салдо') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <!-- Opening balance row -->
              <tr class="bg-blue-50">
                <td class="px-4 py-3 text-sm font-medium text-blue-800">{{ filters.start_date }}</td>
                <td class="px-4 py-3 text-sm text-blue-800" colspan="2">{{ $t('partner.accounting.opening_balance', 'Почетно салдо') }}</td>
                <td class="px-4 py-3"></td>
                <td class="px-4 py-3"></td>
                <td class="px-4 py-3 text-sm text-right font-bold text-blue-800">{{ formatMoney(openingBalance) }}</td>
              </tr>
              <tr v-for="(entry, i) in entries" :key="i" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ formatDate(entry.date) }}</td>
                <td class="px-4 py-3 text-sm text-primary-600 font-medium">{{ entry.reference || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-600 max-w-xs truncate">{{ entry.description || entry.counterparty_name || '-' }}</td>
                <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">{{ entry.debit > 0 ? formatMoney(entry.debit) : '' }}</td>
                <td class="px-4 py-3 text-sm text-right text-red-600 font-medium">{{ entry.credit > 0 ? formatMoney(entry.credit) : '' }}</td>
                <td class="px-4 py-3 text-sm text-right font-medium" :class="entry.runningBalance < 0 ? 'text-red-600' : 'text-gray-900'">
                  {{ formatMoney(entry.runningBalance) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 font-semibold">
              <tr>
                <td colspan="3" class="px-4 py-3 text-sm">{{ $t('general.total') }} ({{ entries.length }} {{ $t('partner.accounting.entries_label', 'записи') }})</td>
                <td class="px-4 py-3 text-sm text-right text-green-700">{{ formatMoney(totalCashIn) }}</td>
                <td class="px-4 py-3 text-sm text-right text-red-700">{{ formatMoney(totalCashOut) }}</td>
                <td class="px-4 py-3 text-sm text-right font-bold">{{ formatMoney(closingBalance) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden p-6">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse mb-4">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded w-48"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
        </div>
      </div>

      <!-- Empty / Initial -->
      <div v-else-if="hasSearched && entries.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.no_cash_entries', 'Нема записи во касовата книга') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.no_cash_entries_desc', 'Нема пронајдено трансакции за избраниот период и сметка.') }}</p>
      </div>
      <div v-else-if="!hasSearched" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="BanknotesIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.select_cash_account_prompt', 'Изберете сметка за касова книга') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.cash_book_hint', 'Изберете сметка на каса (пр. 100 Готовина) за да ги видите влезовите и излезите.') }}</p>
      </div>
    </template>
    <PdfPreviewModal
      :show="showPdfPreview"
      :pdf-url="previewPdfUrl"
      :title="$t('partner.accounting.cash_book', 'Касова Книга')"
      @close="closePdfPreview"
      @download="downloadPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'
import PdfPreviewModal from './components/PdfPreviewModal.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const isLoading = ref(false)
const isLoadingAccounts = ref(false)
const isExportingPdf = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)
const hasSearched = ref(false)
const ifrsEnabled = ref(false)
const ifrsChecked = ref(false)
const entries = ref([])
const glData = ref(null)

function todayStr() {
  return new Date().toISOString().slice(0, 10)
}

const filters = ref({
  account_id: null,
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: todayStr(),
})

const companies = computed(() => consoleStore.managedCompanies || [])
const cashAccounts = ref([])

const selectedAccountName = computed(() => {
  const acct = cashAccounts.value.find(a => a.id === filters.value.account_id)
  return acct ? acct.display_name : ''
})

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const openingBalance = computed(() => {
  if (!glData.value) return 0
  return (glData.value.opening_debit || 0) - (glData.value.opening_credit || 0)
})

const totalCashIn = computed(() => entries.value.reduce((s, e) => s + (e.debit || 0), 0))
const totalCashOut = computed(() => entries.value.reduce((s, e) => s + (e.credit || 0), 0))
const closingBalance = computed(() => openingBalance.value + totalCashIn.value - totalCashOut.value)

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await checkIfrsStatus()
    if (ifrsEnabled.value) await loadAccounts()
  }
})

function onCompanyChange() {
  entries.value = []
  glData.value = null
  hasSearched.value = false
  filters.value.account_id = null
  ifrsEnabled.value = false
  ifrsChecked.value = false
  cashAccounts.value = []
  if (selectedCompanyId.value) {
    checkIfrsStatus().then(() => {
      if (ifrsEnabled.value) loadAccounts()
    })
  }
}

async function checkIfrsStatus() {
  if (!selectedCompanyId.value) return
  ifrsChecked.value = false
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/ifrs-status`)
    ifrsEnabled.value = response.data?.ifrs_enabled === true
  } catch {
    ifrsEnabled.value = false
  } finally {
    ifrsChecked.value = true
  }
}

async function loadAccounts() {
  if (!selectedCompanyId.value) return
  isLoadingAccounts.value = true
  try {
    await partnerAccountingStore.fetchAccounts(selectedCompanyId.value)
    // Filter to cash accounts (codes starting with 10)
    cashAccounts.value = (partnerAccountingStore.accounts || [])
      .filter(a => a.code && (a.code.startsWith('10') || a.code.startsWith('100') || a.code.startsWith('101') || a.code.startsWith('102') || a.code.startsWith('103') || a.code.startsWith('104') || a.code.startsWith('105')))
      .map(a => ({ ...a, display_name: `${a.code} - ${a.name}` }))
    // Auto-select if only one
    if (cashAccounts.value.length === 1) {
      filters.value.account_id = cashAccounts.value[0].id
    }
  } catch {
    cashAccounts.value = []
  } finally {
    isLoadingAccounts.value = false
  }
}

async function loadCashBook() {
  if (!selectedCompanyId.value || !filters.value.account_id) return
  isLoading.value = true
  hasSearched.value = true
  entries.value = []
  glData.value = null

  try {
    const selectedAccount = cashAccounts.value.find(a => a.id === filters.value.account_id)
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/general-ledger`, {
      params: {
        account_id: filters.value.account_id,
        account_code: selectedAccount?.code || null,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
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
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to load cash book',
    })
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
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  const precision = company?.currency?.precision ?? 2
  const displayAmount = Math.abs(amount) / 100
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: precision,
    maximumFractionDigits: precision,
  }).format(displayAmount)
  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} ${selectedCompanyCurrency.value}`
}

function exportCsv() {
  if (!entries.value.length) return
  const headers = ['Датум', 'Документ', 'Опис', 'Прими', 'Издади', 'Салдо']
  const rows = entries.value.map(e => [
    e.date || '',
    e.reference || '',
    e.description || e.counterparty_name || '',
    e.debit > 0 ? (e.debit / 100).toFixed(2) : '',
    e.credit > 0 ? (e.credit / 100).toFixed(2) : '',
    (e.runningBalance / 100).toFixed(2),
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `kasova_kniga_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
}

async function previewPdf() {
  if (!selectedCompanyId.value || !filters.value.account_id) return
  isExportingPdf.value = true
  try {
    const selectedAccount = cashAccounts.value.find(a => a.id === filters.value.account_id)
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/cash-book/export`, {
      params: {
        account_code: selectedAccount?.code || '100',
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    console.error('PDF export failed', error)
  } finally {
    isExportingPdf.value = false
  }
}

function downloadPdf() {
  if (!pdfBlob.value) return
  const selectedAccount = cashAccounts.value.find(a => a.id === filters.value.account_id)
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `kasova_kniga_${selectedAccount?.code || '100'}_${filters.value.start_date}_${filters.value.end_date}.pdf`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

function closePdfPreview() {
  showPdfPreview.value = false
  if (previewPdfUrl.value) {
    window.URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = null
  }
  pdfBlob.value = null
}
</script>

<!-- CLAUDE-CHECKPOINT -->
