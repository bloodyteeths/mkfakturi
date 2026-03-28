<template>
  <div class="flex flex-col h-full bg-white dark:bg-gray-900">
    <!-- Cart Header -->
    <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
      <div class="flex items-center gap-2">
        <h2 class="font-bold text-gray-900 dark:text-gray-100 tracking-tight">
          {{ t('pos.cart') || 'Cart' }}
        </h2>
        <span v-if="itemCount > 0" class="text-xs font-bold bg-primary-500 text-white px-2 py-0.5 rounded-full min-w-[24px] text-center">
          {{ itemCount }}
        </span>
      </div>
      <div class="flex gap-1.5">
        <button
          v-if="parkedCount > 0"
          class="text-xs font-medium px-2.5 py-1.5 bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/50 transition-colors"
          @click="$emit('show-parked')"
        >
          {{ parkedCount }} parked
        </button>
        <button
          v-if="items.length > 0"
          class="text-xs font-medium px-2.5 py-1.5 bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          @click="$emit('park')"
        >
          {{ t('pos.park_sale') || 'Park' }}
        </button>
        <button
          v-if="items.length > 0"
          class="text-xs font-medium px-2.5 py-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
          @click="$emit('clear')"
        >
          {{ t('pos.clear_cart') || 'Clear' }}
        </button>
      </div>
    </div>

    <!-- Cart Items (scrollable) -->
    <div class="flex-1 overflow-y-auto">
      <div v-if="items.length === 0" class="flex flex-col items-center justify-center h-full p-8">
        <div class="w-20 h-20 rounded-2xl bg-gray-50 dark:bg-gray-800 flex items-center justify-center mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-200 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
          </svg>
        </div>
        <span class="text-sm font-medium text-gray-400">{{ t('pos.cart_empty') || 'Cart is empty' }}</span>
        <span class="text-xs mt-1 text-gray-300 dark:text-gray-600">Scan or tap items to add</span>
      </div>

      <div v-else>
        <div
          v-for="(item, index) in items"
          :key="item.item_id"
          class="group px-5 py-3 flex items-center gap-3 hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors border-b border-gray-50 dark:border-gray-800/50"
        >
          <!-- Item info -->
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">{{ item.name }}</div>
            <div class="text-xs text-gray-400 mt-0.5">{{ formatPrice(item.price) }} {{ item.unit_name ? '/ ' + item.unit_name : '' }}</div>
          </div>

          <!-- Qty controls -->
          <div class="flex items-center gap-0.5 shrink-0 bg-gray-50 dark:bg-gray-800 rounded-lg p-0.5">
            <button
              class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 hover:shadow-sm active:scale-90 transition-all font-bold text-lg"
              @click="$emit('update-qty', { index, qty: item.quantity - 1 })"
            >
              -
            </button>
            <span class="w-8 text-center text-sm font-bold text-gray-800 dark:text-gray-200">{{ item.quantity }}</span>
            <button
              class="w-8 h-8 flex items-center justify-center rounded-md text-gray-500 hover:bg-white dark:hover:bg-gray-700 hover:text-gray-700 dark:hover:text-gray-200 hover:shadow-sm active:scale-90 transition-all font-bold text-lg"
              @click="$emit('update-qty', { index, qty: item.quantity + 1 })"
            >
              +
            </button>
          </div>

          <!-- Line total -->
          <div class="text-sm font-bold text-gray-900 dark:text-gray-100 w-20 text-right shrink-0 tabular-nums">
            {{ formatPrice(item.price * item.quantity) }}
          </div>

          <!-- Remove (appears on hover) -->
          <button
            class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-red-500 transition-all shrink-0"
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
    <div class="border-t border-gray-100 dark:border-gray-800 shrink-0 bg-gray-50/50 dark:bg-gray-900">
      <!-- Totals -->
      <div class="px-5 py-3 space-y-1.5 text-sm">
        <div class="flex justify-between text-gray-400">
          <span>{{ t('pos.subtotal') || 'Subtotal' }}</span>
          <span class="tabular-nums">{{ formatPrice(subTotal) }}</span>
        </div>
        <div v-if="discount > 0" class="flex justify-between text-emerald-500">
          <span>{{ t('pos.discount') || 'Discount' }}</span>
          <span class="tabular-nums">-{{ formatPrice(discount) }}</span>
        </div>
        <div class="flex justify-between text-gray-400">
          <span>{{ t('pos.tax') || 'VAT' }}</span>
          <span class="tabular-nums">{{ formatPrice(tax) }}</span>
        </div>
        <div class="flex justify-between items-baseline pt-2 border-t border-gray-200 dark:border-gray-700">
          <span class="text-base font-bold text-gray-900 dark:text-white">{{ t('pos.total') || 'TOTAL' }}</span>
          <span class="text-2xl font-black text-gray-900 dark:text-white tabular-nums">{{ formatPrice(total) }}</span>
        </div>
      </div>

      <!-- Pay Button -->
      <div class="px-5 pb-5">
        <button
          :disabled="items.length === 0 || isProcessing || limitReached"
          class="w-full py-4 rounded-2xl text-white font-bold text-lg transition-all duration-200 flex items-center justify-center gap-2.5 relative overflow-hidden"
          :class="items.length > 0 && !isProcessing && !limitReached
            ? 'bg-gradient-to-r from-emerald-500 to-green-500 hover:from-emerald-600 hover:to-green-600 active:from-emerald-700 active:to-green-700 shadow-lg shadow-green-500/25 hover:shadow-xl hover:shadow-green-500/30 hover:-translate-y-0.5'
            : 'bg-gray-200 dark:bg-gray-700 cursor-not-allowed text-gray-400'"
          @click="$emit('pay')"
        >
          <!-- Shimmer effect on active button -->
          <div
            v-if="items.length > 0 && !isProcessing && !limitReached"
            class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -translate-x-full animate-shimmer"
          ></div>
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 relative" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <span class="relative">{{ t('pos.pay') || 'PAY' }} {{ items.length > 0 ? formatPrice(total) : '' }}</span>
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

<style scoped>
@keyframes shimmer {
  to { transform: translateX(200%); }
}
.animate-shimmer {
  animation: shimmer 3s ease-in-out infinite;
}
</style>

<!-- CLAUDE-CHECKPOINT -->
