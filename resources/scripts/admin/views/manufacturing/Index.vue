<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link to="/admin/manufacturing/orders/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('manufacturing.new_order') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- ==================== LOADING STATE ==================== -->
    <div v-if="isLoading" class="space-y-6">
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-6">
        <div v-for="i in 4" :key="i" class="animate-pulse rounded-lg bg-white p-5 shadow">
          <div class="mb-3 h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-8 w-28 rounded bg-gray-200"></div>
        </div>
      </div>
      <div class="animate-pulse rounded-lg bg-white p-6 shadow">
        <div class="mb-4 h-5 w-40 rounded bg-gray-200"></div>
        <div class="space-y-3">
          <div v-for="i in 4" :key="i" class="h-4 rounded bg-gray-200"></div>
        </div>
      </div>
    </div>

    <!-- ==================== EMPTY STATE — GETTING STARTED ==================== -->
    <div v-else-if="isEmpty" class="mx-auto max-w-3xl">
      <div class="rounded-xl border-2 border-dashed border-gray-300 bg-white px-8 py-12 text-center">
        <CogIcon class="mx-auto h-16 w-16 text-gray-300" />
        <h2 class="mt-4 text-xl font-bold text-gray-900">
          {{ t('manufacturing.dash_getting_started') }}
        </h2>
        <p class="mx-auto mt-2 max-w-lg text-sm text-gray-500">
          {{ t('manufacturing.dash_getting_started_desc') }}
        </p>

        <!-- Step by step workflow -->
        <div class="mx-auto mt-8 grid max-w-xl grid-cols-1 gap-4 text-left sm:grid-cols-2">
          <router-link
            to="/admin/manufacturing/boms/create"
            class="group flex items-start rounded-lg border-2 border-gray-200 p-4 transition hover:border-primary-500 hover:shadow-md"
          >
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-bold text-primary-600">
              1
            </div>
            <div class="ml-3">
              <p class="font-semibold text-gray-900 group-hover:text-primary-600">{{ t('manufacturing.dash_step1_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step1_desc') }}</p>
            </div>
          </router-link>

          <router-link
            to="/admin/manufacturing/orders/create"
            class="group flex items-start rounded-lg border-2 border-gray-200 p-4 transition hover:border-green-500 hover:shadow-md"
          >
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-600">
              2
            </div>
            <div class="ml-3">
              <p class="font-semibold text-gray-900 group-hover:text-green-600">{{ t('manufacturing.dash_step2_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step2_desc') }}</p>
            </div>
          </router-link>

          <div class="flex items-start rounded-lg border-2 border-gray-100 bg-gray-50 p-4">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
              3
            </div>
            <div class="ml-3">
              <p class="font-semibold text-gray-700">{{ t('manufacturing.dash_step3_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step3_desc') }}</p>
            </div>
          </div>

          <div class="flex items-start rounded-lg border-2 border-gray-100 bg-gray-50 p-4">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600">
              4
            </div>
            <div class="ml-3">
              <p class="font-semibold text-gray-700">{{ t('manufacturing.dash_step4_title') }}</p>
              <p class="mt-0.5 text-xs text-gray-500">{{ t('manufacturing.dash_step4_desc') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== FULL DASHBOARD ==================== -->
    <template v-else>

      <!-- ROW 1: KPI Cards -->
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-6">
        <!-- Active Orders -->
        <router-link
          to="/admin/manufacturing/orders?status=in_progress"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_active_orders') }}</span>
            <PlayIcon class="h-5 w-5 text-blue-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-gray-900 xl:text-3xl">
            {{ data.kpis.active_orders }}
          </p>
          <p v-if="data.kpis.overdue_count > 0" class="mt-1 text-xs font-medium text-red-600">
            {{ data.kpis.overdue_count }} {{ t('manufacturing.dash_overdue') }}
          </p>
        </router-link>

        <!-- Completed This Month -->
        <router-link
          to="/admin/manufacturing/orders?status=completed"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_completed_month') }}</span>
            <CheckCircleIcon class="h-5 w-5 text-green-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-gray-900 xl:text-3xl">
            {{ data.kpis.completed_this_month }}
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ data.period.label }}</p>
        </router-link>

        <!-- Total Production Cost This Month -->
        <router-link
          to="/admin/manufacturing/reports/cost-analysis"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_production_cost') }}</span>
            <BanknotesIcon class="h-5 w-5 text-indigo-500" />
          </div>
          <p class="mt-2 text-2xl font-bold text-gray-900 xl:text-3xl">
            {{ formatMoney(data.kpis.total_production_cost_month) }}
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ data.period.label }}</p>
        </router-link>

        <!-- Wastage Rate -->
        <router-link
          to="/admin/manufacturing/reports/wastage"
          class="rounded-lg bg-white p-4 shadow transition hover:shadow-md xl:p-5"
        >
          <div class="flex items-center justify-between">
            <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_wastage_rate') }}</span>
            <ExclamationTriangleIcon class="h-5 w-5" :class="data.kpis.wastage_percent > 10 ? 'text-red-500' : 'text-amber-500'" />
          </div>
          <p class="mt-2 text-2xl font-bold xl:text-3xl" :class="data.kpis.wastage_percent > 10 ? 'text-red-600' : 'text-gray-900'">
            {{ data.kpis.wastage_percent }}%
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ t('manufacturing.of_total_production') }}</p>
        </router-link>
      </div>

      <!-- ROW 2: Production Pipeline + Quick Actions -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:mt-6 lg:grid-cols-3 lg:gap-6">

        <!-- Production Pipeline (2/3 width) -->
        <div class="rounded-lg bg-white p-5 shadow lg:col-span-2">
          <div class="mb-4 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_pipeline') }}</h3>
            <router-link to="/admin/manufacturing/orders" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.dash_view_all') }}
            </router-link>
          </div>

          <!-- Pipeline Status Bars -->
          <div class="space-y-3">
            <router-link
              v-for="s in pipelineStages"
              :key="s.key"
              :to="`/admin/manufacturing/orders?status=${s.key}`"
              class="flex items-center rounded-lg p-3 transition hover:bg-gray-50"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg" :class="s.bgClass">
                <component :is="s.icon" class="h-5 w-5" :class="s.iconClass" />
              </div>
              <div class="ml-3 flex-1">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-gray-900">{{ s.label }}</span>
                  <span class="text-sm font-bold" :class="s.countClass">{{ s.count }}</span>
                </div>
                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                  <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="s.barClass"
                    :style="{ width: pipelinePercent(s.count) + '%' }"
                  ></div>
                </div>
              </div>
            </router-link>
          </div>

          <!-- BOM Summary Line -->
          <div class="mt-4 flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 p-3">
            <div class="flex items-center">
              <ClipboardDocumentListIcon class="h-5 w-5 text-primary-500" />
              <span class="ml-2 text-sm text-gray-700">
                {{ t('manufacturing.boms') }}:
                <strong>{{ data.boms.active }}</strong> {{ t('manufacturing.dash_active_of') }} {{ data.boms.total }}
              </span>
            </div>
            <router-link to="/admin/manufacturing/boms" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.dash_manage') }}
            </router-link>
          </div>
        </div>

        <!-- Quick Actions (1/3 width) -->
        <div class="rounded-lg bg-white p-5 shadow">
          <h3 class="mb-4 text-base font-semibold text-gray-900">{{ t('manufacturing.dash_quick_actions') }}</h3>
          <div class="space-y-2">
            <router-link
              to="/admin/manufacturing/boms/create"
              class="group flex items-center rounded-lg border-2 border-gray-200 p-3 transition hover:border-primary-500 hover:shadow-sm"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-primary-100 group-hover:bg-primary-200">
                <ClipboardDocumentListIcon class="h-5 w-5 text-primary-600" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900 group-hover:text-primary-600">{{ t('manufacturing.new_bom') }}</p>
                <p class="text-xs text-gray-500">{{ t('manufacturing.dash_new_bom_desc') }}</p>
              </div>
            </router-link>

            <router-link
              to="/admin/manufacturing/orders/create"
              class="group flex items-center rounded-lg border-2 border-gray-200 p-3 transition hover:border-green-500 hover:shadow-sm"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-green-100 group-hover:bg-green-200">
                <PlusCircleIcon class="h-5 w-5 text-green-600" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900 group-hover:text-green-600">{{ t('manufacturing.new_order') }}</p>
                <p class="text-xs text-gray-500">{{ t('manufacturing.dash_new_order_desc') }}</p>
              </div>
            </router-link>

            <router-link
              to="/admin/manufacturing/reports/cost-analysis"
              class="group flex items-center rounded-lg border-2 border-gray-200 p-3 transition hover:border-blue-500 hover:shadow-sm"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-blue-100 group-hover:bg-blue-200">
                <ChartBarIcon class="h-5 w-5 text-blue-600" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900 group-hover:text-blue-600">{{ t('manufacturing.cost_analysis') }}</p>
                <p class="text-xs text-gray-500">{{ t('manufacturing.dash_cost_analysis_desc') }}</p>
              </div>
            </router-link>

            <router-link
              to="/admin/manufacturing/reports/variance"
              class="group flex items-center rounded-lg border-2 border-gray-200 p-3 transition hover:border-yellow-500 hover:shadow-sm"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-yellow-100 group-hover:bg-yellow-200">
                <ArrowsRightLeftIcon class="h-5 w-5 text-yellow-600" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900 group-hover:text-yellow-600">{{ t('manufacturing.variance_report') }}</p>
                <p class="text-xs text-gray-500">{{ t('manufacturing.dash_variance_desc') }}</p>
              </div>
            </router-link>

            <router-link
              to="/admin/manufacturing/reports/wastage"
              class="group flex items-center rounded-lg border-2 border-gray-200 p-3 transition hover:border-red-500 hover:shadow-sm"
            >
              <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-red-100 group-hover:bg-red-200">
                <ExclamationTriangleIcon class="h-5 w-5 text-red-600" />
              </div>
              <div class="ml-3">
                <p class="text-sm font-medium text-gray-900 group-hover:text-red-600">{{ t('manufacturing.wastage_report') }}</p>
                <p class="text-xs text-gray-500">{{ t('manufacturing.dash_wastage_desc') }}</p>
              </div>
            </router-link>
          </div>
        </div>
      </div>

      <!-- ROW 3: Recent Orders + Top Products -->
      <div class="mt-4 grid grid-cols-1 gap-4 lg:mt-6 lg:grid-cols-3 lg:gap-6">

        <!-- Recent Orders Table (2/3 width) -->
        <div class="rounded-lg bg-white shadow lg:col-span-2">
          <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
            <h3 class="text-base font-semibold text-gray-900">{{ t('manufacturing.dash_recent_orders') }}</h3>
            <router-link to="/admin/manufacturing/orders" class="text-sm font-medium text-primary-500 hover:text-primary-600">
              {{ t('manufacturing.dash_view_all') }}
            </router-link>
          </div>

          <div v-if="data.recent_orders.length > 0" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.order_number') }}</th>
                  <th class="px-4 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.output_item') }}</th>
                  <th class="hidden px-4 py-2.5 text-center text-xs font-medium uppercase tracking-wider text-gray-500 sm:table-cell">{{ t('manufacturing.status') }}</th>
                  <th class="hidden px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500 md:table-cell">{{ t('manufacturing.planned_quantity') }}</th>
                  <th class="px-4 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.total_production_cost') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr
                  v-for="order in data.recent_orders"
                  :key="order.id"
                  class="cursor-pointer transition hover:bg-gray-50"
                  @click="$router.push(`/admin/manufacturing/orders/${order.id}`)"
                >
                  <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-primary-600">
                    {{ order.order_number }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                    {{ order.item_name || '-' }}
                  </td>
                  <td class="hidden whitespace-nowrap px-4 py-3 text-center sm:table-cell">
                    <span :class="statusBadge(order.status)" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold leading-5">
                      {{ t('manufacturing.status_' + order.status) }}
                    </span>
                  </td>
                  <td class="hidden whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900 md:table-cell">
                    {{ order.planned_quantity }}
                  </td>
                  <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium text-gray-900">
                    {{ order.total_production_cost ? formatMoney(order.total_production_cost) : '-' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="px-5 py-8 text-center">
            <p class="text-sm text-gray-500">{{ t('manufacturing.empty_orders') }}</p>
          </div>
        </div>

        <!-- Top Products This Month (1/3 width) -->
        <div class="rounded-lg bg-white p-5 shadow">
          <h3 class="mb-4 text-base font-semibold text-gray-900">{{ t('manufacturing.dash_top_products') }}</h3>

          <div v-if="data.top_products.length > 0" class="space-y-3">
            <div
              v-for="(product, idx) in data.top_products"
              :key="idx"
              class="rounded-lg border border-gray-100 p-3"
            >
              <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-900">{{ product.item_name }}</span>
                <span class="text-xs font-medium text-gray-500">{{ product.orders }} {{ t('manufacturing.dash_orders_label') }}</span>
              </div>
              <div class="mt-1 flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ t('manufacturing.quantity') }}: {{ formatQty(product.quantity) }}</span>
                <span class="text-xs font-semibold text-indigo-600">{{ formatMoney(product.cost) }}</span>
              </div>
            </div>
          </div>

          <div v-else class="py-6 text-center">
            <ChartBarIcon class="mx-auto h-10 w-10 text-gray-300" />
            <p class="mt-2 text-xs text-gray-500">{{ t('manufacturing.no_completed_orders') }}</p>
          </div>
        </div>
      </div>

      <!-- ROW 4: Documents & PDFs Available -->
      <div class="mt-4 lg:mt-6">
        <div class="rounded-lg bg-white p-5 shadow">
          <h3 class="mb-4 text-base font-semibold text-gray-900">{{ t('manufacturing.dash_documents') }}</h3>
          <p class="mb-4 text-sm text-gray-500">{{ t('manufacturing.dash_documents_desc') }}</p>
          <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div
              v-for="doc in availableDocuments"
              :key="doc.key"
              class="flex flex-col items-center rounded-lg border border-gray-200 p-3 text-center"
            >
              <DocumentTextIcon class="h-8 w-8 text-red-400" />
              <span class="mt-2 text-xs font-medium text-gray-900">{{ doc.label }}</span>
              <span class="mt-0.5 text-[10px] text-gray-500">PDF</span>
            </div>
          </div>
        </div>
      </div>

    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import {
  CogIcon,
  PlayIcon,
  CheckCircleIcon,
  BanknotesIcon,
  ExclamationTriangleIcon,
  ClipboardDocumentListIcon,
  PlusCircleIcon,
  ChartBarIcon,
  ArrowsRightLeftIcon,
  DocumentTextIcon,
  PencilSquareIcon,
  XCircleIcon,
} from '@heroicons/vue/24/outline'

const { t } = useI18n()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const data = ref({
  kpis: {
    total_production_cost_month: 0,
    active_orders: 0,
    completed_this_month: 0,
    wastage_percent: 0,
    overdue_count: 0,
    avg_cost_per_unit: 0,
    active_production_cost: 0,
  },
  pipeline: { draft: 0, in_progress: 0, completed: 0, cancelled: 0 },
  boms: { total: 0, active: 0 },
  recent_orders: [],
  top_products: [],
  period: { month: '', label: '' },
})

const currencySymbol = computed(() => companyStore.selectedCompanyCurrency?.symbol || 'ден')

const isEmpty = computed(() => {
  const p = data.value.pipeline
  return p.draft + p.in_progress + p.completed + p.cancelled === 0 && data.value.boms.total === 0
})

const pipelineTotal = computed(() => {
  const p = data.value.pipeline
  return p.draft + p.in_progress + p.completed + p.cancelled
})

function pipelinePercent(count) {
  return pipelineTotal.value > 0 ? Math.round((count / pipelineTotal.value) * 100) : 0
}

const pipelineStages = computed(() => [
  {
    key: 'draft',
    label: t('manufacturing.status_draft'),
    count: data.value.pipeline.draft,
    icon: PencilSquareIcon,
    bgClass: 'bg-gray-100',
    iconClass: 'text-gray-600',
    barClass: 'bg-gray-400',
    countClass: 'text-gray-700',
  },
  {
    key: 'in_progress',
    label: t('manufacturing.status_in_progress'),
    count: data.value.pipeline.in_progress,
    icon: PlayIcon,
    bgClass: 'bg-blue-100',
    iconClass: 'text-blue-600',
    barClass: 'bg-blue-500',
    countClass: 'text-blue-700',
  },
  {
    key: 'completed',
    label: t('manufacturing.status_completed'),
    count: data.value.pipeline.completed,
    icon: CheckCircleIcon,
    bgClass: 'bg-green-100',
    iconClass: 'text-green-600',
    barClass: 'bg-green-500',
    countClass: 'text-green-700',
  },
  {
    key: 'cancelled',
    label: t('manufacturing.status_cancelled'),
    count: data.value.pipeline.cancelled,
    icon: XCircleIcon,
    bgClass: 'bg-red-100',
    iconClass: 'text-red-600',
    barClass: 'bg-red-400',
    countClass: 'text-red-700',
  },
])

const availableDocuments = computed(() => [
  { key: 'order', label: t('manufacturing.print_order') },
  { key: 'costing', label: t('manufacturing.print_costing') },
  { key: 'normativ', label: t('manufacturing.print_normativ') },
  { key: 'priemnica', label: t('manufacturing.print_priemnica') },
  { key: 'izdatnica', label: t('manufacturing.print_izdatnica') },
  { key: 'trebovnica', label: t('manufacturing.print_trebovnica') },
])

function formatMoney(amount) {
  if (!amount) return `0 ${currencySymbol.value}`
  return `${Math.round(amount / 100).toLocaleString('mk-MK')} ${currencySymbol.value}`
}

function formatQty(qty) {
  const num = parseFloat(qty)
  return Number.isInteger(num) ? num.toLocaleString('mk-MK') : num.toLocaleString('mk-MK', { maximumFractionDigits: 2 })
}

function statusBadge(status) {
  return {
    draft: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}

async function fetchDashboard() {
  isLoading.value = true
  try {
    const res = await window.axios.get('/manufacturing/dashboard')
    if (res.data?.data) {
      data.value = res.data.data
    }
  } catch (error) {
    console.error('Failed to fetch manufacturing dashboard:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchDashboard()
})
</script>
