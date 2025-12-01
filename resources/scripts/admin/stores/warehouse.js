import { defineStore } from 'pinia'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

/**
 * Warehouse stub for initializing new warehouse forms
 */
const warehouseStub = () => ({
  id: null,
  name: '',
  code: '',
  address: '',
  city: '',
  state: '',
  zip: '',
  country: '',
  phone: '',
  email: '',
  is_default: false,
  is_active: true,
})

/**
 * Warehouse Store
 *
 * Manages warehouse state and API interactions for the Stock module.
 */
export const useWarehouseStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'warehouse',

    state: () => ({
      warehouses: [],
      totalWarehouses: 0,
      currentWarehouse: { ...warehouseStub() },
      isLoading: false,
    }),

    getters: {
      /**
       * Check if we're editing an existing warehouse
       */
      isEdit: (state) => (state.currentWarehouse.id ? true : false),

      /**
       * Get warehouse by ID from loaded warehouses
       */
      getWarehouseById: (state) => (id) =>
        state.warehouses.find((w) => w.id === id),

      /**
       * Get the default warehouse
       */
      defaultWarehouse: (state) =>
        state.warehouses.find((w) => w.is_default === true),

      /**
       * Get active warehouses only
       */
      activeWarehouses: (state) =>
        state.warehouses.filter((w) => w.is_active === true),
    },

    actions: {
      /**
       * Reset current warehouse to empty state
       */
      resetCurrentWarehouse() {
        this.currentWarehouse = { ...warehouseStub() }
      },

      /**
       * Fetch all warehouses with filters
       */
      async fetchWarehouses(params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get('/stock/warehouses', { params })

          this.warehouses = response.data.data
          this.totalWarehouses = response.data.meta?.total || response.data.data.length

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Fetch a single warehouse by ID
       */
      async fetchWarehouse(id) {
        this.isLoading = true

        try {
          const response = await axios.get(`/stock/warehouses/${id}`)

          this.currentWarehouse = response.data.data

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Create a new warehouse
       */
      async addWarehouse(data) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.post('/stock/warehouses', data)

          // Add to local state
          this.warehouses.unshift(response.data.data)
          this.totalWarehouses++

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('warehouses.created_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Update an existing warehouse
       */
      async updateWarehouse(id, data) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.put(`/stock/warehouses/${id}`, data)

          // Update in local state
          const index = this.warehouses.findIndex((w) => w.id === id)
          if (index !== -1) {
            this.warehouses[index] = response.data.data
          }

          this.currentWarehouse = response.data.data

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('warehouses.updated_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Delete a warehouse
       */
      async deleteWarehouse(id) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.delete(`/stock/warehouses/${id}`)

          // Remove from local state
          const index = this.warehouses.findIndex((w) => w.id === id)
          if (index !== -1) {
            this.warehouses.splice(index, 1)
            this.totalWarehouses--
          }

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('warehouses.deleted_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Set a warehouse as default
       */
      async setDefaultWarehouse(id) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.post(`/stock/warehouses/${id}/set-default`)

          // Update all warehouses - set this one as default, others as non-default
          this.warehouses.forEach((w) => {
            w.is_default = w.id === id
          })

          if (this.currentWarehouse.id) {
            this.currentWarehouse.is_default = this.currentWarehouse.id === id
          }

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('warehouses.default_set_message'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Set current warehouse from loaded data (for editing)
       */
      setCurrentWarehouse(warehouse) {
        this.currentWarehouse = {
          ...warehouseStub(),
          ...warehouse,
        }
      },
    },
  })()
}

// CLAUDE-CHECKPOINT
