const LayoutBasic = () => import('@/scripts/partner/layouts/LayoutBasic.vue')
const LayoutLogin = () => import('@/scripts/partner/layouts/LayoutLogin.vue')
const Login = () => import('@/scripts/partner/views/auth/Login.vue')
const ForgotPassword = () => import('@/scripts/partner/views/auth/ForgotPassword.vue')
const ResetPassword = () => import('@/scripts/partner/views/auth/ResetPassword.vue')
const Dashboard = () => import('@/scripts/partner/views/dashboard/Dashboard.vue')
const Clients = () => import('@/scripts/partner/views/clients/Clients.vue')

export default [
  {
    path: '/:company/partner',
    component: LayoutLogin,
    meta: { redirectIfAuthenticated: true },
    children: [
      {
        path: '',
        component: Login,
      },
      {
        path: 'login',
        component: Login,
        name: 'partner.login',
        meta: { redirectIfAuthenticated: true },
      },
      {
        path: 'forgot-password',
        component: ForgotPassword,
        name: 'partner.forgot.password',
        meta: { redirectIfAuthenticated: true },
      },
      {
        path: 'reset-password',
        component: ResetPassword,
        name: 'partner.reset.password',
        meta: { redirectIfAuthenticated: true },
      },
    ]
  },
  {
    path: '/:company/partner',
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
      // Additional partner routes can be added here
      // Example: commissions, reports, etc.
    ]
  }
]

