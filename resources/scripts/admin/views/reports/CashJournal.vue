<template>
  <BasePage>
    <BasePageHeader :title="$t('cash_journal')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('cash_journal')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
      <BaseInputGroup :label="$t('for_period') + ' - ' + $t('general.from')">
        <BaseInput v-model="fromDate" type="date" />
      </BaseInputGroup>
      <BaseInputGroup :label="$t('for_period') + ' - ' + $t('general.to')">
        <BaseInput v-model="toDate" type="date" />
      </BaseInputGroup>
      <div class="flex items-end gap-2">
        <BaseButton variant="primary" @click="generate">
          {{ $t('generate_report') }}
        </BaseButton>
        <BaseButton v-if="entries.length" variant="primary-outline" @click="downloadPdf">
          {{ $t('download_pdf') }}
        </BaseButton>
      </div>
    </div>

    <div v-if="loading" class="mt-8 text-center text-gray-500">
      <BaseIcon name="ArrowPathIcon" class="animate-spin h-6 w-6 mx-auto" />
    </div>

    <div v-if="generated && !loading" class="mt-6">
      <div class="flex justify-between items-center mb-4 p-4 bg-gray-50 rounded-lg">
        <div>
          <span class="text-sm text-gray-500">{{ $t('opening_balance') }}:</span>
          <span class="ml-2 font-bold">{{ formatAmount(openingBalance) }} МКД</span>
        </div>
        <div>
          <span class="text-sm text-gray-500">{{ $t('closing_balance') }}:</span>
          <span class="ml-2 font-bold text-lg">{{ formatAmount(closingBalance) }} МКД</span>
        </div>
      </div>

      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('payments.date') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.description') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.document') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('income_label') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('expense_label') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(entry, idx) in entries" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-2 text-sm text-gray-500">{{ idx + 1 }}</td>
            <td class="px-4 py-2 text-sm">{{ entry.date }}</td>
            <td class="px-4 py-2 text-sm">{{ entry.description }}</td>
            <td class="px-4 py-2 text-sm text-gray-500">{{ entry.document_ref }}</td>
            <td class="px-4 py-2 text-sm text-right text-green-600 font-medium">
              {{ entry.income ? formatAmount(entry.income) : '' }}
            </td>
            <td class="px-4 py-2 text-sm text-right text-red-600 font-medium">
              {{ entry.expense ? formatAmount(entry.expense) : '' }}
            </td>
          </tr>
        </tbody>
        <tfoot class="bg-gray-100 font-bold">
          <tr>
            <td colspan="4" class="px-4 py-3 text-right text-sm">{{ $t('general.total') }}:</td>
            <td class="px-4 py-3 text-right text-sm text-green-700">{{ formatAmount(totalIncome) }}</td>
            <td class="px-4 py-3 text-right text-sm text-red-700">{{ formatAmount(totalExpense) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </BasePage>
</template>

<script setup>
import { ref } from 'vue'

const fromDate = ref('')
const toDate = ref('')
const loading = ref(false)
const generated = ref(false)
const entries = ref([])
const openingBalance = ref(0)
const closingBalance = ref(0)
const totalIncome = ref(0)
const totalExpense = ref(0)

function formatAmount(cents) {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(cents) / 100)
}

async function generate() {
  if (!fromDate.value || !toDate.value) return
  loading.value = true
  try {
    const res = await window.axios.get('/reports/cash-journal', {
      params: { from_date: fromDate.value, to_date: toDate.value }
    })
    const data = res.data
    entries.value = data.entries || []
    openingBalance.value = data.opening_balance || 0
    closingBalance.value = data.closing_balance || 0
    totalIncome.value = data.total_income || 0
    totalExpense.value = data.total_expense || 0
    generated.value = true
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

function downloadPdf() {
  const url = `/api/v1/reports/cash-journal/pdf?from_date=${fromDate.value}&to_date=${toDate.value}`
  window.open(url, '_blank')
}
</script>
// CLAUDE-CHECKPOINT
