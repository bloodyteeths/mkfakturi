<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
      <!-- BOMs Card -->
      <router-link
        to="/admin/manufacturing/boms"
        class="block rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
      >
        <div class="flex items-center space-x-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-100 text-primary-600">
            <BaseIcon name="ClipboardDocumentListIcon" class="h-6 w-6" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              {{ t('manufacturing.boms') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ bomCount }} {{ t('manufacturing.boms').toLowerCase() }}
            </p>
          </div>
        </div>
      </router-link>

      <!-- Production Orders Card -->
      <router-link
        to="/admin/manufacturing/orders"
        class="block rounded-lg border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
      >
        <div class="flex items-center space-x-4">
          <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 text-green-600">
            <BaseIcon name="CogIcon" class="h-6 w-6" />
          </div>
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              {{ t('manufacturing.orders') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ orderCount }} {{ t('manufacturing.orders').toLowerCase() }}
            </p>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Reports Section -->
    <h3 class="mb-4 mt-8 text-lg font-medium text-gray-900">{{ t('manufacturing.reports') }}</h3>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <router-link
        to="/admin/manufacturing/reports/cost-analysis"
        class="block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
      >
        <div class="flex items-center space-x-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600">
            <BaseIcon name="ChartBarIcon" class="h-5 w-5" />
          </div>
          <div>
            <h4 class="font-medium text-gray-900">{{ t('manufacturing.cost_analysis') }}</h4>
            <p class="text-xs text-gray-500">{{ t('manufacturing.cost_by_product') }}</p>
          </div>
        </div>
      </router-link>

      <router-link
        to="/admin/manufacturing/reports/variance"
        class="block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
      >
        <div class="flex items-center space-x-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-100 text-yellow-600">
            <BaseIcon name="ArrowsRightLeftIcon" class="h-5 w-5" />
          </div>
          <div>
            <h4 class="font-medium text-gray-900">{{ t('manufacturing.variance_report') }}</h4>
            <p class="text-xs text-gray-500">{{ t('manufacturing.variance_by_order') }}</p>
          </div>
        </div>
      </router-link>

      <router-link
        to="/admin/manufacturing/reports/wastage"
        class="block rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
      >
        <div class="flex items-center space-x-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 text-red-600">
            <BaseIcon name="ExclamationTriangleIcon" class="h-5 w-5" />
          </div>
          <div>
            <h4 class="font-medium text-gray-900">{{ t('manufacturing.wastage_report') }}</h4>
            <p class="text-xs text-gray-500">{{ t('manufacturing.wastage_by_material') }}</p>
          </div>
        </div>
      </router-link>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const bomCount = ref(0)
const orderCount = ref(0)

onMounted(async () => {
  try {
    const [bomRes, orderRes] = await Promise.all([
      window.axios.get('/manufacturing/boms', { params: { limit: 1 } }),
      window.axios.get('/manufacturing/orders', { params: { limit: 1 } }),
    ])
    bomCount.value = bomRes.data.meta?.total || 0
    orderCount.value = orderRes.data.meta?.total || 0
  } catch {
    // Counts will stay at 0
  }
})
</script>
