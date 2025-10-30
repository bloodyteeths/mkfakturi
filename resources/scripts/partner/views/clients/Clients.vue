<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
          Клиенти
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Преглед на компаниите доделени на вашето партнерство
        </p>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              Грешка при вчитување на клиентите
            </h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Clients List -->
      <div v-else>
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Вкупно клиенти
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ clients.length }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Активни
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ activeClients }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Провизија %
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ userStore.currentUser.commission_rate }}%
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="h-8 w-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Месечна провизија
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ monthlyCommission }}
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Clients Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
              Листа на клиенти
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              Детални информации за компаниите доделени на вашето партнерство
            </p>
          </div>

          <div v-if="clients.length === 0" class="px-4 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Нема клиенти</h3>
            <p class="mt-1 text-sm text-gray-500">
              Сè уште нема доделени компании на вашето партнерство.
            </p>
          </div>

          <ul v-else role="list" class="divide-y divide-gray-200">
            <li v-for="client in clients" :key="client.id">
              <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                          {{ client.name.charAt(0).toUpperCase() }}
                        </span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="flex items-center">
                        <p class="text-sm font-medium text-gray-900">
                          {{ client.name }}
                        </p>
                        <span v-if="client.is_active" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          Активен
                        </span>
                        <span v-else class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          Неактивен
                        </span>
                      </div>
                      <p class="text-sm text-gray-500">
                        {{ client.address ? `${client.address.city}, ${client.address.country}` : 'Нема адреса' }}
                      </p>
                      <p class="text-xs text-gray-400">
                        Провизија: {{ client.commission_rate }}% | Последна активност: {{ formatDate(client.last_activity) }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center">
                    <div class="text-right">
                      <p class="text-sm font-medium text-gray-900">
                        {{ client.monthly_revenue }}
                      </p>
                      <p class="text-sm text-gray-500">
                        месечен приход
                      </p>
                    </div>
                    <button 
                      @click="viewClientDetails(client)"
                      class="ml-4 bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-md text-sm font-medium transition-colors"
                    >
                      Детали
                    </button>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/scripts/partner/stores/user'

// Stores
const userStore = useUserStore()

// State
const clients = ref([])
const isLoading = ref(true)
const error = ref(null)

// Computed
const activeClients = computed(() => {
  return clients.value.filter(client => client.is_active).length
})

const monthlyCommission = computed(() => {
  const total = clients.value.reduce((sum, client) => {
    return sum + (client.monthly_commission || 0)
  }, 0)
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'MKD'
  }).format(total)
})

// Methods
const loadClients = async () => {
  isLoading.value = true
  error.value = null
  
  try {
    // Simulate API call - replace with actual API endpoint
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Mock client data based on partner relationship structure
    clients.value = [
      {
        id: 1,
        name: 'Македонска Трговија ДООЕЛ',
        is_active: true,
        commission_rate: 15.0,
        monthly_revenue: '125,000 МКД',
        monthly_commission: 18750,
        last_activity: '2024-01-15',
        address: {
          city: 'Скопје',
          country: 'Македонија'
        }
      },
      {
        id: 2,
        name: 'Охридски Туризам ДОО',
        is_active: true,
        commission_rate: 12.0,
        monthly_revenue: '89,500 МКД',
        monthly_commission: 10740,
        last_activity: '2024-01-12',
        address: {
          city: 'Охрид',
          country: 'Македонија'
        }
      },
      {
        id: 3,
        name: 'Битолски Занаети ДООЕЛ',
        is_active: false,
        commission_rate: 15.0,
        monthly_revenue: '45,200 МКД',
        monthly_commission: 6780,
        last_activity: '2023-12-20',
        address: {
          city: 'Битола',
          country: 'Македонија'
        }
      }
    ]
  } catch (err) {
    error.value = 'Не можеше да се вчитаат клиентите. Обидете се повторно.'
    console.error('Error loading clients:', err)
  } finally {
    isLoading.value = false
  }
}

const viewClientDetails = (client) => {
  // Navigate to client detail view or open modal
  console.log('Viewing details for client:', client.name)
  // TODO: Implement client detail navigation
}

const formatDate = (dateString) => {
  if (!dateString) return 'Никогаш'
  
  try {
    return new Date(dateString).toLocaleDateString('mk-MK', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch {
    return dateString
  }
}

// Lifecycle
onMounted(() => {
  loadClients()
})
</script>

