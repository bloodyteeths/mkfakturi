<template>
  <div class="commissions-page min-h-screen overflow-auto pb-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Провизии</h1>
      <p class="text-gray-600">Детален преглед на вашите заработки од провизии</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
      <button @click="fetchCommissions" class="mt-2 text-red-600 underline">Обиди се повторно</button>
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">Вкупни заработки</h3>
          <div class="text-3xl font-bold text-green-600">{{ formatCurrency(kpis.total_earnings) }}</div>
          <p class="text-xs text-gray-500 mt-1">Од почеток</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">Овој месец</h3>
          <div class="text-3xl font-bold text-blue-600">{{ formatCurrency(kpis.this_month) }}</div>
          <p class="text-xs text-gray-500 mt-1">Тековен месец</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
          <h3 class="text-sm font-medium text-gray-600 mb-2">Чека исплата</h3>
          <div class="text-3xl font-bold text-orange-600">{{ formatCurrency(kpis.pending_payout) }}</div>
          <p class="text-xs text-gray-500 mt-1">Ќе се исплати на 5-ти</p>
        </div>
      </div>

      <!-- Monthly Trend -->
      <div class="bg-white rounded-lg shadow mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Месечен тренд</h3>
        </div>
        <div class="p-6">
          <div v-if="monthlyTrend.length > 0" class="space-y-3">
            <div
              v-for="item in monthlyTrend"
              :key="item.month"
              class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition"
            >
              <span class="text-sm font-medium text-gray-700">{{ item.month }}</span>
              <div class="flex items-center gap-4">
                <div class="w-32 bg-gray-200 rounded-full h-2">
                  <div
                    class="bg-green-500 h-2 rounded-full"
                    :style="{ width: getBarWidth(item.total) + '%' }"
                  ></div>
                </div>
                <span class="text-sm font-semibold text-green-600 min-w-[100px] text-right">
                  {{ formatCurrency(item.total) }}
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Нема податоци за месечен тренд
          </div>
        </div>
      </div>

      <!-- Per-Company Breakdown -->
      <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">Провизии по компанија</h3>
        </div>
        <div class="overflow-x-auto">
          <table v-if="perCompany.length > 0" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Компанија
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Стапка
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Овој месец
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Вкупно
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="company in perCompany" :key="company.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                      <span class="text-sm font-bold text-blue-600">
                        {{ company.name.charAt(0).toUpperCase() }}
                      </span>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">{{ company.name }}</div>
                      <div class="text-xs text-gray-500">{{ company.subscription_status || 'Активен' }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                  {{ company.commission_rate || 0 }}%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-blue-600 font-medium">
                  {{ formatCurrency(company.this_month || 0) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-green-600">
                  {{ formatCurrency(company.total) }}
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="2" class="px-6 py-4 text-sm font-medium text-gray-900">
                  Вкупно
                </td>
                <td class="px-6 py-4 text-sm text-right font-bold text-blue-600">
                  {{ formatCurrency(totalThisMonth) }}
                </td>
                <td class="px-6 py-4 text-sm text-right font-bold text-green-600">
                  {{ formatCurrency(totalAll) }}
                </td>
              </tr>
            </tfoot>
          </table>
          <div v-else class="px-6 py-12 text-center text-gray-500">
            Нема податоци за провизии по компанија
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PartnerCommissions',

  data() {
    return {
      loading: true,
      error: null,
      kpis: {
        total_earnings: 0,
        this_month: 0,
        pending_payout: 0
      },
      monthlyTrend: [],
      perCompany: []
    }
  },

  computed: {
    maxMonthlyValue() {
      if (this.monthlyTrend.length === 0) return 1
      return Math.max(...this.monthlyTrend.map(m => m.total), 1)
    },

    totalThisMonth() {
      return this.perCompany.reduce((sum, c) => sum + (c.this_month || 0), 0)
    },

    totalAll() {
      return this.perCompany.reduce((sum, c) => sum + (c.total || 0), 0)
    }
  },

  mounted() {
    this.fetchCommissions()
  },

  methods: {
    async fetchCommissions() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/console/commissions')
        const data = response.data || {}
        this.kpis = data.kpis || { total_earnings: 0, this_month: 0, pending_payout: 0 }
        this.monthlyTrend = data.monthly_trend || []
        this.perCompany = data.per_company || []
      } catch (err) {
        console.error('Failed to load commissions:', err)
        this.error = 'Не можеше да се вчитаат податоците за провизии. Обидете се повторно.'
      } finally {
        this.loading = false
      }
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('mk-MK', {
        style: 'currency',
        currency: 'EUR'
      }).format((amount || 0) / 100)
    },

    getBarWidth(value) {
      if (!value || !this.maxMonthlyValue) return 0
      return Math.min((value / this.maxMonthlyValue) * 100, 100)
    }
  }
}
</script>

<style scoped>
.commissions-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
