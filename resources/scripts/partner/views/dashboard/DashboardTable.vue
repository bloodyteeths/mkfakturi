<template>
  <div class="grid gap-6 mb-8 md:grid-cols-2">
    <!-- Recent Commissions Card -->
    <div
      class="min-w-0 p-4 bg-white rounded-lg shadow-xs"
      role="region"
      :aria-label="$t('partner_dashboard.recent_commissions_title')"
    >
      <h4 class="mb-4 font-semibold text-gray-800">
        {{ $t('partner_dashboard.recent_commissions_title') }}
      </h4>

      <!-- Loading Skeleton for Commissions -->
      <div v-if="isLoadingCommissions" class="overflow-hidden" role="status" :aria-label="$t('partner_dashboard.loading')">
        <div class="animate-pulse">
          <div class="h-10 bg-gray-100 mb-2 rounded"></div>
          <div v-for="n in 3" :key="'comm-skeleton-' + n" class="h-12 bg-gray-50 mb-2 rounded"></div>
        </div>
      </div>

      <!-- Commissions Table -->
      <div v-else class="overflow-hidden overflow-x-auto">
        <table class="w-full whitespace-no-wrap" role="table" :aria-label="$t('partner_dashboard.recent_commissions_title')">
          <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
              <th class="px-4 py-3" scope="col">{{ $t('partner_dashboard.client') }}</th>
              <th class="px-4 py-3" scope="col">{{ $t('partner_dashboard.type') }}</th>
              <th class="px-4 py-3" scope="col">{{ $t('partner_dashboard.amount') }}</th>
              <th class="px-4 py-3" scope="col">{{ $t('partner_dashboard.status') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y">
            <tr v-for="commission in recentCommissions" :key="commission.id" class="text-gray-700">
              <td class="px-4 py-3 text-sm">
                {{ commission.company_name }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span class="capitalize">{{ getCommissionTypeText(commission.type) }}</span>
              </td>
              <td class="px-4 py-3 text-sm">
                {{ formatMoney(commission.amount) }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getStatusColor(commission.status)"
                  role="status"
                >
                  {{ getStatusText(commission.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="!recentCommissions.length && !isLoadingCommissions">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500" role="status">
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ $t('partner_dashboard.no_recent_commissions') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Activity Card -->
    <div
      class="min-w-0 p-4 bg-white rounded-lg shadow-xs"
      role="region"
      :aria-label="$t('partner_dashboard.recent_activities_title')"
    >
      <h4 class="mb-4 font-semibold text-gray-800">
        {{ $t('partner_dashboard.recent_activities_title') }}
      </h4>

      <!-- Loading Skeleton for Activities -->
      <div v-if="isLoadingActivities" class="space-y-3" role="status" :aria-label="$t('partner_dashboard.loading')">
        <div v-for="n in 4" :key="'act-skeleton-' + n" class="flex items-center animate-pulse">
          <div class="w-2 h-2 bg-gray-200 rounded-full"></div>
          <div class="ml-3 flex-1">
            <div class="h-4 bg-gray-200 rounded w-3/4 mb-1"></div>
            <div class="h-3 bg-gray-100 rounded w-1/4"></div>
          </div>
        </div>
      </div>

      <!-- Activities List -->
      <div v-else class="space-y-3" role="list">
        <div
          v-for="activity in recentActivities"
          :key="activity.id"
          class="flex items-center"
          role="listitem"
        >
          <div class="flex-shrink-0" aria-hidden="true">
            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm text-gray-600">
              {{ activity.description }}
            </p>
            <p class="text-xs text-gray-400">
              <time :datetime="activity.created_at">{{ formatDate(activity.created_at) }}</time>
            </p>
          </div>
        </div>
        <div v-if="!recentActivities.length && !isLoadingActivities" class="text-center py-8 text-gray-500" role="status">
          <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $t('partner_dashboard.no_recent_activities') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePartnerStore } from '@/scripts/partner/stores/partner'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const partnerStore = usePartnerStore()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const recentCommissions = ref([])
const recentActivities = ref([])
const isLoadingCommissions = ref(true)
const isLoadingActivities = ref(true)

let commissionsAbortController = null
let activitiesAbortController = null

// Get company currency with fallback to EUR
const companyCurrency = computed(() => {
  return companyStore.selectedCompanyCurrency?.code || 'EUR'
})

const formatMoney = (amount) => {
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: companyCurrency.value
  }).format(amount || 0)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('mk-MK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const getStatusColor = (status) => {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    paid: 'bg-blue-100 text-blue-800'
  }
  return colors[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const statusKey = 'partner_dashboard.status_' + status
  return t(statusKey) || status
}

const getCommissionTypeText = (type) => {
  const typeKey = 'partner_dashboard.commission_type_' + type
  const translated = t(typeKey)
  // If translation key not found, return original type
  return translated === typeKey ? type : translated
}

const loadCommissions = async () => {
  // Cancel any pending request
  if (commissionsAbortController) {
    commissionsAbortController.abort()
  }
  commissionsAbortController = new AbortController()

  isLoadingCommissions.value = true

  try {
    await partnerStore.loadRecentCommissions()
    recentCommissions.value = partnerStore.recentCommissions || []
  } catch (error) {
    // Don't show error for aborted requests
    if (error.name === 'AbortError' || error.name === 'CanceledError') {
      return
    }

    notificationStore.showNotification({
      type: 'error',
      message: t('partner_dashboard.error_loading_commissions')
    })
    recentCommissions.value = []
  } finally {
    isLoadingCommissions.value = false
  }
}

const loadActivities = async () => {
  // Cancel any pending request
  if (activitiesAbortController) {
    activitiesAbortController.abort()
  }
  activitiesAbortController = new AbortController()

  isLoadingActivities.value = true

  try {
    await partnerStore.loadRecentActivities()
    recentActivities.value = partnerStore.recentActivities || []
  } catch (error) {
    // Don't show error for aborted requests
    if (error.name === 'AbortError' || error.name === 'CanceledError') {
      return
    }

    notificationStore.showNotification({
      type: 'error',
      message: t('partner_dashboard.error_loading_activities')
    })
    recentActivities.value = []
  } finally {
    isLoadingActivities.value = false
  }
}

onMounted(async () => {
  // Load data in parallel for better performance
  await Promise.all([
    loadCommissions(),
    loadActivities()
  ])
})

onUnmounted(() => {
  // Cleanup: abort any pending requests
  if (commissionsAbortController) {
    commissionsAbortController.abort()
  }
  if (activitiesAbortController) {
    activitiesAbortController.abort()
  }
})
</script>

// CLAUDE-CHECKPOINT
