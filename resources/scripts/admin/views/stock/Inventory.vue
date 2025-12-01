<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.inventory')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" />
        <BaseBreadcrumbItem :title="$t('stock.inventory')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="hasInventoryData"
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

    <!-- Stock Module Disabled Warning -->
    <BaseCard v-if="!stockStore.stockEnabled" class="mb-6">
      <div class="text-center py-8">
        <BaseIcon name="ExclamationTriangleIcon" class="h-12 w-12 text-yellow-500 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.module_disabled') }}</h3>
        <p class="text-gray-500 mt-2">{{ $t('stock.module_disabled_message') }}</p>
      </div>
    </BaseCard>

    <template v-else>
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
            :placeholder="$t('stock.select_warehouse')"
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

        <BaseInputGroup :label="$t('items.category')" class="text-left">
          <BaseInput
            v-model="filters.category"
            type="text"
            name="category"
            :placeholder="$t('items.enter_category')"
            autocomplete="off"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <!-- Summary Cards -->
      <div v-if="hasInventoryData" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <BaseCard>
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_items') }}</h3>
          </template>
          <div class="flex justify-center items-center h-20">
            <span class="text-3xl font-bold text-blue-600">
              {{ inventoryData.length }}
            </span>
          </div>
        </BaseCard>

        <BaseCard>
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_quantity') }}</h3>
          </template>
          <div class="flex justify-center items-center h-20">
            <span class="text-3xl font-bold text-green-600">
              {{ formatNumber(totalQuantity) }}
            </span>
          </div>
        </BaseCard>

        <BaseCard>
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_value') }}</h3>
          </template>
          <div class="flex justify-center items-center h-20">
            <BaseFormatMoney :amount="totalValue" class="text-3xl font-bold text-purple-600" />
          </div>
        </BaseCard>
      </div>

      <!-- Empty State -->
      <BaseEmptyPlaceholder
        v-show="showEmptyScreen"
        :title="$t('stock.no_inventory')"
        :description="$t('stock.no_inventory_message')"
      >
        <BaseIcon name="ArchiveBoxIcon" class="h-16 w-16 text-gray-400 mt-5 mb-4" />
      </BaseEmptyPlaceholder>

      <!-- Inventory Table -->
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
          :columns="inventoryColumns"
          :placeholder-count="10"
          class="mt-3"
        >
          <template #cell-name="{ row }">
            <router-link
              :to="{ path: `/admin/stock/item-card/${row.data.item_id}` }"
              class="font-medium text-primary-500"
            >
              <BaseText :text="row.data.name" />
            </router-link>
          </template>

          <template #cell-sku="{ row }">
            <span class="text-gray-600">
              {{ row.data.sku || '-' }}
            </span>
          </template>

          <template #cell-warehouse="{ row }">
            <span class="text-gray-900">
              {{ row.data.warehouse_name || '-' }}
            </span>
          </template>

          <template #cell-quantity="{ row }">
            <div class="flex items-center">
              <span
                class="font-medium"
                :class="getQuantityClass(row.data)"
              >
                {{ formatNumber(row.data.quantity) }}
              </span>
              <BaseBadge
                v-if="isLowStock(row.data)"
                variant="danger"
                class="ml-2 px-2 py-0.5"
              >
                {{ $t('stock.low_stock') }}
              </BaseBadge>
            </div>
          </template>

          <template #cell-unit_cost="{ row }">
            <BaseFormatMoney
              v-if="row.data.unit_cost"
              :amount="row.data.unit_cost"
              :currency="companyStore.selectedCompanyCurrency"
            />
            <span v-else class="text-gray-400">-</span>
          </template>

          <template #cell-total_value="{ row }">
            <BaseFormatMoney
              :amount="row.data.total_value"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>
        </BaseTable>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { debouncedWatch } from '@vueuse/core'

const { t } = useI18n()

const stockStore = useStockStore()
const companyStore = useCompanyStore()
const globalStore = useGlobalStore()

const showFilters = ref(false)
const table = ref(null)
const isRequestOngoing = ref(true)
const inventoryData = ref([])

const filters = reactive({
  warehouse_id: '',
  search: '',
  category: '',
})

const showEmptyScreen = computed(
  () => inventoryData.value.length === 0 && !isRequestOngoing.value
)

const hasInventoryData = computed(
  () => inventoryData.value.length > 0
)

const totalQuantity = computed(() => {
  return inventoryData.value.reduce((sum, item) => sum + (item.quantity || 0), 0)
})

const totalValue = computed(() => {
  return inventoryData.value.reduce((sum, item) => sum + (item.total_value || 0), 0)
})

const inventoryColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('items.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    { key: 'sku', label: t('stock.sku') },
    { key: 'warehouse', label: t('stock.warehouse') },
    { key: 'quantity', label: t('stock.quantity') },
    { key: 'unit_cost', label: t('stock.unit_cost') },
    {
      key: 'total_value',
      label: t('stock.total_value'),
      tdClass: 'font-medium text-gray-900',
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
  // Check if stock is enabled via feature flags from bootstrap
  const featureFlags = globalStore.featureFlags || {}
  const stockEnabled = featureFlags?.stock?.enabled || featureFlags?.stock || false
  stockStore.setStockEnabled(stockEnabled)

  // Load warehouses and initial inventory data
  if (stockStore.stockEnabled) {
    await stockStore.fetchWarehouses()
    await refreshTable()
  }
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 4 })
}

function getQuantityClass(item) {
  if (item.quantity < 0) return 'text-red-600'
  if (isLowStock(item)) return 'text-orange-600'
  return 'text-gray-900'
}

function isLowStock(item) {
  if (!item.minimum_quantity) return false
  return item.quantity < item.minimum_quantity
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
  filters.category = ''
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  const data = {
    warehouse_id: filters.warehouse_id || '',
    search: filters.search || '',
    category: filters.category || '',
    orderByField: sort.fieldName || 'name',
    orderBy: sort.order || 'asc',
    page,
  }

  isRequestOngoing.value = true

  try {
    const response = await stockStore.fetchInventoryList(data)

    // Store data for calculations
    inventoryData.value = response.data.data || []

    isRequestOngoing.value = false

    return {
      data: inventoryData.value,
      pagination: {
        totalPages: response.data.meta?.last_page || 1,
        currentPage: page,
        totalCount: response.data.meta?.total || inventoryData.value.length,
        limit: 10,
      },
    }
  } catch (error) {
    console.error('Error fetching inventory:', error)
    inventoryData.value = []
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

function exportToCsv() {
  if (!inventoryData.value.length) return

  const headers = [
    'Item Name',
    'SKU',
    'Warehouse',
    'Quantity',
    'Unit Cost',
    'Total Value',
  ]

  const rows = inventoryData.value.map((item) => [
    item.name,
    item.sku || '',
    item.warehouse_name || '',
    item.quantity,
    item.unit_cost || '',
    item.total_value,
  ])

  const csvContent = [
    headers.join(','),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `inventory_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}
</script>
// CLAUDE-CHECKPOINT
