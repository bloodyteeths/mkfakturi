<template>
  <BasePage>
    <BasePageHeader title="Choose Your Plan">
      <template #actions>
        <BaseButton
          v-if="currentTier && currentTier !== 'free'"
          variant="white"
          @click="openBillingPortal"
        >
          Manage Subscription
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center items-center py-12">
      <BaseSpinner class="h-8 w-8" />
      <p class="ml-3 text-gray-600">Loading plans...</p>
    </div>

    <!-- Error State -->
    <BaseAlert v-if="error" type="danger" class="mb-6">
      {{ error }}
    </BaseAlert>

    <!-- Plan Cards -->
    <div v-if="!isLoading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <!-- Starter Plan -->
      <BaseCard
        class="relative overflow-hidden hover:shadow-xl transition-shadow duration-300"
        :class="{ 'ring-2 ring-primary-500': isCurrentPlan('starter') }"
      >
        <div class="p-6">
          <div class="mb-4">
            <h3 class="text-2xl font-bold text-gray-900">Starter</h3>
            <div class="mt-2 flex items-baseline">
              <span class="text-4xl font-extrabold text-gray-900">€12</span>
              <span class="ml-1 text-xl text-gray-500">/month</span>
            </div>
          </div>

          <BaseBadge v-if="isCurrentPlan('starter')" variant="success" class="mb-4">
            Current Plan
          </BaseBadge>

          <ul class="space-y-3 mb-6">
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Up to 30 invoices/month</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">e-Invoice (5/month)</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Recurring invoices</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">5 AI questions/month</span>
            </li>
          </ul>

          <BaseButton
            :disabled="isCurrentPlan('starter') || isProcessing"
            :variant="isCurrentPlan('starter') ? 'white' : 'primary'"
            class="w-full"
            @click="handleSubscribe('starter')"
          >
            {{ isCurrentPlan('starter') ? 'Current Plan' : 'Subscribe' }}
          </BaseButton>
        </div>
      </BaseCard>

      <!-- Standard Plan -->
      <BaseCard
        class="relative overflow-hidden hover:shadow-xl transition-shadow duration-300 border-2 border-primary-500"
        :class="{ 'ring-2 ring-primary-500': isCurrentPlan('standard') }"
      >
        <div class="absolute top-0 right-0 bg-primary-500 text-white px-3 py-1 text-xs font-semibold rounded-bl-lg">
          Popular
        </div>
        <div class="p-6">
          <div class="mb-4">
            <h3 class="text-2xl font-bold text-gray-900">Standard</h3>
            <div class="mt-2 flex items-baseline">
              <span class="text-4xl font-extrabold text-gray-900">€29</span>
              <span class="ml-1 text-xl text-gray-500">/month</span>
            </div>
          </div>

          <BaseBadge v-if="isCurrentPlan('standard')" variant="success" class="mb-4">
            Current Plan
          </BaseBadge>

          <ul class="space-y-3 mb-6">
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Up to 200 invoices/month</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">3 users</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Unlimited e-Invoice + QES signing</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Bank feed integration (PSD2)</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">25 AI questions/month</span>
            </li>
          </ul>

          <BaseButton
            :disabled="isCurrentPlan('standard') || isProcessing"
            :variant="isCurrentPlan('standard') ? 'white' : 'primary'"
            class="w-full"
            @click="handleSubscribe('standard')"
          >
            {{ isCurrentPlan('standard') ? 'Current Plan' : 'Subscribe' }}
          </BaseButton>
        </div>
      </BaseCard>

      <!-- Business Plan -->
      <BaseCard
        class="relative overflow-hidden hover:shadow-xl transition-shadow duration-300"
        :class="{ 'ring-2 ring-primary-500': isCurrentPlan('business') }"
      >
        <div class="p-6">
          <div class="mb-4">
            <h3 class="text-2xl font-bold text-gray-900">Business</h3>
            <div class="mt-2 flex items-baseline">
              <span class="text-4xl font-extrabold text-gray-900">€59</span>
              <span class="ml-1 text-xl text-gray-500">/month</span>
            </div>
          </div>

          <BaseBadge v-if="isCurrentPlan('business')" variant="success" class="mb-4">
            Current Plan
          </BaseBadge>

          <ul class="space-y-3 mb-6">
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Up to 1,000 invoices/month</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">5 users</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Multi-currency</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">API access</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">50 AI questions/month</span>
            </li>
          </ul>

          <BaseButton
            :disabled="isCurrentPlan('business') || isProcessing"
            :variant="isCurrentPlan('business') ? 'white' : 'primary'"
            class="w-full"
            @click="handleSubscribe('business')"
          >
            {{ isCurrentPlan('business') ? 'Current Plan' : 'Subscribe' }}
          </BaseButton>
        </div>
      </BaseCard>
    </div>

    <!-- Additional Info -->
    <div class="bg-gray-50 rounded-lg p-6 text-center">
      <p class="text-sm text-gray-600">
        All plans include a 14-day free trial. No credit card required to start.
      </p>
      <p class="text-sm text-gray-600 mt-2">
        Prices are in EUR and billed monthly. Pay by card or bank transfer. Cancel anytime.
      </p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { CheckIcon } from '@heroicons/vue/24/solid'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import axios from 'axios'

// Import base components
import BasePage from '@/scripts/components/base/BasePage.vue'
import BasePageHeader from '@/scripts/components/base/BasePageHeader.vue'
import BaseCard from '@/scripts/components/base/BaseCard.vue'
import BaseButton from '@/scripts/components/base/BaseButton.vue'
import BaseBadge from '@/scripts/components/base/BaseBadge.vue'
import BaseAlert from '@/scripts/components/base/BaseAlert.vue'
import BaseSpinner from '@/scripts/components/base/BaseSpinner.vue'

const router = useRouter()
const companyStore = useCompanyStore()
const isLoading = ref(true)
const isProcessing = ref(false)
const error = ref(null)
const currentTier = ref('free')

// Get company ID from store
const getCompanyId = () => {
  return companyStore.selectedCompany?.id || window.Ls.get('selectedCompany')
}

// Fetch current subscription from Stripe endpoint
const fetchCurrentSubscription = async () => {
  try {
    const companyId = getCompanyId()
    if (!companyId) {
      isLoading.value = false
      return
    }

    const response = await axios.get(`/api/v1/companies/${companyId}/subscription`)
    currentTier.value = response.data.current_plan?.tier || 'free'
  } catch (err) {
    // User might not have a subscription yet
    if (err.response?.status !== 404) {
      console.error('Failed to fetch subscription:', err)
    }
  } finally {
    isLoading.value = false
  }
}

// Check if plan is current
const isCurrentPlan = (plan) => {
  return currentTier.value === plan
}

// Handle subscribe — redirect to Stripe Checkout
const handleSubscribe = async (plan) => {
  if (isProcessing.value) return

  isProcessing.value = true
  error.value = null

  try {
    const companyId = getCompanyId()
    if (!companyId) {
      throw new Error('No company selected')
    }

    const response = await axios.post(
      `/api/v1/companies/${companyId}/subscription/checkout`,
      { tier: plan, interval: 'monthly' }
    )

    // Stripe returns a checkout_url — redirect to hosted checkout
    window.location.href = response.data.checkout_url
  } catch (err) {
    console.error('Checkout error:', err)
    error.value = err.response?.data?.error || 'Failed to start checkout. Please try again.'
    isProcessing.value = false
  }
}

// Open Stripe Billing Portal for managing subscription
const openBillingPortal = async () => {
  try {
    const companyId = getCompanyId()
    const response = await axios.get(`/api/v1/companies/${companyId}/subscription/manage`)
    window.location.href = response.data.portal_url
  } catch (err) {
    console.error('Billing portal error:', err)
    error.value = 'Failed to open billing portal.'
  }
}

onMounted(async () => {
  await fetchCurrentSubscription()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
