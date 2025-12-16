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
                Partner Portal
              </h1>
            </div>
            
            <!-- Navigation Links -->
            <div class="hidden md:ml-6 md:flex md:space-x-8">
              <router-link
                :to="{ name: 'partner.dashboard' }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.dashboard'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                Контролна Табла
              </router-link>
              <router-link
                :to="{ name: 'partner.clients' }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.clients'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                v-if="hasAbilities('view-clients')"
              >
                Клиенти
              </router-link>
              <router-link
                :to="{ name: 'partner.referrals' }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.referrals'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                Препораки
              </router-link>
              <router-link
                :to="{ name: 'partner.payouts' }"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2"
                :class="$route.name === 'partner.payouts'
                  ? 'border-blue-500 text-gray-900'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
              >
                Исплати
              </router-link>
              <a
                :href="`/admin/console`"
                class="inline-flex items-center px-1 pt-1 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
              >
                Конзола
              </a>
            </div>
          </div>

          <!-- User Menu -->
          <div class="flex items-center">
            <div class="relative ml-3">
              <div class="flex items-center space-x-4">
                <span class="text-sm text-gray-700">
                  {{ userStore.currentUser.name }}
                </span>
                <button
                  @click="handleLogout"
                  class="bg-white text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                  <span class="sr-only">Одјави се</span>
                  Одјави се
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

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
import { useUserStore } from '@/scripts/admin/stores/user'
import { useRouter } from 'vue-router'

// Use the MAIN user store (same one used by main login)
const userStore = useUserStore()
const router = useRouter()

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

// Override body overflow on mount (fix scroll issue)
// The app.blade.php has overflow-hidden class on body that blocks scrolling
onMounted(() => {
  document.body.classList.remove('overflow-hidden', 'h-full')
  document.body.classList.add('overflow-auto', 'h-auto')
  document.documentElement.style.overflow = 'auto'
  document.documentElement.style.height = 'auto'
})

onUnmounted(() => {
  document.body.classList.remove('overflow-auto', 'h-auto')
  document.body.classList.add('overflow-hidden', 'h-full')
  document.documentElement.style.overflow = ''
  document.documentElement.style.height = ''
})

const handleLogout = async () => {
  await userStore.logout()
  router.push({ name: 'login' })
}
</script>
<!-- CLAUDE-CHECKPOINT -->

