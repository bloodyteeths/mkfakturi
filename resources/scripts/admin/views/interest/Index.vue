<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          variant="primary"
          :loading="isCalculating"
          @click="batchCalculate"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="CalculatorIcon" />
          </template>
          {{ t('batch_calculate') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Summary Cards -->
    <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('total_interest') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ formatMoney(summary.total_interest) }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('pending') }}</p>
        <p class="text-2xl font-bold text-amber-600">{{ formatMoney(summary.calculated?.amount || 0) }}</p>
        <p class="text-xs text-gray-400">{{ summary.calculated?.count || 0 }} {{ t('title').toLowerCase() }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('invoiced') }}</p>
        <p class="text-2xl font-bold text-blue-600">{{ formatMoney(summary.invoiced?.amount || 0) }}</p>
        <p class="text-xs text-gray-400">{{ summary.invoiced?.count || 0 }} {{ t('title').toLowerCase() }}</p>
      </div>
    </div>

    <!-- Info Banner -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
      <p class="text-sm text-blue-800">
        <strong>{{ t('nbrm_rate') }}:</strong> {{ t('mk_law_info') }}
      </p>
      <p class="text-xs text-blue-600 mt-1">{{ t('formula_info') }}</p>
    </div>

    <!-- Filters -->
    <div class="p-4 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInputGroup :label="t('status')">
          <BaseMultiselect
            v-model="filters.status"
            :options="statusOptions"
            :searchable="false"
            label="label"
            value-prop="value"
            :placeholder="$t('general.select_a_status')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('customer')">
          <BaseMultiselect
            v-model="filters.customer_id"
            :options="customers"
            :searchable="true"
            label="name"
            value-prop="id"
            placeholder="..."
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('as_of_date')">
          <BaseDatePicker
            v-model="asOfDate"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
            :loading="isLoading"
            @click="fetchCalculations(1)"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('reports.update_report') }}
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
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-else-if="calculations.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <!-- Generate Note Action Bar -->
      <div v-if="selectedIds.length > 0" class="bg-amber-50 border-b border-amber-200 px-6 py-3 flex items-center justify-between">
        <span class="text-sm text-amber-800">
          {{ selectedIds.length }} {{ t('select_for_note') }}
        </span>
        <BaseButton
          variant="primary"
          size="sm"
          :loading="isGenerating"
          @click="generateNote"
        >
          {{ t('generate_note') }}
        </BaseButton>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500">
                <input
                  type="checkbox"
                  class="rounded border-gray-300"
                  @change="toggleSelectAll"
                  :checked="allSelected"
                />
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('customer') }}
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('invoice_number') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('principal') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('days_overdue') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('rate') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('interest_amount') }}
              </th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('status') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
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
              <td class="px-4 py-4">
                <input
                  v-if="calc.status === 'calculated'"
                  type="checkbox"
                  class="rounded border-gray-300"
                  :value="calc.id"
                  v-model="selectedIds"
                />
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ calc.customer?.name || '-' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-primary-500">
                {{ calc.invoice?.invoice_number || '-' }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                {{ formatMoney(calc.principal_amount) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-red-600 font-medium">
                {{ calc.days_overdue }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-right text-gray-500">
                {{ calc.annual_rate }}%
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                {{ formatMoney(calc.interest_amount) }}
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-center">
                <span :class="statusBadgeClass(calc.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ statusLabel(calc.status) }}
                </span>
              </td>
              <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                <BaseButton
                  v-if="calc.status === 'calculated'"
                  variant="danger-outline"
                  size="sm"
                  @click="waiveCalculation(calc.id)"
                >
                  {{ t('waive') }}
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">
          {{ meta.total }} {{ t('title').toLowerCase() }}
        </p>
        <div class="flex space-x-1">
          <BaseButton
            v-for="page in meta.last_page"
            :key="page"
            :variant="page === meta.current_page ? 'primary' : 'primary-outline'"
            size="sm"
            @click="fetchCalculations(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16"
    >
      <BaseIcon name="CalculatorIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_calculations') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_calculations_description') }}
      </p>
    </div>

    <!-- Customer Summary Section -->
    <div v-if="summary && summary.by_customer && summary.by_customer.length > 0" class="mt-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('by_customer') }}</h3>
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_principal') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_interest') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">#</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="row in summary.by_customer" :key="row.customer_id" class="hover:bg-gray-50">
              <td class="px-6 py-4 text-sm text-gray-900">{{ row.customer_name }}</td>
              <td class="px-6 py-4 text-sm text-right text-gray-500">{{ formatMoney(row.total_principal) }}</td>
              <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">{{ formatMoney(row.total_interest) }}</td>
              <td class="px-6 py-4 text-sm text-right text-gray-400">{{ row.count }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import interestMessages from '@/scripts/admin/i18n/interest.js'

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
const selectedIds = ref([])
const asOfDate = ref(null)

const filters = reactive({
  status: null,
  customer_id: null,
})

const statusOptions = [
  { value: 'calculated', label: t('status_calculated') },
  { value: 'invoiced', label: t('status_invoiced') },
  { value: 'paid', label: t('status_paid') },
  { value: 'waived', label: t('status_waived') },
]

const allSelected = computed(() => {
  const calculatedItems = calculations.value.filter(c => c.status === 'calculated')
  return calculatedItems.length > 0 && selectedIds.value.length === calculatedItems.length
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
      .filter(c => c.status === 'calculated')
      .map(c => c.id)
  } else {
    selectedIds.value = []
  }
}

async function fetchCalculations(page = 1) {
  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.customer_id) params.customer_id = filters.customer_id

    const response = await window.axios.get('/mk/interest', { params })
    calculations.value = response.data.data || []
    meta.value = response.data.meta || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load interest calculations',
    })
  } finally {
    isLoading.value = false
  }
}

async function fetchSummary() {
  try {
    const response = await window.axios.get('/mk/interest/summary')
    summary.value = response.data.data || null
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

async function batchCalculate() {
  isCalculating.value = true
  try {
    const payload = {}
    if (asOfDate.value) payload.as_of_date = asOfDate.value

    const response = await window.axios.post('/mk/interest/calculate', payload)

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message || t('calculated_success') || 'Interest calculated successfully.',
    })

    fetchCalculations(1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_calculating') || 'Calculation failed',
    })
  } finally {
    isCalculating.value = false
  }
}

async function generateNote() {
  if (selectedIds.value.length === 0) return

  // Get customer_id from first selected
  const firstCalc = calculations.value.find(c => selectedIds.value.includes(c.id))
  if (!firstCalc) return

  isGenerating.value = true
  try {
    const response = await window.axios.post('/mk/interest/generate-note', {
      customer_id: firstCalc.customer_id,
      calculation_ids: selectedIds.value,
    })

    notificationStore.showNotification({
      type: 'success',
      message: response.data.message || t('note_generated') || 'Interest note generated.',
    })

    selectedIds.value = []
    fetchCalculations(1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_generating') || 'Failed to generate interest note',
    })
  } finally {
    isGenerating.value = false
  }
}

async function waiveCalculation(id) {
  if (!confirm(t('confirm_waive'))) return

  try {
    await window.axios.post(`/mk/interest/${id}/waive`)

    notificationStore.showNotification({
      type: 'success',
      message: t('waived_success') || 'Interest waived.',
    })

    fetchCalculations(meta.value?.current_page || 1)
    fetchSummary()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_waiving') || 'Failed to waive interest',
    })
  }
}

// Lifecycle
onMounted(() => {
  fetchCalculations(1)
  fetchSummary()
  fetchCustomers()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
