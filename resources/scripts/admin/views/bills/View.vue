<script setup>
import { useI18n } from 'vue-i18n'
import { computed, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'

import BillIndexDropdown from '@/scripts/admin/components/dropdowns/BillIndexDropdown.vue'
import BaseBillStatusLabel from '@/scripts/components/base/BaseBillStatusLabel.vue'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

import abilities from '@/scripts/admin/stub/abilities'

const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()
const billsStore = useBillsStore()
const userStore = useUserStore()
const router = useRouter()

const { t } = useI18n()
const route = useRoute()

const isMarkAsSent = ref(false)
const isLoading = ref(false)
const isDownloadingPp30 = ref(false)
const journalEntries = ref([])
const journalTransactionId = ref(null)
const isLoadingJournal = ref(false)
const journalError = ref(null)

const bill = computed(() => billsStore.selectedBill)
const pageTitle = computed(() => bill.value?.bill_number || t('bills.view_bill'))

const shareableLink = computed(() => {
  if (!bill.value) return ''
  return `/bills/pdf/${bill.value.unique_hash}`
})

watch(route, (to, from) => {
  if (to.name === 'bills.view') {
    loadBill()
  }
})

async function loadBill() {
  isLoading.value = true
  let response = await billsStore.fetchBill(route.params.id)
  if (response.data) {
    // Bill is already set in store by fetchBill
  }
  isLoading.value = false
}

async function refreshBill() {
  await billsStore.fetchBill(route.params.id)
}

async function onMarkAsSent() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('bills.bill_mark_as_sent'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      isMarkAsSent.value = false
      if (response) {
        await billsStore.markAsSent({
          id: bill.value.id,
          status: 'SENT',
        })
        bill.value.status = 'SENT'
        isMarkAsSent.value = true
        await refreshBill()
      }
      isMarkAsSent.value = false
    })
}

async function onSendBill() {
  if (!bill.value) return
  await billsStore.sendBill(bill.value)
  await refreshBill()
}

async function loadPayments() {
  if (!bill.value) return
  await billsStore.fetchBillPayments(bill.value.id)
}

async function loadJournalEntry() {
  if (!bill.value || !bill.value.posted_to_ifrs) return
  if (journalEntries.value.length > 0) return // already loaded
  isLoadingJournal.value = true
  journalError.value = null
  try {
    const response = await window.axios.get(`/bills/${bill.value.id}/journal-entry`)
    if (response.data.success) {
      journalEntries.value = response.data.entries || []
      journalTransactionId.value = response.data.transaction_id
    } else {
      journalError.value = response.data.message || t('bills.journal_load_error', 'Failed to load journal entry')
    }
  } catch (error) {
    journalError.value = error.response?.data?.message || t('bills.journal_load_error', 'Failed to load journal entry')
  } finally {
    isLoadingJournal.value = false
  }
}

async function deletePayment(paymentId) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('bills.confirm_delete_payment'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (response) => {
      if (response) {
        await billsStore.deleteBillPayment(bill.value.id, paymentId)
        await loadPayments()
        await refreshBill()
      }
    })
}

async function downloadPp30() {
  isDownloadingPp30.value = true
  try {
    const response = await window.axios.get(`/bills/${bill.value.id}/pp30`, {
      responseType: 'blob',
    })

    const blob = response.data

    if (blob.type === 'application/json') {
      const text = await blob.text()
      const json = JSON.parse(text)
      notificationStore.showNotification({
        type: 'error',
        message: json.message || t('payment_orders.supplier_no_iban', 'Supplier has no IBAN'),
      })
      return
    }

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `PP30_${bill.value.bill_number || bill.value.id}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch (error) {
    let message = t('payment_orders.supplier_no_iban', 'Failed to generate PP30')
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
    isDownloadingPp30.value = false
  }
}

onMounted(() => {
  loadBill()
})
</script>

<template>
  <BasePage v-if="bill">
    <BasePageHeader :title="pageTitle">
      <template #actions>
        <div class="flex flex-wrap gap-2 items-center">
          <!-- Edit Button -->
          <router-link
            v-if="userStore.hasAbilities(abilities.EDIT_BILL)"
            :to="`/admin/bills/${bill.id}/edit`"
          >
            <BaseButton variant="primary-outline">
              <template #left="slotProps">
                <BaseIcon name="PencilIcon" :class="[slotProps.class, 'md:hidden']" />
              </template>
              <span class="hidden md:inline">{{ $t('general.edit') }}</span>
            </BaseButton>
          </router-link>

          <!-- Mark as Sent Button -->
          <BaseButton
            v-if="
              bill.status === 'DRAFT' &&
              userStore.hasAbilities(abilities.EDIT_BILL)
            "
            :disabled="isMarkAsSent"
            variant="primary-outline"
            @click="onMarkAsSent"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckCircleIcon" :class="[slotProps.class, 'md:hidden']" />
            </template>
            <span class="hidden md:inline">{{ $t('bills.mark_as_sent') }}</span>
          </BaseButton>

          <!-- Send Bill Button -->
          <BaseButton
            v-if="
              bill.status === 'DRAFT' &&
              userStore.hasAbilities(abilities.SEND_BILL)
            "
            variant="primary"
            @click="onSendBill"
          >
            <template #left="slotProps">
              <BaseIcon name="PaperAirplaneIcon" :class="[slotProps.class, 'md:hidden']" />
            </template>
            <span class="hidden md:inline">{{ $t('bills.send_bill') }}</span>
          </BaseButton>

          <!-- Record Payment Button -->
          <router-link
            v-if="
              userStore.hasAbilities(abilities.CREATE_PAYMENT) &&
              (bill.status === 'SENT' || bill.status === 'VIEWED')
            "
            :to="`/admin/bills/${bill.id}/payments`"
          >
            <BaseButton variant="primary">
              <template #left="slotProps">
                <BaseIcon name="BanknotesIcon" :class="[slotProps.class, 'md:hidden']" />
              </template>
              <span class="hidden md:inline">{{ $t('bills.record_payment') }}</span>
            </BaseButton>
          </router-link>

          <!-- Print PP30 Button — hidden on mobile, available in dropdown -->
          <BaseButton
            v-if="bill.paid_status !== 'PAID'"
            variant="primary-outline"
            class="hidden md:inline-flex"
            :loading="isDownloadingPp30"
            @click="downloadPp30"
          >
            {{ $t('payment_orders.print_pp30', 'Print PP30') }}
          </BaseButton>

          <!-- Bill Dropdown -->
          <BillIndexDropdown
            :row="bill"
            :load-data="refreshBill"
          />
        </div>
      </template>
    </BasePageHeader>

    <!-- Duplicate Warning Banner -->
    <div
      v-if="bill.is_duplicate"
      class="mt-4 flex items-start gap-3 p-4 rounded-lg border border-amber-300 bg-amber-50"
    >
      <BaseIcon name="ExclamationTriangleIcon" class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
      <div>
        <p class="text-sm font-medium text-amber-800">
          {{ $t('bills.duplicate_warning') }}
        </p>
        <router-link
          v-if="bill.duplicate_of_id"
          :to="{ path: `/admin/bills/${bill.duplicate_of_id}/view` }"
          class="text-sm text-amber-700 underline hover:text-amber-900"
        >
          {{ $t('bills.view_original') }}
        </router-link>
      </div>
    </div>

    <BaseCard class="mt-8">
      <BaseTabGroup>
        <!-- Details Tab -->
        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('bills.details')"
        >
          <div class="grid grid-cols-1 gap-4 md:gap-6">
            <!-- Bill Information Section -->
            <BaseCard>
              <div class="p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4">
                  {{ $t('bills.bill_information') }}
                </h3>
                <BaseDescriptionList>
                  <BaseDescriptionListItem :label="$t('bills.bill_number')">
                    {{ bill.bill_number }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem :label="$t('bills.bill_date')">
                    {{ bill.formatted_bill_date }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem :label="$t('bills.due_date')">
                    {{ bill.formatted_due_date || '-' }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem :label="$t('bills.supplier')">
                    {{ bill.supplier?.name || '-' }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem
                    v-if="bill.supplier?.vat_number"
                    :label="$t('bills.supplier_vat_number', 'ЕДБ за ДДВ')"
                  >
                    {{ bill.supplier.vat_number }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem
                    v-if="bill.supplier?.tax_id"
                    :label="$t('bills.supplier_tax_id', 'Даночен број')"
                  >
                    {{ bill.supplier.tax_id }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem
                    v-if="bill.supplier?.company_registration_number"
                    :label="$t('bills.supplier_embs', 'ЕМБС')"
                  >
                    {{ bill.supplier.company_registration_number }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem
                    v-if="bill.supply_date"
                    :label="$t('bills.supply_date', 'Ден на промет')"
                  >
                    {{ bill.supply_date }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem
                    v-if="bill.place_of_issue"
                    :label="$t('bills.place_of_issue', 'Место на издавање')"
                  >
                    {{ bill.place_of_issue }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem
                    v-if="bill.payment_terms_days"
                    :label="$t('bills.payment_terms_days', 'Рок на плаќање')"
                  >
                    {{ bill.payment_terms_days }} {{ $t('bills.days', 'дена') }}
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem :label="$t('bills.status')">
                    <BaseBillStatusBadge :status="bill.status" class="px-2 py-1">
                      <BaseBillStatusLabel :status="bill.status" />
                    </BaseBillStatusBadge>
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem :label="$t('bills.paid_status')">
                    <BaseBillPaidStatusBadge :status="bill.paid_status" class="px-2 py-1">
                      <BaseBillStatusLabel :status="bill.paid_status" />
                    </BaseBillPaidStatusBadge>
                  </BaseDescriptionListItem>
                </BaseDescriptionList>
              </div>
            </BaseCard>

            <!-- Line Items Section -->
            <BaseCard v-if="bill.items && bill.items.length > 0">
              <div class="p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4">
                  {{ $t('bills.line_items') }}
                </h3>

                <!-- Mobile: Card layout -->
                <div class="md:hidden space-y-3">
                  <div
                    v-for="(item, index) in bill.items"
                    :key="'m-' + index"
                    class="border border-gray-100 rounded-lg p-3"
                  >
                    <div class="font-medium text-sm text-gray-900">{{ item.name }}</div>
                    <div v-if="item.description" class="text-xs text-gray-500 mt-0.5">{{ item.description }}</div>
                    <div class="grid grid-cols-2 gap-2 mt-2 text-sm">
                      <div>
                        <span class="text-gray-500">{{ $t('bills.quantity') }}:</span>
                        {{ item.quantity }}
                      </div>
                      <div class="text-right">
                        <span class="text-gray-500">{{ $t('bills.price') }}:</span>
                        <BaseFormatMoney :amount="item.price" :currency="bill.currency" />
                      </div>
                      <div v-if="item.tax > 0">
                        <span class="text-gray-500">{{ $t('bills.tax') }}:</span>
                        <BaseFormatMoney :amount="item.tax" :currency="bill.currency" />
                      </div>
                      <div class="text-right font-semibold">
                        <BaseFormatMoney :amount="item.total" :currency="bill.currency" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Desktop: Table layout -->
                <div class="hidden md:block overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                          {{ $t('bills.item') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          {{ $t('bills.quantity') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          {{ $t('bills.price') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          {{ $t('bills.discount') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          {{ $t('bills.tax') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                          {{ $t('bills.total') }}
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                      <tr v-for="(item, index) in bill.items" :key="index">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                          {{ item.name }}
                          <div v-if="item.description" class="text-xs text-gray-500 mt-1">
                            {{ item.description }}
                          </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                          {{ item.quantity }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                          <BaseFormatMoney :amount="item.price" :currency="bill.currency" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                          <BaseFormatMoney :amount="item.discount_val || 0" :currency="bill.currency" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                          <BaseFormatMoney :amount="item.tax || 0" :currency="bill.currency" />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-right">
                          <BaseFormatMoney :amount="item.total" :currency="bill.currency" />
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </BaseCard>

            <!-- Tax Breakdown Section -->
            <BaseCard v-if="bill.taxes && bill.taxes.length > 0">
              <div class="p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4">
                  {{ $t('bills.tax_breakdown') }}
                </h3>
                <div class="space-y-2">
                  <div
                    v-for="(tax, index) in bill.taxes"
                    :key="index"
                    class="flex justify-between text-sm"
                  >
                    <span class="text-gray-600">{{ tax.name }} ({{ tax.percent }}%)</span>
                    <BaseFormatMoney :amount="tax.amount" :currency="bill.currency" />
                  </div>
                </div>
              </div>
            </BaseCard>

            <!-- Totals Section -->
            <BaseCard>
              <div class="p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4">
                  {{ $t('bills.totals') }}
                </h3>
                <div class="space-y-3">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $t('bills.subtotal') }}</span>
                    <BaseFormatMoney :amount="bill.sub_total" :currency="bill.currency" />
                  </div>
                  <div v-if="bill.discount_val > 0" class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $t('bills.discount') }}</span>
                    <BaseFormatMoney :amount="bill.discount_val" :currency="bill.currency" />
                  </div>
                  <div v-if="bill.tax > 0" class="flex justify-between text-sm">
                    <span class="text-gray-600">{{ $t('bills.tax') }}</span>
                    <BaseFormatMoney :amount="bill.tax" :currency="bill.currency" />
                  </div>
                  <div class="flex justify-between text-base font-semibold pt-3 border-t">
                    <span>{{ $t('bills.total') }}</span>
                    <BaseFormatMoney :amount="bill.total" :currency="bill.currency" />
                  </div>
                  <div class="flex justify-between text-base font-semibold text-primary-600">
                    <span>{{ $t('bills.due_amount') }}</span>
                    <BaseFormatMoney :amount="bill.due_amount" :currency="bill.currency" />
                  </div>
                </div>
              </div>
            </BaseCard>

            <!-- Notes Section -->
            <BaseCard v-if="bill.notes">
              <div class="p-4 md:p-6">
                <h3 class="text-lg font-semibold mb-4">
                  {{ $t('bills.notes') }}
                </h3>
                <p class="text-sm text-gray-600 whitespace-pre-wrap">
                  {{ bill.notes }}
                </p>
              </div>
            </BaseCard>
          </div>
        </BaseTab>

        <!-- Scanned Invoice Tab -->
        <BaseTab
          v-if="bill.scanned_invoice_url"
          tab-panel-container="py-4 mt-px"
          :title="$t('bills.scanned_invoice', 'Scanned Invoice')"
        >
          <BaseCard>
            <div class="p-4 md:p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ $t('bills.scanned_invoice', 'Scanned Invoice') }}
                </h3>
                <a
                  :href="bill.scanned_invoice_url"
                  target="_blank"
                  download
                >
                  <BaseButton variant="primary-outline">
                    {{ $t('general.download') }}
                  </BaseButton>
                </a>
              </div>
              <div class="border rounded-lg overflow-hidden bg-gray-50">
                <iframe
                  :src="bill.scanned_invoice_url"
                  class="w-full"
                  style="height: 800px;"
                  frameborder="0"
                />
              </div>
            </div>
          </BaseCard>
        </BaseTab>

        <!-- Payments Tab -->
        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('bills.payments')"
          @click="loadPayments"
        >
          <BaseCard>
            <div class="p-4 md:p-6">
              <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-semibold">
                  {{ $t('bills.payment_history') }}
                </h3>
                <router-link
                  v-if="userStore.hasAbilities(abilities.CREATE_PAYMENT)"
                  :to="`/admin/bills/${bill.id}/payments`"
                >
                  <BaseButton variant="primary">
                    {{ $t('bills.add_payment') }}
                  </BaseButton>
                </router-link>
              </div>

              <div v-if="billsStore.billPayments && billsStore.billPayments.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.payment_date') }}
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.payment_number') }}
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.amount') }}
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.payment_method') }}
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.notes') }}
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.actions') }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(payment, index) in billsStore.billPayments" :key="index">
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ payment.formatted_payment_date }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ payment.payment_number }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        <BaseFormatMoney :amount="payment.amount" :currency="bill.currency" />
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ payment.payment_method?.name || '-' }}
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900">
                        {{ payment.notes || '-' }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <router-link
                          v-if="userStore.hasAbilities(abilities.EDIT_PAYMENT)"
                          :to="`/admin/bills/${bill.id}/payments/${payment.id}/edit`"
                          class="text-primary-600 hover:text-primary-900 mr-4"
                        >
                          {{ $t('general.edit') }}
                        </router-link>
                        <button
                          v-if="userStore.hasAbilities(abilities.DELETE_PAYMENT)"
                          @click="deletePayment(payment.id)"
                          class="text-red-600 hover:text-red-900"
                        >
                          {{ $t('general.delete') }}
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div v-else class="text-center py-8">
                <p class="text-gray-500">{{ $t('bills.no_payments_yet') }}</p>
              </div>

              <div v-if="billsStore.billPayments && billsStore.billPayments.length > 0" class="mt-6">
                <router-link
                  :to="`/admin/bills/${bill.id}/payments`"
                  class="text-primary-600 hover:text-primary-900 text-sm font-medium"
                >
                  {{ $t('bills.view_all_payments') }} →
                </router-link>
              </div>
            </div>
          </BaseCard>
        </BaseTab>

        <!-- Journal Entry Tab (Книжење) -->
        <BaseTab
          v-if="bill.posted_to_ifrs"
          tab-panel-container="py-4 mt-px"
          :title="$t('bills.journal_entry', 'Книжење')"
          @click="loadJournalEntry"
        >
          <BaseCard>
            <div class="p-4 md:p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">
                  {{ $t('bills.journal_entry', 'Книжење') }}
                </h3>
                <span
                  v-if="journalTransactionId"
                  class="text-xs text-gray-500"
                >
                  {{ $t('bills.transaction_id', 'Transaction ID') }}: #{{ journalTransactionId }}
                </span>
              </div>

              <!-- Loading State -->
              <div v-if="isLoadingJournal" class="flex justify-center py-8">
                <LoadingIcon class="h-8 w-8 animate-spin text-primary-400" />
              </div>

              <!-- Error State -->
              <div v-else-if="journalError" class="text-center py-8">
                <p class="text-red-500">{{ journalError }}</p>
              </div>

              <!-- Journal Entries Table -->
              <div v-else-if="journalEntries.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.account_code', 'Конто') }}
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.account_name', 'Назив на конто') }}
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.debit', 'Должи') }}
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        {{ $t('bills.credit', 'Побарува') }}
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="(entry, index) in journalEntries" :key="index">
                      <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                        {{ entry.account_code }}
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900">
                        {{ entry.account_name }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        {{ entry.debit > 0 ? Number(entry.debit).toLocaleString('mk-MK', { minimumFractionDigits: 2 }) : '' }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                        {{ entry.credit > 0 ? Number(entry.credit).toLocaleString('mk-MK', { minimumFractionDigits: 2 }) : '' }}
                      </td>
                    </tr>
                  </tbody>
                  <tfoot class="bg-gray-50">
                    <tr>
                      <td colspan="2" class="px-6 py-3 text-sm font-semibold text-gray-900">
                        {{ $t('bills.total', 'Вкупно') }}
                      </td>
                      <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                        {{ journalEntries.reduce((sum, e) => sum + (e.debit || 0), 0).toLocaleString('mk-MK', { minimumFractionDigits: 2 }) }}
                      </td>
                      <td class="px-6 py-3 whitespace-nowrap text-sm font-semibold text-gray-900 text-right">
                        {{ journalEntries.reduce((sum, e) => sum + (e.credit || 0), 0).toLocaleString('mk-MK', { minimumFractionDigits: 2 }) }}
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <!-- Fallback: Posted but no entries loaded yet -->
              <div v-else class="text-center py-8">
                <p class="text-gray-500">
                  {{ $t('bills.posted_to_ifrs_label', 'Posted to IFRS') }}
                </p>
                <p v-if="bill.ifrs_transaction_id" class="text-xs text-gray-400 mt-1">
                  {{ $t('bills.transaction_id', 'Transaction ID') }}: #{{ bill.ifrs_transaction_id }}
                </p>
              </div>
            </div>
          </BaseCard>
        </BaseTab>
      </BaseTabGroup>
    </BaseCard>
  </BasePage>

  <div v-else-if="isLoading" class="flex justify-center items-center h-screen">
    <LoadingIcon class="h-12 w-12 animate-spin text-primary-400" />
  </div>
</template>
<!-- CLAUDE-CHECKPOINT -->
