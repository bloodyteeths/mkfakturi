<template>
  <div>
    <CostCenterModal />

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

    <!-- Step 1: Basic Info + Auto-load -->
    <div v-if="currentStep === 0" class="bg-white rounded-lg shadow p-4 sm:p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('budgets.step_basic_info') }}</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInputGroup :label="t('budgets.name')" required :help-text="t('budgets.help_name')">
          <BaseInput v-model="form.name" :placeholder="t('budgets.name')" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.scenario')" :help-text="t('budgets.help_scenario')">
          <select v-model="form.scenario" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            <option value="expected">{{ t('budgets.scenario_expected') }}</option>
            <option value="optimistic">{{ t('budgets.scenario_optimistic') }}</option>
            <option value="pessimistic">{{ t('budgets.scenario_pessimistic') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.start_date')" required :help-text="t('budgets.help_dates')">
          <BaseInput v-model="form.start_date" type="date" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.end_date')" required>
          <BaseInput v-model="form.end_date" type="date" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.period')">
          <select v-model="form.period_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm">
            <option value="monthly">{{ t('budgets.period_monthly') }}</option>
            <option value="quarterly">{{ t('budgets.period_quarterly') }}</option>
            <option value="yearly">{{ t('budgets.period_yearly') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.cost_center')" :help-text="t('budgets.help_cost_center')">
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
      </div>

      <!-- Source year + Growth -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
        <BaseInputGroup :label="t('budgets.help_source_year')">
          <select v-model.number="sourceYear" class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.growth_pct')" :help-text="t('budgets.help_growth')">
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

      <div class="mt-6 flex justify-end">
        <BaseButton variant="primary" @click="goToStep(1)" :disabled="!isFormValid">
          {{ $t('general.next') }}
        </BaseButton>
      </div>
    </div>

    <!-- Step 2: Categories Review/Edit -->
    <div v-if="currentStep === 1">
      <!-- Loading State -->
      <div v-if="isAnalyzing" class="text-center py-16">
        <svg class="h-12 w-12 text-primary-500 animate-pulse mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
        </svg>
        <p class="text-sm text-gray-500">{{ t('budgets.analyzing_data') }}</p>
      </div>

      <template v-else>
        <!-- AI Insights Bar -->
        <div v-if="aiInsights" class="bg-indigo-50 border border-indigo-200 rounded-lg p-4 mb-6">
          <div class="flex items-start gap-3">
            <svg class="h-5 w-5 text-indigo-600 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456Z" />
            </svg>
            <div class="flex-1">
              <p class="text-xs text-indigo-500 mb-1">{{ t('budgets.help_ai') }}</p>
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
          <div class="px-4 py-3 bg-green-50 border-b border-green-100 rounded-t-lg">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-green-800">{{ t('budgets.revenue') }}</h3>
              <span class="text-sm font-bold text-green-800">{{ formatCurrency(totalRevenue) }}</span>
            </div>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="cat in revenueCategories" :key="cat.key" class="px-4 py-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <span v-if="accountCodePrefix(cat)" class="text-xs font-mono text-gray-400">{{ accountCodePrefix(cat) }}</span>
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
              <span v-if="!cat.showMonthly" class="text-xs text-gray-300 ml-2">{{ t('budgets.help_monthly') }}</span>
            </div>
          </div>
        </div>

        <!-- Expenses Section -->
        <div v-if="expenseCategories.length" class="bg-white rounded-lg shadow mb-4">
          <div class="px-4 py-3 bg-red-50 border-b border-red-100 rounded-t-lg">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-medium text-red-800">{{ t('budgets.expenses') }}</h3>
              <span class="text-sm font-bold text-red-800">{{ formatCurrency(totalExpenses) }}</span>
            </div>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="cat in expenseCategories" :key="cat.key" class="px-4 py-3">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                  <span v-if="accountCodePrefix(cat)" class="text-xs font-mono text-gray-400">{{ accountCodePrefix(cat) }}</span>
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
              <span v-if="!cat.showMonthly" class="text-xs text-gray-300 ml-2">{{ t('budgets.help_monthly') }}</span>
            </div>
          </div>
        </div>

        <!-- Add Custom Line -->
        <div class="bg-white rounded-lg shadow mb-6 px-4 py-3">
          <div v-if="showAddCustomLine" class="flex items-end gap-3 mb-3">
            <BaseInputGroup :label="t('budgets.account_type')" class="w-48">
              <select
                v-model="newCustomLine.account_type"
                class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
              >
                <option value="OPERATING_REVENUE">{{ t('budgets.revenue') }}</option>
                <option value="OPERATING_EXPENSE">{{ t('budgets.expenses') }}</option>
                <option value="DIRECT_EXPENSE">{{ t('budgets.type_direct_expense') }}</option>
                <option value="OVERHEAD_EXPENSE">{{ t('budgets.type_overhead_expense') }}</option>
                <option value="NON_OPERATING_REVENUE">{{ t('budgets.type_non_operating_revenue') }}</option>
                <option value="NON_OPERATING_EXPENSE">{{ t('budgets.type_non_operating_expense') }}</option>
              </select>
            </BaseInputGroup>
            <BaseInputGroup :label="t('budgets.select_account')" class="flex-1">
              <BaseMultiselect
                v-model="newCustomLine.account_id"
                :options="filteredIfrsAccounts"
                :searchable="true"
                label="display_name"
                value-prop="id"
                :placeholder="t('budgets.select_account')"
                track-by="display_name"
                :can-clear="true"
              />
            </BaseInputGroup>
            <BaseInputGroup :label="t('budgets.total_budgeted')" class="w-32">
              <BaseInput v-model.number="newCustomLine.amount" type="number" step="100" placeholder="0" />
            </BaseInputGroup>
            <div class="flex gap-2 pb-0.5">
              <BaseButton variant="primary" size="sm" @click="addCustomLine" :disabled="!newCustomLine.account_id || !newCustomLine.amount">
                {{ $t('general.save') }}
              </BaseButton>
              <BaseButton variant="primary-outline" size="sm" @click="showAddCustomLine = false">
                {{ $t('general.cancel') }}
              </BaseButton>
            </div>
          </div>
          <button
            v-if="!showAddCustomLine"
            type="button"
            @click="showAddCustomLine = true"
            class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            {{ t('budgets.add_account_line') }}
          </button>

          <!-- Custom lines list -->
          <div v-if="customLines.length" class="mt-3 divide-y divide-gray-100">
            <div v-for="(line, idx) in customLines" :key="'custom-' + idx" class="flex items-center justify-between py-2">
              <div class="flex items-center gap-2 text-sm text-gray-700">
                <span class="font-mono text-xs text-gray-400">{{ line.code }}</span>
                <span>{{ line.name }}</span>
                <span class="text-xs text-gray-400">({{ line.account_type }})</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm font-medium">{{ formatCurrency(line.amount) }}</span>
                <button type="button" @click="removeCustomLine(idx)" class="text-gray-400 hover:text-red-500">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation -->
        <div class="flex justify-between">
          <BaseButton variant="primary-outline" @click="currentStep = 0">
            {{ $t('general.back') }}
          </BaseButton>
          <BaseButton variant="primary" @click="goToStep(2)">
            {{ $t('general.next') }}
          </BaseButton>
        </div>
      </template>
    </div>

    <!-- Step 3: Review + Save -->
    <div v-if="currentStep === 2" class="bg-white rounded-lg shadow p-6">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('budgets.step_review') }}</h3>

      <!-- Summary grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">{{ t('budgets.name') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ form.name }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">{{ t('budgets.scenario') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ scenarioLabel(form.scenario) }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-500">{{ t('budgets.period') }}</p>
          <p class="text-sm font-medium text-gray-900">{{ form.start_date }} - {{ form.end_date }}</p>
        </div>
        <div class="bg-primary-50 rounded-lg p-3">
          <p class="text-xs text-primary-600">{{ t('budgets.total_budgeted') }}</p>
          <p class="text-lg font-bold text-primary-700">{{ formatCurrency(grandTotal) }}</p>
        </div>
      </div>

      <!-- Categories table -->
      <div class="overflow-hidden rounded-lg border border-gray-200 mb-6">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('budgets.account_type') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('budgets.total_budgeted') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="cat in allCategoriesForReview" :key="cat.key || cat.label">
              <td class="px-4 py-2 text-sm text-gray-900">
                <span v-if="cat.code" class="font-mono text-xs text-gray-400 mr-1">{{ cat.code }}</span>
                {{ cat.label }}
              </td>
              <td class="px-4 py-2 text-sm text-right font-medium" :class="cat.total > 0 ? 'text-gray-900' : 'text-gray-400'">
                {{ formatCurrency(cat.total) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Actions -->
      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 1">
          {{ $t('general.back') }}
        </BaseButton>
        <div class="flex space-x-3">
          <BaseButton variant="primary-outline" @click="saveBudget('draft')" :disabled="isSaving" :title="t('budgets.help_save_draft')">
            {{ t('budgets.save_draft') }}
          </BaseButton>
          <BaseButton variant="primary" @click="saveBudget('approve')" :disabled="isSaving" :title="t('budgets.help_approve')">
            {{ t('budgets.approve') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'
import CostCenterModal from '@/scripts/admin/components/modal-components/CostCenterModal.vue'

const props = defineProps({
  initialData: { type: Object, default: null },
  isEdit: { type: Boolean, default: false },
})
const emit = defineEmits(['saved'])
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
const currentStep = ref(0)
const isAnalyzing = ref(false)
const isLoadingAi = ref(false)
const isSaving = ref(false)
const costCenters = ref([])
const sourceYear = ref(currentYear - 1)
const growthPct = ref(0)
const aiInsights = ref(null)
const aiUsage = ref(null)
const categories = ref([])
const customLines = ref([])
const ifrsAccounts = ref([])
const showAddCustomLine = ref(false)
const newCustomLine = ref({ account_type: 'OPERATING_EXPENSE', account_id: null, amount: 0 })
const dataLoadedOnce = ref(false)

const steps = computed(() => [
  t('budgets.step_basic_info'),
  t('budgets.step_categories'),
  t('budgets.step_review'),
])

const form = reactive({
  name: props.initialData?.name || `${t('budgets.title')} ${currentYear}`,
  period_type: props.initialData?.period_type || 'monthly',
  scenario: props.initialData?.scenario || 'expected',
  cost_center_id: props.initialData?.cost_center_id || null,
  start_date: props.initialData?.start_date || `${currentYear}-01-01`,
  end_date: props.initialData?.end_date || `${currentYear}-12-31`,
})

const availableYears = computed(() => {
  const years = []
  for (let y = currentYear; y >= currentYear - 5; y--) years.push(y)
  return years
})

const isFormValid = computed(() =>
  form.name && form.start_date && form.end_date && form.end_date > form.start_date
)

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

const grandTotal = computed(() => {
  let total = categories.value.reduce((sum, c) => sum + (c.adjusted_total || 0), 0)
  total += customLines.value.reduce((sum, l) => sum + (l.amount || 0), 0)
  return total
})

const allCategoriesForReview = computed(() => {
  const items = categories.value
    .filter(c => c.adjusted_total > 0)
    .map(c => ({
      key: c.key,
      label: c.label,
      code: accountCodePrefix(c),
      total: c.adjusted_total,
    }))
  for (const line of customLines.value) {
    items.push({
      key: 'custom-' + line.account_id,
      label: line.name,
      code: line.code,
      total: line.amount,
    })
  }
  return items
})

const filteredIfrsAccounts = computed(() => {
  const existingIds = customLines.value.map(l => l.account_id)
  return ifrsAccounts.value
    .filter(a => !existingIds.includes(a.id))
    .map(a => ({ ...a, display_name: `${a.code} - ${a.name}` }))
})

// MK chart-of-accounts code prefix mapping
const ACCOUNT_CODE_PREFIXES = {
  OPERATING_REVENUE: '70xx',
  NON_OPERATING_REVENUE: '76xx',
  OPERATING_EXPENSE: '40xx',
  DIRECT_EXPENSE: '41xx',
  OVERHEAD_EXPENSE: '42xx',
  NON_OPERATING_EXPENSE: '46xx',
  CURRENT_ASSET: '10xx',
  NON_CURRENT_ASSET: '02xx',
  CURRENT_LIABILITY: '22xx',
  NON_CURRENT_LIABILITY: '20xx',
  invoice_revenue: '70xx',
  recurring_revenue: '75xx',
  bill_expenses: '40xx',
  recurring_expenses: '42xx',
  salary_expenses: '44xx',
  other_expenses: '46xx',
}

function accountCodePrefix(cat) {
  return ACCOUNT_CODE_PREFIXES[cat.key] || ACCOUNT_CODE_PREFIXES[cat.account_type] || null
}

// Watch growth slider to update all amounts
watch(growthPct, (newPct) => {
  const multiplier = 1 + (newPct / 100)
  for (const cat of categories.value) {
    cat.adjusted_total = Math.round(cat.original_total * multiplier)
    for (const m of Object.keys(cat.original_monthly)) {
      cat.monthly[m] = Math.round(cat.original_monthly[m] * multiplier)
    }
  }
})

onMounted(async () => {
  await loadCostCenters()
  loadIfrsAccounts()

  if (props.isEdit && props.initialData) {
    populateFromInitialData()
  }
})

function populateFromInitialData() {
  const data = props.initialData
  if (!data) return

  // Build categories from initialData.lines
  if (data.lines && data.lines.length > 0) {
    const catMap = {}
    for (const line of data.lines) {
      const type = line.account_type
      if (!catMap[type]) {
        catMap[type] = {
          key: type,
          label: type,
          account_type: type,
          original_total: 0,
          adjusted_total: 0,
          monthly: {},
          original_monthly: {},
          showMonthly: false,
        }
        for (let m = 1; m <= 12; m++) {
          catMap[type].monthly[m] = 0
          catMap[type].original_monthly[m] = 0
        }
      }
      const amount = parseFloat(line.amount) || 0
      catMap[type].adjusted_total += amount
      catMap[type].original_total += amount

      // Determine month from period_start
      const startDate = new Date(line.period_start)
      const month = startDate.getMonth() + 1
      catMap[type].monthly[month] = (catMap[type].monthly[month] || 0) + amount
      catMap[type].original_monthly[month] = (catMap[type].original_monthly[month] || 0) + amount
    }
    categories.value = Object.values(catMap)
  }

  dataLoadedOnce.value = true
}

async function loadCostCenters() {
  try {
    const response = await window.axios.get('/cost-centers')
    costCenters.value = response.data?.data || []
  } catch {
    costCenters.value = []
  }
}

async function loadIfrsAccounts() {
  try {
    const response = await window.axios.get('/accounting/accounts')
    ifrsAccounts.value = (response.data?.data || []).map(a => ({
      id: a.id,
      code: a.code,
      name: a.name,
      type: a.type,
    }))
  } catch {
    ifrsAccounts.value = []
  }
}

async function analyzeData() {
  isAnalyzing.value = true
  aiInsights.value = null

  try {
    const response = await window.axios.post('/budgets/smart-budget', {
      year: sourceYear.value,
      growth_pct: growthPct.value,
      locale: locale.value || 'mk',
    })

    const data = response.data?.data
    const hasData = data?.has_data || false

    if (data?.categories && hasData) {
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

    dataLoadedOnce.value = true

    // Try AI insights (non-blocking)
    loadAiInsights()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('budgets.error_loading'),
    })
    dataLoadedOnce.value = true
  } finally {
    isAnalyzing.value = false
  }
}

async function loadAiInsights() {
  if (categories.value.length === 0) return
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

function scenarioLabel(scenario) {
  const labels = {
    expected: t('budgets.scenario_expected'),
    optimistic: t('budgets.scenario_optimistic'),
    pessimistic: t('budgets.scenario_pessimistic'),
  }
  return labels[scenario] || scenario
}

function updateMonthly(cat, month, value) {
  cat.monthly[month] = parseFloat(value) || 0
  let newTotal = 0
  for (let m = 1; m <= 12; m++) {
    newTotal += cat.monthly[m] || 0
  }
  cat.adjusted_total = Math.round(newTotal)
}

function addCustomLine() {
  const accountId = newCustomLine.value.account_id
  const amount = newCustomLine.value.amount
  if (!accountId || !amount) return

  const account = ifrsAccounts.value.find(a => a.id === accountId)
  if (!account) return

  // Check for duplicates
  if (customLines.value.some(l => l.account_id === accountId)) return

  customLines.value.push({
    account_type: newCustomLine.value.account_type,
    account_id: accountId,
    code: account.code,
    name: account.name,
    amount,
  })

  newCustomLine.value = { account_type: newCustomLine.value.account_type, account_id: null, amount: 0 }
  showAddCustomLine.value = false
}

function removeCustomLine(idx) {
  customLines.value.splice(idx, 1)
}

function goToStep(step) {
  if (step === 1 && !isFormValid.value) return

  // Auto-load categories when entering step 2 for the first time (create mode)
  if (step === 1 && !props.isEdit && !dataLoadedOnce.value) {
    currentStep.value = step
    analyzeData()
    return
  }

  currentStep.value = step
}

function buildBudgetLines() {
  const lines = []
  const startYear = parseInt(form.start_date.split('-')[0])

  for (const cat of categories.value) {
    if (cat.adjusted_total == null || cat.adjusted_total === 0) continue

    const totalFromMonthly = Object.values(cat.monthly).reduce((s, v) => s + (v || 0), 0)

    if (form.period_type === 'monthly') {
      for (let m = 1; m <= 12; m++) {
        let amount
        if (totalFromMonthly > 0) {
          const ratio = (cat.monthly[m] || 0) / totalFromMonthly
          amount = Math.round(cat.adjusted_total * ratio * 100) / 100
        } else {
          amount = Math.round(cat.adjusted_total / 12 * 100) / 100
        }
        if (amount === 0) continue

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
        if (amount === 0) continue

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

  // Append custom lines
  for (const line of customLines.value) {
    if (!line.amount || line.amount === 0) continue

    if (form.period_type === 'monthly') {
      const monthlyAmount = Math.round(line.amount / 12 * 100) / 100
      for (let m = 1; m <= 12; m++) {
        const periodStart = `${startYear}-${String(m).padStart(2, '0')}-01`
        const periodEnd = new Date(startYear, m, 0).toISOString().split('T')[0]
        lines.push({
          account_type: line.account_type,
          ifrs_account_id: line.account_id,
          period_start: periodStart,
          period_end: periodEnd,
          amount: monthlyAmount,
        })
      }
    } else if (form.period_type === 'quarterly') {
      const qAmount = Math.round(line.amount / 4 * 100) / 100
      for (let q = 0; q < 4; q++) {
        const periodStart = `${startYear}-${String(q * 3 + 1).padStart(2, '0')}-01`
        const periodEnd = new Date(startYear, (q + 1) * 3, 0).toISOString().split('T')[0]
        lines.push({
          account_type: line.account_type,
          ifrs_account_id: line.account_id,
          period_start: periodStart,
          period_end: periodEnd,
          amount: qAmount,
        })
      }
    } else {
      lines.push({
        account_type: line.account_type,
        ifrs_account_id: line.account_id,
        period_start: form.start_date,
        period_end: form.end_date,
        amount: line.amount,
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

    let response
    if (props.isEdit && props.initialData?.id) {
      response = await window.axios.put(`/budgets/${props.initialData.id}`, payload)
    } else {
      response = await window.axios.post('/budgets', payload)
    }

    const budgetId = response.data?.data?.id

    if (action === 'approve' && budgetId) {
      await window.axios.post(`/budgets/${budgetId}/approve`)
    }

    notificationStore.showNotification({
      type: 'success',
      message: props.isEdit ? (t('budgets.updated_success') || t('budgets.created_success')) : t('budgets.created_success'),
    })

    if (props.isEdit) {
      emit('saved')
    } else {
      router.push({ name: 'budgets.index' })
    }
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

// CLAUDE-CHECKPOINT
