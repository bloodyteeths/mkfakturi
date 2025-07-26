import { defineStore } from 'pinia'
import { ref } from 'vue'

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
  const isLoading = ref(false)

  // Actions
  const loadDashboardStats = async () => {
    isLoading.value = true
    try {
      // Mock data for now - replace with actual API call
      dashboardStats.value = {
        activeClients: 12,
        monthlyCommissions: 85000,
        processedInvoices: 234,
        currentProjects: 8
      }
    } catch (error) {
      console.error('Error loading dashboard stats:', error)
    } finally {
      isLoading.value = false
    }
  }

  const loadRecentCommissions = async () => {
    try {
      // Mock data - replace with actual API call
      recentCommissions.value = [
        {
          id: 1,
          company_name: 'ТехноСофт ДОО',
          type: 'monthly',
          amount: 8500,
          status: 'paid'
        },
        {
          id: 2, 
          company_name: 'МедТрејд ДООЕЛ',
          type: 'invoice',
          amount: 2500,
          status: 'pending'
        },
        {
          id: 3,
          company_name: 'БизнисЦентар АД',
          type: 'monthly', 
          amount: 12000,
          status: 'approved'
        }
      ]
    } catch (error) {
      console.error('Error loading recent commissions:', error)
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
    isLoading,
    
    // Actions
    loadDashboardStats,
    loadRecentCommissions,
    loadRecentActivities
  }
})