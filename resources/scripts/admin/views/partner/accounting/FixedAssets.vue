<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.fixed_assets', 'Fixed Assets Register')">
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="id"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <!-- Filters -->
    <div v-if="selectedCompanyId" class="p-4 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInputGroup :label="$t('accounting.fixed_assets.as_of_date', 'As of Date')">
          <BaseDatePicker v-model="asOfDate" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <div class="flex items-end gap-2">
          <BaseButton variant="primary" class="flex-1" :loading="isLoading" @click="loadAssets">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
            </template>
            {{ $t('general.load') }}
          </BaseButton>
          <BaseButton variant="primary-outline" :loading="isLoadingRegister" @click="loadRegister">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="DocumentTextIcon" />
            </template>
            {{ $t('accounting.fixed_assets.register', 'Register') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Assets List -->
    <div v-if="assets.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.name') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('accounting.fixed_assets.category_col', 'Category') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('accounting.fixed_assets.cost', 'Cost') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('accounting.fixed_assets.accum_depr', 'Accum. Depr.') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('accounting.fixed_assets.net_value', 'Net Value') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ $t('general.status') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50 cursor-pointer" @click="viewAsset(asset)">
              <td class="px-4 py-3">
                <div class="text-sm font-medium text-gray-900">{{ asset.name }}</div>
                <div v-if="asset.asset_code" class="text-xs text-gray-500">{{ asset.asset_code }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ categoryLabel(asset.category) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(asset.acquisition_cost) }}</td>
              <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatMoney(asset.accumulated_depreciation) }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium">{{ formatMoney(asset.net_book_value) }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="statusBadgeClass(asset.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                  {{ statusLabel(asset.status) }}
                </span>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr class="font-semibold">
              <td colspan="2" class="px-4 py-3 text-sm">{{ $t('general.total') }} ({{ assets.length }})</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(totalCost) }}</td>
              <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatMoney(totalDepreciation) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(totalNetValue) }}</td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Register View -->
    <div v-if="registerData" class="bg-white rounded-lg shadow overflow-hidden mt-6">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">{{ $t('accounting.fixed_assets.register_title', 'Fixed Assets Register (Регистар на основни средства)') }}</h3>
        <p class="text-sm text-gray-500">{{ $t('accounting.fixed_assets.as_of', 'As of') }}: {{ registerData.as_of_date }}</p>
      </div>
      <div class="p-6 overflow-x-auto">
        <div v-for="cat in registerData.categories" :key="cat.category" class="mb-6">
          <h4 class="text-sm font-bold text-gray-900 uppercase mb-2 bg-gray-100 px-3 py-2 rounded">{{ categoryLabel(cat.category) }}</h4>
          <table class="min-w-full divide-y divide-gray-200 mb-2">
            <thead>
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ $t('general.name') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ $t('general.date') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.cost', 'Cost') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.rate', 'Rate %') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.accum_depr', 'Accum. Depr.') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.net_value', 'Net Value') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="a in cat.assets" :key="a.id">
                <td class="px-3 py-1.5 text-sm text-gray-900">{{ a.name }}</td>
                <td class="px-3 py-1.5 text-sm text-gray-600">{{ a.acquisition_date }}</td>
                <td class="px-3 py-1.5 text-sm text-right">{{ formatMoney(a.acquisition_cost) }}</td>
                <td class="px-3 py-1.5 text-sm text-right text-gray-600">{{ a.depreciation_rate }}%</td>
                <td class="px-3 py-1.5 text-sm text-right text-red-600">{{ formatMoney(a.accumulated_depreciation) }}</td>
                <td class="px-3 py-1.5 text-sm text-right font-medium">{{ formatMoney(a.net_book_value) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr class="font-semibold text-sm">
                <td colspan="2" class="px-3 py-2">{{ $t('general.subtotal') }}</td>
                <td class="px-3 py-2 text-right">{{ formatMoney(cat.subtotal_cost) }}</td>
                <td class="px-3 py-2"></td>
                <td class="px-3 py-2 text-right text-red-600">{{ formatMoney(cat.subtotal_depreciation) }}</td>
                <td class="px-3 py-2 text-right">{{ formatMoney(cat.subtotal_net) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="bg-primary-50 border-2 border-primary-200 rounded-lg p-4 mt-4">
          <div class="grid grid-cols-3 gap-4 text-center">
            <div>
              <p class="text-xs text-primary-600 uppercase font-medium">{{ $t('accounting.fixed_assets.total_cost', 'Total Cost') }}</p>
              <p class="text-lg font-bold text-primary-900">{{ formatMoney(registerData.totals.acquisition_cost) }}</p>
            </div>
            <div>
              <p class="text-xs text-red-600 uppercase font-medium">{{ $t('accounting.fixed_assets.total_depr', 'Total Depreciation') }}</p>
              <p class="text-lg font-bold text-red-700">{{ formatMoney(registerData.totals.accumulated_depreciation) }}</p>
            </div>
            <div>
              <p class="text-xs text-green-600 uppercase font-medium">{{ $t('accounting.fixed_assets.total_net', 'Total Net Value') }}</p>
              <p class="text-lg font-bold text-green-700">{{ formatMoney(registerData.totals.net_book_value) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <BaseModal :show="showDetailModal" @close="showDetailModal = false" size="lg">
      <template #header>
        <h3 class="text-lg font-medium">{{ selectedAsset?.name }}</h3>
      </template>
      <div v-if="assetDetail" class="p-4 space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ $t('accounting.fixed_assets.cost', 'Cost') }}</p>
            <p class="text-sm font-semibold">{{ formatMoney(assetDetail.acquisition_cost) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ $t('accounting.fixed_assets.accum_depr', 'Accum. Depr.') }}</p>
            <p class="text-sm font-semibold text-red-600">{{ formatMoney(assetDetail.accumulated_depreciation) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ $t('accounting.fixed_assets.net_value', 'Net Value') }}</p>
            <p class="text-sm font-semibold text-green-700">{{ formatMoney(assetDetail.net_book_value) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500 uppercase">{{ $t('accounting.fixed_assets.monthly_depr', 'Monthly Depr.') }}</p>
            <p class="text-sm font-semibold">{{ formatMoney(assetDetail.monthly_depreciation) }}</p>
          </div>
        </div>
        <div v-if="assetDetail.depreciation_schedule && assetDetail.depreciation_schedule.length > 0">
          <h4 class="text-sm font-bold text-gray-900 mb-2">{{ $t('accounting.fixed_assets.schedule', 'Depreciation Schedule') }}</h4>
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">{{ $t('general.year') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.opening', 'Opening') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.depr_amount', 'Depreciation') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.accum_total', 'Accumulated') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500">{{ $t('accounting.fixed_assets.closing', 'Closing') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="row in assetDetail.depreciation_schedule" :key="row.year">
                <td class="px-3 py-1.5 font-medium">{{ row.year }}</td>
                <td class="px-3 py-1.5 text-right">{{ formatMoney(row.opening_value) }}</td>
                <td class="px-3 py-1.5 text-right text-red-600">{{ formatMoney(row.depreciation) }}</td>
                <td class="px-3 py-1.5 text-right text-red-600">{{ formatMoney(row.accumulated) }}</td>
                <td class="px-3 py-1.5 text-right font-medium">{{ formatMoney(row.closing_value) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="showDetailModal = false">{{ $t('general.close') }}</BaseButton>
      </template>
    </BaseModal>

    <!-- Empty State -->
    <div v-if="hasSearched && assets.length === 0 && !registerData" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="CubeIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('accounting.fixed_assets.no_assets', 'No fixed assets found') }}</p>
    </div>

    <!-- Select company -->
    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import moment from 'moment'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const assets = ref([])
const registerData = ref(null)
const assetDetail = ref(null)
const selectedAsset = ref(null)
const isLoading = ref(false)
const isLoadingRegister = ref(false)
const hasSearched = ref(false)
const showDetailModal = ref(false)
const asOfDate = ref(moment().format('YYYY-MM-DD'))

const companies = computed(() => consoleStore.managedCompanies || [])

const selectedCompanyCurrency = computed(() => {
  if (!selectedCompanyId.value) return 'MKD'
  const company = companies.value.find(c => c.id === selectedCompanyId.value)
  return company?.currency?.code || 'MKD'
})

const totalCost = computed(() => assets.value.reduce((sum, a) => sum + a.acquisition_cost, 0))
const totalDepreciation = computed(() => assets.value.reduce((sum, a) => sum + a.accumulated_depreciation, 0))
const totalNetValue = computed(() => assets.value.reduce((sum, a) => sum + a.net_book_value, 0))

const categoryLabels = {
  real_estate: t('accounting.fixed_assets.cat_real_estate', 'Недвижен имот'),
  buildings: t('accounting.fixed_assets.cat_buildings', 'Згради'),
  equipment: t('accounting.fixed_assets.cat_equipment', 'Опрема'),
  vehicles: t('accounting.fixed_assets.cat_vehicles', 'Возила'),
  computers_software: t('accounting.fixed_assets.cat_computers', 'Компјутери и софтвер'),
  other: t('accounting.fixed_assets.cat_other', 'Останато'),
}

onMounted(async () => {
  try {
    await consoleStore.fetchCompanies()
    if (companies.value.length > 0) {
      selectedCompanyId.value = companies.value[0].id
    }
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: t('errors.failed_to_load_companies') })
  }
})

function onCompanyChange() {
  assets.value = []
  registerData.value = null
  hasSearched.value = false
}

async function loadAssets() {
  if (!selectedCompanyId.value) return
  isLoading.value = true
  hasSearched.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/fixed-assets`)
    assets.value = response.data.data || []
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to load assets' })
  } finally {
    isLoading.value = false
  }
}

async function loadRegister() {
  if (!selectedCompanyId.value) return
  isLoadingRegister.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/fixed-assets/register`, {
      params: { as_of_date: asOfDate.value },
    })
    registerData.value = response.data.data
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to load register' })
  } finally {
    isLoadingRegister.value = false
  }
}

async function viewAsset(asset) {
  selectedAsset.value = asset
  showDetailModal.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/fixed-assets/${asset.id}`)
    assetDetail.value = response.data.data
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: 'Failed to load asset details' })
  }
}

function categoryLabel(category) {
  return categoryLabels[category] || category
}

function statusLabel(status) {
  const map = { active: 'Active', disposed: 'Disposed', fully_depreciated: 'Fully Depreciated' }
  return map[status] || status
}

function statusBadgeClass(status) {
  const map = {
    active: 'bg-green-100 text-green-800',
    disposed: 'bg-gray-100 text-gray-800',
    fully_depreciated: 'bg-yellow-100 text-yellow-800',
  }
  return map[status] || 'bg-gray-100 text-gray-800'
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + selectedCompanyCurrency.value
}
</script>

<!-- CLAUDE-CHECKPOINT -->
