<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">
      Ресетирај Лозинка
    </h2>

    <form @submit.prevent="handleResetPassword" class="space-y-6">
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">
          Email адреса
        </label>
        <div class="mt-1">
          <input
            id="email"
            v-model="form.email"
            name="email"
            type="email"
            autocomplete="email"
            required
            readonly
            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          />
        </div>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700">
          Нова лозинка
        </label>
        <div class="mt-1">
          <input
            id="password"
            v-model="form.password"
            name="password"
            type="password"
            autocomplete="new-password"
            required
            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="••••••••"
          />
        </div>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
          Потврди лозинка
        </label>
        <div class="mt-1">
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            name="password_confirmation"
            type="password"
            autocomplete="new-password"
            required
            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="••••••••"
          />
        </div>
      </div>

      <div>
        <button
          type="submit"
          :disabled="isLoading"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!isLoading">Ресетирај лозинка</span>
          <span v-else>Се ресетира...</span>
        </button>
      </div>

      <!-- Error Message -->
      <div v-if="errorMessage" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
        {{ errorMessage }}
      </div>
    </form>

    <div class="mt-6 text-center">
      <router-link
        :to="{ name: 'partner.login' }"
        class="text-sm text-blue-600 hover:text-blue-500"
      >
        ← Назад до најава
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const errorMessage = ref('')

const form = reactive({
  email: '',
  password: '',
  password_confirmation: '',
  token: ''
})

onMounted(() => {
  // Get email and token from query parameters
  form.email = route.query.email || ''
  form.token = route.query.token || ''
})

const handleResetPassword = async () => {
  isLoading.value = true
  errorMessage.value = ''

  // Validate passwords match
  if (form.password !== form.password_confirmation) {
    errorMessage.value = 'Лозинките не се совпаѓаат.'
    isLoading.value = false
    return
  }

  try {
    // Mock reset password - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Redirect to login with success message
    router.push({ 
      name: 'partner.login',
      query: { message: 'Лозинката е успешно ресетирана. Можете да се најавите.' }
    })
  } catch (error) {
    errorMessage.value = 'Настана грешка. Обидете се повторно.'
    console.error('Reset password error:', error)
  } finally {
    isLoading.value = false
  }
}
</script>