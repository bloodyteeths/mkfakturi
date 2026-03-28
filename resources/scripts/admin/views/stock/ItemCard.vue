<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

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
              :filter-results="false"
              :min-chars="0"
              :delay="300"
              :searchable="true"
              :options="asyncSearchItems"
              value-prop="id"
              track-by="name"
              label="name"
              :object="true"
              :clear-on-select="false"
              :close-on-select="true"
              :placeholder="$t('stock.select_item')"
              :can-clear="true"
            />
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
              <div v-if="stockStore.itemCard.item?.unit_name">
                <dt class="text-sm font-medium text-gray-500">{{ $t('items.unit') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ stockStore.itemCard.item.unit_name }}</dd>
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
              <BaseButton variant="primary-outline" size="sm" @click="exportToPdf" :loading="isExportingPdf">
                <BaseIcon name="DocumentTextIcon" class="h-4 w-4 mr-1" />
                PDF
              </BaseButton>
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
              <tr v-for="movement in stockStore.itemCard.movements" :key="movement.id" :class="getRowClass(movement.source_type)">
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ movement.date }}
                </td>
                <td class="px-4 py-3 text-sm whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span
                      class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                      :class="getSourceClasses(movement.source_type)"
                    >
                      <span
                        class="w-1.5 h-1.5 rounded-full mr-1.5"
                        :class="getSourceDotClass(movement.source_type)"
                      ></span>
                      {{ getSourceLabel(movement.source_type) }}
                    </span>
                    <span v-if="movement.reference" class="text-xs text-gray-500 font-mono">
                      {{ movement.reference }}
                    </span>
                  </div>
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
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useStockStore } from '@/scripts/admin/stores/stock'
import { useItemStore } from '@/scripts/admin/stores/item'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()
const stockStore = useStockStore()
const itemStore = useItemStore()
const route = useRoute()

const isLoadingItems = ref(false)
const isExportingPdf = ref(false)

const filters = reactive({
  item: null,
  warehouse: null,
  from_date: null,
  to_date: null,
})

const warehouseOptions = computed(() => {
  return [
    { id: null, name: t('stock.all_warehouses') },
    ...stockStore.warehouses,
  ]
})

const hasData = computed(() => {
  return stockStore.itemCard.item !== null
})

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('mk-MK', { maximumFractionDigits: 4 })
}

function getSourceClasses(sourceType) {
  const classes = {
    initial: 'bg-gray-100 text-gray-700',
    opening_balance: 'bg-gray-100 text-gray-700',
    purchase: 'bg-green-100 text-green-700',
    bill: 'bg-green-100 text-green-700',
    bill_item: 'bg-green-100 text-green-700',
    sale: 'bg-red-100 text-red-700',
    invoice: 'bg-red-100 text-red-700',
    invoice_item: 'bg-red-100 text-red-700',
    adjustment: 'bg-amber-100 text-amber-700',
    adjustment_in: 'bg-amber-100 text-amber-700',
    adjustment_out: 'bg-amber-100 text-amber-700',
    transfer_in: 'bg-blue-100 text-blue-700',
    transfer_out: 'bg-purple-100 text-purple-700',
    inventory_document: 'bg-slate-100 text-slate-700',
    goods_receipt: 'bg-teal-100 text-teal-700',
    production_consume: 'bg-orange-100 text-orange-700',
    production_output: 'bg-emerald-100 text-emerald-700',
    production_byproduct: 'bg-lime-100 text-lime-700',
    production_wastage: 'bg-rose-100 text-rose-700',
    wac_correction: 'bg-indigo-100 text-indigo-700',
    return: 'bg-cyan-100 text-cyan-700',
    nivelacija: 'bg-violet-100 text-violet-700',
  }
  return classes[sourceType] || 'bg-gray-100 text-gray-700'
}

function getSourceDotClass(sourceType) {
  const dots = {
    initial: 'bg-gray-500',
    opening_balance: 'bg-gray-500',
    purchase: 'bg-green-500',
    bill: 'bg-green-500',
    bill_item: 'bg-green-500',
    sale: 'bg-red-500',
    invoice: 'bg-red-500',
    invoice_item: 'bg-red-500',
    adjustment: 'bg-amber-500',
    adjustment_in: 'bg-amber-500',
    adjustment_out: 'bg-amber-500',
    transfer_in: 'bg-blue-500',
    transfer_out: 'bg-purple-500',
    inventory_document: 'bg-slate-500',
    goods_receipt: 'bg-teal-500',
    production_consume: 'bg-orange-500',
    production_output: 'bg-emerald-500',
    production_byproduct: 'bg-lime-500',
    production_wastage: 'bg-rose-500',
    wac_correction: 'bg-indigo-500',
    return: 'bg-cyan-500',
    nivelacija: 'bg-violet-500',
  }
  return dots[sourceType] || 'bg-gray-500'
}

function getRowClass(sourceType) {
  if (sourceType === 'wac_correction') return 'bg-indigo-50'
  if (sourceType === 'nivelacija') return 'bg-violet-50'
  return ''
}
// CLAUDE-CHECKPOINT

function getSourceLabel(sourceType) {
  // Try translation first, fallback to formatted type
  const key = `stock.source_types.${sourceType}`
  const translated = t(key)
  if (translated !== key) {
    return translated
  }
  // Fallback: format the source type nicely
  return sourceType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

/**
 * Async search function for item multiselect
 * Returns items that have track_quantity enabled
 */
async function asyncSearchItems(search) {
  try {
    isLoadingItems.value = true
    const res = await itemStore.fetchItems({
      search: search || '',
      track_quantity: true,
      limit: 50,
    })
    return res.data.data || []
  } catch (error) {
    console.error('Failed to search items:', error)
    return []
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

async function exportToPdf() {
  if (!filters.item) return
  isExportingPdf.value = true
  try {
    const params = {}
    if (filters.warehouse?.id) params.warehouse_id = filters.warehouse.id
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date

    const response = await window.axios.get(`/stock/item-card/${filters.item.id}/pdf`, {
      params,
      responseType: 'blob',
    })
    const blob = new Blob([response.data], { type: 'application/pdf' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `lagerska-kartica-${filters.item.name || filters.item.id}.pdf`
    link.click()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Failed to export PDF:', error)
  } finally {
    isExportingPdf.value = false
  }
}

function exportToCsv() {
  if (!stockStore.itemCard.movements.length) return

  const BOM = '\uFEFF'
  const headers = [
    t('stock.date'),
    t('stock.source'),
    t('banking.reference'),
    t('stock.description'),
    t('stock.qty_in'),
    t('stock.qty_out'),
    t('stock.unit_cost'),
    t('stock.line_value'),
    t('stock.balance_qty'),
    t('stock.balance_value'),
  ]

  const rows = stockStore.itemCard.movements.map((m) => [
    m.date,
    getSourceLabel(m.source_type),
    m.reference || '',
    m.description || '',
    m.quantity > 0 ? m.quantity : '',
    m.quantity < 0 ? Math.abs(m.quantity) : '',
    m.unit_cost ? (m.unit_cost / 100).toFixed(2) : '',
    m.line_value ? (m.line_value / 100).toFixed(2) : '',
    m.balance_quantity,
    m.balance_value ? (m.balance_value / 100).toFixed(2) : '',
  ])

  const itemName = (stockStore.itemCard.item?.name || 'item').replace(/[^a-zA-Z0-9\u0400-\u04FF\u0410-\u044F -]/g, '')
  const csv = BOM + [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `lagerska-kartica-${itemName}-${new Date().toISOString().slice(0, 10)}.csv`
  a.click()
  URL.revokeObjectURL(url)
}
// CLAUDE-CHECKPOINT

onMounted(async () => {
  // Stock module is always enabled - load warehouses
  await stockStore.fetchWarehouses()

  // Check for item_id in query params OR route params
  const itemId = route.query.item_id || route.params.id
  if (itemId) {
    // Load the item card directly using the item ID
    isLoadingItems.value = true
    try {
      // First, fetch the item card data directly
      await stockStore.fetchItemCard(parseInt(itemId), {})

      // If successful, set a minimal item object for the filters
      // The actual item data comes from the API response
      if (stockStore.itemCard.item) {
        filters.item = {
          id: stockStore.itemCard.item.id,
          name: stockStore.itemCard.item.name,
          sku: stockStore.itemCard.item.sku,
        }
      }
    } catch (error) {
      console.error('Failed to load item card from route param:', error)
    } finally {
      isLoadingItems.value = false
    }
  }
})
</script>
