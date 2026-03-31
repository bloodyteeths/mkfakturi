<template>
  <div class="grid gap-6 pt-10">
    <!-- Filters Card -->
    <div class="p-6 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Account filter -->
        <BaseInputGroup :label="$t('accounting.general_ledger.select_account', 'Избери сметка')">
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

    <!-- Action buttons -->
    <div v-if="entries.length > 0" class="flex justify-between items-center">
      <p class="text-sm text-gray-500">
        {{ pagination.total }} {{ $t('accounting.journal_entries.entries_found', 'налози') }}
        ({{ filters.start_date }} — {{ filters.end_date }})
      </p>
      <div class="flex gap-3">
        <BaseButton
          variant="primary-outline"
          :loading="isPrintingAll"
          @click="printAllPdf"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PrinterIcon" />
          </template>
          {{ $t('accounting.journal_entries.print_all_pdf', 'Печати сите PDF') }}
        </BaseButton>

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

    <!-- Unified Journal Table -->
    <div v-if="entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500" style="width: 90px;">
                {{ $t('accounting.journal_entries.date', 'Датум') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500" style="width: 100px;">
                {{ $t('accounting.journal_entries.reference_short', 'Број') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500" style="width: 80px;">
                {{ $t('accounting.journal_entries.account_code', 'Конто') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ $t('accounting.journal_entries.account_name_desc', 'Назив / Опис') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500" style="width: 120px;">
                {{ $t('accounting.journal_entries.debit', 'Должи') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500" style="width: 120px;">
                {{ $t('accounting.journal_entries.credit', 'Побарува') }}
              </th>
              <th class="px-2 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500" style="width: 80px;">
                {{ $t('general.actions', 'Акции') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-for="entry in entries" :key="entry.id">
              <!-- Entry header row (narration) -->
              <tr class="bg-gray-50 border-t-2 border-gray-300">
                <td class="px-4 py-2 text-xs font-medium text-gray-900">
                  {{ formatDateShort(entry.date) }}
                </td>
                <td class="px-4 py-2 text-xs font-medium text-primary-600">
                  {{ entry.reference }}
                </td>
                <td colspan="3" class="px-4 py-2 text-xs text-gray-600">
                  {{ entry.narration }}
                </td>
                <td class="px-4 py-2 text-right text-xs font-bold text-gray-900">
                  {{ formatAmount(entry.total_amount) }}
                </td>
                <td class="px-2 py-2 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      class="text-gray-400 hover:text-primary-600 transition-colors"
                      :title="$t('accounting.journal_entries.print_pdf', 'Печати PDF')"
                      @click="printEntryPdf(entry)"
                    >
                      <BaseIcon name="PrinterIcon" class="h-4 w-4" />
                    </button>
                    <button
                      class="text-gray-400 hover:text-red-500 transition-colors"
                      :title="$t('accounting.journal_entries.reverse', 'Сторно')"
                      @click="confirmReverse(entry)"
                    >
                      <BaseIcon name="ArrowUturnLeftIcon" class="h-4 w-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <!-- Line item rows -->
              <tr v-for="(line, idx) in entry.lines" :key="`${entry.id}-${idx}`">
                <td class="px-4 py-1.5"></td>
                <td class="px-4 py-1.5"></td>
                <td class="px-4 py-1.5 text-sm font-mono text-gray-700">
                  {{ line.account_code }}
                </td>
                <td class="px-4 py-1.5 text-sm text-gray-600">
                  {{ line.account_name }}
                  <span v-if="line.counterparty_name" class="text-xs text-gray-400 ml-2">
                    ({{ line.counterparty_name }})
                  </span>
                </td>
                <td class="px-4 py-1.5 text-sm text-right font-mono">
                  {{ line.debit > 0 ? formatAmount(line.debit) : '' }}
                </td>
                <td class="px-4 py-1.5 text-sm text-right font-mono">
                  {{ line.credit > 0 ? formatAmount(line.credit) : '' }}
                </td>
                <td class="px-2 py-1.5"></td>
              </tr>
            </template>
          </tbody>
          <!-- Grand totals -->
          <tfoot class="bg-gray-100 border-t-2 border-gray-400">
            <tr>
              <td colspan="4" class="px-4 py-3 text-sm font-bold text-gray-900 text-right">
                {{ $t('accounting.journal_entries.grand_total', 'ВКУПНО:') }}
              </td>
              <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 font-mono">
                {{ formatAmount(grandTotalDebit) }}
              </td>
              <td class="px-4 py-3 text-sm text-right font-bold text-gray-900 font-mono">
                {{ formatAmount(grandTotalCredit) }}
              </td>
              <td class="px-2 py-3"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.totalPages > 1" class="flex justify-center">
      <div class="flex items-center gap-2">
        <BaseButton
          variant="gray"
          size="sm"
          :disabled="pagination.currentPage === 1"
          @click="loadPage(pagination.currentPage - 1)"
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
          @click="loadPage(pagination.currentPage + 1)"
        >
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4" />
        </BaseButton>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-if="hasSearched && entries.length === 0"
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import moment from 'moment'

const companyStore = useCompanyStore()

const entries = ref([])
const isLoading = ref(false)
const isExporting = ref(false)
const isPrintingAll = ref(false)
const hasSearched = ref(false)
const accounts = ref([])

const filters = ref({
  start_date: moment().startOf('month').format('YYYY-MM-DD'),
  end_date: moment().endOf('month').format('YYYY-MM-DD'),
  account_id: null,
})

// Load accounts for filter dropdown
async function loadAccounts() {
  try {
    const response = await window.axios.get('/accounting/accounts', { params: { limit: 'all' } })
    if (response.data?.data) {
      accounts.value = response.data.data.map(a => ({
        ...a,
        label: `${a.code} - ${a.name}`,
      }))
    }
  } catch {
    // Accounting not enabled
  }
}
loadAccounts()

const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  perPage: 50,
  total: 0,
})

const canLoadEntries = computed(() => {
  return filters.value.start_date && filters.value.end_date
})

// Grand totals across all visible entries
const grandTotalDebit = computed(() => {
  return entries.value.reduce((sum, e) => sum + (e.total_debit || 0), 0)
})

const grandTotalCredit = computed(() => {
  return entries.value.reduce((sum, e) => sum + (e.total_credit || 0), 0)
})

async function loadEntries(page = 1) {
  if (!canLoadEntries.value) return

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
    const response = await window.axios.get('/accounting/journal-entries', { params })

    entries.value = response.data.data
    pagination.value = {
      currentPage: response.data.meta?.current_page || 1,
      totalPages: response.data.meta?.last_page || 1,
      perPage: response.data.meta?.per_page || 50,
      total: response.data.meta?.total || 0,
    }
  } catch (error) {
    console.error('Failed to load journal entries:', error)
    entries.value = []
  } finally {
    isLoading.value = false
  }
}

function loadPage(page) {
  loadEntries(page)
}

// ---- Print single entry PDF ----
async function printEntryPdf(entry) {
  try {
    const response = await window.axios.get(`/accounting/journal-entries/${entry.id}/pdf`, {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `nalog_${entry.reference || entry.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download entry PDF:', error)
  }
}

// ---- Print ALL as journal register PDF ----
async function printAllPdf() {
  isPrintingAll.value = true
  try {
    const response = await window.axios.get('/accounting/journal-entries/pdf', {
      params: {
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `dnevnik_${filters.value.start_date}_${filters.value.end_date}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to download bulk PDF:', error)
  } finally {
    isPrintingAll.value = false
  }
}

async function exportToCsv() {
  if (entries.value.length === 0) return

  isExporting.value = true

  try {
    const response = await window.axios.get('/accounting/journal-entries/export', {
      params: {
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
      },
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `journal_entries_${filters.value.start_date}_${filters.value.end_date}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export journal entries:', error)
  } finally {
    isExporting.value = false
  }
}

// ---- Reverse ----
async function confirmReverse(entry) {
  if (!window.confirm(`Дали сте сигурни дека сакате да го сторнирате налогот ${entry.reference || entry.narration}?`)) {
    return
  }
  try {
    await window.axios.post(`/accounting/journal-entries/${entry.id}/reverse`)
    await loadEntries(pagination.value.currentPage)
  } catch (error) {
    console.error('Failed to reverse entry:', error)
  }
}

// ---- Formatting ----
function formatDateShort(dateStr) {
  if (!dateStr) return '-'
  return moment(dateStr).format('DD.MM.YYYY')
}

function formatAmount(amount) {
  if (amount === null || amount === undefined) return '-'
  const formatted = new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Math.abs(amount) / 100)
  return formatted
}

</script>
