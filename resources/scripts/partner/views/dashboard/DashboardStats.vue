<template>
  <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4" role="region" :aria-label="$t('partner_dashboard.stats_section')">
    <!-- Loading Skeleton -->
    <template v-if="isLoading">
      <div
        v-for="n in 4"
        :key="'skeleton-' + n"
        class="flex items-center p-4 bg-white rounded-lg shadow-xs animate-pulse"
        role="status"
        :aria-label="$t('partner_dashboard.loading')"
      >
        <div class="p-3 mr-4 rounded-full bg-gray-200 w-11 h-11"></div>
        <div class="flex-1">
          <div class="h-4 bg-gray-200 rounded w-24 mb-2"></div>
          <div class="h-6 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </template>

    <!-- Stats Cards -->
    <template v-else-if="stats.length > 0">
      <DashboardStatsItem
        v-for="stat in stats"
        :key="stat.title"
        :title="stat.title"
        :value="stat.value"
        :icon="stat.icon"
        :color="stat.color"
        :aria-label="stat.title + ': ' + stat.value"
      />
    </template>

    <!-- Empty State -->
    <div
      v-else
      class="col-span-full flex flex-col items-center justify-center p-8 bg-white rounded-lg shadow-xs"
      role="status"
    >
      <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <p class="text-gray-500 text-sm">{{ $t('partner_dashboard.no_stats_available') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import DashboardStatsItem from '@/scripts/partner/views/dashboard/DashboardStatsItem.vue'
import { usePartnerStore } from '@/scripts/partner/stores/partner'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const partnerStore = usePartnerStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const isLoading = ref(true)
const hasError = ref(false)
let abortController = null

// Get company currency with fallback to EUR
const companyCurrency = computed(() => {
  return companyStore.selectedCompanyCurrency?.code || 'EUR'
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: companyCurrency.value
  }).format(amount || 0)
}

const stats = ref([])

const loadStats = async () => {
  // Cancel any pending request
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoading.value = true
  hasError.value = false

  try {
    await partnerStore.loadDashboardStats()

    // Update stats with real data and i18n
    stats.value = [
      {
        title: t('partner_dashboard.active_clients'),
        value: partnerStore.dashboardStats.activeClients?.toString() || '0',
        icon: 'CustomerIcon',
        color: 'text-blue-500'
      },
      {
        title: t('partner_dashboard.monthly_commissions'),
        value: formatCurrency(partnerStore.dashboardStats.monthlyCommissions || 0),
        icon: 'DollarIcon',
        color: 'text-green-500'
      },
      {
        title: t('partner_dashboard.total_earned'),
        value: formatCurrency(partnerStore.dashboardStats.totalEarned || 0),
        icon: 'InvoiceIcon',
        color: 'text-purple-500'
      },
      {
        title: t('partner_dashboard.pending_payout'),
        value: formatCurrency(partnerStore.dashboardStats.pendingPayout || 0),
        icon: 'EstimateIcon',
        color: 'text-orange-500'
      }
    ]
  } catch (error) {
    // Don't show error for aborted requests
    if (error.name === 'AbortError' || error.name === 'CanceledError') {
      return
    }

    hasError.value = true
    notificationStore.showNotification({
      type: 'error',
      message: t('partner_dashboard.error_loading_stats')
    })

    // Set empty stats on error
    stats.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadStats()
})

onUnmounted(() => {
  // Cleanup: abort any pending requests
  if (abortController) {
    abortController.abort()
  }
})
</script>

// CLAUDE-CHECKPOINT
