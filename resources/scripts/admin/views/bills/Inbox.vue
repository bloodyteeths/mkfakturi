<template>
  <BasePage>
    <BasePageHeader :title="$t('bills.inbox_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.inbox_title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseEmptyPlaceholder
      v-if="!inboxStore.totalDrafts && !inboxStore.isFetching"
      :title="$t('bills.inbox_empty')"
      :description="$t('bills.inbox_empty_description')"
    />

    <div v-else class="relative table-container">
      <BaseTable
        :data="inboxStore.drafts"
        :columns="columns"
        :meta="{ total: inboxStore.totalDrafts }"
        :loading="inboxStore.isFetching"
        @get-data="fetchData"
      >
        <template #cell-bill_date="{ row }">
          {{ row.data.formatted_bill_date }}
        </template>
        <template #cell-bill_number="{ row }">
          <router-link
            :to="{ path: `/admin/bills/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.bill_number }}
          </router-link>
        </template>
        <template #cell-supplier="{ row }">
          {{ row.data.supplier?.name || '-' }}
        </template>
        <template #cell-total="{ row }">
          <BaseFormatMoney :amount="row.data.total" :currency="row.data.currency" />
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
            <BaseDropdownItem @click="openBill(row.data.id)">
              {{ $t('general.view') }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="approveBill(row.data.id)">
              {{ $t('bills.approve') }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="convertToExpense(row.data.id)">
              {{ $t('bills.convert_to_expense') }}
            </BaseDropdownItem>
            <BaseDropdownItem @click="deleteBill(row.data.id)">
              {{ $t('general.delete') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useBillsInboxStore } from '@/scripts/admin/stores/bills-inbox'
import { useBillsStore } from '@/scripts/admin/stores/bills'

const { t } = useI18n()
const inboxStore = useBillsInboxStore()
const billsStore = useBillsStore()
const router = useRouter()

const columns = [
  { key: 'bill_date', label: t('bills.bill_date') },
  { key: 'bill_number', label: t('bills.bill_number') },
  { key: 'supplier', label: t('bills.supplier') },
  { key: 'total', label: t('bills.total') },
  { key: 'actions', label: '', sortable: false, tdClass: 'text-right' },
]

function fetchData(params) {
  const query = {
    page: params?.page ?? 1,
    limit: params?.limit ?? 10,
  }
  inboxStore.fetchDraftBills(query)
}

function openBill(id) {
  router.push(`/admin/bills/${id}/view`)
}

function approveBill(id) {
  billsStore.markAsCompleted(id).then(() => {
    fetchData({ page: 1, limit: 10 })
  })
}

function deleteBill(id) {
  billsStore.deleteBills([id]).then(() => {
    fetchData({ page: 1, limit: 10 })
  })
}

function convertToExpense(id) {
  router.push(`/admin/expenses/create?source=bill&bill_id=${id}`)
}

onMounted(() => {
  fetchData({ page: 1, limit: 10 })
})
</script>
