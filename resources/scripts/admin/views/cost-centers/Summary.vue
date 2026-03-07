<template>
  <BasePage>
    <BasePageHeader :title="t('summary')">
      <template #actions>
        <BaseButton
          v-if="summaryData && summaryData.centers && summaryData.centers.length > 0"
          variant="primary-outline"
          :loading="isExporting"
          @click="exportToCsv"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
          </template>
          CSV
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Sub-navigation tabs -->
    <div class="flex border-b border-gray-200 mb-6">
      <router-link
        v-for="tab in tabs"
        :key="tab.name"
        :to="tab.to"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
        :class="currentRouteName === tab.name
          ? 'border-primary-500 text-primary-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ tab.label }}
      </router-link>
    </div>

    <!-- Filters -->
    <div class="p-6 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInputGroup :label="$t('reports.accounting.from_date')" required>
          <BaseDatePicker
            v-model="filters.from_date"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('reports.accounting.to_date')" required>
          <BaseDatePicker
            v-model="filters.to_date"
          />
        </BaseInputGroup>

        <div class="flex items-end md:col-span-2">
          <BaseButton
            variant="primary"
            class="w-full md:w-auto"
            :loading="isLoading"
            @click="loadSummary"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('reports.update_report') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 6" :key="i" class="flex items-center space-x-4">
          <div class="h-4 bg-gray-200 rounded w-8"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Summary Content -->
    <template v-else-if="summaryData">
      <!-- Bar Chart -->
      <div v-if="summaryData.centers && summaryData.centers.length > 0" class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-sm font-medium text-gray-700 mb-4">{{ t('expenses') }}</h3>
        <div class="space-y-3">
          <div v-for="center in summaryData.centers" :key="center.cost_center_id" class="flex items-center">
            <div class="w-32 text-sm text-gray-700 truncate flex-shrink-0 flex items-center">
              <span
                class="inline-block h-3 w-3 rounded-full mr-2 flex-shrink-0"
                :style="{ backgroundColor: center.color }"
              ></span>
              {{ center.name }}
            </div>
            <div class="flex-1 mx-3">
              <div class="bg-gray-100 rounded-full h-5 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{
                    width: `${center.percentage}%`,
                    backgroundColor: center.color,
                    minWidth: center.percentage > 0 ? '4px' : '0',
                  }"
                ></div>
              </div>
            </div>
            <div class="w-24 text-sm text-right text-gray-900 font-medium flex-shrink-0">
              {{ formatMoney(center.total_debit) }}
            </div>
            <div class="w-16 text-xs text-right text-gray-500 flex-shrink-0 ml-2">
              {{ center.percentage }}%
            </div>
          </div>
        </div>
      </div>

      <!-- Data Table -->
      <div v-if="summaryData.centers && summaryData.centers.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                {{ t('title') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('income') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('expenses') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ t('net') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                % {{ $t('general.total') }}
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                {{ $t('general.actions') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="center in summaryData.centers" :key="center.cost_center_id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm">
                <div class="flex items-center">
                  <span
                    class="inline-block h-3 w-3 rounded-full mr-2 flex-shrink-0"
                    :style="{ backgroundColor: center.color }"
                  ></span>
                  <span class="text-gray-900 font-medium">{{ center.name }}</span>
                  <span v-if="center.code" class="ml-1 text-gray-400 font-mono text-xs">
                    ({{ center.code }})
                  </span>
                </div>
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right text-green-700">
                {{ formatMoney(center.total_credit) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right text-red-600">
                {{ formatMoney(center.total_debit) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right font-medium" :class="center.net >= 0 ? 'text-green-700' : 'text-red-600'">
                {{ formatMoney(center.net) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right text-gray-600">
                {{ center.percentage }}%
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                <button
                  class="text-primary-600 hover:text-primary-800 text-xs font-medium"
                  @click="viewTrialBalance(center)"
                >
                  {{ t('view_detail') }}
                </button>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-100">
            <!-- Assigned total -->
            <tr class="font-semibold border-t-2 border-gray-300">
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ $t('general.total') }} ({{ t('assigned') }})
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right text-green-700">
                {{ formatMoney(summaryData.grand_total?.total_credit) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right text-red-600">
                {{ formatMoney(summaryData.grand_total?.total_debit) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right" :class="(summaryData.grand_total?.net || 0) >= 0 ? 'text-green-700' : 'text-red-600'">
                {{ formatMoney(summaryData.grand_total?.net) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right text-gray-600">
                100%
              </td>
              <td></td>
            </tr>
            <!-- Unassigned -->
            <tr v-if="summaryData.unassigned && (summaryData.unassigned.total_debit > 0 || summaryData.unassigned.total_credit > 0)" class="text-gray-500">
              <td class="px-4 py-3 text-sm italic">
                {{ t('unassigned') }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                {{ formatMoney(summaryData.unassigned.total_credit) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                {{ formatMoney(summaryData.unassigned.total_debit) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 text-sm text-right">
                {{ formatMoney(summaryData.unassigned.net) }}
              </td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Empty state after search -->
      <div
        v-else-if="hasSearched"
        class="bg-white rounded-lg shadow p-12 text-center"
      >
        <BaseIcon name="ChartBarIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">
          {{ t('no_data') }}
        </h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ t('no_data_description') }}
        </p>
      </div>
    </template>

    <!-- Trial Balance Modal for a specific cost center -->
    <div v-if="showTrialBalanceModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black bg-opacity-30" @click="showTrialBalanceModal = false"></div>

        <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] flex flex-col">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 rounded-t-lg">
            <div class="flex items-center">
              <span
                class="inline-block h-4 w-4 rounded-full mr-3"
                :style="{ backgroundColor: selectedCenterForTB?.color || '#6366f1' }"
              ></span>
              <h3 class="text-lg font-medium text-gray-900">
                {{ selectedCenterForTB?.name }} - {{ $t('partner.accounting.trial_balance') }}
              </h3>
            </div>
            <button class="text-gray-400 hover:text-gray-600" @click="showTrialBalanceModal = false">
              <BaseIcon name="XMarkIcon" class="h-5 w-5" />
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-auto p-6">
            <div v-if="isTBLoading" class="space-y-3 animate-pulse">
              <div v-for="i in 6" :key="i" class="h-4 bg-gray-200 rounded"></div>
            </div>

            <table v-else-if="trialBalanceData?.accounts?.length > 0" class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ $t('settings.accounts.code') }}</th>
                  <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ $t('settings.accounts.name') }}</th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('reports.accounting.general_ledger.debit') }}</th>
                  <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('reports.accounting.general_ledger.credit') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="(account, idx) in trialBalanceData.accounts" :key="idx" class="hover:bg-gray-50">
                  <td class="px-3 py-2 text-sm font-mono text-gray-600">{{ account.code }}</td>
                  <td class="px-3 py-2 text-sm text-gray-900">{{ account.name }}</td>
                  <td class="px-3 py-2 text-sm text-right">{{ account.period_debit > 0 ? formatMoney(account.period_debit) : '' }}</td>
                  <td class="px-3 py-2 text-sm text-right">{{ account.period_credit > 0 ? formatMoney(account.period_credit) : '' }}</td>
                </tr>
              </tbody>
              <tfoot class="bg-gray-100 font-semibold">
                <tr>
                  <td colspan="2" class="px-3 py-2 text-sm">{{ $t('general.total') }}</td>
                  <td class="px-3 py-2 text-sm text-right">{{ formatMoney(trialBalanceData.totals?.period_debit) }}</td>
                  <td class="px-3 py-2 text-sm text-right">{{ formatMoney(trialBalanceData.totals?.period_credit) }}</td>
                </tr>
              </tfoot>
            </table>

            <div v-else class="text-center py-8 text-gray-500 text-sm">
              {{ t('no_data') }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import ccMessages from '@/scripts/admin/i18n/cost-centers.js'

const route = useRoute()
const notificationStore = useNotificationStore()

const currentRouteName = computed(() => route.name)

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return ccMessages[locale]?.cost_centers?.[key]
    || ccMessages['en']?.cost_centers?.[key]
    || key
}

const tabs = computed(() => [
  { name: 'cost-centers.index', to: '/admin/cost-centers', label: t('title') },
  { name: 'cost-centers.rules', to: '/admin/cost-centers/rules', label: t('rules') },
  { name: 'cost-centers.summary', to: '/admin/cost-centers/summary', label: t('summary') },
])

// State
const summaryData = ref(null)
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)

// Trial balance modal
const showTrialBalanceModal = ref(false)
const selectedCenterForTB = ref(null)
const trialBalanceData = ref(null)
const isTBLoading = ref(false)

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const filters = ref({
  from_date: `${new Date().getFullYear()}-01-01`,
  to_date: getLocalDateString(),
})

// Lifecycle
onMounted(() => {
  loadSummary()
})

// Methods
async function loadSummary() {
  isLoading.value = true
  hasSearched.value = true
  summaryData.value = null

  try {
    const response = await window.axios.get('/cost-centers/summary', {
      params: {
        from_date: filters.value.from_date,
        to_date: filters.value.to_date,
      },
    })

    summaryData.value = response.data?.data || { centers: [], grand_total: {}, unassigned: {} }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading') || 'Failed to load data',
    })
  } finally {
    isLoading.value = false
  }
}

async function viewTrialBalance(center) {
  selectedCenterForTB.value = center
  showTrialBalanceModal.value = true
  isTBLoading.value = true
  trialBalanceData.value = null

  try {
    const response = await window.axios.get(`/cost-centers/${center.cost_center_id}/trial-balance`, {
      params: {
        from_date: filters.value.from_date,
        to_date: filters.value.to_date,
      },
    })
    trialBalanceData.value = response.data?.trial_balance || { accounts: [], totals: {} }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading') || 'Failed to load data',
    })
  } finally {
    isTBLoading.value = false
  }
}

async function exportToCsv() {
  if (!summaryData.value?.centers) return

  isExporting.value = true
  try {
    const headers = [
      t('title'),
      t('code'),
      t('income'),
      t('expenses'),
      t('net'),
      '% Total',
    ]

    const rows = summaryData.value.centers.map(c => [
      c.name,
      c.code || '',
      c.total_credit || 0,
      c.total_debit || 0,
      c.net || 0,
      c.percentage || 0,
    ])

    const csvContent = [
      headers.join(','),
      ...rows.map(row => row.map(cell => `"${String(cell).replace(/"/g, '""')}"`).join(',')),
    ].join('\n')

    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `cost_centers_summary_${filters.value.from_date}_${filters.value.to_date}.csv`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    notificationStore.showNotification({
      type: 'success',
      message: 'Export successful',
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Export failed',
    })
  } finally {
    isExporting.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  try {
    const formatted = new Intl.NumberFormat(undefined, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(Math.abs(amount))
    const sign = amount < 0 ? '-' : ''
    return `${sign}${formatted}`
  } catch {
    return '-'
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
