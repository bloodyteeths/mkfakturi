<template>
  <BaseModal
    :show="modalStore.active && modalStore.componentName === 'FiscalDeviceModal'"
    @close="closeModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}
        <BaseIcon
          name="XMarkIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>
    <form action="" @submit.prevent="submitData">
      <div class="p-4 sm:p-6">
        <BaseInputGrid layout="one-column">
          <BaseInputGroup
            :label="$t('settings.fiscal_devices.device_type')"
            variant="horizontal"
            :error="
              v$.currentFiscalDevice.device_type.$error &&
              v$.currentFiscalDevice.device_type.$errors[0].$message
            "
            required
          >
            <BaseSelectInput
              v-model="fiscalDeviceStore.currentFiscalDevice.device_type"
              :options="deviceTypeOptions"
              :allow-empty="false"
              :disabled="fiscalDeviceStore.isEdit"
              value-prop="id"
              label-prop="label"
              track-by="label"
              :searchable="false"
              @update:modelValue="onDeviceTypeChange"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('settings.fiscal_devices.name')"
            variant="horizontal"
          >
            <BaseInput
              v-model="fiscalDeviceStore.currentFiscalDevice.name"
              type="text"
              :placeholder="$t('settings.fiscal_devices.name_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('settings.fiscal_devices.serial_number')"
            variant="horizontal"
            :error="
              v$.currentFiscalDevice.serial_number.$error &&
              v$.currentFiscalDevice.serial_number.$errors[0].$message
            "
            required
          >
            <BaseInput
              v-model="fiscalDeviceStore.currentFiscalDevice.serial_number"
              :invalid="v$.currentFiscalDevice.serial_number.$error"
              :disabled="fiscalDeviceStore.isEdit"
              type="text"
              @input="v$.currentFiscalDevice.serial_number.$touch()"
            />
          </BaseInputGroup>

          <!-- ErpNet.FP auto-discovery info banner -->
          <div
            v-if="isErpnetFp"
            class="rounded-md bg-blue-50 p-3"
          >
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3 text-sm text-blue-700">
                {{ $t('settings.fiscal_devices.erpnet_auto_discovery') }}
              </div>
            </div>
          </div>

          <!-- Connection type selector (hidden for erpnet-fp) -->
          <BaseInputGroup
            v-if="!isErpnetFp"
            :label="$t('settings.fiscal_devices.connection_type')"
            variant="horizontal"
            required
          >
            <BaseSelectInput
              v-model="fiscalDeviceStore.currentFiscalDevice.connection_type"
              :options="connectionTypeOptions"
              :allow-empty="false"
              value-prop="id"
              label-prop="label"
              track-by="label"
              :searchable="false"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="!isErpnetFp && fiscalDeviceStore.currentFiscalDevice.connection_type === 'tcp'"
            :label="$t('settings.fiscal_devices.ip_address')"
            variant="horizontal"
            :error="
              v$.currentFiscalDevice.ip_address.$error &&
              v$.currentFiscalDevice.ip_address.$errors[0].$message
            "
            required
          >
            <BaseInput
              v-model="fiscalDeviceStore.currentFiscalDevice.ip_address"
              :invalid="v$.currentFiscalDevice.ip_address.$error"
              type="text"
              placeholder="192.168.1.100"
              @input="v$.currentFiscalDevice.ip_address.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="!isErpnetFp && fiscalDeviceStore.currentFiscalDevice.connection_type === 'tcp'"
            :label="$t('settings.fiscal_devices.port')"
            variant="horizontal"
          >
            <BaseInput
              v-model.number="fiscalDeviceStore.currentFiscalDevice.port"
              type="number"
              placeholder="4999"
            />
          </BaseInputGroup>

          <BaseInputGroup
            v-if="!isErpnetFp && (fiscalDeviceStore.currentFiscalDevice.connection_type === 'serial' || fiscalDeviceStore.currentFiscalDevice.connection_type === 'bluetooth')"
            :label="$t('settings.fiscal_devices.serial_port')"
            variant="horizontal"
            :error="
              v$.currentFiscalDevice.serial_port.$error &&
              v$.currentFiscalDevice.serial_port.$errors[0].$message
            "
            required
          >
            <BaseInput
              v-model="fiscalDeviceStore.currentFiscalDevice.serial_port"
              :invalid="v$.currentFiscalDevice.serial_port.$error"
              type="text"
              :placeholder="serialPortPlaceholder"
              @input="v$.currentFiscalDevice.serial_port.$touch()"
            />
          </BaseInputGroup>
        </BaseInputGrid>
      </div>
      <div
        class="
          z-0
          flex
          justify-end
          p-4
          border-t border-solid border--200 border-modal-bg
        "
      >
        <BaseButton
          class="mr-3 text-sm"
          variant="primary-outline"
          type="button"
          @click="closeModal"
        >
          {{ $t('general.cancel') }}
        </BaseButton>
        <BaseButton
          :loading="isSaving"
          :disabled="isSaving"
          variant="primary"
          type="submit"
        >
          <template #left="slotProps">
            <BaseIcon
              v-if="!isSaving"
              name="ArrowDownOnSquareIcon"
              :class="slotProps.class"
            />
          </template>
          {{ fiscalDeviceStore.isEdit ? $t('general.update') : $t('general.save') }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { useFiscalDeviceStore } from '@/scripts/admin/stores/fiscal-device'
import { useModalStore } from '@/scripts/stores/modal'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import {
  required,
  helpers,
} from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'

const fiscalDeviceStore = useFiscalDeviceStore()
const modalStore = useModalStore()

const { t } = useI18n()
let isSaving = ref(false)

const deviceTypeOptions = computed(() => {
  return fiscalDeviceStore.supportedTypes.map((st) => ({
    id: st.type,
    label: st.label,
  }))
})

const isErpnetFp = computed(() => {
  return fiscalDeviceStore.currentFiscalDevice.device_type === 'erpnet-fp'
})

const connectionTypeOptions = [
  { id: 'tcp', label: 'TCP/IP' },
  { id: 'serial', label: 'RS232 Serial' },
  { id: 'bluetooth', label: 'Bluetooth' },
  { id: 'erpnet-fp', label: 'ErpNet.FP (Auto)' },
]

const serialPortPlaceholder = computed(() => {
  if (fiscalDeviceStore.currentFiscalDevice.connection_type === 'bluetooth') {
    return '/dev/rfcomm0'
  }
  return '/dev/ttyUSB0 or COM1'
})

const ipRequired = (value) => {
  if (fiscalDeviceStore.currentFiscalDevice.connection_type !== 'tcp') return true
  return !!value && value.trim().length > 0
}

const serialPortRequired = (value) => {
  const ct = fiscalDeviceStore.currentFiscalDevice.connection_type
  if (ct !== 'serial' && ct !== 'bluetooth') return true
  return !!value && value.trim().length > 0
}

const rules = computed(() => {
  return {
    currentFiscalDevice: {
      device_type: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      serial_number: {
        required: helpers.withMessage(t('validation.required'), required),
      },
      ip_address: {
        requiredIfTcp: helpers.withMessage(
          t('settings.fiscal_devices.ip_required'),
          ipRequired
        ),
      },
      serial_port: {
        requiredIfSerial: helpers.withMessage(
          t('settings.fiscal_devices.serial_port_required'),
          serialPortRequired
        ),
      },
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => fiscalDeviceStore)
)

function onDeviceTypeChange(deviceType) {
  if (deviceType === 'erpnet-fp') {
    fiscalDeviceStore.currentFiscalDevice.connection_type = 'erpnet-fp'
    fiscalDeviceStore.currentFiscalDevice.ip_address = ''
    fiscalDeviceStore.currentFiscalDevice.port = null
    fiscalDeviceStore.currentFiscalDevice.serial_port = ''
    return
  }
  const st = fiscalDeviceStore.supportedTypes.find((s) => s.type === deviceType)
  if (st) {
    fiscalDeviceStore.currentFiscalDevice.connection_type = st.default_connection
  }
}

async function submitData() {
  v$.value.currentFiscalDevice.$touch()
  if (v$.value.currentFiscalDevice.$invalid) {
    return true
  }
  try {
    const action = fiscalDeviceStore.isEdit
      ? fiscalDeviceStore.updateFiscalDevice
      : fiscalDeviceStore.addFiscalDevice
    isSaving.value = true
    await action(fiscalDeviceStore.currentFiscalDevice)
    isSaving.value = false
    modalStore.refreshData ? modalStore.refreshData() : ''
    closeModal()
  } catch (err) {
    isSaving.value = false
    return true
  }
}

function closeModal() {
  modalStore.closeModal()
  setTimeout(() => {
    fiscalDeviceStore.resetCurrentFiscalDevice()
    v$.value.$reset()
  }, 300)
}
</script>
