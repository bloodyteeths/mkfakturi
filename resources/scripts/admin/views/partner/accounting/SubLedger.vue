<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.sub_ledger')">
      <template #actions>
        <BaseButton
          v-if="result && result.counterparties && result.counterparties.length > 0"
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
          track-by="name"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
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
            :disabled="!filters.account_id"
            @click="loadSubLedger"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- IFRS Not Enabled Warning -->
    <div
      v-if="selectedCompanyId && ifrsChecked && !ifrsEnabled"
      class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-6"
    >
      <div class="flex items-start">
        <BaseIcon name="ExclamationTriangleIcon" class="h-6 w-6 text-yellow-600 mr-3 flex-shrink-0 mt-0.5" />
        <div>
          <h3 class="text-sm font-medium text-yellow-800">
            {{ $t('partner.accounting.ifrs_not_enabled_title', 'Accounting is not enabled for this company') }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden p-6">
      <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse mb-4">
        <div class="h-4 bg-gray-200 rounded w-48"></div>
        <div class="h-4 bg-gray-200 rounded w-24"></div>
        <div class="h-4 bg-gray-200 rounded w-24"></div>
        <div class="h-4 bg-gray-200 rounded w-24"></div>
      </div>
    </div>

    <!-- Results Table -->
    <div v-else-if="result && result.counterparties && result.counterparties.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Account Header -->
      <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
          {{ result.account.code }} - {{ result.account.name }}
        </h3>
        <p class="text-sm text-gray-500 mt-1">
          {{ $t('partner.accounting.sub_ledger_description', 'Per-counterparty breakdown') }}
          &bull; {{ filters.start_date }} &mdash; {{ filters.end_date }}
        </p>
      </div>

      <!-- Summary Table -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.counterparty', 'Комитент') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.opening', 'Почетно') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.debit', 'Должи') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('reports.accounting.general_ledger.credit', 'Побарува') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.closing', 'Салдо') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('partner.accounting.entries_count', 'Ставки') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr
              v-for="(cp, index) in result.counterparties"
              :key="index"
              class="hover:bg-gray-50 cursor-pointer"
              @click="toggleExpand(index)"
            >
              <td class="px-6 py-4 text-sm font-medium text-gray-900">
                <div class="flex items-center">
                  <BaseIcon
                    :name="expandedRows[index] ? 'ChevronDownIcon' : 'ChevronRightIcon'"
                    class="h-4 w-4 text-gray-400 mr-2 flex-shrink-0"
                  />
                  {{ cp.name || $t('partner.accounting.no_counterparty', 'Без комитент') }}
                </div>
              </td>
              <td class="px-6 py-4 text-sm text-right" :class="balanceClass(cp.opening_balance)">
                {{ formatMoney(cp.opening_balance) }}
              </td>
              <td class="px-6 py-4 text-sm text-right text-gray-900">
                {{ cp.total_debit > 0 ? formatMoney(cp.total_debit) : '' }}
              </td>
              <td class="px-6 py-4 text-sm text-right text-gray-900">
                {{ cp.total_credit > 0 ? formatMoney(cp.total_credit) : '' }}
              </td>
              <td class="px-6 py-4 text-sm text-right font-medium" :class="balanceClass(cp.closing_balance)">
                {{ formatMoney(cp.closing_balance) }}
              </td>
              <td class="px-6 py-4 text-sm text-center text-gray-500">
                {{ cp.entries.length }}
              </td>
            </tr>

            <!-- Expanded detail rows -->
            <template v-for="(cp, index) in result.counterparties" :key="'detail-' + index">
              <tr v-if="expandedRows[index] && cp.entries.length > 0" class="bg-blue-50">
                <td colspan="6" class="px-8 py-3">
                  <table class="min-w-full text-xs">
                    <thead>
                      <tr class="text-gray-500">
                        <th class="py-1 text-left">{{ $t('general.date') }}</th>
                        <th class="py-1 text-left">{{ $t('reports.accounting.general_ledger.document') }}</th>
                        <th class="py-1 text-left">{{ $t('general.description') }}</th>
                        <th class="py-1 text-right">{{ $t('reports.accounting.general_ledger.debit') }}</th>
                        <th class="py-1 text-right">{{ $t('reports.accounting.general_ledger.credit') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(entry, ei) in cp.entries" :key="ei" class="border-t border-blue-100">
                        <td class="py-1 text-gray-700">{{ formatDate(entry.date) }}</td>
                        <td class="py-1 text-primary-600 font-medium">{{ entry.reference || '-' }}</td>
                        <td class="py-1 text-gray-600 max-w-xs truncate">{{ entry.description || '-' }}</td>
                        <td class="py-1 text-right text-gray-900">{{ entry.debit > 0 ? formatMoney(entry.debit) : '' }}</td>
                        <td class="py-1 text-right text-gray-900">{{ entry.credit > 0 ? formatMoney(entry.credit) : '' }}</td>
                      </tr>
                    </tbody>
                  </table>
                </td>
              </tr>
            </template>
          </tbody>
          <!-- Totals Footer -->
          <tfoot class="bg-gray-100 font-semibold">
            <tr>
              <td class="px-6 py-3 text-sm text-gray-900">
                {{ $t('general.total') }} ({{ result.counterparties.length }} {{ $t('partner.accounting.counterparties_label', 'комитенти') }})
              </td>
              <td class="px-6 py-3 text-sm text-right" :class="balanceClass(result.totals.opening_balance)">
                {{ formatMoney(result.totals.opening_balance) }}
              </td>
              <td class="px-6 py-3 text-sm text-right text-gray-900">
                {{ formatMoney(result.totals.total_debit) }}
              </td>
              <td class="px-6 py-3 text-sm text-right text-gray-900">
                {{ formatMoney(result.totals.total_credit) }}
              </td>
              <td class="px-6 py-3 text-sm text-right font-bold" :class="balanceClass(result.totals.closing_balance)">
                {{ formatMoney(result.totals.closing_balance) }}
              </td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="hasSearched && (!result || !result.counterparties || result.counterparties.length === 0)"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="UsersIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('partner.accounting.no_counterparty_data', 'Нема податоци по комитент') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('partner.accounting.no_counterparty_data_desc', 'Нема пронајдено записи за избраниот период и сметка. Комитентите се зачувуваат при нов увоз на налози.') }}
      </p>
    </div>

    <!-- Initial State -->
    <div
      v-else-if="selectedCompanyId && ifrsEnabled && !hasSearched"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="UsersIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('partner.accounting.select_account_for_subledger', 'Изберете сметка за аналитика') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('partner.accounting.subledger_hint', 'Изберете сметка (пр. 1200 или 2200) за да видите салда по комитенти.') }}
      </p>
    </div>

    <!-- Select company -->
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
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const accounts = ref([])
const result = ref(null)
const isLoading = ref(false)
const isLoadingAccounts = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)
const ifrsEnabled = ref(false)
const ifrsChecked = ref(false)
const expandedRows = reactive({})

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const filters = ref({
  account_id: null,
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: getLocalDateString(),
})

const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await checkIfrsStatus()
    if (ifrsEnabled.value) {
      await loadAccounts()
    }
  }
})

const debouncedLoadAccounts = debounce(async () => {
  await loadAccounts()
}, 300)

watch(selectedCompanyId, async (newId) => {
  if (newId) {
    result.value = null
    hasSearched.value = false
    filters.value.account_id = null
    ifrsEnabled.value = false
    ifrsChecked.value = false
    Object.keys(expandedRows).forEach(k => delete expandedRows[k])
    await checkIfrsStatus()
    if (ifrsEnabled.value) {
      debouncedLoadAccounts()
    }
  }
})

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
    accounts.value = (partnerAccountingStore.accounts || []).map(account => ({
      ...account,
      display_name: `${account.code || ''} - ${account.name || ''}`.trim(),
    }))
  } catch {
    accounts.value = []
  } finally {
    isLoadingAccounts.value = false
  }
}

function onCompanyChange() {
  filters.value.account_id = null
  result.value = null
  hasSearched.value = false
}

function toggleExpand(index) {
  expandedRows[index] = !expandedRows[index]
}

async function loadSubLedger() {
  if (!selectedCompanyId.value || !filters.value.account_id) return

  isLoading.value = true
  hasSearched.value = true
  result.value = null
  Object.keys(expandedRows).forEach(k => delete expandedRows[k])

  try {
    const selectedAccount = accounts.value.find(a => a.id === filters.value.account_id)
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/sub-ledger`, {
      params: {
        account_id: filters.value.account_id,
        account_code: selectedAccount?.code || null,
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
    })

    result.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to load sub-ledger',
    })
    result.value = null
  } finally {
    isLoading.value = false
  }
}

async function exportToCsv() {
  if (!result.value?.counterparties) return
  isExporting.value = true

  try {
    const selectedAccount = accounts.value.find(a => a.id === filters.value.account_id)
    const headers = [
      t('partner.accounting.counterparty', 'Комитент'),
      t('partner.accounting.opening', 'Почетно салдо'),
      t('reports.accounting.general_ledger.debit', 'Должи'),
      t('reports.accounting.general_ledger.credit', 'Побарува'),
      t('partner.accounting.closing', 'Салдо'),
    ]
    const rows = result.value.counterparties.map(cp => [
      cp.name || t('partner.accounting.no_counterparty', 'Без комитент'),
      cp.opening_balance / 100,
      cp.total_debit / 100,
      cp.total_credit / 100,
      cp.closing_balance / 100,
    ])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `sub_ledger_${selectedAccount?.code || 'all'}_${filters.value.start_date}_${filters.value.end_date}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    notificationStore.showNotification({ type: 'error', message: 'Export failed' })
  } finally {
    isExporting.value = false
  }
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  try {
    const date = new Date(dateStr)
    if (isNaN(date.getTime())) return '-'
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', timeZone: 'UTC' })
  } catch { return '-' }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  try {
    const company = companies.value.find(c => c.id === selectedCompanyId.value)
    const precision = company?.currency?.precision ?? 2
    const absAmount = Math.abs(amount)
    const displayAmount = absAmount / 100
    const formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: precision,
      maximumFractionDigits: precision,
    }).format(displayAmount)
    const sign = amount < 0 ? '-' : ''
    return `${sign}${formatted} ${selectedCompanyCurrency.value}`
  } catch { return '-' }
}

function balanceClass(balance) {
  if (balance === null || balance === undefined || typeof balance !== 'number') return 'text-gray-900'
  if (balance < 0) return 'text-red-600'
  if (balance > 0) return 'text-green-600'
  return 'text-gray-900'
}
</script>
