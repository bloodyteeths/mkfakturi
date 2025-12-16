import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const usePartnerStore = defineStore('partner', () => {
  // State
  const dashboardStats = ref({
    activeClients: 0,
    monthlyCommissions: 0,
    totalEarned: 0,
    pendingPayout: 0
  })

  const recentCommissions = ref([])
  const recentActivities = ref([])
  const clients = ref([])
  const isLoading = ref(false)

  // Stripe Connect State
  const stripeConnect = ref({
    connected: false,
    status: null, // 'pending', 'active', 'restricted', 'disabled', 'error'
    accountId: null,
    payoutsEnabled: false,
    detailsSubmitted: false,
    requirements: {
      currentlyDue: [],
      eventuallyDue: [],
      pastDue: []
    },
    error: null
  })
  const stripeConnectLoading = ref(false)

  // Actions
  const loadDashboardStats = async () => {
    isLoading.value = true
    try {
      const { data } = await axios.get('/partner/dashboard')

      // Update stats - API returns EUR amounts
      dashboardStats.value = {
        activeClients: data.data.active_clients || 0,
        monthlyCommissions: data.data.monthly_commissions || 0,
        totalEarned: data.data.total_earnings || 0,
        pendingPayout: data.data.pending_payout || 0
      }
    } catch (error) {
      console.error('Error loading dashboard stats:', error)
      // Fall back to empty state on error
      dashboardStats.value = {
        activeClients: 0,
        monthlyCommissions: 0,
        totalEarned: 0,
        pendingPayout: 0
      }
    } finally {
      isLoading.value = false
    }
  }

  const loadRecentCommissions = async () => {
    try {
      const { data } = await axios.get('/partner/commissions')
      recentCommissions.value = data.data || []
    } catch (error) {
      console.error('Error loading recent commissions:', error)
      recentCommissions.value = []
    }
  }

  const loadClients = async () => {
    try {
      const { data } = await axios.get('/partner/clients')
      clients.value = data.data || []
    } catch (error) {
      console.error('Error loading clients:', error)
      clients.value = []
    }
  }

  const loadRecentActivities = async () => {
    try {
      // TODO: Implement actual API call when endpoint is available
      recentActivities.value = []
    } catch (error) {
      console.error('Error loading recent activities:', error)
    }
  }

  // Stripe Connect Actions
  const loadStripeConnectStatus = async () => {
    stripeConnectLoading.value = true
    try {
      const { data } = await axios.get('/partner/stripe-connect/status')

      stripeConnect.value = {
        connected: data.connected,
        status: data.status,
        accountId: data.account_id,
        payoutsEnabled: data.payouts_enabled || false,
        detailsSubmitted: data.details_submitted || false,
        requirements: {
          currentlyDue: data.requirements?.currently_due || [],
          eventuallyDue: data.requirements?.eventually_due || [],
          pastDue: data.requirements?.past_due || []
        },
        error: data.error || null
      }
    } catch (error) {
      console.error('Error loading Stripe Connect status:', error)
      stripeConnect.value.error = error.response?.data?.error || 'Грешка при вчитување'
    } finally {
      stripeConnectLoading.value = false
    }
  }

  const createStripeAccount = async () => {
    stripeConnectLoading.value = true
    try {
      const { data } = await axios.post('/partner/stripe-connect/account')

      if (data.success) {
        stripeConnect.value.connected = true
        stripeConnect.value.accountId = data.account_id
        stripeConnect.value.status = 'pending'
        return { success: true }
      }
      return { success: false, error: data.error }
    } catch (error) {
      console.error('Error creating Stripe account:', error)
      const errorMsg = error.response?.data?.error || 'Грешка при креирање на сметка'
      stripeConnect.value.error = errorMsg
      return { success: false, error: errorMsg }
    } finally {
      stripeConnectLoading.value = false
    }
  }

  const getOnboardingLink = async () => {
    try {
      const { data } = await axios.post('/partner/stripe-connect/account-link')

      if (data.success && data.url) {
        return { success: true, url: data.url }
      }
      return { success: false, error: data.error }
    } catch (error) {
      console.error('Error getting onboarding link:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Грешка при креирање линк'
      }
    }
  }

  const getDashboardLink = async () => {
    try {
      const { data } = await axios.post('/partner/stripe-connect/dashboard-link')

      if (data.success && data.url) {
        return { success: true, url: data.url }
      }
      return { success: false, error: data.error }
    } catch (error) {
      console.error('Error getting dashboard link:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Грешка при креирање линк'
      }
    }
  }

  return {
    // State
    dashboardStats,
    recentCommissions,
    recentActivities,
    clients,
    isLoading,

    // Stripe Connect State
    stripeConnect,
    stripeConnectLoading,

    // Actions
    loadDashboardStats,
    loadRecentCommissions,
    loadRecentActivities,
    loadClients,

    // Stripe Connect Actions
    loadStripeConnectStatus,
    createStripeAccount,
    getOnboardingLink,
    getDashboardLink
  }
})

// CLAUDE-CHECKPOINT
