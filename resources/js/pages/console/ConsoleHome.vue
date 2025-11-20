<template>
  <BasePage>
    <BasePageHeader title="Partner Console">
      <template #actions>
        <BaseButton
          v-if="consoleStore.hasMultipleCompanies"
          variant="white"
          @click="showCompanySwitcher = true"
        >
          <template #left>
            <BuildingOfficeIcon class="h-5 w-5" />
          </template>
          Switch Company
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading State -->
    <div v-if="consoleStore.isLoading" class="text-center py-12">
      <BaseSpinner class="mx-auto" />
      <p class="mt-2 text-sm text-gray-500">Loading companies...</p>
    </div>

    <!-- Main Content -->
    <div v-else class="space-y-8">
      <!-- Section 1: Companies I Manage -->
      <section>
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <h2 class="text-xl font-semibold text-gray-900">Companies I Manage</h2>
            <BaseBadge variant="primary" size="sm">
              {{ consoleStore.totalManaged }}
            </BaseBadge>
          </div>
        </div>

        <!-- Managed Companies Grid -->
        <div v-if="consoleStore.managedCompanies.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <BaseCard
            v-for="company in consoleStore.managedCompanies"
            :key="company.id"
            class="cursor-pointer hover:shadow-lg transition-all duration-200 border-l-4 border-l-blue-500"
            @click="switchToCompany(company)"
          >
            <div class="p-6">
              <div class="flex items-center space-x-3 mb-4">
                <img
                  v-if="company.logo"
                  :src="company.logo"
                  :alt="company.name"
                  class="h-12 w-12 rounded-lg object-cover"
                />
                <div
                  v-else
                  class="h-12 w-12 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center"
                >
                  <BuildingOfficeIcon class="h-6 w-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ company.name }}</h3>
                  <p class="text-sm text-blue-600 font-medium">
                    {{ company.commission_rate || 0 }}% commission
                  </p>
                </div>
                <BaseBadge
                  v-if="company.is_primary"
                  variant="success"
                  size="sm"
                >
                  Primary
                </BaseBadge>
              </div>

              <div v-if="company.address" class="text-sm text-gray-600 mb-3">
                {{ company.address.city }}, {{ company.address.country }}
              </div>

              <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                <span class="text-sm text-gray-500">
                  {{ getPermissionsCount(company.permissions) }} permissions
                </span>
                <div class="flex gap-2">
                  <BaseButton
                    size="sm"
                    variant="danger-outline"
                    @click.stop="unlinkCompany(company)"
                  >
                    Delete
                  </BaseButton>
                  <BaseButton
                    size="sm"
                    variant="primary"
                    @click.stop="switchToCompany(company)"
                  >
                    Manage
                  </BaseButton>
                </div>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Empty State for Managed Companies -->
        <div v-else class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
          <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No companies assigned</h3>
          <p class="mt-1 text-sm text-gray-500">
            You don't have management access to any companies yet.
          </p>
        </div>
      </section>

      <!-- Section 2: Companies I Referred -->
      <section v-if="consoleStore.referredCompanies.length > 0 || consoleStore.totalReferred > 0">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <h2 class="text-xl font-semibold text-gray-900">Companies I Referred</h2>
            <BaseBadge variant="warning" size="sm">
              {{ consoleStore.totalReferred }}
            </BaseBadge>
          </div>
        </div>

        <!-- Referred Companies Grid -->
        <div v-if="consoleStore.referredCompanies.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
          <BaseCard
            v-for="company in consoleStore.referredCompanies"
            :key="company.id"
            class="border-l-4 border-l-orange-500 hover:shadow-md transition-shadow duration-200"
          >
            <div class="p-6">
              <div class="flex items-center space-x-3 mb-4">
                <img
                  v-if="company.logo"
                  :src="company.logo"
                  :alt="company.name"
                  class="h-12 w-12 rounded-lg object-cover"
                />
                <div
                  v-else
                  class="h-12 w-12 rounded-lg bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center"
                >
                  <BuildingOfficeIcon class="h-6 w-6 text-white" />
                </div>
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-gray-900 truncate">{{ company.name }}</h3>
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Referral Only</p>
                </div>
              </div>

              <div class="mb-3 space-y-2">
                <div class="flex justify-between items-center text-sm">
                  <span class="text-gray-600">Total Commissions:</span>
                  <span class="font-semibold text-orange-600">
                    {{ formatMoney(company.total_commissions || 0) }}
                  </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="text-gray-600">Status:</span>
                  <BaseBadge
                    :variant="company.subscription_status === 'active' ? 'success' : 'default'"
                    size="sm"
                  >
                    {{ company.subscription_status || 'Unknown' }}
                  </BaseBadge>
                </div>
              </div>

              <div class="pt-3 border-t border-gray-100">
                <BaseButton
                  size="sm"
                  variant="warning-outline"
                  class="w-full"
                  @click="viewCommissions(company)"
                >
                  View Commissions
                </BaseButton>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Empty State for Referred Companies -->
        <div v-else class="text-center py-12 bg-orange-50 rounded-lg border-2 border-dashed border-orange-300">
          <BuildingOfficeIcon class="mx-auto h-12 w-12 text-orange-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No referral tracking yet</h3>
          <p class="mt-1 text-sm text-gray-500">
            Companies you refer will appear here for commission tracking.
          </p>
        </div>
      </section>

      <!-- Section 3: Pending Invitations -->
      <section v-if="consoleStore.pendingInvitations.length > 0 || consoleStore.totalPending > 0">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center space-x-3">
            <h2 class="text-xl font-semibold text-gray-900">Pending Invitations</h2>
            <BaseBadge variant="danger" size="sm">
              {{ consoleStore.totalPending }}
            </BaseBadge>
          </div>
        </div>

        <!-- Pending Invitations -->
        <div v-if="consoleStore.pendingInvitations.length > 0" class="space-y-4">
          <BaseCard
            v-for="invitation in consoleStore.pendingInvitations"
            :key="invitation.id"
            class="border-l-4 border-l-yellow-500 hover:shadow-md transition-shadow duration-200"
          >
            <div class="p-6">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <div class="flex items-center space-x-3 mb-3">
                    <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-yellow-400 to-yellow-600 flex items-center justify-center">
                      <BuildingOfficeIcon class="h-6 w-6 text-white" />
                    </div>
                    <div>
                      <h3 class="font-semibold text-gray-900">{{ invitation.company_name }}</h3>
                      <p class="text-sm text-gray-600">
                        Invited by {{ invitation.inviter_name || 'Company Admin' }}
                      </p>
                    </div>
                  </div>

                  <div class="mb-3 space-y-2">
                    <div class="text-sm text-gray-600">
                      <span class="font-medium">Invited:</span> {{ formatDate(invitation.invited_at) }}
                    </div>
                    <div v-if="invitation.expires_at" class="text-sm">
                      <span class="font-medium text-gray-600">Expires:</span>
                      <span :class="isExpiringSoon(invitation.expires_at) ? 'text-red-600 font-semibold' : 'text-gray-600'">
                        {{ formatDate(invitation.expires_at) }}
                      </span>
                      <span v-if="isExpiringSoon(invitation.expires_at)" class="ml-2 text-xs text-red-600 font-semibold uppercase">
                        Expiring Soon!
                      </span>
                    </div>
                  </div>

                  <div v-if="invitation.permissions" class="mb-4">
                    <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">Permissions Offered:</span>
                    <div class="flex flex-wrap gap-1 mt-2">
                      <span
                        v-for="perm in getPermissions(invitation.permissions)"
                        :key="perm"
                        class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-md"
                      >
                        {{ perm }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex flex-col gap-2 ml-4">
                  <BaseButton
                    variant="primary"
                    size="sm"
                    @click="respondToInvitation(invitation.id, 'accept')"
                    :loading="respondingTo === invitation.id"
                  >
                    Accept
                  </BaseButton>
                  <BaseButton
                    variant="danger-outline"
                    size="sm"
                    @click="respondToInvitation(invitation.id, 'decline')"
                    :loading="respondingTo === invitation.id"
                  >
                    Decline
                  </BaseButton>
                </div>
              </div>
            </div>
          </BaseCard>
        </div>

        <!-- Empty State for Pending Invitations -->
        <div v-else class="text-center py-12 bg-yellow-50 rounded-lg border-2 border-dashed border-yellow-300">
          <BuildingOfficeIcon class="mx-auto h-12 w-12 text-yellow-400" />
          <h3 class="mt-2 text-sm font-medium text-gray-900">No pending invitations</h3>
          <p class="mt-1 text-sm text-gray-500">
            You don't have any pending company invitations at this time.
          </p>
        </div>
      </section>

      <!-- Global Empty State (when all sections are empty) -->
      <div
        v-if="consoleStore.managedCompanies.length === 0 &&
              consoleStore.referredCompanies.length === 0 &&
              consoleStore.pendingInvitations.length === 0"
        class="text-center py-16"
      >
        <BuildingOfficeIcon class="mx-auto h-16 w-16 text-gray-400" />
        <h3 class="mt-4 text-lg font-medium text-gray-900">Welcome to the Partner Console</h3>
        <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
          You don't have any companies assigned yet. Contact your administrator to get access to company accounts
          or wait for company invitations.
        </p>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useRouter } from 'vue-router'
import { useNotificationStore } from '@/scripts/stores/notification'
import { BuildingOfficeIcon } from '@heroicons/vue/24/outline'
import BasePage from '@/scripts/components/base/BasePage.vue'
import BasePageHeader from '@/scripts/components/base/BasePageHeader.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'

const consoleStore = useConsoleStore()
const router = useRouter()
const notificationStore = useNotificationStore()
const showCompanySwitcher = ref(false)
const respondingTo = ref(null)

onMounted(async () => {
  await consoleStore.fetchCompanies()
})

const switchToCompany = async (company) => {
  try {
    await consoleStore.switchCompany(company.id)
    // Redirect to company-specific dashboard
    router.push({ name: 'dashboard' })
  } catch (error) {
    console.error('Failed to switch company:', error)
    notificationStore.showNotification({
      type: 'error',
      message: 'Failed to switch company. Please try again.',
    })
  }
}

const viewCommissions = (company) => {
  // Navigate to commissions view for this referred company
  router.push({
    name: 'partner.commissions',
    query: { company_id: company.id }
  })
}

const respondToInvitation = async (invitationId, action) => {
  respondingTo.value = invitationId
  try {
    await consoleStore.respondToInvitation(invitationId, action)
    notificationStore.showNotification({
      type: 'success',
      message: action === 'accept'
        ? 'Invitation accepted successfully! You now have access to this company.'
        : 'Invitation declined.',
    })
  } catch (error) {
    console.error('Failed to respond to invitation:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to process invitation response.',
    })
  } finally {
    respondingTo.value = null
  }
}

const unlinkCompany = async (company) => {
  if (!confirm(`Are you sure you want to remove access to "${company.name}"? The company can re-invite you later if needed.`)) {
    return
  }

  try {
    await axios.delete(`/invitations/companies/${company.id}/unlink`)
    notificationStore.showNotification({
      type: 'success',
      message: 'Successfully removed access to company',
    })
    // Refresh the list
    await consoleStore.fetchCompanies()
  } catch (error) {
    console.error('Failed to unlink company:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Failed to remove company access',
    })
  }
}

const getPermissions = (permissionsJson) => {
  try {
    const perms = typeof permissionsJson === 'string' ? JSON.parse(permissionsJson) : permissionsJson
    return Array.isArray(perms) ? perms : []
  } catch {
    return []
  }
}

const getPermissionsCount = (permissions) => {
  if (!permissions) return 0
  try {
    const parsed = typeof permissions === 'string' ? JSON.parse(permissions) : permissions
    return Array.isArray(parsed) ? parsed.length : 0
  } catch {
    return 0
  }
}

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatMoney = (amount) => {
  if (!amount) return '$0.00'
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(amount / 100) // Assuming amount is in cents
}

const isExpiringSoon = (expiresAt) => {
  if (!expiresAt) return false
  const expirationDate = new Date(expiresAt)
  const now = new Date()
  const daysUntilExpiration = Math.ceil((expirationDate - now) / (1000 * 60 * 60 * 24))
  return daysUntilExpiration <= 3 && daysUntilExpiration >= 0
}
</script>

<!-- CLAUDE-CHECKPOINT -->

