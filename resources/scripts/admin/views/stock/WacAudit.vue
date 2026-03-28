<template>
  <BasePage>
    <BasePageHeader :title="$t('stock.wac_audit_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('stock.title')" to="/admin/stock" />
        <BaseBreadcrumbItem :title="$t('stock.wac_audit')" to="#" active />
      </BaseBreadcrumb>

      <template #actions>
        <BaseButton variant="primary" @click="showRunModal = true">
          <template #left="slotProps">
            <BaseIcon name="MagnifyingGlassIcon" :class="slotProps.class" />
          </template>
          {{ $t('stock.run_audit') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <StockTabNavigation />

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-500">{{ $t('stock.audit_history') }}</div>
        <div class="text-2xl font-bold mt-1">{{ auditTotal }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-500">{{ $t('stock.discrepancies_found') }}</div>
        <div class="text-2xl font-bold mt-1" :class="totalDiscrepancies > 0 ? 'text-red-600' : 'text-green-600'">
          {{ totalDiscrepancies }}
        </div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm text-gray-500">{{ $t('stock.pending_corrections') }}</div>
        <div class="text-2xl font-bold mt-1" :class="pendingCorrections > 0 ? 'text-yellow-600' : ''">
          {{ pendingCorrections }}
        </div>
      </div>
    </div>

    <!-- Audit Runs Table -->
    <div class="bg-white rounded-lg shadow">
      <div v-if="isLoading" class="p-8 text-center text-gray-500">
        <BaseIcon name="ArrowPathIcon" class="w-6 h-6 animate-spin inline-block mr-2" />
        Loading...
      </div>

      <div v-else-if="auditRuns.length === 0" class="p-8 text-center text-gray-500">
        {{ $t('stock.no_discrepancies') }}
      </div>

      <table v-else class="w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.date') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('stock.audit_scope') }}</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $t('general.status') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.movements_checked') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ $t('stock.discrepancies') }}</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="run in auditRuns"
            :key="run.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="viewDetail(run.id)"
          >
            <td class="px-4 py-3 text-sm">#{{ run.id }}</td>
            <td class="px-4 py-3 text-sm">{{ run.created_at }}</td>
            <td class="px-4 py-3 text-sm">
              {{ run.item_name || $t('stock.all_items') }}
              <span v-if="run.warehouse_name" class="text-gray-400"> / {{ run.warehouse_name }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                :class="statusBadgeClass(run.status)"
              >
                {{ run.status }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-right">{{ run.total_movements_checked }}</td>
            <td class="px-4 py-3 text-sm text-right">
              <span :class="run.discrepancies_found > 0 ? 'text-red-600 font-semibold' : 'text-green-600'">
                {{ run.discrepancies_found }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <BaseIcon name="ChevronRightIcon" class="w-4 h-4 text-gray-400" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Run Audit Modal -->
    <BaseModal :show="showRunModal" @close="showRunModal = false">
      <template #header>
        {{ $t('stock.run_audit') }}
      </template>
      <div class="space-y-4">
        <p class="text-sm text-gray-600">{{ $t('stock.audit_scope') }}</p>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('stock.select_item') }}</label>
          <select v-model="runForm.item_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option :value="null">{{ $t('stock.all_items') }}</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('stock.select_warehouse') }}</label>
          <select v-model="runForm.warehouse_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
            <option :value="null">{{ $t('stock.all_warehouses') }}</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">{{ wh.name }}</option>
          </select>
        </div>
      </div>
      <template #footer>
        <BaseButton variant="primary" :loading="isRunning" @click="runAudit">
          {{ $t('stock.run_audit') }}
        </BaseButton>
      </template>
    </BaseModal>
  </BasePage>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStockStore } from '@/scripts/admin/stores/stock.js'
import StockTabNavigation from '@/scripts/admin/components/StockTabNavigation.vue'
import { useNotificationStore } from '@/scripts/stores/notification'

const router = useRouter()
const stockStore = useStockStore()
const notificationStore = useNotificationStore()

const showRunModal = ref(false)
const isRunning = ref(false)
const runForm = ref({ item_id: null, warehouse_id: null })

const auditRuns = computed(() => stockStore.wacAuditRuns)
const auditTotal = computed(() => stockStore.wacAuditTotal)
const isLoading = computed(() => stockStore.isLoadingAudit)
const warehouses = computed(() => stockStore.warehouses)

const totalDiscrepancies = computed(() =>
  auditRuns.value.reduce((sum, r) => sum + (r.discrepancies_found || 0), 0)
)

const pendingCorrections = computed(() =>
  auditRuns.value.filter((r) => r.has_discrepancies && r.status === 'completed').length
)

const statusBadgeClass = (status) => {
  switch (status) {
    case 'completed': return 'bg-green-100 text-green-800'
    case 'running': return 'bg-blue-100 text-blue-800'
    case 'pending': return 'bg-yellow-100 text-yellow-800'
    case 'failed': return 'bg-red-100 text-red-800'
    default: return 'bg-gray-100 text-gray-800'
  }
}

const viewDetail = (id) => {
  router.push({ name: 'stock.wac-audit.detail', params: { id } })
}

const runAudit = async () => {
  isRunning.value = true
  try {
    const result = await stockStore.runWacAudit(runForm.value)
    showRunModal.value = false
    notificationStore.showNotification({
      type: result.data?.has_discrepancies ? 'warning' : 'success',
      message: result.message,
    })
    await stockStore.fetchWacAuditRuns()
    if (result.data?.id) {
      viewDetail(result.data.id)
    }
  } catch (err) {
    // handled by store
  } finally {
    isRunning.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    stockStore.fetchWacAuditRuns(),
    stockStore.fetchWarehouses(),
  ])
})
</script>
