<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 flex justify-between items-start">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Portfolio</h1>
          <p class="mt-2 text-sm text-gray-600">
            Manage all your client companies in one place.
          </p>
        </div>
        <router-link
          v-if="stats"
          :to="{ name: 'partner.portfolio.companies.create' }"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm font-medium"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Company
        </router-link>
      </div>

      <!-- Onboarding (if not activated) -->
      <div v-if="!loading && !portfolioEnabled" class="bg-white shadow rounded-lg p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">Accountant Portfolio</h2>
        <p class="text-gray-600 mb-2 max-w-lg mx-auto">
          Add all your companies to Facturino for free. Convert your clients to paid subscribers and earn 20% monthly commission.
        </p>
        <p class="text-sm text-gray-500 mb-6 max-w-lg mx-auto">
          You get a 3-month grace period with full Standard features for all companies. After that, each paying company covers 1 non-paying company for premium features.
        </p>
        <button
          :disabled="activating"
          class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-md hover:bg-blue-700 font-medium disabled:opacity-50"
          @click="activatePortfolio"
        >
          {{ activating ? 'Activating...' : 'Activate Portfolio' }}
        </button>
      </div>

      <!-- Stats Cards -->
      <div v-if="stats" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white shadow rounded-lg p-4">
          <p class="text-sm text-gray-500">Total Companies</p>
          <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
          <p class="text-sm text-gray-500">Paying</p>
          <p class="text-2xl font-bold text-green-600">{{ stats.paying }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
          <p class="text-sm text-gray-500">Covered (Standard)</p>
          <p class="text-2xl font-bold text-blue-600">{{ stats.covered }}</p>
        </div>
        <div class="bg-white shadow rounded-lg p-4">
          <p class="text-sm text-gray-500">Uncovered (Basic)</p>
          <p class="text-2xl font-bold" :class="stats.uncovered > 0 ? 'text-orange-500' : 'text-gray-400'">
            {{ stats.uncovered }}
          </p>
        </div>
      </div>

      <!-- Progress Bar -->
      <div v-if="stats && stats.total > 0" class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center mb-2">
          <span class="text-sm font-medium text-gray-700">Coverage Progress</span>
          <span class="text-sm text-gray-500">
            <template v-if="stats.in_grace">
              Grace period active (all companies have Standard features)
            </template>
            <template v-else-if="stats.uncovered === 0">
              All companies covered!
            </template>
            <template v-else>
              {{ stats.non_paying - stats.covered }} more paying companies needed to cover all
            </template>
          </span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
          <div
            class="h-3 rounded-full transition-all duration-300"
            :class="coveragePercentage >= 100 ? 'bg-green-500' : 'bg-blue-500'"
            :style="{ width: Math.min(coveragePercentage, 100) + '%' }"
          ></div>
        </div>
        <div v-if="stats.in_grace && stats.grace_ends_at" class="mt-2 text-xs text-gray-500">
          Grace period ends: {{ formatDate(stats.grace_ends_at) }}
        </div>
      </div>

      <!-- Commission Summary -->
      <div v-if="commission" class="bg-white shadow rounded-lg p-4 mb-6">
        <h3 class="text-sm font-medium text-gray-700 mb-3">Commission Earnings</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="text-xs text-gray-500">Commission Rate</p>
            <p class="text-lg font-semibold">{{ (commission.rate * 100).toFixed(0) }}%</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Monthly Revenue (from paying clients)</p>
            <p class="text-lg font-semibold">&euro;{{ commission.monthly_revenue }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">Your Monthly Commission</p>
            <p class="text-lg font-semibold text-green-600">&euro;{{ commission.monthly_commission }}</p>
          </div>
        </div>
      </div>

      <!-- Companies List -->
      <div v-if="stats" class="bg-white shadow rounded-lg">
        <div class="p-4 border-b flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900">Portfolio Companies</h3>
          <div class="flex gap-2">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search companies..."
              class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
              @input="debouncedSearch"
            />
            <select
              v-model="statusFilter"
              class="px-3 py-1.5 border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500"
              @change="loadCompanies"
            >
              <option value="">All</option>
              <option value="paying">Paying</option>
              <option value="non_paying">Non-paying</option>
            </select>
          </div>
        </div>

        <div v-if="companiesLoading" class="p-8 text-center text-gray-500">Loading...</div>

        <table v-else-if="companies.length > 0" class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax ID</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="company in companies" :key="company.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ company.name }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ company.tax_id }}</td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="{
                    'bg-green-100 text-green-800': company.portfolio_status === 'paying',
                    'bg-blue-100 text-blue-800': company.portfolio_status === 'covered',
                    'bg-orange-100 text-orange-800': company.portfolio_status === 'uncovered',
                  }"
                >
                  {{ company.portfolio_status === 'paying' ? 'Paying' : company.portfolio_status === 'covered' ? 'Covered' : 'Basic' }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 capitalize">{{ company.portfolio_tier }}</td>
              <td class="px-4 py-3 text-right">
                <button
                  class="text-sm text-red-600 hover:text-red-800"
                  @click="removeCompany(company)"
                >
                  Remove
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="p-8 text-center text-gray-500">
          No companies in your portfolio yet. Click "Add Company" to get started.
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="p-4 border-t flex justify-between items-center">
          <span class="text-sm text-gray-500">
            Showing {{ companies.length }} of {{ pagination.total }} companies
          </span>
          <div class="flex gap-2">
            <button
              :disabled="pagination.current_page <= 1"
              class="px-3 py-1 border rounded text-sm disabled:opacity-50"
              @click="loadCompanies(pagination.current_page - 1)"
            >
              Previous
            </button>
            <button
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-1 border rounded text-sm disabled:opacity-50"
              @click="loadCompanies(pagination.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const loading = ref(true)
const activating = ref(false)
const companiesLoading = ref(false)
const portfolioEnabled = ref(false)
const stats = ref(null)
const commission = ref(null)
const companies = ref([])
const searchQuery = ref('')
const statusFilter = ref('')
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })

let searchTimeout = null

const coveragePercentage = computed(() => {
  if (!stats.value || stats.value.non_paying === 0) return 100
  return Math.round((stats.value.covered / stats.value.non_paying) * 100)
})

const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => loadCompanies(), 300)
}

const formatDate = (dateStr) => {
  return new Date(dateStr).toLocaleDateString('en-GB', {
    day: 'numeric', month: 'short', year: 'numeric'
  })
}

const loadStats = async () => {
  try {
    const { data } = await axios.get('/partner/portfolio/stats')
    portfolioEnabled.value = data.portfolio_enabled
    stats.value = data.stats || null
    commission.value = data.commission || null
  } catch (e) {
    console.error('Failed to load portfolio stats', e)
  }
}

const loadCompanies = async (page = 1) => {
  companiesLoading.value = true
  try {
    const params = { page, per_page: 25 }
    if (searchQuery.value) params.search = searchQuery.value
    if (statusFilter.value) params.status = statusFilter.value

    const { data } = await axios.get('/partner/portfolio-companies', { params })
    companies.value = data.data
    pagination.value = data.meta
    if (data.stats) stats.value = data.stats
  } catch (e) {
    console.error('Failed to load companies', e)
  } finally {
    companiesLoading.value = false
  }
}

const activatePortfolio = async () => {
  activating.value = true
  try {
    await axios.post('/partner/portfolio/activate')
    await loadStats()
    await loadCompanies()
  } catch (e) {
    console.error('Failed to activate portfolio', e)
    alert(e.response?.data?.error || 'Failed to activate portfolio')
  } finally {
    activating.value = false
  }
}

const removeCompany = async (company) => {
  if (!confirm(`Remove "${company.name}" from your portfolio?`)) return

  try {
    await axios.delete(`/partner/portfolio-companies/${company.id}`)
    await loadStats()
    await loadCompanies(pagination.value.current_page)
  } catch (e) {
    console.error('Failed to remove company', e)
    alert(e.response?.data?.error || 'Failed to remove company')
  }
}

onMounted(async () => {
  await loadStats()
  if (portfolioEnabled.value) {
    await loadCompanies()
  }
  loading.value = false
})
</script>
