<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.item_card')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" />
        <BaseBreadcrumbItem :title="$t('stock.item_card')" to="#" active />
      </BaseBreadcrumb>
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
      <!-- Filters Card -->
      <BaseCard class="mb-6">
        <template #header>
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.filters') }}</h3>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Item Selector -->
          <BaseInputGroup :label="$t('stock.item')" required>
            <BaseMultiselect
              v-model="filters.item"
              :content-loading="isLoadingItems"
              value-prop="id"
              track-by="name"
              label="name"
              :filterResults="false"
              resolve-on-load
              :delay="500"
              searchable
              :options="searchItems"
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

          <!-- Warehouse Selector -->
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

          <!-- From Date -->
          <BaseInputGroup :label="$t('general.from_date')">
            <BaseDatePicker
              v-model="filters.from_date"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <!-- To Date -->
          <BaseInputGroup :label="$t('general.to_date')">
            <BaseDatePicker
              v-model="filters.to_date"
              :calendar-button="true"
            />
          </BaseInputGroup>
        </div>

        <div class="flex justify-end mt-4 space-x-2">
          <BaseButton variant="secondary" @click="clearFilters">
            {{ $t('general.clear') }}
          </BaseButton>
          <BaseButton variant="primary" :disabled="!filters.item" @click="loadItemCard">
            {{ $t('general.apply') }}
          </BaseButton>
        </div>
      </BaseCard>

      <!-- Results -->
      <template v-if="hasData">
        <!-- Item Info & Balances -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
          <!-- Item Info -->
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.item_info') }}</h3>
            </template>
            <div class="space-y-3">
              <div>
                <dt class="text-sm font-medium text-gray-500">{{ $t('items.name') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.itemCard.item?.name }}</dd>
              </div>
              <div v-if="stockStore.itemCard.item?.sku">
                <dt class="text-sm font-medium text-gray-500">{{ $t('items.sku') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.itemCard.item.sku }}</dd>
              </div>
              <div v-if="stockStore.itemCard.item?.barcode">
                <dt class="text-sm font-medium text-gray-500">{{ $t('items.barcode') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ stockStore.itemCard.item.barcode }}</dd>
              </div>
              <div v-if="stockStore.itemCard.item?.unit">
                <dt class="text-sm font-medium text-gray-500">{{ $t('items.unit') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.itemCard.item.unit }}</dd>
              </div>
            </div>
          </BaseCard>

          <!-- Opening Balance -->
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.opening_balance') }}</h3>
            </template>
            <div class="space-y-3">
              <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                <span class="text-sm font-medium text-blue-700">{{ $t('stock.quantity') }}</span>
                <span class="text-blue-700 font-semibold">
                  {{ formatNumber(stockStore.itemCard.opening_balance.quantity) }}
                </span>
              </div>
              <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
                <span class="text-sm font-medium text-blue-700">{{ $t('stock.value') }}</span>
                <BaseFormatMoney :amount="stockStore.itemCard.opening_balance.value" />
              </div>
            </div>
          </BaseCard>

          <!-- Closing Balance -->
          <BaseCard>
            <template #header>
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.closing_balance') }}</h3>
            </template>
            <div class="space-y-3">
              <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                <span class="text-sm font-medium text-green-700">{{ $t('stock.quantity') }}</span>
                <span class="text-green-700 font-semibold">
                  {{ formatNumber(stockStore.itemCard.closing_balance.quantity) }}
                </span>
              </div>
              <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                <span class="text-sm font-medium text-green-700">{{ $t('stock.value') }}</span>
                <BaseFormatMoney :amount="stockStore.itemCard.closing_balance.value" />
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Movements Table -->
        <BaseCard>
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.movements') }}</h3>
              <BaseButton variant="primary-outline" size="sm" @click="exportToCsv">
                <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4 mr-1" />
                {{ $t('general.export') }}
              </BaseButton>
            </div>
          </template>

          <div v-if="stockStore.isLoadingItemCard" class="flex justify-center py-8">
            <BaseContentPlaceholders>
              <BaseContentPlaceholdersBox class="w-full h-64" />
            </BaseContentPlaceholders>
          </div>

          <div v-else-if="stockStore.itemCard.movements.length === 0" class="text-center py-8">
            <BaseIcon name="ArchiveBoxIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.no_movements') }}</h3>
            <p class="text-gray-500 mt-2">{{ $t('stock.no_movements_message') }}</p>
          </div>

          <table v-else class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.date') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.source') }}
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.description') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.qty_in') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.qty_out') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.unit_cost') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.line_value') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.balance_qty') }}
                </th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                  {{ $t('stock.balance_value') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="movement in stockStore.itemCard.movements" :key="movement.id">
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ movement.date }}
                </td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">
                  <BaseBadge :bg-color="getSourceColor(movement.source_type)">
                    {{ $t(`stock.source_types.${movement.source_type}`) }}
                  </BaseBadge>
                  <span v-if="movement.reference" class="ml-2 text-gray-500">
                    {{ movement.reference }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                  {{ movement.description || '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">
                  {{ movement.quantity > 0 ? formatNumber(movement.quantity) : '' }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-red-600 font-medium">
                  {{ movement.quantity < 0 ? formatNumber(Math.abs(movement.quantity)) : '' }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <BaseFormatMoney v-if="movement.unit_cost" :amount="movement.unit_cost" />
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <BaseFormatMoney v-if="movement.line_value" :amount="movement.line_value" />
                  <span v-else>-</span>
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900 font-medium">
                  {{ formatNumber(movement.balance_quantity) }}
                </td>
                <td class="px-4 py-3 text-sm text-right text-gray-900">
                  <BaseFormatMoney :amount="movement.balance_value" />
                </td>
              </tr>
            </tbody>
          </table>
        </BaseCard>
      </template>

      <!-- Empty State -->
      <BaseCard v-else-if="!stockStore.isLoadingItemCard">
        <div class="text-center py-12">
          <BaseIcon name="DocumentTextIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
          <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.select_item_prompt') }}</h3>
          <p class="text-gray-500 mt-2">{{ $t('stock.select_item_prompt_message') }}</p>
        </div>
      </BaseCard>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const stockStore = useStockStore()
const itemStore = useItemStore()
const globalStore = useGlobalStore()

const isLoadingItems = ref(false)

const filters = reactive({
  item: null,
  warehouse: null,
  from_date: null,
  to_date: null,
})

const warehouseOptions = computed(() => {
  return [
    { id: null, name: '-- All Warehouses --' },
    ...stockStore.warehouses,
  ]
})

const hasData = computed(() => {
  return stockStore.itemCard.item !== null
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('en-US', { maximumFractionDigits: 4 })
}

function getSourceColor(sourceType) {
  const colors = {
    initial: '#6B7280',
    bill: '#10B981',
    invoice: '#EF4444',
    adjustment: '#F59E0B',
    transfer_in: '#3B82F6',
    transfer_out: '#8B5CF6',
  }
  return colors[sourceType] || '#6B7280'
}

async function searchItems(search) {
  isLoadingItems.value = true
  try {
    const res = await itemStore.fetchItems({ search, track_quantity: true })
    return res.data.data
  } finally {
    isLoadingItems.value = false
  }
}

async function loadItemCard() {
  if (!filters.item) return

  const params = {}
  if (filters.warehouse?.id) params.warehouse_id = filters.warehouse.id
  if (filters.from_date) params.from_date = filters.from_date
  if (filters.to_date) params.to_date = filters.to_date

  await stockStore.fetchItemCard(filters.item.id, params)
}

function clearFilters() {
  filters.item = null
  filters.warehouse = null
  filters.from_date = null
  filters.to_date = null
  stockStore.resetItemCard()
}

function exportToCsv() {
  if (!stockStore.itemCard.movements.length) return

  const headers = [
    'Date',
    'Source Type',
    'Reference',
    'Description',
    'Qty In',
    'Qty Out',
    'Unit Cost',
    'Line Value',
    'Balance Qty',
    'Balance Value',
  ]

  const rows = stockStore.itemCard.movements.map((m) => [
    m.date,
    m.source_type,
    m.reference || '',
    m.description || '',
    m.quantity > 0 ? m.quantity : '',
    m.quantity < 0 ? Math.abs(m.quantity) : '',
    m.unit_cost || '',
    m.line_value || '',
    m.balance_quantity,
    m.balance_value,
  ])

  const csvContent = [
    headers.join(','),
    ...rows.map((row) => row.map((cell) => `"${cell}"`).join(',')),
  ].join('\n')

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `stock_card_${stockStore.itemCard.item?.sku || 'item'}_${new Date().toISOString().split('T')[0]}.csv`
  link.click()
}

onMounted(async () => {
  // Check if stock is enabled via bootstrap data
  const bootstrap = globalStore.bootstrap
  stockStore.setStockEnabled(bootstrap?.stock_enabled || false)

  // Load warehouses
  if (stockStore.stockEnabled) {
    await stockStore.fetchWarehouses()
  }
})
</script>
// CLAUDE-CHECKPOINT
