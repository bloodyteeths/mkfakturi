<template>
  <div class="ai-insights-widget">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
        AI Financial Insights
      </h3>
      
      <div v-if="loading" class="flex justify-center items-center h-32">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-500"></div>
      </div>
      
      <div v-else-if="error" class="text-red-500 text-sm">
        Unable to load insights. Please try again later.
      </div>
      
      <div v-else class="space-y-3">
        <!-- Net Profit -->
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-600 dark:text-gray-400">Net Profit (30d)</span>
          <span class="font-semibold text-gray-900 dark:text-gray-100">
            {{ formatCurrency(summary.netProfit) }}
          </span>
        </div>
        
        <!-- Cash Runway -->
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-600 dark:text-gray-400">Cash Runway</span>
          <span class="font-semibold text-gray-900 dark:text-gray-100">
            {{ calculateCashRunway() }} days
          </span>
        </div>
        
        <!-- Risk Index -->
        <div class="flex justify-between items-center">
          <span class="text-sm text-gray-600 dark:text-gray-400">Risk Index</span>
          <div class="flex items-center gap-2">
            <span :class="getRiskColorClass()" class="font-semibold">
              {{ getRiskLabel() }}
            </span>
            <span class="text-xs text-gray-500">
              ({{ (riskScore * 100).toFixed(0) }}%)
            </span>
          </div>
        </div>
        
        <!-- Insights -->
        <div v-if="summary.insights && summary.insights.length > 0" class="mt-4 pt-3 border-t border-gray-200 dark:border-gray-700">
          <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">Key Insights:</p>
          <ul class="space-y-1">
            <li v-for="(insight, index) in summary.insights.slice(0, 2)" :key="index" class="text-xs text-gray-700 dark:text-gray-300">
              • {{ insight }}
            </li>
          </ul>
        </div>
      </div>
      
      <!-- Last Updated -->
      <div class="mt-4 text-xs text-gray-500 dark:text-gray-500 text-right">
        Last updated: {{ lastUpdated }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useAiStore } from '@/admin/stores/ai'
import { formatMoney } from '@/scripts/helpers/money'

const aiStore = useAiStore()

const loading = ref(true)
const error = ref(false)
const refreshInterval = ref(null)

const summary = computed(() => aiStore.summary || {})
const riskScore = computed(() => aiStore.riskScore || 0.5)
const lastUpdated = computed(() => {
  if (!aiStore.lastUpdated) return 'Never'
  const date = new Date(aiStore.lastUpdated)
  return date.toLocaleTimeString('en-US', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
})

const formatCurrency = (amount) => {
  return formatMoney(amount || 0, 'MKD')
}

const calculateCashRunway = () => {
  if (!summary.value.netProfit || summary.value.netProfit <= 0) return '90+'
  const monthlyBurn = Math.abs(summary.value.totalExpenses - summary.value.totalRevenue)
  if (monthlyBurn <= 0) return '90+'
  const runway = Math.floor((summary.value.netProfit * 30) / monthlyBurn)
  return Math.min(runway, 90)
}

const getRiskColorClass = () => {
  if (riskScore.value < 0.3) return 'text-green-600'
  if (riskScore.value < 0.6) return 'text-yellow-600'
  return 'text-red-600'
}

const getRiskLabel = () => {
  if (riskScore.value < 0.3) return 'Low'
  if (riskScore.value < 0.6) return 'Moderate'
  return 'High'
}

const fetchData = async () => {
  try {
    loading.value = true
    error.value = false
    await aiStore.fetchInsights()
  } catch (e) {
    console.error('Failed to fetch AI insights:', e)
    error.value = true
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
  // Refresh every 15 minutes
  refreshInterval.value = setInterval(fetchData, 15 * 60 * 1000)
})

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})
</script>

<style scoped>
.ai-insights-widget {
  min-height: 200px;
}
</style>