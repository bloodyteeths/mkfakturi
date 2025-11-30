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

    <!-- Accounts Table -->
    <BaseTable
      ref="table"
      class="mt-4"
      :show-filter="false"
      :data="fetchData"
      :columns="accountColumns"
    >
      <template #cell-code="{ row }">
        <span class="font-mono font-medium text-gray-900">
          {{ row.data.code }}
        </span>
      </template>

      <template #cell-name="{ row }">
        <div class="flex items-center">
          <span
            v-if="row.data.parent_id"
            class="mr-2 text-gray-400"
            :style="{ paddingLeft: `${getIndent(row.data)}px` }"
          >
            └─
          </span>
          {{ row.data.name }}
        </div>
      </template>

      <template #cell-type="{ row }">
        <BaseBadge
          :bg-color="getTypeBadgeColor(row.data.type)"
          :text-color="getTypeTextColor(row.data.type)"
        >
          {{ $t(`settings.accounts.type_${row.data.type}`) }}
        </BaseBadge>
      </template>

      <template #cell-is_active="{ row }">
        <BaseBadge
          :bg-color="row.data.is_active ? 'bg-green-100' : 'bg-gray-100'"
          :text-color="row.data.is_active ? 'text-green-800' : 'text-gray-800'"
        >
          {{ row.data.is_active ? $t('general.active') : $t('general.inactive') }}
        </BaseBadge>
      </template>

      <template #cell-actions="{ row }">
        <BaseDropdown>
          <template #activator>
            <div class="inline-block cursor-pointer">
              <BaseIcon name="EllipsisHorizontalIcon" class="text-gray-500" />
            </div>
          </template>

          <BaseDropdownItem @click="openEditModal(row.data)">
            <BaseIcon name="PencilIcon" class="mr-3 text-gray-600" />
            {{ $t('general.edit') }}
          </BaseDropdownItem>

          <BaseDropdownItem
            v-if="!row.data.system_defined"
            @click="onDeleteAccount(row.data)"
          >
            <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </template>
    </BaseTable>

    <!-- Account Modal -->
    <BaseModal
      :show="showModal"
      :title="isEdit ? $t('settings.accounts.edit_account') : $t('settings.accounts.add_account')"
      @close="closeModal"
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

const { t } = useI18n()
const accountStore = useAccountStore()
const dialogStore = useDialogStore()

const table = ref(null)
const showModal = ref(false)
const isSubmitting = ref(false)
const selectedType = ref(null)

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

const parentOptions = computed(() => {
  // Filter accounts that can be parents (exclude current account if editing)
  return accountStore.accounts
    .filter((a) => a.id !== accountForm.id)
    .map((a) => ({
      ...a,
      display_name: `${a.code} - ${a.name}`,
    }))
})

const accountColumns = computed(() => [
  {
    key: 'code',
    label: t('settings.accounts.code'),
    thClass: 'w-24',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'name',
    label: t('settings.accounts.name'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'type',
    label: t('settings.accounts.type'),
    thClass: 'w-32',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'is_active',
    label: t('settings.accounts.status'),
    thClass: 'w-24',
    tdClass: 'font-medium text-gray-900',
  },
  {
    key: 'actions',
    label: '',
    tdClass: 'text-right text-sm font-medium',
    sortable: false,
  },
])

onMounted(() => {
  accountStore.fetchAccounts()
})

function getTypeBadgeColor(type) {
  const colors = {
    asset: 'bg-blue-100',
    liability: 'bg-red-100',
    equity: 'bg-purple-100',
    revenue: 'bg-green-100',
    expense: 'bg-orange-100',
  }
  return colors[type] || 'bg-gray-100'
}

function getTypeTextColor(type) {
  const colors = {
    asset: 'text-blue-800',
    liability: 'text-red-800',
    equity: 'text-purple-800',
    revenue: 'text-green-800',
    expense: 'text-orange-800',
  }
  return colors[type] || 'text-gray-800'
}

function getIndent(account) {
  // Calculate indent based on parent hierarchy
  let indent = 0
  let parent = accountStore.accounts.find((a) => a.id === account.parent_id)
  while (parent) {
    indent += 20
    parent = accountStore.accounts.find((a) => a.id === parent.parent_id)
  }
  return indent
}

async function fetchData({ page, filter, sort }) {
  const params = {
    orderByField: sort.fieldName || 'code',
    orderBy: sort.order || 'asc',
    page,
  }

  if (selectedType.value) {
    params.type = selectedType.value
  }

  const response = await accountStore.fetchAccounts(params)

  return {
    data: response.data.data || [],
    pagination: {
      totalPages: 1,
      currentPage: 1,
    },
  }
}

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
    table.value && table.value.refresh()
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
        table.value && table.value.refresh()
      }
    })
}
</script>
// CLAUDE-CHECKPOINT
