<template>
  <div>
    <CostCenterModal />

    <!-- Top: Basic Info -->
    <div class="bg-white rounded-lg shadow p-4 sm:p-6 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInputGroup :label="t('budgets.name')" required>
          <BaseInput v-model="form.name" :placeholder="t('budgets.name')" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('budgets.start_date')" required>
          <BaseInput v-model="form.start_date" type="date" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('budgets.end_date')" required>
          <BaseInput v-model="form.end_date" type="date" />
        </BaseInputGroup>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-4">
        <BaseInputGroup :label="t('budgets.scenario')">
          <select v-model="form.scenario" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            <option value="expected">{{ t('budgets.scenario_expected') }}</option>
            <option value="optimistic">{{ t('budgets.scenario_optimistic') }}</option>
            <option value="pessimistic">{{ t('budgets.scenario_pessimistic') }}</option>
          </select>
        </BaseInputGroup>
        <BaseInputGroup :label="t('budgets.period')">
          <select v-model="form.period_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            <option value="monthly">{{ t('budgets.period_monthly') }}</option>
            <option value="quarterly">{{ t('budgets.period_quarterly') }}</option>
            <option value="yearly">{{ t('budgets.period_yearly') }}</option>
          </select>
        </BaseInputGroup>
        <BaseInputGroup :label="t('budgets.cost_center')">
          <div class="flex gap-2">
            <BaseMultiselect
              v-model="form.cost_center_id"
              :options="costCenters"
              :searchable="true"
              label="name"
              value-prop="id"
              :placeholder="t('budgets.cost_center')"
              :can-clear="true"
              class="flex-1"
            />
            <button
              type="button"
              @click="openCostCenterModal"
              class="flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 bg-white hover:bg-gray-50 text-gray-500 hover:text-primary-600 transition-colors"
              :title="t('budgets.create_cost_center')"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>
          </div>
        </BaseInputGroup>
        <BaseInputGroup :label="t('budgets.growth_pct')">
          <div class="flex items-center gap-3">
            <input
              type="range"
              v-model.number="growthPct"
              min="-50"
              max="100"
              step="1"
              class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
            />
            <span class="text-sm font-medium text-gray-700 min-w-[3rem] text-right">{{ growthPct }}%</span>
          </div>
        </BaseInputGroup>
      </div>
      <div class="mt-4 flex items-center justify-between">
        <button
          type="button"
          @click="analyzeData"
          :disabled="isAnalyzing || !isFormValid"
          class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:text-primary-800 disabled:text-gray-400"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
          </svg>
          {{ t('budgets.based_on_year', { year: sourceYear }) }}
        </button>
        <select v-model.number="sourceYear" @change="analyzeData" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
          <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isAnalyzing" class="text-center py-16">
      <svg class="h-12 w-12 text-primary-500 animate-pulse mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
      </svg>
      <p class="text-sm text-gray-500">{{ t('budgets.analyzing_data') }}</p>
    </div>

    <!-- No Data State -->
    <div v-else-if="dataLoaded && !hasData" class="text-center py-16 bg-white rounded-lg shadow">
      <svg class="h-12 w-12 text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
      </svg>
      <p class="text-sm text-gray-500 mb-4">{{ t('budgets.no_historical_data') }}</p>
      <BaseButton variant="primary-outline" @click="$emit('switchMode', 'advanced')">
        {{ t('budgets.switch_to_advanced') }}
      </BaseButton>
    </div>

    <!-- Budget Proposal -->
    <template v-else-if="dataLoaded && hasData">
      <!-- AI Insights Bar -->
      <div v-if="aiInsights" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
          <svg class="h-5 w-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
          </svg>
          <div class="flex-1">
            <div class="flex items-center justify-between mb-1">
              <p class="text-sm font-medium text-indigo-900">{{ aiInsights.trend_description }}</p>
              <span class="text-xs text-indigo-600">
                {{ t('budgets.ai_suggested_growth') }}: {{ aiInsights.suggested_growth_pct }}%
              </span>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
              <span
                v-for="(risk, i) in aiInsights.risks"
                :key="'r'+i"
                class="text-xs text-red-700 bg-red-50 px-2 py-1 rounded"
              >{{ risk }}</span>
              <span
                v-for="(opp, i) in aiInsights.opportunities"
                :key="'o'+i"
                class="text-xs text-green-700 bg-green-50 px-2 py-1 rounded"
              >{{ opp }}</span>
            </div>
            <div class="flex items-center gap-3 mt-2">
              <button
                type="button"
                @click="applyAiGrowth"
                class="text-xs font-medium text-indigo-700 hover:text-indigo-900 underline"
              >{{ t('budgets.ai_apply_suggestions') }}</button>
              <span v-if="aiUsage" class="text-xs text-gray-400">
                {{ t('budgets.ai_queries_remaining', { remaining: aiUsage.remaining, limit: aiUsage.limit }) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-else-if="isLoadingAi" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
          <svg class="h-5 w-5 text-indigo-600 animate-pulse" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
          </svg>
          <p class="text-sm text-indigo-700">{{ t('budgets.ai_loading') }}</p>
        </div>
      </div>

      <!-- Summary Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 rounded-lg p-4">
          <p class="text-xs text-green-600">{{ t('budgets.expected_revenue') }}</p>
          <p class="text-xl sm:text-2xl font-bold text-green-700">{{ formatCurrency(totalRevenue) }}</p>
        </div>
        <div class="bg-red-50 rounded-lg p-4">
          <p class="text-xs text-red-600">{{ t('budgets.expected_expenses') }}</p>
          <p class="text-xl sm:text-2xl font-bold text-red-700">{{ formatCurrency(totalExpenses) }}</p>
        </div>
        <div :class="profitLoss >= 0 ? 'bg-blue-50' : 'bg-orange-50'" class="rounded-lg p-4">
          <p class="text-xs" :class="profitLoss >= 0 ? 'text-blue-600' : 'text-orange-600'">
            {{ profitLoss >= 0 ? t('budgets.expected_profit') : t('budgets.expected_loss') }}
          </p>
          <p class="text-xl sm:text-2xl font-bold" :class="profitLoss >= 0 ? 'text-blue-700' : 'text-orange-700'">
            {{ formatCurrency(Math.abs(profitLoss)) }}
          </p>
        </div>
      </div>

      <!-- Revenue Section -->
      <div v-if="revenueCategories.length" class="bg-white rounded-lg shadow mb-4">
        <div class="px-4 py-3 bg-green-50 border-b border-green-100 flex items-center justify-between rounded-t-lg">
          <h3 class="text-sm font-medium text-green-800">{{ t('budgets.revenue') }}</h3>
          <span class="text-sm font-bold text-green-800">{{ formatCurrency(totalRevenue) }}</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="cat in revenueCategories" :key="cat.key" class="px-4 py-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 flex-1 min-w-0">
                <span class="text-sm text-gray-700 truncate">{{ cat.label }}</span>
              </div>
              <div class="flex items-center gap-3 ml-4">
                <span class="text-xs text-gray-400 whitespace-nowrap hidden sm:inline">
                  {{ t('budgets.last_year') }}: {{ formatCurrency(cat.original_total) }}
                </span>
                <input
                  type="number"
                  v-model.number="cat.adjusted_total"
                  step="100"
                  class="w-28 sm:w-32 rounded border-gray-200 text-sm text-right py-1 px-2 focus:border-primary-500 focus:ring-primary-500"
                />
              </div>
            </div>
            <!-- Monthly breakdown (collapsible) -->
            <div v-if="cat.showMonthly" class="mt-2 grid grid-cols-4 sm:grid-cols-6 gap-2">
              <div v-for="m in 12" :key="m" class="text-center">
                <p class="text-xs text-gray-400">{{ monthLabel(m) }}</p>
                <input
                  type="number"
                  :value="cat.monthly[m] || 0"
                  @change="updateMonthly(cat, m, $event.target.value)"
                  class="w-full rounded border-gray-200 text-xs text-right py-1 px-1 mt-1"
                  step="100"
                />
              </div>
            </div>
            <button
              type="button"
              @click="cat.showMonthly = !cat.showMonthly"
              class="text-xs text-gray-400 hover:text-gray-600 mt-1"
            >
              {{ cat.showMonthly ? '- ' : '+ ' }}{{ t('budgets.monthly_breakdown') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Expenses Section -->
      <div v-if="expenseCategories.length" class="bg-white rounded-lg shadow mb-6">
        <div class="px-4 py-3 bg-red-50 border-b border-red-100 flex items-center justify-between rounded-t-lg">
          <h3 class="text-sm font-medium text-red-800">{{ t('budgets.expenses') }}</h3>
          <span class="text-sm font-bold text-red-800">{{ formatCurrency(totalExpenses) }}</span>
        </div>
        <div class="divide-y divide-gray-100">
          <div v-for="cat in expenseCategories" :key="cat.key" class="px-4 py-3">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2 flex-1 min-w-0">
                <span class="text-sm text-gray-700 truncate">{{ cat.label }}</span>
              </div>
              <div class="flex items-center gap-3 ml-4">
                <span class="text-xs text-gray-400 whitespace-nowrap hidden sm:inline">
                  {{ t('budgets.last_year') }}: {{ formatCurrency(cat.original_total) }}
                </span>
                <input
                  type="number"
                  v-model.number="cat.adjusted_total"
                  step="100"
                  class="w-28 sm:w-32 rounded border-gray-200 text-sm text-right py-1 px-2 focus:border-primary-500 focus:ring-primary-500"
                />
              </div>
            </div>
            <div v-if="cat.showMonthly" class="mt-2 grid grid-cols-4 sm:grid-cols-6 gap-2">
              <div v-for="m in 12" :key="m" class="text-center">
                <p class="text-xs text-gray-400">{{ monthLabel(m) }}</p>
                <input
                  type="number"
                  :value="cat.monthly[m] || 0"
                  @change="updateMonthly(cat, m, $event.target.value)"
                  class="w-full rounded border-gray-200 text-xs text-right py-1 px-1 mt-1"
                  step="100"
                />
              </div>
            </div>
            <button
              type="button"
              @click="cat.showMonthly = !cat.showMonthly"
              class="text-xs text-gray-400 hover:text-gray-600 mt-1"
            >
              {{ cat.showMonthly ? '- ' : '+ ' }}{{ t('budgets.monthly_breakdown') }}
            </button>
          </div>
        </div>
      </div>

      <!-- Save Actions -->
      <div class="flex flex-col sm:flex-row justify-between gap-3">
        <BaseButton variant="primary-outline" @click="$emit('switchMode', 'advanced')">
          {{ t('budgets.switch_to_advanced') }}
        </BaseButton>
        <div class="flex gap-3">
          <BaseButton variant="primary-outline" @click="saveBudget('draft')" :disabled="isSaving">
            {{ t('budgets.save_draft') }}
          </BaseButton>
          <BaseButton variant="primary" @click="saveBudget('approve')" :disabled="isSaving">
            {{ t('budgets.approve') }}
          </BaseButton>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'
import CostCenterModal from '@/scripts/admin/components/modal-components/CostCenterModal.vue'

const emit = defineEmits(['switchMode'])
const router = useRouter()
const { t, locale } = useI18n()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()

const currentYear = new Date().getFullYear()
const fmtLocale = computed(() => {
  const map = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
  return map[locale.value] || 'mk-MK'
})

// State
const isAnalyzing = ref(false)
const isLoadingAi = ref(false)
const isSaving = ref(false)
const dataLoaded = ref(false)
const hasData = ref(false)
const costCenters = ref([])
const sourceYear = ref(currentYear - 1)
const growthPct = ref(0)
const aiInsights = ref(null)
const aiUsage = ref(null)

const form = reactive({
  name: `${t('budgets.title')} ${currentYear}`,
  period_type: 'monthly',
  scenario: 'expected',
  cost_center_id: null,
  start_date: `${currentYear}-01-01`,
  end_date: `${currentYear}-12-31`,
})

const categories = ref([])

const availableYears = computed(() => {
  const years = []
  for (let y = currentYear; y >= currentYear - 5; y--) {
    years.push(y)
  }
  return years
})

const isFormValid = computed(() => {
  return form.name && form.start_date && form.end_date && form.end_date > form.start_date
})

const revenueCategories = computed(() =>
  categories.value.filter(c => c.account_type.includes('REVENUE'))
)

const expenseCategories = computed(() =>
  categories.value.filter(c => !c.account_type.includes('REVENUE'))
)

const totalRevenue = computed(() =>
  revenueCategories.value.reduce((sum, c) => sum + (c.adjusted_total || 0), 0)
)

const totalExpenses = computed(() =>
  expenseCategories.value.reduce((sum, c) => sum + (c.adjusted_total || 0), 0)
)

const profitLoss = computed(() => totalRevenue.value - totalExpenses.value)

// Watch growth slider to update all amounts
watch(growthPct, (newPct) => {
  const multiplier = 1 + (newPct / 100)
  for (const cat of categories.value) {
    cat.adjusted_total = Math.round(cat.original_total * multiplier)
    // Also adjust monthly
    for (const m of Object.keys(cat.original_monthly)) {
      cat.monthly[m] = Math.round(cat.original_monthly[m] * multiplier)
    }
  }
})

onMounted(async () => {
  await loadCostCenters()
  await analyzeData()
})

async function loadCostCenters() {
  try {
    const response = await window.axios.get('/cost-centers')
    costCenters.value = response.data?.data || []
  } catch {
    costCenters.value = []
  }
}

async function analyzeData() {
  isAnalyzing.value = true
  dataLoaded.value = false
  aiInsights.value = null

  try {
    const response = await window.axios.post('/budgets/smart-budget', {
      year: sourceYear.value,
      growth_pct: growthPct.value,
      locale: locale.value || 'mk',
    })

    const data = response.data?.data
    hasData.value = data?.has_data || false

    if (data?.categories) {
      categories.value = data.categories.map(cat => ({
        ...cat,
        adjusted_total: cat.total,
        original_total: cat.original_total || cat.total,
        original_monthly: { ...cat.monthly },
        showMonthly: false,
      }))

      // Update form dates to target year
      form.start_date = `${data.target_year}-01-01`
      form.end_date = `${data.target_year}-12-31`
    }

    dataLoaded.value = true

    // Try AI insights (non-blocking)
    loadAiInsights()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('budgets.error_loading'),
    })
    dataLoaded.value = true
    hasData.value = false
  } finally {
    isAnalyzing.value = false
  }
}

async function loadAiInsights() {
  if (!hasData.value) return
  isLoadingAi.value = true

  try {
    const response = await window.axios.post('/ai/budget-suggest', {
      year: sourceYear.value,
      locale: locale.value || 'mk',
    })

    const data = response.data?.data
    if (data?.insights) {
      aiInsights.value = data.insights
      aiUsage.value = data.usage || null
    }
  } catch {
    // AI is optional - silently fail
  } finally {
    isLoadingAi.value = false
  }
}

function applyAiGrowth() {
  if (aiInsights.value?.suggested_growth_pct != null) {
    growthPct.value = aiInsights.value.suggested_growth_pct
  }
}

function openCostCenterModal() {
  modalStore.openModal({
    componentName: 'CostCenterModal',
    title: t('budgets.create_cost_center'),
    refreshData: (newCostCenter) => {
      costCenters.value.push(newCostCenter)
      form.cost_center_id = newCostCenter.id
    },
  })
}

function monthLabel(month) {
  return new Date(2000, month - 1).toLocaleDateString(fmtLocale.value, { month: 'short' })
}

function formatCurrency(val) {
  return Number(val || 0).toLocaleString(fmtLocale.value, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  })
}

function updateMonthly(cat, month, value) {
  cat.monthly[month] = parseFloat(value) || 0
  // Recalculate total from monthly
  let newTotal = 0
  for (let m = 1; m <= 12; m++) {
    newTotal += cat.monthly[m] || 0
  }
  cat.adjusted_total = Math.round(newTotal)
}

function buildBudgetLines() {
  const lines = []
  const startYear = parseInt(form.start_date.split('-')[0])

  for (const cat of categories.value) {
    if (!cat.adjusted_total || cat.adjusted_total <= 0) continue

    // Determine the monthly distribution
    const totalFromMonthly = Object.values(cat.monthly).reduce((s, v) => s + (v || 0), 0)

    if (form.period_type === 'monthly') {
      // One line per month
      for (let m = 1; m <= 12; m++) {
        let amount
        if (totalFromMonthly > 0) {
          // Proportional distribution based on historical pattern
          const ratio = (cat.monthly[m] || 0) / totalFromMonthly
          amount = Math.round(cat.adjusted_total * ratio * 100) / 100
        } else {
          amount = Math.round(cat.adjusted_total / 12 * 100) / 100
        }
        if (amount <= 0) continue

        const periodStart = `${startYear}-${String(m).padStart(2, '0')}-01`
        const periodEnd = new Date(startYear, m, 0).toISOString().split('T')[0]

        lines.push({
          account_type: cat.account_type,
          period_start: periodStart,
          period_end: periodEnd,
          amount,
        })
      }
    } else if (form.period_type === 'quarterly') {
      for (let q = 0; q < 4; q++) {
        const qMonths = [q * 3 + 1, q * 3 + 2, q * 3 + 3]
        let amount = 0
        if (totalFromMonthly > 0) {
          for (const m of qMonths) {
            const ratio = (cat.monthly[m] || 0) / totalFromMonthly
            amount += cat.adjusted_total * ratio
          }
        } else {
          amount = cat.adjusted_total / 4
        }
        amount = Math.round(amount * 100) / 100
        if (amount <= 0) continue

        const periodStart = `${startYear}-${String(q * 3 + 1).padStart(2, '0')}-01`
        const periodEnd = new Date(startYear, (q + 1) * 3, 0).toISOString().split('T')[0]

        lines.push({
          account_type: cat.account_type,
          period_start: periodStart,
          period_end: periodEnd,
          amount,
        })
      }
    } else {
      // Yearly - single line
      lines.push({
        account_type: cat.account_type,
        period_start: form.start_date,
        period_end: form.end_date,
        amount: cat.adjusted_total,
      })
    }
  }

  return lines
}

async function saveBudget(action) {
  if (!isFormValid.value) return
  isSaving.value = true

  try {
    const lines = buildBudgetLines()

    if (lines.length === 0) {
      notificationStore.showNotification({
        type: 'error',
        message: t('budgets.no_budget_lines'),
      })
      isSaving.value = false
      return
    }

    const payload = {
      name: form.name,
      period_type: form.period_type,
      start_date: form.start_date,
      end_date: form.end_date,
      scenario: form.scenario,
      cost_center_id: form.cost_center_id || null,
      lines,
    }

    const response = await window.axios.post('/budgets', payload)
    const budgetId = response.data?.data?.id

    if (action === 'approve' && budgetId) {
      await window.axios.post(`/budgets/${budgetId}/approve`)
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('budgets.created_success'),
    })

    router.push({ name: 'budgets.index' })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('budgets.error_creating'),
    })
  } finally {
    isSaving.value = false
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
