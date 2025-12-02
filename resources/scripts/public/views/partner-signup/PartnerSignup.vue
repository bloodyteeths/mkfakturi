<template>
  <div class="bg-white shadow-lg rounded-lg overflow-hidden max-w-2xl mx-auto">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-8 py-6 text-white">
      <h1 class="text-2xl font-bold">Станете партнер на Facturino</h1>
      <p class="text-purple-100 mt-2">Заработувајте 20% провизија од секоја претплата</p>
      <div v-if="referrerInfo" class="mt-4 bg-white/10 rounded-lg p-3">
        <p class="text-sm">Поканети сте од: <strong>{{ referrerInfo.name }}</strong></p>
      </div>
    </div>

    <!-- Form -->
    <div class="p-8">
      <!-- Benefits Section -->
      <div class="mb-8 bg-purple-50 rounded-lg p-6">
        <h3 class="font-semibold text-purple-900 mb-3">Зошто да станете партнер?</h3>
        <ul class="space-y-2 text-sm text-purple-800">
          <li class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            20% провизија од секоја претплата на вашите клиенти
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Пристап до партнерски портал со статистики
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Месечни исплати преку Stripe Connect
          </li>
          <li class="flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            Бесплатно - без почетни трошоци
          </li>
        </ul>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="submitForm">
        <!-- Personal Information -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Лични податоци</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Име и презиме *</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="Марко Марковски"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input
                v-model="form.email"
                type="email"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="marko@example.com"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="+389 70 123 456"
              />
            </div>
          </div>
        </div>

        <!-- Company Information -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Податоци за фирма (опционално)</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Име на фирма</label>
              <input
                v-model="form.company_name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="Мојата Сметководствена Фирма"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Даночен број (ЕДБ)</label>
              <input
                v-model="form.tax_id"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="MK1234567890123"
              />
            </div>
          </div>
        </div>

        <!-- Password -->
        <div class="mb-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Лозинка за пристап</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Лозинка *</label>
              <input
                v-model="form.password"
                type="password"
                required
                minlength="8"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="Минимум 8 карактери"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Потврди лозинка *</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                placeholder="Потврди лозинка"
              />
            </div>
          </div>
        </div>

        <!-- Terms -->
        <div class="mb-6">
          <label class="flex items-start gap-3">
            <input
              v-model="form.accept_terms"
              type="checkbox"
              required
              class="mt-1 h-4 w-4 text-purple-600 border-gray-300 rounded focus:ring-purple-500"
            />
            <span class="text-sm text-gray-600">
              Се согласувам со
              <a href="/terms" target="_blank" class="text-purple-600 hover:underline">условите за користење</a>
              и
              <a href="/privacy" target="_blank" class="text-purple-600 hover:underline">политиката за приватност</a>
            </span>
          </label>
        </div>

        <!-- Submit Button -->
        <button
          type="submit"
          :disabled="submitting"
          class="w-full bg-purple-600 text-white py-3 px-6 rounded-lg font-semibold hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
        >
          {{ submitting ? 'Се регистрира...' : 'Регистрирај се како партнер' }}
        </button>

        <p class="text-center text-sm text-gray-500 mt-4">
          Веќе имате сметка?
          <router-link to="/login" class="text-purple-600 hover:underline">Најавете се</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const form = ref({
  name: '',
  email: '',
  phone: '',
  company_name: '',
  tax_id: '',
  password: '',
  password_confirmation: '',
  accept_terms: false,
  referral_token: null
})

const referrerInfo = ref(null)
const submitting = ref(false)
const errorMessage = ref(null)

onMounted(async () => {
  // Get referral token from URL
  const refToken = route.query.ref
  if (refToken) {
    form.value.referral_token = refToken
    // Validate token and get referrer info
    try {
      const response = await axios.post('/public/partner-signup/validate-referral', {
        token: refToken
      })
      if (response.data.referrer) {
        referrerInfo.value = response.data.referrer
      }
    } catch (err) {
      console.log('Invalid or expired referral token')
    }
  }
})

const submitForm = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    errorMessage.value = 'Лозинките не се совпаѓаат'
    return
  }

  submitting.value = true
  errorMessage.value = null

  try {
    const response = await axios.post('/public/partner-signup/register', form.value)

    if (response.data.success) {
      // Redirect to partner login with success message
      router.push({
        path: '/admin/partner/login',
        query: { registered: 1, email: form.value.email }
      })
    }
  } catch (err) {
    console.error('Partner registration failed:', err)
    if (err.response?.data?.errors) {
      const errors = err.response.data.errors
      errorMessage.value = Object.values(errors).flat().join(', ')
    } else if (err.response?.data?.message) {
      errorMessage.value = err.response.data.message
    } else {
      errorMessage.value = 'Регистрацијата не успеа. Обидете се повторно.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

// CLAUDE-CHECKPOINT
