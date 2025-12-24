<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payroll.tax_summary')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="/admin/payroll" />
        <BaseBreadcrumbItem :title="$t('payroll.tax_summary')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="exportToExcel"
        >
          <template #left="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
          {{ $t('general.export_to_excel') }}
        </BaseButton>

        <BaseButton
          v-if="filters.month"
          variant="primary"
          class="ml-2"
          @click="downloadMpinXml"
        >
          <template #left="slotProps">
            <BaseIcon name="DocumentArrowDownIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.download_mpin_xml') }}
        </BaseButton>

        <BaseButton
          variant="primary"
          class="ml-2"
          @click="downloadDdv04Xml"
        >
          <template #left="slotProps">
            <BaseIcon name="DocumentArrowDownIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.download_ddv04_xml') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <BaseInputGroup :label="$t('payroll.year')">
        <BaseInput
          v-model="filters.year"
          type="number"
          :placeholder="$t('payroll.select_year')"
          @input="loadSummary"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.month')">
        <BaseMultiselect
          v-model="filters.month"
          :options="monthOptions"
          value-prop="value"
          label="label"
          :placeholder="$t('payroll.select_month')"
          @update:modelValue="loadSummary"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('payroll.period_range')">
        <div class="flex gap-2">
          <BaseDatePicker
            v-model="filters.start_date"
            :calendar-button="true"
            :placeholder="$t('general.from')"
            @input="loadSummary"
          />
          <BaseDatePicker
            v-model="filters.end_date"
            :calendar-button="true"
            :placeholder="$t('general.to')"
            @input="loadSummary"
          />
        </div>
      </BaseInputGroup>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 lg:grid-cols-4">
      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_gross') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="summary.total_gross"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>

      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_employee_tax') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="summary.total_employee_tax"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>

      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_employer_tax') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="summary.total_employer_tax"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>

      <BaseCard class="p-6">
        <p class="text-sm font-medium text-gray-600">{{ $t('payroll.total_net') }}</p>
        <p class="mt-2 text-2xl font-semibold text-gray-900">
          <BaseFormatMoney
            :amount="summary.total_net"
            :currency="companyStore.selectedCompanyCurrency"
          />
        </p>
      </BaseCard>
    </div>

    <!-- Tax Breakdown Table -->
    <div class="mt-8">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">
        {{ $t('payroll.tax_breakdown') }}
      </h2>

      <BaseCard>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('payroll.tax_type') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('payroll.rate') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  {{ $t('payroll.amount') }}
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.pension_employee') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  9%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.pension_employee"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.pension_employer') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  9%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.pension_employer"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.health_employee') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  3.75%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.health_employee"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.health_employer') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  3.75%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.health_employer"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.unemployment') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  1.2%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.unemployment"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.additional') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  0.5%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.additional"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ $t('payroll.income_tax') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-right">
                  10%
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.income_tax"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
              <tr class="bg-gray-50 font-semibold">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ $t('payroll.total') }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                  <BaseFormatMoney
                    :amount="summary.total_tax"
                    :currency="companyStore.selectedCompanyCurrency"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'

const { t } = useI18n()
const companyStore = useCompanyStore()
const notificationStore = useNotificationStore()

const currentDate = new Date()

const filters = reactive({
  year: currentDate.getFullYear(),
  month: null,
  start_date: '',
  end_date: '',
})

const summary = ref({
  total_gross: 0,
  total_net: 0,
  total_employee_tax: 0,
  total_employer_tax: 0,
  total_tax: 0,
  pension_employee: 0,
  pension_employer: 0,
  health_employee: 0,
  health_employer: 0,
  unemployment: 0,
  additional: 0,
  income_tax: 0,
})

const monthOptions = [
  { value: null, label: t('payroll.all_months') },
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

onMounted(async () => {
  await loadSummary()
})

async function loadSummary() {
  try {
    const params = {
      year: filters.year,
      month: filters.month,
      start_date: filters.start_date,
      end_date: filters.end_date,
    }

    const response = await axios.get('/api/v1/admin/payroll/reports/tax-summary', { params })

    if (response.data && response.data.data) {
      summary.value = response.data.data
    }
  } catch (error) {
    console.error('Error loading tax summary:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('general.something_went_wrong'),
    })
  }
}

async function exportToExcel() {
  try {
    const params = {
      year: filters.year,
      month: filters.month,
      start_date: filters.start_date,
      end_date: filters.end_date,
      format: 'excel',
    }

    const response = await axios.get('/api/v1/admin/payroll/reports/tax-summary', {
      params,
      responseType: 'blob',
    })

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `tax_summary_${filters.year}_${filters.month || 'all'}.xlsx`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.report_exported'),
    })
  } catch (error) {
    console.error('Error exporting report:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('general.something_went_wrong'),
    })
  }
}

async function downloadMpinXml() {
  if (!filters.month) {
    notificationStore.showNotification({
      type: 'error',
      message: t('payroll.select_month_for_mpin'),
    })
    return
  }

  try {
    const params = {
      year: filters.year,
      month: filters.month,
    }

    const response = await axios.get(
      '/api/v1/admin/payroll-reports/download-mpin-xml',
      { params, responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `MPIN_${filters.year}_${String(filters.month).padStart(2, '0')}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.mpin_xml_downloaded'),
    })
  } catch (error) {
    console.error('Error downloading MPIN XML:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('general.something_went_wrong'),
    })
  }
}

async function downloadDdv04Xml() {
  try {
    const params = {
      year: filters.year,
    }

    const response = await axios.get(
      '/api/v1/admin/payroll-reports/download-ddv04-xml',
      { params, responseType: 'blob' }
    )

    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `DDV04_${filters.year}.xml`)
    document.body.appendChild(link)
    link.click()
    link.remove()

    notificationStore.showNotification({
      type: 'success',
      message: t('payroll.ddv04_xml_downloaded'),
    })
  } catch (error) {
    console.error('Error downloading DDV-04 XML:', error)
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('general.something_went_wrong'),
    })
  }
}
</script>

// LLM-CHECKPOINT
