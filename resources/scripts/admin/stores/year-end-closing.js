import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useYearEndClosingStore = defineStore('yearEndClosing', () => {
  // State
  const currentStep = ref(1)
  const year = ref(new Date().getFullYear() - 1) // Default to previous year
  const isLoading = ref(false)

  // Step data
  const preflightData = ref(null)
  const summaryData = ref(null)
  const closingPreview = ref(null)
  const closingResult = ref(null)
  const fiscalYearStatus = ref(null)
  const lastError = ref(null)
  const accounts = ref([])

  // Computed
  const totalSteps = computed(() => 6)
  const canProceed = computed(() => {
    if (currentStep.value === 1 && preflightData.value) {
      return preflightData.value.can_proceed
    }
    return true
  })

  const stepLabels = computed(() => [
    { num: 1, key: 'partner.accounting.year_end.step_checklist' },
    { num: 2, key: 'partner.accounting.year_end.step_review' },
    { num: 3, key: 'partner.accounting.year_end.step_adjust' },
    { num: 4, key: 'partner.accounting.year_end.step_close' },
    { num: 5, key: 'partner.accounting.year_end.step_reports' },
    { num: 6, key: 'partner.accounting.year_end.step_done' },
  ])

  // Actions
  async function fetchPreflight() {
    isLoading.value = true
    lastError.value = null
    try {
      const { data } = await axios.get(`/year-end/${year.value}/preflight`)
      preflightData.value = data
      return data
    } catch (error) {
      lastError.value = error.response?.data?.error || error.message || 'Failed to load preflight checks'
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchSummary() {
    isLoading.value = true
    lastError.value = null
    try {
      const { data } = await axios.get(`/year-end/${year.value}/summary`)
      summaryData.value = data
      return data
    } catch (error) {
      lastError.value = error.response?.data?.error || error.message || 'Failed to load financial summary'
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchClosingPreview() {
    isLoading.value = true
    lastError.value = null
    try {
      const { data } = await axios.post(`/year-end/${year.value}/closing`, { mode: 'preview' })
      closingPreview.value = data
      return data
    } catch (error) {
      lastError.value = error.response?.data?.error || error.message || 'Failed to generate closing preview'
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function commitClosingEntries() {
    isLoading.value = true
    try {
      const { data } = await axios.post(`/year-end/${year.value}/closing`, { mode: 'commit' })
      closingResult.value = data
      return data
    } finally {
      isLoading.value = false
    }
  }

  async function fetchAccounts() {
    try {
      const { data } = await axios.get('/accounting/accounts')
      accounts.value = data.data || data || []
      return accounts.value
    } catch {
      accounts.value = []
    }
  }

  async function fetchTaxSummary() {
    const { data } = await axios.get(`/year-end/${year.value}/reports/tax-summary`)
    return data
  }

  async function finalize() {
    isLoading.value = true
    try {
      const { data } = await axios.post(`/year-end/${year.value}/finalize`)
      fiscalYearStatus.value = 'CLOSED'
      return data
    } finally {
      isLoading.value = false
    }
  }

  async function undoClosing() {
    isLoading.value = true
    try {
      const { data } = await axios.post(`/year-end/${year.value}/undo`)
      fiscalYearStatus.value = 'OPEN'
      return data
    } finally {
      isLoading.value = false
    }
  }

  function nextStep() {
    if (currentStep.value < totalSteps.value) {
      currentStep.value++
    }
  }

  function prevStep() {
    if (currentStep.value > 1) {
      currentStep.value--
    }
  }

  function goToStep(step) {
    if (step >= 1 && step <= totalSteps.value) {
      currentStep.value = step
    }
  }

  function reset() {
    currentStep.value = 1
    preflightData.value = null
    summaryData.value = null
    closingPreview.value = null
    closingResult.value = null
    fiscalYearStatus.value = null
    lastError.value = null
    accounts.value = []
  }

  return {
    // State
    currentStep,
    year,
    isLoading,
    preflightData,
    summaryData,
    closingPreview,
    closingResult,
    fiscalYearStatus,
    lastError,
    accounts,
    // Computed
    totalSteps,
    canProceed,
    stepLabels,
    // Actions
    fetchPreflight,
    fetchSummary,
    fetchClosingPreview,
    commitClosingEntries,
    fetchAccounts,
    fetchTaxSummary,
    finalize,
    undoClosing,
    nextStep,
    prevStep,
    goToStep,
    reset,
  }
})
// CLAUDE-CHECKPOINT
