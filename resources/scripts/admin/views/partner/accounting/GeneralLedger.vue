<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.general_ledger')">
      <template #actions>
        <BaseButton
          v-if="ledgerData"
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
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Account selector -->
        <BaseInputGroup :label="$t('reports.accounting.general_ledger.select_account')">
          <BaseMultiselect
            v-model="filters.account_id"
            :options="accounts"
            :searchable="true"
            track-by="id"
            label="display_name"
            value-prop="id"
            :placeholder="$t('reports.accounting.general_ledger.select_account_placeholder')"
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

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Ledger Table -->
    <div v-else-if="ledgerData && ledgerData.entries && ledgerData.entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Account Summary Header -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">
              {{ selectedAccountName || $t('reports.accounting.general_ledger.all_accounts') }}
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
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('general.date') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.document') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('general.description') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.debit') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.credit') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
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
                <span class="font-medium text-primary-500">{{ entry.reference }}</span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500 max-w-md truncate">
                {{ entry.description }}
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

    <!-- Empty State - No Ledger Data -->
    <div
      v-else-if="hasSearched && !ledgerData"
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
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import moment from 'moment'

const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()

// State
const selectedCompanyId = ref(null)
const accounts = ref([])
const ledgerData = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

const filters = ref({
  account_id: null,
  start_date: moment().startOf('year').format('YYYY-MM-DD'),
  end_date: moment().format('YYYY-MM-DD'),
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const selectedAccountName = computed(() => {
  if (!filters.value.account_id) return ''
  const account = accounts.value.find(a => a.id === filters.value.account_id)
  return account ? account.name : ''
})

const selectedAccountCode = computed(() => {
  if (!filters.value.account_id) return ''
  const account = accounts.value.find(a => a.id === filters.value.account_id)
  return account ? account.code : ''
})

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()

  // Auto-select first company if available
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await loadAccounts()
  }
})

// Watch for company changes
watch(selectedCompanyId, async (newCompanyId) => {
  if (newCompanyId) {
    await loadAccounts()
    // Reset ledger data when company changes
    ledgerData.value = null
    hasSearched.value = false
    filters.value.account_id = null
  }
})

// Methods
async function loadAccounts() {
  if (!selectedCompanyId.value) return

  try {
    await partnerAccountingStore.fetchAccounts(selectedCompanyId.value)
    accounts.value = partnerAccountingStore.accounts.map(account => ({
      ...account,
      display_name: `${account.code} - ${account.name}`,
    }))
  } catch (error) {
    console.error('Failed to load accounts:', error)
  }
}

function onCompanyChange() {
  filters.value.account_id = null
  ledgerData.value = null
  hasSearched.value = false
}

async function loadLedger() {
  if (!selectedCompanyId.value) return

  isLoading.value = true
  hasSearched.value = true
  ledgerData.value = null

  try {
    const response = await window.axios.get(`/api/v1/partner/companies/${selectedCompanyId.value}/accounting/general-ledger`, {
      params: {
        account_id: filters.value.account_id,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
    })

    ledgerData.value = response.data.data
  } catch (error) {
    console.error('Failed to load general ledger:', error)
    ledgerData.value = null
  } finally {
    isLoading.value = false
  }
}

async function exportToCsv() {
  if (!ledgerData.value || !selectedCompanyId.value) return

  isExporting.value = true

  try {
    // Create CSV content
    const headers = ['Date', 'Reference', 'Description', 'Debit', 'Credit', 'Balance']
    const rows = ledgerData.value.entries.map(entry => [
      entry.date,
      entry.reference || '',
      entry.description || '',
      entry.debit || 0,
      entry.credit || 0,
      entry.running_balance || 0,
    ])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${cell}"`).join(',')),
    ].join('\n')

    // Create download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const filename = `general_ledger_${selectedAccountCode.value || 'all'}_${filters.value.start_date}_${filters.value.end_date}.csv`
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export general ledger:', error)
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

function balanceColorClass(balance) {
  if (balance === null || balance === undefined) return 'text-gray-900'
  if (balance < 0) return 'text-red-600'
  if (balance > 0) return 'text-green-600'
  return 'text-gray-900'
}
</script>

// CLAUDE-CHECKPOINT
