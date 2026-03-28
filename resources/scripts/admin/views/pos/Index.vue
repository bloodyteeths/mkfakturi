<template>
  <div class="flex flex-col h-full">
    <!-- Top Bar -->
    <PosTopBar
      :shift="posStore.currentShift"
      :usage="posStore.posUsage"
      :fiscal-connected="fiscal.isConnected.value"
      @open-shift="showShiftOpen = true"
      @close-shift="showShiftClose = true"
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
      <div class="flex-1 lg:w-3/5 flex flex-col overflow-hidden border-r border-gray-200 dark:border-gray-700">
        <!-- Search Bar -->
        <PosSearchBar
          ref="searchBarRef"
          v-model="posStore.searchQuery"
          @barcode="handleBarcode"
        />

        <!-- Category Tabs -->
        <div class="flex gap-2 px-4 py-2 overflow-x-auto border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shrink-0">
          <button
            class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
            :class="!posStore.selectedCategory ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'"
            @click="posStore.selectedCategory = null"
          >
            {{ t('pos.all_categories') }}
          </button>
          <button
            v-for="cat in posStore.categories"
            :key="cat.id"
            class="px-3 py-1.5 rounded-full text-sm font-medium whitespace-nowrap transition-colors"
            :class="posStore.selectedCategory === cat.id ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200'"
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
      @update:payment-method="posStore.paymentMethod = $event"
      @update:cash-received="posStore.cashReceived = $event"
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

    <!-- Parked Sales Modal -->
    <PosParkedModal
      v-if="showParked"
      :sales="posStore.parkedSales"
      @resume="handleResume"
      @close="showParked = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { usePosStore } from '@/scripts/admin/stores/pos'
import { useFiscalPrinter } from '@/scripts/admin/composables/useFiscalPrinter'
import PosTopBar from './components/TopBar.vue'
import PosSearchBar from './components/SearchBar.vue'
import PosProductGrid from './components/ProductGrid.vue'
import PosCart from './components/Cart.vue'
import PosPaymentModal from './components/PaymentModal.vue'
import PosReceiptModal from './components/ReceiptModal.vue'
import PosShiftModal from './components/ShiftModal.vue'
import PosParkedModal from './components/ParkedModal.vue'

const { t } = useI18n()
const router = useRouter()
const posStore = usePosStore()
const fiscal = useFiscalPrinter()

const showPayment = ref(false)
const showShiftOpen = ref(false)
const showShiftClose = ref(false)
const showParked = ref(false)
const showUpgrade = ref(false)
const searchBarRef = ref(null)

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

// --- Handlers ---
async function handleBarcode(code) {
  const result = await posStore.lookupBarcode(code)
  if (result.success) {
    playBeep(800, 100)
  } else {
    playBeep(300, 200)
  }
}

async function handlePayment() {
  try {
    const sale = await posStore.completeSale()
    if (sale) {
      showPayment.value = false
      playBeep(1200, 150) // cha-ching

      // Auto-fiscalize if fiscal printer is connected
      if (fiscal.isConnected.value && sale.invoice?.id) {
        try {
          await fiscal.fiscalizeInvoice(sale.invoice, null)
        } catch (fiscalErr) {
          console.warn('Fiscal print failed (sale still recorded):', fiscalErr.message)
        }
      }
    }
  } catch (e) {
    alert(t('pos.sale_failed') + ': ' + e.message)
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
    await fiscal.fiscalizeInvoice(posStore.lastSale.invoice, null)
  } catch (e) {
    console.warn('Reprint failed:', e.message)
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

function exitPos() {
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
  // Escape = close modals
  if (e.key === 'Escape') {
    showPayment.value = false
    showShiftOpen.value = false
    showShiftClose.value = false
    showParked.value = false
    posStore.lastSale = null
  }
}

onMounted(async () => {
  await posStore.loadCatalog()
  await posStore.fetchCurrentShift()
  posStore.loadParkedSales()
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<!-- CLAUDE-CHECKPOINT -->
