<template>
  <BaseCard v-if="hasPendingDocs || isLoading">
    <template #header>
      <div class="flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $t('dashboard.pending_documents') || 'Pending Documents' }}
        </h3>
        <span
          v-if="totalCount > 0"
          class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700"
        >
          {{ totalCount }}
        </span>
      </div>
    </template>

    <!-- Loading State -->
    <div v-if="isLoading" class="animate-pulse space-y-3">
      <div v-for="i in 3" :key="i" class="flex items-center space-x-3">
        <div class="h-5 w-20 bg-gray-200 rounded-full"></div>
        <div class="flex-1 space-y-1">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-3 bg-gray-200 rounded w-1/3"></div>
        </div>
      </div>
    </div>

    <!-- Document List -->
    <div v-else class="space-y-2">
      <router-link
        v-for="doc in combinedDocs"
        :key="`${doc.type}-${doc.id}`"
        :to="getDocRoute(doc)"
        class="flex items-center justify-between p-2.5 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors group"
      >
        <div class="flex items-center min-w-0 flex-1">
          <span
            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium shrink-0 mr-3"
            :class="getBadgeClass(doc.type)"
          >
            {{ getBadgeLabel(doc.type) }}
          </span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-gray-900 truncate group-hover:text-primary-600">
              {{ doc.number }}
            </p>
            <p class="text-xs text-gray-500">
              {{ formatDate(doc.created_at) }}
            </p>
          </div>
        </div>
        <div class="ml-3 text-right shrink-0">
          <span v-if="doc.item_count" class="text-xs text-gray-500">
            {{ doc.item_count }} {{ $t('general.items') || 'ставки' }}
          </span>
          <span v-else-if="doc.amount" class="text-xs font-medium text-gray-700">
            {{ formatMoney(doc.amount) }}
          </span>
        </div>
      </router-link>

      <!-- View All Link -->
      <div class="pt-3 border-t border-gray-200 mt-3">
        <router-link
          to="/admin/stock/documents"
          class="text-sm text-primary-500 hover:text-primary-600 font-medium flex items-center justify-center"
        >
          {{ $t('dashboard.view_all_documents') || 'Прикажи ги сите документи' }}
          <BaseIcon name="ChevronRightIcon" class="h-4 w-4 ml-1" />
        </router-link>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useCompanyStore } from '@/scripts/admin/stores/company'

const { t } = useI18n()
const companyStore = useCompanyStore()

const isLoading = ref(true)
const stockDocuments = ref([])
const nivelacii = ref([])

const currencySymbol = computed(() => {
  return companyStore.selectedCompanyCurrency?.symbol || 'ден'
})

const combinedDocs = computed(() => {
  const mapped = []

  for (const doc of stockDocuments.value) {
    mapped.push({
      id: doc.id,
      type: doc.document_type || doc.type || 'receipt',
      number: doc.document_number || doc.number || '-',
      created_at: doc.created_at,
      item_count: doc.items_count || doc.item_count || null,
      amount: doc.total_amount || doc.amount || null,
    })
  }

  for (const doc of nivelacii.value) {
    mapped.push({
      id: doc.id,
      type: 'nivelacija',
      number: doc.document_number || doc.number || '-',
      created_at: doc.created_at,
      item_count: doc.items_count || doc.item_count || null,
      amount: doc.total_difference || doc.amount || null,
    })
  }

  mapped.sort((a, b) => {
    const dateA = new Date(a.created_at || 0)
    const dateB = new Date(b.created_at || 0)
    return dateB - dateA
  })

  return mapped
})

const totalCount = computed(() => combinedDocs.value.length)

const hasPendingDocs = computed(() => totalCount.value > 0)

const badgeConfig = {
  receipt: {
    class: 'bg-emerald-100 text-emerald-700',
    labelKey: 'stock.receipt',
    fallback: 'Приемница',
  },
  issue: {
    class: 'bg-red-100 text-red-700',
    labelKey: 'stock.issue',
    fallback: 'Издатница',
  },
  transfer: {
    class: 'bg-blue-100 text-blue-700',
    labelKey: 'stock.transfer',
    fallback: 'Преносница',
  },
  nivelacija: {
    class: 'bg-purple-100 text-purple-700',
    labelKey: 'trade.nivelacija',
    fallback: 'Нивелација',
  },
}

function getBadgeClass(type) {
  return badgeConfig[type]?.class || 'bg-gray-100 text-gray-700'
}

function getBadgeLabel(type) {
  const config = badgeConfig[type]
  if (!config) return type
  return t(config.labelKey) || config.fallback
}

function getDocRoute(doc) {
  if (doc.type === 'nivelacija') {
    return `/admin/stock/trade/nivelacii/${doc.id}`
  }
  return `/admin/stock/documents/${doc.id}`
}

function formatDate(dateStr) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  const day = String(date.getDate()).padStart(2, '0')
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const year = date.getFullYear()
  return `${day}.${month}.${year}`
}

function formatMoney(amount) {
  if (!amount) return ''
  const formatted = Math.round(amount).toLocaleString('mk-MK')
  return `${formatted} ${currencySymbol.value}`
}

async function fetchPendingDocuments() {
  isLoading.value = true
  try {
    const [stockRes, nivelRes] = await Promise.allSettled([
      window.axios.get('/stock/documents', {
        params: { status: 'draft', limit: 5 },
      }),
      window.axios.get('/trade/nivelacii', {
        params: { status: 'draft', limit: 5 },
      }),
    ])

    if (stockRes.status === 'fulfilled' && stockRes.value?.data) {
      stockDocuments.value = stockRes.value.data.data || stockRes.value.data || []
    }

    if (nivelRes.status === 'fulfilled' && nivelRes.value?.data) {
      nivelacii.value = nivelRes.value.data.data || nivelRes.value.data || []
    }
  } catch (error) {
    console.error('Failed to fetch pending documents:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchPendingDocuments()
})
// CLAUDE-CHECKPOINT
</script>
