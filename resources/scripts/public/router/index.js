const SignupLayout = () =>
  import('@/scripts/public/views/signup/SignupLayout.vue')
const PrivacyPolicy = () =>
  import('@/scripts/public/views/legal/PrivacyPolicy.vue')
const TermsOfService = () =>
  import('@/scripts/public/views/legal/TermsOfService.vue')

// NOTE: /signup and /partner/signup routes are already defined in admin-router.js
// This file only adds routes that don't exist there (legal pages)
const publicRoutes = [
  {
    path: '/privacy',
    component: SignupLayout,
    meta: { requiresAuth: false, isPublic: true },
    children: [
      {
        path: '',
        name: 'privacy',
        component: PrivacyPolicy,
        meta: { requiresAuth: false, isPublic: true },
      },
    ],
  },
  {
    path: '/terms',
    component: SignupLayout,
    meta: { requiresAuth: false, isPublic: true },
    children: [
      {
        path: '',
        name: 'terms',
        component: TermsOfService,
        meta: { requiresAuth: false, isPublic: true },
      },
    ],
  },
]

export default publicRoutes

// CLAUDE-CHECKPOINT
