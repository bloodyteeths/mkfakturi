<template>
  <BaseCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('dashboard.stock_summary') || 'Преглед на залихи' }}
        </h3>
        <CubeIcon class="h-6 w-6 text-indigo-500" />
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="isLoading" class="animate-pulse space-y-4">
      <div class="h-10 bg-gray-200 rounded w-2/3"></div>
      <div class="h-4 bg-gray-200 rounded w-1/2"></div>
      <div class="space-y-2 mt-4">
        <div v-for="i in 3" :key="i" class="h-4 bg-gray-200 rounded w-full"></div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!hasData" class="text-center py-6">
      <CubeIcon class="h-12 w-12 mx-auto text-gray-300 mb-2" />
      <p class="text-sm text-gray-500">
        {{ $t('dashboard.no_stock_data') || 'Нема следени артикли на залиха' }}
      </p>
      <router-link
        to="/admin/stock"
        class="mt-2 inline-block text-sm text-primary-500 hover:text-primary-600 font-medium"
      >
        {{ $t('dashboard.setup_stock') || 'Постави залихи' }}
      </router-link>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Summary Stats -->
      <div class="grid grid-cols-2 gap-4 py-3">
        <!-- Total Value -->
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1">
            {{ $t('dashboard.total_inventory_value') || 'Вкупна вредност' }}
          </p>
          <p class="text-xl font-bold text-indigo-600">
            {{ formatMoney(summaryData.total_value) }}
          </p>
        </div>

        <!-- Total Items -->
        <div class="text-center">
          <p class="text-xs text-gray-500 mb-1">
            {{ $t('dashboard.tracked_items') || 'Следени артикли' }}
          </p>
          <p class="text-xl font-bold text-gray-900">
            {{ summaryData.total_items }}
          </p>
        </div>
      </div>

      <!-- Low Stock Alerts -->
      <div class="border-t pt-3 mt-2">
        <div class="flex items-center justify-between mb-3">
          <span class="text-sm font-medium text-gray-700">
            {{ $t('dashboard.low_stock_alerts') || 'Ниски залихи' }}
          </span>
          <span
            v-if="summaryData.low_stock_count > 0"
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800"
          >
            <ExclamationTriangleIcon class="h-3.5 w-3.5 mr-1" />
            {{ summaryData.low_stock_count }}
          </span>
          <span
            v-else
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
          >
            {{ $t('dashboard.stock_ok') || 'Сe е в ред' }}
          </span>
        </div>

        <!-- Low Stock Items Mini-List -->
        <div v-if="summaryData.low_stock_items && summaryData.low_stock_items.length > 0" class="space-y-2">
          <div
            v-for="item in summaryData.low_stock_items"
            :key="item.item_id"
            class="flex items-center justify-between py-1.5 px-2 rounded-md bg-amber-50 border border-amber-100"
          >
            <span class="text-sm text-gray-800 truncate flex-1 mr-2">
              {{ item.item_name }}
            </span>
            <span class="text-xs font-mono whitespace-nowrap" :class="getStockLevelClass(item)">
              {{ item.current_quantity }} / {{ item.minimum_quantity }}
            </span>
          </div>
        </div>

        <!-- No Low Stock -->
        <div v-else-if="summaryData.low_stock_count === 0" class="text-center py-2">
          <p class="text-xs text-gray-500">
            {{ $t('dashboard.all_stock_sufficient') || 'Сите артикли имаат доволно залиха' }}
          </p>
        </div>
      </div>

      <!-- View All Link -->
      <div class="border-t pt-3 mt-3">
        <router-link
          to="/admin/stock"
          class="text-sm text-primary-500 hover:text-primary-600 font-medium flex items-center justify-center"
        >
          {{ $t('dashboard.view_stock') || 'Прикажи повеќе' }}
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4 ml-1" />
        </router-link>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import axios from 'axios'
import { CubeIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const companyStore = useCompanyStore()

const isLoading = ref(true)
const summaryData = ref({
  total_value: 0,
  total_items: 0,
  total_quantity: 0,
  low_stock_count: 0,
  low_stock_items: [],
})

const currencySymbol = computed(() => {
  return companyStore.selectedCompanyCurrency?.symbol || 'ден'
})

const hasData = computed(() => {
  return summaryData.value.total_items > 0
})

/**
 * Format monetary amount in Macedonian locale.
 *
 * @param {number} amount - Raw monetary amount
 * @returns {string} Formatted string with currency symbol
 */
function formatMoney(amount) {
  const formatted = Math.round(amount).toLocaleString('mk-MK')
  return `${formatted} ${currencySymbol.value}`
}

/**
 * Determine CSS class for stock level indicator.
 *
 * @param {object} item - Low stock item with current_quantity and minimum_quantity
 * @returns {string} Tailwind CSS class string
 */
function getStockLevelClass(item) {
  if (item.current_quantity <= 0) {
    return 'text-red-700 font-semibold'
  }
  if (item.current_quantity <= item.minimum_quantity * 0.5) {
    return 'text-red-600 font-medium'
  }
  return 'text-amber-700 font-medium'
}

/**
 * Fetch stock dashboard summary from API.
 */
async function fetchDashboardSummary() {
  isLoading.value = true
  try {
    const companyId = companyStore.selectedCompany?.id
    if (!companyId) {
      isLoading.value = false
      return
    }
    const response = await axios.get('/stock/dashboard-summary')
    if (response?.data) {
      summaryData.value = {
        total_value: response.data.total_value || 0,
        total_items: response.data.total_items || 0,
        total_quantity: response.data.total_quantity || 0,
        low_stock_count: response.data.low_stock_count || 0,
        low_stock_items: response.data.low_stock_items || [],
      }
    }
  } catch (error) {
    console.error('Failed to fetch stock dashboard summary:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboardSummary()
})
</script>

