import { createRouter, createWebHistory } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useGlobalStore } from '@/scripts/admin/stores/global'

//admin routes
import AdminRoutes from '@/scripts/admin/admin-router'
//  Customers routes
import CustomerRoutes from '@/scripts/customer/customer-router'
// Partner routes
import PartnerRoutes from '@/scripts/partner/partner-router'
//Payment Routes

let routes = []
routes = routes.concat(AdminRoutes, CustomerRoutes, PartnerRoutes)
// CLAUDE-CHECKPOINT

const router = createRouter({
  history: createWebHistory(),
  linkActiveClass: 'active',
  routes,
})

router.beforeEach((to, from, next) => {
  const userStore = useUserStore()
  const globalStore = useGlobalStore()
  let ability = to.meta.ability
  const { isAppLoaded } = globalStore

  // Don't check abilities until app is fully loaded AND abilities are populated
  if (ability && isAppLoaded && to.meta.requiresAuth && userStore.currentAbilities && userStore.currentAbilities.length > 0) {
    if (userStore.hasAbilities(ability)) {
      next()
    } else {
      next({ name: 'account.settings' })
    }
  } else if (to.meta.isOwner && isAppLoaded && userStore.currentUser) {
    if (userStore.currentUser.is_owner) {
      next()
    } else {
      next({ name: 'dashboard' })
    }
  } else {
    // Allow navigation if app isn't loaded yet or abilities aren't ready
    next()
  }
})

export default router
