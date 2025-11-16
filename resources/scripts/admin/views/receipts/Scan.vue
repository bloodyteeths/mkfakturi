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

        <!-- Results Layout: Image + OCR Text -->
        <div v-if="scanResult" class="mt-6">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left Side: Image Preview -->
            <div>
              <BaseHeading tag="h3" size="sm" class="mb-3">
                {{ $t('receipts.uploaded_image') }}
              </BaseHeading>
              <div class="border rounded-lg overflow-hidden bg-gray-50">
                <img
                  :src="scanResult.image_url"
                  :alt="$t('receipts.receipt_image')"
                  class="w-full h-auto max-h-96 object-contain"
                />
              </div>
            </div>

            <!-- Right Side: OCR Text -->
            <div>
              <BaseHeading tag="h3" size="sm" class="mb-3">
                {{ $t('receipts.ocr_text') }}
              </BaseHeading>
              <BaseTextarea
                v-model="ocrText"
                :rows="15"
                :readonly="true"
                class="font-mono text-sm"
                :placeholder="$t('receipts.no_text_extracted')"
              />
            </div>
          </div>

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
      ocr_text: response.data.ocr_text || ''
    }

    console.log('scanResult set to:', scanResult.value)

    // Set OCR text for display
    ocrText.value = response.data.ocr_text || ''

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
  // Navigate to bill creation page with pre-filled data
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
      attachment: scanResult.value?.stored_path
    }
  })
}
// CLAUDE-CHECKPOINT
</script>
