<template>
  <router-link
    v-if="!loading"
    class="
      relative
      flex
      flex-col
      sm:flex-row
      sm:justify-between
      p-3
      bg-white
      rounded
      shadow
      hover:bg-gray-50
      focus:outline-none
      focus:ring-2
      focus:ring-primary-500
      focus:ring-opacity-50
      transition-all
      duration-200
      xl:p-4
      lg:col-span-2
      min-h-[80px]
      sm:min-h-[100px]
    "
    :class="{ 'lg:!col-span-3': large }"
    :to="route"
    :aria-label="`${label}: ${$slots.default?.()[0]?.children || 'View details'}`"
    role="button"
    tabindex="0"
  >
    <div class="flex-1">
      <span 
        class="text-lg font-semibold leading-tight text-black sm:text-xl xl:text-3xl"
        aria-hidden="true"
      >
        <slot />
      </span>
      <span 
        class="block mt-1 text-xs leading-tight text-gray-500 sm:text-sm xl:text-lg"
        aria-hidden="true"
      >
        {{ label }}
      </span>
    </div>
    <div class="flex items-center justify-end mt-2 sm:mt-0 sm:ml-4" aria-hidden="true">
      <component 
        :is="iconComponent" 
        class="w-8 h-8 sm:w-10 sm:h-10 xl:w-12 xl:h-12 text-primary-500"
        aria-hidden="true"
      />
    </div>
  </router-link>

  <StatsCardPlaceholder v-else-if="large" />

  <StatsCardSmPlaceholder v-else />
</template>


<script setup>
import StatsCardPlaceholder from './DashboardStatsPlaceholder.vue'
import StatsCardSmPlaceholder from './DashboardStatsSmPlaceholder.vue'

defineProps({
  iconComponent: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
  route: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  large: {
    type: Boolean,
    default: false,
  },
})
</script>

