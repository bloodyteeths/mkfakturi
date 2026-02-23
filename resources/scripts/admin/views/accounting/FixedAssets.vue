<template>
  <BasePage>
    <BasePageHeader :title="$t('accounting.fixed_assets.title', 'Fixed Assets Register')">
      <template #actions>
        <BaseButton variant="primary" @click="showCreateModal = true">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlusIcon" />
          </template>
          {{ $t('accounting.fixed_assets.add', 'Add Asset') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <div class="p-4 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <BaseInputGroup :label="$t('accounting.fixed_assets.status_filter', 'Status')">
          <BaseMultiselect
            v-model="filterStatus"
            :options="statusOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('general.all')"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.category_filter', 'Category')">
          <BaseMultiselect
            v-model="filterCategory"
            :options="categoryOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('general.all')"
          />
        </BaseInputGroup>
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
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="asset in assets" :key="asset.id" class="hover:bg-gray-50">
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
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-1">
                  <BaseButton size="sm" variant="primary-outline" @click="viewAsset(asset)">
                    <BaseIcon name="EyeIcon" class="h-4 w-4" />
                  </BaseButton>
                  <BaseButton v-if="asset.status === 'active'" size="sm" variant="primary-outline" @click="editAsset(asset)">
                    <BaseIcon name="PencilIcon" class="h-4 w-4" />
                  </BaseButton>
                  <BaseButton v-if="asset.status === 'active'" size="sm" variant="danger-outline" @click="confirmDispose(asset)">
                    <BaseIcon name="ArchiveBoxXMarkIcon" class="h-4 w-4" />
                  </BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
          <tfoot class="bg-gray-50">
            <tr class="font-semibold">
              <td colspan="2" class="px-4 py-3 text-sm">{{ $t('general.total') }} ({{ assets.length }})</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(totalCost) }}</td>
              <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatMoney(totalDepreciation) }}</td>
              <td class="px-4 py-3 text-sm text-right">{{ formatMoney(totalNetValue) }}</td>
              <td colspan="2"></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="hasSearched && assets.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
      <BaseIcon name="CubeIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('accounting.fixed_assets.no_assets', 'No fixed assets found') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ $t('accounting.fixed_assets.no_assets_hint', 'Add your first fixed asset to start tracking depreciation.') }}</p>
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

        <!-- Grand Totals -->
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

    <!-- Create/Edit Modal -->
    <BaseModal :show="showCreateModal || showEditModal" @close="closeModal">
      <template #header>
        <h3 class="text-lg font-medium">
          {{ showEditModal ? $t('accounting.fixed_assets.edit', 'Edit Fixed Asset') : $t('accounting.fixed_assets.add', 'Add Fixed Asset') }}
        </h3>
      </template>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
        <BaseInputGroup :label="$t('general.name')" required class="md:col-span-2">
          <BaseInput v-model="form.name" :placeholder="$t('accounting.fixed_assets.name_placeholder', 'e.g. Dell Latitude 5540')" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.asset_code', 'Asset Code')">
          <BaseInput v-model="form.asset_code" placeholder="OS-001" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.category_col', 'Category')" required>
          <BaseMultiselect v-model="form.category" :options="categoryOptions" label="label" value-prop="value" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.acquisition_date', 'Acquisition Date')" required>
          <BaseDatePicker v-model="form.acquisition_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.cost', 'Acquisition Cost')" required>
          <BaseInput v-model="form.acquisition_cost" type="number" step="0.01" min="0.01" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.residual', 'Residual Value')">
          <BaseInput v-model="form.residual_value" type="number" step="0.01" min="0" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.useful_life', 'Useful Life (months)')" required>
          <BaseInput v-model="form.useful_life_months" type="number" min="1" max="1200" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.method', 'Depreciation Method')">
          <BaseMultiselect v-model="form.depreciation_method" :options="methodOptions" label="label" value-prop="value" />
        </BaseInputGroup>
        <BaseInputGroup v-if="accounts.length > 0" :label="$t('accounting.fixed_assets.gl_account', 'GL Account')">
          <BaseMultiselect
            v-model="form.account_id"
            :options="accounts"
            :custom-label="formatAccountLabel"
            value-prop="id"
            :searchable="true"
            :can-deselect="true"
          />
        </BaseInputGroup>
        <BaseInputGroup v-if="accounts.length > 0" :label="$t('accounting.fixed_assets.depr_account', 'Depreciation Account')">
          <BaseMultiselect
            v-model="form.depreciation_account_id"
            :options="accounts"
            :custom-label="formatAccountLabel"
            value-prop="id"
            :searchable="true"
            :can-deselect="true"
          />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('general.notes')" class="md:col-span-2">
          <BaseTextarea v-model="form.notes" :rows="2" />
        </BaseInputGroup>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="closeModal">{{ $t('general.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="isSaving" @click="saveAsset">
          {{ showEditModal ? $t('general.update') : $t('general.save') }}
        </BaseButton>
      </template>
    </BaseModal>

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

        <!-- Depreciation Schedule -->
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

    <!-- Dispose Modal -->
    <BaseModal :show="showDisposeModal" @close="showDisposeModal = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ $t('accounting.fixed_assets.dispose_title', 'Dispose Asset') }}</h3>
      </template>
      <div class="p-4 space-y-4">
        <p class="text-sm text-gray-600">{{ $t('accounting.fixed_assets.dispose_confirm', 'Are you sure you want to dispose of this asset?') }}</p>
        <p v-if="selectedAsset" class="text-sm font-medium">{{ selectedAsset.name }}</p>
        <BaseInputGroup :label="$t('accounting.fixed_assets.disposal_date', 'Disposal Date')" required>
          <BaseDatePicker v-model="disposeForm.disposal_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
        </BaseInputGroup>
        <BaseInputGroup :label="$t('accounting.fixed_assets.disposal_amount', 'Sale Amount')">
          <BaseInput v-model="disposeForm.disposal_amount" type="number" step="0.01" min="0" />
        </BaseInputGroup>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="showDisposeModal = false">{{ $t('general.cancel') }}</BaseButton>
        <BaseButton variant="danger" :loading="isDisposing" @click="disposeAsset">
          {{ $t('accounting.fixed_assets.dispose', 'Dispose') }}
        </BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import moment from 'moment'

const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const assets = ref([])
const registerData = ref(null)
const assetDetail = ref(null)
const selectedAsset = ref(null)
const accounts = ref([])
const isLoading = ref(false)
const isLoadingRegister = ref(false)
const isSaving = ref(false)
const isDisposing = ref(false)
const hasSearched = ref(false)

const showCreateModal = ref(false)
const showEditModal = ref(false)
const showDetailModal = ref(false)
const showDisposeModal = ref(false)

const filterStatus = ref(null)
const filterCategory = ref(null)
const asOfDate = ref(moment().format('YYYY-MM-DD'))

const defaultForm = {
  name: '',
  asset_code: '',
  category: 'equipment',
  acquisition_date: moment().format('YYYY-MM-DD'),
  acquisition_cost: '',
  residual_value: '0',
  useful_life_months: '60',
  depreciation_method: 'straight_line',
  account_id: null,
  depreciation_account_id: null,
  notes: '',
}
const form = ref({ ...defaultForm })

const disposeForm = ref({
  disposal_date: moment().format('YYYY-MM-DD'),
  disposal_amount: '0',
})

const categoryOptions = [
  { label: t('accounting.fixed_assets.cat_real_estate', 'Недвижен имот'), value: 'real_estate' },
  { label: t('accounting.fixed_assets.cat_buildings', 'Згради'), value: 'buildings' },
  { label: t('accounting.fixed_assets.cat_equipment', 'Опрема'), value: 'equipment' },
  { label: t('accounting.fixed_assets.cat_vehicles', 'Возила'), value: 'vehicles' },
  { label: t('accounting.fixed_assets.cat_computers', 'Компјутери и софтвер'), value: 'computers_software' },
  { label: t('accounting.fixed_assets.cat_other', 'Останато'), value: 'other' },
]

const statusOptions = [
  { label: t('accounting.fixed_assets.status_active', 'Active'), value: 'active' },
  { label: t('accounting.fixed_assets.status_disposed', 'Disposed'), value: 'disposed' },
  { label: t('accounting.fixed_assets.status_depreciated', 'Fully Depreciated'), value: 'fully_depreciated' },
]

const methodOptions = [
  { label: t('accounting.fixed_assets.method_straight', 'Straight-Line (Праволиниски)'), value: 'straight_line' },
  { label: t('accounting.fixed_assets.method_declining', 'Declining Balance (Дегресивен)'), value: 'declining_balance' },
]

const totalCost = computed(() => assets.value.reduce((sum, a) => sum + a.acquisition_cost, 0))
const totalDepreciation = computed(() => assets.value.reduce((sum, a) => sum + a.accumulated_depreciation, 0))
const totalNetValue = computed(() => assets.value.reduce((sum, a) => sum + a.net_book_value, 0))

// Load accounts on mount
loadAccounts()

async function loadAccounts() {
  try {
    const response = await window.axios.get('/accounting/accounts')
    accounts.value = response.data?.accounts || response.data?.data || []
  } catch {
    // Silently fail - accounts dropdown just won't show
  }
}

async function loadAssets() {
  isLoading.value = true
  hasSearched.value = true
  try {
    const params = {}
    if (filterStatus.value) params.status = filterStatus.value
    if (filterCategory.value) params.category = filterCategory.value
    const response = await window.axios.get('/accounting/fixed-assets', { params })
    assets.value = response.data.data || []
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to load assets' })
  } finally {
    isLoading.value = false
  }
}

async function loadRegister() {
  isLoadingRegister.value = true
  try {
    const response = await window.axios.get('/accounting/fixed-assets/register', {
      params: { as_of_date: asOfDate.value },
    })
    registerData.value = response.data.data
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to load register' })
  } finally {
    isLoadingRegister.value = false
  }
}

async function saveAsset() {
  isSaving.value = true
  try {
    const payload = { ...form.value }
    if (showEditModal.value && selectedAsset.value) {
      await window.axios.put(`/accounting/fixed-assets/${selectedAsset.value.id}`, payload)
      notificationStore.showNotification({ type: 'success', message: t('accounting.fixed_assets.updated', 'Asset updated') })
    } else {
      await window.axios.post('/accounting/fixed-assets', payload)
      notificationStore.showNotification({ type: 'success', message: t('accounting.fixed_assets.created', 'Asset created') })
    }
    closeModal()
    loadAssets()
  } catch (error) {
    const message = error.response?.data?.message || Object.values(error.response?.data?.errors || {}).flat().join(', ') || 'Failed to save'
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isSaving.value = false
  }
}

async function viewAsset(asset) {
  selectedAsset.value = asset
  showDetailModal.value = true
  try {
    const response = await window.axios.get(`/accounting/fixed-assets/${asset.id}`)
    assetDetail.value = response.data.data
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: 'Failed to load asset details' })
  }
}

function editAsset(asset) {
  selectedAsset.value = asset
  form.value = {
    name: asset.name,
    asset_code: asset.asset_code || '',
    category: asset.category,
    acquisition_date: asset.acquisition_date,
    acquisition_cost: asset.acquisition_cost,
    residual_value: asset.residual_value,
    useful_life_months: asset.useful_life_months,
    depreciation_method: asset.depreciation_method,
    account_id: asset.account?.id || null,
    depreciation_account_id: asset.depreciation_account?.id || null,
    notes: asset.notes || '',
  }
  showEditModal.value = true
}

function confirmDispose(asset) {
  selectedAsset.value = asset
  disposeForm.value = { disposal_date: moment().format('YYYY-MM-DD'), disposal_amount: '0' }
  showDisposeModal.value = true
}

async function disposeAsset() {
  if (!selectedAsset.value) return
  isDisposing.value = true
  try {
    await window.axios.post(`/accounting/fixed-assets/${selectedAsset.value.id}/dispose`, disposeForm.value)
    notificationStore.showNotification({ type: 'success', message: t('accounting.fixed_assets.disposed', 'Asset disposed') })
    showDisposeModal.value = false
    loadAssets()
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.message || 'Failed to dispose asset' })
  } finally {
    isDisposing.value = false
  }
}

function closeModal() {
  showCreateModal.value = false
  showEditModal.value = false
  selectedAsset.value = null
  form.value = { ...defaultForm }
}

function categoryLabel(category) {
  const found = categoryOptions.find(c => c.value === category)
  return found ? found.label : category
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
  const currency = companyStore.selectedCompanyCurrency
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(amount) + ' ' + (currency?.code || 'MKD')
}

function formatAccountLabel(account) {
  return `${account.code} - ${account.name}`
}
</script>

<!-- CLAUDE-CHECKPOINT -->
