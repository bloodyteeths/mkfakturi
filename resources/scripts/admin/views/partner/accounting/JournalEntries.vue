<template>
  <BasePage>
    <BasePageHeader :title="$t('accounting.journal_entries.title', 'Налози за книжење')">
      <template #actions>
        <BaseButton
          v-if="entries.length > 0"
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
        <!-- Account filter -->
        <BaseInputGroup :label="$t('accounting.general_ledger.select_account', 'Filter by Account')">
          <BaseMultiselect
            v-model="filters.account_id"
            :options="accounts"
            label="label"
            value-prop="id"
            searchable
            :can-deselect="true"
            :placeholder="$t('accounting.journal_entries.all_accounts', 'All accounts')"
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
            :disabled="!canLoadEntries"
            @click="loadEntries(1)"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Journal Entries List -->
    <div v-if="entries.length > 0" class="space-y-4">
      <div
        v-for="entry in entries"
        :key="entry.id"
        class="bg-white rounded-lg shadow overflow-hidden"
      >
        <!-- Entry Header (Clickable) -->
        <div
          class="px-6 py-4 cursor-pointer hover:bg-gray-50 transition-colors"
          @click="toggleEntry(entry.id)"
        >
          <div class="flex justify-between items-center">
            <div class="flex-1">
              <div class="flex items-center gap-4">
                <span class="text-sm font-medium text-gray-900">
                  {{ formatDate(entry.date) }}
                </span>
                <span class="text-sm text-gray-600">
                  {{ entry.narration }}
                </span>
                <BaseBadge
                  v-if="entry.source_type"
                  :bg-color="getSourceBadgeColor(entry.source_type)"
                  :text-color="getSourceTextColor(entry.source_type)"
                >
                  {{ formatSourceType(entry.source_type) }}
                </BaseBadge>
              </div>
              <div v-if="entry.reference" class="mt-1">
                <span class="text-xs text-gray-500">
                  {{ $t('general.reference') }}:
                </span>
                <span class="text-xs font-medium text-primary-500">
                  {{ entry.reference }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="text-right">
                <p class="text-sm font-semibold text-gray-900">
                  {{ formatMoney(entry.total_amount) }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ entry.lines_count }} {{ $t('accounting.journal_entries.lines') }}
                </p>
              </div>
              <BaseIcon
                name="ChevronDownIcon"
                class="h-5 w-5 text-gray-400 transition-transform"
                :class="{ 'transform rotate-180': isExpanded(entry.id) }"
              />
            </div>
          </div>
        </div>

        <!-- Expanded Details -->
        <div
          v-if="isExpanded(entry.id)"
          class="border-t border-gray-200 bg-gray-50"
        >
          <div class="px-6 py-4">
            <!-- Entry Lines Table -->
            <table class="min-w-full divide-y divide-gray-200">
              <thead>
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    {{ $t('accounting.journal_entries.account') }}
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    {{ $t('accounting.journal_entries.counterparty', 'Партнер') }}
                  </th>
                  <th class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                    {{ $t('general.description') }}
                  </th>
                  <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                    {{ $t('accounting.journal_entries.debit') }}
                  </th>
                  <th class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                    {{ $t('accounting.journal_entries.credit') }}
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="(line, index) in entry.lines" :key="index">
                  <td class="px-4 py-3 text-sm">
                    <div class="font-mono text-gray-900">{{ line.account_code }}</div>
                    <div class="text-gray-600">{{ line.account_name }}</div>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">
                    {{ line.counterparty_name || '-' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">
                    {{ line.description || '-' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-medium">
                    {{ line.debit > 0 ? formatMoney(line.debit) : '' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-medium">
                    {{ line.credit > 0 ? formatMoney(line.credit) : '' }}
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr>
                  <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
                    {{ $t('accounting.journal_entries.totals') }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                    {{ formatMoney(entry.total_debit) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                    {{ formatMoney(entry.total_credit) }}
                  </td>
                </tr>
              </tfoot>
            </table>

            <!-- Source Document Link -->
            <div v-if="entry.source_link" class="mt-4 pt-4 border-t border-gray-200">
              <a
                :href="entry.source_link"
                class="inline-flex items-center text-sm font-medium text-primary-500 hover:text-primary-700"
              >
                <BaseIcon name="DocumentTextIcon" class="h-4 w-4 mr-2" />
                {{ $t('accounting.journal_entries.view_source_document') }}
              </a>
            </div>

            <!-- Actions: Print PDF + Reverse -->
            <div class="mt-4 pt-4 border-t border-gray-200 flex gap-3">
              <BaseButton
                variant="primary-outline"
                size="sm"
                :loading="printingEntryId === entry.id"
                @click.stop="printEntryPdf(entry)"
              >
                <template #left="slotProps">
                  <BaseIcon :class="slotProps.class" name="PrinterIcon" />
                </template>
                {{ $t('accounting.journal_entries.print_pdf', 'Печати PDF') }}
              </BaseButton>

              <BaseButton
                variant="danger-outline"
                size="sm"
                :loading="reversingEntryId === entry.id"
                @click.stop="confirmReverse(entry)"
              >
                <template #left="slotProps">
                  <BaseIcon :class="slotProps.class" name="ArrowUturnLeftIcon" />
                </template>
                {{ $t('accounting.journal_entries.reverse', 'Сторно') }}
              </BaseButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.totalPages > 1" class="flex justify-center mt-6">
      <div class="flex items-center gap-2">
        <BaseButton
          variant="gray"
          size="sm"
          :disabled="pagination.currentPage === 1"
          @click="loadEntries(pagination.currentPage - 1)"
        >
          <BaseIcon name="ChevronLeftIcon" class="h-4 w-4" />
        </BaseButton>

        <span class="text-sm text-gray-700">
          {{ $t('general.page') }} {{ pagination.currentPage }} {{ $t('general.of') }} {{ pagination.totalPages }}
        </span>

        <BaseButton
          variant="gray"
          size="sm"
          :disabled="pagination.currentPage === pagination.totalPages"
          @click="loadEntries(pagination.currentPage + 1)"
        >
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4" />
        </BaseButton>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="hasSearched && entries.length === 0 && !isLoading"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('accounting.journal_entries.no_entries') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('accounting.journal_entries.no_entries_description') }}
      </p>
    </div>

    <!-- Initial State -->
    <div
      v-if="selectedCompanyId && ifrsEnabled && !hasSearched && entries.length === 0"
      class="bg-white rounded-lg shadow p-12 text-center"
    >
      <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('accounting.journal_entries.select_and_load', 'Изберете период и притиснете Вчитај') }}
      </h3>
    </div>

    <!-- Select company message -->
    <div
      v-if="!selectedCompanyId"
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

// State
const selectedCompanyId = ref(null)
const entries = ref([])
const expandedEntries = ref(new Set())
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)
const accounts = ref([])
const ifrsEnabled = ref(false)
const ifrsChecked = ref(false)
const isEnablingIfrs = ref(false)
const printingEntryId = ref(null)
const reversingEntryId = ref(null)

let abortController = null

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function getStartOfMonthString() {
  const now = new Date()
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`
}

const filters = ref({
  start_date: getStartOfMonthString(),
  end_date: getLocalDateString(),
  account_id: null,
})

const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  perPage: 20,
  total: 0,
})

// Computed
const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const canLoadEntries = computed(() => {
  return filters.value.start_date && filters.value.end_date
})

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
      await checkIfrsStatus()
      if (ifrsEnabled.value) {
        await loadAccounts()
      }
    }
  } catch {
    // silently handle
  }
})

onUnmounted(() => {
  if (abortController) {
    abortController.abort()
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
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounts`, {
      params: { limit: 'all' },
    })
    if (response.data?.data) {
      accounts.value = response.data.data.map(a => ({
        ...a,
        label: `${a.code} - ${a.name}`,
      }))
    }
  } catch {
    accounts.value = []
  }
}

function onCompanyChange() {
  entries.value = []
  hasSearched.value = false
  accounts.value = []
  filters.value.account_id = null
  ifrsEnabled.value = false
  ifrsChecked.value = false
  expandedEntries.value.clear()

  checkIfrsStatus().then(() => {
    if (ifrsEnabled.value) {
      loadAccounts()
    }
  })
}

function toggleEntry(entryId) {
  if (expandedEntries.value.has(entryId)) {
    expandedEntries.value.delete(entryId)
  } else {
    expandedEntries.value.add(entryId)
  }
}

function isExpanded(entryId) {
  return expandedEntries.value.has(entryId)
}

async function loadEntries(page = 1) {
  if (!canLoadEntries.value || !selectedCompanyId.value) return

  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoading.value = true
  hasSearched.value = true

  try {
    const params = {
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
      page: page,
      per_page: pagination.value.perPage,
    }
    if (filters.value.account_id) {
      params.account_id = filters.value.account_id
    }

    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/journal-entries`,
      { params, signal: abortController.signal }
    )

    entries.value = response.data.data
    pagination.value = {
      currentPage: response.data.meta?.current_page || 1,
      totalPages: response.data.meta?.last_page || 1,
      perPage: response.data.meta?.per_page || 20,
      total: response.data.meta?.total || 0,
    }

    expandedEntries.value.clear()
  } catch (error) {
    if (error.name === 'CanceledError' || error.name === 'AbortError') {
      return
    }
    console.error('Failed to load journal entries:', error)
    entries.value = []
  } finally {
    isLoading.value = false
  }
}

async function exportToCsv() {
  if (entries.value.length === 0 || !selectedCompanyId.value) return

  isExporting.value = true
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/journal-entries`,
      {
        params: {
          start_date: filters.value.start_date,
          end_date: filters.value.end_date,
          per_page: 9999,
        },
      }
    )

    const allEntries = response.data.data || []
    const headers = [
      t('general.date'),
      t('general.reference'),
      t('general.description'),
      t('accounting.journal_entries.account'),
      t('accounting.journal_entries.counterparty', 'Партнер'),
      t('accounting.journal_entries.debit'),
      t('accounting.journal_entries.credit'),
    ]

    const rows = []
    for (const entry of allEntries) {
      for (const line of (entry.lines || [])) {
        rows.push([
          entry.date || '',
          entry.reference || '',
          entry.narration || '',
          `${line.account_code} - ${line.account_name}`,
          line.counterparty_name || '',
          line.debit > 0 ? (line.debit / 100).toFixed(2) : '',
          line.credit > 0 ? (line.credit / 100).toFixed(2) : '',
        ])
      }
    }

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `nalozi_${filters.value.start_date}_${filters.value.end_date}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export:', error)
    notificationStore.showNotification({ type: 'error', message: t('errors.export_failed') })
  } finally {
    isExporting.value = false
  }
}

async function printEntryPdf(entry) {
  printingEntryId.value = entry.id
  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/journal-entries/${entry.id}/pdf`,
      { responseType: 'blob' }
    )

    // Check if the response is actually a PDF
    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      try {
        const errorData = JSON.parse(text)
        notificationStore.showNotification({
          type: 'error',
          message: errorData.message || t('errors.export_failed'),
        })
      } catch {
        notificationStore.showNotification({ type: 'error', message: t('errors.export_failed') })
      }
      return
    }

    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    const ref = (entry.reference || entry.id).toString().replace(/\//g, '-')
    link.setAttribute('download', `nalog_${ref}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download PDF:', error)
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const errorData = JSON.parse(text)
        notificationStore.showNotification({
          type: 'error',
          message: errorData.message || t('errors.export_failed'),
        })
      } catch {
        notificationStore.showNotification({ type: 'error', message: t('errors.export_failed') })
      }
    } else {
      notificationStore.showNotification({ type: 'error', message: t('errors.export_failed') })
    }
  } finally {
    printingEntryId.value = null
  }
}

async function confirmReverse(entry) {
  if (!window.confirm(`${t('accounting.journal_entries.reverse_confirm', 'Дали сте сигурни дека сакате да го сторнирате налогот')} ${entry.reference || entry.narration}?`)) {
    return
  }
  reversingEntryId.value = entry.id
  try {
    await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/accounting/journal-entries/${entry.id}/reverse`
    )
    notificationStore.showNotification({
      type: 'success',
      message: t('accounting.journal_entries.reverse_success', 'Налогот е успешно сторниран'),
    })
    await loadEntries(pagination.value.currentPage)
  } catch (error) {
    console.error('Failed to reverse entry:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('accounting.journal_entries.reverse_error', 'Грешка при сторнирање'),
    })
  } finally {
    reversingEntryId.value = null
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
  const absAmount = Math.abs(amount)
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(absAmount / 100)
  const sign = amount < 0 ? '-' : ''
  return `${sign}${formatted} ${selectedCompanyCurrency.value}`
}

function formatSourceType(sourceType) {
  if (!sourceType) return '-'
  const typeMap = {
    'App\\Models\\Invoice': 'Invoice',
    'App\\Models\\Payment': 'Payment',
    'App\\Models\\Expense': 'Expense',
    'App\\Models\\Bill': 'Bill',
  }
  return typeMap[sourceType] || sourceType
}

function getSourceBadgeColor(sourceType) {
  const colorMap = {
    'App\\Models\\Invoice': 'bg-blue-100',
    'App\\Models\\Payment': 'bg-green-100',
    'App\\Models\\Expense': 'bg-red-100',
    'App\\Models\\Bill': 'bg-orange-100',
  }
  return colorMap[sourceType] || 'bg-gray-100'
}

function getSourceTextColor(sourceType) {
  const colorMap = {
    'App\\Models\\Invoice': 'text-blue-800',
    'App\\Models\\Payment': 'text-green-800',
    'App\\Models\\Expense': 'text-red-800',
    'App\\Models\\Bill': 'text-orange-800',
  }
  return colorMap[sourceType] || 'text-gray-800'
}
</script>
