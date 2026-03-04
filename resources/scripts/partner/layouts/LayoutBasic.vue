<template>
  <div class="flex flex-col min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <!-- Logo -->
            <div class="flex-shrink-0">
              <h1 class="text-xl font-bold text-gray-900">
                {{ t('partner.portal.title') }}
              </h1>
            </div>

            <!-- Navigation Links -->
            <div class="hidden md:ml-6 md:flex md:space-x-8">
              <router-link
                :to="{ name: 'partner.dashboard', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.dashboard'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                {{ t('partner.navigation.dashboard') }}
              </router-link>
              <router-link
                :to="{ name: 'partner.clients', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.clients'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                v-if="hasAbilities('view-clients')"
              >
                {{ t('partner.navigation.clients') }}
              </router-link>
              <router-link
                :to="{ name: 'partner.commissions', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.commissions'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                {{ t('partner.navigation.commissions') }}
              </router-link>
              <router-link
                :to="{ name: 'partner.portfolio', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name?.startsWith('partner.portfolio')
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                {{ t('partner.navigation.portfolio') }}
              </router-link>
              <router-link
                :to="{ name: 'partner.referrals', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.referrals'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                {{ t('partner.navigation.referrals') }}
              </router-link>
              <router-link
                :to="{ name: 'partner.invitations', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.invitations'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                {{ t('partner.navigation.invitations') }}
              </router-link>
              <router-link
                :to="{ name: 'partner.payouts', query: partnerQuery }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.payouts'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                {{ t('partner.navigation.payouts') }}
              </router-link>
            </div>
          </div>

          <!-- User Menu -->
          <div class="flex items-center">
            <div class="relative ml-3">
              <div class="flex items-center space-x-4">
                <!-- Console Switch Button -->
                <button
                  @click="goToConsole"
                  class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  {{ t('partner.navigation.console') }}
                </button>

                <!-- Language Switcher -->
                <BaseDropdown width-class="w-48">
                  <template #activator>
                    <div
                      class="
                        flex
                        items-center
                        justify-center
                        w-8
                        h-8
                        text-sm text-black
                        bg-gray-100
                        rounded
                        cursor-pointer
                        hover:bg-gray-200
                      "
                    >
                      <BaseIcon name="LanguageIcon" class="w-5 h-5 text-gray-600" />
                    </div>
                  </template>

                  <BaseDropdownItem @click="setLanguage('en')">
                    <div class="flex items-center">
                      <span class="mr-3 text-base">🇺🇸</span>
                      English
                      <span v-if="currentLocale === 'en'" class="ml-auto text-green-500">✓</span>
                    </div>
                  </BaseDropdownItem>

                  <BaseDropdownItem @click="setLanguage('mk')">
                    <div class="flex items-center">
                      <span class="mr-3 text-base">🇲🇰</span>
                      Македонски
                      <span v-if="currentLocale === 'mk'" class="ml-auto text-green-500">✓</span>
                    </div>
                  </BaseDropdownItem>

                  <BaseDropdownItem @click="setLanguage('sq')">
                    <div class="flex items-center">
                      <span class="mr-3 text-base">🇦🇱</span>
                      Shqip
                      <span v-if="currentLocale === 'sq'" class="ml-auto text-green-500">✓</span>
                    </div>
                  </BaseDropdownItem>

                  <BaseDropdownItem @click="setLanguage('tr')">
                    <div class="flex items-center">
                      <span class="mr-3 text-base">🇹🇷</span>
                      Türkçe
                      <span v-if="currentLocale === 'tr'" class="ml-auto text-green-500">✓</span>
                    </div>
                  </BaseDropdownItem>
                </BaseDropdown>

                <span class="text-sm text-gray-700">
                  {{ userStore.currentUser?.name || 'Partner' }}
                </span>
                <button
                  @click="handleLogout"
                  class="bg-white text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <span class="sr-only">{{ t('partner.navigation.logout') }}</span>
                  {{ t('partner.navigation.logout') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Impersonation Banner -->
    <div v-if="route.query.partner_id" class="bg-yellow-500 text-yellow-900 text-center py-2 text-sm font-medium">
      Super Admin: Viewing partner portal as Partner #{{ route.query.partner_id }}
      <router-link :to="{ name: 'dashboard' }" class="ml-4 underline hover:no-underline">
        Exit
      </router-link>
    </div>

    <!-- Main Content -->
    <main class="flex-1 overflow-auto">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup>
import { onMounted, onUnmounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useAuthStore } from '@/scripts/admin/stores/auth'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useRouter, useRoute } from 'vue-router'

// i18n
const { t, locale } = useI18n()

// Use the MAIN user store (same one used by main login)
const userStore = useUserStore()
const authStore = useAuthStore()
const globalStore = useGlobalStore()
const router = useRouter()
const route = useRoute()

// Preserve partner_id query param for super admin impersonation
const partnerQuery = computed(() => {
  return route.query.partner_id ? { partner_id: route.query.partner_id } : {}
})

// Current locale for highlighting the active language
const currentLocale = computed(() => locale.value)

// Create a computed wrapper for abilities check (partners have specific abilities)
const hasAbilities = (ability) => {
  const partnerAbilities = [
    'view-dashboard',
    'view-commissions',
    'view-clients',
    'manage-profile'
  ]

  if (Array.isArray(ability)) {
    return ability.every(a => partnerAbilities.includes(a))
  }

  return partnerAbilities.includes(ability)
}

// Navigate to partner console
const goToConsole = () => {
  router.push('/admin/console')
}

/**
 * Set the active language.
 * Saves preference to localStorage, updates vue-i18n locale,
 * and syncs with the global store (same pattern as TheSiteHeader.vue).
 */
function setLanguage(newLocale) {
  locale.value = newLocale
  localStorage.setItem('invoiceshelf_locale', newLocale)
  try {
    globalStore.updateLanguage(newLocale)
  } catch (error) {
    console.log('Global store updateLanguage not available, using localStorage only')
  }
}

// Override body overflow on mount (fix scroll issue)
// The app.blade.php has overflow-hidden class on body that blocks scrolling
onMounted(async () => {
  document.body.classList.remove('overflow-hidden', 'h-full')
  document.body.classList.add('overflow-auto', 'h-auto')
  document.documentElement.style.overflow = 'auto'
  document.documentElement.style.height = 'auto'

  // Load user data if not already loaded
  if (!userStore.currentUser) {
    try {
      await userStore.fetchCurrentUser()
    } catch (e) {
      console.error('Failed to load user data:', e)
    }
  }
})

onUnmounted(() => {
  document.body.classList.remove('overflow-auto', 'h-auto')
  document.body.classList.add('overflow-hidden', 'h-full')
  document.documentElement.style.overflow = ''
  document.documentElement.style.height = ''
})

const handleLogout = async () => {
  console.log('Logout button clicked')
  try {
    console.log('authStore:', authStore)
    console.log('authStore.logout:', authStore.logout)
    await authStore.logout()
    console.log('Logout completed')
  } catch (error) {
    console.error('Logout error:', error)
    // Fallback: redirect to login anyway
    window.location.href = '/login'
  }
}
</script>
<!-- CLAUDE-CHECKPOINT -->

