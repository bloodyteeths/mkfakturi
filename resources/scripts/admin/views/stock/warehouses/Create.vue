<template>
  <BasePage>
    <BasePageHeader :title="isEdit ? $t('warehouses.edit_warehouse') : $t('warehouses.new_warehouse')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('warehouses.title')" to="/admin/stock/warehouses" />
        <BaseBreadcrumbItem
          :title="isEdit ? $t('warehouses.edit_warehouse') : $t('warehouses.new_warehouse')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInputGroup
            :label="$t('warehouses.name')"
            :error="nameError"
            required
          >
            <BaseInput
              v-model="form.name"
              :invalid="!!nameError"
              :placeholder="$t('warehouses.name_placeholder')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('warehouses.code')">
            <BaseInput
              v-model="form.code"
              :placeholder="$t('warehouses.code_placeholder')"
            />
          </BaseInputGroup>

          <div class="md:col-span-2">
            <BaseInputGroup :label="$t('warehouses.address')">
              <BaseTextarea
                v-model="form.address"
                rows="3"
                :placeholder="$t('warehouses.address_placeholder')"
              />
            </BaseInputGroup>
          </div>

          <BaseInputGroup :label="$t('warehouses.is_active')">
            <BaseSwitch
              v-model="form.is_active"
              :label="form.is_active ? $t('general.active') : $t('general.inactive')"
            />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('warehouses.is_default')">
            <BaseSwitch
              v-model="form.is_default"
              :label="form.is_default ? $t('general.yes') : $t('general.no')"
            />
          </BaseInputGroup>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="$router.push('/admin/stock/warehouses')">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" type="submit" :loading="isLoading">
            {{ isEdit ? $t('general.update') : $t('general.create') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { reactive, ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useWarehouseStore } from '@/scripts/admin/stores/warehouse'

const { t } = useI18n()
const warehouseStore = useWarehouseStore()
const route = useRoute()
const router = useRouter()

const isLoading = ref(false)

const form = reactive({
  id: null,
  name: '',
  code: '',
  address: '',
  is_active: true,
  is_default: false,
})

const isEdit = computed(() => !!route.params.id)

const nameError = ref('')

function validateForm() {
  nameError.value = ''
  if (!form.name || form.name.trim() === '') {
    nameError.value = t('validation.required')
    return false
  }
  return true
}

function hydrateForm(data) {
  form.id = data.id
  form.name = data.name
  form.code = data.code
  form.address = data.address
  form.is_active = data.is_active
  form.is_default = data.is_default
}

async function handleSubmit() {
  if (!validateForm()) {
    return
  }

  isLoading.value = true
  const payload = { ...form }

  try {
    if (isEdit.value) {
      await warehouseStore.updateWarehouse(form.id, payload)
    } else {
      await warehouseStore.addWarehouse(payload)
    }
    router.push('/admin/stock/warehouses')
  } catch (error) {
    console.error('Error saving warehouse:', error)
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  // Load warehouse data if editing
  if (isEdit.value) {
    const response = await warehouseStore.fetchWarehouse(route.params.id)
    if (response.data?.data) {
      hydrateForm(response.data.data)
    }
  }
})
</script>
<!-- CLAUDE-CHECKPOINT -->
