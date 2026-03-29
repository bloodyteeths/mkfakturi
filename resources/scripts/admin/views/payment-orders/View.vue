<template>
  <BasePage>
    <BasePageHeader :title="`${t('title')} #${batch?.batch_number || ''}`">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="t('home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="/admin/payment-orders" />
        <BaseBreadcrumbItem :title="batch?.batch_number || '...'" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex flex-col sm:flex-row flex-wrap gap-2 w-full sm:w-auto">
          <!-- Edit Button (draft only) -->
          <BaseButton
            v-if="canEdit"
            variant="primary-outline"
            class="w-full sm:w-auto"
            @click="toggleEditMode"
          >
            <template #left="slotProps">
              <BaseIcon name="PencilSquareIcon" :class="slotProps.class" />
            </template>
            {{ isEditing ? t('go_back') : t('edit_batch') }}
          </BaseButton>

          <!-- Primary Next-Step Action -->
          <BaseButton
            v-if="canApprove && !isEditing"
            variant="primary"
            class="w-full sm:w-auto"
            :loading="isApproving"
            @click="approveBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckCircleIcon" :class="slotProps.class" />
            </template>
            {{ t('approve') }}
          </BaseButton>

          <BaseButton
            v-if="canExport && !isEditing"
            variant="primary"
            class="w-full sm:w-auto"
            :loading="isExporting"
            @click="exportBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            {{ t('export_file') }}
          </BaseButton>

          <BaseButton
            v-if="canConfirm && !isEditing"
            variant="success"
            class="w-full sm:w-auto"
            :loading="isConfirming"
            @click="confirmBatch"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckBadgeIcon" :class="slotProps.class" />
            </template>
            {{ t('confirm_payment') }}
          </BaseButton>

          <!-- More Actions Dropdown -->
          <div v-if="batch && !isEditing" class="relative w-full sm:w-auto">
            <BaseButton
              variant="primary-outline"
              class="w-full sm:w-auto"
              @click="showMoreMenu = !showMoreMenu"
            >
              <template #left="slotProps">
                <BaseIcon name="EllipsisVerticalIcon" :class="slotProps.class" />
              </template>
              <span class="sm:hidden">{{ t('more_actions') }}</span>
            </BaseButton>

            <div
              v-if="showMoreMenu"
              class="absolute right-0 z-20 mt-1 w-52 rounded-md border border-gray-200 bg-white py-1 shadow-lg"
            >
              <!-- PP30 PDF -->
              <button
                v-if="canExportPp30"
                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                :disabled="isDownloadingPp30"
                @click="downloadPp30Pdf(); showMoreMenu = false"
              >
                <BaseIcon name="PrinterIcon" class="h-4 w-4 text-gray-400" />
                {{ t('pp30_pdf') }}
              </button>

              <!-- PP50 PDF -->
              <button
                v-if="canExportPp50"
                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                :disabled="isDownloadingPp50"
                @click="downloadPp50Pdf(); showMoreMenu = false"
              >
                <BaseIcon name="PrinterIcon" class="h-4 w-4 text-gray-400" />
                {{ t('download_pp50') }}
              </button>

              <!-- Print -->
              <button
                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                @click="printPage(); showMoreMenu = false"
              >
                <BaseIcon name="PrinterIcon" class="h-4 w-4 text-gray-400" />
                {{ t('print') }}
              </button>

              <!-- Duplicate -->
              <button
                class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100"
                :disabled="isDuplicating"
                @click="duplicateBatch(); showMoreMenu = false"
              >
                <BaseIcon name="DocumentDuplicateIcon" class="h-4 w-4 text-gray-400" />
                {{ t('duplicate_batch') }}
              </button>

              <!-- Cancel Order -->
              <template v-if="canCancel">
                <div class="my-1 border-t border-gray-100"></div>
                <button
                  class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                  :disabled="isCancelling"
                  @click="cancelBatch(); showMoreMenu = false"
                >
                  <BaseIcon name="XCircleIcon" class="h-4 w-4 text-red-400" />
                  {{ t('cancel_order') }}
                </button>
              </template>
            </div>
          </div>
        </div>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="rounded-lg bg-white p-6 shadow">
      <div v-for="i in 4" :key="i" class="mb-4 flex animate-pulse space-x-4">
        <div class="h-4 w-32 rounded bg-gray-200"></div>
        <div class="h-4 w-48 rounded bg-gray-200"></div>
      </div>
    </div>

    <template v-else-if="batch">
      <!-- Edit Mode -->
      <div v-if="isEditing" class="mb-6 rounded-lg border-2 border-primary-300 bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('edit_batch') }}</h3>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-5">
          <BaseInputGroup :label="t('execution_date')">
            <BaseDatePicker v-model="editForm.batch_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <BaseInputGroup :label="t('format')">
            <select v-model="editForm.format" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
              <option value="pp30">{{ t('pp30') }}</option>
              <option value="pp50">{{ t('pp50') }}</option>
              <option value="sepa_sct">{{ t('sepa') }}</option>
              <option value="csv">CSV</option>
            </select>
          </BaseInputGroup>
          <BaseInputGroup :label="t('urgency')">
            <select v-model="editForm.urgency" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
              <option value="redovno">{{ t('urgency_regular') }}</option>
              <option value="itno">{{ t('urgency_urgent') }}</option>
            </select>
          </BaseInputGroup>
          <BaseInputGroup :label="t('bank_account')">
            <select v-model="editForm.bank_account_id" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
              <option :value="null">{{ t('select') }}</option>
              <option v-for="acc in bankAccounts" :key="acc.id" :value="acc.id">
                {{ acc.account_name || acc.iban }}
              </option>
            </select>
          </BaseInputGroup>
          <BaseInputGroup :label="t('notes')">
            <input v-model="editForm.notes" type="text" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
          </BaseInputGroup>
        </div>
        <div class="mt-4 flex justify-end">
          <BaseButton variant="primary" :loading="isSaving" @click="saveBatch">
            {{ t('save_changes') }}
          </BaseButton>
        </div>
      </div>

      <!-- Batch Header Card -->
      <div v-if="!isEditing" class="mb-6 rounded-lg bg-white p-6 shadow">
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7">
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('batch_number') }}</p>
            <p class="text-sm font-bold text-gray-900">{{ batch.batch_number }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('execution_date') }}</p>
            <p class="text-sm text-gray-900">{{ formatDate(batch.batch_date) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('format', 'Format') }}</p>
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800">
              {{ formatLabel(batch.format) }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('urgency') }}</p>
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="batch.urgency === 'itno' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'"
            >
              {{ batch.urgency === 'itno' ? t('urgency_urgent') : t('urgency_regular') }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('status') }}</p>
            <span
              class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              :class="statusClass(batch.status)"
            >
              {{ statusLabel(batch.status) }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('items', 'Items') }}</p>
            <p class="text-sm text-gray-900">{{ batch.item_count }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('total') }}</p>
            <p class="text-lg font-bold text-primary-600">{{ formatMoney(batch.total_amount) }}</p>
          </div>
        </div>

        <!-- Status Pipeline -->
        <div class="mt-6 border-t border-gray-200 pt-4">
          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
            <div v-for="(step, idx) in statusPipeline" :key="step.key" class="flex items-center sm:flex-col sm:items-center">
              <div class="flex items-center sm:flex-col">
                <div
                  class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold"
                  :class="stepClass(step.key)"
                >
                  {{ idx + 1 }}
                </div>
                <span class="ml-2 text-xs sm:ml-0 sm:mt-1 sm:text-center" :class="isStepActive(step.key) ? 'font-medium text-gray-900' : 'text-gray-400'">
                  {{ step.label }}
                </span>
              </div>
              <!-- Step hint for current step -->
              <span
                v-if="isCurrentStep(step.key)"
                class="ml-2 text-[10px] text-primary-500 sm:ml-0 sm:mt-0.5 sm:text-center"
              >
                {{ stepHint(step.key) }}
              </span>
              <!-- Connector line (horizontal on desktop, vertical on mobile) -->
              <div v-if="idx < statusPipeline.length - 1" class="ml-3 hidden h-px w-8 bg-gray-300 sm:mx-3 sm:block sm:w-12"></div>
            </div>
          </div>
        </div>

        <!-- PP50 Fields Display -->
        <div v-if="batch.format === 'pp50' && hasPp50Data" class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-3">
          <h4 class="mb-2 text-xs font-medium uppercase text-amber-800">{{ t('pp50_fields') }}</h4>
          <div class="grid grid-cols-1 gap-2 text-sm sm:grid-cols-2 lg:grid-cols-5">
            <div v-if="pp50DataFromItems.tax_number" :title="t('tax_number_hint')">
              <span class="text-xs text-gray-500">{{ t('tax_number') }}:</span>
              <p class="font-medium">{{ pp50DataFromItems.tax_number }}</p>
            </div>
            <div v-if="pp50DataFromItems.revenue_code" :title="t('revenue_code_hint')">
              <span class="text-xs text-gray-500">{{ t('revenue_code') }}:</span>
              <p class="font-medium">{{ pp50DataFromItems.revenue_code }}</p>
            </div>
            <div v-if="pp50DataFromItems.program_code" :title="t('program_code_hint')">
              <span class="text-xs text-gray-500">{{ t('program_code') }}:</span>
              <p class="font-medium">{{ pp50DataFromItems.program_code }}</p>
            </div>
            <div v-if="pp50DataFromItems.municipality_code" :title="t('municipality_code_hint')">
              <span class="text-xs text-gray-500">{{ t('municipality_code') }}:</span>
              <p class="font-medium">{{ pp50DataFromItems.municipality_code }}</p>
            </div>
            <div v-if="pp50DataFromItems.approval_reference" :title="t('approval_reference_hint')">
              <span class="text-xs text-gray-500">{{ t('approval_reference') }}:</span>
              <p class="font-medium">{{ pp50DataFromItems.approval_reference }}</p>
            </div>
          </div>
        </div>

        <!-- Additional Details -->
        <div v-if="batch.notes" class="mt-4 border-t border-gray-200 pt-4">
          <p class="text-xs font-medium uppercase text-gray-500">{{ t('notes', 'Notes') }}</p>
          <p class="mt-1 text-sm text-gray-700">{{ batch.notes }}</p>
        </div>

        <div class="mt-4 flex flex-col gap-2 text-xs text-gray-500 sm:flex-row sm:gap-6">
          <span v-if="batch.created_by">
            {{ t('created_by', 'Created by') }}: {{ batch.created_by?.name || '-' }}
          </span>
          <span v-if="batch.approved_by">
            {{ t('approved_by', 'Approved by') }}: {{ batch.approved_by?.name || '-' }}
            ({{ formatDate(batch.approved_at) }})
          </span>
          <span v-if="batch.bank_account">
            {{ t('bank_account', 'Bank Account') }}: {{ batch.bank_account?.account_name || batch.bank_account?.iban }}
          </span>
        </div>
      </div>

      <!-- Confirmation Banner -->
      <div v-if="pendingAction" class="mb-4 rounded-lg border-2 p-4" :class="pendingAction === 'confirm' ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50'">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <p class="text-sm font-medium" :class="pendingAction === 'confirm' ? 'text-green-800' : 'text-red-800'">
            {{ pendingAction === 'confirm' ? t('confirm_warning') : t('cancel_warning') }}
          </p>
          <div class="flex gap-2">
            <BaseButton variant="primary-outline" size="sm" @click="dismissAction">
              {{ t('go_back') }}
            </BaseButton>
            <BaseButton
              :variant="pendingAction === 'confirm' ? 'success' : 'danger'"
              size="sm"
              :loading="pendingAction === 'confirm' ? isConfirming : isCancelling"
              @click="pendingAction === 'confirm' ? confirmBatch() : cancelBatch()"
            >
              {{ pendingAction === 'confirm' ? t('confirm_payment') : t('cancel_order') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Items Table -->
      <div class="rounded-lg bg-white shadow overflow-hidden">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
          <h3 class="text-lg font-medium text-gray-900">{{ t('payment_items', 'Payment Items') }}</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('creditor') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">IBAN</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('bill_number', 'Bill') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('description', 'Description') }}</th>
                <th v-if="hasPaymentCodes" class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('payment_code') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('amount') }}</th>
                <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">{{ t('status') }}</th>
                <th v-if="isEditing" class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(item, idx) in (batch.items || [])" :key="item.id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">{{ idx + 1 }}</td>
                <td class="px-4 py-3 text-sm">
                  <div class="font-medium text-gray-900">{{ item.creditor_name }}</div>
                  <div v-if="item.creditor_bank_name" class="text-xs text-gray-500">{{ item.creditor_bank_name }}</div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 font-mono">
                  {{ item.creditor_iban || '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-sm">
                  <span v-if="item.bill" class="text-primary-600 font-medium">{{ item.bill.bill_number }}</span>
                  <span v-else class="text-gray-400">-</span>
                </td>
                <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">
                  {{ item.description || '-' }}
                </td>
                <td v-if="hasPaymentCodes" class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-500">
                  {{ item.payment_code || '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">
                  {{ formatMoney(item.amount) }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                  <span
                    class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="itemStatusClass(item.status)"
                  >
                    {{ itemStatusLabel(item.status) }}
                  </span>
                  <div v-if="item.reconciled_at" class="text-xs text-green-600 mt-0.5">
                    {{ t('reconciled', 'Reconciled') }}
                  </div>
                </td>
                <td v-if="isEditing" class="whitespace-nowrap px-4 py-3 text-center text-sm">
                  <button class="text-red-500 hover:text-red-700" @click="removeItem(item.id)">
                    {{ t('remove_item') }}
                  </button>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-100 font-semibold">
              <tr>
                <td :colspan="hasPaymentCodes ? 6 : 5" class="px-4 py-3 text-sm">{{ t('total') }} ({{ batch.items?.length || 0 }})</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">{{ formatMoney(batch.total_amount) }}</td>
                <td></td>
                <td v-if="isEditing"></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import poMessages from '@/scripts/admin/i18n/payment-orders.js'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()

const currentLocale = ref(document.documentElement.lang || 'mk')
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const formattedLocale = computed(() => localeMap[currentLocale.value] || 'mk-MK')

const observer = new MutationObserver(() => {
  currentLocale.value = document.documentElement.lang || 'mk'
})
onMounted(() => {
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] })
  loadBatch()
  loadBankAccounts()
})
onBeforeUnmount(() => observer.disconnect())

function t(key) {
  return poMessages[currentLocale.value]?.payment_orders?.[key]
    || poMessages['en']?.payment_orders?.[key]
    || key
}

const isLoading = ref(false)
const isApproving = ref(false)
const isExporting = ref(false)
const isDownloadingPp30 = ref(false)
const isDownloadingPp50 = ref(false)
const isConfirming = ref(false)
const isCancelling = ref(false)
const isDuplicating = ref(false)
const isSaving = ref(false)
const isEditing = ref(false)
const batch = ref(null)
const bankAccounts = ref([])
const pendingAction = ref(null)
const showMoreMenu = ref(false)

const editForm = ref({
  batch_date: '',
  format: '',
  urgency: '',
  bank_account_id: null,
  notes: '',
})

const batchId = computed(() => route.params.id)

const canEdit = computed(() => batch.value && ['draft', 'pending_approval'].includes(batch.value.status))
const canApprove = computed(() => batch.value && ['draft', 'pending_approval'].includes(batch.value.status))
const canExport = computed(() => batch.value && ['approved', 'exported'].includes(batch.value.status))
const canConfirm = computed(() => batch.value && ['exported', 'sent_to_bank'].includes(batch.value.status))
const canCancel = computed(() => batch.value && ['draft', 'pending_approval', 'approved'].includes(batch.value.status))
const canExportPp30 = computed(() => batch.value && batch.value.format === 'pp30' && ['approved', 'exported', 'confirmed'].includes(batch.value.status))
const canExportPp50 = computed(() => batch.value && batch.value.format === 'pp50' && ['approved', 'exported', 'confirmed'].includes(batch.value.status))

const hasPaymentCodes = computed(() => {
  return batch.value?.items?.some(item => item.payment_code)
})

const hasPp50Data = computed(() => {
  if (!batch.value?.items?.length) return false
  const first = batch.value.items[0]
  return first.tax_number || first.revenue_code || first.program_code || first.municipality_code || first.approval_reference
})

const pp50DataFromItems = computed(() => {
  if (!batch.value?.items?.length) return {}
  const first = batch.value.items[0]
  return {
    tax_number: first.tax_number,
    revenue_code: first.revenue_code,
    program_code: first.program_code,
    municipality_code: first.municipality_code,
    approval_reference: first.approval_reference,
  }
})

const statusPipeline = computed(() => {
  if (batch.value && !['draft', 'pending_approval'].includes(batch.value.status)) {
    return [
      { key: 'approved', label: t('status_approved') },
      { key: 'exported', label: t('status_exported') },
      { key: 'confirmed', label: t('status_confirmed') },
    ]
  }
  return [
    { key: 'draft', label: t('status_draft') },
    { key: 'approved', label: t('status_approved') },
    { key: 'exported', label: t('status_exported') },
    { key: 'confirmed', label: t('status_confirmed') },
  ]
})

const statusOrder = ['draft', 'pending_approval', 'approved', 'exported', 'sent_to_bank', 'confirmed']

// Close dropdown on outside click
function handleOutsideClick(e) {
  if (showMoreMenu.value && !e.target.closest('.relative')) {
    showMoreMenu.value = false
  }
}
onMounted(() => {
  document.addEventListener('click', handleOutsideClick)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick)
})

function toggleEditMode() {
  if (isEditing.value) {
    isEditing.value = false
    return
  }
  editForm.value = {
    batch_date: batch.value.batch_date,
    format: batch.value.format,
    urgency: batch.value.urgency || 'redovno',
    bank_account_id: batch.value.bank_account_id || null,
    notes: batch.value.notes || '',
  }
  isEditing.value = true
}

async function loadBankAccounts() {
  try {
    const response = await window.axios.get('/banking/accounts')
    bankAccounts.value = response.data?.data || response.data || []
  } catch {
    // silent
  }
}

async function saveBatch() {
  isSaving.value = true
  try {
    await window.axios.put(`/payment-orders/${batchId.value}`, editForm.value)
    notificationStore.showNotification({ type: 'success', message: t('save_changes') + ' \u2713' })
    isEditing.value = false
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('edit_not_allowed') })
  } finally {
    isSaving.value = false
  }
}

function removeItem(itemId) {
  // Remove item from current view and save updated bill_ids
  const remainingBillIds = batch.value.items
    .filter(i => i.id !== itemId && i.bill_id)
    .map(i => i.bill_id)
  if (remainingBillIds.length === 0) {
    notificationStore.showNotification({ type: 'error', message: t('select_at_least_one') })
    return
  }
  editForm.value.bill_ids = remainingBillIds
  saveBatch()
}

async function loadBatch() {
  isLoading.value = true
  try {
    const response = await window.axios.get(`/payment-orders/${batchId.value}`)
    batch.value = response.data?.data || response.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('error_loading') || 'Failed to load payment order',
    })
    router.push('/admin/payment-orders')
  } finally {
    isLoading.value = false
  }
}

async function approveBatch() {
  isApproving.value = true
  try {
    await window.axios.post(`/payment-orders/${batchId.value}/approve`)
    notificationStore.showNotification({ type: 'success', message: t('approved_success') || 'Approved' })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_approving') || 'Failed to approve' })
  } finally {
    isApproving.value = false
  }
}

async function exportBatch() {
  isExporting.value = true
  try {
    const response = await window.axios.get(`/payment-orders/${batchId.value}/export`, {
      responseType: 'blob',
    })

    const blob = response.data
    if (blob.type === 'application/json') {
      try {
        const text = await blob.text()
        const json = JSON.parse(text)
        notificationStore.showNotification({ type: 'error', message: json.message || t('error_exporting') })
      } catch {
        notificationStore.showNotification({ type: 'error', message: t('error_exporting') })
      }
      return
    }

    const contentDisposition = response.headers['content-disposition'] || ''
    const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
    const filename = filenameMatch ? filenameMatch[1].replace(/['"]/g, '') : `payment_order_${batch.value.batch_number}.csv`

    const downloadBlob = new Blob([blob])
    const url = window.URL.createObjectURL(downloadBlob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    notificationStore.showNotification({ type: 'success', message: t('exported') || 'File exported' })
    await loadBatch()
  } catch (error) {
    let message = t('error_exporting') || 'Failed to export'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        message = json.message || message
      } catch { /* use default message */ }
    } else if (error.response?.data?.message) {
      message = error.response.data.message
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isExporting.value = false
  }
}

async function downloadPdf(endpoint, filename) {
  const loadingRef = endpoint.includes('pp50') ? isDownloadingPp50 : isDownloadingPp30
  loadingRef.value = true
  try {
    const response = await window.axios.get(`/payment-orders/${batchId.value}/${endpoint}`, {
      responseType: 'blob',
    })

    const blob = response.data
    if (blob.type === 'application/json') {
      try {
        const text = await blob.text()
        const json = JSON.parse(text)
        notificationStore.showNotification({ type: 'error', message: json.message || t('error_exporting') })
      } catch {
        notificationStore.showNotification({ type: 'error', message: t('error_exporting') })
      }
      return
    }

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', filename)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)

    notificationStore.showNotification({ type: 'success', message: filename + ' \u2713' })
  } catch (error) {
    let message = t('error_exporting') || 'Failed to generate PDF'
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const json = JSON.parse(text)
        message = json.message || message
      } catch { /* use default */ }
    } else if (error.response?.data?.message) {
      message = error.response.data.message
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    loadingRef.value = false
  }
}

function downloadPp30Pdf() {
  downloadPdf('pp30', `PP30_${batch.value.batch_number}.pdf`)
}

function downloadPp50Pdf() {
  downloadPdf('pp50', `PP50_${batch.value.batch_number}.pdf`)
}

function printPage() {
  window.print()
}

async function duplicateBatch() {
  isDuplicating.value = true
  try {
    const response = await window.axios.post(`/payment-orders/${batchId.value}/duplicate`)
    const newBatch = response.data?.data
    notificationStore.showNotification({ type: 'success', message: t('duplicate_success') })
    router.push(`/admin/payment-orders/${newBatch.id}`)
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_creating') })
  } finally {
    isDuplicating.value = false
  }
}

async function confirmBatch() {
  if (!pendingAction.value) {
    pendingAction.value = 'confirm'
    return
  }
  pendingAction.value = null
  isConfirming.value = true
  try {
    await window.axios.post(`/payment-orders/${batchId.value}/confirm`)
    notificationStore.showNotification({ type: 'success', message: t('confirmed_success') })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_confirming') })
  } finally {
    isConfirming.value = false
  }
}

async function cancelBatch() {
  if (!pendingAction.value) {
    pendingAction.value = 'cancel'
    return
  }
  pendingAction.value = null
  isCancelling.value = true
  try {
    await window.axios.post(`/payment-orders/${batchId.value}/cancel`)
    notificationStore.showNotification({ type: 'success', message: t('cancelled_success') })
    await loadBatch()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || t('error_cancelling') })
  } finally {
    isCancelling.value = false
  }
}

function dismissAction() {
  pendingAction.value = null
}

function isStepActive(stepKey) {
  if (!batch.value) return false
  const currentIdx = statusOrder.indexOf(batch.value.status)
  const stepIdx = statusOrder.indexOf(stepKey)
  return stepIdx <= currentIdx
}

function isCurrentStep(stepKey) {
  if (!batch.value) return false
  const status = batch.value.status
  if (status === 'cancelled' || status === 'confirmed') return false
  // Map current status to the pipeline step that needs action
  const stepMap = {
    draft: 'draft',
    pending_approval: 'draft',
    approved: 'approved',
    exported: 'exported',
    sent_to_bank: 'exported',
  }
  return stepMap[status] === stepKey
}

function stepHint(stepKey) {
  const hints = {
    draft: t('hint_draft'),
    approved: t('hint_approved'),
    exported: t('hint_exported'),
    confirmed: t('hint_confirmed'),
  }
  return hints[stepKey] || ''
}

function stepClass(stepKey) {
  if (!batch.value) return 'bg-gray-200 text-gray-500'
  if (batch.value.status === 'cancelled') return 'bg-red-200 text-red-700'
  if (isStepActive(stepKey)) return 'bg-primary-500 text-white'
  return 'bg-gray-200 text-gray-500'
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

function formatLabel(format) {
  const labels = { pp30: 'PP30', pp50: 'PP50', sepa_sct: 'SEPA', csv: 'CSV' }
  return labels[format] || format
}

function statusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-700',
    pending_approval: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-blue-100 text-blue-700',
    exported: 'bg-indigo-100 text-indigo-700',
    sent_to_bank: 'bg-purple-100 text-purple-700',
    confirmed: 'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function statusLabel(status) {
  const labels = {
    draft: t('status_draft'),
    pending_approval: t('status_pending', 'Pending'),
    approved: t('status_approved'),
    exported: t('status_exported'),
    sent_to_bank: t('status_sent', 'Sent to Bank'),
    confirmed: t('status_confirmed'),
    cancelled: t('cancelled', 'Cancelled'),
  }
  return labels[status] || status
}

function itemStatusClass(status) {
  const classes = {
    pending: 'bg-gray-100 text-gray-700',
    exported: 'bg-indigo-100 text-indigo-700',
    confirmed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}

function itemStatusLabel(status) {
  const labels = {
    pending: t('item_pending', 'Pending'),
    exported: t('item_exported', 'Exported'),
    confirmed: t('item_confirmed', 'Confirmed'),
    failed: t('item_failed', 'Failed'),
  }
  return labels[status] || status
}
</script>

<!-- CLAUDE-CHECKPOINT -->
