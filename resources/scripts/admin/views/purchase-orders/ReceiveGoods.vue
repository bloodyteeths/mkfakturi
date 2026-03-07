<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50" @click="$emit('close')" />
    <div class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full mx-4 max-h-[85vh] overflow-y-auto">
      <!-- Header -->
      <div class="sticky top-0 bg-white px-6 py-4 border-b border-gray-200 flex items-center justify-between z-10">
        <div>
          <h3 class="text-lg font-medium text-gray-900">{{ t('receive_goods') }}</h3>
          <p class="text-sm text-gray-500">{{ po.po_number }}</p>
        </div>
        <button class="text-gray-400 hover:text-gray-600" @click="$emit('close')">
          <BaseIcon name="XMarkIcon" class="h-5 w-5" />
        </button>
      </div>

      <!-- Items Table -->
      <div class="p-6">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('item_name') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('quantity_ordered') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('quantity_received') }}</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('quantity_remaining') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('quantity_accepted') }}</th>
                <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">{{ t('quantity_rejected') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="(item, index) in receiveItems" :key="item.purchase_order_item_id">
                <td class="px-4 py-3 text-sm text-gray-900">{{ item.name }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ item.ordered }}</td>
                <td class="px-4 py-3 text-sm text-right text-gray-500">{{ item.already_received }}</td>
                <td class="px-4 py-3 text-sm text-right">
                  <span :class="item.remaining > 0 ? 'text-amber-600 font-medium' : 'text-green-600'">
                    {{ item.remaining }}
                  </span>
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model.number="item.quantity_received"
                    type="number"
                    min="0"
                    :max="item.remaining"
                    step="0.01"
                    class="w-24 text-center text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                    :disabled="item.remaining <= 0"
                  />
                </td>
                <td class="px-4 py-3">
                  <input
                    v-model.number="item.quantity_rejected"
                    type="number"
                    min="0"
                    :max="item.quantity_received"
                    step="0.01"
                    class="w-24 text-center text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                    :disabled="item.remaining <= 0"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Summary -->
        <div class="mt-4 p-3 bg-gray-50 rounded-lg">
          <p class="text-sm text-gray-600">
            {{ t('items') }}: {{ receiveItems.filter(i => i.quantity_received > 0).length }} / {{ receiveItems.length }}
          </p>
        </div>
      </div>

      <!-- Footer -->
      <div class="sticky bottom-0 bg-white px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
        <BaseButton variant="primary-outline" @click="$emit('close')">
          {{ t('back') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :loading="isSubmitting"
          :disabled="!hasReceivableItems"
          @click="submitReceipt"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckIcon" :class="slotProps.class" />
          </template>
          {{ t('confirm_receive') }}
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/purchase-orders.js'

const props = defineProps({
  po: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['close', 'received'])
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return poMessages[locale]?.purchaseOrders?.[key]
    || poMessages['en']?.purchaseOrders?.[key]
    || key
}

// State
const isSubmitting = ref(false)
const receiveItems = ref([])

// Build the receive items from PO items
onMounted(() => {
  receiveItems.value = (props.po.items || []).map(item => ({
    purchase_order_item_id: item.id,
    item_id: item.item_id,
    name: item.name,
    ordered: item.quantity,
    already_received: item.received_quantity,
    remaining: Math.max(0, item.quantity - item.received_quantity),
    quantity_received: Math.max(0, item.quantity - item.received_quantity),
    quantity_rejected: 0,
  }))
})

// Computed
const hasReceivableItems = computed(() => {
  return receiveItems.value.some(i => i.quantity_received > 0)
})

// Methods
async function submitReceipt() {
  isSubmitting.value = true

  const items = receiveItems.value
    .filter(i => i.quantity_received > 0)
    .map(i => ({
      purchase_order_item_id: i.purchase_order_item_id,
      quantity_received: i.quantity_received,
      quantity_accepted: Math.max(0, i.quantity_received - i.quantity_rejected),
      quantity_rejected: i.quantity_rejected,
    }))

  try {
    const response = await window.axios.post(`/purchase-orders/${props.po.id}/receive-goods`, {
      items,
    })

    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('goods_received') || 'Goods received successfully',
    })

    emit('received')
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_receiving') || 'Failed to receive goods',
    })
  } finally {
    isSubmitting.value = false
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
