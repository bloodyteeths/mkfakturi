<template>
  <BasePage>
    <BasePageHeader :title="t('manufacturing.boms')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('manufacturing.title')" to="/admin/manufacturing" />
        <BaseBreadcrumbItem :title="t('manufacturing.boms')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="showFilters || items.length > 0"
          variant="primary-outline"
          @click="showFilters = !showFilters"
        >
          {{ $t('general.filter') }}
          <template #right="slotProps">
            <BaseIcon v-if="!showFilters" name="FunnelIcon" :class="slotProps.class" />
            <BaseIcon v-else name="XMarkIcon" :class="slotProps.class" />
          </template>
        </BaseButton>

        <router-link to="/admin/manufacturing/boms/create">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            {{ t('manufacturing.new_bom') }}
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Filters -->
    <BaseFilterWrapper v-show="showFilters" @clear="clearFilters">
      <BaseInputGroup :label="$t('general.search')">
        <BaseInput
          v-model="filters.search"
          type="text"
          :placeholder="$t('general.search')"
          @input="debouncedFetch"
        />
      </BaseInputGroup>
      <BaseInputGroup :label="t('manufacturing.is_active')">
        <BaseMultiselect
          v-model="filters.is_active"
          :options="[
            { label: $t('general.yes'), value: '1' },
            { label: $t('general.no'), value: '0' },
          ]"
          label="label"
          value-prop="value"
          :placeholder="$t('general.all')"
          :can-deselect="true"
        />
      </BaseInputGroup>
    </BaseFilterWrapper>

    <!-- Loading -->
    <div v-if="isLoading" class="overflow-hidden rounded-lg bg-white shadow">
      <div class="space-y-4 p-6">
        <div v-for="i in 5" :key="i" class="flex animate-pulse space-x-4">
          <div class="h-4 w-24 rounded bg-gray-200"></div>
          <div class="h-4 flex-1 rounded bg-gray-200"></div>
          <div class="h-4 w-20 rounded bg-gray-200"></div>
          <div class="h-4 w-16 rounded bg-gray-200"></div>
        </div>
      </div>
    </div>

    <!-- Table -->
    <div v-else-if="items.length > 0" class="overflow-hidden rounded-lg bg-white shadow">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ t('manufacturing.bom_code') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ t('manufacturing.bom_name') }}
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ t('manufacturing.output_item') }}
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ t('manufacturing.output_quantity') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ t('manufacturing.version') }}
              </th>
              <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">
                {{ t('manufacturing.is_active') }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr
              v-for="bom in items"
              :key="bom.id"
              class="cursor-pointer hover:bg-gray-50"
              @click="router.push(`/admin/manufacturing/boms/${bom.id}`)"
            >
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-primary-600">
                {{ bom.code }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                {{ bom.name }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                {{ bom.output_item?.name || '-' }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-gray-600">
                {{ bom.output_quantity }}
                <span v-if="bom.output_unit" class="text-gray-400">{{ bom.output_unit.name }}</span>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-center text-sm text-gray-600">
                v{{ bom.version || 1 }}
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-center">
                <span
                  :class="bom.is_active
                    ? 'bg-green-100 text-green-800'
                    : 'bg-gray-100 text-gray-600'"
                  class="inline-flex rounded-full px-2 text-xs font-semibold leading-5"
                >
                  {{ bom.is_active ? $t('general.yes') : $t('general.no') }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="flex items-center justify-between border-t border-gray-200 px-6 py-3">
        <p class="text-sm text-gray-500">
          {{ meta.total }} {{ t('manufacturing.boms').toLowerCase() }}
        </p>
        <div class="flex space-x-1">
          <BaseButton
            v-for="page in meta.last_page"
            :key="page"
            :variant="page === meta.current_page ? 'primary' : 'primary-outline'"
            size="sm"
            @click="fetchItems(page)"
          >
            {{ page }}
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="rounded-lg border-2 border-dashed border-gray-300 px-6 py-12">
      <div class="text-center">
        <BaseIcon name="ClipboardDocumentListIcon" class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-3 text-base font-semibold text-gray-900">
          {{ t('manufacturing.empty_boms') }}
        </h3>
        <p class="mx-auto mt-1 max-w-md text-sm text-gray-500">
          {{ t('manufacturing.empty_boms_description') }}
        </p>
        <div class="mt-6">
          <router-link to="/admin/manufacturing/boms/create">
            <BaseButton variant="primary">
              <template #left="slotProps">
                <BaseIcon name="PlusIcon" :class="slotProps.class" />
              </template>
              {{ t('manufacturing.new_bom') }}
            </BaseButton>
          </router-link>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useNotificationStore } from '@/scripts/stores/notification'
import { debounce } from 'lodash'

const router = useRouter()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const items = ref([])
const meta = ref(null)
const isLoading = ref(false)
const showFilters = ref(false)
const filters = reactive({
  search: '',
  is_active: null,
})

async function fetchItems(page = 1) {
  isLoading.value = true
  try {
    const params = { page, limit: 15 }
    if (filters.search) params.search = filters.search
    if (filters.is_active !== null && filters.is_active !== undefined) {
      params.is_active = filters.is_active
    }

    const response = await window.axios.get('/manufacturing/boms', { params })
    items.value = response.data.data || []
    meta.value = response.data.meta || null
  } catch (error) {
    notificationStore.showNotification({
      type: 'error',
      message: error.response?.data?.message || t('manufacturing.error_loading'),
    })
  } finally {
    isLoading.value = false
  }
}

function clearFilters() {
  filters.search = ''
  filters.is_active = null
  fetchItems(1)
}

const debouncedFetch = debounce(() => fetchItems(1), 400)

watch(() => filters.is_active, () => fetchItems(1))

onMounted(() => fetchItems())
</script>
