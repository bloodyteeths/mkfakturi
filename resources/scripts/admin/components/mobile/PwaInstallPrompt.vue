<template>
  <!-- Android install prompt (beforeinstallprompt) -->
  <Transition name="slide-up">
    <div
      v-if="showPrompt && !isIos"
      data-cy="pwa-install"
      class="fixed bottom-0 inset-x-0 z-50 p-4 bg-white border-t border-gray-200 shadow-lg md:hidden"
    >
      <div class="flex items-center justify-between max-w-lg mx-auto">
        <div class="flex items-center space-x-3 min-w-0">
          <img
            src="/favicons/android-chrome-192x192.png?v=3"
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

  <!-- iOS Safari install instructions -->
  <Transition name="slide-up">
    <div
      v-if="showIosPrompt"
      data-cy="pwa-install-ios"
      class="fixed bottom-0 inset-x-0 z-50 p-4 bg-white border-t border-gray-200 shadow-lg md:hidden"
    >
      <div class="max-w-lg mx-auto">
        <div class="flex items-start space-x-3">
          <img
            src="/favicons/android-chrome-192x192.png?v=3"
            alt="Facturino"
            class="w-10 h-10 rounded-lg flex-shrink-0"
          />
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900">
              Инсталирај Facturino
            </p>
            <p class="text-xs text-gray-500 mt-1 leading-relaxed">
              Притисни
              <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4 text-blue-500 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
              </svg>
              потоа „Додај на почетен екран"
            </p>
          </div>
          <button
            class="p-2 text-gray-400 hover:text-gray-600 min-h-[44px] min-w-[44px] flex items-center justify-center flex-shrink-0"
            @click="dismissIos"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

const showPrompt = ref(false)
const showIosPrompt = ref(false)
let deferredPrompt = null

const isIos = computed(() => {
  return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream
})

const isStandalone = computed(() => {
  return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone
})

function handleBeforeInstallPrompt(event) {
  event.preventDefault()
  deferredPrompt = event

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

function dismissIos() {
  showIosPrompt.value = false
  localStorage.setItem('pwa-ios-dismissed', Date.now().toString())
}

function checkIosPrompt() {
  if (!isIos.value || isStandalone.value) return

  const dismissed = localStorage.getItem('pwa-ios-dismissed')
  if (dismissed) {
    // Re-show after 7 days
    const dismissedAt = parseInt(dismissed, 10)
    const sevenDays = 7 * 24 * 60 * 60 * 1000
    if (Date.now() - dismissedAt < sevenDays) return
  }

  // Delay showing to let user settle in
  setTimeout(() => {
    showIosPrompt.value = true
  }, 5000)
}

onMounted(() => {
  // Android/Chrome install prompt
  window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt)
  window.addEventListener('appinstalled', () => {
    showPrompt.value = false
    localStorage.setItem('pwa-installed', 'true')
  })

  // iOS Safari install guide
  checkIosPrompt()
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
