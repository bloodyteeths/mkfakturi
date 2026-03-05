<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.ios_statement', 'Извод на Отворени Ставки (ИОС)')">
      <template #actions>
        <BaseButton v-if="result && result.entries && result.entries.length > 0" variant="primary-outline" @click="exportCsv">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          {{ $t('general.export') }}
        </BaseButton>
        <BaseButton v-if="result && result.entries && result.entries.length > 0" variant="primary" @click="printStatement" class="ml-2">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PrinterIcon" />
          </template>
          {{ $t('general.print', 'Печати') }}
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
          <BaseInputGroup :label="$t('reports.accounting.general_ledger.select_account')" required>
            <BaseMultiselect
              v-model="filters.account_id"
              :options="arApAccounts"
              :searchable="true"
              label="display_name"
              track-by="display_name"
              value-prop="id"
              :loading="isLoadingAccounts"
              :placeholder="$t('partner.accounting.select_account_placeholder', 'Изберете сметка...')"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('partner.accounting.counterparty', 'Комитент')">
            <BaseMultiselect
              v-model="filters.counterparty"
              :options="counterparties"
              :searchable="true"
              label="name"
              track-by="name"
              value-prop="name"
              :placeholder="$t('partner.accounting.all_counterparties', 'Сите комитенти')"
              :can-deselect="true"
              :can-clear="true"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('partner.accounting.as_of_date', 'На датум')" required>
            <BaseDatePicker v-model="filters.as_of_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <div class="flex items-end">
            <BaseButton variant="primary" class="w-full" :loading="isLoading" :disabled="!filters.account_id" @click="loadIOS">
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
              </template>
              {{ $t('general.load') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Results -->
      <div v-if="result && result.entries && result.entries.length > 0" id="ios-print-area">
        <!-- Statement Header (for print) -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
          <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="text-lg font-bold text-gray-900">{{ $t('partner.accounting.ios_title', 'ИЗВОД НА ОТВОРЕНИ СТАВКИ') }}</h3>
                <p class="text-sm text-gray-600 mt-1">{{ result.account?.code }} - {{ result.account?.name }}</p>
                <p v-if="filters.counterparty" class="text-sm font-medium text-gray-800 mt-1">{{ $t('partner.accounting.counterparty') }}: {{ filters.counterparty }}</p>
              </div>
              <div class="text-right text-sm text-gray-600">
                <p>{{ $t('partner.accounting.as_of_date') }}: <span class="font-medium">{{ filters.as_of_date }}</span></p>
                <p class="mt-1">{{ $t('partner.accounting.total_open_balance', 'Вкупно отворено') }}:
                  <span class="text-lg font-bold" :class="result.total_balance < 0 ? 'text-red-600' : 'text-green-600'">
                    {{ formatMoney(result.total_balance) }}
                  </span>
                </p>
              </div>
            </div>
          </div>

          <!-- Open Items Table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.counterparty') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.document') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.general_ledger.debit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('reports.accounting.general_ledger.credit') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('partner.accounting.balance') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <template v-for="(group, gi) in groupedEntries" :key="gi">
                  <!-- Counterparty header row -->
                  <tr class="bg-blue-50">
                    <td colspan="4" class="px-4 py-2 text-sm font-bold text-blue-900">
                      {{ group.name || $t('partner.accounting.no_counterparty', 'Без комитент') }}
                    </td>
                    <td class="px-4 py-2 text-sm text-right font-medium text-blue-700">{{ group.total_debit > 0 ? formatMoney(group.total_debit) : '' }}</td>
                    <td class="px-4 py-2 text-sm text-right font-medium text-blue-700">{{ group.total_credit > 0 ? formatMoney(group.total_credit) : '' }}</td>
                    <td class="px-4 py-2 text-sm text-right font-bold" :class="group.balance < 0 ? 'text-red-600' : 'text-green-600'">
                      {{ formatMoney(group.balance) }}
                    </td>
                  </tr>
                  <tr v-for="(entry, ei) in group.entries" :key="ei" class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm text-gray-400"></td>
                    <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">{{ formatDate(entry.date) }}</td>
                    <td class="px-4 py-2 text-sm text-primary-600 font-medium">{{ entry.reference || '-' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-600 max-w-xs truncate">{{ entry.description || '-' }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-900">{{ entry.debit > 0 ? formatMoney(entry.debit) : '' }}</td>
                    <td class="px-4 py-2 text-sm text-right text-gray-900">{{ entry.credit > 0 ? formatMoney(entry.credit) : '' }}</td>
                    <td class="px-4 py-2"></td>
                  </tr>
                </template>
              </tbody>
              <tfoot class="bg-gray-100 font-semibold">
                <tr>
                  <td colspan="4" class="px-4 py-3 text-sm">{{ $t('general.total') }} ({{ result.entries.length }} {{ $t('partner.accounting.entries_label', 'записи') }})</td>
                  <td class="px-4 py-3 text-sm text-right">{{ formatMoney(result.total_debit) }}</td>
                  <td class="px-4 py-3 text-sm text-right">{{ formatMoney(result.total_credit) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-bold" :class="result.total_balance < 0 ? 'text-red-600' : 'text-green-600'">
                    {{ formatMoney(result.total_balance) }}
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Confirmation Section (for print) -->
          <div class="px-6 py-6 border-t border-gray-200 bg-gray-50">
            <div class="grid grid-cols-2 gap-8">
              <div>
                <p class="text-sm font-medium text-gray-700 mb-4">{{ $t('partner.accounting.ios_issuer', 'Издавач:') }}</p>
                <div class="border-b border-gray-400 mt-8"></div>
                <p class="text-xs text-gray-500 mt-1">{{ $t('partner.accounting.ios_signature', 'Потпис и печат') }}</p>
              </div>
              <div>
                <p class="text-sm font-medium text-gray-700 mb-4">{{ $t('partner.accounting.ios_confirmer', 'Потврдува:') }}</p>
                <div class="border-b border-gray-400 mt-8"></div>
                <p class="text-xs text-gray-500 mt-1">{{ $t('partner.accounting.ios_signature') }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden p-6">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse mb-4">
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-48"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
        </div>
      </div>

      <!-- Empty -->
      <div v-else-if="hasSearched && (!result || !result.entries || result.entries.length === 0)" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="ClipboardDocumentCheckIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.no_open_items', 'Нема отворени ставки') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.no_open_items_desc', 'Сите ставки се затворени (салдото е нула).') }}</p>
      </div>

      <!-- Initial -->
      <div v-else-if="!hasSearched" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="ClipboardDocumentListIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.ios_prompt', 'Генерирајте ИОС извод') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.ios_hint', 'Изберете сметка (пр. 1200 или 2200) за извод на отворени ставки по комитенти.') }}</p>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const isLoading = ref(false)
const isLoadingAccounts = ref(false)
const hasSearched = ref(false)
const ifrsEnabled = ref(false)
const ifrsChecked = ref(false)
const result = ref(null)
const counterparties = ref([])

const filters = ref({
  account_id: null,
  counterparty: null,
  as_of_date: new Date().toISOString().slice(0, 10),
})

const companies = computed(() => consoleStore.managedCompanies || [])
const arApAccounts = ref([])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

// Group entries by counterparty for display
const groupedEntries = computed(() => {
  if (!result.value?.entries) return []
  const groups = {}
  for (const entry of result.value.entries) {
    const name = entry.counterparty_name || t('partner.accounting.no_counterparty', 'Без комитент')
    if (!groups[name]) {
      groups[name] = { name, entries: [], total_debit: 0, total_credit: 0, balance: 0 }
    }
    groups[name].entries.push(entry)
    groups[name].total_debit += entry.debit || 0
    groups[name].total_credit += entry.credit || 0
  }
  for (const g of Object.values(groups)) {
    g.balance = g.total_debit - g.total_credit
  }
  return Object.values(groups).sort((a, b) => Math.abs(b.balance) - Math.abs(a.balance))
})

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await checkIfrsStatus()
    if (ifrsEnabled.value) await loadAccounts()
  }
})

function onCompanyChange() {
  result.value = null
  hasSearched.value = false
  filters.value.account_id = null
  filters.value.counterparty = null
  ifrsEnabled.value = false
  ifrsChecked.value = false
  arApAccounts.value = []
  counterparties.value = []
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
    // Filter to AR/AP accounts (12xx, 22xx, and similar)
    arApAccounts.value = (partnerAccountingStore.accounts || [])
      .filter(a => a.code && (a.code.startsWith('12') || a.code.startsWith('22') || a.code.startsWith('15') || a.code.startsWith('24')))
      .map(a => ({ ...a, display_name: `${a.code} - ${a.name}` }))
  } catch {
    arApAccounts.value = []
  } finally {
    isLoadingAccounts.value = false
  }
}

async function loadIOS() {
  if (!selectedCompanyId.value || !filters.value.account_id) return
  isLoading.value = true
  hasSearched.value = true
  result.value = null

  try {
    const selectedAccount = arApAccounts.value.find(a => a.id === filters.value.account_id)
    // Use sub-ledger endpoint with as_of_date (from beginning of time to as_of_date)
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/sub-ledger`, {
      params: {
        account_id: filters.value.account_id,
        account_code: selectedAccount?.code || null,
        from_date: '2000-01-01',
        to_date: filters.value.as_of_date,
      },
    })

    const data = response.data?.data || response.data
    if (!data) {
      result.value = null
      return
    }

    // Extract counterparties list for filter dropdown
    if (data.counterparties) {
      counterparties.value = data.counterparties
        .filter(cp => cp.name)
        .map(cp => ({ name: cp.name }))
    }

    // Flatten all entries from counterparties that have non-zero closing balance (open items)
    let allEntries = []
    let totalDebit = 0
    let totalCredit = 0
    let totalBalance = 0

    for (const cp of (data.counterparties || [])) {
      // Filter by counterparty if selected
      if (filters.value.counterparty && cp.name !== filters.value.counterparty) continue
      // Only show counterparties with open balance
      if (cp.closing_balance === 0) continue

      for (const entry of (cp.entries || [])) {
        allEntries.push({
          ...entry,
          counterparty_name: cp.name,
        })
      }
      totalDebit += cp.total_debit || 0
      totalCredit += cp.total_credit || 0
      totalBalance += cp.closing_balance || 0
    }

    // Also add opening balance as implicit debit/credit
    for (const cp of (data.counterparties || [])) {
      if (filters.value.counterparty && cp.name !== filters.value.counterparty) continue
      if (cp.closing_balance === 0) continue
      if (cp.opening_balance && cp.opening_balance !== 0) {
        totalDebit += cp.opening_balance > 0 ? cp.opening_balance : 0
        totalCredit += cp.opening_balance < 0 ? Math.abs(cp.opening_balance) : 0
      }
    }

    result.value = {
      account: data.account,
      entries: allEntries,
      total_debit: totalDebit,
      total_credit: totalCredit,
      total_balance: totalBalance,
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to load IOS statement',
    })
    result.value = null
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

function printStatement() {
  const printArea = document.getElementById('ios-print-area')
  if (!printArea) return
  const printWindow = window.open('', '_blank')
  printWindow.document.write(`
    <html><head><title>ИОС Извод</title>
    <style>
      body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
      table { width: 100%; border-collapse: collapse; margin: 10px 0; }
      th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
      th { background: #f5f5f5; font-weight: bold; }
      .text-right { text-align: right; }
      .font-bold { font-weight: bold; }
      .text-red { color: #dc2626; }
      .text-green { color: #16a34a; }
      .bg-blue { background: #eff6ff; }
      .bg-gray { background: #f9fafb; }
      .signature-line { border-bottom: 1px solid #333; margin-top: 40px; width: 200px; }
    </style></head><body>
    ${printArea.innerHTML}
    </body></html>
  `)
  printWindow.document.close()
  printWindow.print()
}

function exportCsv() {
  if (!result.value?.entries?.length) return
  const headers = ['Комитент', 'Датум', 'Документ', 'Опис', 'Должи', 'Побарува']
  const rows = result.value.entries.map(e => [
    e.counterparty_name || '',
    e.date || '',
    e.reference || '',
    e.description || '',
    e.debit > 0 ? (e.debit / 100).toFixed(2) : '',
    e.credit > 0 ? (e.credit / 100).toFixed(2) : '',
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `ios_izvod_${filters.value.as_of_date}.csv`
  link.click()
}
</script>

<!-- CLAUDE-CHECKPOINT -->
