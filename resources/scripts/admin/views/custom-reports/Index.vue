<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton variant="primary" @click="$router.push({ name: 'custom-reports.create' })">
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="PlusIcon" />
          </template>
          {{ t('create') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Loading -->
    <div v-if="isLoading" class="bg-white rounded-lg shadow p-6">
      <div class="space-y-4 animate-pulse">
        <div v-for="i in 4" :key="i" class="flex items-center space-x-4">
          <div class="h-4 bg-gray-200 rounded flex-1"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
      </div>
    </div>

    <!-- Template Cards -->
    <div v-else-if="templates.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="tpl in templates"
        :key="tpl.id"
        class="bg-white rounded-lg shadow hover:shadow-md transition-shadow"
      >
        <div class="p-5">
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <h3 class="text-base font-medium text-gray-900 truncate">{{ tpl.name }}</h3>
              <p class="text-xs text-gray-500 mt-1">
                {{ periodLabel(tpl.period_type) }}
                <span v-if="tpl.group_by" class="ml-1 text-gray-400">
                  / {{ groupByLabel(tpl.group_by) }}
                </span>
              </p>
            </div>
          </div>

          <!-- Filter Info -->
          <div class="mt-3 flex flex-wrap gap-1">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-blue-50 text-blue-700">
              {{ filterLabel(tpl.account_filter) }}
            </span>
            <span
              v-for="col in (tpl.columns || []).slice(0, 4)"
              :key="col"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-600"
            >
              {{ col }}
            </span>
            <span
              v-if="(tpl.columns || []).length > 4"
              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-gray-100 text-gray-400"
            >
              +{{ tpl.columns.length - 4 }}
            </span>
          </div>

          <!-- Comparison badge -->
          <div v-if="tpl.comparison" class="mt-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-purple-50 text-purple-700">
              {{ comparisonLabel(tpl.comparison) }}
            </span>
          </div>

          <!-- Schedule badge -->
          <div v-if="tpl.schedule_cron" class="mt-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs bg-green-50 text-green-700">
              {{ t('schedule') }}: {{ tpl.schedule_cron }}
            </span>
          </div>

          <!-- Meta -->
          <div class="mt-3 text-xs text-gray-400">
            <span v-if="tpl.created_by_user">{{ t('created_by') }}: {{ tpl.created_by_user.name }}</span>
            <span v-if="tpl.updated_at" class="ml-2">{{ formatDate(tpl.updated_at) }}</span>
          </div>
        </div>

        <!-- Actions -->
        <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between rounded-b-lg">
          <div class="flex items-center space-x-2">
            <button
              class="text-primary-600 hover:text-primary-800 text-sm font-medium"
              @click="$router.push({ name: 'custom-reports.view', params: { id: tpl.id } })"
            >
              {{ t('run_report') }}
            </button>
          </div>
          <div class="flex items-center space-x-2">
            <button
              class="text-gray-400 hover:text-gray-600"
              @click.stop="confirmDelete(tpl)"
              :title="$t('general.delete')"
            >
              <BaseIcon name="TrashIcon" class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!isLoading"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-white py-16"
    >
      <BaseIcon name="DocumentChartBarIcon" class="h-12 w-12 text-gray-400" />
      <h3 class="mt-4 text-sm font-medium text-gray-900">{{ t('no_templates') }}</h3>
      <p class="mt-1 text-sm text-gray-500">{{ t('no_templates_desc') }}</p>
      <BaseButton variant="primary" class="mt-4" @click="$router.push({ name: 'custom-reports.create' })">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ t('create') }}
      </BaseButton>
    </div>

  </BasePage>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import crMessages from '@/scripts/admin/i18n/custom-reports.js'

const locale = document.documentElement.lang || 'mk'
const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

function t(key) {
  return crMessages[locale]?.custom_reports?.[key]
    || crMessages['en']?.custom_reports?.[key]
    || key
}

const notificationStore = useNotificationStore()

const templates = ref([])
const isLoading = ref(false)

onMounted(() => {
  loadTemplates()
})

async function loadTemplates() {
  isLoading.value = true
  try {
    const response = await window.axios.get('/custom-reports')
    templates.value = response.data?.data || []
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

function periodLabel(type) {
  const labels = {
    month: t('monthly'),
    quarter: t('quarterly'),
    year: t('yearly'),
    custom: t('custom_period'),
  }
  return labels[type] || type || t('yearly')
}

function groupByLabel(val) {
  const labels = {
    month: t('by_month'),
    quarter: t('by_quarter'),
    cost_center: t('by_cost_center'),
  }
  return labels[val] || val
}

function comparisonLabel(val) {
  const labels = {
    previous_year: t('previous_year'),
    budget: t('budget_comparison'),
  }
  return labels[val] || val
}

function filterLabel(filter) {
  if (!filter) return t('all_accounts')
  const labels = {
    range: t('account_range') + `: ${filter.from || '?'}-${filter.to || '?'}`,
    category: t('account_category'),
    specific: t('specific_accounts') + ` (${(filter.codes || []).length})`,
    all: t('all_accounts'),
  }
  return labels[filter.type] || t('all_accounts')
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

async function confirmDelete(tpl) {
  if (!window.confirm(t('confirm_delete'))) return

  try {
    await window.axios.delete(`/custom-reports/${tpl.id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('template_deleted'),
    })
    await loadTemplates()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.error || t('error_deleting'),
    })
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
