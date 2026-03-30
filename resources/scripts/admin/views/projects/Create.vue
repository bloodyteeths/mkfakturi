<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('projects.title')" to="/admin/projects" />
        <BaseBreadcrumbItem
          :title="pageTitle"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <!-- Type Selector (only on create, not edit) -->
        <div v-if="!isEdit" class="flex gap-4 mb-6">
          <div
            class="flex-1 cursor-pointer p-4 rounded-lg border-2 transition-all text-center"
            :class="form.type === 'project'
              ? 'border-primary-500 bg-primary-50'
              : 'border-gray-200 hover:border-gray-300'"
            @click="form.type = 'project'"
          >
            <BaseIcon name="FolderIcon" class="h-6 w-6 mx-auto mb-1 text-gray-600" />
            <span class="text-sm font-medium">{{ $t('projects.type_project') }}</span>
          </div>
          <div
            class="flex-1 cursor-pointer p-4 rounded-lg border-2 transition-all text-center"
            :class="form.type === 'branch'
              ? 'border-primary-500 bg-primary-50'
              : 'border-gray-200 hover:border-gray-300'"
            @click="form.type = 'branch'"
          >
            <BaseIcon name="BuildingOfficeIcon" class="h-6 w-6 mx-auto mb-1 text-gray-600" />
            <span class="text-sm font-medium">{{ $t('projects.type_branch') }}</span>
          </div>
        </div>

        <!-- Common Fields -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInputGroup
            :label="$t('projects.name')"
            :error="nameError"
            required
          >
            <BaseInput
              v-model="form.name"
              :invalid="!!nameError"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('projects.code')">
            <BaseInput v-model="form.code" :placeholder="$t('projects.code_placeholder')" />
          </BaseInputGroup>

          <!-- Project-only: Customer -->
          <BaseInputGroup v-if="form.type !== 'branch'" :label="$t('projects.customer')">
            <BaseCustomerSelectInput
              v-model="form.customer_id"
              :placeholder="$t('customers.type_or_click')"
              value-prop="id"
              label="name"
            />
          </BaseInputGroup>

          <!-- Project-only: Status -->
          <BaseInputGroup v-if="form.type !== 'branch'" :label="$t('projects.status')">
            <BaseMultiselect
              v-model="form.status"
              :options="statusOptions"
              label="label"
              value-prop="value"
              :placeholder="$t('projects.select_status')"
              :can-deselect="false"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('projects.budget')">
            <BaseMoney
              v-model="form.budget_amount"
              :currency="selectedCurrency"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('projects.currency')">
            <BaseMultiselect
              v-model="form.currency_id"
              value-prop="id"
              label="name"
              track-by="name"
              :content-loading="!globalStore.currencies.length"
              :options="globalStore.currencies"
              searchable
              :can-deselect="false"
              :placeholder="$t('customers.select_currency')"
              class="w-full"
            />
          </BaseInputGroup>

          <!-- Project-only: Dates -->
          <BaseInputGroup v-if="form.type !== 'branch'" :label="$t('projects.start_date')">
            <BaseDatePicker v-model="form.start_date" />
          </BaseInputGroup>

          <BaseInputGroup v-if="form.type !== 'branch'" :label="$t('projects.end_date')">
            <BaseDatePicker v-model="form.end_date" />
          </BaseInputGroup>
        </div>

        <!-- Branch-specific Required Fields -->
        <div v-if="form.type === 'branch'" class="mt-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('projects.branch_info') }}</h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInputGroup
              :label="$t('projects.address')"
              :error="addressError"
              required
            >
              <BaseTextarea
                v-model="form.address"
                rows="2"
                :invalid="!!addressError"
              />
            </BaseInputGroup>

            <div class="grid grid-cols-1 gap-4">
              <BaseInputGroup
                :label="$t('projects.city')"
                :error="cityError"
                required
              >
                <BaseInput
                  v-model="form.city"
                  :invalid="!!cityError"
                />
              </BaseInputGroup>

              <BaseInputGroup :label="$t('projects.municipality')">
                <BaseInput v-model="form.municipality" />
              </BaseInputGroup>
            </div>
          </div>

          <!-- Collapsible optional fields -->
          <button
            type="button"
            class="mt-4 flex items-center text-sm text-gray-500 hover:text-gray-700 transition-colors"
            @click="showAdvanced = !showAdvanced"
          >
            <BaseIcon
              :name="showAdvanced ? 'ChevronUpIcon' : 'ChevronDownIcon'"
              class="h-4 w-4 mr-1"
            />
            {{ $t('projects.optional_details') }}
            <span class="ml-1 text-xs text-gray-400">({{ $t('projects.optional_details_hint') }})</span>
          </button>

          <div v-show="showAdvanced" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInputGroup :label="$t('projects.registration_number')">
              <BaseInput
                v-model="form.registration_number"
                placeholder="ЕМБС"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('projects.manager')">
              <BaseMultiselect
                v-model="form.manager_id"
                value-prop="id"
                label="name"
                track-by="name"
                :options="userOptions"
                searchable
                :placeholder="$t('projects.manager')"
                class="w-full"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('projects.phone')">
              <BaseInput v-model="form.phone" type="tel" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('projects.email_field')">
              <BaseInput v-model="form.email" type="email" />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('projects.parent_branch')">
              <BaseProjectSelectInput
                v-model="form.parent_id"
                type="branch"
                :show-action="false"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="$t('projects.is_active')">
              <div class="mt-2">
                <BaseCheckbox
                  v-model="form.is_active"
                  :label="$t('projects.is_active')"
                  variant="primary"
                />
              </div>
            </BaseInputGroup>
          </div>
        </div>

        <BaseInputGroup :label="$t('projects.description')" class="mt-4">
          <BaseTextarea v-model="form.description" rows="3" />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('projects.notes')" class="mt-4">
          <BaseTextarea v-model="form.notes" rows="3" />
        </BaseInputGroup>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="$router.push('/admin/projects')">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" type="submit" :loading="isLoading">
            {{ isEdit ? $t('general.update') : $t('general.create') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useProjectStore } from '@/scripts/admin/stores/project'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useUserStore } from '@/scripts/admin/stores/user'
import BaseProjectSelectInput from '@/scripts/components/base/BaseProjectSelectInput.vue'

const { t } = useI18n()
const projectStore = useProjectStore()
const globalStore = useGlobalStore()
const companyStore = useCompanyStore()
const userStore = useUserStore()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const showAdvanced = ref(false)
const userOptions = ref([])

const form = reactive({
  id: null,
  name: '',
  code: '',
  description: '',
  customer_id: null,
  status: 'open',
  budget_amount: null,
  currency_id: null,
  start_date: null,
  end_date: null,
  notes: '',
  type: route.query.type || 'project',
  address: '',
  city: '',
  municipality: '',
  registration_number: '',
  manager_id: null,
  phone: '',
  email: '',
  parent_id: null,
  is_active: true,
})

const statusOptions = computed(() => [
  { value: 'open', label: t('projects.statuses.open') },
  { value: 'in_progress', label: t('projects.statuses.in_progress') },
  { value: 'completed', label: t('projects.statuses.completed') },
  { value: 'on_hold', label: t('projects.statuses.on_hold') },
  { value: 'cancelled', label: t('projects.statuses.cancelled') },
])

const isEdit = computed(() => !!route.params.id)

const pageTitle = computed(() => {
  if (isEdit.value) {
    return form.type === 'branch' ? t('projects.edit_branch') : t('projects.edit_project')
  }
  return form.type === 'branch' ? t('projects.new_branch') : t('projects.new_project')
})

const selectedCurrency = computed(() => {
  if (!form.currency_id) return null
  return globalStore.currencies.find(c => c.id === form.currency_id)
})

const nameError = ref('')
const addressError = ref('')
const cityError = ref('')

function validateForm() {
  nameError.value = ''
  addressError.value = ''
  cityError.value = ''

  let isValid = true

  if (!form.name || form.name.trim() === '') {
    nameError.value = t('validation.required')
    isValid = false
  }

  if (form.type === 'branch') {
    if (!form.address || form.address.trim() === '') {
      addressError.value = t('validation.required')
      isValid = false
    }
    if (!form.city || form.city.trim() === '') {
      cityError.value = t('validation.required')
      isValid = false
    }
  }

  return isValid
}

function hydrateForm(data) {
  form.id = data.id
  form.name = data.name
  form.code = data.code
  form.description = data.description
  form.customer_id = data.customer_id
  form.status = data.status
  form.budget_amount = data.budget_amount ? data.budget_amount / 100 : null
  form.currency_id = data.currency_id
  form.start_date = data.start_date
  form.end_date = data.end_date
  form.notes = data.notes
  form.type = data.type || 'project'
  form.address = data.address || ''
  form.city = data.city || ''
  form.municipality = data.municipality || ''
  form.registration_number = data.registration_number || ''
  form.manager_id = data.manager_id
  form.phone = data.phone || ''
  form.email = data.email || ''
  form.parent_id = data.parent_id
  form.is_active = data.is_active !== undefined ? data.is_active : true

  // Auto-expand advanced section if any optional branch field is filled
  if (data.type === 'branch' && (data.registration_number || data.manager_id || data.phone || data.email || data.parent_id)) {
    showAdvanced.value = true
  }
}

async function handleSubmit() {
  if (!validateForm()) {
    return
  }

  isLoading.value = true
  const payload = {
    ...form,
    budget_amount: form.budget_amount ? Math.round(form.budget_amount * 100) : null,
  }

  try {
    if (isEdit.value) {
      await projectStore.updateProject(payload)
    } else {
      await projectStore.addProject(payload)
    }
    router.push('/admin/projects')
  } catch (error) {
    console.error('Error saving project:', error)
  } finally {
    isLoading.value = false
  }
}

async function loadUsers() {
  try {
    const response = await window.axios.get('/users', { params: { limit: 'all' } })
    userOptions.value = (response.data?.data || []).map(u => ({
      id: u.id,
      name: u.name,
    }))
  } catch (e) {
    // Fallback: empty list
    userOptions.value = []
  }
}

onMounted(async () => {
  if (!globalStore.currencies.length) {
    await globalStore.fetchCurrencies()
  }

  if (!isEdit.value && companyStore.selectedCompanyCurrency) {
    form.currency_id = companyStore.selectedCompanyCurrency.id
  }

  if (isEdit.value) {
    const response = await projectStore.fetchProject(route.params.id)
    if (response.data?.data) {
      hydrateForm(response.data.data)
    }
  }

  // Load users for manager dropdown
  await loadUsers()
})
</script>
<!-- CLAUDE-CHECKPOINT -->
