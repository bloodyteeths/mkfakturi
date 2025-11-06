<template>
  <img :src="logoUrl" :alt="altText" />
</template>

<script setup>
import { computed } from 'vue'
import clearLogo from '$images/facturino_logo_clear.png'
import standardLogo from '$images/facturino_logo.png'

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
  return props.variant === 'clear' ? clearLogo : standardLogo
})
// CLAUDE-CHECKPOINT
</script>
