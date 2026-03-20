<template>
  <form id="loginForm" class="mt-12 text-left" @submit.prevent="onSubmit">
    <BaseInputGroup
      :error="v$.email.$error && v$.email.$errors[0].$message"
      :label="$t('login.email')"
      class="mb-4"
      required
    >
      <BaseInput
        v-model="authStore.loginData.email"
        :invalid="v$.email.$error"
        focus
        type="email"
        name="email"
        @input="v$.email.$touch()"
      />
    </BaseInputGroup>

    <BaseInputGroup
      :error="v$.password.$error && v$.password.$errors[0].$message"
      :label="$t('login.password')"
      class="mb-4"
      required
    >
      <BaseInput
        v-model="authStore.loginData.password"
        :invalid="v$.password.$error"
        :type="getInputType"
        name="password"
        @input="v$.password.$touch()"
      >
        <template #right>
          <BaseIcon
            :name="isShowPassword ? 'EyeIcon' : 'EyeSlashIcon'"
            class="mr-1 text-gray-500 cursor-pointer"
            @click="isShowPassword = !isShowPassword"
          />
        </template>
      </BaseInput>
    </BaseInputGroup>

    <div class="mt-5 mb-8">
      <div class="mb-4">
        <router-link
          to="/forgot-password"
          class="text-sm text-primary-400 hover:text-gray-700"
        >
          {{ $t('login.forgot_password') }}
        </router-link>
      </div>
    </div>
    <BaseButton :loading="isLoading" type="submit">
      {{ $t('login.login') }}
    </BaseButton>

    <!-- eID / OneID divider and button (P13-03) — hidden until eID.mk client_id is configured -->
    <template v-if="false">
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-300"></div>
        <span class="px-3 text-sm text-gray-400">{{ $t('login.or', 'or') }}</span>
        <div class="flex-grow border-t border-gray-300"></div>
      </div>

      <button
        type="button"
        class="
          flex
          items-center
          justify-center
          w-full
          px-4
          py-3
          text-sm
          font-medium
          text-white
          transition-colors
          duration-200
          rounded-md
          bg-blue-700
          hover:bg-blue-800
          focus:outline-none
          focus:ring-2
          focus:ring-offset-2
          focus:ring-blue-500
        "
        @click="loginWithOneId"
      >
        <svg
          class="w-5 h-5 mr-2"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M12 2L3 7v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-9-5zm0 2.18l7 3.89v4.93c0 4.56-3.12 8.83-7 9.93-3.88-1.1-7-5.37-7-9.93V8.07l7-3.89z"
            fill="currentColor"
          />
          <path
            d="M12 7a3 3 0 00-3 3v1H8v5h8v-5h-1v-1a3 3 0 00-3-3zm-1 3a1 1 0 112 0v1h-2v-1zm-1 3h4v1h-4v-1z"
            fill="currentColor"
          />
        </svg>
        {{ $t('login.sign_in_eid', 'Sign in with eID / OneID') }}
      </button>
    </template>
  </form>
</template>

<script setup>
import axios from 'axios'
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useRouter } from 'vue-router'
import { required, email, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/scripts/admin/stores/auth'
import { useUserStore } from '@/scripts/admin/stores/user'
import { handleError } from '@/scripts/helpers/error-handling'

const notificationStore = useNotificationStore()
const authStore = useAuthStore()
const userStore = useUserStore()
const { t } = useI18n()
const router = useRouter()
const isLoading = ref(false)
let isShowPassword = ref(false)

const rules = {
  email: {
    required: helpers.withMessage(t('validation.required'), required),
    email: helpers.withMessage(t('validation.email_incorrect'), email),
  },
  password: {
    required: helpers.withMessage(t('validation.required'), required),
  },
}

const v$ = useVuelidate(
  rules,
  computed(() => authStore.loginData)
)

const getInputType = computed(() => {
  if (isShowPassword.value) {
    return 'text'
  }
  return 'password'
})

async function onSubmit() {
  axios.defaults.withCredentials = true

  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }

  isLoading.value = true

  try {
    // Wait for login to complete and check if successful
    const loginResponse = await authStore.login(authStore.loginData)

    if (!loginResponse || !loginResponse.data) {
      isLoading.value = false
      return
    }

    // Get user data from login response to check role
    const userData = loginResponse.data?.user
    const userRole = userData?.role

    // IMPORTANT: Set user in store immediately so navigation guards work
    if (userData) {
      userStore.currentUser = userData
    }

    // Show success notification
    notificationStore.showNotification({
      type: 'success',
      message: 'Logged in successfully.',
    })

    // Redirect based on user role
    // Partner users go to partner dashboard, others go to admin dashboard
    if (userRole === 'partner') {
      // Redirect to onboarding if not completed (super admins bypass)
      if (!userData.onboarding_completed_at && userData.role !== 'super admin') {
        router.push('/admin/partner/onboarding')
      } else {
        router.push('/admin/partner/dashboard')
      }
    } else {
      router.push('/admin/dashboard')
    }
    // CLAUDE-CHECKPOINT
  } catch (error) {
    isLoading.value = false
    console.error('Login error:', error)
    console.error('Login error response:', error.response)
    console.error('Login error data:', error.response?.data)
  }
}

/**
 * Redirect the browser to the OneID OIDC authorization endpoint.
 * The server-side route generates the state token and builds the URL.
 */
function loginWithOneId() {
  window.location.href = '/auth/oneid/redirect'
}

// Pre-fill demo credentials if in demo environment
onMounted(() => {
  if (window.demo_mode) {
    authStore.loginData.email = 'demo@invoiceshelf.com'
    authStore.loginData.password = 'demo'
  }
})
</script>
