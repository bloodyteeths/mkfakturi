import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

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
      // Get CSRF cookie first
      await axios.get('/sanctum/csrf-cookie')

      // Actual login API call
      const response = await axios.post('/api/v1/auth/login', {
        username: credentials.email,
        password: credentials.password,
        remember: credentials.remember || false
      })

      if (response.data && response.data.type === 'Success') {
        // Load the current user data
        await loadCurrentUser()
        return { success: true }
      } else {
        return {
          success: false,
          error: response.data?.message || 'Грешка при најавување'
        }
      }
    } catch (error) {
      console.error('Login error:', error)
      const message = error.response?.data?.message ||
                     error.response?.data?.error ||
                     'Неточни податоци за најава'
      return { success: false, error: message }
    } finally {
      isLoading.value = false
    }
  }

  const logout = async () => {
    try {
      await axios.post('/api/v1/auth/logout')
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
      const response = await axios.get('/api/v1/bootstrap')

      if (response.data && response.data.current_user) {
        const user = response.data.current_user
        currentUser.value = {
          id: user.id,
          name: user.name,
          email: user.email,
          is_partner: user.role === 'partner',
          partner_id: user.partner_id || null,
          commission_rate: user.commission_rate || 0
        }
        isAuthenticated.value = true
      }
    } catch (error) {
      console.error('Error loading current user:', error)
      isAuthenticated.value = false
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
// CLAUDE-CHECKPOINT
