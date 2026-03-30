<template>
  <div v-if="hasDocument || allowUpload" class="mt-4">
    <!-- Existing document preview -->
    <div v-if="hasDocument" class="border border-gray-200 rounded-lg overflow-hidden">
      <div class="flex items-center justify-between px-4 py-2 bg-gray-50 border-b border-gray-200">
        <span class="text-sm font-medium text-gray-700">
          {{ label || $t('general.attached_document', 'Attached Document') }}
        </span>
        <div class="flex items-center gap-2">
          <a
            :href="documentUrl"
            target="_blank"
            class="inline-flex items-center text-sm text-primary-500 hover:text-primary-700"
          >
            <BaseIcon name="ArrowDownTrayIcon" class="w-4 h-4 mr-1" />
            {{ $t('general.download') }}
          </a>
          <button
            v-if="allowRemove"
            type="button"
            class="text-sm text-red-500 hover:text-red-700"
            @click="$emit('remove')"
          >
            <BaseIcon name="TrashIcon" class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- PDF / Image preview -->
      <div v-if="isPdf" class="h-80">
        <iframe
          :src="documentUrl"
          class="w-full h-full border-0"
          :title="label"
        />
      </div>
      <div v-else-if="isImage" class="p-4 flex justify-center bg-white">
        <img
          :src="documentUrl"
          :alt="label"
          class="max-h-80 object-contain rounded"
        />
      </div>
      <div v-else class="p-4 text-center text-sm text-gray-500">
        <BaseIcon name="DocumentIcon" class="w-8 h-8 mx-auto mb-2 text-gray-400" />
        {{ fileName || $t('general.document_attached', 'Document attached') }}
      </div>
    </div>

    <!-- Upload slot (when no document exists) -->
    <div v-else-if="allowUpload">
      <BaseInputGroup :label="label || $t('general.attach_document', 'Attach Document')">
        <BaseFileUploader
          v-model="files"
          :accept="accept"
          @change="onFileChange"
          @remove="onFileRemove"
        />
      </BaseInputGroup>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  documentUrl: {
    type: String,
    default: null,
  },
  fileName: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: null,
  },
  allowUpload: {
    type: Boolean,
    default: false,
  },
  allowRemove: {
    type: Boolean,
    default: false,
  },
  accept: {
    type: String,
    default: 'image/*,.doc,.docx,.pdf,.csv,.xlsx,.xls',
  },
})

const emit = defineEmits(['change', 'remove'])

const files = ref([])

const hasDocument = computed(() => !!props.documentUrl)

const isPdf = computed(() => {
  if (!props.documentUrl) return false
  return props.documentUrl.includes('.pdf') || props.documentUrl.includes('application/pdf')
})

const isImage = computed(() => {
  if (!props.documentUrl) return false
  return /\.(jpe?g|png|gif|webp|bmp|svg)/i.test(props.documentUrl)
})

function onFileChange(fileName, file) {
  emit('change', fileName, file)
}

function onFileRemove() {
  emit('remove')
}
</script>
