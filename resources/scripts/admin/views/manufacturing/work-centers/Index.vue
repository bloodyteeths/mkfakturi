<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.work_centers')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.work_centers')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link to="/admin/manufacturing/work-centers/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('manufacturing.new_work_center') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4">
      <div v-for="i in 3" :key="i" class="animate-pulse rounded-lg bg-white p-6 shadow">
        <div class="mb-3 h-5 w-40 rounded bg-gray-200"></div>
        <div class="h-4 w-64 rounded bg-gray-200"></div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="items.length === 0" class="mx-auto max-w-lg rounded-xl border-2 border-dashed border-gray-300 bg-white px-8 py-12 text-center">
      <CogIcon class="mx-auto h-12 w-12 text-gray-300" />
      <h3 class="mt-4 text-lg font-semibold text-gray-900">{{ t('manufacturing.work_centers') }}</h3>
      <p class="mt-2 text-sm text-gray-500">{{ t('manufacturing.dash_oee_subtitle') }}</p>
      <router-link to="/admin/manufacturing/work-centers/create" class="mt-4 inline-block">
        <BaseButton variant="primary">{{ t('manufacturing.new_work_center') }}</BaseButton>
      </router-link>
    </div>

    <!-- Work Center Cards -->
    <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div
        v-for="wc in items"
        :key="wc.id"
        class="rounded-lg bg-white p-5 shadow transition hover:shadow-md cursor-pointer"
        @click="$router.push(`/admin/manufacturing/work-centers/${wc.id}`)"
      >
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-base font-semibold text-gray-900">{{ wc.name }}</h3>
            <p class="text-xs text-gray-500">{{ wc.code }}</p>
          </div>
          <span
            class="rounded-full px-2 py-0.5 text-xs font-medium"
            :class="wc.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'"
          >
            {{ wc.is_active ? $t('general.active') : $t('general.inactive') }}
          </span>
        </div>

        <p v-if="wc.description" class="mt-2 line-clamp-2 text-xs text-gray-500">{{ wc.description }}</p>

        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.capacity_hours') }}</p>
            <p class="text-sm font-bold text-gray-900">{{ wc.capacity_hours_per_day }}h</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.hourly_rate') }}</p>
            <p class="text-sm font-bold text-gray-900">{{ formatMoney(wc.hourly_rate) }}</p>
          </div>
          <div>
            <p class="text-xs text-gray-500">{{ t('manufacturing.orders') }}</p>
            <p class="text-sm font-bold text-gray-900">{{ wc.production_orders_count || 0 }}</p>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { CogIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const items = ref([])

const currencySymbol = computed(() => companyStore.selectedCompanyCurrency?.symbol || 'ден')

function formatMoney(amount) {
  if (!amount) return `0 ${currencySymbol.value}`
  return `${Math.round(amount / 100).toLocaleString('mk-MK')} ${currencySymbol.value}`
}

async function fetchData() {
  isLoading.value = true
  try {
    const res = await window.axios.get('/manufacturing/work-centers')
    items.value = res.data?.data || []
  } catch (error) {
    console.error('Failed to fetch work centers:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)
</script>
