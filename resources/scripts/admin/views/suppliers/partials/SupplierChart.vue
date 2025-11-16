<template>
  <BaseCard class="flex flex-col mt-6">
    <div v-if="suppliersStore.isFetchingView" class="p-8">
      <div class="animate-pulse">
        <div class="h-6 bg-gray-200 rounded w-1/4 mb-4"></div>
        <div class="h-64 bg-gray-200 rounded"></div>
      </div>
    </div>

    <div v-else class="grid grid-cols-12">
      <div class="col-span-12 xl:col-span-9 xxl:col-span-10">
        <div class="flex justify-between mt-1 mb-6">
          <h6 class="flex items-center">
            <BaseIcon name="ChartBarSquareIcon" class="h-5 text-primary-400" />
            {{ $t('dashboard.monthly_chart.title') }}
          </h6>

          <div class="w-40 h-10">
            <BaseMultiselect
              v-model="selectedYear"
              :options="years"
              :allow-empty="false"
              :show-labels="false"
              :placeholder="$t('dashboard.select_year')"
              :can-deselect="false"
              @select="onChangeYear"
            />
          </div>
        </div>

        <LineChart
          v-if="isLoading"
          :invoices="getChartBills"
          :expenses="getChartExpenses"
          :receipts="getReceiptTotals"
          :income="getNetProfits"
          :labels="getChartMonths"
          class="sm:w-full"
        />
      </div>

      <div
        class="
          grid
          col-span-12
          mt-6
          text-center
          xl:mt-0
          sm:grid-cols-4
          xl:text-right xl:col-span-3 xl:grid-cols-1
          xxl:col-span-2
        "
      >
        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_sales') }}
          </span>
          <br />
          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
          >
            <BaseFormatMoney
              :amount="chartData.billsTotal"
              :currency="data.currency"
            />
          </span>
        </div>

        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_receipts') }}
          </span>
          <br />

          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
            style="color: #00c99c"
          >
            <BaseFormatMoney
              :amount="chartData.paymentsTotal"
              :currency="data.currency"
            />
          </span>
        </div>

        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.total_expense') }}
          </span>
          <br />
          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
            style="color: #fb7178"
          >
            <BaseFormatMoney
              :amount="chartData.totalExpenses"
              :currency="data.currency"
            />
          </span>
        </div>

        <div class="px-6 py-2">
          <span class="text-xs leading-5 lg:text-sm">
            {{ $t('dashboard.chart_info.net_income') }}
          </span>
          <br />
          <span
            v-if="isLoading"
            class="block mt-1 text-xl font-semibold leading-8"
            style="color: #5851d8"
          >
            <BaseFormatMoney
              :amount="chartData.netProfit"
              :currency="data.currency"
            />
          </span>
        </div>
      </div>
    </div>

    <SupplierInfo />
  </BaseCard>
</template>

<script setup>
import SupplierInfo from './SupplierInfo.vue'
import LineChart from '@/scripts/admin/components/charts/LineChart.vue'
import { ref, computed, watch, reactive } from 'vue'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const suppliersStore = useSuppliersStore()
const { t } = useI18n()

const route = useRoute()

let isLoading = ref(false)
let chartData = reactive({})
let data = reactive({})
let years = reactive([
  { label: t('dateRange.this_year'), value: 'This year' },
  { label: t('dateRange.previous_year'), value: 'Previous year' },
])
let selectedYear = ref('This year')

const getChartExpenses = computed(() => {
  if (chartData.expenseTotals) {
    return chartData.expenseTotals
  }
  return []
})

const getNetProfits = computed(() => {
  if (chartData.netProfits) {
    return chartData.netProfits
  }
  return []
})

const getChartMonths = computed(() => {
  if (chartData && chartData.months) {
    return chartData.months
  }
  return []
})

const getReceiptTotals = computed(() => {
  if (chartData.paymentTotals) {
    return chartData.paymentTotals
  }
  return []
})

const getChartBills = computed(() => {
  if (chartData.billTotals) {
    return chartData.billTotals
  }

  return []
})

watch(
  route,
  () => {
    if (route.params.id) {
      loadSupplier()
    }
    selectedYear.value = 'This year'
  },
  { immediate: true }
)

async function loadSupplier() {
  isLoading.value = false
  let response = await suppliersStore.fetchViewSupplier({
    id: route.params.id,
  })

  if (response.data) {
    Object.assign(chartData, response.data.meta?.chartData || {})
    Object.assign(data, response.data.data)
  }

  isLoading.value = true
}

async function onChangeYear(yearData) {
  let params = {
    id: route.params.id,
  }

  yearData === 'Previous year'
    ? (params.previous_year = true)
    : (params.this_year = true)

  let response = await suppliersStore.fetchViewSupplier(params)

  if (response.data.meta?.chartData) {
    Object.assign(chartData, response.data.meta.chartData)
  }

  return true
}
</script>
// CLAUDE-CHECKPOINT
