<template>
  <div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">
            Рокови
          </h1>
          <p class="mt-2 text-sm text-gray-600">
            Следење на сите даночни и книговодствени рокови за вашите клиенти
          </p>
        </div>
        <button
          @click="showCreateModal = true"
          class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
          aria-label="Додај нов рок"
        >
          <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Нов рок
        </button>
      </div>

      <!-- KPI Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <!-- Overdue -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-red-500">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Задоцнети</dt>
                  <dd class="text-2xl font-bold text-red-600">{{ summary.overdue_count }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Due This Week -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-orange-500">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Оваа недела</dt>
                  <dd class="text-2xl font-bold text-orange-600">{{ summary.due_this_week }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Due This Month -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Овој месец</dt>
                  <dd class="text-2xl font-bold text-blue-600">{{ summary.due_this_month }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Completed This Month -->
        <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Завршени</dt>
                  <dd class="text-2xl font-bold text-green-600">{{ summary.completed_this_month }}</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label for="filter-type" class="block text-sm font-medium text-gray-700 mb-1">Тип на рок</label>
            <select
              id="filter-type"
              v-model="filters.type"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              aria-label="Филтер по тип"
              @change="loadDeadlines"
            >
              <option value="">Сите типови</option>
              <option value="vat_return">ДДВ пријава</option>
              <option value="mpin">МПИН пријава</option>
              <option value="cit_advance">Аконтација</option>
              <option value="annual_fs">Годишна сметка</option>
              <option value="custom">Прилагодено</option>
            </select>
          </div>
          <div>
            <label for="filter-status" class="block text-sm font-medium text-gray-700 mb-1">Статус</label>
            <select
              id="filter-status"
              v-model="filters.status"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              aria-label="Филтер по статус"
              @change="loadDeadlines"
            >
              <option value="">Сите статуси</option>
              <option value="upcoming">Претстојни</option>
              <option value="due_today">Денес</option>
              <option value="overdue">Задоцнети</option>
              <option value="completed">Завршени</option>
            </select>
          </div>
          <div>
            <label for="filter-company" class="block text-sm font-medium text-gray-700 mb-1">Компанија</label>
            <select
              id="filter-company"
              v-model="filters.company_id"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
              aria-label="Филтер по компанија"
              @change="loadDeadlines"
            >
              <option value="">Сите компании</option>
              <option v-for="company in companies" :key="company.id" :value="company.id">
                {{ company.name }}
              </option>
            </select>
          </div>
          <div>
            <label for="filter-date-from" class="block text-sm font-medium text-gray-700 mb-1">Период</label>
            <div class="flex gap-2">
              <input
                id="filter-date-from"
                v-model="filters.date_from"
                type="date"
                class="w-1/2 px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                aria-label="Датум од"
                @change="loadDeadlines"
              />
              <input
                id="filter-date-to"
                v-model="filters.date_to"
                type="date"
                class="w-1/2 px-2 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                aria-label="Датум до"
                @change="loadDeadlines"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="isLoading" class="space-y-4">
        <div v-for="i in 5" :key="i" class="bg-white shadow rounded-lg p-4 animate-pulse">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
              <div class="h-4 bg-gray-200 rounded w-32"></div>
              <div class="h-4 bg-gray-200 rounded w-24"></div>
              <div class="h-4 bg-gray-200 rounded w-20"></div>
            </div>
            <div class="h-8 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6" role="alert">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Грешка при вчитување</h3>
            <p class="mt-1 text-sm text-red-700">{{ error }}</p>
            <button @click="loadDeadlines" class="mt-2 text-sm font-medium text-red-600 hover:text-red-500">
              Обиди се повторно
            </button>
          </div>
        </div>
      </div>

      <!-- Deadlines Table -->
      <div v-else class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Листа на рокови
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            {{ pagination.total }} рокови вкупно
          </p>
        </div>

        <!-- Empty State -->
        <div v-if="deadlines.length === 0" class="px-4 py-12 text-center">
          <svg class="mx-auto h-16 w-16 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          <h3 class="mt-4 text-lg font-medium text-gray-900">Нема рокови</h3>
          <p class="mt-2 text-sm text-gray-500">
            {{ hasActiveFilters ? 'Нема резултати за дадените филтри.' : 'Сеуште нема креирани рокови.' }}
          </p>
          <button
            v-if="hasActiveFilters"
            @click="clearFilters"
            class="mt-4 inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
          >
            Исчисти филтри
          </button>
        </div>

        <!-- Table -->
        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Компанија</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Рок</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Тип</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Краен рок</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Денови</th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Акции</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="deadline in deadlines" :key="deadline.id" :class="getRowClass(deadline)">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ deadline.company?.name || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm font-medium text-gray-900">{{ deadline.title_mk || deadline.title }}</div>
                  <div v-if="deadline.description" class="text-xs text-gray-500 truncate max-w-xs">{{ deadline.description }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getTypeBadgeClass(deadline.deadline_type)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ deadline.type_label || getTypeLabel(deadline.deadline_type) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">{{ formatDate(deadline.due_date) }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getStatusBadgeClass(deadline.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ getStatusLabel(deadline.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="getDaysClass(deadline.days_remaining, deadline.status)" class="text-sm font-medium">
                    {{ deadline.status === 'completed' ? '--' : formatDaysRemaining(deadline.days_remaining) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      v-if="deadline.status !== 'completed'"
                      @click="completeDeadline(deadline)"
                      :disabled="completingId === deadline.id"
                      class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-700 rounded-md text-xs font-medium hover:bg-green-200 transition-colors disabled:opacity-50"
                      :aria-label="`Заврши ${deadline.title_mk || deadline.title}`"
                    >
                      <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      {{ completingId === deadline.id ? '...' : 'Заврши' }}
                    </button>
                    <button
                      v-if="canDelete(deadline)"
                      @click="openDeleteModal(deadline)"
                      class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-700 rounded-md text-xs font-medium hover:bg-red-200 transition-colors"
                      :aria-label="`Избриши ${deadline.title_mk || deadline.title}`"
                    >
                      <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Претходна
            </button>
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Следна
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Прикажани <span class="font-medium">{{ pagination.from }}</span> до <span class="font-medium">{{ pagination.to }}</span> од
                <span class="font-medium">{{ pagination.total }}</span> рокови
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Пагинација">
                <button
                  @click="goToPage(pagination.current_page - 1)"
                  :disabled="pagination.current_page === 1"
                  class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  aria-label="Претходна страна"
                >
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
                <button
                  @click="goToPage(pagination.current_page + 1)"
                  :disabled="pagination.current_page === pagination.last_page"
                  class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                  aria-label="Следна страна"
                >
                  <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Deadline Modal -->
      <div v-if="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="create-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeCreateModal"></div>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form @submit.prevent="createDeadline">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 id="create-modal-title" class="text-lg leading-6 font-medium text-gray-900 mb-4">
                  Нов рок
                </h3>

                <div class="space-y-4">
                  <div>
                    <label for="new-company" class="block text-sm font-medium text-gray-700 mb-1">Компанија *</label>
                    <select
                      id="new-company"
                      v-model="newDeadline.company_id"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">Избери компанија</option>
                      <option v-for="company in companies" :key="company.id" :value="company.id">
                        {{ company.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label for="new-title" class="block text-sm font-medium text-gray-700 mb-1">Наслов *</label>
                    <input
                      id="new-title"
                      v-model="newDeadline.title"
                      type="text"
                      required
                      maxlength="200"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Наслов на рокот"
                    />
                  </div>

                  <div>
                    <label for="new-title-mk" class="block text-sm font-medium text-gray-700 mb-1">Наслов (МК)</label>
                    <input
                      id="new-title-mk"
                      v-model="newDeadline.title_mk"
                      type="text"
                      maxlength="200"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Наслов на македонски"
                    />
                  </div>

                  <div>
                    <label for="new-type" class="block text-sm font-medium text-gray-700 mb-1">Тип</label>
                    <select
                      id="new-type"
                      v-model="newDeadline.deadline_type"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="custom">Прилагодено</option>
                      <option value="vat_return">ДДВ пријава</option>
                      <option value="mpin">МПИН пријава</option>
                      <option value="cit_advance">Аконтација</option>
                      <option value="annual_fs">Годишна сметка</option>
                    </select>
                  </div>

                  <div>
                    <label for="new-due-date" class="block text-sm font-medium text-gray-700 mb-1">Краен рок *</label>
                    <input
                      id="new-due-date"
                      v-model="newDeadline.due_date"
                      type="date"
                      required
                      :min="todayString"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                    />
                  </div>

                  <div>
                    <label for="new-description" class="block text-sm font-medium text-gray-700 mb-1">Опис</label>
                    <textarea
                      id="new-description"
                      v-model="newDeadline.description"
                      rows="3"
                      maxlength="2000"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                      placeholder="Опис на рокот (опционално)"
                    ></textarea>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                  type="submit"
                  :disabled="isCreating"
                  class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  {{ isCreating ? 'Се креира...' : 'Креирај' }}
                </button>
                <button
                  type="button"
                  @click="closeCreateModal"
                  :disabled="isCreating"
                  class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                >
                  Откажи
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="delete-modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeDeleteModal"></div>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                  <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                  </svg>
                </div>
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                  <h3 id="delete-modal-title" class="text-lg leading-6 font-medium text-gray-900">
                    Избриши рок
                  </h3>
                  <div class="mt-2">
                    <p class="text-sm text-gray-500">
                      Дали сте сигурни дека сакате да го избришете рокот "{{ deadlineToDelete?.title_mk || deadlineToDelete?.title }}"?
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
              <button
                type="button"
                :disabled="isDeleting"
                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                @click="confirmDelete"
              >
                {{ isDeleting ? 'Се брише...' : 'Избриши' }}
              </button>
              <button
                type="button"
                :disabled="isDeleting"
                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                @click="closeDeleteModal"
              >
                Откажи
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/scripts/stores/notification'

const notificationStore = useNotificationStore()

// State
const deadlines = ref([])
const companies = ref([])
const isLoading = ref(true)
const error = ref(null)
const completingId = ref(null)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const deadlineToDelete = ref(null)
const isCreating = ref(false)
const isDeleting = ref(false)

// Summary KPIs
const summary = reactive({
  overdue_count: 0,
  due_this_week: 0,
  due_this_month: 0,
  completed_this_month: 0
})

// Filters
const filters = reactive({
  type: '',
  status: '',
  company_id: '',
  date_from: '',
  date_to: ''
})

// Pagination
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 25,
  total: 0,
  from: 0,
  to: 0
})

// New deadline form
const newDeadline = reactive({
  company_id: '',
  title: '',
  title_mk: '',
  deadline_type: 'custom',
  due_date: '',
  description: ''
})

// AbortController for cancelling requests
let abortController = null

// Computed
const todayString = computed(() => {
  return new Date().toISOString().split('T')[0]
})

const hasActiveFilters = computed(() => {
  return filters.type || filters.status || filters.company_id || filters.date_from || filters.date_to
})

// Methods
const loadDeadlines = async () => {
  if (abortController) {
    abortController.abort()
  }
  abortController = new AbortController()

  isLoading.value = true
  error.value = null

  try {
    const params = { page: pagination.current_page, per_page: pagination.per_page }

    if (filters.type) params.type = filters.type
    if (filters.status) params.status = filters.status
    if (filters.company_id) params.company_id = filters.company_id
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to

    const { data } = await window.axios.get('/partner/deadlines', {
      params,
      signal: abortController.signal
    })

    deadlines.value = data.data || []
    pagination.current_page = data.current_page || 1
    pagination.last_page = data.last_page || 1
    pagination.per_page = data.per_page || 25
    pagination.total = data.total || 0
    pagination.from = data.from || 0
    pagination.to = data.to || 0
  } catch (err) {
    if (err?.name === 'AbortError' || err?.code === 'ERR_CANCELED') return
    error.value = err?.response?.data?.error || 'Не можеше да се вчитаат роковите.'
  } finally {
    isLoading.value = false
  }
}

const loadSummary = async () => {
  try {
    const { data } = await window.axios.get('/partner/deadlines/summary')
    Object.assign(summary, data.data || {})
  } catch (err) {
    // Summary is non-critical, silently fail
    console.error('Failed to load deadline summary:', err)
  }
}

const loadCompanies = async () => {
  try {
    const { data } = await window.axios.get('/partner/clients', {
      params: { per_page: 200 }
    })
    // Extract unique companies from client list
    companies.value = (data.data || []).map(c => ({
      id: c.id,
      name: c.name
    }))
  } catch (err) {
    console.error('Failed to load companies:', err)
  }
}

const completeDeadline = async (deadline) => {
  if (completingId.value) return

  completingId.value = deadline.id

  try {
    const { data } = await window.axios.post(`/partner/deadlines/${deadline.id}/complete`)

    // Update the deadline in the list
    const index = deadlines.value.findIndex(d => d.id === deadline.id)
    if (index !== -1) {
      deadlines.value[index] = data.data
    }

    notificationStore.showNotification({
      type: 'success',
      message: `Рокот "${deadline.title_mk || deadline.title}" е означен како завршен.`
    })

    // Refresh summary
    await loadSummary()
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || 'Не можеше да се заврши рокот.'
    })
  } finally {
    completingId.value = null
  }
}

const createDeadline = async () => {
  isCreating.value = true

  try {
    const payload = { ...newDeadline }
    if (!payload.title_mk) delete payload.title_mk
    if (!payload.description) delete payload.description

    await window.axios.post('/partner/deadlines', payload)

    notificationStore.showNotification({
      type: 'success',
      message: 'Рокот е успешно креиран.'
    })

    closeCreateModal()
    await Promise.all([loadDeadlines(), loadSummary()])
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || err?.response?.data?.message || 'Не можеше да се креира рокот.'
    })
  } finally {
    isCreating.value = false
  }
}

const canDelete = (deadline) => {
  // Only custom non-recurring deadlines can be deleted
  if (deadline.is_recurring && deadline.deadline_type !== 'custom') return false
  return true
}

const openDeleteModal = (deadline) => {
  deadlineToDelete.value = deadline
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  deadlineToDelete.value = null
}

const confirmDelete = async () => {
  if (!deadlineToDelete.value) return

  isDeleting.value = true

  try {
    await window.axios.delete(`/partner/deadlines/${deadlineToDelete.value.id}`)

    notificationStore.showNotification({
      type: 'success',
      message: 'Рокот е успешно избришан.'
    })

    closeDeleteModal()
    await Promise.all([loadDeadlines(), loadSummary()])
  } catch (err) {
    notificationStore.showNotification({
      type: 'error',
      message: err?.response?.data?.error || 'Не можеше да се избрише рокот.'
    })
  } finally {
    isDeleting.value = false
  }
}

const closeCreateModal = () => {
  showCreateModal.value = false
  newDeadline.company_id = ''
  newDeadline.title = ''
  newDeadline.title_mk = ''
  newDeadline.deadline_type = 'custom'
  newDeadline.due_date = ''
  newDeadline.description = ''
}

const clearFilters = () => {
  filters.type = ''
  filters.status = ''
  filters.company_id = ''
  filters.date_from = ''
  filters.date_to = ''
  pagination.current_page = 1
  loadDeadlines()
}

const goToPage = (page) => {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  loadDeadlines()
}

// Display helpers
const getTypeLabel = (type) => {
  const labels = {
    vat_return: 'ДДВ',
    mpin: 'МПИН',
    cit_advance: 'Аконтација',
    annual_fs: 'Год. сметка',
    custom: 'Прилагодено'
  }
  return labels[type] || type
}

const getTypeBadgeClass = (type) => {
  const classes = {
    vat_return: 'bg-purple-100 text-purple-800',
    mpin: 'bg-indigo-100 text-indigo-800',
    cit_advance: 'bg-teal-100 text-teal-800',
    annual_fs: 'bg-amber-100 text-amber-800',
    custom: 'bg-gray-100 text-gray-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    upcoming: 'Претстоен',
    due_today: 'Денес',
    overdue: 'Задоцнет',
    completed: 'Завршен'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    upcoming: 'bg-blue-100 text-blue-800',
    due_today: 'bg-orange-100 text-orange-800',
    overdue: 'bg-red-100 text-red-800',
    completed: 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getRowClass = (deadline) => {
  if (deadline.status === 'overdue') return 'bg-red-50'
  if (deadline.status === 'due_today') return 'bg-orange-50'
  if (deadline.status === 'completed') return 'bg-gray-50'
  return ''
}

const getDaysClass = (days, status) => {
  if (status === 'completed') return 'text-gray-400'
  if (days < 0) return 'text-red-600'
  if (days === 0) return 'text-orange-600'
  if (days <= 3) return 'text-orange-500'
  if (days <= 7) return 'text-yellow-600'
  return 'text-gray-600'
}

const formatDaysRemaining = (days) => {
  if (days < 0) return `${Math.abs(days)} ден${Math.abs(days) === 1 ? '' : 'а'} задоцнет`
  if (days === 0) return 'Денес'
  return `${days} ден${days === 1 ? '' : 'а'}`
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  try {
    return new Date(dateString).toLocaleDateString('mk-MK', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  } catch {
    return dateString
  }
}

// Cleanup on unmount
onUnmounted(() => {
  if (abortController) {
    abortController.abort()
  }
})

// Lifecycle
onMounted(async () => {
  await Promise.all([
    loadDeadlines(),
    loadSummary(),
    loadCompanies()
  ])
})
</script>
// CLAUDE-CHECKPOINT
