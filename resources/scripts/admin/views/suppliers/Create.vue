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
          <BaseInputGroup
            :label="$t('suppliers.name')"
            :error="v$.form.name.$error && v$.form.name.$errors[0].$message"
            required
          >
            <BaseInput
              v-model="form.name"
              :invalid="v$.form.name.$error"
              @input="v$.form.name.$touch()"
            />
          </BaseInputGroup>

          <BaseInputGroup
            :label="$t('suppliers.email')"
            :error="v$.form.email.$error && v$.form.email.$errors[0].$message"
            required
          >
            <BaseInput
              v-model="form.email"
              type="email"
              :invalid="v$.form.email.$error"
              @input="v$.form.email.$touch()"
            />
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
import { useI18n } from 'vue-i18n'
import { useSuppliersStore } from '@/scripts/admin/stores/suppliers'
import { useVuelidate } from '@vuelidate/core'
import { required, email, helpers } from '@vuelidate/validators'

const { t } = useI18n()
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

const rules = {
  form: {
    name: {
      required: helpers.withMessage(t('validation.required'), required),
    },
    email: {
      required: helpers.withMessage(t('validation.required'), required),
      email: helpers.withMessage(t('validation.email_incorrect'), email),
    },
  },
}

const v$ = useVuelidate(rules, { form })

const isEdit = computed(() => !!route.params.id)

function hydrateForm(data) {
  form.id = data.id
  form.name = data.name
  form.email = data.email
  form.tax_id = data.tax_id
  form.phone = data.phone
  form.website = data.website
}

async function handleSubmit() {
  v$.value.$touch()
  if (v$.value.$invalid) {
    return
  }

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

