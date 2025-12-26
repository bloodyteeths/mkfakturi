<template>
  <div class="invitations-page min-h-screen overflow-auto pb-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $t('partner.console.invitations') }}</h1>
      <p class="text-gray-600">{{ $t('partner.invitations.description') }}</p>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="space-y-4">
      <div v-for="n in 3" :key="n" class="bg-white rounded-lg shadow p-6 animate-pulse">
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
              <div class="h-12 w-12 rounded-lg bg-gray-200"></div>
              <div>
                <div class="h-5 w-48 bg-gray-200 rounded mb-2"></div>
                <div class="h-4 w-32 bg-gray-200 rounded"></div>
              </div>
            </div>
            <div class="space-y-2 mb-4">
              <div class="h-4 w-40 bg-gray-200 rounded"></div>
              <div class="h-4 w-36 bg-gray-200 rounded"></div>
            </div>
          </div>
          <div class="flex flex-col gap-2 ml-6">
            <div class="h-10 w-24 bg-gray-200 rounded-lg"></div>
            <div class="h-10 w-24 bg-gray-200 rounded-lg"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6" role="alert" aria-live="polite">
      <p class="text-red-800">{{ error }}</p>
      <button
        @click="loadInvitations"
        class="mt-2 text-red-600 underline"
        :aria-label="$t('general.retry')"
      >
        {{ $t('general.retry') }}
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="invitations.length === 0" class="bg-white rounded-lg shadow p-12 text-center" role="status" aria-live="polite">
      <svg
        class="mx-auto h-16 w-16 text-gray-400 mb-4"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        aria-hidden="true"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $t('partner.invitations.no_invitations') }}</h3>
      <p class="text-gray-500">{{ $t('partner.invitations.no_invitations_description') }}</p>
    </div>

    <!-- Invitations List -->
    <div v-else class="space-y-4" role="list" :aria-label="$t('partner.console.invitations')">
      <div
        v-for="invitation in invitations"
        :key="invitation.id"
        class="bg-white rounded-lg shadow p-6 border-l-4 border-l-yellow-500"
        role="listitem"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
              <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center" aria-hidden="true">
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ invitation.company_name }}</h3>
                <p class="text-sm text-gray-600">{{ $t('partner.invitations.invited_by') }}: {{ invitation.inviter_name || $t('partner.invitations.administrator') }}</p>
              </div>
            </div>

            <div class="space-y-2 mb-4">
              <div class="text-sm text-gray-600">
                <span class="font-medium">{{ $t('general.date') }}:</span> {{ formatDate(invitation.invited_at) }}
              </div>
              <div v-if="invitation.expires_at" class="text-sm">
                <span class="font-medium text-gray-600">{{ $t('partner.invitations.expires') }}:</span>
                <span :class="isExpiringSoon(invitation.expires_at) ? 'text-red-600 font-semibold' : 'text-gray-600'">
                  {{ formatDate(invitation.expires_at) }}
                </span>
                <span v-if="isExpiringSoon(invitation.expires_at)" class="ml-2 text-xs text-red-600 font-semibold uppercase">
                  {{ $t('partner.invitations.expiring_soon') }}
                </span>
              </div>
            </div>

            <div v-if="invitation.permissions" class="mb-4">
              <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">{{ $t('partner.invitations.offered_permissions') }}:</span>
              <div class="flex flex-wrap gap-1 mt-2">
                <span
                  v-for="perm in getPermissions(invitation.permissions)"
                  :key="perm"
                  class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded"
                >
                  {{ translatePermission(perm) }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex flex-col gap-2 ml-6">
            <button
              @click="confirmAccept(invitation)"
              :disabled="responding === invitation.id"
              class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
              :aria-label="$t('partner.invitations.accept_invitation_aria', { company: invitation.company_name })"
            >
              <svg v-if="responding === invitation.id && respondingAction === 'accept'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('partner.invitations.accept') }}
            </button>
            <button
              @click="confirmDecline(invitation)"
              :disabled="responding === invitation.id"
              class="px-4 py-2 bg-white text-red-600 border border-red-300 rounded-lg font-medium hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
              :aria-label="$t('partner.invitations.decline_invitation_aria', { company: invitation.company_name })"
            >
              <svg v-if="responding === invitation.id && respondingAction === 'decline'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ $t('partner.invitations.decline') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'

const { t } = useI18n()
const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()

// State
const invitations = ref([])
const loading = ref(true)
const error = ref(null)
const responding = ref(null)
const respondingAction = ref(null)

// AbortController for cancelling pending requests
let abortController = null

onMounted(() => {
  loadInvitations()
})

onBeforeUnmount(() => {
  // Cancel any pending requests
  if (abortController) {
    abortController.abort()
  }
})

async function loadInvitations() {
  // Cancel any pending request before starting a new one
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  loading.value = true
  error.value = null

  try {
    const response = await axios.get('/invitations/pending-for-partner', {
      signal: abortController.signal
    })
    invitations.value = response.data || []
  } catch (err) {
    // Ignore abort errors - they are expected when cancelling requests
    if (err.name === 'AbortError' || err.message === 'canceled') {
      return
    }
    error.value = t('partner.invitations.load_error')
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.invitations.load_error')
    })
  } finally {
    loading.value = false
  }
}

async function confirmAccept(invitation) {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('partner.invitations.accept_confirm', { company: invitation.company_name }),
    yesLabel: t('partner.invitations.accept'),
    noLabel: t('general.cancel'),
    variant: 'primary',
    hideNoButton: false,
    size: 'md'
  })

  if (confirmed) {
    respondToInvitation(invitation.id, 'accept')
  }
}

async function confirmDecline(invitation) {
  const confirmed = await dialogStore.openDialog({
    title: t('general.are_you_sure'),
    message: t('partner.invitations.decline_confirm', { company: invitation.company_name }),
    yesLabel: t('partner.invitations.decline'),
    noLabel: t('general.cancel'),
    variant: 'danger',
    hideNoButton: false,
    size: 'md'
  })

  if (confirmed) {
    respondToInvitation(invitation.id, 'decline')
  }
}

async function respondToInvitation(invitationId, action) {
  responding.value = invitationId
  respondingAction.value = action

  try {
    await axios.post(`/invitations/${invitationId}/respond`, { action })

    // Remove from list
    invitations.value = invitations.value.filter(inv => inv.id !== invitationId)

    // Show success notification
    const message = action === 'accept'
      ? t('partner.invitations.accepted_success')
      : t('partner.invitations.declined_success')

    notificationStore.showNotification({
      type: 'success',
      message
    })
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partner.invitations.respond_error')
    })
  } finally {
    responding.value = null
    respondingAction.value = null
  }
}

function getPermissions(permissionsJson) {
  try {
    const perms = typeof permissionsJson === 'string' ? JSON.parse(permissionsJson) : permissionsJson
    return Array.isArray(perms) ? perms : []
  } catch {
    return []
  }
}

function translatePermission(perm) {
  const translations = {
    'view-dashboard': t('partner.invitations.permissions.view_dashboard'),
    'view-customers': t('partner.invitations.permissions.view_customers'),
    'manage-customers': t('partner.invitations.permissions.manage_customers'),
    'view-invoices': t('partner.invitations.permissions.view_invoices'),
    'manage-invoices': t('partner.invitations.permissions.manage_invoices'),
    'view-payments': t('partner.invitations.permissions.view_payments'),
    'manage-payments': t('partner.invitations.permissions.manage_payments'),
    'view-expenses': t('partner.invitations.permissions.view_expenses'),
    'manage-expenses': t('partner.invitations.permissions.manage_expenses'),
    'view-reports': t('partner.invitations.permissions.view_reports'),
    'manage-settings': t('partner.invitations.permissions.manage_settings')
  }
  return translations[perm] || perm
}

function formatDate(dateString) {
  if (!dateString) return t('general.unknown')
  try {
    return new Date(dateString).toLocaleDateString('mk-MK', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch {
    return dateString
  }
}

function isExpiringSoon(expiresAt) {
  if (!expiresAt) return false
  const expirationDate = new Date(expiresAt)
  const now = new Date()
  const daysUntilExpiration = Math.ceil((expirationDate - now) / (1000 * 60 * 60 * 24))
  return daysUntilExpiration <= 3 && daysUntilExpiration >= 0
}
</script>

<style scoped>
.invitations-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>

// CLAUDE-CHECKPOINT
