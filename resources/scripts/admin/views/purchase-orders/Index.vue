<template>
  <BasePage>
    <BasePageHeader :title="t('purchaseOrders.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('purchaseOrders.title')" to="#" active />
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
            {{ t('purchaseOrders.new_po') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Help Box (collapsible) -->
    <div v-if="showHelp" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
      <div class="flex items-start justify-between">
        <div class="flex items-start gap-3">
          <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-500 mt-0.5 shrink-0" />
          <div>
            <h4 class="text-sm font-semibold text-blue-900">{{ t('purchaseOrders.help_title') }}</h4>
            <p class="mt-1 text-sm text-blue-700">{{ t('purchaseOrders.help_description') }}</p>
            <ol class="mt-2 space-y-1 text-sm text-blue-700 list-decimal list-inside">
              <li>{{ t('purchaseOrders.help_step_1') }}</li>
              <li>{{ t('purchaseOrders.help_step_2') }}</li>
              <li>{{ t('purchaseOrders.help_step_3') }}</li>
              <li>{{ t('purchaseOrders.help_step_4') }}</li>
              <li>{{ t('purchaseOrders.help_step_5') }}</li>
            </ol>
          </div>
        </div>
        <button class="text-blue-400 hover:text-blue-600" @click="dismissHelp">
          <BaseIcon name="XMarkIcon" class="h-4 w-4" />
        </button>
      </div>
    </div>

    <!-- Filters -->
    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilters"
    >
      <BaseInputGroup :label="t('purchaseOrders.status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          label="label"
          value-prop="value"
          :placeholder="t('purchaseOrders.select_status')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="t('purchaseOrders.supplier')">
        <BaseMultiselect
          v-model="filters.supplier_id"
          :options="suppliers"
          :searchable="true"
          label="name"
          value-prop="id"
          :placeholder="t('purchaseOrders.select_supplier')"
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
          :placeholder="t('purchaseOrders.search_placeholder')"
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
                {{ t('purchaseOrders.po_number') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purchaseOrders.date') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purchaseOrders.supplier') }}
              </th>
              <th class="hidden sm:table-cell px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purchaseOrders.items') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purchaseOrders.total') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purchaseOrders.status') }}
              </th>
              <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ t('purchaseOrders.expected_delivery') }}
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
              <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500">
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
              <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ po.expected_delivery_date ? formatDate(po.expected_delivery_date) : '-' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="px-6 py-3 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">
          {{ meta.total }} {{ t('purchaseOrders.title').toLowerCase() }}
        </p>
        <div class="flex space-x-1">
          <BaseButton
            v-for="page in paginationPages"
            :key="page"
            :variant="page === meta.current_page ? 'primary' : 'primary-outline'"
            size="sm"
            class="min-w-[36px] min-h-[36px]"
            :disabled="page === '...'"
            @click="page !== '...' && goToPage(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg border-2 border-dashed border-gray-300 py-12 px-6">
      <div class="text-center">
        <BaseIcon name="ClipboardDocumentListIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-3 text-base font-semibold text-gray-900">
          {{ t('purchaseOrders.empty_title') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500 max-w-md mx-auto">
          {{ t('purchaseOrders.empty_description') }}
        </p>
      </div>

      <!-- Workflow Steps -->
      <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 max-w-3xl mx-auto">
        <div class="relative rounded-lg border border-gray-200 bg-white p-4 text-center">
          <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-primary-100 text-primary-600 text-sm font-bold">1</div>
          <h4 class="mt-2 text-sm font-semibold text-gray-900">{{ t('purchaseOrders.empty_step_1_title') }}</h4>
          <p class="mt-1 text-xs text-gray-500">{{ t('purchaseOrders.empty_step_1_desc') }}</p>
        </div>
        <div class="relative rounded-lg border border-gray-200 bg-white p-4 text-center">
          <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 text-sm font-bold">2</div>
          <h4 class="mt-2 text-sm font-semibold text-gray-900">{{ t('purchaseOrders.empty_step_2_title') }}</h4>
          <p class="mt-1 text-xs text-gray-500">{{ t('purchaseOrders.empty_step_2_desc') }}</p>
        </div>
        <div class="relative rounded-lg border border-gray-200 bg-white p-4 text-center">
          <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-green-100 text-green-600 text-sm font-bold">3</div>
          <h4 class="mt-2 text-sm font-semibold text-gray-900">{{ t('purchaseOrders.empty_step_3_title') }}</h4>
          <p class="mt-1 text-xs text-gray-500">{{ t('purchaseOrders.empty_step_3_desc') }}</p>
        </div>
        <div class="relative rounded-lg border border-gray-200 bg-white p-4 text-center">
          <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-purple-100 text-purple-600 text-sm font-bold">4</div>
          <h4 class="mt-2 text-sm font-semibold text-gray-900">{{ t('purchaseOrders.empty_step_4_title') }}</h4>
          <p class="mt-1 text-xs text-gray-500">{{ t('purchaseOrders.empty_step_4_desc') }}</p>
        </div>
      </div>

      <div class="mt-8 text-center">
        <router-link to="purchase-orders/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('purchaseOrders.new_po') }}
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
import { debounce } from 'lodash'

const router = useRouter()
const notificationStore = useNotificationStore()
const { t, locale } = useI18n()

// State
const purchaseOrders = ref([])
const meta = ref(null)
const suppliers = ref([])
const isLoading = ref(false)
const showFilters = ref(false)
const showHelp = ref(localStorage.getItem('po_help_dismissed') !== '1')
const filters = reactive({
  status: null,
  supplier_id: null,
  date_from: null,
  date_to: null,
  search: '',
})

const statusOptions = computed(() => [
  { value: 'draft', label: t('purchaseOrders.status_draft') },
  { value: 'sent', label: t('purchaseOrders.status_sent') },
  { value: 'acknowledged', label: t('purchaseOrders.status_acknowledged') },
  { value: 'partially_received', label: t('purchaseOrders.status_partially_received') },
  { value: 'fully_received', label: t('purchaseOrders.status_fully_received') },
  { value: 'billed', label: t('purchaseOrders.status_billed') },
  { value: 'closed', label: t('purchaseOrders.status_closed') },
  { value: 'cancelled', label: t('purchaseOrders.status_cancelled') },
])

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
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
  return t('purchaseOrders.status_' + status)
}

function dismissHelp() {
  showHelp.value = false
  localStorage.setItem('po_help_dismissed', '1')
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
      message: error.response?.data?.message || t('purchaseOrders.error_loading'),
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

const paginationPages = computed(() => {
  if (!meta.value) return []
  const current = meta.value.current_page
  const last = meta.value.last_page
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1)
  const pages = [1]
  if (current > 3) pages.push('...')
  for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
    pages.push(i)
  }
  if (current < last - 2) pages.push('...')
  pages.push(last)
  return pages
})

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
