<template>
  <BasePage>
    <BasePageHeader :title="$t('tickets.all_tickets')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('tickets.all_tickets')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="ticketTotalCount"
          variant="primary-outline"
          @click="toggleFilter"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon
              v-if="!showFilters"
              name="FunnelIcon"
              :class="slotProps.class"
            />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Statistics Cards -->
    <div v-if="statistics" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="text-2xl font-bold text-gray-900">{{ statistics.total_tickets }}</div>
        <div class="text-sm text-gray-600">{{ $t('tickets.total_tickets') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-4">
        <div class="text-2xl font-bold text-blue-600">{{ statistics.open_tickets }}</div>
        <div class="text-sm text-gray-600">{{ $t('tickets.open') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-yellow-200 p-4">
        <div class="text-2xl font-bold text-yellow-600">{{ statistics.in_progress_tickets }}</div>
        <div class="text-sm text-gray-600">{{ $t('tickets.in_progress') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-red-200 p-4">
        <div class="text-2xl font-bold text-red-600">{{ statistics.urgent_tickets }}</div>
        <div class="text-sm text-gray-600">{{ $t('tickets.urgent') }}</div>
      </div>
    </div>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="$t('tickets.status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          searchable
          :placeholder="$t('general.select_a_status')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('tickets.priority')">
        <BaseMultiselect
          v-model="filters.priority"
          :options="priorityOptions"
          searchable
          :placeholder="$t('tickets.select_priority')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.search')">
        <BaseInput v-model="filters.search" :placeholder="$t('tickets.search_placeholder')">
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('tickets.no_tickets')"
      :description="$t('tickets.no_tickets_admin')"
    >
    </BaseEmptyPlaceholder>

    <div v-show="!showEmptyScreen" class="relative table-container">
      <div
        class="
          relative
          flex
          items-center
          justify-between
          h-10
          mt-5
          list-none
          border-b-2 border-gray-200 border-solid
        "
      >
        <!-- Tabs -->
        <BaseTabGroup class="-mb-5" @change="setStatusFilter">
          <BaseTab :title="$t('general.all')" filter="" />
          <BaseTab :title="$t('tickets.open')" filter="open" />
          <BaseTab :title="$t('tickets.in_progress')" filter="in_progress" />
          <BaseTab :title="$t('tickets.resolved')" filter="resolved" />
          <BaseTab :title="$t('tickets.closed')" filter="closed" />
        </BaseTabGroup>
      </div>

      <!-- Mobile: Card View -->
      <div class="block md:hidden mt-4 space-y-4">
        <div
          v-for="ticket in tickets"
          :key="ticket.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer"
          @click="viewTicket(ticket)"
        >
          <div class="flex items-start justify-between mb-2">
            <h3 class="text-base font-semibold text-gray-900 flex-1">
              {{ ticket.title }}
            </h3>
            <span
              :class="getStatusBadgeClass(ticket.status)"
              class="ml-2 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap"
            >
              {{ getStatusLabel(ticket.status) }}
            </span>
          </div>

          <p class="text-sm text-gray-600 mb-2">
            <span class="font-medium">{{ $t('tickets.company') }}:</span>
            {{ ticket.company?.name || 'N/A' }}
          </p>

          <p class="text-sm text-gray-600 mb-3 line-clamp-2">
            {{ ticket.message }}
          </p>

          <div class="flex items-center justify-between text-xs text-gray-500">
            <div class="flex items-center space-x-3">
              <span
                :class="getPriorityBadgeClass(ticket.priority)"
                class="px-2 py-1 rounded-full font-medium"
              >
                {{ getPriorityLabel(ticket.priority) }}
              </span>
              <span v-if="ticket.messages_count" class="flex items-center">
                <BaseIcon name="ChatBubbleLeftIcon" class="h-4 w-4 mr-1" />
                {{ ticket.messages_count }}
              </span>
            </div>
            <span>{{ formatDate(ticket.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Desktop: Table View -->
      <div class="hidden md:block">
        <BaseTable
          ref="table"
          :data="tickets"
          :columns="ticketColumns"
          class="mt-3"
        >
          <template #cell-title="{ row }">
            <a
              href="#"
              class="font-medium text-primary-500 hover:text-primary-600"
              @click.prevent="viewTicket(row.data)"
            >
              {{ row.data.title }}
            </a>
          </template>

          <template #cell-company="{ row }">
            <span class="text-sm text-gray-700">
              {{ row.data.company?.name || 'N/A' }}
            </span>
          </template>

          <template #cell-user="{ row }">
            <span class="text-sm text-gray-700">
              {{ row.data.user?.name || 'Unknown' }}
            </span>
          </template>

          <template #cell-status="{ row }">
            <span
              :class="getStatusBadgeClass(row.data.status)"
              class="px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ getStatusLabel(row.data.status) }}
            </span>
          </template>

          <template #cell-priority="{ row }">
            <span
              :class="getPriorityBadgeClass(row.data.priority)"
              class="px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ getPriorityLabel(row.data.priority) }}
            </span>
          </template>

          <template #cell-messages_count="{ row }">
            <div class="flex items-center text-gray-600">
              <BaseIcon name="ChatBubbleLeftIcon" class="h-4 w-4 mr-1" />
              {{ row.data.messages_count || 0 }}
            </div>
          </template>

          <template #cell-created_at="{ row }">
            <span class="text-sm text-gray-600">
              {{ formatDate(row.data.created_at) }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <BaseDropdown>
              <template #activator>
                <BaseIcon
                  name="EllipsisHorizontalIcon"
                  class="h-5 text-gray-500 cursor-pointer"
                />
              </template>

              <BaseDropdownItem @click="viewTicket(row.data)">
                <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('general.view') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="changeTicketStatus(row.data, 'in_progress')">
                <BaseIcon name="PlayCircleIcon" class="h-5 mr-3 text-yellow-600" />
                {{ $t('tickets.mark_in_progress') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="changeTicketStatus(row.data, 'resolved')">
                <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-green-600" />
                {{ $t('tickets.mark_resolved') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="changeTicketStatus(row.data, 'closed')">
                <BaseIcon name="XCircleIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('tickets.close_ticket') }}
              </BaseDropdownItem>
            </BaseDropdown>
          </template>
        </BaseTable>
      </div>

      <BasePagination
        v-if="ticketTotalCount > 0"
        v-model="currentPage"
        :total-pages="totalPages"
        :total-count="ticketTotalCount"
        :per-page="25"
        class="mt-6"
      />
    </div>

    <!-- View Ticket Modal -->
    <BaseModal
      :show="showViewModal"
      @close="closeViewModal"
    >
      <template #header>
        <div class="flex items-center justify-between w-full">
          <h3 class="text-lg font-semibold">
            #{{ selectedTicket?.id }} - {{ selectedTicket?.title }}
          </h3>
        </div>
      </template>

      <div v-if="selectedTicket" class="space-y-4">
        <!-- Ticket Info -->
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-600">{{ $t('tickets.company') }}:</span>
            <span class="font-medium ml-2">{{ selectedTicket.company?.name || 'N/A' }}</span>
          </div>
          <div>
            <span class="text-gray-600">{{ $t('tickets.created_by') }}:</span>
            <span class="font-medium ml-2">{{ selectedTicket.user?.name }}</span>
          </div>
          <div>
            <span class="text-gray-600">{{ $t('tickets.status') }}:</span>
            <span
              :class="getStatusBadgeClass(selectedTicket.status)"
              class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ getStatusLabel(selectedTicket.status) }}
            </span>
          </div>
          <div>
            <span class="text-gray-600">{{ $t('tickets.priority') }}:</span>
            <span
              :class="getPriorityBadgeClass(selectedTicket.priority)"
              class="ml-2 px-2 py-1 text-xs font-medium rounded-full"
            >
              {{ getPriorityLabel(selectedTicket.priority) }}
            </span>
          </div>
        </div>

        <!-- Original Message -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-gray-800 whitespace-pre-wrap">{{ selectedTicket.message }}</p>
        </div>

        <!-- Messages Thread -->
        <div v-if="selectedTicket.messages && selectedTicket.messages.length > 0" class="space-y-3">
          <h4 class="font-medium text-gray-700">{{ $t('tickets.conversation') }}</h4>
          <div
            v-for="message in selectedTicket.messages"
            :key="message.id"
            class="p-3 rounded-lg"
            :class="message.is_internal ? 'bg-yellow-50 border border-yellow-200' : 'bg-blue-50'"
          >
            <div class="flex items-center justify-between mb-2">
              <span class="font-medium text-sm">{{ message.user?.name || 'Unknown' }}</span>
              <span class="text-xs text-gray-500">{{ formatDateTime(message.created_at) }}</span>
            </div>
            <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ message.message }}</p>

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
                  :name="attachment.mime_type.startsWith('image/') ? 'PhotoIcon' : 'DocumentIcon'"
                  class="h-4 w-4 text-gray-500"
                />
                <span class="text-gray-700 truncate max-w-[150px]">{{ attachment.name }}</span>
                <span class="text-gray-400 text-xs">({{ attachment.human_readable_size }})</span>
              </a>
            </div>

            <span v-if="message.is_internal" class="text-xs text-yellow-600 mt-1 inline-block">
              {{ $t('tickets.internal_note') }}
            </span>
          </div>
        </div>

        <!-- Reply Form -->
        <div v-if="!selectedTicket.is_locked" class="border-t pt-4">
          <div class="flex space-x-2 mb-3">
            <BaseButton
              :variant="replyMode === 'public' ? 'primary' : 'primary-outline'"
              size="sm"
              @click="replyMode = 'public'"
            >
              {{ $t('tickets.public_reply') }}
            </BaseButton>
            <BaseButton
              :variant="replyMode === 'internal' ? 'primary' : 'primary-outline'"
              size="sm"
              @click="replyMode = 'internal'"
            >
              {{ $t('tickets.internal_note') }}
            </BaseButton>
          </div>

          <BaseTextarea
            v-model="replyContent"
            :placeholder="replyMode === 'internal' ? $t('tickets.type_internal_note') : $t('tickets.type_your_reply')"
            rows="4"
          />

          <!-- Attachment Input -->
          <div class="mt-3">
            <input
              ref="attachmentInput"
              type="file"
              multiple
              accept="image/*,application/pdf"
              class="hidden"
              @change="handleAttachmentSelect"
            />
            <BaseButton
              type="button"
              variant="primary-outline"
              size="sm"
              @click="$refs.attachmentInput.click()"
            >
              <BaseIcon name="PaperClipIcon" class="h-4 w-4 mr-1" />
              {{ $t('tickets.attach_files') }}
            </BaseButton>

            <!-- Selected Attachments Preview -->
            <div v-if="replyAttachments.length > 0" class="mt-2 flex flex-wrap gap-2">
              <div
                v-for="(file, index) in replyAttachments"
                :key="index"
                class="flex items-center space-x-2 px-3 py-2 bg-gray-100 rounded text-sm"
              >
                <BaseIcon
                  :name="file.type.startsWith('image/') ? 'PhotoIcon' : 'DocumentIcon'"
                  class="h-4 w-4 text-gray-500"
                />
                <span class="text-gray-700 truncate max-w-[150px]">{{ file.name }}</span>
                <button
                  type="button"
                  class="text-red-500 hover:text-red-700"
                  @click="removeAttachment(index)"
                >
                  <BaseIcon name="XMarkIcon" class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <div class="mt-3 flex justify-end space-x-3">
            <BaseButton
              variant="primary-outline"
              @click="closeViewModal"
            >
              {{ $t('general.close') }}
            </BaseButton>
            <BaseButton
              :loading="isSubmittingReply"
              :disabled="!replyContent.trim()"
              variant="primary"
              @click="submitReply"
            >
              {{ replyMode === 'internal' ? $t('tickets.add_note') : $t('tickets.send_reply') }}
            </BaseButton>
          </div>
        </div>

        <div v-else class="text-center py-4 text-gray-500">
          <BaseIcon name="LockClosedIcon" class="h-6 w-6 mx-auto mb-2" />
          {{ $t('tickets.ticket_locked') }}
        </div>
      </div>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import axios from 'axios'
import moment from 'moment'

const { t } = useI18n()
const notificationStore = useNotificationStore()

const tickets = ref([])
const ticketTotalCount = ref(0)
const statistics = ref(null)
const showFilters = ref(false)
const currentPage = ref(1)
const isFetching = ref(false)

const showViewModal = ref(false)
const selectedTicket = ref(null)
const replyContent = ref('')
const replyMode = ref('public')
const replyAttachments = ref([])
const attachmentInput = ref(null)
const isSubmittingReply = ref(false)

const filters = ref({
  status: '',
  priority: '',
  search: '',
})

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

const ticketColumns = [
  {
    key: 'title',
    label: t('tickets.title'),
    thClass: 'min-w-[200px]',
  },
  {
    key: 'company',
    label: t('tickets.company'),
    thClass: 'w-40',
  },
  {
    key: 'user',
    label: t('tickets.created_by'),
    thClass: 'w-32',
  },
  {
    key: 'status',
    label: t('tickets.status'),
    thClass: 'w-32',
  },
  {
    key: 'priority',
    label: t('tickets.priority'),
    thClass: 'w-24',
  },
  {
    key: 'messages_count',
    label: t('tickets.replies'),
    thClass: 'w-20 text-center',
  },
  {
    key: 'created_at',
    label: t('general.created_at'),
    thClass: 'w-32',
  },
  {
    key: 'actions',
    label: '',
    thClass: 'w-16',
  },
]

const showEmptyScreen = computed(() => {
  return !ticketTotalCount.value && !isFetching.value
})

const totalPages = computed(() => {
  return Math.ceil(ticketTotalCount.value / 25)
})

const toggleFilter = () => {
  showFilters.value = !showFilters.value
}

const clearFilter = () => {
  filters.value = {
    status: '',
    priority: '',
    search: '',
  }
  refreshTable()
}

const setStatusFilter = (filter) => {
  filters.value.status = filter
  refreshTable()
}

const refreshTable = () => {
  currentPage.value = 1
  loadTickets()
}

const loadTickets = async () => {
  isFetching.value = true

  try {
    const params = {
      page: currentPage.value,
      limit: 25,
      status: filters.value.status || undefined,
      priority: filters.value.priority?.value || undefined,
      search: filters.value.search || undefined,
    }

    const response = await axios.get('/support/admin/tickets', { params })
    tickets.value = response.data.data
    ticketTotalCount.value = response.data.meta?.ticket_total_count || 0
  } catch (error) {
    console.error('Error loading tickets:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('tickets.error_loading'),
    })
  } finally {
    isFetching.value = false
  }
}

const loadStatistics = async () => {
  try {
    const response = await axios.get('/support/admin/statistics')
    statistics.value = response.data
  } catch (error) {
    console.error('Error loading statistics:', error)
  }
}

const viewTicket = async (ticket) => {
  // Safety check - ensure ticket and ticket.id exist
  if (!ticket || !ticket.id) {
    console.error('Invalid ticket object:', ticket)
    notificationStore.showNotification({
      type: 'error',
      message: t('tickets.error_loading'),
    })
    return
  }

  try {
    // Load full ticket with messages
    const response = await axios.get(`/support/tickets/${ticket.id}`)
    selectedTicket.value = response.data.data
    showViewModal.value = true
    replyContent.value = ''
    replyMode.value = 'public'
  } catch (error) {
    console.error('Error loading ticket:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('tickets.error_loading'),
    })
  }
}

const closeViewModal = () => {
  showViewModal.value = false
  selectedTicket.value = null
  replyContent.value = ''
  replyAttachments.value = []
}

const handleAttachmentSelect = (event) => {
  const files = Array.from(event.target.files)
  for (const file of files) {
    // Check file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
      notificationStore.showNotification({
        type: 'error',
        message: t('tickets.file_too_large', { filename: file.name }),
      })
      continue
    }
    replyAttachments.value.push(file)
  }
  // Reset input
  if (attachmentInput.value) {
    attachmentInput.value.value = ''
  }
}

const removeAttachment = (index) => {
  replyAttachments.value.splice(index, 1)
}

const submitReply = async () => {
  if (!replyContent.value.trim() || !selectedTicket.value) return

  isSubmittingReply.value = true

  try {
    if (replyMode.value === 'internal') {
      // Add internal note (no attachments for internal notes)
      await axios.post(`/support/admin/tickets/${selectedTicket.value.id}/internal-notes`, {
        message: replyContent.value,
      })
    } else {
      // Add public reply with attachments
      const formData = new FormData()
      formData.append('message', replyContent.value)

      // Add attachments
      replyAttachments.value.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file)
      })

      await axios.post(`/support/tickets/${selectedTicket.value.id}/messages`, formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    }

    notificationStore.showNotification({
      type: 'success',
      message: replyMode.value === 'internal'
        ? t('tickets.internal_note_added')
        : t('tickets.reply_sent'),
    })

    // Clear attachments
    replyAttachments.value = []

    // Reload ticket to show new message
    await viewTicket(selectedTicket.value)
  } catch (error) {
    console.error('Error submitting reply:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('tickets.error_sending_reply'),
    })
  } finally {
    isSubmittingReply.value = false
  }
}

const changeTicketStatus = async (ticket, status) => {
  try {
    await axios.post(`/support/admin/tickets/${ticket.id}/change-status`, { status })

    notificationStore.showNotification({
      type: 'success',
      message: t('tickets.status_updated'),
    })

    loadTickets()
    loadStatistics()
  } catch (error) {
    console.error('Error changing status:', error)
    notificationStore.showNotification({
      type: 'error',
      message: t('tickets.error_updating_status'),
    })
  }
}

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
  if (!status) return t('tickets.open')
  return t(`tickets.${status}`)
}

const getPriorityLabel = (priority) => {
  if (!priority) return t('tickets.normal')
  return t(`tickets.${priority}`)
}

const formatDate = (date) => {
  return moment(date).format('MMM DD, YYYY')
}

const formatDateTime = (date) => {
  return moment(date).format('MMM DD, YYYY HH:mm')
}

watch(
  () => filters.value.search,
  () => {
    refreshTable()
  }
)

watch(currentPage, () => {
  loadTickets()
})

onMounted(() => {
  loadTickets()
  loadStatistics()
})
</script>
// CLAUDE-CHECKPOINT
