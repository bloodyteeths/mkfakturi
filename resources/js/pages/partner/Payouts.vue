<template>
  <div class="payouts-page min-h-screen overflow-auto pb-8" role="main" aria-label="Partner Payouts Page">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('partner.payouts.title', 'Управување со исплати') }}</h1>
      <p class="text-gray-600">{{ $t('partner.payouts.subtitle', 'Поврзете се со Stripe за автоматски исплати на провизии') }}</p>
    </div>

    <!-- Stripe Connect Onboarding Section -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('partner.payouts.settings_title', 'Поставки за исплата') }}</h3>
      </div>

      <div class="p-6">
        <!-- Loading State with Skeleton -->
        <div v-if="stripeConnectLoading" class="animate-pulse" aria-busy="true" aria-label="Loading Stripe connect status">
          <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-gray-200 rounded-lg"></div>
            <div class="flex-grow">
              <div class="h-6 bg-gray-200 rounded w-1/3 mb-2"></div>
              <div class="h-4 bg-gray-200 rounded w-2/3 mb-4"></div>
              <div class="h-10 bg-gray-200 rounded w-40"></div>
            </div>
          </div>
        </div>

        <!-- Not Connected State -->
        <div v-else-if="!stripeConnect?.connected" class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-indigo-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.594-7.305z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('partner.payouts.connect_stripe', 'Поврзете се со Stripe') }}</h3>
              <p class="text-gray-600 mb-4">
                {{ $t('partner.payouts.connect_description', 'Поврзете ја вашата банкарска сметка преку Stripe за автоматски месечни исплати на провизии во EUR.') }}
              </p>
              <ul class="text-sm text-gray-600 space-y-2 mb-6">
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  {{ $t('partner.payouts.feature_auto_payout', 'Автоматски исплати на 5-ти секој месец') }}
                </li>
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  {{ $t('partner.payouts.feature_eur_payout', 'Исплата во EUR на вашата македонска сметка') }}
                </li>
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  {{ $t('partner.payouts.feature_dashboard', 'Целосен преглед на исплати во Stripe Dashboard') }}
                </li>
              </ul>
              <!-- Error Message -->
              <div v-if="stripeConnect?.error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg" role="alert">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-red-800">{{ $t('partner.payouts.connection_error', 'Грешка при поврзување') }}</p>
                    <p class="text-sm text-red-600 mt-1">{{ stripeConnect?.error }}</p>
                  </div>
                </div>
              </div>

              <button
                @click="connectStripe"
                :disabled="connectingStripe"
                :aria-busy="connectingStripe"
                aria-label="Connect to Stripe"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="connectingStripe" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ connectingStripe ? $t('partner.payouts.creating_account', 'Се креира сметка...') : (stripeConnect?.error ? $t('partner.payouts.retry', 'Обиди се повторно') : $t('partner.payouts.connect_button', 'Поврзи се со Stripe')) }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pending Onboarding State -->
        <div v-else-if="stripeConnect?.status === 'pending'" class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('partner.payouts.complete_registration', 'Завршете ја регистрацијата') }}</h3>
              <p class="text-gray-600 mb-4">
                {{ $t('partner.payouts.pending_description', 'Вашата Stripe сметка е креирана, но треба да ги пополните потребните информации за да примате исплати.') }}
              </p>

              <!-- Requirements List -->
              <div v-if="stripeConnect?.requirements?.currentlyDue?.length > 0" class="mb-4 p-4 bg-white rounded-lg border border-yellow-200">
                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $t('partner.payouts.required_info', 'Потребни информации:') }}</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                  <li v-for="req in stripeConnect?.requirements?.currentlyDue" :key="req" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    {{ formatRequirement(req) }}
                  </li>
                </ul>
              </div>

              <button
                @click="continueOnboarding"
                :disabled="onboardingLoading"
                :aria-busy="onboardingLoading"
                aria-label="Continue with registration"
                class="inline-flex items-center px-6 py-3 bg-yellow-600 text-white font-semibold rounded-lg hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="onboardingLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ onboardingLoading ? $t('partner.payouts.loading', 'Се вчитува...') : $t('partner.payouts.continue_registration', 'Продолжи со регистрација') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Restricted State -->
        <div v-else-if="stripeConnect?.status === 'restricted'" class="bg-gradient-to-r from-red-50 to-orange-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('partner.payouts.action_required', 'Потребна акција') }}</h3>
              <p class="text-gray-600 mb-4">
                {{ $t('partner.payouts.restricted_description', 'Вашата сметка има ограничувања. Ве молиме ажурирајте ги потребните информации за да продолжите да примате исплати.') }}
              </p>

              <div v-if="stripeConnect?.requirements?.pastDue?.length > 0" class="mb-4 p-4 bg-white rounded-lg border border-red-200">
                <h4 class="text-sm font-medium text-red-700 mb-2">{{ $t('partner.payouts.past_due_requirements', 'Задоцнети барања:') }}</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                  <li v-for="req in stripeConnect?.requirements?.pastDue" :key="req" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ formatRequirement(req) }}
                  </li>
                </ul>
              </div>

              <button
                @click="continueOnboarding"
                :disabled="onboardingLoading"
                :aria-busy="onboardingLoading"
                aria-label="Update information"
                class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="onboardingLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ onboardingLoading ? $t('partner.payouts.loading', 'Се вчитува...') : $t('partner.payouts.update_info', 'Ажурирај информации') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Disabled State -->
        <div v-else-if="stripeConnect?.status === 'disabled'" class="bg-gradient-to-r from-red-50 to-orange-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('partner.payouts.account_disabled', 'Сметката е деактивирана') }}</h3>
              <p class="text-gray-600 mb-4">
                {{ $t('partner.payouts.disabled_description', 'Вашата Stripe сметка е деактивирана. Треба да ги завршите потребните чекори за да ја активирате.') }}
              </p>

              <button
                @click="continueOnboarding"
                :disabled="onboardingLoading"
                :aria-busy="onboardingLoading"
                aria-label="Activate account"
                class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="onboardingLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ onboardingLoading ? $t('partner.payouts.loading', 'Се вчитува...') : $t('partner.payouts.activate_account', 'Активирај сметка') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Active State -->
        <div v-else-if="stripeConnect?.status === 'active'" class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-green-600" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $t('partner.payouts.stripe_connected', 'Stripe е поврзан') }}</h3>
              <p class="text-gray-600 mb-4">
                {{ $t('partner.payouts.active_description', 'Вашата сметка е целосно поставена. Исплатите ќе се процесираат автоматски на 5-ти секој месец.') }}
              </p>

              <div class="flex flex-wrap gap-4 mb-4">
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <span class="text-gray-700">{{ $t('partner.payouts.payouts_enabled', 'Исплати овозможени') }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <span class="text-gray-700">{{ $t('partner.payouts.currency_label', 'Валута:') }} {{ currencyCode }}</span>
                </div>
              </div>

              <button
                @click="openStripeDashboard"
                :disabled="dashboardLoading"
                :aria-busy="dashboardLoading"
                aria-label="Open Stripe Dashboard"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="dashboardLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                {{ dashboardLoading ? $t('partner.payouts.loading', 'Се вчитува...') : $t('partner.payouts.open_dashboard', 'Отвори Stripe Dashboard') }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Payout Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <!-- Skeleton loading for summary cards -->
      <template v-if="loading">
        <div v-for="i in 4" :key="i" class="bg-white rounded-lg shadow p-6 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-2/3 mb-2"></div>
          <div class="h-8 bg-gray-200 rounded w-1/2 mb-1"></div>
          <div class="h-3 bg-gray-200 rounded w-1/3"></div>
        </div>
      </template>

      <template v-else>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.payouts.total_paid', 'Вкупно исплатено') }}</h3>
          <div class="text-3xl font-bold text-green-600">{{ formatCurrency(payoutSummary?.totalPaid) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ $t('partner.payouts.from_beginning', 'Од почеток') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.payouts.pending', 'Чека исплата') }}</h3>
          <div class="text-3xl font-bold text-yellow-600">{{ formatCurrency(payoutSummary?.pending) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ $t('partner.payouts.processing', 'Се процесира') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.payouts.this_month', 'Овој месец') }}</h3>
          <div class="text-3xl font-bold text-blue-600">{{ formatCurrency(payoutSummary?.thisMonth) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ $t('partner.payouts.earned', 'Заработено') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">{{ $t('partner.payouts.next_payout', 'Следна исплата') }}</h3>
          <div class="text-3xl font-bold text-purple-600">{{ formatCurrency(payoutSummary?.nextPayout) }}</div>
          <p class="text-xs text-gray-500 mt-1">{{ payoutSummary?.nextPayoutDate ? formatDate(payoutSummary?.nextPayoutDate) : $t('partner.payouts.next_month_5th', '5-ти наредниот месец') }}</p>
        </div>
      </template>
    </div>

    <!-- Payout History Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">{{ $t('partner.payouts.history', 'Историја на исплати') }}</h3>
        <select
          v-model="statusFilter"
          @change="debouncedFetchPayouts"
          aria-label="Filter by status"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('partner.payouts.all_statuses', 'Сите статуси') }}</option>
          <option value="pending">{{ $t('partner.payouts.status_pending', 'Чека') }}</option>
          <option value="processing">{{ $t('partner.payouts.status_processing', 'Се процесира') }}</option>
          <option value="completed">{{ $t('partner.payouts.status_completed', 'Завршено') }}</option>
          <option value="failed">{{ $t('partner.payouts.status_failed', 'Неуспешно') }}</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <!-- Table Skeleton Loading -->
        <table v-if="loading" class="min-w-full divide-y divide-gray-200" aria-busy="true" aria-label="Loading payouts">
          <thead class="bg-gray-50">
            <tr>
              <th v-for="i in 7" :key="i" class="px-6 py-3">
                <div class="h-4 bg-gray-200 rounded w-20 animate-pulse"></div>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="i in 5" :key="i">
              <td v-for="j in 7" :key="j" class="px-6 py-4">
                <div class="h-4 bg-gray-200 rounded animate-pulse" :class="j === 3 ? 'w-24' : 'w-16'"></div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Actual Table -->
        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.id', 'ID') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.date', 'Датум') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.amount', 'Износ') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.stripe_transfer', 'Stripe Transfer') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.status', 'Статус') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.reference', 'Референца') }}
              </th>
              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                {{ $t('partner.payouts.table.actions', 'Акции') }}
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payout in payouts" :key="payout?.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                #{{ payout?.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatDate(payout?.payout_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                {{ formatCurrency(payout?.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">
                {{ payout?.stripe_transfer_id || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(payout?.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(payout?.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">
                {{ payout?.payment_reference || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  v-if="payout?.status === 'completed'"
                  @click="downloadReceipt(payout?.id)"
                  :disabled="downloadingReceipt === payout?.id"
                  :aria-busy="downloadingReceipt === payout?.id"
                  aria-label="Download receipt"
                  class="text-blue-600 hover:text-blue-800 font-medium disabled:opacity-50 inline-flex items-center"
                >
                  <svg v-if="downloadingReceipt === payout?.id" class="animate-spin mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ $t('partner.payouts.download_receipt', 'Преземи потврда') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && payouts.length === 0" class="px-6 py-12 text-center" role="status">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.payouts.no_payouts', 'Нема исплати') }}</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ statusFilter ? $t('partner.payouts.no_payouts_with_status', 'Нема исплати со овој статус') : $t('partner.payouts.no_payouts_description', 'Исплатите ќе се појават тука откако ќе бидат процесирани') }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          {{ $t('partner.payouts.pagination_info', 'Прикажани {from} до {to} од {total} резултати', { from: pagination.from || 0, to: pagination.to || 0, total: pagination.total || 0 }) }}
        </div>
        <div class="flex gap-2">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1 || paginationLoading"
            :aria-busy="paginationLoading"
            aria-label="Previous page"
            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 inline-flex items-center"
          >
            <svg v-if="paginationLoading && paginationDirection === 'prev'" class="animate-spin mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ $t('partner.payouts.previous', 'Претходна') }}
          </button>
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage || paginationLoading"
            :aria-busy="paginationLoading"
            aria-label="Next page"
            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 inline-flex items-center"
          >
            {{ $t('partner.payouts.next', 'Следна') }}
            <svg v-if="paginationLoading && paginationDirection === 'next'" class="animate-spin ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { usePartnerStore } from '@/scripts/partner/stores/partner'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { storeToRefs } from 'pinia'
import { debounce } from 'lodash'

export default {
  name: 'PartnerPayouts',

  setup() {
    const partnerStore = usePartnerStore()
    const notificationStore = useNotificationStore()
    const dialogStore = useDialogStore()
    const globalStore = useGlobalStore()
    const { stripeConnect, stripeConnectLoading } = storeToRefs(partnerStore)

    // Get currency from company settings or fallback to EUR
    const currency = globalStore.companySettings?.currency || { code: 'EUR', symbol: 'EUR' }

    return {
      partnerStore,
      notificationStore,
      dialogStore,
      stripeConnect,
      stripeConnectLoading,
      currency
    }
  },

  data() {
    return {
      payoutSummary: {
        totalPaid: 0,
        pending: 0,
        thisMonth: 0,
        nextPayout: 0,
        nextPayoutDate: null
      },
      payouts: [],
      statusFilter: '',
      pagination: {
        currentPage: 1,
        perPage: 20,
        total: 0,
        lastPage: 1,
        from: 0,
        to: 0
      },
      loading: false,
      paginationLoading: false,
      paginationDirection: null,
      connectingStripe: false,
      onboardingLoading: false,
      dashboardLoading: false,
      downloadingReceipt: null
    }
  },

  computed: {
    currencyCode() {
      return this.currency?.code || 'EUR'
    }
  },

  created() {
    // Create debounced version of fetchPayouts
    this.debouncedFetchPayouts = debounce(this.fetchPayouts, 300)
  },

  mounted() {
    this.loadStripeStatus()
    this.fetchPayouts()
    this.handleStripeRedirect()
  },

  methods: {
    async fetchPayouts() {
      this.loading = true
      try {
        const params = {
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
          status: this.statusFilter || undefined
        }

        const response = await axios.get('/partner/payouts', { params })
        this.payouts = response.data?.data || []
        this.payoutSummary = response.data?.summary || this.payoutSummary
        this.pagination = {
          currentPage: response.data?.current_page || 1,
          perPage: response.data?.per_page || 20,
          total: response.data?.total || 0,
          lastPage: response.data?.last_page || 1,
          from: response.data?.from || 0,
          to: response.data?.to || 0
        }
      } catch (error) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.$t('partner.payouts.fetch_error', 'Не можеше да се вчитаат исплатите. Обидете се повторно.')
        })
      } finally {
        this.loading = false
        this.paginationLoading = false
        this.paginationDirection = null
      }
    },

    goToPage(page) {
      if (page < 1 || page > this.pagination.lastPage || this.paginationLoading) return
      this.paginationDirection = page > this.pagination.currentPage ? 'next' : 'prev'
      this.paginationLoading = true
      this.pagination.currentPage = page
      this.fetchPayouts()
    },

    async downloadReceipt(payoutId) {
      if (!payoutId) return

      // Show confirmation dialog
      const confirmed = await this.dialogStore.openDialog({
        title: this.$t('partner.payouts.confirm_download_title', 'Преземање на потврда'),
        message: this.$t('partner.payouts.confirm_download_message', 'Дали сакате да ја преземете потврдата за исплата?'),
        yesLabel: this.$t('general.yes', 'Да'),
        noLabel: this.$t('general.no', 'Не'),
        variant: 'primary'
      })

      if (!confirmed) return

      this.downloadingReceipt = payoutId
      try {
        const response = await axios.get(`/partner/payouts/${payoutId}/receipt`, {
          responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `payout-receipt-${payoutId}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        this.notificationStore.showNotification({
          type: 'success',
          message: this.$t('partner.payouts.download_success', 'Потврдата е успешно преземена.')
        })
      } catch (error) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.$t('partner.payouts.download_error', 'Неуспешно преземање. Обидете се повторно.')
        })
      } finally {
        this.downloadingReceipt = null
      }
    },

    formatCurrency(amount) {
      // Database stores amounts as decimal (e.g., 100.00), not cents
      return new Intl.NumberFormat('mk-MK', {
        style: 'currency',
        currency: this.currencyCode
      }).format(amount || 0)
    },

    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('mk-MK', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    formatStatus(status) {
      const statuses = {
        pending: this.$t('partner.payouts.status_pending', 'Чека'),
        processing: this.$t('partner.payouts.status_processing', 'Се процесира'),
        completed: this.$t('partner.payouts.status_completed', 'Завршено'),
        failed: this.$t('partner.payouts.status_failed', 'Неуспешно'),
        cancelled: this.$t('partner.payouts.status_cancelled', 'Откажано')
      }
      return statuses[status] || status || '-'
    },

    getStatusBadgeClass(status) {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        processing: 'bg-blue-100 text-blue-800',
        completed: 'bg-green-100 text-green-800',
        failed: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    },

    // Stripe Connect methods
    async loadStripeStatus() {
      await this.partnerStore.loadStripeConnectStatus()
    },

    async connectStripe() {
      this.connectingStripe = true
      if (this.stripeConnect) {
        this.stripeConnect.error = null // Clear previous error
      }
      try {
        const result = await this.partnerStore.createStripeAccount()

        if (result?.success) {
          // Account created, now get onboarding link
          await this.continueOnboarding()
        } else {
          // Show error in UI and notification
          const errorMsg = result?.error || this.$t('partner.payouts.create_account_error', 'Грешка при креирање на сметка')
          if (this.stripeConnect) {
            this.stripeConnect.error = errorMsg
          }
          this.notificationStore.showNotification({
            type: 'error',
            message: errorMsg
          })
        }
      } catch (error) {
        const errorMsg = error?.response?.data?.error || this.$t('partner.payouts.create_account_error', 'Грешка при креирање на сметка')
        if (this.stripeConnect) {
          this.stripeConnect.error = errorMsg
        }
        this.notificationStore.showNotification({
          type: 'error',
          message: errorMsg
        })
      } finally {
        this.connectingStripe = false
      }
    },

    async continueOnboarding() {
      this.onboardingLoading = true
      if (this.stripeConnect) {
        this.stripeConnect.error = null // Clear previous error
      }
      try {
        const result = await this.partnerStore.getOnboardingLink()

        if (result?.success && result?.url) {
          // Redirect to Stripe-hosted onboarding
          window.location.href = result.url
        } else {
          const errorMsg = result?.error || this.$t('partner.payouts.create_link_error', 'Грешка при креирање на линк')
          if (this.stripeConnect) {
            this.stripeConnect.error = errorMsg
          }
          this.notificationStore.showNotification({
            type: 'error',
            message: errorMsg
          })
        }
      } catch (error) {
        const errorMsg = error?.response?.data?.error || this.$t('partner.payouts.create_link_error', 'Грешка при креирање на линк')
        if (this.stripeConnect) {
          this.stripeConnect.error = errorMsg
        }
        this.notificationStore.showNotification({
          type: 'error',
          message: errorMsg
        })
      } finally {
        this.onboardingLoading = false
      }
    },

    async openStripeDashboard() {
      this.dashboardLoading = true
      try {
        const result = await this.partnerStore.getDashboardLink()

        if (result?.success && result?.url) {
          // Open Stripe Express Dashboard in new tab
          window.open(result.url, '_blank')
        } else {
          const errorMsg = result?.error || this.$t('partner.payouts.create_link_error', 'Грешка при креирање на линк')
          this.notificationStore.showNotification({
            type: 'error',
            message: errorMsg
          })
        }
      } catch (error) {
        this.notificationStore.showNotification({
          type: 'error',
          message: this.$t('partner.payouts.create_link_error', 'Грешка при креирање на линк')
        })
      } finally {
        this.dashboardLoading = false
      }
    },

    handleStripeRedirect() {
      // Check URL params for Stripe redirect
      const urlParams = new URLSearchParams(window.location.search)

      if (urlParams.get('stripe_success') === 'true') {
        // Refresh status after successful onboarding
        this.loadStripeStatus()
        // Show success notification
        this.notificationStore.showNotification({
          type: 'success',
          message: this.$t('partner.payouts.stripe_success', 'Stripe е успешно поврзан!')
        })
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname)
      } else if (urlParams.get('stripe_refresh') === 'true') {
        // Link expired, user needs to get a new one
        this.loadStripeStatus()
        this.notificationStore.showNotification({
          type: 'warning',
          message: this.$t('partner.payouts.stripe_link_expired', 'Линкот истече. Обидете се повторно.')
        })
        window.history.replaceState({}, document.title, window.location.pathname)
      }
    },

    formatRequirement(requirement) {
      // Map Stripe requirement codes to Macedonian translations
      const requirementMap = {
        'individual.verification.document': this.$t('partner.payouts.req_id_document', 'Лична карта или пасош'),
        'individual.verification.additional_document': this.$t('partner.payouts.req_additional_document', 'Дополнителен документ за идентификација'),
        'individual.first_name': this.$t('partner.payouts.req_first_name', 'Име'),
        'individual.last_name': this.$t('partner.payouts.req_last_name', 'Презиме'),
        'individual.dob.day': this.$t('partner.payouts.req_dob', 'Датум на раѓање'),
        'individual.dob.month': this.$t('partner.payouts.req_dob', 'Датум на раѓање'),
        'individual.dob.year': this.$t('partner.payouts.req_dob', 'Датум на раѓање'),
        'individual.address.line1': this.$t('partner.payouts.req_address', 'Адреса'),
        'individual.address.city': this.$t('partner.payouts.req_city', 'Град'),
        'individual.address.postal_code': this.$t('partner.payouts.req_postal_code', 'Поштенски број'),
        'individual.phone': this.$t('partner.payouts.req_phone', 'Телефонски број'),
        'individual.email': this.$t('partner.payouts.req_email', 'Е-пошта'),
        'individual.id_number': this.$t('partner.payouts.req_embg', 'ЕМБГ'),
        'external_account': this.$t('partner.payouts.req_bank_account', 'Банкарска сметка'),
        'tos_acceptance.date': this.$t('partner.payouts.req_tos', 'Прифаќање на услови'),
        'tos_acceptance.ip': this.$t('partner.payouts.req_tos', 'Прифаќање на услови'),
        'business_profile.url': this.$t('partner.payouts.req_website', 'Веб страница'),
        'business_profile.mcc': this.$t('partner.payouts.req_business_type', 'Тип на бизнис')
      }
      return requirementMap[requirement] || requirement
    }
  }
}
// CLAUDE-CHECKPOINT
</script>

<style scoped>
.payouts-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
