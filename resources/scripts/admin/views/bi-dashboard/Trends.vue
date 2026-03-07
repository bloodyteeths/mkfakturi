<template>
  <BasePage>
    <BasePageHeader :title="t('trends')">
      <template #actions>
        <router-link :to="{ name: 'bi-dashboard.index' }">
          <BaseButton variant="primary-outline" size="sm">{{ t('summary') }}</BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Ratio Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="t('select_ratio')">
        <BaseMultiselect
          v-model="selectedRatio"
          :options="ratioOptions"
          :searchable="true"
          label="label"
          value-prop="value"
          class="w-72"
          @update:model-value="loadTrend"
        />
      </BaseInputGroup>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="text-center py-12">
      <p class="text-sm text-gray-500">{{ $t('general.loading') }}...</p>
    </div>

    <!-- No Data -->
    <div v-else-if="!trendData.length && !isLoading" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ t('no_data') }}</p>
    </div>

    <!-- Trend Chart -->
    <div v-else class="bg-white rounded-lg shadow p-6">
      <h3 class="text-sm font-medium text-gray-700 mb-4">
        {{ selectedRatioLabel }} — {{ t('last_12_months') }}
      </h3>

      <!-- Bar Chart -->
      <div class="flex items-end gap-2 h-48 mb-4 border-b border-gray-200 pb-2">
        <div
          v-for="(point, idx) in trendData"
          :key="idx"
          class="flex-1 flex flex-col items-center justify-end"
        >
          <span class="text-[10px] text-gray-600 mb-1">{{ formatValue(point.value) }}</span>
          <div
            class="w-full rounded-t transition-all"
            :class="barColor(point.value)"
            :style="{ height: barHeight(point.value) + '%' }"
            :title="point.date + ': ' + point.value"
          ></div>
        </div>
      </div>

      <!-- Month labels -->
      <div class="flex gap-2">
        <div v-for="(point, idx) in trendData" :key="'lbl-' + idx" class="flex-1 text-center">
          <span class="text-[10px] text-gray-400">{{ formatMonth(point.date) }}</span>
        </div>
      </div>

      <!-- Month-over-month changes -->
      <div class="mt-6">
        <h4 class="text-sm font-medium text-gray-600 mb-3">{{ t('month_change') }}</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
          <div
            v-for="(change, idx) in monthChanges"
            :key="idx"
            class="rounded-lg p-3 text-center"
            :class="change.value >= 0 ? 'bg-green-50' : 'bg-red-50'"
          >
            <p class="text-xs text-gray-500">{{ change.month }}</p>
            <p class="text-sm font-medium" :class="change.value >= 0 ? 'text-green-700' : 'text-red-700'">
              {{ change.value >= 0 ? '+' : '' }}{{ change.formatted }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import { useNotificationStore } from '@/scripts/stores/notification'
import biMessages from '@/scripts/admin/i18n/bi-dashboard.js'

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return biMessages[locale]?.bi_dashboard?.[key]
    || biMessages['en']?.bi_dashboard?.[key]
    || key
}

const isLoading = ref(false)
const trendData = ref([])
const selectedRatio = ref('current_ratio')

const ratioOptions = [
  { value: 'current_ratio', label: t('current_ratio') },
  { value: 'quick_ratio', label: t('quick_ratio') },
  { value: 'cash_ratio', label: t('cash_ratio') },
  { value: 'gross_margin', label: t('gross_margin') },
  { value: 'net_margin', label: t('net_margin') },
  { value: 'roe', label: t('roe') },
  { value: 'roa', label: t('roa') },
  { value: 'debt_to_equity', label: t('debt_to_equity') },
  { value: 'interest_coverage', label: t('interest_coverage') },
  { value: 'receivable_days', label: t('receivable_days') },
  { value: 'payable_days', label: t('payable_days') },
  { value: 'inventory_turnover', label: t('inventory_turnover') },
  { value: 'altman_z', label: t('altman_z') },
]

const percentRatios = ['gross_margin', 'net_margin', 'roe', 'roa']

const selectedRatioLabel = computed(() => {
  const found = ratioOptions.find(o => o.value === selectedRatio.value)
  return found ? found.label : selectedRatio.value
})

const monthChanges = computed(() => {
  if (trendData.value.length < 2) return []
  const changes = []
  for (let i = 1; i < trendData.value.length; i++) {
    const prev = trendData.value[i - 1].value
    const curr = trendData.value[i].value
    const diff = curr - prev

    let formatted
    if (percentRatios.includes(selectedRatio.value)) {
      formatted = (diff * 100).toFixed(1) + '%'
    } else {
      formatted = diff.toFixed(2)
    }

    changes.push({
      month: formatMonth(trendData.value[i].date),
      value: diff,
      formatted,
    })
  }
  return changes
})

function formatValue(val) {
  if (percentRatios.includes(selectedRatio.value)) {
    return (val * 100).toFixed(1) + '%'
  }
  return Number(val).toFixed(2)
}

function formatMonth(dateStr) {
  if (!dateStr) return ''
  const parts = dateStr.split('-')
  const monthNames = {
    mk: ['Јан', 'Фев', 'Мар', 'Апр', 'Мај', 'Јун', 'Јул', 'Авг', 'Сеп', 'Окт', 'Ное', 'Дек'],
    en: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
    tr: ['Oca', 'Sub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Agu', 'Eyl', 'Eki', 'Kas', 'Ara'],
    sq: ['Jan', 'Shk', 'Mar', 'Pri', 'Maj', 'Qer', 'Kor', 'Gus', 'Sht', 'Tet', 'Nen', 'Dhj'],
  }
  const names = monthNames[locale] || monthNames['en']
  const monthIdx = parseInt(parts[1], 10) - 1
  return names[monthIdx] || parts[1]
}

function barColor(value) {
  if (value < 0) return 'bg-red-400'
  return 'bg-primary-500'
}

function barHeight(value) {
  if (!trendData.value.length) return 0
  const maxVal = Math.max(...trendData.value.map(p => Math.abs(p.value)), 0.001)
  return Math.max((Math.abs(value) / maxVal) * 100, 3)
}

async function loadTrend() {
  isLoading.value = true
  try {
    const { data } = await axios.get('/bi-dashboard/trends', {
      params: { ratio_type: selectedRatio.value, months: 12 },
    })
    trendData.value = data.data?.trends || []
  } catch (e) {
    notificationStore.showNotification({ type: 'error', message: t('error_loading_trends') })
    trendData.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadTrend()
})
</script>

// CLAUDE-CHECKPOINT
