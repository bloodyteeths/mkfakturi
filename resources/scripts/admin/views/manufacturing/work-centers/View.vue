<template>
  <BasePage>
    <BasePageHeader :title="workCenter?.name || t('manufacturing.work_centers')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.work_centers')" to="/admin/manufacturing/work-centers" />
        <BaseBreadcrumbItem :title="workCenter?.name || '...'" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link :to="`/admin/manufacturing/work-centers/${$route.params.id}`">
          <BaseButton variant="primary-outline" @click.prevent="editMode = true">
            <template #left="slotProps">
              <BaseIcon name="PencilSquareIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="animate-pulse space-y-6">
      <div class="rounded-lg bg-white p-6 shadow"><div class="h-32 rounded bg-gray-200"></div></div>
    </div>

    <template v-else-if="workCenter">
      <!-- OEE Summary -->
      <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:gap-6">
        <div class="rounded-lg bg-white p-4 shadow xl:p-5">
          <span class="text-xs font-medium uppercase tracking-wider text-gray-500">OEE</span>
          <p class="mt-2 text-3xl font-bold" :class="oee.oee >= 85 ? 'text-green-600' : oee.oee >= 60 ? 'text-yellow-600' : 'text-red-600'">
            {{ oee.oee }}%
          </p>
          <p class="mt-1 text-xs text-gray-500">{{ t('manufacturing.dash_oee_target') }}: {{ workCenter.target_oee }}%</p>
        </div>

        <div class="rounded-lg bg-white p-4 shadow xl:p-5">
          <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_oee_availability') }}</span>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ oee.availability }}%</p>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-blue-500" :style="{ width: Math.min(oee.availability, 100) + '%' }"></div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow xl:p-5">
          <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_oee_performance') }}</span>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ oee.performance }}%</p>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-emerald-500" :style="{ width: Math.min(oee.performance, 100) + '%' }"></div>
          </div>
        </div>

        <div class="rounded-lg bg-white p-4 shadow xl:p-5">
          <span class="text-xs font-medium uppercase tracking-wider text-gray-500">{{ t('manufacturing.dash_oee_quality') }}</span>
          <p class="mt-2 text-3xl font-bold text-gray-900">{{ oee.quality }}%</p>
          <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100">
            <div class="h-full rounded-full bg-purple-500" :style="{ width: Math.min(oee.quality, 100) + '%' }"></div>
          </div>
        </div>
      </div>

      <!-- Details -->
      <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-lg bg-white p-5 shadow">
          <h3 class="mb-4 text-base font-semibold text-gray-900">{{ $t('general.details') }}</h3>
          <dl class="space-y-3">
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('manufacturing.bom_code') }}</dt>
              <dd class="font-medium text-gray-900">{{ workCenter.code || '-' }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('manufacturing.capacity_hours') }}</dt>
              <dd class="font-medium text-gray-900">{{ workCenter.capacity_hours_per_day }}h / {{ $t('general.day') }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('manufacturing.hourly_rate') }}</dt>
              <dd class="font-medium text-gray-900">{{ formatMoney(workCenter.hourly_rate) }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('manufacturing.overhead_rate') }}</dt>
              <dd class="font-medium text-gray-900">{{ formatMoney(workCenter.overhead_rate) }}</dd>
            </div>
            <div class="flex justify-between text-sm">
              <dt class="text-gray-500">{{ t('manufacturing.orders') }}</dt>
              <dd class="font-medium text-gray-900">{{ workCenter.production_orders_count || 0 }}</dd>
            </div>
          </dl>
          <p v-if="workCenter.description" class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 text-sm text-gray-600">
            {{ workCenter.description }}
          </p>
        </div>

        <div class="rounded-lg bg-white p-5 shadow">
          <h3 class="mb-4 text-base font-semibold text-gray-900">{{ t('manufacturing.dash_oee_title') }}</h3>
          <div class="space-y-3">
            <div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ t('manufacturing.dash_oee_availability') }}</span>
                <span class="font-medium">{{ oee.availability }}% / {{ workCenter.target_availability }}%</span>
              </div>
              <div class="mt-1 h-3 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-blue-500 transition-all" :style="{ width: Math.min(oee.availability, 100) + '%' }"></div>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ t('manufacturing.dash_oee_performance') }}</span>
                <span class="font-medium">{{ oee.performance }}% / {{ workCenter.target_performance }}%</span>
              </div>
              <div class="mt-1 h-3 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-emerald-500 transition-all" :style="{ width: Math.min(oee.performance, 100) + '%' }"></div>
              </div>
            </div>
            <div>
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">{{ t('manufacturing.dash_oee_quality') }}</span>
                <span class="font-medium">{{ oee.quality }}% / {{ workCenter.target_quality }}%</span>
              </div>
              <div class="mt-1 h-3 overflow-hidden rounded-full bg-gray-100">
                <div class="h-full rounded-full bg-purple-500 transition-all" :style="{ width: Math.min(oee.quality, 100) + '%' }"></div>
              </div>
            </div>
          </div>

          <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 p-3 text-center">
            <p class="text-xs text-gray-500">{{ t('manufacturing.dash_oee_overall') }}</p>
            <p class="mt-1 text-2xl font-bold" :class="oee.oee >= workCenter.target_oee ? 'text-green-600' : 'text-red-600'">
              {{ oee.oee }}%
            </p>
            <p class="text-xs text-gray-400">{{ t('manufacturing.dash_oee_target') }}: {{ workCenter.target_oee }}%</p>
          </div>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const { t } = useI18n()
const route = useRoute()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const workCenter = ref(null)
const oee = ref({ oee: 0, availability: 0, performance: 0, quality: 0, order_count: 0 })

const currencySymbol = computed(() => companyStore.selectedCompanyCurrency?.symbol || 'ден')

function formatMoney(amount) {
  if (!amount) return `0 ${currencySymbol.value}`
  return `${Math.round(amount / 100).toLocaleString('mk-MK')} ${currencySymbol.value}`
}

async function fetchData() {
  isLoading.value = true
  try {
    const res = await window.axios.get(`/manufacturing/work-centers/${route.params.id}`)
    const data = res.data?.data
    if (data) {
      workCenter.value = data
      oee.value = data.oee || oee.value
    }
  } catch (error) {
    console.error('Failed to load work center:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchData)
</script>
