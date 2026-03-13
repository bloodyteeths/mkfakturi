<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white border-b px-6 py-4">
      <div class="flex items-center justify-between max-w-7xl mx-auto">
        <div class="flex items-center space-x-4">
          <button @click="$router.push({ name: 'client-documents' })" class="text-gray-500 hover:text-gray-700">
            <ArrowLeftIcon class="h-5 w-5" />
          </button>
          <div>
            <h1 class="text-lg font-semibold text-gray-900">{{ $t('documents.review_title', 'Review Document') }}</h1>
            <p class="text-sm text-gray-500">{{ document?.original_filename }}</p>
          </div>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="reprocess"
            :disabled="isSubmitting"
            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm font-medium"
          >
            {{ $t('documents.reprocess', 'Reprocess') }}
          </button>
          <button
            @click="confirmAndCreateBill"
            :disabled="isSubmitting"
            class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-md text-sm font-medium disabled:opacity-50"
          >
            {{ isSubmitting ? '...' : $t('documents.confirm_create_bill', 'Confirm & Create Bill') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="isLoading" class="flex items-center justify-center py-20">
      <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>

    <!-- Split Layout -->
    <div v-else-if="document" class="flex flex-col lg:flex-row max-w-7xl mx-auto" style="height: calc(100vh - 80px)">
      <!-- Left: Document Preview -->
      <div class="lg:w-1/2 p-4 overflow-auto border-r border-gray-200">
        <div class="bg-white rounded-lg shadow h-full">
          <iframe
            v-if="document.mime_type === 'application/pdf'"
            :src="previewUrl"
            class="w-full h-full rounded-lg"
            style="min-height: 600px"
          />
          <img
            v-else-if="document.mime_type?.startsWith('image/')"
            :src="previewUrl"
            class="max-w-full mx-auto p-4"
            :alt="document.original_filename"
          />
          <div v-else class="flex items-center justify-center h-full text-gray-500">
            {{ $t('documents.no_preview', 'Preview not available for this file type') }}
          </div>
        </div>
      </div>

      <!-- Right: Editable Form -->
      <div class="lg:w-1/2 p-4 overflow-auto">
        <!-- AI Classification Summary -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.ai_classification', 'AI Classification') }}</h3>
            <span v-if="document.ai_classification" :class="getCategoryBadgeClass(document.ai_classification.type)">
              {{ getCategoryLabel(document.ai_classification.type) }}
              <span v-if="document.ai_classification.confidence" class="ml-1 opacity-75">
                ({{ Math.round(document.ai_classification.confidence * 100) }}%)
              </span>
            </span>
          </div>
          <p v-if="document.ai_classification?.summary" class="text-sm text-gray-600">{{ document.ai_classification.summary }}</p>
        </div>

        <!-- Supplier Info -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.supplier', 'Supplier') }}</h3>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_name', 'Name') }}</label>
              <input v-model="form.supplier.name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.tax_id', 'Tax ID') }}</label>
              <input v-model="form.supplier.tax_id" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_address', 'Address') }}</label>
              <input v-model="form.supplier.address" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.supplier_email', 'Email') }}</label>
              <input v-model="form.supplier.email" type="email" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
          </div>
        </div>

        <!-- Bill Info -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.bill_info', 'Bill Details') }}</h3>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.bill_number', 'Bill Number') }}</label>
              <input v-model="form.bill.bill_number" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.bill_date', 'Date') }}</label>
              <input v-model="form.bill.bill_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.due_date', 'Due Date') }}</label>
              <input v-model="form.bill.due_date" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-primary-500 focus:border-primary-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-500 mb-1">{{ $t('documents.currency', 'Currency') }}</label>
              <input :value="'MKD'" disabled class="w-full px-3 py-2 border border-gray-200 rounded-md text-sm bg-gray-50 text-gray-500" />
            </div>
          </div>
        </div>

        <!-- Line Items -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-medium text-gray-700">{{ $t('documents.line_items', 'Line Items') }}</h3>
            <button @click="addItem" class="text-xs text-primary-600 hover:text-primary-800 font-medium">
              + {{ $t('documents.add_item', 'Add Item') }}
            </button>
          </div>

          <div v-if="form.items.length === 0" class="text-center text-sm text-gray-500 py-4">
            {{ $t('documents.no_items', 'No line items extracted') }}
          </div>

          <div v-else class="space-y-3">
            <div v-for="(item, idx) in form.items" :key="idx" class="border border-gray-200 rounded-md p-3">
              <div class="flex items-start justify-between mb-2">
                <input v-model="item.name" type="text" :placeholder="$t('documents.item_name', 'Item name')" class="flex-1 px-2 py-1 border border-gray-300 rounded text-sm focus:ring-primary-500 focus:border-primary-500" />
                <button @click="form.items.splice(idx, 1)" class="ml-2 text-red-400 hover:text-red-600">
                  <XMarkIcon class="h-4 w-4" />
                </button>
              </div>
              <div class="grid grid-cols-4 gap-2">
                <div>
                  <label class="block text-xs text-gray-400">{{ $t('documents.qty', 'Qty') }}</label>
                  <input v-model.number="item.quantity" type="number" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-400">{{ $t('documents.price', 'Price') }}</label>
                  <input :value="formatCents(item.price)" @input="item.price = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-400">{{ $t('documents.tax', 'Tax') }}</label>
                  <input :value="formatCents(item.tax)" @input="item.tax = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
                <div>
                  <label class="block text-xs text-gray-400">{{ $t('documents.total', 'Total') }}</label>
                  <input :value="formatCents(item.total)" @input="item.total = parseCents($event.target.value)" type="text" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-primary-500 focus:border-primary-500" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Totals -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
          <h3 class="text-sm font-medium text-gray-700 mb-3">{{ $t('documents.totals', 'Totals') }}</h3>
          <div class="space-y-2">
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">{{ $t('documents.subtotal', 'Subtotal') }}</span>
              <span class="font-medium">{{ formatCents(form.bill.sub_total) }}</span>
            </div>
            <div class="flex justify-between text-sm">
              <span class="text-gray-500">{{ $t('documents.tax_total', 'Tax') }}</span>
              <span class="font-medium">{{ formatCents(form.bill.tax) }}</span>
            </div>
            <div class="flex justify-between text-sm font-semibold border-t pt-2">
              <span>{{ $t('documents.grand_total', 'Total') }}</span>
              <span class="text-primary-600">{{ formatCents(form.bill.total) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Not found -->
    <div v-else class="flex items-center justify-center py-20">
      <p class="text-gray-500">{{ $t('documents.not_found', 'Document not found.') }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useDocumentHubStore } from '@/scripts/admin/stores/document-hub'
import { useNotificationStore } from '@/scripts/stores/notification'
import { ArrowLeftIcon, XMarkIcon } from '@heroicons/vue/24/outline'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const store = useDocumentHubStore()
const notificationStore = useNotificationStore()

const document = ref(null)
const isLoading = ref(true)
const isSubmitting = ref(false)
const previewUrl = ref('')

const form = reactive({
  supplier: { name: '', tax_id: '', address: '', email: '' },
  bill: { bill_number: '', bill_date: '', due_date: '', currency_id: null, sub_total: 0, tax: 0, total: 0, discount: 0, discount_val: 0, due_amount: 0, exchange_rate: 1 },
  items: [],
})

onMounted(async () => {
  const id = route.params.id
  try {
    const doc = await store.fetchDocument(id)
    document.value = doc
    previewUrl.value = `/api/v1/client-documents/${id}/download`

    // Pre-fill form from extracted data
    if (doc.extracted_data) {
      const { supplier, bill, items } = doc.extracted_data
      if (supplier) {
        form.supplier = { ...form.supplier, ...supplier }
      }
      if (bill) {
        form.bill = { ...form.bill, ...bill }
      }
      if (items) {
        form.items = items.map((item) => ({ ...item }))
      }
    }
  } catch {
    document.value = null
  } finally {
    isLoading.value = false
  }
})

const confirmAndCreateBill = async () => {
  isSubmitting.value = true
  try {
    const data = await store.confirmDocument(document.value.id, {
      supplier: form.supplier,
      bill: form.bill,
      items: form.items,
    })

    notificationStore.showNotification({
      type: 'success',
      message: t('documents.bill_created', 'Bill created successfully!'),
    })

    // Navigate to the created bill
    if (data.data?.bill_id) {
      router.push({ name: 'bills.view', params: { id: data.data.bill_id } })
    } else {
      router.push({ name: 'client-documents' })
    }
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Failed to create bill.',
    })
  } finally {
    isSubmitting.value = false
  }
}

const reprocess = async () => {
  isSubmitting.value = true
  try {
    await store.reprocessDocument(document.value.id)
    notificationStore.showNotification({
      type: 'success',
      message: t('documents.reprocess_started', 'Reprocessing started.'),
    })
    router.push({ name: 'client-documents' })
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.message || 'Reprocess failed.',
    })
  } finally {
    isSubmitting.value = false
  }
}

const addItem = () => {
  form.items.push({
    name: '',
    description: null,
    quantity: 1,
    price: 0,
    tax: 0,
    total: 0,
    discount: 0,
    discount_val: 0,
    base_price: 0,
    base_total: 0,
    base_tax: 0,
    base_discount_val: 0,
  })
}

// Amounts stored in cents — display as decimal
const formatCents = (cents) => {
  if (!cents && cents !== 0) return '0.00'
  return (Number(cents) / 100).toFixed(2)
}

const parseCents = (val) => {
  const num = parseFloat(val)
  return isNaN(num) ? 0 : Math.round(num * 100)
}

const getCategoryLabel = (type) => {
  const labels = {
    invoice: t('documents.type_invoice', 'Invoice'),
    receipt: t('documents.type_receipt', 'Receipt'),
    contract: t('documents.type_contract', 'Contract'),
    bank_statement: t('documents.type_bank_statement', 'Bank Statement'),
    tax_form: t('documents.type_tax_form', 'Tax Form'),
    other: t('documents.type_other', 'Other'),
  }
  return labels[type] || type
}

const getCategoryBadgeClass = (type) => {
  const classes = {
    invoice: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
    receipt: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800',
    contract: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800',
    bank_statement: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800',
    tax_form: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800',
    other: 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800',
  }
  return classes[type] || classes.other
}
</script>
