<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <div class="flex items-center gap-2">
          <!-- As-of date input -->
          <div class="hidden sm:flex items-center gap-1">
            <label class="text-xs text-gray-500 whitespace-nowrap">{{ t('as_of_date') }}:</label>
            <BaseDatePicker
              v-model="asOfDate"
              class="w-36"
              :calendar-button="true"
              calendar-button-icon="CalendarDaysIcon"
            />
          </div>
          <BaseButton
            variant="primary"
            :loading="isCalculating"
            @click="confirmBatchCalculate"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="CalculatorIcon" />
            </template>
            <span class="hidden sm:inline">{{ t('batch_calculate') }}</span>
            <span class="sm:hidden">{{ t('calculate') }}</span>
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Summary Cards — compact, scrollable on mobile -->
    <div v-if="summary" class="flex gap-3 mb-4 overflow-x-auto pb-1 -mx-1 px-1 snap-x">
      <div
        v-for="card in summaryCards"
        :key="card.key"
        class="min-w-[130px] flex-1 rounded-lg shadow p-3 snap-start"
        :class="card.bg"
      >
        <p class="text-[10px] uppercase tracking-wide" :class="card.labelColor">{{ card.label }}</p>
        <p class="text-lg sm:text-xl font-bold" :class="card.valueColor">{{ formatMoney(card.amount) }}</p>
        <p v-if="card.count !== undefined" class="text-[10px]" :class="card.countColor">{{ card.count }} {{ t('items') }}</p>
      </div>
    </div>

    <!-- Settings toggle (rate + legal info collapsed) -->
    <div class="mb-4">
      <button
        class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors"
        @click="showSettings = !showSettings"
      >
        <BaseIcon
          :name="showSettings ? 'ChevronDownIcon' : 'ChevronRightIcon'"
          class="h-4 w-4"
        />
        {{ t('settings_and_info') }} — {{ customRate }}%
        <span
          class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium"
          :class="isCustomRate ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700'"
        >
          {{ isCustomRate ? t('custom') : t('statutory') }}
        </span>
      </button>

      <transition
        enter-active-class="transition-all duration-200 ease-out"
        leave-active-class="transition-all duration-150 ease-in"
        enter-from-class="opacity-0 max-h-0"
        enter-to-class="opacity-100 max-h-48"
        leave-from-class="opacity-100 max-h-48"
        leave-to-class="opacity-0 max-h-0"
      >
        <div v-if="showSettings" class="mt-2 overflow-hidden">
          <!-- Rate override -->
          <div class="bg-white rounded-lg shadow p-4 mb-2">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
              <div class="flex items-center gap-3 flex-1">
                <div class="min-w-0">
                  <p class="text-sm font-medium text-gray-700">{{ t('annual_rate') }}</p>
                  <p class="text-xs text-gray-400 mt-0.5 hidden sm:block">{{ t('rate_help') }}</p>
                </div>
                <div class="flex items-center gap-2">
                  <BaseInput
                    v-model="customRate"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    class="w-24"
                    :disabled="isSavingRate"
                  >
                    <template #right>
                      <span class="text-gray-400 text-sm">%</span>
                    </template>
                  </BaseInput>
                </div>
              </div>
              <div class="flex gap-2">
                <BaseButton
                  size="sm"
                  variant="primary"
                  :loading="isSavingRate"
                  @click="saveRate"
                >
                  {{ $t('general.save') }}
                </BaseButton>
                <BaseButton
                  v-if="isCustomRate"
                  size="sm"
                  variant="primary-outline"
                  @click="resetRate"
                >
                  {{ t('reset_to_default') }}
                </BaseButton>
              </div>
            </div>
          </div>
          <!-- Legal info -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-700">
            <p><strong>{{ t('nbrm_rate') }}:</strong> {{ t('mk_law_info') }}</p>
            <p class="mt-0.5 text-blue-500">{{ t('formula_info') }}</p>
          </div>
        </div>
      </transition>
    </div>

    <!-- Mobile as-of-date (shown only on small screens) -->
    <div class="sm:hidden mb-3">
      <BaseInputGroup :label="t('as_of_date')">
        <BaseDatePicker
          v-model="asOfDate"
          :calendar-button="true"
          calendar-button-icon="CalendarDaysIcon"
        />
      </BaseInputGroup>
    </div>

    <!-- Filters — responsive -->
    <div class="p-3 sm:p-4 bg-white rounded-lg shadow mb-4">
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <BaseInputGroup :label="t('status')">
          <BaseMultiselect
            v-model="filters.status"
            :options="statusOptions"
            :searchable="false"
            :can-deselect="true"
            :can-clear="true"
            label="label"
            value-prop="value"
            :placeholder="t('all')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('customer')">
          <BaseMultiselect
            v-model="filters.customer_id"
            :options="customers"
            :searchable="true"
            :can-deselect="true"
            :can-clear="true"
            label="name"
            value-prop="id"
            placeholder="..."
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('date_from')">
          <BaseDatePicker
            v-model="filters.date_from"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('date_to')">
          <BaseDatePicker
            v-model="filters.date_to"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <div class="flex items-end gap-2 col-span-2 sm:col-span-1">
          <BaseButton
            variant="primary"
            class="flex-1"
            :loading="isLoading"
            @click="fetchCalculations(1)"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            <span class="hidden sm:inline">{{ $t('reports.update_report') }}</span>
            <span class="sm:hidden">{{ t('calculate') }}</span>
          </BaseButton>
          <BaseButton
            v-if="calculations.length > 0"
            variant="primary-outline"
            @click="exportCsv"
          >
            CSV
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <template v-else-if="calculations.length > 0">
      <!-- Bulk Action Bar -->
      <div
        v-if="selectedIds.length > 0"
        class="bg-amber-50 border border-amber-200 rounded-t-lg px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
      >
        <span class="text-sm text-amber-800">
          {{ selectedIds.length }} {{ t('select_for_note') }}
        </span>
        <div v-if="canGenerateNote" class="flex gap-2">
          <BaseButton
            variant="primary"
            size="sm"
            :loading="isGenerating"
            @click="generateNote"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="DocumentArrowDownIcon" />
            </template>
            <span class="hidden sm:inline">{{ t('generate_note') }}</span>
            <span class="sm:hidden">PDF</span>
          </BaseButton>
          <BaseButton
            variant="primary-outline"
            size="sm"
            :loading="isSending"
            @click="sendNote"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="EnvelopeIcon" />
            </template>
            <span class="hidden sm:inline">{{ t('send_note') }}</span>
            <span class="sm:hidden">Email</span>
          </BaseButton>
        </div>
        <p v-else class="text-xs text-amber-600">
          {{ t('mixed_customers_warning') }}
        </p>
      </div>

      <!-- Desktop Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden hidden md:block" :class="{ 'rounded-t-none': selectedIds.length > 0 }">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 w-10">
                  <input
                    type="checkbox"
                    class="rounded border-gray-300"
                    :checked="allSelected"
                    @change="toggleSelectAll"
                  />
                </th>
                <th
                  v-for="col in visibleColumns"
                  :key="col.field"
                  class="px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wider select-none"
                  :class="[
                    col.align === 'right' ? 'text-right' : 'text-left',
                    col.sortable ? 'cursor-pointer hover:text-gray-700' : ''
                  ]"
                  @click="col.sortable && toggleSort(col.field)"
                >
                  {{ col.label }}
                  <span v-if="sortField === col.field" class="ml-0.5">{{ sortDir === 'asc' ? '↑' : '↓' }}</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase w-16">
                  {{ t('actions') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="calc in calculations"
                :key="calc.id"
                class="hover:bg-gray-50"
              >
                <td class="px-3 py-3">
                  <input
                    v-if="calc.status === 'calculated' || calc.status === 'invoiced'"
                    type="checkbox"
                    class="rounded border-gray-300"
                    :value="calc.id"
                    v-model="selectedIds"
                  />
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                  {{ calc.customer?.name || '-' }}
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm font-medium text-primary-500">
                  <router-link
                    v-if="calc.invoice?.id"
                    :to="`/admin/invoices/${calc.invoice.id}/view`"
                    class="hover:underline"
                  >
                    {{ calc.invoice?.invoice_number || '-' }}
                  </router-link>
                  <span v-else>-</span>
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDateMK(calc.invoice?.due_date) }}
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-right text-gray-900">
                  {{ formatMoney(calc.principal_amount) }}
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                  {{ calc.days_overdue }}
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                  {{ formatMoney(calc.interest_amount) }}
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-center">
                  <span :class="statusBadgeClass(calc.status)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                    {{ statusLabel(calc.status) }}
                  </span>
                </td>
                <td class="px-3 py-3 whitespace-nowrap text-right text-sm">
                  <InterestActionDropdown
                    v-if="calc.status !== 'paid'"
                    :row="calc"
                    @generate="onDropdownGenerate"
                    @waive="onDropdownWaive"
                    @revert="onDropdownRevert"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="meta && meta.last_page > 1" class="px-4 py-3 border-t border-gray-200 flex items-center justify-between">
          <p class="text-sm text-gray-500">
            {{ meta.total }} {{ t('items') }}
          </p>
          <div class="flex space-x-1">
            <BaseButton
              v-if="meta.current_page > 1"
              variant="primary-outline"
              size="sm"
              @click="fetchCalculations(1)"
            >
              &laquo;
            </BaseButton>
            <BaseButton
              v-if="meta.current_page > 1"
              variant="primary-outline"
              size="sm"
              @click="fetchCalculations(meta.current_page - 1)"
            >
              &lsaquo;
            </BaseButton>
            <template v-for="page in paginationPages" :key="page">
              <span v-if="page === '...'" class="px-2 py-1 text-gray-400 text-sm">&hellip;</span>
              <BaseButton
                v-else
                :variant="page === meta.current_page ? 'primary' : 'primary-outline'"
                size="sm"
                @click="fetchCalculations(page)"
              >
                {{ page }}
              </BaseButton>
            </template>
            <BaseButton
              v-if="meta.current_page < meta.last_page"
              variant="primary-outline"
              size="sm"
              @click="fetchCalculations(meta.current_page + 1)"
            >
              &rsaquo;
            </BaseButton>
            <BaseButton
              v-if="meta.current_page < meta.last_page"
              variant="primary-outline"
              size="sm"
              @click="fetchCalculations(meta.last_page)"
            >
              &raquo;
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Mobile Card List -->
      <div class="md:hidden space-y-2" :class="{ 'rounded-t-none': selectedIds.length > 0 }">
        <div
          v-for="calc in calculations"
          :key="'m-' + calc.id"
          class="bg-white rounded-lg shadow p-3"
        >
          <div class="flex items-start justify-between gap-2">
            <div class="flex items-start gap-2 min-w-0 flex-1">
              <input
                v-if="calc.status === 'calculated' || calc.status === 'invoiced'"
                type="checkbox"
                class="rounded border-gray-300 mt-1 flex-shrink-0"
                :value="calc.id"
                v-model="selectedIds"
              />
              <div class="min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ calc.customer?.name || '-' }}</p>
                <router-link
                  v-if="calc.invoice?.id"
                  :to="`/admin/invoices/${calc.invoice.id}/view`"
                  class="text-xs text-primary-500 hover:underline"
                >
                  {{ calc.invoice?.invoice_number || '-' }}
                </router-link>
              </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span :class="statusBadgeClass(calc.status)" class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium">
                {{ statusLabel(calc.status) }}
              </span>
              <InterestActionDropdown
                v-if="calc.status !== 'paid'"
                :row="calc"
                @generate="onDropdownGenerate"
                @waive="onDropdownWaive"
                @revert="onDropdownRevert"
              />
            </div>
          </div>
          <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
            <div>
              <p class="text-gray-400">{{ t('principal') }}</p>
              <p class="font-medium text-gray-900">{{ formatMoney(calc.principal_amount) }}</p>
            </div>
            <div>
              <p class="text-gray-400">{{ t('days_overdue') }}</p>
              <p class="font-medium text-red-600">{{ calc.days_overdue }}</p>
            </div>
            <div>
              <p class="text-gray-400">{{ t('interest_amount') }}</p>
              <p class="font-bold text-gray-900">{{ formatMoney(calc.interest_amount) }}</p>
            </div>
          </div>
          <div class="mt-1.5 flex justify-between text-[10px] text-gray-400">
            <span>{{ t('due_date') }}: {{ formatDateMK(calc.invoice?.due_date) }}</span>
            <span>{{ formatDateMK(calc.calculation_date) }}</span>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between pt-2">
          <p class="text-xs text-gray-500">
            {{ meta.current_page }}/{{ meta.last_page }} ({{ meta.total }})
          </p>
          <div class="flex gap-1">
            <BaseButton
              v-if="meta.current_page > 1"
              variant="primary-outline"
              size="sm"
              @click="fetchCalculations(meta.current_page - 1)"
            >
              &lsaquo;
            </BaseButton>
            <BaseButton
              v-if="meta.current_page < meta.last_page"
              variant="primary-outline"
              size="sm"
              @click="fetchCalculations(meta.current_page + 1)"
            >
              &rsaquo;
            </BaseButton>
          </div>
        </div>
      </div>
    </template>

    <!-- Empty State -->
    <div
      v-else-if="!isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12 sm:py-16"
    >
      <BaseIcon name="CalculatorIcon" class="h-10 w-10 sm:h-12 sm:w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_calculations') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500 text-center px-4">
        {{ t('no_calculations_description') }}
      </p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import interestMessages from '@/scripts/admin/i18n/interest.js'
import InterestActionDropdown from '@/scripts/admin/components/dropdowns/InterestActionDropdown.vue'

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return interestMessages[locale]?.interest?.[key]
    || interestMessages['en']?.interest?.[key]
    || key
}

// State
const calculations = ref([])
const meta = ref(null)
const summary = ref(null)
const customers = ref([])
const isLoading = ref(false)
const isCalculating = ref(false)
const isGenerating = ref(false)
const isSending = ref(false)
const selectedIds = ref([])
const asOfDate = ref(null)
const showSettings = ref(false)

// Sort state
const sortField = ref('calculation_date')
const sortDir = ref('desc')

// Rate override state
const customRate = ref(13.25)
const isCustomRate = ref(false)
const isSavingRate = ref(false)

const filters = reactive({
  status: null,
  customer_id: null,
  date_from: null,
  date_to: null,
})

const statusOptions = [
  { value: 'calculated', label: t('status_calculated') },
  { value: 'invoiced', label: t('status_invoiced') },
  { value: 'paid', label: t('status_paid') },
  { value: 'waived', label: t('status_waived') },
]

// Summary cards config
const summaryCards = computed(() => {
  if (!summary.value) return []
  return [
    { key: 'total', label: t('total_interest'), amount: summary.value.total_interest, bg: 'bg-white', labelColor: 'text-gray-500', valueColor: 'text-gray-900', countColor: '' },
    { key: 'pending', label: t('pending'), amount: summary.value.calculated?.amount || 0, count: summary.value.calculated?.count || 0, bg: 'bg-amber-50', labelColor: 'text-amber-600', valueColor: 'text-amber-800', countColor: 'text-amber-500' },
    { key: 'invoiced', label: t('invoiced'), amount: summary.value.invoiced?.amount || 0, count: summary.value.invoiced?.count || 0, bg: 'bg-blue-50', labelColor: 'text-blue-600', valueColor: 'text-blue-800', countColor: 'text-blue-500' },
    { key: 'paid', label: t('status_paid'), amount: summary.value.paid?.amount || 0, count: summary.value.paid?.count || 0, bg: 'bg-green-50', labelColor: 'text-green-600', valueColor: 'text-green-800', countColor: 'text-green-500' },
    { key: 'waived', label: t('status_waived'), amount: summary.value.waived?.amount || 0, count: summary.value.waived?.count || 0, bg: 'bg-gray-50', labelColor: 'text-gray-500', valueColor: 'text-gray-500', countColor: 'text-gray-400' },
  ]
})

// Table columns — removed 'rate' (same for all rows) and 'calculation_date' (usually today)
const visibleColumns = computed(() => [
  { field: 'customer_name', label: t('customer'), align: 'left', sortable: true },
  { field: 'invoice_number', label: t('invoice_number'), align: 'left', sortable: true },
  { field: 'due_date', label: t('due_date'), align: 'left', sortable: true },
  { field: 'principal_amount', label: t('principal'), align: 'right', sortable: true },
  { field: 'days_overdue', label: t('days_overdue'), align: 'right', sortable: true },
  { field: 'interest_amount', label: t('interest_amount'), align: 'right', sortable: true },
  { field: 'status', label: t('status'), align: 'center', sortable: true },
])

const allSelected = computed(() => {
  const selectableItems = calculations.value.filter(c => c.status === 'calculated' || c.status === 'invoiced')
  return selectableItems.length > 0 && selectedIds.value.length === selectableItems.length
})

// Check if all selected calculations belong to the same customer
const canGenerateNote = computed(() => {
  if (selectedIds.value.length === 0) return false
  const selectedCalcs = calculations.value.filter(c => selectedIds.value.includes(c.id))
  const customerIds = new Set(selectedCalcs.map(c => c.customer_id))
  return customerIds.size === 1
})

const paginationPages = computed(() => {
  if (!meta.value) return []
  const current = meta.value.current_page
  const last = meta.value.last_page
  const pages = []
  const delta = 2

  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= current - delta && i <= current + delta)) {
      pages.push(i)
    } else if (pages[pages.length - 1] !== '...') {
      pages.push('...')
    }
  }
  return pages
})

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDateMK(dateStr) {
  if (!dateStr) return '-'
  // Parse YYYY-MM-DD string directly to avoid timezone issues
  const parts = String(dateStr).split('T')[0].split('-')
  if (parts.length !== 3) return dateStr
  const [yyyy, mm, dd] = parts
  if (!yyyy || !mm || !dd) return dateStr
  return `${dd}.${mm}.${yyyy}`
}

function statusBadgeClass(status) {
  switch (status) {
    case 'calculated': return 'bg-amber-100 text-amber-800'
    case 'invoiced': return 'bg-blue-100 text-blue-800'
    case 'paid': return 'bg-green-100 text-green-800'
    case 'waived': return 'bg-gray-100 text-gray-600'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  switch (status) {
    case 'calculated': return t('status_calculated')
    case 'invoiced': return t('status_invoiced')
    case 'paid': return t('status_paid')
    case 'waived': return t('status_waived')
    default: return status
  }
}

function toggleSelectAll(event) {
  if (event.target.checked) {
    selectedIds.value = calculations.value
      .filter(c => c.status === 'calculated' || c.status === 'invoiced')
      .map(c => c.id)
  } else {
    selectedIds.value = []
  }
}

function toggleSort(field) {
  if (sortField.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDir.value = 'asc'
  }
  fetchCalculations(1)
}

// Data loading
async function fetchCalculations(page = 1) {
  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    params.sort_field = sortField.value
    params.sort_dir = sortDir.value
    if (filters.status) params.status = filters.status
    if (filters.customer_id) params.customer_id = filters.customer_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await window.axios.get('/interest', { params })
    calculations.value = response.data.data || []
    meta.value = response.data.meta || null
    selectedIds.value = []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function fetchSummary() {
  try {
    const response = await window.axios.get('/interest/summary')
    summary.value = response.data.data || null

    // Sync rate from summary
    if (summary.value) {
      customRate.value = summary.value.annual_rate ?? 13.25
      isCustomRate.value = summary.value.is_custom_rate ?? false
    }
  } catch {
    // Silently fail
  }
}

async function fetchCustomers() {
  try {
    const response = await window.axios.get('/customers', { params: { limit: 'all' } })
    customers.value = response.data?.customers?.data || response.data?.data || []
  } catch {
    // Silently fail
  }
}

// Rate override
async function saveRate() {
  const rate = parseFloat(customRate.value)
  if (isNaN(rate) || rate < 0 || rate > 100) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_saving_rate'),
    })
    return
  }

  // Warn on unusual rates
  if (rate > 30 || rate < 1) {
    const msg = t('confirm_high_rate').replace('{rate}', rate)
    if (!confirm(msg)) return
  }

  isSavingRate.value = true
  try {
    await window.axios.post('/interest/rate', { annual_rate: rate })
    isCustomRate.value = true
    notificationStore.showNotification({
      type: 'success',
      message: t('rate_saved'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_saving_rate'),
    })
  } finally {
    isSavingRate.value = false
  }
}

async function resetRate() {
  isSavingRate.value = true
  try {
    await window.axios.delete('/interest/rate')
    customRate.value = 13.25
    isCustomRate.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('rate_reset'),
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_saving_rate'),
    })
  } finally {
    isSavingRate.value = false
  }
}

// CSV Export
async function exportCsv() {
  try {
    const params = { limit: 'all' }
    if (filters.status) params.status = filters.status
    if (filters.customer_id) params.customer_id = filters.customer_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await window.axios.get('/interest', { params })
    const allCalcs = response.data.data || []

    if (allCalcs.length === 0) return

    const headers = [
      t('customer'), t('invoice_number'), t('due_date'),
      t('principal'), t('days_overdue'), t('rate'),
      t('interest_amount'), t('status'), t('calculation_date')
    ]

    const rows = allCalcs.map(calc => [
      calc.customer?.name || '',
      calc.invoice?.invoice_number || '',
      calc.invoice?.due_date ? formatDateMK(calc.invoice.due_date) : '',
      (calc.principal_amount / 100).toFixed(2),
      calc.days_overdue,
      calc.annual_rate,
      (calc.interest_amount / 100).toFixed(2),
      statusLabel(calc.status),
      calc.calculation_date ? formatDateMK(calc.calculation_date) : '',
    ])

    const csvContent = [headers, ...rows]
      .map(row => row.map(v => `"${String(v).replace(/"/g, '""')}"`).join(','))
      .join('\n')

    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `kamatna-presmetka-${new Date().toISOString().slice(0,10)}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading'),
    })
  }
}

// Actions
function confirmBatchCalculate() {
  const dateLabel = asOfDate.value || t('today_label')
  const msg = t('confirm_batch_calculate').replace('{date}', dateLabel)
  if (!confirm(msg)) return
  batchCalculate()
}

async function batchCalculate() {
  isCalculating.value = true
  try {
    const payload = {}
    if (asOfDate.value) payload.as_of_date = asOfDate.value

    const response = await window.axios.post('/interest/calculate', payload)

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message || t('calculated_success'),
    })

    fetchCalculations(1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_calculating'),
    })
  } finally {
    isCalculating.value = false
  }
}

function getSelectedCustomerPayload() {
  const selectedCalcs = calculations.value.filter(c => selectedIds.value.includes(c.id))
  if (selectedCalcs.length === 0) return null

  return {
    customer_id: selectedCalcs[0].customer_id,
    calculation_ids: selectedIds.value,
    customer_name: selectedCalcs[0].customer?.name || '',
  }
}

async function generateNote() {
  if (selectedIds.value.length === 0 || !canGenerateNote.value) return
  const payload = getSelectedCustomerPayload()
  if (!payload) return

  isGenerating.value = true
  try {
    const response = await window.axios.post('/interest/generate-note', {
      customer_id: payload.customer_id,
      calculation_ids: payload.calculation_ids,
    }, { responseType: 'blob' })

    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      const err = JSON.parse(text)
      notificationStore.showNotification({ type: 'error', message: err.message || t('error_generating') })
      return
    }

    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `kamatna-nota-${payload.customer_name || 'note'}.pdf`
    a.click()
    URL.revokeObjectURL(url)

    notificationStore.showNotification({
      type: 'success',
      message: t('note_generated'),
    })

    selectedIds.value = []
    fetchCalculations(1)
    fetchSummary()
  } catch (error) {
    let msg = t('error_generating')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const err = JSON.parse(text)
        msg = err.message || msg
      } catch (_) {}
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isGenerating.value = false
  }
}

async function sendNote() {
  if (selectedIds.value.length === 0 || !canGenerateNote.value) return
  const payload = getSelectedCustomerPayload()
  if (!payload) return

  const confirmMsg = t('send_note_confirm')
    .replace('{name}', payload.customer_name)
  if (!confirm(confirmMsg)) return

  isSending.value = true
  try {
    const response = await window.axios.post('/interest/send-note', {
      customer_id: payload.customer_id,
      calculation_ids: payload.calculation_ids,
    })

    const email = response.data.email || ''
    notificationStore.showNotification({
      type: 'success',
      message: t('note_sent').replace('{email}', email),
    })

    selectedIds.value = []
    fetchCalculations(1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_sending_note'),
    })
  } finally {
    isSending.value = false
  }
}

async function waiveCalculation(id) {
  if (!confirm(t('confirm_waive'))) return

  try {
    await window.axios.post(`/interest/${id}/waive`)

    notificationStore.showNotification({
      type: 'success',
      message: t('waived_success'),
    })

    fetchCalculations(meta.value?.current_page || 1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_waiving'),
    })
  }
}

async function revertCalculation(id) {
  if (!confirm(t('confirm_revert'))) return

  try {
    await window.axios.post(`/interest/${id}/revert`)

    notificationStore.showNotification({
      type: 'success',
      message: t('reverted_success'),
    })

    fetchCalculations(meta.value?.current_page || 1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_reverting'),
    })
  }
}

// Dropdown handlers
function onDropdownGenerate(row) {
  selectedIds.value = [row.id]
  generateNote()
}

function onDropdownWaive(row) {
  waiveCalculation(row.id)
}

function onDropdownRevert(row) {
  revertCalculation(row.id)
}

// Lifecycle
onMounted(() => {
  fetchCalculations(1)
  fetchSummary()
  fetchCustomers()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
