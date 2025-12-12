import { defineStore } from 'pinia'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const usePartnerAccountingStore = defineStore('partnerAccounting', {
  state: () => ({
    // Accounts
    accounts: [],
    accountTree: [],
    currentAccount: null,

    // Mappings
    mappings: [],
    suggestions: {},

    // Journal entries
    journalEntries: [],
    journalPagination: {
      currentPage: 1,
      totalPages: 1,
      perPage: 20,
      total: 0,
    },

    // Loading states
    isLoading: false,
    isSaving: false,
    isExporting: false,

    // Error handling
    error: null,
  }),

  getters: {
    /**
     * Get accounts by type
     */
    accountsByType: (state) => (type) => {
      return state.accounts.filter((a) => a.type === type)
    },

    /**
     * Get active accounts only
     */
    activeAccounts: (state) => {
      return state.accounts.filter((a) => a.is_active)
    },

    /**
     * Get mappings by entity type (customer/supplier/category)
     */
    mappingsByType: (state) => (type) => {
      return state.mappings.filter((m) => m.entity_type === type)
    },

    /**
     * Check if there are pending journal entries
     */
    hasPendingEntries: (state) => {
      return state.journalEntries.some((e) => e.status === 'pending')
    },
  },

  actions: {
    /**
     * Fetch chart of accounts for a company
     */
    async fetchAccounts(companyId, params = {}) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get(`/api/v1/partner/companies/${companyId}/accounts`, {
          params,
        })

        this.accounts = response.data.data || []
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch accounts'
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Fetch accounts as tree structure
     */
    async fetchAccountTree(companyId) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get(`/api/v1/partner/companies/${companyId}/accounts/tree`)

        this.accountTree = response.data.data || []
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch account tree'
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Create a new account
     */
    async createAccount(companyId, data) {
      const notificationStore = useNotificationStore()
      this.isSaving = true
      this.error = null

      try {
        const response = await axios.post(
          `/api/v1/partner/companies/${companyId}/accounts`,
          data
        )

        this.accounts.push(response.data.data)

        notificationStore.showNotification({
          type: 'success',
          message: 'Account created successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create account'
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    /**
     * Update an account
     */
    async updateAccount(companyId, accountId, data) {
      const notificationStore = useNotificationStore()
      this.isSaving = true
      this.error = null

      try {
        const response = await axios.put(
          `/api/v1/partner/companies/${companyId}/accounts/${accountId}`,
          data
        )

        const index = this.accounts.findIndex((a) => a.id === accountId)
        if (index > -1) {
          this.accounts[index] = response.data.data
        }

        notificationStore.showNotification({
          type: 'success',
          message: 'Account updated successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update account'
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    /**
     * Delete an account
     */
    async deleteAccount(companyId, accountId) {
      const notificationStore = useNotificationStore()
      this.error = null

      try {
        await axios.delete(`/api/v1/partner/companies/${companyId}/accounts/${accountId}`)

        const index = this.accounts.findIndex((a) => a.id === accountId)
        if (index > -1) {
          this.accounts.splice(index, 1)
        }

        notificationStore.showNotification({
          type: 'success',
          message: 'Account deleted successfully',
        })
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete account'
        handleError(error)
        throw error
      }
    },

    /**
     * Import accounts from CSV file
     */
    async importAccounts(companyId, file) {
      const notificationStore = useNotificationStore()
      this.isLoading = true
      this.error = null

      try {
        const formData = new FormData()
        formData.append('file', file)

        const response = await axios.post(
          `/api/v1/partner/companies/${companyId}/accounts/import`,
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data',
            },
          }
        )

        // Refresh accounts list
        await this.fetchAccounts(companyId)

        notificationStore.showNotification({
          type: 'success',
          message: response.data.message || 'Accounts imported successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to import accounts'
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Fetch account mappings for a company
     */
    async fetchMappings(companyId, type = null) {
      this.isLoading = true
      this.error = null

      try {
        const params = type ? { type } : {}
        const response = await axios.get(
          `/api/v1/partner/companies/${companyId}/account-mappings`,
          { params }
        )

        this.mappings = response.data.data || []
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch mappings'
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Create a new mapping
     */
    async createMapping(companyId, data) {
      const notificationStore = useNotificationStore()
      this.isSaving = true
      this.error = null

      try {
        const response = await axios.post(
          `/api/v1/partner/companies/${companyId}/account-mappings`,
          data
        )

        this.mappings.push(response.data.data)

        notificationStore.showNotification({
          type: 'success',
          message: 'Mapping created successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to create mapping'
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    /**
     * Update a mapping
     */
    async updateMapping(companyId, mappingId, data) {
      const notificationStore = useNotificationStore()
      this.isSaving = true
      this.error = null

      try {
        const response = await axios.put(
          `/api/v1/partner/companies/${companyId}/account-mappings/${mappingId}`,
          data
        )

        const index = this.mappings.findIndex((m) => m.id === mappingId)
        if (index > -1) {
          this.mappings[index] = response.data.data
        }

        notificationStore.showNotification({
          type: 'success',
          message: 'Mapping updated successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to update mapping'
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    /**
     * Delete a mapping
     */
    async deleteMapping(companyId, mappingId) {
      const notificationStore = useNotificationStore()
      this.error = null

      try {
        await axios.delete(
          `/api/v1/partner/companies/${companyId}/account-mappings/${mappingId}`
        )

        const index = this.mappings.findIndex((m) => m.id === mappingId)
        if (index > -1) {
          this.mappings.splice(index, 1)
        }

        notificationStore.showNotification({
          type: 'success',
          message: 'Mapping deleted successfully',
        })
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to delete mapping'
        handleError(error)
        throw error
      }
    },

    /**
     * Get AI suggestion for entity mapping
     */
    async getSuggestion(companyId, entityType, entityId) {
      this.error = null

      try {
        const response = await axios.get(
          `/api/v1/partner/companies/${companyId}/account-mappings/suggest/${entityType}/${entityId}`
        )

        // Store suggestion for this entity
        const key = `${entityType}_${entityId}`
        this.suggestions[key] = response.data.data

        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to get suggestion'
        handleError(error)
        throw error
      }
    },

    /**
     * Fetch journal entries with filters
     */
    async fetchJournalEntries(companyId, params = {}) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get(
          `/api/v1/partner/companies/${companyId}/journal-entries`,
          { params }
        )

        this.journalEntries = response.data.data || []

        if (response.data.pagination) {
          this.journalPagination = {
            currentPage: response.data.pagination.current_page || 1,
            totalPages: response.data.pagination.last_page || 1,
            perPage: response.data.pagination.per_page || 20,
            total: response.data.pagination.total || 0,
          }
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch journal entries'
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Confirm a journal entry with account assignment
     */
    async confirmEntry(companyId, entryId, accountId) {
      const notificationStore = useNotificationStore()
      this.isSaving = true
      this.error = null

      try {
        const response = await axios.post(
          `/api/v1/partner/companies/${companyId}/journal-entries/${entryId}/confirm`,
          { account_id: accountId }
        )

        const index = this.journalEntries.findIndex((e) => e.id === entryId)
        if (index > -1) {
          this.journalEntries[index] = response.data.data
        }

        notificationStore.showNotification({
          type: 'success',
          message: 'Journal entry confirmed successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to confirm entry'
        handleError(error)
        throw error
      } finally {
        this.isSaving = false
      }
    },

    /**
     * Export journal entries to various formats
     */
    async exportJournal(companyId, params = {}) {
      const notificationStore = useNotificationStore()
      this.isExporting = true
      this.error = null

      try {
        const response = await axios.get(
          `/api/v1/partner/companies/${companyId}/journal-entries/export`,
          {
            params,
            responseType: 'blob',
          }
        )

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        // Get filename from Content-Disposition header or generate one
        const contentDisposition = response.headers['content-disposition']
        let filename = `journal_export_${params.format || 'csv'}.${params.format || 'csv'}`

        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
          if (filenameMatch) {
            filename = filenameMatch[1]
          }
        }

        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        notificationStore.showNotification({
          type: 'success',
          message: 'Journal exported successfully',
        })

        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to export journal'
        handleError(error)
        throw error
      } finally {
        this.isExporting = false
      }
    },

    /**
     * Reset store state
     */
    resetStore() {
      this.accounts = []
      this.accountTree = []
      this.currentAccount = null
      this.mappings = []
      this.suggestions = {}
      this.journalEntries = []
      this.journalPagination = {
        currentPage: 1,
        totalPages: 1,
        perPage: 20,
        total: 0,
      }
      this.isLoading = false
      this.isSaving = false
      this.isExporting = false
      this.error = null
    },
  },
})

// CLAUDE-CHECKPOINT
