<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton
          v-show="totalCount"
          variant="primary-outline"
          @click="showFilters = !showFilters"
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
    <div v-if="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <div class="text-2xl font-bold text-gray-900">{{ stats.total }}</div>
        <div class="text-sm text-gray-600">{{ t('total') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-blue-200 p-4">
        <div class="text-2xl font-bold text-blue-600">{{ stats.new }}</div>
        <div class="text-sm text-gray-600">{{ t('status_new') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-yellow-200 p-4">
        <div class="text-2xl font-bold text-yellow-600">{{ stats.in_progress }}</div>
        <div class="text-sm text-gray-600">{{ t('status_in_progress') }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm border border-green-200 p-4">
        <div class="text-2xl font-bold text-green-600">{{ stats.resolved }}</div>
        <div class="text-sm text-gray-600">{{ t('status_resolved') }}</div>
      </div>
    </div>

    <BaseFilterWrapper
      v-show="showFilters"
      :row-on-xl="true"
      @clear="clearFilter"
    >
      <BaseInputGroup :label="t('status')">
        <BaseMultiselect
          v-model="filters.status"
          :options="statusOptions"
          label="name"
          value-prop="id"
          :placeholder="t('all_statuses')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="t('category')">
        <BaseMultiselect
          v-model="filters.category"
          :options="categoryOptions"
          label="name"
          value-prop="id"
          :placeholder="t('all_categories')"
        />
      </BaseInputGroup>

      <BaseInputGroup :label="$t('general.search')">
        <BaseInput v-model="filters.search" :placeholder="t('search_placeholder')">
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
        </BaseInput>
      </BaseInputGroup>
    </BaseFilterWrapper>

    <BaseEmptyPlaceholder
      v-show="showEmpty"
      :title="t('no_contacts')"
      :description="t('no_contacts_desc')"
    />

    <div v-show="!showEmpty" class="relative table-container">
      <!-- Tabs -->
      <div class="relative flex items-center h-10 mt-5 list-none border-b-2 border-gray-200 border-solid">
        <BaseTabGroup class="-mb-5" @change="setStatusFilter">
          <BaseTab :title="$t('general.all')" filter="" />
          <BaseTab :title="t('status_new')" filter="new" />
          <BaseTab :title="t('status_in_progress')" filter="in_progress" />
          <BaseTab :title="t('status_resolved')" filter="resolved" />
        </BaseTabGroup>
      </div>

      <!-- Mobile: Card View -->
      <div class="block md:hidden mt-4 space-y-4">
        <div
          v-for="contact in contacts"
          :key="contact.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 cursor-pointer"
          @click="viewContact(contact)"
        >
          <div class="flex items-start justify-between mb-2">
            <div>
              <h3 class="text-base font-semibold text-gray-900">{{ contact.subject }}</h3>
              <p class="text-xs text-gray-500">{{ contact.reference_number }}</p>
            </div>
            <span :class="statusClass(contact.status)" class="ml-2 px-2 py-1 text-xs font-medium rounded-full whitespace-nowrap">
              {{ statusLabel(contact.status) }}
            </span>
          </div>
          <p class="text-sm text-gray-600 mb-2">{{ contact.name }} &middot; {{ contact.email }}</p>
          <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ contact.message }}</p>
          <div class="flex items-center justify-between text-xs text-gray-500">
            <span :class="priorityClass(contact.priority)" class="px-2 py-1 rounded-full font-medium">
              {{ contact.priority }}
            </span>
            <span>{{ formatDate(contact.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Desktop: Table View -->
      <div class="hidden md:block">
        <BaseTable
          :data="contacts"
          :columns="columns"
          class="mt-3"
        >
          <template #cell-reference_number="{ row }">
            <a href="#" class="font-mono text-sm text-primary-500 hover:text-primary-600" @click.prevent="viewContact(row.data)">
              {{ row.data.reference_number }}
            </a>
          </template>

          <template #cell-subject="{ row }">
            <a href="#" class="font-medium text-gray-900 hover:text-primary-600" @click.prevent="viewContact(row.data)">
              {{ row.data.subject }}
            </a>
          </template>

          <template #cell-from="{ row }">
            <div class="text-sm">
              <div class="text-gray-900">{{ row.data.name }}</div>
              <div class="text-gray-500 text-xs">{{ row.data.email }}</div>
            </div>
          </template>

          <template #cell-category="{ row }">
            <span class="text-sm text-gray-700 capitalize">{{ row.data.category }}</span>
          </template>

          <template #cell-priority="{ row }">
            <span :class="priorityClass(row.data.priority)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
              {{ row.data.priority }}
            </span>
          </template>

          <template #cell-status="{ row }">
            <span :class="statusClass(row.data.status)" class="px-2 py-1 text-xs font-medium rounded-full">
              {{ statusLabel(row.data.status) }}
            </span>
          </template>

          <template #cell-created_at="{ row }">
            <span class="text-sm text-gray-600">{{ formatDate(row.data.created_at) }}</span>
          </template>

          <template #cell-actions="{ row }">
            <BaseDropdown>
              <template #activator>
                <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500 cursor-pointer" />
              </template>

              <BaseDropdownItem @click="viewContact(row.data)">
                <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
                {{ $t('general.view') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="changeStatus(row.data, 'in_progress')">
                <BaseIcon name="PlayCircleIcon" class="h-5 mr-3 text-yellow-600" />
                {{ t('mark_in_progress') }}
              </BaseDropdownItem>

              <BaseDropdownItem @click="changeStatus(row.data, 'resolved')">
                <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-green-600" />
                {{ t('mark_resolved') }}
              </BaseDropdownItem>
            </BaseDropdown>
          </template>
        </BaseTable>
      </div>

      <BasePagination
        v-if="totalCount > 0"
        v-model="currentPage"
        :total-pages="totalPages"
        :total-count="totalCount"
        :per-page="25"
        class="mt-6"
      />
    </div>

    <!-- View Contact Modal -->
    <BaseModal :show="showModal" @close="showModal = false">
      <template #header>
        <div class="flex items-center justify-between w-full">
          <h3 class="text-lg font-semibold">{{ selected?.reference_number }} — {{ selected?.subject }}</h3>
        </div>
      </template>

      <div v-if="selected" class="space-y-4">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-500">{{ t('from') }}:</span>
            <span class="font-medium ml-1">{{ selected.name }}</span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('email') }}:</span>
            <span class="font-medium ml-1">{{ selected.email }}</span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('category') }}:</span>
            <span class="font-medium ml-1 capitalize">{{ selected.category }}</span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('priority') }}:</span>
            <span :class="priorityClass(selected.priority)" class="ml-1 px-2 py-0.5 text-xs font-medium rounded-full capitalize">
              {{ selected.priority }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('status') }}:</span>
            <span :class="statusClass(selected.status)" class="ml-1 px-2 py-0.5 text-xs font-medium rounded-full">
              {{ statusLabel(selected.status) }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('date') }}:</span>
            <span class="font-medium ml-1">{{ formatDateTime(selected.created_at) }}</span>
          </div>
        </div>

        <div v-if="selected.user" class="text-sm">
          <span class="text-gray-500">{{ t('user') }}:</span>
          <span class="font-medium ml-1">{{ selected.user.name }} (ID: {{ selected.user.id }})</span>
        </div>

        <div v-if="selected.company" class="text-sm">
          <span class="text-gray-500">{{ t('company') }}:</span>
          <span class="font-medium ml-1">{{ selected.company.name }}</span>
        </div>

        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-gray-800 whitespace-pre-wrap">{{ selected.message }}</p>
        </div>

        <div v-if="selected.attachments && selected.attachments.length" class="space-y-1">
          <p class="text-sm font-medium text-gray-700">{{ t('attachments') }}:</p>
          <div v-for="(att, i) in selected.attachments" :key="i" class="flex items-center space-x-2">
            <a
              :href="getAttachmentUrl(selected.id, i)"
              target="_blank"
              class="text-sm text-primary-600 hover:text-primary-800 hover:underline flex items-center"
            >
              <BaseIcon name="PaperClipIcon" class="h-4 w-4 mr-1" />
              {{ att.name }}
            </a>
            <span class="text-xs text-gray-400">({{ att.size }})</span>
          </div>
        </div>

        <!-- Existing Admin Reply -->
        <div v-if="selected.admin_reply" class="border-t pt-4">
          <p class="text-sm font-medium text-gray-700 mb-2">{{ t('previous_reply') }}
            <span class="text-xs text-gray-400 font-normal ml-1">{{ formatDateTime(selected.admin_replied_at) }}</span>
          </p>
          <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
            <p class="text-gray-800 whitespace-pre-wrap">{{ selected.admin_reply }}</p>
          </div>
        </div>

        <!-- Reply Form -->
        <div class="border-t pt-4">
          <p class="text-sm font-medium text-gray-700 mb-2">{{ selected.admin_reply ? t('update_reply') : t('write_reply') }}</p>
          <BaseTextarea
            v-model="replyText"
            :placeholder="t('reply_placeholder')"
            rows="4"
          />
          <div class="flex items-center justify-between mt-3">
            <div class="flex space-x-2">
              <BaseButton v-if="selected.status !== 'in_progress'" size="sm" variant="primary-outline" @click="changeStatus(selected, 'in_progress')">
                {{ t('mark_in_progress') }}
              </BaseButton>
              <BaseButton v-if="selected.status !== 'resolved'" size="sm" variant="primary-outline" @click="changeStatus(selected, 'resolved')">
                {{ t('mark_resolved') }}
              </BaseButton>
            </div>
            <BaseButton
              variant="primary"
              :loading="isSendingReply"
              :disabled="!replyText.trim() || isSendingReply"
              @click="sendReply"
            >
              {{ t('send_reply') }}
            </BaseButton>
          </div>
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

const { locale } = useI18n({ useScope: 'global' })
const notificationStore = useNotificationStore()

const messages = {
  en: {
    title: 'All Support Requests',
    total: 'Total',
    status_new: 'New',
    status_in_progress: 'In Progress',
    status_resolved: 'Resolved',
    status: 'Status',
    category: 'Category',
    priority: 'Priority',
    from: 'From',
    email: 'Email',
    date: 'Date',
    user: 'User',
    company: 'Company',
    attachments: 'Attachments',
    all_statuses: 'All Statuses',
    all_categories: 'All Categories',
    search_placeholder: 'Search by name, email, subject...',
    no_contacts: 'No Support Requests',
    no_contacts_desc: 'Support requests submitted via the contact form will appear here.',
    mark_in_progress: 'Mark In Progress',
    mark_resolved: 'Mark Resolved',
    ref: 'Ref #',
    subject: 'Subject',
    previous_reply: 'Your Previous Reply',
    write_reply: 'Reply to User',
    update_reply: 'Update Reply',
    reply_placeholder: 'Type your reply here... This will be emailed to the user.',
    send_reply: 'Send Reply',
    reply_sent: 'Reply sent successfully',
    reply_failed: 'Failed to send reply',
  },
  mk: {
    title: 'Сите барања за поддршка',
    total: 'Вкупно',
    status_new: 'Ново',
    status_in_progress: 'Во тек',
    status_resolved: 'Решено',
    status: 'Статус',
    category: 'Категорија',
    priority: 'Приоритет',
    from: 'Од',
    email: 'Е-пошта',
    date: 'Датум',
    user: 'Корисник',
    company: 'Компанија',
    attachments: 'Прилози',
    all_statuses: 'Сите статуси',
    all_categories: 'Сите категории',
    search_placeholder: 'Барај по име, е-пошта, предмет...',
    no_contacts: 'Нема барања за поддршка',
    no_contacts_desc: 'Барањата поднесени преку формуларот за контакт ќе се појават тука.',
    mark_in_progress: 'Означи во тек',
    mark_resolved: 'Означи решено',
    ref: 'Реф #',
    subject: 'Предмет',
    previous_reply: 'Вашиот претходен одговор',
    write_reply: 'Одговори на корисник',
    update_reply: 'Ажурирај одговор',
    reply_placeholder: 'Внесете го вашиот одговор... Ова ќе биде испратено по е-пошта до корисникот.',
    send_reply: 'Испрати одговор',
    reply_sent: 'Одговорот е успешно испратен',
    reply_failed: 'Неуспешно испраќање на одговор',
  },
  sq: {
    title: 'Te gjitha kerkesat per mbeshtetje',
    total: 'Totali',
    status_new: 'E re',
    status_in_progress: 'Ne progres',
    status_resolved: 'E zgjidhur',
    status: 'Statusi',
    category: 'Kategoria',
    priority: 'Prioriteti',
    from: 'Nga',
    email: 'Email',
    date: 'Data',
    user: 'Perdoruesi',
    company: 'Kompania',
    attachments: 'Bashkengjitjet',
    all_statuses: 'Te gjitha statuset',
    all_categories: 'Te gjitha kategorite',
    search_placeholder: 'Kerko sipas emrit, emailit, subjektit...',
    no_contacts: 'Nuk ka kerkesa',
    no_contacts_desc: 'Kerkesat e derguara permes formularit te kontaktit do te shfaqen ketu.',
    mark_in_progress: 'Sheno ne progres',
    mark_resolved: 'Sheno te zgjidhur',
    ref: 'Ref #',
    subject: 'Subjekti',
    previous_reply: 'Pergjigja juaj e meparshme',
    write_reply: 'Pergjigju perdoruesit',
    update_reply: 'Perditeso pergjigjen',
    reply_placeholder: 'Shkruani pergjigjen tuaj... Kjo do ti dergohet perdoruesit me email.',
    send_reply: 'Dergo pergjigjen',
    reply_sent: 'Pergjigja u dergua me sukses',
    reply_failed: 'Dergimi i pergjigjes deshtoi',
  },
  tr: {
    title: 'Tum Destek Talepleri',
    total: 'Toplam',
    status_new: 'Yeni',
    status_in_progress: 'Devam Ediyor',
    status_resolved: 'Cozuldu',
    status: 'Durum',
    category: 'Kategori',
    priority: 'Oncelik',
    from: 'Gonderen',
    email: 'E-posta',
    date: 'Tarih',
    user: 'Kullanici',
    company: 'Sirket',
    attachments: 'Ekler',
    all_statuses: 'Tum durumlar',
    all_categories: 'Tum kategoriler',
    search_placeholder: 'Ad, e-posta, konu ile ara...',
    no_contacts: 'Destek talebi yok',
    no_contacts_desc: 'Iletisim formu ile gonderilen talepler burada gorunecektir.',
    mark_in_progress: 'Devam ediyor olarak isaretle',
    mark_resolved: 'Cozuldu olarak isaretle',
    ref: 'Ref #',
    subject: 'Konu',
    previous_reply: 'Onceki yanitiniz',
    write_reply: 'Kullaniciya yanit ver',
    update_reply: 'Yaniti guncelle',
    reply_placeholder: 'Yanitinizi yazin... Bu kullaniciya e-posta olarak gonderilecektir.',
    send_reply: 'Yanit gonder',
    reply_sent: 'Yanit basariyla gonderildi',
    reply_failed: 'Yanit gonderilemedi',
  },
}

const t = (key) => {
  const lang = locale.value || 'mk'
  return messages[lang]?.[key] || messages['en']?.[key] || key
}

const contacts = ref([])
const totalCount = ref(0)
const stats = ref(null)
const showFilters = ref(false)
const currentPage = ref(1)
const isFetching = ref(false)
const showModal = ref(false)
const selected = ref(null)
const replyText = ref('')
const isSendingReply = ref(false)

const filters = ref({ status: null, category: null, search: '' })

const statusOptions = computed(() => [
  { id: 'new', name: t('status_new') },
  { id: 'in_progress', name: t('status_in_progress') },
  { id: 'resolved', name: t('status_resolved') },
])

const categoryOptions = [
  { id: 'technical', name: 'Technical' },
  { id: 'billing', name: 'Billing' },
  { id: 'feature', name: 'Feature Request' },
  { id: 'general', name: 'General' },
]

const columns = computed(() => [
  { key: 'reference_number', label: t('ref'), thClass: 'w-28' },
  { key: 'subject', label: t('subject'), thClass: 'min-w-[200px]' },
  { key: 'from', label: t('from'), thClass: 'w-44' },
  { key: 'category', label: t('category'), thClass: 'w-28' },
  { key: 'priority', label: t('priority'), thClass: 'w-24' },
  { key: 'status', label: t('status'), thClass: 'w-28' },
  { key: 'created_at', label: t('date'), thClass: 'w-32' },
  { key: 'actions', label: '', thClass: 'w-16' },
])

const showEmpty = computed(() => !totalCount.value && !isFetching.value)
const totalPages = computed(() => Math.ceil(totalCount.value / 25))

const loadContacts = async () => {
  isFetching.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: 25,
      status: filters.value.status || undefined,
      category: filters.value.category || undefined,
      search: filters.value.search || undefined,
    }
    const { data } = await axios.get('/support/admin/contacts', { params })
    contacts.value = data.data || []
    totalCount.value = data.total || 0
  } catch (err) {
    console.error('Error loading contacts:', err)
  } finally {
    isFetching.value = false
  }
}

const loadStats = async () => {
  try {
    const { data } = await axios.get('/support/admin/contacts/statistics')
    stats.value = data.data || data
  } catch (err) {
    console.error('Error loading stats:', err)
  }
}

const viewContact = (contact) => {
  selected.value = contact
  replyText.value = contact.admin_reply || ''
  showModal.value = true
}

const sendReply = async () => {
  if (!replyText.value.trim() || !selected.value) return
  isSendingReply.value = true
  try {
    await axios.post(`/support/admin/contacts/${selected.value.id}/reply`, {
      reply: replyText.value,
    })
    notificationStore.showNotification({ type: 'success', message: t('reply_sent') })
    showModal.value = false
    loadContacts()
    loadStats()
  } catch (err) {
    console.error('Error sending reply:', err)
    notificationStore.showNotification({ type: 'error', message: t('reply_failed') })
  } finally {
    isSendingReply.value = false
  }
}

const changeStatus = async (contact, status) => {
  try {
    await axios.post(`/support/admin/contacts/${contact.id}/status`, { status })
    notificationStore.showNotification({ type: 'success', message: 'Status updated' })
    loadContacts()
    loadStats()
  } catch (err) {
    console.error('Error updating status:', err)
    notificationStore.showNotification({ type: 'error', message: 'Failed to update status' })
  }
}

const setStatusFilter = (filter) => {
  filters.value.status = filter || null
  currentPage.value = 1
  loadContacts()
}

const clearFilter = () => {
  filters.value = { status: null, category: null, search: '' }
  currentPage.value = 1
  loadContacts()
}

const statusClass = (status) => {
  const map = {
    new: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
  }
  return map[status] || map.new
}

const statusLabel = (status) => {
  const map = { new: 'status_new', in_progress: 'status_in_progress', resolved: 'status_resolved' }
  return t(map[status] || 'status_new')
}

const priorityClass = (priority) => {
  const map = {
    low: 'bg-gray-100 text-gray-800',
    medium: 'bg-blue-100 text-blue-800',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800',
  }
  return map[priority] || map.medium
}

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : ''
const formatDateTime = (d) => d ? new Date(d).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : ''

const getAttachmentUrl = (contactId, index) => `/api/v1/support/admin/contacts/${contactId}/attachments/${index}`

let searchTimeout = null
watch(() => filters.value.search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadContacts()
  }, 400)
})

watch(currentPage, () => loadContacts())

onMounted(() => {
  loadContacts()
  loadStats()
})
</script>
