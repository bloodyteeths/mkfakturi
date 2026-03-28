<template>
  <div class="px-4 py-3 bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-800 shrink-0">
    <div class="relative group">
      <!-- Search icon -->
      <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 dark:text-gray-600 group-focus-within:text-primary-500 transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </div>

      <input
        ref="inputRef"
        :value="modelValue"
        type="text"
        :placeholder="t('pos.search_or_scan') || 'Search or scan barcode...'"
        class="w-full pl-12 pr-20 py-3 bg-gray-50 dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-700 rounded-xl text-sm font-medium text-gray-800 dark:text-gray-200 placeholder-gray-300 dark:placeholder-gray-600 focus:border-primary-400 dark:focus:border-primary-600 focus:ring-2 focus:ring-primary-500/20 focus:bg-white dark:focus:bg-gray-800 outline-none transition-all"
        @input="$emit('update:modelValue', $event.target.value)"
        @keydown.enter.prevent="handleEnter"
      />

      <!-- Camera toggle button (only when barcode_camera setting is on) -->
      <button
        v-if="barcodeCameraEnabled"
        type="button"
        class="absolute right-10 top-1/2 -translate-y-1/2 p-1 rounded-md transition-colors"
        :class="cameraActive
          ? 'text-primary-500 bg-primary-50 dark:bg-primary-900/30'
          : 'text-gray-300 dark:text-gray-600 hover:text-gray-500 dark:hover:text-gray-400'"
        :title="cameraActive ? t('pos.camera_stop') || 'Stop camera' : t('pos.camera_start') || 'Scan with camera'"
        @click="toggleCamera"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>

      <!-- Keyboard shortcut hint -->
      <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-mono font-bold text-gray-300 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
        F1
      </div>
    </div>

    <!-- Camera preview -->
    <div
      v-if="cameraActive"
      class="mt-2 relative rounded-lg overflow-hidden bg-black"
      style="width: 200px; height: 150px;"
    >
      <video
        ref="videoRef"
        autoplay
        playsinline
        muted
        class="w-full h-full object-cover"
      />
      <!-- Scanning overlay -->
      <div class="absolute inset-0 border-2 border-primary-400 rounded-lg pointer-events-none">
        <div class="absolute top-1/2 left-2 right-2 h-0.5 bg-primary-400 opacity-60 animate-pulse" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  modelValue: { type: String, default: '' },
  barcodeCameraEnabled: { type: Boolean, default: false },
})

const emit = defineEmits(['update:modelValue', 'barcode'])

const inputRef = ref(null)
const videoRef = ref(null)
const cameraActive = ref(false)
let mediaStream = null
let detectionInterval = null

function handleEnter() {
  const val = inputRef.value?.value?.trim()
  if (val) {
    emit('barcode', val)
    emit('update:modelValue', '')
    if (inputRef.value) inputRef.value.value = ''
  }
}

async function startCamera() {
  try {
    mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
    })
    // Wait for the video element to be rendered
    await new Promise(resolve => setTimeout(resolve, 50))
    if (videoRef.value) {
      videoRef.value.srcObject = mediaStream
    }
    startDetection()
  } catch (err) {
    console.error('[POS Camera] Failed to start camera:', err)
    cameraActive.value = false
  }
}

function stopCamera() {
  stopDetection()
  if (mediaStream) {
    mediaStream.getTracks().forEach(track => track.stop())
    mediaStream = null
  }
  if (videoRef.value) {
    videoRef.value.srcObject = null
  }
}

function startDetection() {
  // Use the native BarcodeDetector API if available
  if (!('BarcodeDetector' in window)) {
    console.warn('[POS Camera] BarcodeDetector API not available in this browser')
    return
  }

  const detector = new window.BarcodeDetector({
    formats: ['ean_13', 'ean_8', 'code_128', 'code_39', 'upc_a', 'upc_e', 'qr_code']
  })

  let lastDetectedCode = ''
  let lastDetectedTime = 0

  detectionInterval = setInterval(async () => {
    if (!videoRef.value || videoRef.value.readyState < 2) return

    try {
      const barcodes = await detector.detect(videoRef.value)
      if (barcodes.length > 0) {
        const code = barcodes[0].rawValue
        const now = Date.now()

        // Debounce: ignore same barcode within 2 seconds
        if (code === lastDetectedCode && now - lastDetectedTime < 2000) return

        lastDetectedCode = code
        lastDetectedTime = now

        emit('barcode', code)
      }
    } catch (err) {
      // Detection frame errors are normal, ignore silently
    }
  }, 250)
}

function stopDetection() {
  if (detectionInterval) {
    clearInterval(detectionInterval)
    detectionInterval = null
  }
}

function toggleCamera() {
  if (cameraActive.value) {
    stopCamera()
    cameraActive.value = false
  } else {
    cameraActive.value = true
    startCamera()
  }
}

// Stop camera if setting is disabled while active
watch(() => props.barcodeCameraEnabled, (enabled) => {
  if (!enabled && cameraActive.value) {
    stopCamera()
    cameraActive.value = false
  }
})

function focus() {
  inputRef.value?.focus()
}

onUnmounted(() => {
  stopCamera()
})

defineExpose({ focus })
</script>

<!-- CLAUDE-CHECKPOINT -->
