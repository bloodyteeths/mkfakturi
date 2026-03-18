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

  // Modal type: 'limit_exceeded' | 'partner_limit_exceeded' | 'view_only_mode'
  const modalType = ref('limit_exceeded')

  // Modal data from limit_exceeded response
  const modalData = ref({
    feature: '',
    featureName: '',
    currentTier: 'free',
    requiredTier: 'starter',
    usage: null,
    message: '',
    upgradeUrl: '/admin/pricing',
    canUseAiChat: false,
  })

  /**
   * Show the upgrade modal with limit exceeded data
   * @param {Object} response - The API error response data
   */
  function showLimitExceeded(response) {
    modalType.value = 'limit_exceeded'
    modalData.value = {
      feature: response.feature || '',
      featureName: response.feature_name || response.featureName || 'This Feature',
      currentTier: response.current_tier || response.currentTier || 'free',
      requiredTier: response.required_tier || response.requiredTier || 'starter',
      usage: response.usage || null,
      message: response.message || 'You\'ve reached your limit. Upgrade to continue.',
      upgradeUrl: response.upgrade_url || '/admin/pricing',
    }
    showModal.value = true
  }

  /**
   * Show partner limit exceeded modal
   */
  function showPartnerLimitExceeded(response) {
    modalType.value = 'partner_limit_exceeded'
    modalData.value = {
      feature: response.meter || '',
      featureName: response.meter_name || 'This Feature',
      currentTier: response.current_tier || 'free',
      requiredTier: response.upgrade_tier || 'office',
      usage: response.usage || null,
      message: response.message || 'You\'ve reached your partner limit. Upgrade to continue.',
      upgradeUrl: response.upgrade_url || '/partner/billing',
    }
    showModal.value = true
  }

  /**
   * Show view-only mode notice
   */
  function showViewOnlyMode(response) {
    modalType.value = 'view_only_mode'
    modalData.value = {
      message: response.message || 'This company is in view-only mode.',
      canUseAiChat: response.can_use_ai_chat || false,
      upgradeUrl: response.upgrade_url || '/admin/pricing',
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
    modalType.value = 'limit_exceeded'
    modalData.value = {
      feature: '',
      featureName: '',
      currentTier: 'free',
      requiredTier: 'starter',
      usage: null,
      message: '',
      upgradeUrl: '/admin/pricing',
      canUseAiChat: false,
    }
  }

  return {
    showModal,
    modalType,
    modalData,
    showLimitExceeded,
    showPartnerLimitExceeded,
    showViewOnlyMode,
    closeModal,
    resetModal,
  }
})
