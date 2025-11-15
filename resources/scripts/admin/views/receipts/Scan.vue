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

        <div v-if="scannerStore.lastResult" class="mt-4">
          <BaseHeading tag="h3" size="sm">
            {{ $t('receipts.scan_result') }}
          </BaseHeading>
          <div class="mt-2 text-sm bg-gray-50 p-3 rounded space-y-1">
            <div>
              <span class="font-medium">
                {{ $t('receipts.document_type') }}:
              </span>
              <span>{{ documentTypeLabel }}</span>
            </div>
            <div v-if="document && document.total">
              <span class="font-medium">
                {{ $t('bills.total') }}:
              </span>
              <BaseFormatMoney
                :amount="document.total"
                :currency="document.currency"
              />
            </div>
            <div v-if="document && document.bill_number">
              <span class="font-medium">
                {{ $t('bills.bill_number') }}:
              </span>
              <span>{{ document.bill_number }}</span>
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

const documentType = computed(
  () => scannerStore.lastResult && scannerStore.lastResult.document_type
)

const document = computed(
  () => scannerStore.lastResult && scannerStore.lastResult.data
)

const documentTypeLabel = computed(() => {
  if (documentType.value === 'bill') {
    return 'Bill'
  }
  if (documentType.value === 'expense') {
    return 'Expense'
  }
  return ''
})

function onFileChange(fieldName, fileOrFiles) {
  const file = Array.isArray(fileOrFiles)
    ? fileOrFiles[0]?.fileObject || fileOrFiles[0]
    : fileOrFiles

  selectedFile.value = file || null
}

function scan() {
  if (!selectedFile.value) return
  scannerStore.scanReceipt(selectedFile.value).then((response) => {
    const type = response.data.document_type
    const data = response.data.data

    if (type === 'bill') {
      router.push(`/admin/bills/${data.id}/edit`)
    } else if (type === 'expense') {
      router.push(`/admin/expenses/${data.id}/edit`)
    }
  })
}
</script>
