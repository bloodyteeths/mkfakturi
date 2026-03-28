<template>
  <div class="border-b border-gray-200 mb-6">
    <!-- Desktop: grouped tabs with dividers -->
    <div class="hidden md:flex items-start gap-0 overflow-x-auto scrollbar-hide">
      <!-- Group 1: Преглед (Overview) -->
      <div class="flex-shrink-0">
        <div class="text-[10px] text-gray-400 uppercase tracking-wider px-1 mb-1">Преглед</div>
        <div class="flex space-x-4">
          <router-link
            to="/admin/stock"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock')"
          >
            Преглед
          </router-link>
          <router-link
            to="/admin/stock/inventory"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/inventory')"
          >
            {{ $t('stock.inventory') }}
          </router-link>
          <router-link
            to="/admin/stock/item-card"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/item-card')"
          >
            {{ $t('stock.item_card') }}
          </router-link>
          <router-link
            to="/admin/stock/warehouses"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/warehouses')"
          >
            {{ $t('navigation.warehouses') }}
          </router-link>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-r border-gray-200 mx-3 h-10 self-end"></div>

      <!-- Group 2: Документи (Documents) -->
      <div class="flex-shrink-0">
        <div class="text-[10px] text-gray-400 uppercase tracking-wider px-1 mb-1">Документи</div>
        <div class="flex space-x-4">
          <router-link
            to="/admin/stock/documents"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/documents')"
          >
            Документи
          </router-link>
          <router-link
            to="/admin/stock/trade/nivelacii"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/trade')"
          >
            {{ $t('trade.nivelacii_title', 'Трговски') }}
          </router-link>
          <router-link
            to="/admin/stock/trade/kap"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/trade/kap')"
          >
            {{ $t('trade.tab_kap', 'КАП') }}
          </router-link>
          <router-link
            to="/admin/stock/trade/plt"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/trade/plt')"
          >
            {{ $t('trade.tab_plt', 'ПЛТ') }}
          </router-link>
          <router-link
            to="/admin/stock/counts"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/counts')"
          >
            {{ $t('stock.stocktake', 'Попис') }}
          </router-link>
          <router-link
            to="/admin/stock/adjustments"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/adjustments')"
          >
            {{ $t('stock.adjustments') }}
          </router-link>
        </div>
      </div>

      <!-- Divider -->
      <div class="border-r border-gray-200 mx-3 h-10 self-end"></div>

      <!-- Group 3: Анализа (Analysis) -->
      <div class="flex-shrink-0">
        <div class="text-[10px] text-gray-400 uppercase tracking-wider px-1 mb-1">Анализа</div>
        <div class="flex space-x-4">
          <router-link
            to="/admin/stock/low-stock"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap inline-flex items-center"
            :class="tabClass('/admin/stock/low-stock')"
          >
            {{ $t('stock.low_stock') }}
            <span
              v-if="criticalCount > 0"
              class="ml-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-600 rounded-full"
            >
              {{ criticalCount }}
            </span>
          </router-link>
          <router-link
            to="/admin/stock/wac-audit"
            class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap"
            :class="tabClass('/admin/stock/wac-audit')"
          >
            {{ $t('wac_audit.title', 'WAC Ревизија') }}
          </router-link>
        </div>
      </div>
    </div>

    <!-- Mobile: scrollable strip with smaller text, no group labels -->
    <nav class="md:hidden -mb-px flex overflow-x-auto scrollbar-hide">
      <router-link
        to="/admin/stock"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock')"
      >
        Преглед
      </router-link>
      <router-link
        to="/admin/stock/inventory"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/inventory')"
      >
        {{ $t('stock.inventory') }}
      </router-link>
      <router-link
        to="/admin/stock/item-card"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/item-card')"
      >
        {{ $t('stock.item_card') }}
      </router-link>
      <router-link
        to="/admin/stock/warehouses"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/warehouses')"
      >
        {{ $t('navigation.warehouses') }}
      </router-link>
      <router-link
        to="/admin/stock/documents"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/documents')"
      >
        Документи
      </router-link>
      <router-link
        to="/admin/stock/trade/nivelacii"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/trade')"
      >
        {{ $t('trade.nivelacii_title', 'Трговски') }}
      </router-link>
      <router-link
        to="/admin/stock/trade/kap"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/trade/kap')"
      >
        {{ $t('trade.tab_kap', 'КАП') }}
      </router-link>
      <router-link
        to="/admin/stock/trade/plt"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/trade/plt')"
      >
        {{ $t('trade.tab_plt', 'ПЛТ') }}
      </router-link>
      <router-link
        to="/admin/stock/counts"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/counts')"
      >
        {{ $t('stock.stocktake', 'Попис') }}
      </router-link>
      <router-link
        to="/admin/stock/adjustments"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/adjustments')"
      >
        {{ $t('stock.adjustments') }}
      </router-link>
      <router-link
        to="/admin/stock/low-stock"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap inline-flex items-center"
        :class="tabClass('/admin/stock/low-stock')"
      >
        {{ $t('stock.low_stock') }}
        <span
          v-if="criticalCount > 0"
          class="ml-1 inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full"
        >
          {{ criticalCount }}
        </span>
      </router-link>
      <router-link
        to="/admin/stock/wac-audit"
        class="py-3 px-2 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock/wac-audit')"
      >
        {{ $t('wac_audit.title', 'WAC Ревизија') }}
      </router-link>
    </nav>
  </div>

  <!-- FAB: Quick Action Floating Button -->
  <div class="fixed bottom-6 right-6 z-50">
    <transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 translate-y-2"
    >
      <div
        v-if="showFabMenu"
        class="absolute bottom-14 right-0 bg-white rounded-lg shadow-xl border border-gray-200 p-2 min-w-[200px]"
      >
        <router-link
          to="/admin/stock/documents?create=receipt"
          class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="showFabMenu = false"
        >
          + Приемница
        </router-link>
        <router-link
          to="/admin/stock/documents?create=issue"
          class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="showFabMenu = false"
        >
          + Издатница
        </router-link>
        <router-link
          to="/admin/stock/trade/nivelacii/create"
          class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="showFabMenu = false"
        >
          + Нивелација
        </router-link>
        <router-link
          to="/admin/stock/documents/create?create=return"
          class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="showFabMenu = false"
        >
          + Повратница
        </router-link>
        <router-link
          to="/admin/stock/documents/create?create=write_off"
          class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="showFabMenu = false"
        >
          + Расходување
        </router-link>
        <router-link
          to="/admin/stock/counts?create=true"
          class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="showFabMenu = false"
        >
          + Попис
        </router-link>
        <div class="border-t border-gray-100 my-1"></div>
        <button
          class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 rounded"
          @click="exportCsv"
        >
          Извоз CSV
        </button>
      </div>
    </transition>
    <button
      @click="showFabMenu = !showFabMenu"
      class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-colors flex items-center justify-center"
      :class="{ 'rotate-45': showFabMenu }"
    >
      <svg class="w-6 h-6 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
      </svg>
    </button>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const criticalCount = ref(0)
const showFabMenu = ref(false)

const isActive = (path) => {
  // Exact match for overview page
  if (path === '/admin/stock') {
    return route.path === '/admin/stock' || route.path === '/admin/stock/'
  }
  // Exact match for inventory (prevent overlap with /admin/stock prefix)
  if (path === '/admin/stock/inventory') {
    return route.path === '/admin/stock/inventory' || route.path === '/admin/stock/inventory/'
  }
  if (path === '/admin/stock/trade') {
    return route.path.startsWith('/admin/stock/trade/nivelacii') || route.path === '/admin/stock/trade'
  }
  return route.path.startsWith(path)
}

const tabClass = (path) => {
  return isActive(path)
    ? 'border-primary-500 text-primary-600'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
}

const fetchCriticalCount = async () => {
  try {
    const { data } = await window.axios.get('/stock/low-stock', {
      params: { severity: 'critical', limit: 1, page: 1 },
    })
    criticalCount.value = data.meta?.total ?? 0
  } catch {
    criticalCount.value = 0
  }
}

const exportCsv = () => {
  showFabMenu.value = false
  window.dispatchEvent(new CustomEvent('stock:export-csv'))
}

const closeFabOnClickOutside = (e) => {
  if (showFabMenu.value && !e.target.closest('.fixed.bottom-6')) {
    showFabMenu.value = false
  }
}

onMounted(() => {
  fetchCriticalCount()
  document.addEventListener('click', closeFabOnClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', closeFabOnClickOutside)
})
// CLAUDE-CHECKPOINT
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
.rotate-45 svg {
  transform: rotate(45deg);
}
</style>
