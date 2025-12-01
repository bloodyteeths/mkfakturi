<template>
  <div class="grid gap-6 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <DashboardStatsItem
      v-for="stat in stats"
      :key="stat.title"
      :title="stat.title"
      :value="stat.value"
      :icon="stat.icon"
      :color="stat.color"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardStatsItem from '@/scripts/partner/views/dashboard/DashboardStatsItem.vue'
import { usePartnerStore } from '@/scripts/partner/stores/partner'

const partnerStore = usePartnerStore()

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount || 0)
}

const stats = ref([
  {
    title: 'Активни Клиенти',
    value: '0',
    icon: 'CustomerIcon',
    color: 'text-blue-500'
  },
  {
    title: 'Месечни Провизии',
    value: '€0.00',
    icon: 'DollarIcon',
    color: 'text-green-500'
  },
  {
    title: 'Вкупно Заработено',
    value: '€0.00',
    icon: 'InvoiceIcon',
    color: 'text-purple-500'
  },
  {
    title: 'Чека Исплата',
    value: '€0.00',
    icon: 'EstimateIcon',
    color: 'text-orange-500'
  }
])

onMounted(async () => {
  // Load partner dashboard data
  await partnerStore.loadDashboardStats()

  // Update stats with real data
  stats.value = [
    {
      title: 'Активни Клиенти',
      value: partnerStore.dashboardStats.activeClients?.toString() || '0',
      icon: 'CustomerIcon',
      color: 'text-blue-500'
    },
    {
      title: 'Месечни Провизии',
      value: formatCurrency(partnerStore.dashboardStats.monthlyCommissions || 0),
      icon: 'DollarIcon',
      color: 'text-green-500'
    },
    {
      title: 'Вкупно Заработено',
      value: formatCurrency(partnerStore.dashboardStats.totalEarned || 0),
      icon: 'InvoiceIcon',
      color: 'text-purple-500'
    },
    {
      title: 'Чека Исплата',
      value: formatCurrency(partnerStore.dashboardStats.pendingPayout || 0),
      icon: 'EstimateIcon',
      color: 'text-orange-500'
    }
  ]
})
</script>