<template>
  <BasePage>
    <BasePageHeader :title="t('create')">
      <template #actions>
        <BaseButton variant="primary-outline" @click="$router.push({ name: 'budgets.index' })">
          {{ $t('general.cancel') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Wizard Steps -->
    <div class="mb-6">
      <nav class="flex items-center justify-center" aria-label="Progress">
        <ol class="flex items-center space-x-5">
          <li v-for="(stepLabel, idx) in steps" :key="idx">
            <button
              class="flex items-center"
              :class="idx <= currentStep ? 'text-primary-600' : 'text-gray-400'"
              @click="idx < currentStep ? currentStep = idx : null"
            >
              <span
                class="flex h-8 w-8 items-center justify-center rounded-full border-2 text-sm font-medium"
                :class="idx === currentStep ? 'border-primary-600 bg-primary-600 text-white' : idx < currentStep ? 'border-primary-600 bg-primary-50 text-primary-600' : 'border-gray-300 text-gray-400'"
              >
                {{ idx + 1 }}
              </span>
              <span class="ml-2 text-sm font-medium hidden sm:block">{{ stepLabel }}</span>
            </button>
          </li>
        </ol>
      </nav>
    </div>

    <!-- Step 1: Budget Details -->
    <div v-if="currentStep === 0" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_details') }}</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInputGroup :label="t('name')" required>
          <BaseInput v-model="form.name" :placeholder="t('name')" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('scenario')">
          <select v-model="form.scenario" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="expected">{{ t('scenario_expected') }}</option>
            <option value="optimistic">{{ t('scenario_optimistic') }}</option>
            <option value="pessimistic">{{ t('scenario_pessimistic') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('period')">
          <select v-model="form.period_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="monthly">{{ t('period_monthly') }}</option>
            <option value="quarterly">{{ t('period_quarterly') }}</option>
            <option value="yearly">{{ t('period_yearly') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('cost_center')">
          <BaseMultiselect
            v-model="form.cost_center_id"
            :options="costCenters"
            :searchable="true"
            label="name"
            value-prop="id"
            :placeholder="t('cost_center')"
            :can-clear="true"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="t('start_date')" required>
          <BaseInput v-model="form.start_date" type="date" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('end_date')" required>
          <BaseInput v-model="form.end_date" type="date" />
        </BaseInputGroup>
      </div>

      <div class="mt-6 flex justify-end">
        <BaseButton variant="primary" @click="goToStep(1)" :disabled="!isStep1Valid">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 2: Budget Lines Grid -->
    <div v-if="currentStep === 1" class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-medium text-gray-900">{{ t('step_lines') }}</h3>
        <div class="flex items-center space-x-3">
          <!-- Pre-fill from Actuals -->
          <div class="flex items-center space-x-2">
            <select v-model="prefillYear" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
              <option v-for="y in prefillYears" :key="y" :value="y">{{ y }}</option>
            </select>
            <BaseInputGroup :label="t('growth_pct')" class="w-24">
              <BaseInput v-model.number="prefillGrowth" type="number" step="0.1" placeholder="0" class="text-sm" />
            </BaseInputGroup>
            <BaseButton variant="primary-outline" size="sm" @click="prefillFromActuals" :disabled="isPrefilling">
              {{ t('prefill_actuals') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Spreadsheet Grid -->
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="sticky left-0 bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[200px] z-10">
                {{ t('account_type') }}
              </th>
              <th
                v-for="period in gridPeriods"
                :key="period.key"
                class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase min-w-[120px]"
              >
                {{ period.label }}
              </th>
              <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase min-w-[120px] bg-gray-100">
                {{ t('total_budgeted') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <tr v-for="cat in accountCategories" :key="cat.type" class="hover:bg-gray-50">
              <td class="sticky left-0 bg-white px-4 py-2 text-sm font-medium text-gray-900 z-10">
                {{ cat.label }}
              </td>
              <td v-for="period in gridPeriods" :key="period.key" class="px-1 py-1">
                <input
                  type="number"
                  step="0.01"
                  :value="getGridValue(cat.type, period.key)"
                  @change="setGridValue(cat.type, period.key, $event.target.value)"
                  class="w-full rounded border-gray-200 text-sm text-right py-1 px-2 focus:border-primary-500 focus:ring-primary-500"
                  placeholder="0.00"
                />
              </td>
              <td class="px-3 py-2 text-sm text-right font-medium text-gray-900 bg-gray-50">
                {{ formatNumber(getRowTotal(cat.type)) }}
              </td>
            </tr>
            <!-- Totals Row -->
            <tr class="bg-primary-50 font-semibold">
              <td class="sticky left-0 bg-primary-50 px-4 py-3 text-sm text-primary-900 z-10">
                {{ t('total_budgeted') }}
              </td>
              <td v-for="period in gridPeriods" :key="period.key" class="px-3 py-3 text-sm text-right text-primary-900">
                {{ formatNumber(getColumnTotal(period.key)) }}
              </td>
              <td class="px-3 py-3 text-sm text-right text-primary-900 bg-primary-100">
                {{ formatNumber(grandTotal) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-6 py-4 border-t border-gray-200 flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 0">
          {{ $t('general.back') }}
        </BaseButton>
        <BaseButton variant="primary" @click="goToStep(2)">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 3: Review -->
    <div v-if="currentStep === 2" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('step_review') }}</h3>

      <!-- Summary -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">{{ t('name') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ form.name }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">{{ t('scenario') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ scenarioLabel(form.scenario) }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">{{ t('period') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ form.start_date }} - {{ form.end_date }}</p>
        </div>
        <div class="bg-primary-50 rounded-lg p-3">
          <p class="text-xs text-primary-600">{{ t('total_budgeted') }}</p>
          <p class="text-lg font-bold text-primary-700">{{ formatNumber(grandTotal) }}</p>
        </div>
      </div>

      <!-- Lines Summary Table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 mb-6">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('account_type') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_budgeted') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="cat in accountCategories" :key="cat.type">
              <td class="px-4 py-2 text-sm text-gray-900">{{ cat.label }}</td>
              <td class="px-4 py-2 text-sm text-right font-medium" :class="getRowTotal(cat.type) > 0 ? 'text-gray-900' : 'text-gray-400'">
                {{ formatNumber(getRowTotal(cat.type)) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 1">
          {{ $t('general.back') }}
        </BaseButton>
        <div class="flex space-x-3">
          <BaseButton variant="primary-outline" @click="saveBudget('draft')" :disabled="isSaving">
            {{ t('draft') }}
          </BaseButton>
          <BaseButton variant="primary" @click="saveBudget('approve')" :disabled="isSaving">
            {{ t('approve') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import budgetMessages from '@/scripts/admin/i18n/budgets.js'

const router = useRouter()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'
function t(key) {
  return budgetMessages[locale]?.budgets?.[key]
    || budgetMessages['en']?.budgets?.[key]
    || key
}

function accountTypeLabel(type) {
  const typeKey = 'type_' + type.toLowerCase()
  return t(typeKey) || type
}

// State
const currentStep = ref(0)
const isSaving = ref(false)
const isPrefilling = ref(false)
const costCenters = ref([])
const prefillYear = ref(new Date().getFullYear() - 1)
const prefillGrowth = ref(0)

const steps = [
  t('step_details'),
  t('step_lines'),
  t('step_review'),
]

const form = ref({
  name: '',
  period_type: 'monthly',
  scenario: 'expected',
  cost_center_id: null,
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: `${new Date().getFullYear()}-12-31`,
})

// Grid data: { "ACCOUNT_TYPE|period_key": amount }
const gridData = ref({})

const accountCategories = [
  { type: 'OPERATING_REVENUE', label: t('revenue') + ' - ' + accountTypeLabel('OPERATING_REVENUE') },
  { type: 'NON_OPERATING_REVENUE', label: t('revenue') + ' - ' + accountTypeLabel('NON_OPERATING_REVENUE') },
  { type: 'OPERATING_EXPENSE', label: t('expenses') + ' - ' + accountTypeLabel('OPERATING_EXPENSE') },
  { type: 'DIRECT_EXPENSE', label: t('expenses') + ' - ' + accountTypeLabel('DIRECT_EXPENSE') },
  { type: 'OVERHEAD_EXPENSE', label: t('expenses') + ' - ' + accountTypeLabel('OVERHEAD_EXPENSE') },
  { type: 'NON_OPERATING_EXPENSE', label: t('expenses') + ' - ' + accountTypeLabel('NON_OPERATING_EXPENSE') },
  { type: 'CURRENT_ASSET', label: t('assets') + ' - CURRENT_ASSET' },
  { type: 'NON_CURRENT_ASSET', label: t('assets') + ' - NON_CURRENT_ASSET' },
  { type: 'CURRENT_LIABILITY', label: t('liabilities') + ' - CURRENT_LIABILITY' },
  { type: 'NON_CURRENT_LIABILITY', label: t('liabilities') + ' - NON_CURRENT_LIABILITY' },
]

const prefillYears = computed(() => {
  const years = []
  const curr = new Date().getFullYear()
  for (let y = curr; y >= curr - 5; y--) {
    years.push(y)
  }
  return years
})

const gridPeriods = computed(() => {
  if (!form.value.start_date || !form.value.end_date) return []

  const periods = []
  const start = new Date(form.value.start_date)
  const end = new Date(form.value.end_date)

  if (form.value.period_type === 'monthly') {
    let current = new Date(start.getFullYear(), start.getMonth(), 1)
    while (current <= end) {
      const monthKey = `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}`
      const monthLabel = new Date(2000, current.getMonth()).toLocaleDateString(fmtLocale, { month: 'short' })
      periods.push({
        key: monthKey,
        label: `${monthLabel} ${current.getFullYear()}`,
        start: `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}-01`,
        end: new Date(current.getFullYear(), current.getMonth() + 1, 0).toISOString().split('T')[0],
      })
      current = new Date(current.getFullYear(), current.getMonth() + 1, 1)
    }
  } else if (form.value.period_type === 'quarterly') {
    let current = new Date(start.getFullYear(), Math.floor(start.getMonth() / 3) * 3, 1)
    while (current <= end) {
      const q = Math.floor(current.getMonth() / 3) + 1
      const qKey = `${current.getFullYear()}-Q${q}`
      periods.push({
        key: qKey,
        label: `Q${q} ${current.getFullYear()}`,
        start: `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}-01`,
        end: new Date(current.getFullYear(), current.getMonth() + 3, 0).toISOString().split('T')[0],
      })
      current = new Date(current.getFullYear(), current.getMonth() + 3, 1)
    }
  } else {
    // Yearly
    let y = start.getFullYear()
    while (y <= end.getFullYear()) {
      periods.push({
        key: `${y}`,
        label: `${y}`,
        start: `${y}-01-01`,
        end: `${y}-12-31`,
      })
      y++
    }
  }

  return periods
})

const isStep1Valid = computed(() => {
  return form.value.name && form.value.start_date && form.value.end_date
})

const grandTotal = computed(() => {
  let total = 0
  for (const key of Object.keys(gridData.value)) {
    total += parseFloat(gridData.value[key]) || 0
  }
  return total
})

// Lifecycle
onMounted(async () => {
  await loadCostCenters()
})

// Methods
async function loadCostCenters() {
  try {
    const response = await window.axios.get('/cost-centers')
    costCenters.value = response.data?.data || []
  } catch {
    // Cost centers are optional
    costCenters.value = []
  }
}

function goToStep(step) {
  if (step === 1 && !isStep1Valid.value) return
  currentStep.value = step
}

function getGridValue(accountType, periodKey) {
  const key = `${accountType}|${periodKey}`
  return gridData.value[key] || ''
}

function setGridValue(accountType, periodKey, value) {
  const key = `${accountType}|${periodKey}`
  const numVal = parseFloat(value) || 0
  if (numVal === 0) {
    delete gridData.value[key]
  } else {
    gridData.value[key] = numVal
  }
}

function getRowTotal(accountType) {
  let total = 0
  for (const period of gridPeriods.value) {
    const key = `${accountType}|${period.key}`
    total += parseFloat(gridData.value[key]) || 0
  }
  return total
}

function getColumnTotal(periodKey) {
  let total = 0
  for (const cat of accountCategories) {
    const key = `${cat.type}|${periodKey}`
    total += parseFloat(gridData.value[key]) || 0
  }
  return total
}

function formatNumber(val) {
  return Number(val || 0).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function scenarioLabel(scenario) {
  const labels = {
    expected: t('scenario_expected'),
    optimistic: t('scenario_optimistic'),
    pessimistic: t('scenario_pessimistic'),
  }
  return labels[scenario] || scenario
}

async function prefillFromActuals() {
  isPrefilling.value = true
  try {
    const response = await window.axios.post('/budgets/prefill-from-actuals', {
      year: prefillYear.value,
      growth_pct: prefillGrowth.value || 0,
    })

    const data = response.data?.data
    if (data?.lines) {
      // Clear existing grid
      gridData.value = {}

      // Map prefilled lines to grid
      for (const line of data.lines) {
        const month = String(line.month).padStart(2, '0')
        let periodKey

        if (form.value.period_type === 'monthly') {
          periodKey = `${data.target_year}-${month}`
        } else if (form.value.period_type === 'quarterly') {
          const q = Math.ceil(line.month / 3)
          periodKey = `${data.target_year}-Q${q}`
        } else {
          periodKey = data.target_year
        }

        const key = `${line.account_type}|${periodKey}`
        gridData.value[key] = (gridData.value[key] || 0) + line.amount
      }

      // Update form dates to target year
      form.value.start_date = `${data.target_year}-01-01`
      form.value.end_date = `${data.target_year}-12-31`

      const msg = t('prefilled_success')
        .replace('{count}', data.lines.length)
        .replace('{year}', data.source_year)
      notificationStore.showNotification({
        type: 'success',
        message: msg,
      })
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  } finally {
    isPrefilling.value = false
  }
}

function buildLines() {
  const lines = []
  for (const [key, amount] of Object.entries(gridData.value)) {
    if (!amount || parseFloat(amount) === 0) continue
    const [accountType, periodKey] = key.split('|')
    const period = gridPeriods.value.find(p => p.key === periodKey)
    if (!period) continue

    lines.push({
      account_type: accountType,
      period_start: period.start,
      period_end: period.end,
      amount: parseFloat(amount),
    })
  }
  return lines
}

async function saveBudget(action) {
  isSaving.value = true
  try {
    const lines = buildLines()

    const payload = {
      name: form.value.name,
      period_type: form.value.period_type,
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      scenario: form.value.scenario,
      cost_center_id: form.value.cost_center_id || null,
      lines,
    }

    const response = await window.axios.post('/budgets', payload)
    const budgetId = response.data?.data?.id

    if (action === 'approve' && budgetId) {
      await window.axios.post(`/budgets/${budgetId}/approve`)
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('create'),
    })

    router.push({ name: 'budgets.index' })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_creating'),
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
