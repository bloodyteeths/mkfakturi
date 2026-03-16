<template>
  <BaseModal :show="modalActive" @close="closeModal">
    <template #header>
      <div class="flex justify-between w-full">
        {{ t('budgets.create_cost_center') }}
        <BaseIcon
          name="XMarkIcon"
          class="h-6 w-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>
    <div>
      <form @submit.prevent="submit">
        <div class="px-8 py-8 sm:p-6">
          <BaseInputGrid layout="one-column">
            <BaseInputGroup
              :label="t('budgets.cost_center_name')"
              required
              :error="nameError"
            >
              <BaseInput
                v-model="form.name"
                type="text"
                :placeholder="t('budgets.cost_center_name')"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('budgets.cost_center_code')">
              <BaseInput
                v-model="form.code"
                type="text"
                :placeholder="t('budgets.cost_center_code')"
              />
            </BaseInputGroup>

            <BaseInputGroup :label="t('budgets.cost_center_color')">
              <div class="flex items-center gap-2">
                <div class="flex gap-1">
                  <button
                    v-for="color in presetColors"
                    :key="color"
                    type="button"
                    class="w-7 h-7 rounded-full border-2 transition-all"
                    :class="form.color === color ? 'border-gray-800 scale-110' : 'border-transparent'"
                    :style="{ backgroundColor: color }"
                    @click="form.color = color"
                  />
                </div>
              </div>
            </BaseInputGroup>
          </BaseInputGrid>
        </div>
        <div class="z-0 flex justify-end p-4 border-t border-gray-200 border-solid">
          <BaseButton
            class="mr-3"
            variant="primary-outline"
            type="button"
            @click="closeModal"
          >
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton
            :loading="isLoading"
            :disabled="isLoading || !form.name"
            variant="primary"
            type="submit"
          >
            <template #left="slotProps">
              <BaseIcon name="ArrowDownOnSquareIcon" :class="slotProps.class" />
            </template>
            {{ $t('general.save') }}
          </BaseButton>
        </div>
      </form>
    </div>
  </BaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useModalStore } from '@/scripts/stores/modal'
import { useNotificationStore } from '@/scripts/stores/notification'

const modalStore = useModalStore()
const notificationStore = useNotificationStore()
const { t } = useI18n()

const isLoading = ref(false)
const nameError = ref('')

const form = ref({
  name: '',
  code: '',
  color: '#3B82F6',
})

const presetColors = [
  '#3B82F6', '#10B981', '#F59E0B', '#EF4444',
  '#8B5CF6', '#EC4899', '#06B6D4', '#6B7280',
]

const modalActive = computed(
  () => modalStore.active && modalStore.componentName === 'CostCenterModal'
)

async function submit() {
  nameError.value = ''

  if (!form.value.name || form.value.name.trim().length < 2) {
    nameError.value = t('validation.required')
    return
  }

  isLoading.value = true

  try {
    const payload = {
      name: form.value.name.trim(),
      is_active: true,
    }
    if (form.value.code) payload.code = form.value.code.trim()
    if (form.value.color) payload.color = form.value.color

    const response = await window.axios.post('/cost-centers', payload)
    const newCostCenter = response.data?.data

    if (newCostCenter && modalStore.refreshData) {
      modalStore.refreshData(newCostCenter)
    }

    notificationStore.showNotification({
      type: 'success',
      message: t('budgets.created_success'),
    })

    closeModal()
  } catch (error) {
    const msg = error.response?.data?.error || error.response?.data?.message || t('budgets.error_creating')
    nameError.value = msg
  } finally {
    isLoading.value = false
  }
}

function closeModal() {
  modalStore.closeModal()
  setTimeout(() => {
    form.value = { name: '', code: '', color: '#3B82F6' }
    nameError.value = ''
    modalStore.$reset()
  }, 300)
}
</script>
