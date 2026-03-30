<template>
  <BasePage>
    <!-- Page Header Section -->
    <BasePageHeader :title="$t('users.title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('users.title', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex flex-wrap items-center justify-end gap-2 sm:gap-3">
          <!-- Usage indicator (Users tab only) -->
          <div
            v-if="activeTab === 'users' && usageStats && !usageStats.is_unlimited"
            class="flex items-center gap-2 text-sm order-first w-full sm:w-auto sm:order-none"
          >
            <span :class="usageStats.has_reached_limit ? 'text-red-600 font-medium' : 'text-gray-500'">
              {{ usageStats.current_count }}/{{ usageStats.limit }} {{ $t('users.users_used') }}
            </span>
            <div class="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all"
                :class="usageStats.has_reached_limit ? 'bg-red-500' : 'bg-primary-500'"
                :style="{ width: Math.min(usageStats.usage_percentage, 100) + '%' }"
              />
            </div>
            <router-link
              v-if="usageStats.has_reached_limit"
              to="/admin/settings/subscription"
              class="text-xs text-primary-600 hover:text-primary-800 underline whitespace-nowrap"
            >
              {{ $t('users.upgrade_plan') }}
            </router-link>
          </div>

          <!-- Export CSV -->
          <BaseButton
            v-show="activeTab === 'users' && usersStore.totalUsers"
            variant="primary-outline"
            @click="usersStore.exportCsv()"
            class="hidden sm:inline-flex"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
            </template>
            <span class="hidden md:inline">{{ $t('general.export_csv') }}</span>
          </BaseButton>

          <BaseButton
            v-show="activeTab === 'users' && usersStore.totalUsers"
            variant="primary-outline"
            @click="toggleFilter"
          >
            <template #right="slotProps">
              <BaseIcon
                v-if="!showFilters"
                name="FunnelIcon"
                :class="slotProps.class"
              />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
            <span class="hidden sm:inline">{{ $t('general.filter') }}</span>
          </BaseButton>

          <!-- Activity Log filters toggle -->
          <BaseButton
            v-show="activeTab === 'activity'"
            variant="primary-outline"
            @click="toggleActivityFilter"
          >
            <template #right="slotProps">
              <BaseIcon
                v-if="!showActivityFilters"
                name="FunnelIcon"
                :class="slotProps.class"
              />
              <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
            </template>
            <span class="hidden sm:inline">{{ $t('general.filter') }}</span>
          </BaseButton>

          <BaseButton
            v-if="activeTab === 'users' && userStore.currentUser.is_owner"
            :disabled="usageStats && usageStats.has_reached_limit && !usageStats.is_unlimited"
            @click="$router.push('users/create')"
          >
            <template #left="slotProps">
              <BaseIcon
                name="PlusIcon"
                :class="slotProps.class"
                aria-hidden="true"
              />
            </template>
            <span class="hidden sm:inline">{{ $t('users.add_user') }}</span>
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Tabs -->
    <div class="mb-6 mt-4">
      <nav class="flex space-x-4" aria-label="Tabs">
        <button
          @click="activeTab = 'users'"
          :class="[
            activeTab === 'users'
              ? 'bg-primary-100 text-primary-700'
              : 'text-gray-500 hover:text-gray-700',
            'px-3 py-2 font-medium text-sm rounded-md'
          ]"
        >
          {{ $t('users.title') }}
        </button>
        <button
          @click="activeTab = 'activity'"
          :class="[
            activeTab === 'activity'
              ? 'bg-primary-100 text-primary-700'
              : 'text-gray-500 hover:text-gray-700',
            'px-3 py-2 font-medium text-sm rounded-md'
          ]"
        >
          {{ $t('activity_log.title') }}
        </button>
      </nav>
    </div>

    <!-- ═══════════ USERS TAB ═══════════ -->
    <template v-if="activeTab === 'users'">
      <BaseFilterWrapper :show="showFilters" class="mt-3" @clear="clearFilter">
        <BaseInputGroup :label="$t('users.name')" class="w-full sm:flex-1 mt-2 sm:mr-4">
          <BaseInput
            v-model="filters.name"
            type="text"
            name="name"
            autocomplete="off"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('users.email')" class="w-full sm:flex-1 mt-2 sm:mr-4">
          <BaseInput
            v-model="filters.email"
            type="text"
            name="email"
            autocomplete="off"
          />
        </BaseInputGroup>

        <BaseInputGroup class="w-full sm:flex-1 mt-2 sm:mr-4" :label="$t('users.phone')">
          <BaseInput
            v-model="filters.phone"
            type="text"
            name="phone"
            autocomplete="off"
          />
        </BaseInputGroup>

        <BaseInputGroup class="w-full sm:flex-1 mt-2 sm:mr-4" :label="$t('users.role')">
          <BaseMultiselect
            v-model="filters.role"
            :options="roleOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('users.select_role')"
          />
        </BaseInputGroup>

        <BaseInputGroup class="w-full sm:flex-1 mt-2" :label="$t('users.status')">
          <BaseMultiselect
            v-model="filters.is_active"
            :options="statusOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('general.all')"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <BaseEmptyPlaceholder
        v-show="showEmptyScreen"
        :title="$t('users.no_users')"
        :description="$t('users.list_of_users')"
      >
        <AstronautIcon class="mt-5 mb-4" />

        <template #actions>
          <BaseButton
            v-if="userStore.currentUser.is_owner"
            variant="primary-outline"
            @click="$router.push('/admin/users/create')"
          >
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('users.add_user') }}
          </BaseButton>
        </template>
      </BaseEmptyPlaceholder>

      <div v-show="!showEmptyScreen" class="relative table-container">
        <!-- Bulk action bar -->
        <transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 -translate-y-2"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition ease-in duration-150"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-2"
        >
          <div
            v-if="usersStore.selectedUsers.length"
            class="flex items-center justify-between px-4 py-2 mb-2 bg-primary-50 border border-primary-200 rounded-lg"
          >
            <span class="text-sm text-primary-700">
              {{ usersStore.selectedUsers.length }} {{ $t('users.selected') }}
            </span>
            <button
              @click="removeMultipleUsers"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50 transition-colors"
            >
              <BaseIcon name="TrashIcon" class="h-4 w-4" />
              {{ $t('general.delete') }}
            </button>
          </div>
        </transition>

        <div class="overflow-x-auto -mx-4 sm:mx-0">
        <BaseTable
          ref="table"
          :data="fetchData"
          :columns="userTableColumns"
          class="mt-3"
        >
          <template #header>
            <div class="absolute z-10 items-center left-6 top-2.5 select-none">
              <BaseCheckbox
                v-model="selectAllFieldStatus"
                variant="primary"
                @change="usersStore.selectAllUsers"
              />
            </div>
          </template>
          <template #cell-status="{ row }">
            <div class="custom-control custom-checkbox">
              <BaseCheckbox
                :id="row.data.id"
                v-model="selectField"
                :value="row.data.id"
                variant="primary"
              />
            </div>
          </template>

          <template #cell-name="{ row }">
            <div class="flex items-center">
              <router-link
                :to="{ path: `users/${row.data.id}/edit` }"
                class="font-medium text-primary-500"
              >
                {{ row.data.name }}
              </router-link>
              <span
                v-if="row.data.is_active === false"
                class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700"
              >
                {{ $t('users.inactive') }}
              </span>
            </div>
          </template>

          <template #cell-phone="{ row }">
            <span>{{ row.data.phone ? row.data.phone : '-' }} </span>
          </template>

          <template #cell-role="{ row }">
            <span
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
              :class="{
                'bg-purple-100 text-purple-800': row.data.role === 'super admin',
                'bg-blue-100 text-blue-800': row.data.role === 'admin',
                'bg-gray-100 text-gray-800': row.data.role === 'user',
                'bg-green-100 text-green-800': row.data.role === 'partner',
                'bg-yellow-100 text-yellow-800': row.data.role === 'accountant',
              }"
            >
              {{ row.data.role || '-' }}
            </span>
          </template>

          <template #cell-company_names="{ row }">
            <span
              class="text-sm text-gray-600 block max-w-[160px] truncate"
              :title="row.data.company_names"
            >
              {{ row.data.company_names || '-' }}
            </span>
          </template>

          <template #cell-last_login_at="{ row }">
            <span class="text-sm text-gray-500">{{ row.data.formatted_last_login || $t('users.never') }}</span>
          </template>

          <template #cell-created_at="{ row }">
            <span>{{ row.data.formatted_created_at }}</span>
          </template>

          <template v-if="userStore.currentUser.is_owner" #cell-actions="{ row }">
            <UserDropdown
              :row="row.data"
              :table="table"
              :load-data="refreshTable"
            />
          </template>
        </BaseTable>
        </div>
      </div>
    </template>

    <!-- ═══════════ ACTIVITY LOG TAB ═══════════ -->
    <template v-if="activeTab === 'activity'">
      <BaseFilterWrapper :show="showActivityFilters" class="mt-3" @clear="clearActivityFilter">
        <BaseInputGroup :label="$t('activity_log.user')" class="w-full sm:flex-1 mt-2 sm:mr-4">
          <BaseMultiselect
            v-model="activityFilters.user_id"
            :options="usersStore.users"
            label="name"
            value-prop="id"
            track-by="name"
            searchable
            :can-deselect="true"
            :placeholder="$t('activity_log.all_users')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('activity_log.event')" class="w-full sm:flex-1 mt-2 sm:mr-4">
          <BaseMultiselect
            v-model="activityFilters.event"
            :options="eventOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('activity_log.all_events')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('activity_log.entity')" class="w-full sm:flex-1 mt-2 sm:mr-4">
          <BaseMultiselect
            v-model="activityFilters.auditable_type"
            :options="entityTypeOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('activity_log.all_entities')"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <BaseEmptyPlaceholder
        v-show="showActivityEmpty"
        :title="$t('activity_log.no_activity')"
        :description="$t('activity_log.no_activity_description')"
      >
        <AstronautIcon class="mt-5 mb-4" />
      </BaseEmptyPlaceholder>

      <div v-show="!showActivityEmpty" class="relative table-container">
        <div class="overflow-x-auto -mx-4 sm:mx-0">
        <BaseTable
          ref="activityTable"
          :data="fetchActivityData"
          :columns="activityColumns"
          class="mt-3"
        >
          <template #cell-user_name="{ row }">
            <div class="flex items-center">
              <div class="h-7 w-7 rounded-full bg-primary-100 flex items-center justify-center mr-2">
                <span class="text-xs font-medium text-primary-700">
                  {{ getInitials(row.data.user_name) }}
                </span>
              </div>
              <span class="text-sm font-medium text-gray-900">{{ row.data.user_name }}</span>
            </div>
          </template>

          <template #cell-event="{ row }">
            <span
              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
              :class="eventBadgeClass(row.data.event)"
            >
              {{ $t(`activity_log.${row.data.event}`) }}
            </span>
          </template>

          <template #cell-entity="{ row }">
            <div>
              <span class="text-sm text-gray-600">{{ formatEntityType(row.data.auditable_type) }}</span>
              <span v-if="row.data.auditable" class="text-sm font-medium ml-1">
                {{ row.data.auditable.name }}
              </span>
            </div>
          </template>

          <template #cell-changes="{ row }">
            <button
              v-if="row.data.changed_fields && row.data.changed_fields.length"
              class="text-sm text-primary-500 hover:text-primary-700"
              @click="showChangesModal(row.data)"
            >
              {{ row.data.changed_fields.length }} {{ $t('activity_log.fields_changed') }}
            </button>
            <span v-else class="text-sm text-gray-400">-</span>
          </template>

          <template #cell-created_at="{ row }">
            <span class="text-sm text-gray-500">{{ formatDate(row.data.created_at) }}</span>
          </template>
        </BaseTable>
        </div>
      </div>

      <!-- Changes Detail Modal -->
      <BaseModal :show="showChanges" @close="showChanges = false">
        <template #header>
          <div class="flex justify-between w-full">
            {{ $t('activity_log.changes_detail') }}
            <BaseIcon
              name="XMarkIcon"
              class="w-6 h-6 text-gray-500 cursor-pointer"
              @click="showChanges = false"
            />
          </div>
        </template>
        <div class="px-6 py-4">
          <p class="text-sm text-gray-500 mb-4">
            {{ selectedLog?.description }}
          </p>
          <div v-if="selectedLog?.changed_fields?.length" class="space-y-3">
            <div
              v-for="field in selectedLog.changed_fields"
              :key="field"
              class="border rounded-md p-3"
            >
              <p class="text-sm font-medium text-gray-700 mb-1">{{ field }}</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                  <span class="text-gray-400">{{ $t('activity_log.old_value') }}:</span>
                  <p class="text-red-600 mt-0.5">{{ selectedLog.old_values?.[field] ?? '-' }}</p>
                </div>
                <div>
                  <span class="text-gray-400">{{ $t('activity_log.new_value') }}:</span>
                  <p class="text-green-600 mt-0.5">{{ selectedLog.new_values?.[field] ?? '-' }}</p>
                </div>
              </div>
            </div>
          </div>
          <p v-else class="text-sm text-gray-400">{{ $t('activity_log.no_changes') }}</p>
        </div>
      </BaseModal>
    </template>
  </BasePage>
</template>

<script setup>
import { computed, onUnmounted, ref, reactive, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useUsersStore } from '@/scripts/admin/stores/users'
import { useActivityLogStore } from '@/scripts/admin/stores/activity-log'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useUserStore } from '@/scripts/admin/stores/user'
import AstronautIcon from '@/scripts/components/icons/empty/AstronautIcon.vue'
import UserDropdown from '@/scripts/admin/components/dropdowns/UserIndexDropdown.vue'
import abilities from '@/scripts/admin/stub/abilities'

const notificationStore = useNotificationStore()
const dialogStore = useDialogStore()
const usersStore = useUsersStore()
const userStore = useUserStore()
const activityLogStore = useActivityLogStore()

const router = useRouter()

const activeTab = ref('users')
let showFilters = ref(false)
let showActivityFilters = ref(false)
let isFetchingInitialData = ref(true)
let isFetchingActivityData = ref(true)
let id = ref(null)
let sortedBy = ref('created_at')
let isLoading = ref(false)
const { t } = useI18n()
let table = ref(null)
let activityTable = ref(null)
let showChanges = ref(false)
let selectedLog = ref(null)
let showActivityEmpty = ref(false)

let filters = reactive({
  name: '',
  email: '',
  phone: '',
  role: null,
  is_active: null,
})

let activityFilters = reactive({
  user_id: null,
  event: null,
  auditable_type: null,
})

const eventOptions = computed(() => [
  { value: 'created', label: t('activity_log.created') },
  { value: 'updated', label: t('activity_log.updated') },
  { value: 'deleted', label: t('activity_log.deleted') },
])

const roleOptions = computed(() => [
  { value: 'super admin', label: t('users.roles.super_admin') },
  { value: 'admin', label: t('users.roles.admin') },
  { value: 'user', label: t('users.roles.user') },
  { value: 'partner', label: t('users.roles.partner') },
  { value: 'accountant', label: t('users.roles.accountant') },
])

const statusOptions = computed(() => [
  { value: '1', label: t('users.active') },
  { value: '0', label: t('users.inactive') },
])

const entityTypeOptions = computed(() => [
  { value: 'App\\Models\\Invoice', label: t('activity_log.entity_types.invoice') },
  { value: 'App\\Models\\Bill', label: t('activity_log.entity_types.bill') },
  { value: 'App\\Models\\Payment', label: t('activity_log.entity_types.payment') },
  { value: 'App\\Models\\Expense', label: t('activity_log.entity_types.expense') },
  { value: 'App\\Models\\Estimate', label: t('activity_log.entity_types.estimate') },
  { value: 'App\\Models\\Customer', label: t('activity_log.entity_types.customer') },
  { value: 'App\\Models\\Item', label: t('activity_log.entity_types.item') },
  { value: 'App\\Models\\User', label: t('activity_log.entity_types.user') },
  { value: 'App\\Models\\Supplier', label: t('activity_log.entity_types.supplier') },
  { value: 'App\\Models\\Company', label: t('activity_log.entity_types.company') },
])

const userTableColumns = computed(() => {
  return [
    {
      key: 'status',
      thClass: 'extra hidden sm:table-cell',
      tdClass: 'font-medium text-gray-900 hidden sm:table-cell',
      sortable: false,
    },
    {
      key: 'name',
      label: t('users.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'email',
      label: t('users.email'),
      thClass: 'hidden sm:table-cell',
      tdClass: 'hidden sm:table-cell',
    },
    {
      key: 'phone',
      label: t('users.phone'),
      thClass: 'hidden xl:table-cell',
      tdClass: 'hidden xl:table-cell',
    },
    {
      key: 'role',
      label: t('users.role'),
      sortable: false,
    },
    {
      key: 'company_names',
      label: t('users.companies'),
      sortable: false,
      thClass: 'hidden lg:table-cell',
      tdClass: 'hidden lg:table-cell',
    },
    {
      key: 'last_login_at',
      label: t('users.last_login'),
      thClass: 'hidden xl:table-cell',
      tdClass: 'hidden xl:table-cell',
    },
    {
      key: 'created_at',
      label: t('users.added_on'),
      thClass: 'hidden md:table-cell',
      tdClass: 'hidden md:table-cell',
    },
    {
      key: 'actions',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

const activityColumns = computed(() => [
  {
    key: 'user_name',
    label: t('activity_log.user'),
    thClass: 'extra',
  },
  {
    key: 'event',
    label: t('activity_log.event'),
  },
  {
    key: 'entity',
    label: t('activity_log.entity'),
    sortable: false,
    thClass: 'hidden sm:table-cell',
    tdClass: 'hidden sm:table-cell',
  },
  {
    key: 'changes',
    label: t('activity_log.changes'),
    sortable: false,
    thClass: 'hidden md:table-cell',
    tdClass: 'hidden md:table-cell',
  },
  {
    key: 'created_at',
    label: t('activity_log.timestamp'),
    thClass: 'hidden sm:table-cell',
    tdClass: 'hidden sm:table-cell',
  },
])

const showEmptyScreen = computed(() => {
  return !usersStore.totalUsers && !isFetchingInitialData.value
})

const selectField = computed({
  get: () => usersStore.selectedUsers,
  set: (value) => {
    return usersStore.selectUser(value)
  },
})

const selectAllFieldStatus = computed({
  get: () => usersStore.selectAllField,
  set: (value) => {
    return usersStore.setSelectAllState(value)
  },
})

watch(
  filters,
  () => {
    setFilters()
  },
  { deep: true }
)

watch(
  activityFilters,
  () => {
    activityTable.value && activityTable.value.refresh()
  },
  { deep: true }
)

const usageStats = computed(() => usersStore.usageStats)

onMounted(() => {
  usersStore.fetchUsers()
  usersStore.fetchRoles()
  usersStore.fetchUsageStats()
})

onUnmounted(() => {
  if (usersStore.selectAllField) {
    usersStore.selectAllUsers()
  }
})

function selectAllUser(params) {
  usersStore.selectAllUsers()
}

function setFilters() {
  refreshTable()
}

function refreshTable() {
  table.value && table.value.refresh()
}

async function fetchData({ page, filter, sort }) {
  let data = {
    display_name: filters.name !== null ? filters.name : '',
    phone: filters.phone !== null ? filters.phone : '',
    email: filters.email !== null ? filters.email : '',
    role: filters.role || '',
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  if (filters.is_active !== null && filters.is_active !== '') {
    data.is_active = filters.is_active
  }

  isFetchingInitialData.value = true

  let response = await usersStore.fetchUsers(data)

  isFetchingInitialData.value = false

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.meta.last_page,
      currentPage: page,
      totalCount: response.data.meta.total,
      limit: 10,
    },
  }
}

async function fetchActivityData({ page, filter, sort }) {
  let data = {
    per_page: 20,
    page,
  }

  if (activityFilters.user_id) data.user_id = activityFilters.user_id
  if (activityFilters.event) data.event = activityFilters.event
  if (activityFilters.auditable_type) data.auditable_type = activityFilters.auditable_type

  isFetchingActivityData.value = true

  try {
    let response = await activityLogStore.fetchActivityLogs(data)

    isFetchingActivityData.value = false
    showActivityEmpty.value = !response.data.data.length && !activityFilters.user_id && !activityFilters.event && !activityFilters.auditable_type

    return {
      data: response.data.data,
      pagination: {
        totalPages: response.data.meta?.last_page || 1,
        currentPage: page,
        totalCount: response.data.meta?.total || 0,
        limit: 20,
      },
    }
  } catch (err) {
    isFetchingActivityData.value = false
    showActivityEmpty.value = true
    return { data: [], pagination: { totalPages: 1, currentPage: 1, totalCount: 0, limit: 20 } }
  }
}

function clearFilter() {
  filters.name = ''
  filters.email = ''
  filters.phone = null
  filters.role = null
  filters.is_active = null
}

function toggleFilter() {
  if (showFilters.value) {
    clearFilter()
  }
  showFilters.value = !showFilters.value
}

function clearActivityFilter() {
  activityFilters.user_id = null
  activityFilters.event = null
  activityFilters.auditable_type = null
}

function toggleActivityFilter() {
  if (showActivityFilters.value) {
    clearActivityFilter()
  }
  showActivityFilters.value = !showActivityFilters.value
}

function getInitials(name) {
  if (!name) return '?'
  return name.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}

function formatEntityType(type) {
  if (!type) return '-'
  return type.split('\\').pop()
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const d = new Date(dateStr)
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

function eventBadgeClass(event) {
  switch (event) {
    case 'created': return 'bg-green-100 text-green-800'
    case 'updated': return 'bg-blue-100 text-blue-800'
    case 'deleted': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

function showChangesModal(log) {
  selectedLog.value = log
  showChanges.value = true
}

function removeUser(id) {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('users.confirm_delete', 1),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'lg',
      hideNoButton: false,
    })
    .then((res) => {
      if (res) {
        let user = [id]
        usersStore.deleteUser(user).then((response) => {
          if (response.data.success) {
            table.value && table.value.refresh()
            return true
          }

          if (response.data.error === 'user_attached') {
            notificationStore.showNotification({
              type: 'error',
              message: t('users.user_attached_message'),
            })
            return true
          }
        })
      }
    })
}

function removeMultipleUsers() {
  dialogStore
    .openDialog({
      title: t('general.are_you_sure'),
      message: t('users.confirm_delete', 2),
      yesLabel: t('general.ok'),
      noLabel: t('general.cancel'),
      variant: 'danger',
      size: 'lg',
      hideNoButton: false,
    })
    .then((res) => {
      if (res) {
        usersStore.deleteMultipleUsers().then((res) => {
          if (res.data.success) {
            table.value && table.value.refresh()
          }
        })
      }
    })
}
</script>
