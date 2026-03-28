import { defineStore } from 'pinia'
import { computed, ref, watch } from 'vue'
import axios from 'axios'

export const usePosStore = defineStore('pos', () => {
  // --- State ---
  const cart = ref([])

  // Restore cart from localStorage on init
  try {
    const savedCart = localStorage.getItem('pos_cart_backup')
    if (savedCart) {
      cart.value = JSON.parse(savedCart)
    }
  } catch (e) { /* ignore parse errors */ }

  // Persist cart to localStorage on changes
  watch(cart, (newCart) => {
    try {
      localStorage.setItem('pos_cart_backup', JSON.stringify(newCart))
    } catch (e) { /* ignore quota errors */ }
  }, { deep: true })

  // Persist selected warehouse to localStorage
  watch(selectedWarehouse, (wh) => {
    try {
      if (wh) localStorage.setItem('pos_selected_warehouse', String(wh))
      else localStorage.removeItem('pos_selected_warehouse')
    } catch (e) { /* ignore */ }
  })

  const customer = ref(null)
  const paymentMethod = ref('cash')
  const cashReceived = ref(0)
  const isProcessing = ref(false)
  const catalog = ref([])
  const categories = ref([])
  const taxTypes = ref([])
  const lastSale = ref(null)
  const posUsage = ref({ used: 0, limit: 30, remaining: 30 })
  const currentShift = ref(null)
  const parkedSales = ref([])
  const searchQuery = ref('')
  const selectedCategory = ref(null)
  const qtyMultiplier = ref(1)
  const catalogLoaded = ref(false)
  const warehouses = ref([])
  const selectedWarehouse = ref(
    (() => { try { const v = localStorage.getItem('pos_selected_warehouse'); return v ? Number(v) : null } catch { return null } })()
  )
  const posSettings = ref({
    numpad_enabled: true,
    sound_enabled: true,
    restaurant_mode: false,
    table_count: 20,
    kitchen_printing: false,
    split_payment: false,
    return_enabled: false,
    casys_qr: false,
    barcode_camera: false,
    auto_print: false,
    show_vat: true,
  })

  // --- Getters ---
  const cartItemCount = computed(() =>
    cart.value.reduce((sum, item) => sum + item.quantity, 0)
  )

  const cartSubTotal = computed(() =>
    cart.value.reduce((sum, item) => sum + item.price * item.quantity, 0)
  )

  const cartTax = computed(() =>
    cart.value.reduce((sum, item) => {
      const itemTotal = item.price * item.quantity
      const discount = item.discount ? Math.round(itemTotal * item.discount / 100) : 0
      return sum + Math.round((itemTotal - discount) * item.tax_percent / 100)
    }, 0)
  )

  const cartDiscount = computed(() =>
    cart.value.reduce((sum, item) => {
      const itemTotal = item.price * item.quantity
      return sum + (item.discount ? Math.round(itemTotal * item.discount / 100) : 0)
    }, 0)
  )

  const cartTotal = computed(() => cartSubTotal.value - cartDiscount.value + cartTax.value)

  const changeAmount = computed(() =>
    Math.max(0, cashReceived.value - cartTotal.value)
  )

  const filteredCatalog = computed(() => {
    let items = catalog.value
    if (selectedCategory.value) {
      items = items.filter(i => i.category_id === selectedCategory.value)
    }
    if (searchQuery.value) {
      const q = searchQuery.value.toLowerCase()
      items = items.filter(i =>
        i.name.toLowerCase().includes(q) ||
        (i.barcode && i.barcode.includes(q)) ||
        (i.sku && i.sku.toLowerCase().includes(q))
      )
    }
    return items
  })

  const isLimitApproaching = computed(() => {
    if (!posUsage.value.limit) return false
    return posUsage.value.used >= posUsage.value.limit * 0.8
  })

  const isLimitReached = computed(() => {
    if (!posUsage.value.limit) return false
    return posUsage.value.remaining <= 0
  })

  // --- Actions ---
  function addItem(item, qty = null) {
    const addQty = qty || qtyMultiplier.value || 1
    const existing = cart.value.find(i => i.item_id === item.id)
    if (existing) {
      existing.quantity += addQty
    } else {
      cart.value.push({
        item_id: item.id,
        name: item.name,
        price: item.retail_price || item.price || 0,
        quantity: addQty,
        tax_percent: item.tax_percent || 0,
        discount: 0,
        unit_name: item.unit_name || '',
        track_quantity: item.track_quantity,
        available_qty: item.quantity || 0,
        barcode: item.barcode,
      })
    }
    qtyMultiplier.value = 1 // Reset after use
  }

  function removeItem(index) {
    cart.value.splice(index, 1)
  }

  function updateQuantity(index, qty) {
    if (qty <= 0) {
      removeItem(index)
    } else {
      cart.value[index].quantity = qty
    }
  }

  function updateDiscount(index, percent) {
    cart.value[index].discount = Math.max(0, Math.min(100, percent))
  }

  function clearCart() {
    cart.value = []
    customer.value = null
    cashReceived.value = 0
    paymentMethod.value = 'cash'
  }

  async function loadCatalog() {
    try {
      const { data } = await axios.get('/pos/catalog')
      catalog.value = data.items || []
      categories.value = data.categories || []
      taxTypes.value = data.tax_types || []
      posUsage.value = data.pos_usage || posUsage.value
      if (data.pos_settings) posSettings.value = data.pos_settings
      warehouses.value = data.warehouses || []
      // Default to the default warehouse or the first one (validate persisted selection)
      if (warehouses.value.length) {
        const valid = warehouses.value.find(w => w.id === selectedWarehouse.value)
        if (!valid) {
          const defaultWh = warehouses.value.find(w => w.is_default)
          selectedWarehouse.value = defaultWh ? defaultWh.id : warehouses.value[0].id
        }
      }
    } catch (e) {
      console.error('Failed to load POS catalog:', e)
    } finally {
      catalogLoaded.value = true
    }
  }

  function lookupPlu(code) {
    // Search catalog by SKU, PLU code, or item ID matching the typed number
    const numCode = code.replace(/^0+/, '') // Remove leading zeros for matching
    const item = catalog.value.find(i =>
      i.sku === code ||
      i.sku === numCode ||
      (i.plu_code && i.plu_code === code) ||
      String(i.id) === numCode
    )
    if (item) {
      addItem(item)
      return { success: true, item }
    }
    return { success: false }
  }

  async function lookupBarcode(code) {
    try {
      const { data } = await axios.get(`/pos/barcode/${encodeURIComponent(code)}`)
      if (data.item) {
        addItem(data.item)
        return { success: true, item: data.item }
      }
      return { success: false }
    } catch (e) {
      return { success: false, error: e.response?.data?.error || 'Not found' }
    }
  }

  async function completeSale(fiscalDeviceId = null, splitAmountsData = null) {
    if (cart.value.length === 0 || isProcessing.value) return null
    isProcessing.value = true

    try {
      const saleData = {
        items: cart.value.map(item => ({
          item_id: item.item_id,
          quantity: item.quantity,
          price: item.price,
          discount: item.discount || 0,
        })),
        customer_id: customer.value?.id || null,
        payment_method: paymentMethod.value,
        cash_received: paymentMethod.value === 'cash' ? cashReceived.value : cartTotal.value,
        fiscal_device_id: fiscalDeviceId,
        warehouse_id: selectedWarehouse.value || null,
      }

      // Add split payment amounts
      if (paymentMethod.value === 'mixed' && splitAmountsData) {
        saleData.cash_amount = splitAmountsData.cash_amount
        saleData.card_amount = splitAmountsData.card_amount
      }

      const { data } = await axios.post('/pos/sale', saleData)

      lastSale.value = {
        invoice: data.invoice,
        payment: data.payment,
        fiscal_data: data.fiscal_data,
        stock_warnings: data.stock_warnings,
        change: data.payment.change,
      }

      // Update usage
      if (posUsage.value.limit) {
        posUsage.value.used++
        posUsage.value.remaining = Math.max(0, posUsage.value.remaining - 1)
      }

      clearCart()
      localStorage.removeItem('pos_cart_backup')
      return lastSale.value
    } catch (e) {
      const error = e.response?.data?.error || e.message
      throw new Error(error)
    } finally {
      isProcessing.value = false
    }
  }

  async function processReturn(invoiceId, items = null, reason = '') {
    try {
      const { data } = await axios.post('/pos/return', {
        invoice_id: invoiceId,
        items,
        reason,
      })
      return data
    } catch (e) {
      throw new Error(e.response?.data?.error || 'Return failed')
    }
  }

  // --- Shift Management ---
  async function openShift(openingCash, fiscalDeviceId = null) {
    try {
      const { data } = await axios.post('/pos/shift/open', {
        opening_cash: openingCash,
        fiscal_device_id: fiscalDeviceId,
      })
      currentShift.value = data.shift
      return data.shift
    } catch (e) {
      throw new Error(e.response?.data?.error || 'Failed to open shift')
    }
  }

  async function closeShift(closingCash, notes = '') {
    try {
      const { data } = await axios.post('/pos/shift/close', {
        closing_cash: closingCash,
        notes,
      })
      const summary = data.summary
      currentShift.value = null
      return summary
    } catch (e) {
      throw new Error(e.response?.data?.error || 'Failed to close shift')
    }
  }

  async function fetchCurrentShift() {
    try {
      const { data } = await axios.get('/pos/shift/current')
      currentShift.value = data.shift
      return data
    } catch (e) {
      console.error('Failed to fetch shift:', e)
    }
  }

  // --- Restaurant Table Management ---
  const tableOrders = ref({})
  const activeTable = ref(null)

  function selectTable(tableNumber) {
    // Save current cart to previous table if any
    if (activeTable.value && cart.value.length > 0) {
      saveTableOrder(activeTable.value)
    }

    activeTable.value = tableNumber

    // Load table's existing order into cart
    const order = tableOrders.value[tableNumber]
    if (order && order.items.length > 0) {
      cart.value = [...order.items]
      customer.value = order.customer || null
    } else {
      cart.value = []
      customer.value = null
    }
    cashReceived.value = 0
    paymentMethod.value = 'cash'
  }

  function saveTableOrder(tableNumber) {
    if (cart.value.length === 0) {
      delete tableOrders.value[tableNumber]
    } else {
      tableOrders.value[tableNumber] = {
        items: [...cart.value],
        customer: customer.value,
        total: cartTotal.value,
        updatedAt: new Date().toISOString(),
      }
    }
    persistTableOrders()
  }

  function clearTable(tableNumber) {
    delete tableOrders.value[tableNumber]
    if (activeTable.value === tableNumber) {
      activeTable.value = null
      cart.value = []
      customer.value = null
    }
    persistTableOrders()
  }

  function persistTableOrders() {
    localStorage.setItem('pos_table_orders', JSON.stringify(tableOrders.value))
  }

  function loadTableOrders() {
    try {
      const saved = localStorage.getItem('pos_table_orders')
      if (saved) tableOrders.value = JSON.parse(saved)
    } catch (e) {
      tableOrders.value = {}
    }
  }

  // --- Park/Resume Sales ---
  function parkSale() {
    if (cart.value.length === 0) return
    const parked = {
      id: Date.now(),
      items: [...cart.value],
      customer: customer.value,
      parkedAt: Date.now(),
      total: cartTotal.value,
    }
    parkedSales.value.push(parked)
    clearCart()
    // Persist to localStorage
    localStorage.setItem('pos_parked_sales', JSON.stringify(parkedSales.value))
    return parked
  }

  function resumeSale(parkedId) {
    const index = parkedSales.value.findIndex(s => s.id === parkedId)
    if (index === -1) return
    const parked = parkedSales.value[index]
    cart.value = parked.items
    customer.value = parked.customer
    parkedSales.value.splice(index, 1)
    localStorage.setItem('pos_parked_sales', JSON.stringify(parkedSales.value))
  }

  function loadParkedSales() {
    try {
      const saved = localStorage.getItem('pos_parked_sales')
      if (saved) {
        const stored = JSON.parse(saved)
        const now = Date.now()
        // Filter out parked sales older than 24 hours
        parkedSales.value = stored.filter(s => {
          const parkedTime = typeof s.parkedAt === 'number' ? s.parkedAt : new Date(s.parkedAt).getTime()
          return !parkedTime || (now - parkedTime < 24 * 60 * 60 * 1000)
        })
        // Persist back the filtered list
        if (parkedSales.value.length !== stored.length) {
          localStorage.setItem('pos_parked_sales', JSON.stringify(parkedSales.value))
        }
      }
    } catch (e) {
      parkedSales.value = []
    }
  }

  return {
    // State
    cart, customer, paymentMethod, cashReceived, isProcessing,
    catalog, categories, taxTypes, lastSale, posUsage,
    currentShift, parkedSales, searchQuery, selectedCategory,
    catalogLoaded, posSettings, warehouses, selectedWarehouse, qtyMultiplier,
    // Getters
    cartItemCount, cartSubTotal, cartTax, cartDiscount, cartTotal,
    changeAmount, filteredCatalog, isLimitApproaching, isLimitReached,
    // Actions
    addItem, removeItem, updateQuantity, updateDiscount, clearCart,
    loadCatalog, lookupPlu, lookupBarcode, completeSale, processReturn,
    openShift, closeShift, fetchCurrentShift,
    parkSale, resumeSale, loadParkedSales,
    // Restaurant
    tableOrders, activeTable,
    selectTable, saveTableOrder, clearTable, loadTableOrders,
  }
})

// CLAUDE-CHECKPOINT
