<template>
  <BasePage>
    <BasePageHeader :title="$t('receipts.scan_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('receipts.scan_title')" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <div class="space-y-4">
        <BaseInputGroup :label="$t('receipts.upload_receipt')">
          <BaseFileUploader
            v-model="files"
            accept="image/*,application/pdf"
            :multiple="false"
            @change="onFileChange"
          />
        </BaseInputGroup>

        <BaseButton
          variant="primary"
          :disabled="!selectedFile || scannerStore.isScanning"
          @click="scan"
        >
          {{ $t('receipts.scan_button') }}
        </BaseButton>

        <AiProcessingOverlay
          :visible="scannerStore.isScanning"
          :current-step="scannerStore.processingStep"
          :steps="receiptSteps"
        />

        <!-- Results Layout: Image + Extracted Data -->
        <div v-if="scanResult" class="mt-6">
          <div class="flex items-center justify-between mb-3">
            <BaseHeading tag="h3" size="sm">
              {{ $t('receipts.scanned_receipt') }}
            </BaseHeading>
            <span v-if="scanResult.extraction_method" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              :class="scanResult.extraction_method === 'gemini' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'"
            >
              {{ scanResult.extraction_method === 'gemini' ? 'Gemini AI' : 'Tesseract OCR' }}
            </span>
          </div>

          <!-- Full-width image preview -->
          <div class="border rounded-lg overflow-hidden bg-gray-50">
            <div class="overflow-auto" style="max-height: 50vh;">
              <img
                :src="scanResult.image_url"
                :alt="$t('receipts.receipt_image')"
                class="max-w-full h-auto mx-auto"
                style="max-width: 600px;"
                @load="onImageLoad"
                @error="onImageError"
              />
            </div>
          </div>
          <p v-if="imageLoadError" class="text-red-600 text-sm mt-2">
            Failed to load image: {{ imageLoadError }}
          </p>

          <!-- Bill Header Form -->
          <div class="mt-6">
            <BaseHeading tag="h3" size="sm" class="mb-4">
              {{ $t('receipts.create_bill_from_scan') }}
            </BaseHeading>
            <div class="bg-white border rounded-lg p-6 space-y-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <BaseInputGroup :label="$t('bills.vendor')">
                  <BaseInput
                    v-model="billForm.vendor"
                    type="text"
                    :placeholder="$t('bills.vendor_placeholder')"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('receipts.tax_id') || 'Tax ID'">
                  <BaseInput
                    v-model="billForm.tax_id"
                    type="text"
                    placeholder="e.g. MK4030009544251"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('bills.bill_number')">
                  <BaseInput
                    v-model="billForm.bill_number"
                    type="text"
                    :placeholder="$t('bills.bill_number_placeholder')"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('bills.bill_date')">
                  <BaseInput
                    v-model="billForm.bill_date"
                    type="date"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('bills.due_date')">
                  <BaseInput
                    v-model="billForm.due_date"
                    type="date"
                  />
                </BaseInputGroup>
              </div>

              <!-- Line Items Table -->
              <div class="mt-6">
                <div class="flex items-center justify-between mb-2">
                  <h4 class="text-sm font-medium text-gray-700">{{ $t('bills.items') }}</h4>
                  <button
                    type="button"
                    class="text-sm text-primary-500 hover:text-primary-700"
                    @click="addLineItem"
                  >
                    + {{ $t('bills.add_item') }}
                  </button>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                      <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-2/5">
                          {{ $t('bills.item_name') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-16">
                          {{ $t('bills.item_quantity') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">
                          {{ $t('bills.item_price') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-24">
                          {{ $t('bills.tax_amount') }}
                        </th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-28">
                          {{ $t('bills.total') }}
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
                        <td class="px-3 py-2 text-right" colspan="2">{{ $t('bills.sub_total') }}:</td>
                        <td class="px-3 py-2 text-right">{{ computedSubtotal.toFixed(2) }}</td>
                        <td class="px-3 py-2 text-right">{{ computedTax.toFixed(2) }}</td>
                        <td class="px-3 py-2 text-right">{{ computedTotal.toFixed(2) }}</td>
                        <td></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>

              <BaseInputGroup :label="$t('bills.notes')">
                <BaseTextarea
                  v-model="billForm.notes"
                  :rows="3"
                  :placeholder="$t('bills.notes_placeholder')"
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
                  @click="createBill"
                >
                  {{ $t('bills.create_bill') }}
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
const files = ref([])

// Scan result data
const scanResult = ref(null)

const receiptSteps = [
  'Uploading document...',
  'AI is reading your invoice...',
  'Extracting data...',
  'Almost done...',
]

// Image loading
const imageLoadError = ref(null)

// Bill header form
const billForm = ref({
  vendor: '',
  tax_id: '',
  bill_number: '',
  bill_date: '',
  due_date: '',
  notes: ''
})

// Line items (editable)
const lineItems = reactive([
  { name: '', quantity: 1, unit_price: 0, tax: 0, total: 0 },
])

// Computed totals
const computedSubtotal = computed(() =>
  lineItems.reduce((sum, item) => sum + ((item.quantity || 0) * (item.unit_price || 0)), 0)
)
const computedTax = computed(() =>
  lineItems.reduce((sum, item) => sum + (Number(item.tax) || 0), 0)
)
const computedTotal = computed(() =>
  lineItems.reduce((sum, item) => sum + (Number(item.total) || 0), 0)
)

function addLineItem() {
  lineItems.push({ name: '', quantity: 1, unit_price: 0, tax: 0, total: 0 })
}

function removeLineItem(idx) {
  lineItems.splice(idx, 1)
}

function onFileChange(fieldName, fileOrFiles) {
  const file = Array.isArray(fileOrFiles)
    ? fileOrFiles[0]?.fileObject || fileOrFiles[0]
    : fileOrFiles

  selectedFile.value = file || null
  scanResult.value = null
  imageLoadError.value = null
}

function onImageLoad() {
  imageLoadError.value = null
}

function onImageError(event) {
  imageLoadError.value = event.target.src
}

function scan() {
  if (!selectedFile.value) return

  scannerStore.scanReceipt(selectedFile.value).then((response) => {
    const data = response.data

    scanResult.value = {
      image_url: data.image_url,
      stored_path: data.stored_path,
      extraction_method: data.extraction_method || 'unknown',
    }

    // Pre-fill header fields
    if (data.data) {
      const d = data.data
      billForm.value.vendor = d.vendor_name || ''
      billForm.value.tax_id = d.tax_id || ''
      billForm.value.bill_number = d.bill_number || ''
      billForm.value.bill_date = d.bill_date || ''
      billForm.value.due_date = d.due_date || ''
    }

    // Pre-fill line items
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
      // If no line items extracted, show single row with total
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
  billForm.value = {
    vendor: '',
    tax_id: '',
    bill_number: '',
    bill_date: '',
    due_date: '',
    notes: ''
  }
  lineItems.splice(0, lineItems.length)
  lineItems.push({ name: '', quantity: 1, unit_price: 0, tax: 0, total: 0 })
}

function createBill() {
  // Store all scanned data in the receipt-scanner store for the Create page to consume
  scannerStore.setScannedBillData({
    bill: {
      bill_number: billForm.value.bill_number,
      bill_date: billForm.value.bill_date,
      due_date: billForm.value.due_date,
      vendor_name: billForm.value.vendor,
      tax_id: billForm.value.tax_id,
      notes: billForm.value.notes,
      scanned_receipt_path: scanResult.value?.stored_path || null,
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

  router.push({ name: 'bills.create' })
}
</script>
