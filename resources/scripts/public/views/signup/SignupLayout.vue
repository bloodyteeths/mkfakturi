<template>
  <div class="signup-layout min-h-screen bg-gradient-to-br from-primary-50 to-primary-100 overflow-y-auto">
    <NotificationRoot />

    <!-- Header with Logo -->
    <div class="w-full bg-white shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <MainLogo class="h-10 w-auto" variant="clear" />
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <router-view />
    </div>

    <!-- Footer -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center text-sm text-gray-600">
        <p>
          Авторски права &copy; Факторино ДООЕЛ. {{ new Date().getFullYear() }}
        </p>
        <p class="mt-2">
          <a href="/terms" class="text-primary-600 hover:text-primary-700">Услови за користење</a>
          <span class="mx-2">•</span>
          <a href="/privacy" class="text-primary-600 hover:text-primary-700">Политика на приватност</a>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'

// Override body overflow on mount
onMounted(() => {
  document.body.style.overflow = 'auto'
  document.body.style.height = 'auto'
})

onUnmounted(() => {
  document.body.style.overflow = ''
  document.body.style.height = ''
})
</script>

<style scoped>
.signup-layout {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow-y: auto;
}
</style>

// CLAUDE-CHECKPOINT
