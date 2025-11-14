<template>
  <div class="paddle-checkout">
    <!-- Header -->
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Choose Your Plan</h1>
      <p class="text-xl text-gray-600">Start with a 14-day free trial. Cancel anytime.</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
      <p class="text-red-800">{{ error }}</p>
    </div>

    <!-- Plan Cards -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      <!-- Starter Plan -->
      <div class="plan-card" :class="{ 'border-blue-500': currentPlan === 'starter' }">
        <div class="p-8">
          <div v-if="currentPlan === 'starter'" class="text-xs font-semibold text-blue-600 mb-2">
            CURRENT PLAN
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">Starter</h3>
          <div class="flex items-baseline mb-6">
            <span class="text-5xl font-extrabold text-gray-900">€12</span>
            <span class="text-gray-600 ml-2">/month</span>
          </div>

          <ul class="space-y-4 mb-8">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Unlimited invoices</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Bank feed integration</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">1 company</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Basic support</span>
            </li>
          </ul>

          <button
            @click="subscribeToPlan('starter')"
            :disabled="currentPlan === 'starter' || subscribing"
            class="w-full py-3 px-6 rounded-lg font-semibold transition-colors"
            :class="currentPlan === 'starter'
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-blue-600 text-white hover:bg-blue-700'"
          >
            {{ currentPlan === 'starter' ? 'Current Plan' : 'Start Free Trial' }}
          </button>
        </div>
      </div>

      <!-- Professional Plan (Popular) -->
      <div class="plan-card popular" :class="{ 'border-blue-500': currentPlan === 'professional' }">
        <div class="popular-badge">POPULAR</div>
        <div class="p-8">
          <div v-if="currentPlan === 'professional'" class="text-xs font-semibold text-blue-600 mb-2">
            CURRENT PLAN
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">Professional</h3>
          <div class="flex items-baseline mb-6">
            <span class="text-5xl font-extrabold text-gray-900">€29</span>
            <span class="text-gray-600 ml-2">/month</span>
          </div>

          <ul class="space-y-4 mb-8">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Everything in Starter</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">3 companies</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">QES e-invoices</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">CASYS payments</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Priority support</span>
            </li>
          </ul>

          <button
            @click="subscribeToPlan('professional')"
            :disabled="currentPlan === 'professional' || subscribing"
            class="w-full py-3 px-6 rounded-lg font-semibold transition-colors"
            :class="currentPlan === 'professional'
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-blue-600 text-white hover:bg-blue-700'"
          >
            {{ currentPlan === 'professional' ? 'Current Plan' : 'Start Free Trial' }}
          </button>
        </div>
      </div>

      <!-- Business Plan -->
      <div class="plan-card" :class="{ 'border-blue-500': currentPlan === 'business' }">
        <div class="p-8">
          <div v-if="currentPlan === 'business'" class="text-xs font-semibold text-blue-600 mb-2">
            CURRENT PLAN
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-2">Business</h3>
          <div class="flex items-baseline mb-6">
            <span class="text-5xl font-extrabold text-gray-900">€59</span>
            <span class="text-gray-600 ml-2">/month</span>
          </div>

          <ul class="space-y-4 mb-8">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Everything in Professional</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">10 companies</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Multi-user access</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">API access</span>
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700">Dedicated support</span>
            </li>
          </ul>

          <button
            @click="subscribeToPlan('business')"
            :disabled="currentPlan === 'business' || subscribing"
            class="w-full py-3 px-6 rounded-lg font-semibold transition-colors"
            :class="currentPlan === 'business'
              ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
              : 'bg-blue-600 text-white hover:bg-blue-700'"
          >
            {{ currentPlan === 'business' ? 'Current Plan' : 'Start Free Trial' }}
          </button>
        </div>
      </div>
    </div>

    <!-- FAQ Section -->
    <div class="mt-16 max-w-3xl mx-auto">
      <h2 class="text-2xl font-bold text-gray-900 mb-6 text-center">Frequently Asked Questions</h2>
      <div class="space-y-4">
        <div class="bg-white rounded-lg border p-6">
          <h3 class="font-semibold text-gray-900 mb-2">Can I change plans later?</h3>
          <p class="text-gray-600">Yes, you can upgrade or downgrade anytime. Changes take effect immediately with prorated billing.</p>
        </div>
        <div class="bg-white rounded-lg border p-6">
          <h3 class="font-semibold text-gray-900 mb-2">What payment methods do you accept?</h3>
          <p class="text-gray-600">We accept all major credit cards, PayPal, and bank transfers via Paddle.</p>
        </div>
        <div class="bg-white rounded-lg border p-6">
          <h3 class="font-semibold text-gray-900 mb-2">Is there a setup fee?</h3>
          <p class="text-gray-600">No setup fees. Just pay the monthly subscription. Cancel anytime.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'PaddleCheckout',
  setup() {
    const router = useRouter()
    const loading = ref(true)
    const subscribing = ref(false)
    const error = ref(null)
    const currentPlan = ref(null)
    const paddleLoaded = ref(false)

    // Load Paddle.js SDK
    const loadPaddleJS = () => {
      return new Promise((resolve, reject) => {
        if (window.Paddle) {
          resolve()
          return
        }

        const script = document.createElement('script')
        script.src = 'https://cdn.paddle.com/paddle/v2/paddle.js'
        script.onload = () => {
          const token = import.meta.env.VITE_PADDLE_CLIENT_TOKEN
          const sandbox = import.meta.env.VITE_PADDLE_SANDBOX === 'true'

          if (window.Paddle) {
            window.Paddle.Initialize({
              token: token,
              environment: sandbox ? 'sandbox' : 'production'
            })
            paddleLoaded.value = true
            resolve()
          } else {
            reject(new Error('Paddle failed to load'))
          }
        }
        script.onerror = () => reject(new Error('Failed to load Paddle SDK'))
        document.head.appendChild(script)
      })
    }

    // Fetch current subscription
    const fetchCurrentSubscription = async () => {
      try {
        const response = await fetch('/api/billing/subscription', {
          headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          credentials: 'include'
        })

        if (response.ok) {
          const data = await response.json()
          if (data.subscription && data.subscription.status === 'active') {
            currentPlan.value = data.subscription.plan
          }
        }
      } catch (err) {
        console.error('Failed to fetch subscription:', err)
      } finally {
        loading.value = false
      }
    }

    // Subscribe to plan
    const subscribeToPlan = async (plan) => {
      if (subscribing.value || currentPlan.value === plan) return

      subscribing.value = true
      error.value = null

      try {
        // Get checkout URL from backend
        const response = await fetch('/api/billing/checkout', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
          },
          credentials: 'include',
          body: JSON.stringify({ plan })
        })

        if (!response.ok) {
          const errorData = await response.json()
          throw new Error(errorData.message || 'Failed to create checkout')
        }

        const data = await response.json()

        // Open Paddle checkout
        if (window.Paddle && paddleLoaded.value) {
          window.Paddle.Checkout.open({
            items: [{ priceId: data.price_id, quantity: 1 }],
            customData: {
              user_id: data.user_id,
              plan: plan
            },
            successCallback: () => {
              router.push('/billing/success')
            },
            closeCallback: () => {
              subscribing.value = false
            }
          })
        } else {
          throw new Error('Paddle SDK not loaded')
        }
      } catch (err) {
        error.value = err.message || 'Failed to start checkout. Please try again.'
        subscribing.value = false
      }
    }

    onMounted(async () => {
      try {
        await loadPaddleJS()
        await fetchCurrentSubscription()
      } catch (err) {
        error.value = 'Failed to initialize checkout. Please refresh the page.'
        loading.value = false
      }
    })

    return {
      loading,
      subscribing,
      error,
      currentPlan,
      subscribeToPlan
    }
  }
}
</script>

<style scoped>
.paddle-checkout {
  @apply py-12 px-4;
}

.plan-card {
  @apply bg-white rounded-lg border-2 border-gray-200 shadow-sm transition-all hover:shadow-lg relative;
}

.plan-card.popular {
  @apply border-blue-500 shadow-lg;
  transform: scale(1.05);
}

.popular-badge {
  @apply absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-1/2
         bg-blue-600 text-white text-xs font-bold px-4 py-1 rounded-full;
}
</style>
// CLAUDE-CHECKPOINT
