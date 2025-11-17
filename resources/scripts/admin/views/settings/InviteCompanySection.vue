<template>
  <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('settings.invite_company') }}</h3>
    <p class="text-sm text-gray-600 mb-4">{{ $t('settings.invite_company_description') }}</p>

    <form @submit.prevent="sendInvitation" class="space-y-4">
      <BaseInputGroup :label="$t('general.email')" required>
        <BaseInput
          v-model="form.company_email"
          type="email"
          :placeholder="$t('settings.company_email_placeholder')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('settings.referral_message')">
        <textarea
          v-model="form.message"
          rows="3"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
          :placeholder="$t('settings.referral_message_placeholder')"
        ></textarea>
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
      <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('settings.pending_company_invitations') }}</h4>
      <div class="space-y-2">
        <div v-for="inv in pendingInvitations" :key="inv.id" class="flex items-center justify-between p-3 bg-gray-50 rounded">
          <div>
            <div class="text-sm font-medium text-gray-900">{{ inv.company_email }}</div>
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
import { useGlobalStore } from '@/scripts/admin/stores/global'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const sending = ref(false)
const pendingInvitations = ref([])

const form = ref({
  company_email: '',
  message: '',
})

const canSubmit = computed(() => {
  return form.value.company_email
})

async function sendInvitation() {
  sending.value = true
  try {
    await axios.post('/invitations/company-to-company', {
      inviter_company_id: globalStore.selectedCompany?.id,
      invitee_email: form.value.company_email,
      message: form.value.message,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('settings.company_invitation_sent_successfully'),
    })

    form.value.company_email = ''
    form.value.message = ''
    loadPendingInvitations()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('settings.company_invitation_failed'),
    })
  } finally {
    sending.value = false
  }
}

async function loadPendingInvitations() {
  try {
    const response = await axios.get('/invitations/pending-company', {
      params: { company_id: globalStore.selectedCompany?.id },
    })
    pendingInvitations.value = response.data
  } catch (error) {
    console.error('Failed to load pending company invitations:', error)
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadPendingInvitations()
})
</script>

// CLAUDE-CHECKPOINT
