<template>
  <div v-if="isAppLoaded" class="h-full">
    <NotificationRoot />

    <SiteHeader />

    <SiteSidebar />

    <ExchangeRateBulkUpdateModal />

    <LimitExceededModal />

    <main
      :class="[
        'h-screen h-screen-ios overflow-y-auto min-h-0 transition-all duration-300 ease-in-out',
        globalStore.isSidebarCollapsed ? 'md:pl-16' : 'md:pl-56 xl:pl-64'
      ]"
    >
      <div class="pt-16 pb-16">
        <router-view />
      </div>
    </main>
  </div>

  <!-- Show minimal loader only briefly - don't block entire app -->
  <div v-else class="h-full flex items-center justify-center">
    <div class="text-primary-500">Loading...</div>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useModalStore } from '@/scripts/stores/modal'
import { useExchangeRateStore } from '@/scripts/admin/stores/exchange-rate'
import { useCompanyStore } from '@/scripts/admin/stores/company'

import SiteHeader from '@/scripts/admin/layouts/partials/TheSiteHeader.vue'
import SiteSidebar from '@/scripts/admin/layouts/partials/TheSiteSidebar.vue'
import NotificationRoot from '@/scripts/components/notifications/NotificationRoot.vue'
import ExchangeRateBulkUpdateModal from '@/scripts/admin/components/modal-components/ExchangeRateBulkUpdateModal.vue'
import LimitExceededModal from '@/scripts/admin/components/LimitExceededModal.vue'

const globalStore = useGlobalStore()
const route = useRoute()
const userStore = useUserStore()
const router = useRouter()
const modalStore = useModalStore()
const { t } = useI18n()
const exchangeRateStore = useExchangeRateStore()
const companyStore = useCompanyStore()

const isAppLoaded = computed(() => {
  return globalStore.isAppLoaded
})

onMounted(() => {
  // Start bootstrap but don't block UI - app will show once isAppLoaded becomes true
  globalStore.bootstrap().then((res) => {
    // Only redirect if abilities are fully loaded AND user lacks the required ability
    if (route.meta.ability && userStore.currentAbilities && userStore.currentAbilities.length > 0) {
      if (!userStore.hasAbilities(route.meta.ability)) {
        router.push({ name: 'account.settings' })
        return
      }
    }
    // Check isPartner meta first - partners and super admins should access partner routes
    if (route.meta.isPartner && userStore.currentUser) {
      const isPartner = userStore.currentUser.role === 'partner' ||
                        userStore.currentUser.account_type === 'accountant' ||
                        userStore.currentUser.is_partner
      const isSuperAdmin = userStore.currentUser.role === 'super admin'
      if (!isPartner && !isSuperAdmin) {
        router.push({ name: 'dashboard' })
        return
      }
      // Partners and super admins can access partner routes - skip other checks
      return
    }

    // Check isOwner meta - only if not a partner route
    if (route.meta.isOwner && userStore.currentUser) {
      // Use optional chaining to avoid undefined errors for partners
      if (userStore.currentUser.is_owner === false) {
        router.push({ name: 'account.settings' })
        return
      }
    }

    if (
      res.data.current_company_settings.bulk_exchange_rate_configured === 'NO'
    ) {
      exchangeRateStore.fetchBulkCurrencies().then((res) => {
        if (res.data.currencies.length) {
          modalStore.openModal({
            componentName: 'ExchangeRateBulkUpdateModal',
            size: 'sm',
          })
        } else {
          let data = {
            settings: {
              bulk_exchange_rate_configured: 'YES',
            },
          }
          companyStore.updateCompanySettings({
            data,
          })
        }
      })
    }
  })
})
</script>
