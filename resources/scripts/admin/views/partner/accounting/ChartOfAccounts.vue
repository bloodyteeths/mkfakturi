<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.chart_of_accounts')">
      <template #actions>
        <div class="flex items-center space-x-3">
          <!-- Export CSV button -->
          <BaseButton
            variant="gray"
            @click="exportAccounts"
            :loading="partnerAccountingStore.isExporting"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            {{ $t('partner.accounting.export_csv') }}
          </BaseButton>

          <!-- Import CSV button -->
          <BaseButton
            variant="gray"
            @click="openImportModal"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowUpTrayIcon" />
            </template>
            {{ $t('partner.accounting.import_csv') }}
          </BaseButton>

          <!-- Add Account button -->
          <BaseButton
            variant="primary"
            @click="openCreateModal"
          >
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="PlusIcon" />
            </template>
            {{ $t('partner.accounting.add_account') }}
          </BaseButton>
        </div>
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

    <!-- Account Type Filter -->
    <div class="mb-6 flex flex-wrap gap-2">
      <BaseButton
        v-for="type in accountTypes"
        :key="type.value"
        :variant="selectedType === type.value ? 'primary' : 'gray'"
        size="sm"
        @click="selectedType = type.value"
      >
        {{ type.label }}
      </BaseButton>
    </div>

    <!-- Loading state -->
    <div v-if="partnerAccountingStore.isLoading" class="flex justify-center py-12">
      <BaseSpinner />
    </div>

    <!-- Account Tree View -->
    <div v-else-if="selectedCompanyId && filteredAccounts.length > 0">
      <AccountTreeComponent
        :accounts="filteredAccounts"
        :selected-id="selectedAccountId"
        :editable="true"
        @select="onSelectAccount"
        @edit="openEditModal"
        @delete="onDeleteAccount"
      />
    </div>

    <!-- Empty state -->
    <BaseEmptyPlaceholder
      v-else-if="selectedCompanyId"
      :title="$t('partner.accounting.no_accounts')"
      :description="$t('partner.accounting.no_accounts_description')"
    >
      <template #icon>
        <BaseIcon name="BanknotesIcon" class="h-12 w-12 text-gray-400" />
      </template>
      <template #cta>
        <BaseButton variant="primary-outline" @click="openCreateModal">
          {{ $t('partner.accounting.add_first_account') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Select company message -->
    <div
      v-else
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <!-- Account Modal -->
    <BaseModal
      :show="showAccountModal"
      :title="isEdit ? $t('partner.accounting.edit_account') : $t('partner.accounting.add_account')"
      @close="closeAccountModal"
    >
      <form @submit.prevent="submitAccount">
        <div class="grid gap-4">
          <div class="grid grid-cols-2 gap-4">
            <BaseInputGroup :label="$t('settings.accounts.code')" required>
              <BaseInput
                v-model="accountForm.code"
                :placeholder="$t('settings.accounts.code_placeholder')"
                :disabled="isEdit && accountForm.system_defined"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('settings.accounts.type')" required>
              <BaseMultiselect
                v-model="accountForm.type"
                :options="accountTypeOptions"
                :searchable="false"
                track-by="value"
                label="label"
                value-prop="value"
                :disabled="isEdit && accountForm.system_defined"
              />
            </BaseInputGroup>
          </div>

          <BaseInputGroup :label="$t('settings.accounts.name')" required>
            <BaseInput
              v-model="accountForm.name"
              :placeholder="$t('settings.accounts.name_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('settings.accounts.parent_account')">
            <BaseMultiselect
              v-model="accountForm.parent_id"
              :options="parentOptions"
              :searchable="true"
              track-by="id"
              label="display_name"
              value-prop="id"
              :placeholder="$t('settings.accounts.no_parent')"
              :disabled="isEdit && accountForm.system_defined"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('settings.accounts.description')">
            <BaseTextarea
              v-model="accountForm.description"
              :placeholder="$t('settings.accounts.description_placeholder')"
              rows="3"
            />
          </BaseInputGroup>

          <BaseSwitchSection
            v-model="accountForm.is_active"
            :title="$t('settings.accounts.is_active')"
            :description="$t('settings.accounts.is_active_description')"
          />
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <BaseButton variant="gray" type="button" @click="closeAccountModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            type="submit"
            :loading="partnerAccountingStore.isSaving"
          >
            {{ isEdit ? $t('general.update') : $t('general.save') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>

    <!-- Import CSV Modal -->
    <BaseModal
      :show="showImportModal"
      :title="$t('partner.accounting.import_accounts')"
      @close="closeImportModal"
    >
      <form @submit.prevent="submitImport">
        <div class="grid gap-4">
          <BaseInputGroup
            :label="$t('partner.accounting.csv_file')"
            :helper-text="$t('partner.accounting.csv_file_help')"
            required
          >
            <BaseFileUploader
              v-model="importFile"
              :placeholder="$t('partner.accounting.select_csv_file')"
              accept=".csv"
            />
          </BaseInputGroup>

          <div class="rounded-md bg-blue-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <BaseIcon name="InformationCircleIcon" class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3 flex-1 text-sm text-blue-700">
                <p class="font-medium">{{ $t('partner.accounting.csv_format_title') }}</p>
                <p class="mt-2">{{ $t('partner.accounting.csv_format_description') }}</p>
                <p class="mt-1 font-mono text-xs">
                  code,name,type,parent_code,description,is_active
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
          <BaseButton variant="gray" type="button" @click="closeImportModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            variant="primary"
            type="submit"
            :loading="partnerAccountingStore.isLoading"
            :disabled="!importFile"
          >
            {{ $t('partner.accounting.import') }}
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
import AccountTreeComponent from '@/scripts/admin/components/accounting/AccountTreeComponent.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const partnerAccountingStore = usePartnerAccountingStore()
const dialogStore = useDialogStore()

// State
const selectedCompanyId = ref(null)
const selectedAccountId = ref(null)
const selectedType = ref(null)
const showAccountModal = ref(false)
const showImportModal = ref(false)
const importFile = ref(null)

const accountForm = reactive({
  id: null,
  code: '',
  name: '',
  type: 'asset',
  parent_id: null,
  description: '',
  is_active: true,
  system_defined: false,
})

// Computed
const companies = computed(() => {
  return consoleStore.managedCompanies || []
})

const isEdit = computed(() => accountForm.id !== null)

const accountTypes = computed(() => [
  { value: null, label: t('general.all') },
  { value: 'asset', label: t('settings.accounts.type_asset') },
  { value: 'liability', label: t('settings.accounts.type_liability') },
  { value: 'equity', label: t('settings.accounts.type_equity') },
  { value: 'revenue', label: t('settings.accounts.type_revenue') },
  { value: 'expense', label: t('settings.accounts.type_expense') },
])

const accountTypeOptions = computed(() =>
  accountTypes.value.filter((t) => t.value !== null)
)

const filteredAccounts = computed(() => {
  if (!selectedType.value) {
    return partnerAccountingStore.accounts
  }
  return partnerAccountingStore.accounts.filter(
    (a) => a.type === selectedType.value
  )
})

const parentOptions = computed(() => {
  return partnerAccountingStore.accounts
    .filter((a) => a.id !== accountForm.id)
    .map((a) => ({
      ...a,
      display_name: `${a.code} - ${a.name}`,
    }))
})

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()

  // Auto-select first company if available
  if (companies.value.length > 0) {
    selectedCompanyId.value = companies.value[0].id
    await loadAccounts()
  }
})

// Watch for company changes
watch(selectedCompanyId, async (newCompanyId) => {
  if (newCompanyId) {
    await loadAccounts()
  }
})

// Methods
async function loadAccounts() {
  if (!selectedCompanyId.value) return

  try {
    await partnerAccountingStore.fetchAccounts(selectedCompanyId.value)
  } catch (error) {
    console.error('Failed to load accounts:', error)
  }
}

function onCompanyChange(companyId) {
  selectedAccountId.value = null
  selectedType.value = null
}

function onSelectAccount(account) {
  selectedAccountId.value = account.id
}

function resetAccountForm() {
  accountForm.id = null
  accountForm.code = ''
  accountForm.name = ''
  accountForm.type = 'asset'
  accountForm.parent_id = null
  accountForm.description = ''
  accountForm.is_active = true
  accountForm.system_defined = false
}

function openCreateModal() {
  if (!selectedCompanyId.value) {
    dialogStore.openDialog({
      title: t('general.error'),
      message: t('partner.accounting.select_company_first'),
      variant: 'danger',
      hideNoButton: true,
    })
    return
  }

  resetAccountForm()
  showAccountModal.value = true
}

function openEditModal(account) {
  console.log('[ChartOfAccounts] Opening edit modal for account:', account)
  accountForm.id = account.id
  accountForm.code = account.code
  accountForm.name = account.name
  accountForm.type = account.type
  accountForm.parent_id = account.parent_id
  accountForm.description = account.description || ''
  accountForm.is_active = account.is_active
  accountForm.system_defined = account.system_defined
  console.log('[ChartOfAccounts] Form populated with:', { ...accountForm })
  showAccountModal.value = true
}

function closeAccountModal() {
  showAccountModal.value = false
  resetAccountForm()
}

async function submitAccount() {
  const data = {
    code: accountForm.code,
    name: accountForm.name,
    type: accountForm.type,
    parent_id: accountForm.parent_id,
    description: accountForm.description,
    is_active: accountForm.is_active,
  }

  try {
    if (isEdit.value) {
      console.log('[ChartOfAccounts] Updating account:', accountForm.id, data)
      await partnerAccountingStore.updateAccount(
        selectedCompanyId.value,
        accountForm.id,
        data
      )
      console.log('[ChartOfAccounts] Account updated successfully')
    } else {
      console.log('[ChartOfAccounts] Creating account:', data)
      await partnerAccountingStore.createAccount(selectedCompanyId.value, data)
      console.log('[ChartOfAccounts] Account created successfully')
    }

    closeAccountModal()
    await loadAccounts()
  } catch (error) {
    console.error('[ChartOfAccounts] Failed to save account:', error)
    console.error('[ChartOfAccounts] Error response:', error.response?.data)
  }
}

function onDeleteAccount(account) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('settings.accounts.delete_confirm', { name: account.name }),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
      size: 'lg',
    })
    .then(async (confirmed) => {
      if (confirmed) {
        try {
          await partnerAccountingStore.deleteAccount(
            selectedCompanyId.value,
            account.id
          )
          await loadAccounts()
        } catch (error) {
          console.error('Failed to delete account:', error)
        }
      }
    })
}

function openImportModal() {
  if (!selectedCompanyId.value) {
    dialogStore.openDialog({
      title: t('general.error'),
      message: t('partner.accounting.select_company_first'),
      variant: 'danger',
      hideNoButton: true,
    })
    return
  }

  importFile.value = null
  showImportModal.value = true
}

function closeImportModal() {
  showImportModal.value = false
  importFile.value = null
}

async function submitImport() {
  if (!importFile.value) return

  try {
    await partnerAccountingStore.importAccounts(
      selectedCompanyId.value,
      importFile.value
    )
    closeImportModal()
    await loadAccounts()
  } catch (error) {
    console.error('Failed to import accounts:', error)
  }
}

async function exportAccounts() {
  if (!selectedCompanyId.value) {
    dialogStore.openDialog({
      title: t('general.error'),
      message: t('partner.accounting.select_company_first'),
      variant: 'danger',
      hideNoButton: true,
    })
    return
  }

  try {
    await partnerAccountingStore.exportAccounts(selectedCompanyId.value)
  } catch (error) {
    console.error('Failed to export accounts:', error)
  }
}
</script>

// CLAUDE-CHECKPOINT
