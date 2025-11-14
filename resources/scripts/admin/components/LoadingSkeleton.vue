<template>
  <div class="animate-pulse">
    <!-- Table Variant -->
    <div v-if="variant === 'table'">
      <div
        v-for="i in rows"
        :key="i"
        class="h-12 bg-gray-200 rounded mb-3"
      ></div>
    </div>

    <!-- Card Variant -->
    <div v-else-if="variant === 'card'" class="space-y-4">
      <div
        v-for="i in rows"
        :key="i"
        class="p-4 border rounded-lg bg-white shadow-sm"
      >
        <div class="flex justify-between items-start mb-3">
          <div class="flex-1">
            <div class="h-6 bg-gray-200 rounded w-1/3 mb-2"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
          </div>
          <div class="h-6 w-20 bg-gray-200 rounded"></div>
        </div>
        <div class="space-y-2 mb-4">
          <div class="h-4 bg-gray-200 rounded"></div>
          <div class="h-4 bg-gray-200 rounded"></div>
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        </div>
        <div class="flex gap-2">
          <div class="h-10 bg-gray-200 rounded flex-1"></div>
          <div class="h-10 bg-gray-200 rounded flex-1"></div>
        </div>
      </div>
    </div>

    <!-- List Variant -->
    <div v-else-if="variant === 'list'" class="space-y-3">
      <div
        v-for="i in rows"
        :key="i"
        class="flex items-center p-3 border rounded-lg"
      >
        <div class="h-10 w-10 bg-gray-200 rounded-full mr-3"></div>
        <div class="flex-1">
          <div class="h-4 bg-gray-200 rounded w-2/3 mb-2"></div>
          <div class="h-3 bg-gray-200 rounded w-1/2"></div>
        </div>
      </div>
    </div>

    <!-- Form Variant -->
    <div v-else-if="variant === 'form'" class="space-y-4">
      <div v-for="i in rows" :key="i">
        <div class="h-4 bg-gray-200 rounded w-1/4 mb-2"></div>
        <div class="h-10 bg-gray-200 rounded w-full"></div>
      </div>
    </div>

    <!-- Dashboard Widget Variant -->
    <div v-else-if="variant === 'widget'" class="p-6 bg-white rounded-lg shadow">
      <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
      <div class="space-y-3">
        <div class="h-4 bg-gray-200 rounded"></div>
        <div class="h-4 bg-gray-200 rounded w-5/6"></div>
        <div class="h-4 bg-gray-200 rounded w-4/6"></div>
      </div>
    </div>

    <!-- Chart Variant -->
    <div v-else-if="variant === 'chart'" class="p-6 bg-white rounded-lg shadow">
      <div class="h-6 bg-gray-200 rounded w-1/4 mb-6"></div>
      <div class="flex items-end justify-between h-48 space-x-2">
        <div
          v-for="i in 12"
          :key="i"
          class="flex-1 bg-gray-200 rounded-t"
          :style="{ height: `${Math.random() * 100}%` }"
        ></div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  variant: {
    type: String,
    default: 'table',
    validator: (value) =>
      ['table', 'card', 'list', 'form', 'widget', 'chart'].includes(value),
  },
  rows: {
    type: Number,
    default: 5,
  },
})
</script>

<style scoped>
@keyframes shimmer {
  0% {
    background-position: -1000px 0;
  }
  100% {
    background-position: 1000px 0;
  }
}

.animate-pulse > * {
  animation: shimmer 2s infinite linear;
  background: linear-gradient(
    to right,
    #f3f4f6 0%,
    #e5e7eb 20%,
    #f3f4f6 40%,
    #f3f4f6 100%
  );
  background-size: 2000px 100%;
}
</style>

// CLAUDE-CHECKPOINT
