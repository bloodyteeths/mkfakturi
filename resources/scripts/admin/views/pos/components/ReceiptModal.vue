<template>
  <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-900 rounded-3xl shadow-2xl w-full max-w-sm text-center overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800">
      <!-- Success animation -->
      <div class="pt-10 pb-6 bg-gradient-to-b from-emerald-50 to-white dark:from-emerald-950/30 dark:to-gray-900">
        <div class="relative w-20 h-20 mx-auto">
          <div class="absolute inset-0 bg-emerald-100 dark:bg-emerald-900/40 rounded-full animate-ping opacity-20"></div>
          <div class="relative w-20 h-20 bg-gradient-to-br from-emerald-400 to-green-500 rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/30">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Sale info -->
      <div class="px-6 pb-5">
        <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-tight">
          {{ t('pos.sale_complete') || 'Sale complete!' }}
        </h3>
        <p v-if="sale?.invoice" class="text-sm text-gray-400 mt-1 font-medium">
          {{ t('pos.receipt_number') || 'Receipt' }} {{ sale.invoice.invoice_number }}
        </p>

        <!-- Totals card -->
        <div class="mt-4 bg-gray-50 dark:bg-gray-800 rounded-2xl p-5 space-y-3 text-sm ring-1 ring-gray-100 dark:ring-gray-700">
          <div class="flex justify-between">
            <span class="text-gray-400 font-medium">{{ t('pos.total') || 'Total' }}</span>
            <span class="font-bold text-gray-800 dark:text-gray-200 tabular-nums">{{ formatPrice(sale?.invoice?.total) }}</span>
          </div>
          <div v-if="sale?.change > 0" class="flex justify-between items-baseline pt-3 border-t border-gray-200 dark:border-gray-700">
            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ t('pos.change') || 'Change' }}</span>
            <span class="text-2xl font-black text-emerald-500 dark:text-emerald-400 tabular-nums">
              {{ formatPrice(sale.change) }}
            </span>
          </div>
        </div>

        <!-- Stock warnings -->
        <div v-if="sale?.stock_warnings?.length" class="mt-3 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl text-left ring-1 ring-amber-200 dark:ring-amber-800/50">
          <div class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-1.5">{{ t('pos.stock_low') || 'Low Stock' }}</div>
          <div v-for="w in sale.stock_warnings" :key="w.item_id" class="text-xs text-amber-700 dark:text-amber-300 py-0.5">
            {{ w.name }}: <span class="font-bold">{{ w.remaining_qty }}</span> {{ t('pos.remaining_stock') || 'remaining' }}
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="px-6 pb-6 grid grid-cols-2 gap-3">
        <button
          class="py-3 text-sm font-bold border-2 border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-300 transition-all active:scale-95"
          @click="$emit('print')"
        >
          {{ t('pos.print_again') || 'Print Again' }}
        </button>
        <button
          class="py-3 text-sm font-bold bg-gradient-to-r from-primary-500 to-primary-600 hover:from-primary-600 hover:to-primary-700 text-white rounded-xl shadow-md shadow-primary-500/20 transition-all active:scale-95"
          @click="$emit('new-sale')"
        >
          {{ t('pos.new_sale') || 'New Sale' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  sale: { type: Object, default: null },
})

defineEmits(['new-sale', 'print', 'close'])

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
