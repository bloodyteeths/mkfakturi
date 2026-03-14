<template>
  <div>
    <div class="mb-6">
      <h2 class="text-2xl font-bold text-gray-900 tracking-tight">
        {{ $t('onboarding.step3.title') }}
      </h2>
      <p class="mt-1.5 text-sm text-gray-500 leading-relaxed">
        {{ $t('onboarding.step3.subtitle') }}
      </p>
    </div>

    <!-- Drag & Drop Zone — animated gradient border -->
    <div
      class="relative mb-6 overflow-hidden rounded-2xl border-2 border-dashed p-10 text-center transition-all duration-300"
      :class="
        isDragging
          ? 'border-primary-400 bg-gradient-to-br from-primary-50 to-indigo-50 scale-[1.01] shadow-lg shadow-primary-500/10'
          : 'border-gray-200 bg-gradient-to-br from-gray-50 to-white hover:border-primary-200 hover:shadow-sm'
      "
      @dragover.prevent="isDragging = true"
      @dragleave.prevent="isDragging = false"
      @drop.prevent="onDrop"
    >
      <!-- Animated upload icon -->
      <div
        class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl transition-all duration-300"
        :class="isDragging ? 'bg-primary-100 scale-110' : 'bg-gray-100'"
      >
        <BaseIcon
          name="CloudArrowUpIcon"
          class="h-8 w-8 transition-colors duration-300"
          :class="isDragging ? 'text-primary-500' : 'text-gray-400'"
        />
      </div>
      <p class="mb-1 text-sm font-semibold text-gray-700">
        {{ $t('onboarding.step3.drag_drop') }}
      </p>
      <p class="mb-4 text-xs text-gray-400">
        {{ $t('onboarding.step3.formats') }}
      </p>
      <BaseButton variant="primary" size="sm" @click="$refs.fileInput.click()">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="FolderOpenIcon" />
        </template>
        {{ $t('onboarding.step3.browse_files') }}
      </BaseButton>
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".csv,.txt,.xls,.xlsx,.xml,.pdf,.jpg,.jpeg,.png"
        class="hidden"
        @change="onFileSelect"
      />
    </div>

    <!-- Uploaded Files List -->
    <div v-if="files.length > 0" class="space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-900">
          {{ $t('onboarding.step3.uploaded_files') }}
        </h3>
        <span class="rounded-full bg-primary-50 px-2 py-0.5 text-xs font-bold text-primary-600">
          {{ files.length }}
        </span>
      </div>

      <div
        v-for="(file, index) in files"
        :key="index"
        class="group flex items-center gap-3 rounded-xl border bg-white p-3.5 transition-all duration-200 hover:shadow-sm"
        :class="{
          'border-green-200 bg-green-50/30': file.status === 'imported',
          'border-gray-100': file.status !== 'imported',
        }"
      >
        <!-- File icon -->
        <div
          class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl transition-colors"
          :class="iconBg(file.detectedType)"
        >
          <BaseIcon :name="getFileIcon(file)" class="h-5 w-5" :class="iconColor(file.detectedType)" />
        </div>

        <!-- File info -->
        <div class="flex-1 min-w-0">
          <p class="truncate text-sm font-semibold text-gray-900">
            {{ file.name }}
          </p>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[11px] text-gray-400">{{ formatSize(file.size) }}</span>
            <span
              v-if="file.detectedType"
              class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
              :class="typeClass(file.detectedType)"
            >
              {{ typeLabel(file.detectedType) }}
            </span>
          </div>
        </div>

        <!-- Status / Action -->
        <div class="flex items-center gap-2">
          <span
            v-if="file.status === 'imported'"
            class="flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700"
          >
            <BaseIcon name="CheckCircleIcon" class="h-3.5 w-3.5" />
            {{ $t('onboarding.step3.imported') }}
          </span>
          <BaseButton
            v-else
            variant="primary"
            size="sm"
            @click="importFile(file)"
          >
            {{ $t('onboarding.step3.import') }}
          </BaseButton>
          <button
            class="rounded-lg p-1 text-gray-300 transition-colors hover:bg-red-50 hover:text-red-500"
            @click="removeFile(index)"
          >
            <BaseIcon name="XMarkIcon" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Quick import tips (when no files) -->
    <div v-if="files.length === 0" class="mt-4">
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div
          v-for="tip in tips"
          :key="tip.key"
          class="rounded-xl border border-gray-100 bg-white p-4 transition-all hover:border-gray-200 hover:shadow-sm"
        >
          <div class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg" :class="tip.iconBg">
            <BaseIcon :name="tip.icon" class="h-4 w-4" :class="tip.iconColor" />
          </div>
          <p class="text-xs font-semibold text-gray-700">{{ tip.title }}</p>
          <p class="mt-0.5 text-[11px] text-gray-400 leading-relaxed">{{ tip.desc }}</p>
        </div>
      </div>
    </div>

    <!-- Action buttons -->
    <div class="mt-8 flex gap-3">
      <BaseButton
        v-if="files.length > 0"
        variant="primary"
        @click="$emit('done')"
      >
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="ArrowRightIcon" />
        </template>
        {{ $t('onboarding.step3.continue') }}
      </BaseButton>
      <BaseButton variant="gray" @click="$emit('skip')">
        {{ $t('onboarding.step3.skip') }}
      </BaseButton>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'

const { t } = useI18n()
const router = useRouter()

const props = defineProps({
  source: {
    type: String,
    default: null,
  },
})

defineEmits(['done', 'skip'])

const files = ref([])
const isDragging = ref(false)

const tips = computed(() => {
  const items = []
  if (props.source === 'pantheon') {
    items.push({
      key: 'pantheon',
      icon: 'BookOpenIcon',
      iconBg: 'bg-blue-50',
      iconColor: 'text-blue-500',
      title: t('onboarding.step3.tip_pantheon_txt'),
      desc: '.txt',
    })
  }
  items.push(
    {
      key: 'csv',
      icon: 'TableCellsIcon',
      iconBg: 'bg-green-50',
      iconColor: 'text-green-500',
      title: t('onboarding.step3.tip_csv'),
      desc: '.csv / .xlsx',
    },
    {
      key: 'bank',
      icon: 'BanknotesIcon',
      iconBg: 'bg-purple-50',
      iconColor: 'text-purple-500',
      title: t('onboarding.step3.tip_bank'),
      desc: '.csv / .pdf',
    },
  )
  return items
})

function onDrop(e) {
  isDragging.value = false
  const droppedFiles = Array.from(e.dataTransfer.files)
  addFiles(droppedFiles)
}

function onFileSelect(e) {
  const selectedFiles = Array.from(e.target.files)
  addFiles(selectedFiles)
  e.target.value = ''
}

function addFiles(newFiles) {
  for (const file of newFiles) {
    const detectedType = detectFileType(file)
    files.value.push({
      name: file.name,
      size: file.size,
      file: file,
      detectedType,
      status: 'pending',
    })
  }
}

function removeFile(index) {
  files.value.splice(index, 1)
}

function detectFileType(file) {
  const name = file.name.toLowerCase()
  const ext = name.split('.').pop()

  if (ext === 'txt') {
    if (name.includes('nalog') || name.includes('nalozi') || name.includes('dnevnik')) return 'journal'
    if (name.includes('firmi') || name.includes('firma')) return 'firms'
    return 'journal'
  }

  if (name.includes('izvod') || name.includes('izvodi') || name.includes('statement') || name.includes('bank')) return 'bank_statement'
  if (['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) return 'bank_statement'

  if (['csv', 'xls', 'xlsx'].includes(ext)) {
    if (name.includes('partner') || name.includes('kupuvac') || name.includes('dobavuvac') || name.includes('klient')) return 'customers'
    if (name.includes('faktur') || name.includes('invoice')) return 'invoices'
    if (name.includes('proizvod') || name.includes('artikl') || name.includes('item') || name.includes('product')) return 'items'
    if (name.includes('konto') || name.includes('account') || name.includes('smetk')) return 'chart_of_accounts'
    if (name.includes('nalog') || name.includes('journal') || name.includes('dnevnik')) return 'journal'
    return 'unknown'
  }

  return 'unknown'
}

function importFile(file) {
  const type = file.detectedType
  switch (type) {
    case 'journal':
      router.push({ name: 'partner.accounting.journal-import' })
      file.status = 'imported'
      break
    case 'customers':
    case 'invoices':
    case 'items':
      router.push({ name: 'imports.wizard' })
      file.status = 'imported'
      break
    case 'bank_statement':
      router.push({ name: 'banking' })
      file.status = 'imported'
      break
    case 'chart_of_accounts':
      router.push({ name: 'partner.accounting.chart-of-accounts' })
      file.status = 'imported'
      break
    default:
      router.push({ name: 'imports.wizard' })
      file.status = 'imported'
  }
}

function getFileIcon(file) {
  const icons = {
    journal: 'BookOpenIcon',
    firms: 'UsersIcon',
    customers: 'UsersIcon',
    invoices: 'DocumentTextIcon',
    items: 'CubeIcon',
    bank_statement: 'BanknotesIcon',
    chart_of_accounts: 'CalculatorIcon',
    unknown: 'DocumentIcon',
  }
  return icons[file.detectedType] || 'DocumentIcon'
}

function iconBg(type) {
  const map = {
    journal: 'bg-emerald-50',
    firms: 'bg-blue-50',
    customers: 'bg-blue-50',
    invoices: 'bg-orange-50',
    items: 'bg-indigo-50',
    bank_statement: 'bg-purple-50',
    chart_of_accounts: 'bg-amber-50',
    unknown: 'bg-gray-50',
  }
  return map[type] || 'bg-gray-50'
}

function iconColor(type) {
  const map = {
    journal: 'text-emerald-500',
    firms: 'text-blue-500',
    customers: 'text-blue-500',
    invoices: 'text-orange-500',
    items: 'text-indigo-500',
    bank_statement: 'text-purple-500',
    chart_of_accounts: 'text-amber-500',
    unknown: 'text-gray-400',
  }
  return map[type] || 'text-gray-400'
}

function typeLabel(type) {
  const labels = {
    journal: t('onboarding.step3.type_journal'),
    firms: t('onboarding.step3.type_firms'),
    customers: t('onboarding.step3.type_customers'),
    invoices: t('onboarding.step3.type_invoices'),
    items: t('onboarding.step3.type_items'),
    bank_statement: t('onboarding.step3.type_bank'),
    chart_of_accounts: t('onboarding.step3.type_chart'),
    unknown: t('onboarding.step3.type_unknown'),
  }
  return labels[type] || type
}

function typeClass(type) {
  const classes = {
    journal: 'bg-emerald-50 text-emerald-700',
    firms: 'bg-blue-50 text-blue-700',
    customers: 'bg-blue-50 text-blue-700',
    invoices: 'bg-orange-50 text-orange-700',
    items: 'bg-indigo-50 text-indigo-700',
    bank_statement: 'bg-purple-50 text-purple-700',
    chart_of_accounts: 'bg-amber-50 text-amber-700',
    unknown: 'bg-gray-100 text-gray-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

function formatSize(bytes) {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>
