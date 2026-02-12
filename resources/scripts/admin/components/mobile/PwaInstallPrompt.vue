<template>
  <Transition name="slide-up">
    <div
      v-if="showPrompt"
      data-cy="pwa-install"
      class="fixed bottom-0 inset-x-0 z-50 p-4 bg-white border-t border-gray-200 shadow-lg md:hidden"
    >
      <div class="flex items-center justify-between max-w-lg mx-auto">
        <div class="flex items-center space-x-3 min-w-0">
          <img
            src="/favicons/android-chrome-192x192.png"
            alt="Facturino"
            class="w-10 h-10 rounded-lg flex-shrink-0"
          />
          <div class="min-w-0">
            <p class="text-sm font-medium text-gray-900 truncate">
              Инсталирај Facturino
            </p>
            <p class="text-xs text-gray-500">
              Додај на почетен екран
            </p>
          </div>
        </div>
        <div class="flex items-center space-x-2 flex-shrink-0">
          <button
            class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 min-h-[44px] min-w-[44px]"
            @click="dismiss"
          >
            Не сега
          </button>
          <button
            class="px-4 py-2 text-sm font-medium text-white bg-gray-800 rounded-lg hover:bg-gray-700 min-h-[44px]"
            @click="install"
          >
            Инсталирај
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'

const showPrompt = ref(false)
let deferredPrompt = null

function handleBeforeInstallPrompt(event) {
  event.preventDefault()
  deferredPrompt = event

  // Don't show if user dismissed before
  const dismissed = localStorage.getItem('pwa-install-dismissed')
  if (!dismissed) {
    showPrompt.value = true
  }
}

async function install() {
  if (!deferredPrompt) return

  deferredPrompt.prompt()
  const { outcome } = await deferredPrompt.userChoice
  deferredPrompt = null
  showPrompt.value = false

  if (outcome === 'accepted') {
    localStorage.setItem('pwa-installed', 'true')
  }
}

function dismiss() {
  showPrompt.value = false
  localStorage.setItem('pwa-install-dismissed', Date.now().toString())
}

onMounted(() => {
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)

  // Hide prompt if already installed
  window.addEventListener('appinstalled', () => {
    showPrompt.value = false
    localStorage.setItem('pwa-installed', 'true')
  })
})

onBeforeUnmount(() => {
  window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
}
</style>
