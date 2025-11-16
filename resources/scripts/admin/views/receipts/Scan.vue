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

        <BaseSpinner v-if="scannerStore.isScanning" class="mt-4" />

        <!-- Results Layout: Image with Selectable Text Overlay -->
        <div v-if="scanResult" class="mt-6">
          <BaseHeading tag="h3" size="sm" class="mb-3">
            {{ $t('receipts.scanned_receipt') }}
          </BaseHeading>

          <!-- Full-width image with selectable text overlay (like macOS Preview) -->
          <div class="border rounded-lg overflow-auto bg-gray-50 max-h-screen">
            <div class="relative inline-block min-w-full">
              <img
                :src="scanResult.image_url"
                :alt="$t('receipts.receipt_image')"
                class="w-full h-auto"
                style="display: block;"
              />
              <!-- hOCR text overlay (if available) - selectable text on top of image -->
              <div
                v-if="scanResult.hocr"
                v-html="scanResult.hocr"
                class="absolute inset-0 pointer-events-auto"
                style="user-select: text; -webkit-user-select: text;"
              ></div>
            </div>
          </div>

          <!-- Collapsible OCR text for debugging/copying -->
          <details class="mt-4">
            <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-900">
              {{ $t('receipts.show_ocr_text') }}
            </summary>
            <BaseTextarea
              v-model="ocrText"
              :rows="15"
              :readonly="true"
              class="font-mono text-sm mt-2"
              :placeholder="$t('receipts.no_text_extracted')"
            />
          </details>

          <!-- Bottom: Bill Creation Form -->
          <div class="mt-8">
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

                <BaseInputGroup :label="$t('bills.amount')">
                  <BaseInput
                    v-model="billForm.amount"
                    type="number"
                    step="0.01"
                    :placeholder="$t('bills.amount_placeholder')"
                  />
                </BaseInputGroup>

                <BaseInputGroup :label="$t('bills.tax_amount')">
                  <BaseInput
                    v-model="billForm.tax_amount"
                    type="number"
                    step="0.01"
                    :placeholder="$t('bills.tax_placeholder')"
                  />
                </BaseInputGroup>
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
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useReceiptScannerStore } from '@/scripts/admin/stores/receipt-scanner'

const scannerStore = useReceiptScannerStore()
const router = useRouter()
const selectedFile = ref(null)
const files = ref([])

// Scan result data
const scanResult = ref(null)
const ocrText = ref('')

// Bill form data
const billForm = ref({
  vendor: '',
  bill_number: '',
  bill_date: '',
  due_date: '',
  amount: '',
  tax_amount: '',
  notes: ''
})

function onFileChange(fieldName, fileOrFiles) {
  const file = Array.isArray(fileOrFiles)
    ? fileOrFiles[0]?.fileObject || fileOrFiles[0]
    : fileOrFiles

  selectedFile.value = file || null
  // Reset results when a new file is selected
  scanResult.value = null
  ocrText.value = ''
}

function scan() {
  if (!selectedFile.value) return

  scannerStore.scanReceipt(selectedFile.value).then((response) => {
    console.log('Receipt scan response:', response)
    console.log('Response data:', response.data)

    // Store the scan result
    scanResult.value = {
      image_url: response.data.image_url,
      stored_path: response.data.stored_path,
      ocr_text: response.data.ocr_text || '',
      hocr: response.data.hocr || null,
      image_width: response.data.image_width || null,
      image_height: response.data.image_height || null,
    }

    console.log('scanResult set to:', scanResult.value)

    // Set OCR text for display
    ocrText.value = response.data.ocr_text || ''

    console.log('Has hOCR:', !!response.data.hocr)

    console.log('ocrText set to:', ocrText.value ? ocrText.value.substring(0, 100) : '(empty)')

    // Pre-fill form if structured data is available
    if (response.data.data) {
      const data = response.data.data
      billForm.value.vendor = data.vendor_name || ''
      billForm.value.bill_number = data.bill_number || ''
      billForm.value.bill_date = data.bill_date || ''
      billForm.value.due_date = data.due_date || ''
      billForm.value.amount = data.total || ''
      billForm.value.tax_amount = data.tax || ''
    }
  }).catch((error) => {
    console.error('Scan failed:', error)
    console.error('Error response:', error.response)
  })
}

function resetForm() {
  billForm.value = {
    vendor: '',
    bill_number: '',
    bill_date: '',
    due_date: '',
    amount: '',
    tax_amount: '',
    notes: ''
  }
}

function createBill() {
  // Navigate to bill creation page with pre-filled data and scanned receipt attachment
  router.push({
    name: 'bills.create',
    query: {
      vendor: billForm.value.vendor,
      bill_number: billForm.value.bill_number,
      bill_date: billForm.value.bill_date,
      due_date: billForm.value.due_date,
      amount: billForm.value.amount,
      tax_amount: billForm.value.tax_amount,
      notes: billForm.value.notes,
      scanned_receipt_path: scanResult.value?.stored_path
    }
  })
}
// CLAUDE-CHECKPOINT
</script>
