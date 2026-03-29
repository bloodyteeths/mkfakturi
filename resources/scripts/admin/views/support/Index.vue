<template>
  <BasePage>
    <BasePageHeader :title="t('title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="t('title')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton variant="primary" @click="$router.push('/admin/support/create')">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ t('new_ticket') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <!-- Stats Cards (mobile: horizontal scroll, desktop: grid) -->
    <div class="flex gap-3 overflow-x-auto pb-2 mt-4 md:grid md:grid-cols-4 md:overflow-visible">
      <div
        v-for="stat in stats"
        :key="stat.key"
        class="flex-shrink-0 w-36 md:w-auto bg-white rounded-lg border p-4 cursor-pointer transition-colors"
        :class="activeFilter === stat.key ? 'border-primary-400 bg-primary-50' : 'border-gray-200 hover:border-gray-300'"
        @click="filterByStatus(stat.key)"
      >
        <p class="text-2xl font-bold" :class="stat.color">{{ stat.count }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ stat.label }}</p>
      </div>
    </div>

    <!-- Filters bar -->
    <div class="mt-4 bg-white rounded-lg border border-gray-200 p-3">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <!-- Search -->
        <div class="flex-1 relative">
          <BaseIcon name="MagnifyingGlassIcon" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('search_placeholder')"
            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
            @input="debouncedFetch"
          />
        </div>

        <!-- Status filter -->
        <select
          v-model="statusFilter"
          class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-1 focus:ring-primary-500"
          @change="fetchTickets"
        >
          <option value="">{{ t('all_statuses') }}</option>
          <option value="open">{{ t('status_open') }}</option>
          <option value="in_progress">{{ t('status_in_progress') }}</option>
          <option value="resolved">{{ t('status_resolved') }}</option>
          <option value="closed">{{ t('status_closed') }}</option>
        </select>

        <!-- Priority filter -->
        <select
          v-model="priorityFilter"
          class="text-sm border border-gray-300 rounded-md px-3 py-2 focus:ring-1 focus:ring-primary-500"
          @change="fetchTickets"
        >
          <option value="">{{ t('all_priorities') }}</option>
          <option value="low">{{ t('pri_low') }}</option>
          <option value="normal">{{ t('pri_normal') }}</option>
          <option value="high">{{ t('pri_high') }}</option>
          <option value="urgent">{{ t('pri_urgent') }}</option>
        </select>

        <!-- Clear filters -->
        <button
          v-if="hasActiveFilters"
          class="text-sm text-gray-500 hover:text-gray-700 whitespace-nowrap"
          @click="clearFilters"
        >
          <BaseIcon name="XMarkIcon" class="h-4 w-4 inline" />
          {{ t('clear') }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="ticketStore.isFetchingTickets" class="flex justify-center py-16">
      <BaseSpinner />
    </div>

    <!-- Empty State -->
    <div
      v-else-if="!ticketStore.tickets.length"
      class="text-center py-16 bg-white rounded-lg border border-gray-200 mt-4"
    >
      <BaseIcon name="LifebuoyIcon" class="h-12 w-12 mx-auto text-gray-300 mb-3" />
      <h3 class="text-lg font-medium text-gray-900 mb-1">{{ t('no_tickets') }}</h3>
      <p class="text-sm text-gray-500 mb-4">{{ t('no_tickets_desc') }}</p>
      <BaseButton variant="primary" @click="$router.push('/admin/support/create')">
        <template #left="slotProps">
          <BaseIcon name="PlusIcon" :class="slotProps.class" />
        </template>
        {{ t('new_ticket') }}
      </BaseButton>
    </div>

    <!-- Ticket List -->
    <div v-else class="mt-4 space-y-3 md:space-y-0">
      <!-- Mobile: Card list -->
      <div class="block md:hidden space-y-3">
        <div
          v-for="ticket in ticketStore.tickets"
          :key="ticket.id"
          class="bg-white rounded-lg border border-gray-200 p-4 active:bg-gray-50 transition-colors"
          @click="$router.push(`/admin/support/${ticket.id}`)"
        >
          <div class="flex items-start justify-between gap-2 mb-2">
            <h4 class="font-medium text-gray-900 text-sm leading-tight flex-1 min-w-0">
              <span class="text-gray-400 font-normal">#{{ ticket.id }}</span>
              {{ ticket.title }}
            </h4>
            <span
              :class="statusClass(ticket.status)"
              class="flex-shrink-0 px-2 py-0.5 text-xs font-medium rounded-full"
            >
              {{ statusLabel(ticket.status) }}
            </span>
          </div>

          <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ ticket.message }}</p>

          <div class="flex items-center justify-between text-xs text-gray-400">
            <div class="flex items-center gap-2">
              <span
                :class="priorityClass(ticket.priority)"
                class="px-1.5 py-0.5 rounded text-xs font-medium"
              >
                {{ priorityLabel(ticket.priority) }}
              </span>
              <span v-if="ticket.messages_count" class="flex items-center gap-0.5">
                <BaseIcon name="ChatBubbleLeftIcon" class="h-3.5 w-3.5" />
                {{ ticket.messages_count }}
              </span>
            </div>
            <span>{{ formatDate(ticket.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- Desktop: Table -->
      <div class="hidden md:block bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
              <th class="text-left px-4 py-3 font-medium text-gray-600 w-16">#</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600">{{ t('col_subject') }}</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 w-24">{{ t('col_priority') }}</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 w-28">{{ t('col_status') }}</th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 w-16">
                <BaseIcon name="ChatBubbleLeftIcon" class="h-4 w-4 text-gray-400" />
              </th>
              <th class="text-left px-4 py-3 font-medium text-gray-600 w-32">{{ t('col_date') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr
              v-for="ticket in ticketStore.tickets"
              :key="ticket.id"
              class="hover:bg-gray-50 cursor-pointer transition-colors"
              @click="$router.push(`/admin/support/${ticket.id}`)"
            >
              <td class="px-4 py-3 text-gray-400 font-mono text-xs">{{ ticket.id }}</td>
              <td class="px-4 py-3">
                <p class="font-medium text-gray-900 truncate max-w-md">{{ ticket.title }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ ticket.user?.name }}</p>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="priorityClass(ticket.priority)"
                  class="px-2 py-0.5 text-xs font-medium rounded-full"
                >
                  {{ priorityLabel(ticket.priority) }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="statusClass(ticket.status)"
                  class="px-2 py-0.5 text-xs font-medium rounded-full"
                >
                  {{ statusLabel(ticket.status) }}
                </span>
              </td>
              <td class="px-4 py-3 text-gray-400 text-center">
                {{ ticket.messages_count || 0 }}
              </td>
              <td class="px-4 py-3 text-gray-500 text-xs">
                {{ formatDate(ticket.created_at) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination (simple) -->
      <div v-if="ticketStore.ticketTotalCount > perPage" class="flex justify-center mt-4">
        <div class="flex items-center gap-2">
          <button
            :disabled="currentPage <= 1"
            class="px-3 py-1.5 text-sm border rounded-md disabled:opacity-40"
            @click="changePage(currentPage - 1)"
          >
            {{ t('prev') }}
          </button>
          <span class="text-sm text-gray-500">
            {{ currentPage }} / {{ totalPages }}
          </span>
          <button
            :disabled="currentPage >= totalPages"
            class="px-3 py-1.5 text-sm border rounded-md disabled:opacity-40"
            @click="changePage(currentPage + 1)"
          >
            {{ t('next') }}
          </button>
        </div>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useTicketStore } from '@/scripts/admin/stores/ticket'

const { locale } = useI18n({ useScope: 'global' })
const ticketStore = useTicketStore()

const searchQuery = ref('')
const statusFilter = ref('')
const priorityFilter = ref('')
const activeFilter = ref('')
const currentPage = ref(1)
const perPage = 15

let debounceTimer = null

// ── i18n ──
const messages = {
  en: {
    title: 'Support',
    new_ticket: 'New Ticket',
    search_placeholder: 'Search tickets...',
    all_statuses: 'All Statuses',
    all_priorities: 'All Priorities',
    clear: 'Clear',
    no_tickets: 'No Support Tickets',
    no_tickets_desc: 'Create your first ticket to get help from our team.',
    stat_total: 'Total',
    stat_open: 'Open',
    stat_in_progress: 'In Progress',
    stat_resolved: 'Resolved',
    status_open: 'Open',
    status_in_progress: 'In Progress',
    status_resolved: 'Resolved',
    status_closed: 'Closed',
    pri_low: 'Low',
    pri_normal: 'Normal',
    pri_high: 'High',
    pri_urgent: 'Urgent',
    col_subject: 'Subject',
    col_priority: 'Priority',
    col_status: 'Status',
    col_date: 'Date',
    prev: 'Previous',
    next: 'Next',
  },
  mk: {
    title: 'Поддршка',
    new_ticket: 'Нов тикет',
    search_placeholder: 'Пребарај тикети...',
    all_statuses: 'Сите статуси',
    all_priorities: 'Сите приоритети',
    clear: 'Исчисти',
    no_tickets: 'Нема тикети',
    no_tickets_desc: 'Креирајте го вашиот прв тикет за да добиете помош од нашиот тим.',
    stat_total: 'Вкупно',
    stat_open: 'Отворени',
    stat_in_progress: 'Во тек',
    stat_resolved: 'Решени',
    status_open: 'Отворен',
    status_in_progress: 'Во тек',
    status_resolved: 'Решен',
    status_closed: 'Затворен',
    pri_low: 'Низок',
    pri_normal: 'Нормален',
    pri_high: 'Висок',
    pri_urgent: 'Итно',
    col_subject: 'Предмет',
    col_priority: 'Приоритет',
    col_status: 'Статус',
    col_date: 'Датум',
    prev: 'Претходна',
    next: 'Следна',
  },
  sq: {
    title: 'Mbeshtetja',
    new_ticket: 'Tiketë e re',
    search_placeholder: 'Kërko tiketa...',
    all_statuses: 'Të gjitha statuset',
    all_priorities: 'Të gjitha prioritetet',
    clear: 'Pastro',
    no_tickets: 'Nuk ka tiketa',
    no_tickets_desc: 'Krijoni tiketën tuaj të parë për të marrë ndihmë nga ekipi ynë.',
    stat_total: 'Gjithsej',
    stat_open: 'Të hapura',
    stat_in_progress: 'Në progres',
    stat_resolved: 'Të zgjidhura',
    status_open: 'E hapur',
    status_in_progress: 'Në progres',
    status_resolved: 'E zgjidhur',
    status_closed: 'E mbyllur',
    pri_low: 'I ulët',
    pri_normal: 'Normal',
    pri_high: 'I lartë',
    pri_urgent: 'Urgjent',
    col_subject: 'Subjekti',
    col_priority: 'Prioriteti',
    col_status: 'Statusi',
    col_date: 'Data',
    prev: 'Para',
    next: 'Pas',
  },
  tr: {
    title: 'Destek',
    new_ticket: 'Yeni Tiket',
    search_placeholder: 'Tiket ara...',
    all_statuses: 'Tüm durumlar',
    all_priorities: 'Tüm öncelikler',
    clear: 'Temizle',
    no_tickets: 'Tiket yok',
    no_tickets_desc: 'Ekibimizden yardım almak için ilk tiketinizi oluşturun.',
    stat_total: 'Toplam',
    stat_open: 'Açık',
    stat_in_progress: 'Devam ediyor',
    stat_resolved: 'Çözüldü',
    status_open: 'Açık',
    status_in_progress: 'Devam ediyor',
    status_resolved: 'Çözüldü',
    status_closed: 'Kapatıldı',
    pri_low: 'Düşük',
    pri_normal: 'Normal',
    pri_high: 'Yüksek',
    pri_urgent: 'Acil',
    col_subject: 'Konu',
    col_priority: 'Öncelik',
    col_status: 'Durum',
    col_date: 'Tarih',
    prev: 'Önceki',
    next: 'Sonraki',
  },
}

const t = (key) => {
  const lang = locale.value || 'mk'
  return messages[lang]?.[key] || messages['en']?.[key] || key
}

// ── Stats ──
const stats = computed(() => {
  const tickets = ticketStore.tickets
  const total = ticketStore.ticketTotalCount || tickets.length
  return [
    { key: '', label: t('stat_total'), count: total, color: 'text-gray-900' },
    { key: 'open', label: t('stat_open'), count: tickets.filter((x) => x.status === 'open').length, color: 'text-blue-600' },
    { key: 'in_progress', label: t('stat_in_progress'), count: tickets.filter((x) => x.status === 'in_progress').length, color: 'text-yellow-600' },
    { key: 'resolved', label: t('stat_resolved'), count: tickets.filter((x) => x.status === 'resolved').length, color: 'text-green-600' },
  ]
})

const hasActiveFilters = computed(() => searchQuery.value || statusFilter.value || priorityFilter.value)
const totalPages = computed(() => Math.ceil(ticketStore.ticketTotalCount / perPage))

// ── Helpers ──
const statusClass = (s) =>
  ({
    open: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-yellow-100 text-yellow-800',
    resolved: 'bg-green-100 text-green-800',
    closed: 'bg-gray-100 text-gray-800',
  })[s] || 'bg-gray-100 text-gray-800'

const statusLabel = (s) =>
  t({ open: 'status_open', in_progress: 'status_in_progress', resolved: 'status_resolved', closed: 'status_closed' }[s] || 'status_open')

const priorityClass = (p) =>
  ({
    low: 'bg-gray-100 text-gray-700',
    normal: 'bg-blue-50 text-blue-700',
    high: 'bg-orange-100 text-orange-800',
    urgent: 'bg-red-100 text-red-800',
  })[p] || 'bg-gray-100 text-gray-700'

const priorityLabel = (p) =>
  t({ low: 'pri_low', normal: 'pri_normal', high: 'pri_high', urgent: 'pri_urgent' }[p] || 'pri_normal')

const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : ''

// ── Actions ──
const filterByStatus = (status) => {
  if (activeFilter.value === status) {
    activeFilter.value = ''
    statusFilter.value = ''
  } else {
    activeFilter.value = status
    statusFilter.value = status
  }
  currentPage.value = 1
  fetchTickets()
}

const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  priorityFilter.value = ''
  activeFilter.value = ''
  currentPage.value = 1
  fetchTickets()
}

const changePage = (page) => {
  currentPage.value = page
  fetchTickets()
}

const debouncedFetch = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    currentPage.value = 1
    fetchTickets()
  }, 400)
}

const fetchTickets = () => {
  const params = { limit: perPage, page: currentPage.value }
  if (statusFilter.value) params.status = statusFilter.value
  if (priorityFilter.value) params.priority = priorityFilter.value
  if (searchQuery.value) params.search = searchQuery.value
  ticketStore.fetchTickets(params)
}

onMounted(() => {
  fetchTickets()
})
</script>
