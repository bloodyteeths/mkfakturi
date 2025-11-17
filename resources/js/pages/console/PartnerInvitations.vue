<template>
  <BasePage>
    <BasePageHeader :title="$t('console.invitations.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/console" />
        <BaseBreadcrumbItem :title="$t('console.invitations.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="mt-6 space-y-4">
      <div v-if="loading" class="space-y-2">
        <div v-for="i in 3" :key="i" class="h-20 bg-gray-200 rounded animate-pulse"></div>
      </div>

      <div v-else-if="invitations.length === 0" class="text-center py-12">
        <BaseIcon name="InboxIcon" class="w-16 h-16 mx-auto text-gray-400 mb-4" />
        <p class="text-gray-500">{{ $t('console.invitations.no_invitations') }}</p>
      </div>

      <div v-else class="space-y-4">
        <div v-for="inv in invitations" :key="inv.id" class="p-6 bg-white border border-gray-200 rounded-lg shadow">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h4 class="text-lg font-medium text-gray-900">{{ inv.company_name }}</h4>
              <p class="text-sm text-gray-600 mt-1">{{ $t('console.invitations.invited_by') }}: {{ inv.inviter_name }}</p>
              <p class="text-xs text-gray-500 mt-2">{{ formatDate(inv.invited_at) }}</p>

              <div class="mt-3">
                <span class="text-xs font-medium text-gray-700">{{ $t('partners.permissions') }}:</span>
                <div class="flex flex-wrap gap-1 mt-1">
                  <span v-for="perm in getPermissions(inv.permissions)" :key="perm" class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded">
                    {{ perm }}
                  </span>
                </div>
              </div>
            </div>

            <div class="flex gap-2 ml-4">
              <BaseButton variant="primary" size="sm" @click="respondToInvitation(inv.id, 'accept')" :loading="responding === inv.id">
                {{ $t('general.accept') }}
              </BaseButton>
              <BaseButton variant="danger-outline" size="sm" @click="respondToInvitation(inv.id, 'decline')" :loading="responding === inv.id">
                {{ $t('general.decline') }}
              </BaseButton>
            </div>
          </div>
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
const responding = ref(null)
const invitations = ref([])

async function loadInvitations() {
  loading.value = true
  try {
    const response = await axios.get('/invitations/pending-for-partner')
    invitations.value = response.data
  } catch (error) {
    console.error('Failed to load invitations:', error)
  } finally {
    loading.value = false
  }
}

async function respondToInvitation(linkId, action) {
  responding.value = linkId
  try {
    await axios.post(`/invitations/${linkId}/respond`, { action })
    notificationStore.showNotification({
      type: 'success',
      message: action === 'accept' ? t('console.invitations.accepted') : t('console.invitations.declined'),
    })
    invitations.value = invitations.value.filter(inv => inv.id !== linkId)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('console.invitations.response_failed'),
    })
  } finally {
    responding.value = null
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

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadInvitations()
})
</script>
