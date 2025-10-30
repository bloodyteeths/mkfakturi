<template>
  <router-view />

  <BaseDialog />
  
  <!-- In-app tour for new users -->
  <Tour
    :auto-start="true"
    @tour-completed="handleTourCompleted"
    @tour-skipped="handleTourSkipped"
  />
</template>

<script setup>
import Tour from '@/scripts/components/Tour.vue'

// Handle tour events
const handleTourCompleted = () => {
  console.log('User completed the tour')
  // Optional: Track analytics or show completion message
}

const handleTourSkipped = () => {
  console.log('User skipped the tour')
  // Optional: Track analytics
}
</script>
