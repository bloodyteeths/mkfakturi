<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">
      Заборавена Лозинка
    </h2>
    
    <p class="text-sm text-gray-600 text-center mb-6">
      Внесете ја вашата email адреса и ќе ви испратиме врска за ресетирање на лозинката.
    </p>

    <form @submit.prevent="handleForgotPassword" class="space-y-6">
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
            class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="partner@example.com"
          />
        </div>
      </div>

      <div>
        <button
          type="submit"
          :disabled="isLoading"
          class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="!isLoading">Испрати врска</span>
          <span v-else>Се испраќа...</span>
        </button>
      </div>

      <!-- Success Message -->
      <div v-if="successMessage" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ successMessage }}
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
import { ref, reactive } from 'vue'

const isLoading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = reactive({
  email: ''
})

const handleForgotPassword = async () => {
  isLoading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // Mock forgot password - replace with actual API call
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    successMessage.value = 'Врската за ресетирање е испратена на вашата email адреса.'
  } catch (error) {
    errorMessage.value = 'Настана грешка. Обидете се повторно.'
    console.error('Forgot password error:', error)
  } finally {
    isLoading.value = false
  }
}
</script>