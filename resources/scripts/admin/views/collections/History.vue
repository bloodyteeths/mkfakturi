<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ t('history') }}</h3>
    </div>

    <!-- Effectiveness Summary -->
    <div v-if="effectiveness" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('total_sent') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ effectivenessTotalSent }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('paid_percentage') }}</p>
        <p class="text-2xl font-bold text-green-600">{{ effectivenessPaidPct }}%</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('avg_days_to_pay') }}</p>
        <p class="text-2xl font-bold text-amber-600">{{ effectivenessAvgDays }}</p>
      </div>
    </div>

    <!-- Effectiveness by Level -->
    <div v-if="effectiveness && effectiveness.by_level" class="bg-white rounded-lg shadow p-4 mb-6">
      <h4 class="text-sm font-medium text-gray-700 mb-3">{{ t('effectiveness_chart') }}</h4>
      <div class="space-y-3">
        <div v-for="level in ['friendly', 'firm', 'final', 'legal']" :key="level" class="flex items-center gap-3">
          <span class="text-xs font-medium text-gray-600 w-20">{{ t(level) }}</span>
          <div class="flex-1 bg-gray-100 rounded-full h-4 overflow-hidden">
            <div
              :class="levelBarClass(level)"
              class="h-full rounded-full transition-all"
              :style="{ width: (effectiveness.by_level[level]?.paid_percentage || 0) + '%' }"
            ></div>
          </div>
          <span class="text-xs text-gray-500 w-12 text-right">
            {{ effectiveness.by_level[level]?.paid_percentage || 0 }}%
          </span>
        </div>
      </div>
    </div>

    <!-- Date Range Filters -->
    <div class="p-4 bg-white rounded-lg shadow mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <BaseInputGroup :label="t('from_date')">
          <BaseInput v-model="filters.from_date" type="date" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('to_date')">
          <BaseInput v-model="filters.to_date" type="date" />
        </BaseInputGroup>
        <div class="flex items-end">
          <BaseButton variant="primary-outline" @click="loadHistory">
            {{ $t('general.filter') }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-6 space-y-4">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse">
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- History Table -->
    <template v-else>
      <div v-if="history.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
        <h3 class="text-lg font-medium text-gray-900">{{ t('no_history') }}</h3>
        <p class="text-sm text-gray-500 mt-1">{{ t('no_history_description') }}</p>
      </div>

      <div v-else class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('escalation') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('sent_via') }}</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('reminder_sent') }}</th>
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('paid') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="item in history" :key="item.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.customer?.name || '-' }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ item.invoice?.invoice_number || '-' }}</td>
              <td class="px-4 py-3 text-center">
                <span :class="levelBadgeClass(item.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                  {{ t(item.escalation_level) }}
                </span>
              </td>
              <td class="px-4 py-3 text-center text-sm text-gray-500">{{ sentViaLabel(item.sent_via) }}</td>
              <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(item.sent_at) }}</td>
              <td class="px-4 py-3 text-center">
                <BaseIcon v-if="item.paid_at" name="CheckCircleIcon" class="h-4 w-4 text-green-500 mx-auto" />
                <span v-else class="text-gray-300">-</span>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
          <p class="text-sm text-gray-500">{{ pagination.total }} {{ t('total_sent').toLowerCase() }}</p>
          <div class="flex items-center gap-2">
            <BaseButton size="sm" variant="primary-outline" :disabled="pagination.page <= 1" @click="goToPage(pagination.page - 1)">&laquo;</BaseButton>
            <span class="text-sm text-gray-700">{{ pagination.page }} / {{ pagination.last_page }}</span>
            <BaseButton size="sm" variant="primary-outline" :disabled="pagination.page >= pagination.last_page" @click="goToPage(pagination.page + 1)">&raquo;</BaseButton>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import collectionMessages from '@/scripts/admin/i18n/collections.js'

const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return collectionMessages[locale]?.collections?.[key]
    || collectionMessages['en']?.collections?.[key]
    || key
}

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

const history = ref([])
const effectiveness = ref(null)
const pagination = ref(null)
const isLoading = ref(false)

const filters = reactive({
  from_date: '',
  to_date: '',
  page: 1,
})

const effectivenessTotalSent = computed(() => {
  if (!effectiveness.value?.by_level) return 0
  return Object.values(effectiveness.value.by_level).reduce((sum, l) => sum + (l.total_sent || 0), 0)
})

const effectivenessPaidPct = computed(() => {
  const totalSent = effectivenessTotalSent.value
  if (totalSent === 0) return 0
  const totalPaid = Object.values(effectiveness.value.by_level).reduce((sum, l) => sum + (l.total_paid || 0), 0)
  return Math.round((totalPaid / totalSent) * 100 * 10) / 10
})

const effectivenessAvgDays = computed(() => {
  if (!effectiveness.value?.by_level) return 0
  const levels = Object.values(effectiveness.value.by_level).filter(l => l.avg_days_to_pay !== null)
  if (levels.length === 0) return 0
  return Math.round(levels.reduce((sum, l) => sum + l.avg_days_to_pay, 0) / levels.length)
})

function levelBadgeClass(level) {
  const map = {
    friendly: 'bg-blue-100 text-blue-800',
    firm: 'bg-yellow-100 text-yellow-800',
    final: 'bg-orange-100 text-orange-800',
    legal: 'bg-red-100 text-red-800',
  }
  return map[level] || 'bg-gray-100 text-gray-800'
}

function levelBarClass(level) {
  const map = { friendly: 'bg-blue-400', firm: 'bg-yellow-400', final: 'bg-orange-400', legal: 'bg-red-400' }
  return map[level] || 'bg-gray-400'
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function sentViaLabel(via) {
  if (via === 'email') return t('sent_via_email') || 'Email'
  if (via === 'sms') return t('sent_via_sms') || 'SMS'
  return via || '-'
}

function goToPage(page) {
  filters.page = page
  loadHistory()
}

async function loadHistory() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date
    params.page = filters.page

    const [histRes, effRes] = await Promise.all([
      window.axios.get('/collections/history', { params }),
      window.axios.get('/collections/effectiveness'),
    ])
    history.value = histRes.data.data || []
    pagination.value = histRes.data.pagination || null
    effectiveness.value = effRes.data.data || null
  } catch (e) {
    console.error('Failed to load history', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load history',
    })
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadHistory()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
