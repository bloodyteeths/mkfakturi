<template>
  <div class="payouts-page">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Управување со исплати</h1>
      <p class="text-gray-600">Прегледајте историја на исплати и поставете банкарски детали за Wise</p>
    </div>

    <!-- Wise Payout Info -->
    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg shadow p-6 mb-8">
      <div class="flex items-start gap-4">
        <div class="flex-shrink-0">
          <svg class="w-12 h-12 text-green-600" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">Исплати преку Wise</h3>
          <p class="text-gray-600 mb-3">
            Вашите провизии се исплаќаат преку Wise на 5-ти секој месец.
            Комисиите се процесираат 30 дена по плаќањето од клиентот.
          </p>
          <ul class="text-sm text-gray-600 space-y-1">
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              Ниски провизии за меѓународни трансфери
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              Брзи исплати (1-2 работни дена)
            </li>
            <li class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              Исплата во EUR директно на вашата сметка
            </li>
          </ul>
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
    editBankDetails() {
      this.bankDetailsForm = { ...this.bankDetails }
      this.editingBankDetails = true
    },

    async fetchBankDetails() {
      try {
        const response = await axios.get('/api/v1/partner/bank-details')
        this.bankDetails = response.data
      } catch (error) {
        console.error('Failed to fetch bank details:', error)
      }
    },

    async saveBankDetails() {
      this.savingBankDetails = true
      try {
        const response = await axios.post('/api/v1/partner/bank-details', this.bankDetailsForm)
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

        const response = await axios.get('/api/v1/partner/payouts', { params })
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
        const response = await axios.get(`/api/v1/partner/payouts/${payoutId}/receipt`, {
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
