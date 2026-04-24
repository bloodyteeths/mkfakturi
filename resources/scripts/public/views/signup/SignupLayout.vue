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
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
        <!-- Signup Form -->
        <div class="lg:col-span-3">
          <router-view />
        </div>

        <!-- Trust Sidebar -->
        <div class="lg:col-span-2 hidden lg:block">
          <div class="bg-white rounded-lg shadow-lg p-6 sticky top-24">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              Зошто Facturino?
            </h3>

            <div class="space-y-4">
              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Е-Фактура со QES потпис</p>
                  <p class="text-xs text-gray-500">Директно испраќање до УЈП</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">PSD2 банкарска врска</p>
                  <p class="text-xs text-gray-500">Автоматски изводи од NLB, Стопанска, Комерцијална</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">МПИН плати со еден клик</p>
                  <p class="text-xs text-gray-500">Автоматско пресметување на придонеси и данок</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <div class="flex-shrink-0 w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                  <svg class="w-4 h-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">AI финансиски асистент</p>
                  <p class="text-xs text-gray-500">Прашајте на македонски: „Кој ми должи пари?"</p>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-100">
              <div class="flex items-center gap-2 text-sm text-gray-600">
                <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                Без кредитна картичка. Без обврска.
              </div>
              <div class="flex items-center gap-2 text-sm text-gray-600 mt-2">
                <svg class="w-4 h-4 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                14 дена бесплатен пристап до сите функции
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="text-center text-sm text-gray-600">
        <p>
          Авторски права &copy; Facturino ДООЕЛ. {{ new Date().getFullYear() }}
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

