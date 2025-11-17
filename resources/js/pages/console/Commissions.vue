<template>
  <BasePage>
    <BasePageHeader :title="$t('console.commissions.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/console" />
        <BaseBreadcrumbItem :title="$t('console.commissions.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-3">
      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <div class="text-sm text-gray-500">{{ $t('console.commissions.total_earnings') }}</div>
        <div class="text-3xl font-bold text-green-600 mt-2">
          <BaseFormatMoney v-if="data" :amount="data.kpis.total_earnings" :currency="currency" />
          <div v-else class="h-8 bg-gray-200 rounded animate-pulse"></div>
        </div>
      </div>

      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <div class="text-sm text-gray-500">{{ $t('console.commissions.this_month') }}</div>
        <div class="text-3xl font-bold text-blue-600 mt-2">
          <BaseFormatMoney v-if="data" :amount="data.kpis.this_month" :currency="currency" />
          <div v-else class="h-8 bg-gray-200 rounded animate-pulse"></div>
        </div>
      </div>

      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <div class="text-sm text-gray-500">{{ $t('console.commissions.pending_payout') }}</div>
        <div class="text-3xl font-bold text-orange-600 mt-2">
          <BaseFormatMoney v-if="data" :amount="data.kpis.pending_payout" :currency="currency" />
          <div v-else class="h-8 bg-gray-200 rounded animate-pulse"></div>
        </div>
      </div>
    </div>

    <!-- Monthly Trend Chart -->
    <div class="mt-6 p-6 bg-white border border-gray-200 rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('console.commissions.monthly_trend') }}</h3>
      <div v-if="data && data.monthly_trend.length > 0" class="space-y-2">
        <div v-for="item in data.monthly_trend" :key="item.month" class="flex items-center justify-between p-3 bg-gray-50 rounded">
          <span class="text-sm font-medium text-gray-700">{{ item.month }}</span>
          <BaseFormatMoney :amount="item.total" :currency="currency" class="text-sm font-semibold text-green-600" />
        </div>
      </div>
      <div v-else-if="loading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-12 bg-gray-200 rounded animate-pulse"></div>
      </div>
      <div v-else class="text-center text-gray-500 py-8">{{ $t('console.commissions.no_data') }}</div>
    </div>

    <!-- Per-Company Breakdown -->
    <div class="mt-6 p-6 bg-white border border-gray-200 rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('console.commissions.per_company') }}</h3>
      <div v-if="data && data.per_company.length > 0" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.company') }}</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('console.commissions.total') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="company in data.per_company" :key="company.id">
              <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ company.name }}</td>
              <td class="px-6 py-4 text-sm text-right font-semibold text-green-600">
                <BaseFormatMoney :amount="company.total" :currency="currency" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else-if="loading" class="space-y-2">
        <div v-for="i in 4" :key="i" class="h-12 bg-gray-200 rounded animate-pulse"></div>
      </div>
      <div v-else class="text-center text-gray-500 py-8">{{ $t('console.commissions.no_companies') }}</div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const globalStore = useGlobalStore()
const loading = ref(true)
const data = ref(null)
const currency = globalStore.companySettings?.currency || { code: 'EUR' }

async function fetchCommissions() {
  loading.value = true
  try {
    const response = await axios.get('/console/commissions')
    data.value = response.data
  } catch (error) {
    console.error('Failed to load commissions:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchCommissions()
})
</script>
