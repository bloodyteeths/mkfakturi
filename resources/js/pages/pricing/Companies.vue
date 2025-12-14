<template>
  <div class="pricing-wrapper">
    <div class="pricing-content">
      <!-- Header -->
      <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">
          Изберете го вашиот план
        </h1>
        <p class="mt-2 text-gray-500">
          Едноставни цени. Без скриени трошоци.
        </p>

        <!-- Billing Toggle -->
        <div class="mt-6 inline-flex bg-gray-100 p-1 rounded-lg">
          <button
            @click="billingInterval = 'monthly'"
            :class="[
              'py-2 px-5 text-sm font-medium rounded-md transition-all',
              billingInterval === 'monthly'
                ? 'bg-white text-gray-900 shadow'
                : 'text-gray-500'
            ]"
          >
            Месечно
          </button>
          <button
            @click="billingInterval = 'yearly'"
            :class="[
              'py-2 px-5 text-sm font-medium rounded-md transition-all relative',
              billingInterval === 'yearly'
                ? 'bg-white text-gray-900 shadow'
                : 'text-gray-500'
            ]"
          >
            Годишно
            <span class="absolute -top-2 -right-1 bg-green-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded">
              -17%
            </span>
          </button>
        </div>
      </div>

      <!-- Pricing Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">

        <!-- Starter Plan -->
        <div
          @click="subscribe('starter')"
          :class="[
            'pricing-card bg-white border-2 rounded-2xl p-6 flex flex-col cursor-pointer transition-all hover:shadow-lg',
            currentPlan === 'starter' ? 'border-gray-300 opacity-60' : 'border-gray-200 hover:border-gray-400'
          ]"
        >
          <h3 class="text-xl font-bold text-gray-900">Starter</h3>
          <p class="text-sm text-gray-500 mt-1">За мали бизниси</p>

          <div class="mt-4">
            <span class="text-4xl font-bold text-gray-900">{{ formatPrice(prices.starter) }}</span>
            <span class="text-gray-500 ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
          </div>
          <p v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mt-1">
            {{ Math.round(prices.starter / 12).toLocaleString() }} ден/мес
          </p>

          <ul class="mt-6 space-y-3 flex-1">
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              50 фактури месечно
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Неограничени клиенти
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Понуди и проформи
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Email поддршка
            </li>
          </ul>

          <div
            :class="[
              'mt-6 py-4 text-center rounded-xl font-semibold text-lg transition-all',
              currentPlan === 'starter'
                ? 'bg-gray-100 text-gray-400'
                : 'bg-gray-900 text-white'
            ]"
          >
            {{ currentPlan === 'starter' ? 'Актуелен план' : 'Избери Starter' }}
          </div>
        </div>

        <!-- Standard Plan -->
        <div
          @click="subscribe('standard')"
          :class="[
            'pricing-card bg-white border-2 rounded-2xl p-6 flex flex-col cursor-pointer transition-all hover:shadow-lg',
            currentPlan === 'standard' ? 'border-gray-300 opacity-60' : 'border-gray-200 hover:border-gray-400'
          ]"
        >
          <h3 class="text-xl font-bold text-gray-900">Standard</h3>
          <p class="text-sm text-gray-500 mt-1">За растечки бизниси</p>

          <div class="mt-4">
            <span class="text-4xl font-bold text-gray-900">{{ formatPrice(prices.standard) }}</span>
            <span class="text-gray-500 ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
          </div>
          <p v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mt-1">
            {{ Math.round(prices.standard / 12).toLocaleString() }} ден/мес
          </p>

          <div class="mt-3 text-xs text-blue-600 font-medium">
            + Сè од Starter
          </div>

          <ul class="mt-4 space-y-3 flex-1">
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              200 фактури месечно
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              е-Фактура интеграција
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Следење на трошоци
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Финансиски извештаи
            </li>
          </ul>

          <div
            :class="[
              'mt-6 py-4 text-center rounded-xl font-semibold text-lg transition-all',
              currentPlan === 'standard'
                ? 'bg-gray-100 text-gray-400'
                : 'bg-gray-900 text-white'
            ]"
          >
            {{ currentPlan === 'standard' ? 'Актуелен план' : 'Избери Standard' }}
          </div>
        </div>

        <!-- Business Plan - RECOMMENDED -->
        <div
          @click="subscribe('business')"
          :class="[
            'pricing-card relative border-2 rounded-2xl p-6 flex flex-col cursor-pointer transition-all hover:shadow-xl',
            currentPlan === 'business'
              ? 'bg-blue-50 border-blue-200 opacity-60'
              : 'bg-blue-50 border-blue-500 hover:border-blue-600'
          ]"
        >
          <div class="absolute -top-3 left-1/2 -translate-x-1/2">
            <span class="bg-blue-600 text-white text-xs font-bold px-4 py-1 rounded-full whitespace-nowrap">
              ПРЕПОРАЧАНО
            </span>
          </div>

          <h3 class="text-xl font-bold text-gray-900 mt-2">Business</h3>
          <p class="text-sm text-gray-500 mt-1">За компании</p>

          <div class="mt-4">
            <span class="text-4xl font-bold text-gray-900">{{ formatPrice(prices.business) }}</span>
            <span class="text-gray-500 ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
          </div>
          <p v-if="billingInterval === 'yearly'" class="text-sm text-green-600 mt-1">
            {{ Math.round(prices.business / 12).toLocaleString() }} ден/мес
          </p>

          <div class="mt-3 text-xs text-blue-600 font-medium">
            + Сè од Standard
          </div>

          <ul class="mt-4 space-y-3 flex-1">
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              1,000 фактури месечно
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Банкарски поврзувања
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              До 5 корисници
            </li>
            <li class="flex items-center text-gray-700">
              <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Напредни AI функции
            </li>
          </ul>

          <div
            :class="[
              'mt-6 py-4 text-center rounded-xl font-semibold text-lg transition-all',
              currentPlan === 'business'
                ? 'bg-gray-200 text-gray-400'
                : 'bg-blue-600 text-white'
            ]"
          >
            {{ currentPlan === 'business' ? 'Актуелен план' : 'Избери Business' }}
          </div>
        </div>

        <!-- Max Plan -->
        <div
          @click="subscribe('max')"
          :class="[
            'pricing-card bg-gray-900 border-2 border-gray-900 rounded-2xl p-6 flex flex-col cursor-pointer transition-all hover:shadow-xl hover:bg-gray-800',
            currentPlan === 'max' ? 'opacity-60' : ''
          ]"
        >
          <h3 class="text-xl font-bold text-white">Max</h3>
          <p class="text-sm text-gray-400 mt-1">Ентерпрајз</p>

          <div class="mt-4">
            <span class="text-4xl font-bold text-white">{{ formatPrice(prices.max) }}</span>
            <span class="text-gray-400 ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
          </div>
          <p v-if="billingInterval === 'yearly'" class="text-sm text-green-400 mt-1">
            {{ Math.round(prices.max / 12).toLocaleString() }} ден/мес
          </p>

          <div class="mt-3 text-xs text-yellow-400 font-medium">
            + Сè од Business
          </div>

          <ul class="mt-4 space-y-3 flex-1">
            <li class="flex items-center text-white">
              <svg class="w-5 h-5 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Неограничени фактури
            </li>
            <li class="flex items-center text-white">
              <svg class="w-5 h-5 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Неограничени корисници
            </li>
            <li class="flex items-center text-white">
              <svg class="w-5 h-5 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              API пристап
            </li>
            <li class="flex items-center text-white">
              <svg class="w-5 h-5 text-green-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              Посветена поддршка 24/7
            </li>
          </ul>

          <div
            :class="[
              'mt-6 py-4 text-center rounded-xl font-semibold text-lg transition-all',
              currentPlan === 'max'
                ? 'bg-gray-700 text-gray-400'
                : 'bg-white text-gray-900'
            ]"
          >
            {{ currentPlan === 'max' ? 'Актуелен план' : 'Избери Max' }}
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-10 text-center pb-8">
        <p class="text-gray-500">
          14-дневен бесплатен пробен период. Откажете кога сакате.
        </p>
        <p class="mt-2 text-sm text-gray-400">
          Прашања? <a href="mailto:support@facturino.mk" class="text-blue-600 font-medium">support@facturino.mk</a>
        </p>

        <!-- Trust badges -->
        <div class="mt-6 flex flex-wrap justify-center gap-6 text-gray-400 text-sm">
          <span class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            SSL
          </span>
          <span class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            Stripe
          </span>
          <span class="flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Откажи кога сакаш
          </span>
        </div>
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
      billingInterval: 'monthly',
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
    const companyId = Ls.get('selectedCompany')
    this.selectedCompanyId = companyId ? parseInt(companyId) : null

    if (this.effectiveCompanyId) {
      this.fetchCurrentPlan()
    }
  },

  methods: {
    formatPrice(price) {
      return price.toLocaleString()
    },

    async fetchCurrentPlan() {
      if (!this.effectiveCompanyId) return

      try {
        const response = await axios.get(`/companies/${this.effectiveCompanyId}/subscription`)
        this.currentPlan = response.data.current_plan?.tier || 'free'
      } catch (error) {
        if (error.response?.status === 401 || error.response?.status === 403) {
          console.log('User not authenticated, showing default plan')
        } else {
          console.error('Failed to fetch current plan', error)
        }
        this.currentPlan = 'free'
      }
    },

    async subscribe(tier) {
      if (this.loading) return
      if (this.currentPlan === tier) return

      this.loading = true

      try {
        if (!this.effectiveCompanyId) {
          const companyId = Ls.get('selectedCompany')
          if (companyId) {
            this.selectedCompanyId = parseInt(companyId)
          }
        }

        if (!this.effectiveCompanyId) {
          Ls.set('pendingSubscription', JSON.stringify({ tier, interval: this.billingInterval }))
          this.$router.push({ name: 'login' })
          return
        }

        const response = await axios.post(`/companies/${this.effectiveCompanyId}/subscription/checkout`, {
          tier,
          interval: this.billingInterval
        })

        if (response.data.checkout_url) {
          window.location.href = response.data.checkout_url
        }
      } catch (error) {
        console.error('Failed to create checkout session', error)

        if (error.response?.status === 401) {
          Ls.set('pendingSubscription', JSON.stringify({ tier, interval: this.billingInterval }))
          this.$router.push({ name: 'login' })
          return
        }

        if (error.response?.status === 403) {
          alert('Немате дозвола за оваа акција.')
          this.loading = false
          return
        }

        const errorMessage = error.response?.data?.error || 'Грешка. Обидете се повторно.'
        alert(errorMessage)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.pricing-wrapper {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  overflow-y: scroll;
  overflow-x: hidden;
  background: #f9fafb;
  z-index: 50;
}

.pricing-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem 1rem;
}

.pricing-card {
  min-height: 420px;
}

@media (max-width: 640px) {
  .pricing-content {
    padding: 1rem 0.75rem;
  }

  .pricing-card {
    min-height: auto;
  }
}
</style>
<!-- CLAUDE-CHECKPOINT -->
