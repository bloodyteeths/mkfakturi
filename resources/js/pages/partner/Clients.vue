<template>
  <div class="clients-page">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Referred Clients</h1>
      <p class="text-gray-600">Manage and track your referred companies</p>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Search -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Search Companies</label>
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search by company name..."
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @input="fetchClients"
          />
        </div>

        <!-- Status Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
          <select
            v-model="filters.status"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchClients"
          >
            <option value="">All Statuses</option>
            <option value="trial">Trial</option>
            <option value="active">Active</option>
            <option value="canceled">Canceled</option>
            <option value="suspended">Suspended</option>
          </select>
        </div>

        <!-- Plan Filter -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Plan</label>
          <select
            v-model="filters.plan"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            @change="fetchClients"
          >
            <option value="">All Plans</option>
            <option value="free">Free</option>
            <option value="starter">Starter</option>
            <option value="standard">Standard</option>
            <option value="business">Business</option>
            <option value="max">Max</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Total Clients</h3>
        <div class="text-3xl font-bold text-gray-900">{{ summary.totalClients }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Active Subscriptions</h3>
        <div class="text-3xl font-bold text-green-600">{{ summary.activeClients }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Monthly Recurring Revenue</h3>
        <div class="text-3xl font-bold text-blue-600">{{ formatCurrency(summary.totalMRR) }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Your Monthly Commission</h3>
        <div class="text-3xl font-bold text-purple-600">{{ formatCurrency(summary.monthlyCommission) }}</div>
      </div>
    </div>

    <!-- Clients Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Client List</h3>
        <div class="text-sm text-gray-600">
          Showing {{ clients.length }} of {{ pagination.total }} clients
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Company Name
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Plan
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                MRR
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Signup Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Your Commission
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="client in clients" :key="client.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <div v-if="client.logo" class="h-10 w-10 rounded-full overflow-hidden">
                      <img :src="client.logo" :alt="client.name" class="h-full w-full object-cover" />
                    </div>
                    <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                      <span class="text-gray-500 font-medium text-sm">{{ getInitials(client.name) }}</span>
                    </div>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ client.name }}</div>
                    <div class="text-sm text-gray-500">{{ client.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="getPlanBadgeClass(client.plan)">
                  {{ formatPlan(client.plan) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                {{ formatCurrency(client.mrr) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="getStatusBadgeClass(client.status)">
                  {{ formatStatus(client.status) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ formatDate(client.signup_date) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">
                {{ formatCurrency(client.commission) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="!loading && clients.length === 0" class="px-6 py-12 text-center">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No clients found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ filters.search || filters.status || filters.plan ? 'Try adjusting your filters' : 'Start referring clients to see them here' }}
        </p>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.perPage" class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} results
        </div>
        <div class="flex gap-2">
          <button
            @click="goToPage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Previous
          </button>
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="[
              'px-3 py-1 border rounded-lg text-sm',
              page === pagination.currentPage
                ? 'bg-blue-600 text-white border-blue-600'
                : 'border-gray-300 hover:bg-gray-50'
            ]"
          >
            {{ page }}
          </button>
          <button
            @click="goToPage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PartnerClients',

  data() {
    return {
      clients: [],
      summary: {
        totalClients: 0,
        activeClients: 0,
        totalMRR: 0,
        monthlyCommission: 0
      },
      filters: {
        search: '',
        status: '',
        plan: ''
      },
      pagination: {
        currentPage: 1,
        perPage: 20,
        total: 0,
        lastPage: 1,
        from: 0,
        to: 0
      },
      loading: false
    }
  },

  computed: {
    visiblePages() {
      const pages = []
      const current = this.pagination.currentPage
      const last = this.pagination.lastPage

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
    }
  },

  mounted() {
    this.fetchClients()
  },

  methods: {
    async fetchClients() {
      this.loading = true
      try {
        const params = {
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
          search: this.filters.search || undefined,
          status: this.filters.status || undefined,
          plan: this.filters.plan || undefined
        }

        const response = await axios.get('/api/partner/clients', { params })
        this.clients = response.data.data
        this.summary = response.data.summary
        this.pagination = {
          currentPage: response.data.current_page,
          perPage: response.data.per_page,
          total: response.data.total,
          lastPage: response.data.last_page,
          from: response.data.from,
          to: response.data.to
        }
      } catch (error) {
        console.error('Failed to fetch clients:', error)
      } finally {
        this.loading = false
      }
    },

    goToPage(page) {
      if (page < 1 || page > this.pagination.lastPage || page === '...') return
      this.pagination.currentPage = page
      this.fetchClients()
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

    formatPlan(plan) {
      const plans = {
        free: 'Free',
        starter: 'Starter',
        standard: 'Standard',
        business: 'Business',
        max: 'Max'
      }
      return plans[plan] || plan
    },

    formatStatus(status) {
      const statuses = {
        trial: 'Trial',
        active: 'Active',
        canceled: 'Canceled',
        suspended: 'Suspended'
      }
      return statuses[status] || status
    },

    getPlanBadgeClass(plan) {
      const classes = {
        free: 'bg-gray-100 text-gray-800',
        starter: 'bg-blue-100 text-blue-800',
        standard: 'bg-green-100 text-green-800',
        business: 'bg-purple-100 text-purple-800',
        max: 'bg-yellow-100 text-yellow-800'
      }
      return classes[plan] || 'bg-gray-100 text-gray-800'
    },

    getStatusBadgeClass(status) {
      const classes = {
        trial: 'bg-blue-100 text-blue-800',
        active: 'bg-green-100 text-green-800',
        canceled: 'bg-red-100 text-red-800',
        suspended: 'bg-yellow-100 text-yellow-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    },

    getInitials(name) {
      if (!name) return '?'
      const parts = name.split(' ')
      if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase()
      }
      return name.substring(0, 2).toUpperCase()
    }
  }
}
</script>

<style scoped>
.clients-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
<!-- CLAUDE-CHECKPOINT -->
