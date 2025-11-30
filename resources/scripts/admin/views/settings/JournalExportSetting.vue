<template>
  <BaseSettingCard
    :title="$t('settings.journal_export.title')"
    :description="$t('settings.journal_export.description')"
  >
    <!-- Date Range Selection -->
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
      <BaseInputGroup :label="$t('settings.journal_export.from_date')" required>
        <BaseDatePicker v-model="fromDate" :placeholder="$t('general.select_date')" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('settings.journal_export.to_date')" required>
        <BaseDatePicker v-model="toDate" :placeholder="$t('general.select_date')" />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('settings.journal_export.format')" required>
        <BaseMultiselect
          v-model="selectedFormat"
          :options="exportFormats"
          :searchable="false"
          track-by="value"
          label="label"
          value-prop="value"
        />
      </BaseInputGroup>
    </div>

    <!-- Preview Button -->
    <div class="mb-6 flex gap-3">
      <BaseButton
        variant="primary-outline"
        :loading="isLoading"
        @click="loadPreview"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="EyeIcon" />
        </template>
        {{ $t('settings.journal_export.preview') }}
      </BaseButton>

      <BaseButton
        variant="primary"
        :loading="isExporting"
        :disabled="!hasEntries"
        @click="downloadExport"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
        </template>
        {{ $t('settings.journal_export.export') }}
      </BaseButton>
    </div>

    <!-- Summary Card -->
    <div
      v-if="summary"
      class="mb-6 rounded-lg border border-gray-200 bg-gray-50 p-4"
    >
      <h3 class="mb-3 text-sm font-medium text-gray-900">
        {{ $t('settings.journal_export.summary') }}
      </h3>
      <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <div>
          <p class="text-xs text-gray-500">
            {{ $t('settings.journal_export.invoices') }}
          </p>
          <p class="text-lg font-semibold text-gray-900">
            {{ summary.invoice_count }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-500">
            {{ $t('settings.journal_export.payments') }}
          </p>
          <p class="text-lg font-semibold text-gray-900">
            {{ summary.payment_count }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-500">
            {{ $t('settings.journal_export.expenses') }}
          </p>
          <p class="text-lg font-semibold text-gray-900">
            {{ summary.expense_count }}
          </p>
        </div>
        <div>
          <p class="text-xs text-gray-500">
            {{ $t('settings.journal_export.total_entries') }}
          </p>
          <p class="text-lg font-semibold text-gray-900">
            {{ summary.entry_count }}
          </p>
        </div>
      </div>

      <!-- Balance Check -->
      <div class="mt-4 flex items-center gap-2">
        <BaseBadge
          :bg-color="summary.is_balanced ? 'bg-green-100' : 'bg-red-100'"
          :text-color="summary.is_balanced ? 'text-green-800' : 'text-red-800'"
        >
          {{ summary.is_balanced ? $t('settings.journal_export.balanced') : $t('settings.journal_export.unbalanced') }}
        </BaseBadge>
        <span class="text-sm text-gray-500">
          {{ $t('settings.journal_export.debit') }}: {{ formatMoney(summary.total_debit) }} |
          {{ $t('settings.journal_export.credit') }}: {{ formatMoney(summary.total_credit) }}
        </span>
      </div>
    </div>

    <!-- Preview Table -->
    <div v-if="entries.length > 0" class="overflow-hidden rounded-lg border border-gray-200">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
            >
              {{ $t('settings.journal_export.date') }}
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
            >
              {{ $t('settings.journal_export.reference') }}
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
            >
              {{ $t('settings.journal_export.account') }}
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
            >
              {{ $t('settings.journal_export.description_col') }}
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
            >
              {{ $t('settings.journal_export.debit') }}
            </th>
            <th
              class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500"
            >
              {{ $t('settings.journal_export.credit') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">
          <tr v-for="(entry, index) in entries" :key="index">
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
              {{ entry.date }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
              {{ entry.reference }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-sm">
              <span class="font-mono text-gray-900">{{ entry.account_code }}</span>
              <span class="ml-2 text-gray-500">{{ entry.account_name }}</span>
            </td>
            <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-500">
              {{ entry.description }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900">
              {{ entry.debit > 0 ? formatMoney(entry.debit) : '' }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-900">
              {{ entry.credit > 0 ? formatMoney(entry.credit) : '' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="hasSearched && entries.length === 0"
      class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center"
    >
      <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
      <h3 class="mt-2 text-sm font-medium text-gray-900">
        {{ $t('settings.journal_export.no_entries') }}
      </h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ $t('settings.journal_export.no_entries_description') }}
      </p>
    </div>
  </BaseSettingCard>
</template>

<script setup>
import { useAccountStore } from '@/scripts/admin/stores/account'
import { useGlobalStore } from '@/scripts/admin/stores/global'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const accountStore = useAccountStore()
const globalStore = useGlobalStore()

const fromDate = ref(null)
const toDate = ref(null)
const selectedFormat = ref('csv')
const isLoading = ref(false)
const isExporting = ref(false)
const hasSearched = ref(false)
const entries = ref([])
const summary = ref(null)

const exportFormats = [
  { value: 'csv', label: 'Generic CSV' },
  { value: 'pantheon', label: 'Pantheon' },
  { value: 'zonel', label: 'Zonel' },
]

const hasEntries = computed(() => entries.value.length > 0)

function formatMoney(amount) {
  const currency = globalStore.currentCompany?.currency?.symbol || 'MKD'
  return `${amount.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`
}

async function loadPreview() {
  if (!fromDate.value || !toDate.value) {
    return
  }

  isLoading.value = true
  hasSearched.value = true

  try {
    const response = await accountStore.getJournalPreview({
      from: fromDate.value,
      to: toDate.value,
    })

    entries.value = response.entries || []
    summary.value = response.summary || null
  } catch (error) {
    console.error('Failed to load journal preview:', error)
    entries.value = []
    summary.value = null
  } finally {
    isLoading.value = false
  }
}

async function downloadExport() {
  if (!fromDate.value || !toDate.value) {
    return
  }

  isExporting.value = true

  try {
    await accountStore.exportJournals({
      from: fromDate.value,
      to: toDate.value,
      format: selectedFormat.value,
    })
  } catch (error) {
    console.error('Failed to export journals:', error)
  } finally {
    isExporting.value = false
  }
}
</script>
// CLAUDE-CHECKPOINT
