import { defineStore } from 'pinia'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

/**
 * Stock Store
 *
 * Manages stock inventory state and API interactions for the Stock module.
 * Handles inventory tracking, item cards, valuation reports, and low stock alerts.
 */
export const useStockStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'stock',

    state: () => ({
      inventory: [],
      totalInventory: 0,
      itemCard: null,
      valuationReport: null,
      lowStockItems: [],
      isLoading: false,
    }),

    getters: {
      /**
       * Get inventory item by item ID
       */
      getInventoryByItemId: (state) => (itemId) =>
        state.inventory.find((inv) => inv.item_id === itemId),

      /**
       * Get total inventory value from valuation report
       */
      totalInventoryValue: (state) =>
        state.valuationReport?.total_value || 0,

      /**
       * Get low stock items count
       */
      lowStockCount: (state) => state.lowStockItems.length,

      /**
       * Check if an item is low on stock
       */
      isLowStock: (state) => (itemId) =>
        state.lowStockItems.some((item) => item.item_id === itemId),
    },

    actions: {
      /**
       * Reset item card to null
       */
      resetItemCard() {
        this.itemCard = null
      },

      /**
       * Reset valuation report to null
       */
      resetValuationReport() {
        this.valuationReport = null
      },

      /**
       * Fetch inventory with filters
       * Supports filtering by warehouse, item, date range, etc.
       */
      async fetchInventory(params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get('/api/v1/stock/inventory', { params })

          this.inventory = response.data.data
          this.totalInventory = response.data.meta?.total || response.data.data.length

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Fetch item card (movement history for a specific item)
       *
       * @param {number} itemId - The item ID
       * @param {object} params - Optional params { warehouse_id, from_date, to_date }
       */
      async fetchItemCard(itemId, params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get(`/api/v1/stock/item-card/${itemId}`, { params })

          this.itemCard = response.data.data

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Fetch warehouse inventory (current stock levels for a specific warehouse)
       *
       * @param {number} warehouseId - The warehouse ID
       */
      async fetchWarehouseInventory(warehouseId) {
        this.isLoading = true

        try {
          const response = await axios.get(`/api/v1/stock/warehouse/${warehouseId}/inventory`)

          // Store as inventory for the current view
          this.inventory = response.data.data
          this.totalInventory = response.data.meta?.total || response.data.data.length

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Fetch valuation report (total inventory value by valuation method)
       * Returns inventory valued at different methods (FIFO, weighted average, etc.)
       */
      async fetchValuationReport() {
        this.isLoading = true

        try {
          const response = await axios.get('/api/v1/stock/valuation-report')

          this.valuationReport = response.data.data

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Fetch low stock items (items below minimum stock level)
       *
       * @param {object} params - Optional params { warehouse_id, search, severity, page, orderByField, orderBy }
       */
      async fetchLowStock(params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get('/api/v1/stock/low-stock', { params })

          this.lowStockItems = response.data.data

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Fetch low stock items (alias for fetchLowStock with params)
       * Used by LowStock.vue page for filtering and pagination
       *
       * @param {object} params - Filter and pagination params
       */
      async fetchLowStockItems(params = {}) {
        return this.fetchLowStock(params)
      },

      /**
       * Clear all inventory data
       */
      clearInventory() {
        this.inventory = []
        this.totalInventory = 0
      },

      /**
       * Clear low stock items
       */
      clearLowStock() {
        this.lowStockItems = []
      },
    },
  })()
}
// CLAUDE-CHECKPOINT
