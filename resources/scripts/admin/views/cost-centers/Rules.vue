<template>
  <BasePage>
    <BasePageHeader :title="t('rules')">
      <template #actions>
        <BaseButton variant="primary" @click="openCreateRule">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlusIcon" />
          </template>
          {{ t('add_rule') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Sub-navigation tabs -->
    <div class="flex border-b border-gray-200 mb-6">
      <router-link
        v-for="tab in tabs"
        :key="tab.name"
        :to="tab.to"
        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px"
        :class="currentRouteName === tab.name
          ? 'border-primary-500 text-primary-600'
          : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
      >
        {{ tab.label }}
      </router-link>
    </div>

    <!-- Explanation -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex">
        <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-500 flex-shrink-0 mt-0.5" />
        <p class="ml-3 text-sm text-blue-700">
          {{ t('rules_explanation') }}
        </p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 4" :key="i" class="flex items-center space-x-4">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-32"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Rules table -->
    <div v-else-if="rules.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('match_type') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('match_value') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              {{ t('title') }}
            </th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
              {{ t('priority') }}
            </th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">
              {{ $t('general.status') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              {{ $t('general.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="rule in rules" :key="rule.id" class="hover:bg-gray-50">
            <td class="whitespace-nowrap px-4 py-3 text-sm">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                {{ matchTypeLabel(rule.match_type) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">
              {{ resolveMatchValue(rule) }}
            </td>
            <td class="px-4 py-3 text-sm">
              <div v-if="rule.cost_center" class="flex items-center">
                <span
                  class="inline-block h-3 w-3 rounded-full mr-2 flex-shrink-0"
                  :style="{ backgroundColor: rule.cost_center.color || '#6366f1' }"
                ></span>
                <span class="text-gray-900">{{ rule.cost_center.name }}</span>
                <span v-if="rule.cost_center.code" class="ml-1 text-gray-400 font-mono text-xs">
                  ({{ rule.cost_center.code }})
                </span>
              </div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-center text-gray-600">
              {{ rule.priority }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-center">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
              >
                {{ rule.is_active ? $t('general.active') : $t('general.inactive') }}
              </span>
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
              <button
                class="text-primary-600 hover:text-primary-800 mr-2"
                @click="openEditRule(rule)"
              >
                <BaseIcon name="PencilIcon" class="h-4 w-4" />
              </button>
              <button
                class="text-red-600 hover:text-red-800"
                @click="confirmDeleteRule(rule)"
              >
                <BaseIcon name="TrashIcon" class="h-4 w-4" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty state -->
    <div
      v-else
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white py-16"
    >
      <BaseIcon name="FunnelIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-sm font-medium text-gray-900">{{ t('no_rules') }}</h3>
      <p class="mt-1 text-sm text-gray-500 text-center max-w-sm">
        {{ t('rules_explanation') }}
      </p>
      <BaseButton variant="primary" class="mt-4" @click="openCreateRule">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ t('add_rule') }}
      </BaseButton>
    </div>

    <!-- Rule Form Modal -->
    <div v-if="showRuleForm" class="fixed inset-0 z-50 overflow-hidden">
      <div class="absolute inset-0 bg-black bg-opacity-25" @click="closeRuleForm"></div>
      <div class="absolute inset-y-0 right-0 max-w-md w-full">
        <div class="h-full bg-white shadow-xl flex flex-col">
          <!-- Header -->
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50">
            <h3 class="text-lg font-medium text-gray-900">
              {{ editingRule ? $t('general.edit') : $t('general.create') }}
              {{ t('rules') }}
            </h3>
            <button class="text-gray-400 hover:text-gray-600" @click="closeRuleForm">
              <BaseIcon name="XMarkIcon" class="h-5 w-5" />
            </button>
          </div>

          <!-- Form -->
          <div class="flex-1 overflow-y-auto p-6 space-y-5">
            <!-- Match Type -->
            <BaseInputGroup :label="t('match_type')" required>
              <BaseMultiselect
                v-model="ruleForm.match_type"
                :options="matchTypeOptions"
                label="label"
                value-prop="value"
                :searchable="false"
                :can-clear="false"
              />
            </BaseInputGroup>

            <!-- Match Value — dynamic based on match_type -->
            <BaseInputGroup :label="t('match_value')" required>
              <!-- Vendor: supplier dropdown -->
              <BaseMultiselect
                v-if="ruleForm.match_type === 'vendor'"
                v-model="ruleForm.match_value"
                :options="suppliers"
                label="name"
                value-prop="id"
                :searchable="true"
                :placeholder="t('vendor_placeholder')"
                :can-clear="true"
              />

              <!-- Account: account dropdown (code - name) -->
              <BaseMultiselect
                v-else-if="ruleForm.match_type === 'account'"
                v-model="ruleForm.match_value"
                :options="accountOptions"
                label="display"
                value-prop="code"
                :searchable="true"
                :placeholder="t('account_placeholder')"
                :can-clear="true"
              />

              <!-- Item: item dropdown -->
              <BaseMultiselect
                v-else-if="ruleForm.match_type === 'item'"
                v-model="ruleForm.match_value"
                :options="items"
                label="name"
                value-prop="id"
                :searchable="true"
                :placeholder="t('item_placeholder')"
                :can-clear="true"
              />

              <!-- Description: free text -->
              <BaseInput
                v-else
                v-model="ruleForm.match_value"
                :placeholder="t('description_placeholder') || 'keyword'"
              />

              <p class="mt-1 text-xs text-gray-500">{{ matchValueHelp }}</p>
            </BaseInputGroup>

            <!-- Cost Center -->
            <BaseInputGroup :label="t('title')" required>
              <BaseMultiselect
                v-model="ruleForm.cost_center_id"
                :options="costCenters"
                :searchable="true"
                label="name"
                value-prop="id"
                :placeholder="t('select')"
              />
            </BaseInputGroup>

            <!-- Priority -->
            <BaseInputGroup :label="t('priority')">
              <BaseInput
                v-model.number="ruleForm.priority"
                type="number"
                min="0"
                placeholder="0"
              />
              <p class="mt-1 text-xs text-gray-500">{{ t('priority_help') }}</p>
            </BaseInputGroup>

            <!-- Active -->
            <div class="flex items-center justify-between">
              <label class="text-sm font-medium text-gray-700">{{ $t('general.active') }}</label>
              <button
                type="button"
                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                :class="ruleForm.is_active ? 'bg-primary-600' : 'bg-gray-200'"
                @click="ruleForm.is_active = !ruleForm.is_active"
              >
                <span
                  class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200"
                  :class="ruleForm.is_active ? 'translate-x-5' : 'translate-x-0'"
                ></span>
              </button>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3 bg-gray-50">
            <BaseButton variant="primary-outline" @click="closeRuleForm">
              {{ $t('general.cancel') }}
            </BaseButton>
            <BaseButton
              variant="primary"
              :loading="isSavingRule"
              @click="saveRule"
            >
              {{ editingRule ? $t('general.update') : $t('general.save') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </div>

  </BasePage>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useI18n } from 'vue-i18n'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useAccountStore } from '@/scripts/admin/stores/account'
import { useItemStore } from '@/scripts/admin/stores/item'
import ccMessages from '@/scripts/admin/i18n/cost-centers.js'

const route = useRoute()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const { t: $t } = useI18n()
const suppliersStore = useSuppliersStore()
const accountStore = useAccountStore()
const itemStore = useItemStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return ccMessages[locale]?.cost_centers?.[key]
    || ccMessages['en']?.cost_centers?.[key]
    || key
}

const currentRouteName = computed(() => route.name)

const tabs = computed(() => [
  { name: 'cost-centers.index', to: '/admin/cost-centers', label: t('title') },
  { name: 'cost-centers.rules', to: '/admin/cost-centers/rules', label: t('rules') },
  { name: 'cost-centers.summary', to: '/admin/cost-centers/summary', label: t('summary') },
])

// State
const rules = ref([])
const costCenters = ref([])
const isLoading = ref(false)
const showRuleForm = ref(false)
const editingRule = ref(null)
const isSavingRule = ref(false)
const deletingRuleId = ref(null)

const matchTypeOptions = [
  { value: 'vendor', label: t('match_vendor') },
  { value: 'account', label: t('match_account') },
  { value: 'description', label: t('match_description') },
  { value: 'item', label: t('match_item') },
]

const ruleForm = ref({
  match_type: 'vendor',
  match_value: '',
  cost_center_id: null,
  priority: 0,
  is_active: true,
})

// Data for dropdowns
const suppliers = computed(() => suppliersStore.suppliers || [])
const items = computed(() => itemStore.items || [])
const accountOptions = computed(() =>
  (accountStore.accounts || []).map(a => ({
    ...a,
    display: `${a.code} - ${a.name}`,
  }))
)

// Computed
const matchValueHelp = computed(() => {
  switch (ruleForm.value.match_type) {
    case 'vendor': return t('vendor_help')
    case 'account': return t('account_help')
    case 'description': return t('description_help')
    case 'item': return t('item_help')
    default: return ''
  }
})

// Clear match_value when match_type changes
watch(() => ruleForm.value.match_type, () => {
  ruleForm.value.match_value = ''
})

// Lifecycle
onMounted(() => {
  loadRules()
  loadCostCenters()
  // Load data for dropdowns
  suppliersStore.fetchSuppliers({ limit: 'all' }).catch(() => {})
  accountStore.fetchAccounts().catch(() => {})
  itemStore.fetchItems({ limit: 'all' }).catch(() => {})
})

// Methods
async function loadRules() {
  isLoading.value = true
  try {
    const response = await window.axios.get('/cost-centers/rules')
    rules.value = response.data?.data || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading') || 'Failed to load data',
    })
  } finally {
    isLoading.value = false
  }
}

async function loadCostCenters() {
  try {
    const response = await window.axios.get('/cost-centers')
    costCenters.value = (response.data?.data || []).filter(cc => cc.is_active)
  } catch (error) {
    // silent fail, rules page still works
  }
}

function matchTypeLabel(type) {
  const opt = matchTypeOptions.find(o => o.value === type)
  return opt?.label || type
}

function resolveMatchValue(rule) {
  const val = rule.match_value
  switch (rule.match_type) {
    case 'vendor': {
      const s = suppliers.value.find(s => String(s.id) === String(val))
      return s ? s.name : val
    }
    case 'account': {
      const a = (accountStore.accounts || []).find(a => a.code === val)
      return a ? `${a.code} - ${a.name}` : val
    }
    case 'item': {
      const i = items.value.find(i => String(i.id) === String(val))
      return i ? i.name : val
    }
    default:
      return val
  }
}

function openCreateRule() {
  editingRule.value = null
  ruleForm.value = {
    match_type: 'vendor',
    match_value: '',
    cost_center_id: null,
    priority: 0,
    is_active: true,
  }
  showRuleForm.value = true
}

function openEditRule(rule) {
  editingRule.value = rule
  // For vendor/item, match_value is stored as string ID — convert to number for multiselect
  let matchVal = rule.match_value
  if (rule.match_type === 'vendor' || rule.match_type === 'item') {
    matchVal = Number(matchVal) || matchVal
  }
  ruleForm.value = {
    match_type: rule.match_type,
    match_value: matchVal,
    cost_center_id: rule.cost_center_id,
    priority: rule.priority,
    is_active: rule.is_active,
  }
  showRuleForm.value = true
}

function closeRuleForm() {
  showRuleForm.value = false
  editingRule.value = null
}

async function saveRule() {
  const val = ruleForm.value.match_value
  if ((!val && val !== 0) || !ruleForm.value.cost_center_id) return

  // Convert IDs to string for vendor/item (backend stores as varchar)
  const payload = { ...ruleForm.value }
  if (payload.match_type === 'vendor' || payload.match_type === 'item') {
    payload.match_value = String(payload.match_value)
  }

  isSavingRule.value = true
  try {
    if (editingRule.value?.id) {
      await window.axios.put(`/cost-centers/rules/${editingRule.value.id}`, payload)
      notificationStore.showNotification({
        type: 'success',
        message: t('updated_success') || 'Updated successfully',
      })
    } else {
      await window.axios.post('/cost-centers/rules', payload)
      notificationStore.showNotification({
        type: 'success',
        message: t('created_success') || 'Created successfully',
      })
    }
    closeRuleForm()
    await loadRules()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_saving') || 'Failed to save',
    })
  } finally {
    isSavingRule.value = false
  }
}

function confirmDeleteRule(rule) {
  deletingRuleId.value = rule.id
  dialogStore
    .openDialog({
      title: $t('general.are_you_sure'),
      message: $t('general.delete_confirm') || `Delete this rule?`,
      yesLabel: $t('general.ok'),
      noLabel: $t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (res) => {
      if (res) {
        try {
          await window.axios.delete(`/cost-centers/rules/${deletingRuleId.value}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('deleted_success') || 'Deleted successfully',
          })
          deletingRuleId.value = null
          await loadRules()
        } catch (error) {
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.error || t('error_deleting') || 'Failed to delete',
          })
        }
      }
    })
}
</script>

<!-- CLAUDE-CHECKPOINT -->
