<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('banking.title')" to="/admin/banking" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-4">
          <BaseButton
            variant="primary-outline"
            @click="fetchRules"
            :disabled="isLoading"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowPathIcon" :class="[slotProps.class, { 'animate-spin': isLoading }]" />
            </template>
            {{ $t('general.refresh') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            @click="openCreateModal"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('matching_rules.create_rule') || 'Create Rule' }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-if="!isLoading && rules.length === 0"
      :title="$t('matching_rules.no_rules') || 'No matching rules'"
      :description="$t('matching_rules.no_rules_description') || 'Create matching rules to automatically categorize and match bank transactions.'"
    >
      <AdjustmentsHorizontalIcon class="mt-5 mb-4 w-20 h-20 text-gray-300" />
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="openCreateModal"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('matching_rules.create_first_rule') || 'Create Your First Rule' }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Loading State -->
    <div v-if="isLoading" class="mt-6 flex justify-center">
      <BaseContentPlaceholders>
        <BaseContentPlaceholdersBox :rounded="true" />
        <BaseContentPlaceholdersBox :rounded="true" />
      </BaseContentPlaceholders>
    </div>

    <!-- Rules List -->
    <div v-if="!isLoading && rules.length > 0" class="mt-6 space-y-4">
      <div
        v-for="rule in rules"
        :key="rule.id"
        class="bg-white rounded-lg shadow p-6 border border-gray-200"
      >
        <div class="flex items-start justify-between">
          <!-- Rule Info -->
          <div class="flex-1">
            <div class="flex items-center space-x-3 mb-2">
              <h3 class="text-lg font-semibold text-gray-900">{{ rule.name }}</h3>
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
              >
                {{ rule.is_active ? ($t('general.active') || 'Active') : ($t('general.inactive') || 'Inactive') }}
              </span>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                {{ $t('matching_rules.priority') || 'Priority' }}: {{ rule.priority }}
              </span>
            </div>

            <!-- Conditions Summary -->
            <div class="mb-2">
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                {{ $t('matching_rules.conditions') || 'Conditions' }}
              </p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="(condition, idx) in rule.conditions"
                  :key="idx"
                  class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700"
                >
                  {{ condition.field }}
                  <span class="mx-1 text-gray-400">{{ formatOperator(condition.operator) }}</span>
                  "{{ truncateValue(condition.value) }}"
                </span>
              </div>
            </div>

            <!-- Actions Summary -->
            <div>
              <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">
                {{ $t('matching_rules.actions') || 'Actions' }}
              </p>
              <div class="flex flex-wrap gap-2">
                <span
                  v-for="(action, idx) in rule.actions"
                  :key="idx"
                  class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium"
                  :class="getActionClass(action.action)"
                >
                  {{ formatAction(action) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Actions Buttons -->
          <div class="ml-6 flex items-center space-x-2">
            <BaseButton
              variant="primary-outline"
              size="sm"
              @click="testRule(rule)"
              :disabled="testingRuleId === rule.id"
            >
              <template #left="slotProps">
                <BaseIcon name="BeakerIcon" :class="[slotProps.class, { 'animate-pulse': testingRuleId === rule.id }]" />
              </template>
              {{ $t('matching_rules.test') || 'Test' }}
            </BaseButton>
            <BaseButton
              variant="primary-outline"
              size="sm"
              @click="openEditModal(rule)"
            >
              <template #left="slotProps">
                <BaseIcon name="PencilIcon" :class="slotProps.class" />
              </template>
              {{ $t('general.edit') }}
            </BaseButton>
            <BaseButton
              variant="danger"
              size="sm"
              @click="confirmDelete(rule)"
            >
              <template #left="slotProps">
                <BaseIcon name="TrashIcon" :class="slotProps.class" />
              </template>
              {{ $t('general.delete') }}
            </BaseButton>
          </div>
        </div>

        <!-- Test Results (shown inline when testing) -->
        <div
          v-if="testResults && testResults.rule_id === rule.id"
          class="mt-4 p-4 rounded-lg border"
          :class="testResults.matched_count > 0 ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'"
        >
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-900">
              {{ $t('matching_rules.test_results') || 'Test Results' }}
            </p>
            <button
              class="text-gray-400 hover:text-gray-600"
              @click="testResults = null"
            >
              <BaseIcon name="XMarkIcon" class="h-4 w-4" />
            </button>
          </div>
          <p class="text-sm text-gray-600 mb-2">
            {{ $t('matching_rules.tested_against') || 'Tested against' }}
            {{ testResults.tested_count }}
            {{ $t('matching_rules.recent_transactions') || 'recent transactions' }}:
            <span class="font-semibold" :class="testResults.matched_count > 0 ? 'text-green-700' : 'text-gray-700'">
              {{ testResults.matched_count }} {{ $t('matching_rules.matches_found') || 'matches found' }}
            </span>
          </p>

          <div v-if="testResults.matches.length > 0" class="space-y-2 max-h-48 overflow-y-auto">
            <div
              v-for="match in testResults.matches"
              :key="match.id"
              class="flex items-center justify-between p-2 bg-white rounded border border-gray-100 text-sm"
            >
              <div>
                <span class="font-medium">{{ match.description || 'No description' }}</span>
                <span class="text-gray-500 ml-2">{{ formatDate(match.transaction_date) }}</span>
              </div>
              <span :class="match.amount > 0 ? 'text-green-600' : 'text-red-600'" class="font-medium">
                {{ match.amount > 0 ? '+' : '' }}{{ formatMoney(match.amount, match.currency) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Create/Edit Rule Modal -->
    <BaseModal
      :show="showRuleModal"
      @close="closeRuleModal"
      @update:show="showRuleModal = $event"
    >
      <template #header>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ editingRule ? ($t('matching_rules.edit_rule') || 'Edit Rule') : ($t('matching_rules.create_rule') || 'Create Rule') }}
        </h3>
      </template>

      <div class="p-6 space-y-6">
        <!-- Rule Name -->
        <BaseInputGroup :label="$t('matching_rules.rule_name') || 'Rule Name'" required>
          <BaseInput
            v-model="ruleForm.name"
            :placeholder="$t('matching_rules.rule_name_placeholder') || 'e.g., Monthly hosting fee'"
            type="text"
          />
        </BaseInputGroup>

        <!-- Priority -->
        <BaseInputGroup :label="$t('matching_rules.priority') || 'Priority'" :content-loading="false">
          <BaseInput
            v-model.number="ruleForm.priority"
            type="number"
            min="0"
            max="1000"
            :placeholder="'0'"
          />
          <p class="text-xs text-gray-500 mt-1">
            {{ $t('matching_rules.priority_help') || 'Higher priority rules are evaluated first (0-1000).' }}
          </p>
        </BaseInputGroup>

        <!-- Active Toggle -->
        <div class="flex items-center space-x-3">
          <label class="relative inline-flex items-center cursor-pointer">
            <input
              type="checkbox"
              v-model="ruleForm.is_active"
              class="sr-only peer"
            />
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
          </label>
          <span class="text-sm font-medium text-gray-900">
            {{ ruleForm.is_active ? ($t('general.active') || 'Active') : ($t('general.inactive') || 'Inactive') }}
          </span>
        </div>

        <!-- Conditions Builder -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-700">
              {{ $t('matching_rules.conditions') || 'Conditions' }}
              <span class="text-xs text-gray-500 font-normal ml-1">
                ({{ $t('matching_rules.all_must_match') || 'all must match' }})
              </span>
            </p>
            <button
              class="text-sm text-primary-600 hover:text-primary-800 font-medium"
              @click="addCondition"
            >
              + {{ $t('matching_rules.add_condition') || 'Add Condition' }}
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(condition, idx) in ruleForm.conditions"
              :key="idx"
              class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg"
            >
              <select
                v-model="condition.field"
                class="block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
              >
                <option value="" disabled>{{ $t('matching_rules.select_field') || 'Select field' }}</option>
                <option v-for="field in validFields" :key="field" :value="field">
                  {{ formatFieldName(field) }}
                </option>
              </select>

              <select
                v-model="condition.operator"
                class="block w-1/4 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
              >
                <option value="" disabled>{{ $t('matching_rules.select_operator') || 'Operator' }}</option>
                <option v-for="op in validOperators" :key="op" :value="op">
                  {{ formatOperator(op) }}
                </option>
              </select>

              <input
                v-model="condition.value"
                class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                :placeholder="$t('matching_rules.value_placeholder') || 'Value'"
                :type="isNumericField(condition.field) ? 'number' : 'text'"
                :step="isNumericField(condition.field) ? '0.01' : undefined"
              />

              <button
                v-if="ruleForm.conditions.length > 1"
                class="text-red-500 hover:text-red-700 p-1"
                @click="removeCondition(idx)"
              >
                <BaseIcon name="XMarkIcon" class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>

        <!-- Actions Builder -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <p class="text-sm font-medium text-gray-700">
              {{ $t('matching_rules.actions') || 'Actions' }}
            </p>
            <button
              class="text-sm text-primary-600 hover:text-primary-800 font-medium"
              @click="addAction"
            >
              + {{ $t('matching_rules.add_action') || 'Add Action' }}
            </button>
          </div>

          <div class="space-y-3">
            <div
              v-for="(action, idx) in ruleForm.actions"
              :key="idx"
              class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg"
            >
              <select
                v-model="action.action"
                class="block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
              >
                <option value="" disabled>{{ $t('matching_rules.select_action') || 'Select action' }}</option>
                <option v-for="act in validActions" :key="act" :value="act">
                  {{ formatActionType(act) }}
                </option>
              </select>

              <!-- Action-specific parameter inputs -->
              <input
                v-if="action.action === 'categorize'"
                v-model="action.category"
                class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                :placeholder="$t('matching_rules.category_placeholder') || 'Category tag (e.g., hosting)'"
              />

              <input
                v-if="action.action === 'match_customer'"
                v-model.number="action.customer_id"
                class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                type="number"
                :placeholder="$t('matching_rules.customer_id_placeholder') || 'Customer ID'"
              />

              <input
                v-if="action.action === 'match_expense'"
                v-model="action.expense_pattern"
                class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                :placeholder="$t('matching_rules.expense_pattern_placeholder') || 'Expense pattern (e.g., Hetzner*)'"
              />

              <input
                v-if="action.action === 'auto_match'"
                v-model.number="action.confidence_threshold"
                class="block flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                type="number"
                min="0"
                max="100"
                :placeholder="$t('matching_rules.confidence_threshold_placeholder') || 'Min confidence % (e.g., 70)'"
              />

              <span
                v-if="action.action === 'ignore'"
                class="flex-1 text-sm text-gray-500 italic"
              >
                {{ $t('matching_rules.ignore_description') || 'Transaction will be marked as ignored' }}
              </span>

              <button
                v-if="ruleForm.actions.length > 1"
                class="text-red-500 hover:text-red-700 p-1"
                @click="removeAction(idx)"
              >
                <BaseIcon name="XMarkIcon" class="h-4 w-4" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <div class="flex justify-end space-x-3">
          <BaseButton
            variant="secondary"
            @click="closeRuleModal"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            :disabled="!isFormValid || isSaving"
            @click="saveRule"
          >
            {{ isSaving ? ($t('general.saving') || 'Saving...') : (editingRule ? ($t('general.update') || 'Update') : ($t('general.save') || 'Save')) }}
          </BaseButton>
        </div>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import axios from 'axios'
import { AdjustmentsHorizontalIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()

// State
const isLoading = ref(false)
const isSaving = ref(false)
const rules = ref([])
const showRuleModal = ref(false)
const editingRule = ref(null)
const testingRuleId = ref(null)
const testResults = ref(null)
const validFields = ref([])
const validOperators = ref([])
const validActions = ref([])

const pageTitle = computed(() => t('matching_rules.title') || 'Matching Rules')

// Rule Form
const defaultRuleForm = () => ({
  name: '',
  conditions: [{ field: '', operator: '', value: '' }],
  actions: [{ action: '' }],
  priority: 0,
  is_active: true,
})

const ruleForm = ref(defaultRuleForm())

// Computed
const isFormValid = computed(() => {
  if (!ruleForm.value.name.trim()) return false

  const hasValidConditions = ruleForm.value.conditions.every(
    c => c.field && c.operator && (c.value !== '' && c.value !== null && c.value !== undefined)
  )
  if (!hasValidConditions) return false

  const hasValidActions = ruleForm.value.actions.every(a => a.action)
  if (!hasValidActions) return false

  return true
})

// Methods
const fetchRules = async () => {
  isLoading.value = true
  try {
    const response = await axios.get('/banking/matching-rules')
    rules.value = response.data.data || []

    // Store valid options from meta
    if (response.data.meta) {
      validFields.value = response.data.meta.valid_fields || []
      validOperators.value = response.data.meta.valid_operators || []
      validActions.value = response.data.meta.valid_actions || []
    }
  } catch (error) {
    console.error('Failed to fetch matching rules:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('matching_rules.failed_to_load') || 'Failed to load matching rules'
    })
  } finally {
    isLoading.value = false
  }
}

const openCreateModal = () => {
  editingRule.value = null
  ruleForm.value = defaultRuleForm()
  showRuleModal.value = true
}

const openEditModal = (rule) => {
  editingRule.value = rule
  ruleForm.value = {
    name: rule.name,
    conditions: JSON.parse(JSON.stringify(rule.conditions)),
    actions: JSON.parse(JSON.stringify(rule.actions)),
    priority: rule.priority,
    is_active: rule.is_active,
  }
  showRuleModal.value = true
}

const closeRuleModal = () => {
  showRuleModal.value = false
  editingRule.value = null
  ruleForm.value = defaultRuleForm()
}

const saveRule = async () => {
  if (!isFormValid.value) return

  isSaving.value = true
  try {
    const payload = {
      name: ruleForm.value.name,
      conditions: ruleForm.value.conditions,
      actions: ruleForm.value.actions,
      priority: ruleForm.value.priority,
      is_active: ruleForm.value.is_active,
    }

    if (editingRule.value) {
      await axios.put(`/banking/matching-rules/${editingRule.value.id}`, payload)
      notificationStore.showNotification({
        type: 'success',
        message: t('matching_rules.updated') || 'Matching rule updated successfully'
      })
    } else {
      await axios.post('/banking/matching-rules', payload)
      notificationStore.showNotification({
        type: 'success',
        message: t('matching_rules.created') || 'Matching rule created successfully'
      })
    }

    closeRuleModal()
    await fetchRules()
  } catch (error) {
    console.error('Failed to save matching rule:', error)
    const message = error.response?.data?.errors
      ? Object.values(error.response.data.errors).flat().join(', ')
      : (t('matching_rules.save_failed') || 'Failed to save matching rule')

    notificationStore.showNotification({
      type: 'error',
      message
    })
  } finally {
    isSaving.value = false
  }
}

const confirmDelete = async (rule) => {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure') || 'Are you sure?',
    message: (t('matching_rules.confirm_delete') || 'This will permanently delete the rule: ') + rule.name,
    yesLabel: t('general.yes') || 'Yes',
    noLabel: t('general.no') || 'No',
    variant: 'danger',
  })

  if (!confirmed) return

  try {
    await axios.delete(`/banking/matching-rules/${rule.id}`)

    notificationStore.showNotification({
      type: 'success',
      message: t('matching_rules.deleted') || 'Matching rule deleted successfully'
    })

    await fetchRules()
  } catch (error) {
    console.error('Failed to delete matching rule:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('matching_rules.delete_failed') || 'Failed to delete matching rule'
    })
  }
}

const testRule = async (rule) => {
  testingRuleId.value = rule.id
  testResults.value = null

  try {
    const response = await axios.post(`/banking/matching-rules/${rule.id}/test`)
    testResults.value = {
      rule_id: rule.id,
      ...response.data.data
    }
  } catch (error) {
    console.error('Failed to test matching rule:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('matching_rules.test_failed') || 'Failed to test matching rule'
    })
  } finally {
    testingRuleId.value = null
  }
}

// Condition/Action Management
const addCondition = () => {
  ruleForm.value.conditions.push({ field: '', operator: '', value: '' })
}

const removeCondition = (idx) => {
  if (ruleForm.value.conditions.length > 1) {
    ruleForm.value.conditions.splice(idx, 1)
  }
}

const addAction = () => {
  ruleForm.value.actions.push({ action: '' })
}

const removeAction = (idx) => {
  if (ruleForm.value.actions.length > 1) {
    ruleForm.value.actions.splice(idx, 1)
  }
}

// Formatting helpers
const formatFieldName = (field) => {
  const names = {
    description: t('matching_rules.field_description') || 'Description',
    remittance_info: t('matching_rules.field_remittance') || 'Remittance Info',
    debtor_name: t('matching_rules.field_debtor') || 'Debtor Name',
    creditor_name: t('matching_rules.field_creditor') || 'Creditor Name',
    amount: t('matching_rules.field_amount') || 'Amount',
    transaction_type: t('matching_rules.field_type') || 'Transaction Type',
    currency: t('matching_rules.field_currency') || 'Currency',
  }
  return names[field] || field
}

const formatOperator = (operator) => {
  const names = {
    contains: t('matching_rules.op_contains') || 'contains',
    equals: t('matching_rules.op_equals') || 'equals',
    greater_than: t('matching_rules.op_greater') || '>',
    less_than: t('matching_rules.op_less') || '<',
    starts_with: t('matching_rules.op_starts') || 'starts with',
    ends_with: t('matching_rules.op_ends') || 'ends with',
    regex: t('matching_rules.op_regex') || 'regex',
  }
  return names[operator] || operator
}

const formatActionType = (action) => {
  const names = {
    categorize: t('matching_rules.action_categorize') || 'Categorize',
    match_customer: t('matching_rules.action_match_customer') || 'Match Customer',
    match_expense: t('matching_rules.action_match_expense') || 'Match Expense',
    auto_match: t('matching_rules.action_auto_match') || 'Auto-Match Invoice',
    ignore: t('matching_rules.action_ignore') || 'Ignore Transaction',
  }
  return names[action] || action
}

const formatAction = (action) => {
  const typeName = formatActionType(action.action)
  if (action.action === 'categorize' && action.category) {
    return `${typeName}: ${action.category}`
  }
  if (action.action === 'match_customer' && action.customer_id) {
    return `${typeName}: #${action.customer_id}`
  }
  if (action.action === 'match_expense' && action.expense_pattern) {
    return `${typeName}: ${action.expense_pattern}`
  }
  if (action.action === 'auto_match' && action.confidence_threshold) {
    return `${typeName}: >${action.confidence_threshold}%`
  }
  return typeName
}

const getActionClass = (action) => {
  const classes = {
    categorize: 'bg-purple-100 text-purple-800',
    match_customer: 'bg-blue-100 text-blue-800',
    match_expense: 'bg-orange-100 text-orange-800',
    auto_match: 'bg-green-100 text-green-800',
    ignore: 'bg-gray-100 text-gray-600',
  }
  return classes[action] || 'bg-gray-100 text-gray-700'
}

const truncateValue = (value) => {
  const str = String(value)
  return str.length > 30 ? str.substring(0, 30) + '...' : str
}

const isNumericField = (field) => {
  return field === 'amount'
}

const formatMoney = (amount, currency = 'MKD') => {
  if (!amount) return '0.00'
  return new Intl.NumberFormat('mk-MK', {
    style: 'currency',
    currency: currency || 'MKD'
  }).format(amount)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  fetchRules()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
