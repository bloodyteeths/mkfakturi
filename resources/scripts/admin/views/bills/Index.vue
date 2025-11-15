<template>
  <BasePage>
    <BasePageHeader :title="$t('bills.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('bills.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-if="userStore.hasAbilities(abilities.CREATE_BILL)"
          variant="primary"
          @click="$router.push('/admin/bills/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('bills.new_bill') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseFilterWrapper v-show="showFilters" @clear="clearFilter">
      <BaseInputGroup :label="$t('bills.supplier')">
        <BaseInput v-model="filters.supplier" />
      </BaseInputGroup>
      <BaseInputGroup :label="$t('bills.bill_number')">
        <BaseInput v-model="filters.bill_number" />
      </BaseInputGroup>
      <BaseInputGroup :label="$t('general.from')">
        <BaseDatePicker v-model="filters.from_date" />
      </BaseInputGroup>
      <BaseInputGroup :label="$t('general.to')">
        <BaseDatePicker v-model="filters.to_date" />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-if="!billsStore.billTotalCount && !billsStore.isFetchingList"
      :title="$t('bills.no_bills')"
      :description="$t('bills.empty_description')"
    >
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

    <div v-else class="relative table-container">
      <BaseTable
        ref="tableComponent"
        :data="billsStore.bills"
        :columns="columns"
        :meta="{ total: billsStore.billTotalCount }"
        :loading="billsStore.isFetchingList"
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
          <BaseFormatMoney
            :amount="row.data.total"
            :currency="row.data.currency"
          />
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
              v-if="userStore.hasAbilities(abilities.EDIT_BILL)"
              @click="$router.push(`/admin/bills/${row.data.id}/edit`)"
            >
              {{ $t('general.edit') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import abilities from '@/scripts/admin/stub/abilities'
import { useBillsStore } from '@/scripts/admin/stores/bills'
import { useUserStore } from '@/scripts/admin/stores/user'

const { t } = useI18n()
const billsStore = useBillsStore()
const userStore = useUserStore()

const showFilters = ref(false)
const tableComponent = ref(null)

const filters = reactive({
  bill_number: '',
  supplier: '',
  from_date: '',
  to_date: '',
  page: 1,
  limit: 10,
})

const columns = [
  { key: 'bill_date', label: t('bills.bill_date') },
  { key: 'bill_number', label: t('bills.bill_number') },
  { key: 'supplier', label: t('bills.supplier') },
  { key: 'total', label: t('bills.total') },
  { key: 'actions', label: '', sortable: false, tdClass: 'text-right' },
]

function fetchData(params) {
  filters.page = params?.page ?? filters.page
  filters.limit = params?.limit ?? filters.limit
  const query = {
    bill_number: filters.bill_number,
    supplier_name: filters.supplier,
    from_date: filters.from_date,
    to_date: filters.to_date,
    page: filters.page,
    limit: filters.limit,
  }
  billsStore.fetchBills(query)
}

function clearFilter() {
  filters.bill_number = ''
  filters.supplier = ''
  filters.from_date = ''
  filters.to_date = ''
  fetchData()
}

onMounted(() => {
  fetchData()
})
</script>

