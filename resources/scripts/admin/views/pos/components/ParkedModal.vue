<template>
  <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="$emit('close')">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl w-full max-w-md">
      <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          {{ t('pos.parked_sales') || 'Parked Sales' }}
        </h3>
        <button class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" @click="$emit('close')">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="max-h-80 overflow-y-auto">
        <div v-if="sales.length === 0" class="p-8 text-center text-gray-400 text-sm">
          No parked sales
        </div>
        <div
          v-for="sale in sales"
          :key="sale.id"
          class="px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-750"
        >
          <div>
            <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
              {{ sale.items.length }} items — {{ formatPrice(sale.total) }}
            </div>
            <div class="text-xs text-gray-500">
              {{ formatTime(sale.parkedAt) }}
            </div>
          </div>
          <button
            class="px-3 py-1.5 text-sm font-medium bg-primary-600 text-white rounded hover:bg-primary-700 transition-colors"
            @click="$emit('resume', sale.id)"
          >
            {{ t('pos.resume') || 'Resume' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  sales: { type: Array, default: () => [] },
})

defineEmits(['resume', 'close'])

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}

function formatTime(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleTimeString('mk-MK', { hour: '2-digit', minute: '2-digit' })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
