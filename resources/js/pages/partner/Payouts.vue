<template>
  <div class="payouts-page">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Управување со исплати</h1>
      <p class="text-gray-600">Поврзете се со Stripe за автоматски исплати на провизии</p>
    </div>

    <!-- Stripe Connect Onboarding Section -->
    <div class="bg-white rounded-lg shadow mb-8">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Поставки за исплата</h3>
      </div>

      <div class="p-6">
        <!-- Loading State -->
        <div v-if="stripeConnectLoading" class="flex items-center justify-center py-8">
          <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="ml-3 text-gray-600">Се вчитува...</span>
        </div>

        <!-- Not Connected State -->
        <div v-else-if="!stripeConnect.connected" class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-indigo-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.975 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.545-2.354 1.545-1.875 0-4.965-.921-6.99-2.109l-.9 5.555C5.175 22.99 8.385 24 11.714 24c2.641 0 4.843-.624 6.328-1.813 1.664-1.305 2.525-3.236 2.525-5.732 0-4.128-2.524-5.851-6.594-7.305z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Поврзете се со Stripe</h3>
              <p class="text-gray-600 mb-4">
                Поврзете ја вашата банкарска сметка преку Stripe за автоматски месечни исплати на провизии во EUR.
              </p>
              <ul class="text-sm text-gray-600 space-y-2 mb-6">
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  Автоматски исплати на 5-ти секој месец
                </li>
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  Исплата во EUR на вашата македонска сметка
                </li>
                <li class="flex items-center gap-2">
                  <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                  </svg>
                  Целосен преглед на исплати во Stripe Dashboard
                </li>
              </ul>
              <!-- Error Message -->
              <div v-if="stripeConnect.error" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-red-800">Грешка при поврзување</p>
                    <p class="text-sm text-red-600 mt-1">{{ stripeConnect.error }}</p>
                  </div>
                </div>
              </div>

              <button
                @click="connectStripe"
                :disabled="connectingStripe"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="connectingStripe" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ connectingStripe ? 'Се креира сметка...' : (stripeConnect.error ? 'Обиди се повторно' : 'Поврзи се со Stripe') }}
              </button>
            </div>
          </div>
        </div>

        <!-- Pending Onboarding State -->
        <div v-else-if="stripeConnect.status === 'pending'" class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Завршете ја регистрацијата</h3>
              <p class="text-gray-600 mb-4">
                Вашата Stripe сметка е креирана, но треба да ги пополните потребните информации за да примате исплати.
              </p>

              <!-- Requirements List -->
              <div v-if="stripeConnect.requirements.currentlyDue.length > 0" class="mb-4 p-4 bg-white rounded-lg border border-yellow-200">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Потребни информации:</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                  <li v-for="req in stripeConnect.requirements.currentlyDue" :key="req" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                    {{ formatRequirement(req) }}
                  </li>
                </ul>
              </div>

              <button
                @click="continueOnboarding"
                :disabled="onboardingLoading"
                class="inline-flex items-center px-6 py-3 bg-yellow-600 text-white font-semibold rounded-lg hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="onboardingLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ onboardingLoading ? 'Се вчитува...' : 'Продолжи со регистрација' }}
              </button>
            </div>
          </div>
        </div>

        <!-- Restricted State -->
        <div v-else-if="stripeConnect.status === 'restricted'" class="bg-gradient-to-r from-red-50 to-orange-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Потребна акција</h3>
              <p class="text-gray-600 mb-4">
                Вашата сметка има ограничувања. Ве молиме ажурирајте ги потребните информации за да продолжите да примате исплати.
              </p>

              <div v-if="stripeConnect.requirements.pastDue.length > 0" class="mb-4 p-4 bg-white rounded-lg border border-red-200">
                <h4 class="text-sm font-medium text-red-700 mb-2">Задоцнети барања:</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                  <li v-for="req in stripeConnect.requirements.pastDue" :key="req" class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ formatRequirement(req) }}
                  </li>
                </ul>
              </div>

              <button
                @click="continueOnboarding"
                :disabled="onboardingLoading"
                class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                Ажурирај информации
              </button>
            </div>
          </div>
        </div>

        <!-- Active State -->
        <div v-else-if="stripeConnect.status === 'active'" class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-6">
          <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
              <svg class="w-12 h-12 text-green-600" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
              </svg>
            </div>
            <div class="flex-grow">
              <h3 class="text-lg font-semibold text-gray-900 mb-2">Stripe е поврзан</h3>
              <p class="text-gray-600 mb-4">
                Вашата сметка е целосно поставена. Исплатите ќе се процесираат автоматски на 5-ти секој месец.
              </p>

              <div class="flex flex-wrap gap-4 mb-4">
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <span class="text-gray-700">Исплати овозможени</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                  <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <span class="text-gray-700">Валута: EUR</span>
                </div>
              </div>

              <button
                @click="openStripeDashboard"
                :disabled="dashboardLoading"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
              >
                <svg v-if="dashboardLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                {{ dashboardLoading ? 'Се вчитува...' : 'Отвори Stripe Dashboard' }}
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Bank Account Settings -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Банкарски детали за исплата</h3>
        <button
          v-if="!editingBankDetails"
          @click="editBankDetails"
          class="px-4 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
        >
          Измени
        </button>
      </div>

      <div v-if="!editingBankDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Име на сопственик</label>
          <p class="text-gray-900">{{ bankDetails.account_holder || 'Не е поставено' }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Име на банка</label>
          <p class="text-gray-900">{{ bankDetails.bank_name || 'Не е поставено' }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">IBAN</label>
          <p class="text-gray-900 font-mono">{{ bankDetails.account_number || 'Не е поставено' }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">SWIFT/BIC код</label>
          <p class="text-gray-900 font-mono">{{ bankDetails.bank_code || 'Не е поставено' }}</p>
        </div>
      </div>

      <form v-else @submit.prevent="saveBankDetails" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Име на сопственик *</label>
            <input
              v-model="bankDetailsForm.account_holder"
              type="text"
              required
              placeholder="Име Презиме или Име на фирма"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Име на банка *</label>
            <input
              v-model="bankDetailsForm.bank_name"
              type="text"
              required
              placeholder="пр. Стопанска банка"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">IBAN *</label>
            <input
              v-model="bankDetailsForm.account_number"
              type="text"
              required
              placeholder="MK07..."
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
            />
            <p class="text-xs text-gray-500 mt-1">Македонски IBAN започнува со MK</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">SWIFT/BIC код</label>
            <input
              v-model="bankDetailsForm.bank_code"
              type="text"
              placeholder="пр. STOBMK2X"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"
            />
          </div>
        </div>

        <div class="flex gap-3">
          <button
            type="submit"
            :disabled="savingBankDetails"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            {{ savingBankDetails ? 'Се зачувува...' : 'Зачувај' }}
          </button>
          <button
            type="button"
            @click="cancelEditBankDetails"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
          >
            Откажи
          </button>
        </div>
      </form>
    </div>

    <!-- Payout Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Вкупно исплатено</h3>
        <div class="text-3xl font-bold text-green-600">{{ formatCurrency(payoutSummary.totalPaid) }}</div>
        <p class="text-xs text-gray-500 mt-1">Од почеток</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Чека исплата</h3>
        <div class="text-3xl font-bold text-yellow-600">{{ formatCurrency(payoutSummary.pending) }}</div>
        <p class="text-xs text-gray-500 mt-1">Се процесира</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Овој месец</h3>
        <div class="text-3xl font-bold text-blue-600">{{ formatCurrency(payoutSummary.thisMonth) }}</div>
        <p class="text-xs text-gray-500 mt-1">Заработено</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Следна исплата</h3>
        <div class="text-3xl font-bold text-purple-600">{{ formatCurrency(payoutSummary.nextPayout) }}</div>
        <p class="text-xs text-gray-500 mt-1">{{ payoutSummary.nextPayoutDate ? formatDate(payoutSummary.nextPayoutDate) : '5-ти наредниот месец' }}</p>
      </div>
    </div>

    <!-- Payout History Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Историја на исплати</h3>
        <select
          v-model="statusFilter"
          @change="fetchPayouts"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">Сите статуси</option>
          <option value="pending">Чека</option>
          <option value="processing">Се процесира</option>
          <option value="completed">Завршено</option>
          <option value="failed">Неуспешно</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Датум
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Износ
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Метод
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Статус
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Референца
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Акции
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="payout in payouts" :key="payout.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                #{{ payout.id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatDate(payout.payout_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                {{ formatCurrency(payout.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatPaymentMethod(payout.payment_method) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(payout.status)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ formatStatus(payout.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-mono">
                {{ payout.payment_reference || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  v-if="payout.status === 'completed'"
                  @click="downloadReceipt(payout.id)"
                  class="text-blue-600 hover:text-blue-800 font-medium"
                >
                  Преземи потврда
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && payouts.length === 0" class="px-6 py-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Нема исплати</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ statusFilter ? 'Нема исплати со овој статус' : 'Исплатите ќе се појават тука откако ќе бидат процесирани' }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Прикажани {{ pagination.from }} до {{ pagination.to }} од {{ pagination.total }} резултати
        </div>
        <div class="flex gap-2">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Претходна
          </button>
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Следна
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { usePartnerStore } from '@/scripts/partner/stores/partner'
import { storeToRefs } from 'pinia'

export default {
  name: 'PartnerPayouts',

  setup() {
    const partnerStore = usePartnerStore()
    const { stripeConnect, stripeConnectLoading } = storeToRefs(partnerStore)

    return {
      partnerStore,
      stripeConnect,
      stripeConnectLoading
    }
  },

  data() {
    return {
      bankDetails: {
        account_holder: '',
        bank_name: '',
        account_number: '',
        bank_code: ''
      },
      bankDetailsForm: {
        account_holder: '',
        bank_name: '',
        account_number: '',
        bank_code: ''
      },
      editingBankDetails: false,
      savingBankDetails: false,
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
      connectingStripe: false,
      onboardingLoading: false,
      dashboardLoading: false
    }
  },

  mounted() {
    this.loadStripeStatus()
    this.fetchBankDetails()
    this.fetchPayouts()
    this.handleStripeRedirect()
  },

  methods: {
    editBankDetails() {
      this.bankDetailsForm = { ...this.bankDetails }
      this.editingBankDetails = true
    },

    async fetchBankDetails() {
      try {
        const response = await axios.get('/partner/bank-details')
        this.bankDetails = response.data
      } catch (error) {
        console.error('Failed to fetch bank details:', error)
      }
    },

    async saveBankDetails() {
      this.savingBankDetails = true
      try {
        const response = await axios.post('/partner/bank-details', this.bankDetailsForm)
        this.bankDetails = response.data
        this.editingBankDetails = false
      } catch (error) {
        console.error('Failed to save bank details:', error)
        alert('Неуспешно зачувување. Обидете се повторно.')
      } finally {
        this.savingBankDetails = false
      }
    },

    cancelEditBankDetails() {
      this.bankDetailsForm = { ...this.bankDetails }
      this.editingBankDetails = false
    },

    async fetchPayouts() {
      this.loading = true
      try {
        const params = {
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
          status: this.statusFilter || undefined
        }

        const response = await axios.get('/partner/payouts', { params })
        this.payouts = response.data.data
        this.payoutSummary = response.data.summary || this.payoutSummary
        this.pagination = {
          currentPage: response.data.current_page,
          perPage: response.data.per_page,
          total: response.data.total,
          lastPage: response.data.last_page,
          from: response.data.from,
          to: response.data.to
        }
      } catch (error) {
        console.error('Failed to fetch payouts:', error)
      } finally {
        this.loading = false
      }
    },

    goToPage(page) {
      if (page < 1 || page > this.pagination.lastPage) return
      this.pagination.currentPage = page
      this.fetchPayouts()
    },

    async downloadReceipt(payoutId) {
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
      } catch (error) {
        console.error('Failed to download receipt:', error)
        alert('Неуспешно преземање. Обидете се повторно.')
      }
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('mk-MK', {
        style: 'currency',
        currency: 'EUR'
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

    formatPaymentMethod(method) {
      const methods = {
        wise: 'Wise',
        bank_transfer: 'Банкарски трансфер',
        manual: 'Рачно'
      }
      return methods[method] || method || '-'
    },

    formatStatus(status) {
      const statuses = {
        pending: 'Чека',
        processing: 'Се процесира',
        completed: 'Завршено',
        failed: 'Неуспешно',
        cancelled: 'Откажано'
      }
      return statuses[status] || status
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
      this.stripeConnect.error = null // Clear previous error
      try {
        const result = await this.partnerStore.createStripeAccount()

        if (result.success) {
          // Account created, now get onboarding link
          await this.continueOnboarding()
        } else {
          // Show error in UI instead of alert
          this.stripeConnect.error = result.error || 'Грешка при креирање на сметка'
        }
      } catch (error) {
        console.error('Error connecting Stripe:', error)
        this.stripeConnect.error = error.response?.data?.error || 'Грешка при креирање на сметка'
      } finally {
        this.connectingStripe = false
      }
    },

    async continueOnboarding() {
      this.onboardingLoading = true
      this.stripeConnect.error = null // Clear previous error
      try {
        const result = await this.partnerStore.getOnboardingLink()

        if (result.success && result.url) {
          // Redirect to Stripe-hosted onboarding
          window.location.href = result.url
        } else {
          this.stripeConnect.error = result.error || 'Грешка при креирање на линк'
        }
      } catch (error) {
        console.error('Error getting onboarding link:', error)
        this.stripeConnect.error = error.response?.data?.error || 'Грешка при креирање на линк'
      } finally {
        this.onboardingLoading = false
      }
    },

    async openStripeDashboard() {
      this.dashboardLoading = true
      try {
        const result = await this.partnerStore.getDashboardLink()

        if (result.success && result.url) {
          // Open Stripe Express Dashboard in new tab
          window.open(result.url, '_blank')
        } else {
          alert(result.error || 'Грешка при креирање на линк')
        }
      } catch (error) {
        console.error('Error getting dashboard link:', error)
        alert('Грешка при креирање на линк')
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
        // Clean URL
        window.history.replaceState({}, document.title, window.location.pathname)
      } else if (urlParams.get('stripe_refresh') === 'true') {
        // Link expired, user needs to get a new one
        this.loadStripeStatus()
        window.history.replaceState({}, document.title, window.location.pathname)
      }
    },

    formatRequirement(requirement) {
      // Map Stripe requirement codes to Macedonian translations
      const requirementMap = {
        'individual.verification.document': 'Лична карта или пасош',
        'individual.verification.additional_document': 'Дополнителен документ за идентификација',
        'individual.first_name': 'Име',
        'individual.last_name': 'Презиме',
        'individual.dob.day': 'Датум на раѓање',
        'individual.dob.month': 'Датум на раѓање',
        'individual.dob.year': 'Датум на раѓање',
        'individual.address.line1': 'Адреса',
        'individual.address.city': 'Град',
        'individual.address.postal_code': 'Поштенски број',
        'individual.phone': 'Телефонски број',
        'individual.email': 'Е-пошта',
        'individual.id_number': 'ЕМБГ',
        'external_account': 'Банкарска сметка',
        'tos_acceptance.date': 'Прифаќање на услови',
        'tos_acceptance.ip': 'Прифаќање на услови',
        'business_profile.url': 'Веб страница',
        'business_profile.mcc': 'Тип на бизнис'
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
