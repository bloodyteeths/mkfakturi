<template>
  <!-- Upgrade CTA Modal -->
  <BaseModal
    :show="show"
    @close="$emit('close')"
    :title="title || $t('subscriptions.upgrade_required')"
    variant="sm"
  >
    <div class="px-6 pb-6">
      <!-- Feature Icon -->
      <div class="flex justify-center mb-4">
        <div
          class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center"
        >
          <BaseIcon
            :name="icon || 'LockClosedIcon'"
            class="w-8 h-8 text-primary-600"
          />
        </div>
      </div>

      <!-- Feature Name -->
      <h3 class="text-lg font-semibold text-center mb-2">
        {{ featureName }}
      </h3>

      <!-- Description -->
      <p class="text-sm text-gray-600 text-center mb-6">
        {{
          description ||
          $t('subscriptions.feature_requires_plan', {
            feature: featureName,
            plan: requiredTierName,
          })
        }}
      </p>

      <!-- Pricing Info -->
      <div class="bg-gray-50 rounded-lg p-4 mb-6">
        <div class="text-center">
          <p class="text-sm text-gray-500 mb-1">
            {{ $t('subscriptions.upgrade_to') }}
          </p>
          <p class="text-2xl font-bold text-gray-900">
            {{ requiredTierName }}
          </p>
          <p class="text-sm text-gray-600 mt-1">
            €{{ price }}/{{ $t('subscriptions.month') }}
          </p>
        </div>

        <!-- Features List (if provided) -->
        <div v-if="features && features.length" class="mt-4 space-y-2">
          <div
            v-for="(feature, index) in features"
            :key="index"
            class="flex items-start"
          >
            <BaseIcon
              name="CheckCircleIcon"
              class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5"
            />
            <span class="text-sm text-gray-700">{{ feature }}</span>
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

        <BaseButton variant="outline" @click="$emit('close')" class="w-full">
          {{ $t('general.cancel') }}
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
import { useNotificationStore } from '@/scripts/stores/notification'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  requiredTier: {
    type: String,
    required: true, // 'starter', 'standard', 'business', 'max'
  },
  featureName: {
    type: String,
    required: true, // e.g., 'E-Faktura Sending'
  },
  title: {
    type: String,
    default: null,
  },
  description: {
    type: String,
    default: null,
  },
  icon: {
    type: String,
    default: 'LockClosedIcon',
  },
  features: {
    type: Array,
    default: null, // Optional list of features to highlight
  },
})

const emit = defineEmits(['close', 'upgraded'])

const { t } = useI18n()
const notificationStore = useNotificationStore()
const companyStore = useCompanyStore()

const isUpgrading = ref(false)

// Tier configuration (synced with config/subscriptions.php)
const tiers = {
  starter: {
    name: 'Starter',
    price: 12.0,
    paddle_price_id: import.meta.env.VITE_PADDLE_STARTER_PRICE_ID,
  },
  standard: {
    name: 'Standard',
    price: 29.0,
    paddle_price_id: import.meta.env.VITE_PADDLE_STANDARD_PRICE_ID,
  },
  business: {
    name: 'Business',
    price: 59.0,
    paddle_price_id: import.meta.env.VITE_PADDLE_BUSINESS_PRICE_ID,
  },
  max: {
    name: 'Max',
    price: 149.0,
    paddle_price_id: import.meta.env.VITE_PADDLE_MAX_PRICE_ID,
  },
}

const requiredTierName = computed(() => {
  return tiers[props.requiredTier]?.name || props.requiredTier
})

const price = computed(() => {
  return tiers[props.requiredTier]?.price || 0
})

const paddlePriceId = computed(() => {
  return tiers[props.requiredTier]?.paddle_price_id
})

async function handleUpgrade() {
  if (isUpgrading.value) return

  try {
    isUpgrading.value = true

    // Get current company
    const company = companyStore.selectedCompany

    if (!company) {
      throw new Error('No company selected')
    }

    if (!paddlePriceId.value) {
      throw new Error('Invalid tier configuration')
    }

    // Open Paddle checkout
    // Note: Paddle.js must be loaded globally
    if (typeof Paddle === 'undefined') {
      throw new Error('Paddle.js not loaded')
    }

    // Open Paddle checkout overlay
    Paddle.Checkout.open({
      product: paddlePriceId.value,
      email: company.owner_email, // Assuming this exists
      successCallback: (data) => {
        // Success! Paddle will send webhook to backend
        notificationStore.showNotification({
          type: 'success',
          message: t('subscriptions.upgrade_initiated'),
        })

        // Emit event to parent
        emit('upgraded', data)
        emit('close')

        // Optionally: Reload page after a delay to refresh subscription data
        setTimeout(() => {
          window.location.reload()
        }, 2000)
      },
      closeCallback: () => {
        // User closed checkout without completing
        isUpgrading.value = false
      },
    })
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

<style scoped>
/* Add any custom styles if needed */
</style>
