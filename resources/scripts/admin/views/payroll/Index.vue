<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('payroll.dashboard')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('payroll.payroll')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          variant="primary"
          @click="$router.push('/admin/payroll/runs/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.new_payroll_run') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 gap-6 mt-6 md:grid-cols-2 lg:grid-cols-4">
      <!-- Active Employees Card -->
      <BaseCard class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('payroll.active_employees') }}
            </p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">
              {{ dashboardStats.active_employees || 0 }}
            </p>
          </div>
          <div class="p-3 bg-primary-100 rounded-lg">
            <BaseIcon name="UsersIcon" class="w-8 h-8 text-primary-600" />
          </div>
        </div>
      </BaseCard>

      <!-- This Month Gross Card -->
      <BaseCard class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('payroll.this_month_gross') }}
            </p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">
              <BaseFormatMoney
                :amount="dashboardStats.current_month_gross || 0"
                :currency="companyStore.selectedCompanyCurrency"
              />
            </p>
          </div>
          <div class="p-3 bg-green-100 rounded-lg">
            <BaseIcon name="BanknotesIcon" class="w-8 h-8 text-green-600" />
          </div>
        </div>
      </BaseCard>

      <!-- This Month Net Card -->
      <BaseCard class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('payroll.this_month_net') }}
            </p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">
              <BaseFormatMoney
                :amount="dashboardStats.current_month_net || 0"
                :currency="companyStore.selectedCompanyCurrency"
              />
            </p>
          </div>
          <div class="p-3 bg-blue-100 rounded-lg">
            <BaseIcon name="CurrencyDollarIcon" class="w-8 h-8 text-blue-600" />
          </div>
        </div>
      </BaseCard>

      <!-- Pending Runs Card -->
      <BaseCard class="p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-600">
              {{ $t('payroll.pending_runs') }}
            </p>
            <p class="mt-2 text-3xl font-semibold text-gray-900">
              {{ dashboardStats.pending_runs || 0 }}
            </p>
          </div>
          <div class="p-3 bg-yellow-100 rounded-lg">
            <BaseIcon name="ClockIcon" class="w-8 h-8 text-yellow-600" />
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 gap-6 mt-8 md:grid-cols-3">
      <!-- Employees -->
      <BaseCard class="p-6 cursor-pointer hover:shadow-lg transition-shadow" @click="$router.push('/admin/payroll/employees')">
        <div class="flex items-center">
          <div class="p-3 bg-primary-100 rounded-lg">
            <BaseIcon name="UsersIcon" class="w-6 h-6 text-primary-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ $t('payroll.employees') }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ $t('payroll.manage_employees') }}
            </p>
          </div>
        </div>
      </BaseCard>

      <!-- Payroll Runs -->
      <BaseCard class="p-6 cursor-pointer hover:shadow-lg transition-shadow" @click="$router.push('/admin/payroll/runs')">
        <div class="flex items-center">
          <div class="p-3 bg-green-100 rounded-lg">
            <BaseIcon name="DocumentTextIcon" class="w-6 h-6 text-green-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ $t('payroll.payroll_runs') }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ $t('payroll.view_payroll_runs') }}
            </p>
          </div>
        </div>
      </BaseCard>

      <!-- Reports -->
      <BaseCard class="p-6 cursor-pointer hover:shadow-lg transition-shadow" @click="$router.push('/admin/payroll/reports/tax-summary')">
        <div class="flex items-center">
          <div class="p-3 bg-blue-100 rounded-lg">
            <BaseIcon name="ChartBarIcon" class="w-6 h-6 text-blue-600" />
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ $t('payroll.reports') }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ $t('payroll.view_reports') }}
            </p>
          </div>
        </div>
      </BaseCard>
    </div>

    <!-- Recent Payroll Runs -->
    <div class="mt-8">
      <h2 class="text-xl font-semibold text-gray-900 mb-4">
        {{ $t('payroll.recent_runs') }}
      </h2>

      <BaseCard v-if="recentRuns.length > 0">
        <BaseTable
          :data="recentRunsData"
          :columns="runsColumns"
        >
          <template #cell-period="{ row }">
            <router-link
              :to="{ path: `/admin/payroll/runs/${row.data.id}` }"
              class="font-medium text-primary-500"
            >
              {{ formatPeriod(row.data.period_year, row.data.period_month) }}
            </router-link>
          </template>

          <template #cell-status="{ row }">
            <span
              :class="statusClass(row.data.status)"
              class="px-2 py-1 text-xs font-semibold rounded-full"
            >
              {{ $t(`payroll.status.${row.data.status}`) }}
            </span>
          </template>

          <template #cell-total_gross="{ row }">
            <BaseFormatMoney
              :amount="row.data.total_gross"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>

          <template #cell-total_net="{ row }">
            <BaseFormatMoney
              :amount="row.data.total_net"
              :currency="companyStore.selectedCompanyCurrency"
            />
          </template>
        </BaseTable>
      </BaseCard>

      <BaseEmptyPlaceholder
        v-else
        :title="$t('payroll.no_runs')"
        :description="$t('payroll.no_runs_description')"
      >
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/payroll/runs/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('payroll.create_first_run') }}
        </BaseButton>
      </BaseEmptyPlaceholder>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import axios from 'axios'

const { t } = useI18n()
const companyStore = useCompanyStore()

const dashboardStats = ref({
  active_employees: 0,
  current_month_gross: 0,
  current_month_net: 0,
  pending_runs: 0,
})

const recentRuns = ref([])

const runsColumns = computed(() => {
  return [
    {
      key: 'period',
      label: t('payroll.period'),
      thClass: 'extra',
      tdClass: 'font-medium text-primary-500',
    },
    {
      key: 'status',
      label: t('payroll.status'),
      thClass: 'extra',
    },
    {
      key: 'total_gross',
      label: t('payroll.total_gross'),
    },
    {
      key: 'total_net',
      label: t('payroll.total_net'),
    },
  ]
})

function recentRunsData() {
  return {
    data: recentRuns.value,
    pagination: {
      totalPages: 1,
      currentPage: 1,
      totalCount: recentRuns.value.length,
      limit: 5,
    },
  }
}

onMounted(async () => {
  await loadDashboardData()
})

async function loadDashboardData() {
  try {
    const response = await axios.get('payroll-reports/statistics')
    if (response.data) {
      dashboardStats.value = response.data.data || dashboardStats.value
      recentRuns.value = response.data.recent_runs || []
    }
  } catch (error) {
    console.error('Error loading payroll dashboard:', error)
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
</script>

// LLM-CHECKPOINT
