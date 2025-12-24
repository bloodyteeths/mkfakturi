<template>
  <BasePage class="relative">
    <form action="" @submit.prevent="submitForm">
      <!-- Page Header -->
      <BasePageHeader :title="pageTitle" class="mb-5">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
          <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
          <BaseBreadcrumbItem :title="$t('payroll.employees')" to="/admin/payroll/employees" />
          <BaseBreadcrumbItem :title="pageTitle" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton
            :loading="isSaving"
            :content-loading="isFetchingInitialData"
            :disabled="isSaving"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon
                v-if="!isSaving"
                name="ArrowDownOnSquareIcon"
                :class="slotProps.class"
              />
            </template>
            {{ isEdit ? $t('payroll.update_employee') : $t('payroll.save_employee') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard>
        <BaseInputGrid>
          <!-- Employee Number -->
          <BaseInputGroup
            :label="$t('payroll.employee_number')"
            :error="v$.currentEmployee.employee_number.$error && v$.currentEmployee.employee_number.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseInput
              v-model="currentEmployee.employee_number"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.employee_number.$error"
              @input="v$.currentEmployee.employee_number.$touch()"
            />
          </BaseInputGroup>

          <!-- EMBG (Macedonian Personal ID) -->
          <BaseInputGroup
            :label="$t('payroll.embg')"
            :error="v$.currentEmployee.embg.$error && v$.currentEmployee.embg.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseInput
              v-model="currentEmployee.embg"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.embg.$error"
              maxlength="13"
              @input="v$.currentEmployee.embg.$touch()"
            />
          </BaseInputGroup>

          <!-- First Name -->
          <BaseInputGroup
            :label="$t('payroll.first_name')"
            :error="v$.currentEmployee.first_name.$error && v$.currentEmployee.first_name.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseInput
              v-model="currentEmployee.first_name"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.first_name.$error"
              @input="v$.currentEmployee.first_name.$touch()"
            />
          </BaseInputGroup>

          <!-- Last Name -->
          <BaseInputGroup
            :label="$t('payroll.last_name')"
            :error="v$.currentEmployee.last_name.$error && v$.currentEmployee.last_name.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseInput
              v-model="currentEmployee.last_name"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.last_name.$error"
              @input="v$.currentEmployee.last_name.$touch()"
            />
          </BaseInputGroup>

          <!-- Email -->
          <BaseInputGroup
            :label="$t('payroll.email')"
            :error="v$.currentEmployee.email.$error && v$.currentEmployee.email.$errors[0].$message"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="currentEmployee.email"
              type="email"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.email.$error"
              @input="v$.currentEmployee.email.$touch()"
            />
          </BaseInputGroup>

          <!-- Phone -->
          <BaseInputGroup
            :label="$t('payroll.phone')"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="currentEmployee.phone"
              :content-loading="isFetchingInitialData"
            />
          </BaseInputGroup>

          <!-- Employment Date -->
          <BaseInputGroup
            :label="$t('payroll.employment_date')"
            :error="v$.currentEmployee.employment_date.$error && v$.currentEmployee.employment_date.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseDatePicker
              v-model="currentEmployee.employment_date"
              :content-loading="isFetchingInitialData"
              :calendar-button="true"
              :invalid="v$.currentEmployee.employment_date.$error"
              @input="v$.currentEmployee.employment_date.$touch()"
            />
          </BaseInputGroup>

          <!-- Termination Date (only if editing inactive employee) -->
          <BaseInputGroup
            v-if="isEdit && !currentEmployee.is_active"
            :label="$t('payroll.termination_date')"
            :content-loading="isFetchingInitialData"
          >
            <BaseDatePicker
              v-model="currentEmployee.termination_date"
              :content-loading="isFetchingInitialData"
              :calendar-button="true"
            />
          </BaseInputGroup>

          <!-- Employment Type -->
          <BaseInputGroup
            :label="$t('payroll.employment_type')"
            :error="v$.currentEmployee.employment_type.$error && v$.currentEmployee.employment_type.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseMultiselect
              v-model="currentEmployee.employment_type"
              :options="employmentTypes"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.employment_type.$error"
              :placeholder="$t('payroll.select_employment_type')"
              @update:modelValue="v$.currentEmployee.employment_type.$touch()"
            >
              <template #option="{ option }">
                {{ $t(`payroll.employment_types.${option.value}`) }}
              </template>
              <template #singlelabel="{ value }">
                {{ $t(`payroll.employment_types.${value.value}`) }}
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <!-- Department -->
          <BaseInputGroup
            :label="$t('payroll.department')"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="currentEmployee.department"
              :content-loading="isFetchingInitialData"
            />
          </BaseInputGroup>

          <!-- Position -->
          <BaseInputGroup
            :label="$t('payroll.position')"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="currentEmployee.position"
              :content-loading="isFetchingInitialData"
            />
          </BaseInputGroup>

          <!-- Base Salary -->
          <BaseInputGroup
            :label="$t('payroll.base_salary')"
            :error="v$.currentEmployee.base_salary_amount.$error && v$.currentEmployee.base_salary_amount.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseMoney
              v-model="baseSalaryData"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.base_salary_amount.$error"
              :currency="companyStore.selectedCompanyCurrency"
              @input="v$.currentEmployee.base_salary_amount.$touch()"
            />
          </BaseInputGroup>

          <!-- Bank Account IBAN -->
          <BaseInputGroup
            :label="$t('payroll.bank_account_iban')"
            :error="v$.currentEmployee.bank_account_iban.$error && v$.currentEmployee.bank_account_iban.$errors[0].$message"
            :content-loading="isFetchingInitialData"
            required
          >
            <BaseInput
              v-model="currentEmployee.bank_account_iban"
              :content-loading="isFetchingInitialData"
              :invalid="v$.currentEmployee.bank_account_iban.$error"
              placeholder="MK07..."
              @input="v$.currentEmployee.bank_account_iban.$touch()"
            />
          </BaseInputGroup>

          <!-- Bank Name -->
          <BaseInputGroup
            :label="$t('payroll.bank_name')"
            :content-loading="isFetchingInitialData"
          >
            <BaseInput
              v-model="currentEmployee.bank_name"
              :content-loading="isFetchingInitialData"
            />
          </BaseInputGroup>

          <!-- Active Status (only show in edit mode) -->
          <BaseInputGroup
            v-if="isEdit"
            :label="$t('payroll.status')"
            :content-loading="isFetchingInitialData"
            class="col-span-2"
          >
            <BaseSwitch
              v-model="currentEmployee.is_active"
              :label="currentEmployee.is_active ? $t('general.active') : $t('general.inactive')"
            />
          </BaseInputGroup>
        </BaseInputGrid>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { required, email, minLength, maxLength, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

let isSaving = ref(false)
let isFetchingInitialData = ref(false)

const currentEmployee = reactive({
  employee_number: '',
  embg: '',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  employment_date: '',
  termination_date: null,
  employment_type: 'full_time',
  department: '',
  position: '',
  base_salary_amount: 0,
  currency_id: companyStore.selectedCompanyCurrency?.id,
  bank_account_iban: '',
  bank_name: '',
  is_active: true,
})

const employmentTypes = [
  { value: 'full_time', label: 'Full Time' },
  { value: 'part_time', label: 'Part Time' },
  { value: 'contract', label: 'Contract' },
]

const rules = computed(() => {
  return {
    currentEmployee: {
      employee_number: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      embg: {
        required: helpers.withMessage(t('validation.required'), required),
        minLength: helpers.withMessage(t('payroll.embg_must_be_13_digits'), minLength(13)),
        maxLength: helpers.withMessage(t('payroll.embg_must_be_13_digits'), maxLength(13)),
      },
      first_name: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      last_name: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      email: {
        email: helpers.withMessage(t('validation.email_incorrect'), email),
      },
      employment_date: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      employment_type: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      base_salary_amount: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      bank_account_iban: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(rules, { currentEmployee })

const isEdit = computed(() => route.name === 'payroll.employees.edit')

const pageTitle = computed(() =>
  isEdit.value ? t('payroll.edit_employee') : t('payroll.new_employee')
)

const baseSalaryData = computed({
  get: () => {
    const precision = parseInt(companyStore.selectedCompanyCurrency.precision)
    return precision === 0
      ? currentEmployee.base_salary_amount
      : currentEmployee.base_salary_amount / 100
  },
  set: (value) => {
    const precision = parseInt(companyStore.selectedCompanyCurrency.precision)
    currentEmployee.base_salary_amount = precision === 0
      ? Math.round(value)
      : Math.round(value * 100)
  },
})

if (isEdit.value) {
  loadEmployee()
}

async function loadEmployee() {
  isFetchingInitialData.value = true
  try {
    const response = await axios.get(`admin/payroll-employees/${route.params.id}`)
    if (response.data && response.data.data) {
      Object.assign(currentEmployee, response.data.data)
    }
  } catch (error) {
    console.error('Error loading employee:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('general.something_went_wrong'),
    })
  } finally {
    isFetchingInitialData.value = false
  }
}

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  try {
    let response
    if (isEdit.value) {
      response = await axios.put(
        `admin/payroll-employees/${route.params.id}`,
        currentEmployee
      )
    } else {
      response = await axios.post('admin/payroll-employees', currentEmployee)
    }

    if (response.data) {
      notificationStore.showNotification({
        type: 'success',
        message: isEdit.value
          ? t('payroll.employee_updated')
          : t('payroll.employee_created'),
      })
      router.push('/admin/payroll/employees')
    }
  } catch (error) {
    console.error('Error saving employee:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  } finally {
    isSaving.value = false
  }
}
</script>

// LLM-CHECKPOINT
