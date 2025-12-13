const LayoutBasic = () => import('@/scripts/partner/layouts/LayoutBasic.vue')
const Dashboard = () => import('@/scripts/partner/views/dashboard/Dashboard.vue')
const Clients = () => import('@/scripts/partner/views/clients/Clients.vue')
const Referrals = () => import('@/js/pages/partner/Referrals.vue')
const Payouts = () => import('@/js/pages/partner/Payouts.vue')
const PartnerLogin = () => import('@/scripts/partner/views/auth/Login.vue')
const PartnerForgotPassword = () => import('@/scripts/partner/views/auth/ForgotPassword.vue')
const PartnerResetPassword = () => import('@/scripts/partner/views/auth/ResetPassword.vue')

export default [
  {
    path: '/admin/partner/login',
    name: 'partner.login',
    component: PartnerLogin,
    meta: { requiresAuth: false, redirectIfAuthenticated: true },
  },
  {
    path: '/admin/partner/forgot-password',
    name: 'partner.forgot-password',
    component: PartnerForgotPassword,
    meta: { requiresAuth: false, redirectIfAuthenticated: true },
  },
  {
    path: '/admin/partner/reset-password/:token',
    name: 'partner.reset-password',
    component: PartnerResetPassword,
    meta: { requiresAuth: false, redirectIfAuthenticated: true },
    props: true,
  },
  {
    path: '/admin/partner',
    component: LayoutBasic,
    meta: { requiresAuth: true, isPartner: true },
    children: [
      {
        path: 'dashboard',
        name: 'partner.dashboard',
        component: Dashboard,
        meta: { isPartner: true }
      },
      {
        path: 'clients',
        name: 'partner.clients',
        component: Clients,
        meta: { isPartner: true, ability: 'view-clients' }
      },
      {
        path: 'referrals',
        name: 'partner.referrals',
        component: Referrals,
        meta: { isPartner: true }
      },
      {
        path: 'payouts',
        name: 'partner.payouts',
        component: Payouts,
        meta: { isPartner: true }
      },
      // Additional partner routes can be added here
      // Example: commissions, reports, etc.
    ]
  }
]
// CLAUDE-CHECKPOINT
