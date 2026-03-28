<template>
  <BasePage>
    <BasePageHeader title="Магацински документи">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem title="Документи" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <router-link :to="{ name: 'stock.documents.create' }">
          <BaseButton variant="primary">
            <template #left="slotProps">
              <BaseIcon name="PlusIcon" :class="slotProps.class" />
            </template>
            Нов документ
          </BaseButton>
        </router-link>
      </template>
    </BasePageHeader>

    <!-- Stock Sub-Navigation Tabs -->
    <StockTabNavigation />

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap items-center gap-3">
      <div class="w-48">
        <BaseMultiselect
          v-model="filters.type"
          :options="typeOptions"
          value-prop="value"
          label="label"
          placeholder="Тип на документ"
          :canClear="true"
        />
      </div>
      <div class="w-40">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          value-prop="value"
          label="label"
          placeholder="Статус"
          :canClear="true"
        />
      </div>
      <div class="w-40">
        <BaseInput
          v-model="filters.from_date"
          type="date"
          placeholder="Од датум"
        />
      </div>
      <div class="w-40">
        <BaseInput
          v-model="filters.to_date"
          type="date"
          placeholder="До датум"
        />
      </div>
      <div class="w-48">
        <BaseInput
          v-model="filters.search"
          type="text"
          placeholder="Пребарај по број..."
        />
      </div>
    </div>

    <!-- Documents Table -->
    <BaseCard>
      <div v-if="isLoading" class="flex justify-center py-8">
        <BaseContentPlaceholders>
          <BaseContentPlaceholdersBox class="w-full h-64" />
        </BaseContentPlaceholders>
      </div>

      <div v-else-if="documents.length === 0" class="text-center py-12">
        <BaseIcon name="DocumentTextIcon" class="h-12 w-12 text-gray-400 mx-auto mb-4" />
        <h3 class="text-lg font-medium text-gray-900">Нема документи</h3>
        <p class="text-gray-500 mt-2">Креирајте го вашиот прв магацински документ.</p>
        <router-link :to="{ name: 'stock.documents.create' }" class="inline-block mt-4">
          <BaseButton variant="primary">
            Нов документ
          </BaseButton>
        </router-link>
      </div>

      <table v-else class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Број
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Тип
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Магацин
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Датум
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              Ставки
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              Вкупна вредност
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
              Статус
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">
              Акции
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr
            v-for="doc in documents"
            :key="doc.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewDocument(doc)"
          >
            <td class="px-4 py-3 text-sm font-medium text-primary-600 whitespace-nowrap">
              {{ doc.document_number }}
            </td>
            <td class="px-4 py-3 text-sm whitespace-nowrap">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="typeBadgeClass(doc.document_type)"
              >
                {{ doc.document_type_label }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">
              {{ doc.warehouse_name }}
              <span v-if="doc.destination_warehouse_name" class="text-gray-400">
                &rarr; {{ doc.destination_warehouse_name }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
              {{ doc.document_date }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-900">
              {{ doc.items_count }}
            </td>
            <td class="px-4 py-3 text-sm text-right text-gray-900 whitespace-nowrap">
              <BaseFormatMoney v-if="doc.total_value" :amount="doc.total_value" />
              <span v-else>-</span>
            </td>
            <td class="px-4 py-3 text-sm whitespace-nowrap">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="statusBadgeClass(doc.status)"
              >
                {{ doc.status_label }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right whitespace-nowrap" @click.stop>
              <div class="flex items-center justify-end space-x-2">
                <router-link :to="{ name: 'stock.documents.view', params: { id: doc.id } }">
                  <BaseButton variant="primary-outline" size="sm">
                    <BaseIcon name="EyeIcon" class="h-4 w-4" />
                  </BaseButton>
                </router-link>
                <router-link
                  v-if="doc.status === 'draft'"
                  :to="{ name: 'stock.documents.edit', params: { id: doc.id } }"
                >
                  <BaseButton variant="primary-outline" size="sm">
                    <BaseIcon name="PencilIcon" class="h-4 w-4" />
                  </BaseButton>
                </router-link>
                <BaseButton
                  v-if="doc.status === 'draft'"
                  variant="danger-outline"
                  size="sm"
                  @click="deleteDocument(doc)"
                >
                  <BaseIcon name="TrashIcon" class="h-4 w-4" />
                </BaseButton>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200">
        <div class="text-sm text-gray-700">
          Прикажани {{ documents.length }} од {{ meta.total }} документи
        </div>
        <div class="flex space-x-2">
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="meta.current_page <= 1"
            @click="changePage(meta.current_page - 1)"
          >
            Претходна
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="meta.current_page >= meta.last_page"
            @click="changePage(meta.current_page + 1)"
          >
            Следна
          </BaseButton>
        </div>
      </div>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useDialogStore } from '@/scripts/stores/dialog'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'
import axios from 'axios'

const router = useRouter()
const dialogStore = useDialogStore()
const notificationStore = useNotificationStore()

const documents = ref([])
const isLoading = ref(false)
const currentPage = ref(1)
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
})

const filters = reactive({
  type: null,
  status: null,
  from_date: '',
  to_date: '',
  search: '',
})

const typeOptions = [
  { label: 'Приемница', value: 'receipt' },
  { label: 'Издатница', value: 'issue' },
  { label: 'Преносница', value: 'transfer' },
]

const statusOptions = [
  { label: 'Нацрт', value: 'draft' },
  { label: 'Одобрен', value: 'approved' },
  { label: 'Поништен', value: 'voided' },
]

/**
 * Get CSS class for document type badge.
 */
function typeBadgeClass(type) {
  const classes = {
    receipt: 'bg-green-100 text-green-800',
    issue: 'bg-red-100 text-red-800',
    transfer: 'bg-blue-100 text-blue-800',
    return: 'bg-cyan-100 text-cyan-800',
    write_off: 'bg-amber-100 text-amber-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

/**
 * Get CSS class for status badge.
 */
function statusBadgeClass(status) {
  const classes = {
    draft: 'bg-gray-100 text-gray-800',
    approved: 'bg-green-100 text-green-800',
    voided: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

/**
 * Navigate to document detail view.
 */
function viewDocument(doc) {
  router.push({ name: 'stock.documents.view', params: { id: doc.id } })
}

/**
 * Fetch documents from API with current filters and pagination.
 */
async function fetchDocuments() {
  isLoading.value = true
  try {
    const params = {
      page: currentPage.value,
      limit: 15,
    }
    if (filters.type) params.type = filters.type
    if (filters.status) params.status = filters.status
    if (filters.from_date) params.from_date = filters.from_date
    if (filters.to_date) params.to_date = filters.to_date
    if (filters.search) params.search = filters.search

    const response = await axios.get('/stock/documents', { params })
    documents.value = response.data.data || []
    meta.value = response.data.meta || meta.value
  } catch (error) {
    console.error('Failed to load documents:', error)
  } finally {
    isLoading.value = false
  }
}

/**
 * Delete a draft document after confirmation.
 */
async function deleteDocument(doc) {
  const confirmed = await dialogStore.openDialog({
    title: 'Бришење на документ',
    message: `Дали сте сигурни дека сакате да го избришете документот ${doc.document_number}?`,
    yesLabel: 'Избриши',
    noLabel: 'Откажи',
    variant: 'danger',
  })

  if (confirmed) {
    try {
      await axios.delete(`/stock/documents/${doc.id}`)
      notificationStore.showNotification({
        type: 'success',
        message: 'Документот е успешно избришан.',
      })
      await fetchDocuments()
    } catch (error) {
      console.error('Failed to delete document:', error)
    }
  }
}

/**
 * Change the current page for pagination.
 */
function changePage(page) {
  currentPage.value = page
  fetchDocuments()
}

// Watch filters for automatic re-fetch (debounced via Vue reactivity)
let filterTimeout = null
watch(filters, () => {
  clearTimeout(filterTimeout)
  filterTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchDocuments()
  }, 400)
}, { deep: true })

onMounted(() => {
  fetchDocuments()
})
</script>
