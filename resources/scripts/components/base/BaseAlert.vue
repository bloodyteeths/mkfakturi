<template>
  <div :class="alertClasses" class="rounded-md p-4">
    <div class="flex">
      <div class="shrink-0">
        <BaseIcon
          :name="iconName"
          :class="iconColorClass"
          class="h-5 w-5"
          aria-hidden="true"
        />
      </div>
      <div class="ml-3 flex-1">
        <h3 v-if="title" :class="titleColorClass" class="text-sm font-medium">
          {{ title }}
        </h3>
        <div :class="[contentColorClass, title ? 'mt-2' : '']" class="text-sm">
          <slot />
        </div>
      </div>
      <div v-if="dismissible" class="ml-auto pl-3">
        <div class="-mx-1.5 -my-1.5">
          <button
            type="button"
            :class="buttonColorClass"
            class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2"
            @click="$emit('dismiss')"
          >
            <span class="sr-only">Dismiss</span>
            <BaseIcon name="XMarkIcon" class="h-5 w-5" aria-hidden="true" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const emit = defineEmits(['dismiss'])

const props = defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'warning', 'error', 'success'].includes(value)
  },
  title: {
    type: String,
    default: ''
  },
  dismissible: {
    type: Boolean,
    default: false
  }
})

const alertClasses = computed(() => {
  const classes = {
    info: 'bg-blue-50',
    warning: 'bg-yellow-50',
    error: 'bg-red-50',
    success: 'bg-green-50'
  }
  return classes[props.type]
})

const iconName = computed(() => {
  const icons = {
    info: 'InformationCircleIcon',
    warning: 'ExclamationTriangleIcon',
    error: 'XCircleIcon',
    success: 'CheckCircleIcon'
  }
  return icons[props.type]
})

const iconColorClass = computed(() => {
  const classes = {
    info: 'text-blue-400',
    warning: 'text-yellow-400',
    error: 'text-red-400',
    success: 'text-green-400'
  }
  return classes[props.type]
})

const titleColorClass = computed(() => {
  const classes = {
    info: 'text-blue-800',
    warning: 'text-yellow-800',
    error: 'text-red-800',
    success: 'text-green-800'
  }
  return classes[props.type]
})

const contentColorClass = computed(() => {
  const classes = {
    info: 'text-blue-700',
    warning: 'text-yellow-700',
    error: 'text-red-700',
    success: 'text-green-700'
  }
  return classes[props.type]
})

const buttonColorClass = computed(() => {
  const classes = {
    info: 'bg-blue-50 text-blue-500 hover:bg-blue-100 focus:ring-blue-600 focus:ring-offset-blue-50',
    warning: 'bg-yellow-50 text-yellow-500 hover:bg-yellow-100 focus:ring-yellow-600 focus:ring-offset-yellow-50',
    error: 'bg-red-50 text-red-500 hover:bg-red-100 focus:ring-red-600 focus:ring-offset-red-50',
    success: 'bg-green-50 text-green-500 hover:bg-green-100 focus:ring-green-600 focus:ring-offset-green-50'
  }
  return classes[props.type]
})
</script>

<!-- CLAUDE-CHECKPOINT -->
