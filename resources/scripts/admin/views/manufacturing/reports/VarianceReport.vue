<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.variance_report')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.variance_report')" to="#" active />
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
          <p class="text-xs text-gray-500">{{ t('manufacturing.favorable') }}</p>
          <p class="text-2xl font-bold text-green-600">{{ data.summary.total_favorable }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.unfavorable') }}</p>
          <p class="text-2xl font-bold text-red-600">{{ data.summary.total_unfavorable }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow">
          <p class="text-xs text-gray-500">{{ t('manufacturing.net_variance') }}</p>
          <p class="text-lg font-bold" :class="data.summary.net_variance > 0 ? 'text-red-600' : 'text-green-600'">
            {{ data.summary.net_variance > 0 ? '+' : '' }}{{ formatMoney(data.summary.net_variance) }}
          </p>
        </div>
      </div>

      <!-- Orders Table -->
      <div class="rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.variance_by_order') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.order_number') }}</th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.date') }}</th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.output_item') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.planned_quantity') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_quantity') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.normative_cost') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.actual_cost') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.variance') }}</th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase text-gray-500">%</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="order in data.orders" :key="order.id" class="cursor-pointer hover:bg-gray-50" @click="$router.push(`/admin/manufacturing/orders/${order.id}`)">
                <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-primary-600">{{ order.order_number }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-700">{{ order.order_date }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-700">{{ order.item_name }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right text-sm text-gray-700">{{ parseFloat(order.planned_quantity).toFixed(2) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right text-sm text-gray-700">{{ parseFloat(order.actual_quantity).toFixed(2) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right text-sm text-gray-700">{{ formatMoney(order.normative_total) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right text-sm text-gray-700">{{ formatMoney(order.actual_total) }}</td>
                <td class="whitespace-nowrap px-3 py-3 text-right text-sm font-semibold" :class="order.total_variance > 0 ? 'text-red-600' : 'text-green-600'">
                  {{ order.total_variance > 0 ? '+' : '' }}{{ formatMoney(order.total_variance) }}
                </td>
                <td class="whitespace-nowrap px-3 py-3 text-right text-sm" :class="order.variance_percent > 0 ? 'text-red-600' : 'text-green-600'">
                  {{ order.variance_percent > 0 ? '+' : '' }}{{ order.variance_percent }}%
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p v-if="!data.orders.length" class="py-8 text-center text-sm text-gray-500">
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

    const res = await window.axios.get('/manufacturing/reports/variance', { params })
    data.value = res.data.data
  } catch (error) {
    console.error('Failed to fetch variance report:', error)
  } finally {
    isLoading.value = false
  }
}

fetchData()
</script>
