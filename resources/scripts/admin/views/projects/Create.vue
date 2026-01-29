<template>
  <BasePage>
    <BasePageHeader :title="isEdit ? $t('projects.edit_project') : $t('projects.new_project')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('projects.title')" to="/admin/projects" />
        <BaseBreadcrumbItem
          :title="isEdit ? $t('projects.edit_project') : $t('projects.new_project')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
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

          <BaseInputGroup :label="$t('projects.customer')">
            <BaseCustomerSelectInput
              v-model="form.customer_id"
              :placeholder="$t('customers.type_or_click')"
              value-prop="id"
              label="name"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('projects.status')">
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

          <BaseInputGroup :label="$t('projects.start_date')">
            <BaseDatePicker v-model="form.start_date" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('projects.end_date')">
            <BaseDatePicker v-model="form.end_date" />
          </BaseInputGroup>
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
const { t } = useI18n()
const projectStore = useProjectStore()
const globalStore = useGlobalStore()
const companyStore = useCompanyStore()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)

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
})

const statusOptions = computed(() => [
  { value: 'open', label: t('projects.statuses.open') },
  { value: 'in_progress', label: t('projects.statuses.in_progress') },
  { value: 'completed', label: t('projects.statuses.completed') },
  { value: 'on_hold', label: t('projects.statuses.on_hold') },
  { value: 'cancelled', label: t('projects.statuses.cancelled') },
])

const isEdit = computed(() => !!route.params.id)

const selectedCurrency = computed(() => {
  if (!form.currency_id) return null
  return globalStore.currencies.find(c => c.id === form.currency_id)
})

const nameError = ref('')

function validateForm() {
  nameError.value = ''
  if (!form.name || form.name.trim() === '') {
    nameError.value = t('validation.required')
    return false
  }
  return true
}

function hydrateForm(data) {
  form.id = data.id
  form.name = data.name
  form.code = data.code
  form.description = data.description
  form.customer_id = data.customer_id
  form.status = data.status
  // Budget is stored in cents, convert to display value for BaseMoney
  form.budget_amount = data.budget_amount ? data.budget_amount / 100 : null
  form.currency_id = data.currency_id
  form.start_date = data.start_date
  form.end_date = data.end_date
  form.notes = data.notes
}

async function handleSubmit() {
  if (!validateForm()) {
    return
  }

  isLoading.value = true
  const payload = {
    ...form,
    // Convert budget from display value to cents for storage
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

onMounted(async () => {
  // Load currencies if not loaded
  if (!globalStore.currencies.length) {
    await globalStore.fetchCurrencies()
  }

  // Set default currency from company settings
  if (!isEdit.value && companyStore.selectedCompanyCurrency) {
    form.currency_id = companyStore.selectedCompanyCurrency.id
  }

  // Load project data if editing
  if (isEdit.value) {
    const response = await projectStore.fetchProject(route.params.id)
    if (response.data?.data) {
      hydrateForm(response.data.data)
    }
  }
})
</script>
<!-- CLAUDE-CHECKPOINT -->
