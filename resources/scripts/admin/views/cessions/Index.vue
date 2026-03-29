<template>
  <BasePage>
    <BasePageHeader :title="$t('cessions_title')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('cessions_title')" to="#" active />
      </BaseBreadcrumb>
      <template #actions>
        <BaseButton variant="primary" @click="$router.push('/admin/cessions/create')">
          <template #left="slotProps">
            <BaseIcon name="PlusIcon" :class="slotProps.class" />
          </template>
          {{ $t('cession_new') }}
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
        {{ formatAmount(row.data.amount) }} <span class="text-gray-400 text-xs">ден</span>
      </template>
      <template #cell-actions="{ row }">
        <div class="flex justify-end gap-2">
          <BaseButton size="sm" variant="gray" @click="$router.push(`/admin/cessions/${row.data.id}/view`)">
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
  { key: 'cession_date', label: t('payments.date') },
  { key: 'cession_number', label: '# Број' },
  { key: 'cedent_name', label: t('cedent') },
  { key: 'cessionary_name', label: t('cessionary') },
  { key: 'debtor_name', label: t('cession_debtor') },
  { key: 'amount', label: 'Износ' },
  { key: 'status', label: t('general.status') },
  { key: 'actions', label: '', sortable: false },
])

function formatAmount(cents) {
  return new Intl.NumberFormat('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Math.abs(cents) / 100)
}

async function fetchData({ page, sort }) {
  const res = await window.axios.get('/cessions', {
    params: { page, limit: 10, orderByField: sort.fieldName || 'cession_date', orderBy: sort.order || 'desc' }
  })
  return {
    data: res.data.data || [],
    pagination: { totalPages: res.data.meta?.last_page || 1, currentPage: page, totalCount: res.data.meta?.total || 0, limit: 10 }
  }
}

async function confirm(id) {
  await window.axios.post(`/cessions/${id}/confirm`)
  table.value?.refresh()
}
</script>
// CLAUDE-CHECKPOINT
