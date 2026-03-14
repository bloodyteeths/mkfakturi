<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.inventory', 'Материјално / Inventory')">
      <template #actions>
        <BaseButton v-if="activeTab === 'inventory' && inventoryData.length > 0" variant="primary-outline" @click="exportCsv">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          {{ $t('general.export') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="id"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <!-- Select company message -->
    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Tab Navigation -->
      <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            class="whitespace-nowrap border-b-2 py-3 px-1 text-sm font-medium"
            :class="activeTab === tab.key
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
            @click="activeTab = tab.key"
          >
            {{ tab.label }}
          </button>
        </nav>
      </div>

      <!-- Inventory List Tab -->
      <template v-if="activeTab === 'inventory'">
        <!-- Search Bar -->
        <div v-if="inventoryData.length > 0 || searchQuery" class="mb-4">
          <input
            v-model="searchQuery"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :placeholder="$t('stock.search_placeholder', 'Search by name or SKU...')"
          />
        </div>

        <!-- Summary Cards -->
        <div v-if="inventoryData.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="rounded-lg bg-white shadow p-6">
            <p class="text-sm font-medium text-gray-500">{{ $t('stock.total_items', 'Total Items') }}</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ filteredAndSortedInventory.length }}</p>
          </div>
          <div class="rounded-lg bg-white shadow p-6">
            <p class="text-sm font-medium text-gray-500">{{ $t('stock.total_quantity', 'Total Quantity') }}</p>
            <p class="mt-2 text-3xl font-bold text-green-600">{{ formatNumber(totalQuantity) }}</p>
          </div>
          <div class="rounded-lg bg-white shadow p-6">
            <p class="text-sm font-medium text-gray-500">{{ $t('stock.total_value', 'Total Value') }}</p>
            <p class="mt-2 text-3xl font-bold text-purple-600">{{ formatMoney(totalValue) }}</p>
          </div>
        </div>

        <!-- Inventory Table -->
        <div v-if="filteredAndSortedInventory.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700" @click="toggleSort('name')">
                    {{ $t('items.name', 'Name') }}
                    <span v-if="sortKey === 'name'" class="ml-1">{{ sortDir === 'asc' ? '\u25B2' : '\u25BC' }}</span>
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700" @click="toggleSort('sku')">
                    {{ $t('items.sku', 'SKU') }}
                    <span v-if="sortKey === 'sku'" class="ml-1">{{ sortDir === 'asc' ? '\u25B2' : '\u25BC' }}</span>
                  </th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700" @click="toggleSort('quantity')">
                    {{ $t('stock.quantity', 'Quantity') }}
                    <span v-if="sortKey === 'quantity'" class="ml-1">{{ sortDir === 'asc' ? '\u25B2' : '\u25BC' }}</span>
                  </th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700" @click="toggleSort('unit_cost')">
                    {{ $t('stock.unit_cost', 'Unit Cost') }}
                    <span v-if="sortKey === 'unit_cost'" class="ml-1">{{ sortDir === 'asc' ? '\u25B2' : '\u25BC' }}</span>
                  </th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase cursor-pointer select-none hover:text-gray-700" @click="toggleSort('total_value')">
                    {{ $t('stock.total_value', 'Total Value') }}
                    <span v-if="sortKey === 'total_value'" class="ml-1">{{ sortDir === 'asc' ? '\u25B2' : '\u25BC' }}</span>
                  </th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('stock.item_card_short', 'Card') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="item in filteredAndSortedInventory" :key="item.item_id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-primary-600 cursor-pointer hover:text-primary-800 hover:underline" @click="viewItemCard(item)">
                    {{ item.name }}
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ item.sku || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-right" :class="item.quantity < 0 ? 'text-red-600 font-medium' : 'text-gray-900'">
                    {{ formatNumber(item.quantity) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatMoney(item.unit_cost) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(item.total_value) }}</td>
                  <td class="px-4 py-3 text-center">
                    <BaseButton size="sm" variant="primary-outline" @click="viewItemCard(item)">
                      <BaseIcon name="DocumentTextIcon" class="h-4 w-4" />
                    </BaseButton>
                  </td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr class="font-semibold">
                  <td colspan="2" class="px-4 py-3 text-sm">{{ $t('general.total') }} ({{ filteredAndSortedInventory.length }})</td>
                  <td class="px-4 py-3 text-sm text-right">{{ formatNumber(filteredTotalQuantity) }}</td>
                  <td class="px-4 py-3"></td>
                  <td class="px-4 py-3 text-sm text-right">{{ formatMoney(filteredTotalValue) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Empty State (no results after search) -->
        <div v-else-if="searchQuery && inventoryData.length > 0" class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="MagnifyingGlassIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('general.no_results', 'No results found') }}</h3>
        </div>

        <!-- Empty State (no inventory) -->
        <div v-else-if="!isLoading && hasSearched" class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="ArchiveBoxIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('stock.no_inventory', 'No inventory items') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ $t('stock.no_inventory_message', 'This company has no tracked inventory items.') }}</p>
        </div>

        <!-- Loading -->
        <div v-else-if="isLoading" class="flex justify-center py-8">
          <BaseContentPlaceholders>
            <BaseContentPlaceholdersBox :rounded="true" class="w-full h-64" />
          </BaseContentPlaceholders>
        </div>
      </template>

      <!-- Item Card Tab -->
      <template v-if="activeTab === 'itemcard'">
        <!-- Item Selector -->
        <div class="p-4 bg-white rounded-lg shadow mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <BaseInputGroup :label="$t('stock.item', 'Item')" required>
              <BaseMultiselect
                v-model="selectedItemId"
                :options="inventoryData"
                :searchable="true"
                value-prop="item_id"
                label="name"
                track-by="item_id"
                :placeholder="$t('stock.select_item', 'Select item...')"
              />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('general.from_date', 'From Date')">
              <BaseDatePicker v-model="cardFromDate" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('general.to_date', 'To Date')">
              <BaseDatePicker v-model="cardToDate" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
            </BaseInputGroup>
            <div class="flex items-end">
              <BaseButton variant="primary" :loading="isLoadingCard" :disabled="!selectedItemId" @click="loadItemCard">
                <template #left="slotProps">
                  <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
                </template>
                {{ $t('general.load', 'Load') }}
              </BaseButton>
            </div>
          </div>
        </div>

        <!-- Item Card Data -->
        <template v-if="itemCardData">
          <!-- Balance Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="rounded-lg bg-white shadow p-6">
              <p class="text-sm font-medium text-gray-500">{{ $t('stock.item_info', 'Item') }}</p>
              <p class="mt-1 text-lg font-bold text-gray-900">{{ itemCardData.item?.name }}</p>
              <p v-if="itemCardData.item?.sku" class="text-sm text-gray-500">{{ itemCardData.item.sku }}</p>
            </div>
            <div class="rounded-lg bg-blue-50 shadow p-6">
              <p class="text-sm font-medium text-blue-700">{{ $t('stock.opening_balance', 'Opening Balance') }}</p>
              <p class="mt-1 text-2xl font-bold text-blue-900">{{ formatNumber(itemCardData.opening_balance?.quantity) }}</p>
              <p class="text-sm text-blue-600">{{ formatMoney(itemCardData.opening_balance?.value) }}</p>
            </div>
            <div class="rounded-lg bg-green-50 shadow p-6">
              <p class="text-sm font-medium text-green-700">{{ $t('stock.closing_balance', 'Closing Balance') }}</p>
              <p class="mt-1 text-2xl font-bold text-green-900">{{ formatNumber(itemCardData.closing_balance?.quantity) }}</p>
              <p class="text-sm text-green-600">{{ formatMoney(itemCardData.closing_balance?.value) }}</p>
            </div>
          </div>

          <!-- Movements Table -->
          <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.movements', 'Movements') }} ({{ itemCardData.movements?.length || 0 }})</h3>
              <BaseButton v-if="itemCardData.movements?.length" variant="primary-outline" size="sm" @click="exportCardCsv">
                <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4 mr-1" />
                CSV
              </BaseButton>
            </div>
            <div v-if="itemCardData.movements?.length > 0" class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('stock.source', 'Source') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.qty_in', 'In') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.qty_out', 'Out') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.unit_cost', 'Unit Cost') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.balance_qty', 'Balance') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.balance_value', 'Value') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="m in itemCardData.movements" :key="m.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ m.date }}</td>
                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                        :class="sourceClass(m.source_type)"
                      >
                        {{ sourceLabel(m.source_type) }}
                      </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ m.description || m.reference || '-' }}</td>
                    <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">{{ m.quantity > 0 ? formatNumber(m.quantity) : '' }}</td>
                    <td class="px-4 py-3 text-sm text-right text-red-600 font-medium">{{ m.quantity < 0 ? formatNumber(Math.abs(m.quantity)) : '' }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ m.unit_cost ? formatMoney(m.unit_cost) : '-' }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatNumber(m.balance_quantity) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-gray-900">{{ m.balance_value ? formatMoney(m.balance_value) : '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="p-8 text-center text-gray-500">
              {{ $t('stock.no_movements', 'No movements in this period') }}
            </div>
          </div>
        </template>

        <!-- Empty State for Item Card -->
        <div v-else-if="!isLoadingCard" class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('stock.select_item_prompt', 'Select an item') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ $t('stock.select_item_prompt_message', 'Choose an item to view its stock card.') }}</p>
        </div>
      </template>

      <!-- Stock Movements Log Tab -->
      <template v-if="activeTab === 'movements'">
        <!-- Filters -->
        <div class="p-4 bg-white rounded-lg shadow mb-6">
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <BaseInputGroup :label="$t('general.from_date', 'From Date')">
              <BaseDatePicker v-model="logFromDate" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('general.to_date', 'To Date')">
              <BaseDatePicker v-model="logToDate" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
            </BaseInputGroup>
            <BaseInputGroup :label="$t('stock.source_type_filter', 'Source Type')">
              <select
                v-model="logSourceType"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
              >
                <option value="">{{ $t('stock.all_sources', 'All Sources') }}</option>
                <option value="initial">{{ $t('stock.source_types.initial', 'Initial') }}</option>
                <option value="bill">{{ $t('stock.source_types.bill', 'Purchase (Bill)') }}</option>
                <option value="invoice">{{ $t('stock.source_types.invoice', 'Sale (Invoice)') }}</option>
                <option value="adjustment">{{ $t('stock.source_types.adjustment', 'Adjustment') }}</option>
                <option value="transfer_in">{{ $t('stock.source_types.transfer_in', 'Transfer In') }}</option>
                <option value="transfer_out">{{ $t('stock.source_types.transfer_out', 'Transfer Out') }}</option>
              </select>
            </BaseInputGroup>
            <div class="flex items-end">
              <BaseButton variant="primary" :loading="isLoadingLog" @click="loadMovementsLog">
                <template #left="slotProps">
                  <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
                </template>
                {{ $t('general.load', 'Load') }}
              </BaseButton>
            </div>
          </div>
        </div>

        <!-- Movements Table -->
        <div v-if="movementsLogData.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900">{{ $t('stock.all_movements', 'All Movements') }} ({{ movementsLogData.length }})</h3>
            <BaseButton variant="primary-outline" size="sm" @click="exportLogCsv">
              <BaseIcon name="ArrowDownTrayIcon" class="h-4 w-4 mr-1" />
              CSV
            </BaseButton>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('items.name', 'Item') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('stock.source', 'Source') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.qty_in', 'In') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.qty_out', 'Out') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.unit_cost', 'Unit Cost') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.balance_qty', 'Balance') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="m in movementsLogData" :key="m.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">{{ m.date }}</td>
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ m.item_name }}</td>
                  <td class="px-4 py-3 text-sm whitespace-nowrap">
                    <span
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                      :class="sourceClass(m.source_type)"
                    >
                      {{ sourceLabel(m.source_type) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ m.description || m.reference || '-' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-green-600 font-medium">{{ m.quantity > 0 ? formatNumber(m.quantity) : '' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-red-600 font-medium">{{ m.quantity < 0 ? formatNumber(Math.abs(m.quantity)) : '' }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ m.unit_cost ? formatMoney(m.unit_cost) : '-' }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatNumber(m.balance_quantity) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Loading -->
        <div v-else-if="isLoadingLog" class="flex justify-center py-8">
          <BaseContentPlaceholders>
            <BaseContentPlaceholdersBox :rounded="true" class="w-full h-64" />
          </BaseContentPlaceholders>
        </div>

        <!-- Empty State -->
        <div v-else-if="hasSearchedLog" class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="ArrowsRightLeftIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('stock.no_movements', 'No movements') }}</h3>
        </div>

        <!-- Initial State -->
        <div v-else class="bg-white rounded-lg shadow p-12 text-center">
          <BaseIcon name="ArrowsRightLeftIcon" class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('stock.all_movements', 'All Movements') }}</h3>
          <p class="mt-1 text-sm text-gray-500">{{ $t('stock.select_item_prompt_message', 'Choose filters and click Load to view stock movements.') }}</p>
        </div>
      </template>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

function todayStr() {
  return new Date().toISOString().slice(0, 10)
}

const selectedCompanyId = ref(null)
const activeTab = ref('inventory')
const isLoading = ref(false)
const isLoadingCard = ref(false)
const hasSearched = ref(false)

// Inventory tab
const inventoryData = ref([])
const searchQuery = ref('')
const sortKey = ref('name')
const sortDir = ref('asc')

// Item card tab
const selectedItemId = ref(null)
const cardFromDate = ref(`${new Date().getFullYear()}-01-01`)
const cardToDate = ref(todayStr())
const itemCardData = ref(null)

// Movements log tab
const logFromDate = ref(`${new Date().getFullYear()}-01-01`)
const logToDate = ref(todayStr())
const logSourceType = ref('')
const movementsLogData = ref([])
const isLoadingLog = ref(false)
const hasSearchedLog = ref(false)

const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const tabs = computed(() => [
  { key: 'inventory', label: t('stock.inventory_list', 'Inventory List') },
  { key: 'itemcard', label: t('stock.item_card', 'Item Card') },
  { key: 'movements', label: t('stock.movements_log', 'Stock Movements Log') },
])

// Client-side filter + sort
const filteredAndSortedInventory = computed(() => {
  let result = inventoryData.value

  // Search filter
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      (item.name || '').toLowerCase().includes(q) ||
      (item.sku || '').toLowerCase().includes(q)
    )
  }

  // Sort
  const key = sortKey.value
  const dir = sortDir.value === 'asc' ? 1 : -1
  result = [...result].sort((a, b) => {
    const aVal = a[key] ?? ''
    const bVal = b[key] ?? ''
    if (typeof aVal === 'string') return aVal.localeCompare(bVal) * dir
    return (aVal - bVal) * dir
  })

  return result
})

const totalQuantity = computed(() => inventoryData.value.reduce((sum, item) => sum + (item.quantity || 0), 0))
const totalValue = computed(() => inventoryData.value.reduce((sum, item) => sum + (item.total_value || 0), 0))
const filteredTotalQuantity = computed(() => filteredAndSortedInventory.value.reduce((sum, item) => sum + (item.quantity || 0), 0))
const filteredTotalValue = computed(() => filteredAndSortedInventory.value.reduce((sum, item) => sum + (item.total_value || 0), 0))

onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('partner.accounting.failed_to_load_companies', 'Failed to load companies') })
    return
  }
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
    onCompanyChange()
  }
})

function onCompanyChange() {
  inventoryData.value = []
  itemCardData.value = null
  selectedItemId.value = null
  movementsLogData.value = []
  hasSearched.value = false
  hasSearchedLog.value = false
  searchQuery.value = ''
  if (selectedCompanyId.value) {
    loadInventory()
  }
}

async function loadInventory() {
  if (!selectedCompanyId.value) return
  isLoading.value = true
  hasSearched.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/stock-reports/valuation`, {
      params: { group_by: 'item' },
    })
    const data = response.data.data || response.data
    inventoryData.value = (data.items || []).map(entry => ({
      item_id: entry.item?.id || entry.id,
      name: entry.item?.name || entry.name,
      sku: entry.item?.sku || entry.sku,
      warehouse_name: null,
      quantity: entry.total_quantity ?? entry.quantity ?? 0,
      unit_cost: entry.weighted_average_cost ?? entry.unit_cost ?? 0,
      total_value: entry.total_value ?? 0,
    }))
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('stock.failed_to_load_inventory', 'Failed to load inventory') })
  } finally {
    isLoading.value = false
  }
}

async function loadItemCard() {
  if (!selectedCompanyId.value || !selectedItemId.value) return
  isLoadingCard.value = true
  try {
    const params = {}
    if (cardFromDate.value) params.from_date = cardFromDate.value
    if (cardToDate.value) params.to_date = cardToDate.value
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/stock-reports/item-card/${selectedItemId.value}`, { params })
    itemCardData.value = response.data.data || response.data
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to load item card' })
  } finally {
    isLoadingCard.value = false
  }
}

async function loadMovementsLog() {
  if (!selectedCompanyId.value) return
  isLoadingLog.value = true
  hasSearchedLog.value = true
  try {
    const params = {}
    if (logFromDate.value) params.from_date = logFromDate.value
    if (logToDate.value) params.to_date = logToDate.value
    if (logSourceType.value) params.source_type = logSourceType.value
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/stock-reports/movements`, { params })
    const data = response.data.data || response.data
    movementsLogData.value = data.movements || []
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to load movements' })
  } finally {
    isLoadingLog.value = false
  }
}

function viewItemCard(item) {
  selectedItemId.value = item.item_id
  activeTab.value = 'itemcard'
  loadItemCard()
}

function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'asc'
  }
}

function formatNumber(num) {
  if (num === null || num === undefined) return '-'
  return Number(num).toLocaleString('mk-MK', { maximumFractionDigits: 4 })
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = amount / 100
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ' + selectedCompanyCurrency.value
}

function sourceClass(sourceType) {
  const map = {
    initial: 'bg-gray-100 text-gray-700',
    bill: 'bg-green-100 text-green-700',
    bill_item: 'bg-green-100 text-green-700',
    invoice: 'bg-red-100 text-red-700',
    invoice_item: 'bg-red-100 text-red-700',
    adjustment: 'bg-amber-100 text-amber-700',
    transfer_in: 'bg-blue-100 text-blue-700',
    transfer_out: 'bg-purple-100 text-purple-700',
    inventory_document: 'bg-teal-100 text-teal-700',
  }
  return map[sourceType] || 'bg-gray-100 text-gray-700'
}

function sourceLabel(sourceType) {
  const normalized = sourceType === 'bill_item' ? 'bill' : sourceType === 'invoice_item' ? 'invoice' : sourceType
  return t(`stock.source_types.${normalized}`, sourceType)
}

function exportCsv() {
  if (!filteredAndSortedInventory.value.length) return
  const headers = [t('items.name'), 'SKU', t('stock.quantity'), t('stock.unit_cost'), t('stock.total_value')]
  const rows = filteredAndSortedInventory.value.map(item => [
    item.name, item.sku || '', item.quantity,
    item.unit_cost ? (item.unit_cost / 100).toFixed(2) : '0', item.total_value ? (item.total_value / 100).toFixed(2) : '0',
  ])
  downloadCsv(headers, rows, 'inventory_report')
}

function exportCardCsv() {
  if (!itemCardData.value?.movements?.length) return
  const headers = [t('general.date'), t('stock.source'), t('general.description'), t('stock.qty_in'), t('stock.qty_out'), t('stock.unit_cost'), t('stock.balance_qty'), t('stock.balance_value')]
  const rows = itemCardData.value.movements.map(m => [
    m.date, sourceLabel(m.source_type), m.description || m.reference || '',
    m.quantity > 0 ? m.quantity : '', m.quantity < 0 ? Math.abs(m.quantity) : '',
    m.unit_cost ? (m.unit_cost / 100).toFixed(2) : '', m.balance_quantity,
    m.balance_value ? (m.balance_value / 100).toFixed(2) : '',
  ])
  downloadCsv(headers, rows, `item_card_${itemCardData.value.item?.sku || 'item'}`)
}

function exportLogCsv() {
  if (!movementsLogData.value.length) return
  const headers = [t('general.date'), t('items.name'), 'SKU', t('stock.source'), t('general.description'), t('stock.qty_in'), t('stock.qty_out'), t('stock.unit_cost'), t('stock.balance_qty')]
  const rows = movementsLogData.value.map(m => [
    m.date, m.item_name, m.item_sku || '',
    sourceLabel(m.source_type), m.description || m.reference || '',
    m.quantity > 0 ? m.quantity : '', m.quantity < 0 ? Math.abs(m.quantity) : '',
    m.unit_cost ? (m.unit_cost / 100).toFixed(2) : '', m.balance_quantity,
  ])
  downloadCsv(headers, rows, 'movements_log')
}

function downloadCsv(headers, rows, filename) {
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${c}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `${filename}_${todayStr()}.csv`
  link.click()
}
</script>

<!-- CLAUDE-CHECKPOINT -->
