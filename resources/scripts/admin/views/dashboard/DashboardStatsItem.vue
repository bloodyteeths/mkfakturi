<template>
  <router-link
    v-if="!loading"
    class="
      relative
      flex
      flex-col
      sm:flex-row
      sm:justify-between
      p-2.5
      sm:p-3
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
    "
    :to="route"
    :aria-label="`${label}: ${$slots.default?.()[0]?.children || 'View details'}`"
    role="button"
    tabindex="0"
  >
    <div class="flex-1">
      <span 
        class="text-base font-semibold leading-tight text-black sm:text-lg xl:text-2xl"
        aria-hidden="true"
      >
        <slot />
      </span>
      <span 
        class="block mt-0.5 text-[11px] leading-tight text-gray-500 sm:text-xs xl:text-sm"
        aria-hidden="true"
      >
        {{ label }}
      </span>
    </div>
    <div class="flex items-center justify-end mt-2 sm:mt-0 sm:ml-4" aria-hidden="true">
      <component 
        :is="iconComponent" 
        class="w-6 h-6 sm:w-8 sm:h-8 xl:w-10 xl:h-10 text-primary-500"
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

