<template>
  <div class="grid gap-8 pt-10">
    <!-- Filters Card -->
    <div class="p-6 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Account selector -->
        <BaseInputGroup :label="$t('accounting.general_ledger.select_account')" required>
          <BaseMultiselect
            v-model="filters.account_id"
            :options="accounts"
            :searchable="true"
            track-by="id"
            label="display_name"
            value-prop="id"
            :placeholder="$t('accounting.general_ledger.select_account_placeholder')"
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
            :disabled="!canLoadLedger"
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

    <!-- Ledger Table -->
    <div v-if="ledgerData" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Opening Balance -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <div>
            <h3 class="text-lg font-medium text-gray-900">
              {{ selectedAccountName }}
            </h3>
            <p class="text-sm text-gray-500">{{ selectedAccountCode }}</p>
          </div>
          <div class="text-right">
            <p class="text-sm text-gray-600">
              {{ $t('accounting.general_ledger.opening_balance') }}
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
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
              >
                {{ $t('general.date') }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
              >
                {{ $t('accounting.general_ledger.document') }}
              </th>
              <th
                class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
              >
                {{ $t('general.description') }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
              >
                {{ $t('accounting.general_ledger.debit') }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
              >
                {{ $t('accounting.general_ledger.credit') }}
              </th>
              <th
                class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
              >
                {{ $t('accounting.general_ledger.balance') }}
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
              {{ $t('accounting.general_ledger.closing_balance') }}
            </p>
          </div>
          <div class="text-right">
            <p class="text-lg font-semibold" :class="balanceColorClass(ledgerData.closing_balance)">
              {{ formatMoney(ledgerData.closing_balance) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Export button -->
      <div class="px-6 py-4 bg-white border-t border-gray-200">
        <BaseButton
          variant="primary-outline"
          :loading="isExporting"
          @click="exportToCsv"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          {{ $t('general.export') }}
        </BaseButton>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="hasSearched && !ledgerData"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('accounting.general_ledger.no_data') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('accounting.general_ledger.no_data_description') }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAccountStore } from '@/scripts/admin/stores/account'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import moment from 'moment'

const accountStore = useAccountStore()
const companyStore = useCompanyStore()

const accounts = ref([])
const ledgerData = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

const filters = ref({
  account_id: null,
  start_date: moment().startOf('month').format('YYYY-MM-DD'),
  end_date: moment().endOf('month').format('YYYY-MM-DD'),
})

const canLoadLedger = computed(() => {
  return filters.value.account_id && filters.value.start_date && filters.value.end_date
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

onMounted(async () => {
  // Load accounts for dropdown
  try {
    const response = await accountStore.fetchAccounts({ active: true })
    accounts.value = response.data.data.map(account => ({
      ...account,
      display_name: `${account.code} - ${account.name}`,
    }))
  } catch (error) {
    console.error('Failed to load accounts:', error)
  }
})

async function loadLedger() {
  if (!canLoadLedger.value) return

  isLoading.value = true
  hasSearched.value = true
  ledgerData.value = null

  try {
    const response = await window.axios.get('/accounting/general-ledger', {
      params: {
        account_id: filters.value.account_id,
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
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
  if (!ledgerData.value) return

  isExporting.value = true

  try {
    const response = await window.axios.get('/accounting/general-ledger/export', {
      params: {
        account_id: filters.value.account_id,
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
      },
      responseType: 'blob',
    })

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url

    const filename = `general_ledger_${selectedAccountCode.value}_${filters.value.start_date}_${filters.value.end_date}.csv`
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

  const currency = companyStore.selectedCompanyCurrency
  const absAmount = Math.abs(amount)
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(absAmount / 100)

  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} ${currency?.code || 'MKD'}`
}

function balanceColorClass(balance) {
  if (balance === null || balance === undefined) return 'text-gray-900'
  if (balance < 0) return 'text-red-600'
  if (balance > 0) return 'text-green-600'
  return 'text-gray-900'
}
</script>

// CLAUDE-CHECKPOINT
