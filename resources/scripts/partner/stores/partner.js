import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const usePartnerStore = defineStore('partner', () => {
  // State
  const dashboardStats = ref({
    activeClients: 0,
    monthlyCommissions: 0,
    processedInvoices: 0,
    currentProjects: 0
  })

  const recentCommissions = ref([])
  const recentActivities = ref([])
  const clients = ref([])
  const isLoading = ref(false)
  const isMocked = ref(false)
  const mockWarning = ref('')

  // Actions
  const loadDashboardStats = async () => {
    isLoading.value = true
    try {
      const { data } = await axios.get('/api/v1/partner/dashboard')

      // Check if data is mocked
      if (data.mocked) {
        console.warn('⚠️ Partner portal using mocked data')
        isMocked.value = true
        mockWarning.value = data.warning || 'Using mocked data for safety'
        dashboardStats.value = {
          activeClients: data.data.active_clients,
          monthlyCommissions: data.data.monthly_commissions,
          processedInvoices: data.data.processed_invoices,
          currentProjects: 0  // Not provided by API
        }
      } else {
        isMocked.value = false
        mockWarning.value = ''
        dashboardStats.value = {
          activeClients: data.data.active_clients,
          monthlyCommissions: data.data.monthly_commissions,
          processedInvoices: data.data.processed_invoices,
          currentProjects: 0  // Not provided by API
        }
      }
    } catch (error) {
      console.error('Error loading dashboard stats:', error)
      // Fall back to empty state on error
      dashboardStats.value = {
        activeClients: 0,
        monthlyCommissions: 0,
        processedInvoices: 0,
        currentProjects: 0
      }
    } finally {
      isLoading.value = false
    }
  }

  const loadRecentCommissions = async () => {
    try {
      const { data} = await axios.get('/api/v1/partner/commissions')

      if (data.mocked) {
        console.warn('⚠️ Partner commissions using mocked data')
        recentCommissions.value = data.data || []
      } else {
        recentCommissions.value = data.data || []
      }
    } catch (error) {
      console.error('Error loading recent commissions:', error)
      recentCommissions.value = []
    }
  }

  const loadClients = async () => {
    try {
      const { data } = await axios.get('/partner/clients')

      if (data.mocked) {
        console.warn('⚠️ Partner clients using mocked data')
        clients.value = data.data || []
      } else {
        clients.value = data.data || []
      }
    } catch (error) {
      console.error('Error loading clients:', error)
      clients.value = []
    }
  }

  const loadRecentActivities = async () => {
    try {
      // Mock data - replace with actual API call
      recentActivities.value = [
        {
          id: 1,
          description: 'Обработена фактура #INV-2025-001 за ТехноСофт ДОО',
          created_at: new Date().toISOString()
        },
        {
          id: 2,
          description: 'Додаден нов клиент: МедТрејд ДООЕЛ',
          created_at: new Date(Date.now() - 2 * 60 * 60 * 1000).toISOString()
        },
        {
          id: 3,
          description: 'Месечен извештај за јануари е генериран',
          created_at: new Date(Date.now() - 5 * 60 * 60 * 1000).toISOString()
        }
      ]
    } catch (error) {
      console.error('Error loading recent activities:', error)
    }
  }

  return {
    // State
    dashboardStats,
    recentCommissions,
    recentActivities,
    clients,
    isLoading,
    isMocked,
    mockWarning,

    // Actions
    loadDashboardStats,
    loadRecentCommissions,
    loadRecentActivities,
    loadClients
  }
})

// CLAUDE-CHECKPOINT