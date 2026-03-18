<template>
  <BasePage>
    <!-- Trial Banner -->
    <div
      v-if="trial.is_trial"
      class="mb-6 rounded-lg p-4 bg-blue-50 border border-blue-200"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="font-semibold text-blue-800">
            {{ t('partner_billing.trial_active') }}
          </p>
          <p class="text-sm text-blue-600">
            {{ t('partner_billing.trial_days_remaining', { days: trial.days_remaining }) }}
          </p>
        </div>
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition"
          @click="scrollToTiers"
        >
          {{ t('partner_billing.choose_plan') }}
        </button>
      </div>
    </div>

    <!-- Hard Blocked Banner -->
    <div
      v-if="trial.is_hard_blocked"
      class="mb-6 rounded-lg p-4 bg-red-50 border border-red-200"
    >
      <p class="font-semibold text-red-800">
        {{ t('partner_billing.trial_expired') }}
      </p>
      <p class="text-sm text-red-600">
        {{ t('partner_billing.trial_expired_message') }}
      </p>
    </div>

    <!-- Current Plan Card -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">
            {{ t('partner_billing.current_plan') }}
          </h2>
          <div class="flex items-center gap-2 mt-1">
            <span class="text-2xl font-bold text-primary-600">
              {{ currentPlan.name }}
            </span>
            <span
              class="px-2 py-0.5 text-xs font-medium rounded-full"
              :class="statusBadgeClass"
            >
              {{ statusLabel }}
            </span>
          </div>
          <p v-if="currentPlan.price_eur > 0" class="text-sm text-gray-500 mt-1">
            {{ currentPlan.price_mkd }} {{ t('partner_billing.mkd_per_month') }}
            ({{ currentPlan.price_eur }}{{ t('partner_billing.eur_per_month') }})
          </p>
        </div>
        <button
          v-if="currentPlan.status === 'active'"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition"
          @click="openBillingPortal"
          :disabled="portalLoading"
        >
          {{ portalLoading ? '...' : t('partner_billing.manage_billing') }}
        </button>
      </div>

      <!-- Seats -->
      <div v-if="currentPlan.seats > 0" class="text-sm text-gray-600 mb-4">
        {{ t('partner_billing.seats_info', { count: currentPlan.seats, price: currentPlan.seat_price_eur }) }}
      </div>

      <!-- Usage Meters -->
      <div class="mt-4 pt-4 border-t">
        <h3 class="text-sm font-medium text-gray-700 mb-3">
          {{ t('partner_billing.usage_this_month') }}
        </h3>
        <UsageMeters :usage="usage" />
      </div>
    </div>

    <!-- Tier Comparison -->
    <div ref="tiersSection" class="bg-white rounded-lg shadow-sm border p-6 mb-6">
      <h2 class="text-lg font-semibold text-gray-900 mb-6">
        {{ t('partner_billing.available_plans') }}
      </h2>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="(tier, key) in tiers"
          :key="key"
          class="border rounded-lg p-5 relative"
          :class="{
            'border-primary-500 ring-2 ring-primary-200': key === currentPlan.tier,
            'border-gray-200': key !== currentPlan.tier,
          }"
        >
          <!-- Current badge -->
          <div
            v-if="key === currentPlan.tier"
            class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-primary-500 text-white text-xs font-medium rounded-full"
          >
            {{ t('partner_billing.current') }}
          </div>

          <!-- Popular badge -->
          <div
            v-if="key === 'office'"
            class="absolute -top-3 right-4 px-3 py-0.5 bg-yellow-500 text-white text-xs font-medium rounded-full"
          >
            {{ t('partner_billing.popular') }}
          </div>

          <h3 class="text-lg font-semibold text-gray-900">{{ tier.name }}</h3>
          <div class="mt-2">
            <span class="text-3xl font-bold text-gray-900">{{ tier.price_mkd }}</span>
            <span class="text-sm text-gray-500"> {{ t('partner_billing.mkd_per_month') }}</span>
          </div>
          <p class="text-sm text-gray-500 mt-1">
            {{ tier.price_eur }}{{ t('partner_billing.eur_per_month') }}
          </p>

          <!-- Limits -->
          <ul class="mt-4 space-y-2 text-sm text-gray-600">
            <li v-for="(limit, meter) in tier.limits" :key="meter" class="flex items-center gap-2">
              <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
              <span>{{ formatLimit(meter, limit) }}</span>
            </li>
          </ul>

          <!-- Support -->
          <p v-if="tier.support_response_hours" class="mt-3 text-xs text-gray-500">
            {{ t('partner_billing.support_response', { hours: tier.support_response_hours }) }}
          </p>

          <!-- Action button -->
          <button
            class="mt-4 w-full py-2 rounded-lg text-sm font-medium transition"
            :class="getButtonClass(key)"
            :disabled="checkoutLoading === key"
            @click="handleTierAction(key)"
          >
            {{ getButtonLabel(key) }}
          </button>
        </div>
      </div>

      <p class="text-center text-sm text-gray-500 mt-4">
        + {{ currentPlan.seat_price_eur }}{{ t('partner_billing.per_seat') }}
      </p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import UsageMeters from './UsageMeters.vue'

const { t } = useI18n()

const loading = ref(true)
const checkoutLoading = ref(null)
const portalLoading = ref(false)
const tiersSection = ref(null)

const currentPlan = ref({
  tier: 'free',
  name: 'Free',
  price_eur: 0,
  price_mkd: 0,
  status: 'none',
  seats: 0,
  seat_price_eur: 5,
})

const trial = ref({
  is_trial: false,
  days_remaining: 0,
  is_expired: false,
  is_hard_blocked: false,
})

const usage = ref({})
const tiers = ref({})

const statusLabel = computed(() => {
  const map = {
    active: t('partner_billing.status_active'),
    trial: t('partner_billing.status_trial'),
    expired: t('partner_billing.status_expired'),
    none: t('partner_billing.status_none'),
  }
  return map[currentPlan.value.status] || currentPlan.value.status
})

const statusBadgeClass = computed(() => {
  const map = {
    active: 'bg-green-100 text-green-800',
    trial: 'bg-blue-100 text-blue-800',
    expired: 'bg-red-100 text-red-800',
    none: 'bg-gray-100 text-gray-800',
  }
  return map[currentPlan.value.status] || 'bg-gray-100 text-gray-800'
})

onMounted(async () => {
  try {
    const { data } = await axios.get('/partner/subscription')
    currentPlan.value = data.current_plan || currentPlan.value
    trial.value = data.trial || trial.value
    usage.value = data.usage || {}
    tiers.value = data.tiers || {}
  } catch (e) {
    console.error('Failed to load billing data', e)
  } finally {
    loading.value = false
  }

  // Handle success redirect
  const params = new URLSearchParams(window.location.search)
  if (params.get('success') === '1') {
    window.history.replaceState({}, '', window.location.pathname)
  }
})

function scrollToTiers() {
  tiersSection.value?.scrollIntoView({ behavior: 'smooth' })
}

async function openBillingPortal() {
  portalLoading.value = true
  try {
    const { data } = await axios.get('/partner/subscription/manage')
    if (data.portal_url) {
      window.location.href = data.portal_url
    }
  } catch (e) {
    console.error('Failed to open billing portal', e)
  } finally {
    portalLoading.value = false
  }
}

async function handleTierAction(tierKey) {
  if (tierKey === currentPlan.value.tier) return

  const isUpgrade = currentPlan.value.status === 'active'

  if (isUpgrade) {
    // Swap plan
    checkoutLoading.value = tierKey
    try {
      await axios.post('/partner/subscription/swap', { tier: tierKey })
      window.location.reload()
    } catch (e) {
      console.error('Plan swap failed', e)
    } finally {
      checkoutLoading.value = null
    }
  } else {
    // New checkout
    checkoutLoading.value = tierKey
    try {
      const { data } = await axios.post('/partner/subscription/checkout', {
        tier: tierKey,
        currency: 'mkd',
      })
      if (data.checkout_url) {
        window.location.href = data.checkout_url
      }
    } catch (e) {
      console.error('Checkout failed', e)
    } finally {
      checkoutLoading.value = null
    }
  }
}

function getButtonClass(tierKey) {
  if (tierKey === currentPlan.value.tier) {
    return 'bg-gray-100 text-gray-500 cursor-not-allowed'
  }
  if (tierKey === 'office') {
    return 'bg-primary-600 text-white hover:bg-primary-700'
  }
  return 'bg-white border border-primary-500 text-primary-600 hover:bg-primary-50'
}

function getButtonLabel(tierKey) {
  if (checkoutLoading.value === tierKey) return '...'
  if (tierKey === currentPlan.value.tier) return t('partner_billing.current_plan')
  if (currentPlan.value.status === 'active') return t('partner_billing.switch_plan')
  return t('partner_billing.start_free_trial')
}

const limitLabels = {
  companies: 'companies',
  ai_credits_per_month: 'AI credits/mo',
  bank_accounts: 'bank accounts',
  payroll_employees: 'employees',
  efaktura_per_month: 'e-Faktura/mo',
  documents_stored_per_month: 'documents/mo',
  client_portal_invites: 'portal invites',
}

function formatLimit(meter, limit) {
  const label = limitLabels[meter] || meter
  if (limit === null) return `Unlimited ${label}`
  return `${limit} ${label}`
}
</script>
