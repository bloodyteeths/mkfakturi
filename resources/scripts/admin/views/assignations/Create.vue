<template>
  <BasePage>
    <BasePageHeader :title="$t('assignation_new')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('assignations_title')" to="/admin/assignations" />
        <BaseBreadcrumbItem :title="$t('assignation_new')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6" @submit.prevent="submit">
      <BaseInputGroup :label="$t('assignor')">
        <BaseInput v-model="form.assignor_name" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('assignee')">
        <BaseInput v-model="form.assignee_name" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('assigned_debtor')">
        <BaseInput v-model="form.debtor_name" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('assignation_amount')">
        <BaseInput v-model="form.amount" type="number" step="0.01" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('original_document')">
        <BaseInput v-model="form.original_document" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payments.date')">
        <BaseInput v-model="form.assignation_date" type="date" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.description')" class="md:col-span-2">
        <BaseInput v-model="form.description" />
      </BaseInputGroup>

      <div class="md:col-span-2 flex justify-end gap-3">
        <BaseButton variant="gray" @click="$router.push('/admin/assignations')">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton type="submit" variant="primary" :loading="saving">
          {{ $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const saving = ref(false)

const form = reactive({
  assignor_name: '',
  assignee_name: '',
  debtor_name: '',
  amount: '',
  original_document: '',
  assignation_date: new Date().toISOString().split('T')[0],
  description: '',
})

async function submit() {
  saving.value = true
  try {
    const payload = { ...form, amount: Math.round(parseFloat(form.amount) * 100) }
    const res = await window.axios.post('/assignations', payload)
    router.push(`/admin/assignations/${res.data.id}/view`)
  } catch (e) {
    console.error(e)
  } finally {
    saving.value = false
  }
}
</script>
// CLAUDE-CHECKPOINT
