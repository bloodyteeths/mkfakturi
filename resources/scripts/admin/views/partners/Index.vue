<template>
  <BasePage>
    <!-- Page Header -->
    <BasePageHeader :title="$t('partners.title', 2)">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('partners.title', 2)" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <div class="flex items-center justify-end space-x-5">
          <BaseButton variant="primary-outline" @click="toggleFilter">
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

          <BaseButton @click="$router.push('/admin/partners/create')">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ $t('partners.new_partner') }}
          </BaseButton>
        </div>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <BaseFilterWrapper :show="showFilters" class="mt-5" @clear="clearFilter">
      <BaseInputGroup :label="$t('general.search')" class="text-left">
        <BaseInput
          v-model="filters.search"
          type="text"
          name="search"
          autocomplete="off"
          :placeholder="$t('partners.search_placeholder')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.status')" class="text-left">
        <BaseSelect v-model="filters.status">
          <option value="">{{ $t('general.all') }}</option>
          <option value="active">{{ $t('general.active') }}</option>
          <option value="inactive">{{ $t('general.inactive') }}</option>
        </BaseSelect>
      </BaseInputGroup>

      <BaseInputGroup :label="$t('partners.kyc_status')" class="text-left">
        <BaseSelect v-model="filters.kyc_status">
          <option value="">{{ $t('general.all') }}</option>
          <option value="pending">{{ $t('partners.kyc.pending') }}</option>
          <option value="under_review">{{ $t('partners.kyc.under_review') }}</option>
          <option value="approved">{{ $t('partners.kyc.approved') }}</option>
          <option value="rejected">{{ $t('partners.kyc.rejected') }}</option>
        </BaseSelect>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Statistics Cards -->
    <div v-if="stats" class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-4">
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.stats.total_partners') }}</div>
        <div class="text-2xl font-semibold">{{ stats.total_partners }}</div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.stats.active_partners') }}</div>
        <div class="text-2xl font-semibold text-green-600">{{ stats.active_partners }}</div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.stats.partner_plus') }}</div>
        <div class="text-2xl font-semibold text-purple-600">{{ stats.partner_plus_count }}</div>
      </div>
      <div class="p-4 bg-white border border-gray-200 rounded-lg">
        <div class="text-sm text-gray-500">{{ $t('partners.stats.pending_kyc') }}</div>
        <div class="text-2xl font-semibold text-orange-600">{{ stats.pending_kyc }}</div>
      </div>
    </div>

    <!-- Empty State -->
    <BaseEmptyPlaceholder
      v-show="showEmptyScreen"
      :title="$t('partners.no_partners')"
      :description="$t('partners.list_description')"
    >
      <AstronautIcon class="mt-5 mb-4" />
      <template #actions>
        <BaseButton
          variant="primary-outline"
          @click="$router.push('/admin/partners/create')"
        >
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('partners.add_new_partner') }}
        </BaseButton>
      </template>
    </BaseEmptyPlaceholder>

    <!-- Partners Table -->
    <div v-show="!showEmptyScreen" class="relative mt-6 table-container">
      <BaseTable
        ref="tableComponent"
        :data="fetchData"
        :columns="partnerColumns"
        class="mt-3"
      >
        <template #cell-name="{ row }">
          <router-link :to="{ path: `partners/${row.data.id}` }">
            <BaseText
              :text="row.data.name"
              tag="span"
              class="font-medium text-primary-500"
            />
            <div class="text-xs text-gray-400">{{ row.data.email }}</div>
          </router-link>
        </template>

        <template #cell-company_name="{ row }">
          <span class="text-sm">
            {{ row.data.company_name || '-' }}
          </span>
        </template>

        <template #cell-companies_count="{ row }">
          <span class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded">
            {{ row.data.companies_count || 0 }}
          </span>
        </template>

        <template #cell-total_earnings="{ row }">
          <BaseFormatMoney
            :amount="row.data.total_earnings || 0"
            :currency="globalStore.companySettings.currency"
          />
        </template>

        <template #cell-status="{ row }">
          <span
            class="px-2 py-1 text-xs font-medium rounded"
            :class="row.data.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
          >
            {{ row.data.is_active ? $t('general.active') : $t('general.inactive') }}
          </span>
          <span
            v-if="row.data.is_partner_plus"
            class="ml-2 px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded"
          >
            Plus
          </span>
        </template>

        <template #cell-kyc_status="{ row }">
          <span
            class="px-2 py-1 text-xs font-medium rounded"
            :class="{
              'bg-gray-100 text-gray-800': row.data.kyc_status === 'pending',
              'bg-yellow-100 text-yellow-800': row.data.kyc_status === 'under_review',
              'bg-green-100 text-green-800': row.data.kyc_status === 'approved',
              'bg-red-100 text-red-800': row.data.kyc_status === 'rejected'
            }"
          >
            {{ $t(`partners.kyc.${row.data.kyc_status || 'pending'}`) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <BaseDropdown>
            <template #activator>
              <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500" />
            </template>

            <BaseDropdownItem @click="$router.push(`/admin/partners/${row.data.id}`)">
              <BaseIcon name="EyeIcon" class="mr-3 text-gray-600" />
              {{ $t('general.view') }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="$router.push(`/admin/partners/${row.data.id}/edit`)">
              <BaseIcon name="PencilIcon" class="mr-3 text-gray-600" />
              {{ $t('general.edit') }}
            </BaseDropdownItem>

            <BaseDropdownItem @click="deactivatePartner(row.data.id)">
              <BaseIcon name="TrashIcon" class="mr-3 text-gray-600" />
              {{ $t('general.deactivate') }}
            </BaseDropdownItem>
          </BaseDropdown>
        </template>
      </BaseTable>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import axios from 'axios'
import AstronautIcon from '@/scripts/components/icons/empty/AstronautIcon.vue'
import { useNotificationStore } from '@/scripts/stores/notification'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const { t } = useI18n()
const router = useRouter()
const notificationStore = useNotificationStore()
const globalStore = useGlobalStore()

const tableComponent = ref(null)
const showFilters = ref(false)
const stats = ref(null)

const filters = reactive({
  search: '',
  status: '',
  kyc_status: '',
})

const partnerColumns = ref([
  {
    key: 'name',
    label: t('partners.name'),
    thClass: 'extra',
    tdClass: 'font-medium text-gray-900',
    sortable: true,
  },
  {
    key: 'company_name',
    label: t('partners.company_name'),
    sortable: true,
  },
  {
    key: 'companies_count',
    label: t('partners.companies'),
    sortable: true,
  },
  {
    key: 'total_earnings',
    label: t('partners.total_earnings'),
    sortable: true,
  },
  {
    key: 'status',
    label: t('general.status'),
    sortable: true,
  },
  {
    key: 'kyc_status',
    label: t('partners.kyc_status'),
    sortable: true,
  },
  {
    key: 'actions',
    label: '',
    tdClass: 'text-right',
  },
])

const showEmptyScreen = computed(() => {
  // Only show empty screen if we've loaded data and have zero partners
  // null means we haven't loaded yet, so don't show empty state
  const isEmpty = totalPartners.value === 0
  console.log('[Partners Index] showEmptyScreen computed:', {
    totalPartners: totalPartners.value,
    isEmpty,
  })
  return isEmpty
})

const totalPartners = ref(null)

async function fetchData({ page, filter, sort }) {
  const params = {
    page,
    per_page: 15,
    search: filters.search,
    status: filters.status,
    kyc_status: filters.kyc_status,
    sort_by: sort.fieldName || 'created_at',
    sort_order: sort.order || 'desc',
  }

  console.log('[Partners Index] Fetching data with params:', params)
  const response = await axios.get('/partners', { params })
  console.log('[Partners Index] Response received:', {
    total: response.data.total,
    dataLength: response.data.data?.length,
    currentPage: response.data.current_page,
    lastPage: response.data.last_page,
  })

  totalPartners.value = response.data.total
  console.log('[Partners Index] totalPartners.value set to:', totalPartners.value)
  console.log('[Partners Index] showEmptyScreen computed:', showEmptyScreen.value)

  return {
    data: response.data.data,
    pagination: {
      totalPages: response.data.last_page,
      currentPage: response.data.current_page,
      count: response.data.total,
    },
  }
}

async function fetchStats() {
  try {
    const response = await axios.get('/partners/stats')
    stats.value = response.data
  } catch (error) {
    console.error('Failed to fetch partner stats:', error)
  }
}

function toggleFilter() {
  showFilters.value = !showFilters.value
}

function clearFilter() {
  filters.search = ''
  filters.status = ''
  filters.kyc_status = ''
  refreshTable()
}

function refreshTable() {
  tableComponent.value?.refresh()
}

async function deactivatePartner(id) {
  if (!confirm(t('partners.confirm_deactivate'))) return

  try {
    await axios.delete(`/partners/${id}`)
    notificationStore.showNotification({
      type: 'success',
      message: t('partners.deactivated_successfully'),
    })
    refreshTable()
    fetchStats()
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: t('partners.deactivate_failed'),
    })
  }
}

onMounted(() => {
  console.log('[Partners Index] Component mounted')
  console.log('[Partners Index] totalPartners initial value:', totalPartners.value)
  fetchStats()
})
</script>

<!-- CLAUDE-CHECKPOINT -->
