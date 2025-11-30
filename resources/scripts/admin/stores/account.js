import axios from 'axios'
import { defineStore } from 'pinia'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

export const useAccountStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore
  const { global } = window.i18n

  return defineStoreFunc({
    id: 'account',

    state: () => ({
      accounts: [],
      accountTree: [],
      mappings: [],
      currentAccount: {
        id: null,
        code: '',
        name: '',
        type: 'asset',
        parent_id: null,
        description: '',
        is_active: true,
        meta: null,
      },
      isLoading: false,
    }),

    getters: {
      isEdit: (state) => (state.currentAccount.id ? true : false),

      // Get accounts by type
      assetAccounts: (state) =>
        state.accounts.filter((a) => a.type === 'asset'),
      liabilityAccounts: (state) =>
        state.accounts.filter((a) => a.type === 'liability'),
      equityAccounts: (state) =>
        state.accounts.filter((a) => a.type === 'equity'),
      revenueAccounts: (state) =>
        state.accounts.filter((a) => a.type === 'revenue'),
      expenseAccounts: (state) =>
        state.accounts.filter((a) => a.type === 'expense'),

      // Get active accounts only
      activeAccounts: (state) => state.accounts.filter((a) => a.is_active),
    },

    actions: {
      resetCurrentAccount() {
        this.currentAccount = {
          id: null,
          code: '',
          name: '',
          type: 'asset',
          parent_id: null,
          description: '',
          is_active: true,
          meta: null,
        }
      },

      /**
       * Fetch all accounts for the company
       */
      fetchAccounts(params = {}) {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .get('/accounting/accounts', { params })
            .then((response) => {
              this.accounts = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Fetch accounts as tree structure
       */
      fetchAccountTree() {
        return new Promise((resolve, reject) => {
          this.isLoading = true
          axios
            .get('/accounting/accounts/tree')
            .then((response) => {
              this.accountTree = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
            .finally(() => {
              this.isLoading = false
            })
        })
      },

      /**
       * Fetch a single account
       */
      fetchAccount(id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/accounting/accounts/${id}`)
            .then((response) => {
              this.currentAccount = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Create a new account
       */
      createAccount(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/accounting/accounts', data)
            .then((response) => {
              this.accounts.push(response.data.data)
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.accounts.created_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Update an account
       */
      updateAccount(id, data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .put(`/accounting/accounts/${id}`, data)
            .then((response) => {
              const index = this.accounts.findIndex((a) => a.id === id)
              if (index > -1) {
                this.accounts[index] = response.data.data
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.accounts.updated_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Delete an account
       */
      deleteAccount(id) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .delete(`/accounting/accounts/${id}`)
            .then((response) => {
              const index = this.accounts.findIndex((a) => a.id === id)
              if (index > -1) {
                this.accounts.splice(index, 1)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.accounts.deleted_message'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Fetch all account mappings
       */
      fetchMappings(params = {}) {
        return new Promise((resolve, reject) => {
          axios
            .get('/accounting/account-mappings', { params })
            .then((response) => {
              this.mappings = response.data.data
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Create or update an account mapping
       */
      upsertMapping(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/accounting/account-mappings', data)
            .then((response) => {
              // Refresh mappings list
              this.fetchMappings()
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.accounts.mapping_saved'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Delete an account mapping
       */
      deleteMapping(id) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .delete(`/accounting/account-mappings/${id}`)
            .then((response) => {
              const index = this.mappings.findIndex((m) => m.id === id)
              if (index > -1) {
                this.mappings.splice(index, 1)
              }
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.accounts.mapping_deleted'),
              })
              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Get journal entries preview
       */
      getJournalPreview(params) {
        return new Promise((resolve, reject) => {
          axios
            .get('/accounting/journals', { params })
            .then((response) => {
              resolve(response.data)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Export journals as CSV download
       */
      exportJournals(params) {
        return new Promise((resolve, reject) => {
          axios
            .get('/accounting/journals/export', {
              params,
              responseType: 'blob',
            })
            .then((response) => {
              // Create download link
              const url = window.URL.createObjectURL(new Blob([response.data]))
              const link = document.createElement('a')
              link.href = url

              // Get filename from Content-Disposition header or generate one
              const contentDisposition = response.headers['content-disposition']
              let filename = `journals_${params.format}_${params.from}_${params.to}.csv`
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

              resolve(response)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Get account suggestion for a transaction
       */
      getSuggestion(type, id) {
        return new Promise((resolve, reject) => {
          axios
            .get(`/accounting/suggestions/${type}/${id}`)
            .then((response) => {
              resolve(response.data)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Confirm account assignment for a transaction
       */
      confirmSuggestion(data) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/accounting/suggestions/confirm', data)
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: global.t('settings.account_review.confirmed_successfully'),
              })
              resolve(response.data)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Get pending transactions for review
       */
      getPendingReview(params = {}) {
        return new Promise((resolve, reject) => {
          axios
            .get('/accounting/suggestions/pending', { params })
            .then((response) => {
              resolve(response.data)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },

      /**
       * Bulk confirm account assignments
       */
      bulkConfirm(items) {
        const notificationStore = useNotificationStore()
        return new Promise((resolve, reject) => {
          axios
            .post('/accounting/suggestions/bulk-confirm', { items })
            .then((response) => {
              notificationStore.showNotification({
                type: 'success',
                message: response.data.message || global.t('settings.account_review.confirmed_successfully'),
              })
              resolve(response.data)
            })
            .catch((err) => {
              handleError(err)
              reject(err)
            })
        })
      },
    },
  })()
}
// CLAUDE-CHECKPOINT
