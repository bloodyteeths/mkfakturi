<template>
  <div class="grid gap-6 mb-8 md:grid-cols-2">
    <!-- Recent Commissions Card -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
      <h4 class="mb-4 font-semibold text-gray-800">
        {{ $t('partner_dashboard.recent_commissions_title') }}
      </h4>
      <div class="overflow-hidden overflow-x-auto">
        <table class="w-full whitespace-no-wrap">
          <thead>
            <tr class="text-xs font-semibold tracking-wide text-left text-gray-500 uppercase border-b bg-gray-50">
              <th class="px-4 py-3">{{ $t('partner_dashboard.client') }}</th>
              <th class="px-4 py-3">{{ $t('partner_dashboard.type') }}</th>
              <th class="px-4 py-3">{{ $t('partner_dashboard.amount') }}</th>
              <th class="px-4 py-3">{{ $t('partner_dashboard.status') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y">
            <tr v-for="commission in recentCommissions" :key="commission.id" class="text-gray-700">
              <td class="px-4 py-3 text-sm">
                {{ commission.company_name }}
              </td>
              <td class="px-4 py-3 text-sm">
                <span class="capitalize">{{ commission.type }}</span>
              </td>
              <td class="px-4 py-3 text-sm">
                {{ formatMoney(commission.amount) }} МКД
              </td>
              <td class="px-4 py-3 text-sm">
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full"
                  :class="getStatusColor(commission.status)"
                >
                  {{ getStatusText(commission.status) }}
                </span>
              </td>
            </tr>
            <tr v-if="!recentCommissions.length">
              <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                {{ $t('partner_dashboard.no_recent_commissions') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Activity Card -->
    <div class="min-w-0 p-4 bg-white rounded-lg shadow-xs">
      <h4 class="mb-4 font-semibold text-gray-800">
        {{ $t('partner_dashboard.recent_activities_title') }}
      </h4>
      <div class="space-y-3">
        <div v-for="activity in recentActivities" :key="activity.id" class="flex items-center">
          <div class="flex-shrink-0">
            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
          </div>
          <div class="ml-3 flex-1">
            <p class="text-sm text-gray-600">
              {{ activity.description }}
            </p>
            <p class="text-xs text-gray-400">
              {{ formatDate(activity.created_at) }}
            </p>
          </div>
        </div>
        <div v-if="!recentActivities.length" class="text-center py-8 text-gray-500">
          {{ $t('partner_dashboard.no_recent_activities') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { usePartnerStore } from '@/scripts/partner/stores/partner'

const { t } = useI18n()
const partnerStore = usePartnerStore()
const recentCommissions = ref([])
const recentActivities = ref([])

const formatMoney = (amount) => {
  return new Intl.NumberFormat('mk-MK').format(amount)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('mk-MK')
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
  const texts = {
    pending: t('partner_dashboard.status_pending'),
    approved: t('partner_dashboard.status_approved'),
    paid: t('partner_dashboard.status_paid')
  }
  return texts[status] || status
}

onMounted(async () => {
  // Load recent data
  await partnerStore.loadRecentCommissions() 
  await partnerStore.loadRecentActivities()
  
  recentCommissions.value = partnerStore.recentCommissions || []
  recentActivities.value = partnerStore.recentActivities || []
})
</script>

