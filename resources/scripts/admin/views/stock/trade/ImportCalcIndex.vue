<template>
  <BasePage>
    <BasePageHeader :title="$t('trade.import_calc_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('trade.import_calc_title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link :to="{ name: 'stock.trade.import-calculation.create' }">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('trade.new_import_calc') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
      <div class="w-40">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          value-prop="value"
          label="label"
          :placeholder="$t('trade.status')"
          :canClear="true"
        />
      </div>
      <div class="w-40">
        <BaseInput
          v-model="filters.from_date"
          type="date"
          placeholder="Од датум"
        />
      </div>
      <div class="w-40">
        <BaseInput
          v-model="filters.to_date"
          type="date"
          placeholder="До датум"
        />
      </div>
      <div class="w-48">
        <BaseInput
          v-model="filters.search"
          type="text"
          :placeholder="$t('trade.doc_number') + '...'"
        />
      </div>
    </div>

    <!-- Summary Cards -->
    <div v-if="calculations.length > 0" class="mb-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-amber-400">
        <p class="text-xs text-gray-500 uppercase">{{ $t('trade.status_draft') }}</p>
        <p class="text-lg font-bold text-amber-700">{{ draftCount }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
        <p class="text-xs text-gray-500 uppercase">{{ $t('trade.status_approved') }}</p>
        <p class="text-lg font-bold text-green-700">{{ approvedCount }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
        <p class="text-xs text-gray-500 uppercase">{{ $t('trade.status_voided') }}</p>
        <p class="text-lg font-bold text-red-700">{{ voidedCount }}</p>
      </div>
    </div>

    <!-- Table -->
    <BaseCard>
      <div v-if="isLoading" class="flex justify-center py-8">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox class="w-full h-64" />
        </BaseContentPlaceholders>
      </div>

      <div v-else-if="calculations.length === 0" class="text-center py-12">
        <BaseIcon name="DocumentTextIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900">{{ $t('trade.import_calc_title') }}</h3>
        <p class="text-gray-500 mt-2">Нема креирани влезни калкулации.</p>
        <router-link :to="{ name: 'stock.trade.import-calculation.create' }" class="inline-block mt-4">
          <BaseButton variant="primary">
            {{ $t('trade.new_import_calc') }}
          </BaseButton>
        </router-link>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.doc_number') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.doc_date') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.supplier') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.fakturna_iznos') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.exchange_rate') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.nabavna_cena') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ $t('trade.status') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              Акции
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="calc in calculations"
            :key="calc.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewCalculation(calc)"
          >
            <td class="px-4 py-3 text-sm font-medium text-primary-600 whitespace-nowrap">
              {{ calc.document_number }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
              {{ formatDate(calc.document_date) }}
            </td>
            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
              {{ calc.supplier_name || '-' }}
            </td>
            <td class="px-4 py-3 text-sm text-right whitespace-nowrap font-mono text-gray-900">
              {{ formatMoney(calc.invoice_value) }}
            </td>
            <td class="px-4 py-3 text-sm text-right whitespace-nowrap font-mono text-gray-900">
              {{ calc.exchange_rate ? Number(calc.exchange_rate).toFixed(4) : '-' }}
            </td>
            <td class="px-4 py-3 text-sm text-right whitespace-nowrap font-mono text-gray-900">
              {{ formatMoney(calc.nabavna_cena) }}
            </td>
            <td class="px-4 py-3 text-sm whitespace-nowrap">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="statusBadgeClass(calc.status)"
              >
                {{ statusLabel(calc.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right whitespace-nowrap" @click.stop>
              <div class="flex items-center justify-end space-x-2">
                <router-link :to="{ name: 'stock.trade.import-calculation.view', params: { id: calc.id } }">
                  <BaseButton variant="primary-outline" size="sm">
                    <BaseIcon name="EyeIcon" class="h-4 w-4" />
                  </BaseButton>
                </router-link>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200">
        <div class="text-sm text-gray-700">
          {{ calculations.length }} / {{ meta.total }}
        </div>
        <div class="flex space-x-2">
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="meta.current_page <= 1"
            @click="changePage(meta.current_page - 1)"
          >
            Претходна
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="meta.current_page >= meta.last_page"
            @click="changePage(meta.current_page + 1)"
          >
            Следна
          </BaseButton>
        </div>
      </div>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const calculations = ref([])
const isLoading = ref(false)
const currentPage = ref(1)
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

const filters = reactive({
  status: null,
  from_date: '',
  to_date: '',
  search: '',
})

const statusOptions = [
  { label: t('trade.status_draft'), value: 'draft' },
  { label: t('trade.status_approved'), value: 'approved' },
  { label: t('trade.status_voided'), value: 'voided' },
]

const companyId = computed(() => companyStore.selectedCompany?.id)

const draftCount = computed(() => calculations.value.filter(c => c.status === 'draft').length)
const approvedCount = computed(() => calculations.value.filter(c => c.status === 'approved').length)
const voidedCount = computed(() => calculations.value.filter(c => c.status === 'voided').length)

function apiBase() {
  return `/partner/companies/${companyId.value}/accounting`
}

function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-amber-100 text-amber-800',
    approved: 'bg-green-100 text-green-800',
    voided: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function statusLabel(status) {
  const labels = { draft: t('trade.status_draft'), approved: t('trade.status_approved'), voided: t('trade.status_voided') }
  return labels[status] || status
}

function formatDate(date) {
  if (!date) return '-'
  return String(date).substring(0, 10)
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const num = Number(amount) / 100
  return num.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' МКД'
}

function viewCalculation(calc) {
  router.push({ name: 'stock.trade.import-calculation.view', params: { id: calc.id } })
}

async function fetchCalculations() {
  if (!companyId.value) return
  isLoading.value = true
  try {
    const params = { page: currentPage.value, limit: 15 }
    if (filters.status) params.status = filters.status
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date
    if (filters.search) params.search = filters.search

    const response = await window.axios.get(`${apiBase()}/import-calculations`, { params })
    calculations.value = response.data.data || []
    if (response.data.meta) {
      meta.value = response.data.meta
    }
  } catch (error) {
    console.error('Failed to load import calculations:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Грешка при вчитување на влезни калкулации.',
    })
  } finally {
    isLoading.value = false
  }
}

function changePage(page) {
  currentPage.value = page
  fetchCalculations()
}

let filterTimeout = null
watch(filters, () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchCalculations()
  }, 400)
}, { deep: true })

onMounted(() => {
  fetchCalculations()
})
</script>

// CLAUDE-CHECKPOINT
