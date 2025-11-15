<template>
  <BasePage>
    <BasePageHeader :title="$t('bills.payments_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.title')" to="/admin/bills" />
        <BaseBreadcrumbItem
          :title="bill?.bill_number || ''"
          :to="`/admin/bills/${billId}/view`"
        />
        <BaseBreadcrumbItem
          :title="$t('bills.payments_title')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <BaseCard class="lg:col-span-2">
        <BaseHeading tag="h3" size="sm" class="mb-4">
          {{ $t('bills.payments_list') }}
        </BaseHeading>

        <BaseEmptyPlaceholder
          v-if="!billsStore.billPayments.length"
          :title="$t('bills.no_payments')"
          :description="$t('bills.no_payments_description')"
        />

        <div v-else class="relative table-container">
          <BaseTable
            :data="billsStore.billPayments"
            :columns="columns"
            :meta="{ total: billsStore.billPayments.length }"
          >
            <template #cell-payment_date="{ row }">
              {{ row.data.formatted_payment_date }}
            </template>
            <template #cell-amount="{ row }">
              <BaseFormatMoney
                :amount="row.data.amount"
                :currency="bill?.currency"
              />
            </template>
            <template #cell-method="{ row }">
              {{ row.data.payment_method?.name || '-' }}
            </template>
            <template #cell-actions="{ row }">
              <BaseDropdown>
                <template #button="slotProps">
                  <BaseButton
                    variant="tertiary"
                    size="xs"
                    :class="slotProps.class"
                  >
                    {{ $t('general.actions') }}
                  </BaseButton>
                </template>
                <BaseDropdownItem
                  @click="editPayment(row.data)"
                >
                  {{ $t('general.edit') }}
                </BaseDropdownItem>
                <BaseDropdownItem
                  @click="removePayment(row.data.id)"
                >
                  {{ $t('general.delete') }}
                </BaseDropdownItem>
              </BaseDropdown>
            </template>
          </BaseTable>
        </div>
      </BaseCard>

      <BaseCard>
        <BaseHeading tag="h3" size="sm" class="mb-4">
          {{ editingPayment ? $t('bills.edit_payment') : $t('bills.add_payment') }}
        </BaseHeading>

        <form @submit.prevent="submitPayment">
          <div class="space-y-4">
            <BaseInputGroup :label="$t('payments.date')" required>
              <BaseDatePicker
                v-model="paymentForm.payment_date"
                :calendar-button="true"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('payments.amount')" required>
              <BaseMoney
                v-model="paymentForm.amount"
                :currency="bill?.currency"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('payments.payment_mode')" required>
              <BaseMultiselect
                v-model="paymentForm.payment_method_id"
                :options="expenseStore.paymentModes"
                label="name"
                value-prop="id"
                track-by="name"
                :placeholder="$t('payments.select_payment_mode')"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('payments.notes')">
              <BaseTextarea
                v-model="paymentForm.notes"
                rows="3"
              />
            </BaseInputGroup>
          </div>

          <div class="mt-6 flex justify-end space-x-3">
            <BaseButton
              v-if="editingPayment"
              variant="secondary"
              type="button"
              @click="resetForm"
            >
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton variant="primary" type="submit">
              {{ editingPayment ? $t('general.update') : $t('general.create') }}
            </BaseButton>
          </div>
        </form>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, reactive, ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useExpenseStore } from '@/scripts/admin/stores/expense'

const route = useRoute()
const billsStore = useBillsStore()
const expenseStore = useExpenseStore()

const billId = computed(() => route.params.id)
const editingPayment = ref(false)

const columns = [
  { key: 'payment_date', label: 'Date' },
  { key: 'amount', label: 'Amount' },
  { key: 'method', label: 'Method' },
  { key: 'actions', label: '', sortable: false, tdClass: 'text-right' },
]

const paymentForm = reactive({
  id: null,
  payment_date: '',
  amount: 0,
  payment_method_id: null,
  notes: '',
})

const bill = computed(() => billsStore.selectedBill)

function hydratePaymentForm(payment) {
  paymentForm.id = payment.id
  paymentForm.payment_date = payment.payment_date
  paymentForm.amount = payment.amount
  paymentForm.payment_method_id = payment.payment_method_id
  paymentForm.notes = payment.notes || ''
  editingPayment.value = true
}

function resetForm() {
  paymentForm.id = null
  paymentForm.payment_date = ''
  paymentForm.amount = 0
  paymentForm.payment_method_id = null
  paymentForm.notes = ''
  editingPayment.value = false
}

function editPayment(payment) {
  hydratePaymentForm(payment)
}

function removePayment(paymentId) {
  billsStore.deleteBillPayment(billId.value, paymentId).then(() => {
    billsStore.fetchBill(billId.value)
  })
}

function submitPayment() {
  const payload = {
    payment_date: paymentForm.payment_date,
    amount: paymentForm.amount,
    payment_method_id: paymentForm.payment_method_id,
    notes: paymentForm.notes,
  }

  if (editingPayment.value && paymentForm.id) {
    billsStore
      .updateBillPayment(billId.value, paymentForm.id, payload)
      .then(() => {
        billsStore.fetchBillPayments(billId.value)
        billsStore.fetchBill(billId.value)
        resetForm()
      })
  } else {
    billsStore.createBillPayment(billId.value, payload).then(() => {
      billsStore.fetchBillPayments(billId.value)
      billsStore.fetchBill(billId.value)
      resetForm()
    })
  }
}

onMounted(() => {
  expenseStore.fetchPaymentModes()
  billsStore.fetchBill(billId.value)
  billsStore.fetchBillPayments(billId.value)
})
</script>

