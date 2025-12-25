/**
 * Barcode Scanner Composable
 *
 * Integrates @programic/vue-barcode-detector with item lookup
 * to provide seamless USB/Bluetooth barcode scanner support.
 *
 * Usage:
 *   const { isEnabled, toggleScanner, lastScannedItem, error } = useBarcodeScanner({
 *     onItemFound: (item) => addItemToInvoice(item),
 *     onError: (err) => showNotification(err)
 *   })
 */

import { ref, computed, onUnmounted } from 'vue'
import useBarcodeDetector from '@programic/vue-barcode-detector'
import { useItemStore } from '@/scripts/admin/stores/item'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useI18n } from 'vue-i18n'

export function useBarcodeScanner(options = {}) {
  const {
    onItemFound = null,
    onError = null,
    autoEnable = false,
    playSound = true
  } = options

  const itemStore = useItemStore()
  const notificationStore = useNotificationStore()
  const { t } = useI18n()

  // State
  const isEnabled = ref(false)
  const isProcessing = ref(false)
  const lastScannedBarcode = ref(null)
  const lastScannedItem = ref(null)
  const error = ref(null)
  const scanCount = ref(0)

  // Barcode detector from @programic/vue-barcode-detector
  const barcodeDetector = useBarcodeDetector()

  // Audio feedback
  const successSound = ref(null)
  const errorSound = ref(null)

  // Initialize audio (lazy load)
  function initAudio() {
    if (playSound && !successSound.value) {
      try {
        // Create simple beep sounds using Web Audio API
        successSound.value = createBeepSound(800, 0.1) // High pitch, short
        errorSound.value = createBeepSound(300, 0.2)   // Low pitch, longer
      } catch (e) {
        console.warn('Audio feedback not available:', e)
      }
    }
  }

  // Create a simple beep sound
  function createBeepSound(frequency, duration) {
    return () => {
      try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)()
        const oscillator = audioContext.createOscillator()
        const gainNode = audioContext.createGain()

        oscillator.connect(gainNode)
        gainNode.connect(audioContext.destination)

        oscillator.frequency.value = frequency
        oscillator.type = 'sine'

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration)

        oscillator.start(audioContext.currentTime)
        oscillator.stop(audioContext.currentTime + duration)
      } catch (e) {
        // Ignore audio errors
      }
    }
  }

  // Play success sound
  function playSuccessSound() {
    if (playSound && successSound.value) {
      successSound.value()
    }
  }

  // Play error sound
  function playErrorSound() {
    if (playSound && errorSound.value) {
      errorSound.value()
    }
  }

  // Handle barcode scan
  async function handleBarcodeScan(barcodeData) {
    if (isProcessing.value) return

    const barcode = barcodeData.value?.trim()
    if (!barcode) return

    isProcessing.value = true
    error.value = null
    lastScannedBarcode.value = barcode

    try {
      // Look up item by barcode
      const item = await itemStore.lookupByBarcode(barcode)

      if (item) {
        lastScannedItem.value = item
        scanCount.value++
        playSuccessSound()

        // Call the callback if provided
        if (onItemFound && typeof onItemFound === 'function') {
          onItemFound(item, barcode)
        }

        // Show success notification
        notificationStore.showNotification({
          type: 'success',
          message: t('stock.barcode_item_found', { name: item.name })
        })
      }
    } catch (err) {
      lastScannedItem.value = null

      if (err.response?.status === 404) {
        // Barcode not found
        error.value = t('stock.barcode_not_found', { barcode })
        playErrorSound()

        notificationStore.showNotification({
          type: 'warning',
          message: t('stock.barcode_not_found', { barcode })
        })
      } else {
        // Other error
        error.value = t('stock.barcode_lookup_error')
        playErrorSound()

        notificationStore.showNotification({
          type: 'error',
          message: t('stock.barcode_lookup_error')
        })
      }

      // Call error callback if provided
      if (onError && typeof onError === 'function') {
        onError(err, barcode)
      }
    } finally {
      isProcessing.value = false
    }
  }

  // Enable scanner mode
  function enableScanner() {
    if (isEnabled.value) return

    initAudio()
    barcodeDetector.listen(handleBarcodeScan)
    isEnabled.value = true

    notificationStore.showNotification({
      type: 'info',
      message: t('stock.scanner_mode_enabled')
    })
  }

  // Disable scanner mode
  function disableScanner() {
    if (!isEnabled.value) return

    barcodeDetector.stopListening()
    isEnabled.value = false

    notificationStore.showNotification({
      type: 'info',
      message: t('stock.scanner_mode_disabled')
    })
  }

  // Toggle scanner mode
  function toggleScanner() {
    if (isEnabled.value) {
      disableScanner()
    } else {
      enableScanner()
    }
  }

  // Reset state
  function reset() {
    lastScannedBarcode.value = null
    lastScannedItem.value = null
    error.value = null
    scanCount.value = 0
  }

  // Auto-enable if option is set
  if (autoEnable) {
    enableScanner()
  }

  // Cleanup on unmount
  onUnmounted(() => {
    if (isEnabled.value) {
      barcodeDetector.stopListening()
    }
  })

  return {
    // State
    isEnabled,
    isProcessing,
    lastScannedBarcode,
    lastScannedItem,
    error,
    scanCount,

    // Reactive barcode from detector
    currentBarcode: barcodeDetector.barcode,

    // Actions
    enableScanner,
    disableScanner,
    toggleScanner,
    reset,

    // Manual lookup (for testing or manual input)
    lookupBarcode: handleBarcodeScan
  }
}

export default useBarcodeScanner
