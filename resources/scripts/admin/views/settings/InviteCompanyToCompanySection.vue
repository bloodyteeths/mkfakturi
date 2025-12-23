<template>
  <BasePage>
    <BasePageHeader :title="$t('settings.invite_company.title')">
      <template #actions>
        <BaseButton
          v-if="stats.total_referrals > 0"
          variant="primary-outline"
          size="sm"
          @click="showReferralHistory = !showReferralHistory"
        >
          {{ showReferralHistory ? $t('settings.invite_company.hide_history') : $t('settings.invite_company.show_history') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- Generate Link Section -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('settings.invite_company.generate_link') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $t('settings.invite_company.generate_description') }}</p>

        <div v-if="linkData" class="space-y-4">
          <BaseInputGroup :label="$t('settings.invite_company.your_link')">
            <BaseInput :model-value="linkData.signup_link" readonly>
              <template #right>
                <button @click="copyLink" class="text-primary-500 hover:text-primary-700">
                  <BaseIcon :name="copied ? 'CheckIcon' : 'ClipboardDocumentIcon'" class="w-5 h-5" />
                </button>
              </template>
            </BaseInput>
          </BaseInputGroup>

          <div v-if="linkData.qr_code_url" class="flex justify-center p-4 bg-gray-50 rounded">
            <img :src="linkData.qr_code_url" alt="QR Code" class="w-48 h-48" />
          </div>
        </div>

        <div v-else class="space-y-4">
          <p class="text-sm text-gray-500">{{ $t('settings.invite_company.no_link_yet') }}</p>
          <BaseButton variant="primary" @click="generateLink" :loading="generatingLink">
            {{ $t('settings.invite_company.generate_button') }}
          </BaseButton>
        </div>
      </div>

      <!-- Email Invite Section -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('settings.invite_company.email_invite') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $t('settings.invite_company.email_description') }}</p>

        <form @submit.prevent="sendEmailInvite" class="space-y-4">
          <BaseInputGroup :label="$t('settings.invite_company.invitee_email')" :error="emailError">
            <BaseInput
              v-model="emailForm.email"
              type="email"
              :placeholder="$t('settings.invite_company.email_placeholder')"
              @input="emailError = ''"
            />
          </BaseInputGroup>

          <BaseButton type="submit" :loading="sendingEmail" :disabled="!emailForm.email">
            {{ $t('settings.invite_company.send_invite') }}
          </BaseButton>
        </form>
      </div>
    </div>

    <!-- Referral Stats -->
    <div class="mt-6 p-6 bg-white border border-gray-200 rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('settings.invite_company.referral_stats') }}</h3>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-sm text-gray-500">{{ $t('settings.invite_company.total_invites') }}</div>
          <div class="text-2xl font-bold text-primary-600">{{ stats.total_referrals || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-sm text-gray-500">{{ $t('settings.invite_company.accepted_invites') }}</div>
          <div class="text-2xl font-bold text-green-600">{{ stats.accepted || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-sm text-gray-500">{{ $t('settings.invite_company.pending_invites') }}</div>
          <div class="text-2xl font-bold text-yellow-600">{{ stats.pending || 0 }}</div>
        </div>
      </div>
    </div>

    <!-- Referral History -->
    <div v-if="showReferralHistory" class="mt-6 p-6 bg-white border border-gray-200 rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('settings.invite_company.referral_history') }}</h3>
      <table v-if="referrals.length > 0" class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('settings.invite_company.email') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('settings.invite_company.status') }}</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('settings.invite_company.date') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="referral in referrals" :key="referral.id">
            <td class="px-6 py-4 text-sm text-gray-900">{{ referral.invitee_email }}</td>
            <td class="px-6 py-4">
              <span :class="getStatusClass(referral.status)" class="px-2 py-1 text-xs rounded-full">
                {{ referral.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(referral.created_at) }}</td>
          </tr>
        </tbody>
      </table>
      <p v-else class="text-sm text-gray-500">{{ $t('settings.invite_company.no_referrals') }}</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const companyStore = useCompanyStore()

const generatingLink = ref(false)
const sendingEmail = ref(false)
const copied = ref(false)
const linkData = ref(null)
const showReferralHistory = ref(false)
const referrals = ref([])
const emailError = ref('')

const stats = ref({
  total_referrals: 0,
  accepted: 0,
  pending: 0,
})

const emailForm = ref({
  email: '',
})

const companyId = computed(() => companyStore.selectedCompany?.id)

async function generateLink() {
  if (!companyId.value) return

  generatingLink.value = true
  try {
    // Generate a link (creates a pending referral with empty email that can be used as a general link)
    const response = await axios.post('/api/v1/invitations/company-to-company', {
      inviter_company_id: companyId.value,
      invitee_email: 'link@generated.placeholder',
    })
    linkData.value = response.data
    await loadStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('settings.invite_company.generate_failed'),
    })
  } finally {
    generatingLink.value = false
  }
}

function copyLink() {
  if (!linkData.value?.signup_link) return

  navigator.clipboard.writeText(linkData.value.signup_link)
  copied.value = true
  notificationStore.showNotification({
    type: 'success',
    message: t('settings.invite_company.link_copied'),
  })
  setTimeout(() => (copied.value = false), 2000)
}

async function sendEmailInvite() {
  if (!emailForm.value.email || !companyId.value) return

  // Simple email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(emailForm.value.email)) {
    emailError.value = t('settings.invite_company.invalid_email')
    return
  }

  sendingEmail.value = true
  try {
    const response = await axios.post('/api/v1/invitations/company-to-company', {
      inviter_company_id: companyId.value,
      invitee_email: emailForm.value.email,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('settings.invite_company.invite_sent'),
    })
    emailForm.value.email = ''

    // Store the link for display
    if (!linkData.value) {
      linkData.value = response.data
    }

    await loadStats()
    await loadReferrals()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('settings.invite_company.send_failed'),
    })
  } finally {
    sendingEmail.value = false
  }
}

async function loadStats() {
  if (!companyId.value) return

  try {
    const response = await axios.get(`/api/v1/companies/${companyId.value}/referral-stats`)
    stats.value = response.data
  } catch (error) {
    // Stats are optional, don't show error
  }
}

async function loadReferrals() {
  if (!companyId.value) return

  try {
    const response = await axios.get(`/api/v1/companies/${companyId.value}/referrals`)
    referrals.value = response.data.data || []
  } catch (error) {
    // Referrals are optional, don't show error
  }
}

function getStatusClass(status) {
  switch (status) {
    case 'accepted':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'expired':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}

onMounted(async () => {
  await loadStats()
  await loadReferrals()
})
</script>

// CLAUDE-CHECKPOINT
