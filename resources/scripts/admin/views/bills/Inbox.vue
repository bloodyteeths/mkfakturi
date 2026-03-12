<template>
  <BasePage>
    <BasePageHeader :title="$t('bills.inbox_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.title')" to="/admin/bills" />
        <BaseBreadcrumbItem :title="$t('bills.inbox_title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Forwarding email address -->
    <div
      v-if="inboundEmail"
      class="flex items-center gap-3 p-4 mb-6 rounded-lg border border-primary-200 bg-primary-50"
    >
      <BaseIcon name="EnvelopeIcon" class="w-5 h-5 text-primary-500 shrink-0" />
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium text-gray-700">
          {{ $t('bills.inbound_email_label') }}
        </p>
        <p class="text-sm text-gray-500">
          {{ $t('bills.inbound_email_description') }}
        </p>
      </div>
      <div class="flex items-center gap-2 shrink-0">
        <code class="px-3 py-1.5 text-sm font-mono bg-white rounded border border-gray-200 text-primary-600">
          {{ inboundEmail }}
        </code>
        <BaseButton variant="primary-outline" size="xs" @click="copyEmail">
          <BaseIcon :name="copied ? 'CheckIcon' : 'ClipboardDocumentIcon'" class="w-4 h-4" />
        </BaseButton>
      </div>
    </div>

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
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useBillsInboxStore } from '@/scripts/admin/stores/bills-inbox'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import axios from 'axios'

const { t } = useI18n()
const inboxStore = useBillsInboxStore()
const billsStore = useBillsStore()
const router = useRouter()

const inboundEmail = ref(null)
const copied = ref(false)

const columns = [
  { key: 'bill_date', label: t('bills.bill_date') },
  { key: 'bill_number', label: t('bills.bill_number') },
  { key: 'supplier', label: t('bills.supplier') },
  { key: 'total', label: t('bills.total') },
  { key: 'actions', label: '', sortable: false, tdClass: 'text-right' },
]

async function fetchInboundAlias() {
  try {
    const { data } = await axios.get('/api/v1/bills/inbound-alias')
    inboundEmail.value = data.email
  } catch {
    // Silently fail — alias display is optional
  }
}

function copyEmail() {
  if (inboundEmail.value) {
    navigator.clipboard.writeText(inboundEmail.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}

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
  fetchInboundAlias()
})
</script>
