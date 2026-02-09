<template>
  <div class="partner-pricing-page">
    <div class="container mx-auto px-4 py-12">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">
          Партнерска програма
        </h1>
        <p class="text-xl text-gray-600">
          Заработувајте провизија со препорака на Facturino на вашите клиенти
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto mb-8">
        <!-- Free Partner -->
        <div class="pricing-card border-2 border-gray-300 rounded-lg p-8 bg-white">
          <h3 class="text-2xl font-semibold mb-2">Partner (Бесплатно)</h3>
          <div class="text-4xl font-bold mb-6">
            €0<span class="text-sm text-gray-500">/мес</span>
          </div>

          <div class="mb-6">
            <div class="bg-blue-100 border-l-4 border-blue-500 p-4 mb-4">
              <p class="text-lg font-semibold text-blue-900">20% Провизија</p>
              <p class="text-sm text-blue-700">На сите препорачани клиентски претплати</p>
            </div>
          </div>

          <ul class="space-y-3 mb-8">
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Рекурентни месечни провизии</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Партнер портал</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Следење на провизија и извештаи</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Генерирање на линк за препорака</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Пристап за управување со клиенти</span>
            </li>
          </ul>

          <button
            class="w-full py-3 px-6 bg-gray-200 text-gray-800 rounded-lg font-semibold"
            :class="{ 'bg-green-600 text-white': currentTier === 'free' }"
            :disabled="currentTier === 'free'"
          >
            {{ currentTier === 'free' ? 'Актуелен план' : 'Веќе вклучено' }}
          </button>
        </div>

        <!-- Partner Plus -->
        <div class="pricing-card border-2 border-blue-500 rounded-lg p-8 bg-gradient-to-br from-blue-50 to-white relative">
          <div class="absolute top-0 right-0 bg-blue-500 text-white text-xs font-semibold px-4 py-2 rounded-bl-lg rounded-tr-lg">
            ПРЕПОРАЧАНО
          </div>

          <h3 class="text-2xl font-semibold mb-2">Partner Plus</h3>
          <div class="text-4xl font-bold mb-6">
            €29<span class="text-sm text-gray-500">/мес</span>
          </div>

          <div class="mb-6">
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-4">
              <p class="text-lg font-semibold text-green-900">22% Провизија</p>
              <p class="text-sm text-green-700">+2% зголемување над бесплатниот план</p>
            </div>
            <p class="text-sm text-gray-600 italic">
              Се исплатува после само €1.450 месечни клиентски претплати
            </p>
          </div>

          <ul class="space-y-3 mb-8">
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span class="font-semibold">Сè од бесплатниот, плус:</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-blue-600 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span><strong>22% удел во приход</strong> (наспроти 20%)</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-blue-600 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Приоритетна партнерска поддршка</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-blue-600 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Напредни аналитики и увиди</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-blue-600 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Ко-брендирани маркетинг материјали</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-blue-600 mr-3 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
              </svg>
              <span>Ран пристап до нови функции</span>
            </li>
          </ul>

          <button
            @click="subscribePlus"
            class="w-full py-3 px-6 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition shadow-lg"
            :class="{ 'bg-green-600 hover:bg-green-700': currentTier === 'plus' }"
            :disabled="loading || currentTier === 'plus'"
          >
            {{ currentTier === 'plus' ? 'Актуелен план' : 'Надгради на Plus' }}
          </button>
        </div>
      </div>

      <!-- Commission Calculator -->
      <div class="max-w-2xl mx-auto bg-gray-50 rounded-lg p-8 mb-8">
        <h3 class="text-2xl font-bold mb-4 text-center">Калкулатор на провизија</h3>
        <div class="mb-4">
          <label class="block text-sm font-medium mb-2">Месечни клиентски претплати:</label>
          <input
            v-model.number="clientRevenue"
            type="number"
            min="0"
            step="10"
            class="w-full px-4 py-2 border rounded-lg"
            placeholder="€0"
          />
        </div>
        <div class="grid grid-cols-2 gap-4 mt-6">
          <div class="bg-white p-4 rounded-lg border">
            <p class="text-sm text-gray-600 mb-1">Бесплатен Partner (20%)</p>
            <p class="text-2xl font-bold text-gray-900">€{{ (clientRevenue * 0.20).toFixed(2) }}</p>
          </div>
          <div class="bg-blue-50 p-4 rounded-lg border-2 border-blue-500">
            <p class="text-sm text-blue-700 mb-1">Partner Plus (22%)</p>
            <p class="text-2xl font-bold text-blue-900">€{{ (clientRevenue * 0.22).toFixed(2) }}</p>
            <p class="text-xs text-green-600 mt-1">+€{{ ((clientRevenue * 0.22) - (clientRevenue * 0.20)).toFixed(2) }} повеќе</p>
          </div>
        </div>
        <p class="text-center text-sm text-gray-600 mt-4">
          Со €{{ clientRevenue }} месечни клиентски претплати
        </p>
      </div>

      <div class="text-center text-sm text-gray-600">
        <p>Прашања за партнерската програма? <a href="#" class="text-blue-600 hover:underline">Контактирајте нè</a></p>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PartnersPricing',

  data() {
    return {
      currentTier: 'free',
      loading: false,
      clientRevenue: 500,
    }
  },

  mounted() {
    this.fetchCurrentTier()
  },

  methods: {
    async fetchCurrentTier() {
      try {
        const response = await axios.get('/api/partner/subscription')
        this.currentTier = response.data.current_plan?.tier || 'free'
      } catch (error) {
        console.error('Failed to fetch partner subscription', error)
      }
    },

    async subscribePlus() {
      if (this.loading) return

      this.loading = true

      try {
        const response = await axios.post('/api/partner/subscription/checkout')

        // Redirect to Paddle checkout
        if (response.data.checkout_url) {
          window.location.href = response.data.checkout_url
        }
      } catch (error) {
        console.error('Failed to create checkout session', error)
        alert('Грешка при започнување на претплата. Обидете се повторно.')
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
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

input[type="number"] {
  -moz-appearance: textfield;
}

input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
<!-- CLAUDE-CHECKPOINT -->
