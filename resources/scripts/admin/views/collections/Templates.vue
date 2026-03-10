<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ t('templates') }}</h3>
      <BaseButton variant="primary" size="sm" @click="openForm(null)">
        {{ $t('general.add') }}
      </BaseButton>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-6 space-y-4">
        <div v-for="i in 3" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
      </div>
    </div>

    <!-- Template Cards -->
    <template v-else>
      <div v-if="templates.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
        <p class="text-sm text-gray-500">{{ $t('general.no_data') }}</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div
          v-for="tpl in templates"
          :key="tpl.id"
          class="bg-white rounded-lg shadow p-4 border-l-4"
          :class="levelBorderClass(tpl.escalation_level)"
        >
          <div class="flex items-center justify-between mb-2">
            <span :class="levelBadgeClass(tpl.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
              {{ t(tpl.escalation_level) }}
            </span>
            <div class="flex items-center gap-2">
              <span v-if="tpl.is_active" class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                {{ t('active') }}
              </span>
            </div>
          </div>
          <p class="text-sm font-medium text-gray-900 mb-1">{{ humanize(tpl.subject_mk || tpl.subject_en) }}</p>
          <p class="text-xs text-gray-500 mb-2">{{ t('days_after_due') }}: {{ tpl.days_after_due }}</p>
          <div class="text-xs text-gray-600 mb-3 border rounded p-2 bg-gray-50 max-h-20 overflow-hidden" v-html="humanize(tpl.body_mk || tpl.body_en)"></div>
          <div class="flex justify-end gap-2">
            <BaseButton size="sm" variant="primary-outline" @click="openForm(tpl)">
              {{ t('edit_template') }}
            </BaseButton>
            <BaseButton size="sm" variant="danger-outline" @click="deleteTemplate(tpl.id)">
              {{ t('delete_template') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </template>

    <!-- Edit/Create Form Modal -->
    <BaseModal :show="showForm" @close="showForm = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ editingTemplate ? t('edit_template') : t('save_template') }}</h3>
      </template>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <BaseInputGroup :label="t('escalation')">
            <BaseMultiselect
              v-model="form.escalation_level"
              :options="levelOptions"
              label="label"
              value-prop="value"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('days_after_due')">
            <BaseInput v-model="form.days_after_due" type="number" min="1" />
          </BaseInputGroup>
        </div>

        <!-- Placeholder Legend -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <p class="text-xs font-medium text-blue-800 mb-2">{{ t('placeholder_legend_title') }}</p>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1">
            <div v-for="ph in placeholders" :key="ph.token" class="flex items-center gap-2 text-xs">
              <code class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-mono text-[10px]">{{ ph.token }}</code>
              <span class="text-gray-600">{{ ph.label }}</span>
            </div>
          </div>
        </div>

        <BaseInputGroup :label="t('template_subject') + ' (MK)'">
          <BaseInput v-model="form.subject_mk" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('template_body') + ' (MK)'">
          <BaseTextarea v-model="form.body_mk" rows="4" />
        </BaseInputGroup>

        <!-- Live Preview -->
        <div v-if="form.subject_mk || form.body_mk" class="border rounded-lg overflow-hidden">
          <div class="bg-gray-100 px-3 py-2 border-b">
            <p class="text-xs font-medium text-gray-500 uppercase">{{ t('preview') }}</p>
          </div>
          <div class="p-3 bg-white">
            <p class="text-sm font-medium text-gray-900 mb-2">{{ humanize(form.subject_mk) }}</p>
            <div class="text-sm text-gray-700 prose prose-sm max-w-none" v-html="humanize(form.body_mk)"></div>
          </div>
        </div>

        <BaseInputGroup :label="t('template_subject') + ' (EN)'">
          <BaseInput v-model="form.subject_en" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('template_body') + ' (EN)'">
          <BaseTextarea v-model="form.body_en" rows="4" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('subject_tr')">
          <BaseInput v-model="form.subject_tr" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('body_tr')">
          <BaseTextarea v-model="form.body_tr" rows="4" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('subject_sq')">
          <BaseInput v-model="form.subject_sq" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('body_sq')">
          <BaseTextarea v-model="form.body_sq" rows="4" />
        </BaseInputGroup>

        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300" />
          <span class="text-sm text-gray-700">{{ t('active') }}</span>
        </label>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="showForm = false">{{ $t('general.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="isSaving" @click="saveTemplate">{{ t('save_template') }}</BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import collectionMessages from '@/scripts/admin/i18n/collections.js'

const { locale } = useI18n()
const notificationStore = useNotificationStore()

function t(key) {
  const loc = locale.value || 'mk'
  return collectionMessages[loc]?.collections?.[key]
    || collectionMessages['en']?.collections?.[key]
    || key
}

const templates = ref([])
const isLoading = ref(false)
const isSaving = ref(false)
const showForm = ref(false)
const editingTemplate = ref(null)

const levelOptions = [
  { value: 'friendly', label: t('level_friendly') || 'Friendly' },
  { value: 'firm', label: t('level_firm') || 'Firm' },
  { value: 'final', label: t('level_final') || 'Final' },
  { value: 'legal', label: t('level_legal') || 'Legal' },
]

const form = reactive({
  escalation_level: 'friendly',
  days_after_due: 7,
  subject_mk: '', subject_en: '', subject_tr: '', subject_sq: '',
  body_mk: '', body_en: '', body_tr: '', body_sq: '',
  is_active: true,
})

// Sample data for preview — replaces {PLACEHOLDERS} with realistic values
const sampleData = {
  '{INVOICE_NUMBER}': 'ФАК-2026-0042',
  '{AMOUNT_DUE}': '24,500.00 ден.',
  '{DUE_DATE}': '15.02.2026',
  '{DAYS_OVERDUE}': '23',
  '{CUSTOMER_NAME}': 'ДООЕЛ Пример',
  '{COMPANY_NAME}': 'Мојата Фирма ДООЕЛ',
  '{TOTAL}': '28,900.00 ден.',
}

// Placeholder legend labels
const placeholders = [
  { token: '{INVOICE_NUMBER}', label: t('ph_invoice_number') },
  { token: '{AMOUNT_DUE}', label: t('ph_amount_due') },
  { token: '{DUE_DATE}', label: t('ph_due_date') },
  { token: '{DAYS_OVERDUE}', label: t('ph_days_overdue') },
  { token: '{CUSTOMER_NAME}', label: t('ph_customer_name') },
  { token: '{COMPANY_NAME}', label: t('ph_company_name') },
  { token: '{TOTAL}', label: t('ph_total') },
]

function humanize(text) {
  if (!text) return ''
  let result = text
  for (const [token, value] of Object.entries(sampleData)) {
    result = result.replaceAll(token, value)
  }
  return result
}

function levelBorderClass(level) {
  const map = { friendly: 'border-blue-400', firm: 'border-yellow-400', final: 'border-orange-400', legal: 'border-red-400' }
  return map[level] || 'border-gray-400'
}

function levelBadgeClass(level) {
  const map = {
    friendly: 'bg-blue-100 text-blue-800',
    firm: 'bg-yellow-100 text-yellow-800',
    final: 'bg-orange-100 text-orange-800',
    legal: 'bg-red-100 text-red-800',
  }
  return map[level] || 'bg-gray-100 text-gray-800'
}

async function loadTemplates() {
  isLoading.value = true
  try {
    const { data } = await window.axios.get('/collections/templates')
    templates.value = data.data || []
  } catch (e) {
    console.error('Failed to load templates', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load templates',
    })
  } finally {
    isLoading.value = false
  }
}

function openForm(tpl) {
  editingTemplate.value = tpl
  if (tpl) {
    Object.assign(form, {
      escalation_level: tpl.escalation_level,
      days_after_due: tpl.days_after_due,
      subject_mk: tpl.subject_mk || '', subject_en: tpl.subject_en || '',
      subject_tr: tpl.subject_tr || '', subject_sq: tpl.subject_sq || '',
      body_mk: tpl.body_mk || '', body_en: tpl.body_en || '',
      body_tr: tpl.body_tr || '', body_sq: tpl.body_sq || '',
      is_active: tpl.is_active,
    })
  } else {
    Object.assign(form, {
      escalation_level: 'friendly', days_after_due: 7,
      subject_mk: '', subject_en: '', subject_tr: '', subject_sq: '',
      body_mk: '', body_en: '', body_tr: '', body_sq: '',
      is_active: true,
    })
  }
  showForm.value = true
}

async function saveTemplate() {
  isSaving.value = true
  try {
    if (editingTemplate.value) {
      await window.axios.put(`/collections/templates/${editingTemplate.value.id}`, form)
    } else {
      await window.axios.post('/collections/templates', form)
    }
    showForm.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('template_saved') || 'Template saved.',
    })
    loadTemplates()
  } catch (e) {
    console.error('Failed to save template', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_saving') || 'Failed to save template',
    })
  } finally {
    isSaving.value = false
  }
}

async function deleteTemplate(id) {
  if (!confirm(t('delete_template') + '?')) return
  try {
    await window.axios.delete(`/collections/templates/${id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('template_deleted') || 'Template deleted.',
    })
    loadTemplates()
  } catch (e) {
    console.error('Failed to delete template', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_deleting') || 'Failed to delete template',
    })
  }
}

onMounted(() => {
  loadTemplates()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
