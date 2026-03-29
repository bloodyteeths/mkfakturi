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
      <BaseInputGroup :label="$t('assignor')" :help-text="$t('assignor_help') !== 'assignor_help' ? $t('assignor_help') : 'Лице кое упатува плаќање'">
        <div class="relative">
          <BaseInput
            v-model="form.assignor_name"
            placeholder="Внесете или изберете..."
            required
            list="assignor-list"
          />
          <datalist id="assignor-list">
            <option v-for="c in contacts" :key="'asr-' + c.id" :value="c.name" />
          </datalist>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('assignee')" :help-text="$t('assignee_help') !== 'assignee_help' ? $t('assignee_help') : 'Лице кое го прима плаќањето'">
        <div class="relative">
          <BaseInput
            v-model="form.assignee_name"
            placeholder="Внесете или изберете..."
            required
            list="assignee-list"
          />
          <datalist id="assignee-list">
            <option v-for="c in contacts" :key="'ase-' + c.id" :value="c.name" />
          </datalist>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('assigned_debtor')" :help-text="$t('assigned_debtor_help') !== 'assigned_debtor_help' ? $t('assigned_debtor_help') : 'Должник кој треба да плати'">
        <div class="relative">
          <BaseInput
            v-model="form.debtor_name"
            placeholder="Внесете или изберете..."
            required
            list="debtor-list"
          />
          <datalist id="debtor-list">
            <option v-for="c in contacts" :key="'deb-' + c.id" :value="c.name" />
          </datalist>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('assignation_amount')">
        <div class="relative">
          <BaseInput v-model="form.amount" type="number" step="0.01" min="0" required class="pr-12" />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">ден</span>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('original_document')">
        <BaseInput v-model="form.original_document" placeholder="бр. на фактура / договор" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payments.date')">
        <BaseInput v-model="form.assignation_date" type="date" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.description')" class="md:col-span-2">
        <BaseInput v-model="form.description" placeholder="Опис на асигнацијата..." />
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
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const saving = ref(false)
const contacts = ref([])

const form = reactive({
  assignor_name: '',
  assignee_name: '',
  debtor_name: '',
  amount: '',
  original_document: '',
  assignation_date: new Date().toISOString().split('T')[0],
  description: '',
})

onMounted(async () => {
  try {
    const [custRes, suppRes] = await Promise.all([
      window.axios.get('/customers', { params: { limit: 100 } }).catch(() => ({ data: { data: [] } })),
      window.axios.get('/suppliers', { params: { limit: 100 } }).catch(() => ({ data: { data: [] } })),
    ])
    const custs = (custRes.data?.data || []).map(c => ({ id: 'c-' + c.id, name: c.name }))
    const supps = (suppRes.data?.data || []).map(s => ({ id: 's-' + s.id, name: s.name }))
    contacts.value = [...custs, ...supps]
  } catch (e) {
    // Non-critical
  }
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
