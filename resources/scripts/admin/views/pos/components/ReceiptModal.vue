<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-sm text-center">
      <!-- Success icon -->
      <div class="pt-8 pb-4">
        <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
      </div>

      <!-- Sale info -->
      <div class="px-6 pb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
          {{ t('pos.sale_complete') || 'Sale complete!' }}
        </h3>
        <p v-if="sale?.invoice" class="text-sm text-gray-500 mb-4">
          {{ t('pos.receipt_number') || 'Receipt' }} {{ sale.invoice.invoice_number }}
        </p>

        <!-- Totals -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2 text-sm">
          <div class="flex justify-between">
            <span class="text-gray-500">{{ t('pos.total') || 'Total' }}</span>
            <span class="font-semibold">{{ formatPrice(sale?.invoice?.total) }}</span>
          </div>
          <div v-if="sale?.change > 0" class="flex justify-between text-green-600 dark:text-green-400 font-bold text-base pt-1 border-t border-gray-200 dark:border-gray-600">
            <span>{{ t('pos.change') || 'Change' }}</span>
            <span>{{ formatPrice(sale.change) }}</span>
          </div>
        </div>

        <!-- Stock warnings -->
        <div v-if="sale?.stock_warnings?.length" class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg text-left">
          <div class="text-xs font-medium text-yellow-700 dark:text-yellow-400 mb-1">{{ t('pos.stock_low') || 'Low Stock' }}:</div>
          <div v-for="w in sale.stock_warnings" :key="w.item_id" class="text-xs text-yellow-600">
            {{ w.name }}: {{ w.remaining_qty }} remaining
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="px-6 pb-6 grid grid-cols-2 gap-3">
        <button
          class="py-2.5 text-sm font-medium border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
          @click="$emit('print')"
        >
          {{ t('pos.print_again') || 'Print Again' }}
        </button>
        <button
          class="py-2.5 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
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
