<template>
  <div class="bg-white rounded-lg shadow-md p-6 min-h-[300px]">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="p-2 bg-blue-100 rounded-lg">
          <CreditCardIcon class="w-6 h-6 text-blue-600" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('banking.status_widget.title') }}</h3>
          <p class="text-sm text-gray-500">{{ $t('banking.status_widget.subtitle') }}</p>
        </div>
      </div>
      <button
        @click="refreshData"
        :disabled="loading"
        class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
        :title="$t('banking.status_widget.refresh')"
      >
        <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': loading }" />
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="space-y-4">
      <div class="animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/3 mb-2"></div>
        <div class="h-6 bg-gray-200 rounded w-1/2 mb-4"></div>
      </div>
      <div class="space-y-3">
        <div class="h-12 bg-gray-200 rounded animate-pulse"></div>
        <div class="h-12 bg-gray-200 rounded animate-pulse"></div>
        <div class="h-12 bg-gray-200 rounded animate-pulse"></div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-8">
      <ExclamationTriangleIcon class="w-12 h-12 text-red-400 mx-auto mb-4" />
      <h4 class="text-lg font-medium text-gray-900 mb-2">{{ $t('banking.status_widget.error_title') }}</h4>
      <p class="text-gray-500 mb-4">{{ error }}</p>
      <button
        @click="refreshData"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
      >
        {{ $t('banking.status_widget.retry') }}
      </button>
    </div>

    <!-- Banking Status Content -->
    <div v-else class="space-y-6">
      <!-- Overall Status Summary -->
      <div class="bg-gray-50 rounded-lg p-4">
        <div class="flex items-center justify-between">
          <div>
            <h4 class="font-medium text-gray-900">{{ $t('banking.status_widget.overall_status') }}</h4>
            <p class="text-sm text-gray-600 mt-1">
              {{ $t('banking.status_widget.accounts_connected', { count: connectedAccounts }) }}
            </p>
          </div>
          <div class="flex items-center space-x-2">
            <span
              :class="[
                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                overallStatus === 'healthy' ? 'bg-green-100 text-green-800' :
                overallStatus === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                'bg-red-100 text-red-800'
              ]"
            >
              <span
                :class="[
                  'w-1.5 h-1.5 rounded-full mr-1.5',
                  overallStatus === 'healthy' ? 'bg-green-400' :
                  overallStatus === 'warning' ? 'bg-yellow-400' :
                  'bg-red-400'
                ]"
              ></span>
              {{ $t(`banking.status_widget.status_${overallStatus}`) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Bank Connections -->
      <div>
        <h4 class="font-medium text-gray-900 mb-3">{{ $t('banking.status_widget.bank_connections') }}</h4>
        <div class="space-y-3">
          <div
            v-for="bank in bankConnections"
            :key="bank.code"
            class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
          >
            <div class="flex items-center space-x-3">
              <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                <BuildingLibraryIcon class="w-5 h-5 text-gray-600" />
              </div>
              <div>
                <h5 class="font-medium text-gray-900">{{ bank.name }}</h5>
                <p class="text-sm text-gray-500">
                  {{ $t('banking.status_widget.accounts', { count: bank.accountCount }) }}
                </p>
              </div>
            </div>
            <div class="flex items-center space-x-3">
              <div class="text-right">
                <p class="text-sm text-gray-600">{{ $t('banking.status_widget.last_sync') }}</p>
                <p class="text-sm font-medium text-gray-900">
                  {{ formatRelativeTime(bank.lastSync) }}
                </p>
              </div>
              <span
                :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                  bank.status === 'connected' ? 'bg-green-100 text-green-800' :
                  bank.status === 'warning' ? 'bg-yellow-100 text-yellow-800' :
                  'bg-red-100 text-red-800'
                ]"
              >
                {{ $t(`banking.status_widget.bank_status_${bank.status}`) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Sync Statistics -->
      <div class="grid grid-cols-2 gap-4">
        <div class="bg-blue-50 rounded-lg p-4">
          <div class="flex items-center space-x-2">
            <ArrowDownIcon class="w-5 h-5 text-blue-600" />
            <h5 class="font-medium text-gray-900">{{ $t('banking.status_widget.transactions_today') }}</h5>
          </div>
          <p class="text-2xl font-bold text-blue-600 mt-2">{{ syncStats.transactionsToday }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('banking.status_widget.total_amount') }}: 
            <span class="font-medium">{{ formatMoney(syncStats.totalAmountToday) }}</span>
          </p>
        </div>

        <div class="bg-green-50 rounded-lg p-4">
          <div class="flex items-center space-x-2">
            <CheckCircleIcon class="w-5 h-5 text-green-600" />
            <h5 class="font-medium text-gray-900">{{ $t('banking.status_widget.matched_payments') }}</h5>
          </div>
          <p class="text-2xl font-bold text-green-600 mt-2">{{ syncStats.matchedPayments }}</p>
          <p class="text-sm text-gray-600">
            {{ $t('banking.status_widget.match_rate') }}: 
            <span class="font-medium">{{ syncStats.matchRate }}%</span>
          </p>
        </div>
      </div>

      <!-- Last Sync Info -->
      <div class="border-t pt-4">
        <div class="flex items-center justify-between text-sm text-gray-600">
          <span>{{ $t('banking.status_widget.last_full_sync') }}: {{ formatDateTime(lastFullSync) }}</span>
          <span>{{ $t('banking.status_widget.next_sync') }}: {{ formatRelativeTime(nextSync) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  CreditCardIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
  BuildingLibraryIcon,
  ArrowDownIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline'

const { t } = useI18n()

// Reactive data
const loading = ref(true)
const error = ref(null)
const bankConnections = ref([])
const syncStats = ref({
  transactionsToday: 0,
  totalAmountToday: 0,
  matchedPayments: 0,
  matchRate: 0
})
const lastFullSync = ref(null)
const nextSync = ref(null)

// Computed properties
const connectedAccounts = computed(() => {
  return bankConnections.value.reduce((total, bank) => total + bank.accountCount, 0)
})

const overallStatus = computed(() => {
  const hasErrors = bankConnections.value.some(bank => bank.status === 'error')
  const hasWarnings = bankConnections.value.some(bank => bank.status === 'warning')
  
  if (hasErrors) return 'error'
  if (hasWarnings) return 'warning'
  return 'healthy'
})

// Methods
const loadBankingData = async () => {
  try {
    loading.value = true
    error.value = null

    // For now, using mock data since actual API endpoints would need to be implemented
    // In a real implementation, this would fetch from actual banking API endpoints
    if (import.meta.env.DEV) {
      console.info('Banking status using mock data - API endpoints not yet implemented')
    }
    await new Promise(resolve => setTimeout(resolve, 1000)) // Simulate API call

    bankConnections.value = [
      {
        code: 'stopanska',
        name: 'Stopanska Banka',
        accountCount: 2,
        status: 'connected',
        lastSync: new Date(Date.now() - 1000 * 60 * 15), // 15 minutes ago
        psd2Status: 'active'
      },
      {
        code: 'nlb',
        name: 'NLB Banka',
        accountCount: 1,
        status: 'connected',
        lastSync: new Date(Date.now() - 1000 * 60 * 30), // 30 minutes ago
        psd2Status: 'active'
      },
      {
        code: 'komercijalna',
        name: 'Komercijalna Banka',
        accountCount: 1,
        status: 'warning',
        lastSync: new Date(Date.now() - 1000 * 60 * 60 * 2), // 2 hours ago
        psd2Status: 'expired'
      }
    ]

    syncStats.value = {
      transactionsToday: 47,
      totalAmountToday: 125430.50,
      matchedPayments: 23,
      matchRate: 89
    }

    lastFullSync.value = new Date(Date.now() - 1000 * 60 * 60) // 1 hour ago
    nextSync.value = new Date(Date.now() + 1000 * 60 * 15) // 15 minutes from now

  } catch (err) {
    error.value = err.message || t('banking.status_widget.load_error')
  } finally {
    loading.value = false
  }
}

const refreshData = () => {
  loadBankingData()
}

const formatRelativeTime = (date) => {
  if (!date) return t('banking.status_widget.never')
  
  const now = new Date()
  const diffInMinutes = Math.floor((now - date) / (1000 * 60))
  
  if (diffInMinutes < 1) return t('banking.status_widget.just_now')
  if (diffInMinutes < 60) return t('banking.status_widget.minutes_ago', { count: diffInMinutes })
  
  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return t('banking.status_widget.hours_ago', { count: diffInHours })
  
  const diffInDays = Math.floor(diffInHours / 24)
  return t('banking.status_widget.days_ago', { count: diffInDays })
}

const formatDateTime = (date) => {
  if (!date) return t('banking.status_widget.never')
  try {
    return new Date(date).toLocaleString()
  } catch {
    return date
  }
}

const formatMoney = (amount) => {
  if (!amount || isNaN(amount)) return '0 MKD'
  return `${Number(amount).toLocaleString('mk-MK')} MKD`
}

// Lifecycle
onMounted(() => {
  loadBankingData()
})
</script>

// LLM-CHECKPOINT
