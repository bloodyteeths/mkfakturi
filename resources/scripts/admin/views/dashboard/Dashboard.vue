<script setup>
import DashboardStats from '../dashboard/DashboardStats.vue'
import DashboardChart from '../dashboard/DashboardChart.vue'
import DashboardTable from '../dashboard/DashboardTable.vue'
import AiInsightsWidget from './widgets/AiInsightsWidget.vue'
import AiChatWidget from './widgets/AiChatWidget.vue'
import QuickActionsWidget from './widgets/QuickActionsWidget.vue'
import OverdueInvoicesWidget from './widgets/OverdueInvoicesWidget.vue'
import BankStatus from '@/scripts/components/widgets/BankStatus.vue'
import VatStatus from '@/scripts/components/widgets/VatStatus.vue'
import CertExpiry from '@/scripts/components/widgets/CertExpiry.vue'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const userStore = useUserStore()
const router = useRouter()
const globalStore = useGlobalStore()

// Check if AI Insights should be shown (default to true if setting doesn't exist)
const showAiInsights = computed(() => {
  return userStore.currentUserSettings?.show_ai_insights !== false
})

// Check if MCP AI Tools feature flag is enabled
const mcpAiToolsEnabled = computed(() => {
  return globalStore.featureFlags?.['mcp_ai_tools'] === true
})

// Check if PSD2 Banking feature flag is enabled
const bankingEnabled = computed(() => {
  return globalStore.featureFlags?.['psd2_banking'] === true
})

onMounted(() => {
  // Only redirect if abilities are fully loaded AND user lacks the required ability
  // Don't redirect if abilities haven't loaded yet or if route doesn't require special permissions
  const globalStore = useGlobalStore()
  if (globalStore.isAppLoaded && route.meta.ability && userStore.currentAbilities && userStore.currentAbilities.length > 0) {
    if (!userStore.hasAbilities(route.meta.ability)) {
      router.push({ name: 'account.settings' })
      return
    }
  }
  if (globalStore.isAppLoaded && route.meta.isOwner && userStore.currentUser && userStore.currentUser.is_owner === false) {
    router.push({ name: 'account.settings' })
    return
  }
  // Allow dashboard to load normally
})
</script>

<template>
  <BasePage>
    <!-- Top Stats Cards (always visible) -->
    <DashboardStats />

    <!-- Main Revenue Chart (full width) -->
    <div class="mb-6">
      <DashboardChart />
    </div>

    <!-- AI Insights & Chat Widgets (if feature enabled and user setting enabled) -->
    <div v-if="mcpAiToolsEnabled && showAiInsights" class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <AiInsightsWidget />
      <AiChatWidget />
    </div>

    <!-- Status Widgets Section - Only show if banking enabled (3 cols), otherwise hide for cleaner look -->
    <div v-if="bankingEnabled" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
      <BankStatus />
      <VatStatus />
      <CertExpiry />
    </div>

    <!-- Quick Actions & Overdue Alerts Row (Mobile: stack, Desktop: side-by-side) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <QuickActionsWidget />
      <OverdueInvoicesWidget />
    </div>

    <!-- Recent Invoices Table -->
    <DashboardTable />
  </BasePage>
</template>

// CLAUDE-CHECKPOINT
