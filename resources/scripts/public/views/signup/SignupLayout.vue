<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-50 to-primary-100">
    <NotificationRoot />

    <!-- Header with Logo -->
    <div class="w-full bg-white shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <MainLogo class="h-12 w-auto" variant="clear" />
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import MainLogo from '@/scripts/components/icons/MainLogo.vue'
</script>

// CLAUDE-CHECKPOINT
