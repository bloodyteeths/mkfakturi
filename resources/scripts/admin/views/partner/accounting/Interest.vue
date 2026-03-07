<template>
  <BasePage>
    <BasePageHeader :title="t('title')" />

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ $t('partner.select_company_placeholder') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Summary Cards -->
      <div v-if="summary" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('total_interest') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ formatMoney(summary.total_interest) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('pending') }}</p>
          <p class="text-2xl font-bold text-amber-600">{{ formatMoney(summary.calculated?.amount || 0) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('invoiced') }}</p>
          <p class="text-2xl font-bold text-blue-600">{{ formatMoney(summary.invoiced?.amount || 0) }}</p>
        </div>
      </div>

      <!-- Info Banner -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
        <p class="text-sm text-blue-800">
          <strong>{{ t('nbrm_rate') }}:</strong> {{ t('mk_law_info') }}
        </p>
      </div>

      <!-- Actions -->
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-4">
          <BaseInputGroup :label="t('status')">
            <BaseMultiselect
              v-model="filters.status"
              :options="statusOptions"
              :searchable="false"
              label="label"
              value-prop="value"
              class="w-48"
            />
          </BaseInputGroup>
        </div>
        <BaseButton variant="primary" :loading="isCalculating" @click="batchCalculate">
          {{ t('batch_calculate') }}
        </BaseButton>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 space-y-4">
          <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse">
            <div class="h-4 bg-gray-200 rounded w-24"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
            <div class="h-4 bg-gray-200 rounded flex-1"></div>
            <div class="h-4 bg-gray-200 rounded w-16"></div>
            <div class="h-4 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <!-- Table -->
      <div v-else-if="calculations.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('principal') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('days_overdue') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('annual_rate') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('interest_amount') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('status') }}</th>
              <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="calc in calculations" :key="calc.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900">{{ calc.customer?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ calc.invoice?.invoice_number || '-' }}</td>
              <td class="px-4 py-3 text-sm text-right font-medium">{{ formatMoney(calc.principal_amount) }}</td>
              <td class="px-4 py-3 text-sm text-center">{{ calc.days_overdue }}</td>
              <td class="px-4 py-3 text-sm text-center">{{ calc.annual_rate }}%</td>
              <td class="px-4 py-3 text-sm text-right font-bold text-red-600">{{ formatMoney(calc.interest_amount) }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="statusClass(calc.status)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                  {{ statusLabel(calc.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-right">
                <BaseButton v-if="calc.status === 'calculated'" size="sm" variant="primary-outline" @click="waive(calc.id)">
                  {{ t('waive') }}
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div
        v-else-if="!isLoading"
        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16"
      >
        <BaseIcon name="CalculatorIcon" class="h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ t('no_calculations') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ t('no_calculations_description') }}</p>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import interestMessages from '@/scripts/admin/i18n/interest.js'

const notificationStore = useNotificationStore()
const consoleStore = useConsoleStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return interestMessages[locale]?.interest?.[key]
    || interestMessages['en']?.interest?.[key]
    || key
}

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

const companies = computed(() => consoleStore.managedCompanies || [])
const selectedCompanyId = ref(null)
const calculations = ref([])
const summary = ref(null)
const isLoading = ref(false)
const isCalculating = ref(false)

const filters = reactive({ status: null })

const statusOptions = [
  { value: null, label: t('all') || 'All' },
  { value: 'calculated', label: t('status_calculated') },
  { value: 'invoiced', label: t('status_invoiced') || 'Invoiced' },
  { value: 'paid', label: t('status_paid') || 'Paid' },
  { value: 'waived', label: t('status_waived') || 'Waived' },
]

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '0.00'
  return (cents / 100).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function statusClass(s) {
  const map = {
    calculated: 'bg-amber-100 text-amber-800',
    invoiced: 'bg-blue-100 text-blue-800',
    paid: 'bg-green-100 text-green-800',
    waived: 'bg-gray-100 text-gray-800',
  }
  return map[s] || 'bg-gray-100 text-gray-800'
}

function statusLabel(status) {
  switch (status) {
    case 'calculated': return t('status_calculated')
    case 'invoiced': return t('status_invoiced')
    case 'paid': return t('status_paid')
    case 'waived': return t('status_waived')
    default: return status
  }
}

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/interest${path}`
}

async function loadCompanies() {
  await consoleStore.fetchCompanies()
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  loadData()
}

async function loadData() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.status) params.status = filters.status
    const [calcRes, sumRes] = await Promise.all([
      window.axios.get(partnerApi(''), { params }),
      window.axios.get(partnerApi('/summary')),
    ])
    calculations.value = calcRes.data.data || []
    summary.value = sumRes.data.data || null
  } catch (e) {
    console.error('Failed to load interest data', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load interest data',
    })
  } finally {
    isLoading.value = false
  }
}

async function batchCalculate() {
  isCalculating.value = true
  try {
    await window.axios.post(partnerApi('/calculate'))
    notificationStore.showNotification({
      type: 'success',
      message: t('calculated_success') || 'Interest calculated successfully.',
    })
    loadData()
  } catch (e) {
    console.error('Failed to calculate', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_calculating') || 'Failed to calculate',
    })
  } finally {
    isCalculating.value = false
  }
}

async function waive(id) {
  try {
    await window.axios.post(partnerApi(`/${id}/waive`))
    notificationStore.showNotification({
      type: 'success',
      message: t('waived_success') || 'Interest waived.',
    })
    loadData()
  } catch (e) {
    console.error('Failed to waive', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_waiving') || 'Failed to waive',
    })
  }
}

watch(() => filters.status, () => {
  if (selectedCompanyId.value) loadData()
})

onMounted(() => {
  loadCompanies()
})
// CLAUDE-CHECKPOINT
</script>

<!-- CLAUDE-CHECKPOINT -->
