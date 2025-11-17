<template>
  <BaseModal :show="show" @close="$emit('close')" size="lg">
    <template #header>
      <h3 class="text-lg font-medium text-gray-900">{{ $t('partners.reassign_entity') }}</h3>
    </template>

    <div class="space-y-6">
      <!-- Reassignment Type -->
      <BaseInputGroup :label="$t('partners.reassignment_type')" required>
        <select
          v-model="formData.type"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
          @change="resetForm"
        >
          <option value="company_partner">{{ $t('partners.reassign_company_partner') }}</option>
          <option value="partner_upline">{{ $t('partners.reassign_partner_upline') }}</option>
        </select>
      </BaseInputGroup>

      <!-- Company→Partner Reassignment -->
      <div v-if="formData.type === 'company_partner'" class="space-y-4">
        <BaseInputGroup :label="$t('general.company')" required>
          <select
            v-model="formData.company_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            @change="loadCurrentPartner"
          >
            <option value="">{{ $t('general.select_company') }}</option>
            <option v-for="company in companies" :key="company.id" :value="company.id">
              {{ company.name }}
            </option>
          </select>
        </BaseInputGroup>

        <div v-if="currentPartner" class="p-3 bg-gray-50 rounded border border-gray-200">
          <div class="text-xs text-gray-500 mb-1">{{ $t('partners.current_partner') }}</div>
          <div class="text-sm font-medium text-gray-900">{{ currentPartner.name }}</div>
        </div>

        <BaseInputGroup :label="$t('partners.new_partner')" required>
          <select
            v-model="formData.new_partner_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">{{ $t('partners.select_new_partner') }}</option>
            <option v-for="partner in availablePartners" :key="partner.id" :value="partner.id">
              {{ partner.name }} ({{ partner.email }})
            </option>
          </select>
        </BaseInputGroup>
      </div>

      <!-- Partner→Upline Reassignment -->
      <div v-else-if="formData.type === 'partner_upline'" class="space-y-4">
        <BaseInputGroup :label="$t('partners.partner')" required>
          <select
            v-model="formData.partner_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
            @change="loadCurrentUpline"
          >
            <option value="">{{ $t('partners.select_partner') }}</option>
            <option v-for="partner in partners" :key="partner.id" :value="partner.id">
              {{ partner.name }}
            </option>
          </select>
        </BaseInputGroup>

        <div v-if="currentUpline" class="p-3 bg-gray-50 rounded border border-gray-200">
          <div class="text-xs text-gray-500 mb-1">{{ $t('partners.current_upline') }}</div>
          <div class="text-sm font-medium text-gray-900">{{ currentUpline.name }}</div>
        </div>

        <BaseInputGroup :label="$t('partners.new_upline')" required>
          <select
            v-model="formData.new_upline_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <option value="">{{ $t('partners.select_new_upline') }}</option>
            <option v-for="partner in availableUplines" :key="partner.id" :value="partner.id">
              {{ partner.name }} ({{ partner.email }})
            </option>
          </select>
        </BaseInputGroup>
      </div>

      <!-- Options -->
      <div class="pt-4 border-t border-gray-200">
        <label class="flex items-center">
          <input
            v-model="formData.preserve_commissions"
            type="checkbox"
            class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
          />
          <span class="ml-2 text-sm text-gray-700">{{ $t('partners.preserve_commissions') }}</span>
        </label>
        <p class="mt-1 text-xs text-gray-500">{{ $t('partners.preserve_commissions_help') }}</p>
      </div>

      <!-- Reason -->
      <BaseInputGroup :label="$t('partners.reassignment_reason')">
        <textarea
          v-model="formData.reason"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
          :placeholder="$t('partners.reassignment_reason_placeholder')"
        ></textarea>
      </BaseInputGroup>

      <!-- Warning -->
      <div class="p-4 bg-yellow-50 border border-yellow-200 rounded">
        <div class="flex">
          <BaseIcon name="ExclamationTriangleIcon" class="w-5 h-5 text-yellow-600 mr-2" />
          <div class="text-sm text-yellow-800">
            {{ $t('partners.reassignment_warning') }}
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex justify-end gap-2">
        <BaseButton variant="secondary" @click="$emit('close')">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton variant="primary" :loading="loading" :disabled="!canSubmit" @click="submitReassignment">
          {{ $t('partners.confirm_reassignment') }}
        </BaseButton>
      </div>
    </template>
  </BaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'

const props = defineProps({
  show: Boolean,
})

const emit = defineEmits(['close', 'reassigned'])

const { t } = useI18n()
const notificationStore = useNotificationStore()

const loading = ref(false)
const companies = ref([])
const partners = ref([])
const availablePartners = ref([])
const availableUplines = ref([])
const currentPartner = ref(null)
const currentUpline = ref(null)

const formData = ref({
  type: 'company_partner',
  company_id: '',
  partner_id: '',
  new_partner_id: '',
  new_upline_id: '',
  preserve_commissions: false,
  reason: '',
})

const canSubmit = computed(() => {
  if (formData.value.type === 'company_partner') {
    return formData.value.company_id && formData.value.new_partner_id
  } else {
    return formData.value.partner_id && formData.value.new_upline_id
  }
})

async function loadCompanies() {
  try {
    const response = await axios.get('/companies')
    companies.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load companies:', error)
  }
}

async function loadPartners() {
  try {
    const response = await axios.get('/partners')
    partners.value = response.data.data || []
  } catch (error) {
    console.error('Failed to load partners:', error)
  }
}

async function loadCurrentPartner() {
  if (!formData.value.company_id) return

  try {
    const response = await axios.get(`/companies/${formData.value.company_id}/current-partner`)
    currentPartner.value = response.data

    // Load available partners excluding current
    availablePartners.value = partners.value.filter(p => p.id !== currentPartner.value?.id)
  } catch (error) {
    console.error('Failed to load current partner:', error)
  }
}

async function loadCurrentUpline() {
  if (!formData.value.partner_id) return

  try {
    const response = await axios.get(`/partners/${formData.value.partner_id}/upline`)
    currentUpline.value = response.data

    // Load available uplines excluding current and the partner itself
    availableUplines.value = partners.value.filter(
      p => p.id !== currentUpline.value?.id && p.id !== parseInt(formData.value.partner_id)
    )
  } catch (error) {
    console.error('Failed to load current upline:', error)
  }
}

function resetForm() {
  formData.value.company_id = ''
  formData.value.partner_id = ''
  formData.value.new_partner_id = ''
  formData.value.new_upline_id = ''
  currentPartner.value = null
  currentUpline.value = null
}

async function submitReassignment() {
  loading.value = true
  try {
    if (formData.value.type === 'company_partner') {
      await axios.post('/reassignments/company-partner', {
        company_id: formData.value.company_id,
        old_partner_id: currentPartner.value.id,
        new_partner_id: formData.value.new_partner_id,
        preserve_commissions: formData.value.preserve_commissions,
        reason: formData.value.reason,
      })
    } else {
      await axios.post('/reassignments/partner-upline', {
        partner_id: formData.value.partner_id,
        old_upline_id: currentUpline.value?.id,
        new_upline_id: formData.value.new_upline_id,
        reason: formData.value.reason,
      })
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('partners.reassignment_successful'),
    })

    emit('reassigned')
    emit('close')
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partners.reassignment_failed'),
    })
  } finally {
    loading.value = false
  }
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    resetForm()
    loadCompanies()
    loadPartners()
  }
})

onMounted(() => {
  if (props.show) {
    loadCompanies()
    loadPartners()
  }
})
</script>

// CLAUDE-CHECKPOINT
