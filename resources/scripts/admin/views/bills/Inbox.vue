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

    <!-- DEBUG: remove after inbox investigation -->
    <div v-if="debugInfo" class="p-3 mb-4 text-xs font-mono bg-yellow-100 border border-yellow-300 rounded">
      INBOX v3 | Bills received: {{ debugInfo.count }} | IDs: {{ debugInfo.ids }} | Total: {{ debugInfo.total }} | Pages: {{ debugInfo.pages }}
    </div>

    <div class="relative">
      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="columns"
        class="mt-6"
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
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import axios from 'axios'

const { t } = useI18n()
const billsStore = useBillsStore()
const router = useRouter()
const table = ref(null)

const inboundEmail = ref(null)
const copied = ref(false)
const debugInfo = ref(null)

const columns = [
  { key: 'bill_date', label: t('bills.bill_date') },
  { key: 'bill_number', label: t('bills.bill_number') },
  { key: 'supplier', label: t('bills.supplier') },
  { key: 'total', label: t('bills.total') },
  { key: 'actions', label: '', sortable: false, tdClass: 'text-right' },
]

async function fetchInboundAlias() {
  try {
    const { data } = await axios.get('/bills/inbound-alias')
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

async function fetchData({ page, sort }) {
  const response = await axios.get('/bills', {
    params: {
      status: 'DRAFT',
      page: page || 1,
      limit: 10,
      orderByField: sort?.fieldName || 'bill_date',
      orderBy: sort?.order || 'desc',
    },
  })

  const bills = response.data.data || []
  const meta = response.data.meta || {}

  // DEBUG: show what the browser actually receives
  debugInfo.value = {
    count: bills.length,
    ids: bills.map(b => b.id).join(', '),
    total: meta.total || 0,
    pages: meta.last_page || 1,
  }
  console.log('[Inbox v3] fetchData response:', { bills, meta, rawResponse: response.data })

  return {
    data: bills,
    pagination: {
      totalPages: meta.last_page || 1,
      currentPage: meta.current_page || 1,
      totalCount: meta.total || 0,
      limit: 10,
    },
  }
}

function openBill(id) {
  router.push(`/admin/bills/${id}/view`)
}

function deleteBill(id) {
  billsStore.deleteBills([id]).then(() => {
    table.value?.refresh()
  })
}

onMounted(() => {
  fetchInboundAlias()
})
</script>
