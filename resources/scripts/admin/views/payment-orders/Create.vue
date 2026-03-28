<template>
  <BasePage>
    <BasePageHeader :title="t('new_batch')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="/admin/payment-orders" />
        <BaseBreadcrumbItem :title="t('new_batch')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Settings Panel -->
    <div class="mb-6 rounded-lg bg-white p-6 shadow">
      <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('order_settings', 'Order Settings') }}</h3>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <BaseInputGroup :label="t('execution_date')" required>
          <BaseDatePicker v-model="form.batch_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('format', 'Format')" required>
          <select
            v-model="form.format"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="pp30">{{ t('pp30') }}</option>
            <option value="pp50">{{ t('pp50') }}</option>
            <option value="sepa_sct">{{ t('sepa') }}</option>
            <option value="csv">CSV</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('urgency')">
          <select
            v-model="form.urgency"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="redovno">{{ t('urgency_regular') }}</option>
            <option value="itno">{{ t('urgency_urgent') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('payment_code')">
          <select
            v-model="form.payment_code"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option value="">-</option>
            <option value="110">110 - {{ t('payment_code_110') }}</option>
            <option value="120">120 - {{ t('payment_code_120') }}</option>
            <option value="130">130 - {{ t('payment_code_130') }}</option>
            <option value="140">140 - {{ t('payment_code_140') }}</option>
            <option value="220">220 - {{ t('payment_code_220') }}</option>
            <option value="450">450 - {{ t('payment_code_450') }}</option>
            <option value="460">460 - {{ t('payment_code_460') }}</option>
            <option value="470">470 - {{ t('payment_code_470') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('bank_account', 'Bank Account')">
          <select
            v-model="form.bank_account_id"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          >
            <option :value="null">{{ t('select', 'Select...') }}</option>
            <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
              {{ acc.account_name || acc.iban || acc.account_number }}
            </option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('notes', 'Notes')">
          <input
            v-model="form.notes"
            type="text"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            :placeholder="t('notes_placeholder', 'Optional notes...')"
          />
        </BaseInputGroup>
      </div>

      <!-- PP50 Public Revenue Fields -->
      <div v-if="form.format === 'pp50'" class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4">
        <h4 class="mb-3 text-sm font-medium text-amber-800">{{ t('pp50_fields') }}</h4>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
          <BaseInputGroup :label="t('tax_number')">
            <input
              v-model="form.tax_number"
              type="text"
              maxlength="13"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('revenue_code')">
            <input
              v-model="form.revenue_code"
              type="text"
              maxlength="10"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('program_code')">
            <input
              v-model="form.program_code"
              type="text"
              maxlength="10"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('municipality_code')">
            <input
              v-model="form.municipality_code"
              type="text"
              maxlength="10"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('approval_reference')">
            <input
              v-model="form.approval_reference"
              type="text"
              maxlength="50"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
          </BaseInputGroup>
        </div>
      </div>
    </div>

    <!-- Quick Select Buttons -->
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <BaseButton variant="danger-outline" size="sm" @click="selectOverdue">
        <template #left="slotProps">
          <BaseIcon name="ExclamationTriangleIcon" :class="slotProps.class" />
        </template>
        {{ t('select_overdue', 'Select All Overdue') }}
      </BaseButton>
      <BaseButton variant="warning-outline" size="sm" @click="selectDueThisWeek">
        <template #left="slotProps">
          <BaseIcon name="ClockIcon" :class="slotProps.class" />
        </template>
        {{ t('select_due_week', 'Select All Due This Week') }}
      </BaseButton>
      <BaseButton variant="primary-outline" size="sm" @click="selectDueThisMonth">
        <template #left="slotProps">
          <BaseIcon name="CalendarDaysIcon" :class="slotProps.class" />
        </template>
        {{ t('select_due_month') }}
      </BaseButton>
      <BaseButton v-if="selectedBillIds.length > 0" variant="primary-outline" size="sm" @click="clearSelection">
        {{ t('clear', 'Clear') }} ({{ selectedBillIds.length }})
      </BaseButton>

      <div class="ml-auto flex items-center gap-2">
        <label class="text-sm text-gray-600">{{ t('supplier_filter', 'Filter by supplier') }}:</label>
        <input
          v-model="supplierFilter"
          type="text"
          class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
          :placeholder="t('search', 'Search...')"
        />
        <span v-if="hiddenSelectionCount > 0" class="ml-2 text-xs text-amber-600">
          (+ {{ hiddenSelectionCount }} {{ t('hidden_selected', 'hidden') }})
        </span>
      </div>
    </div>

    <!-- Bill Selector Table -->
    <div class="rounded-lg bg-white shadow overflow-hidden">
      <div v-if="isLoadingBills" class="p-6">
        <div v-for="i in 5" :key="i" class="mb-4 flex animate-pulse space-x-4">
          <div class="h-4 w-6 rounded bg-gray-200"></div>
          <div class="h-4 w-40 rounded bg-gray-200"></div>
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
        </div>
      </div>

      <template v-else-if="filteredBills.length > 0">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="w-12 px-4 py-3">
                  <input
                    type="checkbox"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    :checked="allFilteredSelected"
                    @change="toggleSelectAll"
                  />
                </th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('supplier', 'Supplier') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('bill_number', 'Bill Number') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('description') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('date') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('due_date', 'Due Date') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('due_amount', 'Due Amount') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('status') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="bill in filteredBills"
                :key="bill.id"
                class="hover:bg-gray-50"
                :class="{
                  'bg-red-50': bill.is_overdue,
                  'bg-yellow-50': bill.is_due_soon && !bill.is_overdue,
                }"
              >
                <td class="px-4 py-3">
                  <input
                    type="checkbox"
                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    :value="bill.id"
                    :checked="selectedBillIds.includes(bill.id)"
                    @change="toggleBill(bill.id)"
                  />
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">
                  <div class="font-medium">{{ bill.supplier?.name }}</div>
                  <div v-if="bill.supplier?.iban" class="text-xs text-gray-500">{{ bill.supplier.iban }}</div>
                  <div v-else class="text-xs text-red-500">{{ t('no_iban', 'No IBAN') }}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-primary-600">
                  {{ bill.bill_number }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 max-w-[200px] truncate" :title="bill.notes || bill.description || ''">
                  {{ truncate(bill.notes || bill.description, 30) || t('no_description') }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  {{ formatDate(bill.bill_date) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                  {{ formatDate(bill.due_date) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">
                  {{ formatMoney(bill.due_amount) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                  <span
                    v-if="bill.is_overdue"
                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700"
                  >
                    {{ t('overdue') }}
                  </span>
                  <span
                    v-else-if="bill.is_due_soon"
                    class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-700"
                  >
                    {{ t('due_this_week') }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700"
                  >
                    {{ bill.paid_status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <div v-else class="p-12 text-center">
        <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('no_payable_bills', 'No payable bills found') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ t('no_payable_bills_hint', 'All bills have been paid or none match the filters.') }}</p>
      </div>
    </div>

    <!-- Running Total Footer -->
    <div v-if="selectedBillIds.length > 0" class="mt-6 rounded-lg border border-primary-200 bg-primary-50 p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-primary-800">
            {{ t('selected_bills', 'Selected bills') }}: {{ selectedBillIds.length }}
          </p>
          <p class="text-2xl font-bold text-primary-900">
            {{ t('total') }}: {{ formatMoney(selectedTotal) }}
          </p>
        </div>
        <BaseButton variant="primary" :loading="isCreating" :disabled="selectedBillIds.length === 0 || !form.batch_date" @click="createBatch">
          <template #left="slotProps">
            <BaseIcon name="BanknotesIcon" :class="slotProps.class" />
          </template>
          {{ t('create_order', 'Create Payment Order') }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/payment-orders.js'

const router = useRouter()
const notificationStore = useNotificationStore()

const currentLocale = ref(document.documentElement.lang || 'mk')
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = computed(() => localeMap[currentLocale.value] || 'mk-MK')

// Watch for locale changes
const observer = new MutationObserver(() => {
  currentLocale.value = document.documentElement.lang || 'mk'
})
onMounted(() => {
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] })
  loadPayableBills()
  loadBankAccounts()
})
onBeforeUnmount(() => observer.disconnect())

function t(key) {
  return poMessages[currentLocale.value]?.payment_orders?.[key]
    || poMessages['en']?.payment_orders?.[key]
    || key
}

const isLoadingBills = ref(false)
const isCreating = ref(false)
const bills = ref([])
const bankAccounts = ref([])
const selectedBillIds = ref([])
const supplierFilter = ref('')

const form = ref({
  batch_date: new Date().toISOString().slice(0, 10),
  format: 'pp30',
  urgency: 'redovno',
  payment_code: '',
  bank_account_id: null,
  notes: '',
  // PP50 fields
  tax_number: '',
  revenue_code: '',
  program_code: '',
  municipality_code: '',
  approval_reference: '',
})

const filteredBills = computed(() => {
  if (!supplierFilter.value) return bills.value
  const search = supplierFilter.value.toLowerCase()
  return bills.value.filter(
    (b) =>
      b.supplier?.name?.toLowerCase().includes(search) ||
      b.bill_number?.toLowerCase().includes(search)
  )
})

const allFilteredSelected = computed(() => {
  if (filteredBills.value.length === 0) return false
  return filteredBills.value.every((b) => selectedBillIds.value.includes(b.id))
})

const selectedTotal = computed(() => {
  return bills.value
    .filter((b) => selectedBillIds.value.includes(b.id))
    .reduce((sum, b) => sum + (b.due_amount || 0), 0)
})

const hiddenSelectionCount = computed(() => {
  const visibleIds = new Set(filteredBills.value.map(b => b.id))
  return selectedBillIds.value.filter(id => !visibleIds.has(id)).length
})

async function loadPayableBills() {
  isLoadingBills.value = true
  try {
    const response = await window.axios.get('/payment-orders/payable-bills')
    bills.value = response.data?.data || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load bills',
    })
  } finally {
    isLoadingBills.value = false
  }
}

async function loadBankAccounts() {
  try {
    const response = await window.axios.get('/banking/accounts')
    bankAccounts.value = response.data?.data || response.data || []
  } catch {
    // Bank accounts may not be available (no tier access)
  }
}

function toggleBill(billId) {
  const idx = selectedBillIds.value.indexOf(billId)
  if (idx > -1) {
    selectedBillIds.value.splice(idx, 1)
  } else {
    selectedBillIds.value.push(billId)
  }
}

function toggleSelectAll() {
  if (allFilteredSelected.value) {
    const filteredIds = filteredBills.value.map((b) => b.id)
    selectedBillIds.value = selectedBillIds.value.filter((id) => !filteredIds.includes(id))
  } else {
    const newIds = filteredBills.value.map((b) => b.id)
    selectedBillIds.value = [...new Set([...selectedBillIds.value, ...newIds])]
  }
}

function selectOverdue() {
  const overdueIds = bills.value.filter((b) => b.is_overdue).map((b) => b.id)
  selectedBillIds.value = [...new Set([...selectedBillIds.value, ...overdueIds])]
}

function selectDueThisWeek() {
  const dueIds = bills.value.filter((b) => b.is_due_soon || b.is_overdue).map((b) => b.id)
  selectedBillIds.value = [...new Set([...selectedBillIds.value, ...dueIds])]
}

function selectDueThisMonth() {
  const now = new Date()
  const endOfMonth = new Date(now.getFullYear(), now.getMonth() + 1, 0)
  const dueIds = bills.value.filter((b) => {
    if (!b.due_date) return false
    const due = new Date(b.due_date)
    return due <= endOfMonth
  }).map((b) => b.id)
  selectedBillIds.value = [...new Set([...selectedBillIds.value, ...dueIds])]
}

function clearSelection() {
  selectedBillIds.value = []
}

function truncate(str, len) {
  if (!str) return ''
  return str.length > len ? str.substring(0, len) + '...' : str
}

async function createBatch() {
  if (selectedBillIds.value.length === 0) {
    notificationStore.showNotification({
      type: 'error',
      message: t('select_at_least_one', 'Please select at least one bill'),
    })
    return
  }

  if (!form.value.batch_date) {
    notificationStore.showNotification({
      type: 'error',
      message: t('date_required', 'Execution date is required'),
    })
    return
  }

  isCreating.value = true
  try {
    const payload = {
      batch_date: form.value.batch_date,
      format: form.value.format,
      urgency: form.value.urgency,
      bank_account_id: form.value.bank_account_id,
      notes: form.value.notes || null,
      bill_ids: selectedBillIds.value,
    }

    if (form.value.payment_code) {
      payload.payment_code = form.value.payment_code
    }

    if (form.value.format === 'pp50') {
      payload.tax_number = form.value.tax_number || null
      payload.revenue_code = form.value.revenue_code || null
      payload.program_code = form.value.program_code || null
      payload.municipality_code = form.value.municipality_code || null
      payload.approval_reference = form.value.approval_reference || null
    }

    const response = await window.axios.post('/payment-orders', payload)
    const batch = response.data?.data
    const warnings = response.data?.warnings

    if (warnings && warnings.length > 0) {
      notificationStore.showNotification({
        type: 'warning',
        message: warnings.join(' '),
      })
    } else {
      notificationStore.showNotification({
        type: 'success',
        message: t('created_success', 'Payment order created successfully'),
      })
    }

    router.push(`/admin/payment-orders/${batch.id}`)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_creating') || 'Failed to create payment order',
    })
  } finally {
    isCreating.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  return sign + new Intl.NumberFormat(formattedLocale.value, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' \u0434\u0435\u043d.'
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(formattedLocale.value, { year: 'numeric', month: '2-digit', day: '2-digit' })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
