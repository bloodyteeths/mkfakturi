<template>
  <BasePage>
    <BasePageHeader :title="$t('cessions_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('cessions_title')" to="/admin/cessions" />
        <BaseBreadcrumbItem :title="cession.cession_number || '...'" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton v-if="cession.status === 'draft'" variant="primary" :loading="confirming" @click="confirmCession">
          {{ $t('status_confirmed') }}
        </BaseButton>
        <BaseButton v-if="cession.status === 'draft'" variant="danger" :loading="cancelling" @click="cancelCession">
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
            <dd>{{ cession.cession_date }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('general.status') }}</dt>
            <dd>
              <span
                class="px-2 py-1 text-xs rounded-full"
                :class="{
                  'bg-yellow-100 text-yellow-800': cession.status === 'draft',
                  'bg-green-100 text-green-800': cession.status === 'confirmed',
                  'bg-red-100 text-red-800': cession.status === 'cancelled',
                }"
              >
                {{ $t('status_' + (cession.status || 'draft')) }}
              </span>
            </dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('transferred_amount') }}</dt>
            <dd class="font-bold">{{ formatAmount(cession.amount) }} МКД</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('original_document') }}</dt>
            <dd>{{ cession.original_document || '-' }}</dd>
          </div>
        </dl>
      </div>

      <div class="p-4 bg-gray-50 rounded-lg">
        <h3 class="font-bold mb-3">{{ $t('general.parties') || 'Страни' }}</h3>
        <dl class="space-y-2 text-sm">
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('cedent') }}</dt>
            <dd>{{ cession.cedent_name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('cessionary') }}</dt>
            <dd>{{ cession.cessionary_name }}</dd>
          </div>
          <div class="flex justify-between">
            <dt class="text-gray-500">{{ $t('cession_debtor') }}</dt>
            <dd>{{ cession.debtor_name }}</dd>
          </div>
        </dl>
      </div>

      <div v-if="cession.description" class="md:col-span-2 p-4 bg-gray-50 rounded-lg">
        <h3 class="font-bold mb-2">{{ $t('general.description') }}</h3>
        <p class="text-sm text-gray-700">{{ cession.description }}</p>
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
const cession = reactive({})

function formatAmount(cents) {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(cents || 0) / 100)
}

async function fetchCession() {
  loading.value = true
  try {
    const res = await window.axios.get(`/cessions/${route.params.id}`)
    Object.assign(cession, res.data)
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

async function confirmCession() {
  confirming.value = true
  try {
    await window.axios.post(`/cessions/${route.params.id}/confirm`)
    await fetchCession()
  } finally {
    confirming.value = false
  }
}

async function cancelCession() {
  cancelling.value = true
  try {
    await window.axios.post(`/cessions/${route.params.id}/cancel`)
    await fetchCession()
  } finally {
    cancelling.value = false
  }
}

function downloadPdf() {
  window.open(`/api/v1/admin/cessions/${route.params.id}/pdf`, '_blank')
}

onMounted(fetchCession)
</script>
// CLAUDE-CHECKPOINT
