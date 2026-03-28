<template>
  <div class="flex flex-col h-full">
    <!-- Cart Header -->
    <div class="px-4 py-2.5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shrink-0">
      <h2 class="font-semibold text-gray-900 dark:text-gray-100">
        {{ t('pos.cart') || 'Cart' }}
        <span v-if="itemCount > 0" class="text-sm font-normal text-gray-500">({{ itemCount }})</span>
      </h2>
      <div class="flex gap-2">
        <button
          v-if="parkedCount > 0"
          class="text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200 transition-colors"
          @click="$emit('show-parked')"
        >
          {{ parkedCount }} parked
        </button>
        <button
          v-if="items.length > 0"
          class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 transition-colors"
          @click="$emit('park')"
        >
          {{ t('pos.park_sale') || 'Park' }}
        </button>
        <button
          v-if="items.length > 0"
          class="text-xs px-2 py-1 bg-red-50 text-red-600 rounded hover:bg-red-100 transition-colors"
          @click="$emit('clear')"
        >
          {{ t('pos.clear_cart') || 'Clear' }}
        </button>
      </div>
    </div>

    <!-- Cart Items (scrollable) -->
    <div class="flex-1 overflow-y-auto">
      <div v-if="items.length === 0" class="flex flex-col items-center justify-center h-full text-gray-400 p-8">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
        </svg>
        <span class="text-sm">{{ t('pos.cart_empty') || 'Cart is empty' }}</span>
        <span class="text-xs mt-1 text-gray-300">Scan or tap items to add</span>
      </div>

      <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
        <div
          v-for="(item, index) in items"
          :key="item.item_id"
          class="px-4 py-2.5 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-750"
        >
          <!-- Item info -->
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ item.name }}</div>
            <div class="text-xs text-gray-500">{{ formatPrice(item.price) }} {{ item.unit_name ? '/ ' + item.unit_name : '' }}</div>
          </div>

          <!-- Qty controls -->
          <div class="flex items-center gap-1 shrink-0">
            <button
              class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 active:bg-gray-300 text-lg font-bold"
              @click="$emit('update-qty', { index, qty: item.quantity - 1 })"
            >
              -
            </button>
            <span class="w-8 text-center text-sm font-medium">{{ item.quantity }}</span>
            <button
              class="w-7 h-7 flex items-center justify-center rounded bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 active:bg-gray-300 text-lg font-bold"
              @click="$emit('update-qty', { index, qty: item.quantity + 1 })"
            >
              +
            </button>
          </div>

          <!-- Line total -->
          <div class="text-sm font-semibold text-gray-900 dark:text-gray-100 w-20 text-right shrink-0">
            {{ formatPrice(item.price * item.quantity) }}
          </div>

          <!-- Remove -->
          <button
            class="text-gray-300 hover:text-red-500 transition-colors shrink-0"
            @click="$emit('remove', index)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Totals + Pay -->
    <div class="border-t border-gray-200 dark:border-gray-700 shrink-0">
      <!-- Totals -->
      <div class="px-4 py-2 space-y-1 text-sm">
        <div class="flex justify-between text-gray-500">
          <span>{{ t('pos.subtotal') || 'Subtotal' }}</span>
          <span>{{ formatPrice(subTotal) }}</span>
        </div>
        <div v-if="discount > 0" class="flex justify-between text-green-600">
          <span>{{ t('pos.discount') || 'Discount' }}</span>
          <span>-{{ formatPrice(discount) }}</span>
        </div>
        <div class="flex justify-between text-gray-500">
          <span>{{ t('pos.tax') || 'VAT' }}</span>
          <span>{{ formatPrice(tax) }}</span>
        </div>
        <div class="flex justify-between text-lg font-bold text-gray-900 dark:text-white pt-1 border-t border-gray-100 dark:border-gray-700">
          <span>{{ t('pos.total') || 'TOTAL' }}</span>
          <span>{{ formatPrice(total) }}</span>
        </div>
      </div>

      <!-- Pay Button -->
      <div class="px-4 pb-4">
        <button
          :disabled="items.length === 0 || isProcessing || limitReached"
          class="w-full py-4 rounded-lg text-white font-bold text-lg transition-all flex items-center justify-center gap-2"
          :class="items.length > 0 && !isProcessing && !limitReached
            ? 'bg-green-600 hover:bg-green-700 active:bg-green-800 shadow-lg'
            : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
          @click="$emit('pay')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          {{ t('pos.pay') || 'PAY' }} {{ items.length > 0 ? formatPrice(total) : '' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  items: { type: Array, default: () => [] },
  subTotal: { type: Number, default: 0 },
  tax: { type: Number, default: 0 },
  discount: { type: Number, default: 0 },
  total: { type: Number, default: 0 },
  itemCount: { type: Number, default: 0 },
  isProcessing: { type: Boolean, default: false },
  limitReached: { type: Boolean, default: false },
  parkedCount: { type: Number, default: 0 },
})

defineEmits(['update-qty', 'remove', 'clear', 'pay', 'park', 'show-parked'])

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
