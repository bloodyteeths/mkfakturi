<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.general_ledger')">
      <template #actions>
        <div v-if="ledgerData && ledgerData.entries && ledgerData.entries.length > 0" class="flex space-x-2">
          <BaseButton
            variant="primary-outline"
            :loading="isExporting"
            @click="exportToCsv"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            CSV
          </BaseButton>
          <BaseButton
            variant="primary"
            :loading="isExportingPdf"
            @click="previewPdf"
          >
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
          aria-label="Select company"
        />
      </BaseInputGroup>
    </div>

    <!-- IFRS Not Enabled Warning -->
    <div
      v-if="selectedCompanyId && ifrsChecked && !ifrsEnabled"
      class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-6"
    >
      <div class="flex items-start">
        <BaseIcon name="ExclamationTriangleIcon" class="h-6 w-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" />
        <div class="flex-1">
          <h3 class="text-sm font-medium text-yellow-800">
            {{ $t('partner.accounting.ifrs_not_enabled_title', 'Accounting is not enabled for this company') }}
          </h3>
          <p class="mt-1 text-sm text-yellow-700">
            {{ $t('partner.accounting.ifrs_not_enabled_description', 'Enable double-entry accounting to start tracking journal entries, general ledger, and financial reports for this company.') }}
          </p>
          <BaseButton
            class="mt-3"
            variant="primary"
            :loading="isEnablingIfrs"
            @click="enableIfrs"
          >
            {{ $t('partner.accounting.enable_accounting', 'Enable Accounting') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Filters Card -->
    <div v-if="selectedCompanyId && ifrsEnabled" class="p-6 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Account selector -->
        <BaseInputGroup :label="$t('reports.accounting.general_ledger.select_account')" required>
          <BaseMultiselect
            v-model="filters.account_id"
            :options="accounts"
            :searchable="true"
            track-by="display_name"
            label="display_name"
            value-prop="id"
            :placeholder="$t('reports.accounting.general_ledger.select_account_placeholder')"
            :loading="isLoadingAccounts"
            aria-label="Select account"
          />
        </BaseInputGroup>

        <!-- Start date -->
        <BaseInputGroup :label="$t('general.from_date')" required>
          <BaseDatePicker
            v-model="filters.start_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
            aria-label="Start date"
          />
        </BaseInputGroup>

        <!-- End date -->
        <BaseInputGroup :label="$t('general.to_date')" required>
          <BaseDatePicker
            v-model="filters.end_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
            aria-label="End date"
          />
        </BaseInputGroup>

        <!-- Load button -->
        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
            :loading="isLoading"
            :disabled="!filters.account_id"
            @click="loadLedger"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading state with skeleton -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-48"></div>
        <div class="h-4 bg-gray-200 rounded w-24 mt-2"></div>
      </div>
      <div class="p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <!-- Ledger Table -->
    <div v-else-if="ledgerData && ledgerData.entries && ledgerData.entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Account Summary Header -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">
              {{ selectedAccountName }}
            </h3>
            <p v-if="selectedAccountCode" class="text-sm text-gray-500">{{ selectedAccountCode }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">
              {{ $t('reports.accounting.general_ledger.opening_balance') }}
            </p>
            <p class="text-lg font-semibold" :class="balanceColorClass(ledgerData.opening_balance)">
              {{ formatMoney(ledgerData.opening_balance) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Ledger entries table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="General Ledger Entries">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('general.date') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.document') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('general.description') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.balance') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="(entry, index) in ledgerData.entries" :key="index">
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ formatDate(entry.date) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm">
                <span class="font-medium text-primary-500">{{ entry.reference || '-' }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate">
                {{ entry.description || '-' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-right text-gray-900">
                {{ entry.debit > 0 ? formatMoney(entry.debit) : '' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-right text-gray-900">
                {{ entry.credit > 0 ? formatMoney(entry.credit) : '' }}
              </td>
              <td
                class="whitespace-nowrap px-6 py-4 text-sm text-right font-medium"
                :class="balanceColorClass(entry.running_balance)"
              >
                {{ formatMoney(entry.running_balance) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Closing Balance -->
      <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm font-medium text-gray-700">
              {{ $t('reports.accounting.general_ledger.closing_balance') }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-lg font-semibold" :class="balanceColorClass(ledgerData.closing_balance)">
              {{ formatMoney(ledgerData.closing_balance) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State - No Ledger Data after search -->
    <div
      v-else-if="hasSearched && (!ledgerData || !ledgerData.entries || ledgerData.entries.length === 0)"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('reports.accounting.general_ledger.no_data') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('reports.accounting.general_ledger.no_data_description') }}
      </p>
    </div>

    <!-- Initial State - Has company but no search yet -->
    <div
      v-else-if="selectedCompanyId && ifrsEnabled && !hasSearched"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('reports.accounting.general_ledger.select_and_load') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('reports.accounting.general_ledger.select_and_load_description') }}
      </p>
    </div>

    <!-- Select company message -->
    <div
      v-else-if="!selectedCompanyId"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>
    <PdfPreviewModal
      :show="showPdfPreview"
      :pdf-url="previewPdfUrl"
      :title="$t('partner.accounting.general_ledger')"
      @close="closePdfPreview"
      @download="downloadPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'
import PdfPreviewModal from './components/PdfPreviewModal.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const accounts = ref([])
const ledgerData = ref(null)
const isLoading = ref(false)
const isLoadingAccounts = ref(false)
const isExporting = ref(false)
const isExportingPdf = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)
const hasSearched = ref(false)
const ifrsEnabled = ref(false)
const ifrsChecked = ref(false)
const isEnablingIfrs = ref(false)

// AbortController for cancelling requests
let abortController = null

// Get current date in local timezone as YYYY-MM-DD
function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Get start of year in local timezone
function getStartOfYearString() {
  const now = new Date()
  return `${now.getFullYear()}-01-01`
}

const filters = ref({
  account_id: null,
  start_date: getStartOfYearString(),
  end_date: getLocalDateString(),
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const selectedAccountName = computed(() => {
  if (!filters.value.account_id) return ''
  const account = accounts.value.find(a => a.id === filters.value.account_id)
  return account?.name || ''
})

const selectedAccountCode = computed(() => {
  if (!filters.value.account_id) return ''
  const account = accounts.value.find(a => a.id === filters.value.account_id)
  return account?.code || ''
})

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()

    // Auto-select first company if available
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
      await checkIfrsStatus()
      if (ifrsEnabled.value) {
        await loadAccounts()
      }
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.failed_to_load_companies'),
    })
  }
})

onUnmounted(() => {
  // Cancel any pending requests on unmount
  if (abortController) {
    abortController.abort()
  }
})

// Watch for company changes - debounced
const debouncedLoadAccounts = debounce(async () => {
  await loadAccounts()
}, 300)

watch(selectedCompanyId, async (newCompanyId) => {
  if (newCompanyId) {
    // Reset state when company changes
    ledgerData.value = null
    hasSearched.value = false
    filters.value.account_id = null
    ifrsEnabled.value = false
    ifrsChecked.value = false

    await checkIfrsStatus()
    if (ifrsEnabled.value) {
      debouncedLoadAccounts()
    }
  }
})

// Methods
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

async function enableIfrs() {
  if (!selectedCompanyId.value) return

  isEnablingIfrs.value = true
  try {
    await window.axios.post(`/partner/companies/${selectedCompanyId.value}/accounting/enable-ifrs`)
    ifrsEnabled.value = true
    notificationStore.showNotification({
      type: 'success',
      message: t('partner.accounting.accounting_enabled_success', 'Accounting has been enabled for this company'),
    })
    await loadAccounts()
  } catch {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.failed_to_enable_accounting', 'Failed to enable accounting'),
    })
  } finally {
    isEnablingIfrs.value = false
  }
}

async function loadAccounts() {
  if (!selectedCompanyId.value) return

  isLoadingAccounts.value = true
  try {
    await partnerAccountingStore.fetchAccounts(selectedCompanyId.value)
    accounts.value = (partnerAccountingStore.accounts || []).map(account => ({
      ...account,
      display_name: `${account.code || ''} - ${account.name || ''}`.trim(),
    }))
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.failed_to_load_accounts'),
    })
    accounts.value = []
  } finally {
    isLoadingAccounts.value = false
  }
}

function onCompanyChange() {
  filters.value.account_id = null
  ledgerData.value = null
  hasSearched.value = false
}

async function loadLedger() {
  if (!selectedCompanyId.value || !filters.value.account_id) return

  // Cancel previous request if still pending
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoading.value = true
  hasSearched.value = true
  ledgerData.value = null

  try {
    // Find the selected account's code to pass to IFRS adapter
    const selectedAccount = accounts.value.find(a => a.id === filters.value.account_id)
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/general-ledger`, {
      params: {
        account_id: filters.value.account_id,
        account_code: selectedAccount?.code || null,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      signal: abortController.signal,
    })

    ledgerData.value = response.data?.data || null
  } catch (error) {
    // Don't show error for cancelled requests
    if (error.name === 'CanceledError' || error.name === 'AbortError') {
      return
    }

    const errorMessage = error.response?.data?.message || t('errors.failed_to_load_data')
    notificationStore.showNotification({
      type: 'error',
      message: errorMessage,
    })
    ledgerData.value = null
  } finally {
    isLoading.value = false
  }
}

async function exportToCsv() {
  if (!ledgerData.value?.entries || !selectedCompanyId.value) return

  isExporting.value = true

  try {
    // Create CSV content with localized headers
    const headers = [
      t('general.date'),
      t('reports.accounting.general_ledger.document'),
      t('general.description'),
      t('reports.accounting.general_ledger.debit'),
      t('reports.accounting.general_ledger.credit'),
      t('reports.accounting.general_ledger.balance'),
    ]
    const rows = ledgerData.value.entries.map(entry => [
      entry.date || '',
      entry.reference || '',
      entry.description || '',
      entry.debit || 0,
      entry.credit || 0,
      entry.running_balance || 0,
    ])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    // Create download
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const filename = `general_ledger_${selectedAccountCode.value || 'all'}_${filters.value.start_date}_${filters.value.end_date}.csv`
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    notificationStore.showNotification({
      type: 'success',
      message: t('general.export_success'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.export_failed'),
    })
  } finally {
    isExporting.value = false
  }
}

async function previewPdf() {
  if (!selectedCompanyId.value || !filters.value.account_id) return
  isExportingPdf.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/general-ledger/export`, {
      params: {
        account_id: filters.value.account_id,
        account_code: selectedAccountCode.value,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: t('errors.export_failed') })
  } finally {
    isExportingPdf.value = false
  }
}

function downloadPdf() {
  if (!pdfBlob.value) return
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `glavna_kniga_${selectedAccountCode.value || 'all'}_${filters.value.start_date}_${filters.value.end_date}.pdf`)
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

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return '-'
    return date.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      timeZone: 'UTC',
    })
  } catch {
    return '-'
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'

  try {
    // Get currency precision from company settings (0 for MKD, 2 for others)
    const company = companies.value.find(c => c.id === selectedCompanyId.value)
    const precision = company?.currency?.precision ?? 2

    const absAmount = Math.abs(amount)
    // All amounts stored in cents, always divide by 100 for display
    const displayAmount = absAmount / 100

    const formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: precision,
      maximumFractionDigits: precision,
    }).format(displayAmount)

    const sign = amount < 0 ? '-' : ''
    return `${sign}${formatted} ${selectedCompanyCurrency.value}`
  } catch {
    return '-'
  }
}

function balanceColorClass(balance) {
  // Validate that balance exists and is a number before applying styling
  if (balance === null || balance === undefined || typeof balance !== 'number' || isNaN(balance)) {
    return 'text-gray-900'
  }
  if (balance < 0) return 'text-red-600'
  if (balance > 0) return 'text-green-600'
  return 'text-gray-900'
}
</script>

