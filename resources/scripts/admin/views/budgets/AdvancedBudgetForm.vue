<template>
  <div>
    <CostCenterModal />

    <!-- Link to switch mode -->
    <div class="mb-4 flex justify-end">
      <button
        type="button"
        @click="$emit('switchMode', 'smart')"
        class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-800"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
        </svg>
        {{ t('budgets.switch_to_smart') }}
      </button>
    </div>

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
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('budgets.step_details') }}</h3>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <BaseInputGroup :label="t('budgets.name')" required>
          <BaseInput v-model="form.name" :placeholder="t('budgets.name')" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.scenario')">
          <select v-model="form.scenario" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option value="expected">{{ t('budgets.scenario_expected') }}</option>
            <option value="optimistic">{{ t('budgets.scenario_optimistic') }}</option>
            <option value="pessimistic">{{ t('budgets.scenario_pessimistic') }}</option>
          </select>
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.period')">
          <select v-model="form.period_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
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
              class="flex items-center justify-center w-9 h-9 rounded-md border border-gray-300 bg-white hover:bg-gray-50 text-gray-500 hover:text-primary-600"
              :title="t('budgets.create_cost_center')"
            >
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
              </svg>
            </button>
          </div>
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.start_date')" required>
          <BaseInput v-model="form.start_date" type="date" />
        </BaseInputGroup>

        <BaseInputGroup :label="t('budgets.end_date')" required>
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
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-wrap gap-2">
        <h3 class="text-lg font-medium text-gray-900">{{ t('budgets.step_lines') }}</h3>
        <div class="flex items-center space-x-3">
          <div class="flex items-center space-x-2">
            <select v-model="prefillYear" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
              <option v-for="y in prefillYears" :key="y" :value="y">{{ y }}</option>
            </select>
            <BaseInputGroup :label="t('budgets.growth_pct')" class="w-24">
              <BaseInput v-model.number="prefillGrowth" type="number" step="0.1" placeholder="0" class="text-sm" />
            </BaseInputGroup>
            <BaseButton variant="primary-outline" size="sm" @click="prefillFromActuals" :disabled="isPrefilling">
              {{ t('budgets.prefill_actuals') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="sticky left-0 bg-gray-50 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase min-w-[200px] z-10">
                {{ t('budgets.account_type') }}
              </th>
              <th
                v-for="period in gridPeriods"
                :key="period.key"
                class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase min-w-[120px]"
              >
                {{ period.label }}
              </th>
              <th class="px-3 py-3 text-right text-xs font-medium text-gray-500 uppercase min-w-[120px] bg-gray-100">
                {{ t('budgets.total_budgeted') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 bg-white">
            <template v-for="cat in accountCategories" :key="cat.type">
              <!-- Type total row -->
              <tr class="hover:bg-gray-50">
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
              <!-- Account-level sub-rows -->
              <tr
                v-for="acctRow in getAccountRowsForType(cat.type)"
                :key="'acct-' + acctRow.account_id"
                class="hover:bg-blue-50 bg-gray-50/50"
              >
                <td class="sticky left-0 bg-gray-50/50 px-4 py-1 text-xs text-gray-600 z-10 pl-8">
                  <div class="flex items-center gap-1">
                    <span class="font-mono text-gray-500">{{ acctRow.code }}</span>
                    <span class="truncate">{{ acctRow.name }}</span>
                    <button
                      type="button"
                      @click="removeAccountRow(cat.type, acctRow.account_id)"
                      class="ml-1 text-gray-400 hover:text-red-500"
                      :title="t('budgets.remove_account_line')"
                    >
                      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                      </svg>
                    </button>
                  </div>
                </td>
                <td v-for="period in gridPeriods" :key="period.key" class="px-1 py-1">
                  <input
                    type="number"
                    step="0.01"
                    :value="getGridValue(acctRow.gridKey, period.key)"
                    @change="setGridValue(acctRow.gridKey, period.key, $event.target.value)"
                    class="w-full rounded border-gray-200 text-xs text-right py-1 px-2 focus:border-primary-500 focus:ring-primary-500"
                    placeholder="0.00"
                  />
                </td>
                <td class="px-3 py-1 text-xs text-right font-medium text-gray-600 bg-gray-50">
                  {{ formatNumber(getRowTotal(acctRow.gridKey)) }}
                </td>
              </tr>
            </template>
            <tr class="bg-primary-50 font-semibold">
              <td class="sticky left-0 bg-primary-50 px-4 py-3 text-sm text-primary-900 z-10">
                {{ t('budgets.total_budgeted') }}
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

      <!-- Add Account Line -->
      <div class="px-6 py-3 border-t border-gray-100">
        <div v-if="showAddAccountRow" class="flex items-end gap-3 mb-3">
          <BaseInputGroup :label="t('budgets.account_type')" class="w-48">
            <select
              v-model="newAccountLine.account_type"
              class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
              <option v-for="cat in accountCategories" :key="cat.type" :value="cat.type">{{ cat.label }}</option>
            </select>
          </BaseInputGroup>
          <BaseInputGroup :label="t('budgets.select_account')" class="flex-1">
            <BaseMultiselect
              v-model="newAccountLine.account_id"
              :options="filteredAccounts"
              :searchable="true"
              label="display_name"
              value-prop="id"
              :placeholder="t('budgets.select_account')"
              track-by="display_name"
              :can-clear="true"
            />
          </BaseInputGroup>
          <div class="flex gap-2 pb-0.5">
            <BaseButton variant="primary" size="sm" @click="addAccountLineRow" :disabled="!newAccountLine.account_id">
              {{ $t('general.save') }}
            </BaseButton>
            <BaseButton variant="primary-outline" size="sm" @click="showAddAccountRow = false">
              {{ $t('general.cancel') }}
            </BaseButton>
          </div>
        </div>
        <button
          v-if="!showAddAccountRow && ifrsAccounts.length > 0"
          type="button"
          @click="showAddAccountRow = true"
          class="inline-flex items-center gap-1.5 text-sm text-primary-600 hover:text-primary-800"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          {{ t('budgets.add_account_line') }}
        </button>
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
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ t('budgets.step_review') }}</h3>

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
          <p class="text-lg font-bold text-primary-700">{{ formatNumber(grandTotal) }}</p>
        </div>
      </div>

      <div class="overflow-hidden rounded-lg border border-gray-200 mb-6">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ t('budgets.account_type') }}</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ t('budgets.total_budgeted') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <template v-for="cat in accountCategories" :key="cat.type">
              <tr>
                <td class="px-4 py-2 text-sm text-gray-900">{{ cat.label }}</td>
                <td class="px-4 py-2 text-sm text-right font-medium" :class="getRowTotal(cat.type) > 0 ? 'text-gray-900' : 'text-gray-400'">
                  {{ formatNumber(getRowTotal(cat.type)) }}
                </td>
              </tr>
              <tr v-for="acctRow in getAccountRowsForType(cat.type)" :key="'review-' + acctRow.account_id" class="bg-gray-50/50">
                <td class="px-4 py-1 text-xs text-gray-600 pl-8">
                  <span class="font-mono text-gray-500">{{ acctRow.code }}</span> {{ acctRow.name }}
                </td>
                <td class="px-4 py-1 text-xs text-right font-medium" :class="getRowTotal(acctRow.gridKey) > 0 ? 'text-gray-600' : 'text-gray-400'">
                  {{ formatNumber(getRowTotal(acctRow.gridKey)) }}
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="flex justify-between">
        <BaseButton variant="primary-outline" @click="currentStep = 1">
          {{ $t('general.back') }}
        </BaseButton>
        <div class="flex space-x-3">
          <BaseButton variant="primary-outline" @click="saveBudget('draft')" :disabled="isSaving">
            {{ t('budgets.save_draft') }}
          </BaseButton>
          <BaseButton variant="primary" @click="saveBudget('approve')" :disabled="isSaving">
            {{ t('budgets.approve') }}
          </BaseButton>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'
import CostCenterModal from '@/scripts/admin/components/modal-components/CostCenterModal.vue'

const props = defineProps({
  initialData: { type: Object, default: null },
  isEdit: { type: Boolean, default: false },
})
const emit = defineEmits(['switchMode', 'saved'])
const router = useRouter()
const { t } = useI18n()
const modalStore = useModalStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function accountTypeLabel(type) {
  const typeKey = 'budgets.type_' + type.toLowerCase()
  return t(typeKey) || type
}

const currentStep = ref(0)
const isSaving = ref(false)
const isPrefilling = ref(false)
const costCenters = ref([])
const prefillYear = ref(new Date().getFullYear() - 1)
const prefillGrowth = ref(0)

// Account-level detail state
const ifrsAccounts = ref([])
const accountRows = ref([]) // { account_type, account_id, code, name, gridKey }
const showAddAccountRow = ref(false)
const newAccountLine = ref({ account_type: 'OPERATING_REVENUE', account_id: null })

const steps = [t('budgets.step_details'), t('budgets.step_lines'), t('budgets.step_review')]

const form = ref({
  name: '',
  period_type: 'monthly',
  scenario: 'expected',
  cost_center_id: null,
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: `${new Date().getFullYear()}-12-31`,
})

const gridData = ref({})

const accountCategories = [
  { type: 'OPERATING_REVENUE', label: t('budgets.revenue') + ' - ' + accountTypeLabel('OPERATING_REVENUE') },
  { type: 'NON_OPERATING_REVENUE', label: t('budgets.revenue') + ' - ' + accountTypeLabel('NON_OPERATING_REVENUE') },
  { type: 'OPERATING_EXPENSE', label: t('budgets.expenses') + ' - ' + accountTypeLabel('OPERATING_EXPENSE') },
  { type: 'DIRECT_EXPENSE', label: t('budgets.expenses') + ' - ' + accountTypeLabel('DIRECT_EXPENSE') },
  { type: 'OVERHEAD_EXPENSE', label: t('budgets.expenses') + ' - ' + accountTypeLabel('OVERHEAD_EXPENSE') },
  { type: 'NON_OPERATING_EXPENSE', label: t('budgets.expenses') + ' - ' + accountTypeLabel('NON_OPERATING_EXPENSE') },
  { type: 'CURRENT_ASSET', label: t('budgets.assets') + ' - ' + accountTypeLabel('CURRENT_ASSET') },
  { type: 'NON_CURRENT_ASSET', label: t('budgets.assets') + ' - ' + accountTypeLabel('NON_CURRENT_ASSET') },
  { type: 'CURRENT_LIABILITY', label: t('budgets.liabilities') + ' - ' + accountTypeLabel('CURRENT_LIABILITY') },
  { type: 'NON_CURRENT_LIABILITY', label: t('budgets.liabilities') + ' - ' + accountTypeLabel('NON_CURRENT_LIABILITY') },
]

const prefillYears = computed(() => {
  const years = []
  const curr = new Date().getFullYear()
  for (let y = curr; y >= curr - 5; y--) years.push(y)
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
      periods.push({
        key: `${current.getFullYear()}-Q${q}`,
        label: `Q${q} ${current.getFullYear()}`,
        start: `${current.getFullYear()}-${String(current.getMonth() + 1).padStart(2, '0')}-01`,
        end: new Date(current.getFullYear(), current.getMonth() + 3, 0).toISOString().split('T')[0],
      })
      current = new Date(current.getFullYear(), current.getMonth() + 3, 1)
    }
  } else {
    let y = start.getFullYear()
    while (y <= end.getFullYear()) {
      periods.push({ key: `${y}`, label: `${y}`, start: `${y}-01-01`, end: `${y}-12-31` })
      y++
    }
  }
  return periods
})

const isStep1Valid = computed(() =>
  form.value.name && form.value.start_date && form.value.end_date && form.value.end_date > form.value.start_date
)

const grandTotal = computed(() => {
  let total = 0
  for (const key of Object.keys(gridData.value)) total += parseFloat(gridData.value[key]) || 0
  return total
})

const filteredAccounts = computed(() => {
  const existingIds = accountRows.value.map(r => r.account_id)
  return ifrsAccounts.value
    .filter(a => !existingIds.includes(a.id))
    .map(a => ({ ...a, display_name: `${a.code} - ${a.name}` }))
})

function getAccountRowsForType(accountType) {
  return accountRows.value.filter(r => r.account_type === accountType)
}

function addAccountLineRow() {
  const accountId = newAccountLine.value.account_id
  const accountType = newAccountLine.value.account_type
  if (!accountId) return
  const account = ifrsAccounts.value.find(a => a.id === accountId)
  if (!account) return
  // Check for duplicates
  if (accountRows.value.some(r => r.account_id === accountId && r.account_type === accountType)) return
  accountRows.value.push({
    account_type: accountType,
    account_id: accountId,
    code: account.code,
    name: account.name,
    gridKey: `ACCT_${accountId}`,
  })
  newAccountLine.value = { account_type: accountType, account_id: null }
  showAddAccountRow.value = false
}

function removeAccountRow(accountType, accountId) {
  const idx = accountRows.value.findIndex(r => r.account_type === accountType && r.account_id === accountId)
  if (idx === -1) return
  const gridKey = accountRows.value[idx].gridKey
  // Remove grid data for this account row
  for (const key of Object.keys(gridData.value)) {
    if (key.startsWith(gridKey + '|')) delete gridData.value[key]
  }
  accountRows.value.splice(idx, 1)
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
  } catch { ifrsAccounts.value = [] }
}

onMounted(async () => {
  await loadCostCenters()
  loadIfrsAccounts()
  if (props.initialData) {
    form.value.name = props.initialData.name || ''
    form.value.scenario = props.initialData.scenario || 'expected'
    form.value.period_type = props.initialData.period_type || 'monthly'
    form.value.cost_center_id = props.initialData.cost_center_id || null
    form.value.start_date = props.initialData.start_date || form.value.start_date
    form.value.end_date = props.initialData.end_date || form.value.end_date
    // Pre-populate grid from initialData.lines
    if (props.initialData.lines && props.initialData.lines.length > 0) {
      gridData.value = {}
      for (const line of props.initialData.lines) {
        // Build period key from line dates
        const startDate = new Date(line.period_start)
        let periodKey
        if (form.value.period_type === 'monthly') {
          periodKey = `${startDate.getFullYear()}-${String(startDate.getMonth() + 1).padStart(2, '0')}`
        } else if (form.value.period_type === 'quarterly') {
          const q = Math.floor(startDate.getMonth() / 3) + 1
          periodKey = `${startDate.getFullYear()}-Q${q}`
        } else {
          periodKey = `${startDate.getFullYear()}`
        }

        // Account-level lines use a separate grid key
        if (line.ifrs_account_id && line.ifrs_account) {
          const gridKey = `ACCT_${line.ifrs_account_id}`
          // Register the account row if not already present
          if (!accountRows.value.some(r => r.account_id === line.ifrs_account_id && r.account_type === line.account_type)) {
            accountRows.value.push({
              account_type: line.account_type,
              account_id: line.ifrs_account_id,
              code: line.ifrs_account.code,
              name: line.ifrs_account.name,
              gridKey,
            })
          }
          const key = `${gridKey}|${periodKey}`
          gridData.value[key] = (gridData.value[key] || 0) + parseFloat(line.amount || 0)
        } else {
          const key = `${line.account_type}|${periodKey}`
          gridData.value[key] = (gridData.value[key] || 0) + parseFloat(line.amount || 0)
        }
      }
    }
  }
})

async function loadCostCenters() {
  try {
    const response = await window.axios.get('/cost-centers')
    costCenters.value = response.data?.data || []
  } catch { costCenters.value = [] }
}

function openCostCenterModal() {
  modalStore.openModal({
    componentName: 'CostCenterModal',
    title: t('budgets.create_cost_center'),
    refreshData: (newCC) => {
      costCenters.value.push(newCC)
      form.value.cost_center_id = newCC.id
    },
  })
}

function goToStep(step) {
  if (step === 1 && !isStep1Valid.value) return
  currentStep.value = step
}

function getGridValue(accountType, periodKey) {
  return gridData.value[`${accountType}|${periodKey}`] || ''
}

function setGridValue(accountType, periodKey, value) {
  const key = `${accountType}|${periodKey}`
  const numVal = parseFloat(value) || 0
  if (numVal === 0) delete gridData.value[key]
  else gridData.value[key] = numVal
}

function getRowTotal(accountType) {
  let total = 0
  for (const period of gridPeriods.value) total += parseFloat(gridData.value[`${accountType}|${period.key}`]) || 0
  return total
}

function getColumnTotal(periodKey) {
  let total = 0
  for (const cat of accountCategories) total += parseFloat(gridData.value[`${cat.type}|${periodKey}`]) || 0
  for (const acctRow of accountRows.value) total += parseFloat(gridData.value[`${acctRow.gridKey}|${periodKey}`]) || 0
  return total
}

function formatNumber(val) {
  return Number(val || 0).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function scenarioLabel(scenario) {
  const labels = {
    expected: t('budgets.scenario_expected'),
    optimistic: t('budgets.scenario_optimistic'),
    pessimistic: t('budgets.scenario_pessimistic'),
  }
  return labels[scenario] || scenario
}

async function prefillFromActuals() {
  isPrefilling.value = true
  try {
    const response = await window.axios.post('/budgets/prefill-actuals', {
      year: prefillYear.value,
      growth_pct: prefillGrowth.value || 0,
    })
    const data = response.data?.data
    if (data?.lines) {
      gridData.value = {}
      for (const line of data.lines) {
        const month = String(line.month).padStart(2, '0')
        let periodKey
        if (form.value.period_type === 'monthly') periodKey = `${data.target_year}-${month}`
        else if (form.value.period_type === 'quarterly') periodKey = `${data.target_year}-Q${Math.ceil(line.month / 3)}`
        else periodKey = data.target_year
        const key = `${line.account_type}|${periodKey}`
        gridData.value[key] = (gridData.value[key] || 0) + line.amount
      }
      form.value.start_date = `${data.target_year}-01-01`
      form.value.end_date = `${data.target_year}-12-31`
      notificationStore.showNotification({
        type: 'success',
        message: t('budgets.prefilled_success', { count: data.lines.length, year: data.source_year }),
      })
    }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('budgets.error_loading'),
    })
  } finally { isPrefilling.value = false }
}

function buildLines() {
  const lines = []
  for (const [key, amount] of Object.entries(gridData.value)) {
    if (!amount || parseFloat(amount) === 0) continue
    const [rowKey, periodKey] = key.split('|')
    const period = gridPeriods.value.find(p => p.key === periodKey)
    if (!period) continue

    // Check if this is an account-level row (ACCT_<id>)
    const acctRow = rowKey.startsWith('ACCT_')
      ? accountRows.value.find(r => r.gridKey === rowKey)
      : null

    lines.push({
      account_type: acctRow ? acctRow.account_type : rowKey,
      ifrs_account_id: acctRow ? acctRow.account_id : null,
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
    if (lines.length === 0) {
      notificationStore.showNotification({ type: 'error', message: t('budgets.no_budget_lines') })
      isSaving.value = false
      return
    }
    const payload = {
      name: form.value.name,
      period_type: form.value.period_type,
      start_date: form.value.start_date,
      end_date: form.value.end_date,
      scenario: form.value.scenario,
      cost_center_id: form.value.cost_center_id || null,
      lines,
    }
    let response
    if (props.isEdit && props.initialData?.id) {
      response = await window.axios.put(`/budgets/${props.initialData.id}`, payload)
    } else {
      response = await window.axios.post('/budgets', payload)
    }
    const budgetId = response.data?.data?.id
    if (action === 'approve' && budgetId) await window.axios.post(`/budgets/${budgetId}/approve`)
    notificationStore.showNotification({ type: 'success', message: props.isEdit ? t('budgets.updated_success') || t('budgets.created_success') : t('budgets.created_success') })
    if (props.isEdit) {
      emit('saved')
    } else {
      router.push({ name: 'budgets.index' })
    }
  } catch (error) {
    notificationStore.showNotification({ type: 'error', message: error.response?.data?.error || t('budgets.error_creating') })
  } finally { isSaving.value = false }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
