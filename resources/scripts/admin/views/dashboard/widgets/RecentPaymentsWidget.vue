<template>
  <BaseCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('dashboard.recent_payments') || 'Recent Payments' }}
        </h3>
        <BaseIcon name="CreditCardIcon" class="h-6 w-6 text-green-500" />
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="isLoading" class="animate-pulse space-y-3">
      <div v-for="i in 4" :key="i" class="flex justify-between">
        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
      </div>
    </div>

    <!-- No Payments -->
    <div v-else-if="!recentPayments.length" class="text-center py-6">
      <BaseIcon name="BanknotesIcon" class="h-12 w-12 mx-auto text-gray-300 mb-2" />
      <p class="text-sm text-gray-500">{{ $t('dashboard.no_recent_payments') || 'No recent payments' }}</p>
    </div>

    <!-- Payments List -->
    <div v-else class="space-y-3">
      <!-- This Month Summary -->
      <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-green-800">
              {{ $t('dashboard.received_this_month') || 'Received This Month' }}
            </p>
            <p class="text-xs text-green-600 mt-0.5">
              {{ paymentsThisMonth }} {{ $t('payments.payment', paymentsThisMonth) || 'payments' }}
            </p>
          </div>
          <p class="text-lg font-bold text-green-700">
            {{ formatMoney(totalThisMonth) }}
          </p>
        </div>
      </div>

      <!-- Payment Items -->
      <div
        v-for="payment in recentPayments"
        :key="payment.id"
        class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0"
      >
        <div class="flex-1 min-w-0">
          <router-link
            :to="`/admin/payments/${payment.id}/view`"
            class="text-sm font-medium text-gray-900 hover:text-primary-500 truncate block"
          >
            {{ payment.customer?.name || 'Unknown' }}
          </router-link>
          <p class="text-xs text-gray-500">
            {{ formatDate(payment.payment_date) }}
            <span v-if="payment.payment_number" class="text-gray-400">
              · {{ payment.payment_number }}
            </span>
          </p>
        </div>
        <div class="ml-4 text-right flex-shrink-0">
          <span class="text-sm font-semibold text-green-600">
            +{{ formatMoney(payment.amount) }}
          </span>
        </div>
      </div>

      <!-- View All Link -->
      <div class="pt-3 border-t border-gray-200">
        <router-link
          to="/admin/payments"
          class="text-sm text-primary-500 hover:text-primary-600 font-medium flex items-center justify-center"
        >
          {{ $t('dashboard.view_all_payments') || 'View all payments' }}
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4 ml-1" />
        </router-link>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { usePaymentStore } from '@/scripts/admin/stores/payment'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const paymentStore = usePaymentStore()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const payments = ref([])

const currencySymbol = computed(() => {
  return companyStore.selectedCompanyCurrency?.symbol || 'ден'
})

const recentPayments = computed(() => {
  return payments.value.slice(0, 5)
})

const totalThisMonth = computed(() => {
  const now = new Date()
  const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)

  return payments.value
    .filter(p => new Date(p.payment_date) >= startOfMonth)
    .reduce((sum, p) => sum + (parseFloat(p.amount) || 0), 0)
})

const paymentsThisMonth = computed(() => {
  const now = new Date()
  const startOfMonth = new Date(now.getFullYear(), now.getMonth(), 1)

  return payments.value.filter(p => new Date(p.payment_date) >= startOfMonth).length
})

function formatMoney(amount) {
  const formatted = Math.round(amount).toLocaleString('mk-MK')
  return `${formatted} ${currencySymbol.value}`
}

function formatDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('mk-MK', {
    day: '2-digit',
    month: 'short',
  })
}

async function fetchRecentPayments() {
  isLoading.value = true
  try {
    const response = await paymentStore.fetchPayments({
      orderByField: 'payment_date',
      orderBy: 'desc',
      limit: 20,
    })

    if (response?.data?.data) {
      payments.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch recent payments:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchRecentPayments()
})
</script>
