import { defineStore } from 'pinia'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { handleError } from '@/scripts/helpers/error-handling'

/**
 * Stock Store
 *
 * Manages stock reports state and API interactions.
 * Part of Phase 2: Stock Module (S2-4).
 *
 * Features:
 * - Item Stock Card (movement history)
 * - Warehouse Inventory
 * - Inventory Valuation
 * - Feature flag: FACTURINO_STOCK_V1_ENABLED
 */
export const useStockStore = (useWindow = false) => {
  const defineStoreFunc = useWindow ? window.pinia.defineStore : defineStore

  return defineStoreFunc({
    id: 'stock',

    state: () => ({
      // Warehouses list for dropdowns
      warehouses: [],

      // Item Stock Card state
      itemCard: {
        item: null,
        warehouse: null,
        filters: {
          from_date: null,
          to_date: null,
        },
        opening_balance: { quantity: 0, value: 0 },
        movements: [],
        closing_balance: { quantity: 0, value: 0 },
      },

      // Warehouse Inventory state
      warehouseInventory: {
        warehouse: null,
        as_of_date: null,
        items: [],
        totals: { quantity: 0, value: 0 },
      },

      // Inventory Valuation state
      inventoryValuation: {
        as_of_date: null,
        group_by: 'warehouse',
        warehouses: [],
        items: [],
        grand_total: { quantity: 0, value: 0 },
      },

      // Inventory List state
      inventoryList: {
        as_of_date: null,
        warehouse_id: null,
        items: [],
        total_items: 0,
      },

      // Loading states
      isLoadingWarehouses: false,
      isLoadingItemCard: false,
      isLoadingInventory: false,
      isLoadingValuation: false,
      isLoadingList: false,

      // Stock module enabled flag (set from bootstrap)
      isStockEnabled: false,
    }),

    getters: {
      /**
       * Check if stock module is enabled
       */
      stockEnabled: (state) => state.isStockEnabled,

      /**
       * Get default warehouse
       */
      defaultWarehouse: (state) =>
        state.warehouses.find((w) => w.is_default) || state.warehouses[0],

      /**
       * Get warehouse by ID
       */
      getWarehouseById: (state) => (id) =>
        state.warehouses.find((w) => w.id === id),
    },

    actions: {
      /**
       * Set stock enabled flag (called from bootstrap)
       */
      setStockEnabled(enabled) {
        this.isStockEnabled = enabled
      },

      /**
       * Fetch warehouses list for dropdowns
       */
      async fetchWarehouses() {
        this.isLoadingWarehouses = true
        try {
          const response = await axios.get('/api/v1/stock/warehouses')
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
       * Fetch Item Stock Card
       *
       * @param {number} itemId - Item ID
       * @param {object} params - Optional filters (warehouse_id, from_date, to_date)
       */
      async fetchItemCard(itemId, params = {}) {
        this.isLoadingItemCard = true
        try {
          const response = await axios.get(`/api/v1/stock/items/${itemId}/card`, { params })
          const data = response.data.data

          this.itemCard = {
            item: data.item,
            warehouse: data.warehouse,
            filters: data.filters,
            opening_balance: data.opening_balance,
            movements: data.movements || [],
            closing_balance: data.closing_balance,
          }

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoadingItemCard = false
        }
      },

      /**
       * Fetch Warehouse Inventory
       *
       * @param {number} warehouseId - Warehouse ID
       * @param {object} params - Optional filters (as_of_date, search)
       */
      async fetchWarehouseInventory(warehouseId, params = {}) {
        this.isLoadingInventory = true
        try {
          const response = await axios.get(`/api/v1/stock/warehouses/${warehouseId}/inventory`, { params })
          const data = response.data.data

          this.warehouseInventory = {
            warehouse: data.warehouse,
            as_of_date: data.as_of_date,
            items: data.items || [],
            totals: data.totals,
          }

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoadingInventory = false
        }
      },

      /**
       * Fetch Inventory Valuation
       *
       * @param {object} params - Optional filters (as_of_date, warehouse_id, group_by)
       */
      async fetchInventoryValuation(params = {}) {
        this.isLoadingValuation = true
        try {
          const response = await axios.get('/api/v1/stock/inventory-valuation', { params })
          const data = response.data.data

          this.inventoryValuation = {
            as_of_date: data.as_of_date,
            group_by: data.group_by,
            warehouses: data.warehouses || [],
            items: data.items || [],
            grand_total: data.grand_total,
          }

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoadingValuation = false
        }
      },

      /**
       * Fetch Inventory List (for physical counting)
       *
       * @param {object} params - Optional filters (as_of_date, warehouse_id)
       */
      async fetchInventoryList(params = {}) {
        this.isLoadingList = true
        try {
          const response = await axios.get('/api/v1/stock/inventory-list', { params })
          const data = response.data.data

          this.inventoryList = {
            as_of_date: data.as_of_date,
            warehouse_id: data.warehouse_id,
            items: data.items || [],
            total_items: data.total_items,
          }

          return response
        } catch (err) {
          handleError(err)
          throw err
        } finally {
          this.isLoadingList = false
        }
      },

      /**
       * Reset Item Card state
       */
      resetItemCard() {
        this.itemCard = {
          item: null,
          warehouse: null,
          filters: { from_date: null, to_date: null },
          opening_balance: { quantity: 0, value: 0 },
          movements: [],
          closing_balance: { quantity: 0, value: 0 },
        }
      },

      /**
       * Reset Warehouse Inventory state
       */
      resetWarehouseInventory() {
        this.warehouseInventory = {
          warehouse: null,
          as_of_date: null,
          items: [],
          totals: { quantity: 0, value: 0 },
        }
      },

      /**
       * Reset Inventory Valuation state
       */
      resetInventoryValuation() {
        this.inventoryValuation = {
          as_of_date: null,
          group_by: 'warehouse',
          warehouses: [],
          items: [],
          grand_total: { quantity: 0, value: 0 },
        }
      },
    },
  })()
}
// CLAUDE-CHECKPOINT
