<template>
  <BasePage v-if="!isLoading">
    <!-- Page Header -->
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll_runs')" to="/admin/payroll/runs" />
        <BaseBreadcrumbItem :title="pageTitle" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-if="run.status === 'draft'"
          variant="primary-outline"
          @click="calculateRun"
        >
          <template #left="slotProps">
            <BaseIcon name="CalculatorIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.calculate') }}
        </BaseButton>

        <BaseButton
          v-if="run.status === 'calculated'"
          variant="primary"
          class="ml-2"
          @click="approveRun"
        >
          <template #left="slotProps">
            <BaseIcon name="CheckCircleIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.approve') }}
        </BaseButton>

        <BaseButton
          v-if="run.status === 'approved'"
          variant="primary"
          class="ml-2"
          @click="postRun"
        >
          <template #left="slotProps">
            <BaseIcon name="DocumentCheckIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.post_to_gl') }}
        </BaseButton>

        <BaseButton
          v-if="run.status === 'posted'"
          variant="primary"
          class="ml-2"
          @click="markAsPaid"
        >
          <template #left="slotProps">
            <BaseIcon name="BanknotesIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.mark_as_paid') }}
        </BaseButton>

        <BaseButton
          v-if="run.status === 'posted' || run.status === 'paid'"
          variant="primary-outline"
          class="ml-2"
          @click="generateBankFile"
        >
          <template #left="slotProps">
            <BaseIcon name="DocumentArrowDownIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.generate_bank_file') }}
        </BaseButton>

        <BaseButton
          v-if="run.lines && run.lines.length > 0"
          variant="primary-outline"
          class="ml-2"
          @click="downloadAllPayslips"
        >
          <template #left="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.download_all_payslips') }}
        </BaseButton>

        <BaseButton
          v-if="run.status === 'draft' || run.status === 'calculated'"
          variant="danger-outline"
          class="ml-2"
          @click="deleteRun"
        >
          <template #left="slotProps">
            <BaseIcon name="TrashIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.delete') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Run Summary -->
    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-4">
      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.status') }}</p>
        <p class="mt-2">
          <span
            :class="statusClass(run.status)"
            class="px-3 py-1 text-sm font-semibold rounded-full"
          >
            {{ $t(`payroll.status.${run.status}`) }}
          </span>
        </p>
      </BaseCard>

      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_gross') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="run.total_gross"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>

      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_net') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="run.total_net"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>

      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_employer_tax') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="run.total_employer_tax"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>
    </div>

    <!-- Payroll Lines Table -->
    <div class="mt-8">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">
        {{ $t('payroll.payroll_lines') }}
      </h2>

      <BaseCard v-if="run.lines && run.lines.length > 0">
        <BaseTable
          :data="linesData"
          :columns="linesColumns"
        >
          <template #cell-employee="{ row }">
            <div class="font-medium text-gray-900">
              {{ row.data.employee.first_name }} {{ row.data.employee.last_name }}
            </div>
            <div class="text-sm text-gray-500">
              {{ row.data.employee.employee_number }}
            </div>
          </template>

          <template #cell-worked_days="{ row }">
            {{ row.data.worked_days }} / {{ row.data.working_days }}
          </template>

          <template #cell-gross_salary="{ row }">
            <BaseFormatMoney
              :amount="row.data.gross_salary"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>

          <template #cell-employee_deductions="{ row }">
            <BaseFormatMoney
              :amount="calculateEmployeeDeductions(row.data)"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>

          <template #cell-income_tax="{ row }">
            <BaseFormatMoney
              :amount="row.data.income_tax_amount"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>

          <template #cell-net_salary="{ row }">
            <BaseFormatMoney
              :amount="row.data.net_salary"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>

          <template #cell-actions="{ row }">
            <BaseDropdown>
              <template #activator>
                <BaseIcon name="DotsHorizontalIcon" class="h-5 text-gray-500" />
              </template>

              <BaseDropdownItem @click="viewPayslip(row.data.id)">
                <BaseIcon name="DocumentTextIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('payroll.view_payslip') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="downloadPayslip(row.data.id)">
                <BaseIcon name="ArrowDownTrayIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('payroll.download_payslip') }}
              </BaseDropdownItem>
            </BaseDropdown>
          </template>
        </BaseTable>
      </BaseCard>

      <BaseEmptyPlaceholder
        v-else
        :title="$t('payroll.no_lines')"
        :description="$t('payroll.no_lines_description')"
      />
    </div>
  </BasePage>

  <div v-else class="flex items-center justify-center min-h-screen">
    <BaseSpinner />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const isLoading = ref(true)
const run = ref({
  id: null,
  period_year: null,
  period_month: null,
  status: 'draft',
  total_gross: 0,
  total_net: 0,
  total_employer_tax: 0,
  total_employee_tax: 0,
  lines: [],
})

const pageTitle = computed(() => {
  if (run.value.period_year && run.value.period_month) {
    return formatPeriod(run.value.period_year, run.value.period_month)
  }
  return t('payroll.payroll_run')
})

const linesColumns = computed(() => {
  return [
    {
      key: 'employee',
      label: t('payroll.employee'),
      thClass: 'extra',
    },
    {
      key: 'worked_days',
      label: t('payroll.worked_days'),
    },
    {
      key: 'gross_salary',
      label: t('payroll.gross_salary'),
    },
    {
      key: 'employee_deductions',
      label: t('payroll.employee_deductions'),
    },
    {
      key: 'income_tax',
      label: t('payroll.income_tax'),
    },
    {
      key: 'net_salary',
      label: t('payroll.net_salary'),
    },
    {
      key: 'actions',
      label: '',
      sortable: false,
      tdClass: 'text-right text-sm font-medium',
    },
  ]
})

function linesData() {
  return {
    data: run.value.lines || [],
    pagination: {
      totalPages: 1,
      currentPage: 1,
      totalCount: run.value.lines?.length || 0,
      limit: 100,
    },
  }
}

onMounted(async () => {
  await loadRun()
})

async function loadRun() {
  isLoading.value = true
  try {
    const response = await axios.get(`payroll-runs/${route.params.id}`)
    if (response.data && response.data.data) {
      run.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading payroll run:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('general.something_went_wrong'),
    })
    router.push('/admin/payroll/runs')
  } finally {
    isLoading.value = false
  }
}

function formatPeriod(year, month) {
  const monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' })
  return `${monthName} ${year}`
}

function statusClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    calculated: 'bg-blue-100 text-blue-800',
    approved: 'bg-green-100 text-green-800',
    posted: 'bg-purple-100 text-purple-800',
    paid: 'bg-primary-100 text-primary-800',
  }
  return classes[status] || classes.draft
}

function calculateEmployeeDeductions(line) {
  const pension = line.pension_contribution_employee || 0
  const health = line.health_contribution_employee || 0
  const unemployment = line.unemployment_contribution || 0
  const additional = line.additional_contribution || 0
  return pension + health + unemployment + additional
}

async function calculateRun() {
  try {
    await axios.post(`payroll-runs/${run.value.id}/calculate`)
    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.run_calculated'),
    })
    await loadRun()
  } catch (error) {
    console.error('Error calculating run:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

async function approveRun() {
  dialogStore
    .openDialog({
      title: t('payroll.approve_run'),
      message: t('payroll.approve_run_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`payroll-runs/${run.value.id}/approve`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_approved'),
          })
          await loadRun()
        } catch (error) {
          console.error('Error approving run:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

async function postRun() {
  dialogStore
    .openDialog({
      title: t('payroll.post_to_gl'),
      message: t('payroll.post_to_gl_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`payroll-runs/${run.value.id}/post`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_posted'),
          })
          await loadRun()
        } catch (error) {
          console.error('Error posting run:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

async function markAsPaid() {
  dialogStore
    .openDialog({
      title: t('payroll.mark_as_paid'),
      message: t('payroll.mark_as_paid_confirm'),
      yesLabel: t('general.yes'),
      noLabel: t('general.cancel'),
      variant: 'primary',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.post(`payroll-runs/${run.value.id}/mark-paid`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_marked_as_paid'),
          })
          await loadRun()
        } catch (error) {
          console.error('Error marking run as paid:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}

async function generateBankFile() {
  try {
    const response = await axios.get(
      `payroll-runs/${run.value.id}/bank-file`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payroll_payment_${run.value.id}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.bank_file_generated'),
    })
  } catch (error) {
    console.error('Error generating bank file:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

function viewPayslip(lineId) {
  router.push(`/admin/payroll/payslips/${lineId}`)
}

async function downloadPayslip(lineId) {
  try {
    const response = await axios.get(
      `payslips/${lineId}/download`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payslip_${lineId}.pdf`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.payslip_downloaded'),
    })
  } catch (error) {
    console.error('Error downloading payslip:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

async function downloadAllPayslips() {
  try {
    const response = await axios.get(
      `payslips/bulk/${run.value.id}`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payslips_${run.value.period_year}_${run.value.period_month}.zip`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.payslips_downloaded'),
    })
  } catch (error) {
    console.error('Error downloading payslips:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('general.something_went_wrong'),
    })
  }
}

function deleteRun() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('payroll.confirm_delete_run'),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      hideNoButton: false,
    })
    .then(async (res) => {
      if (res) {
        try {
          await axios.delete(`payroll-runs/${run.value.id}`)
          notificationStore.showNotification({
            type: 'success',
            message: t('payroll.run_deleted'),
          })
          router.push('/admin/payroll/runs')
        } catch (error) {
          console.error('Error deleting run:', error)
          notificationStore.showNotification({
            type: 'error',
            message: error.response?.data?.message || t('general.something_went_wrong'),
          })
        }
      }
    })
}
</script>

// LLM-CHECKPOINT
