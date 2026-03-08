<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <template #actions>
        <p class="text-sm text-gray-500">{{ t('subtitle') }}</p>
      </template>
    </BasePageHeader>

    <!-- Company & Period Selectors -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 mb-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Company Selector -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('select_company') }}</label>
          <BaseMultiselect
            v-model="selectedCompanyId"
            :options="companies"
            :searchable="true"
            track-by="name"
            label="name"
            value-prop="id"
            :placeholder="t('select_company_placeholder')"
            @update:model-value="onCompanyChange"
          />
        </div>

        <!-- Year Selector -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('select_year') }}</label>
          <BaseMultiselect
            v-model="selectedYear"
            :options="yearOptions"
            label="label"
            value-prop="value"
            :searchable="false"
          />
        </div>

        <!-- Month Selector (for DDV-04) -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ t('select_month') }}</label>
          <BaseMultiselect
            v-model="selectedMonth"
            :options="monthOptions"
            label="label"
            value-prop="value"
            :searchable="false"
          />
        </div>
      </div>
    </div>

    <!-- Empty State (no company selected) -->
    <div v-if="!selectedCompanyId" class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-16">
      <BaseIcon name="DocumentTextIcon" class="h-16 w-16 text-gray-300" />
      <h3 class="mt-4 text-sm font-medium text-gray-900">{{ t('select_company_prompt') }}</h3>
    </div>

    <!-- Form Cards Grid -->
    <template v-if="selectedCompanyId">
      <!-- Tax Returns Section -->
      <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
          <span class="w-1 h-4 bg-purple-600 rounded-full mr-2"></span>
          {{ t('categories.tax_returns') }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UjpFormCard
            v-for="code in taxFormCodes"
            :key="code"
            :form-code="code"
            :is-loading="loadingForm === code"
            :loading-text="loadingText"
            :validation="validationResults[code] || null"
            :is-expanded="expandedForm === code"
            @preview="handlePreview"
            @generate-pdf="handleGeneratePdf"
            @download-xml="handleDownloadXml"
          />
        </div>
      </div>

      <!-- Annual Accounts Section -->
      <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
          <span class="w-1 h-4 bg-indigo-600 rounded-full mr-2"></span>
          {{ t('categories.annual_accounts') }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <UjpFormCard
            v-for="code in annualFormCodes"
            :key="code"
            :form-code="code"
            :is-loading="loadingForm === code"
            :loading-text="loadingText"
            :validation="validationResults[code] || null"
            :is-expanded="expandedForm === code"
            @preview="handlePreview"
            @generate-pdf="handleGeneratePdf"
            @download-xml="handleDownloadXml"
          />
        </div>
      </div>

      <!-- Preview Data Panel -->
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-2"
      >
        <div v-if="previewData && expandedForm" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
          <div class="px-5 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-900">
              {{ t('preview') }}: {{ t(`forms.${expandedForm}.title`) }} — {{ t(`forms.${expandedForm}.name`) }}
            </h3>
            <button
              class="text-gray-400 hover:text-gray-600 focus:outline-none"
              @click="closePreview"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          <div class="p-5 overflow-x-auto">
            <!-- Preview table -->
            <PreviewTable :data="previewData" :form-code="expandedForm" />
          </div>
        </div>
      </Transition>
    </template>

    <!-- PDF Preview Modal -->
    <PdfPreviewModal
      :show="showPdfPreview"
      :pdf-url="pdfPreviewUrl"
      :title="pdfPreviewTitle"
      @close="closePdfPreview"
      @download="downloadCurrentPdf"
    />
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import UjpFormCard from './components/UjpFormCard.vue'
import PdfPreviewModal from './components/PdfPreviewModal.vue'
import PreviewTable from './components/UjpPreviewTable.vue'
import ujpMessages from '@/scripts/admin/i18n/ujp-forms.js'

const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const locale = document.documentElement.lang || 'mk'
function t(key) {
  const parts = key.split('.')
  let val = ujpMessages[locale]?.ujp_forms
  let fallback = ujpMessages['en']?.ujp_forms
  for (const part of parts) {
    val = val?.[part]
    fallback = fallback?.[part]
  }
  return val || fallback || key
}

// State
const selectedCompanyId = ref(null)
const selectedYear = ref(new Date().getFullYear())
const selectedMonth = ref(null)
const loadingForm = ref(null)
const loadingText = ref('')
const expandedForm = ref(null)
const previewData = ref(null)
const validationResults = ref({})

// PDF Preview
const showPdfPreview = ref(false)
const pdfPreviewUrl = ref(null)
const pdfPreviewTitle = ref('')
const pdfBlob = ref(null)

// Form code lists
const taxFormCodes = ['ddv-04', 'db']
const annualFormCodes = ['obrazec-36', 'obrazec-37']

// Computed
const companies = computed(() => consoleStore.managedCompanies || [])

const currentYear = new Date().getFullYear()
const yearOptions = computed(() => {
  const years = []
  for (let y = currentYear; y >= currentYear - 5; y--) {
    years.push({ value: y, label: String(y) })
  }
  return years
})

const monthOptions = computed(() => {
  const months = [
    { value: null, label: t('all_months') },
    { value: 1, label: 'Јануари / January' },
    { value: 2, label: 'Февруари / February' },
    { value: 3, label: 'Март / March' },
    { value: 4, label: 'Април / April' },
    { value: 5, label: 'Мај / May' },
    { value: 6, label: 'Јуни / June' },
    { value: 7, label: 'Јули / July' },
    { value: 8, label: 'Август / August' },
    { value: 9, label: 'Септември / September' },
    { value: 10, label: 'Октомври / October' },
    { value: 11, label: 'Ноември / November' },
    { value: 12, label: 'Декември / December' },
  ]
  return months
})

// Lifecycle
onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
  }
})

function onCompanyChange() {
  previewData.value = null
  expandedForm.value = null
  validationResults.value = {}
}

function buildParams(formCode) {
  const params = { year: selectedYear.value }
  if (formCode === 'ddv-04' && selectedMonth.value) {
    params.month = selectedMonth.value
  }
  return params
}

// Preview
async function handlePreview(formCode) {
  if (!selectedCompanyId.value) return

  loadingForm.value = formCode
  loadingText.value = t('preview_loading')

  try {
    const params = buildParams(formCode)
    const response = await window.axios.get(
      `/partner/companies/${selectedCompanyId.value}/ujp-forms/${formCode}/preview`,
      { params }
    )
    const preview = response.data?.data || response.data
    previewData.value = preview?.data || preview
    validationResults.value[formCode] = preview?.validation || null
    expandedForm.value = formCode
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || error.response?.data?.error || 'Error loading preview',
    })
  } finally {
    loadingForm.value = null
    loadingText.value = ''
  }
}

function closePreview() {
  previewData.value = null
  expandedForm.value = null
}

// PDF Generation — uses base64 JSON response to avoid binary blob issues
async function handleGeneratePdf(formCode) {
  if (!selectedCompanyId.value) return

  loadingForm.value = formCode
  loadingText.value = t('generating_pdf')

  try {
    const params = buildParams(formCode)
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/ujp-forms/${formCode}/pdf`,
      params
    )

    const { pdf, filename, size, debug } = response.data

    console.log('[UJP PDF]', formCode, 'response:', { size, filename, pdfLength: pdf?.length, debug })

    if (!pdf) {
      throw new Error('Server returned empty PDF (size=' + size + ', keys=' + Object.keys(response.data).join(',') + ')')
    }

    // Decode base64 to binary
    const binaryStr = atob(pdf)
    const bytes = new Uint8Array(binaryStr.length)
    for (let i = 0; i < binaryStr.length; i++) {
      bytes[i] = binaryStr.charCodeAt(i)
    }
    const blob = new Blob([bytes], { type: 'application/pdf' })

    // Verify PDF magic bytes
    const header = String.fromCharCode(...bytes.slice(0, 5))
    if (!header.startsWith('%PDF')) {
      console.error('[UJP PDF]', formCode, 'INVALID: content does not start with %PDF, got:', header)
      throw new Error('Server returned invalid PDF content')
    }

    pdfBlob.value = blob
    pdfPreviewUrl.value = URL.createObjectURL(blob)
    pdfPreviewTitle.value = `${t(`forms.${formCode}.title`)} — ${t(`forms.${formCode}.name`)}`
    showPdfPreview.value = true
  } catch (error) {
    const message = error.response?.data?.message || error.response?.data?.error || error.message || 'Error generating PDF'
    console.error('PDF generation error:', formCode, error)
    notificationStore.showNotification({ type: 'error', message })
  } finally {
    loadingForm.value = null
    loadingText.value = ''
  }
}

function closePdfPreview() {
  showPdfPreview.value = false
  if (pdfPreviewUrl.value) {
    URL.revokeObjectURL(pdfPreviewUrl.value)
    pdfPreviewUrl.value = null
  }
  pdfBlob.value = null
}

function downloadCurrentPdf() {
  if (!pdfBlob.value) return
  const url = URL.createObjectURL(pdfBlob.value)
  const a = document.createElement('a')
  a.href = url
  a.download = `${pdfPreviewTitle.value || 'form'}.pdf`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}

// XML Download
async function handleDownloadXml(formCode) {
  if (!selectedCompanyId.value) return

  loadingForm.value = formCode
  loadingText.value = t('generating_xml')

  try {
    const params = buildParams(formCode)
    const response = await window.axios.post(
      `/partner/companies/${selectedCompanyId.value}/ujp-forms/${formCode}/xml`,
      params,
      { responseType: 'blob' }
    )

    const url = URL.createObjectURL(response.data)
    const a = document.createElement('a')
    a.href = url
    a.download = `${formCode}_${selectedYear.value}.xml`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)

    notificationStore.showNotification({
      type: 'success',
      message: 'XML downloaded',
    })
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || 'Error generating XML',
    })
  } finally {
    loadingForm.value = null
    loadingText.value = ''
  }
}
</script>

<!-- CLAUDE-CHECKPOINT -->
