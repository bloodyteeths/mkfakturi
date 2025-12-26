const LayoutBasic = () => import('@/scripts/partner/layouts/LayoutBasic.vue')
const Dashboard = () => import('@/scripts/partner/views/dashboard/Dashboard.vue')
const Clients = () => import('@/scripts/partner/views/clients/Clients.vue')
const Referrals = () => import('@/js/pages/partner/Referrals.vue')
const Payouts = () => import('@/js/pages/partner/Payouts.vue')
const Invitations = () => import('@/js/pages/partner/Invitations.vue')
const Commissions = () => import('@/js/pages/partner/Commissions.vue')

export default [
  {
    path: '/admin/partner',
    component: LayoutBasic,
    meta: { requiresAuth: true, isPartner: true },
    children: [
      {
        path: '',
        redirect: { name: 'partner.dashboard' }
      },
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
      {
        path: 'invitations',
        name: 'partner.invitations',
        component: Invitations,
        meta: { isPartner: true }
      },
      {
        path: 'commissions',
        name: 'partner.commissions',
        component: Commissions,
        meta: { isPartner: true }
      },
    ]
  }
]
// CLAUDE-CHECKPOINT
