<template>
  <BasePage>
    <BasePageHeader :title="t('summary')" />

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="bg-white rounded-lg shadow p-6 animate-pulse">
        <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>
        <div class="h-8 bg-gray-200 rounded w-1/4"></div>
      </div>
    </div>

    <template v-else-if="summary">
      <!-- Overview Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-lg shadow p-5">
          <p class="text-xs text-gray-500 uppercase mb-1">{{ t('total_interest') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ formatMoney(summary.total_interest) }}</p>
        </div>
        <div class="bg-amber-50 rounded-lg shadow p-5">
          <p class="text-xs text-amber-600 uppercase mb-1">{{ t('status_calculated') }}</p>
          <p class="text-2xl font-bold text-amber-800">{{ formatMoney(summary.calculated?.amount || 0) }}</p>
          <p class="text-xs text-amber-500">{{ summary.calculated?.count || 0 }} {{ t('items') || 'items' }}</p>
        </div>
        <div class="bg-blue-50 rounded-lg shadow p-5">
          <p class="text-xs text-blue-600 uppercase mb-1">{{ t('status_invoiced') }}</p>
          <p class="text-2xl font-bold text-blue-800">{{ formatMoney(summary.invoiced?.amount || 0) }}</p>
          <p class="text-xs text-blue-500">{{ summary.invoiced?.count || 0 }} {{ t('items') || 'items' }}</p>
        </div>
        <div class="bg-green-50 rounded-lg shadow p-5">
          <p class="text-xs text-green-600 uppercase mb-1">{{ t('status_paid') }}</p>
          <p class="text-2xl font-bold text-green-800">{{ formatMoney(summary.paid?.amount || 0) }}</p>
          <p class="text-xs text-green-500">{{ summary.paid?.count || 0 }} {{ t('items') || 'items' }}</p>
        </div>
      </div>

      <!-- Per-Customer Breakdown -->
      <div v-if="summary.by_customer && summary.by_customer.length > 0">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('by_customer') }}</h3>
        <div class="bg-white rounded-lg shadow overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_principal') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_interest') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">#</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <template v-for="row in summary.by_customer" :key="row.customer_id">
                <tr class="hover:bg-gray-50 cursor-pointer" @click="toggleExpand(row.customer_id)">
                  <td class="px-6 py-4 text-sm text-gray-900 flex items-center">
                    <BaseIcon
                      :name="expanded[row.customer_id] ? 'ChevronDownIcon' : 'ChevronRightIcon'"
                      class="h-4 w-4 mr-2 text-gray-400"
                    />
                    {{ row.customer_name }}
                  </td>
                  <td class="px-6 py-4 text-sm text-right text-gray-500">{{ formatMoney(row.total_principal) }}</td>
                  <td class="px-6 py-4 text-sm text-right font-bold text-gray-900">{{ formatMoney(row.total_interest) }}</td>
                  <td class="px-6 py-4 text-sm text-right text-gray-400">{{ row.count }}</td>
                  <td class="px-6 py-4 text-sm text-right">
                    <BaseButton
                      variant="primary-outline"
                      size="sm"
                      @click.stop="viewCustomerDetails(row.customer_id)"
                    >
                      {{ t('generate_note') }}
                    </BaseButton>
                  </td>
                </tr>
                <!-- Expanded detail row -->
                <tr v-if="expanded[row.customer_id] && customerDetails[row.customer_id]">
                  <td colspan="5" class="px-8 py-2 bg-gray-50">
                    <table class="min-w-full text-xs">
                      <thead>
                        <tr>
                          <th class="px-3 py-1 text-left text-gray-500">{{ t('invoice_number') }}</th>
                          <th class="px-3 py-1 text-right text-gray-500">{{ t('principal') }}</th>
                          <th class="px-3 py-1 text-right text-gray-500">{{ t('days_overdue') }}</th>
                          <th class="px-3 py-1 text-right text-gray-500">{{ t('rate') }}</th>
                          <th class="px-3 py-1 text-right text-gray-500">{{ t('interest_amount') }}</th>
                          <th class="px-3 py-1 text-center text-gray-500">{{ t('status') }}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="detail in customerDetails[row.customer_id]" :key="detail.id">
                          <td class="px-3 py-1">{{ detail.invoice?.invoice_number || '-' }}</td>
                          <td class="px-3 py-1 text-right">{{ formatMoney(detail.principal_amount) }}</td>
                          <td class="px-3 py-1 text-right text-red-600">{{ detail.days_overdue }}</td>
                          <td class="px-3 py-1 text-right">{{ detail.annual_rate }}%</td>
                          <td class="px-3 py-1 text-right font-medium">{{ formatMoney(detail.interest_amount) }}</td>
                          <td class="px-3 py-1 text-center">
                            <span :class="statusBadgeClass(detail.status)" class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium">
                              {{ statusLabel(detail.status) }}
                            </span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Empty state for no customers -->
      <div
        v-else
        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16"
      >
        <BaseIcon name="CalculatorIcon" class="h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('no_calculations') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ t('no_calculations_description') }}</p>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import interestMessages from '@/scripts/admin/i18n/interest.js'

const router = useRouter()

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return interestMessages[locale]?.interest?.[key]
    || interestMessages['en']?.interest?.[key]
    || key
}

// State
const summary = ref(null)
const isLoading = ref(false)
const expanded = reactive({})
const customerDetails = reactive({})

// Methods
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function statusBadgeClass(status) {
  switch (status) {
    case 'calculated': return 'bg-amber-100 text-amber-800'
    case 'invoiced': return 'bg-blue-100 text-blue-800'
    case 'paid': return 'bg-green-100 text-green-800'
    case 'waived': return 'bg-gray-100 text-gray-600'
    default: return 'bg-gray-100 text-gray-700'
  }
}

function statusLabel(status) {
  switch (status) {
    case 'calculated': return t('status_calculated')
    case 'invoiced': return t('status_invoiced')
    case 'paid': return t('status_paid')
    case 'waived': return t('status_waived')
    default: return status
  }
}

async function toggleExpand(customerId) {
  if (expanded[customerId]) {
    expanded[customerId] = false
    return
  }

  expanded[customerId] = true

  if (!customerDetails[customerId]) {
    try {
      const response = await window.axios.get('/mk/interest', {
        params: { customer_id: customerId, limit: 'all' }
      })
      customerDetails[customerId] = response.data.data || []
    } catch {
      customerDetails[customerId] = []
    }
  }
}

async function viewCustomerDetails(customerId) {
  // Navigate to Index with customer filter
  router.push({ path: '/admin/interest', query: { customer_id: customerId } })
}

async function fetchSummary() {
  isLoading.value = true
  try {
    const response = await window.axios.get('/mk/interest/summary')
    summary.value = response.data.data || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load interest summary',
    })
  } finally {
    isLoading.value = false
  }
}

// Lifecycle
onMounted(() => {
  fetchSummary()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
