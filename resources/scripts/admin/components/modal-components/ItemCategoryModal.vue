<template>
  <BaseModal
    :show="modalStore.active && modalStore.componentName === 'ItemCategoryModal'"
    @close="closeModal"
  >
    <template #header>
      <div class="flex justify-between w-full">
        {{ modalStore.title }}
        <BaseIcon
          name="XMarkIcon"
          class="w-6 h-6 text-gray-500 cursor-pointer"
          @click="closeModal"
        />
      </div>
    </template>

    <form action="" @submit.prevent="submitCategory">
      <div class="p-8 sm:p-6">
        <BaseInputGroup
          :label="$t('items.category_name')"
          :error="v$.name.$error && v$.name.$errors[0].$message"
          variant="horizontal"
          required
        >
          <BaseInput
            v-model="itemStore.currentItemCategory.name"
            :invalid="v$.name.$error"
            type="text"
            @input="v$.name.$touch()"
          />
        </BaseInputGroup>

        <BaseInputGroup
          :label="$t('items.category_description')"
          variant="horizontal"
          class="mt-4"
        >
          <BaseInput
            v-model="itemStore.currentItemCategory.description"
            type="text"
          />
        </BaseInputGroup>
      </div>

      <div
        class="
          z-0
          flex
          justify-end
          p-4
          border-t border-gray-200 border-solid border-modal-bg
        "
      >
        <BaseButton
          type="button"
          variant="primary-outline"
          class="mr-3 text-sm"
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
          {{
            itemStore.isItemCategoryEdit ? $t('general.update') : $t('general.save')
          }}
        </BaseButton>
      </div>
    </form>
  </BaseModal>
</template>

<script setup>
import { useItemStore } from '@/scripts/admin/stores/item'
import { useModalStore } from '@/scripts/stores/modal'
import { computed, ref } from 'vue'
import { required, minLength, maxLength, helpers } from '@vuelidate/validators'
import { useVuelidate } from '@vuelidate/core'
import { useI18n } from 'vue-i18n'

const itemStore = useItemStore()
const modalStore = useModalStore()

const { t } = useI18n()
let isSaving = ref(false)

const rules = computed(() => {
  return {
    name: {
      required: helpers.withMessage(t('validation.required'), required),
      minLength: helpers.withMessage(
        t('validation.name_min_length', { count: 2 }),
        minLength(2)
      ),
      maxLength: helpers.withMessage(
        t('validation.max_length', { length: 100 }),
        maxLength(100)
      ),
    },
  }
})

const v$ = useVuelidate(
  rules,
  computed(() => itemStore.currentItemCategory)
)

async function submitCategory() {
  v$.value.$touch()

  if (v$.value.$invalid) {
    return true
  }
  try {
    const action = itemStore.isItemCategoryEdit
      ? itemStore.updateItemCategory
      : itemStore.addItemCategory

    isSaving.value = true

    await action(itemStore.currentItemCategory)

    modalStore.refreshData ? modalStore.refreshData() : ''

    closeModal()
    isSaving.value = false
  } catch (err) {
    isSaving.value = false
    return true
  }
}

function closeModal() {
  modalStore.closeModal()

  setTimeout(() => {
    itemStore.currentItemCategory = {
      id: null,
      name: '',
      description: '',
    }

    modalStore.$reset()
    v$.value.$reset()
  }, 300)
}
</script>
// CLAUDE-CHECKPOINT
