<template>
  <BasePage v-if="!isLoading">
    <!-- Page Header -->
    <BasePageHeader :title="$t('payroll.payslip')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
        <BaseBreadcrumbItem :title="$t('payroll.payslip')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="downloadPayslip"
        >
          <template #left="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.download_pdf') }}
        </BaseButton>

        <BaseButton
          variant="primary"
          class="ml-2"
          @click="printPayslip"
        >
          <template #left="slotProps">
            <BaseIcon name="PrinterIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.print') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Payslip Content -->
    <div class="mt-6">
      <BaseCard class="p-8">
        <!-- Company & Employee Header -->
        <div class="grid grid-cols-2 gap-8 pb-6 border-b border-gray-200">
          <div>
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ companyStore.selectedCompany?.name }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ companyStore.selectedCompany?.address?.address_street_1 }}
            </p>
            <p class="text-sm text-gray-600">
              {{ companyStore.selectedCompany?.address?.city }}, {{ companyStore.selectedCompany?.address?.zip }}
            </p>
          </div>
          <div class="text-right">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ payslip.employee?.first_name }} {{ payslip.employee?.last_name }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ $t('payroll.employee_number') }}: {{ payslip.employee?.employee_number }}
            </p>
            <p class="text-sm text-gray-600">
              {{ $t('payroll.embg') }}: {{ payslip.employee?.embg }}
            </p>
            <p class="text-sm text-gray-600">
              {{ payslip.employee?.department }} - {{ payslip.employee?.position }}
            </p>
          </div>
        </div>

        <!-- Period Information -->
        <div class="py-6 border-b border-gray-200">
          <h4 class="text-base font-semibold text-gray-900 mb-3">
            {{ $t('payroll.period_information') }}
          </h4>
          <div class="grid grid-cols-3 gap-4">
            <div>
              <p class="text-sm text-gray-600">{{ $t('payroll.period') }}</p>
              <p class="text-base font-medium text-gray-900">
                {{ formatPeriod(payslip.payroll_run?.period_year, payslip.payroll_run?.period_month) }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">{{ $t('payroll.working_days') }}</p>
              <p class="text-base font-medium text-gray-900">
                {{ payslip.working_days }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">{{ $t('payroll.worked_days') }}</p>
              <p class="text-base font-medium text-gray-900">
                {{ payslip.worked_days }}
              </p>
            </div>
          </div>
        </div>

        <!-- Earnings -->
        <div class="py-6 border-b border-gray-200">
          <h4 class="text-base font-semibold text-gray-900 mb-3">
            {{ $t('payroll.earnings') }}
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.gross_salary') }}</span>
              <span class="text-sm font-medium text-gray-900">
                <BaseFormatMoney
                  :amount="payslip.gross_salary"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
            <div v-if="payslip.transport_allowance" class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.transport_allowance') }}</span>
              <span class="text-sm font-medium text-gray-900">
                <BaseFormatMoney
                  :amount="payslip.transport_allowance"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
            <div v-if="payslip.meal_allowance" class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.meal_allowance') }}</span>
              <span class="text-sm font-medium text-gray-900">
                <BaseFormatMoney
                  :amount="payslip.meal_allowance"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
          </div>
        </div>

        <!-- Employee Deductions -->
        <div class="py-6 border-b border-gray-200">
          <h4 class="text-base font-semibold text-gray-900 mb-3">
            {{ $t('payroll.employee_deductions') }}
          </h4>
          <div class="space-y-2">
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.pension_employee') }} (9%)</span>
              <span class="text-sm font-medium text-red-600">
                -<BaseFormatMoney
                  :amount="payslip.pension_contribution_employee"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.health_employee') }} (3.75%)</span>
              <span class="text-sm font-medium text-red-600">
                -<BaseFormatMoney
                  :amount="payslip.health_contribution_employee"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.unemployment') }} (1.2%)</span>
              <span class="text-sm font-medium text-red-600">
                -<BaseFormatMoney
                  :amount="payslip.unemployment_contribution"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
            <div class="flex justify-between">
              <span class="text-sm text-gray-600">{{ $t('payroll.additional') }} (0.5%)</span>
              <span class="text-sm font-medium text-red-600">
                -<BaseFormatMoney
                  :amount="payslip.additional_contribution"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
            <div class="flex justify-between pt-2 border-t border-gray-200">
              <span class="text-sm text-gray-600">{{ $t('payroll.income_tax') }} (10%)</span>
              <span class="text-sm font-medium text-red-600">
                -<BaseFormatMoney
                  :amount="payslip.income_tax_amount"
                  :currency="companyStore.selectedCompanyCurrency"
                />
              </span>
            </div>
          </div>
        </div>

        <!-- Net Salary -->
        <div class="py-6">
          <div class="flex justify-between items-center">
            <h4 class="text-lg font-semibold text-gray-900">
              {{ $t('payroll.net_salary') }}
            </h4>
            <p class="text-2xl font-bold text-primary-600">
              <BaseFormatMoney
                :amount="payslip.net_salary"
                :currency="companyStore.selectedCompanyCurrency"
              />
            </p>
          </div>
        </div>

        <!-- Bank Information -->
        <div class="pt-6 border-t border-gray-200">
          <h4 class="text-base font-semibold text-gray-900 mb-3">
            {{ $t('payroll.payment_information') }}
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <p class="text-sm text-gray-600">{{ $t('payroll.bank_account_iban') }}</p>
              <p class="text-base font-medium text-gray-900">
                {{ payslip.employee?.bank_account_iban }}
              </p>
            </div>
            <div>
              <p class="text-sm text-gray-600">{{ $t('payroll.bank_name') }}</p>
              <p class="text-base font-medium text-gray-900">
                {{ payslip.employee?.bank_name }}
              </p>
            </div>
          </div>
        </div>
      </BaseCard>
    </div>
  </BasePage>

  <div v-else class="flex items-center justify-center min-h-screen">
    <BaseSpinner />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const isLoading = ref(true)
const payslip = ref({
  employee: {},
  payroll_run: {},
  working_days: 0,
  worked_days: 0,
  gross_salary: 0,
  net_salary: 0,
  pension_contribution_employee: 0,
  health_contribution_employee: 0,
  unemployment_contribution: 0,
  additional_contribution: 0,
  income_tax_amount: 0,
  transport_allowance: 0,
  meal_allowance: 0,
})

onMounted(async () => {
  await loadPayslip()
})

async function loadPayslip() {
  isLoading.value = true
  try {
    const response = await axios.get(`payslips/${route.params.id}/preview`)
    if (response.data && response.data.data) {
      payslip.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading payslip:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('general.something_went_wrong'),
    })
    router.push('/admin/payroll')
  } finally {
    isLoading.value = false
  }
}

function formatPeriod(year, month) {
  if (!year || !month) return ''
  const monthName = new Date(year, month - 1).toLocaleString('default', { month: 'long' })
  return `${monthName} ${year}`
}

async function downloadPayslip() {
  try {
    const response = await axios.get(
      `payslips/${route.params.id}/download`,
      { responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `payslip_${route.params.id}.pdf`)
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

function printPayslip() {
  window.print()
}
</script>

// LLM-CHECKPOINT
