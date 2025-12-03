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

const lowStockColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('items.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'sku', label: t('stock.sku') },
    { key: 'warehouse', label: t('stock.warehouse') },
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
  // Stock module is always enabled - load data
  await stockStore.fetchWarehouses()
  await refreshTable()
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 2 })
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
  // Navigate to create bill with item pre-selected
  // Pass item info via query params so the bill form can pre-fill
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

  // We need to get the current table data
  const tableData = table.value?.data || []

  const headers = [
    'Item Name',
    'SKU',
    'Warehouse',
    'Current Qty',
    'Minimum Qty',
    'Shortage',
    'Shortage %',
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
// CLAUDE-CHECKPOINT
