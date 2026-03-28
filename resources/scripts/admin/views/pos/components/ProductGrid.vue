<template>
  <div class="flex-1 overflow-y-auto p-4 bg-gradient-to-b from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-950">
    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center h-48">
      <div class="relative w-12 h-12">
        <div class="absolute inset-0 rounded-full border-2 border-primary-200 dark:border-primary-900"></div>
        <div class="absolute inset-0 rounded-full border-2 border-primary-500 border-t-transparent animate-spin"></div>
      </div>
    </div>

    <!-- Empty -->
    <div v-else-if="items.length === 0" class="flex flex-col items-center justify-center h-48 text-gray-400">
      <div class="w-16 h-16 rounded-2xl bg-gray-200/50 dark:bg-gray-800 flex items-center justify-center mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
      </div>
      <span class="text-sm font-medium">No items found</span>
    </div>

    <!-- Grid -->
    <div v-else class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
      <button
        v-for="item in items"
        :key="item.id"
        class="group relative flex flex-col p-3.5 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700/50 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-lg hover:shadow-primary-500/5 active:scale-[0.97] transition-all duration-150 text-left min-h-[100px] overflow-hidden"
        @click="$emit('select', item)"
      >
        <!-- Subtle gradient overlay on hover -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary-50/0 to-primary-50/0 group-hover:from-primary-50/50 group-hover:to-transparent dark:group-hover:from-primary-950/20 dark:group-hover:to-transparent transition-all duration-200 rounded-xl pointer-events-none"></div>

        <!-- Item photo -->
        <div v-if="item.image_url" class="relative w-full h-16 mb-2 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex-shrink-0">
          <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover" loading="lazy" />
        </div>

        <!-- Item name -->
        <span class="relative text-sm font-semibold text-gray-800 dark:text-gray-100 line-clamp-2 leading-tight tracking-tight">
          {{ item.name }}
        </span>

        <div class="relative mt-auto pt-2 flex items-end justify-between">
          <!-- Price -->
          <span class="text-lg font-bold bg-gradient-to-r from-primary-600 to-primary-500 dark:from-primary-400 dark:to-primary-300 bg-clip-text text-transparent">
            {{ formatPrice(item.retail_price || item.price) }}
          </span>
          <!-- Stock badge -->
          <span
            v-if="item.track_quantity"
            class="text-[10px] font-bold px-1.5 py-0.5 rounded-full"
            :class="item.quantity > 0
              ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
              : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400'"
          >
            {{ item.quantity > 0 ? item.quantity : 'Out' }}
          </span>
        </div>

        <!-- Barcode (subtle) -->
        <span v-if="item.barcode" class="relative text-[9px] text-gray-300 dark:text-gray-600 mt-1 truncate font-mono">
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
