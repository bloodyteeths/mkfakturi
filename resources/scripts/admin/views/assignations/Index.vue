<template>
  <BasePage>
    <BasePageHeader :title="$t('assignations_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('assignations_title')" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton variant="primary" @click="$router.push('/admin/assignations/create')">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('assignation_new') }}
        </BaseButton>
      </template>
    </BasePageHeader>

    <BaseTable
      ref="table"
      :data="fetchData"
      :columns="columns"
      class="mt-6"
    >
      <template #cell-status="{ row }">
        <span
          class="px-2 py-1 text-xs rounded-full"
          :class="{
            'bg-yellow-100 text-yellow-800': row.data.status === 'draft',
            'bg-green-100 text-green-800': row.data.status === 'confirmed',
            'bg-red-100 text-red-800': row.data.status === 'cancelled',
          }"
        >
          {{ $t('status_' + row.data.status) }}
        </span>
      </template>
      <template #cell-amount="{ row }">
        {{ formatAmount(row.data.amount) }}
      </template>
      <template #cell-actions="{ row }">
        <div class="flex justify-end gap-2">
          <BaseButton size="sm" variant="gray" @click="$router.push(`/admin/assignations/${row.data.id}/view`)">
            {{ $t('general.view') }}
          </BaseButton>
          <BaseButton v-if="row.data.status === 'draft'" size="sm" variant="primary" @click="confirm(row.data.id)">
            {{ $t('status_confirmed') }}
          </BaseButton>
        </div>
      </template>
    </BaseTable>
  </BasePage>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const table = ref(null)

const columns = computed(() => [
  { key: 'assignation_date', label: t('payments.date') },
  { key: 'assignation_number', label: t('payments.payment_number') },
  { key: 'assignor_name', label: t('assignor') },
  { key: 'assignee_name', label: t('assignee') },
  { key: 'debtor_name', label: t('assigned_debtor') },
  { key: 'amount', label: t('assignation_amount') },
  { key: 'status', label: t('general.status') },
  { key: 'actions', label: '', sortable: false },
])

function formatAmount(cents) {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(cents) / 100)
}

async function fetchData({ page, sort }) {
  const res = await window.axios.get('/assignations', {
    params: { page, limit: 10, orderByField: sort.fieldName || 'assignation_date', orderBy: sort.order || 'desc' }
  })
  return {
    data: res.data.data || [],
    pagination: { totalPages: res.data.meta?.last_page || 1, currentPage: page, totalCount: res.data.meta?.total || 0, limit: 10 }
  }
}

async function confirm(id) {
  await window.axios.post(`/assignations/${id}/confirm`)
  table.value?.refresh()
}
</script>
// CLAUDE-CHECKPOINT
