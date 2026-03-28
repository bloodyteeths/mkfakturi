<template>
  <div class="grid gap-8 md:grid-cols-12 pt-10">
    <!-- Left sidebar: filters -->
    <div class="col-span-8 md:col-span-4">
      <BaseInputGroup
        :label="$t('reports.trade_documents.document_type')"
        class="col-span-12 md:col-span-8"
      >
        <BaseMultiselect
          v-model="selectedDocType"
          :options="docTypes"
          value-prop="key"
          track-by="key"
          label="label"
          object
          @update:modelValue="onDocTypeChange"
        />
      </BaseInputGroup>

      <BaseInputGroup
        :label="$t('reports.trade_documents.date_range')"
        class="col-span-12 md:col-span-8 mt-4"
      >
        <BaseMultiselect
          v-model="selectedRange"
          :options="dateRanges"
          value-prop="key"
          track-by="key"
          label="label"
          object
          @update:modelValue="onChangeDateRange"
        />
      </BaseInputGroup>

      <div class="flex flex-col my-6 lg:space-x-3 lg:flex-row">
        <BaseInputGroup :label="$t('reports.trade_documents.from_date')">
          <BaseDatePicker v-model="formData.from_date" />
        </BaseInputGroup>

        <div
          class="hidden w-5 h-0 mx-4 border border-gray-400 border-solid xl:block"
          style="margin-top: 2.5rem"
        />

        <BaseInputGroup :label="$t('reports.trade_documents.to_date')">
          <BaseDatePicker v-model="formData.to_date" />
        </BaseInputGroup>
      </div>

      <BaseButton
        variant="primary-outline"
        class="content-center hidden mt-0 w-md md:flex md:mt-4"
        type="submit"
        @click.prevent="loadDocuments"
      >
        {{ $t('reports.update_report') }}
      </BaseButton>
    </div>

    <!-- Right side: document list -->
    <div class="col-span-8">
      <!-- Loading state -->
      <div v-if="isLoading" class="flex items-center justify-center py-16">
        <BaseIcon name="ArrowPathIcon" class="h-6 w-6 animate-spin text-gray-400" />
        <span class="ml-2 text-gray-500">{{ $t('reports.trade_documents.loading') }}</span>
      </div>

      <!-- Empty state -->
      <div
        v-else-if="!documents.length"
        class="flex flex-col items-center justify-center py-16 text-gray-400"
      >
        <BaseIcon name="DocumentTextIcon" class="h-12 w-12 mb-3" />
        <p>{{ $t('reports.trade_documents.no_documents') }}</p>
      </div>

      <!-- Document list -->
      <div v-else>
        <div class="mb-4 flex items-center justify-between">
          <p class="text-sm text-gray-500">
            {{ $t('reports.trade_documents.showing_count', { count: documents.length }) }}
          </p>
        </div>

        <table class="w-full text-left text-sm">
          <thead class="border-b border-gray-200 text-xs font-medium uppercase text-gray-500">
            <tr>
              <th class="py-3 px-4">{{ $t('reports.trade_documents.col_date') }}</th>
              <th class="py-3 px-4">{{ $t('reports.trade_documents.col_type') }}</th>
              <th class="py-3 px-4">{{ $t('reports.trade_documents.col_number') }}</th>
              <th class="py-3 px-4">{{ $t('reports.trade_documents.col_party') }}</th>
              <th class="py-3 px-4 text-right">{{ $t('reports.trade_documents.col_amount') }}</th>
              <th class="py-3 px-4 text-right">{{ $t('reports.trade_documents.col_actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(doc, idx) in documents"
              :key="idx"
              class="border-b border-gray-100 hover:bg-gray-50"
            >
              <td class="py-3 px-4 text-gray-600">{{ doc.date }}</td>
              <td class="py-3 px-4">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                  :class="typeClasses[doc.doc_type] || 'bg-gray-100 text-gray-700'"
                >
                  {{ doc.type_label }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium text-gray-800">{{ doc.doc_number }}</td>
              <td class="py-3 px-4 text-gray-600">{{ doc.party || '—' }}</td>
              <td class="py-3 px-4 text-right font-mono text-gray-700">
                {{ doc.amount_formatted || '—' }}
              </td>
              <td class="py-3 px-4 text-right">
                <BaseButton
                  v-if="doc.export_url"
                  variant="primary-outline"
                  size="sm"
                  @click="downloadPdf(doc)"
                >
                  <template #left="slotProps">
                    <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
                  </template>
                  PDF
                </BaseButton>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import moment from 'moment'
import axios from 'axios'
import { useCompanyStore } from '@/scripts/admin/stores/company'
import { useI18n } from 'vue-i18n'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const { t } = useI18n()
const companyStore = useCompanyStore()
const globalStore = useGlobalStore()

globalStore.downloadReport = downloadTradeBook

const isLoading = ref(false)
const documents = ref([])

const docTypes = reactive([
  { key: 'all', label: t('reports.trade_documents.type_all') },
  { key: 'trade_book', label: t('reports.trade_documents.type_et') },
  { key: 'kap', label: t('reports.trade_documents.type_kap') },
  { key: 'plt', label: t('reports.trade_documents.type_plt') },
  { key: 'nivelacija', label: t('reports.trade_documents.type_nivelacija') },
  { key: 'prenosnica', label: t('reports.trade_documents.type_prenosnica') },
])

const selectedDocType = ref(docTypes[0])

const dateRanges = reactive([
  { label: t('dateRange.this_month'), key: 'This Month' },
  { label: t('dateRange.this_quarter'), key: 'This Quarter' },
  { label: t('dateRange.this_year'), key: 'This Year' },
  { label: t('dateRange.previous_month'), key: 'Previous Month' },
  { label: t('dateRange.previous_quarter'), key: 'Previous Quarter' },
  { label: t('dateRange.previous_year'), key: 'Previous Year' },
  { label: t('dateRange.custom'), key: 'Custom' },
])

const selectedRange = ref(dateRanges[0])

const formData = reactive({
  from_date: moment().startOf('month').format('YYYY-MM-DD'),
  to_date: moment().endOf('month').format('YYYY-MM-DD'),
})

const typeClasses = {
  trade_book: 'bg-indigo-100 text-indigo-700',
  kap: 'bg-emerald-100 text-emerald-700',
  plt: 'bg-blue-100 text-blue-700',
  nivelacija: 'bg-amber-100 text-amber-700',
  prenosnica: 'bg-purple-100 text-purple-700',
  invoice: 'bg-green-100 text-green-700',
  bill: 'bg-orange-100 text-orange-700',
  credit_note: 'bg-red-100 text-red-700',
  expense: 'bg-gray-100 text-gray-700',
}

const companyId = computed(() => companyStore.selectedCompany?.id)

onMounted(() => {
  loadDocuments()
})

function getThisDate(type, time) {
  return moment()[type](time).format('YYYY-MM-DD')
}

function getPreDate(type, time) {
  return moment().subtract(1, time)[type](time).format('YYYY-MM-DD')
}

function onChangeDateRange() {
  const key = selectedRange.value.key
  switch (key) {
    case 'This Month':
      formData.from_date = getThisDate('startOf', 'month')
      formData.to_date = getThisDate('endOf', 'month')
      break
    case 'This Quarter':
      formData.from_date = getThisDate('startOf', 'quarter')
      formData.to_date = getThisDate('endOf', 'quarter')
      break
    case 'This Year':
      formData.from_date = getThisDate('startOf', 'year')
      formData.to_date = getThisDate('endOf', 'year')
      break
    case 'Previous Month':
      formData.from_date = getPreDate('startOf', 'month')
      formData.to_date = getPreDate('endOf', 'month')
      break
    case 'Previous Quarter':
      formData.from_date = getPreDate('startOf', 'quarter')
      formData.to_date = getPreDate('endOf', 'quarter')
      break
    case 'Previous Year':
      formData.from_date = getPreDate('startOf', 'year')
      formData.to_date = getPreDate('endOf', 'year')
      break
    default:
      break
  }
}

function onDocTypeChange() {
  loadDocuments()
}

async function loadDocuments() {
  if (!companyId.value) return

  isLoading.value = true
  try {
    const params = {
      from_date: moment(formData.from_date).format('YYYY-MM-DD'),
      to_date: moment(formData.to_date).format('YYYY-MM-DD'),
      type: selectedDocType.value.key,
    }

    const { data } = await axios.get(
      '/api/v1/trade-documents',
      { params }
    )

    documents.value = data.data || []
  } catch (err) {
    console.error('Failed to load trade documents:', err)
    documents.value = []
  } finally {
    isLoading.value = false
  }
}

function downloadPdf(doc) {
  if (doc.export_url) {
    window.open(doc.export_url, '_blank')
  }
}

function downloadTradeBook() {
  if (!companyId.value) return

  const fromDate = moment(formData.from_date).format('YYYY-MM-DD')
  const toDate = moment(formData.to_date).format('YYYY-MM-DD')

  window.open(
    `/api/v1/trade-documents/trade-book/export?from_date=${fromDate}&to_date=${toDate}`,
    '_blank'
  )
}
</script>

// CLAUDE-CHECKPOINT
