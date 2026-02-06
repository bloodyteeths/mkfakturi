<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('banking.title')" :to="{ name: 'banking.dashboard' }" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-3">
          <BaseButton
            variant="primary-outline"
            :to="{ name: 'banking.import' }"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowUpTrayIcon" :class="slotProps.class" />
            </template>
            {{ $t('banking.import_csv') || 'Import CSV' }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Stats Summary Cards -->
    <div v-if="stats" class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-500">
          {{ $t('banking.history.total_imports') || 'Total Imports' }}
        </p>
        <p class="text-2xl font-bold text-gray-900 mt-1">
          {{ stats.total_imports }}
        </p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-500">
          {{ $t('banking.history.success_rate') || 'Success Rate' }}
        </p>
        <p class="text-2xl font-bold mt-1" :class="successRateColor">
          {{ stats.success_rate }}%
        </p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-500">
          {{ $t('banking.history.avg_parse_time') || 'Avg Parse Time' }}
        </p>
        <p class="text-2xl font-bold text-gray-900 mt-1">
          {{ formatParseTime(stats.avg_parse_time_ms) }}
        </p>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <p class="text-sm font-medium text-gray-500">
          {{ $t('banking.history.total_imported') || 'Total Rows Imported' }}
        </p>
        <p class="text-2xl font-bold text-gray-900 mt-1">
          {{ stats.total_rows_imported?.toLocaleString('mk-MK') || 0 }}
        </p>
      </div>
    </div>

    <!-- Per-Bank Breakdown -->
    <div v-if="stats?.per_bank?.length" class="mt-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-3">
        {{ $t('banking.history.per_bank_breakdown') || 'Per Bank Breakdown' }}
      </h3>
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ $t('banking.bank') || 'Bank' }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('banking.history.imports') || 'Imports' }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('banking.history.imported_rows') || 'Imported' }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('banking.history.duplicates') || 'Duplicates' }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('banking.history.success_rate') || 'Success Rate' }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('banking.history.avg_time') || 'Avg Time' }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="bank in stats.per_bank" :key="bank.bank_code">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">
                {{ bankDisplayName(bank.bank_code) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600 text-right">
                {{ bank.import_count }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600 text-right">
                {{ bank.total_imported }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-600 text-right">
                {{ bank.total_duplicates }}
              </td>
              <td class="px-4 py-3 text-sm text-right">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="bank.success_rate >= 90
                    ? 'bg-green-100 text-green-800'
                    : bank.success_rate >= 70
                      ? 'bg-yellow-100 text-yellow-800'
                      : 'bg-red-100 text-red-800'"
                >
                  {{ bank.success_rate }}%
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600 text-right">
                {{ formatParseTime(bank.avg_parse_time_ms) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Filters -->
    <div class="mt-8 flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $t('banking.history.import_log') || 'Import Log' }}
      </h3>
      <BaseButton
        variant="primary-outline"
        size="sm"
        @click="showFilters = !showFilters"
      >
        {{ $t('general.filter') || 'Filter' }}
        <template #right="slotProps">
          <BaseIcon
            v-if="!showFilters"
            name="FunnelIcon"
            :class="slotProps.class"
          />
          <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
        </template>
      </BaseButton>
    </div>

    <BaseFilterWrapper :show="showFilters" class="mt-3 mb-4" @clear="clearFilters">
      <BaseInputGroup :label="$t('banking.bank') || 'Bank'" class="text-left">
        <BaseSelect
          v-model="filters.bank_code"
          :options="bankFilterOptions"
          :searchable="true"
          :show-labels="false"
          :placeholder="$t('banking.history.all_banks') || 'All Banks'"
          label="label"
          value-prop="value"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('banking.status') || 'Status'" class="text-left">
        <BaseSelect
          v-model="filters.status"
          :options="statusOptions"
          :show-labels="false"
          :placeholder="$t('banking.history.all_statuses') || 'All Statuses'"
          label="label"
          value-prop="value"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.from') || 'From'" class="text-left">
        <BaseDatePicker
          v-model="filters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.to') || 'To'" class="text-left">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Import History Table -->
    <div class="mt-4 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
      <!-- Loading State -->
      <div v-if="isLoading" class="p-8 flex justify-center">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox :rounded="true" />
        </BaseContentPlaceholders>
      </div>

      <!-- Empty State -->
      <div v-else-if="!logs.length" class="p-8 text-center">
        <p class="text-gray-500">
          {{ $t('banking.history.no_imports') || 'No import history found.' }}
        </p>
        <BaseButton
          variant="primary-outline"
          class="mt-4"
          :to="{ name: 'banking.import' }"
        >
          {{ $t('banking.import_csv') || 'Import CSV' }}
        </BaseButton>
      </div>

      <!-- Table -->
      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.date') || 'Date' }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.bank') || 'Bank' }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.file') || 'File' }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.total') || 'Total' }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.imported_rows') || 'Imported' }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.duplicates') || 'Dupes' }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.failed') || 'Failed' }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.parse_time') || 'Time' }}
            </th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.status') || 'Status' }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('banking.history.user') || 'User' }}
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="log in logs" :key="log.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
              {{ formatDateTime(log.created_at) }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ bankDisplayName(log.bank_code) }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-600 max-w-[200px] truncate" :title="log.file_name">
              {{ log.file_name }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">
              {{ log.total_rows }}
            </td>
            <td class="px-4 py-3 text-sm text-green-600 text-right font-medium">
              {{ log.imported_rows }}
            </td>
            <td class="px-4 py-3 text-sm text-yellow-600 text-right">
              {{ log.duplicate_rows }}
            </td>
            <td class="px-4 py-3 text-sm text-right" :class="log.failed_rows > 0 ? 'text-red-600 font-medium' : 'text-gray-400'">
              {{ log.failed_rows }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-500 text-right whitespace-nowrap">
              {{ formatParseTime(log.parse_time_ms) }}
            </td>
            <td class="px-4 py-3 text-center">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="statusBadgeClass(log.status)"
              >
                {{ statusLabel(log.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">
              {{ log.user?.name || '-' }}
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="pagination.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-700">
          {{ $t('banking.history.showing') || 'Showing' }}
          {{ pagination.from }}-{{ pagination.to }}
          {{ $t('banking.history.of') || 'of' }}
          {{ pagination.total }}
        </p>
        <div class="flex space-x-2">
          <BaseButton
            variant="gray"
            size="sm"
            :disabled="pagination.current_page <= 1"
            @click="goToPage(pagination.current_page - 1)"
          >
            {{ $t('general.previous') || 'Previous' }}
          </BaseButton>
          <BaseButton
            variant="gray"
            size="sm"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goToPage(pagination.current_page + 1)"
          >
            {{ $t('general.next') || 'Next' }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const notificationStore = useNotificationStore()

// Page title
const pageTitle = computed(() => t('banking.history.title') || 'Import History')

// State
const isLoading = ref(true)
const logs = ref([])
const stats = ref(null)
const showFilters = ref(false)
const currentPage = ref(1)

const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0,
})

// Filters
const filters = ref({
  bank_code: null,
  status: null,
  from_date: null,
  to_date: null,
})

// Bank name mapping
const bankNames = {
  nlb: 'NLB Banka',
  stopanska: 'Stopanska Banka',
  komercijalna: 'Komercijalna Banka',
  generic: 'Generic CSV',
  auto: 'Auto-detected',
}

// Filter options
const bankFilterOptions = computed(() => [
  { label: t('banking.history.all_banks') || 'All Banks', value: null },
  { label: 'NLB Banka', value: 'nlb' },
  { label: 'Stopanska Banka', value: 'stopanska' },
  { label: 'Komercijalna Banka', value: 'komercijalna' },
])

const statusOptions = computed(() => [
  { label: t('banking.history.all_statuses') || 'All Statuses', value: null },
  { label: t('banking.history.status_completed') || 'Completed', value: 'completed' },
  { label: t('banking.history.status_partial') || 'Partial', value: 'partial' },
  { label: t('banking.history.status_failed') || 'Failed', value: 'failed' },
  { label: t('banking.history.status_pending') || 'Pending', value: 'pending' },
])

// Computed
const successRateColor = computed(() => {
  if (!stats.value) return 'text-gray-900'
  if (stats.value.success_rate >= 90) return 'text-green-600'
  if (stats.value.success_rate >= 70) return 'text-yellow-600'
  return 'text-red-600'
})

// Methods
const fetchHistory = async (page = 1) => {
  isLoading.value = true
  try {
    const params = { page, per_page: 15 }
    if (filters.value.bank_code) params.bank_code = filters.value.bank_code
    if (filters.value.status) params.status = filters.value.status
    if (filters.value.from_date) params.from_date = filters.value.from_date
    if (filters.value.to_date) params.to_date = filters.value.to_date

    const response = await axios.get('/banking/import/history', { params })
    logs.value = response.data.data || []
    pagination.value = {
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
      from: response.data.from || 0,
      to: response.data.to || 0,
      total: response.data.total || 0,
    }
  } catch (error) {
    console.error('Failed to fetch import history:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('banking.history.fetch_error') || 'Failed to load import history',
    })
  } finally {
    isLoading.value = false
  }
}

const fetchStats = async () => {
  try {
    const params = {}
    if (filters.value.from_date) params.from_date = filters.value.from_date
    if (filters.value.to_date) params.to_date = filters.value.to_date

    const response = await axios.get('/banking/import/stats', { params })
    stats.value = response.data.data || null
  } catch (error) {
    console.error('Failed to fetch import stats:', error)
  }
}

const goToPage = (page) => {
  currentPage.value = page
  fetchHistory(page)
}

const clearFilters = () => {
  filters.value = {
    bank_code: null,
    status: null,
    from_date: null,
    to_date: null,
  }
}

const bankDisplayName = (code) => {
  return bankNames[code] || code
}

const statusBadgeClass = (status) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800'
    case 'partial':
      return 'bg-yellow-100 text-yellow-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    case 'pending':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const statusLabel = (status) => {
  switch (status) {
    case 'completed':
      return t('banking.history.status_completed') || 'Completed'
    case 'partial':
      return t('banking.history.status_partial') || 'Partial'
    case 'failed':
      return t('banking.history.status_failed') || 'Failed'
    case 'pending':
      return t('banking.history.status_pending') || 'Pending'
    default:
      return status
  }
}

const formatDateTime = (dateStr) => {
  if (!dateStr) return '-'
  return new Date(dateStr).toLocaleString('mk-MK', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const formatParseTime = (ms) => {
  if (!ms && ms !== 0) return '-'
  if (ms < 1000) return `${ms}ms`
  return `${(ms / 1000).toFixed(1)}s`
}

// Watch filters and re-fetch
watch(filters, () => {
  currentPage.value = 1
  fetchHistory(1)
  fetchStats()
}, { deep: true })

// Initialize
onMounted(() => {
  fetchHistory()
  fetchStats()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
