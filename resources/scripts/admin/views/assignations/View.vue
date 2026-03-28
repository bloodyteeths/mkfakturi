<template>
  <BasePage>
    <BasePageHeader :title="$t('assignations_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('assignations_title')" to="/admin/assignations" />
        <BaseBreadcrumbItem :title="assignation.assignation_number || '...'" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton v-if="assignation.status === 'draft'" variant="primary" :loading="confirming" @click="confirmAssignation">
          {{ $t('status_confirmed') }}
        </BaseButton>
        <BaseButton v-if="assignation.status === 'draft'" variant="danger" :loading="cancelling" @click="cancelAssignation">
          {{ $t('status_cancelled') }}
        </BaseButton>
        <BaseButton variant="primary-outline" @click="downloadPdf">
          {{ $t('download_pdf') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <div v-if="loading" class="mt-8 text-center text-gray-500">
      <BaseIcon name="ArrowPathIcon" class="animate-spin h-6 w-6 mx-auto" />
    </div>

    <div v-else class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="p-4 bg-gray-50 rounded-lg">
        <h3 class="font-bold mb-3">{{ $t('general.details') }}</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('payments.date') }}</dt>
            <dd>{{ assignation.assignation_date }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('general.status') }}</dt>
            <dd>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="{
                  'bg-yellow-100 text-yellow-800': assignation.status === 'draft',
                  'bg-green-100 text-green-800': assignation.status === 'confirmed',
                  'bg-red-100 text-red-800': assignation.status === 'cancelled',
                }"
              >
                {{ $t('status_' + (assignation.status || 'draft')) }}
              </span>
            </dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('assignation_amount') }}</dt>
            <dd class="font-bold">{{ formatAmount(assignation.amount) }} МКД</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('original_document') }}</dt>
            <dd>{{ assignation.original_document || '-' }}</dd>
          </div>
        </dl>
      </div>

      <div class="p-4 bg-gray-50 rounded-lg">
        <h3 class="font-bold mb-3">{{ $t('general.parties') || 'Страни' }}</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('assignor') }}</dt>
            <dd>{{ assignation.assignor_name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('assignee') }}</dt>
            <dd>{{ assignation.assignee_name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('assigned_debtor') }}</dt>
            <dd>{{ assignation.debtor_name }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="assignation.description" class="md:col-span-2 p-4 bg-gray-50 rounded-lg">
        <h3 class="font-bold mb-2">{{ $t('general.description') }}</h3>
        <p class="text-sm text-gray-700">{{ assignation.description }}</p>
      </div>
    </div>
  </BasePage>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const loading = ref(true)
const confirming = ref(false)
const cancelling = ref(false)
const assignation = reactive({})

function formatAmount(cents) {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(cents || 0) / 100)
}

async function fetchAssignation() {
  loading.value = true
  try {
    const res = await window.axios.get(`/assignations/${route.params.id}`)
    Object.assign(assignation, res.data)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function confirmAssignation() {
  confirming.value = true
  try {
    await window.axios.post(`/assignations/${route.params.id}/confirm`)
    await fetchAssignation()
  } finally {
    confirming.value = false
  }
}

async function cancelAssignation() {
  cancelling.value = true
  try {
    await window.axios.post(`/assignations/${route.params.id}/cancel`)
    await fetchAssignation()
  } finally {
    cancelling.value = false
  }
}

function downloadPdf() {
  window.open(`/api/v1/assignations/${route.params.id}/pdf`, '_blank')
}

onMounted(fetchAssignation)
</script>
// CLAUDE-CHECKPOINT
