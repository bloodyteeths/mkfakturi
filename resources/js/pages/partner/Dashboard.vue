<template>
  <div class="partner-dashboard">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Partner Dashboard</h1>
      <p class="text-gray-600">Track your earnings, referrals, and partner performance</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <!-- Total Earnings -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">Total Earnings</h3>
          <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">
          {{ formatCurrency(dashboardData.totalEarnings) }}
        </div>
        <p class="text-xs text-gray-500 mt-1">Lifetime commission earnings</p>
      </div>

      <!-- This Month -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">This Month</h3>
          <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">
          {{ formatCurrency(dashboardData.monthlyEarnings) }}
        </div>
        <p class="text-xs text-gray-500 mt-1">{{ currentMonthName }} earnings</p>
      </div>

      <!-- Pending Payout -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">Pending Payout</h3>
          <svg class="w-8 h-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">
          {{ formatCurrency(dashboardData.pendingPayout) }}
        </div>
        <p class="text-xs text-gray-500 mt-1">Awaiting next payout</p>
      </div>

      <!-- Active Clients -->
      <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between mb-2">
          <h3 class="text-sm font-medium text-gray-600">Active Clients</h3>
          <svg class="w-8 h-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
        </div>
        <div class="text-3xl font-bold text-gray-900">
          {{ dashboardData.activeClients }}
        </div>
        <p class="text-xs text-gray-500 mt-1">Referred companies</p>
      </div>
    </div>

    <!-- Next Payout Countdown -->
    <div v-if="dashboardData.nextPayout" class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-6 mb-8 border border-blue-200">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-blue-900 mb-1">Next Payout</h3>
          <p class="text-blue-700">
            {{ formatCurrency(dashboardData.nextPayout.amount) }} will be paid on
            <span class="font-semibold">{{ formatDate(dashboardData.nextPayout.date) }}</span>
          </p>
        </div>
        <div class="text-right">
          <div class="text-3xl font-bold text-blue-900">{{ daysUntilPayout }}</div>
          <div class="text-sm text-blue-700">days remaining</div>
        </div>
      </div>
    </div>

    <!-- Earnings Chart -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Monthly Earnings Trend</h3>
      <div class="h-64">
        <canvas ref="earningsChart"></canvas>
      </div>
    </div>

    <!-- Recent Commissions Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Recent Commissions</h3>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="commission in dashboardData.recentCommissions" :key="commission.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(commission.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ commission.company_name }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatCommissionType(commission.event_type) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                {{ formatCurrency(commission.amount) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getStatusBadgeClass(commission.paid_at)" class="px-2 py-1 text-xs font-semibold rounded-full">
                  {{ commission.paid_at ? 'Paid' : 'Pending' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!dashboardData.recentCommissions || dashboardData.recentCommissions.length === 0" class="px-6 py-12 text-center text-gray-500">
        No recent commissions yet
      </div>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

export default {
  name: 'PartnerDashboard',

  data() {
    return {
      dashboardData: {
        totalEarnings: 0,
        monthlyEarnings: 0,
        pendingPayout: 0,
        activeClients: 0,
        nextPayout: null,
        earningsHistory: [],
        recentCommissions: []
      },
      chart: null,
      loading: true
    }
  },

  computed: {
    currentMonthName() {
      return new Date().toLocaleDateString('en-US', { month: 'long' })
    },

    daysUntilPayout() {
      if (!this.dashboardData.nextPayout) return 0
      const today = new Date()
      const payoutDate = new Date(this.dashboardData.nextPayout.date)
      const diffTime = payoutDate - today
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
      return diffDays > 0 ? diffDays : 0
    }
  },

  mounted() {
    this.fetchDashboardData()
  },

  beforeUnmount() {
    if (this.chart) {
      this.chart.destroy()
    }
  },

  methods: {
    async fetchDashboardData() {
      this.loading = true
      try {
        const response = await axios.get('/api/partner/dashboard')
        this.dashboardData = response.data

        this.$nextTick(() => {
          this.renderChart()
        })
      } catch (error) {
        console.error('Failed to fetch partner dashboard data:', error)
      } finally {
        this.loading = false
      }
    },

    renderChart() {
      if (!this.$refs.earningsChart) return

      const ctx = this.$refs.earningsChart.getContext('2d')

      if (this.chart) {
        this.chart.destroy()
      }

      const labels = this.dashboardData.earningsHistory.map(item => item.month)
      const data = this.dashboardData.earningsHistory.map(item => parseFloat(item.amount))

      this.chart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Monthly Earnings (EUR)',
            data: data,
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              callbacks: {
                label: (context) => {
                  return 'EUR ' + context.parsed.y.toFixed(2)
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: (value) => 'EUR ' + value
              }
            }
          }
        }
      })
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'EUR'
      }).format(amount || 0)
    },

    formatDate(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },

    formatCommissionType(type) {
      const types = {
        recurring_commission: 'Recurring',
        company_bounty: 'Company Bounty',
        partner_bounty: 'Partner Bounty',
        upline_commission: 'Upline'
      }
      return types[type] || type
    },

    getStatusBadgeClass(paidAt) {
      return paidAt
        ? 'bg-green-100 text-green-800'
        : 'bg-yellow-100 text-yellow-800'
    }
  }
}
</script>

<style scoped>
.partner-dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
<!-- CLAUDE-CHECKPOINT -->
