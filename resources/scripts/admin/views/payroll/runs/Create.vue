<template>
  <BasePage class="relative">
    <form action="" @submit.prevent="submitForm">
      <!-- Page Header -->
      <BasePageHeader :title="$t('payroll.new_payroll_run')" class="mb-5">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
          <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
          <BaseBreadcrumbItem :title="$t('payroll.payroll_runs')" to="/admin/payroll/runs" />
          <BaseBreadcrumbItem :title="$t('payroll.new_payroll_run')" to="#" active />
        </BaseBreadcrumb>

        <template #actions>
          <BaseButton
            :loading="isSaving"
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
            {{ $t('payroll.create_run') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard>
        <BaseInputGrid>
          <!-- Period Year -->
          <BaseInputGroup
            :label="$t('payroll.year')"
            :error="v$.currentRun.period_year.$error && v$.currentRun.period_year.$errors[0].$message"
            required
          >
            <BaseInput
              v-model="currentRun.period_year"
              type="number"
              :invalid="v$.currentRun.period_year.$error"
              :min="2020"
              :max="2050"
              @input="v$.currentRun.period_year.$touch(); updatePeriodDates()"
            />
          </BaseInputGroup>

          <!-- Period Month -->
          <BaseInputGroup
            :label="$t('payroll.month')"
            :error="v$.currentRun.period_month.$error && v$.currentRun.period_month.$errors[0].$message"
            required
          >
            <BaseMultiselect
              v-model="currentRun.period_month"
              :options="monthOptions"
              value-prop="value"
              label="label"
              :invalid="v$.currentRun.period_month.$error"
              :placeholder="$t('payroll.select_month')"
              @update:modelValue="v$.currentRun.period_month.$touch(); updatePeriodDates()"
            />
          </BaseInputGroup>

          <!-- Period Start Date (auto-calculated) -->
          <BaseInputGroup
            :label="$t('payroll.period_start')"
            :error="v$.currentRun.period_start.$error && v$.currentRun.period_start.$errors[0].$message"
            required
          >
            <BaseDatePicker
              v-model="currentRun.period_start"
              :calendar-button="true"
              :invalid="v$.currentRun.period_start.$error"
              @input="v$.currentRun.period_start.$touch()"
            />
          </BaseInputGroup>

          <!-- Period End Date (auto-calculated) -->
          <BaseInputGroup
            :label="$t('payroll.period_end')"
            :error="v$.currentRun.period_end.$error && v$.currentRun.period_end.$errors[0].$message"
            required
          >
            <BaseDatePicker
              v-model="currentRun.period_end"
              :calendar-button="true"
              :invalid="v$.currentRun.period_end.$error"
              @input="v$.currentRun.period_end.$touch()"
            />
          </BaseInputGroup>

          <!-- Info Box -->
          <div class="col-span-2 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
              <BaseIcon name="InformationCircleIcon" class="w-5 h-5 text-blue-600 mt-0.5 mr-3" />
              <div class="text-sm text-blue-800">
                <p class="font-semibold mb-1">{{ $t('payroll.run_creation_info') }}</p>
                <ul class="list-disc list-inside space-y-1">
                  <li>{{ $t('payroll.run_info_1') }}</li>
                  <li>{{ $t('payroll.run_info_2') }}</li>
                  <li>{{ $t('payroll.run_info_3') }}</li>
                </ul>
              </div>
            </div>
          </div>
        </BaseInputGrid>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { required, minValue, maxValue, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const router = useRouter()
const { t } = useI18n()
const notificationStore = useNotificationStore()

let isSaving = ref(false)

const currentDate = new Date()
const currentRun = reactive({
  period_year: currentDate.getFullYear(),
  period_month: currentDate.getMonth() + 1,
  period_start: '',
  period_end: '',
})

const monthOptions = [
  { value: 1, label: t('months.january') },
  { value: 2, label: t('months.february') },
  { value: 3, label: t('months.march') },
  { value: 4, label: t('months.april') },
  { value: 5, label: t('months.may') },
  { value: 6, label: t('months.june') },
  { value: 7, label: t('months.july') },
  { value: 8, label: t('months.august') },
  { value: 9, label: t('months.september') },
  { value: 10, label: t('months.october') },
  { value: 11, label: t('months.november') },
  { value: 12, label: t('months.december') },
]

const rules = computed(() => {
  return {
    currentRun: {
      period_year: {
        required: helpers.withMessage(t('validation.required'), required),
        minValue: helpers.withMessage(t('payroll.invalid_year'), minValue(2020)),
        maxValue: helpers.withMessage(t('payroll.invalid_year'), maxValue(2050)),
      },
      period_month: {
        required: helpers.withMessage(t('validation.required'), required),
        minValue: helpers.withMessage(t('payroll.invalid_month'), minValue(1)),
        maxValue: helpers.withMessage(t('payroll.invalid_month'), maxValue(12)),
      },
      period_start: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      period_end: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(rules, { currentRun })

// Initialize period dates
updatePeriodDates()

function updatePeriodDates() {
  if (currentRun.period_year && currentRun.period_month) {
    const year = parseInt(currentRun.period_year)
    const month = parseInt(currentRun.period_month)

    // First day of the month
    const startDate = new Date(year, month - 1, 1)

    // Last day of the month
    const endDate = new Date(year, month, 0)

    // Format as YYYY-MM-DD
    currentRun.period_start = formatDate(startDate)
    currentRun.period_end = formatDate(endDate)
  }
}

function formatDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  try {
    const response = await axios.post('admin/payroll-runs', currentRun)

    if (response.data && response.data.data) {
      notificationStore.showNotification({
        type: 'success',
        message: t('payroll.run_created'),
      })
      // Redirect to the run details page
      router.push(`/admin/payroll/runs/${response.data.data.id}`)
    }
  } catch (error) {
    console.error('Error creating payroll run:', error)
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
