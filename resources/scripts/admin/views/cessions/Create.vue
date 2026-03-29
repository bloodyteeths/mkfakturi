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
      <BaseInputGroup :label="$t('cedent')" :help-text="$t('cedent_help') !== 'cedent_help' ? $t('cedent_help') : 'Старт доверител кој го пренесува побарувањето'">
        <div class="relative">
          <BaseInput
            v-model="form.cedent_name"
            :placeholder="$t('cedent_placeholder') !== 'cedent_placeholder' ? $t('cedent_placeholder') : 'Внесете или изберете...'"
            required
            list="cedent-list"
          />
          <datalist id="cedent-list">
            <option v-for="c in contacts" :key="'ced-' + c.id" :value="c.name" />
          </datalist>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('cessionary')" :help-text="$t('cessionary_help') !== 'cessionary_help' ? $t('cessionary_help') : 'Нов доверител кој го прима побарувањето'">
        <div class="relative">
          <BaseInput
            v-model="form.cessionary_name"
            :placeholder="$t('cessionary_placeholder') !== 'cessionary_placeholder' ? $t('cessionary_placeholder') : 'Внесете или изберете...'"
            required
            list="cessionary-list"
          />
          <datalist id="cessionary-list">
            <option v-for="c in contacts" :key="'ces-' + c.id" :value="c.name" />
          </datalist>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('cession_debtor')" :help-text="$t('debtor_help') !== 'debtor_help' ? $t('debtor_help') : 'Должник кон кого се пренесува побарувањето'">
        <div class="relative">
          <BaseInput
            v-model="form.debtor_name"
            :placeholder="$t('debtor_placeholder') !== 'debtor_placeholder' ? $t('debtor_placeholder') : 'Внесете или изберете...'"
            required
            list="debtor-list"
          />
          <datalist id="debtor-list">
            <option v-for="c in contacts" :key="'deb-' + c.id" :value="c.name" />
          </datalist>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('transferred_amount')">
        <div class="relative">
          <BaseInput v-model="form.amount" type="number" step="0.01" min="0" required class="pr-12" />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">ден</span>
        </div>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('original_document')">
        <BaseInput v-model="form.original_document" :placeholder="$t('original_document_placeholder') !== 'original_document_placeholder' ? $t('original_document_placeholder') : 'бр. на фактура / договор'" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payments.date')">
        <BaseInput v-model="form.cession_date" type="date" required />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.description')" class="md:col-span-2">
        <BaseInput v-model="form.description" :placeholder="$t('cession_description_placeholder') !== 'cession_description_placeholder' ? $t('cession_description_placeholder') : 'Опис на цесијата...'" />
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
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const saving = ref(false)
const contacts = ref([])

const form = reactive({
  cedent_name: '',
  cessionary_name: '',
  debtor_name: '',
  amount: '',
  original_document: '',
  cession_date: new Date().toISOString().split('T')[0],
  description: '',
})

onMounted(async () => {
  try {
    // Load customers + suppliers for autocomplete
    const [custRes, suppRes] = await Promise.all([
      window.axios.get('/customers', { params: { limit: 100 } }).catch(() => ({ data: { data: [] } })),
      window.axios.get('/suppliers', { params: { limit: 100 } }).catch(() => ({ data: { data: [] } })),
    ])
    const custs = (custRes.data?.data || []).map(c => ({ id: 'c-' + c.id, name: c.name }))
    const supps = (suppRes.data?.data || []).map(s => ({ id: 's-' + s.id, name: s.name }))
    contacts.value = [...custs, ...supps]
  } catch (e) {
    // Non-critical — user can still type manually
  }
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
