<template>
  <BasePage>
    <BasePageHeader :title="pageTitle">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('projects.title')" to="/admin/projects" />
        <BaseBreadcrumbItem
          :title="project?.name || $t('projects.view_project')"
          to="#"
          active
        />
      </BaseBreadcrumb>

      <template #actions>
        <router-link
          v-if="userStore.hasAbilities(abilities.EDIT_PROJECT)"
          :to="`/admin/projects/${route.params.id}/edit`"
        >
          <BaseButton
            class="mr-3"
            variant="primary-outline"
            :content-loading="isLoading"
          >
            {{ $t('general.edit') }}
          </BaseButton>
        </router-link>

        <ProjectDropdown
          v-if="hasAtleastOneAbility()"
          :class="{
            'ml-3': isLoading,
          }"
          :row="project || {}"
          :load-data="refreshData"
        />
      </template>
    </BasePageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Project Info Card -->
      <div class="lg:col-span-2">
        <BaseCard>
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('projects.project_info') }}</h3>
          </template>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('projects.name') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ project?.name || '-' }}</dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('projects.code') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">{{ project?.code || '-' }}</dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('projects.customer') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <router-link
                  v-if="project?.customer"
                  :to="`/admin/customers/${project.customer.id}/view`"
                  class="text-primary-500 hover:text-primary-700"
                >
                  {{ project.customer.name }}
                </router-link>
                <span v-else>-</span>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('projects.status') }}</dt>
              <dd class="mt-1">
                <BaseBadge
                  v-if="project?.status"
                  :bg-color="getStatusColor(project.status)"
                  :content-loading="false"
                >
                  {{ $t(`projects.statuses.${project.status}`) }}
                </BaseBadge>
                <span v-else>-</span>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('projects.budget') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <BaseFormatMoney
                  v-if="project?.budget_amount"
                  :amount="project.budget_amount"
                  :currency="project.currency"
                />
                <span v-else>-</span>
              </dd>
            </div>

            <div>
              <dt class="text-sm font-medium text-gray-500">{{ $t('projects.dates') }}</dt>
              <dd class="mt-1 text-sm text-gray-900">
                <span v-if="project?.formatted_start_date">{{ project.formatted_start_date }}</span>
                <span v-if="project?.formatted_start_date && project?.formatted_end_date"> - </span>
                <span v-if="project?.formatted_end_date">{{ project.formatted_end_date }}</span>
                <span v-if="!project?.formatted_start_date && !project?.formatted_end_date">-</span>
              </dd>
            </div>
          </div>

          <div v-if="project?.description" class="mt-4">
            <dt class="text-sm font-medium text-gray-500">{{ $t('projects.description') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ project.description }}</dd>
          </div>

          <div v-if="project?.notes" class="mt-4">
            <dt class="text-sm font-medium text-gray-500">{{ $t('projects.notes') }}</dt>
            <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap">{{ project.notes }}</dd>
          </div>
        </BaseCard>
      </div>

      <!-- Financial Summary Card -->
      <div class="lg:col-span-1">
        <BaseCard>
          <template #header>
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">{{ $t('projects.financial_summary') }}</h3>
              <BaseButton
                variant="primary-outline"
                size="sm"
                @click="toggleDateFilter"
              >
                <BaseIcon name="FunnelIcon" class="h-4 w-4" />
              </BaseButton>
            </div>
          </template>

          <!-- Date Range Filter -->
          <div v-if="showDateFilter" class="mb-4 p-3 bg-gray-50 rounded-lg space-y-3">
            <div class="grid grid-cols-2 gap-2">
              <BaseInputGroup :label="$t('general.from_date')">
                <BaseDatePicker
                  v-model="filters.from_date"
                  :calendar-button="true"
                />
              </BaseInputGroup>
              <BaseInputGroup :label="$t('general.to_date')">
                <BaseDatePicker
                  v-model="filters.to_date"
                  :calendar-button="true"
                />
              </BaseInputGroup>
            </div>
            <div class="flex justify-end space-x-2">
              <BaseButton variant="secondary" size="sm" @click="clearDateFilter">
                {{ $t('general.clear') }}
              </BaseButton>
              <BaseButton variant="primary" size="sm" @click="applyDateFilter">
                {{ $t('general.apply') }}
              </BaseButton>
            </div>
            <div v-if="filters.applied_from && filters.applied_to" class="text-xs text-gray-500 text-center">
              {{ $t('projects.showing_data_for') }}: {{ filters.applied_from }} - {{ filters.applied_to }}
            </div>
          </div>

          <div v-if="isLoadingSummary" class="flex justify-center py-8">
            <BaseContentPlaceholders>
              <BaseContentPlaceholdersBox class="w-full h-32" />
            </BaseContentPlaceholders>
          </div>

          <div v-else class="space-y-4">
            <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
              <span class="text-sm font-medium text-green-700">{{ $t('projects.total_invoiced') }}</span>
              <BaseFormatMoney
                v-if="summary.total_invoiced !== undefined"
                :amount="summary.total_invoiced"
                :currency="project?.currency"
                class="text-green-700 font-semibold"
              />
              <span v-else class="text-green-700 font-semibold">0</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
              <span class="text-sm font-medium text-red-700">{{ $t('projects.total_expenses') }}</span>
              <BaseFormatMoney
                v-if="summary.total_expenses !== undefined"
                :amount="summary.total_expenses"
                :currency="project?.currency"
                class="text-red-700 font-semibold"
              />
              <span v-else class="text-red-700 font-semibold">0</span>
            </div>

            <div class="flex justify-between items-center p-3 bg-blue-50 rounded-lg">
              <span class="text-sm font-medium text-blue-700">{{ $t('projects.total_payments') }}</span>
              <BaseFormatMoney
                v-if="summary.total_payments !== undefined"
                :amount="summary.total_payments"
                :currency="project?.currency"
                class="text-blue-700 font-semibold"
              />
              <span v-else class="text-blue-700 font-semibold">0</span>
            </div>

            <hr class="my-2" />

            <div class="flex justify-between items-center p-3 bg-gray-100 rounded-lg">
              <span class="text-sm font-medium text-gray-700">{{ $t('projects.net_result') }}</span>
              <BaseFormatMoney
                v-if="summary.net_result !== undefined"
                :amount="summary.net_result"
                :currency="project?.currency"
                :class="[
                  'font-bold',
                  summary.net_result >= 0 ? 'text-green-700' : 'text-red-700'
                ]"
              />
              <span v-else class="text-gray-700 font-bold">0</span>
            </div>

            <!-- Budget Progress (if budget is set) -->
            <div v-if="summary.budget_amount" class="mt-4">
              <div class="flex justify-between items-center mb-1">
                <span class="text-sm text-gray-600">{{ $t('projects.budget_used') }}</span>
                <span class="text-sm font-medium">{{ summary.budget_used_percentage || 0 }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :class="budgetProgressColor"
                  :style="{ width: `${Math.min(summary.budget_used_percentage || 0, 100)}%` }"
                ></div>
              </div>
              <div class="flex justify-between items-center mt-1 text-xs text-gray-500">
                <span>{{ $t('projects.budget_remaining') }}:</span>
                <BaseFormatMoney
                  v-if="summary.budget_remaining !== undefined"
                  :amount="summary.budget_remaining"
                  :currency="project?.currency"
                />
              </div>
            </div>
          </div>
        </BaseCard>

        <!-- Document Counts Card -->
        <BaseCard class="mt-6">
          <template #header>
            <h3 class="text-lg font-medium text-gray-900">{{ $t('projects.linked_documents') }}</h3>
          </template>

          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ $t('projects.invoices') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ summary.invoice_count || 0 }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ $t('projects.expenses') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ summary.expense_count || 0 }}</span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">{{ $t('projects.payments') }}</span>
              <span class="text-sm font-medium text-gray-900">{{ summary.payment_count || 0 }}</span>
            </div>
          </div>
        </BaseCard>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useProjectStore } from '@/scripts/admin/stores/project'
import { useUserStore } from '@/scripts/admin/stores/user'
import ProjectDropdown from '@/scripts/admin/components/dropdowns/ProjectIndexDropdown.vue'
import abilities from '@/scripts/admin/stub/abilities'

const route = useRoute()
const router = useRouter()
const projectStore = useProjectStore()
const userStore = useUserStore()

const isLoadingSummary = ref(true)
const showDateFilter = ref(false)

const filters = reactive({
  from_date: null,
  to_date: null,
  applied_from: null,
  applied_to: null,
})

const summary = ref({
  total_invoiced: 0,
  total_expenses: 0,
  total_payments: 0,
  net_result: 0,
  invoice_count: 0,
  expense_count: 0,
  payment_count: 0,
  budget_amount: null,
  budget_remaining: null,
  budget_used_percentage: null,
})

const project = computed(() => projectStore.currentProject)

const pageTitle = computed(() => {
  return project.value ? project.value.name : ''
})

const isLoading = computed(() => {
  return projectStore.isFetching || false
})

const budgetProgressColor = computed(() => {
  const percentage = summary.value.budget_used_percentage || 0
  if (percentage >= 100) return 'bg-red-500'
  if (percentage >= 80) return 'bg-yellow-500'
  return 'bg-green-500'
})

function getStatusColor(status) {
  const colors = {
    open: '#3B82F6', // blue
    in_progress: '#F59E0B', // amber
    completed: '#10B981', // green
    on_hold: '#6B7280', // gray
    cancelled: '#EF4444', // red
  }
  return colors[status] || '#6B7280'
}

function hasAtleastOneAbility() {
  return userStore.hasAbilities([
    abilities.DELETE_PROJECT,
    abilities.EDIT_PROJECT,
  ])
}

function refreshData() {
  router.push('/admin/projects')
}

function toggleDateFilter() {
  showDateFilter.value = !showDateFilter.value
}

async function loadSummary(params = {}) {
  isLoadingSummary.value = true
  try {
    const summaryResponse = await projectStore.fetchProjectSummary(route.params.id, params)
    if (summaryResponse?.data) {
      summary.value = summaryResponse.data
    }
  } catch (error) {
    console.error('Error fetching project summary:', error)
  } finally {
    isLoadingSummary.value = false
  }
}

function applyDateFilter() {
  const params = {}
  if (filters.from_date && filters.to_date) {
    params.from_date = filters.from_date
    params.to_date = filters.to_date
    filters.applied_from = filters.from_date
    filters.applied_to = filters.to_date
  }
  loadSummary(params)
}

function clearDateFilter() {
  filters.from_date = null
  filters.to_date = null
  filters.applied_from = null
  filters.applied_to = null
  loadSummary()
}

onMounted(async () => {
  // Fetch project details
  await projectStore.fetchProject(route.params.id)

  // Fetch project summary
  await loadSummary()
})
</script>
// CLAUDE-CHECKPOINT
