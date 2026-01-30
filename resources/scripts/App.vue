<template>
  <router-view />

  <BaseDialog />

  <!-- In-app tour for new users - only show when authenticated and on dashboard -->
  <Tour
    v-if="showTour"
    :auto-start="true"
    @tour-completed="handleTourCompleted"
    @tour-skipped="handleTourSkipped"
  />
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import Tour from '@/scripts/components/Tour.vue'

const route = useRoute()
const userStore = useUserStore()

// Only show tour when user is authenticated and on the dashboard
const showTour = computed(() => {
  const isAuthenticated = !!userStore.currentUser?.id
  const isOnDashboard = route.path === '/admin/dashboard' || route.name === 'dashboard'
  return isAuthenticated && isOnDashboard
})

// Handle tour events
const handleTourCompleted = () => {
  console.log('User completed the tour')
}

const handleTourSkipped = () => {
  console.log('User skipped the tour')
}
</script>
