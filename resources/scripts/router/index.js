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

  // Check if route requires authentication
  if (to.meta.requiresAuth !== false && to.name !== 'login' && to.name !== 'forgot-password' && to.name !== 'reset-password') {
    // If user is not logged in (no currentUser), redirect to login
    if (!userStore.currentUser && isAppLoaded) {
      next({ name: 'login' })
      return
    }
  }

  // Check for redirectIfAuthenticated (login page when already logged in)
  if (to.meta.redirectIfAuthenticated && userStore.currentUser) {
    // Redirect based on user role - partners go to partner dashboard, others to admin dashboard
    const userRole = userStore.currentUser?.role
    if (userRole === 'partner') {
      next({ name: 'partner.dashboard' })
    } else {
      next({ name: 'dashboard' })
    }
    return
  }

  // Check for partner-only routes
  if (to.meta.isPartner && isAppLoaded && userStore.currentUser) {
    const isPartner = userStore.currentUser.role === 'partner' ||
                      userStore.currentUser.account_type === 'accountant' ||
                      userStore.currentUser.is_partner
    if (!isPartner) {
      // Redirect non-partner users to admin dashboard
      next({ name: 'dashboard' })
      return
    }
  }

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
// CLAUDE-CHECKPOINT
