<template>
  <div class="flex-1 overflow-y-auto p-4">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-48">
      <div class="animate-spin w-8 h-8 border-2 border-primary-500 border-t-transparent rounded-full"></div>
    </div>

    <!-- Empty -->
    <div v-else-if="items.length === 0" class="flex flex-col items-center justify-center h-48 text-gray-400">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
      <span class="text-sm">No items found</span>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
      <button
        v-for="item in items"
        :key="item.id"
        class="flex flex-col p-3 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg hover:border-primary-400 hover:shadow-md active:scale-95 transition-all text-left min-h-[88px]"
        @click="$emit('select', item)"
      >
        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 line-clamp-2 leading-tight">
          {{ item.name }}
        </span>
        <div class="mt-auto pt-1.5 flex items-end justify-between">
          <span class="text-base font-bold text-primary-600 dark:text-primary-400">
            {{ formatPrice(item.retail_price || item.price) }}
          </span>
          <span
            v-if="item.track_quantity"
            class="text-xs px-1.5 py-0.5 rounded"
            :class="item.quantity > 0
              ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400'
              : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
          >
            {{ item.quantity > 0 ? item.quantity : 'x' }}
          </span>
        </div>
        <span v-if="item.barcode" class="text-[10px] text-gray-400 mt-0.5 truncate">
          {{ item.barcode }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  items: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

defineEmits(['select'])

function formatPrice(cents) {
  if (!cents) return '0 МКД'
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 }) + ' МКД'
}
</script>

<!-- CLAUDE-CHECKPOINT -->
