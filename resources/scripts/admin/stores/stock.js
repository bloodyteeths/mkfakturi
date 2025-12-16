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
      itemCard: {
        item: null,
        opening_balance: { quantity: 0, value: 0 },
        closing_balance: { quantity: 0, value: 0 },
        movements: []
      },
      valuationReport: null,
      lowStockItems: [],
      isLoading: false,
      isLoadingItemCard: false,
      // Stock module is always enabled
      stockEnabled: true,
      // Warehouses for filter dropdowns (fetched from warehouse API)
      warehouses: [],
      isLoadingWarehouses: false,
      // Warehouse Inventory state (for WarehouseInventory.vue)
      warehouseInventory: {
        warehouse: null,
        as_of_date: null,
        items: [],
        totals: { quantity: 0, value: 0 }
      },
      isLoadingInventory: false,
      // Inventory Valuation state (for InventoryValuation.vue)
      inventoryValuation: {
        as_of_date: null,
        group_by: 'warehouse',
        warehouses: [],
        items: [],
        grand_total: { quantity: 0, value: 0 }
      },
      isLoadingValuation: false,
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
       * Reset item card to default structure
       */
      resetItemCard() {
        this.itemCard = {
          item: null,
          opening_balance: { quantity: 0, value: 0 },
          closing_balance: { quantity: 0, value: 0 },
          movements: []
        }
      },

      /**
       * Reset valuation report to null
       */
      resetValuationReport() {
        this.valuationReport = null
      },

      /**
       * Reset warehouse inventory to default structure
       */
      resetWarehouseInventory() {
        this.warehouseInventory = {
          warehouse: null,
          as_of_date: null,
          items: [],
          totals: { quantity: 0, value: 0 }
        }
      },

      /**
       * Reset inventory valuation to default structure
       */
      resetInventoryValuation() {
        this.inventoryValuation = {
          as_of_date: null,
          group_by: 'warehouse',
          warehouses: [],
          items: [],
          grand_total: { quantity: 0, value: 0 }
        }
      },

      /**
       * Fetch inventory with filters
       * Supports filtering by warehouse, item, date range, etc.
       */
      async fetchInventory(params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get('/stock/inventory', { params })

          // API returns {inventory: [...], summary: {...}}
          this.inventory = response.data.inventory || response.data.data || []
          this.totalInventory = response.data.summary?.total_items || this.inventory.length

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
        this.isLoadingItemCard = true

        try {
          const response = await axios.get(`/stock/item-card/${itemId}`, { params })

          this.itemCard = response.data.data || {
            item: null,
            opening_balance: { quantity: 0, value: 0 },
            closing_balance: { quantity: 0, value: 0 },
            movements: []
          }

          return response
        } catch (err) {
          handleError(err)
          // Reset to default structure on error
          this.resetItemCard()
          throw err
        } finally {
          this.isLoadingItemCard = false
        }
      },

      /**
       * Fetch warehouse inventory (current stock levels for a specific warehouse)
       *
       * @param {number} warehouseId - The warehouse ID
       * @param {object} params - Optional params { as_of_date }
       */
      async fetchWarehouseInventory(warehouseId, params = {}) {
        this.isLoadingInventory = true

        try {
          const response = await axios.get(`/stock/warehouse/${warehouseId}/inventory`, { params })

          // Store in warehouseInventory state for dedicated view
          this.warehouseInventory = {
            warehouse: response.data.warehouse || { id: warehouseId },
            as_of_date: params.as_of_date || new Date().toISOString().split('T')[0],
            items: response.data.data || response.data.items || [],
            totals: response.data.totals || {
              quantity: (response.data.data || []).reduce((sum, item) => sum + (item.quantity || 0), 0),
              value: (response.data.data || []).reduce((sum, item) => sum + (item.value || 0), 0)
            }
          }

          // Also update legacy inventory state for backwards compatibility
          this.inventory = this.warehouseInventory.items
          this.totalInventory = response.data.meta?.total || this.warehouseInventory.items.length

          return response
        } catch (err) {
          handleError(err)
          this.resetWarehouseInventory()
          throw err
        } finally {
          this.isLoadingInventory = false
        }
      },

      /**
       * Fetch inventory valuation report (grouped by warehouse or item)
       *
       * @param {object} params - { as_of_date, group_by: 'warehouse'|'item' }
       */
      async fetchInventoryValuation(params = {}) {
        this.isLoadingValuation = true

        try {
          const response = await axios.get('/stock/inventory-valuation', { params })

          this.inventoryValuation = {
            as_of_date: params.as_of_date || response.data.as_of_date || new Date().toISOString().split('T')[0],
            group_by: params.group_by || 'warehouse',
            warehouses: response.data.warehouses || [],
            items: response.data.items || response.data.data || [],
            grand_total: response.data.grand_total || response.data.totals || {
              quantity: 0,
              value: 0
            }
          }

          return response
        } catch (err) {
          handleError(err)
          this.resetInventoryValuation()
          throw err
        } finally {
          this.isLoadingValuation = false
        }
      },

      /**
       * Fetch valuation report (total inventory value by valuation method)
       * Returns inventory valued at different methods (FIFO, weighted average, etc.)
       */
      async fetchValuationReport() {
        this.isLoading = true

        try {
          const response = await axios.get('/stock/valuation-report')

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
          const response = await axios.get('/stock/low-stock', { params })

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

      /**
       * Fetch warehouses for filter dropdowns
       * Uses the warehouse API endpoint
       */
      async fetchWarehouses() {
        this.isLoadingWarehouses = true

        try {
          const response = await axios.get('/stock/warehouses', {
            params: { limit: 100 },
          })

          this.warehouses = response.data.data || []

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoadingWarehouses = false
        }
      },

      /**
       * Fetch inventory list with pagination and filters
       * Alias for fetchInventory with pagination support
       *
       * @param {object} params - Filter and pagination params
       */
      async fetchInventoryList(params = {}) {
        return this.fetchInventory(params)
      },

      // ==========================================
      // Stock Adjustments API
      // ==========================================

      /**
       * Fetch stock adjustments list
       * @param {object} params - Filter params { warehouse_id, item_id, from_date, to_date, limit }
       */
      async fetchAdjustments(params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get('/stock/adjustments', { params })
          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Create a stock adjustment
       * @param {object} data - { warehouse_id, item_id, quantity, unit_cost, reason, notes }
       */
      async createAdjustment(data) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.post('/stock/adjustments', data)

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('stock.adjustment_created'),
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
       * Delete/reverse a stock adjustment
       * @param {number} id - Adjustment ID
       */
      async deleteAdjustment(id) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.delete(`/stock/adjustments/${id}`)

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('stock.adjustment_reversed'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      // ==========================================
      // Stock Transfers API
      // ==========================================

      /**
       * Fetch stock transfers list
       * @param {object} params - Filter params { limit }
       */
      async fetchTransfers(params = {}) {
        this.isLoading = true

        try {
          const response = await axios.get('/stock/transfers', { params })
          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      /**
       * Create a stock transfer between warehouses
       * @param {object} data - { from_warehouse_id, to_warehouse_id, item_id, quantity, notes }
       */
      async createTransfer(data) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.post('/stock/transfers', data)

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('stock.transfer_created'),
          })

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoading = false
        }
      },

      // ==========================================
      // Initial Stock API
      // ==========================================

      /**
       * Record initial stock for an item
       * @param {object} data - { warehouse_id, item_id, quantity, unit_cost, notes }
       */
      async createInitialStock(data) {
        const notificationStore = useNotificationStore()
        this.isLoading = true

        try {
          const response = await axios.post('/stock/initial-stock', data)

          notificationStore.showNotification({
            type: 'success',
            message: window.i18n.global.t('stock.initial_stock_created'),
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
       * Get available stock for an item (for UI validation)
       * @param {number} itemId - Item ID
       * @param {number} warehouseId - Optional warehouse ID
       */
      async getItemStock(itemId, warehouseId = null) {
        try {
          const params = warehouseId ? { warehouse_id: warehouseId } : {}
          const response = await axios.get(`/stock/items/${itemId}/stock`, { params })
          return response.data
        } catch (err) {
          handleError(err)
          throw err
        }
      },
    },
  })()
}
// CLAUDE-CHECKPOINT
