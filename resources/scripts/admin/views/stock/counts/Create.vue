<template>
  <div class="relative">
    <StockTabNavigation />

    <div class="max-w-2xl">
      <h2 class="text-xl font-semibold text-gray-900 mb-6">
        {{ $t('stock.new_count', 'Нов попис на залиха') }}
      </h2>

      <form @submit.prevent="createCount" class="space-y-6">
        <!-- Warehouse -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $t('stock.warehouse', 'Магацин') }} *
          </label>
          <select
            v-model="form.warehouse_id"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            required
          >
            <option value="" disabled>{{ $t('stock.select_warehouse', 'Изберете магацин') }}</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
        </div>

        <!-- Count Date -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $t('stock.count_date', 'Датум на попис') }} *
          </label>
          <input
            v-model="form.count_date"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            required
          />
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            {{ $t('stock.notes', 'Забелешки') }}
          </label>
          <textarea
            v-model="form.notes"
            rows="3"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
            :placeholder="$t('stock.count_notes_placeholder', 'Опис или причина за попис...')"
          ></textarea>
        </div>

        <!-- Buttons -->
        <div class="flex items-center space-x-4">
          <button
            type="submit"
            :disabled="isSubmitting || !form.warehouse_id"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSubmitting" class="animate-spin mr-2 h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
            {{ $t('stock.create_count', 'Креирај попис') }}
          </button>
          <router-link
            to="/admin/stock/counts"
            class="text-sm text-gray-600 hover:text-gray-900"
          >
            {{ $t('general.cancel', 'Откажи') }}
          </router-link>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const router = useRouter()
const notificationStore = useNotificationStore()

const warehouses = ref([])
const isSubmitting = ref(false)

const today = new Date()
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`

const form = reactive({
  warehouse_id: '',
  count_date: todayStr,
  notes: '',
})

const fetchWarehouses = async () => {
  try {
    const response = await window.axios.get('/stock/warehouses', { params: { limit: 100 } })
    warehouses.value = response.data.data || []
  } catch (err) {
    console.error('Failed to fetch warehouses:', err)
  }
}

const createCount = async () => {
  if (!form.warehouse_id) return

  isSubmitting.value = true
  try {
    const response = await window.axios.post('/stock/counts', {
      warehouse_id: form.warehouse_id,
      count_date: form.count_date,
      notes: form.notes || null,
    })

    notificationStore.showNotification({
      type: 'success',
      message: 'Пописот е креиран успешно.',
    })

    const countId = response.data.data?.id
    if (countId) {
      router.push({ name: 'stock.counts.view', params: { id: countId } })
    } else {
      router.push({ name: 'stock.counts' })
    }
  } catch (err) {
    console.error('Failed to create stock count:', err)
    const msg = err.response?.data?.message || err.response?.data?.error || 'Failed to create stock count.'
    notificationStore.showNotification({
      type: 'error',
      message: msg,
    })
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  fetchWarehouses()
})
</script>
// CLAUDE-CHECKPOINT
