<template>
  <div class="bg-white shadow-lg rounded-lg overflow-hidden">
    <!-- Partner Referral Banner -->
    <div
      v-if="referralPartnerName"
      class="bg-primary-50 border-l-4 border-primary-500 p-4"
    >
      <div class="flex items-center">
        <BaseIcon name="UserGroupIcon" class="h-6 w-6 text-primary-600 mr-3" />
        <div>
          <p class="text-sm font-medium text-primary-900">
            Покана од партнер
          </p>
          <p class="text-sm text-primary-700">
            Регистрирате се преку <strong>{{ referralPartnerName }}</strong>
          </p>
        </div>
      </div>
    </div>

    <!-- Company Referral Banner with Discount -->
    <div
      v-if="companyReferralData"
      class="bg-green-50 border-l-4 border-green-500 p-4"
    >
      <div class="flex items-center">
        <BaseIcon name="GiftIcon" class="h-6 w-6 text-green-600 mr-3" />
        <div>
          <p class="text-sm font-medium text-green-900">
            Покана од {{ companyReferralData.inviter_company_name }}
          </p>
          <p class="text-sm text-green-700">
            Добивате <strong>{{ companyReferralData.discount_percent }}% попуст</strong> на првата уплата!
          </p>
        </div>
      </div>
    </div>

    <!-- Wizard Navigation -->
    <div class="px-6 py-6 border-b border-gray-200">
      <BaseWizardNavigation :current-step="currentStep" :steps="2" />
    </div>

    <!-- Wizard Content -->
    <div class="px-6 py-8">
      <!-- Step 1: Account (Company Name + Email + Password) -->
      <BaseWizardStep
        v-show="currentStep === 0"
        title="Креирајте сметка"
        description="Потребни се само 3 полиња за да започнете"
      >
        <form @submit.prevent="nextStep">
          <div class="grid grid-cols-1 gap-6 mb-6">
            <BaseInputGroup
              :label="'Име на компанија'"
              :error="v$.companyForm.name.$error && v$.companyForm.name.$errors[0]?.$message"
              required
            >
              <BaseInput
                v-model.trim="companyForm.name"
                :invalid="v$.companyForm.name.$error"
                type="text"
                placeholder="Компанија ДООЕЛ"
                @input="v$.companyForm.name.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="'Email адреса'"
              :error="v$.userForm.email.$error && v$.userForm.email.$errors[0]?.$message"
              required
            >
              <BaseInput
                v-model.trim="userForm.email"
                :invalid="v$.userForm.email.$error"
                type="email"
                placeholder="marko@kompanija.mk"
                @input="v$.userForm.email.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="'Лозинка'"
              :error="v$.userForm.password.$error && v$.userForm.password.$errors[0]?.$message"
              required
            >
              <BaseInput
                v-model.trim="userForm.password"
                :invalid="v$.userForm.password.$error"
                :type="showPassword ? 'text' : 'password'"
                placeholder="Најмалку 8 знаци"
                @input="v$.userForm.password.$touch()"
              >
                <template #right>
                  <BaseIcon
                    :name="showPassword ? 'EyeIcon' : 'EyeSlashIcon'"
                    class="mr-1 text-gray-500 cursor-pointer"
                    @click="showPassword = !showPassword"
                  />
                </template>
              </BaseInput>
            </BaseInputGroup>
          </div>

          <p class="text-xs text-gray-500 mb-6">
            Со регистрација се согласувате со
            <a href="/terms" target="_blank" class="text-primary-600 hover:underline">условите за користење</a>
            и
            <a href="/privacy" target="_blank" class="text-primary-600 hover:underline">политиката за приватност</a>.
          </p>

          <div class="flex justify-end">
            <BaseButton type="submit" class="w-full sm:w-auto">
              Продолжи
              <template #right="slotProps">
                <BaseIcon name="ChevronRightIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
          </div>

          <p class="text-center text-sm text-gray-600 mt-6">
            Веќе имате сметка?
            <router-link to="/login" class="text-primary-600 font-medium hover:underline">Најавете се</router-link>
          </p>
        </form>
      </BaseWizardStep>

      <!-- Step 2: Plan Selection -->
      <BaseWizardStep
        v-show="currentStep === 1"
        title="Избери план"
        description="Сите платени планови имаат 14 дена бесплатен пробен период"
      >
        <div class="mb-8">
          <!-- Billing Period + Currency Toggle -->
          <div class="flex flex-col items-center gap-4 mb-8">
            <div class="inline-flex items-center bg-gray-100 rounded-full p-1">
              <button
                :class="[
                  'px-6 py-2 rounded-full text-sm font-medium transition-all',
                  billingPeriod === 'monthly'
                    ? 'bg-white text-primary-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
                @click="billingPeriod = 'monthly'"
              >
                Месечно
              </button>
              <button
                :class="[
                  'px-6 py-2 rounded-full text-sm font-medium transition-all',
                  billingPeriod === 'yearly'
                    ? 'bg-white text-primary-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
                @click="billingPeriod = 'yearly'"
              >
                Годишно
                <span class="ml-1 text-xs text-green-600 font-bold">-17%</span>
              </button>
            </div>

            <!-- Currency Toggle (MKD / EUR for SEPA) -->
            <div class="inline-flex items-center bg-gray-100 rounded-full p-1">
              <button
                :class="[
                  'px-5 py-1.5 rounded-full text-xs font-medium transition-all',
                  paymentCurrency === 'mkd'
                    ? 'bg-white text-primary-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
                @click="paymentCurrency = 'mkd'"
              >
                MKD (Картичка)
              </button>
              <button
                :class="[
                  'px-5 py-1.5 rounded-full text-xs font-medium transition-all',
                  paymentCurrency === 'eur'
                    ? 'bg-white text-primary-600 shadow-sm'
                    : 'text-gray-600 hover:text-gray-800'
                ]"
                @click="paymentCurrency = 'eur'"
              >
                EUR (SEPA)
              </button>
            </div>
          </div>

          <div v-if="loadingPlans" class="text-center py-12">
            <BaseSpinner class="w-12 h-12 mx-auto text-primary-500" />
            <p class="mt-4 text-gray-600">Се вчитуваат плановите...</p>
          </div>

          <div v-else-if="plansError" class="text-center py-12">
            <BaseIcon name="ExclamationCircleIcon" class="w-16 h-16 mx-auto text-red-500" />
            <p class="mt-4 text-red-600">{{ plansError }}</p>
            <BaseButton class="mt-4" @click="loadPlans">Обиди се повторно</BaseButton>
          </div>

          <div v-else class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
              v-for="plan in plans"
              :key="plan.id"
              :class="[
                'relative border-2 rounded-lg p-6 cursor-pointer transition-all',
                selectedPlan?.id === plan.id
                  ? 'border-primary-500 bg-primary-50 shadow-lg'
                  : 'border-gray-200 hover:border-primary-300 hover:shadow-md'
              ]"
              @click="selectPlan(plan)"
            >
              <!-- Selected Checkmark -->
              <div
                v-if="selectedPlan?.id === plan.id"
                class="absolute -top-3 -right-3 bg-primary-500 text-white rounded-full p-2"
              >
                <BaseIcon name="CheckIcon" class="w-5 h-5" />
              </div>

              <!-- Popular Badge -->
              <div
                v-if="plan.popular"
                class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full z-10"
              >
                Популарен
              </div>

              <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  {{ plan.name }}
                </h3>
                <div class="mb-4">
                  <span v-if="plan.price === 0" class="text-2xl font-bold text-green-600">Бесплатно</span>
                  <template v-else-if="paymentCurrency === 'eur'">
                    <div v-if="billingPeriod === 'monthly'">
                      <span class="text-2xl font-bold text-gray-900">€{{ plan.price_eur }}</span>
                      <span class="text-sm text-gray-600">/месец</span>
                    </div>
                    <div v-else>
                      <span class="text-2xl font-bold text-gray-900">€{{ plan.price_eur_yearly }}</span>
                      <span class="text-sm text-gray-600">/год</span>
                      <div class="text-xs text-green-600 mt-1">
                        (€{{ Math.round(plan.price_eur_yearly / 12) }}/месец)
                      </div>
                    </div>
                  </template>
                  <template v-else>
                    <div v-if="billingPeriod === 'monthly'">
                      <span class="text-2xl font-bold text-gray-900">{{ plan.price }}</span>
                      <span class="text-sm text-gray-600"> ден/месец</span>
                    </div>
                    <div v-else>
                      <span class="text-2xl font-bold text-gray-900">{{ plan.price_yearly }}</span>
                      <span class="text-sm text-gray-600"> ден/год</span>
                      <div class="text-xs text-green-600 mt-1">
                        ({{ Math.round(plan.price_yearly / 12) }} ден/месец)
                      </div>
                    </div>
                  </template>
                  <!-- Trial note for paid plans -->
                  <p v-if="plan.price > 0" class="text-xs text-green-600 mt-1 font-medium">
                    Првите 14 дена не се наплаќаат
                  </p>
                </div>
                <ul class="text-sm text-left space-y-2 mb-6">
                  <li
                    v-for="(feature, index) in plan.features"
                    :key="index"
                    class="flex items-start"
                  >
                    <BaseIcon
                      name="CheckCircleIcon"
                      class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5"
                    />
                    <span class="text-gray-700">{{ feature }}</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- SEPA Info Note -->
          <div class="mt-6 text-center">
            <p v-if="paymentCurrency === 'eur'" class="inline-flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-full px-5 py-2.5">
              <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
              SEPA банкарски трансфер е овозможен. На checkout ќе можете да платите со картичка или банкарски трансфер.
            </p>
            <p v-else class="inline-flex items-center gap-2 text-sm text-gray-500 bg-gray-50 border border-gray-200 rounded-full px-5 py-2.5">
              <svg class="w-4 h-4 text-primary-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              Немате картичка? Изберете EUR за плаќање преку банкарски трансфер (SEPA).
            </p>
          </div>
        </div>

        <div class="flex justify-between">
          <BaseButton variant="outline" @click="prevStep">
            <template #left="slotProps">
              <BaseIcon name="ChevronLeftIcon" :class="slotProps.class" />
            </template>
            Назад
          </BaseButton>
          <BaseButton
            :disabled="!selectedPlan || isProcessing"
            :loading="isProcessing"
            @click="completeRegistration"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isProcessing"
                :name="selectedPlan?.price === 0 ? 'CheckCircleIcon' : 'CreditCardIcon'"
                :class="slotProps.class"
              />
            </template>
            {{ selectedPlan?.price === 0 ? 'Започни бесплатно' : 'Започни 14-дневен пробен период' }}
          </BaseButton>
        </div>

        <!-- Registration Error -->
        <div v-if="registrationError" class="mt-6 bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex items-start">
            <BaseIcon name="XCircleIcon" class="w-5 h-5 text-red-500 mr-3 mt-0.5 flex-shrink-0" />
            <div>
              <p class="text-sm text-red-800">{{ registrationError }}</p>
              <button
                class="mt-2 text-sm text-red-600 hover:text-red-800 underline"
                @click="registrationError = ''"
              >
                Затвори
              </button>
            </div>
          </div>
        </div>
      </BaseWizardStep>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useVuelidate } from '@vuelidate/core'
import { required, email, minLength, helpers } from '@vuelidate/validators'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const notificationStore = useNotificationStore()

// State
const currentStep = ref(0)
const referralCode = ref('')
const referralPartnerName = ref('')
const showPassword = ref(false)
const loadingPlans = ref(false)
const plansError = ref('')
const plans = ref([])
const selectedPlan = ref(null)
const billingPeriod = ref('monthly')
const paymentCurrency = ref('mkd')
const isProcessing = ref(false)
const registrationError = ref('')
const utmParams = ref(null)

// Forms
const companyForm = reactive({
  name: '',
})

const userForm = reactive({
  email: '',
  password: '',
})

// Validation Rules — only 3 fields required
const rules = {
  companyForm: {
    name: {
      required: helpers.withMessage('Внесете име на компанија', required),
      minLength: helpers.withMessage('Името мора да содржи најмалку 2 знаци', minLength(2)),
    },
  },
  userForm: {
    email: {
      required: helpers.withMessage('Внесете email адреса', required),
      email: helpers.withMessage('Внесете валидна email адреса', email),
    },
    password: {
      required: helpers.withMessage('Внесете лозинка', required),
      minLength: helpers.withMessage('Лозинката мора да содржи најмалку 8 знаци', minLength(8)),
    },
  },
}

const v$ = useVuelidate(rules, { companyForm, userForm })

// Referral data for registration
const referralData = ref(null)

// Company-to-company referral data
const companyReferralToken = ref('')
const companyReferralData = ref(null)

// Methods
async function validateReferralCode() {
  if (!referralCode.value) return

  try {
    const response = await axios.post('/public/signup/validate-referral', {
      code: referralCode.value,
    })

    if (response.data.success && response.data.data) {
      referralPartnerName.value = response.data.data.partner_name || response.data.data.partner_company
      referralData.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to validate referral code:', error)
  }
}

async function validateCompanyReferral() {
  if (!companyReferralToken.value) return

  try {
    const response = await axios.post('/public/signup/validate-company-referral', {
      token: companyReferralToken.value,
    })

    if (response.data.success && response.data.data) {
      companyReferralData.value = response.data.data
    }
  } catch (error) {
    console.error('Failed to validate company referral:', error)
  }
}

async function loadPlans() {
  loadingPlans.value = true
  plansError.value = ''

  try {
    const response = await axios.get('/public/signup/plans')
    const plansData = response.data.data || response.data || []

    plans.value = plansData.map((plan) => ({
      id: plan.id,
      name: plan.name,
      price: plan.price || 0,
      price_yearly: plan.price_yearly || 0,
      price_eur: plan.price_eur || 0,
      price_eur_yearly: plan.price_eur_yearly || 0,
      stripe_price_id: plan.stripe_price_id || null,
      popular: plan.id === 'standard',
      features: plan.features || [],
      description: plan.description || '',
      currency: plan.currency || 'MKD',
    }))

    // Auto-select free plan by default
    if (plans.value.length > 0) {
      const freePlan = plans.value.find((p) => p.id === 'free')
      selectedPlan.value = freePlan || plans.value[0]
    }
  } catch (error) {
    console.error('Failed to load plans:', error)
    plansError.value = 'Неуспешно вчитување на плановите. Ве молиме обидете се повторно.'
  } finally {
    loadingPlans.value = false
  }
}

function selectPlan(plan) {
  selectedPlan.value = plan
}

async function nextStep() {
  // Validate step 1 fields
  if (currentStep.value === 0) {
    await v$.value.$validate()
    if (v$.value.$error) return

    // Load plans when entering step 2
    if (plans.value.length === 0) {
      await loadPlans()
    }
  }

  currentStep.value++
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

async function completeRegistration() {
  if (!selectedPlan.value) return

  isProcessing.value = true
  registrationError.value = ''

  try {
    const payload = {
      company_name: companyForm.name,
      name: userForm.email.split('@')[0], // Use email prefix as name
      email: userForm.email,
      password: userForm.password,
      plan: selectedPlan.value.id,
      billing_period: billingPeriod.value,
      payment_currency: paymentCurrency.value,
    }

    if (utmParams.value) {
      payload.utm_source = utmParams.value.utm_source
      payload.utm_medium = utmParams.value.utm_medium
      payload.utm_campaign = utmParams.value.utm_campaign
    }

    // Add partner referral data if available
    if (referralData.value) {
      payload.partner_id = referralData.value.partner_id
      payload.affiliate_link_id = referralData.value.affiliate_link_id
    } else if (referralCode.value) {
      payload.referral_code = referralCode.value
    }

    // Add company referral token if available
    if (companyReferralToken.value && companyReferralData.value) {
      payload.company_referral_token = companyReferralToken.value
    }

    const response = await axios.post('/public/signup/register', payload)

    // Get checkout URL from response
    const checkoutUrl = response.data.data?.checkout_url || response.data.checkout_url

    // Redirect to Stripe Checkout or auto-login (for free plan)
    if (checkoutUrl) {
      window.location.href = checkoutUrl
    } else {
      throw new Error('No checkout URL returned')
    }
  } catch (error) {
    console.error('Registration failed:', error)
    isProcessing.value = false

    let errorMsg = 'Настана грешка при регистрацијата. Ве молиме обидете се повторно.'

    if (error.response?.data) {
      const data = error.response.data

      if (data.errors) {
        const firstError = Object.values(data.errors)[0]
        errorMsg = Array.isArray(firstError) ? firstError[0] : firstError
      } else if (data.message) {
        errorMsg = data.message
      }
    } else if (error.message && error.message !== 'No checkout URL returned') {
      errorMsg = error.message
    }

    registrationError.value = errorMsg

    notificationStore.showNotification({
      type: 'error',
      message: errorMsg,
    })
  }
}

// Lifecycle
onMounted(() => {
  // Capture partner referral code from URL
  if (route.query.ref) {
    referralCode.value = route.query.ref
    validateReferralCode()
  }

  // Capture company referral token from URL
  if (route.query.company_ref) {
    companyReferralToken.value = route.query.company_ref
    validateCompanyReferral()
  }

  // Pre-fill email from outreach link (base64 encoded)
  if (route.query.email) {
    try {
      userForm.email = atob(route.query.email)
    } catch (e) {
      userForm.email = route.query.email
    }
  }

  // Capture UTM params for attribution
  if (route.query.utm_source) {
    utmParams.value = {
      utm_source: route.query.utm_source || '',
      utm_medium: route.query.utm_medium || '',
      utm_campaign: route.query.utm_campaign || '',
    }
  }
})
</script>
// CLAUDE-CHECKPOINT
