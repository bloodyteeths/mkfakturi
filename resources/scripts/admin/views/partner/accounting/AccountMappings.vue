<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.account_mappings')">
      <template #actions>
        <BaseButton
          variant="primary"
          :disabled="!canApplyAllSuggestions"
          @click="applyAllSuggestions"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="SparklesIcon" />
          </template>
          {{ $t('partner.accounting.apply_all_suggestions') }}
        </BaseButton>
      </template>
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

    <!-- Tabs for entity types -->
    <BaseTabGroup v-if="selectedCompanyId">
      <template #default>
        <BaseTab
          :title="$t('partner.accounting.customers')"
          :active="activeTab === 'customer'"
          @click="activeTab = 'customer'"
        />
        <BaseTab
          :title="$t('partner.accounting.suppliers')"
          :active="activeTab === 'supplier'"
          @click="activeTab = 'supplier'"
        />
        <BaseTab
          :title="$t('partner.accounting.categories')"
          :active="activeTab === 'category'"
          @click="activeTab = 'category'"
        />
      </template>
    </BaseTabGroup>

    <!-- Loading state -->
    <div v-if="partnerAccountingStore.isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Mappings Table -->
    <div v-else-if="selectedCompanyId" class="mt-6">
      <BaseTable
        ref="tableRef"
        :data="fetchMappingsData"
        :columns="mappingColumns"
        :show-filter="false"
        :loading-type="'placeholder'"
      >
        <template #cell-entity_name="{ row }">
          <div class="flex items-center">
            <span class="font-medium text-gray-900">
              {{ row.data.entity_name }}
            </span>
          </div>
        </template>

        <template #cell-current_mapping="{ row }">
          <div v-if="row.data.account" class="flex items-center space-x-2">
            <span class="font-mono text-sm text-gray-700">
              {{ row.data.account.code }}
            </span>
            <span class="text-sm text-gray-600">
              {{ row.data.account.name }}
            </span>
          </div>
          <BaseBadge v-else bg-color="bg-yellow-100" text-color="text-yellow-800">
            {{ $t('partner.accounting.not_mapped') }}
          </BaseBadge>
        </template>

        <template #cell-suggestion="{ row }">
          <div v-if="row.data.suggestion" class="flex items-center space-x-2">
            <BaseIcon name="SparklesIcon" class="h-4 w-4 text-primary-500" />
            <span class="font-mono text-sm text-gray-700">
              {{ row.data.suggestion.code }}
            </span>
            <span class="text-sm text-gray-600">
              {{ row.data.suggestion.name }}
            </span>
            <BaseBadge
              v-if="row.data.suggestion.confidence"
              :bg-color="getConfidenceBadgeColor(row.data.suggestion.confidence)"
              :text-color="getConfidenceTextColor(row.data.suggestion.confidence)"
            >
              {{ Math.round(row.data.suggestion.confidence * 100) }}%
            </BaseBadge>
          </div>
          <span v-else class="text-sm text-gray-400">
            {{ $t('partner.accounting.no_suggestion') }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end space-x-2">
            <BaseButton
              v-if="row.data.suggestion"
              variant="primary-outline"
              size="sm"
              @click="applySuggestion(row.data)"
            >
              {{ $t('partner.accounting.apply') }}
            </BaseButton>
            <BaseButton
              variant="gray"
              size="sm"
              @click="openMappingModal(row.data)"
            >
              {{ $t('general.edit') }}
            </BaseButton>
            <BaseButton
              v-if="row.data.account"
              variant="gray"
              size="sm"
              @click="removeMapping(row.data)"
            >
              <BaseIcon name="TrashIcon" class="h-4 w-4" />
            </BaseButton>
          </div>
        </template>
      </BaseTable>
    </div>

    <!-- Select company message -->
    <div
      v-else
      class="mt-6 flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <!-- Mapping Modal -->
    <BaseModal
      :show="showMappingModal"
      :title="$t('partner.accounting.edit_mapping')"
      @close="closeMappingModal"
    >
      <form @submit.prevent="submitMapping">
        <div class="grid gap-4">
          <!-- Entity Info -->
          <div class="rounded-md bg-gray-50 p-4">
            <div class="text-sm">
              <span class="font-medium text-gray-700">
                {{ $t('partner.accounting.entity') }}:
              </span>
              <span class="ml-2 text-gray-900">
                {{ mappingForm.entity_name }}
              </span>
            </div>
          </div>

          <!-- Account Selection -->
          <BaseInputGroup :label="$t('partner.accounting.select_account')" required>
            <BaseMultiselect
              v-model="mappingForm.account_id"
              :options="accountOptions"
              :searchable="true"
              track-by="id"
              label="display_name"
              value-prop="id"
              :placeholder="$t('partner.accounting.select_account_placeholder')"
            />
          </BaseInputGroup>

          <!-- Suggestion Info (if available) -->
          <div
            v-if="mappingForm.suggestion"
            class="rounded-md bg-blue-50 p-4"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <BaseIcon name="SparklesIcon" class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3 flex-1">
                <p class="text-sm font-medium text-blue-700">
                  {{ $t('partner.accounting.ai_suggestion') }}
                </p>
                <p class="mt-1 text-sm text-blue-600">
                  {{ mappingForm.suggestion.code }} - {{ mappingForm.suggestion.name }}
                </p>
                <p
                  v-if="mappingForm.suggestion.confidence"
                  class="mt-1 text-xs text-blue-500"
                >
                  {{ $t('partner.accounting.confidence') }}:
                  {{ Math.round(mappingForm.suggestion.confidence * 100) }}%
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <BaseButton variant="gray" type="button" @click="closeMappingModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            type="submit"
            :loading="partnerAccountingStore.isSaving"
          >
            {{ $t('general.save') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { usePartnerAccountingStore } from '@/scripts/admin/stores/partner-accounting'
import { useDialogStore } from '@/scripts/stores/dialog'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const activeTab = ref('customer')
const showMappingModal = ref(false)
const tableRef = ref(null)

const mappingForm = reactive({
  id: null,
  entity_id: null,
  entity_type: null,
  entity_name: '',
  account_id: null,
  suggestion: null,
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const mappingColumns = computed(() => [
  {
    key: 'entity_name',
    label: t('partner.accounting.entity_name'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'current_mapping',
    label: t('partner.accounting.current_mapping'),
    thClass: 'w-64',
    tdClass: 'text-gray-700',
  },
  {
    key: 'suggestion',
    label: t('partner.accounting.ai_suggestion'),
    thClass: 'w-64',
    tdClass: 'text-gray-700',
  },
  {
    key: 'actions',
    label: '',
    thClass: 'w-48',
    tdClass: 'text-right',
    sortable: false,
  },
])

const accountOptions = computed(() => {
  return partnerAccountingStore.activeAccounts.map((a) => ({
    ...a,
    display_name: `${a.code} - ${a.name}`,
  }))
})

const canApplyAllSuggestions = computed(() => {
  return (
    partnerAccountingStore.mappings.length > 0 &&
    partnerAccountingStore.mappings.some((m) => m.suggestion)
  )
})

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()

  // Auto-select first company if available
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await loadData()
  }
})

// Watch for company and tab changes
watch([selectedCompanyId, activeTab], async () => {
  if (selectedCompanyId.value) {
    await loadData()
  }
})

// Methods
async function loadData() {
  if (!selectedCompanyId.value) return

  try {
    // Load accounts first (needed for dropdowns)
    await partnerAccountingStore.fetchAccounts(selectedCompanyId.value)

    // Load mappings for current tab
    await partnerAccountingStore.fetchMappings(
      selectedCompanyId.value,
      activeTab.value
    )

    // Refresh table if it exists
    if (tableRef.value) {
      tableRef.value.refresh()
    }
  } catch (error) {
    console.error('Failed to load data:', error)
  }
}

async function fetchMappingsData({ page, filter, sort }) {
  // Return mappings from store
  const mappings = partnerAccountingStore.mappingsByType(activeTab.value)

  return {
    data: mappings,
    pagination: {
      totalPages: 1,
      currentPage: 1,
    },
  }
}

function onCompanyChange() {
  // Reset state when company changes
  partnerAccountingStore.mappings = []
  if (tableRef.value) {
    tableRef.value.refresh()
  }
}

function resetMappingForm() {
  mappingForm.id = null
  mappingForm.entity_id = null
  mappingForm.entity_type = null
  mappingForm.entity_name = ''
  mappingForm.account_id = null
  mappingForm.suggestion = null
}

function openMappingModal(mapping) {
  mappingForm.id = mapping.id
  mappingForm.entity_id = mapping.entity_id
  mappingForm.entity_type = mapping.entity_type
  mappingForm.entity_name = mapping.entity_name
  mappingForm.account_id = mapping.account?.id || null
  mappingForm.suggestion = mapping.suggestion || null
  showMappingModal.value = true
}

function closeMappingModal() {
  showMappingModal.value = false
  resetMappingForm()
}

async function submitMapping() {
  const data = {
    entity_id: mappingForm.entity_id,
    entity_type: mappingForm.entity_type,
    account_id: mappingForm.account_id,
  }

  try {
    if (mappingForm.id) {
      await partnerAccountingStore.updateMapping(
        selectedCompanyId.value,
        mappingForm.id,
        data
      )
    } else {
      await partnerAccountingStore.createMapping(selectedCompanyId.value, data)
    }

    closeMappingModal()
    await loadData()
  } catch (error) {
    console.error('Failed to save mapping:', error)
  }
}

async function applySuggestion(mapping) {
  if (!mapping.suggestion) return

  const data = {
    entity_id: mapping.entity_id,
    entity_type: mapping.entity_type,
    account_id: mapping.suggestion.id,
  }

  try {
    if (mapping.id) {
      await partnerAccountingStore.updateMapping(
        selectedCompanyId.value,
        mapping.id,
        data
      )
    } else {
      await partnerAccountingStore.createMapping(selectedCompanyId.value, data)
    }

    await loadData()
  } catch (error) {
    console.error('Failed to apply suggestion:', error)
  }
}

async function applyAllSuggestions() {
  const mappingsWithSuggestions = partnerAccountingStore.mappingsByType(
    activeTab.value
  ).filter((m) => m.suggestion)

  if (mappingsWithSuggestions.length === 0) return

  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('partner.accounting.apply_all_suggestions_confirm', {
        count: mappingsWithSuggestions.length,
      }),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (confirmed) => {
      if (confirmed) {
        try {
          // Apply each suggestion
          for (const mapping of mappingsWithSuggestions) {
            await applySuggestion(mapping)
          }
        } catch (error) {
          console.error('Failed to apply all suggestions:', error)
        }
      }
    })
}

function removeMapping(mapping) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('partner.accounting.remove_mapping_confirm', {
        name: mapping.entity_name,
      }),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (confirmed) => {
      if (confirmed) {
        try {
          await partnerAccountingStore.deleteMapping(
            selectedCompanyId.value,
            mapping.id
          )
          await loadData()
        } catch (error) {
          console.error('Failed to remove mapping:', error)
        }
      }
    })
}

function getConfidenceBadgeColor(confidence) {
  if (confidence >= 0.8) return 'bg-green-100'
  if (confidence >= 0.6) return 'bg-yellow-100'
  return 'bg-orange-100'
}

function getConfidenceTextColor(confidence) {
  if (confidence >= 0.8) return 'text-green-800'
  if (confidence >= 0.6) return 'text-yellow-800'
  return 'text-orange-800'
}
</script>

// CLAUDE-CHECKPOINT
