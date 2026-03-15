<template>
  <div class="flex flex-col sm:flex-row sm:flex-wrap sm:justify-between gap-2 sm:gap-0">
    <div>
      <h3 class="text-xl sm:text-2xl font-bold text-left text-black">
        {{ title }}
      </h3>
      <slot />
    </div>
    <div class="flex items-center flex-wrap gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: {
    type: [String],
    default: '',
    required: true,
  },
})
</script>
