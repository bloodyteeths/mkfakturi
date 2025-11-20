<template>
  <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('settings.invite_partner') }}</h3>
    <p class="text-sm text-gray-600 mb-4">{{ $t('settings.invite_partner_description') }}</p>

    <form @submit.prevent="sendInvitation" class="space-y-4">
      <BaseInputGroup :label="$t('partners.email')" required>
        <BaseInput
          v-model="form.partner_email"
          type="email"
          :placeholder="$t('settings.partner_email_placeholder')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('partners.permissions')" required>
        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
          <PermissionEditor v-model="form.permissions" />
        </div>
      </BaseInputGroup>

      <BaseButton type="submit" :loading="sending" :disabled="!canSubmit">
        <template #left="slotProps">
          <BaseIcon name="PaperAirplaneIcon" :class="slotProps.class" />
        </template>
        {{ $t('settings.send_invitation') }}
      </BaseButton>
    </form>

    <!-- Pending Invitations -->
    <div v-if="pendingInvitations.length > 0" class="mt-6 pt-6 border-t border-gray-200">
      <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('settings.pending_invitations') }}</h4>
      <div class="space-y-2">
        <div v-for="inv in pendingInvitations" :key="inv.id" class="flex items-center justify-between p-3 bg-gray-50 rounded">
          <div>
            <div class="text-sm font-medium text-gray-900">{{ inv.partner_email }}</div>
            <div class="text-xs text-gray-500">{{ $t('settings.invited_at') }}: {{ formatDate(inv.invited_at) }}</div>
          </div>
          <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded">{{ $t('general.pending') }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import PermissionEditor from '@/scripts/admin/views/partners/components/PermissionEditor.vue'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const companyStore = useCompanyStore()

const sending = ref(false)
const pendingInvitations = ref([])

const form = ref({
  partner_email: '',
  permissions: [],
})

const canSubmit = computed(() => {
  return form.value.partner_email && form.value.permissions.length > 0
})

async function sendInvitation() {
  sending.value = true
  try {
    await axios.post('/invitations/company-to-partner', {
      company_id: companyStore.selectedCompany?.id,
      partner_email: form.value.partner_email,
      permissions: form.value.permissions,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('settings.invitation_sent_successfully'),
    })

    form.value.partner_email = ''
    form.value.permissions = []
    loadPendingInvitations()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('settings.invitation_failed'),
    })
  } finally {
    sending.value = false
  }
}

async function loadPendingInvitations() {
  try {
    const response = await axios.get('/invitations/pending', {
      params: { company_id: companyStore.selectedCompany?.id },
    })
    pendingInvitations.value = response.data
  } catch (error) {
    console.error('Failed to load pending invitations:', error)
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadPendingInvitations()
})
</script>
