<template>
  <!-- Limit Exceeded / Upgrade Modal -->
  <BaseModal
    :show="upgradeStore.showModal"
    @close="upgradeStore.closeModal"
    :title="$t('subscriptions.upgrade_required')"
    variant="sm"
  >
    <div class="px-6 pb-6">
      <!-- Feature Icon -->
      <div class="flex justify-center mb-4">
        <div
          class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center"
        >
          <BaseIcon :name="featureIcon" class="w-8 h-8 text-primary-600" />
        </div>
      </div>

      <!-- Feature Name -->
      <h3 class="text-lg font-semibold text-center mb-2">
        {{ upgradeStore.modalData.featureName }}
      </h3>

      <!-- Limit Message -->
      <p class="text-sm text-gray-600 text-center mb-4">
        {{ upgradeStore.modalData.message }}
      </p>

      <!-- Usage Stats (if available) -->
      <div
        v-if="upgradeStore.modalData.usage"
        class="bg-gray-50 rounded-lg p-3 mb-4"
      >
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">{{ $t('subscriptions.used') }}</span>
          <span class="font-semibold">
            {{ upgradeStore.modalData.usage.used || 0 }} /
            {{ upgradeStore.modalData.usage.limit || '∞' }}
          </span>
        </div>
        <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
          <div
            class="h-full bg-primary-500 rounded-full transition-all"
            :style="{ width: usagePercentage + '%' }"
          ></div>
        </div>
      </div>

      <!-- Upgrade Tier Info -->
      <div class="bg-gradient-to-br from-primary-50 to-primary-100 rounded-lg p-4 mb-6">
        <div class="text-center">
          <p class="text-xs text-primary-600 uppercase tracking-wide mb-1">
            {{ $t('subscriptions.upgrade_to') }}
          </p>
          <p class="text-2xl font-bold text-primary-900">
            {{ tierName }}
          </p>
          <p class="text-sm text-primary-700 mt-1">
            €{{ tierPrice }}/{{ $t('subscriptions.month') }}
          </p>
        </div>

        <!-- Tier Features -->
        <div class="mt-4 space-y-2">
          <div
            v-for="(feature, index) in tierFeatures"
            :key="index"
            class="flex items-start"
          >
            <BaseIcon
              name="CheckCircleIcon"
              class="w-5 h-5 text-green-600 mr-2 flex-shrink-0 mt-0.5"
            />
            <span class="text-sm text-primary-800">{{ feature }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col space-y-3">
        <BaseButton
          variant="primary"
          @click="handleUpgrade"
          :loading="isUpgrading"
          class="w-full"
        >
          <BaseIcon name="ArrowUpCircleIcon" class="w-4 h-4 mr-2" />
          {{ $t('subscriptions.upgrade_now') }}
        </BaseButton>

        <BaseButton
          variant="outline"
          @click="upgradeStore.closeModal"
          class="w-full"
        >
          {{ $t('general.maybe_later') }}
        </BaseButton>
      </div>

      <!-- Help Text -->
      <p class="text-xs text-center text-gray-500 mt-4">
        {{ $t('subscriptions.cancel_anytime') }}
      </p>
    </div>
  </BaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUpgradeStore } from '@/scripts/stores/upgrade'
import { useNotificationStore } from '@/scripts/stores/notification'

const { t } = useI18n()
const router = useRouter()
const upgradeStore = useUpgradeStore()
const notificationStore = useNotificationStore()

const isUpgrading = ref(false)

// Tier configuration (synced with config/subscriptions.php)
const tiers = {
  starter: {
    name: 'Starter',
    price: 12,
    features: [
      '50 invoices/month',
      '50 expenses/month',
      '20 estimates/month',
      '5 recurring invoices',
      'Basic AI suggestions',
    ],
    paddle_price_id: import.meta.env.VITE_PADDLE_STARTER_PRICE_ID,
  },
  standard: {
    name: 'Standard',
    price: 29,
    features: [
      '200 invoices/month',
      'Unlimited expenses',
      'E-Faktura sending',
      'QES digital signing',
      'Bank connections (PSD2)',
      '3 users',
    ],
    paddle_price_id: import.meta.env.VITE_PADDLE_STANDARD_PRICE_ID,
  },
  business: {
    name: 'Business',
    price: 59,
    features: [
      '1,000 invoices/month',
      'Unlimited everything',
      'Multi-currency',
      'API access',
      'Advanced AI',
      '5 users',
    ],
    paddle_price_id: import.meta.env.VITE_PADDLE_BUSINESS_PRICE_ID,
  },
  max: {
    name: 'Max',
    price: 149,
    features: [
      'Unlimited invoices',
      'Unlimited users',
      'Priority support',
      'Multi-location',
      'IFRS reports',
    ],
    paddle_price_id: import.meta.env.VITE_PADDLE_MAX_PRICE_ID,
  },
}

// Feature to icon mapping
const featureIcons = {
  expenses_per_month: 'CreditCardIcon',
  estimates_per_month: 'DocumentTextIcon',
  custom_fields: 'AdjustmentsHorizontalIcon',
  recurring_invoices_active: 'ArrowPathIcon',
  ai_queries_per_month: 'SparklesIcon',
}

const featureIcon = computed(() => {
  const feature = upgradeStore.modalData.feature
  return featureIcons[feature] || 'LockClosedIcon'
})

const tierName = computed(() => {
  const tier = upgradeStore.modalData.requiredTier
  return tiers[tier]?.name || tier
})

const tierPrice = computed(() => {
  const tier = upgradeStore.modalData.requiredTier
  return tiers[tier]?.price || 0
})

const tierFeatures = computed(() => {
  const tier = upgradeStore.modalData.requiredTier
  return tiers[tier]?.features || []
})

const usagePercentage = computed(() => {
  const usage = upgradeStore.modalData.usage
  if (!usage || !usage.limit) return 100
  return Math.min(100, Math.round((usage.used / usage.limit) * 100))
})

async function handleUpgrade() {
  if (isUpgrading.value) return

  try {
    isUpgrading.value = true

    const tier = upgradeStore.modalData.requiredTier
    const paddlePriceId = tiers[tier]?.paddle_price_id

    // If Paddle is available, open checkout overlay
    if (typeof Paddle !== 'undefined' && paddlePriceId) {
      Paddle.Checkout.open({
        product: paddlePriceId,
        successCallback: (data) => {
          notificationStore.showNotification({
            type: 'success',
            message: t('subscriptions.upgrade_initiated'),
          })
          upgradeStore.closeModal()

          // Reload page after delay to refresh subscription data
          setTimeout(() => {
            window.location.reload()
          }, 2000)
        },
        closeCallback: () => {
          isUpgrading.value = false
        },
      })
    } else {
      // Fallback: Navigate to billing page
      upgradeStore.closeModal()
      router.push('/admin/settings/billing')
    }
  } catch (error) {
    console.error('Upgrade failed:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.message || t('subscriptions.upgrade_failed'),
    })
    isUpgrading.value = false
  }
}
</script>
