<template>
  <div class="relative">
    <StockTabNavigation />

    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold text-gray-900">
        {{ $t('stock.stocktake', 'Попис на залиха') }}
      </h2>
      <router-link
        to="/admin/stock/counts/create"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none"
      >
        + {{ $t('stock.new_count', 'Нов попис') }}
      </router-link>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-4 mb-6">
      <select
        v-model="filters.warehouse_id"
        class="block w-48 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        @change="fetchCounts"
      >
        <option value="">{{ $t('stock.all_warehouses', 'Сите магацини') }}</option>
        <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
          {{ wh.name }}
        </option>
      </select>

      <select
        v-model="filters.status"
        class="block w-40 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
        @change="fetchCounts"
      >
        <option value="">{{ $t('stock.all_statuses', 'Сите статуси') }}</option>
        <option value="draft">{{ $t('stock.status_draft', 'Нацрт') }}</option>
        <option value="in_progress">{{ $t('stock.status_in_progress', 'Во тек') }}</option>
        <option value="completed">{{ $t('stock.status_completed', 'Завршен') }}</option>
        <option value="cancelled">{{ $t('stock.status_cancelled', 'Откажан') }}</option>
      </select>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <!-- Empty state -->
    <div v-else-if="counts.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('stock.no_counts', 'Нема пописи') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('stock.no_counts_desc', 'Започнете со креирање нов попис на залиха.') }}</p>
    </div>

    <!-- Table -->
    <div v-else class="bg-white shadow overflow-hidden rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.warehouse', 'Магацин') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.count_date', 'Датум') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.status', 'Статус') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.items_counted', 'Артикли') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.variance', 'Разлика') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.counted_by', 'Пребројано од') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="count in counts"
            :key="count.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="$router.push({ name: 'stock.counts.view', params: { id: count.id } })"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ count.id }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
              {{ count.warehouse_name }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
              {{ count.count_date }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="statusClass(count.status)"
              >
                {{ statusLabel(count.status) }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
              {{ count.total_items_counted }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm" :class="count.total_variance_quantity !== 0 ? 'text-red-600 font-medium' : 'text-gray-500'">
              {{ count.total_variance_quantity !== 0 ? count.total_variance_quantity.toFixed(2) : '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
              {{ count.counted_by_name }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="meta.last_page > 1" class="flex justify-center mt-4 space-x-2">
      <button
        v-for="page in meta.last_page"
        :key="page"
        class="px-3 py-1 text-sm rounded"
        :class="page === meta.current_page ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
        @click="goToPage(page)"
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const counts = ref([])
const warehouses = ref([])
const isLoading = ref(false)
const meta = reactive({ current_page: 1, last_page: 1, total: 0 })
const filters = reactive({ warehouse_id: '', status: '' })

const statusClass = (status) => {
  const map = {
    draft: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-gray-100 text-gray-800',
  }
  return map[status] || 'bg-gray-100 text-gray-800'
}

const statusLabel = (status) => {
  const map = {
    draft: 'Нацрт',
    in_progress: 'Во тек',
    completed: 'Завршен',
    cancelled: 'Откажан',
  }
  return map[status] || status
}

const fetchCounts = async (page = 1) => {
  isLoading.value = true
  try {
    const params = { page, limit: 25 }
    if (filters.warehouse_id) params.warehouse_id = filters.warehouse_id
    if (filters.status) params.status = filters.status

    const response = await window.axios.get('/stock/counts', { params })
    counts.value = response.data.data || []
    Object.assign(meta, response.data.meta || {})
  } catch (err) {
    console.error('Failed to fetch stock counts:', err)
  } finally {
    isLoading.value = false
  }
}

const fetchWarehouses = async () => {
  try {
    const response = await window.axios.get('/stock/warehouses', { params: { limit: 100 } })
    warehouses.value = response.data.data || []
  } catch (err) {
    console.error('Failed to fetch warehouses:', err)
  }
}

const goToPage = (page) => {
  fetchCounts(page)
}

onMounted(() => {
  fetchWarehouses()
  fetchCounts()
})
</script>
// CLAUDE-CHECKPOINT
