<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.cost_analysis')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.cost_analysis')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap items-end gap-4 rounded-lg bg-white p-4 shadow">
      <BaseInputGroup :label="t('manufacturing.date_from')" class="w-40">
        <BaseInput v-model="filters.from" type="date" />
      </BaseInputGroup>
      <BaseInputGroup :label="t('manufacturing.date_to')" class="w-40">
        <BaseInput v-model="filters.to" type="date" />
      </BaseInputGroup>
      <BaseButton variant="primary" @click="fetchData">
        {{ t('manufacturing.generate_report') }}
      </BaseButton>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4 rounded-lg bg-white p-6 shadow">
      <div v-for="i in 4" :key="i" class="h-4 animate-pulse rounded bg-gray-200"></div>
    </div>

    <template v-if="!isLoading && data">
      <!-- Summary Cards -->
      <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.total_orders') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ data.summary.total_orders }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.total_material_cost') }}</p>
          <p class="text-lg font-bold text-blue-600">{{ formatMoney(data.summary.total_material_cost) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.total_labor_cost') }}</p>
          <p class="text-lg font-bold text-green-600">{{ formatMoney(data.summary.total_labor_cost) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.total_production_cost') }}</p>
          <p class="text-lg font-bold text-primary-600">{{ formatMoney(data.summary.total_production_cost) }}</p>
        </div>
      </div>

      <!-- By Product Table -->
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.cost_by_product') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.output_item') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.orders') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.quantity') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_material_cost') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_labor_cost') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_overhead_cost') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_production_cost') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.cost_per_unit') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="item in data.by_product" :key="item.item_id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ item.item_name }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ item.order_count }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ parseFloat(item.total_quantity).toFixed(2) }} {{ item.unit_name }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ formatMoney(item.total_material_cost) }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ formatMoney(item.total_labor_cost) }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ formatMoney(item.total_overhead_cost) }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">{{ formatMoney(item.total_production_cost) }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-primary-600">{{ formatMoney(item.avg_cost_per_unit) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!data.by_product.length" class="py-8 text-center text-sm text-gray-500">
          {{ t('manufacturing.no_completed_orders') }}
        </p>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useI18n } from 'vue-i18n'

const { t, locale } = useI18n()

const isLoading = ref(false)
const data = ref(null)

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

const filters = reactive({
  from: new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10),
  to: new Date().toISOString().slice(0, 10),
})

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

async function fetchData() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.from) params.from = filters.from
    if (filters.to) params.to = filters.to

    const res = await window.axios.get('/manufacturing/reports/cost-analysis', { params })
    data.value = res.data.data
  } catch (error) {
    console.error('Failed to fetch cost analysis:', error)
  } finally {
    isLoading.value = false
  }
}

fetchData()
</script>
