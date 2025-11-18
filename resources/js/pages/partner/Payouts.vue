<template>
  <div class="payouts-page">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Payout Management</h1>
      <p class="text-gray-600">View payout history and manage bank account settings</p>
    </div>

    <!-- Bank Account Settings -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Bank Account Details</h3>
        <button
          v-if="!editingBankDetails"
          @click="editingBankDetails = true"
          class="px-4 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
        >
          Edit
        </button>
      </div>

      <div v-if="!editingBankDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Account Holder Name</label>
          <p class="text-gray-900">{{ bankDetails.account_holder || 'Not set' }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Bank Name</label>
          <p class="text-gray-900">{{ bankDetails.bank_name || 'Not set' }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Account Number / IBAN</label>
          <p class="text-gray-900">{{ bankDetails.account_number || 'Not set' }}</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Bank Code / SWIFT</label>
          <p class="text-gray-900">{{ bankDetails.bank_code || 'Not set' }}</p>
        </div>
      </div>

      <form v-else @submit.prevent="saveBankDetails" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name *</label>
            <input
              v-model="bankDetailsForm.account_holder"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name *</label>
            <input
              v-model="bankDetailsForm.bank_name"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Account Number / IBAN *</label>
            <input
              v-model="bankDetailsForm.account_number"
              type="text"
              required
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Code / SWIFT</label>
            <input
              v-model="bankDetailsForm.bank_code"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
        </div>

        <div class="flex gap-3">
          <button
            type="submit"
            :disabled="savingBankDetails"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
          >
            {{ savingBankDetails ? 'Saving...' : 'Save Changes' }}
          </button>
          <button
            type="button"
            @click="cancelEditBankDetails"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>

    <!-- Payout Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Total Paid Out</h3>
        <div class="text-3xl font-bold text-green-600">{{ formatCurrency(payoutSummary.totalPaid) }}</div>
        <p class="text-xs text-gray-500 mt-1">Lifetime payouts</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Pending Payout</h3>
        <div class="text-3xl font-bold text-yellow-600">{{ formatCurrency(payoutSummary.pending) }}</div>
        <p class="text-xs text-gray-500 mt-1">Awaiting processing</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">This Month</h3>
        <div class="text-3xl font-bold text-blue-600">{{ formatCurrency(payoutSummary.thisMonth) }}</div>
        <p class="text-xs text-gray-500 mt-1">Current month earnings</p>
      </div>
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-sm font-medium text-gray-600 mb-2">Next Payout</h3>
        <div class="text-3xl font-bold text-purple-600">{{ formatCurrency(payoutSummary.nextPayout) }}</div>
        <p class="text-xs text-gray-500 mt-1">{{ formatDate(payoutSummary.nextPayoutDate) }}</p>
      </div>
    </div>

    <!-- Payout History Table -->
    <div class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">Payout History</h3>
        <select
          v-model="statusFilter"
          @change="fetchPayouts"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="processing">Processing</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
        </select>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Payout ID
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Date
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Amount
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Payment Method
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Status
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Reference
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Actions
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
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                {{ payout.payment_reference || '-' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button
                  v-if="payout.status === 'completed'"
                  @click="downloadReceipt(payout.id)"
                  class="text-blue-600 hover:text-blue-800 font-medium"
                >
                  Download Receipt
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
        <h3 class="mt-2 text-sm font-medium text-gray-900">No payouts yet</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ statusFilter ? 'No payouts with this status' : 'Payouts will appear here once processed' }}
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
  name: 'PartnerPayouts',

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
      loading: false
    }
  },

  mounted() {
    this.fetchBankDetails()
    this.fetchPayouts()
  },

  methods: {
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
        alert('Failed to save bank details. Please try again.')
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
        this.payoutSummary = response.data.summary
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
        alert('Failed to download receipt. Please try again.')
      }
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

    formatPaymentMethod(method) {
      const methods = {
        bank_transfer: 'Bank Transfer',
        paypal: 'PayPal',
        stripe: 'Stripe',
        manual: 'Manual'
      }
      return methods[method] || method || '-'
    },

    formatStatus(status) {
      const statuses = {
        pending: 'Pending',
        processing: 'Processing',
        completed: 'Completed',
        failed: 'Failed',
        cancelled: 'Cancelled'
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
    }
  }
}
</script>

<style scoped>
.payouts-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
<!-- CLAUDE-CHECKPOINT -->
