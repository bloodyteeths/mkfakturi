<template>
  <BasePage>
    <BasePageHeader :title="$t('proforma_invoices.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('proforma_invoices.proforma_invoice', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="proformaInvoiceStore.proformaInvoiceTotalCount"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <ExportButton
          v-show="proformaInvoiceStore.proformaInvoiceTotalCount"
          type="proforma_invoices"
          :filters="filters"
          class="ml-4"
        />

        <router-link
          v-if="userStore.hasAbilities(abilities.CREATE_PROFORMA_INVOICE)"
          to="proforma-invoices/create"
        >
          <BaseButton variant="primary" class="ml-4">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('proforma_invoices.new_proforma_invoice') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="$t('customers.customer', 1)">
        <BaseCustomerSelectInput
          v-model="filters.customer_id"
          :placeholder="$t('customers.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('proforma_invoices.status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="status"
          searchable
          :placeholder="$t('general.select_a_status')"
          @update:modelValue="setActiveTab"
          @remove="clearStatusSearch()"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.from')">
        <BaseDatePicker
          v-model="filters.from_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <div
        class="hidden w-8 h-0 mx-4 border border-gray-400 border-solid xl:block"
        style="margin-top: 1.5rem"
      />

      <BaseInputGroup :label="$t('general.to')" class="mt-2">
        <BaseDatePicker
          v-model="filters.to_date"
          :calendar-button="true"
          calendar-button-icon="calendar"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('proforma_invoices.proforma_invoice_number')">
        <BaseInput v-model="filters.proforma_invoice_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('proforma_invoices.no_proforma_invoices')"
      :description="$t('proforma_invoices.list_of_proforma_invoices')"
    >
      <ObservatoryIcon class="mt-5 mb-4" />
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_PROFORMA_INVOICE)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/proforma-invoices/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('proforma_invoices.add_new_proforma_invoice') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div
        class="
          relative
          flex
          items-center
          justify-between
          h-10
          mt-5
          list-none
          border-b-2 border-gray-200 border-solid
        "
      >
        <!-- Tabs -->
        <BaseTabGroup class="-mb-5" @change="setStatusFilter">
          <BaseTab :title="$t('general.all')" filter="" />
          <BaseTab :title="$t('general.draft')" filter="DRAFT" />
          <BaseTab :title="$t('general.sent')" filter="SENT" />
          <BaseTab :title="$t('proforma_invoices.converted')" filter="CONVERTED" />
        </BaseTabGroup>

        <BaseDropdown
          v-if="
            proformaInvoiceStore.selectedProformaInvoices.length &&
            userStore.hasAbilities(abilities.DELETE_PROFORMA_INVOICE)
          "
          class="absolute float-right"
        >
          <template #activator>
            <span
              class="
                flex
                text-sm
                font-medium
                cursor-pointer
                select-none
                text-primary-400
              "
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" />
            </span>
          </template>

          <BaseDropdownItem @click="removeMultipleProformaInvoices">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="proformaInvoiceColumns"
        :placeholder-count="proformaInvoiceStore.proformaInvoiceTotalCount >= 20 ? 10 : 5"
        :key="tableKey"
        class="mt-10"
      >
        <!-- Select All Checkbox -->
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="proformaInvoiceStore.selectAllField"
              variant="primary"
              @change="proformaInvoiceStore.selectAllProformaInvoices"
            />
          </div>
        </template>

        <template #cell-checkbox="{ row }">
          <div class="relative block">
            <BaseCheckbox
              :id="row.id"
              v-model="selectField"
              :value="row.data.id"
            />
          </div>
        </template>

        <template #cell-name="{ row }">
          <BaseText :text="row.data.customer?.name || '-'" />
        </template>

        <!-- Proforma Invoice Number  -->
        <template #cell-proforma_invoice_number="{ row }">
          <router-link
            :to="{ path: `proforma-invoices/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.proforma_invoice_number }}
          </router-link>
        </template>

        <!-- Proforma Invoice date  -->
        <template #cell-proforma_invoice_date="{ row }">
          {{ row.data.formatted_proforma_invoice_date }}
        </template>

        <!-- Expiry date  -->
        <template #cell-expiry_date="{ row }">
          <span :class="{ 'text-red-500': row.data.is_expired }">
            {{ row.data.formatted_expiry_date }}
          </span>
        </template>

        <!-- Proforma Invoice Total  -->
        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.currency"
          />
        </template>

        <!-- Proforma Invoice status  -->
        <template #cell-status="{ row }">
          <BaseProformaInvoiceStatusBadge :status="row.data.status">
            <BaseProformaInvoiceStatusLabel :status="row.data.status" />
          </BaseProformaInvoiceStatusBadge>
        </template>

        <!-- Actions -->
        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <ProformaInvoiceDropdown :row="row.data" :table="table" />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onUnmounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProformaInvoiceStore } from '@/scripts/admin/stores/proforma-invoice'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { debouncedWatch } from '@vueuse/core'

import ObservatoryIcon from '@/scripts/components/icons/empty/ObservatoryIcon.vue'
import ProformaInvoiceDropdown from '@/scripts/admin/components/dropdowns/ProformaInvoiceIndexDropdown.vue'
import ExportButton from '@/scripts/admin/components/ExportButton.vue'
import BaseProformaInvoiceStatusBadge from '@/scripts/components/base/BaseProformaInvoiceStatusBadge.vue'
import BaseProformaInvoiceStatusLabel from '@/scripts/components/base/BaseProformaInvoiceStatusLabel.vue'

// Stores
const proformaInvoiceStore = useProformaInvoiceStore()
const dialogStore = useDialogStore()
const userStore = useUserStore()

const { t } = useI18n()

// Local State
const table = ref(null)
const tableKey = ref(0)
const showFilters = ref(false)
const router = useRouter()

const status = ref([
  { label: t('proforma_invoices.statuses.draft'), value: 'DRAFT' },
  { label: t('proforma_invoices.statuses.sent'), value: 'SENT' },
  { label: t('proforma_invoices.statuses.viewed'), value: 'VIEWED' },
  { label: t('proforma_invoices.statuses.expired'), value: 'EXPIRED' },
  { label: t('proforma_invoices.statuses.converted'), value: 'CONVERTED' },
  { label: t('proforma_invoices.statuses.rejected'), value: 'REJECTED' },
])

const isRequestOngoing = ref(true)
const activeTab = ref('general.all')

let filters = reactive({
  customer_id: '',
  status: '',
  from_date: '',
  to_date: '',
  proforma_invoice_number: '',
})

const showEmptyScreen = computed(
  () => !proformaInvoiceStore.proformaInvoiceTotalCount && !isRequestOngoing.value
)

const selectField = computed({
  get: () => proformaInvoiceStore.selectedProformaInvoices,
  set: (value) => {
    return proformaInvoiceStore.selectProformaInvoice(value)
  },
})

const proformaInvoiceColumns = computed(() => {
  return [
    {
      key: 'checkbox',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    {
      key: 'proforma_invoice_date',
      label: t('proforma_invoices.date'),
      thClass: 'extra',
      tdClass: 'font-medium',
    },
    { key: 'proforma_invoice_number', label: t('proforma_invoices.number') },
    { key: 'name', label: t('proforma_invoices.customer') },
    { key: 'expiry_date', label: t('proforma_invoices.expiry_date') },
    { key: 'status', label: t('proforma_invoices.status') },
    {
      key: 'total',
      label: t('proforma_invoices.total'),
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'actions',
      label: t('proforma_invoices.action'),
      tdClass: 'text-right text-sm font-medium',
      thClass: 'text-right',
      sortable: false,
    },
  ]
})

debouncedWatch(
  filters,
  () => {
    setFilters()
  },
  { debounce: 500 }
)

onUnmounted(() => {
  if (proformaInvoiceStore.selectAllField) {
    proformaInvoiceStore.selectAllProformaInvoices()
  }
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_PROFORMA_INVOICE,
    abilities.EDIT_PROFORMA_INVOICE,
    abilities.VIEW_PROFORMA_INVOICE,
    abilities.SEND_PROFORMA_INVOICE,
  ])
}

async function clearStatusSearch() {
  filters.status = ''
  refreshTable()
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  let data = {
    customer_id: filters.customer_id,
    status: filters.status,
    from_date: filters.from_date,
    to_date: filters.to_date,
    proforma_invoice_number: filters.proforma_invoice_number,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  isRequestOngoing.value = true

  let response = await proformaInvoiceStore.fetchProformaInvoices(data)

  isRequestOngoing.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

function setStatusFilter(val) {
  if (activeTab.value == val.title) {
    return true
  }

  activeTab.value = val.title

  switch (val.title) {
    case t('general.draft'):
      filters.status = 'DRAFT'
      break
    case t('general.sent'):
      filters.status = 'SENT'
      break
    case t('proforma_invoices.converted'):
      filters.status = 'CONVERTED'
      break
    default:
      filters.status = ''
      break
  }
}

function setFilters() {
  proformaInvoiceStore.$patch((state) => {
    state.selectedProformaInvoices = []
    state.selectAllField = false
  })

  tableKey.value += 1

  refreshTable()
}

function clearFilter() {
  filters.customer_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.proforma_invoice_number = ''

  activeTab.value = t('general.all')
}

async function removeMultipleProformaInvoices() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('proforma_invoices.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await proformaInvoiceStore.deleteMultipleProformaInvoices().then((res) => {
          if (res.data.success) {
            refreshTable()

            proformaInvoiceStore.$patch((state) => {
              state.selectedProformaInvoices = []
              state.selectAllField = false
            })
          }
        })
      }
    })
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }

  showFilters.value = !showFilters.value
}

function setActiveTab(val) {
  switch (val) {
    case 'DRAFT':
      activeTab.value = t('general.draft')
      break
    case 'SENT':
      activeTab.value = t('general.sent')
      break
    case 'VIEWED':
      activeTab.value = t('proforma_invoices.statuses.viewed')
      break
    case 'EXPIRED':
      activeTab.value = t('proforma_invoices.statuses.expired')
      break
    case 'CONVERTED':
      activeTab.value = t('proforma_invoices.converted')
      break
    case 'REJECTED':
      activeTab.value = t('proforma_invoices.statuses.rejected')
      break
    default:
      activeTab.value = t('general.all')
      break
  }
}
</script>
// CLAUDE-CHECKPOINT
