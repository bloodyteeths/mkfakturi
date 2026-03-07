<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="showFilters || orders.length > 0"
          variant="primary-outline"
          class="ml-2"
          @click="showFilters = !showFilters"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link to="travel-orders/create">
          <BaseButton variant="primary" class="ml-2">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('create') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Summary Cards -->
    <div v-if="summary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase font-medium">{{ t('total_orders') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ summary.total_orders || 0 }}</p>
      </div>
      <div class="bg-yellow-50 rounded-lg shadow p-4 border border-yellow-200">
        <p class="text-xs text-yellow-600 uppercase font-medium">{{ t('pending_count') }}</p>
        <p class="text-2xl font-bold text-yellow-800">{{ summary.pending_approval || 0 }}</p>
      </div>
      <div class="bg-blue-50 rounded-lg shadow p-4 border border-blue-200">
        <p class="text-xs text-blue-600 uppercase font-medium">{{ t('total_per_diem') }}</p>
        <p class="text-2xl font-bold text-blue-800">{{ formatMoney(summary.total_per_diem) }}</p>
      </div>
      <div class="bg-green-50 rounded-lg shadow p-4 border border-green-200">
        <p class="text-xs text-green-600 uppercase font-medium">{{ t('total_expenses') }}</p>
        <p class="text-2xl font-bold text-green-800">{{ formatMoney(summary.total_expenses) }}</p>
      </div>
    </div>

    <!-- Filters -->
    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilters"
    >
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

      <BaseInputGroup :label="$t('general.to')">
        <BaseDatePicker
          v-model="filters.date_to"
          :calendar-button="true"
          calendar-button-icon="CalendarDaysIcon"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

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
                {{ t('employee') }}
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
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="viewOrder(order.id)"
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
                {{ order.employee ? `${order.employee.first_name} ${order.employee.last_name}` : '-' }}
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
            @click="goToPage(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="MapIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_travel_orders') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_travel_orders_description') }}
      </p>
      <div class="mt-6">
        <router-link to="travel-orders/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('create') }}
          </BaseButton>
        </router-link>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import travelMessages from '@/scripts/admin/i18n/travel-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()
const { locale: i18nLocale } = useI18n()

function t(key) {
  const l = i18nLocale.value || 'mk'
  return travelMessages[l]?.travel_orders?.[key]
    || travelMessages['en']?.travel_orders?.[key]
    || key
}

// State
const orders = ref([])
const meta = ref(null)
const summary = ref(null)
const isLoading = ref(false)
const showFilters = ref(false)
const currentPage = ref(1)

const filters = reactive({
  status: null,
  type: null,
  date_from: null,
  date_to: null,
})

const statusOptions = computed(() => [
  { value: 'draft', label: t('status_draft') },
  { value: 'pending_approval', label: t('status_pending_approval') },
  { value: 'approved', label: t('status_approved') },
  { value: 'settled', label: t('status_settled') },
  { value: 'rejected', label: t('status_rejected') },
])

const typeOptions = computed(() => [
  { value: 'domestic', label: t('domestic') },
  { value: 'foreign', label: t('foreign') },
])

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const fmtLocale = localeMap[i18nLocale.value] || 'mk-MK'
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

async function fetchOrders(page = 1) {
  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.type) params.type = filters.type
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await window.axios.get('/travel-orders', { params })
    orders.value = response.data.data || []
    meta.value = response.data.meta || null
    currentPage.value = page
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
    const response = await window.axios.get('/travel-orders/summary')
    summary.value = response.data.data || null
  } catch {
    // Silently fail
  }
}

function goToPage(page) {
  fetchOrders(page)
}

function viewOrder(id) {
  router.push({ path: `travel-orders/${id}` })
}

function clearFilters() {
  filters.status = null
  filters.type = null
  filters.date_from = null
  filters.date_to = null
  fetchOrders(1)
}

watch([() => filters.status, () => filters.type, () => filters.date_from, () => filters.date_to], () => {
  fetchOrders(1)
})

// Lifecycle
onMounted(() => {
  fetchOrders()
  fetchSummary()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
