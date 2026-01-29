<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.warehouse_inventory')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" />
        <BaseBreadcrumbItem :title="$t('stock.warehouse_inventory')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

      <!-- Filters Card -->
      <BaseCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.filters') }}</h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Warehouse Selector -->
          <BaseInputGroup :label="$t('stock.warehouse')" required>
            <BaseMultiselect
              v-model="filters.warehouse"
              :content-loading="stockStore.isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="stockStore.warehouses"
              object
              :placeholder="$t('stock.select_warehouse_prompt')"
            />
          </BaseInputGroup>

          <!-- As Of Date -->
          <BaseInputGroup :label="$t('stock.as_of_date')">
            <BaseDatePicker
              v-model="filters.as_of_date"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <!-- Search -->
          <BaseInputGroup :label="$t('general.search')">
            <BaseInput
              v-model="filters.search"
              :placeholder="$t('stock.search_items')"
            />
          </BaseInputGroup>
        </div>

        <div class="flex justify-end mt-4 space-x-2">
          <BaseButton variant="secondary" @click="clearFilters">
            {{ $t('general.clear') }}
          </BaseButton>
          <BaseButton variant="primary" :disabled="!filters.warehouse" @click="loadInventory">
            {{ $t('general.apply') }}
          </BaseButton>
        </div>
      </BaseCard>

      <!-- Results -->
      <template v-if="hasData">
        <!-- Warehouse Info & Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Warehouse Info -->
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.warehouse') }}</h3>
            </template>
            <div class="space-y-3">
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('general.name') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.warehouseInventory.warehouse?.name }}</dd>
              </div>
              <div v-if="stockStore.warehouseInventory.warehouse?.code">
                <dt class="text-sm font-medium text-gray-500">{{ $t('general.code') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.warehouseInventory.warehouse.code }}</dd>
              </div>
              <div v-if="stockStore.warehouseInventory.as_of_date">
                <dt class="text-sm font-medium text-gray-500">{{ $t('stock.as_of_date') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.warehouseInventory.as_of_date }}</dd>
              </div>
            </div>
          </BaseCard>

          <!-- Total Quantity -->
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_quantity') }}</h3>
            </template>
            <div class="flex justify-center items-center h-24">
              <span class="text-3xl font-bold text-blue-600">
                {{ formatNumber(stockStore.warehouseInventory.totals.quantity) }}
              </span>
            </div>
          </BaseCard>

          <!-- Total Value -->
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_value') }}</h3>
            </template>
            <div class="flex justify-center items-center h-24">
              <BaseFormatMoney :amount="stockStore.warehouseInventory.totals.value" class="text-3xl font-bold text-green-600" />
            </div>
          </BaseCard>
        </div>

        <!-- Inventory Table -->
        <BaseCard>
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.inventory_items') }}</h3>
              <BaseButton variant="primary-outline" size="sm" @click="exportToCsv">
                <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4 mr-1" />
                {{ $t('general.export') }}
              </BaseButton>
            </div>
          </template>

          <div v-if="stockStore.isLoadingInventory" class="flex justify-center py-8">
            <BaseContentPlaceholders>
              <BaseContentPlaceholdersBox class="w-full h-64" />
            </BaseContentPlaceholders>
          </div>

          <div v-else-if="stockStore.warehouseInventory.items.length === 0" class="text-center py-8">
            <BaseIcon name="ArchiveBoxIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.no_inventory') }}</h3>
            <p class="text-gray-500 mt-2">{{ $t('stock.no_inventory_message') }}</p>
          </div>

          <table v-else class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('items.name') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.sku') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.barcode') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.quantity') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.avg_cost') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.value') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.last_movement') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="item in stockStore.warehouseInventory.items" :key="item.id">
                <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                  {{ item.name }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">
                  {{ item.sku || '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 font-mono">
                  {{ item.barcode || '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-right font-medium" :class="item.quantity < 0 ? 'text-red-600' : 'text-gray-900'">
                  {{ formatNumber(item.quantity) }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <BaseFormatMoney v-if="item.avg_cost" :amount="item.avg_cost" />
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <BaseFormatMoney :amount="item.value" />
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">
                  {{ item.last_movement || '-' }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-900">
                  {{ $t('stock.grand_total') }}
                </td>
                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                  {{ formatNumber(stockStore.warehouseInventory.totals.quantity) }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">
                  -
                </td>
                <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                  <BaseFormatMoney :amount="stockStore.warehouseInventory.totals.value" />
                </td>
                <td class="px-4 py-3"></td>
              </tr>
            </tfoot>
          </table>
        </BaseCard>
      </template>

      <!-- Empty State -->
      <BaseCard v-else-if="!stockStore.isLoadingInventory">
        <div class="text-center py-12">
          <BaseIcon name="BuildingStorefrontIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.select_warehouse_prompt') }}</h3>
          <p class="text-gray-500 mt-2">{{ $t('stock.select_warehouse_prompt_message') }}</p>
        </div>
      </BaseCard>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useStockStore } from '@/scripts/admin/stores/stock'

const route = useRoute()
const stockStore = useStockStore()

const filters = reactive({
  warehouse: null,
  as_of_date: null,
  search: '',
})

const hasData = computed(() => {
  return stockStore.warehouseInventory.warehouse !== null
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 4 })
}

async function loadInventory() {
  if (!filters.warehouse) return

  const params = {}
  if (filters.as_of_date) params.as_of_date = filters.as_of_date
  if (filters.search) params.search = filters.search

  await stockStore.fetchWarehouseInventory(filters.warehouse.id, params)
}

function clearFilters() {
  filters.warehouse = null
  filters.as_of_date = null
  filters.search = ''
  stockStore.resetWarehouseInventory()
}

function exportToCsv() {
  if (!stockStore.warehouseInventory.items.length) return

  const headers = [
    'Item Name',
    'SKU',
    'Barcode',
    'Quantity',
    'Avg Cost',
    'Value',
    'Last Movement',
  ]

  const rows = stockStore.warehouseInventory.items.map((item) => [
    item.name,
    item.sku || '',
    item.barcode || '',
    item.quantity,
    item.avg_cost || '',
    item.value,
    item.last_movement || '',
  ])

  const csvContent = [
    headers.join(','),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `warehouse_inventory_${stockStore.warehouseInventory.warehouse?.name || 'export'}_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

onMounted(async () => {
  // Stock module is always enabled - load warehouses
  await stockStore.fetchWarehouses()

  // Check for warehouse_id in query params and auto-load
  const warehouseId = route.query.warehouse_id
  if (warehouseId) {
    const warehouse = stockStore.warehouses.find(w => w.id === parseInt(warehouseId))
    if (warehouse) {
      filters.warehouse = warehouse
      await loadInventory()
    }
  }
})
</script>
// CLAUDE-CHECKPOINT
