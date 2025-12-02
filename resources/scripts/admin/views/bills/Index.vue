<template>
  <BasePage>
    <BasePageHeader :title="$t('bills.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="billsStore.billTotalCount"
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

        <router-link
          v-if="userStore.hasAbilities(abilities.CREATE_BILL)"
          to="bills/create"
        >
          <BaseButton variant="primary" class="ml-4">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('bills.new_bill') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="$t('bills.supplier')">
        <BaseSupplierSelectInput
          v-model="filters.supplier_id"
          :placeholder="$t('bills.type_or_click')"
          value-prop="id"
          label="name"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('bills.status')">
        <BaseMultiselect
          v-model="filters.status"
          :groups="true"
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

      <BaseInputGroup :label="$t('bills.bill_number')">
        <BaseInput v-model="filters.bill_number">
          <template #left="slotProps">
            <BaseIcon name="HashtagIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('bills.no_bills')"
      :description="$t('bills.list_of_bills')"
    >
      <MoonwalkerIcon class="mt-5 mb-4" />
      <template
        v-if="userStore.hasAbilities(abilities.CREATE_BILL)"
        #actions
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/bills/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('bills.add_new_bill') }}
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
          <BaseTab :title="$t('general.due')" filter="OVERDUE" />
          <BaseTab :title="$t('general.paid')" filter="PAID" />
        </BaseTabGroup>

        <BaseDropdown
          v-if="
            billsStore.selectedBills.length &&
            userStore.hasAbilities(abilities.DELETE_BILL)
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

          <BaseDropdownItem @click="removeMultipleBills">
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <!-- Mobile: Card View (< 768px) -->
      <div v-if="billListData.length" class="block md:hidden mt-6">
        <BillCard
          v-for="bill in billListData"
          :key="bill.id"
          :bill="bill"
          :selectable="userStore.hasAbilities(abilities.DELETE_BILL)"
          :is-selected="billsStore.selectedBills.includes(bill.id)"
          @toggle-select="toggleBillSelection"
        />
      </div>

      <!-- Desktop: Table View (>= 768px) -->
      <BaseTable
        ref="table"
        :data="fetchData"
        :columns="billColumns"
        :placeholder-count="billsStore.billTotalCount >= 20 ? 10 : 5"
        :key="tableKey"
        class="mt-10 hidden md:block"
      >
        <!-- Select All Checkbox -->
        <template #header>
          <div class="absolute items-center left-6 top-2.5 select-none">
            <BaseCheckbox
              v-model="billsStore.selectAllField"
              variant="primary"
              @change="billsStore.selectAllBills"
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

        <template #cell-supplier="{ row }">
          <BaseText :text="row.data.supplier?.name || '-'" />
        </template>

        <!-- Bill Number  -->
        <template #cell-bill_number="{ row }">
          <router-link
            :to="{ path: `bills/${row.data.id}/view` }"
            class="font-medium text-primary-500"
          >
            {{ row.data.bill_number }}
          </router-link>
        </template>

        <!-- Bill date  -->
        <template #cell-bill_date="{ row }">
          {{ row.data.formatted_bill_date }}
        </template>

        <!-- Bill Total  -->
        <template #cell-total="{ row }">
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.currency"
          />
        </template>

        <!-- Bill status  -->
        <template #cell-status="{ row }">
          <BaseBillStatusBadge :status="row.data.status" class="px-3 py-1">
            <BaseBillStatusLabel :status="row.data.status" />
          </BaseBillStatusBadge>
        </template>

        <!-- Due Amount + Paid Status  -->
        <template #cell-due_amount="{ row }">
          <div class="flex justify-between">
            <BaseFormatMoney
              :amount="row.data.due_amount"
              :currency="row.data.currency"
            />

            <BaseBillPaidStatusBadge
              v-if="row.data.overdue"
              status="OVERDUE"
              class="px-1 py-0.5 ml-2"
            >
              {{ $t('bills.overdue') }}
            </BaseBillPaidStatusBadge>

            <BaseBillPaidStatusBadge
              :status="row.data.paid_status"
              class="px-1 py-0.5 ml-2"
            >
              <BaseBillStatusLabel :status="row.data.paid_status" />
            </BaseBillPaidStatusBadge>
          </div>
        </template>

        <!-- Actions -->
        <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
          <BillIndexDropdown :row="row.data" :table="table" />
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, onUnmounted, onMounted, reactive, ref, inject } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'
import abilities from '@/scripts/admin/stub/abilities'
import { debouncedWatch } from '@vueuse/core'

import MoonwalkerIcon from '@/scripts/components/icons/empty/MoonwalkerIcon.vue'
import BillIndexDropdown from '@/scripts/admin/components/dropdowns/BillIndexDropdown.vue'
import BaseBillStatusLabel from "@/scripts/components/base/BaseBillStatusLabel.vue"
import BillCard from '@/scripts/admin/components/BillCard.vue'

// Stores
const billsStore = useBillsStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const { t } = useI18n()

// Local State
const utils = inject('$utils')
const table = ref(null)
const tableKey = ref(0)
const showFilters = ref(false)

const status = ref([
  {
    label: t('bills.status'),
    options: [
      {label: t('general.draft'), value: 'DRAFT'},
      {label: t('general.due'), value: 'DUE'},
      {label: t('general.sent'), value: 'SENT'},
      {label: t('bills.viewed'), value: 'VIEWED'},
      {label: t('bills.completed'), value: 'COMPLETED'}
    ],
  },
  {
    label: t('bills.paid_status'),
    options: [
      {label: t('bills.unpaid'), value: 'UNPAID'},
      {label: t('bills.paid'), value: 'PAID'},
      {label: t('bills.partially_paid'), value: 'PARTIALLY_PAID'}],
  },
  ,
])
const isRequestOngoing = ref(true)
const activeTab = ref('general.draft')
const router = useRouter()
const route = useRoute()
const userStore = useUserStore()

let filters = reactive({
  supplier_id: '',
  status: '',
  from_date: '',
  to_date: '',
  bill_number: '',
  project_id: '',
})

// Initialize filters from query params
onMounted(() => {
  if (route.query.project_id) {
    filters.project_id = route.query.project_id
  }
})

const billListData = ref([])

const showEmptyScreen = computed(
  () => !billsStore.billTotalCount && !isRequestOngoing.value
)

const selectField = computed({
  get: () => billsStore.selectedBills,
  set: (value) => {
    return billsStore.selectBill(value)
  },
})

const billColumns = computed(() => {
  return [
    {
      key: 'checkbox',
      thClass: 'extra w-10',
      tdClass: 'font-medium text-gray-900',
      placeholderClass: 'w-10',
      sortable: false,
    },
    {
      key: 'bill_date',
      label: t('bills.date'),
      thClass: 'extra',
      tdClass: 'font-medium',
    },
    { key: 'bill_number', label: t('bills.number') },
    { key: 'supplier', label: t('bills.supplier') },
    { key: 'status', label: t('bills.status') },
    {
      key: 'due_amount',
      label: t('dashboard.recent_bills_card.amount_due'),
    },
    {
      key: 'total',
      label: t('bills.total'),
      tdClass: 'font-medium text-gray-900',
    },

    {
      key: 'actions',
      label: t('bills.action'),
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
  if (billsStore.selectAllField) {
    billsStore.selectAllBills()
  }
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_BILL,
    abilities.EDIT_BILL,
    abilities.VIEW_BILL,
  ])
}

async function clearStatusSearch(removedOption, id) {
  filters.status = ''
  refreshTable()
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  let data = {
    supplier_id: filters.supplier_id,
    status: filters.status,
    from_date: filters.from_date,
    to_date: filters.to_date,
    bill_number: filters.bill_number,
    project_id: filters.project_id,
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  console.log(data)

  isRequestOngoing.value = true

  let response = await billsStore.fetchBills(data)
  console.log('API response:', response.data.data)

  // Store data for mobile card view
  billListData.value = response.data.data

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

function toggleBillSelection(billId) {
  billsStore.selectBill([billId])
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

    case t('general.due'):
      filters.status = 'OVERDUE'
      break

    case t('general.paid'):
      filters.status = 'PAID'
      break

    default:
      filters.status = ''
      break
  }
}

function setFilters() {
  billsStore.$patch((state) => {
    state.selectedBills = []
    state.selectAllField = false
  })

  tableKey.value += 1

  refreshTable()
}

function clearFilter() {
  filters.supplier_id = ''
  filters.status = ''
  filters.from_date = ''
  filters.to_date = ''
  filters.bill_number = ''

  activeTab.value = t('general.all')
}

async function removeMultipleBills() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('bills.confirm_delete'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        await billsStore.deleteMultipleBills().then((res) => {
          if (res.data.success) {
            refreshTable()

            billsStore.$patch((state) => {
              state.selectedBills = []
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

    case 'DUE':
      activeTab.value = t('general.due')
      break

    case 'OVERDUE':
      activeTab.value = t('general.due')
      break

    case 'COMPLETED':
      activeTab.value = t('bills.completed')
      break

    case 'PAID':
      activeTab.value = t('bills.paid')
      break

    case 'UNPAID':
      activeTab.value = t('bills.unpaid')
      break

    case 'PARTIALLY_PAID':
      activeTab.value = t('bills.partially_paid')
      break

    case 'VIEWED':
      activeTab.value = t('bills.viewed')
      break

    default:
      activeTab.value = t('general.all')
      break
  }
}
</script>
// CLAUDE-CHECKPOINT
