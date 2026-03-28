<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="lowStockStore.totalLowStockItems"
          variant="primary-outline"
          @click="exportToCsv"
        >
          <template #left="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.export') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

      <!-- Summary Bar -->
      <div v-if="lowStockStore.totalLowStockItems > 0" class="mb-4">
        <div class="flex items-center gap-6 px-4 py-3 bg-white border border-gray-200 rounded-lg shadow-sm">
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
            <span class="text-sm font-medium text-gray-700">
              {{ forecastSummary.critical }} {{ $t('stock.summary_critical') }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-yellow-500 inline-block"></span>
            <span class="text-sm font-medium text-gray-700">
              {{ forecastSummary.low }} {{ $t('stock.summary_low') }}
            </span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
            <span class="text-sm font-medium text-gray-700">
              {{ forecastSummary.ok }} {{ $t('stock.summary_ok') }}
            </span>
          </div>
          <div class="ml-auto">
            <BaseButton
              v-if="!stockStore.aiAnalysisLoading"
              variant="primary-outline"
              size="sm"
              @click="runAIAnalysis"
            >
              <template #left="slotProps">
                <BaseIcon name="SparklesIcon" :class="slotProps.class" />
              </template>
              {{ $t('stock.ai_analyze') }}
            </BaseButton>
            <BaseButton
              v-else
              variant="primary-outline"
              size="sm"
              :disabled="true"
            >
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-500 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
              </svg>
              {{ $t('stock.ai_analyzing') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Summary Card -->
      <div v-if="lowStockStore.totalLowStockItems > 0" class="mb-6">
        <BaseCard>
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <BaseIcon name="ExclamationTriangleIcon" class="h-10 w-10 text-orange-500" />
            </div>
            <div class="ml-4">
              <h3 class="text-lg font-medium text-gray-900">
                {{ $t('stock.low_stock_warning') }}
              </h3>
              <p class="text-sm text-gray-500 mt-1">
                {{ $t('stock.low_stock_warning_message', { count: lowStockStore.totalLowStockItems }) }}
              </p>
            </div>
          </div>
        </BaseCard>
      </div>

      <!-- Filters -->
      <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
        <BaseInputGroup :label="$t('stock.warehouse')" class="text-left">
          <BaseMultiselect
            v-model="filters.warehouse_id"
            :content-loading="stockStore.isLoadingWarehouses"
            value-prop="id"
            track-by="name"
            label="name"
            :options="stockStore.warehouses"
            :placeholder="$t('stock.all_warehouses')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('general.search')" class="text-left">
          <BaseInput
            v-model="filters.search"
            type="text"
            name="search"
            :placeholder="$t('stock.search_items')"
            autocomplete="off"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('stock.severity')" class="text-left">
          <BaseMultiselect
            v-model="filters.severity"
            :options="severityOptions"
            :placeholder="$t('stock.all_severities')"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <!-- Empty State -->
      <BaseEmptyPlaceholder
        v-show="showEmptyScreen"
        :title="$t('stock.no_low_stock')"
        :description="$t('stock.no_low_stock_message')"
      >
        <BaseIcon name="CheckCircleIcon" class="h-16 w-16 text-green-400 mt-5 mb-4" />
      </BaseEmptyPlaceholder>

      <!-- Low Stock Table -->
      <div v-show="!showEmptyScreen" class="relative table-container">
        <div class="relative flex items-center justify-end h-10 border-gray-200 border-solid">
          <BaseButton
            variant="primary-outline"
            size="sm"
            @click="toggleFilter"
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
        </div>

        <BaseTable
          ref="table"
          :data="fetchData"
          :columns="lowStockColumns"
          :placeholder-count="lowStockStore.totalLowStockItems >= 20 ? 10 : 5"
          class="mt-3"
        >
          <template #cell-name="{ row }">
            <router-link
              :to="{ path: `/admin/stock/item-card/${row.data.item_id}` }"
              class="font-medium text-primary-500"
            >
              <BaseText :text="row.data.item_name" />
            </router-link>
          </template>

          <template #cell-sku="{ row }">
            <span class="text-gray-600">
              {{ row.data.item_sku || '-' }}
            </span>
          </template>

          <template #cell-warehouse="{ row }">
            <span class="text-gray-900">
              {{ row.data.warehouse_name || '-' }}
            </span>
          </template>

          <template #cell-current_quantity="{ row }">
            <span class="font-medium text-orange-600">
              {{ formatNumber(row.data.current_quantity) }}
            </span>
          </template>

          <template #cell-minimum_quantity="{ row }">
            <span class="text-gray-900">
              {{ formatNumber(row.data.minimum_quantity) }}
            </span>
          </template>

          <template #cell-daily_consumption="{ row }">
            <span class="text-gray-700">
              {{ getForecastField(row.data.item_id, 'avg_daily_consumption', '-') }}
              <span v-if="getForecastField(row.data.item_id, 'avg_daily_consumption')" class="text-xs text-gray-400">
                {{ $t('stock.qty_per_day') }}
              </span>
            </span>
          </template>

          <template #cell-days_of_stock="{ row }">
            <span :class="getDaysOfStockClass(getForecastField(row.data.item_id, 'days_of_stock'))">
              {{ formatDaysOfStock(getForecastField(row.data.item_id, 'days_of_stock')) }}
              <span v-if="getForecastField(row.data.item_id, 'days_of_stock') !== null && getForecastField(row.data.item_id, 'days_of_stock') < 999" class="text-xs">
                {{ $t('stock.days_short') }}
              </span>
            </span>
          </template>

          <template #cell-predicted_stockout="{ row }">
            <span class="text-gray-700">
              {{ formatStockoutDate(getForecastField(row.data.item_id, 'predicted_stockout_date')) }}
            </span>
          </template>

          <template #cell-trend="{ row }">
            <span :class="getTrendClass(getForecastField(row.data.item_id, 'trend'))">
              {{ getTrendIcon(getForecastField(row.data.item_id, 'trend')) }}
            </span>
          </template>

          <template #cell-shortage="{ row }">
            <BaseBadge
              :variant="getSeverityVariant(row.data.shortage_percentage)"
              class="px-3 py-1"
            >
              {{ formatNumber(row.data.shortage) }}
              <span v-if="row.data.shortage_percentage !== null">
                ({{ Math.abs(row.data.shortage_percentage) }}%)
              </span>
            </BaseBadge>
          </template>

          <template #cell-actions="{ row }">
            <BaseDropdown>
              <template #activator>
                <BaseButton variant="primary-outline" size="sm">
                  {{ $t('general.actions') }}
                  <template #right="slotProps">
                    <BaseIcon name="ChevronDownIcon" :class="slotProps.class" />
                  </template>
                </BaseButton>
              </template>
              <BaseDropdownItem @click="viewItemCard(row.data.item_id)">
                <BaseIcon name="DocumentTextIcon" class="mr-3 text-gray-600" />
                {{ $t('stock.view_item_card') }}
              </BaseDropdownItem>
              <BaseDropdownItem @click="createPurchaseOrder(row.data)">
                <BaseIcon name="ShoppingCartIcon" class="mr-3 text-gray-600" />
                {{ $t('stock.reorder') }}
              </BaseDropdownItem>
            </BaseDropdown>
          </template>
        </BaseTable>
      </div>

      <!-- AI Analysis Panel -->
      <div v-if="stockStore.demandAIAnalysis" class="mt-6">
        <div class="border border-gray-200 rounded-lg overflow-hidden">
          <button
            class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors"
            @click="showAIPanel = !showAIPanel"
          >
            <div class="flex items-center gap-2">
              <BaseIcon name="SparklesIcon" class="h-5 w-5 text-primary-500" />
              <span class="font-medium text-gray-900">{{ $t('stock.ai_analyze') }}</span>
              <span v-if="aiAnalysisItems.length" class="text-sm text-gray-500">
                ({{ aiAnalysisItems.length }} {{ $t('stock.summary_critical') }})
              </span>
            </div>
            <BaseIcon
              :name="showAIPanel ? 'ChevronUpIcon' : 'ChevronDownIcon'"
              class="h-5 w-5 text-gray-400"
            />
          </button>

          <div v-show="showAIPanel" class="p-4">
            <!-- AI unavailable message -->
            <div
              v-if="stockStore.demandAIAnalysis.status === 'ai_unavailable'"
              class="text-center py-4 text-gray-500"
            >
              {{ $t('stock.ai_unavailable') }}
            </div>

            <!-- No critical items -->
            <div
              v-else-if="stockStore.demandAIAnalysis.status === 'no_critical_items'"
              class="text-center py-4 text-green-600"
            >
              {{ $t('stock.ai_no_critical') }}
            </div>

            <!-- AI Analysis Cards -->
            <div v-else-if="aiAnalysisItems.length" class="grid gap-4 md:grid-cols-2">
              <div
                v-for="item in aiAnalysisItems"
                :key="item.item_id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="flex items-center justify-between mb-3">
                  <h4 class="font-medium text-gray-900">
                    {{ getItemNameById(item.item_id) }}
                  </h4>
                  <span
                    :class="getUrgencyBadgeClass(item.urgency)"
                    class="px-2 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ getUrgencyLabel(item.urgency) }}
                  </span>
                </div>

                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-500">{{ $t('stock.reorder_qty') }}:</span>
                    <span class="font-medium text-gray-900">{{ formatNumber(item.reorder_qty) }}</span>
                  </div>
                  <div v-if="item.risk">
                    <span class="text-gray-500">{{ $t('stock.ai_risk_assessment') }}:</span>
                    <p class="text-gray-700 mt-0.5">{{ item.risk }}</p>
                  </div>
                  <div v-if="item.notes">
                    <span class="text-gray-500">{{ $t('stock.ai_notes') }}:</span>
                    <p class="text-gray-600 mt-0.5 italic">{{ item.notes }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debouncedWatch } from '@vueuse/core'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()
const router = useRouter()

const stockStore = useStockStore()
const notificationStore = useNotificationStore()

// Create a pseudo-store for low stock items
const lowStockStore = reactive({
  totalLowStockItems: 0,
  selectedItems: [],
})

const showFilters = ref(false)
const showAIPanel = ref(true)
const table = ref(null)
const isRequestOngoing = ref(true)

const filters = reactive({
  warehouse_id: '',
  search: '',
  severity: '',
})

const severityOptions = computed(() => [
  { label: t('stock.all_severities'), value: '' },
  { label: t('stock.critical'), value: 'critical' },
  { label: t('stock.warning'), value: 'warning' },
  { label: t('stock.moderate'), value: 'moderate' },
])

const showEmptyScreen = computed(
  () => lowStockStore.totalLowStockItems === 0 && !isRequestOngoing.value
)

// Forecast summary computed from forecast data
const forecastSummary = computed(() => {
  const forecasts = stockStore.demandForecasts || []
  let critical = 0
  let low = 0
  let ok = 0

  for (const f of forecasts) {
    if (f.days_of_stock < 7) {
      critical++
    } else if (f.days_of_stock < 14) {
      low++
    } else {
      ok++
    }
  }

  return { critical, low, ok }
})

// AI analysis items (from Gemini response)
const aiAnalysisItems = computed(() => {
  const analysis = stockStore.demandAIAnalysis
  if (!analysis || !analysis.items) return []
  return analysis.items
})

const lowStockColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('items.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'sku', label: t('stock.sku') },
    {
      key: 'current_quantity',
      label: t('stock.current_qty'),
      tdClass: 'text-gray-900',
    },
    {
      key: 'minimum_quantity',
      label: t('stock.minimum_qty'),
      tdClass: 'text-gray-900',
    },
    {
      key: 'daily_consumption',
      label: t('stock.daily_consumption'),
      sortable: false,
    },
    {
      key: 'days_of_stock',
      label: t('stock.days_of_stock'),
      sortable: false,
    },
    {
      key: 'predicted_stockout',
      label: t('stock.predicted_stockout'),
      sortable: false,
    },
    {
      key: 'trend',
      label: t('stock.trend'),
      sortable: false,
      thClass: 'text-center',
      tdClass: 'text-center',
    },
    {
      key: 'shortage',
      label: t('stock.shortage'),
      tdClass: 'font-medium',
    },
    {
      key: 'actions',
      label: t('general.actions'),
      thClass: 'text-right',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

debouncedWatch(
  filters,
  () => {
    refreshTable()
  },
  { debounce: 500 }
)

onMounted(async () => {
  await stockStore.fetchWarehouses()
  await refreshTable()
  // Load demand forecast data
  try {
    await stockStore.fetchDemandForecast()
  } catch (e) {
    // Non-critical — forecast is optional
    console.warn('Failed to load demand forecast:', e)
  }
})

// Forecast helper functions
function getForecastField(itemId, field, fallback = null) {
  const forecasts = stockStore.demandForecasts || []
  const forecast = forecasts.find((f) => f.item_id === itemId)
  if (!forecast) return fallback
  const value = forecast[field]
  if (value === null || value === undefined) return fallback
  return value
}

function getDaysOfStockClass(days) {
  if (days === null || days === undefined) return 'text-gray-400'
  if (days < 7) return 'font-bold text-red-600'
  if (days < 14) return 'font-medium text-yellow-600'
  return 'text-green-600'
}

function formatDaysOfStock(days) {
  if (days === null || days === undefined) return '-'
  if (days >= 999) return '99+'
  return days
}

function formatStockoutDate(dateStr) {
  if (!dateStr) return '-'
  const parts = dateStr.split('-')
  if (parts.length !== 3) return dateStr
  return `${parts[2]}.${parts[1]}.${parts[0]}`
}

function getTrendIcon(trend) {
  if (trend === 'increasing') return '\u2191'
  if (trend === 'decreasing') return '\u2193'
  return '\u2192'
}

function getTrendClass(trend) {
  if (trend === 'increasing') return 'text-lg font-bold text-red-500'
  if (trend === 'decreasing') return 'text-lg font-bold text-green-500'
  return 'text-lg text-gray-400'
}

function getItemNameById(itemId) {
  const forecasts = stockStore.demandForecasts || []
  const forecast = forecasts.find((f) => f.item_id === itemId)
  return forecast?.item_name || `#${itemId}`
}

function getUrgencyBadgeClass(urgency) {
  if (urgency === 'critical') return 'bg-red-100 text-red-800'
  if (urgency === 'high') return 'bg-orange-100 text-orange-800'
  return 'bg-yellow-100 text-yellow-800'
}

function getUrgencyLabel(urgency) {
  if (urgency === 'critical') return t('stock.ai_urgency_critical')
  if (urgency === 'high') return t('stock.ai_urgency_high')
  return t('stock.ai_urgency_medium')
}

async function runAIAnalysis() {
  try {
    await stockStore.analyzeDemandAI()
    showAIPanel.value = true
  } catch (e) {
    notificationStore.showNotification({
      type: 'error',
      message: t('stock.ai_unavailable'),
    })
  }
}

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('mk-MK', { maximumFractionDigits: 2 })
}

function getSeverityVariant(percentage) {
  if (percentage === null || percentage === undefined) return 'warning'
  const absPercentage = Math.abs(percentage)
  if (absPercentage >= 75) return 'danger'
  if (absPercentage >= 50) return 'warning'
  return 'primary'
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }
  showFilters.value = !showFilters.value
}

function clearFilter() {
  filters.warehouse_id = ''
  filters.search = ''
  filters.severity = ''
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  const data = {
    warehouse_id: filters.warehouse_id || '',
    search: filters.search || '',
    severity: filters.severity || '',
    orderByField: sort.fieldName || 'shortage_percentage',
    orderBy: sort.order || 'desc',
    page,
  }

  isRequestOngoing.value = true

  try {
    const response = await stockStore.fetchLowStock(data)

    lowStockStore.totalLowStockItems = response.data.meta?.total || 0

    isRequestOngoing.value = false

    return {
      data: response.data.data || [],
      pagination: {
        totalPages: response.data.meta?.last_page || 1,
        currentPage: page,
        totalCount: response.data.meta?.total || 0,
        limit: 10,
      },
    }
  } catch (error) {
    console.error('Error fetching low stock items:', error)
    lowStockStore.totalLowStockItems = 0
    isRequestOngoing.value = false

    return {
      data: [],
      pagination: {
        totalPages: 1,
        currentPage: 1,
        totalCount: 0,
        limit: 10,
      },
    }
  }
}

function viewItemCard(itemId) {
  router.push({ path: `/admin/stock/item-card/${itemId}` })
}

function createPurchaseOrder(item) {
  router.push({
    path: '/admin/bills/create',
    query: {
      prefill_item_id: item.item_id,
      prefill_item_name: item.item_name,
      prefill_quantity: item.shortage > 0 ? item.shortage : 1,
    },
  })
}

function exportToCsv() {
  if (!lowStockStore.totalLowStockItems) return

  const tableData = table.value?.data || []

  const headers = [
    t('items.name'),
    t('items.sku'),
    t('stock.warehouse'),
    t('stock.current_qty'),
    t('stock.minimum_qty'),
    t('stock.shortage'),
    t('stock.shortage') + ' %',
  ]

  const rows = tableData.map((item) => [
    item.name,
    item.sku || '',
    item.warehouse_name || '',
    item.current_quantity,
    item.minimum_quantity,
    item.shortage,
    item.shortage_percentage !== null ? `${Math.abs(item.shortage_percentage)}%` : '',
  ])

  const csvContent = [
    headers.join(','),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `low_stock_alerts_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}
</script>

<!-- CLAUDE-CHECKPOINT -->
