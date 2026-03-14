<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
        {{ $t('onboarding.step4.title') }}
      </h2>
      <p class="mt-1.5 text-sm text-gray-500 leading-relaxed">
        {{ $t('onboarding.step4.subtitle') }}
      </p>
    </div>

    <!-- Upload state -->
    <div v-if="!analysisResult" class="space-y-4">
      <!-- Upload zone -->
      <div
        class="relative overflow-hidden rounded-2xl border-2 border-dashed p-10 text-center transition-all duration-300"
        :class="
          isDragging
            ? 'border-indigo-400 bg-gradient-to-br from-indigo-50 to-purple-50 scale-[1.01] shadow-lg'
            : 'border-gray-200 bg-gradient-to-br from-slate-50 to-white hover:border-indigo-200 hover:shadow-sm'
        "
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="onDrop"
      >
        <div
          class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl transition-all duration-300"
          :class="isDragging ? 'bg-indigo-100 scale-110' : 'bg-gray-100'"
        >
          <BaseIcon
            name="BanknotesIcon"
            class="h-8 w-8 transition-colors duration-300"
            :class="isDragging ? 'text-indigo-500' : 'text-gray-400'"
          />
        </div>
        <p class="mb-1 text-sm font-semibold text-gray-700">
          {{ $t('onboarding.step4.upload_prompt') }}
        </p>
        <p class="mb-4 text-xs text-gray-400">
          {{ $t('onboarding.step4.upload_formats') }}
        </p>
        <BaseButton
          variant="primary"
          size="sm"
          :loading="isAnalyzing"
          @click="$refs.bankFileInput.click()"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="FolderOpenIcon" />
          </template>
          {{ $t('onboarding.step4.select_file') }}
        </BaseButton>
        <input
          ref="bankFileInput"
          type="file"
          accept=".csv,.txt,.pdf,.jpg,.jpeg,.png"
          class="hidden"
          @change="onFileSelect"
        />
      </div>

      <!-- Loading state -->
      <div v-if="isAnalyzing" class="flex flex-col items-center justify-center gap-3 py-12">
        <div class="relative h-12 w-12">
          <div class="absolute inset-0 rounded-full border-4 border-primary-100" />
          <div class="absolute inset-0 rounded-full border-4 border-primary-500 border-t-transparent animate-spin" />
        </div>
        <span class="text-sm font-medium text-gray-600">{{ $t('onboarding.step4.analyzing') }}</span>
      </div>

      <!-- Error state -->
      <div v-if="error" class="rounded-xl border border-red-200 bg-red-50 p-4">
        <div class="flex items-start gap-3">
          <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-red-100">
            <BaseIcon name="ExclamationTriangleIcon" class="h-4 w-4 text-red-500" />
          </div>
          <p class="text-sm text-red-700">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Analysis Results -->
    <div v-else class="space-y-6">
      <!-- Summary cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-5 text-center shadow-lg shadow-blue-500/20">
          <p class="text-3xl font-black text-white">{{ analysisResult.transaction_count }}</p>
          <p class="mt-1 text-xs font-medium text-blue-100">{{ $t('onboarding.step4.transactions_found') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 p-5 text-center shadow-lg shadow-emerald-500/20">
          <p class="text-3xl font-black text-white">{{ selectedCustomers.length }}</p>
          <p class="mt-1 text-xs font-medium text-emerald-100">{{ $t('onboarding.step4.customers') }}</p>
        </div>
        <div class="rounded-2xl bg-gradient-to-br from-orange-500 to-orange-600 p-5 text-center shadow-lg shadow-orange-500/20">
          <p class="text-3xl font-black text-white">{{ selectedSuppliers.length }}</p>
          <p class="mt-1 text-xs font-medium text-orange-100">{{ $t('onboarding.step4.suppliers') }}</p>
        </div>
      </div>

      <!-- Suggested Customers -->
      <div v-if="analysisResult.suggested_customers.length > 0">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900">
            <div class="h-2 w-2 rounded-full bg-emerald-500" />
            {{ $t('onboarding.step4.suggested_customers') }}
          </h3>
          <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition-colors">
            <input
              type="checkbox"
              :checked="allCustomersSelected"
              class="h-3.5 w-3.5 rounded border-gray-300 text-primary-600"
              @change="toggleAllCustomers"
            />
            {{ $t('onboarding.step4.select_all') }}
          </label>
        </div>
        <div class="max-h-52 overflow-y-auto rounded-xl border border-gray-100 shadow-sm">
          <table class="w-full text-sm">
            <thead class="bg-gray-50/80 sticky top-0">
              <tr>
                <th class="w-10 px-3 py-2.5" />
                <th class="px-3 py-2.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step4.name') }}</th>
                <th class="px-3 py-2.5 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step4.tx_count') }}</th>
                <th class="px-3 py-2.5 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step4.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(customer, idx) in analysisResult.suggested_customers"
                :key="'c-' + idx"
                class="border-t border-gray-50 transition-colors hover:bg-gray-50/50"
              >
                <td class="px-3 py-2.5">
                  <input
                    v-model="customerSelection[idx]"
                    type="checkbox"
                    class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                  />
                </td>
                <td class="px-3 py-2.5 font-medium text-gray-900">{{ customer.name }}</td>
                <td class="px-3 py-2.5 text-right">
                  <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ customer.transaction_count }}</span>
                </td>
                <td class="px-3 py-2.5 text-right font-semibold text-gray-700">{{ formatAmount(customer.total_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Suggested Suppliers -->
      <div v-if="analysisResult.suggested_suppliers.length > 0">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="flex items-center gap-2 text-sm font-bold text-gray-900">
            <div class="h-2 w-2 rounded-full bg-orange-500" />
            {{ $t('onboarding.step4.suggested_suppliers') }}
          </h3>
          <label class="flex items-center gap-2 text-xs text-gray-500 cursor-pointer hover:text-gray-700 transition-colors">
            <input
              type="checkbox"
              :checked="allSuppliersSelected"
              class="h-3.5 w-3.5 rounded border-gray-300 text-primary-600"
              @change="toggleAllSuppliers"
            />
            {{ $t('onboarding.step4.select_all') }}
          </label>
        </div>
        <div class="max-h-52 overflow-y-auto rounded-xl border border-gray-100 shadow-sm">
          <table class="w-full text-sm">
            <thead class="bg-gray-50/80 sticky top-0">
              <tr>
                <th class="w-10 px-3 py-2.5" />
                <th class="px-3 py-2.5 text-left text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step4.name') }}</th>
                <th class="px-3 py-2.5 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step4.tx_count') }}</th>
                <th class="px-3 py-2.5 text-right text-[11px] font-bold text-gray-400 uppercase tracking-wider">{{ $t('onboarding.step4.total') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(supplier, idx) in analysisResult.suggested_suppliers"
                :key="'s-' + idx"
                class="border-t border-gray-50 transition-colors hover:bg-gray-50/50"
              >
                <td class="px-3 py-2.5">
                  <input
                    v-model="supplierSelection[idx]"
                    type="checkbox"
                    class="h-3.5 w-3.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                  />
                </td>
                <td class="px-3 py-2.5 font-medium text-gray-900">{{ supplier.name }}</td>
                <td class="px-3 py-2.5 text-right">
                  <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ supplier.transaction_count }}</span>
                </td>
                <td class="px-3 py-2.5 text-right font-semibold text-gray-700">{{ formatAmount(supplier.total_amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Confirm button -->
      <div class="flex gap-3">
        <BaseButton
          variant="primary"
          :loading="isConfirming"
          @click="confirmEntities"
        >
          <template #left="slotProps">
            <BaseIcon :class="slotProps.class" name="CheckIcon" />
          </template>
          {{ $t('onboarding.step4.confirm_create', { count: selectedCount }) }}
        </BaseButton>
        <BaseButton variant="gray" @click="$emit('skip')">
          {{ $t('onboarding.step4.skip') }}
        </BaseButton>
      </div>
    </div>

    <!-- Skip option when no file uploaded -->
    <div v-if="!analysisResult && !isAnalyzing" class="mt-6">
      <BaseButton variant="gray" @click="$emit('skip')">
        {{ $t('onboarding.step4.skip') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

const { t } = useI18n()

const isDragging = ref(false)
const isAnalyzing = ref(false)
const isConfirming = ref(false)
const error = ref(null)
const analysisResult = ref(null)

const customerSelection = reactive({})
const supplierSelection = reactive({})

function onDrop(e) {
  isDragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) analyzeFile(file)
}

function onFileSelect(e) {
  const file = e.target.files[0]
  if (file) analyzeFile(file)
  e.target.value = ''
}

async function analyzeFile(file) {
  isAnalyzing.value = true
  error.value = null

  const formData = new FormData()
  formData.append('file', file)

  try {
    const { data } = await axios.post('/onboarding/analyze-bank', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    if (!data.success) {
      error.value = data.message || t('onboarding.step4.analysis_failed')
      return
    }

    analysisResult.value = data
    data.suggested_customers.forEach((_, i) => { customerSelection[i] = true })
    data.suggested_suppliers.forEach((_, i) => { supplierSelection[i] = true })
  } catch (e) {
    error.value = e.response?.data?.message || t('onboarding.step4.analysis_failed')
  } finally {
    isAnalyzing.value = false
  }
}

const selectedCustomers = computed(() =>
  analysisResult.value?.suggested_customers.filter((_, i) => customerSelection[i]) || []
)

const selectedSuppliers = computed(() =>
  analysisResult.value?.suggested_suppliers.filter((_, i) => supplierSelection[i]) || []
)

const selectedCount = computed(() =>
  selectedCustomers.value.length + selectedSuppliers.value.length
)

const allCustomersSelected = computed(() =>
  analysisResult.value?.suggested_customers.every((_, i) => customerSelection[i])
)

const allSuppliersSelected = computed(() =>
  analysisResult.value?.suggested_suppliers.every((_, i) => supplierSelection[i])
)

function toggleAllCustomers(e) {
  const checked = e.target.checked
  analysisResult.value?.suggested_customers.forEach((_, i) => {
    customerSelection[i] = checked
  })
}

function toggleAllSuppliers(e) {
  const checked = e.target.checked
  analysisResult.value?.suggested_suppliers.forEach((_, i) => {
    supplierSelection[i] = checked
  })
}

const emit = defineEmits(['done', 'skip'])

async function confirmEntities() {
  isConfirming.value = true

  const entities = [
    ...selectedCustomers.value.map(c => ({ name: c.name, type: 'customer' })),
    ...selectedSuppliers.value.map(s => ({ name: s.name, type: 'supplier' })),
  ]

  if (entities.length === 0) {
    emit('done')
    return
  }

  try {
    await axios.post('/onboarding/confirm-entities', { entities })
    emit('done')
  } catch (e) {
    error.value = e.response?.data?.message || t('onboarding.step4.confirm_failed')
  } finally {
    isConfirming.value = false
  }
}

function formatAmount(amount) {
  return new Intl.NumberFormat('mk-MK', {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}
</script>
