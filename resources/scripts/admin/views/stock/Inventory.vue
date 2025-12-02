<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex space-x-2">
          <!-- Bulk Actions Dropdown -->
          <BaseDropdown v-if="selectedItems.length > 0">
            <template #activator>
              <BaseButton variant="white" class="text-gray-600 border-gray-300">
                {{ $t('general.actions') }} ({{ selectedItems.length }})
                <BaseIcon name="ChevronDownIcon" class="h-4 w-4 ml-2" />
              </BaseButton>
            </template>
            <BaseDropdownItem @click="bulkDelete">
              <BaseIcon name="TrashIcon" class="h-4 w-4 mr-2 text-red-500" />
              {{ $t('general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>

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
        </div>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

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

        <!-- Item Filter (Dropdown) -->
        <BaseInputGroup :label="$t('stock.item')" class="text-left">
          <BaseMultiselect
            v-model="filters.item_id"
            :content-loading="isLoadingItems"
            :filterResults="false"
            resolve-on-load
            :delay="500"
            searchable
            :options="searchItems"
            value-prop="id"
            track-by="name"
            label="name"
            object
            :placeholder="$t('stock.select_item')"
          >
             <template #singlelabel="{ value }">
                <div class="multiselect-single-label">
                  <span>{{ value.name }}</span>
                  <span v-if="value.sku" class="text-gray-500 text-xs ml-2">({{ value.sku }})</span>
                </div>
              </template>
              <template #option="{ option }">
                <div class="flex justify-between items-center w-full">
                  <span>{{ option.name }}</span>
                  <span v-if="option.sku" class="text-gray-500 text-xs">({{ option.sku }})</span>
                </div>
              </template>
          </BaseMultiselect>
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
          <!-- Select All Header -->
          <template #header-select>
             <BaseCheckbox
                v-model="selectAll"
                @change="toggleSelectAll"
             />
          </template>

          <!-- Checkbox Cell -->
          <template #cell-select="{ row }">
             <BaseCheckbox
                :model-value="selectedItems.includes(row.data.item_id)"
                @change="toggleSelect(row.data.item_id)"
             />
          </template>

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
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debouncedWatch } from '@vueuse/core'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()

const stockStore = useStockStore()
const companyStore = useCompanyStore()
const itemStore = useItemStore()

const showFilters = ref(false)
const table = ref(null)
const isRequestOngoing = ref(true)
const inventoryData = ref([])
const selectedItems = ref([])
const selectAll = ref(false)
const isLoadingItems = ref(false)

const filters = reactive({
  warehouse_id: '',
  item_id: null,
  category: '',
})

const showEmptyScreen = computed(
  () => inventoryData.value.length === 0 && !isRequestOngoing.value && !filters.item_id && !filters.warehouse_id && !filters.category
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
        key: 'select',
        label: '',
        thClass: 'w-10 text-center',
        tdClass: 'text-center',
        sortable: false
    },
    {
      key: 'name',
      label: t('items.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
      sortable: true
    },
    { key: 'sku', label: t('stock.sku'), sortable: true },
    { key: 'warehouse', label: t('stock.warehouse'), sortable: false },
    { key: 'quantity', label: t('stock.quantity'), sortable: true },
    { key: 'unit_cost', label: t('stock.unit_cost'), sortable: true },
    {
      key: 'total_value',
      label: t('stock.total_value'),
      tdClass: 'font-medium text-gray-900',
      sortable: false
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
  filters.item_id = null
  filters.category = ''
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  const data = {
    warehouse_id: filters.warehouse_id || '',
    item_id: filters.item_id?.id || '',
    category: filters.category || '',
    orderByField: sort.fieldName || 'name',
    orderBy: sort.order || 'asc',
    page,
  }

  isRequestOngoing.value = true
  selectedItems.value = [] // Clear selection on refresh
  selectAll.value = false

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
        limit: 15,
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
        limit: 15,
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
    'Total Value'
  ]

  const rows = inventoryData.value.map(item => [
    item.name,
    item.sku || '',
    item.warehouse_name || 'All Warehouses',
    item.quantity,
    item.unit_cost,
    item.total_value
  ])

  let csvContent = "data:text/csv;charset=utf-8,"
    + headers.join(",") + "\n"
    + rows.map(e => e.join(",")).join("\n")

  const encodedUri = encodeURI(csvContent)
  const link = document.createElement("a")
  link.setAttribute("href", encodedUri)
  link.setAttribute("download", "inventory_report.csv")
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}

function toggleSelect(itemId) {
    if (selectedItems.value.includes(itemId)) {
        selectedItems.value = selectedItems.value.filter(id => id !== itemId)
    } else {
        selectedItems.value.push(itemId)
    }
}

function toggleSelectAll() {
    if (selectAll.value) {
        selectedItems.value = inventoryData.value.map(item => item.item_id)
    } else {
        selectedItems.value = []
    }
}



function bulkDelete() {
  if (!confirm(t('general.are_you_sure'))) return

  isRequestOngoing.value = true
  
  axios.post('/items/delete', { ids: selectedItems.value })
    .then((response) => {
      if (response.data.success) {
        // Remove deleted items from local list
        inventoryData.value = inventoryData.value.filter(item => !selectedItems.value.includes(item.item_id))
        
        // Update total count in pagination if possible, or just refresh
        refreshTable()
        
        const notificationStore = useNotificationStore()
        notificationStore.showNotification({
          type: 'success',
          message: t('items.deleted_message', 2)
        })
        
        selectedItems.value = []
        selectAll.value = false
      }
    })
    .catch((error) => {
      console.error(error)
      const notificationStore = useNotificationStore()
      notificationStore.showNotification({
        type: 'error',
        message: t('general.error_occurred')
      })
    })
    .finally(() => {
      isRequestOngoing.value = false
    })
}

async function searchItems(query) {
  if (!query) return []
  isLoadingItems.value = true
  try {
    const response = await itemStore.fetchItems({
      search: query,
      limit: 10
    })
    return response.data.data
  } catch (error) {
    console.error(error)
    return []
  } finally {
    isLoadingItems.value = false
  }
}
</script>
