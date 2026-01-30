<template>
  <BaseCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('dashboard.unpaid_summary') || 'Unpaid Invoices' }}
        </h3>
        <BaseIcon name="BanknotesIcon" class="h-6 w-6 text-orange-500" />
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="isLoading" class="animate-pulse space-y-4">
      <div class="h-10 bg-gray-200 rounded w-2/3"></div>
      <div class="h-4 bg-gray-200 rounded w-1/2"></div>
    </div>

    <!-- Content -->
    <div v-else>
      <!-- Total Unpaid Amount - Prominent -->
      <div class="text-center py-4">
        <p class="text-sm text-gray-500 mb-1">
          {{ $t('dashboard.total_outstanding') || 'Total Outstanding' }}
        </p>
        <p class="text-3xl font-bold" :class="totalUnpaid > 0 ? 'text-orange-600' : 'text-green-600'">
          {{ formatMoney(totalUnpaid) }}
        </p>
        <p class="text-sm text-gray-500 mt-1">
          {{ unpaidCount }} {{ $t('dashboard.unpaid_invoices_count') || 'unpaid invoices' }}
        </p>
      </div>

      <!-- Breakdown by Age -->
      <div v-if="totalUnpaid > 0" class="border-t pt-4 mt-4 space-y-2">
        <div class="flex justify-between items-center text-sm">
          <span class="text-gray-600">{{ $t('dashboard.due_today') || 'Due Today' }}</span>
          <span class="font-medium text-gray-900">{{ formatMoney(dueTodayAmount) }}</span>
        </div>
        <div class="flex justify-between items-center text-sm">
          <span class="text-gray-600">{{ $t('dashboard.overdue') || 'Overdue' }}</span>
          <span class="font-medium" :class="overdueAmount > 0 ? 'text-red-600' : 'text-gray-900'">
            {{ formatMoney(overdueAmount) }}
          </span>
        </div>
        <div class="flex justify-between items-center text-sm">
          <span class="text-gray-600">{{ $t('dashboard.upcoming') || 'Upcoming' }}</span>
          <span class="font-medium text-gray-900">{{ formatMoney(upcomingAmount) }}</span>
        </div>
      </div>

      <!-- All Paid State -->
      <div v-else class="text-center py-4">
        <BaseIcon name="CheckCircleIcon" class="h-12 w-12 mx-auto text-green-500 mb-2" />
        <p class="text-sm text-gray-600">{{ $t('dashboard.all_invoices_paid') || 'All invoices are paid!' }}</p>
      </div>

      <!-- View All Link -->
      <div class="border-t pt-3 mt-4">
        <router-link
          to="/admin/invoices?status=UNPAID"
          class="text-sm text-primary-500 hover:text-primary-600 font-medium flex items-center justify-center"
        >
          {{ $t('dashboard.view_all_unpaid') || 'View all unpaid invoices' }}
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4 ml-1" />
        </router-link>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const invoiceStore = useInvoiceStore()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const unpaidInvoices = ref([])

const currencySymbol = computed(() => {
  return companyStore.selectedCompanyCurrency?.symbol || 'ден'
})

const totalUnpaid = computed(() => {
  return unpaidInvoices.value.reduce((sum, inv) => {
    const amount = parseFloat(inv.due_amount) || 0
    return sum + amount
  }, 0)
})

const unpaidCount = computed(() => unpaidInvoices.value.length)

const dueTodayAmount = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return unpaidInvoices.value
    .filter(inv => {
      const dueDate = new Date(inv.due_date)
      dueDate.setHours(0, 0, 0, 0)
      return dueDate.getTime() === today.getTime()
    })
    .reduce((sum, inv) => sum + (parseFloat(inv.due_amount) || 0), 0)
})

const overdueAmount = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return unpaidInvoices.value
    .filter(inv => {
      const dueDate = new Date(inv.due_date)
      dueDate.setHours(0, 0, 0, 0)
      return dueDate < today
    })
    .reduce((sum, inv) => sum + (parseFloat(inv.due_amount) || 0), 0)
})

const upcomingAmount = computed(() => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)

  return unpaidInvoices.value
    .filter(inv => {
      const dueDate = new Date(inv.due_date)
      dueDate.setHours(0, 0, 0, 0)
      return dueDate > today
    })
    .reduce((sum, inv) => sum + (parseFloat(inv.due_amount) || 0), 0)
})

function formatMoney(amount) {
  const formatted = Math.round(amount).toLocaleString('mk-MK')
  return `${formatted} ${currencySymbol.value}`
}

async function fetchUnpaidInvoices() {
  isLoading.value = true
  try {
    const response = await invoiceStore.fetchInvoices({
      status: 'UNPAID',
      limit: 100,
    })

    if (response?.data?.data) {
      unpaidInvoices.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to fetch unpaid invoices:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchUnpaidInvoices()
})
</script>
