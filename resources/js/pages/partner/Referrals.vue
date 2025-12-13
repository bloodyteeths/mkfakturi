<template>
  <BasePage>
    <BasePageHeader :title="$t('console.invite_partner.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/partner/dashboard" />
        <BaseBreadcrumbItem :title="$t('console.invite_partner.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- QR Code Section -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('console.invite_partner.qr_code') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $t('console.invite_partner.qr_description') }}</p>

        <div v-if="loading" class="flex items-center justify-center h-64">
          <BaseLoader />
        </div>

        <div v-else-if="inviteData" class="space-y-4">
          <div class="flex justify-center p-6 bg-gray-50 rounded">
            <img :src="inviteData.qr_code_url" alt="QR Code" class="w-64 h-64" />
          </div>

          <div class="text-center">
            <p class="text-xs text-gray-500 mb-2">{{ $t('console.invite_partner.scan_to_signup') }}</p>
            <BaseButton variant="primary-outline" size="sm" @click="downloadQR">
              <template #left="slotProps">
                <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
              </template>
              {{ $t('console.invite_partner.download_qr') }}
            </BaseButton>
          </div>
        </div>
      </div>

      <!-- Share Link Section -->
      <div class="p-6 bg-white border border-gray-200 rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('console.invite_partner.share_link') }}</h3>
        <p class="text-sm text-gray-600 mb-4">{{ $t('console.invite_partner.link_description') }}</p>

        <div v-if="inviteData" class="space-y-4">
          <BaseInputGroup :label="$t('console.invite_partner.your_link')">
            <BaseInput :model-value="inviteData.link" readonly>
              <template #right>
                <button @click="copyLink" class="text-primary-500 hover:text-primary-700">
                  <BaseIcon :name="copied ? 'CheckIcon' : 'ClipboardDocumentIcon'" class="w-5 h-5" />
                </button>
              </template>
            </BaseInput>
          </BaseInputGroup>

          <div class="pt-4 border-t border-gray-200">
            <h4 class="text-sm font-medium text-gray-900 mb-3">{{ $t('console.invite_partner.email_invite') }}</h4>
            <form @submit.prevent="sendEmailInvite" class="space-y-3">
              <BaseInput
                v-model="emailForm.email"
                type="email"
                :placeholder="$t('console.invite_partner.email_placeholder')"
              />
              <BaseButton type="submit" :loading="sendingEmail" size="sm">
                {{ $t('console.invite_partner.send_email') }}
              </BaseButton>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Referral Stats -->
    <div class="mt-6 p-6 bg-white border border-gray-200 rounded-lg shadow">
      <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $t('console.invite_partner.referral_stats') }}</h3>
      <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-sm text-gray-500">{{ $t('console.invite_partner.total_referrals') }}</div>
          <div class="text-2xl font-bold text-primary-600">{{ stats.total_referrals || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-sm text-gray-500">{{ $t('console.invite_partner.active_downline') }}</div>
          <div class="text-2xl font-bold text-green-600">{{ stats.active_downline || 0 }}</div>
        </div>
        <div class="p-4 bg-gray-50 rounded">
          <div class="text-sm text-gray-500">{{ $t('console.invite_partner.upline_earnings') }}</div>
          <div class="text-2xl font-bold text-blue-600">€{{ stats.upline_earnings || 0 }}</div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const loading = ref(true)
const sendingEmail = ref(false)
const copied = ref(false)
const inviteData = ref(null)
const stats = ref({})

const emailForm = ref({
  email: '',
})

async function loadInviteLink() {
  loading.value = true
  try {
    const response = await axios.post('/invitations/partner-to-partner')
    inviteData.value = response.data
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('console.invite_partner.load_failed'),
    })
  } finally {
    loading.value = false
  }
}

function copyLink() {
  navigator.clipboard.writeText(inviteData.value.link)
  copied.value = true
  notificationStore.showNotification({
    type: 'success',
    message: t('console.invite_partner.link_copied'),
  })
  setTimeout(() => (copied.value = false), 2000)
}

function downloadQR() {
  const link = document.createElement('a')
  link.href = inviteData.value.qr_code_url
  link.download = 'partner-referral-qr.png'
  link.click()
}

async function sendEmailInvite() {
  sendingEmail.value = true
  try {
    await axios.post('/invitations/send-partner-email', {
      email: emailForm.value.email,
      link: inviteData.value.link,
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('console.invite_partner.email_sent'),
    })
    emailForm.value.email = ''
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('console.invite_partner.email_failed'),
    })
  } finally {
    sendingEmail.value = false
  }
}

onMounted(() => {
  loadInviteLink()
})
</script>
