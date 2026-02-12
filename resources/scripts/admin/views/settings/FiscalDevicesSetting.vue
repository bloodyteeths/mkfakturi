<template>
  <BaseSettingCard
    :title="$t('settings.fiscal_devices.title')"
    :description="$t('settings.fiscal_devices.description')"
  >
    <FiscalDeviceModal />

    <!-- ErpNet.FP Sidecar Status Banner -->
    <div
      v-if="erpnetStatus !== null"
      class="mb-4 rounded-lg border p-4"
      :class="erpnetStatus.connected
        ? 'border-green-200 bg-green-50'
        : 'border-gray-200 bg-gray-50'"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center">
          <span
            class="mr-2 inline-block h-3 w-3 rounded-full"
            :class="erpnetStatus.connected ? 'bg-green-400' : 'bg-gray-400'"
          ></span>
          <span class="text-sm font-medium" :class="erpnetStatus.connected ? 'text-green-800' : 'text-gray-600'">
            ErpNet.FP
          </span>
          <span class="ml-2 text-sm" :class="erpnetStatus.connected ? 'text-green-700' : 'text-gray-500'">
            {{ erpnetStatus.message }}
          </span>
        </div>
        <BaseButton
          type="button"
          variant="primary-outline"
          class="text-xs"
          @click="checkErpnetStatus"
        >
          {{ $t('settings.fiscal_devices.check_status') }}
        </BaseButton>
      </div>
      <!-- Show discovered printers -->
      <div v-if="erpnetStatus.connected && erpnetStatus.printers.length > 0" class="mt-3 space-y-1">
        <div
          v-for="printer in erpnetStatus.printers"
          :key="printer.id"
          class="flex items-center text-sm text-green-700"
        >
          <svg class="mr-1.5 h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
          {{ printer.model || 'Unknown' }} — SN: {{ printer.serialNumber || printer.id }}
        </div>
      </div>
    </div>

    <template #action>
      <BaseButton type="submit" variant="primary-outline" @click="openAddModal">
        <template #left="slotProps">
          <BaseIcon :class="slotProps.class" name="PlusIcon" />
        </template>
        {{ $t('settings.fiscal_devices.add_device') }}
      </BaseButton>
    </template>

    <BaseTable
      ref="table"
      class="mt-16"
      :data="fetchData"
      :columns="deviceColumns"
    >
      <template #cell-device_type="{ row }">
        {{ getDeviceLabel(row.data.device_type) }}
      </template>

      <template #cell-connection_type="{ row }">
        <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium"
          :class="connectionBadgeClass(row.data.connection_type)"
        >
          {{ connectionLabel(row.data.connection_type) }}
        </span>
      </template>

      <template #cell-is_active="{ row }">
        <span
          class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
          :class="row.data.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
        >
          {{ row.data.is_active ? $t('settings.fiscal_devices.active') : $t('settings.fiscal_devices.inactive') }}
        </span>
      </template>

      <template #cell-actions="{ row }">
        <FiscalDeviceDropdown
          :row="row.data"
          :table="table"
          :load-data="refreshTable"
        />
      </template>
    </BaseTable>
  </BaseSettingCard>
</template>

<script setup>
import { useFiscalDeviceStore } from '@/scripts/admin/stores/fiscal-device'
import { useModalStore } from '@/scripts/stores/modal'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

import FiscalDeviceDropdown from '@/scripts/admin/components/dropdowns/FiscalDeviceIndexDropdown.vue'
import FiscalDeviceModal from '@/scripts/admin/components/modal-components/FiscalDeviceModal.vue'

const { t } = useI18n()

const fiscalDeviceStore = useFiscalDeviceStore()
const modalStore = useModalStore()
const table = ref(null)
const erpnetStatus = ref(null)

async function checkErpnetStatus() {
  try {
    const response = await axios.get('/fiscal-devices/erpnet-status')
    erpnetStatus.value = response.data.data
  } catch (e) {
    erpnetStatus.value = {
      connected: false,
      printers: [],
      message: 'Could not check ErpNet.FP status',
    }
  }
}

onMounted(() => {
  checkErpnetStatus()
})

const deviceColumns = computed(() => {
  return [
    {
      key: 'name',
      label: t('settings.fiscal_devices.name'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'device_type',
      label: t('settings.fiscal_devices.device_type'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'serial_number',
      label: t('settings.fiscal_devices.serial_number'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'connection_type',
      label: t('settings.fiscal_devices.connection_type'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'is_active',
      label: t('settings.fiscal_devices.status'),
      thClass: 'extra',
      tdClass: 'font-medium text-gray-900',
    },
    {
      key: 'actions',
      label: '',
      tdClass: 'text-right text-sm font-medium',
      sortable: false,
    },
  ]
})

function getDeviceLabel(deviceType) {
  const st = fiscalDeviceStore.supportedTypes.find((s) => s.type === deviceType)
  return st ? st.label : deviceType
}

function connectionLabel(type) {
  const labels = { tcp: 'TCP/IP', serial: 'RS232', bluetooth: 'Bluetooth', 'erpnet-fp': 'ErpNet.FP' }
  return labels[type] || type
}

function connectionBadgeClass(type) {
  const classes = {
    tcp: 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-700/10',
    serial: 'bg-yellow-50 text-yellow-800 ring-1 ring-inset ring-yellow-600/20',
    bluetooth: 'bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-700/10',
    'erpnet-fp': 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-700/10',
  }
  return classes[type] || 'bg-gray-50 text-gray-600'
}

async function fetchData({ page, filter, sort }) {
  let data = {
    orderByField: sort.fieldName || 'created_at',
    orderBy: sort.order || 'desc',
    page,
  }

  let response = await fiscalDeviceStore.fetchFiscalDevices(data)

  return {
    data: response.data.data,
    pagination: {
      totalPages: 1,
      currentPage: page,
      totalCount: response.data.data.length,
      limit: 25,
    },
  }
}

async function refreshTable() {
  table.value && table.value.refresh()
}

function openAddModal() {
  fiscalDeviceStore.resetCurrentFiscalDevice()
  modalStore.openModal({
    title: t('settings.fiscal_devices.add_device'),
    componentName: 'FiscalDeviceModal',
    size: 'sm',
    refreshData: table.value && table.value.refresh,
  })
}
</script>
