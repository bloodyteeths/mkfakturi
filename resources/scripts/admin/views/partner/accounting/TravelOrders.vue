<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          v-if="selectedCompanyId"
          variant="primary-outline"
          :loading="isLoading"
          @click="fetchOrders(1)"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowPathIcon" />
          </template>
          {{ $t('reports.update_report') }}
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

    <div v-if="!selectedCompanyId" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ $t('partner.select_company_placeholder') }}</p>
    </div>

    <!-- Filters -->
    <div v-if="selectedCompanyId" class="p-4 bg-white rounded-lg shadow mb-6">
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

        <BaseInputGroup :label="t('type')">
          <BaseMultiselect
            v-model="filters.type"
            :options="typeOptions"
            :searchable="false"
            label="label"
            value-prop="value"
            :placeholder="t('type')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.from')">
          <BaseDatePicker
            v-model="filters.date_from"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
            :loading="isLoading"
            @click="fetchOrders(1)"
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
    <div v-else-if="orders.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('number') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('type') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purpose') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('departure') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('grand_total') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('status') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="viewOrder(order)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-500">
                {{ order.travel_number }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ order.type === 'domestic' ? t('domestic') : t('foreign') }}
              </td>
              <td class="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                {{ order.purpose }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(order.departure_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                {{ formatMoney(order.grand_total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span :class="statusBadgeClass(order.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ statusLabel(order.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                <div class="flex items-center justify-end space-x-2" @click.stop>
                  <BaseButton
                    v-if="order.status === 'draft' || order.status === 'pending_approval'"
                    variant="primary"
                    size="sm"
                    @click="approveOrder(order.id)"
                  >
                    {{ t('approve') }}
                  </BaseButton>
                  <BaseButton
                    v-if="order.status === 'approved'"
                    variant="primary-outline"
                    size="sm"
                    @click="settleOrder(order.id)"
                  >
                    {{ t('settle') }}
                  </BaseButton>
                </div>
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
            @click="fetchOrders(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="selectedCompanyId && !isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16"
    >
      <BaseIcon name="MapIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_travel_orders') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_travel_orders_description') }}
      </p>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal && selectedOrder" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showDetailModal = false" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[85vh] overflow-y-auto p-6">
        <div class="flex items-center justify-between mb-4">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ selectedOrder.travel_number }}</h3>
            <p class="text-sm text-gray-500">{{ selectedOrder.purpose }}</p>
          </div>
          <div class="flex items-center space-x-2">
            <span :class="statusBadgeClass(selectedOrder.status)" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
              {{ statusLabel(selectedOrder.status) }}
            </span>
            <button class="text-gray-400 hover:text-gray-600" @click="showDetailModal = false">
              <BaseIcon name="XMarkIcon" class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Totals -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
          <div class="bg-blue-50 rounded p-3">
            <p class="text-xs text-blue-600 uppercase font-medium">{{ t('total_per_diem') }}</p>
            <p class="text-lg font-bold text-blue-800">{{ formatMoney(selectedOrder.total_per_diem) }}</p>
          </div>
          <div class="bg-amber-50 rounded p-3">
            <p class="text-xs text-amber-600 uppercase font-medium">{{ t('total_mileage') }}</p>
            <p class="text-lg font-bold text-amber-800">{{ formatMoney(selectedOrder.total_mileage_cost) }}</p>
          </div>
          <div class="bg-purple-50 rounded p-3">
            <p class="text-xs text-purple-600 uppercase font-medium">{{ t('total_expenses') }}</p>
            <p class="text-lg font-bold text-purple-800">{{ formatMoney(selectedOrder.total_expenses) }}</p>
          </div>
          <div class="bg-green-50 rounded p-3">
            <p class="text-xs text-green-600 uppercase font-medium">{{ t('grand_total') }}</p>
            <p class="text-lg font-bold text-green-800">{{ formatMoney(selectedOrder.grand_total) }}</p>
          </div>
          <div class="bg-indigo-50 rounded p-3">
            <p class="text-xs text-indigo-600 uppercase font-medium">{{ t('reimbursement') }}</p>
            <p class="text-lg font-bold text-indigo-800">{{ formatMoney(selectedOrder.reimbursement_amount) }}</p>
          </div>
        </div>

        <!-- Segments -->
        <div v-if="selectedOrder.segments && selectedOrder.segments.length > 0" class="mb-4">
          <h4 class="text-xs font-semibold text-blue-800 uppercase mb-1">{{ t('segments') }}</h4>
          <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('from_city') }}</th>
                <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('to_city') }}</th>
                <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('transport_type') }}</th>
                <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('per_diem') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="seg in selectedOrder.segments" :key="seg.id">
                <td class="px-3 py-2">{{ seg.from_city }}</td>
                <td class="px-3 py-2">{{ seg.to_city }}</td>
                <td class="px-3 py-2 text-gray-500">{{ transportLabel(seg.transport_type) }}</td>
                <td class="px-3 py-2 text-right font-medium text-blue-700">{{ formatMoney(seg.per_diem_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Expenses -->
        <div v-if="selectedOrder.expenses && selectedOrder.expenses.length > 0" class="mb-4">
          <h4 class="text-xs font-semibold text-purple-800 uppercase mb-1">{{ t('expenses') }}</h4>
          <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('category') }}</th>
                <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('description') }}</th>
                <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('amount') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="exp in selectedOrder.expenses" :key="exp.id">
                <td class="px-3 py-2 text-gray-500">{{ categoryLabel(exp.category) }}</td>
                <td class="px-3 py-2">{{ exp.description }}</td>
                <td class="px-3 py-2 text-right font-medium text-gray-900">{{ formatMoney(exp.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Notes -->
        <div v-if="selectedOrder.notes" class="text-sm text-gray-600 bg-gray-50 rounded p-3">
          <strong>{{ t('notes') }}:</strong> {{ selectedOrder.notes }}
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import travelMessages from '@/scripts/admin/i18n/travel-orders.js'

const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return travelMessages[locale]?.travel_orders?.[key]
    || travelMessages['en']?.travel_orders?.[key]
    || key
}

// Transport & category labels
const transportLabelsMap = {
  car: t('transport_car'),
  bus: t('transport_bus'),
  train: t('transport_train'),
  plane: t('transport_plane'),
  other: t('transport_other'),
}

const categoryLabelsMap = {
  transport: t('category_transport'),
  accommodation: t('category_accommodation'),
  meals: t('category_meals'),
  other: t('category_other'),
}

function transportLabel(type) {
  return transportLabelsMap[type] || type
}

function categoryLabel(cat) {
  return categoryLabelsMap[cat] || cat
}

// State
const selectedCompanyId = ref(null)
const orders = ref([])
const meta = ref(null)
const isLoading = ref(false)

// Detail modal
const showDetailModal = ref(false)
const selectedOrder = ref(null)
const isLoadingDetail = ref(false)

const filters = reactive({
  status: null,
  type: null,
  date_from: null,
})

const statusOptions = [
  { value: 'draft', label: t('status_draft') },
  { value: 'pending_approval', label: t('status_pending_approval') },
  { value: 'approved', label: t('status_approved') },
  { value: 'settled', label: t('status_settled') },
  { value: 'rejected', label: t('status_rejected') },
]

const typeOptions = [
  { value: 'domestic', label: t('domestic') },
  { value: 'foreign', label: t('foreign') },
]

const companies = computed(() => consoleStore.managedCompanies || [])

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'pending_approval': return 'bg-yellow-100 text-yellow-800'
    case 'approved': return 'bg-green-100 text-green-800'
    case 'settled': return 'bg-blue-100 text-blue-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  const key = `status_${status}`
  return t(key)
}

function onCompanyChange() {
  orders.value = []
  meta.value = null
  if (selectedCompanyId.value) {
    fetchOrders(1)
  }
}

async function fetchOrders(page = 1) {
  if (!selectedCompanyId.value) return

  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.type) params.type = filters.type
    if (filters.date_from) params.date_from = filters.date_from

    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/travel-orders`,
      { params }
    )
    orders.value = response.data.data || []
    meta.value = response.data.meta || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

async function viewOrder(order) {
  isLoadingDetail.value = true
  selectedOrder.value = null

  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/travel-orders/${order.id}`
    )
    selectedOrder.value = response.data?.data || null
    showDetailModal.value = true
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading'),
    })
  } finally {
    isLoadingDetail.value = false
  }
}

async function approveOrder(id) {
  try {
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/accounting/travel-orders/${id}/approve`
    )
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('approved_success'),
    })
    fetchOrders(1)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_approving'),
    })
  }
}

async function settleOrder(id) {
  try {
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/accounting/travel-orders/${id}/settle`
    )
    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('settled_success'),
    })
    fetchOrders(1)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_settling'),
    })
  }
}

// Lifecycle
onMounted(() => {
  // Companies are loaded by the console store
})
</script>

<!-- CLAUDE-CHECKPOINT -->
