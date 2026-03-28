<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.wastage_report')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.wastage_report')" to="#" active />
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
      <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.total_orders') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ data.summary.total_orders }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.total_wastage_cost') }}</p>
          <p class="text-lg font-bold text-red-600">{{ formatMoney(data.summary.total_wastage_cost) }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.wastage_percent') }}</p>
          <p class="text-lg font-bold" :class="data.summary.wastage_percent_of_total > 5 ? 'text-red-600' : 'text-yellow-600'">
            {{ data.summary.wastage_percent_of_total }}%
          </p>
          <p class="text-xs text-gray-400">{{ t('manufacturing.of_total_production') }}</p>
        </div>
      </div>

      <!-- By Material Table -->
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.wastage_by_material') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.material') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.orders') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.planned_quantity') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_quantity') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.wastage_qty') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.wastage_percent') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.wastage_cost') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="item in data.by_material" :key="item.item_id" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900">{{ item.item_name }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ item.order_count }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ parseFloat(item.total_planned).toFixed(2) }} {{ item.unit_name }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">{{ parseFloat(item.total_actual).toFixed(2) }} {{ item.unit_name }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-red-600 font-medium">{{ parseFloat(item.total_wastage_qty).toFixed(2) }} {{ item.unit_name }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm" :class="item.wastage_percent > 5 ? 'text-red-600 font-bold' : 'text-yellow-600'">
                  {{ item.wastage_percent }}%
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-red-600">{{ formatMoney(item.total_wastage_cost) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!data.by_material.length" class="py-8 text-center text-sm text-gray-500">
          {{ t('manufacturing.no_wastage_data') }}
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

    const res = await window.axios.get('/manufacturing/reports/wastage', { params })
    data.value = res.data.data
  } catch (error) {
    console.error('Failed to fetch wastage report:', error)
  } finally {
    isLoading.value = false
  }
}

fetchData()
</script>
