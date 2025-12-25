<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.trial_balance')">
      <template #actions>
        <BaseButton
          v-if="trialBalanceData"
          variant="primary-outline"
          :loading="isExporting"
          @click="exportToCsv"
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

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <BaseSpinner />
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
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('settings.accounts.code') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('settings.accounts.name') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="(account, index) in trialBalanceData.accounts" :key="index">
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                {{ account.code }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900">
                {{ account.name }}
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
            {{ $t('reports.accounting.trial_balance.difference') }}: {{ formatMoney(Math.abs(trialBalanceData.total_debit - trialBalanceData.total_credit)) }}
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State - No Data -->
    <div
      v-else-if="hasSearched && !trialBalanceData"
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
import { ref, computed, onMounted, watch } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import moment from 'moment'

const consoleStore = useConsoleStore()

// State
const selectedCompanyId = ref(null)
const trialBalanceData = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

const filters = ref({
  as_of_date: moment().format('YYYY-MM-DD'),
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
  return trialBalanceData.value.total_debit === trialBalanceData.value.total_credit
})

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()

  // Auto-select first company if available
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
  }
})

// Watch for company changes
watch(selectedCompanyId, () => {
  // Reset data when company changes
  trialBalanceData.value = null
  hasSearched.value = false
})

// Methods
function onCompanyChange() {
  trialBalanceData.value = null
  hasSearched.value = false
}

async function loadTrialBalance() {
  if (!selectedCompanyId.value) return

  isLoading.value = true
  hasSearched.value = true
  trialBalanceData.value = null

  try {
    const response = await window.axios.get(`/api/v1/partner/companies/${selectedCompanyId.value}/accounting/trial-balance`, {
      params: {
        as_of_date: filters.value.as_of_date,
      },
    })

    trialBalanceData.value = response.data.trial_balance
  } catch (error) {
    console.error('Failed to load trial balance:', error)
    trialBalanceData.value = null
  } finally {
    isLoading.value = false
  }
}

async function exportToCsv() {
  if (!trialBalanceData.value || !selectedCompanyId.value) return

  isExporting.value = true

  try {
    // Create CSV content
    const headers = ['Code', 'Account Name', 'Debit', 'Credit']
    const rows = trialBalanceData.value.accounts.map(account => [
      account.code,
      account.name,
      account.debit || 0,
      account.credit || 0,
    ])

    // Add totals row
    rows.push(['', 'TOTAL', trialBalanceData.value.total_debit, trialBalanceData.value.total_credit])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${cell}"`).join(',')),
    ].join('\n')

    // Create download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const filename = `trial_balance_${filters.value.as_of_date}.csv`
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export trial balance:', error)
  } finally {
    isExporting.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  return moment(dateStr).format('DD MMM YYYY')
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'

  const absAmount = Math.abs(amount)
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(absAmount / 100)

  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} ${selectedCompanyCurrency.value}`
}
</script>

// CLAUDE-CHECKPOINT
