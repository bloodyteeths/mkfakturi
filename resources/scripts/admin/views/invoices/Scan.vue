<template>
  <BasePage>
    <BasePageHeader :title="$t('invoices.scan_invoice')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('invoices.invoice', 2)" to="/admin/invoices" />
        <BaseBreadcrumbItem :title="$t('invoices.scan_invoice')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <div class="space-y-4">
        <!-- File Upload -->
        <BaseInputGroup :label="$t('invoices.scan_upload_invoice')">
          <div
            v-if="!selectedFile"
            class="mt-1 cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors"
            :class="[
              isDragOver
                ? 'border-primary-300 bg-primary-50'
                : 'border-gray-300 hover:border-gray-400',
            ]"
            @dragover.prevent="isDragOver = true"
            @dragleave.prevent="isDragOver = false"
            @drop.prevent="handleDrop"
            @click="$refs.fileInput.click()"
          >
            <BaseIcon name="CameraIcon" class="mx-auto h-12 w-12 text-gray-400" />
            <p class="mt-2 text-sm font-medium text-gray-700">
              {{ $t('invoices.scan_upload_invoice') }}
            </p>
            <p class="mt-1 text-xs text-gray-500">
              JPG, PNG, PDF — max 20MB
            </p>
            <input
              ref="fileInput"
              type="file"
              accept="image/*,application/pdf"
              class="hidden"
              @change="onFileSelected"
            />
          </div>

          <!-- Selected File Display -->
          <div
            v-else
            class="mt-1 flex items-center justify-between rounded-lg border border-green-200 bg-green-50 px-4 py-3"
          >
            <div class="flex items-center gap-3">
              <BaseIcon name="DocumentTextIcon" class="h-5 w-5 text-green-600" />
              <div>
                <p class="text-sm font-medium text-green-900">{{ selectedFile.name }}</p>
                <p class="text-xs text-green-700">{{ formatFileSize(selectedFile.size) }}</p>
              </div>
            </div>
            <button
              class="text-sm text-green-700 hover:text-green-900"
              @click="removeFile"
            >
              {{ $t('general.remove') }}
            </button>
          </div>
        </BaseInputGroup>

        <BaseButton
          variant="primary"
          :disabled="!selectedFile || scannerStore.isScanning"
          @click="scan"
        >
          <template #left="slotProps">
            <BaseIcon name="CameraIcon" :class="slotProps.class" />
          </template>
          {{ $t('invoices.scan_invoice') }}
        </BaseButton>

        <AiProcessingOverlay
          :visible="scannerStore.isScanning"
          :current-step="scannerStore.processingStep"
          :steps="scanSteps"
        />

        <!-- Results: Extracted Data -->
        <div v-if="scanResult" class="mt-6">
          <div>
            <div class="flex items-center justify-between mb-4">
              <BaseHeading tag="h3" size="sm">
                {{ $t('invoices.create_invoice_from_scan') }}
              </BaseHeading>
              <span v-if="scanResult.extraction_method" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="scanResult.extraction_method === 'gemini' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'"
              >
                {{ scanResult.extraction_method === 'gemini' ? 'Gemini AI' : 'Tesseract OCR' }}
              </span>
            </div>
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2">
              <span class="text-amber-500 mt-0.5">&#9888;</span>
              <p class="text-sm text-amber-700">
                {{ $t('receipts.ai_warning') }}
              </p>
            </div>
            <div class="bg-white border rounded-lg p-6 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <BaseInputGroup :label="$t('invoices.scan_customer_name')">
                  <BaseInput
                    v-model="invoiceForm.customer_name"
                    type="text"
                    :placeholder="$t('customers.customer', 1)"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('receipts.tax_id') || 'Tax ID'">
                  <BaseInput
                    v-model="invoiceForm.tax_id"
                    type="text"
                    placeholder="e.g. MK4030009544251"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('invoices.invoice_number')">
                  <BaseInput
                    v-model="invoiceForm.invoice_number"
                    type="text"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('invoices.invoice_date')">
                  <BaseInput
                    v-model="invoiceForm.invoice_date"
                    type="date"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('invoices.due_date')">
                  <BaseInput
                    v-model="invoiceForm.due_date"
                    type="date"
                  />
                </BaseInputGroup>
              </div>

              <!-- Line Items Table -->
              <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                  <h4 class="text-sm font-medium text-gray-700">{{ $t('invoices.items') }}</h4>
                  <button
                    type="button"
                    class="text-sm text-primary-500 hover:text-primary-700"
                    @click="addLineItem"
                  >
                    + {{ $t('invoices.add_item') }}
                  </button>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-2/5">
                          {{ $t('items.name') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-16">
                          {{ $t('items.quantity') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">
                          {{ $t('items.price') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">
                          {{ $t('invoices.tax') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">
                          {{ $t('invoices.total') }}
                        </th>
                        <th class="px-3 py-2 w-10"></th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                      <tr v-for="(item, idx) in lineItems" :key="idx" class="hover:bg-gray-50">
                        <td class="px-3 py-2">
                          <input
                            v-model="item.name"
                            type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500"
                          />
                        </td>
                        <td class="px-3 py-2">
                          <input
                            v-model.number="item.quantity"
                            type="number"
                            min="0"
                            step="1"
                            class="w-full text-right border-gray-300 rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500"
                          />
                        </td>
                        <td class="px-3 py-2">
                          <input
                            v-model.number="item.unit_price"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full text-right border-gray-300 rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500"
                          />
                        </td>
                        <td class="px-3 py-2">
                          <input
                            v-model.number="item.tax"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full text-right border-gray-300 rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500"
                          />
                        </td>
                        <td class="px-3 py-2">
                          <input
                            v-model.number="item.total"
                            type="number"
                            min="0"
                            step="0.01"
                            class="w-full text-right border-gray-300 rounded-md shadow-sm text-sm focus:ring-primary-500 focus:border-primary-500"
                          />
                        </td>
                        <td class="px-3 py-2 text-center">
                          <button
                            v-if="lineItems.length > 1"
                            type="button"
                            class="text-red-400 hover:text-red-600"
                            @click="removeLineItem(idx)"
                          >
                            &times;
                          </button>
                        </td>
                      </tr>
                    </tbody>
                    <tfoot class="bg-gray-50 font-medium">
                      <tr>
                        <td class="px-3 py-2 text-right" colspan="2">{{ $t('invoices.sub_total') }}:</td>
                        <td class="px-3 py-2 text-right">{{ computedSubtotal.toFixed(2) }}</td>
                        <td class="px-3 py-2 text-right">{{ computedTax.toFixed(2) }}</td>
                        <td class="px-3 py-2 text-right">{{ computedTotal.toFixed(2) }}</td>
                        <td></td>
                      </tr>
                      <tr v-if="ocrTotal !== null" class="border-t-2 border-gray-300">
                        <td class="px-3 py-2 text-right font-bold" colspan="4">{{ $t('invoices.total') }}:</td>
                        <td class="px-3 py-2 text-right font-bold">{{ ocrTotal.toFixed(2) }}</td>
                        <td></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <BaseInputGroup :label="$t('invoices.notes')">
                <BaseTextarea
                  v-model="invoiceForm.notes"
                  :rows="3"
                />
              </BaseInputGroup>

              <div class="flex justify-end gap-3 pt-4">
                <BaseButton
                  variant="outline"
                  @click="resetForm"
                >
                  {{ $t('general.reset') }}
                </BaseButton>
                <BaseButton
                  variant="primary"
                  @click="createInvoice"
                >
                  {{ $t('invoices.create_invoice_from_scan') }}
                </BaseButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { computed, ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useReceiptScannerStore } from '@/scripts/admin/stores/receipt-scanner'
import AiProcessingOverlay from '@/scripts/admin/components/AiProcessingOverlay.vue'

const scannerStore = useReceiptScannerStore()
const router = useRouter()
const selectedFile = ref(null)
const isDragOver = ref(false)
const fileInput = ref(null)

const scanResult = ref(null)
const ocrTotal = ref(null)

const scanSteps = [
  'Uploading document...',
  'AI is reading your invoice...',
  'Extracting data...',
  'Almost done...',
]

const invoiceForm = ref({
  customer_name: '',
  tax_id: '',
  invoice_number: '',
  invoice_date: '',
  due_date: '',
  notes: '',
})

const lineItems = reactive([
  { name: '', quantity: 1, unit_price: 0, tax: 0, total: 0 },
])

const computedSubtotal = computed(() =>
  lineItems.reduce((sum, item) => sum + ((item.quantity || 0) * (item.unit_price || 0)), 0)
)
const computedTax = computed(() =>
  lineItems.reduce((sum, item) => sum + (Number(item.tax) || 0), 0)
)
const computedTotal = computed(() =>
  lineItems.reduce((sum, item) => sum + (Number(item.total) || 0), 0)
)

function onFileSelected(event) {
  const file = event.target.files[0]
  if (file) validateAndSetFile(file)
  event.target.value = ''
}

function handleDrop(event) {
  isDragOver.value = false
  const file = event.dataTransfer.files[0]
  if (file) validateAndSetFile(file)
}

function validateAndSetFile(file) {
  const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']
  if (!validTypes.includes(file.type)) {
    return
  }
  if (file.size > 20 * 1024 * 1024) {
    return
  }
  selectedFile.value = file
  scanResult.value = null
}

function removeFile() {
  selectedFile.value = null
  scanResult.value = null
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i]
}

function addLineItem() {
  lineItems.push({ name: '', quantity: 1, unit_price: 0, tax: 0, total: 0 })
}

function removeLineItem(idx) {
  lineItems.splice(idx, 1)
}

function scan() {
  if (!selectedFile.value) return

  scannerStore.scanReceipt(selectedFile.value).then((response) => {
    const data = response.data

    scanResult.value = {
      stored_path: data.stored_path,
      extraction_method: data.extraction_method || 'unknown',
    }

    if (data.data) {
      const d = data.data
      invoiceForm.value.customer_name = d.vendor_name || ''
      invoiceForm.value.tax_id = d.tax_id || ''
      invoiceForm.value.invoice_number = d.bill_number || ''
      invoiceForm.value.invoice_date = d.bill_date || ''
      invoiceForm.value.due_date = d.due_date || ''
      ocrTotal.value = d.total !== null && d.total !== undefined ? Number(d.total) : null
    }

    if (data.data?.line_items && data.data.line_items.length > 0) {
      lineItems.splice(0, lineItems.length)
      data.data.line_items.forEach((li) => {
        lineItems.push({
          name: li.name || li.description || '',
          description: li.description || '',
          quantity: Number(li.quantity) || 1,
          unit_price: Number(li.unit_price) || 0,
          tax: Number(li.tax) || 0,
          total: Number(li.total) || 0,
        })
      })
    } else {
      lineItems.splice(0, lineItems.length)
      lineItems.push({
        name: data.data?.vendor_name || '',
        description: '',
        quantity: 1,
        unit_price: Number(data.data?.subtotal) || Number(data.data?.total) || 0,
        tax: Number(data.data?.tax) || 0,
        total: Number(data.data?.total) || 0,
      })
    }
  }).catch((error) => {
    console.error('Scan failed:', error)
  })
}

function resetForm() {
  invoiceForm.value = {
    customer_name: '',
    tax_id: '',
    invoice_number: '',
    invoice_date: '',
    due_date: '',
    notes: '',
  }
  lineItems.splice(0, lineItems.length)
  lineItems.push({ name: '', quantity: 1, unit_price: 0, tax: 0, total: 0 })
}

function createInvoice() {
  scannerStore.setScannedInvoiceData({
    invoice: {
      invoice_number: invoiceForm.value.invoice_number,
      invoice_date: invoiceForm.value.invoice_date,
      due_date: invoiceForm.value.due_date,
      customer_name: invoiceForm.value.customer_name,
      tax_id: invoiceForm.value.tax_id,
      notes: invoiceForm.value.notes,
    },
    items: lineItems.map((item) => ({
      name: item.name,
      description: item.description || '',
      quantity: item.quantity,
      price: item.unit_price,
      tax: item.tax,
      total: item.total,
    })),
  })

  router.push({ name: 'invoices.create' })
}
</script>
