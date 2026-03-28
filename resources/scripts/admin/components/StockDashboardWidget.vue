<template>
  <div v-if="hasData || isLoading" class="bg-white rounded-lg shadow p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">
        {{ $t('stock.dashboard.title') || 'Stock Overview' }}
      </h3>
      <router-link
        to="/admin/stock"
        class="text-sm text-primary-500 hover:text-primary-600 font-medium"
      >
        {{ $t('general.view_all') || 'View All' }}
      </router-link>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="animate-pulse space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div class="h-16 bg-gray-200 rounded"></div>
        <div class="h-16 bg-gray-200 rounded"></div>
        <div class="h-16 bg-gray-200 rounded"></div>
        <div class="h-16 bg-gray-200 rounded"></div>
      </div>
      <div class="h-4 bg-gray-200 rounded w-1/2 mt-4"></div>
      <div v-for="i in 3" :key="i" class="h-4 bg-gray-200 rounded w-full"></div>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Summary Numbers: 2x2 Grid -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-indigo-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('stock.dashboard.total_value') || 'Total Value' }}</p>
          <p class="text-lg font-bold text-indigo-600">
            {{ formatCurrency(data.total_value) }}
          </p>
        </div>

        <div class="bg-blue-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('stock.dashboard.items_tracked') || 'Items Tracked' }}</p>
          <p class="text-lg font-bold text-blue-600">
            {{ data.total_items }}
          </p>
        </div>

        <div class="bg-gray-50 rounded-lg p-3 text-center">
          <p class="text-xs text-gray-500 mb-1">{{ $t('stock.dashboard.total_quantity') || 'Total Quantity' }}</p>
          <p class="text-lg font-bold text-gray-700">
            {{ data.total_quantity.toLocaleString('mk-MK') }}
          </p>
        </div>

        <div class="rounded-lg p-3 text-center" :class="lowStockBgClass">
          <p class="text-xs text-gray-500 mb-1">{{ $t('stock.dashboard.low_stock_alerts') || 'Low Stock Alerts' }}</p>
          <div class="flex items-center justify-center gap-1.5">
            <p class="text-lg font-bold" :class="lowStockTextClass">
              {{ data.low_stock_count }}
            </p>
            <span
              v-if="criticalCount > 0"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 text-red-700"
            >
              {{ criticalCount }} {{ $t('stock.dashboard.critical') || 'critical' }}
            </span>
            <span
              v-else-if="data.low_stock_count > 0"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-yellow-100 text-yellow-700"
            >
              {{ $t('stock.dashboard.warning') || 'warning' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Top 5 Critical Items -->
      <div v-if="data.low_stock_items && data.low_stock_items.length > 0" class="mb-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('stock.dashboard.critical_items') || 'Critical Items' }}</h4>
        <div class="overflow-x-auto">
          <table class="w-full text-xs" role="table" :aria-label="$t('stock.dashboard.critical_items') || 'Critical Items'">
            <thead>
              <tr class="text-left text-gray-500 border-b">
                <th class="pb-1.5 font-medium">{{ $t('stock.dashboard.item') || 'Item' }}</th>
                <th class="pb-1.5 font-medium text-right">{{ $t('stock.dashboard.current') || 'Current' }}</th>
                <th class="pb-1.5 font-medium text-right">{{ $t('stock.dashboard.minimum') || 'Minimum' }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="item in data.low_stock_items"
                :key="item.item_id"
                class="border-b border-gray-100 last:border-0"
              >
                <td class="py-1.5 pr-2 truncate max-w-[140px]" :title="item.item_name">
                  {{ item.item_name }}
                </td>
                <td class="py-1.5 text-right font-mono" :class="getQtyClass(item)">
                  {{ item.current_quantity }}
                </td>
                <td class="py-1.5 text-right font-mono text-gray-500">
                  {{ item.minimum_quantity }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent Movements -->
      <div v-if="data.recent_movements && data.recent_movements.length > 0">
        <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('stock.dashboard.recent_movements') || 'Recent Movements' }}</h4>
        <div class="space-y-1.5">
          <div
            v-for="mov in data.recent_movements"
            :key="mov.id"
            class="flex items-center justify-between py-1.5 px-2 rounded bg-gray-50 text-xs"
          >
            <div class="flex-1 min-w-0 mr-2">
              <span class="text-gray-400 mr-1.5">{{ mov.date }}</span>
              <span class="text-gray-800 truncate">{{ mov.item_name }}</span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
              <span class="text-gray-500">{{ mov.source_type_label || mov.source_type }}</span>
              <span
                class="font-mono font-medium"
                :class="mov.is_stock_in ? 'text-green-600' : 'text-red-600'"
              >
                {{ mov.is_stock_in ? '+' : '' }}{{ mov.quantity }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Document Actions -->
      <div class="flex gap-2 pt-3 mt-3 border-t border-gray-200">
        <router-link
          to="/admin/stock/documents/create?type=receipt"
          class="flex-1 text-center text-xs font-medium text-emerald-600 hover:text-emerald-700 py-1.5 rounded border border-emerald-200 hover:bg-emerald-50 transition-colors"
        >
          {{ $t('stock.dashboard.new_receipt') || '+ Приемница' }}
        </router-link>
        <router-link
          to="/admin/stock/documents/create?type=issue"
          class="flex-1 text-center text-xs font-medium text-red-600 hover:text-red-700 py-1.5 rounded border border-red-200 hover:bg-red-50 transition-colors"
        >
          {{ $t('stock.dashboard.new_issue') || '+ Издатница' }}
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const isLoading = ref(true)
const data = ref({
  total_value: 0,
  total_items: 0,
  total_quantity: 0,
  low_stock_count: 0,
  low_stock_items: [],
  recent_movements: [],
})

const hasData = computed(() => data.value.total_items > 0)

const criticalCount = computed(() => {
  if (!data.value.low_stock_items) return 0
  return data.value.low_stock_items.filter(
    (item) => item.shortage_percentage >= 75
  ).length
})

const lowStockBgClass = computed(() => {
  if (criticalCount.value > 0) return 'bg-red-50'
  if (data.value.low_stock_count > 0) return 'bg-yellow-50'
  return 'bg-green-50'
})

const lowStockTextClass = computed(() => {
  if (criticalCount.value > 0) return 'text-red-600'
  if (data.value.low_stock_count > 0) return 'text-yellow-600'
  return 'text-green-600'
})

function formatCurrency(value) {
  return (value / 100).toLocaleString('mk-MK', { minimumFractionDigits: 2 }) + ' MKD'
}

function getQtyClass(item) {
  if (item.current_quantity <= 0) return 'text-red-700 font-semibold'
  if (item.shortage_percentage >= 75) return 'text-red-600 font-medium'
  return 'text-amber-700 font-medium'
}

async function fetchData() {
  isLoading.value = true
  try {
    const response = await window.axios.get('/stock/dashboard-summary')
    if (response?.data) {
      data.value = {
        total_value: response.data.total_value || 0,
        total_items: response.data.total_items || 0,
        total_quantity: response.data.total_quantity || 0,
        low_stock_count: response.data.low_stock_count || 0,
        low_stock_items: response.data.low_stock_items || [],
        recent_movements: response.data.recent_movements || [],
      }
    }
  } catch (error) {
    console.error('StockDashboardWidget: failed to fetch summary', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchData()
})
// CLAUDE-CHECKPOINT
</script>
