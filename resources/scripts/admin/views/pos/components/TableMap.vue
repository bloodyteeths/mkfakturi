<template>
  <div class="p-4">
    <div class="grid grid-cols-4 sm:grid-cols-5 md:grid-cols-6 lg:grid-cols-8 gap-3">
      <button
        v-for="table in tables"
        :key="table.number"
        class="aspect-square flex flex-col items-center justify-center rounded-2xl border-2 font-bold transition-all duration-200 active:scale-95"
        :class="tableClass(table)"
        @click="$emit('select-table', table.number)"
      >
        <span class="text-2xl tabular-nums">{{ table.number }}</span>
        <span v-if="table.status === 'occupied'" class="text-[10px] mt-0.5 opacity-80">
          {{ table.itemCount }} {{ t('pos.quantity') || 'items' }}
        </span>
        <span v-if="table.status === 'occupied'" class="text-xs font-black mt-0.5 tabular-nums">
          {{ formatPrice(table.total) }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  tableCount: { type: Number, default: 20 },
  tableOrders: { type: Object, default: () => ({}) },
})

defineEmits(['select-table'])

const tables = computed(() => {
  const result = []
  for (let i = 1; i <= props.tableCount; i++) {
    const order = props.tableOrders[i]
    result.push({
      number: i,
      status: order && order.items.length > 0 ? 'occupied' : 'free',
      itemCount: order?.items?.length || 0,
      total: order?.total || 0,
    })
  }
  return result
})

function tableClass(table) {
  if (table.status === 'occupied') {
    return 'bg-amber-50 dark:bg-amber-900/30 border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 shadow-md shadow-amber-500/10'
  }
  return 'bg-emerald-50 dark:bg-emerald-900/20 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 hover:shadow-md'
}

function formatPrice(cents) {
  if (!cents) return ''
  return (cents / 100).toLocaleString('mk-MK', { minimumFractionDigits: 0 })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
