<template>
  <BasePage>
    <BasePageHeader :title="$t('tickets.new_ticket')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('tickets.ticket', 2)" to="support" />
        <BaseBreadcrumbItem :title="$t('tickets.new_ticket')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          variant="primary-outline"
          type="button"
          @click="$router.push('/admin/support')"
        >
          <BaseIcon name="XMarkIcon" class="h-5 w-5 mr-1" />
          {{ $t('general.cancel') }}
        </BaseButton>

        <BaseButton
          :loading="isLoading"
          :disabled="isLoading"
          variant="primary"
          type="submit"
          class="ml-4"
          @click="submitTicket"
        >
          <BaseIcon v-if="!isLoading" name="PaperAirplaneIcon" class="h-5 w-5 mr-1" />
          {{ $t('tickets.create_ticket') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div class="max-w-4xl mx-auto mt-8">
      <BaseCard class="p-6">
        <form @submit.prevent="submitTicket">
          <!-- Title -->
          <BaseInputGroup
            :label="$t('tickets.title')"
            :error="v$.formData.title.$error && v$.formData.title.$errors[0].$message"
            required
          >
            <BaseInput
              v-model="formData.title"
              :invalid="v$.formData.title.$error"
              :placeholder="$t('tickets.title_placeholder')"
              @input="v$.formData.title.$touch()"
            />
          </BaseInputGroup>

          <!-- Description/Message -->
          <BaseInputGroup
            :label="$t('tickets.description')"
            :error="v$.formData.message.$error && v$.formData.message.$errors[0].$message"
            required
            class="mt-6"
          >
            <BaseTextarea
              v-model="formData.message"
              :invalid="v$.formData.message.$error"
              :placeholder="$t('tickets.description_placeholder')"
              rows="8"
              @input="v$.formData.message.$touch()"
            />
            <template #help>
              <span class="text-sm text-gray-500">
                {{ formData.message.length }} / 5000 {{ $t('general.characters') }}
              </span>
            </template>
          </BaseInputGroup>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <!-- Priority -->
            <BaseInputGroup :label="$t('tickets.priority')">
              <BaseMultiselect
                v-model="formData.priority"
                :options="priorityOptions"
                :placeholder="$t('tickets.select_priority')"
                searchable
              />
            </BaseInputGroup>

            <!-- Category -->
            <BaseInputGroup :label="$t('tickets.category')">
              <BaseMultiselect
                v-model="formData.categories"
                :options="categoryOptions"
                :placeholder="$t('tickets.select_category')"
                searchable
                multiple
              />
            </BaseInputGroup>
          </div>

          <!-- Attachments -->
          <BaseInputGroup
            :label="$t('tickets.attachments')"
            class="mt-6"
          >
            <div
              class="
                border-2 border-dashed border-gray-300
                rounded-lg
                p-6
                text-center
                hover:border-primary-400
                transition-colors
                cursor-pointer
              "
              @drop.prevent="handleDrop"
              @dragover.prevent
              @click="$refs.fileInput.click()"
            >
              <input
                ref="fileInput"
                type="file"
                class="hidden"
                multiple
                accept="image/*,application/pdf"
                @change="handleFileSelect"
              />

              <BaseIcon
                name="DocumentArrowUpIcon"
                class="h-12 w-12 mx-auto text-gray-400 mb-2"
              />
              <p class="text-sm text-gray-600">
                {{ $t('tickets.drag_drop_files') }}
              </p>
              <p class="text-xs text-gray-500 mt-1">
                {{ $t('tickets.file_types') }}: Images, PDF ({{ $t('tickets.max_size') }}: 5MB)
              </p>
            </div>

            <!-- File List -->
            <div v-if="attachments.length" class="mt-4 space-y-2">
              <div
                v-for="(file, index) in attachments"
                :key="index"
                class="
                  flex
                  items-center
                  justify-between
                  p-3
                  bg-gray-50
                  rounded-lg
                "
              >
                <div class="flex items-center space-x-3">
                  <BaseIcon
                    :name="getFileIcon(file.type)"
                    class="h-5 w-5 text-gray-500"
                  />
                  <div>
                    <p class="text-sm font-medium text-gray-900">{{ file.name }}</p>
                    <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                  </div>
                </div>

                <button
                  type="button"
                  class="text-red-500 hover:text-red-700"
                  @click="removeAttachment(index)"
                >
                  <BaseIcon name="XMarkIcon" class="h-5 w-5" />
                </button>
              </div>
            </div>

            <template #help>
              <span v-if="fileError" class="text-sm text-red-500">
                {{ fileError }}
              </span>
            </template>
          </BaseInputGroup>
        </form>
      </BaseCard>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTicketStore } from '@/scripts/admin/stores/ticket'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useVuelidate } from '@vuelidate/core'
import { required, minLength, maxLength } from '@vuelidate/validators'

const { t } = useI18n()
const router = useRouter()
const ticketStore = useTicketStore()
const notificationStore = useNotificationStore()

const isLoading = ref(false)
const fileError = ref('')
const attachments = ref([])
const fileInput = ref(null)

const formData = reactive({
  title: '',
  message: '',
  priority: { label: t('tickets.normal'), value: 'normal' },
  categories: [],
})

const priorityOptions = [
  { label: t('tickets.low'), value: 'low' },
  { label: t('tickets.normal'), value: 'normal' },
  { label: t('tickets.high'), value: 'high' },
  { label: t('tickets.urgent'), value: 'urgent' },
]

const categoryOptions = [
  { label: t('tickets.billing_subscriptions'), value: 1 },
  { label: t('tickets.technical_issues'), value: 2 },
  { label: t('tickets.feature_requests'), value: 3 },
  { label: t('tickets.general_questions'), value: 4 },
]

const rules = {
  formData: {
    title: {
      required,
      minLength: minLength(3),
      maxLength: maxLength(255),
    },
    message: {
      required,
      minLength: minLength(10),
      maxLength: maxLength(5000),
    },
  },
}

const v$ = useVuelidate(rules, { formData })

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  validateAndAddFiles(files)
}

const handleDrop = (event) => {
  const files = Array.from(event.dataTransfer.files)
  validateAndAddFiles(files)
}

const validateAndAddFiles = (files) => {
  fileError.value = ''

  for (const file of files) {
    // Check file size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
      fileError.value = t('tickets.file_too_large', { filename: file.name })
      continue
    }

    // Check file type (images + PDF only)
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf']
    if (!allowedTypes.includes(file.type)) {
      fileError.value = t('tickets.invalid_file_type', { filename: file.name })
      continue
    }

    // Add file if not already added
    if (!attachments.value.find((f) => f.name === file.name && f.size === file.size)) {
      attachments.value.push(file)
    }
  }
}

const removeAttachment = (index) => {
  attachments.value.splice(index, 1)
  fileError.value = ''
}

const getFileIcon = (type) => {
  if (type.startsWith('image/')) {
    return 'PhotoIcon'
  }
  if (type === 'application/pdf') {
    return 'DocumentTextIcon'
  }
  return 'DocumentIcon'
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const submitTicket = async () => {
  v$.value.$touch()

  if (v$.value.$invalid) {
    notificationStore.showNotification({
      type: 'error',
      message: t('validation.invalid_form'),
    })
    return
  }

  isLoading.value = true

  try {
    const data = new FormData()
    data.append('title', formData.title)
    data.append('message', formData.message)
    data.append('priority', formData.priority.value)

    // Add categories
    if (formData.categories.length) {
      formData.categories.forEach((category, index) => {
        data.append(`categories[${index}]`, category.value)
      })
    }

    // Add attachments
    attachments.value.forEach((file, index) => {
      data.append(`attachments[${index}]`, file)
    })

    await ticketStore.createTicket(data)

    notificationStore.showNotification({
      type: 'success',
      message: t('tickets.created_message'),
    })

    router.push('/admin/support')
  } catch (error) {
    console.error('Error creating ticket:', error)
  } finally {
    isLoading.value = false
  }
}
</script>
// CLAUDE-CHECKPOINT
