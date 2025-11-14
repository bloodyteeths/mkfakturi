<template>
  <div
    v-if="isTrial && daysRemaining !== null"
    class="p-4 rounded-lg border"
    :class="urgencyClass"
  >
    <div class="flex items-start justify-between">
      <div class="flex items-start">
        <BaseIcon
          :name="urgencyIcon"
          :class="iconClass"
          class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5"
        />
        <div>
          <p class="text-sm font-medium" :class="textClass">
            {{ urgencyMessage }}
          </p>
          <p class="text-xs mt-1" :class="detailsClass">
            {{ trialEndDate }}
          </p>
        </div>
      </div>

      <BaseButton
        v-if="daysRemaining <= 3"
        variant="primary"
        size="sm"
        @click="showUpgradeModal = true"
      >
        {{ $t('subscriptions.upgrade_now') }}
      </BaseButton>
    </div>

    <!-- Upgrade CTA Modal -->
    <UpgradeCTA
      v-if="showUpgradeModal"
      :show="showUpgradeModal"
      @close="showUpgradeModal = false"
      required-tier="standard"
      :feature-name="$t('subscriptions.continue_premium_features')"
      icon="ClockIcon"
      :description="$t('subscriptions.trial_ending_description')"
      :features="[
        $t('subscriptions.features.efaktura_sending'),
        $t('subscriptions.features.qes_signing'),
        $t('subscriptions.features.multi_users'),
        $t('subscriptions.features.200_invoices'),
      ]"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import UpgradeCTA from '@/scripts/admin/components/UpgradeCTA.vue'

const { t } = useI18n()
const companyStore = useCompanyStore()
const showUpgradeModal = ref(false)

// Check if company is on trial
const isTrial = computed(() => {
  const company = companyStore.selectedCompany
  return company?.subscription?.on_trial || false
})

// Calculate days remaining
const daysRemaining = computed(() => {
  const company = companyStore.selectedCompany
  if (!company?.subscription?.trial_ends_at) return null

  const endDate = new Date(company.subscription.trial_ends_at)
  const now = new Date()
  const diffTime = endDate - now
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))

  return diffDays >= 0 ? diffDays : 0
})

// Trial end date formatted
const trialEndDate = computed(() => {
  const company = companyStore.selectedCompany
  if (!company?.subscription?.trial_ends_at) return ''

  const endDate = new Date(company.subscription.trial_ends_at)
  return t('subscriptions.trial_ends_on', {
    date: endDate.toLocaleDateString(),
  })
})

// Urgency-based styling
const urgencyClass = computed(() => {
  if (daysRemaining.value === 0) return 'bg-red-50 border-red-200'
  if (daysRemaining.value <= 3) return 'bg-yellow-50 border-yellow-200'
  return 'bg-blue-50 border-blue-200'
})

const urgencyIcon = computed(() => {
  if (daysRemaining.value === 0) return 'ExclamationTriangleIcon'
  if (daysRemaining.value <= 3) return 'ClockIcon'
  return 'InformationCircleIcon'
})

const iconClass = computed(() => {
  if (daysRemaining.value === 0) return 'text-red-600'
  if (daysRemaining.value <= 3) return 'text-yellow-600'
  return 'text-blue-600'
})

const textClass = computed(() => {
  if (daysRemaining.value === 0) return 'text-red-900'
  if (daysRemaining.value <= 3) return 'text-yellow-900'
  return 'text-blue-900'
})

const detailsClass = computed(() => {
  if (daysRemaining.value === 0) return 'text-red-700'
  if (daysRemaining.value <= 3) return 'text-yellow-700'
  return 'text-blue-700'
})

const urgencyMessage = computed(() => {
  if (daysRemaining.value === 0) {
    return t('subscriptions.trial_ends_today')
  } else if (daysRemaining.value === 1) {
    return t('subscriptions.trial_ends_tomorrow')
  } else if (daysRemaining.value <= 3) {
    return t('subscriptions.trial_ends_soon', { days: daysRemaining.value })
  } else {
    return t('subscriptions.trial_active', { days: daysRemaining.value })
  }
})
</script>

<style scoped>
/* Add any custom styles if needed */
</style>
