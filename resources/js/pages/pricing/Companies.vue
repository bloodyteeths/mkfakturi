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

        <!-- Billing Toggle -->
        <div class="flex items-center justify-center mt-8 gap-4">
          <span :class="billingInterval === 'monthly' ? 'text-gray-900 font-semibold' : 'text-gray-500'">
            Месечно
          </span>
          <button
            @click="toggleBillingInterval"
            class="relative inline-flex h-8 w-16 items-center rounded-full transition-colors"
            :class="billingInterval === 'yearly' ? 'bg-blue-600' : 'bg-gray-300'"
          >
            <span
              class="inline-block h-6 w-6 transform rounded-full bg-white shadow transition-transform"
              :class="billingInterval === 'yearly' ? 'translate-x-9' : 'translate-x-1'"
            ></span>
          </button>
          <span :class="billingInterval === 'yearly' ? 'text-gray-900 font-semibold' : 'text-gray-500'">
            Годишно
            <span class="ml-1 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">
              Заштеда 17%
            </span>
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
        <!-- Free Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Free</h3>
          <div class="text-3xl font-bold mb-4">0 ден<span class="text-sm text-gray-500">/мес</span></div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Основно фактурирање
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              До 5 клиенти
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Email поддршка
            </li>
          </ul>
          <button
            class="w-full py-2 px-4 bg-gray-200 text-gray-800 rounded hover:bg-gray-300 transition"
            :class="{ 'bg-blue-600 text-white hover:bg-blue-700': currentPlan === 'free' }"
            :disabled="currentPlan === 'free'"
          >
            {{ currentPlan === 'free' ? 'Актуелен план' : 'Започни' }}
          </button>
        </div>

        <!-- Starter Plan -->
        <div class="pricing-card border-2 border-blue-500 rounded-lg p-6 bg-white hover:shadow-lg transition">
          <div class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full inline-block mb-2">
            ПОПУЛАРЕН
          </div>
          <h3 class="text-xl font-semibold mb-2">Starter</h3>
          <div class="text-3xl font-bold mb-4">
            {{ formatPrice(prices.starter) }} ден
            <span class="text-sm text-gray-500">{{ billingInterval === 'monthly' ? '/мес' : '/год' }}</span>
          </div>
          <div v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mb-2">
            {{ Math.round(prices.starter / 12).toLocaleString() }} ден/мес
          </div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              50 фактури месечно
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Неограничени клиенти
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Понуди и проформи
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Приоритетна поддршка
            </li>
          </ul>
          <button
            @click="subscribe('starter')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'starter' }"
            :disabled="loading || currentPlan === 'starter'"
          >
            {{ currentPlan === 'starter' ? 'Актуелен план' : 'Претплати се' }}
          </button>
        </div>

        <!-- Standard Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Standard</h3>
          <div class="text-3xl font-bold mb-4">
            {{ formatPrice(prices.standard) }} ден
            <span class="text-sm text-gray-500">{{ billingInterval === 'monthly' ? '/мес' : '/год' }}</span>
          </div>
          <div v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mb-2">
            {{ Math.round(prices.standard / 12).toLocaleString() }} ден/мес
          </div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              200 фактури месечно
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              е-Фактура интеграција
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Следење на трошоци
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Финансиски извештаи
            </li>
          </ul>
          <button
            @click="subscribe('standard')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'standard' }"
            :disabled="loading || currentPlan === 'standard'"
          >
            {{ currentPlan === 'standard' ? 'Актуелен план' : 'Претплати се' }}
          </button>
        </div>

        <!-- Business Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Business</h3>
          <div class="text-3xl font-bold mb-4">
            {{ formatPrice(prices.business) }} ден
            <span class="text-sm text-gray-500">{{ billingInterval === 'monthly' ? '/мес' : '/год' }}</span>
          </div>
          <div v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mb-2">
            {{ Math.round(prices.business / 12).toLocaleString() }} ден/мес
          </div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              1000 фактури месечно
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Банкарски фидови
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Повеќе валути
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Тимска соработка
            </li>
          </ul>
          <button
            @click="subscribe('business')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'business' }"
            :disabled="loading || currentPlan === 'business'"
          >
            {{ currentPlan === 'business' ? 'Актуелен план' : 'Претплати се' }}
          </button>
        </div>

        <!-- Max Plan -->
        <div class="pricing-card border rounded-lg p-6 bg-white hover:shadow-lg transition">
          <h3 class="text-xl font-semibold mb-2">Max</h3>
          <div class="text-3xl font-bold mb-4">
            {{ formatPrice(prices.max) }} ден
            <span class="text-sm text-gray-500">{{ billingInterval === 'monthly' ? '/мес' : '/год' }}</span>
          </div>
          <div v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mb-2">
            {{ Math.round(prices.max / 12).toLocaleString() }} ден/мес
          </div>
          <ul class="space-y-2 mb-6 text-sm">
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Неограничено сè
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              API пристап
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              White-label опции
            </li>
            <li class="flex items-start">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
              Посветена поддршка
            </li>
          </ul>
          <button
            @click="subscribe('max')"
            class="w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
            :class="{ 'bg-green-600 hover:bg-green-700': currentPlan === 'max' }"
            :disabled="loading || currentPlan === 'max'"
          >
            {{ currentPlan === 'max' ? 'Актуелен план' : 'Претплати се' }}
          </button>
        </div>
      </div>

      <div class="text-center text-sm text-gray-600">
        <p>Сите планови вклучуваат 14-дневен бесплатен пробен период. Откажете кога сакате.</p>
        <p class="mt-2">Потребна помош? <a href="#" class="text-blue-600 hover:underline">Контактирајте нè</a></p>
      </div>
    </div>
  </div>
</template>

<script>
import Ls from '@/scripts/services/ls.js'

export default {
  name: 'CompaniesPricing',

  props: {
    companyId: {
      type: Number,
      default: null
    }
  },

  data() {
    return {
      currentPlan: 'free',
      loading: false,
      selectedCompanyId: null,
      billingInterval: 'monthly', // 'monthly' or 'yearly'
      // MKD prices
      monthlyPrices: {
        starter: 799,
        standard: 1799,
        business: 3699,
        max: 9199
      },
      yearlyPrices: {
        starter: 7990,
        standard: 17990,
        business: 36990,
        max: 91990
      },
      // Paddle price IDs from environment
      paddlePriceIds: {
        starter: {
          monthly: import.meta.env.VITE_PADDLE_STARTER_PRICE_ID,
          yearly: import.meta.env.VITE_PADDLE_STARTER_YEARLY_PRICE_ID,
        },
        standard: {
          monthly: import.meta.env.VITE_PADDLE_STANDARD_PRICE_ID,
          yearly: import.meta.env.VITE_PADDLE_STANDARD_YEARLY_PRICE_ID,
        },
        business: {
          monthly: import.meta.env.VITE_PADDLE_BUSINESS_PRICE_ID,
          yearly: import.meta.env.VITE_PADDLE_BUSINESS_YEARLY_PRICE_ID,
        },
        max: {
          monthly: import.meta.env.VITE_PADDLE_MAX_PRICE_ID,
          yearly: import.meta.env.VITE_PADDLE_MAX_YEARLY_PRICE_ID,
        },
      }
    }
  },

  computed: {
    prices() {
      return this.billingInterval === 'monthly' ? this.monthlyPrices : this.yearlyPrices
    },

    effectiveCompanyId() {
      return this.companyId || this.selectedCompanyId
    }
  },

  mounted() {
    // Get selected company from localStorage
    const companyId = Ls.get('selectedCompany')
    this.selectedCompanyId = companyId ? parseInt(companyId) : null

    // Try to fetch current plan (will fail silently if not authenticated)
    if (this.effectiveCompanyId) {
      this.fetchCurrentPlan()
    }
  },

  methods: {
    toggleBillingInterval() {
      this.billingInterval = this.billingInterval === 'monthly' ? 'yearly' : 'monthly'
    },

    formatPrice(price) {
      return price.toLocaleString()
    },

    async fetchCurrentPlan() {
      if (!this.effectiveCompanyId) return

      try {
        // Note: axios baseURL is /api/v1, so we use /companies/... not /api/companies/...
        const response = await axios.get(`/companies/${this.effectiveCompanyId}/subscription`)
        this.currentPlan = response.data.current_plan?.tier || 'free'
      } catch (error) {
        // If 401/403, user is not authenticated - that's fine for public pricing page
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.log('User not authenticated, showing default plan')
        } else {
          console.error('Failed to fetch current plan', error)
        }
        // Default to free if we can't fetch the plan
        this.currentPlan = 'free'
      }
    },

    async subscribe(tier) {
      if (this.loading) return

      this.loading = true

      try {
        // If no company selected, try to get from localStorage first
        if (!this.effectiveCompanyId) {
          const companyId = Ls.get('selectedCompany')
          if (companyId) {
            this.selectedCompanyId = parseInt(companyId)
          }
        }

        // If still no company, redirect to login (they need to log in and select a company)
        if (!this.effectiveCompanyId) {
          // Store the selected tier for after login
          Ls.set('pendingSubscription', JSON.stringify({ tier, interval: this.billingInterval }))
          this.$router.push({ name: 'login' })
          return
        }

        // Try server-side checkout
        const response = await axios.post(`/companies/${this.effectiveCompanyId}/subscription/checkout`, {
          tier,
          interval: this.billingInterval
        })

        // Redirect to checkout
        if (response.data.checkout_url) {
          window.location.href = response.data.checkout_url
        }
      } catch (error) {
        console.error('Failed to create checkout session', error)

        // If 401, redirect to login
        if (error.response?.status === 401) {
          Ls.set('pendingSubscription', JSON.stringify({ tier, interval: this.billingInterval }))
          this.$router.push({ name: 'login' })
          return
        }

        // If 403, might be authorization issue - show error
        if (error.response?.status === 403) {
          alert('Немате дозвола за оваа акција. Ве молиме контактирајте го сопственикот на компанијата.')
          this.loading = false
          return
        }

        // Fallback: Try opening Paddle checkout directly
        const priceId = this.paddlePriceIds[tier]?.[this.billingInterval]

        if (typeof Paddle !== 'undefined' && priceId) {
          try {
            Paddle.Checkout.open({
              product: priceId,
              passthrough: JSON.stringify({
                company_id: this.effectiveCompanyId,
                tier: tier,
              }),
              successCallback: () => {
                alert('Претплатата е успешно започната!')
                window.location.reload()
              },
              closeCallback: () => {
                this.loading = false
              },
            })
            return
          } catch (paddleError) {
            console.error('Paddle checkout failed', paddleError)
          }
        }

        alert('Неуспешно започнување на претплата. Обидете се повторно.')
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
