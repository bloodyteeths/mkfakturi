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

      <!-- Client Detail Modal -->
      <div v-if="showDetailModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Backdrop -->
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDetailModal"></div>

          <!-- Modal panel -->
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <!-- Loading state -->
            <div v-if="loadingDetail" class="p-8 flex justify-center">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            </div>

            <!-- Client details -->
            <div v-else-if="selectedClient">
              <div class="bg-white px-6 pt-5 pb-4">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                  <div class="flex items-center">
                    <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center">
                      <span class="text-xl font-bold text-blue-600">
                        {{ selectedClient.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <h3 class="text-xl font-bold text-gray-900">{{ selectedClient.name }}</h3>
                      <p class="text-sm text-gray-500">{{ selectedClient.email }}</p>
                    </div>
                  </div>
                  <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mb-6">
                  <button
                    @click="activeTab = 'info'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                      activeTab === 'info'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Информации
                  </button>
                  <button
                    @click="activeTab = 'accounting'; setDefaultDateRange()"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                      activeTab === 'accounting'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                  >
                    Книговодство
                  </button>
                </div>

                <!-- Info Tab Content -->
                <div v-if="activeTab === 'info'">
                  <!-- Subscription Info -->
                  <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Претплата</h4>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-xs text-gray-500">План</p>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ selectedClient.subscription?.plan || 'Free' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">Статус</p>
                        <span :class="getStatusClass(selectedClient.subscription?.status)">
                          {{ getStatusLabel(selectedClient.subscription?.status) }}
                        </span>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">Период</p>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ selectedClient.subscription?.billing_period || 'N/A' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">Месечна цена</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatCurrency(selectedClient.subscription?.price || 0) }}</p>
                      </div>
                      <div v-if="selectedClient.subscription?.trial_ends_at">
                        <p class="text-xs text-gray-500">Пробен период до</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedClient.subscription.trial_ends_at) }}</p>
                      </div>
                      <div v-if="selectedClient.subscription?.current_period_end">
                        <p class="text-xs text-gray-500">Тековен период до</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedClient.subscription.current_period_end) }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Commission Info -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-900 mb-3">Провизија</h4>
                  <div class="bg-green-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-xs text-gray-500">Стапка</p>
                        <p class="text-sm font-medium text-green-700">
                          {{ selectedClient.commission?.rate }}%
                          <span v-if="selectedClient.commission?.is_override" class="text-xs text-green-600">(прилагодена)</span>
                        </p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">Месечна провизија</p>
                        <p class="text-sm font-medium text-green-700">{{ formatCurrency(selectedClient.commission?.monthly_amount || 0) }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Client Info -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-900 mb-3">Информации</h4>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-xs text-gray-500">Регистриран</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedClient.signup_date) }}</p>
                      </div>
                      <div v-if="selectedClient.address">
                        <p class="text-xs text-gray-500">Локација</p>
                        <p class="text-sm font-medium text-gray-900">{{ selectedClient.address.city }}, {{ selectedClient.address.country }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                  <!-- Billing History -->
                  <div v-if="selectedClient.billing_history?.length">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">Историја на плаќања</h4>
                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                      <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                          <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Датум</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Износ</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Статус</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                          <tr v-for="payment in selectedClient.billing_history" :key="payment.id">
                            <td class="px-4 py-2 text-sm text-gray-900">{{ formatDate(payment.date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ formatCurrency(payment.amount) }}</td>
                            <td class="px-4 py-2">
                              <span :class="getPaymentStatusClass(payment.status)">{{ payment.status }}</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Accounting Tab Content -->
                <div v-if="activeTab === 'accounting'">
                  <div class="space-y-6">
                    <!-- Export Description -->
                    <div class="bg-blue-50 rounded-lg p-4">
                      <p class="text-sm text-blue-700">
                        Прегледајте и проверете ги книговодствените записи со АИ класификација на сметки, или директно извезете во формат компатибилен со Пантеон, Zonel или CSV.
                      </p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Датум од</label>
                        <input
                          v-model="exportDateFrom"
                          type="date"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Датум до</label>
                        <input
                          v-model="exportDateTo"
                          type="date"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                      </div>
                    </div>

                    <!-- Review Entries Button (Recommended) -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                      <div class="flex items-start">
                        <div class="flex-shrink-0">
                          <svg class="h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <div class="ml-3 flex-1">
                          <h4 class="text-sm font-medium text-green-800">Препорачано: Преглед пред извоз</h4>
                          <p class="mt-1 text-sm text-green-700">
                            АИ автоматски класифицира записи (стока, услуга, производ). Прегледајте и потврдете ги сметките пред извоз.
                          </p>
                          <button
                            @click="goToJournalReview"
                            :disabled="!exportDateFrom || !exportDateTo"
                            class="mt-3 inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                          >
                            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            Прегледај записи
                          </button>
                        </div>
                      </div>
                    </div>

                    <!-- Divider -->
                    <div class="relative">
                      <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                      </div>
                      <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">или директен извоз</span>
                      </div>
                    </div>

                    <!-- Export Format -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">Формат на извоз</label>
                      <div class="grid grid-cols-3 gap-3">
                        <label
                          :class="[
                            'relative flex items-center justify-center p-4 border rounded-lg cursor-pointer transition-colors',
                            exportFormat === 'csv'
                              ? 'border-blue-500 bg-blue-50 text-blue-700'
                              : 'border-gray-200 hover:border-gray-300'
                          ]"
                        >
                          <input v-model="exportFormat" type="radio" value="csv" class="sr-only" />
                          <div class="text-center">
                            <svg class="h-8 w-8 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span class="text-sm font-medium">CSV</span>
                          </div>
                        </label>
                        <label
                          :class="[
                            'relative flex items-center justify-center p-4 border rounded-lg cursor-pointer transition-colors',
                            exportFormat === 'pantheon'
                              ? 'border-blue-500 bg-blue-50 text-blue-700'
                              : 'border-gray-200 hover:border-gray-300'
                          ]"
                        >
                          <input v-model="exportFormat" type="radio" value="pantheon" class="sr-only" />
                          <div class="text-center">
                            <svg class="h-8 w-8 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            <span class="text-sm font-medium">Пантеон</span>
                          </div>
                        </label>
                        <label
                          :class="[
                            'relative flex items-center justify-center p-4 border rounded-lg cursor-pointer transition-colors',
                            exportFormat === 'zonel'
                              ? 'border-blue-500 bg-blue-50 text-blue-700'
                              : 'border-gray-200 hover:border-gray-300'
                          ]"
                        >
                          <input v-model="exportFormat" type="radio" value="zonel" class="sr-only" />
                          <div class="text-center">
                            <svg class="h-8 w-8 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                            <span class="text-sm font-medium">Zonel</span>
                          </div>
                        </label>
                      </div>
                    </div>

                    <!-- Direct Export Button -->
                    <div class="pt-2">
                      <button
                        @click="exportJournal"
                        :disabled="isExporting || !exportDateFrom || !exportDateTo"
                        class="w-full flex items-center justify-center px-4 py-3 bg-gray-600 text-white rounded-lg font-medium hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                      >
                        <svg v-if="isExporting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ isExporting ? 'Извезување...' : 'Директен извоз (без преглед)' }}
                      </button>
                      <p class="mt-2 text-xs text-gray-500 text-center">
                        Директниот извоз користи АИ класификација, но без рачна проверка
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-6 py-3 flex justify-end">
                <button @click="closeDetailModal" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                  Затвори
                </button>
              </div>
            </div>
          </div>
        </div>
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
import { useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/partner/stores/user'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'

// Stores
const router = useRouter()
const userStore = useUserStore()
const accountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

// State
const clients = ref([])
const isLoading = ref(true)
const error = ref(null)
const showDetailModal = ref(false)
const loadingDetail = ref(false)
const selectedClient = ref(null)

// Export state
const activeTab = ref('info') // 'info' or 'accounting'
const exportDateFrom = ref('')
const exportDateTo = ref('')
const exportFormat = ref('csv')
const isExporting = ref(false)

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
    currency: 'EUR'
  }).format(total)
})

// Methods
const loadClients = async () => {
  isLoading.value = true
  error.value = null

  try {
    const { data } = await window.axios.get('/partner/clients')

    // Transform API data to match component expectations
    clients.value = data.data.map(client => ({
      id: client.id,
      name: client.name,
      is_active: client.status === 'active',
      commission_rate: client.commission,
      monthly_revenue: new Intl.NumberFormat('mk-MK', {
        style: 'currency',
        currency: 'EUR'
      }).format(client.mrr),
      monthly_commission: client.commission,
      last_activity: client.signup_date,
      address: {
        city: 'N/A',
        country: 'Македонија'
      },
      plan: client.plan
    }))
  } catch (err) {
    error.value = 'Не можеше да се вчитаат клиентите. Обидете се повторно.'
    console.error('Error loading clients:', err)
  } finally {
    isLoading.value = false
  }
}

const viewClientDetails = async (client) => {
  showDetailModal.value = true
  loadingDetail.value = true
  selectedClient.value = null

  try {
    const { data } = await window.axios.get(`/partner/clients/${client.id}`)
    selectedClient.value = data.data
  } catch (err) {
    console.error('Error loading client details:', err)
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
  }
}

const closeDetailModal = () => {
  showDetailModal.value = false
  selectedClient.value = null
  activeTab.value = 'info'
  exportDateFrom.value = ''
  exportDateTo.value = ''
  exportFormat.value = 'csv'
}

// Export journal for selected client
const exportJournal = async () => {
  if (!selectedClient.value) return

  if (!exportDateFrom.value || !exportDateTo.value) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Изберете датум од и до за извоз'
    })
    return
  }

  isExporting.value = true

  try {
    await accountingStore.exportJournal(selectedClient.value.id, {
      format: exportFormat.value,
      start_date: exportDateFrom.value,
      end_date: exportDateTo.value
    })

    notificationStore.showNotification({
      type: 'success',
      message: 'Дневникот е успешно извезен'
    })
  } catch (err) {
    console.error('Export failed:', err)
    notificationStore.showNotification({
      type: 'error',
      message: err.response?.data?.message || 'Грешка при извоз на дневникот'
    })
  } finally {
    isExporting.value = false
  }
}

// Set default date range (current month)
const setDefaultDateRange = () => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), now.getMonth(), 1)
  const lastDay = new Date(now.getFullYear(), now.getMonth() + 1, 0)

  exportDateFrom.value = firstDay.toISOString().split('T')[0]
  exportDateTo.value = lastDay.toISOString().split('T')[0]
}

// Navigate to Journal Review page with company pre-selected
const goToJournalReview = () => {
  if (!selectedClient.value) return

  // Close modal first
  closeDetailModal()

  // Navigate to Journal Review with company and dates
  router.push({
    name: 'partner.accounting.review',
    query: {
      company_id: selectedClient.value.id,
      start_date: exportDateFrom.value,
      end_date: exportDateTo.value,
    },
  })
}

const getStatusClass = (status) => {
  const classes = {
    active: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
    trialing: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
    canceled: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
    suspended: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
  }
  return classes[status] || 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    active: 'Активна',
    trialing: 'Пробен период',
    canceled: 'Откажана',
    suspended: 'Суспендирана',
  }
  return labels[status] || 'Неактивна'
}

const getPaymentStatusClass = (status) => {
  const classes = {
    paid: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800',
    pending: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800',
    failed: 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800',
  }
  return classes[status] || 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800'
}

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'EUR'
  }).format(amount)
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

// CLAUDE-CHECKPOINT

