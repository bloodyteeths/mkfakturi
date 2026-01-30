<template>
  <BasePage>
    <BasePageHeader
      v-if="ticket"
      :title="`#${ticket.id} - ${ticket.title}`"
    >
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('tickets.ticket', 2)" to="support" />
        <BaseBreadcrumbItem :title="`#${ticket.id}`" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/support')"
        >
          <BaseIcon name="ArrowLeftIcon" class="h-5 w-5 mr-1" />
          {{ $t('general.back') }}
        </BaseButton>

        <!-- Admin-only actions dropdown -->
        <BaseDropdown v-if="isAdminOrOwner" class="ml-4">
          <template #activator>
            <BaseButton variant="primary-outline">
              {{ $t('general.actions') }}
              <template #right="slotProps">
                <BaseIcon name="ChevronDownIcon" :class="slotProps.class" />
              </template>
            </BaseButton>
          </template>

          <BaseDropdownItem @click="updateTicketStatus('resolved')">
            <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-gray-600" />
            {{ $t('tickets.mark_resolved') }}
          </BaseDropdownItem>

          <BaseDropdownItem @click="updateTicketStatus('closed')">
            <BaseIcon name="XCircleIcon" class="h-5 mr-3 text-gray-600" />
            {{ $t('tickets.close_ticket') }}
          </BaseDropdownItem>

          <BaseDropdownItem @click="deleteCurrentTicket">
            <BaseIcon name="TrashIcon" class="h-5 mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </template>
    </BasePageHeader>

    <div v-if="ticketStore.isFetchingTicket" class="flex justify-center items-center h-64">
      <BaseSpinner />
    </div>

    <div v-else-if="ticket" class="max-w-5xl mx-auto mt-8">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content (Messages) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Ticket Header -->
          <BaseCard class="p-6">
            <div class="flex items-start justify-between mb-4">
              <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                  {{ ticket.title }}
                </h1>
                <div class="flex items-center space-x-4 text-sm text-gray-600">
                  <span class="flex items-center">
                    <BaseIcon name="UserIcon" class="h-4 w-4 mr-1" />
                    {{ ticket.user?.name || 'Unknown' }}
                  </span>
                  <span class="flex items-center">
                    <BaseIcon name="CalendarIcon" class="h-4 w-4 mr-1" />
                    {{ formatDate(ticket.created_at) }}
                  </span>
                </div>
              </div>

              <div class="flex flex-col items-end space-y-2">
                <span
                  :class="getStatusBadgeClass(ticket.status)"
                  class="px-3 py-1 text-sm font-medium rounded-full"
                >
                  {{ getStatusLabel(ticket.status) }}
                </span>
                <span
                  :class="getPriorityBadgeClass(ticket.priority)"
                  class="px-3 py-1 text-sm font-medium rounded-full"
                >
                  {{ getPriorityLabel(ticket.priority) }}
                </span>
              </div>
            </div>

            <!-- Original Message -->
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
              <p class="text-gray-800 whitespace-pre-wrap">{{ ticket.message }}</p>
            </div>

            <!-- Categories -->
            <div v-if="ticket.categories && ticket.categories.length" class="mt-4">
              <div class="flex items-center space-x-2">
                <BaseIcon name="TagIcon" class="h-4 w-4 text-gray-500" />
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="category in ticket.categories"
                    :key="category.id"
                    class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded"
                  >
                    {{ category.name }}
                  </span>
                </div>
              </div>
            </div>
          </BaseCard>

          <!-- Messages Thread -->
          <BaseCard class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('tickets.conversation') }}
            </h3>

            <div v-if="!ticket.messages || ticket.messages.length === 0" class="text-center py-8">
              <BaseIcon name="ChatBubbleLeftIcon" class="h-12 w-12 mx-auto text-gray-400 mb-2" />
              <p class="text-gray-500">{{ $t('tickets.no_messages') }}</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="message in ticket.messages"
                :key="message.id"
                :class="isCustomerMessage(message) ? 'ml-0 mr-8' : 'ml-8 mr-0'"
              >
                <div
                  :class="isCustomerMessage(message) ? 'bg-blue-50' : 'bg-gray-100'"
                  class="rounded-lg p-4"
                >
                  <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center space-x-2">
                      <div
                        :class="isCustomerMessage(message) ? 'bg-blue-500' : 'bg-gray-500'"
                        class="h-8 w-8 rounded-full flex items-center justify-center text-white font-medium text-sm"
                      >
                        {{ getInitials(message.user?.name) }}
                      </div>
                      <div>
                        <p class="text-sm font-medium text-gray-900">
                          {{ message.user?.name || 'Unknown' }}
                        </p>
                        <p class="text-xs text-gray-500">
                          {{ formatDateTime(message.created_at) }}
                        </p>
                      </div>
                    </div>

                    <BaseDropdown v-if="canEditMessage(message)">
                      <template #activator>
                        <BaseIcon
                          name="EllipsisVerticalIcon"
                          class="h-5 w-5 text-gray-500 cursor-pointer"
                        />
                      </template>

                      <BaseDropdownItem @click="editMessage(message)">
                        <BaseIcon name="PencilIcon" class="h-4 mr-2 text-gray-600" />
                        {{ $t('general.edit') }}
                      </BaseDropdownItem>

                      <BaseDropdownItem @click="deleteMessage(message.id)">
                        <BaseIcon name="TrashIcon" class="h-4 mr-2 text-gray-600" />
                        {{ $t('general.delete') }}
                      </BaseDropdownItem>
                    </BaseDropdown>
                  </div>

                  <p class="text-gray-800 whitespace-pre-wrap">{{ message.message }}</p>

                  <!-- Message Attachments -->
                  <div v-if="message.attachments && message.attachments.length > 0" class="mt-3 flex flex-wrap gap-2">
                    <a
                      v-for="attachment in message.attachments"
                      :key="attachment.id"
                      :href="attachment.url"
                      target="_blank"
                      class="flex items-center space-x-2 px-3 py-2 bg-white rounded border border-gray-200 hover:border-primary-300 text-sm"
                    >
                      <BaseIcon
                        :name="attachment.mime_type && attachment.mime_type.startsWith('image/') ? 'PhotoIcon' : 'DocumentIcon'"
                        class="h-4 w-4 text-gray-500"
                      />
                      <span class="text-gray-700 truncate max-w-[150px]">{{ attachment.name }}</span>
                      <span class="text-gray-400 text-xs">({{ attachment.human_readable_size }})</span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </BaseCard>

          <!-- Reply Form -->
          <BaseCard v-if="!ticket.is_locked" class="p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
              {{ $t('tickets.reply') }}
            </h3>

            <form @submit.prevent="submitReply">
              <BaseInputGroup
                :error="replyError"
              >
                <BaseTextarea
                  v-model="replyContent"
                  :placeholder="$t('tickets.type_your_reply')"
                  rows="6"
                  :invalid="!!replyError"
                />
              </BaseInputGroup>

              <div class="mt-4 flex items-center justify-between">
                <div>
                  <input
                    ref="replyFileInput"
                    type="file"
                    class="hidden"
                    multiple
                    accept="image/*,application/pdf"
                    @change="handleReplyFileSelect"
                  />
                  <BaseButton
                    type="button"
                    variant="primary-outline"
                    @click="$refs.replyFileInput.click()"
                  >
                    <BaseIcon name="PaperClipIcon" class="h-5 w-5 mr-1" />
                    {{ $t('tickets.attach_files') }}
                  </BaseButton>
                </div>

                <BaseButton
                  :loading="isSubmittingReply"
                  :disabled="isSubmittingReply || !replyContent.trim()"
                  variant="primary"
                  type="submit"
                >
                  <BaseIcon v-if="!isSubmittingReply" name="PaperAirplaneIcon" class="h-5 w-5 mr-1" />
                  {{ $t('tickets.send_reply') }}
                </BaseButton>
              </div>

              <!-- Reply Attachments -->
              <div v-if="replyAttachments.length" class="mt-4 space-y-2">
                <div
                  v-for="(file, index) in replyAttachments"
                  :key="index"
                  class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
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
                    @click="removeReplyAttachment(index)"
                  >
                    <BaseIcon name="XMarkIcon" class="h-5 w-5" />
                  </button>
                </div>
              </div>
            </form>
          </BaseCard>

          <div v-else class="text-center py-8">
            <BaseIcon name="LockClosedIcon" class="h-12 w-12 mx-auto text-gray-400 mb-2" />
            <p class="text-gray-500">{{ $t('tickets.ticket_locked') }}</p>
          </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
          <!-- Status Card -->
          <BaseCard class="p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">
              {{ $t('tickets.ticket_status') }}
            </h3>

            <!-- Admin/Owner: Full status dropdown -->
            <BaseInputGroup v-if="isAdminOrOwner" :label="$t('tickets.status')">
              <BaseMultiselect
                v-model="selectedStatus"
                :options="statusOptions"
                @update:modelValue="updateStatus"
              />
            </BaseInputGroup>

            <!-- Regular user: Only show current status and close button -->
            <div v-else>
              <div class="mb-3">
                <span class="text-sm text-gray-600">{{ $t('tickets.status') }}:</span>
                <span
                  :class="getStatusBadgeClass(ticket.status)"
                  class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ getStatusLabel(ticket.status) }}
                </span>
              </div>
              <BaseButton
                v-if="ticket.status !== 'closed'"
                variant="primary-outline"
                size="sm"
                class="w-full"
                @click="closeTicket"
              >
                <BaseIcon name="XCircleIcon" class="h-4 w-4 mr-1" />
                {{ $t('tickets.close_ticket') }}
              </BaseButton>
            </div>

            <!-- Admin/Owner: Priority dropdown -->
            <BaseInputGroup v-if="isAdminOrOwner" :label="$t('tickets.priority')" class="mt-4">
              <BaseMultiselect
                v-model="selectedPriority"
                :options="priorityOptions"
                @update:modelValue="updatePriority"
              />
            </BaseInputGroup>

            <!-- Regular user: Show priority as read-only -->
            <div v-else class="mt-4">
              <span class="text-sm text-gray-600">{{ $t('tickets.priority') }}:</span>
              <span
                :class="getPriorityBadgeClass(ticket.priority)"
                class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ getPriorityLabel(ticket.priority) }}
              </span>
            </div>
          </BaseCard>

          <!-- Ticket Info Card -->
          <BaseCard class="p-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">
              {{ $t('tickets.ticket_info') }}
            </h3>

            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-600">{{ $t('tickets.ticket_id') }}:</span>
                <span class="font-medium text-gray-900 ml-2">#{{ ticket.id }}</span>
              </div>

              <div>
                <span class="text-gray-600">{{ $t('tickets.created_by') }}:</span>
                <span class="font-medium text-gray-900 ml-2">{{ ticket.user?.name }}</span>
              </div>

              <div>
                <span class="text-gray-600">{{ $t('tickets.created_at') }}:</span>
                <span class="font-medium text-gray-900 ml-2">{{ formatDateTime(ticket.created_at) }}</span>
              </div>

              <div v-if="ticket.updated_at !== ticket.created_at">
                <span class="text-gray-600">{{ $t('tickets.last_updated') }}:</span>
                <span class="font-medium text-gray-900 ml-2">{{ formatDateTime(ticket.updated_at) }}</span>
              </div>

              <div>
                <span class="text-gray-600">{{ $t('tickets.replies') }}:</span>
                <span class="font-medium text-gray-900 ml-2">{{ ticket.messages_count || 0 }}</span>
              </div>
            </div>
          </BaseCard>
        </div>
      </div>
    </div>

    <div v-else class="text-center py-12">
      <p class="text-gray-500">{{ $t('tickets.ticket_not_found') }}</p>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTicketStore } from '@/scripts/admin/stores/ticket'
import { useUserStore } from '@/scripts/admin/stores/user'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import moment from 'moment'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const ticketStore = useTicketStore()
const userStore = useUserStore()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const ticket = computed(() => ticketStore.currentTicket)

// Check if current user is platform admin (super admin or support role only)
// Regular company owners should NOT have status/priority edit access
const isAdminOrOwner = computed(() => {
  const user = userStore.currentUser
  if (!user) return false
  return user.role === 'super admin' || user.role === 'support'
})

const replyContent = ref('')
const replyAttachments = ref([])
const replyFileInput = ref(null)
const isSubmittingReply = ref(false)
const replyError = ref('')

const selectedStatus = ref(null)
const selectedPriority = ref(null)

const statusOptions = [
  { label: t('tickets.open'), value: 'open' },
  { label: t('tickets.in_progress'), value: 'in_progress' },
  { label: t('tickets.resolved'), value: 'resolved' },
  { label: t('tickets.closed'), value: 'closed' },
]

const priorityOptions = [
  { label: t('tickets.low'), value: 'low' },
  { label: t('tickets.normal'), value: 'normal' },
  { label: t('tickets.high'), value: 'high' },
  { label: t('tickets.urgent'), value: 'urgent' },
]

const getStatusBadgeClass = (status) => {
  const classes = {
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
  }
  return classes[status] || classes.open
}

const getPriorityBadgeClass = (priority) => {
  const classes = {
    low: 'bg-gray-100 text-gray-800',
    normal: 'bg-blue-100 text-blue-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800',
  }
  return classes[priority] || classes.normal
}

const getStatusLabel = (status) => {
  return t(`tickets.${status}`)
}

const getPriorityLabel = (priority) => {
  return t(`tickets.${priority}`)
}

const formatDate = (date) => {
  return moment(date).format('MMM DD, YYYY')
}

const formatDateTime = (date) => {
  return moment(date).format('MMM DD, YYYY HH:mm')
}

const getInitials = (name) => {
  if (!name) return '?'
  return name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

const isCustomerMessage = (message) => {
  return message.user_id !== ticket.value?.user_id
}

const canEditMessage = (message) => {
  return message.user_id === userStore.currentUser.id
}

const handleReplyFileSelect = (event) => {
  const files = Array.from(event.target.files)
  validateAndAddReplyFiles(files)
}

const validateAndAddReplyFiles = (files) => {
  for (const file of files) {
    if (file.size > 5 * 1024 * 1024) {
      notificationStore.showNotification({
        type: 'error',
        message: t('tickets.file_too_large', { filename: file.name }),
      })
      continue
    }

    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf']
    if (!allowedTypes.includes(file.type)) {
      notificationStore.showNotification({
        type: 'error',
        message: t('tickets.invalid_file_type', { filename: file.name }),
      })
      continue
    }

    if (!replyAttachments.value.find((f) => f.name === file.name && f.size === file.size)) {
      replyAttachments.value.push(file)
    }
  }
}

const removeReplyAttachment = (index) => {
  replyAttachments.value.splice(index, 1)
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
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i]
}

const submitReply = async () => {
  if (!replyContent.value.trim()) {
    replyError.value = t('tickets.reply_required')
    return
  }

  isSubmittingReply.value = true
  replyError.value = ''

  try {
    const data = new FormData()
    data.append('message', replyContent.value)

    replyAttachments.value.forEach((file, index) => {
      data.append(`attachments[${index}]`, file)
    })

    await ticketStore.replyToTicket(ticket.value.id, data)

    replyContent.value = ''
    replyAttachments.value = []
  } catch (error) {
    console.error('Error submitting reply:', error)
  } finally {
    isSubmittingReply.value = false
  }
}

const updateStatus = async () => {
  if (!selectedStatus.value) return

  try {
    await ticketStore.updateTicket(ticket.value.id, {
      status: selectedStatus.value.value,
    })
  } catch (error) {
    console.error('Error updating status:', error)
  }
}

const updatePriority = async () => {
  if (!selectedPriority.value) return

  try {
    await ticketStore.updateTicket(ticket.value.id, {
      priority: selectedPriority.value.value,
    })
  } catch (error) {
    console.error('Error updating priority:', error)
  }
}

const updateTicketStatus = async (status) => {
  try {
    await ticketStore.updateTicket(ticket.value.id, { status })
    selectedStatus.value = statusOptions.find((s) => s.value === status)
  } catch (error) {
    console.error('Error updating ticket status:', error)
  }
}

// Close ticket (for regular users who can only close, not change to other statuses)
const closeTicket = async () => {
  try {
    await ticketStore.updateTicket(ticket.value.id, { status: 'closed' })
    selectedStatus.value = statusOptions.find((s) => s.value === 'closed')
    notificationStore.showNotification({
      type: 'success',
      message: t('tickets.ticket_closed'),
    })
  } catch (error) {
    console.error('Error closing ticket:', error)
  }
}

const deleteCurrentTicket = () => {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('tickets.confirm_delete'),
      yesLabel: t('general.yes'),
      noLabel: t('general.no'),
    })
    .then((result) => {
      if (result) {
        ticketStore.deleteTicket(ticket.value.id).then(() => {
          router.push('/admin/support')
        })
      }
    })
}

const editMessage = (message) => {
  // TODO: Implement edit message functionality
  console.log('Edit message:', message)
}

const deleteMessage = (messageId) => {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('tickets.confirm_delete_message'),
      yesLabel: t('general.yes'),
      noLabel: t('general.no'),
    })
    .then((result) => {
      if (result) {
        ticketStore.deleteMessage(ticket.value.id, messageId)
      }
    })
}

onMounted(async () => {
  const ticketId = route.params.id
  await ticketStore.fetchTicket(ticketId)

  if (ticket.value) {
    selectedStatus.value = statusOptions.find((s) => s.value === ticket.value.status)
    selectedPriority.value = priorityOptions.find((p) => p.value === ticket.value.priority)
  }
})
</script>
// CLAUDE-CHECKPOINT
