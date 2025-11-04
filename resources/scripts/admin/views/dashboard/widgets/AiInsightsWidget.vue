<template>
  <div class="bg-white rounded-lg shadow-md p-6 min-h-[400px]">
    <!-- Widget Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center space-x-3">
        <div class="p-2 bg-purple-100 rounded-lg">
          <SparklesIcon class="w-6 h-6 text-purple-600" />
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">{{ $t('ai.insights.title') }}</h3>
          <p class="text-sm text-gray-500">{{ $t('ai.insights.subtitle') }}</p>
        </div>
      </div>
      <button
        @click="refreshInsights"
        :disabled="isRefreshing"
        class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
        :title="$t('ai.insights.refresh')"
      >
        <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': isRefreshing }" />
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="flex items-center justify-center py-12">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto mb-4"></div>
        <p class="text-gray-500">{{ $t('ai.insights.loading') }}</p>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!insights || insights.length === 0" class="text-center py-12">
      <SparklesIcon class="h-16 w-16 mx-auto text-gray-300 mb-4" />
      <p class="text-gray-500 mb-4">{{ $t('ai.insights.no_insights') }}</p>
      <button
        @click="generateInsights"
        :disabled="isGenerating"
        class="px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="isGenerating">{{ $t('ai.insights.generating') }}</span>
        <span v-else>{{ $t('ai.insights.generate') }}</span>
      </button>
    </div>

    <!-- Insights List -->
    <div v-else class="space-y-4">
      <div
        v-for="insight in insights"
        :key="insight.id"
        :class="[
          'p-4 rounded-lg border-l-4',
          insightBorderColor(insight.type)
        ]"
        class="bg-gray-50"
      >
        <div class="flex items-start">
          <component
            :is="insightIcon(insight.type)"
            :class="insightIconColor(insight.type)"
            class="h-6 w-6 mr-3 flex-shrink-0 mt-0.5"
          />
          <div class="flex-1">
            <h4 class="font-semibold text-gray-900 mb-1">{{ insight.title }}</h4>
            <p class="text-sm text-gray-600 mb-2">{{ insight.description }}</p>
            <div v-if="insight.action" class="flex items-start space-x-2 text-sm">
              <span class="font-medium text-purple-600">💡</span>
              <span class="text-gray-700">{{ insight.action }}</span>
            </div>
            <div v-if="insight.priority" class="mt-2">
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                  priorityBadgeColor(insight.priority)
                ]"
              >
                {{ $t('ai.insights.priority') }}: {{ insight.priority }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer with Last Updated -->
    <div v-if="insights && insights.length > 0" class="mt-6 pt-4 border-t">
      <div class="flex items-center justify-between text-xs text-gray-500">
        <span>{{ $t('ai.insights.last_updated') }}: {{ formatDateTime(lastUpdate) }}</span>
        <span v-if="nextRefresh">{{ $t('ai.insights.next_refresh') }}: {{ formatRelativeTime(nextRefresh) }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'
import {
  SparklesIcon,
  ArrowPathIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline'

const { t } = useI18n()

// Reactive data
const insights = ref([])
const isLoading = ref(true)
const isRefreshing = ref(false)
const isGenerating = ref(false)
const lastUpdate = ref(null)
const nextRefresh = ref(null)
const error = ref(null)

// Methods
async function fetchInsights() {
  try {
    const response = await axios.get('/api/v1/ai/insights')
    insights.value = response.data.insights || []
    lastUpdate.value = response.data.generated_at
    nextRefresh.value = response.data.next_refresh
    error.value = null
  } catch (err) {
    console.error('Failed to fetch AI insights:', err)
    error.value = err.message
    // Don't show error in UI, just log it
  } finally {
    isLoading.value = false
    isRefreshing.value = false
  }
}

async function generateInsights() {
  isGenerating.value = true
  try {
    await axios.post('/api/v1/ai/insights/generate')
    // Poll for results after a short delay
    setTimeout(() => {
      fetchInsights()
      isGenerating.value = false
    }, 3000)
  } catch (err) {
    console.error('Failed to generate AI insights:', err)
    isGenerating.value = false
  }
}

async function refreshInsights() {
  isRefreshing.value = true
  await fetchInsights()
}

function insightIcon(type) {
  switch (type) {
    case 'warning':
      return ExclamationTriangleIcon
    case 'success':
      return CheckCircleIcon
    case 'info':
    default:
      return InformationCircleIcon
  }
}

function insightIconColor(type) {
  switch (type) {
    case 'warning':
      return 'text-red-500'
    case 'success':
      return 'text-green-500'
    case 'info':
    default:
      return 'text-blue-500'
  }
}

function insightBorderColor(type) {
  switch (type) {
    case 'warning':
      return 'border-red-500'
    case 'success':
      return 'border-green-500'
    case 'info':
    default:
      return 'border-blue-500'
  }
}

function priorityBadgeColor(priority) {
  if (priority >= 4) {
    return 'bg-red-100 text-red-800'
  } else if (priority >= 3) {
    return 'bg-yellow-100 text-yellow-800'
  } else {
    return 'bg-gray-100 text-gray-800'
  }
}

function formatDateTime(date) {
  if (!date) return t('general.never')
  try {
    return new Date(date).toLocaleString('mk-MK', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch {
    return date
  }
}

function formatRelativeTime(date) {
  if (!date) return t('general.never')

  const now = new Date()
  const target = new Date(date)
  const diffInMinutes = Math.floor((target - now) / (1000 * 60))

  if (diffInMinutes < 1) return t('ai.insights.in_a_moment')
  if (diffInMinutes < 60) return t('ai.insights.in_minutes', { count: diffInMinutes })

  const diffInHours = Math.floor(diffInMinutes / 60)
  if (diffInHours < 24) return t('ai.insights.in_hours', { count: diffInHours })

  const diffInDays = Math.floor(diffInHours / 24)
  return t('ai.insights.in_days', { count: diffInDays })
}

// Lifecycle
onMounted(() => {
  fetchInsights()
})
</script>

// CLAUDE-CHECKPOINT
