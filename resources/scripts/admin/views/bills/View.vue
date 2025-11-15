<template>
  <BasePage>
    <BasePageHeader :title="bill?.bill_number || $t('bills.view_bill')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.title')" to="/admin/bills" />
        <BaseBreadcrumbItem
          :title="bill?.bill_number || $t('bills.view_bill')"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          variant="secondary"
          class="mr-2"
          @click="downloadPdf"
        >
          {{ $t('general.download_pdf') }}
        </BaseButton>

        <BaseButton
          v-if="bill"
          variant="secondary"
          class="mr-2"
          @click="goToPayments"
        >
          {{ $t('bills.view_payments') }}
        </BaseButton>

        <BaseButton
          v-if="bill"
          variant="secondary"
          class="mr-2"
          @click="sendBill"
        >
          {{ $t('bills.send_bill') }}
        </BaseButton>

        <BaseButton
          v-if="bill && bill.status === 'SENT'"
          variant="secondary"
          class="mr-2"
          @click="markViewed"
        >
          {{ $t('bills.mark_viewed') }}
        </BaseButton>

        <BaseButton
          v-if="bill"
          variant="primary"
          @click="markCompleted"
        >
          {{ $t('bills.mark_completed') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseCard v-if="bill">
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
          {{ bill.status }}
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('bills.paid_status')">
          {{ bill.paid_status }}
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('bills.total')">
          <BaseFormatMoney :amount="bill.total" :currency="bill.currency" />
        </BaseDescriptionListItem>
        <BaseDescriptionListItem :label="$t('bills.due_amount')">
          <BaseFormatMoney :amount="bill.due_amount" :currency="bill.currency" />
        </BaseDescriptionListItem>
      </BaseDescriptionList>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useBillsStore } from '@/scripts/admin/stores/bills'

const route = useRoute()
const router = useRouter()
const billsStore = useBillsStore()

const bill = computed(() => billsStore.selectedBill)

function downloadPdf() {
  if (!bill.value) return
  window.open(`/api/v1/bills/${bill.value.id}/download-pdf`, '_blank')
}

function goToPayments() {
  if (!bill.value) return
  router.push(`/admin/bills/${bill.value.id}/payments`)
}

function sendBill() {
  if (!bill.value) return
  billsStore.sendBill(bill.value)
}

function markViewed() {
  if (!bill.value) return
  billsStore.markAsViewed(bill.value.id).then(() => {
    billsStore.fetchBill(bill.value.id)
  })
}

function markCompleted() {
  if (!bill.value) return
  billsStore.markAsCompleted(bill.value.id).then(() => {
    billsStore.fetchBill(bill.value.id)
  })
}

onMounted(() => {
  billsStore.fetchBill(route.params.id)
})
</script>
