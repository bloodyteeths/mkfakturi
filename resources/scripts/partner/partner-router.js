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
      {
        path: 'onboarding',
        name: 'partner.onboarding',
        component: () => import('@/scripts/admin/views/partner/onboarding/PartnerOnboardingWizard.vue'),
        meta: { isPartner: true }
      },
      {
        path: 'portfolio',
        name: 'partner.portfolio',
        component: () => import('@/scripts/partner/views/portfolio/PortfolioDashboard.vue'),
        meta: { isPartner: true }
      },
      {
        path: 'portfolio/companies/create',
        name: 'partner.portfolio.companies.create',
        component: () => import('@/scripts/partner/views/portfolio/PortfolioCompanyCreate.vue'),
        meta: { isPartner: true }
      },
      {
        path: 'portfolio/companies/import',
        name: 'partner.portfolio.companies.import',
        component: () => import('@/scripts/partner/views/portfolio/PortfolioCompanyImport.vue'),
        meta: { isPartner: true }
      },
      {
        path: 'deadlines',
        name: 'partner.deadlines',
        component: () => import('@/scripts/partner/views/deadlines/DeadlinesDashboard.vue'),
        meta: { isPartner: true }
      },
      {
        path: 'reports',
        name: 'partner.reports',
        component: () => import('@/scripts/partner/views/reports/BulkReports.vue'),
        meta: { isPartner: true }
      },
      {
        path: 'billing',
        name: 'partner.billing',
        component: () => import('@/scripts/partner/views/billing/PartnerBilling.vue'),
        meta: { isPartner: true }
      },
    ]
  }
]
