<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.inventory_valuation')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" />
        <BaseBreadcrumbItem :title="$t('stock.inventory_valuation')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

      <!-- Filters Card -->
      <BaseCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.filters') }}</h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- As Of Date -->
          <BaseInputGroup :label="$t('stock.as_of_date')">
            <BaseDatePicker
              v-model="filters.as_of_date"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <!-- Warehouse Filter (optional) -->
          <BaseInputGroup :label="$t('stock.warehouse')">
            <BaseMultiselect
              v-model="filters.warehouse"
              :content-loading="stockStore.isLoadingWarehouses"
              value-prop="id"
              track-by="name"
              label="name"
              :options="warehouseOptions"
              object
              :placeholder="$t('stock.all_warehouses')"
            />
          </BaseInputGroup>

          <!-- Group By -->
          <BaseInputGroup :label="$t('stock.group_by')">
            <BaseMultiselect
              v-model="filters.group_by"
              :options="groupByOptions"
              value-prop="value"
              label="label"
              object
            />
          </BaseInputGroup>
        </div>

        <div class="flex justify-end mt-4 space-x-2">
          <BaseButton variant="secondary" @click="clearFilters">
            {{ $t('general.clear') }}
          </BaseButton>
          <BaseButton variant="primary" @click="loadValuation">
            {{ $t('general.apply') }}
          </BaseButton>
        </div>
      </BaseCard>

      <!-- Loading State -->
      <div v-if="stockStore.isLoadingValuation" class="flex justify-center py-8">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox class="w-full h-64" />
        </BaseContentPlaceholders>
      </div>

      <!-- Results -->
      <template v-else-if="hasData">
        <!-- Grand Total Summary -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_quantity') }}</h3>
            </template>
            <div class="flex justify-center items-center h-24">
              <span class="text-3xl font-bold text-blue-600">
                {{ formatNumber(stockStore.inventoryValuation.grand_total.quantity) }}
              </span>
            </div>
          </BaseCard>

          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.total_value') }}</h3>
            </template>
            <div class="flex justify-center items-center h-24">
              <BaseFormatMoney :amount="stockStore.inventoryValuation.grand_total.value" class="text-3xl font-bold text-green-600" />
            </div>
          </BaseCard>
        </div>

        <!-- Grouped by Warehouse -->
        <template v-if="stockStore.inventoryValuation.group_by === 'warehouse'">
          <BaseCard v-for="warehouse in stockStore.inventoryValuation.warehouses" :key="warehouse.id" class="mb-6">
            <template #header>
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">{{ warehouse.name }}</h3>
                <div class="flex items-center space-x-4">
                  <span class="text-sm text-gray-500">
                    {{ $t('stock.quantity') }}: <span class="font-medium text-gray-900">{{ formatNumber(warehouse.totals.quantity) }}</span>
                  </span>
                  <span class="text-sm text-gray-500">
                    {{ $t('stock.value') }}: <BaseFormatMoney :amount="warehouse.totals.value" class="font-medium" />
                  </span>
                  <BaseButton variant="primary-outline" size="sm" @click="exportWarehouseToCsv(warehouse)">
                    <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4" />
                  </BaseButton>
                </div>
              </div>
            </template>

            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ $t('items.name') }}
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ $t('stock.sku') }}
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
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in warehouse.items" :key="item.id">
                  <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                    {{ item.name }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">
                    {{ item.sku || '-' }}
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
                </tr>
              </tbody>
            </table>
          </BaseCard>
        </template>

        <!-- Grouped by Item -->
        <template v-else>
          <BaseCard>
            <template #header>
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.inventory_items') }}</h3>
                <BaseButton variant="primary-outline" size="sm" @click="exportAllToCsv">
                  <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4 mr-1" />
                  {{ $t('general.export') }}
                </BaseButton>
              </div>
            </template>

            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ $t('items.name') }}
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {{ $t('stock.sku') }}
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
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in stockStore.inventoryValuation.items" :key="item.id">
                  <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                    {{ item.name }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">
                    {{ item.sku || '-' }}
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
                </tr>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr>
                  <td colspan="2" class="px-4 py-3 text-sm font-medium text-gray-900">
                    {{ $t('stock.grand_total') }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                    {{ formatNumber(stockStore.inventoryValuation.grand_total.quantity) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-500">
                    -
                  </td>
                  <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                    <BaseFormatMoney :amount="stockStore.inventoryValuation.grand_total.value" />
                  </td>
                </tr>
              </tfoot>
            </table>
          </BaseCard>
        </template>
      </template>

      <!-- Empty State -->
      <BaseCard v-else>
        <div class="text-center py-12">
          <BaseIcon name="ChartBarIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.no_valuation_data') }}</h3>
          <p class="text-gray-500 mt-2">{{ $t('stock.no_valuation_message') }}</p>
        </div>
      </BaseCard>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useStockStore } from '@/scripts/admin/stores/stock'

const { t } = useI18n()
const stockStore = useStockStore()

const filters = reactive({
  as_of_date: null,
  warehouse: null,
  group_by: { value: 'warehouse', label: t('stock.group_by_warehouse') },
})

const warehouseOptions = computed(() => {
  return [
    { id: null, name: t('stock.all_warehouses') },
    ...stockStore.warehouses,
  ]
})

const groupByOptions = computed(() => [
  { value: 'warehouse', label: t('stock.group_by_warehouse') },
  { value: 'item', label: t('stock.group_by_item') },
])

const hasData = computed(() => {
  if (stockStore.inventoryValuation.group_by === 'warehouse') {
    return stockStore.inventoryValuation.warehouses.length > 0
  }
  return stockStore.inventoryValuation.items.length > 0
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 4 })
}

async function loadValuation() {
  const params = {}
  if (filters.as_of_date) params.as_of_date = filters.as_of_date
  if (filters.warehouse?.id) params.warehouse_id = filters.warehouse.id
  if (filters.group_by) params.group_by = filters.group_by.value

  await stockStore.fetchInventoryValuation(params)
}

function clearFilters() {
  filters.as_of_date = null
  filters.warehouse = null
  filters.group_by = { value: 'warehouse', label: t('stock.group_by_warehouse') }
  stockStore.resetInventoryValuation()
}

function exportWarehouseToCsv(warehouse) {
  const headers = ['Item Name', 'SKU', 'Quantity', 'Avg Cost', 'Value']

  const rows = warehouse.items.map((item) => [
    item.name,
    item.sku || '',
    item.quantity,
    item.avg_cost || '',
    item.value,
  ])

  const csvContent = [
    headers.join(','),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `inventory_valuation_${warehouse.name}_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

function exportAllToCsv() {
  const headers = ['Item Name', 'SKU', 'Quantity', 'Avg Cost', 'Value']
  let rows = []

  if (stockStore.inventoryValuation.group_by === 'warehouse') {
    for (const warehouse of stockStore.inventoryValuation.warehouses) {
      rows.push([`--- ${warehouse.name} ---`, '', '', '', ''])
      for (const item of warehouse.items) {
        rows.push([
          item.name,
          item.sku || '',
          item.quantity,
          item.avg_cost || '',
          item.value,
        ])
      }
    }
  } else {
    rows = stockStore.inventoryValuation.items.map((item) => [
      item.name,
      item.sku || '',
      item.quantity,
      item.avg_cost || '',
      item.value,
    ])
  }

  const csvContent = [
    headers.join(','),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `inventory_valuation_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

onMounted(async () => {
  // Stock module is always enabled - load data
  await stockStore.fetchWarehouses()
  await loadValuation()
})
</script>
// CLAUDE-CHECKPOINT
