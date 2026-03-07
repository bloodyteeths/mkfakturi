<template>
  <div>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ t('history') }}</h3>
    </div>

    <!-- Effectiveness Summary -->
    <div v-if="effectiveness" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('total_sent') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ effectiveness.total_sent || 0 }}</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('paid_percentage') }}</p>
        <p class="text-2xl font-bold text-green-600">{{ effectiveness.paid_percentage || 0 }}%</p>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <p class="text-xs text-gray-500 uppercase">{{ t('avg_days_to_pay') }}</p>
        <p class="text-2xl font-bold text-amber-600">{{ effectiveness.avg_days_to_pay || 0 }}</p>
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
              :style="{ width: (effectiveness.by_level[level]?.paid_pct || 0) + '%' }"
            ></div>
          </div>
          <span class="text-xs text-gray-500 w-12 text-right">
            {{ effectiveness.by_level[level]?.paid_pct || 0 }}%
          </span>
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
              <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('opened') }}</th>
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
                <BaseIcon v-if="item.opened_at" name="CheckCircleIcon" class="h-4 w-4 text-green-500 mx-auto" />
                <span v-else class="text-gray-300">-</span>
              </td>
              <td class="px-4 py-3 text-center">
                <BaseIcon v-if="item.paid_at" name="CheckCircleIcon" class="h-4 w-4 text-green-500 mx-auto" />
                <span v-else class="text-gray-300">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
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
const isLoading = ref(false)

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

async function loadHistory() {
  isLoading.value = true
  try {
    const [histRes, effRes] = await Promise.all([
      window.axios.get('/collections/history'),
      window.axios.get('/collections/effectiveness'),
    ])
    history.value = histRes.data.data || []
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
// CLAUDE-CHECKPOINT
</script>

<!-- CLAUDE-CHECKPOINT -->
