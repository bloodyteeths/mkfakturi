<template>
  <div class="grid gap-8 pt-10">
    <!-- Filters Card -->
    <div class="p-6 bg-white rounded-lg shadow">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            @click="loadEntries"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Export Button -->
    <div v-if="entries.length > 0" class="flex justify-end">
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
                  <th
                    class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                  >
                    {{ $t('accounting.journal_entries.account') }}
                  </th>
                  <th
                    class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                  >
                    {{ $t('general.description') }}
                  </th>
                  <th
                    class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                  >
                    {{ $t('accounting.journal_entries.debit') }}
                  </th>
                  <th
                    class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
                  >
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
                  <td colspan="2" class="px-4 py-3 text-sm font-medium text-gray-900 text-right">
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
          </div>
        </div>
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
const expandedEntries = ref(new Set())
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

const filters = ref({
  start_date: moment().startOf('month').format('YYYY-MM-DD'),
  end_date: moment().endOf('month').format('YYYY-MM-DD'),
})

const pagination = ref({
  currentPage: 1,
  totalPages: 1,
  perPage: 20,
  total: 0,
})

const canLoadEntries = computed(() => {
  return filters.value.start_date && filters.value.end_date
})

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
  if (!canLoadEntries.value) return

  isLoading.value = true
  hasSearched.value = true

  try {
    const response = await window.axios.get('/api/v1/accounting/journal-entries', {
      params: {
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
        page: page,
        per_page: pagination.value.perPage,
      },
    })

    entries.value = response.data.data
    pagination.value = {
      currentPage: response.data.meta?.current_page || 1,
      totalPages: response.data.meta?.last_page || 1,
      perPage: response.data.meta?.per_page || 20,
      total: response.data.meta?.total || 0,
    }

    // Clear expanded entries when loading new data
    expandedEntries.value.clear()
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

async function exportToCsv() {
  if (entries.length === 0) return

  isExporting.value = true

  try {
    const response = await window.axios.get('/api/v1/accounting/journal-entries/export', {
      params: {
        start_date: filters.value.start_date,
        end_date: filters.value.end_date,
      },
      responseType: 'blob',
    })

    // Create download link
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url

    const filename = `journal_entries_${filters.value.start_date}_${filters.value.end_date}.csv`
    link.setAttribute('download', filename)
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

// CLAUDE-CHECKPOINT
