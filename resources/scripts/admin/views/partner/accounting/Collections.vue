<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="activeTab = 'templates'"
          v-if="selectedCompanyId && activeTab !== 'templates'"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="DocumentTextIcon" />
          </template>
          {{ t('templates') }}
        </BaseButton>
        <BaseButton
          variant="primary-outline"
          @click="activeTab = 'history'"
          v-if="selectedCompanyId && activeTab !== 'history'"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ClockIcon" />
          </template>
          {{ t('history') }}
        </BaseButton>
        <BaseButton
          variant="primary"
          @click="activeTab = 'overdue'"
          v-if="selectedCompanyId && activeTab !== 'overdue'"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="ExclamationTriangleIcon" />
          </template>
          {{ t('total_overdue') }}
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
      <div v-if="summary" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
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
        <div class="bg-white rounded-lg shadow p-4">
          <p class="text-xs text-gray-500 uppercase">{{ t('total_interest') }}</p>
          <p class="text-2xl font-bold text-red-500">{{ formatMoney(summary.total_interest) }}</p>
          <p class="text-xs text-gray-400 mt-1">{{ summary.interest_rate || 0 }}% {{ t('interest_rate') }}</p>
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
          <!-- Filters -->
          <div class="p-4 bg-white rounded-lg shadow mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <BaseInputGroup :label="t('filter_level')">
                <BaseMultiselect
                  v-model="filters.escalation_level"
                  :options="levelOptions"
                  :searchable="false"
                  label="label"
                  value-prop="value"
                  :placeholder="$t('general.all')"
                />
              </BaseInputGroup>
              <BaseInputGroup :label="t('customer')">
                <BaseInput
                  v-model="filters.search"
                  :placeholder="t('search_placeholder')"
                  type="text"
                  @input="debouncedLoadOverdue"
                />
              </BaseInputGroup>
              <div class="flex items-end">
                <BaseButton variant="primary-outline" @click="loadOverdue">
                  {{ $t('general.filter') }}
                </BaseButton>
              </div>
            </div>
          </div>

          <!-- Aging Report -->
          <div v-if="aging" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-3 border-l-4 border-green-400">
              <p class="text-xs text-gray-500">{{ t('aging_0_30') }}</p>
              <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['0_30']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 border-l-4 border-yellow-400">
              <p class="text-xs text-gray-500">{{ t('aging_31_60') }}</p>
              <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['31_60']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 border-l-4 border-orange-400">
              <p class="text-xs text-gray-500">{{ t('aging_61_90') }}</p>
              <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['61_90']) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-3 border-l-4 border-red-400">
              <p class="text-xs text-gray-500">{{ t('aging_90_plus') }}</p>
              <p class="text-lg font-bold text-gray-900">{{ formatMoney(aging['90_plus']) }}</p>
            </div>
          </div>

          <div v-if="overdueInvoices.length === 0" class="text-center py-16 bg-white rounded-lg shadow">
            <BaseIcon name="CheckCircleIcon" class="h-12 w-12 text-green-400 mx-auto mb-4" />
            <h3 class="text-lg font-medium text-gray-900">{{ t('no_overdue') }}</h3>
            <p class="text-sm text-gray-500 mt-1">{{ t('no_overdue_description') }}</p>
          </div>

          <div v-else class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('customer') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('invoice_number') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ t('due_date') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('amount_due') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('interest') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ t('total_with_interest') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('days_overdue') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('escalation') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ t('reminders_sent') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.actions') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="inv in overdueInvoices" :key="inv.id" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900">{{ inv.customer_name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ inv.invoice_number }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(inv.due_date) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ formatMoney(inv.due_amount) }}</td>
                    <td class="px-4 py-3 text-sm text-right text-red-600">{{ formatMoney(inv.interest) }}</td>
                    <td class="px-4 py-3 text-sm text-right font-bold text-red-700">{{ formatMoney(inv.total_with_interest) }}</td>
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
                    <td class="px-4 py-3 text-center text-sm text-gray-500">{{ inv.reminder_count || 0 }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                      <div class="flex items-center justify-end gap-1">
                        <BaseButton
                          size="sm"
                          variant="primary"
                          @click="openSendDialog(inv)"
                          :disabled="!inv.can_send"
                          :title="inv.can_send ? '' : t('cooldown_active')"
                        >
                          {{ t('send_reminder') }}
                        </BaseButton>
                        <BaseButton
                          size="sm"
                          variant="primary-outline"
                          @click="downloadOpomena(inv.id)"
                          :title="t('download_opomena')"
                        >
                          {{ t('opomena') }}
                        </BaseButton>
                      </div>
                      <p v-if="!inv.can_send" class="text-xs text-amber-600 mt-1">{{ t('cooldown_active') }}</p>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
              <p class="text-sm text-gray-500">
                {{ pagination.total }} {{ t('invoice_count').toLowerCase() }}
              </p>
              <div class="flex items-center gap-2">
                <BaseButton
                  size="sm"
                  variant="primary-outline"
                  :disabled="pagination.page <= 1"
                  @click="goToPage(pagination.page - 1)"
                >
                  &laquo;
                </BaseButton>
                <span class="text-sm text-gray-700">{{ pagination.page }} / {{ pagination.last_page }}</span>
                <BaseButton
                  size="sm"
                  variant="primary-outline"
                  :disabled="pagination.page >= pagination.last_page"
                  @click="goToPage(pagination.page + 1)"
                >
                  &raquo;
                </BaseButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Templates Tab -->
        <div v-if="activeTab === 'templates'">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">{{ t('templates') }}</h3>
            <BaseButton variant="primary" size="sm" @click="openTemplateForm(null)">
              {{ $t('general.add') }}
            </BaseButton>
          </div>

          <div v-if="templates.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
            <p class="text-sm text-gray-500">{{ $t('general.no_data') }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="tpl in templates"
              :key="tpl.id"
              class="bg-white rounded-lg shadow p-4 border-l-4"
              :class="levelBorderClass(tpl.escalation_level)"
            >
              <div class="flex items-center justify-between mb-2">
                <span :class="levelClass(tpl.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                  {{ t(tpl.escalation_level) }}
                </span>
                <div class="flex items-center gap-2">
                  <span v-if="tpl.is_active" class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                    {{ t('active') }}
                  </span>
                </div>
              </div>
              <p class="text-sm font-medium text-gray-900 mb-1">{{ humanize(tpl.subject_mk || tpl.subject_en) }}</p>
              <p class="text-xs text-gray-500 mb-2">{{ t('days_after_due') }}: {{ tpl.days_after_due }}</p>
              <div class="text-xs text-gray-600 mb-3 border rounded p-2 bg-gray-50 max-h-20 overflow-hidden" v-html="humanize(tpl.body_mk || tpl.body_en)"></div>
              <div class="flex justify-end gap-2">
                <BaseButton size="sm" variant="primary-outline" @click="openTemplateForm(tpl)">
                  {{ t('edit_template') }}
                </BaseButton>
                <BaseButton size="sm" variant="danger-outline" @click="deleteTemplate(tpl.id)">
                  {{ t('delete_template') }}
                </BaseButton>
              </div>
            </div>
          </div>
        </div>

        <!-- History Tab -->
        <div v-if="activeTab === 'history'">
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
                <BaseInput v-model="historyFilters.from_date" type="date" />
              </BaseInputGroup>
              <BaseInputGroup :label="t('to_date')">
                <BaseInput v-model="historyFilters.to_date" type="date" />
              </BaseInputGroup>
              <div class="flex items-end">
                <BaseButton variant="primary-outline" @click="loadHistory">
                  {{ $t('general.filter') }}
                </BaseButton>
              </div>
            </div>
          </div>

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
                    <span :class="levelClass(item.escalation_level)" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium">
                      {{ t(item.escalation_level) }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-center text-sm text-gray-500">{{ sentViaLabel(item.sent_via) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-600">{{ formatDateTime(item.sent_at) }}</td>
                  <td class="px-4 py-3 text-center">
                    <BaseIcon v-if="item.paid_at" name="CheckCircleIcon" class="h-4 w-4 text-green-500 mx-auto" />
                    <span v-else class="text-gray-300">-</span>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- History Pagination -->
            <div v-if="historyPagination && historyPagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
              <p class="text-sm text-gray-500">
                {{ historyPagination.total }} {{ t('total_sent').toLowerCase() }}
              </p>
              <div class="flex items-center gap-2">
                <BaseButton
                  size="sm"
                  variant="primary-outline"
                  :disabled="historyPagination.page <= 1"
                  @click="goToHistoryPage(historyPagination.page - 1)"
                >
                  &laquo;
                </BaseButton>
                <span class="text-sm text-gray-700">{{ historyPagination.page }} / {{ historyPagination.last_page }}</span>
                <BaseButton
                  size="sm"
                  variant="primary-outline"
                  :disabled="historyPagination.page >= historyPagination.last_page"
                  @click="goToHistoryPage(historyPagination.page + 1)"
                >
                  &raquo;
                </BaseButton>
              </div>
            </div>
          </div>
        </div>
      </template>
    </template>

    <!-- Send Reminder Dialog -->
    <BaseModal :show="showSendDialog" @close="showSendDialog = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ t('confirm_send_title') }}</h3>
      </template>
      <div v-if="selectedInvoice" class="space-y-4">
        <p class="text-sm text-gray-600">{{ t('confirm_send') }}</p>
        <div class="bg-gray-50 rounded p-3">
          <p class="text-sm"><strong>{{ t('customer') }}:</strong> {{ selectedInvoice.customer_name }}</p>
          <p class="text-sm"><strong>{{ t('invoice_number') }}:</strong> {{ selectedInvoice.invoice_number }}</p>
          <p class="text-sm"><strong>{{ t('amount_due') }}:</strong> {{ formatMoney(selectedInvoice.due_amount) }}</p>
          <p class="text-sm"><strong>{{ t('interest') }}:</strong> {{ formatMoney(selectedInvoice.interest) }}</p>
          <p class="text-sm font-bold"><strong>{{ t('total_with_interest') }}:</strong> {{ formatMoney(selectedInvoice.total_with_interest) }}</p>
        </div>
        <BaseInputGroup :label="t('escalation')">
          <BaseMultiselect
            v-model="sendLevel"
            :options="sendLevelOptions"
            label="label"
            value-prop="value"
          />
        </BaseInputGroup>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="showSendDialog = false">{{ $t('general.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="isSending" @click="confirmSend">{{ t('send_reminder') }}</BaseButton>
      </template>
    </BaseModal>

    <!-- Template Form Modal -->
    <BaseModal :show="showTemplateForm" @close="showTemplateForm = false">
      <template #header>
        <h3 class="text-lg font-medium">{{ editingTemplate ? t('edit_template') : t('save_template') }}</h3>
      </template>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <BaseInputGroup :label="t('escalation')">
            <BaseMultiselect
              v-model="templateForm.escalation_level"
              :options="sendLevelOptions"
              label="label"
              value-prop="value"
            />
          </BaseInputGroup>
          <BaseInputGroup :label="t('days_after_due')">
            <BaseInput v-model="templateForm.days_after_due" type="number" min="1" />
          </BaseInputGroup>
        </div>

        <!-- Placeholder Legend -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <p class="text-xs font-medium text-blue-800 mb-2">{{ t('placeholder_legend_title') }}</p>
          <div class="grid grid-cols-2 gap-x-4 gap-y-1">
            <div v-for="ph in placeholders" :key="ph.token" class="flex items-center gap-2 text-xs">
              <code class="bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded font-mono text-[10px]">{{ ph.token }}</code>
              <span class="text-gray-600">{{ ph.label }}</span>
            </div>
          </div>
        </div>

        <BaseInputGroup :label="t('template_subject') + ' (MK)'">
          <BaseInput v-model="templateForm.subject_mk" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('template_body') + ' (MK)'">
          <BaseTextarea v-model="templateForm.body_mk" rows="4" />
        </BaseInputGroup>

        <!-- Live Preview -->
        <div v-if="templateForm.subject_mk || templateForm.body_mk" class="border rounded-lg overflow-hidden">
          <div class="bg-gray-100 px-3 py-2 border-b">
            <p class="text-xs font-medium text-gray-500 uppercase">{{ t('preview') }}</p>
          </div>
          <div class="p-3 bg-white">
            <p class="text-sm font-medium text-gray-900 mb-2">{{ humanize(templateForm.subject_mk) }}</p>
            <div class="text-sm text-gray-700 prose prose-sm max-w-none" v-html="humanize(templateForm.body_mk)"></div>
          </div>
        </div>

        <BaseInputGroup :label="t('template_subject') + ' (EN)'">
          <BaseInput v-model="templateForm.subject_en" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('template_body') + ' (EN)'">
          <BaseTextarea v-model="templateForm.body_en" rows="4" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('subject_tr')">
          <BaseInput v-model="templateForm.subject_tr" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('body_tr')">
          <BaseTextarea v-model="templateForm.body_tr" rows="4" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('subject_sq')">
          <BaseInput v-model="templateForm.subject_sq" />
        </BaseInputGroup>
        <BaseInputGroup :label="t('body_sq')">
          <BaseTextarea v-model="templateForm.body_sq" rows="4" />
        </BaseInputGroup>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="templateForm.is_active" class="rounded border-gray-300" />
          <span class="text-sm text-gray-700">{{ t('active') }}</span>
        </label>
      </div>
      <template #footer>
        <BaseButton variant="primary-outline" @click="showTemplateForm = false">{{ $t('general.cancel') }}</BaseButton>
        <BaseButton variant="primary" :loading="isSavingTemplate" @click="saveTemplate">{{ t('save_template') }}</BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
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
const templates = ref([])
const effectiveness = ref(null)
const summary = ref(null)
const aging = ref(null)
const pagination = ref(null)
const historyPagination = ref(null)
const isLoading = ref(false)
const activeTab = ref('overdue')

// Send dialog
const showSendDialog = ref(false)
const selectedInvoice = ref(null)
const sendLevel = ref('friendly')
const isSending = ref(false)

// Template form
const showTemplateForm = ref(false)
const editingTemplate = ref(null)
const isSavingTemplate = ref(false)
const templateForm = reactive({
  escalation_level: 'friendly',
  days_after_due: 7,
  subject_mk: '', subject_en: '', subject_tr: '', subject_sq: '',
  body_mk: '', body_en: '', body_tr: '', body_sq: '',
  is_active: true,
})

// History filters
const historyFilters = reactive({
  from_date: '',
  to_date: '',
  page: 1,
})

const levelOptions = [
  { value: null, label: t('level_all') || 'All' },
  { value: 'friendly', label: t('level_friendly') || 'Friendly' },
  { value: 'firm', label: t('level_firm') || 'Firm' },
  { value: 'final', label: t('level_final') || 'Final' },
  { value: 'legal', label: t('level_legal') || 'Legal' },
]

const sendLevelOptions = [
  { value: 'friendly', label: t('level_friendly') || 'Friendly' },
  { value: 'firm', label: t('level_firm') || 'Firm' },
  { value: 'final', label: t('level_final') || 'Final' },
  { value: 'legal', label: t('level_legal') || 'Legal' },
]

// Sample data for preview — replaces {PLACEHOLDERS} with realistic values
const sampleData = {
  '{INVOICE_NUMBER}': 'ФАК-2026-0042',
  '{AMOUNT_DUE}': '24,500.00 ден.',
  '{DUE_DATE}': '15.02.2026',
  '{DAYS_OVERDUE}': '23',
  '{CUSTOMER_NAME}': 'ДООЕЛ Пример',
  '{COMPANY_NAME}': 'Мојата Фирма ДООЕЛ',
  '{TOTAL}': '28,900.00 ден.',
}

const placeholders = [
  { token: '{INVOICE_NUMBER}', label: t('ph_invoice_number') },
  { token: '{AMOUNT_DUE}', label: t('ph_amount_due') },
  { token: '{DUE_DATE}', label: t('ph_due_date') },
  { token: '{DAYS_OVERDUE}', label: t('ph_days_overdue') },
  { token: '{CUSTOMER_NAME}', label: t('ph_customer_name') },
  { token: '{COMPANY_NAME}', label: t('ph_company_name') },
  { token: '{TOTAL}', label: t('ph_total') },
]

function humanize(text) {
  if (!text) return ''
  let result = text
  for (const [token, value] of Object.entries(sampleData)) {
    result = result.replaceAll(token, value)
  }
  return result
}

const filters = reactive({
  escalation_level: null,
  search: '',
  page: 1,
})

// Debounce search
let searchTimeout = null
function debouncedLoadOverdue() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    loadOverdue()
  }, 400)
}

// Computed effectiveness aggregates
const effectivenessTotalSent = computed(() => {
  if (!effectiveness.value?.by_level) return 0
  return Object.values(effectiveness.value.by_level).reduce((sum, l) => sum + (l.total_sent || 0), 0)
})

const effectivenessPaidPct = computed(() => {
  if (!effectiveness.value?.by_level) return 0
  const totalSent = effectivenessTotalSent.value
  if (totalSent === 0) return 0
  const totalPaid = Object.values(effectiveness.value.by_level).reduce((sum, l) => sum + (l.total_paid || 0), 0)
  return Math.round((totalPaid / totalSent) * 100)
})

const effectivenessAvgDays = computed(() => {
  if (!effectiveness.value?.by_level) return 0
  const levels = Object.values(effectiveness.value.by_level).filter(l => l.avg_days_to_pay !== null)
  if (levels.length === 0) return 0
  return Math.round(levels.reduce((sum, l) => sum + l.avg_days_to_pay, 0) / levels.length)
})

function formatMoney(cents) {
  if (cents === null || cents === undefined) return '0.00'
  return (cents / 100).toLocaleString(fmtLocale, { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function formatDate(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function formatDateTime(d) {
  if (!d) return '-'
  return new Date(d).toLocaleDateString(fmtLocale, { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

function daysClass(days) {
  if (days > 60) return 'bg-red-100 text-red-800'
  if (days > 30) return 'bg-orange-100 text-orange-800'
  if (days > 7) return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
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

function levelBorderClass(level) {
  const map = { friendly: 'border-blue-400', firm: 'border-yellow-400', final: 'border-orange-400', legal: 'border-red-400' }
  return map[level] || 'border-gray-400'
}

function levelBarClass(level) {
  const map = { friendly: 'bg-blue-400', firm: 'bg-yellow-400', final: 'bg-orange-400', legal: 'bg-red-400' }
  return map[level] || 'bg-gray-400'
}

function sentViaLabel(via) {
  if (via === 'email') return t('sent_via_email') || 'Email'
  if (via === 'sms') return t('sent_via_sms') || 'SMS'
  return via || '-'
}

function partnerApi(path) {
  return `/partner/companies/${selectedCompanyId.value}/accounting/collections${path}`
}

async function onCompanyChange() {
  if (!selectedCompanyId.value) return
  activeTab.value = 'overdue'
  loadData()
}

async function loadData() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.escalation_level) params.escalation_level = filters.escalation_level
    if (filters.search) params.search = filters.search
    params.page = filters.page

    const histParams = {}
    if (historyFilters.from_date) histParams.from_date = historyFilters.from_date
    if (historyFilters.to_date) histParams.to_date = historyFilters.to_date
    histParams.page = historyFilters.page

    const [overdueRes, histRes, effRes, tplRes] = await Promise.all([
      window.axios.get(partnerApi('/overdue'), { params }),
      window.axios.get(partnerApi('/history'), { params: histParams }),
      window.axios.get(partnerApi('/effectiveness')),
      window.axios.get(partnerApi('/templates')),
    ])
    overdueInvoices.value = overdueRes.data.data || []
    summary.value = overdueRes.data.summary || null
    aging.value = overdueRes.data.aging || null
    pagination.value = overdueRes.data.pagination || null
    history.value = histRes.data.data || []
    historyPagination.value = histRes.data.pagination || null
    effectiveness.value = effRes.data.data || null
    templates.value = tplRes.data.data || []
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

async function loadOverdue() {
  isLoading.value = true
  try {
    const params = {}
    if (filters.escalation_level) params.escalation_level = filters.escalation_level
    if (filters.search) params.search = filters.search
    params.page = filters.page
    const { data } = await window.axios.get(partnerApi('/overdue'), { params })
    overdueInvoices.value = data.data || []
    summary.value = data.summary || null
    aging.value = data.aging || null
    pagination.value = data.pagination || null
  } catch (e) {
    console.error('Failed to load overdue invoices', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_loading') || 'Failed to load overdue invoices',
    })
  } finally {
    isLoading.value = false
  }
}

async function loadHistory() {
  isLoading.value = true
  try {
    const params = {}
    if (historyFilters.from_date) params.from_date = historyFilters.from_date
    if (historyFilters.to_date) params.to_date = historyFilters.to_date
    params.page = historyFilters.page
    const { data } = await window.axios.get(partnerApi('/history'), { params })
    history.value = data.data || []
    historyPagination.value = data.pagination || null
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

function goToPage(page) {
  filters.page = page
  loadOverdue()
}

function goToHistoryPage(page) {
  historyFilters.page = page
  loadHistory()
}

// --- Send Reminder ---
function openSendDialog(inv) {
  selectedInvoice.value = inv
  sendLevel.value = inv.escalation_level || 'friendly'
  showSendDialog.value = true
}

async function confirmSend() {
  if (!selectedInvoice.value) return
  isSending.value = true
  try {
    await window.axios.post(partnerApi('/send-reminder'), {
      invoice_id: selectedInvoice.value.id,
      level: sendLevel.value,
    })
    showSendDialog.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('reminder_sent_success') || 'Reminder sent successfully.',
    })
    loadData()
  } catch (e) {
    console.error('Failed to send reminder', e)
    const msg = e.response?.data?.message || t('error_sending') || 'Failed to send reminder'
    notificationStore.showNotification({ type: 'error', message: msg })
  } finally {
    isSending.value = false
  }
}

// --- Opomena PDF ---
async function downloadOpomena(invoiceId) {
  try {
    const response = await window.axios.get(partnerApi(`/opomena/${invoiceId}`), {
      responseType: 'blob',
    })
    // Check if response is actually a PDF
    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      const err = JSON.parse(text)
      notificationStore.showNotification({ type: 'error', message: err.message || t('error_loading') })
      return
    }
    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `opomena-${invoiceId}.pdf`
    a.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    console.error('Failed to download opomena', e)
    let msg = t('error_loading')
    if (e.response?.data instanceof Blob) {
      try {
        const text = await e.response.data.text()
        const err = JSON.parse(text)
        msg = err.message || msg
      } catch (_) {}
    }
    notificationStore.showNotification({ type: 'error', message: msg })
  }
}

// --- Template CRUD ---
function openTemplateForm(tpl) {
  editingTemplate.value = tpl
  if (tpl) {
    Object.assign(templateForm, {
      escalation_level: tpl.escalation_level,
      days_after_due: tpl.days_after_due,
      subject_mk: tpl.subject_mk || '', subject_en: tpl.subject_en || '',
      subject_tr: tpl.subject_tr || '', subject_sq: tpl.subject_sq || '',
      body_mk: tpl.body_mk || '', body_en: tpl.body_en || '',
      body_tr: tpl.body_tr || '', body_sq: tpl.body_sq || '',
      is_active: tpl.is_active,
    })
  } else {
    Object.assign(templateForm, {
      escalation_level: 'friendly', days_after_due: 7,
      subject_mk: '', subject_en: '', subject_tr: '', subject_sq: '',
      body_mk: '', body_en: '', body_tr: '', body_sq: '',
      is_active: true,
    })
  }
  showTemplateForm.value = true
}

async function saveTemplate() {
  isSavingTemplate.value = true
  try {
    if (editingTemplate.value) {
      await window.axios.put(partnerApi(`/templates/${editingTemplate.value.id}`), templateForm)
    } else {
      await window.axios.post(partnerApi('/templates'), templateForm)
    }
    showTemplateForm.value = false
    notificationStore.showNotification({
      type: 'success',
      message: t('template_saved') || 'Template saved.',
    })
    const { data } = await window.axios.get(partnerApi('/templates'))
    templates.value = data.data || []
  } catch (e) {
    console.error('Failed to save template', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_saving') || 'Failed to save template',
    })
  } finally {
    isSavingTemplate.value = false
  }
}

async function deleteTemplate(id) {
  if (!confirm(t('delete_template') + '?')) return
  try {
    await window.axios.delete(partnerApi(`/templates/${id}`))
    notificationStore.showNotification({
      type: 'success',
      message: t('template_deleted') || 'Template deleted.',
    })
    const { data } = await window.axios.get(partnerApi('/templates'))
    templates.value = data.data || []
  } catch (e) {
    console.error('Failed to delete template', e)
    notificationStore.showNotification({
      type: 'error',
      message: t('error_deleting') || 'Failed to delete template',
    })
  }
}

onMounted(() => {
  consoleStore.fetchCompanies()
})
</script>

// CLAUDE-CHECKPOINT
