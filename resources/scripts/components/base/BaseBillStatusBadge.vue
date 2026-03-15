<template>
  <span :class="badgeColorClasses">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: false,
    default: '',
  },
})

const badgeColorClasses = computed(() => {
  switch (props.status) {
    case 'DRAFT':
      return 'bg-gray-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-gray-700 uppercase rounded-full'
    case 'SENT':
      return 'bg-blue-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-blue-700 uppercase rounded-full'
    case 'VIEWED':
      return 'bg-amber-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-amber-700 uppercase rounded-full'
    case 'COMPLETED':
      return 'bg-green-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-green-700 uppercase rounded-full'
    case 'OVERDUE':
      return 'bg-red-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-red-700 uppercase rounded-full'
    case 'UNPAID':
      return 'bg-amber-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-amber-700 uppercase rounded-full'
    case 'PARTIALLY_PAID':
      return 'bg-blue-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-blue-700 uppercase rounded-full'
    case 'PAID':
      return 'bg-green-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-green-700 uppercase rounded-full'
    default:
      return 'bg-gray-100 bg-opacity-75 px-3 py-1 text-xs font-medium text-gray-700 uppercase rounded-full'
  }
})
</script>
