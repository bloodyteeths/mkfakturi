<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          v-if="selectedCompanyId"
          variant="primary-outline"
          @click="activeTab = activeTab === 'history' ? 'overdue' : 'history'"
        >
          {{ activeTab === 'history' ? t('total_overdue') : t('history') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="text-center py-12 bg-white rounded-lg shadow">
      <p class="text-sm text-gray-500">{{ $t('partner.select_company_placeholder') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Summary Cards -->
      <div v-if="summary" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('total_overdue') }}</p>
          <p class="text-2xl font-bold text-red-600">{{ formatMoney(summary.total_overdue_amount) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('invoice_count') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ summary.invoice_count || 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('customer_count') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ summary.customer_count || 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('avg_days') }}</p>
          <p class="text-2xl font-bold text-amber-600">{{ summary.avg_days_overdue || 0 }}</p>
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
            <div class="h-4 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <template v-else>
        <!-- Overdue Tab -->
        <div v-if="activeTab === 'overdue'">
          <div v-if="overdueInvoices.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <BaseIcon name="CheckCircleIcon" class="h-12 w-12 text-green-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">{{ t('no_overdue') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ t('no_overdue_description') }}</p>
          </div>

          <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('due_date') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_due') }}</th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('days_overdue') }}</th>
                  <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('escalation') }}</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="inv in overdueInvoices" :key="inv.id" class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-sm text-gray-900">{{ inv.customer_name }}</td>
                  <td class="px-4 py-3 text-sm text-gray-600">{{ inv.invoice_number }}</td>
                  <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(inv.due_date) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-medium">{{ formatMoney(inv.due_amount) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span :class="daysClass(inv.days_overdue)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                      {{ inv.days_overdue }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center">
                    <span :class="levelClass(inv.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                      {{ t(inv.escalation_level) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <BaseButton size="sm" variant="primary" @click="sendReminder(inv)">
                      {{ t('send_reminder') }}
                    </BaseButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- History Tab -->
        <div v-if="activeTab === 'history'">
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
                    <span :class="levelClass(item.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                      {{ t(item.escalation_level) }}
                    </span>
                  </td>
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
        </div>
      </template>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import collectionMessages from '@/scripts/admin/i18n/collections.js'

const notificationStore = useNotificationStore()
const consoleStore = useConsoleStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  return collectionMessages[locale]?.collections?.[key]
    || collectionMessages['en']?.collections?.[key]
    || key
}

const localeMap = { mk: 'mk-MK', en: 'en-US', tr: 'tr-TR', sq: 'sq-AL' }
const fmtLocale = localeMap[locale] || 'mk-MK'

const companies = computed(() => consoleStore.managedCompanies || [])
const selectedCompanyId = ref(null)
const overdueInvoices = ref([])
const history = ref([])
const summary = ref(null)
const isLoading = ref(false)
const activeTab = ref('overdue')

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '0.00'
  return (cents / 100).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function daysClass(days) {
  if (days > 60) return 'bg-red-100 text-red-800'
  if (days > 30) return 'bg-orange-100 text-orange-800'
  return 'bg-yellow-100 text-yellow-800'
}

function levelClass(level) {
  const map = {
    friendly: 'bg-blue-100 text-blue-800',
    firm: 'bg-yellow-100 text-yellow-800',
    final: 'bg-orange-100 text-orange-800',
    legal: 'bg-red-100 text-red-800',
  }
  return map[level] || 'bg-gray-100 text-gray-800'
}

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/collections${path}`
}

async function loadCompanies() {
  await consoleStore.fetchCompanies()
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  loadData()
}

async function loadData() {
  isLoading.value = true
  try {
    const [overdueRes, histRes] = await Promise.all([
      window.axios.get(partnerApi('/overdue')),
      window.axios.get(partnerApi('/history')),
    ])
    overdueInvoices.value = overdueRes.data.data || []
    summary.value = overdueRes.data.summary || null
    history.value = histRes.data.data || []
  } catch (e) {
    console.error('Failed to load collections data', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load collections data',
    })
  } finally {
    isLoading.value = false
  }
}

async function sendReminder(inv) {
  if (!confirm(t('confirm_send_reminder') || 'Are you sure you want to send this reminder?')) return
  try {
    await window.axios.post(partnerApi('/send-reminder'), {
      invoice_id: inv.id,
      level: inv.escalation_level || 'friendly',
    })
    notificationStore.showNotification({
      type: 'success',
      message: t('reminder_sent_success') || 'Reminder sent successfully.',
    })
    loadData()
  } catch (e) {
    console.error('Failed to send reminder', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_sending') || 'Failed to send reminder',
    })
  }
}

onMounted(() => {
  loadCompanies()
})
// CLAUDE-CHECKPOINT
</script>

<!-- CLAUDE-CHECKPOINT -->
