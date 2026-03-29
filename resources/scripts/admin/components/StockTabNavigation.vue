<template>
  <div class="border-b border-gray-200 mb-6">
    <!-- Desktop: 5 nav items with dropdowns -->
    <div class="hidden md:flex items-center gap-1">
      <!-- 1. Преглед — direct link -->
      <router-link
        to="/admin/stock"
        class="py-4 px-3 border-b-2 font-medium text-sm whitespace-nowrap"
        :class="tabClass('/admin/stock')"
      >
        Преглед
      </router-link>

      <!-- 2. Залиха — dropdown -->
      <div class="relative" ref="inventoryRef">
        <button
          @click="toggle('inventory')"
          class="py-4 px-3 border-b-2 font-medium text-sm whitespace-nowrap inline-flex items-center gap-1"
          :class="groupTabClass(['inventory', 'item-card', 'warehouses'])"
        >
          Залиха
          <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openMenu === 'inventory' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="openMenu === 'inventory'" class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[220px] z-50">
            <router-link
              to="/admin/stock/inventory"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/inventory')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('stock.inventory') }}</div>
              <div class="text-xs text-gray-400">Тековна залиха по артикл</div>
            </router-link>
            <router-link
              to="/admin/stock/item-card"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/item-card')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('stock.item_card') }}</div>
              <div class="text-xs text-gray-400">Движења по артикл</div>
            </router-link>
            <router-link
              to="/admin/stock/warehouses"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/warehouses')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('navigation.warehouses') }}</div>
              <div class="text-xs text-gray-400">Управување со магацини</div>
            </router-link>
          </div>
        </transition>
      </div>

      <!-- 3. Документи — dropdown -->
      <div class="relative" ref="documentsRef">
        <button
          @click="toggle('documents')"
          class="py-4 px-3 border-b-2 font-medium text-sm whitespace-nowrap inline-flex items-center gap-1"
          :class="groupTabClass(['documents', 'counts', 'adjustments'])"
        >
          Документи
          <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openMenu === 'documents' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="openMenu === 'documents'" class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[240px] z-50">
            <router-link
              to="/admin/stock/documents"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/documents')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">Магацински документи</div>
              <div class="text-xs text-gray-400">Приемници, издатници, повратници</div>
            </router-link>
            <router-link
              to="/admin/stock/counts"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/counts')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('stock.stocktake', 'Попис') }}</div>
              <div class="text-xs text-gray-400">Пописни листи и записници</div>
            </router-link>
            <router-link
              to="/admin/stock/adjustments"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/adjustments')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('stock.adjustments') }}</div>
              <div class="text-xs text-gray-400">Корекции на залиха</div>
            </router-link>
          </div>
        </transition>
      </div>

      <!-- 4. Трговија — dropdown -->
      <div class="relative" ref="tradeRef">
        <button
          @click="toggle('trade')"
          class="py-4 px-3 border-b-2 font-medium text-sm whitespace-nowrap inline-flex items-center gap-1"
          :class="groupTabClass(['trade'])"
        >
          Трговија
          <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openMenu === 'trade' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="openMenu === 'trade'" class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[260px] z-50">
            <router-link
              to="/admin/stock/trade/nivelacii"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/trade/nivelacii')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('trade.nivelacii_title', 'Нивелации') }}</div>
              <div class="text-xs text-gray-400">Промена на малопродажна цена</div>
            </router-link>
            <router-link
              to="/admin/stock/trade/kap"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/trade/kap')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">КАП — Калкулација</div>
              <div class="text-xs text-gray-400">Калкулација на набавна цена</div>
            </router-link>
            <router-link
              to="/admin/stock/trade/plt"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/trade/plt')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">ПЛТ — Малопродажба</div>
              <div class="text-xs text-gray-400">Пресметка на малопродажна цена</div>
            </router-link>
          </div>
        </transition>
      </div>

      <!-- 5. Анализа — dropdown -->
      <div class="relative" ref="analysisRef">
        <button
          @click="toggle('analysis')"
          class="py-4 px-3 border-b-2 font-medium text-sm whitespace-nowrap inline-flex items-center gap-1"
          :class="groupTabClass(['low-stock', 'wac-audit'])"
        >
          Анализа
          <span
            v-if="criticalCount > 0"
            class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full"
          >
            {{ criticalCount }}
          </span>
          <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': openMenu === 'analysis' }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <transition
          enter-active-class="transition ease-out duration-100"
          enter-from-class="opacity-0 scale-95"
          enter-to-class="opacity-100 scale-100"
          leave-active-class="transition ease-in duration-75"
          leave-from-class="opacity-100 scale-100"
          leave-to-class="opacity-0 scale-95"
        >
          <div v-if="openMenu === 'analysis'" class="absolute left-0 top-full mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[240px] z-50">
            <router-link
              to="/admin/stock/low-stock"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/low-stock')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium inline-flex items-center gap-2">
                {{ $t('stock.low_stock') }}
                <span
                  v-if="criticalCount > 0"
                  class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-red-600 rounded-full"
                >
                  {{ criticalCount }}
                </span>
              </div>
              <div class="text-xs text-gray-400">Критични нивоа на залиха</div>
            </router-link>
            <router-link
              to="/admin/stock/wac-audit"
              class="block px-4 py-2.5 hover:bg-gray-50"
              :class="dropdownItemClass('/admin/stock/wac-audit')"
              @click="openMenu = null"
            >
              <div class="text-sm font-medium">{{ $t('wac_audit.title', 'WAC Ревизија') }}</div>
              <div class="text-xs text-gray-400">Проверка на просечна цена</div>
            </router-link>
          </div>
        </transition>
      </div>
    </div>

    <!-- Mobile: 5 items, dropdowns open as bottom sheet style -->
    <nav class="md:hidden -mb-px flex overflow-x-auto scrollbar-hide">
      <router-link
        to="/admin/stock"
        class="py-3 px-3 border-b-2 font-medium text-xs whitespace-nowrap"
        :class="tabClass('/admin/stock')"
      >
        Преглед
      </router-link>
      <button
        @click="toggle('inventory')"
        class="py-3 px-3 border-b-2 font-medium text-xs whitespace-nowrap inline-flex items-center gap-0.5"
        :class="groupTabClass(['inventory', 'item-card', 'warehouses'])"
      >
        Залиха
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <button
        @click="toggle('documents')"
        class="py-3 px-3 border-b-2 font-medium text-xs whitespace-nowrap inline-flex items-center gap-0.5"
        :class="groupTabClass(['documents', 'counts', 'adjustments'])"
      >
        Документи
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <button
        @click="toggle('trade')"
        class="py-3 px-3 border-b-2 font-medium text-xs whitespace-nowrap inline-flex items-center gap-0.5"
        :class="groupTabClass(['trade'])"
      >
        Трговија
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
      <button
        @click="toggle('analysis')"
        class="py-3 px-3 border-b-2 font-medium text-xs whitespace-nowrap inline-flex items-center gap-0.5"
        :class="groupTabClass(['low-stock', 'wac-audit'])"
      >
        Анализа
        <span
          v-if="criticalCount > 0"
          class="inline-flex items-center justify-center px-1 py-0.5 text-[9px] font-bold leading-none text-white bg-red-600 rounded-full"
        >
          {{ criticalCount }}
        </span>
        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </button>
    </nav>

    <!-- Mobile dropdown panel (slides down below tabs) -->
    <transition
      enter-active-class="transition ease-out duration-150"
      enter-from-class="opacity-0 -translate-y-1"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-100"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-1"
    >
      <div v-if="openMenu && isMobile" class="md:hidden bg-white border-b border-gray-200 shadow-sm -mt-px">
        <template v-for="item in mobileMenuItems" :key="item.to">
          <router-link
            :to="item.to"
            class="block px-4 py-3 hover:bg-gray-50 border-l-2"
            :class="isActive(item.to) ? 'border-l-primary-500 bg-primary-50' : 'border-l-transparent'"
            @click="openMenu = null"
          >
            <div class="text-sm font-medium text-gray-900">{{ item.label }}</div>
            <div class="text-xs text-gray-400">{{ item.desc }}</div>
          </router-link>
        </template>
      </div>
    </transition>
  </div>

  <!-- FAB: Quick Action Floating Button -->
  <div class="fixed bottom-6 right-6 z-50" ref="fabRef">
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute } from 'vue-router'
import { onClickOutside } from '@vueuse/core'

const route = useRoute()
const criticalCount = ref(0)
const showFabMenu = ref(false)
const openMenu = ref(null)

// Refs for click-outside detection
const inventoryRef = ref(null)
const documentsRef = ref(null)
const tradeRef = ref(null)
const analysisRef = ref(null)
const fabRef = ref(null)

// Mobile detection
const isMobile = ref(false)
const checkMobile = () => { isMobile.value = window.innerWidth < 768 }

// Mobile dropdown items based on which menu is open
const mobileMenuItems = computed(() => {
  const menus = {
    inventory: [
      { to: '/admin/stock/inventory', label: 'Инвентар', desc: 'Тековна залиха по артикл' },
      { to: '/admin/stock/item-card', label: 'Картица', desc: 'Движења по артикл' },
      { to: '/admin/stock/warehouses', label: 'Магацини', desc: 'Управување со магацини' },
    ],
    documents: [
      { to: '/admin/stock/documents', label: 'Магацински документи', desc: 'Приемници, издатници, повратници' },
      { to: '/admin/stock/counts', label: 'Попис', desc: 'Пописни листи и записници' },
      { to: '/admin/stock/adjustments', label: 'Регулирање', desc: 'Корекции на залиха' },
    ],
    trade: [
      { to: '/admin/stock/trade/nivelacii', label: 'Нивелации', desc: 'Промена на малопродажна цена' },
      { to: '/admin/stock/trade/kap', label: 'КАП — Калкулација', desc: 'Калкулација на набавна цена' },
      { to: '/admin/stock/trade/plt', label: 'ПЛТ — Малопродажба', desc: 'Пресметка на малопродажна цена' },
    ],
    analysis: [
      { to: '/admin/stock/low-stock', label: 'Ниска залиха', desc: 'Критични нивоа на залиха' },
      { to: '/admin/stock/wac-audit', label: 'WAC Ревизија', desc: 'Проверка на просечна цена' },
    ],
  }
  return menus[openMenu.value] || []
})

const toggle = (menu) => {
  openMenu.value = openMenu.value === menu ? null : menu
}

const isActive = (path) => {
  if (path === '/admin/stock') {
    return route.path === '/admin/stock' || route.path === '/admin/stock/'
  }
  if (path === '/admin/stock/inventory') {
    return route.path === '/admin/stock/inventory' || route.path === '/admin/stock/inventory/'
  }
  if (path === '/admin/stock/trade/nivelacii') {
    return route.path.startsWith('/admin/stock/trade/nivelacii')
  }
  return route.path.startsWith(path)
}

const tabClass = (path) => {
  return isActive(path)
    ? 'border-primary-500 text-primary-600'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
}

/** Check if any route in the group is active — highlights the dropdown button */
const groupTabClass = (segments) => {
  const active = segments.some(seg => {
    const fullPath = seg === 'trade'
      ? '/admin/stock/trade'
      : `/admin/stock/${seg}`
    return route.path.startsWith(fullPath) && route.path !== '/admin/stock' && route.path !== '/admin/stock/'
  })
  return active
    ? 'border-primary-500 text-primary-600'
    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
}

const dropdownItemClass = (path) => {
  return isActive(path)
    ? 'bg-primary-50 border-l-2 border-l-primary-500'
    : ''
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

// Click-outside for desktop dropdowns
onClickOutside(inventoryRef, () => { if (openMenu.value === 'inventory') openMenu.value = null })
onClickOutside(documentsRef, () => { if (openMenu.value === 'documents') openMenu.value = null })
onClickOutside(tradeRef, () => { if (openMenu.value === 'trade') openMenu.value = null })
onClickOutside(analysisRef, () => { if (openMenu.value === 'analysis') openMenu.value = null })
onClickOutside(fabRef, () => { showFabMenu.value = false })

onMounted(() => {
  fetchCriticalCount()
  checkMobile()
  window.addEventListener('resize', checkMobile)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', checkMobile)
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
