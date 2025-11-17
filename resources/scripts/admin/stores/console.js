import { defineStore } from 'pinia'
import axios from 'axios'

export const useConsoleStore = defineStore('console', {
  state: () => ({
    // Partner information
    partner: null,
    
    // Companies managed by this partner
    companies: [],
    
    // Currently selected company
    currentCompany: null,
    
    // Loading states
    isLoading: false,
    isSwitching: false,
    
    // Error handling
    error: null,
    
    // UI state
    showCompanySwitcher: false,
  }),

  getters: {
    /**
     * Get primary company for this partner
     */
    primaryCompany: (state) => {
      return state.companies.find(company => company.is_primary) || null
    },

    /**
     * Get total number of companies
     */
    totalCompanies: (state) => {
      return state.companies.length
    },

    /**
     * Check if partner has multiple companies
     */
    hasMultipleCompanies: (state) => {
      return state.companies.length > 1
    },

    /**
     * Get companies sorted by primary first, then by name
     */
    sortedCompanies: (state) => {
      return [...state.companies].sort((a, b) => {
        // Primary company first
        if (a.is_primary && !b.is_primary) return -1
        if (!a.is_primary && b.is_primary) return 1
        // Then sort by name
        return a.name.localeCompare(b.name)
      })
    },
  },

  actions: {
    /**
     * Fetch companies for the authenticated partner
     */
    async fetchCompanies() {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get('/console/companies')

        this.partner = response.data.partner
        this.companies = response.data.companies
        
        // Set current company to primary if exists, or first company
        if (this.companies.length > 0) {
          this.currentCompany = this.primaryCompany || this.companies[0]
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch companies'
        console.error('Console store - fetchCompanies error:', error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Switch to a different company context
     */
    async switchCompany(companyId) {
      this.isSwitching = true
      this.error = null

      try {
        const response = await axios.post('/console/switch', {
          company_id: companyId
        })

        // Update current company
        this.currentCompany = this.companies.find(company => company.id === companyId)
        
        // Store context in local storage for persistence
        const context = response.data.context
        localStorage.setItem('partner_context', JSON.stringify({
          partner_id: context.partner_id,
          company_id: context.company_id,
          company_name: this.currentCompany?.name,
          switched_at: new Date().toISOString()
        }))

        return response.data
      } catch (error) {
        this.error = error.response?.data?.error || 'Failed to switch company'
        console.error('Console store - switchCompany error:', error)
        throw error
      } finally {
        this.isSwitching = false
      }
    },

    /**
     * Get current partner context from localStorage
     */
    getStoredContext() {
      try {
        const stored = localStorage.getItem('partner_context')
        return stored ? JSON.parse(stored) : null
      } catch (error) {
        console.error('Console store - getStoredContext error:', error)
        return null
      }
    },

    /**
     * Clear stored context
     */
    clearStoredContext() {
      localStorage.removeItem('partner_context')
      this.currentCompany = null
    },

    /**
     * Initialize console store with stored context
     */
    async initialize() {
      const storedContext = this.getStoredContext()
      
      if (storedContext && storedContext.company_id) {
        // Fetch companies first
        await this.fetchCompanies()
        
        // Set current company from stored context
        const company = this.companies.find(c => c.id === storedContext.company_id)
        if (company) {
          this.currentCompany = company
        }
      } else {
        // No stored context, just fetch companies
        await this.fetchCompanies()
      }
    },

    /**
     * Reset store state
     */
    resetStore() {
      this.partner = null
      this.companies = []
      this.currentCompany = null
      this.isLoading = false
      this.isSwitching = false
      this.error = null
      this.showCompanySwitcher = false
      this.clearStoredContext()
    },

    /**
     * Toggle company switcher visibility
     */
    toggleCompanySwitcher() {
      this.showCompanySwitcher = !this.showCompanySwitcher
    },

    /**
     * Get company by ID
     */
    getCompanyById(companyId) {
      return this.companies.find(company => company.id === companyId) || null
    },
  },
})

