<template>
  <div class="pricing-page">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12 lg:py-16">
      <!-- Header -->
      <div class="text-center max-w-3xl mx-auto mb-8 sm:mb-12">
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-gray-900 tracking-tight">
          Изберете го вашиот план
        </h1>
        <p class="mt-3 text-base sm:text-lg text-gray-500">
          Едноставни и транспарентни цени. Без скриени трошоци.
        </p>

        <!-- Billing Toggle -->
        <div class="mt-6 sm:mt-8 flex items-center justify-center">
          <div class="relative bg-gray-100 p-1 rounded-xl flex">
            <button
              @click="billingInterval = 'monthly'"
              :class="[
                'relative py-2 px-4 sm:py-2.5 sm:px-6 text-sm font-medium rounded-lg transition-all duration-200',
                billingInterval === 'monthly'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-500 hover:text-gray-900'
              ]"
            >
              Месечно
            </button>
            <button
              @click="billingInterval = 'yearly'"
              :class="[
                'relative py-2 px-4 sm:py-2.5 sm:px-6 text-sm font-medium rounded-lg transition-all duration-200',
                billingInterval === 'yearly'
                  ? 'bg-white text-gray-900 shadow-sm'
                  : 'text-gray-500 hover:text-gray-900'
              ]"
            >
              Годишно
              <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs font-bold px-1.5 py-0.5 rounded-full">
                -17%
              </span>
            </button>
          </div>
        </div>
      </div>

      <!-- Pricing Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
        <!-- Starter Plan -->
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col">
          <div class="p-5 sm:p-6 flex-1">
            <h3 class="text-lg font-semibold text-gray-900">Starter</h3>
            <p class="mt-1 text-sm text-gray-500">За мали бизниси</p>

            <div class="mt-4">
              <span class="text-3xl sm:text-4xl font-bold text-gray-900">{{ formatPrice(prices.starter) }}</span>
              <span class="text-gray-500 text-sm ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
            </div>
            <p v-if="billingInterval === 'yearly'" class="mt-1 text-xs text-green-600">
              {{ Math.round(prices.starter / 12).toLocaleString() }} ден месечно
            </p>

            <ul class="mt-5 space-y-3 text-sm">
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">50 фактури месечно</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Неограничени клиенти</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Понуди и проформи</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Email поддршка</span>
              </li>
            </ul>
          </div>

          <div class="p-5 sm:p-6 pt-0">
            <button
              @click="subscribe('starter')"
              :disabled="loading || currentPlan === 'starter'"
              :class="[
                'w-full py-2.5 px-4 rounded-xl text-sm font-medium transition-all duration-200',
                currentPlan === 'starter'
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-gray-900 text-white hover:bg-gray-800'
              ]"
            >
              {{ currentPlan === 'starter' ? 'Актуелен план' : 'Започни пробен период' }}
            </button>
          </div>
        </div>

        <!-- Standard Plan - Most Popular -->
        <div class="relative bg-white rounded-2xl shadow-xl border-2 border-blue-500 flex flex-col order-first sm:order-none lg:order-none">
          <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-10">
            <span class="bg-blue-500 text-white text-xs font-semibold px-3 py-1 rounded-full whitespace-nowrap">
              Најпопуларен
            </span>
          </div>

          <div class="p-5 sm:p-6 pt-6 flex-1">
            <h3 class="text-lg font-semibold text-gray-900">Standard</h3>
            <p class="mt-1 text-sm text-gray-500">За растечки бизниси</p>

            <div class="mt-4">
              <span class="text-3xl sm:text-4xl font-bold text-gray-900">{{ formatPrice(prices.standard) }}</span>
              <span class="text-gray-500 text-sm ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
            </div>
            <p v-if="billingInterval === 'yearly'" class="mt-1 text-xs text-green-600">
              {{ Math.round(prices.standard / 12).toLocaleString() }} ден месечно
            </p>

            <!-- Includes badge -->
            <div class="mt-4 inline-flex items-center text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              Вклучува сè од Starter
            </div>

            <ul class="mt-4 space-y-3 text-sm">
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">200 фактури месечно</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">е-Фактура интеграција</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Следење на трошоци</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Финансиски извештаи</span>
              </li>
            </ul>
          </div>

          <div class="p-5 sm:p-6 pt-0">
            <button
              @click="subscribe('standard')"
              :disabled="loading || currentPlan === 'standard'"
              :class="[
                'w-full py-2.5 px-4 rounded-xl text-sm font-medium transition-all duration-200',
                currentPlan === 'standard'
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-blue-600 text-white hover:bg-blue-700'
              ]"
            >
              {{ currentPlan === 'standard' ? 'Актуелен план' : 'Започни пробен период' }}
            </button>
          </div>
        </div>

        <!-- Business Plan -->
        <div class="relative bg-white rounded-2xl shadow-sm border border-gray-200 flex flex-col">
          <div class="p-5 sm:p-6 flex-1">
            <h3 class="text-lg font-semibold text-gray-900">Business</h3>
            <p class="mt-1 text-sm text-gray-500">За компании</p>

            <div class="mt-4">
              <span class="text-3xl sm:text-4xl font-bold text-gray-900">{{ formatPrice(prices.business) }}</span>
              <span class="text-gray-500 text-sm ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
            </div>
            <p v-if="billingInterval === 'yearly'" class="mt-1 text-xs text-green-600">
              {{ Math.round(prices.business / 12).toLocaleString() }} ден месечно
            </p>

            <!-- Includes badge -->
            <div class="mt-4 inline-flex items-center text-xs text-purple-600 bg-purple-50 px-2 py-1 rounded-full">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              Вклучува сè од Standard
            </div>

            <ul class="mt-4 space-y-3 text-sm">
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">1,000 фактури месечно</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Банкарски поврзувања</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">До 5 корисници</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-600">Напредни AI функции</span>
              </li>
            </ul>
          </div>

          <div class="p-5 sm:p-6 pt-0">
            <button
              @click="subscribe('business')"
              :disabled="loading || currentPlan === 'business'"
              :class="[
                'w-full py-2.5 px-4 rounded-xl text-sm font-medium transition-all duration-200',
                currentPlan === 'business'
                  ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                  : 'bg-gray-900 text-white hover:bg-gray-800'
              ]"
            >
              {{ currentPlan === 'business' ? 'Актуелен план' : 'Започни пробен период' }}
            </button>
          </div>
        </div>

        <!-- Max Plan -->
        <div class="relative bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-xl flex flex-col text-white">
          <div class="p-5 sm:p-6 flex-1">
            <h3 class="text-lg font-semibold">Max</h3>
            <p class="mt-1 text-sm text-gray-300">Ентерпрајз</p>

            <div class="mt-4">
              <span class="text-3xl sm:text-4xl font-bold">{{ formatPrice(prices.max) }}</span>
              <span class="text-gray-300 text-sm ml-1">ден/{{ billingInterval === 'monthly' ? 'мес' : 'год' }}</span>
            </div>
            <p v-if="billingInterval === 'yearly'" class="mt-1 text-xs text-green-400">
              {{ Math.round(prices.max / 12).toLocaleString() }} ден месечно
            </p>

            <!-- Includes badge -->
            <div class="mt-4 inline-flex items-center text-xs text-amber-300 bg-amber-900/30 px-2 py-1 rounded-full">
              <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              Вклучува сè од Business
            </div>

            <ul class="mt-4 space-y-3 text-sm">
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-200">Неограничени фактури</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-200">Неограничени корисници</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-200">API пристап</span>
              </li>
              <li class="flex items-start">
                <svg class="w-4 h-4 text-green-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="ml-2 text-gray-200">Посветена поддршка 24/7</span>
              </li>
            </ul>
          </div>

          <div class="p-5 sm:p-6 pt-0">
            <button
              @click="subscribe('max')"
              :disabled="loading || currentPlan === 'max'"
              :class="[
                'w-full py-2.5 px-4 rounded-xl text-sm font-medium transition-all duration-200',
                currentPlan === 'max'
                  ? 'bg-gray-700 text-gray-400 cursor-not-allowed'
                  : 'bg-white text-gray-900 hover:bg-gray-100'
              ]"
            >
              {{ currentPlan === 'max' ? 'Актуелен план' : 'Контактирајте нè' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="mt-10 sm:mt-12 text-center">
        <p class="text-sm text-gray-500">
          Сите планови вклучуваат 14-дневен бесплатен пробен период. Откажете кога сакате.
        </p>
        <p class="mt-3 text-sm text-gray-400">
          Имате прашања?
          <a href="mailto:support@facturino.mk" class="text-blue-600 hover:text-blue-700 font-medium">
            Контактирајте нè
          </a>
        </p>
      </div>

      <!-- Trust Badges -->
      <div class="mt-10 sm:mt-12 border-t border-gray-200 pt-8">
        <div class="flex flex-col sm:flex-row flex-wrap justify-center items-center gap-4 sm:gap-8 text-gray-400">
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
            </svg>
            <span class="text-xs sm:text-sm">SSL заштитено</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <span class="text-xs sm:text-sm">Безбедно плаќање со Stripe</span>
          </div>
          <div class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-xs sm:text-sm">Откажете кога сакате</span>
          </div>
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
          alert('Немате дозвола за оваа акција. Ве молиме контактирајте го сопственикот на компанијата.')
          this.loading = false
          return
        }

        const errorMessage = error.response?.data?.error || 'Неуспешно започнување на претплата. Обидете се повторно.'
        alert(errorMessage)
      } finally {
        this.loading = false
      }
    }
  }
}
</script>

<style scoped>
.pricing-page {
  min-height: 100vh;
  overflow-y: auto;
  overflow-x: hidden;
  background: linear-gradient(to bottom, #f9fafb, #ffffff);
}
</style>
<!-- CLAUDE-CHECKPOINT -->
