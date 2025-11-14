<template>
  <BasePage>
    <BasePageHeader title="Choose Your Plan">
      <template #actions>
        <BaseButton
          v-if="currentSubscription"
          variant="white"
          @click="$router.push({ name: 'billing.index' })"
        >
          View Current Subscription
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
              <span class="text-sm text-gray-700">Up to 100 invoices/month</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Basic reporting</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Email support</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">1 company</span>
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

      <!-- Professional Plan -->
      <BaseCard
        class="relative overflow-hidden hover:shadow-xl transition-shadow duration-300 border-2 border-primary-500"
        :class="{ 'ring-2 ring-primary-500': isCurrentPlan('professional') }"
      >
        <div class="absolute top-0 right-0 bg-primary-500 text-white px-3 py-1 text-xs font-semibold rounded-bl-lg">
          Popular
        </div>
        <div class="p-6">
          <div class="mb-4">
            <h3 class="text-2xl font-bold text-gray-900">Professional</h3>
            <div class="mt-2 flex items-baseline">
              <span class="text-4xl font-extrabold text-gray-900">€29</span>
              <span class="ml-1 text-xl text-gray-500">/month</span>
            </div>
          </div>

          <BaseBadge v-if="isCurrentPlan('professional')" variant="success" class="mb-4">
            Current Plan
          </BaseBadge>

          <ul class="space-y-3 mb-6">
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Unlimited invoices</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Advanced reporting & analytics</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Priority support</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">3 companies</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Bank feed integration</span>
            </li>
          </ul>

          <BaseButton
            :disabled="isCurrentPlan('professional') || isProcessing"
            :variant="isCurrentPlan('professional') ? 'white' : 'primary'"
            class="w-full"
            @click="handleSubscribe('professional')"
          >
            {{ isCurrentPlan('professional') ? 'Current Plan' : 'Subscribe' }}
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
              <span class="text-sm text-gray-700">Everything in Professional</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Unlimited companies</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">QES e-Invoice signing</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">API access</span>
            </li>
            <li class="flex items-start">
              <CheckIcon class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" />
              <span class="text-sm text-gray-700">Dedicated account manager</span>
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
        Prices are in EUR and billed monthly. Cancel anytime.
      </p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { CheckIcon } from '@heroicons/vue/24/solid'
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
const isLoading = ref(true)
const isProcessing = ref(false)
const error = ref(null)
const currentSubscription = ref(null)
const paddleInstance = ref(null)

// Paddle configuration from environment
const paddleClientToken = import.meta.env.VITE_PADDLE_CLIENT_TOKEN
const paddleSandbox = import.meta.env.VITE_PADDLE_SANDBOX === 'true'

// Initialize Paddle.js
const initializePaddle = async () => {
  try {
    // Load Paddle.js SDK dynamically
    if (!window.Paddle) {
      const script = document.createElement('script')
      script.src = paddleSandbox
        ? 'https://cdn.paddle.com/paddle/v2/paddle.js'
        : 'https://cdn.paddle.com/paddle/v2/paddle.js'
      script.async = true

      await new Promise((resolve, reject) => {
        script.onload = resolve
        script.onerror = reject
        document.head.appendChild(script)
      })
    }

    // Initialize Paddle
    if (window.Paddle) {
      window.Paddle.Environment.set(paddleSandbox ? 'sandbox' : 'production')
      window.Paddle.Initialize({
        token: paddleClientToken,
      })
      paddleInstance.value = window.Paddle
    }
  } catch (err) {
    console.error('Failed to initialize Paddle:', err)
    error.value = 'Failed to load payment system. Please refresh the page.'
  }
}

// Fetch current subscription
const fetchCurrentSubscription = async () => {
  try {
    const response = await axios.get('/api/billing/subscription')
    currentSubscription.value = response.data.data
  } catch (err) {
    // User might not have a subscription yet, which is fine
    if (err.response?.status !== 404) {
      console.error('Failed to fetch subscription:', err)
    }
  } finally {
    isLoading.value = false
  }
}

// Check if plan is current
const isCurrentPlan = (plan) => {
  return currentSubscription.value?.plan_name?.toLowerCase() === plan.toLowerCase()
}

// Handle subscribe button click
const handleSubscribe = async (plan) => {
  if (isProcessing.value) return

  isProcessing.value = true
  error.value = null

  try {
    // Call backend to create checkout session
    const response = await axios.post('/api/billing/checkout', { plan })
    const { checkout_url } = response.data

    if (!paddleInstance.value) {
      throw new Error('Paddle is not initialized')
    }

    // Open Paddle checkout overlay
    paddleInstance.value.Checkout.open({
      transactionId: checkout_url,
      successCallback: (data) => {
        // Redirect to success page
        router.push({ name: 'billing.success' })
      },
      closeCallback: () => {
        isProcessing.value = false
      },
    })
  } catch (err) {
    console.error('Checkout error:', err)
    error.value = err.response?.data?.message || 'Failed to start checkout. Please try again.'
    isProcessing.value = false
  }
}

onMounted(async () => {
  await initializePaddle()
  await fetchCurrentSubscription()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
