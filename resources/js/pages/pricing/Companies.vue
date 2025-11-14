<template>
  <div class="pricing-page">
    <div class="container mx-auto px-4 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          Choose the Perfect Plan for Your Business
        </h1>
        <p class="text-xl text-gray-600">
          Simple, transparent pricing. Upgrade or downgrade at any time.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
        <!-- Free Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Free</h3>
          <div class="text-3xl font-bold mb-4">€0<span class="text-sm text-gray-500">/mo</span></div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Basic invoicing
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Up to 5 clients
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Email support
            </li>
          </ul>
          <button
            class="w-full py-2 px-4 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition"
            :class="{ 'bg-blue-600 text-white hover:bg-blue-700': currentPlan === 'free' }"
            :disabled="currentPlan === 'free'"
          >
            {{ currentPlan === 'free' ? 'Current Plan' : 'Get Started' }}
          </button>
        </div>

        <!-- Starter Plan -->
        <div class="pricing-card border-2 border-blue-500 rounded-lg p-6 bg-white hover:shadow-lg transition">
          <div class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full inline-block mb-2">
            POPULAR
          </div>
          <h3 class="text-xl font-semibold mb-2">Starter</h3>
          <div class="text-3xl font-bold mb-4">€12<span class="text-sm text-gray-500">/mo</span></div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Unlimited clients
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Estimates & quotes
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Recurring invoices
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Priority support
            </li>
          </ul>
          <button
            @click="subscribe('starter')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'starter' }"
            :disabled="loading || currentPlan === 'starter'"
          >
            {{ currentPlan === 'starter' ? 'Current Plan' : 'Subscribe' }}
          </button>
        </div>

        <!-- Standard Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Standard</h3>
          <div class="text-3xl font-bold mb-4">€29<span class="text-sm text-gray-500">/mo</span></div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Everything in Starter
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Expense tracking
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Financial reports
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Bank sync
            </li>
          </ul>
          <button
            @click="subscribe('standard')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'standard' }"
            :disabled="loading || currentPlan === 'standard'"
          >
            {{ currentPlan === 'standard' ? 'Current Plan' : 'Subscribe' }}
          </button>
        </div>

        <!-- Business Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Business</h3>
          <div class="text-3xl font-bold mb-4">€59<span class="text-sm text-gray-500">/mo</span></div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Everything in Standard
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Multi-currency support
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Custom fields
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Team collaboration
            </li>
          </ul>
          <button
            @click="subscribe('business')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'business' }"
            :disabled="loading || currentPlan === 'business'"
          >
            {{ currentPlan === 'business' ? 'Current Plan' : 'Subscribe' }}
          </button>
        </div>

        <!-- Max Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Max</h3>
          <div class="text-3xl font-bold mb-4">€149<span class="text-sm text-gray-500">/mo</span></div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Everything in Business
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              API access
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              White-label options
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Dedicated support
            </li>
          </ul>
          <button
            @click="subscribe('max')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'max' }"
            :disabled="loading || currentPlan === 'max'"
          >
            {{ currentPlan === 'max' ? 'Current Plan' : 'Subscribe' }}
          </button>
        </div>
      </div>

      <div class="text-center text-sm text-gray-600">
        <p>All plans include 14-day free trial. Cancel anytime.</p>
        <p class="mt-2">Need help choosing? <a href="#" class="text-blue-600 hover:underline">Contact us</a></p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'CompaniesPricing',

  props: {
    companyId: {
      type: Number,
      required: true
    }
  },

  data() {
    return {
      currentPlan: 'free',
      loading: false,
    }
  },

  mounted() {
    this.fetchCurrentPlan()
  },

  methods: {
    async fetchCurrentPlan() {
      try {
        const response = await axios.get(`/api/companies/${this.companyId}/subscription`)
        this.currentPlan = response.data.current_plan?.tier || 'free'
      } catch (error) {
        console.error('Failed to fetch current plan', error)
      }
    },

    async subscribe(tier) {
      if (this.loading) return

      this.loading = true

      try {
        const response = await axios.post(`/api/companies/${this.companyId}/subscription/checkout`, {
          tier
        })

        // Redirect to Paddle checkout
        if (response.data.checkout_url) {
          window.location.href = response.data.checkout_url
        }
      } catch (error) {
        console.error('Failed to create checkout session', error)
        alert('Failed to start subscription process. Please try again.')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.pricing-card {
  transition: all 0.3s ease;
}

.pricing-card:hover {
  transform: translateY(-4px);
}
</style>
<!-- CLAUDE-CHECKPOINT -->
