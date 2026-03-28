<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.wac_audit_title') + ' #' + auditId">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('stock.wac_audit')" to="/admin/stock/wac-audit" />
        <BaseBreadcrumbItem :title="'#' + auditId" to="#" active />
      </BaseBreadcrumb>
    </BasePageHeader>

    <StockTabNavigation />

    <div v-if="isLoading" class="p-8 text-center text-gray-500">
      <BaseIcon name="ArrowPathIcon" class="w-6 h-6 animate-spin inline-block mr-2" />
      Loading...
    </div>

    <template v-else-if="audit">
      <!-- Summary Row -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('general.status') }}</div>
          <span
            class="inline-flex items-center mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
            :class="statusBadgeClass(audit.status)"
          >
            {{ audit.status }}
          </span>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('stock.movements_checked') }}</div>
          <div class="text-2xl font-bold mt-1">{{ audit.total_movements_checked }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('stock.discrepancies') }}</div>
          <div class="text-2xl font-bold mt-1" :class="audit.discrepancies_found > 0 ? 'text-red-600' : 'text-green-600'">
            {{ audit.discrepancies_found }}
          </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
          <div class="text-sm text-gray-500">{{ $t('stock.audit_scope') }}</div>
          <div class="text-sm font-medium mt-1">
            {{ audit.item_name || $t('stock.all_items') }}
            <span v-if="audit.warehouse_name"> / {{ audit.warehouse_name }}</span>
          </div>
        </div>
      </div>

      <!-- AI Analysis Card -->
      <div v-if="audit.ai_analysis" class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 class="text-sm font-semibold text-blue-800 mb-2">
          <BaseIcon name="SparklesIcon" class="w-4 h-4 inline-block mr-1" />
          {{ $t('stock.ai_analysis') }}
        </h3>
        <div v-for="(analysis, key) in audit.ai_analysis" :key="key" class="mb-3 last:mb-0">
          <div v-if="analysis.root_cause" class="text-sm text-blue-900">
            <span class="font-medium">{{ $t('stock.root_cause') }}:</span>
            {{ analysis.root_cause.explanation }}
            <span class="inline-flex items-center ml-2 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">
              {{ errorCategoryLabel(analysis.root_cause.category) }}
            </span>
          </div>
          <div v-if="analysis.cascade_impact" class="text-xs text-blue-700 mt-1">
            {{ analysis.cascade_impact.affected_movements }} movements affected,
            value drift: {{ formatCurrency(analysis.cascade_impact.total_value_drift_cents) }}
          </div>
          <div v-if="analysis.confidence !== undefined" class="mt-2 flex items-center gap-2">
            <span class="text-xs font-medium text-gray-500">{{ $t('stock.ai_confidence') }}:</span>
            <span :class="confidenceClass(analysis.confidence)">
              {{ typeof analysis.confidence === 'number' ? (analysis.confidence * 100).toFixed(0) + '%' : analysis.confidence }}
            </span>
          </div>
          <div v-if="analysis.error" class="text-sm text-red-600">{{ analysis.error }}</div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center space-x-3 mb-6">
        <BaseButton
          v-if="audit.has_discrepancies && !audit.ai_analysis"
          variant="primary-outline"
          :loading="isAnalyzing"
          @click="triggerAnalysis"
        >
          <template #left="slotProps">
            <BaseIcon name="SparklesIcon" :class="slotProps.class" />
          </template>
          {{ $t('stock.trigger_ai_analysis') }}
        </BaseButton>

        <BaseButton
          v-if="audit.has_discrepancies && !proposal"
          variant="primary"
          :loading="isGenerating"
          @click="generateProposal"
        >
          {{ $t('stock.generate_correction') }}
        </BaseButton>
      </div>

      <!-- Correction Proposal -->
      <div v-if="proposal" class="bg-white rounded-lg shadow mb-6">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-sm font-semibold">{{ $t('stock.correction_proposal') }}</h3>
          <span
            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
            :class="proposalStatusClass(proposal.status)"
          >
            {{ proposal.status }}
          </span>
        </div>
        <div class="p-4">
          <p class="text-sm text-gray-600 mb-3">{{ proposal.description }}</p>

          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <span class="text-xs text-gray-500">{{ $t('stock.quantity_drift') }}</span>
              <div class="font-medium">{{ Number(proposal.net_quantity_adjustment).toFixed(4) }}</div>
            </div>
            <div>
              <span class="text-xs text-gray-500">{{ $t('stock.value_drift') }}</span>
              <div class="font-medium">{{ formatCurrency(proposal.net_value_adjustment) }}</div>
            </div>
          </div>

          <!-- Correction Entries -->
          <div v-if="proposal.correction_entries?.length" class="mb-4">
            <table class="w-full text-sm">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-3 py-2 text-left text-xs">Item</th>
                  <th class="px-3 py-2 text-left text-xs">Warehouse</th>
                  <th class="px-3 py-2 text-right text-xs">Qty Adj</th>
                  <th class="px-3 py-2 text-right text-xs">Value Adj</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(entry, idx) in proposal.correction_entries" :key="idx" class="border-t">
                  <td class="px-3 py-2">#{{ entry.item_id }}</td>
                  <td class="px-3 py-2">#{{ entry.warehouse_id }}</td>
                  <td class="px-3 py-2 text-right">{{ Number(entry.quantity_adjustment || 0).toFixed(4) }}</td>
                  <td class="px-3 py-2 text-right">{{ formatCurrency(entry.value_adjustment || 0) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Approve / Reject -->
          <div v-if="proposal.is_usable" class="flex items-center space-x-3">
            <BaseButton variant="primary" :loading="isApproving" @click="approve">
              {{ $t('stock.approve_correction') }}
            </BaseButton>
            <BaseButton variant="danger-outline" :loading="isRejecting" @click="reject">
              {{ $t('stock.reject_correction') }}
            </BaseButton>
          </div>

          <div v-else-if="proposal.status === 'applied'" class="text-sm text-green-600 font-medium">
            {{ $t('stock.correction_applied') }} — {{ proposal.applied_at }}
          </div>
          <div v-else-if="proposal.status === 'rejected'" class="text-sm text-red-600 font-medium">
            {{ $t('stock.correction_rejected') }}
            <span v-if="proposal.review_notes" class="text-gray-500"> — {{ proposal.review_notes }}</span>
          </div>
        </div>
      </div>

      <!-- Discrepancies Table -->
      <div v-if="discrepancies.length > 0" class="bg-white rounded-lg shadow">
        <div class="px-4 py-3 border-b border-gray-200">
          <h3 class="text-sm font-semibold">{{ $t('stock.discrepancies') }} ({{ discrepancies.length }})</h3>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('stock.chain_position') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Source</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Qty</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.stored_value') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.expected_value') }}</th>
                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.value_drift') }}</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('stock.root_cause') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="d in discrepancies"
                :key="d.id"
                :class="rowClass(d)"
              >
                <td class="px-3 py-2">#{{ d.chain_position }}</td>
                <td class="px-3 py-2">{{ d.movement?.movement_date }}</td>
                <td class="px-3 py-2">{{ d.movement?.source_type_label }}</td>
                <td class="px-3 py-2 text-right">{{ d.movement?.quantity }}</td>
                <td class="px-3 py-2 text-right">{{ formatCurrency(d.stored_balance_value) }}</td>
                <td class="px-3 py-2 text-right">{{ formatCurrency(d.expected_balance_value) }}</td>
                <td class="px-3 py-2 text-right font-medium text-red-600">
                  {{ formatCurrency(d.value_drift) }}
                </td>
                <td class="px-3 py-2">
                  <span v-if="d.is_root_cause" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                    {{ errorCategoryLabel(d.error_category) || $t('stock.root_cause') }}
                  </span>
                  <span v-else-if="d.error_category === 'cascade'" class="text-xs text-gray-400">cascade</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- No discrepancies -->
      <div v-else class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
        <BaseIcon name="CheckCircleIcon" class="w-8 h-8 text-green-500 mx-auto mb-2" />
        <p class="text-green-700 font-medium">{{ $t('stock.no_discrepancies') }}</p>
      </div>
    </template>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useStockStore } from '@/scripts/admin/stores/stock.js'
import { useNotificationStore } from '@/scripts/stores/notification'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'

const { t } = useI18n()
const route = useRoute()
const stockStore = useStockStore()
const notificationStore = useNotificationStore()

const auditId = computed(() => route.params.id)
const isLoading = computed(() => stockStore.isLoadingAudit)
const audit = computed(() => stockStore.currentAuditRun)
const discrepancies = computed(() => audit.value?.discrepancies || [])
const proposal = computed(() => {
  const proposals = audit.value?.proposals || []
  return proposals.length > 0 ? proposals[proposals.length - 1] : null
})

const isAnalyzing = ref(false)
const isGenerating = ref(false)
const isApproving = ref(false)
const isRejecting = ref(false)

const statusBadgeClass = (status) => {
  switch (status) {
    case 'completed': return 'bg-green-100 text-green-800'
    case 'running': return 'bg-blue-100 text-blue-800'
    case 'failed': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const proposalStatusClass = (status) => {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800'
    case 'approved':
    case 'applied': return 'bg-green-100 text-green-800'
    case 'rejected': return 'bg-red-100 text-red-800'
    case 'expired': return 'bg-gray-100 text-gray-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const rowClass = (d) => {
  if (d.is_root_cause) return 'bg-orange-50 border-l-4 border-orange-400'
  return 'bg-red-50'
}

const errorCategoryLabel = (category) => {
  if (!category) return ''
  const key = `stock.error_category_${category}`
  const translated = t(key)
  // Fallback to formatted category if no translation found
  return translated !== key ? translated : category.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const confidenceClass = (confidence) => {
  if (!confidence) return 'text-xs font-medium text-gray-500'
  if (confidence >= 0.8) return 'text-xs font-semibold text-green-600'
  if (confidence >= 0.5) return 'text-xs font-semibold text-yellow-600'
  return 'text-xs font-semibold text-red-600'
}
// CLAUDE-CHECKPOINT

const formatCurrency = (cents) => {
  if (cents === null || cents === undefined) return '-'
  return (cents / 100).toFixed(2) + ' MKD'
}

const triggerAnalysis = async () => {
  isAnalyzing.value = true
  try {
    await stockStore.triggerAiAnalysis(auditId.value)
    await stockStore.fetchWacAuditDetail(auditId.value)
    notificationStore.showNotification({ type: 'success', message: 'AI analysis complete.' })
  } catch (err) {
    // handled by store
  } finally {
    isAnalyzing.value = false
  }
}

const generateProposal = async () => {
  isGenerating.value = true
  try {
    await stockStore.generateCorrectionProposal(auditId.value)
    await stockStore.fetchWacAuditDetail(auditId.value)
    notificationStore.showNotification({ type: 'success', message: 'Correction proposal generated.' })
  } catch (err) {
    // handled by store
  } finally {
    isGenerating.value = false
  }
}

const approve = async () => {
  if (!confirm('Are you sure you want to approve this correction?')) return
  isApproving.value = true
  try {
    const result = await stockStore.approveProposal(proposal.value.id)
    await stockStore.fetchWacAuditDetail(auditId.value)
    notificationStore.showNotification({ type: 'success', message: result.message })
  } catch (err) {
    // handled by store
  } finally {
    isApproving.value = false
  }
}

const reject = async () => {
  if (!confirm('Are you sure you want to reject this correction?')) return
  const notes = prompt('Rejection reason (optional):')
  isRejecting.value = true
  try {
    await stockStore.rejectProposal(proposal.value.id, notes || '')
    await stockStore.fetchWacAuditDetail(auditId.value)
    notificationStore.showNotification({ type: 'info', message: 'Correction rejected.' })
  } catch (err) {
    // handled by store
  } finally {
    isRejecting.value = false
  }
}

onMounted(() => {
  stockStore.fetchWacAuditDetail(auditId.value)
})
</script>
