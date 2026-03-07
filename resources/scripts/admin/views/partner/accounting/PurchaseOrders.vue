<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <!-- placeholder for future partner actions -->
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

        <div class="flex items-end">
          <BaseButton
            variant="primary"
            class="w-full"
            :loading="isLoading"
            @click="fetchPurchaseOrders(1)"
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
            @click="fetchPurchaseOrders(page)"
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
      <BaseIcon name="ClipboardDocumentListIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ t('no_purchase_orders') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ t('no_purchase_orders_description') }}
      </p>
    </div>

    <!-- Select Company -->
    <div
      v-else-if="!selectedCompanyId"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <!-- Detail Modal -->
    <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeDetail" />
      <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[85vh] overflow-y-auto">
        <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between z-10">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ selectedPo?.po_number }}</h3>
            <span
              :class="statusBadgeClass(selectedPo?.status)"
              class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium mt-1"
            >
              {{ statusLabel(selectedPo?.status) }}
            </span>
          </div>
          <button class="text-gray-400 hover:text-gray-600" @click="closeDetail">
            <BaseIcon name="XMarkIcon" class="h-5 w-5" />
          </button>
        </div>

        <div v-if="isLoadingDetail" class="p-6 space-y-4">
          <div v-for="i in 4" :key="i" class="flex space-x-4 animate-pulse">
            <div class="h-4 bg-gray-200 rounded flex-1"></div>
          </div>
        </div>

        <div v-else-if="selectedPo" class="p-6 space-y-4">
          <!-- Summary -->
          <div class="grid grid-cols-3 gap-3">
            <div class="bg-blue-50 rounded p-3 text-center">
              <p class="text-xs text-blue-600 uppercase">{{ t('sub_total') }}</p>
              <p class="text-lg font-bold text-blue-800">{{ formatMoney(selectedPo.sub_total) }}</p>
            </div>
            <div class="bg-amber-50 rounded p-3 text-center">
              <p class="text-xs text-amber-600 uppercase">{{ t('tax_amount') }}</p>
              <p class="text-lg font-bold text-amber-800">{{ formatMoney(selectedPo.tax) }}</p>
            </div>
            <div class="bg-green-50 rounded p-3 text-center">
              <p class="text-xs text-green-600 uppercase">{{ t('total') }}</p>
              <p class="text-lg font-bold text-green-800">{{ formatMoney(selectedPo.total) }}</p>
            </div>
          </div>

          <!-- Info -->
          <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span class="text-gray-500">{{ t('supplier') }}:</span>
              <span class="ml-1 font-medium">{{ selectedPo.supplier?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ t('expected_delivery') }}:</span>
              <span class="ml-1 font-medium">{{ selectedPo.expected_delivery_date ? formatDate(selectedPo.expected_delivery_date) : '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ t('warehouse') }}:</span>
              <span class="ml-1 font-medium">{{ selectedPo.warehouse?.name || '-' }}</span>
            </div>
            <div>
              <span class="text-gray-500">{{ t('created_by') }}:</span>
              <span class="ml-1 font-medium">{{ selectedPo.created_by?.name || '-' }}</span>
            </div>
          </div>

          <!-- Items -->
          <div>
            <h4 class="text-xs font-semibold text-gray-800 uppercase mb-1">{{ t('items') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-1 text-left text-xs font-medium text-gray-500">{{ t('item_name') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('quantity_ordered') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('quantity_received') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('price') }}</th>
                  <th class="px-3 py-1 text-right text-xs font-medium text-gray-500">{{ t('item_total') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in selectedPo.items" :key="item.id">
                  <td class="px-3 py-2">{{ item.name }}</td>
                  <td class="px-3 py-2 text-right">{{ item.quantity }}</td>
                  <td class="px-3 py-2 text-right">
                    <span :class="item.received_quantity >= item.quantity ? 'text-green-600' : 'text-amber-600'">
                      {{ item.received_quantity }}
                    </span>
                  </td>
                  <td class="px-3 py-2 text-right text-gray-500">{{ formatMoney(item.price) }}</td>
                  <td class="px-3 py-2 text-right font-medium">{{ formatMoney(item.total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-if="selectedPo.notes" class="text-sm text-gray-600 bg-gray-50 rounded p-3">
            <strong>{{ t('notes') }}:</strong> {{ selectedPo.notes }}
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/purchase-orders.js'

const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return poMessages[locale]?.purchaseOrders?.[key]
    || poMessages['en']?.purchaseOrders?.[key]
    || key
}

// State
const selectedCompanyId = ref(null)
const purchaseOrders = ref([])
const meta = ref(null)
const isLoading = ref(false)

// Detail modal
const showDetailModal = ref(false)
const selectedPo = ref(null)
const isLoadingDetail = ref(false)

const filters = reactive({
  status: null,
  date_from: null,
  date_to: null,
})

const statusOptions = [
  { value: 'draft', label: t('status_draft') },
  { value: 'sent', label: t('status_sent') },
  { value: 'acknowledged', label: t('status_acknowledged') },
  { value: 'partially_received', label: t('status_partially_received') },
  { value: 'fully_received', label: t('status_fully_received') },
  { value: 'billed', label: t('status_billed') },
  { value: 'closed', label: t('status_closed') },
  { value: 'cancelled', label: t('status_cancelled') },
]

const companies = computed(() => consoleStore.managedCompanies || [])

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

function onCompanyChange() {
  purchaseOrders.value = []
  meta.value = null
  if (selectedCompanyId.value) {
    fetchPurchaseOrders(1)
  }
}

async function fetchPurchaseOrders(page = 1) {
  if (!selectedCompanyId.value) return

  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/purchase-orders`,
      { params }
    )
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

async function viewPo(id) {
  showDetailModal.value = true
  isLoadingDetail.value = true
  selectedPo.value = null

  try {
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/accounting/purchase-orders/${id}`
    )
    selectedPo.value = response.data?.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load purchase order details',
    })
    showDetailModal.value = false
  } finally {
    isLoadingDetail.value = false
  }
}

function closeDetail() {
  showDetailModal.value = false
  selectedPo.value = null
}

// Lifecycle
onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
      fetchPurchaseOrders(1)
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load companies',
    })
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
