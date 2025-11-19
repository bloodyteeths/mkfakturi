const LayoutBasic = () => import('@/scripts/partner/layouts/LayoutBasic.vue')
const Dashboard = () => import('@/scripts/partner/views/dashboard/Dashboard.vue')
const Clients = () => import('@/scripts/partner/views/clients/Clients.vue')
const Referrals = () => import('@/js/pages/partner/Referrals.vue')
const Payouts = () => import('@/js/pages/partner/Payouts.vue')

export default [
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

