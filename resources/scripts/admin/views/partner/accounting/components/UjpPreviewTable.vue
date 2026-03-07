<template>
  <div>
    <!-- DDV-04 Preview -->
    <template v-if="formCode === 'ddv-04'">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">#</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('position') }}</th>
            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase">{{ t('amount') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr v-for="(value, key) in ddvFields" :key="key" :class="isHighlightField(key) ? 'bg-purple-50 font-semibold' : ''">
            <td class="px-3 py-1.5 text-gray-500 w-12">{{ key }}</td>
            <td class="px-3 py-1.5 text-gray-900">{{ getDdvLabel(key) }}</td>
            <td class="px-3 py-1.5 text-right text-gray-900 tabular-nums">
              {{ value !== 0 ? formatNumber(value) : '' }}
            </td>
          </tr>
        </tbody>
      </table>
    </template>

    <!-- DB Preview (AOP fields) -->
    <template v-else-if="formCode === 'db'">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase w-16">{{ t('aop_code') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('position') }}</th>
            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase w-32">{{ t('amount') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="(section, sKey) in (data?.config?.sections || {})" :key="sKey">
            <tr v-for="field in section.fields" :key="field.aop" :class="getDbRowClass(field)">
              <td class="px-3 py-1.5 text-center text-gray-500 text-xs">{{ field.aop }}</td>
              <td class="px-3 py-1.5 text-gray-900 text-xs">{{ field.label }}</td>
              <td class="px-3 py-1.5 text-right text-gray-900 tabular-nums">
                {{ aopValue(field.aop) !== 0 ? formatNumber(aopValue(field.aop)) : '' }}
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </template>

    <!-- Obrazec 36 Preview -->
    <template v-else-if="formCode === 'obrazec-36'">
      <div v-for="section in ['aktiva', 'pasiva']" :key="section" class="mb-4">
        <h4 class="text-xs font-bold text-gray-700 uppercase mb-2">{{ section === 'aktiva' ? 'AKTИВА' : 'ПАСИВА' }}</h4>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase w-16">{{ t('aop_code') }}</th>
              <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('position') }}</th>
              <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase w-28">{{ t('current_year') }}</th>
              <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase w-28">{{ t('previous_year') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="row in (data?.[section] || [])"
              :key="row.aop"
              :class="getObrazecRowClass(row)"
            >
              <td class="px-3 py-1.5 text-center text-gray-500 text-xs">{{ row.aop }}</td>
              <td
                class="px-3 py-1.5 text-gray-900 text-xs"
                :style="{ paddingLeft: (8 + (row.indent || 0) * 12) + 'px' }"
              >
                {{ row.label }}
              </td>
              <td class="px-3 py-1.5 text-right text-gray-900 tabular-nums text-xs">
                {{ row.current !== 0 ? formatNumber(row.current) : '' }}
              </td>
              <td class="px-3 py-1.5 text-right text-gray-900 tabular-nums text-xs">
                {{ row.previous !== 0 ? formatNumber(row.previous) : '' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>

    <!-- Obrazec 37 Preview -->
    <template v-else-if="formCode === 'obrazec-37'">
      <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 uppercase w-16">{{ t('aop_code') }}</th>
            <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600 uppercase">{{ t('position') }}</th>
            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase w-28">{{ t('current_year') }}</th>
            <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 uppercase w-28">{{ t('previous_year') }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <template v-for="section in ['prihodi', 'rashodi', 'rezultat']" :key="section">
            <tr
              v-for="row in (data?.[section] || [])"
              :key="row.aop"
              :class="getObrazecRowClass(row)"
            >
              <td class="px-3 py-1.5 text-center text-gray-500 text-xs">{{ row.aop }}</td>
              <td
                class="px-3 py-1.5 text-gray-900 text-xs"
                :style="{ paddingLeft: (8 + (row.indent || 0) * 12) + 'px' }"
              >
                {{ row.label }}
              </td>
              <td class="px-3 py-1.5 text-right tabular-nums text-xs" :class="getResultValueClass(row)">
                {{ row.current !== 0 ? formatNumber(Math.abs(row.current)) : '' }}
              </td>
              <td class="px-3 py-1.5 text-right tabular-nums text-xs" :class="getResultValueClass(row)">
                {{ row.previous !== 0 ? formatNumber(Math.abs(row.previous)) : '' }}
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </template>

    <!-- No Data -->
    <div v-if="!data" class="py-8 text-center text-sm text-gray-500">
      {{ t('no_data') }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import ujpMessages from '@/scripts/admin/i18n/ujp-forms.js'

const props = defineProps({
  data: { type: Object, default: null },
  formCode: { type: String, required: true },
})

const locale = document.documentElement.lang || 'mk'
function t(key) {
  const parts = key.split('.')
  let val = ujpMessages[locale]?.ujp_forms
  let fallback = ujpMessages['en']?.ujp_forms
  for (const part of parts) {
    val = val?.[part]
    fallback = fallback?.[part]
  }
  return val || fallback || key
}

// DDV-04 helpers
const ddvFields = computed(() => props.data?.fields || props.data?.data?.fields || {})

const ddvLabels = {
  1: 'Стандардна стапка (18%) — основица',
  2: 'Стандардна стапка (18%) — ДДВ',
  3: 'Намалена стапка (5%) — основица',
  4: 'Намалена стапка (5%) — ДДВ',
  5: 'Нулта стапка — основица',
  6: 'Ослободен промет — основица',
  7: 'Обратна наплата — основица',
  8: 'Обратна наплата — ДДВ',
  9: 'Останат ДДВ',
  10: 'ВКУПЕН ИЗЛЕЗЕН ДДВ',
  11: 'Влезна стандардна (18%) — основица',
  12: 'Влезна стандардна (18%) — ДДВ',
  13: 'Влезна намалена (5%) — основица',
  14: 'Влезна намалена (5%) — ДДВ',
  15: 'Увоз — ДДВ',
  16: 'Обратна наплата влезна',
  17: 'Останат влезен ДДВ',
  18: 'Останат влезен ДДВ (2)',
  19: 'ВКУПЕН ВЛЕЗЕН ДДВ',
  20: 'Нето ДДВ (излезен - влезен)',
  21: 'Корекција +',
  22: 'Корекција -',
  23: 'Корекција (3)',
  24: 'Корекција (4)',
  25: 'Корекција (5)',
  26: 'Корекција (6)',
  27: 'Корекција (7)',
  28: 'ВКУПНИ КОРЕКЦИИ',
  29: 'ДДВ по корекции',
  30: 'Пренесен вишок од претходен период',
  31: 'ДДВ ЗА ПЛАЌАЊЕ / ВРАЌАЊЕ',
  32: 'Барање за враќање',
}

function getDdvLabel(key) {
  return ddvLabels[key] || `Field ${key}`
}

function isHighlightField(key) {
  return [10, 19, 20, 28, 29, 31].includes(Number(key))
}

// DB helpers
function aopValue(aopCode) {
  return props.data?.aop?.[aopCode] || props.data?.data?.aop?.[aopCode] || 0
}

function getDbRowClass(field) {
  const source = field.source || ''
  if (source === 'formula') return 'bg-amber-50 font-semibold'
  if (field.aop === '59') return 'bg-purple-50 font-bold'
  if (['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII'].includes(field.row)) return 'bg-gray-100 font-semibold'
  return ''
}

// Obrazec helpers
function getObrazecRowClass(row) {
  if (row.is_grand_total) return 'bg-gray-800 text-white font-bold'
  if (row.is_total && (row.indent || 0) === 0) return 'bg-purple-50 font-semibold'
  if (row.is_total) return 'bg-gray-50 font-medium'
  if (row.is_result) return 'bg-amber-50'
  return ''
}

function getResultValueClass(row) {
  if (!row.is_result) return 'text-gray-900'
  const aop = row.aop
  if (['226', '230', '234'].includes(aop)) return 'text-red-600'
  if (['225', '229', '233'].includes(aop)) return 'text-green-600'
  if (['243', '244'].includes(aop)) return 'text-gray-900 font-bold'
  return 'text-gray-900'
}

function formatNumber(val) {
  if (val === null || val === undefined) return ''
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(val)
}
</script>

<!-- CLAUDE-CHECKPOINT -->
