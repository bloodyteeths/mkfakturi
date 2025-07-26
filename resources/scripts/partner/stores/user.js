import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUserStore = defineStore('partnerUser', () => {
  // State
  const currentUser = ref({
    id: null,
    name: '',
    email: '',
    is_partner: false,
    partner_id: null,
    commission_rate: 0
  })
  
  const isAuthenticated = ref(false)
  const isLoading = ref(false)

  // Actions
  const login = async (credentials) => {
    isLoading.value = true
    try {
      // Mock login - replace with actual API call
      currentUser.value = {
        id: 1,
        name: 'Марко Петровски',
        email: credentials.email,
        is_partner: true,
        partner_id: 1,
        commission_rate: 15.0
      }
      isAuthenticated.value = true
      return { success: true }
    } catch (error) {
      console.error('Login error:', error)
      return { success: false, error: error.message }
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    try {
      // Mock logout - replace with actual API call
      currentUser.value = {
        id: null,
        name: '',
        email: '',
        is_partner: false,
        partner_id: null,
        commission_rate: 0
      }
      isAuthenticated.value = false
    } catch (error) {
      console.error('Logout error:', error)
    }
  }

  const loadCurrentUser = async () => {
    isLoading.value = true
    try {
      // Mock current user load - replace with actual API call
      // This would typically fetch from /api/partner/user or similar
      if (localStorage.getItem('partner_token')) {
        currentUser.value = {
          id: 1,
          name: 'Марко Петровски',
          email: 'marko.petrovski@email.com',
          is_partner: true,
          partner_id: 1,
          commission_rate: 15.0
        }
        isAuthenticated.value = true
      }
    } catch (error) {
      console.error('Error loading current user:', error)
    } finally {
      isLoading.value = false
    }
  }

  const hasAbilities = (abilities) => {
    // For partners, we can define specific abilities
    const partnerAbilities = [
      'view-dashboard',
      'view-commissions', 
      'view-clients',
      'manage-profile'
    ]
    
    if (Array.isArray(abilities)) {
      return abilities.every(ability => partnerAbilities.includes(ability))
    }
    
    return partnerAbilities.includes(abilities)
  }

  return {
    // State
    currentUser,
    isAuthenticated,
    isLoading,
    
    // Actions
    login,
    logout,
    loadCurrentUser,
    hasAbilities
  }
})