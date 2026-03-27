<template>
  <BaseSettingCard
    :title="$t('settings.accounts.title')"
    :description="$t('settings.accounts.description')"
  >
    <template #action>
      <BaseButton variant="primary-outline" @click="openCreateModal">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ $t('settings.accounts.add_account') }}
      </BaseButton>
    </template>

    <!-- Search + Account Type Filter -->
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
      <BaseInput
        v-model="searchQuery"
        :placeholder="$t('general.search') + '...'"
        class="w-full sm:w-64"
      />
      <div class="flex flex-wrap gap-2">
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
    </div>

    <!-- Account count -->
    <p class="mb-3 text-xs text-gray-400">
      {{ filteredAccounts.length }} / {{ accountStore.accounts.length }}
      {{ $t('settings.accounts.title').toLowerCase() }}
    </p>

    <!-- Accounts Tree -->
    <AccountTreeComponent
      :accounts="filteredAccounts"
      :editable="true"
      @edit="openEditModal"
      @delete="onDeleteAccount"
    />

    <!-- Account Modal -->
    <BaseModal
      :show="showModal"
      :title="isEdit ? $t('settings.accounts.edit_account') : $t('settings.accounts.add_account')"
      @close="closeModal"
    >
      <form @submit.prevent="submitAccount">
        <div class="grid gap-4">
          <!-- System-defined notice -->
          <div
            v-if="accountForm.system_defined"
            class="rounded-md bg-amber-50 p-3 text-sm text-amber-700"
          >
            {{ $t('settings.accounts.system_defined_notice') }}
          </div>

          <div class="grid grid-cols-2 gap-4">
            <BaseInputGroup :label="$t('settings.accounts.code')" required>
              <BaseInput
                v-model="accountForm.code"
                :placeholder="$t('settings.accounts.code_placeholder')"
                :disabled="accountForm.system_defined"
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
                :disabled="accountForm.system_defined"
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
              :disabled="accountForm.system_defined"
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
          <BaseButton variant="gray" type="button" @click="closeModal">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" type="submit" :loading="isSubmitting">
            {{ isEdit ? $t('general.update') : $t('general.save') }}
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </BaseSettingCard>
</template>

<script setup>
import { useAccountStore } from '@/scripts/admin/stores/account'
import { useDialogStore } from '@/scripts/stores/dialog'
import { computed, ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import AccountTreeComponent from '@/scripts/admin/components/accounting/AccountTreeComponent.vue'

const { t } = useI18n()
const accountStore = useAccountStore()
const dialogStore = useDialogStore()

const showModal = ref(false)
const isSubmitting = ref(false)
const selectedType = ref(null)
const searchQuery = ref('')

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
  let accounts = accountStore.accounts
  if (selectedType.value) {
    accounts = accounts.filter((a) => a.type === selectedType.value)
  }
  if (searchQuery.value) {
    const q = searchQuery.value.toLowerCase()
    accounts = accounts.filter(
      (a) => a.code.includes(q) || a.name.toLowerCase().includes(q)
    )
  }
  return accounts
})

const parentOptions = computed(() => {
  return accountStore.accounts
    .filter((a) => a.id !== accountForm.id)
    .map((a) => ({
      ...a,
      display_name: `${a.code} - ${a.name}`,
    }))
})

onMounted(() => {
  accountStore.fetchAccounts()
})

function resetForm() {
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
  resetForm()
  showModal.value = true
}

function openEditModal(account) {
  accountForm.id = account.id
  accountForm.code = account.code
  accountForm.name = account.name
  accountForm.type = account.type
  accountForm.parent_id = account.parent_id
  accountForm.description = account.description || ''
  accountForm.is_active = account.is_active
  accountForm.system_defined = account.system_defined
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  resetForm()
}

async function submitAccount() {
  isSubmitting.value = true

  try {
    const data = {
      code: accountForm.code,
      name: accountForm.name,
      type: accountForm.type,
      parent_id: accountForm.parent_id,
      description: accountForm.description,
      is_active: accountForm.is_active,
    }

    if (isEdit.value) {
      await accountStore.updateAccount(accountForm.id, data)
    } else {
      await accountStore.createAccount(data)
    }

    closeModal()
    // Refresh accounts to reflect changes in tree
    accountStore.fetchAccounts()
  } finally {
    isSubmitting.value = false
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
    .then(async (res) => {
      if (res) {
        await accountStore.deleteAccount(account.id)
        accountStore.fetchAccounts()
      }
    })
}
</script>
