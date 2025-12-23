<template>
  <BasePage>
    <BasePageHeader :title="$t('tickets.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('tickets.ticket', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="ticketStore.ticketTotalCount"
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

        <router-link to="support/create">
          <BaseButton variant="primary" class="ml-4">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('tickets.new_ticket') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

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
      :description="$t('tickets.list_of_tickets')"
    >
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/support/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('tickets.create_new_ticket') }}
        </BaseButton>
      </template>
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

        <BaseDropdown
          v-if="ticketStore.selectedTickets.length"
          class="absolute float-right"
        >
          <template #activator>
            <span
              class="
                flex
                text-sm
                font-medium
                cursor-pointer
                select-none
                text-primary-400
              "
            >
              {{ $t('general.actions') }}
              <BaseIcon name="ChevronDownIcon" class="h-5" />
            </span>
          </template>

          <BaseDropdownItem @click="removeMultipleTickets">
            <BaseIcon name="TrashIcon" class="h-5 mr-3 text-gray-600" />
            {{ $t('general.delete') }}
          </BaseDropdownItem>
        </BaseDropdown>
      </div>

      <!-- Mobile: Card View -->
      <div class="block md:hidden mt-4 space-y-4">
        <div
          v-for="ticket in ticketStore.tickets"
          :key="ticket.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer"
          @click="$router.push(`/admin/support/${ticket.id}`)"
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
          :data="ticketStore.tickets"
          :columns="ticketColumns"
          class="mt-3"
        >
          <template #cell-select="{ row }">
            <BaseCheckbox
              :id="row.data.id"
              v-model="selectField"
              :value="row.data.id"
            />
          </template>

          <template #cell-title="{ row }">
            <router-link
              :to="`/admin/support/${row.data.id}`"
              class="font-medium text-primary-500 hover:text-primary-600"
            >
              {{ row.data.title }}
            </router-link>
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

              <BaseDropdownItem @click="$router.push(`/admin/support/${row.data.id}`)">
                <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('general.view') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="removeTicket(row.data.id)">
                <BaseIcon name="TrashIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('general.delete') }}
              </BaseDropdownItem>
            </BaseDropdown>
          </template>
        </BaseTable>
      </div>

      <BasePagination
        v-if="ticketStore.ticketTotalCount > 0"
        v-model="currentPage"
        :total-pages="totalPages"
        :total-count="ticketStore.ticketTotalCount"
        :per-page="25"
        class="mt-6"
      />
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useTicketStore } from '@/scripts/admin/stores/ticket'
import { useDialogStore } from '@/scripts/stores/dialog'
import moment from 'moment'

const { t } = useI18n()
const router = useRouter()
const ticketStore = useTicketStore()
const dialogStore = useDialogStore()

const showFilters = ref(false)
const currentPage = ref(1)
const selectField = ref([])

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
    key: 'select',
    thClass: 'w-12',
  },
  {
    key: 'title',
    label: t('tickets.title'),
    thClass: 'min-w-[300px]',
  },
  {
    key: 'status',
    label: t('tickets.status'),
    thClass: 'w-32',
  },
  {
    key: 'priority',
    label: t('tickets.priority'),
    thClass: 'w-32',
  },
  {
    key: 'messages_count',
    label: t('tickets.replies'),
    thClass: 'w-24 text-center',
  },
  {
    key: 'created_at',
    label: t('general.created_at'),
    thClass: 'w-40',
  },
  {
    key: 'actions',
    label: '',
    thClass: 'w-20',
  },
]

const showEmptyScreen = computed(() => {
  return !ticketStore.ticketTotalCount && !ticketStore.isFetchingTickets
})

const totalPages = computed(() => {
  return Math.ceil(ticketStore.ticketTotalCount / 25)
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
  const params = {
    page: currentPage.value,
    limit: 25,
    status: filters.value.status || undefined,
    priority: filters.value.priority?.value || undefined,
    search: filters.value.search || undefined,
  }

  await ticketStore.fetchTickets(params)
}

const removeTicket = (id) => {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('tickets.confirm_delete'),
      yesLabel: t('general.yes'),
      noLabel: t('general.no'),
    })
    .then((result) => {
      if (result) {
        ticketStore.deleteTicket(id).then(() => {
          refreshTable()
        })
      }
    })
}

const removeMultipleTickets = () => {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('tickets.confirm_delete_multiple'),
      yesLabel: t('general.yes'),
      noLabel: t('general.no'),
    })
    .then((result) => {
      if (result) {
        ticketStore.deleteMultipleTickets(ticketStore.selectedTickets).then(() => {
          refreshTable()
        })
      }
    })
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
  if (!status) return t('tickets.open') // Default to 'open' if no status
  return t(`tickets.${status}`)
}

const getPriorityLabel = (priority) => {
  if (!priority) return t('tickets.normal') // Default to 'normal' if no priority
  return t(`tickets.${priority}`)
}

const formatDate = (date) => {
  return moment(date).format('MMM DD, YYYY')
}

watch(selectField, (val) => {
  ticketStore.selectTicket(val)
})

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
})
</script>
// CLAUDE-CHECKPOINT
