<script setup>
import DashboardStats from '../dashboard/DashboardStats.vue'
import DashboardChart from '../dashboard/DashboardChart.vue'
import DashboardTable from '../dashboard/DashboardTable.vue'
import AiInsights from '@/components/AiInsights.vue'
import BankStatus from '@/scripts/components/widgets/BankStatus.vue'
import VatStatus from '@/scripts/components/widgets/VatStatus.vue'
import CertExpiry from '@/scripts/components/widgets/CertExpiry.vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const userStore = useUserStore()
const router = useRouter()

// Check if AI Insights should be shown (default to true if setting doesn't exist)
const showAiInsights = computed(() => {
  return userStore.currentUserSettings?.show_ai_insights !== false
})

onMounted(() => {
  // Only redirect if abilities are loaded AND user lacks the required ability
  // Don't redirect if abilities haven't loaded yet (empty array)
  if (route.meta.ability && userStore.currentAbilities.length > 0 && !userStore.hasAbilities(route.meta.ability)) {
    router.push({ name: 'account.settings' })
  } else if (route.meta.isOwner && userStore.currentUser && !userStore.currentUser.is_owner) {
    router.push({ name: 'account.settings' })
  }
})
</script>

<template>
  <BasePage>
    <DashboardStats />
    <div :class="showAiInsights ? 'grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6' : 'mb-6'">
      <div :class="showAiInsights ? 'lg:col-span-2' : ''">
        <DashboardChart />
      </div>
      <div v-if="showAiInsights" class="lg:col-span-1">
        <AiInsights />
      </div>
    </div>
    
    <!-- Widgets Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
      <BankStatus />
      <VatStatus />
      <CertExpiry />
    </div>
    
    <DashboardTable />
  </BasePage>
</template>
