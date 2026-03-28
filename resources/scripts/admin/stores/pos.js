import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import axios from 'axios'

export const usePosStore = defineStore('pos', () => {
  // --- State ---
  const cart = ref([])
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
  const catalogLoaded = ref(false)

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
  function addItem(item) {
    const existing = cart.value.find(i => i.item_id === item.id)
    if (existing) {
      existing.quantity++
    } else {
      cart.value.push({
        item_id: item.id,
        name: item.name,
        price: item.retail_price || item.price || 0,
        quantity: 1,
        tax_percent: item.tax_percent || 0,
        discount: 0,
        unit_name: item.unit_name || '',
        track_quantity: item.track_quantity,
        available_qty: item.quantity || 0,
        barcode: item.barcode,
      })
    }
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
    } catch (e) {
      console.error('Failed to load POS catalog:', e)
    } finally {
      catalogLoaded.value = true
    }
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

  async function completeSale(fiscalDeviceId = null) {
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

  // --- Park/Resume Sales ---
  function parkSale() {
    if (cart.value.length === 0) return
    const parked = {
      id: Date.now(),
      items: [...cart.value],
      customer: customer.value,
      parkedAt: new Date().toISOString(),
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
      if (saved) parkedSales.value = JSON.parse(saved)
    } catch (e) {
      parkedSales.value = []
    }
  }

  return {
    // State
    cart, customer, paymentMethod, cashReceived, isProcessing,
    catalog, categories, taxTypes, lastSale, posUsage,
    currentShift, parkedSales, searchQuery, selectedCategory,
    catalogLoaded,
    // Getters
    cartItemCount, cartSubTotal, cartTax, cartDiscount, cartTotal,
    changeAmount, filteredCatalog, isLimitApproaching, isLimitReached,
    // Actions
    addItem, removeItem, updateQuantity, updateDiscount, clearCart,
    loadCatalog, lookupBarcode, completeSale, processReturn,
    openShift, closeShift, fetchCurrentShift,
    parkSale, resumeSale, loadParkedSales,
  }
})

// CLAUDE-CHECKPOINT
