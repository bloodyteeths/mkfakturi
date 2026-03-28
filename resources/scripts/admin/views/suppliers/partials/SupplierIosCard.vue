<template>
  <div class="mt-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-800">
        {{ $t('suppliers.ios_title') }}
      </h3>
      <button
        v-if="items.length > 0"
        @click="downloadPdf"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        {{ $t('suppliers.download_ios_pdf') }}
      </button>
    </div>

    <div class="overflow-x-auto border border-gray-200 rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('bills.bill_number') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('bills.bill_date') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('bills.due_date') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('general.total') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('suppliers.paid') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('suppliers.outstanding') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('suppliers.days_overdue') }}</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-if="isLoading">
            <td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ $t('general.loading') }}...</td>
          </tr>
          <tr v-else-if="items.length === 0">
            <td colspan="8" class="px-4 py-8 text-center text-gray-400">{{ $t('suppliers.no_open_items') }}</td>
          </tr>
          <tr v-for="(item, index) in items" :key="index">
            <td class="px-4 py-2 text-sm text-gray-500">{{ index + 1 }}</td>
            <td class="px-4 py-2 text-sm text-gray-700 font-medium">{{ item.bill_number }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ item.bill_date }}</td>
            <td class="px-4 py-2 text-sm text-gray-700">{{ item.due_date }}</td>
            <td class="px-4 py-2 text-sm text-right text-gray-700">{{ formatAmount(item.total) }}</td>
            <td class="px-4 py-2 text-sm text-right text-green-600">{{ formatAmount(item.paid) }}</td>
            <td class="px-4 py-2 text-sm text-right text-red-600 font-medium">{{ formatAmount(item.outstanding) }}</td>
            <td class="px-4 py-2 text-sm text-right" :class="item.days_overdue > 0 ? 'text-red-600 font-bold' : 'text-gray-400'">
              {{ item.days_overdue > 0 ? item.days_overdue : '-' }}
            </td>
          </tr>
        </tbody>
        <tfoot v-if="items.length > 0" class="bg-gray-100 font-medium">
          <tr>
            <td colspan="6" class="px-4 py-3 text-sm text-gray-700 text-right">{{ $t('suppliers.total_open') }}</td>
            <td class="px-4 py-3 text-sm text-right text-red-600 font-bold">{{ formatAmount(meta.total_open) }}</td>
            <td></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()
const route = useRoute()

const items = ref([])
const meta = ref({})
const isLoading = ref(false)

function formatAmount(amount) {
  if (amount === null || amount === undefined) return '-'
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount / 100)
}

function downloadPdf() {
  window.open(`/api/v1/suppliers/${route.params.id}/ios/pdf?download=true`, '_blank')
}

async function fetchIos() {
  isLoading.value = true
  try {
    const response = await axios.get(`/suppliers/${route.params.id}/ios`)
    items.value = response.data.data
    meta.value = response.data.meta
  } catch (e) {
    items.value = []
    meta.value = {}
  } finally {
    isLoading.value = false
  }
}

onMounted(fetchIos)

watch(() => route.params.id, (newId, oldId) => {
  if (newId && newId !== oldId) {
    fetchIos()
  }
})
</script>
