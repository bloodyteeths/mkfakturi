<template>
  <BasePage>
    <BasePageHeader :title="$t('activity_log.title')">
    </BasePageHeader>

    <!-- Company Selector -->
    <div class="mb-6">
      <BaseInputGroup :label="$t('partner.select_company')">
        <BaseMultiselect
          v-model="selectedCompanyId"
          :options="companies"
          :searchable="true"
          track-by="id"
          label="name"
          value-prop="id"
          :placeholder="$t('partner.select_company_placeholder')"
          @update:model-value="onCompanyChange"
        />
      </BaseInputGroup>
    </div>

    <!-- Select company message -->
    <div
      v-if="!selectedCompanyId"
      class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
    >
      <BaseIcon name="BuildingOfficeIcon" class="h-12 w-12 text-gray-400" />
      <p class="mt-2 text-sm text-gray-500">
        {{ $t('partner.accounting.select_company_to_view') }}
      </p>
    </div>

    <template v-if="selectedCompanyId">
      <!-- Filters -->
      <div class="mb-4 flex items-center space-x-2">
        <BaseButton
          variant="primary-outline"
          @click="toggleFilters"
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
      </div>

      <BaseFilterWrapper :show="showFilters" class="mt-3 mb-4" @clear="clearFilters">
        <BaseInputGroup :label="$t('activity_log.event')" class="flex-1 mt-2 mr-4">
          <BaseMultiselect
            v-model="filters.event"
            :options="eventOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('activity_log.all_events')"
          />
        </BaseInputGroup>

        <BaseInputGroup :label="$t('activity_log.entity')" class="flex-1 mt-2 mr-4">
          <BaseMultiselect
            v-model="filters.auditable_type"
            :options="entityTypeOptions"
            label="label"
            value-prop="value"
            :can-deselect="true"
            :placeholder="$t('activity_log.all_entities')"
          />
        </BaseInputGroup>
      </BaseFilterWrapper>

      <!-- Loading -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <LoadingIcon class="h-6 w-6 text-primary-500 animate-spin" />
      </div>

      <!-- Empty state -->
      <div
        v-else-if="!logs.length"
        class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 py-12"
      >
        <BaseIcon name="ClipboardDocumentListIcon" class="h-12 w-12 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500">{{ $t('activity_log.no_activity_description') }}</p>
      </div>

      <!-- Activity Log Table -->
      <div v-else class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('activity_log.user') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('activity_log.event') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('activity_log.entity') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('activity_log.changes') }}</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('activity_log.timestamp') }}</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="log in logs" :key="log.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-7 w-7 rounded-full bg-primary-100 flex items-center justify-center mr-2">
                    <span class="text-xs font-medium text-primary-700">
                      {{ getInitials(log.user_name) }}
                    </span>
                  </div>
                  <span class="text-sm font-medium text-gray-900">{{ log.user_name }}</span>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                  :class="eventBadgeClass(log.event)"
                >
                  {{ $t(`activity_log.${log.event}`) }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="text-sm text-gray-600">{{ formatEntityType(log.auditable_type) }}</span>
                <span v-if="log.auditable" class="text-sm font-medium ml-1">{{ log.auditable.name }}</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <button
                  v-if="log.changed_fields && log.changed_fields.length"
                  class="text-sm text-primary-500 hover:text-primary-700"
                  @click="showChangesModal(log)"
                >
                  {{ log.changed_fields.length }} {{ $t('activity_log.fields_changed') }}
                </button>
                <span v-else class="text-sm text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(log.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="px-6 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
          <span class="text-sm text-gray-500">
            {{ $t('general.page') }} {{ currentPage }} / {{ totalPages }}
          </span>
          <div class="space-x-2">
            <BaseButton
              variant="primary-outline"
              size="sm"
              :disabled="currentPage <= 1"
              @click="loadPage(currentPage - 1)"
            >
              &laquo;
            </BaseButton>
            <BaseButton
              variant="primary-outline"
              size="sm"
              :disabled="currentPage >= totalPages"
              @click="loadPage(currentPage + 1)"
            >
              &raquo;
            </BaseButton>
          </div>
        </div>
      </div>
    </template>

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
            <div class="grid grid-cols-2 gap-4 text-sm">
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
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted, watch, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import { useConsoleStore } from '@/scripts/admin/stores/console'
import { useNotificationStore } from '@/scripts/stores/notification'
import LoadingIcon from '@heroicons/vue/20/solid/ArrowPathIcon'

const { t } = useI18n()
const consoleStore = useConsoleStore()
const notificationStore = useNotificationStore()

const selectedCompanyId = ref(null)
const isLoading = ref(false)
const logs = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const showFilters = ref(false)
const showChanges = ref(false)
const selectedLog = ref(null)

const filters = reactive({
  event: null,
  auditable_type: null,
})

const companies = computed(() => consoleStore.managedCompanies || [])

const eventOptions = computed(() => [
  { value: 'created', label: t('activity_log.created') },
  { value: 'updated', label: t('activity_log.updated') },
  { value: 'deleted', label: t('activity_log.deleted') },
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

watch(filters, () => {
  if (selectedCompanyId.value) {
    currentPage.value = 1
    loadData()
  }
}, { deep: true })

onMounted(async () => {
  await consoleStore.fetchCompanies()
  if (companies.value.length === 1) {
    selectedCompanyId.value = companies.value[0].id
    onCompanyChange()
  }
})

function onCompanyChange() {
  if (!selectedCompanyId.value) {
    logs.value = []
    return
  }
  currentPage.value = 1
  loadData()
}

async function loadData() {
  if (!selectedCompanyId.value) return

  isLoading.value = true
  try {
    const params = {
      per_page: 20,
      page: currentPage.value,
    }
    if (filters.event) params.event = filters.event
    if (filters.auditable_type) params.auditable_type = filters.auditable_type

    const { data } = await window.axios.get('/audit-logs', {
      params,
      headers: { company: selectedCompanyId.value },
    })

    logs.value = data.data
    totalPages.value = data.meta?.last_page || 1
  } catch (err) {
    logs.value = []
    notificationStore.showNotification({
      type: 'error',
      message: err.response?.data?.message || 'Failed to load activity logs',
    })
  } finally {
    isLoading.value = false
  }
}

function loadPage(page) {
  currentPage.value = page
  loadData()
}

function toggleFilters() {
  if (showFilters.value) {
    clearFilters()
  }
  showFilters.value = !showFilters.value
}

function clearFilters() {
  filters.event = null
  filters.auditable_type = null
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
</script>
