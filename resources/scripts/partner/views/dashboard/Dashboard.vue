<template>
  <BasePage>
    <DashboardStats />
    <DashboardTable />
  </BasePage>
</template>

<script setup>
import DashboardStats from '@/scripts/partner/views/dashboard/DashboardStats.vue'
import DashboardTable from '@/scripts/partner/views/dashboard/DashboardTable.vue'
import { useUserStore } from '@/scripts/partner/stores/user'
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const userStore = useUserStore()
const router = useRouter()

onMounted(() => {
  // Partner-specific authorization logic
  if (route.meta.isPartner && !userStore.currentUser.is_partner) {
    router.push({ name: 'partner.login' })
  }
})
</script>