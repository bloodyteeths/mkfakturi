<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.trial_balance')">
      <template #actions>
        <BaseButton
          v-if="trialBalanceData && trialBalanceData.accounts && trialBalanceData.accounts.length > 0"
          variant="primary-outline"
          :loading="isExporting"
          @click="exportToCsv"
          :aria-label="$t('general.export')"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          {{ $t('general.export') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="id"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
          aria-label="Select company"
        />
      </BaseInputGroup>
    </div>

    <!-- Filters Card -->
    <div v-if="selectedCompanyId" class="p-6 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- As of date -->
        <BaseInputGroup :label="$t('reports.accounting.as_of_date')" required>
          <BaseDatePicker
            v-model="filters.as_of_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
            aria-label="As of date"
          />
        </BaseInputGroup>

        <!-- Load button -->
        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
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

    <!-- Loading state with skeleton -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-48"></div>
        <div class="h-4 bg-gray-200 rounded w-32 mt-2"></div>
      </div>
      <div class="p-6 space-y-4">
        <div v-for="i in 8" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
        </div>
      </div>
    </div>

    <!-- Trial Balance Table -->
    <div v-else-if="trialBalanceData && trialBalanceData.accounts && trialBalanceData.accounts.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Header -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">
              {{ $t('partner.accounting.trial_balance') }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ $t('reports.accounting.as_of_date') }}: {{ formatDate(filters.as_of_date) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Trial Balance table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200" role="table" aria-label="Trial Balance">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('settings.accounts.code') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('settings.accounts.name') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="(account, index) in trialBalanceData.accounts" :key="index">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ account.code || '-' }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                {{ account.name || '-' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-right text-gray-900">
                {{ account.debit > 0 ? formatMoney(account.debit) : '' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-right text-gray-900">
                {{ account.credit > 0 ? formatMoney(account.credit) : '' }}
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr class="font-semibold">
              <td colspan="2" class="px-6 py-4 text-sm text-gray-900">
                {{ $t('general.total') }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-right text-gray-900">
                {{ formatMoney(trialBalanceData.total_debit) }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-right text-gray-900">
                {{ formatMoney(trialBalanceData.total_credit) }}
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Balance Check -->
      <div class="px-6 py-4 border-t border-gray-200" :class="isBalanced ? 'bg-green-50' : 'bg-red-50'">
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

    <!-- Initial State - Has company but no search yet -->
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
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const trialBalanceData = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

// AbortController for cancelling requests
let abortController = null

// Get current date in local timezone as YYYY-MM-DD
function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const filters = ref({
  as_of_date: getLocalDateString(),
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const isBalanced = computed(() => {
  if (!trialBalanceData.value) return true
  const debit = trialBalanceData.value.total_debit || 0
  const credit = trialBalanceData.value.total_credit || 0
  // Use epsilon comparison for floating point numbers to avoid precision issues
  return Math.abs(debit - credit) < 0.01
})

const balanceDifference = computed(() => {
  if (!trialBalanceData.value) return 0
  const debit = trialBalanceData.value.total_debit || 0
  const credit = trialBalanceData.value.total_credit || 0
  return Math.abs(debit - credit)
})

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()

    // Auto-select first company if available
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
  // Cancel any pending requests on unmount
  if (abortController) {
    abortController.abort()
  }
})

// Watch for company changes - debounced
const debouncedReset = debounce(() => {
  trialBalanceData.value = null
  hasSearched.value = false
}, 300)

watch(selectedCompanyId, () => {
  debouncedReset()
})

// Methods
function onCompanyChange() {
  trialBalanceData.value = null
  hasSearched.value = false
}

async function loadTrialBalance() {
  if (!selectedCompanyId.value) return

  // Cancel previous request if still pending
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoading.value = true
  hasSearched.value = true
  trialBalanceData.value = null

  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/trial-balance`, {
      params: {
        as_of_date: filters.value.as_of_date,
      },
      signal: abortController.signal,
    })

    // Safely extract trial balance data with null check and fallback
    const responseTrialBalance = response.data?.trial_balance
    trialBalanceData.value = responseTrialBalance && typeof responseTrialBalance === 'object'
      ? responseTrialBalance
      : { accounts: [], total_debit: 0, total_credit: 0 }
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
    trialBalanceData.value = null
  } finally {
    isLoading.value = false
  }
}

async function exportToCsv() {
  if (!trialBalanceData.value?.accounts || !selectedCompanyId.value) return

  isExporting.value = true

  try {
    // Create CSV content with localized headers
    const headers = [
      t('settings.accounts.code'),
      t('settings.accounts.name'),
      t('reports.accounting.general_ledger.debit'),
      t('reports.accounting.general_ledger.credit'),
    ]
    const rows = trialBalanceData.value.accounts.map(account => [
      account.code || '',
      account.name || '',
      account.debit || 0,
      account.credit || 0,
    ])

    // Add totals row
    rows.push(['', t('general.total'), trialBalanceData.value.total_debit || 0, trialBalanceData.value.total_credit || 0])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    // Create download with BOM for Excel compatibility
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const filename = `trial_balance_${filters.value.as_of_date}.csv`
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
    const absAmount = Math.abs(amount)
    const formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(absAmount / 100)

    const sign = amount < 0 ? '-' : ''
    return `${sign}${formatted} ${selectedCompanyCurrency.value}`
  } catch {
    return '-'
  }
}
</script>

// CLAUDE-CHECKPOINT
