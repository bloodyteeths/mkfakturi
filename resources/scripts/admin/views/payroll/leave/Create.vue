<template>
  <BasePage class="relative">
    <form action="" @submit.prevent="submitForm">
      <!-- Page Header -->
      <BasePageHeader :title="$t('payroll.new_leave_request')" class="mb-5">
        <BaseBreadcrumb>
          <BaseBreadcrumbItem :title="$t('general.home')" to="/admin/dashboard" />
          <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
          <BaseBreadcrumbItem :title="$t('payroll.leave_requests')" to="/admin/payroll/leave" />
          <BaseBreadcrumbItem :title="$t('payroll.new_leave_request')" to="#" active />
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
            {{ $t('payroll.submit_leave_request') }}
          </BaseButton>
        </template>
      </BasePageHeader>

      <BaseCard>
        <BaseInputGrid>
          <!-- Employee -->
          <BaseInputGroup
            :label="$t('payroll.employee')"
            :error="v$.leaveRequest.employee_id.$error && v$.leaveRequest.employee_id.$errors[0].$message"
            required
          >
            <BaseMultiselect
              v-model="leaveRequest.employee_id"
              :options="employees"
              value-prop="id"
              label="full_name"
              searchable
              :placeholder="$t('payroll.select_employee')"
              :invalid="v$.leaveRequest.employee_id.$error"
              @update:modelValue="onEmployeeChange"
            >
              <template #option="{ option }">
                {{ option.first_name }} {{ option.last_name }}
              </template>
              <template #singlelabel="{ value }">
                {{ value.first_name }} {{ value.last_name }}
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <!-- Leave Type -->
          <BaseInputGroup
            :label="$t('payroll.leave_type')"
            :error="v$.leaveRequest.leave_type_id.$error && v$.leaveRequest.leave_type_id.$errors[0].$message"
            required
          >
            <BaseMultiselect
              v-model="leaveRequest.leave_type_id"
              :options="leaveTypes"
              value-prop="id"
              label="name"
              searchable
              :placeholder="$t('payroll.select_leave_type')"
              :invalid="v$.leaveRequest.leave_type_id.$error"
              @update:modelValue="onLeaveTypeChange"
            >
              <template #option="{ option }">
                {{ option.name }} ({{ option.name_mk }})
              </template>
              <template #singlelabel="{ value }">
                {{ value.name }} ({{ value.name_mk }})
              </template>
            </BaseMultiselect>
          </BaseInputGroup>

          <!-- Start Date -->
          <BaseInputGroup
            :label="$t('payroll.start_date')"
            :error="v$.leaveRequest.start_date.$error && v$.leaveRequest.start_date.$errors[0].$message"
            required
          >
            <BaseDatePicker
              v-model="leaveRequest.start_date"
              :calendar-button="true"
              :invalid="v$.leaveRequest.start_date.$error"
              @input="calculateDays"
            />
          </BaseInputGroup>

          <!-- End Date -->
          <BaseInputGroup
            :label="$t('payroll.end_date')"
            :error="v$.leaveRequest.end_date.$error && v$.leaveRequest.end_date.$errors[0].$message"
            required
          >
            <BaseDatePicker
              v-model="leaveRequest.end_date"
              :calendar-button="true"
              :invalid="v$.leaveRequest.end_date.$error"
              @input="calculateDays"
            />
          </BaseInputGroup>

          <!-- Business Days (calculated, read-only) -->
          <BaseInputGroup :label="$t('payroll.business_days')">
            <BaseInput
              :modelValue="businessDays"
              disabled
            />
          </BaseInputGroup>

          <!-- Remaining Balance -->
          <BaseInputGroup :label="$t('payroll.remaining_balance')">
            <BaseInput
              :modelValue="remainingBalance !== null ? `${remainingBalance} ${$t('payroll.days_remaining')}` : '-'"
              disabled
            />
          </BaseInputGroup>

          <!-- Reason -->
          <BaseInputGroup
            :label="$t('payroll.reason')"
            class="col-span-2"
          >
            <BaseTextarea
              v-model="leaveRequest.reason"
              :placeholder="$t('payroll.leave_reason_placeholder')"
              rows="3"
            />
          </BaseInputGroup>
        </BaseInputGrid>
      </BaseCard>
    </form>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { required, helpers } from '@vuelidate/validators'
import useVuelidate from '@vuelidate/core'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const router = useRouter()
const { t } = useI18n()
const notificationStore = useNotificationStore()

let isSaving = ref(false)
let employees = ref([])
let leaveTypes = ref([])
let businessDays = ref(0)
let remainingBalance = ref(null)

const leaveRequest = reactive({
  employee_id: null,
  leave_type_id: null,
  start_date: '',
  end_date: '',
  reason: '',
})

const rules = computed(() => {
  return {
    leaveRequest: {
      employee_id: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      leave_type_id: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      start_date: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      end_date: {
        required: helpers.withMessage(t('validation.required'), required),
      },
    },
  }
})

const v$ = useVuelidate(rules, { leaveRequest })

onMounted(async () => {
  await Promise.all([loadEmployees(), loadLeaveTypes()])
})

async function loadEmployees() {
  try {
    const response = await axios.get('payroll-employees', { params: { limit: 1000 } })
    if (response.data && response.data.data) {
      employees.value = response.data.data.map(emp => ({
        ...emp,
        full_name: `${emp.first_name} ${emp.last_name}`,
      }))
    }
  } catch (error) {
    console.error('Error loading employees:', error)
  }
}

async function loadLeaveTypes() {
  try {
    const response = await axios.get('leave-types')
    if (response.data && response.data.data) {
      leaveTypes.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading leave types:', error)
  }
}

function onEmployeeChange() {
  loadBalance()
}

function onLeaveTypeChange() {
  loadBalance()
}

async function loadBalance() {
  if (!leaveRequest.employee_id || !leaveRequest.leave_type_id) {
    remainingBalance.value = null
    return
  }

  try {
    const response = await axios.get(`leave-requests/balance/${leaveRequest.employee_id}`)
    if (response.data && response.data.data) {
      const balance = response.data.data.find(
        b => b.leave_type_id === leaveRequest.leave_type_id
      )
      remainingBalance.value = balance ? balance.remaining_days : null
    }
  } catch (error) {
    console.error('Error loading balance:', error)
    remainingBalance.value = null
  }
}

function calculateDays() {
  if (!leaveRequest.start_date || !leaveRequest.end_date) {
    businessDays.value = 0
    return
  }

  const start = new Date(leaveRequest.start_date)
  const end = new Date(leaveRequest.end_date)

  if (start > end) {
    businessDays.value = 0
    return
  }

  let count = 0
  const current = new Date(start)
  while (current <= end) {
    const dayOfWeek = current.getDay()
    if (dayOfWeek !== 0 && dayOfWeek !== 6) {
      count++
    }
    current.setDate(current.getDate() + 1)
  }

  businessDays.value = count
}

// Recalculate when dates change
watch(
  () => [leaveRequest.start_date, leaveRequest.end_date],
  () => {
    calculateDays()
  }
)

async function submitForm() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return
  }

  isSaving.value = true

  try {
    const response = await axios.post('leave-requests', {
      employee_id: leaveRequest.employee_id,
      leave_type_id: leaveRequest.leave_type_id,
      start_date: leaveRequest.start_date,
      end_date: leaveRequest.end_date,
      reason: leaveRequest.reason || null,
    })

    if (response.data) {
      notificationStore.showNotification({
        type: 'success',
        message: t('payroll.leave_request_created'),
      })
      router.push('/admin/payroll/leave')
    }
  } catch (error) {
    console.error('Error creating leave request:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  } finally {
    isSaving.value = false
  }
}
</script>

// CLAUDE-CHECKPOINT
