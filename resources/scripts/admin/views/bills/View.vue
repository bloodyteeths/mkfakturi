<script setup>
import { useI18n } from 'vue-i18n'
import { computed, ref, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'

import BillIndexDropdown from '@/scripts/admin/components/dropdowns/BillIndexDropdown.vue'
import LoadingIcon from '@/scripts/components/icons/LoadingIcon.vue'

import abilities from '@/scripts/admin/stub/abilities'

const dialogStore = useDialogStore()
const billsStore = useBillsStore()
const userStore = useUserStore()
const router = useRouter()

const { t } = useI18n()
const route = useRoute()

const isMarkAsSent = ref(false)
const isLoading = ref(false)

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

onMounted(() => {
  loadBill()
})
// CLAUDE-CHECKPOINT
</script>

<template>
  <BasePage v-if="bill">
    <BasePageHeader :title="pageTitle">
      <template #actions>
        <!-- Edit Button -->
        <router-link
          v-if="userStore.hasAbilities(abilities.EDIT_BILL)"
          :to="`/admin/bills/${bill.id}/edit`"
        >
          <BaseButton
            variant="primary-outline"
            class="mr-3"
          >
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>

        <!-- Mark as Sent Button -->
        <div class="text-sm mr-3">
          <BaseButton
            v-if="
              bill.status === 'DRAFT' &&
              userStore.hasAbilities(abilities.EDIT_BILL)
            "
            :disabled="isMarkAsSent"
            variant="primary-outline"
            @click="onMarkAsSent"
          >
            {{ $t('bills.mark_as_sent') }}
          </BaseButton>
        </div>

        <!-- Send Bill Button -->
        <BaseButton
          v-if="
            bill.status === 'DRAFT' &&
            userStore.hasAbilities(abilities.SEND_BILL)
          "
          variant="primary"
          class="text-sm mr-3"
          @click="onSendBill"
        >
          {{ $t('bills.send_bill') }}
        </BaseButton>

        <!-- Record Payment Button -->
        <router-link
          v-if="
            userStore.hasAbilities(abilities.CREATE_PAYMENT) &&
            (bill.status === 'SENT' || bill.status === 'VIEWED')
          "
          :to="`/admin/bills/${bill.id}/payments`"
        >
          <BaseButton
            variant="primary"
            class="mr-3"
          >
            {{ $t('bills.record_payment') }}
          </BaseButton>
        </router-link>

        <!-- Bill Dropdown -->
        <BillIndexDropdown
          class="ml-3"
          :row="bill"
          :load-data="refreshBill"
        />
      </template>
    </BasePageHeader>

    <BaseCard class="mt-8">
      <BaseTabGroup>
        <!-- Details Tab -->
        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('bills.details')"
        >
          <div class="grid grid-cols-1 gap-6">
            <!-- Bill Information Section -->
            <BaseCard>
              <div class="p-6">
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
                  <BaseDescriptionListItem :label="$t('bills.status')">
                    <span
                      class="px-2 py-1 text-xs font-semibold rounded"
                      :class="{
                        'bg-gray-200 text-gray-800': bill.status === 'DRAFT',
                        'bg-blue-200 text-blue-800': bill.status === 'SENT',
                        'bg-green-200 text-green-800': bill.status === 'VIEWED',
                        'bg-purple-200 text-purple-800': bill.status === 'COMPLETED',
                      }"
                    >
                      {{ bill.status }}
                    </span>
                  </BaseDescriptionListItem>
                  <BaseDescriptionListItem :label="$t('bills.paid_status')">
                    <span
                      class="px-2 py-1 text-xs font-semibold rounded"
                      :class="{
                        'bg-red-200 text-red-800': bill.paid_status === 'UNPAID',
                        'bg-yellow-200 text-yellow-800': bill.paid_status === 'PARTIALLY_PAID',
                        'bg-green-200 text-green-800': bill.paid_status === 'PAID',
                      }"
                    >
                      {{ bill.paid_status }}
                    </span>
                  </BaseDescriptionListItem>
                </BaseDescriptionList>
              </div>
            </BaseCard>

            <!-- Line Items Section -->
            <BaseCard v-if="bill.items && bill.items.length > 0">
              <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">
                  {{ $t('bills.line_items') }}
                </h3>
                <div class="overflow-x-auto">
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
              <div class="p-6">
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
              <div class="p-6">
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
              <div class="p-6">
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

        <!-- Payments Tab -->
        <BaseTab
          tab-panel-container="py-4 mt-px"
          :title="$t('bills.payments')"
          @click="loadPayments"
        >
          <BaseCard>
            <div class="p-6">
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
      </BaseTabGroup>
    </BaseCard>
  </BasePage>

  <div v-else-if="isLoading" class="flex justify-center items-center h-screen">
    <LoadingIcon class="h-12 w-12 animate-spin text-primary-400" />
  </div>
</template>
<!-- CLAUDE-CHECKPOINT -->
