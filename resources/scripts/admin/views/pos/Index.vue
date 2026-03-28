<template>
  <div class="flex flex-col h-full">
    <!-- Top Bar -->
    <PosTopBar
      :shift="posStore.currentShift"
      :usage="posStore.posUsage"
      :fiscal-connected="fiscal.isConnected.value"
      :return-enabled="posStore.posSettings.return_enabled"
      :restaurant-mode="posStore.posSettings.restaurant_mode"
      :warehouses="posStore.warehouses"
      :selected-warehouse="posStore.selectedWarehouse"
      @open-shift="showShiftOpen = true"
      @close-shift="showShiftClose = true"
      @open-return="showReturn = true"
      @warehouse-change="posStore.selectedWarehouse = $event"
      @open-receipt-history="showReceiptHistory = true"
      @open-cash-drawer="showCashDrawer = true"
      @x-report="handleXReport"
      @exit="exitPos"
    />

    <!-- Limit Warning Banner -->
    <div
      v-if="posStore.isLimitApproaching && !posStore.isLimitReached"
      class="bg-yellow-50 border-b border-yellow-200 px-4 py-1.5 text-sm text-yellow-800 flex items-center justify-between"
    >
      <span>{{ t('pos.limit_warning') }} — {{ posStore.posUsage.used }}/{{ posStore.posUsage.limit }}</span>
    </div>
    <div
      v-if="posStore.isLimitReached"
      class="bg-red-50 border-b border-red-200 px-4 py-2 text-sm text-red-800 flex items-center justify-between"
    >
      <span>{{ t('pos.limit_reached') }}</span>
      <button class="text-red-600 font-medium underline" @click="showUpgrade = true">
        {{ t('pos.upgrade_prompt', { plan: 'Starter', limit: 60, price: 12 }) }}
      </button>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:flex-row overflow-hidden">
      <!-- Left: Products -->
      <div class="flex-1 lg:w-3/5 flex flex-col overflow-hidden border-r border-gray-100 dark:border-gray-800">
        <!-- Table Map (restaurant mode) -->
        <div v-if="posStore.posSettings.restaurant_mode && !posStore.activeTable" class="flex-1 overflow-y-auto">
          <div class="px-5 py-3 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
            <h2 class="font-bold text-gray-900 dark:text-white">{{ t('pos_settings.restaurant_mode') || 'Tables' }}</h2>
          </div>
          <PosTableMap
            :table-count="posStore.posSettings.table_count || 20"
            :table-orders="posStore.tableOrders"
            @select-table="handleSelectTable"
          />
        </div>

        <!-- Product area (shown when no restaurant mode, or table is selected) -->
        <template v-if="!posStore.posSettings.restaurant_mode || posStore.activeTable">
        <!-- Back to tables button (restaurant mode) -->
        <button
          v-if="posStore.posSettings.restaurant_mode && posStore.activeTable"
          class="flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 border-b border-gray-100 dark:border-gray-800 transition-colors shrink-0"
          @click="backToTables"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Table {{ posStore.activeTable }}
        </button>

        <!-- Search Bar -->
        <PosSearchBar
          ref="searchBarRef"
          v-model="posStore.searchQuery"
          :barcode-camera-enabled="posStore.posSettings.barcode_camera"
          :qty-multiplier="posStore.qtyMultiplier"
          @barcode="handleBarcode"
          @set-multiplier="posStore.qtyMultiplier = $event"
        />

        <!-- Category Tabs -->
        <div class="flex gap-2 px-4 py-2.5 overflow-x-auto border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-800 shrink-0 scrollbar-hide">
          <button
            class="px-4 py-1.5 rounded-full text-sm font-bold whitespace-nowrap transition-all duration-200"
            :class="!posStore.selectedCategory
              ? 'bg-primary-500 text-white shadow-md shadow-primary-500/20'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            @click="posStore.selectedCategory = null"
          >
            {{ t('pos.all_categories') }}
          </button>
          <button
            v-for="cat in posStore.categories"
            :key="cat.id"
            class="px-4 py-1.5 rounded-full text-sm font-bold whitespace-nowrap transition-all duration-200"
            :class="posStore.selectedCategory === cat.id
              ? 'bg-primary-500 text-white shadow-md shadow-primary-500/20'
              : 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
            @click="posStore.selectedCategory = cat.id"
          >
            {{ cat.name }}
          </button>
        </div>

        <!-- Product Grid -->
        <PosProductGrid
          :items="posStore.filteredCatalog"
          :loading="!posStore.catalogLoaded"
          @select="posStore.addItem($event)"
        />
        </template>
      </div>

      <!-- Right: Cart -->
      <div class="lg:w-2/5 flex flex-col bg-white dark:bg-gray-800 overflow-hidden">
        <PosCart
          :items="posStore.cart"
          :sub-total="posStore.cartSubTotal"
          :tax="posStore.cartTax"
          :discount="posStore.cartDiscount"
          :total="posStore.cartTotal"
          :item-count="posStore.cartItemCount"
          :is-processing="posStore.isProcessing"
          :limit-reached="posStore.isLimitReached"
          :parked-count="posStore.parkedSales.length"
          @update-qty="({ index, qty }) => posStore.updateQuantity(index, qty)"
          @remove="posStore.removeItem($event)"
          @clear="confirmClear"
          @pay="showPayment = true"
          @park="parkCurrentSale"
          @show-parked="showParked = true"
        />
      </div>
    </div>

    <!-- Payment Modal -->
    <PosPaymentModal
      v-if="showPayment"
      :total="posStore.cartTotal"
      :payment-method="posStore.paymentMethod"
      :cash-received="posStore.cashReceived"
      :change="posStore.changeAmount"
      :is-processing="posStore.isProcessing"
      :split-enabled="posStore.posSettings.split_payment"
      :casys-enabled="posStore.posSettings.casys_qr"
      @update:payment-method="posStore.paymentMethod = $event"
      @update:cash-received="posStore.cashReceived = $event"
      @update:split-amounts="splitAmounts = $event"
      @confirm="handlePayment"
      @close="showPayment = false"
    />

    <!-- Receipt Modal -->
    <PosReceiptModal
      v-if="posStore.lastSale"
      :sale="posStore.lastSale"
      @new-sale="posStore.lastSale = null"
      @print="handlePrintReceipt"
      @close="posStore.lastSale = null"
    />

    <!-- Shift Open Modal -->
    <PosShiftModal
      v-if="showShiftOpen"
      mode="open"
      @confirm="handleOpenShift"
      @close="showShiftOpen = false"
    />

    <!-- Shift Close Modal -->
    <PosShiftModal
      v-if="showShiftClose"
      mode="close"
      :shift="posStore.currentShift"
      @confirm="handleCloseShift"
      @close="showShiftClose = false"
    />

    <!-- Return Modal -->
    <PosReturnModal
      v-if="showReturn"
      @close="showReturn = false"
    />

    <!-- Cash In/Out Modal -->
    <PosCashDrawerModal
      :show="showCashDrawer"
      @close="showCashDrawer = false"
      @confirm="handleCashInOut"
    />

    <!-- Receipt History Modal -->
    <PosReceiptHistoryModal
      :show="showReceiptHistory"
      @close="showReceiptHistory = false"
      @reprint="handleReprintFromHistory"
    />

    <!-- Parked Sales Modal -->
    <PosParkedModal
      v-if="showParked"
      :sales="posStore.parkedSales"
      @resume="handleResume"
      @close="showParked = false"
    />

    <!-- Mobile Action Bar (touch alternatives for keyboard shortcuts) -->
    <div class="lg:hidden fixed bottom-0 left-0 right-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 flex items-center justify-around py-2 px-4 z-40 safe-area-bottom">
      <button
        class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
        @click="searchBarRef?.focus()"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span class="text-[10px] font-medium">{{ t('pos.search_or_scan') ? t('pos.search_or_scan').split(' ')[0] : 'Search' }}</span>
      </button>
      <button
        v-if="posStore.cart.length > 0"
        class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
        @click="showPayment = true"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <span class="text-[10px] font-bold">{{ t('pos.pay') || 'Pay' }}</span>
      </button>
      <button
        v-if="posStore.cart.length > 0"
        class="flex flex-col items-center gap-0.5 px-3 py-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
        @click="confirmClear"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
        </svg>
        <span class="text-[10px] font-medium">{{ t('pos.clear_cart') || 'Clear' }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { usePosStore } from '@/scripts/admin/stores/pos'
import { useFiscalPrinter } from '@/scripts/admin/composables/useFiscalPrinter'
import { useFiscalDeviceStore } from '@/scripts/admin/stores/fiscal-device'
import PosTopBar from './components/TopBar.vue'
import PosSearchBar from './components/SearchBar.vue'
import PosProductGrid from './components/ProductGrid.vue'
import PosCart from './components/Cart.vue'
import PosPaymentModal from './components/PaymentModal.vue'
import PosReceiptModal from './components/ReceiptModal.vue'
import PosShiftModal from './components/ShiftModal.vue'
import PosParkedModal from './components/ParkedModal.vue'
import PosReturnModal from './components/ReturnModal.vue'
import PosReceiptHistoryModal from './components/ReceiptHistoryModal.vue'
import PosCashDrawerModal from './components/CashDrawerModal.vue'
import PosTableMap from './components/TableMap.vue'

const { t } = useI18n()
const router = useRouter()
const posStore = usePosStore()
const fiscal = useFiscalPrinter()
let fiscalDeviceStore = null

const showPayment = ref(false)
const showShiftOpen = ref(false)
const showShiftClose = ref(false)
const showParked = ref(false)
const showReturn = ref(false)
const showReceiptHistory = ref(false)
const showCashDrawer = ref(false)
const showUpgrade = ref(false)
const searchBarRef = ref(null)
const splitAmounts = ref({ cash_amount: 0, card_amount: 0 })

// --- Audio feedback ---
function playBeep(freq = 800, duration = 100) {
  try {
    const ctx = new AudioContext()
    const osc = ctx.createOscillator()
    const gain = ctx.createGain()
    osc.connect(gain)
    gain.connect(ctx.destination)
    osc.frequency.value = freq
    gain.gain.value = 0.1
    osc.start()
    osc.stop(ctx.currentTime + duration / 1000)
  } catch (e) { /* silent fail */ }
}

// --- Weight barcode parser (prefix 27/28 EAN-13) ---
function parseWeightBarcode(code) {
  if (code.length !== 13) return null
  const prefix = code.substring(0, 2)
  if (prefix !== '27' && prefix !== '28') return null
  const itemCode = code.substring(2, 7)
  const weightRaw = parseInt(code.substring(7, 12), 10)
  if (isNaN(weightRaw) || weightRaw <= 0) return null
  const weightKg = weightRaw / 1000
  return { itemCode, weightKg }
}

// --- Handlers ---
async function handleBarcode(code) {
  // Check for embedded weight barcode (prefix 27/28)
  const weightData = parseWeightBarcode(code)
  if (weightData) {
    const pluResult = posStore.lookupPlu(weightData.itemCode)
    if (pluResult.success) {
      // Update last added item's quantity to the weight
      const lastIdx = posStore.cart.length - 1
      if (lastIdx >= 0) {
        posStore.updateQuantity(lastIdx, weightData.weightKg)
      }
      playBeep(800, 100)
      return
    }
  }

  // First try PLU lookup from loaded catalog
  const pluResult = posStore.lookupPlu(code)
  if (pluResult.success) {
    playBeep(800, 100)
    return
  }

  // Then try server barcode lookup
  const result = await posStore.lookupBarcode(code)
  if (result.success) {
    playBeep(800, 100)
  } else {
    playBeep(300, 200)
  }
}

async function handlePayment() {
  try {
    const sale = await posStore.completeSale(null, splitAmounts.value)
    if (sale) {
      showPayment.value = false
      playBeep(1200, 150)

      // Clear table in restaurant mode
      if (posStore.posSettings.restaurant_mode && posStore.activeTable) {
        posStore.clearTable(posStore.activeTable)
      }

      // Auto-fiscalize if fiscal printer is connected
      if (fiscal.isConnected.value && sale.invoice?.id) {
        try {
          const deviceId = fiscalDeviceStore?.fiscalDevices?.find(d => d.is_active)?.id || null
          if (deviceId) {
            await fiscal.fiscalizeInvoice(sale.invoice, deviceId)
          }
        } catch (fiscalErr) {
          console.warn('Fiscal print failed (sale still recorded):', fiscalErr.message)
        }
      }
    }
  } catch (e) {
    if (e.response?.status === 402) {
      // Show upgrade prompt instead of alert
      const data = e.response.data
      if (confirm(
        (data.message || t('pos.limit_reached')) + '\n\n' + t('pos.upgrade_prompt', { plan: data.suggested_plan?.name || 'Business', limit: data.suggested_plan?.pos_limit || '3000', price: data.suggested_plan?.price || '59' })
      )) {
        window.location.href = '/admin/settings/billing'
      }
    } else {
      alert(t('pos.sale_failed') + ': ' + (e.response?.data?.error || e.message))
    }
  }
}

async function handleOpenShift(openingCash) {
  try {
    await posStore.openShift(openingCash)
    showShiftOpen.value = false
  } catch (e) {
    alert(e.message)
  }
}

async function handleCloseShift({ closingCash, notes }) {
  try {
    await posStore.closeShift(closingCash, notes)
    showShiftClose.value = false
  } catch (e) {
    alert(e.message)
  }
}

async function handlePrintReceipt() {
  if (!fiscal.isConnected.value || !posStore.lastSale?.invoice) return
  try {
    const deviceId = fiscalDeviceStore?.fiscalDevices?.find(d => d.is_active)?.id || null
    if (deviceId) {
      await fiscal.fiscalizeInvoice(posStore.lastSale.invoice, deviceId)
    }
  } catch (e) {
    console.warn('Reprint failed:', e.message)
  }
}

async function handleReprintFromHistory(receipt) {
  if (!fiscal.isConnected.value) {
    alert(t('pos.fiscal_not_connected') || 'No fiscal device connected')
    return
  }
  try {
    const deviceId = fiscalDeviceStore?.fiscalDevices?.find(d => d.is_active)?.id || null
    if (deviceId && receipt.invoice_id) {
      await fiscal.fiscalizeInvoice({ id: receipt.invoice_id, ...receipt.invoice }, deviceId)
      showReceiptHistory.value = false
    }
  } catch (e) {
    console.warn('Reprint from history failed:', e.message)
  }
}

function handleCashInOut(entry) {
  // Store in pos store's cash transactions for the current shift
  const transactions = JSON.parse(localStorage.getItem('pos_cash_transactions') || '[]')
  transactions.push(entry)
  localStorage.setItem('pos_cash_transactions', JSON.stringify(transactions))
  showCashDrawer.value = false
}

async function handleXReport() {
  if (!fiscal.isConnected.value) return
  try {
    await fiscal.xReport()
  } catch (e) {
    console.warn('X-Report failed:', e.message)
  }
}

function confirmClear() {
  if (confirm(t('pos.confirm_clear'))) {
    posStore.clearCart()
  }
}

function parkCurrentSale() {
  posStore.parkSale()
}

function handleResume(parkedId) {
  posStore.resumeSale(parkedId)
  showParked.value = false
}

function handleSelectTable(tableNumber) {
  posStore.selectTable(tableNumber)
}

function backToTables() {
  if (posStore.activeTable && posStore.cart.length > 0) {
    posStore.saveTableOrder(posStore.activeTable)
  }
  posStore.activeTable = null
  posStore.cart = []
  posStore.customer = null
}

function exitPos() {
  // Save current table order before exiting
  if (posStore.activeTable && posStore.cart.length > 0) {
    posStore.saveTableOrder(posStore.activeTable)
  }
  router.push('/admin/dashboard')
}

// --- Keyboard shortcuts ---
function handleKeydown(e) {
  // F1 = focus search
  if (e.key === 'F1') {
    e.preventDefault()
    searchBarRef.value?.focus()
  }
  // F2 = pay
  if (e.key === 'F2' && posStore.cart.length > 0) {
    e.preventDefault()
    showPayment.value = true
  }
  // F3 = clear
  if (e.key === 'F3') {
    e.preventDefault()
    confirmClear()
  }
  // F11 = receipt history / reprint
  if (e.key === 'F11') {
    e.preventDefault()
    showReceiptHistory.value = true
  }
  // Escape = close modals
  if (e.key === 'Escape') {
    showPayment.value = false
    showShiftOpen.value = false
    showShiftClose.value = false
    showParked.value = false
    showReturn.value = false
    showReceiptHistory.value = false
    posStore.lastSale = null
  }
}

onMounted(async () => {
  await posStore.loadCatalog()
  await posStore.fetchCurrentShift()
  posStore.loadParkedSales()
  posStore.loadTableOrders()
  try {
    fiscalDeviceStore = useFiscalDeviceStore()
    fiscalDeviceStore.fetchFiscalDevices().catch(() => {})
  } catch (e) {
    console.warn('Fiscal device store init failed:', e.message)
  }
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<!-- CLAUDE-CHECKPOINT -->
