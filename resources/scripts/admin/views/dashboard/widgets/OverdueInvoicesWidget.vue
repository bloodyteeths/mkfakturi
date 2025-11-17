<template>
  <BaseCard>
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-red-600">
          {{ $t('dashboard.overdue_invoices') || 'Overdue Invoices' }}
        </h3>
        <BaseIcon name="ExclamationTriangleIcon" class="h-6 w-6 text-red-500" />
      </div>
    </template>

    <!-- Loading State -->
    <LoadingSkeleton v-if="isLoading" variant="list" :rows="3" />

    <!-- No Overdue Invoices -->
    <div v-else-if="!overdueInvoices.length" class="text-center py-6">
      <BaseIcon name="CheckCircleIcon" class="h-16 w-16 mx-auto text-green-500 mb-2" />
      <p class="text-sm text-gray-600">
        {{ $t('dashboard.no_overdue_invoices') || 'No overdue invoices' }}
      </p>
    </div>

    <!-- Overdue Invoices List -->
    <div v-else class="space-y-3">
      <!-- Summary Alert -->
      <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-red-800">
              {{ $t('dashboard.total_overdue') || 'Total Overdue' }}
            </p>
            <p class="text-xs text-red-600 mt-1">
              {{ overdueInvoices.length }} {{ $t('invoices.invoice', overdueInvoices.length) }}
            </p>
          </div>
          <div class="text-right">
            <div>
              <div v-if="totalOverdueAmount !== null" class="text-lg font-bold text-red-700">
                {{ formatAmount(totalOverdueAmount) }}
              </div>
              <div v-else class="text-sm text-gray-500">
                Calculating...
              </div>
              <div class="text-xs text-gray-500">
                {{ overdueInvoices.length }} {{ $t('invoices.invoice', overdueInvoices.length) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Invoice List (limited to 5) -->
      <div
        v-for="invoice in displayedInvoices"
        :key="invoice.id"
        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
      >
        <div class="flex-1 min-w-0">
          <router-link
            :to="{ path: `/admin/invoices/${invoice.id}/view` }"
            class="text-sm font-medium text-primary-500 hover:text-primary-600 truncate block"
          >
            {{ invoice.invoice_number }}
          </router-link>
          <p class="text-xs text-gray-600 truncate">
            {{ invoice.customer.name }}
          </p>
          <p class="text-xs text-red-600 mt-1">
            {{ $t('invoices.overdue_by') || 'Overdue by' }} {{ getDaysOverdue(invoice.due_date) }} {{ $t('general.days') || 'days' }}
          </p>
        </div>
        <div class="ml-4 text-right flex-shrink-0">
          <BaseFormatMoney
            :amount="invoice.due_amount"
            :currency="invoice.currency"
            class="text-sm font-semibold text-gray-900"
          />
        </div>
      </div>

      <!-- View All Link -->
      <div v-if="overdueInvoices.length > 5" class="pt-3 border-t border-gray-200">
        <router-link
          to="/admin/invoices?status=overdue"
          class="text-sm text-primary-500 hover:text-primary-600 font-medium flex items-center justify-center"
        >
          {{ $t('dashboard.view_all_overdue') || 'View all overdue invoices' }}
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4 ml-1" />
        </router-link>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useInvoiceStore } from '@/scripts/admin/stores/invoice'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import LoadingSkeleton from '@/scripts/admin/components/LoadingSkeleton.vue'

// Helper function to format amounts with currency
function formatAmount(amount) {
  if (amount === null || amount === undefined) return '0.00'
  
  const numericAmount = typeof amount === 'string' 
    ? parseFloat(amount.replace(/[^0-9.,]/g, '').replace(',', '.'))
    : Number(amount)
    
  if (isNaN(numericAmount)) return '0.00'
  
  // Format with 2 decimal places and thousand separators
  return numericAmount.toLocaleString('mk-MK', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  })
}

const invoiceStore = useInvoiceStore()
const globalStore = useGlobalStore()

const isLoading = ref(true)
const overdueInvoices = ref([])

const defaultCurrency = computed(() => {
  return globalStore.companySettings?.currency || { code: 'MKD', symbol: 'ден' }
})

const displayedInvoices = computed(() => {
  return overdueInvoices.value.slice(0, 5)
})

const totalOverdueAmount = computed(() => {
  if (!overdueInvoices.value.length) return 0
  
  // Extract the raw amount from each invoice, handling different possible formats
  return overdueInvoices.value.reduce((sum, invoice) => {
    // Try different possible amount properties
    const amount = invoice.due_amount || invoice.amount_due || invoice.total || 0
    const numericAmount = typeof amount === 'string' 
      ? parseFloat(amount.replace(/[^0-9.,]/g, '').replace(',', '.')) 
      : Number(amount)
    
    return sum + (isNaN(numericAmount) ? 0 : numericAmount)
  }, 0)
})

function getDaysOverdue(dueDate) {
  if (!dueDate) return 0
  
  // Ensure the date is in the correct format (handle both string and Date objects)
  const due = new Date(dueDate)
  const today = new Date()
  
  // Set both dates to the same time to compare just the dates
  today.setHours(0, 0, 0, 0)
  due.setHours(0, 0, 0, 0)
  
  // Calculate difference in days
  const diffTime = today.getTime() - due.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  // Return 0 if the invoice is not yet due
  return Math.max(0, diffDays)
}

async function fetchOverdueInvoices() {
  isLoading.value = true
  try {
    // Fetch invoices with DUE status (overdue)
    const response = await invoiceStore.fetchInvoices({
      status: 'DUE',
      orderByField: 'due_date',
      orderBy: 'asc',
      limit: 10, // Fetch top 10 overdue
    })

    if (!response?.data?.data) {
      console.error('Error: Invalid response format from server')
      overdueInvoices.value = []
      return
    }

    // Filter to only truly overdue invoices (due_date < today)
    const today = new Date()
    today.setHours(0, 0, 0, 0) // Normalize to start of day
    
    const processedInvoices = response.data.data.map(invoice => {
      const dueDate = invoice.due_date ? new Date(invoice.due_date) : null
      const amount = parseFloat(invoice.due_amount || 0)
      const isOverdue = dueDate ? (new Date(dueDate) < today) : false
      
      return {
        ...invoice,
        _processed: {
          dueDate: dueDate,
          formattedDueDate: dueDate ? dueDate.toISOString().split('T')[0] : 'N/A',
          amount: amount,
          isOverdue: isOverdue,
          daysOverdue: isOverdue ? getDaysOverdue(dueDate) : 0
        }
      }
    })
    
    // Filter to only show truly overdue invoices with positive amounts
    overdueInvoices.value = processedInvoices.filter(invoice => {
      return invoice._processed.isOverdue && invoice._processed.amount > 0
    })
  } catch (error) {
    console.error('Error fetching overdue invoices:', error)
    overdueInvoices.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchOverdueInvoices()
})
</script>

// CLAUDE-CHECKPOINT
