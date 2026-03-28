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
          @click="exportCsv"
        >
          {{ t('export_csv') }}
          <template #right="slotProps">
            <BaseIcon name="ArrowDownTrayIcon" :class="slotProps.class" />
          </template>
        </BaseButton>
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

      <BaseInputGroup :label="t('priority')">
        <BaseMultiselect
          v-model="filters.priority"
          :options="priorityOptions"
          label="name"
          value-prop="id"
          :placeholder="t('all_priorities')"
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

      <!-- Bulk Actions Bar -->
      <transition name="slide-up">
        <div
          v-if="selectedIds.length"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-gray-900 text-white rounded-xl shadow-2xl px-5 py-3 flex items-center space-x-3"
        >
          <span class="text-sm font-medium whitespace-nowrap">
            {{ selectedIds.length }} {{ t('selected') }}
          </span>
          <div class="w-px h-6 bg-gray-600" />
          <button
            class="text-sm px-3 py-1.5 rounded-lg hover:bg-yellow-600 bg-yellow-500 font-medium transition"
            @click="bulkStatus('in_progress')"
          >
            {{ t('mark_in_progress') }}
          </button>
          <button
            class="text-sm px-3 py-1.5 rounded-lg hover:bg-green-600 bg-green-500 font-medium transition"
            @click="bulkStatus('resolved')"
          >
            {{ t('mark_resolved') }}
          </button>
          <button
            class="text-sm px-3 py-1.5 rounded-lg hover:bg-red-600 bg-red-500 font-medium transition"
            @click="confirmBulkDelete"
          >
            {{ t('delete') }}
          </button>
          <button
            class="text-sm px-2 py-1.5 rounded-lg hover:bg-gray-700 transition"
            @click="selectedIds = []"
          >
            <BaseIcon name="XMarkIcon" class="h-4 w-4" />
          </button>
        </div>
      </transition>

      <!-- Mobile: Card View -->
      <div class="block md:hidden mt-4 space-y-4">
        <div
          v-for="contact in contacts"
          :key="contact.id"
          class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
        >
          <div class="flex items-start space-x-3">
            <input
              type="checkbox"
              :checked="selectedIds.includes(contact.id)"
              class="mt-1 h-4 w-4 text-primary-600 rounded border-gray-300"
              @change="toggleSelect(contact.id)"
            />
            <div class="flex-1 cursor-pointer" @click="viewContact(contact)">
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
                <span v-if="contact.assigned_to_obj" class="text-gray-500">
                  {{ contact.assigned_to_obj?.name }}
                </span>
                <span>{{ formatDate(contact.created_at) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Desktop: Table View -->
      <div class="hidden md:block">
        <table class="w-full mt-3">
          <thead>
            <tr class="border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <th class="py-3 px-2 w-10">
                <input
                  type="checkbox"
                  :checked="allSelected"
                  :indeterminate="someSelected && !allSelected"
                  class="h-4 w-4 text-primary-600 rounded border-gray-300"
                  @change="toggleSelectAll"
                />
              </th>
              <th class="py-3 px-2 w-28 cursor-pointer select-none" @click="toggleSort('id')">
                {{ t('ref') }}
                <span v-if="sortBy === 'id'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 min-w-[180px] cursor-pointer select-none" @click="toggleSort('subject')">
                {{ t('subject') }}
                <span v-if="sortBy === 'subject'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-44 cursor-pointer select-none" @click="toggleSort('name')">
                {{ t('from') }}
                <span v-if="sortBy === 'name'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-28 cursor-pointer select-none" @click="toggleSort('category')">
                {{ t('category') }}
                <span v-if="sortBy === 'category'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-24 cursor-pointer select-none" @click="toggleSort('priority')">
                {{ t('priority') }}
                <span v-if="sortBy === 'priority'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-28 cursor-pointer select-none" @click="toggleSort('status')">
                {{ t('status') }}
                <span v-if="sortBy === 'status'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-32 cursor-pointer select-none" @click="toggleSort('assigned_to')">
                {{ t('assigned') }}
                <span v-if="sortBy === 'assigned_to'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-32 cursor-pointer select-none" @click="toggleSort('created_at')">
                {{ t('date') }}
                <span v-if="sortBy === 'created_at'" class="ml-1">{{ sortOrder === 'asc' ? '▲' : '▼' }}</span>
              </th>
              <th class="py-3 px-2 w-16"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="contact in contacts" :key="contact.id" class="hover:bg-gray-50 transition-colors">
              <td class="py-3 px-2">
                <input
                  type="checkbox"
                  :checked="selectedIds.includes(contact.id)"
                  class="h-4 w-4 text-primary-600 rounded border-gray-300"
                  @change="toggleSelect(contact.id)"
                />
              </td>
              <td class="py-3 px-2">
                <a href="#" class="font-mono text-sm text-primary-500 hover:text-primary-600" @click.prevent="viewContact(contact)">
                  {{ contact.reference_number }}
                </a>
              </td>
              <td class="py-3 px-2">
                <a href="#" class="font-medium text-gray-900 hover:text-primary-600" @click.prevent="viewContact(contact)">
                  {{ contact.subject }}
                </a>
              </td>
              <td class="py-3 px-2">
                <div class="text-sm">
                  <div class="text-gray-900">{{ contact.name }}</div>
                  <div class="text-gray-500 text-xs">{{ contact.email }}</div>
                </div>
              </td>
              <td class="py-3 px-2">
                <span class="text-sm text-gray-700 capitalize">{{ categoryLabel(contact.category) }}</span>
              </td>
              <td class="py-3 px-2">
                <span :class="priorityClass(contact.priority)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                  {{ contact.priority }}
                </span>
              </td>
              <td class="py-3 px-2">
                <span :class="statusClass(contact.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                  {{ statusLabel(contact.status) }}
                </span>
              </td>
              <td class="py-3 px-2">
                <span v-if="contact.assigned_to_obj" class="text-sm text-gray-700">
                  {{ contact.assigned_to_obj?.name }}
                </span>
                <span v-else class="text-sm text-gray-400 italic">{{ t('unassigned') }}</span>
              </td>
              <td class="py-3 px-2">
                <span class="text-sm text-gray-600">{{ formatDate(contact.created_at) }}</span>
              </td>
              <td class="py-3 px-2">
                <BaseDropdown>
                  <template #activator>
                    <BaseIcon name="EllipsisHorizontalIcon" class="h-5 text-gray-500 cursor-pointer" />
                  </template>

                  <BaseDropdownItem @click="viewContact(contact)">
                    <BaseIcon name="EyeIcon" class="h-5 mr-3 text-gray-600" />
                    {{ $t('general.view') }}
                  </BaseDropdownItem>

                  <BaseDropdownItem @click="changeStatus(contact, 'in_progress')">
                    <BaseIcon name="PlayCircleIcon" class="h-5 mr-3 text-yellow-600" />
                    {{ t('mark_in_progress') }}
                  </BaseDropdownItem>

                  <BaseDropdownItem @click="changeStatus(contact, 'resolved')">
                    <BaseIcon name="CheckCircleIcon" class="h-5 mr-3 text-green-600" />
                    {{ t('mark_resolved') }}
                  </BaseDropdownItem>

                  <BaseDropdownItem @click="confirmDelete(contact)">
                    <BaseIcon name="TrashIcon" class="h-5 mr-3 text-red-600" />
                    <span class="text-red-600">{{ t('delete') }}</span>
                  </BaseDropdownItem>
                </BaseDropdown>
              </td>
            </tr>
          </tbody>
        </table>
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
          <span v-if="selected" :class="statusClass(selected.status)" class="px-2 py-1 text-xs font-medium rounded-full ml-3">
            {{ statusLabel(selected.status) }}
          </span>
        </div>
      </template>

      <div v-if="selected" class="space-y-4 max-h-[70vh] md:max-h-[80vh] overflow-y-auto">
        <!-- Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
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
            <span class="font-medium ml-1 capitalize">{{ categoryLabel(selected.category) }}</span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('priority') }}:</span>
            <span :class="priorityClass(selected.priority)" class="ml-1 px-2 py-0.5 text-xs font-medium rounded-full capitalize">
              {{ selected.priority }}
            </span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('date') }}:</span>
            <span class="font-medium ml-1">{{ formatDateTime(selected.created_at) }}</span>
          </div>
          <div>
            <span class="text-gray-500">{{ t('assigned') }}:</span>
            <select
              :value="selected.assigned_to_id || ''"
              class="ml-1 text-sm border border-gray-300 rounded px-2 py-0.5"
              @change="assignContact($event.target.value)"
            >
              <option value="">{{ t('unassigned') }}</option>
              <option v-for="admin in adminUsers" :key="admin.id" :value="admin.id">
                {{ admin.name }}
              </option>
            </select>
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

        <!-- Original Message -->
        <div class="p-4 bg-gray-50 rounded-lg">
          <p class="text-xs text-gray-500 mb-1">{{ t('original_message') }}</p>
          <p class="text-gray-800 whitespace-pre-wrap">{{ selected.message }}</p>
        </div>

        <!-- Attachments -->
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

        <!-- Conversation Thread -->
        <div class="border-t pt-4">
          <p class="text-sm font-medium text-gray-700 mb-3">{{ t('conversation') }} ({{ replies.length }})</p>

          <div v-if="isLoadingReplies" class="text-sm text-gray-500 py-4 text-center">Loading...</div>

          <div v-else-if="replies.length === 0 && !selected.admin_reply" class="text-sm text-gray-400 italic py-4 text-center">
            {{ t('no_replies') }}
          </div>

          <div v-else class="space-y-3 max-h-64 overflow-y-auto mb-4">
            <!-- Legacy reply (if no threaded replies exist yet) -->
            <div v-if="replies.length === 0 && selected.admin_reply" class="p-3 bg-blue-50 rounded-lg border border-blue-100">
              <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-blue-700">Admin</span>
                <span class="text-xs text-gray-400">{{ formatDateTime(selected.admin_replied_at) }}</span>
              </div>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ selected.admin_reply }}</p>
            </div>

            <!-- Threaded replies -->
            <div
              v-for="reply in replies"
              :key="reply.id"
              :class="reply.is_internal ? 'bg-amber-50 border-amber-200' : 'bg-blue-50 border-blue-100'"
              class="p-3 rounded-lg border"
            >
              <div class="flex items-center justify-between mb-1">
                <div class="flex items-center space-x-2">
                  <span class="text-xs font-medium" :class="reply.is_internal ? 'text-amber-700' : 'text-blue-700'">
                    {{ reply.user?.name || 'Admin' }}
                  </span>
                  <span v-if="reply.is_internal" class="text-[10px] px-1.5 py-0.5 bg-amber-200 text-amber-800 rounded font-medium">
                    {{ t('internal_note') }}
                  </span>
                </div>
                <span class="text-xs text-gray-400">{{ formatDateTime(reply.created_at) }}</span>
              </div>
              <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ reply.message }}</p>
            </div>
          </div>
        </div>

        <!-- Reply Form -->
        <div class="border-t pt-4">
          <BaseTextarea
            v-model="replyText"
            :placeholder="t('reply_placeholder')"
            rows="3"
          />
          <div class="flex items-center justify-between mt-3">
            <div class="flex items-center space-x-3">
              <div class="flex space-x-2">
                <BaseButton v-if="selected.status !== 'in_progress'" size="sm" variant="primary-outline" @click="changeStatus(selected, 'in_progress')">
                  {{ t('mark_in_progress') }}
                </BaseButton>
                <BaseButton v-if="selected.status !== 'resolved'" size="sm" variant="primary-outline" @click="changeStatus(selected, 'resolved')">
                  {{ t('mark_resolved') }}
                </BaseButton>
              </div>
              <label class="flex items-center space-x-1.5 text-sm text-gray-600 cursor-pointer">
                <input v-model="isInternalNote" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-amber-600" />
                <span>{{ t('internal_note') }}</span>
              </label>
            </div>
            <BaseButton
              variant="primary"
              :loading="isSendingReply"
              :disabled="!replyText.trim() || isSendingReply"
              @click="sendReply"
            >
              {{ isInternalNote ? t('add_internal_note') : t('send_reply') }}
            </BaseButton>
          </div>
        </div>
      </div>
    </BaseModal>

    <!-- Delete Confirmation Modal -->
    <BaseModal :show="showDeleteConfirm" @close="showDeleteConfirm = false">
      <template #header>
        <h3 class="text-lg font-semibold text-red-600">{{ t('delete_confirm') }}</h3>
      </template>
      <p class="text-sm text-gray-600 py-4">{{ t('delete_confirm_msg') }}</p>
      <div class="flex justify-end space-x-3 pt-2">
        <BaseButton variant="primary-outline" @click="showDeleteConfirm = false">
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton variant="danger" :loading="isDeleting" @click="executeDelete">
          {{ t('delete') }}
        </BaseButton>
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
    all_priorities: 'All Priorities',
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
    export_csv: 'Export CSV',
    assigned: 'Assigned',
    unassigned: 'Unassigned',
    delete: 'Delete',
    delete_confirm: 'Confirm Delete',
    delete_confirm_msg: 'Are you sure you want to delete this support request? This action cannot be undone.',
    selected: 'selected',
    conversation: 'Conversation',
    no_replies: 'No replies yet. Be the first to respond.',
    internal_note: 'Internal Note',
    add_internal_note: 'Add Note',
    original_message: 'Original Message',
    cat_technical: 'Technical',
    cat_billing: 'Billing',
    cat_feature: 'Feature Request',
    cat_general: 'General',
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
    all_priorities: 'Сите приоритети',
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
    export_csv: 'Извези CSV',
    assigned: 'Доделено',
    unassigned: 'Недоделено',
    delete: 'Избриши',
    delete_confirm: 'Потврди бришење',
    delete_confirm_msg: 'Дали сте сигурни дека сакате да го избришете ова барање? Ова дејство не може да се поврати.',
    selected: 'избрани',
    conversation: 'Конверзација',
    no_replies: 'Нема одговори. Бидете првиот кој ќе одговори.',
    internal_note: 'Интерна белешка',
    add_internal_note: 'Додади белешка',
    original_message: 'Оригинална порака',
    cat_technical: 'Техничко',
    cat_billing: 'Наплата',
    cat_feature: 'Барање функција',
    cat_general: 'Општо',
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
    all_priorities: 'Te gjitha prioritetet',
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
    export_csv: 'Eksporto CSV',
    assigned: 'Caktuar',
    unassigned: 'Pa caktuar',
    delete: 'Fshi',
    delete_confirm: 'Konfirmo fshirjen',
    delete_confirm_msg: 'Jeni te sigurt qe doni te fshini kete kerkese? Ky veprim nuk mund te kthehet.',
    selected: 'te zgjedhura',
    conversation: 'Biseda',
    no_replies: 'Nuk ka pergjigje. Behuni i pari qe pergjigjet.',
    internal_note: 'Shenim i brendshem',
    add_internal_note: 'Shto shenim',
    original_message: 'Mesazhi origjinal',
    cat_technical: 'Teknike',
    cat_billing: 'Faturim',
    cat_feature: 'Kerkese funksioni',
    cat_general: 'Pergjithshme',
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
    all_priorities: 'Tum oncelikler',
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
    export_csv: 'CSV Indir',
    assigned: 'Atanan',
    unassigned: 'Atanmamis',
    delete: 'Sil',
    delete_confirm: 'Silmeyi Onayla',
    delete_confirm_msg: 'Bu destek talebini silmek istediginizden emin misiniz? Bu islem geri alinamaz.',
    selected: 'secili',
    conversation: 'Konusma',
    no_replies: 'Henuz yanit yok. Ilk yanit veren siz olun.',
    internal_note: 'Dahili not',
    add_internal_note: 'Not ekle',
    original_message: 'Orijinal mesaj',
    cat_technical: 'Teknik',
    cat_billing: 'Faturalama',
    cat_feature: 'Ozellik talebi',
    cat_general: 'Genel',
  },
}

const t = (key) => {
  const lang = locale.value || 'mk'
  return messages[lang]?.[key] || messages['en']?.[key] || key
}

// State
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
const isInternalNote = ref(false)
const replies = ref([])
const isLoadingReplies = ref(false)
const adminUsers = ref([])
const selectedIds = ref([])
const sortBy = ref('created_at')
const sortOrder = ref('desc')
const showDeleteConfirm = ref(false)
const deleteTarget = ref(null)
const isDeleting = ref(false)
const isBulkDelete = ref(false)

const filters = ref({ status: null, category: null, priority: null, search: '' })

const statusOptions = computed(() => [
  { id: 'new', name: t('status_new') },
  { id: 'in_progress', name: t('status_in_progress') },
  { id: 'resolved', name: t('status_resolved') },
])

const categoryOptions = computed(() => [
  { id: 'technical', name: t('cat_technical') },
  { id: 'billing', name: t('cat_billing') },
  { id: 'feature', name: t('cat_feature') },
  { id: 'general', name: t('cat_general') },
])

const priorityOptions = [
  { id: 'low', name: 'Low' },
  { id: 'medium', name: 'Medium' },
  { id: 'high', name: 'High' },
  { id: 'urgent', name: 'Urgent' },
]

const showEmpty = computed(() => !totalCount.value && !isFetching.value)
const totalPages = computed(() => Math.ceil(totalCount.value / 25))
const allSelected = computed(() => contacts.value.length > 0 && contacts.value.every(c => selectedIds.value.includes(c.id)))
const someSelected = computed(() => selectedIds.value.length > 0)

// Data loading
const loadContacts = async () => {
  isFetching.value = true
  try {
    const params = {
      page: currentPage.value,
      per_page: 25,
      sort_by: sortBy.value,
      sort_order: sortOrder.value,
      status: filters.value.status || undefined,
      category: filters.value.category || undefined,
      priority: filters.value.priority || undefined,
      search: filters.value.search || undefined,
    }
    const { data } = await axios.get('/support/admin/contacts', { params })
    contacts.value = (data.data || []).map(c => ({
      ...c,
      // When relation is loaded, assigned_to becomes the user object
      assigned_to_obj: typeof c.assigned_to === 'object' ? c.assigned_to : null,
      assigned_to_id: typeof c.assigned_to === 'object' ? c.assigned_to?.id : c.assigned_to,
    }))
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

const loadAdminUsers = async () => {
  try {
    const { data } = await axios.get('/users?limit=100')
    adminUsers.value = (data.data || data.users || []).filter(u => u.role === 'super admin' || u.is_owner)
  } catch (err) {
    // Fallback: just use current user
    adminUsers.value = []
  }
}

const loadReplies = async (contactId) => {
  isLoadingReplies.value = true
  try {
    const { data } = await axios.get(`/support/admin/contacts/${contactId}/replies`)
    replies.value = data.data || []
  } catch (err) {
    console.error('Error loading replies:', err)
    replies.value = []
  } finally {
    isLoadingReplies.value = false
  }
}

// Actions
const viewContact = (contact) => {
  selected.value = contact
  replyText.value = ''
  isInternalNote.value = false
  showModal.value = true
  loadReplies(contact.id)
}

const sendReply = async () => {
  if (!replyText.value.trim() || !selected.value) return
  isSendingReply.value = true
  try {
    const { data } = await axios.post(`/support/admin/contacts/${selected.value.id}/replies`, {
      message: replyText.value,
      is_internal: isInternalNote.value,
    })
    replies.value.push(data.data)
    replyText.value = ''
    isInternalNote.value = false
    notificationStore.showNotification({ type: 'success', message: t('reply_sent') })
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
    if (selected.value && selected.value.id === contact.id) {
      selected.value.status = status
    }
    loadContacts()
    loadStats()
  } catch (err) {
    console.error('Error updating status:', err)
    notificationStore.showNotification({ type: 'error', message: 'Failed to update status' })
  }
}

const assignContact = async (userId) => {
  if (!selected.value) return
  try {
    const { data } = await axios.post(`/support/admin/contacts/${selected.value.id}/assign`, {
      assigned_to: userId ? parseInt(userId) : null,
    })
    const resp = data.data
    selected.value.assigned_to_id = typeof resp?.assigned_to === 'object' ? resp.assigned_to?.id : resp?.assigned_to
    selected.value.assigned_to_obj = typeof resp?.assigned_to === 'object' ? resp.assigned_to : null
    notificationStore.showNotification({ type: 'success', message: 'Assigned' })
    loadContacts()
  } catch (err) {
    console.error('Error assigning:', err)
  }
}

// Sorting
const toggleSort = (column) => {
  if (sortBy.value === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = column
    sortOrder.value = column === 'created_at' ? 'desc' : 'asc'
  }
  currentPage.value = 1
  loadContacts()
}

// Selection
const toggleSelect = (id) => {
  const idx = selectedIds.value.indexOf(id)
  if (idx > -1) {
    selectedIds.value.splice(idx, 1)
  } else {
    selectedIds.value.push(id)
  }
}

const toggleSelectAll = () => {
  if (allSelected.value) {
    selectedIds.value = []
  } else {
    selectedIds.value = contacts.value.map(c => c.id)
  }
}

// Bulk actions
const bulkStatus = async (status) => {
  try {
    await axios.post('/support/admin/contacts/bulk-status', {
      ids: selectedIds.value,
      status,
    })
    notificationStore.showNotification({ type: 'success', message: `${selectedIds.value.length} contacts updated` })
    selectedIds.value = []
    loadContacts()
    loadStats()
  } catch (err) {
    console.error('Bulk status error:', err)
    notificationStore.showNotification({ type: 'error', message: 'Bulk update failed' })
  }
}

const confirmDelete = (contact) => {
  deleteTarget.value = contact
  isBulkDelete.value = false
  showDeleteConfirm.value = true
}

const confirmBulkDelete = () => {
  deleteTarget.value = null
  isBulkDelete.value = true
  showDeleteConfirm.value = true
}

const executeDelete = async () => {
  isDeleting.value = true
  try {
    if (isBulkDelete.value) {
      await axios.post('/support/admin/contacts/bulk-delete', { ids: selectedIds.value })
      notificationStore.showNotification({ type: 'success', message: `${selectedIds.value.length} contacts deleted` })
      selectedIds.value = []
    } else if (deleteTarget.value) {
      await axios.delete(`/support/admin/contacts/${deleteTarget.value.id}`)
      notificationStore.showNotification({ type: 'success', message: 'Contact deleted' })
    }
    showDeleteConfirm.value = false
    showModal.value = false
    loadContacts()
    loadStats()
  } catch (err) {
    console.error('Delete error:', err)
    notificationStore.showNotification({ type: 'error', message: 'Delete failed' })
  } finally {
    isDeleting.value = false
  }
}

// Export
const exportCsv = () => {
  const params = new URLSearchParams()
  if (filters.value.status) params.set('status', filters.value.status)
  if (filters.value.category) params.set('category', filters.value.category)
  const url = `/api/v1/support/admin/contacts/export?${params.toString()}`
  window.open(url, '_blank')
}

// Filters
const setStatusFilter = (filter) => {
  filters.value.status = filter || null
  currentPage.value = 1
  loadContacts()
}

const clearFilter = () => {
  filters.value = { status: null, category: null, priority: null, search: '' }
  currentPage.value = 1
  loadContacts()
}

// Helpers
const categoryLabel = (cat) => {
  const key = `cat_${cat}`
  return t(key) !== key ? t(key) : cat
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

watch(currentPage, () => {
  selectedIds.value = []
  loadContacts()
})

onMounted(() => {
  loadContacts()
  loadStats()
  loadAdminUsers()
})
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.2s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translate(-50%, 20px);
}
</style>
