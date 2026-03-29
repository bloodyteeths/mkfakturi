<template>
  <BasePage>
    <BasePageHeader :title="t('create')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="../compensations" />
        <BaseBreadcrumbItem :title="t('create')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Step Indicator -->
    <div class="mb-8">
      <nav aria-label="Progress">
        <ol class="flex items-center">
          <li v-for="(stepInfo, index) in steps" :key="index" class="relative flex-1">
            <div class="flex items-center">
              <span
                :class="[
                  'relative flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium',
                  step > index + 1 ? 'bg-primary-600 text-white' :
                  step === index + 1 ? 'bg-primary-600 text-white ring-2 ring-primary-600 ring-offset-2' :
                  'bg-gray-200 text-gray-600'
                ]"
              >
                <BaseIcon v-if="step > index + 1" name="CheckIcon" class="h-4 w-4" />
                <span v-else>{{ index + 1 }}</span>
              </span>
              <span
                v-if="index < steps.length - 1"
                :class="[
                  'ml-2 flex-1 h-0.5',
                  step > index + 1 ? 'bg-primary-600' : 'bg-gray-200'
                ]"
              />
            </div>
            <p class="hidden sm:block mt-1 text-xs font-medium" :class="step >= index + 1 ? 'text-primary-600' : 'text-gray-500'">
              {{ stepInfo }}
            </p>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step 1: Select Counterparty -->
    <div v-if="step === 1" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-2">{{ t('step1_select') }}</h3>

      <!-- Workflow help box -->
      <div v-if="showHelp" class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
        <div class="flex items-start justify-between">
          <div class="flex items-start gap-3">
            <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-500 mt-0.5 shrink-0" />
            <div class="text-sm text-blue-700">
              <p class="font-semibold text-blue-900 mb-1">{{ t('help_title') }}</p>
              <p>{{ t('help_description') }}</p>
              <ul class="mt-2 space-y-1 list-disc list-inside text-xs">
                <li>{{ t('help_bilateral') }}</li>
                <li>{{ t('help_unilateral') }}</li>
              </ul>
            </div>
          </div>
          <button class="text-blue-400 hover:text-blue-600" @click="showHelp = false">
            <BaseIcon name="XMarkIcon" class="h-4 w-4" />
          </button>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Customer (receivables from) -->
        <BaseInputGroup :label="t('select_customer')" :help-text="t('help_customer')">
          <BaseMultiselect
            v-model="form.customer_id"
            :options="customers"
            :searchable="true"
            label="name"
            value-prop="id"
            :placeholder="t('select_customer')"
            :loading="isLoadingCustomers"
          />
        </BaseInputGroup>

        <!-- Supplier (payables to) -->
        <BaseInputGroup :label="t('select_supplier')" :help-text="t('help_supplier')">
          <BaseMultiselect
            v-model="form.supplier_id"
            :options="suppliers"
            :searchable="true"
            label="name"
            value-prop="id"
            :placeholder="t('select_supplier')"
            :loading="isLoadingSuppliers"
          />
        </BaseInputGroup>
      </div>

      <!-- Date & Type -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        <BaseInputGroup :label="t('date')" required>
          <BaseDatePicker
            v-model="form.compensation_date"
            :calendar-button="true"
            calendar-button-icon="CalendarDaysIcon"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('type')" :help-text="form.type === 'bilateral' ? t('help_bilateral') : t('help_unilateral')">
          <BaseMultiselect
            v-model="form.type"
            :options="typeOptions"
            label="label"
            value-prop="value"
            :searchable="false"
          />
        </BaseInputGroup>
      </div>

      <div class="flex justify-end mt-6">
        <BaseButton
          variant="primary"
          :disabled="!canProceedStep1"
          @click="goToStep2"
        >
          {{ t('next') }}
          <template #right="slotProps">
            <BaseIcon name="ArrowRightIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
      </div>
    </div>

    <!-- Step 2: Match Documents -->
    <div v-if="step === 2" class="space-y-6">
      <!-- Loading eligible docs -->
      <div v-if="isLoadingDocuments" class="bg-white rounded-lg shadow p-6">
        <div class="space-y-4">
          <div v-for="i in 4" :key="i" class="flex space-x-4 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-6"></div>
            <div class="h-4 bg-gray-200 rounded flex-1"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <template v-else>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Left: Receivables (invoices) -->
          <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 bg-blue-50 border-b border-blue-200 rounded-t-lg">
              <h3 class="text-sm font-semibold text-blue-800">
                {{ t('our_receivables') }}
              </h3>
              <p class="text-xs text-blue-600">
                {{ t('total_receivables') }}: {{ formatMoney(receivablesSelectedTotal) }}
              </p>
            </div>

            <div v-if="eligibleReceivables.length === 0" class="p-6 text-center text-sm text-gray-500">
              {{ t('no_eligible_documents') }}
            </div>

            <div v-else class="divide-y divide-gray-100">
              <div
                v-for="doc in eligibleReceivables"
                :key="`r-${doc.id}`"
                class="p-3 hover:bg-gray-50"
              >
                <div class="flex items-center space-x-3">
                  <input
                    type="checkbox"
                    :checked="isReceivableSelected(doc.id)"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    @change="toggleReceivable(doc)"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ doc.document_number }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(doc.document_date) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ t('amount') }}: {{ formatMoney(doc.due_amount) }}</p>
                    <div v-if="isReceivableSelected(doc.id)" class="mt-1">
                      <input
                        type="number"
                        :value="getReceivableOffset(doc.id) / 100"
                        min="0"
                        :max="doc.due_amount / 100"
                        step="0.01"
                        class="w-24 sm:w-28 text-right text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                        @input="updateReceivableOffset(doc.id, $event)"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Payables (bills) -->
          <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 bg-amber-50 border-b border-amber-200 rounded-t-lg">
              <h3 class="text-sm font-semibold text-amber-800">
                {{ t('our_payables') }}
              </h3>
              <p class="text-xs text-amber-600">
                {{ t('total_payables') }}: {{ formatMoney(payablesSelectedTotal) }}
              </p>
            </div>

            <div v-if="eligiblePayables.length === 0" class="p-6 text-center text-sm text-gray-500">
              {{ t('no_eligible_documents') }}
            </div>

            <div v-else class="divide-y divide-gray-100">
              <div
                v-for="doc in eligiblePayables"
                :key="`p-${doc.id}`"
                class="p-3 hover:bg-gray-50"
              >
                <div class="flex items-center space-x-3">
                  <input
                    type="checkbox"
                    :checked="isPayableSelected(doc.id)"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    @change="togglePayable(doc)"
                  />
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900">{{ doc.document_number }}</p>
                    <p class="text-xs text-gray-500">{{ formatDate(doc.document_date) }}</p>
                  </div>
                  <div class="text-right">
                    <p class="text-xs text-gray-500">{{ t('amount') }}: {{ formatMoney(doc.due_amount) }}</p>
                    <div v-if="isPayableSelected(doc.id)" class="mt-1">
                      <input
                        type="number"
                        :value="getPayableOffset(doc.id) / 100"
                        min="0"
                        :max="doc.due_amount / 100"
                        step="0.01"
                        class="w-24 sm:w-28 text-right text-sm border-gray-300 rounded focus:ring-primary-500 focus:border-primary-500"
                        @input="updatePayableOffset(doc.id, $event)"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Balance indicator -->
        <div
          :class="[
            'rounded-lg p-4 flex items-center justify-between',
            isBalanced ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200'
          ]"
        >
          <div class="flex items-center">
            <BaseIcon
              :name="isBalanced ? 'CheckCircleIcon' : 'ExclamationTriangleIcon'"
              :class="isBalanced ? 'text-green-600' : 'text-yellow-600'"
              class="h-5 w-5 mr-2"
            />
            <span :class="isBalanced ? 'text-green-700' : 'text-yellow-700'" class="text-sm font-medium">
              {{ isBalanced ? t('balanced') : t('not_balanced') }}
            </span>
          </div>
          <div class="text-right">
            <p class="text-sm">
              <span class="text-blue-700 font-medium">{{ t('total_receivables') }}: {{ formatMoney(receivablesSelectedTotal) }}</span>
              <span class="mx-2 text-gray-400">|</span>
              <span class="text-amber-700 font-medium">{{ t('total_payables') }}: {{ formatMoney(payablesSelectedTotal) }}</span>
            </p>
            <p class="text-sm font-bold mt-1" :class="isBalanced ? 'text-green-700' : 'text-yellow-700'">
              {{ t('offset_amount') }}: {{ formatMoney(offsetAmount) }}
            </p>
          </div>
        </div>

        <div class="flex justify-between">
          <BaseButton variant="primary-outline" @click="step = 1">
            <template #left="slotProps">
              <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
            </template>
            {{ t('back') }}
          </BaseButton>
          <div class="flex items-center space-x-3">
            <p v-if="canProceedStep2 && !isBalanced" class="text-xs text-yellow-600">
              {{ t('not_balanced_warning') }}
            </p>
            <BaseButton
              variant="primary"
              :disabled="!canProceedStep2"
              @click="step = 3"
            >
              {{ t('next') }}
              <template #right="slotProps">
                <BaseIcon name="ArrowRightIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
          </div>
        </div>
      </template>
    </div>

    <!-- Step 3: Review & Confirm -->
    <div v-if="step === 3" class="space-y-6">
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-900">{{ t('step3_review') }}</h3>
        </div>

        <div class="p-6 space-y-6">
          <!-- Summary -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 rounded-lg p-4">
              <p class="text-xs text-blue-600 uppercase font-medium">{{ t('total_receivables') }}</p>
              <p class="text-xl font-bold text-blue-800">{{ formatMoney(receivablesSelectedTotal) }}</p>
            </div>
            <div class="bg-amber-50 rounded-lg p-4">
              <p class="text-xs text-amber-600 uppercase font-medium">{{ t('total_payables') }}</p>
              <p class="text-xl font-bold text-amber-800">{{ formatMoney(payablesSelectedTotal) }}</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4">
              <p class="text-xs text-green-600 uppercase font-medium">{{ t('offset_amount') }}</p>
              <p class="text-xl font-bold text-green-800">{{ formatMoney(offsetAmount) }}</p>
            </div>
          </div>

          <!-- Receivables list -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ t('our_receivables') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_number') }}</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_date') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('document_total') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_to_offset') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in selectedReceivables" :key="item.document_id">
                  <td class="px-4 py-2 text-sm text-gray-900">{{ item.document_number }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ formatDate(item.document_date) }}</td>
                  <td class="px-4 py-2 text-sm text-right text-gray-500">{{ formatMoney(item.due_amount) }}</td>
                  <td class="px-4 py-2 text-sm text-right font-medium text-blue-700">{{ formatMoney(item.amount_offset) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Payables list -->
          <div>
            <h4 class="text-sm font-semibold text-gray-700 mb-2">{{ t('our_payables') }}</h4>
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_number') }}</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('document_date') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('document_total') }}</th>
                  <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_to_offset') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in selectedPayables" :key="item.document_id">
                  <td class="px-4 py-2 text-sm text-gray-900">{{ item.document_number }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ formatDate(item.document_date) }}</td>
                  <td class="px-4 py-2 text-sm text-right text-gray-500">{{ formatMoney(item.due_amount) }}</td>
                  <td class="px-4 py-2 text-sm text-right font-medium text-amber-700">{{ formatMoney(item.amount_offset) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Notes -->
          <BaseInputGroup :label="t('notes')">
            <textarea
              v-model="form.notes"
              rows="3"
              class="w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 text-sm"
              :placeholder="t('notes_placeholder')"
            />
          </BaseInputGroup>
        </div>
      </div>

      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="step = 2">
          <template #left="slotProps">
            <BaseIcon name="ArrowLeftIcon" :class="slotProps.class" />
          </template>
          {{ t('back') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          :loading="isSaving"
          @click="saveCompensation"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckIcon" :class="slotProps.class" />
          </template>
          {{ t('save_draft') }}
        </BaseButton>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import compensationMessages from '@/scripts/admin/i18n/compensations.js'

const router = useRouter()
const route = useRoute()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return compensationMessages[locale]?.compensations?.[key]
    || compensationMessages['en']?.compensations?.[key]
    || key
}

// State
const step = ref(1)
const isSaving = ref(false)
const isLoadingCustomers = ref(false)
const isLoadingSuppliers = ref(false)
const isLoadingDocuments = ref(false)
const showHelp = ref(true)

const customers = ref([])
const suppliers = ref([])
const eligibleReceivables = ref([])
const eligiblePayables = ref([])

// Selected items with their offsets
const receivableSelections = ref([])  // [{ document_id, document_type, document_number, document_date, due_amount, amount_offset }]
const payableSelections = ref([])

function getLocalDateString(date = new Date()) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const form = reactive({
  customer_id: null,
  supplier_id: null,
  compensation_date: getLocalDateString(),
  type: 'bilateral',
  notes: '',
})

const steps = [t('step1_select'), t('step2_match'), t('step3_review')]

const typeOptions = [
  { value: 'bilateral', label: t('bilateral') },
  { value: 'unilateral', label: t('unilateral') },
]

// Computed
const canProceedStep1 = computed(() => {
  return (form.customer_id || form.supplier_id) && form.compensation_date
})

const receivablesSelectedTotal = computed(() => {
  return receivableSelections.value.reduce((sum, s) => sum + s.amount_offset, 0)
})

const payablesSelectedTotal = computed(() => {
  return payableSelections.value.reduce((sum, s) => sum + s.amount_offset, 0)
})

const offsetAmount = computed(() => {
  return Math.min(receivablesSelectedTotal.value, payablesSelectedTotal.value)
})

const isBalanced = computed(() => {
  return receivablesSelectedTotal.value > 0
    && payablesSelectedTotal.value > 0
    && receivablesSelectedTotal.value === payablesSelectedTotal.value
})

const canProceedStep2 = computed(() => {
  return receivableSelections.value.length > 0
    && payableSelections.value.length > 0
    && offsetAmount.value > 0
})

const selectedReceivables = computed(() => receivableSelections.value)
const selectedPayables = computed(() => payableSelections.value)

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = localeMap[locale] || 'mk-MK'

// Methods
function formatMoney(cents) {
  if (!cents && cents !== 0) return '-'
  return new Intl.NumberFormat(formattedLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString(formattedLocale, { year: 'numeric', month: '2-digit', day: '2-digit' })
}

function isReceivableSelected(docId) {
  return receivableSelections.value.some(s => s.document_id === docId)
}

function isPayableSelected(docId) {
  return payableSelections.value.some(s => s.document_id === docId)
}

function getReceivableOffset(docId) {
  const sel = receivableSelections.value.find(s => s.document_id === docId)
  return sel ? sel.amount_offset : 0
}

function getPayableOffset(docId) {
  const sel = payableSelections.value.find(s => s.document_id === docId)
  return sel ? sel.amount_offset : 0
}

function toggleReceivable(doc) {
  const idx = receivableSelections.value.findIndex(s => s.document_id === doc.id)
  if (idx >= 0) {
    receivableSelections.value.splice(idx, 1)
  } else {
    receivableSelections.value.push({
      document_id: doc.id,
      document_type: doc.document_type,
      document_number: doc.document_number,
      document_date: doc.document_date,
      due_amount: doc.due_amount,
      amount_offset: doc.due_amount,
      side: 'receivable',
    })
  }
}

function togglePayable(doc) {
  const idx = payableSelections.value.findIndex(s => s.document_id === doc.id)
  if (idx >= 0) {
    payableSelections.value.splice(idx, 1)
  } else {
    payableSelections.value.push({
      document_id: doc.id,
      document_type: doc.document_type,
      document_number: doc.document_number,
      document_date: doc.document_date,
      due_amount: doc.due_amount,
      amount_offset: doc.due_amount,
      side: 'payable',
    })
  }
}

function updateReceivableOffset(docId, event) {
  const sel = receivableSelections.value.find(s => s.document_id === docId)
  if (sel) {
    const cents = Math.round(parseFloat(event.target.value || 0) * 100)
    sel.amount_offset = Math.max(0, Math.min(cents, sel.due_amount))
  }
}

function updatePayableOffset(docId, event) {
  const sel = payableSelections.value.find(s => s.document_id === docId)
  if (sel) {
    const cents = Math.round(parseFloat(event.target.value || 0) * 100)
    sel.amount_offset = Math.max(0, Math.min(cents, sel.due_amount))
  }
}

async function fetchCustomers() {
  isLoadingCustomers.value = true
  try {
    const response = await window.axios.get('/customers', { params: { limit: 'all' } })
    customers.value = response.data?.customers?.data || response.data?.data || []
  } catch {
    customers.value = []
  } finally {
    isLoadingCustomers.value = false
  }
}

async function fetchSuppliers() {
  isLoadingSuppliers.value = true
  try {
    const response = await window.axios.get('/suppliers', { params: { limit: 'all' } })
    suppliers.value = response.data?.suppliers?.data || response.data?.data || []
  } catch {
    suppliers.value = []
  } finally {
    isLoadingSuppliers.value = false
  }
}

async function goToStep2() {
  step.value = 2
  isLoadingDocuments.value = true

  try {
    const params = {}
    if (form.customer_id) params.customer_id = form.customer_id
    if (form.supplier_id) params.supplier_id = form.supplier_id

    const response = await window.axios.get('/compensations/eligible-documents', { params })
    eligibleReceivables.value = response.data?.data?.receivables || []
    eligiblePayables.value = response.data?.data?.payables || []

    // Reset selections
    receivableSelections.value = []
    payableSelections.value = []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load documents',
    })
  } finally {
    isLoadingDocuments.value = false
  }
}

async function saveCompensation() {
  isSaving.value = true

  const items = [
    ...receivableSelections.value.map(s => ({
      side: 'receivable',
      document_type: s.document_type,
      document_id: s.document_id,
      amount_offset: s.amount_offset,
    })),
    ...payableSelections.value.map(s => ({
      side: 'payable',
      document_type: s.document_type,
      document_id: s.document_id,
      amount_offset: s.amount_offset,
    })),
  ]

  let counterpartyType = 'both'
  if (form.customer_id && !form.supplier_id) counterpartyType = 'customer'
  if (!form.customer_id && form.supplier_id) counterpartyType = 'supplier'

  try {
    const response = await window.axios.post('/compensations', {
      counterparty_type: counterpartyType,
      customer_id: form.customer_id,
      supplier_id: form.supplier_id,
      compensation_date: form.compensation_date,
      type: form.type,
      notes: form.notes,
      items,
    })

    notificationStore.showNotification({
      type: 'success',
      message: response.data?.message || t('created_success') || 'Compensation created',
    })

    const compId = response.data?.data?.id
    if (compId) {
      router.push({ path: `/admin/compensations/${compId}` })
    } else {
      router.push({ path: '/admin/compensations' })
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_creating') || 'Failed to create compensation',
    })
  } finally {
    isSaving.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await Promise.all([fetchCustomers(), fetchSuppliers()])

  // Pre-select from query params (from opportunities)
  if (route.query.customer_id) {
    form.customer_id = parseInt(route.query.customer_id)
  }
  if (route.query.supplier_id) {
    form.supplier_id = parseInt(route.query.supplier_id)
  }
})
</script>

<!-- CLAUDE-CHECKPOINT -->
