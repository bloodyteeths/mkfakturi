<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">
          Збирни извештаи
        </h1>
        <p class="mt-2 text-sm text-gray-600">
          Споредба и консолидација на извештаи за повеќе компании
        </p>
      </div>

      <!-- Controls Panel -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <!-- Company Selection -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-3">
            <label class="block text-sm font-medium text-gray-700">
              Изберете компании
            </label>
            <button
              type="button"
              class="text-sm text-blue-600 hover:text-blue-800 font-medium"
              @click="toggleSelectAll"
            >
              {{ allSelected ? 'Отселектирај ги сите' : 'Селектирај ги сите' }}
            </button>
          </div>

          <!-- Loading companies -->
          <div v-if="loadingCompanies" class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500" aria-label="Се вчитуваат компании..."></div>
            <span class="ml-3 text-sm text-gray-500">Се вчитуваат компании...</span>
          </div>

          <!-- Company checkboxes -->
          <div v-else-if="companies.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3">
            <label
              v-for="company in companies"
              :key="company.id"
              :class="[
                'flex items-center p-2 rounded-md cursor-pointer transition-colors',
                selectedCompanyIds.includes(company.id)
                  ? 'bg-blue-50 border border-blue-200'
                  : 'bg-white border border-gray-100 hover:bg-gray-50'
              ]"
            >
              <input
                type="checkbox"
                :value="company.id"
                v-model="selectedCompanyIds"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
              />
              <span class="ml-2 text-sm text-gray-900 truncate">{{ company.name }}</span>
            </label>
          </div>

          <!-- Empty state -->
          <div v-else class="text-center py-8 bg-gray-50 rounded-lg">
            <svg class="mx-auto h-12 w-12 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">Нема достапни компании</p>
          </div>

          <p v-if="selectedCompanyIds.length > 0" class="mt-2 text-xs text-gray-500">
            {{ selectedCompanyIds.length }} компании селектирани
          </p>
        </div>

        <!-- Date Range and Report Type -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div>
            <label for="from-date" class="block text-sm font-medium text-gray-700 mb-1">Датум од</label>
            <input
              id="from-date"
              v-model="fromDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              aria-label="Датум од"
            />
          </div>
          <div>
            <label for="to-date" class="block text-sm font-medium text-gray-700 mb-1">Датум до</label>
            <input
              id="to-date"
              v-model="toDate"
              type="date"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              aria-label="Датум до"
            />
          </div>
          <div>
            <label for="report-type" class="block text-sm font-medium text-gray-700 mb-1">Тип на извештај</label>
            <select
              id="report-type"
              v-model="reportType"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              aria-label="Тип на извештај"
            >
              <option value="trial_balance">Пробен биланс</option>
              <option value="profit_loss">Биланс на успех</option>
              <option value="balance_sheet">Биланс на состојба</option>
            </select>
          </div>
        </div>

        <!-- View Toggle and Actions -->
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center space-x-4">
            <!-- Consolidated Toggle -->
            <label class="flex items-center cursor-pointer">
              <input
                type="checkbox"
                v-model="showConsolidated"
                class="sr-only peer"
              />
              <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              <span class="ml-2 text-sm font-medium text-gray-700">Консолидиран преглед</span>
            </label>
          </div>

          <div class="flex items-center space-x-3">
            <!-- Export CSV -->
            <button
              @click="exportReport('csv')"
              :disabled="!canGenerate || isExporting"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Извези CSV
            </button>

            <!-- Export JSON -->
            <button
              @click="exportReport('json')"
              :disabled="!canGenerate || isExporting"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Извези JSON
            </button>

            <!-- Generate Button -->
            <button
              @click="generateReport"
              :disabled="!canGenerate || isLoading"
              class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <svg v-if="isLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ isLoading ? 'Генерирање...' : 'Генерирај' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Грешка</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
          </div>
        </div>
      </div>

      <!-- Empty State (no report generated yet) -->
      <div v-if="!isLoading && !reportData && !consolidatedData" class="bg-white shadow rounded-lg p-12 text-center">
        <svg class="mx-auto h-16 w-16 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Нема генериран извештај</h3>
        <p class="mt-2 text-sm text-gray-500">
          Изберете компании, период и тип на извештај, па кликнете "Генерирај"
        </p>
      </div>

      <!-- Consolidated View -->
      <div v-if="consolidatedData && showConsolidated" class="bg-white shadow rounded-lg overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
          <h3 class="text-lg font-semibold text-gray-900">
            Консолидиран извештај
          </h3>
          <p class="text-sm text-gray-600">
            Агрегирани податоци за {{ consolidatedData.company_count }} компании
          </p>
        </div>

        <div class="p-6">
          <!-- Summary Cards -->
          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
            <div class="bg-green-50 rounded-lg p-4">
              <p class="text-xs font-medium text-green-600 uppercase tracking-wider">Средства</p>
              <p class="mt-1 text-lg font-bold text-green-900">{{ formatAmount(consolidatedData.consolidated.total_assets) }}</p>
            </div>
            <div class="bg-red-50 rounded-lg p-4">
              <p class="text-xs font-medium text-red-600 uppercase tracking-wider">Обврски</p>
              <p class="mt-1 text-lg font-bold text-red-900">{{ formatAmount(consolidatedData.consolidated.total_liabilities) }}</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4">
              <p class="text-xs font-medium text-blue-600 uppercase tracking-wider">Капитал</p>
              <p class="mt-1 text-lg font-bold text-blue-900">{{ formatAmount(consolidatedData.consolidated.total_equity) }}</p>
            </div>
            <div class="bg-emerald-50 rounded-lg p-4">
              <p class="text-xs font-medium text-emerald-600 uppercase tracking-wider">Приход</p>
              <p class="mt-1 text-lg font-bold text-emerald-900">{{ formatAmount(consolidatedData.consolidated.total_revenue) }}</p>
            </div>
            <div class="bg-orange-50 rounded-lg p-4">
              <p class="text-xs font-medium text-orange-600 uppercase tracking-wider">Расходи</p>
              <p class="mt-1 text-lg font-bold text-orange-900">{{ formatAmount(consolidatedData.consolidated.total_expenses) }}</p>
            </div>
            <div :class="[
              'rounded-lg p-4',
              consolidatedData.consolidated.net_income >= 0 ? 'bg-green-50' : 'bg-red-50'
            ]">
              <p :class="[
                'text-xs font-medium uppercase tracking-wider',
                consolidatedData.consolidated.net_income >= 0 ? 'text-green-600' : 'text-red-600'
              ]">Нето приход</p>
              <p :class="[
                'mt-1 text-lg font-bold',
                consolidatedData.consolidated.net_income >= 0 ? 'text-green-900' : 'text-red-900'
              ]">{{ formatAmount(consolidatedData.consolidated.net_income) }}</p>
            </div>
          </div>

          <!-- Company breakdown table -->
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Компанија</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Средства</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Обврски</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Капитал</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Приход</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Расходи</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Нето приход</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="company in consolidatedData.companies" :key="company.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ company.name }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatAmount(company.assets) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatAmount(company.liabilities) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatAmount(company.equity) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatAmount(company.revenue) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">{{ formatAmount(company.expenses) }}</td>
                  <td :class="[
                    'px-4 py-3 text-sm text-right font-medium',
                    company.net_income >= 0 ? 'text-green-700' : 'text-red-700'
                  ]">{{ formatAmount(company.net_income) }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-50">
                <tr class="font-bold">
                  <td class="px-4 py-3 text-sm text-gray-900">Вкупно</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatAmount(consolidatedData.consolidated.total_assets) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatAmount(consolidatedData.consolidated.total_liabilities) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatAmount(consolidatedData.consolidated.total_equity) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatAmount(consolidatedData.consolidated.total_revenue) }}</td>
                  <td class="px-4 py-3 text-sm text-right text-gray-900">{{ formatAmount(consolidatedData.consolidated.total_expenses) }}</td>
                  <td :class="[
                    'px-4 py-3 text-sm text-right',
                    consolidatedData.consolidated.net_income >= 0 ? 'text-green-900' : 'text-red-900'
                  ]">{{ formatAmount(consolidatedData.consolidated.net_income) }}</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Multi-Company View (Individual Reports) -->
      <div v-if="reportData && !showConsolidated" class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ reportTypeLabel }} - По компанија
          </h3>
          <p class="text-sm text-gray-600">
            {{ reportData.companies.length }} компании
          </p>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Компанија</th>
                <template v-if="reportType === 'trial_balance'">
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Вкупно должи</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Вкупно побарува</th>
                  <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Балансирано</th>
                </template>
                <template v-else-if="reportType === 'balance_sheet'">
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Средства</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Обврски</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Капитал</th>
                </template>
                <template v-else-if="reportType === 'profit_loss'">
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Приход</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Расходи</th>
                  <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Нето приход</th>
                </template>
                <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="company in reportData.companies" :key="company.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ company.name }}</td>

                <!-- Trial Balance columns -->
                <template v-if="reportType === 'trial_balance'">
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.total_debits || 0) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.total_credits || 0) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-center">
                    <span v-if="hasError(company)" class="text-gray-400">-</span>
                    <span v-else-if="company.report_data?.is_balanced" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Да</span>
                    <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Не</span>
                  </td>
                </template>

                <!-- Balance Sheet columns -->
                <template v-else-if="reportType === 'balance_sheet'">
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.balance_sheet?.totals?.assets || 0) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.balance_sheet?.totals?.liabilities || 0) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.balance_sheet?.totals?.equity || 0) }}
                  </td>
                </template>

                <!-- Profit & Loss columns -->
                <template v-else-if="reportType === 'profit_loss'">
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.income_statement?.totals?.revenue || 0) }}
                  </td>
                  <td class="px-4 py-3 text-sm text-right text-gray-700">
                    {{ hasError(company) ? '-' : formatAmount(company.report_data?.income_statement?.totals?.expenses || 0) }}
                  </td>
                  <td :class="[
                    'px-4 py-3 text-sm text-right font-medium',
                    getNetIncome(company) >= 0 ? 'text-green-700' : 'text-red-700'
                  ]">
                    {{ hasError(company) ? '-' : formatAmount(getNetIncome(company)) }}
                  </td>
                </template>

                <!-- Status column -->
                <td class="px-4 py-3 text-sm text-center">
                  <span v-if="hasError(company)" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800" :title="company.report_data?.error || company.report_data?.message">
                    {{ company.report_data?.status === 'not_initialized' ? 'Не е иницијализирано' : 'Грешка' }}
                  </span>
                  <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    OK
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'

// Stores
const notificationStore = useNotificationStore()

// State
const companies = ref([])
const selectedCompanyIds = ref([])
const fromDate = ref('')
const toDate = ref('')
const reportType = ref('trial_balance')
const showConsolidated = ref(false)

const loadingCompanies = ref(true)
const isLoading = ref(false)
const isExporting = ref(false)
const error = ref(null)

const reportData = ref(null)
const consolidatedData = ref(null)

// Computed
const allSelected = computed(() => {
  return companies.value.length > 0 && selectedCompanyIds.value.length === companies.value.length
})

const canGenerate = computed(() => {
  return selectedCompanyIds.value.length > 0 && fromDate.value && toDate.value
})

const reportTypeLabel = computed(() => {
  const labels = {
    trial_balance: 'Пробен биланс',
    profit_loss: 'Биланс на успех',
    balance_sheet: 'Биланс на состојба',
  }
  return labels[reportType.value] || reportType.value
})

// Methods
const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedCompanyIds.value = []
  } else {
    selectedCompanyIds.value = companies.value.map(c => c.id)
  }
}

const loadCompanies = async () => {
  loadingCompanies.value = true
  try {
    const { data } = await window.axios.get('/partner/clients', {
      params: { per_page: 200, status: 'active' }
    })

    // Handle paginated response
    const clientList = data.data || data || []
    companies.value = clientList.map(c => ({
      id: c.id,
      name: c.name || 'Непознато',
    }))
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: 'Не можеше да се вчитаат компаниите.'
    })
  } finally {
    loadingCompanies.value = false
  }
}

const generateReport = async () => {
  if (!canGenerate.value) return

  isLoading.value = true
  error.value = null
  reportData.value = null
  consolidatedData.value = null

  try {
    if (showConsolidated.value) {
      const { data } = await window.axios.post('/partner/reports/consolidated', {
        company_ids: selectedCompanyIds.value,
        from_date: fromDate.value,
        to_date: toDate.value,
      })
      consolidatedData.value = data.data
    } else {
      const { data } = await window.axios.post('/partner/reports/multi-company', {
        company_ids: selectedCompanyIds.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        report_type: reportType.value,
      })
      reportData.value = data.data
    }
  } catch (err) {
    error.value = err?.response?.data?.message || 'Грешка при генерирање на извештајот.'
    notificationStore.showNotification({
      type: 'error',
      message: error.value
    })
  } finally {
    isLoading.value = false
  }
}

const exportReport = async (format) => {
  if (!canGenerate.value) return

  isExporting.value = true

  try {
    if (format === 'csv') {
      // CSV downloads as a file via blob
      const response = await window.axios.post('/partner/reports/export', {
        company_ids: selectedCompanyIds.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        report_type: reportType.value,
        format: 'csv',
      }, {
        responseType: 'blob',
      })

      // Trigger download
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `bulk_${reportType.value}_${fromDate.value}_${toDate.value}.csv`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      notificationStore.showNotification({
        type: 'success',
        message: 'CSV извозот е успешен.'
      })
    } else {
      // JSON download
      const { data } = await window.axios.post('/partner/reports/export', {
        company_ids: selectedCompanyIds.value,
        from_date: fromDate.value,
        to_date: toDate.value,
        report_type: reportType.value,
        format: 'json',
      })

      // Trigger JSON file download
      const jsonStr = JSON.stringify(data.data, null, 2)
      const blob = new Blob([jsonStr], { type: 'application/json' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `bulk_${reportType.value}_${fromDate.value}_${toDate.value}.json`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)

      notificationStore.showNotification({
        type: 'success',
        message: 'JSON извозот е успешен.'
      })
    }
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Грешка при извоз на извештајот.'
    })
  } finally {
    isExporting.value = false
  }
}

const hasError = (company) => {
  return !!company?.report_data?.error
}

const getNetIncome = (company) => {
  if (hasError(company)) return 0
  const revenue = company?.report_data?.income_statement?.totals?.revenue || 0
  const expenses = company?.report_data?.income_statement?.totals?.expenses || 0
  return revenue - expenses
}

const formatAmount = (amount) => {
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount || 0)
}

// Set default dates (current year)
const setDefaultDates = () => {
  const now = new Date()
  const firstDay = new Date(now.getFullYear(), 0, 1)
  fromDate.value = firstDay.toISOString().split('T')[0]
  toDate.value = now.toISOString().split('T')[0]
}

// Lifecycle
onMounted(() => {
  setDefaultDates()
  loadCompanies()
})
</script>

