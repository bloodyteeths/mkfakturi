<template>
  <div class="invitations-page min-h-screen overflow-auto pb-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-2">Покани</h1>
      <p class="text-gray-600">Прегледајте и одговорете на покани од компании</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
      <button @click="loadInvitations" class="mt-2 text-red-600 underline">Обиди се повторно</button>
    </div>

    <!-- Empty State -->
    <div v-else-if="invitations.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
      <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Нема покани</h3>
      <p class="text-gray-500">Во моментов немате покани од компании.</p>
    </div>

    <!-- Invitations List -->
    <div v-else class="space-y-4">
      <div
        v-for="invitation in invitations"
        :key="invitation.id"
        class="bg-white rounded-lg shadow p-6 border-l-4 border-l-yellow-500"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
              <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center">
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10" />
                </svg>
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-900">{{ invitation.company_name }}</h3>
                <p class="text-sm text-gray-600">Поканет од: {{ invitation.inviter_name || 'Администратор' }}</p>
              </div>
            </div>

            <div class="space-y-2 mb-4">
              <div class="text-sm text-gray-600">
                <span class="font-medium">Датум:</span> {{ formatDate(invitation.invited_at) }}
              </div>
              <div v-if="invitation.expires_at" class="text-sm">
                <span class="font-medium text-gray-600">Истекува:</span>
                <span :class="isExpiringSoon(invitation.expires_at) ? 'text-red-600 font-semibold' : 'text-gray-600'">
                  {{ formatDate(invitation.expires_at) }}
                </span>
                <span v-if="isExpiringSoon(invitation.expires_at)" class="ml-2 text-xs text-red-600 font-semibold uppercase">
                  Истекува наскоро!
                </span>
              </div>
            </div>

            <div v-if="invitation.permissions" class="mb-4">
              <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">Понудени дозволи:</span>
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
              @click="respondToInvitation(invitation.id, 'accept')"
              :disabled="responding === invitation.id"
              class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center justify-center"
            >
              <svg v-if="responding === invitation.id" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Прифати
            </button>
            <button
              @click="respondToInvitation(invitation.id, 'decline')"
              :disabled="responding === invitation.id"
              class="px-4 py-2 bg-white text-red-600 border border-red-300 rounded-lg font-medium hover:bg-red-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Одбиј
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PartnerInvitations',

  data() {
    return {
      invitations: [],
      loading: true,
      error: null,
      responding: null
    }
  },

  mounted() {
    this.loadInvitations()
  },

  methods: {
    async loadInvitations() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/invitations/pending-for-partner')
        this.invitations = response.data || []
      } catch (err) {
        console.error('Failed to load invitations:', err)
        this.error = 'Не можеше да се вчитаат поканите. Обидете се повторно.'
      } finally {
        this.loading = false
      }
    },

    async respondToInvitation(invitationId, action) {
      this.responding = invitationId
      try {
        await axios.post(`/invitations/${invitationId}/respond`, { action })

        // Remove from list
        this.invitations = this.invitations.filter(inv => inv.id !== invitationId)

        // Show success message (simple alert for now)
        const message = action === 'accept'
          ? 'Поканата е успешно прифатена!'
          : 'Поканата е одбиена.'
        alert(message)
      } catch (err) {
        console.error('Failed to respond to invitation:', err)
        alert('Грешка при обработка на поканата. Обидете се повторно.')
      } finally {
        this.responding = null
      }
    },

    getPermissions(permissionsJson) {
      try {
        const perms = typeof permissionsJson === 'string' ? JSON.parse(permissionsJson) : permissionsJson
        return Array.isArray(perms) ? perms : []
      } catch {
        return []
      }
    },

    translatePermission(perm) {
      const translations = {
        'view-dashboard': 'Преглед на табла',
        'view-customers': 'Преглед на клиенти',
        'manage-customers': 'Управување со клиенти',
        'view-invoices': 'Преглед на фактури',
        'manage-invoices': 'Управување со фактури',
        'view-payments': 'Преглед на плаќања',
        'manage-payments': 'Управување со плаќања',
        'view-expenses': 'Преглед на трошоци',
        'manage-expenses': 'Управување со трошоци',
        'view-reports': 'Преглед на извештаи',
        'manage-settings': 'Управување со поставки'
      }
      return translations[perm] || perm
    },

    formatDate(dateString) {
      if (!dateString) return 'Непознато'
      try {
        return new Date(dateString).toLocaleDateString('mk-MK', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      } catch {
        return dateString
      }
    },

    isExpiringSoon(expiresAt) {
      if (!expiresAt) return false
      const expirationDate = new Date(expiresAt)
      const now = new Date()
      const daysUntilExpiration = Math.ceil((expirationDate - now) / (1000 * 60 * 60 * 24))
      return daysUntilExpiration <= 3 && daysUntilExpiration >= 0
    }
  }
}
</script>

<style scoped>
.invitations-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
</style>
