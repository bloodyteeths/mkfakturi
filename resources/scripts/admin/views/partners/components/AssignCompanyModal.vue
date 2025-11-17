<template>
  <BaseModal :show="show" @close="closeModal">
    <template #header>
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">
          {{ isEdit ? $t('partners.edit_company_assignment') : $t('partners.assign_company') }}
        </h3>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-500"
          @click="closeModal"
        >
          <BaseIcon name="XMarkIcon" class="w-6 h-6" />
        </button>
      </div>
    </template>

    <div class="px-6 py-4">
      <form @submit.prevent="submitAssignment">
        <!-- Company Selection (only for new assignment) -->
        <BaseInputGroup
          v-if="!isEdit"
          :label="$t('partners.select_company')"
          required
          class="mb-4"
        >
          <BaseMultiselect
            v-model="formData.company_id"
            :options="availableCompanies"
            value-prop="id"
            label="name"
            track-by="name"
            searchable
            :placeholder="$t('partners.search_company')"
            :loading="loadingCompanies"
          />
        </BaseInputGroup>

        <!-- Commission Rate Override -->
        <BaseInputGroup
          :label="$t('partners.commission_rate_override')"
          :helper-text="$t('partners.commission_rate_override_help')"
          class="mb-4"
        >
          <BaseInput
            v-model="formData.override_commission_rate"
            type="number"
            step="0.01"
            min="0"
            max="100"
            :placeholder="$t('partners.use_default_rate')"
          >
            <template #right>
              <span class="text-gray-500">%</span>
            </template>
          </BaseInput>
        </BaseInputGroup>

        <!-- Primary Company Toggle -->
        <BaseInputGroup class="mb-4">
          <BaseSwitch
            v-model="formData.is_primary"
            :label="$t('partners.set_as_primary_company')"
          />
        </BaseInputGroup>

        <!-- Permissions Section -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ $t('partners.permissions') }}
          </label>
          <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <PermissionEditor v-model="formData.permissions" />
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
          <BaseButton
            type="button"
            variant="primary-outline"
            @click="closeModal"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            type="submit"
            :loading="saving"
            :disabled="!canSubmit"
          >
            <template #left="slotProps">
              <BaseIcon name="CheckIcon" :class="slotProps.class" />
            </template>
            {{ isEdit ? $t('partners.update_assignment') : $t('partners.assign_company') }}
          </BaseButton>
        </div>
      </form>
    </div>
  </BaseModal>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import PermissionEditor from './PermissionEditor.vue'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
  partnerId: {
    type: Number,
    required: true,
  },
  company: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['close', 'saved'])

const isEdit = computed(() => !!props.company)
const loadingCompanies = ref(false)
const saving = ref(false)
const availableCompanies = ref([])

const formData = reactive({
  company_id: null,
  is_primary: false,
  override_commission_rate: null,
  permissions: [],
})

const canSubmit = computed(() => {
  if (isEdit.value) return true
  return formData.company_id && formData.permissions.length > 0
})

watch(() => props.show, (newValue) => {
  if (newValue) {
    if (isEdit.value) {
      loadEditData()
    } else {
      resetForm()
      loadAvailableCompanies()
    }
  }
})

function loadEditData() {
  if (!props.company) return

  formData.company_id = props.company.id
  formData.is_primary = props.company.pivot?.is_primary || false
  formData.override_commission_rate = props.company.pivot?.override_commission_rate || null

  // Parse permissions
  try {
    const permissions = props.company.pivot?.permissions
    formData.permissions = typeof permissions === 'string'
      ? JSON.parse(permissions)
      : (Array.isArray(permissions) ? permissions : [])
  } catch {
    formData.permissions = []
  }
}

async function loadAvailableCompanies() {
  // AC-09: Will implement full company loading
  // For now, this is a stub
  loadingCompanies.value = true
  try {
    // const response = await axios.get('/companies')
    // availableCompanies.value = response.data
    availableCompanies.value = []
  } catch (error) {
    console.error('Failed to load companies:', error)
  } finally {
    loadingCompanies.value = false
  }
}

async function submitAssignment() {
  saving.value = true

  try {
    // AC-09: Will implement actual API calls
    // For now, just show success
    notificationStore.showNotification({
      type: 'info',
      message: 'AC-09: Full implementation coming - assign/unassign endpoints',
    })

    emit('saved')
    closeModal()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partners.assignment_failed'),
    })
  } finally {
    saving.value = false
  }
}

function resetForm() {
  formData.company_id = null
  formData.is_primary = false
  formData.override_commission_rate = null
  formData.permissions = []
}

function closeModal() {
  emit('close')
}
</script>

<!-- CLAUDE-CHECKPOINT -->
