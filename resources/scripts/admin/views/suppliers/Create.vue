<template>
  <BasePage>
    <BasePageHeader :title="isEdit ? $t('suppliers.edit_supplier') : $t('suppliers.new_supplier')">
      <BaseBreadcrumb>
        <BaseBreadcrumbItem :title="$t('general.home')" to="dashboard" />
        <BaseBreadcrumbItem :title="$t('suppliers.title')" to="/admin/suppliers" />
        <BaseBreadcrumbItem
          :title="isEdit ? $t('suppliers.edit_supplier') : $t('suppliers.new_supplier')"
          to="#"
          active
        />
      </BaseBreadcrumb>
    </BasePageHeader>

    <BaseCard>
      <form @submit.prevent="handleSubmit">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <BaseInputGroup :label="$t('suppliers.name')">
            <BaseInput v-model="form.name" required />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('suppliers.email')">
            <BaseInput v-model="form.email" type="email" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('suppliers.tax_id')">
            <BaseInput v-model="form.tax_id" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('suppliers.phone')">
            <BaseInput v-model="form.phone" />
          </BaseInputGroup>

          <BaseInputGroup :label="$t('suppliers.website')">
            <BaseInput v-model="form.website" />
          </BaseInputGroup>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <BaseButton variant="secondary" @click="$router.push('/admin/suppliers')">
            {{ $t('general.cancel') }}
          </BaseButton>
          <BaseButton variant="primary" type="submit">
            {{ isEdit ? $t('general.update') : $t('general.create') }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </BasePage>
</template>

<script setup>
import { reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'

const suppliersStore = useSuppliersStore()
const route = useRoute()
const router = useRouter()

const form = reactive({
  id: null,
  name: '',
  email: '',
  tax_id: '',
  phone: '',
  website: '',
})

const isEdit = computed(() => !!route.params.id)

function hydrateForm(data) {
  form.id = data.id
  form.name = data.name
  form.email = data.email
  form.tax_id = data.tax_id
  form.phone = data.phone
  form.website = data.website
}

function handleSubmit() {
  const payload = { ...form }
  if (isEdit.value) {
    suppliersStore.updateSupplier(payload).then(() => {
      router.push('/admin/suppliers')
    })
  } else {
    suppliersStore.createSupplier(payload).then(() => {
      router.push('/admin/suppliers')
    })
  }
}

onMounted(() => {
  if (isEdit.value) {
    suppliersStore.fetchSupplier(route.params.id).then((response) => {
      hydrateForm(response.data.data)
    })
  }
})
</script>

