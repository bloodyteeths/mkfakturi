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

        <!-- Document Generation Dropdown -->
        <BaseDropdown v-if="supplier">
          <template #activator>
            <BaseButton variant="primary-outline" class="mr-3">
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

          <BaseDropdownItem @click="openStatementModal">
            <BaseIcon name="ClipboardDocumentListIcon" class="w-5 h-5 mr-3 text-gray-400" />
            {{ $t('suppliers.statement') }}
          </BaseDropdownItem>
        </BaseDropdown>

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

    <!-- Supplier View Sidebar -->
    <SupplierViewSidebar />

    <!-- Tab Navigation -->
    <div v-if="supplier" class="mt-5 border-b border-gray-200">
      <nav class="flex -mb-px space-x-6">
        <button
          v-for="tab in tabs"
          :key="tab.key"
          :class="[
            'py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap',
            activeTab === tab.key
              ? 'border-primary-500 text-primary-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
          ]"
          @click="activeTab = tab.key"
        >
          {{ tab.label }}
          <span
            v-if="tab.badge"
            class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div v-if="supplier">
      <!-- Overview Tab -->
      <div v-show="activeTab === 'overview'">
        <SupplierChart />
      </div>

      <!-- Ledger Tab -->
      <div v-show="activeTab === 'ledger'">
        <SupplierLedgerCard />
      </div>

      <!-- Open Items (IOS) Tab -->
      <div v-show="activeTab === 'ios'">
        <SupplierIosCard />
      </div>
    </div>

    <!-- Statement Modal -->
    <div
      v-if="showStatementModal"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click.self="showStatementModal = false"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ $t('suppliers.statement') }} — {{ supplier?.name }}
          </h3>
          <button
            class="text-gray-400 hover:text-gray-600"
            @click="showStatementModal = false"
          >
            <BaseIcon name="XMarkIcon" class="w-5 h-5" />
          </button>
        </div>

        <div v-if="statementLoading" class="px-6 py-8 text-center text-gray-400">
          {{ $t('general.loading') }}...
        </div>

        <div v-else-if="statementData" class="px-6 py-5">
          <div class="space-y-4">
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-sm text-gray-600">{{ $t('suppliers.opening_balance') }}</span>
              <span class="text-sm font-semibold text-gray-900">{{ formatAmount(statementData.opening_balance) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-sm text-gray-600">{{ $t('suppliers.bills') }}</span>
              <span class="text-sm font-semibold text-red-600">{{ formatAmount(statementData.bills_total) }}</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
              <span class="text-sm text-gray-600">{{ $t('suppliers.payments') }}</span>
              <span class="text-sm font-semibold text-green-600">{{ formatAmount(statementData.payments_total) }}</span>
            </div>
            <div class="flex justify-between items-center py-3 bg-gray-50 rounded-lg px-3 -mx-3">
              <span class="text-sm font-semibold text-gray-900">{{ $t('suppliers.closing_balance') }}</span>
              <span
                :class="[
                  'text-lg font-bold',
                  statementData.closing_balance > 0 ? 'text-red-600' : statementData.closing_balance < 0 ? 'text-green-600' : 'text-gray-600'
                ]"
              >
                {{ formatAmount(statementData.closing_balance) }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex justify-end px-6 py-4 border-t border-gray-200">
          <BaseButton variant="primary-outline" size="sm" @click="showStatementModal = false">
            {{ $t('general.close') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useUserStore } from '@/scripts/admin/stores/user'
import SupplierDropdown from '@/scripts/admin/components/dropdowns/SupplierIndexDropdown.vue'
import SupplierViewSidebar from './partials/SupplierViewSidebar.vue'
import SupplierChart from './partials/SupplierChart.vue'
import SupplierLedgerCard from './partials/SupplierLedgerCard.vue'
import SupplierIosCard from './partials/SupplierIosCard.vue'
import abilities from '@/scripts/admin/stub/abilities'
import axios from 'axios'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const suppliersStore = useSuppliersStore()
const userStore = useUserStore()

const supplier = computed(() => suppliersStore.selectedSupplier)

const pageTitle = computed(() => {
  return supplier.value ? supplier.value.name : ''
})

const isLoading = computed(() => {
  return suppliersStore.isFetchingView || false
})

// Tabs
const activeTab = ref('overview')

const tabs = computed(() => [
  { key: 'overview', label: t('suppliers.tab_overview') },
  { key: 'ledger', label: t('suppliers.ledger_card') },
  {
    key: 'ios',
    label: t('suppliers.tab_open_items'),
    badge: supplier.value?.due_amount > 0 ? '!' : null,
  },
])

// Statement modal
const showStatementModal = ref(false)
const statementLoading = ref(false)
const statementData = ref(null)

function formatAmount(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount / 100)
}

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

async function openStatementModal() {
  showStatementModal.value = true
  statementLoading.value = true
  statementData.value = null
  try {
    const res = await axios.get(`/suppliers/${route.params.id}/statement`)
    statementData.value = res.data.data
  } catch (e) {
    statementData.value = null
  } finally {
    statementLoading.value = false
  }
}

// Fix D: Fetch supplier on mount if not already loaded (direct URL navigation)
onMounted(async () => {
  if (!supplier.value && route.params.id) {
    await suppliersStore.fetchSupplier(route.params.id)
  }
})
</script>
