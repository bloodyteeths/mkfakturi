<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="showFilters || purchaseOrders.length > 0"
          variant="primary-outline"
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

        <router-link to="purchase-orders/create">
          <BaseButton variant="primary" class="ml-2">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('new_po') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Status Pipeline -->
    <div class="mb-6 flex flex-wrap gap-2">
      <button
        v-for="statusOpt in statusPipeline"
        :key="statusOpt.value"
        :class="[
          'inline-flex items-center px-3 py-1.5 rounded-full text-xs font-medium border transition-colors',
          filters.status === statusOpt.value
            ? statusOpt.activeClass
            : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
        ]"
        @click="toggleStatusFilter(statusOpt.value)"
      >
        <span
          :class="['w-2 h-2 rounded-full mr-1.5', statusOpt.dotClass]"
        />
        {{ statusOpt.label }}
        <span v-if="statusCounts[statusOpt.value]" class="ml-1.5 text-xs opacity-75">
          ({{ statusCounts[statusOpt.value] }})
        </span>
      </button>
    </div>

    <!-- Filters -->
    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilters"
    >
      <BaseInputGroup :label="t('supplier')">
        <BaseMultiselect
          v-model="filters.supplier_id"
          :options="suppliers"
          :searchable="true"
          label="name"
          value-prop="id"
          :placeholder="t('select_supplier')"
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

      <BaseInputGroup :label="$t('general.search')">
        <BaseInput
          v-model="filters.search"
          type="text"
          :placeholder="t('search_placeholder')"
          @input="debouncedFetch"
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
    <div v-else-if="purchaseOrders.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('po_number') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('date') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('supplier') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('items') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('total') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('status') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('expected_delivery') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr
              v-for="po in purchaseOrders"
              :key="po.id"
              class="hover:bg-gray-50 cursor-pointer"
              @click="viewPo(po.id)"
            >
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-primary-500">
                {{ po.po_number }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(po.po_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ po.supplier?.name || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
                {{ po.items?.length || 0 }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                {{ formatMoney(po.total) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center">
                <span
                  :class="statusBadgeClass(po.status)"
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                >
                  {{ statusLabel(po.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ po.expected_delivery_date ? formatDate(po.expected_delivery_date) : '-' }}
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
      <BaseIcon name="ClipboardDocumentListIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_purchase_orders') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_purchase_orders_description') }}
      </p>
      <div class="mt-6">
        <router-link to="purchase-orders/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('new_po') }}
          </BaseButton>
        </router-link>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'
import poMessages from '@/scripts/admin/i18n/purchase-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return poMessages[locale]?.purchaseOrders?.[key]
    || poMessages['en']?.purchaseOrders?.[key]
    || key
}

// State
const purchaseOrders = ref([])
const meta = ref(null)
const suppliers = ref([])
const isLoading = ref(false)
const showFilters = ref(false)
const statusCounts = ref({})

const filters = reactive({
  status: null,
  supplier_id: null,
  date_from: null,
  date_to: null,
  search: '',
})

const statusPipeline = [
  { value: 'draft', label: t('status_draft'), dotClass: 'bg-gray-400', activeClass: 'bg-gray-100 text-gray-800 border-gray-400' },
  { value: 'sent', label: t('status_sent'), dotClass: 'bg-blue-400', activeClass: 'bg-blue-100 text-blue-800 border-blue-400' },
  { value: 'acknowledged', label: t('status_acknowledged'), dotClass: 'bg-indigo-400', activeClass: 'bg-indigo-100 text-indigo-800 border-indigo-400' },
  { value: 'partially_received', label: t('status_partially_received'), dotClass: 'bg-yellow-400', activeClass: 'bg-yellow-100 text-yellow-800 border-yellow-400' },
  { value: 'fully_received', label: t('status_fully_received'), dotClass: 'bg-green-400', activeClass: 'bg-green-100 text-green-800 border-green-400' },
  { value: 'billed', label: t('status_billed'), dotClass: 'bg-purple-400', activeClass: 'bg-purple-100 text-purple-800 border-purple-400' },
  { value: 'closed', label: t('status_closed'), dotClass: 'bg-gray-600', activeClass: 'bg-gray-200 text-gray-900 border-gray-600' },
  { value: 'cancelled', label: t('status_cancelled'), dotClass: 'bg-red-400', activeClass: 'bg-red-100 text-red-800 border-red-400' },
]

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

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function statusBadgeClass(status) {
  switch (status) {
    case 'draft': return 'bg-gray-100 text-gray-700'
    case 'sent': return 'bg-blue-100 text-blue-800'
    case 'acknowledged': return 'bg-indigo-100 text-indigo-800'
    case 'partially_received': return 'bg-yellow-100 text-yellow-800'
    case 'fully_received': return 'bg-green-100 text-green-800'
    case 'billed': return 'bg-purple-100 text-purple-800'
    case 'closed': return 'bg-gray-200 text-gray-900'
    case 'cancelled': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  const key = 'status_' + status
  return t(key)
}

function toggleStatusFilter(status) {
  if (filters.status === status) {
    filters.status = null
  } else {
    filters.status = status
  }
}

async function fetchPurchaseOrders(page = 1) {
  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.supplier_id) params.supplier_id = filters.supplier_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    if (filters.search) params.search = filters.search

    const response = await window.axios.get('/purchase-orders', { params })
    purchaseOrders.value = response.data.data || []
    meta.value = response.data.meta || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load purchase orders',
    })
  } finally {
    isLoading.value = false
  }
}

async function fetchSuppliers() {
  try {
    const response = await window.axios.get('/suppliers', { params: { limit: 'all' } })
    suppliers.value = response.data?.suppliers?.data || response.data?.data || []
  } catch {
    suppliers.value = []
  }
}

function goToPage(page) {
  fetchPurchaseOrders(page)
}

function viewPo(id) {
  router.push({ path: `purchase-orders/${id}` })
}

function clearFilters() {
  filters.status = null
  filters.supplier_id = null
  filters.date_from = null
  filters.date_to = null
  filters.search = ''
  fetchPurchaseOrders(1)
}

const debouncedFetch = debounce(() => {
  fetchPurchaseOrders(1)
}, 400)

watch(
  [() => filters.status, () => filters.supplier_id, () => filters.date_from, () => filters.date_to],
  () => { fetchPurchaseOrders(1) }
)

// Lifecycle
onMounted(() => {
  fetchPurchaseOrders()
  fetchSuppliers()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
