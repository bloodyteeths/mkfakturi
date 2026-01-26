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
        const response = await axios.get(`/partner/companies/${companyId}/accounts`, {
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
        const response = await axios.get(`/partner/companies/${companyId}/accounts/tree`)

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
     * Fetch a single account by ID
     */
    async fetchAccount(companyId, accountId) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get(
          `/partner/companies/${companyId}/accounts/${accountId}`
        )

        this.currentAccount = response.data.data || null
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch account'
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
          `/partner/companies/${companyId}/accounts`,
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
          `/partner/companies/${companyId}/accounts/${accountId}`,
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
      this.isSaving = true
      this.error = null

      try {
        await axios.delete(`/partner/companies/${companyId}/accounts/${accountId}`)

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
      } finally {
        this.isSaving = false
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
          `/partner/companies/${companyId}/accounts/import`,
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
     * Export accounts to CSV file
     */
    async exportAccounts(companyId) {
      const notificationStore = useNotificationStore()
      this.isExporting = true
      this.error = null

      try {
        const response = await axios.get(
          `/partner/companies/${companyId}/accounts/export`,
          {
            responseType: 'blob',
          }
        )

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        // Get filename from Content-Disposition header or generate one
        const contentDisposition = response.headers['content-disposition']
        let filename = `chart-of-accounts-${new Date().toISOString().split('T')[0]}.csv`

        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="([^"]+)"/) ||
                                contentDisposition.match(/filename=([^;\s]+)/)
          if (filenameMatch) {
            filename = filenameMatch[1].replace(/["']/g, '')
          }
        }

        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        notificationStore.showNotification({
          type: 'success',
          message: 'Chart of accounts exported successfully',
        })

        return response
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to export accounts'
        handleError(error)
        throw error
      } finally {
        this.isExporting = false
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
          `/partner/companies/${companyId}/mappings`,
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
          `/partner/companies/${companyId}/mappings`,
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
          `/partner/companies/${companyId}/mappings/${mappingId}`,
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
      this.isSaving = true
      this.error = null

      try {
        await axios.delete(
          `/partner/companies/${companyId}/mappings/${mappingId}`
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
      } finally {
        this.isSaving = false
      }
    },

    /**
     * Get AI suggestion for entity mapping
     */
    async getSuggestion(companyId, entityType, entityId) {
      this.error = null

      try {
        const response = await axios.post(
          `/partner/companies/${companyId}/mappings/suggest`,
          { entity_type: entityType, entity_id: entityId }
        )

        // Store suggestion for this entity with cache size limit
        const key = `${entityType}_${entityId}`
        this.addSuggestionWithLimit(key, response.data.data)

        return response.data.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to get suggestion'
        handleError(error)
        throw error
      }
    },

    /**
     * Add suggestion to cache with size limit and TTL to prevent memory leak and stale data
     * @param {string} key - The suggestion key
     * @param {Object} value - The suggestion data
     * @param {number} maxSize - Maximum cache size (default 100)
     * @param {number} ttlMs - Time-to-live in milliseconds (default 5 minutes)
     */
    addSuggestionWithLimit(key, value, maxSize = 100, ttlMs = 5 * 60 * 1000) {
      const now = Date.now()

      // First, clean up expired entries
      const keys = Object.keys(this.suggestions)
      keys.forEach((k) => {
        const entry = this.suggestions[k]
        if (entry && entry._expiresAt && entry._expiresAt < now) {
          delete this.suggestions[k]
        }
      })

      // Then check size limit after cleanup
      const remainingKeys = Object.keys(this.suggestions)
      if (remainingKeys.length >= maxSize && !this.suggestions[key]) {
        // Remove oldest entries (first 10) when limit is reached
        const keysToRemove = remainingKeys.slice(0, 10)
        keysToRemove.forEach((k) => delete this.suggestions[k])
      }

      // Store value with expiry timestamp
      this.suggestions[key] = {
        ...value,
        _expiresAt: now + ttlMs,
      }
    },

    /**
     * Get suggestion from cache, respecting TTL
     * @param {string} key - The suggestion key
     * @returns {Object|null} - The suggestion data or null if expired/not found
     */
    getSuggestionFromCache(key) {
      const entry = this.suggestions[key]
      if (!entry) return null

      // Check if expired
      if (entry._expiresAt && entry._expiresAt < Date.now()) {
        delete this.suggestions[key]
        return null
      }

      // Return data without internal _expiresAt field
      const { _expiresAt, ...data } = entry
      return data
    },

    /**
     * Clear all cached suggestions
     */
    clearSuggestions() {
      this.suggestions = {}
    },

    /**
     * Extract pagination data from API response
     * @param {Object} response - The axios response object
     * @returns {Object} Normalized pagination object
     */
    extractPagination(response) {
      const paginationData = response.data.meta || response.data.pagination
      if (paginationData) {
        return {
          currentPage: paginationData.current_page || 1,
          totalPages: paginationData.last_page || 1,
          perPage: paginationData.per_page || 20,
          total: paginationData.total || 0,
        }
      }
      return null
    },

    /**
     * Fetch journal entries with filters
     */
    async fetchJournalEntries(companyId, params = {}) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get(
          `/partner/companies/${companyId}/journal-entries`,
          { params }
        )

        this.journalEntries = response.data.data || []

        // Extract pagination using helper
        const pagination = this.extractPagination(response)
        if (pagination) {
          this.journalPagination = pagination
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
        const response = await axios.put(
          `/partner/companies/${companyId}/journal-entries/${entryId}`,
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
     * Fetch journal entries with AI suggestions
     */
    async fetchJournalWithSuggestions(companyId, params = {}) {
      this.isLoading = true
      this.error = null

      try {
        const response = await axios.get(
          `/partner/companies/${companyId}/journal-entries`,
          {
            params: {
              ...params,
              with_suggestions: true,
            },
          }
        )

        this.journalEntries = response.data.data || []

        // Extract pagination using helper
        const pagination = this.extractPagination(response)
        if (pagination) {
          this.journalPagination = pagination
        }

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to fetch journal entries with suggestions'
        handleError(error)
        throw error
      } finally {
        this.isLoading = false
      }
    },

    /**
     * Get AI suggestions for specific entries
     *
     * @param {number} companyId - The company ID
     * @param {Array} entries - Array of entry objects with { type, entity_id, name, description }
     *                          where type is 'customer', 'supplier', 'category', or 'expense_category'
     */
    async getSuggestions(companyId, entries) {
      this.error = null

      try {
        // Backend expects: { entries: [{ type, entity_id, name, description }] }
        const response = await axios.post(
          `/partner/companies/${companyId}/journal/suggest`,
          { entries }
        )

        // Update suggestions in journalEntries based on response
        const suggestionsData = response.data.data?.suggestions || []
        suggestionsData.forEach((suggestion) => {
          // Find matching entry by entity_type and entity_id
          const index = this.journalEntries.findIndex(
            (e) =>
              e.entity_type === suggestion.entity_type &&
              e.entity_id === suggestion.entity_id
          )
          if (index > -1) {
            this.journalEntries[index] = {
              ...this.journalEntries[index],
              account_id: suggestion.suggested_account?.id || null,
              confidence: suggestion.suggested_account?.confidence || 0,
              suggestion_reason: suggestion.suggested_account?.reason || 'default',
            }
          }
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to get suggestions'
        handleError(error)
        throw error
      }
    },

    /**
     * Save learned mapping (when accountant overrides)
     */
    async learnMapping(companyId, mappings) {
      const notificationStore = useNotificationStore()
      this.error = null

      try {
        const response = await axios.post(
          `/partner/companies/${companyId}/journal/learn`,
          { mappings }
        )

        notificationStore.showNotification({
          type: 'success',
          message: 'Mapping learned successfully',
        })

        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to save learned mapping'
        handleError(error)
        throw error
      }
    },

    /**
     * Bulk accept all high-confidence suggestions
     *
     * Filters journal entries with confidence >= threshold and saves them as learned mappings.
     * Uses the /journal/learn endpoint to persist the accepted mappings.
     *
     * @param {number} companyId - The company ID
     * @param {number} minConfidence - Minimum confidence threshold (default 0.8)
     * @param {string|null} dateFrom - Optional start date filter (not used for local filtering)
     * @param {string|null} dateTo - Optional end date filter (not used for local filtering)
     */
    async acceptAllSuggestions(companyId, minConfidence = 0.8, dateFrom = null, dateTo = null) {
      const notificationStore = useNotificationStore()
      this.isSaving = true
      this.error = null

      try {
        // Filter entries with high confidence that have valid entity info and account_id
        const highConfidenceEntries = this.journalEntries.filter(
          (entry) =>
            entry.confidence >= minConfidence &&
            entry.entity_type &&
            entry.entity_id &&
            entry.account_id &&
            !entry.confirmed
        )

        if (highConfidenceEntries.length === 0) {
          notificationStore.showNotification({
            type: 'warning',
            message: 'No high-confidence entries to accept',
          })
          return { success: true, accepted_count: 0 }
        }

        // Build mappings array for the learn endpoint
        const mappings = highConfidenceEntries.map((entry) => ({
          entity_type: entry.entity_type,
          entity_id: entry.entity_id,
          account_id: entry.account_id,
          accepted: true,
        }))

        // Use the learn endpoint to save all mappings at once
        const response = await axios.post(
          `/partner/companies/${companyId}/journal/learn`,
          { mappings }
        )

        // Mark entries as confirmed in local state
        highConfidenceEntries.forEach((entry) => {
          const index = this.journalEntries.findIndex((e) => e.id === entry.id)
          if (index > -1) {
            this.journalEntries[index] = {
              ...this.journalEntries[index],
              confirmed: true,
            }
          }
        })

        notificationStore.showNotification({
          type: 'success',
          message: `${highConfidenceEntries.length} high-confidence entries accepted`,
        })

        return {
          success: true,
          accepted_count: highConfidenceEntries.length,
          learned_count: response.data.learned_count,
        }
      } catch (error) {
        this.error = error.response?.data?.message || 'Failed to accept all suggestions'
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
        const response = await axios.post(
          `/partner/companies/${companyId}/journal/export`,
          params,
          {
            responseType: 'blob',
          }
        )

        // Create download link
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        // Get filename from Content-Disposition header or generate one
        const contentDisposition = response.headers['content-disposition']
        const extension = params.format === 'pantheon' ? 'xml' : 'csv'
        let filename = `journal_export_${params.format || 'csv'}.${extension}`

        if (contentDisposition) {
          // Match filename with or without quotes, non-greedy
          const filenameMatch = contentDisposition.match(/filename="([^"]+)"/) ||
                                contentDisposition.match(/filename=([^;\s]+)/)
          if (filenameMatch) {
            filename = filenameMatch[1].replace(/["']/g, '') // Remove any quotes
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

    /**
     * Update the account_id for a journal entry in local state
     * This avoids direct v-model mutation of store state
     * @param {number} entryId - The entry ID
     * @param {number|null} accountId - The new account ID
     */
    updateEntryAccount(entryId, accountId) {
      const index = this.journalEntries.findIndex((e) => e.id === entryId)
      if (index > -1) {
        this.journalEntries[index] = {
          ...this.journalEntries[index],
          account_id: accountId,
        }
      }
    },
  },
})

// CLAUDE-CHECKPOINT
