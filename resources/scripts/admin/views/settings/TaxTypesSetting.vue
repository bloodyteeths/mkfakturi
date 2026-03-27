<template>
  <BaseSettingCard
    :title="$t('settings.tax_types.title')"
    :description="$t('settings.tax_types.description')"
  >
    <TaxTypeModal />

    <template v-if="userStore.hasAbilities(abilities.CREATE_TAX_TYPE)" #action>
      <BaseButton type="submit" variant="primary-outline" @click="openTaxModal">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ $t('settings.tax_types.add_new_tax') }}
      </BaseButton>
    </template>

    <BaseTable
      ref="table"
      class="mt-16"
      :data="fetchData"
      :columns="taxTypeColumns"
    >
    <template #cell-category="{ row }">
      <span
        v-if="row.data.category"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
        :class="categoryBadgeClass(row.data.category)"
      >
        {{ $t(`settings.tax_types.category_${row.data.category}`) }}
      </span>
      <span v-else class="text-gray-400">-</span>
    </template>
    <template #cell-calculation_type="{ row }">
      {{ $t(`settings.tax_types.${row.data.calculation_type}`) }}
    </template>
    <template #cell-amount="{ row }">
      <template v-if="row.data.calculation_type === 'percentage'">
        {{ row.data.percent }} %
      </template>
      <template v-else-if="row.data.calculation_type === 'fixed'">
        <BaseFormatMoney :amount="row.data.fixed_amount" :currency="defaultCurrency" />
      </template>
      <template v-else>
        -
      </template>
    </template>

      <template v-if="hasAtleastOneAbility()" #cell-actions="{ row }">
        <TaxTypeDropdown
          :row="row.data"
          :table="table"
          :load-data="refreshTable"
        />
      </template>
    </BaseTable>
    <div v-if="userStore.currentUser.is_owner">
      <BaseDivider class="mt-8 mb-2" />

      <BaseSwitchSection
        v-model="taxPerItemField"
        :disabled="salesTaxEnabled"
        :title="$t('settings.tax_types.tax_per_item')"
        :description="$t('settings.tax_types.tax_setting_description')"
      />
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { useTaxTypeStore } from '@/scripts/admin/stores/tax-type'
import { useModalStore } from '@/scripts/stores/modal'
import { computed, reactive, ref, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useModuleStore } from '@/scripts/admin/stores/module'

import TaxTypeDropdown from '@/scripts/admin/components/dropdowns/TaxTypeIndexDropdown.vue'
import TaxTypeModal from '@/scripts/admin/components/modal-components/TaxTypeModal.vue'
import abilities from '@/scripts/admin/stub/abilities'

const { t } = useI18n()
const utils = inject('utils')

const companyStore = useCompanyStore()
const taxTypeStore = useTaxTypeStore()
const modalStore = useModalStore()
const userStore = useUserStore()
const moduleStore = useModuleStore()
const table = ref(null)
const taxPerItemSetting = ref(companyStore.selectedCompanySettings.tax_per_item)
const defaultCurrency = computed(() => companyStore.selectedCompanyCurrency)

const taxTypeColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('settings.tax_types.tax_name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'category',
      label: t('settings.tax_types.category'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'calculation_type',
      label: t('settings.tax_types.calculation_type'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'amount',
      label: t('settings.tax_types.amount'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'actions',
      label: '',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

const salesTaxEnabled = computed(() => {
  return (
    companyStore.selectedCompanySettings.sales_tax_us_enabled === 'YES' &&
    moduleStore.salesTaxUSEnabled
  )
})

const taxPerItemField = computed({
  get: () => {
    return taxPerItemSetting.value === 'YES'
  },
  set: async (newValue) => {
    const value = newValue ? 'YES' : 'NO'

    let data = {
      settings: {
        tax_per_item: value,
      },
    }

    taxPerItemSetting.value = value

    await companyStore.updateCompanySettings({
      data,
      message: 'general.setting_updated',
    })
  },
})

function categoryBadgeClass(category) {
  const classes = {
    standard: 'bg-blue-100 text-blue-800',
    reduced: 'bg-green-100 text-green-800',
    hospitality: 'bg-purple-100 text-purple-800',
    zero_rated: 'bg-yellow-100 text-yellow-800',
    exempt: 'bg-gray-100 text-gray-800',
    reverse_charge: 'bg-red-100 text-red-800',
  }
  return classes[category] || 'bg-gray-100 text-gray-800'
}

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_TAX_TYPE,
    abilities.EDIT_TAX_TYPE,
  ])
}

async function fetchData({ page, filter, sort }) {
  let data = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  let response = await taxTypeStore.fetchTaxTypes(data)

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 5,
    },
  }
}

async function refreshTable() {
  table.value && table.value.refresh()
}

function openTaxModal() {
  modalStore.openModal({
    title: t('settings.tax_types.add_tax'),
    componentName: 'TaxTypeModal',
    size: 'sm',
    refreshData: table.value && table.value.refresh,
  })
}
</script>
