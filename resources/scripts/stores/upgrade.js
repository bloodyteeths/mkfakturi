import { defineStore } from 'pinia'
import { ref } from 'vue'

/**
 * Upgrade Store
 *
 * Manages the global upgrade modal state for displaying tier upgrade prompts
 * when users hit feature limits.
 */
export const useUpgradeStore = defineStore('upgrade', () => {
  // Modal visibility
  const showModal = ref(false)

  // Modal data from limit_exceeded response
  const modalData = ref({
    feature: '',
    featureName: '',
    currentTier: 'free',
    requiredTier: 'starter',
    usage: null,
    message: '',
  })

  /**
   * Show the upgrade modal with limit exceeded data
   * @param {Object} response - The API error response data
   */
  function showLimitExceeded(response) {
    modalData.value = {
      feature: response.feature || '',
      featureName: response.feature_name || response.featureName || 'This Feature',
      currentTier: response.current_tier || response.currentTier || 'free',
      requiredTier: response.required_tier || response.requiredTier || 'starter',
      usage: response.usage || null,
      message: response.message || 'You\'ve reached your limit. Upgrade to continue.',
    }
    showModal.value = true
  }

  /**
   * Close the upgrade modal
   */
  function closeModal() {
    showModal.value = false
  }

  /**
   * Reset modal data
   */
  function resetModal() {
    showModal.value = false
    modalData.value = {
      feature: '',
      featureName: '',
      currentTier: 'free',
      requiredTier: 'starter',
      usage: null,
      message: '',
    }
  }

  return {
    showModal,
    modalData,
    showLimitExceeded,
    closeModal,
    resetModal,
  }
})
