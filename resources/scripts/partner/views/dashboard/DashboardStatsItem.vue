<template>
  <div class="flex items-center p-4 bg-white rounded-lg shadow-xs">
    <div class="p-3 mr-4 rounded-full" :class="colorClasses">
      <component :is="iconComponent" class="w-5 h-5" />
    </div>
    <div>
      <p class="mb-2 text-sm font-medium text-gray-600">
        {{ title }}
      </p>
      <p class="text-lg font-semibold text-gray-700">
        {{ value }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import CustomerIcon from '@/scripts/components/icons/dashboard/CustomerIcon.vue'
import DollarIcon from '@/scripts/components/icons/dashboard/DollarIcon.vue'
import InvoiceIcon from '@/scripts/components/icons/dashboard/InvoiceIcon.vue'
import EstimateIcon from '@/scripts/components/icons/dashboard/EstimateIcon.vue'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: String,
    required: true
  },
  color: {
    type: String,
    default: 'text-blue-500'
  }
})

const iconComponent = computed(() => {
  const components = {
    CustomerIcon,
    DollarIcon,
    InvoiceIcon,
    EstimateIcon
  }
  return components[props.icon] || CustomerIcon
})

const colorClasses = computed(() => {
  const colorMap = {
    'text-blue-500': 'text-blue-500 bg-blue-100',
    'text-green-500': 'text-green-500 bg-green-100',
    'text-purple-500': 'text-purple-500 bg-purple-100',
    'text-orange-500': 'text-orange-500 bg-orange-100'
  }
  return colorMap[props.color] || 'text-blue-500 bg-blue-100'
})
</script>