<template>
  <BasePage>
    <BasePageHeader :title="bom ? bom.name : t('manufacturing.view_bom')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.boms')" to="/admin/manufacturing/boms" />
        <BaseBreadcrumbItem :title="bom?.code || '...'" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton variant="primary-outline" @click="downloadNormativ">
          {{ t('manufacturing.print_normativ') }}
        </BaseButton>
        <BaseButton variant="primary-outline" @click="duplicateBom" :loading="isDuplicating">
          {{ t('manufacturing.duplicate_bom') }}
        </BaseButton>
        <BaseButton variant="danger" @click="deleteBom">
          {{ t('manufacturing.delete_bom') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="space-y-4 rounded-lg bg-white p-6 shadow">
      <div v-for="i in 6" :key="i" class="h-4 animate-pulse rounded bg-gray-200"></div>
    </div>

    <template v-else-if="bom">
      <!-- Header Info -->
      <div class="rounded-lg bg-white p-6 shadow">
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.bom_code') }}</p>
            <p class="mt-1 text-sm font-semibold text-gray-900">{{ bom.code }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.output_item') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ bom.output_item?.name || '-' }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.output_quantity') }}</p>
            <p class="mt-1 text-sm text-gray-900">
              {{ bom.output_quantity }}
              <span v-if="bom.output_unit" class="text-gray-500">{{ bom.output_unit.name }}</span>
            </p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.version') }}</p>
            <p class="mt-1 text-sm text-gray-900">v{{ bom.version || 1 }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.is_active') }}</p>
            <span
              :class="bom.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'"
              class="mt-1 inline-flex rounded-full px-2 text-xs font-semibold leading-5"
            >
              {{ bom.is_active ? $t('general.yes') : $t('general.no') }}
            </span>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.expected_wastage') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ bom.expected_wastage_percent || 0 }}%</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.created_by') }}</p>
            <p class="mt-1 text-sm text-gray-900">{{ bom.created_by_user?.name || '-' }}</p>
          </div>
        </div>

        <div v-if="bom.description" class="mt-4 border-t border-gray-200 pt-4">
          <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.description') }}</p>
          <p class="mt-1 text-sm text-gray-700">{{ bom.description }}</p>
        </div>
      </div>

      <!-- Material Lines -->
      <div class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.lines') }}</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.material') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.quantity') }}</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.unit') }}</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.wastage_percent') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="(line, i) in bom.lines" :key="line.id">
                <td class="px-4 py-3 text-sm text-gray-500">{{ i + 1 }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ line.item?.name || '-' }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-900">{{ line.quantity }}</td>
                <td class="px-4 py-3 text-sm text-gray-600">{{ line.unit?.name || '-' }}</td>
                <td class="px-4 py-3 text-right text-sm text-gray-600">{{ line.wastage_percent || 0 }}%</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Normative Cost -->
      <div v-if="normativeCost" class="mt-6 rounded-lg bg-white p-6 shadow">
        <h3 class="mb-4 text-lg font-medium text-gray-900">{{ t('manufacturing.normative_cost') }}</h3>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_material_cost') }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(normativeCost.material_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_labor_cost') }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(normativeCost.labor_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_overhead_cost') }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ formatMoney(normativeCost.overhead_cost) }}</p>
          </div>
          <div>
            <p class="text-xs font-medium uppercase text-gray-500">{{ t('manufacturing.total_production_cost') }}</p>
            <p class="mt-1 text-lg font-bold text-primary-600">{{ formatMoney(normativeCost.total_cost) }}</p>
          </div>
        </div>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'

const route = useRoute()
const router = useRouter()
const notificationStore = useNotificationStore()
const { t, locale } = useI18n()

const bom = ref(null)
const normativeCost = ref(null)
const isLoading = ref(true)
const isDuplicating = ref(false)

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '-'
  const fmtLocale = localeMap[locale.value] || 'mk-MK'
  return new Intl.NumberFormat(fmtLocale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(cents / 100)
}

async function fetchBom() {
  isLoading.value = true
  try {
    const response = await window.axios.get(`/manufacturing/boms/${route.params.id}`)
    bom.value = response.data.data
    normativeCost.value = response.data.normative_cost
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

function downloadNormativ() {
  window.open(`/api/v1/manufacturing/boms/${route.params.id}/pdf/normativ?preview=1`, '_blank')
}

async function duplicateBom() {
  isDuplicating.value = true
  try {
    const response = await window.axios.post(`/manufacturing/boms/${route.params.id}/duplicate`)
    notificationStore.showNotification({
      type: 'success',
      message: t('manufacturing.duplicated_success'),
    })
    router.push(`/admin/manufacturing/boms/${response.data.data.id}`)
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isDuplicating.value = false
  }
}

async function deleteBom() {
  if (!confirm(t('manufacturing.confirm_delete'))) return

  try {
    await window.axios.delete(`/manufacturing/boms/${route.params.id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('manufacturing.deleted_success'),
    })
    router.push('/admin/manufacturing/boms')
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.cannot_delete_used'),
    })
  }
}

onMounted(() => fetchBom())
</script>
