<template>
  <img :src="logoUrl" :alt="altText" />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  darkColor: {
    type: String,
    default: 'rgba(var(--color-primary-500), var(--tw-text-opacity))',
  },
  lightColor: {
    type: String,
    default: 'rgba(var(--color-primary-400), var(--tw-text-opacity))',
  },
  variant: {
    type: String,
    default: 'clear', // 'clear' or 'standard'
  },
  altText: {
    type: String,
    default: 'Facturino Logo',
  },
})

const logoUrl = computed(() => {
  const logoName = props.variant === 'clear' ? 'facturino_logo_clear.png' : 'facturino_logo.png'
  return `/${logoName}`
})
// CLAUDE-CHECKPOINT
</script>
