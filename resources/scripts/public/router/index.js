const SignupLayout = () =>
  import('@/scripts/public/views/signup/SignupLayout.vue')
const Signup = () => import('@/scripts/public/views/signup/Signup.vue')
const PartnerSignup = () =>
  import('@/scripts/public/views/partner-signup/PartnerSignup.vue')
const PrivacyPolicy = () =>
  import('@/scripts/public/views/legal/PrivacyPolicy.vue')
const TermsOfService = () =>
  import('@/scripts/public/views/legal/TermsOfService.vue')

const publicRoutes = [
  {
    path: '/signup',
    component: SignupLayout,
    children: [
      {
        path: '',
        name: 'signup',
        component: Signup,
        meta: {
          requiresAuth: false,
        },
      },
    ],
  },
  {
    path: '/partner/signup',
    component: SignupLayout,
    children: [
      {
        path: '',
        name: 'partner-signup',
        component: PartnerSignup,
        meta: {
          requiresAuth: false,
        },
      },
    ],
  },
  {
    path: '/privacy',
    component: SignupLayout,
    children: [
      {
        path: '',
        name: 'privacy',
        component: PrivacyPolicy,
        meta: {
          requiresAuth: false,
        },
      },
    ],
  },
  {
    path: '/terms',
    component: SignupLayout,
    children: [
      {
        path: '',
        name: 'terms',
        component: TermsOfService,
        meta: {
          requiresAuth: false,
        },
      },
    ],
  },
]

export default publicRoutes

// CLAUDE-CHECKPOINT
