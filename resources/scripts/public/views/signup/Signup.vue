<template>
  <div class="bg-white shadow-lg rounded-lg overflow-hidden">
    <!-- Referral Banner -->
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

    <!-- Wizard Navigation -->
    <div class="px-6 py-6 border-b border-gray-200">
      <BaseWizardNavigation :current-step="currentStep" :steps="4" />
    </div>

    <!-- Wizard Content -->
    <div class="px-6 py-8">
      <!-- Step 1: Company Info -->
      <BaseWizardStep
        v-show="currentStep === 0"
        title="Информации за компанијата"
        description="Внесете ги основните податоци за вашата компанија"
      >
        <form @submit.prevent="nextStep">
          <div class="grid grid-cols-1 gap-6 mb-6">
            <BaseInputGroup
              :label="'Име на компанија'"
              :error="v$.companyForm.name.$error && v$.companyForm.name.$errors[0].$message"
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
              :label="'Даночен број (ЕМБС)'"
              :error="v$.companyForm.tax_id.$error && v$.companyForm.tax_id.$errors[0].$message"
              required
            >
              <BaseInput
                v-model.trim="companyForm.tax_id"
                :invalid="v$.companyForm.tax_id.$error"
                type="text"
                placeholder="1234567890123"
                @input="v$.companyForm.tax_id.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="'Адреса'"
              :error="v$.companyForm.address.$error && v$.companyForm.address.$errors[0].$message"
              required
            >
              <BaseInput
                v-model.trim="companyForm.address"
                :invalid="v$.companyForm.address.$error"
                type="text"
                placeholder="Улица бр. 123"
                @input="v$.companyForm.address.$touch()"
              />
            </BaseInputGroup>

            <div class="grid grid-cols-2 gap-4">
              <BaseInputGroup
                :label="'Град'"
                :error="v$.companyForm.city.$error && v$.companyForm.city.$errors[0].$message"
                required
              >
                <BaseInput
                  v-model.trim="companyForm.city"
                  :invalid="v$.companyForm.city.$error"
                  type="text"
                  placeholder="Скопје"
                  @input="v$.companyForm.city.$touch()"
                />
              </BaseInputGroup>

              <BaseInputGroup
                :label="'Поштенски број'"
                :error="v$.companyForm.zip.$error && v$.companyForm.zip.$errors[0].$message"
              >
                <BaseInput
                  v-model.trim="companyForm.zip"
                  :invalid="v$.companyForm.zip.$error"
                  type="text"
                  placeholder="1000"
                  @input="v$.companyForm.zip.$touch()"
                />
              </BaseInputGroup>
            </div>
          </div>

          <div class="flex justify-end">
            <BaseButton type="submit">
              Продолжи
              <template #right="slotProps">
                <BaseIcon name="ChevronRightIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
          </div>
        </form>
      </BaseWizardStep>

      <!-- Step 2: Admin User -->
      <BaseWizardStep
        v-show="currentStep === 1"
        title="Администраторски корисник"
        description="Креирајте го вашиот администраторски профил"
      >
        <form @submit.prevent="nextStep">
          <div class="grid grid-cols-1 gap-6 mb-6">
            <BaseInputGroup
              :label="'Име и презиме'"
              :error="v$.userForm.name.$error && v$.userForm.name.$errors[0].$message"
              required
            >
              <BaseInput
                v-model.trim="userForm.name"
                :invalid="v$.userForm.name.$error"
                type="text"
                placeholder="Марко Петровски"
                @input="v$.userForm.name.$touch()"
              />
            </BaseInputGroup>

            <BaseInputGroup
              :label="'Email адреса'"
              :error="v$.userForm.email.$error && v$.userForm.email.$errors[0].$message"
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
              :error="v$.userForm.password.$error && v$.userForm.password.$errors[0].$message"
              required
            >
              <BaseInput
                v-model.trim="userForm.password"
                :invalid="v$.userForm.password.$error"
                :type="showPassword ? 'text' : 'password'"
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

            <BaseInputGroup
              :label="'Потврди лозинка'"
              :error="v$.userForm.password_confirmation.$error && v$.userForm.password_confirmation.$errors[0].$message"
              required
            >
              <BaseInput
                v-model.trim="userForm.password_confirmation"
                :invalid="v$.userForm.password_confirmation.$error"
                :type="showPasswordConfirm ? 'text' : 'password'"
                @input="v$.userForm.password_confirmation.$touch()"
              >
                <template #right>
                  <BaseIcon
                    :name="showPasswordConfirm ? 'EyeIcon' : 'EyeSlashIcon'"
                    class="mr-1 text-gray-500 cursor-pointer"
                    @click="showPasswordConfirm = !showPasswordConfirm"
                  />
                </template>
              </BaseInput>
            </BaseInputGroup>
          </div>

          <div class="flex justify-between">
            <BaseButton variant="outline" @click="prevStep">
              <template #left="slotProps">
                <BaseIcon name="ChevronLeftIcon" :class="slotProps.class" />
              </template>
              Назад
            </BaseButton>
            <BaseButton type="submit">
              Продолжи
              <template #right="slotProps">
                <BaseIcon name="ChevronRightIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
          </div>
        </form>
      </BaseWizardStep>

      <!-- Step 3: Plan Selection -->
      <BaseWizardStep
        v-show="currentStep === 2"
        title="Избери план"
        description="Одберете го планот што најмногу ви одговара"
      >
        <div class="mb-8">
          <div v-if="loadingPlans" class="text-center py-12">
            <BaseSpinner class="w-12 h-12 mx-auto text-primary-500" />
            <p class="mt-4 text-gray-600">Се вчитуваат плановите...</p>
          </div>

          <div v-else-if="plansError" class="text-center py-12">
            <BaseIcon name="ExclamationCircleIcon" class="w-16 h-16 mx-auto text-red-500" />
            <p class="mt-4 text-red-600">{{ plansError }}</p>
            <BaseButton class="mt-4" @click="loadPlans">Обиди се повторно</BaseButton>
          </div>

          <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-yellow-400 text-yellow-900 text-xs font-bold px-3 py-1 rounded-full"
              >
                Популарен
              </div>

              <div class="text-center">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                  {{ plan.name }}
                </h3>
                <div class="mb-4">
                  <span class="text-3xl font-bold text-gray-900">{{ plan.price }} ден</span>
                  <span class="text-gray-600">/месец</span>
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
        </div>

        <div class="flex justify-between">
          <BaseButton variant="outline" @click="prevStep">
            <template #left="slotProps">
              <BaseIcon name="ChevronLeftIcon" :class="slotProps.class" />
            </template>
            Назад
          </BaseButton>
          <BaseButton :disabled="!selectedPlan" @click="nextStep">
            Продолжи кон плаќање
            <template #right="slotProps">
              <BaseIcon name="ChevronRightIcon" :class="slotProps.class" />
            </template>
          </BaseButton>
        </div>
      </BaseWizardStep>

      <!-- Step 4: Payment -->
      <BaseWizardStep
        v-show="currentStep === 3"
        title="Плаќање"
        description="Завршете ја вашата регистрација со плаќање"
      >
        <div class="text-center py-8">
          <div v-if="isProcessing" class="space-y-4">
            <BaseSpinner class="w-16 h-16 mx-auto text-primary-500" />
            <p class="text-lg font-medium text-gray-900">
              Ве пренасочуваме кон плаќање...
            </p>
            <p class="text-sm text-gray-600">
              Ве молиме почекајте додека ја подготвуваме вашата сесија за плаќање.
            </p>
          </div>

          <div v-else-if="registrationError" class="space-y-4">
            <BaseIcon name="XCircleIcon" class="w-16 h-16 mx-auto text-red-500" />
            <p class="text-lg font-medium text-red-900">
              Грешка при регистрација
            </p>
            <p class="text-sm text-gray-600">
              {{ registrationError }}
            </p>
            <div class="flex justify-center gap-4">
              <BaseButton variant="outline" @click="prevStep">
                Назад
              </BaseButton>
              <BaseButton @click="completeRegistration">
                Обиди се повторно
              </BaseButton>
            </div>
          </div>

          <div v-else class="space-y-6">
            <div class="bg-primary-50 rounded-lg p-6">
              <h4 class="text-lg font-semibold text-gray-900 mb-4">
                Резиме на нарачка
              </h4>
              <div class="space-y-3 text-left max-w-md mx-auto">
                <div class="flex justify-between">
                  <span class="text-gray-600">План:</span>
                  <span class="font-medium text-gray-900">{{ selectedPlan?.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Цена:</span>
                  <span class="font-medium text-gray-900">{{ selectedPlan?.price }} ден/месец</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Компанија:</span>
                  <span class="font-medium text-gray-900">{{ companyForm.name }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Email:</span>
                  <span class="font-medium text-gray-900">{{ userForm.email }}</span>
                </div>
              </div>
            </div>

            <p class="text-sm text-gray-600">
              Со кликање на "Заврши регистрација" ќе бидете пренасочени кон Stripe за безбедно плаќање.
            </p>

            <div class="flex justify-between">
              <BaseButton variant="outline" @click="prevStep">
                <template #left="slotProps">
                  <BaseIcon name="ChevronLeftIcon" :class="slotProps.class" />
                </template>
                Назад
              </BaseButton>
              <BaseButton
                :loading="isProcessing"
                @click="completeRegistration"
              >
                <template #left="slotProps">
                  <BaseIcon
                    v-if="!isProcessing"
                    name="CreditCardIcon"
                    :class="slotProps.class"
                  />
                </template>
                Заврши регистрација
              </BaseButton>
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
import { required, email, minLength, sameAs, helpers } from '@vuelidate/validators'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const notificationStore = useNotificationStore()

// State
const currentStep = ref(0)
const referralCode = ref('')
const referralPartnerName = ref('')
const showPassword = ref(false)
const showPasswordConfirm = ref(false)
const loadingPlans = ref(false)
const plansError = ref('')
const plans = ref([])
const selectedPlan = ref(null)
const isProcessing = ref(false)
const registrationError = ref('')

// Forms
const companyForm = reactive({
  name: '',
  tax_id: '',
  address: '',
  city: '',
  zip: '',
})

const userForm = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

// Validation Rules
const companyRules = {
  companyForm: {
    name: {
      required: helpers.withMessage('Полето за име на компанија е задолжително', required),
    },
    tax_id: {
      required: helpers.withMessage('Полето за даночен број е задолжително', required),
      minLength: helpers.withMessage('Даночниот број мора да содржи најмалку 13 знаци', minLength(13)),
    },
    address: {
      required: helpers.withMessage('Полето за адреса е задолжително', required),
    },
    city: {
      required: helpers.withMessage('Полето за град е задолжително', required),
    },
    zip: {},
  },
}

const userRules = {
  userForm: {
    name: {
      required: helpers.withMessage('Полето за име е задолжително', required),
    },
    email: {
      required: helpers.withMessage('Полето за email е задолжително', required),
      email: helpers.withMessage('Ве молиме внесете валидна email адреса', email),
    },
    password: {
      required: helpers.withMessage('Полето за лозинка е задолжително', required),
      minLength: helpers.withMessage('Лозинката мора да содржи најмалку 8 знаци', minLength(8)),
    },
    password_confirmation: {
      required: helpers.withMessage('Полето за потврда на лозинка е задолжително', required),
      sameAs: helpers.withMessage('Лозинките не се совпаѓаат', sameAs(computed(() => userForm.password))),
    },
  },
}

const v$ = useVuelidate(
  currentStep.value === 0 ? companyRules : userRules,
  currentStep.value === 0 ? { companyForm } : { userForm }
)

// Methods
async function validateReferralCode() {
  if (!referralCode.value) return

  try {
    const response = await axios.post('/api/v1/public/signup/validate-referral', {
      code: referralCode.value,
    })

    if (response.data.valid) {
      referralPartnerName.value = response.data.partner_name
    }
  } catch (error) {
    console.error('Failed to validate referral code:', error)
    // Don't show error to user - just proceed without referral
  }
}

async function loadPlans() {
  loadingPlans.value = true
  plansError.value = ''

  try {
    const response = await axios.get('/api/v1/public/signup/plans')
    plans.value = response.data.data.map((plan) => ({
      id: plan.id,
      name: plan.name,
      price: plan.price,
      stripe_price_id: plan.stripe_price_id,
      popular: plan.popular || false,
      features: plan.features || [],
    }))

    // Auto-select the popular plan or first plan
    if (plans.value.length > 0) {
      const popularPlan = plans.value.find((p) => p.popular)
      selectedPlan.value = popularPlan || plans.value[0]
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
  // Validate current step
  if (currentStep.value === 0) {
    v$.value = useVuelidate(companyRules, { companyForm })
    await v$.value.$validate()
    if (v$.value.$error) return
  } else if (currentStep.value === 1) {
    v$.value = useVuelidate(userRules, { userForm })
    await v$.value.$validate()
    if (v$.value.$error) return
  } else if (currentStep.value === 2) {
    if (!selectedPlan.value) {
      notificationStore.showNotification({
        type: 'error',
        message: 'Ве молиме изберете план',
      })
      return
    }
  }

  // Load plans when entering step 3
  if (currentStep.value === 1 && plans.value.length === 0) {
    await loadPlans()
  }

  currentStep.value++
}

function prevStep() {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

async function completeRegistration() {
  isProcessing.value = true
  registrationError.value = ''

  try {
    const response = await axios.post('/api/v1/public/signup/register', {
      company: companyForm,
      user: userForm,
      plan_id: selectedPlan.value.id,
      referral_code: referralCode.value || null,
    })

    // Redirect to Stripe Checkout
    if (response.data.checkout_url) {
      window.location.href = response.data.checkout_url
    } else {
      throw new Error('No checkout URL returned')
    }
  } catch (error) {
    console.error('Registration failed:', error)
    isProcessing.value = false

    if (error.response?.data?.message) {
      registrationError.value = error.response.data.message
    } else {
      registrationError.value = 'Настана грешка при регистрацијата. Ве молиме обидете се повторно.'
    }

    notificationStore.showNotification({
      type: 'error',
      message: registrationError.value,
    })
  }
}

// Lifecycle
onMounted(() => {
  // Capture referral code from URL
  if (route.query.ref) {
    referralCode.value = route.query.ref
    validateReferralCode()
  }
})
</script>

// CLAUDE-CHECKPOINT
