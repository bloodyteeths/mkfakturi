<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
          {{ $t('partner.clients.title') }}
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          {{ $t('partner.clients.description') }}
        </p>
      </div>

      <!-- Search and Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="md:col-span-2">
            <label for="search-clients" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('partner.clients.search') }}</label>
            <input
              id="search-clients"
              v-model="searchQuery"
              type="text"
              :placeholder="$t('partner.clients.search_placeholder')"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              :aria-label="$t('partner.clients.search')"
              @input="debouncedSearch"
            />
          </div>
          <div>
            <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('partner.clients.status') }}</label>
            <select
              id="status-filter"
              v-model="statusFilter"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              :aria-label="$t('partner.clients.filter_by_status')"
              @change="loadClients"
            >
              <option value="">{{ $t('partner.clients.all') }}</option>
              <option value="active">{{ $t('partner.clients.active') }}</option>
              <option value="inactive">{{ $t('partner.clients.inactive') }}</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDeleteModal"></div>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 id="delete-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                    {{ $t('partner.clients.delete_client') }}
                  </h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      {{ $t('partner.clients.delete_confirm', { name: clientToDelete?.name }) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button
                type="button"
                :disabled="isDeleting"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                @click="confirmDelete"
              >
                {{ isDeleting ? $t('partner.clients.deleting') : $t('partner.clients.delete') }}
              </button>
              <button
                type="button"
                :disabled="isDeleting"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                @click="closeDeleteModal"
              >
                {{ $t('partner.clients.cancel') }}
              </button>
            </div>
          </div>
        </div>
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
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" :aria-label="$t('partner.clients.loading')"></div>
            </div>

            <!-- Client details -->
            <div v-else-if="selectedClient">
              <div class="bg-white px-6 pt-5 pb-4">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                  <div class="flex items-center">
                    <div class="h-14 w-14 rounded-full bg-blue-100 flex items-center justify-center">
                      <span class="text-xl font-bold text-blue-600">
                        {{ selectedClient?.name?.charAt(0)?.toUpperCase() || '?' }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <h3 class="text-xl font-bold text-gray-900">{{ selectedClient?.name || $t('partner.clients.unknown') }}</h3>
                      <p class="text-sm text-gray-500">{{ selectedClient?.email || $t('partner.clients.no_email') }}</p>
                    </div>
                  </div>
                  <button @click="closeDetailModal" class="text-gray-400 hover:text-gray-600" :aria-label="$t('partner.clients.close_modal')">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-200 mb-6" role="tablist">
                  <button
                    @click="activeTab = 'info'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                      activeTab === 'info'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                    role="tab"
                    :aria-selected="activeTab === 'info'"
                    aria-controls="info-panel"
                  >
                    {{ $t('partner.clients.info') }}
                  </button>
                  <button
                    @click="activeTab = 'accounting'; setDefaultDateRange()"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                      activeTab === 'accounting'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                    role="tab"
                    :aria-selected="activeTab === 'accounting'"
                    aria-controls="accounting-panel"
                  >
                    {{ $t('partner.clients.accounting') }}
                  </button>
                  <button
                    @click="activeTab = 'documents'"
                    :class="[
                      'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors',
                      activeTab === 'documents'
                        ? 'border-blue-500 text-blue-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                    ]"
                    role="tab"
                    :aria-selected="activeTab === 'documents'"
                    aria-controls="documents-panel"
                  >
                    {{ $t('partner.clients.documents') }}
                  </button>
                </div>

                <!-- Info Tab Content -->
                <div v-if="activeTab === 'info'" id="info-panel" role="tabpanel">
                  <!-- Subscription Info -->
                  <div class="mb-6">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('partner.clients.subscription') }}</h4>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.plan') }}</p>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ selectedClient?.subscription?.plan || 'Free' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.status') }}</p>
                        <span :class="getStatusClass(selectedClient?.subscription?.status)">
                          {{ getStatusLabel(selectedClient?.subscription?.status) }}
                        </span>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.period') }}</p>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ selectedClient?.subscription?.billing_period || 'N/A' }}</p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.monthly_price') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatCurrency(selectedClient?.subscription?.price || 0) }}</p>
                      </div>
                      <div v-if="selectedClient?.subscription?.trial_ends_at">
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.trial_until') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedClient?.subscription?.trial_ends_at) }}</p>
                      </div>
                      <div v-if="selectedClient?.subscription?.current_period_end">
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.current_period_until') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedClient?.subscription?.current_period_end) }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Commission Info -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('partner.clients.commission') }}</h4>
                  <div class="bg-green-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.rate') }}</p>
                        <p class="text-sm font-medium text-green-700">
                          {{ selectedClient?.commission?.rate || 0 }}%
                          <span v-if="selectedClient?.commission?.is_override" class="text-xs text-green-600">{{ $t('partner.clients.custom_rate') }}</span>
                        </p>
                      </div>
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.monthly_commission') }}</p>
                        <p class="text-sm font-medium text-green-700">{{ formatCurrency(selectedClient?.commission?.monthly_amount || 0) }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Client Info -->
                <div class="mb-6">
                  <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('partner.clients.info') }}</h4>
                  <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.registered') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ formatDate(selectedClient?.signup_date) }}</p>
                      </div>
                      <div v-if="selectedClient?.address">
                        <p class="text-xs text-gray-500">{{ $t('partner.clients.location') }}</p>
                        <p class="text-sm font-medium text-gray-900">{{ selectedClient?.address?.city || 'N/A' }}, {{ selectedClient?.address?.country || 'N/A' }}</p>
                      </div>
                    </div>
                  </div>
                </div>

                  <!-- Billing History -->
                  <div v-if="selectedClient?.billing_history?.length">
                    <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('partner.clients.billing_history') }}</h4>
                    <div class="bg-gray-50 rounded-lg overflow-hidden">
                      <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                          <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ $t('partner.clients.date') }}</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ $t('partner.clients.amount') }}</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500">{{ $t('partner.clients.status') }}</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                          <tr v-for="payment in selectedClient?.billing_history" :key="payment?.id">
                            <td class="px-4 py-2 text-sm text-gray-900">{{ formatDate(payment?.date) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-900">{{ formatCurrency(payment?.amount) }}</td>
                            <td class="px-4 py-2">
                              <span :class="getPaymentStatusClass(payment?.status)">{{ payment?.status || 'N/A' }}</span>
                            </td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <!-- Documents Tab Content (P8-01) -->
                <div v-if="activeTab === 'documents'" id="documents-panel" role="tabpanel">
                  <ClientDocumentsTab :company-id="selectedClient?.id" />
                </div>

                <!-- Accounting Tab Content -->
                <div v-if="activeTab === 'accounting'" id="accounting-panel" role="tabpanel">
                  <div class="space-y-6">
                    <!-- Export Description -->
                    <div class="bg-blue-50 rounded-lg p-4">
                      <p class="text-sm text-blue-700">
                        {{ $t('partner.clients.accounting_description') }}
                      </p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label for="export-date-from" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('partner.clients.date_from') }}</label>
                        <input
                          id="export-date-from"
                          v-model="exportDateFrom"
                          type="date"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          :aria-label="$t('partner.clients.date_from')"
                        />
                      </div>
                      <div>
                        <label for="export-date-to" class="block text-sm font-medium text-gray-700 mb-1">{{ $t('partner.clients.date_to') }}</label>
                        <input
                          id="export-date-to"
                          v-model="exportDateTo"
                          type="date"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                          :aria-label="$t('partner.clients.date_to')"
                        />
                      </div>
                    </div>

                    <!-- Review Entries Button (Recommended) -->
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                      <div class="flex items-start">
                        <div class="flex-shrink-0">
                          <svg class="h-6 w-6 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                        </div>
                        <div class="ml-3 flex-1">
                          <h4 class="text-sm font-medium text-green-800">{{ $t('partner.clients.recommended_review') }}</h4>
                          <p class="mt-1 text-sm text-green-700">
                            {{ $t('partner.clients.ai_classification_desc') }}
                          </p>
                          <button
                            @click="goToJournalReview"
                            :disabled="!exportDateFrom || !exportDateTo"
                            class="mt-3 inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            :aria-label="$t('partner.clients.review_entries')"
                          >
                            <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                            {{ $t('partner.clients.review_entries') }}
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
                        <span class="px-2 bg-white text-gray-500">{{ $t('partner.clients.or_direct_export') }}</span>
                      </div>
                    </div>

                    <!-- Export Format -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">{{ $t('partner.clients.export_format') }}</label>
                      <div class="grid grid-cols-3 gap-3" role="radiogroup" :aria-label="$t('partner.clients.export_format')">
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
                            <svg class="h-8 w-8 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                            <svg class="h-8 w-8 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            <span class="text-sm font-medium">{{ $t('partner.clients.pantheon') }}</span>
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
                            <svg class="h-8 w-8 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
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
                        :aria-label="$t('partner.clients.direct_export')"
                      >
                        <svg v-if="isExporting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        {{ isExporting ? $t('partner.clients.exporting') : $t('partner.clients.direct_export') }}
                      </button>
                      <p class="mt-2 text-xs text-gray-500 text-center">
                        {{ $t('partner.clients.direct_export_note') }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-6 py-3 flex justify-end">
                <button @click="closeDetailModal" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-md text-sm font-medium">
                  {{ $t('partner.clients.close') }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State with Skeleton -->
      <div v-if="isLoading" class="space-y-6">
        <!-- Stats Skeleton -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
          <div v-for="i in 4" :key="i" class="bg-white overflow-hidden shadow rounded-lg animate-pulse">
            <div class="p-5">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-8 w-8 bg-gray-200 rounded"></div>
                <div class="ml-5 w-0 flex-1">
                  <div class="h-4 bg-gray-200 rounded w-20 mb-2"></div>
                  <div class="h-6 bg-gray-200 rounded w-12"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Table Skeleton -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
          <div class="px-4 py-5 sm:px-6">
            <div class="h-6 bg-gray-200 rounded w-32 mb-2 animate-pulse"></div>
            <div class="h-4 bg-gray-200 rounded w-64 animate-pulse"></div>
          </div>
          <ul class="divide-y divide-gray-200">
            <li v-for="i in 5" :key="i" class="px-4 py-4 sm:px-6 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="flex items-center">
                  <div class="h-10 w-10 bg-gray-200 rounded-full"></div>
                  <div class="ml-4">
                    <div class="h-4 bg-gray-200 rounded w-32 mb-2"></div>
                    <div class="h-3 bg-gray-200 rounded w-24"></div>
                  </div>
                </div>
                <div class="h-8 bg-gray-200 rounded w-16"></div>
              </div>
            </li>
          </ul>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">
              {{ $t('partner.clients.load_error') }}
            </h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
            <button
              @click="loadClients"
              class="mt-2 text-sm font-medium text-red-600 hover:text-red-500"
              :aria-label="$t('partner.clients.retry')"
            >
              {{ $t('partner.clients.retry') }}
            </button>
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
                  <svg class="h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      {{ $t('partner.clients.total_clients') }}
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ pagination.total }}
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
                  <svg class="h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      {{ $t('partner.clients.active') }}
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
                  <svg class="h-8 w-8 text-yellow-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      {{ $t('partner.clients.commission_rate') }}
                    </dt>
                    <dd class="text-lg font-medium text-gray-900">
                      {{ userStore?.currentUser?.commission_rate || 0 }}%
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
                  <svg class="h-8 w-8 text-purple-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      {{ $t('partner.clients.monthly_commission') }}
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
              {{ $t('partner.clients.client_list') }}
            </h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">
              {{ $t('partner.clients.client_list_description') }}
            </p>
          </div>

          <!-- Empty State -->
          <div v-if="clients.length === 0" class="px-4 py-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900">{{ $t('partner.clients.no_clients') }}</h3>
            <p class="mt-2 text-sm text-gray-500">
              {{ searchQuery || statusFilter ? $t('partner.clients.no_search_results') : $t('partner.clients.no_assigned_companies') }}
            </p>
            <div v-if="searchQuery || statusFilter" class="mt-4">
              <button
                @click="clearFilters"
                class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                {{ $t('partner.clients.clear_filters') }}
              </button>
            </div>
          </div>

          <ul v-else role="list" class="divide-y divide-gray-200" :aria-label="$t('partner.clients.client_list')">
            <li v-for="client in clients" :key="client?.id">
              <div class="px-4 py-4 sm:px-6">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                        <span class="text-sm font-medium text-gray-700">
                          {{ client?.name?.charAt(0)?.toUpperCase() || '?' }}
                        </span>
                      </div>
                    </div>
                    <div class="ml-4">
                      <div class="flex items-center">
                        <p class="text-sm font-medium text-gray-900">
                          {{ client?.name || $t('partner.clients.unknown') }}
                        </p>
                        <span v-if="client?.is_active" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                          {{ $t('partner.clients.active') }}
                        </span>
                        <span v-else class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                          {{ $t('partner.clients.inactive') }}
                        </span>
                      </div>
                      <p class="text-sm text-gray-500">
                        {{ client?.address ? `${client?.address?.city || 'N/A'}, ${client?.address?.country || 'N/A'}` : $t('partner.clients.no_address') }}
                      </p>
                      <p class="text-xs text-gray-400">
                        {{ $t('partner.clients.commission_label') }}: {{ client?.commission_rate || 0 }}% | {{ $t('partner.clients.last_activity') }}: {{ formatDate(client?.last_activity) }}
                      </p>
                    </div>
                  </div>
                  <div class="flex items-center">
                    <div class="text-right">
                      <p class="text-sm font-medium text-gray-900">
                        {{ client?.monthly_revenue || formatCurrency(0) }}
                      </p>
                      <p class="text-sm text-gray-500">
                        {{ $t('partner.clients.monthly_revenue') }}
                      </p>
                    </div>
                    <button
                      @click="viewClientDetails(client)"
                      :disabled="loadingClientId === client?.id"
                      class="ml-4 bg-blue-100 hover:bg-blue-200 text-blue-800 px-3 py-1 rounded-md text-sm font-medium transition-colors disabled:opacity-50"
                      :aria-label="`${$t('partner.clients.details')} ${client?.name}`"
                    >
                      {{ loadingClientId === client?.id ? $t('partner.clients.loading') : $t('partner.clients.details') }}
                    </button>
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <!-- Pagination -->
          <div v-if="pagination.lastPage > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
            <div class="flex-1 flex justify-between sm:hidden">
              <button
                @click="goToPage(pagination.currentPage - 1)"
                :disabled="pagination.currentPage === 1 || isLoading"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ $t('partner.clients.previous') }}
              </button>
              <button
                @click="goToPage(pagination.currentPage + 1)"
                :disabled="pagination.currentPage === pagination.lastPage || isLoading"
                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ $t('partner.clients.next') }}
              </button>
            </div>
            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p class="text-sm text-gray-700">
                  {{ $t('partner.clients.showing_results', { from: pagination.from, to: pagination.to, total: pagination.total }) }}
                </p>
              </div>
              <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" :aria-label="$t('partner.clients.pagination')">
                  <button
                    @click="goToPage(pagination.currentPage - 1)"
                    :disabled="pagination.currentPage === 1 || isLoading"
                    class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :aria-label="$t('partner.clients.previous')"
                  >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                  <template v-for="page in visiblePages" :key="page">
                    <span v-if="page === '...'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                      ...
                    </span>
                    <button
                      v-else
                      @click="goToPage(page)"
                      :disabled="isLoading"
                      :class="[
                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium disabled:opacity-50',
                        page === pagination.currentPage
                          ? 'z-10 bg-blue-50 border-blue-500 text-blue-600'
                          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                      ]"
                      :aria-current="page === pagination.currentPage ? 'page' : undefined"
                    >
                      {{ page }}
                    </button>
                  </template>
                  <button
                    @click="goToPage(pagination.currentPage + 1)"
                    :disabled="pagination.currentPage === pagination.lastPage || isLoading"
                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    :aria-label="$t('partner.clients.next')"
                  >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </nav>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useNotificationStore } from '@/scripts/stores/notification'
import ClientDocumentsTab from './ClientDocumentsTab.vue'

const { t } = useI18n()

// Stores
const router = useRouter()
const userStore = useUserStore()
const companyStore = useCompanyStore()
const accountingStore = usePartnerAccountingStore()
const notificationStore = useNotificationStore()

// State
const clients = ref([])
const isLoading = ref(true)
const error = ref(null)
const showDetailModal = ref(false)
const loadingDetail = ref(false)
const selectedClient = ref(null)
const loadingClientId = ref(null)

// Search and filter state
const searchQuery = ref('')
const statusFilter = ref('')
const searchDebounceTimer = ref(null)

// Pagination state
const pagination = ref({
  currentPage: 1,
  lastPage: 1,
  perPage: 15,
  total: 0,
  from: 0,
  to: 0
})

// Delete confirmation state
const showDeleteModal = ref(false)
const clientToDelete = ref(null)
const isDeleting = ref(false)

// AbortController for cancelling requests
let abortController = null

// Export state
const activeTab = ref('info') // 'info' or 'accounting'
const exportDateFrom = ref('')
const exportDateTo = ref('')
const exportFormat = ref('csv')
const isExporting = ref(false)

// Computed
const activeClients = computed(() => {
  return clients.value.filter(client => client?.is_active).length
})

const monthlyCommission = computed(() => {
  const total = clients.value.reduce((sum, client) => {
    return sum + (client?.monthly_commission || 0)
  }, 0)
  return formatCurrency(total)
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.currentPage
  const last = pagination.value.lastPage

  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i)
    }
  } else {
    if (current <= 3) {
      for (let i = 1; i <= 5; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    } else if (current >= last - 2) {
      pages.push(1)
      pages.push('...')
      for (let i = last - 4; i <= last; i++) pages.push(i)
    } else {
      pages.push(1)
      pages.push('...')
      for (let i = current - 1; i <= current + 1; i++) pages.push(i)
      pages.push('...')
      pages.push(last)
    }
  }

  return pages
})

// Get currency from company store or default to EUR
const getCurrencyCode = () => {
  return companyStore?.selectedCompanyCurrency?.code || 'EUR'
}

// Methods
const loadClients = async () => {
  // Cancel any pending request
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.currentPage,
      per_page: pagination.value.perPage
    }

    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    if (statusFilter.value) {
      params.status = statusFilter.value
    }

    const { data } = await window.axios.get('/partner/clients', {
      params,
      signal: abortController.signal
    })

    // Update pagination
    pagination.value = {
      currentPage: data.current_page || 1,
      lastPage: data.last_page || 1,
      perPage: data.per_page || 15,
      total: data.total || 0,
      from: data.from || 0,
      to: data.to || 0
    }

    // Transform API data to match component expectations
    clients.value = (data.data || []).map(client => ({
      id: client?.id,
      name: client?.name || t('partner.clients.unknown'),
      is_active: client?.status === 'active',
      commission_rate: client?.commission || 0,
      monthly_revenue: formatCurrency(client?.mrr || 0),
      monthly_commission: client?.commission || 0,
      last_activity: client?.signup_date,
      address: {
        city: client?.city || 'N/A',
        country: client?.country || t('partner.clients.default_country')
      },
      plan: client?.plan
    }))
  } catch (err) {
    // Don't show error for aborted requests
    if (err?.name === 'AbortError' || err?.code === 'ERR_CANCELED') {
      return
    }
    error.value = err?.response?.data?.message || t('partner.clients.load_clients_error')
    notificationStore.showNotification({
      type: 'error',
      message: error.value
    })
  } finally {
    isLoading.value = false
  }
}

const debouncedSearch = () => {
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }
  searchDebounceTimer.value = setTimeout(() => {
    pagination.value.currentPage = 1
    loadClients()
  }, 300)
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  pagination.value.currentPage = 1
  loadClients()
}

const goToPage = (page) => {
  if (page < 1 || page > pagination.value.lastPage || page === pagination.value.currentPage) return
  pagination.value.currentPage = page
  loadClients()
}

const viewClientDetails = async (client) => {
  if (!client?.id) return

  showDetailModal.value = true
  loadingDetail.value = true
  loadingClientId.value = client.id
  selectedClient.value = null

  try {
    const { data } = await window.axios.get(`/partner/clients/${client.id}`)
    selectedClient.value = data?.data || data
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || t('partner.clients.load_details_error')
    })
    showDetailModal.value = false
  } finally {
    loadingDetail.value = false
    loadingClientId.value = null
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

// Delete functionality
const openDeleteModal = (client) => {
  clientToDelete.value = client
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  clientToDelete.value = null
}

const confirmDelete = async () => {
  if (!clientToDelete.value?.id) return

  isDeleting.value = true

  try {
    await window.axios.delete(`/partner/clients/${clientToDelete.value.id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('partner.clients.delete_success')
    })
    closeDeleteModal()
    loadClients()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || t('partner.clients.delete_error')
    })
  } finally {
    isDeleting.value = false
  }
}

// Export journal for selected client
const exportJournal = async () => {
  if (!selectedClient.value?.id) return

  if (!exportDateFrom.value || !exportDateTo.value) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.clients.export_date_required')
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
      message: t('partner.clients.export_success')
    })
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || t('partner.clients.export_error')
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
  if (!selectedClient.value?.id) return

  const companyId = selectedClient.value.id
  const startDate = exportDateFrom.value
  const endDate = exportDateTo.value

  // Close modal first
  closeDetailModal()

  // Navigate to Journal Review with company and dates using path
  // Use path instead of name for cross-router navigation
  const url = `/admin/partner/accounting/review?company_id=${companyId}&start_date=${startDate}&end_date=${endDate}`
  router.push(url).catch((err) => {
    // Fallback to window.location if router.push fails
    if (err?.name !== 'NavigationDuplicated') {
      window.location.href = url
    }
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
    active: t('partner.clients.status_active'),
    trialing: t('partner.clients.status_trialing'),
    canceled: t('partner.clients.status_canceled'),
    suspended: t('partner.clients.status_suspended'),
  }
  return labels[status] || t('partner.clients.status_inactive')
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
  const currencyCode = getCurrencyCode()
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currencyCode
  }).format(amount || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return t('partner.clients.never')

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

// Cleanup on unmount
onUnmounted(() => {
  if (abortController) {
    abortController.abort()
  }
  if (searchDebounceTimer.value) {
    clearTimeout(searchDebounceTimer.value)
  }
})

// Lifecycle
onMounted(() => {
  loadClients()
})
</script>

