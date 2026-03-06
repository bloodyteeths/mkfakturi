<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.trial_balance')">
      <template #actions>
        <div v-if="trialBalanceData && trialBalanceData.accounts && trialBalanceData.accounts.length > 0" class="flex space-x-2">
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
        />
      </BaseInputGroup>
    </div>

    <!-- Filters Card -->
    <div v-if="selectedCompanyId" class="p-6 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- From date -->
        <BaseInputGroup :label="$t('reports.accounting.from_date')" required>
          <BaseDatePicker
            v-model="filters.from_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <!-- To date -->
        <BaseInputGroup :label="$t('reports.accounting.to_date')" required>
          <BaseDatePicker
            v-model="filters.to_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <!-- Load button -->
        <div class="flex items-end md:col-span-2">
          <BaseButton
            variant="primary"
            class="w-full md:w-auto"
            :loading="isLoading"
            @click="loadTrialBalance"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('reports.update_report') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-48"></div>
        <div class="h-4 bg-gray-200 rounded w-32 mt-2"></div>
      </div>
      <div class="p-6 space-y-4">
        <div v-for="i in 8" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Trial Balance Table - 6 columns -->
    <div v-else-if="trialBalanceData && trialBalanceData.accounts && trialBalanceData.accounts.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
          {{ $t('partner.accounting.trial_balance') }}
        </h3>
        <p class="text-sm text-gray-500">
          {{ formatDate(filters.from_date) }} — {{ formatDate(filters.to_date) }}
        </p>
      </div>

      <!-- 6-column table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead>
            <!-- Group headers -->
            <tr class="bg-gray-50 border-b border-gray-200">
              <th rowspan="2" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 border-r border-gray-200 w-16">
                {{ $t('settings.accounts.code') }}
              </th>
              <th rowspan="2" class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500 border-r border-gray-200">
                {{ $t('settings.accounts.name') }}
              </th>
              <th colspan="2" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-blue-700 border-r border-gray-200 bg-blue-50">
                {{ $t('reports.accounting.trial_balance.opening_balance') }}
              </th>
              <th colspan="2" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-amber-700 border-r border-gray-200 bg-amber-50">
                {{ $t('reports.accounting.trial_balance.period_turnover') }}
              </th>
              <th colspan="2" class="px-3 py-2 text-center text-xs font-semibold uppercase tracking-wider text-green-700 bg-green-50">
                {{ $t('reports.accounting.trial_balance.closing_balance') }}
              </th>
            </tr>
            <!-- Sub-headers -->
            <tr class="bg-gray-50">
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 bg-blue-50 border-r border-gray-100 w-24">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 bg-blue-50 border-r border-gray-200 w-24">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 bg-amber-50 border-r border-gray-100 w-24">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 bg-amber-50 border-r border-gray-200 w-24">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 bg-green-50 border-r border-gray-100 w-24">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 bg-green-50 w-24">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="(account, index) in trialBalanceData.accounts" :key="index" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-3 py-3 text-sm font-mono font-medium text-gray-600">
                {{ account.code || '-' }}
              </td>
              <td class="px-3 py-3 text-sm text-gray-900">
                {{ account.name || '-' }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-gray-900">
                {{ account.opening_debit > 0 ? formatMoney(account.opening_debit) : '' }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-gray-900 border-r border-gray-100">
                {{ account.opening_credit > 0 ? formatMoney(account.opening_credit) : '' }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-gray-900">
                {{ account.period_debit > 0 ? formatMoney(account.period_debit) : '' }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-gray-900 border-r border-gray-100">
                {{ account.period_credit > 0 ? formatMoney(account.period_credit) : '' }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right font-medium text-gray-900">
                {{ account.closing_debit > 0 ? formatMoney(account.closing_debit) : '' }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right font-medium text-gray-900">
                {{ account.closing_credit > 0 ? formatMoney(account.closing_credit) : '' }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-100">
            <tr class="font-semibold border-t-2 border-gray-300">
              <td colspan="2" class="px-3 py-3 text-sm text-gray-900">
                {{ $t('general.total') }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-blue-700">
                {{ formatMoney(trialBalanceData.totals.opening_debit) }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-blue-700 border-r border-gray-200">
                {{ formatMoney(trialBalanceData.totals.opening_credit) }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-amber-700">
                {{ formatMoney(trialBalanceData.totals.period_debit) }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-amber-700 border-r border-gray-200">
                {{ formatMoney(trialBalanceData.totals.period_credit) }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-green-700">
                {{ formatMoney(trialBalanceData.totals.closing_debit) }}
              </td>
              <td class="whitespace-nowrap px-3 py-3 text-sm text-right text-green-700">
                {{ formatMoney(trialBalanceData.totals.closing_credit) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Balance Check -->
      <div class="px-6 py-3 border-t border-gray-200" :class="isBalanced ? 'bg-green-50' : 'bg-red-50'">
        <div class="flex items-center justify-between">
          <div class="flex items-center">
            <BaseIcon
              :name="isBalanced ? 'CheckCircleIcon' : 'ExclamationCircleIcon'"
              :class="isBalanced ? 'text-green-600' : 'text-red-600'"
              class="h-5 w-5 mr-2"
            />
            <span :class="isBalanced ? 'text-green-700' : 'text-red-700'" class="text-sm font-medium">
              {{ isBalanced ? $t('reports.accounting.trial_balance.balanced') : $t('reports.accounting.trial_balance.not_balanced') }}
            </span>
          </div>
          <div v-if="!isBalanced" class="text-sm text-red-600">
            {{ $t('reports.accounting.trial_balance.difference') }}: {{ formatMoney(balanceDifference) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State - No Data after search -->
    <div
      v-else-if="hasSearched && (!trialBalanceData || !trialBalanceData.accounts || trialBalanceData.accounts.length === 0)"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('reports.accounting.trial_balance.no_data') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('reports.accounting.trial_balance.no_data_description') }}
      </p>
    </div>

    <!-- Initial State -->
    <div
      v-else-if="selectedCompanyId && !hasSearched"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('reports.accounting.trial_balance.select_and_load') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('reports.accounting.trial_balance.select_and_load_description') }}
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
      :title="$t('partner.accounting.trial_balance')"
      @close="closePdfPreview"
      @download="downloadPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'
import PdfPreviewModal from './components/PdfPreviewModal.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const trialBalanceData = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const isExportingPdf = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)
const hasSearched = ref(false)

let abortController = null

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function getYearStart() {
  const now = new Date()
  return `${now.getFullYear()}-01-01`
}

const filters = ref({
  from_date: getYearStart(),
  to_date: getLocalDateString(),
})

// Computed
const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const isBalanced = computed(() => {
  if (!trialBalanceData.value?.totals) return true
  const d = trialBalanceData.value.totals.closing_debit || 0
  const c = trialBalanceData.value.totals.closing_credit || 0
  return Math.abs(d - c) < 0.01
})

const balanceDifference = computed(() => {
  if (!trialBalanceData.value?.totals) return 0
  const d = trialBalanceData.value.totals.closing_debit || 0
  const c = trialBalanceData.value.totals.closing_credit || 0
  return Math.abs(d - c)
})

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.failed_to_load_companies'),
    })
  }
})

onUnmounted(() => {
  if (abortController) abortController.abort()
})

const debouncedReset = debounce(() => {
  trialBalanceData.value = null
  hasSearched.value = false
}, 300)

watch(selectedCompanyId, () => { debouncedReset() })

function onCompanyChange() {
  trialBalanceData.value = null
  hasSearched.value = false
}

async function loadTrialBalance() {
  if (!selectedCompanyId.value) return

  if (abortController) abortController.abort()
  abortController = new AbortController()

  isLoading.value = true
  hasSearched.value = true
  trialBalanceData.value = null

  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/trial-balance`, {
      params: {
        from_date: filters.value.from_date,
        to_date: filters.value.to_date,
      },
      signal: abortController.signal,
    })

    const data = response.data?.trial_balance
    trialBalanceData.value = data && typeof data === 'object'
      ? data
      : { accounts: [], totals: { opening_debit: 0, opening_credit: 0, period_debit: 0, period_credit: 0, closing_debit: 0, closing_credit: 0 } }
  } catch (error) {
    if (error.name === 'CanceledError' || error.name === 'AbortError') return

    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('errors.failed_to_load_data'),
    })
    trialBalanceData.value = null
  } finally {
    isLoading.value = false
  }
}

async function previewPdf() {
  if (!selectedCompanyId.value) return

  isExportingPdf.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/trial-balance/export`, {
      params: {
        from_date: filters.value.from_date,
        to_date: filters.value.to_date,
      },
      responseType: 'blob',
    })

    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('errors.export_failed'),
    })
  } finally {
    isExportingPdf.value = false
  }
}

function downloadPdf() {
  if (!pdfBlob.value) return
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `bruto_bilans_${filters.value.from_date}_${filters.value.to_date}.pdf`)
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

async function exportToCsv() {
  if (!trialBalanceData.value?.accounts || !selectedCompanyId.value) return

  isExporting.value = true
  try {
    const headers = [
      t('settings.accounts.code'),
      t('settings.accounts.name'),
      t('reports.accounting.trial_balance.opening_balance') + ' ' + t('reports.accounting.general_ledger.debit'),
      t('reports.accounting.trial_balance.opening_balance') + ' ' + t('reports.accounting.general_ledger.credit'),
      t('reports.accounting.trial_balance.period_turnover') + ' ' + t('reports.accounting.general_ledger.debit'),
      t('reports.accounting.trial_balance.period_turnover') + ' ' + t('reports.accounting.general_ledger.credit'),
      t('reports.accounting.trial_balance.closing_balance') + ' ' + t('reports.accounting.general_ledger.debit'),
      t('reports.accounting.trial_balance.closing_balance') + ' ' + t('reports.accounting.general_ledger.credit'),
    ]

    const rows = trialBalanceData.value.accounts.map(a => [
      a.code || '',
      a.name || '',
      a.opening_debit || 0,
      a.opening_credit || 0,
      a.period_debit || 0,
      a.period_credit || 0,
      a.closing_debit || 0,
      a.closing_credit || 0,
    ])

    const totals = trialBalanceData.value.totals
    rows.push([
      '', t('general.total'),
      totals.opening_debit || 0, totals.opening_credit || 0,
      totals.period_debit || 0, totals.period_credit || 0,
      totals.closing_debit || 0, totals.closing_credit || 0,
    ])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `bruto_bilans_${filters.value.from_date}_${filters.value.to_date}.csv`)
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
    const formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(Math.abs(amount))

    const sign = amount < 0 ? '-' : ''
    return `${sign}${formatted}`
  } catch {
    return '-'
  }
}
</script>
