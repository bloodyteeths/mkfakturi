<template>
  <BasePage class="xl:pl-96">
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('suppliers.title')" to="/admin/suppliers" />
        <BaseBreadcrumbItem
          :title="supplier?.name || $t('suppliers.view_supplier')"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <router-link
          v-if="userStore.hasAbilities(abilities.EDIT_SUPPLIER)"
          :to="`/admin/suppliers/${route.params.id}/edit`"
        >
          <BaseButton
            class="mr-3"
            variant="primary-outline"
            :content-loading="isLoading"
          >
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>

        <SupplierDropdown
          v-if="hasAtleastOneAbility()"
          :class="{
            'ml-3': isLoading,
          }"
          :row="supplier || {}"
          :load-data="refreshData"
        />
      </template>
    </BasePageHeader>

    <!-- Document Generation -->
    <div v-if="supplier" class="mt-5 flex flex-wrap items-center gap-2">
      <BaseDropdown>
        <template #activator>
          <BaseButton variant="primary-outline" size="sm">
            <template #left="slotProps">
              <BaseIcon name="DocumentTextIcon" :class="slotProps.class" />
            </template>
            {{ $t('suppliers.generate_document') }}
          </BaseButton>
        </template>

        <BaseDropdownItem @click="downloadIos">
          <BaseIcon name="DocumentTextIcon" class="w-5 h-5 mr-3 text-gray-400" />
          {{ $t('suppliers.ios_title') }}
        </BaseDropdownItem>

        <BaseDropdownItem @click="downloadLedger">
          <BaseIcon name="TableCellsIcon" class="w-5 h-5 mr-3 text-gray-400" />
          {{ $t('suppliers.ledger_card') }}
        </BaseDropdownItem>

        <BaseDropdownItem @click="downloadPp30">
          <BaseIcon name="BanknotesIcon" class="w-5 h-5 mr-3 text-gray-400" />
          {{ $t('suppliers.pp30') }}
        </BaseDropdownItem>

        <BaseDropdownItem @click="downloadStatement">
          <BaseIcon name="ClipboardDocumentListIcon" class="w-5 h-5 mr-3 text-gray-400" />
          {{ $t('suppliers.statement') }}
        </BaseDropdownItem>
      </BaseDropdown>
    </div>

    <!-- Supplier View Sidebar -->
    <SupplierViewSidebar />

    <!-- Chart -->
    <SupplierChart />

    <!-- Ledger Card -->
    <SupplierLedgerCard />

    <!-- IOS Card -->
    <SupplierIosCard />
  </BasePage>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useNotificationStore } from '@/scripts/stores/notification'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
import SupplierViewSidebar from './partials/SupplierViewSidebar.vue'
import SupplierChart from './partials/SupplierChart.vue'
import SupplierLedgerCard from './partials/SupplierLedgerCard.vue'
import SupplierIosCard from './partials/SupplierIosCard.vue'
import abilities from '@/scripts/admin/stub/abilities'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const suppliersStore = useSuppliersStore()
const userStore = useUserStore()
const notificationStore = useNotificationStore()

const supplier = computed(() => suppliersStore.selectedSupplier)

const pageTitle = computed(() => {
  return supplier.value ? supplier.value.name : ''
})

const isLoading = computed(() => {
  return suppliersStore.isFetchingView || false
})

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_SUPPLIER,
    abilities.EDIT_SUPPLIER,
  ])
}

function refreshData() {
  router.push('/admin/suppliers')
}

function downloadIos() {
  window.open(`/api/v1/suppliers/${route.params.id}/ios/pdf?download=true`, '_blank')
}

function downloadLedger() {
  const now = new Date()
  const from = `${now.getFullYear()}-01-01`
  const to = `${now.getFullYear()}-12-31`
  window.open(`/api/v1/suppliers/${route.params.id}/ledger/pdf?from_date=${from}&to_date=${to}&download=true`, '_blank')
}

function downloadPp30() {
  window.open(`/api/v1/suppliers/${route.params.id}/pp30`, '_blank')
}

function downloadStatement() {
  import('axios').then(({ default: axios }) => {
    axios.get(`/suppliers/${route.params.id}/statement`).then((res) => {
      const d = res.data.data
      const fmt = (v) => new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 0, maximumFractionDigits: 2 }).format(v / 100)
      notificationStore.showNotification({
        type: 'info',
        message: `${t('suppliers.opening_balance')}: ${fmt(d.opening_balance)} | ${t('suppliers.bills')}: ${fmt(d.bills_total)} | ${t('suppliers.payments')}: ${fmt(d.payments_total)} | ${t('suppliers.closing_balance')}: ${fmt(d.closing_balance)}`,
      })
    })
  })
}
</script>
