<template>
  <div class="relative min-h-screen bg-gray-50">
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
                v-if="userStore.hasAbilities('view-clients')"
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
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { useUserStore } from '@/scripts/partner/stores/user'
import { useRouter } from 'vue-router'

const userStore = useUserStore()
const router = useRouter()

const handleLogout = async () => {
  await userStore.logout()
  router.push({ name: 'partner.login' })
}
</script>
<!-- CLAUDE-CHECKPOINT -->

