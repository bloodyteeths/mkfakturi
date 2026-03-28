<template>
  <div class="relative">
    <StockTabNavigation />

    <!-- Loading -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="stockCount">
      <!-- Header -->
      <div class="flex flex-wrap justify-between items-start mb-6 gap-4">
        <div>
          <div class="flex items-center space-x-3">
            <h2 class="text-xl font-semibold text-gray-900">
              {{ $t('stock.stocktake', 'Попис') }} #{{ stockCount.id }}
            </h2>
            <span
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="statusClass(stockCount.status)"
            >
              {{ statusLabel(stockCount.status) }}
            </span>
            <span v-if="stockCount.approved_at" class="text-xs text-green-600 font-medium">
              ({{ $t('stock.approved', 'Одобрено') }})
            </span>
          </div>
          <p class="mt-1 text-sm text-gray-500">
            {{ stockCount.warehouse_name }} &middot; {{ stockCount.count_date }}
            &middot; {{ stockCount.counted_by_name }}
          </p>
          <p v-if="stockCount.notes" class="mt-1 text-sm text-gray-500 italic">{{ stockCount.notes }}</p>
        </div>

        <!-- Actions -->
        <div class="flex space-x-3">
          <button
            v-if="stockCount.status === 'draft'"
            @click="deleteCount"
            class="inline-flex items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50"
            :disabled="isActing"
          >
            {{ $t('general.delete', 'Избриши') }}
          </button>
          <button
            v-if="stockCount.status === 'draft' || stockCount.status === 'in_progress'"
            @click="saveItems"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
            :disabled="isSaving"
          >
            <span v-if="isSaving" class="animate-spin mr-2 h-4 w-4 border-2 border-gray-500 border-t-transparent rounded-full"></span>
            {{ $t('general.save', 'Зачувај') }}
          </button>
          <button
            v-if="stockCount.status === 'draft' || stockCount.status === 'in_progress'"
            @click="completeCount"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
            :disabled="isActing"
          >
            {{ $t('stock.complete_count', 'Заврши попис') }}
          </button>
          <button
            v-if="stockCount.status === 'completed' && !stockCount.approved_at"
            @click="approveCount"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700"
            :disabled="isActing"
          >
            {{ $t('stock.approve_adjust', 'Одобри и корегирај') }}
          </button>
          <router-link
            to="/admin/stock/counts"
            class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            {{ $t('general.back', 'Назад') }}
          </router-link>
        </div>
      </div>

      <!-- Summary (for completed counts) -->
      <div v-if="stockCount.status === 'completed'" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('stock.items_counted', 'Пребројани артикли') }}</div>
          <div class="text-2xl font-bold text-gray-900">{{ stockCount.total_items_counted }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('stock.total_variance_qty', 'Вкупна разлика (кол.)') }}</div>
          <div class="text-2xl font-bold" :class="stockCount.total_variance_quantity !== 0 ? 'text-red-600' : 'text-green-600'">
            {{ stockCount.total_variance_quantity?.toFixed(2) || '0.00' }}
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('stock.total_variance_val', 'Вкупна разлика (вредн.)') }}</div>
          <div class="text-2xl font-bold" :class="stockCount.total_variance_value !== 0 ? 'text-red-600' : 'text-green-600'">
            {{ formatMoney(stockCount.total_variance_value || 0) }}
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="bg-white shadow overflow-hidden rounded-lg">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.item_name', 'Артикл') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.system_qty', 'Системска кол.') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.counted_qty', 'Пребројана кол.') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.variance', 'Разлика') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $t('stock.notes', 'Забелешки') }}</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="item in items"
                :key="item.id"
                :class="rowClass(item)"
              >
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ item.item_name }}</td>
                <td class="px-4 py-3 text-sm text-gray-500">{{ item.item_sku || '-' }}</td>
                <td class="px-4 py-3 text-sm text-gray-700 text-right">{{ parseFloat(item.system_quantity).toFixed(2) }}</td>
                <td class="px-4 py-3 text-right">
                  <input
                    v-if="isEditable"
                    v-model.number="item.counted_quantity"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-24 text-right rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    @input="recalcVariance(item)"
                  />
                  <span v-else class="text-sm text-gray-700">
                    {{ item.counted_quantity !== null ? parseFloat(item.counted_quantity).toFixed(2) : '-' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-sm text-right font-medium" :class="varianceTextClass(item)">
                  {{ item.variance_quantity !== null ? parseFloat(item.variance_quantity).toFixed(2) : '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-right" :class="varianceTextClass(item)">
                  {{ item.variance_percentage !== null ? item.variance_percentage.toFixed(1) + '%' : '-' }}
                </td>
                <td class="px-4 py-3">
                  <input
                    v-if="isEditable"
                    v-model="item.notes"
                    type="text"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    :placeholder="$t('stock.item_notes', 'Забелешка...')"
                  />
                  <span v-else class="text-sm text-gray-500">{{ item.notes || '' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const stockCount = ref(null)
const items = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const isActing = ref(false)

const isEditable = computed(() => {
  return stockCount.value && ['draft', 'in_progress'].includes(stockCount.value.status)
})

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

const rowClass = (item) => {
  if (item.counted_quantity === null || item.counted_quantity === '') return ''
  const variance = item.variance_quantity
  if (variance === null || variance === undefined) return ''
  const absVariance = Math.abs(variance)
  const pct = item.system_quantity != 0 ? (absVariance / Math.abs(item.system_quantity)) * 100 : (absVariance > 0 ? 100 : 0)
  if (pct === 0) return 'bg-green-50'
  if (pct <= 5) return 'bg-yellow-50'
  return 'bg-red-50'
}

const varianceTextClass = (item) => {
  if (item.variance_quantity === null || item.variance_quantity === undefined) return 'text-gray-400'
  if (item.variance_quantity === 0) return 'text-green-600'
  const pct = item.system_quantity != 0 ? (Math.abs(item.variance_quantity) / Math.abs(item.system_quantity)) * 100 : 100
  if (pct <= 5) return 'text-yellow-600'
  return 'text-red-600'
}

const recalcVariance = (item) => {
  if (item.counted_quantity !== null && item.counted_quantity !== '') {
    const counted = parseFloat(item.counted_quantity) || 0
    const system = parseFloat(item.system_quantity) || 0
    item.variance_quantity = counted - system
    item.variance_percentage = system !== 0 ? ((counted - system) / system) * 100 : (counted !== 0 ? 100 : 0)
  } else {
    item.variance_quantity = null
    item.variance_percentage = null
  }
}

const formatMoney = (cents) => {
  const val = (cents / 100).toFixed(2)
  return val.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + ' ден.'
}

const fetchCount = async () => {
  isLoading.value = true
  try {
    const response = await window.axios.get(`/stock/counts/${route.params.id}`)
    stockCount.value = response.data.data
    items.value = response.data.data.items || []
  } catch (err) {
    console.error('Failed to fetch stock count:', err)
    notificationStore.showNotification({ type: 'error', message: 'Failed to load stock count.' })
  } finally {
    isLoading.value = false
  }
}

const saveItems = async () => {
  isSaving.value = true
  try {
    const payload = {
      notes: stockCount.value.notes,
      items: items.value.map((item) => ({
        id: item.id,
        counted_quantity: item.counted_quantity !== null && item.counted_quantity !== '' ? parseFloat(item.counted_quantity) : null,
        notes: item.notes || null,
      })),
    }

    const response = await window.axios.put(`/stock/counts/${route.params.id}`, payload)
    stockCount.value = response.data.data
    items.value = response.data.data.items || []

    notificationStore.showNotification({ type: 'success', message: 'Пописот е зачуван.' })
  } catch (err) {
    console.error('Failed to save items:', err)
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to save.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isSaving.value = false
  }
}

const completeCount = async () => {
  if (!confirm('Дали сте сигурни дека сакате да го завршите пописот? По завршување не може да се менуваат количини.')) return

  // Save first, then complete
  isActing.value = true
  try {
    // Save current items first
    const payload = {
      items: items.value.map((item) => ({
        id: item.id,
        counted_quantity: item.counted_quantity !== null && item.counted_quantity !== '' ? parseFloat(item.counted_quantity) : null,
        notes: item.notes || null,
      })),
    }
    await window.axios.put(`/stock/counts/${route.params.id}`, payload)

    // Now complete
    const response = await window.axios.post(`/stock/counts/${route.params.id}/complete`)
    stockCount.value = response.data.data
    items.value = response.data.data.items || []

    notificationStore.showNotification({ type: 'success', message: 'Пописот е завршен. Прегледајте ги разликите и одобрете.' })
  } catch (err) {
    console.error('Failed to complete count:', err)
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to complete.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isActing.value = false
  }
}

const approveCount = async () => {
  if (!confirm('Дали сте сигурни? Ова ќе креира корекции на залиха за сите разлики.')) return

  isActing.value = true
  try {
    const response = await window.axios.post(`/stock/counts/${route.params.id}/approve`)
    stockCount.value = response.data.data
    items.value = response.data.data.items || []

    const adjustments = response.data.adjustments_created || 0
    notificationStore.showNotification({
      type: 'success',
      message: `Пописот е одобрен. ${adjustments} корекции се креирани.`,
    })
  } catch (err) {
    console.error('Failed to approve count:', err)
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to approve.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isActing.value = false
  }
}

const deleteCount = async () => {
  if (!confirm('Дали сте сигурни дека сакате да го избришете овој попис?')) return

  isActing.value = true
  try {
    await window.axios.delete(`/stock/counts/${route.params.id}`)
    notificationStore.showNotification({ type: 'success', message: 'Пописот е избришан.' })
    router.push({ name: 'stock.counts' })
  } catch (err) {
    console.error('Failed to delete count:', err)
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to delete.'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isActing.value = false
  }
}

onMounted(() => {
  fetchCount()
})
</script>
// CLAUDE-CHECKPOINT
