<template>
  <BasePage>
    <BasePageHeader :title="$t('partner.accounting.trade_book', 'Трговска книга')">
      <template #actions>
        <div v-if="entries.length > 0" class="flex space-x-2">
          <BaseButton variant="primary-outline" @click="exportCsv">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="ArrowDownTrayIcon" />
            </template>
            CSV
          </BaseButton>
          <BaseButton variant="primary" :loading="isExportingPdf" @click="previewPdf">
            <template #left="slotProps">
              <BaseIcon :class="slotProps.class" name="EyeIcon" />
            </template>
            PDF
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="name"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12">
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">{{ $t('partner.accounting.select_company_to_view') }}</p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Filters -->
      <div class="p-6 bg-white rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <BaseInputGroup :label="$t('general.from_date')" required>
            <BaseDatePicker v-model="filters.start_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <BaseInputGroup :label="$t('general.to_date')" required>
            <BaseDatePicker v-model="filters.end_date" :calendar-button="true" calendar-button-icon="CalendarDaysIcon" />
          </BaseInputGroup>
          <div class="flex flex-col items-end">
            <BaseButton variant="primary" class="w-full" :loading="isLoading" :disabled="!canLoad" @click="loadTradeBook">
              <template #left="slotProps">
                <BaseIcon :class="slotProps.class" name="MagnifyingGlassIcon" />
              </template>
              {{ $t('general.load') }}
            </BaseButton>
            <p v-if="dateError" class="mt-1 text-xs text-red-500">{{ dateError }}</p>
          </div>
        </div>
      </div>

      <!-- Summary Cards -->
      <div v-if="hasSearched && !isLoading && entries.length > 0" class="mb-6 grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.nabavna_vrednost', 'Набавна вредност') }}</p>
          <p class="text-lg font-bold text-red-700">{{ formatMoney(summary.total_nabavna) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.prodazhna_vrednost', 'Продажна вредност') }}</p>
          <p class="text-lg font-bold text-green-700">{{ formatMoney(summary.total_prodazhna) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.dneven_promet', 'Вкупен промет') }}</p>
          <p class="text-lg font-bold text-blue-700">{{ formatMoney(summary.total_promet) }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-gray-400">
          <p class="text-xs text-gray-500 uppercase">{{ $t('partner.accounting.total_entries', 'Вкупно записи') }}</p>
          <p class="text-lg font-bold text-gray-700">{{ summary.count }}</p>
        </div>
      </div>

      <!-- Table (Образец ЕТ format) -->
      <div v-if="entries.length > 0" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
          <div>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('partner.accounting.obrazec_et', 'Образец "ЕТ" — Евиденција во трговијата') }}</h3>
            <p class="text-sm text-gray-500">{{ filters.start_date }} &mdash; {{ filters.end_date }}</p>
          </div>
          <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700">
            Сл. весник 51/04
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-800 text-white">
              <tr>
                <th class="px-3 py-3 text-center text-xs font-medium uppercase" style="width: 5%">
                  Ред. бр.
                  <span class="block text-[10px] font-normal text-gray-400">1</span>
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_knizhenje', 'Датум на книжење') }}
                  <span class="block text-[10px] font-normal text-gray-400">2</span>
                </th>
                <th class="px-3 py-3 text-left text-xs font-medium uppercase" style="width: 25%">
                  {{ $t('partner.accounting.knigovodstven_dokument', 'Книговодствен документ (назив и број)') }}
                  <span class="block text-[10px] font-normal text-gray-400">3</span>
                </th>
                <th class="px-3 py-3 text-center text-xs font-medium uppercase" style="width: 10%">
                  {{ $t('partner.accounting.datum_na_dokument', 'Датум на документот') }}
                  <span class="block text-[10px] font-normal text-gray-400">4</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase" style="width: 15%">
                  {{ $t('partner.accounting.nabavna_vrednost', 'Набавна вредност') }}
                  <span class="block text-[10px] font-normal text-gray-400">5</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase" style="width: 15%">
                  {{ $t('partner.accounting.prodazhna_vrednost', 'Продажна вредност') }}
                  <span class="block text-[10px] font-normal text-gray-400">6</span>
                </th>
                <th class="px-3 py-3 text-right text-xs font-medium uppercase" style="width: 15%">
                  {{ $t('partner.accounting.dneven_promet', 'Дневен промет') }}
                  <span class="block text-[10px] font-normal text-gray-400">7</span>
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr
                v-for="entry in entries"
                :key="entry.seq"
                class="hover:bg-gray-50"
                :class="{
                  'bg-red-50': entry.doc_type === 'credit_note',
                  'bg-blue-50/30': entry.doc_type === 'bill',
                  'bg-amber-50/30': entry.doc_type === 'expense',
                }"
              >
                <td class="px-3 py-2 text-sm text-gray-500 text-center">{{ entry.seq }}</td>
                <td class="px-3 py-2 text-sm text-gray-900 whitespace-nowrap">{{ entry.date }}</td>
                <td class="px-3 py-2 text-sm">
                  <span class="font-medium" :class="docTypeColor(entry.doc_type)">{{ entry.doc_name }}</span>
                  <span class="text-gray-600 ml-1">{{ entry.doc_number }}</span>
                  <span v-if="entry.doc_type === 'credit_note'" class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100 text-red-700">КН</span>
                  <span v-if="entry.party" class="block text-xs text-gray-400 truncate" :title="entry.party">{{ entry.party }}</span>
                </td>
                <td class="px-3 py-2 text-sm text-gray-600 text-center whitespace-nowrap">{{ entry.doc_date }}</td>
                <td class="px-3 py-2 text-sm text-right font-mono" :class="entry.nabavna < 0 ? 'text-red-600' : 'text-gray-900'">
                  <template v-if="entry.nabavna !== 0">{{ formatMoney(entry.nabavna) }}</template>
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono" :class="entry.prodazhna < 0 ? 'text-red-600' : 'text-gray-900'">
                  <template v-if="entry.prodazhna !== 0">{{ formatMoney(entry.prodazhna) }}</template>
                </td>
                <td class="px-3 py-2 text-sm text-right font-mono font-semibold" :class="entry.promet && entry.promet < 0 ? 'text-red-600' : 'text-blue-700'">
                  <template v-if="entry.promet !== null && entry.promet !== 0">{{ formatMoney(entry.promet) }}</template>
                </td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-800 text-white font-semibold">
              <tr>
                <td colspan="4" class="px-3 py-3 text-sm">ВКУПНО ({{ summary.count }} {{ $t('partner.accounting.entries', 'записи') }})</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_nabavna) }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_prodazhna) }}</td>
                <td class="px-3 py-3 text-sm text-right font-mono">{{ formatMoney(summary.total_promet) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="DocumentTextIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.no_trade_entries', 'Нема записи во трговската книга') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.no_trade_entries_desc', 'Нема пронајдено набавки или продажби за избраниот период.') }}</p>
      </div>

      <!-- Loading -->
      <div v-if="isLoading" class="bg-white rounded-lg shadow overflow-hidden p-6">
        <div v-for="i in 5" :key="i" class="flex space-x-4 animate-pulse mb-4">
          <div class="h-4 bg-gray-200 rounded w-8"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-40"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-24"></div>
          <div class="h-4 bg-gray-200 rounded w-20"></div>
        </div>
      </div>

      <!-- Initial State -->
      <div v-if="!hasSearched && !isLoading" class="bg-white rounded-lg shadow p-12 text-center">
        <BaseIcon name="BookOpenIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('partner.accounting.trade_book_prompt', 'Изберете период за трговската книга') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ $t('partner.accounting.trade_book_hint', 'Хронолошки регистар на набавка и продажба — Образец ЕТ (Сл. весник 51/04)') }}</p>
      </div>
    </template>

    <PdfPreviewModal
      :show="showPdfPreview"
      :pdf-url="previewPdfUrl"
      :title="$t('partner.accounting.trade_book', 'Трговска книга')"
      @close="closePdfPreview"
      @download="downloadPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import PdfPreviewModal from './components/PdfPreviewModal.vue'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const isLoading = ref(false)
const isExportingPdf = ref(false)
const showPdfPreview = ref(false)
const previewPdfUrl = ref(null)
const pdfBlob = ref(null)
const hasSearched = ref(false)
const entries = ref([])
const summary = ref({ total_nabavna: 0, total_prodazhna: 0, total_promet: 0, count: 0 })

const filters = ref({
  start_date: `${new Date().getFullYear()}-01-01`,
  end_date: new Date().toISOString().slice(0, 10),
})

const companies = computed(() => consoleStore.managedCompanies || [])

const dateError = computed(() => {
  if (!filters.value.start_date || !filters.value.end_date) {
    return t('partner.accounting.dates_required', 'Изберете почетен и краен датум')
  }
  if (filters.value.start_date > filters.value.end_date) {
    return t('partner.accounting.date_order_error', 'Почетниот датум мора да биде пред крајниот')
  }
  return null
})

const canLoad = computed(() => !dateError.value && selectedCompanyId.value)

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
  }
})

function onCompanyChange() {
  entries.value = []
  summary.value = { total_nabavna: 0, total_prodazhna: 0, total_promet: 0, count: 0 }
  hasSearched.value = false
}

async function loadTradeBook() {
  if (!canLoad.value) return
  isLoading.value = true
  hasSearched.value = true
  entries.value = []

  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/trade-book`, {
      params: {
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
    })
    const data = response.data?.data || response.data
    entries.value = data.entries || []
    summary.value = data.summary || { total_nabavna: 0, total_prodazhna: 0, total_promet: 0, count: 0 }
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('partner.accounting.trade_book_load_error', 'Грешка при вчитување на трговската книга'),
    })
  } finally {
    isLoading.value = false
  }
}

function formatMoney(amount) {
  if (amount === null || amount === undefined) return '-'
  const value = Math.abs(amount) / 100
  const sign = amount < 0 ? '-' : ''
  return sign + new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value) + ' ден.'
}

function docTypeColor(type) {
  switch (type) {
    case 'invoice': return 'text-green-700'
    case 'credit_note': return 'text-red-600'
    case 'bill': return 'text-blue-600'
    case 'expense': return 'text-amber-600'
    default: return 'text-gray-700'
  }
}

function exportCsv() {
  if (!entries.value.length) return
  const headers = ['Ред.бр.', 'Датум на книжење', 'Книговодствен документ', 'Број', 'Датум на документот', 'Контрагент', 'Набавна вредност', 'Продажна вредност', 'Дневен промет']
  const rows = entries.value.map(e => [
    e.seq, e.date, e.doc_name, e.doc_number, e.doc_date, e.party || '',
    (e.nabavna / 100).toFixed(2),
    (e.prodazhna / 100).toFixed(2),
    e.promet !== null ? (e.promet / 100).toFixed(2) : '',
  ])
  const csvContent = [headers.join(','), ...rows.map(r => r.map(c => `"${String(c).replace(/"/g, '""')}"`).join(','))].join('\n')
  const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = `trgovska_kniga_${filters.value.start_date}_${filters.value.end_date}.csv`
  link.click()
  URL.revokeObjectURL(link.href)
}

async function previewPdf() {
  if (!selectedCompanyId.value) return
  isExportingPdf.value = true
  try {
    const response = await window.axios.get(`/partner/companies/${selectedCompanyId.value}/accounting/trade-book/export`, {
      params: {
        from_date: filters.value.start_date,
        to_date: filters.value.end_date,
      },
      responseType: 'blob',
    })
    if (response.data.type && !response.data.type.includes('pdf')) {
      const text = await response.data.text()
      try {
        const err = JSON.parse(text)
        throw new Error(err.message || 'Server returned non-PDF response')
      } catch (parseErr) {
        if (parseErr.message !== 'Server returned non-PDF response') {
          throw new Error('Unexpected server response')
        }
        throw parseErr
      }
    }
    pdfBlob.value = new Blob([response.data], { type: 'application/pdf' })
    previewPdfUrl.value = window.URL.createObjectURL(pdfBlob.value)
    showPdfPreview.value = true
  } catch (error) {
    let message = t('partner.accounting.pdf_export_error', 'Грешка при генерирање на PDF')
    if (error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text()
        const parsed = JSON.parse(text)
        message = parsed.message || message
      } catch (_) { /* ignore */ }
    } else if (error.message) {
      message = error.message
    }
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    isExportingPdf.value = false
  }
}

function downloadPdf() {
  if (!pdfBlob.value) return
  const url = window.URL.createObjectURL(pdfBlob.value)
  const link = document.createElement('a')
  link.href = url
  link.setAttribute('download', `trgovska_kniga_${filters.value.start_date}_${filters.value.end_date}.pdf`)
  document.body.appendChild(link)
  link.click()
  link.remove()
  window.URL.revokeObjectURL(url)
}

function closePdfPreview() {
  showPdfPreview.value = false
  if (previewPdfUrl.value) {
    window.URL.revokeObjectURL(previewPdfUrl.value)
    previewPdfUrl.value = null
  }
  pdfBlob.value = null
}
</script>

<!-- CLAUDE-CHECKPOINT -->
