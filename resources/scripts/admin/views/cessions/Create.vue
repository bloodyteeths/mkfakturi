<template>
  <BasePage>
    <BasePageHeader :title="$t('cession_new')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('cessions_title')" to="/admin/cessions" />
        <BaseBreadcrumbItem :title="$t('cession_new')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <form class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6" @submit.prevent="submit">
      <BaseInputGroup :label="$t('cedent')">
        <BaseInput v-model="form.cedent_name" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('cessionary')">
        <BaseInput v-model="form.cessionary_name" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('cession_debtor')">
        <BaseInput v-model="form.debtor_name" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('transferred_amount')">
        <BaseInput v-model="form.amount" type="number" step="0.01" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('original_document')">
        <BaseInput v-model="form.original_document" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payments.date')">
        <BaseInput v-model="form.cession_date" type="date" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.description')" class="md:col-span-2">
        <BaseInput v-model="form.description" />
      </BaseInputGroup>

      <div class="md:col-span-2 flex justify-end gap-3">
        <BaseButton variant="gray" @click="$router.push('/admin/cessions')">
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
  cedent_name: '',
  cessionary_name: '',
  debtor_name: '',
  amount: '',
  original_document: '',
  cession_date: new Date().toISOString().split('T')[0],
  description: '',
})

async function submit() {
  saving.value = true
  try {
    const payload = { ...form, amount: Math.round(parseFloat(form.amount) * 100) }
    const res = await window.axios.post('/cessions', payload)
    router.push(`/admin/cessions/${res.data.id}/view`)
  } catch (e) {
    console.error(e)
  } finally {
    saving.value = false
  }
}
</script>
// CLAUDE-CHECKPOINT
