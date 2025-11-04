<template>
  <BasePage>
    <BaseAlert
      v-if="partnerStore.isMocked"
      type="warning"
      :title="'⚠️ Внимание'"
      class="mb-6"
    >
      {{ partnerStore.mockWarning || 'Гледате симулирани податоци. Обработката на провизии е оневозможена за безбедност.' }}
    </BaseAlert>
    <DashboardStats />
    <DashboardTable />
  </BasePage>
</template>

<script setup>
import BaseAlert from '@/scripts/components/base/BaseAlert.vue'
import DashboardStats from '@/scripts/partner/views/dashboard/DashboardStats.vue'
import DashboardTable from '@/scripts/partner/views/dashboard/DashboardTable.vue'
import { usePartnerStore } from '@/scripts/partner/stores/partner'
import { useUserStore } from '@/scripts/partner/stores/user'
import { onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const partnerStore = usePartnerStore()
const userStore = useUserStore()
const router = useRouter()

onMounted(() => {
  // Partner-specific authorization logic
  if (route.meta.isPartner && !userStore.currentUser.is_partner) {
    router.push({ name: 'partner.login' })
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->