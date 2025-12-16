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

    <!-- Stripe Connect Status Banner -->
    <div
      v-if="showStripeConnectBanner"
      class="mb-6 rounded-lg p-4 flex items-center justify-between"
      :class="stripeConnectBannerClass"
    >
      <div class="flex items-center gap-3">
        <div class="flex-shrink-0">
          <!-- Not connected icon -->
          <svg v-if="!partnerStore.stripeConnect.connected" class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.594-7.305z"/>
          </svg>
          <!-- Pending icon -->
          <svg v-else-if="partnerStore.stripeConnect.status === 'pending'" class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
          </svg>
          <!-- Restricted icon -->
          <svg v-else-if="partnerStore.stripeConnect.status === 'restricted'" class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <!-- Active icon -->
          <svg v-else-if="partnerStore.stripeConnect.status === 'active'" class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          </svg>
        </div>
        <div>
          <p class="font-medium" :class="stripeConnectTextClass">
            {{ stripeConnectMessage }}
          </p>
        </div>
      </div>
      <router-link
        to="/admin/partner/payouts"
        class="px-4 py-2 rounded-lg text-sm font-medium transition"
        :class="stripeConnectButtonClass"
      >
        {{ stripeConnectButtonText }}
      </router-link>
    </div>

    <DashboardStats />
    <DashboardTable />
  </BasePage>
</template>

<script setup>
import BaseAlert from '@/scripts/components/base/BaseAlert.vue'
import DashboardStats from '@/scripts/partner/views/dashboard/DashboardStats.vue'
import DashboardTable from '@/scripts/partner/views/dashboard/DashboardTable.vue'
import { usePartnerStore } from '@/scripts/partner/stores/partner'
import { useUserStore } from '@/scripts/admin/stores/user'
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const partnerStore = usePartnerStore()
const userStore = useUserStore()
const router = useRouter()

// Stripe Connect computed properties
const showStripeConnectBanner = computed(() => {
  // Show banner if not loading and either not connected or needs attention
  if (partnerStore.stripeConnectLoading) return false
  if (!partnerStore.stripeConnect.connected) return true
  if (partnerStore.stripeConnect.status === 'pending') return true
  if (partnerStore.stripeConnect.status === 'restricted') return true
  if (partnerStore.stripeConnect.status === 'disabled') return true
  // Don't show banner if active - everything is fine
  return false
})

const stripeConnectBannerClass = computed(() => {
  if (!partnerStore.stripeConnect.connected) {
    return 'bg-indigo-50 border border-indigo-200'
  }
  if (partnerStore.stripeConnect.status === 'pending') {
    return 'bg-yellow-50 border border-yellow-200'
  }
  if (partnerStore.stripeConnect.status === 'restricted' || partnerStore.stripeConnect.status === 'disabled') {
    return 'bg-red-50 border border-red-200'
  }
  return 'bg-green-50 border border-green-200'
})

const stripeConnectTextClass = computed(() => {
  if (!partnerStore.stripeConnect.connected) {
    return 'text-indigo-800'
  }
  if (partnerStore.stripeConnect.status === 'pending') {
    return 'text-yellow-800'
  }
  if (partnerStore.stripeConnect.status === 'restricted' || partnerStore.stripeConnect.status === 'disabled') {
    return 'text-red-800'
  }
  return 'text-green-800'
})

const stripeConnectButtonClass = computed(() => {
  if (!partnerStore.stripeConnect.connected) {
    return 'bg-indigo-600 text-white hover:bg-indigo-700'
  }
  if (partnerStore.stripeConnect.status === 'pending') {
    return 'bg-yellow-600 text-white hover:bg-yellow-700'
  }
  if (partnerStore.stripeConnect.status === 'restricted' || partnerStore.stripeConnect.status === 'disabled') {
    return 'bg-red-600 text-white hover:bg-red-700'
  }
  return 'bg-green-600 text-white hover:bg-green-700'
})

const stripeConnectMessage = computed(() => {
  if (!partnerStore.stripeConnect.connected) {
    return 'Поврзете се со Stripe за да примате автоматски исплати на провизии'
  }
  if (partnerStore.stripeConnect.status === 'pending') {
    return 'Завршете ја Stripe регистрацијата за да почнете да примате исплати'
  }
  if (partnerStore.stripeConnect.status === 'restricted') {
    return 'Вашата Stripe сметка има ограничувања - потребна е акција'
  }
  if (partnerStore.stripeConnect.status === 'disabled') {
    return 'Вашата Stripe сметка е деактивирана - потребна е акција'
  }
  return 'Stripe е поврзан и исплатите се активни'
})

const stripeConnectButtonText = computed(() => {
  if (!partnerStore.stripeConnect.connected) {
    return 'Поврзи се'
  }
  if (partnerStore.stripeConnect.status === 'pending') {
    return 'Продолжи'
  }
  if (partnerStore.stripeConnect.status === 'restricted' || partnerStore.stripeConnect.status === 'disabled') {
    return 'Активирај'
  }
  return 'Прегледај'
})

onMounted(() => {
  // Partner-specific authorization logic
  const currentUser = userStore.currentUser
  const isPartner = currentUser?.role === 'partner' ||
                    currentUser?.is_partner ||
                    currentUser?.account_type === 'accountant'

  if (route.meta.isPartner && !isPartner) {
    router.push({ name: 'login' })
    return
  }

  // Load Stripe Connect status
  partnerStore.loadStripeConnectStatus()
})
</script>

<!-- CLAUDE-CHECKPOINT -->